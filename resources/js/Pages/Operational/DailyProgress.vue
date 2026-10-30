<template>
    <AppLayout title="Progress Harian Operasional" subtitle="Laporan Terpadu Gangguan PGS/PGK/PGJ, Instalasi, Survey, CBP, Pemeliharaan Jalur & Shift Teknisi">
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            
            <!-- TOP BAR: FILTER & ACTION CONTROLS -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    
                    <!-- Title & Date Context -->
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-500/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Progress Operasional Lapangan</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    Live Data
                                </span>
                            </div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ indonesianDate }} · <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ areaLabel }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Filters & Form -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        
                        <!-- Quick Date Buttons -->
                        <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs font-semibold">
                            <button
                                type="button"
                                @click="setQuickDate(-1)"
                                class="px-2.5 py-1 rounded-lg transition-all"
                                :class="filterDate === getLocalDateString(-1) 
                                    ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' 
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                title="Kemarin"
                            >
                                Kemarin
                            </button>
                            <button
                                type="button"
                                @click="setQuickDate(0)"
                                class="px-2.5 py-1 rounded-lg transition-all"
                                :class="filterDate === getLocalDateString(0) 
                                    ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' 
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                title="Hari Ini"
                            >
                                Hari Ini
                            </button>
                            <button
                                type="button"
                                @click="setQuickDate(1)"
                                class="px-2.5 py-1 rounded-lg transition-all"
                                :class="filterDate === getLocalDateString(1) 
                                    ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' 
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                title="Besok"
                            >
                                Besok
                            </button>
                        </div>

                        <!-- Date Picker -->
                        <div class="relative">
                            <input
                                type="date"
                                v-model="filterDate"
                                @change="applyFilter"
                                class="pl-9 pr-3 py-2 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none transition-all cursor-pointer"
                            />
                            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>

                        <!-- Area Selector -->
                        <div class="relative">
                            <select
                                v-model="filterArea"
                                @change="applyFilter"
                                class="pl-8 pr-8 py-2 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none appearance-none transition-all cursor-pointer"
                            >
                                <option v-if="canViewAllAreas !== false" value="">🌐 Semua Wilayah / Area</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

                        <!-- Tombol Tambah Kegiatan Perapihan / Cek Jalur Baru -->
                        <button
                            type="button"
                            @click="openCreateModal"
                            class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white shadow-md shadow-amber-500/20 transition-all cursor-pointer active:scale-98"
                            title="Tambah Kegiatan Perapihan atau Pengecekan Jalur ODP, ODC, Jalur Utama"
                        >
                            <span>🛠️</span>
                            <span class="hidden sm:inline">+ Jadwal Pemeliharaan Jalur</span>
                            <span class="sm:hidden">+ Jalur</span>
                        </button>

                        <!-- Refresh Button -->
                        <button
                            type="button"
                            @click="applyFilter"
                            :disabled="isLoading"
                            class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-xl transition-all disabled:opacity-50"
                            title="Segarkan Data"
                        >
                            <svg class="w-4 h-4" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>

                </div>
            </div>

            <!-- STATS KPI SUMMARY CARDS (6 KOLOM) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                
                <!-- 1. Gangguan Card -->
                <div 
                    @click="activeTab = 'gangguan'"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="activeTab === 'gangguan' 
                        ? 'bg-rose-50/70 dark:bg-rose-950/40 border-rose-400 dark:border-rose-700 ring-2 ring-rose-200 dark:ring-rose-900' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-rose-300'"
                >
                    <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">🆘 Gangguan</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/60">{{ summary.gangguan || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.gangguan || 0 }}
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex-wrap">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.gangguan_done || 0 }} Selesai</span>
                        <span v-if="summary.gangguan_in_progress > 0" class="text-blue-600 font-bold">· 🛠️ {{ summary.gangguan_in_progress }} Proses</span>
                        <span>·</span>
                        <span class="text-amber-600 font-bold">⏳ {{ summary.gangguan_pending || 0 }} Pending</span>
                    </div>
                </div>

                <!-- 2. Pemasangan Card -->
                <div 
                    @click="activeTab = 'pemasangan'"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="activeTab === 'pemasangan' 
                        ? 'bg-blue-50/70 dark:bg-blue-950/40 border-blue-400 dark:border-blue-700 ring-2 ring-blue-200 dark:ring-blue-900' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-blue-300'"
                >
                    <div class="flex items-center justify-between text-blue-600 dark:text-blue-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">🛜 Pemasangan</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-blue-100 dark:bg-blue-900/60">{{ summary.pemasangan || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.pemasangan || 0 }}
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex-wrap">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.pemasangan_done || 0 }} Selesai</span>
                        <span v-if="summary.pemasangan_in_progress > 0" class="text-blue-600 font-bold">· 🛠️ {{ summary.pemasangan_in_progress }}</span>
                        <span>·</span>
                        <span class="text-amber-600 font-bold">⏳ {{ summary.pemasangan_pending || 0 }} Pending</span>
                    </div>
                </div>

                <!-- 3. Survey Card -->
                <div 
                    @click="activeTab = 'survey'"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="activeTab === 'survey' 
                        ? 'bg-amber-50/70 dark:bg-amber-950/40 border-amber-400 dark:border-amber-700 ring-2 ring-amber-200 dark:ring-amber-900' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-amber-300'"
                >
                    <div class="flex items-center justify-between text-amber-600 dark:text-amber-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">🔎 Survey PSB</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/60">{{ summary.survey || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.survey || 0 }}
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex-wrap">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.survey_feasible || 0 }} Selesai</span>
                        <span>·</span>
                        <span class="text-amber-600 font-bold">⏳ {{ summary.survey_pending || 0 }} Tunggu</span>
                    </div>
                </div>

                <!-- 4. Cabut CBP Card -->
                <div 
                    @click="activeTab = 'cbp'"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="activeTab === 'cbp' 
                        ? 'bg-purple-50/70 dark:bg-purple-950/40 border-purple-400 dark:border-purple-700 ring-2 ring-purple-200 dark:ring-purple-900' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-purple-300'"
                >
                    <div class="flex items-center justify-between text-purple-600 dark:text-purple-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">🔌 Pencabutan</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-purple-100 dark:bg-purple-900/60">{{ summary.cbp || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.cbp || 0 }}
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex-wrap">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.cbp_done || 0 }} Selesai</span>
                        <span>·</span>
                        <span class="text-amber-600 font-bold">⏳ {{ summary.cbp_pending || 0 }} Pending</span>
                    </div>
                </div>

                <!-- 5. PERAPIHAN & PEMELIHARAAN JALUR CARD (BARU) -->
                <div 
                    @click="activeTab = 'maintenance'"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="activeTab === 'maintenance' 
                        ? 'bg-teal-50/70 dark:bg-teal-950/40 border-teal-400 dark:border-teal-700 ring-2 ring-teal-200 dark:ring-teal-900' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-teal-300'"
                >
                    <div class="flex items-center justify-between text-teal-600 dark:text-teal-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">🛠️ Pemeliharaan Jalur</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-teal-100 dark:bg-teal-900/60">{{ summary.maintenance || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.maintenance || 0 }}
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex-wrap">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.maintenance_done || 0 }} Selesai</span>
                        <span v-if="summary.maintenance_in_progress > 0" class="text-blue-600 font-bold">· 🛠️ {{ summary.maintenance_in_progress }}</span>
                        <span>·</span>
                        <span class="text-amber-600 font-bold">⏳ {{ summary.maintenance_pending || 0 }} Tunggu</span>
                    </div>
                </div>

                <!-- 6. Status Petugas & Shift Card -->
                <div 
                    @click="activeTab = 'teknisi'"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md col-span-2 sm:col-span-1"
                    :class="activeTab === 'teknisi' 
                        ? 'bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-400 dark:border-emerald-700 ring-2 ring-emerald-200 dark:ring-emerald-900' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-emerald-300'"
                >
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">👷 Shift Petugas</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/60">{{ totalStaffDutyCount }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ totalStaffDutyCount }} Orang
                    </div>
                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 mt-1 truncate">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">Tek: {{ tekStatus?.on_duty?.length || 0 }} (Off: {{ tekStatus?.off?.length || 0 }})</span>
                        <span>·</span>
                        <span class="text-teal-600 dark:text-teal-400 font-bold">Mnt: {{ maintStatus?.on_duty?.length || 0 }} (Off: {{ maintStatus?.off?.length || 0 }})</span>
                    </div>
                </div>

            </div>

            <!-- MAIN TWO COLUMNS LAYOUT -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- LEFT COLUMN: DETAIL DATA TABS (7 COLS) -->
                <div class="lg:col-span-7 space-y-4">
                    
                    <!-- Tabs Navigation Header -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-1.5 shadow-2xs flex items-center overflow-x-auto scrollbar-none gap-1">
                        <button
                            v-for="t in tabs"
                            :key="t.key"
                            @click="activeTab = t.key"
                            type="button"
                            :class="[
                                'flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl whitespace-nowrap transition-all duration-200 cursor-pointer',
                                activeTab === t.key 
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' 
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                            ]"
                        >
                            <span>{{ t.icon }}</span>
                            <span>{{ t.label }}</span>
                            <span 
                                class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                                :class="activeTab === t.key ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
                            >
                                {{ t.count }}
                            </span>
                        </button>
                    </div>

                    <!-- TAB 1: GANGGUAN (PGS / PGK / PGJ) -->
                    <div v-if="activeTab === 'gangguan'" class="space-y-3">
                        <div v-if="gangguanList.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl mb-3">
                                🌴
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Nihil Gangguan Hari Ini</h3>
                            <p class="text-xs text-slate-400 mt-1">Tidak ada komplain atau tiket gangguan yang tercatat untuk tanggal dan wilayah yang dipilih.</p>
                        </div>

                        <div 
                            v-for="(item, idx) in gangguanList" 
                            :key="'gangguan-' + idx"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 hover:shadow-md transition-all space-y-2.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 text-xs font-black flex items-center justify-center">
                                        {{ idx + 1 }}
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 flex-wrap">
                                            <span>{{ item.customer_name }}</span>
                                            <span 
                                                class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider" 
                                                :class="(item.type || item.source) === 'PGS' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300' : ((item.type || item.source) === 'PGK' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border border-purple-300')"
                                            >
                                                {{ item.type || item.source }}
                                            </span>
                                        </h4>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span class="font-mono font-bold">{{ item.ticket_number }}</span>
                                            <span class="text-slate-300">·</span>
                                            <span 
                                                class="font-semibold flex items-center gap-1"
                                                :class="item.technicians === 'Belum Ditugaskan' ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            >
                                                👷 {{ item.technicians }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0 border"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : (item.is_in_progress ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800' : (item.is_rescheduled ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800'))"
                                >
                                    {{ item.status_label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl">
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Alamat</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ item.address || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">ODP / Posisi</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ item.odp || '-' }}</span>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Kendala Gangguan</span>
                                    <span class="text-slate-900 dark:text-slate-100 font-medium">{{ item.issue || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Petugas Teknisi Ditugaskan</span>
                                    <span 
                                        class="font-bold flex items-center gap-1 text-xs"
                                        :class="item.technicians === 'Belum Ditugaskan' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'"
                                    >
                                        👷 {{ item.technicians || '-' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Durasi Pengerjaan</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-mono">{{ item.duration || '-' }}</span>
                                </div>
                            </div>

                            <!-- Actions Row -->
                            <div class="flex items-center justify-between pt-1 text-xs">
                                <span class="text-slate-400 flex items-center gap-1">
                                    📞 <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ item.phone }}</strong>
                                </span>
                                <a 
                                    v-if="item.phone && item.phone !== '-'"
                                    :href="`https://wa.me/${cleanPhone(item.phone)}`" 
                                    target="_blank"
                                    class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <span>Chat Pelanggan</span> ↗
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PEMASANGAN BARU (INSTALASI) -->
                    <div v-if="activeTab === 'pemasangan'" class="space-y-3">
                        <div v-if="pemasanganList.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-3xl mb-3">
                                🛜
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak Ada Pemasangan Baru</h3>
                            <p class="text-xs text-slate-400 mt-1">Tidak ada jadwal atau instalasi pemasangan baru pada tanggal ini.</p>
                        </div>

                        <div 
                            v-for="(item, idx) in pemasanganList" 
                            :key="'pasang-' + idx"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 hover:shadow-md transition-all space-y-2.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 text-xs font-black flex items-center justify-center">
                                        {{ idx + 1 }}
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ item.customer_name }}</h4>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span class="font-mono font-bold">{{ item.customer_code }}</span>
                                            <span class="text-slate-300">·</span>
                                            <span 
                                                class="font-semibold flex items-center gap-1"
                                                :class="item.technicians === 'Belum Ditugaskan' ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            >
                                                👷 {{ item.technicians }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0 border"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : (item.is_in_progress ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800' : (item.is_rescheduled ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800'))"
                                >
                                    {{ item.status_label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl">
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Data Detail & Alamat</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ item.detail || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">ODP</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ item.odp || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Waktu / Jadwal Pengerjaan</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-mono">{{ item.duration || '-' }}</span>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Petugas Teknisi Ditugaskan</span>
                                    <span 
                                        class="font-bold flex items-center gap-1 text-xs"
                                        :class="item.technicians === 'Belum Ditugaskan' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'"
                                    >
                                        👷 {{ item.technicians || '-' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions Row -->
                            <div class="flex items-center justify-between pt-1 text-xs">
                                <span class="text-slate-400 flex items-center gap-1">
                                    📞 <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ item.phone }}</strong>
                                </span>
                                <a 
                                    v-if="item.phone && item.phone !== '-'"
                                    :href="`https://wa.me/${cleanPhone(item.phone)}`" 
                                    target="_blank"
                                    class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <span>Chat Pelanggan</span> ↗
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: SURVEY PELANGGAN BARU -->
                    <div v-if="activeTab === 'survey'" class="space-y-3">
                        <div v-if="surveyList.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center text-3xl mb-3">
                                🔎
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak Ada Survey Hari Ini</h3>
                            <p class="text-xs text-slate-400 mt-1">Belum ada aktivitas survey calon pelanggan baru pada tanggal ini.</p>
                        </div>

                        <div 
                            v-for="(item, idx) in surveyList" 
                            :key="'survey-' + idx"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 hover:shadow-md transition-all space-y-2.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 text-xs font-black flex items-center justify-center">
                                        {{ idx + 1 }}
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ item.customer_name }}</h4>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span>Calon Pelanggan Baru</span>
                                            <span class="text-slate-300">·</span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-300">👷 {{ item.technicians }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0 border"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : (item.is_rescheduled ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800')"
                                >
                                    {{ item.status_label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl">
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Data Detail & Alamat</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ item.detail || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Target ODP</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ item.odp || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Petugas Survey Ditugaskan</span>
                                    <span class="text-slate-900 dark:text-white font-bold flex items-center gap-1">
                                        👷 {{ item.technicians || '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1 text-xs">
                                <span class="text-slate-400 flex items-center gap-1">
                                    📞 <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ item.phone }}</strong>
                                </span>
                                <a 
                                    v-if="item.phone && item.phone !== '-'"
                                    :href="`https://wa.me/${cleanPhone(item.phone)}`" 
                                    target="_blank"
                                    class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <span>Chat Calon</span> ↗
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: CABUT PERANGKAT (CBP) -->
                    <div v-if="activeTab === 'cbp'" class="space-y-3">
                        <div v-if="cbpList.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center text-3xl mb-3">
                                🔌
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Nihil Pencabutan Perangkat</h3>
                            <p class="text-xs text-slate-400 mt-1">Tidak ada jadwal atau permintaan pencabutan perangkat (CBP) pada tanggal ini.</p>
                        </div>

                        <div 
                            v-for="(item, idx) in cbpList" 
                            :key="'cbp-' + idx"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 hover:shadow-md transition-all space-y-2.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-purple-100 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 text-xs font-black flex items-center justify-center">
                                        {{ idx + 1 }}
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ item.customer_name }}</h4>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span class="font-mono font-bold">{{ item.customer_code || 'CBP' }}</span>
                                            <span class="text-slate-300">·</span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-300">👷 {{ item.technicians }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0 border"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : (item.is_in_progress ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800' : (item.is_rescheduled ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800'))"
                                >
                                    {{ item.status_label }}
                                </span>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl text-xs space-y-1">
                                <div><strong class="text-slate-400 text-[10px] block uppercase">Data Detail</strong> {{ item.detail }}</div>
                                <div class="text-slate-600 dark:text-slate-400 pt-1 flex items-center justify-between flex-wrap gap-2">
                                    <span>👷 Petugas Ditugaskan: <strong class="text-slate-900 dark:text-white">{{ item.technicians || '-' }}</strong></span>
                                    <span v-if="item.duration && item.duration !== '-'">⏱️ Durasi: <strong class="font-mono text-slate-800 dark:text-slate-200">{{ item.duration }}</strong></span>
                                    <span v-if="item.phone && item.phone !== '-'">📞 {{ item.phone }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: PERAPIHAN & PENGECEKAN JALUR (BARU) -->
                    <div v-if="activeTab === 'maintenance'" class="space-y-3">
                        <!-- Sub-filter: Semua Tugas vs Tugas Saya -->
                        <div class="flex items-center justify-between gap-2 pb-1 flex-wrap">
                            <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs font-semibold">
                                <button
                                    type="button"
                                    @click="filterOnlyMyTasks = false"
                                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer"
                                    :class="!filterOnlyMyTasks ? 'bg-white dark:bg-slate-900 text-teal-700 dark:text-teal-400 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    👥 Semua Petugas ({{ maintenanceList.length }})
                                </button>
                                <button
                                    type="button"
                                    @click="filterOnlyMyTasks = true"
                                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1"
                                    :class="filterOnlyMyTasks ? 'bg-teal-600 text-white font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    <span>👤 Tugas Saya</span>
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="filterOnlyMyTasks ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700'">
                                        {{ myMaintenanceList.length }}
                                    </span>
                                </button>
                            </div>

                            <button
                                type="button"
                                @click="openCreateModal"
                                class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-teal-600 hover:bg-teal-700 text-white shadow-xs transition-all cursor-pointer inline-flex items-center gap-1"
                            >
                                <span>+</span>
                                <span>Jadwal Jalur Baru</span>
                            </button>
                        </div>

                        <div v-if="filteredMaintenanceList.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-3xl mb-3">
                                🛠️
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                {{ filterOnlyMyTasks ? 'Tidak Ada Tugas Pemeliharaan yang Ditugaskan ke Anda' : 'Nihil Pemeliharaan & Perapihan Jalur Hari Ini' }}
                            </h3>
                            <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                                {{ filterOnlyMyTasks ? 'Anda tidak memiliki penugasan perapihan kabel kendur, ODP, ODC, atau jalur utama pada tanggal ini.' : 'Belum ada jadwal pemeliharaan rutin, perapihan kabel kendur, atau inspeksi jalur ODP/ODC/Jalur Utama pada tanggal ini.' }}
                            </p>
                            <div class="mt-4" v-if="!filterOnlyMyTasks">
                                <button
                                    type="button"
                                    @click="openCreateModal"
                                    class="px-4 py-2 text-xs font-bold rounded-xl bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 transition-all cursor-pointer inline-flex items-center gap-1.5"
                                >
                                    <span>+</span>
                                    <span>Tambah Kegiatan Jalur Sekarang</span>
                                </button>
                            </div>
                        </div>

                        <div 
                            v-for="(item, idx) in filteredMaintenanceList" 
                            :key="'maintenance-' + idx"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 hover:shadow-md transition-all space-y-2.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-teal-100 dark:bg-teal-950/70 text-teal-700 dark:text-teal-300 text-xs font-black flex items-center justify-center">
                                        {{ idx + 1 }}
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 flex-wrap">
                                            <span>{{ item.title }}</span>
                                            <span 
                                                class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider border"
                                                :class="getCategoryBadgeClass(item.category)"
                                            >
                                                {{ item.category_icon }} {{ item.category_label }}
                                            </span>
                                        </h4>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ item.maintenance_number }}</span>
                                            <span class="text-slate-300">·</span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                                👷 {{ item.technicians }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0 border"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : (item.is_in_progress ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800' : (item.is_rescheduled ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800'))"
                                >
                                    {{ item.status_label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl">
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Wilayah / Lokasi Rute</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ item.location || '-' }}</span>
                                    <span v-if="item.area_name && item.area_name !== '-'" class="text-[10px] text-slate-400 block">({{ item.area_name }})</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Waktu / Jadwal Pengerjaan</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-mono font-semibold">{{ item.duration || '-' }}</span>
                                </div>
                                <div v-if="item.odp && item.odp !== '-'" class="sm:col-span-1">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Target ODP</span>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold font-mono">📦 {{ item.odp }}</span>
                                </div>
                                <div v-if="item.odc && item.odc !== '-'" class="sm:col-span-1">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Target ODC</span>
                                    <span class="text-purple-600 dark:text-purple-400 font-bold font-mono">🗄️ {{ item.odc }}</span>
                                </div>
                                <div v-if="item.optical_power_after || item.optical_power_before" class="sm:col-span-2">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Hasil Pengukuran Redaman (OPM)</span>
                                    <span class="text-emerald-700 dark:text-emerald-300 font-bold font-mono bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                        ⚡ {{ item.optical_power_after || item.optical_power_before }}
                                    </span>
                                </div>
                                <div v-if="item.description" class="sm:col-span-2">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Rencana Kerja / Instruksi</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ item.description }}</span>
                                </div>
                                <div v-if="item.action_taken" class="sm:col-span-2 bg-emerald-50/50 dark:bg-emerald-950/30 p-2 rounded-lg border border-emerald-100 dark:border-emerald-900/40">
                                    <span class="text-emerald-800 dark:text-emerald-300 text-[10px] font-bold block uppercase">Tindakan Lapangan / Hasil</span>
                                    <span class="text-slate-800 dark:text-slate-200 font-medium">{{ item.action_taken }}</span>
                                </div>
                                <!-- Bekal & Pemakaian Material Petugas -->
                                <div v-if="item.materials && item.materials.length > 0" class="sm:col-span-2 bg-amber-50/60 dark:bg-amber-950/30 p-2.5 rounded-xl border border-amber-200/80 dark:border-amber-900/50 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-amber-800 dark:text-amber-300 text-[10px] font-bold block uppercase flex items-center gap-1">
                                            📦 Bekal & Pemakaian Material Petugas
                                        </span>
                                        <span class="text-[10px] text-amber-700 dark:text-amber-400 font-medium">
                                            {{ item.materials.length }} Item
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5 pt-0.5">
                                        <div v-for="mat in item.materials" :key="mat.id" class="inline-flex items-center gap-1 text-[11px] bg-white dark:bg-slate-800 px-2 py-0.5 rounded-md border border-amber-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 shadow-2xs">
                                            <span class="font-bold text-amber-600 dark:text-amber-400">{{ mat.material?.name || mat.name || 'Material' }}:</span>
                                            <span class="font-mono font-semibold">{{ mat.quantity_prepared }} {{ mat.unit }}</span>
                                            <span v-if="parseFloat(mat.quantity_used) > 0" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">(Pakai: {{ mat.quantity_used }})</span>
                                            <span v-if="parseFloat(mat.quantity_returned) > 0" class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold">(Kembali: {{ mat.quantity_returned }})</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Row -->
                            <div class="flex items-center justify-between pt-1 text-xs flex-wrap gap-2">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        v-if="item.photo_before || item.photo_after"
                                        type="button"
                                        @click="viewPhotos(item)"
                                        class="px-2.5 py-1 text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                                    >
                                        <span>📷</span>
                                        <span>Lihat Foto</span>
                                    </button>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="openQuickUpdateModal(item)"
                                        class="px-3 py-1 text-[11px] font-bold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/50 hover:bg-teal-100 rounded-lg transition-colors flex items-center gap-1 cursor-pointer border border-teal-200 dark:border-teal-800"
                                    >
                                        <span>📝</span>
                                        <span>Update Status / Laporan</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteMaintenance(item)"
                                        class="p-1 text-slate-400 hover:text-rose-600 rounded-lg transition-colors cursor-pointer"
                                        title="Hapus Kegiatan"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: STATUS TEKNISI & ROSTER SHIFT -->
                    <!-- TAB 6: STATUS SHIFT & PETUGAS LAPANGAN -->
                    <div v-if="activeTab === 'teknisi'" class="space-y-4">
                        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 space-y-6">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                        <span>👷‍♂️</span>
                                        <span>Status Shift Teknisi & Maintenance</span>
                                    </h3>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Pemantauan kehadiran & shift tugas terpisah antara Teknisi Lapangan dan Tim Maintenance Jalur</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    🏢 {{ areaLabel }}
                                </span>
                            </div>

                            <!-- BAGIAN 1: DIVISI TEKNISI LAPANGAN -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-black text-emerald-800 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1.5">
                                        <span class="p-1 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700">👷</span>
                                        <span>Divisi Teknisi Lapangan</span>
                                    </h4>
                                    <span class="text-[10px] font-bold text-slate-400">Total Tugas: {{ tekStatus?.on_duty?.length || 0 }} Orang</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <!-- Teknisi On Duty -->
                                    <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 uppercase tracking-wider">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Teknisi Bertugas ({{ tekStatus?.on_duty?.length || 0 }})
                                            </h5>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="(name, nIdx) in tekStatus?.on_duty" 
                                                :key="'tek-duty-' + nIdx"
                                                class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-800 text-xs font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700 shadow-2xs"
                                            >
                                                👷 {{ name }}
                                            </span>
                                            <span v-if="!tekStatus?.on_duty?.length" class="text-xs text-slate-400 italic">Tidak ada teknisi bertugas</span>
                                        </div>
                                    </div>

                                    <!-- Teknisi Shift Malam -->
                                    <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-xs font-bold text-indigo-800 dark:text-indigo-300 flex items-center gap-1.5 uppercase tracking-wider">
                                                <span>🌙</span>
                                                Shift Malam ({{ tekStatus?.night?.length || 0 }})
                                            </h5>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="(name, mIdx) in tekStatus?.night" 
                                                :key="'tek-night-' + mIdx"
                                                class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-800 text-xs font-bold text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 shadow-2xs"
                                            >
                                                🌙 {{ name }}
                                            </span>
                                            <span v-if="!tekStatus?.night?.length" class="text-xs text-slate-400 italic">- (Nihil)</span>
                                        </div>
                                    </div>

                                    <!-- Teknisi Libur / Off / Cuti -->
                                    <div class="p-4 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wider">
                                                <span>🏖️</span>
                                                Teknisi Libur / Cuti ({{ tekStatus?.off?.length || 0 }})
                                            </h5>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="(name, oIdx) in tekStatus?.off" 
                                                :key="'tek-off-' + oIdx"
                                                class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 shadow-2xs"
                                            >
                                                🏖️ {{ name }}
                                            </span>
                                            <span v-if="!tekStatus?.off?.length" class="text-xs text-slate-400 italic">- (Nihil)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BAGIAN 2: DIVISI MAINTENANCE JALUR -->
                            <div class="space-y-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-black text-teal-800 dark:text-teal-300 uppercase tracking-wider flex items-center gap-1.5">
                                        <span class="p-1 rounded-lg bg-teal-100 dark:bg-teal-950 text-teal-700">🛠️</span>
                                        <span>Divisi Maintenance Jalur & Pemeliharaan Jaringan</span>
                                    </h4>
                                    <span class="text-[10px] font-bold text-slate-400">Total Tugas: {{ maintStatus?.on_duty?.length || 0 }} Orang</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <!-- Maintenance Bertugas -->
                                    <div class="p-4 rounded-2xl bg-teal-50/70 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-xs font-bold text-teal-800 dark:text-teal-300 flex items-center gap-1.5 uppercase tracking-wider">
                                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                                Maintenance Bertugas ({{ maintStatus?.on_duty?.length || 0 }})
                                            </h5>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="(name, mnIdx) in maintStatus?.on_duty" 
                                                :key="'maint-duty-' + mnIdx"
                                                class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-800 text-xs font-bold text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-700 shadow-2xs"
                                            >
                                                🛠️ {{ name }}
                                            </span>
                                            <span v-if="!maintStatus?.on_duty?.length" class="text-xs text-slate-400 italic">Tidak ada maintenance bertugas</span>
                                        </div>
                                    </div>

                                    <!-- Maintenance Shift Malam -->
                                    <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-xs font-bold text-indigo-800 dark:text-indigo-300 flex items-center gap-1.5 uppercase tracking-wider">
                                                <span>🌙</span>
                                                Maintenance Shift Malam ({{ maintStatus?.night?.length || 0 }})
                                            </h5>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="(name, mnMIdx) in maintStatus?.night" 
                                                :key="'maint-night-' + mnMIdx"
                                                class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-800 text-xs font-bold text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 shadow-2xs"
                                            >
                                                🌙 {{ name }}
                                            </span>
                                            <span v-if="!maintStatus?.night?.length" class="text-xs text-slate-400 italic">- (Nihil)</span>
                                        </div>
                                    </div>

                                    <!-- Maintenance Libur / Off / Cuti -->
                                    <div class="p-4 rounded-2xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/60 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-xs font-bold text-rose-800 dark:text-rose-300 flex items-center gap-1.5 uppercase tracking-wider">
                                                <span>🏖️</span>
                                                Maintenance Libur / Cuti ({{ maintStatus?.off?.length || 0 }})
                                            </h5>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="(name, mnOIdx) in maintStatus?.off" 
                                                :key="'maint-off-' + mnOIdx"
                                                class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-800 text-xs font-bold text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-700 shadow-2xs"
                                            >
                                                🏖️ {{ name }}
                                            </span>
                                            <span v-if="!maintStatus?.off?.length" class="text-xs text-slate-400 italic">- (Nihil)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: SMART WHATSAPP BROADCAST PREVIEW & ACTION (5 COLS) -->
                <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-4">
                    
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-md p-5 space-y-4">
                        
                        <!-- Header Preview -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-base shadow-sm">
                                    📱
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Format Broadcast WhatsApp</h3>
                                    <p class="text-[11px] text-slate-400">Siap kirim ke grup operasional teknisi</p>
                                </div>
                            </div>
                            
                            <!-- Gateway Status Pill -->
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1"
                                :class="waGatewayStatus ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                                :title="waGatewayStatus ? 'WhatsApp Gateway Baileys Aktif & Terhubung' : 'WhatsApp Gateway Belum Terhubung'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="waGatewayStatus ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                {{ waGatewayStatus ? 'GW Online' : 'GW Standby' }}
                            </span>
                        </div>

                        <!-- Editable Note Field -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                📝 Custom Note / Pesan Semangat:
                            </label>
                            <input
                                type="text"
                                v-model="customNote"
                                placeholder="Tetap semangat dan jaga keselamatan kerja! 💪"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Live Textarea / WhatsApp Bubble Box -->
                        <div class="relative rounded-2xl bg-[#0b141a]/95 text-slate-100 p-4 font-mono text-xs shadow-inner border border-slate-800">
                            <div class="flex items-center justify-between text-[10px] text-emerald-400 pb-2 mb-2 border-b border-emerald-950 font-sans font-bold">
                                <span>PREVIEW PESAN WHATSAPP</span>
                                <span>{{ activeWaText.length }} KARAKTER</span>
                            </div>
                            <pre class="whitespace-pre-wrap leading-relaxed max-h-[380px] overflow-y-auto pr-1 scrollbar-thin select-all font-sans text-xs text-slate-200">{{ activeWaText }}</pre>
                        </div>

                        <!-- 3 Action Buttons -->
                        <div class="space-y-2 pt-1">
                            
                            <!-- 1. Salin ke Clipboard -->
                            <button
                                type="button"
                                @click="copyWhatsAppText"
                                class="w-full py-3 px-4 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-md active:scale-98 cursor-pointer"
                                :class="isCopied 
                                    ? 'bg-emerald-600 text-white shadow-emerald-500/20' 
                                    : 'bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-slate-900/10'"
                            >
                                <svg v-if="!isCopied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                </svg>
                                <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ isCopied ? 'Berhasil Disalin ke Clipboard!' : 'Salin Format WA (1-Click Copy)' }}</span>
                            </button>

                            <!-- 2. Buka di WhatsApp Web / App Direct -->
                            <a
                                :href="`https://api.whatsapp.com/send?text=${encodeURIComponent(activeWaText)}`"
                                target="_blank"
                                class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition-all cursor-pointer"
                            >
                                <span>💬 Buka Langsung di WhatsApp Web / Desktop</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>

                            <!-- 3. Modal Kirim Otomatis via Gateway WA -->
                            <button
                                type="button"
                                @click="openSendModal"
                                class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-md shadow-emerald-600/20 transition-all cursor-pointer"
                            >
                                <span>🚀 Kirim Otomatis via WhatsApp Gateway</span>
                            </button>

                        </div>

                        <!-- Info Tip -->
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 text-[11px] text-slate-500 dark:text-slate-400 flex items-start gap-2">
                            <span class="text-base shrink-0">💡</span>
                            <span>Format di atas dirancang otomatis rapi dengan bold, bullet point, emoji, dan pembagian per teknisi sehingga siap dibaca oleh tim teknisi lapangan di grup WA.</span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MODAL 1: TAMBAH KEGIATAN PERAPIHAN & CEK JALUR BARU             -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div 
            v-if="showCreateModal" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto"
            @click.self="showCreateModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 my-auto animate-in fade-in zoom-in-95 duration-200 overflow-hidden">
                
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            🛠️
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tambah Kegiatan Pemeliharaan & Jalur</h3>
                            <p class="text-[11px] text-slate-400">Jadwalkan perapihan kabel, inspeksi ODP, ODC, atau jalur utama</p>
                        </div>
                    </div>
                    <button 
                        @click="showCreateModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitCreateMaintenance" class="flex flex-col min-h-0 flex-1 overflow-hidden">
                    <div class="p-6 space-y-3.5 overflow-y-auto flex-1 overscroll-contain">
                    
                    <!-- 1. Kategori Kegiatan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Jenis Kegiatan Pemeliharaan <span class="text-rose-500">*</span>:
                        </label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label 
                                class="p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-all"
                                :class="createForm.category === 'perapihan_jalur' ? 'bg-amber-50 dark:bg-amber-950/50 border-amber-500 font-bold text-amber-900 dark:text-amber-200' : 'border-slate-200 dark:border-slate-700'"
                            >
                                <input type="radio" v-model="createForm.category" value="perapihan_jalur" class="sr-only" />
                                <span>🧹 Perapihan Jalur & Kabel</span>
                            </label>
                            <label 
                                class="p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-all"
                                :class="createForm.category === 'pengecekan_odp' ? 'bg-blue-50 dark:bg-blue-950/50 border-blue-500 font-bold text-blue-900 dark:text-blue-200' : 'border-slate-200 dark:border-slate-700'"
                            >
                                <input type="radio" v-model="createForm.category" value="pengecekan_odp" class="sr-only" />
                                <span>📦 Pengecekan Jalur ODP</span>
                            </label>
                            <label 
                                class="p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-all"
                                :class="createForm.category === 'pengecekan_odc' ? 'bg-purple-50 dark:bg-purple-950/50 border-purple-500 font-bold text-purple-900 dark:text-purple-200' : 'border-slate-200 dark:border-slate-700'"
                            >
                                <input type="radio" v-model="createForm.category" value="pengecekan_odc" class="sr-only" />
                                <span>🗄️ Pengecekan Jalur ODC</span>
                            </label>
                            <label 
                                class="p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-all"
                                :class="createForm.category === 'pengecekan_jalur_utama' ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-500 font-bold text-emerald-900 dark:text-emerald-200' : 'border-slate-200 dark:border-slate-700'"
                            >
                                <input type="radio" v-model="createForm.category" value="pengecekan_jalur_utama" class="sr-only" />
                                <span>🛣️ Pengecekan Jalur Utama</span>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Judul Tugas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Judul / Deskripsi Singkat Pekerjaan <span class="text-rose-500">*</span>:
                        </label>
                        <input
                            type="text"
                            v-model="createForm.title"
                            placeholder="Contoh: Perapihan kabel kendur di depan Pasar Sirnajaya"
                            required
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                        />
                    </div>

                    <!-- 3. Tanggal & Jam Jadwal -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Tanggal Pelaksanaan:
                            </label>
                            <input
                                type="date"
                                v-model="createForm.scheduled_date"
                                required
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Jam Jadwal (Opsional):
                            </label>
                            <input
                                type="time"
                                v-model="createForm.scheduled_time"
                                placeholder="09:00"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- 4. Wilayah & Target Aset -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Wilayah / Area:
                            </label>
                            <select
                                v-model="createForm.area_id"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="">Semua Wilayah</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>

                        <!-- Dropdown ODP jika kategori pengecekan ODP -->
                        <div v-if="createForm.category === 'pengecekan_odp'">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Target ODP Terkait:
                            </label>
                            <select
                                v-model="createForm.odp_id"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="">Pilih ODP (Opsional)</option>
                                <option v-for="o in filteredOdps" :key="o.id" :value="o.id">{{ o.name }}</option>
                            </select>
                        </div>

                        <!-- Dropdown ODC jika kategori pengecekan ODC -->
                        <div v-else-if="createForm.category === 'pengecekan_odc'">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Target ODC Terkait:
                            </label>
                            <select
                                v-model="createForm.odc_id"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="">Pilih ODC (Opsional)</option>
                                <option v-for="c in filteredOdcs" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <!-- Lokasi / Rute Patokan jika Jalur Utama atau Perapihan -->
                        <div v-else>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Segmen Rute / Patokan Lokasi:
                            </label>
                            <input
                                type="text"
                                v-model="createForm.location_name"
                                placeholder="Contoh: Rute Sirnajaya KM 2 - 4"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- 5. Pilih Petugas Ditugaskan (Teknisi / Maintenance) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Petugas Ditugaskan (Teknisi / Maintenance):
                            </label>
                            <button
                                v-if="createForm.area_id"
                                type="button"
                                @click="showAllAreaTechs = !showAllAreaTechs"
                                class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 hover:underline cursor-pointer"
                            >
                                {{ showAllAreaTechs ? 'Filter Sesuai Area' : 'Tampilkan Semua Area' }}
                            </button>
                        </div>
                        <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 max-h-36 overflow-y-auto space-y-1">
                            <div v-if="filteredTechnicians.length" class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <label 
                                    v-for="tech in filteredTechnicians" 
                                    :key="tech.id"
                                    class="flex items-center justify-between gap-1.5 p-1.5 rounded-lg text-xs cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition-colors"
                                >
                                    <div class="flex items-center gap-1.5 truncate">
                                        <input 
                                            type="checkbox" 
                                            :value="tech.id" 
                                            v-model="createForm.technician_ids"
                                            class="rounded text-amber-600 focus:ring-amber-500"
                                        />
                                        <span class="truncate font-medium text-slate-800 dark:text-slate-200">{{ tech.name }}</span>
                                    </div>
                                    <span 
                                        class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase shrink-0"
                                        :class="tech.is_maintenance ? 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300 border border-teal-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 border border-slate-300'"
                                    >
                                        {{ tech.role_label || (tech.is_maintenance ? 'Maintenance' : 'Teknisi') }}
                                    </span>
                                </label>
                            </div>
                            <p v-else class="text-xs text-slate-400 italic py-1 text-center">Belum ada petugas terdaftar pada area ini.</p>
                        </div>
                    </div>

                    <!-- Persiapan Material Petugas Lapangan (Bekal Kerja) -->
                    <div class="space-y-2 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase flex items-center gap-1.5">
                                    <span>📦</span>
                                    <span>Persiapan Bekal Material Petugas</span>
                                </label>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Siapkan kabel dropcore, span clamp, fast connector, splitter, atau closure dari gudang</p>
                            </div>
                            <button 
                                type="button" 
                                @click="addMaterialRow" 
                                class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:text-amber-700 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 px-2.5 py-1.5 rounded-xl border border-amber-200 dark:border-amber-800 flex items-center gap-1 transition-colors cursor-pointer"
                            >
                                <span>+ Tambah Material</span>
                            </button>
                        </div>

                        <div v-if="createForm.materials && createForm.materials.length > 0" class="space-y-2 pt-1">
                            <div v-for="(item, idx) in createForm.materials" :key="idx" class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 space-y-1.5 shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <select 
                                            v-model="item.selected_key" 
                                            @change="onMaterialItemChange(item)"
                                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 text-xs py-1.5 px-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-amber-500 truncate" 
                                            required
                                        >
                                            <option value="">-- Pilih Material dari Inventory Gudang --</option>
                                            <option v-for="ord in availableMaterialItems" :key="ord.unique_key" :value="ord.unique_key">
                                                {{ ord.name }} — Stok: {{ ord.area_stock }} {{ ord.unit }} {{ ord.trx_number ? '(PO: ' + ord.trx_number + ')' : '' }} [{{ ord.area_name }}]
                                            </option>
                                        </select>
                                    </div>

                                    <div class="w-20 shrink-0">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0.01" 
                                            :max="item.max_stock || undefined"
                                            v-model.number="item.quantity" 
                                            @input="validateMaterialQty(item)"
                                            placeholder="Qty" 
                                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 text-xs py-1.5 text-center font-bold text-amber-700 dark:text-amber-400 bg-amber-50/50 dark:bg-amber-950/40 focus:border-amber-500" 
                                            required
                                        />
                                    </div>

                                    <span class="text-xs font-semibold text-slate-500 w-10 text-center truncate shrink-0">{{ item.unit || '-' }}</span>

                                    <button type="button" @click="removeMaterialRow(idx)" class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition-colors shrink-0 cursor-pointer" title="Hapus Item">
                                        ✕
                                    </button>
                                </div>
                                <div v-if="item.trx_number || item.max_stock" class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 px-1">
                                    <span class="truncate">
                                        <span>Sumber:</span> 
                                        <b class="font-mono text-amber-700 dark:text-amber-400 ml-1">{{ item.trx_number ? 'PO ' + item.trx_number : 'Gudang' }}</b>
                                    </span>
                                    <span v-if="item.max_stock" class="shrink-0 text-emerald-600 dark:text-emerald-400 font-medium">
                                        Stok Gudang: {{ item.max_stock }} {{ item.unit }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 italic">* Stok akan otomatis terpotong saat kegiatan disimpan. Sisa material yang tidak terpakai dapat dilaporkan kembali ke gudang saat pekerjaan selesai.</p>
                        </div>
                        <div v-else class="p-3 rounded-xl bg-amber-50/50 dark:bg-amber-950/30 border border-dashed border-amber-200 dark:border-amber-900/50 text-center text-xs text-amber-700 dark:text-amber-400">
                            Belum ada bekal material yang disiapkan. Klik <b>+ Tambah Material</b> jika petugas memerlukan perlengkapan kabel, clamp, atau perangkat pasif.
                        </div>
                    </div>

                    <!-- 6. Status Awal & Prioritas -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Status Awal:
                            </label>
                            <select
                                v-model="createForm.status"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="scheduled">⏳ Terjadwal</option>
                                <option value="in_progress">🛠️ Sedang Dikerjakan</option>
                                <option value="completed">✅ Langsung Selesai</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Prioritas:
                            </label>
                            <select
                                v-model="createForm.priority"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="low">Rendah</option>
                                <option value="normal">Normal</option>
                                <option value="high">Tinggi</option>
                                <option value="urgent">Mendesak</option>
                            </select>
                        </div>
                    </div>

                    <!-- 7. Keterangan / Rencana Kerja -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Keterangan / Instruksi Kerja Lapangan:
                        </label>
                        <textarea
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Detail kabel kendur, tiang miring, port ODP yang perlu diperiksa, atau kabel yang perlu diikat slacknya..."
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                        ></textarea>
                    </div>

                    </div>

                    <!-- Modal Actions (Pinned) -->
                    <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-900/90 shrink-0">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 disabled:opacity-50 rounded-xl transition-all flex items-center gap-2 shadow-md shadow-amber-600/20 cursor-pointer"
                        >
                            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Kegiatan 🚀' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MODAL 2: UPDATE STATUS & LAPORAN PENGERJAAN CEPAT              -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div 
            v-if="showUpdateModal && activeMaintenanceItem" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto"
            @click.self="showUpdateModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 my-auto animate-in fade-in zoom-in-95 duration-200 overflow-hidden">
                
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-teal-500 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            📝
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Update Status & Laporan Hasil Kerja</h3>
                            <p class="text-[11px] text-slate-400 font-mono">{{ activeMaintenanceItem.maintenance_number }} · {{ activeMaintenanceItem.title }}</p>
                        </div>
                    </div>
                    <button 
                        @click="showUpdateModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitUpdateMaintenance" class="flex flex-col min-h-0 flex-1 overflow-hidden">
                    <div class="p-6 space-y-4 overflow-y-auto flex-1 overscroll-contain">

                        <!-- Info Ringkas Target & Petugas -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 text-xs space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Target & Wilayah:</span>
                                <span class="font-bold text-teal-600 dark:text-teal-400 font-mono">
                                    {{ activeMaintenanceItem.category_icon || '🛠️' }} {{ activeMaintenanceItem.location }} ({{ activeMaintenanceItem.area_name }})
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Petugas Ditugaskan:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-200">👷 {{ activeMaintenanceItem.technicians }}</span>
                            </div>
                        </div>
                    
                    <!-- 1. Pilihan Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pilih Status Saat Ini:
                        </label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label 
                                @click="selectUpdateStatus('in_progress')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'in_progress' ? 'bg-blue-50 border-blue-500 text-blue-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="in_progress" class="sr-only" />
                                <span>🛠️ Sedang Dikerjakan</span>
                            </label>
                            <label 
                                @click="selectUpdateStatus('completed')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'completed' ? 'bg-emerald-50 border-emerald-500 text-emerald-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="completed" class="sr-only" />
                                <span>✅ Selesai Dikerjakan</span>
                            </label>
                            <label 
                                @click="selectUpdateStatus('rescheduled')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'rescheduled' ? 'bg-purple-50 border-purple-500 text-purple-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="rescheduled" class="sr-only" />
                                <span>🗓️ Reschedule</span>
                            </label>
                            <label 
                                @click="selectUpdateStatus('scheduled')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'scheduled' ? 'bg-amber-50 border-amber-500 text-amber-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="scheduled" class="sr-only" />
                                <span>⏳ Pending / Jadwal</span>
                            </label>
                        </div>
                    </div>

                    <!-- Jika Reschedule: Tanggal Baru -->
                    <div v-if="updateForm.status === 'rescheduled'">
                        <label class="block text-xs font-bold text-purple-700 dark:text-purple-300 mb-1">
                            Pindahkan Tanggal Pelaksanaan ke:
                        </label>
                        <input
                            type="date"
                            v-model="updateForm.scheduled_date"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-purple-50/50 dark:bg-slate-800 border border-purple-300 text-slate-900 dark:text-white"
                        />
                    </div>

                    <!-- 2. Jam Mulai & Jam Selesai -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Jam Mulai:
                                </label>
                                <button 
                                    type="button" 
                                    @click="updateForm.start_time = getCurrentTimeStr()" 
                                    class="text-[10px] text-teal-600 dark:text-teal-400 hover:underline font-semibold flex items-center gap-0.5 cursor-pointer"
                                >
                                    ⏱️ Sekarang
                                </button>
                            </div>
                            <input
                                type="time"
                                v-model="updateForm.start_time"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Jam Selesai:
                                </label>
                                <button 
                                    type="button" 
                                    @click="updateForm.end_time = getCurrentTimeStr()" 
                                    class="text-[10px] text-teal-600 dark:text-teal-400 hover:underline font-semibold flex items-center gap-0.5 cursor-pointer"
                                >
                                    ⏱️ Sekarang
                                </button>
                            </div>
                            <input
                                type="time"
                                v-model="updateForm.end_time"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- 3. Redaman Optik (OPM dBm) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pengukuran Redaman Optik (OPM dBm) (Opsional):
                        </label>
                        <input
                            type="text"
                            v-model="updateForm.optical_power_after"
                            placeholder="Contoh: -18.4 dBm (Normal)"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono"
                        />
                    </div>

                    <!-- 4. Tindakan & Hasil Pengerjaan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Tindakan Lapangan / Hasil Pekerjaan:
                        </label>
                        <textarea
                            v-model="updateForm.action_taken"
                            rows="3"
                            placeholder="Kabel kendur telah dirapikan dan diikat slack di tiang 14. ODP telah dibersihkan dan konektor dipastikan kencang."
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Laporan Pemakaian Material Lapangan -->
                    <div v-if="updateForm.materials && updateForm.materials.length > 0" class="space-y-2 p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/80 dark:border-amber-900/50">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-amber-800 dark:text-amber-300 uppercase flex items-center gap-1.5">
                                <span>📦</span>
                                <span>Laporan Pemakaian Material Lapangan</span>
                            </label>
                            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">Sisa otomatis dikembalikan ke gudang</span>
                        </div>

                        <div class="space-y-2 pt-1">
                            <div v-for="(mat, idx) in updateForm.materials" :key="idx" class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 space-y-1.5 text-xs shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ mat.name }}</span>
                                    <span class="text-[11px] text-slate-500">Bekal Awal: <strong class="text-amber-700 dark:text-amber-400 font-mono">{{ mat.quantity_prepared }} {{ mat.unit }}</strong></span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">Jumlah Terpakai:</label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            :max="mat.quantity_prepared"
                                            v-model.number="mat.quantity_used" 
                                            @input="onUsedQtyChange(mat)"
                                            class="w-full px-2.5 py-1 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 font-bold"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">Sisa Kembali ke Gudang:</label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            v-model.number="mat.quantity_returned" 
                                            class="w-full px-2.5 py-1 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-blue-700 dark:text-blue-400 font-bold"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Upload Foto Sesudah (Auto Kompres Cerdas) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Upload Foto Dokumentasi Lapangan (Opsional):
                            </label>
                            <span v-if="photoCompressionInfo?.isCompressed" class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-700">
                                ⚡ Auto-Kompres Aktif (Hemat {{ photoCompressionInfo.ratio }})
                            </span>
                        </div>

                        <!-- Indikator Sedang Mengompres -->
                        <div v-if="isCompressingPhoto" class="p-3 rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 text-xs text-teal-800 dark:text-teal-300 flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="font-medium">Mengompres ukuran dan resolusi foto secara otomatis...</span>
                        </div>

                        <!-- Input File (Jika Belum Ada File Terpilih) -->
                        <div v-else-if="!updatePhotoAfterFile">
                            <input
                                type="file"
                                accept="image/*"
                                @change="handlePhotoAfterUpload"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-slate-800 dark:file:text-teal-400 cursor-pointer"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Ukuran foto kamera HP (mis. 5-15MB) akan otomatis dikompres ke ~200-350KB agar upload instan tanpa menguras kuota.</p>
                        </div>

                        <!-- Preview Thumbnail & Info Kompresi -->
                        <div v-else class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img 
                                    v-if="photoCompressionInfo?.previewUrl" 
                                    :src="photoCompressionInfo.previewUrl" 
                                    class="w-12 h-12 object-cover rounded-xl border border-slate-300 dark:border-slate-600 shrink-0 shadow-2xs" 
                                />
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ updatePhotoAfterFile.name }}</p>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
                                        <span class="line-through text-slate-400" v-if="photoCompressionInfo?.isCompressed">{{ photoCompressionInfo.originalFormatted }}</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ photoCompressionInfo?.compressedFormatted || formatBytes(updatePhotoAfterFile.size) }}</span>
                                        <span v-if="photoCompressionInfo?.isCompressed" class="px-1.5 py-0.2 rounded font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                                            -{{ photoCompressionInfo.ratio }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="removePhotoAfter"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                                title="Hapus / Ganti Foto"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    </div>

                    <!-- Modal Actions (Pinned) -->
                    <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-900/90 shrink-0">
                        <button
                            type="button"
                            @click="showUpdateModal = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2 text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 disabled:opacity-50 rounded-xl transition-all flex items-center gap-2 shadow-md shadow-teal-600/20 cursor-pointer"
                        >
                            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Update 🚀' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MODAL 3: PREVIEW FOTO DOKUMENTASI                              -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div 
            v-if="showPhotoModal && selectedPhotoItem" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
            @click.self="showPhotoModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Foto Dokumentasi Lapangan</h3>
                    <button @click="showPhotoModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-if="selectedPhotoItem.photo_before" class="space-y-1 text-center">
                        <span class="text-xs font-bold text-slate-500 uppercase">Foto Sebelum / Kondisi Awal</span>
                        <img :src="selectedPhotoItem.photo_before" alt="Foto Sebelum" class="w-full h-56 object-cover rounded-2xl border border-slate-200 shadow-sm" />
                    </div>
                    <div v-if="selectedPhotoItem.photo_after" class="space-y-1 text-center">
                        <span class="text-xs font-bold text-emerald-600 uppercase">Foto Sesudah / Hasil Rapih</span>
                        <img :src="selectedPhotoItem.photo_after" alt="Foto Sesudah" class="w-full h-56 object-cover rounded-2xl border border-slate-200 shadow-sm" />
                    </div>
                </div>

                <div class="text-right pt-2">
                    <button 
                        @click="showPhotoModal = false"
                        class="px-4 py-2 text-xs font-bold bg-slate-900 text-white rounded-xl cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MODAL 4: KIRIM OTOMATIS VIA GATEWAY WA                         -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div 
            v-if="showSendModal" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
            @click.self="showSendModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-emerald-500 text-white flex items-center justify-center font-bold text-lg">
                            🚀
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kirim Laporan via Gateway WA</h3>
                            <p class="text-[11px] text-slate-400">Pesan akan dikirim langsung oleh server Baileys</p>
                        </div>
                    </div>
                    <button 
                        @click="showSendModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Form Fields -->
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor WhatsApp Tujuan atau JID Grup:
                        </label>
                        <input
                            type="text"
                            v-model="targetPhone"
                            placeholder="Contoh: 08123456789 atau 1203630xxx@g.us"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        />
                        <p class="text-[10px] text-slate-400 mt-1">Bisa nomor pribadi koordinator (mis. 0812xxx / 62812xxx) atau ID Grup WhatsApp.</p>
                    </div>

                    <!-- Preset Target Suggestions -->
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pintasan Target Cepat:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                @click="targetPhone = '120363024823901234@g.us'"
                                class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 cursor-pointer"
                            >
                                👥 Grup Teknisi
                            </button>
                            <button
                                type="button"
                                @click="targetPhone = '120363024823909999@g.us'"
                                class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 cursor-pointer"
                            >
                                🛡️ Grup NOC & Helpdesk
                            </button>
                        </div>
                    </div>

                    <!-- Gateway Status Warning -->
                    <div v-if="!waGatewayStatus" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300">
                        ⚠️ <strong>Peringatan:</strong> Layanan Gateway WhatsApp terdeteksi sedang offline atau belum terhubung QR. Anda tetap bisa menggunakan opsi <strong>Salin Format WA</strong> atau <strong>Buka di WhatsApp Web</strong>.
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showSendModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeSendWa"
                        :disabled="isSending || !targetPhone"
                        class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 rounded-xl transition-all flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer"
                    >
                        <svg v-if="isSending" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ isSending ? 'Mengirim...' : 'Kirim Sekarang 🚀' }}</span>
                    </button>
                </div>

            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { compressImage, formatBytes } from '@/Utils/imageCompressor';

