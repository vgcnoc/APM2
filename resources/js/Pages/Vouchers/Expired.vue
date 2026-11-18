<template>
    <component :is="layoutComponent" title="Voucher Expired" subtitle="Daftar voucher yang sudah habis masa aktifnya / kedaluwarsa">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Voucher Expired</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">Daftar voucher yang sudah habis masa aktifnya / kedaluwarsa</p>
        </div>

        <!-- EXECUTIVE KPI STATS CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 mb-6">
            <!-- Card 1: Total Expired -->
            <div class="border border-rose-200 dark:border-rose-900/60 bg-gradient-to-br from-rose-50/50 via-white to-red-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-rose-950/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider">Total Voucher Expired</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-rose-950 dark:text-rose-100 tracking-tight flex items-center gap-2">
                        <span>{{ Number(stats?.total_expired || expiredUsers.length || 0).toLocaleString('id-ID') }}</span>
                        <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">Voucher</span>
                    </div>
                    <div class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold mt-0.5">
                        Masa aktif telah habis
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Durasi Terpakai -->
            <div class="border border-indigo-200 dark:border-indigo-900/60 bg-gradient-to-br from-indigo-50/50 via-white to-purple-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Total Durasi Online</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-indigo-950 dark:text-indigo-100 tracking-tight">
                        {{ formatSecondsFull(stats?.total_time || totalDurationSeconds) }}
                    </div>
                    <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                        Akumulasi waktu pemakaian
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Traffic Terpakai -->
            <div class="border border-blue-200 dark:border-blue-900/60 bg-gradient-to-br from-blue-50/50 via-white to-sky-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Total Kuota Terpakai</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-blue-950 dark:text-blue-100 tracking-tight">
                        {{ formatBytes((stats?.total_download || 0) + (stats?.total_upload || 0) || totalTrafficBytes) }}
                    </div>
                    <div class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold mt-0.5">
                        ↓ {{ formatBytes(stats?.total_download || totalDownloadBytes) }} | ↑ {{ formatBytes(stats?.total_upload || totalUploadBytes) }}
                    </div>
                </div>
            </div>

            <!-- Card 4: Total Nilai Tarif -->
            <div class="border border-emerald-200 dark:border-emerald-900/60 bg-gradient-to-br from-emerald-50/50 via-white to-teal-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-emerald-950/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between col-span-2 sm:col-span-1 lg:col-span-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-wider">Total Omset Selesai</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-emerald-950 dark:text-emerald-100 tracking-tight">
                        Rp {{ Number(stats?.total_value || totalValueNominal).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold mt-0.5">
                        Nilai omset voucher habis
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTER & ACTION BAR -->
        <div class="mb-6 flex flex-col xl:flex-row justify-between items-stretch xl:items-center gap-3">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[220px]">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari kode, user, password, profil, reseller, area, pembeli..." 
                        class="w-full pl-10 pr-9 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-2xs"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <button 
                        v-if="search" 
                        @click="search = ''" 
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Profil Filter -->
                <select v-model="profileFilter" class="bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[200px]">
                    <option value="">Semua Profil</option>
                    <option v-for="prof in profiles" :key="prof.id" :value="prof.name">{{ prof.name }}</option>
                </select>

                <!-- Reseller Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="resellerFilter" class="bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Pemilik / Reseller</option>
                    <option value="Kantor / System">🏢 Kantor / System</option>
                    <option v-for="res in resellers" :key="res.id" :value="res.name">
                        👤 {{ res.name }}
                    </option>
                </select>

                <!-- Area / Wilayah Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="areaFilter" class="bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Area / Wilayah</option>
                    <option v-for="area in areas" :key="area.id" :value="area.name">
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

            <!-- Actions Right -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <!-- Bulk Delete Selected -->
                <button 
                    v-if="selectedVouchers.length > 0" 
                    @click="bulkDelete" 
                    class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5 transition-all animate-in fade-in"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus Terpilih ({{ selectedVouchers.length }})</span>
                </button>

                <!-- Purge All Expired Button -->
                <button 
                    v-if="filteredData.length > 0 && selectedVouchers.length === 0" 
                    @click="purgeAllExpired" 
                    class="px-3.5 py-2 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xl text-xs font-bold shadow-2xs flex items-center gap-1.5 transition-colors"
                    title="Bersihkan semua voucher expired dari sistem"
                >
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Bersihkan Semua</span>
                </button>

                <!-- Export Excel -->
                <button 
                    @click="exportCsv" 
                    class="px-3.5 py-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-200 font-semibold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors"
                    title="Export data voucher expired ke file spreadsheet Excel/CSV"
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
                    title="Cetak lembar laporan voucher expired"
                >
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print Daftar</span>
                </button>

                <!-- Refresh Button -->
                <button 
                    id="btn-refresh-expired" 
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

        <!-- TABLE SECTION -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-200 dark:border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                    <thead class="bg-gray-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="w-10 text-center px-3 py-3.5">
                                <input 
                                    type="checkbox" 
                                    class="rounded border-gray-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 shadow-2xs"
                                    :checked="isAllSelected" 
                                    @change="toggleSelectAll" 
                                />
                            </th>
                            <th class="px-3 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider w-12">#</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Kode / User</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Password</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Profil</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Pemilik / Area</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Terpakai Sejak</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Waktu Habis</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Penggunaan</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                        <tr v-for="(row, index) in paginatedData.data" :key="row.id" class="hover:bg-gray-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="w-10 text-center px-3 py-3.5">
                                <input 
                                    type="checkbox" 
                                    class="rounded border-gray-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 shadow-2xs"
                                    :value="row.id"
                                    v-model="selectedVouchers"
                                />
                            </td>
                            <td class="px-3 py-3.5 text-center text-xs text-gray-400 dark:text-slate-500 font-mono">
                                {{ (paginatedData.current_page - 1) * perPage + index + 1 }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
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
                                        <div v-if="row.username && row.username !== row.code" class="text-[11px] text-gray-400 dark:text-slate-500 font-mono">
                                            User: {{ row.username }}
                                        </div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span v-if="row.is_free" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                🎁 Gratis
                                            </span>
                                            <span v-else class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                💰 Berbayar
                                            </span>
                                            <span v-if="row.buyer_name" class="text-[10px] text-gray-500 dark:text-slate-400">
                                                👤 {{ row.buyer_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-700 dark:text-slate-300 font-mono">
                                {{ row.password || '(Sama dg Kode)' }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-bold text-xs text-gray-900 dark:text-slate-100">{{ row.profile || '-' }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                    <span v-if="row.duration">Durasi: {{ row.duration }}</span>
                                    <span v-if="row.price" class="font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ Number(row.price).toLocaleString('id-ID') }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
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
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-700 dark:text-slate-300">
                                <div v-if="row.first_login">{{ formatDate(row.first_login) }}</div>
                                <div v-else-if="row.used_at">{{ formatDate(row.used_at) }}</div>
                                <div v-else class="text-gray-400 dark:text-slate-500">-</div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                <div class="flex flex-col">
                                    <span class="text-xs text-rose-600 dark:text-rose-400 font-semibold">{{ formatDate(row.expired_at || row.used_at) || '-' }}</span>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 mt-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 w-fit border border-rose-200 dark:border-rose-800">
                                        {{ row.terminate_cause || 'Masa Aktif Habis' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-700 dark:text-slate-300 font-mono">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-slate-100">{{ formatSeconds(row.total_time) }}</span>
                                    <span v-if="row.duration" class="text-gray-400 dark:text-slate-500 text-[11px] ml-1">/ {{ row.duration }}</span>
                                </div>
                                <div v-if="row.download > 0 || row.upload > 0" class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">
                                    <span class="text-blue-600 dark:text-blue-400">↓ {{ formatBytes(row.download) }}</span> | <span class="text-amber-600 dark:text-amber-400">↑ {{ formatBytes(row.upload) }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center text-xs">
                                <div class="inline-flex items-center justify-center gap-1.5">
                                    <!-- Detail / View Button -->
                                    <button 
                                        @click="openDetailModal(row)" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-gray-700 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 dark:text-slate-200 transition-colors shadow-2xs"
                                        title="Lihat detail lengkap voucher expired"
                                    >
                                        <svg class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Detail</span>
                                    </button>

                                    <!-- Delete Button -->
                                    <button
                                        :id="`btn-delete-${row.id}`"
                                        @click="deleteVoucher(row)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-600 hover:text-white dark:bg-rose-950/50 dark:border-rose-800 dark:text-rose-300 transition-colors shadow-2xs"
                                        title="Hapus voucher ini permanen"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paginatedData.data.length === 0">
                            <td colspan="10" class="text-center py-16">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center border border-gray-100 dark:border-slate-700">
                                        <svg class="w-7 h-7 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-slate-300 text-sm font-semibold">Tidak ada voucher expired ditemukan.</p>
                                    <p class="text-gray-400 dark:text-slate-500 text-xs">Voucher yang sudah mencapai batas masa aktif akan otomatis masuk ke sini.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="paginatedData.last_page > 1" class="px-6 py-3.5 bg-gray-50 dark:bg-slate-800/80 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-slate-400">
                    Menampilkan {{ (paginatedData.current_page - 1) * perPage + 1 }} - {{ Math.min(paginatedData.current_page * perPage, paginatedData.total) }} dari {{ paginatedData.total }} data
                </span>
                <div class="flex items-center gap-1">
                    <button 
                        @click="currentPage--" 
                        :disabled="currentPage <= 1"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span class="px-2 text-xs font-semibold text-gray-700 dark:text-slate-200">Hal {{ currentPage }} / {{ paginatedData.last_page }}</span>
                    <button 
                        @click="currentPage++" 
                        :disabled="currentPage >= paginatedData.last_page"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- MODAL: DETAIL VOUCHER EXPIRED -->
        <!-- ========================================================================================= -->
        <div v-if="showDetailModal && selectedDetailRow" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in duration-150">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between bg-gray-50/70 dark:bg-slate-800/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Detail Voucher Expired</h3>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-500 dark:text-slate-400">Kode: <strong class="font-mono text-indigo-600 dark:text-indigo-400">{{ selectedDetailRow.code }}</strong></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 uppercase tracking-wider">
                                    EXPIRED
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
                                <div class="font-medium text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.duration || 'Unlimited' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Tarif Voucher</div>
                                <div class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">Rp {{ Number(selectedDetailRow.price || 0).toLocaleString('id-ID') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Riwayat Penggunaan & Sesi -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500 mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Riwayat Pemakaian & Koneksi</span>
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-gray-50/70 dark:bg-slate-800/40 p-3.5 rounded-2xl border border-gray-200/70 dark:border-slate-800 text-xs">
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Waktu Terpakai (First Login)</div>
                                <div class="font-semibold text-gray-800 dark:text-slate-200 mt-0.5">{{ formatDateFull(selectedDetailRow.first_login || selectedDetailRow.used_at) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Waktu Kedaluwarsa</div>
                                <div class="font-semibold text-rose-600 dark:text-rose-400 mt-0.5">{{ formatDateFull(selectedDetailRow.expired_at || selectedDetailRow.used_at) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Total Durasi Pemakaian</div>
                                <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ formatSecondsFull(selectedDetailRow.total_time) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Total Unduhan (Download)</div>
                                <div class="font-mono font-bold text-blue-600 dark:text-blue-400 mt-0.5">↓ {{ formatBytes(selectedDetailRow.download) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Total Unggahan (Upload)</div>
                                <div class="font-mono font-bold text-amber-600 dark:text-amber-400 mt-0.5">↑ {{ formatBytes(selectedDetailRow.upload) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">Status Penghentian</div>
                                <div class="font-semibold text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.terminate_cause || 'Masa Aktif Habis' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">IP Terakhir</div>
                                <div class="font-mono text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.ip_address || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">MAC Address Terakhir</div>
                                <div class="font-mono text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.mac_address || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 dark:text-slate-500">NAS / Router IP</div>
                                <div class="font-mono text-gray-800 dark:text-slate-200 mt-0.5">{{ selectedDetailRow.nas_ip || '-' }}</div>
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
                            @click="deleteVoucher(selectedDetailRow); showDetailModal = false"
                            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus Voucher</span>
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
        <!-- MODAL: PRINT PREVIEW LAPORAN VOUCHER EXPIRED -->
        <!-- ========================================================================================= -->
        <div v-if="showPrintModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white text-gray-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-200 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Bar -->
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50 shrink-0">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Preview Cetak Laporan Voucher Expired</h3>
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
                <div class="p-8 overflow-y-auto flex-1 bg-white print:p-0 print:m-0" id="print-area-expired">
                    <!-- Report Header -->
                    <div class="border-b-2 border-gray-800 pb-4 mb-4 flex justify-between items-start">
                        <div>
                            <h1 class="text-xl font-black text-gray-900 uppercase tracking-tight">LAPORAN DAFTAR VOUCHER EXPIRED</h1>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Dicetak pada: <strong>{{ printTimestamp }}</strong> | Kategori: <strong>Voucher Kedaluwarsa</strong>
                            </p>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                Filter: {{ search ? `Pencarian "${search}"` : 'Semua Pencarian' }} | Profil: {{ profileFilter || 'Semua Profil' }} | Reseller: {{ resellerFilter || 'Semua Reseller' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-base font-black text-indigo-700">VIZBILL</div>
                            <div class="text-[11px] text-gray-500">Hotspot Billing Lifecycle</div>
                            <div class="text-[11px] font-semibold text-gray-700 mt-1">Total: {{ filteredData.length }} Voucher</div>
                        </div>
                    </div>

                    <!-- Summary KPI Box -->
                    <div class="grid grid-cols-4 gap-3 mb-6 bg-gray-50 p-3 rounded-xl border border-gray-200 text-center">
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Total Voucher</div>
                            <div class="text-base font-black text-rose-600 mt-0.5">{{ filteredData.length }} Voucher</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Total Durasi</div>
                            <div class="text-base font-black text-indigo-600 mt-0.5">{{ formatSecondsFull(filteredTotalDuration) }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Total Kuota Terpakai</div>
                            <div class="text-base font-black text-blue-600 mt-0.5">{{ formatBytes(filteredTotalTraffic) }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Total Nilai Selesai</div>
                            <div class="text-base font-black text-emerald-600 mt-0.5">Rp {{ Number(filteredTotalValue).toLocaleString('id-ID') }}</div>
                        </div>
                    </div>

                    <!-- Report Table -->
                    <table class="w-full text-left text-xs border border-gray-300 divide-y divide-gray-300">
                        <thead class="bg-gray-100 font-bold text-gray-700">
                            <tr>
                                <th class="p-2 border border-gray-300 text-center w-8">#</th>
                                <th class="p-2 border border-gray-300">KODE / USER</th>
                                <th class="p-2 border border-gray-300">PASSWORD</th>
                                <th class="p-2 border border-gray-300">PROFIL</th>
                                <th class="p-2 border border-gray-300">PEMILIK / AREA</th>
                                <th class="p-2 border border-gray-300">TERPAKAI SEJAK</th>
                                <th class="p-2 border border-gray-300">WAKTU HABIS</th>
                                <th class="p-2 border border-gray-300">PENGGUNAAN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(item, idx) in filteredData" :key="item.id" class="hover:bg-gray-50">
                                <td class="p-2 border border-gray-300 text-center font-mono">{{ idx + 1 }}</td>
                                <td class="p-2 border border-gray-300 font-mono font-bold">{{ item.code }}</td>
                                <td class="p-2 border border-gray-300 font-mono">{{ item.password || '-' }}</td>
                                <td class="p-2 border border-gray-300">{{ item.profile }} ({{ item.duration }})</td>
                                <td class="p-2 border border-gray-300">{{ item.reseller }} <span v-if="item.area && item.area !== '-'">({{ item.area }})</span></td>
                                <td class="p-2 border border-gray-300">{{ formatDate(item.first_login || item.used_at) }}</td>
                                <td class="p-2 border border-gray-300 text-rose-600 font-semibold">{{ formatDate(item.expired_at || item.used_at) }}</td>
                                <td class="p-2 border border-gray-300 font-mono text-[11px]">{{ formatSeconds(item.total_time) }}</td>
                            </tr>
                            <tr v-if="filteredData.length === 0">
                                <td colspan="8" class="p-6 text-center text-gray-500 italic">
                                    Tidak ada data voucher expired untuk dicetak.
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
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';

const page = usePage();

const props = defineProps({
    expiredUsers: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_expired: 0, total_time: 0, total_download: 0, total_upload: 0, total_value: 0 }) },
    profiles: { type: Array, default: () => [] },
    resellers: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    isResellerPortal: { type: Boolean, default: false },
});

const isClientArea = computed(() => {
    return page.props.auth?.user?.role === 'reseller' 
        || page.props.auth?.user?.role === 'customer' 
        || page.props.auth?.user?.login_portal === 'reseller'
        || Boolean(props.isResellerPortal);
});

const layoutComponent = computed(() => isClientArea.value ? ClientAreaLayout : AppLayout);

const refreshing = ref(false);
const search = ref('');
const profileFilter = ref('');
const resellerFilter = ref('');
const areaFilter = ref('');
const selectedVouchers = ref([]);
const currentPage = ref(1);
const perPage = 15;

// Modal States
const showDetailModal = ref(false);
const selectedDetailRow = ref(null);
const showPrintModal = ref(false);

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

function resetFilter() {
    search.value = '';
    profileFilter.value = '';
    resellerFilter.value = '';
    areaFilter.value = '';
    currentPage.value = 1;
}

const filteredData = computed(() => {
    let result = props.expiredUsers || [];

    if (search.value) {
        const query = search.value.toLowerCase().trim();
        result = result.filter(v => 
            (v.code && v.code.toLowerCase().includes(query)) ||
            (v.username && v.username.toLowerCase().includes(query)) ||
            (v.password && v.password.toLowerCase().includes(query)) ||
            (v.profile && v.profile.toLowerCase().includes(query)) ||
            (v.reseller && v.reseller.toLowerCase().includes(query)) ||
            (v.area && v.area.toLowerCase().includes(query)) ||
            (v.buyer_name && v.buyer_name.toLowerCase().includes(query)) ||
            (v.buyer_phone && v.buyer_phone.toLowerCase().includes(query))
        );
    }

    if (profileFilter.value) {
        result = result.filter(v => v.profile === profileFilter.value);
    }

    if (resellerFilter.value) {
        result = result.filter(v => v.reseller === resellerFilter.value);
    }

    if (areaFilter.value) {
        result = result.filter(v => v.area === areaFilter.value);
    }

    return result;
});

const paginatedData = computed(() => {
    const list = filteredData.value;
    const lastPage = Math.max(1, Math.ceil(list.length / perPage));
    const safePage = Math.min(Math.max(1, currentPage.value), lastPage);
    const start = (safePage - 1) * perPage;
    return {
        data: list.slice(start, start + perPage),
        current_page: safePage,
        last_page: lastPage,
        per_page: perPage,
        total: list.length,
    };
});

const isAllSelected = computed(() => {
    const currentRows = paginatedData.value.data;
    return currentRows.length > 0 && currentRows.every(r => selectedVouchers.value.includes(r.id));
});

function toggleSelectAll(e) {
    const currentIds = paginatedData.value.data.map(r => r.id);
    if (e.target.checked) {
        const next = [...selectedVouchers.value];
        currentIds.forEach(id => {
            if (!next.includes(id)) next.push(id);
        });
        selectedVouchers.value = next;
    } else {
        selectedVouchers.value = selectedVouchers.value.filter(id => !currentIds.includes(id));
    }
}

// Aggregations for computed stats
const totalDurationSeconds = computed(() => {
    return (props.expiredUsers || []).reduce((acc, v) => acc + (Number(v.total_time) || 0), 0);
});

const totalDownloadBytes = computed(() => {
    return (props.expiredUsers || []).reduce((acc, v) => acc + (Number(v.download) || 0), 0);
});

const totalUploadBytes = computed(() => {
    return (props.expiredUsers || []).reduce((acc, v) => acc + (Number(v.upload) || 0), 0);
});

const totalTrafficBytes = computed(() => {
    return totalDownloadBytes.value + totalUploadBytes.value;
});

const totalValueNominal = computed(() => {
    return (props.expiredUsers || []).reduce((acc, v) => acc + (Number(v.price) || 0), 0);
});

const filteredTotalDuration = computed(() => {
    return filteredData.value.reduce((acc, v) => acc + (Number(v.total_time) || 0), 0);
});

const filteredTotalTraffic = computed(() => {
    return filteredData.value.reduce((acc, v) => acc + (Number(v.download) || 0) + (Number(v.upload) || 0), 0);
});

const filteredTotalValue = computed(() => {
    return filteredData.value.reduce((acc, v) => acc + (Number(v.price) || 0), 0);
});

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['expiredUsers', 'stats'],
        onFinish: () => (refreshing.value = false),
    });
}

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

function deleteVoucher(row) {
    Swal.fire({
        title: `Hapus Voucher ${row.code}?`,
        text: 'Voucher yang dihapus akan dihapus permanen dari sistem dan RADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/vouchers/${row.id}/delete`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Terhapus!', `Voucher ${row.code} berhasil dihapus.`, 'success');
                    selectedVouchers.value = selectedVouchers.value.filter(id => id !== row.id);
                }
            });
        }
    });
}

function bulkDelete() {
    if (selectedVouchers.value.length === 0) return;
    Swal.fire({
        title: `Hapus ${selectedVouchers.value.length} Voucher Terpilih?`,
        text: 'Semua voucher yang dipilih akan dihapus permanen dari sistem.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.bulk-destroy'), { ids: selectedVouchers.value }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedVouchers.value = [];
                    Swal.fire('Terhapus!', 'Voucher terpilih berhasil dihapus.', 'success');
                }
            });
        }
    });
}

function purgeAllExpired() {
    Swal.fire({
        title: 'Bersihkan Semua Voucher Expired?',
        text: `Semua ${props.expiredUsers.length} voucher expired akan dihapus permanen sekaligus dari database dan FreeRADIUS. Tindakan ini tidak dapat dibatalkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Bersihkan Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            const allIds = props.expiredUsers.map(v => v.id);
            router.post(route('vouchers.bulk-destroy'), { ids: allIds }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedVouchers.value = [];
                    Swal.fire('Selesai!', 'Seluruh voucher expired berhasil dibersihkan.', 'success');
                }
            });
        }
    });
}

// Export CSV / Excel Function
function exportCsv() {
    const list = filteredData.value;
    if (!list || list.length === 0) {
        Swal.fire('Info', 'Tidak ada data voucher expired untuk diexport.', 'info');
        return;
    }

    const headers = [
        'No',
        'Kode Voucher',
        'Username',
        'Password',
        'Profil Paket',
        'Durasi',
        'Harga (Rp)',
        'Pemilik / Reseller',
        'Area',
        'Terpakai Sejak',
        'Waktu Habis',
        'Durasi Terpakai (Detik)',
        'Download (Bytes)',
        'Upload (Bytes)',
        'Alasan Putus',
        'Nama Pembeli',
        'No WhatsApp'
    ];

    const rows = list.map((item, idx) => [
        idx + 1,
        item.code || '',
        item.username || '',
        item.password || '',
        item.profile || '',
        item.duration || '',
        item.price || 0,
        item.reseller || '',
        item.area || '',
        item.first_login || item.used_at || '',
        item.expired_at || item.used_at || '',
        item.total_time || 0,
        item.download || 0,
        item.upload || 0,
        item.terminate_cause || 'Masa Aktif Habis',
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
    link.setAttribute('download', `voucher_expired_${dateStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function formatDate(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
}

function formatDateFull(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
}

function formatSeconds(seconds) {
    if (!seconds || seconds <= 0) return '0 mnt';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h} jam ${m} mnt`;
    return `${m} mnt`;
}

function formatSecondsFull(seconds) {
    if (!seconds || seconds <= 0) return '0 Menit';
    const d = Math.floor(seconds / (3600 * 24));
    const h = Math.floor((seconds % (3600 * 24)) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    
    let parts = [];
    if (d > 0) parts.push(`${d} Hari`);
    if (h > 0) parts.push(`${h} Jam`);
    if (m > 0 || parts.length === 0) parts.push(`${m} Mnt`);
    
    return parts.join(' ');
}

function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #print-area-expired, #print-area-expired * {
        visibility: visible;
    }
    #print-area-expired {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
    }
}
</style>
