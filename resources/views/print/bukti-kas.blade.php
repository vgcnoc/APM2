<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docTitle }} - {{ $docNumber }}</title>
    @php
        $rp = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
        $logoUrl = $companyLogo ? asset('storage/' . ltrim($companyLogo, '/')) : null;
    @endphp
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #f8fafc;
            padding: 24px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .toolbar {
            max-width: 800px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn {
            padding: 7px 16px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-close { background: #334155; color: #f8fafc; }
        .btn-close:hover { background: #475569; }

        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 28px 32px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            position: relative;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .company-name {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .company-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.4;
        }
        .doc-badge {
            text-align: right;
        }
        .doc-title {
            font-size: 15px;
            font-weight: 900;
            color: {{ $type === 'income' ? '#047857' : '#b91c1c' }};
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            font-size: 9px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 1px;
        }
        .doc-number {
            font-size: 11px;
            font-weight: 800;
            color: #334155;
            margin-top: 4px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }
        .meta-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 10.5px;
        }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-label {
            width: 125px;
            color: #64748b;
            font-weight: 600;
            flex-shrink: 0;
        }
        .meta-value {
            color: #0f172a;
            font-weight: 700;
            flex-grow: 1;
        }

        .content-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
        }
        .content-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px 12px;
            text-align: left;
        }
        .content-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }

        .amount-highlight {
            font-size: 15px;
            font-weight: 900;
            color: {{ $type === 'income' ? '#047857' : '#b91c1c' }};
            text-align: right;
            white-space: nowrap;
        }

        .terbilang-box {
            background: #f8fafc;
            border-left: 4px solid {{ $type === 'income' ? '#10b981' : '#f43f5e' }};
            padding: 10px 14px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 24px;
            font-size: 10.5px;
        }
        .terbilang-label {
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            font-size: 9px;
            margin-bottom: 2px;
        }
        .terbilang-text {
            font-style: italic;
            font-weight: 700;
            color: #0f172a;
        }

        .signature-table {
            width: 100%;
            margin-top: 24px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
        }
        .sig-title {
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 60px;
            text-transform: uppercase;
        }
        .sig-name {
            font-weight: 800;
            color: #0f172a;
            border-top: 1px dashed #94a3b8;
            padding-top: 6px;
            display: inline-block;
            min-width: 140px;
        }
        .sig-role {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        .footer-note {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            font-size: 8.5px;
            color: #94a3b8;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .toolbar { display: none !important; }
            .sheet {
                border: 1px solid #94a3b8;
                box-shadow: none;
                padding: 18px 22px;
                max-width: 100%;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div>
            <strong style="font-size: 13px;">{{ $docTitle }}</strong>
            <span style="font-size: 10.5px; color: #94a3b8; margin-left: 8px;">No: {{ $docNumber }}</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-print">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Dokumen (Print)</span>
            </button>
            <button onclick="window.close()" class="btn btn-close">
                <span>Tutup</span>
            </button>
        </div>
    </div>

    <div class="sheet">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="vertical-align: top; width: 60%;">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 40px; margin-bottom: 6px; object-fit: contain;">
                    @endif
                    <div class="company-name">{{ $companyName }}</div>
                    <div class="company-sub">
                        @if(!empty($companyAddress)) {{ $companyAddress }}<br> @endif
                        @if(!empty($companyPhone)) Telp: {{ $companyPhone }} @endif
                        @if(!empty($companyEmail)) | Email: {{ $companyEmail }} @endif
                    </div>
                </td>
                <td style="vertical-align: top; width: 40%;" class="doc-badge">
                    <div class="doc-title">{{ $docTitle }}</div>
                    <div class="doc-subtitle">{{ $docSubtitle }}</div>
                    <div class="doc-number">{{ $docNumber }}</div>
                </td>
            </tr>
        </table>

        <!-- Metadata Grid -->
        <div class="meta-grid">
            <div>
                <div class="meta-row">
                    <span class="meta-label">{{ $type === 'income' ? 'Diterima Dari' : 'Dibayarkan Kepada' }}:</span>
                    <span class="meta-value">{{ $recipientOrPayer }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Kategori Mutasi:</span>
                    <span class="meta-value">[{{ $categoryCode }}] {{ $categoryName }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Wilayah / Cabang:</span>
                    <span class="meta-value">{{ $areaName }}</span>
                </div>
            </div>
            <div>
                <div class="meta-row">
                    <span class="meta-label">Tanggal Transaksi:</span>
                    <span class="meta-value">{{ $dateTime }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Metode / Jalur:</span>
                    <span class="meta-value">{{ $method }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">No. Referensi:</span>
                    <span class="meta-value font-mono">{{ $refNo }}</span>
                </div>
            </div>
        </div>

        <!-- Uraian Pembayaran -->
        <div class="content-box">
            <table class="content-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">No</th>
                        <th>Uraian / Keterangan Transaksi</th>
                        <th style="width: 140px; text-align: right;">Jumlah (Nominal)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: center; font-weight: 700; color: #64748b;">1</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">{{ $description }}</div>
                            <div style="font-size: 9.5px; color: #64748b; margin-top: 3px;">
                                Dicatat oleh: <strong>{{ $userName }}</strong>
                                @if($areaName && $areaName !== '-') | Area: {{ $areaName }} @endif
                            </div>
                        </td>
                        <td class="amount-highlight">
                            {{ $rp($amount) }}
                        </td>
                    </tr>
                    @if($materialTransaction && $materialTransaction->items && $materialTransaction->items->count() > 0)
                        <tr>
                            <td colspan="3" style="background: #f8fafc; padding: 10px 14px; border-top: 1px dashed #cbd5e1;">
                                <div style="font-weight: 800; font-size: 10px; color: #334155; text-transform: uppercase; margin-bottom: 6px;">
                                    📦 Rincian Barang / Material (PO: {{ $materialTransaction->transaction_number }}):
                                </div>
                                <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid #cbd5e1; color: #475569; text-align: left;">
                                            <th style="padding: 4px 6px;">Barang</th>
                                            <th style="padding: 4px 6px; text-align: center;">Qty</th>
                                            <th style="padding: 4px 6px; text-align: right;">Harga Satuan</th>
                                            <th style="padding: 4px 6px; text-align: right;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($materialTransaction->items as $idx => $it)
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 4px 6px; font-weight: 600;">{{ $it->material?->name ?? 'Material' }}</td>
                                                <td style="padding: 4px 6px; text-align: center;">{{ $it->quantity }} {{ $it->unit ?: 'pcs' }}</td>
                                                <td style="padding: 4px 6px; text-align: right;">{{ $rp($it->price_per_unit) }}</td>
                                                <td style="padding: 4px 6px; text-align: right; font-weight: 700;">{{ $rp($it->total_price) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1;">
                        <td colspan="2" style="text-align: right; text-transform: uppercase; font-size: 11px; padding: 10px 12px;">Total Mutasi:</td>
                        <td class="amount-highlight" style="padding: 10px 12px; font-size: 16px;">
                            {{ $rp($amount) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Terbilang Box -->
        <div class="terbilang-box">
            <div class="terbilang-label">Terbilang (In Words):</div>
            <div class="terbilang-text"># {{ $terbilang }} #</div>
        </div>

        <!-- Signature Section -->
        <table class="signature-table">
            <tr>
                <td>
                    <div class="sig-title">Dibuat / Dicatat Oleh,</div>
                    <div class="sig-name">{{ $userName }}</div>
                    <div class="sig-role">Kasir / Administrasi Keuangan</div>
                </td>
                <td>
                    <div class="sig-title">Mengetahui / Disetujui,</div>
                    <div class="sig-name">Pimpinan / Finance</div>
                    <div class="sig-role">Manajer Operasional & Keuangan</div>
                </td>
                <td>
                    <div class="sig-title">{{ $type === 'income' ? 'Penyetor / Pembayar,' : 'Penerima Kas,' }}</div>
                    <div class="sig-name">{{ $recipientOrPayer !== '-' ? $recipientOrPayer : 'Penerima Kas' }}</div>
                    <div class="sig-role">Tanda Tangan & Nama Terang</div>
                </td>
            </tr>
        </table>

        <!-- Footer Note -->
        <div class="footer-note">
            <span>Sistem Billing & Keuangan ISP - Dicetak otomatis pada: {{ now()->translatedFormat('d F Y, H:i:s') }} WIB</span>
            <span>Dokumen Sah & Tercatat di Buku Kas Umum</span>
        </div>
    </div>

</body>
</html>

