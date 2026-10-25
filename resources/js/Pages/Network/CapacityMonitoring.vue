<template>
    <AppLayout title="Monitoring Kapasitas ODC & ODP">
        <div class="space-y-6">
            <!-- ── Top Header & Action Controls ──────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </span>
                        <div>
                            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                                Monitoring Kapasitas ODC & ODP
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Live Analyzer</span>
                            </h1>
                            <p class="text-xs text-slate-500 mt-0.5">Pemantauan ketersediaan slot distribusi ODC, port pelanggan ODP, dan kelayakan buka perangkat baru</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Tombol Rollout Work Order -->
                    <Link
                        v-if="can('network_capacity_btn_rollout') || can('infrastructure_work_orders_create')"
                        href="/infrastructure/work-orders/create"
                        class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm hover:shadow flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>⚡ Rollout Baru</span>
                    </Link>

                    <!-- Export CSV -->
                    <button
                        v-if="can('network_capacity_export')"
                        @click="handleExportCsv"
                        class="px-3.5 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        title="Export data monitoring kapasitas ke file CSV"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Export CSV</span>
                    </button>

                    <!-- View Toggle -->
                    <div class="flex items-center bg-slate-100 p-0.5 rounded-xl border border-slate-200">
                        <button
                            type="button"
                            @click="viewMode = 'table'"
                            :class="['p-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1', viewMode === 'table' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800']"
                            title="Tampilan Tabel"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'grid'"
                            :class="['p-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1', viewMode === 'grid' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800']"
                            title="Tampilan Kartu / Grid"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Active Filter Bar (Jika Ada Filter Aktif) ──────────── -->
            <div v-if="hasActiveFilter" class="bg-indigo-50/70 border border-indigo-200/80 rounded-2xl p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs shadow-xs animate-fade-in">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-indigo-950 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter Aktif:
                    </span>
                    <span v-if="selectedAreaName" class="px-2.5 py-1 bg-white border border-indigo-200 rounded-lg text-indigo-800 font-bold shadow-xs flex items-center gap-1">
                        📍 Area: {{ selectedAreaName }}
                    </span>
                    <span v-if="selectedOltName" class="px-2.5 py-1 bg-white border border-indigo-200 rounded-lg text-indigo-800 font-bold shadow-xs flex items-center gap-1">
                        ⚡ OLT: {{ selectedOltName }}
                    </span>
                    <span v-if="searchQuery" class="px-2.5 py-1 bg-white border border-indigo-200 rounded-lg text-indigo-800 font-bold shadow-xs flex items-center gap-1">
                        🔍 "{{ searchQuery }}"
                    </span>
                    <span v-if="selectedStatus !== 'all'" class="px-2.5 py-1 bg-white border border-indigo-200 rounded-lg text-indigo-800 font-bold shadow-xs flex items-center gap-1">
                        🏷️ {{ getStatusLabel(selectedStatus) }}
                    </span>
                    <span v-if="selectedFeasibility !== 'all'" class="px-2.5 py-1 bg-white border border-indigo-200 rounded-lg text-indigo-800 font-bold shadow-xs flex items-center gap-1">
                        🎯 {{ selectedFeasibility === 'can_open' ? (currentTab === 'odc' ? 'Bisa Buka ODP' : 'Bisa Pasang Baru') : (currentTab === 'odc' ? 'Slot ODC Penuh' : 'ODP Penuh') }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-slate-500 font-medium">
                        Card &amp; statistik otomatis mengikuti filter
                    </span>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="px-3 py-1 bg-white hover:bg-rose-50 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg font-bold shadow-xs transition cursor-pointer flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- ── Global KPI Cards ──────────────────────────────────── -->
            <div v-if="can('network_capacity_card_stats')" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Slot Buka ODP di ODC -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kapasitas Slot ODC</span>
                        <span class="p-2 rounded-xl bg-violet-50 text-violet-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900">{{ kpi.available_odc_slots }}</span>
                        <span class="text-xs font-semibold text-slate-500">/ {{ kpi.total_odc_slots }} Slot Tersedia</span>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-[11px] font-semibold text-slate-600 mb-1">
                            <span>Terpakai: {{ kpi.used_odc_slots }} ODP</span>
                            <span>{{ kpi.odc_usage_percent }}% Full</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-violet-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${kpi.odc_usage_percent}%` }"></div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="text-emerald-600 font-bold">🟢 {{ kpi.odc_available_count }} ODC Ada Slot</span>
                        <span class="text-rose-600 font-bold">🔴 {{ kpi.odc_full_count }} ODC Penuh</span>
                    </div>
                </div>

                <!-- Card 2: Port Pelanggan di ODP -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Port Pelanggan ODP</span>
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900">{{ kpi.available_odp_ports }}</span>
                        <span class="text-xs font-semibold text-slate-500">/ {{ kpi.total_odp_ports }} Port Ready</span>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-[11px] font-semibold text-slate-600 mb-1">
                            <span>Terisi: {{ kpi.used_odp_ports }} Pelanggan</span>
                            <span>{{ kpi.odp_usage_percent }}% Terpakai</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${kpi.odp_usage_percent}%` }"></div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="text-cyan-600 font-bold">⚪ {{ kpi.odp_empty_count }} ODP Kosong</span>
                        <span class="text-emerald-700 font-bold">🟢 {{ kpi.odp_available_count }} Siap Jual</span>
                    </div>
                </div>

                <!-- Card 3: Status Kritis & Penuh -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Waspada & Penuh</span>
                        <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-rose-600">{{ kpi.odp_full_count }}</span>
                        <span class="text-xs font-semibold text-slate-500">ODP Sudah 100% Penuh</span>
                    </div>
                    <div class="mt-3 space-y-1.5">
                        <div class="flex items-center justify-between text-xs bg-amber-50 text-amber-800 px-2.5 py-1 rounded-lg font-semibold">
                            <span>Sisa Port Kritis (&le; 2 port):</span>
                            <span class="font-bold">{{ kpi.odp_critical_count }} ODP</span>
                        </div>
                        <div class="flex items-center justify-between text-xs bg-rose-50 text-rose-800 px-2.5 py-1 rounded-lg font-semibold">
                            <span>ODC Slot Penuh (0 slot):</span>
                            <span class="font-bold">{{ kpi.odc_full_count }} ODC</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Kemampuan Buka ODC/ODP Baru -->
                <div class="bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 text-white rounded-2xl p-5 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-white/5 rounded-full pointer-events-none"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-200">Kesiapan Ekspansi</span>
                        <span class="p-1.5 bg-white/10 rounded-lg text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-white">
                        {{ kpi.available_odc_slots > 0 ? 'Bisa Buka ODP' : (kpi.total_odc > 0 ? 'ODC Penuh' : 'Tidak Ada Data') }}
                    </div>
                    <p class="text-[11px] text-indigo-200 mt-1 leading-relaxed">
                        <template v-if="kpi.total_odc > 0">
                            {{ kpi.available_odc_slots }} slot penarikan ODP baru siap digunakan {{ hasActiveFilter ? 'pada filter ini' : 'di seluruh ODC aktif' }}.
                        </template>
                        <template v-else>
                            Tidak ada infrastruktur ODC yang cocok dengan filter yang dipilih.
                        </template>
                    </p>
                    <div class="mt-3 pt-2.5 border-t border-white/10 flex items-center justify-between text-[11px]">
                        <span class="text-indigo-300">Total ODC: <b>{{ kpi.total_odc }}</b></span>
                        <span class="text-indigo-300">Total ODP: <b>{{ kpi.total_odp }}</b></span>
                    </div>
                </div>
            </div>

            <!-- ── Smart Insights / AI Advisor Alert Banners ──────────── -->
            <div v-if="smart_insights && smart_insights.length > 0" class="space-y-2.5">
                <div
                    v-for="(insight, idx) in smart_insights"
                    :key="idx"
                    :class="[
                        'p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 transition-all',
                        insight.type === 'danger' ? 'bg-rose-50/80 border-rose-200 text-rose-900' : '',
                        insight.type === 'warning' ? 'bg-amber-50/80 border-amber-200 text-amber-900' : '',
                        insight.type === 'success' ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900' : '',
                        insight.type === 'info' ? 'bg-cyan-50/80 border-cyan-200 text-cyan-900' : ''
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-xl">
                            <template v-if="insight.type === 'danger'">🚨</template>
                            <template v-else-if="insight.type === 'warning'">⚠️</template>
                            <template v-else-if="insight.type === 'success'">🚀</template>
                            <template v-else>💡</template>
                        </span>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-wider">{{ insight.title }}</h4>
                            <p class="text-xs mt-0.5 opacity-90 leading-relaxed">{{ insight.desc }}</p>
                        </div>
                    </div>
                    <button
                        v-if="insight.action_label"
                        type="button"
                        @click="applyInsightFilter(insight)"
                        class="self-start sm:self-auto px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-xs shrink-0 flex items-center gap-1.5 bg-white text-slate-800 hover:bg-slate-50 border border-slate-200"
                    >
                        <span>{{ insight.action_label }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            <!-- ── Navigation Tabs ───────────────────────────────────── -->
            <div class="border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <nav class="flex space-x-2 -mb-px overflow-x-auto pb-1 sm:pb-0" aria-label="Tabs">
                    <button
                        v-if="can('network_capacity_tab_odp')"
                        @click="switchTab('odp')"
                        :class="[
                            'py-3 px-4 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 whitespace-nowrap',
                            currentTab === 'odp'
                                ? 'border-indigo-600 text-indigo-600 bg-white shadow-xs'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                        ]"
                    >
                        <span>🔌 Monitoring Port ODP (Pelanggan)</span>
                        <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black', currentTab === 'odp' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600']">
                            {{ odps.length }}
                        </span>
                    </button>

                    <button
                        v-if="can('network_capacity_tab_odc')"
                        @click="switchTab('odc')"
                        :class="[
                            'py-3 px-4 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 whitespace-nowrap',
                            currentTab === 'odc'
                                ? 'border-indigo-600 text-indigo-600 bg-white shadow-xs'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                        ]"
                    >
                        <span>🔀 Monitoring Slot ODC (Distribusi)</span>
                        <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black', currentTab === 'odc' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600']">
                            {{ odcs.length }}
                        </span>
                    </button>

                    <button
                        v-if="can('network_capacity_tab_expansion')"
                        @click="switchTab('expansion')"
                        :class="[
                            'py-3 px-4 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 whitespace-nowrap',
                            currentTab === 'expansion'
                                ? 'border-indigo-600 text-indigo-600 bg-white shadow-xs'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                        ]"
                    >
                        <span>🧠 Smart Expansion Matrix (Per Area)</span>
                        <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black', currentTab === 'expansion' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600']">
                            {{ expansion_matrix.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ── Filters Toolbar ───────────────────────────────────── -->
            <div v-if="can('network_capacity_filter')" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="relative lg:col-span-2">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama perangkat, alamat, area..."
                            class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                            @input="debouncedSearch"
                        />
                    </div>

                    <!-- Filter Area -->
                    <div>
                        <select
                            v-model="selectedArea"
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                        >
                            <option value="">Semua Wilayah/Area</option>
                            <option v-for="area in filter_options.areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>

                    <!-- Filter OLT -->
                    <div>
                        <select
                            v-model="selectedOlt"
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                        >
                            <option value="">Semua OLT</option>
                            <option v-for="olt in filter_options.olts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                        </select>
                    </div>

                    <!-- Filter Kelayakan Buka / Pasang -->
                    <div>
                        <select
                            v-model="selectedFeasibility"
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                        >
                            <option value="all">Semua Kelayakan</option>
                            <option value="can_open">✅ {{ currentTab === 'odc' ? 'Bisa Buka ODP' : 'Bisa Pasang Baru' }}</option>
                            <option value="cannot_open">❌ {{ currentTab === 'odc' ? 'Slot ODC Penuh' : 'ODP Penuh' }}</option>
                        </select>
                    </div>
                </div>

                <!-- Status Pills -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Status Kapasitas:</span>
                    <button
                        type="button"
                        @click="setStatusFilter('all')"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', selectedStatus === 'all' ? 'bg-slate-800 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        @click="setStatusFilter('empty')"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1', selectedStatus === 'empty' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200']"
                    >
                        <span>⚪ Masih Kosong (0%)</span>
                    </button>
                    <button
                        type="button"
                        @click="setStatusFilter('available')"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1', selectedStatus === 'available' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200']"
                    >
                        <span>🟢 Ada Slot</span>
                    </button>
                    <button
                        type="button"
                        @click="setStatusFilter('critical')"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1', selectedStatus === 'critical' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200']"
                    >
                        <span>🟠 Kritis (&le; 2 port)</span>
                    </button>
                    <button
                        type="button"
                        @click="setStatusFilter('full')"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1', selectedStatus === 'full' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200']"
                    >
                        <span>🔴 Penuh (0 Slot)</span>
                    </button>

                    <!-- Reset Filter Button -->
                    <button
                        v-if="hasActiveFilter"
                        type="button"
                        @click="resetFilters"
                        class="ml-auto text-xs text-rose-600 hover:text-rose-800 font-bold flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- ── TAB 1: MONITORING PORT ODP ────────────────────────── -->
            <div v-if="currentTab === 'odp'">
                <!-- Table View -->
                <div v-if="viewMode === 'table'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="py-3.5 px-4">Nama ODP & Area</th>
                                    <th class="py-3.5 px-4">ODC Induk & PON</th>
                                    <th class="py-3.5 px-4 text-center">Total Port</th>
                                    <th class="py-3.5 px-4 text-center">Port Terpakai</th>
                                    <th class="py-3.5 px-4 text-center">Sisa Slot</th>
                                    <th class="py-3.5 px-4 text-center">Utilisasi</th>
                                    <th class="py-3.5 px-4">Status & Kelayakan Pasang</th>
                                    <th class="py-3.5 px-4">Rekomendasi Cerdas</th>
                                    <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr v-for="odp in odps" :key="odp.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-black text-slate-900 text-sm flex items-center gap-1.5">
                                            <span>{{ odp.name }}</span>
                                            <span v-if="odp.type === 'Split 2'" class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-purple-50 text-purple-700 border border-purple-200">Split 2</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                                            <span class="font-semibold text-indigo-600">{{ odp.area_name || '-' }}</span>
                                            <span v-if="odp.address">&bull; {{ odp.address }}</span>
                                        </div>
                                        <!-- Koordinat Pills -->
                                        <div v-if="odp.latitude && odp.longitude" class="flex items-center gap-1 mt-1 font-mono text-[10px]">
                                            <span class="bg-slate-100 px-1 py-0.2 rounded text-slate-600">Lat: {{ odp.latitude }}</span>
                                            <span class="bg-slate-100 px-1 py-0.2 rounded text-slate-600">Long: {{ odp.longitude }}</span>
                                            <a :href="`https://maps.google.com/?q=${odp.latitude},${odp.longitude}`" target="_blank" class="text-emerald-600 hover:underline font-bold">Maps ↗</a>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800">{{ odp.parent_odc_name || '-' }}</div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ odp.olt_name || '-' }} (PON {{ odp.pon_port || '-' }})
                                        </div>
                                        <div class="mt-1 text-[10px] flex items-center gap-1">
                                            <span :class="odp.parent_odc_can_expand ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                                                ODC Sisa: {{ odp.parent_odc_remaining }} Slot
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-slate-900">
                                        {{ odp.total_ports }} Port
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold">
                                        <span class="text-indigo-600">{{ odp.used_ports }}</span>
                                        <span class="text-[10px] text-slate-400 block font-normal">{{ odp.active_customers_count }} Aktif</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span :class="[
                                            'inline-flex px-2.5 py-1 rounded-xl font-mono font-black text-xs',
                                            odp.available_ports === 0 ? 'bg-rose-100 text-rose-700' : (odp.available_ports <= 2 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')
                                        ]">
                                            {{ odp.available_ports }} Port
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="font-bold text-slate-800 mb-1">{{ odp.usage_percent }}%</div>
                                        <div class="w-16 mx-auto bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div
                                                :class="[
                                                    'h-1.5 rounded-full transition-all',
                                                    odp.usage_percent >= 100 ? 'bg-rose-500' : (odp.usage_percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500')
                                                ]"
                                                :style="{ width: `${odp.usage_percent}%` }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span :class="[
                                            'px-2.5 py-1 rounded-full text-[11px] font-black inline-flex items-center gap-1',
                                            odp.status_category === 'empty' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : '',
                                            odp.status_category === 'available' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '',
                                            odp.status_category === 'critical' ? 'bg-amber-50 text-amber-800 border border-amber-200 animate-pulse' : '',
                                            odp.status_category === 'full' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ''
                                        ]">
                                            {{ odp.status_label }}
                                        </span>
                                        <div class="mt-1">
                                            <span v-if="odp.can_register_customer" class="text-[10px] font-bold text-emerald-600 flex items-center gap-0.5">
                                                ✓ Bisa Pasang Baru
                                            </span>
                                            <span v-else class="text-[10px] font-bold text-rose-600 flex items-center gap-0.5">
                                                ✕ Slot Penuh / Nonaktif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs">
                                        <p class="text-xs text-slate-700 leading-tight">{{ odp.recommendation }}</p>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Detail Button -->
                                            <Link
                                                :href="`/odps/${odp.id}`"
                                                class="p-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 rounded-lg transition-colors"
                                                title="Lihat Detail ODP"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </Link>

                                            <!-- Rollout ODP Cabang jika penuh -->
                                            <Link
                                                v-if="odp.available_ports === 0 && odp.parent_odc_can_expand"
                                                :href="`/infrastructure/work-orders/create?device_type=odp&odc_id=${odp.parent_odc_id}&area_id=${odp.area_id}`"
                                                class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition-colors font-bold text-[10px]"
                                                title="Tarik ODP Baru dari ODC ini"
                                            >
                                                ⚡ Rollout
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="odps.length === 0">
                                    <td colspan="9" class="py-12 text-center text-slate-400">
                                        <div class="text-3xl mb-2">🔍</div>
                                        <p class="font-bold">Tidak ada data ODP yang sesuai filter</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grid Card View -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="odp in odps"
                        :key="odp.id"
                        class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div>
                            <!-- Header Card -->
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div>
                                    <h3 class="font-black text-slate-900 text-base leading-tight">{{ odp.name }}</h3>
                                    <span class="text-xs font-semibold text-indigo-600">{{ odp.area_name || '-' }}</span>
                                </div>
                                <span :class="[
                                    'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider',
                                    odp.status_category === 'empty' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : '',
                                    odp.status_category === 'available' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '',
                                    odp.status_category === 'critical' ? 'bg-amber-50 text-amber-800 border border-amber-200 animate-pulse' : '',
                                    odp.status_category === 'full' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ''
                                ]">
                                    {{ odp.status_label }}
                                </span>
                            </div>

                            <!-- Induk ODC & Port -->
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs space-y-1.5 mb-3">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">ODC Induk:</span>
                                    <span class="font-bold text-slate-800">{{ odp.parent_odc_name || '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">OLT & PON:</span>
                                    <span class="font-semibold text-slate-700">{{ odp.olt_name || '-' }} (PON {{ odp.pon_port || '-' }})</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Alamat:</span>
                                    <span class="font-medium text-slate-700 truncate max-w-[180px]">{{ odp.address || '-' }}</span>
                                </div>
                            </div>

                            <!-- Bar Utilisasi -->
                            <div class="mb-3">
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span>Port: {{ odp.used_ports }} / {{ odp.total_ports }}</span>
                                    <span :class="odp.available_ports === 0 ? 'text-rose-600' : 'text-emerald-600'">
                                        Sisa: {{ odp.available_ports }} Port
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                    <div
                                        :class="[
                                            'h-2.5 rounded-full transition-all',
                                            odp.usage_percent >= 100 ? 'bg-rose-500' : (odp.usage_percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500')
                                        ]"
                                        :style="{ width: `${odp.usage_percent}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Smart Insight Box -->
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600 leading-snug mb-3">
                                {{ odp.recommendation }}
                            </div>
                        </div>

                        <!-- Card Footer Action -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span v-if="odp.can_register_customer" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                                ✓ Siap Pasang Baru
                            </span>
                            <span v-else class="text-xs font-bold text-rose-600 flex items-center gap-1">
                                ✕ Slot Penuh
                            </span>

                            <div class="flex items-center gap-1.5">
                                <a
                                    v-if="odp.latitude && odp.longitude"
                                    :href="`https://maps.google.com/?q=${odp.latitude},${odp.longitude}`"
                                    target="_blank"
                                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors"
                                >
                                    Maps
                                </a>
                                <Link
                                    :href="`/odps/${odp.id}`"
                                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition-colors"
                                >
                                    Detail
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TAB 2: MONITORING SLOT ODC ────────────────────────── -->
            <div v-if="currentTab === 'odc'">
                <!-- Table View -->
                <div v-if="viewMode === 'table'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="py-3.5 px-4">Nama ODC & Area</th>
                                    <th class="py-3.5 px-4">Induk OLT & PON Port</th>
                                    <th class="py-3.5 px-4 text-center">Total Slot ODC</th>
                                    <th class="py-3.5 px-4 text-center">ODP Terpasang</th>
                                    <th class="py-3.5 px-4 text-center">Sisa Slot ODP</th>
                                    <th class="py-3.5 px-4 text-center">Utilisasi ODC</th>
                                    <th class="py-3.5 px-4 text-center">Bisa Buka ODP Baru?</th>
                                    <th class="py-3.5 px-4">Rekomendasi Cerdas</th>
                                    <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr v-for="odc in odcs" :key="odc.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-black text-slate-900 text-sm flex items-center gap-1.5">
                                            <span>{{ odc.name }}</span>
                                            <span v-if="odc.type === 'Split'" class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-purple-50 text-purple-700 border border-purple-200">Split ODC</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                                            <span class="font-semibold text-indigo-600">{{ odc.area_name || '-' }}</span>
                                            <span v-if="odc.location">&bull; {{ odc.location }}</span>
                                        </div>
                                        <div v-if="odc.latitude && odc.longitude" class="flex items-center gap-1 mt-1 font-mono text-[10px]">
                                            <span class="bg-slate-100 px-1 py-0.2 rounded text-slate-600">Lat: {{ odc.latitude }}</span>
                                            <span class="bg-slate-100 px-1 py-0.2 rounded text-slate-600">Long: {{ odc.longitude }}</span>
                                            <a :href="`https://maps.google.com/?q=${odc.latitude},${odc.longitude}`" target="_blank" class="text-emerald-600 hover:underline font-bold">Maps ↗</a>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800">{{ odc.olt_name || '-' }}</div>
                                        <div class="text-[11px] text-slate-500">
                                            Port PON {{ odc.pon_port || '-' }}
                                            <span v-if="odc.splitter_ratio">&bull; Ratio {{ odc.splitter_ratio }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-slate-900">
                                        {{ odc.capacity }} Slot
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-indigo-600">
                                        {{ odc.odps_count }} ODP
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span :class="[
                                            'inline-flex px-2.5 py-1 rounded-xl font-mono font-black text-xs',
                                            odc.available_slots === 0 ? 'bg-rose-100 text-rose-700' : (odc.available_slots === 1 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')
                                        ]">
                                            {{ odc.available_slots }} Slot
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="font-bold text-slate-800 mb-1">{{ odc.usage_percent }}%</div>
                                        <div class="w-16 mx-auto bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div
                                                :class="[
                                                    'h-1.5 rounded-full transition-all',
                                                    odc.usage_percent >= 100 ? 'bg-rose-500' : (odc.usage_percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500')
                                                ]"
                                                :style="{ width: `${odc.usage_percent}%` }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span v-if="odc.can_open_odp" class="px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                            ✓ Bisa Buka ODP
                                        </span>
                                        <span v-else class="px-2.5 py-1 rounded-full text-[11px] font-black bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1">
                                            ✕ Slot ODC Penuh
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs">
                                        <p class="text-xs text-slate-700 leading-tight">{{ odc.recommendation }}</p>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Link
                                                :href="`/odcs/${odc.id}`"
                                                class="p-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 rounded-lg transition-colors"
                                                title="Lihat Detail ODC"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </Link>
                                            <!-- Tombol Rollout ODP jika ODC masih ada slot -->
                                            <Link
                                                v-if="odc.can_open_odp"
                                                :href="`/infrastructure/work-orders/create?device_type=odp&odc_id=${odc.id}&area_id=${odc.area_id}`"
                                                class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1"
                                                title="Buat Rollout ODP Baru dari ODC ini"
                                            >
                                                <span>⚡ Tarik ODP</span>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="odcs.length === 0">
                                    <td colspan="9" class="py-12 text-center text-slate-400">
                                        <div class="text-3xl mb-2">🔍</div>
                                        <p class="font-bold">Tidak ada data ODC yang sesuai filter</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grid Card View -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="odc in odcs"
                        :key="odc.id"
                        class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div>
                                    <h3 class="font-black text-slate-900 text-base leading-tight">{{ odc.name }}</h3>
                                    <span class="text-xs font-semibold text-indigo-600">{{ odc.area_name || '-' }}</span>
                                </div>
                                <span :class="[
                                    'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider',
                                    odc.status_category === 'empty' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : '',
                                    odc.status_category === 'available' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '',
                                    odc.status_category === 'critical' ? 'bg-amber-50 text-amber-800 border border-amber-200 animate-pulse' : '',
                                    odc.status_category === 'full' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ''
                                ]">
                                    {{ odc.status_label }}
                                </span>
                            </div>

                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs space-y-1.5 mb-3">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">OLT Induk:</span>
                                    <span class="font-bold text-slate-800">{{ odc.olt_name || '-' }} (PON {{ odc.pon_port || '-' }})</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Lokasi:</span>
                                    <span class="font-medium text-slate-700 truncate max-w-[180px]">{{ odc.location || '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Total Port Pelanggan di Bawah:</span>
                                    <span class="font-bold text-indigo-700">{{ odc.downstream_used_ports }} / {{ odc.downstream_total_ports }}</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span>Slot ODP: {{ odc.odps_count }} / {{ odc.capacity }}</span>
                                    <span :class="odc.available_slots === 0 ? 'text-rose-600' : 'text-emerald-600'">
                                        Sisa: {{ odc.available_slots }} Slot
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                    <div
                                        :class="[
                                            'h-2.5 rounded-full transition-all',
                                            odc.usage_percent >= 100 ? 'bg-rose-500' : (odc.usage_percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500')
                                        ]"
                                        :style="{ width: `${odc.usage_percent}%` }"
                                    ></div>
                                </div>
                            </div>

                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600 leading-snug mb-3">
                                {{ odc.recommendation }}
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span v-if="odc.can_open_odp" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                                ✓ Siap Buka ODP
                            </span>
                            <span v-else class="text-xs font-bold text-rose-600 flex items-center gap-1">
                                ✕ Slot Habis
                            </span>

                            <div class="flex items-center gap-1.5">
                                <Link
                                    v-if="odc.can_open_odp"
                                    :href="`/infrastructure/work-orders/create?device_type=odp&odc_id=${odc.id}&area_id=${odc.area_id}`"
                                    class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs"
                                >
                                    ⚡ Tarik ODP
                                </Link>
                                <Link
                                    :href="`/odcs/${odc.id}`"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors"
                                >
                                    Detail
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TAB 3: SMART EXPANSION MATRIX (PER AREA) ──────────── -->
            <div v-if="currentTab === 'expansion'" class="space-y-4">
                <div class="bg-indigo-50/70 border border-indigo-200/80 p-4 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🧠</span>
                        <div>
                            <h3 class="text-xs font-black uppercase text-indigo-900 tracking-wider">Matriks Analisis Kebutuhan Ekspansi Jaringan Per Area</h3>
                            <p class="text-xs text-indigo-700 mt-0.5">Sistem membandingkan ODP yang sudah penuh dengan sisa slot ODC induk untuk menentukan area yang mendesak butuh penambahan ODP atau Rollout ODC baru.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="matrix in expansion_matrix"
                        :key="matrix.area_id"
                        :class="[
                            'bg-white rounded-2xl border p-5 shadow-xs flex flex-col justify-between transition-all',
                            matrix.urgency === 'critical' ? 'border-rose-300 ring-2 ring-rose-100' : (matrix.urgency === 'high' ? 'border-amber-300 ring-2 ring-amber-100' : 'border-slate-200/80')
                        ]"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <h3 class="font-black text-slate-900 text-base">{{ matrix.area_name }}</h3>
                                <span :class="[
                                    'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider',
                                    matrix.urgency === 'critical' ? 'bg-rose-100 text-rose-800' : '',
                                    matrix.urgency === 'high' ? 'bg-amber-100 text-amber-800' : '',
                                    matrix.urgency === 'warning' ? 'bg-amber-50 text-amber-700' : '',
                                    matrix.urgency === 'optimal' ? 'bg-emerald-100 text-emerald-800' : '',
                                    matrix.urgency === 'normal' ? 'bg-slate-100 text-slate-700' : '',
                                    matrix.urgency === 'idle' ? 'bg-gray-100 text-gray-500' : ''
                                ]">
                                    {{ matrix.urgency === 'critical' ? '🚨 Krisis Kapasitas' : (matrix.urgency === 'high' ? '⚡ Butuh ODP Baru' : (matrix.urgency === 'optimal' ? '🎯 Target Sales' : 'Aman')) }}
                                </span>
                            </div>

                            <!-- Stat Grid Per Area -->
                            <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
                                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">ODC & Slot ODP</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ matrix.odc_count }} ODC</div>
                                    <div class="text-[11px] font-semibold text-violet-600 mt-0.5">Sisa {{ matrix.odc_available_slots }} Slot ODP</div>
                                </div>
                                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">ODP & Port Pelanggan</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ matrix.odp_count }} ODP</div>
                                    <div class="text-[11px] font-semibold text-emerald-600 mt-0.5">Sisa {{ matrix.odp_available_ports }} Port</div>
                                </div>
                            </div>

                            <!-- Status Warning Pills -->
                            <div class="space-y-1.5 mb-3 text-xs">
                                <div v-if="matrix.odp_full_count > 0" class="flex items-center justify-between bg-rose-50 text-rose-800 px-2.5 py-1 rounded-lg font-bold">
                                    <span>🔴 ODP 100% Penuh:</span>
                                    <span>{{ matrix.odp_full_count }} ODP</span>
                                </div>
                                <div v-if="matrix.odp_critical_count > 0" class="flex items-center justify-between bg-amber-50 text-amber-800 px-2.5 py-1 rounded-lg font-bold">
                                    <span>🟠 ODP Kritis (&le; 2 port):</span>
                                    <span>{{ matrix.odp_critical_count }} ODP</span>
                                </div>
                            </div>

                            <!-- Rekomendasi Pintar -->
                            <div :class="[
                                'p-3 rounded-xl text-xs font-medium leading-relaxed',
                                matrix.urgency === 'critical' ? 'bg-rose-50 text-rose-900 border border-rose-200' : 'bg-slate-50 text-slate-700 border border-slate-100'
                            ]">
                                {{ matrix.recommendation }}
                            </div>
                        </div>

                        <!-- Action Footer -->
                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <span class="text-[11px] text-slate-400 font-semibold">
                                Total Port: {{ matrix.odp_total_ports }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                <Link
                                    v-if="matrix.can_add_odp"
                                    :href="`/infrastructure/work-orders/create?device_type=odp&area_id=${matrix.area_id}`"
                                    class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs"
                                >
                                    ⚡ Buka ODP
                                </Link>
                                <Link
                                    v-if="matrix.needs_new_odc"
                                    :href="`/infrastructure/work-orders/create?device_type=odc&area_id=${matrix.area_id}`"
                                    class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs"
                                >
                                    🚨 Rollout ODC
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    tab: { type: String, default: 'odp' },
    kpi: { type: Object, required: true },
    odps: { type: Array, default: () => [] },
    odcs: { type: Array, default: () => [] },
    expansion_matrix: { type: Array, default: () => [] },
    smart_insights: { type: Array, default: () => [] },
    filter_options: { type: Object, default: () => ({ areas: [], olts: [], odcs: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const currentTab = ref(props.tab || 'odp');
const viewMode = ref('table'); // 'table' | 'grid'

const searchQuery = ref(props.filters.search || '');
const selectedArea = ref(props.filters.area_id || '');
const selectedOlt = ref(props.filters.olt_id || '');
const selectedStatus = ref(props.filters.status || 'all');
const selectedFeasibility = ref(props.filters.feasibility || 'all');

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        searchQuery.value = newFilters.search || '';
        selectedArea.value = newFilters.area_id || '';
        selectedOlt.value = newFilters.olt_id || '';
        selectedStatus.value = newFilters.status || 'all';
        selectedFeasibility.value = newFilters.feasibility || 'all';
        if (newFilters.tab) {
            currentTab.value = newFilters.tab;
        }
    }
}, { deep: true });

const selectedAreaName = computed(() => {
    if (!selectedArea.value) return '';
    const a = props.filter_options.areas?.find(item => String(item.id) === String(selectedArea.value));
    return a ? a.name : '';
});

const selectedOltName = computed(() => {
    if (!selectedOlt.value) return '';
    const o = props.filter_options.olts?.find(item => String(item.id) === String(selectedOlt.value));
    return o ? o.name : '';
});

function getStatusLabel(status) {
    const labels = {
        empty: 'Masih Kosong (0%)',
        available: 'Ada Slot',
        critical: 'Kritis (≤ 2 port)',
        full: 'Penuh (0 Slot)',
    };
    return labels[status] || status;
}

let searchTimeout = null;

const hasActiveFilter = computed(() => {
    return selectedStatus.value !== 'all' ||
        selectedFeasibility.value !== 'all' ||
        selectedArea.value !== '' ||
        selectedOlt.value !== '' ||
        searchQuery.value !== '';
});

function debouncedSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
}

function switchTab(newTab) {
    currentTab.value = newTab;
    applyFilters();
}

function setStatusFilter(status) {
    selectedStatus.value = status;
    applyFilters();
}

function applyInsightFilter(insight) {
    if (insight.action_tab) {
        currentTab.value = insight.action_tab;
    }
    if (insight.action_status) {
        selectedStatus.value = insight.action_status;
    }
    if (insight.action_feasibility) {
        selectedFeasibility.value = insight.action_feasibility;
    }
    applyFilters();
}

function applyFilters() {
    router.get(
        '/network/capacity-monitoring',
        {
            tab: currentTab.value,
            status: selectedStatus.value,
            feasibility: selectedFeasibility.value,
            area_id: selectedArea.value,
            olt_id: selectedOlt.value,
            search: searchQuery.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function resetFilters() {
    searchQuery.value = '';
    selectedArea.value = '';
    selectedOlt.value = '';
    selectedStatus.value = 'all';
    selectedFeasibility.value = 'all';
    applyFilters();
}

function handleExportCsv() {
    const params = new URLSearchParams({
        tab: currentTab.value,
        status: selectedStatus.value,
        feasibility: selectedFeasibility.value,
        area_id: selectedArea.value || '',
        olt_id: selectedOlt.value || '',
        search: searchQuery.value || '',
        export: 'csv',
    });
    window.location.href = `/network/capacity-monitoring?${params.toString()}`;
}
</script>
