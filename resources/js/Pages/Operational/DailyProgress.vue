<template>
    <AppLayout title="Progress Harian Operasional" subtitle="Laporan Terpadu Gangguan PGS/PGK/PGJ, Instalasi, Survey, CBP & Shift Teknisi">
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
                                class="px-2.5 py-1 rounded-lg transition-colors hover:text-slate-900 dark:hover:text-white text-slate-600 dark:text-slate-400"
                                title="Kemarin"
                            >
                                Kemarin
                            </button>
                            <button
                                type="button"
                                @click="setQuickDate(0)"
                                class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold transition-all"
                                title="Hari Ini"
                            >
                                Hari Ini
                            </button>
                            <button
                                type="button"
                                @click="setQuickDate(1)"
                                class="px-2.5 py-1 rounded-lg transition-colors hover:text-slate-900 dark:hover:text-white text-slate-600 dark:text-slate-400"
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
                                <option value="">🌐 Semua Wilayah / Area</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

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

            <!-- STATS KPI SUMMARY CARDS -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3.5">
                
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
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.gangguan_done || 0 }} Selesai</span>
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
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.pemasangan_done || 0 }} Selesai</span>
                        <span>·</span>
                        <span class="text-amber-600 font-bold">⏳ {{ summary.pemasangan_pending || 0 }} Proses</span>
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
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.survey_feasible || 0 }} Layak</span>
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
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                        <span class="text-emerald-600 font-bold">✓ {{ summary.cbp_done || 0 }} Selesai</span>
                        <span>·</span>
                        <span class="text-amber-600 font-bold">⏳ {{ summary.cbp_pending || 0 }} Dijadwal</span>
                    </div>
                </div>

                <!-- 5. Status Teknisi Card -->
                <div 
                    @click="activeTab = 'teknisi'"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md col-span-2 sm:col-span-1"
                    :class="activeTab === 'teknisi' 
                        ? 'bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-400 dark:border-emerald-700 ring-2 ring-emerald-200 dark:ring-emerald-900' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-emerald-300'"
                >
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">👷 Teknisi Tugas</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/60">{{ technicianStatus?.on_duty?.length || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ technicianStatus?.on_duty?.length || 0 }} Orang
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1 truncate">
                        <span class="text-slate-600 dark:text-slate-300">Off: {{ technicianStatus?.off?.length || 0 }}</span>
                        <span>·</span>
                        <span class="text-indigo-600 dark:text-indigo-400 font-bold">Malam: {{ technicianStatus?.night?.length || 0 }}</span>
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
                                'flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl whitespace-nowrap transition-all duration-200',
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
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            {{ item.customer_name }}
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider" :class="item.source === 'PGS' ? 'bg-amber-100 text-amber-800' : (item.source === 'PGK' ? 'bg-rose-100 text-rose-800' : 'bg-purple-100 text-purple-800')">
                                                {{ item.source }}
                                            </span>
                                        </h4>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ item.ticket_number }}</div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
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
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Petugas Teknisi</span>
                                    <span class="text-slate-900 dark:text-white font-bold flex items-center gap-1">
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
                                        <div class="text-[11px] text-slate-400 font-mono">{{ item.customer_code }}</div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
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
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Durasi Pengerjaan</span>
                                    <span class="text-slate-700 dark:text-slate-300 font-mono">{{ item.duration || '-' }}</span>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Petugas Teknisi</span>
                                    <span class="text-slate-900 dark:text-white font-bold flex items-center gap-1">
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
                                        <div class="text-[11px] text-slate-400">Calon Pelanggan Baru</div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0"
                                    :class="item.feasibility === 'feasible' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
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
                                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Petugas Survey</span>
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
                                        <div class="text-[11px] text-slate-400 font-mono">{{ item.customer_code }}</div>
                                    </div>
                                </div>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0"
                                    :class="item.is_done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                                >
                                    {{ item.status_label }}
                                </span>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl text-xs space-y-1">
                                <div><strong class="text-slate-400 text-[10px] block uppercase">Data Detail</strong> {{ item.detail }}</div>
                                <div class="text-slate-600 dark:text-slate-400 pt-1 flex items-center justify-between">
                                    <span>👷 Petugas: <strong class="text-slate-900 dark:text-white">{{ item.technicians || '-' }}</strong></span>
                                    <span v-if="item.phone && item.phone !== '-'">📞 {{ item.phone }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: STATUS TEKNISI & ROSTER SHIFT -->
                    <div v-if="activeTab === 'teknisi'" class="space-y-4">
                        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 space-y-4">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span>👷‍♂️</span>
                                <span>Status Shift & Penugasan Teknisi</span>
                            </h3>

                            <!-- On Duty -->
                            <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 uppercase tracking-wider">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Teknisi On Duty / Bertugas Hari Ini ({{ technicianStatus?.on_duty?.length || 0 }})
                                    </h4>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="(name, nIdx) in technicianStatus?.on_duty" 
                                        :key="'duty-' + nIdx"
                                        class="px-3 py-1 rounded-xl bg-white dark:bg-slate-800 text-xs font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700 shadow-2xs"
                                    >
                                        👷 {{ name }}
                                    </span>
                                    <span v-if="!technicianStatus?.on_duty?.length" class="text-xs text-slate-400 italic">Tidak ada teknisi on duty tercatat</span>
                                </div>
                            </div>

                            <!-- Shift Malam -->
                            <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-indigo-800 dark:text-indigo-300 flex items-center gap-1.5 uppercase tracking-wider">
                                        <span>🌙</span>
                                        Teknisi Masuk Shift Malam ({{ technicianStatus?.night?.length || 0 }})
                                    </h4>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="(name, mIdx) in technicianStatus?.night" 
                                        :key="'night-' + mIdx"
                                        class="px-3 py-1 rounded-xl bg-white dark:bg-slate-800 text-xs font-bold text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 shadow-2xs"
                                    >
                                        🌙 {{ name }}
                                    </span>
                                    <span v-if="!technicianStatus?.night?.length" class="text-xs text-slate-400 italic">- (Nihil)</span>
                                </div>
                            </div>

                            <!-- Libur / Off / Cuti -->
                            <div class="p-4 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wider">
                                        <span>🏖️</span>
                                        Teknisi Libur / Off / Cuti ({{ technicianStatus?.off?.length || 0 }})
                                    </h4>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="(name, oIdx) in technicianStatus?.off" 
                                        :key="'off-' + oIdx"
                                        class="px-3 py-1 rounded-xl bg-white dark:bg-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 shadow-2xs"
                                    >
                                        🏖️ {{ name }}
                                    </span>
                                    <span v-if="!technicianStatus?.off?.length" class="text-xs text-slate-400 italic">- (Nihil)</span>
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

        <!-- MODAL: KIRIM OTOMATIS VIA GATEWAY WA -->
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
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
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
                                class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200"
                            >
                                👥 Grup Teknisi
                            </button>
                            <button
                                type="button"
                                @click="targetPhone = '120363024823909999@g.us'"
                                class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200"
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
                        class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
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
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    areas: { type: Array, default: () => [] },
    indonesianDate: { type: String, default: '' },
    areaLabel: { type: String, default: '' },
    gangguanList: { type: Array, default: () => [] },
    pemasanganList: { type: Array, default: () => [] },
    surveyList: { type: Array, default: () => [] },
    cbpList: { type: Array, default: () => [] },
    technicianStatus: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    waText: { type: String, default: '' },
    waGatewayStatus: { type: Boolean, default: false },
});

