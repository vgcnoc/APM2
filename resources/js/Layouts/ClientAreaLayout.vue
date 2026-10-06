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

                        <Link href="/client-area/dashboard" class="flex-shrink-0 flex items-center gap-2 group">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 group-hover:shadow-blue-500/50 transition-all duration-300">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <span class="text-lg sm:text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-700 hidden sm:block">Reseller Area</span>
                            <span class="text-lg sm:text-xl font-bold text-gray-900 sm:hidden">Reseller</span>
                        </Link>
                    </div>
                    
                    <div class="hidden md:flex items-center gap-8 flex-1 ml-10">
                        <Link href="/client-area/dashboard" class="text-sm font-medium text-gray-600 hover:text-blue-600">Dashboard</Link>
                        <Link href="/vouchers" class="text-sm font-medium text-gray-600 hover:text-blue-600">Semua Voucher</Link>
                        <Link href="/vouchers/online" class="text-sm font-medium text-gray-600 hover:text-blue-600">Online</Link>
                        <Link href="/vouchers/offline" class="text-sm font-medium text-gray-600 hover:text-blue-600">Offline</Link>
                        <Link href="/vouchers/expired" class="text-sm font-medium text-gray-600 hover:text-blue-600">Expired</Link>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center gap-4 relative">
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
                    <Link href="/client-area/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Dashboard</Link>
                    <Link href="/vouchers" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Semua Voucher</Link>
                    <Link href="/vouchers/online" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Online</Link>
                    <Link href="/vouchers/offline" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Offline</Link>
                    <Link href="/vouchers/expired" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Expired</Link>
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
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const showUserMenu = ref(false);
const showMobileMenu = ref(false);
</script>
