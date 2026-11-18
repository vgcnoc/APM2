<template>
    <AppLayout title="Profil Voucher" subtitle="Kelola daftar profil, durasi, kecepatan, shared user, dan fee reseller">
        <div class="space-y-6">
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </span>
                        Daftar Profil Voucher Hotspot
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola template profil voucher MikroTik RADIUS dengan kontrol batas shared user, durasi sesi, dan fee reseller.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <button 
                        @click="syncAllProfiles" 
                        :disabled="syncingAll"
                        class="flex-1 sm:flex-initial px-3.5 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-bold shadow-sm hover:shadow transition-all flex items-center justify-center gap-2"
                        title="Sinkronkan seluruh profil ke tabel RADIUS (radgroupreply & radgroupcheck)"
                    >
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" :class="{ 'animate-spin': syncingAll }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ syncingAll ? 'Menyinkronkan...' : 'Sinkron Semua ke RADIUS' }}</span>
                    </button>
                    <button 
                        @click="openCreateModal" 
                        class="flex-1 sm:flex-initial px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Tambah Profil Baru</span>
                    </button>
                </div>
            </div>

            <!-- EXECUTIVE KPI CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Card 1: Total Profil -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Profil</span>
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">{{ computedStats.total_profiles }}</span>
                        <span class="text-xs text-slate-400 font-medium">Paket</span>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                        Template voucher MikroTik RADIUS
                    </div>
                </div>

                <!-- Card 2: Profil Berbayar -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Profil Berbayar</span>
                        <span class="p-2 bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ computedStats.paid_profiles }}</span>
                        <span class="text-xs text-slate-400 font-medium">Komersial</span>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                        Untuk penjualan mitra & reseller
                    </div>
                </div>

                <!-- Card 3: Hotspot Gratis -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">Hotspot Gratis</span>
                        <span class="p-2 bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400">{{ computedStats.free_profiles }}</span>
                        <span class="text-xs text-slate-400 font-medium">Khusus Pelanggan</span>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                        Otomatis sinkron status langganan
                    </div>
                </div>

                <!-- Card 4: Total Voucher Terkait -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Voucher Beredar</span>
                        <span class="p-2 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400">{{ computedStats.total_vouchers }}</span>
                        <span class="text-xs text-slate-400 font-medium">Voucher</span>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 flex-wrap">
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ computedStats.total_available }} Tersedia</span>
                        <span>•</span>
                        <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ computedStats.total_used }} Digunakan</span>
                    </div>
                </div>
            </div>

            <!-- FILTER & SEARCH BAR -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/80 shadow-sm space-y-3">
                <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari nama profil, durasi, kecepatan (misal: 8 JAM, 3M/4M, promo)..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                        >
                    </div>

                    <!-- Filter Dropdowns -->
                    <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                        <select 
                            v-model="filterType"
                            class="px-3 py-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="paid">Berbayar (Komersial)</option>
                            <option value="free">Hotspot Gratis Pelanggan</option>
                        </select>

                        <select 
                            v-model="filterShared"
                            class="px-3 py-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Shared User</option>
                            <option value="1">1 User (Strict)</option>
                            <option value="multi">Multi Device (>1 User)</option>
                        </select>

                        <button 
                            v-if="searchQuery || filterType || filterShared"
                            @click="resetFilter"
                            class="px-3 py-2 text-xs font-bold text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 transition-colors whitespace-nowrap"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/80 text-left">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Profil</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tarif & Modal Reseller</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durasi Sesi</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Limit Kecepatan (MikroTik)</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Shared Users</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 bg-white dark:bg-slate-800">
                            <tr 
                                v-for="profile in filteredProfiles" 
                                :key="profile.id" 
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors"
                            >
                                <!-- NAMA PROFIL -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ profile.name }}</span>
                                        <span v-if="profile.is_customer_free" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800 shadow-sm">
                                            ⭐ Hotspot Gratis Pelanggan
                                        </span>
                                        <span v-if="profile.vouchers_count" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                            {{ profile.vouchers_count }} Voucher
                                        </span>
                                    </div>
                                    <div v-if="profile.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-1 max-w-sm">{{ profile.description }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 font-mono">
                                        Group RADIUS: <span class="text-slate-600 dark:text-slate-300 font-bold">vcp_{{ profile.id }}</span>
                                    </div>
                                </td>

                                <!-- TARIF & MODAL RESELLER -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div v-if="profile.is_customer_free">
                                        <span class="text-sm font-extrabold text-purple-600 dark:text-purple-400">Gratis (Rp 0)</span>
                                        <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Khusus 1 User Hotspot Pelanggan</div>
                                    </div>
                                    <div v-else>
                                        <div class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400">
                                            Rp {{ formatPrice(profile.price) }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium flex items-center gap-1.5 mt-0.5">
                                            <span>Modal: <strong class="text-slate-700 dark:text-slate-200">Rp {{ formatPrice(Math.max(0, profile.price - (profile.fee_reseller || 0))) }}</strong></span>
                                            <span class="text-slate-300 dark:text-slate-600">•</span>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Fee: Rp {{ formatPrice(profile.fee_reseller || 0) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- DURASI SESI -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-lg" :class="profile.is_customer_free ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-200'">
                                        <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ profile.is_customer_free ? 'Masa Aktif Pelanggan' : profile.duration }}
                                    </div>
                                    <div v-if="!profile.is_customer_free" class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                        {{ humanizeDuration(profile.duration) }}
                                    </div>
                                </td>

                                <!-- LIMIT KECEPATAN (CERDAS DENGAN BURST PARSER) -->
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <!-- Main Rate Chip -->
                                        <div class="inline-flex items-center gap-1.5 text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700/60 text-slate-800 dark:text-slate-200 border border-slate-200/80 dark:border-slate-600">
                                            <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                            <span>{{ parseRate(profile.limit_rate).main }}</span>
                                        </div>

                                        <!-- Burst Rate Badge (Jika Ada Burst) -->
                                        <div v-if="parseRate(profile.limit_rate).hasBurst" class="flex items-center gap-1 mt-1">
                                            <button 
                                                @click="openBurstModal(profile)" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition-colors"
                                                title="Klik untuk melihat detail parameter Burst Rate MikroTik"
                                            >
                                                <span>⚡ Burst: {{ parseRate(profile.limit_rate).burst }} ({{ parseRate(profile.limit_rate).time }}s)</span>
                                                <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <!-- SHARED USERS -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div v-if="profile.shared_users == 1" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        1 User (Strict)
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        {{ profile.shared_users }} Users
                                    </div>
                                </td>

                                <!-- AKSI -->
                                <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Detail Button -->
                                        <button 
                                            @click="openDetailModal(profile)" 
                                            class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700/60 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold transition-colors"
                                            title="Lihat Detail Profil & Atribut RADIUS"
                                        >
                                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Quick Sync RADIUS -->
                                        <button 
                                            @click="syncSingleProfile(profile)" 
                                            class="p-2 bg-slate-100 hover:bg-indigo-50 dark:bg-slate-700/60 dark:hover:bg-indigo-900/40 text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400 rounded-lg text-xs font-bold transition-colors"
                                            title="Sinkronkan profil ini ke FreeRADIUS"
                                        >
                                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </button>

                                        <!-- Edit Button -->
                                        <button 
                                            @click="openEditModal(profile)" 
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 dark:bg-slate-700/60 dark:hover:bg-indigo-900/40 text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-400 rounded-lg text-xs font-bold transition-colors"
                                        >
                                            Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            v-if="!profile.is_customer_free" 
                                            @click="deleteProfile(profile)" 
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-red-50 dark:bg-slate-700/60 dark:hover:bg-red-900/40 text-slate-700 hover:text-red-600 dark:text-slate-200 dark:hover:text-red-400 rounded-lg text-xs font-bold transition-colors"
                                        >
                                            Hapus
                                        </button>
                                        <span v-else class="text-slate-400 dark:text-slate-500 text-xs italic font-medium px-2">Sistem</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredProfiles.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center text-slate-400 mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        </div>
                                        <p class="text-slate-700 dark:text-slate-200 text-sm font-bold">Tidak ada profil ditemukan</p>
                                        <p class="text-slate-400 text-xs mt-0.5">Coba ubah kata kunci pencarian atau reset filter.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL FORM (TAMBAH / EDIT PROFIL) -->
        <!-- ========================================================================= -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm p-3 sm:p-4 animate-fade-in">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col border border-slate-200 dark:border-slate-700 animate-scale-up">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/70 dark:bg-slate-900/40">
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="p-1.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            {{ editMode ? 'Edit Profil Voucher' : 'Tambah Profil Voucher Baru' }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Atur tarif, durasi sesi MikroTik, limit kecepatan, batas shared user, dan fee reseller.</p>
                    </div>
                    <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body with Scroll Reset -->
                <div ref="modalBodyRef" class="p-6 overflow-y-auto max-h-[75vh] space-y-5 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                    <form @submit.prevent="submitForm" id="profileForm" class="space-y-5">
                        <!-- Switch Khusus Hotspot Pelanggan -->
                        <div class="p-3.5 bg-purple-50/80 dark:bg-purple-950/40 rounded-xl border border-purple-200 dark:border-purple-800/60">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.is_customer_free" @change="onToggleCustomerFree" class="mt-0.5 rounded border-slate-300 dark:border-slate-600 text-purple-600 focus:ring-purple-500 w-4 h-4">
                                <div>
                                    <span class="text-xs font-bold text-purple-950 dark:text-purple-300">Profil Khusus Hotspot Gratis Pelanggan</span>
                                    <p class="text-[11px] text-purple-700 dark:text-purple-400 mt-0.5">Voucher khusus 1 user hotspot gratis yang diberikan saat aktivasi pelanggan. Masa aktif otomatis mengikuti status aktif pelanggan.</p>
                                </div>
                            </label>
                        </div>

                        <!-- SECTION 1: NAMA & DESKRIPSI -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Nama Profil Voucher <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" class="w-full bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3" required placeholder="Contoh: 1 Jam - Rp 3.000 / 1 Hari Unlimited">
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Deskripsi Profil (Opsional)</label>
                                <textarea v-model="form.description" rows="2" class="w-full bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3" placeholder="Contoh: Cocok untuk browsing cepat, streaming YouTube tanpa buffering"></textarea>
                            </div>
                        </div>

                        <!-- SECTION 2: TARIF & FEE RESELLER DENGAN KALKULATOR MODAL CERDAS -->
                        <div v-if="!form.is_customer_free" class="p-4 bg-slate-50/80 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-3.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Tarif & Bagi Hasil Reseller / Mitra
                                </h3>
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Kalkulator Otomatis</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        Harga Jual ke Pelanggan <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex shadow-sm rounded-xl overflow-hidden border border-slate-300 dark:border-slate-700 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                        <span class="inline-flex items-center px-3 bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 border-r border-slate-300 dark:border-slate-700">Rp</span>
                                        <input v-model.number="form.price" type="number" min="0" step="500" class="w-full bg-white dark:bg-slate-900 border-0 focus:ring-0 text-sm font-bold text-slate-900 dark:text-white py-2 px-3" required placeholder="0">
                                    </div>
                                    <p v-if="form.errors.price" class="text-xs text-red-500 mt-1">{{ form.errors.price }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                        <span>Fee Reseller / Margin Mitra <span class="text-red-500">*</span></span>
                                    </label>
                                    <div class="flex shadow-sm rounded-xl overflow-hidden border border-slate-300 dark:border-slate-700 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                        <span class="inline-flex items-center px-3 bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 border-r border-slate-300 dark:border-slate-700">Rp</span>
                                        <input v-model.number="form.fee_reseller" type="number" min="0" step="100" class="w-full bg-white dark:bg-slate-900 border-0 focus:ring-0 text-sm font-bold text-indigo-600 dark:text-indigo-400 py-2 px-3" required placeholder="0">
                                    </div>
                                    <p v-if="form.errors.fee_reseller" class="text-xs text-red-500 mt-1">{{ form.errors.fee_reseller }}</p>
                                </div>
                            </div>

                            <!-- Live Breakdown Card -->
                            <div class="p-3.5 rounded-xl border transition-all" :class="isFeeExceeded ? 'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-800' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 shadow-sm'">
                                <div v-if="isFeeExceeded" class="flex items-start gap-2 text-xs text-red-700 dark:text-red-400 font-bold">
                                    <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Peringatan: Fee Reseller (Rp {{ formatPrice(form.fee_reseller) }}) tidak boleh melebihi Harga Jual (Rp {{ formatPrice(form.price) }}). Harap sesuaikan kembali.</span>
                                </div>
                                <div v-else class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-100 dark:divide-slate-700">
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-slate-400">Harga Jual</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-white mt-0.5">Rp {{ formatPrice(form.price) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-indigo-500 dark:text-indigo-400">Margin Reseller</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-indigo-600 dark:text-indigo-400 mt-0.5">Rp {{ formatPrice(form.fee_reseller) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Modal Reseller</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-emerald-700 dark:text-emerald-300 mt-0.5">Rp {{ formatPrice(resellerCost) }}</div>
                                    </div>
                                </div>
                                <p v-if="!isFeeExceeded" class="text-[10px] text-slate-500 dark:text-slate-400 text-center mt-2 font-medium">
                                    💡 Saat reseller generate voucher, saldo mereka dipotong <strong>Rp {{ formatPrice(resellerCost) }}</strong> (Modal) dan mendapat keuntungan <strong>Rp {{ formatPrice(form.fee_reseller) }}</strong> per voucher.
                                </p>
                            </div>
                        </div>

                        <!-- SECTION 3: DURASI & MIKROTIK SESSION TIMEOUT -->
                        <div v-if="!form.is_customer_free" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Durasi Sesi (MikroTik Format) <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <select v-model="durationChoice" class="w-full bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3" required>
                                    <option value="" disabled>-- Pilih Durasi --</option>
                                    <option v-for="d in durationOptions" :key="d.value" :value="d.value">{{ d.label }} ({{ d.value }})</option>
                                    <option value="__custom">Kustom (Isi Sendiri)</option>
                                </select>
                                <input v-if="durationChoice === '__custom'" v-model="form.duration" type="text" class="w-full bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm py-2 px-3" required placeholder="Contoh: 30m, 2h, 1d, 7d">
                            </div>
                            <div v-if="form.duration" class="p-2.5 bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800 rounded-xl text-xs text-blue-800 dark:text-blue-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>MikroTik Session-Timeout & Max-All-Session: <strong>{{ parsedDurationHuman }}</strong>. Router otomatis memutus koneksi saat waktu habis.</span>
                            </div>
                            <p v-if="form.errors.duration" class="text-xs text-red-500 mt-1">{{ form.errors.duration }}</p>
                        </div>
                        <div v-else class="p-3 bg-purple-50/50 dark:bg-purple-950/40 rounded-xl border border-purple-100 dark:border-purple-800 text-xs text-purple-800 dark:text-purple-300">
                            <strong>Durasi:</strong> Mengikuti masa aktif langganan pelanggan (selama status pelanggan aktif).
                        </div>

                        <!-- SECTION 4: LIMIT RATE -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between">
                                <span>Limit Rate (Kecepatan Download/Upload)</span>
                                <span class="text-[11px] text-slate-400 font-normal">Format: Upload/Download (Contoh: 1M/1M)</span>
                            </label>
                            <LimitRateCalculator v-model="form.limit_rate" />
                            <!-- Quick Chips -->
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <span class="text-[11px] text-slate-400 font-semibold mr-1">Preset Cepat:</span>
                                <button v-for="rate in ['512k/512k', '1M/1M', '2M/2M', '3M/3M', '5M/5M', '10M/10M']" :key="rate" type="button" @click="form.limit_rate = rate" class="px-2 py-0.5 rounded-lg text-xs font-mono font-bold bg-slate-100 hover:bg-indigo-50 dark:bg-slate-700 dark:hover:bg-indigo-900/50 text-slate-700 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-300 border border-slate-200 dark:border-slate-600 transition-colors">
                                    {{ rate }}
                                </button>
                            </div>
                        </div>

                        <!-- SECTION 5: SHARED USERS (CERDAS & KETAT) -->
                        <div v-if="!form.is_customer_free" class="p-4 bg-slate-50/80 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Shared Users (Batas Perangkat Simultan) <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <div class="w-32">
                                    <input v-model.number="form.shared_users" type="number" min="1" max="20" class="w-full bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-extrabold py-2 px-3 text-center" required>
                                </div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Perangkat bersamaan</span>
                            </div>

                            <!-- Indicator Badge & Explanation -->
                            <div v-if="form.shared_users == 1" class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-300 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-amber-800 dark:text-amber-300">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Proteksi Ketat 1 Perangkat Aktif (Strict Port-Limit & Simultaneous-Use)
                                </div>
                                <p class="text-[11px] text-amber-700 dark:text-amber-400">
                                    1 kode voucher ini <strong>hanya dapat digunakan oleh 1 HP/Laptop dalam satu waktu</strong>. Jika perangkat kedua mencoba login saat voucher sedang aktif, router MikroTik dan RADIUS akan <strong>langsung menolak login</strong>.
                                </p>
                            </div>
                            <div v-else class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-300 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-blue-800 dark:text-blue-300">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Multi-Device Login (Maksimal {{ form.shared_users }} Perangkat Simultan)
                                </div>
                                <p class="text-[11px] text-blue-700 dark:text-blue-400">
                                    Maksimal <strong>{{ form.shared_users }} perangkat</strong> dapat login bersamaan menggunakan kode voucher yang sama. Perangkat ke-{{ form.shared_users + 1 }} dan seterusnya akan ditolak otomatis.
                                </p>
                            </div>
                            <p v-if="form.errors.shared_users" class="text-xs text-red-500 mt-1">{{ form.errors.shared_users }}</p>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 flex justify-between items-center gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="triggerSubmit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center gap-2" :disabled="form.processing || isFeeExceeded">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>{{ form.processing ? 'Menyimpan & Sinkron RADIUS...' : 'Simpan Profil' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL DETAIL & RADIUS INSPECTION -->
        <!-- ========================================================================= -->
        <div v-if="showDetailModal && selectedProfile" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm p-3 sm:p-4 animate-fade-in">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col border border-slate-200 dark:border-slate-700 animate-scale-up">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/70 dark:bg-slate-900/40">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                                Detail Profil: {{ selectedProfile.name }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Inspeksi konfigurasi MikroTik, RADIUS attributes, dan statistik voucher.</p>
                        </div>
                    </div>
                    <button @click="showDetailModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 overflow-y-auto max-h-[75vh] space-y-5 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                    <!-- Badges Row -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span v-if="selectedProfile.is_customer_free" class="px-3 py-1 rounded-full text-xs font-black bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                            ⭐ Hotspot Gratis Pelanggan
                        </span>
                        <span v-else class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            Komersial / Reseller
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                            Group: vcp_{{ selectedProfile.id }}
                        </span>
                    </div>

                    <!-- Financial & Reseller Breakdown -->
                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 space-y-3">
                        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Struktur Tarif & Keuntungan Reseller
                        </h3>
                        <div class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-200 dark:divide-slate-700">
                            <div>
                                <div class="text-[10px] uppercase font-bold text-slate-400">Harga Jual</div>
                                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-white mt-0.5">
                                    {{ selectedProfile.is_customer_free ? 'Rp 0' : 'Rp ' + formatPrice(selectedProfile.price) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[10px] uppercase font-bold text-indigo-500">Margin Reseller</div>
                                <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 mt-0.5">
                                    {{ selectedProfile.is_customer_free ? 'Rp 0' : 'Rp ' + formatPrice(selectedProfile.fee_reseller) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[10px] uppercase font-bold text-emerald-600">Modal Reseller</div>
                                <div class="text-sm sm:text-base font-black text-emerald-700 dark:text-emerald-300 mt-0.5">
                                    {{ selectedProfile.is_customer_free ? 'Rp 0' : 'Rp ' + formatPrice(Math.max(0, selectedProfile.price - (selectedProfile.fee_reseller || 0))) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FreeRADIUS Attributes Inspection -->
                    <div class="border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden shadow-sm">
                        <div class="bg-slate-100 dark:bg-slate-900/70 px-4 py-2.5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                Atribut FreeRADIUS & MikroTik (Group: vcp_{{ selectedProfile.id }})
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono">RFC 2865 & Mikrotik VSA</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700 text-xs font-mono">
                            <div class="p-3 flex justify-between items-center bg-white dark:bg-slate-800">
                                <span class="text-slate-500">Simultaneous-Use (Check)</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedProfile.is_customer_free ? 1 : selectedProfile.shared_users }} Device</span>
                            </div>
                            <div class="p-3 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                                <span class="text-slate-500">Port-Limit (Reply MikroTik)</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedProfile.is_customer_free ? 1 : selectedProfile.shared_users }}</span>
                            </div>
                            <div class="p-3 flex justify-between items-center bg-white dark:bg-slate-800">
                                <span class="text-slate-500">Mikrotik-Rate-Limit (Reply)</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ selectedProfile.limit_rate || 'Unlimited' }}</span>
                            </div>
                            <div class="p-3 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                                <span class="text-slate-500">Session-Timeout & Max-All-Session</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ selectedProfile.is_customer_free ? 'Mengikuti Pelanggan' : humanizeDuration(selectedProfile.duration) }}
                                </span>
                            </div>
                            <div class="p-3 flex justify-between items-center bg-white dark:bg-slate-800">
                                <span class="text-slate-500">Acct-Interim-Interval</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">60 Detik</span>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Statistics for this Profile -->
                    <div class="bg-indigo-50/60 dark:bg-indigo-950/30 rounded-2xl p-4 border border-indigo-100 dark:border-indigo-800/60 space-y-2">
                        <div class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider">
                            Akumulasi Voucher Profil Ini
                        </div>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Total Dibuat</div>
                                <div class="text-base font-black text-slate-800 dark:text-white mt-0.5">{{ selectedProfile.vouchers_count || 0 }}</div>
                            </div>
                            <div class="bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                                <div class="text-[10px] text-emerald-500 uppercase font-bold">Tersedia</div>
                                <div class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ selectedProfile.available_vouchers_count || 0 }}</div>
                            </div>
                            <div class="bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                                <div class="text-[10px] text-blue-500 uppercase font-bold">Digunakan</div>
                                <div class="text-base font-black text-blue-600 dark:text-blue-400 mt-0.5">{{ selectedProfile.used_vouchers_count || 0 }}</div>
                            </div>
                            <div class="bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                                <div class="text-[10px] text-rose-500 uppercase font-bold">Expired</div>
                                <div class="text-base font-black text-rose-600 dark:text-rose-400 mt-0.5">{{ selectedProfile.expired_vouchers_count || 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 flex justify-between items-center gap-3">
                    <button 
                        @click="showDetailModal = false" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    >
                        Tutup
                    </button>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="syncSingleProfile(selectedProfile)" 
                            class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-xl text-sm font-bold transition-colors flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Sinkron RADIUS</span>
                        </button>
                        <button 
                            @click="showDetailModal = false; openEditModal(selectedProfile)" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition-colors"
                        >
                            Edit Profil
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL BURST RATE INSPECTION -->
        <!-- ========================================================================= -->
        <div v-if="showBurstModal && selectedBurstProfile" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm p-3 sm:p-4 animate-fade-in">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col border border-slate-200 dark:border-slate-700 animate-scale-up">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-amber-50/70 dark:bg-amber-950/30">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">
                                Detail Burst Limit MikroTik
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Profil: {{ selectedBurstProfile.name }}</p>
                        </div>
                    </div>
                    <button @click="showBurstModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4 text-xs font-mono">
                    <div class="p-3 bg-slate-900 text-green-400 rounded-xl break-all">
                        {{ selectedBurstProfile.limit_rate }}
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-700 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="p-3 flex justify-between items-center">
                            <span class="text-slate-500">1. Kecepatan Utama (Max Limit)</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ parseRate(selectedBurstProfile.limit_rate).main }}</span>
                        </div>
                        <div class="p-3 flex justify-between items-center bg-amber-50/50 dark:bg-amber-950/20">
                            <span class="text-amber-800 dark:text-amber-300 font-bold">2. Kecepatan Burst (Burst Limit)</span>
                            <span class="font-black text-amber-600 dark:text-amber-400">{{ parseRate(selectedBurstProfile.limit_rate).burst }}</span>
                        </div>
                        <div class="p-3 flex justify-between items-center">
                            <span class="text-slate-500">3. Pemicu Burst (Burst Threshold)</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ parseRate(selectedBurstProfile.limit_rate).threshold }}</span>
                        </div>
                        <div class="p-3 flex justify-between items-center">
                            <span class="text-slate-500">4. Durasi Burst (Burst Time)</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ parseRate(selectedBurstProfile.limit_rate).time }} Detik</span>
                        </div>
                        <div class="p-3 flex justify-between items-center">
                            <span class="text-slate-500">5. Prioritas Antrean (Priority)</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">Level {{ parseRate(selectedBurstProfile.limit_rate).priority }}</span>
                        </div>
                        <div class="p-3 flex justify-between items-center">
                            <span class="text-slate-500">6. Garansi Kecepatan (Limit At)</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ parseRate(selectedBurstProfile.limit_rate).min }}</span>
                        </div>
                    </div>

                    <p class="text-[11px] font-sans text-slate-500 dark:text-slate-400">
                        💡 Fitur burst memungkinkan voucher mendapatkan lonjakan kecepatan sesaat hingga <strong>{{ parseRate(selectedBurstProfile.limit_rate).burst }}</strong> selama <strong>{{ parseRate(selectedBurstProfile.limit_rate).time }} detik</strong> pertama saat membuka website atau aplikasi sebelum diturunkan ke kecepatan normal <strong>{{ parseRate(selectedBurstProfile.limit_rate).main }}</strong>.
                    </p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 flex justify-end">
                    <button 
                        @click="showBurstModal = false" 
                        class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-700 transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import LimitRateCalculator from '@/Components/LimitRateCalculator.vue';

const props = defineProps({
    profiles: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    }
});

const formatPrice = (price) => {
    return parseFloat(price || 0).toLocaleString('id-ID');
};

// Computed Stats with fallback
const computedStats = computed(() => {
    return {
        total_profiles: props.stats?.total_profiles ?? props.profiles.length,
        paid_profiles: props.stats?.paid_profiles ?? props.profiles.filter(p => !p.is_customer_free).length,
        free_profiles: props.stats?.free_profiles ?? props.profiles.filter(p => p.is_customer_free).length,
        total_vouchers: props.stats?.total_vouchers ?? props.profiles.reduce((acc, p) => acc + (p.vouchers_count || 0), 0),
        total_available: props.stats?.total_available ?? props.profiles.reduce((acc, p) => acc + (p.available_vouchers_count || 0), 0),
        total_used: props.stats?.total_used ?? props.profiles.reduce((acc, p) => acc + (p.used_vouchers_count || 0), 0),
        total_expired: props.stats?.total_expired ?? props.profiles.reduce((acc, p) => acc + (p.expired_vouchers_count || 0), 0),
    };
});

// Search & Filter
const searchQuery = ref('');
const filterType = ref('');
const filterShared = ref('');

const resetFilter = () => {
    searchQuery.value = '';
    filterType.value = '';
    filterShared.value = '';
};

const filteredProfiles = computed(() => {
    return props.profiles.filter((p) => {
        // Filter Type
        if (filterType.value === 'paid' && p.is_customer_free) return false;
        if (filterType.value === 'free' && !p.is_customer_free) return false;

        // Filter Shared Users
        if (filterShared.value === '1' && (p.shared_users > 1)) return false;
        if (filterShared.value === 'multi' && (p.shared_users <= 1)) return false;

        // Search Query
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchName = (p.name || '').toLowerCase().includes(q);
            const matchDesc = (p.description || '').toLowerCase().includes(q);
            const matchDuration = (p.duration || '').toLowerCase().includes(q);
            const matchRate = (p.limit_rate || '').toLowerCase().includes(q);
            const matchPrice = String(p.price || '').includes(q);
            return matchName || matchDesc || matchDuration || matchRate || matchPrice;
        }

        return true;
    });
});

// Parse Rate Limit Helper (Intelligent Burst Breakdown)
const parseRate = (rate) => {
    if (!rate) return { main: 'Unlimited', hasBurst: false, burst: null, full: 'Unlimited' };
    const parts = rate.trim().split(/\s+/);
    if (parts.length >= 6) {
        return {
            main: parts[0],
            hasBurst: true,
            burst: parts[1],
            threshold: parts[2],
            time: parts[3],
            priority: parts[4],
            min: parts[5],
            full: rate
        };
    }
    return { main: parts[0] || rate, hasBurst: false, burst: null, full: rate };
};

// Humanize Duration
const humanizeDuration = (duration) => {
    if (!duration) return '-';
    if (duration === 'Mengikuti Pelanggan') return 'Masa Aktif Pelanggan';
    const raw = String(duration).toLowerCase().trim();
    const match = raw.match(/^(\d+)\s*([wdhms])$/);
    if (!match) return raw;
    const val = parseInt(match[1]);
    const unit = match[2];
    const map = {
        'm': `${val} Menit`,
        'h': `${val} Jam`,
        'd': `${val} Hari`,
        'w': `${val} Minggu`,
        's': `${val} Detik`,
    };
    return map[unit] || raw;
};

// Presets
const durationOptions = [
    { value: '30m', label: '30 Menit' },
    { value: '1h', label: '1 Jam' },
    { value: '2h', label: '2 Jam' },
    { value: '3h', label: '3 Jam' },
    { value: '5h', label: '5 Jam' },
    { value: '6h', label: '6 Jam' },
    { value: '8h', label: '8 Jam' },
    { value: '10h', label: '10 Jam' },
    { value: '12h', label: '12 Jam' },
    { value: '1d', label: '1 Hari (24 Jam)' },
    { value: '2d', label: '2 Hari' },
    { value: '3d', label: '3 Hari' },
    { value: '7d', label: '7 Hari (1 Minggu)' },
    { value: '15d', label: '15 Hari' },
    { value: '30d', label: '30 Hari (1 Bulan)' },
];

const customDuration = ref(false);
const durationChoice = computed({
    get: () => {
        if (customDuration.value) return '__custom';
        if (!form.duration) return '';
        return durationOptions.some(d => d.value === form.duration) ? form.duration : '__custom';
    },
    set: (val) => {
        if (val === '__custom') {
            customDuration.value = true;
            if (durationOptions.some(d => d.value === form.duration)) form.duration = '';
        } else {
            customDuration.value = false;
            form.duration = val;
        }
    },
});

const parsedDurationHuman = computed(() => {
    if (form.is_customer_free) return 'Mengikuti status aktif pelanggan';
    if (!form.duration) return '-';
    const raw = String(form.duration).toLowerCase().trim();
    const match = raw.match(/^(\d+)\s*([wdhms])$/);
    if (!match) return `${raw}`;
    const val = parseInt(match[1]);
    const unit = match[2];
    const map = {
        'm': `${val} Menit (${val * 60} Detik)`,
        'h': `${val} Jam (${(val * 3600).toLocaleString('id-ID')} Detik)`,
        'd': `${val} Hari (${(val * 86400).toLocaleString('id-ID')} Detik)`,
        'w': `${val} Minggu (${(val * 604800).toLocaleString('id-ID')} Detik)`,
        's': `${val} Detik`,
    };
    return map[unit] || raw;
});

// Modals State
const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);
const modalBodyRef = ref(null);

