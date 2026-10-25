<template>
    <div class="apm-shell workspace-ui workspace-partner min-h-screen flex flex-col transition-colors">
        <!-- Top Navbar -->
        <nav class="apm-topbar bg-white/95 dark:bg-gray-900/95 backdrop-blur-md shadow-xs border-b border-gray-100 dark:border-gray-800/90 sticky top-0 z-50 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Sisi Kiri: Hamburger Mobile & Logo -->
                    <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                        <!-- Mobile menu button -->
                        <div class="flex items-center md:hidden">
                            <button 
                                @click="showMobileMenu = !showMobileMenu" 
                                type="button" 
                                class="inline-flex items-center justify-center p-2 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none"
                            >
                                <span class="sr-only">Buka menu utama</span>
                                <svg v-if="!showMobileMenu" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <svg v-else class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Logo Perusahaan dengan Badge Area Reseller -->
                        <Link href="/client-area/dashboard" class="flex-shrink-0 flex items-center gap-2.5 sm:gap-3 group">
                            <AppLogo context="navbar" :prefer-company-logo="true" />
                            <div class="hidden sm:flex items-center pl-2.5 border-l border-gray-200 dark:border-gray-700">
                                <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-0.5 rounded-full border border-emerald-200/80 dark:border-emerald-800/60 leading-none flex items-center gap-1.5 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Area Mitra / Reseller
                                </span>
                            </div>
                        </Link>
                    </div>
                    
                    <!-- Menu Utama Desktop (Simple & Rapi) -->
                    <div class="hidden md:flex items-center gap-1.5 lg:gap-2 flex-1 ml-6 lg:ml-10">
                        <!-- 1. Dashboard -->
                        <Link 
                            href="/client-area/dashboard" 
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200"
                            :class="$page.url === '/client-area/dashboard' 
                                ? 'text-blue-600 dark:text-blue-400 bg-blue-50/90 dark:bg-blue-900/40 font-semibold shadow-2xs border border-blue-100 dark:border-blue-800/60' 
                                : 'text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 font-medium'"
                        >
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span>Dashboard</span>
                        </Link>

                        <!-- 2. Loket Bayar -->
                        <Link 
                            href="/client-area/biller" 
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200"
                            :class="$page.url.startsWith('/client-area/biller') 
                                ? 'text-blue-600 dark:text-blue-400 bg-blue-50/90 dark:bg-blue-900/40 font-semibold shadow-2xs border border-blue-100 dark:border-blue-800/60' 
                                : 'text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 font-medium'"
                        >
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Loket Bayar</span>
                        </Link>

                        <!-- 3. Kelola Voucher (Dropdown Elegan) -->
                        <div class="relative" ref="voucherDropdownRef">
                            <button 
                                type="button"
                                @click="toggleVoucherDropdown"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200 cursor-pointer"
                                :class="isVoucherActive 
                                    ? 'text-blue-600 dark:text-blue-400 bg-blue-50/90 dark:bg-blue-900/40 font-semibold shadow-2xs border border-blue-100 dark:border-blue-800/60' 
                                    : 'text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 font-medium'"
                            >
                                <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                </svg>
                                <span>Voucher</span>
                                <svg 
                                    class="w-3.5 h-3.5 transition-transform duration-200" 
                                    :class="showVoucherDropdown ? 'rotate-180 text-blue-600 dark:text-blue-400' : 'text-gray-400'"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu Box -->
                            <div 
                                v-if="showVoucherDropdown" 
                                class="absolute left-0 mt-2 w-72 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 py-1.5 z-50 animate-fade-in-up"
                            >
                                <div class="px-3.5 py-2 border-b border-gray-100 dark:border-gray-800/80">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Layanan Hotspot Voucher</span>
                                </div>

                                <!-- Semua Voucher -->
                                <Link 
                                    href="/vouchers" 
                                    @click="showVoucherDropdown = false"
                                    class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-blue-50/70 dark:hover:bg-gray-800/80 transition-colors group"
                                    :class="$page.url === '/vouchers' || $page.url.startsWith('/vouchers?') ? 'bg-blue-50/50 dark:bg-gray-800/50' : ''"
                                >
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">Semua Voucher</p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">Katalog, generate & cetak voucher</p>
                                    </div>
                                </Link>

                                <!-- Monitoring Sesi (Online & Offline) -->
                                <Link 
                                    href="/vouchers/online" 
                                    @click="showVoucherDropdown = false"
                                    class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-emerald-50/70 dark:hover:bg-gray-800/80 transition-colors group"
                                    :class="$page.url.startsWith('/vouchers/online') || $page.url.startsWith('/vouchers/offline') ? 'bg-emerald-50/50 dark:bg-gray-800/50' : ''"
                                >
                                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-2xs relative">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                        </svg>
                                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Monitoring Sesi</p>
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 rounded-md">Live</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">Pantau voucher online & offline</p>
                                    </div>
                                </Link>

                                <!-- Voucher Expired -->
                                <Link 
                                    href="/vouchers/expired" 
                                    @click="showVoucherDropdown = false"
                                    class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-rose-50/70 dark:hover:bg-gray-800/80 transition-colors group"
                                    :class="$page.url.startsWith('/vouchers/expired') ? 'bg-rose-50/50 dark:bg-gray-800/50' : ''"
                                >
                                    <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 group-hover:text-rose-600 dark:group-hover:text-rose-400">Voucher Expired</p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">Riwayat voucher kedaluwarsa</p>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- 4. Tagihan & Kasbon -->
                        <Link 
                            href="/client-area/billing" 
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200"
                            :class="$page.url.startsWith('/client-area/billing') 
                                ? 'text-blue-600 dark:text-blue-400 bg-blue-50/90 dark:bg-blue-900/40 font-semibold shadow-2xs border border-blue-100 dark:border-blue-800/60' 
                                : 'text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 font-medium'"
                        >
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                            </svg>
                            <span>Tagihan & Kasbon</span>
                        </Link>
                    </div>

                    <!-- Pattern & Theme Toggles & User Menu Profile -->
                    <div class="flex items-center gap-2 sm:gap-2.5 shrink-0 relative">
                        <!-- Corak Background Picker -->
                        <PatternPicker />

                        <!-- Dark / Light Theme Toggle -->
                        <ThemeToggle />

                        <!-- User Profile Dropdown -->
                        <div class="relative" ref="userDropdownRef">
                            <button 
                                @click="toggleUserMenu" 
                                class="flex items-center gap-2 focus:outline-none hover:bg-gray-100 dark:hover:bg-gray-800 p-1.5 rounded-xl transition-colors cursor-pointer"
                            >
                                <img 
                                    class="h-8 w-8 rounded-full object-cover ring-2 ring-blue-500/30 shadow-sm" 
                                    :src="'https://ui-avatars.com/api/?name='+encodeURIComponent($page.props.auth?.user?.name || 'User')+'&color=4F46E5&background=EEF2FF'" 
                                    :alt="$page.props.auth?.user?.name || 'User'" 
                                />
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 hidden md:block max-w-[120px] truncate">
                                    {{ $page.props.auth?.user?.name || 'User' }}
                                </span>
                                <svg 
                                    class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" 
                                    :class="showUserMenu ? 'rotate-180' : ''" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- User Dropdown Box -->
                            <div 
                                v-if="showUserMenu" 
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-900 rounded-2xl shadow-xl py-1.5 border border-gray-100 dark:border-gray-800 z-50 animate-fade-in-up"
                            >
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                                    <p class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">{{ $page.props.auth?.user?.name }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $page.props.auth?.user?.email }}</p>
                                    <div class="mt-2 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Mitra Reseller
                                    </div>
                                </div>
                                <Link 
                                    v-if="['admin', 'superadmin', 'super admin', 'super-admin'].includes(String($page.props.auth?.user?.role || '').toLowerCase())"
                                    href="/" 
                                    class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    <span>Panel Admin ISP</span>
                                </Link>
                                <Link 
                                    href="/logout" 
                                    method="post" 
                                    as="button" 
                                    class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 hover:text-red-700 transition-colors cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>Keluar (Logout)</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div v-if="showMobileMenu" class="md:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 transition-colors">
                <div class="px-3 pt-3 pb-4 space-y-1 sm:px-4">
                    <!-- Dashboard -->
                    <Link 
                        href="/client-area/dashboard" 
                        @click="showMobileMenu = false"
                        class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors" 
                        :class="$page.url === '/client-area/dashboard' 
                            ? 'text-blue-600 bg-blue-50 dark:bg-blue-950/60 font-bold' 
                            : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-800'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Dashboard</span>
                    </Link>

                    <!-- Loket Bayar -->
                    <Link 
                        href="/client-area/biller" 
                        @click="showMobileMenu = false"
                        class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors" 
                        :class="$page.url.startsWith('/client-area/biller') 
                            ? 'text-blue-600 bg-blue-50 dark:bg-blue-950/60 font-bold' 
                            : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-800'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Loket Bayar</span>
                    </Link>

                    <!-- Voucher Accordion Section -->
                    <div class="pt-2 pb-1">
                        <button 
                            type="button" 
                            @click="mobileVoucherOpen = !mobileVoucherOpen"
                            class="w-full flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                </svg>
                                Kelola Voucher
                            </span>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="mobileVoucherOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-show="mobileVoucherOpen" class="pl-4 pr-1 mt-1 space-y-1 border-l-2 border-indigo-100 dark:border-indigo-900 ml-4">
                            <Link 
                                href="/vouchers" 
                                @click="showMobileMenu = false"
                                class="block px-3 py-2 rounded-lg text-sm transition-colors" 
                                :class="$page.url === '/vouchers' ? 'text-indigo-600 font-bold bg-indigo-50/60 dark:bg-indigo-950/40' : 'text-gray-600 dark:text-gray-400 hover:text-indigo-600'"
                            >
                                Semua Voucher
                            </Link>
                            <Link 
                                href="/vouchers/online" 
                                @click="showMobileMenu = false"
                                class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors" 
                                :class="$page.url.startsWith('/vouchers/online') || $page.url.startsWith('/vouchers/offline') ? 'text-emerald-600 font-bold bg-emerald-50/60 dark:bg-emerald-950/40' : 'text-gray-600 dark:text-gray-400 hover:text-emerald-600'"
                            >
                                <span>Monitoring Sesi</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded">Live</span>
                            </Link>
                            <Link 
                                href="/vouchers/expired" 
                                @click="showMobileMenu = false"
                                class="block px-3 py-2 rounded-lg text-sm transition-colors" 
                                :class="$page.url.startsWith('/vouchers/expired') ? 'text-rose-600 font-bold bg-rose-50/60 dark:bg-rose-950/40' : 'text-gray-600 dark:text-gray-400 hover:text-rose-600'"
                            >
                                Voucher Expired
                            </Link>
                        </div>
                    </div>

                    <!-- Tagihan & Kasbon -->
                    <Link 
                        href="/client-area/billing" 
                        @click="showMobileMenu = false"
                        class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors" 
                        :class="$page.url.startsWith('/client-area/billing') 
                            ? 'text-blue-600 bg-blue-50 dark:bg-blue-950/60 font-bold' 
                            : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-800'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                        </svg>
                        <span>Tagihan & Kasbon</span>
                    </Link>

                    <!-- Link to Admin Panel for Admin / Superadmin -->
                    <Link 
                        v-if="['admin', 'superadmin', 'super admin', 'super-admin'].includes(String($page.props.auth?.user?.role || '').toLowerCase())"
                        href="/" 
                        @click="showMobileMenu = false"
                        class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-blue-600 dark:text-blue-400 bg-blue-50/70 dark:bg-blue-950/40"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Kembali ke Panel Admin ISP</span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Main Content Slot -->
        <main class="workspace-content max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex-1 w-full">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-auto py-3.5 px-4 sm:px-6 lg:px-8 border-t border-slate-200/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md text-xs text-slate-500 dark:text-slate-400 print:hidden transition-colors">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
                <p class="font-medium">
                    Copyright {{ new Date().getFullYear() }} &copy; <span class="font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">VIRUZS</span> - Smart Billing, Smarter Business.
                </p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Portal Mitra &amp; Reseller</p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import PatternPicker from '@/Components/PatternPicker.vue';
import { initTheme } from '@/Composables/useTheme';

const page = usePage();

const showUserMenu = ref(false);
const showMobileMenu = ref(false);
const showVoucherDropdown = ref(false);
const mobileVoucherOpen = ref(true);

const voucherDropdownRef = ref(null);
const userDropdownRef = ref(null);

const isVoucherActive = computed(() => {
    const url = page.url || '';
    return url.startsWith('/vouchers');
});

const toggleVoucherDropdown = () => {
    showVoucherDropdown.value = !showVoucherDropdown.value;
    if (showVoucherDropdown.value) {
        showUserMenu.value = false;
    }
};

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
    if (showUserMenu.value) {
        showVoucherDropdown.value = false;
    }
};

const handleClickOutside = (event) => {
    if (voucherDropdownRef.value && !voucherDropdownRef.value.contains(event.target)) {
        showVoucherDropdown.value = false;
    }
    if (userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
        showUserMenu.value = false;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        initTheme();
        document.addEventListener('click', handleClickOutside);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        document.removeEventListener('click', handleClickOutside);
    }
});
</script>
