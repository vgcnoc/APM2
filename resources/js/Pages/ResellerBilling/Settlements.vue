<template>
    <AppLayout title="Pelunasan Reseller" subtitle="Verifikasi penerimaan uang saldo/tagihan dari Reseller">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-12 font-sans">
            
            <!-- Alert Messages -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 dark:bg-emerald-950/40 border border-green-200 dark:border-emerald-800 text-green-700 dark:text-emerald-300 px-4 py-3 rounded-2xl text-sm font-medium shadow-xs flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 dark:bg-rose-950/40 border border-red-200 dark:border-rose-800 text-red-700 dark:text-rose-300 px-4 py-3 rounded-2xl text-sm font-medium shadow-xs flex items-center gap-2">
                <span>❌</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- ── 4 RINGKASAN STATISTIK CARDS ── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">
                
                <!-- Card 1: Menunggu Validasi (Klik untuk filter tab pending) -->
                <div 
                    @click="switchTab('pending')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'pending'
                            ? 'bg-amber-50/90 dark:bg-amber-950/40 border-amber-300 dark:border-amber-600 ring-2 ring-amber-500 shadow-md'
                            : 'bg-white dark:bg-slate-900 border-gray-200/80 dark:border-slate-800 hover:border-amber-200 dark:hover:border-amber-700/50 hover:bg-amber-50/20 dark:hover:bg-amber-950/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                            <span class="truncate">Menunggu Validasi</span>
                        </span>
                        <span v-if="activeTab === 'pending'" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-amber-200 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-amber-700 dark:text-amber-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.pending_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-amber-800 dark:text-amber-300/90 font-semibold">
                        <span>{{ stats?.pending_count || 0 }} Setoran Menunggu</span>
                        <span class="text-xs">&rarr;</span>
                    </div>
                </div>

                <!-- Card 2: Tervalidasi Hari Ini -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 min-w-0 border border-gray-200/80 dark:border-slate-800 shadow-xs hover:border-sky-200 dark:hover:border-sky-700/50 hover:bg-sky-50/20 dark:hover:bg-sky-950/20 transition-all duration-200">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-sky-800 dark:text-sky-300 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
                            <span class="truncate">Tervalidasi Hari Ini</span>
                        </span>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-sky-100 dark:bg-sky-900/50 text-sky-800 dark:text-sky-200 border border-sky-200 dark:border-sky-700 shrink-0">
                            Hari Ini
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-sky-700 dark:text-sky-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.today_verified_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-sky-800 dark:text-sky-300/90 font-semibold">
                        <span>{{ stats?.today_verified_count || 0 }} Setoran Masuk</span>
                        <span class="text-sky-500 text-xs">✓</span>
                    </div>
                </div>

                <!-- Card 3: Tervalidasi Bulan Ini -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 min-w-0 border border-gray-200/80 dark:border-slate-800 shadow-xs hover:border-indigo-200 dark:hover:border-indigo-700/50 hover:bg-indigo-50/20 dark:hover:bg-indigo-950/20 transition-all duration-200">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shrink-0"></span>
                            <span class="truncate">Tervalidasi Bulan Ini</span>
                        </span>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-700 shrink-0">
                            Bulan Ini
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-indigo-700 dark:text-indigo-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.month_verified_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-indigo-800 dark:text-indigo-300/90 font-semibold">
                        <span>{{ stats?.month_verified_count || 0 }} Setoran Masuk</span>
                        <span class="text-indigo-500 text-xs">📊</span>
                    </div>
                </div>

                <!-- Card 4: Total Tervalidasi (Klik untuk filter tab verified) -->
                <div 
                    @click="switchTab('verified')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'verified'
                            ? 'bg-emerald-50/90 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-600 ring-2 ring-emerald-500 shadow-md'
                            : 'bg-white dark:bg-slate-900 border-gray-200/80 dark:border-slate-800 hover:border-emerald-200 dark:hover:border-emerald-700/50 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="truncate">Total Tervalidasi</span>
                        </span>
                        <span v-if="activeTab === 'verified'" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-200 dark:bg-emerald-900/60 text-emerald-900 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-emerald-700 dark:text-emerald-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.total_verified_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-emerald-800 dark:text-emerald-300/90 font-semibold">
                        <span>{{ stats?.total_verified_count || 0 }} Masuk Kas</span>
                        <span class="text-xs">&rarr;</span>
                    </div>
                </div>

            </div>

            <!-- ── FILTER BAR ── -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all min-w-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 items-end">
                    
                    <!-- Search Input -->
                    <div class="xl:col-span-3 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                            Pencarian Data
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                v-model="filterForm.search" 
                                @input="onSearchInput"
                                @keyup.enter="applyFilters"
                                placeholder="Cari nama reseller, invoice, penagih..." 
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 pl-8 pr-3 py-2 transition-colors"
                            />
                            <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Area / Wilayah -->
                    <div v-if="areas && areas.length" class="xl:col-span-2 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                            Wilayah / Area
                        </label>
                        <select 
                            v-model="filterForm.area_id" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Wilayah</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">
                                {{ a.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Petugas Penagih -->
                    <div :class="areas && areas.length ? 'xl:col-span-3' : 'xl:col-span-4'" class="min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                            Petugas Penagih
                        </label>
                        <select 
                            v-model="filterForm.collector_id" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Petugas Penagih</option>
                            <option v-for="c in collectors" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Metode Pembayaran -->
                    <div :class="areas && areas.length ? 'xl:col-span-2' : 'xl:col-span-3'" class="min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                            Metode Pembayaran
                        </label>
                        <select 
                            v-model="filterForm.payment_method" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Metode</option>
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                            <option value="loket">Loket Mitra</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="xl:col-span-2 flex items-center gap-2">
                        <button 
                            @click="applyFilters"
                            type="button" 
                            class="flex-1 inline-flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-3 rounded-xl transition shadow-xs cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter
                        </button>
                        <button 
                            v-if="hasActiveFilters"
                            @click="resetFilters" 
                            type="button" 
                            title="Reset Semua Filter"
                            class="inline-flex items-center justify-center bg-gray-100 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 dark:hover:text-rose-400 text-gray-600 dark:text-slate-300 text-xs font-bold py-2 px-3 rounded-xl transition border border-gray-200 dark:border-slate-700 cursor-pointer shrink-0"
                        >
                            Reset
                        </button>
                    </div>

                </div>

                <!-- Date Range Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-slate-800 items-center">
                    <div class="xl:col-span-4 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                            Tanggal Terima Dari
                        </label>
                        <input 
                            type="date" 
                            v-model="filterForm.start_date" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                        />
                    </div>
                    <div class="xl:col-span-4 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                            Sampai Dengan Tanggal
                        </label>
                        <input 
                            type="date" 
                            v-model="filterForm.end_date" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                        />
                    </div>
                    <div class="sm:col-span-2 xl:col-span-4 flex items-center justify-start xl:justify-end text-xs text-gray-500 dark:text-slate-400 pt-1 xl:pt-4">
                        <span v-if="hasActiveFilters" class="inline-flex items-center gap-1.5 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 px-3 py-1 rounded-xl border border-amber-200 dark:border-amber-800 font-bold text-xs">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Filter sedang aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── TABEL DAFTAR SETORAN ── -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 dark:border-slate-800 overflow-hidden min-w-0">
                <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Validasi Setoran Reseller</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">Uang setoran saldo/tagihan reseller diverifikasi di sini.</p>
                    </div>
                    
                    <div class="flex bg-gray-200/70 dark:bg-slate-800 p-1 rounded-xl w-full lg:w-auto max-w-full overflow-x-auto overscroll-x-contain gap-1">
                        <button 
                            type="button"
                            @click="switchTab('pending')" 
                            class="px-3.5 sm:px-4 py-2 text-xs font-bold rounded-lg transition-all whitespace-nowrap text-center flex-1 lg:flex-none flex items-center justify-center gap-2 cursor-pointer shrink-0"
                            :class="activeTab === 'pending' ? 'bg-white dark:bg-slate-700 text-indigo-700 dark:text-indigo-300 shadow-xs' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'">
                            <span>Menunggu Validasi</span>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                :class="activeTab === 'pending' ? 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200' : 'bg-gray-300/80 dark:bg-slate-700 text-gray-700 dark:text-slate-300'"
                            >
                                {{ stats?.pending_count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('verified')" 
                            class="px-3.5 sm:px-4 py-2 text-xs font-bold rounded-lg transition-all whitespace-nowrap text-center flex-1 lg:flex-none flex items-center justify-center gap-2 cursor-pointer shrink-0"
                            :class="activeTab === 'verified' ? 'bg-white dark:bg-slate-700 text-indigo-700 dark:text-indigo-300 shadow-xs' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'">
                            <span>Sudah Tervalidasi</span>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                :class="activeTab === 'verified' ? 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200' : 'bg-gray-300/80 dark:bg-slate-700 text-gray-700 dark:text-slate-300'"
                            >
                                {{ stats?.total_verified_count || 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                        <thead class="bg-gray-50 dark:bg-slate-800/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Tanggal Terima</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Reseller / Invoice</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Penagih & Metode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Rincian Pembayaran</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                            <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- Tanggal Terima -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                    <div class="font-bold text-gray-900 dark:text-slate-100">
                                        {{ new Date(pay.payment_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                    </div>
                                    <div v-if="pay.created_at" class="text-[11px] text-gray-400 dark:text-slate-500">
                                        {{ new Date(pay.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'}) }} WIB
                                    </div>
                                </td>

                                <!-- Reseller / Invoice -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                        <span>{{ pay.reseller_name || pay.customer?.name || 'Reseller #' + (pay.customer_id || pay.id) }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-mono font-medium">{{ pay.reseller_code || pay.customer?.customer_code || '-' }}</span>
                                        <span v-if="pay.reseller_phone && pay.reseller_phone !== '-'" class="text-gray-400 dark:text-slate-500">({{ pay.reseller_phone }})</span>
                                        <a 
                                            v-if="pay.reseller_phone && pay.reseller_phone !== '-'" 
                                            :href="getWhatsAppUrl(pay)" 
                                            target="_blank" 
                                            title="Konfirmasi setoran via WhatsApp" 
                                            class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                                        >
                                            💬 WA
                                        </a>
                                    </div>
                                    <div class="text-[11px] font-mono text-gray-500 dark:text-slate-400 mt-0.5">
                                        INV: <span class="font-semibold text-gray-700 dark:text-slate-300">{{ pay.invoice?.invoice_number || '-' }}</span>
                                    </div>
                                </td>

                                <!-- Penagih & Metode -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-slate-200 flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold inline-flex items-center justify-center">
                                            {{ (pay.collector?.name || 'P')[0].toUpperCase() }}
                                        </span>
                                        <span>{{ pay.collector?.name || 'Tidak tercatat' }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span 
                                            class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full capitalize"
                                            :class="getMethodBadgeClass(pay.payment_method)"
                                        >
                                            {{ pay.payment_method || 'Tunai' }}
                                        </span>
                                        <button 
                                            v-if="pay.proof_url || pay.proof_image" 
                                            type="button"
                                            @click="openProofModal(pay)"
                                            class="text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 px-1.5 py-0.5 rounded cursor-pointer hover:bg-amber-100 dark:hover:bg-amber-900/40 inline-flex items-center gap-0.5"
                                        >
                                            📷 Bukti
                                        </button>
                                    </div>
                                </td>

                                <!-- Rincian Pembayaran -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <div class="text-[11px] text-gray-500 dark:text-slate-400 font-medium">
                                            Tagihan: Rp {{ formatRupiah(pay.invoice?.amount || 0) }}
                                        </div>
                                        <div class="text-sm font-black text-green-600 dark:text-green-400 flex items-center gap-1">
                                            <span class="bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold">Setor</span>
                                            Rp {{ formatRupiah(pay.amount) }}
                                        </div>
                                        <div class="text-[11px] font-bold" :class="(pay.invoice?.remaining || 0) > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-slate-500'">
                                            Sisa Utang: Rp {{ formatRupiah(pay.invoice?.remaining || 0) }}
                                        </div>
                                        <div v-if="pay.notes" class="text-[11px] text-gray-500 dark:text-slate-400 italic mt-0.5 line-clamp-1 max-w-[220px]" :title="pay.notes">
                                            📝 {{ pay.notes }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div v-if="pay.status === 'pending'" class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="approve(pay)" 
                                            class="inline-flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white px-3.5 py-2 rounded-xl shadow-xs transition-all hover:shadow text-xs font-bold ring-1 ring-green-600/50 cursor-pointer"
                                        >
                                            ✓ Validasi
                                        </button>
                                        <button 
                                            @click="cancel(pay)" 
                                            class="inline-flex items-center gap-1.5 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-300 px-3.5 py-2 rounded-xl border border-rose-200 dark:border-rose-800 transition-all text-xs font-bold shadow-xs cursor-pointer"
                                        >
                                            ✕ Cancel
                                        </button>
                                    </div>
                                    <div v-else class="flex items-center justify-end gap-2">
                                        <span class="inline-flex items-center gap-1.5 text-green-700 dark:text-green-300 font-bold text-xs bg-green-50 dark:bg-green-950/40 px-3 py-1.5 rounded-lg border border-green-200 dark:border-green-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Tervalidasi
                                        </span>
                                        <button 
                                            @click="cancel(pay)" 
                                            title="Batalkan validasi setoran ini" 
                                            class="inline-flex items-center gap-1 bg-gray-50 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-gray-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-300 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-slate-700 hover:border-rose-200 dark:hover:border-rose-800 text-xs font-semibold transition-all cursor-pointer"
                                        >
                                            ✕ Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!payments.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 flex items-center justify-center text-2xl mb-3">
                                            🔍
                                        </div>
                                        <p class="font-bold text-gray-700 dark:text-slate-200 text-sm">Tidak ada data setoran yang cocok</p>
                                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian, rentang tanggal, atau petugas penagih pada filter.</p>
                                        <button 
                                            v-if="hasActiveFilters" 
                                            @click="resetFilters" 
                                            class="mt-3 text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="payments.links && payments.links.length > 3" class="px-6 py-4 bg-gray-50/50 dark:bg-slate-800/40 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 dark:text-slate-400">
                        Menampilkan <span class="font-bold text-gray-800 dark:text-slate-200">{{ payments.from || 0 }}</span> - <span class="font-bold text-gray-800 dark:text-slate-200">{{ payments.to || 0 }}</span> dari <span class="font-bold text-gray-800 dark:text-slate-200">{{ payments.total || 0 }}</span> data setoran
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link 
                            v-for="(link, i) in payments.links" 
                            :key="i" 
                            :href="link.url || '#'" 
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs rounded-lg border transition-all"
                            :class="[
                                link.active 
                                    ? 'bg-indigo-600 text-white font-bold border-indigo-600 shadow-xs' 
                                    : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 border-gray-200 dark:border-slate-700',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>

            </div>

            <!-- ── MODAL PREVIEW BUKTI BAYAR ── -->
            <div v-if="showProofModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity" @click.self="showProofModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 dark:border-slate-800 animate-in fade-in zoom-in duration-150">
                    <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Bukti Pembayaran Setoran</h4>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ activeProofPayment?.reseller_name }} — Rp {{ formatRupiah(activeProofPayment?.amount || 0) }}</p>
                        </div>
                        <button 
                            @click="showProofModal = false" 
                            type="button" 
                            class="w-8 h-8 rounded-full bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400 flex items-center justify-center text-sm font-bold transition"
                        >
                            ✕
                        </button>
                    </div>
                    <div class="p-4 flex flex-col items-center justify-center bg-gray-50 dark:bg-slate-950 min-h-[260px] max-h-[500px] overflow-auto">
                        <img 
                            v-if="activeProofPayment?.proof_url" 
                            :src="activeProofPayment.proof_url" 
                            alt="Bukti Transfer/Pembayaran" 
                            class="max-w-full max-h-[460px] object-contain rounded-xl shadow-sm border border-gray-200 dark:border-slate-800"
                        />
                        <p v-else class="text-xs text-gray-400">Bukti gambar tidak tersedia.</p>
                    </div>
                    <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-500 dark:text-slate-400 font-mono">{{ activeProofPayment?.invoice?.invoice_number }}</span>
                        <div class="flex items-center gap-2">
                            <a 
                                v-if="activeProofPayment?.proof_url" 
                                :href="activeProofPayment.proof_url" 
                                target="_blank" 
                                class="px-3 py-1.5 text-xs font-bold rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition"
                            >
                                ↗ Buka Gambar Penuh
                            </a>
                            <button 
                                @click="showProofModal = false" 
                                class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 transition"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    payments: Object,
    activeTab: String,
    stats: Object,
    filters: Object,
    collectors: Array,
    areas: Array
});

// Modal bukti transfer / pembayaran
const showProofModal = ref(false);
const activeProofPayment = ref(null);

const openProofModal = (pay) => {
    activeProofPayment.value = pay;
    showProofModal.value = true;
};

// Form Filter reaktif
const filterForm = reactive({
    search: props.filters?.search || '',
    collector_id: props.filters?.collector_id || '',
    payment_method: props.filters?.payment_method || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    area_id: props.filters?.area_id || '',
    tab: props.activeTab || 'pending',
});

// Deteksi apakah filter sedang aktif
const hasActiveFilters = computed(() => {
    return !!(
        filterForm.search ||
        filterForm.collector_id ||
        filterForm.payment_method ||
        filterForm.start_date ||
        filterForm.end_date ||
        filterForm.area_id
    );
});

// Debounce pencarian
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 450);
};

// Terapkan filter ke backend
const applyFilters = () => {
    router.get(route('reseller-settlements.index'), {
        search: filterForm.search || undefined,
        collector_id: filterForm.collector_id || undefined,
        payment_method: filterForm.payment_method || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
        area_id: filterForm.area_id || undefined,
        tab: filterForm.tab || 'pending',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Beralih antar tab (Menunggu Validasi vs Sudah Tervalidasi)
const switchTab = (tabName) => {
    filterForm.tab = tabName;
    applyFilters();
};

// Reset filter
const resetFilters = () => {
    filterForm.search = '';
    filterForm.collector_id = '';
    filterForm.payment_method = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.area_id = '';
    applyFilters();
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

const getMethodBadgeClass = (method) => {
    const m = (method || '').toLowerCase();
    if (m === 'cash' || m === 'tunai') return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
    if (m === 'transfer') return 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
    if (m === 'qris') return 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800';
    return 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700';
};

const getWhatsAppUrl = (pay) => {
    let phone = (pay.reseller_phone || '').replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    const name = pay.reseller_name || 'Mitra Reseller';
    const amount = formatRupiah(pay.amount);
    const inv = pay.invoice?.invoice_number || '-';
    const collector = pay.collector?.name || 'Petugas Lapangan';
    const msg = `Halo ${name},\nKonfirmasi penerimaan setoran kasbon:\n• No Tagihan: ${inv}\n• Jumlah: Rp ${amount}\n• Penagih: ${collector}\nStatus setoran telah kami proses. Terima kasih!`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
};

const approve = (pay) => {
    const name = pay.reseller_name || pay.customer?.name || 'Reseller';
    const amountStr = formatRupiah(pay.amount);

    Swal.fire({
        title: 'Validasi Setoran Kasbon?',
        html: `
            <div class="text-left text-sm space-y-2 mt-2">
                <p>Uang setoran dari <b>${name}</b> sebesar <b>Rp ${amountStr}</b> akan diverifikasi.</p>
                <div class="bg-gray-50 dark:bg-slate-800 p-3 rounded-xl border border-gray-200 dark:border-slate-700 text-xs text-gray-600 dark:text-slate-300">
                    <div>• Invoice: <b>${pay.invoice?.invoice_number || '-'}</b></div>
                    <div>• Penagih: <b>${pay.collector?.name || '-'}</b></div>
                    <div>• Metode: <b>${pay.payment_method || 'Tunai'}</b></div>
                </div>
                <p class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs">Saldo utang kasbon reseller akan berkurang dan dana masuk ke kas perusahaan.</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Validasi Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('reseller-settlements.approve', pay.id), {}, { 
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Tervalidasi!',
                        text: `Setoran Rp ${amountStr} berhasil disetujui.`,
                        icon: 'success',
                        timer: 1800,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
};

const cancel = (pay) => {
    const name = pay.reseller_name || pay.customer?.name || 'Reseller';
    const amountStr = formatRupiah(pay.amount);

    Swal.fire({
        title: 'Batalkan Setoran?',
        html: `
            <div class="text-left text-sm space-y-2 mt-2">
                <p>Apakah Anda yakin ingin membatalkan setoran dari <b>${name}</b> sebesar <b>Rp ${amountStr}</b>?</p>
                <p class="text-rose-500 font-semibold text-xs">Catatan pembayaran akan dihapus dan status tagihan invoice akan dikembalikan seperti semula.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Batalkan Setoran',
        cancelButtonText: 'Kembali'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('reseller-settlements.cancel', pay.id), {}, { 
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Dibatalkan',
                        text: 'Setoran penagih berhasil dibatalkan.',
                        icon: 'info',
                        timer: 1600,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
};
</script>
