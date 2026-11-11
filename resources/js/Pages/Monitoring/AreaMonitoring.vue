<template>
    <AppLayout title="Monitoring Belum Bayar Area" subtitle="Rekapitulasi tagihan belum bayar, lunas, nunggak, dan status pelanggan per area & all area">
        <!-- ── 1. HEADER & FILTER PERIODE ───────────────────────────── -->
        <div class="mb-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight">Monitoring Belum Bayar & Status Pelanggan Per Area</h1>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Rekapitulasi komprehensif penagihan, tunggakan, dan status siklus hidup pelanggan per wilayah & konsolidasi seluruh area</p>
                        </div>
                    </div>
                </div>

                <!-- Action Controls: Period, Area, Export, Print -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Dropdown Bulan -->
                    <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Bulan:</span>
                        <select 
                            v-model="filterMonth" 
                            @change="applyFilter"
                            class="bg-transparent text-xs font-bold text-slate-800 dark:text-slate-200 border-none p-0 focus:ring-0 cursor-pointer"
                        >
                            <option v-for="(name, num) in months" :key="num" :value="num" class="dark:bg-slate-800">{{ name }}</option>
                        </select>
                    </div>

                    <!-- Dropdown Tahun -->
                    <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Tahun:</span>
                        <select 
                            v-model="filterYear" 
                            @change="applyFilter"
                            class="bg-transparent text-xs font-bold text-slate-800 dark:text-slate-200 border-none p-0 focus:ring-0 cursor-pointer"
                        >
                            <option v-for="y in years" :key="y" :value="y" class="dark:bg-slate-800">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Filter Area Dropdown -->
                    <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Area:</span>
                        <select 
                            v-model="filterArea" 
                            @change="applyFilter"
                            class="bg-transparent text-xs font-bold text-slate-800 dark:text-slate-200 border-none p-0 focus:ring-0 cursor-pointer max-w-[130px]"
                        >
                            <option value="all" class="dark:bg-slate-800">Semua Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id" class="dark:bg-slate-800">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- Export Excel / CSV -->
                    <a 
                        :href="exportUrl" 
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition shadow-sm"
                        title="Unduh Spreadsheet Format Excel / CSV"
                    >
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Excel
                    </a>

                    <!-- Cetak / Print -->
                    <button 
                        @click="printTable"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-sm"
                        title="Cetak Laporan / Simpan PDF"
                    >
                        <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak
                    </button>
                </div>
            </div>
        </div>

        <!-- ── 2. TOP KPI SUMMARY CARDS ─────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- KPI 1: Collection Rate -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tingkat Kolektibilitas</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-100 dark:border-amber-900 shadow-inner group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-3xl font-black text-amber-600 dark:text-amber-400">{{ kpis.collection_rate }}%</span>
                    <span class="text-xs font-semibold text-slate-400">Tercapai</span>
                </div>
                <!-- Progress bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full mt-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-2 rounded-full transition-all duration-700" :style="{ width: Math.min(100, Math.max(0, kpis.collection_rate)) + '%' }"></div>
                </div>
                <div class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Lunas vs Total Tagihan:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ kpis.total_paid_count }} / {{ kpis.total_invoiced_count }}</span>
                </div>
            </div>

            <!-- KPI 2: Tagihan Lunas / Terkumpul -->
            <div 
                @click="openDrillDown('all', 'lunas')"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all cursor-pointer relative overflow-hidden group select-none"
                title="Klik untuk melihat daftar pelanggan lunas"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Tagihan Lunas (Terkumpul)</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-900 shadow-inner group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ formatRupiah(kpis.total_paid_amount) }}</span>
                </div>
                <div class="mt-3 text-xs font-semibold text-slate-600 dark:text-slate-300 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span>Jumlah Invoice:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        {{ kpis.total_paid_count }} Transaksi
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </div>

            <!-- KPI 3: Total Belum Bayar & Nunggak -->
            <div 
                @click="openDrillDown('all', 'nunggak')"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all cursor-pointer relative overflow-hidden group select-none"
                title="Klik untuk melihat daftar pelanggan nunggak"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Total Belum Bayar & Nunggak</span>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-100 dark:border-rose-900 shadow-inner group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ formatRupiah(kpis.total_unpaid_amount) }}</span>
                </div>
                <div class="mt-3 text-xs font-semibold text-slate-600 dark:text-slate-300 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span>Nunggak / Janji:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                        {{ kpis.total_nunggak_count }} Nunggak | {{ kpis.total_janji_count }} Janji
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </div>

            <!-- KPI 4: Total Pelanggan Aktif Se-ISP -->
            <div 
                @click="openDrillDown('all', 'aktif')"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all cursor-pointer relative overflow-hidden group select-none"
                title="Klik untuk melihat daftar pelanggan aktif"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Pelanggan Aktif Se-ISP</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900 shadow-inner group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ kpis.total_active_customers }}</span>
                    <span class="text-xs font-semibold text-slate-400">Pelanggan Aktif</span>
                </div>
                <div class="mt-3 text-xs font-semibold text-slate-600 dark:text-slate-300 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span>Total Keseluruhan:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                        {{ kpis.total_all_customers }} Pelanggan
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </div>
        </div>

        <!-- ── 3. MODE SWITCHER & LEGEND ────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <!-- View Mode Switcher -->
            <div class="inline-flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 shadow-inner">
                <button 
                    @click="viewMode = 'matrix'"
                    :class="[
                        'flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all',
                        viewMode === 'matrix' 
                            ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' 
                            : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Format Matrix Spreadsheet (Mockup Excel)
                </button>
                <button 
                    @click="viewMode = 'cards'"
                    :class="[
                        'flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all',
                        viewMode === 'cards' 
                            ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' 
                            : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Tampilan Kartu Wilayah
                </button>
            </div>

            <!-- Petunjuk Interaktif -->
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <span>Klik angka di tabel untuk membuka rincian pelanggan & kirim WA penagihan otomatis</span>
            </div>
        </div>

        <!-- ── 4. TAMPILAN MATRIX SPREADSHEET (PERSIS EXCEL MOCKUP) ────── -->
        <div v-if="viewMode === 'matrix'" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="overflow-x-auto print:overflow-visible">
                <table class="w-full text-xs text-left border-collapse border border-slate-300 dark:border-slate-800">
                    <!-- Master Header -->
                    <thead>
                        <tr class="bg-slate-800 text-white divide-x divide-slate-700">
                            <th rowspan="2" class="py-3 px-4 font-black uppercase text-center align-middle border border-slate-700 tracking-wider w-36">
                                AREA / WILAYAH
                            </th>
                            <th colspan="4" class="py-2.5 px-4 font-black uppercase text-center border border-slate-700 bg-slate-800 tracking-wider">
                                STATUS TAGIHAN & PEMBAYARAN (KIRI)
                            </th>
                            <th colspan="3" class="py-2.5 px-4 font-black uppercase text-center border border-slate-700 bg-slate-800 tracking-wider">
                                STATUS OPERASIONAL PELANGGAN (KANAN)
                            </th>
                        </tr>
                        <tr class="divide-x divide-slate-300 dark:divide-slate-700 text-slate-700 dark:text-slate-200">
                            <!-- Tagihan subheaders -->
                            <th class="py-2 px-3 font-bold uppercase bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-center w-40">Status Tagihan</th>
                            <th class="py-2 px-3 font-bold uppercase bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-center w-20">Jumlah</th>
                            <th class="py-2 px-3 font-bold uppercase bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-center w-28">Nominal (Rp)</th>
                            <th class="py-2 px-3 font-black uppercase bg-amber-200 dark:bg-amber-900/60 text-amber-900 dark:text-amber-100 border border-amber-300 dark:border-amber-700 text-center w-28 shadow-sm">
                                PERSENTASE (%)
                            </th>
                            <!-- Pelanggan subheaders -->
                            <th class="py-2 px-3 font-bold uppercase bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-center w-40">Status Pelanggan</th>
                            <th class="py-2 px-3 font-bold uppercase bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-center w-20">Jumlah</th>
                            <th class="py-2 px-3 font-bold uppercase bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-center w-24">Porsi (%)</th>
                        </tr>
                    </thead>

                    <!-- Table Bodies: Loop each Area -->
                    <tbody v-for="(row, idx) in filteredRows" :key="row.area_id" class="border-b-4 border-slate-400 dark:border-slate-700">
                        <!-- Baris 1: LUNAS & AKTIF -->
                        <tr class="divide-x divide-slate-200 dark:divide-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- Area Name Cell with rowspan 7 -->
                            <td rowspan="7" class="py-4 px-4 font-black text-center align-middle bg-slate-50/80 dark:bg-slate-900/90 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-100 text-sm">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-base font-extrabold tracking-tight text-blue-600 dark:text-blue-400">{{ row.area_name }}</span>
                                    <span v-if="row.description" class="text-[10px] text-slate-400 font-normal mt-0.5 line-clamp-1">{{ row.description }}</span>
                                    <button 
                                        @click="openDrillDown(row.area_id, 'total_tagihan')"
                                        class="mt-2 text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-900 hover:bg-blue-100 transition"
                                    >
                                        Detail Area
                                    </button>
                                </div>
                            </td>

                            <!-- LUNAS -->
                            <td class="py-2 px-3 font-bold text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-800">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    LUNAS
                                </span>
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'lunas')" 
                                    class="font-black text-emerald-600 dark:text-emerald-400 hover:underline px-2 py-0.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-950/40"
                                >
                                    {{ row.billing.lunas.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right font-medium text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ formatRupiah(row.billing.lunas.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-50 dark:bg-amber-950/30 text-slate-800 dark:text-amber-200 border border-amber-200 dark:border-amber-900/60">
                                {{ row.billing.lunas.percentage }}%
                            </td>

                            <!-- AKTIF -->
                            <td class="py-2 px-3 font-bold text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-800">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    AKTIF
                                </span>
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'aktif')" 
                                    class="font-black text-blue-600 dark:text-blue-400 hover:underline px-2 py-0.5 rounded hover:bg-blue-50 dark:hover:bg-blue-950/40"
                                >
                                    {{ row.customer.aktif.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center font-medium text-slate-500 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ row.customer.aktif.percentage }}%
                            </td>
                        </tr>

                        <!-- Baris 2: BELUM BAYAR & STOP SEMENTARA -->
                        <tr class="divide-x divide-slate-200 dark:divide-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- BELUM BAYAR (Highlight pink/soft red matching mockup) -->
                            <td class="py-2 px-3 font-bold bg-rose-50/70 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 border border-slate-300 dark:border-slate-800">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                    BELUM BAYAR
                                </span>
                            </td>
                            <td class="py-2 px-3 text-center bg-rose-50/70 dark:bg-rose-950/30 border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'belum_bayar')" 
                                    class="font-black text-rose-700 dark:text-rose-300 hover:underline px-2 py-0.5 rounded hover:bg-rose-100 dark:hover:bg-rose-900/50"
                                >
                                    {{ row.billing.belum_bayar.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right font-medium text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ formatRupiah(row.billing.belum_bayar.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-50 dark:bg-amber-950/30 text-slate-800 dark:text-amber-200 border border-amber-200 dark:border-amber-900/60">
                                {{ row.billing.belum_bayar.percentage }}%
                            </td>

                            <!-- STOP SEMENTARA -->
                            <td class="py-2 px-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800">
                                STOP SEMENTARA
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'stop_sementara')" 
                                    class="font-bold text-amber-600 dark:text-amber-400 hover:underline px-2 py-0.5 rounded hover:bg-amber-50 dark:hover:bg-amber-950/40"
                                >
                                    {{ row.customer.stop_sementara.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center font-medium text-slate-500 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ row.customer.stop_sementara.percentage }}%
                            </td>
                        </tr>

                        <!-- Baris 3: BAYAR SEBAGIAN & STOP PERMANEN -->
                        <tr class="divide-x divide-slate-200 dark:divide-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- BAYAR SEBAGIAN -->
                            <td class="py-2 px-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800">
                                BAYAR SEBAGIAN
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'bayar_sebagian')" 
                                    class="font-bold text-sky-600 dark:text-sky-400 hover:underline px-2 py-0.5 rounded hover:bg-sky-50 dark:hover:bg-sky-950/40"
                                >
                                    {{ row.billing.bayar_sebagian.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right font-medium text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ formatRupiah(row.billing.bayar_sebagian.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-50 dark:bg-amber-950/30 text-slate-800 dark:text-amber-200 border border-amber-200 dark:border-amber-900/60">
                                {{ row.billing.bayar_sebagian.percentage }}%
                            </td>

                            <!-- STOP PERMANEN -->
                            <td class="py-2 px-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800">
                                STOP PERMANEN
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'stop_permanen')" 
                                    class="font-bold text-slate-600 dark:text-slate-400 hover:underline px-2 py-0.5 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    {{ row.customer.stop_permanen.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center font-medium text-slate-500 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ row.customer.stop_permanen.percentage }}%
                            </td>
                        </tr>

                        <!-- Baris 4: JANJI BAYAR & PSB -->
                        <tr class="divide-x divide-slate-200 dark:divide-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- JANJI BAYAR -->
                            <td class="py-2 px-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800">
                                JANJI BAYAR
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'janji_bayar')" 
                                    class="font-bold text-amber-600 dark:text-amber-400 hover:underline px-2 py-0.5 rounded hover:bg-amber-50 dark:hover:bg-amber-950/40"
                                >
                                    {{ row.billing.janji_bayar.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right font-medium text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ formatRupiah(row.billing.janji_bayar.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-50 dark:bg-amber-950/30 text-slate-800 dark:text-amber-200 border border-amber-200 dark:border-amber-900/60">
                                {{ row.billing.janji_bayar.percentage }}%
                            </td>

                            <!-- PSB -->
                            <td class="py-2 px-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800">
                                PSB (Pemasangan Baru)
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'psb')" 
                                    class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline px-2 py-0.5 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                                >
                                    {{ row.customer.psb.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center font-medium text-slate-500 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ row.customer.psb.percentage }}%
                            </td>
                        </tr>

                        <!-- Baris 5: NUNGGAK & GRATIS -->
                        <tr class="divide-x divide-slate-200 dark:divide-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- NUNGGAK -->
                            <td class="py-2 px-3 font-medium text-rose-700 dark:text-rose-400 border border-slate-300 dark:border-slate-800">
                                NUNGGAK (Lewat Tempo)
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'nunggak')" 
                                    class="font-black text-rose-600 dark:text-rose-400 hover:underline px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                >
                                    {{ row.billing.nunggak.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right font-medium text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ formatRupiah(row.billing.nunggak.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-50 dark:bg-amber-950/30 text-slate-800 dark:text-amber-200 border border-amber-200 dark:border-amber-900/60">
                                {{ row.billing.nunggak.percentage }}%
                            </td>

                            <!-- GRATIS -->
                            <td class="py-2 px-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800">
                                GRATIS (CSR)
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'gratis')" 
                                    class="font-bold text-teal-600 dark:text-teal-400 hover:underline px-2 py-0.5 rounded hover:bg-teal-50 dark:hover:bg-teal-950/40"
                                >
                                    {{ row.customer.gratis.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center font-medium text-slate-500 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ row.customer.gratis.percentage }}%
                            </td>
                        </tr>

                        <!-- Baris 6: KOSONG (LEFT) & FREE HOME (RIGHT) -->
                        <tr class="divide-x divide-slate-200 dark:divide-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2 px-3 bg-slate-50/50 dark:bg-slate-800/20 border border-slate-300 dark:border-slate-800"></td>
                            <td class="py-2 px-3 bg-slate-50/50 dark:bg-slate-800/20 border border-slate-300 dark:border-slate-800"></td>
                            <td class="py-2 px-3 bg-slate-50/50 dark:bg-slate-800/20 border border-slate-300 dark:border-slate-800"></td>
                            <td class="py-2 px-3 bg-amber-50/30 dark:bg-amber-950/10 border border-amber-200 dark:border-amber-900/60"></td>

                            <!-- FREE HOME -->
                            <td class="py-2 px-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800">
                                FREE HOME (Karyawan)
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'free_home')" 
                                    class="font-bold text-cyan-600 dark:text-cyan-400 hover:underline px-2 py-0.5 rounded hover:bg-cyan-50 dark:hover:bg-cyan-950/40"
                                >
                                    {{ row.customer.free_home.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center font-medium text-slate-500 dark:text-slate-400 border border-slate-300 dark:border-slate-800">
                                {{ row.customer.free_home.percentage }}%
                            </td>
                        </tr>

                        <!-- Baris 7: TOTAL (Highlight soft purple/lavender matching mockup) -->
                        <tr class="divide-x divide-slate-200 dark:divide-slate-800 bg-purple-100/60 dark:bg-purple-950/40 font-black">
                            <!-- TOTAL TAGIHAN -->
                            <td class="py-2.5 px-3 uppercase text-purple-900 dark:text-purple-200 border border-slate-300 dark:border-slate-800">
                                TOTAL TAGIHAN
                            </td>
                            <td class="py-2.5 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'total_tagihan')" 
                                    class="font-black text-purple-900 dark:text-purple-200 hover:underline px-2 py-0.5 rounded hover:bg-purple-200/50"
                                >
                                    {{ row.billing.total.count }}
                                </button>
                            </td>
                            <td class="py-2.5 px-3 text-right font-black text-purple-900 dark:text-purple-200 border border-slate-300 dark:border-slate-800">
                                {{ formatRupiah(row.billing.total.amount) }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-black bg-amber-100 dark:bg-amber-900/50 text-amber-950 dark:text-amber-100 border border-amber-300 dark:border-amber-700">
                                {{ row.billing.total.percentage }}%
                            </td>

                            <!-- TOTAL PELANGGAN -->
                            <td class="py-2.5 px-3 uppercase text-purple-900 dark:text-purple-200 border border-slate-300 dark:border-slate-800">
                                TOTAL PELANGGAN
                            </td>
                            <td class="py-2.5 px-3 text-center border border-slate-300 dark:border-slate-800">
                                <button 
                                    @click="openDrillDown(row.area_id, 'total_pelanggan')" 
                                    class="font-black text-purple-900 dark:text-purple-200 hover:underline px-2 py-0.5 rounded hover:bg-purple-200/50"
                                >
                                    {{ row.customer.total.count }}
                                </button>
                            </td>
                            <td class="py-2.5 px-3 text-center font-black text-purple-900 dark:text-purple-200 border border-slate-300 dark:border-slate-800">
                                {{ row.customer.total.percentage }}%
                            </td>
                        </tr>
                    </tbody>

                    <!-- ── GRAND SUMMARY TFOOT: ALL AREA (KONSOLIDASI SELURUH ISP) ── -->
                    <tfoot class="border-t-4 border-slate-800 bg-slate-900 text-white font-bold">
                        <tr class="divide-x divide-slate-700">
                            <!-- Area Cell -->
                            <td rowspan="7" class="py-4 px-4 font-black text-center align-middle bg-slate-950 border border-slate-700 text-amber-400 text-sm">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    <span class="inline-block px-2 py-0.5 rounded bg-amber-400/20 text-amber-300 text-[10px] font-black tracking-widest uppercase">KONSOLIDASI</span>
                                    <span class="text-base font-black tracking-tight text-white leading-tight">SEMUA AREA</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Seluruh Wilayah ISP</span>
                                    <button 
                                        @click="openDrillDown('all', 'total_tagihan')"
                                        class="mt-2 text-[10px] font-black text-amber-300 bg-amber-950/80 px-2.5 py-1 rounded-lg border border-amber-500/40 hover:bg-amber-900 transition"
                                    >
                                        Semua Invoice
                                    </button>
                                </div>
                            </td>

                            <!-- Grand LUNAS -->
                            <td class="py-2.5 px-3 border border-slate-700 text-emerald-300">LUNAS</td>
                            <td class="py-2.5 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'lunas')" class="font-black text-emerald-400 hover:underline">
                                    {{ summary.billing.lunas.count }}
                                </button>
                            </td>
                            <td class="py-2.5 px-3 text-right border border-slate-700 text-emerald-300">
                                {{ formatRupiah(summary.billing.lunas.amount) }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-black bg-amber-400 text-slate-950 border border-amber-300">
                                {{ summary.billing.lunas.percentage }}%
                            </td>

                            <!-- Grand AKTIF -->
                            <td class="py-2.5 px-3 border border-slate-700 text-blue-300">AKTIF</td>
                            <td class="py-2.5 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'aktif')" class="font-black text-blue-400 hover:underline">
                                    {{ summary.customer.aktif.count }}
                                </button>
                            </td>
                            <td class="py-2.5 px-3 text-center border border-slate-700 text-slate-300">
                                {{ summary.customer.aktif.percentage }}%
                            </td>
                        </tr>

                        <!-- Grand BELUM BAYAR & STOP SEMENTARA -->
                        <tr class="divide-x divide-slate-700">
                            <td class="py-2 px-3 border border-slate-700 text-rose-300">BELUM BAYAR</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'belum_bayar')" class="font-black text-rose-400 hover:underline">
                                    {{ summary.billing.belum_bayar.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right border border-slate-700 text-slate-300">
                                {{ formatRupiah(summary.billing.belum_bayar.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ summary.billing.belum_bayar.percentage }}%
                            </td>

                            <td class="py-2 px-3 border border-slate-700 text-slate-300">STOP SEMENTARA</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'stop_sementara')" class="font-bold text-amber-400 hover:underline">
                                    {{ summary.customer.stop_sementara.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-700 text-slate-300">
                                {{ summary.customer.stop_sementara.percentage }}%
                            </td>
                        </tr>

                        <!-- Grand BAYAR SEBAGIAN & STOP PERMANEN -->
                        <tr class="divide-x divide-slate-700">
                            <td class="py-2 px-3 border border-slate-700 text-sky-300">BAYAR SEBAGIAN</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'bayar_sebagian')" class="font-bold text-sky-400 hover:underline">
                                    {{ summary.billing.bayar_sebagian.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right border border-slate-700 text-slate-300">
                                {{ formatRupiah(summary.billing.bayar_sebagian.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ summary.billing.bayar_sebagian.percentage }}%
                            </td>

                            <td class="py-2 px-3 border border-slate-700 text-slate-300">STOP PERMANEN</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'stop_permanen')" class="font-bold text-slate-400 hover:underline">
                                    {{ summary.customer.stop_permanen.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-700 text-slate-300">
                                {{ summary.customer.stop_permanen.percentage }}%
                            </td>
                        </tr>

                        <!-- Grand JANJI BAYAR & PSB -->
                        <tr class="divide-x divide-slate-700">
                            <td class="py-2 px-3 border border-slate-700 text-amber-300">JANJI BAYAR</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'janji_bayar')" class="font-bold text-amber-400 hover:underline">
                                    {{ summary.billing.janji_bayar.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right border border-slate-700 text-slate-300">
                                {{ formatRupiah(summary.billing.janji_bayar.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ summary.billing.janji_bayar.percentage }}%
                            </td>

                            <td class="py-2 px-3 border border-slate-700 text-slate-300">PSB (Pemasangan Baru)</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'psb')" class="font-bold text-indigo-400 hover:underline">
                                    {{ summary.customer.psb.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-700 text-slate-300">
                                {{ summary.customer.psb.percentage }}%
                            </td>
                        </tr>

                        <!-- Grand NUNGGAK & GRATIS -->
                        <tr class="divide-x divide-slate-700">
                            <td class="py-2 px-3 border border-slate-700 text-rose-400">NUNGGAK (Lewat Tempo)</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'nunggak')" class="font-black text-rose-400 hover:underline">
                                    {{ summary.billing.nunggak.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right border border-slate-700 text-slate-300">
                                {{ formatRupiah(summary.billing.nunggak.amount) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ summary.billing.nunggak.percentage }}%
                            </td>

                            <td class="py-2 px-3 border border-slate-700 text-slate-300">GRATIS (CSR)</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'gratis')" class="font-bold text-teal-400 hover:underline">
                                    {{ summary.customer.gratis.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-700 text-slate-300">
                                {{ summary.customer.gratis.percentage }}%
                            </td>
                        </tr>

                        <!-- Grand FREE HOME -->
                        <tr class="divide-x divide-slate-700">
                            <td class="py-2 px-3 border border-slate-700"></td>
                            <td class="py-2 px-3 border border-slate-700"></td>
                            <td class="py-2 px-3 border border-slate-700"></td>
                            <td class="py-2 px-3 border border-slate-700"></td>

                            <td class="py-2 px-3 border border-slate-700 text-cyan-300">FREE HOME (Karyawan)</td>
                            <td class="py-2 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'free_home')" class="font-bold text-cyan-400 hover:underline">
                                    {{ summary.customer.free_home.count }}
                                </button>
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-700 text-slate-300">
                                {{ summary.customer.free_home.percentage }}%
                            </td>
                        </tr>

                        <!-- Grand TOTAL -->
                        <tr class="divide-x divide-slate-700 bg-slate-950 font-black text-amber-300 text-sm">
                            <td class="py-3 px-3 uppercase border border-slate-700 tracking-wider">TOTAL TAGIHAN ISP</td>
                            <td class="py-3 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'total_tagihan')" class="font-black text-amber-300 hover:underline">
                                    {{ summary.billing.total.count }}
                                </button>
                            </td>
                            <td class="py-3 px-3 text-right border border-slate-700 text-amber-300">
                                {{ formatRupiah(summary.billing.total.amount) }}
                            </td>
                            <td class="py-3 px-3 text-center font-black bg-amber-400 text-slate-950 border border-amber-300">
                                {{ summary.billing.total.percentage }}%
                            </td>

                            <td class="py-3 px-3 uppercase border border-slate-700 tracking-wider">TOTAL PELANGGAN ISP</td>
                            <td class="py-3 px-3 text-center border border-slate-700">
                                <button @click="openDrillDown('all', 'total_pelanggan')" class="font-black text-amber-300 hover:underline">
                                    {{ summary.customer.total.count }}
                                </button>
                            </td>
                            <td class="py-3 px-3 text-center border border-slate-700 text-amber-300">
                                {{ summary.customer.total.percentage }}%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ── 5. TAMPILAN KARTU WILAYAH (CARDS VIEW) ────────────────── -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
            <div 
                v-for="row in filteredRows" 
                :key="row.area_id"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between"
            >
                <div>
                    <!-- Card Top Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-800 dark:text-slate-100">{{ row.area_name }}</h2>
                            <p class="text-xs text-slate-400">{{ row.description || 'Wilayah Operasional ISP' }}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900">
                            {{ row.customer.total.count }} Pelanggan
                        </span>
                    </div>

                    <!-- Collection Rate Mini Bar -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs font-bold mb-1">
                            <span class="text-slate-500">Kolektibilitas Tagihan:</span>
                            <span class="text-amber-600 dark:text-amber-400">{{ row.billing.lunas.percentage }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-2 rounded-full" :style="{ width: row.billing.lunas.percentage + '%' }"></div>
                        </div>
                    </div>

                    <!-- Billing Mini Stats -->
                    <div class="space-y-2 mb-4 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Lunas ({{ row.billing.lunas.percentage }}%)
                            </span>
                            <button @click="openDrillDown(row.area_id, 'lunas')" class="font-bold text-emerald-600 hover:underline">
                                {{ row.billing.lunas.count }} ({{ formatRupiah(row.billing.lunas.amount) }})
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                Belum Bayar ({{ row.billing.belum_bayar.percentage }}%)
                            </span>
                            <button @click="openDrillDown(row.area_id, 'belum_bayar')" class="font-bold text-rose-600 hover:underline">
                                {{ row.billing.belum_bayar.count }} ({{ formatRupiah(row.billing.belum_bayar.amount) }})
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                Nunggak ({{ row.billing.nunggak.percentage }}%)
                            </span>
                            <button @click="openDrillDown(row.area_id, 'nunggak')" class="font-bold text-rose-700 hover:underline">
                                {{ row.billing.nunggak.count }} ({{ formatRupiah(row.billing.nunggak.amount) }})
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                Janji Bayar
                            </span>
                            <button @click="openDrillDown(row.area_id, 'janji_bayar')" class="font-bold text-amber-600 hover:underline">
                                {{ row.billing.janji_bayar.count }}
                            </button>
                        </div>
                    </div>

                    <!-- Customer Lifecycle Mini Badges -->
                    <div class="grid grid-cols-3 gap-2 text-center text-[11px] mb-4">
                        <div class="bg-blue-50 dark:bg-blue-950/40 p-2 rounded-lg border border-blue-100 dark:border-blue-900">
                            <div class="text-slate-400 text-[10px]">Aktif</div>
                            <button @click="openDrillDown(row.area_id, 'aktif')" class="font-black text-blue-600 dark:text-blue-400 hover:underline">
                                {{ row.customer.aktif.count }}
                            </button>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-slate-400 text-[10px]">Suspended</div>
                            <button @click="openDrillDown(row.area_id, 'stop_sementara')" class="font-black text-amber-600 hover:underline">
                                {{ row.customer.stop_sementara.count }}
                            </button>
                        </div>
                        <div class="bg-indigo-50 dark:bg-indigo-950/40 p-2 rounded-lg border border-indigo-100 dark:border-indigo-900">
                            <div class="text-slate-400 text-[10px]">PSB</div>
                            <button @click="openDrillDown(row.area_id, 'psb')" class="font-black text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ row.customer.psb.count }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Card Action -->
                <button 
                    @click="openDrillDown(row.area_id, 'total_tagihan')"
                    class="w-full py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5"
                >
                    Lihat Seluruh Data Area
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- ── 6. MODAL DRAWER DRILL-DOWN PELANGGAN ─────────────────── -->
        <div v-if="drillDownModal.show" class="fixed inset-0 z-50 overflow-hidden flex justify-end" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div @click="closeDrillDown" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <!-- Drawer Container -->
            <div class="relative w-screen max-w-2xl bg-white dark:bg-slate-900 shadow-2xl flex flex-col z-10 border-l border-slate-200 dark:border-slate-800">
                <!-- Drawer Header -->
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/90 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                {{ drillDownModal.areaTitle }}
                            </span>
                            <span v-if="drillDownModal.totalAmount > 0" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                {{ formatRupiah(drillDownModal.totalAmount) }}
                            </span>
                        </div>
                        <h2 class="text-base font-black text-slate-800 dark:text-slate-100 mt-1">{{ drillDownModal.title }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total terdata: {{ drillDownModal.totalRecords }} pelanggan / invoice</p>
                    </div>

                    <button 
                        @click="closeDrillDown"
                        class="w-8 h-8 rounded-xl bg-slate-200/70 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Drawer Search Filter -->
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center gap-2">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input 
                            v-model="drillDownModal.search" 
                            @input="debounceSearch"
                            type="text" 
                            placeholder="Cari nama, kode pelanggan, invoice, atau no HP..."
                            class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Drawer Content List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <!-- Loading state -->
                    <div v-if="drillDownModal.loading" class="py-16 text-center text-slate-400 flex flex-col items-center justify-center">
                        <svg class="w-8 h-8 animate-spin text-blue-500 mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs font-semibold">Memuat data pelanggan...</span>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="drillDownModal.items.length === 0" class="py-16 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300">Tidak ada data ditemukan</p>
                        <p class="text-[11px] text-slate-400 mt-1">Coba ubah kata kunci pencarian atau pilih kategori lain</p>
                    </div>

                    <!-- Items list -->
                    <div 
                        v-else 
                        v-for="item in drillDownModal.items" 
                        :key="item.id + '-' + (item.invoice_number || item.customer_code)"
                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/60 shadow-sm hover:border-blue-300 dark:hover:border-blue-700 transition"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-sm text-slate-800 dark:text-slate-100 truncate">{{ item.name }}</span>
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        {{ item.customer_code }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-1">
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">{{ item.area_name }}</span> &bull; {{ item.address }}
                                </p>
                                <div class="flex flex-wrap items-center gap-2 mt-2 text-[11px]">
                                    <span class="px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-semibold border border-blue-100 dark:border-blue-900">
                                        {{ item.package_name }}
                                    </span>
                                    <span v-if="item.invoice_number" class="text-slate-500 font-mono">
                                        {{ item.invoice_number }}
                                    </span>
                                    <span v-if="item.due_date && item.due_date !== '-'" class="text-slate-500">
                                        Jatuh Tempo: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ item.due_date }}</span>
                                    </span>
                                    <span v-if="item.promise_date" class="px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-bold border border-amber-200">
                                        Janji Bayar: {{ item.promise_date }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right actions: Nominal & WA button -->
                            <div class="text-right flex flex-col items-end gap-1.5 shrink-0">
                                <span class="text-sm font-black text-slate-800 dark:text-slate-100">
                                    {{ formatRupiah(item.amount) }}
                                </span>

                                <a 
                                    v-if="item.phone"
                                    :href="getWhatsAppUrl(item)"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition shadow-sm"
                                    title="Hubungi & Kirim Pengingat Tagihan via WhatsApp"
                                >
                                    <svg class="w-3.5 h-3.5 fill-current text-emerald-600" viewBox="0 0 24 24">
                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/>
                                    </svg>
                                    WA Penagihan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drawer Pagination Footer -->
                <div v-if="drillDownModal.lastPage > 1" class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold">
                        Halaman {{ drillDownModal.currentPage }} dari {{ drillDownModal.lastPage }}
                    </span>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="loadPage(drillDownModal.currentPage - 1)" 
                            :disabled="drillDownModal.currentPage <= 1"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 disabled:opacity-40 font-bold hover:bg-slate-100 transition"
                        >
                            Sebelumnya
                        </button>
                        <button 
                            @click="loadPage(drillDownModal.currentPage + 1)" 
                            :disabled="drillDownModal.currentPage >= drillDownModal.lastPage"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 disabled:opacity-40 font-bold hover:bg-slate-100 transition"
                        >
                            Berikutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    kpis: { type: Object, default: () => ({}) },
    areas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    months: { type: Object, default: () => ({}) },
    years: { type: Array, default: () => [] },
});

// State Filter
const filterMonth = ref(props.filters.month || new Date().getMonth() + 1);
const filterYear = ref(props.filters.year || new Date().getFullYear());
const filterArea = ref(props.filters.area_id || 'all');
const viewMode = ref('matrix'); // 'matrix' or 'cards'

// URL export Excel / CSV
const exportUrl = computed(() => {
    return `/monitoring-area/export?month=${filterMonth.value}&year=${filterYear.value}`;
});

// Filter rows jika user memilih area tertentu di dropdown
const filteredRows = computed(() => {
    if (filterArea.value === 'all') {
        return props.rows;
    }
    return props.rows.filter(r => String(r.area_id) === String(filterArea.value));
});

// Terapkan filter periode & area
const applyFilter = () => {
    router.get('/monitoring-area', {
        month: filterMonth.value,
        year: filterYear.value,
        area_id: filterArea.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Cetak halaman
const printTable = () => {
    window.print();
};

// Format Rupiah
const formatRupiah = (val) => {
    const num = Number(val) || 0;
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
};

// ── DRILL DOWN MODAL STATE & ACTIONS ──────────────────────────────
const drillDownModal = reactive({
    show: false,
    loading: false,
    title: '',
    areaTitle: '',
    categoryKey: '',
    areaId: 'all',
    search: '',
    totalRecords: 0,
    totalAmount: 0,
    currentPage: 1,
    lastPage: 1,
    items: [],
});

let searchTimeout = null;
const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchDrillDownData(1);
    }, 350);
};

const openDrillDown = (areaId, category) => {
    drillDownModal.areaId = areaId;
    drillDownModal.categoryKey = category;
    drillDownModal.search = '';
    drillDownModal.show = true;
    fetchDrillDownData(1);
};

const closeDrillDown = () => {
    drillDownModal.show = false;
    drillDownModal.items = [];
};

const loadPage = (page) => {
    fetchDrillDownData(page);
};

const fetchDrillDownData = async (page = 1) => {
    drillDownModal.loading = true;
    drillDownModal.currentPage = page;

    try {
        const params = new URLSearchParams({
            month: filterMonth.value,
            year: filterYear.value,
            area_id: drillDownModal.areaId,
            category: drillDownModal.categoryKey,
            search: drillDownModal.search || '',
            page: page,
        });

        const res = await fetch(`/monitoring-area/customers?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (!res.ok) throw new Error('Gagal mengambil data pelanggan');
        const json = await res.json();

        drillDownModal.title = json.category_label;
        drillDownModal.areaTitle = json.area_title;
        drillDownModal.totalRecords = json.total_records;
        drillDownModal.totalAmount = json.total_amount;
        drillDownModal.lastPage = json.last_page;
        drillDownModal.items = json.items || [];
    } catch (err) {
        console.error('Error fetching drill-down:', err);
    } finally {
        drillDownModal.loading = false;
    }
};

// Helper Generate link WhatsApp pesan penagihan
const getWhatsAppUrl = (item) => {
    let cleanPhone = String(item.phone || '').replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.slice(1);
    }

    const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const currentMonthName = monthNames[filterMonth.value] || '';

    let text = '';
    if (drillDownModal.categoryKey === 'janji_bayar' && item.promise_date) {
        text = `Halo Kak *${item.name}*,\n\nKami dari Bagian Billing ISP mengonfirmasi komitmen janji bayar tagihan internet periode *${currentMonthName} ${filterYear.value}* sebesar *${formatRupiah(item.amount)}* yang dijadwalkan pada tanggal *${item.promise_date}*.\n\nMohon konfirmasi jika pembayaran telah dilakukan. Terima kasih atas kerja samanya! 🙏`;
    } else {
        text = `Halo Kak *${item.name}* (ID: ${item.customer_code}),\n\nKami mengingatkan tagihan internet *${item.package_name}* periode *${currentMonthName} ${filterYear.value}* sebesar *${formatRupiah(item.amount)}* dengan no invoice *${item.invoice_number || '-'}* saat ini belum terlunasi.\n\nMohon kesediaannya untuk melakukan pembayaran agar layanan tetap aktif lancar. Jika sudah membayar, abaikan pesan ini. Terima kasih! 🙏`;
    }

    const isMobile = typeof navigator !== 'undefined' && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    return isMobile
        ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`
        : `https://web.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(text)}`;
};
</script>

<style scoped>
@media print {
    body {
        background: white !important;
        color: black !important;
    }
    aside, header, nav, .print\:hidden {
        display: none !important;
    }
}
</style>

