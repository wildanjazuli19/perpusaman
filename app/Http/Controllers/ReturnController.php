<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookReturn;
use App\Models\Loan;
use App\Models\Member;
use App\Services\MemberCryptography;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReturnController extends Controller
{
    public function index(MemberCryptography $cryptography): View
    {
        $returns = BookReturn::with(['loan.book', 'loan.member'])
            ->latest('returned_at')
            ->paginate(15);

        $members = $this->memberNames(
            $returns->getCollection()->pluck('loan.member')->filter()->unique('id'),
            $cryptography
        );

        return view('returns.index', compact('returns', 'members'));
    }

    public function create(Loan $loan, MemberCryptography $cryptography): View|RedirectResponse
    {
        $loan->load(['book', 'member']);
        $remaining = (int) $loan->quantity - (int) $loan->quantity_returned;

        if ($remaining < 1 || $loan->status === 'returned') {
            return redirect()->route('loans.index')
                ->with('status', 'Semua eksemplar pada transaksi ini sudah dikembalikan.');
        }

        $memberNames = $this->memberNames(collect([$loan->member])->filter(), $cryptography);
        $memberName = $memberNames[$loan->member_id] ?? 'Anggota #'.$loan->member_id;

        return view('returns.create', compact('loan', 'remaining', 'memberName'));
    }

    public function store(Request $request, Loan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'book_condition' => ['required', 'in:good,damaged'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = DB::transaction(function () use ($loan, $validated) {
            $lockedLoan = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $alreadyReturned = (int) $lockedLoan->quantity_returned;
            $remaining = (int) $lockedLoan->quantity - $alreadyReturned;

            if ($lockedLoan->status === 'returned' || $remaining < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Seluruh eksemplar pada transaksi ini sudah dikembalikan.',
                ]);
            }

            if ((int) $validated['quantity'] > $remaining) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pengembalian melebihi sisa pinjaman, yaitu '.$remaining.' eksemplar.',
                ]);
            }

            // Hanya buku berkondisi baik yang kembali menambah stok tersedia.
            if ($validated['book_condition'] === 'good') {
                $book = Book::whereKey($lockedLoan->book_id)->lockForUpdate()->firstOrFail();
                $book->increment('stock_available', (int) $validated['quantity']);
            }

            $newReturned = $alreadyReturned + (int) $validated['quantity'];
            $isComplete = $newReturned >= (int) $lockedLoan->quantity;

            $record = BookReturn::create([
                'return_code' => 'RET-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'loan_id' => $lockedLoan->id,
                'quantity' => (int) $validated['quantity'],
                'book_condition' => $validated['book_condition'],
                'notes' => $validated['notes'] ?? null,
                'returned_at' => now(),
            ]);

            $lockedLoan->update([
                'quantity_returned' => $newReturned,
                'status' => $isComplete ? 'returned' : 'partially_returned',
                'returned_at' => $isComplete ? now() : null,
            ]);

            return $record;
        });

        $message = 'Pengembalian '.$return->return_code.' berhasil dicatat.';
        if ($validated['book_condition'] === 'damaged') {
            $message .= ' Buku berkondisi rusak tidak ditambahkan ke stok tersedia.';
        } else {
            $message .= ' Stok tersedia telah diperbarui.';
        }

        return redirect()->route('returns.index')->with('status', $message);
    }

    /** @param iterable<Member> $records
     *  @return array<int, string>
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
                // Jangan tampilkan plaintext atau detail error kriptografi.
            }

            $names[$member->id] = $label;
        }

        return $names;
    }
}