const showDetailModal = ref(false);
const selectedProfile = ref(null);

const showBurstModal = ref(false);
const selectedBurstProfile = ref(null);

const syncingAll = ref(false);

const form = useForm({
    name: '',
    price: 0,
    fee_admin: 0,
    fee_reseller: 0,
    fee_partner: 0,
    duration: '',
    limit_rate: '',
    shared_users: 1,
    is_customer_free: false,
    description: '',
});

const resellerCost = computed(() => {
    if (form.is_customer_free) return 0;
    const price = parseFloat(form.price || 0);
    const fee = parseFloat(form.fee_reseller || 0);
    return Math.max(0, price - fee);
});

const isFeeExceeded = computed(() => {
    if (form.is_customer_free) return false;
    const price = parseFloat(form.price || 0);
    const fee = parseFloat(form.fee_reseller || 0);
    return fee > price;
});

const onToggleCustomerFree = () => {
    if (form.is_customer_free) {
        form.price = 0;
        form.duration = 'Mengikuti Pelanggan';
        form.shared_users = 1;
        form.fee_reseller = 0;
        form.fee_admin = 0;
        form.fee_partner = 0;
    } else {
        if (form.duration === 'Mengikuti Pelanggan') form.duration = '1d';
    }
};

const openCreateModal = () => {
    editMode.value = false;
    editId.value = null;
    customDuration.value = false;
    form.reset();
    form.clearErrors();
    form.shared_users = 1;
    form.price = 0;
    form.fee_reseller = 0;
    form.fee_admin = 0;
    form.fee_partner = 0;
    showModal.value = true;
    nextTick(() => {
        if (modalBodyRef.value) {
            modalBodyRef.value.scrollTop = 0;
        }
    });
};

