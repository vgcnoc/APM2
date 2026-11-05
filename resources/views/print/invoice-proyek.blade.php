<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Proyek {{ $invoice->invoice_number }} - {{ $invoice->client_name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12.5px;
            line-height: 1.5;
            color: #1e293b;
            margin: 0;
            padding: 24px;
            background-color: #f8fafc;
        }
        .sheet {
            max-width: 860px;
            margin: auto;
            padding: 36px 42px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
            position: relative;
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
            max-width: 860px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-bar .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 7px 16px;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .action-bar .btn-print:hover {
            background-color: #1d4ed8;
        }
        .action-bar .btn-wa {
            background-color: #059669;
            color: #ffffff;
            border: none;
            padding: 7px 16px;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .action-bar .btn-wa:hover {
            background-color: #047857;
        }
        .action-bar .btn-receipt {
            background-color: #7c3aed;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .action-bar .btn-close {
            background: transparent;
            color: #cbd5e1;
            border: 1px solid #475569;
            padding: 7px 12px;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
        }
        .action-bar .btn-close:hover {
            background: #1e293b;
            color: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .logo-img {
            max-height: 55px;
            max-width: 180px;
            object-fit: contain;
        }
        .company-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }
        .company-meta {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }
        .inv-badge-wrap {
            text-align: right;
            vertical-align: top;
        }
        .inv-main-title {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 1px;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .inv-sub-title {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .meta-grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }
        .meta-col {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
        }
        .meta-heading {
            font-size: 10.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .meta-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 11.5px;
        }
        .meta-label {
            width: 120px;
            color: #64748b;
            font-weight: 500;
            flex-shrink: 0;
        }
        .meta-value {
            color: #0f172a;
            font-weight: 600;
            flex: 1;
        }
        .project-banner {
            background: #f1f5f9;
            border-left: 4px solid #2563eb;
            padding: 10px 14px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 20px;
        }
        .project-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .project-loc {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        table.items-table td {
            padding: 9px 10px;
            border: 1px solid #e2e8f0;
            font-size: 11.5px;
            vertical-align: top;
        }
        table.items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .bottom-section {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }
        .left-info {
            flex: 1.1;
        }
        .bank-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .bank-title {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .bank-desc {
            font-size: 11.5px;
            color: #334155;
            line-height: 1.5;
        }
        .terbilang-box {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 8px 12px;
            border-radius: 0 4px 4px 0;
            font-size: 11px;
            color: #1e40af;
            font-style: italic;
        }
        .totals-box {
            flex: 0.9;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .tot-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11.5px;
        }
        .tot-label {
            color: #64748b;
        }
        .tot-val {
            font-weight: 600;
            color: #0f172a;
        }
        .grand-total-row {
            background: #0f172a;
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 800;
            padding: 8px 12px;
            display: flex;
            justify-content: space-between;
        }
        .grand-total-row .tot-val {
            color: #38bdf8;
        }
        .paid-row {
            background: #f0fdf4;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            display: flex;
            justify-content: space-between;
            border-top: 1px dashed #bbf7d0;
        }
        .remaining-row {
            background: #fef2f2;
            color: #991b1b;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #fecaca;
        }
        .signatures {
            margin-top: 28px;
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
            font-size: 11px;
            color: #475569;
            font-weight: 600;
            min-height: 28px;
        }
        .sign-space {
            height: 55px;
            position: relative;
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
        /* ── Real Rubber Stamp Style ── */
        .real-stamp {
            position: relative;
            display: inline-block;
            width: 105px;
            height: 105px;
            border-radius: 50%;
            border: 2px solid currentColor;
            padding: 2.5px;
            box-sizing: border-box;
            transform: rotate(-5deg);
            opacity: 0.88;
            user-select: none;
            font-family: 'Arial', 'Helvetica', sans-serif;
            text-align: center;
            background: rgba(255, 255, 255, 0.45);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .real-stamp::before {
            content: '';
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            border: 1px dashed currentColor;
            opacity: 0.75;
            pointer-events: none;
        }
        .stamp-inner-ring {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 1px solid currentColor;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 4px 3px 5px;
            z-index: 1;
        }
        .stamp-top-text {
            font-size: 6.5px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            line-height: 1;
            color: currentColor;
            padding-top: 1px;
            max-width: 95%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .stamp-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin: auto 0;
        }
        .stamp-logo {
            max-height: 20px;
            max-width: 50px;
            object-fit: contain;
            margin-bottom: 1.5px;
            filter: drop-shadow(0 0 0.5px currentColor);
        }
        .stamp-status-ribbon {
            width: 100%;
            border-top: 1px solid currentColor;
            border-bottom: 1px solid currentColor;
            padding: 1px 0;
            margin: 1px 0;
            background: rgba(255, 255, 255, 0.75);
        }
        .stamp-status-text {
            font-size: 8.5px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: block;
            line-height: 1;
        }
        .stamp-date-text {
            font-size: 6.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-top: 1px;
            line-height: 1;
        }
        .stamp-bottom-text {
            font-size: 6px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            line-height: 1;
            color: currentColor;
            padding-bottom: 1px;
        }
        .stamp-paid {
            color: #047857;
            border-color: #047857;
        }
        .stamp-unpaid {
            color: #dc2626;
            border-color: #dc2626;
        }
        .stamp-partial {
            color: #b45309;
            border-color: #b45309;
        }

        .stamp-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .badge-partial {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-draft {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
    </style>
</head>
<body>

    @php
        $cleanedPhone = preg_replace('/[^0-9]/', '', (string)$invoice->client_phone);
        if (str_starts_with($cleanedPhone, '0')) {
            $cleanedPhone = '62' . substr($cleanedPhone, 1);
        } elseif (str_starts_with($cleanedPhone, '8')) {
            $cleanedPhone = '62' . $cleanedPhone;
        }

        $schemeLabels = [
            'full' => 'Pembayaran Penuh (100%)',
            'termin_dp' => 'Termin 1 (Uang Muka / DP)',
            'termin_progress' => 'Termin Progres Pengerjaan',
            'termin_final' => 'Termin Pelunasan (Final)',
        ];
        $schemeText = $schemeLabels[$invoice->payment_scheme] ?? 'Pembayaran Proyek';

        $issueDateFmt = $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->translatedFormat('d F Y') : '-';
        $dueDateFmt = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y') : 'Sesuai SPK / Perjanjian';

        $totalFmt = 'Rp ' . number_format($invoice->total_amount, 0, ',', '.');
        $paidFmt = 'Rp ' . number_format($invoice->paid_amount, 0, ',', '.');
        $remFmt = 'Rp ' . number_format($invoice->remaining_amount, 0, ',', '.');

        $status = $invoice->status;
        if ($status === 'paid') {
            $statusBadgeClass = 'badge-paid';
            $statusLabel = 'LUNAS';
            $waStatus = "✅ *Status Pembayaran*: LUNAS (100% Selesai)\n" . "💰 *Jumlah Terbayar*: {$paidFmt}\n";
        } elseif ($status === 'partial') {
            $statusBadgeClass = 'badge-partial';
            $statusLabel = 'TERMIN / SEBAGIAN';
            $waStatus = "⏳ *Status Pembayaran*: TERMIN / DP (Sebagian)\n" . "💵 *Sudah Terbayar*: {$paidFmt}\n" . "⚠️ *Sisa Tagihan*: {$remFmt}\n";
        } elseif ($status === 'draft') {
            $statusBadgeClass = 'badge-draft';
            $statusLabel = 'DRAF';
            $waStatus = "📝 *Status*: DRAF INVOICE PROYEK\n";
        } else {
            $statusBadgeClass = 'badge-unpaid';
            $statusLabel = 'BELUM LUNAS';
            $waStatus = "⚠️ *Status Pembayaran*: BELUM LUNAS\n" . "💰 *Total Tagihan*: {$totalFmt}\n" . "📅 *Jatuh Tempo*: {$dueDateFmt}\n";
        }

        $waMessage = "*INVOICE PROYEK & JASA KERJASAMA*\n"
            . "*{$company['name']}*\n"
            . "----------------------------------------\n"
            . "Kepada Yth. Bpk/Ibu *{$invoice->client_pic}* / *{$invoice->client_name}*,\n\n"
            . "Berikut rincian tagihan resmi untuk pekerjaan:\n"
            . "🏗️ *Proyek*: {$invoice->project_title}\n"
            . "📄 *No. Invoice*: {$invoice->invoice_number}\n"
            . (!empty($invoice->po_spk_number) ? "📋 *No. PO/SPK*: {$invoice->po_spk_number}\n" : "")
            . "📅 *Tgl Terbit*: {$issueDateFmt}\n"
            . "🏷️ *Skema*: {$schemeText}\n"
            . "💵 *Total Tagihan*: {$totalFmt}\n"
            . $waStatus
            . "----------------------------------------\n"
            . "*Petunjuk Pembayaran Transfer Resmi:*\n"
            . "🏦 {$company['bank_info']}\n\n"
            . "Mohon mengirimkan bukti transfer setelah pembayaran dilakukan.\n"
            . (!empty($company['phone']) ? "Hubungi Layanan Finance: *{$company['phone']}*\n\n" : "")
            . "Hormat Kami,\n*{$company['name']}*";

        $waUrl = $cleanedPhone
            ? "https://wa.me/{$cleanedPhone}?text=" . rawurlencode($waMessage)
            : "https://wa.me/?text=" . rawurlencode($waMessage);
    @endphp

    <!-- ACTION BAR (TIDAK IKUT TERCETAK) -->
    <div class="action-bar no-print">
        <div>
            <strong style="font-size: 13.5px;">Invoice Proyek & Jasa B2B</strong>
            <span style="font-size: 12px; color: #94a3b8; margin-left: 8px;">No: {{ $invoice->invoice_number }}</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ $waUrl }}" target="_blank" class="btn-wa">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/></svg>
                Kirim WA ke Klien
            </a>
            @if($invoice->paid_amount > 0)
                <a href="{{ route('project-invoices.receipt', $invoice->id) }}" target="_blank" class="btn-receipt">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Kwitansi Bayar
                </a>
            @endif
            <button onclick="window.print()" class="btn-print">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak / Simpan PDF
            </button>
            <a href="javascript:window.close()" class="btn-close">Tutup</a>
        </div>
    </div>

    <!-- SHEET PRINTABLE -->
    <div class="sheet">
        
        <!-- HEADER KOP -->
        <table class="header-table">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    @if(!empty($company['logo']))
                        <img src="{{ $company['logo'] }}" alt="Logo" class="logo-img"><br>
                    @endif
                    <div class="company-title">{{ $company['name'] }}</div>
                    <div class="company-meta">
                        {{ $company['address'] }}<br>
                        @if(!empty($company['phone'])) Telp / WhatsApp: {{ $company['phone'] }} @endif
                        @if(!empty($company['email'])) | Email: {{ $company['email'] }} @endif
                        @if(!empty($company['npwp'])) <br>NPWP: {{ $company['npwp'] }} @endif
                    </div>
                </td>
                <td class="inv-badge-wrap">
                    <div class="inv-main-title">INVOICE PROYEK</div>
                    <div class="inv-sub-title">COMMERCIAL INVOICE</div>
                    <div style="margin-top: 8px;">
                        <span class="stamp-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- INFO GRID -->
        <div class="meta-grid">
            <!-- DITUJUKAN KEPADA (KLIEN) -->
            <div class="meta-col">
                <div class="meta-heading">Ditujukan Kepada (Klien / Rekanan):</div>
                <div class="meta-row">
                    <span class="meta-label">Nama Perusahaan:</span>
                    <span class="meta-value" style="font-size: 13px; color: #1e40af;">{{ $invoice->client_name }}</span>
                </div>
                @if(!empty($invoice->client_pic))
                <div class="meta-row">
                    <span class="meta-label">PIC / Kontak:</span>
                    <span class="meta-value">{{ $invoice->client_pic }}</span>
                </div>
                @endif
                @if(!empty($invoice->client_phone))
                <div class="meta-row">
                    <span class="meta-label">No. Telepon / WA:</span>
                    <span class="meta-value">{{ $invoice->client_phone }}</span>
                </div>
                @endif
                @if(!empty($invoice->client_email))
                <div class="meta-row">
                    <span class="meta-label">Email:</span>
                    <span class="meta-value">{{ $invoice->client_email }}</span>
                </div>
                @endif
                @if(!empty($invoice->client_address))
                <div class="meta-row">
                    <span class="meta-label">Alamat Klien:</span>
                    <span class="meta-value" style="font-weight: 400; color: #475569;">{{ $invoice->client_address }}</span>
                </div>
                @endif
                @if(!empty($invoice->client_npwp))
                <div class="meta-row">
                    <span class="meta-label">NPWP Klien:</span>
                    <span class="meta-value">{{ $invoice->client_npwp }}</span>
                </div>
                @endif
            </div>

            <!-- DETAIL INVOICE -->
            <div class="meta-col">
                <div class="meta-heading">Informasi Penagihan:</div>
                <div class="meta-row">
                    <span class="meta-label">Nomor Invoice:</span>
                    <span class="meta-value" style="font-size: 13px; letter-spacing: 0.5px;">{{ $invoice->invoice_number }}</span>
                </div>
                @if(!empty($invoice->po_spk_number))
                <div class="meta-row">
                    <span class="meta-label">No. PO / SPK:</span>
                    <span class="meta-value" style="color: #0369a1;">{{ $invoice->po_spk_number }}</span>
                </div>
                @endif
                <div class="meta-row">
                    <span class="meta-label">Tanggal Terbit:</span>
                    <span class="meta-value">{{ $issueDateFmt }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Jatuh Tempo:</span>
                    <span class="meta-value" style="color: #dc2626;">{{ $dueDateFmt }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Skema Tagihan:</span>
                    <span class="meta-value">{{ $schemeText }}</span>
                </div>
            </div>
        </div>

        <!-- PROJECT TITLE BANNER -->
        <div class="project-banner">
            <div style="font-size: 10px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px;">Nama Pekerjaan / Proyek:</div>
            <h3 class="project-title">{{ $invoice->project_title }}</h3>
            @if(!empty($invoice->project_location))
                <div class="project-loc">📍 Lokasi Pengerjaan: <strong>{{ $invoice->project_location }}</strong></div>
            @endif
        </div>

        <!-- ITEMS TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">No</th>
                    <th>Deskripsi & Rincian Pekerjaan / Material</th>
                    <th style="width: 70px;" class="text-center">Volume</th>
                    <th style="width: 75px;" class="text-center">Satuan</th>
                    <th style="width: 130px;" class="text-right">Harga Satuan (Rp)</th>
                    <th style="width: 145px;" class="text-right">Total Harga (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0f172a;">{{ $item->description }}</strong>
                        </td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($item->qty, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-center"><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11px;">{{ $item->unit }}</span></td>
                        <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: 700; color: #0f172a;">{{ number_format($item->total_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- BOTTOM SECTION -->
        <div class="bottom-section">
            <div class="left-info">
                <!-- BANK REKENING -->
                <div class="bank-box">
                    <div class="bank-title">💳 Rekening Pembayaran Resmi:</div>
                    <div class="bank-desc">
                        <strong>{{ $company['bank_info'] }}</strong><br>
                        <span style="font-size: 10.5px; color: #64748b;">Harap cantumkan nomor invoice <strong>{{ $invoice->invoice_number }}</strong> pada berita transfer.</span>
                    </div>
                </div>

                <!-- TERBILANG -->
                <div class="terbilang-box">
                    <strong>Terbilang:</strong> {{ $terbilangTotal }}
                </div>

                @if(!empty($invoice->notes))
                <div style="margin-top: 10px; font-size: 11px; color: #475569; background: #fdf4ff; border: 1px dashed #d8b4fe; padding: 8px 12px; border-radius: 6px;">
                    <strong>Catatan / Ketentuan:</strong><br>
                    {!! nl2br(e($invoice->notes)) !!}
                </div>
                @endif
            </div>

            <!-- TOTALS -->
            <div class="totals-box">
                <div class="tot-row">
                    <span class="tot-label">Subtotal</span>
                    <span class="tot-val">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($invoice->discount > 0)
                <div class="tot-row" style="color: #059669;">
                    <span class="tot-label">Potongan Diskon</span>
                    <span class="tot-val">- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($invoice->tax_ppn_amount > 0)
                <div class="tot-row">
                    <span class="tot-label">PPN ({{ $invoice->tax_ppn_percent }}%)</span>
                    <span class="tot-val">+ Rp {{ number_format($invoice->tax_ppn_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($invoice->tax_pph23_amount > 0)
                <div class="tot-row" style="color: #dc2626;">
                    <span class="tot-label">PPh 23 Jasa ({{ $invoice->tax_pph23_percent }}%)</span>
                    <span class="tot-val">- Rp {{ number_format($invoice->tax_pph23_amount, 0, ',', '.') }}</span>
                </div>
                @endif

                <div class="grand-total-row">
                    <span>Total Tagihan:</span>
                    <span class="tot-val">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>

                @if($invoice->paid_amount > 0)
                <div class="paid-row">
                    <span>Jumlah Terbayar:</span>
                    <span>Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</span>
                </div>
                @if($invoice->remaining_amount > 0)
                <div class="remaining-row">
                    <span>Sisa Tagihan (Piutang):</span>
                    <span>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @endif
            </div>
        </div>

        <!-- TANDA TANGAN -->
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">Disiapkan Oleh (Finance):</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $invoice->creator->name ?? 'Bagian Keuangan' }}</div>
                <div class="sign-role">Finance & Accounting</div>
            </div>
            <div class="sign-box" style="position: relative; overflow: visible;">
                <div class="sign-title">Mengetahui / Menyetujui:</div>
                <div class="sign-space" style="position: relative; height: 75px; display: flex; align-items: center; justify-content: center;">
                    <div class="real-stamp {{ $status === 'paid' ? 'stamp-paid' : ($status === 'partial' ? 'stamp-partial' : 'stamp-unpaid') }}" style="position: absolute; top: -14px;">
                        <div class="stamp-inner-ring">
                            <div class="stamp-top-text">★ {{ strtoupper($company['name'] ?? 'VIRUZS') }} ★</div>
                            <div class="stamp-center">
                                @if(!empty($company['logo']))
                                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="{{ $company['name'] }}" class="stamp-logo" onerror="this.style.display='none';">
                                @endif
                                <div class="stamp-status-ribbon">
                                    <span class="stamp-status-text">{{ $statusLabel }}</span>
                                </div>
                                <div class="stamp-date-text">{{ $issueDateFmt }}</div>
                            </div>
                            <div class="stamp-bottom-text">★ AUTHORIZED STAMP ★</div>
                        </div>
                    </div>
                </div>
                <div class="sign-name">{{ $company['name'] }}</div>
                <div class="sign-role">Pimpinan / Direksi</div>
            </div>
            <div class="sign-box">
                <div class="sign-title">Diterima Oleh (Klien / PIC):</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $invoice->client_pic ?: $invoice->client_name }}</div>
                <div class="sign-role">{{ $invoice->client_name }}</div>
            </div>
        </div>

    </div>

</body>
</html>

