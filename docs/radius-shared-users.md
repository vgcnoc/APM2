# Shared Users voucher

Shared Users membatasi sesi login bersamaan per username, bukan jumlah perangkat di balik NAT atau jumlah pemakaian bergantian.

- Profil biasa: ikuti `shared_users`, minimal 1.
- Hotspot Gratis Pelanggan: selalu 1, termasuk profil lama yang nilai tersimpannya keliru.
- `radgroupcheck`: `Simultaneous-Use := batas` untuk pemeriksaan lintas NAS.
- `radgroupreply`: `Port-Limit := batas` untuk pembatasan Hotspot langsung di MikroTik, termasuk login cookie/mac-cookie dan login serentak sebelum accounting masuk.

Script router menyediakan fallback `shared-users=1` pada profil default dan profil VIZBILL. Atribut Port-Limit menggantikan batas default router sesuai profil yang dibeli, sehingga paket berbayar dengan batas lebih dari satu tetap didukung. Jangan mengubah profil kustom lain milik operator. Pada router lama, periksa akun lokal sebelum mengubah fallback default agar akun di luar billing tidak ikut terpengaruh.

## FreeRADIUS 3 / SQL

1. Pastikan SQL aktif pada `authorize` dan `accounting`, tabel `radacct` dan `nasreload` tersedia, serta interim update diterima. Gunakan interval 60 detik.
2. Aktifkan `sql` pada `session {}` di `/etc/freeradius/3.0/sites-enabled/default`. Mengaktifkan SQL hanya pada authorize/accounting tidak cukup. Untuk EAP, evaluasi virtual server inner-tunnel secara terpisah.
3. Jika checkrad/SNMP belum dikonfigurasi untuk memverifikasi sesi MikroTik, gunakan `simul_verify_query = ""` pada `mods-config/sql/main/mysql/queries.conf`. Pertahankan `simul_count_query` standar, yang menghitung sesi SQL belum berhenti dan memperhitungkan NAS reload. Verifikasi SNMP yang gagal dapat salah menganggap sesi aktif hilang. Dengan mode SQL-only, sesi accounting yang benar-benar yatim perlu direkonsiliasi setelah diverifikasi terhadap router; jangan hapus sesi hanya berdasarkan umurnya.
4. Buat salinan konfigurasi sebelum perubahan. Jalankan `freeradius -C` dan pastikan valid sebelum reload/restart. Jangan menampilkan isi konfigurasi SQL, shared secret, atau password pelanggan ke log publik.
5. Jalankan `php artisan radius:sync-voucher-limits` untuk pemeriksaan, lalu `php artisan radius:sync-voucher-limits --apply` sebagai user aplikasi. Perintah hanya memperbarui batas profil, tidak mengubah akun atau memutus sesi.
6. Sesi yang sudah aktif belum tentu menerima atribut baru. Jika ada akun melampaui batas, baca ulang daftar sesi router dan putuskan hanya sesi berlebih milik akun itu, mempertahankan sesi pertama. Jangan disconnect seluruh username atau menghapus accounting aktif.

## Pengujian

Gunakan akun uji khusus, bukan kredensial pelanggan: login pertama diterima dan mengandung Port-Limit yang benar, sedangkan login kedua dengan username yang sama saat sesi pertama masih aktif harus ditolak. Uji profil berbayar bernilai 2 agar sesi kedua tetap diperbolehkan dan sesi ketiga ditolak. Hapus hanya data akun/sesi uji setelah selesai.

Referensi: [MikroTik RADIUS](https://help.mikrotik.com/docs/spaces/ROS/pages/328097/RADIUS), [FreeRADIUS simultaneous-use](https://www.freeradius.org/documentation/freeradius-server/3.2.8/howto/simultaneous_use.html).
