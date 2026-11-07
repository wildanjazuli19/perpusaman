<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Manajemen koleksi</p>
            <h1>Katalog buku</h1>
            <p class="subtitle">Kelola koleksi, identitas bibliografi, lokasi rak, dan ketersediaan buku.</p>
        </div>
        <a class="button" href="{{ route('books.create') }}">+ Tambah buku</a>
    </div>

    @if ($errors->has('category'))
        <div class="alert alert-danger">{{ $errors->first('category') }}</div>
    @endif

    <section class="card card-pad">
        <form method="GET" action="{{ route('books.index') }}" class="filter-bar">
            <input type="search" name="search" value="{{ $search }}" placeholder="Cari judul, penulis, atau ISBN..." aria-label="Cari buku">
            <select name="category" aria-label="Filter kategori">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search || $selectedCategory)
                <a class="link-button" href="{{ route('books.index') }}">Reset</a>
            @endif
        </form>

        @if ($books->isEmpty())
            <div class="empty-state">Belum ada buku yang cocok. Tambahkan buku baru atau ubah kata pencarian.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Judul buku</th><th>Kategori</th><th>Penulis</th><th>Stok</th><th>Lokasi rak</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($books as $book)
                            <tr>
                                <td><div class="table-primary">{{ $book->title }}</div><div class="table-muted">{{ $book->isbn ?: 'ISBN belum diisi' }} · {{ $book->publication_year ?: 'Tahun —' }}</div></td>
                                <td>{{ $book->category->name }}</td>
                                <td>{{ $book->author }}</td>
                                <td><span class="badge {{ $book->stock_available === 0 ? 'badge-muted' : '' }}">{{ $book->stock_available }} / {{ $book->stock_total }} tersedia</span></td>
                                <td>{{ $book->shelf_location ?: '—' }}</td>
                                <td><div class="table-actions"><a class="link-button" href="{{ route('books.edit', $book) }}">Edit</a>
                                    <form method="POST" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Hapus buku ini dari katalog?')">@csrf @method('DELETE')<button class="link-button danger-link" type="submit">Hapus</button></form></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $books->links() }}</div>
        @endif
    </section>
</x-app-layout>
