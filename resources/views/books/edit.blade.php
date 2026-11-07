<x-app-layout>
    <div class="page-heading"><div><p class="eyebrow">Koleksi perpustakaan</p><h1>Edit buku</h1><p class="subtitle">Perbarui informasi untuk: {{ $book->title }}</p></div><a class="button button-secondary" href="{{ route('books.index') }}">Kembali</a></div>
    <section class="card card-pad form-card">
        <form method="POST" action="{{ route('books.update', $book) }}">@csrf @method('PUT')
            @include('books.form', ['book' => $book])
            <div class="form-actions"><a class="button button-secondary" href="{{ route('books.index') }}">Batal</a><button class="button" type="submit">Simpan perubahan</button></div>
        </form>
    </section>
</x-app-layout>
