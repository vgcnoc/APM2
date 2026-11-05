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
                'title' => 'SURAT PERJANJIAN BERLANGGANAN INTERNET PT VIRUZS GLOBAL CONNECTION DENGAN PELANGGAN',
                'type' => 'general',
                'content' => $this->getDefaultContent(),
                'requires_employee_signature' => true,
                'requires_customer_signature' => true,
                'is_active' => true,
                'notes' => 'Template resmi PT VIRUZS GLOBAL CONNECTION',
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
                'title' => 'SURAT PERJANJIAN BERLANGGANAN INTERNET PT VIRUZS GLOBAL CONNECTION DENGAN PELANGGAN',
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
                'title' => 'SURAT PERJANJIAN BERLANGGANAN INTERNET PT VIRUZS GLOBAL CONNECTION DENGAN PELANGGAN',
                'type' => 'general',
                'content' => $this->getDefaultContent(),
                'requires_employee_signature' => true,
                'requires_customer_signature' => true,
                'is_active' => true,
                'notes' => 'Template resmi PT VIRUZS GLOBAL CONNECTION',
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

        $basePrice = (float) ($customer->base_amount ?: ($customer->package?->price ?? 0));
        $globalTaxPpn = (float) \App\Models\Setting::get('tax_ppn', '0');
        $taxPpn = (float) ($customer->tax_ppn ?? $globalTaxPpn);
        $priceWithTax = $basePrice;
        if ($taxPpn > 0 && !in_array($customer->service_status, ['gratis', 'free_home'], true)) {
            $raw = $basePrice * (1 + ($taxPpn / 100));
            $rounded = (int) round($raw);
            if (abs($rounded - round($rounded / 50) * 50) <= 1) {
                $priceWithTax = (int) (round($rounded / 50) * 50);
            } else {
                $priceWithTax = $rounded;
            }
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
                'package_speed' => $customer->package?->speed_mbps ? ($customer->package->speed_mbps . ' Mbps') : ($customer->package?->speed ?? '-'),
                'price' => $priceWithTax,
                'price_before_tax' => $basePrice,
                'price_with_ppn' => $priceWithTax,
                'tax_ppn' => $taxPpn,
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
                'print_url' => url('/agreements/' . $agreement->id . '/print'),
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
    public function printAgreement($agreement)
    {
        if (!$agreement instanceof CustomerAgreement) {
            $agreement = CustomerAgreement::findOrFail($agreement);
        }

        $agreement->loadMissing([
            'customer.package',
            'customer.areaModel',
            'customer.surveys.surveyor',
            'customer.technicianSchedules.technician',
            'customer.sales.employee',
            'employee'
        ]);

        $surveyTechnician = $agreement->customer?->surveys?->last()?->surveyor?->name;
        $installationTechnician = $agreement->customer?->technicianSchedules?->last()?->technician?->name;

        $company = [
            'name' => Setting::get('company_name', 'PT VIRUZS GLOBAL CONNECTION'),
            'address' => Setting::get('company_address', 'Jl.Perintis Kemerdekaan No.12A Kel.Sukamulya Kec.Cikembar Sukabumi'),
            'phone' => Setting::get('company_phone', '088976397034'),
            'email' => Setting::get('company_email', ''),
            'logo' => Setting::get('company_logo', null),
        ];

        return view('print.agreement', compact('agreement', 'company', 'surveyTechnician', 'installationTechnician'));
    }

    /**
     * Default content template
     */
    private function getDefaultContent(): string
    {
        return <<<'EOT'
<p>Yang bertanda tangan dibawah ini:</p>

<table style="width: 100%; margin-bottom: 8px; border-collapse: collapse;">
  <tr>
    <td style="width: 110px; font-weight: 600; vertical-align: top; padding: 2px 0;">Nama</td>
    <td style="width: 15px; vertical-align: top; padding: 2px 0;">:</td>
    <td style="vertical-align: top; padding: 2px 0;"><strong>VIRUZS.NET</strong></td>
  </tr>
  <tr>
    <td style="font-weight: 600; vertical-align: top; padding: 2px 0;">Status</td>
    <td style="vertical-align: top; padding: 2px 0;">:</td>
    <td style="vertical-align: top; padding: 2px 0;">Penyedia Jasa Internet</td>
  </tr>
  <tr>
    <td style="font-weight: 600; vertical-align: top; padding: 2px 0;">Alamat</td>
    <td style="vertical-align: top; padding: 2px 0;">:</td>
    <td style="vertical-align: top; padding: 2px 0;">Jl.Perintis Kemerdekaan No.12A Kel.Sukamulya Kec.Cikembar Sukabumi</td>
  </tr>
</table>

<p>Disebut sebagai VIRUZS.NET ( mewakili ) <strong>PIHAK PERTAMA</strong>:</p>

<table style="width: 100%; margin-bottom: 8px; border-collapse: collapse;">
  <tr>
    <td style="width: 110px; font-weight: 600; vertical-align: top; padding: 2px 0;">Nama</td>
    <td style="width: 15px; vertical-align: top; padding: 2px 0;">:</td>
    <td style="vertical-align: top; padding: 2px 0;"><strong>{nama_pelanggan}</strong></td>
  </tr>
  <tr>
    <td style="font-weight: 600; vertical-align: top; padding: 2px 0;">Alamat</td>
    <td style="vertical-align: top; padding: 2px 0;">:</td>
    <td style="vertical-align: top; padding: 2px 0;">{alamat}</td>
  </tr>
  <tr>
    <td style="font-weight: 600; vertical-align: top; padding: 2px 0;">NIK KTP</td>
    <td style="vertical-align: top; padding: 2px 0;">:</td>
    <td style="vertical-align: top; padding: 2px 0;">{nik_ktp}</td>
  </tr>
</table>

<p>Disebut sebagai PELANGGAN <strong>PIHAK KEDUA</strong>.</p>

<p>Dengan ini menyatakan bahwa kedua belah pihak sepakat untuk bekerja sama dalam penyediaan dan penggunaan layanan internet dengan ketentuan sebagai berikut:</p>

<h3>Kewajiban Pihak Pertama (Penyedia Jasa Internet)</h3>
<ol>
  <li>
    <strong>Menyediakan Layanan Internet</strong><br>
    Pihak Pertama berkomitmen untuk menyediakan layanan internet dengan spesifikasi kecepatan Bandwidth Up To dan menyediakan alat untuk koneksi internet.
  </li>
  <li>
    <strong>Menjaga Kualitas Layanan</strong><br>
    Pihak Pertama bertanggung jawab untuk menjaga kualitas layanan internet agar sesuai dengan standar yang dijanjikan.
  </li>
  <li>
    <strong>Memberikan Dukungan Teknis</strong><br>
    Pihak Pertama akan memberikan dukungan teknis jika Pihak Kedua mengalami masalah dalam penggunaan layanan internet. Layanan dukungan ini dapat diakses melalui nomor kontak admin di grup pelanggan.
  </li>
  <li>
    <strong>Pemulihan Gangguan Layanan</strong><br>
    Pihak Pertama bertanggung jawab untuk segera memperbaiki dan memulihkan layanan internet jika terjadi gangguan teknis, sesuai dengan ketentuan waktu perbaikan yang wajar.
  </li>
</ol>

<h3>Syarat dan Ketentuan untuk pelanggan</h3>
<ol>
  <li>Melakukan pembayaran registrasi sebesar {biaya_registrasi} (Jika tidak ada promo gratis pemasangan), ketika perangkat wifi sudah terpasang dan sudah dilakukan proses aktivasi. (Tidak di perkenankan membayar registrasi ketika booking atau survey).</li>
  <li>Minimal kontrak berlangganan selama 6 (enam) bulan . Apabila berhenti berlangganan dibawah 6 (enam) bulan maka harus membayar sisa waktunya.</li>
  <li>Perangkat WIFI seperti router/ONT dipinjamkan selama berlangganan.</li>
  <li>Pembayaran internet bulanan dapat dilakukan per-tanggal 1 dan jatuh tempo tanggal 15. Pada saat pelanggan tidak membayar melewati jatuh tempo, maka internet akan putus secara otomatis. Begitupun dibulan kedua pembayaran internet bulanan masih juga belum dilunasi , maka tunggakan menjadi 2 bulan dan seterusnya , meskipun internet dibulan ke 2 tidak terkoneksi/putus.</li>
  <li>Apabila 3 bulan masih menunggak maka alat akan dicabut kembali oleh pihak kami.</li>
  <li>Pembayaran melalui transfer BANK BRI 410101025028534 a/n Deri Gantinayasa , setelah transfer pelanggan diharuskan konfirmasi dengan mengirim bukti transfer ke No Whatsapp 088976397034, Pelunasan otomatis silahkan login ke Aplikasi Client Area : <a href="{client_area_url}" target="_blank" style="color: #4f46e5; text-decoration: underline;">{client_area_url}</a> , login menggunakan No Whatsapp aktif pertama kali Registrasi. Tagihan otomatis lunas setelah melakukan pembayaran tanpa perlu konfirmasi jika menggunakan QRIS ( OVO , DANA, GOPAY, dll.) , Shopee Pay, Virtual Account ( BRI , MANDIRI, BSI, BNI), Indomart, Alfamart, DLL.</li>
</ol>

<h3>Penutup</h3>
<p>Dengan ini, kedua belah pihak menyatakan sepakat dengan kewajiban dan syarat ketentuan yang telah disebutkan di atas, dan berjanji untuk mematuhinya selama masa berlakunya layanan.</p>
EOT;
    }
}

