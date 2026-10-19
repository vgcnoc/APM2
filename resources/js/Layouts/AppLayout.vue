<template>
    <div class="flex min-h-screen bg-gray-50 font-sans text-gray-900">
        <div v-show="mobileMenuOpen" class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden transition-opacity" @click="mobileMenuOpen = false"></div>
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col border-r border-gray-200 bg-white shadow-sm transition-transform duration-300 print:hidden',
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
                    <div v-if="item.type === 'group' && sidebarOpen" class="pt-5 pb-2 px-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-gray-400 tracking-wider uppercase">{{ item.label }}</span>
                            <div class="h-px bg-gray-200/60 flex-1"></div>
                        </div>
                    </div>
                    <div v-else-if="item.type === 'group' && !sidebarOpen" class="my-2 border-t border-gray-200/60 mx-3"></div>

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
            :class="['flex-1 transition-all duration-300 min-w-0 flex flex-col print:m-0 print:p-0 print:ml-0', sidebarOpen ? 'lg:ml-64' : 'lg:ml-20']"
        >
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 px-4 sm:px-6 py-4 shadow-sm flex items-center justify-between gap-4 print:hidden">
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
                        <!-- On Duty Toggle -->
                        <div v-if="$page.props.auth?.user?.role !== 'customer'" class="hidden sm:flex items-center gap-2">
                            <span class="text-xs font-bold" :class="$page.props.auth?.user?.is_on_duty ? 'text-green-600' : 'text-gray-400'">
                                {{ $page.props.auth?.user?.is_on_duty ? 'ON DUTY' : 'OFF DUTY' }}
                            </span>
                            <Link href="/profile/toggle-duty" method="post" as="button" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none" :class="$page.props.auth?.user?.is_on_duty ? 'bg-green-500' : 'bg-gray-200'" title="Toggle On Duty">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" :class="$page.props.auth?.user?.is_on_duty ? 'translate-x-6' : 'translate-x-1'"></span>
                            </Link>
                        </div>
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
            <div v-if="$page.props.flash?.success" class="mx-6 mt-4 print:hidden">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 animate-fade-in-up">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm">{{ $page.props.flash.success }}</span>
                </div>
            </div>

            <!-- Page Content -->
            <div class="p-4 sm:p-6 lg:p-8 flex-1 w-full max-w-full overflow-hidden print:p-0 print:overflow-visible">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';
import { usePermission } from '@/Composables/usePermission';

defineProps({
    title: { type: String, default: 'Dashboard' },
    subtitle: { type: String, default: '' },
});

const page = usePage();
const { can, hasRole } = usePermission();
const sidebarOpen = ref(true);
const mobileMenuOpen = ref(false);
const sidebarNav = ref(null);
const isRestored = ref(false);
const openDropdowns = ref([]);

const saveScrollPosition = () => {
    if (sidebarNav.value) {
        localStorage.setItem('sidebarScrollPos', sidebarNav.value.scrollTop);
    }
};

const hasPermission = (permission) => {
    if (!permission) return true;
    return can(permission);
};

