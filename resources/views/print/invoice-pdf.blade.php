<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->status === 'paid' ? 'Kwitansi Pembayaran' : 'Tagihan Invoice' }} #{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            border-bottom: 3px solid #1e293b;
            margin-bottom: 14px;
            padding-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 9.5px;
            color: #475569;
            margin: 0;
            line-height: 1.35;
        }
        .doc-type-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
            letter-spacing: 0.6px;
        }
        .badge-paid {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .badge-partial {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .badge-unpaid {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .box-table {
            margin-bottom: 14px;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            vertical-align: top;
        }
        .info-card-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .meta-inner td {
            padding: 2px 0;
            font-size: 10px;
            vertical-align: top;
        }
        .meta-label {
            color: #64748b;
            width: 38%;
            font-weight: bold;
        }
        .meta-val {
            color: #0f172a;
        }
        .items-table {
            margin-bottom: 14px;
        }
        .items-table th {
            background: #1e293b;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 8px 10px;
            border: 1px solid #1e293b;
            text-align: left;
        }
        .items-table td {
            padding: 10px;
            border: 1px solid #cbd5e1;
            font-size: 10.5px;
            vertical-align: top;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-mono { font-family: Courier, monospace; }
        .font-bold { font-weight: bold; }

        .summary-table td {
            vertical-align: top;
        }
        .totals-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10.5px;
        }
        .totals-grand td {
            background: #f1f5f9;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            padding: 7px 8px;
        }
        .history-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin: 12px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #cbd5e1;
        }
        .history-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .history-table td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            font-size: 9.5px;
        }
        .bank-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            font-size: 9.5px;
        }
        .bank-box-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .stamp-box {
            display: inline-block;
            padding: 6px 18px;
            border: 2px solid;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 6px;
        }
        .stamp-paid {
            border-color: #16a34a;
            color: #15803d;
            background: #f0fdf4;
        }
        .stamp-partial {
            border-color: #d97706;
            color: #b45309;
            background: #fffbeb;
        }
        .stamp-unpaid {
            border-color: #dc2626;
            color: #b91c1c;
            background: #fef2f2;
        }
        .sign-table {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .sign-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
        }
        .sign-space {
            height: 52px;
        }
        .sign-line {
            display: inline-block;
            min-width: 170px;
            border-top: 1px solid #0f172a;
            padding-top: 3px;
            font-weight: bold;
            color: #0f172a;
        }
        .footer-note {
            margin-top: 16px;
            border-top: 1px dashed #94a3b8;
            padding-top: 6px;
            font-size: 8.5px;
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $customer = $invoice->customer;
        $verifiedPaid = (float) ($invoice->payments ? $invoice->payments->where('status', 'verified')->sum('amount') : ($invoice->total_paid ?? 0));
        $pendingPaid = (float) ($invoice->payments ? $invoice->payments->where('status', 'pending')->sum('amount') : 0);
        $remaining = max(0, (float) $invoice->amount - $verifiedPaid);

        $taxPpn = (float) ($customer->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
        $subtotal = (float) $invoice->amount;
        $taxAmount = 0;
        if ($taxPpn > 0) {
            $subtotal = round($invoice->amount / (1 + ($taxPpn / 100)));
            $taxAmount = $invoice->amount - $subtotal;
        }

        $isPaid = ($invoice->status === 'paid' || $remaining <= 0);
        $isPartial = (!$isPaid && ($invoice->status === 'partial' || $verifiedPaid > 0));

        $lastPayment = $invoice->payments ? $invoice->payments->sortByDesc('payment_date')->first() : null;
        $collectorName = $lastPayment?->collector?->name ?? (auth()->user()->name ?? 'Admin / Kasir');
    @endphp

    <!-- HEADER PERUSAHAAN -->
    <table class="header-table">
        <tr>
            <td style="width: 62%;">
                <table>
                    <tr>
                        @if(!empty($logoBase64))
                            <td style="width: 65px; padding-right: 10px; vertical-align: middle;">
                                <img src="{{ $logoBase64 }}" alt="Logo" style="max-width: 58px; max-height: 52px;">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="company-name">{{ $company['name'] ?? 'VIZBILL ISP NETWORK' }}</div>
                            <div class="company-sub">
                                {{ $company['address'] ?? 'Layanan Akses Internet Cepat & Stabil' }}<br>
                                @if(!empty($company['phone'])) Telp/WA: {{ $company['phone'] }} @endif
                                @if(!empty($company['email'])) | Email: {{ $company['email'] }} @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 38%; text-align: right;">
                <div style="font-size: 10px; font-weight: bold; color: #475569; text-transform: uppercase;">
                    {{ $isPaid ? 'KWITANSI LUNAS / INVOICE' : 'TAGIHAN INVOICE PELANGGAN' }}
                </div>
                <div class="font-mono font-bold" style="font-size: 16px; color: #0f172a; margin: 2px 0 5px 0;">
                    #{{ $invoice->invoice_number }}
                </div>
                @if($isPaid)
                    <span class="doc-type-badge badge-paid">STATUS: LUNAS (PAID)</span>
                @elseif($isPartial)
                    <span class="doc-type-badge badge-partial">STATUS: BAYAR SEBAGIAN</span>
                @else
                    <span class="doc-type-badge badge-unpaid">STATUS: BELUM LUNAS</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- IDENTITAS PELANGGAN & META INVOICE -->
    <table class="box-table">
        <tr>
            <td class="info-card" style="width: 52%;">
                <div class="info-card-title">Ditagihkan Kepada (Pelanggan)</div>
                <table class="meta-inner">
                    <tr>
                        <td class="meta-label">Nama Pelanggan</td>
                        <td class="meta-val"><strong>{{ $customer?->name ?? 'Pelanggan' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="meta-label">ID Pelanggan</td>
                        <td class="meta-val font-mono font-bold" style="color: #1e40af;">{{ $customer?->customer_code ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">No. Telepon / WA</td>
                        <td class="meta-val">{{ $customer?->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Wilayah / Area</td>
                        <td class="meta-val">{{ $customer?->areaModel?->name ?? $customer?->area ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Alamat</td>
                        <td class="meta-val">{{ $customer?->address ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 2%;"></td>
            <td class="info-card" style="width: 46%;">
                <div class="info-card-title">Rincian Dokumen Tagihan</div>
                <table class="meta-inner">
                    <tr>
                        <td class="meta-label">Nomor Invoice</td>
                        <td class="meta-val font-mono font-bold">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Periode Tagihan</td>
                        <td class="meta-val"><strong>{{ $invoice->period_label ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal Terbit</td>
                        <td class="meta-val">{{ $invoice->issued_date ? \Carbon\Carbon::parse($invoice->issued_date)->format('d/m/Y') : ($invoice->created_at ? $invoice->created_at->format('d/m/Y') : '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Jatuh Tempo</td>
                        <td class="meta-val"><strong style="color: {{ $isPaid ? '#15803d' : '#b91c1c' }};">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : '-' }}</strong></td>
                    </tr>
                    @if($invoice->promise_date && !$isPaid)
                        <tr>
                            <td class="meta-label">Janji Bayar</td>
                            <td class="meta-val"><strong style="color: #7e22ce;">{{ \Carbon\Carbon::parse($invoice->promise_date)->format('d/m/Y') }}</strong></td>
                        </tr>
                    @endif
                    @if($lastPayment)
                        <tr>
                            <td class="meta-label">Tgl Pembayaran</td>
                            <td class="meta-val"><strong style="color: #15803d;">{{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d/m/Y') }} ({{ strtoupper($lastPayment->payment_method) }})</strong></td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- TABEL ITEM LAYANAN -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">No</th>
                <th style="width: 52%;">Deskripsi Layanan Internet</th>
                <th style="width: 20%;">Periode</th>
                <th style="width: 22%;" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>
                    <div class="font-bold" style="color: #0f172a;">
                        Layanan Internet: {{ $customer?->package?->name ?? 'Paket Reguler' }}
                        @if($customer?->package?->speed_mbps)
                            ({{ $customer->package->speed_mbps }} Mbps)
                        @endif
                    </div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">
                        Tipe Tagihan: {{ ($invoice->is_prorata || $invoice->billing_type === 'prorata') ? 'Prorata' : 'Reguler Bulanan' }}
                        @if(!empty($invoice->notes))
                            <br>Catatan: {{ $invoice->notes }}
                        @endif
                    </div>
                </td>
                <td>{{ $invoice->period_label ?? '-' }}</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($taxPpn > 0)
                <tr>
                    <td class="text-center">2</td>
                    <td>PPN ({{ $taxPpn }}%)</td>
                    <td>{{ $invoice->period_label ?? '-' }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- RINGKASAN PEMBAYARAN & REKENING BANK -->
    <table class="summary-table">
        <tr>
            <td style="width: 52%; padding-right: 12px;">
                @if(!empty($banks) && count($banks) > 0)
                    <div class="bank-box">
                        <div class="bank-box-title">Informasi Rekening Pembayaran Resmi</div>
                        <table>
                            @foreach($banks as $bank)
                                <tr>
                                    <td style="padding: 2px 0; font-weight: bold; width: 32%;">{{ $bank['bank_name'] ?? $bank['name'] ?? 'BANK' }}</td>
                                    <td style="padding: 2px 0; font-family: Courier, monospace; font-weight: bold; width: 36%;">{{ $bank['account_number'] ?? $bank['number'] ?? '-' }}</td>
                                    <td style="padding: 2px 0; color: #475569;">a.n {{ $bank['account_name'] ?? $bank['holder'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif

                <div style="margin-top: 10px;">
                    @if($isPaid)
                        <div class="stamp-box stamp-paid">LUNAS / TERBAYAR</div>
                    @elseif($isPartial)
                        <div class="stamp-box stamp-partial">BAYAR SEBAGIAN (CICILAN)</div>
                    @else
                        <div class="stamp-box stamp-unpaid">BELUM LUNAS</div>
                    @endif
                </div>
            </td>
            <td style="width: 48%;">
                <table class="totals-table">
                    <tr>
                        <td style="color: #475569;">Subtotal Tagihan</td>
                        <td class="text-right font-mono">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($taxPpn > 0)
                        <tr>
                            <td style="color: #475569;">PPN ({{ $taxPpn }}%)</td>
                            <td class="text-right font-mono">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="totals-grand">
                        <td>Total Tagihan</td>
                        <td class="text-right font-mono">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #15803d; font-weight: bold;">Sudah Dibayar (Tervalidasi)</td>
                        <td class="text-right font-mono font-bold" style="color: #15803d;">Rp {{ number_format($verifiedPaid, 0, ',', '.') }}</td>
                    </tr>
                    @if($pendingPaid > 0)
                        <tr>
                            <td style="color: #b45309; font-weight: bold;">Setoran Menunggu Validasi</td>
                            <td class="text-right font-mono font-bold" style="color: #b45309;">Rp {{ number_format($pendingPaid, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr style="background: {{ $remaining > 0 ? '#fef2f2' : '#f0fdf4' }};">
                        <td style="font-weight: bold; color: {{ $remaining > 0 ? '#b91c1c' : '#15803d' }};">Sisa Tagihan Harus Dibayar</td>
                        <td class="text-right font-mono font-bold" style="font-size: 12px; color: {{ $remaining > 0 ? '#b91c1c' : '#15803d' }};">
                            Rp {{ number_format($remaining, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- RIWAYAT PEMBAYARAN / CICILAN JIKA ADA -->
    @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="history-title">Riwayat Penerimaan Pembayaran Tagihan Ini</div>
        <table class="history-table">
            <thead>
                <tr>
                    <th style="width: 6%;" class="text-center">No</th>
                    <th style="width: 18%;">Tanggal Bayar</th>
                    <th style="width: 18%;">Metode</th>
                    <th style="width: 24%;">Petugas / Kolektor</th>
                    <th style="width: 14%;" class="text-center">Status</th>
                    <th style="width: 20%;" class="text-right">Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $idx => $pay)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('d/m/Y') : '-' }}</td>
                        <td style="text-transform: uppercase; font-weight: bold;">{{ $pay->payment_method ?? 'CASH' }}</td>
                        <td>{{ $pay->collector?->name ?? 'Sistem / Kasir' }}</td>
                        <td class="text-center">
                            @if($pay->status === 'verified')
                                <span style="color: #15803d; font-weight: bold;">TERVALIDASI</span>
                            @else
                                <span style="color: #b45309; font-weight: bold;">PENDING SETOR</span>
                            @endif
                        </td>
                        <td class="text-right font-mono font-bold">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TANDA TANGAN -->
    <table class="sign-table">
        <tr>
            <td>
                <div>Pelanggan / Penyetor,</div>
                <div class="sign-space"></div>
                <div class="sign-line">{{ $customer?->name ?? 'Pelanggan' }}</div>
                <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">Pelanggan Layanan Internet</div>
            </td>
            <td>
                <div>{{ now()->translatedFormat('d F Y') }}<br>Petugas Penagihan / Kasir,</div>
                <div class="sign-space"></div>
                <div class="sign-line">{{ $collectorName }}</div>
                <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">{{ $company['name'] ?? 'ISP Management' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini dicetak secara sah melalui Sistem Penagihan {{ $company['name'] ?? 'ISP Management' }} pada {{ now()->format('d/m/Y H:i') }} WIB. Simpan dokumen ini sebagai bukti tagihan / pembayaran yang sah.
    </div>
</body>
</html>