const openEditModal = (profile) => {
    editMode.value = true;
    editId.value = profile.id;
    customDuration.value = false;
    form.clearErrors();
    form.name = profile.name;
    form.price = parseFloat(profile.price || 0);
    form.fee_admin = parseFloat(profile.fee_admin || 0);
    form.fee_reseller = parseFloat(profile.fee_reseller || 0);
    form.fee_partner = parseFloat(profile.fee_partner || 0);
    form.duration = profile.duration;
    form.limit_rate = profile.limit_rate;
    form.shared_users = profile.shared_users || 1;
    form.is_customer_free = !!profile.is_customer_free;
    form.description = profile.description;
    showModal.value = true;
    nextTick(() => {
        if (modalBodyRef.value) {
            modalBodyRef.value.scrollTop = 0;
        }
    });
};

const openDetailModal = (profile) => {
    selectedProfile.value = profile;
    showDetailModal.value = true;
};

const openBurstModal = (profile) => {
    selectedBurstProfile.value = profile;
    showBurstModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const triggerSubmit = () => {
    if (!form.name || form.name.trim() === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Nama Profil Wajib Diisi',
            text: 'Silakan masukkan nama profil voucher.',
        });
        return;
    }
    if (!form.is_customer_free && (!form.duration || form.duration.trim() === '')) {
        Swal.fire({
            icon: 'warning',
            title: 'Durasi Sesi Wajib Dipilih',
            text: 'Silakan pilih durasi sesi MikroTik untuk profil voucher ini.',
        });
        return;
    }
    if (isFeeExceeded.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Fee Reseller Melebihi Harga Jual',
            text: 'Fee reseller tidak boleh melebihi harga jual voucher.',
        });
        return;
    }
    submitForm();
};

