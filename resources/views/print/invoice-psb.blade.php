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
        .paid-stamp {
            display: inline-block;
            border: 3px solid #059669;
            color: #059669;
            font-weight: 900;
            font-size: 16px;
            letter-spacing: 2px;
            padding: 6px 16px;
            text-transform: uppercase;
            border-radius: 8px;
            transform: rotate(-5deg);
            opacity: 0.9;
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
            . "✅ *Status Pembayaran*: LUNAS (Sudah Diterima)\n"
            . "----------------------------------------\n"
            . "_Simpan pesan ini sebagai bukti pembayaran dan registrasi resmi pemasangan baru._\n\n"
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
            <div class="company-info">
                <h1>{{ $company['name'] }}</h1>
                <p>{{ $company['address'] }}</p>
                <p>
                    @if(!empty($company['phone'])) Telp/WA: {{ $company['phone'] }} @endif
                    @if(!empty($company['email'])) &bull; Email: {{ $company['email'] }} @endif
                    @if(!empty($company['website'])) &bull; Website: {{ $company['website'] }} @endif
                </p>
            </div>
            <div class="doc-title">
                <h2>INVOICE PSB & AKTIVASI</h2>
                <div class="doc-num">{{ $invNumber }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                    Tgl Aktivasi: <strong>{{ $actDateFmt }}</strong>
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

        <!-- STATUS CAP LUNAS -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin: 16px 0;">
            <div>
                <span class="paid-stamp">LUNAS</span>
                <span style="font-size: 11px; color: #059669; font-weight: 700; margin-left: 10px;">
                    Pembayaran telah diterima dan diverifikasi
                </span>
            </div>
            <div style="text-align: right; font-size: 11px; color: #64748b;">
                Tanggal Pelunasan: <strong>{{ $actDateFmt }}</strong>
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
            <div class="sign-box">
                <div class="sign-title">Diterbitkan & Diverifikasi Oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ auth()->user()->name ?? 'Petugas Administrasi' }}</div>
                <div class="sign-role">{{ $company['name'] }}</div>
            </div>
        </div>

    </div>

</body>
</html>

