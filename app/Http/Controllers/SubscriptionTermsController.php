<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAgreement;
use App\Models\SubscriptionTermsTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionTermsController extends Controller
{
    /**
     * Tampilan Kelola Template Syarat & Ketentuan (Pengaturan)
     */
    public function index()
    {
        $template = SubscriptionTermsTemplate::where('type', 'general')->first();

        if (!$template) {
            $template = SubscriptionTermsTemplate::create([
                'title' => 'SURAT PERJANJIAN & SYARAT KETENTUAN BERLANGGANAN LAYANAN INTERNET',
                'type' => 'general',
                'content' => $this->getDefaultContent(),
                'requires_employee_signature' => true,
                'requires_customer_signature' => true,
                'is_active' => true,
                'notes' => 'Template standar legalitas berlangganan ISP',
                'created_by' => auth()->id(),
            ]);
        }

        $allTemplates = SubscriptionTermsTemplate::with(['creator:id,name', 'updater:id,name'])->get();
        $tokens = SubscriptionTermsTemplate::getSupportedTokens();

        return Inertia::render('Settings/TermsConditions', [
            'currentTemplate' => $template,
            'allTemplates' => $allTemplates,
            'tokens' => $tokens,
            'companyName' => Setting::get('company_name', config('app.name', 'Vizbill ISP')),
        ]);
    }

    /**
     * Simpan / Perbarui Template S&K
     */
    public function update(Request $request, $id = null)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|in:general,booking,survey,installation',
            'content' => 'required|string',
            'requires_employee_signature' => 'boolean',
            'requires_customer_signature' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($id) {
            $template = SubscriptionTermsTemplate::findOrFail($id);
            $validated['updated_by'] = auth()->id();
            $template->update($validated);
        } else {
            $template = SubscriptionTermsTemplate::where('type', $validated['type'] ?? 'general')->first();
            if ($template) {
                $validated['updated_by'] = auth()->id();
                $template->update($validated);
            } else {
                $validated['created_by'] = auth()->id();
                $template = SubscriptionTermsTemplate::create($validated);
            }
        }

        return redirect()->back()->with('success', 'Template Syarat & Ketentuan berlangganan berhasil disimpan.');
    }

    /**
     * Kembalikan isi template ke standar bawaan
     */
    public function resetToDefault(Request $request)
    {
        $template = SubscriptionTermsTemplate::first();
        if ($template) {
            $template->update([
                'title' => 'SURAT PERJANJIAN & SYARAT KETENTUAN BERLANGGANAN LAYANAN INTERNET',
                'content' => $this->getDefaultContent(),
                'requires_employee_signature' => true,
                'requires_customer_signature' => true,
                'updated_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', 'Template Syarat & Ketentuan berhasil dikembalikan ke standar awal.');
    }

    /**
     * API: Ambil data S&K, template, dan status agreement terkini untuk pelanggan tertentu
     */
    public function getCustomerAgreementData(Customer $customer, Request $request)
    {
        $stage = $request->query('stage', 'booking');

        $customer->loadMissing(['package', 'areaModel']);

        // Cari template yang sesuai dengan stage, atau fallback ke general
        $template = SubscriptionTermsTemplate::where('type', $stage)->where('is_active', true)->first()
            ?? SubscriptionTermsTemplate::where('type', 'general')->where('is_active', true)->first()
            ?? SubscriptionTermsTemplate::first();

        if (!$template) {
            $template = SubscriptionTermsTemplate::create([
                'title' => 'SURAT PERJANJIAN & SYARAT KETENTUAN BERLANGGANAN LAYANAN INTERNET',
                'type' => 'general',
                'content' => $this->getDefaultContent(),
                'requires_employee_signature' => true,
                'requires_customer_signature' => true,
                'is_active' => true,
                'notes' => 'Template standar legalitas berlangganan ISP',
            ]);
        }

        // Render teks dengan nilai data aktual pelanggan
        $renderedContent = $template->renderContent($customer, auth()->user());

        // Cek apakah sudah pernah ditandatangani di stage ini
        $existingAgreement = CustomerAgreement::where('customer_id', $customer->id)
            ->where('stage', $stage)
            ->latest()
            ->first();

        // Jika tidak ada di stage ini, ambil agreement terakhir di stage mana pun untuk riwayat/referensi
        $latestAnyStage = null;
        if (!$existingAgreement) {
            $latestAnyStage = CustomerAgreement::where('customer_id', $customer->id)->latest()->first();
        }

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'customer_code' => $customer->customer_code,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'area' => $customer->areaModel?->name ?? $customer->area ?? '-',
                'package_name' => $customer->package?->name ?? '-',
                'package_speed' => $customer->package?->speed ? ($customer->package->speed . ' Mbps') : '-',
                'price' => $customer->base_amount ?: ($customer->package?->price ?? 0),
                'installation_fee' => $customer->installation_fee ?? 0,
            ],
            'template' => [
                'id' => $template->id,
                'title' => $template->title,
                'requires_employee_signature' => $template->requires_employee_signature,
                'requires_customer_signature' => $template->requires_customer_signature,
            ],
            'rendered_content' => $renderedContent,
            'existing_agreement' => $existingAgreement,
            'latest_any_stage' => $latestAnyStage,
            'current_user' => [
                'id' => auth()->id(),
                'name' => auth()->user()?->name ?? 'Petugas Lapangan',
            ],
        ]);
    }

    /**
     * Simpan Perjanjian & Tanda Tangan Digital Baru
     */
    public function storeCustomerAgreement(Customer $customer, Request $request)
    {
        $validated = $request->validate([
            'stage' => 'required|string|in:booking,survey,installation,general',
            'terms_template_id' => 'nullable|exists:subscription_terms_templates,id',
            'title' => 'nullable|string|max:255',
            'content_snapshot' => 'required|string',
            'customer_name' => 'required|string|max:150',
            'customer_signature' => 'required|string',
            'employee_name' => 'required|string|max:150',
            'employee_signature' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $agreementNumber = CustomerAgreement::generateAgreementNumber($validated['stage']);

            $agreement = CustomerAgreement::create([
                'agreement_number' => $agreementNumber,
                'customer_id' => $customer->id,
                'terms_template_id' => $validated['terms_template_id'] ?? null,
                'stage' => $validated['stage'],
                'title' => $validated['title'] ?? 'Surat Perjanjian & Syarat Ketentuan Berlangganan',
                'content_snapshot' => $validated['content_snapshot'],
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $customer->phone,
                'customer_signature' => $validated['customer_signature'],
                'customer_signed_at' => now(),
                'employee_id' => auth()->id(),
                'employee_name' => $validated['employee_name'],
                'employee_signature' => $validated['employee_signature'],
                'employee_signed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'signed',
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Surat Perjanjian & Tanda Tangan Digital berhasil disimpan.',
                'agreement' => $agreement,
                'print_url' => route('agreements.print', $agreement->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[SubscriptionTermsController::storeCustomerAgreement] Failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan tanda tangan perjanjian: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cetak Lembar Perjanjian & T&C Bertanda Tangan Digital
     */
    public function printAgreement(CustomerAgreement $agreement)
    {
        $agreement->loadMissing(['customer.package', 'customer.areaModel', 'customer.sales.employee', 'employee']);

        $company = [
            'name' => Setting::get('company_name', config('app.name', 'Vizbill ISP')),
            'address' => Setting::get('company_address', 'Layanan Internet Cepat, Stabil & Terpercaya'),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'logo' => Setting::get('company_logo', null),
        ];

        return view('print.agreement', compact('agreement', 'company'));
    }

    /**
     * Default content template
     */
    private function getDefaultContent(): string
    {
        return <<<'EOT'
<h3>PASAL 1 - KETENTUAN UMUM & DEFINISI</h3>
<ol>
  <li><strong>Penyedia Layanan</strong> adalah <strong>{perusahaan}</strong> yang menyediakan layanan sambungan internet dan jaringan komunikasi data resmi.</li>
  <li><strong>Pelanggan</strong> adalah perorangan, lembaga, atau badan hukum atas nama <strong>{nama_pelanggan}</strong> (ID Pelanggan: <strong>{nomor_layanan}</strong>) yang berdomisili di <strong>{alamat}</strong> yang menyetujui berlangganan layanan internet.</li>
  <li><strong>Layanan</strong> adalah penyediaan sambungan internet paket <strong>{paket}</strong> dengan kecepatan hingga <strong>{kecepatan}</strong>.</li>
</ol>

<h3>PASAL 2 - BIAYA LAYANAN & PEMBAYARAN</h3>
<ol>
  <li>Biaya registrasi / instalasi awal disepakati sebesar <strong>{biaya_pasang}</strong> dibayarkan sesuai ketentuan pendaftaran.</li>
  <li>Biaya langganan bulanan paket <strong>{paket}</strong> adalah sebesar <strong>{tarif}</strong> per bulan.</li>
  <li>Pembayaran tagihan bulanan dilakukan selambat-lambatnya sebelum tanggal jatuh tempo setiap bulannya melalui rekening atau channel resmi Penyedia Layanan.</li>
  <li>Keterlambatan pembayaran tagihan dapat mengakibatkan penghentian sementara (isolir) layanan internet sampai seluruh tunggakan dilunasi.</li>
</ol>

<h3>PASAL 3 - PERANGKAT JARINGAN & PEMELIHARAAN</h3>
<ol>
  <li>Perangkat keras jaringan berupa Optical Network Terminal (ONT / Modem), Kabel Fiber Optik Dropcore, dan adaptor listrik yang dipasang di lokasi Pelanggan berstatus <strong>PINJAM PAKAI</strong> milik Penyedia Layanan.</li>
  <li>Pelanggan berkewajiban merawat dan menjaga perangkat dalam kondisi baik, serta tidak memindahkan, membongkar, atau merubah konfigurasi perangkat tanpa izin resmi teknisi.</li>
  <li>Kerusakan atau kehilangan perangkat yang diakibatkan oleh kelalaian Pelanggan (seperti terjatuh, terbakar, terkena air, atau hilang) menjadi tanggung jawab Pelanggan dan wajib mengganti biaya perbaikan/penggantian unit sesuai tarif yang berlaku.</li>
</ol>

<h3>PASAL 4 - HAK DAN KEWAJIBAN</h3>
<ol>
  <li>Penyedia Layanan berkewajiban menjaga kestabilan dan ketersediaan layanan internet secara optimal, serta menindaklanjuti laporan gangguan teknis secepatnya sesuai jam operasional.</li>
  <li>Pelanggan berhak menikmati sambungan internet sesuai spesifikasi paket yang dipilih selama masa berlangganan aktif.</li>
  <li>Pelanggan DILARANG keras menyalahgunakan layanan internet untuk aktivitas melanggar hukum di wilayah Republik Indonesia (seperti perjudian online, hacking, penipuan digital, pornografi anak, atau penyebaran konten terorisme/SARA).</li>
  <li>Pelanggan DILARANG memperjualbelikan kembali (reselling / RT-RW Net) kapasitas internet yang diterima kepada pihak ketiga tanpa perjanjian kemitraan resmi tertulis dengan Penyedia Layanan.</li>
</ol>

<h3>PASAL 5 - PEMUTUSAN BERLANGGANAN & PENGEMBALIAN PERANGKAT</h3>
<ol>
  <li>Pelanggan yang bermaksud berhenti berlangganan wajib memberitahukan Penyedia Layanan sekurang-kurangnya 7 (tujuh) hari kerja sebelum periode tagihan berikutnya.</li>
  <li>Saat pemutusan layanan dilakukan, teknisi resmi berhak mengambil kembali seluruh perangkat pinjam pakai (ONT modem & adaptor) dalam keadaan baik dan lengkap.</li>
</ol>

<h3>PASAL 6 - PERSETUJUAN & KEKUATAN HUKUM</h3>
<p>Dengan membubuhkan tanda tangan digital di bawah ini, Pelanggan dan Petugas/Karyawan yang bertugas menyatakan bahwa seluruh data yang tercantum adalah benar serta telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan yang berlaku tanpa ada paksaan dari pihak manapun.</p>
EOT;
    }
}

