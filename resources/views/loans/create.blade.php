<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Transaksi baru</p>
            <h1>Catat peminjaman</h1>
            <p class="subtitle">Pilih anggota dan buku. Stok akan berkurang setelah transaksi berhasil disimpan.</p>
        </div>
        <a class="button button-secondary" href="{{ route('loans.index') }}">Kembali</a>
    </div>

    @if ($members === [])
        <div class="alert alert-warning">Belum ada anggota. Tambahkan anggota terlebih dahulu melalui menu Data Anggota.</div>
    @endif
    @if ($books->isEmpty())
        <div class="alert alert-warning">Tidak ada buku dengan stok tersedia. Tambahkan buku atau perbarui stok di Katalog Buku.</div>
    @endif

    <section class="card card-pad">
        <form method="POST" action="{{ route('loans.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field field-full">
                    <label for="member_id">Anggota *</label>
                    <select id="member_id" name="member_id" required>
                        <option value="">Pilih anggota</option>
                        @foreach ($members as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('member_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('member_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field field-full">
                    <label for="book_id">Buku *</label>
                    <select id="book_id" name="book_id" required>
                        <option value="">Pilih buku</option>
                        @foreach ($books as $book)
                            <option value="{{ $book->id }}" @selected((string) old('book_id') === (string) $book->id)>{{ $book->title }} — stok {{ $book->stock_available }}</option>
                        @endforeach
                    </select>
                    @error('book_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="quantity">Jumlah eksemplar *</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="100" value="{{ old('quantity', 1) }}" required>
                    @error('quantity')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="loan_date">Tanggal pinjam *</label>
                    <input id="loan_date" name="loan_date" type="date" value="{{ old('loan_date', now()->toDateString()) }}" required>
                    @error('loan_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="due_date">Tanggal jatuh tempo *</label>
                    <input id="due_date" name="due_date" type="date" min="{{ old('loan_date', now()->toDateString()) }}" value="{{ old('due_date', now()->addDays(7)->toDateString()) }}" required>
                    @error('due_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field field-full">
                    <label for="notes">Catatan</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                    @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <p class="field-help" style="margin: 14px 0">* Wajib diisi. Satu transaksi saat ini mencatat satu judul buku dengan jumlah eksemplar yang dapat ditentukan.</p>
            <div class="action-row">
                <button class="button" type="submit" @disabled($members === [] || $books->isEmpty())>Simpan peminjaman</button>
                <a class="button button-secondary" href="{{ route('loans.index') }}">Batal</a>
            </div>
        </form>
    </section>
</x-app-layout>
