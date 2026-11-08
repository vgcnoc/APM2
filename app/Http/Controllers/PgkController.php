<?php

namespace App\Http\Controllers;

use App\Models\PgkTicket;
use App\Models\PgkMaterial;
use App\Models\PgkMaterialRequest;
use App\Models\Customer;
use App\Models\Area;
use App\Models\User;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\AuditLog;
use App\Models\Ont;
use App\Services\DataScopeService;
use App\Services\RestockStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PgkController extends Controller
{
    public function __construct()
    {
        // Pastikan role 'kepala teknisi' ada
        try {
            Role::firstOrCreate(['name' => 'kepala teknisi']);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Halaman Utama Penanganan Gangguan Komplain (PGK) - Penerimaan & Penanganan Admin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgk')) {
            if ($user->can('menu_pgk_laporan')) {
                return redirect()->route('pgk.laporan');
            }
            if ($user->can('menu_pgk_jadwal')) {
                return redirect()->route('pgk.jadwal');
            }
            abort(403, 'Akses ke modul PGK tidak diizinkan.');
        }

        $query = PgkTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser',
            'dismantledMaterials.material',
            'dismantledMaterials.receiver',
            'dismantledMaterials.technician'
        ]);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgkScope($query);

        // Filter pencarian
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
                  ->orWhere('complainant_phone', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status tab
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'completed') {
                $query->whereIn('status', ['completed', 'resolved_by_admin']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter saluran komplain
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter area
        if ($request->filled('area_id')) {
            $areaModel = Area::find($request->area_id);
            $areaName = $areaModel?->name;
            $query->where(function($q) use ($request, $areaName) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request, $areaName) {
                      $cq->where('area_id', $request->area_id)
                         ->when($areaName, fn($sub) => $sub->orWhere('area', $areaName));
                  });
            });
        }

        // Filter hak akses teknisi (teknisi biasa hanya melihat tugasnya)
        if (auth()->check() && (auth()->user()->hasAnyRole(['teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) || str_contains(strtolower(auth()->user()->role ?? ''), 'teknisi') || str_contains(strtolower(auth()->user()->role ?? ''), 'serbu')) && !auth()->user()->hasRole('admin') && !auth()->user()->hasRole('kepala teknisi')) {
            $query->where(function($q) {
                $q->whereHas('technicians', function($tq) {
                    $tq->where('users.id', auth()->id());
                })->orWhere('lead_technician_id', auth()->id());
            });
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        // Hitungan statistik dengan scope
        $baseCountQuery = PgkTicket::query();
        DataScopeService::applyPgkScope($baseCountQuery, $user);

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'admin_handling' => (clone $baseCountQuery)->where('status', 'admin_handling')->count(),
            'escalated_to_lead' => (clone $baseCountQuery)->where('status', 'escalated_to_lead')->count(),
            'scheduled' => (clone $baseCountQuery)->where('status', 'scheduled')->count(),
            'in_progress' => (clone $baseCountQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseCountQuery)->whereIn('status', ['completed', 'resolved_by_admin'])->count(),
        ];

        return Inertia::render('Pgk/Index', [
            'tickets' => $tickets,
            'counts' => $counts,
            'customers' => function () use ($user) {
                $customerQuery = Customer::whereIn('status', ['active', 'suspended', 'isolir', 'installing'])
                    ->select('id', 'name', 'customer_code', 'phone', 'address', 'area_id', 'area', 'package_id', 'is_reseller')
                    ->with(['package:id,name', 'areaModel:id,name', 'ont:id,customer_id,serial_number,status,rx_power,odp_id', 'ont.odp:id,name']);
                DataScopeService::applyCustomerScope($customerQuery, $user);
                return $customerQuery->get();
            },
            'areas' => fn () => DataScopeService::getScopedAreas($user, 'pgk'),
            'technicians' => function () {
                $techs = User::where(function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
                          ->orWhere('name', 'like', '%teknisi%')
                          ->orWhere('name', 'like', '%serbu%');
                    })
                    ->orWhereIn('role', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
                    ->orWhere('role', 'like', '%teknisi%')
                    ->orWhere('role', 'like', '%serbu%');
                })->select('id', 'name', 'phone')->orderBy('name')->get();
                return $techs->isEmpty() ? User::select('id', 'name', 'phone')->get() : $techs;
            },
            'leadTechnicians' => function () {
                $leads = User::role(['kepala teknisi', 'admin', 'noc', 'manager'])->select('id', 'name')->get();
                return $leads->isEmpty() ? User::select('id', 'name')->get() : $leads;
            },
            'materials' => fn () => Material::select('id', 'name', 'category', 'unit', 'stock')->where('stock', '>', 0)->get(),
            'filters' => $request->only(['search', 'status', 'priority', 'channel', 'area_id']),
        ]);
    }

    /**
     * Halaman Khusus Kepala Teknisi: Penjadwalan & Penyiapan Material PGK
     */
    public function jadwal(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgk_jadwal')) {
            if ($user->can('menu_pgk_laporan')) {
                return redirect()->route('pgk.laporan');
            }
            if ($user->can('menu_pgk')) {
                return redirect()->route('pgk.index');
            }
            abort(403, 'Akses ke Jadwal PGK tidak diizinkan.');
        }

        $status = $request->get('status', 'active');
        $priority = $request->get('priority');
        $areaId = $request->get('area_id');
        $technicianId = $request->get('technician_id');
        $period = $request->get('period');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $materialFilter = $request->get('material_filter');

        // Hitung counts per tab status dengan filter cakupan hak akses
        $baseCountQuery = PgkTicket::query();
        DataScopeService::applyPgkScope($baseCountQuery);

        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $areaModel = Area::find($request->area_id);
            $areaName = $areaModel?->name;
            $baseCountQuery->where(function($q) use ($request, $areaName) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request, $areaName) {
                      $cq->where('area_id', $request->area_id)
                         ->when($areaName, fn($sub) => $sub->orWhere('area', $areaName));
                  });
            });
        }

        $counts = [
            'total' => (clone $baseCountQuery)->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress', 'completed'])->count(),
            'active' => (clone $baseCountQuery)->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress'])->count(),
            'waiting' => (clone $baseCountQuery)->where('status', 'escalated_to_lead')->count(),
            'scheduled' => (clone $baseCountQuery)->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'with_materials' => (clone $baseCountQuery)->where(function($q) {
                $q->whereHas('materials')
                  ->orWhereHas('materialRequests')
                  ->orWhereNotNull('prepared_ont_id')
                  ->orWhere('ont_replacement_status', '!=', 'none')
                  ->orWhereHas('dismantledMaterials');
            })->count(),
        ];

        $query = PgkTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
            'preparedOnt',
            'oldOnt',
            'requestedByUser',
            'preparedByUser',
            'dismantledMaterials.receiver',
            'dismantledMaterials.technician',
            'dismantledMaterials.material',
        ]);

        // Filter: Status
        if ($status === 'waiting') {
            $query->where('status', 'escalated_to_lead');
        } elseif ($status === 'scheduled') {
            $query->where('status', 'scheduled');
        } elseif ($status === 'in_progress') {
            $query->where('status', 'in_progress');
        } elseif ($status === 'completed') {
            $query->where('status', 'completed');
        } elseif ($status === 'all') {
            $query->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress', 'completed']);
        } else {
            $status = 'active';
            $query->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress']);
        }

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgkScope($query);

        // Filter: Pencarian Cerdas
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhere('escalation_reason', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer.ont.odp', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Prioritas
        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Filter: Area / Wilayah
        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $areaModel = Area::find($request->area_id);
            $areaName = $areaModel?->name;
            $query->where(function($q) use ($request, $areaName) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request, $areaName) {
                      $cq->where('area_id', $request->area_id)
                         ->when($areaName, fn($sub) => $sub->orWhere('area', $areaName));
                  });
            });
        }

        // Filter: Teknisi Ditugaskan
        if ($request->filled('technician_id') && $request->technician_id !== 'all') {
            $techId = $request->technician_id;
            $query->whereHas('technicians', function($tq) use ($techId) {
                $tq->where('users.id', $techId);
            });
        }

        // Filter: Periode Jadwal
        if ($request->filled('period') && $request->period !== 'all') {
            if ($request->period === 'today') {
                $query->where(function($q) {
                    $q->whereDate('scheduled_date', today())
                      ->orWhereDate('created_at', today());
                });
            } elseif ($request->period === 'tomorrow') {
                $query->whereDate('scheduled_date', today()->addDay());
            } elseif ($request->period === 'this_week') {
                $query->where(function($q) {
                    $q->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()])
                      ->orWhereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                });
            } elseif ($request->period === 'this_month') {
                $query->where(function($q) {
                    $q->whereMonth('scheduled_date', now()->month)
                      ->orWhereMonth('created_at', now()->month);
                });
            } elseif ($request->period === 'custom') {
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $query->where(function($q) use ($request) {
                        $q->whereBetween('scheduled_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                    });
                }
            }
        }

        // Filter: Material & Komponen
        if ($request->filled('material_filter') && $request->material_filter !== 'all') {
            if ($request->material_filter === 'with_materials') {
                $query->where(function($q) {
                    $q->whereHas('materials')
                      ->orWhereHas('materialRequests')
                      ->orWhereNotNull('prepared_ont_id')
                      ->orWhere('ont_replacement_status', '!=', 'none')
                      ->orWhereHas('dismantledMaterials');
                });
            } elseif ($request->material_filter === 'request_pending') {
                $query->whereHas('materialRequests', function($mq) {
                    $mq->where('status', 'pending');
                });
            } elseif ($request->material_filter === 'with_ont') {
                $query->where(function($q) {
                    $q->whereNotNull('prepared_ont_id')
                      ->orWhere('ont_replacement_status', '!=', 'none');
                });
            } elseif ($request->material_filter === 'with_dismantle') {
                $query->whereHas('dismantledMaterials');
            }
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        $technicians = User::where(function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
                  ->orWhere('name', 'like', '%teknisi%')
                  ->orWhere('name', 'like', '%serbu%');
            })
            ->orWhereIn('role', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
            ->orWhere('role', 'like', '%teknisi%')
            ->orWhere('role', 'like', '%serbu%');
        })->select('id', 'name', 'phone')->orderBy('name')->get();
        if ($technicians->isEmpty()) {
            $technicians = User::select('id', 'name', 'phone')->get();
        }

        $materials = Material::with('stocks')
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();
        $materialTransactions = fn () => RestockStockService::getRestockTransactions();
        $areas = DataScopeService::getScopedAreas($user, 'pgk');
        $ontQuery = Ont::availableForAssignment()
            ->select('id', 'ont_id', 'serial_number', 'brand', 'model', 'status', 'area_id')
            ->orderBy('serial_number');
        DataScopeService::applyOntScope($ontQuery, $user);
        $availableOnts = fn () => $ontQuery->get();

        return Inertia::render('Pgk/Jadwal', [
            'tickets' => $tickets,
            'areas' => $areas,
            'technicians' => $technicians,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'availableOnts' => $availableOnts,
            'counts' => $counts,
            'filters' => array_merge($request->only([
                'search', 'area_id', 'priority', 'technician_id', 'period', 'start_date', 'end_date', 'material_filter'
            ]), ['status' => $status]),
        ]);
    }

    /**
     * Halaman Khusus Teknisi: Laporan Hasil Penanganan PGK
     */
    public function laporan(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgk_laporan')) {
            if ($user->can('menu_pgk')) {
                return redirect()->route('pgk.index');
            }
            if ($user->can('menu_pgk_jadwal')) {
                return redirect()->route('pgk.jadwal');
            }
            abort(403, 'Akses ke Laporan PGK tidak diizinkan.');
        }

        // Summary counts for metric cards (scoped to user's permissions)
        $baseCountQuery = PgkTicket::whereIn('status', ['scheduled', 'in_progress', 'completed']);
        DataScopeService::applyPgkScope($baseCountQuery);
        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $baseCountQuery->where(function($q) use ($request) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request) {
                      $cq->where('area_id', $request->area_id);
                  });
            });
        }
        $counts = [
            'total' => (clone $baseCountQuery)->count(),
            'scheduled' => (clone $baseCountQuery)->where('status', 'scheduled')->count(),
            'in_progress' => (clone $baseCountQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'with_replacement' => (clone $baseCountQuery)->where(function($q) {
                $q->where('ont_replacement_status', 'installed')
                  ->orWhereHas('dismantledMaterials');
            })->count(),
        ];

        $query = PgkTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
            'preparedOnt',
            'oldOnt',
            'requestedByUser',
            'preparedByUser',
            'customer.activeInstalledMaterials.material',
            'customer.installedMaterials.material',
            'dismantledMaterials.receiver',
            'dismantledMaterials.technician',
            'dismantledMaterials.material',
        ])->whereIn('status', ['scheduled', 'in_progress', 'completed']);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgkScope($query);

        // Filter: Pencarian Pintar
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhere('admin_notes', 'like', "%{$search}%")
                  ->orWhere('resolution_notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer.ont.odp', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter: Prioritas
        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Filter: Area / Wilayah
        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $areaModel = Area::find($request->area_id);
            $areaName = $areaModel?->name;
            $query->where(function($q) use ($request, $areaName) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request, $areaName) {
                      $cq->where('area_id', $request->area_id)
                         ->when($areaName, fn($sub) => $sub->orWhere('area', $areaName));
                  });
            });
        }

        // Filter: Periode Tanggal
        if ($request->filled('period') && $request->period !== 'all') {
            if ($request->period === 'today') {
                $query->where(function($q) {
                    $q->whereDate('scheduled_date', today())
                      ->orWhereDate('created_at', today());
                });
            } elseif ($request->period === 'this_week') {
                $query->where(function($q) {
                    $q->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()])
                      ->orWhereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                });
            } elseif ($request->period === 'this_month') {
                $query->where(function($q) {
                    $q->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
                      ->orWhereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                });
            } elseif ($request->period === 'custom') {
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $query->where(function($q) use ($request) {
                        $q->whereBetween('scheduled_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                    });
                } elseif ($request->filled('start_date')) {
                    $query->where(function($q) use ($request) {
                        $q->whereDate('scheduled_date', '>=', $request->start_date)
                          ->orWhereDate('created_at', '>=', $request->start_date);
                    });
                } elseif ($request->filled('end_date')) {
                    $query->where(function($q) use ($request) {
                        $q->whereDate('scheduled_date', '<=', $request->end_date)
                          ->orWhereDate('created_at', '<=', $request->end_date);
                    });
                }
            }
        }

        // Filter: Pergantian Material / ONT
        if ($request->filled('has_replacement') && $request->has_replacement !== 'all') {
            if ($request->has_replacement === 'yes') {
                $query->where(function($q) {
                    $q->where('ont_replacement_status', 'installed')
                      ->orWhereHas('dismantledMaterials');
                });
            } elseif ($request->has_replacement === 'no') {
                $query->where(function($q) {
                    $q->where(function($sub) {
                        $sub->whereNull('ont_replacement_status')
                            ->orWhere('ont_replacement_status', '!=', 'installed');
                    })->whereDoesntHave('dismantledMaterials');
                });
            }
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        // Pastikan material terpasang di pelanggan telah terisi secara cerdas
        foreach ($tickets as $t) {
            if ($t->customer) {
                \App\Services\CustomerMaterialService::getOrSeedInstalledMaterials($t->customer);
                $t->customer->unsetRelation('activeInstalledMaterials');
                $t->customer->unsetRelation('installedMaterials');
                $t->customer->load('activeInstalledMaterials.material', 'installedMaterials.material');
            }
        }

        $materials = Material::with('stocks')->where(function ($q) { $q->where('is_active', true)->orWhereNull('is_active'); })->select('id', 'name', 'category', 'unit', 'stock')->orderBy('name')->get();
        $materialTransactions = fn () => RestockStockService::getRestockTransactions();
        $areas = DataScopeService::getScopedAreas($user, 'pgk');
        $ontQuery = Ont::availableForAssignment()
            ->select('id', 'ont_id', 'serial_number', 'brand', 'model', 'status', 'area_id')
            ->orderBy('serial_number');
        DataScopeService::applyOntScope($ontQuery, $user);
        $availableOnts = fn () => $ontQuery->get();

        return Inertia::render('Pgk/Laporan', [
            'tickets' => $tickets,
            'areas' => $areas,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'availableOnts' => $availableOnts,
            'counts' => $counts,
            'filters' => $request->only([
                'search', 'area_id', 'status', 'priority', 'period', 'start_date', 'end_date', 'has_replacement', 'per_page'
            ]),
        ]);
    }

    /**
     * Step 1: Admin Menerima Komplain Pelanggan
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $canCreate = $user && (
            $user->can('pgk_btn_create') ||
            $user->can('pgk_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'cs', 'admin cs'])
        );
        if (!$canCreate) {
            abort(403, 'Anda tidak memiliki hak akses untuk mendaftarkan komplain PGK.');
        }

        $validated = $request->validate([
            'area_id' => 'nullable|exists:areas,id',
            'customer_id' => 'required|exists:customers,id',
            'complainant_name' => 'nullable|string|max:150',
            'complainant_phone' => 'nullable|string|max:50',
            'channel' => 'required|string|in:whatsapp,telepon,datang_kantor,aplikasi,lainnya',
            'category' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'reported_issue' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $customer = Customer::find($validated['customer_id']);
        if (empty($validated['area_id'])) {
            $validated['area_id'] = $customer?->area_id;
            if (empty($validated['area_id']) && $customer?->area) {
                $matchedArea = Area::where('name', $customer->area)->first();
                $validated['area_id'] = $matchedArea?->id;
            }
        }

        if (empty($validated['complainant_name'])) {
            $validated['complainant_name'] = $customer?->name;
        }
        if (empty($validated['complainant_phone'])) {
            $validated['complainant_phone'] = $customer?->phone;
        }

        $validated['status'] = 'admin_handling';
        $validated['created_by'] = auth()->id();

        $ticket = PgkTicket::create($validated);

        AuditLog::createLog(
            'Penerimaan Komplain (PGK)',
            $ticket,
            null,
            'admin_handling',
            'Admin menerima komplain gangguan pelanggan ID: ' . $validated['customer_id'] . ' (' . ($ticket->complainant_name ?? '') . ')'
        );

        return redirect()->back()->with('success', 'Komplain Gangguan (' . $ticket->ticket_number . ') berhasil didaftarkan.');
    }

    /**
     * Step 1B: Admin berhasil menyelesaikan komplain jarak jauh di sistem
     */
    public function resolveByAdmin(Request $request, PgkTicket $pgk)
    {
        $user = $request->user();
        $canResolve = $user && (
            $user->can('pgk_btn_resolve_admin') ||
            $user->can('pgk_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'cs', 'admin cs'])
        );
        if (!$canResolve) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyelesaikan komplain PGK di sistem.');
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string',
            'update_pppoe' => 'nullable|boolean',
            'new_pppoe_user' => 'nullable|string|max:100',
            'new_pppoe_password' => 'nullable|string|max:100',
            'update_wifi' => 'nullable|boolean',
            'new_wifi_ssid' => 'nullable|string|max:100',
            'new_wifi_password' => 'nullable|string|max:100',
        ]);

        $oldStatus = $pgk->status;
        $resolutionNotes = 'Diselesaikan langsung oleh Admin di sistem: ' . $validated['admin_notes'];

        // Handle update kredensial login internet pelanggan (PPPoE) jika diisi
        if ($request->boolean('update_pppoe') || !empty($validated['new_pppoe_user']) || !empty($validated['new_pppoe_password'])) {
            $customer = $pgk->customer ?? Customer::find($pgk->customer_id);
            $targetOnt = $customer?->ont ?? Ont::where('customer_id', $pgk->customer_id)->first();
            $newPppoeUser = trim((string)($validated['new_pppoe_user'] ?? ''));
            $newPppoePass = (string)($validated['new_pppoe_password'] ?? '');

            if (!empty($newPppoeUser) && $targetOnt) {
                $dup = \App\Http\Controllers\OntController::findPppoeDuplicate($newPppoeUser, $targetOnt->id);
                if ($dup) {
                    $detail = !empty($dup->customer) ? " (digunakan oleh pelanggan: {$dup->customer->name} / {$dup->customer->customer_code})" : " (digunakan oleh ONT SN: {$dup->serial_number})";
                    return redirect()->back()->withErrors([
                        'new_pppoe_user' => "Akun login internet / PPPoE '{$newPppoeUser}' sudah digunakan{$detail}. Sistem otomatis menolak duplikasi!"
                    ])->with('error', "Username PPPoE '{$newPppoeUser}' sudah terdaftar{$detail}.");
                }
            }

            $oldPppoeUser = $targetOnt?->pppoe_user ?: $customer?->pppoe_username;
            $ontUpdates = [];
            $custUpdates = [];
            if (!empty($newPppoeUser)) {
                $ontUpdates['pppoe_user'] = $newPppoeUser;
                $custUpdates['pppoe_username'] = $newPppoeUser;
            }
            if (!empty($newPppoePass)) {
                $ontUpdates['pppoe_password'] = $newPppoePass;
                $custUpdates['pppoe_password'] = $newPppoePass;
            }

            if (!empty($ontUpdates) && $targetOnt) {
                $targetOnt->update($ontUpdates);
            }
            if (!empty($custUpdates) && $customer) {
                $customer->update($custUpdates);
            }

            if ($customer && (!empty($newPppoeUser) || !empty($newPppoePass))) {
                try {
                    app(\App\Services\RadiusService::class)->syncCustomer($customer, array_filter([$oldPppoeUser]));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[RADIUS] PGK resolveByAdmin PPPoE sync error: ' . $e->getMessage());
                }
                $resolutionNotes .= ' | Akun PPPoE ONT diperbarui ke: ' . ($newPppoeUser ?: $oldPppoeUser);
            }
        }

        // Handle update kredensial WiFi Pelanggan (SSID & Password) jika diisi
        if ($request->boolean('update_wifi') || !empty($validated['new_wifi_ssid']) || !empty($validated['new_wifi_password'])) {
            $customer = $pgk->customer ?? Customer::find($pgk->customer_id);
            $targetOnt = $customer?->ont ?? Ont::where('customer_id', $pgk->customer_id)->first();
            $newWifiSsid = trim((string)($validated['new_wifi_ssid'] ?? ''));
            $newWifiPass = (string)($validated['new_wifi_password'] ?? '');

            $wifiOntUpdates = [];
            $wifiCustUpdates = [];
            if (!empty($newWifiSsid)) {
                $wifiOntUpdates['wifi_ssid'] = $newWifiSsid;
                $wifiCustUpdates['wifi_ssid'] = $newWifiSsid;
            }
            if (!empty($newWifiPass)) {
                $wifiOntUpdates['wifi_password'] = $newWifiPass;
                $wifiCustUpdates['wifi_password'] = $newWifiPass;
            }

            if (!empty($wifiOntUpdates) && $targetOnt) {
                $targetOnt->update($wifiOntUpdates);
            }
            if (!empty($wifiCustUpdates) && $customer) {
                $customer->update($wifiCustUpdates);
            }

            if (!empty($newWifiSsid) || !empty($newWifiPass)) {
                $resolutionNotes .= ' | WiFi Pelanggan diperbarui: SSID ' . ($newWifiSsid ?: ($targetOnt?->wifi_ssid ?: '-'));
                AuditLog::createLog(
                    'Update WiFi Pasca Resolve PGK Admin',
                    $targetOnt ?? $customer,
                    $targetOnt?->wifi_ssid ?: '-',
                    $newWifiSsid ?: ($targetOnt?->wifi_ssid ?: '-'),
                    'Nama WiFi/Sandi diperbarui oleh Admin di sistem'
                );
            }
        }

        $pgk->update([
            'status' => 'resolved_by_admin',
            'admin_notes' => $validated['admin_notes'],
            'resolution_notes' => $resolutionNotes,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'PGK Selesai oleh Admin',
            $pgk,
            $oldStatus,
            'resolved_by_admin',
            'Admin menyelesaikan komplain secara remote/sistem: ' . $validated['admin_notes']
        );

        return redirect()->back()->with('success', 'Komplain berhasil diselesaikan langsung oleh Admin di sistem.');
    }

    /**
     * Step 1C: Admin meneruskan komplain ke Kepala Teknisi untuk kunjungan lapangan
     */
    public function escalateToLead(Request $request, PgkTicket $pgk)
    {
        $user = $request->user();
        $canEscalate = $user && (
            $user->can('pgk_btn_escalate') ||
            $user->can('pgk_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'cs', 'admin cs'])
        );
        if (!$canEscalate) {
            abort(403, 'Anda tidak memiliki hak akses untuk meneruskan komplain ke Kepala Teknisi.');
        }

        $validated = $request->validate([
            'escalation_reason' => 'required|string',
            'lead_technician_id' => 'nullable|exists:users,id',
            'admin_notes' => 'nullable|string',
        ]);

        $oldStatus = $pgk->status;

        $pgk->update([
            'status' => 'escalated_to_lead',
            'escalated_at' => now(),
            'escalation_reason' => $validated['escalation_reason'],
            'admin_notes' => $validated['admin_notes'] ?: $pgk->admin_notes,
            'lead_technician_id' => $validated['lead_technician_id'] ?? $pgk->lead_technician_id,
        ]);

        AuditLog::createLog(
            'PGK Diteruskan ke Kepala Teknisi',
            $pgk,
            $oldStatus,
            'escalated_to_lead',
            'Komplain diteruskan ke Kepala Teknisi untuk dijadwalkan: ' . $validated['escalation_reason']
        );

        return redirect()->back()->with('success', 'Komplain PGK berhasil diteruskan ke Kepala Teknisi untuk dijadwalkan.');
    }

    /**
     * Step 2: Kepala Teknisi menjadwalkan penanganan ke teknisi & menyiapkan material
     */
    public function schedule(Request $request, PgkTicket $pgk)
    {
        $user = $request->user();
        $canSchedule = $user && (
            $user->can('pgk_btn_assign') ||
            $user->can('pgk_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher', 'maintenance'])
        );
        if (!$canSchedule) {
            abort(403, 'Anda tidak memiliki hak akses untuk menjadwalkan teknisi PGK.');
        }

        // Normalisasi parameter teknisi
        if ($request->has('technician_ids') && !$request->has('technicians')) {
            $request->merge(['technicians' => $request->technician_ids]);
        }
        if ($request->has('technicians') && !$request->has('technician_ids')) {
            $request->merge(['technician_ids' => $request->technicians]);
        }

        // Normalisasi parameter materials
        if ($request->has('materials') && is_array($request->materials)) {
            $mats = $request->materials;
            foreach ($mats as $k => $m) {
                if (isset($m['quantity']) && !isset($m['quantity_prepared'])) {
                    $mats[$k]['quantity_prepared'] = $m['quantity'];
                } elseif (isset($m['quantity_prepared']) && !isset($m['quantity'])) {
                    $mats[$k]['quantity'] = $m['quantity_prepared'];
                }
            }
            $request->merge(['materials' => $mats]);
        }

        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:50',
            'technicians' => 'required_without:technician_ids|array|min:1',
            'technicians.*' => 'exists:users,id',
            'technician_ids' => 'sometimes|array',
            'technician_ids.*' => 'exists:users,id',
            'lead_notes' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.material_transaction_item_id' => 'nullable',
            'materials.*.trx_number' => 'nullable|string|max:100',
            'materials.*.quantity_prepared' => 'nullable|numeric|min:0.01',
            'materials.*.quantity' => 'nullable|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
            'materials.*.notes' => 'nullable|string',
        ]);

        $oldStatus = $pgk->status;

        $pgk->update([
            'status' => 'scheduled',
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'lead_notes' => $validated['lead_notes'] ?? null,
            'lead_technician_id' => auth()->id(),
        ]);

        // Hubungkan teknisi pelaksana
        $techIds = $request->technicians ?? $request->technician_ids ?? [];
        $pgk->technicians()->sync($techIds);

        // Penyiapan material dari gudang / order (pulihkan dulu jadwal regular sebelumnya agar revisi / pengosongan jadwal selalu simetris)
        $areaId = $pgk->area_id ?? $pgk->customer?->area_id;
        $restoredScheduleMats = [];

        $prevScheduleOuts = MaterialTransaction::with('items.material')
            ->where('source_type', 'pgk')
            ->where('source_id', $pgk->id)
            ->where('type', 'out')
            ->where('transaction_number', 'like', 'OUT-PGK-%')
            ->where('transaction_number', 'not like', 'OUT-PGK-ONT-%')
            ->where('transaction_number', 'not like', 'OUT-PGK-REQ-%')
            ->where('transaction_number', 'not like', 'OUT-PGK-EXTRA-%')
            ->where('transaction_number', 'not like', 'OUT-PGK-OVERAGE-%')
            ->get();

        foreach ($prevScheduleOuts as $prevOut) {
            $restoreAreaId = $prevOut->area_id ?: $areaId;
            if ($prevOut->status === 'approved' && !$prevOut->is_migration) {
                foreach ($prevOut->items as $prevItem) {
                    if ($prevItem->material && (float) $prevItem->quantity > 0) {
                        $res = \App\Services\MaterialService::restoreOperationalStock(
                            $prevItem->material,
                            (float) $prevItem->quantity,
                            $restoreAreaId,
                            $prevItem->unit
                        );
                        $restoredScheduleMats[$prevItem->material->id] = ($restoredScheduleMats[$prevItem->material->id] ?? 0) + $res['base_qty'];
                    }
                }
            }
            $prevOut->skipStockRestoreOnDelete = true;
            $prevOut->items()->delete();
            $prevOut->delete();
        }

        // Fallback untuk baris pgk_materials lama tanpa transaksi OUT-PGK (kecuali tambahan request yang punya OUT-PGK-REQ sendiri)
        $previousMaterials = $pgk->materials()
            ->where(function ($q) {
                $q->whereNull('notes')
                  ->orWhere('notes', 'not like', '%Tambahan disetujui%');
            })
            ->get();

        foreach ($previousMaterials as $prevMat) {
            $prevQty = (float) $prevMat->quantity_prepared;
            if ($prevQty > 0 && $prevMat->material_id && empty($restoredScheduleMats[(int) $prevMat->material_id])) {
                $matModel = Material::find($prevMat->material_id);
                if ($matModel) {
                    $orderItem = $prevMat->material_transaction_item_id
                        ? MaterialTransactionItem::find($prevMat->material_transaction_item_id)
                        : null;
                    $targetAreaId = $areaId ?? $orderItem?->materialTransaction?->area_id ?? $orderItem?->transaction?->area_id;
                    \App\Services\MaterialService::restoreOperationalStock($matModel, $prevQty, $targetAreaId, $prevMat->unit);
                }
            }
        }

        $pgk->materials()
            ->where(function ($q) {
                $q->whereNull('notes')
                  ->orWhere('notes', 'not like', '%Tambahan disetujui%');
            })
            ->delete();

        if (!empty($validated['materials'])) {
            $techNames = User::whereIn('id', $techIds)->pluck('name')->join(', ');

            $sourceOrders = array_filter(array_unique(array_column($validated['materials'], 'trx_number')));
            $orderRefStr = !empty($sourceOrders) ? ' (Ref Order/SJ: ' . implode(', ', $sourceOrders) . ')' : '';

            // Buat transaksi material keluar
            $trxNumber = \App\Services\MaterialService::generateTransactionNumber('OUT-PGK');
            $trans = MaterialTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'out',
                'status' => 'approved',
                'request_type' => 'regular',
                'date' => now(),
                'technician_name' => $techNames ?: auth()->user()->name,
                'purpose' => 'Penyiapan Material PGK: ' . $pgk->ticket_number . ' (' . ($pgk->customer?->name ?? '') . ')' . $orderRefStr,
                'source_type' => 'pgk',
                'source_id' => $pgk->id,
                'source_reference' => $pgk->ticket_number,
                'user_id' => auth()->id(),
                'customer_id' => $pgk->customer_id,
                'area_id' => $areaId,
            ]);

            $totalScheduleCost = 0;
            foreach ($validated['materials'] as $matItem) {
                if (empty($matItem['material_id'])) continue;
                $material = Material::find($matItem['material_id']);
                if ($material) {
                    $qty = (float) ($matItem['quantity_prepared'] ?? $matItem['quantity'] ?? 0);
                    if ($qty <= 0) continue;

                    $trxItemId = $matItem['material_transaction_item_id'] ?? null;
                    $orderTrxItem = $trxItemId ? MaterialTransactionItem::with(['materialTransaction', 'transaction'])->find($trxItemId) : null;
                    $trxNumber = $matItem['trx_number'] ?? $orderTrxItem?->materialTransaction?->transaction_number ?? $orderTrxItem?->transaction?->transaction_number;
                    $targetAreaId = $areaId ?? $orderTrxItem?->materialTransaction?->area_id ?? $orderTrxItem?->transaction?->area_id;
                    $unit = $matItem['unit'] ?? $material->unit;

                    $stockRes = \App\Services\MaterialService::deductOperationalStock($material, $qty, $targetAreaId, $unit);

                    PgkMaterial::create([
                        'pgk_ticket_id' => $pgk->id,
                        'material_id' => $material->id,
                        'material_transaction_item_id' => $trxItemId,
                        'trx_number' => $trxNumber,
                        'quantity_prepared' => $qty,
                        'quantity_used' => $qty,
                        'quantity_returned' => 0,
                        'unit' => $unit,
                        'notes' => $matItem['notes'] ?? null,
                    ]);

                    $ppu = \App\Services\MaterialService::calculateUnitPrice($material, $unit);
                    $itemTotal = $ppu * $qty;
                    $totalScheduleCost += $itemTotal;

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $trans->id,
                        'material_id' => $material->id,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $itemTotal,
                        'stock_before' => $stockRes['stock_before'],
                        'stock_after' => $stockRes['stock_after'],
                    ]);
                }
            }
            $trans->update(['total_cost' => $totalScheduleCost]);
        }

        AuditLog::createLog(
            'Penjadwalan & Material PGK',
            $pgk,
            $oldStatus,
            'scheduled',
            'Kepala Teknisi menjadwalkan kunjungan teknisi dan menyiapkan material penanganan komplain.'
        );

        return redirect()->back()->with('success', 'Penanganan komplain berhasil dijadwalkan ke Teknisi.');
    }

    /**
     * Step 3: Teknisi membuat laporan penanganan komplain
     */
    public function submitReport(Request $request, PgkTicket $pgk)
    {
        $user = $request->user();
        $canReport = $user && (
            $user->can('pgk_btn_report') ||
            $user->can('pgk_manage') ||
            $user->hasAnyRole(['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            str_contains(strtolower($user->role ?? ''), 'teknisi') ||
            str_contains(strtolower($user->role ?? ''), 'serbu') ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'teknisi'])
        );
        if (!$canReport) {
            abort(403, 'Anda tidak memiliki hak akses untuk melaporkan hasil penanganan PGK.');
        }

        // Jika scope 'own' (teknisi biasa), verifikasi bahwa user memang ditugaskan pada komplain ini
        if ($user && \App\Services\DataScopeService::getScopeLevel($user, 'pgk') === 'own') {
            $isAssigned = $pgk->technicians()->where('users.id', $user->id)->exists()
                || $pgk->created_by === $user->id
                || $pgk->lead_technician_id === $user->id;
            if (!$isAssigned) {
                abort(403, 'Anda hanya dapat melaporkan komplain PGK yang ditugaskan kepada Anda.');
            }
        }

        $validated = $request->validate([
            'action_taken' => 'required|string',
            'optical_power_before' => 'nullable|string|max:50',
            'optical_power_after' => 'nullable|string|max:50',
            'start_time' => 'nullable|string|max:50',
            'end_time' => 'nullable|string|max:50',
            'resolution_notes' => 'nullable|string',
            'photo_before' => 'nullable|image|max:10240',
            'photo_after' => 'nullable|image|max:10240',
            'update_pppoe' => 'nullable|boolean',
            'new_pppoe_user' => 'nullable|string|max:100',
            'new_pppoe_password' => 'nullable|string|max:100',
            'update_wifi' => 'nullable|boolean',
            'new_wifi_ssid' => 'nullable|string|max:100',
            'new_wifi_password' => 'nullable|string|max:100',
            'ont_replacement_action' => 'nullable|in:installed,cancelled',
            'ont_replacement_notes' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.id' => 'nullable',
            'materials.*.pgk_material_id' => 'nullable',
            'materials.*.material_id' => 'nullable|exists:materials,id',
            'materials.*.quantity_used' => 'nullable|numeric|min:0',
            'materials.*.quantity_returned' => 'nullable|numeric|min:0',
            'incident_coordinates' => 'nullable|array',
            'incident_coordinates.*.label' => 'nullable|string|max:100',
            'incident_coordinates.*.latitude' => 'nullable|numeric',
            'incident_coordinates.*.longitude' => 'nullable|numeric',
            'incident_coordinates.*.address_reference' => 'nullable|string',
            'incident_coordinates.*.notes' => 'nullable|string',
            'material_replacements' => 'nullable|array',
            'material_replacements.*.installed_material_id' => 'nullable|integer',
            'material_replacements.*.item_name' => 'required|string|max:255',
            'material_replacements.*.replacement_type' => 'required|in:full,partial',
            'material_replacements.*.quantity' => 'required|numeric|min:0.01',
            'material_replacements.*.unit' => 'nullable|string|max:50',
            'material_replacements.*.condition' => 'required|in:rusak_scrap,normal_reusable,perlu_cek',
            'material_replacements.*.damage_reason' => 'nullable|string|max:255',
            'material_replacements.*.replacement_material_id' => 'nullable|integer',
            'material_replacements.*.replacement_quantity' => 'nullable|numeric|min:0',
            'additional_materials' => 'nullable|array',
            'additional_materials.*.material_id' => 'required|exists:materials,id',
            'additional_materials.*.quantity' => 'required|numeric|min:0.01',
            'additional_materials.*.unit' => 'nullable|string',
            'additional_materials.*.notes' => 'nullable|string',
        ]);

        $photoBeforePath = $pgk->photo_before;
        if ($request->hasFile('photo_before')) {
            $photoBeforePath = $request->file('photo_before')->store('pgk_photos', 'public');
        }

        $photoAfterPath = $pgk->photo_after;
        if ($request->hasFile('photo_after')) {
            $photoAfterPath = $request->file('photo_after')->store('pgk_photos', 'public');
        }

        $areaId = $pgk->area_id ?? $pgk->customer?->area_id;

        // 1. Handle Rekonsiliasi Material Bekal Awal (Serah Terima Pengembalian Gudang)
        if (!empty($request->materials) && is_array($request->materials)) {
            \App\Services\MaterialHandoverService::replacePendingOrApprovedReturnHandover(
                'pgk',
                $pgk->id,
                $areaId,
                $pgk->ticket_number
            );

            // Pulihkan transaksi overage dan extra sebelumnya jika laporan diedit ulang
            $oldOuts = MaterialTransaction::with('items.material')
                ->where('source_type', 'pgk')
                ->where('source_id', $pgk->id)
                ->where('type', 'out')
                ->where(function ($q) {
                    $q->where('transaction_number', 'like', 'OUT-PGK-OVERAGE-%')
                      ->orWhere('transaction_number', 'like', 'OUT-PGK-EXTRA-%');
                })
                ->get();
            foreach ($oldOuts as $oo) {
                if ($oo->status === 'approved' && !$oo->is_migration) {
                    foreach ($oo->items as $ooItem) {
                        if ($ooItem->material && (float) $ooItem->quantity > 0) {
                            \App\Services\MaterialService::restoreOperationalStock(
                                $ooItem->material,
                                (float) $ooItem->quantity,
                                $oo->area_id ?: $areaId,
                                $ooItem->unit
                            );
                        }
                    }
                }
                $oo->skipStockRestoreOnDelete = true;
                $oo->items()->delete();
                $oo->delete();
            }

            // Bersihkan baris pgk_materials tambahan lama agar tidak terduplikasi
            PgkMaterial::where('pgk_ticket_id', $pgk->id)
                ->where('notes', 'like', 'Tambahan:%')
                ->delete();

            $itemsToReturn = [];
            $overageItems = [];

            foreach ($request->materials as $matInput) {
                $pgkMatId = $matInput['id'] ?? $matInput['pgk_material_id'] ?? null;
                if (!$pgkMatId) continue;

                $pgkMat = PgkMaterial::find($pgkMatId);
                if ($pgkMat) {
                    $qtyUsed = (float) ($matInput['quantity_used'] ?? 0);
                    $prepQty = (float) $pgkMat->quantity_prepared;
                    $qtyReturned = isset($matInput['quantity_returned'])
                        ? (float) $matInput['quantity_returned']
                        : max(0, $prepQty - $qtyUsed);

                    $pgkMat->update([
                        'quantity_used' => $qtyUsed,
                        'quantity_returned' => $qtyReturned,
                    ]);

                    // Jika ada sisa material yang dikembalikan, kumpulkan untuk serah terima ke gudang
                    if ($qtyReturned > 0) {
                        $itemsToReturn[] = [
                            'material_id' => $pgkMat->material_id,
                            'quantity' => $qtyReturned,
                            'unit' => $pgkMat->unit ?? 'pcs',
                            'condition' => 'Layak Pakai',
                        ];
                    }

                    // Jika pemakaian aktual melebihi bekal awal, potong selisihnya dari stok
                    if ($qtyUsed > $prepQty && $pgkMat->material_id) {
                        $overageItems[] = [
                            'material_id' => $pgkMat->material_id,
                            'quantity' => $qtyUsed - $prepQty,
                            'unit' => $pgkMat->unit ?? 'pcs',
                        ];
                    }
                }
            }

            if (!empty($overageItems)) {
                $ovTrxNum = \App\Services\MaterialService::generateTransactionNumber('OUT-PGK-OVERAGE');
                $ovTrans = MaterialTransaction::create([
                    'transaction_number' => $ovTrxNum,
                    'type' => 'out',
                    'status' => 'approved',
                    'request_type' => 'additional_material',
                    'date' => now(),
                    'technician_name' => auth()->user()->name,
                    'purpose' => 'Selisih Lebih Pemakaian Material PGK: ' . $pgk->ticket_number,
                    'source_type' => 'pgk',
                    'source_id' => $pgk->id,
                    'source_reference' => $pgk->ticket_number,
                    'user_id' => auth()->id(),
                    'customer_id' => $pgk->customer_id,
                    'area_id' => $areaId,
                ]);
                $ovCost = 0;
                foreach ($overageItems as $ov) {
                    $ovMat = Material::find($ov['material_id']);
                    if (!$ovMat) continue;
                    $ovStock = \App\Services\MaterialService::deductOperationalStock($ovMat, (float) $ov['quantity'], $areaId, $ov['unit']);
                    $ppu = \App\Services\MaterialService::calculateUnitPrice($ovMat, $ov['unit']);
                    $ovTotal = $ppu * (float) $ov['quantity'];
                    $ovCost += $ovTotal;
                    MaterialTransactionItem::create([
                        'material_transaction_id' => $ovTrans->id,
                        'material_id' => $ovMat->id,
                        'quantity' => $ov['quantity'],
                        'unit' => $ov['unit'],
                        'price_per_unit' => $ppu,
                        'total_price' => $ovTotal,
                        'stock_before' => $ovStock['stock_before'],
                        'stock_after' => $ovStock['stock_after'],
                    ]);

                    // Sinkronisasi otomatis ke CustomerInstalledMaterial
                    if ($pgk->customer_id) {
                        \App\Models\CustomerInstalledMaterial::create([
                            'customer_id' => $pgk->customer_id,
                            'material_id' => $ovMat->id,
                            'item_name' => $ovMat->name,
                            'quantity' => (float) $ov['quantity'],
                            'unit' => $ov['unit'],
                            'installed_at' => now(),
                            'installation_source' => 'pgk_overage',
                            'status' => 'installed',
                            'notes' => "Kelebihan pemakaian material lapangan pada tiket PGK #{$pgk->ticket_number}",
                        ]);
                    }
                }
                $ovTrans->update(['total_cost' => $ovCost]);
            }

            if (!empty($itemsToReturn)) {
                \App\Services\MaterialHandoverService::submitReturnHandover(
                    sourceType: 'pgk',
                    sourceId: $pgk->id,
                    sourceRef: $pgk->ticket_number,
                    items: $itemsToReturn,
                    technicianName: auth()->user()->name,
                    areaId: $areaId,
                    notes: 'Pengembalian Sisa Material PGK: ' . $pgk->ticket_number
                );
            }
        }

        // 2. Handle Permintaan / Material Tambahan di Lapangan (Extra Materials)
        if (!empty($request->additional_materials) && is_array($request->additional_materials)) {
            $extraItems = array_filter($request->additional_materials, function ($item) {
                return !empty($item['material_id']) && (float) ($item['quantity'] ?? 0) > 0;
            });

            if (!empty($extraItems)) {
                $extraTrxNum = \App\Services\MaterialService::generateTransactionNumber('OUT-PGK-EXTRA');
                $outTrans = MaterialTransaction::create([
                    'transaction_number' => $extraTrxNum,
                    'type' => 'out',
                    'status' => 'approved',
                    'date' => now(),
                    'technician_name' => auth()->user()->name,
                    'purpose' => 'Material Tambahan Lapangan PGK: ' . $pgk->ticket_number . ' (' . ($pgk->customer?->name ?? '') . ')',
                    'source_type' => 'pgk',
                    'source_id' => $pgk->id,
                    'source_reference' => $pgk->ticket_number,
                    'request_type' => 'additional_material',
                    'user_id' => auth()->id(),
                    'customer_id' => $pgk->customer_id,
                    'area_id' => $areaId,
                ]);

                $extraTotalCost = 0;
                foreach ($extraItems as $extra) {
                    $matId = $extra['material_id'];
                    $extraQty = (float) $extra['quantity'];
                    $material = Material::find($matId);
                    if (!$material) continue;

                    $unit = $extra['unit'] ?? $material->unit;
                    $notes = !empty($extra['notes']) ? 'Tambahan: ' . $extra['notes'] : 'Material Tambahan di Lapangan';

                    $stockRes = \App\Services\MaterialService::deductOperationalStock($material, $extraQty, $areaId, $unit);

                    // Catat ke PgkMaterial agar tampil di riwayat dan cetak SPK
                    PgkMaterial::create([
                        'pgk_ticket_id' => $pgk->id,
                        'material_id' => $material->id,
                        'quantity_prepared' => $extraQty,
                        'quantity_used' => $extraQty,
                        'quantity_returned' => 0,
                        'unit' => $unit,
                        'notes' => $notes,
                    ]);

                    $ppu = \App\Services\MaterialService::calculateUnitPrice($material, $unit);
                    $itemTotal = $ppu * $extraQty;
                    $extraTotalCost += $itemTotal;

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $outTrans->id,
                        'material_id' => $material->id,
                        'quantity' => $extraQty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $itemTotal,
                        'stock_before' => $stockRes['stock_before'],
                        'stock_after' => $stockRes['stock_after'],
                    ]);

                    // Sinkronisasi otomatis ke CustomerInstalledMaterial
                    if ($pgk->customer_id) {
                        \App\Models\CustomerInstalledMaterial::create([
                            'customer_id' => $pgk->customer_id,
                            'material_id' => $material->id,
                            'item_name' => $material->name,
                            'quantity' => $extraQty,
                            'unit' => $unit,
                            'installed_at' => now(),
                            'installation_source' => 'pgk_additional',
                            'status' => 'installed',
                            'notes' => "Material tambahan lapangan pada tiket PGK #{$pgk->ticket_number}" . (!empty($extra['notes']) ? ": {$extra['notes']}" : ''),
                        ]);
                    }
                }
                $outTrans->update(['total_cost' => $extraTotalCost]);
            }
        }

        $oldStatus = $pgk->status;

        // 3. Handle Eksekusi Pergantian ONT (Jadi vs Tidak Jadi/Retur Gudang)
        $ontAction = $request->input('ont_replacement_action');
        $preparedOnt = $pgk->prepared_ont_id ? Ont::find($pgk->prepared_ont_id) : null;
        $ontSwapped = false;
        $ontReturned = false;

        if ($preparedOnt && ($pgk->ont_replacement_status === 'prepared' || $ontAction)) {
            if ($ontAction === 'installed') {
                $customer = $pgk->customer;
                $oldOnt = Ont::where('customer_id', $pgk->customer_id)
                    ->where('id', '!=', $preparedOnt->id)
                    ->first() ?: ($pgk->old_ont_id ? Ont::find($pgk->old_ont_id) : null);

                $odpId = $oldOnt?->odp_id;
                $portNum = $oldOnt?->port_number;
                $odpPortId = $oldOnt?->odp_port_id;

                // 1. Lepas SEMUA ONT lama pelanggan dan tandai rusak/ditarik
                Ont::where('customer_id', $pgk->customer_id)
                    ->where('id', '!=', $preparedOnt->id)
                    ->update([
                        'customer_id' => null,
                        'odp_id' => null,
                        'port_number' => null,
                        'odp_port_id' => null,
                        'status' => 'rusak',
                    ]);

                if ($oldOnt && $pgk->ont_replacement_status !== 'installed') {
                    $oldOnt->update([
                        'description' => trim(($oldOnt->description ? $oldOnt->description . ' | ' : '') . 'Diganti dengan SN ' . $preparedOnt->serial_number . ' pada tiket PGK ' . $pgk->ticket_number . ' (' . now()->format('Y-m-d H:i') . ')'),
                    ]);
                }

                // 2. Parse redaman baru & petugas teknisi
                $cleanRx = !empty($request->optical_power_after) ? preg_replace('/[^0-9.\-]/', '', (string)$request->optical_power_after) : null;
                $rxVal = is_numeric($cleanRx) ? (float)$cleanRx : $preparedOnt->rx_power;

                $officers = is_array($preparedOnt->input_officers) ? $preparedOnt->input_officers : [];
                $techName = auth()->user()?->name;
                if ($techName && !in_array($techName, $officers)) {
                    $officers[] = $techName;
                }

                // 3. Pasangkan ONT baru ke pelanggan & sinkronkan data konfigurasi aktivasi
                $preparedOnt->update([
                    'customer_id' => $pgk->customer_id,
                    'area_id' => $customer?->area_id ?? $preparedOnt->area_id,
                    'odp_id' => $odpId ?? $preparedOnt->odp_id,
                    'port_number' => $portNum ?? $preparedOnt->port_number,
                    'odp_port_id' => $odpPortId ?? $preparedOnt->odp_port_id,
                    'status' => 'active',
                    'pppoe_user' => $oldOnt?->pppoe_user ?: $preparedOnt->pppoe_user,
                    'pppoe_password' => $oldOnt?->pppoe_password ?: $preparedOnt->pppoe_password,
                    'access_mode' => $oldOnt?->access_mode ?: ($preparedOnt->access_mode ?? 'PPPOE'),
                    'vlan_id' => $oldOnt?->vlan_id ?: $preparedOnt->vlan_id,
                    'vlan_mode' => $oldOnt?->vlan_mode ?: $preparedOnt->vlan_mode,
                    'ip_login' => $preparedOnt->ip_login ?: ($oldOnt?->ip_login ?? '192.168.1.1'),
                    'login_user' => $preparedOnt->login_user ?: ($oldOnt?->login_user ?? 'admin'),
                    'login_password' => $preparedOnt->login_password ?: ($oldOnt?->login_password ?? 'admin'),
                    'free_hotspot' => $oldOnt?->free_hotspot ?? ($preparedOnt->free_hotspot ?? false),
                    'hotspot_user' => $oldOnt?->hotspot_user ?: $preparedOnt->hotspot_user,
                    'hotspot_password' => $oldOnt?->hotspot_password ?: $preparedOnt->hotspot_password,
                    'hotspot_vlan_id' => $oldOnt?->hotspot_vlan_id ?: $preparedOnt->hotspot_vlan_id,
                    'rx_power' => $rxVal,
                    'initial_rx_power' => $preparedOnt->initial_rx_power ?: $rxVal,
                    'last_rx_updated_at' => now(),
                    'last_rx_ticket_ref' => $pgk->ticket_number,
                    'last_rx_notes' => 'Diukur saat pergantian ONT tiket PGK ' . $pgk->ticket_number,
                    'input_officers' => $officers,
                    'description' => trim(($preparedOnt->description ? $preparedOnt->description . ' | ' : '') . 'Menggantikan SN ' . ($oldOnt?->serial_number ?? '-') . ' pada tiket PGK ' . $pgk->ticket_number),
                ]);

                // 4. Update riwayat material terpasang pelanggan (CustomerInstalledMaterial) & Log Aktivasi
                if ($customer) {
                    \App\Models\CustomerInstalledMaterial::where('customer_id', $customer->id)
                        ->where(function($q) use ($oldOnt) {
                            $q->where('item_name', 'like', '%ont%')
                              ->orWhere('item_name', 'like', '%modem%');
                            if ($oldOnt && $oldOnt->serial_number) {
                                $q->orWhere('serial_number', $oldOnt->serial_number);
                            }
                        })
                        ->where('status', 'installed')
                        ->update([
                            'status' => 'fully_replaced',
                            'notes' => 'Diganti seluruhnya dengan SN ' . $preparedOnt->serial_number . ' pada tiket PGK #' . $pgk->ticket_number,
                        ]);

                    $ontMat = \App\Services\MaterialService::resolveOntMaterial($preparedOnt);
                    \App\Models\CustomerInstalledMaterial::create([
                        'customer_id' => $customer->id,
                        'material_id' => $ontMat?->id,
                        'item_name' => $preparedOnt->brand ?: ($ontMat?->name ?? 'ONT Baru'),
                        'quantity' => 1,
                        'unit' => 'unit',
                        'serial_number' => $preparedOnt->serial_number,
                        'installed_at' => now(),
                        'installation_source' => 'pgk_replacement',
                        'status' => 'installed',
                        'notes' => 'Unit ONT baru hasil pergantian tiket PGK #' . $pgk->ticket_number,
                    ]);

                    $customer->touch();
                    \App\Models\AuditLog::createLog(
                        'Pergantian ONT PGK',
                        $customer,
                        $customer->status,
                        $customer->status,
                        "Pergantian unit ONT berhasil dipasang melalui tiket PGK {$pgk->ticket_number}. ONT baru: {$preparedOnt->brand} {$preparedOnt->model} (SN: {$preparedOnt->serial_number}) menggantikan ONT lama (SN: " . ($oldOnt?->serial_number ?? '-') . ")."
                    );
                }

                // 5. Sinkronkan akun pelanggan ke RADIUS Mikrotik jika aktif
                try {
                    if ($customer) {
                        app(\App\Services\RadiusService::class)->syncCustomer($customer);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[RADIUS] ONT replacement PGK sync error: ' . $e->getMessage());
                }

                // Pastikan stok ONT pengganti sudah terpotong (fallback jika ONT disiapkan sebelum update sistem 2-tahap)
                $hasOntOutTx = MaterialTransaction::where('source_type', 'pgk')
                    ->where('source_id', $pgk->id)
                    ->where('type', 'out')
                    ->where(function ($q) {
                        $q->where('request_type', 'ont_replacement')
                          ->orWhere('transaction_number', 'like', 'OUT-PGK-ONT-%');
                    })
                    ->exists();
                if (!$hasOntOutTx) {
                    $ontMat = \App\Services\MaterialService::resolveOntMaterial($preparedOnt);
                    if ($ontMat) {
                        $ontAreaId = $preparedOnt->area_id ?: $areaId;
                        $ontStockRes = \App\Services\MaterialService::deductOperationalStock($ontMat, 1, $ontAreaId, 'pcs');
                        $ontPpu = (float) ($ontMat->selling_price ?: ($ontMat->price_per_unit ?? 0));
                        $ontOutTx = MaterialTransaction::create([
                            'transaction_number' => 'OUT-PGK-ONT-' . date('YmdHis') . '-' . $pgk->id,
                            'type' => 'out',
                            'status' => 'approved',
                            'request_type' => 'ont_replacement',
                            'date' => now(),
                            'technician_name' => auth()->user()->name,
                            'purpose' => 'Pergantian ONT PGK: ' . $pgk->ticket_number . ' (SN: ' . $preparedOnt->serial_number . ')',
                            'source_type' => 'pgk',
                            'source_id' => $pgk->id,
                            'source_reference' => $pgk->ticket_number,
                            'replacement_ont_id' => $preparedOnt->id,
                            'old_ont_id' => $oldOnt?->id,
                            'user_id' => auth()->id(),
                            'customer_id' => $pgk->customer_id,
                            'area_id' => $ontAreaId,
                            'total_cost' => $ontPpu,
                        ]);
                        MaterialTransactionItem::create([
                            'material_transaction_id' => $ontOutTx->id,
                            'material_id' => $ontMat->id,
                            'quantity' => 1,
                            'unit' => 'pcs',
                            'price_per_unit' => $ontPpu,
                            'total_price' => $ontPpu,
                            'stock_before' => $ontStockRes['stock_before'],
                            'stock_after' => $ontStockRes['stock_after'],
                            'condition' => 'Terpasang/Digunakan',
                        ]);
                    }
                }

                $pgk->update([
                    'ont_replacement_status' => 'installed',
                    'ont_replacement_action' => 'installed',
                    'old_ont_id' => $oldOnt?->id,
                    'ont_replacement_notes' => $request->ont_replacement_notes ?? ('ONT berhasil diganti dengan unit baru SN: ' . $preparedOnt->serial_number),
                ]);
                $ontSwapped = true;
            } elseif ($ontAction === 'cancelled') {
                // Pergantian TIDAK JADI -> Kembalikan stok & unit ONT baru ke Inventory Gudang
                $this->restorePreparedOntStock($pgk);
                $preparedOnt->update([
                    'customer_id' => null,
                    'status' => 'Sudah Set',
                ]);

                $pgk->update([
                    'prepared_ont_id' => null,
                    'ont_replacement_status' => 'cancelled',
                    'ont_replacement_action' => 'cancelled',
                    'ont_replacement_notes' => $request->ont_replacement_notes ?? 'Pergantian ONT tidak jadi saat penyelesaian, unit baru dikembalikan ke Inventory Gudang.',
                ]);
                $ontReturned = true;
            }
        }

        // 4. Handle Pergantian Material Terpasang di Pelanggan (Seluruhnya vs Sebagian)
        $replacementCount = 0;
        if (!empty($request->material_replacements) && is_array($request->material_replacements)) {
            $replacedOntId = $oldOnt?->id ?: $pgk->old_ont_id;
            foreach ($request->material_replacements as $rep) {
                if (!empty($rep['item_name']) && (float) ($rep['quantity'] ?? 0) > 0) {
                    if ($replacedOntId && empty($rep['ont_id']) && (stripos($rep['item_name'], 'ont') !== false || stripos($rep['item_name'], 'modem') !== false)) {
                        $rep['ont_id'] = $replacedOntId;
                    }
                    \App\Services\CustomerMaterialService::processMaterialReplacement(
                        ticketType: 'pgk',
                        ticketId: $pgk->id,
                        ticketNumber: $pgk->ticket_number,
                        customer: $pgk->customer,
                        technician: auth()->user(),
                        replacementData: $rep
                    );
                    $replacementCount++;
                }
            }
        }

        // 5. Handle Update Kredensial Login Internet Pelanggan (PPPoE)
        if ($request->boolean('update_pppoe') || !empty($request->new_pppoe_user) || !empty($request->new_pppoe_password)) {
            $customer = $pgk->customer ?? Customer::find($pgk->customer_id);
            $targetOnt = $ontSwapped ? $preparedOnt : ($customer?->ont ?? Ont::where('customer_id', $pgk->customer_id)->first());
            $newPppoeUser = trim((string)($request->new_pppoe_user ?? ''));
            $newPppoePass = (string)($request->new_pppoe_password ?? '');

            if (!empty($newPppoeUser) && $targetOnt) {
                $dup = \App\Http\Controllers\OntController::findPppoeDuplicate($newPppoeUser, $targetOnt->id);
                if ($dup) {
                    $detail = !empty($dup->customer) ? " (digunakan oleh pelanggan: {$dup->customer->name} / {$dup->customer->customer_code})" : " (digunakan oleh ONT SN: {$dup->serial_number})";
                    return redirect()->back()->withErrors([
                        'new_pppoe_user' => "Akun login internet / PPPoE '{$newPppoeUser}' sudah digunakan{$detail}. Sistem otomatis menolak duplikasi!"
                    ])->with('error', "Username PPPoE '{$newPppoeUser}' sudah terdaftar{$detail}.");
                }
            }

            $oldPppoeUser = $targetOnt?->pppoe_user ?: $customer?->pppoe_username;
            $ontUpdates = [];
            $custUpdates = [];
            if (!empty($newPppoeUser)) {
                $ontUpdates['pppoe_user'] = $newPppoeUser;
                $custUpdates['pppoe_username'] = $newPppoeUser;
            }
            if (!empty($newPppoePass)) {
                $ontUpdates['pppoe_password'] = $newPppoePass;
                $custUpdates['pppoe_password'] = $newPppoePass;
            }

            if (!empty($ontUpdates) && $targetOnt) {
                $targetOnt->update($ontUpdates);
            }
            if (!empty($custUpdates) && $customer) {
                $customer->update($custUpdates);
            }

            if ($customer && (!empty($newPppoeUser) || !empty($newPppoePass))) {
                try {
                    app(\App\Services\RadiusService::class)->syncCustomer($customer, array_filter([$oldPppoeUser]));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[RADIUS] PGK submitReport PPPoE sync error: ' . $e->getMessage());
                }

                AuditLog::createLog(
                    'Update PPPoE Pasca Laporan PGK',
                    $targetOnt ?? $customer,
                    $oldPppoeUser,
                    $newPppoeUser ?: $oldPppoeUser,
                    'Login internet (PPPoE) diperbarui melalui laporan tiket PGK #' . $pgk->ticket_number . ' oleh ' . (auth()->user()?->name ?? 'Teknisi')
                );
            }
        }

        // 5B. Handle Update Nama & Password WiFi Pelanggan (SSID / WPA2)
        if ($request->boolean('update_wifi') || !empty($request->new_wifi_ssid) || !empty($request->new_wifi_password)) {
            $customer = $pgk->customer ?? Customer::find($pgk->customer_id);
            $targetOnt = $ontSwapped ? $preparedOnt : ($customer?->ont ?? Ont::where('customer_id', $pgk->customer_id)->first());
            $newWifiSsid = trim((string)($request->new_wifi_ssid ?? ''));
            $newWifiPass = (string)($request->new_wifi_password ?? '');

            $wifiOntUpdates = [];
            $wifiCustUpdates = [];
            if (!empty($newWifiSsid)) {
                $wifiOntUpdates['wifi_ssid'] = $newWifiSsid;
                $wifiCustUpdates['wifi_ssid'] = $newWifiSsid;
            }
            if (!empty($newWifiPass)) {
                $wifiOntUpdates['wifi_password'] = $newWifiPass;
                $wifiCustUpdates['wifi_password'] = $newWifiPass;
            }

            if (!empty($wifiOntUpdates) && $targetOnt) {
                $targetOnt->update($wifiOntUpdates);
            }
            if (!empty($wifiCustUpdates) && $customer) {
                $customer->update($wifiCustUpdates);
            }

            if (!empty($newWifiSsid) || !empty($newWifiPass)) {
                AuditLog::createLog(
                    'Update WiFi Pasca Laporan PGK',
                    $targetOnt ?? $customer,
                    $targetOnt?->wifi_ssid ?: '-',
                    $newWifiSsid ?: ($targetOnt?->wifi_ssid ?: '-'),
                    'WiFi pelanggan diperbarui melalui laporan tiket PGK #' . $pgk->ticket_number . ' oleh ' . (auth()->user()?->name ?? 'Teknisi')
                );
            }
        }

        $pgk->update([
            'status' => 'completed',
            'action_taken' => $validated['action_taken'],
            'optical_power_before' => $validated['optical_power_before'] ?? null,
            'optical_power_after' => $validated['optical_power_after'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'photo_before' => $photoBeforePath,
            'photo_after' => $photoAfterPath,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'incident_coordinates' => $validated['incident_coordinates'] ?? $pgk->incident_coordinates,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'Laporan Penanganan PGK Selesai',
            $pgk,
            $oldStatus,
            'completed',
            'Teknisi melaporkan penanganan komplain selesai: ' . $validated['action_taken']
        );

        $updatedRxPower = null;
        if (!empty($validated['optical_power_after'])) {
            $parsedRx = \App\Services\DataMigrationService::parseRxPower($validated['optical_power_after']);
            if ($parsedRx !== null) {
                $targetOnt = $ontSwapped ? $preparedOnt : ($pgk->customer?->ont ?? Ont::where('customer_id', $pgk->customer_id)->first());
                if ($targetOnt) {
                    $ontUpdate = [
                        'rx_power' => $parsedRx,
                    ];

                    if (Schema::hasColumn('onts', 'last_rx_updated_at')) {
                        $ontUpdate['last_rx_updated_at'] = now();
                    }
                    if (Schema::hasColumn('onts', 'last_rx_ticket_ref')) {
                        $ontUpdate['last_rx_ticket_ref'] = $pgk->ticket_number;
                    }
                    if (Schema::hasColumn('onts', 'last_rx_notes')) {
                        $ontUpdate['last_rx_notes'] = 'Diperbarui pasca penanganan tiket ' . $pgk->ticket_number . ' (' . ($validated['action_taken'] ?? 'Selesai') . ')';
                    }

                    // Pertahankan data acuan awal saat instalasi jika belum pernah terisi
                    if (Schema::hasColumn('onts', 'initial_rx_power') && empty($targetOnt->initial_rx_power) && !empty($targetOnt->rx_power)) {
                        $ontUpdate['initial_rx_power'] = $targetOnt->rx_power;
                    }

                    // Sinkronkan juga foto redaman jika ada foto sesudah yang diunggah
                    if (!empty($photoAfterPath)) {
                        $ontUpdate['photo_redaman'] = $photoAfterPath;
                    }

                    $targetOnt->update($ontUpdate);
                    $updatedRxPower = number_format($parsedRx, 2, '.', '') . ' dBm';

                    AuditLog::createLog(
                        'Update Redaman Pasca Perbaikan PGK',
                        $targetOnt,
                        $validated['optical_power_before'] ?? ($targetOnt->initial_rx_power ? $targetOnt->initial_rx_power . ' dBm' : null),
                        $updatedRxPower,
                        'Redaman optik pelanggan diupdate menjadi ' . $updatedRxPower . ' pasca penanganan tiket PGK ' . $pgk->ticket_number
                    );
                }
            }
        }

        $retCount = PgkMaterial::where('pgk_ticket_id', $pgk->id)->where('quantity_returned', '>', 0)->count();
        $msg = 'Laporan penanganan komplain berhasil disimpan. Status tiket PGK telah selesai.';
        if ($ontSwapped) {
            $msg .= ' Unit ONT baru berhasil dipasangkan ke pelanggan.';
        } elseif ($ontReturned) {
            $msg .= ' Unit ONT baru telah dikembalikan ke Inventory Gudang.';
        }
        if ($retCount > 0) {
            $msg .= ' Sebanyak ' . $retCount . ' jenis kelebihan material otomatis dikembalikan ke stok gudang (Transaksi IN-PGK-RET).';
        }
        if ($replacementCount > 0) {
            $msg .= ' Sebanyak ' . $replacementCount . ' jenis material bekas bongkaran telah dicatat dan masuk antrean serah terima ke Gudang / Ka. Teknisi.';
        }
        if ($updatedRxPower) {
            $msg .= ' Nilai redaman baru (' . $updatedRxPower . ') otomatis tersinkronisasi ke data perangkat & instalasi pelanggan.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Teknisi Lapangan mengajukan permintaan tambahan material ke Kepala Teknisi
     */
    public function requestMaterial(Request $request, PgkTicket $pgk)
    {
        // Mendukung multiple items dalam satu pengajuan pengadaan teknisi
        if ($request->has('items') && is_array($request->items)) {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.item_name' => 'required|string|max:255',
                'items.*.material_id' => 'nullable|exists:materials,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'required|string|max:50',
                'notes' => 'required|string|max:500', // Alasan kebutuhan darurat di lapangan
            ]);

            $createdNames = [];
            foreach ($validated['items'] as $item) {
                $itemName = trim($item['item_name'] ?? '');
                $materialId = $item['material_id'] ?? null;
                if ($materialId && !$itemName) {
                    $mat = Material::find($materialId);
                    $itemName = $mat?->name;
                }
                if (!$itemName) continue;

                PgkMaterialRequest::create([
                    'pgk_ticket_id' => $pgk->id,
                    'item_name' => $itemName,
                    'material_id' => $materialId,
                    'requested_by' => auth()->id(),
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'notes' => $validated['notes'],
                    'status' => 'pending',
                ]);

                $createdNames[] = $item['quantity'] . ' ' . $item['unit'] . ' (' . $itemName . ')';
            }

            if (count($createdNames) > 0) {
                AuditLog::createLog(
                    'Permintaan Tambahan Material PGK',
                    $pgk,
                    'pending',
                    'pending',
                    'Teknisi ' . auth()->user()->name . ' mengajukan ' . count($createdNames) . ' item material: ' . implode(', ', $createdNames) . '. Alasan: ' . $validated['notes']
                );
            }

            return redirect()->back()->with('success', count($createdNames) . ' item permintaan material berhasil diajukan ke Kepala Teknisi.');
        }

        $validated = $request->validate([
            'item_name' => 'required_without:material_id|nullable|string|max:255',
            'material_id' => 'nullable|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'notes' => 'required|string|max:500', // Alasan kebutuhan darurat di lapangan
        ]);

        $materialId = $validated['material_id'] ?? null;
        $itemName = $validated['item_name'] ?? null;
        
        if ($materialId && !$itemName) {
            $mat = Material::find($materialId);
            $itemName = $mat?->name;
        }

        PgkMaterialRequest::create([
            'pgk_ticket_id' => $pgk->id,
            'item_name' => trim($itemName ?: 'Material Tambahan'),
            'material_id' => $materialId,
            'requested_by' => auth()->id(),
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        AuditLog::createLog(
            'Permintaan Tambahan Material PGK',
            $pgk,
            'pending',
            'pending',
            'Teknisi ' . auth()->user()->name . ' mengajukan permintaan tambahan: ' . $validated['quantity'] . ' ' . $validated['unit'] . ' (' . ($itemName ?: 'Material') . '). Alasan: ' . $validated['notes']
        );

        return redirect()->back()->with('success', 'Permintaan material berhasil diajukan ke Kepala Teknisi.');
    }

    /**
     * Kepala Teknisi menyetujui permintaan tambahan material dari teknisi lapangan
     */
    public function approveMaterialRequest(Request $request, PgkTicket $pgk, PgkMaterialRequest $materialRequest)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($pgk, $materialRequest, $validated) {
            $lockedReq = PgkMaterialRequest::where('id', $materialRequest->id)->lockForUpdate()->first();
            if (!$lockedReq || $lockedReq->status !== 'pending') {
                return redirect()->back()->with('error', 'Permintaan material ini sudah diproses sebelumnya.');
            }
            $materialRequest = $lockedReq;

            $material = Material::findOrFail($validated['material_id']);
            $qty = !empty($validated['quantity']) ? (float) $validated['quantity'] : (float) $materialRequest->quantity;
            $areaId = $pgk->area_id ?? $pgk->customer?->area_id;
            $unit = $materialRequest->unit ?: ($material->unit ?? 'pcs');

            $stockRes = \App\Services\MaterialService::deductOperationalStock($material, $qty, $areaId, $unit);

            $ppu = \App\Services\MaterialService::calculateUnitPrice($material, $unit);
            $itemTotal = $ppu * $qty;

            // Catat transaksi pengeluaran barang
            $trxNumber = \App\Services\MaterialService::generateTransactionNumber('OUT-PGK-REQ');
            $outTrans = MaterialTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'out',
                'status' => 'approved',
                'request_type' => 'additional_material',
                'date' => now(),
                'technician_name' => $materialRequest->requester?->name ?: auth()->user()->name,
                'purpose' => 'Persetujuan Material Tambahan PGK: ' . $pgk->ticket_number . ' (' . ($pgk->customer?->name ?? '') . ')',
                'source_type' => 'pgk',
                'source_id' => $pgk->id,
                'source_reference' => $pgk->ticket_number,
                'user_id' => auth()->id(),
                'customer_id' => $pgk->customer_id,
                'area_id' => $areaId,
                'total_cost' => $itemTotal,
                'notes' => 'Disetujui Ka. Teknisi: ' . $material->name . ' (' . $qty . ' ' . $unit . '). Kebutuhan teknisi: ' . ($materialRequest->item_name ?? '-') . ' - ' . ($materialRequest->notes ?? '-'),
            ]);

            MaterialTransactionItem::create([
                'material_transaction_id' => $outTrans->id,
                'material_id' => $material->id,
                'quantity' => $qty,
                'unit' => $unit,
                'price_per_unit' => $ppu,
                'total_price' => $itemTotal,
                'stock_before' => $stockRes['stock_before'],
                'stock_after' => $stockRes['stock_after'],
            ]);

            // Tambahkan ke pgk_materials (jika sudah ada, tambahkan quantity_prepared, jika belum buat baru)
            $existingPgkMat = PgkMaterial::where('pgk_ticket_id', $pgk->id)
                ->where('material_id', $material->id)
                ->first();

            if ($existingPgkMat) {
                $newPrep = (float) $existingPgkMat->quantity_prepared + $qty;
                $existingPgkMat->update([
                    'quantity_prepared' => $newPrep,
                    'quantity_used' => (float) $existingPgkMat->quantity_used + $qty,
                    'notes' => trim(($existingPgkMat->notes ?? '') . ' | Tambahan disetujui: +' . $qty . ' ' . $unit),
                ]);
            } else {
                PgkMaterial::create([
                    'pgk_ticket_id' => $pgk->id,
                    'material_id' => $material->id,
                    'quantity_prepared' => $qty,
                    'quantity_used' => $qty,
                    'quantity_returned' => 0,
                    'unit' => $unit,
                    'notes' => 'Tambahan disetujui Ka. Teknisi (Kebutuhan: ' . ($materialRequest->item_name ?: $material->name) . '): ' . ($materialRequest->notes ?? '-'),
                ]);
            }

            $materialRequest->update([
                'material_id' => $material->id,
                'quantity' => $qty,
                'unit' => $unit,
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            AuditLog::createLog(
                'Setujui Permintaan Material PGK',
                $pgk,
                'pending',
                'approved',
                'Kepala Teknisi menyetujui tambahan ' . $qty . ' ' . $unit . ' ' . $material->name . ' untuk teknisi ' . ($materialRequest->requester?->name ?? '-')
            );

            return redirect()->back()->with('success', 'Permintaan tambahan material disetujui dan ditambahkan ke penugasan teknisi.');
        });
    }

    /**
     * Kepala Teknisi menolak permintaan tambahan material dari teknisi lapangan
     */
    public function rejectMaterialRequest(Request $request, PgkTicket $pgk, PgkMaterialRequest $materialRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $materialRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        AuditLog::createLog(
            'Tolak Permintaan Material PGK',
            $pgk,
            'pending',
            'rejected',
            'Kepala Teknisi menolak permintaan material: ' . $validated['rejection_reason']
        );

        return redirect()->back()->with('success', 'Permintaan tambahan material telah ditolak.');
    }

    /**
     * Cetak Surat Perintah Kerja (SPK) & Berita Acara PGK
     */
    public function print(PgkTicket $pgk)
    {
        $pgk->load([
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'area',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser',
            'dismantledMaterials.material'
        ]);

        return view('print.pgk-report', [
            'ticket' => $pgk,
        ]);
    }

    /**
     * Hapus permanen tiket PGK
     */
    public function destroy(Request $request, PgkTicket $pgk)
    {
        $user = $request->user();
        if ($user && !$user->can('pgk_btn_delete') && !$user->hasRole('admin') && !$user->hasRole('kepala teknisi')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus laporan penanganan PGK.');
        }

        $num = $pgk->ticket_number;

        DB::transaction(function () use ($pgk) {
            // Hapus file foto dari disk storage jika ada
            if ($pgk->photo_before && Storage::disk('public')->exists($pgk->photo_before)) {
                Storage::disk('public')->delete($pgk->photo_before);
            }
            if ($pgk->photo_after && Storage::disk('public')->exists($pgk->photo_after)) {
                Storage::disk('public')->delete($pgk->photo_after);
            }

            $this->cleanupAndRestoreTicketStock($pgk);
        });

        AuditLog::createLog(
            'Hapus Tiket PGK',
            PgkTicket::class,
            null,
            null,
            'Tiket PGK ' . $num . ' dihapus oleh user: ' . (auth()->user()?->name ?? 'System') . ' (Stok material dikembalikan ke gudang)'
        );

        return redirect()->back()->with('success', 'Tiket PGK ' . $num . ' berhasil dihapus dan seluruh material telah dikembalikan ke stok gudang.');
    }

    /**
     * Hapus massal (Bulk Destroy) tiket PGK
     */
    public function bulkDestroy(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('pgk_btn_delete_all') && !$user->hasRole('admin') && !$user->hasRole('kepala teknisi')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus massal laporan penanganan PGK.');
        }

        $query = PgkTicket::query();
        DataScopeService::applyPgkScope($query);

        if ($request->boolean('delete_all')) {
            $query->whereIn('status', ['scheduled', 'in_progress', 'completed']);

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                      ->orWhere('action_taken', 'like', "%{$search}%")
                      ->orWhere('complainant_name', 'like', "%{$search}%")
                      ->orWhere('reported_issue', 'like', "%{$search}%")
                      ->orWhere('admin_notes', 'like', "%{$search}%")
                      ->orWhere('resolution_notes', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                             ->orWhere('customer_code', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('area_id') && $request->area_id !== 'all') {
                $query->where('area_id', $request->area_id);
            }

            $tickets = $query->get();
        } else {
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer'
            ]);
            $tickets = $query->whereIn('id', $request->ids)->get();
        }

        $count = 0;
        DB::transaction(function () use ($tickets, &$count) {
            foreach ($tickets as $ticket) {
                if ($ticket->photo_before && Storage::disk('public')->exists($ticket->photo_before)) {
                    Storage::disk('public')->delete($ticket->photo_before);
                }
                if ($ticket->photo_after && Storage::disk('public')->exists($ticket->photo_after)) {
                    Storage::disk('public')->delete($ticket->photo_after);
                }

                $this->cleanupAndRestoreTicketStock($ticket);
                $count++;
            }
        });

        AuditLog::createLog(
            'Hapus Massal Tiket PGK',
            PgkTicket::class,
            null,
            null,
            'Sebanyak ' . $count . ' tiket PGK dihapus massal oleh: ' . (auth()->user()?->name ?? 'System') . ' (Stok material dikembalikan ke gudang)'
        );

        return redirect()->back()->with('success', "Sebanyak {$count} data laporan PGK berhasil dihapus permanen dan seluruh material terkait dikembalikan ke gudang.");
    }

    /**
     * Pulihkan stok unit ONT yang disiapkan untuk tiket PGK (jika dibatalkan atau diganti unit lain)
     */
    protected function restorePreparedOntStock(PgkTicket $pgk): void
    {
        $areaId = $pgk->area_id ?? $pgk->customer?->area_id;
        $ontOutTxs = MaterialTransaction::with('items.material')
            ->where('source_type', 'pgk')
            ->where('source_id', $pgk->id)
            ->where('type', 'out')
            ->where(function ($q) {
                $q->where('request_type', 'ont_replacement')
                  ->orWhere('transaction_number', 'like', 'OUT-PGK-ONT-%');
            })
            ->get();

        foreach ($ontOutTxs as $tx) {
            $targetAreaId = $tx->area_id ?: $areaId;
            if ($tx->status === 'approved' && !$tx->is_migration) {
                foreach ($tx->items as $item) {
                    if ($item->material && (float) $item->quantity > 0) {
                        \App\Services\MaterialService::restoreOperationalStock(
                            $item->material,
                            (float) $item->quantity,
                            $targetAreaId,
                            $item->unit
                        );
                    }
                }
            }
            $tx->skipStockRestoreOnDelete = true;
            $tx->items()->delete();
            $tx->delete();
        }
    }

    /**
     * Bersihkan data tiket dan kembalikan stok material ke gudang jika tiket dihapus
     */
    protected function cleanupAndRestoreTicketStock(PgkTicket $pgk): void
    {
        $areaId = $pgk->area_id ?? $pgk->customer?->area_id;
        $restoredByMat = [];

        // 1. Kembalikan seluruh material pengeluaran (OUT) & balikkan retur (IN) yang sudah diterima secara simetris
        try {
            $restoredByMat = \App\Services\MaterialHandoverService::restoreDispatchedMaterials(
                'pgk',
                $pgk->id,
                $areaId,
                $pgk->ticket_number
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[PgkController::cleanup] Gagal restore material handover: ' . $e->getMessage());
        }

        // Fallback: Hanya jika ada material di pgk_materials lama yang belum punya transaksi OUT sama sekali
        foreach ($pgk->materials as $pgkMat) {
            $matId = (int) $pgkMat->material_id;
            $qty = (float) $pgkMat->quantity_prepared;
            if ($qty > 0 && $matId && empty($restoredByMat[$matId])) {
                $material = Material::find($matId);
                if ($material) {
                    \App\Services\MaterialService::restoreOperationalStock($material, $qty, $areaId, $pgkMat->unit);
                }
            }
        }

        // 2. Kembalikan unit ONT yang disiapkan ke status bebas di gudang jika belum dipasang permanen
        if ($pgk->prepared_ont_id) {
            $prepOnt = Ont::find($pgk->prepared_ont_id);
            if ($prepOnt && ($pgk->status !== 'completed' || $prepOnt->customer_id == $pgk->customer_id)) {
                $prepOnt->update([
                    'customer_id' => null,
                    'status' => 'Sudah Set',
                ]);
            }
        }

        // 3. Hapus data serah terima bongkaran
        \App\Models\CustomerMaterialDismantle::where('ticket_type', 'pgk')
            ->where(function ($q) use ($pgk) {
                $q->where('ticket_id', $pgk->id)
                  ->orWhere('ticket_number', $pgk->ticket_number);
            })
            ->delete();

        // 4. Hapus relasi teknisi, material, dan permintaan tambahan
        $pgk->technicians()->detach();
        $pgk->materials()->delete();
        $pgk->materialRequests()->delete();
        $pgk->delete();
    }

    /**
     * Teknisi Lapangan mengajukan permintaan pergantian ONT ke Kepala Teknisi
     */
    public function requestOnt(Request $request, PgkTicket $pgk)
    {
        $validated = $request->validate([
            'requested_ont_notes' => 'required|string|max:1000',
        ]);

        $oldOntId = $pgk->customer?->ont?->id;

        $pgk->update([
            'ont_replacement_status' => 'requested',
            'requested_ont_notes' => $validated['requested_ont_notes'],
            'requested_ont_at' => now(),
            'requested_by' => auth()->id(),
            'old_ont_id' => $oldOntId,
        ]);

        AuditLog::createLog(
            'Request Pergantian ONT PGK',
            $pgk,
            'none',
            'requested',
            'Teknisi mengajukan pergantian unit ONT komplain: ' . $validated['requested_ont_notes']
        );

        return redirect()->back()->with('success', 'Permintaan pergantian ONT berhasil diajukan ke Kepala Teknisi.');
    }

    /**
     * Kepala Teknisi menerima permintaan & menyiapkan ONT baru dari inventory gudang
     */
    public function prepareOnt(Request $request, PgkTicket $pgk)
    {
        $user = $request->user();
        $canPrepare = $user && (
            $user->can('pgk_btn_assign') ||
            $user->can('pgk_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher', 'maintenance'])
        );
        if (!$canPrepare) {
            abort(403, 'Hanya Kepala Teknisi atau Admin yang dapat menyiapkan ONT baru.');
        }

        $validated = $request->validate([
            'prepared_ont_id' => 'required|exists:onts,id',
        ]);

        return DB::transaction(function () use ($pgk, $validated) {
            $ont = Ont::where('id', $validated['prepared_ont_id'])->lockForUpdate()->firstOrFail();
            if (!empty($ont->customer_id)) {
                return redirect()->back()->with('error', 'ONT tersebut sudah terpasang pada pelanggan lain!');
            }

            // Jika sebelumnya sudah menyiapkan ONT lain pada tiket ini, pulihkan unit & stok sebelumnya terlebih dahulu
            if ($pgk->prepared_ont_id && (int) $pgk->prepared_ont_id !== (int) $ont->id) {
                $prevPrepOnt = Ont::find($pgk->prepared_ont_id);
                if ($prevPrepOnt && empty($prevPrepOnt->customer_id)) {
                    $prevPrepOnt->update(['status' => 'Sudah Set']);
                }
            }
            $this->restorePreparedOntStock($pgk);

            // Pre-fill akun PPPoE & VLAN dari ONT lama jika unit pengganti belum memiliki PPPoE
            $customer = $pgk->customer;
            $oldOnt = Ont::where('customer_id', $pgk->customer_id)->first() ?: ($pgk->old_ont_id ? Ont::find($pgk->old_ont_id) : null);
            $prepAreaId = $ont->area_id ?: ($pgk->area_id ?? $customer?->area_id ?? null);

            $prepUpdates = [
                'status' => 'Disiapkan Teknisi',
                'area_id' => $prepAreaId,
            ];

            if (empty($ont->pppoe_user) && !empty($oldOnt?->pppoe_user)) {
                $prepUpdates['pppoe_user'] = $oldOnt->pppoe_user;
                $prepUpdates['pppoe_password'] = $oldOnt->pppoe_password;
            }
            if (empty($ont->access_mode) && !empty($oldOnt?->access_mode)) {
                $prepUpdates['access_mode'] = $oldOnt->access_mode;
            }
            if (empty($ont->vlan_id) && !empty($oldOnt?->vlan_id)) {
                $prepUpdates['vlan_id'] = $oldOnt->vlan_id;
                $prepUpdates['vlan_mode'] = $oldOnt->vlan_mode;
            }
            if (empty($ont->ip_login) && !empty($oldOnt?->ip_login)) {
                $prepUpdates['ip_login'] = $oldOnt->ip_login;
            }
            if (empty($ont->login_user) && !empty($oldOnt?->login_user)) {
                $prepUpdates['login_user'] = $oldOnt->login_user;
                $prepUpdates['login_password'] = $oldOnt->login_password;
            }
            if (!empty($oldOnt?->free_hotspot) && empty($ont->free_hotspot)) {
                $prepUpdates['free_hotspot'] = $oldOnt->free_hotspot;
                $prepUpdates['hotspot_user'] = $oldOnt->hotspot_user;
                $prepUpdates['hotspot_password'] = $oldOnt->hotspot_password;
                $prepUpdates['hotspot_vlan_id'] = $oldOnt->hotspot_vlan_id;
            }

            // Tandai status ONT di inventory dan update konfigurasi siap pakai
            $ont->update($prepUpdates);

            // Potong stok ONT di gudang area saat disiapkan untuk teknisi (Tahap 1)
            $ontMat = \App\Services\MaterialService::resolveOntMaterial($ont);
            if ($ontMat) {
                $stockRes = \App\Services\MaterialService::deductOperationalStock($ontMat, 1, $prepAreaId, 'pcs');
                $ppu = \App\Services\MaterialService::calculateUnitPrice($ontMat, 'pcs');
                $techNames = $pgk->technicians()->pluck('name')->join(', ') ?: (auth()->user()->name ?? 'Teknisi');

                $trxNumber = \App\Services\MaterialService::generateTransactionNumber('OUT-PGK-ONT');
                $ontOutTx = MaterialTransaction::create([
                    'transaction_number' => $trxNumber,
                    'type' => 'out',
                    'status' => 'approved',
                    'request_type' => 'ont_replacement',
                    'date' => now(),
                    'technician_name' => $techNames,
                    'purpose' => 'Penyiapan Unit ONT Pengganti PGK: ' . $pgk->ticket_number . ' (SN: ' . $ont->serial_number . ')',
                    'source_type' => 'pgk',
                    'source_id' => $pgk->id,
                    'source_reference' => $pgk->ticket_number,
                    'replacement_ont_id' => $ont->id,
                    'old_ont_id' => $oldOnt?->id,
                    'user_id' => auth()->id(),
                    'customer_id' => $pgk->customer_id,
                    'area_id' => $prepAreaId,
                    'total_cost' => $ppu,
                ]);

                MaterialTransactionItem::create([
                    'material_transaction_id' => $ontOutTx->id,
                    'material_id' => $ontMat->id,
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'price_per_unit' => $ppu,
                    'total_price' => $ppu,
                    'stock_before' => $stockRes['stock_before'],
                    'stock_after' => $stockRes['stock_after'],
                    'condition' => 'Disiapkan Teknisi',
                ]);
            }

            $pgk->update([
                'ont_replacement_status' => 'prepared',
                'prepared_ont_id' => $ont->id,
                'prepared_ont_at' => now(),
                'prepared_by' => auth()->id(),
            ]);

            AuditLog::createLog(
                'Kepala Teknisi Menyiapkan ONT Baru PGK',
                $pgk,
                'requested',
                'prepared',
                'Kepala Teknisi menyiapkan unit ONT baru SN: ' . $ont->serial_number . ' (' . ($ont->brand ?? 'ONT') . ')'
            );

            return redirect()->back()->with('success', 'ONT baru berhasil disiapkan dari Inventory Gudang untuk teknisi lapangan.');
        });
    }

    /**
     * Batalkan permintaan atau kembalikan ONT baru yang disiapkan / terpasang ke inventory gudang
     */
    public function cancelOnt(Request $request, PgkTicket $pgk)
    {
        $notes = $request->input('notes', 'Pergantian ONT tidak jadi, unit dikembalikan ke Inventory Gudang.');

        $this->restorePreparedOntStock($pgk);

        $preparedOnt = $pgk->prepared_ont_id ? Ont::find($pgk->prepared_ont_id) : null;
        $oldOnt = $pgk->old_ont_id ? Ont::find($pgk->old_ont_id) : null;

        // Jika prepared_ont_id tidak terisi tapi status installed, cari unit ONT baru yang terpasang di customer
        if (!$preparedOnt && $pgk->customer_id) {
            $preparedOnt = Ont::where('customer_id', $pgk->customer_id)
                ->where('description', 'like', "%{$pgk->ticket_number}%")
                ->first();
        }

        $odpId = null;
        $odpPortId = null;
        $portNum = null;

        // 1. Kembalikan unit ONT baru ke persediaan gudang
        if ($preparedOnt) {
            $odpId = $preparedOnt->odp_id;
            $odpPortId = $preparedOnt->odp_port_id;
            $portNum = $preparedOnt->port_number;

            $preparedOnt->update([
                'customer_id' => null,
                'odp_id' => null,
                'odp_port_id' => null,
                'port_number' => null,
                'status' => 'Sudah Set',
                'description' => trim(($preparedOnt->description ? $preparedOnt->description . ' | ' : '') . 'Batal dipasang pada tiket ' . $pgk->ticket_number . ' (diretur ke gudang)'),
            ]);

            // Hapus rekaman CustomerInstalledMaterial untuk ONT baru
            if ($pgk->customer_id) {
                \App\Models\CustomerInstalledMaterial::where('customer_id', $pgk->customer_id)
                    ->where('serial_number', $preparedOnt->serial_number)
                    ->delete();
            }
        }

        // 2. Pulihkan unit ONT lama pelanggan jika sebelumnya sempat diganti
        if ($oldOnt && $pgk->customer_id) {
            $oldOnt->update([
                'customer_id' => $pgk->customer_id,
                'status' => 'active',
                'odp_id' => $oldOnt->odp_id ?: $odpId,
                'odp_port_id' => $oldOnt->odp_port_id ?: $odpPortId,
                'port_number' => $oldOnt->port_number ?: $portNum,
                'description' => trim(($oldOnt->description ? $oldOnt->description . ' | ' : '') . 'Dipulihkan aktif kembali karena pergantian unit batal pada tiket ' . $pgk->ticket_number),
            ]);

            // Pulihkan status CustomerInstalledMaterial untuk ONT lama
            \App\Models\CustomerInstalledMaterial::where('customer_id', $pgk->customer_id)
                ->where('serial_number', $oldOnt->serial_number)
                ->update([
                    'status' => 'installed',
                    'notes' => 'ONT aktif pelanggan (pembatalan retur pergantian unit)',
                ]);

            if ($pgk->customer) {
                $pgk->customer->touch();
                try {
                    app(\App\Services\RadiusService::class)->syncCustomer($pgk->customer);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[RADIUS] cancelOnt PGK sync error: ' . $e->getMessage());
                }
            }
        }

        $oldStatus = $pgk->ont_replacement_status;
        $pgk->update([
            'prepared_ont_id' => null,
            'ont_replacement_status' => 'cancelled',
            'ont_replacement_action' => 'cancelled',
            'ont_replacement_notes' => $notes,
        ]);

        AuditLog::createLog(
            'Pergantian ONT PGK Dibatalkan & Retur Gudang',
            $pgk,
            $oldStatus,
            'cancelled',
            $notes
        );

        return redirect()->back()->with('success', 'Pergantian ONT dibatalkan. Unit ONT baru telah dikembalikan ke Inventory Gudang dan unit ONT lama pelanggan telah dipulihkan aktif.');
    }

    /**
     * Kepala Teknisi / Admin menolak permintaan pergantian ONT dari teknisi lapangan
     */
    public function rejectOnt(Request $request, PgkTicket $pgk)
    {
        $user = $request->user();
        $canReject = $user && (
            $user->can('pgk_btn_assign') ||
            $user->can('pgk_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher', 'maintenance'])
        );
        if (!$canReject) {
            abort(403, 'Hanya Kepala Teknisi atau Admin yang dapat menolak permintaan ganti ONT.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $this->restorePreparedOntStock($pgk);

        if ($pgk->prepared_ont_id) {
            $preparedOnt = Ont::find($pgk->prepared_ont_id);
            if ($preparedOnt) {
                $preparedOnt->update([
                    'customer_id' => null,
                    'status' => 'Sudah Set',
                ]);
            }
        }

        $oldStatus = $pgk->ont_replacement_status;
        $reasonText = trim($validated['rejection_reason']);

        $pgk->update([
            'prepared_ont_id' => null,
            'ont_replacement_status' => 'rejected',
            'ont_replacement_action' => 'rejected',
            'ont_replacement_notes' => 'Permintaan ganti ONT ditolak: ' . $reasonText,
        ]);

        AuditLog::createLog(
            'Kepala Teknisi Menolak Permintaan Ganti ONT PGK',
            $pgk,
            $oldStatus,
            'rejected',
            'Alasan penolakan: ' . $reasonText
        );

        return redirect()->back()->with('success', 'Permintaan pergantian unit ONT lapangan berhasil ditolak.');
    }

    /**
     * Tambah material terpasang baru di pelanggan
     */
    public function storeInstalledMaterial(Request $request, PgkTicket $pgk)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'material_id' => 'nullable|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $item = \App\Models\CustomerInstalledMaterial::create([
            'customer_id' => $pgk->customer_id,
            'material_id' => $validated['material_id'] ?? null,
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'serial_number' => $validated['serial_number'] ?? null,
            'installed_at' => now(),
            'installation_source' => 'pgk_maintenance',
            'status' => 'installed',
            'notes' => $validated['notes'] ?? ('Ditambahkan saat penanganan PGK #' . $pgk->ticket_number),
        ]);

        return redirect()->back()->with('success', "Material terpasang {$item->item_name} berhasil didaftarkan di pelanggan.");
    }

    /**
     * Konfirmasi serah terima material bongkaran oleh Ka. Teknisi atau Orang Gudang
     */
    public function confirmDismantleHandover(Request $request, PgkTicket $pgk, \App\Models\CustomerMaterialDismantle $dismantle)
    {
        $user = $request->user();
        $isLead = $user->hasRole('kepala teknisi') || $user->can('pgk_manage');
        
        $validated = $request->validate([
            'disposition' => 'required|in:scrap,restocked,reconditioned',
            'notes' => 'nullable|string|max:500',
        ]);

        \App\Services\CustomerMaterialService::confirmHandover(
            dismantle: $dismantle,
            receiver: $user,
            disposition: $validated['disposition'],
            notes: $validated['notes'] ?? null,
            isLeadTechnician: $isLead
        );

        $dispMsg = match ($validated['disposition']) {
            'restocked' => 'Material layak pakai telah diterima dan otomatis ditambahkan kembali ke stok gudang!',
            'scrap' => 'Material rusak/afkir telah diterima dan dicatat sebagai scrap.',
            default => 'Material telah diterima untuk proses servis/rekondisi.',
        };

        return redirect()->back()->with('success', "Serah terima material bongkaran {$dismantle->item_name} berhasil diverifikasi. {$dispMsg}");
    }
}

