<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; margin: 0; padding: 20px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #f3f4f6; padding-bottom: 20px; }
        .company-info h1 { margin: 0; color: #4f46e5; font-size: 24px; }
        .company-info p { margin: 5px 0; color: #6b7280; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0; color: #111827; font-size: 28px; }
        .invoice-details p { margin: 5px 0; color: #6b7280; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-weight: bold; font-size: 12px; text-transform: uppercase; margin-top: 10px; }
        .status-paid { background-color: #d1fae5; color: #059669; }
        .status-unpaid { background-color: #fee2e2; color: #dc2626; }
        .status-partial { background-color: #fef3c7; color: #d97706; }
        .customer-info { margin-bottom: 40px; }
        .customer-info h3 { margin: 0 0 10px 0; color: #111827; }
        .customer-info p { margin: 5px 0; color: #4b5563; }
        .items-table { w-full: 100%; width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th { background-color: #f9fafb; border-bottom: 2px solid #e5e7eb; text-align: left; padding: 12px; color: #374151; font-weight: bold; }
        .items-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; color: #4b5563; }
        .items-table th.right, .items-table td.right { text-align: right; }
        .total-section { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
        .payment-info { flex: 1; max-width: 420px; }
        .payment-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .payment-title { font-size: 11px; font-weight: bold; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 10px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
        .bank-list { display: flex; flex-direction: column; gap: 8px; }
        .bank-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #e5e7eb; }
        .bank-item:last-child { border-bottom: none; padding-bottom: 0; }
        .bank-name { font-weight: bold; color: #4f46e5; font-size: 13px; }
        .bank-holder { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .bank-account { font-family: Consolas, 'Courier New', Courier, monospace; font-size: 14px; font-weight: bold; color: #111827; letter-spacing: 0.5px; text-align: right; }
        .payment-note { margin: 10px 0 0 0; font-size: 11px; color: #6b7280; line-height: 1.4; border-top: 1px dashed #e5e7eb; padding-top: 8px; }
        .total-table { width: 300px; border-collapse: collapse; }
        .total-table td { padding: 8px 12px; color: #4b5563; }
        .total-table td.right { text-align: right; }
        .total-table tr.grand-total td { font-weight: bold; color: #111827; font-size: 18px; border-top: 2px solid #e5e7eb; padding-top: 12px; }

        /* Signature & Stamp Section */
        .signature-section { display: flex; justify-content: flex-end; margin-top: 35px; margin-bottom: 20px; page-break-inside: avoid; }
        .signature-box { width: 230px; text-align: center; position: relative; }
        .signature-date { margin: 0 0 4px 0; font-size: 12px; color: #4b5563; }
        .signature-title { margin: 0 0 10px 0; font-size: 13px; font-weight: bold; color: #111827; }
        .signature-space { height: 95px; position: relative; display: flex; align-items: center; justify-content: center; }
        .signature-name { margin: 6px 0 0 0; font-size: 12px; font-weight: bold; color: #111827; border-top: 1px solid #374151; padding-top: 4px; }
        .signature-role { margin: 2px 0 0 0; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Official Stamp */
        .stamp-container { position: absolute; top: 50%; left: 45%; transform: translate(-50%, -50%) rotate(-10deg); z-index: 2; pointer-events: none; }
        .official-stamp {
            width: 155px;
            height: 98px;
            border: 2.5px solid #2563eb;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            box-sizing: border-box;
            color: #2563eb;
            position: relative;
            background: rgba(255, 255, 255, 0.45);
            opacity: 0.88;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .official-stamp::before {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            right: 3px;
            bottom: 3px;
            border: 1px dashed #2563eb;
            border-radius: 50%;
        }
        .stamp-header { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; line-height: 1; margin-bottom: 2px; }
        .stamp-logo { max-height: 40px; max-width: 85px; object-fit: contain; margin: 2px 0; }
        .stamp-footer { font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; line-height: 1; margin-top: 2px; }

        .footer { text-align: center; margin-top: 35px; color: #9ca3af; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
        @media print { body { padding: 0; } .invoice-box { border: none; box-shadow: none; max-width: 100%; } .no-print { display: none; } }
        .btn-print { background-color: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ Cetak Invoice</button>

    <div class="invoice-box">
        <div class="header">
            <div class="company-info" style="display: flex; flex-direction: column; align-items: flex-start; gap: 15px;">
                @if($company['logo'])
                    <img src="{{ asset('storage/' . $company['logo']) }}" alt="Logo" style="max-height: 80px; max-width: 150px; object-fit: contain;">
                @endif
                <div>
                    <h1>{{ $company['name'] }}</h1>
                    <p style="white-space: pre-line;">{{ $company['address'] }}</p>
                    @if($company['phone'] || $company['email'])
                    <p style="font-size: 12px; margin-top: 5px;">
                        @if($company['phone']) Telp: {{ $company['phone'] }} @endif
                        @if($company['phone'] && $company['email']) | @endif
                        @if($company['email']) Email: {{ $company['email'] }} @endif
                    </p>
                    @endif
                    @if($company['website'])
                    <p style="font-size: 12px;">Web: {{ $company['website'] }}</p>
                    @endif
                </div>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>No. Tagihan:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Tanggal Terbit:</strong> {{ $invoice->issued_date->format('d M Y') }}</p>
                <p><strong>Jatuh Tempo:</strong> {{ $invoice->due_date->format('d M Y') }}</p>
                <div>
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ $invoice->status == 'paid' ? 'LUNAS' : ($invoice->status == 'unpaid' ? 'BELUM LUNAS' : 'DIBAYAR SEBAGIAN') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="customer-info">
            <h3>Ditagihkan Kepada:</h3>
            <p><strong>{{ $invoice->customer->name }}</strong> ({{ $invoice->customer->customer_code }})</p>
            <p>{{ $invoice->customer->address }}</p>
            <p>Telp: {{ $invoice->customer->phone }}</p>
            <p>Layanan: {{ $invoice->customer->package ? $invoice->customer->package->name : '-' }}</p>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi Layanan</th>
                    <th>Periode</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
@php
    $taxPpn = (float) ($invoice->customer->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
    
    // Asumsikan total tagihan ($invoice->amount) sudah termasuk PPN jika ada
    $subtotal = $invoice->amount;
    $taxAmount = 0;
    
    if ($taxPpn > 0) {
        $subtotal = round($invoice->amount / (1 + ($taxPpn / 100)));
        $taxAmount = $invoice->amount - $subtotal;
    }

    $banks = $banks ?? (json_decode(\App\Models\Setting::get('payment_banks', '[]'), true) ?: []);
@endphp
                <tr>
                    <td>Tagihan Layanan Internet {{ $invoice->customer->package ? $invoice->customer->package->name : '' }}</td>
                    <td>{{ $invoice->period_label }}</td>
                    <td class="right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="payment-info">
                <div class="payment-box">
                    <div class="payment-title">Informasi Pembayaran / No. Rekening</div>
                    @if(!empty($banks) && count($banks) > 0)
                        <div class="bank-list">
                            @foreach($banks as $b)
                                <div class="bank-item">
                                    <div>
                                        <div class="bank-name">{{ $b['bank_name'] ?? '-' }}</div>
                                        <div class="bank-holder">a.n. {{ $b['account_name'] ?? '-' }}</div>
                                    </div>
                                    <div class="bank-account">{{ $b['account_number'] ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="margin: 0; font-size: 11px; color: #6b7280;">Silakan hubungi admin untuk informasi rekening pembayaran.</p>
                    @endif
                    <p class="payment-note">* Sertakan No. Tagihan <strong>{{ $invoice->invoice_number }}</strong> saat melakukan transfer.</p>
                </div>
            </div>

            <table class="total-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($taxAmount > 0)
                <tr>
                    <td>PPN ({{ $taxPpn }}%)</td>
                    <td class="right">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="border-top: 1px solid #e5e7eb;">
                    <td style="padding-top: 10px;">Total Tagihan</td>
                    <td class="right" style="padding-top: 10px;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Sudah Dibayar</td>
                    <td class="right text-green">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Sisa Tagihan</td>
                    <td class="right">Rp {{ number_format($invoice->remaining, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <p class="signature-date">{{ $invoice->issued_date ? $invoice->issued_date->format('d M Y') : date('d M Y') }}</p>
                <p class="signature-title">Finance,</p>
                <div class="signature-space">
                    @if(!empty($company['logo']))
                        <div class="stamp-container">
                            <div class="official-stamp">
                                <div class="stamp-header">{{ $company['name'] }}</div>
                                <img src="{{ asset('storage/' . $company['logo']) }}" class="stamp-logo" alt="Stempel">
                                <div class="stamp-footer">★ FINANCE ★</div>
                            </div>
                        </div>
                    @endif
                </div>
                <p class="signature-name">( Finance )</p>
                <p class="signature-role">{{ $company['name'] }}</p>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih atas kepercayaan Anda menggunakan layanan kami.</p>
            <p>Harap melakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari pemutusan layanan.</p>
        </div>
    </div>
</body>
</html>
