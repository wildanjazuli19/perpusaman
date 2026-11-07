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

    <section class="card card-pad catalog-card">
        <form method="GET" action="{{ route('books.index') }}" class="catalog-filter">
            <label class="sr-only" for="catalog-search">Cari buku</label>
            <input id="catalog-search" type="search" name="search" value="{{ $search }}" placeholder="Cari judul, penulis, atau ISBN...">
            <label class="sr-only" for="catalog-category">Filter kategori</label>
            <select id="catalog-category" name="category">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="button button-secondary" type="submit">Terapkan filter</button>
            @if ($search || $selectedCategory)
                <a class="catalog-reset" href="{{ route('books.index') }}">Reset</a>
            @endif
        </form>

        <div class="catalog-table-heading">
            <div>
                <h2>Daftar koleksi</h2>
                <p>
                    @if ($books->total() > 0)
                        Menampilkan {{ $books->firstItem() }}–{{ $books->lastItem() }} dari {{ $books->total() }} buku
                    @else
                        0 buku ditemukan
                    @endif
                </p>
            </div>
            @if ($search || $selectedCategory)
                <span class="badge badge-muted">Filter aktif</span>
            @endif
        </div>

        @if ($books->isEmpty())
            <div class="catalog-empty-state">
                <strong>{{ $search || $selectedCategory ? 'Buku tidak ditemukan' : 'Katalog masih kosong' }}</strong>
                <span>{{ $search || $selectedCategory ? 'Coba ubah kata pencarian atau pilih kategori lain.' : 'Tambahkan buku pertama untuk mulai mengisi katalog.' }}</span>
                <div>
                    @if ($search || $selectedCategory)
                        <a class="button button-secondary" href="{{ route('books.index') }}">Hapus filter</a>
                    @else
                        <a class="button" href="{{ route('books.create') }}">+ Tambah buku</a>
                    @endif
                </div>
            </div>
        @else
            <p class="catalog-scroll-hint">Geser tabel ke samping untuk melihat semua kolom.</p>
            <div class="table-wrap catalog-table-wrap" role="region" aria-label="Daftar koleksi buku" tabindex="0">
                <table class="catalog-table">
                    <thead>
                        <tr>
                            <th scope="col">Judul buku</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Penulis</th>
                            <th scope="col">Stok tersedia</th>
                            <th scope="col">Lokasi rak</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($books as $book)
                            <tr>
                                <td>
                                    <div class="table-primary">{{ $book->title }}</div>
                                    <div class="table-muted">{{ $book->isbn ?: 'ISBN belum diisi' }} <span aria-hidden="true">·</span> {{ $book->publication_year ?: 'Tahun —' }}</div>
                                </td>
                                <td><span class="catalog-category">{{ $book->category->name }}</span></td>
                                <td>{{ $book->author }}</td>
                                <td>
                                    <span class="badge {{ $book->stock_available === 0 ? 'badge-muted' : '' }}">
                                        {{ $book->stock_available === 0 ? 'Habis' : $book->stock_available.' tersedia' }}
                                    </span>
                                    <div class="table-muted">dari {{ $book->stock_total }} eksemplar</div>
                                </td>
                                <td><span class="catalog-shelf">{{ $book->shelf_location ?: 'Belum ditentukan' }}</span></td>
                                <td>
                                    <div class="catalog-actions">
                                        <a class="link-button" href="{{ route('books.edit', $book) }}">Edit</a>
                                        <form method="POST" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Hapus buku ini dari katalog?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="link-button danger-link" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $books->links() }}</div>
        @endif
    </section>
</x-app-layout>