const page = usePage();

const props = defineProps({
    defaultTab: { type: String, default: 'gangguan' },
    filters: { type: Object, default: () => ({}) },
    areas: { type: Array, default: () => [] },
    indonesianDate: { type: String, default: '' },
    areaLabel: { type: String, default: '' },
    gangguanList: { type: Array, default: () => [] },
    pemasanganList: { type: Array, default: () => [] },
    surveyList: { type: Array, default: () => [] },
    cbpList: { type: Array, default: () => [] },
    maintenanceList: { type: Array, default: () => [] },
    technicianStatus: { type: Object, default: () => ({}) },
    maintenanceStatus: { type: Object, default: () => ({}) },
    availableTechnicians: { type: Array, default: () => [] },
    availableOdps: { type: Array, default: () => [] },
    availableOdcs: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    waText: { type: String, default: '' },
    waGatewayStatus: { type: Boolean, default: false },
    canViewAllAreas: { type: Boolean, default: true },
    isAreaScoped: { type: Boolean, default: false },
    materials: { type: Array, default: () => [] },
    materialTransactions: { type: Array, default: () => [] },
});

// Normalisasi Status Teknisi & Maintenance (Divisi Terpisah & Anti-Tercampur)
const tekStatus = computed(() => {
    return props.technicianStatus?.teknisi || {
        on_duty: props.technicianStatus?.on_duty || [],
        off: props.technicianStatus?.off || [],
        night: props.technicianStatus?.night || [],
    };
});

