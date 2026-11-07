<x-app-layout>
    <div class="page-heading experiment-heading">
        <div>
            <p class="eyebrow">Hasil eksperimen #{{ $experiment->id }}</p>
            <h1>Evaluasi skema enkripsi</h1>
            <p class="subtitle">Dijalankan {{ $experiment->created_at->format('d M Y, H:i:s') }} <span aria-hidden="true">·</span> input {{ number_format($experiment->data_size_bytes) }} byte.</p>
        </div>
        <a class="button button-secondary" href="{{ route('experiments.index') }}">Kembali ke riwayat</a>
    </div>

    <div class="callout experiment-notice">
        <span><strong>Interpretasi hasil:</strong> angka di bawah diukur saat eksperimen berjalan, bukan angka benchmark tetap. Hasil dapat berubah karena CPU, beban mesin, dan versi Node/OpenSSL.</span>
    </div>

    <section class="card card-pad experiment-metrics-card">
        <div class="experiment-section-heading">
            <div>
                <p class="eyebrow">Ringkasan kinerja</p>
                <h2>Metrik kinerja dan penyimpanan</h2>
                <p>Durasi operasi kriptografi dalam milidetik, kecuali ukuran penyimpanan.</p>
            </div>
            <span class="badge {{ $experiment->metrics['tampering_rejected'] ? 'experiment-pass-badge' : 'experiment-fail-badge' }}">
                {{ $experiment->metrics['tampering_rejected'] ? 'Integritas terverifikasi' : 'Integritas perlu diperiksa' }}
            </span>
        </div>

        <div class="experiment-total-grid">
            <article class="experiment-total-card experiment-hybrid-total">
                <span>Skema hybrid</span>
                <strong>{{ number_format($experiment->metrics['hybrid_total_ms'], 3) }} <small>ms</small></strong>
                <p>Total proses enkripsi dan dekripsi</p>
            </article>
            <article class="experiment-total-card">
                <span>Baseline AES-GCM</span>
                <strong>{{ number_format($experiment->metrics['baseline_total_ms'], 3) }} <small>ms</small></strong>
                <p>Total proses enkripsi dan dekripsi</p>
            </article>
            <article class="experiment-total-card">
                <span>Overhead penyimpanan hybrid</span>
                <strong>{{ number_format($experiment->metrics['storage_overhead_bytes']) }} <small>byte</small></strong>
                <p>Tambahan metadata dan ciphertext</p>
            </article>
        </div>

        <p class="experiment-scroll-hint">Geser tabel ke samping untuk melihat semua metrik.</p>
        <div class="table-wrap experiment-metric-table-wrap" role="region" aria-label="Rincian metrik benchmark" tabindex="0">
            <table class="metric-table experiment-metric-table">
                <thead>
                    <tr><th scope="col">Tahap pengukuran</th><th scope="col">Hybrid</th><th scope="col">Baseline AES-GCM</th></tr>
                </thead>
                <tbody>
                    <tr><td>Key generation ML-KEM-768</td><td>{{ number_format($experiment->metrics['key_generation_ms'], 3) }} ms</td><td>—</td></tr>
                    <tr><td>Encapsulation</td><td>{{ number_format($experiment->metrics['kem_encapsulation_ms'], 3) }} ms</td><td>—</td></tr>
                    <tr><td>Decapsulation</td><td>{{ number_format($experiment->metrics['kem_decapsulation_ms'], 3) }} ms</td><td>—</td></tr>
                    <tr><td>HKDF saat enkripsi</td><td>{{ number_format($experiment->metrics['hkdf_encryption_ms'], 3) }} ms</td><td>—</td></tr>
                    <tr><td>HKDF saat dekripsi</td><td>{{ number_format($experiment->metrics['hkdf_decryption_ms'], 3) }} ms</td><td>—</td></tr>
                    <tr><td>Enkripsi AES-256-GCM</td><td>{{ number_format($experiment->metrics['aes_encryption_ms'], 3) }} ms</td><td>{{ number_format($experiment->metrics['baseline_encryption_ms'], 3) }} ms</td></tr>
                    <tr><td>Dekripsi AES-256-GCM</td><td>{{ number_format($experiment->metrics['aes_decryption_ms'], 3) }} ms</td><td>{{ number_format($experiment->metrics['baseline_decryption_ms'], 3) }} ms</td></tr>
                    <tr class="experiment-total-row"><td>Total encrypt + decrypt</td><td>{{ number_format($experiment->metrics['hybrid_total_ms'], 3) }} ms</td><td>{{ number_format($experiment->metrics['baseline_total_ms'], 3) }} ms</td></tr>
                    <tr><td>Ukuran metadata + ciphertext</td><td>{{ number_format($experiment->metrics['hybrid_storage_bytes']) }} byte</td><td>{{ number_format($experiment->metrics['baseline_storage_bytes']) }} byte</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="result-grid experiment-validation-grid">
        <section class="card result-card experiment-validation-card">
            <div class="experiment-validation-heading">
                <span class="experiment-validation-icon" aria-hidden="true">↻</span>
                <div>
                    <h2>Validasi roundtrip</h2>
                    <p class="subtitle">Plaintext hasil dekripsi dibandingkan dengan input sintetis.</p>
                </div>
            </div>
            <div class="experiment-validation-results">
                <div class="experiment-validation-result">
                    <span>Skema hybrid</span>
                    <span class="badge {{ $experiment->metrics['hybrid_roundtrip_matches'] ? 'experiment-pass-badge' : 'experiment-fail-badge' }}">
                        {{ $experiment->metrics['hybrid_roundtrip_matches'] ? 'Sesuai' : 'Tidak sesuai' }}
                    </span>
                </div>
                <div class="experiment-validation-result">
                    <span>Baseline AES-GCM</span>
                    <span class="badge {{ $experiment->metrics['baseline_roundtrip_matches'] ? 'experiment-pass-badge' : 'experiment-fail-badge' }}">
                        {{ $experiment->metrics['baseline_roundtrip_matches'] ? 'Sesuai' : 'Tidak sesuai' }}
                    </span>
                </div>
            </div>
        </section>
        <section class="card result-card experiment-validation-card">
            <div class="experiment-validation-heading">
                <span class="experiment-validation-icon experiment-integrity-icon" aria-hidden="true">!</span>
                <div>
                    <h2>Uji integritas</h2>
                    <p class="subtitle">Ciphertext hybrid diubah sebelum authentication tag diverifikasi.</p>
                </div>
            </div>
            <div class="experiment-integrity-result">
                <span class="badge {{ $experiment->metrics['tampering_rejected'] ? 'experiment-pass-badge' : 'experiment-fail-badge' }}">
                    {{ $experiment->metrics['tampering_rejected'] ? 'Perubahan terdeteksi dan ditolak' : 'Pengujian gagal' }}
                </span>
            </div>
        </section>
    </div>
</x-app-layout>
