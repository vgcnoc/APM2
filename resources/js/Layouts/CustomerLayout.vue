<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
        <!-- Top Navbar -->
        <nav class="bg-white shadow-xs border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-3 sm:gap-6">
                        <!-- Mobile menu button -->
                        <div class="flex items-center sm:hidden">
                            <button @click="showMobileMenu = !showMobileMenu" type="button" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-hidden">
                                <span class="sr-only">Buka menu</span>
                                <svg v-if="!showMobileMenu" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <svg v-else class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Logo Perusahaan dengan Badge Area Pelanggan -->
                        <Link href="/my/dashboard" class="shrink-0 flex items-center gap-2.5 sm:gap-3 group">
                            <AppLogo context="navbar" :prefer-company-logo="true" />
                            <div class="hidden sm:flex items-center pl-2.5 border-l border-slate-200">
                                <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200/80 leading-none flex items-center gap-1.5 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                    Area Pelanggan
                                </span>
                            </div>
                        </Link>
                        
                        <!-- Desktop Navigation -->
                        <div class="hidden sm:flex items-center gap-1.5 mt-0.5">
                            <Link href="/my/dashboard" :class="[route().current('customer-area.dashboard') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium', 'px-3.5 py-2 rounded-xl text-xs transition-colors flex items-center gap-1.5']">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Beranda
                            </Link>
                            <Link href="/my/billing" :class="[route().current('customer-area.billing') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium', 'px-3.5 py-2 rounded-xl text-xs transition-colors flex items-center gap-1.5']">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Tagihan
                            </Link>
                            <Link href="/my/tickets" :class="[route().current('customer-area.tickets') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium', 'px-3.5 py-2 rounded-xl text-xs transition-colors flex items-center gap-1.5']">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Bantuan & Tiket
                            </Link>
                        </div>
                    </div>

                    <!-- Right Controls -->
                    <div class="flex items-center gap-3">
                        <!-- Admin Return Button (jika yang login adalah staf/admin) -->
                        <Link
                            v-if="isStaff"
                            href="/"
                            class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Panel Admin
                        </Link>

                        <!-- User Profile Dropdown -->
                        <div class="relative">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-2.5 focus:outline-hidden hover:bg-slate-100 p-1.5 rounded-xl transition-colors"
                            >
                                <img
                                    class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-50 shadow-xs"
                                    :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(userName)+'&color=4F46E5&background=EEF2FF'"
                                    :alt="userName"
                                />
                                <span class="text-xs font-semibold text-slate-700 hidden md:block max-w-[140px] truncate">
                                    {{ userName }}
                                </span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="showUserMenu"
                                class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl py-1.5 border border-slate-100 z-50 animate-in fade-in zoom-in-95 duration-100"
                            >
                                <div class="px-4 py-3 border-b border-slate-100">
                                    <p class="text-xs font-bold text-slate-900 truncate">{{ userName }}</p>
                                    <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ userEmail }}</p>
                                </div>
                                <Link
                                    v-if="isStaff"
                                    href="/"
                                    class="block w-full text-left px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition-colors"
                                >
                                    Kembali ke Panel Admin
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="block w-full text-left px-4 py-2.5 text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors"
                                >
                                    Keluar / Logout
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="showMobileMenu" class="sm:hidden border-t border-slate-100 bg-white px-4 pt-2 pb-4 space-y-1">
                <Link href="/my/dashboard" class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold" :class="route().current('customer-area.dashboard') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-700 hover:bg-slate-50'">Beranda</Link>
                <Link href="/my/billing" class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold" :class="route().current('customer-area.billing') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-700 hover:bg-slate-50'">Tagihan Saya</Link>
                <Link href="/my/tickets" class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold" :class="route().current('customer-area.tickets') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-700 hover:bg-slate-50'">Bantuan & Tiket</Link>
                <Link v-if="isStaff" href="/" class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50">Panel Admin</Link>
            </div>
        </nav>

        <!-- Flash Alerts -->
        <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <button @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-800">&times;</button>
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <button @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-800">&times;</button>
            </div>
        </div>

        <!-- Main Content Slot -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex-1 w-full">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200/80 bg-white py-4 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <p>&copy; {{ new Date().getFullYear() }} Portal Layanan Internet Pelanggan Resmi.</p>
                <div class="flex items-center gap-4">
                    <Link href="/my/dashboard" class="hover:text-indigo-600 transition-colors">Beranda</Link>
                    <Link href="/my/billing" class="hover:text-indigo-600 transition-colors">Tagihan</Link>
                    <Link href="/my/tickets" class="hover:text-indigo-600 transition-colors">Bantuan</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

const page = usePage();
const showUserMenu = ref(false);
const showMobileMenu = ref(false);

const userName = computed(() => page.props.auth?.user?.name || 'Pelanggan');
const userEmail = computed(() => page.props.auth?.user?.email || '-');
const userRole = computed(() => page.props.auth?.user?.role || '');

const isStaff = computed(() => {
    const role = (userRole.value || '').toLowerCase();
    const roles = page.props.auth?.user?.roles || [];
    return ['admin', 'super admin', 'superadmin', 'dispatcher', 'auditor', 'management'].includes(role) ||
        roles.some(r => ['admin', 'super admin', 'superadmin'].includes(r.toLowerCase()));
});
</script>
