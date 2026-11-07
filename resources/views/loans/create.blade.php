<x-app-layout>
    <div class="page-heading circulation-heading">
        <div>
            <p class="eyebrow">Transaksi baru</p>
            <h1>Catat peminjaman</h1>
            <p class="subtitle">Lengkapi data anggota, buku, dan tanggal pinjam. Stok diperbarui setelah transaksi tersimpan.</p>
        </div>
        <a class="button button-secondary" href="{{ route('loans.index') }}">Kembali ke peminjaman</a>
    </div>

    @if ($members === [])
        <div class="alert alert-warning">Belum ada anggota. Tambahkan anggota terlebih dahulu melalui menu Data Anggota.</div>
    @endif
    @if ($books->isEmpty())
        <div class="alert alert-warning">Tidak ada buku dengan stok tersedia. Tambahkan buku atau perbarui stok di Katalog Buku.</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Periksa kembali data peminjaman. {{ $errors->first() }}</div>
    @endif

    <section class="card card-pad circulation-form-card">
        <div class="circulation-form-heading">
            <span class="circulation-form-icon" aria-hidden="true">＋</span>
            <div>
                <h2>Informasi peminjaman</h2>
                <p>Kolom bertanda <span aria-hidden="true">*</span> wajib diisi.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('loans.store') }}">
            @csrf
            <div class="form-grid circulation-form-grid">
                <div class="field">
                    <label for="member_id">Anggota <span aria-hidden="true">*</span></label>
                    <select id="member_id" name="member_id" required>
                        <option value="">Pilih anggota</option>
                        @foreach ($members as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('member_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('member_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="book_id">Buku <span aria-hidden="true">*</span></label>
                    <select id="book_id" name="book_id" required>
                        <option value="">Pilih buku</option>
                        @foreach ($books as $book)
                            <option value="{{ $book->id }}" @selected((string) old('book_id') === (string) $book->id)>{{ $book->title }} — {{ $book->stock_available }} tersedia</option>
                        @endforeach
                    </select>
                    @error('book_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="quantity">Jumlah eksemplar <span aria-hidden="true">*</span></label>
                    <input id="quantity" name="quantity" type="number" min="1" max="100" value="{{ old('quantity', 1) }}" required>
                    <span class="field-hint">Maksimal 100 eksemplar per transaksi.</span>
                    @error('quantity')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="loan_date">Tanggal pinjam <span aria-hidden="true">*</span></label>
                    <input id="loan_date" name="loan_date" type="date" value="{{ old('loan_date', now()->toDateString()) }}" required>
                    @error('loan_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="due_date">Tanggal jatuh tempo <span aria-hidden="true">*</span></label>
                    <input id="due_date" name="due_date" type="date" min="{{ old('loan_date', now()->toDateString()) }}" value="{{ old('due_date', now()->addDays(7)->toDateString()) }}" required>
                    @error('due_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field field-full">
                    <label for="notes">Catatan <span class="field-optional">Opsional</span></label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Tambahkan catatan terkait transaksi bila diperlukan">{{ old('notes') }}</textarea>
                    @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="circulation-form-note">
                <span aria-hidden="true">i</span>
                Satu transaksi mencatat satu judul buku. Stok tersedia akan berkurang sesuai jumlah eksemplar.
            </div>
            <div class="action-row circulation-form-actions">
                <a class="button button-secondary" href="{{ route('loans.index') }}">Batal</a>
                <button class="button" type="submit" @disabled($members === [] || $books->isEmpty())>Simpan peminjaman</button>
            </div>
        </form>
    </section>
</x-app-layout>
