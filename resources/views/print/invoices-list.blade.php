<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tagihan Invoice - {{ $printDate }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 8mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 8.5px;
            line-height: 1.3;
            margin: 0;
            padding: {{ ($isPdf ?? false) ? '0' : '14px 18px 36px' }};
            background: {{ ($isPdf ?? false) ? '#ffffff' : '#f1f5f9' }};
        }
        .sheet {
            background: #ffffff;
            width: 100%;
            @if(!($isPdf ?? false))
            max-width: 1250px;
            margin: 0 auto;
            padding: 20px 24px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border-radius: 10px;
            @endif
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #2563eb;
            margin-bottom: 10px;
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
            font-size: 8.5px;
            color: #475569;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .card {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            background: #f8fafc;
            vertical-align: top;
        }
        .card .label {
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .card .value {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
            font-family: Courier, monospace;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table tr.row-promise td {
            background-color: #fefce8 !important; /* Soft Amber / Yellow for Janji Bayar */
        }
        .data-table tr.row-promise td:first-child {
            border-left: 3.5px solid #f59e0b !important;
        }
        .data-table tr.row-partial td {
            background-color: #eff6ff !important; /* Soft Sky Blue for Bayar Sebagian (Cicil) */
        }
        .data-table tr.row-partial td:first-child {
            border-left: 3.5px solid #3b82f6 !important;
        }
        .data-table tr.row-promise-partial td {
            background-color: #faf5ff !important; /* Soft Purple for Bayar Sebagian & Janji Bayar */
        }
        .data-table tr.row-promise-partial td:first-child {
            border-left: 3.5px solid #a855f7 !important;
        }
        .data-table tfoot td {
            background-color: #f1f5f9;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            font-size: 8px;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .badge {
            display: inline-block;
            padding: 1.5px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-unpaid { background: #fee2e2; color: #b91c1c; }
        .badge-partial { background: #fef3c7; color: #b45309; }
        .badge-promise { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .sheet { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
        <div class="no-print" style="max-width: 1250px; margin: 0 auto 12px auto; text-align: right;">
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
                        @if(!empty($filters['tab']) && $filters['tab'] !== 'semua')
                            | Kategori: <b style="text-transform: capitalize; color: #2563eb;">{{ str_replace('_', ' ', $filters['tab']) }}</b>
                        @endif
                        @if(!empty($filters['area_id']))
                            @php
                                $filterArea = \App\Models\Area::find($filters['area_id']);
                            @endphp
                            @if($filterArea)
                                | Area: <b style="color: #2563eb;">{{ $filterArea->name }}</b>
                            @endif
                        @endif
                        @if(!empty($filters['status']))
                            | Status: <b style="text-transform: uppercase; color: #2563eb;">{{ $filters['status'] }}</b>
                        @endif
                        @if(!empty($filters['bank']))
                            @php
                                $bankLabels = [
                                    'cash' => 'Tunai / Cash',
                                    'reseller_balance' => 'Loket Reseller',
                                    'payment_gateway' => 'Payment Gateway',
                                    'transfer_unspecified' => 'Transfer Lainnya'
                                ];
                                $bankDisplay = $bankLabels[$filters['bank']] ?? ('Bank ' . $filters['bank']);
                            @endphp
                            | Bank/Metode: <b style="color: #2563eb;">{{ $bankDisplay }}</b>
                        @endif
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
            $invoicesWithPromise = $invoices->filter(fn($i) => !empty($i->promise_date));
            $totalJanjiBayar = $invoicesWithPromise->count();
            $nilaiJanjiBayar = $invoicesWithPromise->sum('remaining');
            $cardWidth = ($totalJanjiBayar > 0) ? '20%' : '25%';
        @endphp

        <table class="summary-table">
            <tr>
                <td class="card" style="width: {{ $cardWidth }};">
                    <div class="label">Total Invoice</div>
                    <div class="value">{{ number_format(count($invoices), 0, ',', '.') }} Lembar</div>
                </td>
                <td class="card" style="width: {{ $cardWidth }};">
                    <div class="label">Total Nilai Tagihan</div>
                    <div class="value" style="color: #0284c7;">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                </td>
                <td class="card" style="width: {{ $cardWidth }};">
                    <div class="label">Total Sudah Dibayar</div>
                    <div class="value" style="color: #16a34a;">Rp {{ number_format($totalTerbayar, 0, ',', '.') }}</div>
                </td>
                <td class="card" style="width: {{ $cardWidth }};">
                    <div class="label">Total Sisa Piutang</div>
                    <div class="value" style="color: #dc2626;">Rp {{ number_format($totalSisa, 0, ',', '.') }}</div>
                </td>
                @if($totalJanjiBayar > 0)
                <td class="card" style="width: {{ $cardWidth }}; border-left: 2px solid #f59e0b; background: #fffdf5;">
                    <div class="label" style="color: #b45309;">Janji Bayar (Komitmen)</div>
                    <div class="value" style="color: #b45309;">
                        {{ $totalJanjiBayar }} Lembar
                        <span style="font-size: 8.5px; font-weight: normal; color: #78350f;">(Rp {{ number_format($nilaiJanjiBayar, 0, ',', '.') }})</span>
                    </div>
                </td>
                @endif
            </tr>
        </table>

        <!-- Petunjuk Legenda Blok Warna -->
        <div style="margin: 8px 0 6px 0; display: flex; align-items: center; justify-content: space-between; font-size: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: bold; color: #475569;">Petunjuk Blok Warna:</span>
                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 1.5px 6px; background: #fefce8; border: 1px solid #fde047; border-left: 3.5px solid #f59e0b; border-radius: 3px;">
                    <span style="color: #854d0e; font-weight: bold;">Kuning: Janji Bayar</span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 1.5px 6px; background: #eff6ff; border: 1px solid #bfdbfe; border-left: 3.5px solid #3b82f6; border-radius: 3px;">
                    <span style="color: #1e40af; font-weight: bold;">Biru: Bayar Sebagian (Cicil)</span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 1.5px 6px; background: #faf5ff; border: 1px solid #e9d5ff; border-left: 3.5px solid #a855f7; border-radius: 3px;">
                    <span style="color: #6b21a8; font-weight: bold;">Ungu: Bayar Sebagian & Janji Bayar</span>
                </span>
            </div>
            <div style="color: #64748b; font-size: 7.5px; font-style: italic;">
                * Baris di-blok warna otomatis agar penagihan lapangan mudah dimonitor
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 22px;">No</th>
                    <th style="width: 82px;">No. Invoice</th>
                    <th style="min-width: 105px;">Nama Pelanggan</th>
                    <th style="min-width: 120px; max-width: 180px;">Alamat</th>
                    <th style="width: 48px;">Area</th>
                    <th style="width: 75px;">Paket Internet</th>
                    <th style="width: 52px;">Tgl Terbit</th>
                    <th style="width: 52px;">Jatuh Tempo</th>
                    <th class="text-center" style="width: 65px;">Janji Bayar</th>
                    <th class="text-right" style="width: 65px;">Tagihan</th>
                    <th class="text-right" style="width: 60px;">Terbayar</th>
                    <th class="text-right" style="width: 65px;">Sisa</th>
                    <th class="text-center" style="width: 55px;">Status</th>
                    <th style="width: 75px;">Pembayaran Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $idx => $inv)
                    @php
                        $lastPayment = $inv->payments->first();
                        $hasPromise = !empty($inv->promise_date);
                        $isPartial = ($inv->status === 'partial');
                        $rowClass = '';
                        if ($hasPromise && $isPartial) {
                            $rowClass = 'row-promise-partial';
                        } elseif ($hasPromise) {
                            $rowClass = 'row-promise';
                        } elseif ($isPartial) {
                            $rowClass = 'row-partial';
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td style="font-family: Courier, monospace; font-weight: bold; font-size: 8px;">{{ $inv->invoice_number }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ $inv->customer?->name ?? 'Pelanggan Dihapus' }}</div>
                            <div style="font-size: 7.5px; color: #64748b;">{{ $inv->customer?->customer_code }} | {{ $inv->customer?->phone }}</div>
                        </td>
                        <td style="font-size: 7.5px; color: #334155; line-height: 1.25; word-break: break-word;">
                            {{ $inv->customer?->address ?? '-' }}
                        </td>
                        <td style="font-size: 8px;">{{ $inv->customer?->areaModel?->name ?? ($inv->customer?->area ?? '-') }}</td>
                        <td style="font-size: 8px;">
                            {{ $inv->customer?->package?->name ?? '-' }}
                            @if($inv->is_prorata || $inv->billing_type === 'prorata')
                                <span style="font-size: 7px; color: #2563eb; font-weight: bold;">(PRORATA)</span>
                            @endif
                        </td>
                        <td style="font-size: 8px;">{{ $inv->issued_date ? $inv->issued_date->format('d/m/Y') : '-' }}</td>
                        <td style="font-size: 8px;">{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}</td>
                        <td class="text-center">
                            @if($inv->promise_date)
                                <span class="badge badge-promise">
                                    {{ \Carbon\Carbon::parse($inv->promise_date)->format('d/m/Y') }}
                                </span>
                            @else
                                <span style="color: #cbd5e1;">-</span>
                            @endif
                        </td>
                        <td class="text-right" style="font-weight: bold; font-family: Courier, monospace; font-size: 8px;">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #16a34a; font-family: Courier, monospace; font-size: 8px;">Rp {{ number_format($inv->total_paid, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: bold; font-family: Courier, monospace; font-size: 8px; color: {{ $inv->remaining > 0 ? '#dc2626' : '#64748b' }};">
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
                        <td style="font-size: 8px;">
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
                                    $pMethod = strtoupper($lastPayment->payment_method);
                                    if ($lastPayment->payment_method === 'transfer' && preg_match('/\[Transfer ke:\s*(.*?)\s*-/i', $lastPayment->notes ?? '', $bm)) {
                                        $pMethod = 'TRANSFER - ' . trim($bm[1]);
                                    }
                                @endphp
                                <div>{{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d/m/Y') }}</div>
                                <div style="font-size: 7.5px; color: #64748b;">{{ $pMethod }} | {{ $cName ?? '-' }}</div>
                            @else
                                <span style="color: #94a3b8;">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center" style="padding: 24px; color: #94a3b8; font-style: italic;">
                            Tidak ada data tagihan yang sesuai dengan filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($invoices) > 0)
                <tfoot>
                    <tr>
                        <td colspan="9" class="text-right">TOTAL KESELURUHAN ({{ count($invoices) }} TAGIHAN):</td>
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
