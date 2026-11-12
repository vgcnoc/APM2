<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $scopeTitle }} - {{ $periodLabel }}</title>
    @php
        $companyName = \App\Models\Setting::get('company_name', config('app.name', 'ISP Provider'));
        $companyAddress = \App\Models\Setting::get('company_address', '');
        $companyPhone = \App\Models\Setting::get('company_phone', '');
        $companyLogo = \App\Models\Setting::get('company_logo', '');
        $logoBase64 = null;
        if ($companyLogo && !str_starts_with($companyLogo, 'http')) {
            $logoDisk = storage_path('app/public/' . ltrim($companyLogo, '/'));
            if (file_exists($logoDisk)) {
                $mime = mime_content_type($logoDisk) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoDisk));
            }
        }
        $queryParams = request()->except(['mode', 'scope']);
    @endphp
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9.5px;
            line-height: 1.35;
            margin: 0;
            padding: {{ $mode === 'print' ? '16px' : '0' }};
            background: #ffffff;
        }
        .no-print-toolbar {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .no-print-toolbar table {
            width: 100%;
            border-collapse: collapse;
        }
        .no-print-toolbar td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .btn-bar {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            border: none;
            margin-right: 6px;
        }
        .btn-print { background: #e11d48; color: #ffffff; }
        .btn-pdf { background: #2563eb; color: #ffffff; }
        .btn-scope { background: #334155; color: #e2e8f0; }
        .btn-scope.active { background: #10b981; color: #ffffff; }
        .btn-close { background: #475569; color: #ffffff; }

        /* Document Header */
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #be123c;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        table.header-table td {
            border: none;
            padding: 0 0 8px 0;
            vertical-align: top;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #881337;
            text-transform: uppercase;
            margin: 0;
        }
        .company-sub {
            font-size: 9.5px;
            color: #475569;
            margin: 2px 0 0 0;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #be123c;
            text-transform: uppercase;
            margin: 0;
            text-align: right;
        }
        .report-meta {
            font-size: 9px;
            color: #334155;
            text-align: right;
            margin-top: 3px;
        }

        /* KPI Summary Cards Table */
        table.kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin: 0 -6px 12px -6px;
        }
        table.kpi-table td.kpi-card {
            width: 25%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 7px 10px;
            background: #f8fafc;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-sub {
            font-size: 8.5px;
            color: #475569;
            margin-top: 2px;
        }

        /* Section Title */
        .section-header {
            background: #fef2f2;
            border-left: 4px solid #e11d48;
            padding: 5px 10px;
            font-size: 10.5px;
            font-weight: bold;
            color: #881337;
            text-transform: uppercase;
            margin: 12px 0 6px 0;
        }
        .section-header.purple {
            background: #f5f3ff;
            border-left-color: #7c3aed;
            color: #4c1d95;
        }
        .section-header.emerald {
            background: #ecfdf5;
            border-left-color: #059669;
            color: #065f46;
        }

        /* Data Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
            font-size: 9px;
        }
        table.data-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            text-align: left;
        }
        table.data-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .center { text-align: center; }
        .right { text-align: right; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-critical { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge-warning { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-normal { background: #e0f2fe; color: #075985; border: 1px solid #7dd3fc; }
        .badge-done { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-scheduled { background: #e0e7ff; color: #3730a3; border: 1px solid #a5b4fc; }
        .badge-unvisited { background: #ffe4e6; color: #9f1239; border: 1px solid #fda4af; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; }

        .text-muted { color: #64748b; font-size: 8.5px; }
        .font-mono { font-family: Courier, monospace; font-weight: bold; }

        /* Signature Block */
        table.sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            page-break-inside: avoid;
        }
        table.sign-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            border: none;
            padding: 4px 10px;
            font-size: 9.5px;
        }
        .sign-space {
            height: 48px;
        }
        .sign-line {
            border-top: 1px solid #334155;
            display: inline-block;
            min-width: 170px;
            padding-top: 3px;
            font-weight: bold;
        }
        .page-break {
            page-break-before: always;
        }

        @media print {
            body { padding: 0; }
            .no-print-toolbar { display: none !important; }
        }
    </style>
</head>
<body>
    @if($mode === 'print')
    <div class="no-print-toolbar">
        <table>
            <tr>
                <td>
                    <button type="button" class="btn-bar btn-print" onclick="window.print()">Cetak Dokumen (Print)</button>
                    <a href="{{ route('operational.chronic-monitoring.export', array_merge($queryParams, ['mode' => 'pdf', 'scope' => $scope])) }}" class="btn-bar btn-pdf">
                        Download PDF
                    </a>
                    @if(!$specificCustomerId && !$specificNodeKey)
                        <span style="margin: 0 8px; color: #64748b;">|</span>
                        <span style="font-size: 11px; color: #cbd5e1; margin-right: 6px;">Tampilan Modul:</span>
                        <a href="{{ route('operational.chronic-monitoring.export', array_merge($queryParams, ['mode' => 'print', 'scope' => 'all'])) }}" class="btn-bar btn-scope {{ $scope === 'all' ? 'active' : '' }}">Semua Modul (Lengkap)</a>
                        <a href="{{ route('operational.chronic-monitoring.export', array_merge($queryParams, ['mode' => 'print', 'scope' => 'customers'])) }}" class="btn-bar btn-scope {{ $scope === 'customers' ? 'active' : '' }}">1. Pelanggan Sering Gangguan ({{ count($chronicCustomers) }})</a>
                        <a href="{{ route('operational.chronic-monitoring.export', array_merge($queryParams, ['mode' => 'print', 'scope' => 'paths'])) }}" class="btn-bar btn-scope {{ $scope === 'paths' ? 'active' : '' }}">2. Jalur ODP/ODC Rawan ({{ count($chronicPaths) }})</a>
                        <a href="{{ route('operational.chronic-monitoring.export', array_merge($queryParams, ['mode' => 'print', 'scope' => 'visits'])) }}" class="btn-bar btn-scope {{ $scope === 'visits' ? 'active' : '' }}">3. Kunjungan Sosialisasi ({{ count($socializationVisits) }})</a>
                    @endif
                </td>
                <td class="right">
                    <button type="button" class="btn-bar btn-close" onclick="window.close()">Tutup</button>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- HEADER DOKUMEN -->
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: middle;">
                <table>
                    <tr>
                        @if($logoBase64)
                            <td style="padding-right: 14px; vertical-align: middle; border-right: 2px solid #cbd5e1;">
                                <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 56px; max-width: 175px; width: auto; height: auto; object-fit: contain; display: block;">
                            </td>
                            <td style="width: 12px;"></td>
                        @elseif(!empty($companyLogo) && $mode === 'print')
                            <td style="padding-right: 14px; vertical-align: middle; border-right: 2px solid #cbd5e1;">
                                <img src="{{ asset('storage/' . $companyLogo) }}" alt="Logo" style="max-height: 56px; max-width: 175px; width: auto; height: auto; object-fit: contain; display: block;" onerror="this.style.display='none'">
                            </td>
                            <td style="width: 12px;"></td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="company-name">{{ $companyName }}</div>
                            @if($companyAddress)
                                <div class="company-sub">{{ $companyAddress }}</div>
                            @endif
                            @if($companyPhone)
                                <div class="company-sub">Telp / NOC Layanan Pelanggan: {{ $companyPhone }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 42%; vertical-align: middle;">
                <div class="report-title">{{ $scopeTitle }}</div>
                <div class="report-meta">
                    <strong>Periode Pantauan:</strong> {{ $periodLabel }} &nbsp;|&nbsp;
                    <strong>Wilayah / Area:</strong> {{ $areaLabel }}<br>
                    <strong>Filter Frekuensi:</strong> &ge; {{ $filters['min_tickets'] ?? 1 }}x Gangguan
                    @if(!empty($filters['search']))
                        &nbsp;|&nbsp; <strong>Kata Kunci:</strong> "{{ $filters['search'] }}"
                    @endif
                    <br>
                    <strong>Dicetak Oleh:</strong> {{ $printedBy }} &nbsp;|&nbsp; <strong>Waktu Cetak:</strong> {{ $printedAt }}
                </div>
            </td>
        </tr>
    </table>

    <!-- RINGKASAN EKSEKUTIF KPI -->
    @if(!$specificCustomerId && !$specificNodeKey)
    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <div class="kpi-label">Pelanggan Rawan / Kronis</div>
                <div class="kpi-value" style="color: #be123c;">
                    {{ ($kpiSummary['chronic_customers_critical'] ?? 0) + ($kpiSummary['chronic_customers_warning'] ?? 0) }}
                    <span style="font-size: 10px; font-weight: normal; color: #64748b;">dari {{ $kpiSummary['total_customers_with_issues'] ?? 0 }} pelanggan</span>
                </div>
                <div class="kpi-sub">
                    Kronis (&ge;3x): <strong>{{ $kpiSummary['chronic_customers_critical'] ?? 0 }}</strong> &nbsp;|&nbsp;
                    Rawan (2x): <strong>{{ $kpiSummary['chronic_customers_warning'] ?? 0 }}</strong>
                </div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Wajib Dikunjungi Sosialisasi</div>
                <div class="kpi-value" style="color: #d97706;">
                    {{ $kpiSummary['unvisited_repeat_customers'] ?? 0 }}
                    <span style="font-size: 10px; font-weight: normal; color: #64748b;">pelanggan berulang (&ge;2x)</span>
                </div>
                <div class="kpi-sub">
                    Belum didatangi petugas untuk sosialisasi & edukasi
                </div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Jalur / ODP / ODC Rawan</div>
                <div class="kpi-value" style="color: #6d28d9;">
                    {{ $kpiSummary['total_paths_with_issues'] ?? 0 }}
                    <span style="font-size: 10px; font-weight: normal; color: #64748b;">titik distribusi</span>
                </div>
                <div class="kpi-sub">
                    Jalur Kritis: <strong>{{ $kpiSummary['chronic_paths_critical'] ?? 0 }}</strong> titik perlu peremajaan
                </div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Kunjungan Sosialisasi Rumah</div>
                <div class="kpi-value" style="color: #059669;">
                    {{ $kpiSummary['visits_completed'] ?? 0 }}
                    <span style="font-size: 10px; font-weight: normal; color: #64748b;">selesai dikunjungi</span>
                </div>
                <div class="kpi-sub">
                    Menunggu / Terjadwal Kunjungan: <strong>{{ $kpiSummary['visits_pending'] ?? 0 }}</strong>
                </div>
            </td>
        </tr>
    </table>
    @endif

    <!-- ========================================================================= -->
    <!-- BAGIAN 1: PELANGGAN SERING GANGGUAN                                       -->
    <!-- ========================================================================= -->
    @if($scope === 'all' || $scope === 'customers')
        <div class="section-header">
            1. Daftar Analisa Pelanggan Rawan & Sering Gangguan ({{ count($chronicCustomers) }} Pelanggan)
        </div>

        @if(count($chronicCustomers) === 0)
            <p style="padding: 10px; background: #f8fafc; border: 1px solid #cbd5e1; text-align: center; color: #64748b;">
                Tidak ada data pelanggan gangguan yang sesuai dengan filter ini.
            </p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 3%;" class="center">No</th>
                        <th style="width: 15%;">Pelanggan & ID</th>
                        <th style="width: 16%;">Wilayah & Alamat Rumah</th>
                        <th style="width: 13%;">Jalur ODP & Redaman</th>
                        <th style="width: 11%;">Frekuensi & Perangkat</th>
                        <th style="width: 14%;">Keluhan Dominan</th>
                        <th style="width: 15%;">Diagnosa & Rekomendasi</th>
                        <th style="width: 13%;">Status Kunjungan Sosialisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chronicCustomers as $idx => $cust)
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $cust['name'] }}</strong><br>
                                <span class="font-mono" style="font-size: 8.5px; color: #334155;">{{ $cust['customer_code'] }}</span>
                                @if(!empty($cust['phone']))
                                    <br><span class="text-muted">HP: {{ $cust['phone'] }}</span>
                                @endif
                                <br><span class="text-muted">Paket: {{ $cust['package_name'] }}</span>
                            </td>
                            <td>
                                <strong>{{ $cust['area_name'] }}</strong><br>
                                <span>{{ $cust['address'] ?: '-' }}</span>
                            </td>
                            <td>
                                <strong>ODP: {{ $cust['odp_name'] }}</strong>
                                @if(!empty($cust['odp_port']))
                                    (Port {{ $cust['odp_port'] }})
                                @endif
                                <br>
                                <span class="text-muted">ODC: {{ $cust['odc_name'] }}</span><br>
                                @if($cust['rx_power'] !== null)
                                    <span style="font-weight: bold; color: {{ !empty($cust['is_bad_rx']) ? '#be123c' : '#059669' }};">
                                        Redaman: {{ $cust['rx_power'] }} dBm {{ !empty($cust['is_bad_rx']) ? '(RAWAN)' : '(Normal)' }}
                                    </span>
                                @else
                                    <span class="text-muted">Redaman: -</span>
                                @endif
                            </td>
                            <td>
                                @if($cust['severity'] === 'critical')
                                    <span class="badge badge-critical">KRONIS ({{ $cust['total_tickets'] }}x)</span>
                                @elseif($cust['severity'] === 'warning')
                                    <span class="badge badge-warning">RAWAN ({{ $cust['total_tickets'] }}x)</span>
                                @else
                                    <span class="badge badge-normal">{{ $cust['total_tickets'] }}x Gangguan</span>
                                @endif
                                <div style="margin-top: 3px;">
                                    PGK: <strong>{{ $cust['pgk_count'] }}</strong> | PGS: <strong>{{ $cust['pgs_count'] }}</strong>
                                </div>
                                @if(($cust['ont_replaced_count'] ?? 0) > 0)
                                    <div style="color: #b45309; font-weight: bold;">Ganti ONT: {{ $cust['ont_replaced_count'] }}x</div>
                                @endif
                                @if(($cust['material_replaced_count'] ?? 0) > 0)
                                    <div style="color: #4338ca; font-weight: bold;">Ganti Material: {{ $cust['material_replaced_count'] }}x</div>
                                @endif
                            </td>
                            <td>
                                @if(!empty($cust['top_categories']))
                                    @foreach($cust['top_categories'] as $cat)
                                        <div>&bull; {{ $cat['category'] }} (<strong>{{ $cat['count'] }}x</strong>)</div>
                                    @endforeach
                                @else
                                    <span>{{ $cust['dominant_category'] ?? '-' }}</span>
                                @endif
                                <div class="text-muted" style="margin-top: 2px;">Terakhir: {{ $cust['last_ticket_at'] }}</div>
                            </td>
                            <td>{{ $cust['recommendation'] }}</td>
                            <td>
                                @if(!empty($cust['latest_visit']))
                                    @if($cust['latest_visit']['status'] === 'completed')
                                        <span class="badge badge-done">SUDAH DIKUNJUNGI</span>
                                    @else
                                        <span class="badge badge-scheduled">TERJADWAL</span>
                                    @endif
                                    <div style="margin-top: 3px;">
                                        <strong>{{ $cust['latest_visit']['visit_number'] }}</strong><br>
                                        Tgl: {{ $cust['latest_visit']['scheduled_date'] }}<br>
                                        Petugas: {{ $cust['latest_visit']['officers'] }}
                                        @if(!empty($cust['latest_visit']['met_with_name']))
                                            <br>Bertemu: {{ $cust['latest_visit']['met_with_name'] }} ({{ $cust['latest_visit']['response_label'] }})
                                        @endif
                                    </div>
                                @else
                                    <span class="badge badge-unvisited">BELUM DIKUNJUNGI</span>
                                    @if($cust['total_tickets'] >= 2)
                                        <div class="text-muted" style="margin-top: 3px;">Perlu penugasan petugas sosialisasi ke rumah pelanggan</div>
                                    @endif
                                @endif
                            </td>
                        </tr>

                        @if($specificCustomerId && !empty($cust['tickets_timeline']))
                            <tr>
                                <td colspan="8" style="background: #fff1f2; padding: 8px;">
                                    <strong>Rincian Riwayat Tiket Gangguan Pelanggan ({{ count($cust['tickets_timeline']) }} Tiket):</strong>
                                    <table style="width: 100%; border-collapse: collapse; margin-top: 5px; background: #ffffff;">
                                        <thead>
                                            <tr>
                                                <th style="width: 14%;">No. Tiket & Jenis</th>
                                                <th style="width: 14%;">Tanggal</th>
                                                <th style="width: 16%;">Kategori</th>
                                                <th style="width: 22%;">Keluhan Dilaporkan</th>
                                                <th style="width: 22%;">Tindakan Perbaikan</th>
                                                <th style="width: 12%;">Petugas & Redaman</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cust['tickets_timeline'] as $t)
                                                <tr>
                                                    <td><strong>{{ $t['ticket_number'] }}</strong> ({{ $t['type'] }})</td>
                                                    <td>{{ $t['created_at'] }}</td>
                                                    <td>{{ $t['category'] }}</td>
                                                    <td>{{ $t['reported_issue'] ?: '-' }}</td>
                                                    <td>{{ $t['action_taken'] ?: '-' }}</td>
                                                    <td>
                                                        {{ $t['technicians'] }}
                                                        @if(!empty($t['optical_power_after']))
                                                            <br><span class="text-muted">Redaman: {{ $t['optical_power_after'] }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <!-- ========================================================================= -->
    <!-- BAGIAN 2: JALUR / ODP / ODC SERING GANGGUAN                               -->
    <!-- ========================================================================= -->
    @if($scope === 'all' || $scope === 'paths')
        <div class="section-header purple" style="{{ $scope === 'all' ? 'margin-top: 16px;' : '' }}">
            2. Daftar Analisa Jalur Distribusi (ODP / ODC) Rawan Gangguan ({{ count($chronicPaths) }} Titik Distribusi)
        </div>

        @if(count($chronicPaths) === 0)
            <p style="padding: 10px; background: #f8fafc; border: 1px solid #cbd5e1; text-align: center; color: #64748b;">
                Tidak ada titik jalur ODP/ODC bermasalah pada filter ini.
            </p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 3%;" class="center">No</th>
                        <th style="width: 16%;">Nama Titik (ODP/ODC) & Induk</th>
                        <th style="width: 11%;">Wilayah & Kapasitas</th>
                        <th style="width: 13%;">Statistik Insiden Jalur</th>
                        <th style="width: 11%;">Kualitas Redaman</th>
                        <th style="width: 16%;">Pelanggan Terdampak</th>
                        <th style="width: 15%;">Analisa Kesehatan Jalur</th>
                        <th style="width: 15%;">Pemeliharaan & Bekal Material</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chronicPaths as $idx => $pathNode)
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td>
                                <span class="badge badge-purple">{{ $pathNode['node_type'] }}</span>
                                <strong style="font-size: 9.5px;">{{ $pathNode['name'] }}</strong><br>
                                <span class="text-muted">{{ $pathNode['parent_name'] }}</span>
                                @if(!empty($pathNode['address']) && $pathNode['address'] !== '-')
                                    <br><span class="text-muted">Lokasi: {{ $pathNode['address'] }}</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $pathNode['area_name'] }}</strong>
                                @if(($pathNode['total_ports'] ?? 0) > 0)
                                    <br><span class="text-muted">Port: {{ $pathNode['used_ports'] }}/{{ $pathNode['total_ports'] }} ({{ $pathNode['port_utilization'] }}%)</span>
                                @endif
                            </td>
                            <td>
                                @if($pathNode['severity'] === 'critical')
                                    <span class="badge badge-critical">KRITIS ({{ $pathNode['total_incidents'] }} Insiden)</span>
                                @elseif($pathNode['severity'] === 'warning')
                                    <span class="badge badge-warning">RAWAN ({{ $pathNode['total_incidents'] }} Insiden)</span>
                                @else
                                    <span class="badge badge-normal">{{ $pathNode['total_incidents'] }} Insiden</span>
                                @endif
                                <div style="margin-top: 3px;">
                                    PGJ: <strong>{{ $pathNode['pgj_count'] }}</strong> | PGK+PGS: <strong>{{ $pathNode['customer_ticket_count'] }}</strong><br>
                                    Terdampak: <strong>{{ $pathNode['affected_customers_count'] }} Pelanggan</strong>
                                </div>
                            </td>
                            <td>
                                @if($pathNode['avg_rx_power'] !== null)
                                    Rata-rata: <strong>{{ $pathNode['avg_rx_power'] }} dBm</strong><br>
                                    Terburuk: <strong style="color: {{ ($pathNode['bad_rx_ont_count'] ?? 0) > 0 ? '#be123c' : '#059669' }};">{{ $pathNode['worst_rx_power'] }} dBm</strong>
                                    @if(($pathNode['bad_rx_ont_count'] ?? 0) > 0)
                                        <br><span style="color: #be123c; font-weight: bold;">{{ $pathNode['bad_rx_ont_count'] }} ONT Redaman Buruk</span>
                                    @endif
                                @else
                                    <span class="text-muted">Data OPM belum tersedia</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($pathNode['affected_customers']))
                                    @foreach(array_slice($pathNode['affected_customers'], 0, 6) as $ac)
                                        <div>
                                            &bull; {{ $ac['name'] }} (<strong>{{ $ac['total_tickets'] }}x</strong>)
                                            @if(!empty($ac['has_visited']))
                                                <span style="color: #059669; font-weight: bold;">[Sudah Sosialisasi]</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if(count($pathNode['affected_customers']) > 6)
                                        <div class="text-muted">+{{ count($pathNode['affected_customers']) - 6 }} pelanggan lainnya</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $pathNode['diagnosis'] }}</td>
                            <td>
                                @if(!empty($pathNode['latest_maintenance']))
                                    <span class="badge badge-done">{{ strtoupper($pathNode['latest_maintenance']['status']) }}</span>
                                    <div style="margin-top: 3px;">
                                        <strong>{{ $pathNode['latest_maintenance']['maintenance_number'] }}</strong><br>
                                        Jadwal: {{ $pathNode['latest_maintenance']['scheduled_date'] }}<br>
                                        Petugas: {{ $pathNode['latest_maintenance']['technicians'] }}
                                        @if(!empty($pathNode['latest_maintenance']['materials_summary']))
                                            <br><strong>Material:</strong> {{ implode(', ', $pathNode['latest_maintenance']['materials_summary']) }}
                                        @endif
                                        @if(!empty($pathNode['latest_maintenance']['action_taken']))
                                            <br><span class="text-muted">Hasil: {{ $pathNode['latest_maintenance']['action_taken'] }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge badge-unvisited">BELUM DIJADWALKAN</span>
                                    <div class="text-muted" style="margin-top: 2px;">Perlu penjadwalan pengecekan / peremajaan jalur</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <!-- ========================================================================= -->
    <!-- BAGIAN 3: KUNJUNGAN SOSIALISASI RUMAH PELANGGAN                           -->
    <!-- ========================================================================= -->
    @if($scope === 'all' || $scope === 'visits')
        <div class="section-header emerald" style="{{ $scope === 'all' ? 'margin-top: 16px;' : '' }}">
            3. Daftar Penugasan & Laporan Kunjungan Sosialisasi Rumah Pelanggan ({{ count($socializationVisits) }} Kunjungan)
        </div>

        @if(count($socializationVisits) === 0)
            <p style="padding: 10px; background: #f8fafc; border: 1px solid #cbd5e1; text-align: center; color: #64748b;">
                Belum ada data jadwal atau laporan kunjungan sosialisasi ke rumah pelanggan pada filter ini.
            </p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 3%;" class="center">No</th>
                        <th style="width: 12%;">No. Tugas & Jadwal</th>
                        <th style="width: 16%;">Pelanggan & Alamat Rumah</th>
                        <th style="width: 11%;">Wilayah & ODP</th>
                        <th style="width: 13%;">Petugas Sosialisasi</th>
                        <th style="width: 18%;">Tujuan & Riwayat Masalah</th>
                        <th style="width: 17%;">Hasil Kunjungan & Respon</th>
                        <th style="width: 10%;" class="center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($socializationVisits as $idx => $visit)
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td>
                                <strong class="font-mono">{{ $visit['visit_number'] }}</strong><br>
                                <span>{{ $visit['scheduled_date_formatted'] }}</span>
                                @if(!empty($visit['scheduled_time']))
                                    <span>({{ $visit['scheduled_time'] }})</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $visit['customer_name'] }}</strong> ({{ $visit['customer_code'] }})<br>
                                <span>{{ $visit['customer_address'] }}</span>
                                @if(!empty($visit['customer_phone']) && $visit['customer_phone'] !== '-')
                                    <br><span class="text-muted">HP: {{ $visit['customer_phone'] }}</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $visit['area_name'] }}</strong><br>
                                <span class="text-muted">ODP: {{ $visit['odp_name'] }}</span>
                            </td>
                            <td>
                                <strong>{{ $visit['officers_str'] }}</strong>
                            </td>
                            <td>
                                <strong>{{ $visit['purpose_label'] }}</strong><br>
                                <span>{{ $visit['title'] }}</span>
                                @if(!empty($visit['issue_summary']))
                                    <br><span class="text-muted">{{ $visit['issue_summary'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($visit['met_with_name']) || !empty($visit['report_notes']))
                                    <div><strong>Bertemu:</strong> {{ $visit['met_with_name'] ?: '-' }} {{ !empty($visit['met_with_relation']) ? '(' . $visit['met_with_relation'] . ')' : '' }}</div>
                                    <div><strong>Respon:</strong> {{ $visit['response_label'] }}</div>
                                    @if(!empty($visit['report_notes']))
                                        <div style="margin-top: 2px;">"{{ $visit['report_notes'] }}"</div>
                                    @endif
                                    @if(!empty($visit['follow_up_action']))
                                        <div style="color: #be123c; font-weight: bold;">Tindak Lanjut: {{ $visit['follow_up_action'] }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">Menunggu laporan kunjungan petugas dari rumah pelanggan</span>
                                @endif
                            </td>
                            <td class="center">
                                @if($visit['status'] === 'completed')
                                    <span class="badge badge-done">SELESAI</span>
                                    @if(!empty($visit['completed_at']))
                                        <br><span class="text-muted">{{ $visit['completed_at'] }}</span>
                                    @endif
                                @elseif($visit['status'] === 'in_progress')
                                    <span class="badge badge-warning">DIKUNJUNGI</span>
                                @else
                                    <span class="badge badge-scheduled">TERJADWAL</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <!-- FOOTER TANDA TANGAN PENGESAHAN LAPORAN -->
    <table class="sign-table">
        <tr>
            <td>
                <div>Dibuat / Dicetak Oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-line">{{ $printedBy }}</div>
                <div class="text-muted">Petugas / Admin Operasional</div>
            </td>
            <td>
                <div>Diperiksa Oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-line">Koordinator NOC / Maintenance</div>
                <div class="text-muted">Divisi Teknis & Infrastruktur</div>
            </td>
            <td>
                <div>Mengetahui & Menyetujui,</div>
                <div class="sign-space"></div>
                <div class="sign-line">Manager Operasional</div>
                <div class="text-muted">{{ $companyName }}</div>
            </td>
        </tr>
    </table>

    @if($mode === 'print' && request()->boolean('autoprint', false))
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 350);
        });
    </script>
    @endif
</body>
</html>

