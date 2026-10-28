<template>
    <AppLayout title="Pantauan Lokasi Karyawan">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                            🛰️
                        </span>
                        <span>Pantauan Lokasi Karyawan</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Monitoring real-time karyawan yang sedang menggunakan aplikasi, sesi GPS aktif & pemetaan lokasi kerja.
                    </p>
                </div>

                <!-- Action Toolbar: Auto-refresh & Focus -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button 
                        type="button" 
                        @click="toggleAutoRefresh"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold border transition shadow-2xs flex items-center gap-2 cursor-pointer"
                        :class="autoRefreshEnabled 
                            ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' 
                            : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-slate-700'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="autoRefreshEnabled ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                        <span>Auto-Refresh: {{ autoRefreshEnabled ? `${countdown}s` : 'OFF' }}</span>
                    </button>

                    <button 
                        type="button" 
                        @click="fetchLiveData"
                        :disabled="isLoading"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition shadow-2xs flex items-center gap-1.5 cursor-pointer disabled:opacity-60"
                        title="Perbarui Data Sekarang"
                    >
                        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Refresh</span>
                    </button>

                    <button 
                        type="button" 
                        @click="fitAllMarkers"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>🎯 Fokus Peta</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-5 space-y-5">
            <!-- 1. STATISTIC CARDS -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <!-- Card 1: Online -->
                <div 
                    @click="filterStatus = filterStatus === 'online' ? 'all' : 'online'" 
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterStatus === 'online' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-gray-200/80 dark:border-slate-800 hover:border-emerald-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 group-hover:text-emerald-600 transition-colors">Sedang Online</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-100/70 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600">{{ liveStats.online || 0 }}</div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Aktif di Aplikasi (≤ 5 mnt)</p>
                </div>

                <!-- Card 2: Idle -->
                <div 
                    @click="filterStatus = filterStatus === 'idle' ? 'all' : 'idle'" 
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterStatus === 'idle' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/20' : 'border-gray-200/80 dark:border-slate-800 hover:border-amber-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 group-hover:text-amber-600 transition-colors">Karyawan Idle</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-100/70 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-600">{{ liveStats.idle || 0 }}</div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Standby (5 - 30 mnt lalu)</p>
                </div>

                <!-- Card 3: GPS Aktif -->
                <div 
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-800 transition-all duration-200 shadow-xs"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">GPS Terdeteksi</span>
                        <div class="w-8 h-8 rounded-xl bg-sky-100/70 dark:bg-sky-950/60 text-sky-600 flex items-center justify-center text-sm font-bold">
                            📍
                        </div>
                    </div>
                    <div class="text-2xl font-black text-sky-600">{{ liveStats.with_gps || 0 }}</div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Koordinat Live Tersedia</p>
                </div>

                <!-- Card 4: Teknisi Online -->
                <div 
                    @click="filterRole = filterRole === 'teknisi' ? 'all' : 'teknisi'"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterRole === 'teknisi' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-50/20' : 'border-gray-200/80 dark:border-slate-800 hover:border-indigo-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 group-hover:text-indigo-600 transition-colors">Teknisi Online</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-100/70 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center text-sm font-bold">
                            🔧
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600">{{ liveStats.technicians_online || 0 }}</div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Teknisi Lapangan Aktif</p>
                </div>

                <!-- Card 5: Sales Online -->
                <div 
                    @click="filterRole = filterRole === 'sales' ? 'all' : 'sales'"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterRole === 'sales' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/20' : 'border-gray-200/80 dark:border-slate-800 hover:border-purple-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 group-hover:text-purple-600 transition-colors">Sales Online</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-100/70 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center text-sm font-bold">
                            💼
                        </div>
                    </div>
                    <div class="text-2xl font-black text-purple-600">{{ liveStats.sales_online || 0 }}</div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Marketing / Sales Aktif</p>
                </div>
            </div>

            <!-- 2. FILTER & SEARCH BAR -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl shadow-xs border border-gray-200/80 dark:border-slate-800">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-3 items-center">
                    <!-- Search Input -->
                    <div class="lg:col-span-5">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                class="w-full pl-10 pr-9 py-2 bg-gray-50/60 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm focus:bg-white dark:focus:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all placeholder:text-gray-400" 
                                placeholder="Cari nama karyawan, jabatan, no HP, nama daerah..." 
                            />
                            <button 
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                type="button"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterStatus"
                            class="w-full px-3 py-2 bg-gray-50/60 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                        >
                            <option value="all">Semua Status ({{ liveEmployees.length }})</option>
                            <option value="online">🟢 Online ({{ liveStats.online }})</option>
                            <option value="idle">🟡 Idle ({{ liveStats.idle }})</option>
                            <option value="offline">⚪ Offline ({{ liveStats.offline }})</option>
                        </select>
                    </div>

                    <!-- Wilayah / Area Filter -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterArea"
                            class="w-full px-3 py-2 bg-gray-50/60 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                        >
                            <option value="all">Semua Wilayah</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Jabatan Filter -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterRole"
                            class="w-full px-3 py-2 bg-gray-50/60 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                        >
                            <option value="all">Semua Posisi / Jabatan</option>
                            <option v-for="pos in positions" :key="pos" :value="pos">
                                {{ pos }}
                            </option>
                        </select>
                    </div>

                    <!-- Reset Button -->
                    <div class="lg:col-span-1 flex justify-end">
                        <button 
                            type="button" 
                            @click="resetFilters" 
                            class="p-2 rounded-xl text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                            title="Reset Semua Filter"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Info Bar -->
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span>Menampilkan <b>{{ filteredEmployees.length }}</b> dari <b>{{ liveEmployees.length }}</b> karyawan.</span>
                        <span class="text-gray-300 dark:text-gray-700">|</span>
                        <span><b>{{ employeesWithGps.length }}</b> terpetakan pada Maps.</span>
                    </div>
                    <div>
                        Terakhir diperbarui: <b>{{ serverTime }}</b>
                    </div>
                </div>
            </div>

            <!-- 3. SPLIT VIEW: MAP & EMPLOYEE LIST -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                <!-- LEFT: INTERACTIVE LEAFLET MAP (7 COLS) -->
                <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-200/80 dark:border-slate-800 overflow-hidden sticky top-20">
                    <div class="px-4 py-3 bg-gray-50/80 dark:bg-slate-800/80 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Peta Live GPS Karyawan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                {{ employeesWithGps.length }} Marker Aktif
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-3">
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Online</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Idle</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-400"></span> Offline</span>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div id="employee-tracking-map" class="w-full h-[540px] z-10 bg-slate-100 dark:bg-slate-950"></div>

                    <div class="p-3 bg-gray-50/60 dark:bg-slate-800/60 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex items-center gap-1.5">
                            <span>🏢 <b>Kantor Cabang:</b> {{ areas.length }} Wilayah Operasional</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button 
                                v-for="area in areasWithCoords" 
                                :key="area.id" 
                                @click="flyToCoordinates(area.latitude, area.longitude, 15, area.name)"
                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-100 text-gray-700 dark:text-gray-300 transition cursor-pointer"
                            >
                                {{ area.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: EMPLOYEE CARDS LIST (5 COLS) -->
                <div class="lg:col-span-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Daftar Karyawan ({{ filteredEmployees.length }})
                        </h3>
                        <span class="text-[11px] text-gray-500">Klik "Fokus" untuk zoom ke peta</span>
                    </div>

                    <!-- List Container (Scrollable) -->
                    <div class="space-y-2.5 max-h-[600px] overflow-y-auto pr-1">
                        <div 
                            v-for="emp in filteredEmployees" 
                            :key="emp.id"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-4 border transition-all duration-200 shadow-2xs hover:shadow-md"
                            :class="[
                                emp.status === 'online' ? 'border-emerald-200 dark:border-emerald-800/80 hover:border-emerald-400' :
                                emp.status === 'idle' ? 'border-amber-200 dark:border-amber-800/80 hover:border-amber-400' :
                                'border-gray-200/80 dark:border-slate-800 hover:border-gray-300'
                            ]"
                        >
                            <!-- Top: Avatar, Name & Status -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="relative shrink-0">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center shadow-xs overflow-hidden">
                                            <img v-if="emp.photo_url" :src="emp.photo_url" :alt="emp.name" class="w-full h-full object-cover" />
                                            <span v-else>{{ emp.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <span 
                                            class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900"
                                            :class="[
                                                emp.status === 'online' ? 'bg-emerald-500' :
                                                emp.status === 'idle' ? 'bg-amber-500' :
                                                'bg-gray-400'
                                            ]"
                                            :title="emp.status === 'online' ? 'Sedang Online' : (emp.status === 'idle' ? 'Idle' : 'Offline')"
                                        ></span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">
                                                {{ emp.name }}
                                            </h4>
                                            <span v-if="emp.is_on_duty" class="px-1.5 py-0.2 rounded text-[9px] font-black bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                ON DUTY
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ emp.job_position || emp.role }}</span>
                                            <span>•</span>
                                            <span>{{ emp.area_name || '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div>
                                    <span 
                                        class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider inline-flex items-center gap-1"
                                        :class="[
                                            emp.status === 'online' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' :
                                            emp.status === 'idle' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' :
                                            'bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-slate-700'
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="emp.status === 'online' ? 'bg-emerald-500 animate-pulse' : (emp.status === 'idle' ? 'bg-amber-500' : 'bg-gray-400')"></span>
                                        <span>{{ emp.status === 'online' ? 'Online' : (emp.status === 'idle' ? 'Idle' : 'Offline') }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Middle: Nama Daerah Lengkap & Status GPS -->
                            <div class="mt-3 p-2.5 rounded-xl bg-gray-50 dark:bg-slate-800/60 border border-gray-100 dark:border-slate-800/80 space-y-1.5 text-xs">
                                <div class="flex items-start gap-1.5">
                                    <span class="text-sky-500 font-bold shrink-0 mt-0.5">📍</span>
                                    <div class="flex-1">
                                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Lokasi / Daerah Terdeteksi:</div>
                                        <div class="font-semibold text-gray-800 dark:text-gray-200 leading-snug">
                                            {{ emp.location_name }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-200/50 dark:border-slate-700/50">
                                    <span v-if="emp.accuracy" class="text-slate-600 dark:text-slate-300">
                                        Akurasi: <b>±{{ emp.accuracy }}m</b>
                                    </span>
                                    <span v-else class="text-slate-400">GPS belum ada sinyal</span>
                                    <span>Aktif: <b>{{ emp.last_seen_human }}</b></span>
                                </div>
                            </div>

                            <!-- Bottom Actions -->
                            <div class="mt-3 flex items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-slate-800 text-xs">
                                <div class="text-[10px] text-gray-400">
                                    <span v-if="emp.is_gps_enabled" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                        <span>🛰️</span> GPS Aktif
                                    </span>
                                    <span v-else class="text-rose-500 font-bold flex items-center gap-1">
                                        <span>⚠️</span> GPS Mati
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a 
                                        v-if="emp.phone && emp.phone !== '-'"
                                        :href="getWhatsAppLink(emp.phone, emp.name)" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition flex items-center gap-1"
                                        title="Chat WhatsApp"
                                    >
                                        <span>💬</span>
                                        <span>WA</span>
                                    </a>

                                    <button 
                                        v-if="emp.latitude && emp.longitude"
                                        type="button" 
                                        @click="focusEmployeeOnMap(emp)"
                                        class="px-3 py-1 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition flex items-center gap-1 cursor-pointer"
                                    >
                                        <span>📍</span>
                                        <span>Fokus Peta</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredEmployees.length === 0" class="p-8 text-center bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 text-gray-400">
                            <span class="text-3xl block mb-2">🔍</span>
                            <div class="text-sm font-bold text-gray-700 dark:text-gray-200">Tidak ada karyawan yang cocok</div>
                            <p class="text-xs text-gray-500 mt-1">Coba sesuaikan kata kunci pencarian atau filter status Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    employees: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, online: 0, idle: 0, offline: 0, with_gps: 0, technicians_online: 0, sales_online: 0 })
    },
    areas: {
        type: Array,
        default: () => []
    },
    positions: {
        type: Array,
        default: () => []
    },
    server_time: String,
});

