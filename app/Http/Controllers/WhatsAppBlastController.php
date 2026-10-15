<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\Area;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\WaCampaign;
use App\Models\WaLog;
use App\Models\Setting;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WhatsAppBlastController extends Controller
{
    protected WhatsAppGatewayService $waService;

    public function __construct(WhatsAppGatewayService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Halaman Utama WhatsApp Blast
     */
    public function index(Request $request)
    {
        // 1. Data Area & Paket untuk filter
        $areas = Area::select('id', 'name')->orderBy('name')->get();
        $packages = InternetPackage::select('id', 'name')->orderBy('name')->get();

        // 2. Statistik target penerima
        $stats = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'isolir_customers' => Customer::where('status', 'isolir')->count(),
            'booking_customers' => Customer::where('status', 'booking')->count(),
            'terminated_customers' => Customer::where('status', 'terminated')->count(),
            'total_resellers' => User::where('role', 'reseller')->where('is_active', true)->count(),
            'total_technicians' => User::whereIn('role', ['technician', 'lead_technician'])->where('is_active', true)->count(),
        ];

        // 3. Riwayat Kampanye Blast Terakhir
        $campaigns = WaCampaign::with(['creator:id,name', 'area:id,name'])
            ->latest()
            ->paginate(15);

        // 4. Status WA Gateway
        $gatewayStatus = $this->waService->getStatus();

        // 5. Pengaturan Gateway tersimpan
        $gatewaySettings = [
            'gateway_url' => $this->waService->getGatewayUrl(),
            'anti_ban_delay_min' => (int) Setting::get('wa_anti_ban_delay_min', 5),
            'anti_ban_delay_max' => (int) Setting::get('wa_anti_ban_delay_max', 12),
            'batch_size' => (int) Setting::get('wa_batch_size', 20),
            'batch_pause' => (int) Setting::get('wa_batch_pause', 25),
        ];

        return Inertia::render('WhatsApp/Blast', [
            'areas' => $areas,
            'packages' => $packages,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'gatewayStatus' => $gatewayStatus,
            'gatewaySettings' => $gatewaySettings,
        ]);
    }

    /**
     * API Get Live Status & QR Code dari Baileys
     */
    public function getStatus()
    {
        return response()->json($this->waService->getStatus());
    }

    /**
     * API Logout Sesi WhatsApp
     */
    public function logout()
    {
        $result = $this->waService->logout();
        return response()->json($result);
    }

    /**
     * API Restart Sesi WhatsApp
     */
    public function restart()
    {
        $result = $this->waService->restart();
        return response()->json($result);
    }

    /**
     * Ambil daftar calon penerima pesan berdasarkan filter (Pratinjau Cerdas)
     */
    public function getRecipients(Request $request)
    {
        $targetType = $request->input('target_type', 'all_customers');
        $areaId = $request->input('area_id');
        $packageId = $request->input('package_id');
        $onlyUnpaid = $request->boolean('only_unpaid');

        $recipients = collect();

        if (in_array($targetType, ['all_customers', 'active', 'isolir', 'booking', 'terminated'])) {
            $query = Customer::with(['package:id,name', 'areaRef:id,name'])
                ->whereNotNull('phone')
                ->where('phone', '!=', '');

            if ($targetType !== 'all_customers') {
                $query->where('status', $targetType);
            }

            if ($areaId) {
                $query->where('area_id', $areaId);
            }

            if ($packageId) {
                $query->where('package_id', $packageId);
            }

            $customers = $query->get();

            // Ambil data invoice unpaid jika ada
            $customerIds = $customers->pluck('id');
            $unpaidInvoices = Invoice::whereIn('customer_id', $customerIds)
                ->where('status', 'unpaid')
                ->latest()
                ->get()
                ->groupBy('customer_id');

            foreach ($customers as $c) {
                $invList = $unpaidInvoices->get($c->id);
                $hasUnpaid = $invList && $invList->isNotEmpty();
                $firstInv = $hasUnpaid ? $invList->first() : null;
                $totalUnpaid = $hasUnpaid ? $invList->sum('amount') : 0;

                if ($onlyUnpaid && !$hasUnpaid) {
                    continue;
                }

                $recipients->push([
                    'type' => 'customer',
                    'id' => $c->id,
                    'customer_id' => $c->customer_id ?? $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'package_name' => $c->package->name ?? '-',
                    'area_name' => $c->areaRef->name ?? ($c->area ?? 'Pusat'),
                    'address' => $c->address ?? '-',
                    'unpaid_amount' => (float) $totalUnpaid,
                    'invoice_number' => $firstInv ? ($firstInv->invoice_number ?? $firstInv->id) : '-',
                    'due_date' => $firstInv && $firstInv->due_date ? $firstInv->due_date->format('d M Y') : '-',
                ]);
            }
        } elseif ($targetType === 'resellers') {
            $resellers = User::where('role', 'reseller')
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->get();

            foreach ($resellers as $r) {
                $recipients->push([
                    'type' => 'reseller',
                    'id' => $r->id,
                    'customer_id' => 'RSL-' . $r->id,
                    'name' => $r->name,
                    'phone' => $r->phone,
                    'package_name' => 'Reseller Mitra',
                    'area_name' => 'Mitra',
                    'address' => '-',
                    'unpaid_amount' => 0,
                    'invoice_number' => '-',
                    'due_date' => '-',
                ]);
            }
        } elseif ($targetType === 'technicians') {
            $techs = User::whereIn('role', ['technician', 'lead_technician'])
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->get();

            foreach ($techs as $t) {
                $recipients->push([
                    'type' => 'technician',
                    'id' => $t->id,
                    'customer_id' => 'STAF-' . $t->id,
                    'name' => $t->name,
                    'phone' => $t->phone,
                    'package_name' => ucfirst(str_replace('_', ' ', $t->role)),
                    'area_name' => 'Internal',
                    'address' => '-',
                    'unpaid_amount' => 0,
                    'invoice_number' => '-',
                    'due_date' => '-',
                ]);
            }
        }

        return response()->json([
            'total' => $recipients->count(),
            'recipients' => $recipients->values()->all(),
        ]);
    }

    /**
     * Inisialisasi Kampanye Baru
     */
    public function createCampaign(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'target_type' => 'required|string',
            'target_area_id' => 'nullable|integer',
            'message' => 'required|string',
            'media_url' => 'nullable|string',
            'total_recipients' => 'required|integer|min:1',
            'delay_seconds' => 'nullable|integer|min:2|max:60',
        ]);

        $campaign = WaCampaign::create([
            'title' => $validated['title'],
            'target_type' => $validated['target_type'],
            'target_area_id' => $validated['target_area_id'] ?? null,
            'message' => $validated['message'],
            'media_url' => $validated['media_url'] ?? null,
            'total_recipients' => $validated['total_recipients'],
            'delay_seconds' => $validated['delay_seconds'] ?? 5,
            'status' => 'processing',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'campaign_id' => $campaign->id,
            'message' => 'Kampanye WhatsApp Blast berhasil diinisialisasi.',
        ]);
    }

    /**
     * Kirim Satu Batch Pesan (Realtime Streaming Endpoint)
     * Dipanggil oleh Frontend dalam loop berulang dengan jeda acak anti-banned
     */
    public function dispatchRecipient(Request $request, WaCampaign $campaign)
    {
        $request->validate([
            'recipient' => 'required|array',
            'recipient.name' => 'required|string',
            'recipient.phone' => 'required|string',
        ]);

        $recipientData = $request->input('recipient');
        $phone = $recipientData['phone'];
        $name = $recipientData['name'];

        // 1. Render template dengan spintax dan smart variables
        $messageToSend = WhatsAppGatewayService::renderMessage($campaign->message, $recipientData);

        // 2. Kirim melalui Baileys gateway service
        $sendResult = $this->waService->sendMessage($phone, $messageToSend, $campaign->media_url);

        $isSuccess = !empty($sendResult['success']);
        $status = $isSuccess ? 'sent' : 'failed';
        $errorMsg = $isSuccess ? null : ($sendResult['message'] ?? 'Gagal kirim');

        // 3. Catat di tabel wa_logs
        $log = WaLog::create([
            'wa_campaign_id' => $campaign->id,
            'recipient_name' => $name,
            'recipient_phone' => $phone,
            'recipient_type' => $recipientData['type'] ?? 'customer',
            'recipient_id' => $recipientData['id'] ?? null,
            'message_sent' => $messageToSend,
            'status' => $status,
            'error_message' => $errorMsg,
            'sent_at' => $isSuccess ? now() : null,
        ]);

        // 4. Update counter kampanye
        if ($isSuccess) {
            $campaign->increment('sent_count');
        } else {
            $campaign->increment('failed_count');
        }

        return response()->json([
            'success' => $isSuccess,
            'status' => $status,
            'messageId' => $sendResult['messageId'] ?? null,
            'error' => $errorMsg,
            'sent_message_preview' => mb_substr($messageToSend, 0, 80) . '...',
            'campaign_stats' => [
                'sent' => $campaign->sent_count,
                'failed' => $campaign->failed_count,
                'total' => $campaign->total_recipients,
            ]
        ]);
    }

    /**
     * Selesaikan kampanye
     */
    public function completeCampaign(Request $request, WaCampaign $campaign)
    {
        $campaign->update([
            'status' => $request->input('status', 'completed'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status kampanye diperbarui menjadi: ' . $campaign->status,
        ]);
    }

    /**
     * Kirim Pesan Uji Coba Cepat (Test Send)
     */
    public function testSend(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'media_url' => 'nullable|string',
        ]);

        $dummyRecipient = [
            'name' => 'Uji Coba Admin',
            'phone' => $request->phone,
            'customer_id' => 'TEST-001',
            'package_name' => 'Paket Ultra 50 Mbps',
            'area_name' => 'Pusat Operasional',
            'address' => 'Jl. Uji Coba No. 1',
            'unpaid_amount' => 175000,
            'invoice_number' => 'INV-TEST-2026',
            'due_date' => now()->addDays(3)->format('d M Y'),
        ];

        $rendered = WhatsAppGatewayService::renderMessage($request->message, $dummyRecipient);
        $result = $this->waService->sendMessage($request->phone, $rendered, $request->media_url);

        return response()->json([
            'success' => !empty($result['success']),
            'message' => !empty($result['success']) ? 'Pesan uji coba berhasil dikirim ke ' . $request->phone : ($result['message'] ?? 'Gagal mengirim pesan.'),
            'rendered_message' => $rendered,
            'raw_result' => $result,
        ]);
    }

    /**
     * Simpan Pengaturan Gateway
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'wa_gateway_url' => 'required|url',
            'wa_anti_ban_delay_min' => 'required|integer|min:2|max:30',
            'wa_anti_ban_delay_max' => 'required|integer|min:3|max:60',
            'wa_batch_size' => 'required|integer|min:5|max:100',
            'wa_batch_pause' => 'required|integer|min:10|max:120',
        ]);

        Setting::set('wa_gateway_url', $request->wa_gateway_url);
        Setting::set('wa_anti_ban_delay_min', $request->wa_anti_ban_delay_min);
        Setting::set('wa_anti_ban_delay_max', $request->wa_anti_ban_delay_max);
        Setting::set('wa_batch_size', $request->wa_batch_size);
        Setting::set('wa_batch_pause', $request->wa_batch_pause);

        return back()->with('success', 'Pengaturan WhatsApp Gateway & Anti-Banned berhasil disimpan.');
    }
}