const maintStatus = computed(() => {
    return (props.maintenanceStatus && Object.keys(props.maintenanceStatus).length > 0)
        ? props.maintenanceStatus
        : (props.technicianStatus?.maintenance || { on_duty: [], off: [], night: [] });
});

const totalStaffDutyCount = computed(() => {
    return (tekStatus.value.on_duty?.length || 0) + (maintStatus.value.on_duty?.length || 0);
});

// Helper Local Date (WIB / Browser Local timezone, bebas dari bug UTC)
function getLocalDateString(offsetDays = 0) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Filters State
const filterDate = ref(props.filters?.date || getLocalDateString(0));
const filterArea = ref(props.filters?.area_id || (!props.canViewAllAreas && props.areas?.[0]?.id ? props.areas[0].id : ''));
const customNote = ref(props.filters?.note || 'Tetap semangat dan jaga keselamatan kerja! 💪');
const isLoading = ref(false);

// Filter Tugas Saya vs Semua Petugas
const filterOnlyMyTasks = ref(false);
const currentUserId = computed(() => page.props.auth?.user?.id);
const currentUserName = computed(() => (page.props.auth?.user?.name || '').toLowerCase());

const myMaintenanceList = computed(() => {
    return (props.maintenanceList || []).filter(item => {
        if (item.technician_ids && Array.isArray(item.technician_ids) && item.technician_ids.includes(currentUserId.value)) {
            return true;
        }
        if (item.technicians && currentUserName.value && item.technicians.toLowerCase().includes(currentUserName.value)) {
            return true;
        }
        return false;
    });
});

