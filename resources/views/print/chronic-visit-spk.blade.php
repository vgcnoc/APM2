<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK & Berita Acara Sosialisasi Pelanggan #{{ $visit->visit_number }}</title>
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
        $officersList = $visit->officers->pluck('name')->join(', ') ?: 'Petugas Sosialisasi';
        $odpName = $visit->odp?->name ?: ($customer?->ont?->odp?->name ?? '-');
        $odcName = $visit->odp?->odc?->name ?: ($customer?->ont?->odp?->odc?->name ?? '-');
        $areaName = $visit->area?->name ?: ($customer?->areaModel?->name ?: ($customer?->area ?: '-'));
        $rxPower = $customer?->ont?->rx_power;
        $standardPoints = [
            'Menyampaikan permohonan maaf & empati langsung kepada pelanggan di rumah',
            'Menjelaskan penyebab gangguan & tindakan perbaikan yang telah dilakukan tim',
            'Mengecek langsung sinyal WiFi, posisi ONT/Router & redaman di rumah pelanggan',
            'Memberikan edukasi penggunaan perangkat & nomor kontak pengaduan prioritas',
            'Memastikan pelanggan sudah kembali nyaman menggunakan layanan internet',
        ];
        $checkedPoints = is_array($visit->socialization_points) ? $visit->socialization_points : [];
    @endphp
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
            padding: {{ $mode === 'print' ? '18px' : '0' }};
            background: #ffffff;
        }
        .no-print-toolbar {
            max-width: 780px;
            margin: 0 auto 14px auto;
            background: #0f172a;
            padding: 10px 16px;
            border-radius: 8px;
        }
        .no-print-toolbar table { width: 100%; border-collapse: collapse; }
        .no-print-toolbar td { border: none; padding: 0; }
        .btn-bar {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
            color: #ffffff;
            border: none;
            cursor: pointer;
            margin-right: 6px;
        }
        .btn-print { background: #059669; }
        .btn-pdf { background: #2563eb; }
        .btn-close { background: #475569; }

        .doc-container {
            max-width: 780px;
            margin: 0 auto;
        }
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #059669;
            margin-bottom: 12px;
        }
        table.header-table td {
            border: none;
            padding: 0 0 10px 0;
            vertical-align: top;
        }
        .brand-title {
            font-size: 17px;
            font-weight: bold;
            color: #065f46;
            text-transform: uppercase;
            margin: 0;
        }
        .brand-sub {
            font-size: 10px;
            color: #475569;
            margin: 2px 0 0 0;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #059669;
            text-align: right;
            text-transform: uppercase;
            margin: 0;
        }
        .doc-no {
            font-family: Courier, monospace;
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            margin-top: 3px;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-done { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-scheduled { background: #e0f2fe; color: #075985; border: 1px solid #7dd3fc; }
        .badge-priority { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .section-title {
            background: #ecfdf5;
            border-left: 4px solid #059669;
            padding: 5px 9px;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #065f46;
            margin: 11px 0 6px 0;
        }

        table.info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.info-grid td {
            padding: 3.5px 6px;
            vertical-align: top;
            font-size: 10.5px;
            border-bottom: 1px dashed #e2e8f0;
        }
        table.info-grid td.lbl {
            width: 26%;
            font-weight: bold;
            color: #475569;
        }
        table.info-grid td.val {
            width: 74%;
            color: #0f172a;
        }

        table.ticket-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.ticket-table th,
        table.ticket-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 9.5px;
            vertical-align: top;
        }
        table.ticket-table th {
            background: #f1f5f9;
            font-weight: bold;
            text-align: left;
        }

        .checklist-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            background: #f8fafc;
        }
        .check-item {
            margin: 4px 0;
            font-size: 10px;
        }
        .check-mark {
            display: inline-block;
            width: 14px;
            font-family: Courier, monospace;
            font-weight: bold;
        }

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
            padding: 4px 8px;
            font-size: 10px;
        }
        .sign-space { height: 55px; }
        .sign-line {
            border-top: 1px solid #1e293b;
            display: inline-block;
            min-width: 165px;
            padding-top: 3px;
            font-weight: bold;
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
                    <button type="button" class="btn-bar btn-print" onclick="window.print()">Cetak SPK & Berita Acara</button>
                    <a href="{{ route('operational.chronic-monitoring.visits.spk', ['visit' => $visit->id, 'mode' => 'pdf']) }}" class="btn-bar btn-pdf">
                        Download PDF
                    </a>
                </td>
                <td style="text-align: right;">
                    <button type="button" class="btn-bar btn-close" onclick="window.close()">Tutup</button>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <div class="doc-container">
        <!-- HEADER -->
        <table class="header-table">
            <tr>
                <td style="width: 52%;">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 40px; margin-bottom: 4px; display: block;">
                    @endif
                    <div class="brand-title">{{ $companyName }}</div>
                    @if($companyAddress)
                        <div class="brand-sub">{{ $companyAddress }}</div>
                    @endif
                    @if($companyPhone)
                        <div class="brand-sub">Layanan Pelanggan / NOC: {{ $companyPhone }}</div>
                    @endif
                </td>
                <td style="width: 48%; text-align: right;">
                    <div class="doc-title">SURAT TUGAS & BERITA ACARA</div>
                    <div style="font-size: 10.5px; font-weight: bold; color: #475569;">KUNJUNGAN SOSIALISASI & PENDAMPINGAN PELANGGAN</div>
                    <div class="doc-no">{{ $visit->visit_number }}</div>
                    <div style="margin-top: 4px;">
                        @if($visit->status === 'completed')
                            <span class="badge badge-done">SELESAI DIKUNJUNGI</span>
                        @else
                            <span class="badge badge-scheduled">TERJADWAL KUNJUNGAN</span>
                        @endif
                        <span class="badge badge-priority">PRIORITAS: {{ strtoupper($visit->priority ?: 'HIGH') }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- 1. IDENTITAS PELANGGAN & LOKASI RUMAH -->
        <div class="section-title">1. Data Pelanggan & Lokasi Kunjungan Rumah</div>
        <table class="info-grid">
            <tr>
                <td class="lbl">Nama Pelanggan / ID</td>
                <td class="val">
                    <strong>{{ $customer?->name ?? '-' }}</strong>
                    ({{ $customer?->customer_code ?? '-' }})
                </td>
            </tr>
            <tr>
                <td class="lbl">Nomor HP / WhatsApp</td>
                <td class="val">{{ $customer?->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Alamat Rumah Lengkap</td>
                <td class="val"><strong>{{ $customer?->address ?? '-' }}</strong> ({{ $areaName }})</td>
            </tr>
            <tr>
                <td class="lbl">Paket & Jalur Distribusi</td>
                <td class="val">
                    Paket: <strong>{{ $customer?->package?->name ?? ($customer?->package_name ?? '-') }}</strong> &nbsp;|&nbsp;
                    ODP: <strong>{{ $odpName }}</strong> (ODC: {{ $odcName }})
                    @if($rxPower !== null)
                        &nbsp;|&nbsp; Redaman ONT: <strong>{{ $rxPower }} dBm</strong>
                    @endif
                </td>
            </tr>
        </table>

        <!-- 2. DETAIL PENUGASAN PETUGAS SOSIALISASI -->
        <div class="section-title">2. Detail Penugasan Petugas Sosialisasi</div>
        <table class="info-grid">
            <tr>
                <td class="lbl">Petugas yang Ditugaskan</td>
                <td class="val"><strong>{{ $officersList }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Jadwal Kunjungan</td>
                <td class="val">
                    <strong>{{ $visit->scheduled_date ? \Carbon\Carbon::parse($visit->scheduled_date)->locale('id')->isoFormat('dddd, D MMMM Y') : '-' }}</strong>
                    {{ $visit->scheduled_time ? '(Pukul ' . substr((string) $visit->scheduled_time, 0, 5) . ' WIB)' : '' }}
                </td>
            </tr>
            <tr>
                <td class="lbl">Tujuan Kunjungan</td>
                <td class="val"><strong>{{ $visit->purpose_label }}</strong> — {{ $visit->title }}</td>
            </tr>
            @if(!empty($visit->issue_summary))
            <tr>
                <td class="lbl">Ringkasan Riwayat Gangguan</td>
                <td class="val">{{ $visit->issue_summary }}</td>
            </tr>
            @endif
            @if(!empty($visit->admin_notes))
            <tr>
                <td class="lbl">Instruksi Khusus Petugas</td>
                <td class="val">{{ $visit->admin_notes }}</td>
            </tr>
            @endif
        </table>

        <!-- 3. RIWAYAT TIKET GANGGUAN PELANGGAN -->
        @if(!empty($recentTickets))
        <div class="section-title">3. Riwayat Tiket Gangguan Terakhir Pelanggan</div>
        <table class="ticket-table">
            <thead>
                <tr>
                    <th style="width: 18%;">No. Tiket</th>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 20%;">Kategori</th>
                    <th style="width: 23%;">Keluhan</th>
                    <th style="width: 24%;">Tindakan Perbaikan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentTickets as $rt)
                    <tr>
                        <td><strong>{{ $rt['ticket_number'] }}</strong> ({{ $rt['type'] }})</td>
                        <td>{{ $rt['created_at'] }}</td>
                        <td>{{ $rt['category'] }}</td>
                        <td>{{ $rt['reported_issue'] }}</td>
                        <td>{{ $rt['action_taken'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- 4. CHECKLIST POIN SOSIALISASI & HASIL KUNJUNGAN DI RUMAH PELANGGAN -->
        <div class="section-title">4. Berita Acara Pelaksanaan & Hasil Sosialisasi di Rumah Pelanggan</div>
        <div class="checklist-box">
            <div style="font-weight: bold; margin-bottom: 4px; color: #0f172a;">Poin Sosialisasi & Pengecekan Layanan yang Dilakukan Petugas:</div>
            @foreach($standardPoints as $pt)
                @php
                    $isChecked = in_array($pt, $checkedPoints, true);
                @endphp
                <div class="check-item">
                    <span class="check-mark">{{ $isChecked ? '[X]' : '[ ]' }}</span>
                    <span style="{{ $isChecked ? 'font-weight: bold; color: #065f46;' : '' }}">{{ $pt }}</span>
                </div>
            @endforeach
        </div>

        <table class="info-grid" style="margin-top: 6px;">
            <tr>
                <td class="lbl">Bertemu Dengan</td>
                <td class="val">
                    <strong>{{ $visit->met_with_name ?: '........................................................................' }}</strong>
                    @if(!empty($visit->met_with_relation))
                        ({{ $visit->met_with_relation }})
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Respon / Tanggapan Pelanggan</td>
                <td class="val">
                    <strong>{{ $visit->customer_response ? $visit->response_label : '........................................................................' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="lbl">Catatan Hasil Sosialisasi</td>
                <td class="val">
                    @if(!empty($visit->report_notes))
                        {{ $visit->report_notes }}
                    @else
                        <div style="height: 32px; border-bottom: 1px dotted #94a3b8;"></div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Rekomendasi / Tindak Lanjut</td>
                <td class="val">
                    {{ $visit->follow_up_action ?: '-' }}
                </td>
            </tr>
            @if($visit->completed_at || ($visit->latitude && $visit->longitude))
            <tr>
                <td class="lbl">Waktu Selesai & Titik GPS</td>
                <td class="val">
                    Selesai: <strong>{{ $visit->completed_at ? \Carbon\Carbon::parse($visit->completed_at)->format('d/m/Y H:i') : '-' }}</strong>
                    @if($visit->latitude && $visit->longitude)
                        &nbsp;|&nbsp; Koordinat GPS: <strong>{{ $visit->latitude }}, {{ $visit->longitude }}</strong>
                    @endif
                </td>
            </tr>
            @endif
        </table>

        @if($photoBase64)
        <div style="margin-top: 8px; text-align: left;">
            <div style="font-size: 9.5px; font-weight: bold; color: #475569; margin-bottom: 4px;">Dokumentasi Foto Kunjungan di Rumah Pelanggan:</div>
            <img src="{{ $photoBase64 }}" alt="Foto Kunjungan" style="max-height: 135px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px;">
        </div>
        @endif

        <!-- TANDA TANGAN BERITA ACARA -->
        <table class="sign-table">
            <tr>
                <td>
                    <div>Petugas Sosialisasi,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">{{ $officersList }}</div>
                    <div style="font-size: 8.5px; color: #64748b;">Petugas Lapangan</div>
                </td>
                <td>
                    <div>Pelanggan / Perwakilan Rumah,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">{{ $visit->met_with_name ?: ($customer?->name ?: '.................................') }}</div>
                    <div style="font-size: 8.5px; color: #64748b;">Tanda Tangan & Nama Jelas</div>
                </td>
                <td>
                    <div>Mengetahui,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">Koordinator / Manager</div>
                    <div style="font-size: 8.5px; color: #64748b;">{{ $companyName }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

