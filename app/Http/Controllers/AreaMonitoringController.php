<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AreaMonitoringController extends Controller
{
    /**
     * Tampilkan halaman Matrix Monitoring Belum Bayar Per Area & Konsolidasi All Area
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today()->toDateString();
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);
        $selectedAreaId = $request->query('area_id', 'all');

        // Scoping hak akses area jika user dibatasi
        $user = auth()->user();
        $isAreaRestricted = $user && !$user->hasRole(['admin', 'super admin']) && !$user->can('monitoring_area_view_all') && !$user->can('monitoring_pelanggan_view_all');
        $userAreaIds = $isAreaRestricted ? $user->getAccessibleAreaIds() : [];

        $areasQuery = Area::orderBy('name');
        if ($isAreaRestricted && !empty($userAreaIds)) {
            $areasQuery->whereIn('id', $userAreaIds);
        }
        $areas = $areasQuery->get();

        // 1. Ambil Invoices pada periode bulan & tahun yang dipilih
        $invoiceQuery = Invoice::query()
            ->with(['customer:id,customer_code,name,phone,address,area_id,area,status,service_status,package_id', 'customer.package:id,name,price'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('is_reseller_balance', false)
            ->where('invoice_number', 'not like', 'INV-MIG-%');

        if ($isAreaRestricted && !empty($userAreaIds)) {
            $invoiceQuery->whereHas('customer', function ($cq) use ($userAreaIds) {
                $cq->whereIn('area_id', $userAreaIds);
            });
        }
        $invoices = $invoiceQuery->get();

        // 2. Ambil Pelanggan untuk Lifecycle Status
        $customerQuery = Customer::query()
            ->with(['package:id,name,price', 'areaModel:id,name'])
            ->select('id', 'customer_code', 'name', 'phone', 'address', 'area_id', 'area', 'package_id', 'status', 'service_status', 'billing_type');

        if ($isAreaRestricted && !empty($userAreaIds)) {
            $customerQuery->whereIn('area_id', $userAreaIds);
        }
        $customers = $customerQuery->get();

        // Mapping Area Lookup (by ID and normalized name)
        $areaMap = [];
        foreach ($areas as $a) {
            $areaMap[$a->id] = $a;
        }

        // Helper untuk mencocokkan customer ke Area ID
        $findAreaIdForCustomer = function ($c) use ($areaMap, $areas) {
            if (!empty($c->area_id) && isset($areaMap[$c->area_id])) {
                return (int) $c->area_id;
            }
            if (!empty($c->area)) {
                $cAreaClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $c->area)));
                foreach ($areas as $a) {
                    $aClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $a->name)));
                    if ($cAreaClean === $aClean) {
                        return (int) $a->id;
                    }
                }
            }
            return 0; // Area lainnya / belum ditentukan
        };

        // Inisialisasi container data per-area
        $areaStats = [];
        foreach ($areas as $a) {
            $areaStats[$a->id] = [
                'area_id' => $a->id,
                'area_name' => $a->name,
                'description' => $a->description,

                // Billing / Tagihan
                'billing' => [
                    'lunas' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                    'belum_bayar' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                    'bayar_sebagian' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                    'janji_bayar' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                    'nunggak' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                    'total' => ['count' => 0, 'amount' => 0, 'percentage' => 100.0],
                ],

                // Status Pelanggan / Operational Lifecycle
                'customer' => [
                    'aktif' => ['count' => 0, 'percentage' => 0.0],
                    'stop_sementara' => ['count' => 0, 'percentage' => 0.0],
                    'stop_permanen' => ['count' => 0, 'percentage' => 0.0],
                    'psb' => ['count' => 0, 'percentage' => 0.0],
                    'gratis' => ['count' => 0, 'percentage' => 0.0],
                    'free_home' => ['count' => 0, 'percentage' => 0.0],
                    'total' => ['count' => 0, 'percentage' => 100.0],
                ],
            ];
        }

        // Tambah wadah unassigned jika ada
        $unassignedArea = [
            'area_id' => 0,
            'area_name' => 'Lainnya / Belum Ditentukan',
            'description' => 'Pelanggan tanpa area terdaftar',
            'billing' => [
                'lunas' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'belum_bayar' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'bayar_sebagian' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'janji_bayar' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'nunggak' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'total' => ['count' => 0, 'amount' => 0, 'percentage' => 100.0],
            ],
            'customer' => [
                'aktif' => ['count' => 0, 'percentage' => 0.0],
                'stop_sementara' => ['count' => 0, 'percentage' => 0.0],
                'stop_permanen' => ['count' => 0, 'percentage' => 0.0],
                'psb' => ['count' => 0, 'percentage' => 0.0],
                'gratis' => ['count' => 0, 'percentage' => 0.0],
                'free_home' => ['count' => 0, 'percentage' => 0.0],
                'total' => ['count' => 0, 'percentage' => 100.0],
            ],
        ];

        // 3. Klasifikasi Invoices
        foreach ($invoices as $inv) {
            $cust = $inv->customer;
            $aId = $cust ? $findAreaIdForCustomer($cust) : 0;
            $ref = &$areaStats[$aId];
            if (!$ref) {
                $ref = &$unassignedArea;
            }

            $amount = (float) $inv->amount;
            $status = strtolower($inv->status ?? 'unpaid');
            $hasPromise = !empty($inv->promise_date);
            $dueDate = $inv->due_date ? Carbon::parse($inv->due_date)->toDateString() : null;
            $isOverdue = $dueDate && $dueDate < $today;

            if ($status === 'paid') {
                $category = 'lunas';
            } elseif ($hasPromise) {
                $category = 'janji_bayar';
            } elseif ($status === 'partial') {
                $category = 'bayar_sebagian';
            } elseif ($isOverdue) {
                $category = 'nunggak';
            } else {
                $category = 'belum_bayar';
            }

            $ref['billing'][$category]['count']++;
            $ref['billing'][$category]['amount'] += $amount;
            $ref['billing']['total']['count']++;
            $ref['billing']['total']['amount'] += $amount;
            unset($ref);
        }

        // 4. Klasifikasi Customers Lifecycle
        foreach ($customers as $c) {
            $aId = $findAreaIdForCustomer($c);
            $ref = &$areaStats[$aId];
            if (!$ref) {
                $ref = &$unassignedArea;
            }

            $status = strtolower($c->status ?? 'active');
            $serviceStatus = strtolower($c->service_status ?? 'berbayar');

            if ($serviceStatus === 'free_home') {
                $category = 'free_home';
            } elseif ($serviceStatus === 'gratis') {
                $category = 'gratis';
            } elseif (in_array($status, ['booking', 'survey', 'installing', 'pending'])) {
                $category = 'psb';
            } elseif ($status === 'terminated') {
                $category = 'stop_permanen';
            } elseif ($status === 'suspended') {
                $category = 'stop_sementara';
            } else {
                $category = 'aktif';
            }

            $ref['customer'][$category]['count']++;
            $ref['customer']['total']['count']++;
            unset($ref);
        }

        // 5. Hitung Persentase Per Area & Bangun Rekapitulasi Global (All Areas)
        $allAreasSummary = [
            'area_id' => 'all',
            'area_name' => 'SEMUA AREA (KONSOLIDASI ISP)',
            'description' => 'Rekapitulasi total seluruh wilayah operasional ISP',
            'billing' => [
                'lunas' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'belum_bayar' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'bayar_sebagian' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'janji_bayar' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'nunggak' => ['count' => 0, 'amount' => 0, 'percentage' => 0.0],
                'total' => ['count' => 0, 'amount' => 0, 'percentage' => 100.0],
            ],
            'customer' => [
                'aktif' => ['count' => 0, 'percentage' => 0.0],
                'stop_sementara' => ['count' => 0, 'percentage' => 0.0],
                'stop_permanen' => ['count' => 0, 'percentage' => 0.0],
                'psb' => ['count' => 0, 'percentage' => 0.0],
                'gratis' => ['count' => 0, 'percentage' => 0.0],
                'free_home' => ['count' => 0, 'percentage' => 0.0],
                'total' => ['count' => 0, 'percentage' => 100.0],
            ],
        ];

        $calculatePercentages = function (&$row) {
            $totalBilling = $row['billing']['total']['count'];
            foreach (['lunas', 'belum_bayar', 'bayar_sebagian', 'janji_bayar', 'nunggak'] as $cat) {
                $c = $row['billing'][$cat]['count'];
                $row['billing'][$cat]['percentage'] = $totalBilling > 0 ? round(($c / $totalBilling) * 100, 2) : 0.0;
            }
            $row['billing']['total']['percentage'] = $totalBilling > 0 ? 100.0 : 0.0;

            $totalCustomer = $row['customer']['total']['count'];
            foreach (['aktif', 'stop_sementara', 'stop_permanen', 'psb', 'gratis', 'free_home'] as $cat) {
                $c = $row['customer'][$cat]['count'];
                $row['customer'][$cat]['percentage'] = $totalCustomer > 0 ? round(($c / $totalCustomer) * 100, 2) : 0.0;
            }
            $row['customer']['total']['percentage'] = $totalCustomer > 0 ? 100.0 : 0.0;
        };

        $rows = [];
        foreach ($areaStats as $id => &$item) {
            $calculatePercentages($item);

            // Tambahkan ke All Areas summary
            foreach (['lunas', 'belum_bayar', 'bayar_sebagian', 'janji_bayar', 'nunggak', 'total'] as $cat) {
                $allAreasSummary['billing'][$cat]['count'] += $item['billing'][$cat]['count'];
                $allAreasSummary['billing'][$cat]['amount'] += $item['billing'][$cat]['amount'];
            }
            foreach (['aktif', 'stop_sementara', 'stop_permanen', 'psb', 'gratis', 'free_home', 'total'] as $cat) {
                $allAreasSummary['customer'][$cat]['count'] += $item['customer'][$cat]['count'];
            }

            $rows[] = $item;
        }

        // Masukkan unassigned jika memiliki data
        if ($unassignedArea['billing']['total']['count'] > 0 || $unassignedArea['customer']['total']['count'] > 0) {
            $calculatePercentages($unassignedArea);
            foreach (['lunas', 'belum_bayar', 'bayar_sebagian', 'janji_bayar', 'nunggak', 'total'] as $cat) {
                $allAreasSummary['billing'][$cat]['count'] += $unassignedArea['billing'][$cat]['count'];
                $allAreasSummary['billing'][$cat]['amount'] += $unassignedArea['billing'][$cat]['amount'];
            }
            foreach (['aktif', 'stop_sementara', 'stop_permanen', 'psb', 'gratis', 'free_home', 'total'] as $cat) {
                $allAreasSummary['customer'][$cat]['count'] += $unassignedArea['customer'][$cat]['count'];
            }
            $rows[] = $unassignedArea;
        }

        // Hitung persentase untuk All Areas
        $calculatePercentages($allAreasSummary);

        // 6. Hitung Top KPI Stats
        $totalTagihanCount = $allAreasSummary['billing']['total']['count'];
        $lunasCount = $allAreasSummary['billing']['lunas']['count'];
        $collectionRate = $totalTagihanCount > 0 ? round(($lunasCount / $totalTagihanCount) * 100, 2) : 0.0;

        $unpaidCount = $allAreasSummary['billing']['belum_bayar']['count'] + $allAreasSummary['billing']['nunggak']['count'] + $allAreasSummary['billing']['bayar_sebagian']['count'];
        $unpaidAmount = $allAreasSummary['billing']['belum_bayar']['amount'] + $allAreasSummary['billing']['nunggak']['amount'] + $allAreasSummary['billing']['bayar_sebagian']['amount'];

        $kpis = [
            'collection_rate' => $collectionRate,
            'total_invoiced_count' => $totalTagihanCount,
            'total_invoiced_amount' => $allAreasSummary['billing']['total']['amount'],
            'total_paid_count' => $lunasCount,
            'total_paid_amount' => $allAreasSummary['billing']['lunas']['amount'],
            'total_unpaid_count' => $unpaidCount,
            'total_unpaid_amount' => $unpaidAmount,
            'total_nunggak_count' => $allAreasSummary['billing']['nunggak']['count'],
            'total_nunggak_amount' => $allAreasSummary['billing']['nunggak']['amount'],
            'total_janji_count' => $allAreasSummary['billing']['janji_bayar']['count'],
            'total_janji_amount' => $allAreasSummary['billing']['janji_bayar']['amount'],
            'total_active_customers' => $allAreasSummary['customer']['aktif']['count'],
            'total_all_customers' => $allAreasSummary['customer']['total']['count'],
            'total_special_customers' => $allAreasSummary['customer']['psb']['count'] + $allAreasSummary['customer']['gratis']['count'] + $allAreasSummary['customer']['free_home']['count'],
        ];

        // Daftar tahun untuk filter dropdown (3 tahun ke belakang sampai 1 tahun ke depan)
        $currentYear = Carbon::now()->year;
        $years = range($currentYear - 3, $currentYear + 1);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return Inertia::render('Monitoring/AreaMonitoring', [
            'rows' => $rows,
            'summary' => $allAreasSummary,
            'kpis' => $kpis,
            'areas' => $areas->map(fn($a) => ['id' => $a->id, 'name' => $a->name]),
            'filters' => [
                'month' => $month,
                'year' => $year,
                'area_id' => $selectedAreaId,
            ],
            'months' => $months,
            'years' => $years,
        ]);
    }

    /**
     * Endpoint Ajax drill-down untuk menampilkan daftar pelanggan ketika sebuah angka diklik di tabel
     */
    public function customers(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);
        $areaId = $request->query('area_id', 'all');
        $category = $request->query('category', 'belum_bayar');
        $search = $request->query('search');

        $isBillingCategory = in_array($category, ['lunas', 'belum_bayar', 'bayar_sebagian', 'janji_bayar', 'nunggak', 'total_tagihan']);

        // Judul & Badge Kategori
        $categoryLabels = [
            'lunas' => 'Pelanggan Lunas (Tagihan Terbayar)',
            'belum_bayar' => 'Pelanggan Belum Bayar (Jatuh Tempo Mendatang)',
            'bayar_sebagian' => 'Pelanggan Bayar Sebagian (Partial)',
            'janji_bayar' => 'Pelanggan Komitmen Janji Bayar',
            'nunggak' => 'Pelanggan Menunggak (Lewat Jatuh Tempo)',
            'total_tagihan' => 'Semua Tagihan Pelanggan Periode Ini',
            'aktif' => 'Pelanggan Aktif Berbayar',
            'stop_sementara' => 'Pelanggan Stop Sementara (Suspended)',
            'stop_permanen' => 'Pelanggan Stop Permanen (Terminated)',
            'psb' => 'Pelanggan Pemasangan Baru (PSB)',
            'gratis' => 'Pelanggan Layanan Gratis & CSR',
            'free_home' => 'Pelanggan Free Home',
            'total_pelanggan' => 'Seluruh Pelanggan Terdaftar di Area',
        ];

        $targetArea = null;
        if ($areaId !== 'all' && is_numeric($areaId) && (int) $areaId > 0) {
            $targetArea = Area::find((int) $areaId);
        }

        if ($isBillingCategory) {
            // Drill-down data Invoices
            $query = Invoice::query()
                ->with([
                    'customer:id,customer_code,name,phone,address,area_id,area,status,service_status,package_id,notes',
                    'customer.package:id,name,price',
                    'customer.areaModel:id,name'
                ])
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->where('is_reseller_balance', false)
                ->where('invoice_number', 'not like', 'INV-MIG-%');

            // Filter Area
            if ($targetArea) {
                $query->whereHas('customer', function ($cq) use ($targetArea) {
                    $cq->where('area_id', $targetArea->id)
                       ->orWhere('area', $targetArea->name);
                });
            } elseif ($areaId === '0') {
                $allAreaIds = Area::pluck('id')->toArray();
                $query->whereHas('customer', function ($cq) use ($allAreaIds) {
                    $cq->whereNull('area_id')->orWhereNotIn('area_id', $allAreaIds);
                });
            }

            // Filter Kategori Tagihan
            if ($category === 'lunas') {
                $query->where('status', 'paid');
            } elseif ($category === 'janji_bayar') {
                $query->whereIn('status', ['unpaid', 'partial'])->whereNotNull('promise_date');
            } elseif ($category === 'bayar_sebagian') {
                $query->where('status', 'partial')->whereNull('promise_date');
            } elseif ($category === 'nunggak') {
                $query->whereIn('status', ['unpaid', 'partial'])
                    ->whereNull('promise_date')
                    ->where('due_date', '<', $today);
            } elseif ($category === 'belum_bayar') {
                $query->where('status', 'unpaid')
                    ->whereNull('promise_date')
                    ->where(function ($sub) use ($today) {
                        $sub->whereNull('due_date')->orWhere('due_date', '>=', $today);
                    });
            }

            // Search
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                             ->orWhere('customer_code', 'like', "%{$search}%")
                             ->orWhere('phone', 'like', "%{$search}%")
                             ->orWhere('address', 'like', "%{$search}%");
                      });
                });
            }

            $totalAmount = (float) (clone $query)->sum('amount');
            $paginator = $query->latest('due_date')->paginate(20)->withQueryString();

            // Transform data agar seragam dan mudah dipakai di modal
            $items = collect($paginator->items())->map(function ($inv) {
                $cust = $inv->customer;
                return [
                    'id' => $inv->id,
                    'customer_id' => $cust?->id,
                    'customer_code' => $cust?->customer_code ?? '-',
                    'name' => $cust?->name ?? 'Tanpa Nama',
                    'phone' => $cust?->phone ?? '',
                    'address' => $cust?->address ?? '-',
                    'area_name' => $cust?->areaModel?->name ?? $cust?->area ?? '-',
                    'package_name' => $cust?->package?->name ?? '-',
                    'invoice_number' => $inv->invoice_number,
                    'amount' => (float) $inv->amount,
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d/m/Y') : '-',
                    'promise_date' => $inv->promise_date ? Carbon::parse($inv->promise_date)->format('d/m/Y') : null,
                    'status' => $inv->status,
                    'type' => 'invoice',
                ];
            });

            return response()->json([
                'success' => true,
                'category_type' => 'billing',
                'category_key' => $category,
                'category_label' => $categoryLabels[$category] ?? $category,
                'area_title' => $targetArea ? $targetArea->name : ($areaId === '0' ? 'Lainnya / Unassigned' : 'Semua Area (Konsolidasi ISP)'),
                'total_records' => $paginator->total(),
                'total_amount' => $totalAmount,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'items' => $items,
            ]);
        }

        // Drill-down data Customers Lifecycle
        $query = Customer::query()
            ->with(['package:id,name,price', 'areaModel:id,name']);

        // Filter Area
        if ($targetArea) {
            $query->where(function ($cq) use ($targetArea) {
                $cq->where('area_id', $targetArea->id)
                   ->orWhere('area', $targetArea->name);
            });
        } elseif ($areaId === '0') {
            $allAreaIds = Area::pluck('id')->toArray();
            $query->where(function ($cq) use ($allAreaIds) {
                $cq->whereNull('area_id')->orWhereNotIn('area_id', $allAreaIds);
            });
        }

        // Filter Kategori Lifecycle
        if ($category === 'free_home') {
            $query->where('service_status', 'free_home');
        } elseif ($category === 'gratis') {
            $query->where('service_status', 'gratis');
        } elseif ($category === 'psb') {
            $query->whereIn('status', ['booking', 'survey', 'installing', 'pending']);
        } elseif ($category === 'stop_permanen') {
            $query->where('status', 'terminated');
        } elseif ($category === 'stop_sementara') {
            $query->where('status', 'suspended');
        } elseif ($category === 'aktif') {
            $query->where('status', 'active')
                  ->where(function ($sub) {
                      $sub->whereNull('service_status')->orWhereNotIn('service_status', ['gratis', 'free_home']);
                  });
        }

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('name')->paginate(20)->withQueryString();

        $items = collect($paginator->items())->map(function ($cust) {
            return [
                'id' => $cust->id,
                'customer_id' => $cust->id,
                'customer_code' => $cust->customer_code ?? '-',
                'name' => $cust->name ?? 'Tanpa Nama',
                'phone' => $cust->phone ?? '',
                'address' => $cust->address ?? '-',
                'area_name' => $cust->areaModel?->name ?? $cust->area ?? '-',
                'package_name' => $cust->package?->name ?? '-',
                'amount' => (float) ($cust->package?->price ?? $cust->base_amount ?? 0),
                'status' => $cust->status,
                'service_status' => $cust->service_status,
                'type' => 'customer',
            ];
        });

        return response()->json([
            'success' => true,
            'category_type' => 'customer',
            'category_key' => $category,
            'category_label' => $categoryLabels[$category] ?? $category,
            'area_title' => $targetArea ? $targetArea->name : ($areaId === '0' ? 'Lainnya / Unassigned' : 'Semua Area (Konsolidasi ISP)'),
            'total_records' => $paginator->total(),
            'total_amount' => 0,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'items' => $items,
        ]);
    }

    /**
     * Export data Matrix Monitoring Area ke format Excel / CSV persis seperti template spreadsheet pengguna
     */
    public function export(Request $request): StreamedResponse
    {
        $today = Carbon::today()->toDateString();
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthLabel = $monthNames[$month] ?? $month;

        $areas = Area::orderBy('name')->get();
        $invoices = Invoice::with(['customer:id,area_id,area'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('is_reseller_balance', false)
            ->where('invoice_number', 'not like', 'INV-MIG-%')
            ->get();

        $customers = Customer::select('id', 'area_id', 'area', 'status', 'service_status')->get();

        $areaMap = [];
        foreach ($areas as $a) {
            $areaMap[$a->id] = $a;
        }

        $findAreaId = function ($c) use ($areaMap, $areas) {
            if (!empty($c->area_id) && isset($areaMap[$c->area_id])) {
                return (int) $c->area_id;
            }
            if (!empty($c->area)) {
                $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $c->area)));
                foreach ($areas as $a) {
                    if (strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $a->name))) === $clean) {
                        return (int) $a->id;
                    }
                }
            }
            return 0;
        };

        // Buat struktur data
        $data = [];
        foreach ($areas as $a) {
            $data[$a->id] = [
                'name' => $a->name,
                'billing' => [
                    'LUNAS' => 0, 'BELUM BAYAR' => 0, 'BAYAR SEBAGIAN' => 0,
                    'JANJI BAYAR' => 0, 'NUNGGAK' => 0, 'TOTAL' => 0,
                ],
                'customer' => [
                    'AKTIF' => 0, 'STOP SEMENTARA' => 0, 'STOP PERMANEN' => 0,
                    'PSB' => 0, 'GRATIS' => 0, 'FREE HOME' => 0, 'TOTAL' => 0,
                ]
            ];
        }

        foreach ($invoices as $inv) {
            $cust = $inv->customer;
            $aId = $cust ? $findAreaId($cust) : 0;
            if (!isset($data[$aId])) continue;

            $status = strtolower($inv->status ?? 'unpaid');
            $hasPromise = !empty($inv->promise_date);
            $dueDate = $inv->due_date ? Carbon::parse($inv->due_date)->toDateString() : null;
            $isOverdue = $dueDate && $dueDate < $today;

            if ($status === 'paid') $cat = 'LUNAS';
            elseif ($hasPromise) $cat = 'JANJI BAYAR';
            elseif ($status === 'partial') $cat = 'BAYAR SEBAGIAN';
            elseif ($isOverdue) $cat = 'NUNGGAK';
            else $cat = 'BELUM BAYAR';

            $data[$aId]['billing'][$cat]++;
            $data[$aId]['billing']['TOTAL']++;
        }

        foreach ($customers as $c) {
            $aId = $findAreaId($c);
            if (!isset($data[$aId])) continue;

            $st = strtolower($c->status ?? 'active');
            $srv = strtolower($c->service_status ?? 'berbayar');

            if ($srv === 'free_home') $cat = 'FREE HOME';
            elseif ($srv === 'gratis') $cat = 'GRATIS';
            elseif (in_array($st, ['booking', 'survey', 'installing', 'pending'])) $cat = 'PSB';
            elseif ($st === 'terminated') $cat = 'STOP PERMANEN';
            elseif ($st === 'suspended') $cat = 'STOP SEMENTARA';
            else $cat = 'AKTIF';

            $data[$aId]['customer'][$cat]++;
            $data[$aId]['customer']['TOTAL']++;
        }

        $filename = "Monitoring_Belum_Bayar_Area_{$monthLabel}_{$year}.csv";

        return response()->streamDownload(function () use ($data, $monthLabel, $year) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Judul Dokumen
            fputcsv($handle, ["MONITORING PELANGGAN & TAGIHAN PER AREA / WILAYAH"]);
            fputcsv($handle, ["PERIODE: {$monthLabel} {$year}"]);
            fputcsv($handle, []);

            // Header Tabel persis layout mockup pengguna
            fputcsv($handle, ['AREA', 'STATUS TAGIHAN', 'JUMLAH', 'PERSENTASE (%)', 'STATUS PELANGGAN', 'JUMLAH']);

            $grandBilling = ['LUNAS' => 0, 'BELUM BAYAR' => 0, 'BAYAR SEBAGIAN' => 0, 'JANJI BAYAR' => 0, 'NUNGGAK' => 0, 'TOTAL' => 0];
            $grandCustomer = ['AKTIF' => 0, 'STOP SEMENTARA' => 0, 'STOP PERMANEN' => 0, 'PSB' => 0, 'GRATIS' => 0, 'FREE HOME' => 0, 'TOTAL' => 0];

            foreach ($data as $areaItem) {
                $name = $areaItem['name'];
                $b = $areaItem['billing'];
                $c = $areaItem['customer'];
                $totB = $b['TOTAL'];

                $pct = function ($cnt) use ($totB) {
                    return $totB > 0 ? number_format(($cnt / $totB) * 100, 2) . '%' : '0.00%';
                };

                // Baris 1: LUNAS & AKTIF
                fputcsv($handle, [$name, 'LUNAS', $b['LUNAS'], $pct($b['LUNAS']), 'AKTIF', $c['AKTIF']]);
                // Baris 2: BELUM BAYAR & STOP SEMENTARA
                fputcsv($handle, ['', 'BELUM BAYAR', $b['BELUM BAYAR'], $pct($b['BELUM BAYAR']), 'STOP SEMENTARA', $c['STOP SEMENTARA']]);
                // Baris 3: BAYAR SEBAGIAN & STOP PERMANEN
                fputcsv($handle, ['', 'BAYAR SEBAGIAN', $b['BAYAR SEBAGIAN'], $pct($b['BAYAR SEBAGIAN']), 'STOP PERMANEN', $c['STOP PERMANEN']]);
                // Baris 4: JANJI BAYAR & PSB
                fputcsv($handle, ['', 'JANJI BAYAR', $b['JANJI BAYAR'], $pct($b['JANJI BAYAR']), 'PSB', $c['PSB']]);
                // Baris 5: NUNGGAK & GRATIS
                fputcsv($handle, ['', 'NUNGGAK', $b['NUNGGAK'], $pct($b['NUNGGAK']), 'GRATIS', $c['GRATIS']]);
                // Baris 6: FREE HOME
                fputcsv($handle, ['', '', '', '', 'FREE HOME', $c['FREE HOME']]);
                // Baris 7: TOTAL
                fputcsv($handle, ['', 'TOTAL', $totB, $totB > 0 ? '100.00%' : '0.00%', 'TOTAL', $c['TOTAL']]);
                // Pemisah antar area
                fputcsv($handle, ['', '', '', '', '', '']);

                // Akumulasi grand total
                foreach ($b as $k => $v) $grandBilling[$k] += $v;
                foreach ($c as $k => $v) $grandCustomer[$k] += $v;
            }

            // Summary ALL AREA (Konsolidasi ISP)
            $totGrandB = $grandBilling['TOTAL'];
            $grandPct = function ($cnt) use ($totGrandB) {
                return $totGrandB > 0 ? number_format(($cnt / $totGrandB) * 100, 2) . '%' : '0.00%';
            };

            fputcsv($handle, ['SEMUA AREA (KONSOLIDASI ISP)', 'LUNAS', $grandBilling['LUNAS'], $grandPct($grandBilling['LUNAS']), 'AKTIF', $grandCustomer['AKTIF']]);
            fputcsv($handle, ['', 'BELUM BAYAR', $grandBilling['BELUM BAYAR'], $grandPct($grandBilling['BELUM BAYAR']), 'STOP SEMENTARA', $grandCustomer['STOP SEMENTARA']]);
            fputcsv($handle, ['', 'BAYAR SEBAGIAN', $grandBilling['BAYAR SEBAGIAN'], $grandPct($grandBilling['BAYAR SEBAGIAN']), 'STOP PERMANEN', $grandCustomer['STOP PERMANEN']]);
            fputcsv($handle, ['', 'JANJI BAYAR', $grandBilling['JANJI BAYAR'], $grandPct($grandBilling['JANJI BAYAR']), 'PSB', $grandCustomer['PSB']]);
            fputcsv($handle, ['', 'NUNGGAK', $grandBilling['NUNGGAK'], $grandPct($grandBilling['NUNGGAK']), 'GRATIS', $grandCustomer['GRATIS']]);
            fputcsv($handle, ['', '', '', '', 'FREE HOME', $grandCustomer['FREE HOME']]);
            fputcsv($handle, ['', 'TOTAL', $totGrandB, $totGrandB > 0 ? '100.00%' : '0.00%', 'TOTAL', $grandCustomer['TOTAL']]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