const filteredMaintenanceList = computed(() => {
    if (filterOnlyMyTasks.value) {
        return myMaintenanceList.value;
    }
    return props.maintenanceList || [];
});

// Active Tab
const activeTab = ref(
    (typeof window !== 'undefined' && new URLSearchParams(window.location.search).get('tab')) ||
    (typeof window !== 'undefined' && window.location.pathname.includes('network-maintenance') ? 'maintenance' : (props.defaultTab || 'gangguan'))
);

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam && ['gangguan', 'pemasangan', 'survey', 'cbp', 'maintenance', 'teknisi'].includes(tabParam)) {
            activeTab.value = tabParam;
        } else if (window.location.pathname.includes('network-maintenance')) {
            activeTab.value = 'maintenance';
        } else if (props.defaultTab) {
            activeTab.value = props.defaultTab;
        }
    }
});

const tabs = computed(() => [
    { key: 'gangguan', label: 'Gangguan PGS/PGK/PGJ', icon: '🆘', count: props.summary?.gangguan || 0 },
    { key: 'pemasangan', label: 'Pemasangan Baru', icon: '🛜', count: props.summary?.pemasangan || 0 },
    { key: 'survey', label: 'Survey PSB', icon: '🔎', count: props.summary?.survey || 0 },
    { key: 'cbp', label: 'Cabut Perangkat', icon: '🔌', count: props.summary?.cbp || 0 },
    { key: 'maintenance', label: 'Perapihan & Jalur', icon: '🛠️', count: props.summary?.maintenance || 0 },
    { key: 'teknisi', label: 'Status Shift', icon: '👷', count: totalStaffDutyCount.value },
]);

