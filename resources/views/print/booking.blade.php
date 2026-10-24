<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Booking Pelanggan - {{ $customer->name }} ({{ $customer->customer_code }})</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1f2937;
            margin: 0;
            padding: 24px;
            background-color: #f3f4f6;
        }
        .sheet {
            max-width: 820px;
            margin: auto;
            padding: 35px 40px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 15px 20px;
                max-width: 100%;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        .action-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
        .btn-close {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        .btn-close:hover {
            background-color: #e5e7eb;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .company-info h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
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
            font-size: 17px;
            color: #2563eb;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title .doc-num {
            margin: 4px 0 0;
            font-size: 12px;
            color: #64748b;
            font-family: Consolas, monospace;
            font-weight: 700;
        }
        .statement-intro {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 10px 14px;
            font-size: 12px;
            color: #1e3a8a;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        .section-title {
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
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
        .badge-blue {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-amber {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-green {
            background-color: #dcfce7;
            color: #166534;
        }
        .terms {
            background: #fafafa;
            border: 1px dashed #cbd5e1;
            padding: 10px 14px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.5;
            border-radius: 6px;
            margin-top: 18px;
        }
        .terms ol {
            margin: 4px 0 0;
            padding-left: 18px;
        }
        .signatures {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            page-break-inside: avoid;
        }
        .sign-box {
            flex: 1;
            text-align: center;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 10px;
            background: #ffffff;
        }
        .sign-title {
            font-size: 11.5px;
            color: #475569;
            font-weight: 600;
            min-height: 32px;
        }
        .sign-space {
            height: 60px;
        }
        .sign-name {
            font-weight: 700;
            font-size: 12px;
            color: #0f172a;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            display: inline-block;
            min-width: 140px;
        }
        .sign-role {
            font-size: 10.5px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- ACTION BAR (TIDAK IKUT TERCETAK) -->
    <div class="action-bar no-print">
        <div>
            <strong style="color: #0f172a;">Formulir Pendaftaran & Booking PSB</strong>
            <span style="font-size: 12px; color: #64748b; margin-left: 8px;">No: {{ $customer->customer_code }}</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn-print">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Formulir (Print)
            </button>
            <a href="javascript:window.close()" class="btn-close">Tutup</a>
        </div>
    </div>

    <!-- LEMBAR CETAK (PRINT SHEET) -->
    <div class="sheet">
        <!-- HEADER KOP SURAT -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $company['name'] }}</h1>
                <p>{{ $company['address'] }}</p>
                <p>
                    @if(!empty($company['phone'])) Telp/WA: {{ $company['phone'] }} @endif
                    @if(!empty($company['email'])) &bull; Email: {{ $company['email'] }} @endif
                </p>
            </div>
            <div class="doc-title">
                <h2>FORMULIR BOOKING PSB</h2>
                <div class="doc-num">{{ $customer->customer_code }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    Tgl Daftar: {{ \Carbon\Carbon::parse($customer->registration_date ?? $customer->created_at)->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        <!-- INTRODUKSI -->
        <div class="statement-intro">
            <strong>Bukti Registrasi Pemasangan Baru (PSB):</strong> Formulir ini merupakan tanda terima resmi pendaftaran calon pelanggan internet. Data di bawah ini telah terdaftar dalam sistem dan siap diteruskan untuk verifikasi jalur, survey teknisi, serta penjadwalan instalasi perangkat.
        </div>

        <!-- 1. DATA CALON PELANGGAN -->
        <div class="section-title">
            <span>1. Data Calon Pelanggan</span>
        </div>
        <table class="grid-table">
            <tr>
                <td class="label">ID / Kode Pelanggan</td>
                <td class="val"><strong style="font-family: Consolas;">{{ $customer->customer_code }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nama Lengkap</td>
                <td class="val"><strong>{{ $customer->name }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nomor Telepon / WhatsApp</td>
                <td class="val">{{ $customer->phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Email Pelanggan</td>
                <td class="val">{{ $customer->email ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Lengkap Pemasangan</td>
                <td class="val">{{ $customer->address }}</td>
            </tr>
            <tr>
                <td class="label">Wilayah / Area Layanan</td>
                <td class="val">
                    <span class="badge badge-blue">
                        {{ $customer->areaModel?->name ?: ($customer->area ?: 'Belum Ada Area') }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">Titik Koordinat Lokasi (GPS)</td>
                <td class="val">
                    @if($customer->latitude && $customer->longitude)
                        <span style="font-family: Consolas;">{{ $customer->latitude }}, {{ $customer->longitude }}</span>
                    @else
                        <em>Belum ditentukan saat pendaftaran</em>
                    @endif
                </td>
            </tr>
        </table>

        <!-- 2. DETAIL PAKET & LAYANAN -->
        <div class="section-title">
            <span>2. Paket Layanan Yang Dipilih</span>
        </div>
        <table class="grid-table">
            <tr>
                <td class="label">Paket Internet</td>
                <td class="val">
                    @if($customer->service_status === 'free_home')
                        <strong>🏠 Free Home (Voucher Rumahan ONT)</strong>
                    @elseif($customer->service_status === 'gratis')
                        <strong>🎁 Layanan Gratis (Non-Invoice)</strong>
                    @else
                        <strong>{{ $customer->package?->name ?: 'Paket Internet' }}</strong>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Kecepatan Bandwidth</td>
                <td class="val">
                    {{ $customer->package?->speed_mbps ? $customer->package->speed_mbps . ' Mbps' : '-' }}
                </td>
            </tr>
            <tr>
                <td class="label">Biaya / Tarif Berlangganan</td>
                <td class="val">
                    @if(in_array($customer->service_status, ['gratis', 'free_home']))
                        <span class="badge badge-green">Rp 0 (Bebas Biaya)</span>
                    @else
                        <strong>Rp {{ number_format($customer->base_amount > 0 ? $customer->base_amount : ($customer->package?->price ?? 0), 0, ',', '.') }}</strong> / bulan
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Status Layanan</td>
                <td class="val">
                    <span class="badge {{ $customer->status === 'booking' ? 'badge-amber' : 'badge-blue' }}">
                        {{ strtoupper($customer->status === 'booking' ? 'Booking Baru' : ($customer->status === 'survey' ? 'Proses Survey' : $customer->status)) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">Metode Tagihan / Billing</td>
                <td class="val">{{ ucfirst($customer->billing_type ?: 'prabayar') }}</td>
            </tr>
        </table>

        <!-- 3. INFORMASI SALES & PETUGAS PENDAFTAR -->
        <div class="section-title">
            <span>3. Informasi Sales / Petugas Pendaftar</span>
        </div>
        <table class="grid-table">
            <tr>
                <td class="label">Nama Sales / Pendaftar</td>
                <td class="val">
                    <strong>{{ $customer->sales?->name ?: 'Direct Admin' }}</strong>
                    @if($customer->sales && ($customer->sales->job_position || $customer->sales->role))
                        <span style="font-size: 11px; color: #64748b; margin-left: 4px;">({{ $customer->sales->job_position ?: $customer->sales->role }})</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Kode Referral</td>
                <td class="val" style="font-family: Consolas;">{{ $customer->referral_code ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Tambahan</td>
                <td class="val">{{ $customer->notes ?: 'Tidak ada catatan khusus.' }}</td>
            </tr>
        </table>

        <!-- KETENTUAN LAYANAN -->
        <div class="terms">
            <strong>Ketentuan Pemasangan Baru (PSB):</strong>
            <ol>
                <li>Formulir ini berfungsi sebagai konfirmasi awal permohonan pemasangan layanan internet fiber optik.</li>
                <li>Pelaksanaan pemasangan bergantung pada hasil survey teknisi di lapangan terkait ketersediaan port ODP dan jarak kabel.</li>
                <li>Perangkat ONT/modem yang dipasang berstatus dipinjamkan selama pelanggan aktif berlangganan.</li>
            </ol>
        </div>

        <!-- TANDA TANGAN -->
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">
                    Calon Pelanggan
                </div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $customer->name }}</div>
                <div class="sign-role">Pelanggan</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Sales / Petugas Pendaftar
                </div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $customer->sales?->name ?: (auth()->user()->name ?? 'Petugas Sales') }}</div>
                <div class="sign-role">{{ $customer->sales?->job_position ?: ($customer->sales?->role ?: 'Sales / Marketing') }}</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Mengetahui,<br>{{ $company['name'] }}
                </div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ auth()->user()->name ?? 'Admin Operasional' }}</div>
                <div class="sign-role">Koordinator / Admin PSB</div>
            </div>
        </div>
    </div>

</body>
</html>
