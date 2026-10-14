<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Master list of all permissions grouped by module.
     * Each module has: menu, features, actions.
     */
    public static function getPermissionRegistry(): array
    {
        return [
            [
                'group' => 'Dashboard',
                'icon' => 'dashboard',
                'permissions' => [
                    ['name' => 'menu_dashboard', 'type' => 'menu', 'label' => 'Akses Menu Dashboard'],
                    ['name' => 'dashboard_view_stats', 'type' => 'feature', 'label' => 'Lihat Statistik'],
                    ['name' => 'dashboard_view_charts', 'type' => 'feature', 'label' => 'Lihat Grafik'],
                ],
            ],
            [
                'group' => 'Data Booking',
                'icon' => 'document-add',
                'permissions' => [
                    ['name' => 'menu_customers_booking', 'type' => 'menu', 'label' => 'Akses Menu Booking'],
                    ['name' => 'customers_booking_create', 'type' => 'action', 'label' => 'Tambah Booking'],
                    ['name' => 'customers_booking_edit', 'type' => 'action', 'label' => 'Edit Booking'],
                    ['name' => 'customers_booking_delete', 'type' => 'action', 'label' => 'Hapus Booking'],
                    ['name' => 'customers_booking_request_survey', 'type' => 'feature', 'label' => 'Request Survey'],
                    ['name' => 'customers_booking_whatsapp', 'type' => 'feature', 'label' => 'Kirim WhatsApp'],
                    ['name' => 'customers_booking_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_booking_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Survey',
                'icon' => 'clipboard-check',
                'permissions' => [
                    ['name' => 'menu_customers_survey', 'type' => 'menu', 'label' => 'Akses Menu Survey'],
                    ['name' => 'customers_survey_assign', 'type' => 'action', 'label' => 'Jadwalkan Survey'],
                    ['name' => 'customers_survey_reschedule', 'type' => 'action', 'label' => 'Reschedule Survey'],
                    ['name' => 'customers_survey_report', 'type' => 'feature', 'label' => 'Laporan Hasil Survey'],
                    ['name' => 'customers_survey_mark_ready', 'type' => 'feature', 'label' => 'Tandai Ready Install'],
                    ['name' => 'customers_survey_edit', 'type' => 'action', 'label' => 'Edit Data Survey'],
                    ['name' => 'customers_survey_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_survey_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Instalasi Jaringan',
                'icon' => 'cog',
                'permissions' => [
                    ['name' => 'menu_customers_installed', 'type' => 'menu', 'label' => 'Akses Menu Instalasi'],
                    ['name' => 'customers_installed_assign', 'type' => 'action', 'label' => 'Jadwalkan Pasang'],
                    ['name' => 'customers_installed_report', 'type' => 'feature', 'label' => 'Laporan Instalasi'],
                    ['name' => 'customers_installed_audit', 'type' => 'feature', 'label' => 'Audit Instalasi'],
                    ['name' => 'customers_installed_edit', 'type' => 'action', 'label' => 'Edit Data Instalasi'],
                    ['name' => 'customers_installed_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_installed_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Aktivasi Layanan',
                'icon' => 'key',
                'permissions' => [
                    ['name' => 'menu_customers_activation', 'type' => 'menu', 'label' => 'Akses Menu Aktivasi'],
                    ['name' => 'customers_activation_activate', 'type' => 'action', 'label' => 'Aktivasi Pelanggan'],
                    ['name' => 'customers_activation_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_activation_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Pelanggan Aktif & Layanan',
                'icon' => 'badge-check',
                'permissions' => [
                    ['name' => 'menu_customers_active', 'type' => 'menu', 'label' => 'Akses Pelanggan Aktif'],
                    ['name' => 'customers_active_edit', 'type' => 'action', 'label' => 'Edit Data Pelanggan'],
                    ['name' => 'customers_active_suspend', 'type' => 'action', 'label' => 'Isolir / Suspend Layanan'],
                    ['name' => 'customers_active_terminate', 'type' => 'action', 'label' => 'Terminate / Stop Permanen'],
                    ['name' => 'customers_active_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_active_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Semua Pelanggan',
                'icon' => 'users',
                'permissions' => [
                    ['name' => 'menu_customers_all', 'type' => 'menu', 'label' => 'Akses Menu Semua Pelanggan'],
                    ['name' => 'customers_all_create', 'type' => 'action', 'label' => 'Tambah Pelanggan Baru'],
                    ['name' => 'customers_all_edit', 'type' => 'action', 'label' => 'Edit Pelanggan'],
                    ['name' => 'customers_delete', 'type' => 'action', 'label' => 'Hapus Pelanggan'],
                    ['name' => 'customers_all_export', 'type' => 'feature', 'label' => 'Export Data Pelanggan'],
                    ['name' => 'customers_all_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_all_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Pantauan Pelanggan',
                'icon' => 'calendar',
                'permissions' => [
                    ['name' => 'menu_monitoring_pelanggan', 'type' => 'menu', 'label' => 'Akses Pantauan Pelanggan'],
                    ['name' => 'monitoring_pelanggan_manage', 'type' => 'action', 'label' => 'Kelola Menunggak & Janji Bayar'],
                ],
            ],
            [
                'group' => 'Billing & Invoice Pelanggan',
                'icon' => 'document-text',
                'permissions' => [
                    ['name' => 'menu_invoices', 'type' => 'menu', 'label' => 'Akses Menu Invoice'],
                    ['name' => 'invoices_create', 'type' => 'action', 'label' => 'Terbitkan Invoice'],
                    ['name' => 'invoices_edit', 'type' => 'action', 'label' => 'Edit Invoice'],
                    ['name' => 'invoices_delete', 'type' => 'action', 'label' => 'Hapus Invoice'],
                    ['name' => 'invoices_pay', 'type' => 'action', 'label' => 'Proses Bayar Invoice'],
                    ['name' => 'invoices_rollback', 'type' => 'action', 'label' => 'Rollback Pembayaran'],
                    ['name' => 'invoices_promise', 'type' => 'feature', 'label' => 'Atur Janji Bayar'],
                    ['name' => 'invoices_change_package', 'type' => 'feature', 'label' => 'Ubah Paket Tagihan'],
                    ['name' => 'invoices_print', 'type' => 'feature', 'label' => 'Cetak Struk/Invoice'],
                    ['name' => 'invoices_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'invoices_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Penagihan Lapangan & Setoran',
                'icon' => 'cash',
                'permissions' => [
                    ['name' => 'menu_customer_billing', 'type' => 'menu', 'label' => 'Akses Penagihan Lapangan'],
                    ['name' => 'customer_billing_collect', 'type' => 'action', 'label' => 'Terima Setoran Tagihan'],
                    ['name' => 'customer_settlements_approve', 'type' => 'action', 'label' => 'Validasi / Approve Setoran'],
                ],
            ],
            [
                'group' => 'Laporan Keuangan & Pajak',
                'icon' => 'chart-pie',
                'permissions' => [
                    ['name' => 'menu_financial_reports', 'type' => 'menu', 'label' => 'Akses Laporan Keuangan'],
                    ['name' => 'financial_reports_view', 'type' => 'feature', 'label' => 'Lihat Buku Kas Global'],
                    ['name' => 'financial_reports_export', 'type' => 'feature', 'label' => 'Export Laporan Keuangan'],
                    ['name' => 'tax_reports_view', 'type' => 'feature', 'label' => 'Lihat & Cetak Laporan Pajak'],
                ],
            ],
            [
                'group' => 'Ticketing & CBP',
                'icon' => 'alert-circle',
                'permissions' => [
                    ['name' => 'menu_tickets', 'type' => 'menu', 'label' => 'Akses Menu Ticketing'],
                    ['name' => 'tickets_create', 'type' => 'action', 'label' => 'Buat Tiket'],
                    ['name' => 'tickets_edit', 'type' => 'action', 'label' => 'Edit Tiket'],
                    ['name' => 'tickets_delete', 'type' => 'action', 'label' => 'Hapus Tiket'],
                    ['name' => 'tickets_assign', 'type' => 'feature', 'label' => 'Assign Teknisi'],
                    ['name' => 'tickets_close', 'type' => 'feature', 'label' => 'Tutup Tiket'],
                    ['name' => 'tickets_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'tickets_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                    ['name' => 'menu_cbp', 'type' => 'menu', 'label' => 'Akses Cabut Perangkat (CBP)'],
                    ['name' => 'cbp_manage', 'type' => 'action', 'label' => 'Kelola Jadwal & Eksekusi CBP'],
                ],
            ],
            [
                'group' => 'Penanganan Khusus (PGK, PGJ, PGS)',
                'icon' => 'support',
                'permissions' => [
                    ['name' => 'menu_pgk', 'type' => 'menu', 'label' => 'Akses Komplain Pelanggan (PGK)'],
                    ['name' => 'pgk_manage', 'type' => 'action', 'label' => 'Kelola Komplain Masuk & Laporan PGK'],
                    ['name' => 'menu_pgj', 'type' => 'menu', 'label' => 'Akses Gangguan Jalur (PGJ)'],
                    ['name' => 'pgj_manage', 'type' => 'action', 'label' => 'Kelola Restorasi Jalur PGJ'],
                    ['name' => 'menu_pgs', 'type' => 'menu', 'label' => 'Akses Penanganan Sigap (PGS)'],
                    ['name' => 'pgs_manage', 'type' => 'action', 'label' => 'Kelola Tindakan Cepat PGS'],
                ],
            ],
            [
                'group' => 'Topologi & Radar Jaringan',
                'icon' => 'globe',
                'permissions' => [
                    ['name' => 'menu_network_topology', 'type' => 'menu', 'label' => 'Akses Network Topology & Radar'],
                    ['name' => 'menu_network_data', 'type' => 'menu', 'label' => 'Akses Data Jaringan'],
                    ['name' => 'menu_ftth', 'type' => 'menu', 'label' => 'Akses FTTH Mapping'],
                    ['name' => 'ftth_manage', 'type' => 'action', 'label' => 'Kelola Rute & Desain FTTH'],
                ],
            ],
            [
                'group' => 'OLT',
                'icon' => 'server',
                'permissions' => [
                    ['name' => 'menu_network_olt', 'type' => 'menu', 'label' => 'Akses Menu OLT'],
                    ['name' => 'network_olt_create', 'type' => 'action', 'label' => 'Tambah OLT'],
                    ['name' => 'network_olt_edit', 'type' => 'action', 'label' => 'Edit OLT'],
                    ['name' => 'network_olt_delete', 'type' => 'action', 'label' => 'Hapus OLT'],
                    ['name' => 'network_olt_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_olt_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'ODC',
                'icon' => 'box',
                'permissions' => [
                    ['name' => 'menu_network_odc', 'type' => 'menu', 'label' => 'Akses Menu ODC'],
                    ['name' => 'network_odc_create', 'type' => 'action', 'label' => 'Tambah ODC'],
                    ['name' => 'network_odc_edit', 'type' => 'action', 'label' => 'Edit ODC'],
                    ['name' => 'network_odc_delete', 'type' => 'action', 'label' => 'Hapus ODC'],
                    ['name' => 'network_odc_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_odc_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'ODP',
                'icon' => 'git-branch',
                'permissions' => [
                    ['name' => 'menu_network_odp', 'type' => 'menu', 'label' => 'Akses Menu ODP'],
                    ['name' => 'network_odp_create', 'type' => 'action', 'label' => 'Tambah ODP'],
                    ['name' => 'network_odp_edit', 'type' => 'action', 'label' => 'Edit ODP'],
                    ['name' => 'network_odp_delete', 'type' => 'action', 'label' => 'Hapus ODP'],
                    ['name' => 'network_odp_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_odp_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Rollout Pembangunan ODC & ODP',
                'icon' => 'zap',
                'permissions' => [
                    ['name' => 'menu_infrastructure_work_orders', 'type' => 'menu', 'label' => 'Akses Menu Rollout Work Orders'],
                    ['name' => 'infrastructure_work_orders_create', 'type' => 'action', 'label' => 'Buat Penugasan Baru'],
                    ['name' => 'infrastructure_work_orders_edit', 'type' => 'action', 'label' => 'Edit Penugasan'],
                    ['name' => 'infrastructure_work_orders_dispatch', 'type' => 'action', 'label' => 'Approve Pengeluaran Material'],
                    ['name' => 'infrastructure_work_orders_report', 'type' => 'feature', 'label' => 'Submit Laporan & QC OPM'],
                    ['name' => 'infrastructure_work_orders_approve', 'type' => 'action', 'label' => 'Approve Komisioning Otomatis'],
                    ['name' => 'infrastructure_work_orders_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'infrastructure_work_orders_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'ONT & Find ONU',
                'icon' => 'wifi',
                'permissions' => [
                    ['name' => 'menu_network_ont', 'type' => 'menu', 'label' => 'Akses Menu ONT'],
                    ['name' => 'network_ont_create', 'type' => 'action', 'label' => 'Tambah ONT'],
                    ['name' => 'network_ont_edit', 'type' => 'action', 'label' => 'Edit ONT'],
                    ['name' => 'network_ont_delete', 'type' => 'action', 'label' => 'Hapus ONT'],
                    ['name' => 'network_ont_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_ont_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                    ['name' => 'menu_network_find_onu', 'type' => 'menu', 'label' => 'Akses Menu Find ONU'],
                ],
            ],
            [
                'group' => 'MikroTik & Router',
                'icon' => 'server',
                'permissions' => [
                    ['name' => 'menu_routers', 'type' => 'menu', 'label' => 'Akses Menu Router MikroTik'],
                    ['name' => 'routers_create', 'type' => 'action', 'label' => 'Tambah Router'],
                    ['name' => 'routers_edit', 'type' => 'action', 'label' => 'Edit Router'],
                    ['name' => 'routers_delete', 'type' => 'action', 'label' => 'Hapus Router'],
                    ['name' => 'routers_ping', 'type' => 'feature', 'label' => 'Test Ping Router'],
                ],
            ],
            [
                'group' => 'RADIUS Billing System',
                'icon' => 'wifi',
                'permissions' => [
                    ['name' => 'menu_radius_nas', 'type' => 'menu', 'label' => 'Akses Menu NAS / RADIUS'],
                    ['name' => 'radius_nas_create', 'type' => 'action', 'label' => 'Tambah NAS'],
                    ['name' => 'radius_nas_edit', 'type' => 'action', 'label' => 'Edit NAS'],
                    ['name' => 'radius_nas_delete', 'type' => 'action', 'label' => 'Hapus NAS'],
                    ['name' => 'menu_radius_online_users', 'type' => 'menu', 'label' => 'Akses Pengguna Online RADIUS'],
                    ['name' => 'radius_online_users_disconnect', 'type' => 'action', 'label' => 'Disconnect Pengguna Online'],
                    ['name' => 'menu_radius_auth_logs', 'type' => 'menu', 'label' => 'Akses Log Autentikasi RADIUS'],
                ],
            ],
            [
                'group' => 'Material & Logistik Gudang',
                'icon' => 'archive',
                'permissions' => [
                    ['name' => 'menu_materials', 'type' => 'menu', 'label' => 'Akses Menu Material'],
                    ['name' => 'materials_create', 'type' => 'action', 'label' => 'Tambah Material'],
                    ['name' => 'materials_edit', 'type' => 'action', 'label' => 'Edit Material'],
                    ['name' => 'materials_delete', 'type' => 'action', 'label' => 'Hapus Material'],
                    ['name' => 'materials_add_stock', 'type' => 'feature', 'label' => 'Tambah Stok'],
                    ['name' => 'materials_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'materials_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                    ['name' => 'menu_material_transactions', 'type' => 'menu', 'label' => 'Akses Transaksi Pengambilan'],
                    ['name' => 'material_transactions_create', 'type' => 'action', 'label' => 'Buat Transaksi Pengambilan'],
                    ['name' => 'material_transactions_delete', 'type' => 'action', 'label' => 'Hapus Transaksi'],
                    ['name' => 'material_transactions_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'material_transactions_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                    ['name' => 'menu_inventory', 'type' => 'menu', 'label' => 'Akses Inventaris Toko & Stock Opname'],
                    ['name' => 'inventory_manage', 'type' => 'action', 'label' => 'Kelola Inventaris & Order Toko'],
                ],
            ],
            [
                'group' => 'Karyawan & SDM (HR)',
                'icon' => 'users',
                'permissions' => [
                    ['name' => 'menu_hr_employees', 'type' => 'menu', 'label' => 'Akses Data Karyawan'],
                    ['name' => 'hr_employees_create', 'type' => 'action', 'label' => 'Tambah Karyawan'],
                    ['name' => 'hr_employees_edit', 'type' => 'action', 'label' => 'Edit Karyawan'],
                    ['name' => 'hr_employees_delete', 'type' => 'action', 'label' => 'Hapus Karyawan'],
                    ['name' => 'hr_employees_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'hr_employees_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                    ['name' => 'menu_hr_positions', 'type' => 'menu', 'label' => 'Akses Posisi / Jabatan'],
                    ['name' => 'hr_positions_create', 'type' => 'action', 'label' => 'Tambah Jabatan'],
                    ['name' => 'hr_positions_edit', 'type' => 'action', 'label' => 'Edit Jabatan'],
                    ['name' => 'hr_positions_delete', 'type' => 'action', 'label' => 'Hapus Jabatan'],
                    ['name' => 'menu_shifts', 'type' => 'menu', 'label' => 'Akses Shift & Jadwal Roster'],
                    ['name' => 'shifts_manage', 'type' => 'action', 'label' => 'Kelola Shift & Roster Karyawan'],
                ],
            ],
            [
                'group' => 'Presensi & Absensi Karyawan',
                'icon' => 'badge-check',
                'permissions' => [
                    ['name' => 'menu_attendances', 'type' => 'menu', 'label' => 'Akses Presensi & Absensi'],
                    ['name' => 'attendances_manage', 'type' => 'action', 'label' => 'Kelola Absensi, Tugas Luar, Tim Khusus'],
                    ['name' => 'attendances_view_all', 'type' => 'feature', 'label' => 'Lihat Absensi All Area'],
                    ['name' => 'attendances_view_area', 'type' => 'feature', 'label' => 'Lihat Absensi Per Area'],
                ],
            ],
            [
                'group' => 'Penggajian & Insentif (Payroll)',
                'icon' => 'calculator',
                'permissions' => [
                    ['name' => 'menu_payroll', 'type' => 'menu', 'label' => 'Akses Menu Payroll & Gaji'],
                    ['name' => 'payroll_manage', 'type' => 'action', 'label' => 'Kelola Slip & Komponen Gaji'],
                    ['name' => 'payroll_disburse', 'type' => 'action', 'label' => 'Pencairan / Disburse Gaji'],
                    ['name' => 'payroll_incentives', 'type' => 'action', 'label' => 'Input Insentif & Potongan'],
                    ['name' => 'menu_target_market', 'type' => 'menu', 'label' => 'Akses Bonus Target Market'],
                    ['name' => 'target_market_manage', 'type' => 'action', 'label' => 'Kelola Program Bonus Target'],
                    ['name' => 'payroll_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'payroll_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Voucher & Reseller',
                'icon' => 'ticket',
                'permissions' => [
                    ['name' => 'menu_vouchers', 'type' => 'menu', 'label' => 'Akses Menu Voucher'],
                    ['name' => 'vouchers_create', 'type' => 'action', 'label' => 'Generate Voucher'],
                    ['name' => 'vouchers_delete', 'type' => 'action', 'label' => 'Hapus Voucher'],
                    ['name' => 'vouchers_print', 'type' => 'feature', 'label' => 'Cetak Voucher'],
                    ['name' => 'menu_vouchers_profiles', 'type' => 'menu', 'label' => 'Akses Profil Voucher'],
                    ['name' => 'menu_resellers', 'type' => 'menu', 'label' => 'Akses Menu Reseller'],
                    ['name' => 'resellers_create', 'type' => 'action', 'label' => 'Tambah Reseller'],
                    ['name' => 'resellers_edit', 'type' => 'action', 'label' => 'Edit Reseller'],
                    ['name' => 'resellers_delete', 'type' => 'action', 'label' => 'Hapus Reseller'],
                    ['name' => 'resellers_balance_approve', 'type' => 'action', 'label' => 'Approve Saldo Reseller'],
                    ['name' => 'resellers_billing_collect', 'type' => 'action', 'label' => 'Penagihan & Pelunasan Kasbon'],
                ],
            ],
            [
                'group' => 'Marketing Afiliasi',
                'icon' => 'gift',
                'permissions' => [
                    ['name' => 'menu_marketing_affiliates', 'type' => 'menu', 'label' => 'Akses Menu Marketing Afiliasi'],
                    ['name' => 'marketing_affiliates_manage', 'type' => 'action', 'label' => 'Kelola Mitra & Komisi Sales'],
                    ['name' => 'marketing_affiliates_withdraw_approve', 'type' => 'action', 'label' => 'Approve Pencairan Komisi'],
                ],
            ],
            [
                'group' => 'Paket Internet',
                'icon' => 'package',
                'permissions' => [
                    ['name' => 'menu_internet_packages', 'type' => 'menu', 'label' => 'Akses Menu Paket Internet'],
                    ['name' => 'internet_packages_create', 'type' => 'action', 'label' => 'Tambah Paket'],
                    ['name' => 'internet_packages_edit', 'type' => 'action', 'label' => 'Edit Paket'],
                    ['name' => 'internet_packages_delete', 'type' => 'action', 'label' => 'Hapus Paket'],
                ],
            ],
            [
                'group' => 'Master Area & Wilayah',
                'icon' => 'map',
                'permissions' => [
                    ['name' => 'menu_settings_areas', 'type' => 'menu', 'label' => 'Akses Master Area'],
                    ['name' => 'settings_areas_create', 'type' => 'action', 'label' => 'Tambah Area'],
                    ['name' => 'settings_areas_edit', 'type' => 'action', 'label' => 'Edit Area'],
                    ['name' => 'settings_areas_delete', 'type' => 'action', 'label' => 'Hapus Area'],
                ],
            ],
            [
                'group' => 'Master Konfigurasi & Kategori',
                'icon' => 'cog',
                'permissions' => [
                    ['name' => 'menu_settings_branding', 'type' => 'menu', 'label' => 'Akses Branding & Informasi Perusahaan'],
                    ['name' => 'settings_branding_edit', 'type' => 'action', 'label' => 'Ubah Branding Perusahaan'],
                    ['name' => 'menu_settings_billing', 'type' => 'menu', 'label' => 'Akses Pengaturan Billing & Pajak'],
                    ['name' => 'menu_settings_payroll', 'type' => 'menu', 'label' => 'Akses Pengaturan Payroll & Master Fee'],
                    ['name' => 'menu_settings_attendance', 'type' => 'menu', 'label' => 'Akses Pengaturan Radius & Lokasi Absensi'],
                    ['name' => 'menu_settings_api', 'type' => 'menu', 'label' => 'Akses API Setting & Token'],
                    ['name' => 'settings_api_generate_token', 'type' => 'feature', 'label' => 'Generate Token API'],
                    ['name' => 'settings_api_sync', 'type' => 'feature', 'label' => 'Sinkronisasi Data API'],
                ],
            ],
            [
                'group' => 'Manajemen Pengguna (User)',
                'icon' => 'users',
                'permissions' => [
                    ['name' => 'menu_users', 'type' => 'menu', 'label' => 'Akses Menu Pengguna (User)'],
                    ['name' => 'users_create', 'type' => 'action', 'label' => 'Tambah User'],
                    ['name' => 'users_edit', 'type' => 'action', 'label' => 'Edit User'],
                    ['name' => 'users_delete', 'type' => 'action', 'label' => 'Hapus User'],
                    ['name' => 'users_assign_role', 'type' => 'feature', 'label' => 'Assign Role Pengguna'],
                ],
            ],
            [
                'group' => 'Manajemen Role & Hak Akses',
                'icon' => 'lock-closed',
                'permissions' => [
                    ['name' => 'menu_settings_roles', 'type' => 'menu', 'label' => 'Akses Manajemen Role & Hak Akses'],
                    ['name' => 'settings_roles_create', 'type' => 'action', 'label' => 'Tambah Role Baru'],
                    ['name' => 'settings_roles_edit', 'type' => 'action', 'label' => 'Edit Nama Role & Hak Akses'],
                    ['name' => 'settings_roles_delete', 'type' => 'action', 'label' => 'Hapus Role'],
                ],
            ],
        ];
    }

    /**
     * Ensure all permissions from registry exist in DB.
     */
    private function syncPermissionsToDb(): void
    {
        $registry = $this->getPermissionRegistry();
        foreach ($registry as $group) {
            foreach ($group['permissions'] as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm['name'],
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    public function index(Request $request)
    {
        // Auto-sync permissions to DB on page load
        $this->syncPermissionsToDb();

        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => $role->users()->count(),
            ];
        });

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $this->getPermissionRegistry(),
            'positions' => \App\Models\Position::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'array',
        ]);

        if ($role->name === 'admin' && $request->name !== 'admin') {
            return redirect()->back()->with('error', 'Nama role Admin tidak dapat diubah.');
        }

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil diperbarui.");
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->back()->with('error', 'Role Admin tidak dapat dihapus.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', "Role '{$role->name}' masih digunakan oleh {$role->users()->count()} user. Pindahkan user terlebih dahulu.");
        }

        $role->delete();

        return redirect()->back()->with('success', "Role berhasil dihapus.");
    }
}
