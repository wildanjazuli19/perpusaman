<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sistem informasi perpustakaan</p>
            <h1>Dashboard Perpustakaan</h1>
            <p class="subtitle">Ringkasan koleksi, anggota, peminjaman, pengembalian, dan keamanan data.</p>
        </div>
        <a class="button" href="{{ route('loans.create') }}">+ Transaksi peminjaman</a>
    </div>

    <div class="summary-section-heading">
        <div>
            <h2>Ringkasan perpustakaan</h2>
            <p>Ikhtisar koleksi dan aktivitas sirkulasi saat ini.</p>
        </div>
        <span class="summary-live-label"><span aria-hidden="true"></span> Data terkini</span>
    </div>

    <div class="stats-grid dashboard-summary-grid">
        <a class="card stat-card summary-card" href="{{ route('members.index') }}">
            <div class="summary-card-top">
                <span class="stat-label">Data anggota</span>
            </div>
            <div class="stat-value">{{ number_format($memberCount) }}</div>
            <div class="summary-card-footer"><span>Data tersimpan terenkripsi</span><span class="summary-card-arrow" aria-hidden="true">→</span></div>
        </a>
        <a class="card stat-card summary-card" href="{{ route('books.index') }}">
            <div class="summary-card-top">
                <span class="stat-label">Judul buku</span>
            </div>
            <div class="stat-value">{{ number_format($bookCount) }}</div>
            <div class="summary-card-footer"><span>{{ number_format($categoryCount) }} kategori</span><span class="summary-card-arrow" aria-hidden="true">→</span></div>
        </a>
        <a class="card stat-card summary-card" href="{{ route('books.index') }}">
            <div class="summary-card-top">
                <span class="stat-label">Stok tersedia</span>
                <span class="summary-card-icon summary-stock-icon" aria-hidden="true">+</span>
            </div>
            <div class="stat-value">{{ number_format($availableStock) }}</div>
            <div class="summary-card-footer"><span>Eksemplar siap dipinjam</span><span class="summary-card-arrow" aria-hidden="true">→</span></div>
        </a>
        <a class="card stat-card summary-card" href="{{ route('loans.index') }}">
            <div class="summary-card-top">
                <span class="stat-label">Peminjaman aktif</span>
                <span class="summary-card-icon summary-loans-icon" aria-hidden="true">↗</span>
            </div>
            <div class="stat-value">{{ number_format($activeLoanCount) }}</div>
            <div class="summary-card-footer"><span>Termasuk pengembalian sebagian</span><span class="summary-card-arrow" aria-hidden="true">→</span></div>
        </a>
        <a class="card stat-card summary-card {{ $overdueLoanCount > 0 ? 'summary-card-warning' : '' }}" href="{{ route('loans.index') }}">
            <div class="summary-card-top">
                <span class="stat-label">Terlambat</span>
                <span class="summary-card-icon summary-overdue-icon" aria-hidden="true">!</span>
            </div>
            <div class="stat-value">{{ number_format($overdueLoanCount) }}</div>
            <div class="summary-card-footer"><span>Melewati tanggal jatuh tempo</span><span class="summary-card-arrow" aria-hidden="true">→</span></div>
        </a>
        <a class="card stat-card summary-card" href="{{ route('returns.index') }}">
            <div class="summary-card-top">
                <span class="stat-label">Pengembalian</span>
                <span class="summary-card-icon summary-returns-icon" aria-hidden="true">↙</span>
            </div>
            <div class="stat-value">{{ number_format($returnCount) }}</div>
            <div class="summary-card-footer"><span>Catatan transaksi tercatat</span><span class="summary-card-arrow" aria-hidden="true">→</span></div>
        </a>
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
                        <tr>
                            <th>Kode</th>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Jatuh tempo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($latestLoans as $loan)
                        <tr>
                            <td class="table-primary">{{ $loan->loan_code }}</td>
                            <td>Anggota #{{ $loan->member_id }}</td>
                            <td>{{ $loan->book?->title ?? 'Buku tidak tersedia' }}
                                <div class="table-muted">{{ (int) $loan->quantity_returned }}/{{ (int) $loan->quantity }} dikembalikan</div>
                            </td>
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
                    <thead>
                        <tr>
                            <th>Judul buku</th>
                            <th>Lokasi</th>
                            <th>Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lowStockBooks as $book)
                        <tr>
                            <td class="table-primary">{{ $book->title }}
                                <div class="table-muted">{{ $book->author }}</div>
                            </td>
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
                    <thead>
                        <tr>
                            <th>Kode pengembalian</th>
                            <th>Buku</th>
                            <th>Jumlah</th>
                            <th>Kondisi</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
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