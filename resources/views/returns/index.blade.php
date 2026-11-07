<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Arsip sirkulasi</p>
            <h1>Riwayat pengembalian</h1>
            <p class="subtitle">Daftar setiap proses pengembalian, termasuk pengembalian sebagian.</p>
        </div>
        <a class="button button-secondary" href="{{ route('loans.index') }}">Lihat peminjaman</a>
    </div>

    <section class="card card-pad">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($returns->isEmpty())
            <div class="empty-state">Belum ada riwayat pengembalian buku.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Kode pengembalian</th><th>Kode pinjaman</th><th>Anggota</th><th>Buku</th><th>Jumlah</th><th>Kondisi</th><th>Tanggal kembali</th><th>Catatan</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($returns as $record)
                            @php($loan = $record->loan)
                            <tr>
                                <td><div class="table-primary">{{ $record->return_code }}</div></td>
                                <td>{{ $loan?->loan_code ?? '-' }}</td>
                                <td>{{ $loan ? ($members[$loan->member_id] ?? 'Anggota #'.$loan->member_id) : '-' }}</td>
                                <td>{{ $loan?->book?->title ?? 'Buku tidak ditemukan' }}</td>
                                <td>{{ $record->quantity }} eksemplar</td>
                                <td>
                                    @if ($record->book_condition === 'good')
                                        <span class="badge">Baik</span>
                                    @else
                                        <span class="badge badge-muted">Rusak</span>
                                    @endif
                                </td>
                                <td>{{ $record->returned_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $record->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $returns->links() }}</div>
        @endif
    </section>
</x-app-layout>