// Dynamic WhatsApp message text calculation
const activeWaText = computed(() => {
    const baseText = props.waText || '';
    if (!baseText) return '';
    
    const noteMarker = '📝 *NOTE*';
    const markerIndex = baseText.indexOf(noteMarker);
    if (markerIndex !== -1) {
        return baseText.substring(0, markerIndex) + noteMarker + '\n' + (customNote.value || 'Tetap semangat dan jaga keselamatan kerja! 💪');
    }
    return baseText + '\n\n📝 *NOTE*\n' + (customNote.value || 'Tetap semangat dan jaga keselamatan kerja! 💪');
});

// Quick Date Change
function setQuickDate(offsetDays) {
    filterDate.value = getLocalDateString(offsetDays);
    applyFilter();
}

function applyFilter() {
    isLoading.value = true;
    router.get('/operational/daily-progress', {
        date: filterDate.value,
        area_id: filterArea.value || '',
        note: customNote.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        }
    });
}

// Category Badge Helper
function getCategoryBadgeClass(cat) {
    switch (cat) {
        case 'perapihan_jalur':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300';
        case 'pengecekan_odp':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border-blue-300';
        case 'pengecekan_odc':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-300';
        case 'pengecekan_jalur_utama':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300';
        default:
            return 'bg-teal-100 text-teal-800 dark:bg-teal-950/80 dark:text-teal-300 border-teal-300';
    }
}

