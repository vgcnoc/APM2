# NAS / Router: alamat unik dan penerapan RADIUS

Setiap router menggunakan satu IP sumber RADIUS yang tetap. Router VPN baru mendapat IP unik dari pool VPN; `nasname` harus sama dengan IP tersebut. Koneksi langsung wajib menggunakan IP spesifik. Wildcard/subnet dan IP ganda ditolak oleh service serta unique index database.

Membuka halaman NAS tidak lagi membuat/mengganti kredensial atau menulis file VPN. Password hanya diganti melalui aksi Generate Ulang yang dikonfirmasi pengguna. NAS yang terhubung ke Data Router harus dihapus lewat Data Router.

## Koreksi data lama

Cadangkan database dan file akun VPN ke lokasi privat sebelum deployment. Jangan menjalankan seluruh migration yang tertunda tanpa audit terpisah.

```sh
php artisan radius:repair-nas
php artisan radius:repair-nas --apply
php artisan migrate --path=database/migrations/2026_10_25_060000_enforce_unique_nas_address.php --force
php artisan radius:repair-nas --apply --reload
```

Mode awal hanya menampilkan rencana. Koreksi menggunakan IP VPN yang sudah ada, tidak mengalokasikan ulang IP, merotasi password, atau menghapus NAS. Jika ada alamat yang tidak dapat ditentukan/masih ganda, seluruh koreksi dibatalkan. Backup alamat dibuat di disk `local`, direktori `nas-backups`, sebelum transaksi perubahan. Backup tersebut tidak mengandung kredensial.

## Penerapan FreeRADIUS

Client SQL dibaca ketika FreeRADIUS dimulai, bukan setiap tabel `nas` berubah. Setelah mutasi, aplikasi menyinkronkan blok akun VPN terkelola, melakukan check-only FreeRADIUS, memeriksa warning duplicate-client (termasuk ketika exit code 0), lalu restart singkat dan memastikan layanan aktif. Hasil gagal ditampilkan sebagai konfigurasi belum diterapkan; kegagalan validasi tidak memicu restart. Penerapan tidak menjamin router fisik telah tersambung.

Untuk deployment berbasis systemd dengan executable standar, sudoers dapat membatasi `www-data` ke dua perintah **persis** berikut (validasi file menggunakan `visudo`):

```sudoers
www-data ALL=(root) NOPASSWD: /usr/sbin/freeradius -C -l stdout, /usr/bin/systemctl restart freeradius
```

Konfigurasi aplikasi: `RADIUS_CHECK_COMMAND`, `RADIUS_RESTART_COMMAND`, `RADIUS_STATUS_COMMAND`, `RADIUS_NAS_AUTO_APPLY`. `RADIUS_NAS_AUTO_APPLY=false` menunda penerapan hingga aksi admin. Path executable dan nama unit harus diverifikasi pada VPS, bukan disalin tanpa pemeriksaan. Restart singkat dapat menunda permintaan autentikasi; jangan mengirim CoA atau memutus pelanggan sebagai bagian deployment ini.

## Monitoring

- Status API online memerlukan login dan pembacaan resource yang berhasil, bukan sekadar TCP terbuka. Status accounting dilaporkan terpisah.
- Sesi online harus mempunyai update accounting mutakhir (minimum 180 detik atau tiga interval konfigurasi).
- Data accounting dipetakan ke IP NAS/VPN, termasuk fallback untuk data wildcard lama. Filter area tanpa izin menghasilkan scope kosong.
- Mode seluruh router menjumlahkan hasil terukur, dengan penanda cakupan parsial jika tidak semua NAS dapat diukur. Cache history/peak dipisahkan menurut NAS, area yang diizinkan, dan tanggal WIB.
- Tidak ada data = `null` / “data belum tersedia”, berbeda dari trafik yang benar-benar diukur 0 Mbps. History hanya berisi sampel terukur.
- Fallback rate RADIUS memakai delta counter per sesi dibagi interval **accounting**, bukan interval polling browser. Sesi baru, stale, hilang, atau counter reset tidak boleh menghasilkan lonjakan palsu.
- Volume harian adalah volume **RADIUS teramati**, bukan seluruh trafik WAN. Hari dimulai pukul 00:00 WIB. Sesi lintas tengah malam menggunakan baseline saat pertama diamati dan berlabel parsial; counter lifetime tidak boleh disebut pemakaian penuh hari ini. Cache kosong tidak dapat merekonstruksi histori tengah malam.

## Verifikasi operasional

Periksa service, warning konfigurasi, alamat unik, konsistensi akun VPN tanpa menampilkan secret, dan HTTP aplikasi. Dari router, verifikasi tunnel, IP tetap, rute ke gateway billing, akses API, `src-address` RADIUS, dan pembaruan accounting. Bila API/VPN belum terjangkau, jangan menyatakan koneksi normal hanya karena service VPS aktif. Jangan menghapus rute VPN tumpang tindih atau mengaktifkan firewall global tanpa inventaris layanan lain.

Referensi: [FreeRADIUS SQL clients](https://github.com/FreeRADIUS/freeradius-server/blob/v3.2.x/raddb/mods-available/sql), [FreeRADIUS check-only](https://www.freeradius.org/radiusd/man/radiusd.html), [MikroTik RADIUS](https://manual.mikrotik.com/docs/authentication-authorization-accounting/radius/).

Uji regresi terisolasi:

```sh
php vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Feature/NasRouterRegressionTest.php --no-progress
```
