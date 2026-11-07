<x-app-layout>
    <div class="page-heading experiment-heading">
        <div>
            <p class="eyebrow">Metode penelitian eksperimental</p>
            <h1>Benchmark kriptografi</h1>
            <p class="subtitle">Bandingkan AES-256-GCM baseline dengan ML-KEM-768, HKDF-SHA-256, dan AES-256-GCM.</p>
        </div>
        <form method="POST" action="{{ route('experiments.store') }}">
            @csrf
            <button class="button" type="submit">Jalankan benchmark</button>
        </form>
    </div>

    <div class="callout experiment-notice">
        <span><strong>Data sintetis digunakan.</strong> Pengukuran mencakup key generation, encapsulation/decapsulation, derivasi kunci, enkripsi/dekripsi, overhead penyimpanan, roundtrip, dan uji perubahan ciphertext.</span>
    </div>

    <section class="card card-pad experiment-history-card">
        <div class="experiment-list-heading">
            <div>
                <h2>Riwayat pengujian</h2>
                <p>Hasil aktual dari mesin dan versi runtime yang menjalankan aplikasi ini.</p>
            </div>
            @if ($experiments->total() > 0)
                <span class="experiment-count">{{ $experiments->total() }} <small>pengujian</small></span>
            @endif
        </div>

        @if ($experiments->isEmpty())
            <div class="experiment-empty-state">
                <span class="experiment-empty-icon" aria-hidden="true">&gt;</span>
                <strong>Belum ada hasil benchmark</strong>
                <p>Jalankan benchmark untuk membandingkan kinerja skema hybrid dengan baseline AES-GCM.</p>
                <form method="POST" action="{{ route('experiments.store') }}">
                    @csrf
                    <button class="button" type="submit">Jalankan benchmark pertama</button>
                </form>
            </div>
        @else
            <p class="experiment-scroll-hint">Geser tabel ke samping untuk melihat semua informasi.</p>
            <div class="table-wrap experiment-table-wrap" role="region" aria-label="Riwayat benchmark kriptografi" tabindex="0">
                <table class="experiment-table">
                    <thead>
                        <tr>
                            <th scope="col">Waktu uji</th>
                            <th scope="col">Ukuran data</th>
                            <th scope="col">Total hybrid</th>
                            <th scope="col">Total baseline</th>
                            <th scope="col">Uji integritas</th>
                            <th scope="col"><span class="sr-only">Detail</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($experiments as $experiment)
                            <tr>
                                <td>
                                    <div class="table-primary">{{ $experiment->created_at->format('d M Y') }}</div>
                                    <div class="table-muted">{{ $experiment->created_at->format('H:i:s') }}</div>
                                </td>
                                <td>{{ number_format($experiment->data_size_bytes) }} byte</td>
                                <td>
                                    <span class="experiment-duration">{{ number_format($experiment->metrics['hybrid_total_ms'], 3) }}</span>
                                    <span class="experiment-unit">ms</span>
                                </td>
                                <td>
                                    <span class="experiment-duration">{{ number_format($experiment->metrics['baseline_total_ms'], 3) }}</span>
                                    <span class="experiment-unit">ms</span>
                                </td>
                                <td>
                                    @if ($experiment->metrics['tampering_rejected'])
                                        <span class="badge experiment-pass-badge">Perubahan ditolak</span>
                                    @else
                                        <span class="badge experiment-fail-badge">Perlu diperiksa</span>
                                    @endif
                                </td>
                                <td><a class="button button-secondary button-small experiment-detail-link" href="{{ route('experiments.show', $experiment) }}">Lihat hasil</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $experiments->links('pagination::simple-tailwind') }}</div>
        @endif
    </section>
</x-app-layout>
