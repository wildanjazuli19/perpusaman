<x-app-layout>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Keamanan dan pemantauan sistem</p>
            <h1>Audit Aktivitas</h1>
            <p class="subtitle">Riwayat perubahan data yang dilakukan oleh Admin di Perpus Aman.</p>
        </div>
    </div>

    <section class="card card-pad">
        <div class="section-title">
            <div>
                <h2>Riwayat aktivitas</h2>
                <p>Hanya aksi perubahan yang berhasil dicatat. Isi formulir dan data pribadi tidak disimpan dalam log.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('activity-logs.index') }}" class="report-filter-form" style="margin-bottom: 18px">
            <div class="field" style="flex: 1; min-width: 220px">
                <label for="search">Cari aktivitas</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Aksi, modul, URL, Admin, atau IP">
            </div>
            <div class="report-filter-actions">
                <button class="button" type="submit">Cari</button>
                <a class="button button-secondary" href="{{ route('activity-logs.index') }}">Reset</a>
            </div>
        </form>

        @if ($logs->isEmpty())
            <div class="empty-state">Belum ada aktivitas yang tercatat.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Waktu</th><th>Admin</th><th>Aktivitas</th><th>Objek</th><th>Metode</th><th>Alamat IP</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                                <td>{{ $log->user?->name ?? 'Akun dihapus' }}</td>
                                <td>
                                    <strong>{{ $log->action }}</strong>
                                    @if ($log->route_name)
                                        <div class="muted">{{ $log->route_name }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->subject_type ? $log->subject_type.' #'.$log->subject_id : '—' }}</td>
                                <td><span class="badge">{{ $log->method }}</span></td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $logs->links('pagination::simple-tailwind') }}</div>
        @endif
    </section>
</x-app-layout>
