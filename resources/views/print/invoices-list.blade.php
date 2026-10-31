<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tagihan Invoice - {{ $printDate }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0 0 4px 0;
            color: #0f172a;
            font-weight: bold;
        }
        .meta {
            font-size: 9px;
            color: #475569;
        }
        .summary-box {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 12px;
            background: #f8fafc;
            min-width: 140px;
        }
        .card .label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .card .value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-unpaid { background: #fee2e2; color: #b91c1c; }
        .badge-partial { background: #fef3c7; color: #b45309; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 12px; display: flex; justify-content: flex-end; gap: 8px;">
        <button onclick="window.print()" style="padding: 6px 16px; background: #2563eb; color: #fff; font-weight: bold; border: none; border-radius: 6px; cursor: pointer;">
            🖨️ Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" style="padding: 6px 12px; background: #64748b; color: #fff; font-weight: bold; border: none; border-radius: 6px; cursor: pointer;">
            Tutup
        </button>
    </div>

    <div class="header">
        <div>
            <h1>LAPORAN DAFTAR TAGIHAN INVOICE</h1>
            <div class="meta">
                Dicetak pada: <b>{{ $printDate }}</b> | Operator: <b>{{ auth()->user()->name ?? 'Admin' }}</b>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 13px; font-weight: bold; color: #2563eb;">VIZBILL ISP BILLING SYSTEM</div>
            <div class="meta">Total Data: <b>{{ count($invoices) }} Tagihan</b></div>
        </div>
    </div>

    @php
        $totalTagihan = $invoices->sum('amount');
        $totalTerbayar = $invoices->sum('total_paid');
        $totalSisa = $invoices->sum('remaining');
    @endphp

    <div class="summary-box">
        <div class="card">
            <div class="label">Total Invoice</div>
            <div class="value">{{ number_format(count($invoices), 0, ',', '.') }} Lembar</div>
        </div>
        <div class="card">
            <div class="label">Total Nilai Tagihan</div>
            <div class="value" style="color: #0284c7;">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Total Sudah Dibayar</div>
            <div class="value" style="color: #16a34a;">Rp {{ number_format($totalTerbayar, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Total Sisa Piutang</div>
            <div class="value" style="color: #dc2626;">Rp {{ number_format($totalSisa, 0, ',', '.') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th>No. Invoice</th>
                <th>Nama Pelanggan</th>
                <th>Area</th>
                <th>Paket Internet</th>
                <th>Tgl Terbit</th>
                <th>Jatuh Tempo</th>
                <th class="text-right">Tagihan</th>
                <th class="text-right">Terbayar</th>
                <th class="text-right">Sisa</th>
                <th class="text-center">Status</th>
                <th>Pembayaran Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $idx => $inv)
                @php
                    $lastPayment = $inv->payments->first();
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $inv->invoice_number }}</td>
                    <td>
                        <div style="font-weight: bold;">{{ $inv->customer?->name ?? 'Pelanggan Dihapus' }}</div>
                        <div style="font-size: 8px; color: #64748b;">{{ $inv->customer?->customer_code }} • {{ $inv->customer?->phone }}</div>
                    </td>
                    <td>{{ $inv->customer?->areaModel?->name ?? '-' }}</td>
                    <td>
                        {{ $inv->customer?->package?->name ?? '-' }}
                        @if($inv->is_prorata || $inv->billing_type === 'prorata')
                            <span style="font-size: 7px; color: #2563eb; font-weight: bold;">(PRORATA)</span>
                        @endif
                    </td>
                    <td>{{ $inv->issued_date ? $inv->issued_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                    <td class="text-right" style="color: #16a34a;">Rp {{ number_format($inv->total_paid, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold; color: {{ $inv->remaining > 0 ? '#dc2626' : '#64748b' }};">
                        Rp {{ number_format($inv->remaining, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @if($inv->status === 'paid')
                            <span class="badge badge-paid">LUNAS</span>
                        @elseif($inv->status === 'partial')
                            <span class="badge badge-partial">SEBAGIAN</span>
                        @else
                            <span class="badge badge-unpaid">BELUM LUNAS</span>
                        @endif
                    </td>
                    <td>
                        @if($lastPayment)
                            <div>{{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d/m/Y') }}</div>
                            <div style="font-size: 8px; color: #64748b;">{{ strtoupper($lastPayment->payment_method) }} • {{ $lastPayment->collector?->name ?? '-' }}</div>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 24px; color: #94a3b8; font-style: italic;">
                        Tidak ada data tagihan yang sesuai dengan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Auto open print dialog if desired
        });
    </script>
</body>
</html>
