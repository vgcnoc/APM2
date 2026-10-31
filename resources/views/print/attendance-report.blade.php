<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi & Kedisiplinan - {{ $filterPeriodLabel }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 15px;
            margin: 0 0 4px 0;
            color: #0f172a;
            font-weight: bold;
        }
        .meta {
            font-size: 9px;
            color: #475569;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            background: #f8fafc;
        }
        .card .label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .card .value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
        }
        th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-align: left;
        }
        .center { text-align: center; }
        .num { text-align: right; }
        .font-bold { font-weight: bold; }
        tr:nth-child(even) { background-color: #f8fafc; }
        tfoot td {
            background-color: #e2e8f0;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-amber { background: #fef3c7; color: #b45309; }
        .badge-rose  { background: #ffe4e6; color: #be123c; }
        .badge-blue  { background: #dbeafe; color: #1d4ed8; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    @endphp

    <div class="header">
        <div>
            <h1>Laporan Rekapitulasi Presensi Bulanan (01 s/d Akhir Bulan)</h1>
            <div class="meta">
                Periode: <strong>{{ $filterPeriodLabel }}</strong> &middot; Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB &middot; Total Karyawan: {{ count($employeeRecap) }} Orang
                @if(!empty($periodDetails))
                    &middot; Hari Kerja: <strong>{{ $periodDetails['total_work_days'] }} Hari</strong> (Berjalan: {{ $periodDetails['elapsed_work_days'] }} Hari)
                @endif
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
            <div class="label">Keterlambatan (Kali / Menit)</div>
            <div class="value" style="color: #ea580c;">{{ number_format($stats['total_late']) }}x / {{ number_format($stats['total_late_minutes']) }}m</div>
        </div>
        <div class="card">
            <div class="label">Alpha / Mangkir (Auto)</div>
            <div class="value" style="color: #dc2626;">{{ number_format($stats['total_alpha'] ?? 0) }} Hari</div>
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
                <th class="center" title="Hari Kerja Berjalan / Total">Hari Kerja</th>
                <th class="center">Hadir</th>
                <th class="center">Tepat</th>
                <th class="center">Telat</th>
                <th class="center">Alpha</th>
                <th class="center">Dinas</th>
                <th class="center">No Out</th>
                <th class="num">Kehadiran</th>
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
                    <td class="center">{{ $emp['elapsed_work_days'] ?? '-' }}/{{ $emp['total_work_days'] ?? '-' }}h</td>
                    <td class="center font-bold" style="color: #2563eb;">{{ $emp['total_present'] }}</td>
                    <td class="center" style="color: #16a34a; font-weight: bold;">{{ $emp['total_on_time'] }}</td>
                    <td class="center" style="color: #ea580c; font-weight: bold;">
                        {{ $emp['total_late'] > 0 ? $emp['total_late'] . 'x (' . $emp['total_late_minutes'] . 'm)' : '-' }}
                    </td>
                    <td class="center" style="{{ ($emp['total_alpha'] ?? 0) > 0 ? 'color: #dc2626; font-weight: bold;' : '' }}">
                        {{ ($emp['total_alpha'] ?? 0) > 0 ? $emp['total_alpha'] . 'h' : '-' }}
                    </td>
                    <td class="center">{{ $emp['total_field_duty'] ?: '-' }}</td>
                    <td class="center" style="{{ $emp['total_missing_out'] > 0 ? 'color: #dc2626; font-weight: bold;' : '' }}">{{ $emp['total_missing_out'] ?: '-' }}</td>
                    <td class="num">
                        <span class="badge {{ ($emp['attendance_rate'] ?? 0) >= 80 ? 'badge-blue' : 'badge-amber' }}">
                            {{ $emp['attendance_rate'] ?? 0 }}%
                        </span>
                    </td>
                    <td class="num">
                        <span class="badge {{ $emp['discipline_rate'] >= 80 ? 'badge-green' : 'badge-rose' }}">
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
                    <td colspan="16" style="text-align: center; padding: 15px; color: #64748b;">
                        Tidak ada catatan presensi pada periode dan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="center">TOTAL KESELURUHAN</td>
                <td class="center" style="color: #2563eb;">{{ $stats['total_present'] }}</td>
                <td class="center" style="color: #16a34a;">{{ $stats['total_on_time'] }}</td>
                <td class="center" style="color: #ea580c;">{{ $stats['total_late'] }}x ({{ $stats['total_late_minutes'] }}m)</td>
                <td class="center" style="color: #dc2626;">{{ $stats['total_alpha'] ?? 0 }}h</td>
                <td class="center">{{ $stats['total_field_duty'] }}</td>
                <td class="center">{{ $stats['total_missing_out'] }}</td>
                <td class="num">{{ $stats['avg_attendance_rate'] ?? 0 }}%</td>
                <td class="num">{{ $stats['avg_discipline_rate'] }}%</td>
                <td class="num" style="color: #dc2626;">{{ $rp($stats['total_penalty_amount']) }}</td>
                <td class="center">-</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 15px; display: flex; justify-content: space-between; font-size: 8px; color: #64748b;">
        <div>Keterangan: Perhitungan otomatis dari awal bulan (01) s/d akhir bulan. Alpha dihitung berdasarkan hari kerja tanpa absensi.</div>
        <div>Dicetak secara otomatis oleh VIZBILL System</div>
    </div>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
