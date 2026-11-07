<?php

namespace App\Http\Controllers;

use App\Models\Book;
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

class LoanController extends Controller
{
    public function index(MemberCryptography $cryptography): View
    {
        $loans = Loan::with(['member', 'book'])->latest()->paginate(15);
        $memberNames = $this->memberNames(
            $loans->getCollection()->pluck('member')->filter()->unique('id'),
            $cryptography
        );

        return view('loans.index', compact('loans', 'memberNames'));
    }

    public function create(MemberCryptography $cryptography): View
    {
        $members = $this->memberNames(Member::latest()->get(), $cryptography);
        $books = Book::where('stock_available', '>', 0)->orderBy('title')->get();

        return view('loans.create', compact('members', 'books'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'loan_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:loan_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $loan = DB::transaction(function () use ($validated) {
            $book = Book::whereKey($validated['book_id'])->lockForUpdate()->firstOrFail();

            if ($book->stock_available < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok buku tidak mencukupi. Stok tersedia saat ini: '.$book->stock_available.'.',
                ]);
            }

            $book->decrement('stock_available', $validated['quantity']);

            return Loan::create([
                'loan_code' => 'PPM-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'member_id' => $validated['member_id'],
                'book_id' => $validated['book_id'],
                'quantity' => $validated['quantity'],
                'loan_date' => $validated['loan_date'],
                'due_date' => $validated['due_date'],
                'status' => 'borrowed',
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('loans.index')->with('status', 'Peminjaman '.$loan->loan_code.' berhasil dicatat. Stok buku telah diperbarui.');
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
                // Do not expose cryptographic errors or plaintext; retain a safe record identifier.
            }

            $names[$member->id] = $label;
        }

        return $names;
    }
}
