<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Komitmen Janji Bayar - {{ $customer->name ?? 'Pelanggan' }} ({{ $invoice->invoice_number }})</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1e293b;
            margin: 0;
            padding: {{ ($isPdf ?? false) ? '0' : '20px' }};
            background-color: {{ ($isPdf ?? false) ? '#ffffff' : '#f1f5f9' }};
        }

        /* Toolbar (No-Print) */
        .toolbar {
            max-width: 820px;
            margin: 0 auto 16px;
        }
        .toolbar-table {
            width: 100%;
            border-collapse: collapse;
        }
        .back-btn {
            display: inline-block;
            color: #475569;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 14px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        .btn-print {
            display: inline-block;
            background: #d97706;
            color: #ffffff;
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 6px;
        }
        .btn-pdf {
            display: inline-block;
            background: #dc2626;
            color: #ffffff;
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 6px;
        }
        .btn-close {
            display: inline-block;
            background: #ffffff;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 9px 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-sig {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 6px;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);
        }
        .btn-sig:hover {
            background: #4338ca;
        }

        /* Modal TTD Digital */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-box {
            background: #ffffff;
            width: 100%;
            max-width: 600px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .modal-header {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-title {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }
        .modal-body {
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 14px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }
        .sig-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .sig-tab-btn {
            flex: 1;
            padding: 8px 12px;
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            cursor: pointer;
            text-align: center;
        }
        .sig-tab-btn.active {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
        }
        .sig-canvas-wrap {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #fcfcfc;
            position: relative;
            touch-action: none;
            overflow: hidden;
        }
        .sig-canvas {
            width: 100%;
            height: 180px;
            display: block;
            cursor: crosshair;
        }
        .sig-canvas-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            pointer-events: none;
            user-select: none;
        }

        /* Sheet Container */
        .sheet {
            background: #ffffff;
            width: 100%;
            @if(!($isPdf ?? false))
            max-width: 820px;
            margin: auto;
            padding: 36px 44px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.08);
            @endif
        }

        @media print {
            body {
                background-color: #ffffff !important;
                padding: 0 !important;
            }
            .toolbar, .no-print {
                display: none !important;
            }
            .sheet {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            padding-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .company-info h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-info p {
            margin: 2px 0;
            color: #475569;
            font-size: 10.5px;
        }
        .doc-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-divider {
            border-top: 2.5px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 3px;
            margin: 10px 0 18px 0;
        }

        .doc-title-wrapper {
            text-align: center;
            margin-bottom: 18px;
        }
        .doc-title {
            margin: 0;
            font-size: 15.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .doc-number {
            margin: 4px 0 0;
            font-size: 11px;
            color: #64748b;
            font-family: Courier, monospace;
            font-weight: bold;
        }

        .intro-text {
            font-size: 11.5px;
            color: #334155;
            line-height: 1.55;
            margin-bottom: 12px;
            text-align: justify;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 14px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #cbd5e1;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            padding: 4px 6px;
            font-size: 11px;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-table td.col-label {
            width: 28%;
            font-weight: bold;
            color: #475569;
        }
        .info-table td.col-sep {
            width: 2%;
            font-weight: bold;
            color: #475569;
        }
        .info-table td.col-val {
            width: 70%;
            color: #0f172a;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .invoice-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
            font-size: 10.5px;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
        }
        .invoice-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
        }

        .promise-box-table {
            width: 100%;
            border-collapse: collapse;
            background: #fef3c7;
            border: 1.5px solid #f59e0b;
            margin: 12px 0 14px;
        }
        .promise-box-table td {
            padding: 12px 14px;
            vertical-align: middle;
        }

        .clauses {
            margin: 10px 0 14px;
            padding-left: 18px;
            font-size: 11px;
            color: #334155;
            line-height: 1.55;
        }
        .clauses li {
            margin-bottom: 5px;
            text-align: justify;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
            page-break-inside: avoid;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .sign-title {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .sig-container {
            min-height: 72px;
            text-align: center;
            margin: 4px auto 6px auto;
        }
        .sig-img {
            max-height: 65px;
            max-width: 190px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .sig-empty-space {
            height: 60px;
        }
        .sig-verified-badge {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 8px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-family: Courier, monospace;
        }
        .sign-name {
            font-weight: bold;
            font-size: 11.5px;
            color: #0f172a;
            border-top: 1px solid #0f172a;
            display: inline-block;
            min-width: 175px;
            padding-top: 4px;
        }
        .sign-role {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        .sheet-footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-top: 1px dashed #cbd5e1;
        }
        .sheet-footer td {
            padding-top: 6px;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    @php
        if (!function_exists('terbilangAngka')) {
            function terbilangAngka($angka) {
                $angka = abs((int)$angka);
                $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
                $terbilang = "";

                if ($angka < 12) {
                    $terbilang = " " . $baca[$angka];
                } elseif ($angka < 20) {
                    $terbilang = terbilangAngka($angka - 10) . " Belas";
                } elseif ($angka < 100) {
                    $terbilang = terbilangAngka((int)($angka / 10)) . " Puluh" . terbilangAngka($angka % 10);
                } elseif ($angka < 200) {
                    $terbilang = " Seratus" . terbilangAngka($angka - 100);
                } elseif ($angka < 1000) {
                    $terbilang = terbilangAngka((int)($angka / 100)) . " Ratus" . terbilangAngka($angka % 100);
                } elseif ($angka < 2000) {
                    $terbilang = " Seribu" . terbilangAngka($angka - 1000);
                } elseif ($angka < 1000000) {
                    $terbilang = terbilangAngka((int)($angka / 1000)) . " Ribu" . terbilangAngka($angka % 1000);
                } elseif ($angka < 1000000000) {
                    $terbilang = terbilangAngka((int)($angka / 1000000)) . " Juta" . terbilangAngka($angka % 1000000);
                }
                return trim($terbilang);
            }
        }

        $customer = $invoice->customer;
        $remaining = (int) ($invoice->remaining ?? max(0, $invoice->amount - $invoice->total_paid));
        $terbilang = ($remaining > 0 ? terbilangAngka($remaining) : 'Nol') . ' Rupiah';
        $promiseDateFormatted = $invoice->promise_date ? \Carbon\Carbon::parse($invoice->promise_date)->translatedFormat('l, d F Y') : '-';
        $todayFormatted = \Carbon\Carbon::now()->translatedFormat('d F Y');
    @endphp

    @if(!($isPdf ?? false))
        <div class="toolbar no-print">
            <table class="toolbar-table">
                <tr>
                    <td>
                        <a href="javascript:history.back()" class="back-btn">&larr; Kembali</a>
                    </td>
                    <td style="text-align: right;">
                        <button type="button" class="btn-sig" onclick="openSignatureModal()">✍️ Tanda Tangan Digital Lapangan</button>
                        <button class="btn-print" onclick="window.print()">Cetak Dokumen (Print)</button>
                        <a href="{{ request()->fullUrlWithQuery(['mode' => 'pdf']) }}" class="btn-pdf">Download PDF</a>
                        <button class="btn-close" onclick="window.close()">Tutup Halaman</button>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="sheet">
        <table class="header-table">
            <tr>
                <td style="width: 76%;">
                    <table>
                        <tr>
                            @if(!empty($logoBase64))
                                <td style="padding-right: 18px; vertical-align: middle; border-right: 2px solid #cbd5e1;">
                                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 64px; max-width: 195px; width: auto; height: auto; object-fit: contain; display: block;">
                                </td>
                                <td style="width: 16px;"></td>
                            @elseif(!empty($company['logo']) && !($isPdf ?? false))
                                <td style="padding-right: 18px; vertical-align: middle; border-right: 2px solid #cbd5e1;">
                                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 64px; max-width: 195px; width: auto; height: auto; object-fit: contain; display: block;" onerror="this.style.display='none'">
                                </td>
                                <td style="width: 16px;"></td>
                            @endif
                            <td class="company-info" style="vertical-align: middle;">
                                <h1>{{ $company['name'] ?? 'ISP PROVIDER NETWORK' }}</h1>
                                <p>{{ $company['address'] ?? 'Layanan Akses Internet Cepat, Stabil & Terpercaya' }}</p>
                                <p>
                                    @if(!empty($company['phone'])) Telp/WA: {{ $company['phone'] }} @endif
                                    @if(!empty($company['email'])) | Email: {{ $company['email'] }} @endif
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 24%; text-align: right; vertical-align: middle;">
                    <span class="doc-badge">DOKUMEN RESMI</span>
                </td>
            </tr>
        </table>
        <div class="header-divider"></div>

        <div class="doc-title-wrapper">
            <h2 class="doc-title">Surat Pernyataan Komitmen Janji Bayar</h2>
            <div class="doc-number">Nomor: SPJB/{{ $invoice->promise_date ? \Carbon\Carbon::parse($invoice->promise_date)->format('Ym') : date('Ym') }}/{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</div>
        </div>

        <p class="intro-text">
            Yang bertanda tangan di bawah ini, selaku pihak pelanggan layanan internet menyatakan dengan sebenarnya dan membuat komitmen pembayaran atas tunggakan tagihan penggunaan fasilitas jaringan internet dengan rincian data sebagai berikut:
        </p>

        <div class="section-title">1. Identitas Pelanggan</div>
        <table class="info-table">
            <tr>
                <td class="col-label">ID / Kode Pelanggan</td>
                <td class="col-sep">:</td>
                <td class="col-val"><strong style="font-family: Courier, monospace; font-size: 12px; color: #4338ca;">{{ $customer->customer_code ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="col-label">Nama Lengkap</td>
                <td class="col-sep">:</td>
                <td class="col-val"><strong>{{ $customer->name ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="col-label">Nomor WhatsApp / HP</td>
                <td class="col-sep">:</td>
                <td class="col-val">{{ $customer?->phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="col-label">Alamat Pemasangan</td>
                <td class="col-sep">:</td>
                <td class="col-val">{{ $customer?->address ?: '-' }} (Area: {{ $customer?->areaModel?->name ?? $customer?->area ?? '-' }})</td>
            </tr>
            <tr>
                <td class="col-label">Paket Langganan</td>
                <td class="col-sep">:</td>
                <td class="col-val">{{ $customer?->package?->name ?? 'Internet Paket' }} ({{ $customer?->package?->speed_mbps ? $customer->package->speed_mbps . ' Mbps' : ($customer?->package?->speed ?? '-') }})</td>
            </tr>
        </table>

        <div class="section-title">2. Rincian Tagihan & Kesepakatan Janji Bayar</div>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Nomor Tagihan</th>
                    <th style="width: 20%;">Periode</th>
                    <th style="width: 20%;">Jatuh Tempo Asli</th>
                    <th style="width: 17%; text-align: right;">Total Tagihan</th>
                    <th style="width: 18%; text-align: right;">Sudah Dibayar</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong style="font-family: Courier, monospace;">{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ $invoice->period_label }}</td>
                    <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d M Y') : '-' }}</td>
                    <td style="text-align: right; font-family: Courier, monospace;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    <td style="text-align: right; font-family: Courier, monospace; color: #059669;">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table class="promise-box-table">
            <tr>
                <td style="width: 58%;">
                    <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #92400e;">Sisa Kewajiban Tagihan Yang Harus Dibayar:</div>
                    <div style="font-size: 18px; font-weight: bold; color: #78350f; font-family: Courier, monospace; margin: 2px 0;">Rp {{ number_format($remaining, 0, ',', '.') }}</div>
                    <div style="font-size: 10px; font-style: italic; color: #b45309;"># {{ $terbilang }} #</div>
                </td>
                <td style="width: 42%; text-align: right; border-left: 1.5px dashed #d97706;">
                    <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #92400e;">Batas Akhir Janji Bayar:</div>
                    <div style="font-size: 14px; font-weight: bold; color: #0f172a; margin: 2px 0;">{{ $promiseDateFormatted }}</div>
                    @if(!empty($invoice->notes))
                        <div style="font-size: 10px; font-weight: bold; color: #b45309;">Ket: {{ $invoice->notes }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <div class="section-title">3. Klausul Pernyataan & Kesepakatan Bersama</div>
        <ol class="clauses">
            <li>
                <strong>Komitmen Pelunasan:</strong> Pihak Pelanggan bersedia, bertanggung jawab penuh, dan berkomitmen untuk melunasi seluruh sisa kewajiban tagihan sebesar <strong>Rp {{ number_format($remaining, 0, ',', '.') }}</strong> paling lambat pada hari <strong>{{ $promiseDateFormatted }}</strong>.
            </li>
            <li>
                <strong>Dispensasi Layanan:</strong> Pihak Penyedia Layanan memberikan relaksasi/kelonggaran waktu penagihan dan dispensasi akses internet selama periode komitmen janji bayar tersebut di atas berlangsung.
            </li>
            <li>
                <strong>Konsekuensi Keterlambatan (Isolir Otomatis):</strong> Apabila sampai dengan tanggal jatuh tempo janji bayar yang telah disepakati pelanggan belum melakukan pelunasan tagihan secara penuh, maka Pihak Pelanggan bersedia dan menyetujui pemutusan sementara (isolir/suspend) jaringan internet secara otomatis oleh sistem tanpa memerlukan pemberitahuan ulang.
            </li>
            <li>
                <strong>Keaslian & Itikad Baik:</strong> Surat pernyataan komitmen janji bayar ini dibuat dengan kesadaran penuh, itikad baik, dan tanpa adanya paksaan dari pihak manapun demi menjaga kelangsungan hubungan kerjasama penyediaan layanan internet.
            </li>
        </ol>

        <table class="signatures-table">
            <tr>
                <td>
                    <div class="sign-title">
                        Pihak Pelanggan / Yang Berjanji,<br>
                        Tanggal: {{ $invoice->promise_signed_at ? \Carbon\Carbon::parse($invoice->promise_signed_at)->translatedFormat('d F Y') : $todayFormatted }}
                    </div>
                    @if(!empty($invoice->customer_promise_signature))
                        <div class="sig-container">
                            <img src="{{ $invoice->customer_promise_signature }}" class="sig-img" alt="TTD Pelanggan">
                            <span class="sig-verified-badge">✓ Terverifikasi Digital</span>
                        </div>
                    @else
                        <div class="sig-empty-space" @if(!($isPdf ?? false)) onclick="openSignatureModal('customer')" style="cursor: pointer; border: 1.5px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 4px auto 6px; max-width: 220px; min-height: 60px;" title="Klik untuk tanda tangan digital langsung" @endif>
                            @if(!($isPdf ?? false))
                                <span style="font-size: 11px; color: #4f46e5; font-weight: bold;">✍️ Klik untuk TTD Pelanggan</span>
                            @endif
                        </div>
                    @endif
                    <div class="sign-name">{{ $invoice->promise_signer_name ?: ($customer->name ?? 'Nama Pelanggan') }}</div>
                    <div class="sign-role">Pelanggan / Penanggung Jawab</div>
                </td>
                <td>
                    <div class="sign-title">
                        Petugas / Bagian Billing Penagihan,<br>
                        {{ $company['name'] ?? 'ISP Provider' }}
                    </div>
                    @if(!empty($invoice->collector_promise_signature))
                        <div class="sig-container">
                            <img src="{{ $invoice->collector_promise_signature }}" class="sig-img" alt="TTD Petugas">
                            <span class="sig-verified-badge">✓ Terverifikasi Digital</span>
                        </div>
                    @else
                        <div class="sig-empty-space" @if(!($isPdf ?? false)) onclick="openSignatureModal('collector')" style="cursor: pointer; border: 1.5px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 4px auto 6px; max-width: 220px; min-height: 60px;" title="Klik untuk tanda tangan digital langsung" @endif>
                            @if(!($isPdf ?? false))
                                <span style="font-size: 11px; color: #4f46e5; font-weight: bold;">✍️ Klik untuk TTD Petugas</span>
                            @endif
                        </div>
                    @endif
                    <div class="sign-name">{{ $invoice->promise_collector_name ?: (auth()->user()->name ?? 'Petugas Penagihan') }}</div>
                    <div class="sign-role">Staff Operasional / Billing Admin</div>
                </td>
            </tr>
        </table>

        <table class="sheet-footer">
            <tr>
                <td>Dicetak secara otomatis melalui Sistem APM2 Billing | Tanggal cetak: {{ now()->translatedFormat('d/m/Y H:i') }} WIB</td>
                <td style="text-align: right;">Surat Komitmen Janji Bayar #{{ $invoice->invoice_number }}</td>
            </tr>
        </table>
    </div>

    @if(!($isPdf ?? false))
        <!-- Modal Tanda Tangan Digital Lapangan -->
        <div id="sigModal" class="modal-overlay no-print" style="display: none;">
            <div class="modal-box">
                <div class="modal-header">
                    <div>
                        <h3 class="modal-title">✍️ Tanda Tangan Digital Lapangan</h3>
                        <p style="margin: 2px 0 0; font-size: 11px; color: #64748b;">Surat Janji Bayar #{{ $invoice->invoice_number }} &bull; {{ $customer->name ?? 'Pelanggan' }}</p>
                    </div>
                    <button type="button" onclick="closeSignatureModal()" style="border: none; background: transparent; font-size: 20px; color: #94a3b8; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="sig-tabs">
                        <button type="button" id="tabCustomerBtn" class="sig-tab-btn active" onclick="switchSigTab('customer')">
                            1. TTD Pelanggan
                        </button>
                        <button type="button" id="tabCollectorBtn" class="sig-tab-btn" onclick="switchSigTab('collector')">
                            2. TTD Petugas Penagihan
                        </button>
                    </div>

                    <!-- Panel TTD Pelanggan -->
                    <div id="panelCustomer">
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 11px; font-weight: bold; color: #334155; margin-bottom: 4px;">Nama Lengkap Pelanggan / Yang Berjanji <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="signerNameInput" value="{{ $invoice->promise_signer_name ?: ($customer->name ?? '') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; font-weight: bold; box-sizing: border-box;" placeholder="Masukkan nama pelanggan...">
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-size: 11px; font-weight: bold; color: #475569;">Gores Tanda Tangan Pelanggan di Bawah:</span>
                            <button type="button" onclick="clearSig('customer')" style="background: none; border: none; color: #ef4444; font-size: 11px; font-weight: bold; cursor: pointer;">Hapus Goresan</button>
                        </div>
                        <div class="sig-canvas-wrap">
                            <canvas id="canvasCustomer" class="sig-canvas"></canvas>
                            <div id="placeholderCustomer" class="sig-canvas-placeholder">Sentuh & gores tanda tangan di sini (layar HP/mouse)</div>
                        </div>
                    </div>

                    <!-- Panel TTD Petugas -->
                    <div id="panelCollector" style="display: none;">
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 11px; font-weight: bold; color: #334155; margin-bottom: 4px;">Nama Petugas / Billing Lapangan <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="collectorNameInput" value="{{ $invoice->promise_collector_name ?: (auth()->user()->name ?? 'Petugas Penagihan') }}" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; font-weight: bold; box-sizing: border-box;" placeholder="Masukkan nama petugas...">
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-size: 11px; font-weight: bold; color: #475569;">Gores Tanda Tangan Petugas di Bawah:</span>
                            <button type="button" onclick="clearSig('collector')" style="background: none; border: none; color: #ef4444; font-size: 11px; font-weight: bold; cursor: pointer;">Hapus Goresan</button>
                        </div>
                        <div class="sig-canvas-wrap">
                            <canvas id="canvasCollector" class="sig-canvas"></canvas>
                            <div id="placeholderCollector" class="sig-canvas-placeholder">Sentuh & gores tanda tangan di sini (layar HP/mouse)</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeSignatureModal()" style="padding: 8px 14px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; font-weight: bold; color: #64748b; cursor: pointer;">Batal</button>
                    <button type="button" id="saveSigBtn" onclick="saveSignatures()" style="padding: 8px 18px; background: #4f46e5; border: none; border-radius: 8px; font-size: 12px; font-weight: bold; color: #ffffff; cursor: pointer; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);">Simpan & Terapkan TTD Digital</button>
                </div>
            </div>
        </div>

        <script>
            let currentTab = 'customer';
            let customerHasDrawn = false;
            let collectorHasDrawn = false;
            const existingCustomerSig = @json($invoice->customer_promise_signature ?? '');
            const existingCollectorSig = @json($invoice->collector_promise_signature ?? '');

            function setupSignaturePad(canvasId, placeholderId, isCollector) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return null;
                const ctx = canvas.getContext('2d');
                const placeholder = document.getElementById(placeholderId);

                // Set logical resolution matching display
                function resize() {
                    const rect = canvas.getBoundingClientRect();
                    const dpr = window.devicePixelRatio || 1;
                    canvas.width = rect.width * dpr;
                    canvas.height = rect.height * dpr;
                    ctx.scale(dpr, dpr);
                    ctx.strokeStyle = '#0f172a';
                    ctx.lineWidth = 2.5;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                }
                resize();

                let drawing = false;

                function getPos(e) {
                    const rect = canvas.getBoundingClientRect();
                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                    return {
                        x: clientX - rect.left,
                        y: clientY - rect.top
                    };
                }

                function start(e) {
                    drawing = true;
                    const pos = getPos(e);
                    ctx.beginPath();
                    ctx.moveTo(pos.x, pos.y);
                    if (placeholder) placeholder.style.display = 'none';
                    if (isCollector) collectorHasDrawn = true;
                    else customerHasDrawn = true;
                }

                function move(e) {
                    if (!drawing) return;
                    e.preventDefault();
                    const pos = getPos(e);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                }

                function stop() {
                    drawing = false;
                }

                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', move);
                window.addEventListener('mouseup', stop);

                canvas.addEventListener('touchstart', (e) => {
                    start(e);
                    e.preventDefault();
                }, { passive: false });
                canvas.addEventListener('touchmove', (e) => {
                    move(e);
                    e.preventDefault();
                }, { passive: false });
                window.addEventListener('touchend', stop);

                return { canvas, ctx, resize };
            }

            let padCustomer, padCollector;

            function openSignatureModal(targetTab) {
                const modal = document.getElementById('sigModal');
                modal.style.display = 'flex';
                if (!padCustomer) padCustomer = setupSignaturePad('canvasCustomer', 'placeholderCustomer', false);
                if (!padCollector) padCollector = setupSignaturePad('canvasCollector', 'placeholderCollector', true);

                setTimeout(() => {
                    if (padCustomer) padCustomer.resize();
                    if (padCollector) padCollector.resize();
                    if (targetTab) switchSigTab(targetTab);
                }, 100);
            }

            function closeSignatureModal() {
                document.getElementById('sigModal').style.display = 'none';
            }

            function switchSigTab(tab) {
                currentTab = tab;
                const tabC = document.getElementById('tabCustomerBtn');
                const tabP = document.getElementById('tabCollectorBtn');
                const panelC = document.getElementById('panelCustomer');
                const panelP = document.getElementById('panelCollector');

                if (tab === 'customer') {
                    tabC.classList.add('active');
                    tabP.classList.remove('active');
                    panelC.style.display = 'block';
                    panelP.style.display = 'none';
                    if (padCustomer) padCustomer.resize();
                } else {
                    tabP.classList.add('active');
                    tabC.classList.remove('active');
                    panelC.style.display = 'none';
                    panelP.style.display = 'block';
                    if (padCollector) padCollector.resize();
                }
            }

            function clearSig(tab) {
                if (tab === 'customer') {
                    const canvas = document.getElementById('canvasCustomer');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    customerHasDrawn = false;
                    document.getElementById('placeholderCustomer').style.display = 'block';
                } else {
                    const canvas = document.getElementById('canvasCollector');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    collectorHasDrawn = false;
                    document.getElementById('placeholderCollector').style.display = 'block';
                }
            }

            function saveSignatures() {
                const saveBtn = document.getElementById('saveSigBtn');
                saveBtn.disabled = true;
                saveBtn.innerText = 'Menyimpan TTD...';

                let customerSig = existingCustomerSig;
                let collectorSig = existingCollectorSig;

                if (customerHasDrawn) {
                    const c = document.getElementById('canvasCustomer');
                    customerSig = c.toDataURL('image/png');
                }
                if (collectorHasDrawn) {
                    const c = document.getElementById('canvasCollector');
                    collectorSig = c.toDataURL('image/png');
                }

                const signerName = document.getElementById('signerNameInput').value;
                const collectorName = document.getElementById('collectorNameInput').value;

                fetch("{{ route('invoices.promise-signature', $invoice) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_signature: customerSig,
                        collector_signature: collectorSig,
                        signer_name: signerName,
                        collector_name: collectorName
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Tanda tangan digital berhasil disimpan!');
                        window.location.reload();
                    } else {
                        alert('Gagal menyimpan tanda tangan: ' + (data.message || 'Terjadi kesalahan'));
                        saveBtn.disabled = false;
                        saveBtn.innerText = 'Simpan & Terapkan TTD Digital';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan jaringan saat menyimpan tanda tangan.');
                    saveBtn.disabled = false;
                    saveBtn.innerText = 'Simpan & Terapkan TTD Digital';
                });
            }
        </script>
    @endif

</body>
</html>
