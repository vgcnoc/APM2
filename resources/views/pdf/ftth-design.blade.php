<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perancangan FTTH - {{ $design->name }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1e3a8a; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
        .section { margin-bottom: 30px; }
        .section h2 { font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; color: #0f172a; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge.approved { background-color: #dcfce7; color: #166534; }
        .badge.draft { background-color: #f1f5f9; color: #475569; }
        .badge.review { background-color: #fef9c3; color: #854d0e; }
        .text-right { text-align: right; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; font-size: 10px; color: #94a3b8; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="footer">
        Dicetak otomatis oleh Sistem Manajemen ISP &copy; {{ date('Y') }}
    </div>

    <div class="header">
        <h1>Dokumen Perancangan Jaringan FTTH</h1>
        <p>Area: {{ $design->area ? $design->area->name : '-' }} | PIC: {{ $design->pic ?: '-' }}</p>
    </div>

    <div class="section">
        <h2>1. Informasi Proyek</h2>
        <table>
            <tr>
                <th width="30%">Nama Perancangan</th>
                <td>{{ $design->name }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge {{ $design->status }}">{{ $design->status }}</span>
                </td>
            </tr>
            <tr>
                <th>Total Jarak Kabel (Rencana)</th>
                <td>{{ number_format($design->total_distance, 2) }} meter (Toleransi Slack: {{ $design->slack_percentage }}%)</td>
            </tr>
            <tr>
                <th>Tanggal Dibuat</th>
                <td>{{ $design->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Deskripsi</th>
                <td>{{ $design->description ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>2. Ringkasan Perangkat Aktif & Pasif (Rencana)</h2>
        <table>
            <thead>
                <tr>
                    <th width="50%">Jenis Perangkat</th>
                    <th width="50%">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tiang (Baru)</td>
                    <td>{{ $devices->where('device_type', 'tiang')->count() }} Titik</td>
                </tr>
                <tr>
                    <td>ODC (Baru)</td>
                    <td>{{ $devices->where('device_type', 'odc')->count() }} Unit</td>
                </tr>
                <tr>
                    <td>ODP (Baru)</td>
                    <td>{{ $devices->where('device_type', 'odp')->count() }} Unit</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>3. Kebutuhan Material (Bill of Quantities)</h2>
        @if($design->materials && $design->materials->count() > 0)
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="45%">Nama Material</th>
                    <th width="20%">Kategori</th>
                    <th width="15%" class="text-right">Kuantitas</th>
                    <th width="15%">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($design->materials as $idx => $m)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $m->material ? $m->material->name : 'Unknown Material' }}</td>
                    <td>{{ $m->material ? $m->material->category : '-' }}</td>
                    <td class="text-right">{{ $m->quantity }}</td>
                    <td>{{ $m->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #ef4444; font-style: italic;">Material belum dihitung atau diajukan untuk review.</p>
        @endif
    </div>

    <div class="page-break"></div>

    <div class="header">
        <h1>Rincian Teknis & Jalur Kabel</h1>
    </div>

    <div class="section">
        <h2>Daftar Jalur Kabel (Rute)</h2>
        @if($routes && $routes->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tipe Rute</th>
                    <th>Titik Mulai</th>
                    <th>Titik Akhir</th>
                    <th class="text-right">Jarak (m)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routes as $idx => $r)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ ucfirst($r->route_type) }}</td>
                    <td>{{ strtoupper($r->start_type) }} #{{ $r->start_id }}</td>
                    <td>{{ strtoupper($r->end_type) }} #{{ $r->end_id }}</td>
                    <td class="text-right">{{ number_format($r->distance, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>Belum ada rute kabel yang digambar.</p>
        @endif
    </div>

    <div style="margin-top: 50px;">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: center; width: 33%;">
                    <p>Dibuat Oleh,</p>
                    <br><br><br>
                    <p><strong>_____________________</strong><br>Engineering / Designer</p>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <p>Disetujui Oleh,</p>
                    <br><br><br>
                    <p><strong>_____________________</strong><br>NOC / Manager</p>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
