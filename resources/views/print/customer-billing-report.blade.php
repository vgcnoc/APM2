<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Laporan Penagihan Pelanggan' }} - {{ $printDate }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
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
            max-width: 1220px;
            margin: 0 auto;
            padding: 22px 26px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border-radius: 10px;
            @endif
        }

        /* Toolbar No-Print */
        .toolbar {
            max-width: 1220px;
            margin: 0 auto 14px auto;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
            border: 1px solid #334155;
        }
        .toolbar-table {
            width: 100%;
            border-collapse: collapse;
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
        .btn-scope-active { background: #f59e0b; color: #0f172a; border: 1px solid #fbbf24; padding: 5px 10px; font-size: 10px; font-weight: bold; }
        .btn-close { background: #475569; color: #ffffff; }

        /* Header Kop */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #0f172a;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
            padding-bottom: 8px;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .company-sub {
            font-size: 9px;
            color: #475569;
            margin: 0;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin: 0 0 2px 0;
            text-align: right;
        }
        .report-meta {
            font-size: 8.5px;
            color: #475569;
            text-align: right;
        }

        /* Filter Info Strip */
        .filter-strip {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            margin-bottom: 10px;
        }
        .filter-strip td {
            padding: 5px 8px;
            font-size: 8.5px;
            color: #334155;
            border-right: 1px solid #e2e8f0;
        }
        .filter-strip td:last-child {
            border-right: none;
        }

        /* KPI Summary Cards Table */
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .kpi-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 6px 8px;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }
        .kpi-val {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
            font-family: Courier, monospace;
        }
        .kpi-sub {
            font-size: 8px;
            color: #475569;
            margin-top: 2px;
        }

        /* Section Title */
        .section-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 5px 9px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
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
        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }
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

        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 3px;
        }
        .badge-unpaid { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge-partial { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-promise { background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; }
        .badge-overdue { background: #ffe4e6; color: #be123c; border: 1px solid #fda4af; }
        .badge-paid { background: #dcfce7; color: #166534; border: 1px solid #86efac; }

        /* Signature Block */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            page-break-inside: avoid;
        }
        .sign-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 9px;
            padding: 4px 10px;
        }
        .sign-space {
            height: 46px;
        }
        .sign-line {
            display: inline-block;
            min-width: 165px;
            border-top: 1px solid #0f172a;
            padding-top: 3px;
            font-weight: bold;
            color: #0f172a;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    @if(!($isPdf ?? false))
        @php
            $baseQuery = request()->except(['mode', 'scope']);
            $urlPrintActive = route('customer-billing.export', array_merge($baseQuery, ['mode' => 'print', 'scope' => 'active']));
            $urlPrintCollector = route('customer-billing.export', array_merge($baseQuery, ['mode' => 'print', 'scope' => 'collector_sheet']));
            $urlPrintMyCollections = route('customer-billing.export', array_merge($baseQuery, ['mode' => 'print', 'scope' => 'my_collections']));
            $urlPrintAll = route('customer-billing.export', array_merge($baseQuery, ['mode' => 'print', 'scope' => 'all_summary']));
            $urlPdfCurrent = route('customer-billing.export', array_merge($baseQuery, ['mode' => 'pdf', 'scope' => $scope]));
        @endphp
        <div class="toolbar no-print">
            <table class="toolbar-table">
                <tr>
                    <td style="vertical-align: middle;">
                        <span style="font-size: 11px; font-weight: bold; color: #94a3b8; margin-right: 6px;">MODE CETAK:</span>
                        <a href="{{ $urlPrintActive }}" class="toolbar-btn {{ $scope === 'active' ? 'btn-scope-active' : 'btn-scope' }}">
                            1. Daftar Tagihan ({{ $activeTabLabel }})
                        </a>
                        <a href="{{ $urlPrintCollector }}" class="toolbar-btn {{ $scope === 'collector_sheet' ? 'btn-scope-active' : 'btn-scope' }}">
                            2. Lembar Kerja Kolektor Lapangan
                        </a>
                        <a href="{{ $urlPrintMyCollections }}" class="toolbar-btn {{ $scope === 'my_collections' ? 'btn-scope-active' : 'btn-scope' }}">
                            3. Rekap Setoran Saya
                        </a>
                        <a href="{{ $urlPrintAll }}" class="toolbar-btn {{ $scope === 'all_summary' ? 'btn-scope-active' : 'btn-scope' }}">
                            4. Semua Modul (Lengkap)
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
        <!-- HEADER KOP PERUSAHAAN -->
        <table class="header-table">
            <tr>
                <td style="width: 58%;">
                    <table>
                        <tr>
                            @if(!empty($logoBase64))
                                <td style="padding-right: 14px; vertical-align: middle; border-right: 2px solid #cbd5e1;">
                                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-width: 170px; max-height: 54px; width: auto; height: auto; object-fit: contain; display: block;">
                                </td>
                                <td style="width: 12px;"></td>
                            @elseif(!empty($company['logo']) && !($isPdf ?? false))
                                <td style="padding-right: 14px; vertical-align: middle; border-right: 2px solid #cbd5e1;">
                                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-width: 170px; max-height: 54px; width: auto; height: auto; object-fit: contain; display: block;" onerror="this.style.display='none'">
                                </td>
                                <td style="width: 12px;"></td>
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
                <td style="width: 42%;">
                    <div class="report-title">{{ $reportTitle }}</div>
                    <div class="report-meta">
                        Dicetak pada: <strong>{{ $printDate }} WIB</strong> | Petugas/Operator: <strong>{{ $operatorName }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- FILTER INFO STRIP -->
        <table class="filter-strip">
            <tr>
                <td><strong>Tab / Kategori:</strong> {{ $activeTabLabel }}</td>
                <td><strong>Wilayah / Area:</strong> {{ $filterAreaName }}</td>
                <td><strong>Desa / Kelurahan:</strong> {{ $filterDesaName }}</td>
                <td><strong>Rentang Tanggal:</strong> {{ $filterDateRange }}</td>
                <td><strong>Pencarian:</strong> {{ !empty($filters['search']) ? $filters['search'] : 'Semua Data' }}</td>
            </tr>
        </table>

        <!-- RINGKASAN KPI -->
        @if($isAdmin)
            <table class="kpi-table">
                <tr>
                    <td class="kpi-box" style="width: 16.66%; border-left: 3px solid #d97706;">
                        <div class="kpi-label">BELUM LUNAS</div>
                        <div class="kpi-val">Rp {{ number_format($stats['unpaid']['total'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $stats['unpaid']['count'] ?? 0 }} Tagihan</div>
                    </td>
                    <td class="kpi-box" style="width: 16.66%; border-left: 3px solid #0284c7;">
                        <div class="kpi-label">BAYAR SEBAGIAN</div>
                        <div class="kpi-val">Rp {{ number_format($stats['partial']['total'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $stats['partial']['count'] ?? 0 }} Tagihan</div>
                    </td>
                    <td class="kpi-box" style="width: 16.66%; border-left: 3px solid #7e22ce;">
                        <div class="kpi-label">JANJI BAYAR</div>
                        <div class="kpi-val">Rp {{ number_format($stats['promise']['total'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $stats['promise']['count'] ?? 0 }} Pelanggan</div>
                    </td>
                    <td class="kpi-box" style="width: 16.66%; border-left: 3px solid #e11d48;">
                        <div class="kpi-label">JATUH TEMPO</div>
                        <div class="kpi-val">Rp {{ number_format($stats['overdue']['total'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $stats['overdue']['count'] ?? 0 }} Lewat Tempo</div>
                    </td>
                    <td class="kpi-box" style="width: 16.66%; border-left: 3px solid #16a34a;">
                        <div class="kpi-label">SUDAH LUNAS</div>
                        <div class="kpi-val">Rp {{ number_format($stats['paid']['total'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $stats['paid']['count'] ?? 0 }} Lunas</div>
                    </td>
                    <td class="kpi-box" style="width: 16.70%; border-left: 3px solid #4f46e5;">
                        <div class="kpi-label">MASUK HARI INI (GLOBAL)</div>
                        <div class="kpi-val">Rp {{ number_format($stats['today']['verified_amount'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">
                            {{ $stats['today']['verified_count'] ?? 0 }} Valid
                            @if(($stats['today']['pending_amount'] ?? 0) > 0)
                                (+Rp {{ number_format($stats['today']['pending_amount'], 0, ',', '.') }} Pending)
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        @else
            <table class="kpi-table">
                <tr>
                    <td class="kpi-box" style="width: 25%; border-left: 3px solid #d97706;">
                        <div class="kpi-label">SETORAN HARI INI (TUNAI DI TANGAN)</div>
                        <div class="kpi-val">Rp {{ number_format($collectorStats['today_pending_amount'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $collectorStats['today_pending_count'] ?? 0 }} Tagihan Menunggu Setor</div>
                    </td>
                    <td class="kpi-box" style="width: 25%; border-left: 3px solid #0284c7;">
                        <div class="kpi-label">SETORAN TERVALIDASI HARI INI</div>
                        <div class="kpi-val">Rp {{ number_format($collectorStats['today_verified_amount'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $collectorStats['today_verified_count'] ?? 0 }} Setoran Lunas Kasir</div>
                    </td>
                    <td class="kpi-box" style="width: 25%; border-left: 3px solid #4f46e5;">
                        <div class="kpi-label">TERIMA SETORAN BULAN INI</div>
                        <div class="kpi-val">Rp {{ number_format($collectorStats['month_amount'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $collectorStats['month_count'] ?? 0 }} Tagihan Tertagih</div>
                    </td>
                    <td class="kpi-box" style="width: 25%; border-left: 3px solid #16a34a;">
                        <div class="kpi-label">TOTAL RIWAYAT PENAGIHAN PETUGAS</div>
                        <div class="kpi-val">Rp {{ number_format($collectorStats['all_amount'] ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-sub">{{ $collectorStats['all_count'] ?? 0 }} Transaksi Sukses</div>
                    </td>
                </tr>
            </table>
        @endif

        <!-- =========================================================================
             BAGIAN 1: LEMBAR KERJA PENAGIHAN LAPANGAN (COLLECTOR SHEET)
             ========================================================================= -->
        @if($scope === 'collector_sheet')
            <div class="section-bar">
                LEMBAR KERJA PENAGIHAN LAPANGAN (DAFTAR TAGIH KOLEKTOR) - {{ strtoupper($filterAreaName) }} {{ $filterDesaName !== 'Semua Desa / Kelurahan' ? '/ DESA ' . strtoupper($filterDesaName) : '' }} ({{ count($collectorSheetInvoices) }} PELANGGAN)
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 3%;" class="text-center">No</th>
                        <th style="width: 11%;">No. Tagihan</th>
                        <th style="width: 17%;">Nama Pelanggan & HP</th>
                        <th style="width: 24%;">Alamat Lengkap & Area / Desa</th>
                        <th style="width: 10%;">Paket</th>
                        <th style="width: 9%;" class="text-center">Jatuh Tempo / Janji</th>
                        <th style="width: 10%;" class="text-right">Sisa Tagihan (Rp)</th>
                        <th style="width: 8%;" class="text-center">Metode Bayar</th>
                        <th style="width: 8%;">Jml Diterima / Paraf</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sumSheetAmount = 0;
                        $sumSheetRemaining = 0;
                    @endphp
                    @forelse($collectorSheetInvoices as $idx => $inv)
                        @php
                            $sumSheetAmount += (float) $inv['amount'];
                            $sumSheetRemaining += (float) $inv['remaining'];
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <div class="font-mono font-bold">{{ $inv['invoice_number'] }}</div>
                                <div style="font-size: 7.5px; color: #64748b;">{{ $inv['period_label'] ?? '-' }}</div>
                                @if($inv['status'] === 'partial')
                                    <span class="badge badge-partial">CICILAN</span>
                                @elseif(!empty($inv['promise_date']))
                                    <span class="badge badge-promise">JANJI BAYAR</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-bold" style="color: #0f172a;">{{ $inv['customer_name'] }}</div>
                                <div class="font-mono" style="font-size: 8px; color: #334155;">{{ $inv['customer_code'] }} | {{ $inv['customer_phone'] }}</div>
                            </td>
                            <td>
                                <div>{{ $inv['customer_address'] }}</div>
                                <div style="font-size: 7.5px; font-weight: bold; color: #1e40af; margin-top: 1px;">
                                    Area: {{ $inv['customer_area'] }}
                                </div>
                            </td>
                            <td>{{ $inv['customer_package'] }}</td>
                            <td class="text-center">
                                <div class="font-bold">{{ $inv['due_date_formatted'] }}</div>
                                @if(!empty($inv['promise_date_formatted']))
                                    <div style="font-size: 7.5px; color: #6b21a8; font-weight: bold;">Janji: {{ $inv['promise_date_formatted'] }}</div>
                                @endif
                            </td>
                            <td class="text-right font-mono font-bold" style="color: #b91c1c; font-size: 9.5px;">
                                Rp {{ number_format($inv['remaining'], 0, ',', '.') }}
                                @if($inv['pending_amount'] > 0)
                                    <div style="font-size: 7.5px; color: #b45309;">(Pending: Rp {{ number_format($inv['pending_amount'], 0, ',', '.') }})</div>
                                @endif
                            </td>
                            <td class="text-center" style="font-size: 8px; line-height: 1.5;">
                                [ &nbsp; ] Tunai<br>
                                [ &nbsp; ] Transfer
                            </td>
                            <td style="font-size: 8px; color: #64748b;">
                                Rp .........................<br>
                                Paraf: ....................
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 18px; color: #64748b; font-style: italic;">
                                Tidak ada daftar tagihan belum lunas untuk kriteria filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($collectorSheetInvoices) > 0)
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right">TOTAL TARGET PENAGIHAN LAPANGAN ({{ count($collectorSheetInvoices) }} TAGIHAN):</td>
                            <td class="text-right font-mono" style="color: #b91c1c;">Rp {{ number_format($sumSheetRemaining, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <!-- Box Rekapitulasi Manual Serah Terima Kolektor -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #94a3b8; page-break-inside: avoid;">
                <tr>
                    <td style="background: #f1f5f9; padding: 6px 10px; font-weight: bold; font-size: 9px; border-bottom: 1px solid #94a3b8;" colspan="4">
                        REKAPITULASI HASIL PENAGIHAN LAPANGAN (DIISI SAAT SERAH TERIMA SETORAN KE KASIR / ADMIN)
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 8px 10px; border-right: 1px solid #cbd5e1; font-size: 8.5px;">
                        <strong>Total Lembar Dibawa:</strong> {{ count($collectorSheetInvoices) }} Tagihan<br>
                        <strong>Lembar Tertagih:</strong> ............ Tagihan
                    </td>
                    <td style="width: 25%; padding: 8px 10px; border-right: 1px solid #cbd5e1; font-size: 8.5px;">
                        <strong>Total Uang Tunai (Cash):</strong><br>
                        Rp ....................................................
                    </td>
                    <td style="width: 25%; padding: 8px 10px; border-right: 1px solid #cbd5e1; font-size: 8.5px;">
                        <strong>Total Transfer Bank:</strong><br>
                        Rp ....................................................
                    </td>
                    <td style="width: 25%; padding: 8px 10px; font-size: 8.5px;">
                        <strong>Total Disetorkan ke Kasir:</strong><br>
                        Rp ....................................................
                    </td>
                </tr>
            </table>
        @endif

        <!-- =========================================================================
             BAGIAN 2: DAFTAR TAGIHAN SESUAI TAB AKTIF / SEMUA MODUL
             ========================================================================= -->
        @if($scope === 'active' || $scope === 'all_summary')
            <div class="section-bar">
                DAFTAR TAGIHAN PELANGGAN - KATEGORI: {{ strtoupper($activeTabLabel) }} ({{ count($invoiceRows) }} TAGIHAN)
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 3%;" class="text-center">No</th>
                        <th style="width: 11%;">No. Tagihan</th>
                        <th style="width: 17%;">Pelanggan & ID</th>
                        <th style="width: 9%;">No. Telepon</th>
                        <th style="width: 20%;">Alamat & Wilayah</th>
                        <th style="width: 10%;">Paket</th>
                        <th style="width: 8%;" class="text-center">
                            @if($activeTab === 'paid') Tgl Lunas @elseif($activeTab === 'promise') Janji Bayar @else Jatuh Tempo @endif
                        </th>
                        <th style="width: 8%;" class="text-right">Total Tagihan</th>
                        <th style="width: 8%;" class="text-right">Sudah Bayar</th>
                        <th style="width: 8%;" class="text-right">Sisa Tagihan</th>
                        <th style="width: 6%;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sumTotal = 0;
                        $sumPaid = 0;
                        $sumRemaining = 0;
                    @endphp
                    @forelse($invoiceRows as $idx => $inv)
                        @php
                            $sumTotal += (float) $inv['amount'];
                            $sumPaid += (float) $inv['total_paid'];
                            $sumRemaining += (float) $inv['remaining'];
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <div class="font-mono font-bold">{{ $inv['invoice_number'] }}</div>
                                <div style="font-size: 7.5px; color: #64748b;">{{ $inv['period_label'] ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="font-bold">{{ $inv['customer_name'] }}</div>
                                <div class="font-mono" style="font-size: 7.5px; color: #475569;">{{ $inv['customer_code'] }}</div>
                            </td>
                            <td class="font-mono">{{ $inv['customer_phone'] }}</td>
                            <td>
                                <div>{{ $inv['customer_address'] }}</div>
                                <div style="font-size: 7.5px; color: #1e40af; font-weight: bold;">Area: {{ $inv['customer_area'] }}</div>
                            </td>
                            <td>{{ $inv['customer_package'] }}</td>
                            <td class="text-center">
                                @if($activeTab === 'paid')
                                    <span class="font-bold" style="color: #15803d;">{{ $inv['paid_date_formatted'] ?: $inv['due_date_formatted'] }}</span>
                                @elseif($activeTab === 'promise')
                                    <span class="font-bold" style="color: #6b21a8;">{{ $inv['promise_date_formatted'] ?: '-' }}</span>
                                    <div style="font-size: 7px; color: #64748b;">Tempo: {{ $inv['due_date_formatted'] }}</div>
                                @else
                                    <span class="font-bold">{{ $inv['due_date_formatted'] }}</span>
                                    @if(!empty($inv['promise_date_formatted']) && $inv['status'] !== 'paid')
                                        <div style="font-size: 7px; color: #6b21a8; font-weight: bold;">Janji: {{ $inv['promise_date_formatted'] }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-right font-mono font-bold">Rp {{ number_format($inv['amount'], 0, ',', '.') }}</td>
                            <td class="text-right font-mono" style="color: #15803d;">
                                Rp {{ number_format($inv['total_paid'], 0, ',', '.') }}
                                @if($inv['pending_amount'] > 0)
                                    <div style="font-size: 7px; color: #b45309; font-weight: bold;">+Pending Rp {{ number_format($inv['pending_amount'], 0, ',', '.') }}</div>
                                @endif
                            </td>
                            <td class="text-right font-mono font-bold" style="color: {{ $inv['remaining'] > 0 ? '#b91c1c' : '#64748b' }};">
                                Rp {{ number_format($inv['remaining'], 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($inv['status'] === 'paid')
                                    <span class="badge badge-paid">LUNAS</span>
                                @elseif($inv['status'] === 'partial')
                                    <span class="badge badge-partial">SEBAGIAN</span>
                                @else
                                    <span class="badge badge-unpaid">BELUM LUNAS</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center" style="padding: 18px; color: #64748b; font-style: italic;">
                                Tidak ada data tagihan untuk kategori dan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($invoiceRows) > 0)
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right">TOTAL REKAPITULASI ({{ count($invoiceRows) }} TAGIHAN):</td>
                            <td class="text-right font-mono">Rp {{ number_format($sumTotal, 0, ',', '.') }}</td>
                            <td class="text-right font-mono" style="color: #15803d;">Rp {{ number_format($sumPaid, 0, ',', '.') }}</td>
                            <td class="text-right font-mono" style="color: #b91c1c;">Rp {{ number_format($sumRemaining, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        @endif

        <!-- =========================================================================
             BAGIAN 3: REKAPITULASI SETORAN PETUGAS (MY COLLECTIONS)
             ========================================================================= -->
        @if($scope === 'my_collections' || $scope === 'all_summary')
            <div class="section-bar">
                REKAPITULASI PENERIMAAN & SETORAN PENAGIH ({{ strtoupper($operatorName) }}) - {{ count($myCollectionRows) }} TRANSAKSI
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;" class="text-center">No</th>
                        <th style="width: 11%;">Tgl Terima</th>
                        <th style="width: 13%;">No. Invoice</th>
                        <th style="width: 20%;">Nama Pelanggan & ID</th>
                        <th style="width: 14%;">Wilayah / Area</th>
                        <th style="width: 10%;" class="text-center">Metode Bayar</th>
                        <th style="width: 12%;">Petugas Penagih</th>
                        <th style="width: 10%;" class="text-center">Status Setoran</th>
                        <th style="width: 12%;" class="text-right">Nominal Setoran (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sumColPending = 0;
                        $sumColVerified = 0;
                    @endphp
                    @forelse($myCollectionRows as $idx => $pay)
                        @php
                            if ($pay['status'] === 'verified') {
                                $sumColVerified += (float) $pay['amount'];
                            } else {
                                $sumColPending += (float) $pay['amount'];
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>{{ $pay['payment_date_formatted'] }}</td>
                            <td class="font-mono font-bold">{{ $pay['invoice_number'] }}</td>
                            <td>
                                <div class="font-bold">{{ $pay['customer_name'] }}</div>
                                <div class="font-mono" style="font-size: 7.5px; color: #64748b;">{{ $pay['customer_code'] }} | {{ $pay['customer_phone'] }}</div>
                            </td>
                            <td>{{ $pay['customer_area'] }}</td>
                            <td class="text-center font-bold" style="text-transform: uppercase;">{{ $pay['payment_method'] }}</td>
                            <td>{{ $pay['collector_name'] }}</td>
                            <td class="text-center">
                                @if($pay['status'] === 'verified')
                                    <span class="badge badge-paid">TERVALIDASI KASIR</span>
                                @else
                                    <span class="badge badge-partial">TUNAI DI TANGAN (PENDING)</span>
                                @endif
                            </td>
                            <td class="text-right font-mono font-bold">Rp {{ number_format($pay['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 18px; color: #64748b; font-style: italic;">
                                Belum ada transaksi penerimaan setoran pada kriteria ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($myCollectionRows) > 0)
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right">
                                Setoran Menunggu Validasi (Di Tangan): <span class="font-mono" style="color: #b45309;">Rp {{ number_format($sumColPending, 0, ',', '.') }}</span>
                                &nbsp;|&nbsp;
                                Setoran Tervalidasi Kasir: <span class="font-mono" style="color: #15803d;">Rp {{ number_format($sumColVerified, 0, ',', '.') }}</span>
                            </td>
                            <td colspan="2" class="text-right">TOTAL DITERIMA:</td>
                            <td class="text-right font-mono" style="color: #1e3a8a;">Rp {{ number_format($sumColPending + $sumColVerified, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        @endif

        <!-- TANDA TANGAN PENGESAHAN & SERAH TERIMA -->
        <table class="sign-table">
            <tr>
                <td>
                    <div>Petugas Penagih / Kolektor,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">{{ $operatorName }}</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Penagih Lapangan</div>
                </td>
                <td>
                    <div>Penerima Setoran / Kasir Finance,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( ............................................ )</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Admin Kasir / Finance</div>
                </td>
                <td>
                    <div>Mengetahui & Menyetujui,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( ............................................ )</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Koordinator / Manager Operasional</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

