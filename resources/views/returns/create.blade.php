<x-app-layout>
    @php($returnedPercent = $loan->quantity > 0 ? (int) round(($loan->quantity_returned / $loan->quantity) * 100) : 0)

    <div class="page-heading circulation-heading">
        <div>
            <p class="eyebrow">Sirkulasi perpustakaan</p>
            <h1>Proses pengembalian</h1>
            <p class="subtitle">Catat jumlah eksemplar yang kembali dan kondisi setiap buku.</p>
        </div>
        <a class="button button-secondary" href="{{ route('loans.index') }}">Kembali ke peminjaman</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Periksa kembali data pengembalian. {{ $errors->first() }}</div>
    @endif

    <section class="card card-pad circulation-return-card">
        <div class="circulation-return-heading">
            <div>
                <p class="eyebrow">Ringkasan transaksi</p>
                <h2>{{ $loan->loan_code }}</h2>
                <p>{{ $memberName }} <span aria-hidden="true">·</span> {{ $loan->book?->title ?? 'Buku tidak ditemukan' }}</p>
            </div>
            <span class="badge {{ $loan->due_date->lt(today()) ? 'circulation-overdue-badge' : 'circulation-active-badge' }}">
                {{ $loan->due_date->lt(today()) ? 'Terlambat' : 'Sedang dipinjam' }}
            </span>
        </div>

        <div class="circulation-return-progress" aria-label="Progres pengembalian">
            <div class="circulation-progress-heading">
                <span>Progres pengembalian</span>
                <strong>{{ $loan->quantity_returned }} dari {{ $loan->quantity }} eksemplar</strong>
            </div>
            <div class="circulation-progress-track" role="progressbar" aria-valuenow="{{ $returnedPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Persentase eksemplar yang telah dikembalikan">
                <span style="width: {{ $returnedPercent }}%"></span>
            </div>
        </div>

        <div class="circulation-return-summary">
            <div class="circulation-return-stat">
                <span>Jumlah pinjaman</span>
                <strong>{{ $loan->quantity }} <small>eksemplar</small></strong>
            </div>
            <div class="circulation-return-stat">
                <span>Sudah kembali</span>
                <strong>{{ $loan->quantity_returned }} <small>eksemplar</small></strong>
            </div>
            <div class="circulation-return-stat circulation-remaining-stat">
                <span>Sisa pengembalian</span>
                <strong>{{ $remaining }} <small>eksemplar</small></strong>
            </div>
            <div class="circulation-return-stat">
                <span>Jatuh tempo</span>
                <strong>{{ $loan->due_date->format('d/m/Y') }}</strong>
            </div>
        </div>

        <div class="circulation-form-divider"></div>
        <div class="circulation-form-heading">
            <span class="circulation-form-icon circulation-return-icon" aria-hidden="true">↙</span>
            <div>
                <h2>Data pengembalian</h2>
                <p>Isi jumlah dan kondisi buku yang diterima.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('loans.returns.store', $loan) }}">
            @csrf
            <div class="form-grid circulation-form-grid">
                <div class="field">
                    <label for="quantity">Jumlah dikembalikan <span aria-hidden="true">*</span></label>
                    <input id="quantity" name="quantity" type="number" min="1" max="{{ $remaining }}" value="{{ old('quantity', $remaining) }}" required>
                    <span class="field-hint">Maksimal {{ $remaining }} eksemplar untuk transaksi ini.</span>
                    @error('quantity')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="book_condition">Kondisi buku <span aria-hidden="true">*</span></label>
                    <select id="book_condition" name="book_condition" required>
                        <option value="good" @selected(old('book_condition', 'good') === 'good')>Baik — stok tersedia bertambah</option>
                        <option value="damaged" @selected(old('book_condition') === 'damaged')>Rusak — tidak masuk stok tersedia</option>
                    </select>
                    @error('book_condition')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field field-full">
                    <label for="notes">Catatan kondisi <span class="field-optional">Opsional</span></label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Contoh: sampul sedikit terlipat">{{ old('notes') }}</textarea>
                    @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="circulation-form-note circulation-damage-note">
                <span aria-hidden="true">i</span>
                Buku rusak tetap dicatat telah kembali, tetapi tidak menambah stok yang dapat dipinjam.
            </div>
            <div class="action-row circulation-form-actions">
                <a class="button button-secondary" href="{{ route('loans.index') }}">Batal</a>
                <button class="button" type="submit" onclick="return confirm('Simpan pengembalian buku ini?')">Simpan pengembalian</button>
            </div>
        </form>
    </section>
</x-app-layout>
