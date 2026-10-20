<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Hasil eksperimen #{{ $experiment->id }}</p>
            <h1>Evaluasi skema enkripsi</h1>
            <p class="subtitle">Dijalankan {{ $experiment->created_at->format('d M Y, H:i:s') }} · input {{ number_format($experiment->data_size_bytes) }} byte.</p>
        </div>
        <a class="button button-secondary" href="{{ route('experiments.index') }}">Riwayat eksperimen</a>
    </div>

    <div class="callout" style="margin-top: 0">
        <span><strong>Interpretasi hasil:</strong> angka di bawah diukur saat eksperimen berjalan, bukan angka benchmark tetap. Hasil dapat berubah karena CPU, beban mesin, dan versi Node/OpenSSL.</span>
    </div>

    <section class="card card-pad">
        <div class="section-title">
            <div><h2>Metrik kinerja dan penyimpanan</h2><p>Durasi operasi kriptografi dalam milidetik.</p></div>
            <span class="badge {{ $experiment->metrics['tampering_rejected'] ? '' : 'badge-muted' }}">
                {{ $experiment->metrics['tampering_rejected'] ? 'Tampering berhasil ditolak' : 'Tampering tidak ditolak' }}
            </span>
        </div>
        <div class="table-wrap">
            <table class="metric-table">
                <thead><tr><th>Metrik</th><th>Hybrid</th><th>Baseline AES-GCM</th></tr></thead>
                <tbody>
                    <tr><td>Key generation ML-KEM-768</td><td>{{ number_format($experiment->metrics['key_generation_ms'], 3) }} ms</td><td>-</td></tr>
                    <tr><td>Encapsulation</td><td>{{ number_format($experiment->metrics['kem_encapsulation_ms'], 3) }} ms</td><td>-</td></tr>
                    <tr><td>Decapsulation</td><td>{{ number_format($experiment->metrics['kem_decapsulation_ms'], 3) }} ms</td><td>-</td></tr>
                    <tr><td>HKDF saat enkripsi</td><td>{{ number_format($experiment->metrics['hkdf_encryption_ms'], 3) }} ms</td><td>-</td></tr>
                    <tr><td>HKDF saat dekripsi</td><td>{{ number_format($experiment->metrics['hkdf_decryption_ms'], 3) }} ms</td><td>-</td></tr>
                    <tr><td>Enkripsi AES-256-GCM</td><td>{{ number_format($experiment->metrics['aes_encryption_ms'], 3) }} ms</td><td>{{ number_format($experiment->metrics['baseline_encryption_ms'], 3) }} ms</td></tr>
                    <tr><td>Dekripsi AES-256-GCM</td><td>{{ number_format($experiment->metrics['aes_decryption_ms'], 3) }} ms</td><td>{{ number_format($experiment->metrics['baseline_decryption_ms'], 3) }} ms</td></tr>
                    <tr><td>Total encrypt + decrypt</td><td><strong>{{ number_format($experiment->metrics['hybrid_total_ms'], 3) }} ms</strong></td><td><strong>{{ number_format($experiment->metrics['baseline_total_ms'], 3) }} ms</strong></td></tr>
                    <tr><td>Ukuran metadata + ciphertext</td><td>{{ number_format($experiment->metrics['hybrid_storage_bytes']) }} byte</td><td>{{ number_format($experiment->metrics['baseline_storage_bytes']) }} byte</td></tr>
                    <tr><td>Storage overhead hybrid</td><td colspan="2">{{ number_format($experiment->metrics['storage_overhead_bytes']) }} byte</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="result-grid">
        <section class="card result-card">
            <h2>Validasi roundtrip</h2>
            <p class="subtitle">Plaintext setelah dekripsi dibandingkan dengan input sintetis.</p>
            <div style="margin-top: 15px">
                <p><span class="badge {{ $experiment->metrics['hybrid_roundtrip_matches'] ? '' : 'badge-muted' }}">Hybrid: {{ $experiment->metrics['hybrid_roundtrip_matches'] ? 'Sesuai' : 'Tidak sesuai' }}</span></p>
                <p><span class="badge {{ $experiment->metrics['baseline_roundtrip_matches'] ? '' : 'badge-muted' }}">Baseline: {{ $experiment->metrics['baseline_roundtrip_matches'] ? 'Sesuai' : 'Tidak sesuai' }}</span></p>
            </div>
        </section>
        <section class="card result-card">
            <h2>Integritas authentication tag</h2>
            <p class="subtitle">Ciphertext hybrid diubah satu bit sebelum verifikasi.</p>
            <div style="margin-top: 15px">
                @if ($experiment->metrics['tampering_rejected'])
                    <span class="badge">Perubahan terdeteksi dan ditolak</span>
                @else
                    <span class="badge badge-muted">Pengujian gagal</span>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
