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
        .total-section { display: flex; justify-content: flex-end; }
        .total-table { width: 300px; border-collapse: collapse; }
        .total-table td { padding: 8px 12px; color: #4b5563; }
        .total-table td.right { text-align: right; }
        .total-table tr.grand-total td { font-weight: bold; color: #111827; font-size: 18px; border-top: 2px solid #e5e7eb; padding-top: 12px; }
        .footer { text-align: center; margin-top: 50px; color: #9ca3af; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
        @media print { body { padding: 0; } .invoice-box { border: none; box-shadow: none; max-width: 100%; } .no-print { display: none; } }
        .btn-print { background-color: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ Cetak Invoice</button>

    <div class="invoice-box">
        <div class="header">
            <div class="company-info" style="display: flex; align-items: center; gap: 20px;">
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
                <tr>
                    <td>Tagihan Layanan Internet {{ $invoice->customer->package ? $invoice->customer->package->name : '' }}</td>
                    <td>{{ $invoice->period_label }}</td>
                    <td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
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

        <div class="footer">
            <p>Terima kasih atas kepercayaan Anda menggunakan layanan kami.</p>
            <p>Harap melakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari pemutusan layanan.</p>
        </div>
    </div>
</body>
</html>