// Clean phone helper
function cleanPhone(num) {
    if (!num) return '';
    let cleaned = String(num).replace(/[^0-9]/g, '');
    if (cleaned.startsWith('08')) {
        cleaned = '628' + cleaned.slice(2);
    }
    return cleaned;
}

// Copy to Clipboard
const isCopied = ref(false);
function copyWhatsAppText() {
    const text = activeWaText.value;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            triggerCopySuccess();
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    try {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        triggerCopySuccess();
    } catch (e) {
        alert('Gagal menyalin teks secara otomatis. Silakan salin teks dari kotak preview.');
    }
}

function triggerCopySuccess() {
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2500);
}

// ══════════════════════════════════════════════════════════════════
// MODAL & HANDLERS: TAMBAH KEGIATAN PEMELIHARAAN JALUR
// ══════════════════════════════════════════════════════════════════
const showCreateModal = ref(false);
const isSubmitting = ref(false);

const createForm = ref({
    category: 'perapihan_jalur',
    title: '',
    area_id: '',
    odp_id: '',
    odc_id: '',
    location_name: '',
    scheduled_date: getLocalDateString(0),
    scheduled_time: '09:00',
    priority: 'normal',
    status: 'scheduled',
    description: '',
    technician_ids: [],
    materials: [],
});

