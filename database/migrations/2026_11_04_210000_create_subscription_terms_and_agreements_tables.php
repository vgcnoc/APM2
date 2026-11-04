<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Template Syarat & Ketentuan
        if (!Schema::hasTable('subscription_terms_templates')) {
            Schema::create('subscription_terms_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title')->default('SURAT PERJANJIAN & SYARAT KETENTUAN BERLANGGANAN LAYANAN INTERNET');
                $table->string('type')->default('general'); // general, booking, survey, installation
                $table->longText('content');
                $table->boolean('requires_employee_signature')->default(true);
                $table->boolean('requires_customer_signature')->default(true);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 2. Tabel Dokumen Persetujuan Pelanggan & Tanda Tangan Digital
        if (!Schema::hasTable('customer_agreements')) {
            Schema::create('customer_agreements', function (Blueprint $table) {
                $table->id();
                $table->string('agreement_number')->unique();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('terms_template_id')->nullable()->constrained('subscription_terms_templates')->nullOnDelete();
                $table->string('stage', 50)->default('booking'); // booking, survey, installation, general
                $table->string('title');
                $table->longText('content_snapshot');
                
                // Pihak Pelanggan
                $table->string('customer_name');
                $table->string('customer_phone')->nullable();
                $table->longText('customer_signature')->nullable(); // Base64 data URL
                $table->timestamp('customer_signed_at')->nullable();

                // Pihak Karyawan / Petugas
                $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('employee_name');
                $table->longText('employee_signature')->nullable(); // Base64 data URL
                $table->timestamp('employee_signed_at')->nullable();

                // Metadata
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('status', 30)->default('signed'); // signed, draft, cancelled
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'stage']);
                $table->index('agreement_number');
            });
        }

        // 3. Seed Default Template jika belum ada
        $existingCount = DB::table('subscription_terms_templates')->count();
        if ($existingCount === 0) {
            $defaultContent = <<<'EOT'
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

            DB::table('subscription_terms_templates')->insert([
                'title' => 'SURAT PERJANJIAN & SYARAT KETENTUAN BERLANGGANAN LAYANAN INTERNET',
                'type' => 'general',
                'content' => $defaultContent,
                'requires_employee_signature' => true,
                'requires_customer_signature' => true,
                'is_active' => true,
                'notes' => 'Template standar legalitas berlangganan ISP',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_agreements');
        Schema::dropIfExists('subscription_terms_templates');
    }
};

