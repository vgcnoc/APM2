<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Komitmen Janji Bayar - {{ $customer->name ?? 'Pelanggan' }} ({{ $invoice->invoice_number }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --amber-dark: #b45309;
            --amber-main: #d97706;
            --amber-light: #fef3c7;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-200: #e2e8f0;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            background-color: #f1f5f9;
        }

        /* ── Toolbar (No-Print) ─────────────────────────── */
        .toolbar {
            max-width: 820px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .toolbar .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #475569;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .toolbar .back-btn:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #d97706;
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);
            transition: all 0.2s;
        }
        .btn-print:hover {
            background: #b45309;
        }
        .btn-close {
            background: #ffffff;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 9px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-close:hover {
            background: #f1f5f9;
            color: #334155;
        }

        /* ── Sheet Container (A4 Printable) ─────────────── */
        .sheet {
            max-width: 820px;
            margin: auto;
            padding: 40px 48px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .toolbar, .no-print {
                display: none !important;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 24px 30px;
                max-width: 100%;
                width: 100%;
            }
            @page {
                size: A4;
                margin: 12mm 15mm;
            }
        }

        /* ── Kop Surat ─────────────────────────────────── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
        }
        .brand-area {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .brand-logo {
            max-height: 56px;
            max-width: 120px;
            object-fit: contain;
        }
        .company-info h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .company-info p {
            margin: 2px 0;
            color: #475569;
            font-size: 11.5px;
        }
        .header-meta {
            text-align: right;
        }
        .header-meta .doc-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-divider {
            border-top: 2.5px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 3px;
            margin-bottom: 22px;
        }

        /* ── Judul Surat ───────────────────────────────── */
        .doc-title-wrapper {
            text-align: center;
            margin-bottom: 22px;
        }
        .doc-title {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .doc-number {
            margin: 4px 0 0;
            font-size: 11.5px;
            color: #64748b;
            font-family: 'JetBrains Mono', Consolas, monospace;
            font-weight: 600;
        }

        /* ── Paragraf Pembuka ──────────────────────────── */
        .intro-text {
            font-size: 12.5px;
            color: #334155;
            line-height: 1.6;
            margin-bottom: 14px;
            text-align: justify;
        }

        /* ── Section Title ─────────────────────────────── */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 16px 0 8px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── Grid Table Identitas ──────────────────────── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .info-table td {
            padding: 5px 8px;
            font-size: 12px;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-table td.col-label {
            width: 28%;
            font-weight: 600;
            color: #475569;
        }
        .info-table td.col-sep {
            width: 2%;
            font-weight: 600;
            color: #475569;
        }
        .info-table td.col-val {
            width: 70%;
            color: #0f172a;
        }

        /* ── Box Janji Bayar (Highlight Elegan) ────────── */
        .promise-box {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1.5px solid #f59e0b;
            border-radius: 10px;
            padding: 14px 18px;
            margin: 14px 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .promise-amount-wrap {
            flex: 1;
        }
        .promise-amount-wrap .label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #92400e;
            letter-spacing: 0.5px;
        }
        .promise-amount-wrap .amount {
            font-size: 20px;
            font-weight: 800;
            color: #78350f;
            font-family: 'JetBrains Mono', Consolas, monospace;
            margin: 2px 0;
        }
        .promise-amount-wrap .terbilang {
            font-size: 11px;
            font-style: italic;
            color: #b45309;
        }
        .promise-date-wrap {
            text-align: right;
            padding-left: 16px;
            border-left: 1.5px dashed #d97706;
        }
        .promise-date-wrap .label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #92400e;
            letter-spacing: 0.5px;
        }
        .promise-date-wrap .date {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 2px 0;
        }
        .promise-date-wrap .countdown {
            font-size: 11px;
            font-weight: 600;
            color: #b45309;
        }

        /* ── Rincian Tagihan Table ─────────────────────── */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .invoice-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 700;
            font-size: 11.5px;
            text-align: left;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
        }
        .invoice-table td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
        }

        /* ── Klausul Pernyataan (Nomor) ─────────────────── */
        .clauses {
            margin: 12px 0 16px;
            padding-left: 18px;
            font-size: 12px;
            color: #334155;
            line-height: 1.6;
        }
        .clauses li {
            margin-bottom: 6px;
            text-align: justify;
        }

        /* ── Tanda Tangan ──────────────────────────────── */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 28px;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 44%;
            text-align: center;
        }
        .sign-title {
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 65px;
            line-height: 1.4;
        }
        .sign-name {
            font-weight: 700;
            font-size: 12.5px;
            color: #0f172a;
            border-top: 1px solid #0f172a;
            display: inline-block;
            min-width: 180px;
            padding-top: 4px;
        }
        .sign-role {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Footer Catatan Kecil ──────────────────────── */
        .sheet-footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    @php
        // Helper fungsi terbilang sederhana
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

        $customer = $invoice->customer;
        $remaining = (int) ($invoice->remaining ?? max(0, $invoice->amount - $invoice->total_paid));
        $terbilang = terbilangAngka($remaining) . ' Rupiah';
        $promiseDateFormatted = $invoice->promise_date ? \Carbon\Carbon::parse($invoice->promise_date)->translatedFormat('l, d F Y') : '-';
        $todayFormatted = \Carbon\Carbon::now()->translatedFormat('d F Y');
    @endphp

    <!-- Toolbar Atas (No-Print) -->
    <div class="toolbar">
        <a href="javascript:history.back()" class="back-btn">&larr; Kembali</a>
        <div class="toolbar-actions">
            <button class="btn-close" onclick="window.close()">Tutup Halaman</button>
            <button class="btn-print" onclick="window.print()">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    <!-- Lembar Surat Resmi -->
    <div class="sheet">

        <!-- KOP SURAT RESMI -->
        <div class="header">
            <div class="brand-area">
                @if(!empty($company['logo']))
                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
                @endif
                <div class="company-info">
                    <h1>{{ $company['name'] ?? 'ISP PROVIDER NETWORK' }}</h1>
                    <p>{{ $company['address'] ?? 'Layanan Akses Internet Cepat, Stabil & Terpercaya' }}</p>
                    <p>
                        @if(!empty($company['phone'])) Telp/WA: {{ $company['phone'] }} &bull; @endif
                        @if(!empty($company['email'])) Email: {{ $company['email'] }} @endif
                    </p>
                </div>
            </div>
            <div class="header-meta">
                <span class="doc-badge">DOKUMEN RESMI</span>
            </div>
        </div>
        <div class="header-divider"></div>

        <!-- JUDUL SURAT -->
        <div class="doc-title-wrapper">
            <h2 class="doc-title">Surat Pernyataan Komitmen Janji Bayar</h2>
            <div class="doc-number">Nomor: SPJB/{{ $invoice->promise_date ? \Carbon\Carbon::parse($invoice->promise_date)->format('Ym') : date('Ym') }}/{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</div>
        </div>

        <!-- KALIMAT PEMBUKA -->
        <p class="intro-text">
            Yang bertanda tangan di bawah ini, selaku pihak pelanggan layanan internet menyatakan dengan sebenarnya dan membuat komitmen pembayaran atas tunggakan tagihan penggunaan fasilitas jaringan internet dengan rincian data sebagai berikut:
        </p>

        <!-- IDENTITAS PELANGGAN -->
        <div class="section-title">1. Identitas Pelanggan</div>
        <table class="info-table">
            <tr>
                <td class="col-label">ID / Kode Pelanggan</td>
                <td class="col-sep">:</td>
                <td class="col-val"><strong style="font-family: 'JetBrains Mono', Consolas, monospace; font-size: 13px; color: #4338ca;">{{ $customer->customer_code ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="col-label">Nama Lengkap</td>
                <td class="col-sep">:</td>
                <td class="col-val"><strong>{{ $customer->name ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="col-label">Nomor WhatsApp / HP</td>
                <td class="col-sep">:</td>
                <td class="col-val">{{ $customer->phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="col-label">Alamat Pemasangan</td>
                <td class="col-sep">:</td>
                <td class="col-val">{{ $customer->address ?: '-' }} (Area: {{ $customer->areaModel->name ?? $customer->area ?? '-' }})</td>
            </tr>
            <tr>
                <td class="col-label">Paket Langganan</td>
                <td class="col-sep">:</td>
                <td class="col-val">{{ $customer->package->name ?? 'Internet Paket' }} ({{ $customer->package->speed ?? '-' }})</td>
            </tr>
        </table>

        <!-- RINCIAN TAGIHAN & JANJI BAYAR -->
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
                    <td><strong style="font-family: 'JetBrains Mono', Consolas, monospace;">{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ $invoice->period_label }}</td>
                    <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d M Y') : '-' }}</td>
                    <td style="text-align: right; font-family: 'JetBrains Mono', Consolas, monospace;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    <td style="text-align: right; font-family: 'JetBrains Mono', Consolas, monospace; color: #059669;">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- HIGHLIGHT BOX JANJI BAYAR -->
        <div class="promise-box">
            <div class="promise-amount-wrap">
                <div class="label">Sisa Kewajiban Tagihan Yang Harus Dibayar:</div>
                <div class="amount">Rp {{ number_format($remaining, 0, ',', '.') }}</div>
                <div class="terbilang"># {{ $terbilang }} #</div>
            </div>
            <div class="promise-date-wrap">
                <div class="label">Batas Akhir Janji Bayar:</div>
                <div class="date">{{ $promiseDateFormatted }}</div>
                @if(!empty($invoice->notes))
                    <div class="countdown">Ket: {{ $invoice->notes }}</div>
                @endif
            </div>
        </div>

        <!-- PASAL KESEPAKATAN -->
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

        <!-- KOLOM TANDA TANGAN -->
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">
                    Pihak Pelanggan / Yang Berjanji,<br>
                    Tanggal: {{ $todayFormatted }}
                </div>
                <div class="sign-name">{{ $customer->name ?? 'Nama Pelanggan' }}</div>
                <div class="sign-role">Pelanggan / Penanggung Jawab</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Petugas / Bagian Billing Penagihan,<br>
                    {{ $company['name'] ?? 'ISP Provider' }}
                </div>
                <div class="sign-name">{{ auth()->user()->name ?? 'Petugas Penagihan' }}</div>
                <div class="sign-role">Staff Operasional / Billing Admin</div>
            </div>
        </div>

        <!-- FOOTER CATATAN -->
        <div class="sheet-footer">
            <span>Dicetak secara otomatis melalui Sistem APM2 Billing &bull; Tanggal cetak: {{ now()->translatedFormat('d/m/Y H:i') }} WIB</span>
            <span>Surat Komitmen Janji Bayar #{{ $invoice->invoice_number }}</span>
        </div>

    </div>

</body>
</html>
