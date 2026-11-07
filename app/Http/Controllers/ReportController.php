<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookReturn;
use App\Models\Loan;
use App\Models\Member;
use App\Services\MemberCryptography;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportController extends Controller
{
    private const TYPES = ['members', 'books', 'loans', 'returns'];

    public function index(Request $request, MemberCryptography $cryptography): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:members,books,loans,returns'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $type = $filters['type'] ?? 'members';
        [$title, $headers, $rows] = $this->buildReport(
            $type,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $cryptography
        );

        return view('reports.index', [
            'type' => $type,
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
            'isPrint' => false,
        ]);
    }

    public function print(Request $request, MemberCryptography $cryptography): View
    {
        $filters = $request->validate([
            'type' => ['required', 'in:members,books,loans,returns'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        [$title, $headers, $rows] = $this->buildReport(
            $filters['type'],
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $cryptography
        );

        return view('reports.index', [
            'type' => $filters['type'],
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
            'isPrint' => true,
        ]);
    }

    public function pdf(Request $request, MemberCryptography $cryptography): Response
    {
        $filters = $request->validate([
            'type' => ['required', 'in:members,books,loans,returns'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        [$title, $headers, $rows] = $this->buildReport(
            $filters['type'],
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $cryptography
        );

        $filename = 'laporan-'.str_replace(' ', '-', strtolower($title)).'-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('reports.pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function export(Request $request, MemberCryptography $cryptography): StreamedResponse
    {
        $filters = $request->validate([
            'type' => ['required', 'in:members,books,loans,returns'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        [$title, $headers, $rows] = $this->buildReport(
            $filters['type'],
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $cryptography
        );

        $filename = 'laporan-'.str_replace(' ', '-', strtolower($title)).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('File export CSV tidak dapat dibuat.');
            }

            try {
                // UTF-8 BOM and semicolon delimiter improve compatibility with Excel regional settings.
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $headers, ';', '"', '', "\r\n");

                foreach ($rows as $row) {
                    fputcsv($output, $this->spreadsheetSafeRow(array_values($row)), ';', '"', '', "\r\n");
                }
            } finally {
                fclose($output);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Prefix formula-like text cells so spreadsheet applications treat them as text.
     *
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    private function spreadsheetSafeRow(array $row): array
    {
        return array_map(static function (mixed $value): mixed {
            if (is_string($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1) {
                return "'".$value;
            }

            return $value;
        }, $row);
    }

    /**
     * @return array{0:string,1:array<int,string>,2:array<int,array<int,string|int>>}
     */
    private function buildReport(
        string $type,
        ?string $startDate,
        ?string $endDate,
        MemberCryptography $cryptography
    ): array {
        if ($type === 'members') {
            $query = Member::query()->latest('created_at');
            $this->applyDateFilter($query, 'created_at', $startDate, $endDate);

            $rows = $query->get()->map(function (Member $member) use ($cryptography) {
                $data = [];
                try {
                    $result = $cryptography->decryptHybrid($member->hybrid_payload);
                    if (($result['authenticated'] ?? false) && is_array($result['plaintext'] ?? null)) {
                        $data = $result['plaintext'];
                    }
                } catch (Throwable) {
                    // Jangan tampilkan detail error atau ciphertext ke laporan.
                }

                return [
                    $member->id,
                    $data['no_anggota'] ?? 'Tidak tersedia',
                    $data['nama'] ?? 'Data tidak dapat didekripsi',
                    $data['kampus'] ?? '-',
                    $data['tanggal_berlaku'] ?? '-',
                    $data['email'] ?? '-',
                    $data['telepon'] ?? '-',
                    $member->created_at?->format('d/m/Y') ?? '-',
                ];
            })->all();

            return ['Laporan Data Anggota', ['ID', 'Nomor Anggota', 'Nama', 'Kampus', 'Tanggal Berlaku', 'Email', 'Telepon', 'Tanggal Terdaftar'], $rows];
        }

        if ($type === 'books') {
            $query = Book::with('category')->latest('created_at');
            $this->applyDateFilter($query, 'created_at', $startDate, $endDate);

            $rows = $query->get()->map(fn (Book $book) => [
                $book->id,
                $book->isbn ?: '-',
                $book->title,
                $book->author,
                $book->publisher ?: '-',
                $book->category?->name ?? '-',
                $book->publication_year ?: '-',
                $book->stock_total,
                $book->stock_available,
                $book->shelf_location ?: '-',
                $book->created_at?->format('d/m/Y') ?? '-',
            ])->all();

            return ['Laporan Koleksi Buku', ['ID', 'ISBN', 'Judul Buku', 'Penulis', 'Penerbit', 'Kategori', 'Tahun', 'Stok Total', 'Stok Tersedia', 'Lokasi Rak', 'Tanggal Ditambahkan'], $rows];
        }

        if ($type === 'loans') {
            $query = Loan::with(['book', 'member'])->latest('loan_date');
            $this->applyDateFilter($query, 'loan_date', $startDate, $endDate);
            $loans = $query->get();
            $memberNames = $this->memberNames($loans->pluck('member')->filter()->unique('id'), $cryptography);

            $rows = $loans->map(fn (Loan $loan) => [
                $loan->loan_code,
                $memberNames[$loan->member_id] ?? 'Anggota #'.$loan->member_id,
                $loan->book?->title ?? 'Buku tidak ditemukan',
                $loan->quantity,
                $loan->quantity_returned ?? 0,
                $loan->loan_date?->format('d/m/Y') ?? '-',
                $loan->due_date?->format('d/m/Y') ?? '-',
                $this->loanStatus($loan->status),
                $loan->returned_at?->format('d/m/Y H:i') ?? '-',
            ])->all();

            return ['Laporan Peminjaman', ['Kode Transaksi', 'Anggota', 'Buku', 'Jumlah Pinjam', 'Sudah Kembali', 'Tanggal Pinjam', 'Jatuh Tempo', 'Status', 'Waktu Pengembalian'], $rows];
        }

        $query = BookReturn::with(['loan.book', 'loan.member'])->latest('returned_at');
        $this->applyDateFilter($query, 'returned_at', $startDate, $endDate);
        $returns = $query->get();
        $memberRecords = $returns->pluck('loan.member')->filter()->unique('id');
        $memberNames = $this->memberNames($memberRecords, $cryptography);

        $rows = $returns->map(function (BookReturn $return) use ($memberNames) {
            $loan = $return->loan;

            return [
                $return->return_code,
                $loan?->loan_code ?? '-',
                $memberNames[$loan?->member_id] ?? 'Anggota tidak tersedia',
                $loan?->book?->title ?? 'Buku tidak ditemukan',
                $return->quantity,
                $return->book_condition === 'good' ? 'Baik' : 'Rusak',
                $return->returned_at?->format('d/m/Y H:i') ?? '-',
                $return->notes ?: '-',
            ];
        })->all();

        return ['Laporan Pengembalian', ['Kode Pengembalian', 'Kode Peminjaman', 'Anggota', 'Buku', 'Jumlah Kembali', 'Kondisi Buku', 'Waktu Pengembalian', 'Catatan'], $rows];
    }

    private function applyDateFilter($query, string $column, ?string $startDate, ?string $endDate): void
    {
        if ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }
    }

    /** @param iterable<Member> $records
     * @return array<int,string>
     */
    private function memberNames(iterable $records, MemberCryptography $cryptography): array
    {
        $names = [];

        foreach ($records as $member) {
            $label = 'Anggota #'.$member->id;
            try {
                $result = $cryptography->decryptHybrid($member->hybrid_payload);
                if (($result['authenticated'] ?? false) && ! empty($result['plaintext']['nama'])) {
                    $label = $result['plaintext']['nama'].' ('.$result['plaintext']['no_anggota'].')';
                }
            } catch (Throwable) {
                // Gunakan identitas record yang aman jika dekripsi gagal.
            }
            $names[$member->id] = $label;
        }

        return $names;
    }

    private function loanStatus(?string $status): string
    {
        return match ($status) {
            'returned' => 'Dikembalikan',
            'partially_returned' => 'Dikembalikan sebagian',
            'borrowed' => 'Dipinjam',
            default => ucfirst((string) $status),
        };
    }
}
