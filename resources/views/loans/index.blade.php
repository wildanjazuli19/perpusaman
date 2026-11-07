<x-app-layout>
    <div class="page-heading circulation-heading">
        <div>
            <p class="eyebrow">Sirkulasi perpustakaan</p>
            <h1>Peminjaman buku</h1>
            <p class="subtitle">Pantau transaksi aktif, tanggal jatuh tempo, dan proses pengembalian.</p>
        </div>
        <a class="button" href="{{ route('loans.create') }}">+ Catat peminjaman</a>
    </div>

    <section class="card card-pad circulation-card">
        <div class="circulation-list-heading">
            <div>
                <h2>Daftar transaksi</h2>
                <p>
                    @if ($loans->total() > 0)
                        Menampilkan {{ $loans->firstItem() }}–{{ $loans->lastItem() }} dari {{ $loans->total() }} transaksi
                    @else
                        0 transaksi peminjaman
                    @endif
                </p>
            </div>
            <a class="circulation-history-link" href="{{ route('returns.index') }}">Lihat riwayat pengembalian <span aria-hidden="true">→</span></a>
        </div>

        @if ($loans->isEmpty())
            <div class="circulation-empty-state">
                <span class="circulation-empty-icon" aria-hidden="true">↗</span>
                <strong>Belum ada transaksi peminjaman</strong>
                <p>Catat peminjaman untuk mulai mengelola buku yang sedang dipinjam.</p>
                <a class="button" href="{{ route('loans.create') }}">+ Catat peminjaman</a>
            </div>
        @else
            <p class="circulation-scroll-hint">Geser tabel ke samping untuk melihat semua informasi.</p>
            <div class="table-wrap circulation-table-wrap" role="region" aria-label="Daftar transaksi peminjaman" tabindex="0">
                <table class="circulation-table loan-table">
                    <thead>
                        <tr>
                            <th scope="col">Transaksi</th>
                            <th scope="col">Anggota</th>
                            <th scope="col">Buku</th>
                            <th scope="col">Tanggal pinjam</th>
                            <th scope="col">Jatuh tempo</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loans as $loan)
                            @php($isOverdue = in_array($loan->status, ['borrowed', 'partially_returned'], true) && $loan->due_date->lt(today()))

                            <tr>
                                <td>
                                    <div class="table-primary">{{ $loan->loan_code }}</div>
                                    <div class="table-muted">{{ $loan->quantity }} eksemplar dipinjam</div>
                                </td>
                                <td>{{ $memberNames[$loan->member_id] ?? 'Anggota #'.$loan->member_id }}</td>
                                <td>{{ $loan->book?->title ?? 'Buku tidak ditemukan' }}</td>
                                <td>{{ $loan->loan_date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="{{ $isOverdue ? 'circulation-overdue-date' : '' }}">{{ $loan->due_date->format('d/m/Y') }}</span>
                                    @if ($isOverdue)
                                        <div class="table-muted circulation-overdue-note">Lewat jatuh tempo</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($loan->status === 'returned')
                                        <span class="badge">Selesai</span>
                                    @elseif ($isOverdue)
                                        <span class="badge circulation-overdue-badge">{{ $loan->status === 'partially_returned' ? 'Terlambat · sebagian' : 'Terlambat' }}</span>
                                    @elseif ($loan->status === 'partially_returned')
                                        <span class="badge circulation-partial-badge">Sebagian kembali</span>
                                    @else
                                        <span class="badge circulation-active-badge">Dipinjam</span>
                                    @endif
                                    @if ($loan->quantity_returned > 0 && $loan->status !== 'returned')
                                        <div class="table-muted">{{ $loan->quantity_returned }} dari {{ $loan->quantity }} kembali</div>
                                    @endif
                                </td>
                                <td>
                                    @if (in_array($loan->status, ['borrowed', 'partially_returned'], true))
                                        <a href="{{ route('loans.returns.create', $loan) }}" class="button button-secondary button-small circulation-action">
                                            Proses kembali
                                        </a>
                                    @else
                                        <span class="circulation-completed">Selesai{{ $loan->returned_at ? ' · '.$loan->returned_at->format('d/m/Y') : '' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $loans->links() }}</div>
        @endif
    </section>
</x-app-layout>
