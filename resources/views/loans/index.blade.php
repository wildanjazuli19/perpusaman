<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sirkulasi perpustakaan</p>
            <h1>Peminjaman buku</h1>
            <p class="subtitle">Pantau transaksi, jatuh tempo, dan status pengembalian buku.</p>
        </div>
        <a class="button" href="{{ route('loans.create') }}">+ Catat peminjaman</a>
    </div>

    <section class="card card-pad">
        @if ($loans->isEmpty())
            <div class="empty-state">
                Belum ada transaksi peminjaman. Klik “Catat peminjaman” untuk membuat transaksi pertama.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode transaksi</th>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Tanggal pinjam</th>
                            <th>Jatuh tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($loans as $loan)
                            @php($isOverdue = $loan->status === 'borrowed' && $loan->due_date->lt(today()))

                            <tr>
                                <td>
                                    <div class="table-primary">{{ $loan->loan_code }}</div>
                                    <div class="table-muted">{{ $loan->quantity }} eksemplar</div>
                                </td>

                                <td>
                                    {{ $memberNames[$loan->member_id] ?? 'Anggota #'.$loan->member_id }}
                                </td>

                                <td>
                                    {{ $loan->book?->title ?? 'Buku tidak ditemukan' }}
                                </td>

                                <td>{{ $loan->loan_date->format('d/m/Y') }}</td>

                                <td>{{ $loan->due_date->format('d/m/Y') }}</td>

                                <td>
                                    @if ($loan->status === 'returned')
                                        <span class="badge">Dikembalikan</span>
                                    @elseif ($loan->status === 'partially_returned')
                                        <span class="badge badge-muted">Dikembalikan sebagian</span>
                                    @elseif ($isOverdue)
                                        <span class="badge badge-muted">Terlambat</span>
                                    @else
                                        <span class="badge">Dipinjam</span>
                                    @endif
                                </td>

                                <td>
                                    @if (in_array($loan->status, ['borrowed', 'partially_returned']))
                                        <a
                                            href="{{ route('loans.returns.create', $loan) }}"
                                            class="button button-secondary button-small"
                                        >
                                            Proses Pengembalian
                                        </a>
                                    @else
                                        <span class="table-muted">
                                            {{ $loan->returned_at?->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                {{ $loans->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
