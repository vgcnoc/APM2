<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top Navbar -->
        <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-3 sm:gap-6">
                        <!-- Mobile menu button -->
                        <div class="flex items-center sm:hidden">
                            <button @click="showMobileMenu = !showMobileMenu" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                                <span class="sr-only">Open main menu</span>
                                <svg v-if="!showMobileMenu" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <svg v-else class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Logo Perusahaan dengan Badge Area Pelanggan -->
                        <Link href="/my/dashboard" class="flex-shrink-0 flex items-center gap-2.5 sm:gap-3 group">
                            <AppLogo context="navbar" :prefer-company-logo="true" />
                            <div class="hidden sm:flex items-center pl-2.5 border-l border-gray-200">
                                <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200/80 leading-none flex items-center gap-1.5 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                    Area Pelanggan
                                </span>
                            </div>
                        </Link>
                        
                        <!-- Desktop Navigation -->
                        <div class="hidden sm:flex items-center gap-1 mt-0.5">
                            <Link href="/my/dashboard" :class="[route().current('customer-area.dashboard') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium', 'px-3 py-2 rounded-lg text-sm transition-colors']">
                                Beranda
                            </Link>
                            <Link href="/my/billing" :class="[route().current('customer-area.billing') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium', 'px-3 py-2 rounded-lg text-sm transition-colors']">
                                Tagihan
                            </Link>
                            <Link href="/my/tickets" :class="[route().current('customer-area.tickets') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium', 'px-3 py-2 rounded-lg text-sm transition-colors']">
                                Bantuan
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center gap-4">
                        <div class="relative">
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

            <!-- Mobile menu -->
            <div v-if="showMobileMenu" class="sm:hidden border-t border-gray-100 bg-white">
                <div class="px-3 pt-2 pb-3 space-y-1">
                    <Link href="/my/dashboard" class="block px-3 py-2 rounded-lg text-base font-medium" :class="route().current('customer-area.dashboard') ? 'text-indigo-700 bg-indigo-50 font-bold' : 'text-gray-700 hover:bg-gray-50'">Beranda</Link>
                    <Link href="/my/billing" class="block px-3 py-2 rounded-lg text-base font-medium" :class="route().current('customer-area.billing') ? 'text-indigo-700 bg-indigo-50 font-bold' : 'text-gray-700 hover:bg-gray-50'">Tagihan</Link>
                    <Link href="/my/tickets" class="block px-3 py-2 rounded-lg text-base font-medium" :class="route().current('customer-area.tickets') ? 'text-indigo-700 bg-indigo-50 font-bold' : 'text-gray-700 hover:bg-gray-50'">Bantuan & Tiket</Link>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

const showUserMenu = ref(false);
const showMobileMenu = ref(false);
</script>
