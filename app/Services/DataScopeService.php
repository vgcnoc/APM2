<?php

namespace App\Services;

use App\Models\User;
use App\Models\Area;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service cerdas untuk membatasi visibilitas data aplikasi ke dalam 3 Tingkatan Scope:
 * 
 * 1. Tier 3 - 'all'  (All Area / Global Scope):
 *    - Role admin / super admin ATAU memiliki permission {module}_view_all.
 *    - Bebas melihat seluruh data dari semua cabang / wilayah nasional.
 * 
 * 2. Tier 2 - 'area' (Per Area / Branch Scope):
 *    - Memiliki permission {module}_view_area.
 *    - Hanya dapat melihat data pada wilayah kerja yang ditugaskan (area_id utama + accessible_areas).
 * 
 * 3. Tier 1 - 'own'  (Karyawan Sendiri / Own Data Scope):
 *    - Karyawan tanpa permission _view_all dan tanpa _view_area.
 *    - Hanya dapat melihat data miliknya sendiri (mis. sales_id, teknisi yang ditugaskan, pembuat data).
 */
class DataScopeService
{
    /**
     * Tentukan level cakupan data pengguna untuk modul tertentu
     * 
     * @return 'all'|'area'|'own'
     */
    public static function getScopeLevel(?User $user, string $module): string
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return 'own';
        }

        // Admin dan Super Admin selalu memiliki akses global ke seluruh data
        if ($user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin') {
            return 'all';
        }

        // Cek permission _view_all
        $viewAllPerms = [
            "{$module}_view_all",
            'view_all',
        ];

        // Fallback hirarki permission view_all
        if (str_starts_with($module, 'customers_')) {
            $viewAllPerms[] = 'customers_all_view_all';
        }
        if ($module === 'purchase_orders') {
            $viewAllPerms[] = 'material_transactions_view_all';
            $viewAllPerms[] = 'materials_view_all';
        }
        if (in_array($module, ['network_data', 'network_odp', 'network_odc', 'network_olt', 'network_ont', 'topology', 'radar', 'network_map'])) {
            $viewAllPerms[] = 'network_data_view_all';
            $viewAllPerms[] = 'network_olt_view_all';
            $viewAllPerms[] = 'network_odp_view_all';
            $viewAllPerms[] = 'network_odc_view_all';
            $viewAllPerms[] = 'ftth_view_all';
        }

        foreach ($viewAllPerms as $perm) {
            if ($user->can($perm)) {
                return 'all';
            }
        }

        // Cek permission _view_area
        $viewAreaPerms = [
            "{$module}_view_area",
            'view_area',
        ];

        // Fallback hirarki permission view_area
        if (str_starts_with($module, 'customers_')) {
            $viewAreaPerms[] = 'customers_all_view_area';
        }
        if ($module === 'purchase_orders') {
            $viewAreaPerms[] = 'material_transactions_view_area';
            $viewAreaPerms[] = 'materials_view_area';
        }
        if (in_array($module, ['network_data', 'network_odp', 'network_odc', 'network_olt', 'network_ont', 'topology', 'radar', 'network_map'])) {
            $viewAreaPerms[] = 'network_data_view_area';
            $viewAreaPerms[] = 'network_olt_view_area';
            $viewAreaPerms[] = 'network_odp_view_area';
            $viewAreaPerms[] = 'network_odc_view_area';
            $viewAreaPerms[] = 'ftth_view_area';
        }

        foreach ($viewAreaPerms as $perm) {
            if ($user->can($perm)) {
                return 'area';
            }
        }

        // Cek permission _view_own / _view_assigned
        $viewOwnPerms = [
            "{$module}_view_own",
            "{$module}_view_assigned",
            'view_own',
            'view_assigned',
        ];

        if (str_starts_with($module, 'customers_')) {
            $viewOwnPerms[] = 'customers_all_view_own';
        }
        if ($module === 'purchase_orders') {
            $viewOwnPerms[] = 'material_transactions_view_own';
        }

        foreach ($viewOwnPerms as $perm) {
            if ($user->can($perm)) {
                return 'own';
            }
        }

        // Default: Karyawan sendiri (own data) / view_assigned
        return 'own';
    }

    /**
     * Dapatkan koleksi Area yang berhak dilihat oleh user berdasarkan level scope modul
     */
    public static function getScopedAreas(
        ?User $user = null,
        string $module = 'general',
        array $columns = ['id', 'name'],
        ?callable $queryCallback = null
    ): \Illuminate\Database\Eloquent\Collection {
        $user = $user ?? auth()->user();
        if (!$user) {
            $q = Area::orderBy('name');
            if (!empty($columns) && $columns !== ['*']) {
                $q->select($columns);
            }
            if ($queryCallback) {
                $queryCallback($q);
            }
            return $q->get();
        }

        $level = self::getScopeLevel($user, $module);
        $q = Area::orderBy('name');
        if (!empty($columns) && $columns !== ['*']) {
            $q->select($columns);
        }

        if ($level !== 'all') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            if (empty($accessibleAreas)) {
                return Area::whereRaw('1 = 0')->get();
            }
            $q->whereIn('id', $accessibleAreas);
        }

        if ($queryCallback) {
            $queryCallback($q);
        }

        return $q->get();
    }

    /**
     * Dapatkan array nama Area yang berhak dilihat oleh user
     */
    public static function getScopedAreaNames(?User $user = null, string $module = 'general'): array
    {
        return self::getScopedAreas($user, $module, ['id', 'name'])
            ->pluck('name')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Terapkan filter scope pada query Pelanggan
     */
    public static function applyCustomerScope(Builder $query, ?User $user = null, string $module = 'customers_all'): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, $module);

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->where(function ($q) use ($accessibleAreas, $user) {
                $q->whereIn('area_id', $accessibleAreas);
                $areaNames = Area::whereIn('id', $accessibleAreas)->pluck('name')->filter()->toArray();
                if (!empty($areaNames)) {
                    $q->orWhereIn('area', $areaNames);
                }
                // Bebas di semua area/wilayah sesuai penugasan (Booking, Survey, Instalasi):
                $q->orWhere('sales_id', $user->id)
                  ->orWhereHas('technicianSchedules', fn($ts) => $ts->where('technician_id', $user->id))
                  ->orWhereHas('surveys', fn($sq) => $sq->where('surveyor_id', $user->id));
            });
        }

        // Level: 'own' (Karyawan sendiri)
        return $query->where(function ($sub) use ($user, $module) {
            if ($module === 'customers_survey') {
                $sub->whereHas('technicianSchedules', fn($q) => $q->where('technician_id', $user->id)->where('type', 'survey'))
                    ->orWhereHas('surveys', fn($q) => $q->where('surveyor_id', $user->id))
                    ->orWhere('sales_id', $user->id);
            } elseif ($module === 'customers_installed') {
                $sub->whereHas('technicianSchedules', fn($q) => $q->where('technician_id', $user->id)->where('type', 'installation'))
                    ->orWhere('sales_id', $user->id);
            } elseif ($module === 'customers_booking') {
                $sub->where('sales_id', $user->id)
                    ->orWhere('created_by', $user->id);
            } else {
                $sub->where('sales_id', $user->id)
                    ->orWhereHas('technicianSchedules', fn($q) => $q->where('technician_id', $user->id))
                    ->orWhereHas('surveys', fn($q) => $q->where('surveyor_id', $user->id));
            }
        });
    }

    /**
     * Terapkan filter scope pada query Booking Pelanggan Baru
     */
    public static function applyBookingScope(Builder $query, ?User $user = null): Builder
    {
        return self::applyCustomerScope($query, $user, 'customers_booking');
    }

    /**
     * Terapkan filter scope pada query Tiket Gangguan
     */
    public static function applyTicketScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'tickets');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->where(function ($q) use ($accessibleAreas, $user) {
                $q->whereHas('customer', function ($cq) use ($accessibleAreas) {
                    $cq->whereIn('area_id', $accessibleAreas);
                })
                // Bebas di semua area/wilayah sesuai penugasan tiket:
                ->orWhere('assigned_to', $user->id)
                ->orWhereHas('schedules', fn($sq) => $sq->where('technician_id', $user->id));
            });
        }

        // Level: 'own' (Tiket yang ditugaskan ke teknisi bersangkutan)
        return $query->where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
              ->orWhereHas('schedules', fn($sq) => $sq->where('technician_id', $user->id));
        });
    }

    /**
     * Terapkan filter scope pada query Cabut Perangkat (CBP)
     */
    public static function applyCbpScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'cbp');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->where(function ($q) use ($accessibleAreas, $user) {
                $q->whereHas('customer', function ($cq) use ($accessibleAreas) {
                    $cq->whereIn('area_id', $accessibleAreas);
                })
                // Bebas di semua area/wilayah sesuai penugasan CBP:
                ->orWhere('created_by', $user->id)
                ->orWhere('assigned_to', $user->id)
                ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
            });
        }

        // Level: 'own' (Dibuat oleh user, ditugaskan langsung, atau anggota tim teknisi CBP)
        return $query->where(function ($sub) use ($user) {
            $sub->where('created_by', $user->id)
                ->orWhere('assigned_to', $user->id)
                ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
        });
    }

    /**
     * Terapkan filter scope pada query Penanganan Gangguan Jalur (PGJ)
     */
    public static function applyPgjScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'pgj');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->where(function ($q) use ($accessibleAreas, $user) {
                $q->whereIn('area_id', $accessibleAreas)
                  ->orWhereHas('olt', fn($oq) => $oq->whereIn('area_id', $accessibleAreas))
                  ->orWhereHas('odc', fn($odcq) => $odcq->whereIn('area_id', $accessibleAreas))
                  ->orWhereHas('odp', fn($odpq) => $odpq->whereIn('area_id', $accessibleAreas))
                  // Bebas di semua area/wilayah sesuai penugasan PGJ:
                  ->orWhere('created_by', $user->id)
                  ->orWhere('lead_technician_id', $user->id)
                  ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
            });
        }

        // Level: 'own' (Dibuat oleh user, ditugaskan sebagai lead, atau anggota teknisi pelaksana)
        return $query->where(function ($sub) use ($user) {
            $sub->where('created_by', $user->id)
                ->orWhere('lead_technician_id', $user->id)
                ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
        });
    }

    /**
     * Terapkan filter scope pada query Penanganan Sigap (PGS)
     */
    public static function applyPgsScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'pgs');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            $areaNames = Area::whereIn('id', $accessibleAreas)->pluck('name')->filter()->toArray();
            return $query->where(function ($q) use ($accessibleAreas, $areaNames, $user) {
                $q->whereIn('area_id', $accessibleAreas)
                  ->orWhereHas('customer', function ($cq) use ($accessibleAreas, $areaNames) {
                      $cq->whereIn('area_id', $accessibleAreas);
                      if (!empty($areaNames)) {
                          $cq->orWhereIn('area', $areaNames);
                      }
                  })
                  // Bebas di semua area/wilayah sesuai penugasan PGS:
                  ->orWhere('created_by', $user->id)
                  ->orWhere('lead_technician_id', $user->id)
                  ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
            });
        }

        // Level: 'own' (Dibuat oleh user, atau ditugaskan sebagai lead / teknisi pelaksana)
        return $query->where(function ($sub) use ($user) {
            $sub->where('created_by', $user->id)
                ->orWhere('lead_technician_id', $user->id)
                ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
        });
    }

    /**
     * Terapkan filter scope pada query Penanganan Komplain (PGK)
     */
    public static function applyPgkScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'pgk');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            $areaNames = Area::whereIn('id', $accessibleAreas)->pluck('name')->filter()->toArray();
            return $query->where(function ($q) use ($accessibleAreas, $areaNames, $user) {
                $q->whereIn('area_id', $accessibleAreas)
                  ->orWhereHas('customer', function ($cq) use ($accessibleAreas, $areaNames) {
                      $cq->whereIn('area_id', $accessibleAreas);
                      if (!empty($areaNames)) {
                          $cq->orWhereIn('area', $areaNames);
                      }
                  })
                  // Bebas di semua area/wilayah sesuai penugasan PGK:
                  ->orWhere('created_by', $user->id)
                  ->orWhere('lead_technician_id', $user->id)
                  ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
            });
        }

        // Level: 'own' (Dibuat oleh user, atau ditugaskan sebagai lead / teknisi pelaksana)
        return $query->where(function ($sub) use ($user) {
            $sub->where('created_by', $user->id)
                ->orWhere('lead_technician_id', $user->id)
                ->orWhereHas('technicians', fn($tq) => $tq->where('users.id', $user->id));
        });
    }

    /**
     * Terapkan filter scope pada query OLT
     */
    public static function applyOltScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) return $query;
        $level = self::getScopeLevel($user, 'network_olt');
        if ($level === 'all') return $query;

        $accessibleAreas = $user->getAccessibleAreaIds();
        if (empty($accessibleAreas)) return $query->whereRaw('1 = 0');
        return $query->whereIn('area_id', $accessibleAreas);
    }

    /**
     * Terapkan filter scope pada query ODC
     */
    public static function applyOdcScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) return $query;
        $level = self::getScopeLevel($user, 'network_odc');
        if ($level === 'all') return $query;

        $accessibleAreas = $user->getAccessibleAreaIds();
        if (empty($accessibleAreas)) return $query->whereRaw('1 = 0');
        return $query->where(function ($q) use ($accessibleAreas) {
            $q->whereIn('area_id', $accessibleAreas)
              ->orWhere(function ($fallback) use ($accessibleAreas) {
                  $fallback->whereNull('area_id')
                      ->whereHas('olt', fn($oq) => $oq->whereIn('area_id', $accessibleAreas));
              });
        });
    }

    /**
     * Terapkan filter scope pada query ODP
     */
    public static function applyOdpScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) return $query;
        $level = self::getScopeLevel($user, 'network_odp');
        if ($level === 'all') return $query;

        $accessibleAreas = $user->getAccessibleAreaIds();
        if (empty($accessibleAreas)) return $query->whereRaw('1 = 0');
        return $query->where(function ($q) use ($accessibleAreas) {
            $q->whereIn('area_id', $accessibleAreas)
              ->orWhere(function ($fallback) use ($accessibleAreas) {
                  $fallback->whereNull('area_id')
                      ->whereHas('odc', function ($odcq) use ($accessibleAreas) {
                          $odcq->whereIn('area_id', $accessibleAreas)
                               ->orWhere(function ($odcFallback) use ($accessibleAreas) {
                                   $odcFallback->whereNull('area_id')
                                       ->whereHas('olt', fn($oq) => $oq->whereIn('area_id', $accessibleAreas));
                               });
                      });
              });
        });
    }

    /**
     * Terapkan filter scope pada query ONT
     */
    public static function applyOntScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) return $query;
        $level = self::getScopeLevel($user, 'network_ont');
        if ($level === 'all') return $query;

        $accessibleAreas = $user->getAccessibleAreaIds();
        if (empty($accessibleAreas)) return $query->whereRaw('1 = 0');
        return $query->where(function ($q) use ($accessibleAreas) {
            $q->whereIn('area_id', $accessibleAreas)
              ->orWhere(function ($fallback) use ($accessibleAreas) {
                  $fallback->whereNull('area_id')
                      ->where(function ($f) use ($accessibleAreas) {
                          $f->whereHas('odp', fn($odpq) => $odpq->whereIn('area_id', $accessibleAreas))
                            ->orWhereHas('customer', fn($cq) => $cq->whereIn('area_id', $accessibleAreas));
                      });
              });
        });
    }

    /**
     * Terapkan filter scope pada query FTTH Design
     */
    public static function applyFtthScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) return $query;
        $level = self::getScopeLevel($user, 'ftth');
        if ($level === 'all') return $query;

        $accessibleAreas = $user->getAccessibleAreaIds();
        if (empty($accessibleAreas)) return $query->whereRaw('1 = 0');
        return $query->whereIn('area_id', $accessibleAreas);
    }

    /**
     * Terapkan filter scope pada query Invoice / Billing
     */
    public static function applyInvoiceScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'invoices');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereHas('customer', function ($cq) use ($accessibleAreas, $user) {
                $cq->whereIn('area_id', $accessibleAreas)
                   ->orWhere('sales_id', $user->id);
            });
        }

        // Level: 'own' (Invoice milik pelanggan yang didaftarkan oleh sales ini)
        return $query->whereHas('customer', function ($cq) use ($user) {
            $cq->where('sales_id', $user->id);
        });
    }

    /**
     * Terapkan filter scope pada query Work Orders Rollout Infrastruktur ODC & ODP
     */
    public static function applyWorkOrderScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'infrastructure_work_orders');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->where(function ($q) use ($accessibleAreas, $user) {
                $q->whereIn('area_id', $accessibleAreas)
                  ->orWhere('technician_id', $user->id)
                  ->orWhereHas('technicians', fn($t) => $t->where('users.id', $user->id));
            });
        }

        // Level: 'own' / 'assigned' (Ditugaskan sebagai teknisi utama atau anggota tim teknisi)
        return $query->where(function ($q) use ($user) {
            $q->where('technician_id', $user->id)
              ->orWhereHas('technicians', fn($t) => $t->where('users.id', $user->id));
        });
    }

    /**
     * Terapkan filter scope pada query Transaksi Gudang / Material
     */
    public static function applyMaterialTransactionScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'material_transactions');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            $areaNames = Area::whereIn('id', $accessibleAreas)->pluck('name')->toArray();
            return $query->where(function ($q) use ($accessibleAreas, $areaNames) {
                $q->whereIn('area_id', $accessibleAreas)
                  ->orWhereIn('area', $areaNames)
                  ->orWhereIn('technician_name', $areaNames);
            });
        }

        // Level: 'own' (Transaksi yang diinput / diajukan oleh user bersangkutan)
        return $query->where('user_id', $user->id);
    }

    /**
     * Terapkan filter scope pada query Order Toko (Restock)
     */
    public static function applyPurchaseOrderScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'purchase_orders');
        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            $areaNames = Area::whereIn('id', $accessibleAreas)->pluck('name')->toArray();
            return $query->where(function ($q) use ($accessibleAreas, $areaNames) {
                $q->whereIn('area_id', $accessibleAreas)
                  ->orWhereIn('area', $areaNames)
                  ->orWhereIn('technician_name', $areaNames);
            });
        }

        // Level: 'own'
        return $query->where('user_id', $user->id);
    }

    /**
     * Terapkan filter scope pada query Absensi Karyawan
     */
    public static function applyAttendanceScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'attendances');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereIn('area_id', $accessibleAreas);
        }

        // Level: 'own' (Hanya absensi diri sendiri)
        $empId = $user->employee?->id;
        return $query->where(function ($q) use ($user, $empId) {
            $q->where('user_id', $user->id);
            if ($empId) {
                $q->orWhere('employee_id', $empId);
            }
        });
    }

    /**
     * Terapkan filter scope pada query Pantauan Pelanggan (Menunggak & Janji Bayar)
     */
    public static function applyCustomerMonitoringScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'monitoring_pelanggan');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereIn('area_id', $accessibleAreas);
        }

        // Level: 'own' (Hanya pelanggan binaan sales bersangkutan)
        return $query->where('sales_id', $user->id);
    }

    /**
     * Terapkan filter scope pada query Penagihan Lapangan & Setoran
     */
    public static function applyCustomerBillingScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'customer_billing');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereIn('area_id', $accessibleAreas);
        }

        // Level: 'own' (Hanya setoran/tagihan kolektor bersangkutan)
        return $query->where('collector_id', $user->id);
    }

    /**
     * Terapkan filter scope pada query Karyawan & SDM (HR)
     */
    public static function applyEmployeeScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'hr_employees');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereIn('area_id', $accessibleAreas);
        }

        // Level: 'own' (Hanya biodata diri sendiri)
        $empId = $user->employee?->id;
        return $query->where(function ($q) use ($user, $empId) {
            $q->where('email', $user->email);
            if ($empId) {
                $q->orWhere('id', $empId);
            }
            if (!empty($user->phone)) {
                $q->orWhere('phone', $user->phone);
            }
            if (!empty($user->name)) {
                $q->orWhere('name', $user->name);
            }
        });
    }

    /**
     * Terapkan filter scope pada query Karyawan Jadwal Kerja / Roster
     */
    public static function applyWorkScheduleEmployeeScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'work_schedules');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->where(function ($q) use ($accessibleAreas) {
                $q->whereIn('area_id', $accessibleAreas);
                $areaNames = Area::whereIn('id', $accessibleAreas)->pluck('name')->filter()->toArray();
                if (!empty($areaNames)) {
                    $q->orWhereIn('branch', $areaNames);
                }
            });
        }

        // Level: 'own' (Hanya jadwal roster diri sendiri)
        $empId = $user->employee?->id;
        return $query->where(function ($q) use ($user, $empId) {
            $q->where('email', $user->email);
            if ($empId) {
                $q->orWhere('id', $empId);
            }
            if (!empty($user->phone)) {
                $q->orWhere('phone', $user->phone);
            }
            if (!empty($user->name)) {
                $q->orWhere('name', $user->name);
            }
        });
    }

    /**
     * Helper umum untuk query dengan kolom area_id dan callback data pribadi
     */
    public static function applyGeneralScope(
        Builder $query,
        ?User $user = null,
        string $module = 'general',
        string $areaColumn = 'area_id',
        ?callable $ownCallback = null
    ): Builder {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, $module);

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            return $query->whereIn($areaColumn, $user->getAccessibleAreaIds());
        }

        if ($ownCallback) {
            return $ownCallback($query, $user);
        }

        // Default fallback own data: filter by user_id
        return $query->where('user_id', $user->id);
    }

    /**
     * Terapkan filter scope pada query Master Material & Stok
     */
    public static function applyMaterialScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'materials');

        if ($level === 'all') {
            return $query;
        }

        if ($level === 'area') {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereHas('stocks', function ($sq) use ($accessibleAreas) {
                $sq->whereIn('area_id', $accessibleAreas);
            });
        }

        return $query;
    }

    /**
     * Terapkan filter scope pada query Stok Barang per Area
     */
    public static function applyWarehouseStockScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'warehouse_stocks');
        if ($level === 'all' || $user->can('materials_view_all')) {
            return $query;
        }

        if ($level === 'area' || $user->can('materials_view_area')) {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereHas('stocks', function ($sq) use ($accessibleAreas) {
                $sq->whereIn('area_id', $accessibleAreas);
            });
        }

        return $query;
    }

    /**
     * Terapkan filter scope pada query Marketing Afiliasi & Mitra
     */
    public static function applyAffiliateScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $level = self::getScopeLevel($user, 'marketing_affiliates');
        if ($level === 'all' || $user->can('marketing_affiliates_view_all')) {
            return $query;
        }

        if ($level === 'area' || $user->can('marketing_affiliates_view_area')) {
            $accessibleAreas = $user->getAccessibleAreaIds();
            return $query->whereHas('user', function ($uq) use ($accessibleAreas) {
                $uq->whereIn('area_id', $accessibleAreas);
            });
        }

        // Own data scope
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('upline_id', $user->id);
        });
    }
}
