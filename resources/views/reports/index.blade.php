@if ($isPrint)
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} - Perpus Aman</title>
        <style>
            @page { size: landscape; margin: 12mm; }
            * { box-sizing: border-box; }
            body { margin: 0; color: #1d2925; background: #f3f6f4; font: 14px/1.45 Arial, sans-serif; }
            .report-sheet { width: min(1180px, calc(100% - 40px)); margin: 28px auto; padding: 32px; border: 1px solid #dfe7e2; background: #fff; }
            .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; }
            .actions button, .actions a { display: inline-flex; min-height: 40px; align-items: center; justify-content: center; border: 1px solid #176e57; border-radius: 6px; padding: 0 14px; background: #176e57; color: #fff; font: 700 13px Arial, sans-serif; text-decoration: none; cursor: pointer; }
            .actions a { border-color: #d3ded7; background: #fff; color: #305f50; }
            .report-heading { margin-bottom: 22px; padding-bottom: 16px; border-bottom: 2px solid #176e57; }
            .report-heading p { margin: 4px 0; color: #596961; font-size: 12px; }
            .report-heading h1 { margin: 0 0 8px; color: #163a33; font-size: 24px; }
            .report-meta { display: flex; flex-wrap: wrap; gap: 6px 24px; }
            table { width: 100%; border-collapse: collapse; table-layout: auto; }
            thead { display: table-header-group; }
            th, td { border: 1px solid #cbd5cf; padding: 7px 8px; text-align: left; vertical-align: top; font-size: 10px; overflow-wrap: anywhere; }
            th { background: #eaf1ed; color: #244b3e; font-weight: 700; }
            tbody tr:nth-child(even) { background: #f7f9f8; }
            @media (max-width: 700px) {
                .report-sheet { width: 100%; margin: 0; padding: 18px; border: 0; }
                .table-wrap { overflow-x: auto; }
                table { min-width: 760px; }
            }
            @media print {
                body { background: #fff; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                .report-sheet { width: 100%; margin: 0; padding: 0; border: 0; }
                .actions { display: none !important; }
                .table-wrap { overflow: visible; }
                h1, .report-heading { break-inside: avoid; }
                tr { break-inside: avoid; page-break-inside: avoid; }
                th, td { padding: 5px 6px; font-size: 8pt; }
                a { color: inherit; text-decoration: none; }
            }
        </style>
    </head>
    <body>
        <main class="report-sheet">
            <div class="actions">
                <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
                <a href="{{ route('reports.index', array_filter(['type' => $type, 'start_date' => $startDate, 'end_date' => $endDate])) }}">Kembali ke laporan</a>
            </div>

            <header class="report-heading">
                <h1>{{ $title }}</h1>
                <p><strong>Perpus Aman</strong></p>
                <div class="report-meta">
                    <p>Periode:
                        {{ $startDate ? \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') : 'Awal' }}
                        s.d.
                        {{ $endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') : 'Sekarang' }}
                    </p>
                    <p>{{ count($rows) }} data</p>
                    <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </header>

            <div class="table-wrap">
                <table>
                    <thead><tr>@foreach ($headers as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                        @empty
                            <tr><td colspan="{{ count($headers) }}">Tidak ada data pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
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
            <div class="section-title">
                <div>
                    <h2>Atur laporan</h2>
                    <p>Pilih jenis data dan rentang tanggal yang ingin ditinjau.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('reports.index') }}" class="report-filter-form">
                <div class="field">
                    <label for="report-type">Jenis laporan</label>
                    <select id="report-type" name="type">
                        <option value="members" @selected($type === 'members')>Data Anggota</option>
                        <option value="books" @selected($type === 'books')>Koleksi Buku</option>
                        <option value="loans" @selected($type === 'loans')>Peminjaman</option>
                        <option value="returns" @selected($type === 'returns')>Pengembalian</option>
                    </select>
                </div>
                <div class="field">
                    <label for="report-start-date">Dari tanggal</label>
                    <input id="report-start-date" type="date" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="field">
                    <label for="report-end-date">Sampai tanggal</label>
                    <input id="report-end-date" type="date" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="report-filter-actions">
                    <button class="button" type="submit">Tampilkan laporan</button>
                    <a class="button button-secondary" href="{{ route('reports.index', ['type' => $type]) }}">Reset filter</a>
                </div>
            </form>
        </section>

        <section class="card card-pad report-results">
            <div class="section-title report-title-row">
                <div>
                    <h2>{{ $title }}</h2>
                    <p>{{ count($rows) }} baris ditemukan
                        <span class="report-period">
                            Periode
                            {{ $startDate ? \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') : 'awal' }}
                            sampai
                            {{ $endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') : 'sekarang' }}
                        </span>
                    </p>
                </div>
                <div class="report-export-actions">
                    <a class="button button-secondary" href="{{ route('reports.export', request()->query()) }}">Export Excel (CSV)</a>
                    <a class="button button-secondary" target="_blank" rel="noopener" href="{{ route('reports.print', request()->query()) }}">Pratinjau Cetak</a>
                    <a class="button" href="{{ route('reports.pdf', request()->query()) }}">Cetak / Unduh PDF</a>
                </div>
            </div>

            @if ($rows === [])
                <div class="report-empty-state">
                    <span class="report-empty-icon" aria-hidden="true">—</span>
                    <strong>Tidak ada data laporan</strong>
                    <p>Coba rentang tanggal lain atau reset filter untuk melihat data yang tersedia.</p>
                </div>
            @else
                <p class="report-scroll-hint">Geser tabel ke samping untuk melihat semua kolom.</p>
                <div class="table-wrap report-table-wrap" role="region" aria-label="Data laporan {{ $title }}" tabindex="0">
                    <table class="report-table">
                        <thead>
                            <tr>@foreach ($headers as $header)<th scope="col">{{ $header }}</th>@endforeach</tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </x-app-layout>
@endif