// State
const liveEmployees = ref([...props.employees]);
const liveStats = ref({ ...props.stats });
const serverTime = ref(props.server_time || '');
const isLoading = ref(false);

// Filter State
const searchQuery = ref('');
const filterStatus = ref('all');
const filterArea = ref('all');
const filterRole = ref('all');

// Auto-refresh timer
const autoRefreshEnabled = ref(true);
const countdown = ref(15);
let timerInterval = null;

// Leaflet Map Instance
let map = null;
let markersLayer = null;
let officesLayer = null;
const employeeMarkerMap = {};

const employeesWithGps = computed(() => {
    return liveEmployees.value.filter(e => e.latitude && e.longitude);
});

const areasWithCoords = computed(() => {
    return props.areas.filter(a => a.latitude && a.longitude);
});

const filteredEmployees = computed(() => {
    let list = liveEmployees.value;

    if (filterStatus.value !== 'all') {
        list = list.filter(e => e.status === filterStatus.value);
    }

    if (filterArea.value !== 'all') {
        list = list.filter(e => String(e.area_id) === String(filterArea.value));
    }

    if (filterRole.value !== 'all') {
        list = list.filter(e => String(e.job_position || e.role).toLowerCase() === String(filterRole.value).toLowerCase());
    }

    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(e => 
            (e.name && e.name.toLowerCase().includes(q)) ||
            (e.job_position && e.job_position.toLowerCase().includes(q)) ||
            (e.phone && e.phone.includes(q)) ||
            (e.location_name && e.location_name.toLowerCase().includes(q)) ||
            (e.area_name && e.area_name.toLowerCase().includes(q))
        );
    }

    return list;
});

