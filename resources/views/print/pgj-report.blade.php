<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK & Berita Acara PGJ #{{ $ticket->ticket_number }}</title>
    @php
        $companyName = \App\Models\Setting::get('company_name', config('app.name', 'ISP Provider'));
        $companyAddress = \App\Models\Setting::get('company_address', '');
        $companyPhone = \App\Models\Setting::get('company_phone', '');
        $companyLogo = \App\Models\Setting::get('company_logo', '');
    @endphp
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.5; color: #222; margin: 0; padding: 20px; }
        .box { max-width: 800px; margin: auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #dc2626; padding-bottom: 15px; margin-bottom: 20px; }
        .brand h1 { margin: 0; font-size: 20px; color: #991b1b; text-transform: uppercase; }
        .brand p { margin: 2px 0; color: #555; font-size: 12px; }
        .title-section { text-align: right; }
        .title-section h2 { margin: 0; font-size: 18px; color: #dc2626; font-weight: 900; }
        .title-section .ticket-no { font-family: monospace; font-size: 15px; font-weight: bold; color: #991b1b; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-critical { background: #fee2e2; color: #b91c1c; }
        .badge-high { background: #ffedd5; color: #c2410c; }
        .badge-medium { background: #e0e7ff; color: #3730a3; }
        .badge-low { background: #f3f4f6; color: #4b5563; }
        .badge-segment { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 800; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #991b1b; background: #fef2f2; padding: 5px 8px; border-left: 3px solid #dc2626; margin: 15px 0 8px 0; }
        table.info-grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.info-grid td { padding: 4px 6px; vertical-align: top; }
        table.info-grid td.label { width: 25%; font-weight: bold; color: #4b5563; }
        table.info-grid td.value { width: 75%; color: #111; }
        table.data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 12px; }
        table.data-table th { background: #f8fafc; text-align: left; font-weight: bold; }
        .sign-area { display: flex; justify-content: space-between; margin-top: 35px; page-break-inside: avoid; }
        .sign-box { width: 30%; text-align: center; }
        .sign-space { height: 60px; }
        .sign-name { border-top: 1px solid #333; font-weight: bold; margin-top: 5px; padding-top: 3px; }
        .toolbar { margin-bottom: 15px; }
        .btn-print { background: #dc2626; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        @media print {
            body { padding: 0; }
            .box { border: none; box-shadow: none; max-width: 100%; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak SPK & Berita Acara Restorasi PGJ</button>
        <button class="btn-print" style="background:#4b5563;" onclick="window.close()">Tutup</button>
    </div>

    <div class="box">
        <!-- Header -->
        <div class="header">
            <div class="brand">
                @if($companyLogo)
                    <img src="{{ asset('storage/' . $companyLogo) }}" alt="Logo" style="max-height: 50px; margin-bottom: 5px;">
                @endif
                <h1>{{ $companyName }}</h1>
                <p>{{ $companyAddress }}</p>
                @if($companyPhone) <p>NOC / Call Center: {{ $companyPhone }}</p> @endif
            </div>
            <div class="title-section">
                <h2>SURAT PERINTAH KERJA & BERITA ACARA</h2>
                <div style="font-size:12px; font-weight:bold; color:#4b5563;">RESTORASI GANGGUAN JALUR FIBER OPTIK (PGJ)</div>
                <div class="ticket-no">{{ $ticket->ticket_number }}</div>
                <div style="margin-top:4px;">
                    <span class="badge badge-segment">{{ strtoupper($ticket->segment_type) }}</span>
                    <span class="badge badge-{{ $ticket->priority }}">{{ $ticket->priority }} PRIORITY</span>
                </div>
            </div>
        </div>

        <!-- 1. Identifikasi Infrastruktur & Titik Jalur -->
        <div class="section-title">1. Identifikasi Segmen & Infrastruktur Jalur</div>
        <table class="info-grid">
            <tr>
                <td class="label">Segmen Jaringan</td>
                <td class="value">
                    <strong style="text-transform:uppercase; color:#991b1b;">{{ $ticket->segment_type }}</strong> 
                    @if($ticket->segment_type === 'backbone') (Jalur Utama / Feeder Trunk)
                    @elseif($ticket->segment_type === 'odc') (Optical Distribution Cabinet)
                    @elseif($ticket->segment_type === 'odp') (Optical Distribution Point)
                    @elseif($ticket->segment_type === 'distribution') (Jalur Distribusi ODC-ODP)
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">ODC Terkait</td>
                <td class="value"><strong>{{ $ticket->odc?->name ?: '-' }}</strong> {{ $ticket->odc?->location ? '(' . $ticket->odc->location . ')' : '' }}</td>
            </tr>
            <tr>
                <td class="label">ODP Terkait</td>
                <td class="value"><strong>{{ $ticket->odp?->name ?: '-' }}</strong> {{ $ticket->odp?->address ? '(' . $ticket->odp->address . ')' : '' }}</td>
            </tr>
            <tr>
                <td class="label">Area / Wilayah</td>
                <td class="value">{{ $ticket->area?->name ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Titik Lokasi / Alamat</td>
                <td class="value"><strong>{{ $ticket->location_name ?: '-' }}</strong></td>
            </tr>
            @if(!empty($ticket->incident_coordinates) && count($ticket->incident_coordinates) > 0)
            <tr>
                <td class="label">Titik Koordinat Insiden</td>
                <td class="value">
                    <table style="width:100%; border-collapse:collapse; margin-top:4px; font-size:11px;">
                        <thead>
                            <tr style="background:#f1f5f9; border-bottom:1px solid #cbd5e1;">
                                <th style="padding:4px 6px; text-align:left;">No</th>
                                <th style="padding:4px 6px; text-align:left;">Nama Titik / Label</th>
                                <th style="padding:4px 6px; text-align:left;">Koordinat (Lat, Lng)</th>
                                <th style="padding:4px 6px; text-align:left;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ticket->incident_coordinates as $cIdx => $coord)
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:4px 6px;">#{{ $cIdx + 1 }}</td>
                                <td style="padding:4px 6px; font-weight:bold;">{{ $coord['name'] ?? 'Titik ' . ($cIdx + 1) }}</td>
                                <td style="padding:4px 6px; font-family:monospace;">{{ $coord['latitude'] ?? '-' }}, {{ $coord['longitude'] ?? '-' }}</td>
                                <td style="padding:4px 6px; color:#555;">{{ $coord['notes'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
            @elseif($ticket->latitude && $ticket->longitude)
            <tr>
                <td class="label">Koordinat GPS Insiden</td>
                <td class="value" style="font-family:monospace;">{{ $ticket->latitude }}, {{ $ticket->longitude }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Dampak Pelanggan</td>
                <td class="value">
                    <strong style="color:#b91c1c; font-size:14px;">{{ $ticket->affected_customers_count }} Pelanggan</strong>
                    <span style="color:#666; font-size:11px;">(Estimasi perangkat ONT offline pada segmen ini)</span>
                </td>
            </tr>
        </table>

        <!-- 2. Temuan Kerusakan Jalur (Admin / NOC) -->
        <div class="section-title">2. Temuan Kerusakan Jalur (Admin / NOC)</div>
        <table class="info-grid">
            <tr>
                <td class="label">Kategori Kerusakan</td>
                <td class="value"><strong>{{ $ticket->category }}</strong></td>
            </tr>
            <tr>
                <td class="label">Sumber Laporan</td>
                <td class="value">{{ $ticket->source_report ?: 'Monitoring NMS' }} · Dilaporkan: {{ $ticket->created_at->format('d F Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Uraian Indikasi Masalah</td>
                <td class="value" style="color:#b91c1c; font-weight:600;">{{ $ticket->reported_issue }}</td>
            </tr>
            <tr>
                <td class="label">Tindakan Awal Admin</td>
                <td class="value">{{ $ticket->admin_notes ?: '-' }}</td>
            </tr>
            @if($ticket->escalation_reason)
            <tr>
                <td class="label">Alasan Eskalasi Lapangan</td>
                <td class="value" style="color:#c2410c; font-weight:bold;">{{ $ticket->escalation_reason }}</td>
            </tr>
            @endif
        </table>

        <!-- 3. Penjadwalan & Alokasi Material (Ka. Teknisi) -->
        <div class="section-title">3. Penjadwalan Teknisi & Alokasi Material Jalur (Ka. Teknisi)</div>
        <table class="info-grid">
            <tr>
                <td class="label">Jadwal Restorasi</td>
                <td class="value">
                    {{ $ticket->scheduled_date ? \Carbon\Carbon::parse($ticket->scheduled_date)->format('d F Y') : '-' }} 
                    ({{ $ticket->scheduled_time ?: 'Darurat / Segera' }})
                </td>
            </tr>
            <tr>
                <td class="label">Tim Teknisi Pelaksana</td>
                <td class="value">
                    <strong>{{ $ticket->technicians->pluck('name')->join(', ') ?: '-' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label">Instruksi Ka. Teknisi</td>
                <td class="value">{{ $ticket->lead_notes ?: '-' }}</td>
            </tr>
        </table>

        <!-- Tabel Material Disiapkan -->
        @if($ticket->materials->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th>Nama Material Jalur (FO / Closure / Aksesoris)</th>
                    <th style="width:100px; text-align:center;">Disiapkan</th>
                    <th style="width:100px; text-align:center;">Terpakai Aktual</th>
                    <th style="width:100px; text-align:center;">Sisa Kembali</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticket->materials as $idx => $mat)
                <tr>
                    <td style="text-align:center;">{{ $idx + 1 }}</td>
                    <td>{{ $mat->material?->name }}</td>
                    <td style="text-align:center;">{{ (float) $mat->quantity_prepared }} {{ $mat->unit ?: $mat->material?->unit }}</td>
                    <td style="text-align:center;">{{ (float) $mat->quantity_used }} {{ $mat->unit ?: $mat->material?->unit }}</td>
                    <td style="text-align:center;">{{ (float) $mat->quantity_returned }} {{ $mat->unit ?: $mat->material?->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- 4. Laporan Hasil Restorasi Lapangan (Teknisi) -->
        <div class="section-title">4. Laporan Hasil Restorasi Lapangan (Teknisi)</div>
        <table class="info-grid">
            <tr>
                <td class="label">Tindakan Restorasi Fisik</td>
                <td class="value"><strong>{{ $ticket->action_taken ?: 'Sedang dalam pengerjaan lapangan' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Detail Core FO / Tube</td>
                <td class="value"><strong>{{ $ticket->core_details ?: '-' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Redaman / Optical Loss</td>
                <td class="value">
                    Sebelum: <strong>{{ $ticket->optical_loss_before ?: '-' }}</strong> &nbsp;|&nbsp; 
                    Sesudah: <strong style="color:#059669;">{{ $ticket->optical_loss_after ?: '-' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label">Waktu Pengerjaan</td>
                <td class="value">Mulai: {{ $ticket->start_time ?: '-' }} &nbsp;|&nbsp; Selesai: {{ $ticket->end_time ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Hasil Akhir</td>
                <td class="value">{{ $ticket->resolution_notes ?: '-' }}</td>
            </tr>
        </table>

        <!-- Dokumentasi Foto Penanganan Lapangan -->
        @php
            $photos = $ticket->handling_photos ?? [];
        @endphp
        @if(!empty($photos) && count($photos) > 0)
        <div style="margin-top:15px; page-break-inside: avoid;">
            <div style="font-size:11px; font-weight:bold; text-transform:uppercase; color:#334155; margin-bottom:8px;">
                📸 Dokumentasi Foto Penanganan Lapangan ({{ count($photos) }} Foto):
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                @foreach($photos as $p)
                <div style="width: 31%; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; background: #fff; box-sizing: border-box; font-size: 11px;">
                    <div style="height: 120px; overflow: hidden; background: #f8fafc; text-align: center;">
                        <img src="{{ asset('storage/' . $p['path']) }}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="padding: 6px 8px;">
                        <div style="font-weight: bold; color: #1e293b;">{{ $p['caption'] ?? 'Foto Penanganan' }}</div>
                        @if(!empty($p['uploaded_at']))
                        <div style="font-size: 9px; color: #64748b; margin-top: 2px;">{{ $p['uploaded_at'] }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @elseif($ticket->photo_before || $ticket->photo_after)
        <div style="margin-top:15px; display: flex; gap: 15px; page-break-inside: avoid;">
            @if($ticket->photo_before)
            <div style="width: 48%; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; background: #fff;">
                <div style="height: 140px; overflow: hidden; background: #f8fafc;">
                    <img src="{{ asset('storage/' . $ticket->photo_before) }}" alt="Sebelum" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="padding: 6px 8px; font-weight: bold; font-size: 11px; color: #b91c1c;">Foto Kerusakan (Sebelum)</div>
            </div>
            @endif
            @if($ticket->photo_after)
            <div style="width: 48%; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; background: #fff;">
                <div style="height: 140px; overflow: hidden; background: #f8fafc;">
                    <img src="{{ asset('storage/' . $ticket->photo_after) }}" alt="Sesudah" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="padding: 6px 8px; font-weight: bold; font-size: 11px; color: #059669;">Hasil Restorasi (Sesudah)</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Tanda Tangan -->
        <div class="sign-area">
            <div class="sign-box">
                <div>Tim Teknisi Lapangan,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $ticket->technicians->first()?->name ?: '................................' }}</div>
            </div>
            <div class="sign-box">
                <div>Kepala Teknisi,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $ticket->leadTechnician?->name ?: '................................' }}</div>
            </div>
            <div class="sign-box">
                <div>Manajer Jaringan / NOC,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $ticket->creator?->name ?: '................................' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
