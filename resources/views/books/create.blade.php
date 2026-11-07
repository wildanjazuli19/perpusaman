<x-app-layout>
    <div class="page-heading"><div><p class="eyebrow">Koleksi perpustakaan</p><h1>Tambah buku</h1><p class="subtitle">Masukkan informasi bibliografi dan jumlah eksemplar buku.</p></div><a class="button button-secondary" href="{{ route('books.index') }}">Kembali</a></div>
    @if ($categories->isEmpty())
        <div class="alert alert-warning">Tambahkan kategori terlebih dahulu sebelum memasukkan buku. <a class="link-button" href="{{ route('categories.index') }}">Kelola kategori</a></div>
    @else
        <section class="card card-pad form-card">
            <form method="POST" action="{{ route('books.store') }}">@csrf
                @include('books.form', ['book' => null])
                <div class="form-actions"><a class="button button-secondary" href="{{ route('books.index') }}">Batal</a><button class="button" type="submit">Simpan buku</button></div>
            </form>
        </section>
    @endif
</x-app-layout>
