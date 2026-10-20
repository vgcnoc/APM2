<template>
    <AppLayout title="Status Koneksi Pelanggan" subtitle="Pusat Kendali & Monitoring Real-Time Sesi Konektivitas Pelanggan (PPPoE & Hotspot)">
        <!-- Top Toolbar: NOC Status & Auto-Refresh Controls -->
        <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    RADIUS NOC SYNC
                </span>
                <span class="text-xs text-gray-400">|</span>
                <span class="text-xs text-gray-500 font-medium">
                    Kesehatan Jaringan: 
                    <strong :class="stats.health_rate >= 80 ? 'text-emerald-600' : (stats.health_rate >= 50 ? 'text-amber-600' : 'text-rose-600')">
                        {{ stats.health_rate }}%
                    </strong>
                </span>
            </div>

            <!-- Auto Refresh & Manual Reload -->
            <div class="flex items-center gap-2 self-start md:self-auto">
                <button
                    @click="toggleAutoRefresh"
                    :class="[
                        'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all duration-200 shadow-sm',
                        autoRefreshEnabled
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-700 hover:bg-emerald-100'
                            : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                    ]"
                    :title="autoRefreshEnabled ? 'Klik untuk mematikan auto refresh' : 'Klik untuk mengaktifkan auto refresh tiap 30 detik'"
                >
                    <span v-if="autoRefreshEnabled" class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span v-else class="w-2 h-2 rounded-full bg-gray-400"></span>
                    <span>{{ autoRefreshEnabled ? `Auto NOC (${countdown}s)` : 'Auto: Off' }}</span>
                </button>

                <button
                    id="btn-refresh-connections"
                    @click="refreshData(false)"
                    :disabled="refreshing"
                    class="btn-secondary bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium text-xs py-1.5 px-3 rounded-lg shadow-sm flex items-center gap-1.5 transition-colors disabled:opacity-50"
                >
                    <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>{{ refreshing ? 'Sinkron...' : 'Refresh' }}</span>
                </button>
            </div>
        </div>

        <!-- 5 Interactive Top KPI Metric Cards -->
        <div v-can="'customers_card_summary'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
            <!-- Card 1: Status Online -->
            <div
                @click="filterStatusTab('online')"
                :class="[
                    'group relative rounded-2xl p-4 border transition-all duration-200 cursor-pointer overflow-hidden',
                    filterStatus === 'online'
                        ? 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 border-emerald-400 ring-2 ring-emerald-300/60 shadow-md shadow-emerald-50'
                        : 'bg-white hover:bg-emerald-50/30 border-gray-200 hover:border-emerald-300 hover:shadow-sm'
                ]"
            >
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] uppercase tracking-wider font-bold text-emerald-700 flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Online
                    </span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100/70 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-emerald-700 tracking-tight">{{ stats.online }}</span>
                    <span class="text-[11px] font-semibold text-emerald-600/80">sesi</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[10px] text-emerald-600 font-medium">
                    <span>{{ stats.health_rate }}% Terhubung</span>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity font-bold">Filter &rarr;</span>
                </div>
            </div>

            <!-- Card 2: Status Offline -->
            <div
                @click="filterStatusTab('offline')"
                :class="[
                    'group relative rounded-2xl p-4 border transition-all duration-200 cursor-pointer overflow-hidden',
                    filterStatus === 'offline'
                        ? 'bg-gradient-to-br from-rose-50 to-rose-100/50 border-rose-400 ring-2 ring-rose-300/60 shadow-md shadow-rose-50'
                        : 'bg-white hover:bg-rose-50/30 border-gray-200 hover:border-rose-300 hover:shadow-sm'
                ]"
            >
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] uppercase tracking-wider font-bold text-rose-700 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Offline
                    </span>
                    <div class="w-8 h-8 rounded-xl bg-rose-100/70 text-rose-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-12.728 0m0 0l2.829-2.829m-2.829 2.829L3 21m2.828-5.657a5 5 0 010-7.072m0 0l2.829 2.829m0 0l4.242 4.243"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-rose-700 tracking-tight">{{ stats.offline }}</span>
                    <span class="text-[11px] font-semibold text-rose-600/80">pelanggan</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[10px] text-rose-600 font-medium">
                    <span>{{ stats.total > 0 ? (100 - stats.health_rate).toFixed(1) : 0 }}% Perlu Cek</span>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity font-bold">Filter &rarr;</span>
                </div>
            </div>

            <!-- Card 3: PPPoE Count -->
            <div
                @click="filterModePill('pppoe')"
                :class="[
                    'group relative rounded-2xl p-4 border transition-all duration-200 cursor-pointer overflow-hidden',
                    filterMode === 'pppoe'
                        ? 'bg-gradient-to-br from-indigo-50 to-indigo-100/50 border-indigo-400 ring-2 ring-indigo-300/60 shadow-md shadow-indigo-50'
                        : 'bg-white hover:bg-indigo-50/30 border-gray-200 hover:border-indigo-300 hover:shadow-sm'
                ]"
            >
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] uppercase tracking-wider font-bold text-indigo-700 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        PPPoE
                    </span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-100/70 text-indigo-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-indigo-700 tracking-tight">{{ stats.pppoe_count }}</span>
                    <span class="text-[11px] font-semibold text-indigo-600/80">pelanggan</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[10px] text-indigo-600 font-medium">
                    <span>Broadband Rumahan</span>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity font-bold">Filter &rarr;</span>
                </div>
            </div>

            <!-- Card 4: Hotspot Count -->
            <div
                @click="filterModePill('hotspot')"
                :class="[
                    'group relative rounded-2xl p-4 border transition-all duration-200 cursor-pointer overflow-hidden',
                    filterMode === 'hotspot'
                        ? 'bg-gradient-to-br from-amber-50 to-amber-100/50 border-amber-400 ring-2 ring-amber-300/60 shadow-md shadow-amber-50'
                        : 'bg-white hover:bg-amber-50/30 border-gray-200 hover:border-amber-300 hover:shadow-sm'
                ]"
            >
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] uppercase tracking-wider font-bold text-amber-700 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Hotspot
                    </span>
                    <div class="w-8 h-8 rounded-xl bg-amber-100/70 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-amber-700 tracking-tight">{{ stats.hotspot_count }}</span>
                    <span class="text-[11px] font-semibold text-amber-600/80">pelanggan</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[10px] text-amber-600 font-medium">
                    <span>Login Member</span>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity font-bold">Filter &rarr;</span>
                </div>
            </div>

            <!-- Card 5: Total Pelanggan (Reset) -->
            <div
                @click="resetAllFilters"
                :class="[
                    'group relative rounded-2xl p-4 border transition-all duration-200 cursor-pointer overflow-hidden col-span-2 sm:col-span-1',
                    filterStatus === 'all' && filterMode === 'all'
                        ? 'bg-gradient-to-br from-slate-50 to-blue-50 border-blue-400 ring-2 ring-blue-300/60 shadow-md shadow-blue-50'
                        : 'bg-white hover:bg-slate-50 border-gray-200 hover:border-blue-300 hover:shadow-sm'
                ]"
            >
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] uppercase tracking-wider font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                        Semua
                    </span>
                    <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-slate-800 tracking-tight">{{ stats.total }}</span>
                    <span class="text-[11px] font-semibold text-slate-500">terdaftar</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[10px] text-slate-600 font-medium">
                    <span>Non-Voucher</span>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity font-bold">Reset &rarr;</span>
                </div>
            </div>
        </div>

        <!-- Segmented Control Bar: Status Tabs & Mode Pills -->
        <div class="mb-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 bg-white p-2 rounded-2xl border border-gray-200/80 shadow-sm">
            <!-- Left: Status Segmented Tabs -->
            <div class="flex items-center gap-1 bg-gray-100/80 p-1 rounded-xl">
                <button
                    id="tab-status-all"
                    @click="filterStatusTab('all')"
                    :class="[
                        'px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all duration-200 flex items-center gap-1.5',
                        filterStatus === 'all'
                            ? 'bg-white text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-800 hover:bg-white/50'
                    ]"
                >
                    <span>Semua Sesi</span>
                    <span :class="['px-1.5 py-0.2 rounded-full text-[10px] font-black', filterStatus === 'all' ? 'bg-slate-100 text-slate-700' : 'bg-gray-200/60 text-gray-600']">
                        {{ stats.total }}
                    </span>
                </button>

                <button
                    id="tab-status-online"
                    @click="filterStatusTab('online')"
                    :class="[
                        'px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all duration-200 flex items-center gap-1.5',
                        filterStatus === 'online'
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'text-gray-600 hover:text-emerald-700 hover:bg-emerald-50/60'
                    ]"
                >
                    <span class="relative flex h-2 w-2">
                        <span v-if="filterStatus === 'online'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                        <span :class="['relative inline-flex rounded-full h-2 w-2', filterStatus === 'online' ? 'bg-white' : 'bg-emerald-500']"></span>
                    </span>
                    <span>Online</span>
                    <span :class="['px-1.5 py-0.2 rounded-full text-[10px] font-black', filterStatus === 'online' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-100 text-emerald-700']">
                        {{ stats.online }}
                    </span>
                </button>

                <button
                    id="tab-status-offline"
                    @click="filterStatusTab('offline')"
                    :class="[
                        'px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all duration-200 flex items-center gap-1.5',
                        filterStatus === 'offline'
                            ? 'bg-rose-600 text-white shadow-sm'
                            : 'text-gray-600 hover:text-rose-700 hover:bg-rose-50/60'
                    ]"
                >
                    <span :class="['inline-block w-2 h-2 rounded-full', filterStatus === 'offline' ? 'bg-white' : 'bg-rose-500']"></span>
                    <span>Offline</span>
                    <span :class="['px-1.5 py-0.2 rounded-full text-[10px] font-black', filterStatus === 'offline' ? 'bg-rose-700 text-rose-100' : 'bg-rose-100 text-rose-700']">
                        {{ stats.offline }}
                    </span>
                </button>
            </div>

            <!-- Right: Access Mode Sub-Filter Pills -->
            <div class="flex items-center gap-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mr-1 hidden sm:inline">Mode:</span>
                <button
                    @click="filterModePill('all')"
                    :class="[
                        'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all',
                        filterMode === 'all'
                            ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-xs'
                            : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100'
                    ]"
                >
                    Semua Mode
                </button>
                <button
                    @click="filterModePill('pppoe')"
                    :class="[
                        'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all flex items-center gap-1',
                        filterMode === 'pppoe'
                            ? 'bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-xs'
                            : 'text-gray-500 hover:text-indigo-700 hover:bg-indigo-50/50'
                    ]"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    PPPoE ({{ stats.pppoe_count }})
                </button>
                <button
                    @click="filterModePill('hotspot')"
                    :class="[
                        'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all flex items-center gap-1',
                        filterMode === 'hotspot'
                            ? 'bg-amber-50 text-amber-700 border border-amber-200 shadow-xs'
                            : 'text-gray-500 hover:text-amber-700 hover:bg-amber-50/50'
                    ]"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Hotspot ({{ stats.hotspot_count }})
                </button>
            </div>
        </div>

        <!-- DataTable with Rich Filters & Dynamic Rows -->
        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, kode, phone, user, IP, MAC, SN, ODP..."
            :searchRoute="currentSearchRoute"
            :filters="tableFilters"
        >
            <!-- Dropdown Filters Slot -->
            <template #filters>
                <!-- Area -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-xl pl-3 pr-2 py-1 shadow-xs w-full sm:w-auto sm:min-w-[160px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5 leading-none mt-0.5">Area</span>
                        <select v-model="filterArea" @change="applyFilters" class="form-select border-0 p-0 h-auto text-xs bg-transparent focus:ring-0 text-gray-700 font-semibold w-full pb-0.5">
                            <option value="">Semua Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Paket -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-xl pl-3 pr-2 py-1 shadow-xs w-full sm:w-auto sm:min-w-[170px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5 leading-none mt-0.5">Paket</span>
                        <select v-model="filterPackage" @change="applyFilters" class="form-select border-0 p-0 h-auto text-xs bg-transparent focus:ring-0 text-gray-700 font-semibold w-full pb-0.5">
                            <option value="">Semua Paket</option>
                            <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Reset Active Filter Button -->
                <button
                    v-if="hasActiveFilter"
                    @click="resetAllFilters"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-rose-200 transition-colors flex items-center gap-1.5"
                    title="Reset semua filter ke kondisi awal"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reset Filter
                </button>
            </template>

            <!-- Table Rows -->
            <template #row="{ row, index }">
                <!-- Index -->
                <td class="text-gray-400 text-xs text-center font-mono w-10">
                    {{ (customers.current_page - 1) * customers.per_page + index + 1 }}
                </td>

                <!-- Pelanggan Profile -->
                <td>
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <div :class="[
                                'w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black text-white shadow-sm ring-2',
                                row.is_online ? 'ring-emerald-400 shadow-emerald-100' : 'ring-rose-300 shadow-rose-100',
                                getAvatarColor(row.name)
                            ]">
                                {{ (row.name || 'P').charAt(0).toUpperCase() }}
                            </div>
                            <span :class="[
                                'absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white',
                                row.is_online ? 'bg-emerald-500 ring-1 ring-emerald-300' : 'bg-rose-500 ring-1 ring-rose-200'
                            ]"></span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <Link
                                    :href="`/customers/${row.id}`"
                                    class="font-bold text-sm text-gray-900 hover:text-blue-600 transition-colors truncate max-w-[200px]"
                                    :title="row.name"
                                >
                                    {{ row.name }}
                                </Link>
                                <span v-if="row.odp_name" class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200" :title="`ODP: ${row.odp_name}`">
                                    {{ row.odp_name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-500 font-mono">
                                <span class="font-semibold text-gray-700">{{ row.customer_code }}</span>
                                <span v-if="row.phone" class="text-gray-400">•</span>
                                <a
                                    v-if="row.phone"
                                    :href="`https://wa.me/${formatWaNumber(row.phone)}`"
                                    target="_blank"
                                    class="text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1"
                                    title="Hubungi via WhatsApp"
                                >
                                    <span>{{ row.phone }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- Status & Sesi Live -->
                <td>
                    <div class="flex flex-col gap-1 items-start">
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="row.is_online"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-300 uppercase tracking-wider"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                Online
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-300 uppercase tracking-wider"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Offline
                            </span>
                        </div>

                        <!-- IP Address / Disconnect Cause -->
                        <div v-if="row.is_online && row.ip_address" class="flex items-center gap-1">
                            <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-50 border border-slate-200 text-[10px] font-mono font-bold text-slate-800">
                                {{ row.ip_address }}
                            </span>
                            <button
                                @click="copyText(row.ip_address, 'IP Address')"
                                class="text-gray-400 hover:text-gray-600 p-0.5 transition-colors"
                                title="Salin IP"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                        <div v-else-if="!row.is_online" class="text-[10px]">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 rounded border text-[9px] font-semibold',
                                    getDisconnectCauseStyle(row.disconnect_cause)
                                ]"
                                :title="row.disconnect_cause || 'Tidak ada riwayat sesi'"
                            >
                                {{ humanizeDisconnectCause(row.disconnect_cause) }}
                            </span>
                        </div>
                    </div>
                </td>

                <!-- Paket & Area -->
                <td>
                    <div class="flex flex-col items-start gap-1">
                        <span v-if="row.package" class="inline-flex items-center gap-1 text-xs font-bold text-gray-800 whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                            {{ row.package }}
                        </span>
                        <span v-else class="text-xs text-gray-400 font-medium">-</span>

                        <span v-if="row.area" class="inline-flex px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[9px] font-bold uppercase tracking-wider">
                            {{ row.area }}
                        </span>
                        <span v-else class="text-[10px] text-gray-400">-</span>
                    </div>
                </td>

                <!-- Akun / Kredensial (Username & MAC) -->
                <td>
                    <div class="flex flex-col items-start gap-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span v-if="row.access_mode" :class="['inline-flex px-1.5 py-0.2 border rounded text-[9px] font-extrabold uppercase whitespace-nowrap', modeClass(row.access_mode)]">
                                {{ modeLabel(row.access_mode) }}
                            </span>
                            <div class="flex items-center gap-1">
                                <span class="font-mono text-xs font-bold text-gray-800">
                                    {{ row.username || '-' }}
                                </span>
                                <button
                                    v-if="row.username"
                                    @click="copyText(row.username, 'Username')"
                                    class="text-gray-400 hover:text-gray-600 p-0.5 transition-colors"
                                    title="Salin Username"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 text-[10px] text-gray-500 font-mono">
                            <span v-if="row.mac_address" title="MAC Address">{{ row.mac_address }}</span>
                            <span v-if="row.ont_sn" class="text-gray-400">|</span>
                            <span v-if="row.ont_sn" class="text-indigo-600 font-semibold" title="ONT Serial Number">SN: {{ row.ont_sn }}</span>
                        </div>
                    </div>
                </td>

                <!-- Metrik Uptime / Terakhir Terhubung -->
                <td>
                    <!-- Online: Uptime + Live Traffic -->
                    <div v-if="row.is_online" class="flex flex-col gap-0.5">
                        <div class="flex items-center gap-1 text-xs font-bold text-emerald-700 whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Up: {{ formatUptime(row.session_time) }}
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-mono font-medium whitespace-nowrap">
                            <span class="text-blue-600" title="Download">↓ {{ formatBytes(row.download) }}</span>
                            <span class="text-gray-300">/</span>
                            <span class="text-amber-600" title="Upload">↑ {{ formatBytes(row.upload) }}</span>
                        </div>
                        <span class="text-[9px] text-gray-400">Login: {{ formatDate(row.start_time) }}</span>
                    </div>

                    <!-- Offline: Last Logout + Duration Ago -->
                    <div v-else class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold text-gray-800 whitespace-nowrap">
                            {{ formatDate(row.last_logout) || 'Belum Login' }}
                        </span>
                        <span v-if="row.last_logout" class="text-[10px] text-gray-500 font-medium">
                            {{ formatTimeAgo(row.last_logout) }}
                        </span>
                        <span v-else class="text-[10px] text-gray-400 italic">
                            Belum ada riwayat sesi
                        </span>
                    </div>
                </td>

                <!-- Kolom Aksi -->
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        <!-- Kick Sesi Button (Hanya jika Online) -->
                        <button
                            v-if="row.is_online"
                            v-can="'customers_active_suspend'"
                            :id="`btn-kick-${row.id}`"
                            @click="kickSession(row)"
                            :disabled="kicking === row.username"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200 transition-all shadow-xs disabled:opacity-50"
                            title="Kick / Putuskan sesi online ini dari Mikrotik"
                        >
                            <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': kicking === row.username }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>{{ kicking === row.username ? 'Kicking...' : 'KICK' }}</span>
                        </button>

                        <!-- Link Detail Pelanggan -->
                        <Link
                            :href="`/customers/${row.id}`"
                            class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-100"
                            title="Buka Profil Pelanggan"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                    </div>
                </td>
            </template>
        </DataTable>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    customers: { type: Object, required: true },
    areas: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            online: 0,
            offline: 0,
            health_rate: 0,
            pppoe_count: 0,
            hotspot_count: 0,
        })
    },
    totalOnline: { type: Number, default: 0 },
    totalOffline: { type: Number, default: 0 },
});