const menuItems = [
    // 1. DASHBOARD
    { type: 'link', href: '/', icon: 'dashboard', label: 'Dashboard', active: (url) => (url || '').length > 0 && url === '/', permission: 'menu_dashboard' },
    
    // 2. PELANGGAN & LAYANAN
    { type: 'group', label: 'PELANGGAN & LAYANAN' },
    {
        type: 'dropdown',
        icon: 'users',
        label: 'Data Pelanggan',
        active: (url) => ['/customers', '/customers/gratis', '/customers/free-home', '/customers/active', '/customers/isolir', '/customers/online', '/customers/offline', '/customers/terminated'].some(path => (url || '') === path || (url || '').startsWith(path + '?')),
        permission: ['menu_customers_all', 'menu_customers_active', 'menu_customers_online', 'menu_customers_gratis', 'menu_customers_free_home', 'menu_customers_isolir', 'menu_customers_terminated'],
        children: [
            { href: '/customers', label: 'Semua Pelanggan', active: (url) => (url || '').length > 0 && url === '/customers', permission: 'menu_customers_all' },
            { href: '/customers/gratis', label: 'Pelanggan Gratis', active: (url) => (url || '').startsWith('/customers/gratis'), permission: 'menu_customers_gratis' },
            { href: '/customers/free-home', label: 'Pelanggan Free Home', active: (url) => (url || '').startsWith('/customers/free-home'), permission: 'menu_customers_free_home' },
            { href: '/customers/active', label: 'Pelanggan Aktif', active: (url) => (url || '').startsWith('/customers/active'), permission: 'menu_customers_active' },
            { href: '/customers/isolir', label: 'Pelanggan Isolir', active: (url) => (url || '').startsWith('/customers/isolir'), permission: 'menu_customers_isolir' },
            { href: '/customers/online', label: 'Status Online', active: (url) => (url || '').startsWith('/customers/online'), permission: 'menu_customers_online' },
            { href: '/customers/offline', label: 'Status Offline', active: (url) => (url || '').startsWith('/customers/offline'), permission: 'menu_customers_online' },
            { href: '/customers/terminated', label: 'Stop Permanen', active: (url) => (url || '').startsWith('/customers/terminated'), permission: 'menu_customers_all' },
        ]
    },
    { 
        type: 'link', 
        href: '/customers/gratis', 
        icon: 'gift', 
        label: 'Pelanggan Gratis', 
        active: (url) => (url || '').startsWith('/customers/gratis'), 
        permission: 'menu_customers_gratis' 
    },
    { 
        type: 'link', 
        href: '/customers/free-home', 
        icon: 'home', 
        label: 'Pelanggan Free Home', 
        active: (url) => (url || '').startsWith('/customers/free-home'), 
        permission: 'menu_customers_free_home' 
    },
    {
        type: 'link',
        href: '/monitoring-pelanggan',
        icon: 'calendar',
        label: 'Pantauan Pelanggan',
        active: (url) => (url || '').startsWith('/monitoring-pelanggan'),
        permission: 'menu_monitoring_pelanggan',
    },
    {
        type: 'dropdown',
        icon: 'clipboard-check',
        label: 'Pemasangan Baru (PSB)',
        active: (url) => ['/customers/booking', '/customers/survey', '/customers/installed', '/customers/activation', '/customers/canceled'].some(path => (url || '').startsWith(path)),
        permission: ['menu_customers_booking', 'menu_customers_survey', 'menu_customers_installed', 'menu_customers_activation'],
        children: [
            { href: '/customers/booking', label: '1. Booking Baru', active: (url) => (url || '').startsWith('/customers/booking'), permission: 'menu_customers_booking' },
            { href: '/customers/survey', label: '2. Survey Lokasi', active: (url) => (url || '').startsWith('/customers/survey'), permission: 'menu_customers_survey' },
            { href: '/customers/installed', label: '3. Instalasi Jaringan', active: (url) => (url || '').startsWith('/customers/installed'), permission: 'menu_customers_installed' },
            { href: '/customers/activation', label: '4. Aktivasi Layanan', active: (url) => (url || '').startsWith('/customers/activation'), permission: 'menu_customers_activation' },
            { href: '/customers/canceled', label: '5. Dibatalkan', active: (url) => (url || '').startsWith('/customers/canceled'), permission: 'menu_customers_booking' },
        ]
    },
    { type: 'link', href: '/internet-packages', icon: 'package', label: 'Paket Internet', active: (url) => (url || '').startsWith('/internet-packages'), permission: 'menu_internet_packages' },

    // 3. BILLING & KEUANGAN
    { type: 'group', label: 'BILLING & KEUANGAN' },
    { type: 'link', href: '/invoices', icon: 'document-text', label: 'Invoice Pelanggan', active: (url) => (url || '').length > 0 && url === '/invoices', permission: 'menu_invoices' },
    {
        type: 'dropdown',
        icon: 'cash',
        label: 'Penagihan Lapangan',
        active: (url) => ['/customer-billing', '/customer-settlements'].some(path => (url || '').startsWith(path)),
        permission: 'menu_customer_billing',
        children: [
            { href: '/customer-billing', label: 'Terima Setoran', active: (url) => (url || '').startsWith('/customer-billing'), permission: 'menu_customer_billing' },
            { href: '/customer-settlements', label: 'Validasi Setoran', active: (url) => (url || '').startsWith('/customer-settlements'), permission: 'menu_customer_billing' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'chart-pie',
        label: 'Laporan Keuangan',
        active: (url) => ['/financial-reports', '/customer-reports', '/tax-reports'].some(path => (url || '').startsWith(path)),
        permission: 'menu_financial_reports',
        children: [
            { href: '/financial-reports', label: 'Buku Kas Global', active: (url) => (url || '').startsWith('/financial-reports'), permission: 'menu_financial_reports' },
            { href: '/customer-reports', label: 'Laporan Pelanggan', active: (url) => (url || '').startsWith('/customer-reports'), permission: 'menu_financial_reports' },
            { href: '/tax-reports', label: 'Laporan Pajak', active: (url) => (url || '').startsWith('/tax-reports'), permission: 'menu_financial_reports' },
        ]
    },

    // 4. OPERASIONAL & GANGGUAN (TIKET)
    { type: 'group', label: 'OPERASIONAL & GANGGUAN' },
    {
        type: 'link',
        href: '/monitoring-teknisi',
        icon: 'presentation-chart-line',
        label: 'Monitor Kinerja Teknisi',
        active: (url) => (url || '').startsWith('/monitoring-teknisi'),
        permission: 'menu_monitoring_teknisi',
    },
    {
        type: 'dropdown',
        icon: 'support',
        label: 'Komplain Pelanggan (PGK)',
        active: (url) => (url || '').startsWith('/pgk'),
        permission: ['menu_pgk', 'menu_pgk_jadwal', 'menu_pgk_laporan'],
        children: [
            { href: '/pgk', label: '1. Komplain Masuk', active: (url) => (url || '').length > 0 && url === '/pgk', permission: 'menu_pgk' },
            { href: '/pgk/jadwal', label: '2. Jadwal Teknisi', active: (url) => (url || '').startsWith('/pgk/jadwal'), permission: 'menu_pgk_jadwal' },
            { href: '/pgk/laporan', label: '3. Laporan Penanganan', active: (url) => (url || '').startsWith('/pgk/laporan'), permission: 'menu_pgk_laporan' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'git-branch',
        label: 'Gangguan Jalur (PGJ)',
        active: (url) => (url || '').startsWith('/pgj'),
        permission: ['menu_pgj', 'menu_pgj_jadwal', 'menu_pgj_laporan'],
        children: [
            { href: '/pgj', label: '1. Temuan Jalur', active: (url) => (url || '').length > 0 && url === '/pgj', permission: 'menu_pgj' },
            { href: '/pgj/jadwal', label: '2. Jadwal & Material', active: (url) => (url || '').startsWith('/pgj/jadwal'), permission: 'menu_pgj_jadwal' },
            { href: '/pgj/laporan', label: '3. Laporan Restorasi', active: (url) => (url || '').startsWith('/pgj/laporan'), permission: 'menu_pgj_laporan' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'zap',
        label: 'Penanganan Sigap (PGS)',
        active: (url) => (url || '').startsWith('/pgs'),
        permission: ['menu_pgs', 'menu_pgs_jadwal', 'menu_pgs_laporan'],
        children: [
            { href: '/pgs', label: '1. Temuan & Tindakan', active: (url) => (url || '').length > 0 && url === '/pgs', permission: 'menu_pgs' },
            { href: '/pgs/jadwal', label: '2. Jadwal & Material', active: (url) => (url || '').startsWith('/pgs/jadwal'), permission: 'menu_pgs_jadwal' },
            { href: '/pgs/laporan', label: '3. Laporan Penanganan', active: (url) => (url || '').startsWith('/pgs/laporan'), permission: 'menu_pgs_laporan' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'scissors',
        label: 'Cabut Perangkat (CBP)',
        active: (url) => (url || '').startsWith('/cbp'),
        permission: ['menu_cbp', 'menu_cbp_jadwal', 'menu_cbp_laporan'],
        children: [
            { href: '/cbp', label: '1. Request Cabut', active: (url) => (url || '').length > 0 && url === '/cbp', permission: 'menu_cbp' },
            { href: '/cbp/jadwal', label: '2. Jadwal CBP', active: (url) => (url || '').startsWith('/cbp/jadwal'), permission: 'menu_cbp_jadwal' },
            { href: '/cbp/laporan', label: '3. Laporan CBP', active: (url) => (url || '').startsWith('/cbp/laporan'), permission: 'menu_cbp_laporan' },
        ]
    },

    // 5. RESELLER & VOUCHER
    { type: 'group', label: 'RESELLER & VOUCHER' },
    { 
        type: 'dropdown',
        icon: 'ticket',
        label: 'Sistem Voucher',
        active: (url) => (url || '').startsWith('/vouchers'),
        permission: 'menu_vouchers',
        children: [
            { href: '/vouchers', label: 'Data Voucher', active: (url) => (url || '') === '/vouchers' || (url || '').startsWith('/vouchers?'), permission: 'menu_vouchers' },
            { href: '/vouchers/online', label: 'Monitoring Sesi Voucher', active: (url) => (url || '').startsWith('/vouchers/online') || (url || '').startsWith('/vouchers/offline'), permission: 'menu_vouchers_monitoring' },
            { href: '/vouchers/expired', label: 'Voucher Expired', active: (url) => (url || '').startsWith('/vouchers/expired'), permission: 'menu_vouchers' },
            { href: '/vouchers/profiles', label: 'Profil Voucher', active: (url) => (url || '').startsWith('/vouchers/profiles'), permission: 'menu_vouchers_profiles' },
            { href: '/vouchers/free', label: 'Voucher Gratis', active: (url) => (url || '').startsWith('/vouchers/free'), permission: 'menu_vouchers_free' },
        ]
    },
    { type: 'link', href: '/resellers', icon: 'users', label: 'Data Reseller', active: (url) => (url || '').startsWith('/resellers'), permission: 'menu_resellers' },
    {
        type: 'dropdown',
        icon: 'credit-card',
        label: 'Keuangan Reseller',
        active: (url) => ['/reseller-requests', '/reseller-billing', '/reseller-settlements', '/reseller-reports'].some(path => (url || '').startsWith(path)),
        permission: 'menu_resellers',
        children: [
            { href: '/reseller-requests', label: 'Riwayat Saldo', active: (url) => (url || '').startsWith('/reseller-requests'), permission: 'menu_resellers' },
            { href: '/reseller-billing', label: 'Penagihan Kasbon', active: (url) => (url || '').startsWith('/reseller-billing'), permission: 'menu_resellers' },
            { href: '/reseller-settlements', label: 'Pelunasan Kasbon', active: (url) => (url || '').startsWith('/reseller-settlements'), permission: 'menu_resellers' },
            { href: '/reseller-reports', label: 'Laporan Reseller', active: (url) => (url || '').startsWith('/reseller-reports'), permission: 'menu_resellers' },
        ]
    },

    // 6. MARKETING & AFILIASI
    { type: 'group', label: 'MARKETING & KOMUNIKASI' },
    {
        type: 'link',
        href: '/whatsapp-blast',
        icon: 'zap',
        label: '⚡ WhatsApp Blast & QR',
        active: (url) => (url || '').startsWith('/whatsapp-blast'),
        permission: 'menu_whatsapp_blast',
    },
    {
        type: 'dropdown',
        icon: 'gift',
        label: 'Marketing Afiliasi',
        active: (url) => (url || '').startsWith('/marketing/affiliates'),
        permission: 'menu_marketing_affiliates',
        children: [
            { href: '/marketing/affiliates?tab=settings', label: 'Pengaturan Komisi', active: (url) => (url || '').includes('/marketing/affiliates') && ((url || '').includes('tab=settings') || !(url || '').includes('tab=')), permission: 'menu_marketing_affiliates' },
            { href: '/marketing/affiliates?tab=affiliates', label: 'Mitra & Agen Sales', active: (url) => (url || '').includes('/marketing/affiliates') && (url || '').includes('tab=affiliates'), permission: 'menu_marketing_affiliates' },
            { href: '/marketing/affiliates?tab=commissions', label: 'Riwayat Komisi', active: (url) => (url || '').includes('/marketing/affiliates') && (url || '').includes('tab=commissions'), permission: 'menu_marketing_affiliates' },
            { href: '/marketing/affiliates?tab=withdrawals', label: 'Pencairan Komisi', active: (url) => (url || '').includes('/marketing/affiliates') && (url || '').includes('tab=withdrawals'), permission: 'menu_marketing_affiliates' },
        ]
    },
    {
        type: 'link',
        href: '/affiliate/dashboard',
        icon: 'sparkles',
        label: 'Portal Mitra Saya',
        active: (url) => (url || '').startsWith('/affiliate'),
    },

    // 7. INFRASTRUKTUR JARINGAN
    { type: 'group', label: 'INFRASTRUKTUR JARINGAN' },
    {
        type: 'dropdown',
        icon: 'globe',
        label: 'Peta & Topologi',
        active: (url) => ['/network-map', '/network-topology', '/network-radar', '/network-data'].some(path => (url || '').startsWith(path)),
        permission: ['menu_network_map', 'menu_network_topology', 'menu_network_data', 'menu_ftth'],
        children: [
            { href: '/network-map', label: '🗺️ GIS Map Jaringan & Rute', active: (url) => (url || '').startsWith('/network-map'), permission: 'menu_network_map' },
            { href: '/network-topology', label: 'Network Topology', active: (url) => (url || '').startsWith('/network-topology'), permission: 'menu_network_topology' },
            { href: '/network-radar', label: 'Radar Tikor', active: (url) => (url || '').startsWith('/network-radar'), permission: 'menu_network_topology' },
            { href: '/network-data', label: 'Data Jaringan', active: (url) => (url || '').startsWith('/network-data') && !(url || '').startsWith('/network-data/terminated'), permission: 'menu_network_data' },
            { href: '/network-data/terminated', label: 'Cabut Perangkat', active: (url) => (url || '').startsWith('/network-data/terminated'), permission: 'menu_network_data' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'server',
        label: 'Perangkat OSP / FTTH',
        active: (url) => ['/olts', '/odcs', '/odps', '/onts', '/find-onu', '/infrastructure/work-orders', '/network/capacity-monitoring'].some(path => (url || '').startsWith(path)),
        permission: ['menu_network_capacity', 'menu_infrastructure_work_orders', 'menu_network_olt', 'menu_network_odc', 'menu_network_odp', 'menu_network_ont'],
        children: [
            { href: '/network/capacity-monitoring', label: '📊 Monitoring Kapasitas ODC/ODP', active: (url) => (url || '').startsWith('/network/capacity-monitoring'), permission: ['menu_network_capacity', 'menu_network_odc', 'menu_network_odp'] },
            { href: '/infrastructure/work-orders', label: '⚡ Rollout ODC & ODP', active: (url) => (url || '').startsWith('/infrastructure/work-orders'), permission: ['menu_infrastructure_work_orders', 'menu_network_odc', 'menu_network_odp'] },
            { href: '/olts', label: 'Data OLT', active: (url) => (url || '').startsWith('/olts'), permission: 'menu_network_olt' },
            { href: '/odcs', label: 'Data ODC', active: (url) => (url || '').startsWith('/odcs'), permission: 'menu_network_odc' },
            { href: '/odps', label: 'Data ODP', active: (url) => (url || '').startsWith('/odps'), permission: 'menu_network_odp' },
            { href: '/onts', label: 'Data ONT', active: (url) => (url || '').startsWith('/onts'), permission: 'menu_network_ont' },
            { href: '/find-onu', label: 'Find ONU', active: (url) => (url || '').startsWith('/find-onu'), permission: 'menu_network_find_onu' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'wifi',
        label: 'MikroTik & RADIUS',
        active: (url) => (url || '').startsWith('/routers') || (url || '').startsWith('/radius'),
        permission: ['menu_routers', 'menu_radius_nas', 'menu_radius_online_users', 'menu_radius_auth_logs'],
        children: [
            { href: '/routers', label: 'Data Router', active: (url) => (url || '').startsWith('/routers'), permission: 'menu_routers' },
            { href: '/radius/nas', label: 'NAS / Router', active: (url) => (url || '').startsWith('/radius/nas'), permission: 'menu_radius_nas' },
            { href: '/radius/online-users', label: 'Pengguna Online', active: (url) => (url || '').startsWith('/radius/online-users'), permission: 'menu_radius_online_users' },
            { href: '/radius/auth-logs', label: 'Log Autentikasi', active: (url) => (url || '').startsWith('/radius/auth-logs'), permission: 'menu_radius_auth_logs' },
        ]
    },

    // 7. LOGISTIK & GUDANG
    { type: 'group', label: 'LOGISTIK & GUDANG' },
    {
        type: 'dropdown',
        icon: 'archive',
        label: 'Inventaris & Stok',
        active: (url) => (url || '').startsWith('/produk') || (url || '').startsWith('/order-toko') || (url || '').startsWith('/stok-in-out') || (url || '').startsWith('/warehouse-stocks'),
        permission: ['menu_materials', 'menu_inventory', 'menu_material_transactions'],
        children: [
            { href: '/produk', label: 'Data Produk & Material', active: (url) => (url || '').startsWith('/produk'), permission: 'menu_materials' },
            { href: '/order-toko', label: 'Order Toko (Restock)', active: (url) => (url || '').startsWith('/order-toko'), permission: 'menu_materials' },
            { href: '/stok-in-out', label: 'Stock Opname Per Area', active: (url) => (url || '').startsWith('/stok-in-out'), permission: 'menu_materials' },
            { href: '/warehouse-stocks', label: 'Inventory Gudang', active: (url) => (url || '').startsWith('/warehouse-stocks'), permission: 'menu_materials' },
        ]
    },

    // 8. KEPEGAWAIAN & HRD
    { type: 'group', label: 'KEPEGAWAIAN & HRD' },
    {
        type: 'dropdown',
        icon: 'briefcase',
        label: 'Karyawan & Shift',
        active: (url) => ['/employees', '/positions', '/shifts', '/work-schedules'].some(path => (url || '').startsWith(path)),
        permission: ['menu_hr_employees', 'menu_hr_positions', 'menu_shifts'],
        children: [
            { href: '/employees', label: 'Data Karyawan', active: (url) => (url || '').startsWith('/employees'), permission: 'menu_hr_employees' },
            { href: '/positions', label: 'Posisi / Jabatan', active: (url) => (url || '').startsWith('/positions'), permission: 'menu_hr_positions' },
            { href: '/shifts', label: 'Shift Kerja', active: (url) => (url || '').startsWith('/shifts'), permission: 'menu_shifts' },
            { href: '/work-schedules', label: 'Jadwal Kerja', active: (url) => (url || '').startsWith('/work-schedules'), permission: 'menu_shifts' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'badge-check',
        label: 'Sistem Presensi & Absensi',
        active: (url) => ['/attendances', '/absen-terbaik', '/absen-khusus', '/absen-tugas-luar', '/settings/attendance'].some(path => (url || '').startsWith(path)),
        permission: ['menu_attendances', 'attendance_tab_reguler', 'attendance_tab_best', 'attendance_tab_special', 'attendance_tab_field', 'menu_settings_attendance'],
        children: [
            { href: '/attendances', label: 'Absensi Reguler', active: (url) => (url || '').startsWith('/attendances'), permission: 'attendance_tab_reguler' },
            { href: '/absen-terbaik', label: 'Absen Terbaik', active: (url) => (url || '').startsWith('/absen-terbaik'), permission: 'attendance_tab_best' },
            { href: '/absen-khusus', label: 'Absen Tim Khusus', active: (url) => (url || '').startsWith('/absen-khusus'), permission: 'attendance_tab_special' },
            { href: '/absen-tugas-luar', label: 'Absen Tugas Luar', active: (url) => (url || '').startsWith('/absen-tugas-luar'), permission: 'attendance_tab_field' },
            { href: '/settings/attendance', label: 'Pengaturan Absensi', active: (url) => (url || '').startsWith('/settings/attendance'), permission: 'menu_settings_attendance' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'calculator',
        label: 'Penggajian & Insentif',
        active: (url) => ['/payroll', '/bonus-target-market', '/insentif-potongan'].some(path => (url || '').startsWith(path)),
        permission: ['menu_payroll', 'payroll_view_own', 'payroll_tab_slip', 'payroll_tab_incentives', 'menu_target_market'],
        children: [
            { href: '/payroll', label: 'Gaji & Payroll', active: (url) => (url || '').length > 0 && url === '/payroll', permission: ['menu_payroll', 'payroll_view_own', 'payroll_tab_slip'] },
            { href: '/bonus-target-market', label: 'Bonus Target Market', active: (url) => (url || '').startsWith('/bonus-target-market'), permission: 'menu_target_market' },
            { href: '/insentif-potongan', label: 'Histori Insentif', active: (url) => (url || '').startsWith('/insentif-potongan'), permission: ['menu_payroll', 'payroll_tab_incentives', 'payroll_view_own'] },
        ]
    },

    // 9. PENGATURAN SISTEM
    { type: 'group', label: 'PENGATURAN SISTEM' },
    {
        type: 'dropdown',
        icon: 'cog',
        label: 'Konfigurasi Sistem',
        active: (url) => ['/settings/branding', '/settings/billing', '/settings/payroll', '/settings/resellers', '/settings/api', '/settings/data-migration'].some(path => (url || '').startsWith(path)),
        permission: ['menu_settings_branding', 'menu_settings_billing', 'menu_settings_payroll', 'menu_settings_api', 'menu_settings_roles', 'menu_data_migration'],
        children: [
            { href: '/settings/branding', label: 'Branding & Tampilan', active: (url) => (url || '').startsWith('/settings/branding'), permission: 'menu_settings_branding' },
            { href: '/settings/billing', label: 'Billing / Invoice', active: (url) => (url || '').startsWith('/settings/billing'), permission: 'menu_settings_billing' },
            { href: '/settings/payroll', label: 'Sistem Gaji', active: (url) => (url || '').startsWith('/settings/payroll'), permission: 'menu_settings_payroll' },
            { href: '/settings/resellers', label: 'Pengaturan Reseller', active: (url) => (url || '').startsWith('/settings/resellers'), permission: 'menu_settings_roles' },
            { href: '/settings/api', label: 'API & Integrasi', active: (url) => (url || '').startsWith('/settings/api'), permission: 'menu_settings_api' },
            { href: '/settings/data-migration', label: '⚡ Data Migration Center', active: (url) => (url || '').startsWith('/settings/data-migration'), permission: 'menu_data_migration' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'box',
        label: 'Master Data',
        active: (url) => ['/settings/areas', '/settings/payroll-categories', '/settings/master-fees', '/settings/expense-categories'].some(path => (url || '').startsWith(path)),
        permission: ['menu_settings_areas', 'menu_settings_payroll', 'menu_settings_roles'],
        children: [
            { href: '/settings/areas', label: 'Master Area', active: (url) => (url || '').startsWith('/settings/areas'), permission: 'menu_settings_areas' },
            { href: '/settings/expense-categories', label: 'Kategori Pengeluaran', active: (url) => (url || '').startsWith('/settings/expense-categories'), permission: 'menu_settings_areas' },
            { href: '/settings/master-fees', label: 'Master Fee Teknisi/Sales', active: (url) => (url || '').startsWith('/settings/master-fees'), permission: 'menu_settings_payroll' },
            { href: '/settings/payroll-categories', label: 'Kategori Insentif/Potongan', active: (url) => (url || '').startsWith('/settings/payroll-categories'), permission: 'menu_settings_payroll' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'lock-closed',
        label: 'Hak Akses Pengguna',
        active: (url) => ['/users', '/settings/roles'].some(path => (url || '').startsWith(path)),
        permission: ['menu_users', 'menu_settings_roles'],
        children: [
            { href: '/users', label: 'Manajemen User', active: (url) => (url || '').startsWith('/users'), permission: 'menu_users' },
            { href: '/settings/roles', label: 'Role & Akses', active: (url) => (url || '').startsWith('/settings/roles'), permission: 'menu_settings_roles' },
        ]
    }
];

const filteredMenuItems = computed(() => {
    try {
        const filtered = [];
        let currentGroup = null;

        menuItems.forEach(item => {
            if (item.type === 'group') {
                currentGroup = item;
            } else {
                let isAllowed = false;
                if (item.type === 'dropdown' && item.children) {
                    const hasChildAccess = item.children.some(c => !c.permission || hasPermission(c.permission));
                    const hasParentAccess = !item.permission || hasPermission(item.permission);
                    isAllowed = hasParentAccess && hasChildAccess;
                } else {
                    isAllowed = !item.permission || hasPermission(item.permission);
                }

                if (isAllowed) {
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
        return menuItems;
    }
});

const toggleDropdown = (index) => {
    if (openDropdowns.value.includes(index)) {
        openDropdowns.value = openDropdowns.value.filter(i => i !== index);
    } else {
        openDropdowns.value.push(index);
    }
    localStorage.setItem('sidebarOpenDropdowns', JSON.stringify(openDropdowns.value));
};

onMounted(() => {
    // 1. Restore open dropdowns state from storage
    const savedDropdowns = localStorage.getItem('sidebarOpenDropdowns');
    if (savedDropdowns) {
        try {
            openDropdowns.value = JSON.parse(savedDropdowns);
        } catch (e) {
            openDropdowns.value = [];
        }
    }

    // 2. Automatically expand the dropdown that matches the current URL
    const currentUrl = page.url;
    filteredMenuItems.value.forEach((item, index) => {
        if (item.type === 'dropdown' && item.active(currentUrl)) {
            if (!openDropdowns.value.includes(index)) {
                openDropdowns.value.push(index);
            }
        }
    });

    // 3. Restore sidebar scroll position smoothly
    const scrollPos = parseInt(localStorage.getItem('sidebarScrollPos'), 10);
    if (!isNaN(scrollPos)) {
        let attempts = 0;
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

const iconPaths = {
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'x-circle': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    globe: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'document-add': 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'clipboard-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    cog: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    key: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
    'badge-check': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    server: 'M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
    box: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'git-branch': 'M6 3v12M18 9a3 3 0 01-3 3H9m-3 0a3 3 0 003 3h0a3 3 0 003-3m0 0V3',
    wifi: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0',
    'file-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'document-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    ticket: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
    'alert-circle': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    package: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
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
    support: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
    cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    'chart-pie': 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
    scissors: 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879a3 3 0 11-1.414-1.414L10.586 12 7.707 9.121a3 3 0 111.414-1.414L12 10.586m0 0L19 12',
    gift: 'M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5m-5 0V4.5a2.5 2.5 0 10-5 0v3.5m-5 0h20a1 1 0 011 1v3a1 1 0 01-1 1H2a1 1 0 01-1-1V9a1 1 0 011-1zm1 5v7a2 2 0 002 2h14a2 2 0 002-2v-7',
    sparkles: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.286L13 21l-2.286-6.857L5 12l5.714-2.286L13 3z',
    'presentation-chart-line': 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
    'desktop-computer': 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
};
</script>
