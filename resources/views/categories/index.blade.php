<x-app-layout>
    <div class="page-heading"><div><p class="eyebrow">Pengelompokan koleksi</p><h1>Kategori buku</h1><p class="subtitle">Buat dan kelola kategori untuk menjaga katalog tetap terorganisasi.</p></div><a class="button button-secondary" href="{{ route('books.index') }}">Lihat katalog</a></div>
    <div class="category-layout">
        <section class="card card-pad">
            <div class="section-title"><div><h2>Tambah kategori</h2><p>Kategori baru dapat langsung digunakan pada data buku.</p></div></div>
            <form method="POST" action="{{ route('categories.store') }}" class="category-create-form">@csrf
                <div class="field"><label for="name">Nama kategori *</label><input id="name" name="name" value="{{ old('name') }}" maxlength="100" required placeholder="Contoh: Teknologi Informasi">@error('name')<span class="field-error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="description">Deskripsi</label><textarea id="description" name="description" rows="3" maxlength="1000" placeholder="Keterangan kategori (opsional)">{{ old('description') }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
                <button class="button" type="submit">Tambah kategori</button>
            </form>
        </section>
        <section class="card card-pad">
            <div class="section-title"><div><h2>Daftar kategori</h2><p>{{ $categories->total() }} kategori terdaftar.</p></div></div>
            @if ($categories->isEmpty())
                <div class="empty-state">Belum ada kategori. Tambahkan kategori pertama melalui formulir.</div>
            @else
                <div class="category-list">
                    @foreach ($categories as $category)
                        <article class="category-item">
                            <form method="POST" action="{{ route('categories.update', $category) }}" class="category-edit-form">@csrf @method('PUT')
                                <div class="field"><label>Nama kategori</label><input name="name" value="{{ $category->name }}" maxlength="100" required></div>
                                <div class="field"><label>Deskripsi</label><textarea name="description" rows="2" maxlength="1000">{{ $category->description }}</textarea></div>
                                <div class="category-footer"><span class="badge badge-muted">{{ $category->books_count }} buku</span><button class="button button-secondary button-small" type="submit">Simpan</button></div>
                                @error('name')<span class="field-error">{{ $message }}</span>@enderror
                            </form>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="link-button danger-link" type="submit" @disabled($category->books_count > 0)>Hapus</button></form>
                        </article>
                    @endforeach
                </div>
                <div class="pagination">{{ $categories->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