// Filters State
const filterDate = ref(props.filters?.date || new Date().toISOString().slice(0, 10));
const filterArea = ref(props.filters?.area_id || '');
const customNote = ref(props.filters?.note || 'Tetap semangat dan jaga keselamatan kerja! 💪');
const isLoading = ref(false);

// Active Tab
const activeTab = ref('gangguan');

const tabs = computed(() => [
    { key: 'gangguan', label: 'Gangguan PGS/PGK', icon: '🆘', count: props.summary?.gangguan || 0 },
    { key: 'pemasangan', label: 'Pemasangan Baru', icon: '🛜', count: props.summary?.pemasangan || 0 },
    { key: 'survey', label: 'Survey PSB', icon: '🔎', count: props.summary?.survey || 0 },
    { key: 'cbp', label: 'Cabut Perangkat', icon: '🔌', count: props.summary?.cbp || 0 },
    { key: 'teknisi', label: 'Status Shift', icon: '👷', count: props.technicianStatus?.on_duty?.length || 0 },
]);

// Dynamic WhatsApp message text calculation
const activeWaText = computed(() => {
    // If user edited custom note, replace or append note to original text seamlessly
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
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    filterDate.value = d.toISOString().slice(0, 10);
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

// WhatsApp Gateway Modal & Execution
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
