<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Pembayaran Proyek - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 24px;
            background-color: #f8fafc;
        }
        .sheet {
            max-width: 820px;
            margin: auto;
            padding: 36px 42px;
            background: #ffffff;
            border: 2px solid #334155;
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
                border: 2px solid #334155;
                box-shadow: none;
                padding: 20px 25px;
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
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 7px 16px;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-close {
            background: transparent;
            color: #cbd5e1;
            border: 1px solid #475569;
            padding: 7px 12px;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
        }
        .kw-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .company-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-info {
            font-size: 11px;
            color: #64748b;
        }
        .kw-title {
            text-align: right;
        }
        .kw-title h2 {
            margin: 0;
            font-size: 22px;
            color: #0f172a;
            letter-spacing: 2px;
        }
        .kw-no {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        .row-item {
            display: flex;
            margin-bottom: 12px;
            align-items: baseline;
        }
        .row-label {
            width: 190px;
            font-weight: 600;
            color: #475569;
            flex-shrink: 0;
        }
        .row-dots {
            width: 15px;
            color: #64748b;
        }
        .row-value {
            flex: 1;
            color: #0f172a;
            font-weight: 600;
            border-bottom: 1px dotted #cbd5e1;
            padding-bottom: 3px;
        }
        .terbilang-banner {
            background: #eff6ff;
            border: 1px dashed #3b82f6;
            padding: 10px 14px;
            border-radius: 6px;
            font-style: italic;
            font-weight: 700;
            color: #1e40af;
            font-size: 13px;
        }
        .bottom-kw {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .nominal-badge {
            border: 2px solid #0f172a;
            background: #f8fafc;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            display: inline-block;
        }
        /* ── Real Rubber Stamp Style ── */
        .real-stamp {
            position: relative;
            display: inline-block;
            width: 105px;
            height: 105px;
            border-radius: 50%;
            border: 2px solid #047857;
            color: #047857;
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
        .sign-area {
            text-align: center;
            width: 220px;
        }
        .sign-date {
            font-size: 12px;
            color: #475569;
            margin-bottom: 50px;
        }
        .sign-person {
            font-weight: 700;
            border-top: 1px solid #334155;
            padding-top: 4px;
            color: #0f172a;
        }
    </style>
</head>
<body>

    <div class="action-bar no-print">
        <div>
            <strong>Kwitansi Pembayaran Resmi</strong>
            <span style="font-size: 12px; color: #94a3b8; margin-left: 8px;">No: KW-PRJ-{{ $invoice->invoice_number }}</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn-print">Cetak Kwitansi</button>
            <a href="javascript:window.close()" class="btn-close">Tutup</a>
        </div>
    </div>

    <div class="sheet">
        <div class="kw-header">
            <div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-info">{{ $company['address'] }} | Telp: {{ $company['phone'] }}</div>
            </div>
            <div class="kw-title">
                <h2>KWITANSI</h2>
                <div class="kw-no">No: KW-{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        <div class="row-item">
            <span class="row-label">Telah Diterima Dari</span>
            <span class="row-dots">:</span>
            <span class="row-value" style="font-size: 14px; color: #1e40af;">{{ $invoice->client_name }} @if($invoice->client_pic) (PIC: {{ $invoice->client_pic }}) @endif</span>
        </div>

        <div class="row-item">
            <span class="row-label">Uang Sejumlah</span>
            <span class="row-dots">:</span>
            <div class="row-value terbilang-banner">
                # {{ $terbilangNominal }} #
            </div>
        </div>

        <div class="row-item">
            <span class="row-label">Untuk Pembayaran</span>
            <span class="row-dots">:</span>
            <span class="row-value">
                Pembayaran Invoice Proyek <strong>{{ $invoice->invoice_number }}</strong> — {{ $invoice->project_title }}
                @if($invoice->status === 'paid') (Pelunasan 100%) @else (Pembayaran Termin / DP) @endif
            </span>
        </div>

        @if(!empty($invoice->po_spk_number))
        <div class="row-item">
            <span class="row-label">Dasar Kontrak / PO</span>
            <span class="row-dots">:</span>
            <span class="row-value">{{ $invoice->po_spk_number }}</span>
        </div>
        @endif

        <div class="row-item">
            <span class="row-label">Metode Pembayaran</span>
            <span class="row-dots">:</span>
            <span class="row-value">{{ strtoupper($invoice->payment_method ?: 'TRANSFER BANK') }} @if($invoice->payment_notes) - Ref: {{ $invoice->payment_notes }} @endif</span>
        </div>

        <div class="bottom-kw">
            <div>
                <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; text-transform: uppercase; font-weight: 700;">Nominal Pembayaran Diterima:</div>
                <div class="nominal-badge">
                    Rp {{ number_format($amountToReceipt, 0, ',', '.') }}
                </div>
            </div>

            <div class="sign-area" style="position: relative; overflow: visible;">
                <div class="sign-date">
                    {{ \Carbon\Carbon::parse($invoice->paid_at ?? now())->translatedFormat('d F Y') }}
                </div>
                <div style="height: 65px; display: flex; align-items: center; justify-content: center; position: relative;">
                    <div class="real-stamp" style="position: absolute; top: -15px;">
                        <div class="stamp-inner-ring">
                            <div class="stamp-top-text">★ {{ strtoupper($company['name'] ?? 'VIRUZS') }} ★</div>
                            <div class="stamp-center">
                                @if(!empty($company['logo']))
                                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="{{ $company['name'] }}" class="stamp-logo" onerror="this.style.display='none';">
                                @endif
                                <div class="stamp-status-ribbon">
                                    <span class="stamp-status-text">LUNAS</span>
                                </div>
                                <div class="stamp-date-text">{{ \Carbon\Carbon::parse($invoice->paid_at ?? now())->format('d/m/Y') }}</div>
                            </div>
                            <div class="stamp-bottom-text">★ OFFICIAL RECEIPT ★</div>
                        </div>
                    </div>
                </div>
                <div class="sign-person">
                    {{ $invoice->creator->name ?? 'Bagian Keuangan' }}<br>
                    <span style="font-size: 10.5px; font-weight: 400; color: #64748b;">Finance & Kasir</span>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

