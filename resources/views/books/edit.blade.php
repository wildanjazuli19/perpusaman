<x-app-layout>
    <div class="page-heading book-edit-heading">
        <div>
            <p class="eyebrow">Koleksi perpustakaan</p>
            <h1>Edit buku</h1>
            <p class="subtitle">Perbarui detail katalog untuk <strong>{{ $book->title }}</strong>.</p>
        </div>
        <a class="button button-secondary book-back-link" href="{{ route('books.index') }}">
            <span aria-hidden="true">←</span>
            <span>Kembali ke katalog</span>
        </a>
    </div>

    <section class="book-stock-summary" aria-label="Ringkasan stok buku">
        <div class="book-stock-item">
            <span>Total eksemplar</span>
            <strong>{{ number_format($book->stock_total) }}</strong>
        </div>
        <div class="book-stock-item">
            <span>Tersedia</span>
            <strong>{{ number_format($book->stock_available) }}</strong>
        </div>
        <div class="book-stock-item">
            <span>Sedang dipinjam</span>
            <strong>{{ number_format($book->stock_total - $book->stock_available) }}</strong>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Perubahan belum dapat disimpan.</strong> Periksa kembali isian yang ditandai.
            @if ($errors->has('stock_total'))
                <div>{{ $errors->first('stock_total') }}</div>
            @endif
        </div>
    @endif

    <section class="card card-pad form-card book-edit-card">
        <div class="book-edit-form-heading">
            <span class="book-edit-icon" aria-hidden="true">✎</span>
            <div>
                <h2>Informasi buku</h2>
                <p>Kolom bertanda <span aria-hidden="true">*</span> wajib diisi.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('books.update', $book) }}">
            @csrf
            @method('PUT')
            @include('books.form', ['book' => $book])
            <div class="book-stock-note">
                <span aria-hidden="true">i</span>
                Jumlah eksemplar adalah jumlah total. {{ $book->stock_total - $book->stock_available }} buku sedang dipinjam dan tetap dihitung sebagai bagian dari total.
            </div>
            <div class="form-actions book-edit-actions">
                <a class="button button-secondary" href="{{ route('books.index') }}">Batal</a>
                <button class="button" type="submit">Simpan perubahan</button>
            </div>
        </form>
    </section>
</x-app-layout>