// State Filters
const filterArea = ref(props.filters?.area_id || '');
const filterPackage = ref(props.filters?.package_id || '');
const filterStatus = ref(props.filters?.status || 'all');
const filterMode = ref(props.filters?.mode || 'all');

// Loading & Operation States
const refreshing = ref(false);
const kicking = ref(null);

// NOC Auto-Refresh Engine (30s)
const autoRefreshEnabled = ref(true);
const countdown = ref(30);
let timerInterval = null;

function startAutoRefreshTimer() {
    stopAutoRefreshTimer();
    countdown.value = 30;
    timerInterval = setInterval(() => {
        if (!autoRefreshEnabled.value || refreshing.value || kicking.value) {
            return;
        }
        if (countdown.value > 1) {
            countdown.value--;
        } else {
            countdown.value = 30;
            refreshData(true);
        }
    }, 1000);
}

function stopAutoRefreshTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

function toggleAutoRefresh() {
    autoRefreshEnabled.value = !autoRefreshEnabled.value;
    if (autoRefreshEnabled.value) {
        startAutoRefreshTimer();
    }
}

onMounted(() => {
    startAutoRefreshTimer();
});

onUnmounted(() => {
    stopAutoRefreshTimer();
});

// Dynamic Columns Definition
const columns = computed(() => {
    let modeLabel = 'AKUN & MODE';
    if (filterMode.value === 'pppoe') modeLabel = 'AKUN PPPoE';
    else if (filterMode.value === 'hotspot') modeLabel = 'AKUN HOTSPOT';

    return [
        { key: 'index', label: '#', class: 'w-10 text-center' },
        { key: 'name', label: 'PELANGGAN' },
        { key: 'status_session', label: 'STATUS & IP' },
        { key: 'package_area', label: 'PAKET & AREA' },
        { key: 'credentials', label: modeLabel },
        { key: 'uptime_activity', label: 'METRIK / SESI TERAKHIR' },
    ];
});

