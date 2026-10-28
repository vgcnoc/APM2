<?php

namespace App\Http\Controllers;

use App\Models\InternetPackage;
use App\Models\Customer;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InternetPackageController extends Controller
{
    public function index(Request $request)
    {
        // 1. Hitung Ringkasan Statistik Kartu
        $totalPackages = InternetPackage::count();
        $activePackages = InternetPackage::where('is_active', true)->count();
        $inactivePackages = InternetPackage::where('is_active', false)->count();
        $promoPackages = InternetPackage::where('is_promo', true)->count();
        $pppoePackages = InternetPackage::where('access_mode', 'pppoe')->count();
        $hotspotPackages = InternetPackage::whereIn('access_mode', ['hotspot', 'voucher'])->count();
        $totalCustomers = Customer::whereNotNull('package_id')->count();

        $stats = [
            'total' => $totalPackages,
            'active' => $activePackages,
            'inactive' => $inactivePackages,
            'promo' => $promoPackages,
            'pppoe' => $pppoePackages,
            'hotspot' => $hotspotPackages,
            'total_customers' => $totalCustomers,
        ];

        // 2. Query Paket dengan Filter Cerdas
        $query = InternetPackage::query()->withCount('customers');

        // Pencarian Teks
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('rate_limit', 'like', "%{$search}%")
                  ->orWhere('speed_mbps', 'like', "%{$search}%");
            });
        }

        // Filter Tipe Akses
        if ($request->filled('access_mode') && $request->access_mode !== 'all') {
            $query->where('access_mode', $request->access_mode);
        }

        // Filter Status Aktif / Nonaktif
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter Promo
        if ($request->filled('is_promo') && $request->is_promo !== 'all') {
            if ($request->is_promo === 'promo') {
                $query->where('is_promo', true);
            } elseif ($request->is_promo === 'non_promo') {
                $query->where('is_promo', false);
            }
        }

        // Urutan / Sorting
        $sort = $request->query('sort', 'price_asc');
        switch ($sort) {
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'speed_desc':
                $query->orderBy('speed_mbps', 'desc');
                break;
            case 'customers_desc':
                $query->orderBy('customers_count', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'price_asc':
            default:
                $query->orderBy('price', 'asc');
                break;
        }

        $packages = $query->paginate(12)->withQueryString();
        $areas = Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('InternetPackages/Index', [
            'packages' => $packages,
            'stats' => $stats,
            'areas' => $areas,
            'filters' => $request->only(['search', 'access_mode', 'status', 'is_promo', 'sort']),
        ]);
    }

    private function getValidationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'speed_mbps' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'installation_fee' => 'required|numeric|min:0',
            'area_installation_fees' => 'nullable|array',
            'area_installation_fees.*.area_id' => 'nullable|integer',
            'area_installation_fees.*.area_name' => 'nullable|string|max:100',
            'area_installation_fees.*.fee' => 'nullable|numeric|min:0',
            'area_installation_fees.*.allowed_options' => 'nullable|array',
            'access_mode' => 'required|in:pppoe,hotspot,voucher,static_ip,lainnya',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_visible_to_sales' => 'boolean',
            'color' => 'required|string|max:50',
            'is_promo' => 'boolean',
            'promo_price' => 'nullable|numeric|min:0',
            'is_mikrotik_group_custom' => 'boolean',
            'mikrotik_group' => 'nullable|string|max:255',
            'is_mikrotik_address_list_custom' => 'boolean',
            'mikrotik_address_list' => 'nullable|string|max:255',
            'shared_device' => 'required|integer|min:1',
            'rate_limit' => 'nullable|string|max:255',
            'active_period' => 'required|integer|min:1',
            'active_period_unit' => 'required|in:Hari,Minggu,Bulan,Tahun',
            'fee_reseller' => 'required|numeric|min:0',
        ];
    }

    private function cleanAreaInstallationFees(array $validated): array
    {
        if (isset($validated['area_installation_fees']) && is_array($validated['area_installation_fees'])) {
            $cleanFees = [];
            foreach ($validated['area_installation_fees'] as $item) {
                if (!is_array($item)) continue;
                $areaName = trim($item['area_name'] ?? '');
                if ($areaName === '') continue;

                $fee = isset($item['fee']) && is_numeric($item['fee']) ? (float)$item['fee'] : 0.0;
                $allowed = [];
                if (!empty($item['allowed_options']) && is_array($item['allowed_options'])) {
                    foreach ($item['allowed_options'] as $opt) {
                        if (is_numeric($opt)) {
                            $allowed[] = (float)$opt;
                        }
                    }
                }
                if (!in_array($fee, $allowed, false)) {
                    $allowed[] = $fee;
                }
                sort($allowed);
                $cleanFees[] = [
                    'area_id' => !empty($item['area_id']) ? (int)$item['area_id'] : null,
                    'area_name' => $areaName,
                    'fee' => $fee,
                    'allowed_options' => array_values(array_unique($allowed)),
                ];
            }
            $validated['area_installation_fees'] = $cleanFees;
        }

        return $validated;
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());
        
        if ($validated['fee_reseller'] > $validated['price']) {
            return redirect()->back()->with('error', 'Fee Reseller tidak boleh melebihi harga jual.');
        }

        $validated = $this->cleanAreaInstallationFees($validated);

        InternetPackage::create($validated);

        return redirect()->back()->with('success', 'Paket internet berhasil ditambahkan.');
    }

    public function update(Request $request, InternetPackage $internetPackage)
    {
        $validated = $request->validate($this->getValidationRules());
        
        if ($validated['fee_reseller'] > $validated['price']) {
            return redirect()->back()->with('error', 'Fee Reseller tidak boleh melebihi harga jual.');
        }

        $validated = $this->cleanAreaInstallationFees($validated);

        $internetPackage->update($validated);

        return redirect()->back()->with('success', 'Paket internet berhasil diperbarui.');
    }

    public function destroy(InternetPackage $internetPackage)
    {
        if ($internetPackage->customers()->count() > 0) {
            return redirect()->back()->with('error', 'Paket internet tidak dapat dihapus karena masih digunakan oleh pelanggan.');
        }

        $internetPackage->delete();

        return redirect()->back()->with('success', 'Paket internet berhasil dihapus.');
    }
}
