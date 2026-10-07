<template>
    <div class="flex min-h-screen bg-gray-50 font-sans text-gray-900">
        <div v-show="mobileMenuOpen" class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden transition-opacity" @click="mobileMenuOpen = false"></div>
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col border-r border-gray-200 bg-white shadow-sm transition-transform duration-300',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                sidebarOpen ? 'w-64' : 'w-20 lg:w-20 w-64'
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between px-5 py-5 border-b border-gray-200">
                <Link href="/" class="block">
                    <AppLogo :sidebar-collapsed="!sidebarOpen" context="desktop" />
                </Link>
                <!-- Mobile Close Button -->
                <button @click="mobileMenuOpen = false" class="lg:hidden text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav ref="sidebarNav" scroll-region class="flex-1 overflow-y-auto px-3 py-4 space-y-1 transition-opacity duration-100" :class="{ 'opacity-0': !isRestored, 'opacity-100': isRestored }" @scroll="saveScrollPosition">
                <template v-for="(item, index) in filteredMenuItems" :key="index">
                    <!-- Group Label -->
                    <div v-if="item.type === 'group' && sidebarOpen" class="pt-5 pb-2 px-4">
                        <span class="text-[10px] font-bold text-gray-500 tracking-widest uppercase">{{ item.label }}</span>
                    </div>

                    <!-- Link -->
                    <Link v-if="item.type === 'link'" :href="item.href" @click="mobileMenuOpen = false" :class="['sidebar-link', { active: item.active($page.url), 'pl-11': item.indent }]">
                        <svg v-if="!item.indent" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[item.icon]"/>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" :d="iconPaths['submenu-dot']"/>
                        </svg>
                        <span v-if="sidebarOpen" :class="['truncate', item.indent ? 'text-[13px] font-medium' : '']">{{ item.label }}</span>
                    </Link>

                    <!-- Dropdown -->
                    <div v-else-if="item.type === 'dropdown'">
                        <button 
                            @click="toggleDropdown(index)" 
                            :class="['sidebar-link w-full text-left', { active: item.active($page.url) }]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[item.icon]"/>
                            </svg>
                            <span v-if="sidebarOpen" class="truncate flex-1">{{ item.label }}</span>
                            <svg v-if="sidebarOpen" :class="['w-4 h-4 shrink-0 transition-transform duration-200', openDropdowns.includes(index) ? 'rotate-90' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Items -->
                        <div v-show="sidebarOpen && openDropdowns.includes(index)" class="mt-1 space-y-1">
                            <template v-for="(child, childIndex) in item.children" :key="childIndex">
                                <Link 
                                    v-if="!child.permission || hasPermission(child.permission)"
                                    :href="child.href" 
                                    @click="mobileMenuOpen = false" 
                                    :class="['sidebar-link pl-11', { active: child.active($page.url) }]"
                                >
                                    <svg class="w-3.5 h-3.5 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" :d="iconPaths['submenu-dot']"/>
                                    </svg>
                                    <span class="truncate text-[13px] font-medium">{{ child.label }}</span>
                                </Link>
                            </template>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Sidebar Toggle (Desktop Only) -->
            <div class="hidden lg:block border-t border-gray-200 p-3">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="w-full flex items-center justify-center p-2.5 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-all"
                >
                    <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': !sidebarOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main
            :class="['flex-1 transition-all duration-300 min-w-0 flex flex-col', sidebarOpen ? 'lg:ml-64' : 'lg:ml-20']"
        >
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 px-4 sm:px-6 py-4 shadow-sm flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="mobileMenuOpen = true" class="lg:hidden p-2 -ml-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 truncate">{{ title }}</h2>
                        <p v-if="subtitle" class="text-xs sm:text-sm font-medium text-gray-500 mt-0.5 truncate">{{ subtitle }}</p>
                    </div>
                </div>
                    <div class="flex items-center gap-5">
                        <!-- Notification Bell -->
                        <button class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-600 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <!-- User Menu -->
                        <div class="flex items-center gap-3 pl-5 border-l border-gray-200">
                            <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700">
                                {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                            </div>
                            <div v-if="sidebarOpen" class="hidden md:block">
                                <p class="text-sm font-bold text-gray-900 leading-tight">{{ $page.props.auth?.user?.name || 'Admin' }}</p>
                                <p class="text-xs font-medium text-gray-500 capitalize mt-0.5">{{ $page.props.auth?.user?.role || 'admin' }}</p>
                            </div>
                            <Link href="/logout" method="post" as="button" class="ml-2 p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Logout">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </Link>
                        </div>
                    </div>
            </header>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mx-6 mt-4">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 animate-fade-in-up">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm">{{ $page.props.flash.success }}</span>
                </div>
            </div>

            <!-- Page Content -->
            <div class="p-4 sm:p-6 lg:p-8 flex-1 w-full max-w-full overflow-hidden">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

defineProps({
    title: { type: String, default: 'Dashboard' },
    subtitle: { type: String, default: '' },
});

const sidebarOpen = ref(true);
const mobileMenuOpen = ref(false);
const sidebarNav = ref(null);
const isRestored = ref(false);

const saveScrollPosition = () => {
    if (sidebarNav.value) {
        localStorage.setItem('sidebarScrollPos', sidebarNav.value.scrollTop);
    }
};

onMounted(() => {
    // Restore open dropdowns first so the DOM height is correct BEFORE restoring scroll
    const savedDropdowns = localStorage.getItem('sidebarOpenDropdowns');
    if (savedDropdowns) {
        openDropdowns.value = JSON.parse(savedDropdowns);
    } else {
        const currentUrl = page.url;
        filteredMenuItems.value.forEach((item, index) => {
            if (item.type === 'dropdown' && item.active(currentUrl)) {
                if (!openDropdowns.value.includes(index)) {
                    openDropdowns.value.push(index);
                }
            }
        });
    }

    // Restore sidebar scroll position aggressively to ensure it sticks after DOM mutations
    const scrollPos = parseInt(localStorage.getItem('sidebarScrollPos'), 10);
    if (!isNaN(scrollPos)) {
        let attempts = 0;
        // Run every 10ms for 150ms to ensure it overrides any browser scroll resets
        const interval = setInterval(() => {
            if (sidebarNav.value) {
                sidebarNav.value.scrollTop = scrollPos;
            }
            attempts++;
            if (attempts > 15) {
                clearInterval(interval);
                isRestored.value = true;
            }
        }, 10);
    } else {
        isRestored.value = true;
    }
});

const menuItems = [
    { type: 'link', href: '/', icon: 'dashboard', label: 'Dashboard', active: (url) => (url || '').length > 0 && url === '/', permission: 'menu_dashboard' },
    
    { type: 'group', label: 'MANAJEMEN PELANGGAN' },
    { type: 'link', href: '/customers/booking', icon: 'document-add', label: 'Data Booking', active: (url) => (url || '').startsWith('/customers/booking'), permission: 'menu_customers_booking' },
    { 
        type: 'dropdown', 
        icon: 'clipboard-check', 
        label: 'Survey', 
        active: (url) => (url || '').startsWith('/customers/survey'), 
        permission: 'menu_customers_survey',
        children: [
            { href: '/customers/survey', label: 'Semua Survey', active: (url) => (url || '').startsWith('/customers/survey') && !(url || '').includes('tab='), permission: 'customers_survey_tab_semua' },
            { href: '/customers/survey?tab=jadwalkan', label: 'Jadwalkan', active: (url) => (url || '').includes('tab=jadwalkan'), permission: 'customers_survey_tab_jadwalkan' },
            { href: '/customers/survey?tab=laporan', label: 'Isi Laporan', active: (url) => (url || '').includes('tab=laporan'), permission: 'customers_survey_tab_laporan' },
            { href: '/customers/survey?tab=ready', label: 'Ready Install', active: (url) => (url || '').includes('tab=ready'), permission: 'customers_survey_tab_ready' },
            { href: '/customers/survey?tab=unfeasible', label: 'Unfeasible', active: (url) => (url || '').includes('tab=unfeasible'), permission: 'customers_survey_tab_unfeasible' },
        ]
    },
    { 
        type: 'dropdown', 
        icon: 'cog', 
        label: 'Instalasi', 
        active: (url) => (url || '').startsWith('/customers/installed'), 
        permission: 'menu_customers_installed',
        children: [
            { href: '/customers/installed', label: 'Semua Instalasi', active: (url) => (url || '').startsWith('/customers/installed') && !(url || '').includes('tab='), permission: 'customers_installed_tab_semua' },
            { href: '/customers/installed?tab=jadwal_pasang', label: 'Jadwal Pasang', active: (url) => (url || '').includes('tab=jadwal_pasang'), permission: 'customers_installed_tab_jadwal' },
            { href: '/customers/installed?tab=laporan_pasang', label: 'Laporan Pasang', active: (url) => (url || '').includes('tab=laporan_pasang'), permission: 'customers_installed_tab_laporan' },
            { href: '/customers/installed?tab=audit', label: 'Audit', active: (url) => (url || '').includes('tab=audit'), permission: 'customers_installed_tab_audit' },
            { href: '/customers/installed?tab=selesai_instalasi', label: 'Selesai Instalasi', active: (url) => (url || '').includes('tab=selesai_instalasi'), permission: 'customers_installed_tab_selesai' },
        ]
    },
    { type: 'link', href: '/customers/activation', icon: 'key', label: 'Aktivasi', active: (url) => (url || '').startsWith('/customers/activation'), permission: 'menu_customers_activation' },
    {
        type: 'dropdown',
        icon: 'users',
        label: 'Data Pelanggan',
        active: (url) => ['/customers', '/customers/active', '/customers/isolir', '/customers/online', '/customers/offline'].some(path => (url || '') === path || (url || '').startsWith(path + '?')),
        permission: 'menu_customers_all',
        children: [
            { href: '/customers', label: 'Semua Pelanggan', active: (url) => (url || '').length > 0 && url === '/customers', permission: 'menu_customers_all' },
            { href: '/customers/active', label: 'Pelanggan Aktif', active: (url) => (url || '').startsWith('/customers/active'), permission: 'menu_customers_active' },
            { href: '/customers/isolir', label: 'Pelanggan Isolir', active: (url) => (url || '').startsWith('/customers/isolir'), permission: 'menu_customers_active' },
            { href: '/customers/online', label: 'Online', active: (url) => (url || '').startsWith('/customers/online'), permission: 'menu_customers_online' },
            { href: '/customers/offline', label: 'Offline', active: (url) => (url || '').startsWith('/customers/offline'), permission: 'menu_customers_online' },
        ]
    },
    { type: 'link', href: '/tickets', icon: 'alert-circle', label: 'Ticketing / Gangguan', active: (url) => (url || '').startsWith('/tickets'), permission: 'menu_tickets' },
    
    { type: 'group', label: 'BILLING & PRODUK' },
    { type: 'link', href: '/invoices', icon: 'document-text', label: 'Invoice Pelanggan', active: (url) => (url || '').length > 0 && url === '/invoices', permission: 'menu_customers_all' },
    {
        type: 'dropdown',
        icon: 'cash',
        label: 'Penagihan Lapangan',
        active: (url) => ['/customer-billing', '/customer-settlements'].some(path => (url || '').startsWith(path)),
        permission: 'menu_customers_all',
        children: [
            { href: '/customer-billing', label: 'Terima Setoran', active: (url) => (url || '').startsWith('/customer-billing'), permission: 'menu_customers_all' },
            { href: '/customer-settlements', label: 'Validasi Setoran', active: (url) => (url || '').startsWith('/customer-settlements'), permission: 'menu_customers_all' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'chart-pie',
        label: 'Laporan Keuangan',
        active: (url) => ['/financial-reports', '/customer-reports', '/tax-reports'].some(path => (url || '').startsWith(path)),
        permission: 'menu_customers_all',
        children: [
            { href: '/financial-reports', label: 'Buku Kas Global', active: (url) => (url || '').startsWith('/financial-reports'), permission: 'menu_customers_all' },
            { href: '/customer-reports', label: 'Laporan Pelanggan', active: (url) => (url || '').startsWith('/customer-reports'), permission: 'menu_customers_all' },
            { href: '/tax-reports', label: 'Laporan Pajak', active: (url) => (url || '').startsWith('/tax-reports'), permission: 'menu_customers_all' },
        ]
    },
    { type: 'link', href: '/internet-packages', icon: 'package', label: 'Paket Internet', active: (url) => (url || '').startsWith('/internet-packages'), permission: 'menu_internet_packages' },

    { type: 'group', label: 'VOUCHER & RESELLER' },
    { 
        type: 'dropdown',
        icon: 'ticket',
        label: 'Data Voucher',
        active: (url) => ['/vouchers', '/vouchers/online', '/vouchers/offline', '/vouchers/expired'].some(path => (url || '') === path || (url || '').startsWith(path + '?')),
        permission: 'menu_vouchers',
        children: [
            { href: '/vouchers', label: 'Semua Voucher', active: (url) => (url || '').length > 0 && url === '/vouchers', permission: 'menu_vouchers' },
            { href: '/vouchers/online', label: 'Voucher Online', active: (url) => (url || '').startsWith('/vouchers/online'), permission: 'menu_vouchers' },
            { href: '/vouchers/offline', label: 'Voucher Offline', active: (url) => (url || '').startsWith('/vouchers/offline'), permission: 'menu_vouchers' },
            { href: '/vouchers/expired', label: 'Voucher Expired', active: (url) => (url || '').startsWith('/vouchers/expired'), permission: 'menu_vouchers' },
        ]
    },
    { type: 'link', href: '/vouchers/profiles', icon: 'document-text', label: 'Profil Voucher', active: (url) => (url || '').startsWith('/vouchers/profiles'), permission: 'menu_vouchers_profiles' },
    { type: 'link', href: '/resellers', icon: 'users', label: 'Data Reseller', active: (url) => (url || '').startsWith('/resellers'), permission: 'menu_resellers' },
    {
        type: 'dropdown',
        icon: 'cash',
        label: 'Keuangan Reseller',
        active: (url) => ['/reseller-requests', '/reseller-billing', '/reseller-settlements', '/reseller-reports'].some(path => (url || '').startsWith(path)),
        permission: 'menu_resellers',
        children: [
            { href: '/reseller-requests', label: 'Riwayat Saldo', active: (url) => (url || '').startsWith('/reseller-requests'), permission: 'menu_resellers' },
            { href: '/reseller-billing', label: 'Penagihan Kasbon', active: (url) => (url || '').startsWith('/reseller-billing'), permission: 'menu_customers_all' },
            { href: '/reseller-settlements', label: 'Pelunasan Kasbon', active: (url) => (url || '').startsWith('/reseller-settlements'), permission: 'menu_customers_all' },
            { href: '/reseller-reports', label: 'Laporan Reseller', active: (url) => (url || '').startsWith('/reseller-reports'), permission: 'menu_customers_all' },
        ]
    },

    { type: 'group', label: 'INFRASTRUKTUR & JARINGAN' },
    { type: 'link', href: '/network-topology', icon: 'globe', label: 'Network Topology', active: (url) => (url || '').startsWith('/network-topology'), permission: 'menu_network_topology' },
    { type: 'link', href: '/network-data', icon: 'globe', label: 'Data Jaringan', active: (url) => (url || '').startsWith('/network-data'), permission: 'menu_network_data' },
    {
        type: 'dropdown',
        icon: 'server',
        label: 'Perangkat OSP',
        active: (url) => ['/olts', '/odcs', '/odps', '/onts', '/find-onu'].some(path => (url || '').startsWith(path)),
        permission: 'menu_network_olt',
        children: [
            { href: '/olts', label: 'Data OLT', active: (url) => (url || '').startsWith('/olts'), permission: 'menu_network_olt' },
            { href: '/odcs', label: 'Data ODC', active: (url) => (url || '').startsWith('/odcs'), permission: 'menu_network_odc' },
            { href: '/odps', label: 'Data ODP', active: (url) => (url || '').startsWith('/odps'), permission: 'menu_network_odp' },
            { href: '/onts', label: 'Data ONT', active: (url) => (url || '').startsWith('/onts'), permission: 'menu_network_ont' },
            { href: '/find-onu', label: 'Find ONU', active: (url) => (url || '').startsWith('/find-onu'), permission: 'menu_network_find_onu' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'server',
        label: 'Mikrotik / RADIUS',
        active: (url) => (url || '').startsWith('/routers') || (url || '').startsWith('/radius'),
        permission: 'menu_routers',
        children: [
            { href: '/routers', label: 'Data Router', active: (url) => (url || '').startsWith('/routers'), permission: 'menu_routers' },
            { href: '/radius/nas', label: 'NAS / Router', active: (url) => (url || '').startsWith('/radius/nas'), permission: 'menu_radius_nas' },
            { href: '/radius/online-users', label: 'Pengguna Online', active: (url) => (url || '').startsWith('/radius/online-users'), permission: 'menu_radius_online_users' },
            { href: '/radius/auth-logs', label: 'Log Autentikasi', active: (url) => (url || '').startsWith('/radius/auth-logs'), permission: 'menu_radius_auth_logs' },
        ]
    },

    { type: 'group', label: 'INVENTARIS & HR' },
    {
        type: 'dropdown',
        icon: 'archive',
        label: 'Inventaris & Logistik',
        active: (url) => (url || '').startsWith('/materials') || (url || '').startsWith('/material-transactions'),
        permission: 'menu_materials',
        children: [
            { href: '/materials', label: 'Material / Barang', active: (url) => (url || '').startsWith('/materials'), permission: 'menu_materials' },
            { href: '/material-transactions', label: 'Order / Pengambilan', active: (url) => (url || '').startsWith('/material-transactions'), permission: 'menu_material_transactions' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'users',
        label: 'HR & Personalia',
        active: (url) => (url || '').startsWith('/employees') || (url || '').startsWith('/positions') || (url || '').startsWith('/payroll') || (url || '').startsWith('/insentif-potongan'),
        permission: 'menu_hr_employees',
        children: [
            { href: '/employees', label: 'Data Karyawan', active: (url) => (url || '').startsWith('/employees'), permission: 'menu_hr_employees' },
            { href: '/positions', label: 'Posisi / Jabatan', active: (url) => (url || '').startsWith('/positions'), permission: 'menu_hr_positions' },
            { href: '/payroll', label: 'Gaji, Insentif & Potongan', active: (url) => (url || '').length > 0 && url === '/payroll', permission: 'menu_hr_employees' },
            { href: '/insentif-potongan', label: 'Data Insentif & Potongan', active: (url) => (url || '').startsWith('/insentif-potongan'), permission: 'menu_hr_employees' },
        ]
    },
    
    { type: 'group', label: 'PENGATURAN' },
    { type: 'link', href: '/settings/areas', icon: 'map', label: 'Master Area', active: (url) => (url || '').startsWith('/settings/areas'), permission: 'menu_settings_areas' },
    { type: 'link', href: '/users', icon: 'users', label: 'Manajemen User', active: (url) => (url || '').startsWith('/users'), permission: 'menu_users' },
    { type: 'link', href: '/settings/roles', icon: 'lock-closed', label: 'Role & Akses', active: (url) => (url || '').startsWith('/settings/roles'), permission: 'menu_settings_roles' },
    {
        type: 'dropdown',
        icon: 'cog',
        label: 'Konfigurasi Sistem',
        active: (url) => ['/settings/resellers', '/settings/api', '/settings/branding', '/settings/billing'].some(path => (url || '').startsWith(path)),
        permission: 'menu_settings_roles',
        children: [
            { href: '/settings/resellers', label: 'Pengaturan Reseller', active: (url) => (url || '').startsWith('/settings/resellers'), permission: 'menu_settings_roles' },
            { href: '/settings/api', label: 'API & Integrasi', active: (url) => (url || '').startsWith('/settings/api'), permission: 'menu_settings_api' },
            { href: '/settings/branding', label: 'Branding', active: (url) => (url || '').startsWith('/settings/branding'), permission: 'menu_settings_branding' },
            { href: '/settings/billing', label: 'Billing / Invoice', active: (url) => (url || '').startsWith('/settings/billing'), permission: 'menu_settings_branding' },
        ]
    }
];

const iconPaths = {
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'x-circle': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    globe: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'document-add': 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'clipboard-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    'cog': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    'key': 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
    'badge-check': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    server: 'M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
    box: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'git-branch': 'M6 3v12M18 9a3 3 0 01-3 3H9m-3 0a3 3 0 003 3h0a3 3 0 003-3m0 0V3',
    wifi: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0',
    'file-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'document-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    'ticket': 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
    'alert-circle': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    'package': 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    zap: 'M13 10V3L4 14h7v7l9-11h-7z',
    signal: 'M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z',
    map: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
    'color-swatch': 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    archive: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    'shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    briefcase: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    code: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
    'lock-closed': 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    'submenu-dot': 'M9 5l7 7-7 7',
    calculator: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
};

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const hasPermission = (permission) => {
    try {
        const user = page.props.auth?.user;
        if (!user) return false; // Fallback jika user blm ter-load
        
        // Fallback legacy role column
        if (user.role === 'admin' || user.role === 'Super Admin') return true;

        // Cek super admin (spatie)
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        if (roles.includes('admin') || roles.includes('Super Admin')) return true;

        // Cek permission spesifik
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        if (perms.includes(permission)) return true;
        
        return false;
    } catch (e) {
        console.error("Error in hasPermission:", e);
        return false; // JANGAN tampilkan jika error agar strict
    }
};

const filteredMenuItems = computed(() => {
    try {
        const filtered = [];
        let currentGroup = null;

        menuItems.forEach(item => {
            if (item.type === 'group') {
                currentGroup = item;
            } else {
                if (!item.permission || hasPermission(item.permission)) {
                    if (currentGroup) {
                        filtered.push(currentGroup);
                        currentGroup = null;
                    }
                    filtered.push(item);
                }
            }
        });

        return filtered;
    } catch (e) {
        console.error("Error in filteredMenuItems:", e);
        return menuItems; // Tampilkan semua jika error
    }
});

const openDropdowns = ref([]);
const toggleDropdown = (index) => {
    if (openDropdowns.value.includes(index)) {
        openDropdowns.value = openDropdowns.value.filter(i => i !== index);
    } else {
        openDropdowns.value.push(index);
    }
    localStorage.setItem('sidebarOpenDropdowns', JSON.stringify(openDropdowns.value));
};

onMounted(() => {
    // Buka dropdown yang sedang aktif secara otomatis
    filteredMenuItems.value.forEach((item, index) => {
        if (item.type === 'dropdown' && item.active(page.url)) {
            openDropdowns.value.push(index);
        }
    });
});
</script>
