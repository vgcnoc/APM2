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
        $newTitle = 'SURAT PERJANJIAN BERLANGGANAN INTERNET PT VIRUZS GLOBAL CONNECTION DENGAN PELANGGAN';

        $newContent = <<<'EOT'
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
  <li>Melakukan pembayaran registrasi sebesar Rp 120,000,- (Jika tidak ada promo gratis pemasangan), ketika perangkat wifi sudah terpasang dan sudah dilakukan proses aktivasi. (Tidak di perkenankan membayar registrasi ketika booking atau survey).</li>
  <li>Minimal kontrak berlangganan selama 6 (enam) bulan . Apabila berhenti berlangganan dibawah 6 (enam) bulan maka harus membayar sisa waktunya.</li>
  <li>Perangkat WIFI seperti router/ONT dipinjamkan selama berlangganan.</li>
  <li>Pembayaran internet bulanan dapat dilakukan per-tanggal 1 dan jatuh tempo tanggal 15. Pada saat pelanggan tidak membayar melewati jatuh tempo, maka internet akan putus secara otomatis. Begitupun dibulan kedua pembayaran internet bulanan masih juga belum dilunasi , maka tunggakan menjadi 2 bulan dan seterusnya , meskipun internet dibulan ke 2 tidak terkoneksi/putus.</li>
  <li>Apabila 3 bulan masih menunggak maka alat akan dicabut kembali oleh pihak kami.</li>
  <li>Pembayaran melalui transfer BANK BRI 410101025028534 a/n Deri Gantinayasa , setelah transfer pelanggan diharuskan konfirmasi dengan mengirim bukti transfer ke No Whatsapp 088976397034, Pelunasan otomatis silahkan login ke Aplikasi Client Area : <a href="https://viruzs.rlradius.com" target="_blank" style="color: #4f46e5; text-decoration: underline;">https://viruzs.rlradius.com</a> , login menggunakan No Whatsapp aktif pertama kali Registrasi. Tagihan otomatis lunas setelah melakukan pembayaran tanpa perlu konfirmasi jika menggunakan QRIS ( OVO , DANA, GOPAY, dll.) , Shopee Pay, Virtual Account ( BRI , MANDIRI, BSI, BNI), Indomart, Alfamart, DLL.</li>
</ol>

<h3>Penutup</h3>
<p>Dengan ini, kedua belah pihak menyatakan sepakat dengan kewajiban dan syarat ketentuan yang telah disebutkan di atas, dan berjanji untuk mematuhinya selama masa berlakunya layanan.</p>
EOT;

        if (Schema::hasTable('subscription_terms_templates')) {
            $count = DB::table('subscription_terms_templates')->count();
            if ($count > 0) {
                DB::table('subscription_terms_templates')->update([
                    'title' => $newTitle,
                    'content' => $newContent,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('subscription_terms_templates')->insert([
                    'title' => $newTitle,
                    'type' => 'general',
                    'content' => $newContent,
                    'requires_employee_signature' => true,
                    'requires_customer_signature' => true,
                    'is_active' => true,
                    'notes' => 'Template resmi PT VIRUZS GLOBAL CONNECTION',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down rollback required for content update
    }
};
