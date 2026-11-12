<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Pasang Baru (PSB) - {{ $customer->name }} ({{ $customer->customer_code }})</title>
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
            background-color: #059669;
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
            background-color: #047857;
        }
        .btn-wa {
            background-color: #25d366;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s;
        }
        .btn-wa:hover {
            background-color: #128c7e;
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
            border-bottom: 2px solid #059669;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .company-info h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #064e3b;
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
            color: #059669;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title .doc-num {
            margin: 4px 0 0;
            font-size: 13px;
            color: #0f172a;
            font-family: Consolas, monospace;
            font-weight: 700;
        }
        .statement-intro {
            background: #ecfdf5;
            border-left: 4px solid #059669;
            padding: 10px 14px;
            font-size: 12px;
            color: #065f46;
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
            margin-bottom: 14px;
        }
        .grid-table td {
            padding: 7px 10px;
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
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
        }
        .invoice-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 2px solid #cbd5e1;
        }
        .invoice-table td {
            padding: 10px 12px;
            font-size: 12.5px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .invoice-table .total-row td {
            font-weight: 800;
            font-size: 13.5px;
            background-color: #f8fafc;
            border-top: 2px solid #059669;
            color: #064e3b;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-green {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .badge-red {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .badge-gray {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        /* ══════════════════════════════════════════════════════════════
           REAL OFFICIAL CORPORATE RUBBER STAMP (STEMPEL RESMI BERLOGO)
           ══════════════════════════════════════════════════════════════ */
        .stamp-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin: 18px 0;
            padding: 12px 18px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            page-break-inside: avoid;
        }
        .stamp-wrap {
            flex-shrink: 0;
        }
        .real-stamp {
            position: relative;
            display: inline-block;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 2.5px solid currentColor;
            padding: 3px;
            box-sizing: border-box;
            transform: rotate(-5deg);
            opacity: 0.92;
            user-select: none;
            font-family: 'Arial', 'Helvetica', sans-serif;
            text-align: center;
            background: rgba(255, 255, 255, 0.4);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .real-stamp::before {
            content: '';
            position: absolute;
            inset: 4px;
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
            border: 1.5px solid currentColor;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 5px 4px 6px;
            z-index: 1;
        }
        .stamp-top-text {
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            line-height: 1.1;
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
            max-height: 28px;
            max-width: 68px;
            object-fit: contain;
            margin-bottom: 2px;
            filter: drop-shadow(0 0 0.5px currentColor);
        }
        .stamp-logo-fallback {
            font-size: 10px;
            letter-spacing: 2px;
            margin-bottom: 2px;
            font-weight: 900;
        }
        .stamp-status-ribbon {
            width: 100%;
            border-top: 1.5px solid currentColor;
            border-bottom: 1.5px solid currentColor;
            padding: 2px 0;
            margin: 1px 0;
            background: rgba(255, 255, 255, 0.75);
        }
        .stamp-status-text {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            display: block;
            line-height: 1.1;
        }
        .stamp-date-text {
            font-size: 7.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-top: 2px;
            line-height: 1;
        }
        .stamp-bottom-text {
            font-size: 7.5px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            line-height: 1.1;
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
        .stamp-free {
            color: #0f766e;
            border-color: #0f766e;
        }
        .stamp-desc-box {
            flex: 1;
            min-width: 0;
        }
        .stamp-desc-title {
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .stamp-desc-title.text-green { color: #047857; }
        .stamp-desc-title.text-red { color: #dc2626; }
        .stamp-desc-title.text-teal { color: #0f766e; }
        .stamp-desc-meta {
            font-size: 11.5px;
            color: #334155;
            margin-bottom: 6px;
        }
        .stamp-desc-note {
            font-size: 11px;
            color: #64748b;
            line-height: 1.5;
        }
        .stamp-signature-stamp {
            width: 105px !important;
            height: 105px !important;
            opacity: 0.82;
            transform: rotate(-6deg);
            position: absolute;
            top: -24px;
            left: 50%;
            margin-left: -52px;
            pointer-events: none;
        }
        .stamp-signature-stamp .stamp-logo {
            max-height: 20px;
            max-width: 50px;
        }
        .stamp-signature-stamp .stamp-status-text {
            font-size: 8.5px;
            letter-spacing: 1px;
        }
        .stamp-signature-stamp .stamp-top-text,
        .stamp-signature-stamp .stamp-bottom-text {
            font-size: 6.5px;
            letter-spacing: 0.8px;
        }
        .stamp-signature-stamp .stamp-inner-ring {
            padding: 3px 2px 4px;
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
            min-height: 28px;
        }
        .sign-space {
            height: 55px;
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

    @php
        $cleanedPhone = preg_replace('/[^0-9]/', '', (string)$customer->phone);
        if (str_starts_with($cleanedPhone, '0')) {
            $cleanedPhone = '62' . substr($cleanedPhone, 1);
        } elseif (str_starts_with($cleanedPhone, '8')) {
            $cleanedPhone = '62' . $cleanedPhone;
        }
        $invNumber = 'INV-PSB-' . ($customer->customer_code ?: 'CUS-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT));
        $psbFee = (float)($customer->installation_fee ?? 0);
        $psbFeeFmt = 'Rp ' . number_format($psbFee, 0, ',', '.');
        $actDateFmt = $customer->activation_date ? \Carbon\Carbon::parse($customer->activation_date)->translatedFormat('d F Y') : \Carbon\Carbon::now()->translatedFormat('d F Y');
        
        $psbStatus = $customer->psb_payment_status;
        if (!$psbStatus || $psbFee <= 0) {
            $psbStatus = ($psbFee > 0) ? ($psbStatus ?: 'paid') : 'free';
        }
        $isFree = ($psbStatus === 'free' || $psbFee <= 0);
        $isUnpaid = (!$isFree && $psbStatus === 'unpaid');
        $isPaid = (!$isFree && !$isUnpaid);

        $paidDateFmt = $customer->psb_paid_at ? \Carbon\Carbon::parse($customer->psb_paid_at)->translatedFormat('d F Y') : $actDateFmt;
        $payMethod = $customer->psb_payment_method ? strtoupper($customer->psb_payment_method) : 'TUNAI / TRANSFER';

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
        $stampClass = $isPaid ? 'stamp-paid' : ($isUnpaid ? 'stamp-unpaid' : 'stamp-free');

        if ($isFree) {
            $statusLabel = 'BEBAS BIAYA';
            $statusBadgeClass = 'badge-gray';
            $waStatusText = "🎁 *Status Pembayaran*: GRATIS (Promo Bebas Biaya Registrasi)\n";
            $waExtra = "_Layanan aktif dengan promo bebas biaya pasang baru._\n\n";
        } elseif ($isUnpaid) {
            $statusLabel = 'BELUM LUNAS';
            $statusBadgeClass = 'badge-red';
            $waStatusText = "⚠️ *Status Pembayaran*: BELUM LUNAS (MENUNGGAK)\n"
                . "💰 *Total Tagihan PSB*: {$psbFeeFmt}\n\n"
                . "*Petunjuk Pembayaran Transfer:*\n"
                . "🏦 *BANK BRI*: 410101025028534\n"
                . "👤 *A/N*: Deri Gantinayasa\n"
                . "Atau bayar tunai melalui teknisi resmi kami.\n\n"
                . "Setelah transfer, kirim bukti bayar ke No. WhatsApp ini.\n";
            $waExtra = "Aplikasi Pelanggan: https://viruzs.rlradius.com\n\n";
        } else {
            $statusLabel = 'LUNAS';
            $statusBadgeClass = 'badge-green';
            $waStatusText = "✅ *Status Pembayaran*: LUNAS (Sudah Diterima via {$payMethod})\n";
            $waExtra = "_Simpan pesan ini sebagai bukti pembayaran dan registrasi resmi pemasangan baru._\n\n";
        }

        $waMessage = "*INVOICE PEMASANGAN BARU (PSB)*\n"
            . "*{$company['name']}*\n"
            . "----------------------------------------\n"
            . "Halo Bpk/Ibu *{$customer->name}*,\n"
            . "Terima kasih telah mempercayakan kebutuhan internet Anda kepada kami. Layanan internet Anda telah *BERHASIL DIAKTIVASI* dan siap digunakan.\n\n"
            . "Berikut rincian Invoice Biaya Pasang Baru (PSB):\n"
            . "📄 *No. Invoice*: {$invNumber}\n"
            . "🆔 *ID Pelanggan*: {$customer->customer_code}\n"
            . "👤 *Nama*: {$customer->name}\n"
            . "📞 *WhatsApp*: {$customer->phone}\n"
            . "📍 *Alamat*: {$customer->address}\n"
            . "📦 *Paket Layanan*: " . ($customer->package->name ?? 'Internet Service') . "\n"
            . "📅 *Tanggal Aktivasi*: {$actDateFmt}\n"
            . "💰 *Biaya Pasang Baru (PSB)*: {$psbFeeFmt}\n"
            . $waStatusText
            . "----------------------------------------\n"
            . $waExtra
            . (!empty($company['phone']) ? "Layanan Bantuan & CS: *{$company['phone']}*\n\n" : "")
            . "Salam,\n*{$company['name']}*";
            
        $waUrl = $cleanedPhone 
            ? "https://wa.me/{$cleanedPhone}?text=" . rawurlencode($waMessage)
            : "https://wa.me/?text=" . rawurlencode($waMessage);
    @endphp

    <!-- ACTION BAR (TIDAK IKUT TERCETAK) -->
    <div class="action-bar no-print">
        <div>
            <strong style="color: #064e3b; font-size: 14px;">Invoice Pemasangan Baru (PSB) & Bukti Aktivasi</strong>
            <span style="font-size: 12px; color: #64748b; margin-left: 8px;">No: {{ $invNumber }}</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ $waUrl }}" target="_blank" class="btn-wa">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/></svg>
                Kirim ke WhatsApp Pelanggan
            </a>
            <button onclick="window.print()" class="btn-print">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Invoice (Print)
            </button>
            <a href="javascript:window.close()" class="btn-close">Tutup</a>
        </div>
    </div>

    <!-- LEMBAR CETAK (PRINT SHEET) -->
    <div class="sheet">
        <!-- HEADER KOP SURAT -->
        <div class="header">
            <div style="display: flex; align-items: center; gap: 16px;">
                @if(!empty($logoUrl))
                    <div style="flex-shrink: 0; max-width: 195px;">
                        <img src="{{ $logoUrl }}" alt="{{ $company['name'] }}" style="max-height: 62px; max-width: 190px; width: auto; height: auto; object-fit: contain; display: block;" onerror="this.style.display='none'">
                    </div>
                    <div style="width: 2px; height: 54px; background-color: #cbd5e1; flex-shrink: 0;"></div>
                @endif
                <div class="company-info">
                    <h1>{{ $company['name'] }}</h1>
                    <p>{{ $company['address'] }}</p>
                    <p>
                        @if(!empty($company['phone'])) Telp/WA: {{ $company['phone'] }} @endif
                        @if(!empty($company['email'])) &bull; Email: {{ $company['email'] }} @endif
                        @if(!empty($company['website'])) &bull; Website: {{ $company['website'] }} @endif
                    </p>
                </div>
            </div>
            <div class="doc-title">
                <h2>INVOICE PSB & AKTIVASI</h2>
                <div class="doc-num">{{ $invNumber }}</div>
                <div style="font-size: 11px; margin-top: 4px;">
                    <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                    <span style="color: #64748b; margin-left: 4px;">&bull; Aktivasi: <strong>{{ $actDateFmt }}</strong></span>
                </div>
            </div>
        </div>

        <!-- INTRODUKSI -->
        <div class="statement-intro">
            <strong>Bukti Resmi Pembayaran Pasang Baru (PSB) & Aktivasi Layanan:</strong> 
            Dokumen ini menyatakan bahwa proses registrasi, penarikan jalur kabel, instalasi perangkat di lokasi pelanggan, serta aktivasi sistem telah selesai dilaksanakan dengan sukses dan siap digunakan.
        </div>

        <!-- 1. DATA PELANGGAN -->
        <div class="section-title">
            <span>1. Data Pelanggan</span>
        </div>
        <table class="grid-table">
            <tr>
                <td class="label">ID Pelanggan (Customer Code)</td>
                <td class="val"><strong style="font-family: Consolas; font-size: 13px; color: #065f46;">{{ $customer->customer_code }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nama Lengkap Pelanggan</td>
                <td class="val"><strong style="font-size: 13px;">{{ $customer->name }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nomor Telepon / WhatsApp</td>
                <td class="val">{{ $customer->phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Pemasangan</td>
                <td class="val">{{ $customer->address ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Wilayah / Area Layanan</td>
                <td class="val">
                    {{ $customer->areaModel ? $customer->areaModel->name : ($customer->area ?: '-') }}
                </td>
            </tr>
        </table>

        <!-- 2. DATA LAYANAN & PERANGKAT -->
        <div class="section-title">
            <span>2. Data Layanan & Perangkat Terpasang</span>
        </div>
        <table class="grid-table">
            <tr>
                <td class="label">Paket Berlangganan</td>
                <td class="val">
                    <strong style="color: #0f172a;">{{ $customer->package ? $customer->package->name : 'Internet Service' }}</strong>
                    @if($customer->package && $customer->package->speed)
                        <span class="badge badge-green" style="margin-left: 6px;">{{ $customer->package->speed }} Mbps</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Jenis Layanan / Tagihan</td>
                <td class="val">
                    <span style="text-transform: capitalize; font-weight: 600;">{{ $customer->billing_type ?? 'Prabayar' }}</span>
                    &bull; Status: <span class="badge badge-green">Aktif</span>
                </td>
            </tr>
            <tr>
                <td class="label">Perangkat Modem (ONT)</td>
                <td class="val">
                    @if($customer->ont)
                        <strong>{{ $customer->ont->brand ?? 'ONT' }} {{ $customer->ont->model ?? '' }}</strong>
                        <span style="color: #64748b; font-family: Consolas; margin-left: 6px;">(SN: {{ $customer->ont->serial_number ?: '-' }})</span>
                    @else
                        <span style="color: #64748b;">Perangkat Terpasang Sesuai SOP</span>
                    @endif
                </td>
            </tr>
            @if($customer->pppoe_user)
            <tr>
                <td class="label">Username PPPoE / Akun</td>
                <td class="val"><span style="font-family: Consolas; font-weight: 600; color: #1e293b;">{{ $customer->pppoe_user }}</span></td>
            </tr>
            @endif
            <tr>
                <td class="label">Petugas Sales / Referral</td>
                <td class="val">{{ $customer->sales ? $customer->sales->name : 'Pusat / Direct' }}</td>
            </tr>
        </table>

        <!-- 3. RINCIAN BIAYA PASANG BARU (PSB) -->
        <div class="section-title">
            <span>3. Rincian Biaya Pasang Baru (PSB)</span>
        </div>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 55%;">Deskripsi Layanan / Item</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 25%; text-align: right;">Total Biaya</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; color: #64748b;">1</td>
                    <td>
                        <strong style="color: #0f172a;">Biaya Pemasangan Baru (PSB) & Registrasi</strong>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                            Termasuk penarikan kabel dropcore, setting ONT, penyambungan port ODP, dan aktivasi koneksi internet.
                        </div>
                    </td>
                    <td style="text-align: center; font-weight: 600;">1 Paket</td>
                    <td style="text-align: right; font-weight: 700; font-family: Consolas, monospace;">
                        {{ $psbFeeFmt }}
                    </td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Tagihan Biaya Pasang Baru:</td>
                    <td style="text-align: right; font-family: Consolas, monospace; font-size: 14px;">
                        {{ $psbFeeFmt }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- STATUS CAP LUNAS / BELUM LUNAS / BEBAS BIAYA (REAL STEMPEL RESMI BERLOGO) -->
        <div class="stamp-section">
            <div class="stamp-wrap">
                <div class="real-stamp {{ $stampClass }}">
                    <div class="stamp-inner-ring">
                        <div class="stamp-top-text">★ {{ strtoupper($company['name'] ?? 'VIRUZS') }} ★</div>
                        <div class="stamp-center">
                            @if(!empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="{{ $company['name'] }}" class="stamp-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div class="stamp-logo-fallback" style="display: none;">★ ★ ★</div>
                            @else
                                <div class="stamp-logo-fallback">★ ★ ★</div>
                            @endif
                            <div class="stamp-status-ribbon">
                                <span class="stamp-status-text">{{ $statusLabel }}</span>
                            </div>
                            @if($isPaid && !empty($paidDateFmt))
                                <div class="stamp-date-text">{{ $paidDateFmt }}</div>
                            @elseif($isFree)
                                <div class="stamp-date-text">PROMO REGISTRASI</div>
                            @else
                                <div class="stamp-date-text">TAGIHAN PIUTANG</div>
                            @endif
                        </div>
                        <div class="stamp-bottom-text">★ FINANCE & BILLING ★</div>
                    </div>
                </div>
            </div>

            <div class="stamp-desc-box">
                @if($isPaid)
                    <div class="stamp-desc-title text-green">
                        ✓ PEMBAYARAN TELAH DITERIMA & LUNAS
                    </div>
                    <div class="stamp-desc-meta">
                        Metode: <strong>{{ $payMethod }}</strong> &bull; Tgl Pelunasan: <strong>{{ $paidDateFmt }}</strong>
                        @if($customer->psbReceiver) &bull; Diterima Oleh: <strong>{{ $customer->psbReceiver->name }}</strong> @endif
                    </div>
                    <div class="stamp-desc-note">
                        Invoice ini sah sebagai bukti tanda terima pembayaran resmi pasang baru dan registrasi aktivasi layanan internet.
                    </div>
                @elseif($isUnpaid)
                    <div class="stamp-desc-title text-red">
                        ⚠️ MENUNGGAK / MENUNGGU PEMBAYARAN
                    </div>
                    <div class="stamp-desc-meta">
                        Status: <strong>PIUTANG PSB</strong> &bull; Total Tagihan: <strong>{{ $psbFeeFmt }}</strong>
                    </div>
                    <div class="stamp-desc-note">
                        Silakan lakukan pembayaran tunai ke petugas teknisi resmi atau transfer ke rekening <strong>BANK BRI: 410101025028534 a.n Deri Gantinayasa</strong>. Setelah transfer, kirim bukti bayar ke WhatsApp layanan pelanggan.
                    </div>
                @else
                    <div class="stamp-desc-title text-teal">
                        🎁 PROGRAM PROMO BEBAS BIAYA REGISTRASI
                    </div>
                    <div class="stamp-desc-meta">
                        Status: <strong>GRATIS (Bebas Biaya Pasang)</strong> &bull; Biaya Tagihan: <strong>Rp 0</strong>
                    </div>
                    <div class="stamp-desc-note">
                        Pelanggan mendapatkan fasilitas bebas biaya pasang baru (PSB). Layanan internet langsung aktif tanpa kewajiban biaya registrasi.
                    </div>
                @endif
            </div>
        </div>

        <!-- CATATAN & SYARAT KETENTUAN -->
        <div class="terms">
            <strong>Catatan & Ketentuan Layanan:</strong>
            <ol>
                <li>Invoice ini merupakan bukti sah pembayaran Biaya Pasang Baru (PSB) dan registrasi pelanggan internet {{ $company['name'] }}.</li>
                <li>Perangkat ONT / Router dipinjamkan / dipasang selama masa berlangganan aktif dan dilindungi garansi teknis penyedia.</li>
                <li>Tagihan rutin bulanan berikutnya akan diterbitkan sesuai dengan siklus tagihan dan paket internet yang telah dipilih.</li>
                <li>Untuk bantuan kendala koneksi atau konfirmasi tagihan, pelanggan dapat menghubungi layanan pelanggan resmi di <strong>{{ $company['phone'] ?? 'Call Center' }}</strong>.</li>
            </ol>
        </div>

        <!-- TANDA TANGAN -->
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">Pelanggan Yang Menerima,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $customer->name }}</div>
                <div class="sign-role">Pelanggan</div>
            </div>
            <div class="sign-box" style="border: none; background: transparent;">
                <!-- Ruang tengah -->
            </div>
            <div class="sign-box" style="position: relative; overflow: visible;">
                <div class="sign-title">Diterbitkan & Diverifikasi Oleh,</div>
                <div class="sign-space" style="position: relative; height: 60px;">
                    <div class="real-stamp stamp-signature-stamp {{ $stampClass }}">
                        <div class="stamp-inner-ring">
                            <div class="stamp-top-text">★ {{ strtoupper($company['name'] ?? 'VIRUZS') }} ★</div>
                            <div class="stamp-center">
                                @if(!empty($logoUrl))
                                    <img src="{{ $logoUrl }}" alt="{{ $company['name'] }}" class="stamp-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div class="stamp-logo-fallback" style="display: none;">★ ★ ★</div>
                                @else
                                    <div class="stamp-logo-fallback">★ ★ ★</div>
                                @endif
                                <div class="stamp-status-ribbon">
                                    <span class="stamp-status-text">{{ $statusLabel }}</span>
                                </div>
                            </div>
                            <div class="stamp-bottom-text">★ OFFICIAL STAMP ★</div>
                        </div>
                    </div>
                </div>
                <div class="sign-name">{{ auth()->user()->name ?? 'Petugas Administrasi' }}</div>
                <div class="sign-role">{{ $company['name'] }}</div>
            </div>
        </div>

    </div>

</body>
</html>