const resetFilters = () => {
    searchQuery.value = '';
    filterStatus.value = 'all';
    filterArea.value = 'all';
    filterRole.value = 'all';
};

const getWhatsAppLink = (phone, name) => {
    let clean = (phone || '').replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.substring(1);
    }
    return `https://wa.me/${clean}?text=Halo%20${encodeURIComponent(name)},%20kami%20menghubungi%20Anda%20dari%20kantor.`;
};

// Initialize Leaflet Map
const initMap = async () => {
    const container = document.getElementById('employee-tracking-map');
    if (!container || map) return;

    const L = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    // Default center (Sirnajaya / area Sukabumi)
    const defaultCenter = [-6.98, 106.74];
    map = L.map('employee-tracking-map', {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView(defaultCenter, 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);

    markersLayer = L.featureGroup().addTo(map);
    officesLayer = L.featureGroup().addTo(map);

    // Render Kantor Cabang
    renderOffices(L);

    // Render Karyawan
    renderEmployeeMarkers(L);
};

const renderOffices = (L) => {
    if (!map || !officesLayer) return;
    officesLayer.clearLayers();

    areasWithCoords.value.forEach(area => {
        const lat = parseFloat(area.latitude);
        const lng = parseFloat(area.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const officeIcon = L.divIcon({
            className: 'custom-office-pin',
            html: `
                <div style="background:#1e40af; color:#fff; width:32px; height:32px; border-radius:10px; display:flex; align-items:center; justify-content:center; border:2px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.3); font-size:16px;">
                    🏢
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -18],
        });

        const marker = L.marker([lat, lng], { icon: officeIcon })
            .bindPopup(`
                <div style="font-family:inherit; min-width:180px;">
                    <div style="font-size:13px; font-weight:800; color:#1e3a8a;">🏢 Kantor Cabang ${area.name}</div>
                    <div style="font-size:11px; color:#475569; margin-top:2px;">${area.address || 'Kantor Operasional'}</div>
                </div>
            `);

        officesLayer.addLayer(marker);
    });
};

const renderEmployeeMarkers = (L) => {
    if (!map || !markersLayer) return;
    markersLayer.clearLayers();

    employeesWithGps.value.forEach(emp => {
        const lat = parseFloat(emp.latitude);
        const lng = parseFloat(emp.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const borderColor = emp.status === 'online' ? '#10b981' : (emp.status === 'idle' ? '#f59e0b' : '#94a3b8');
        const pulseEffect = emp.status === 'online' ? 'box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.35);' : '';
        const initial = emp.name.charAt(0).toUpperCase();

        const empIcon = L.divIcon({
            className: 'custom-emp-pin',
            html: `
                <div style="background:#ffffff; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:3px solid ${borderColor}; ${pulseEffect} font-size:13px; font-weight:900; color:#1e293b; box-shadow:0 3px 10px rgba(0,0,0,0.25); cursor:pointer;">
                    ${initial}
                </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -20],
        });

        const popupContent = `
            <div style="font-family:inherit; min-width:230px; padding:2px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                    <div style="font-size:13px; font-weight:900; color:#0f172a;">${emp.name}</div>
                    <span style="font-size:9px; font-weight:800; padding:2px 6px; border-radius:999px; text-transform:uppercase; background:${emp.status === 'online' ? '#dcfce7' : (emp.status === 'idle' ? '#fef3c7' : '#f1f5f9')}; color:${emp.status === 'online' ? '#15803d' : (emp.status === 'idle' ? '#b45309' : '#475569')};">
                        ${emp.status}
                    </span>
                </div>
                <div style="font-size:11px; font-weight:700; color:#4f46e5; margin-bottom:6px;">
                    ${emp.job_position || emp.role} • ${emp.area_name || '-'}
                </div>
                <div style="background:#f8fafc; padding:6px 8px; border-radius:8px; border:1px solid #e2e8f0; font-size:11px; margin-bottom:8px;">
                    <div style="font-weight:700; color:#334155; margin-bottom:2px;">📍 ${emp.location_name}</div>
                    <div style="font-size:10px; color:#64748b; display:flex; justify-content:space-between;">
                        <span>Akurasi: ±${emp.accuracy || 0}m</span>
                        <span>Aktif: ${emp.last_seen_human}</span>
                    </div>
                </div>
                <div style="display:flex; gap:4px;">
                    ${emp.phone && emp.phone !== '-' ? `<a href="https://wa.me/${emp.phone.replace(/[^0-9]/g, '')}" target="_blank" style="flex:1; text-align:center; padding:4px 6px; background:#10b981; color:#fff; font-size:10px; font-weight:700; border-radius:6px; text-decoration:none;">WhatsApp</a>` : ''}
                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" style="flex:1; text-align:center; padding:4px 6px; background:#6366f1; color:#fff; font-size:10px; font-weight:700; border-radius:6px; text-decoration:none;">Google Maps</a>
                </div>
            </div>
        `;

        const marker = L.marker([lat, lng], { icon: empIcon })
            .bindPopup(popupContent);

        markersLayer.addLayer(marker);
        employeeMarkerMap[emp.id] = marker;
    });

    if (employeesWithGps.value.length > 0 && !hasFittedInitially.value) {
        fitAllMarkers();
        hasFittedInitially.value = true;
    }
};

const hasFittedInitially = ref(false);

const fitAllMarkers = () => {
    if (!map) return;
    const allLayers = [];
    if (markersLayer && markersLayer.getLayers().length > 0) {
        allLayers.push(markersLayer);
    }
    if (officesLayer && officesLayer.getLayers().length > 0) {
        allLayers.push(officesLayer);
    }

    if (allLayers.length > 0) {
        const group = new (window.L || Object).featureGroup(
            allLayers.flatMap(l => l.getLayers())
        );
        map.fitBounds(group.getBounds(), { padding: [50, 50], maxZoom: 16 });
    }
};

const focusEmployeeOnMap = (emp) => {
    if (!map || !emp.latitude || !emp.longitude) return;
    const lat = parseFloat(emp.latitude);
    const lng = parseFloat(emp.longitude);

    map.flyTo([lat, lng], 16, { animate: true, duration: 1.2 });
    nextTick(() => {
        const marker = employeeMarkerMap[emp.id];
        if (marker) {
            marker.openPopup();
        }
    });
};

const flyToCoordinates = (lat, lng, zoom = 15, name = '') => {
    if (!map || !lat || !lng) return;
    map.flyTo([parseFloat(lat), parseFloat(lng)], zoom, { animate: true, duration: 1.0 });
};

// Fetch live data without page reload
const fetchLiveData = async () => {
    if (isLoading.value) return;
    isLoading.value = true;

    try {
        const res = await axios.get(route('employee-locations.live-data'));
        if (res.data) {
            liveEmployees.value = res.data.employees || [];
            liveStats.value = res.data.stats || liveStats.value;
            serverTime.value = res.data.server_time || serverTime.value;

            // Re-render markers on map
            const L = await import('leaflet');
            renderEmployeeMarkers(L);
        }
    } catch (e) {
        console.error('Gagal memperbarui data lokasi karyawan:', e);
    } finally {
        isLoading.value = false;
        countdown.value = 15;
    }
};

const toggleAutoRefresh = () => {
    autoRefreshEnabled.value = !autoRefreshEnabled.value;
    if (autoRefreshEnabled.value) {
        countdown.value = 15;
        startTimer();
    } else {
        clearInterval(timerInterval);
    }
};

const startTimer = () => {
    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!autoRefreshEnabled.value) return;
        countdown.value--;
        if (countdown.value <= 0) {
            fetchLiveData();
        }
    }, 1000);
};

onMounted(() => {
    nextTick(() => {
        initMap();
        startTimer();
    });
});

onUnmounted(() => {
    clearInterval(timerInterval);
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<style scoped>
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 16px;
    padding: 2px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}
:deep(.leaflet-popup-content) {
    margin: 10px 12px;
    line-height: 1.4;
}
:deep(.leaflet-popup-tip-container) {
    margin-top: -1px;
}
</style>

