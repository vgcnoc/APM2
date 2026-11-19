<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Employee;
use App\Models\Area;
use App\Models\Odp;
use App\Models\Odc;
use App\Models\NetworkMaintenance;
use App\Models\NetworkMaintenanceMaterial;
use App\Models\InfrastructureWorkOrder;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\PgsTicket;
use App\Models\PgkTicket;
use App\Models\PgjTicket;
use App\Models\CbpRequest;
use App\Models\TechnicianSchedule;
use App\Models\Customer;
use App\Models\Survey;
use App\Models\WorkSchedule;
use App\Models\Shift;
use App\Models\Attendance;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OperationalDailyProgressController extends Controller
{
    /**
     * Tampilkan Halaman Progress Harian Operasional (PGS, PGK, PGJ, CBP, Instalasi, Survey & Shift Teknisi)
     */
    /**
     * Halaman Khusus Menu Pemeliharaan Jalur (ODP, ODC, Jalur Utama)
     */
    public function networkMaintenanceIndex(Request $request)
    {
        $request->merge(['tab' => 'maintenance']);
        return $this->index($request);
    }

    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $areaId = $request->input('area_id');
        $customNote = $request->input('note', 'Tetap semangat dan jaga keselamatan kerja! 💪');

        // Parse Carbon Date & Format Bahasa Indonesia
        $carbonDate = Carbon::parse($date)->locale('id');
        $indonesianDayDate = $this->formatIndonesianDayDate($carbonDate);

        $user = auth()->user();
        $isGlobal = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->can('operational_progress_view_all'));
        $accessibleAreaIds = (!$isGlobal && $user) ? $user->getAccessibleAreaIds() : null;

        $targetAreaIds = null;
        if ($accessibleAreaIds !== null) {
            // User dibatasi per area kerja mereka (karyawan hanya bisa lihat data per area)
            if (empty($accessibleAreaIds)) {
                $targetAreaIds = [-1]; // Jika belum di-assign area, blokir data area lain
                $areaId = null;
            } else {
                if ($areaId) {
                    $requestedAreaId = (int) $areaId;
                    if (in_array($requestedAreaId, $accessibleAreaIds, true)) {
                        $targetAreaIds = [$requestedAreaId];
                    } else {
                        $targetAreaIds = $accessibleAreaIds;
                        $areaId = $accessibleAreaIds[0];
                    }
                } else {
                    $targetAreaIds = $accessibleAreaIds;
                    $areaId = $accessibleAreaIds[0];
                }
            }
        } else {
            // User global / admin: bebas filter area manapun atau tampilkan semua
            if ($areaId) {
                $targetAreaIds = [(int) $areaId];
            }
        }

        // Ambil Data Area sesuai scope
        $areas = \App\Services\DataScopeService::getScopedAreas($user, 'operational_progress');
        $selectedArea = $areaId ? Area::find($areaId) : null;
        $areaLabel = $selectedArea ? $selectedArea->name : ($accessibleAreaIds !== null ? 'Wilayah Ditugaskan' : 'Semua Wilayah / Area');

        // 1. DATA GANGGUAN (PGS, PGK)
        $gangguanList = $this->getGangguanData($date, $targetAreaIds);

        // 2. DATA GANGGUAN JALUR (PGJ - MANDIRI)
        $pgjList = $this->getPgjData($date, $targetAreaIds);

        // 3. DATA PEMASANGAN BARU (INSTALASI)
        $pemasanganList = $this->getPemasanganData($date, $targetAreaIds);

        // 4. DATA SURVEY PELANGGAN BARU
        $surveyList = $this->getSurveyData($date, $targetAreaIds);

        // 5. DATA PENCABUTAN / CBP
        $cbpList = $this->getCbpData($date, $targetAreaIds);

        // 6. DATA PEMELIHARAAN & PERAPIHAN JALUR (ODP, ODC, JALUR UTAMA - MANDIRI TANPA PGS/PGK/PGJ)
        $maintenanceList = $this->getMaintenanceData($date, $targetAreaIds);

        // Kumpulkan teknisi aktif dari seluruh penugasan operasional hari ini
        $activeJobTechNames = [];
        foreach (array_merge($gangguanList, $pgjList, $pemasanganList, $surveyList, $cbpList, $maintenanceList) as $taskItem) {
            if (!empty($taskItem['technicians'])) {
                foreach (explode(',', $taskItem['technicians']) as $tName) {
                    $trimmed = trim($tName);
                    if ($trimmed && !in_array($trimmed, ['Belum Ditugaskan', 'Tim Maintenance Jalur', 'Petugas Survey', 'Petugas CBP', 'Teknisi Instalasi', '-'])) {
                        $activeJobTechNames[] = $trimmed;
                    }
                }
            }
        }
        $activeJobTechNames = array_unique($activeJobTechNames);

        // 7. STATUS TEKNISI (On Duty, Libur, Masuk Malam)
        $technicianStatus = $this->getTechnicianStatus($date, $targetAreaIds, $activeJobTechNames);

        // 8. RINGKASAN DATA CERDAS & AUTO
        $summary = [
            'gangguan' => count($gangguanList),
            'gangguan_done' => collect($gangguanList)->where('is_done', true)->count(),
            'gangguan_in_progress' => collect($gangguanList)->where('is_in_progress', true)->count(),
            'gangguan_rescheduled' => collect($gangguanList)->where('is_rescheduled', true)->count(),
            'gangguan_pending' => collect($gangguanList)->where('is_done', false)->where('is_in_progress', false)->count(),

            'pgj' => count($pgjList),
            'pgj_done' => collect($pgjList)->where('is_done', true)->count(),
            'pgj_in_progress' => collect($pgjList)->where('is_in_progress', true)->count(),
            'pgj_rescheduled' => collect($pgjList)->where('is_rescheduled', true)->count(),
            'pgj_pending' => collect($pgjList)->where('is_done', false)->where('is_in_progress', false)->count(),

            'pemasangan' => count($pemasanganList),
            'pemasangan_done' => collect($pemasanganList)->where('is_done', true)->count(),
            'pemasangan_in_progress' => collect($pemasanganList)->where('is_in_progress', true)->count(),
            'pemasangan_rescheduled' => collect($pemasanganList)->where('is_rescheduled', true)->count(),
            'pemasangan_pending' => collect($pemasanganList)->where('is_done', false)->where('is_in_progress', false)->count(),

            'survey' => count($surveyList),
            'survey_feasible' => collect($surveyList)->where('is_done', true)->count(),
            'survey_pending' => collect($surveyList)->where('is_done', false)->count(),

            'cbp' => count($cbpList),
            'cbp_done' => collect($cbpList)->where('is_done', true)->count(),
            'cbp_in_progress' => collect($cbpList)->where('is_in_progress', true)->count(),
            'cbp_assigned' => collect($cbpList)->where('is_assigned', true)->count(),
            'cbp_pending' => collect($cbpList)->where('is_done', false)->where('is_in_progress', false)->where('is_assigned', false)->count(),

            'maintenance' => count($maintenanceList),
            'maintenance_done' => collect($maintenanceList)->where('is_done', true)->count(),
            'maintenance_in_progress' => collect($maintenanceList)->where('is_in_progress', true)->count(),
            'maintenance_rescheduled' => collect($maintenanceList)->where('is_rescheduled', true)->count(),
            'maintenance_pending' => collect($maintenanceList)->where('is_done', false)->where('is_in_progress', false)->count(),
        ];

        // 9. HASILKAN FORMAT TEKS WHATSAPP SESUAI TEMPLATE
        $waText = $this->buildWhatsAppTemplate(
            $areaLabel,
            $indonesianDayDate,
            $technicianStatus,
            $gangguanList,
            $pgjList,
            $pemasanganList,
            $surveyList,
            $cbpList,
            $maintenanceList,
            $summary,
            $customNote
        );

        $waTextPgj = $this->buildPgjWhatsAppTemplate(
            $areaLabel,
            $indonesianDayDate,
            $technicianStatus,
            $pgjList,
            $summary,
            $customNote
        );

        $waTextGangguan = $this->buildGangguanWhatsAppTemplate(
            $areaLabel,
            $indonesianDayDate,
            $technicianStatus,
            $gangguanList,
            $summary,
            $customNote
        );

        $waTextPemasangan = $this->buildPemasanganWhatsAppTemplate(
            $areaLabel,
            $indonesianDayDate,
            $technicianStatus,
            $pemasanganList,
            $summary,
            $customNote
        );

        // Cek Status Gateway WA
        $waGatewayStatus = false;
        try {
            $waService = app(WhatsAppGatewayService::class);
            $gwInfo = $waService->getStatus();
            $waGatewayStatus = !empty($gwInfo['connected']);
        } catch (\Throwable $e) {
            $waGatewayStatus = false;
        }

        // Ambil Daftar Teknisi Lapangan untuk Penugasan Cepat (Teknisi & Maintenance)
        $rawTechnicians = User::forAssignment()->where(function ($q) {
            $q->whereHas('roles', function ($r) {
                $r->where(function ($rq) {
                    $rq->where('name', 'LIKE', '%teknisi%')
                       ->orWhere('name', 'LIKE', '%maintenance%')
                       ->orWhere('name', 'LIKE', '%lapangan%')
                       ->orWhere('name', 'LIKE', '%jaringan%')
                       ->orWhere('name', 'LIKE', '%serbu%');
                });
            })->orWhere(function ($uq) {
                $uq->where('role', 'LIKE', '%teknisi%')
                   ->orWhere('role', 'LIKE', '%maintenance%')
                   ->orWhere('role', 'LIKE', '%lapangan%')
                   ->orWhere('role', 'LIKE', '%jaringan%')
                   ->orWhere('role', 'LIKE', '%serbu%');
            });
        })->with('roles')->orderBy('name')->get(['id', 'name', 'phone', 'area_id', 'role']);

        $allEmpsMap = Employee::get(['id', 'name', 'position', 'area_id']);
        $empMapByName = [];
        foreach ($allEmpsMap as $em) {
            if ($nm = mb_strtolower(trim((string)$em->name))) {
                $empMapByName[$nm] = $em;
            }
        }

        $availableTechnicians = $rawTechnicians->map(function ($u) use ($empMapByName) {
            $nm = mb_strtolower(trim((string)$u->name));
            $emp = $empMapByName[$nm] ?? null;
            $pos = mb_strtolower(trim((string)($emp?->position ?? '')));
            $roleCol = mb_strtolower(trim((string)($u->role ?? '')));
            $isMaint = str_contains($pos, 'maintenance') || str_contains($roleCol, 'maintenance');
            if (!$isMaint && $u->relationLoaded('roles')) {
                foreach ($u->roles as $r) {
                    if (str_contains(mb_strtolower((string)$r->name), 'maintenance')) {
                        $isMaint = true;
                        break;
                    }
                }
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'area_id' => $u->area_id,
                'is_maintenance' => $isMaint,
                'division' => $isMaint ? 'maintenance' : 'teknisi',
                'role_label' => $isMaint ? 'Maintenance' : 'Teknisi',
            ];
        });

        $availableOdps = Odp::select('id', 'name', 'area_id')->orderBy('name')->limit(500)->get();
        $availableOdcs = Odc::select('id', 'name', 'area_id')->orderBy('name')->limit(150)->get();

        $materials = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->with('stocks')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock', 'price_per_unit', 'category']);

        $materialTransactions = MaterialTransaction::where('type', 'in')
            ->with(['items.material.stocks', 'areaModel'])
            ->latest('id')
            ->limit(200)
            ->get();

        return Inertia::render('Operational/DailyProgress', [
            'defaultTab' => $request->input('tab', 'gangguan'),
            'filters' => [
                'date' => $date,
                'area_id' => $areaId ? (int) $areaId : null,
                'note' => $customNote,
            ],
            'areas' => $areas,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'indonesianDate' => $indonesianDayDate,
            'areaLabel' => $areaLabel,
            'gangguanList' => $gangguanList,
            'pgjList' => $pgjList,
            'pemasanganList' => $pemasanganList,
            'surveyList' => $surveyList,
            'cbpList' => $cbpList,
            'maintenanceList' => $maintenanceList,
            'technicianStatus' => $technicianStatus,
            'maintenanceStatus' => $technicianStatus['maintenance'] ?? [],
            'availableTechnicians' => $availableTechnicians,
            'availableOdps' => $availableOdps,
            'availableOdcs' => $availableOdcs,
            'summary' => $summary,
            'waText' => $waText,
            'waTextPgj' => $waTextPgj,
            'waTextGangguan' => $waTextGangguan,
            'waTextPemasangan' => $waTextPemasangan,
            'waGatewayStatus' => $waGatewayStatus,
            'canViewAllAreas' => $isGlobal,
            'isAreaScoped' => !$isGlobal,
        ]);
    }

    /**
     * Kirim Laporan Langsung via WhatsApp Gateway
     */
    public function sendWa(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ], [
            'phone.required' => 'Nomor tujuan WhatsApp atau ID Grup wajib diisi.',
            'message.required' => 'Isi pesan WhatsApp tidak boleh kosong.',
        ]);

        try {
            $waService = app(WhatsAppGatewayService::class);
            $rawPhone = trim($validated['phone']);

            // Bersihkan karakter selain angka, @, dan titik (untuk group ID WA atau no telepon)
            $rawPhone = preg_replace('/[^\d@\.a-zA-Z_-]/', '', $rawPhone);
            if (str_starts_with($rawPhone, '08')) {
                $rawPhone = '628' . substr($rawPhone, 2);
            }

            $res = $waService->sendMessage($rawPhone, $validated['message']);

            if (!empty($res['success']) || (!empty($res['status']) && $res['status'] === 'success')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan progress harian berhasil dikirim ke WhatsApp ' . $rawPhone . '!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Gagal mengirim pesan melalui WhatsApp Gateway.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[DailyProgress::sendWa] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menghubungi WA Gateway: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format Hari, Tanggal Bahasa Indonesia
     */
    private function formatIndonesianDayDate(Carbon $carbon): string
    {
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $dayName = $days[$carbon->format('l')] ?? $carbon->format('l');
        $dayNum = $carbon->format('d');
        $monthName = $months[(int)$carbon->format('m')] ?? $carbon->format('F');
        $year = $carbon->format('Y');

        return "{$dayName}, {$dayNum} {$monthName} {$year}";
    }

    /**
     * Ambil Data Gangguan (PGS, PGK, PGJ) secara Cerdas & Otomatis
     */
    private function getGangguanData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);
        $isToday = ($date === Carbon::today()->toDateString());

        // 1. PGS
        $pgsQuery = PgsTicket::with(['customer.ont.odp', 'technicians', 'leadTechnician', 'resolvedByUser', 'area'])
            ->where(function ($q) use ($date, $isToday) {
                $q->whereDate('scheduled_date', $date)
                  ->orWhereDate('resolved_at', $date)
                  ->orWhereDate('created_at', $date);

                if ($isToday) {
                    $q->orWhere(function ($sq) use ($date) {
                        $sq->whereIn('status', ['in_progress', 'scheduled', 'admin_handling', 'reported', 'open'])
                           ->whereNull('resolved_at')
                           ->where(function ($ssq) use ($date) {
                               $ssq->whereNull('scheduled_date')
                                   ->orWhereDate('scheduled_date', '<=', $date);
                           });
                    });
                }
            });

        if (!empty($areaIds)) {
            $pgsQuery->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)
                  ->orWhereHas('customer', fn($cq) => $cq->whereIn('area_id', $areaIds));
            });
        }

        foreach ($pgsQuery->get() as $pgs) {
            $techNames = $pgs->technicians->pluck('name')->toArray();
            if (empty($techNames) && $pgs->leadTechnician) {
                $techNames[] = $pgs->leadTechnician->name;
            }
            if (empty($techNames) && $pgs->resolvedByUser) {
                $techNames[] = $pgs->resolvedByUser->name;
            }
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Belum Ditugaskan';

            // Evaluasi Status Cerdas & Auto
            $isDone = in_array($pgs->status, ['completed', 'resolved', 'closed'])
                || !empty($pgs->resolved_at)
                || (!empty($pgs->action_taken) && !empty($pgs->end_time));

            $isInProgress = in_array($pgs->status, ['in_progress', 'working'])
                || (!empty($pgs->start_time) && empty($pgs->end_time) && !$isDone);

            // Cek apakah di-reschedule ke tanggal lain
            $isRescheduled = ($pgs->status === 'rescheduled');
            $schedDateStr = $pgs->scheduled_date ? Carbon::parse($pgs->scheduled_date)->toDateString() : null;
            if ($schedDateStr && $schedDateStr !== $date && $schedDateStr > $date) {
                $isRescheduled = true;
            }

            if ($isDone) {
                $statusLabel = '✅ Selesai';
                $statusClass = 'completed';
            } elseif ($isInProgress) {
                $statusLabel = '🛠️ Sedang Dikerjakan';
                $statusClass = 'in_progress';
            } elseif ($isRescheduled) {
                $reschedDateFmt = $schedDateStr ? Carbon::parse($schedDateStr)->format('d/m') : '';
                $statusLabel = '🗓️ Reschedule' . ($reschedDateFmt ? " ({$reschedDateFmt})" : '');
                $statusClass = 'rescheduled';
            } else {
                $statusLabel = '⏳ Pending';
                $statusClass = 'pending';
            }

            $duration = '-';
            if ($pgs->start_time && $pgs->end_time) {
                $duration = substr($pgs->start_time, 0, 5) . ' - ' . substr($pgs->end_time, 0, 5);
            } elseif ($pgs->start_time) {
                $duration = 'Mulai ' . substr($pgs->start_time, 0, 5);
            } elseif ($pgs->scheduled_time) {
                $duration = 'Jadwal ' . substr((string)$pgs->scheduled_time, 0, 5);
            }

            $odpName = $pgs->customer?->ont?->odp?->name ?? '-';

            $items[] = [
                'type' => 'PGS',
                'source' => 'PGS',
                'ticket_number' => $pgs->ticket_number,
                'customer_name' => $pgs->customer?->name ?? 'Non-Pelanggan (Temuan Sigap)',
                'address' => $pgs->customer?->address ?? ($pgs->area?->name ?? '-'),
                'odp' => $odpName,
                'issue' => $pgs->reported_issue ?: ($pgs->category ?: 'Gangguan Sigap'),
                'phone' => $pgs->customer?->phone ?? '-',
                'technicians' => $techniciansStr,
                'duration' => $duration,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_rescheduled' => $isRescheduled,
            ];
        }

        // 2. PGK (Komplain Pelanggan)
        $pgkQuery = PgkTicket::with(['customer.ont.odp', 'technicians', 'leadTechnician', 'resolvedByUser', 'area'])
            ->where(function ($q) use ($date, $isToday) {
                $q->whereDate('scheduled_date', $date)
                  ->orWhereDate('resolved_at', $date)
                  ->orWhereDate('created_at', $date);

                if ($isToday) {
                    $q->orWhere(function ($sq) use ($date) {
                        $sq->whereIn('status', ['in_progress', 'scheduled', 'admin_handling', 'reported', 'open'])
                           ->whereNull('resolved_at')
                           ->where(function ($ssq) use ($date) {
                               $ssq->whereNull('scheduled_date')
                                   ->orWhereDate('scheduled_date', '<=', $date);
                           });
                    });
                }
            });

        if (!empty($areaIds)) {
            $pgkQuery->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)
                  ->orWhereHas('customer', fn($cq) => $cq->whereIn('area_id', $areaIds));
            });
        }

        foreach ($pgkQuery->get() as $pgk) {
            $techNames = $pgk->technicians->pluck('name')->toArray();
            if (empty($techNames) && $pgk->leadTechnician) {
                $techNames[] = $pgk->leadTechnician->name;
            }
            if (empty($techNames) && $pgk->resolvedByUser) {
                $techNames[] = $pgk->resolvedByUser->name;
            }
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Belum Ditugaskan';

            // Evaluasi Status Cerdas & Auto
            $isDone = in_array($pgk->status, ['completed', 'resolved', 'closed'])
                || !empty($pgk->resolved_at)
                || (!empty($pgk->action_taken) && !empty($pgk->end_time));

            $isInProgress = in_array($pgk->status, ['in_progress', 'working'])
                || (!empty($pgk->start_time) && empty($pgk->end_time) && !$isDone);

            // Cek apakah di-reschedule ke tanggal lain
            $isRescheduled = ($pgk->status === 'rescheduled');
            $schedDateStr = $pgk->scheduled_date ? Carbon::parse($pgk->scheduled_date)->toDateString() : null;
            if ($schedDateStr && $schedDateStr !== $date && $schedDateStr > $date) {
                $isRescheduled = true;
            }

            if ($isDone) {
                $statusLabel = '✅ Selesai';
                $statusClass = 'completed';
            } elseif ($isInProgress) {
                $statusLabel = '🛠️ Sedang Dikerjakan';
                $statusClass = 'in_progress';
            } elseif ($isRescheduled) {
                $reschedDateFmt = $schedDateStr ? Carbon::parse($schedDateStr)->format('d/m') : '';
                $statusLabel = '🗓️ Reschedule' . ($reschedDateFmt ? " ({$reschedDateFmt})" : '');
                $statusClass = 'rescheduled';
            } else {
                $statusLabel = '⏳ Pending';
                $statusClass = 'pending';
            }

            $duration = '-';
            if ($pgk->start_time && $pgk->end_time) {
                $duration = substr($pgk->start_time, 0, 5) . ' - ' . substr($pgk->end_time, 0, 5);
            } elseif ($pgk->start_time) {
                $duration = 'Mulai ' . substr($pgk->start_time, 0, 5);
            } elseif ($pgk->scheduled_time) {
                $duration = 'Jadwal ' . substr((string)$pgk->scheduled_time, 0, 5);
            }

            $odpName = $pgk->customer?->ont?->odp?->name ?? '-';

            $items[] = [
                'type' => 'PGK',
                'source' => 'PGK',
                'ticket_number' => $pgk->ticket_number,
                'customer_name' => $pgk->customer?->name ?? ($pgk->complainant_name ?: 'Pelanggan'),
                'address' => $pgk->customer?->address ?? ($pgk->area?->name ?? '-'),
                'odp' => $odpName,
                'issue' => $pgk->reported_issue ?: ($pgk->category ?: 'Komplain Layanan'),
                'phone' => $pgk->complainant_phone ?: ($pgk->customer?->phone ?? '-'),
                'technicians' => $techniciansStr,
                'duration' => $duration,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_rescheduled' => $isRescheduled,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Gangguan Jalur (PGJ) secara Mandiri, Cerdas & Lengkap
     */
    private function getPgjData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);
        $isToday = ($date === Carbon::today()->toDateString());

        // PGJ (Gangguan Jalur / Backbone / Feeder / ODC / ODP)
        $pgjQuery = PgjTicket::with(['area', 'olt', 'odc', 'odp', 'technicians', 'leadTechnician', 'resolvedByUser'])
            ->where(function ($q) use ($date, $isToday) {
                $q->whereDate('scheduled_date', $date)
                  ->orWhereDate('resolved_at', $date)
                  ->orWhereDate('created_at', $date);

                if ($isToday) {
                    $q->orWhere(function ($sq) use ($date) {
                        $sq->whereIn('status', ['in_progress', 'scheduled', 'admin_handling', 'reported', 'open'])
                           ->whereNull('resolved_at')
                           ->where(function ($ssq) use ($date) {
                               $ssq->whereNull('scheduled_date')
                                   ->orWhereDate('scheduled_date', '<=', $date);
                           });
                    });
                }
            });

        if (!empty($areaIds)) {
            $pgjQuery->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)
                  ->orWhereHas('odp', fn($oq) => $oq->whereIn('area_id', $areaIds))
                  ->orWhereHas('odc', fn($oq) => $oq->whereIn('area_id', $areaIds))
                  ->orWhereHas('olt', fn($oq) => $oq->whereIn('area_id', $areaIds));
            });
        }

        foreach ($pgjQuery->get() as $pgj) {
            $techNames = $pgj->technicians->pluck('name')->toArray();
            if (empty($techNames) && $pgj->leadTechnician) {
                $techNames[] = $pgj->leadTechnician->name;
            }
            if (empty($techNames) && $pgj->resolvedByUser) {
                $techNames[] = $pgj->resolvedByUser->name;
            }
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Belum Ditugaskan';

            $isDone = in_array($pgj->status, ['completed', 'resolved', 'closed']) || !empty($pgj->resolved_at);
            $isInProgress = in_array($pgj->status, ['in_progress', 'working']) || (!empty($pgj->start_time) && empty($pgj->end_time) && !$isDone);

            $isRescheduled = ($pgj->status === 'rescheduled');
            $schedDateStr = $pgj->scheduled_date ? Carbon::parse($pgj->scheduled_date)->toDateString() : null;
            if ($schedDateStr && $schedDateStr !== $date && $schedDateStr > $date) {
                $isRescheduled = true;
            }

            if ($isDone) {
                $statusLabel = '✅ Selesai';
                $statusClass = 'completed';
            } elseif ($isInProgress) {
                $statusLabel = '🛠️ Sedang Dikerjakan';
                $statusClass = 'in_progress';
            } elseif ($isRescheduled) {
                $reschedDateFmt = $schedDateStr ? Carbon::parse($schedDateStr)->format('d/m') : '';
                $statusLabel = '🗓️ Reschedule' . ($reschedDateFmt ? " ({$reschedDateFmt})" : '');
                $statusClass = 'rescheduled';
            } else {
                $statusLabel = '⏳ Pending';
                $statusClass = 'pending';
            }

            $duration = '-';
            if ($pgj->start_time && $pgj->end_time) {
                $duration = substr($pgj->start_time, 0, 5) . ' - ' . substr($pgj->end_time, 0, 5);
            } elseif ($pgj->start_time) {
                $duration = 'Mulai ' . substr($pgj->start_time, 0, 5);
            } elseif ($pgj->scheduled_time) {
                $duration = 'Jadwal ' . substr((string)$pgj->scheduled_time, 0, 5);
            }

            $infraName = $pgj->odp?->name ?: ($pgj->odc?->name ?: ($pgj->olt?->name ?: ($pgj->location_name ?: '-')));
            $locName = $pgj->location_name ?: ($pgj->segment_type ? "Segmen {$pgj->segment_type}" : 'Jalur FTTH');

            $items[] = [
                'id' => $pgj->id,
                'type' => 'PGJ',
                'source' => 'PGJ',
                'ticket_number' => $pgj->ticket_number,
                'segment_type' => $pgj->segment_type ?: 'Jalur FTTH',
                'category' => $pgj->category ?: 'Gangguan Jalur',
                'priority' => $pgj->priority ?: 'normal',
                'location_name' => $locName,
                'customer_name' => '⚠️ Jalur/Infra: ' . $locName,
                'address' => $pgj->area?->name ?? 'Wilayah Jalur',
                'area_name' => $pgj->area?->name ?? '-',
                'odp' => $pgj->odp?->name ?: '-',
                'odc' => $pgj->odc?->name ?: '-',
                'olt' => $pgj->olt?->name ?: '-',
                'infra_target' => $infraName,
                'issue' => $pgj->reported_issue ?: 'Gangguan Jalur FTTH',
                'cause' => $pgj->cause_indication ?: ($pgj->escalation_reason ?: '-'),
                'action_taken' => $pgj->action_taken ?: ($pgj->resolution_notes ?: '-'),
                'phone' => '-',
                'technicians' => $techniciansStr,
                'technician_ids' => $pgj->technicians->pluck('id')->toArray(),
                'duration' => $duration,
                'affected_customers_count' => (int) ($pgj->affected_customers_count ?: 0),
                'optical_power_before' => $pgj->optical_loss_before,
                'optical_power_after' => $pgj->optical_loss_after,
                'photo_before' => $pgj->photo_before ? Storage::url($pgj->photo_before) : null,
                'photo_after' => $pgj->photo_after ? Storage::url($pgj->photo_after) : null,
                'status' => $pgj->status,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_rescheduled' => $isRescheduled,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Pemasangan Baru (Instalasi) secara Cerdas & Otomatis
     */
    private function getPemasanganData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);

        // 1. Ambil dari TechnicianSchedule type = 'installation'
        $scheduleQuery = TechnicianSchedule::with(['customer.ont.odp', 'customer.package', 'customer.surveys.odp', 'technician'])
            ->where('type', 'installation')
            ->whereDate('scheduled_date', $date);

        if (!empty($areaIds)) {
            $scheduleQuery->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)
                  ->orWhereHas('customer', fn($cq) => $cq->whereIn('area_id', $areaIds));
            });
        }

        // Grouping berdasarkan customer_id agar bila 1 pelanggan ditugaskan 2 teknisi (misal: Ruli & Saepul),
        // tidak menjadi 2 item duplikat melainkan digabung teknisinya!
        $groupedSchedules = $scheduleQuery->get()->groupBy('customer_id');
        $seenCustomerIds = [];

        foreach ($groupedSchedules as $customerId => $schedules) {
            $firstSched = $schedules->first();
            $cust = $firstSched->customer;
            if (!$cust) continue;

            $seenCustomerIds[$cust->id] = true;

            // Gabungkan seluruh teknisi yang ditugaskan
            $techNames = $schedules->pluck('technician.name')->filter()->unique()->toArray();
            $techniciansStr = !empty($techNames) ? implode(', ', $techNames) : 'Belum Ditugaskan';

            // Cek apakah ada schedule yang selesai atau customer sudah aktif
            $isAnyDone = $schedules->contains(fn($s) => in_array($s->status, ['done', 'completed']));
            $isCustActive = in_array($cust->status, ['active', 'installed']) 
                || (!empty($cust->activation_date) && Carbon::parse($cust->activation_date)->toDateString() <= $date);
            $isDone = $isAnyDone || $isCustActive;

            $isAnyWorking = $schedules->contains(fn($s) => in_array($s->status, ['working', 'in_progress', 'on_the_way']));
            $isInProgress = !$isDone && ($isAnyWorking || $cust->status === 'installing');

            $isRescheduled = $schedules->contains(fn($s) => $s->status === 'rescheduled');

            if ($isDone) {
                $statusLabel = '✅ Selesai';
                $statusClass = 'completed';
            } elseif ($isInProgress) {
                $statusLabel = '🛠️ Sedang Dipasang';
                $statusClass = 'in_progress';
            } elseif ($isRescheduled) {
                $statusLabel = '🗓️ Reschedule';
                $statusClass = 'rescheduled';
            } else {
                $statusLabel = '⏳ Pending';
                $statusClass = 'pending';
            }

            $pkgName = $cust->package?->name ?? 'Paket Internet';
            $detail = "{$pkgName} - {$cust->address}";
            $odpName = $cust->ont?->odp?->name ?? ($cust->surveys?->last()?->odp?->name ?? '-');

            // Ambil waktu dari schedule pertama yang memiliki scheduled_time
            $duration = '-';
            $schedWithTime = $schedules->first(fn($s) => !empty($s->scheduled_time));
            if ($schedWithTime) {
                $duration = substr((string)$schedWithTime->scheduled_time, 0, 5);
            }

            $items[] = [
                'customer_id' => $cust->id,
                'customer_name' => $cust->name,
                'customer_code' => $cust->customer_id ?? $cust->code ?? '-',
                'detail' => $detail,
                'address' => $cust->address ?? '-',
                'odp' => $odpName,
                'phone' => $cust->phone,
                'technicians' => $techniciansStr,
                'duration' => $duration,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_rescheduled' => $isRescheduled,
            ];
        }

        // Tambahkan juga Customer yang aktivasi hari ini jika belum tercakup di jadwal di atas
        $activeQuery = Customer::with(['ont.odp', 'package', 'technicianSchedules.technician'])
            ->whereDate('activation_date', $date);

        if (!empty($areaIds)) {
            $activeQuery->whereIn('area_id', $areaIds);
        }

        foreach ($activeQuery->get() as $cust) {
            if (isset($seenCustomerIds[$cust->id])) continue;

            $installScheds = $cust->technicianSchedules->where('type', 'installation');
            $datedScheds = $installScheds->filter(function ($ts) use ($date) {
                if (!$ts->scheduled_date) return false;
                $tsDate = is_string($ts->scheduled_date) ? substr($ts->scheduled_date, 0, 10) : $ts->scheduled_date->format('Y-m-d');
                return $tsDate === $date;
            });
            $techNames = ($datedScheds->isNotEmpty() ? $datedScheds : $installScheds)->pluck('technician.name')->filter()->unique()->toArray();
            $techniciansStr = !empty($techNames) ? implode(', ', $techNames) : 'Teknisi Instalasi';

            $pkgName = $cust->package?->name ?? 'Paket Internet';

            $items[] = [
                'customer_id' => $cust->id,
                'customer_name' => $cust->name,
                'customer_code' => $cust->customer_id ?? $cust->code ?? '-',
                'detail' => "{$pkgName} - {$cust->address}",
                'address' => $cust->address ?? '-',
                'odp' => $cust->ont?->odp?->name ?? '-',
                'phone' => $cust->phone,
                'technicians' => $techniciansStr,
                'duration' => '-',
                'status_label' => '✅ Selesai',
                'status_class' => 'completed',
                'is_done' => true,
                'is_in_progress' => false,
                'is_rescheduled' => false,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Survey Pelanggan Baru secara Cerdas & Otomatis
     */
    private function getSurveyData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);
        $seenCustomerIds = [];

        // 1. Dari model Survey (hasil survey lapangan)
        $surveyQuery = Survey::with(['customer.package', 'odp', 'surveyor', 'customer.technicianSchedules.technician'])
            ->whereDate('survey_date', $date);

        if (!empty($areaIds)) {
            $surveyQuery->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)
                  ->orWhereHas('customer', fn($sq) => $sq->whereIn('area_id', $areaIds));
            });
        }

        foreach ($surveyQuery->get() as $srv) {
            $cust = $srv->customer;
            if (!$cust) continue;

            $seenCustomerIds[$cust->id] = true;
            $pkgName = $cust->package?->name ?? 'Paket Internet';
            $detail = "{$pkgName} - {$cust->address}";

            // Petugas survey
            $techNames = [];
            if ($srv->surveyor) {
                $techNames[] = $srv->surveyor->name;
            }
            $schedTechs = $cust->technicianSchedules
                ->where('type', 'survey')
                ->filter(function ($ts) use ($date) {
                    if (!$ts->scheduled_date) return false;
                    $tsDate = is_string($ts->scheduled_date) ? substr($ts->scheduled_date, 0, 10) : $ts->scheduled_date->format('Y-m-d');
                    return $tsDate === $date;
                })
                ->pluck('technician.name')
                ->filter()
                ->toArray();
            $allTechs = array_unique(array_merge($techNames, $schedTechs));
            $techniciansStr = !empty($allTechs) ? implode(', ', $allTechs) : 'Petugas Survey';

            $feasibility = $srv->feasibility;
            $isDone = in_array($feasibility, ['feasible', 'not_feasible', 'conditional']);

            if ($feasibility === 'feasible') {
                $statusLabel = '✅ Layak (Feasible)';
                $statusClass = 'completed';
            } elseif ($feasibility === 'not_feasible') {
                $statusLabel = '❌ Tidak Layak' . ($srv->notes ? " ({$srv->notes})" : '');
                $statusClass = 'rejected';
            } elseif ($feasibility === 'conditional') {
                $statusLabel = '⚠️ Bersyarat';
                $statusClass = 'conditional';
            } else {
                $statusLabel = '⏳ Pending';
                $statusClass = 'pending';
            }

            $items[] = [
                'customer_name' => $cust->name,
                'detail' => $detail,
                'address' => $cust->address ?? '-',
                'odp' => $srv->odp?->name ?? '-',
                'phone' => $cust->phone,
                'technicians' => $techniciansStr,
                'feasibility' => $feasibility,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => false,
                'is_rescheduled' => false,
            ];
        }

        // 2. Dari TechnicianSchedule type = 'survey' yang terjadwal pada $date
        $schedQuery = TechnicianSchedule::with(['customer.package', 'technician'])
            ->where('type', 'survey')
            ->whereDate('scheduled_date', $date);

        if (!empty($areaIds)) {
            $schedQuery->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)
                  ->orWhereHas('customer', fn($cq) => $cq->whereIn('area_id', $areaIds));
            });
        }

        $groupedScheds = $schedQuery->get()->groupBy('customer_id');

        foreach ($groupedScheds as $customerId => $schedules) {
            $firstSched = $schedules->first();
            $cust = $firstSched->customer;
            if (!$cust || isset($seenCustomerIds[$cust->id])) continue;

            $techNames = $schedules->pluck('technician.name')->filter()->unique()->toArray();
            $techniciansStr = !empty($techNames) ? implode(', ', $techNames) : 'Petugas Survey';

            $isDone = $schedules->contains(fn($s) => in_array($s->status, ['done', 'completed']));
            $isRescheduled = $schedules->contains(fn($s) => $s->status === 'rescheduled');

            if ($isDone) {
                $statusLabel = '✅ Selesai Survey';
                $statusClass = 'completed';
                $feasibility = 'feasible';
            } elseif ($isRescheduled) {
                $statusLabel = '🗓️ Reschedule';
                $statusClass = 'rescheduled';
                $feasibility = 'pending';
            } else {
                $statusLabel = '⏳ Menunggu Survey';
                $statusClass = 'pending';
                $feasibility = 'pending';
            }

            $pkgName = $cust->package?->name ?? 'Paket Internet';

            $items[] = [
                'customer_name' => $cust->name,
                'detail' => "{$pkgName} - {$cust->address}",
                'address' => $cust->address ?? '-',
                'odp' => '-',
                'phone' => $cust->phone,
                'technicians' => $techniciansStr,
                'feasibility' => $feasibility,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => false,
                'is_rescheduled' => $isRescheduled,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Pencabutan / CBP secara Cerdas & Otomatis
     */
    private function getCbpData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);
        $areaInts = array_values(array_filter(array_map('intval', $areaIds)));
        $areaNames = !empty($areaInts) ? Area::whereIn('id', $areaInts)->pluck('name')->filter()->toArray() : [];

        $isToday = ($date === Carbon::today()->toDateString());

        $cbpQuery = CbpRequest::with(['customer.ont.odp', 'customer.areaModel', 'technicians', 'creator', 'dispatcher'])
            ->where('status', '!=', 'canceled')
            ->where(function ($q) use ($date, $isToday) {
                // 1. Tiket yang dijadwalkan pada tanggal ini
                $q->whereDate('scheduled_date', $date)
                  // 2. Atau tiket yang diselesaikan pada tanggal ini
                  ->orWhereDate('completed_at', $date)
                  // 3. Atau tiket yang belum punya jadwal (masih pending) pada tanggal pembuatan / disposisinya
                  ->orWhere(function ($fallback) use ($date) {
                      $fallback->whereNull('scheduled_date')
                               ->whereNull('completed_at')
                               ->where(function ($sq) use ($date) {
                                   $sq->whereDate('sent_to_schedule_at', $date)
                                      ->orWhereDate('created_at', $date);
                               });
                  });

                // 4. Jika melihat hari ini (today), sertakan juga tiket aktif (pending/assigned/in_progress) yang belum selesai dari jadwal sebelumnya (carry over)
                if ($isToday) {
                    $q->orWhere(function ($sq) use ($date) {
                        $sq->whereIn('status', ['pending', 'assigned', 'in_progress'])
                           ->whereNull('completed_at')
                           ->where(function ($ssq) use ($date) {
                               $ssq->whereNull('scheduled_date')
                                   ->orWhereDate('scheduled_date', '<=', $date);
                           });
                    });
                }
            });

        if (!empty($areaInts)) {
            $cbpQuery->whereHas('customer', function ($cq) use ($areaInts, $areaNames) {
                $cq->where(function ($sub) use ($areaInts, $areaNames) {
                    $sub->whereIn('area_id', $areaInts);
                    if (!empty($areaNames)) {
                        $sub->orWhereIn('area', $areaNames);
                    }
                });
            });
        }

        foreach ($cbpQuery->get() as $cbp) {
            $cust = $cbp->customer;
            $techNames = $cbp->technicians->pluck('name')->toArray();
            if (empty($techNames) && !empty($cbp->assigned_to)) {
                $assignedUser = User::find($cbp->assigned_to);
                if ($assignedUser) $techNames[] = $assignedUser->name;
            }
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Belum Ditugaskan';

            $isDone = ($cbp->status === 'completed') || !empty($cbp->completed_at);
            $isInProgress = ($cbp->status === 'in_progress');
            $isRescheduled = ($cbp->status === 'rescheduled') || (!empty($cbp->reschedule_count) && $cbp->reschedule_count > 0 && !empty($cbp->rescheduled_at) && Carbon::parse($cbp->rescheduled_at)->isSameDay($date));
            $isAssigned = ($cbp->status === 'assigned');

            if ($isDone) {
                $statusLabel = '✅ Selesai Cabut';
                $statusClass = 'completed';
            } elseif ($isInProgress) {
                $statusLabel = '🛠️ Sedang Dikerjakan';
                $statusClass = 'in_progress';
            } elseif ($isRescheduled) {
                $statusLabel = '🗓️ Reschedule' . ($cbp->scheduled_date ? ' (' . Carbon::parse($cbp->scheduled_date)->format('d/m') . ')' : '');
                $statusClass = 'rescheduled';
            } elseif ($isAssigned) {
                $statusLabel = '🔧 Terjadwal / Ditugaskan';
                $statusClass = 'assigned';
            } else {
                $statusLabel = '⏳ Menunggu Jadwal';
                $statusClass = 'pending';
            }

            $odpName = $cust?->ont?->odp?->name ?? '-';
            $custName = $cust?->name ?? "Pelanggan #{$cbp->customer_id}";
            $address = $cust?->address ?? '-';
            $reason = $cbp->reason ?: ($cbp->notes ?: 'Putus Berlangganan');

            $duration = '-';
            if ($cbp->start_time && $cbp->end_time) {
                $duration = substr($cbp->start_time, 0, 5) . ' - ' . substr($cbp->end_time, 0, 5);
            } elseif ($cbp->start_time) {
                $duration = 'Mulai ' . substr($cbp->start_time, 0, 5);
            }

            $items[] = [
                'id' => $cbp->id,
                'cbp_number' => $cbp->cbp_number,
                'customer_id' => $cbp->customer_id,
                'customer_code' => $cust?->customer_code ?? $cbp->cbp_number,
                'customer_name' => $custName,
                'detail' => "{$custName} - {$address} (ODP: {$odpName}, Alasan: {$reason})",
                'address' => $address,
                'odp' => $odpName,
                'reason' => $reason,
                'phone' => $cust?->phone ?? '-',
                'technicians' => $techniciansStr,
                'duration' => $duration,
                'status' => $cbp->status,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_rescheduled' => $isRescheduled,
                'is_assigned' => $isAssigned,
                'reschedule_reason' => $cbp->reschedule_reason,
                'reschedule_count' => (int)($cbp->reschedule_count ?? 0),
                'rescheduled_at' => $cbp->rescheduled_at ? Carbon::parse($cbp->rescheduled_at)->toDateTimeString() : null,
                'scheduled_date' => $cbp->scheduled_date ? Carbon::parse($cbp->scheduled_date)->toDateString() : null,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Pemeliharaan & Perapihan Jalur (ODP, ODC, Jalur Utama) secara Cerdas
     * Berjalan mandiri tanpa harus melalui tiket gangguan PGS/PGK/PGJ
     */
    private function getMaintenanceData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);
        $isToday = ($date === Carbon::today()->toDateString());

        $query = NetworkMaintenance::with(['area', 'odp', 'odc', 'technicians', 'creator', 'materials.material'])
            ->where(function ($q) use ($date, $isToday) {
                $q->whereDate('scheduled_date', $date)
                  ->orWhereDate('completed_at', $date);

                if ($isToday) {
                    $q->orWhere(function ($sq) use ($date) {
                        $sq->whereIn('status', ['in_progress', 'scheduled'])
                           ->whereNull('completed_at')
                           ->where(function ($ssq) use ($date) {
                               $ssq->whereNull('scheduled_date')
                                   ->orWhereDate('scheduled_date', '<=', $date);
                           });
                    });
                }
            });

        if (!empty($areaIds)) {
            $query->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)
                  ->orWhereHas('odp', fn($oq) => $oq->whereIn('area_id', $areaIds))
                  ->orWhereHas('odc', fn($cq) => $cq->whereIn('area_id', $areaIds));
            });
        }

        foreach ($query->orderBy('scheduled_time')->orderBy('id', 'desc')->get() as $m) {
            $techNames = $m->technicians->pluck('name')->toArray();
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Tim Maintenance Jalur';

            $isDone = ($m->status === 'completed') || !empty($m->completed_at);
            $isInProgress = ($m->status === 'in_progress') || (!empty($m->start_time) && empty($m->end_time) && !$isDone);

            $isRescheduled = ($m->status === 'rescheduled');
            $schedDateStr = $m->scheduled_date ? Carbon::parse($m->scheduled_date)->toDateString() : null;
            if ($schedDateStr && $schedDateStr !== $date && $schedDateStr > $date) {
                $isRescheduled = true;
            }

            if ($isDone) {
                $statusLabel = '✅ Selesai';
                $statusClass = 'completed';
            } elseif ($isInProgress) {
                $statusLabel = '🛠️ Sedang Dikerjakan';
                $statusClass = 'in_progress';
            } elseif ($isRescheduled) {
                $reschedDateFmt = $schedDateStr ? Carbon::parse($schedDateStr)->format('d/m') : '';
                $statusLabel = '🗓️ Reschedule' . ($reschedDateFmt ? " ({$reschedDateFmt})" : '');
                $statusClass = 'rescheduled';
            } else {
                $statusLabel = '⏳ Pending';
                $statusClass = 'pending';
            }

            $duration = '-';
            if ($m->start_time && $m->end_time) {
                $duration = substr($m->start_time, 0, 5) . ' - ' . substr($m->end_time, 0, 5);
            } elseif ($m->start_time) {
                $duration = 'Mulai ' . substr($m->start_time, 0, 5);
            } elseif ($m->scheduled_time) {
                $duration = 'Jadwal ' . substr((string)$m->scheduled_time, 0, 5);
            }

            $location = $m->location_name ?: ($m->odp?->name ?: ($m->odc?->name ?: ($m->area?->name ?? 'Wilayah Lapangan')));

            $items[] = [
                'id' => $m->id,
                'maintenance_number' => $m->maintenance_number,
                'category' => $m->category,
                'category_label' => $m->category_label,
                'category_icon' => $m->category_icon,
                'title' => $m->title,
                'location' => $location,
                'area_name' => $m->area?->name ?? '-',
                'odp' => $m->odp?->name ?? '-',
                'odc' => $m->odc?->name ?? '-',
                'technicians' => $techniciansStr,
                'technician_ids' => $m->technicians->pluck('id')->toArray(),
                'duration' => $duration,
                'start_time' => $m->start_time,
                'end_time' => $m->end_time,
                'scheduled_time' => $m->scheduled_time,
                'scheduled_date' => $schedDateStr,
                'status' => $m->status,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_rescheduled' => $isRescheduled,
                'description' => $m->description,
                'action_taken' => $m->action_taken,
                'optical_power_before' => $m->optical_power_before,
                'optical_power_after' => $m->optical_power_after,
                'photo_before' => $m->photo_before ? Storage::url($m->photo_before) : null,
                'photo_after' => $m->photo_after_urls[0] ?? ($m->photo_after ? Storage::url($m->photo_after) : null),
                'photo_after_urls' => $m->photo_after_urls,
                'photo_before_urls' => $m->photo_before_urls,
                'replaced_materials' => $m->replaced_materials ?? [],
                'materials' => $m->materials->map(fn($mat) => [
                    'id' => $mat->id,
                    'material_id' => $mat->material_id,
                    'name' => $mat->material?->name ?? 'Material',
                    'material_name' => $mat->material?->name ?? 'Material',
                    'category' => $mat->material?->category ?? '',
                    'material_transaction_item_id' => $mat->material_transaction_item_id,
                    'trx_number' => $mat->trx_number,
                    'quantity_prepared' => (float) $mat->quantity_prepared,
                    'quantity_used' => (float) $mat->quantity_used,
                    'quantity_returned' => (float) $mat->quantity_returned,
                    'unit' => $mat->unit ?: ($mat->material?->unit ?? 'pcs'),
                    'notes' => $mat->notes,
                ])->values(),
            ];
        }

        return $items;
    }

    /**
     * Ambil Status Teknisi (On Duty, Libur, Masuk Malam) secara Cerdas & Sesuai Area
     */
    private function getTechnicianStatus(string $date, string|array|null $areaId, array $activeJobTechNames = []): array
    {
        $areaIds = array_values(array_unique(array_filter(
            array_map('intval', is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []))
        )));

        $normKey = static fn(?string $v): string => mb_strtolower(trim((string) $v));
        $normPhone = static fn(?string $v): string => preg_replace('/\D+/', '', (string) $v);

        $targetAreaNames = !empty($areaIds)
            ? Area::whereIn('id', $areaIds)
                ->pluck('name')
                ->map(fn($n) => $normKey((string) $n))
                ->filter()
                ->values()
                ->toArray()
            : [];

        // 1. Ambil seluruh Karyawan HR aktif divisi teknisi / maintenance / lapangan
        $allTechEmployees = Employee::query()
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->where(function ($sq) {
                $sq->where('position', 'LIKE', '%teknisi%')
                   ->orWhere('position', 'LIKE', '%maintenance%')
                   ->orWhere('position', 'LIKE', '%lapangan%')
                   ->orWhere('position', 'LIKE', '%jaringan%')
                   ->orWhere('position', 'LIKE', '%serbu%');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'position', 'area_id', 'branch', 'status']);

        // 2. Ambil seluruh User aktif dengan role teknisi / maintenance / lapangan
        $allTechUsers = User::forAssignment()
            ->where(function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where(function ($rq) {
                        $rq->where('name', 'LIKE', '%teknisi%')
                           ->orWhere('name', 'LIKE', '%maintenance%')
                           ->orWhere('name', 'LIKE', '%lapangan%')
                           ->orWhere('name', 'LIKE', '%jaringan%')
                           ->orWhere('name', 'LIKE', '%serbu%');
                    });
                })->orWhere(function ($uq) {
                    $uq->where('role', 'LIKE', '%teknisi%')
                       ->orWhere('role', 'LIKE', '%maintenance%')
                       ->orWhere('role', 'LIKE', '%lapangan%')
                       ->orWhere('role', 'LIKE', '%jaringan%')
                       ->orWhere('role', 'LIKE', '%serbu%');
                });
            })
            ->with('roles')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'is_on_duty', 'area_id', 'accessible_areas', 'role']);

        // 3. Petakan relasi silang User <-> Employee (email -> telepon -> nama)
        $empById = [];
        $empByEmail = [];
        $empByPhone = [];
        $empByName = [];
        foreach ($allTechEmployees as $emp) {
            $empById[$emp->id] = $emp;
            if ($e = $normKey($emp->email)) $empByEmail[$e] ??= $emp;
            if ($p = $normPhone($emp->phone)) $empByPhone[$p] ??= $emp;
            if ($n = $normKey($emp->name)) $empByName[$n] ??= $emp;
        }

        $userByEmail = [];
        $userByPhone = [];
        $userByName = [];
        foreach ($allTechUsers as $u) {
            if ($e = $normKey($u->email)) $userByEmail[$e] ??= $u;
            if ($p = $normPhone($u->phone)) $userByPhone[$p] ??= $u;
            if ($n = $normKey($u->name)) $userByName[$n] ??= $u;
        }

        $findLinkedUser = static function ($emp) use ($userByEmail, $userByPhone, $userByName, $normKey, $normPhone) {
            if (!$emp) return null;
            if (($e = $normKey($emp->email)) && isset($userByEmail[$e])) return $userByEmail[$e];
            if (($p = $normPhone($emp->phone)) && isset($userByPhone[$p])) return $userByPhone[$p];
            if (($n = $normKey($emp->name)) && isset($userByName[$n])) return $userByName[$n];
            return null;
        };

        $findLinkedEmp = static function ($u) use ($empByEmail, $empByPhone, $empByName, $normKey, $normPhone) {
            if (!$u) return null;
            if (($e = $normKey($u->email)) && isset($empByEmail[$e])) return $empByEmail[$e];
            if (($p = $normPhone($u->phone)) && isset($empByPhone[$p])) return $empByPhone[$p];
            if (($n = $normKey($u->name)) && isset($empByName[$n])) return $empByName[$n];
            return null;
        };

        // Klasifikasi Cerdas: Apakah Petugas Tergolong Maintenance (Bukan Teknisi)
        $isMaintenancePerson = static function ($emp, $u): bool {
            $pos = mb_strtolower(trim((string) ($emp?->position ?? '')));
            if (str_contains($pos, 'maintenance') || str_contains($pos, 'pemeliharaan')) {
                return true;
            }
            $roleCol = mb_strtolower(trim((string) ($u?->role ?? '')));
            if (str_contains($roleCol, 'maintenance') || str_contains($roleCol, 'pemeliharaan')) {
                return true;
            }
            if ($u && $u->relationLoaded('roles')) {
                foreach ($u->roles as $r) {
                    $rName = mb_strtolower((string) $r->name);
                    if (str_contains($rName, 'maintenance') || str_contains($rName, 'pemeliharaan')) {
                        return true;
                    }
                }
            }
            return false;
        };

        // 4. Filter teknisi berdasarkan penempatan Area yang dipilih
        $matchesTargetArea = static function ($emp, $u) use ($areaIds, $targetAreaNames, $normKey): bool {
            if (empty($areaIds)) {
                return true;
            }

            $primaryAreaId = $emp?->area_id ?: $u?->area_id;
            if (!empty($primaryAreaId)) {
                return in_array((int) $primaryAreaId, $areaIds, true);
            }

            if (!empty($emp?->branch)) {
                return in_array($normKey($emp->branch), $targetAreaNames, true);
            }

            if (!empty($u?->accessible_areas) && is_array($u->accessible_areas)) {
                $accIds = array_map('intval', array_filter($u->accessible_areas));
                if (!empty($accIds)) {
                    return count(array_intersect($accIds, $areaIds)) > 0;
                }
            }

            return false;
        };

        $scopedEmployees = $allTechEmployees
            ->filter(fn($emp) => $matchesTargetArea($emp, $findLinkedUser($emp)))
            ->values();

        $scopedUsers = $allTechUsers
            ->filter(fn($u) => $matchesTargetArea($findLinkedEmp($u), $u))
            ->values();

        $scopedEmpIds = $scopedEmployees->pluck('id')->all();

        $teknisiOffList = [];
        $teknisiNightList = [];
        $teknisiOnDutyList = [];

        $maintOffList = [];
        $maintNightList = [];
        $maintOnDutyList = [];

        $offKeys = [];
        $nightKeys = [];
        $onDutyKeys = [];

        // 5. Ambil WorkSchedule & Attendance hari ini KHUSUS petugas di Area tersebut
        if (!empty($scopedEmpIds)) {
            $schedules = WorkSchedule::with(['employee', 'shift'])
                ->whereDate('date', $date)
                ->whereIn('employee_id', $scopedEmpIds)
                ->get();

            foreach ($schedules as $ws) {
                $emp = $ws->employee ?? ($empById[$ws->employee_id] ?? null);
                $empName = trim((string) ($emp?->name ?? ''));
                if ($empName === '') continue;

                $k = $normKey($empName);
                $shift = $ws->shift;
                $linkedU = $findLinkedUser($emp);
                $isMaint = $isMaintenancePerson($emp, $linkedU);

                // Cek status libur / cuti / sakit
                if (
                    in_array(strtolower((string) $ws->status), ['off', 'leave', 'sick'], true)
                    || !$shift
                    || stripos((string) $shift->name, 'libur') !== false
                    || stripos((string) $shift->name, 'off') !== false
                ) {
                    if (!isset($offKeys[$k])) {
                        $offKeys[$k] = true;
                        if ($isMaint) {
                            $maintOffList[] = $empName;
                        } else {
                            $teknisiOffList[] = $empName;
                        }
                    }
                } elseif (
                    $shift
                    && ($shift->is_night_shift || stripos((string) $shift->name, 'malam') !== false || stripos((string) $shift->name, 'night') !== false)
                ) {
                    if (!isset($nightKeys[$k]) && !isset($offKeys[$k])) {
                        $nightKeys[$k] = true;
                        if ($isMaint) {
                            $maintNightList[] = $empName;
                        } else {
                            $teknisiNightList[] = $empName;
                        }
                    }
                }
            }

            $attendances = Attendance::with('employee')
                ->whereDate('date', $date)
                ->whereIn('status', ['leave', 'sick', 'off'])
                ->whereIn('employee_id', $scopedEmpIds)
                ->get();

            foreach ($attendances as $att) {
                $emp = $att->employee ?? ($empById[$att->employee_id] ?? null);
                $eName = trim((string) ($emp?->name ?? ''));
                if ($eName === '') continue;

                $k = $normKey($eName);
                $linkedU = $findLinkedUser($emp);
                $isMaint = $isMaintenancePerson($emp, $linkedU);

                if (!isset($offKeys[$k])) {
                    $offKeys[$k] = true;
                    if ($isMaint) {
                        $maintOffList[] = $eName;
                    } else {
                        $teknisiOffList[] = $eName;
                    }
                }
                if (isset($nightKeys[$k])) {
                    unset($nightKeys[$k]);
                    $teknisiNightList = array_values(array_filter($teknisiNightList, fn($n) => $normKey($n) !== $k));
                    $maintNightList = array_values(array_filter($maintNightList, fn($n) => $normKey($n) !== $k));
                }
            }
        }

        // 6. Petugas yang bertugas / On Duty (sesuai Area, tidak sedang libur/shift malam)
        $activeJobKeys = [];
        foreach ($activeJobTechNames as $aName) {
            $trimmed = trim((string) $aName);
            if ($trimmed !== '' && !in_array($trimmed, ['-', 'Belum Ditugaskan', 'Tim Maintenance Jalur', 'Petugas Survey', 'Petugas CBP', 'Teknisi Instalasi'], true)) {
                $activeJobKeys[$normKey($trimmed)] = $trimmed;
            }
        }

        foreach ($scopedUsers as $tech) {
            $tName = trim((string) $tech->name);
            if ($tName === '') continue;

            $k = $normKey($tName);
            $linkedEmp = $findLinkedEmp($tech);
            $empKey = $linkedEmp ? $normKey($linkedEmp->name) : null;

            $isOff = isset($offKeys[$k]) || ($empKey && isset($offKeys[$empKey]));
            $isNight = isset($nightKeys[$k]) || ($empKey && isset($nightKeys[$empKey]));
            $isMaint = $isMaintenancePerson($linkedEmp, $tech);

            if (!$isOff && (!$isNight || isset($activeJobKeys[$k])) && !isset($onDutyKeys[$k])) {
                $onDutyKeys[$k] = true;
                if ($empKey) $onDutyKeys[$empKey] = true;
                if ($isMaint) {
                    $maintOnDutyList[] = $tName;
                } else {
                    $teknisiOnDutyList[] = $tName;
                }
            }
        }

        foreach ($scopedEmployees as $emp) {
            $eName = trim((string) $emp->name);
            if ($eName === '') continue;

            $k = $normKey($eName);
            $linkedUser = $findLinkedUser($emp);
            $userKey = $linkedUser ? $normKey($linkedUser->name) : null;

            $alreadyAdded = isset($onDutyKeys[$k]) || ($userKey && isset($onDutyKeys[$userKey]));
            $isOff = isset($offKeys[$k]) || ($userKey && isset($offKeys[$userKey]));
            $isNight = isset($nightKeys[$k]) || ($userKey && isset($nightKeys[$userKey]));
            $isMaint = $isMaintenancePerson($emp, $linkedUser);

            if (!$alreadyAdded && !$isOff && (!$isNight || isset($activeJobKeys[$k]))) {
                $onDutyKeys[$k] = true;
                if ($userKey) $onDutyKeys[$userKey] = true;
                if ($isMaint) {
                    $maintOnDutyList[] = $eName;
                } else {
                    $teknisiOnDutyList[] = $eName;
                }
            }
        }

        // Tangkap nama dari tugas aktif hari ini yang belum tercatat
        foreach ($activeJobTechNames as $aName) {
            $trimmed = trim((string) $aName);
            if ($trimmed === '' || in_array($trimmed, ['-', 'Belum Ditugaskan', 'Tim Maintenance Jalur', 'Petugas Survey', 'Petugas CBP', 'Teknisi Instalasi'], true)) {
                continue;
            }
            $k = $normKey($trimmed);
            if (!isset($onDutyKeys[$k])) {
                $linkedEmp = $empByName[$k] ?? null;
                $linkedU = $userByName[$k] ?? null;
                $isMaint = $isMaintenancePerson($linkedEmp, $linkedU);
                $onDutyKeys[$k] = true;
                if ($isMaint) {
                    $maintOnDutyList[] = $trimmed;
                } else {
                    $teknisiOnDutyList[] = $trimmed;
                }
            }
        }

        $cleanUnique = static fn(array $arr): array => array_values(array_unique(array_filter($arr)));

        $rawTekDuty = $cleanUnique($teknisiOnDutyList);
        $rawMaintDuty = $cleanUnique($maintOnDutyList);

        // Pastikan nama maintenance dan teknisi terpisah dengan benar
        $maintDutyKeys = array_flip(array_map($normKey, $rawMaintDuty));
        $tekDuty = array_values(array_filter($rawTekDuty, fn($n) => !isset($maintDutyKeys[$normKey($n)])));
        $maintDuty = $rawMaintDuty;

        // Siapkan lookup semua nama yang aktif bertugas hari ini
        $allDutyNormKeys = array_flip(array_merge(
            array_map($normKey, $tekDuty),
            array_map($normKey, $maintDuty)
        ));

        // Shift Malam: Petugas yang bertugas aktif TIDAK BOLEH masuk ke shift malam
        $rawTekNight = $cleanUnique($teknisiNightList);
        $rawMaintNight = $cleanUnique($maintNightList);
        $tekNight = array_values(array_filter($rawTekNight, fn($n) => !isset($allDutyNormKeys[$normKey($n)])));
        $maintNight = array_values(array_filter($rawMaintNight, fn($n) => !isset($allDutyNormKeys[$normKey($n)])));

        // Siapkan lookup semua nama yang bertugas atau shift malam
        $allWorkingNormKeys = $allDutyNormKeys + array_flip(array_merge(
            array_map($normKey, $tekNight),
            array_map($normKey, $maintNight)
        ));

        // Libur / Cuti / Off: Petugas yang sedang bertugas atau shift malam TIDAK BOLEH masuk ke Libur / Cuti
        $rawTekOff = $cleanUnique($teknisiOffList);
        $rawMaintOff = $cleanUnique($maintOffList);
        $tekOff = array_values(array_filter($rawTekOff, fn($n) => !isset($allWorkingNormKeys[$normKey($n)])));
        $maintOff = array_values(array_filter($rawMaintOff, fn($n) => !isset($allWorkingNormKeys[$normKey($n)])));

        // Pastikan juga maintenance libur tidak terduplikasi di teknisi libur
        $maintOffNormKeys = array_flip(array_map($normKey, $maintOff));
        $tekOff = array_values(array_filter($tekOff, fn($n) => !isset($maintOffNormKeys[$normKey($n)])));

        return [
            // Top-level teknisi (agar petugas maintenance seperti Herli tidak tercampur ke teknisi)
            'on_duty' => $tekDuty,
            'off' => $tekOff,
            'night' => $tekNight,

            'teknisi' => [
                'on_duty' => $tekDuty,
                'off' => $tekOff,
                'night' => $tekNight,
            ],
            'maintenance' => [
                'on_duty' => $maintDuty,
                'off' => $maintOff,
                'night' => $maintNight,
            ],
            'all' => [
                'on_duty' => $cleanUnique(array_merge($tekDuty, $maintDuty)),
                'off' => $cleanUnique(array_merge($tekOff, $maintOff)),
                'night' => $cleanUnique(array_merge($tekNight, $maintNight)),
            ],
        ];
    }

    /**
     * Susun Teks Format WhatsApp Sesuai Template Permintaan User
     */
    private function buildWhatsAppTemplate(
        string $areaLabel,
        string $indonesianDayDate,
        array $technicianStatus,
        array $gangguanList,
        array $pgjList,
        array $pemasanganList,
        array $surveyList,
        array $cbpList,
        array $maintenanceList,
        array $summary,
        string $customNote
    ): string {
        $lines = [];

        // Header
        $lines[] = "📡 *PROGRESS OPERASIONAL*";
        $lines[] = "🏢 *{$areaLabel}*";
        $lines[] = $indonesianDayDate;
        $lines[] = "";

        // Petugas Bertugas
        $tekStatus = $technicianStatus['teknisi'] ?? [
            'on_duty' => $technicianStatus['on_duty'] ?? [],
            'off' => $technicianStatus['off'] ?? [],
            'night' => $technicianStatus['night'] ?? [],
        ];
        $maintStatus = $technicianStatus['maintenance'] ?? [
            'on_duty' => [],
            'off' => [],
            'night' => [],
        ];

        $tekDutyStr = !empty($tekStatus['on_duty']) ? implode(', ', $tekStatus['on_duty']) : '-';
        $maintDutyStr = !empty($maintStatus['on_duty']) ? implode(', ', $maintStatus['on_duty']) : '-';

        $lines[] = "👷‍♂️ *PETUGAS BERTUGAS :*";
        $lines[] = "• Teknisi: {$tekDutyStr}";
        $lines[] = "• Maintenance: {$maintDutyStr}";

        // 1. GANGGUAN PGS/PGK
        if (!empty($gangguanList)) {
            $lines[] = "";
            $lines[] = "🆘 *GANGGUAN PGS/PGK*";
            // Group by teknisi
            $groupedGangguan = collect($gangguanList)->groupBy('technicians');
            foreach ($groupedGangguan as $techName => $tItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi :* {$techName}";
                $idx = 1;
                foreach ($tItems as $g) {
                    $cName = $g['customer_name'] ?? '-';
                    $addr = $g['address'] ?? '-';
                    $odp = $g['odp'] ?? '-';
                    $issue = $g['issue'] ?? '-';
                    $hp = $g['phone'] ?? '-';
                    $dur = $g['duration'] ?? '-';
                    $stat = $g['status_label'] ?? '-';

                    $lines[] = "{$idx}. Nama Pelanggan : {$cName}";
                    $lines[] = "   Alamat : {$addr}";
                    $lines[] = "   ODP\t  : {$odp}";
                    $lines[] = "   Kendala: {$issue}";
                    $lines[] = "   HP: {$hp}";
                    $lines[] = "   Durasi: {$dur}";
                    $lines[] = "   Status: {$stat}";
                    $idx++;
                }
            }
        }

        // 2. GANGGUAN JALUR (PGJ) - MANDIRI
        if (!empty($pgjList)) {
            $lines[] = "";
            $lines[] = "⚡ *GANGGUAN JALUR (PGJ)*";
            $groupedPgj = collect($pgjList)->groupBy('technicians');
            foreach ($groupedPgj as $techName => $pItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Jalur/Teknisi :* {$techName}";
                $idx = 1;
                foreach ($pItems as $pg) {
                    $segmen = !empty($pg['segment_type']) ? " ({$pg['segment_type']})" : "";
                    $ticket = $pg['ticket_number'] ?? '-';
                    $loc = $pg['location_name'] ?? '-';
                    $target = $pg['infra_target'] ?? '-';
                    $issue = $pg['issue'] ?? '-';
                    $dur = $pg['duration'] ?? '-';
                    $stat = $pg['status_label'] ?? '-';

                    $lines[] = "{$idx}. No. Tiket: {$ticket}{$segmen}";
                    $lines[] = "   Lokasi : {$loc}";
                    $lines[] = "   Target : {$target}";
                    $lines[] = "   Kendala: {$issue}";
                    if (!empty($pg['affected_customers_count']) && $pg['affected_customers_count'] > 0) {
                        $lines[] = "   Dampak : {$pg['affected_customers_count']} Pelanggan";
                    }
                    if (!empty($pg['optical_power_after']) || !empty($pg['optical_power_before'])) {
                        $lines[] = "   Redaman: " . ($pg['optical_power_after'] ?: $pg['optical_power_before']);
                    }
                    if (!empty($pg['action_taken']) && $pg['action_taken'] !== '-') {
                        $lines[] = "   Tindakan: {$pg['action_taken']}";
                    }
                    $lines[] = "   Durasi : {$dur}";
                    $lines[] = "   Status : {$stat}";
                    $idx++;
                }
            }
        }

        // 3. PEMASANGAN BARU
        if (!empty($pemasanganList)) {
            $lines[] = "";
            $lines[] = "🛜 *PEMASANGAN BARU*";
            $groupedPasang = collect($pemasanganList)->groupBy('technicians');
            foreach ($groupedPasang as $techName => $pItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi :* {$techName}";
                $idx = 1;
                foreach ($pItems as $p) {
                    $cName = $p['customer_name'] ?? '-';
                    $detail = $p['detail'] ?? '-';
                    $odp = $p['odp'] ?? '-';
                    $hp = $p['phone'] ?? '-';
                    $dur = $p['duration'] ?? '-';
                    $stat = $p['status_label'] ?? '-';

                    $lines[] = "{$idx}. Nama Pelanggan : {$cName}";
                    $lines[] = "   Data Detail : {$detail}";
                    $lines[] = "   ODP\t  : {$odp}";
                    $lines[] = "   HP: {$hp}";
                    $lines[] = "   Durasi: {$dur}";
                    $lines[] = "   Status: {$stat}";
                    $idx++;
                }
            }
        }

        // 3. SURVEY PELANGGAN BARU
        if (!empty($surveyList)) {
            $lines[] = "";
            $lines[] = "🔎 *SURVEY PELANGGAN BARU*";
            $groupedSurvey = collect($surveyList)->groupBy('technicians');
            foreach ($groupedSurvey as $techName => $sItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Survey/Teknisi :* {$techName}";
                $idx = 1;
                foreach ($sItems as $s) {
                    $cName = $s['customer_name'] ?? '-';
                    $detail = $s['detail'] ?? '-';
                    $odp = $s['odp'] ?? '-';
                    $hp = $s['phone'] ?? '-';
                    $stat = $s['status_label'] ?? '-';

                    $lines[] = "{$idx}. Nama Pelanggan : {$cName}";
                    $lines[] = "   Data Detail : {$detail}";
                    $lines[] = "   ODP\t  : {$odp}";
                    $lines[] = "   HP: {$hp}";
                    $lines[] = "   Status: {$stat}";
                    $idx++;
                }
            }
        }

        // 4. PENCABUTAN / CBP
        if (!empty($cbpList)) {
            $lines[] = "";
            $lines[] = "🔌 *PENCABUTAN/CBP*";
            $groupedCbp = collect($cbpList)->groupBy('technicians');
            foreach ($groupedCbp as $techName => $cItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi/CBP :* {$techName}";
                $idx = 1;
                foreach ($cItems as $c) {
                    $cName = $c['customer_name'] ?? '-';
                    $addr = $c['address'] ?? '-';
                    $odp = $c['odp'] ?? '-';
                    $reason = $c['reason'] ?? '-';
                    $hp = $c['phone'] ?? '-';
                    $stat = $c['status_label'] ?? '-';

                    $lines[] = "{$idx}. Nama Pelanggan : {$cName}";
                    $lines[] = "   Alamat : {$addr}";
                    $lines[] = "   ODP\t  : {$odp}";
                    $lines[] = "   Alasan : {$reason}";
                    $lines[] = "   HP: {$hp}";
                    $lines[] = "   Status: {$stat}";
                    $idx++;
                }
            }
        }

        // 6. PERAPIHAN & PENGECEKAN JALUR (ODP / ODC / JALUR UTAMA)
        if (!empty($maintenanceList)) {
            $lines[] = "";
            $lines[] = "🛠️ *PERAPIHAN & PENGECEKAN JALUR (ODP/ODC/JALUR UTAMA)*";
            $groupedMnt = collect($maintenanceList)->groupBy('technicians');
            foreach ($groupedMnt as $techName => $mItems) {
                $lines[] = "";
                $lines[] = "🛠️ *Petugas Maintenance/Teknisi :* {$techName}";
                $idx = 1;
                foreach ($mItems as $m) {
                    $catLabel = $m['category_label'] ?? '-';
                    $title = $m['title'] ?? '-';
                    $loc = !empty($m['location']) ? " - {$m['location']}" : '';
                    $lines[] = "{$idx}. Kegiatan : {$catLabel}";
                    $lines[] = "   Target/Lokasi: {$title}{$loc}";
                    if (!empty($m['optical_power_after']) || !empty($m['optical_power_before'])) {
                        $lines[] = "   Redaman: " . ($m['optical_power_after'] ?: $m['optical_power_before']);
                    }
                    if (!empty($m['action_taken'])) {
                        $lines[] = "   Tindakan: {$m['action_taken']}";
                    } elseif (!empty($m['description'])) {
                        $lines[] = "   Rencana : {$m['description']}";
                    }
                    if (!empty($m['materials']) && count($m['materials']) > 0) {
                        $matParts = [];
                        foreach ($m['materials'] as $mat) {
                            $q = ($mat['quantity_used'] ?? 0) > 0 ? $mat['quantity_used'] : ($mat['quantity_prepared'] ?? 0);
                            $matParts[] = "{$q} " . ($mat['unit'] ?? '') . " " . ($mat['material_name'] ?? '');
                        }
                        if (!empty($matParts)) {
                            $lines[] = "   Material: " . implode(', ', $matParts);
                        }
                    }
                    $lines[] = "   Durasi  : " . ($m['duration'] ?? '-');
                    $lines[] = "   Status  : " . ($m['status_label'] ?? '-');
                    $idx++;
                }
            }
        }

        // 7. RINGKASAN HARI INI
        $lines[] = "📊 *RINGKASAN HARI INI*";
        $lines[] = "* Gangguan PGS/PGK: " . ($summary['gangguan'] ?? 0) . " (" . ($summary['gangguan_done'] ?? 0) . " Selesai, " . ($summary['gangguan_pending'] ?? 0) . " Pending)";
        $lines[] = "* Gangguan Jalur (PGJ): " . ($summary['pgj'] ?? 0) . " (" . ($summary['pgj_done'] ?? 0) . " Selesai, " . ($summary['pgj_pending'] ?? 0) . " Pending)";
        $lines[] = "* Pemasangan: " . ($summary['pemasangan'] ?? 0) . " (" . ($summary['pemasangan_done'] ?? 0) . " Selesai, " . ($summary['pemasangan_pending'] ?? 0) . " Pending)";
        $lines[] = "* Survey: " . ($summary['survey'] ?? 0) . " (" . ($summary['survey_feasible'] ?? 0) . " Selesai, " . ($summary['survey_pending'] ?? 0) . " Tunggu)";
        $cbpPendingStr = ($summary['cbp_assigned'] ?? 0) > 0 
            ? (($summary['cbp_done'] ?? 0) . " Selesai, " . ($summary['cbp_assigned'] ?? 0) . " Terjadwal" . (($summary['cbp_pending'] ?? 0) > 0 ? ", " . ($summary['cbp_pending'] ?? 0) . " Pending" : ""))
            : (($summary['cbp_done'] ?? 0) . " Selesai, " . ($summary['cbp_pending'] ?? 0) . " Pending");
        $lines[] = "* Pencabutan: " . ($summary['cbp'] ?? 0) . " ({$cbpPendingStr})";
        $lines[] = "* Perapihan/Cek Jalur: " . ($summary['maintenance'] ?? 0) . " (" . ($summary['maintenance_done'] ?? 0) . " Selesai, " . ($summary['maintenance_pending'] ?? 0) . " Pending)";
        $lines[] = "";

        // 8. STATUS TEKNISI
        $lines[] = "👷 *STATUS TEKNISI*";
        $tekLiburStr = !empty($tekStatus['off']) ? implode(', ', $tekStatus['off']) : '-';
        $tekMalamStr = !empty($tekStatus['night']) ? implode(', ', $tekStatus['night']) : '-';
        $lines[] = "Teknisi Libur: {$tekLiburStr}";
        $lines[] = "Teknisi Masuk Malam: {$tekMalamStr}";
        $lines[] = "";

        // 9. STATUS MAINTENANCE
        $lines[] = "🛠️ *STATUS MAINTENANCE*";
        $maintLiburStr = !empty($maintStatus['off']) ? implode(', ', $maintStatus['off']) : '-';
        $lines[] = "Maintenance Bertugas: {$maintDutyStr}";
        $lines[] = "Maintenance Libur: {$maintLiburStr}";
        if (!empty($maintStatus['night'])) {
            $maintMalamStr = implode(', ', $maintStatus['night']);
            $lines[] = "Maintenance Masuk Malam: {$maintMalamStr}";
        }
        $lines[] = "";

        // 10. NOTE
        $lines[] = "📝 *NOTE*";
        $lines[] = $customNote ?: "Tetap semangat dan jaga keselamatan kerja! 💪";

        return implode("\n", $lines);
    }

    /**
     * Susun Teks Format WhatsApp Khusus Gangguan Jalur (PGJ) - Mandiri
     */
    private function buildPgjWhatsAppTemplate(
        string $areaLabel,
        string $indonesianDayDate,
        array $technicianStatus,
        array $pgjList,
        array $summary,
        string $customNote
    ): string {
        $lines = [];

        $lines[] = "⚡ *PROGRESS GANGGUAN JALUR (PGJ)*";
        $lines[] = "🏢 *{$areaLabel}*";
        $lines[] = $indonesianDayDate;
        $lines[] = "";

        $maintStatus = $technicianStatus['maintenance'] ?? [];
        $tekStatus = $technicianStatus['teknisi'] ?? [];
        
        $assignedTechs = collect($pgjList)->pluck('technicians')->flatMap(function ($t) {
            return explode(',', (string)($t ?? ''));
        })->map(fn($t) => trim($t))->filter(fn($t) => $t && !in_array($t, ['Belum Ditugaskan', '-']))->unique()->values()->all();
        
        $dutyStr = !empty($assignedTechs) ? implode(', ', $assignedTechs) : (!empty($maintStatus['on_duty']) ? implode(', ', $maintStatus['on_duty']) : (!empty($tekStatus['on_duty']) ? implode(', ', $tekStatus['on_duty']) : '-'));

        $lines[] = "👷‍♂️ *TIM / PETUGAS JALUR BERTUGAS :*";
        $lines[] = "• Petugas: {$dutyStr}";

        if (!empty($pgjList)) {
            $lines[] = "";
            $lines[] = "⚡ *DETAIL PENANGANAN JALUR (PGJ)*";
            $groupedPgj = collect($pgjList)->groupBy('technicians');
            foreach ($groupedPgj as $techName => $pItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Jalur/Teknisi :* {$techName}";
                $idx = 1;
                foreach ($pItems as $pg) {
                    $segmen = !empty($pg['segment_type']) ? " ({$pg['segment_type']})" : "";
                    $ticket = $pg['ticket_number'] ?? '-';
                    $loc = $pg['location_name'] ?? '-';
                    $target = $pg['infra_target'] ?? '-';
                    $issue = $pg['issue'] ?? '-';
                    $dur = $pg['duration'] ?? '-';
                    $stat = $pg['status_label'] ?? '-';

                    $lines[] = "{$idx}. No. Tiket: {$ticket}{$segmen}";
                    $lines[] = "   Lokasi : {$loc}";
                    $lines[] = "   Target : {$target}";
                    $lines[] = "   Kendala: {$issue}";
                    if (!empty($pg['affected_customers_count']) && $pg['affected_customers_count'] > 0) {
                        $lines[] = "   Dampak : {$pg['affected_customers_count']} Pelanggan";
                    }
                    if (!empty($pg['optical_power_after']) || !empty($pg['optical_power_before'])) {
                        $lines[] = "   Redaman: " . ($pg['optical_power_after'] ?: $pg['optical_power_before']);
                    }
                    if (!empty($pg['action_taken']) && $pg['action_taken'] !== '-') {
                        $lines[] = "   Tindakan: {$pg['action_taken']}";
                    }
                    $lines[] = "   Durasi : {$dur}";
                    $lines[] = "   Status : {$stat}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "";
            $lines[] = "✨ *KONDISI JALUR :* Normal / Tidak ada laporan gangguan jalur aktif hari ini.";
        }

        $lines[] = "";
        $lines[] = "📊 *RINGKASAN PGJ HARI INI*";
        $pgjTotal = $summary['pgj'] ?? 0;
        $pgjDone = $summary['pgj_done'] ?? 0;
        $pgjInProg = $summary['pgj_in_progress'] ?? 0;
        $pgjPending = $summary['pgj_pending'] ?? 0;
        $lines[] = "* Total Tiket Jalur: {$pgjTotal}";
        $lines[] = "* Selesai (Done)   : {$pgjDone}";
        if ($pgjInProg > 0) {
            $lines[] = "* Sedang Dikerjakan: {$pgjInProg}";
        }
        $lines[] = "* Pending/Antrean  : {$pgjPending}";

        $lines[] = "";
        $lines[] = "📝 *NOTE*";
        $lines[] = $customNote ?: "Tetap utamakan keselamatan kerja dan pantau terus stabilitas redaman kabel optik! ⚡";

        return implode("\n", $lines);
    }

    /**
     * Susun Teks Format WhatsApp Khusus Gangguan PGS/PGK - Mandiri
     */
    private function buildGangguanWhatsAppTemplate(
        string $areaLabel,
        string $indonesianDayDate,
        array $technicianStatus,
        array $gangguanList,
        array $summary,
        string $customNote
    ): string {
        $lines = [];

        $lines[] = "🆘 *PROGRESS GANGGUAN PELANGGAN (PGS/PGK)*";
        $lines[] = "🏢 *{$areaLabel}*";
        $lines[] = $indonesianDayDate;
        $lines[] = "";

        $tekStatus = $technicianStatus['teknisi'] ?? [];
        $tekDutyStr = !empty($tekStatus['on_duty']) ? implode(', ', $tekStatus['on_duty']) : '-';
        $lines[] = "👷‍♂️ *TEKNISI GANGGUAN BERTUGAS :*";
        $lines[] = "• Teknisi: {$tekDutyStr}";

        if (!empty($gangguanList)) {
            $lines[] = "";
            $lines[] = "🆘 *DAFTAR GANGGUAN PGS/PGK*";
            $groupedGangguan = collect($gangguanList)->groupBy('technicians');
            foreach ($groupedGangguan as $techName => $tItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi :* {$techName}";
                $idx = 1;
                foreach ($tItems as $g) {
                    $cName = $g['customer_name'] ?? '-';
                    $addr = $g['address'] ?? '-';
                    $odp = $g['odp'] ?? '-';
                    $issue = $g['issue'] ?? '-';
                    $hp = $g['phone'] ?? '-';
                    $dur = $g['duration'] ?? '-';
                    $stat = $g['status_label'] ?? '-';

                    $lines[] = "{$idx}. Nama Pelanggan : {$cName}";
                    $lines[] = "   Alamat : {$addr}";
                    $lines[] = "   ODP\t  : {$odp}";
                    $lines[] = "   Kendala: {$issue}";
                    $lines[] = "   HP: {$hp}";
                    $lines[] = "   Durasi: {$dur}";
                    $lines[] = "   Status: {$stat}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "";
            $lines[] = "✨ *STATUS :* Tidak ada gangguan pelanggan (PGS/PGK) aktif hari ini.";
        }

        $lines[] = "";
        $lines[] = "📊 *RINGKASAN PGS/PGK HARI INI*";
        $lines[] = "* Total Gangguan : " . ($summary['gangguan'] ?? 0);
        $lines[] = "* Selesai (Done) : " . ($summary['gangguan_done'] ?? 0);
        $lines[] = "* Pending        : " . ($summary['gangguan_pending'] ?? 0);

        $lines[] = "";
        $lines[] = "📝 *NOTE*";
        $lines[] = $customNote ?: "Tetap semangat dan utamakan kepuasan pelanggan! 💪";

        return implode("\n", $lines);
    }

    /**
     * Susun Teks Format WhatsApp Khusus Pemasangan Baru (PSB) - Mandiri
     */
    private function buildPemasanganWhatsAppTemplate(
        string $areaLabel,
        string $indonesianDayDate,
        array $technicianStatus,
        array $pemasanganList,
        array $summary,
        string $customNote
    ): string {
        $lines = [];

        $lines[] = "🛜 *PROGRESS PEMASANGAN BARU (PSB)*";
        $lines[] = "🏢 *{$areaLabel}*";
        $lines[] = $indonesianDayDate;
        $lines[] = "";

        $tekStatus = $technicianStatus['teknisi'] ?? [];
        $tekDutyStr = !empty($tekStatus['on_duty']) ? implode(', ', $tekStatus['on_duty']) : '-';
        $lines[] = "👷‍♂️ *TEKNISI INSTALASI BERTUGAS :*";
        $lines[] = "• Teknisi: {$tekDutyStr}";

        if (!empty($pemasanganList)) {
            $lines[] = "";
            $lines[] = "🛜 *DAFTAR PEMASANGAN BARU*";
            $groupedPasang = collect($pemasanganList)->groupBy('technicians');
            foreach ($groupedPasang as $techName => $pItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi :* {$techName}";
                $idx = 1;
                foreach ($pItems as $p) {
                    $cName = $p['customer_name'] ?? '-';
                    $detail = $p['detail'] ?? '-';
                    $odp = $p['odp'] ?? '-';
                    $addr = $p['address'] ?? ($p['detail'] ?? '-');
                    $hp = $p['phone'] ?? '-';
                    $dur = $p['duration'] ?? '-';
                    $stat = $p['status_label'] ?? '-';

                    $lines[] = "{$idx}. Nama Pelanggan : {$cName}";
                    $lines[] = "   Data Detail : {$detail}";
                    $lines[] = "   ODP\t  : {$odp}";
                    $lines[] = "   Alamat : {$addr}";
                    $lines[] = "   HP: {$hp}";
                    $lines[] = "   Durasi: {$dur}";
                    $lines[] = "   Status: {$stat}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "";
            $lines[] = "✨ *STATUS :* Tidak ada jadwal pemasangan baru (PSB) hari ini.";
        }

        $lines[] = "";
        $lines[] = "📊 *RINGKASAN PEMASANGAN HARI INI*";
        $lines[] = "* Total Pemasangan: " . ($summary['pemasangan'] ?? 0);
        $lines[] = "* Selesai (Aktif) : " . ($summary['pemasangan_done'] ?? 0);
        $lines[] = "* Pending         : " . ($summary['pemasangan_pending'] ?? 0);

        $lines[] = "";
        $lines[] = "📝 *NOTE*";
        $lines[] = $customNote ?: "Pastikan kerapihan instalasi kabel & redaman optik standar sebelum aktivasi! 🛜";

        return implode("\n", $lines);
    }
}
