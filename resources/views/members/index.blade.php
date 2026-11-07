<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Perlindungan data</p>
            <h1>Data anggota</h1>
            <p class="subtitle">Hanya ciphertext dan metadata kriptografi yang tersimpan di basis data.</p>
        </div>
        <a class="button" href="{{ route('members.create') }}">+ Enkripsi anggota</a>
    </div>

    <section class="card card-pad member-list-card">
        <div class="member-list-heading">
            <div>
                <h2>Rekaman anggota</h2>
                <p>
                    @if ($members->total() > 0)
                        Menampilkan {{ $members->firstItem() }}–{{ $members->lastItem() }} dari {{ $members->total() }} rekaman
                    @else
                        Belum ada rekaman tersimpan
                    @endif
                </p>
            </div>
            <span class="member-security-label"><span aria-hidden="true"></span> Data terenkripsi</span>
        </div>

        @if ($members->isEmpty())
            <div class="member-empty-state">
                <span class="member-empty-icon" aria-hidden="true">+</span>
                <strong>Belum ada data anggota</strong>
                <p>Tambahkan data untuk mengenkripsi dan membandingkan kedua skema perlindungan.</p>
                <a class="button" href="{{ route('members.create') }}">Enkripsi anggota pertama</a>
            </div>
        @else
            <p class="member-scroll-hint">Geser tabel ke samping untuk melihat seluruh informasi.</p>
            <div class="table-wrap member-table-wrap" role="region" aria-label="Daftar data anggota terenkripsi" tabindex="0">
                <table class="member-table">
                    <thead>
                        <tr>
                            <th scope="col">Referensi rekaman</th>
                            <th scope="col">Waktu dibuat</th>
                            <th scope="col">Overhead hybrid</th>
                            <th scope="col">Skema perlindungan</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td>
                                    <div class="member-reference"><span aria-hidden="true">#</span>{{ $member->id }}</div>
                                    <div class="table-muted">Identitas disimpan terenkripsi</div>
                                </td>
                                <td>
                                    <time datetime="{{ $member->created_at->toIso8601String() }}">{{ $member->created_at->format('d M Y') }}</time>
                                    <div class="table-muted">{{ $member->created_at->format('H:i') }}</div>
                                </td>
                                <td><span class="member-overhead">{{ number_format($member->metrics['storage_overhead_bytes']) }} <small>byte</small></span></td>
                                <td><span class="badge">ML-KEM-768 <span aria-hidden="true">+</span> AES-GCM</span></td>
                                <td><a class="button button-secondary button-small" href="{{ route('members.show', $member) }}">Lihat detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $members->links('pagination::simple-tailwind') }}</div>
        @endif
    </section>
</x-app-layout>
