#!/bin/bash
# Script to install and configure FreeRADIUS with MySQL/MariaDB for Billing RADIUS
# Run this script as root on your VPS

echo "Mulai instalasi FreeRADIUS dan dependensi..."
apt-get update
apt-get install -y freeradius freeradius-mysql freeradius-utils mariadb-client

echo "Mengaktifkan modul SQL di FreeRADIUS..."
ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/ 2>/dev/null

echo "Silakan edit file konfigurasi SQL FreeRADIUS:"
echo "nano /etc/freeradius/3.0/mods-available/sql"
echo "Pastikan mengubah bagian:"
echo "  driver = \"rlm_sql_mysql\""
echo "  server = \"localhost\""
echo "  port = 3306"
echo "  login = \"user_database_anda\""
echo "  password = \"password_database_anda\""
echo "  radius_db = \"nama_database_radius_anda\""
echo ""

echo "Jika Anda menggunakan satu database untuk Laravel dan RADIUS, gunakan kredensial Laravel Anda."
echo "Setelah diedit, jalankan perintah ini untuk merestart FreeRADIUS:"
echo "systemctl restart freeradius"
echo "systemctl enable freeradius"
echo ""
echo "Selesai!"
