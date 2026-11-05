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
            .kop-surat {
                margin-bottom: 16px;
            }
            .kop-header-content {
                gap: 14px;
                padding-bottom: 8px;
            }
            .kop-logo-img {
                max-height: 52px;
                max-width: 120px;
            }
            .kop-divider-v {
                background-color: #cbd5e1 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-company-title {
                color: #000000 !important;
            }
            .kop-subtitle {
                color: #2563eb !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-rule-thick {
                height: 2px !important;
                background-color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-rule-thin {
                height: 0.75px !important;
                background-color: #64748b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-icon {
                color: #2563eb !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        /* Kop Surat Modern, Simpel & Menarik */
        .kop-surat {
            margin-bottom: 18px;
        }
        .kop-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 10px;
        }
        .kop-logo-box {
            flex-shrink: 0;
            width: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kop-logo-img {
            max-height: 54px;
            max-width: 125px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
        .kop-divider-v {
            width: 1.5px;
            height: 48px;
            background-color: #cbd5e1;
            flex-shrink: 0;
        }
        .kop-info-box {
            flex: 1;
            min-width: 0;
        }
        .kop-company-title {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }
        .kop-subtitle {
            font-size: 9.5px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .kop-address {
            font-size: 11px;
            font-weight: 400;
            color: #475569;
            line-height: 1.4;
            margin-bottom: 5px;
        }
        .kop-contacts {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            font-size: 10.5px;
            font-weight: 500;
            color: #334155;
        }
        .kop-contact-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .kop-icon {
            width: 12px;
            height: 12px;
            color: #2563eb;
            flex-shrink: 0;
            vertical-align: middle;
        }
        .kop-contact-sep {
            color: #94a3b8;
            font-size: 11px;
            user-select: none;
        }
        .kop-rule {
            margin-top: 4px;
        }
        .kop-rule-thick {
            height: 2px;
            background-color: #0f172a;
            border-radius: 1px;
            margin-bottom: 2px;
        }
        .kop-rule-thin {
            height: 0.75px;
            background-color: #64748b;
        }
        .doc-title-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .doc-badge-pill {
            background-color: #e0e7ff;
            color: #3730a3;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 6px;
        }
        .doc-meta-row {
            font-family: Consolas, monospace;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
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

    @php
        $rawLogo = $company['logo'] ?? \App\Models\Setting::get('company_logo', null);
        $logoUrl = null;
        if (!empty($rawLogo)) {
            if (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://') || str_starts_with($rawLogo, 'data:')) {
                $logoUrl = $rawLogo;
            } elseif (str_starts_with($rawLogo, 'storage/') || str_starts_with($rawLogo, '/storage/')) {
                $logoUrl = asset(ltrim($rawLogo, '/'));
            } else {
                $logoUrl = asset('storage/' . ltrim($rawLogo, '/'));
            }
        }
        if (empty($logoUrl)) {
            $logoUrl = asset('images/company_logo.png');
        }

        $companyRawName = trim((string)($company['name'] ?? \App\Models\Setting::get('company_name', '')));
        if (empty($companyRawName) || strtoupper($companyRawName) === 'VIRUZS') {
            $companyTitle = 'PT VIRUZS GLOBAL CONNECTION';
        } else {
            $companyTitle = $companyRawName;
        }

        $companyAddress = trim((string)($company['address'] ?? \App\Models\Setting::get('company_address', 'Kp Cilandak RT 02 / RW 02 Desa Sirnajaya Kecamatan Warungkiara, Kab. Sukabumi 43362')));
        $companyPhone = trim((string)($company['phone'] ?? \App\Models\Setting::get('company_phone', '088976397034')));
        $companyEmail = trim((string)($company['email'] ?? \App\Models\Setting::get('company_email', 'helpdesk@viruzs.net')));
        $companyWeb = trim((string)($company['website'] ?? \App\Models\Setting::get('company_website', 'viruzs.co.id')));
    @endphp

    <!-- LEMBAR CETAK (PRINT SHEET) -->
    <div class="sheet">
        <!-- HEADER KOP SURAT SIMPEL & MENARIK -->
        <div class="kop-surat">
            <div class="kop-header-content">
                <div class="kop-logo-box">
                    <img 
                        src="{{ $logoUrl }}" 
                        alt="{{ $companyTitle }}" 
                        class="kop-logo-img"
                        onerror="this.onerror=null; this.src='{{ asset('images/company_logo.png') }}';"
                    >
                </div>
                <div class="kop-divider-v"></div>
                <div class="kop-info-box">
                    <h1 class="kop-company-title">{{ $companyTitle }}</h1>
                    <div class="kop-subtitle">PENYEDIA JASA INTERNET (ISP)</div>
                    @if(!empty($companyAddress))
                        <div class="kop-address">{{ $companyAddress }}</div>
                    @endif
                    <div class="kop-contacts">
                        @php $hasPrev = false; @endphp
                        @if(!empty($companyPhone))
                            <span class="kop-contact-item">
                                <svg class="kop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>{{ $companyPhone }}</span>
                            </span>
                            @php $hasPrev = true; @endphp
                        @endif

                        @if(!empty($companyEmail))
                            @if($hasPrev) <span class="kop-contact-sep">&bull;</span> @endif
                            <span class="kop-contact-item">
                                <svg class="kop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $companyEmail }}</span>
                            </span>
                            @php $hasPrev = true; @endphp
                        @endif

                        @if(!empty($companyWeb))
                            @if($hasPrev) <span class="kop-contact-sep">&bull;</span> @endif
                            <span class="kop-contact-item">
                                <svg class="kop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" />
                                </svg>
                                <span>{{ $companyWeb }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="kop-rule">
                <div class="kop-rule-thick"></div>
                <div class="kop-rule-thin"></div>
            </div>
        </div>

        <!-- JUDUL FORMULIR -->
        <div class="doc-title-container">
            <span class="doc-badge-pill">FORMULIR REGISTRASI &amp; BOOKING PSB</span>
            <div class="doc-meta-row">
                <span>KODE: <strong>{{ $customer->customer_code }}</strong></span> &bull; 
                <span>Tgl Registrasi: {{ \Carbon\Carbon::parse($customer->registration_date ?? $customer->created_at)->translatedFormat('d F Y') }}</span>
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
                        @php
                            $basePrice = (float) ($customer->base_amount > 0 ? $customer->base_amount : ($customer->package?->price ?? 0));
                            $taxPpn = (float) ($customer->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
                            $priceWithTax = $basePrice;
                            if ($taxPpn > 0) {
                                $raw = $basePrice * (1 + ($taxPpn / 100));
                                $rounded = (int) round($raw);
                                if (abs($rounded - round($rounded / 50) * 50) <= 1) {
                                    $priceWithTax = (int) (round($rounded / 50) * 50);
                                } else {
                                    $priceWithTax = $rounded;
                                }
                            }
                        @endphp
                        <strong>Rp {{ number_format($priceWithTax, 0, ',', '.') }}</strong> / bulan
                        @if($taxPpn > 0 && $priceWithTax > $basePrice)
                            <span style="font-size: 8pt; color: #64748b; font-weight: normal;">(Termasuk PPN {{ $taxPpn }}%)</span>
                        @endif
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
