<x-app-layout>
    <div class="page-heading">
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

    <div class="callout" style="margin-top: 0">
        <span><strong>Data sintetis digunakan.</strong> Pengukuran mencakup key generation, encapsulation/decapsulation, derivasi kunci, enkripsi/dekripsi, overhead penyimpanan, roundtrip, dan uji perubahan ciphertext.</span>
    </div>

    <section class="card card-pad">
        <div class="section-title">
            <div><h2>Riwayat pengujian</h2><p>Hasil aktual di mesin dan versi runtime yang menjalankan aplikasi ini.</p></div>
        </div>
        @if ($experiments->isEmpty())
            <div class="empty-state">Belum ada eksperimen. Jalankan benchmark untuk membuat hasil pertama.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Waktu uji</th><th>Ukuran data</th><th>Total hybrid</th><th>Total baseline</th><th>Deteksi tampering</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($experiments as $experiment)
                            <tr>
                                <td>{{ $experiment->created_at->format('d M Y, H:i:s') }}</td>
                                <td>{{ number_format($experiment->data_size_bytes) }} byte</td>
                                <td>{{ number_format($experiment->metrics['hybrid_total_ms'], 3) }} ms</td>
                                <td>{{ number_format($experiment->metrics['baseline_total_ms'], 3) }} ms</td>
                                <td><span class="badge">{{ $experiment->metrics['tampering_rejected'] ? 'Ditolak' : 'Gagal' }}</span></td>
                                <td><a class="link-button" href="{{ route('experiments.show', $experiment) }}">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $experiments->links('pagination::simple-tailwind') }}</div>
        @endif
    </section>
</x-app-layout>
