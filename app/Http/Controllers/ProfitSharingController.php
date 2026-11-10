<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\InternetPackage;
use App\Models\ProfitSharingPartnership;
use App\Models\ProfitSharingScheme;
use App\Models\ProfitSharingSettlement;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProfitSharingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfitSharingController extends Controller
{
    /**
     * Tampilan Utama Manajemen Profit Sharing & Kemitraan
     */
    public function index(Request $request): Response
    {
        // Pastikan skema default tersedia
        try {
            ProfitSharingService::ensureDefaultSchemes();
        } catch (\Throwable $e) {
            // Ignored if table doesn't exist yet before migration
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        if ($month < 1 || $month > 12) $month = now()->month;
        if ($year < 2020 || $year > 2050) $year = now()->year;

        $schemes = ProfitSharingScheme::withCount('partnerships')
            ->orderBy('is_active', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        $partnerships = ProfitSharingPartnership::with(['scheme', 'area', 'user'])
            ->orderBy('is_active', 'desc')
            ->orderBy('partner_name', 'asc')
            ->get();

        // Hitung rekonsiliasi live untuk setiap kemitraan aktif di periode ini
        $liveCalculations = $partnerships->where('is_active', true)->map(function ($p) use ($month, $year) {
            $calc = ProfitSharingService::calculateForPartnership($p, $month, $year);
            $settlement = ProfitSharingSettlement::where('partnership_id', $p->id)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            $calc['settlement_id'] = $settlement?->id;
            $calc['settlement_number'] = $settlement?->settlement_number;
            $calc['status'] = $settlement?->status ?? 'unsettled';
            $calc['paid_at'] = $settlement?->paid_at ? $settlement->paid_at->format('d/m/Y H:i') : null;
            $calc['notes'] = $settlement?->notes;

            return $calc;
        })->values();

        // Riwayat Settlements
        $settlements = ProfitSharingSettlement::with(['partnership.scheme', 'partnership.area', 'partnership.user', 'paidByUser'])
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        $areas = Area::select('id', 'name')->orderBy('name')->get();
        $users = User::select('id', 'name', 'email', 'role')->orderBy('name')->get();
        $packages = InternetPackage::select('id', 'name', 'current_price')->orderBy('name')->get();

        // Agregasi Ringkasan Bulan Ini
        $totalGrossRevenue = (float) $liveCalculations->sum('total_gross_revenue');
        $totalPartnerShare = (float) $liveCalculations->sum('partner_share_amount');
        $totalIspShare = (float) $liveCalculations->sum('isp_share_amount');
        $totalCustomers = (int) $liveCalculations->sum('total_customers');

        return Inertia::render('ProfitSharing/Index', [
            'period' => [
                'month' => $month,
                'year' => $year,
                'label' => ProfitSharingService::getPeriodLabel($month, $year),
            ],
            'summary' => [
                'total_partnerships' => $partnerships->count(),
                'active_partnerships' => $partnerships->where('is_active', true)->count(),
                'total_customers' => $totalCustomers,
                'total_gross_revenue' => $totalGrossRevenue,
                'total_partner_share' => $totalPartnerShare,
                'total_isp_share' => $totalIspShare,
            ],
            'live_calculations' => $liveCalculations,
            'schemes' => $schemes,
            'partnerships' => $partnerships,
            'settlements' => $settlements,
            'model_labels' => ProfitSharingScheme::modelLabels(),
            'areas' => $areas,
            'users' => $users,
            'packages' => $packages,
        ]);
    }

    /**
     * Simpan / Perbarui Skema Kerjasama
     */
    public function storeScheme(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:191',
            'code' => 'required|string|max:50',
            'model' => 'required|in:gross_percentage,net_percentage,flat_per_customer,tiered_volume,per_package',
            'partner_percentage' => 'nullable|numeric|min:0|max:100',
            'isp_percentage' => 'nullable|numeric|min:0|max:100',
            'flat_per_customer_amount' => 'nullable|numeric|min:0',
            'registration_bonus' => 'nullable|numeric|min:0',
            'operational_deduction' => 'nullable|numeric|min:0',
            'deduct_tax' => 'nullable|boolean',
            'tiered_rules' => 'nullable|array',
            'package_rules' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $partnerPct = (float) ($validated['partner_percentage'] ?? 30);
        $validated['partner_percentage'] = $partnerPct;
        $validated['isp_percentage'] = max(0, 100 - $partnerPct);
        $validated['is_active'] = $validated['is_active'] ?? true;

        if (!empty($validated['id'])) {
            $scheme = ProfitSharingScheme::findOrFail($validated['id']);
            $scheme->update($validated);
            $msg = "Skema kerjasama '{$scheme->name}' berhasil diperbarui!";
        } else {
            // Cek kode unik
            $baseCode = strtoupper(trim($validated['code']));
            $code = $baseCode;
            $counter = 1;
            while (ProfitSharingScheme::where('code', $code)->exists()) {
                $code = "{$baseCode}-{$counter}";
                $counter++;
            }
            $validated['code'] = $code;
            $scheme = ProfitSharingScheme::create($validated);
            $msg = "Skema kerjasama baru '{$scheme->name}' berhasil ditambahkan!";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Hapus Skema
     */
    public function deleteScheme(ProfitSharingScheme $scheme)
    {
        if ($scheme->partnerships()->exists()) {
            return redirect()->back()->with('error', "Skema '{$scheme->name}' tidak dapat dihapus karena masih digunakan oleh kemitraan aktif.");
        }

        $name = $scheme->name;
        $scheme->delete();

        return redirect()->back()->with('success', "Skema '{$name}' berhasil dihapus.");
    }

    /**
     * Simpan / Perbarui Kemitraan (Hubungkan Area/Mitra ke Skema)
     */
    public function storePartnership(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'scheme_id' => 'required|exists:profit_sharing_schemes,id',
            'partner_type' => 'required|in:area,user',
            'area_id' => 'nullable|required_if:partner_type,area|exists:areas,id',
            'user_id' => 'nullable|required_if:partner_type,user|exists:users,id',
            'partner_name' => 'required|string|max:191',
            'partner_phone' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:191',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        if (!empty($validated['id'])) {
            $partnership = ProfitSharingPartnership::findOrFail($validated['id']);
            $partnership->update($validated);
            $msg = "Data kemitraan '{$partnership->partner_name}' berhasil diperbarui!";
        } else {
            $partnership = ProfitSharingPartnership::create($validated);
            $msg = "Kemitraan baru '{$partnership->partner_name}' berhasil dihubungkan ke skema!";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Hapus Kemitraan
     */
    public function deletePartnership(ProfitSharingPartnership $partnership)
    {
        $name = $partnership->partner_name;
        $partnership->delete();

        return redirect()->back()->with('success', "Kemitraan '{$name}' berhasil dihapus.");
    }

    /**
     * Kunci & Simpan Settlement (Rekonsiliasi Payout)
     */
    public function saveSettlement(Request $request)
    {
        $validated = $request->validate([
            'partnership_id' => 'required|exists:profit_sharing_partnerships,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2050',
            'notes' => 'nullable|string',
        ]);

        $partnership = ProfitSharingPartnership::findOrFail($validated['partnership_id']);
        $settlement = ProfitSharingService::saveSettlement($partnership, (int) $validated['month'], (int) $validated['year'], $validated['notes'] ?? null);

        return redirect()->back()->with('success', "Rekap bagi hasil {$partnership->partner_name} periode {$settlement->period_label} berhasil dikunci ({$settlement->settlement_number})!");
    }

    /**
     * Perbarui Status Pembayaran Bagi Hasil (Draft -> Approved -> Paid)
     */
    public function updateSettlementStatus(Request $request, ProfitSharingSettlement $settlement)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,approved,paid',
            'payment_reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        $settlement->status = $validated['status'];
        if ($validated['status'] === 'paid') {
            $settlement->paid_at = now();
            $settlement->paid_by = auth()->id();
            if (!empty($validated['payment_reference'])) {
                $settlement->payment_reference = $validated['payment_reference'];
            }
        } elseif ($validated['status'] === 'draft') {
            $settlement->paid_at = null;
            $settlement->paid_by = null;
        }

        if (isset($validated['notes'])) {
            $settlement->notes = $validated['notes'];
        }

        $settlement->save();

        return redirect()->back()->with('success', "Status rekap bagi hasil {$settlement->settlement_number} berhasil diubah menjadi " . strtoupper($settlement->status));
    }

    /**
     * Cetak PDF / Slip Rekapitulasi Bagi Hasil
     */
    public function printSettlement(ProfitSharingSettlement $settlement): Response
    {
        $settlement->load(['partnership.scheme', 'partnership.area', 'partnership.user', 'paidByUser']);

        // Recalculate detail breakdown
        $calc = ProfitSharingService::calculateForPartnership(
            $settlement->partnership,
            $settlement->period_month,
            $settlement->period_year
        );

        return Inertia::render('ProfitSharing/Print', [
            'settlement' => $settlement,
            'calculation' => $calc,
            'company' => [
                'name' => Setting::get('company_name', Setting::get('app_name', 'ISP Provider')),
                'address' => Setting::get('company_address', 'Jl. Jaringan Telekomunikasi Indonesia'),
                'phone' => Setting::get('company_phone', '0812-XXXX-XXXX'),
                'email' => Setting::get('company_email', 'billing@ispprovider.net'),
                'logo' => Setting::get('company_logo') ? asset('storage/' . Setting::get('company_logo')) : null,
            ]
        ]);
    }

    /**
     * API Simulator Cepat (Quick Calculate)
     */
    public function quickSimulate(Request $request)
    {
        $result = ProfitSharingService::quickCalculate($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}

