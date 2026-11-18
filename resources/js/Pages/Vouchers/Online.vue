<template>
    <component :is="layoutComponent" title="Monitoring Sesi Voucher" subtitle="Pantau aktivitas voucher online realtime dan riwayat sesi voucher offline">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Monitoring Sesi Voucher</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">Pantau aktivitas voucher online realtime dan riwayat sesi voucher offline</p>
        </div>

        <!-- EXECUTIVE KPI STATS CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 mb-6">
            <!-- Card 1: Voucher Online -->
            <div 
                @click="switchTab('online')" 
                :class="activeTab === 'online' ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30' : 'border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Voucher Online</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-emerald-900 dark:text-emerald-100 tracking-tight flex items-center gap-2">
                        <span>{{ Number(stats?.online_count || 0).toLocaleString('id-ID') }}</span>
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                    </div>
                    <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold mt-0.5">
                        Sesi aktif terhubung
                    </div>
                </div>
            </div>

            <!-- Card 2: Voucher Offline -->
            <div 
                @click="switchTab('offline')" 
                :class="activeTab === 'offline' ? 'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/30' : 'border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Voucher Offline</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-indigo-900 dark:text-indigo-100 tracking-tight">
                        {{ Number(stats?.offline_count || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                        Riwayat sesi terputus
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Sesi Tercatat -->
            <div class="border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Sesi</span>
                    <div class="w-8 h-8 rounded-xl bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-gray-900 dark:text-slate-100 tracking-tight">
                        {{ Number(stats?.total_count || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 font-medium mt-0.5">
                        Sesi online & riwayat
                    </div>
                </div>
            </div>

            <!-- Card 4: Total Download -->
            <div class="border border-blue-200 dark:border-blue-900/60 bg-gradient-to-br from-blue-50/50 via-white to-sky-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Total Unduhan</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black text-blue-950 dark:text-blue-100 tracking-tight">
                        ↓ {{ formatBytes(stats?.total_download || 0) }}
                    </div>
                    <div class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold mt-0.5">
                        Akumulasi download
                    </div>
                </div>
            </div>

            <!-- Card 5: Total Upload -->
            <div class="border border-amber-200 dark:border-amber-900/60 bg-gradient-to-br from-amber-50/50 via-white to-orange-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-amber-950/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between col-span-2 sm:col-span-1 lg:col-span-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-amber-800 dark:text-amber-400 uppercase tracking-wider">Total Unggahan</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black text-amber-950 dark:text-amber-100 tracking-tight">
                        ↑ {{ formatBytes(stats?.total_upload || 0) }}
                    </div>
                    <div class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold mt-0.5">
                        Akumulasi upload
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS NAVIGATOR -->
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-slate-800 mb-6 bg-white dark:bg-slate-900 px-2 rounded-t-2xl">
            <button 
                @click="switchTab('online')"
                :class="activeTab === 'online' ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400 font-bold bg-emerald-50/50 dark:bg-emerald-950/30' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium'"
                class="flex items-center gap-2.5 py-3.5 px-5 border-b-2 text-sm transition-all rounded-t-xl"
            >
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span>Voucher Online</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold transition-colors" :class="activeTab === 'online' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400'">
                    {{ Number(stats?.online_count || 0).toLocaleString('id-ID') }}
                </span>
            </button>

            <button 
                @click="switchTab('offline')"
                :class="activeTab === 'offline' ? 'border-indigo-600 text-indigo-700 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium'"
                class="flex items-center gap-2.5 py-3.5 px-5 border-b-2 text-sm transition-all rounded-t-xl"
            >
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Voucher Offline</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold transition-colors" :class="activeTab === 'offline' ? 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400'">
                    {{ Number(stats?.offline_count || 0).toLocaleString('id-ID') }}
                </span>
            </button>
        </div>

        <!-- FILTER & ACTION BAR -->
        <div class="mb-6 flex flex-col xl:flex-row justify-between items-stretch xl:items-center gap-3">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[220px]">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari kode, user, IP, MAC, reseller, area, pembeli..." 
                        class="w-full pl-10 pr-9 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-2xs"
                        @keyup.enter="doFilter"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <button 
                        v-if="search" 
                        @click="search = ''; doFilter()" 
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Profil Paket Filter -->
                <select v-model="profileFilter" @change="doFilter" class="bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[200px]">
                    <option value="">Semua Profil Paket</option>
                    <option v-for="prof in profiles" :key="prof.id" :value="prof.id">
                        {{ prof.name }} ({{ prof.duration || 'Hotspot' }})
                    </option>
                </select>

                <!-- Reseller Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="resellerFilter" @change="doFilter" class="bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Pemilik / Reseller</option>
                    <option value="system">🏢 Kantor / System</option>
                    <option v-for="res in resellers" :key="res.id" :value="res.id">
                        👤 {{ res.name }}
                    </option>
                </select>

                <!-- Area / Wilayah Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="areaFilter" @change="doFilter" class="bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Area / Wilayah</option>
                    <option v-for="area in areas" :key="area.id" :value="area.id">
                        📍 {{ area.name }}
                    </option>
                </select>

                <!-- Active Filter Pill & Reset Button -->
                <div v-if="hasActiveFilter" class="flex items-center gap-1.5">
                    <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-bold">
                        {{ activeFilterCount }} Filter Aktif
                    </span>
                    <button 
                        @click="resetFilter" 
                        class="px-2.5 py-1 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-xl text-xs font-semibold flex items-center gap-1 transition-colors shadow-2xs"
                        title="Reset Filter"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
            
            <!-- Actions Right: Export, Print, Auto-refresh toggle, Manual refresh -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <!-- Export Excel -->
                <button 
                    @click="exportCsv" 
                    class="px-3.5 py-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-200 font-semibold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors"
                    title="Export data sesi ke file spreadsheet Excel/CSV"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Export Excel</span>
                </button>

                <!-- Print Daftar -->
                <button 
                    @click="openPrintModal" 
                    class="px-3.5 py-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-200 font-semibold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors"
                    title="Cetak lembar laporan monitoring sesi voucher"
                >
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print Daftar</span>
                </button>

                <!-- Auto-Refresh Toggle Pill -->
                <button 
                    @click="toggleAutoRefresh" 
                    class="px-3 py-2 border rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                    :class="autoRefreshActive ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-gray-500 dark:text-slate-400'"
                    :title="autoRefreshActive ? 'Klik untuk jeda auto-refresh (30 detik)' : 'Klik untuk mengaktifkan auto-refresh otomatis'"
                >
                    <span class="relative flex h-2 w-2">
                        <span v-if="autoRefreshActive" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2" :class="autoRefreshActive ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                    </span>
                    <span>{{ autoRefreshActive ? 'Auto: ON (30s)' : 'Auto: PAUSE' }}</span>
                </button>

                <!-- Refresh Button -->
                <button 
                    id="btn-refresh-online" 
                    @click="refresh" 
                    class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors"
                >
                    <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>

        <!-- TAB CONTENT: 1. VOUCHER ONLINE TABLE -->
        <div v-if="activeTab === 'online'" class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-200 dark:border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                    <thead class="bg-gray-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider w-12">#</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Kode / User</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Profil Paket</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Pemilik / Area</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">IP / MAC Address</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Uptime & Login</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Traffic</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                        <tr v-for="(row, idx) in paginatedOnlineData.data" :key="row.id" class="bg-emerald-50/75 hover:bg-emerald-100/80 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40 border-l-4 border-l-emerald-500 transition-colors">
                            <td class="px-4 py-3.5 text-center text-xs text-gray-400 dark:text-slate-500 font-mono">
                                {{ (paginatedOnlineData.current_page - 1) * perPage + idx + 1 }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="font-mono font-bold text-indigo-700 dark:text-indigo-400 text-sm tracking-wide bg-indigo-50/70 dark:bg-indigo-950/60 px-2.5 py-1 rounded-lg border border-indigo-200/80 dark:border-indigo-800 flex items-center gap-1.5">
                                        <span>{{ row.code }}</span>
                                        <button 
                                            @click.stop="copyText(row.code, 'Kode Voucher')" 
                                            class="text-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors"
                                            title="Salin Kode Voucher"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                    <div class="flex flex-col">
                                        <div v-if="row.username && row.username !== row.code" class="text-[11px] text-gray-500 dark:text-slate-400 font-mono">User: {{ row.username }}</div>
                                        <div v-if="row.buyer_name" class="text-[10px] text-gray-500 dark:text-slate-400 flex items-center gap-1">
                                            <span>👤 {{ row.buyer_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-0.5">
                                    <span class="inline-flex px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded text-xs font-semibold">
                                        {{ row.profile || '-' }}
                                    </span>
                                    <div class="text-[11px] text-gray-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span v-if="row.profile_duration">Durasi: {{ row.profile_duration }}</span>
                                        <span v-if="row.profile_price" class="font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ Number(row.profile_price).toLocaleString('id-ID') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-gray-900 dark:text-slate-100">{{ row.reseller }}</span>
                                        <span v-if="row.reseller_type === 'system'" class="text-[9px] font-bold px-1.5 py-0.2 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 rounded border border-gray-200 dark:border-slate-700">Pusat</span>
                                        <span v-else-if="row.reseller_type === 'mitra_user'" class="text-[9px] font-bold px-1.5 py-0.2 bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 rounded border border-purple-200 dark:border-purple-800">Mitra User</span>
                                    </div>
                                    <span v-if="row.area && row.area !== '-'" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                        📍 {{ row.area }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs text-gray-700 dark:text-slate-300">
                                <div class="font-bold text-gray-900 dark:text-slate-100">{{ row.ip_address || '-' }}</div>
                                <div class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">{{ row.mac_address || '-' }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs">
                                <div class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>{{ formatUptime(row.uptime) }}</span>
                                </div>
                                <div class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">{{ formatDate(row.login_time) }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs">
                                <div class="text-blue-600 dark:text-blue-400 font-semibold">↓ {{ formatBytes(row.download) }}</div>
                                <div class="text-amber-600 dark:text-amber-400 font-semibold">↑ {{ formatBytes(row.upload) }}</div>
                            </td>
                            <!-- Dedicated Action Column -->
                            <td class="px-6 py-3.5 whitespace-nowrap text-center text-xs">
                                <div class="inline-flex items-center justify-center gap-1.5">
                                    <!-- Kick Button -->
                                    <button
                                        v-if="can('voucher_monitoring_btn_kick')"
                                        :id="`btn-kick-${row.id}`"
                                        @click="kickSession(row)"
                                        :disabled="kicking === row.username || !row.is_active"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-600 hover:text-white dark:bg-rose-950/50 dark:border-rose-800 dark:text-rose-300 transition-colors disabled:opacity-50 shadow-2xs"
                                        title="Putus / Disconnect sesi aktif dari MikroTik & RADIUS"
                                    >
                                        <svg class="w-3.5 h-3.5" :class="{'animate-spin': kicking === row.username}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span>{{ kicking === row.username ? '...' : 'Kick' }}</span>
                                    </button>

                                    <!-- Detail / View Button -->
                                    <button 
                                        @click="openDetailModal(row)" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-gray-700 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 dark:text-slate-200 transition-colors shadow-2xs"
                                        title="Lihat detail lengkap voucher dan riwayat sesi"
                                    >
                                        <svg class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Detail</span>
                                    </button>

                                    <!-- Toggle Status Button -->
                                    <button
                                        v-if="row.voucher_id && can('voucher_monitoring_btn_toggle')"
                                        :id="`btn-toggle-${row.id}`"
                                        @click="toggleStatus(row)"
                                        :disabled="toggling === row.voucher_id"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors disabled:opacity-50 shadow-2xs"
                                        :class="row.is_active ? 'text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-500 hover:text-white border border-amber-200 dark:border-amber-800' : 'text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-500 hover:text-white border border-emerald-200 dark:border-emerald-800'"
                                        :title="row.is_active ? 'Nonaktifkan Voucher' : 'Aktifkan Voucher'"
                                    >
                                        <svg v-if="toggling === row.voucher_id" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>{{ row.is_active ? 'Disable' : 'Enable' }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paginatedOnlineData.data.length === 0">
                            <td colspan="8" class="text-center py-16">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center border border-gray-100 dark:border-slate-700">
                                        <svg class="w-7 h-7 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-slate-300 text-sm font-semibold">Tidak ada sesi voucher yang sedang online saat ini.</p>
                                    <p class="text-gray-400 dark:text-slate-500 text-xs">Voucher yang login di MikroTik akan otomatis tampil realtime di sini.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Online -->
            <div v-if="paginatedOnlineData.last_page > 1" class="px-6 py-3.5 bg-gray-50 dark:bg-slate-800/80 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-slate-400">
                    Menampilkan {{ (paginatedOnlineData.current_page - 1) * perPage + 1 }} - {{ Math.min(paginatedOnlineData.current_page * perPage, paginatedOnlineData.total) }} dari {{ paginatedOnlineData.total }} data
                </span>
                <div class="flex items-center gap-1">
                    <button 
                        @click="currentOnlinePage--" 
                        :disabled="currentOnlinePage <= 1"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span class="px-2 text-xs font-semibold text-gray-700 dark:text-slate-200">Hal {{ currentOnlinePage }} / {{ paginatedOnlineData.last_page }}</span>
                    <button 
                        @click="currentOnlinePage++" 
                        :disabled="currentOnlinePage >= paginatedOnlineData.last_page"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT: 2. VOUCHER OFFLINE TABLE -->
        <div v-else-if="activeTab === 'offline'" class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-200 dark:border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                    <thead class="bg-gray-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider w-12">#</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Kode / User</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Profil Paket</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Pemilik / Area</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">IP / MAC Terakhir</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Sesi Terakhir</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Durasi</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Traffic</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Alasan Putus</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                        <tr v-for="(row, idx) in paginatedOfflineData.data" :key="row.id" class="bg-rose-50/75 hover:bg-rose-100/80 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 border-l-4 border-l-rose-500 transition-colors">
                            <td class="px-4 py-3.5 text-center text-xs text-gray-400 dark:text-slate-500 font-mono">
                                {{ (paginatedOfflineData.current_page - 1) * perPage + idx + 1 }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="font-mono font-bold text-gray-900 dark:text-slate-100 text-sm tracking-wide bg-gray-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-slate-700 flex items-center gap-1.5">
                                        <span>{{ row.code }}</span>
                                        <button 
                                            @click.stop="copyText(row.code, 'Kode Voucher')" 
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors"
                                            title="Salin Kode Voucher"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                    <div class="flex flex-col">
                                        <div v-if="row.username && row.username !== row.code" class="text-[11px] text-gray-400 dark:text-slate-500 font-mono">User: {{ row.username }}</div>
                                        <div v-if="row.buyer_name" class="text-[10px] text-gray-500 dark:text-slate-400 flex items-center gap-1">
                                            <span>👤 {{ row.buyer_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-0.5">
                                    <span class="inline-flex px-2 py-0.5 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 rounded text-xs font-medium">
                                        {{ row.profile || '-' }}
                                    </span>
                                    <div class="text-[11px] text-gray-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span v-if="row.profile_duration">Durasi: {{ row.profile_duration }}</span>
                                        <span v-if="row.profile_price" class="font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ Number(row.profile_price).toLocaleString('id-ID') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-gray-900 dark:text-slate-100">{{ row.reseller }}</span>
                                        <span v-if="row.reseller_type === 'system'" class="text-[9px] font-bold px-1.5 py-0.2 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 rounded border border-gray-200 dark:border-slate-700">Pusat</span>
                                        <span v-else-if="row.reseller_type === 'mitra_user'" class="text-[9px] font-bold px-1.5 py-0.2 bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 rounded border border-purple-200 dark:border-purple-800">Mitra User</span>
                                    </div>
                                    <span v-if="row.area && row.area !== '-'" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                        📍 {{ row.area }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs text-gray-700 dark:text-slate-300">
                                <div class="font-bold text-gray-900 dark:text-slate-100">{{ row.ip_address || '-' }}</div>
                                <div class="text-[10px] text-gray-400 dark:text-slate-500">{{ row.mac_address || '-' }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs text-gray-600 dark:text-slate-300">
                                <div v-if="row.logout_time">Logout: {{ formatDate(row.logout_time) }}</div>
                                <div v-else-if="row.login_time">Login: {{ formatDate(row.login_time) }}</div>
                                <div v-else class="text-gray-400 dark:text-slate-500">-</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs font-mono">
                                <span v-if="row.total_time > 0" class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ formatUptime(row.total_time) }}</span>
                                <span v-else class="text-gray-400 dark:text-slate-500">-</span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs">
                                <div class="text-blue-600 dark:text-blue-400 font-medium">↓ {{ formatBytes(row.download) }}</div>
                                <div class="text-amber-600 dark:text-amber-400 font-medium">↑ {{ formatBytes(row.upload) }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span 
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                                    :class="{
                                        'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700': (row.terminate_cause || '').includes('User-Request') || (row.terminate_cause || '').includes('Logout'),
                                        'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800': (row.terminate_cause || '').includes('Lost-Carrier'),
                                        'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800': (row.terminate_cause || '').includes('Timeout') || (row.terminate_cause || '').includes('Expired'),
                                        'bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800': !(row.terminate_cause || '').includes('User-Request') && !(row.terminate_cause || '').includes('Lost')
                                    }"
                                >
                                    {{ row.terminate_cause || 'Selesai / Logout' }}
                                </span>
                            </td>
                            <!-- Dedicated Action Column -->
                            <td class="px-6 py-3.5 whitespace-nowrap text-center text-xs">
                                <div class="inline-flex items-center justify-center gap-1.5">
                                    <!-- Detail / View Button -->
                                    <button 
                                        @click="openDetailModal(row)" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-gray-700 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 dark:text-slate-200 transition-colors shadow-2xs"
                                        title="Lihat detail lengkap voucher dan riwayat sesi"
                                    >
                                        <svg class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Detail</span>
                                    </button>

                                    <!-- Toggle Status Button -->
                                    <button
                                        v-if="row.voucher_id && can('voucher_monitoring_btn_toggle')"
                                        :id="`btn-toggle-${row.id}`"
                                        @click="toggleStatus(row)"
                                        :disabled="toggling === row.voucher_id"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors disabled:opacity-50 shadow-2xs"
                                        :class="row.is_active ? 'text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-500 hover:text-white border border-amber-200 dark:border-amber-800' : 'text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-500 hover:text-white border border-emerald-200 dark:border-emerald-800'"
                                        :title="row.is_active ? 'Nonaktifkan Voucher' : 'Aktifkan Voucher'"
                                    >
                                        <svg v-if="toggling === row.voucher_id" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>{{ row.is_active ? 'Disable' : 'Enable' }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paginatedOfflineData.data.length === 0">
                            <td colspan="10" class="text-center py-16">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center border border-gray-100 dark:border-slate-700">
                                        <svg class="w-7 h-7 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-slate-300 text-sm font-semibold">Tidak ada riwayat voucher offline ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Offline -->
            <div v-if="paginatedOfflineData.last_page > 1" class="px-6 py-3.5 bg-gray-50 dark:bg-slate-800/80 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-slate-400">
                    Menampilkan {{ (paginatedOfflineData.current_page - 1) * perPage + 1 }} - {{ Math.min(paginatedOfflineData.current_page * perPage, paginatedOfflineData.total) }} dari {{ paginatedOfflineData.total }} data
                </span>
                <div class="flex items-center gap-1">
                    <button 
                        @click="currentOfflinePage--" 
                        :disabled="currentOfflinePage <= 1"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span class="px-2 text-xs font-semibold text-gray-700 dark:text-slate-200">Hal {{ currentOfflinePage }} / {{ paginatedOfflineData.last_page }}</span>
                    <button 
                        @click="currentOfflinePage++" 
                        :disabled="currentOfflinePage >= paginatedOfflineData.last_page"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- MODAL: DETAIL SESI VOUCHER -->
        <!-- ========================================================================================= -->
        <div v-if="showDetailModal && selectedDetailRow" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in duration-150">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between bg-gray-50/70 dark:bg-slate-800/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center" :class="selectedDetailRow.status === 'online' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="selectedDetailRow.status === 'online'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Detail Sesi Voucher</h3>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-500 dark:text-slate-400">Kode: <strong class="font-mono text-indigo-600 dark:text-indigo-400">{{ selectedDetailRow.code }}</strong></span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="selectedDetailRow.status === 'online' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-300'">
                                    <span v-if="selectedDetailRow.status === 'online'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>{{ selectedDetailRow.status }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Section 1: Informasi Voucher -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            <span>Informasi Voucher</span>
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-gray-50/70 dark:bg-slate-800/40 p-3.5 rounded-2xl border border-gray-200/70 dark:border-slate-800 text-xs">
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Kode Login</div>
                                <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400 text-sm mt-0.5">{{ selectedDetailRow.code }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Username</div>
                                <div class="font-mono font-semibold text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.username || selectedDetailRow.code }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Password</div>
                                <div class="font-mono font-semibold text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.password || '(Sama dg Kode)' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Profil Paket</div>
                                <div class="font-bold text-gray-900 dark:text-slate-100 mt-0.5">{{ selectedDetailRow.profile }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Batas Durasi</div>
                                <div class="font-medium text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.profile_duration || 'Unlimited' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Harga / Tarif</div>
                                <div class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">Rp {{ Number(selectedDetailRow.profile_price || 0).toLocaleString('id-ID') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Informasi Koneksi Sesi Realtime / Terakhir -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Informasi Koneksi Sesi</span>
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-gray-50/70 dark:bg-slate-800/40 p-3.5 rounded-2xl border border-gray-200/70 dark:border-slate-800 text-xs">
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">IP Address</div>
                                <div class="font-mono font-bold text-gray-900 dark:text-slate-100 mt-0.5">{{ selectedDetailRow.ip_address || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">MAC Address</div>
                                <div class="font-mono font-semibold text-gray-700 dark:text-slate-300 mt-0.5">{{ selectedDetailRow.mac_address || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">NAS / Router IP</div>
                                <div class="font-mono text-gray-700 dark:text-slate-300 mt-0.5">{{ selectedDetailRow.nas_ip || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Waktu Login</div>
                                <div class="text-gray-800 dark:text-slate-200 mt-0.5">{{ formatDateFull(selectedDetailRow.login_time) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Waktu Logout</div>
                                <div class="text-gray-800 dark:text-slate-200 mt-0.5">{{ formatDateFull(selectedDetailRow.logout_time) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Lama Uptime Sesi</div>
                                <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ formatUptime(selectedDetailRow.uptime || selectedDetailRow.total_time) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Total Download</div>
                                <div class="font-mono font-bold text-blue-600 dark:text-blue-400 mt-0.5">↓ {{ formatBytes(selectedDetailRow.download) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Total Upload</div>
                                <div class="font-mono font-bold text-amber-600 dark:text-amber-400 mt-0.5">↑ {{ formatBytes(selectedDetailRow.upload) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Alasan Putus (Terminate)</div>
                                <div class="font-semibold text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.terminate_cause || (selectedDetailRow.status === 'online' ? 'Masih Terhubung' : 'Logout') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Kepemilikan & Data Pembeli -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Kepemilikan Reseller & Data Pembeli</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50/70 dark:bg-slate-800/40 p-3.5 rounded-2xl border border-gray-200/70 dark:border-slate-800 text-xs">
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Pemilik / Reseller</div>
                                <div class="font-bold text-gray-900 dark:text-slate-100 mt-0.5 flex items-center gap-1.5">
                                    <span>{{ selectedDetailRow.reseller }}</span>
                                    <span v-if="selectedDetailRow.reseller_type === 'system'" class="text-[9px] px-1.5 py-0.5 bg-gray-200 dark:bg-slate-700 rounded text-gray-700 dark:text-slate-300 font-bold">Pusat</span>
                                    <span v-else-if="selectedDetailRow.reseller_type === 'mitra_user'" class="text-[9px] px-1.5 py-0.5 bg-purple-100 dark:bg-purple-950/60 rounded text-purple-700 dark:text-purple-300 font-bold">Mitra User</span>
                                </div>
                                <div v-if="selectedDetailRow.area" class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 font-medium">📍 {{ selectedDetailRow.area }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Data Pembeli / Pengguna</div>
                                <div v-if="selectedDetailRow.buyer_name || selectedDetailRow.buyer_phone" class="mt-0.5">
                                    <div class="font-bold text-gray-900 dark:text-slate-100">{{ selectedDetailRow.buyer_name || 'Tanpa Nama' }}</div>
                                    <div v-if="selectedDetailRow.buyer_phone" class="flex items-center gap-2 mt-1">
                                        <span class="font-mono text-gray-600 dark:text-slate-400">{{ selectedDetailRow.buyer_phone }}</span>
                                        <a 
                                            :href="`https://wa.me/${formatWaNumber(selectedDetailRow.buyer_phone)}`" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-500 text-white rounded text-[10px] font-bold hover:bg-emerald-600 transition-colors"
                                        >
                                            Chat WA
                                        </a>
                                    </div>
                                </div>
                                <div v-else class="text-gray-400 dark:text-slate-500 italic mt-0.5">
                                    Tidak ada data pembeli dicatat
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 border-t border-gray-200 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-800/50 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button 
                            @click="copyText(selectedDetailRow.code, 'Kode Voucher')" 
                            class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span>Salin Kode</span>
                        </button>

                        <button 
                            v-if="selectedDetailRow.status === 'online' && can('voucher_monitoring_btn_kick')"
                            @click="kickSession(selectedDetailRow); showDetailModal = false"
                            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Kick Sesi</span>
                        </button>
                    </div>

                    <button 
                        @click="showDetailModal = false" 
                        class="px-4 py-1.5 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-slate-200 rounded-xl text-xs font-bold transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- MODAL: PRINT PREVIEW LAPORAN SESI VOUCHER -->
        <!-- ========================================================================================= -->
        <div v-if="showPrintModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white text-gray-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-200 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Bar -->
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50 shrink-0">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Preview Cetak Laporan Sesi Voucher</h3>
                        <p class="text-xs text-gray-500">Dokumen siap cetak dengan format resmi VIZBILL</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="triggerPrint" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak Dokumen (Print)</span>
                        </button>
                        <button 
                            @click="showPrintModal = false" 
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs rounded-xl transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>

                <!-- Printable Paper Container -->
                <div class="p-8 overflow-y-auto flex-1 bg-white print:p-0 print:m-0" id="print-area">
                    <!-- Report Header -->
                    <div class="border-b-2 border-gray-800 pb-4 mb-4 flex justify-between items-start">
                        <div>
                            <h1 class="text-xl font-black text-gray-900 uppercase tracking-tight">LAPORAN MONITORING SESI VOUCHER</h1>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Dicetak pada: <strong>{{ printTimestamp }}</strong> | Kategori: <strong>Voucher {{ activeTab === 'online' ? 'Online' : 'Offline' }}</strong>
                            </p>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                Filter: {{ search ? `Pencarian "${search}"` : 'Semua Pencarian' }} | Profil: {{ activeProfileName }} | Reseller: {{ activeResellerName }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-base font-black text-indigo-700">VIZBILL</div>
                            <div class="text-[11px] text-gray-500">Smarter Voucher Monitoring</div>
                            <div class="text-[11px] font-semibold text-gray-700 mt-1">Total Data: {{ activeTableData.length }} Sesi</div>
                        </div>
                    </div>

                    <!-- Summary KPI Box -->
                    <div class="grid grid-cols-4 gap-3 mb-6 bg-gray-50 p-3 rounded-xl border border-gray-200 text-center">
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Total Sesi Dicetak</div>
                            <div class="text-base font-black text-gray-900 mt-0.5">{{ activeTableData.length }} Sesi</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Status Tab</div>
                            <div class="text-base font-black text-indigo-600 mt-0.5">{{ activeTab === 'online' ? 'Voucher Online' : 'Voucher Offline' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Total Download</div>
                            <div class="text-base font-black text-blue-600 mt-0.5">↓ {{ formatBytes(sumDownload) }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Total Upload</div>
                            <div class="text-base font-black text-amber-600 mt-0.5">↑ {{ formatBytes(sumUpload) }}</div>
                        </div>
                    </div>

                    <!-- Report Table -->
                    <table class="w-full text-left text-xs border border-gray-300 divide-y divide-gray-300">
                        <thead class="bg-gray-100 font-bold text-gray-700">
                            <tr>
                                <th class="p-2 border border-gray-300 text-center w-8">#</th>
                                <th class="p-2 border border-gray-300">KODE / USER</th>
                                <th class="p-2 border border-gray-300">PROFIL</th>
                                <th class="p-2 border border-gray-300">PEMILIK / AREA</th>
                                <th class="p-2 border border-gray-300">IP / MAC</th>
                                <th class="p-2 border border-gray-300">{{ activeTab === 'online' ? 'UPTIME' : 'DURASI' }}</th>
                                <th class="p-2 border border-gray-300">TRAFFIC</th>
                                <th class="p-2 border border-gray-300 text-center">{{ activeTab === 'online' ? 'STATUS' : 'ALASAN PUTUS' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(item, idx) in activeTableData" :key="item.id" class="hover:bg-gray-50">
                                <td class="p-2 border border-gray-300 text-center font-mono">{{ idx + 1 }}</td>
                                <td class="p-2 border border-gray-300 font-mono font-bold">{{ item.code }}</td>
                                <td class="p-2 border border-gray-300">{{ item.profile }}</td>
                                <td class="p-2 border border-gray-300">{{ item.reseller }} <span v-if="item.area && item.area !== '-'">({{ item.area }})</span></td>
                                <td class="p-2 border border-gray-300 font-mono text-[10px]">
                                    <div>{{ item.ip_address }}</div>
                                    <div class="text-gray-500">{{ item.mac_address }}</div>
                                </td>
                                <td class="p-2 border border-gray-300 font-mono text-[11px]">
                                    {{ formatUptime(activeTab === 'online' ? item.uptime : item.total_time) }}
                                </td>
                                <td class="p-2 border border-gray-300 font-mono text-[10px]">
                                    <div>↓ {{ formatBytes(item.download) }}</div>
                                    <div>↑ {{ formatBytes(item.upload) }}</div>
                                </td>
                                <td class="p-2 border border-gray-300 text-center text-[10px] font-semibold">
                                    <span v-if="activeTab === 'online'" class="text-emerald-700">ONLINE</span>
                                    <span v-else>{{ item.terminate_cause || 'Offline' }}</span>
                                </td>
                            </tr>
                            <tr v-if="activeTableData.length === 0">
                                <td colspan="8" class="p-6 text-center text-gray-500 italic">
                                    Tidak ada data sesi voucher untuk dicetak dengan filter saat ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Report Signatures -->
                    <div class="mt-8 pt-4 flex justify-between text-xs text-gray-600">
                        <div>
                            <p>Petugas Operator:</p>
                            <div class="h-16"></div>
                            <p class="font-bold text-gray-900 border-t border-gray-400 pt-1 inline-block min-w-[140px]">{{ page.props.auth?.user?.name || 'Administrator' }}</p>
                        </div>
                        <div class="text-right">
                            <p>Mengetahui, Supervisor:</p>
                            <div class="h-16"></div>
                            <p class="font-bold text-gray-900 border-t border-gray-400 pt-1 inline-block min-w-[140px]">( ................................ )</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </component>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';

const page = usePage();

const props = defineProps({
    tab: { type: String, default: 'online' },
    onlineUsers: { type: Array, default: () => [] },
    offlineUsers: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ online_count: 0, offline_count: 0, total_count: 0, total_download: 0, total_upload: 0 }) },
    profiles: { type: Array, default: () => [] },
    resellers: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    isResellerPortal: { type: Boolean, default: false },
});

const isClientArea = computed(() => {
    return page.props.auth?.user?.role === 'reseller' 
        || page.props.auth?.user?.role === 'customer' 
        || page.props.auth?.user?.login_portal === 'reseller'
        || Boolean(props.isResellerPortal);
});

const layoutComponent = computed(() => isClientArea.value ? ClientAreaLayout : AppLayout);

const can = (permission) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    const role = (user.role || '').toLowerCase();
    if (['admin', 'super admin', 'superadmin'].includes(role)) return true;
    const perms = Array.isArray(user.permissions) ? user.permissions : Object.values(user.permissions || {});
    return perms.includes(permission);
};

const activeTab = ref(props.tab || props.filters?.tab || 'online');
const search = ref(props.filters?.search || '');
const profileFilter = ref(props.filters?.profile_id || '');
const resellerFilter = ref(props.filters?.reseller_id || '');
const areaFilter = ref(props.filters?.area_id || '');

const refreshing = ref(false);
const kicking = ref(null);
const toggling = ref(null);

const perPage = 15;
const currentOnlinePage = ref(1);
const currentOfflinePage = ref(1);

// Modals State
const showDetailModal = ref(false);
const selectedDetailRow = ref(null);
const showPrintModal = ref(false);

// Active filters count
const hasActiveFilter = computed(() => {
    return Boolean(search.value || profileFilter.value || resellerFilter.value || areaFilter.value);
});

const activeFilterCount = computed(() => {
    let count = 0;
    if (search.value) count++;
    if (profileFilter.value) count++;
    if (resellerFilter.value) count++;
    if (areaFilter.value) count++;
    return count;
});

const switchTab = (tabName) => {
    activeTab.value = tabName;
    currentOnlinePage.value = 1;
    currentOfflinePage.value = 1;
    doFilter();
};

const doFilter = () => {
    currentOnlinePage.value = 1;
    currentOfflinePage.value = 1;
    router.get(route('vouchers.online'), {
        tab: activeTab.value,
        search: search.value,
        profile_id: profileFilter.value,
        reseller_id: resellerFilter.value,
        area_id: areaFilter.value,
    }, { preserveState: true, replace: true });
};

const resetFilter = () => {
    search.value = '';
    profileFilter.value = '';
    resellerFilter.value = '';
    areaFilter.value = '';
    currentOnlinePage.value = 1;
    currentOfflinePage.value = 1;
    doFilter();
};

// Client-side pagination & filter matching with safe boundary checks
const paginatedOnlineData = computed(() => {
    const list = props.onlineUsers || [];
    const lastPage = Math.max(1, Math.ceil(list.length / perPage));
    const safePage = Math.min(Math.max(1, currentOnlinePage.value), lastPage);
    const start = (safePage - 1) * perPage;
    return {
        data: list.slice(start, start + perPage),
        current_page: safePage,
        last_page: lastPage,
        total: list.length,
    };
});

const paginatedOfflineData = computed(() => {
    const list = props.offlineUsers || [];
    const lastPage = Math.max(1, Math.ceil(list.length / perPage));
    const safePage = Math.min(Math.max(1, currentOfflinePage.value), lastPage);
    const start = (safePage - 1) * perPage;
    return {
        data: list.slice(start, start + perPage),
        current_page: safePage,
        last_page: lastPage,
        total: list.length,
    };
});

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['onlineUsers', 'offlineUsers', 'stats'],
        onFinish: () => (refreshing.value = false),
    });
}

// Auto-refresh tiap 30 detik (bisa di-pause/toggle, otomatis pause saat modal terbuka)
const AUTO_REFRESH_MS = 30000;
const autoRefreshActive = ref(true);
let autoTimer = null;

const toggleAutoRefresh = () => {
    autoRefreshActive.value = !autoRefreshActive.value;
};

onMounted(() => {
    autoTimer = setInterval(() => {
        if (!autoRefreshActive.value || document.hidden || refreshing.value || kicking.value || toggling.value || showDetailModal.value || showPrintModal.value) return;
        router.reload({ only: ['onlineUsers', 'offlineUsers', 'stats'], preserveScroll: true, preserveState: true });
    }, AUTO_REFRESH_MS);
});
onUnmounted(() => {
    if (autoTimer) clearInterval(autoTimer);
});

function openDetailModal(row) {
    selectedDetailRow.value = row;
    showDetailModal.value = true;
}

function openPrintModal() {
    showPrintModal.value = true;
}

function triggerPrint() {
    window.print();
}

const printTimestamp = computed(() => {
    return new Date().toLocaleString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
});

const activeTableData = computed(() => {
    return activeTab.value === 'online' ? (props.onlineUsers || []) : (props.offlineUsers || []);
});

const sumDownload = computed(() => {
    return activeTableData.value.reduce((acc, item) => acc + (Number(item.download) || 0), 0);
});

const sumUpload = computed(() => {
    return activeTableData.value.reduce((acc, item) => acc + (Number(item.upload) || 0), 0);
});

const activeProfileName = computed(() => {
    if (!profileFilter.value) return 'Semua Profil';
    const found = props.profiles.find(p => String(p.id) === String(profileFilter.value));
    return found ? found.name : 'Profil Terpilih';
});

const activeResellerName = computed(() => {
    if (!resellerFilter.value) return 'Semua Reseller';
    if (resellerFilter.value === 'system') return 'Kantor / System';
    const found = props.resellers.find(r => String(r.id) === String(resellerFilter.value));
    return found ? found.name : 'Reseller Terpilih';
});

// Export CSV / Excel Function
function exportCsv() {
    const list = activeTableData.value;
    if (!list || list.length === 0) {
        Swal.fire('Info', 'Tidak ada data sesi untuk diexport.', 'info');
        return;
    }

    const headers = [
        'No',
        'Kode Voucher',
        'Username',
        'Profil Paket',
        'Durasi',
        'Harga (Rp)',
        'Pemilik / Reseller',
        'Area',
        'IP Address',
        'MAC Address',
        'NAS IP',
        'Waktu Login',
        'Waktu Logout',
        'Uptime (Detik)',
        'Download (Bytes)',
        'Upload (Bytes)',
        'Alasan Putus',
        'Status Sesi',
        'Nama Pembeli',
        'No WhatsApp'
    ];

    const rows = list.map((item, idx) => [
        idx + 1,
        item.code || '',
        item.username || '',
        item.profile || '',
        item.profile_duration || '',
        item.profile_price || 0,
        item.reseller || '',
        item.area || '',
        item.ip_address || '',
        item.mac_address || '',
        item.nas_ip || '',
        item.login_time || '',
        item.logout_time || '',
        item.uptime || item.total_time || 0,
        item.download || 0,
        item.upload || 0,
        item.terminate_cause || '',
        item.status || '',
        item.buyer_name || '',
        item.buyer_phone || ''
    ]);

    let csvContent = '\uFEFF'; // UTF-8 BOM
    csvContent += headers.map(h => `"${h.replace(/"/g, '""')}"`).join(',') + '\r\n';
    rows.forEach(row => {
        csvContent += row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',') + '\r\n';
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    const dateStr = new Date().toISOString().slice(0, 10);
    link.setAttribute('download', `monitoring_sesi_voucher_${activeTab.value}_${dateStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function copyText(text, label = 'Teks') {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `${label} berhasil disalin!`,
            showConfirmButton: false,
            timer: 2000
        });
    });
}

function formatWaNumber(phone) {
    if (!phone) return '';
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    return cleaned;
}

function toggleStatus(row) {
    const voucherId = row.voucher_id || row.id;
    if (!voucherId) return;
    const action = row.is_active ? 'Nonaktifkan' : 'Aktifkan';
    
    Swal.fire({
        title: `${action} Voucher?`,
        text: `Voucher ${row.code} akan di-${action.toLowerCase()}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Ya, ${action}!`,
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            toggling.value = voucherId;
            router.post(`/vouchers/${voucherId}/toggle-status`, {}, {
                preserveScroll: true,
                onFinish: () => (toggling.value = null),
                onSuccess: (page) => {
                    const flash = page.props?.flash;
                    if (flash?.error) {
                        Swal.fire('Gagal', flash.error, 'error');
                    } else {
                        Swal.fire('Berhasil', flash?.success || `Voucher ${row.code} berhasil di-${action.toLowerCase()}.`, 'success');
                    }
                }
            });
        }
    });
}

function kickSession(row) {
    if (!row.username && !row.code) return;
    Swal.fire({
        title: 'Kick Sesi Online?',
        text: `Putus koneksi aktif untuk voucher ${row.code} (${row.username})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Ya, Putus Sesi!',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            kicking.value = row.username;
            router.post('/radius/online-users/disconnect', { 
                username: row.username,
                code: row.code 
            }, {
                preserveScroll: true,
                onFinish: () => (kicking.value = null),
                onSuccess: (page) => {
                    const flash = page.props?.flash;
                    if (flash?.error) {
                        Swal.fire('Gagal', flash.error, 'error');
                    } else {
                        Swal.fire('Selesai', flash?.success || `Sesi ${row.code} berhasil diputus.`, 'success');
                        refresh();
                    }
                }
            });
        }
    });
}

function formatUptime(seconds) {
    if (!seconds || seconds <= 0) return '0s';
    const d = Math.floor(seconds / (3600 * 24));
    const h = Math.floor((seconds % (3600 * 24)) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    
    let parts = [];
    if (d > 0) parts.push(`${d}h`);
    if (h > 0) parts.push(`${h}j`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 || parts.length === 0) parts.push(`${s}d`);
    
    return parts.join(' ');
}

function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDate(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
}

function formatDateFull(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #print-area, #print-area * {
        visibility: visible;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
    }
}
</style>