const showAllAreaTechs = ref(false);

const filteredTechnicians = computed(() => {
    if (!createForm.value.area_id || showAllAreaTechs.value) {
        return props.availableTechnicians;
    }
    return props.availableTechnicians.filter(
        t => String(t.area_id) === String(createForm.value.area_id) || createForm.value.technician_ids.includes(t.id)
    );
});

const filteredOdps = computed(() => {
    if (!createForm.value.area_id) return props.availableOdps;
    return props.availableOdps.filter(o => String(o.area_id) === String(createForm.value.area_id));
});

const filteredOdcs = computed(() => {
    if (!createForm.value.area_id) return props.availableOdcs;
    return props.availableOdcs.filter(c => String(c.area_id) === String(createForm.value.area_id));
});

// Daftar Material Persiapan Gudang (Bekal Petugas)
const availableMaterialItems = computed(() => {
    let items = [];
    const areaId = createForm.value.area_id;
    const handledMaterialIds = new Set();

    // 1. Order Restock Area jika ada
    const trxs = props.materialTransactions || [];
    trxs.forEach(trx => {
        if (trx.items && trx.items.length > 0) {
            trx.items.forEach(item => {
                if (!item.material) return;
                const name = (item.material.name || '').toLowerCase();
                const category = (item.material.category || '').toLowerCase();
                const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;

                if (!isRegistered && !name.includes('modem') && !category.includes('modem') && !name.includes('ont') && !category.includes('ont')) {
                    let remainingInOrder = typeof item.remaining_quantity !== 'undefined'
                        ? parseFloat(item.remaining_quantity)
                        : parseFloat(item.quantity);

                    let areaStock = null;
                    if (item.material.stocks && areaId) {
                        const stockObj = item.material.stocks.find(s => s.area_id == areaId);
                        if (stockObj) {
                            areaStock = parseFloat(stockObj.stock) || 0;
                        }
                    }

                    let availableQty = remainingInOrder;
                    if (areaStock !== null && areaStock < availableQty) {
                        availableQty = areaStock;
                    }

                    if (availableQty > 0) {
                        handledMaterialIds.add(item.material_id);
                        items.push({
                            unique_key: 'ord-' + item.id,
                            id: item.id,
                            material_id: item.material_id,
                            trx_number: trx.transaction_number,
                            trx_date: trx.date,
                            purpose: trx.purpose,
                            area_name: trx.area_model?.name || trx.area || 'Restock Area',
                            name: item.material.name,
                            category: item.material.category,
                            area_stock: availableQty,
                            unit: item.unit || item.material.unit || 'pcs',
                            material: item.material,
                        });
                    }
                }
            });
        }
    });

    // 2. Sertakan SEMUA Material dari Inventory Gudang
    const allMaterials = props.materials || [];
    allMaterials.forEach(m => {
        const name = (m.name || '').toLowerCase();
        const category = (m.category || '').toLowerCase();
        if (name.includes('modem') || category.includes('modem') || name.includes('ont') || category.includes('ont')) {
            return;
        }

        let areaStock = 0;
        if (m.stocks && areaId) {
            const stockObj = m.stocks.find(s => s.area_id == areaId);
            if (stockObj) {
                areaStock = parseFloat(stockObj.stock) || 0;
            }
        }
        if (areaStock <= 0 && m.stock) {
            areaStock = parseFloat(m.stock) || 0;
        }

        if (!handledMaterialIds.has(m.id)) {
            items.push({
                unique_key: 'mat-' + m.id,
                id: 'mat-' + m.id,
                material_id: m.id,
                trx_number: '',
                area_name: 'Gudang Utama',
                name: m.name,
                category: m.category,
                area_stock: areaStock,
                unit: m.unit || 'pcs',
                material: m,
            });
        }
    });

    return items;
});

const addMaterialRow = () => {
    if (!createForm.value.materials) {
        createForm.value.materials = [];
    }
    createForm.value.materials.push({
        selected_key: '',
        material_transaction_item_id: null,
        material_id: '',
        trx_number: '',
        name: '',
        quantity: 1,
        unit: 'pcs',
        max_stock: null,
        notes: '',
    });
};

const removeMaterialRow = (index) => {
    createForm.value.materials.splice(index, 1);
};

const onMaterialItemChange = (item) => {
    const selectedKey = item.selected_key;
    const selectedOrder = availableMaterialItems.value.find(i => i.unique_key === selectedKey || i.id === selectedKey || i.material_id === selectedKey);
    if (selectedOrder) {
        item.material_id = selectedOrder.material_id;
        item.trx_number = selectedOrder.trx_number || '';
        item.name = selectedOrder.name;
        item.unit = selectedOrder.unit;
        item.max_stock = selectedOrder.area_stock;
        item.material_transaction_item_id = selectedOrder.unique_key?.startsWith('ord-') ? (selectedOrder.id || null) : null;
        
        if (item.quantity && item.quantity > selectedOrder.area_stock) {
            item.quantity = selectedOrder.area_stock;
        } else if (!item.quantity || item.quantity <= 0) {
            item.quantity = 1;
        }
    }
};

