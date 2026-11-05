<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $agreement->title }} - {{ $agreement->customer_name }} ({{ $agreement->agreement_number }})</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 24px;
            background-color: #f1f5f9;
        }
        .sheet {
            max-width: 840px;
            margin: auto;
            padding: 40px 45px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 10px 15px;
                max-width: 100%;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
            .kop-surat {
                margin-bottom: 20px;
            }
            .kop-header-content {
                gap: 14px;
                padding-bottom: 10px;
            }
            .kop-logo-img {
                max-height: 68px;
                max-width: 125px;
            }
            .kop-divider-line {
                background-color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-company-title {
                color: #000000 !important;
            }
            .kop-subtitle {
                color: #1e40af !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-rule-thick {
                height: 3.5px;
                background-color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-rule-thin {
                height: 1px;
                background-color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .kop-icon {
                color: #1e40af !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        .action-bar {
            max-width: 840px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .btn-print {
            background-color: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.15s;
        }
        .btn-print:hover {
            background-color: #4338ca;
        }
        .btn-close {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        .btn-close:hover {
            background-color: #f1f5f9;
        }
        /* Kop Surat Resmi & Elegan */
        .kop-surat {
            margin-bottom: 24px;
        }
        .kop-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 12px;
        }
        .kop-logo-box {
            flex-shrink: 0;
            width: 135px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kop-logo-img {
            max-height: 72px;
            max-width: 130px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
        .kop-divider-double {
            display: flex;
            gap: 3.5px;
            height: 70px;
            align-items: stretch;
            flex-shrink: 0;
        }
        .kop-divider-line {
            width: 1.5px;
            background-color: #0f172a;
        }
        .kop-info-box {
            flex: 1;
            min-width: 0;
        }
        .kop-company-title {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin: 0 0 2px 0;
            line-height: 1.15;
        }
        .kop-subtitle {
            font-size: 10px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .kop-address {
            font-size: 11.5px;
            font-weight: 500;
            color: #334155;
            line-height: 1.45;
            margin-bottom: 6px;
        }
        .kop-contacts {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            font-weight: 600;
            color: #1e293b;
        }
        .kop-contact-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .kop-icon {
            width: 13px;
            height: 13px;
            color: #2563eb;
            flex-shrink: 0;
            vertical-align: middle;
        }
        .kop-contact-sep {
            color: #cbd5e1;
            font-size: 12px;
        }
        .kop-rule {
            margin-top: 6px;
        }
        .kop-rule-thick {
            height: 3.5px;
            background-color: #0f172a;
            margin-bottom: 2px;
        }
        .kop-rule-thin {
            height: 1px;
            background-color: #0f172a;
        }
        .doc-badge {
            background-color: #e0e7ff;
            color: #3730a3;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        .doc-title-box {
            text-align: center;
            margin-bottom: 24px;
        }
        .doc-title-box h2 {
            margin: 8px 0 4px 0;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .doc-number {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .info-table td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .info-label {
            width: 18%;
            color: #64748b;
            font-weight: 600;
        }
        .info-sep {
            width: 2%;
            color: #94a3b8;
        }
        .info-val {
            width: 30%;
            color: #0f172a;
            font-weight: 600;
        }
        .content-body {
            font-size: 12.5px;
            color: #334155;
            line-height: 1.7;
            margin-bottom: 30px;
        }
        .content-body h3 {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 18px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            text-transform: uppercase;
        }
        .content-body ol {
            padding-left: 20px;
            margin-top: 6px;
            margin-bottom: 12px;
        }
        .content-body li {
            margin-bottom: 6px;
        }
        .content-body p {
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .signatures {
            margin-top: 35px;
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
            page-break-inside: avoid;
        }
        .sig-grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .sig-box {
            width: 48%;
            text-align: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            background-color: #fafafa;
        }
        .sig-role {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .sig-img-container {
            height: 95px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        .sig-img {
            max-height: 85px;
            max-width: 90%;
            object-fit: contain;
        }
        .sig-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            margin-top: 6px;
        }
        .sig-time {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 3px;
        }
        .footer-note {
            margin-top: 25px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px dashed #e2e8f0;
            padding-top: 12px;
        }
    </style>
</head>
<body>
    <!-- Action Bar -->
    <div class="action-bar no-print">
        <div>
            <span class="doc-badge">Dokumen Sah S&amp;K (Tahap {{ ucfirst($agreement->stage) }})</span>
            <span style="margin-left: 10px; font-weight: 600; color: #1e293b;">{{ $agreement->agreement_number }}</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-print" onclick="window.print()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak / Simpan PDF
            </button>
            <button class="btn-close" onclick="window.close()">Tutup</button>
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

        $companyTitle = 'PT VIRUZS GLOBAL CONNECTION';
        $companyAddress = 'Jl. Perintis Kemerdekaan No. 12A, Kel. Sukamulya, Kec. Cikembar, Kab. Sukabumi, Jawa Barat 43157';
        $companyPhone = '0266-250 8004 / 0889-7639-7034';
        $companyEmail = 'info@viruzs.co.id';
        $companyWeb = 'www.viruzs.co.id';
    @endphp

    <!-- Sheet Kontrak -->
    <div class="sheet">
        <!-- Header Perusahaan / Kop Surat Resmi & Elegan -->
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
                <div class="kop-divider-double">
                    <div class="kop-divider-line"></div>
                    <div class="kop-divider-line"></div>
                </div>
                <div class="kop-info-box">
                    <h1 class="kop-company-title">{{ $companyTitle }}</h1>
                    <div class="kop-subtitle">PENYEDIA JASA INTERNET (ISP) &bull; SURAT PERJANJIAN BERLANGGANAN RESMI</div>
                    <div class="kop-address">
                        {{ $companyAddress }}
                    </div>
                    <div class="kop-contacts">
                        <span class="kop-contact-item">
                            <svg class="kop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>{{ $companyPhone }}</span>
                        </span>
                        <span class="kop-contact-sep">&bull;</span>
                        <span class="kop-contact-item">
                            <svg class="kop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $companyEmail }}</span>
                        </span>
                        <span class="kop-contact-sep">&bull;</span>
                        <span class="kop-contact-item">
                            <svg class="kop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" />
                            </svg>
                            <span>{{ $companyWeb }}</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="kop-rule">
                <div class="kop-rule-thick"></div>
                <div class="kop-rule-thin"></div>
            </div>
        </div>

        <!-- Judul Dokumen -->
        <div class="doc-title-box">
            <div class="doc-badge">Surat Perjanjian Berlangganan</div>
            <h2>{{ $agreement->title }}</h2>
            <div class="doc-number">NO: {{ $agreement->agreement_number }}</div>
        </div>

        <!-- Ringkasan Data Pelanggan -->
        <div class="info-card">
            <table class="info-table">
                <tr>
                    <td class="info-label">Nama Pelanggan</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $agreement->customer_name }}</td>
                    <td class="info-label">ID Pelanggan</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $agreement->customer?->customer_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Nomor WhatsApp/HP</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $agreement->customer_phone ?: ($agreement->customer?->phone ?? '-') }}</td>
                    <td class="info-label">NIK / No. KTP</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $agreement->customer?->nik ?? $agreement->customer?->identity_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Paket Langganan</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $agreement->customer?->package?->name ?? 'Paket Internet' }}</td>
                    <td class="info-label">Biaya Bulanan</td>
                    <td class="info-sep">:</td>
                    @php
                        $basePrice = (float) ($agreement->customer?->base_amount ?: ($agreement->customer?->package?->price ?? 0));
                        $taxPpn = (float) ($agreement->customer?->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
                        $priceWithTax = $basePrice;
                        if ($taxPpn > 0 && !in_array($agreement->customer?->service_status, ['gratis', 'free_home'], true)) {
                            $raw = $basePrice * (1 + ($taxPpn / 100));
                            $rounded = (int) round($raw);
                            if (abs($rounded - round($rounded / 50) * 50) <= 1) {
                                $priceWithTax = (int) (round($rounded / 50) * 50);
                            } else {
                                $priceWithTax = $rounded;
                            }
                        }
                    @endphp
                    <td class="info-val">Rp {{ number_format($priceWithTax, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="info-label">Alamat Pemasangan</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $agreement->customer?->address ?? '-' }}</td>
                    <td class="info-label">Tahapan S&amp;K</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ strtoupper($agreement->stage) }}</td>
                </tr>
            </table>
        </div>

        <!-- Klausul Perjanjian -->
        <div class="content-body">
            {!! $agreement->content_snapshot !!}
        </div>

        <!-- Kotak Tanda Tangan Digital & Para Pihak -->
        <div class="signatures">
            <!-- Lokasi dan Tanggal -->
            <div style="text-align: right; margin-bottom: 14px; font-weight: 600; font-size: 13px; color: #1e293b;">
                Sukabumi, {{ $agreement->customer_signed_at ? $agreement->customer_signed_at->translatedFormat('d F Y') : ($agreement->created_at ? $agreement->created_at->translatedFormat('d F Y') : now()->translatedFormat('d F Y')) }}
            </div>

            <!-- Baris 1: Pihak Pertama & Pihak Kedua -->
            <div class="sig-grid">
                <!-- TTD Pihak Pertama -->
                <div class="sig-box">
                    <div class="sig-role">
                        PT VIRUZS GLOBAL CONNECTION<br>
                        <span style="font-size: 10px; font-weight: 500; color: #64748b; text-transform: none;">Pihak Pertama</span>
                    </div>
                    <div class="sig-img-container">
                        @if($agreement->employee_signature)
                            <img src="{{ $agreement->employee_signature }}" alt="TTD Petugas" class="sig-img">
                        @else
                            <span style="color: #94a3b8; font-style: italic; font-size: 11px;">(Belum bertanda tangan)</span>
                        @endif
                    </div>
                    <div class="sig-name">{{ $agreement->employee_name ?: 'Petugas / Teknisi' }}</div>
                    <div class="sig-time">
                        {{ $agreement->employee_signed_at ? $agreement->employee_signed_at->translatedFormat('d F Y - H:i') . ' WIB' : '-' }}
                    </div>
                </div>

                <!-- TTD Pihak Kedua -->
                <div class="sig-box">
                    <div class="sig-role">
                        PELANGGAN<br>
                        <span style="font-size: 10px; font-weight: 500; color: #64748b; text-transform: none;">Pihak Kedua</span>
                    </div>
                    <div class="sig-img-container">
                        @if($agreement->customer_signature)
                            <img src="{{ $agreement->customer_signature }}" alt="TTD Pelanggan" class="sig-img">
                        @else
                            <span style="color: #94a3b8; font-style: italic; font-size: 11px;">(Belum bertanda tangan)</span>
                        @endif
                    </div>
                    <div class="sig-name">{{ $agreement->customer_name }}</div>
                    <div class="sig-time">
                        {{ $agreement->customer_signed_at ? $agreement->customer_signed_at->translatedFormat('d F Y - H:i') . ' WIB' : '-' }}
                    </div>
                </div>
            </div>

            <!-- Baris 2: Petugas Lapangan (Survey & Pasang) -->
            <div class="sig-grid" style="margin-top: 14px;">
                <!-- Petugas Survey -->
                <div class="sig-box" style="background-color: #ffffff;">
                    <div class="sig-role" style="margin-bottom: 6px;">Petugas Survey</div>
                    <div class="sig-img-container" style="height: 55px;">
                        @if($agreement->stage === 'survey' && $agreement->employee_signature)
                            <img src="{{ $agreement->employee_signature }}" alt="TTD Survey" style="max-height: 50px;" class="sig-img">
                        @else
                            <span style="color: #cbd5e1; font-size: 10.5px; font-style: italic;">(Tanda Tangan Petugas)</span>
                        @endif
                    </div>
                    <div class="sig-name" style="font-size: 12px;">
                        {{ $agreement->stage === 'survey' ? $agreement->employee_name : ($surveyTechnician ?? '_______________________') }}
                    </div>
                </div>

                <!-- Petugas Pasang -->
                <div class="sig-box" style="background-color: #ffffff;">
                    <div class="sig-role" style="margin-bottom: 6px;">Petugas Pasang</div>
                    <div class="sig-img-container" style="height: 55px;">
                        @if($agreement->stage === 'installation' && $agreement->employee_signature)
                            <img src="{{ $agreement->employee_signature }}" alt="TTD Pasang" style="max-height: 50px;" class="sig-img">
                        @else
                            <span style="color: #cbd5e1; font-size: 10.5px; font-style: italic;">(Tanda Tangan Petugas)</span>
                        @endif
                    </div>
                    <div class="sig-name" style="font-size: 12px;">
                        {{ $agreement->stage === 'installation' ? $agreement->employee_name : ($installationTechnician ?? '_______________________') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan Kaki Legalitas -->
        <div class="footer-note">
            Dokumen ini ditandatangani secara elektronik melalui sistem aplikasi manajemen pelanggan pada tanggal {{ $agreement->created_at->translatedFormat('d F Y H:i:s') }} WIB (IP: {{ $agreement->ip_address ?? '127.0.0.1' }}). Memiliki kekuatan hukum yang sah dan mengikat antara kedua belah pihak.
        </div>
    </div>
</body>
</html>

