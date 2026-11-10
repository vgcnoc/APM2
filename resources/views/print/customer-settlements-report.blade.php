<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Laporan Setoran & Pelunasan Pelanggan' }} - {{ $printDate }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.35;
            color: #1e293b;
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
        .toolbar {
            max-width: 1200px;
            margin: 0 auto 14px auto;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
        }
        .toolbar-btn {
            display: inline-block;
            padding: 7px 14px;
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            margin-left: 4px;
        }
        .btn-print { background: #4f46e5; color: #ffffff; }
        .btn-pdf { background: #dc2626; color: #ffffff; }
        .btn-scope { background: #1e293b; color: #cbd5e1; border: 1px solid #475569; padding: 5px 10px; font-size: 10px; }
        .btn-scope-active { background: #10b981; color: #0f172a; border: 1px solid #34d399; padding: 5px 10px; font-size: 10px; font-weight: bold; }
        .btn-close { background: #475569; color: #ffffff; }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #0f172a;
            margin-bottom: 10px;
        }
        .header-table td { vertical-align: middle; padding-bottom: 8px; }
        .company-name { font-size: 15px; font-weight: bold; color: #0f172a; text-transform: uppercase; margin: 0 0 2px 0; }
        .company-sub { font-size: 9px; color: #475569; margin: 0; }
        .report-title { font-size: 13px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; margin: 0 0 2px 0; text-align: right; }
        .report-meta { font-size: 8.5px; color: #475569; text-align: right; }

        .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .kpi-box { border: 1px solid #cbd5e1; background: #f8fafc; padding: 6px 8px; vertical-align: top; width: 25%; }
        .kpi-label { font-size: 7.5px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .kpi-val { font-size: 12px; font-weight: bold; color: #0f172a; font-family: Courier, monospace; }
        .kpi-sub { font-size: 8px; color: #475569; margin-top: 2px; }

        .section-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 5px 9px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
            margin-bottom: 4px;
        }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .data-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #94a3b8;
            text-align: left;
        }
        .data-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td { background: #f8fafc; }
        .data-table tfoot td {
            background: #f1f5f9;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            font-size: 9px;
            padding: 6px;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-mono { font-family: Courier, monospace; }
        .font-bold { font-weight: bold; }

        .badge { display: inline-block; padding: 1.5px 5px; font-size: 7.5px; font-weight: bold; text-transform: uppercase; border-radius: 3px; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-verified { background: #dcfce7; color: #166534; border: 1px solid #86efac; }

        .sign-table { width: 100%; border-collapse: collapse; margin-top: 16px; page-break-inside: avoid; }
        .sign-table td { width: 33.33%; text-align: center; vertical-align: top; font-size: 9px; padding: 4px 10px; }
        .sign-space { height: 46px; }
        .sign-line { display: inline-block; min-width: 165px; border-top: 1px solid #0f172a; padding-top: 3px; font-weight: bold; color: #0f172a; }

        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .sheet { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
        @php
            $baseQuery = request()->except(['mode', 'scope']);
            $urlPrintPending = route('customer-settlements.export', array_merge($baseQuery, ['mode' => 'print', 'scope' => 'pending']));
            $urlPrintVerified = route('customer-settlements.export', array_merge($baseQuery, ['mode' => 'print', 'scope' => 'verified']));
            $urlPrintAll = route('customer-settlements.export', array_merge($baseQuery, ['mode' => 'print', 'scope' => 'all']));
            $urlPdfCurrent = route('customer-settlements.export', array_merge($baseQuery, ['mode' => 'pdf', 'scope' => $scope]));
        @endphp
        <div class="toolbar no-print">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <span style="font-size: 11px; font-weight: bold; color: #94a3b8; margin-right: 6px;">CAKUPAN SETORAN:</span>
                        <a href="{{ $urlPrintPending }}" class="toolbar-btn {{ $scope === 'pending' ? 'btn-scope-active' : 'btn-scope' }}">
                            1. Menunggu Validasi (Pending)
                        </a>
                        <a href="{{ $urlPrintVerified }}" class="toolbar-btn {{ $scope === 'verified' ? 'btn-scope-active' : 'btn-scope' }}">
                            2. Sudah Tervalidasi (Lunas Kas)
                        </a>
                        <a href="{{ $urlPrintAll }}" class="toolbar-btn {{ $scope === 'all' ? 'btn-scope-active' : 'btn-scope' }}">
                            3. Semua Setoran (Lengkap)
                        </a>
                    </td>
                    <td style="text-align: right; vertical-align: middle; white-space: nowrap;">
                        <button type="button" onclick="window.print()" class="toolbar-btn btn-print">
                            Cetak Dokumen (Print)
                        </button>
                        <a href="{{ $urlPdfCurrent }}" class="toolbar-btn btn-pdf">
                            Download PDF
                        </a>
                        <button type="button" onclick="window.close()" class="toolbar-btn btn-close">
                            Tutup
                        </button>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="sheet">
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <table>
                        <tr>
                            @if(!empty($logoBase64))
                                <td style="width: 55px; padding-right: 10px; vertical-align: middle;">
                                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-width: 50px; max-height: 46px;">
                                </td>
                            @endif
                            <td style="vertical-align: middle;">
                                <div class="company-name">{{ $company['name'] ?? 'VIZBILL ISP NETWORK' }}</div>
                                <div class="company-sub">
                                    {{ $company['address'] ?? 'Layanan Internet Cepat & Stabil' }}
                                    @if(!empty($company['phone'])) | Telp/WA: {{ $company['phone'] }} @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 45%;">
                    <div class="report-title">{{ $reportTitle }}</div>
                    <div class="report-meta">
                        Dicetak pada: <strong>{{ $printDate }} WIB</strong> | Operator: <strong>{{ $operatorName }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        <table class="kpi-table">
            <tr>
                <td class="kpi-box" style="border-left: 3px solid #d97706;">
                    <div class="kpi-label">MENUNGGU VALIDASI (DI TANGAN)</div>
                    <div class="kpi-val">Rp {{ number_format($stats['pending_amount'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">{{ $stats['pending_count'] ?? 0 }} Setoran Menunggu</div>
                </td>
                <td class="kpi-box" style="border-left: 3px solid #0284c7;">
                    <div class="kpi-label">TERVALIDASI HARI INI</div>
                    <div class="kpi-val">Rp {{ number_format($stats['today_verified_amount'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">{{ $stats['today_verified_count'] ?? 0 }} Setoran Masuk</div>
                </td>
                <td class="kpi-box" style="border-left: 3px solid #4f46e5;">
                    <div class="kpi-label">TERVALIDASI BULAN INI</div>
                    <div class="kpi-val">Rp {{ number_format($stats['month_verified_amount'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">{{ $stats['month_verified_count'] ?? 0 }} Setoran Masuk</div>
                </td>
                <td class="kpi-box" style="border-left: 3px solid #16a34a;">
                    <div class="kpi-label">TOTAL TERVALIDASI</div>
                    <div class="kpi-val">Rp {{ number_format($stats['total_verified_amount'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">{{ $stats['total_verified_count'] ?? 0 }} Masuk Kas</div>
                </td>
            </tr>
        </table>

        <div class="section-bar">
            DAFTAR TRANSAKSI SETORAN PENAGIH ({{ count($paymentRows) }} TRANSAKSI)
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;" class="text-center">No</th>
                    <th style="width: 10%;">Tgl Terima</th>
                    <th style="width: 12%;">No. Invoice</th>
                    <th style="width: 18%;">Pelanggan & ID</th>
                    <th style="width: 14%;">Petugas Penagih</th>
                    <th style="width: 9%;" class="text-center">Metode</th>
                    <th style="width: 11%;" class="text-right">Nominal Setoran</th>
                    <th style="width: 11%;" class="text-right">Sisa Invoice</th>
                    <th style="width: 11%;" class="text-center">Status Validasi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumSetoran = 0;
                @endphp
                @forelse($paymentRows as $idx => $row)
                    @php
                        $sumSetoran += (float) $row['amount'];
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $row['payment_date_formatted'] }}</td>
                        <td class="font-mono font-bold">{{ $row['invoice_number'] }}</td>
                        <td>
                            <div class="font-bold">{{ $row['customer_name'] }}</div>
                            <div class="font-mono" style="font-size: 7.5px; color: #64748b;">{{ $row['customer_code'] }} | {{ $row['customer_phone'] }}</div>
                        </td>
                        <td><strong>{{ $row['collector_name'] }}</strong></td>
                        <td class="text-center font-bold" style="text-transform: uppercase;">{{ $row['payment_method'] }}</td>
                        <td class="text-right font-mono font-bold" style="color: #0f172a;">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                        <td class="text-right font-mono" style="color: {{ $row['invoice_remaining'] > 0 ? '#b91c1c' : '#15803d' }};">
                            Rp {{ number_format($row['invoice_remaining'], 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if($row['status'] === 'verified')
                                <span class="badge badge-verified">TERVALIDASI</span>
                            @else
                                <span class="badge badge-pending">MENUNGGU VALIDASI</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 18px; color: #64748b; font-style: italic;">
                            Tidak ada data setoran yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($paymentRows) > 0)
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right">TOTAL NOMINAL SETORAN ({{ count($paymentRows) }} TRANSAKSI):</td>
                        <td class="text-right font-mono" style="color: #1e3a8a;">Rp {{ number_format($sumSetoran, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <table class="sign-table">
            <tr>
                <td>
                    <div>Diserahkan Oleh (Petugas Penagih),</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">{{ $collectorFilterName ?: $operatorName }}</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Kolektor / Penagih Lapangan</div>
                </td>
                <td>
                    <div>Diterima & Divalidasi Oleh (Kasir),</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">{{ $isAdmin ? $operatorName : '( ............................................ )' }}</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Admin Kasir / Finance</div>
                </td>
                <td>
                    <div>Mengetahui,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( ............................................ )</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Manager Operasional / Keuangan</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

