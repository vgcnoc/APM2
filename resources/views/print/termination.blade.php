<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Berhenti Berlangganan - {{ $customer->name }} ({{ $customer->customer_code }})</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1f2937;
            margin: 0;
            padding: 20px;
            background-color: #f3f4f6;
        }
        .sheet {
            max-width: 800px;
            margin: auto;
            padding: 35px 40px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 20px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .company-info h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .company-info p {
            margin: 3px 0;
            color: #4b5563;
            font-size: 12px;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 18px;
            color: #dc2626;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title .doc-num {
            margin: 4px 0 0;
            font-size: 11px;
            color: #64748b;
            font-family: Consolas, monospace;
        }
        .statement-intro {
            background: #f8fafc;
            border-left: 4px solid #ef4444;
            padding: 10px 14px;
            font-size: 12px;
            color: #334155;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 20px 0 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .grid-table td {
            padding: 6px 10px;
            font-size: 12.5px;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }
        .grid-table td.label {
            width: 32%;
            font-weight: 600;
            color: #475569;
            background-color: #f8fafc;
        }
        .grid-table td.val {
            width: 68%;
            color: #0f172a;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-red {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-green {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-amber {
            background-color: #fef3c7;
            color: #92400e;
        }
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .inventory-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 11.5px;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
        }
        .inventory-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .checkbox-group {
            display: flex;
            gap: 15px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .box {
            width: 13px;
            height: 13px;
            border: 1.5px solid #475569;
            display: inline-block;
        }
        .box.checked {
            background-color: #0f172a;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 45%;
            text-align: center;
        }
        .sign-title {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 70px;
        }
        .sign-name {
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
            border-top: 1px solid #0f172a;
            display: inline-block;
            min-width: 200px;
            padding-top: 5px;
        }
        .sign-role {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 800px; margin: 0 auto 15px; display: flex; justify-content: space-between; align-items: center;">
        <a href="javascript:history.back()" style="color: #4b5563; text-decoration: none; font-size: 13px;">&larr; Kembali</a>
        <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="sheet">
        <!-- HEADER -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $company['name'] ?? 'ISP PROVIDER' }}</h1>
                <p>{{ $company['address'] ?? 'Layanan Akses Internet Cepat, Stabil & Terpercaya' }}</p>
                <p>Telp / WA: {{ $company['phone'] ?? '-' }} &bull; Email: {{ $company['email'] ?? '-' }}</p>
            </div>
            <div class="doc-title">
                <h2>Berita Acara</h2>
                <div style="font-size: 12px; font-weight: bold; color: #1e293b;">PENGAKHIRAN BERLANGGANAN</div>
                <div class="doc-num">No: BAB/{{ date('Ym') }}/{{ str_pad($customer->id, 5, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>

        <div class="statement-intro">
            Pada hari ini <strong>{{ \Carbon\Carbon::parse($customer->cancel_date ?? now())->translatedFormat('l, d F Y') }}</strong>, telah dilakukan pemutusan layanan dan/atau serah terima penarikan perangkat fasilitas internet oleh pihak penyedia layanan bersama pelanggan terkait rincian sebagai berikut:
        </div>

        <!-- IDENTITAS PELANGGAN -->
        <div class="section-title">1. Identitas Pelanggan</div>
        <table class="grid-table">
            <tr>
                <td class="label">ID / Kode Pelanggan</td>
                <td class="val"><strong style="font-family: Consolas, monospace; font-size: 13px;">{{ $customer->customer_code }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nama Lengkap</td>
                <td class="val"><strong>{{ $customer->name }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nomor WhatsApp / HP</td>
                <td class="val">{{ $customer->phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Pemasangan</td>
                <td class="val">{{ $customer->address ?: '-' }} (Area: {{ $customer->areaModel->name ?? '-' }})</td>
            </tr>
            <tr>
                <td class="label">Paket Langganan Sebelumnya</td>
                <td class="val">{{ $customer->package->name ?? '-' }} (Rp {{ number_format($customer->package->price ?? 0, 0, ',', '.') }}/bulan)</td>
            </tr>
            <tr>
                <td class="label">Tanggal Mulai Pasang</td>
                <td class="val">{{ $customer->installation_date ? \Carbon\Carbon::parse($customer->installation_date)->translatedFormat('d F Y') : '-' }}</td>
            </tr>
        </table>

        <!-- KETERANGAN BERHENTI -->
        <div class="section-title">2. Keterangan Penghentian Layanan</div>
        <table class="grid-table">
            <tr>
                <td class="label">Status Layanan</td>
                <td class="val"><span class="badge badge-red">STOP PERMANEN (TERMINATED)</span></td>
            </tr>
            <tr>
                <td class="label">Tanggal Efektif Berhenti</td>
                <td class="val"><strong>{{ $customer->cancel_date ? \Carbon\Carbon::parse($customer->cancel_date)->translatedFormat('d F Y') : \Carbon\Carbon::now()->translatedFormat('d F Y') }}</strong></td>
            </tr>
            <tr>
                <td class="label">Alasan Berhenti</td>
                <td class="val"><strong>{{ $customer->cancel_reason ?: 'Permintaan Pelanggan / Penutupan Layanan' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Catatan Tambahan</td>
                <td class="val">{{ $customer->notes ?: '-' }}</td>
            </tr>
        </table>

        <!-- STATUS PERANGKAT & PENARIKAN -->
        <div class="section-title">3. Penarikan Fasilitas & Perangkat (ONT / Modem / Router)</div>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Item Perangkat</th>
                    <th style="width: 35%;">Spesifikasi / Serial Number</th>
                    <th style="width: 20%;">Status Fisik</th>
                    <th style="width: 20%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Modem ONT / Router</strong></td>
                    <td>
                        @if($customer->ont)
                            SN: <strong style="font-family: Consolas;">{{ $customer->ont->serial_number }}</strong><br>
                            Model: {{ $customer->ont->model ?? 'ONT GPON/EPON' }}
                        @else
                            <em>Perangkat sudah ditarik / Tidak terdata</em>
                        @endif
                    </td>
                    <td>
                        @if($customer->ont)
                            <span class="badge badge-amber">Terpasang di Lokasi</span>
                        @else
                            <span class="badge badge-green">Sudah Ditarik</span>
                        @endif
                    </td>
                    <td>
                        [ &check; ] Unit ONT<br>
                        [ &check; ] Adaptor Power<br>
                        [ &check; ] Kabel Patchcord
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- KLAUSA PENUTUP -->
        <div style="font-size: 11.5px; color: #475569; line-height: 1.6; margin-top: 15px;">
            Dengan ditandatanganinya Berita Acara ini, pelanggan menyatakan setuju bahwa seluruh fasilitas layanan internet telah dihentikan, dan perangkat yang dipinjamkan telah/akan diserahterimakan kembali kepada pihak penyedia layanan dalam keadaan baik.
        </div>

        <!-- TANDA TANGAN -->
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">
                    Pihak Pelanggan / Yang Menyerahkan,<br>
                    Tanggal: {{ \Carbon\Carbon::parse($customer->cancel_date ?? now())->translatedFormat('d/m/Y') }}
                </div>
                <div class="sign-name">{{ $customer->name }}</div>
                <div class="sign-role">Pelanggan</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Petugas / Teknisi Yang Menerima,<br>
                    {{ $company['name'] ?? 'ISP Provider' }}
                </div>
                <div class="sign-name">{{ auth()->user()->name ?? 'Admin / Petugas Lapangan' }}</div>
                <div class="sign-role">Staff Operasional / Teknisi</div>
            </div>
        </div>
    </div>

</body>
</html>
