<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan & Buku Kas Umum - {{ $periodLabel }}</title>
    @php
        $rp = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    @endphp
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            background: #f8fafc;
            padding: 16px;
            line-height: 1.35;
        }
        .toolbar {
            max-width: 100%;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .btn {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-close { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .btn-close:hover { background: #e2e8f0; }

        .sheet {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-brand h1 {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .header-brand p {
            font-size: 10px;
            color: #64748b;
            margin-top: 1px;
        }
        .header-doc { text-align: right; }
        .header-doc .title {
            font-size: 14px;
            font-weight: 900;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-doc .period {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }
        .header-doc .meta {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            background: #f8fafc;
        }
        .card.card-blue { border-left: 4px solid #4f46e5; }
        .card.card-red { border-left: 4px solid #e11d48; }
        .card.card-green { border-left: 4px solid #10b981; }
        .card.card-amber { border-left: 4px solid #f59e0b; }
        .card .label {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .card .value {
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 2px;
        }
        .card .sub {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 4px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 3px;
            display: flex;
            justify-content: space-between;
        }

        .filter-banner {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 9.5px;
            color: #1e40af;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .filter-banner strong { font-weight: 700; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            vertical-align: middle;
        }
        th {
            background: #f1f5f9;
            text-align: left;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #334155;
            letter-spacing: 0.3px;
        }
        td.num, th.num { text-align: right; white-space: nowrap; font-family: ui-monospace, monospace; }
        td.center, th.center { text-align: center; white-space: nowrap; }
        .income-text { color: #047857; font-weight: 800; }
        .expense-text { color: #be123c; font-weight: 800; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-income { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-expense { background: #ffe4e6; color: #9f1239; border: 1px solid #fecdd3; }
        .badge-material { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }

        tfoot td {
            font-weight: 800;
            background: #f8fafc;
            border-top: 2px solid #0f172a;
        }

        .signatures {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
            page-break-inside: avoid;
        }
        .sign-box {
            padding: 8px 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #fafafa;
        }
        .sign-title { font-size: 9px; font-weight: 700; color: #475569; text-transform: uppercase; }
        .sign-space { height: 48px; }
        .sign-name { font-size: 10px; font-weight: 800; color: #0f172a; text-decoration: underline; }
        .sign-role { font-size: 8.5px; color: #64748b; margin-top: 1px; }

        @media print {
            .toolbar { display: none !important; }
            body { background: #fff; padding: 0; }
            .sheet { border: none; padding: 0; box-shadow: none; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { page-break-inside: avoid; }
            th, tfoot td, .badge, .card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <!-- Action Toolbar (Hidden when printed) -->
    <div class="toolbar">
        <div>
            <span style="font-weight: 800; color: #0f172a; font-size: 13px;">Pratinjau Cetak Laporan Keuangan</span>
            <span style="color: #64748b; margin-left: 8px; font-size: 11px;">(Format Resmi A4 Landscape)</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-print">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Dokumen
            </button>
            <button onclick="window.close()" class="btn btn-close">
                Tutup
            </button>
        </div>
    </div>

    <!-- Printable Sheet -->
    <div class="sheet">
        <!-- Header Kop -->
        <div class="header">
            <div class="header-brand">
                <h1>VIZBILL INTERNET & NETWORK</h1>
                <p>Master Laporan Keuangan & Rekapitulasi Buku Kas Umum</p>
            </div>
            <div class="header-doc">
                <div class="title">Buku Kas Umum</div>
                <div class="period">Periode: {{ $periodLabel }}</div>
                <div class="meta">Dicetak oleh: <strong>{{ $printedBy }}</strong> &bull; {{ $printDate }}</div>
            </div>
        </div>

        <!-- 4 Grand Executive Summary Cards -->
        <div class="summary-grid">
            <!-- Pemasukan -->
            <div class="card card-blue">
                <div class="label">Total Pemasukan Kas (Omzet)</div>
                <div class="value">{{ $rp($summary['total_omzet']) }}</div>
                <div class="sub">
                    <span>Pelanggan: {{ $rp($summary['omzet_customer']) }}</span>
                    <span>Reseller: {{ $rp($summary['omzet_reseller']) }}</span>
                </div>
            </div>

            <!-- Pengeluaran -->
            <div class="card card-red">
                <div class="label">Total Pengeluaran Kas</div>
                <div class="value">{{ $rp($summary['total_pengeluaran']) }}</div>
                <div class="sub">
                    <span>Belanja Material: {{ $rp($summary['pengeluaran_material']) }}</span>
                    <span>Operasional: {{ $rp($summary['pengeluaran_operasional']) }}</span>
                </div>
            </div>

            <!-- Laba Bersih -->
            <div class="card card-green">
                <div class="label">Laba Bersih Perusahaan</div>
                <div class="value">{{ $rp($summary['laba_bersih']) }}</div>
                <div class="sub">
                    <span>(Omzet - Total Pengeluaran)</span>
                    <span style="font-weight: bold; color: #047857;">Surplus</span>
                </div>
            </div>

            <!-- Piutang -->
            <div class="card card-amber">
                <div class="label">Total Piutang Berjalan</div>
                <div class="value">{{ $rp($summary['total_piutang']) }}</div>
                <div class="sub">
                    <span>Tunggakan Aktif: {{ $rp($summary['piutang_customer']) }}</span>
                    <span>Kasbon: {{ $rp($summary['piutang_reseller']) }}</span>
                </div>
            </div>
        </div>

        <!-- Active Filter Info (If filtered) -->
        @if(!empty($filters['search']) || ($filters['type'] && $filters['type'] !== 'all') || ($filters['category'] && $filters['category'] !== 'all') || !empty($filters['date_from']))
        <div class="filter-banner">
            <div>
                <strong>Filter Aktif:</strong>
                @if(!empty($filters['search'])) [Cari: "{{ $filters['search'] }}"] @endif
                @if($filters['type'] && $filters['type'] !== 'all') [Tipe: {{ $filters['type'] === 'income' ? 'Pemasukan Kas' : 'Pengeluaran Kas' }}] @endif
                @if($filters['category'] && $filters['category'] !== 'all') [Kategori: {{ $filters['category'] === 'material' ? 'Belanja Material (Order Toko)' : ($filters['category'] === 'customer' ? 'Pelanggan Reguler' : ($filters['category'] === 'reseller' ? 'Reseller Voucher' : $filters['category'])) }}] @endif
                @if(!empty($filters['date_from']) || !empty($filters['date_to'])) [Tgl: {{ $filters['date_from'] ?: '...' }} s/d {{ $filters['date_to'] ?: '...' }}] @endif
            </div>
            <div>
                Menampilkan <strong>{{ $filteredTotals['count'] }}</strong> data &bull; 
                Masuk: <strong style="color:#047857;">+{{ $rp($filteredTotals['income']) }}</strong> &bull; 
                Keluar: <strong style="color:#be123c;">-{{ $rp($filteredTotals['expense']) }}</strong> &bull; 
                Net: <strong>{{ $rp($filteredTotals['net']) }}</strong>
            </div>
        </div>
        @endif

        <!-- General Ledger Table -->
        <table>
            <thead>
                <tr>
                    <th class="center" style="width: 28px;">No</th>
                    <th style="width: 110px;">Tanggal & Waktu</th>
                    <th style="width: 140px;">Tipe / Kategori</th>
                    <th>Keterangan Transaksi & Petugas</th>
                    <th class="num" style="width: 110px;">Pemasukan (+)</th>
                    <th class="num" style="width: 110px;">Pengeluaran (-)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledger as $idx => $item)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($item['date'])->translatedFormat('d M Y, H:i') }}</td>
                    <td>
                        @if($item['type'] === 'income')
                            <span class="badge badge-income">Pemasukan ({{ $item['category_name'] }})</span>
                        @elseif($item['category_code'] === 'MAT' || !empty($item['po_number']))
                            <span class="badge badge-material">Belanja Material</span>
                        @else
                            <span class="badge badge-expense">{{ $item['category_name'] }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">{{ $item['description'] }}</div>
                        <div style="font-size: 8.5px; color: #64748b; margin-top: 1px;">
                            Petugas: <strong>{{ $item['user'] }}</strong>
                            @if(!empty($item['person']) && $item['person'] !== '-') &bull; Terkait: {{ $item['person'] }} @endif
                            @if(!empty($item['po_number'])) &bull; Ref PO: <strong>{{ $item['po_number'] }}</strong> @endif
                        </div>
                    </td>
                    <td class="num income-text">
                        @if($item['type'] === 'income')
                            +{{ $rp($item['amount']) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="num expense-text">
                        @if($item['type'] === 'expense')
                            -{{ $rp($item['amount']) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="center" style="padding: 24px; color: #64748b;">
                        Tidak ada transaksi kas pada periode atau kriteria filter yang dipilih.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; text-transform: uppercase;">Total Transaksi Tertera:</td>
                    <td class="num income-text">+{{ $rp($filteredTotals['income']) }}</td>
                    <td class="num expense-text">-{{ $rp($filteredTotals['expense']) }}</td>
                </tr>
                <tr style="background: #f1f5f9;">
                    <td colspan="4" style="text-align: right; text-transform: uppercase; font-size: 10px;">Saldo Kas Bersih (Net Cash Flow):</td>
                    <td colspan="2" class="num" style="font-size: 11px; color: {{ $filteredTotals['net'] >= 0 ? '#047857' : '#be123c' }};">
                        {{ $rp($filteredTotals['net']) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Signature Block -->
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">Dibuat / Dicatat Oleh:</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $printedBy }}</div>
                <div class="sign-role">Kasir / Staff Administrasi</div>
            </div>
            <div class="sign-box">
                <div class="sign-title">Diperiksa Oleh:</div>
                <div class="sign-space"></div>
                <div class="sign-name">Finance & Accounting</div>
                <div class="sign-role">Supervisor Keuangan</div>
            </div>
            <div class="sign-box">
                <div class="sign-title">Mengetahui & Menyetujui:</div>
                <div class="sign-space"></div>
                <div class="sign-name">Pimpinan Perusahaan</div>
                <div class="sign-role">Direktur Utama</div>
            </div>
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
