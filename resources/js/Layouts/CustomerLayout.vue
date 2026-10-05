<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top Navbar -->
        <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-8">
                        <Link href="/my/dashboard" class="flex-shrink-0 flex items-center gap-2 group">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 group-hover:shadow-indigo-500/50 transition-all duration-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-700 hidden sm:block">Dashboard Pelanggan</span>
                        </Link>
                        
                        <!-- Desktop Navigation -->
                        <div class="hidden sm:flex items-center gap-1 mt-1">
                            <Link href="/my/dashboard" :class="[route().current('customer-area.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'px-3 py-2 rounded-md text-sm font-medium transition-colors']">
                                Beranda
                            </Link>
                            <Link href="/my/billing" :class="[route().current('customer-area.billing') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'px-3 py-2 rounded-md text-sm font-medium transition-colors']">
                                Tagihan
                            </Link>
                            <Link href="/my/tickets" :class="[route().current('customer-area.tickets') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'px-3 py-2 rounded-md text-sm font-medium transition-colors']">
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
                            <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-1 border border-gray-100">
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

const showUserMenu = ref(false);
</script>