const submitForm = () => {
    if (isFeeExceeded.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Fee Reseller Melebihi Harga Jual',
            text: 'Fee reseller tidak boleh melebihi harga jual voucher.',
        });
        return;
    }

    if (editMode.value) {
        form.post(route('vouchers.profiles.update.post', editId.value), {
            onSuccess: (page) => {
                if (page.props.flash?.error) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: page.props.flash.error });
                } else {
                    closeModal();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: page.props.flash?.success || 'Profil Voucher diperbarui dan disinkronkan ke RADIUS.' });
                }
            }
        });
    } else {
        form.post(route('vouchers.profiles.store'), {
            onSuccess: (page) => {
                if (page.props.flash?.error) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: page.props.flash.error });
                } else {
                    closeModal();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: page.props.flash?.success || 'Profil Voucher ditambahkan dan disinkronkan ke RADIUS.' });
                }
            }
        });
    }
};

// Sync Single Profile to RADIUS
const syncSingleProfile = (profile) => {
    Swal.fire({
        title: 'Sinkronkan Profil ke RADIUS?',
        text: `Atribut grup FreeRADIUS (vcp_${profile.id}) untuk profil "${profile.name}" akan diperbarui di database FreeRADIUS.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Sinkronkan!'
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('vouchers.profiles.sync', profile.id), {}, {
                onSuccess: (page) => {
                    if (page.props.flash?.error) {
                        Swal.fire('Gagal', page.props.flash.error, 'error');
                    } else {
                        Swal.fire('Tersinkronkan!', page.props.flash?.success || 'Profil berhasil disinkronkan ke FreeRADIUS.', 'success');
                    }
                }
            });
        }
    });
};

// Sync All Profiles to RADIUS
const syncAllProfiles = () => {
    Swal.fire({
        title: 'Sinkronkan Semua Profil ke RADIUS?',
        text: 'Seluruh konfigurasi grup profil voucher akan ditulis ulang ke FreeRADIUS (radgroupreply & radgroupcheck).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Sinkronkan Semua!'
    }).then((res) => {
        if (res.isConfirmed) {
            syncingAll.value = true;
            router.post(route('vouchers.profiles.sync-all'), {}, {
                onFinish: () => {
                    syncingAll.value = false;
                },
                onSuccess: (page) => {
                    if (page.props.flash?.error) {
                        Swal.fire('Gagal', page.props.flash.error, 'error');
                    } else {
                        Swal.fire('Berhasil!', page.props.flash?.success || 'Semua profil berhasil disinkronkan ke RADIUS.', 'success');
                    }
                }
            });
        }
    });
};

// Delete Profile
const deleteProfile = (profile) => {
    if (profile.vouchers_count && profile.vouchers_count > 0) {
        Swal.fire({
            icon: 'info',
            title: 'Profil Sedang Digunakan',
            text: `Profil "${profile.name}" tidak dapat dihapus karena masih digunakan oleh ${profile.vouchers_count} data voucher aktif/terjual.`,
        });
        return;
    }

    Swal.fire({
        title: 'Hapus Profil?',
        text: `Anda yakin ingin menghapus profil "${profile.name}"? Atribut RADIUS (vcp_${profile.id}) akan otomatis dibersihkan dari server.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.profiles.destroy.post', profile.id), {}, {
                onSuccess: (page) => {
                    if (page.props.flash?.error) {
                        Swal.fire('Gagal', page.props.flash.error, 'error');
                    } else {
                        Swal.fire('Terhapus!', page.props.flash?.success || 'Profil berhasil dihapus dari sistem.', 'success');
                    }
                }
            });
        }
    });
};
</script>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleUp {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.animate-fade-in {
    animation: fadeIn 0.18s ease-out forwards;
}
.animate-scale-up {
    animation: scaleUp 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