// Search Route
const currentSearchRoute = computed(() => {
    return '/customers/online';
});

// Table Filters passed to DataTable search query
const tableFilters = computed(() => {
    return {
        area_id: filterArea.value || undefined,
        package_id: filterPackage.value || undefined,
        status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
        mode: filterMode.value !== 'all' ? filterMode.value : undefined,
    };
});

const hasActiveFilter = computed(() => {
    return Boolean(
        filterArea.value ||
        filterPackage.value ||
        (filterStatus.value && filterStatus.value !== 'all') ||
        (filterMode.value && filterMode.value !== 'all') ||
        props.filters?.search
    );
});

// Filter Handlers
function applyFilters() {
    router.get('/customers/online', {
        search: props.filters?.search || undefined,
        area_id: filterArea.value || undefined,
        package_id: filterPackage.value || undefined,
        status: filterStatus.value || 'all',
        mode: filterMode.value || 'all',
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function filterStatusTab(status) {
    filterStatus.value = status;
    applyFilters();
}

function filterModePill(mode) {
    filterMode.value = mode;
    applyFilters();
}

function resetAllFilters() {
    filterArea.value = '';
    filterPackage.value = '';
    filterStatus.value = 'all';
    filterMode.value = 'all';
    router.get('/customers/online', {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Refresh Data
function refreshData(silent = false) {
    if (!silent) refreshing.value = true;
    router.reload({
        only: ['customers', 'stats', 'totalOnline', 'totalOffline'],
        onFinish: () => {
            refreshing.value = false;
            countdown.value = 30;
        },
    });
}

// Kick Sesi Session
function kickSession(row) {
    if (!row.username) return;

    Swal.fire({
        title: 'Kick Sesi Online?',
        html: `Apakah Anda yakin ingin memutuskan sesi koneksi online pelanggan <b>${row.name}</b> (${row.username}) di Mikrotik?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Kick Sesi!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            kicking.value = row.username;
            router.post('/radius/online-users/disconnect', { username: row.username }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: `Sesi online untuk ${row.name} (${row.username}) berhasil diputuskan.`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                    refreshData(true);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal memutuskan sesi online. Periksa koneksi RADIUS / Mikrotik.',
                    });
                },
                onFinish: () => {
                    kicking.value = null;
                },
            });
        }
    });
}

// Clipboard Copy
function copyText(text, label = 'Teks') {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            icon: 'success',
            title: `${label} Disalin`,
            text: text,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
        });
    });
}

// Helpers & Formatters
function getAvatarColor(name) {
    if (!name) return 'bg-gray-500';
    const colors = [
        'bg-indigo-600',
        'bg-blue-600',
        'bg-emerald-600',
        'bg-violet-600',
        'bg-cyan-600',
        'bg-teal-600',
        'bg-amber-600',
        'bg-rose-600',
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
}

function formatWaNumber(phone) {
    if (!phone) return '';
    let p = String(phone).replace(/\D/g, '');
    if (p.startsWith('0')) {
        p = '62' + p.slice(1);
    }
    return p;
}

const modeLabels = {
    pppoe: 'PPPoE',
    hotspot: 'Hotspot',
    static_ip: 'Static IP',
    voucher: 'Voucher',
    lainnya: 'Lainnya',
};

function modeLabel(mode) {
    return modeLabels[mode] || mode;
}

function modeClass(mode) {
    switch (mode) {
        case 'pppoe': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'hotspot': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'static_ip': return 'bg-sky-50 text-sky-700 border-sky-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}

function formatBytes(bytes) {
    if (!bytes || isNaN(bytes) || bytes <= 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatUptime(seconds) {
    if (!seconds || isNaN(seconds) || seconds <= 0) return '0s';
    const d = Math.floor(seconds / (3600 * 24));
    const h = Math.floor((seconds % (3600 * 24)) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);

    let parts = [];
    if (d > 0) parts.push(`${d}d`);
    if (h > 0) parts.push(`${h}h`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 || parts.length === 0) parts.push(`${s}s`);

    return parts.join(' ');
}

function formatDate(isoString) {
    if (!isoString) return '-';
    try {
        const date = new Date(isoString);
        if (isNaN(date.getTime())) return isoString;
        return date.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (e) {
        return isoString;
    }
}

function formatTimeAgo(isoString) {
    if (!isoString) return '';
    try {
        const date = new Date(isoString);
        if (isNaN(date.getTime())) return '';
        const now = new Date();
        const diffSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

        if (diffSeconds < 60) return 'Baru saja';
        if (diffSeconds < 3600) return `${Math.floor(diffSeconds / 60)} menit lalu`;
        if (diffSeconds < 86400) return `${Math.floor(diffSeconds / 3600)} jam lalu`;
        if (diffSeconds < 604800) return `${Math.floor(diffSeconds / 86400)} hari lalu`;
        return `${Math.floor(diffSeconds / 604800)} minggu lalu`;
    } catch (e) {
        return '';
    }
}

function humanizeDisconnectCause(cause) {
    if (!cause) return 'Belum Login';
    const c = String(cause).toLowerCase();
    if (c.includes('lost-carrier') || c.includes('lost carrier')) return 'Kabel Lepas / Redaman';
    if (c.includes('user-request') || c.includes('user request')) return 'User / Router Logout';
    if (c.includes('nas-reboot') || c.includes('nas reboot')) return 'Router Reboot';
    if (c.includes('admin-reset') || c.includes('admin reset')) return 'Kick oleh Admin';
    if (c.includes('idle-timeout') || c.includes('idle timeout')) return 'Idle Timeout';
    if (c.includes('session-timeout') || c.includes('session timeout')) return 'Sesi Habis';
    if (c.includes('port-error')) return 'Gangguan Port';
    return cause;
}

function getDisconnectCauseStyle(cause) {
    if (!cause) return 'bg-gray-50 text-gray-500 border-gray-200';
    const c = String(cause).toLowerCase();
    if (c.includes('lost-carrier')) return 'bg-rose-50 text-rose-700 border-rose-200';
    if (c.includes('user-request')) return 'bg-slate-50 text-slate-700 border-slate-200';
    if (c.includes('nas-reboot')) return 'bg-purple-50 text-purple-700 border-purple-200';
    if (c.includes('admin-reset')) return 'bg-amber-50 text-amber-700 border-amber-200';
    return 'bg-gray-50 text-gray-700 border-gray-200';
}
</script>
