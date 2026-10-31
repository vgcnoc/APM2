<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Karyawan - {{ $filterPeriodLabel }}</title>
    @php
        $rp = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px; color: #1e293b; margin: 15px; }
        h1 { font-size: 16px; margin: 0 0 4px; text-transform: uppercase; font-weight: 800; color: #0f172a; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 12px; }
        .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 14px; }
        .card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; background: #f8fafc; }
        .card .label { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .card .value { font-size: 13px; font-weight: 800; margin-top: 2px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 4.5px 6px; vertical-align: middle; }
        th { background: #f1f5f9; text-align: left; font-size: 9px; text-transform: uppercase; font-weight: 700; color: #334155; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        td.center, th.center { text-align: center; }
        tfoot td { font-weight: 800; background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 4px; font-size: 8.5px; font-weight: 700; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .toolbar { margin-bottom: 12px; display: flex; gap: 8px; }
        .toolbar button { padding: 6px 14px; cursor: pointer; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600; font-size: 11px; }
        .toolbar button.primary { background: #2563eb; color: #fff; border-color: #1d4ed8; }
        .signatures { margin-top: 35px; display: flex; justify-content: space-between; page-break-inside: avoid; }
        .sign-box { width: 200px; text-align: center; font-size: 10px; }
        .sign-space { height: 50px; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            th, tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @page { size: A4 landscape; margin: 10mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="primary" onclick="window.print()">Cetak Dokumen</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 10px;">
        <div>
            <h1>Laporan Rekapitulasi Presensi & Kedisiplinan Karyawan</h1>
            <div class="meta">
                Periode: <strong>{{ $filterPeriodLabel }}</strong> &middot; Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB &middot; Total Karyawan: {{ count($employeeRecap) }} Orang
            </div>
        </div>
        <div style="text-align: right; font-size: 9px; color: #64748b;">
            <strong style="font-size: 11px; color: #0f172a;">VIZBILL ISP MANAGEMENT</strong><br>
            Sistem Informasi Operasional & Presensi Terpadu
        </div>
    </div>

    <div class="summary-grid">
        <div class="card">
            <div class="label">Total Log Presensi</div>
            <div class="value">{{ number_format($stats['total_logs']) }} Hari</div>
        </div>
        <div class="card">
            <div class="label">Hadir Tepat Waktu</div>
            <div class="value" style="color: #16a34a;">{{ number_format($stats['total_on_time']) }} Hari</div>
        </div>
        <div class="card">
            <div class="label">Terlambat (Kali / Menit)</div>
            <div class="value" style="color: #ea580c;">{{ number_format($stats['total_late']) }}x / {{ number_format($stats['total_late_minutes']) }}m</div>
        </div>
        <div class="card">
            <div class="label">Rata-rata Disiplin</div>
            <div class="value" style="color: #2563eb;">{{ $stats['avg_discipline_rate'] }}%</div>
        </div>
        <div class="card">
            <div class="label">Estimasi Potongan Sanksi</div>
            <div class="value" style="color: #dc2626;">{{ $rp($stats['total_penalty_amount']) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;" class="center">No</th>
                <th>Nama Karyawan</th>
                <th>IAK / NIK</th>
                <th>Jabatan / Divisi</th>
                <th>Cabang / Wilayah</th>
                <th class="center">Hadir</th>
                <th class="center">Tepat Waktu</th>
                <th class="center">Terlambat</th>
                <th class="center">Total Menit</th>
                <th class="center">Dinas Luar</th>
                <th class="center">Tanpa Out</th>
                <th class="num">Disiplin</th>
                <th class="num">Est. Potongan</th>
                <th class="center">Status Payroll</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employeeRecap as $i => $emp)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td><strong>{{ $emp['name'] }}</strong></td>
                    <td>{{ $emp['iak_number'] ?: '-' }}</td>
                    <td>{{ $emp['position'] ?: '-' }}</td>
                    <td>{{ $emp['area_name'] }}</td>
                    <td class="center font-bold">{{ $emp['total_present'] }}</td>
                    <td class="center" style="color: #16a34a; font-weight: bold;">{{ $emp['total_on_time'] }}</td>
                    <td class="center" style="color: #ea580c; font-weight: bold;">{{ $emp['total_late'] }}</td>
                    <td class="center">{{ $emp['total_late_minutes'] > 0 ? $emp['total_late_minutes'] . 'm' : '-' }}</td>
                    <td class="center">{{ $emp['total_field_duty'] ?: '-' }}</td>
                    <td class="center" style="{{ $emp['total_missing_out'] > 0 ? 'color: #dc2626; font-weight: bold;' : '' }}">{{ $emp['total_missing_out'] ?: '-' }}</td>
                    <td class="num">
                        <span class="badge {{ $emp['discipline_rate'] >= 85 ? 'badge-green' : 'badge-amber' }}">
                            {{ $emp['discipline_rate'] }}%
                        </span>
                    </td>
                    <td class="num font-bold">{{ $emp['estimated_penalty'] > 0 ? $rp($emp['estimated_penalty']) : 'Rp 0' }}</td>
                    <td class="center">
                        <span class="badge {{ $emp['is_all_synced'] ? 'badge-green' : 'badge-amber' }}">
                            {{ $emp['is_all_synced'] ? 'Tersinkron' : 'Pending' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align: center; padding: 15px; color: #64748b;">
                        Tidak ada catatan presensi pada periode dan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="center">TOTAL KESELURUHAN</td>
                <td class="center">{{ $stats['total_present'] }}</td>
                <td class="center" style="color: #16a34a;">{{ $stats['total_on_time'] }}</td>
                <td class="center" style="color: #ea580c;">{{ $stats['total_late'] }}</td>
                <td class="center">{{ $stats['total_late_minutes'] }}m</td>
                <td class="center">{{ $stats['total_field_duty'] }}</td>
                <td class="center">{{ $stats['total_missing_out'] }}</td>
                <td class="num">{{ $stats['avg_discipline_rate'] }}%</td>
                <td class="num" style="color: #dc2626;">{{ $rp($stats['total_penalty_amount']) }}</td>
                <td class="center">-</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sign-box">
            <div>Dibuat Oleh,</div>
            <div class="sign-space"></div>
            <div><strong>Staff Administrasi / HRD</strong></div>
        </div>
        <div class="sign-box">
            <div>Diperiksa Oleh,</div>
            <div class="sign-space"></div>
            <div><strong>Manager Operasional</strong></div>
        </div>
        <div class="sign-box">
            <div>Mengetahui,</div>
            <div class="sign-space"></div>
            <div><strong>Direktur / Pimpinan</strong></div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>

