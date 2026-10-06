<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pajak Pelanggan - {{ now()->format('d/m/Y') }}</title>
    @php
        $rp = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 20px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 14px; }
        .summary { display: flex; gap: 10px; margin-bottom: 14px; }
        .card { flex: 1; border: 1px solid #ccc; border-radius: 6px; padding: 8px 10px; }
        .card .label { font-size: 9px; text-transform: uppercase; color: #666; font-weight: bold; }
        .card .value { font-size: 14px; font-weight: bold; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 5px 6px; vertical-align: top; }
        th { background: #f0f0f0; text-align: left; font-size: 10px; text-transform: uppercase; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: bold; background: #f7f7f7; }
        .muted { color: #666; font-size: 10px; }
        .toolbar { margin-bottom: 12px; }
        .toolbar button { padding: 6px 14px; cursor: pointer; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            th, tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @page { size: A4 landscape; margin: 12mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <h1>Laporan Pajak Pelanggan</h1>
    <div class="meta">
        Dicetak: {{ now()->format('d/m/Y H:i') }} &middot; Jumlah pelanggan aktif: {{ $rows->count() }}
        @if($search) &middot; Filter: "{{ $search }}" @endif
    </div>

    <div class="summary">
        <div class="card"><div class="label">Total Dasar (DPP)</div><div class="value">{{ $rp($totals['base_price']) }}</div></div>
        <div class="card"><div class="label">Total PPN</div><div class="value">{{ $rp($totals['ppn']) }}</div></div>
        <div class="card"><div class="label">Total BHP</div><div class="value">{{ $rp($totals['bhp']) }}</div></div>
        <div class="card"><div class="label">Total USO</div><div class="value">{{ $rp($totals['uso']) }}</div></div>
        <div class="card"><div class="label">Grand Total</div><div class="value">{{ $rp($totals['grand_total']) }}</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th>Pelanggan</th>
                <th>Paket</th>
                <th class="num">DPP</th>
                <th class="num">PPN</th>
                <th class="num">BHP</th>
                <th class="num">USO</th>
                <th class="num">Total Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->name }}<br><span class="muted">{{ $row->customer_code }}</span></td>
                    <td>{{ $row->package_name }}</td>
                    <td class="num">{{ $rp($row->base_price) }}</td>
                    <td class="num">{{ $rp($row->ppn_amount) }}<br><span class="muted">{{ $row->ppn_percent }}%</span></td>
                    <td class="num">{{ $rp($row->bhp_amount) }}<br><span class="muted">{{ $row->bhp_percent }}%</span></td>
                    <td class="num">{{ $rp($row->uso_amount) }}<br><span class="muted">{{ $row->uso_percent }}%</span></td>
                    <td class="num"><strong>{{ $rp($row->total_price) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; padding:20px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">TOTAL</td>
                <td class="num">{{ $rp($totals['base_price']) }}</td>
                <td class="num">{{ $rp($totals['ppn']) }}</td>
                <td class="num">{{ $rp($totals['bhp']) }}</td>
                <td class="num">{{ $rp($totals['uso']) }}</td>
                <td class="num">{{ $rp($totals['grand_total']) }}</td>
            </tr>
        </tfoot>
    </table>

    <script>
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
    </script>
</body>
</html>
