<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sistem informasi perpustakaan</p>
            <h1>Dashboard Perpustakaan</h1>
            <p class="subtitle">Ringkasan koleksi, anggota, peminjaman, pengembalian, dan keamanan data.</p>
        </div>
        <a class="button" href="{{ route('loans.create') }}">+ Transaksi peminjaman</a>
    </div>

    <div class="stats-grid">
        <section class="card stat-card">
            <div class="stat-label">Data anggota</div>
            <div class="stat-value">{{ number_format($memberCount) }}</div>
            <div class="stat-note">Data anggota tersimpan terenkripsi</div>
        </section>
        <section class="card stat-card">
            <div class="stat-label">Judul buku</div>
            <div class="stat-value">{{ number_format($bookCount) }}</div>
            <div class="stat-note">Dalam {{ number_format($categoryCount) }} kategori</div>
        </section>
        <section class="card stat-card">
            <div class="stat-label">Stok tersedia</div>
            <div class="stat-value">{{ number_format($availableStock) }}</div>
            <div class="stat-note">Total eksemplar yang dapat dipinjam</div>
        </section>
        <section class="card stat-card">
            <div class="stat-label">Peminjaman aktif</div>
            <div class="stat-value">{{ number_format($activeLoanCount) }}</div>
            <div class="stat-note">Termasuk pengembalian sebagian</div>
        </section>
        <section class="card stat-card">
            <div class="stat-label">Terlambat</div>
            <div class="stat-value">{{ number_format($overdueLoanCount) }}</div>
            <div class="stat-note">Melewati tanggal jatuh tempo</div>
        </section>
        <section class="card stat-card">
            <div class="stat-label">Riwayat pengembalian</div>
            <div class="stat-value">{{ number_format($returnCount) }}</div>
            <div class="stat-note">Jumlah catatan transaksi pengembalian</div>
        </section>
    </div>

    <div class="dashboard-grid">
        <section class="card card-pad">
            <div class="section-title">
                <div>
                    <h2>Peminjaman terbaru</h2>
                    <p>Enam transaksi peminjaman terakhir.</p>
                </div>
                <a class="link-button" href="{{ route('loans.index') }}">Semua peminjaman</a>
            </div>

            @if ($latestLoans->isEmpty())
                <div class="empty-state">Belum ada transaksi peminjaman.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Kode</th><th>Anggota</th><th>Buku</th><th>Jatuh tempo</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($latestLoans as $loan)
                                <tr>
                                    <td class="table-primary">{{ $loan->loan_code }}</td>
                                    <td>Anggota #{{ $loan->member_id }}</td>
                                    <td>{{ $loan->book?->title ?? 'Buku tidak tersedia' }}<div class="table-muted">{{ (int) $loan->quantity_returned }}/{{ (int) $loan->quantity }} dikembalikan</div></td>
                                    <td>{{ $loan->due_date?->format('d M Y') ?? '-' }}</td>
                                    <td>
                                        @if ($loan->status === 'returned')
                                            <span class="badge">Selesai</span>
                                        @elseif ($loan->due_date && $loan->due_date->lt(today()))
                                            <span class="badge">Terlambat</span>
                                        @elseif ($loan->status === 'partially_returned')
                                            <span class="badge">Sebagian</span>
                                        @else
                                            <span class="badge">Dipinjam</span>
                                        @endif
                                    </td>
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
                    <h2>Stok buku menipis</h2>
                    <p>Buku dengan stok tersedia maksimal 2 eksemplar.</p>
                </div>
                <a class="link-button" href="{{ route('books.index') }}">Kelola buku</a>
            </div>

            @if ($lowStockBooks->isEmpty())
                <div class="empty-state">Tidak ada buku dengan stok menipis.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Judul buku</th><th>Lokasi</th><th>Stok</th></tr></thead>
                        <tbody>
                            @foreach ($lowStockBooks as $book)
                                <tr>
                                    <td class="table-primary">{{ $book->title }}<div class="table-muted">{{ $book->author }}</div></td>
                                    <td>{{ $book->shelf_location ?: '-' }}</td>
                                    <td>{{ number_format($book->stock_available) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="dashboard-grid" style="margin-top: 18px">
        <section class="card card-pad">
            <div class="section-title">
                <div>
                    <h2>Pengembalian terbaru</h2>
                    <p>Riwayat transaksi pengembalian buku.</p>
                </div>
                <a class="link-button" href="{{ route('returns.index') }}">Semua pengembalian</a>
            </div>

            @if ($latestReturns->isEmpty())
                <div class="empty-state">Belum ada riwayat pengembalian.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Kode pengembalian</th><th>Buku</th><th>Jumlah</th><th>Kondisi</th><th>Waktu</th></tr></thead>
                        <tbody>
                            @foreach ($latestReturns as $return)
                                <tr>
                                    <td class="table-primary">{{ $return->return_code }}</td>
                                    <td>{{ $return->loan?->book?->title ?? 'Buku tidak tersedia' }}</td>
                                    <td>{{ number_format($return->quantity) }}</td>
                                    <td>{{ $return->book_condition === 'good' ? 'Baik' : 'Rusak' }}</td>
                                    <td>{{ $return->returned_at?->format('d M Y, H:i') ?? '-' }}</td>
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
                    <h2>Keamanan & eksperimen</h2>
                    <p>Ringkasan komponen penelitian kriptografi hybrid.</p>
                </div>
                <a class="link-button" href="{{ route('experiments.index') }}">Eksperimen</a>
            </div>

            <div class="stats-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 14px">
                <div>
                    <div class="stat-label">Eksperimen dijalankan</div>
                    <div class="stat-value">{{ number_format($experimentCount) }}</div>
                </div>
                <div>
                    <div class="stat-label">Skema hybrid</div>
                    <div class="stat-value" style="font-size: 20px">ML-KEM-768</div>
                </div>
            </div>
            <p class="subtitle" style="margin-top: 12px">HKDF-SHA-256 digunakan untuk derivasi kunci, kemudian AES-256-GCM melindungi data anggota.</p>

            @if ($latestExperiment)
                <div class="callout" style="margin-top: 16px">
                    <span><strong>Benchmark terakhir</strong><br>
                    {{ $latestExperiment->created_at->format('d M Y, H:i') }}
                    @if (isset($latestExperiment->metrics['hybrid_total_ms']))
                        · {{ number_format((float) $latestExperiment->metrics['hybrid_total_ms'], 3) }} ms hybrid
                    @endif
                    </span>
                </div>
                <a class="button button-secondary" href="{{ route('experiments.show', $latestExperiment) }}" style="margin-top: 12px">Lihat hasil benchmark</a>
            @else
                <div class="empty-state" style="margin-top: 16px">Belum ada hasil benchmark. Jalankan eksperimen untuk mencatat hasil pengujian.</div>
            @endif
        </section>
    </div>
</x-app-layout>
