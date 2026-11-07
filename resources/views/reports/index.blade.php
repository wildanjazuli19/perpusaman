@if ($isPrint)
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} - Perpus Aman</title>
        <style>
            body { font-family: Arial, sans-serif; color: #222; margin: 28px; }
            h1 { margin: 0 0 8px; font-size: 22px; }
            p { margin: 4px 0; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 22px; }
            th, td { border: 1px solid #bbb; padding: 7px; text-align: left; font-size: 10px; }
            th { background: #eee; }
            .actions { margin-bottom: 20px; }
            button { padding: 9px 14px; cursor: pointer; }
            @media print { .actions { display: none; } body { margin: 10mm; } }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()">Cetak / Simpan sebagai PDF</button>
            <button onclick="window.close()">Tutup</button>
        </div>
        <h1>{{ $title }}</h1>
        <p>Perpus Aman</p>
        <p>Periode:
            {{ $startDate ? \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') : 'Awal' }}
            s.d.
            {{ $endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') : 'Sekarang' }}
        </p>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>

        <table>
            <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}">Tidak ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        <script>window.addEventListener('load', () => window.print());</script>
    </body>
    </html>
@else
    <x-app-layout>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Administrasi perpustakaan</p>
                <h1>Laporan Perpustakaan</h1>
                <p class="subtitle">Rekap data anggota, koleksi buku, peminjaman, dan pengembalian.</p>
            </div>
        </div>

        <section class="card card-pad report-filter-card">
            <form method="GET" action="{{ route('reports.index') }}" class="report-filter-form">
                <div class="field">
                    <label for="type">Jenis laporan</label>
                    <select id="type" name="type">
                        <option value="members" @selected($type === 'members')>Data Anggota</option>
                        <option value="books" @selected($type === 'books')>Koleksi Buku</option>
                        <option value="loans" @selected($type === 'loans')>Peminjaman</option>
                        <option value="returns" @selected($type === 'returns')>Pengembalian</option>
                    </select>
                </div>
                <div class="field">
                    <label for="start_date">Dari tanggal</label>
                    <input id="start_date" type="date" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="field">
                    <label for="end_date">Sampai tanggal</label>
                    <input id="end_date" type="date" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="report-filter-actions">
                    <button class="button" type="submit">Tampilkan</button>
                    <a class="button button-secondary" href="{{ route('reports.index', ['type' => $type]) }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="card card-pad report-results">
            <div class="section-title report-title-row">
                <div>
                    <h2>{{ $title }}</h2>
                    <p>
                        {{ count($rows) }} data ditemukan
                        @if ($startDate || $endDate)
                            · Periode {{ $startDate ? \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') : 'awal' }}
                            sampai {{ $endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') : 'sekarang' }}
                        @endif
                    </p>
                </div>
                <div class="report-export-actions">
                    <a class="button button-secondary" href="{{ route('reports.export', request()->query()) }}">Export Excel (CSV)</a>
                    <a class="button" target="_blank" rel="noopener" href="{{ route('reports.print', request()->query()) }}">Cetak / PDF</a>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                        @empty
                            <tr><td colspan="{{ count($headers) }}">
                                <div class="empty-state">Tidak ada data untuk laporan dan periode yang dipilih.</div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </x-app-layout>
@endif
