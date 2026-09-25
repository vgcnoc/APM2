<template>
    <div class="flex min-h-screen bg-gray-950">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col border-r border-white/10 bg-gray-950/95 backdrop-blur-xl transition-all duration-300',
                sidebarOpen ? 'w-64' : 'w-20'
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div v-show="sidebarOpen" class="transition-opacity duration-200">
                    <h1 class="text-lg font-bold text-white leading-tight">ISP Manager</h1>
                    <p class="text-xs text-gray-500">Network & Customer</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <!-- Dashboard -->
                <SidebarLink href="/" icon="dashboard" :label="sidebarOpen ? 'Dashboard' : ''" :active="$page.url === '/'" />

                <!-- Data Customers -->
                <SidebarGroup v-if="sidebarOpen" label="DATA CUSTOMERS" />
                <SidebarLink href="/customers/booking" icon="bookmark" :label="sidebarOpen ? 'Data Booking' : ''" :active="$page.url.startsWith('/customers/booking')" />
                <SidebarLink href="/customers/survey" icon="search" :label="sidebarOpen ? 'Survey' : ''" :active="$page.url.startsWith('/customers/survey')" />
                <SidebarLink href="/customers/installed" icon="check-circle" :label="sidebarOpen ? 'Pasang / Aktif' : ''" :active="$page.url.startsWith('/customers/installed')" />
                <SidebarLink href="/customers" icon="users" :label="sidebarOpen ? 'Semua Pelanggan' : ''" :active="$page.url === '/customers'" />

                <!-- Infrastruktur -->
                <SidebarGroup v-if="sidebarOpen" label="INFRASTRUKTUR" />
                <SidebarLink href="/olts" icon="server" :label="sidebarOpen ? 'OLT' : ''" :active="$page.url.startsWith('/olts')" />
                <SidebarLink href="/odcs" icon="box" :label="sidebarOpen ? 'ODC' : ''" :active="$page.url.startsWith('/odcs')" />
                <SidebarLink href="/odps" icon="git-branch" :label="sidebarOpen ? 'ODP' : ''" :active="$page.url.startsWith('/odps')" />
                <SidebarLink href="/onts" icon="wifi" :label="sidebarOpen ? 'ONT' : ''" :active="$page.url.startsWith('/onts')" />

                <!-- Billing -->
                <SidebarGroup v-if="sidebarOpen" label="BILLING & KEUANGAN" />
                <SidebarLink href="#" icon="file-text" :label="sidebarOpen ? 'Invoice' : ''" />
                <SidebarLink href="#" icon="credit-card" :label="sidebarOpen ? 'Pembayaran' : ''" />

                <!-- Ticketing -->
                <SidebarGroup v-if="sidebarOpen" label="TICKETING" />
                <SidebarLink href="#" icon="alert-circle" :label="sidebarOpen ? 'Daftar Laporan' : ''" />
                <SidebarLink href="#" icon="calendar" :label="sidebarOpen ? 'Jadwal Teknisi' : ''" />

                <!-- Settings -->
                <SidebarGroup v-if="sidebarOpen" label="PENGATURAN" />
                <SidebarLink href="#" icon="settings" :label="sidebarOpen ? 'Manajemen User' : ''" />
                <SidebarLink href="#" icon="package" :label="sidebarOpen ? 'Paket Internet' : ''" />
            </nav>

            <!-- Sidebar Toggle -->
            <div class="border-t border-white/10 p-3">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="w-full flex items-center justify-center p-2 rounded-xl text-gray-400 hover:bg-white/10 hover:text-white transition-all"
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
            <header class="sticky top-0 z-40 glass border-b border-white/10 px-6 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-white">{{ title }}</h2>
                        <p v-if="subtitle" class="text-sm text-gray-400">{{ subtitle }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Notification Bell -->
                        <button class="relative p-2 rounded-xl text-gray-400 hover:bg-white/10 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <!-- User Menu -->
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-sm font-bold text-white">
                                {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                            </div>
                            <div v-if="sidebarOpen" class="hidden md:block">
                                <p class="text-sm font-medium text-white">{{ $page.props.auth?.user?.name || 'Admin' }}</p>
                                <p class="text-xs text-gray-500 capitalize">{{ $page.props.auth?.user?.role || 'admin' }}</p>
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

// ── Sidebar Components ─────────────────────────────────────
</script>

<script>
// Inline sub-components for sidebar
import { Link } from '@inertiajs/vue3';

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
};

export default {
    components: {
        SidebarLink: {
            props: ['href', 'icon', 'label', 'active'],
            template: `
                <Link :href="href" :class="['sidebar-link', { active }]">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getPath(icon)"/>
                    </svg>
                    <span v-if="label" class="truncate">{{ label }}</span>
                </Link>
            `,
            methods: {
                getPath(icon) {
                    return iconPaths[icon] || iconPaths.dashboard;
                }
            },
            components: { Link },
        },
        SidebarGroup: {
            props: ['label'],
            template: `
                <div class="pt-4 pb-1 px-4">
                    <span class="text-[10px] font-bold text-gray-600 tracking-widest">{{ label }}</span>
                </div>
            `,
        },
    },
};
</script>
