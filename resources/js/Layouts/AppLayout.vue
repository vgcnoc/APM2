<template>
    <div class="flex min-h-screen bg-gray-50 font-sans text-gray-900">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col border-r border-gray-200 bg-white shadow-sm transition-all duration-300',
                sidebarOpen ? 'w-64' : 'w-20'
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-200">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div v-show="sidebarOpen" class="transition-opacity duration-200">
                    <h1 class="text-lg font-extrabold text-gray-900 leading-tight">ISP Manager</h1>
                    <p class="text-[11px] font-semibold text-blue-600 uppercase tracking-widest mt-0.5">Enterprise</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <template v-for="(item, index) in menuItems" :key="index">
                    <!-- Group Label -->
                    <div v-if="item.type === 'group' && sidebarOpen" class="pt-5 pb-2 px-4">
                        <span class="text-[10px] font-bold text-gray-500 tracking-widest uppercase">{{ item.label }}</span>
                    </div>

                    <!-- Link -->
                    <Link v-if="item.type === 'link'" :href="item.href" :class="['sidebar-link', { active: item.active($page.url) }]">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[item.icon]"/>
                        </svg>
                        <span v-if="sidebarOpen" class="truncate">{{ item.label }}</span>
                    </Link>
                </template>
            </nav>

            <!-- Sidebar Toggle -->
            <div class="border-t border-gray-200 p-3">
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
            :class="['flex-1 transition-all duration-300', sidebarOpen ? 'ml-64' : 'ml-20']"
        >
            <!-- Top Bar -->
            <header class="sticky top-0 z-40 bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ title }}</h2>
                        <p v-if="subtitle" class="text-sm font-medium text-gray-500 mt-0.5">{{ subtitle }}</p>
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
                        </div>
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
            <div class="p-6">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Dashboard' },
    subtitle: { type: String, default: '' },
});

const sidebarOpen = ref(true);

const menuItems = [
    { type: 'link', href: '/', icon: 'dashboard', label: 'Dashboard', active: (url) => url === '/' },
    { type: 'group', label: 'DATA CUSTOMERS' },
    { type: 'link', href: '/customers/booking', icon: 'bookmark', label: 'Data Booking', active: (url) => url.startsWith('/customers/booking') },
    { type: 'link', href: '/customers/survey', icon: 'search', label: 'Survey', active: (url) => url.startsWith('/customers/survey') },
    { type: 'link', href: '/customers/installed', icon: 'check-circle', label: 'Pasang / Aktif', active: (url) => url.startsWith('/customers/installed') },
    { type: 'link', href: '/customers', icon: 'users', label: 'Semua Pelanggan', active: (url) => url === '/customers' },
    { type: 'group', label: 'INFRASTRUKTUR' },
    { type: 'link', href: '/olts', icon: 'server', label: 'OLT', active: (url) => url.startsWith('/olts') },
    { type: 'link', href: '/odcs', icon: 'box', label: 'ODC', active: (url) => url.startsWith('/odcs') },
    { type: 'link', href: '/odps', icon: 'git-branch', label: 'ODP', active: (url) => url.startsWith('/odps') },
    { type: 'link', href: '/onts', icon: 'wifi', label: 'ONT', active: (url) => url.startsWith('/onts') },
    { type: 'link', href: '/materials', icon: 'archive', label: 'Material/Barang', active: (url) => url.startsWith('/materials') },
    { type: 'link', href: '/material-transactions', icon: 'shopping-cart', label: 'Order / Pengambilan', active: (url) => url.startsWith('/material-transactions') },
    { type: 'group', label: 'BILLING & KEUANGAN' },
    { type: 'link', href: '#', icon: 'file-text', label: 'Invoice', active: () => false },
    { type: 'link', href: '#', icon: 'credit-card', label: 'Pembayaran', active: () => false },
    { type: 'group', label: 'TICKETING' },
    { type: 'link', href: '#', icon: 'alert-circle', label: 'Daftar Laporan', active: () => false },
    { type: 'link', href: '#', icon: 'calendar', label: 'Jadwal Teknisi', active: () => false },
    { type: 'group', label: 'PENGATURAN' },
    { type: 'link', href: '/settings/areas', icon: 'map', label: 'Master Area', active: (url) => url.startsWith('/settings/areas') },
    { type: 'link', href: '/settings/api', icon: 'zap', label: 'API Integrasi', active: (url) => url.startsWith('/settings/api') },
    { type: 'link', href: '#', icon: 'settings', label: 'Manajemen User', active: () => false },
    { type: 'link', href: '#', icon: 'package', label: 'Paket Internet', active: () => false },
];

const iconPaths = {
    dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    bookmark: 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z',
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    server: 'M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
    box: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'git-branch': 'M6 3v12M18 9a3 3 0 01-3 3H9m-3 0a3 3 0 003 3h0a3 3 0 003-3m0 0V3',
    wifi: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0',
    'file-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    'alert-circle': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    'package': 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    zap: 'M13 10V3L4 14h7v7l9-11h-7z',
    map: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
    archive: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    'shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
};
</script>
