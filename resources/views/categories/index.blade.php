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
        <section class="card card-pad category-create-card">
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
                <button class="button category-save-button" type="submit">
                    <span aria-hidden="true">＋</span>
                    <span>Simpan kategori</span>
                </button>
            </form>
        </section>

        <section class="card card-pad category-list-card">
            <div class="section-title category-list-heading">
                <div>
                    <h2>Daftar kategori</h2>
                    <p>Kelola nama, deskripsi, dan penggunaan kategori.</p>
                </div>
                <span class="category-count">{{ $categories->total() }} <small>kategori</small></span>
            </div>

            @if ($categories->isEmpty())
                <div class="category-empty-state">
                    <span class="category-empty-icon" aria-hidden="true">▦</span>
                    <strong>Belum ada kategori</strong>
                    <p>Tambahkan kategori melalui formulir untuk mulai mengelompokkan koleksi buku.</p>
                </div>
            @else
                <div class="category-list">
                    @foreach ($categories as $category)
                        <article class="category-item">
                            <div class="category-item-heading">
                                <span class="category-item-icon" aria-hidden="true">▦</span>
                                <div>
                                    <strong>Kategori buku</strong>
                                    <span>ID {{ $category->id }}</span>
                                </div>
                                <span class="badge badge-muted">{{ $category->books_count }} buku</span>
                            </div>
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
                                <div class="category-edit-actions">
                                    <button class="button category-save-button category-save-small" type="submit">
                                        <span aria-hidden="true">✓</span>
                                        <span>Simpan perubahan</span>
                                    </button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="category-delete-form" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="button button-danger button-small category-delete-button" type="submit" @disabled($category->books_count > 0)>
                                    <span aria-hidden="true">×</span>
                                    <span>Hapus kategori</span>
                                </button>
                                @if ($category->books_count > 0)
                                    <span class="category-delete-hint">Masih digunakan oleh buku, tidak dapat dihapus.</span>
                                @else
                                    <span class="category-delete-hint">Kategori kosong dapat dihapus.</span>
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
