<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sirkulasi perpustakaan</p>
            <h1>Proses pengembalian</h1>
            <p class="subtitle">Catat jumlah eksemplar yang dikembalikan dan kondisi buku.</p>
        </div>
        <a class="button button-secondary" href="{{ route('loans.index') }}">Kembali</a>
    </div>

    <section class="card card-pad form-card">
        <div class="return-summary">
            <div><span class="summary-label">Kode transaksi</span><strong>{{ $loan->loan_code }}</strong></div>
            <div><span class="summary-label">Anggota</span><strong>{{ $memberName }}</strong></div>
            <div><span class="summary-label">Judul buku</span><strong>{{ $loan->book?->title ?? 'Buku tidak ditemukan' }}</strong></div>
            <div><span class="summary-label">Jumlah pinjaman</span><strong>{{ $loan->quantity }} eksemplar</strong></div>
            <div><span class="summary-label">Sudah dikembalikan</span><strong>{{ $loan->quantity_returned }} eksemplar</strong></div>
            <div><span class="summary-label">Sisa yang harus dikembalikan</span><strong>{{ $remaining }} eksemplar</strong></div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">Periksa kembali isian pengembalian. {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('loans.returns.store', $loan) }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="quantity">Jumlah dikembalikan *</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="{{ $remaining }}" value="{{ old('quantity', $remaining) }}" required>
                    @error('quantity')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="book_condition">Kondisi buku *</label>
                    <select id="book_condition" name="book_condition" required>
                        <option value="good" @selected(old('book_condition', 'good') === 'good')>Baik — masuk stok tersedia</option>
                        <option value="damaged" @selected(old('book_condition') === 'damaged')>Rusak — tidak masuk stok tersedia</option>
                    </select>
                    @error('book_condition')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field field-full">
                    <label for="notes">Catatan kondisi / pengembalian</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Contoh: sampul sedikit terlipat (opsional)">{{ old('notes') }}</textarea>
                    @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <p class="field-help" style="margin: 14px 0">Buku berkondisi rusak tetap dicatat telah kembali, tetapi tidak menambah stok yang dapat dipinjam.</p>
            <div class="action-row">
                <button class="button" type="submit" onclick="return confirm('Simpan pengembalian buku ini?')">Simpan pengembalian</button>
                <a class="button button-secondary" href="{{ route('loans.index') }}">Batal</a>
            </div>
        </form>
    </section>
</x-app-layout>
