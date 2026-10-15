<template>
    <AppLayout title="Monitor Kinerja Teknisi" subtitle="Sistem Cerdas Pemantauan Penanganan Gangguan & Instalasi Pemasangan Teknisi">
        <!-- TOP CONTROL & FILTER BAR -->
        <div class="bg-white rounded-2xl border border-gray-200/80 p-4 sm:p-5 shadow-sm mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Title & Live Status Indicator -->
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center shadow-md shadow-indigo-100 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg sm:text-xl font-black text-gray-900 tracking-tight">Monitoring Produktivitas Teknisi</h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Sync
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Periode: <span class="font-bold text-gray-700">{{ filters.period_label }}</span> • Total <span class="font-bold text-indigo-600">{{ technicianStats.length }}</span> Personel Lapangan
                        </p>
                    </div>
                </div>

                <!-- Quick Actions & Auto-refresh -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Auto-refresh interval toggle -->
                    <button 
                        @click="toggleAutoRefresh" 
                        :class="[
                            'px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all border',
                            autoRefresh 
                                ? 'bg-indigo-50 text-indigo-700 border-indigo-200 shadow-sm' 
                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'
                        ]"
                        title="Auto-refresh data otomatis setiap 30 detik"
                    >
                        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ autoRefresh ? 'Auto 30s' : 'Refresh' }}</span>
                    </button>

                    <!-- Export CSV Button -->
                    <button 
                        v-if="can('monitoring_teknisi_btn_export')"
                        @click="exportToCsv" 
                        class="px-3.5 py-2 rounded-xl text-xs font-bold bg-gray-900 hover:bg-gray-800 text-white flex items-center gap-1.5 transition-all shadow-sm"
                        title="Unduh Rekap Kinerja Format CSV/Excel"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Export CSV</span>
                    </button>
                </div>
            </div>

            <!-- Filter Controls Grid -->
            <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- 1. Quick Period Select -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Rentang Waktu</label>
                    <select 
                        v-model="selectedPeriod" 
                        @change="handlePeriodChange"
                        class="w-full text-xs font-medium rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 py-2"
                    >
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="custom">Kustom Tanggal...</option>
                    </select>
                </div>

                <!-- 2. Area Filter -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Wilayah / Area</label>
                    <select 
                        v-model="selectedArea" 
                        @change="applyFilters"
                        class="w-full text-xs font-medium rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 py-2"
                    >
                        <option value="">Semua Wilayah</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">
                            {{ area.name }}
                        </option>
                    </select>
                </div>

                <!-- 3. Technician Specific Scope Filter -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Spesifik Teknisi</label>
                    <select 
                        v-model="selectedTechnician" 
                        @change="applyFilters"
                        class="w-full text-xs font-medium rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 py-2"
                    >
                        <option value="">Semua Teknisi</option>
                        <option v-for="tech in technicians" :key="tech.id" :value="tech.id">
                            {{ tech.name }}
                        </option>
                    </select>
                </div>

                <!-- 4. Search Filter by Name -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Cari Cepat</label>
                    <div class="relative">
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari nama teknisi..." 
                            class="w-full text-xs font-medium rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50 py-2 pl-8"
                        />
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Custom Date Range Picker (shown when custom selected) -->
            <div v-if="selectedPeriod === 'custom'" class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-3 animate-fade-in">
                <span class="text-xs font-bold text-gray-600">Dari:</span>
                <input type="date" v-model="customStartDate" class="text-xs rounded-xl border-gray-200 py-1.5 px-3 focus:ring-indigo-500" />
                <span class="text-xs font-bold text-gray-600">Sampai:</span>
                <input type="date" v-model="customEndDate" class="text-xs rounded-xl border-gray-200 py-1.5 px-3 focus:ring-indigo-500" />
                <button 
                    @click="applyFilters" 
                    class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all"
                >
                    Terapkan Rentang
                </button>
            </div>
        </div>

        <!-- 6 EXECUTIVE KPI STAT CARDS -->
        <div v-if="can('monitoring_teknisi_card_stats')" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-6">
            <!-- 1. Total Gangguan Ditangani -->
            <div 
                @click="switchTab('gangguan')"
                :class="[
                    'bg-white rounded-2xl p-4 border shadow-sm relative overflow-hidden group hover:shadow-md transition-all cursor-pointer',
                    activeTab === 'gangguan' ? 'border-rose-400 ring-2 ring-rose-200/70 shadow-rose-50' : 'border-gray-200/80 hover:border-rose-200'
                ]"
                title="Klik untuk membuka tab Penanganan Gangguan"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Gangguan</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-rose-600">{{ globalTrouble.total }}</span>
                    <span class="text-xs font-semibold text-gray-400">Kasus</span>
                </div>
                <div class="mt-2 text-[11px] font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Selesai:</span>
                    <span class="font-bold text-emerald-600">{{ globalTrouble.completion_rate }}%</span>
                </div>
            </div>

            <!-- 2. Gangguan Terselesaikan -->
            <div 
                @click="switchTab('gangguan')"
                :class="[
                    'bg-white rounded-2xl p-4 border shadow-sm relative overflow-hidden group hover:shadow-md transition-all cursor-pointer',
                    activeTab === 'gangguan' ? 'border-emerald-400 ring-2 ring-emerald-200/70 shadow-emerald-50' : 'border-gray-200/80 hover:border-emerald-200'
                ]"
                title="Klik untuk membuka tab Penanganan Gangguan"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Gangguan Selesai</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-emerald-600">{{ globalTrouble.completed }}</span>
                    <span class="text-xs font-semibold text-gray-400">Tiket</span>
                </div>
                <div class="mt-2 text-[11px] font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Rata2 SLA:</span>
                    <span class="font-bold text-gray-800">{{ globalTrouble.avg_resolution_hours }} Jam</span>
                </div>
            </div>

            <!-- 3. Gangguan Dalam Proses / Pending -->
            <div 
                @click="switchTab('gangguan')"
                :class="[
                    'bg-white rounded-2xl p-4 border shadow-sm relative overflow-hidden group hover:shadow-md transition-all cursor-pointer',
                    activeTab === 'gangguan' ? 'border-amber-400 ring-2 ring-amber-200/70 shadow-amber-50' : 'border-gray-200/80 hover:border-amber-200'
                ]"
                title="Klik untuk membuka tab Penanganan Gangguan"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pending Gangguan</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-amber-600">{{ globalTrouble.pending + globalTrouble.in_progress }}</span>
                    <span class="text-xs font-semibold text-gray-400">Tiket</span>
                </div>
                <div class="mt-2 text-[11px] font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Proses: {{ globalTrouble.in_progress }}</span>
                    <span>Antri: {{ globalTrouble.pending }}</span>
                </div>
            </div>

            <!-- 4. Total Instalasi PSB -->
            <div 
                @click="switchTab('instalasi')"
                :class="[
                    'bg-white rounded-2xl p-4 border shadow-sm relative overflow-hidden group hover:shadow-md transition-all cursor-pointer',
                    activeTab === 'instalasi' ? 'border-blue-400 ring-2 ring-blue-200/70 shadow-blue-50' : 'border-gray-200/80 hover:border-blue-200'
                ]"
                title="Klik untuk membuka tab Instalasi Pemasangan"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Pasang (PSB)</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-blue-600">{{ globalInstall.total }}</span>
                    <span class="text-xs font-semibold text-gray-400">Jadwal</span>
                </div>
                <div class="mt-2 text-[11px] font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Selesai:</span>
                    <span class="font-bold text-blue-600">{{ globalInstall.completion_rate }}%</span>
                </div>
            </div>

            <!-- 5. Instalasi Selesai -->
            <div 
                @click="switchTab('instalasi')"
                :class="[
                    'bg-white rounded-2xl p-4 border shadow-sm relative overflow-hidden group hover:shadow-md transition-all cursor-pointer',
                    activeTab === 'instalasi' ? 'border-teal-400 ring-2 ring-teal-200/70 shadow-teal-50' : 'border-gray-200/80 hover:border-teal-200'
                ]"
                title="Klik untuk membuka tab Instalasi Pemasangan"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pasang Selesai</span>
                    <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-teal-600">{{ globalInstall.completed }}</span>
                    <span class="text-xs font-semibold text-gray-400">Pelanggan</span>
                </div>
                <div class="mt-2 text-[11px] font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Survey Layak:</span>
                    <span class="font-bold text-teal-700">{{ globalInstall.surveys_feasible }}</span>
                </div>
            </div>

            <!-- 6. Instalasi Pending / Terjadwal -->
            <div 
                @click="switchTab('instalasi')"
                :class="[
                    'bg-white rounded-2xl p-4 border shadow-sm relative overflow-hidden group hover:shadow-md transition-all cursor-pointer',
                    activeTab === 'instalasi' ? 'border-violet-400 ring-2 ring-violet-200/70 shadow-violet-50' : 'border-gray-200/80 hover:border-violet-200'
                ]"
                title="Klik untuk membuka tab Instalasi Pemasangan"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pasang Pending</span>
                    <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center border border-violet-100 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-violet-600">{{ globalInstall.pending + globalInstall.in_progress }}</span>
                    <span class="text-xs font-semibold text-gray-400">Antrian</span>
                </div>
                <div class="mt-2 text-[11px] font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Rollout ODC/ODP:</span>
                    <span class="font-bold text-violet-700">{{ globalInstall.rollout_completed }}/{{ globalInstall.rollout_total }}</span>
                </div>
            </div>
        </div>

        <!-- TOP ACHIEVERS / SMART PODIUM CARD -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Best Troubleshooter -->
            <div 
                @click="switchTab('gangguan')"
                class="bg-gradient-to-br from-rose-500/10 via-white to-white rounded-2xl p-4 border border-rose-200/60 shadow-sm flex items-center gap-3.5 hover:shadow-md transition-all cursor-pointer hover:border-rose-400 group"
                title="Klik untuk melihat tab Penanganan Gangguan"
            >
                <div class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center font-black text-xl shadow-md shadow-rose-200 shrink-0 group-hover:scale-105 transition-transform">
                    🛠️
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-extrabold text-rose-600 tracking-wider uppercase">Top Troubleshooter Gangguan</span>
                    <h2 class="text-sm font-black text-gray-900 truncate">
                        {{ topPerformers.trouble ? topPerformers.trouble.technician.name : 'Belum Ada Data' }}
                    </h2>
                    <p class="text-xs text-gray-500 font-medium">
                        <span class="font-bold text-rose-600">{{ topPerformers.trouble ? topPerformers.trouble.trouble.completed : 0 }}</span> Masalah Teratasi • SLA {{ topPerformers.trouble ? topPerformers.trouble.trouble.avg_resolution_hours : 0 }}j
                    </p>
                </div>
            </div>

            <!-- Best Installer -->
            <div 
                @click="switchTab('instalasi')"
                class="bg-gradient-to-br from-blue-500/10 via-white to-white rounded-2xl p-4 border border-blue-200/60 shadow-sm flex items-center gap-3.5 hover:shadow-md transition-all cursor-pointer hover:border-blue-400 group"
                title="Klik untuk melihat tab Instalasi Pemasangan"
            >
                <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-black text-xl shadow-md shadow-blue-200 shrink-0 group-hover:scale-105 transition-transform">
                    ⚡
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-extrabold text-blue-600 tracking-wider uppercase">Top Installer Pasang Baru</span>
                    <h2 class="text-sm font-black text-gray-900 truncate">
                        {{ topPerformers.installation ? topPerformers.installation.technician.name : 'Belum Ada Data' }}
                    </h2>
                    <p class="text-xs text-gray-500 font-medium">
                        <span class="font-bold text-blue-600">{{ topPerformers.installation ? topPerformers.installation.installation.completed : 0 }}</span> PSB Aktif • {{ topPerformers.installation ? topPerformers.installation.installation.surveys_feasible : 0 }} Survey
                    </p>
                </div>
            </div>

            <!-- MVP Overall -->
            <div 
                @click="switchTab('leaderboard')"
                class="bg-gradient-to-br from-amber-500/10 via-white to-white rounded-2xl p-4 border border-amber-200/60 shadow-sm flex items-center gap-3.5 hover:shadow-md transition-all cursor-pointer hover:border-amber-400 group"
                title="Klik untuk melihat tab Leaderboard & Analitik"
            >
                <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-black text-xl shadow-md shadow-amber-200 shrink-0 group-hover:scale-105 transition-transform">
                    👑
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-extrabold text-amber-600 tracking-wider uppercase">MVP Produktivitas Tertinggi</span>
                    <h2 class="text-sm font-black text-gray-900 truncate">
                        {{ topPerformers.overall ? topPerformers.overall.technician.name : 'Belum Ada Data' }}
                    </h2>
                    <p class="text-xs text-gray-500 font-medium">
                        Skor Kinerja: <span class="font-extrabold text-amber-600">{{ topPerformers.overall ? topPerformers.overall.score : 0 }} Poin</span> (#Rank 1)
                    </p>
                </div>
            </div>
        </div>

        <!-- MAIN TABBED INTERFACE -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            <!-- Navigation Tabs Bar -->
            <div class="flex border-b border-gray-200 px-4 pt-3 overflow-x-auto gap-2 bg-gray-50/40">
                <!-- Tab 1: Gangguan -->
                <button
                    v-if="can('monitoring_teknisi_tab_gangguan')"
                    @click="switchTab('gangguan')"
                    :class="[
                        'px-4 py-3 text-xs sm:text-sm font-bold flex items-center gap-2 border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'gangguan'
                            ? 'border-rose-600 text-rose-600 bg-white rounded-t-xl shadow-sm'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    1. Monitor Penanganan Gangguan
                    <span :class="['px-2 py-0.5 rounded-full text-xs font-extrabold', activeTab === 'gangguan' ? 'bg-rose-100 text-rose-700' : 'bg-gray-200 text-gray-600']">
                        {{ globalTrouble.completed }}/{{ globalTrouble.total }}
                    </span>
                </button>

                <!-- Tab 2: Instalasi -->
                <button
                    v-if="can('monitoring_teknisi_tab_instalasi')"
                    @click="switchTab('instalasi')"
                    :class="[
                        'px-4 py-3 text-xs sm:text-sm font-bold flex items-center gap-2 border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'instalasi'
                            ? 'border-blue-600 text-blue-600 bg-white rounded-t-xl shadow-sm'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    2. Monitor Instalasi Pemasangan
                    <span :class="['px-2 py-0.5 rounded-full text-xs font-extrabold', activeTab === 'instalasi' ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600']">
                        {{ globalInstall.completed }}/{{ globalInstall.total }}
                    </span>
                </button>

                <!-- Tab 3: Leaderboard -->
                <button
                    v-if="can('monitoring_teknisi_tab_leaderboard')"
                    @click="switchTab('leaderboard')"
                    :class="[
                        'px-4 py-3 text-xs sm:text-sm font-bold flex items-center gap-2 border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'leaderboard'
                            ? 'border-amber-600 text-amber-600 bg-white rounded-t-xl shadow-sm'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    3. Leaderboard & Analitik Cerdas
                    <span class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-amber-100 text-amber-700">
                        Skor
                    </span>
                </button>
            </div>

            <!-- TAB 1 CONTENT: PENANGANAN GANGGUAN -->
            <div v-show="activeTab === 'gangguan'" class="p-4 sm:p-6">
                <!-- Gangguan Breakdown Visual Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
                    <div class="bg-red-50/60 rounded-xl p-3 border border-red-100 text-center">
                        <span class="text-[10px] font-bold text-red-600 uppercase">PGK Komplain</span>
                        <div class="text-lg font-black text-red-700">{{ globalTrouble.pgk_count }}</div>
                    </div>
                    <div class="bg-orange-50/60 rounded-xl p-3 border border-orange-100 text-center">
                        <span class="text-[10px] font-bold text-orange-600 uppercase">PGJ Kabel/Jalur</span>
                        <div class="text-lg font-black text-orange-700">{{ globalTrouble.pgj_count }}</div>
                    </div>
                    <div class="bg-amber-50/60 rounded-xl p-3 border border-amber-100 text-center">
                        <span class="text-[10px] font-bold text-amber-600 uppercase">PGS Sigap</span>
                        <div class="text-lg font-black text-amber-700">{{ globalTrouble.pgs_count }}</div>
                    </div>
                    <div class="bg-purple-50/60 rounded-xl p-3 border border-purple-100 text-center">
                        <span class="text-[10px] font-bold text-purple-600 uppercase">CBP Cabut Alat</span>
                        <div class="text-lg font-black text-purple-700">{{ globalTrouble.cbp_count }}</div>
                    </div>
                    <div class="bg-indigo-50/60 rounded-xl p-3 border border-indigo-100 text-center col-span-2 sm:col-span-1">
                        <span class="text-[10px] font-bold text-indigo-600 uppercase">Tiket Umum</span>
                        <div class="text-lg font-black text-indigo-700">{{ globalTrouble.general_count }}</div>
                    </div>
                </div>

                <!-- Table Penanganan Gangguan -->
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-gray-50/80 font-bold text-gray-600 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Nama Teknisi</th>
                                <th class="py-3 px-3 text-center">Status Hari Ini</th>
                                <th class="py-3 px-3 text-center">Total Kasus</th>
                                <th class="py-3 px-3 text-center">Rincian Tiket</th>
                                <th class="py-3 px-3 text-center">Selesai (Closed)</th>
                                <th class="py-3 px-3 text-center">Proses / Antri</th>
                                <th class="py-3 px-4 text-center">Tingkat Beres (%)</th>
                                <th class="py-3 px-3 text-center">Rata2 SLA</th>
                                <th class="py-3 px-4 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="stat in filteredTechnicianStats" :key="stat.technician.id" class="hover:bg-gray-50/60 transition-colors">
                                <!-- Teknisi Info -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                            {{ stat.technician.initials }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-gray-900">{{ stat.technician.name }}</div>
                                            <div class="text-[11px] text-gray-500 font-medium">
                                                {{ stat.technician.area_name }} • {{ stat.technician.role_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Kerja Hari Ini -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-full text-[10px] font-bold inline-block',
                                        stat.technician.status_today.startsWith('Aktif')
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60'
                                            : 'bg-gray-100 text-gray-600'
                                    ]">
                                        {{ stat.technician.status_today }}
                                    </span>
                                </td>

                                <!-- Total Gangguan Ditangani -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-black text-sm text-gray-900">
                                    {{ stat.trouble.total }}
                                </td>

                                <!-- Breakdown Komposisi -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1 text-[11px] font-bold">
                                        <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700" title="PGK Komplain Pelanggan">{{ stat.trouble.breakdown.pgk }} PGK</span>
                                        <span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700" title="PGJ Gangguan Jalur">{{ stat.trouble.breakdown.pgj }} PGJ</span>
                                        <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-700" title="PGS Penanganan Sigap">{{ stat.trouble.breakdown.pgs }} PGS</span>
                                        <span class="px-1.5 py-0.5 rounded bg-purple-100 text-purple-700" title="CBP Cabut Perangkat">{{ stat.trouble.breakdown.cbp }} CBP</span>
                                    </div>
                                </td>

                                <!-- Selesai (Closed) -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-black text-emerald-600">
                                    {{ stat.trouble.completed }}
                                </td>

                                <!-- In Progress vs Pending -->
                                <td class="py-3 px-3 text-center whitespace-nowrap text-[11px] font-bold">
                                    <span class="text-blue-600">{{ stat.trouble.in_progress }} P</span>
                                    <span class="text-gray-400 mx-1">/</span>
                                    <span class="text-amber-600">{{ stat.trouble.pending }} A</span>
                                </td>

                                <!-- Progress Bar Rate -->
                                <td class="py-3 px-4 whitespace-nowrap text-center">
                                    <div class="w-28 mx-auto">
                                        <div class="flex justify-between text-[10px] font-bold mb-1">
                                            <span :class="stat.trouble.completion_rate >= 80 ? 'text-emerald-600' : 'text-gray-600'">
                                                {{ stat.trouble.completion_rate }}%
                                            </span>
                                            <span class="text-gray-400">{{ stat.trouble.completed }}/{{ stat.trouble.total }}</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="h-1.5 rounded-full transition-all duration-500" 
                                                :class="stat.trouble.completion_rate >= 80 ? 'bg-emerald-500' : (stat.trouble.completion_rate >= 50 ? 'bg-amber-500' : 'bg-rose-500')"
                                                :style="{ width: stat.trouble.completion_rate + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rata-rata SLA Jam -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-lg text-xs font-extrabold bg-gray-100 text-gray-800">
                                        {{ stat.trouble.avg_resolution_hours }} jam
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button 
                                            @click="openDetailModal(stat.technician, 'trouble')" 
                                            class="p-1.5 rounded-lg bg-gray-100 hover:bg-rose-50 hover:text-rose-600 text-gray-600 transition-colors"
                                            title="Buka Rekap Detail Tiket Teknisi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button 
                                            v-if="can('monitoring_teknisi_btn_whatsapp') && stat.technician.phone"
                                            @click="sendWhatsAppReminder(stat.technician, 'trouble', stat)" 
                                            class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 transition-colors"
                                            title="Kirim WhatsApp Evaluasi / Apresiasi ke Teknisi"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredTechnicianStats.length === 0">
                                <td colspan="9" class="py-8 text-center text-gray-500 font-medium">
                                    Tidak ada data teknisi yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2 CONTENT: INSTALASI PEMASANGAN (PSB) -->
            <div v-show="activeTab === 'instalasi'" class="p-4 sm:p-6">
                <!-- Funnel Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div class="bg-blue-50/60 rounded-xl p-3 border border-blue-100 text-center">
                        <span class="text-[10px] font-bold text-blue-600 uppercase">Jadwal PSB Total</span>
                        <div class="text-lg font-black text-blue-700">{{ globalInstall.total }}</div>
                    </div>
                    <div class="bg-teal-50/60 rounded-xl p-3 border border-teal-100 text-center">
                        <span class="text-[10px] font-bold text-teal-600 uppercase">PSB Selesai Aktif</span>
                        <div class="text-lg font-black text-teal-700">{{ globalInstall.completed }}</div>
                    </div>
                    <div class="bg-indigo-50/60 rounded-xl p-3 border border-indigo-100 text-center">
                        <span class="text-[10px] font-bold text-indigo-600 uppercase">Survey Lokasi Layak</span>
                        <div class="text-lg font-black text-indigo-700">{{ globalInstall.surveys_feasible }}/{{ globalInstall.surveys_total }}</div>
                    </div>
                    <div class="bg-violet-50/60 rounded-xl p-3 border border-violet-100 text-center">
                        <span class="text-[10px] font-bold text-violet-600 uppercase">Rollout ODC / ODP</span>
                        <div class="text-lg font-black text-violet-700">{{ globalInstall.rollout_completed }}/{{ globalInstall.rollout_total }}</div>
                    </div>
                </div>

                <!-- Table Instalasi Pemasangan -->
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-gray-50/80 font-bold text-gray-600 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Nama Teknisi</th>
                                <th class="py-3 px-3 text-center">Status Hari Ini</th>
                                <th class="py-3 px-3 text-center">Total Jadwal PSB</th>
                                <th class="py-3 px-3 text-center">Pasang Selesai</th>
                                <th class="py-3 px-3 text-center">Sedang Pasang</th>
                                <th class="py-3 px-3 text-center">Menunggu Pasang</th>
                                <th class="py-3 px-3 text-center">Survey Layak</th>
                                <th class="py-3 px-3 text-center">Rollout ODC/ODP</th>
                                <th class="py-3 px-4 text-center">Keberhasilan (%)</th>
                                <th class="py-3 px-4 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="stat in filteredTechnicianStats" :key="stat.technician.id" class="hover:bg-gray-50/60 transition-colors">
                                <!-- Teknisi Info -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                            {{ stat.technician.initials }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-gray-900">{{ stat.technician.name }}</div>
                                            <div class="text-[11px] text-gray-500 font-medium">
                                                {{ stat.technician.area_name }} • {{ stat.technician.role_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Hari Ini -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-full text-[10px] font-bold inline-block',
                                        stat.technician.status_today.startsWith('Aktif')
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60'
                                            : 'bg-gray-100 text-gray-600'
                                    ]">
                                        {{ stat.technician.status_today }}
                                    </span>
                                </td>

                                <!-- Total Jadwal PSB -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-black text-sm text-gray-900">
                                    {{ stat.installation.total }}
                                </td>

                                <!-- Selesai Pasang -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-black text-teal-600">
                                    {{ stat.installation.completed }}
                                </td>

                                <!-- Sedang Pasang -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-bold text-blue-600">
                                    {{ stat.installation.in_progress }}
                                </td>

                                <!-- Menunggu Pasang -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-bold text-amber-600">
                                    {{ stat.installation.pending }}
                                </td>

                                <!-- Survey Layak -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-bold text-indigo-700">
                                    {{ stat.installation.surveys_feasible }}/{{ stat.installation.surveys_total }}
                                </td>

                                <!-- Rollout Selesai -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-bold text-violet-700">
                                    {{ stat.installation.rollout_completed }}/{{ stat.installation.rollout_total }}
                                </td>

                                <!-- Progress Bar Rate -->
                                <td class="py-3 px-4 whitespace-nowrap text-center">
                                    <div class="w-28 mx-auto">
                                        <div class="flex justify-between text-[10px] font-bold mb-1">
                                            <span :class="stat.installation.completion_rate >= 80 ? 'text-teal-600' : 'text-gray-600'">
                                                {{ stat.installation.completion_rate }}%
                                            </span>
                                            <span class="text-gray-400">{{ stat.installation.completed }}/{{ stat.installation.total }}</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="h-1.5 rounded-full transition-all duration-500" 
                                                :class="stat.installation.completion_rate >= 80 ? 'bg-teal-500' : (stat.installation.completion_rate >= 50 ? 'bg-blue-500' : 'bg-amber-500')"
                                                :style="{ width: stat.installation.completion_rate + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button 
                                            @click="openDetailModal(stat.technician, 'installation')" 
                                            class="p-1.5 rounded-lg bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-600 transition-colors"
                                            title="Buka Rekap Detail Pasang Baru Pelanggan"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button 
                                            v-if="can('monitoring_teknisi_btn_whatsapp') && stat.technician.phone"
                                            @click="sendWhatsAppReminder(stat.technician, 'installation', stat)" 
                                            class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 transition-colors"
                                            title="Kirim WhatsApp Reminder / Apresiasi ke Teknisi"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredTechnicianStats.length === 0">
                                <td colspan="10" class="py-8 text-center text-gray-500 font-medium">
                                    Tidak ada data teknisi yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3 CONTENT: LEADERBOARD & REKAP CERDAS -->
            <div v-show="activeTab === 'leaderboard'" class="p-4 sm:p-6">
                <!-- Bar Chart Comparison (Top 8 Performers) -->
                <div class="bg-gray-50/60 rounded-2xl p-4 sm:p-5 border border-gray-200/80 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-sm font-black text-gray-900">Perbandingan Output Kerja Top Teknisi</h2>
                            <p class="text-xs text-gray-500">Perbandingan jumlah Gangguan Selesai vs Instalasi Selesai</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-bold">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-500"></span> Gangguan Selesai</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-600"></span> Pasang Selesai</span>
                        </div>
                    </div>
                    <div class="h-64">
                        <apexchart 
                            v-if="chartData.categories.length > 0" 
                            type="bar" 
                            height="100%" 
                            :options="barChartOptions" 
                            :series="barChartSeries"
                        ></apexchart>
                        <div v-else class="h-full flex items-center justify-center text-xs text-gray-400 font-bold">
                            Belum cukup data untuk menampilkan grafik perbandingan
                        </div>
                    </div>
                </div>

                <!-- Leaderboard Table -->
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-gray-50/80 font-bold text-gray-600 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4 text-center">Rank</th>
                                <th class="py-3 px-4">Nama Teknisi</th>
                                <th class="py-3 px-3 text-center">Total Skor Kinerja</th>
                                <th class="py-3 px-3 text-center">Gangguan Selesai</th>
                                <th class="py-3 px-3 text-center">PSB Selesai</th>
                                <th class="py-3 px-3 text-center">Survey Layak</th>
                                <th class="py-3 px-3 text-center">Rollout Selesai</th>
                                <th class="py-3 px-4 text-center">Badge Pencapaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="stat in filteredTechnicianStats" :key="stat.technician.id" class="hover:bg-amber-50/30 transition-colors">
                                <!-- Rank Podium Badge -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span v-if="stat.rank === 1" class="w-7 h-7 rounded-full bg-amber-400 text-white font-black text-xs inline-flex items-center justify-center shadow-md shadow-amber-200">
                                        🥇 1
                                    </span>
                                    <span v-else-if="stat.rank === 2" class="w-7 h-7 rounded-full bg-slate-300 text-slate-800 font-black text-xs inline-flex items-center justify-center shadow-sm">
                                        🥈 2
                                    </span>
                                    <span v-else-if="stat.rank === 3" class="w-7 h-7 rounded-full bg-amber-700 text-white font-black text-xs inline-flex items-center justify-center shadow-sm">
                                        🥉 3
                                    </span>
                                    <span v-else class="text-xs font-bold text-gray-500">
                                        #{{ stat.rank }}
                                    </span>
                                </td>

                                <!-- Teknisi -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-extrabold text-gray-900">{{ stat.technician.name }}</div>
                                    <div class="text-[11px] text-gray-500">{{ stat.technician.area_name }}</div>
                                </td>

                                <!-- Total Skor Kinerja -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span class="px-3 py-1 rounded-full text-xs font-black bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-sm">
                                        {{ stat.score }} Pts
                                    </span>
                                </td>

                                <!-- Gangguan Selesai -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-black text-rose-600">
                                    {{ stat.trouble.completed }}
                                </td>

                                <!-- Pasang Selesai -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-black text-blue-600">
                                    {{ stat.installation.completed }}
                                </td>

                                <!-- Survey Layak -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-bold text-indigo-600">
                                    {{ stat.installation.surveys_feasible }}
                                </td>

                                <!-- Rollout Selesai -->
                                <td class="py-3 px-3 text-center whitespace-nowrap font-bold text-violet-600">
                                    {{ stat.installation.rollout_completed }}
                                </td>

                                <!-- Badge Pencapaian -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <span v-if="stat.trouble.completed >= 10" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700">
                                            🔥 Troubleshooter Pro
                                        </span>
                                        <span v-if="stat.installation.completed >= 10" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700">
                                            ⚡ Master Installer
                                        </span>
                                        <span v-if="stat.score >= 50" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800">
                                            ⭐ Elite Workhorse
                                        </span>
                                        <span v-if="stat.score < 50 && stat.trouble.completed < 10 && stat.installation.completed < 10" class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                            Aktif Bertugas
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DRILLDOWN DETAIL INSPECTION MODAL -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in">
                <div class="bg-white rounded-2xl max-w-4xl w-full p-6 shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 font-bold flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-black text-gray-900">
                                    Rincian Tugas: {{ activeDetailTechnician?.name }}
                                </h2>
                                <p class="text-xs text-gray-500">
                                    Tipe: <span class="font-bold uppercase text-indigo-600">{{ activeDetailType === 'trouble' ? 'Penanganan Gangguan' : 'Instalasi Pemasangan (PSB)' }}</span> • Periode Terpilih
                                </p>
                            </div>
                        </div>
                        <button @click="showDetailModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body / Content -->
                    <div class="py-4 overflow-y-auto flex-1">
                        <div v-if="isLoadingDetail" class="flex flex-col items-center justify-center py-12 gap-2 text-gray-400">
                            <svg class="w-8 h-8 animate-spin text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="text-xs font-bold">Memuat log tugas teknisi...</span>
                        </div>
                        <div v-else-if="detailItems.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-xs font-bold">Tidak ada riwayat penugasan ditemukan pada periode ini.</p>
                        </div>
                        <div v-else class="space-y-2.5">
                            <div 
                                v-for="(item, idx) in detailItems" 
                                :key="idx" 
                                class="p-3.5 rounded-xl border border-gray-100 hover:border-indigo-200 bg-gray-50/50 hover:bg-white transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-100 text-indigo-700">
                                            {{ item.type }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-900">{{ item.number }}</span>
                                        <span :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                                            item.status === 'Selesai' || item.status === 'resolved' || item.status === 'closed'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : (item.status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700')
                                        ]">
                                            {{ item.status }}
                                        </span>
                                    </div>
                                    <div class="text-xs font-semibold text-gray-800">{{ item.title || item.customer_name }}</div>
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                        <span>👤 Pelanggan: <span class="font-bold text-gray-700">{{ item.customer_name }}</span></span>
                                        <span v-if="item.package">📦 {{ item.package }}</span>
                                        <span v-if="item.area">📍 {{ item.area }}</span>
                                        <span v-if="item.address" class="truncate max-w-xs">🏠 {{ item.address }}</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 flex sm:flex-col items-center sm:items-end justify-between sm:justify-center">
                                    <span class="text-xs font-bold text-gray-700">{{ item.date }}</span>
                                    <span class="text-[11px] text-gray-400">{{ item.time || (item.resolved_at ? ('Selesai: ' + item.resolved_at) : '') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end">
                        <button 
                            @click="showDetailModal = false" 
                            class="px-5 py-2 text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';
import VueApexCharts from 'vue3-apexcharts';
import Swal from 'sweetalert2';

const apexchart = VueApexCharts;
const { can } = usePermission();

const props = defineProps({
    technicians: Array,
    areas: Array,
    technicianStats: Array,
    globalTrouble: Object,
    globalInstall: Object,
    topPerformers: Object,
    chartData: Object,
    filters: Object,
});

// State Filters
const activeTab = ref(props.filters?.tab || 'gangguan');
const selectedPeriod = ref(props.filters?.period || 'this_month');
const selectedArea = ref(props.filters?.area_id || '');
const selectedTechnician = ref(props.filters?.technician_id || '');
const searchQuery = ref('');
const customStartDate = ref(props.filters?.start_date || '');
const customEndDate = ref(props.filters?.end_date || '');

// Polling Auto Refresh
const autoRefresh = ref(false);
const isRefreshing = ref(false);
let refreshTimer = null;

// Modal Drilldown State
const showDetailModal = ref(false);
const activeDetailTechnician = ref(null);
const activeDetailType = ref('trouble');
const detailItems = ref([]);
const isLoadingDetail = ref(false);

// Filtered List by Search Query
const filteredTechnicianStats = computed(() => {
    if (!searchQuery.value) return props.technicianStats;
    const query = searchQuery.value.toLowerCase();
    return props.technicianStats.filter(s => 
        s.technician.name.toLowerCase().includes(query) ||
        (s.technician.area_name && s.technician.area_name.toLowerCase().includes(query))
    );
});

// Switch Tab
const switchTab = (tab) => {
    activeTab.value = tab;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
};

// Handle Period Change
const handlePeriodChange = () => {
    if (selectedPeriod.value !== 'custom') {
        applyFilters();
    }
};

// Apply Filters to Backend
const applyFilters = () => {
    isRefreshing.value = true;
    router.get('/monitoring-teknisi', {
        period: selectedPeriod.value,
        start_date: customStartDate.value,
        end_date: customEndDate.value,
        area_id: selectedArea.value,
        technician_id: selectedTechnician.value,
        tab: activeTab.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

// Auto Refresh Toggle
const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        startPolling();
    } else {
        stopPolling();
    }
};

const startPolling = () => {
    stopPolling();
    refreshTimer = setInterval(() => {
        applyFilters();
    }, 30000);
};

const stopPolling = () => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
};

// Drilldown Modal Opener
const openDetailModal = async (technician, type) => {
    activeDetailTechnician.value = technician;
    activeDetailType.value = type;
    showDetailModal.value = true;
    isLoadingDetail.value = true;
    detailItems.value = [];

    try {
        const queryParams = new URLSearchParams({
            type: type,
            period: selectedPeriod.value,
            start_date: customStartDate.value || '',
            end_date: customEndDate.value || '',
        });

        const res = await fetch(`/monitoring-teknisi/detail/${technician.id}?${queryParams.toString()}`);
        const data = await res.json();
        if (data.success) {
            detailItems.value = data.items || [];
        }
    } catch (e) {
        console.error('Error fetching detail:', e);
    } finally {
        isLoadingDetail.value = false;
    }
};

// WhatsApp Direct Notification
const sendWhatsAppReminder = (technician, type, stat) => {
    const phone = technician.phone ? technician.phone.replace(/^0/, '62').replace(/\D/g, '') : '';
    if (!phone) {
        Swal.fire({
            title: 'Nomor WhatsApp Tidak Ada',
            text: `Nomor telepon untuk teknisi ${technician.name} belum tersimpan.`,
            icon: 'warning',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    let message = '';
    if (type === 'trouble') {
        message = `Halo Rekan *${technician.name}*,\n\nBerikut ringkasan kinerja Penanganan Gangguan Anda periode ini:\n- Total Ditangani: *${stat.trouble.total} kasus*\n- Selesai (Closed): *${stat.trouble.completed} tiket*\n- Sedang Proses: *${stat.trouble.in_progress}*\n- Antrian Pending: *${stat.trouble.pending}*\n- SLA Rata-rata: *${stat.trouble.avg_resolution_hours} jam*\n\nTerima kasih atas kerja keras dan dedikasinya dalam menjaga kualitas jaringan! 👍🚀`;
    } else {
        message = `Halo Rekan *${technician.name}*,\n\nBerikut ringkasan kinerja Pemasangan Baru (PSB) Anda periode ini:\n- Total Jadwal Pasang: *${stat.installation.total}*\n- Selesai Terpasang: *${stat.installation.completed} pelanggan*\n- Sedang Proses: *${stat.installation.in_progress}*\n- Antrian Pasang: *${stat.installation.pending}*\n- Survey Selesai: *${stat.installation.surveys_feasible}*\n\nTetap semangat dan utamakan keselamatan kerja di lapangan! ⚡🌐`;
    }

    const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
    window.open(waUrl, '_blank');
};

// Export to CSV
const exportToCsv = () => {
    const headers = [
        'Ranking', 'Nama Teknisi', 'Wilayah', 'Status Hari Ini',
        'Total Gangguan', 'Gangguan Selesai', 'Gangguan Proses', 'Gangguan Pending', 'SLA Jam',
        'Total Pasang PSB', 'Pasang Selesai', 'Pasang Proses', 'Pasang Pending',
        'Survey Selesai', 'Rollout Selesai', 'Total Skor Kinerja'
    ];

    const rows = props.technicianStats.map(s => [
        s.rank,
        `"${s.technician.name}"`,
        `"${s.technician.area_name || '-'}"`,
        `"${s.technician.status_today}"`,
        s.trouble.total,
        s.trouble.completed,
        s.trouble.in_progress,
        s.trouble.pending,
        s.trouble.avg_resolution_hours,
        s.installation.total,
        s.installation.completed,
        s.installation.in_progress,
        s.installation.pending,
        s.installation.surveys_feasible,
        s.installation.rollout_completed,
        s.score
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `rekap_kinerja_teknisi_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Bar Chart ApexCharts Configuration
const barChartSeries = computed(() => [
    {
        name: 'Gangguan Selesai',
        data: props.chartData?.troubleSeries || [],
    },
    {
        name: 'Pasang Selesai',
        data: props.chartData?.installSeries || [],
    }
]);

const barChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    colors: ['#f43f5e', '#2563eb'],
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '45%',
            borderRadius: 6,
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    xaxis: {
        categories: props.chartData?.categories || [],
        labels: {
            style: { fontSize: '11px', fontWeight: 600 }
        }
    },
    yaxis: {
        title: { text: 'Jumlah Kasus / PSB' },
    },
    fill: { opacity: 1 },
    tooltip: {
        y: {
            formatter: (val) => val + ' Kasus'
        }
    }
}));

onMounted(() => {
    // If URL has tab param, match it
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab')) {
        activeTab.value = urlParams.get('tab');
    }
});

onUnmounted(() => {
    stopPolling();
});
</script>
