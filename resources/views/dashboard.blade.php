<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sistem informasi perpustakaan</p>
            <h1>Ruang kendali keamanan</h1>
            <p class="subtitle">Prototipe perlindungan data anggota dengan kriptografi hybrid pascakuantum.</p>
        </div>
        <a class="button" href="{{ route('members.create') }}">+ Enkripsi anggota</a>
    </div>

    <div class="stats-grid">
        <section class="card stat-card">
            <div class="stat-label">Data anggota terlindungi</div>
            <div class="stat-value">{{ number_format($memberCount) }}</div>
            <div class="stat-note">Ciphertext disimpan di SQLite</div>
        </section>
        <section class="card stat-card">
            <div class="stat-label">Eksperimen dijalankan</div>
            <div class="stat-value">{{ number_format($experimentCount) }}</div>
            <div class="stat-note">Benchmark aktual pada runtime ini</div>
        </section>
        <section class="card stat-card">
            <div class="stat-label">Skema perlindungan</div>
            <div class="stat-value" style="font-size: 21px">ML-KEM-768</div>
            <div class="stat-note">HKDF-SHA-256 + AES-256-GCM</div>
        </section>
    </div>

    <div class="dashboard-grid">
        <section class="card card-pad">
            <div class="section-title">
                <div>
                    <h2>Data anggota terbaru</h2>
                    <p>Identitas tidak ditampilkan sebelum dekripsi terverifikasi.</p>
                </div>
                <a class="link-button" href="{{ route('members.index') }}">Semua data</a>
            </div>
            @if ($latestMembers->isEmpty())
                <div class="empty-state">Belum ada data terenkripsi. Tambahkan data anggota untuk memulai.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Referensi</th><th>Dibuat</th><th>Skema</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($latestMembers as $member)
                                <tr>
                                    <td class="table-primary">Anggota #{{ $member->id }}<div class="table-muted">ID anonim</div></td>
                                    <td>{{ $member->created_at->format('d M Y, H:i') }}</td>
                                    <td><span class="badge">Hybrid + baseline</span></td>
                                    <td><a class="link-button" href="{{ route('members.show', $member) }}">Buka</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card card-pad">
            <div class="section-title">
                <div>
                    <h2>Eksperimen kriptografi</h2>
                    <p>Uji roundtrip, waktu proses, overhead, dan integritas.</p>
                </div>
            </div>
            @if ($latestExperiment)
                <div class="callout">
                    <span><strong>Benchmark terakhir</strong><br>
                    {{ $latestExperiment->created_at->format('d M Y, H:i') }} ·
                    {{ number_format($latestExperiment->metrics['hybrid_total_ms'], 3) }} ms hybrid</span>
                </div>
                <a class="button button-secondary" href="{{ route('experiments.show', $latestExperiment) }}">Lihat hasil</a>
            @else
                <p class="subtitle" style="margin: 22px 0">Belum ada hasil uji. Jalankan benchmark dengan data sintetis bawaan aplikasi.</p>
            @endif
            <a class="button {{ $latestExperiment ? 'button-secondary' : '' }}" href="{{ route('experiments.index') }}" style="margin-left: 7px">Buka eksperimen</a>
        </section>
    </div>

    <div class="callout" style="margin-top: 18px">
        <span><strong>Catatan ruang lingkup:</strong> aplikasi ini adalah prototipe terpisah dari sistem operasional perpustakaan. Gunakan data sintetis; tidak ada integrasi dengan SIA atau proses peminjaman.</span>
    </div>
</x-app-layout>