const validateMaterialQty = (item) => {
    if (item.max_stock !== undefined && item.max_stock !== null) {
        if (item.quantity > item.max_stock) {
            item.quantity = item.max_stock;
            alert(`Stok tidak mencukupi! Maksimal stok tersedia adalah ${item.max_stock} ${item.unit}`);
        }
    }
};

const onUsedQtyChange = (mat) => {
    const prep = parseFloat(mat.quantity_prepared) || 0;
    const used = parseFloat(mat.quantity_used) || 0;
    mat.quantity_returned = Math.max(0, parseFloat((prep - used).toFixed(2)));
};

function openCreateModal() {
    showAllAreaTechs.value = false;
    createForm.value = {
        category: 'perapihan_jalur',
        title: '',
        area_id: filterArea.value || '',
        odp_id: '',
        odc_id: '',
        location_name: '',
        scheduled_date: filterDate.value || getLocalDateString(0),
        scheduled_time: '09:00',
        priority: 'normal',
        status: 'scheduled',
        description: '',
        technician_ids: [],
        materials: [],
    };
    showCreateModal.value = true;
}

async function submitCreateMaintenance() {
    isSubmitting.value = true;
    try {
        const payload = new FormData();
        payload.append('category', createForm.value.category);
        payload.append('title', createForm.value.title);
        payload.append('scheduled_date', createForm.value.scheduled_date);
        if (createForm.value.scheduled_time) payload.append('scheduled_time', createForm.value.scheduled_time);
        if (createForm.value.area_id) payload.append('area_id', createForm.value.area_id);
        if (createForm.value.odp_id) payload.append('odp_id', createForm.value.odp_id);
        if (createForm.value.odc_id) payload.append('odc_id', createForm.value.odc_id);
        if (createForm.value.location_name) payload.append('location_name', createForm.value.location_name);
        payload.append('priority', createForm.value.priority);
        payload.append('status', createForm.value.status);
        if (createForm.value.description) payload.append('description', createForm.value.description);

        createForm.value.technician_ids.forEach(id => {
            payload.append('technician_ids[]', id);
        });

        if (createForm.value.materials && createForm.value.materials.length > 0) {
            payload.append('materials', JSON.stringify(createForm.value.materials));
        }

        const res = await axios.post('/operational/network-maintenance', payload);
        if (res.data && res.data.success) {
            alert('✓ ' + (res.data.message || 'Kegiatan pemeliharaan berhasil ditambahkan!'));
            showCreateModal.value = false;
            activeTab.value = 'maintenance';
            applyFilter();
        } else {
            alert('⚠️ ' + (res.data?.message || 'Gagal menambahkan kegiatan.'));
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'Terjadi kesalahan saat menyimpan data.';
        alert('❌ Error: ' + msg);
    } finally {
        isSubmitting.value = false;
    }
}

// ══════════════════════════════════════════════════════════════════
// MODAL & HANDLERS: UPDATE STATUS & LAPORAN KERJA CEPAT
// ══════════════════════════════════════════════════════════════════
const showUpdateModal = ref(false);
const activeMaintenanceItem = ref(null);
const updatePhotoAfterFile = ref(null);

const updateForm = ref({
    status: 'completed',
    start_time: '',
    end_time: '',
    optical_power_after: '',
    action_taken: '',
    scheduled_date: '',
});

function getCurrentTimeStr() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    return `${h}:${m}`;
}

function selectUpdateStatus(newStatus) {
    updateForm.value.status = newStatus;
    const nowStr = getCurrentTimeStr();
    if (newStatus === 'in_progress') {
        if (!updateForm.value.start_time) {
            updateForm.value.start_time = nowStr;
        }
    } else if (newStatus === 'completed') {
        if (!updateForm.value.end_time) {
            updateForm.value.end_time = nowStr;
        }
        if (!updateForm.value.start_time) {
            updateForm.value.start_time = nowStr;
        }
    }
}

const isCompressingPhoto = ref(false);
const photoCompressionInfo = ref(null);

function openQuickUpdateModal(item) {
    activeMaintenanceItem.value = item;
    updatePhotoAfterFile.value = null;
    photoCompressionInfo.value = null;
    updateForm.value = {
        status: item.is_done ? 'completed' : (item.is_in_progress ? 'completed' : 'in_progress'),
        start_time: item.start_time || '',
        end_time: item.end_time || '',
        optical_power_after: item.optical_power_after || '',
        action_taken: item.action_taken || '',
        scheduled_date: item.scheduled_date || filterDate.value,
        materials: (item.materials || []).map(m => {
            const prep = parseFloat(m.quantity_prepared) || 0;
            const used = parseFloat(m.quantity_used) || 0;
            const ret = (typeof m.quantity_returned !== 'undefined' && m.quantity_returned !== null) 
                ? parseFloat(m.quantity_returned) 
                : Math.max(0, parseFloat((prep - used).toFixed(2)));
            return {
                id: m.id,
                material_id: m.material_id,
                name: m.material?.name || m.name || 'Material',
                quantity_prepared: prep,
                quantity_used: used,
                quantity_returned: ret,
                unit: m.unit || m.material?.unit || 'pcs',
                notes: m.notes || '',
            };
        }),
    };
    showUpdateModal.value = true;
}

async function handlePhotoAfterUpload(e) {
    const file = e.target.files?.[0];
    if (!file) {
        updatePhotoAfterFile.value = null;
        photoCompressionInfo.value = null;
        return;
    }

    isCompressingPhoto.value = true;
    try {
        const res = await compressImage(file, {
            maxWidth: 1920,
            maxHeight: 1920,
            quality: 0.82,
        });
        updatePhotoAfterFile.value = res.file;
        photoCompressionInfo.value = res;
    } catch (err) {
        console.warn('Gagal kompres foto:', err);
        updatePhotoAfterFile.value = file;
    } finally {
        isCompressingPhoto.value = false;
    }
}

function removePhotoAfter() {
    updatePhotoAfterFile.value = null;
    photoCompressionInfo.value = null;
}

async function submitUpdateMaintenance() {
    if (!activeMaintenanceItem.value) return;
    isSubmitting.value = true;
    try {
        const payload = new FormData();
        payload.append('status', updateForm.value.status);
        if (updateForm.value.action_taken) payload.append('action_taken', updateForm.value.action_taken);
        if (updateForm.value.optical_power_after) payload.append('optical_power_after', updateForm.value.optical_power_after);
        if (updateForm.value.start_time) payload.append('start_time', updateForm.value.start_time);
        if (updateForm.value.end_time) payload.append('end_time', updateForm.value.end_time);
        if (updateForm.value.scheduled_date) payload.append('scheduled_date', updateForm.value.scheduled_date);
        if (updatePhotoAfterFile.value) payload.append('photo_after', updatePhotoAfterFile.value);
        if (updateForm.value.materials && updateForm.value.materials.length > 0) {
            payload.append('materials', JSON.stringify(updateForm.value.materials));
        }

        const res = await axios.post(`/operational/network-maintenance/${activeMaintenanceItem.value.id}/quick-update`, payload);
        if (res.data && res.data.success) {
            alert('✓ ' + (res.data.message || 'Status berhasil diperbarui!'));
            showUpdateModal.value = false;
            applyFilter();
        } else {
            alert('⚠️ ' + (res.data?.message || 'Gagal memperbarui status.'));
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'Terjadi kesalahan saat update status.';
        alert('❌ Error: ' + msg);
    } finally {
        isSubmitting.value = false;
    }
}

async function deleteMaintenance(item) {
    if (!confirm(`Apakah Anda yakin ingin menghapus kegiatan "${item.title}" (${item.maintenance_number})?`)) return;
    try {
        const res = await axios.delete(`/operational/network-maintenance/${item.id}`);
        if (res.data && res.data.success) {
            alert('✓ Kegiatan berhasil dihapus.');
            applyFilter();
        } else {
            alert('⚠️ ' + (res.data?.message || 'Gagal menghapus data.'));
        }
    } catch (err) {
        alert('❌ Error: ' + (err.response?.data?.message || err.message));
    }
}

// ══════════════════════════════════════════════════════════════════
// MODAL FOTO DOKUMENTASI
// ══════════════════════════════════════════════════════════════════
const showPhotoModal = ref(false);
const selectedPhotoItem = ref(null);

function viewPhotos(item) {
    selectedPhotoItem.value = item;
    showPhotoModal.value = true;
}

// ══════════════════════════════════════════════════════════════════
// MODAL WHATSAPP GATEWAY
// ══════════════════════════════════════════════════════════════════
const showSendModal = ref(false);
const targetPhone = ref('');
const isSending = ref(false);

function openSendModal() {
    showSendModal.value = true;
}

async function executeSendWa() {
    if (!targetPhone.value) return;

    isSending.value = true;
    try {
        const response = await axios.post('/operational/daily-progress/send-wa', {
            phone: targetPhone.value,
            message: activeWaText.value,
        });

        if (response.data && response.data.success) {
            alert('✓ ' + (response.data.message || 'Laporan progress harian berhasil dikirim ke WhatsApp!'));
            showSendModal.value = false;
        } else {
            alert('⚠️ ' + (response.data?.message || 'Gagal mengirim pesan via WhatsApp Gateway.'));
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'Terjadi kesalahan saat mengirim pesan via WhatsApp Gateway.';
        alert('❌ Error: ' + msg);
    } finally {
        isSending.value = false;
    }
}
</script>
