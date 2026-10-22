<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top Navbar -->
        <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-2 sm:gap-4">
                        <!-- Mobile menu button -->
                        <div class="flex items-center md:hidden">
                            <button @click="showMobileMenu = !showMobileMenu" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                                <span class="sr-only">Open main menu</span>
                                <svg v-if="!showMobileMenu" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <svg v-else class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Logo Perusahaan dengan Badge Area Reseller -->
                        <Link href="/client-area/dashboard" class="flex-shrink-0 flex items-center gap-2.5 sm:gap-3 group">
                            <AppLogo context="navbar" :prefer-company-logo="true" />
                            <div class="hidden sm:flex items-center pl-2.5 border-l border-gray-200">
                                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200/80 leading-none flex items-center gap-1.5 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Area Mitra / Reseller
                                </span>
                            </div>
                        </Link>
                    </div>
                    
                    <div class="hidden md:flex items-center gap-7 flex-1 ml-10">
                        <Link href="/client-area/dashboard" class="text-sm transition-colors" :class="$page.url === '/client-area/dashboard' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Dashboard</Link>
                        <Link href="/client-area/biller" class="text-sm transition-colors" :class="$page.url.startsWith('/client-area/biller') ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Loket Bayar</Link>
                        <Link href="/vouchers" class="text-sm transition-colors" :class="$page.url === '/vouchers' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Semua Voucher</Link>
                        <Link href="/vouchers/free" class="text-sm transition-colors" :class="$page.url.startsWith('/vouchers/free') ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Voucher Gratis</Link>
                        <Link href="/vouchers/online" class="text-sm transition-colors" :class="$page.url.startsWith('/vouchers/online') && !$page.url.includes('tab=offline') ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Online</Link>
                        <Link href="/vouchers/offline" class="text-sm transition-colors" :class="$page.url.startsWith('/vouchers/offline') || $page.url.includes('tab=offline') ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Offline</Link>
                        <Link href="/vouchers/expired" class="text-sm transition-colors" :class="$page.url.startsWith('/vouchers/expired') ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Expired</Link>
                        <Link href="/client-area/billing" class="text-sm transition-colors" :class="$page.url.startsWith('/client-area/billing') ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600 font-medium'">Tagihan & Kasbon</Link>
                    </div>

                    <!-- Theme Toggle & User Menu -->
                    <div class="flex items-center gap-2 sm:gap-4 relative">
                        <button 
                            type="button" 
                            @click="toggleTheme" 
                            class="p-2 rounded-xl border transition-all duration-200 cursor-pointer flex items-center justify-center group"
                            :class="isDarkMode 
                                ? 'bg-gray-800 border-gray-700 text-amber-400 hover:bg-gray-700 shadow-sm' 
                                : 'bg-gray-50 border-gray-200 text-slate-600 hover:bg-gray-100 shadow-2xs'"
                            :title="isDarkMode ? 'Beralih ke Mode Terang (Light Mode)' : 'Beralih ke Mode Gelap (Dark Mode)'"
                        >
                            <svg v-if="isDarkMode" class="w-4 h-4 text-amber-400 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="w-4 h-4 text-slate-600 transition-transform duration-300 group-hover:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                        <div>
                            <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-2 focus:outline-none hover:bg-gray-50 p-1.5 rounded-lg transition-colors">
                                <img class="h-8 w-8 rounded-full object-cover ring-2 ring-white shadow-sm" :src="'https://ui-avatars.com/api/?name='+encodeURIComponent($page.props.auth.user.name)+'&color=4F46E5&background=EEF2FF'" :alt="$page.props.auth.user.name" />
                                <span class="text-sm font-medium text-gray-700 hidden md:block">{{ $page.props.auth.user.name }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <!-- Dropdown -->
                            <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-1 border border-gray-100 z-50">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-xs text-gray-500 truncate mt-0.5">{{ $page.props.auth.user.email }}</p>
                                </div>
                                <Link href="/logout" method="post" as="button" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu, show/hide based on menu state. -->
            <div v-if="showMobileMenu" class="md:hidden border-t border-gray-100 bg-white">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <Link href="/client-area/dashboard" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url === '/client-area/dashboard' ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Dashboard</Link>
                    <Link href="/client-area/biller" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url.startsWith('/client-area/biller') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Loket Bayar Internet</Link>
                    <Link href="/vouchers" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url === '/vouchers' ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Semua Voucher</Link>
                    <Link href="/vouchers/free" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url.startsWith('/vouchers/free') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Voucher Gratis</Link>
                    <Link href="/vouchers/online" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url.startsWith('/vouchers/online') && !$page.url.includes('tab=offline') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Online</Link>
                    <Link href="/vouchers/offline" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url.startsWith('/vouchers/offline') || $page.url.includes('tab=offline') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Offline</Link>
                    <Link href="/vouchers/expired" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url.startsWith('/vouchers/expired') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Expired</Link>
                    <Link href="/client-area/billing" class="block px-3 py-2 rounded-md text-base font-medium" :class="$page.url.startsWith('/client-area/billing') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'">Tagihan & Kasbon</Link>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

const showUserMenu = ref(false);
const showMobileMenu = ref(false);
const isDarkMode = ref(false);

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('apm_theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('apm_theme', 'light');
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('apm_theme');
        isDarkMode.value = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
    }
});
</script>
