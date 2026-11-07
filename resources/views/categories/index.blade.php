<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Pengelompokan koleksi</p>
            <h1>Kategori buku</h1>
            <p class="subtitle">Buat dan kelola kategori untuk menjaga katalog tetap terorganisasi.</p>
        </div>
        <a class="button button-secondary" href="{{ route('books.index') }}">Lihat katalog</a>
    </div>

    @if ($errors->has('category'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('category') }}</div>
    @endif

    <div class="category-layout">
        <section class="card card-pad">
            <div class="section-title">
                <div>
                    <h2>Tambah kategori</h2>
                    <p>Kategori baru dapat langsung digunakan pada data buku.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('categories.store') }}" class="category-create-form">
                @csrf
                <div class="field">
                    <label for="new-category-name">Nama kategori <span aria-hidden="true">*</span></label>
                    <input id="new-category-name" name="name" value="{{ old('name', '', 'categoryCreate') }}" maxlength="100" required placeholder="Contoh: Teknologi Informasi" @error('name', 'categoryCreate') aria-invalid="true" aria-describedby="new-category-name-error" @enderror>
                    @error('name', 'categoryCreate')<span id="new-category-name-error" class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="new-category-description">Deskripsi</label>
                    <textarea id="new-category-description" name="description" rows="4" maxlength="1000" placeholder="Keterangan kategori (opsional)" @error('description', 'categoryCreate') aria-invalid="true" aria-describedby="new-category-description-error" @enderror>{{ old('description', '', 'categoryCreate') }}</textarea>
                    @error('description', 'categoryCreate')<span id="new-category-description-error" class="field-error">{{ $message }}</span>@enderror
                </div>
                <button class="button" type="submit">Tambah kategori</button>
            </form>
        </section>

        <section class="card card-pad">
            <div class="section-title">
                <div>
                    <h2>Daftar kategori</h2>
                    <p>{{ $categories->total() }} kategori terdaftar.</p>
                </div>
            </div>

            @if ($categories->isEmpty())
                <div class="empty-state">Belum ada kategori. Tambahkan kategori pertama melalui formulir.</div>
            @else
                <div class="category-list">
                    @foreach ($categories as $category)
                        <article class="category-item">
                            <form method="POST" action="{{ route('categories.update', $category) }}" class="category-edit-form">
                                @csrf
                                @method('PUT')
                                <div class="field">
                                    <label for="category-name-{{ $category->id }}">Nama kategori</label>
                                    <input id="category-name-{{ $category->id }}" name="name" value="{{ old('name', $category->name, 'categoryEdit'.$category->id) }}" maxlength="100" required @error('name', 'categoryEdit'.$category->id) aria-invalid="true" aria-describedby="category-name-error-{{ $category->id }}" @enderror>
                                    @error('name', 'categoryEdit'.$category->id)<span id="category-name-error-{{ $category->id }}" class="field-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="field">
                                    <label for="category-description-{{ $category->id }}">Deskripsi</label>
                                    <textarea id="category-description-{{ $category->id }}" name="description" rows="2" maxlength="1000" @error('description', 'categoryEdit'.$category->id) aria-invalid="true" aria-describedby="category-description-error-{{ $category->id }}" @enderror>{{ old('description', $category->description, 'categoryEdit'.$category->id) }}</textarea>
                                    @error('description', 'categoryEdit'.$category->id)<span id="category-description-error-{{ $category->id }}" class="field-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="category-footer">
                                    <span class="badge badge-muted">{{ $category->books_count }} buku</span>
                                    <button class="button button-secondary button-small" type="submit">Simpan</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="category-delete-form" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="link-button danger-link" type="submit" @disabled($category->books_count > 0)>Hapus</button>
                                @if ($category->books_count > 0)
                                    <span class="category-delete-hint">Masih digunakan</span>
                                @endif
                            </form>
                        </article>
                    @endforeach
                </div>
                <div class="pagination">{{ $categories->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
