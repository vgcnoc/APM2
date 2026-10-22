<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK & Berita Acara PGK #{{ $ticket->ticket_number }}</title>
    @php
        $companyName = \App\Models\Setting::get('company_name', config('app.name', 'ISP Provider'));
        $companyAddress = \App\Models\Setting::get('company_address', '');
        $companyPhone = \App\Models\Setting::get('company_phone', '');
        $companyLogo = \App\Models\Setting::get('company_logo', '');
    @endphp
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.5; color: #222; margin: 0; padding: 20px; }
        .box { max-width: 800px; margin: auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0284c7; padding-bottom: 15px; margin-bottom: 20px; }
        .brand h1 { margin: 0; font-size: 20px; color: #0369a1; text-transform: uppercase; }
        .brand p { margin: 2px 0; color: #555; font-size: 12px; }
        .title-section { text-align: right; }
        .title-section h2 { margin: 0; font-size: 18px; color: #ea580c; font-weight: 900; }
        .title-section .ticket-no { font-family: monospace; font-size: 15px; font-weight: bold; color: #0284c7; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-critical { background: #fee2e2; color: #b91c1c; }
        .badge-high { background: #ffedd5; color: #c2410c; }
        .badge-medium { background: #e0e7ff; color: #3730a3; }
        .badge-low { background: #f3f4f6; color: #4b5563; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #0369a1; background: #f0f9ff; padding: 5px 8px; border-left: 3px solid #0284c7; margin: 15px 0 8px 0; }
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
        .btn-print { background: #0284c7; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        @media print {
            body { padding: 0; }
            .box { border: none; box-shadow: none; max-width: 100%; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak SPK & Berita Acara PGK</button>
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
                @if($companyPhone) <p>Telp / CS: {{ $companyPhone }}</p> @endif
            </div>
            <div class="title-section">
                <h2>SURAT PERINTAH KERJA (SPK)</h2>
                <div style="font-size:12px; font-weight:bold; color:#4b5563;">PENANGANAN GANGGUAN KOMPLAIN (PGK)</div>
                <div class="ticket-no">{{ $ticket->ticket_number }}</div>
                <div style="margin-top:4px;">
                    <span class="badge badge-{{ $ticket->priority }}">{{ $ticket->priority }} PRIORITY</span>
                </div>
            </div>
        </div>

        <!-- 1. Data Pelanggan & Pelapor -->
        <div class="section-title">1. Data Pelanggan & Pelapor Komplain</div>
        <table class="info-grid">
            <tr>
                <td class="label">Nama Pelanggan</td>
                <td class="value"><strong>{{ $ticket->customer?->name ?: $ticket->complainant_name }}</strong> (ID: {{ $ticket->customer?->customer_code ?: '-' }})</td>
            </tr>
            <tr>
                <td class="label">Nama Pelapor</td>
                <td class="value">
                    <strong>{{ $ticket->complainant_name }}</strong>
                    @if($ticket->complainant_phone)
                        (Telp/WA: {{ $ticket->complainant_phone }})
                    @endif
                    · Saluran: <em>{{ strtoupper($ticket->channel ?: 'WhatsApp') }}</em>
                </td>
            </tr>
            <tr>
                <td class="label">No. Telepon Terdaftar</td>
                <td class="value">{{ $ticket->customer?->phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Pelanggan</td>
                <td class="value">{{ $ticket->customer?->address ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Paket / Layanan</td>
                <td class="value">{{ $ticket->customer?->package?->name ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Area / ODP</td>
                <td class="value">
                    <strong>{{ $ticket->area?->name ?: ($ticket->customer?->areaModel?->name ?: '-') }}</strong> · 
                    ODP: {{ $ticket->customer?->ont?->odp?->name ?: '-' }} · 
                    SN ONT: {{ $ticket->customer?->ont?->serial_number ?: '-' }}
                </td>
            </tr>
        </table>

        <!-- 2. Rincian Aduan / Komplain (Penerimaan Admin) -->
        <div class="section-title">2. Rincian Komplain Masuk (Penerimaan Admin)</div>
        <table class="info-grid">
            <tr>
                <td class="label">Waktu Komplain</td>
                <td class="value">{{ $ticket->reported_at ? \Carbon\Carbon::parse($ticket->reported_at)->format('d F Y H:i') : $ticket->created_at->format('d F Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Kategori Gangguan</td>
                <td class="value"><strong>{{ $ticket->category }}</strong></td>
            </tr>
            <tr>
                <td class="label">Uraian Masalah / Keluhan</td>
                <td class="value" style="color:#b91c1c; font-weight:600;">{{ $ticket->reported_issue }}</td>
            </tr>
            <tr>
                <td class="label">Tindakan Jarak Jauh Admin</td>
                <td class="value">{{ $ticket->admin_notes ?: '-' }}</td>
            </tr>
            @if($ticket->escalation_reason)
            <tr>
                <td class="label">Alasan Eskalasi Lapangan</td>
                <td class="value" style="color:#c2410c; font-weight:bold;">{{ $ticket->escalation_reason }}</td>
            </tr>
            @endif
        </table>

        <!-- 3. Penjadwalan & Material (Kepala Teknisi) -->
        <div class="section-title">3. Penjadwalan Teknisi & Alokasi Material (Kepala Teknisi)</div>
        <table class="info-grid">
            <tr>
                <td class="label">Jadwal Penanganan</td>
                <td class="value">
                    {{ $ticket->scheduled_date ? \Carbon\Carbon::parse($ticket->scheduled_date)->format('d F Y') : '-' }} 
                    ({{ $ticket->scheduled_time ?: 'Fleksibel' }})
                </td>
            </tr>
            <tr>
                <td class="label">Teknisi Ditugaskan</td>
                <td class="value">
                    <strong>{{ $ticket->technicians->pluck('name')->join(', ') ?: '-' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label">Instruksi Kepala Teknisi</td>
                <td class="value">{{ $ticket->lead_notes ?: '-' }}</td>
            </tr>
        </table>

        <!-- Tabel Material Disiapkan -->
        @if($ticket->materials->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th>Nama Material</th>
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

        <!-- 4. Laporan Hasil Penanganan (Teknisi) -->
        <div class="section-title">4. Laporan Hasil Penanganan Lapangan (Teknisi)</div>
        <table class="info-grid">
            <tr>
                <td class="label">Tindakan Perbaikan</td>
                <td class="value"><strong>{{ $ticket->action_taken ?: 'Sedang dalam pengerjaan' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Redaman Optik (dBm)</td>
                <td class="value">
                    Sebelum: <strong>{{ $ticket->optical_power_before ?: '-' }}</strong> &nbsp;|&nbsp; 
                    Sesudah: <strong style="color:#059669;">{{ $ticket->optical_power_after ?: '-' }}</strong>
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

        <!-- Titik Koordinat (Tikor) Insiden -->
        @if(!empty($ticket->incident_coordinates) && count($ticket->incident_coordinates) > 0)
        <div class="section-title">Titik Koordinat Insiden / Penanganan (Multi-Tikor)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th style="width:160px;">Label Titik</th>
                    <th style="width:180px;">Koordinat (Lat, Lng)</th>
                    <th>Keterangan Lokasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticket->incident_coordinates as $tIdx => $tikor)
                <tr>
                    <td style="text-align:center;">{{ $tIdx + 1 }}</td>
                    <td><strong>{{ $tikor['label'] ?? ('Titik #' . ($tIdx + 1)) }}</strong></td>
                    <td style="font-family:monospace; font-size:11px;">{{ $tikor['latitude'] ?? '-' }}, {{ $tikor['longitude'] ?? '-' }}</td>
                    <td>{{ $tikor['description'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Material Lama Dicabut / Diganti (Dismantled) -->
        @if($ticket->dismantledMaterials && $ticket->dismantledMaterials->count() > 0)
        <div class="section-title">Material Pelanggan Dicabut / Diganti (Serah Terima Gudang)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th>Nama Material</th>
                    <th style="width:90px; text-align:center;">Jenis Ganti</th>
                    <th style="width:80px; text-align:center;">Jumlah</th>
                    <th style="width:100px; text-align:center;">Kondisi Fisik</th>
                    <th>Alasan Kerusakan / Status Gudang</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticket->dismantledMaterials as $dIdx => $dm)
                <tr>
                    <td style="text-align:center;">{{ $dIdx + 1 }}</td>
                    <td><strong>{{ $dm->material?->name ?: $dm->item_name }}</strong></td>
                    <td style="text-align:center;">
                        <span class="badge {{ $dm->replacement_type === 'full' ? 'badge-high' : 'badge-medium' }}">
                            {{ $dm->replacement_type === 'full' ? 'Total (100%)' : 'Sebagian' }}
                        </span>
                    </td>
                    <td style="text-align:center;">{{ (float) $dm->quantity }} {{ $dm->unit }}</td>
                    <td style="text-align:center;">
                        <span class="badge {{ $dm->condition === 'rusak_scrap' ? 'badge-critical' : 'badge-low' }}">
                            {{ $dm->condition === 'rusak_scrap' ? 'Rusak / Afkir' : ($dm->condition === 'normal_reusable' ? 'Layak Pakai' : 'Perlu Cek') }}
                        </span>
                    </td>
                    <td>
                        {{ $dm->damage_reason ?: '-' }}
                        <div style="font-size:10px; color:#555; margin-top:2px;">
                            Serah Terima: <strong>{{ $dm->handover_status === 'received' ? 'Diterima Gudang (' . ucfirst($dm->disposition) . ')' : 'Menunggu Serah Terima' }}</strong>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Tanda Tangan -->
        <div class="sign-area">
            <div class="sign-box">
                <div>Pelanggan / Pelapor,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $ticket->complainant_name ?: $ticket->customer?->name }}</div>
            </div>
            <div class="sign-box">
                <div>Teknisi Pelaksana,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $ticket->technicians->first()?->name ?: '................................' }}</div>
            </div>
            <div class="sign-box">
                <div>Kepala Teknisi / Admin,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $ticket->leadTechnician?->name ?: $ticket->creator?->name ?: '................................' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
