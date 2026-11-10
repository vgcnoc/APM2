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
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9.5px;
            line-height: 1.35;
            margin: 0;
            padding: {{ ($isPdf ?? false) ? '0' : '16px 20px 40px' }};
            background: {{ ($isPdf ?? false) ? '#ffffff' : '#f1f5f9' }};
        }
        .sheet {
            background: #ffffff;
            width: 100%;
            @if(!($isPdf ?? false))
            max-width: 1200px;
            margin: 0 auto;
            padding: 22px 26px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border-radius: 10px;
            @endif
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #2563eb;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
            padding-bottom: 8px;
        }
        .header-table h1 {
            font-size: 15px;
            margin: 0 0 3px 0;
            color: #0f172a;
            font-weight: bold;
        }
        .meta {
            font-size: 9px;
            color: #475569;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .card {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            background: #f8fafc;
            width: 25%;
            vertical-align: top;
        }
        .card .label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .card .value {
            font-size: 12.5px;
            font-weight: bold;
            color: #0f172a;
            font-family: Courier, monospace;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table tfoot td {
            background-color: #f1f5f9;
            font-weight: bold;
            border-top: 2px solid #0f172a;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-unpaid { background: #fee2e2; color: #b91c1c; }
        .badge-partial { background: #fef3c7; color: #b45309; }
        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .sheet { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
        <div class="no-print" style="max-width: 1200px; margin: 0 auto 12px auto; text-align: right;">
            <button type="button" onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: #fff; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">
                Cetak Dokumen (Print)
            </button>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'pdf']) }}" style="display: inline-block; padding: 8px 16px; background: #dc2626; color: #fff; font-weight: bold; text-decoration: none; border-radius: 6px; font-size: 11px; margin-left: 6px;">
                Download PDF
            </a>
            <button type="button" onclick="window.close()" style="padding: 8px 14px; background: #64748b; color: #fff; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; margin-left: 6px;">
                Tutup
            </button>
        </div>
    @endif

    <div class="sheet">
        <table class="header-table">
            <tr>
                <td>
                    <h1>LAPORAN DAFTAR TAGIHAN INVOICE</h1>
                    <div class="meta">
                        Dicetak pada: <b>{{ $printDate }}</b> | Operator: <b>{{ auth()->user()->name ?? 'Admin' }}</b>
                    </div>
                </td>
                <td style="text-align: right;">
                    <div style="font-size: 13px; font-weight: bold; color: #2563eb;">{{ $companyName ?? 'VIZBILL ISP BILLING SYSTEM' }}</div>
                    <div class="meta">Total Data: <b>{{ count($invoices) }} Tagihan</b></div>
                </td>
            </tr>
        </table>

        @php
            $totalTagihan = $invoices->sum('amount');
            $totalTerbayar = $invoices->sum('total_paid');
            $totalSisa = $invoices->sum('remaining');
        @endphp

        <table class="summary-table">
            <tr>
                <td class="card">
                    <div class="label">Total Invoice</div>
                    <div class="value">{{ number_format(count($invoices), 0, ',', '.') }} Lembar</div>
                </td>
                <td class="card">
                    <div class="label">Total Nilai Tagihan</div>
                    <div class="value" style="color: #0284c7;">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                </td>
                <td class="card">
                    <div class="label">Total Sudah Dibayar</div>
                    <div class="value" style="color: #16a34a;">Rp {{ number_format($totalTerbayar, 0, ',', '.') }}</div>
                </td>
                <td class="card">
                    <div class="label">Total Sisa Piutang</div>
                    <div class="value" style="color: #dc2626;">Rp {{ number_format($totalSisa, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        <table class="data-table">
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
                        <td style="font-family: Courier, monospace; font-weight: bold;">{{ $inv->invoice_number }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ $inv->customer?->name ?? 'Pelanggan Dihapus' }}</div>
                            <div style="font-size: 8px; color: #64748b;">{{ $inv->customer?->customer_code }} | {{ $inv->customer?->phone }}</div>
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
                        <td class="text-right" style="font-weight: bold; font-family: Courier, monospace;">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #16a34a; font-family: Courier, monospace;">Rp {{ number_format($inv->total_paid, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: bold; font-family: Courier, monospace; color: {{ $inv->remaining > 0 ? '#dc2626' : '#64748b' }};">
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
                                @php
                                    $cName = $lastPayment->collector?->name;
                                    if (!$cName) {
                                        if ($lastPayment->payment_method === 'payment_gateway') {
                                            $cName = 'Sistem Otomatis';
                                        } elseif ($lastPayment->payment_method === 'reseller_balance' && preg_match('/(?:Reseller|Loket|Agen)\s*:\s*([^|,\n]+)/i', $lastPayment->notes ?? '', $m)) {
                                            $cName = 'Loket ' . trim($m[1]);
                                        }
                                    }
                                @endphp
                                <div>{{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d/m/Y') }}</div>
                                <div style="font-size: 8px; color: #64748b;">{{ strtoupper($lastPayment->payment_method) }} | {{ $cName ?? '-' }}</div>
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
            @if(count($invoices) > 0)
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-right">TOTAL KESELURUHAN ({{ count($invoices) }} TAGIHAN):</td>
                        <td class="text-right" style="font-family: Courier, monospace;">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: Courier, monospace; color: #16a34a;">Rp {{ number_format($totalTerbayar, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: Courier, monospace; color: #dc2626;">Rp {{ number_format($totalSisa, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</body>
</html>
