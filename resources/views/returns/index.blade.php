<x-app-layout>
    <div class="page-heading circulation-heading">
        <div>
            <p class="eyebrow">Arsip sirkulasi</p>
            <h1>Riwayat pengembalian</h1>
            <p class="subtitle">Lihat setiap pengembalian buku, termasuk jumlah, kondisi, dan catatan.</p>
        </div>
        <a class="button button-secondary" href="{{ route('loans.index') }}">Lihat peminjaman</a>
    </div>

    <section class="card card-pad circulation-card">
        <div class="circulation-list-heading">
            <div>
                <h2>Catatan pengembalian</h2>
                <p>
                    @if ($returns->total() > 0)
                        Menampilkan {{ $returns->firstItem() }}–{{ $returns->lastItem() }} dari {{ $returns->total() }} catatan
                    @else
                        0 catatan pengembalian
                    @endif
                </p>
            </div>
            <span class="circulation-history-label">Riwayat tersimpan</span>
        </div>

        @if ($returns->isEmpty())
            <div class="circulation-empty-state">
                <span class="circulation-empty-icon" aria-hidden="true">✓</span>
                <strong>Belum ada pengembalian tercatat</strong>
                <p>Pengembalian dapat diproses dari transaksi peminjaman yang masih aktif.</p>
                <a class="button button-secondary" href="{{ route('loans.index') }}">Buka daftar peminjaman</a>
            </div>
        @else
            <p class="circulation-scroll-hint">Geser tabel ke samping untuk melihat semua informasi.</p>
            <div class="table-wrap circulation-table-wrap" role="region" aria-label="Riwayat pengembalian buku" tabindex="0">
                <table class="circulation-table return-table">
                    <thead>
                        <tr>
                            <th scope="col">Pengembalian</th>
                            <th scope="col">Anggota</th>
                            <th scope="col">Buku</th>
                            <th scope="col">Jumlah</th>
                            <th scope="col">Kondisi</th>
                            <th scope="col">Tanggal kembali</th>
                            <th scope="col">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returns as $record)
                            @php($loan = $record->loan)
                            <tr>
                                <td>
                                    <div class="table-primary">{{ $record->return_code }}</div>
                                    <div class="table-muted">Pinjaman {{ $loan?->loan_code ?? '-' }}</div>
                                </td>
                                <td>{{ $loan ? ($members[$loan->member_id] ?? 'Anggota #'.$loan->member_id) : '-' }}</td>
                                <td>{{ $loan?->book?->title ?? 'Buku tidak ditemukan' }}</td>
                                <td><span class="circulation-quantity">{{ $record->quantity }}</span> eksemplar</td>
                                <td>
                                    @if ($record->book_condition === 'good')
                                        <span class="badge circulation-active-badge">Baik</span>
                                    @else
                                        <span class="badge circulation-overdue-badge">Rusak</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $record->returned_at->format('d/m/Y') }}</div>
                                    <div class="table-muted">{{ $record->returned_at->format('H:i') }}</div>
                                </td>
                                <td>{{ $record->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $returns->links() }}</div>
        @endif
    </section>
</x-app-layout>
