<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Detail rekaman terenkripsi</p>
            <h1>Anggota #{{ $member->id }}</h1>
            <p class="subtitle">Dibuat {{ $member->created_at->format('d M Y, H:i:s') }} · plaintext tidak disimpan.</p>
        </div>
        <a class="button button-secondary" href="{{ route('members.index') }}">Semua anggota</a>
    </div>

    @if (isset($tamperRejected))
        <div class="alert {{ $tamperRejected ? 'alert-success' : 'alert-danger' }}" role="status">
            @if ($tamperRejected)
                Tampering terdeteksi: authentication tag menolak ciphertext yang telah diubah. Tidak ada plaintext yang dikeluarkan.
            @else
                Peringatan: ciphertext yang diubah lolos dari pemeriksaan autentikasi.
            @endif
        </div>
    @endif

    <div class="action-row">
        <form method="POST" action="{{ route('members.decrypt', $member) }}">
            @csrf
            <button class="button" type="submit">Dekripsi dan verifikasi</button>
        </form>
        <form method="POST" action="{{ route('members.tamper-test', $member) }}">
            @csrf
            <button class="button button-danger" type="submit">Uji deteksi tampering</button>
        </form>
    </div>

    @if (isset($hybridDecryption, $baselineDecryption))
        <div class="result-grid">
            @foreach (['hybrid' => $hybridDecryption, 'baseline' => $baselineDecryption] as $label => $result)
                <section class="card result-card">
                    <h2>{{ $label === 'hybrid' ? 'Hybrid ML-KEM-768' : 'Baseline AES-256-GCM' }}</h2>
                    @if ($result['authenticated'])
                        <span class="badge">Autentikasi valid</span>
                        <dl class="data-list">
                            @foreach ($result['plaintext'] as $key => $value)
                                <dt>{{ str_replace('_', ' ', ucfirst($key)) }}</dt><dd>{{ $value }}</dd>
                            @endforeach
                        </dl>
                    @else
                        <div class="alert alert-danger" style="margin: 12px 0 0">Dekripsi ditolak: authentication tag tidak valid.</div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

    <section class="card card-pad" style="margin-bottom: 16px">
        <div class="section-title">
            <div><h2>Pengukuran saat enkripsi</h2><p>Waktu proses aktual dari permintaan enkripsi rekaman ini.</p></div>
        </div>
        <table class="metric-table">
            <tbody>
                <tr><td>Hybrid - encapsulation ML-KEM</td><td>{{ number_format($member->metrics['kem_encapsulation_ms'], 3) }} ms</td></tr>
                <tr><td>Hybrid - derivasi HKDF</td><td>{{ number_format($member->metrics['hkdf_ms'], 3) }} ms</td></tr>
                <tr><td>Hybrid - enkripsi AES-GCM</td><td>{{ number_format($member->metrics['aes_encryption_ms'], 3) }} ms</td></tr>
                <tr><td>Hybrid - total enkripsi</td><td>{{ number_format($member->metrics['total_encryption_ms'], 3) }} ms</td></tr>
                <tr><td>Baseline - enkripsi AES-GCM</td><td>{{ number_format($member->metrics['baseline_encryption_ms'], 3) }} ms</td></tr>
                <tr><td>Overhead penyimpanan hybrid</td><td>{{ number_format($member->metrics['storage_overhead_bytes']) }} byte</td></tr>
            </tbody>
        </table>
    </section>

    <div class="detail-grid">
        <section class="card detail-card">
            <div class="detail-header"><div><h3>Ciphertext hybrid</h3><p>ML-KEM-768 + HKDF-SHA-256 + AES-256-GCM</p></div><span class="badge">Terenkripsi</span></div>
            <pre class="code-block">{{ json_encode($member->hybrid_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
        <section class="card detail-card">
            <div class="detail-header"><div><h3>Ciphertext baseline</h3><p>AES-256-GCM dengan kunci baseline</p></div><span class="badge badge-muted">Pembanding</span></div>
            <pre class="code-block">{{ json_encode($member->baseline_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
    </div>

    <div class="callout"><span><strong>Metadata yang disimpan:</strong> ciphertext, nonce, tag autentikasi, salt HKDF, dan ciphertext KEM. Private key ML-KEM disimpan terpisah dan dibungkus dengan APP_KEY.</span></div>
</x-app-layout>
