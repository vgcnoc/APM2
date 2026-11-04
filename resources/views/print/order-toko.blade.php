<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Serah Terima & Order Toko - {{ $transaction->transaction_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1e293b;
            background: #f1f5f9;
            padding: 24px;
        }
        .action-bar {
            max-width: 840px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover {
            color: #0f172a;
        }
        .sheet {
            max-width: 840px;
            margin: auto;
            padding: 40px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 24px;
        }
        .header-brand h1 {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .header-brand p {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .header-doc {
            text-align: right;
        }
        .header-doc .title-doc {
            font-size: 15px;
            font-weight: 900;
            color: #2563eb;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header-doc .no-ref {
            font-size: 13px;
            font-weight: 700;
            font-family: monospace;
            color: #0f172a;
            margin-top: 2px;
        }
        .header-doc .date-ref {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .party-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }
        .party-card.receiver {
            border-left: 4px solid #2563eb;
        }
        .party-card.giver {
            border-left: 4px solid #059669;
        }
        .party-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .party-card.receiver .party-label {
            color: #2563eb;
        }
        .party-card.giver .party-label {
            color: #059669;
        }
        .party-name {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }
        .party-meta {
            font-size: 12px;
            color: #475569;
            margin-top: 4px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table.items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        table.items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12.5px;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .summary-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 32px;
        }
        .summary-notes {
            max-width: 50%;
        }
        .summary-notes h4 {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .summary-notes p {
            font-size: 12px;
            color: #334155;
            font-style: italic;
        }
        .summary-cost {
            text-align: right;
        }
        .summary-cost .total-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }
        .summary-cost .total-value {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 2px;
        }
        .summary-cost .kas-source {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            background: #d1fae5;
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
        }
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .sig-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 140px;
        }
        .sig-title {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .sig-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            padding-bottom: 2px;
            min-width: 140px;
            text-transform: uppercase;
        }
        .sig-role {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }
        .footer-note {
            margin-top: 36px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
            text-align: center;
            font-size: 10.5px;
            color: #94a3b8;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 10px 15px;
                max-width: 100%;
                border-radius: 0;
            }
            @page {
                size: A4 portrait;
                margin: 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar (Hidden in Print) -->
    <div class="action-bar">
        <a href="javascript:window.close()" class="btn-back">
            &larr; Tutup / Kembali
        </a>
        <button onclick="window.print()" class="btn-print">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Cetak Surat Serah Terima
        </button>
    </div>

    <!-- Printable Sheet -->
    <div class="sheet">
        <!-- Header -->
        <div class="header">
            <div class="header-brand">
                <h1>{{ $company['name'] ?? 'VIZBILL ISP' }}</h1>
                <p>{{ $company['address'] ?? 'Sistem Manajemen Gudang & Logistik' }}</p>
                <p>Telp: {{ $company['phone'] ?? '-' }} | Email: {{ $company['email'] ?? '-' }}</p>
            </div>
            <div class="header-doc">
                <div class="title-doc">SURAT SERAH TERIMA RESTOCK</div>
                <div class="no-ref">{{ $transaction->transaction_number }}</div>
                <div class="date-ref">Tanggal: {{ \Carbon\Carbon::parse($transaction->date)->translatedFormat('d F Y') }}</div>
            </div>
        </div>

        <!-- Pihak Terkait Serah Terima -->
        <div class="parties-grid">
            <!-- Pihak 1: Yang Order / Penerima di Wilayah -->
            <div class="party-card receiver">
                <div class="party-label">Pihak Yang Meminta / Order (Penerima)</div>
                <div class="party-name">{{ $transaction->orderUser?->name ?? $transaction->order_account_name ?? $transaction->user?->name ?? '-' }}</div>
                <div class="party-meta">
                    <strong>Wilayah Tujuan:</strong> {{ $transaction->area ?? $transaction->technician_name ?? '-' }}
                </div>
                <div class="party-meta">
                    <strong>Role / Akses:</strong> {{ $transaction->orderUser?->role ? ucwords(str_replace('_', ' ', $transaction->orderUser->role)) : 'Petugas Toko / Lapangan' }}
                </div>
            </div>

            <!-- Pihak 2: Yang Memberi / Gudang Pusat -->
            <div class="party-card giver">
                <div class="party-label">Pihak Yang Menyerahkan / Memberi (Gudang Pusat)</div>
                <div class="party-name">{{ $transaction->giverUser?->name ?? $transaction->giver_account_name ?? 'Super Admin (Gudang Pusat)' }}</div>
                <div class="party-meta">
                    <strong>Asal Stok:</strong> Gudang Utama / Pusat
                </div>
                <div class="party-meta">
                    <strong>Status Approval:</strong> Disetujui & Dikeluarkan
                </div>
            </div>
        </div>

        <!-- Rincian Material / Barang -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">No</th>
                    <th>Nama Barang / Material</th>
                    <th class="text-center" style="width: 120px;">Kategori</th>
                    <th class="text-right" style="width: 130px;">Jumlah Terkonversi</th>
                    <th class="text-right" style="width: 120px;">Biaya Satuan</th>
                    <th class="text-right" style="width: 130px;">Total Biaya</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaction->items as $idx => $item)
                    <tr>
                        <td class="text-center" style="color: #64748b; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0f172a; font-size: 13px;">{{ $item->material?->name ?? 'Material #' . $item->material_id }}</strong>
                            @if($item->material?->brand)
                                <div style="font-size: 11px; color: #64748b;">Merk: {{ $item->material->brand }}</div>
                            @endif
                        </td>
                        <td class="text-center" style="color: #475569; font-size: 11.5px;">
                            {{ $item->material?->category ?? '-' }}
                        </td>
                        <td class="text-right" style="font-weight: 700; color: #047857;">
                            +{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit ?? $item->material?->unit ?? 'pcs' }}
                        </td>
                        <td class="text-right" style="color: #475569; font-family: monospace;">
                            <div>Rp {{ number_format($item->price_per_unit, 0, ',', '.') }} / {{ $item->unit ?? $item->material?->unit ?? 'pcs' }}</div>
                            @php
                                $mat = $item->material;
                                $isIso = $mat && (str_contains(strtolower($mat->name ?? ''), 'isolasi') || str_contains(strtolower($mat->category ?? ''), 'isolasi'));
                            @endphp
                            @if($mat && (float)$mat->meter_per_roll > 0)
                                <div style="font-size: 10.5px; color: #64748b; font-family: sans-serif; margin-top: 2px;">
                                    (Rp {{ number_format($item->price_per_unit * $mat->meter_per_roll, 0, ',', '.') }} / roll)
                                </div>
                            @elseif($isIso && (float)($mat->cm_per_pcs ?? 0) > 0)
                                <div style="font-size: 10.5px; color: #64748b; font-family: sans-serif; margin-top: 2px;">
                                    (Rp {{ number_format($item->price_per_unit * $mat->cm_per_pcs * ($mat->pcs_per_pack ?: 1), 0, ',', '.') }} / bungkus)
                                </div>
                            @elseif($mat && (float)$mat->pcs_per_pack > 0)
                                <div style="font-size: 10.5px; color: #64748b; font-family: sans-serif; margin-top: 2px;">
                                    (Rp {{ number_format($item->price_per_unit * $mat->pcs_per_pack, 0, ',', '.') }} / bungkus)
                                </div>
                            @endif
                        </td>
                        <td class="text-right" style="font-weight: 700; color: #0f172a; font-family: monospace;">
                            Rp {{ number_format($item->total_price, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="color: #94a3b8; padding: 24px;">Tidak ada rincian material.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Ringkasan & Sumber Kas -->
        <div class="summary-box">
            <div class="summary-notes">
                <h4>Catatan Transaksi:</h4>
                <p>{{ $transaction->notes ?: 'Tidak ada catatan tambahan untuk order ini.' }}</p>
            </div>
            <div class="summary-cost">
                <div class="total-label">Total Biaya Pengadaan:</div>
                <div class="total-value">Rp {{ number_format($transaction->total_cost, 0, ',', '.') }}</div>
                <div class="kas-source">✓ Sumber: Buku Kas Umum</div>
            </div>
        </div>

        <!-- Kolom Tanda Tangan Serah Terima -->
        <div class="signatures-grid">
            <div class="sig-block">
                <div class="sig-title">Yang Memesan / Menerima,</div>
                <div class="sig-name">{{ $transaction->orderUser?->name ?? $transaction->order_account_name ?? $transaction->user?->name ?? 'Pemesan' }}</div>
                <div class="sig-role">Petugas Toko / Area {{ $transaction->area ?? '' }}</div>
            </div>

            <div class="sig-block">
                <div class="sig-title">Yang Menyerahkan / Memberi,</div>
                <div class="sig-name">{{ $transaction->giverUser?->name ?? $transaction->giver_account_name ?? 'Super Admin' }}</div>
                <div class="sig-role">Petugas Gudang Pusat</div>
            </div>

            <div class="sig-block">
                <div class="sig-title">Mengetahui / Pimpinan,</div>
                <div class="sig-name">( ............................................ )</div>
                <div class="sig-role">Kepala Logistik / Pimpinan ISP</div>
            </div>
        </div>

        <div class="footer-note">
            Dokumen sah serah terima barang ini diterbitkan secara otomatis oleh Sistem VIZBILL ISP pada {{ now()->translatedFormat('d F Y, H:i:s') }} WIB.
        </div>
    </div>

    <script>
        // Auto trigger print jika dibuka langsung dari aksi cetak
        window.addEventListener('load', function() {
            if (window.location.search.includes('auto=true')) {
                setTimeout(function() {
                    window.print();
                }, 300);
            }
        });
    </script>
</body>
</html>
