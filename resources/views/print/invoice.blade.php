<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }} - {{ $company['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --emerald: #059669;
            --rose: #e11d48;
            --amber: #d97706;
            --slate: #0f172a;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }

        /* ── Top Bar (No Print) ─────────────────────────────────── */
        .toolbar-wrapper {
            position: sticky;
            top: 0;
            z-index: 999;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            padding: 12px 20px;
        }
        .toolbar-container {
            max-width: 1000px;
            margin: auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .preview-badge {
            background: #fef3c7;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid #fde68a;
        }
        .template-selector {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            padding: 4px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .tpl-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .tpl-btn:hover {
            color: #0f172a;
            background: #ffffff;
        }
        .tpl-btn.active {
            color: #ffffff;
            background: #4f46e5;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.35);
        }
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
            transition: all 0.2s ease;
        }
        .btn-print:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        /* ── Base Container ─────────────────────────────────────── */
        .page-wrapper {
            padding: 30px 20px 60px;
            display: flex;
            justify-content: center;
        }

        /* ── Calculations ───────────────────────────────────────── */
        @php
            $taxPpn = (float) ($invoice->customer->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
            $subtotal = $invoice->amount;
            $taxAmount = 0;
            if ($taxPpn > 0) {
                $subtotal = round($invoice->amount / (1 + ($taxPpn / 100)));
                $taxAmount = $invoice->amount - $subtotal;
            }
            $banks = $banks ?? (json_decode(\App\Models\Setting::get('payment_banks', '[]'), true) ?: []);
            $activeTemplate = $template ?? \App\Models\Setting::get('invoice_template', 'modern');
        @endphp

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 1: MODERN CLEAN (Default)
           ═════════════════════════════════════════════════════════ */
        .invoice-modern {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .modern-header {
            background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
            color: #ffffff;
            padding: 32px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        .modern-header::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.12), transparent 70%);
            pointer-events: none;
        }
        .modern-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .modern-logo {
            max-height: 55px;
            max-width: 130px;
            object-fit: contain;
            background: #ffffff;
            padding: 6px 10px;
            border-radius: 10px;
        }
        .modern-company-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .modern-company-sub {
            font-size: 11px;
            color: #c7d2fe;
            margin: 3px 0 0;
            max-width: 320px;
            line-height: 1.4;
        }
        .modern-inv-meta {
            text-align: right;
        }
        .modern-inv-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #a5b4fc;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .modern-inv-num {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
            color: #ffffff;
        }
        .modern-status-pill {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .pill-paid { background: #10b981; color: #ffffff; }
        .pill-unpaid { background: #f43f5e; color: #ffffff; }
        .pill-partial { background: #f59e0b; color: #ffffff; }

        .modern-body {
            padding: 32px 36px;
        }
        .modern-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding-bottom: 24px;
            margin-bottom: 24px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .meta-card-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .meta-card-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px;
        }
        .meta-card-text {
            font-size: 12px;
            color: #475569;
            margin: 2px 0;
            line-height: 1.4;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 24px;
        }
        .modern-table th {
            background: #f8fafc;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            padding: 12px 16px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .modern-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
        }
        .modern-table th.text-right, .modern-table td.text-right {
            text-align: right;
        }

        .modern-summary-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-top: 20px;
        }
        .modern-banks {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
        }
        .modern-banks-header {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #4338ca;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .modern-bank-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .modern-bank-item:last-child { border-bottom: none; }
        .modern-bank-acc {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .modern-totals {
            width: 320px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
        }
        .modern-total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #475569;
        }
        .modern-grand-total {
            border-top: 2px solid #e2e8f0;
            margin-top: 8px;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        .modern-stamp-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
        }
        .modern-stamp-box {
            text-align: center;
            width: 200px;
            position: relative;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 2: CLASSIC CORPORATE (Formal Letterhead)
           ═════════════════════════════════════════════════════════ */
        .invoice-classic {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 40px;
            font-family: 'Times New Roman', Times, serif;
        }
        .classic-header {
            text-align: center;
            border-bottom: 3px double #000000;
            padding-bottom: 18px;
            margin-bottom: 24px;
            position: relative;
        }
        .classic-header h1 {
            font-size: 24px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }
        .classic-header p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #333;
        }
        .classic-title-block {
            text-align: center;
            margin-bottom: 24px;
        }
        .classic-title-block h2 {
            font-size: 20px;
            text-decoration: underline;
            margin: 0;
            letter-spacing: 1px;
        }
        .classic-title-block p {
            margin: 4px 0 0;
            font-size: 13px;
            font-weight: bold;
        }
        .classic-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .classic-grid table {
            width: 100%;
        }
        .classic-grid td {
            padding: 3px 0;
            vertical-align: top;
        }
        .classic-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .classic-table th, .classic-table td {
            border: 1px solid #000000;
            padding: 8px 12px;
        }
        .classic-table th {
            background-color: #f1f5f9;
            text-align: center;
            font-weight: bold;
        }
        .classic-table td.right, .classic-table th.right {
            text-align: right;
        }
        .classic-footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }
        .classic-signature {
            text-align: center;
            margin-left: auto;
            width: 220px;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 3: THERMAL / STRUK POS (58mm / 80mm)
           ═════════════════════════════════════════════════════════ */
        .invoice-thermal {
            width: 320px;
            background: #ffffff;
            border: 1px dashed #94a3b8;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 16px 12px;
            font-family: 'JetBrains Mono', Courier, monospace;
            font-size: 11px;
            line-height: 1.4;
            color: #000000;
            margin: auto;
        }
        .thermal-center {
            text-align: center;
        }
        .thermal-divider {
            border-top: 1px dashed #000000;
            margin: 8px 0;
        }
        .thermal-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        .thermal-bold {
            font-weight: 700;
        }
        .thermal-status {
            display: inline-block;
            border: 1.5px solid #000000;
            padding: 3px 10px;
            font-weight: 800;
            margin: 6px 0;
            text-transform: uppercase;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 4: ELEGANT MINIMALIST (Tech ISP)
           ═════════════════════════════════════════════════════════ */
        .invoice-minimalist {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 40px;
        }
        .mini-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 28px;
        }
        .mini-brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .mini-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }
        .mini-meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 20px;
        }
        .mini-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .mini-table th {
            text-align: left;
            padding: 10px 0;
            border-bottom: 1px solid #0f172a;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .mini-table td {
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .mini-total-card {
            background: #0f172a;
            color: #ffffff;
            padding: 20px 24px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        /* ── Stempel & Signature Shared ─────────────────────────── */
        .official-stamp {
            width: 140px;
            height: 90px;
            border: 2px solid #2563eb;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4px;
            color: #2563eb;
            background: rgba(255, 255, 255, 0.6);
            transform: rotate(-10deg);
            opacity: 0.88;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Media Print ────────────────────────────────────────── */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .page-wrapper {
                padding: 0 !important;
            }
            .invoice-modern,
            .invoice-classic,
            .invoice-minimalist {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .invoice-thermal {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 320px !important;
                margin: 0 auto !important;
            }
            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- ── TOOLBAR (NON PRINT) ─────────────────────────────────── -->
    <div class="toolbar-wrapper no-print">
        <div class="toolbar-container">
            <div class="toolbar-title">
                <span>📄 Invoice #{{ $invoice->invoice_number }}</span>
                @if(!empty($isPreview))
                    <span class="preview-badge">Mode Pratinjau Desain</span>
                @endif
            </div>

            <div class="template-selector">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; padding-left: 6px;">Template:</span>
                <a href="?template=modern" class="tpl-btn {{ $activeTemplate === 'modern' ? 'active' : '' }}">
                    🌟 Modern
                </a>
                <a href="?template=classic" class="tpl-btn {{ $activeTemplate === 'classic' ? 'active' : '' }}">
                    🏛️ Classic
                </a>
                <a href="?template=thermal" class="tpl-btn {{ $activeTemplate === 'thermal' ? 'active' : '' }}">
                    🧾 Struk POS
                </a>
                <a href="?template=minimalist" class="tpl-btn {{ $activeTemplate === 'minimalist' ? 'active' : '' }}">
                    🎨 Minimalis
                </a>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <button class="btn-print" onclick="window.print()">
                    🖨️ Cetak Invoice
                </button>
                @if(!empty($isPreview))
                    <button onclick="window.close()" class="tpl-btn" style="background: #e2e8f0; font-weight: 700;">
                        Tutup
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="page-wrapper">

        <!-- ═══════════════════════════════════════════════════════
             1. TEMPLATE: MODERN CLEAN (DEFAULT)
             ═══════════════════════════════════════════════════════ -->
        @if($activeTemplate === 'modern')
        <div class="invoice-modern">
            <div class="modern-header">
                <div class="modern-brand">
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" class="modern-logo">
                    @endif
                    <div>
                        <h1 class="modern-company-name">{{ $company['name'] }}</h1>
                        <p class="modern-company-sub">{{ $company['address'] }}</p>
                        @if(!empty($company['phone']) || !empty($company['email']))
                            <p class="modern-company-sub" style="opacity: 0.9;">
                                @if(!empty($company['phone'])) 📞 {{ $company['phone'] }} @endif
                                @if(!empty($company['phone']) && !empty($company['email'])) &nbsp;|&nbsp; @endif
                                @if(!empty($company['email'])) ✉️ {{ $company['email'] }} @endif
                            </p>
                        @endif
                    </div>
                </div>
                <div class="modern-inv-meta">
                    <span class="modern-inv-badge">INVOICE TAGIHAN</span>
                    <h2 class="modern-inv-num">{{ $invoice->invoice_number }}</h2>
                    <span class="modern-status-pill {{ $invoice->status == 'paid' ? 'pill-paid' : ($invoice->status == 'unpaid' ? 'pill-unpaid' : 'pill-partial') }}">
                        ● {{ $invoice->status == 'paid' ? 'LUNAS' : ($invoice->status == 'unpaid' ? 'BELUM LUNAS' : 'DIBAYAR SEBAGIAN') }}
                    </span>
                </div>
            </div>

            <div class="modern-body">
                <div class="modern-meta-grid">
                    <div class="meta-card">
                        <div class="meta-card-title">Ditagihkan Kepada</div>
                        <h3 class="meta-card-name">{{ $invoice->customer->name }}</h3>
                        <p class="meta-card-text"><strong>ID:</strong> {{ $invoice->customer->customer_code }}</p>
                        <p class="meta-card-text"><strong>Alamat:</strong> {{ $invoice->customer->address }}</p>
                        <p class="meta-card-text"><strong>Telepon:</strong> {{ $invoice->customer->phone }}</p>
                    </div>

                    <div class="meta-card">
                        <div class="meta-card-title">Informasi Tagihan</div>
                        <p class="meta-card-text"><strong>Periode Layanan:</strong> {{ $invoice->period_label }}</p>
                        <p class="meta-card-text"><strong>Tanggal Terbit:</strong> {{ $invoice->issued_date ? $invoice->issued_date->format('d M Y') : date('d M Y') }}</p>
                        <p class="meta-card-text" style="color: #e11d48;"><strong>Jatuh Tempo:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : date('d M Y') }}</p>
                        <p class="meta-card-text"><strong>Paket Internet:</strong> {{ $invoice->customer->package ? $invoice->customer->package->name : 'Layanan Internet' }}</p>
                    </div>
                </div>

                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Deskripsi Item</th>
                            <th>Periode</th>
                            <th class="text-right">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Langganan Internet {{ $invoice->customer->package ? $invoice->customer->package->name : '' }}</strong>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Akses Internet Unlimited Kecepatan Tinggi</div>
                            </td>
                            <td>{{ $invoice->period_label }}</td>
                            <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="modern-summary-wrap">
                    <div class="modern-banks">
                        <div class="modern-banks-header">
                            💳 Rekening Pembayaran Resmi
                        </div>
                        @if(!empty($banks) && count($banks) > 0)
                            @foreach($banks as $b)
                                <div class="modern-bank-item">
                                    <div>
                                        <div style="font-weight: 700; color: #1e293b; font-size: 12px;">{{ $b['bank_name'] ?? '-' }}</div>
                                        <div style="font-size: 11px; color: #64748b;">a.n. {{ $b['account_name'] ?? '-' }}</div>
                                    </div>
                                    <div class="modern-bank-acc">{{ $b['account_number'] ?? '-' }}</div>
                                </div>
                            @endforeach
                        @else
                            <p style="font-size: 11px; color: #64748b; margin: 0;">Silakan hubungi kasir/admin untuk instruksi pembayaran.</p>
                        @endif
                        <p style="font-size: 10px; color: #64748b; margin: 8px 0 0; line-height: 1.4;">
                            * Cantumkan nomor tagihan <strong>{{ $invoice->invoice_number }}</strong> pada berita transfer.
                        </p>
                    </div>

                    <div class="modern-totals">
                        <div class="modern-total-row">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($taxAmount > 0)
                        <div class="modern-total-row">
                            <span>PPN ({{ $taxPpn }}%)</span>
                            <span>Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="modern-total-row" style="border-top: 1px solid #f1f5f9; padding-top: 8px;">
                            <span>Total Tagihan</span>
                            <span style="font-weight: 700;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="modern-total-row" style="color: #059669;">
                            <span>Sudah Dibayar</span>
                            <span>Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                        </div>
                        <div class="modern-grand-total">
                            <span>Sisa Tagihan</span>
                            <span style="color: #4f46e5;">Rp {{ number_format($invoice->remaining, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div class="modern-stamp-row">
                    <div class="modern-stamp-box">
                        <div style="font-size: 11px; color: #64748b;">{{ $invoice->issued_date ? $invoice->issued_date->format('d M Y') : date('d M Y') }}</div>
                        <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 2px;">Bagian Keuangan,</div>
                        <div style="height: 70px; display: flex; align-items: center; justify-content: center; position: relative;">
                            @if(!empty($company['logo']))
                            <div class="official-stamp">
                                <div style="font-size: 8px; font-weight: 800; text-transform: uppercase;">{{ $company['name'] }}</div>
                                <div style="font-size: 9px; font-weight: 900; margin: 2px 0;">★ LUNAS ★</div>
                                <div style="font-size: 7px;">FINANCE DIVISION</div>
                            </div>
                            @endif
                        </div>
                        <div style="font-weight: 700; font-size: 12px; border-top: 1px solid #94a3b8; display: inline-block; padding-top: 4px; min-width: 140px;">
                            {{ $company['name'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             2. TEMPLATE: CLASSIC CORPORATE (Formal Letterhead)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'classic')
        <div class="invoice-classic">
            <div class="classic-header">
                @if(!empty($company['logo']))
                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 55px; margin-bottom: 6px;">
                @endif
                <h1>{{ $company['name'] }}</h1>
                <p>{{ $company['address'] }}</p>
                <p>Telp: {{ $company['phone'] ?? '-' }} | Email: {{ $company['email'] ?? '-' }} | Web: {{ $company['website'] ?? '-' }}</p>
            </div>

            <div class="classic-title-block">
                <h2>SURAT TAGIHAN / INVOICE</h2>
                <p>Nomor: {{ $invoice->invoice_number }}</p>
            </div>

            <div class="classic-grid">
                <table>
                    <tr>
                        <td style="width: 110px;"><strong>Kepada Yth:</strong></td>
                        <td>{{ $invoice->customer->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>ID Pelanggan:</strong></td>
                        <td>{{ $invoice->customer->customer_code }}</td>
                    </tr>
                    <tr>
                        <td><strong>Alamat:</strong></td>
                        <td>{{ $invoice->customer->address }}</td>
                    </tr>
                    <tr>
                        <td><strong>No. Telp/HP:</strong></td>
                        <td>{{ $invoice->customer->phone }}</td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <td style="width: 120px;"><strong>Tanggal Terbit:</strong></td>
                        <td>{{ $invoice->issued_date ? $invoice->issued_date->format('d F Y') : date('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Jatuh Tempo:</strong></td>
                        <td>{{ $invoice->due_date ? $invoice->due_date->format('d F Y') : date('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Periode Tagihan:</strong></td>
                        <td>{{ $invoice->period_label }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><strong>{{ $invoice->status == 'paid' ? 'LUNAS' : ($invoice->status == 'unpaid' ? 'BELUM LUNAS' : 'DIBAYAR SEBAGIAN') }}</strong></td>
                    </tr>
                </table>
            </div>

            <table class="classic-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Rincian Layanan / Deskripsi</th>
                        <th style="width: 150px;">Periode</th>
                        <th style="width: 160px;" class="right">Jumlah (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: center;">1</td>
                        <td>
                            Biaya Langganan Internet Bulanan
                            <div style="font-size: 11px; color: #555;">Paket: {{ $invoice->customer->package ? $invoice->customer->package->name : 'Broadband Internet' }}</div>
                        </td>
                        <td style="text-align: center;">{{ $invoice->period_label }}</td>
                        <td class="right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($taxAmount > 0)
                    <tr>
                        <td colspan="3" class="right"><strong>PPN ({{ $taxPpn }}%)</strong></td>
                        <td class="right">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="right"><strong>TOTAL TAGIHAN</strong></td>
                        <td class="right"><strong>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="right">Telah Dibayar</td>
                        <td class="right">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="background-color: #f8fafc;">
                        <td colspan="3" class="right"><strong>SISA PEMBAYARAN</strong></td>
                        <td class="right"><strong>Rp {{ number_format($invoice->remaining, 0, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="classic-footer-grid">
                <div>
                    <div style="border: 1px solid #000; padding: 10px; font-size: 11px;">
                        <strong>INSTRUKSI PEMBAYARAN:</strong>
                        <p style="margin: 4px 0;">Pembayaran dapat ditransfer melalui rekening:</p>
                        @foreach($banks as $b)
                            <div style="margin-top: 4px;">
                                - <strong>{{ $b['bank_name'] }}</strong>: {{ $b['account_number'] }} (a.n. {{ $b['account_name'] }})
                            </div>
                        @endforeach
                        <p style="margin: 6px 0 0; font-style: italic;">* Harap konfirmasi bukti transfer jika telah melakukan pembayaran.</p>
                    </div>
                </div>

                <div class="classic-signature">
                    <p>{{ date('d F Y') }}</p>
                    <p style="margin-top: 4px;">Hormat kami,</p>
                    <div style="height: 65px;"></div>
                    <p style="text-decoration: underline; font-weight: bold; margin: 0;">( Bagian Keuangan )</p>
                    <p style="margin: 2px 0 0;">{{ $company['name'] }}</p>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             3. TEMPLATE: THERMAL / STRUK POS (58mm / 80mm)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'thermal')
        <div class="invoice-thermal">
            <div class="thermal-center">
                <div class="thermal-bold" style="font-size: 14px;">{{ $company['name'] }}</div>
                <div>{{ $company['address'] }}</div>
                @if(!empty($company['phone']))
                    <div>Telp: {{ $company['phone'] }}</div>
                @endif
                <div class="thermal-divider"></div>
                <div class="thermal-bold" style="font-size: 12px; letter-spacing: 1px;">STRUK TAGIHAN INTERNET</div>
                <div>NO: {{ $invoice->invoice_number }}</div>
                <div>TGL: {{ $invoice->issued_date ? $invoice->issued_date->format('d/m/Y') : date('d/m/Y') }}</div>
            </div>

            <div class="thermal-divider"></div>

            <div class="thermal-row">
                <span>Pelanggan</span>
                <span class="thermal-bold">{{ $invoice->customer->name }}</span>
            </div>
            <div class="thermal-row">
                <span>ID Pelanggan</span>
                <span>{{ $invoice->customer->customer_code }}</span>
            </div>
            <div class="thermal-row">
                <span>Paket</span>
                <span>{{ $invoice->customer->package ? $invoice->customer->package->name : '-' }}</span>
            </div>
            <div class="thermal-row">
                <span>Periode</span>
                <span>{{ $invoice->period_label }}</span>
            </div>
            <div class="thermal-row">
                <span>Jatuh Tempo</span>
                <span>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</span>
            </div>

            <div class="thermal-divider"></div>

            <div class="thermal-row">
                <span>Tagihan Pokok</span>
                <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
            </div>
            @if($taxAmount > 0)
            <div class="thermal-row">
                <span>PPN ({{ $taxPpn }}%)</span>
                <span>Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="thermal-row thermal-bold" style="font-size: 12px;">
                <span>TOTAL TAGIHAN</span>
                <span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
            </div>
            <div class="thermal-row">
                <span>Telah Dibayar</span>
                <span>Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
            </div>
            <div class="thermal-row thermal-bold">
                <span>SISA TAGIHAN</span>
                <span>Rp {{ number_format($invoice->remaining, 0, ',', '.') }}</span>
            </div>

            <div class="thermal-center" style="margin-top: 8px;">
                <div class="thermal-status">
                    STATUS: {{ $invoice->status == 'paid' ? 'LUNAS' : ($invoice->status == 'unpaid' ? 'BELUM LUNAS' : 'SEBAGIAN') }}
                </div>
            </div>

            @if(!empty($banks) && count($banks) > 0)
            <div class="thermal-divider"></div>
            <div class="thermal-center" style="font-size: 10px;">
                <div>PEMBAYARAN VIA TRANSFER:</div>
                @foreach($banks as $b)
                    <div>{{ $b['bank_name'] }}: {{ $b['account_number'] }} ({{ $b['account_name'] }})</div>
                @endforeach
            </div>
            @endif

            <div class="thermal-divider"></div>
            <div class="thermal-center" style="font-size: 10px;">
                <div>Simpan struk ini sebagai bukti sah.</div>
                <div>Terima kasih atas pembayaran Anda!</div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             4. TEMPLATE: ELEGANT MINIMALIST (Tech ISP)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'minimalist')
        <div class="invoice-minimalist">
            <div class="mini-header">
                <div class="mini-brand">
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 45px; margin-bottom: 8px;">
                    @endif
                    <h1>{{ $company['name'] }}</h1>
                    <p style="margin: 3px 0 0; color: #64748b; font-size: 12px;">{{ $company['address'] }}</p>
                </div>
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; text-align: right;">INVOICE STATEMENT</div>
                    <div class="mini-number">{{ $invoice->invoice_number }}</div>
                    <div style="text-align: right; margin-top: 4px;">
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 800; background: #0f172a; color: #ffffff;">
                            {{ $invoice->status == 'paid' ? 'PAID' : ($invoice->status == 'unpaid' ? 'UNPAID' : 'PARTIAL') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mini-meta-row">
                <div style="flex: 1;">
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Billed To</div>
                    <div style="font-size: 15px; font-weight: 700; color: #0f172a;">{{ $invoice->customer->name }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $invoice->customer->customer_code }}</div>
                    <div style="font-size: 12px; color: #64748b;">{{ $invoice->customer->address }}</div>
                    <div style="font-size: 12px; color: #64748b;">{{ $invoice->customer->phone }}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Billing Details</div>
                    <div style="font-size: 12px; color: #0f172a;"><strong>Issued:</strong> {{ $invoice->issued_date ? $invoice->issued_date->format('d M Y') : date('d M Y') }}</div>
                    <div style="font-size: 12px; color: #e11d48;"><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : date('d M Y') }}</div>
                    <div style="font-size: 12px; color: #0f172a;"><strong>Cycle:</strong> {{ $invoice->period_label }}</div>
                </div>
            </div>

            <table class="mini-table">
                <thead>
                    <tr>
                        <th>Service Description</th>
                        <th>Period</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong style="color: #0f172a;">{{ $invoice->customer->package ? $invoice->customer->package->name : 'Internet Plan' }}</strong>
                            <div style="font-size: 11px; color: #64748b;">High-Speed Dedicated Broadband Connectivity</div>
                        </td>
                        <td>{{ $invoice->period_label }}</td>
                        <td style="text-align: right; font-weight: 600;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($taxAmount > 0)
                    <tr>
                        <td colspan="2" style="text-align: right; color: #64748b;">VAT / PPN ({{ $taxPpn }}%)</td>
                        <td style="text-align: right;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <div class="mini-total-card">
                <div>
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Remaining Balance Due</div>
                    <div style="font-size: 22px; font-weight: 800; letter-spacing: -0.5px; margin-top: 2px;">
                        Rp {{ number_format($invoice->remaining, 0, ',', '.') }}
                    </div>
                </div>
                <div style="text-align: right; font-size: 12px;">
                    <div>Total Billed: Rp {{ number_format($invoice->amount, 0, ',', '.') }}</div>
                    <div style="color: #34d399; margin-top: 2px;">Paid: Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 35px;">
                <div style="font-size: 11px; color: #64748b; max-width: 400px;">
                    <strong style="color: #0f172a;">Payment Transfer:</strong>
                    @foreach($banks as $b)
                        <div style="margin-top: 2px;">• {{ $b['bank_name'] }} <strong>{{ $b['account_number'] }}</strong> a.n. {{ $b['account_name'] }}</div>
                    @endforeach
                </div>
                <div style="text-align: right; font-size: 11px; color: #94a3b8;">
                    <div>Authorized by {{ $company['name'] }}</div>
                    <div style="margin-top: 4px; font-weight: bold; color: #0f172a;">FINANCE SYSTEM</div>
                </div>
            </div>
        </div>
        @endif

    </div>

</body>
</html>
