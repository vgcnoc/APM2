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
        }
        .sign-title {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 58px;
            line-height: 1.4;
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
                        <button class="btn-close" onclick="window.close()">Tutup Halaman</button>
                        <button class="btn-print" onclick="window.print()">Cetak Dokumen (Print)</button>
                        <a href="{{ request()->fullUrlWithQuery(['mode' => 'pdf']) }}" class="btn-pdf">Download PDF</a>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="sheet">
        <table class="header-table">
            <tr>
                <td style="width: 72%;">
                    <table>
                        <tr>
                            @if(!empty($logoBase64))
                                <td style="width: 64px; padding-right: 12px; vertical-align: middle;">
                                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 52px; max-width: 60px;">
                                </td>
                            @elseif(!empty($company['logo']) && !($isPdf ?? false))
                                <td style="width: 64px; padding-right: 12px; vertical-align: middle;">
                                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 52px; max-width: 60px;" onerror="this.style.display='none'">
                                </td>
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
                <td style="width: 28%; text-align: right;">
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
                        Tanggal: {{ $todayFormatted }}
                    </div>
                    <div class="sign-name">{{ $customer->name ?? 'Nama Pelanggan' }}</div>
                    <div class="sign-role">Pelanggan / Penanggung Jawab</div>
                </td>
                <td>
                    <div class="sign-title">
                        Petugas / Bagian Billing Penagihan,<br>
                        {{ $company['name'] ?? 'ISP Provider' }}
                    </div>
                    <div class="sign-name">{{ auth()->user()->name ?? 'Petugas Penagihan' }}</div>
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

</body>
</html>
