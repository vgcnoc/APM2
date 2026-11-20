<template>
    <AppLayout title="Log Autentikasi (RADIUS)">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans px-4 sm:px-6 lg:px-8 py-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-slate-500">
                <Link href="/dashboard" class="hover:text-indigo-600 transition-colors">Ruang Kerja</Link>
                <span>/</span>
                <span class="text-slate-400">RADIUS</span>
                <span>/</span>
                <span class="text-slate-700 font-semibold">Log Autentikasi</span>
            </nav>

            <!-- Flash Alert Messages -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-xs animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
                </div>
                <button @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-700 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-xs animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
                </div>
                <button @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-700 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white/90 backdrop-blur-md p-6 rounded-2xl border border-slate-100 shadow-xs">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-2xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Log Autentikasi (RADIUS)</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                    {{ stats?.total_logs || logs?.total || 0 }} Data
                                </span>
                            </div>
                            <p class="text-slate-500 text-sm mt-0.5">Pantau riwayat login pelanggan PPPoE & voucher hotspot secara real-time, sukses maupun ditolak.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                    <!-- Auto Refresh Dropdown -->
                    <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/80 rounded-xl px-2.5 py-1.5 text-xs text-slate-600 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isRefreshing || autoRefreshInterval > 0 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span class="font-medium">Auto-Refresh:</span>
                        <select v-model="autoRefreshInterval" @change="setupAutoRefresh" class="bg-transparent border-none text-xs font-semibold text-slate-800 p-0 pr-4 focus:ring-0 cursor-pointer">
                            <option :value="0">Off</option>
                            <option :value="10">10 dtk</option>
                            <option :value="30">30 dtk</option>
                            <option :value="60">1 mnt</option>
                        </select>
                        <span v-if="autoRefreshInterval > 0" class="text-[10px] font-mono text-indigo-600 bg-indigo-50 px-1 py-0.5 rounded">
                            {{ countdown }}s
                        </span>
                    </div>

                    <!-- Manual Refresh -->
                    <button 
                        @click="manualRefresh" 
                        :disabled="isRefreshing"
                        class="px-3.5 py-2 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-50 shadow-2xs flex items-center gap-1.5 transition-all"
                        title="Segarkan data terbaru"
                    >
                        <svg class="w-4 h-4 text-slate-500" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Refresh</span>
                    </button>

                    <!-- Export CSV Button -->
                    <a 
                        :href="exportUrl" 
                        class="px-3.5 py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 rounded-xl text-sm font-semibold shadow-2xs flex items-center gap-1.5 transition-all"
                        title="Download file CSV hasil filter"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Export CSV</span>
                    </a>

                    <!-- Bersihkan Log Modal Trigger -->
                    <button 
                        v-if="canDelete"
                        @click="openClearModal" 
                        class="px-3.5 py-2 bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 rounded-xl text-sm font-semibold shadow-2xs flex items-center gap-1.5 transition-all"
                        title="Opsi bersihkan riwayat log autentikasi"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Bersihkan Log</span>
                    </button>
                </div>
            </div>

            <!-- 4 Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Log -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Riwayat Log</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatNumber(stats?.total_logs || 0) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Seluruh rekaman login sistem</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100/60 shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16"/></svg>
                    </div>
                </div>

                <!-- Card 2: Sukses (Accept) -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Login Sukses</p>
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                {{ stats?.success_rate || 0 }}%
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ formatNumber(stats?.total_success || 0) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Access-Accept (Kredensial Cocok)</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- Card 3: Gagal (Reject) -->
                <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Login Ditolak / Gagal</p>
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                {{ stats?.fail_rate || 0 }}%
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-rose-600 mt-1">{{ formatNumber(stats?.total_failed || 0) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Access-Reject (Salah Password/User)</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>

                <!-- Card 4: Hari Ini -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Aktivitas Hari Ini</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatNumber(stats?.today_logs || 0) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Request autentikasi 24 jam ini</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100/60 shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Toolbar Filter Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex flex-col md:flex-row justify-between items-stretch md:items-center gap-3">
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    <!-- Search Input (Live Debounced) -->
                    <div class="relative min-w-[240px] flex-1 max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            v-model="searchQuery" 
                            @input="onSearchInput"
                            type="text" 
                            placeholder="Cari username, reply, nama pelanggan..." 
                            class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                        />
                        <button 
                            v-if="searchQuery" 
                            @click="searchQuery = ''; applyFilters()" 
                            class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-200/60 transition-colors"
                            title="Hapus pencarian"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Filter Status -->
                    <select v-model="statusFilter" @change="applyFilters" class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="success">Sukses (Access-Accept)</option>
                        <option value="failed">Gagal (Access-Reject)</option>
                    </select>

                    <!-- Filter Periode Waktu -->
                    <select v-model="periodFilter" @change="applyFilters" class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                        <option value="">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="7_days">7 Hari Terakhir</option>
                        <option value="30_days">30 Hari Terakhir</option>
                    </select>

                    <!-- Filter Tipe Akun -->
                    <select v-model="userTypeFilter" @change="applyFilters" class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                        <option value="">Semua Tipe Akun</option>
                        <option value="customer">Pelanggan (PPPoE)</option>
                        <option value="voucher">Voucher (Hotspot)</option>
                        <option value="unknown">Tidak Terdaftar</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 self-end md:self-center">
                    <!-- Global Eye Password Toggle -->
                    <button 
                        @click="showAllPasswords = !showAllPasswords" 
                        class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all"
                        :title="showAllPasswords ? 'Sembunyikan password' : 'Tampilkan password diajukan'"
                    >
                        <svg v-if="!showAllPasswords" class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg v-else class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        <span>{{ showAllPasswords ? 'Tutup Password' : 'Buka Password' }}</span>
                    </button>

                    <!-- Per Page Selector -->
                    <select v-model="perPage" @change="applyFilters" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer">
                        <option :value="15">15 / hal</option>
                        <option :value="25">25 / hal</option>
                        <option :value="50">50 / hal</option>
                        <option :value="100">100 / hal</option>
                    </select>
                </div>
            </div>

            <!-- Floating / Inline Bulk Action Bar (Muncul bila ada baris dicentang) -->
            <div v-if="selectedIds.length > 0 && canDelete" class="p-3.5 rounded-xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 shadow-lg animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-rose-500 text-white text-xs font-bold flex items-center justify-center">
                        {{ selectedIds.length }}
                    </span>
                    <span class="text-sm font-medium">Baris log dipilih untuk tindakan massal</span>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="selectedIds = []" 
                        class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs font-medium text-slate-300 transition-colors"
                    >
                        Batal Pilih
                    </button>
                    <button 
                        @click="deleteSelected" 
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-sm transition-all"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus {{ selectedIds.length }} Terpilih</span>
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <!-- Checkbox Select All -->
                                <th v-if="canDelete" class="w-12 px-4 py-3.5 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        @change="toggleSelectAll"
                                        class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer"
                                        title="Pilih Semua di Halaman Ini"
                                    />
                                </th>
                                <th class="px-5 py-3.5">Tanggal & Waktu</th>
                                <th class="px-5 py-3.5">Username & Identitas</th>
                                <th class="px-5 py-3.5">Password Diajukan</th>
                                <th class="px-5 py-3.5">Status & Diagnosa</th>
                                <th class="px-5 py-3.5">Balasan (Reply)</th>
                                <th class="px-4 py-3.5 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr 
                                v-for="log in logs.data" 
                                :key="log.id" 
                                :class="[
                                    'transition-colors',
                                    selectedIds.includes(log.id) ? 'bg-indigo-50/40' : 'hover:bg-slate-50/60'
                                ]"
                            >
                                <!-- Checkbox Row -->
                                <td v-if="canDelete" class="w-12 px-4 py-3.5 text-center">
                                    <input 
                                        type="checkbox" 
                                        :value="log.id" 
                                        v-model="selectedIds"
                                        class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer"
                                    />
                                </td>
                                
                                <!-- Tanggal & Waktu -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-medium text-slate-800">{{ formatDateTime(log.authdate) }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ formatTimeDiff(log.authdate) }}</div>
                                </td>

                                <!-- Username & Identitas -->
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-1 items-start">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded text-xs font-semibold text-slate-800">
                                                {{ log.username }}
                                            </span>
                                            <!-- Tipe Badge -->
                                            <span v-if="log.user_type === 'customer'" class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
                                                PPPoE
                                            </span>
                                            <span v-else-if="log.user_type === 'voucher'" class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200/60">
                                                Hotspot
                                            </span>
                                            <span v-else class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                Non-Terdaftar
                                            </span>
                                        </div>

                                        <!-- Detail Pelanggan jika ada -->
                                        <div v-if="log.customer" class="text-xs text-slate-600 flex items-center gap-1.5">
                                            <Link :href="`/customers/${log.customer.id}`" class="text-indigo-600 hover:text-indigo-800 font-medium hover:underline flex items-center gap-1">
                                                <span>{{ log.customer.name }}</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </Link>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-slate-500 text-[11px]">{{ log.customer.package_name }} ({{ log.customer.area_name }})</span>
                                        </div>

                                        <!-- Detail Voucher jika ada -->
                                        <div v-else-if="log.voucher" class="text-xs text-slate-600 flex items-center gap-1">
                                            <span class="text-slate-500 text-[11px]">Paket: {{ log.voucher.package_name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Password Diajukan -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 font-mono text-xs">
                                        <span v-if="!log.pass" class="text-slate-400 italic">-</span>
                                        <span v-else-if="showAllPasswords || revealedPasswords[log.id]" class="text-slate-800 font-semibold bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
                                            {{ log.pass }}
                                        </span>
                                        <span v-else class="text-slate-400 tracking-widest">
                                            ••••••••
                                        </span>
                                        <button 
                                            v-if="log.pass" 
                                            @click="togglePassword(log.id)" 
                                            class="text-slate-400 hover:text-slate-600 p-1 rounded hover:bg-slate-100 transition-colors"
                                            :title="revealedPasswords[log.id] ? 'Sembunyikan' : 'Lihat password'"
                                        >
                                            <svg v-if="revealedPasswords[log.id]" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Status & Diagnosa -->
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span 
                                            v-if="log.reply === 'Access-Accept'" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-semibold"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Sukses
                                        </span>
                                        <span 
                                            v-else 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-xs font-semibold"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Gagal
                                        </span>

                                        <!-- Diagnosa Keterangan -->
                                        <span 
                                            v-if="log.diagnosis"
                                            :class="[
                                                'text-[11px] font-medium px-2 py-0.5 rounded-md inline-block',
                                                log.diagnosis_type === 'success' ? 'bg-emerald-50/70 text-emerald-800' :
                                                log.diagnosis_type === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200/60' :
                                                log.diagnosis_type === 'danger' ? 'bg-rose-50 text-rose-800 border border-rose-200/60' :
                                                'bg-slate-100 text-slate-700'
                                            ]"
                                        >
                                            {{ log.diagnosis }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Balasan (Reply) -->
                                <td class="px-5 py-3.5 text-xs font-mono whitespace-nowrap">
                                    <span :class="log.reply === 'Access-Accept' ? 'text-emerald-700 font-semibold' : 'text-rose-700 font-semibold'">
                                        {{ log.reply }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol Detail -->
                                        <button 
                                            @click="openDetailModal(log)" 
                                            class="p-1.5 text-slate-500 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-lg transition-all shadow-2xs"
                                            title="Lihat rincian lengkap log"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Tombol Hapus Satuan -->
                                        <button 
                                            v-if="canDelete"
                                            @click="deleteSingle(log)" 
                                            class="p-1.5 text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 rounded-lg transition-all shadow-2xs"
                                            title="Hapus baris log ini"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!logs?.data || logs.data.length === 0">
                                <td :colspan="canDelete ? 7 : 6" class="px-6 py-16 text-center text-slate-500">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-center text-slate-300 mx-auto mb-3 shadow-inner">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <p class="text-base font-bold text-slate-800">Tidak ada riwayat log ditemukan.</p>
                                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                        Belum ada data autentikasi yang sesuai dengan kriteria filter atau pencarian Anda.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-3" v-if="logs?.data && logs.data.length > 0">
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-bold text-slate-700">{{ logs.from || 0 }}</span> sampai <span class="font-bold text-slate-700">{{ logs.to || 0 }}</span> dari <span class="font-bold text-slate-700">{{ logs.total || 0 }}</span> total log
                </p>
                <div class="flex gap-1 flex-wrap">
                    <Link 
                        v-for="(link, i) in logs.links" 
                        :key="i"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-xl transition-all',
                            link.active ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200',
                            !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>

            <!-- Modal Detail Log -->
            <div v-if="selectedDetailLog" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 transform transition-all animate-fade-in space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Rincian Log Autentikasi #{{ selectedDetailLog.id }}</h3>
                                <p class="text-xs text-slate-500">{{ formatDateTime(selectedDetailLog.authdate) }}</p>
                            </div>
                        </div>
                        <button @click="selectedDetailLog = null" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <!-- Status Banner -->
                        <div :class="[
                            'p-3 rounded-xl border flex items-center justify-between',
                            selectedDetailLog.reply === 'Access-Accept' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
                        ]">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm">{{ selectedDetailLog.reply }}</span>
                                <span class="text-[11px]">({{ selectedDetailLog.reply === 'Access-Accept' ? 'Kredensial Cocok' : 'Akses Ditolak' }})</span>
                            </div>
                            <span class="font-mono text-[10px] bg-white/70 px-2 py-0.5 rounded border border-current">
                                ID: {{ selectedDetailLog.id }}
                            </span>
                        </div>

                        <!-- Grid Details -->
                        <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Username</span>
                                <span class="font-mono font-bold text-slate-800 text-sm">{{ selectedDetailLog.username }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Tipe Akun</span>
                                <span class="font-semibold text-slate-800 uppercase">
                                    {{ selectedDetailLog.user_type === 'customer' ? 'Pelanggan (PPPoE)' : selectedDetailLog.user_type === 'voucher' ? 'Voucher (Hotspot)' : 'Tidak Dikenal' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Password Diajukan</span>
                                <span class="font-mono font-bold text-slate-800">{{ selectedDetailLog.pass || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Ada di FreeRADIUS</span>
                                <span :class="selectedDetailLog.has_radcheck ? 'text-emerald-700 font-bold' : 'text-slate-500'">
                                    {{ selectedDetailLog.has_radcheck ? 'Ya (Terdaftar)' : 'Tidak Ada di radcheck' }}
                                </span>
                            </div>
                        </div>

                        <!-- Customer Info Box if available -->
                        <div v-if="selectedDetailLog.customer" class="p-3 bg-blue-50/60 border border-blue-100 rounded-xl space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-blue-900">Data Pelanggan Terkait:</span>
                                <Link :href="`/customers/${selectedDetailLog.customer.id}`" class="text-indigo-600 font-semibold hover:underline flex items-center gap-0.5">
                                    <span>Buka Profil</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </Link>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-slate-700 pt-1">
                                <div><b>Nama:</b> {{ selectedDetailLog.customer.name }}</div>
                                <div><b>Status:</b> <span class="capitalize">{{ selectedDetailLog.customer.status }}</span></div>
                                <div><b>Paket:</b> {{ selectedDetailLog.customer.package_name }} ({{ selectedDetailLog.customer.speed }})</div>
                                <div><b>Area:</b> {{ selectedDetailLog.customer.area_name }}</div>
                            </div>
                        </div>

                        <!-- Diagnosis Box -->
                        <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl space-y-1 text-amber-900">
                            <span class="font-bold block">Diagnosa Teknis:</span>
                            <p class="text-[11px] leading-relaxed">{{ selectedDetailLog.diagnosis }}</p>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button 
                            type="button" 
                            @click="selectedDetailLog = null" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Bersihkan Log -->
            <div v-if="showClearModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all animate-fade-in">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Bersihkan Riwayat Log</h3>
                                <p class="text-xs text-slate-500">Pilih kriteria data log yang ingin dihapus</p>
                            </div>
                        </div>
                        <button @click="showClearModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Pilihan Mode Pembersihan -->
                    <div class="mt-4 space-y-2.5">
                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === 'failed' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-slate-200 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" value="failed" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Hanya Log Gagal (Access-Reject)</div>
                                <div class="text-[11px] text-slate-500">Hapus semua riwayat kegagalan autentikasi (login ditolak/password salah). Log sukses tetap dipertahankan.</div>
                            </div>
                        </label>

                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === 'success' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-slate-200 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" value="success" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Hanya Log Sukses (Access-Accept)</div>
                                <div class="text-[11px] text-slate-500">Hapus riwayat sukses login. Log gagal tetap dipertahankan untuk audit keamanan.</div>
                            </div>
                        </label>

                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === '7_days' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-slate-200 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" value="7_days" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Log Lebih Dari 7 Hari Lalu</div>
                                <div class="text-[11px] text-slate-500">Hapus log lama yang usianya sudah lebih dari 1 minggu.</div>
                            </div>
                        </label>

                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === '30_days' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-slate-200 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" value="30_days" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Log Lebih Dari 30 Hari Lalu</div>
                                <div class="text-[11px] text-slate-500">Hapus log lama yang usianya sudah lebih dari 1 bulan.</div>
                            </div>
                        </label>

                        <label 
                            v-if="searchQuery || statusFilter || periodFilter"
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === 'filtered' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-slate-200 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" value="filtered" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Sesuai Filter & Pencarian Saat Ini</div>
                                <div class="text-[11px] text-slate-500">
                                    Hanya hapus data yang cocok dengan kriteria pencarian dan filter aktif saat ini.
                                </div>
                            </div>
                        </label>

                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === 'all' ? 'border-rose-500 bg-rose-50/70 ring-1 ring-rose-500' : 'border-slate-200 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" value="all" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-rose-700">Hapus SEMUA Riwayat Log</div>
                                <div class="text-[11px] text-rose-600/80">Kosongkan seluruh data tabel riwayat login RADIUS secara permanen.</div>
                            </div>
                        </label>
                    </div>

                    <!-- Konfirmasi teks jika mode ALL -->
                    <div v-if="clearMode === 'all'" class="mt-3.5 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                        <p class="font-semibold mb-1">Konfirmasi Keamanan:</p>
                        <p class="mb-2">Ketik <span class="font-mono font-bold text-rose-600 bg-white px-1.5 py-0.5 rounded border border-amber-300">HAPUS</span> di bawah ini untuk konfirmasi pengosongan seluruh log:</p>
                        <input 
                            v-model="confirmInput" 
                            type="text" 
                            placeholder="Ketik HAPUS" 
                            class="w-full px-3 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-semibold uppercase tracking-wider focus:ring-2 focus:ring-rose-500"
                        />
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-5 flex justify-end gap-2.5">
                        <button 
                            type="button" 
                            @click="showClearModal = false"
                            class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="button" 
                            @click="executeClear"
                            :disabled="isClearing || (clearMode === 'all' && confirmInput.trim().toUpperCase() !== 'HAPUS')"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-xs flex items-center gap-1.5',
                                (clearMode === 'all' && confirmInput.trim().toUpperCase() !== 'HAPUS') || isClearing
                                    ? 'bg-rose-300 cursor-not-allowed'
                                    : 'bg-rose-600 hover:bg-rose-700'
                            ]"
                        >
                            <svg v-if="isClearing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>{{ isClearing ? 'Membersihkan...' : 'Konfirmasi Bersihkan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    logs: Object,
    filters: Object,
    stats: Object
});

const { can, hasRole } = usePermission();
const canDelete = computed(() => {
    return hasRole(['admin', 'super admin', 'superadmin']) || can('radius_auth_logs_delete') || can('menu_radius_auth_logs');
});

// State Filter
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const periodFilter = ref(props.filters?.period || '');
const userTypeFilter = ref(props.filters?.user_type || '');
const perPage = ref(props.filters?.per_page || 15);

// State Tabel & Seleksi
const selectedIds = ref([]);
const showAllPasswords = ref(false);
const revealedPasswords = ref({});
const selectedDetailLog = ref(null);

// State Modal Bersihkan Log
const showClearModal = ref(false);
const clearMode = ref('failed');
const confirmInput = ref('');
const isClearing = ref(false);

// State Auto Refresh
const autoRefreshInterval = ref(0);
const countdown = ref(0);
const isRefreshing = ref(false);
let autoRefreshTimer = null;
let countdownTimer = null;
let searchDebounceTimeout = null;

const isAllSelected = computed(() => {
    if (!props.logs?.data || props.logs.data.length === 0) return false;
    return props.logs.data.every(log => selectedIds.value.includes(log.id));
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.logs.data.map(log => log.id);
    }
}

function togglePassword(id) {
    revealedPasswords.value[id] = !revealedPasswords.value[id];
}

function openDetailModal(log) {
    selectedDetailLog.value = log;
}

// URL Export CSV dengan parameter aktif
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (searchQuery.value) params.set('search', searchQuery.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (periodFilter.value) params.set('period', periodFilter.value);
    return `/radius/auth-logs/export?${params.toString()}`;
});

function onSearchInput() {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
}

function applyFilters() {
    selectedIds.value = [];
    router.get('/radius/auth-logs', { 
        search: searchQuery.value,
        status: statusFilter.value,
        period: periodFilter.value,
        user_type: userTypeFilter.value,
        per_page: perPage.value
    }, { 
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}

function manualRefresh() {
    isRefreshing.value = true;
    router.reload({
        only: ['logs', 'stats'],
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
}

function setupAutoRefresh() {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    if (countdownTimer) clearInterval(countdownTimer);

    if (autoRefreshInterval.value > 0) {
        countdown.value = autoRefreshInterval.value;
        countdownTimer = setInterval(() => {
            if (countdown.value > 1) {
                countdown.value--;
            } else {
                countdown.value = autoRefreshInterval.value;
            }
        }, 1000);

        autoRefreshTimer = setInterval(() => {
            manualRefresh();
        }, autoRefreshInterval.value * 1000);
    }
}

onMounted(() => {
    setupAutoRefresh();
});

onBeforeUnmount(() => {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    if (countdownTimer) clearInterval(countdownTimer);
    if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
});

function formatNumber(num) {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).format(d);
    } catch {
        return dateStr;
    }
}

function formatTimeDiff(dateStr) {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        const now = new Date();
        const diffMs = now - d;
        const diffSec = Math.floor(diffMs / 1000);
        if (diffSec < 60) return `${Math.max(1, diffSec)} dtk lalu`;
        const diffMin = Math.floor(diffSec / 60);
        if (diffMin < 60) return `${diffMin} mnt lalu`;
        const diffHour = Math.floor(diffMin / 60);
        if (diffHour < 24) return `${diffHour} jam lalu`;
        const diffDay = Math.floor(diffHour / 24);
        return `${diffDay} hr lalu`;
    } catch {
        return '';
    }
}

// ── Aksi Hapus Satuan ──────────────────────────────────────────
function deleteSingle(log) {
    Swal.fire({
        title: 'Hapus Log Autentikasi?',
        html: `Apakah Anda yakin ingin menghapus log autentikasi untuk username <b>${log.username}</b> (${log.reply})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/radius/auth-logs/${log.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = selectedIds.value.filter(id => id !== log.id);
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Log autentikasi berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Gagal menghapus log.', 'error');
                }
            });
        }
    });
}

// ── Aksi Hapus Terpilih (Bulk Delete) ───────────────────────────
function deleteSelected() {
    if (selectedIds.value.length === 0) return;

    Swal.fire({
        title: `Hapus ${selectedIds.value.length} Log Terpilih?`,
        text: 'Data log autentikasi yang dipilih akan dihapus secara permanen dari database.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Ya, Hapus (${selectedIds.value.length})!`,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/radius/auth-logs/bulk-delete', {
                ids: selectedIds.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = [];
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Log autentikasi terpilih berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Gagal menghapus log terpilih.', 'error');
                }
            });
        }
    });
}

// ── Aksi Modal Bersihkan Log ───────────────────────────────────
function openClearModal() {
    confirmInput.value = '';
    showClearModal.value = true;
}

function executeClear() {
    if (clearMode.value === 'all' && confirmInput.value.trim().toUpperCase() !== 'HAPUS') {
        return;
    }

    isClearing.value = true;
    router.post('/radius/auth-logs/bulk-delete', {
        all: true,
        mode: clearMode.value,
        search: searchQuery.value,
        status: statusFilter.value,
        period: periodFilter.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showClearModal.value = false;
            selectedIds.value = [];
            confirmInput.value = '';
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Pembersihan riwayat log autentikasi telah selesai.',
                timer: 1800,
                showConfirmButton: false
            });
        },
        onError: (err) => {
            Swal.fire('Gagal!', err?.message || 'Gagal membersihkan log.', 'error');
        },
        onFinish: () => {
            isClearing.value = false;
        }
    });
}
</script>
