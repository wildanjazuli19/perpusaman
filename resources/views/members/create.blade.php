<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Data sintetis atau uji</p>
            <h1>Enkripsi data anggota</h1>
            <p class="subtitle">Data akan dienkripsi sebelum disimpan. Atribut plaintext tidak ditulis ke database.</p>
        </div>
        <a class="button button-secondary" href="{{ route('members.index') }}">Kembali</a>
    </div>

    <section class="card card-pad form-card">
        <div class="callout" style="margin-top: 0">
            <span><strong>Dua skema dibuat bersamaan.</strong> Hybrid ML-KEM-768/HKDF-SHA-256/AES-256-GCM dan AES-256-GCM baseline untuk perbandingan.</span>
        </div>
        <form method="POST" action="{{ route('members.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="no_anggota">Nomor anggota / NIM</label>
                    <input id="no_anggota" name="no_anggota" value="{{ old('no_anggota') }}" maxlength="50" required>
                    @error('no_anggota')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="nama">Nama anggota</label>
                    <input id="nama" name="nama" value="{{ old('nama') }}" maxlength="150" required>
                    @error('nama')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="kampus">Kampus</label>
                    <input id="kampus" name="kampus" value="{{ old('kampus', 'Meruya') }}" maxlength="100" required>
                    @error('kampus')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="tanggal_berlaku">Tanggal berlaku</label>
                    <input id="tanggal_berlaku" type="date" name="tanggal_berlaku" value="{{ old('tanggal_berlaku') }}" required>
                    @error('tanggal_berlaku')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" required>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="telepon">Nomor telepon</label>
                    <input id="telepon" type="tel" name="telepon" value="{{ old('telepon') }}" maxlength="30" required>
                    @error('telepon')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <p class="field-help" style="margin-top: 18px">Kunci AES baseline diturunkan dari APP_KEY. Jangan gunakan data anggota nyata pada prototipe ini.</p>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('members.index') }}">Batal</a>
                <button class="button" type="submit">Enkripsi dan simpan</button>
            </div>
        </form>
    </section>
</x-app-layout>
