<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Perlindungan data</p>
            <h1>Data anggota</h1>
            <p class="subtitle">Hanya ciphertext dan metadata kriptografi yang tersimpan di basis data.</p>
        </div>
        <a class="button" href="{{ route('members.create') }}">+ Enkripsi anggota</a>
    </div>

    <section class="card card-pad">
        @if ($members->isEmpty())
            <div class="empty-state">Belum ada data anggota terenkripsi.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Referensi</th><th>Waktu dibuat</th><th>Overhead hybrid</th><th>Skema</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td class="table-primary">Anggota #{{ $member->id }}<div class="table-muted">Identitas terenkripsi</div></td>
                                <td>{{ $member->created_at->format('d M Y, H:i:s') }}</td>
                                <td>{{ number_format($member->metrics['storage_overhead_bytes']) }} byte</td>
                                <td><span class="badge">ML-KEM + AES-GCM</span></td>
                                <td><a class="link-button" href="{{ route('members.show', $member) }}">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $members->links('pagination::simple-tailwind') }}</div>
        @endif
    </section>
</x-app-layout>
