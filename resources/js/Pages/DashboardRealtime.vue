<template>
    <AppLayout title="Dashboard Realtime" subtitle="Monitoring live koneksi pelanggan, voucher, billing & radius">
        <!-- ═══ 1. SMART OUTAGE DETECTION BANNER (Fitur Cerdas Gangguan Massal) ═══ -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform -translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform -translate-y-4 opacity-0"
        >
            <div
                v-if="stats.smart_outage?.detected"
                class="mb-6 rounded-2xl bg-gradient-to-r from-rose-500 via-red-600 to-amber-600 p-0.5 shadow-lg shadow-rose-500/20"
            >
                <div class="rounded-[14px] bg-white dark:bg-slate-900 p-4 md:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl shrink-0 animate-bounce">
                            ⚠️
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500 text-white shadow-2xs">
                                    Deteksi Cerdas Gangguan
                                </span>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white">
                                    Potensi Gangguan Jalur / ODP Terdeteksi!
                                </h3>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                                {{ stats.smart_outage?.summary }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            @click="activeModal = 'outage'"
                            type="button"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-rose-500/30 active:scale-95"
                        >
                            <span>🔍 Investigasi ODP</span>
                        </button>
                        <button
                            @click="copyOutageReport"
                            type="button"
                            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                            title="Salin Rincian Gangguan"
                        >
                            <span>📋 Salin</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ═══ 2. COMPREHENSIVE FILTER & LIVE CONTROL BAR ═══ -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-4 md:p-5 mb-6 transition-all">
            <!-- Top Row: Title, Area Context & Auto Refresh Controller -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800/80">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-blue-600 text-white flex items-center justify-center font-black shadow-md shadow-purple-500/20 text-lg shrink-0">
                        ⚡
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-black text-slate-900 dark:text-white">Live Realtime Operations Center</h2>
                            <span
                                v-if="activeAreaId !== 'all'"
                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                📍 {{ currentAreaName }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200"
                            >
                                🌐 Seluruh Wilayah (Nasional)
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5 truncate">
                            Monitoring terintegrasi: Trafik Internet, PPPoE/Hotspot UP-DOWN, Transaksi Saldo, & Penjualan Voucher.
                        </p>
                    </div>
                </div>

                <!-- Right: Auto-Refresh Controls with Timer Circle & Refresh Button -->
                <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
                    <!-- Interval Selector -->
                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl text-xs">
                        <span class="text-[11px] font-bold text-slate-400 px-2 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full" :class="pollInterval > 0 ? 'bg-emerald-500 animate-ping' : 'bg-slate-400'"></span>
                            Auto:
                        </span>
                        <button
                            v-for="opt in refreshOptions"
                            :key="opt.value"
                            @click="setPollInterval(opt.value)"
                            type="button"
                            class="px-2.5 py-1 rounded-lg font-bold text-[11px] transition-all"
                            :class="pollInterval === opt.value
                                ? 'bg-white dark:bg-slate-700 text-purple-600 dark:text-purple-300 shadow-2xs'
                                : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <!-- Countdown Progress Ring or Counter -->
                    <div
                        v-if="pollInterval > 0"
                        class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900/40 text-purple-700 dark:text-purple-300 text-xs font-mono font-bold"
                        title="Waktu mundur refresh berikutnya"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-pulse"></span>
                        <span>{{ countdown }}s</span>
                    </div>

                    <!-- Manual Refresh Button -->
                    <button
                        @click="triggerManualRefresh"
                        :disabled="isRefreshing"
                        type="button"
                        class="px-3.5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 active:scale-95 shadow-md shadow-purple-500/20 disabled:opacity-70"
                    >
                        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ isRefreshing ? 'Memuat...' : 'Refresh' }}</span>
                    </button>
                </div>
            </div>

            <!-- Bottom Row: Filter Controls (Area, NAS, Layanan, Status, Periode, Live Search) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 pt-4">
                <!-- 1. Filter Area -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">📍 Wilayah / Area</label>
                    <select
                        v-model="activeAreaId"
                        @change="onFilterChange"
                        class="w-full text-xs font-bold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:ring-purple-500 focus:border-purple-500 cursor-pointer"
                    >
                        <option value="all">Semua Wilayah</option>
                        <option v-for="area in availableAreas" :key="area.id" :value="area.id">
                            {{ area.name }}
                        </option>
                    </select>
                </div>

                <!-- 2. Filter Router NAS -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">📡 Router NAS</label>
                    <select
                        v-model="activeNasId"
                        @change="onFilterChange"
                        class="w-full text-xs font-bold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:ring-purple-500 focus:border-purple-500 cursor-pointer"
                    >
                        <option value="all">Semua Router NAS</option>
                        <option v-for="nas in availableNas" :key="nas.id" :value="nas.id">
                            {{ nas.shortname }} ({{ nas.nasname }})
                        </option>
                    </select>
                </div>

                <!-- 3. Filter Layanan (Service) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">⚙️ Layanan</label>
                    <select
                        v-model="activeService"
                        @change="onFilterChange"
                        class="w-full text-xs font-bold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:ring-purple-500 focus:border-purple-500 cursor-pointer"
                    >
                        <option value="all">Semua Layanan</option>
                        <option value="pppoe">Hanya PPPoE</option>
                        <option value="hotspot">Hanya Hotspot</option>
                    </select>
                </div>

                <!-- 4. Filter Status (UP / DOWN / Isolir / Expired) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">🟢 Status Koneksi</label>
                    <select
                        v-model="activeStatus"
                        @change="onFilterChange"
                        class="w-full text-xs font-bold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:ring-purple-500 focus:border-purple-500 cursor-pointer"
                    >
                        <option value="all">Semua Status</option>
                        <option value="up">Hanya ONLINE (UP)</option>
                        <option value="down">Hanya OFFLINE (DOWN)</option>
                        <option value="isolir">Hanya ISOLIR</option>
                        <option value="expired">Hanya EXPIRED</option>
                    </select>
                </div>

                <!-- 5. Filter Periode -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">📅 Periode</label>
                    <select
                        v-model="activePeriod"
                        @change="onFilterChange"
                        class="w-full text-xs font-bold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-8 focus:ring-purple-500 focus:border-purple-500 cursor-pointer"
                    >
                        <option value="today">Hari Ini</option>
                        <option value="24h">24 Jam Terakhir</option>
                        <option value="7d">7 Hari Terakhir</option>
                        <option value="30d">Bulan Ini</option>
                    </select>
                </div>

                <!-- 6. Live Search Input -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">🔍 Pencarian Cepat</label>
                    <div class="relative">
                        <input
                            v-model="searchKeyword"
                            type="text"
                            placeholder="Cari Nama/IP/ODP/HP..."
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-7 focus:ring-purple-500 focus:border-purple-500"
                        />
                        <button
                            v-if="searchKeyword"
                            @click="searchKeyword = ''"
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ 3. TOP SECTION: REALTIME CARD + TRAFIK INTERNET BANDWIDTH LIVE ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Left: RealtimeWidget Core (6 Cols) -->
            <div class="lg:col-span-6">
                <RealtimeWidget
                    ref="realtimeWidgetRef"
                    :stats="stats"
                    :area-id="activeAreaId"
                    :auto-poll="false"
                />
            </div>

            <!-- Right: TRAFIK INTERNET & BANDWIDTH MONITOR (6 Cols) -->
            <div class="lg:col-span-6 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-5 md:p-6 flex flex-col justify-between relative overflow-hidden group">
                <!-- Top Header: Title & Badges -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center font-black text-sm shadow-sm shadow-emerald-500/20">
                                📶
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                    <span>Trafik Jaringan Internet Live</span>
                                    <span
                                        v-if="trafficStats.source_type === 'routeros'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 animate-pulse"
                                    >
                                        ● LIVE MIKROTIK
                                    </span>
                                    <span
                                        v-else-if="trafficStats.source_type === 'radius'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200/60 animate-pulse"
                                    >
                                        ● LIVE RADIUS
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200/60"
                                    >
                                        ● STANDBY (0 Mbps)
                                    </span>
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">Throughput live bandwidth download (Rx) & upload (Tx)</p>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-xs font-mono font-black text-slate-900 dark:text-white">
                                {{ trafficStats.total_mbps }} <span class="text-[10px] text-slate-400 font-normal">Mbps</span>
                            </span>
                            <p class="text-[10px] text-slate-400">Total Throughput</p>
                        </div>
                    </div>

                    <!-- Bandwidth KPI Cards (Rx / Tx / Peak / Total GB) -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-4">
                        <!-- 1. Download (Rx) -->
                        <div class="p-3 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40">
                            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 text-xs font-bold mb-1">
                                <span>↓ Download (Rx)</span>
                                <span class="w-2 h-2 rounded-full" :class="trafficStats.download_mbps > 0 ? 'bg-emerald-500 animate-ping' : 'bg-slate-400'"></span>
                            </div>
                            <p class="text-lg font-black text-emerald-700 dark:text-emerald-300 font-mono">
                                {{ trafficStats.download_mbps }} <span class="text-[10px] font-bold">Mbps</span>
                            </p>
                            <p class="text-[10px] text-emerald-600/80 mt-0.5">{{ trafficStats.download_gb_today }} GB Hari Ini</p>
                        </div>

                        <!-- 2. Upload (Tx) -->
                        <div class="p-3 rounded-xl bg-sky-50/70 dark:bg-sky-950/30 border border-sky-100 dark:border-sky-900/40">
                            <div class="flex items-center justify-between text-sky-600 dark:text-sky-400 text-xs font-bold mb-1">
                                <span>↑ Upload (Tx)</span>
                                <span class="w-2 h-2 rounded-full" :class="trafficStats.upload_mbps > 0 ? 'bg-sky-500' : 'bg-slate-400'"></span>
                            </div>
                            <p class="text-lg font-black text-sky-700 dark:text-sky-300 font-mono">
                                {{ trafficStats.upload_mbps }} <span class="text-[10px] font-bold">Mbps</span>
                            </p>
                            <p class="text-[10px] text-sky-600/80 mt-0.5">{{ trafficStats.upload_gb_today }} GB Hari Ini</p>
                        </div>

                        <!-- 3. Peak Bandwidth -->
                        <div class="p-3 rounded-xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40">
                            <p class="text-xs font-bold text-purple-600 dark:text-purple-400 mb-1">⚡ Puncak Trafik</p>
                            <p class="text-lg font-black text-purple-700 dark:text-purple-300 font-mono">
                                {{ trafficStats.peak_mbps_today }} <span class="text-[10px] font-bold">Mbps</span>
                            </p>
                            <p class="text-[10px] text-purple-600/80 mt-0.5">Puncak Terukur Hari Ini</p>
                        </div>

                        <!-- 4. Total Kuota GB Hari Ini -->
                        <div class="p-3 rounded-xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40">
                            <p class="text-xs font-bold text-amber-600 dark:text-amber-400 mb-1">📦 Total Transfer</p>
                            <p class="text-lg font-black text-amber-700 dark:text-amber-300 font-mono">
                                {{ trafficStats.total_gb_today }} <span class="text-[10px] font-bold">GB</span>
                            </p>
                            <p class="text-[10px] text-amber-600/80 mt-0.5">Akumulasi Kuota Hari Ini</p>
                        </div>
                    </div>

                    <!-- Dynamic Live SVG Area Chart -->
                    <div class="relative bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 mb-2">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                    <span class="w-2.5 h-1 rounded-full bg-emerald-500"></span> Rx (Download)
                                </span>
                                <span class="flex items-center gap-1 text-sky-600 dark:text-sky-400">
                                    <span class="w-2.5 h-1 rounded-full bg-sky-500"></span> Tx (Upload)
                                </span>
                            </div>
                            <span>12 Titik Interval Terakhir</span>
                        </div>

                        <!-- SVG Curve -->
                        <div class="h-28 w-full relative">
                            <svg class="w-full h-full overflow-visible" viewBox="0 0 500 100" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="rxGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.35" />
                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0.0" />
                                    </linearGradient>
                                    <linearGradient id="txGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#0284c7" stop-opacity="0.25" />
                                        <stop offset="100%" stop-color="#0284c7" stop-opacity="0.0" />
                                    </linearGradient>
                                </defs>

                                <!-- Grid Lines -->
                                <line x1="0" y1="25" x2="500" y2="25" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-dasharray="3,3" />
                                <line x1="0" y1="50" x2="500" y2="50" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-dasharray="3,3" />
                                <line x1="0" y1="75" x2="500" y2="75" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-dasharray="3,3" />

                                <!-- RX Area & Line -->
                                <polygon :points="rxAreaPoints" fill="url(#rxGrad)" />
                                <polyline :points="rxLinePoints" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                                <!-- TX Area & Line -->
                                <polygon :points="txAreaPoints" fill="url(#txGrad)" />
                                <polyline :points="txLinePoints" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />

                                <!-- Current Point Indicator -->
                                <circle :cx="latestX" :cy="latestRxY" r="4" fill="#10b981" stroke="#ffffff" stroke-width="2" class="animate-pulse" />
                            </svg>
                        </div>

                        <!-- Time Axis Labels -->
                        <div class="flex items-center justify-between text-[9px] font-mono text-slate-400 mt-1">
                            <span v-for="(item, idx) in trafficHistory" :key="idx" :class="{ 'hidden sm:inline': idx % 2 !== 0 }">
                                {{ item.time }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer Status -->
                <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <span>Diperbarui: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ stats.last_updated_full || lastUpdated }}</strong></span>
                    <span
                        class="inline-flex items-center gap-1.5 text-[11px] font-bold"
                        :class="trafficStats.source_type === 'routeros' ? 'text-emerald-600 dark:text-emerald-400' : (trafficStats.source_type === 'radius' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400')"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="trafficStats.source_type !== 'standby' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                        {{ trafficStats.source_label || 'Standby (0 Mbps)' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ═══ 4. MIDDLE SECTION: PPPoE, HOTSPOT, ISI SALDO, VOUCHER TERJUAL ═══ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- 1. PPPoE Online & Down Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-950 text-sky-600 flex items-center justify-center text-lg">
                                🏠
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Layanan PPPoE</h4>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white">Pelanggan Rumah</h3>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-sky-50 dark:bg-sky-950 text-sky-600 border border-sky-200/60">
                            {{ pppoeStats.uptime_rate }}% UP
                        </span>
                    </div>

                    <!-- 3 Stat Blocks (UP, DOWN, ISOLIR) -->
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40 text-center">
                            <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">UP</p>
                            <p class="text-base font-black text-emerald-700 dark:text-emerald-300 font-mono">{{ pppoeStats.online }}</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/40 text-center">
                            <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400">DOWN</p>
                            <p class="text-base font-black text-rose-700 dark:text-rose-300 font-mono">{{ pppoeStats.down }}</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/40 text-center">
                            <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400">ISOLIR</p>
                            <p class="text-base font-black text-amber-700 dark:text-amber-300 font-mono">{{ pppoeStats.isolir }}</p>
                        </div>
                    </div>

                    <!-- Mini sample list of down PPPoE -->
                    <div class="space-y-1.5 mb-3">
                        <div
                            v-for="item in pppoeDownSample"
                            :key="item.id"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between text-[11px]"
                        >
                            <div class="min-w-0 pr-2">
                                <p class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ item.name }}</p>
                                <p class="text-[10px] text-rose-500 font-medium truncate">{{ item.disconnect_cause }}</p>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400 shrink-0">{{ item.time_ago }}</span>
                        </div>
                        <p v-if="!pppoeDownSample.length" class="text-[11px] text-center text-emerald-600 py-1 font-medium">
                            ✓ Seluruh pelanggan PPPoE saat ini online
                        </p>
                    </div>
                </div>

                <button
                    @click="activeModal = 'pppoe_all'"
                    type="button"
                    class="w-full py-2 bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1"
                >
                    <span>Rincian PPPoE ({{ pppoeStats.total }})</span>
                    <span>→</span>
                </button>
            </div>

            <!-- 2. Hotspot Voucher Online & Down Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950 text-purple-600 flex items-center justify-center text-lg">
                                🎟️
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Layanan Hotspot</h4>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white">Voucher Aktif</h3>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-50 dark:bg-purple-950 text-purple-600 border border-purple-200/60">
                            {{ hotspotStats.uptime_rate }}% UP
                        </span>
                    </div>

                    <!-- 3 Stat Blocks (UP, DOWN, STOK) -->
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40 text-center">
                            <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">UP</p>
                            <p class="text-base font-black text-emerald-700 dark:text-emerald-300 font-mono">{{ hotspotStats.online }}</p>
                        </div>
                        <div
                            @click="activeModal = 'expired_vouchers'"
                            class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/40 text-center cursor-pointer hover:bg-rose-100 dark:hover:bg-rose-900/60 transition group"
                            title="Klik untuk kelola & hapus voucher expired"
                        >
                            <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400 flex items-center justify-center gap-1">
                                <span>EXPIRED</span>
                                <span class="text-[9px] opacity-75 group-hover:scale-110">🗑️</span>
                            </p>
                            <p class="text-base font-black text-rose-700 dark:text-rose-300 font-mono">{{ stats.expired_voucher_count ?? hotspotStats.expired ?? hotspotStats.down }}</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/40 text-center">
                            <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">STOK</p>
                            <p class="text-base font-black text-indigo-700 dark:text-indigo-300 font-mono">{{ hotspotStats.available }}</p>
                        </div>
                    </div>

                    <!-- Mini sample list of recent vouchers -->
                    <div class="space-y-1.5 mb-3">
                        <div
                            v-for="item in hotspotSample"
                            :key="item.id"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between text-[11px]"
                        >
                            <div class="min-w-0 pr-2">
                                <p class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ item.code }}</p>
                                <p class="text-[10px] text-purple-600 font-medium truncate">{{ item.package_name }}</p>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400 shrink-0">{{ item.time_ago }}</span>
                        </div>
                        <p v-if="!hotspotSample.length" class="text-[11px] text-center text-slate-400 py-1">
                            Belum ada riwayat sesi voucher hari ini
                        </p>
                    </div>
                </div>

                <button
                    @click="activeModal = 'hotspot_all'"
                    type="button"
                    class="w-full py-2 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1"
                >
                    <span>Rincian Sesi Hotspot</span>
                    <span>→</span>
                </button>
            </div>

            <!-- 3. Isi Saldo Reseller Hari Ini Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center text-lg">
                                💰
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Top Up Agen</h4>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white">Isi Saldo Hari Ini</h3>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950 text-emerald-600 border border-emerald-200/60">
                            {{ topupStats.today_count }} Top-up
                        </span>
                    </div>

                    <!-- Big Stat Topup -->
                    <div class="p-3 rounded-xl bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-transparent border border-emerald-500/20 mb-3">
                        <p class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300">Total Top-up Hari Ini</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5 font-mono">
                            {{ topupStats.today_amount_formatted }}
                        </p>
                        <p class="text-[10px] text-slate-400 mt-1">Akumulasi Sepanjang Waktu: {{ topupStats.total_all_formatted }}</p>
                    </div>

                    <!-- Recent top-ups feed -->
                    <div class="space-y-1.5 mb-3">
                        <div
                            v-for="item in topupStats.recent_topups?.slice(0, 2)"
                            :key="item.id"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between text-[11px]"
                        >
                            <div class="min-w-0 pr-2">
                                <p class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ item.reseller_name }}</p>
                                <p class="text-[10px] text-slate-400 truncate">{{ item.full_time }}</p>
                            </div>
                            <span class="text-xs font-bold font-mono text-emerald-600 shrink-0">{{ item.amount_formatted }}</span>
                        </div>
                        <p v-if="!topupStats.recent_topups?.length" class="text-[11px] text-center text-slate-400 py-1">
                            Belum ada transaksi top-up hari ini
                        </p>
                    </div>
                </div>

                <button
                    @click="activeModal = 'topup_details'"
                    type="button"
                    class="w-full py-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1"
                >
                    <span>Rincian Isi Saldo</span>
                    <span>→</span>
                </button>
            </div>

            <!-- 4. Voucher Terjual Hari Ini Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-600 flex items-center justify-center text-lg">
                                📊
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Omset Penjualan</h4>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white">Voucher Terjual</h3>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950 text-amber-600 border border-amber-200/60">
                            {{ voucherSalesStats.today_count }} Lembar
                        </span>
                    </div>

                    <!-- Big Stat Voucher Sales -->
                    <div class="p-3 rounded-xl bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-transparent border border-amber-500/20 mb-3">
                        <p class="text-[11px] font-bold text-amber-700 dark:text-amber-300">Pendapatan Voucher Hari Ini</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5 font-mono">
                            {{ voucherSalesStats.today_amount_formatted }}
                        </p>
                        <p class="text-[10px] text-slate-400 mt-1">{{ voucherSalesStats.today_count }} lembar voucher diaktifkan</p>
                    </div>

                    <!-- Breakdown Pills per profile -->
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <span
                            v-for="(bk, idx) in voucherSalesStats.breakdown"
                            :key="idx"
                            class="px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center gap-1"
                        >
                            <span>{{ bk.profile_name }}:</span>
                            <span class="font-mono text-purple-600 dark:text-purple-400">{{ bk.count }}x</span>
                        </span>
                        <p v-if="!voucherSalesStats.breakdown?.length" class="text-[11px] text-center text-slate-400 py-1 w-full">
                            Belum ada voucher terjual hari ini
                        </p>
                    </div>
                </div>

                <button
                    @click="activeModal = 'voucher_details'"
                    type="button"
                    class="w-full py-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1"
                >
                    <span>Rincian Penjualan Voucher</span>
                    <span>→</span>
                </button>
            </div>
        </div>

        <!-- ═══ 5. TABEL AKTIVITAS KONEKSI UP & DOWN TERKINI (LIVE FEED) ═══ -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-5 md:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📡 Log Aktivitas Koneksi UP & DOWN Terkini</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            {{ filteredConnectionFeed.length }} Sesi
                        </span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Keterangan waktu lengkap detik WIB, penyebab disconnect, alamat IP, ODP, dan follow-up WhatsApp langsung.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-slate-400 font-medium">Tampilan Cepat:</span>
                    <button
                        @click="tableFilterStatus = 'all'"
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition"
                        :class="tableFilterStatus === 'all' ? 'bg-purple-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                    >
                        Semua
                    </button>
                    <button
                        @click="tableFilterStatus = 'up'"
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1"
                        :class="tableFilterStatus === 'up' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> UP (Online)
                    </button>
                    <button
                        @click="tableFilterStatus = 'down'"
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1"
                        :class="tableFilterStatus === 'down' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-rose-600 dark:text-rose-400'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> DOWN (Offline)
                    </button>
                    <button
                        @click="tableFilterStatus = 'expired'"
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1"
                        :class="tableFilterStatus === 'expired' ? 'bg-red-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-red-600 dark:text-red-400'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> EXPIRED
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-400 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Pelanggan / Kode</th>
                            <th class="py-3 px-4">Waktu Lengkap (WIB)</th>
                            <th class="py-3 px-4">Penyebab Disconnect / Keterangan</th>
                            <th class="py-3 px-4">Jaringan & Lokasi</th>
                            <th class="py-3 px-4 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr
                            v-for="item in filteredConnectionFeed"
                            :key="`${item.service_type}-${item.id}`"
                            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                        >
                            <!-- Status Badge -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span
                                    v-if="item.status === 'up'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                    <span>ONLINE (UP)</span>
                                </span>
                                <span
                                    v-else-if="item.status === 'down'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/40"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>OFFLINE (DOWN)</span>
                                </span>
                                <span
                                    v-else-if="item.status === 'expired'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/40"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>EXPIRED</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40"
                                >
                                    <span>ISOLIR</span>
                                </span>
                            </td>

                            <!-- Pelanggan / Kode -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wide"
                                        :class="item.service_type === 'pppoe' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300'"
                                    >
                                        {{ item.service_type }}
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white">{{ item.name }}</p>
                                        <p class="text-[10px] font-mono text-slate-400">{{ item.customer_code }} · {{ item.package_name }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Waktu Lengkap (WIB) & Waktu Relatif -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <p class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ item.full_time }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">
                                    ⏱️ {{ item.time_ago }}
                                </p>
                            </td>

                            <!-- Penyebab Disconnect / Keterangan -->
                            <td class="py-3 px-4">
                                <span
                                    class="inline-block px-2 py-0.5 rounded-md text-[11px] font-medium"
                                    :class="item.status === 'up' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-semibold'"
                                >
                                    {{ item.disconnect_cause }}
                                </span>
                            </td>

                            <!-- Jaringan & Lokasi -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <p class="text-xs font-mono text-slate-700 dark:text-slate-300">
                                    IP: {{ item.ip_address }}
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    📍 {{ item.area_name }} · 🗄️ {{ item.odp_name }}
                                </p>
                            </td>

                            <!-- Aksi Cepat (WhatsApp Follow-up / Hapus Expired) -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <button
                                    v-if="item.service_type === 'hotspot' && item.status === 'expired'"
                                    @click="deleteFeedVoucher(item)"
                                    type="button"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold shadow-sm shadow-rose-500/20 active:scale-95 transition"
                                    title="Hapus Voucher Expired Ini"
                                >
                                    <span>🗑️ Hapus</span>
                                </button>
                                <a
                                    v-else-if="item.whatsapp_url"
                                    :href="item.whatsapp_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-[11px] font-bold shadow-sm shadow-emerald-500/20 active:scale-95 transition"
                                    title="Hubungi Pelanggan via WhatsApp"
                                >
                                    <span>💬 Hubungi WA</span>
                                </a>
                                <span v-else class="text-[10px] text-slate-400">-</span>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="!filteredConnectionFeed.length">
                            <td colspan="6" class="text-center py-10 text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto text-xl mb-2">
                                    🔍
                                </div>
                                <p class="font-bold text-xs text-slate-600 dark:text-slate-300">Tidak ada sesi koneksi yang sesuai dengan filter.</p>
                                <p class="text-[11px] mt-1">Coba ubah kata kunci pencarian atau filter status koneksi di atas.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══ 6. MODAL INVESTIGASI GANGGUAN MASSAL (SMART OUTAGE) ═══ -->
        <div v-if="activeModal === 'outage'" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-rose-50 dark:bg-rose-950/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center text-xl shadow-md">
                            ⚠️
                        </div>
                        <div>
                            <h3 class="text-base font-black text-rose-700 dark:text-rose-300">Deteksi Gangguan Massal Jalur</h3>
                            <p class="text-xs text-rose-600/80">Analisa cerdas korelasi pelanggan terputus bersamaan</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 rounded-full bg-white/80 dark:bg-slate-800 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                        ✕
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[400px] overflow-y-auto">
                    <div
                        v-for="(cl, idx) in stats.smart_outage?.clusters"
                        :key="idx"
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-xs text-slate-900 dark:text-white">📍 {{ cl.target_type }}: {{ cl.target_name }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white">{{ cl.affected_count }} Pelanggan Down</span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mb-2">{{ cl.message }}</p>
                        <div class="text-[11px] text-slate-500">
                            <strong>Pelanggan terdampak:</strong>
                            <p class="mt-0.5 font-mono text-[10px]">{{ cl.customers?.join(', ') }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <button @click="copyOutageReport" type="button" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200">
                        📋 Salin Teks Laporan
                    </button>
                    <button @click="activeModal = null" type="button" class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl text-xs font-bold">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ 7. MODAL DETAIL ISI SALDO & VOUCHER TERJUAL ═══ -->
        <div v-if="activeModal === 'topup_details' || activeModal === 'voucher_details'" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">{{ activeModal === 'topup_details' ? '💰' : '🎟️' }}</span>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">
                            {{ activeModal === 'topup_details' ? 'Rincian Transaksi Isi Saldo Hari Ini' : 'Rincian Penjualan Voucher Hari Ini' }}
                        </h3>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                        ✕
                    </button>
                </div>

                <div class="p-6 max-h-[380px] overflow-y-auto space-y-2.5">
                    <template v-if="activeModal === 'topup_details'">
                        <div
                            v-for="item in topupStats.recent_topups"
                            :key="item.id"
                            class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-center justify-between"
                        >
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ item.reseller_name }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ item.full_time }} ({{ item.time_ago }})</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ item.description }}</p>
                            </div>
                            <span class="text-sm font-black font-mono text-emerald-600 dark:text-emerald-400">{{ item.amount_formatted }}</span>
                        </div>
                    </template>

                    <template v-else>
                        <div
                            v-for="item in voucherSalesStats.recent_vouchers"
                            :key="item.id"
                            class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-center justify-between"
                        >
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ item.code }}</p>
                                <p class="text-[10px] text-purple-600 font-bold">{{ item.profile_name }} · {{ item.reseller_name }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ item.full_time }}</p>
                            </div>
                            <span class="text-sm font-black font-mono text-amber-600 dark:text-amber-400">{{ item.price_formatted }}</span>
                        </div>
                    </template>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button @click="activeModal = null" type="button" class="px-5 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl text-xs font-bold">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ 8. MODAL DETAIL PPPOE & HOTSPOT KONEKSI ═══ -->
        <div v-if="activeModal === 'pppoe_all' || activeModal === 'hotspot_all'" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-3xl w-full shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">{{ activeModal === 'pppoe_all' ? '🏠' : '🎟️' }}</span>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">
                            {{ activeModal === 'pppoe_all' ? 'Daftar Sesi Pelanggan PPPoE (UP & DOWN)' : 'Daftar Sesi Voucher Hotspot (UP & DOWN)' }}
                        </h3>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                        ✕
                    </button>
                </div>

                <div class="p-6 max-h-[440px] overflow-y-auto space-y-2">
                    <div
                        v-for="item in (activeModal === 'pppoe_all' ? pppoeStats.items : hotspotStats.items)"
                        :key="item.id"
                        class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-3"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-2 h-2 rounded-full"
                                    :class="item.status === 'up' ? 'bg-emerald-500' : 'bg-rose-500'"
                                ></span>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ item.name }}</p>
                                <span class="text-[10px] font-mono text-slate-400">({{ item.customer_code }})</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                <strong class="text-slate-700 dark:text-slate-300">{{ item.full_time }}</strong> · {{ item.time_ago }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Keterangan: <span :class="item.status === 'up' ? 'text-emerald-600' : 'text-rose-600 font-bold'">{{ item.disconnect_cause }}</span> · 📍 {{ item.area_name }} / {{ item.odp_name }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a
                                v-if="item.whatsapp_url"
                                :href="item.whatsapp_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-[11px] font-bold shadow-sm"
                            >
                                WhatsApp
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button @click="activeModal = null" type="button" class="px-5 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl text-xs font-bold">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ 9. MODAL DETAIL VOUCHER HOTSPOT EXPIRED (BISA DIHAPUS) ═══ -->
        <div v-if="activeModal === 'expired_vouchers'" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-in fade-in duration-150">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-rose-500/10">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">🎟️</span>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Kelola & Hapus Voucher Hotspot Expired
                            </h3>
                            <p class="text-xs text-slate-500">
                                Total {{ stats.expired_voucher_count || (stats.expired_vouchers?.length || 0) }} voucher masa aktif telah kedaluwarsa
                            </p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <!-- Toolbar: Search & Hapus Semua -->
                    <div class="flex items-center gap-2 mb-4">
                        <div class="relative flex-1">
                            <input
                                v-model="expiredSearch"
                                type="text"
                                placeholder="Cari kode voucher, profil, reseller..."
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2.5 pl-3 pr-7 focus:ring-rose-500 focus:border-rose-500"
                            />
                            <button
                                v-if="expiredSearch"
                                @click="expiredSearch = ''"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs"
                            >
                                ✕
                            </button>
                        </div>
                        <button
                            v-if="(stats.expired_vouchers?.length || 0) > 0"
                            @click="deleteAllExpiredVouchers"
                            :disabled="isBulkDeleting"
                            type="button"
                            class="px-4 py-2.5 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-sm shadow-rose-500/20 active:scale-95 disabled:opacity-50 shrink-0"
                            title="Hapus SEMUA voucher expired dari database & FreeRADIUS"
                        >
                            <svg v-if="isBulkDeleting" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span v-else>🗑️</span>
                            <span>Hapus Semua ({{ stats.expired_voucher_count || stats.expired_vouchers?.length || 0 }})</span>
                        </button>
                    </div>

                    <!-- List Container -->
                    <div class="max-h-[380px] overflow-y-auto space-y-2.5 pr-1">
                        <div
                            v-for="item in filteredExpiredVouchers"
                            :key="item.id"
                            class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-mono font-black text-slate-900 dark:text-white bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded">
                                        {{ item.code }}
                                    </span>
                                    <span v-if="item.is_free" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60">
                                        🎁 Gratis
                                    </span>
                                    <span v-else class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200/60 font-mono">
                                        {{ item.price_formatted }}
                                    </span>
                                    <span class="text-[10px] text-slate-600 dark:text-slate-300 font-semibold truncate">
                                        {{ item.profile_name }} ({{ item.duration }})
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-1 flex-wrap">
                                    <span>👤 {{ item.reseller_name }}</span>
                                    <span>·</span>
                                    <span>📍 {{ item.area_name }}</span>
                                    <span>·</span>
                                    <span class="text-rose-500 font-medium">⏱️ Habis: {{ item.expired_at }}</span>
                                </div>
                            </div>

                            <button
                                @click="deleteSingleExpiredVoucher(item)"
                                :disabled="deletingVoucherId === item.id"
                                type="button"
                                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs active:scale-95 disabled:opacity-50 shrink-0"
                                title="Hapus voucher ini"
                            >
                                <svg v-if="deletingVoucherId === item.id" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span v-else>🗑️</span>
                                <span>Hapus</span>
                            </button>
                        </div>

                        <div v-if="!filteredExpiredVouchers.length" class="text-center py-10 text-xs text-slate-400">
                            <div class="text-4xl mb-2">🎉</div>
                            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">Tidak ada voucher expired</p>
                            <p class="text-[11px] mt-1">Seluruh voucher aktif atau database telah dibersihkan.</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button @click="activeModal = null" type="button" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200">
                        Tutup
                    </button>
                    <a
                        href="/vouchers/expired"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm"
                    >
                        <span>Buka Halaman Lengkap Voucher Expired →</span>
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import RealtimeWidget from '@/Components/Dashboard/RealtimeWidget.vue';

const props = defineProps({
    realtimeStats: {
        type: Object,
        default: () => ({}),
    },
    availableAreas: {
        type: Array,
        default: () => [],
    },
    availableNas: {
        type: Array,
        default: () => [],
    },
    selectedAreaId: {
        type: [String, Number],
        default: 'all',
    },
    selectedNasId: {
        type: [String, Number],
        default: 'all',
    },
    selectedPeriod: {
        type: String,
        default: 'today',
    },
    selectedService: {
        type: String,
        default: 'all',
    },
    selectedStatus: {
        type: String,
        default: 'all',
    },
    searchQuery: {
        type: String,
        default: '',
    },
    nasStats: {
        type: Object,
        default: () => ({ total_nas: 1 }),
    },
});

// State Filter
const activeAreaId = ref(props.selectedAreaId);
const activeNasId = ref(props.selectedNasId);
const activePeriod = ref(props.selectedPeriod);
const activeService = ref(props.selectedService);
const activeStatus = ref(props.selectedStatus);
const searchKeyword = ref(props.searchQuery);
const tableFilterStatus = ref('all');

// Live State Data
const stats = ref({ ...props.realtimeStats });
const isRefreshing = ref(false);
const realtimeWidgetRef = ref(null);
const lastUpdated = ref(props.realtimeStats?.last_updated || new Date().toLocaleTimeString('id-ID'));
const activeModal = ref(null);
const expiredSearch = ref('');
const deletingVoucherId = ref(null);
const isBulkDeleting = ref(false);

const filteredExpiredVouchers = computed(() => {
    let list = stats.value.expired_vouchers || [];
    if (!expiredSearch.value) return list;
    const q = expiredSearch.value.toLowerCase();
    return list.filter((v) =>
        (v.code && v.code.toLowerCase().includes(q)) ||
        (v.username && v.username.toLowerCase().includes(q)) ||
        (v.profile_name && v.profile_name.toLowerCase().includes(q)) ||
        (v.reseller_name && v.reseller_name.toLowerCase().includes(q)) ||
        (v.area_name && v.area_name.toLowerCase().includes(q))
    );
});

// Auto Refresh & Countdown Timer
const pollInterval = ref(15); // Default 15s
const countdown = ref(15);
let timerInterval = null;

const refreshOptions = [
    { label: 'Pause', value: 0 },
    { label: '5s', value: 5 },
    { label: '15s', value: 15 },
    { label: '30s', value: 30 },
    { label: '60s', value: 60 },
];

const setPollInterval = (sec) => {
    pollInterval.value = sec;
    countdown.value = sec;
};

// Computed Helpers
const currentAreaName = computed(() => {
    if (activeAreaId.value === 'all') return 'Seluruh Wilayah (Nasional)';
    const found = props.availableAreas.find((a) => a.id == activeAreaId.value);
    return found ? found.name : 'Wilayah';
});

const trafficStats = computed(() => stats.value.traffic || {
    download_mbps: 0,
    upload_mbps: 0,
    total_mbps: 0,
    peak_mbps_today: 0,
    download_gb_today: 0,
    upload_gb_today: 0,
    total_gb_today: 0,
    history: [],
});

const trafficHistory = computed(() => trafficStats.value.history || []);

const pppoeStats = computed(() => stats.value.pppoe || {
    online: 0,
    down: 0,
    isolir: 0,
    total: 0,
    uptime_rate: 100,
    items: [],
});

const pppoeDownSample = computed(() => {
    return (pppoeStats.value.items || []).filter((i) => i.status === 'down').slice(0, 3);
});

const hotspotStats = computed(() => stats.value.hotspot || {
    online: 0,
    down: 0,
    available: 0,
    total: 0,
    uptime_rate: 100,
    items: [],
});

const hotspotSample = computed(() => {
    return (hotspotStats.value.items || []).slice(0, 3);
});

const topupStats = computed(() => stats.value.topup || {
    today_amount_formatted: 'Rp 0',
    today_count: 0,
    total_all_formatted: 'Rp 0',
    recent_topups: [],
});

const voucherSalesStats = computed(() => stats.value.voucher_sales || {
    today_amount_formatted: 'Rp 0',
    today_count: 0,
    breakdown: [],
    recent_vouchers: [],
});

// SVG Chart Calculations
const rxLinePoints = computed(() => {
    const list = trafficHistory.value;
    if (!list.length) return '0,100 500,100';
    const maxVal = Math.max(50, ...list.map((d) => Math.max(d.rx, d.tx))) * 1.15;
    return list
        .map((d, idx) => {
            const x = (idx / (list.length - 1)) * 500;
            const y = 95 - (d.rx / maxVal) * 85;
            return `${x},${y}`;
        })
        .join(' ');
});

const rxAreaPoints = computed(() => {
    const line = rxLinePoints.value;
    return `0,100 ${line} 500,100`;
});

const txLinePoints = computed(() => {
    const list = trafficHistory.value;
    if (!list.length) return '0,100 500,100';
    const maxVal = Math.max(50, ...list.map((d) => Math.max(d.rx, d.tx))) * 1.15;
    return list
        .map((d, idx) => {
            const x = (idx / (list.length - 1)) * 500;
            const y = 95 - (d.tx / maxVal) * 85;
            return `${x},${y}`;
        })
        .join(' ');
});

const txAreaPoints = computed(() => {
    const line = txLinePoints.value;
    return `0,100 ${line} 500,100`;
});

const latestX = computed(() => 500);
const latestRxY = computed(() => {
    const list = trafficHistory.value;
    if (!list.length) return 50;
    const maxVal = Math.max(50, ...list.map((d) => Math.max(d.rx, d.tx))) * 1.15;
    const last = list[list.length - 1];
    return 95 - (last.rx / maxVal) * 85;
});

// Filtered Connection Feed
const filteredConnectionFeed = computed(() => {
    let list = stats.value.connection_feed || [];

    // Filter status table tabs
    if (tableFilterStatus.value !== 'all') {
        list = list.filter((i) => i.status === tableFilterStatus.value);
    }

    // Filter search keyword
    if (searchKeyword.value) {
        const q = searchKeyword.value.toLowerCase();
        list = list.filter(
            (i) =>
                (i.name && i.name.toLowerCase().includes(q)) ||
                (i.customer_code && i.customer_code.toLowerCase().includes(q)) ||
                (i.username && i.username.toLowerCase().includes(q)) ||
                (i.ip_address && i.ip_address.toLowerCase().includes(q)) ||
                (i.odp_name && i.odp_name.toLowerCase().includes(q)) ||
                (i.phone && i.phone.toLowerCase().includes(q))
        );
    }

    return list;
});

// Methods
const onFilterChange = () => {
    triggerManualRefresh();
};

const triggerManualRefresh = async () => {
    isRefreshing.value = true;
    try {
        const res = await axios.get('/dashboard/realtime-api', {
            params: {
                area_id: activeAreaId.value,
                nas_id: activeNasId.value,
                period: activePeriod.value,
                service: activeService.value,
                status: activeStatus.value,
                search: searchKeyword.value,
            },
        });
        if (res.data) {
            stats.value = res.data;
            lastUpdated.value = res.data.last_updated || new Date().toLocaleTimeString('id-ID');
        }
    } catch (e) {
        console.warn('Refresh error:', e);
    } finally {
        isRefreshing.value = false;
        countdown.value = pollInterval.value;
    }
};

const copyOutageReport = () => {
    const summary = stats.value.smart_outage?.summary || 'Tidak ada gangguan massal.';
    navigator.clipboard.writeText(summary);
    alert('Laporan gangguan berhasil disalin ke clipboard!');
};

const deleteFeedVoucher = async (item) => {
    const result = await Swal.fire({
        title: `Hapus Voucher ${item.customer_code || item.name}?`,
        text: 'Voucher expired ini akan dihapus permanen dari sistem & FreeRADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
    });

    if (!result.isConfirmed) return;

    try {
        const res = await axios.post(`/dashboard/vouchers/${item.id}/delete`);
        if (res.data?.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: res.data.message || 'Voucher expired berhasil dihapus.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
            });
            triggerManualRefresh();
        }
    } catch (e) {
        Swal.fire({
            title: 'Gagal',
            text: e.response?.data?.message || 'Gagal menghapus voucher.',
            icon: 'error',
        });
    }
};

