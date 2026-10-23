<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Area;
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

class OperationalDailyProgressController extends Controller
{
    /**
     * Tampilkan Halaman Progress Harian Operasional (PGS, PGK, PGJ, CBP, Instalasi, Survey & Shift Teknisi)
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $areaId = $request->input('area_id');
        $customNote = $request->input('note', 'Tetap semangat dan jaga keselamatan kerja! 💪');

        // Parse Carbon Date & Format Bahasa Indonesia
        $carbonDate = Carbon::parse($date)->locale('id');
        $indonesianDayDate = $this->formatIndonesianDayDate($carbonDate);

        // Ambil Data Area
        $areas = Area::orderBy('name')->get(['id', 'name']);
        $selectedArea = $areaId ? Area::find($areaId) : null;
        $areaLabel = $selectedArea ? $selectedArea->name : 'Semua Wilayah / Area';

        // 1. DATA GANGGUAN (PGS, PGK, PGJ)
        $gangguanList = $this->getGangguanData($date, $areaId);

        // 2. DATA PEMASANGAN BARU (INSTALASI)
        $pemasanganList = $this->getPemasanganData($date, $areaId);

        // 3. DATA SURVEY PELANGGAN BARU
        $surveyList = $this->getSurveyData($date, $areaId);

        // 4. DATA PENCABUTAN / CBP
        $cbpList = $this->getCbpData($date, $areaId);

        // 5. STATUS TEKNISI (On Duty, Libur, Masuk Malam)
        $technicianStatus = $this->getTechnicianStatus($date, $areaId);

        // 6. RINGKASAN DATA
        $summary = [
            'gangguan' => count($gangguanList),
            'gangguan_done' => collect($gangguanList)->where('is_done', true)->count(),
            'gangguan_pending' => collect($gangguanList)->where('is_done', false)->count(),

            'pemasangan' => count($pemasanganList),
            'pemasangan_done' => collect($pemasanganList)->where('is_done', true)->count(),
            'pemasangan_pending' => collect($pemasanganList)->where('is_done', false)->count(),

            'survey' => count($surveyList),
            'survey_feasible' => collect($surveyList)->where('feasibility', 'feasible')->count(),
            'survey_pending' => collect($surveyList)->where('feasibility', 'pending')->count(),

            'cbp' => count($cbpList),
            'cbp_done' => collect($cbpList)->where('is_done', true)->count(),
            'cbp_pending' => collect($cbpList)->where('is_done', false)->count(),
        ];

        // 7. HASILKAN FORMAT TEKS WHATSAPP SESUAI TEMPLATE
        $waText = $this->buildWhatsAppTemplate(
            $areaLabel,
            $indonesianDayDate,
            $technicianStatus,
            $gangguanList,
            $pemasanganList,
            $surveyList,
            $cbpList,
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

        return Inertia::render('Operational/DailyProgress', [
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
            'technicianStatus' => $technicianStatus,
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

            // Format nomor jika personal (08xxx -> 628xxx)
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
     * Ambil Data Gangguan (PGS, PGK, PGJ)
     */
    private function getGangguanData(string $date, ?string $areaId): array
    {
        $items = [];

        // 1. PGS
        $pgsQuery = PgsTicket::with(['customer.ont.odp', 'technicians', 'leadTechnician', 'area'])
            ->where(function ($q) use ($date) {
                $q->whereDate('scheduled_date', $date)
                  ->orWhereDate('resolved_at', $date)
                  ->orWhere(function ($sq) use ($date) {
                      $sq->whereIn('status', ['in_progress', 'scheduled', 'admin_handling'])
                         ->whereDate('created_at', '<=', $date);
                  });
            });

        if ($areaId) {
            $pgsQuery->where('area_id', $areaId);
        }

        foreach ($pgsQuery->get() as $pgs) {
            $techNames = $pgs->technicians->pluck('name')->toArray();
            if (empty($techNames) && $pgs->leadTechnician) {
                $techNames[] = $pgs->leadTechnician->name;
            }

            $isDone = in_array($pgs->status, ['resolved', 'closed']);
            $statusLabel = $isDone ? '✅ Selesai' : (in_array($pgs->status, ['in_progress']) ? '🛠️ Sedang Dikerjakan' : '⏳ Pending');

            $duration = '-';
            if ($pgs->start_time && $pgs->end_time) {
                $duration = substr($pgs->start_time, 0, 5) . ' - ' . substr($pgs->end_time, 0, 5);
            } elseif ($pgs->start_time) {
                $duration = 'Mulai ' . substr($pgs->start_time, 0, 5);
            }

            $odpName = $pgs->customer?->ont?->odp?->name ?? '-';

            $items[] = [
                'type' => 'PGS',
                'ticket_number' => $pgs->ticket_number,
                'customer_name' => $pgs->customer?->name ?? 'Non-Pelanggan (Temuan Sigap)',
                'address' => $pgs->customer?->address ?? ($pgs->area?->name ?? '-'),
                'odp' => $odpName,
                'issue' => $pgs->reported_issue ?: ($pgs->category ?: 'Gangguan Sigap'),
                'phone' => $pgs->customer?->phone ?? '-',
                'technicians' => !empty($techNames) ? implode(', ', $techNames) : 'Belum Ditugaskan',
                'duration' => $duration,
                'status_label' => $statusLabel,
                'is_done' => $isDone,
            ];
        }

        // 2. PGK (Komplain Pelanggan)
        $pgkQuery = PgkTicket::with(['customer.ont.odp', 'technicians', 'leadTechnician', 'area'])
            ->where(function ($q) use ($date) {
                $q->whereDate('scheduled_date', $date)
                  ->orWhereDate('resolved_at', $date)
                  ->orWhere(function ($sq) use ($date) {
                      $sq->whereIn('status', ['in_progress', 'scheduled', 'admin_handling'])
                         ->whereDate('created_at', '<=', $date);
                  });
            });

        if ($areaId) {
            $pgkQuery->where('area_id', $areaId);
        }

        foreach ($pgkQuery->get() as $pgk) {
            $techNames = $pgk->technicians->pluck('name')->toArray();
            if (empty($techNames) && $pgk->leadTechnician) {
                $techNames[] = $pgk->leadTechnician->name;
            }

            $isDone = in_array($pgk->status, ['resolved', 'closed']);
            $statusLabel = $isDone ? '✅ Selesai' : (in_array($pgk->status, ['in_progress']) ? '🛠️ Sedang Dikerjakan' : '⏳ Pending');

            $duration = '-';
            if ($pgk->start_time && $pgk->end_time) {
                $duration = substr($pgk->start_time, 0, 5) . ' - ' . substr($pgk->end_time, 0, 5);
            } elseif ($pgk->start_time) {
                $duration = 'Mulai ' . substr($pgk->start_time, 0, 5);
            }

            $odpName = $pgk->customer?->ont?->odp?->name ?? '-';

            $items[] = [
                'type' => 'PGK',
                'ticket_number' => $pgk->ticket_number,
                'customer_name' => $pgk->customer?->name ?? ($pgk->complainant_name ?: 'Pelanggan'),
                'address' => $pgk->customer?->address ?? ($pgk->area?->name ?? '-'),
                'odp' => $odpName,
                'issue' => $pgk->reported_issue ?: ($pgk->category ?: 'Komplain Layanan'),
                'phone' => $pgk->complainant_phone ?: ($pgk->customer?->phone ?? '-'),
                'technicians' => !empty($techNames) ? implode(', ', $techNames) : 'Belum Ditugaskan',
                'duration' => $duration,
                'status_label' => $statusLabel,
                'is_done' => $isDone,
            ];
        }

        // 3. PGJ (Gangguan Jalur / Backbone / ODC)
        $pgjQuery = PgjTicket::with(['area', 'olt', 'odc', 'odp'])
            ->where(function ($q) use ($date) {
                $q->whereDate('scheduled_date', $date)
                  ->orWhereDate('resolved_at', $date)
                  ->orWhere(function ($sq) use ($date) {
                      $sq->whereIn('status', ['in_progress', 'scheduled', 'admin_handling'])
                         ->whereDate('created_at', '<=', $date);
                  });
            });

        if ($areaId) {
            $pgjQuery->where('area_id', $areaId);
        }

        foreach ($pgjQuery->get() as $pgj) {
            $isDone = in_array($pgj->status, ['resolved', 'closed']);
            $statusLabel = $isDone ? '✅ Selesai' : (in_array($pgj->status, ['in_progress']) ? '🛠️ Sedang Dikerjakan' : '⏳ Pending');

            $duration = '-';
            if ($pgj->start_time && $pgj->end_time) {
                $duration = substr($pgj->start_time, 0, 5) . ' - ' . substr($pgj->end_time, 0, 5);
            }

            $infraName = $pgj->odp?->name ?: ($pgj->odc?->name ?: ($pgj->olt?->name ?: ($pgj->location_name ?: '-')));

            $items[] = [
                'type' => 'PGJ',
                'ticket_number' => $pgj->ticket_number,
                'customer_name' => '⚠️ Jalur/Infra: ' . ($pgj->location_name ?: $pgj->segment_type),
                'address' => $pgj->area?->name ?? 'Wilayah Jalur',
                'odp' => $infraName,
                'issue' => $pgj->reported_issue ?: 'Gangguan Jalur FTTH',
                'phone' => '-',
                'technicians' => 'Tim Maintenance Jalur',
                'duration' => $duration,
                'status_label' => $statusLabel,
                'is_done' => $isDone,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Pemasangan Baru (Instalasi)
     */
    private function getPemasanganData(string $date, ?string $areaId): array
    {
        $items = [];

        // Ambil dari TechnicianSchedule type = 'installation'
        $scheduleQuery = TechnicianSchedule::with(['customer.ont.odp', 'customer.package', 'technician'])
            ->where('type', 'installation')
            ->whereDate('scheduled_date', $date);

        if ($areaId) {
            $scheduleQuery->whereHas('customer', fn($q) => $q->where('area_id', $areaId));
        }

        $seenCustomerIds = [];

        foreach ($scheduleQuery->get() as $sched) {
            $cust = $sched->customer;
            if (!$cust) continue;

            $seenCustomerIds[$cust->id] = true;
            $isDone = in_array($sched->status, ['done', 'completed']) || $cust->status === 'active';
            $statusLabel = $isDone ? '✅ Selesai' : (in_array($sched->status, ['working', 'on_the_way']) ? '🛠️ Sedang Dipasang' : '⏳ Pending');

            $pkgName = $cust->package?->name ?? 'Paket Internet';
            $detail = "{$pkgName} - {$cust->address}";
            $odpName = $cust->ont?->odp?->name ?? '-';

            $duration = $sched->scheduled_time ? substr((string)$sched->scheduled_time, 0, 5) : '-';

            $items[] = [
                'customer_name' => $cust->name,
                'detail' => $detail,
                'odp' => $odpName,
                'phone' => $cust->phone,
                'technicians' => $sched->technician?->name ?? 'Belum Ditugaskan',
                'duration' => $duration,
                'status_label' => $statusLabel,
                'is_done' => $isDone,
            ];
        }

        // Tambahkan juga Customer yang aktivasi hari ini jika belum tercakup di jadwal
        $activeQuery = Customer::with(['ont.odp', 'package'])
            ->where(function ($q) use ($date) {
                $q->whereDate('activation_date', $date)
                  ->orWhere(function ($sq) use ($date) {
                      $sq->where('status', 'installing')->whereDate('registration_date', $date);
                  });
            });

        if ($areaId) {
            $activeQuery->where('area_id', $areaId);
        }

        foreach ($activeQuery->get() as $cust) {
            if (isset($seenCustomerIds[$cust->id])) continue;

            $isDone = $cust->status === 'active';
            $statusLabel = $isDone ? '✅ Selesai' : '⏳ Pending';
            $pkgName = $cust->package?->name ?? 'Paket Internet';

            $items[] = [
                'customer_name' => $cust->name,
                'detail' => "{$pkgName} - {$cust->address}",
                'odp' => $cust->ont?->odp?->name ?? '-',
                'phone' => $cust->phone,
                'technicians' => 'Teknisi Instalasi',
                'duration' => '-',
                'status_label' => $statusLabel,
                'is_done' => $isDone,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Survey Pelanggan Baru
     */
    private function getSurveyData(string $date, ?string $areaId): array
    {
        $items = [];

        // 1. Dari model Survey
        $surveyQuery = Survey::with(['customer.package', 'odp', 'surveyor'])
            ->whereDate('survey_date', $date);

        if ($areaId) {
            $surveyQuery->where(function ($q) use ($areaId) {
                $q->where('area_id', $areaId)
                  ->orWhereHas('customer', fn($sq) => $sq->where('area_id', $areaId));
            });
        }

        $seenCustomerIds = [];

        foreach ($surveyQuery->get() as $srv) {
            $cust = $srv->customer;
            if (!$cust) continue;

            $seenCustomerIds[$cust->id] = true;
            $pkgName = $cust->package?->name ?? 'Paket Internet';
            $detail = "{$pkgName} - {$cust->address}";

            $feasibility = $srv->feasibility;
            if ($feasibility === 'feasible') {
                $statusLabel = '✅ Feasible (Layak)';
            } elseif ($feasibility === 'not_feasible') {
                $statusLabel = '❌ Tidak Layak' . ($srv->notes ? " ({$srv->notes})" : '');
            } elseif ($feasibility === 'conditional') {
                $statusLabel = '⚠️ Bersyarat';
            } else {
                $statusLabel = '⏳ Pending';
            }

            $items[] = [
                'customer_name' => $cust->name,
                'detail' => $detail,
                'odp' => $srv->odp?->name ?? '-',
                'phone' => $cust->phone,
                'technicians' => $srv->surveyor?->name ?? 'Petugas Survey',
                'feasibility' => $feasibility,
                'status_label' => $statusLabel,
            ];
        }

        // 2. Dari TechnicianSchedule type = 'survey'
        $schedQuery = TechnicianSchedule::with(['customer.package', 'technician'])
            ->where('type', 'survey')
            ->whereDate('scheduled_date', $date);

        if ($areaId) {
            $schedQuery->whereHas('customer', fn($q) => $q->where('area_id', $areaId));
        }

        foreach ($schedQuery->get() as $sched) {
            $cust = $sched->customer;
            if (!$cust || isset($seenCustomerIds[$cust->id])) continue;

            $pkgName = $cust->package?->name ?? 'Paket Internet';
            $isDone = in_array($sched->status, ['done', 'completed']);
            $statusLabel = $isDone ? '✅ Selesai Survey' : '⏳ Pending';

            $items[] = [
                'customer_name' => $cust->name,
                'detail' => "{$pkgName} - {$cust->address}",
                'odp' => '-',
                'phone' => $cust->phone,
                'technicians' => $sched->technician?->name ?? 'Petugas Survey',
                'feasibility' => $isDone ? 'feasible' : 'pending',
                'status_label' => $statusLabel,
            ];
        }

        return $items;
    }

    /**
     * Ambil Data Pencabutan / CBP
     */
    private function getCbpData(string $date, ?string $areaId): array
    {
        $items = [];

        $cbpQuery = CbpRequest::with(['customer.ont.odp', 'technicians'])
            ->where(function ($q) use ($date) {
                $q->whereDate('created_at', $date)
                  ->orWhereDate('completed_at', $date)
                  ->orWhereDate('sent_to_schedule_at', $date);
            });

        if ($areaId) {
            $cbpQuery->whereHas('customer', fn($q) => $q->where('area_id', $areaId));
        }

        foreach ($cbpQuery->get() as $cbp) {
            $cust = $cbp->customer;
            $techNames = $cbp->technicians->pluck('name')->toArray();
            $isDone = $cbp->status === 'completed';
            $statusLabel = $isDone ? '✅ Selesai' : '⏳ Pending';

            $odpName = $cust?->ont?->odp?->name ?? '-';
            $custName = $cust?->name ?? "Pelanggan #{$cbp->customer_id}";
            $address = $cust?->address ?? '-';
            $reason = $cbp->reason ?: ($cbp->notes ?: 'Putus Berlangganan');

            $items[] = [
                'customer_name' => $custName,
                'detail' => "{$custName} - {$address} (ODP: {$odpName}, Alasan: {$reason})",
                'address' => $address,
                'odp' => $odpName,
                'reason' => $reason,
                'phone' => $cust?->phone ?? '-',
                'technicians' => !empty($techNames) ? implode(', ', $techNames) : 'Petugas CBP',
                'status_label' => $statusLabel,
                'is_done' => $isDone,
            ];
        }

        return $items;
    }

    /**
     * Ambil Status Teknisi (On Duty, Libur, Masuk Malam)
     */
    private function getTechnicianStatus(string $date, ?string $areaId): array
    {
        // 1. Ambil seluruh teknisi
        $allTechnicians = User::where(function ($q) {
            $q->whereHas('roles', function ($r) {
                $r->whereIn('name', ['teknisi', 'kepala teknisi', 'maintenance', 'teknisi lapangan']);
            })->orWhereIn('role', ['teknisi', 'kepala teknisi', 'maintenance']);
        });

        if ($areaId) {
            $allTechnicians->where(function ($q) use ($areaId) {
                $q->where('area_id', $areaId)->orWhereNull('area_id');
            });
        }

        $techUsers = $allTechnicians->get(['id', 'name', 'phone', 'is_on_duty', 'area_id']);

        // 2. Ambil WorkSchedule hari ini
        $schedules = WorkSchedule::with(['employee', 'shift'])
            ->whereDate('date', $date)
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

        // Cek juga dari data Attendance jika ada status off / leave
        $attendances = Attendance::with('user')
            ->whereDate('date', $date)
            ->whereIn('status', ['leave', 'sick', 'off'])
            ->get();

        foreach ($attendances as $att) {
            $uName = $att->user?->name;
            if ($uName && !in_array($uName, $offList)) {
                $offList[] = $uName;
            }
        }

        // Petugas yang bertugas / On Duty
        $onDutyList = [];
        foreach ($techUsers as $tech) {
            if (!in_array($tech->name, $offList)) {
                $onDutyList[] = $tech->name;
            }
        }

        return [
            'on_duty' => array_values(array_unique($onDutyList)),
            'off' => array_values(array_unique($offList)),
            'night' => array_values(array_unique($nightList)),
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
                $lines[] = "👷 *Petugas Survey/Teknisi :* {$techName}";
                $idx = 1;
                foreach ($cItems as $c) {
                    $lines[] = "{$idx}. Data detail: {$c['detail']} - Status: {$c['status_label']}";
                    $idx++;
                }
            }
        } else {
            $lines[] = "👷 Petugas Survey/Teknisi : -";
            $lines[] = "(Nihil / Belum ada pencabutan)";
        }
        $lines[] = "";

        // 5. RINGKASAN HARI INI
        $lines[] = "📊 *RINGKASAN HARI INI*";
        $lines[] = "* Gangguan: {$summary['gangguan']}";
        $lines[] = "* Pemasangan: {$summary['pemasangan']}";
        $lines[] = "* Survey: {$summary['survey']}";
        $lines[] = "* Pencabutan: {$summary['cbp']}";
        $lines[] = "";

        // 6. STATUS TEKNISI
        $lines[] = "👷 *STATUS TEKNISI*";
        $liburStr = !empty($technicianStatus['off']) ? implode(', ', $technicianStatus['off']) : '-';
        $malamStr = !empty($technicianStatus['night']) ? implode(', ', $technicianStatus['night']) : '-';
        $lines[] = "Teknisi Libur: {$liburStr}";
        $lines[] = "Teknisi Masuk Malam: {$malamStr}";
        $lines[] = "";

        // 7. NOTE
        $lines[] = "📝 *NOTE*";
        $lines[] = $customNote ?: "Tetap semangat dan jaga keselamatan kerja! 💪";

        return implode("\n", $lines);
    }
}
