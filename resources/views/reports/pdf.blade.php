<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Perpus Aman</title>
    <style>
        @page { margin: 24px 28px; }
        body { color: #202a26; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0 0 5px; color: #163a33; font-size: 18px; }
        p { margin: 3px 0; }
        .report-heading { margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #176e57; }
        .muted { color: #5e6d65; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #cbd5cf; padding: 5px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #eaf1ed; color: #244b3e; font-size: 8px; }
        td { font-size: 7px; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <header class="report-heading">
        <h1>{{ $title }}</h1>
        <p><strong>Perpus Aman</strong></p>
        <p class="muted">
            Periode:
            {{ $startDate ? \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') : 'Awal' }}
            s.d.
            {{ $endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') : 'Sekarang' }}
            · {{ count($rows) }} data
            · Dicetak pada {{ now()->format('d/m/Y H:i') }}
        </p>
    </header>

    <table>
        <thead>
            <tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">Tidak ada data pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