const deleteSingleExpiredVoucher = async (item) => {
    const result = await Swal.fire({
        title: `Hapus Voucher ${item.code}?`,
        text: 'Voucher akan dihapus permanen dari sistem & FreeRADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
    });

    if (!result.isConfirmed) return;

    deletingVoucherId.value = item.id;
    try {
        const res = await axios.post(`/dashboard/vouchers/${item.id}/delete`);
        if (res.data?.success) {
            if (stats.value.expired_vouchers) {
                stats.value.expired_vouchers = stats.value.expired_vouchers.filter((v) => v.id !== item.id);
            }
            if (stats.value.expired_voucher_count > 0) {
                stats.value.expired_voucher_count--;
            }
            Swal.fire({
                title: 'Berhasil!',
                text: res.data.message || 'Voucher berhasil dihapus.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
            });
            triggerManualRefresh();
        }
    } catch (e) {
        Swal.fire({
            title: 'Gagal',
            text: e.response?.data?.message || 'Gagal menghapus voucher.',
            icon: 'error',
        });
    } finally {
        deletingVoucherId.value = null;
    }
};

const deleteAllExpiredVouchers = async () => {
    const count = stats.value.expired_voucher_count || stats.value.expired_vouchers?.length || 0;
    const result = await Swal.fire({
        title: `Hapus SEMUA (${count}) Voucher Expired?`,
        text: 'Seluruh voucher yang telah habis masa aktifnya akan dibersihkan permanen dari database & FreeRADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Ya, Hapus Semua (${count})!`,
        cancelButtonText: 'Batal',
    });

    if (!result.isConfirmed) return;

    isBulkDeleting.value = true;
    try {
        const res = await axios.post('/dashboard/vouchers/delete-expired');
        if (res.data?.success) {
            stats.value.expired_vouchers = [];
            stats.value.expired_voucher_count = 0;
            Swal.fire({
                title: 'Dibersihkan!',
                text: res.data.message || 'Semua voucher expired berhasil dihapus.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
            });
            triggerManualRefresh();
        }
    } catch (e) {
        Swal.fire({
            title: 'Gagal',
            text: e.response?.data?.message || 'Gagal menghapus voucher expired massal.',
            icon: 'error',
        });
    } finally {
        isBulkDeleting.value = false;
    }
};

// Lifecycle
onMounted(() => {
    timerInterval = setInterval(() => {
        if (pollInterval.value <= 0) return;
        if (countdown.value > 1) {
            countdown.value--;
        } else {
            countdown.value = pollInterval.value;
            triggerManualRefresh();
        }
    }, 1000);
});

onBeforeUnmount(() => {
    if (timerInterval) clearInterval(timerInterval);
});
</script>
