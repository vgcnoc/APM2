<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProfitSharingPartnership;
use App\Models\ProfitSharingScheme;
use App\Models\ProfitSharingSettlement;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfitSharingService
{
    /**
     * Hitung bagi hasil untuk satu kemitraan pada periode bulan & tahun tertentu
     */
    public static function calculateForPartnership(ProfitSharingPartnership $partnership, int $month, int $year): array
    {
        $scheme = $partnership->scheme;
        if (!$scheme) {
            return [
                'error' => 'Skema kerjasama tidak ditemukan.',
                'partner_share_amount' => 0,
                'isp_share_amount' => 0,
            ];
        }

        // Ambil query invoice berstatus lunas pada periode ini
        $invoiceQuery = Invoice::with(['customer.package'])
            ->where('status', 'paid')
            ->where('is_reseller_balance', false)
            ->where(function ($q) use ($month, $year) {
                $q->where(function ($sub) use ($month, $year) {
                    $sub->where('period_month', $month)
                        ->where('period_year', $year);
                })->orWhere(function ($sub) use ($month, $year) {
                    $sub->whereNull('period_month')
                        ->whereMonth('paid_at', $month)
                        ->whereYear('paid_at', $year);
                });
            });

        // Filter invoice berdasarkan kemitraan Area atau User Mitra
        if ($partnership->partner_type === 'area' && $partnership->area_id) {
            $area = $partnership->area;
            $invoiceQuery->whereHas('customer', function ($cq) use ($partnership, $area) {
                $cq->where(function ($sub) use ($partnership, $area) {
                    $sub->where('area_id', $partnership->area_id);
                    if ($area && $area->name) {
                        $sub->orWhere('area', $area->name);
                    }
                });
            });
        } elseif ($partnership->partner_type === 'user' && $partnership->user_id) {
            $invoiceQuery->whereHas('customer', function ($cq) use ($partnership) {
                $cq->where('sales_id', $partnership->user_id);
            });
        }

        $invoices = $invoiceQuery->get();

        $totalPaidInvoices = $invoices->count();
        $uniqueCustomerIds = $invoices->pluck('customer_id')->filter()->unique()->values();
        $totalCustomers = $uniqueCustomerIds->count();
        $totalGrossRevenue = (float) $invoices->sum('amount');

        // Hitung pajak PPN dan BHP/USO
        $globalBhp = (float) Setting::get('tax_bhp', '1.25');
        $globalUso = (float) Setting::get('tax_uso', '0.5');
        $totalPpn = 0.0;
        $totalBhpUso = 0.0;

        foreach ($invoices as $inv) {
            $cust = $inv->customer;
            $ppnPercent = $cust && $cust->tax_ppn !== null ? (float) $cust->tax_ppn : 0.0;
            $bhpPercent = $cust && $cust->tax_bhp !== null ? (float) $cust->tax_bhp : ($ppnPercent > 0 ? $globalBhp : 0.0);
            $usoPercent = $cust && $cust->tax_uso !== null ? (float) $cust->tax_uso : ($ppnPercent > 0 ? $globalUso : 0.0);

            if ($ppnPercent > 0) {
                $base = round((float) $inv->amount / (1 + ($ppnPercent / 100)));
                $ppnAmount = (float) $inv->amount - $base;
            } else {
                $base = (float) $inv->amount;
                $ppnAmount = 0.0;
            }

            $totalPpn += $ppnAmount;
            $totalBhpUso += round($base * (($bhpPercent + $usoPercent) / 100));
        }

        $taxDeductions = $scheme->deduct_tax ? ($totalPpn + $totalBhpUso) : 0.0;
        $operationalDeductions = (float) $scheme->operational_deduction;
        $netRevenue = max(0, $totalGrossRevenue - $taxDeductions - $operationalDeductions);

        $partnerShare = 0.0;
        $formulaDetail = '';

        // Terapkan model skema profit sharing
        switch ($scheme->model) {
            case 'gross_percentage':
                $percent = (float) $scheme->partner_percentage;
                $partnerShare = round($totalGrossRevenue * ($percent / 100));
                $formulaDetail = "{$percent}% dari Total Omset Kotor " . self::formatRupiah($totalGrossRevenue);
                break;

            case 'net_percentage':
                $percent = (float) $scheme->partner_percentage;
                $partnerShare = round($netRevenue * ($percent / 100));
                $formulaDetail = "{$percent}% dari Omset Bersih " . self::formatRupiah($netRevenue) . " (setelah potongan pajak & beban)";
                break;

            case 'flat_per_customer':
                $flat = (float) $scheme->flat_per_customer_amount;
                $partnerShare = $totalCustomers * $flat;
                $formulaDetail = "{$totalCustomers} Pelanggan Aktif × " . self::formatRupiah($flat) . " / user";
                break;

            case 'tiered_volume':
                $tieredRules = $scheme->tiered_rules ?: [
                    ['min' => 1, 'max' => 50, 'percent' => 20],
                    ['min' => 51, 'max' => 100, 'percent' => 25],
                    ['min' => 101, 'max' => null, 'percent' => 30],
                ];
                $matchedPercent = (float) $scheme->partner_percentage;
                foreach ($tieredRules as $rule) {
                    $min = (int) ($rule['min'] ?? 0);
                    $max = isset($rule['max']) && $rule['max'] !== '' && $rule['max'] !== null ? (int) $rule['max'] : PHP_INT_MAX;
                    if ($totalCustomers >= $min && $totalCustomers <= $max) {
                        $matchedPercent = (float) ($rule['percent'] ?? $matchedPercent);
                        break;
                    }
                }
                $baseAmount = $scheme->deduct_tax ? $netRevenue : $totalGrossRevenue;
                $partnerShare = round($baseAmount * ($matchedPercent / 100));
                $formulaDetail = "Tier {$totalCustomers} Pelanggan ({$matchedPercent}%) dari " . self::formatRupiah($baseAmount);
                break;

            case 'per_package':
                $packageRules = $scheme->package_rules ?: [];
                $partnerShare = 0.0;
                foreach ($invoices as $inv) {
                    $pkgId = $inv->customer?->package_id;
                    $rule = $packageRules[$pkgId] ?? null;
                    if ($rule) {
                        $type = $rule['type'] ?? 'percent';
                        $val = (float) ($rule['value'] ?? 0);
                        if ($type === 'percent') {
                            $partnerShare += round((float) $inv->amount * ($val / 100));
                        } else {
                            $partnerShare += $val;
                        }
                    } else {
                        // Fallback ke persentase default skema
                        $partnerShare += round((float) $inv->amount * ((float) $scheme->partner_percentage / 100));
                    }
                }
                $formulaDetail = "Kalkulasi tarif kustom per paket langganan ({$totalPaidInvoices} tagihan)";
                break;

            default:
                $percent = (float) $scheme->partner_percentage;
                $partnerShare = round($totalGrossRevenue * ($percent / 100));
                $formulaDetail = "{$percent}% dari Total Omset " . self::formatRupiah($totalGrossRevenue);
                break;
        }

        // Tambahan Bonus Registrasi / Pelanggan Baru jika aktif
        $registrationBonusTotal = 0.0;
        $newCustomersCount = 0;
        if ((float) $scheme->registration_bonus > 0) {
            $newCustQuery = Customer::query()
                ->whereMonth('activation_date', $month)
                ->whereYear('activation_date', $year)
                ->where('status', 'active');

            if ($partnership->partner_type === 'area' && $partnership->area_id) {
                $newCustQuery->where(function($q) use ($partnership, $area) {
                    $q->where('area_id', $partnership->area_id);
                    if ($area && $area->name) $q->orWhere('area', $area->name);
                });
            } elseif ($partnership->partner_type === 'user' && $partnership->user_id) {
                $newCustQuery->where('sales_id', $partnership->user_id);
            }

            $newCustomersCount = $newCustQuery->count();
            $registrationBonusTotal = $newCustomersCount * (float) $scheme->registration_bonus;
            $partnerShare += $registrationBonusTotal;

            if ($newCustomersCount > 0) {
                $formulaDetail .= " + Bonus Pasang Baru ({$newCustomersCount} × " . self::formatRupiah($scheme->registration_bonus) . ")";
            }
        }

        $ispShare = max(0, $totalGrossRevenue - $partnerShare);

        // Ambil sampel 5 invoice/pelanggan untuk pratinjau
        $sampleCustomers = $invoices->take(6)->map(function ($inv) {
            return [
                'customer_name' => $inv->customer?->name ?? 'Pelanggan',
                'customer_code' => $inv->customer?->customer_code ?? '-',
                'package_name' => $inv->customer?->package?->name ?? 'Default',
                'amount' => (float) $inv->amount,
                'paid_at' => $inv->paid_at ? Carbon::parse($inv->paid_at)->format('d/m/Y') : '-',
            ];
        })->toArray();

        return [
            'partnership_id' => $partnership->id,
            'partner_name' => $partnership->partner_name,
            'partner_type' => $partnership->partner_type,
            'target_display' => $partnership->target_display_name,
            'scheme_name' => $scheme->name,
            'scheme_code' => $scheme->code,
            'scheme_model' => $scheme->model,
            'scheme_model_label' => $scheme->model_label,
            'period_month' => $month,
            'period_year' => $year,
            'period_label' => self::getPeriodLabel($month, $year),
            'total_customers' => $totalCustomers,
            'total_paid_invoices' => $totalPaidInvoices,
            'total_gross_revenue' => $totalGrossRevenue,
            'total_tax_deductions' => $taxDeductions,
            'total_operational_deductions' => $operationalDeductions,
            'net_revenue' => $netRevenue,
            'partner_share_amount' => $partnerShare,
            'isp_share_amount' => $ispShare,
            'partner_percentage_effective' => $totalGrossRevenue > 0 ? round(($partnerShare / $totalGrossRevenue) * 100, 1) : 0,
            'registration_bonus_total' => $registrationBonusTotal,
            'new_customers_count' => $newCustomersCount,
            'formula_detail' => $formulaDetail,
            'sample_customers' => $sampleCustomers,
            'bank_info' => [
                'bank_name' => $partnership->bank_name,
                'bank_account_number' => $partnership->bank_account_number,
                'bank_account_name' => $partnership->bank_account_name,
            ],
        ];
    }

    /**
     * Hitung simulator instan (Quick Simulator) tanpa perlu simpan ke database
     */
    public static function quickCalculate(array $params): array
    {
        $model = $params['model'] ?? 'gross_percentage';
        $omset = (float) str_replace(['.', ',', ' '], '', (string) ($params['omset'] ?? 30000000));
        $percentage = (float) ($params['percentage'] ?? 30);
        $customerCount = (int) ($params['customer_count'] ?? 150);
        $flatAmount = (float) str_replace(['.', ',', ' '], '', (string) ($params['flat_amount'] ?? 15000));
        $deductTax = !empty($params['deduct_tax']);
        $operationalDeduction = (float) str_replace(['.', ',', ' '], '', (string) ($params['operational_deduction'] ?? 0));

        $taxAmount = 0.0;
        if ($deductTax) {
            // Estimasi PPN 11% + BHP USO 1.75% = ~12.75%
            $taxAmount = round($omset * (12.75 / 100));
        }

        $netBase = max(0, $omset - $taxAmount - $operationalDeduction);
        $partnerShare = 0.0;
        $formulaDetail = '';

        switch ($model) {
            case 'gross_percentage':
                $partnerShare = round($omset * ($percentage / 100));
                $formulaDetail = "{$percentage}% dari Omset Kotor " . self::formatRupiah($omset);
                break;
            case 'net_percentage':
                $partnerShare = round($netBase * ($percentage / 100));
                $formulaDetail = "{$percentage}% dari Omset Bersih " . self::formatRupiah($netBase);
                break;
            case 'flat_per_customer':
                $partnerShare = $customerCount * $flatAmount;
                $formulaDetail = "{$customerCount} Pelanggan × " . self::formatRupiah($flatAmount);
                break;
            case 'tiered_volume':
                $tieredRules = $params['tiered_rules'] ?? [
                    ['min' => 1, 'max' => 50, 'percent' => 20],
                    ['min' => 51, 'max' => 100, 'percent' => 25],
                    ['min' => 101, 'max' => null, 'percent' => 30],
                ];
                $matchedPercent = $percentage;
                foreach ($tieredRules as $rule) {
                    $min = (int) ($rule['min'] ?? 0);
                    $max = isset($rule['max']) && $rule['max'] !== '' && $rule['max'] !== null ? (int) $rule['max'] : PHP_INT_MAX;
                    if ($customerCount >= $min && $customerCount <= $max) {
                        $matchedPercent = (float) ($rule['percent'] ?? $matchedPercent);
                        break;
                    }
                }
                $partnerShare = round($omset * ($matchedPercent / 100));
                $formulaDetail = "Tier {$customerCount} Pelanggan ({$matchedPercent}%) dari " . self::formatRupiah($omset);
                break;
            default:
                $partnerShare = round($omset * ($percentage / 100));
                $formulaDetail = "{$percentage}% dari Omset " . self::formatRupiah($omset);
                break;
        }

        $ispShare = max(0, $omset - $partnerShare);

        return [
            'omset' => $omset,
            'customer_count' => $customerCount,
            'model' => $model,
            'tax_amount' => $taxAmount,
            'operational_deduction' => $operationalDeduction,
            'net_base' => $netBase,
            'partner_share' => $partnerShare,
            'isp_share' => $ispShare,
            'effective_percentage' => $omset > 0 ? round(($partnerShare / $omset) * 100, 1) : 0,
            'formula_detail' => $formulaDetail,
        ];
    }

    /**
     * Simpan atau perbarui Settlement (Rekonsiliasi Pembayaran)
     */
    public static function saveSettlement(ProfitSharingPartnership $partnership, int $month, int $year, ?string $notes = null): ProfitSharingSettlement
    {
        $calc = self::calculateForPartnership($partnership, $month, $year);

        $settlement = ProfitSharingSettlement::firstOrNew([
            'partnership_id' => $partnership->id,
            'period_month' => $month,
            'period_year' => $year,
        ]);

        if (!$settlement->exists) {
            $settlement->settlement_number = 'PS-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($partnership->id, 4, '0', STR_PAD_LEFT);
            $settlement->status = 'draft';
        }

        $settlement->total_customers = $calc['total_customers'];
        $settlement->total_paid_invoices = $calc['total_paid_invoices'];
        $settlement->total_gross_revenue = $calc['total_gross_revenue'];
        $settlement->total_tax_deductions = $calc['total_tax_deductions'];
        $settlement->total_operational_deductions = $calc['total_operational_deductions'];
        $settlement->net_revenue = $calc['net_revenue'];
        $settlement->partner_share_amount = $calc['partner_share_amount'];
        $settlement->isp_share_amount = $calc['isp_share_amount'];
        $settlement->scheme_snapshot = [
            'scheme_name' => $partnership->scheme?->name,
            'model' => $partnership->scheme?->model,
            'partner_percentage' => $partnership->scheme?->partner_percentage,
            'flat_amount' => $partnership->scheme?->flat_per_customer_amount,
            'formula_detail' => $calc['formula_detail'],
        ];

        if ($notes !== null) {
            $settlement->notes = $notes;
        }

        $settlement->save();

        return $settlement;
    }

    /**
     * Buat data awal default skema (Seeder otomatis) jika tabel masih kosong
     */
    public static function ensureDefaultSchemes(): void
    {
        if (ProfitSharingScheme::count() > 0) {
            return;
        }

        $defaults = [
            [
                'name' => 'Investor Jaringan Area (30% Omset Kotor)',
                'code' => 'SCH-GROSS-30',
                'model' => 'gross_percentage',
                'partner_percentage' => 30.00,
                'isp_percentage' => 70.00,
                'description' => 'Bagi hasil standar mitra investor kabel/ODP di perumahan atau desa. Mitra menerima 30% langsung dari total tagihan pelanggan yang lunas.',
            ],
            [
                'name' => 'Mitra Pengurus RT/RW (Rp 10.000 / Pelanggan)',
                'code' => 'SCH-FLAT-10K',
                'model' => 'flat_per_customer',
                'partner_percentage' => 0.00,
                'isp_percentage' => 100.00,
                'flat_per_customer_amount' => 10000.00,
                'description' => 'Nominal tetap Rp 10.000 untuk setiap pelanggan aktif yang membayar tagihan tiap bulan. Praktis dan transparan untuk tokoh masyarakat.',
            ],
            [
                'name' => 'Kemitraan Wilayah Netto (40% Setelah Pajak & Bandwidth)',
                'code' => 'SCH-NET-40',
                'model' => 'net_percentage',
                'partner_percentage' => 40.00,
                'isp_percentage' => 60.00,
                'deduct_tax' => true,
                'operational_deduction' => 500000.00,
                'description' => 'Bagi hasil 40% setelah dipotong pajak resmi (PPN, BHP/USO) dan kontribusi pemeliharaan jaringan server Rp 500.000.',
            ],
            [
                'name' => 'Skema Progresif Bertingkat (20% - 35% Volume)',
                'code' => 'SCH-TIER-PROG',
                'model' => 'tiered_volume',
                'partner_percentage' => 25.00,
                'isp_percentage' => 75.00,
                'tiered_rules' => [
                    ['min' => 1, 'max' => 50, 'percent' => 20],
                    ['min' => 51, 'max' => 100, 'percent' => 25],
                    ['min' => 101, 'max' => 200, 'percent' => 30],
                    ['min' => 201, 'max' => null, 'percent' => 35],
                ],
                'description' => 'Makin banyak pelanggan di wilayah mitra, makin tinggi persentase bagi hasil mitra. Memacu mitra mencari lebih banyak warga.',
            ],
            [
                'name' => 'Mitra Sales Perekrut (Bonus Pasang Baru + 15% Bulanan)',
                'code' => 'SCH-HYBRID-SALES',
                'model' => 'gross_percentage',
                'partner_percentage' => 15.00,
                'isp_percentage' => 85.00,
                'registration_bonus' => 25000.00,
                'description' => 'Mitra menerima bonus Rp 25.000 saat pelanggan baru pasang dan aktivasi + bagi hasil bulanan rutin 15% dari tagihan.',
            ],
        ];

        foreach ($defaults as $d) {
            ProfitSharingScheme::create($d);
        }
    }

    public static function formatRupiah($val): string
    {
        return 'Rp ' . number_format((float) $val, 0, ',', '.');
    }

    public static function getPeriodLabel(int $month, int $year): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $m = $months[$month] ?? "Bulan {$month}";
        return "{$m} {$year}";
    }
}

