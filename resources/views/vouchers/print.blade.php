<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers</title>
    <style>
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        /* A4 Page Configuration */
        @page {
            size: A4;
            margin: 10mm;
        }

        /* Container for print */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 10mm;
            box-sizing: border-box;
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 10px;
        }

        /* Voucher Card Styles */
        .voucher-card {
            width: calc(33.333% - 10px); /* 3 columns */
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 15px;
            box-sizing: border-box;
            position: relative;
            background-color: #fff;
            page-break-inside: avoid;
            margin-bottom: 10px;
        }

        .voucher-header {
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .voucher-title {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .voucher-price {
            font-size: 14px;
            color: #ef4444;
            font-weight: bold;
            margin: 5px 0 0 0;
        }

        .voucher-body {
            font-size: 13px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .info-label {
            color: #6b7280;
        }

        .info-value {
            font-weight: bold;
            color: #111827;
        }

        .voucher-code {
            text-align: center;
            background: #f3f4f6;
            padding: 8px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
            font-family: monospace;
            letter-spacing: 1px;
            font-weight: bold;
        }

        /* Utility for screen viewing */
        .print-btn-container {
            text-align: center;
            padding: 20px;
            background: #fff;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .print-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .print-btn:hover {
            background-color: #2563eb;
        }

        /* Print Specific Styles */
        @media print {
            body {
                background: white;
            }
            .print-btn-container {
                display: none;
            }
            .page {
                margin: 0;
                padding: 0;
                width: 100%;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

    <div class="print-btn-container">
        <button class="print-btn" onclick="window.print()">Print Vouchers</button>
        <p style="color: #6b7280; font-size: 14px; margin-top: 10px;">Gunakan kertas ukuran A4. Atur margin ke "None" atau "Default" pada dialog print.</p>
    </div>

    <div class="page">
        @foreach($vouchers as $voucher)
            <div class="voucher-card">
                <div class="voucher-header">
                    <h3 class="voucher-title">{{ $voucher->profile->name }}</h3>
                    <p class="voucher-price">Rp {{ number_format($voucher->profile->price, 0, ',', '.') }}</p>
                </div>
                
                <div class="voucher-body">
                    <div class="info-row">
                        <span class="info-label">Username:</span>
                        <span class="info-value">{{ $voucher->username }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Password:</span>
                        <span class="info-value">{{ $voucher->password }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Durasi:</span>
                        <span class="info-value">{{ $voucher->profile->duration }}</span>
                    </div>
                    
                    <div class="voucher-code">
                        {{ $voucher->code }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            // setTimeout(() => { window.print(); }, 500);
        };
    </script>
</body>
</html>
