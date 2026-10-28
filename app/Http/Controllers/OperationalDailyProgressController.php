<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Area;
use App\Models\Odp;
use App\Models\Odc;
use App\Models\NetworkMaintenance;
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
        if ($areaId) {
            $targetAreaIds = [(int) $areaId];
        } elseif ($accessibleAreaIds !== null) {
            $targetAreaIds = $accessibleAreaIds;
        }

        // Ambil Data Area sesuai scope
        $areas = \App\Services\DataScopeService::getScopedAreas($user, 'operational_progress');
        $selectedArea = $areaId ? Area::find($areaId) : null;
        $areaLabel = $selectedArea ? $selectedArea->name : ($accessibleAreaIds !== null ? 'Wilayah Ditugaskan' : 'Semua Wilayah / Area');

        // 1. DATA GANGGUAN (PGS, PGK, PGJ)
        $gangguanList = $this->getGangguanData($date, $targetAreaIds);

        // 2. DATA PEMASANGAN BARU (INSTALASI)
        $pemasanganList = $this->getPemasanganData($date, $targetAreaIds);

        // 3. DATA SURVEY PELANGGAN BARU
        $surveyList = $this->getSurveyData($date, $targetAreaIds);

        // 4. DATA PENCABUTAN / CBP
        $cbpList = $this->getCbpData($date, $targetAreaIds);

        // 5. DATA PEMELIHARAAN & PERAPIHAN JALUR (ODP, ODC, JALUR UTAMA - MANDIRI TANPA PGS/PGK/PGJ)
        $maintenanceList = $this->getMaintenanceData($date, $targetAreaIds);

        // Kumpulkan teknisi aktif dari seluruh penugasan operasional hari ini
        $activeJobTechNames = [];
        foreach (array_merge($gangguanList, $pemasanganList, $surveyList, $cbpList, $maintenanceList) as $taskItem) {
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

        // 6. STATUS TEKNISI (On Duty, Libur, Masuk Malam)
        $technicianStatus = $this->getTechnicianStatus($date, $targetAreaIds, $activeJobTechNames);

        // 7. RINGKASAN DATA CERDAS & AUTO
        $summary = [
            'gangguan' => count($gangguanList),
            'gangguan_done' => collect($gangguanList)->where('is_done', true)->count(),
            'gangguan_in_progress' => collect($gangguanList)->where('is_in_progress', true)->count(),
            'gangguan_rescheduled' => collect($gangguanList)->where('is_rescheduled', true)->count(),
            'gangguan_pending' => collect($gangguanList)->where('is_done', false)->where('is_in_progress', false)->count(),

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
            'cbp_pending' => collect($cbpList)->where('is_done', false)->where('is_in_progress', false)->count(),

            'maintenance' => count($maintenanceList),
            'maintenance_done' => collect($maintenanceList)->where('is_done', true)->count(),
            'maintenance_in_progress' => collect($maintenanceList)->where('is_in_progress', true)->count(),
            'maintenance_rescheduled' => collect($maintenanceList)->where('is_rescheduled', true)->count(),
            'maintenance_pending' => collect($maintenanceList)->where('is_done', false)->where('is_in_progress', false)->count(),
        ];

        // 8. HASILKAN FORMAT TEKS WHATSAPP SESUAI TEMPLATE
        $waText = $this->buildWhatsAppTemplate(
            $areaLabel,
            $indonesianDayDate,
            $technicianStatus,
            $gangguanList,
            $pemasanganList,
            $surveyList,
            $cbpList,
            $maintenanceList,
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

        // Ambil Daftar Teknisi Lapangan untuk Penugasan Cepat
        $availableTechnicians = User::where(function ($q) {
            $q->whereHas('roles', fn($r) => $r->whereIn('name', ['teknisi', 'kepala teknisi', 'maintenance', 'teknisi lapangan']))
              ->orWhereIn('role', ['teknisi', 'kepala teknisi', 'maintenance']);
        })->orderBy('name')->get(['id', 'name', 'phone']);

        $availableOdps = Odp::select('id', 'name', 'area_id')->orderBy('name')->limit(500)->get();
        $availableOdcs = Odc::select('id', 'name', 'area_id')->orderBy('name')->limit(150)->get();

        return Inertia::render('Operational/DailyProgress', [
            'defaultTab' => $request->input('tab', 'gangguan'),
            'filters' => [
                'date' => $date,
                'area_id' => $areaId ? (int) $areaId : null,
                'note' => $customNote,
            ],
            'areas' => $areas,
            'indonesianDate' => $indonesianDayDate,
            'areaLabel' => $areaLabel,
            'gangguanList' => $gangguanList,
            'pemasanganList' => $pemasanganList,
            'surveyList' => $surveyList,
            'cbpList' => $cbpList,
            'maintenanceList' => $maintenanceList,
            'technicianStatus' => $technicianStatus,
            'availableTechnicians' => $availableTechnicians,
            'availableOdps' => $availableOdps,
            'availableOdcs' => $availableOdcs,
            'summary' => $summary,
            'waText' => $waText,
            'waGatewayStatus' => $waGatewayStatus,
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

            if (!empty($res['status']) && $res['status'] === 'success') {
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

        // 3. PGJ (Gangguan Jalur / Backbone / ODC)
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
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Tim Maintenance Jalur';

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
            }

            $infraName = $pgj->odp?->name ?: ($pgj->odc?->name ?: ($pgj->olt?->name ?: ($pgj->location_name ?: '-')));

            $items[] = [
                'type' => 'PGJ',
                'source' => 'PGJ',
                'ticket_number' => $pgj->ticket_number,
                'customer_name' => '⚠️ Jalur/Infra: ' . ($pgj->location_name ?: $pgj->segment_type),
                'address' => $pgj->area?->name ?? 'Wilayah Jalur',
                'odp' => $infraName,
                'issue' => $pgj->reported_issue ?: 'Gangguan Jalur FTTH',
                'phone' => '-',
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
            $techNames = $installScheds->pluck('technician.name')->filter()->unique()->toArray();
            $techniciansStr = !empty($techNames) ? implode(', ', $techNames) : 'Teknisi Instalasi';

            $pkgName = $cust->package?->name ?? 'Paket Internet';

            $items[] = [
                'customer_id' => $cust->id,
                'customer_name' => $cust->name,
                'customer_code' => $cust->customer_id ?? $cust->code ?? '-',
                'detail' => "{$pkgName} - {$cust->address}",
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
            $schedTechs = $cust->technicianSchedules->where('type', 'survey')->pluck('technician.name')->filter()->toArray();
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

        $cbpQuery = CbpRequest::with(['customer.ont.odp', 'technicians', 'creator', 'dispatcher'])
            ->where(function ($q) use ($date) {
                $q->whereDate('completed_at', $date)
                  ->orWhereDate('sent_to_schedule_at', $date)
                  ->orWhereDate('created_at', $date);
            });

        if (!empty($areaIds)) {
            $cbpQuery->whereHas('customer', fn($q) => $q->whereIn('area_id', $areaIds));
        }

        foreach ($cbpQuery->get() as $cbp) {
            $cust = $cbp->customer;
            $techNames = $cbp->technicians->pluck('name')->toArray();
            if (empty($techNames) && !empty($cbp->assigned_to)) {
                $assignedUser = User::find($cbp->assigned_to);
                if ($assignedUser) $techNames[] = $assignedUser->name;
            }
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Petugas CBP';

            $isDone = ($cbp->status === 'completed') || !empty($cbp->completed_at);
            $isInProgress = ($cbp->status === 'in_progress');
            $isRescheduled = ($cbp->status === 'rescheduled');

            if ($isDone) {
                $statusLabel = '✅ Selesai';
                $statusClass = 'completed';
            } elseif ($isInProgress) {
                $statusLabel = '🛠️ Sedang Dikerjakan';
                $statusClass = 'in_progress';
            } elseif ($isRescheduled) {
                $statusLabel = '🗓️ Reschedule';
                $statusClass = 'rescheduled';
            } else {
                $statusLabel = '⏳ Pending';
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
                'customer_name' => $custName,
                'detail' => "{$custName} - {$address} (ODP: {$odpName}, Alasan: {$reason})",
                'address' => $address,
                'odp' => $odpName,
                'reason' => $reason,
                'phone' => $cust?->phone ?? '-',
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
     * Ambil Data Pemeliharaan & Perapihan Jalur (ODP, ODC, Jalur Utama) secara Cerdas
     * Berjalan mandiri tanpa harus melalui tiket gangguan PGS/PGK/PGJ
     */
    private function getMaintenanceData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);
        $isToday = ($date === Carbon::today()->toDateString());

        $query = NetworkMaintenance::with(['area', 'odp', 'odc', 'technicians', 'creator'])
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
                'photo_after' => $m->photo_after ? Storage::url($m->photo_after) : null,
            ];
        }

        return $items;
    }

    /**
     * Ambil Status Teknisi (On Duty, Libur, Masuk Malam) secara Cerdas
     */
    private function getTechnicianStatus(string $date, string|array|null $areaId, array $activeJobTechNames = []): array
    {
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);

        // 1. Ambil seluruh teknisi
        $allTechnicians = User::where(function ($q) {
            $q->whereHas('roles', function ($r) {
                $r->whereIn('name', ['teknisi', 'kepala teknisi', 'maintenance', 'teknisi lapangan']);
            })->orWhereIn('role', ['teknisi', 'kepala teknisi', 'maintenance']);
        });

        if (!empty($areaIds)) {
            $allTechnicians->where(function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds)->orWhereNull('area_id');
            });
        }

        $techUsers = $allTechnicians->get(['id', 'name', 'phone', 'is_on_duty', 'area_id']);
        $techNamesList = $techUsers->pluck('name')->toArray();

        // 2. Ambil WorkSchedule hari ini KHUSUS teknisi & maintenance
        $schedules = WorkSchedule::with(['employee', 'shift'])
            ->whereDate('date', $date)
            ->whereHas('employee', function ($eq) {
                $eq->where(function ($sq) {
                    $sq->where('position', 'LIKE', '%teknisi%')
                       ->orWhere('position', 'LIKE', '%maintenance%')
                       ->orWhere('position', 'LIKE', '%lapangan%')
                       ->orWhere('position', 'LIKE', '%jaringan%')
                       ->orWhere('position', 'LIKE', '%serbu%');
                });
            })
            ->get();

        $offList = [];
        $nightList = [];
        $scheduledEmpNames = [];

        foreach ($schedules as $ws) {
            $empName = $ws->employee?->name;
            if (!$empName) continue;

            $scheduledEmpNames[] = $empName;
            $shift = $ws->shift;

            // Cek status libur / cuti / sakit
            if (in_array(strtolower((string)$ws->status), ['off', 'leave', 'sick']) || !$shift || stripos((string)$shift->name, 'libur') !== false || stripos((string)$shift->name, 'off') !== false) {
                $offList[] = $empName;
            } elseif ($shift && ($shift->is_night_shift || stripos($shift->name, 'malam') !== false || stripos($shift->name, 'night') !== false)) {
                $nightList[] = $empName;
            }
        }

        // Cek juga dari data Attendance jika ada status off / leave KHUSUS teknisi & maintenance
        $attendances = Attendance::with('employee')
            ->whereDate('date', $date)
            ->whereIn('status', ['leave', 'sick', 'off'])
            ->whereHas('employee', function ($eq) {
                $eq->where(function ($sq) {
                    $sq->where('position', 'LIKE', '%teknisi%')
                       ->orWhere('position', 'LIKE', '%maintenance%')
                       ->orWhere('position', 'LIKE', '%lapangan%')
                       ->orWhere('position', 'LIKE', '%jaringan%')
                       ->orWhere('position', 'LIKE', '%serbu%');
                });
            })
            ->get();

        foreach ($attendances as $att) {
            $eName = $att->employee?->name;
            if ($eName && !in_array($eName, $offList)) {
                $offList[] = $eName;
            }
        }

        // Petugas yang bertugas / On Duty
        $onDutyList = [];
        foreach ($techUsers as $tech) {
            if (!in_array($tech->name, $offList)) {
                $onDutyList[] = $tech->name;
            }
        }

        // Tambahkan juga teknisi yang memiliki tugas aktif di hari ini (bila belum tercantum)
        // HANYA jika ia terdaftar sebagai teknisi/maintenance, bukan staff non-lapangan
        foreach ($activeJobTechNames as $aName) {
            if ($aName && !in_array($aName, ['-', 'Belum Ditugaskan', 'Tim Maintenance Jalur', 'Petugas Survey', 'Petugas CBP', 'Teknisi Instalasi'])) {
                if (!in_array($aName, $offList) && !in_array($aName, $onDutyList)) {
                    $isTechStaff = User::where('name', $aName)
                        ->where(function ($uq) {
                            $uq->whereHas('roles', fn($r) => $r->whereIn('name', ['teknisi', 'kepala teknisi', 'maintenance', 'teknisi lapangan']))
                               ->orWhereIn('role', ['teknisi', 'kepala teknisi', 'maintenance']);
                        })->exists();

                    if ($isTechStaff || in_array($aName, $techNamesList)) {
                        $onDutyList[] = $aName;
                    }
                }
            }
        }

        return [
            'on_duty' => array_values(array_unique(array_filter($onDutyList))),
            'off' => array_values(array_unique(array_filter($offList))),
            'night' => array_values(array_unique(array_filter($nightList))),
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
        $lines[] = "👷‍♂️ *PETUGAS :*";
        if (!empty($technicianStatus['on_duty'])) {
            $lines[] = implode(', ', $technicianStatus['on_duty']);
        } else {
            $lines[] = "-";
        }
        $lines[] = "";
        $lines[] = "";

        // 1. GANGGUAN PGS/PGK
        $lines[] = "🆘 *GANGGUAN PGS/PGK*";
        if (!empty($gangguanList)) {
            // Group by teknisi
            $groupedGangguan = collect($gangguanList)->groupBy('technicians');
            foreach ($groupedGangguan as $techName => $tItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi :* {$techName}";
                $idx = 1;
                foreach ($tItems as $g) {
                    $lines[] = "{$idx}. Nama Pelanggan : {$g['customer_name']}";
                    $lines[] = "   Alamat : {$g['address']}";
                    $lines[] = "   ODP\t  : {$g['odp']}";
                    $lines[] = "   Kendala: {$g['issue']}";
                    $lines[] = "   HP: {$g['phone']}";
                    $lines[] = "   Durasi: {$g['duration']}";
                    $lines[] = "   Status: {$g['status_label']}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "👷 Petugas Teknisi : -";
            $lines[] = "(Nihil / Belum ada gangguan)";
        }
        $lines[] = "";
        $lines[] = "";

        // 2. PEMASANGAN BARU
        $lines[] = "🛜 *PEMASANGAN BARU*";
        if (!empty($pemasanganList)) {
            $groupedPasang = collect($pemasanganList)->groupBy('technicians');
            foreach ($groupedPasang as $techName => $pItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi :* {$techName}";
                $idx = 1;
                foreach ($pItems as $p) {
                    $lines[] = "{$idx}. Nama Pelanggan : {$p['customer_name']}";
                    $lines[] = "   Data Detail : {$p['detail']}";
                    $lines[] = "   ODP\t  : {$p['odp']}";
                    $lines[] = "   HP: {$p['phone']}";
                    $lines[] = "   Durasi: {$p['duration']}";
                    $lines[] = "   Status: {$p['status_label']}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "👷 Petugas Teknisi : -";
            $lines[] = "(Nihil / Belum ada pemasangan)";
        }
        $lines[] = "";
        $lines[] = "";

        // 3. SURVEY PELANGGAN BARU
        $lines[] = "🔎 *SURVEY PELANGGAN BARU*";
        if (!empty($surveyList)) {
            $groupedSurvey = collect($surveyList)->groupBy('technicians');
            foreach ($groupedSurvey as $techName => $sItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Survey/Teknisi :* {$techName}";
                $idx = 1;
                foreach ($sItems as $s) {
                    $lines[] = "{$idx}. Nama Pelanggan : {$s['customer_name']}";
                    $lines[] = "   Data Detail : {$s['detail']}";
                    $lines[] = "   ODP\t  : {$s['odp']}";
                    $lines[] = "   HP: {$s['phone']}";
                    $lines[] = "   Status: {$s['status_label']}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "👷 Petugas Survey/Teknisi : -";
            $lines[] = "(Nihil / Belum ada survey)";
        }
        $lines[] = "";
        $lines[] = "";

        // 4. PENCABUTAN / CBP
        $lines[] = "🔌 *PENCABUTAN/CBP*";
        if (!empty($cbpList)) {
            $groupedCbp = collect($cbpList)->groupBy('technicians');
            foreach ($groupedCbp as $techName => $cItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi/CBP :* {$techName}";
                $idx = 1;
                foreach ($cItems as $c) {
                    $lines[] = "{$idx}. Nama Pelanggan : {$c['customer_name']}";
                    $lines[] = "   Alamat : {$c['address']}";
                    $lines[] = "   ODP\t  : {$c['odp']}";
                    $lines[] = "   Alasan : {$c['reason']}";
                    $lines[] = "   HP: {$c['phone']}";
                    $lines[] = "   Status: {$c['status_label']}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "👷 Petugas Teknisi/CBP : -";
            $lines[] = "(Nihil / Belum ada pencabutan)";
        }
        $lines[] = "";
        $lines[] = "";

        // 5. PERAPIHAN & PENGECEKAN JALUR (ODP / ODC / JALUR UTAMA)
        $lines[] = "🛠️ *PERAPIHAN & PENGECEKAN JALUR (ODP/ODC/JALUR UTAMA)*";
        if (!empty($maintenanceList)) {
            $groupedMnt = collect($maintenanceList)->groupBy('technicians');
            foreach ($groupedMnt as $techName => $mItems) {
                $lines[] = "";
                $lines[] = "👷 *Petugas Teknisi :* {$techName}";
                $idx = 1;
                foreach ($mItems as $m) {
                    $lines[] = "{$idx}. Kegiatan : {$m['category_label']}";
                    $lines[] = "   Target/Lokasi: {$m['title']}" . ($m['location'] ? " - {$m['location']}" : '');
                    if (!empty($m['optical_power_after']) || !empty($m['optical_power_before'])) {
                        $lines[] = "   Redaman: " . ($m['optical_power_after'] ?: $m['optical_power_before']);
                    }
                    if (!empty($m['action_taken'])) {
                        $lines[] = "   Tindakan: {$m['action_taken']}";
                    } elseif (!empty($m['description'])) {
                        $lines[] = "   Rencana : {$m['description']}";
                    }
                    $lines[] = "   Durasi  : {$m['duration']}";
                    $lines[] = "   Status  : {$m['status_label']}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "👷 Petugas Teknisi : -";
            $lines[] = "(Nihil / Belum ada perapihan & pengecekan jalur)";
        }
        $lines[] = "";

        // 6. RINGKASAN HARI INI
        $lines[] = "📊 *RINGKASAN HARI INI*";
        $lines[] = "* Gangguan: {$summary['gangguan']} ({$summary['gangguan_done']} Selesai, {$summary['gangguan_pending']} Pending)";
        $lines[] = "* Pemasangan: {$summary['pemasangan']} ({$summary['pemasangan_done']} Selesai, {$summary['pemasangan_pending']} Pending)";
        $lines[] = "* Survey: {$summary['survey']} ({$summary['survey_feasible']} Selesai, {$summary['survey_pending']} Tunggu)";
        $lines[] = "* Pencabutan: {$summary['cbp']} ({$summary['cbp_done']} Selesai, {$summary['cbp_pending']} Pending)";
        $lines[] = "* Perapihan/Cek Jalur: {$summary['maintenance']} ({$summary['maintenance_done']} Selesai, {$summary['maintenance_pending']} Pending)";
        $lines[] = "";

        // 7. STATUS TEKNISI
        $lines[] = "👷 *STATUS TEKNISI*";
        $liburStr = !empty($technicianStatus['off']) ? implode(', ', $technicianStatus['off']) : '-';
        $malamStr = !empty($technicianStatus['night']) ? implode(', ', $technicianStatus['night']) : '-';
        $lines[] = "Teknisi Libur: {$liburStr}";
        $lines[] = "Teknisi Masuk Malam: {$malamStr}";
        $lines[] = "";

        // 8. NOTE
        $lines[] = "📝 *NOTE*";
        $lines[] = $customNote ?: "Tetap semangat dan jaga keselamatan kerja! 💪";

        return implode("\n", $lines);
    }
}
