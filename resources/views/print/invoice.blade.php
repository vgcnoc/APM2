<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }} - {{ $company['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Courier+Prime:ital,wght@0,400;0,700;1,400&family=JetBrains+Mono:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
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

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 5: FUTURISTIC FIBER MESH (geometric_mesh)
           ═════════════════════════════════════════════════════════ */
        .invoice-geometric-mesh {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(2, 132, 199, 0.12);
            border: 1px solid #0284c7;
            overflow: hidden;
            font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
        }
        .mesh-header {
            background-color: #0c192c;
            background-image: 
                linear-gradient(135deg, rgba(9, 13, 22, 0.95) 0%, rgba(15, 23, 42, 0.92) 50%, rgba(30, 27, 75, 0.94) 100%),
                url("data:image/svg+xml,%3Csvg width='40' height='69.282' viewBox='0 0 40 69.282' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M40 34.641l-20 11.547L0 34.641V11.547L20 0l20 11.547v23.094zm0 34.641l-20 11.547L0 69.282V46.188L20 34.641l20 11.547v23.094zM0 34.641l20-11.547 20 11.547v23.094L20 69.282 0 57.735V34.641z' fill='none' stroke='rgba(56,189,248,0.22)' stroke-width='1'/%3E%3C/svg%3E");
            padding: 32px 36px;
            color: #ffffff;
            border-bottom: 3px solid #06b6d4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        .mesh-header::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 140px;
            height: 3px;
            background: #38bdf8;
            box-shadow: 0 0 12px #38bdf8;
        }
        .mesh-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .mesh-logo {
            max-height: 54px;
            background: rgba(255, 255, 255, 0.95);
            padding: 6px;
            border-radius: 10px;
            border: 1px solid rgba(56, 189, 248, 0.4);
        }
        .mesh-company-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
            color: #f8fafc;
        }
        .mesh-company-sub {
            font-size: 11px;
            color: #94a3b8;
            margin: 3px 0 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .mesh-inv-meta {
            text-align: right;
        }
        .mesh-tag {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.35);
            padding: 3px 10px;
            border-radius: 6px;
            margin-bottom: 6px;
        }
        .mesh-inv-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .mesh-body {
            padding: 32px 36px 40px;
            background: #ffffff;
        }
        .mesh-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .mesh-card {
            background: #f8fafc;
            border: 1px solid #e0f2fe;
            border-left: 4px solid #0284c7;
            border-radius: 12px;
            padding: 16px 20px;
            position: relative;
            background-image: radial-gradient(rgba(2, 132, 199, 0.04) 1px, transparent 0);
            background-size: 14px 14px;
        }
        .mesh-card-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #0284c7;
            margin-bottom: 6px;
        }
        .mesh-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .mesh-table thead tr {
            background: linear-gradient(90deg, #0369a1 0%, #0284c7 50%, #4338ca 100%);
            color: #ffffff;
        }
        .mesh-table th {
            padding: 12px 18px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: left;
        }
        .mesh-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #1e293b;
        }
        .mesh-table tbody tr:nth-child(even) {
            background-color: #f0f9ff;
        }
        .mesh-summary-wrap {
            display: flex;
            gap: 24px;
        }
        .mesh-totals {
            width: 330px;
            background: linear-gradient(135deg, #0c192c 0%, #0369a1 100%);
            color: #ffffff;
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 8px 20px rgba(3, 105, 161, 0.25);
        }
        .mesh-stamp {
            border: 2px solid #0284c7;
            border-radius: 12px;
            padding: 8px 14px;
            text-align: center;
            color: #0284c7;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 1px;
            background: #f0f9ff;
            display: inline-block;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 6: ROYAL GOLD & CARBON FIBER (luxury_gold)
           ═════════════════════════════════════════════════════════ */
        .invoice-luxury-gold {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border: 2px solid #b8860b;
            border-radius: 8px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            position: relative;
        }
        .gold-header {
            background-color: #141518;
            background-image: 
                linear-gradient(45deg, #1d1f24 25%, transparent 25%), 
                linear-gradient(-45deg, #1d1f24 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, #1d1f24 75%), 
                linear-gradient(-45deg, transparent 75%, #1d1f24 75%);
            background-size: 10px 10px;
            background-position: 0 0, 0 5px, 5px -5px, -5px 0px;
            padding: 34px 38px;
            border-bottom: 3px double #d4af37;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fbf8f0;
        }
        .gold-company-name {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #f5d77f;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        .gold-company-sub {
            font-size: 11px;
            color: #d1d5db;
            margin: 4px 0 0;
            letter-spacing: 0.3px;
        }
        .gold-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f5d77f 0%, #d4af37 50%, #aa771c 100%);
            color: #141518;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            padding: 4px 14px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(212, 175, 55, 0.4);
            margin-bottom: 6px;
        }
        .gold-inv-num {
            font-family: 'Cinzel', serif;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            letter-spacing: 1px;
        }
        .gold-body {
            padding: 34px 38px 40px;
            background: #ffffff;
        }
        .gold-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
        }
        .gold-card {
            border: 1px solid #e5c07b;
            background: #fdfcf9;
            border-radius: 6px;
            padding: 16px 20px;
            position: relative;
        }
        .gold-card::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            bottom: 2px;
            border: 1px dashed #f3dfaa;
            border-radius: 4px;
            pointer-events: none;
        }
        .gold-card-title {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            font-weight: 700;
            color: #996515;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .gold-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .gold-table th {
            background: #141518;
            color: #f5d77f;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 2px solid #d4af37;
        }
        .gold-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f3dfaa;
            font-size: 13px;
        }
        .gold-totals {
            width: 330px;
            background: #141518;
            border: 1.5px solid #d4af37;
            border-radius: 8px;
            padding: 18px 22px;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .gold-seal {
            width: 130px;
            height: 130px;
            border: 2px solid #b8860b;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #b8860b;
            padding: 8px;
            margin: auto;
            position: relative;
            background: rgba(253, 252, 249, 0.8);
            transform: rotate(-6deg);
        }
        .gold-seal::after {
            content: '';
            position: absolute;
            inset: 4px;
            border: 1px dashed #b8860b;
            border-radius: 50%;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 7: CYBER NOC CIRCUIT (cyber_circuit)
           ═════════════════════════════════════════════════════════ */
        .invoice-cyber-circuit {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border: 1px solid #10b981;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.12);
            overflow: hidden;
            font-family: 'JetBrains Mono', monospace;
        }
        .circuit-header {
            background-color: #041017;
            background-image: 
                linear-gradient(135deg, rgba(4, 16, 23, 0.94) 0%, rgba(2, 44, 34, 0.92) 100%),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M10 0v20h20v20h20v20M0 40h20v20M40 0h20v20M0 10h10v20M50 40v20' fill='none' stroke='rgba(16,185,129,0.26)' stroke-width='1.5'/%3E%3Ccircle cx='10' cy='20' r='2.5' fill='rgba(16,185,129,0.7)'/%3E%3Ccircle cx='30' cy='40' r='2.5' fill='rgba(16,185,129,0.7)'/%3E%3Ccircle cx='50' cy='20' r='2.5' fill='rgba(16,185,129,0.7)'/%3E%3C/svg%3E");
            padding: 30px 36px;
            color: #ffffff;
            border-bottom: 2px solid #10b981;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .circuit-title {
            font-size: 20px;
            font-weight: 800;
            color: #34d399;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .circuit-meta-pill {
            display: inline-block;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid #10b981;
            color: #34d399;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .circuit-body {
            padding: 30px 36px 36px;
            background: #ffffff;
        }
        .circuit-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 26px;
        }
        .circuit-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 16px;
            font-size: 12px;
            position: relative;
        }
        .circuit-card::before {
            content: '// SPEC_BLOCK';
            position: absolute;
            top: -9px;
            right: 12px;
            font-size: 9px;
            font-weight: 700;
            color: #059669;
            background: #ffffff;
            padding: 0 6px;
            border: 1px solid #bbf7d0;
            border-radius: 3px;
        }
        .circuit-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 26px;
        }
        .circuit-table th {
            background: #022c22;
            color: #34d399;
            font-size: 11px;
            padding: 10px 14px;
            text-align: left;
            border: 1px solid #065f46;
        }
        .circuit-table td {
            padding: 12px 14px;
            border: 1px solid #dcfce7;
            font-size: 12px;
        }
        .circuit-totals {
            width: 320px;
            background: #041017;
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 16px 20px;
            color: #ffffff;
            font-size: 12px;
        }
        .circuit-stamp {
            border: 1.5px dashed #10b981;
            padding: 10px 14px;
            text-align: center;
            color: #059669;
            font-size: 10px;
            background: #f0fdf4;
            border-radius: 6px;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 8: GUILLOCHE SECURITY WAVE (corporate_wave)
           ═════════════════════════════════════════════════════════ */
        .invoice-corporate-wave {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border: 2px solid #0f766e;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 118, 110, 0.1);
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            position: relative;
        }
        .wave-header {
            background-color: #0e3a63;
            background-image: 
                linear-gradient(135deg, rgba(14, 58, 99, 0.95) 0%, rgba(15, 118, 110, 0.92) 100%),
                url("data:image/svg+xml,%3Csvg width='80' height='40' viewBox='0 0 80 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 20 Q20 5, 40 20 T80 20 M0 25 Q20 10, 40 25 T80 25 M0 15 Q20 0, 40 15 T80 15 M0 30 Q20 15, 40 30 T80 30' fill='none' stroke='rgba(255,255,255,0.2)' stroke-width='1'/%3E%3C/svg%3E");
            padding: 32px 38px;
            color: #ffffff;
            border-bottom: 3px solid #14b8a6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .wave-company-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin: 0;
            color: #ffffff;
        }
        .wave-body {
            padding: 32px 38px 40px;
            background: #ffffff;
            position: relative;
        }
        .wave-body::after {
            content: 'SECURE VALIDATED INVOICE';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 40px;
            font-weight: 900;
            color: rgba(15, 118, 110, 0.04);
            letter-spacing: 6px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
        }
        .wave-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        .wave-card {
            background: #f0fdfa;
            border: 1px solid #ccfbf1;
            border-left: 4px solid #0f766e;
            border-radius: 8px;
            padding: 16px 20px;
        }
        .wave-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        .wave-table th {
            background: #0f766e;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            text-align: left;
        }
        .wave-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .wave-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .wave-totals {
            width: 330px;
            background: linear-gradient(135deg, #0e3a63 0%, #0f766e 100%);
            color: #ffffff;
            border-radius: 10px;
            padding: 18px 22px;
            box-shadow: 0 4px 15px rgba(15, 118, 110, 0.2);
            position: relative;
            z-index: 1;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 9: VINTAGE POSTAL KRAFT (retro_kraft)
           ═════════════════════════════════════════════════════════ */
        .invoice-retro-kraft {
            width: 100%;
            max-width: 820px;
            background-color: #faf7f0;
            background-image: radial-gradient(rgba(120, 53, 15, 0.06) 1px, transparent 0);
            background-size: 16px 16px;
            border: 3px double #78350f;
            border-radius: 4px;
            box-shadow: 0 8px 25px rgba(120, 53, 15, 0.08);
            padding: 38px 42px;
            font-family: 'Courier Prime', Courier, monospace;
            color: #291507;
            position: relative;
        }
        .retro-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #78350f;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .retro-company-name {
            font-size: 22px;
            font-weight: 700;
            color: #451a03;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .retro-postmark {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .retro-stamp-circle {
            width: 80px;
            height: 80px;
            border: 2px dashed #991b1b;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #991b1b;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            line-height: 1.2;
            transform: rotate(-12deg);
        }
        .retro-wavy-lines {
            font-size: 22px;
            color: #991b1b;
            line-height: 0.6;
            letter-spacing: -2px;
            font-weight: bold;
        }
        .retro-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
            border: 1px solid #78350f;
            background: #fffdf9;
            padding: 16px 20px;
        }
        .retro-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #78350f;
            margin-bottom: 24px;
            background: #fffdf9;
        }
        .retro-table th {
            background: #f5eedf;
            border: 1px solid #78350f;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            text-align: left;
        }
        .retro-table td {
            border: 1px solid #78350f;
            padding: 12px 14px;
            font-size: 12px;
        }
        .retro-totals {
            width: 320px;
            border: 2px solid #78350f;
            background: #fffdf9;
            padding: 16px 20px;
        }

        /* ═════════════════════════════════════════════════════════
           TEMPLATE 10: DYNAMIC AURORA FLOW (aurora_flow)
           ═════════════════════════════════════════════════════════ */
        .invoice-aurora-flow {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(124, 58, 237, 0.12);
            border: 1px solid rgba(124, 58, 237, 0.2);
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .aurora-header {
            background-color: #4338ca;
            background-image: 
                linear-gradient(135deg, #4338ca 0%, #7c3aed 30%, #c026d3 65%, #06b6d4 100%),
                url("data:image/svg+xml,%3Csvg width='120' height='60' viewBox='0 0 120 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 30 C30 10, 70 50, 120 25 L120 60 L0 60 Z' fill='rgba(255,255,255,0.08)'/%3E%3Cpath d='M0 45 C40 25, 80 55, 120 35' fill='none' stroke='rgba(255,255,255,0.18)' stroke-width='1.5'/%3E%3C/svg%3E");
            padding: 34px 40px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .aurora-company-name {
            font-size: 24px;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .aurora-pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 6px;
        }
        .aurora-body {
            padding: 34px 40px 40px;
            background: #ffffff;
        }
        .aurora-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
        }
        .aurora-card {
            background: #faf5ff;
            border: 1px solid #f3e8ff;
            border-top: 3px solid #a855f7;
            border-radius: 14px;
            padding: 18px 22px;
        }
        .aurora-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #f3e8ff;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .aurora-table thead tr {
            background: linear-gradient(90deg, #7c3aed 0%, #a855f7 50%, #c026d3 100%);
            color: #ffffff;
        }
        .aurora-table th {
            padding: 13px 18px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: left;
        }
        .aurora-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #faf5ff;
            font-size: 13px;
        }
        .aurora-totals {
            width: 330px;
            background: linear-gradient(135deg, #4338ca 0%, #7c3aed 100%);
            color: #ffffff;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.25);
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
            .invoice-minimalist,
            .invoice-geometric-mesh,
            .invoice-luxury-gold,
            .invoice-cyber-circuit,
            .invoice-corporate-wave,
            .invoice-retro-kraft,
            .invoice-aurora-flow {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                border-radius: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
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

            <div class="template-selector" style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; padding-left: 6px;">Pilih Template:</span>
                <select onchange="window.location.href='?template=' + this.value" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; cursor: pointer; outline: none;">
                    <option value="modern" {{ $activeTemplate === 'modern' ? 'selected' : '' }}>🌟 Modern Clean (Default)</option>
                    <option value="geometric_mesh" {{ $activeTemplate === 'geometric_mesh' ? 'selected' : '' }}>⚡ Futuristic Fiber Mesh (Motif Heksagonal)</option>
                    <option value="luxury_gold" {{ $activeTemplate === 'luxury_gold' ? 'selected' : '' }}>👑 Royal Gold & Carbon (Motif Serat Karbon)</option>
                    <option value="cyber_circuit" {{ $activeTemplate === 'cyber_circuit' ? 'selected' : '' }}>🌐 Cyber NOC Circuit (Motif PCB Emerald)</option>
                    <option value="corporate_wave" {{ $activeTemplate === 'corporate_wave' ? 'selected' : '' }}>🌊 Guilloche Security Wave (Motif Gelombang)</option>
                    <option value="aurora_flow" {{ $activeTemplate === 'aurora_flow' ? 'selected' : '' }}>✨ Dynamic Aurora Flow (Motif Prisma)</option>
                    <option value="retro_kraft" {{ $activeTemplate === 'retro_kraft' ? 'selected' : '' }}>📜 Vintage Postal Kraft (Motif Perangko)</option>
                    <option value="classic" {{ $activeTemplate === 'classic' ? 'selected' : '' }}>🏛️ Classic Corporate (Formal)</option>
                    <option value="minimalist" {{ $activeTemplate === 'minimalist' ? 'selected' : '' }}>🎨 Elegant Minimalist (Tech)</option>
                    <option value="thermal" {{ $activeTemplate === 'thermal' ? 'selected' : '' }}>🧾 Struk POS Thermal (58/80mm)</option>
                </select>
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

        <!-- ═══════════════════════════════════════════════════════
             5. TEMPLATE: FUTURISTIC FIBER MESH (geometric_mesh)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'geometric_mesh')
        <div class="invoice-geometric-mesh">
            <div class="mesh-header">
                <div class="mesh-brand">
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" class="mesh-logo">
                    @endif
                    <div>
                        <h1 class="mesh-company-name">{{ $company['name'] }}</h1>
                        <p class="mesh-company-sub">{{ $company['address'] }}</p>
                        @if(!empty($company['phone']) || !empty($company['email']))
                            <p class="mesh-company-sub" style="color: #38bdf8;">
                                @if(!empty($company['phone'])) 📞 {{ $company['phone'] }} @endif
                                @if(!empty($company['phone']) && !empty($company['email'])) &nbsp;|&nbsp; @endif
                                @if(!empty($company['email'])) ✉️ {{ $company['email'] }} @endif
                            </p>
                        @endif
                    </div>
                </div>
                <div class="mesh-inv-meta">
                    <span class="mesh-tag">⚡ FIBER OPTIC BROADBAND</span>
                    <h2 class="mesh-inv-num">{{ $invoice->invoice_number }}</h2>
                    <div style="margin-top: 6px;">
                        <span style="display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 11px; font-weight: 800; font-family: 'JetBrains Mono', monospace; {{ $invoice->status == 'paid' ? 'background: #059669; color: #ffffff;' : ($invoice->status == 'unpaid' ? 'background: #e11d48; color: #ffffff;' : 'background: #d97706; color: #ffffff;') }}">
                            ● {{ $invoice->status == 'paid' ? 'LUNAS / PAID' : ($invoice->status == 'unpaid' ? 'BELUM LUNAS / UNPAID' : 'DIBAYAR SEBAGIAN') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mesh-body">
                <div class="mesh-meta-grid">
                    <div class="mesh-card">
                        <div class="mesh-card-title">Informasi Pelanggan</div>
                        <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">{{ $invoice->customer->name }}</h3>
                        <p style="font-size: 12px; color: #475569; margin: 2px 0;"><strong>CID:</strong> <span style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #0284c7;">{{ $invoice->customer->customer_code }}</span></p>
                        <p style="font-size: 12px; color: #475569; margin: 2px 0;"><strong>Alamat:</strong> {{ $invoice->customer->address }}</p>
                        <p style="font-size: 12px; color: #475569; margin: 2px 0;"><strong>Kontak:</strong> {{ $invoice->customer->phone }}</p>
                    </div>

                    <div class="mesh-card">
                        <div class="mesh-card-title">Detail Tagihan & Layanan</div>
                        <p style="font-size: 12px; color: #475569; margin: 2px 0;"><strong>Paket Internet:</strong> <span style="font-weight: 700; color: #0284c7;">{{ $invoice->customer->package ? $invoice->customer->package->name : 'Internet Broadband' }}</span></p>
                        <p style="font-size: 12px; color: #475569; margin: 2px 0;"><strong>Periode Layanan:</strong> {{ $invoice->period_label }}</p>
                        <p style="font-size: 12px; color: #475569; margin: 2px 0;"><strong>Tanggal Terbit:</strong> {{ $invoice->issued_date ? $invoice->issued_date->format('d M Y') : date('d M Y') }}</p>
                        <p style="font-size: 12px; color: #e11d48; margin: 2px 0;"><strong>Batas Jatuh Tempo:</strong> <span style="font-weight: 800;">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : date('d M Y') }}</span></p>
                    </div>
                </div>

                <table class="mesh-table">
                    <thead>
                        <tr>
                            <th>Deskripsi Item Layanan</th>
                            <th>Periode</th>
                            <th style="text-align: right;">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong style="color: #0f172a; font-size: 13px;">Langganan Fiber Broadband {{ $invoice->customer->package ? $invoice->customer->package->name : '' }}</strong>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Dedicated High Speed Internet Access, Unlimited Quota & Low Latency SLA</div>
                            </td>
                            <td style="font-weight: 600; color: #475569;">{{ $invoice->period_label }}</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 13px; color: #0f172a;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($taxAmount > 0)
                        <tr>
                            <td colspan="2" style="text-align: right; font-weight: 600; color: #64748b; font-size: 12px;">PPN ({{ $taxPpn }}%)</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 12px;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div class="mesh-summary-wrap">
                    <div style="flex: 1; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 14px; padding: 18px 22px;">
                        <div style="font-size: 11px; font-weight: 800; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                            💳 Kanal Pembayaran Resmi
                        </div>
                        @if(!empty($banks) && count($banks) > 0)
                            @foreach($banks as $b)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #bae6fd; font-size: 12px;">
                                    <div>
                                        <span style="font-weight: 700; color: #0f172a;">{{ $b['bank_name'] }}</span>
                                        <span style="color: #64748b; font-size: 11px;">(a.n. {{ $b['account_name'] }})</span>
                                    </div>
                                    <div style="font-family: 'JetBrains Mono', monospace; font-weight: 800; color: #0284c7;">{{ $b['account_number'] }}</div>
                                </div>
                            @endforeach
                        @endif
                        <p style="font-size: 11px; color: #64748b; margin: 10px 0 0; font-style: italic;">
                            Sertakan nomor invoice pada berita transfer untuk percepatan verifikasi sistem.
                        </p>
                    </div>

                    <div class="mesh-totals">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: #94a3b8;">
                            <span>Subtotal Layanan:</span>
                            <span style="font-family: 'JetBrains Mono', monospace; color: #ffffff;">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($taxAmount > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: #94a3b8;">
                            <span>PPN ({{ $taxPpn }}%):</span>
                            <span style="font-family: 'JetBrains Mono', monospace; color: #ffffff;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: #94a3b8;">
                            <span>Total Tagihan:</span>
                            <span style="font-family: 'JetBrains Mono', monospace; color: #ffffff;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 12px; color: #34d399;">
                            <span>Telah Dibayar:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                        </div>
                        <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 10px; display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-size: 12px; font-weight: 700; color: #38bdf8; text-transform: uppercase;">Sisa Tagihan</span>
                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 800; color: #38bdf8;">
                                Rp {{ number_format($invoice->remaining, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 35px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                    <div>
                        <div class="mesh-stamp">
                            VERIFIED NOC TELEMETRY • HIGH SPEED SECURE
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #64748b;">
                        <div>Diterbitkan secara elektronik oleh:</div>
                        <div style="font-weight: 800; color: #0f172a; font-size: 12px; margin-top: 2px;">{{ $company['name'] }}</div>
                        <div style="font-family: 'JetBrains Mono', monospace; font-size: 10px; color: #94a3b8; margin-top: 2px;">DOC_ID: {{ md5($invoice->invoice_number) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             6. TEMPLATE: ROYAL GOLD & CARBON FIBER (luxury_gold)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'luxury_gold')
        <div class="invoice-luxury-gold">
            <div class="gold-header">
                <div style="display: flex; align-items: center; gap: 18px;">
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 56px; background: rgba(255,255,255,0.9); padding: 6px; border-radius: 6px; border: 1.5px solid #d4af37;">
                    @endif
                    <div>
                        <h1 class="gold-company-name">{{ $company['name'] }}</h1>
                        <p class="gold-company-sub">{{ $company['address'] }}</p>
                        @if(!empty($company['phone']) || !empty($company['email']))
                            <p class="gold-company-sub" style="color: #f5d77f;">
                                @if(!empty($company['phone'])) Telp: {{ $company['phone'] }} @endif
                                @if(!empty($company['phone']) && !empty($company['email'])) &nbsp;◆&nbsp; @endif
                                @if(!empty($company['email'])) Email: {{ $company['email'] }} @endif
                            </p>
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="gold-badge">VIP ENTERPRISE STATEMENT</span>
                    <h2 class="gold-inv-num">{{ $invoice->invoice_number }}</h2>
                    <div style="margin-top: 6px;">
                        <span style="font-family: 'Cinzel', serif; font-size: 11px; font-weight: 800; letter-spacing: 1px; color: {{ $invoice->status == 'paid' ? '#34d399' : ($invoice->status == 'unpaid' ? '#f87171' : '#fbbf24') }};">
                            ◆ {{ $invoice->status == 'paid' ? 'PAID / LUNAS' : ($invoice->status == 'unpaid' ? 'UNPAID / BELUM LUNAS' : 'PARTIALLY PAID') }} ◆
                        </span>
                    </div>
                </div>
            </div>

            <div class="gold-body">
                <div class="gold-grid">
                    <div class="gold-card">
                        <div class="gold-card-title">Client Account Specification</div>
                        <h3 style="font-family: 'Cinzel', serif; font-size: 15px; font-weight: 700; color: #141518; margin: 0 0 6px;">{{ $invoice->customer->name }}</h3>
                        <p style="font-size: 12px; color: #4b5563; margin: 2px 0;"><strong>Customer Code:</strong> <span style="font-family: 'JetBrains Mono', monospace; color: #996515; font-weight: 700;">{{ $invoice->customer->customer_code }}</span></p>
                        <p style="font-size: 12px; color: #4b5563; margin: 2px 0;"><strong>Address:</strong> {{ $invoice->customer->address }}</p>
                        <p style="font-size: 12px; color: #4b5563; margin: 2px 0;"><strong>Contact:</strong> {{ $invoice->customer->phone }}</p>
                    </div>

                    <div class="gold-card">
                        <div class="gold-card-title">Billing & Service Terms</div>
                        <p style="font-size: 12px; color: #4b5563; margin: 2px 0;"><strong>Subscribed Tier:</strong> <span style="font-weight: 700; color: #996515;">{{ $invoice->customer->package ? $invoice->customer->package->name : 'VIP Enterprise Broadband' }}</span></p>
                        <p style="font-size: 12px; color: #4b5563; margin: 2px 0;"><strong>Billing Period:</strong> {{ $invoice->period_label }}</p>
                        <p style="font-size: 12px; color: #4b5563; margin: 2px 0;"><strong>Statement Date:</strong> {{ $invoice->issued_date ? $invoice->issued_date->format('d F Y') : date('d F Y') }}</p>
                        <p style="font-size: 12px; color: #dc2626; margin: 2px 0;"><strong>Due Date:</strong> <span style="font-weight: 700;">{{ $invoice->due_date ? $invoice->due_date->format('d F Y') : date('d F Y') }}</span></p>
                    </div>
                </div>

                <table class="gold-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Service Description</th>
                            <th>Billing Cycle</th>
                            <th style="text-align: right;">Amount (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong style="color: #141518; font-size: 13px;">Dedicated Enterprise Internet {{ $invoice->customer->package ? $invoice->customer->package->name : '' }}</strong>
                                <div style="font-size: 11px; color: #6b7280; margin-top: 2px;">Premium Symmetrical Bandwidth, Priority Core Routing & 24/7 SLA Guarantee</div>
                            </td>
                            <td style="color: #4b5563; font-weight: 600;">{{ $invoice->period_label }}</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #141518;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($taxAmount > 0)
                        <tr>
                            <td colspan="2" style="text-align: right; color: #6b7280; font-size: 12px;">Value Added Tax (PPN {{ $taxPpn }}%)</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div style="display: flex; gap: 24px; align-items: flex-start;">
                    <div style="flex: 1; border: 1px solid #e5c07b; background: #fdfcf9; border-radius: 8px; padding: 18px 22px;">
                        <div style="font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; color: #996515; letter-spacing: 1px; margin-bottom: 10px;">
                            Official Remittance Bank Accounts
                        </div>
                        @if(!empty($banks) && count($banks) > 0)
                            @foreach($banks as $b)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #f3dfaa; font-size: 12px;">
                                    <div>
                                        <span style="font-weight: 700; color: #141518;">{{ $b['bank_name'] }}</span>
                                        <span style="color: #6b7280; font-size: 11px;">(a.n. {{ $b['account_name'] }})</span>
                                    </div>
                                    <div style="font-family: 'JetBrains Mono', monospace; font-weight: 800; color: #996515;">{{ $b['account_number'] }}</div>
                                </div>
                            @endforeach
                        @endif
                        <p style="font-size: 11px; color: #6b7280; margin: 10px 0 0; font-style: italic;">
                            Please notify finance remittance confirmation upon settling payment.
                        </p>
                    </div>

                    <div class="gold-totals">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; color: #d1d5db;">
                            <span>Subtotal:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($taxAmount > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; color: #d1d5db;">
                            <span>VAT ({{ $taxPpn }}%):</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; color: #d1d5db;">
                            <span>Gross Total:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 12px; color: #34d399;">
                            <span>Total Paid:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                        </div>
                        <div style="border-top: 1px solid #d4af37; padding-top: 10px; display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-family: 'Cinzel', serif; font-size: 12px; font-weight: 700; color: #f5d77f; letter-spacing: 0.5px;">Balance Due</span>
                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 800; color: #f5d77f;">
                                Rp {{ number_format($invoice->remaining, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 35px; padding-top: 20px; border-top: 1.5px solid #e5c07b;">
                    <div>
                        <div class="gold-seal">
                            <span style="font-size: 8px; letter-spacing: 1px; font-weight: 800;">★ VIP ISP SEAL ★</span>
                            <span style="font-size: 10px; font-weight: 800; margin: 3px 0; font-family: 'Cinzel', serif;">VERIFIED</span>
                            <span style="font-size: 7px; letter-spacing: 0.5px;">OFFICIAL INVOICE</span>
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #6b7280;">
                        <div>Corporate Finance Division</div>
                        <div style="font-family: 'Cinzel', serif; font-weight: 800; color: #141518; font-size: 13px; margin-top: 2px;">{{ $company['name'] }}</div>
                        <div style="font-style: italic; margin-top: 4px;">Thank you for your valued enterprise partnership.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             7. TEMPLATE: CYBER NOC CIRCUIT (cyber_circuit)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'cyber_circuit')
        <div class="invoice-cyber-circuit">
            <div class="circuit-header">
                <div style="display: flex; align-items: center; gap: 16px;">
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 48px; background: rgba(255,255,255,0.9); padding: 4px; border-radius: 4px; border: 1px solid #10b981;">
                    @endif
                    <div>
                        <div style="font-size: 10px; color: #10b981; letter-spacing: 1.5px; font-weight: 700;">// NOC_GATEWAY_NODE</div>
                        <h1 class="circuit-title">{{ $company['name'] }}</h1>
                        <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0;">{{ $company['address'] }}</p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="circuit-meta-pill">[TELEMETRY_STATEMENT]</span>
                    <div style="font-size: 18px; font-weight: 800; color: #ffffff;">#{{ $invoice->invoice_number }}</div>
                    <div style="font-size: 11px; color: {{ $invoice->status == 'paid' ? '#34d399' : ($invoice->status == 'unpaid' ? '#f87171' : '#fbbf24') }}; font-weight: 700; margin-top: 4px;">
                        STATUS: {{ $invoice->status == 'paid' ? 'LUNAS (CONFIRMED)' : ($invoice->status == 'unpaid' ? 'UNPAID (PENDING)' : 'PARTIAL') }}
                    </div>
                </div>
            </div>

            <div class="circuit-body">
                <div class="circuit-grid">
                    <div class="circuit-card">
                        <div style="font-weight: 800; color: #059669; margin-bottom: 6px;">[CLIENT_NODE_IDENTITY]</div>
                        <div><strong>NAME:</strong> {{ $invoice->customer->name }}</div>
                        <div><strong>CID:</strong> {{ $invoice->customer->customer_code }}</div>
                        <div><strong>LOCATION:</strong> {{ $invoice->customer->address }}</div>
                        <div><strong>CONTACT:</strong> {{ $invoice->customer->phone }}</div>
                    </div>

                    <div class="circuit-card">
                        <div style="font-weight: 800; color: #059669; margin-bottom: 6px;">[SERVICE_SPECIFICATION]</div>
                        <div><strong>PACKAGE:</strong> {{ $invoice->customer->package ? $invoice->customer->package->name : 'Broadband Pipe' }}</div>
                        <div><strong>CYCLE:</strong> {{ $invoice->period_label }}</div>
                        <div><strong>ISSUED:</strong> {{ $invoice->issued_date ? $invoice->issued_date->format('Y-m-d') : date('Y-m-d') }}</div>
                        <div style="color: #dc2626;"><strong>DEADLINE:</strong> {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : date('Y-m-d') }}</div>
                    </div>
                </div>

                <table class="circuit-table">
                    <thead>
                        <tr>
                            <th>[ITEM_SPECIFICATION]</th>
                            <th>[BILLING_PERIOD]</th>
                            <th style="text-align: right;">[AMOUNT_IDR]</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>BANDWIDTH TRANSIT // {{ $invoice->customer->package ? $invoice->customer->package->name : 'ISP' }}</strong>
                                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Core Optical Uplink, Unmetered Symmetric Data Pipeline</div>
                            </td>
                            <td>{{ $invoice->period_label }}</td>
                            <td style="text-align: right; font-weight: 700;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($taxAmount > 0)
                        <tr>
                            <td colspan="2" style="text-align: right; color: #64748b;">TAX_PPN ({{ $taxPpn }}%)</td>
                            <td style="text-align: right;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div style="display: flex; gap: 20px; align-items: flex-start;">
                    <div style="flex: 1; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 18px; font-size: 11px;">
                        <div style="font-weight: 800; color: #059669; margin-bottom: 8px;">// PAYMENT_GATEWAYS:</div>
                        @if(!empty($banks) && count($banks) > 0)
                            @foreach($banks as $b)
                                <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed #bbf7d0;">
                                    <span>{{ $b['bank_name'] }} ({{ $b['account_name'] }})</span>
                                    <strong style="color: #059669;">{{ $b['account_number'] }}</strong>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="circuit-totals">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: #94a3b8;">
                            <span>SUBTOTAL:</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($taxAmount > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: #94a3b8;">
                            <span>TAX PPN:</span>
                            <span>Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: #94a3b8;">
                            <span>BILLED:</span>
                            <span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #34d399;">
                            <span>PAID:</span>
                            <span>Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                        </div>
                        <div style="border-top: 1px solid #10b981; padding-top: 8px; display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-weight: 800; color: #34d399;">REMAINING_DUE:</span>
                            <span style="font-size: 18px; font-weight: 800; color: #34d399;">Rp {{ number_format($invoice->remaining, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <div class="circuit-stamp">
                        [CRYPTOGRAPHIC_VERIFIED] NOC-SHA256: {{ substr(hash('sha256', $invoice->invoice_number), 0, 16) }}
                    </div>
                    <div style="text-align: right; font-size: 10px; color: #64748b;">
                        <div>AUTOMATED NOC BILLING DAEMON</div>
                        <div style="font-weight: 700; color: #022c22;">{{ $company['name'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             8. TEMPLATE: GUILLOCHE SECURITY WAVE (corporate_wave)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'corporate_wave')
        <div class="invoice-corporate-wave">
            <div class="wave-header">
                <div style="display: flex; align-items: center; gap: 16px;">
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 52px; background: rgba(255,255,255,0.95); padding: 5px; border-radius: 8px; border: 1px solid #14b8a6;">
                    @endif
                    <div>
                        <h1 class="wave-company-name">{{ $company['name'] }}</h1>
                        <p style="font-size: 11px; color: #ccfbf1; margin: 3px 0 0;">{{ $company['address'] }}</p>
                        @if(!empty($company['phone']) || !empty($company['email']))
                            <p style="font-size: 11px; color: #5eead4; margin: 2px 0 0;">
                                @if(!empty($company['phone'])) Telp: {{ $company['phone'] }} @endif
                                @if(!empty($company['phone']) && !empty($company['email'])) &nbsp;|&nbsp; @endif
                                @if(!empty($company['email'])) Email: {{ $company['email'] }} @endif
                            </p>
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 10px; font-weight: 800; color: #5eead4; text-transform: uppercase; letter-spacing: 1px;">OFFICIAL BILLING DOCUMENT</div>
                    <div style="font-size: 20px; font-weight: 800; color: #ffffff; margin-top: 2px;">{{ $invoice->invoice_number }}</div>
                    <div style="margin-top: 4px;">
                        <span style="display: inline-block; padding: 2px 10px; border-radius: 4px; font-size: 11px; font-weight: 800; background: #ffffff; color: {{ $invoice->status == 'paid' ? '#0f766e' : '#e11d48' }};">
                            {{ $invoice->status == 'paid' ? 'LUNAS (SETTLED)' : ($invoice->status == 'unpaid' ? 'BELUM LUNAS (OUTSTANDING)' : 'DIBAYAR SEBAGIAN') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="wave-body">
                <div class="wave-grid">
                    <div class="wave-card">
                        <div style="font-size: 11px; font-weight: 800; color: #0f766e; text-transform: uppercase; margin-bottom: 4px;">Penerima Tagihan / Billed To</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0f172a;">{{ $invoice->customer->name }}</div>
                        <div style="font-size: 12px; color: #475569; margin-top: 2px;">ID Pelanggan: <strong>{{ $invoice->customer->customer_code }}</strong></div>
                        <div style="font-size: 12px; color: #475569;">Alamat: {{ $invoice->customer->address }}</div>
                        <div style="font-size: 12px; color: #475569;">No. Kontak: {{ $invoice->customer->phone }}</div>
                    </div>

                    <div class="wave-card">
                        <div style="font-size: 11px; font-weight: 800; color: #0f766e; text-transform: uppercase; margin-bottom: 4px;">Informasi Transaksi / Billing Details</div>
                        <div style="font-size: 12px; color: #475569;">Paket Internet: <strong>{{ $invoice->customer->package ? $invoice->customer->package->name : 'Internet Fiber' }}</strong></div>
                        <div style="font-size: 12px; color: #475569;">Periode Tagihan: <strong>{{ $invoice->period_label }}</strong></div>
                        <div style="font-size: 12px; color: #475569;">Tanggal Terbit: {{ $invoice->issued_date ? $invoice->issued_date->format('d M Y') : date('d M Y') }}</div>
                        <div style="font-size: 12px; color: #e11d48;"><strong>Jatuh Tempo: {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : date('d M Y') }}</strong></div>
                    </div>
                </div>

                <table class="wave-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Rincian Layanan / Description</th>
                            <th>Periode</th>
                            <th style="text-align: right;">Jumlah (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong style="color: #0f172a;">Layanan Internet Broadband {{ $invoice->customer->package ? $invoice->customer->package->name : '' }}</strong>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Koneksi Internet Fiber Optik Stabil & Unlimited</div>
                            </td>
                            <td>{{ $invoice->period_label }}</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($taxAmount > 0)
                        <tr>
                            <td colspan="2" style="text-align: right; color: #64748b; font-size: 12px;">PPN ({{ $taxPpn }}%)</td>
                            <td style="text-align: right;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div style="display: flex; gap: 24px; align-items: flex-start; position: relative; z-index: 1;">
                    <div style="flex: 1; background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 10px; padding: 18px 22px;">
                        <div style="font-size: 11px; font-weight: 800; color: #0f766e; text-transform: uppercase; margin-bottom: 8px;">
                            Rekening Resmi Pembayaran Transfer
                        </div>
                        @if(!empty($banks) && count($banks) > 0)
                            @foreach($banks as $b)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #ccfbf1; font-size: 12px;">
                                    <div>
                                        <span style="font-weight: 700; color: #0f172a;">{{ $b['bank_name'] }}</span>
                                        <span style="color: #64748b; font-size: 11px;">(a.n. {{ $b['account_name'] }})</span>
                                    </div>
                                    <div style="font-family: 'JetBrains Mono', monospace; font-weight: 800; color: #0f766e;">{{ $b['account_number'] }}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="wave-totals">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; color: #ccfbf1;">
                            <span>Subtotal:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($taxAmount > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; color: #ccfbf1;">
                            <span>PPN ({{ $taxPpn }}%):</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; color: #ccfbf1;">
                            <span>Total Tagihan:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 12px; color: #5eead4;">
                            <span>Telah Dibayar:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                        </div>
                        <div style="border-top: 1px solid rgba(255,255,255,0.25); padding-top: 10px; display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-weight: 700; color: #ffffff; text-transform: uppercase;">Sisa Tagihan</span>
                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 800; color: #ffffff;">
                                Rp {{ number_format($invoice->remaining, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 35px; padding-top: 20px; border-top: 1px solid #e2e8f0; position: relative; z-index: 1;">
                    <div style="font-size: 11px; color: #0f766e; font-weight: 700; border: 1.5px solid #0f766e; padding: 6px 12px; border-radius: 6px; background: #f0fdfa;">
                        DOCUMENT VALIDATED VIA SECURITY GUILLOCHE VERIFICATION
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #64748b;">
                        <div>Bagian Keuangan & Billing</div>
                        <div style="font-weight: 800; color: #0f172a; font-size: 12px; margin-top: 2px;">{{ $company['name'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             9. TEMPLATE: VINTAGE POSTAL KRAFT (retro_kraft)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'retro_kraft')
        <div class="invoice-retro-kraft">
            <div class="retro-header">
                <div>
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 48px; filter: sepia(0.6); margin-bottom: 6px;">
                    @endif
                    <h1 class="retro-company-name">{{ $company['name'] }}</h1>
                    <p style="font-size: 11px; margin: 3px 0 0; color: #78350f;">{{ $company['address'] }}</p>
                    <p style="font-size: 11px; margin: 2px 0 0; color: #78350f;">
                        @if(!empty($company['phone'])) TEL: {{ $company['phone'] }} @endif
                        @if(!empty($company['phone']) && !empty($company['email'])) | @endif
                        @if(!empty($company['email'])) EMAIL: {{ $company['email'] }} @endif
                    </p>
                </div>

                <div class="retro-postmark">
                    <div class="retro-stamp-circle">
                        <span>★ TELCO ★</span>
                        <span style="font-weight: 800; font-size: 10px; margin: 2px 0;">{{ $invoice->status == 'paid' ? 'LUNAS' : 'BILLED' }}</span>
                        <span>{{ date('d/m/Y') }}</span>
                    </div>
                    <div class="retro-wavy-lines">
                        ≈≈≈≈<br>≈≈≈≈<br>≈≈≈≈
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 11px; font-weight: 700; color: #78350f;">№ TAGIHAN:</div>
                        <div style="font-size: 16px; font-weight: 800; color: #451a03;">{{ $invoice->invoice_number }}</div>
                    </div>
                </div>
            </div>

            <div class="retro-grid">
                <div>
                    <div style="font-size: 10px; font-weight: 700; color: #78350f; text-transform: uppercase;">Ditujukan Kepada:</div>
                    <div style="font-size: 14px; font-weight: 700; color: #451a03; margin-top: 2px;">{{ $invoice->customer->name }}</div>
                    <div style="font-size: 11px; color: #78350f; margin-top: 2px;">No. Pelanggan: {{ $invoice->customer->customer_code }}</div>
                    <div style="font-size: 11px; color: #78350f;">Alamat: {{ $invoice->customer->address }}</div>
                    <div style="font-size: 11px; color: #78350f;">Telepon: {{ $invoice->customer->phone }}</div>
                </div>

                <div>
                    <div style="font-size: 10px; font-weight: 700; color: #78350f; text-transform: uppercase;">Rincian Jadwal:</div>
                    <div style="font-size: 11px; color: #78350f; margin-top: 2px;">Paket Langganan: <strong>{{ $invoice->customer->package ? $invoice->customer->package->name : 'Internet' }}</strong></div>
                    <div style="font-size: 11px; color: #78350f;">Periode: <strong>{{ $invoice->period_label }}</strong></div>
                    <div style="font-size: 11px; color: #78350f;">Tanggal Cetak: {{ $invoice->issued_date ? $invoice->issued_date->format('d/m/Y') : date('d/m/Y') }}</div>
                    <div style="font-size: 11px; color: #991b1b; font-weight: 700;">Jatuh Tempo: {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : date('d/m/Y') }}</div>
                </div>
            </div>

            <table class="retro-table">
                <thead>
                    <tr>
                        <th>URAIAN PEMBAYARAN</th>
                        <th style="width: 140px;">PERIODE</th>
                        <th style="width: 160px; text-align: right;">JUMLAH (RP)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Tagihan Internet Bulanan ({{ $invoice->customer->package ? $invoice->customer->package->name : 'Broadband' }})</strong>
                            <div style="font-size: 10px; color: #78350f;">Akses Telekomunikasi Jaringan Internet</div>
                        </td>
                        <td>{{ $invoice->period_label }}</td>
                        <td style="text-align: right; font-weight: 700;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($taxAmount > 0)
                    <tr>
                        <td colspan="2" style="text-align: right; font-size: 11px;">Pajak Pertambahan Nilai (PPN {{ $taxPpn }}%)</td>
                        <td style="text-align: right;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="flex: 1; border: 1px solid #78350f; background: #fffdf9; padding: 14px 18px; font-size: 11px;">
                    <div style="font-weight: 700; color: #78350f; margin-bottom: 6px;">INSTRUKSI PEMBAYARAN VIA TRANSFER:</div>
                    @if(!empty($banks) && count($banks) > 0)
                        @foreach($banks as $b)
                            <div style="margin-top: 3px;">
                                • {{ $b['bank_name'] }}: <strong>{{ $b['account_number'] }}</strong> (a.n. {{ $b['account_name'] }})
                            </div>
                        @endforeach
                    @endif
                    <div style="margin-top: 8px; font-style: italic; color: #78350f;">
                        Harap simpan lembaran ini sebagai arsip bukti pembayaran yang sah.
                    </div>
                </div>

                <div class="retro-totals">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px;">
                        <span>Jumlah Tagihan:</span>
                        <span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px; color: #047857;">
                        <span>Telah Diterima:</span>
                        <span>Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                    </div>
                    <div style="border-top: 2px dashed #78350f; padding-top: 8px; display: flex; justify-content: space-between; align-items: baseline;">
                        <span style="font-weight: 700; font-size: 12px;">SISA SALDO:</span>
                        <span style="font-size: 18px; font-weight: 800; color: #991b1b;">
                            Rp {{ number_format($invoice->remaining, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 30px; font-size: 11px; color: #78350f;">
                <div>
                    <div>Catatan: Lunas setelah dana efektif masuk di rekening.</div>
                    <div style="font-weight: 700; margin-top: 2px;">Terima kasih atas kepercayaannya.</div>
                </div>
                <div style="text-align: center; width: 180px;">
                    <div>{{ date('d F Y') }}</div>
                    <div style="height: 45px;"></div>
                    <div style="border-bottom: 1px solid #78350f; font-weight: 700;">( Kasir / Admin )</div>
                    <div style="font-size: 10px; margin-top: 2px;">{{ $company['name'] }}</div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             10. TEMPLATE: DYNAMIC AURORA FLOW (aurora_flow)
             ═══════════════════════════════════════════════════════ -->
        @elseif($activeTemplate === 'aurora_flow')
        <div class="invoice-aurora-flow">
            <div class="aurora-header">
                <div style="display: flex; align-items: center; gap: 18px;">
                    @if(!empty($company['logo']))
                        <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 54px; background: rgba(255,255,255,0.95); padding: 6px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    @endif
                    <div>
                        <h1 class="aurora-company-name">{{ $company['name'] }}</h1>
                        <p style="font-size: 12px; color: rgba(255,255,255,0.85); margin: 3px 0 0;">{{ $company['address'] }}</p>
                        @if(!empty($company['phone']) || !empty($company['email']))
                            <p style="font-size: 11px; color: rgba(255,255,255,0.9); margin: 2px 0 0;">
                                @if(!empty($company['phone'])) 📞 {{ $company['phone'] }} @endif
                                @if(!empty($company['phone']) && !empty($company['email'])) &nbsp;|&nbsp; @endif
                                @if(!empty($company['email'])) ✉️ {{ $company['email'] }} @endif
                            </p>
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="aurora-pill">✨ SMART BROADBAND INVOICE</span>
                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 800; color: #ffffff;">{{ $invoice->invoice_number }}</div>
                    <div style="margin-top: 6px;">
                        <span style="display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 11px; font-weight: 800; background: #ffffff; color: {{ $invoice->status == 'paid' ? '#059669' : ($invoice->status == 'unpaid' ? '#e11d48' : '#d97706') }}; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            ● {{ $invoice->status == 'paid' ? 'LUNAS' : ($invoice->status == 'unpaid' ? 'BELUM LUNAS' : 'SEBAGIAN') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="aurora-body">
                <div class="aurora-grid">
                    <div class="aurora-card">
                        <div style="font-size: 10px; font-weight: 800; color: #a855f7; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">Pelanggan Penerima</div>
                        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">{{ $invoice->customer->name }}</h3>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0;"><strong>ID:</strong> <span style="font-weight: 700; color: #7c3aed;">{{ $invoice->customer->customer_code }}</span></p>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0;"><strong>Alamat:</strong> {{ $invoice->customer->address }}</p>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0;"><strong>Kontak:</strong> {{ $invoice->customer->phone }}</p>
                    </div>

                    <div class="aurora-card">
                        <div style="font-size: 10px; font-weight: 800; color: #a855f7; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">Spesifikasi Layanan</div>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0;"><strong>Paket Internet:</strong> <span style="font-weight: 700; color: #7c3aed;">{{ $invoice->customer->package ? $invoice->customer->package->name : 'Internet Broadband' }}</span></p>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0;"><strong>Periode:</strong> {{ $invoice->period_label }}</p>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0;"><strong>Tanggal Terbit:</strong> {{ $invoice->issued_date ? $invoice->issued_date->format('d M Y') : date('d M Y') }}</p>
                        <p style="font-size: 12px; color: #e11d48; margin: 2px 0;"><strong>Batas Jatuh Tempo:</strong> <span style="font-weight: 800;">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : date('d M Y') }}</span></p>
                    </div>
                </div>

                <table class="aurora-table">
                    <thead>
                        <tr>
                            <th>Layanan & Rincian</th>
                            <th>Siklus Tagihan</th>
                            <th style="text-align: right;">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong style="color: #0f172a; font-size: 13px;">Langganan Internet Cepat {{ $invoice->customer->package ? $invoice->customer->package->name : '' }}</strong>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Internet Tanpa Batas Kuota (FUP Unlimited) untuk Kebutuhan Kerja & Hiburan</div>
                            </td>
                            <td style="font-weight: 600; color: #475569;">{{ $invoice->period_label }}</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #0f172a; font-size: 13px;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($taxAmount > 0)
                        <tr>
                            <td colspan="2" style="text-align: right; font-weight: 600; color: #64748b; font-size: 12px;">PPN ({{ $taxPpn }}%)</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div style="display: flex; gap: 24px; align-items: flex-start;">
                    <div style="flex: 1; background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 16px; padding: 20px 24px;">
                        <div style="font-size: 11px; font-weight: 800; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px;">
                            💳 Rekening Pembayaran Resmi
                        </div>
                        @if(!empty($banks) && count($banks) > 0)
                            @foreach($banks as $b)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px dashed #e9d5ff; font-size: 12px;">
                                    <div>
                                        <span style="font-weight: 700; color: #0f172a;">{{ $b['bank_name'] }}</span>
                                        <span style="color: #64748b; font-size: 11px;">(a.n. {{ $b['account_name'] }})</span>
                                    </div>
                                    <div style="font-family: 'JetBrains Mono', monospace; font-weight: 800; color: #7c3aed;">{{ $b['account_number'] }}</div>
                                </div>
                            @endforeach
                        @endif
                        <p style="font-size: 11px; color: #94a3b8; margin: 10px 0 0;">
                            Pembayaran dapat juga dilakukan melalui Loket Kasir atau Kolektor resmi ISP.
                        </p>
                    </div>

                    <div class="aurora-totals">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: rgba(255,255,255,0.85);">
                            <span>Subtotal:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($taxAmount > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: rgba(255,255,255,0.85);">
                            <span>PPN ({{ $taxPpn }}%):</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: rgba(255,255,255,0.85);">
                            <span>Total Tagihan:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 12px; color: #34d399;">
                            <span>Telah Dibayar:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                        </div>
                        <div style="border-top: 1px solid rgba(255,255,255,0.25); padding-top: 10px; display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-size: 12px; font-weight: 800; color: #ffffff; text-transform: uppercase;">Sisa Tagihan</span>
                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 800; color: #ffffff;">
                                Rp {{ number_format($invoice->remaining, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 35px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">✨</span>
                        <div style="font-size: 11px; color: #64748b;">
                            <div>Smart Billing Experience</div>
                            <div style="font-weight: 700; color: #7c3aed;">Terima kasih atas kepercayaan Anda</div>
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #64748b;">
                        <div>Manajemen Keuangan</div>
                        <div style="font-weight: 800; color: #0f172a; font-size: 12px; margin-top: 2px;">{{ $company['name'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

</body>
</html>
