<template>
    <component :is="layoutComponent" title="Monitoring Sesi Voucher" subtitle="Pantau aktivitas voucher online realtime dan riwayat sesi voucher offline">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Monitoring Sesi Voucher</h1>
            <p class="text-sm text-gray-500">Pantau aktivitas voucher online realtime dan riwayat sesi voucher offline</p>
        </div>

        <!-- EXECUTIVE KPI STATS CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 mb-6">
            <!-- Card 1: Voucher Online -->
            <div 
                @click="switchTab('online')" 
                :class="activeTab === 'online' ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30' : 'border-gray-200 bg-white hover:border-emerald-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Voucher Online</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-emerald-900 tracking-tight flex items-center gap-2">
                        <span>{{ Number(stats?.online_count || 0).toLocaleString('id-ID') }}</span>
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                    </div>
                    <div class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                        Sesi aktif terhubung
                    </div>
                </div>
            </div>

            <!-- Card 2: Voucher Offline -->
            <div 
                @click="switchTab('offline')" 
                :class="activeTab === 'offline' ? 'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50/30' : 'border-gray-200 bg-white hover:border-indigo-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Voucher Offline</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-indigo-900 tracking-tight">
                        {{ Number(stats?.offline_count || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-indigo-600 font-semibold mt-0.5">
                        Riwayat sesi terputus
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Sesi Tercatat -->
            <div class="border border-gray-200 bg-white rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Sesi</span>
                    <div class="w-8 h-8 rounded-xl bg-gray-50 text-gray-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-gray-900 tracking-tight">
                        {{ Number(stats?.total_count || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-gray-500 font-medium mt-0.5">
                        Sesi online & riwayat
                    </div>
                </div>
            </div>

            <!-- Card 4: Total Download -->
            <div class="border border-blue-200 bg-gradient-to-br from-blue-50/50 via-white to-sky-50/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">Total Unduhan</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black text-blue-950 tracking-tight">
                        ↓ {{ formatBytes(stats?.total_download || 0) }}
                    </div>
                    <div class="text-[11px] text-blue-600 font-semibold mt-0.5">
                        Akumulasi download
                    </div>
                </div>
            </div>

            <!-- Card 5: Total Upload -->
            <div class="border border-amber-200 bg-gradient-to-br from-amber-50/50 via-white to-orange-50/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between col-span-2 sm:col-span-1 lg:col-span-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">Total Unggahan</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black text-amber-950 tracking-tight">
                        ↑ {{ formatBytes(stats?.total_upload || 0) }}
                    </div>
                    <div class="text-[11px] text-amber-700 font-semibold mt-0.5">
                        Akumulasi upload
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS NAVIGATOR -->
        <div class="flex items-center gap-2 border-b border-gray-200 mb-6 bg-white px-2 rounded-t-2xl">
            <button 
                @click="switchTab('online')"
                :class="activeTab === 'online' ? 'border-emerald-600 text-emerald-700 font-bold bg-emerald-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                class="flex items-center gap-2.5 py-3.5 px-5 border-b-2 text-sm transition-all rounded-t-xl"
            >
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span>Voucher Online</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold transition-colors" :class="activeTab === 'online' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                    {{ Number(stats?.online_count || 0).toLocaleString('id-ID') }}
                </span>
            </button>

            <button 
                @click="switchTab('offline')"
                :class="activeTab === 'offline' ? 'border-indigo-600 text-indigo-700 font-bold bg-indigo-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                class="flex items-center gap-2.5 py-3.5 px-5 border-b-2 text-sm transition-all rounded-t-xl"
            >
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Voucher Offline</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold transition-colors" :class="activeTab === 'offline' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-600'">
                    {{ Number(stats?.offline_count || 0).toLocaleString('id-ID') }}
                </span>
            </button>
        </div>

        <!-- FILTER BAR -->
        <div class="mb-6 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari kode, username, IP, MAC, reseller, area..." 
                        class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-2xs"
                        @keyup.enter="doFilter"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Profil Paket Filter -->
                <select v-model="profileFilter" @change="doFilter" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[200px]">
                    <option value="">Semua Profil Paket</option>
                    <option v-for="prof in profiles" :key="prof.id" :value="prof.id">
                        {{ prof.name }} ({{ prof.duration || 'Hotspot' }})
                    </option>
                </select>

                <!-- Reseller Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="resellerFilter" @change="doFilter" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Pemilik / Reseller</option>
                    <option value="system">🏢 Kantor / System</option>
                    <option v-for="res in resellers" :key="res.id" :value="res.id">
                        👤 {{ res.customer?.name || ('Reseller #' + res.id) }}
                    </option>
                </select>

                <!-- Area / Wilayah Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="areaFilter" @change="doFilter" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Area / Wilayah</option>
                    <option v-for="area in areas" :key="area.id" :value="area.id">
                        📍 {{ area.name }}
                    </option>
                </select>

                <!-- Reset Button -->
                <button 
                    v-if="search || profileFilter || resellerFilter || areaFilter" 
                    @click="resetFilter" 
                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                    title="Reset Filter"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Reset</span>
                </button>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <button id="btn-refresh-online" @click="refresh" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold text-sm rounded-xl shadow-xs flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>

        <!-- TAB CONTENT: 1. VOUCHER ONLINE TABLE -->
        <div v-if="activeTab === 'online'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode / User</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil Paket</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pemilik / Area</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP / MAC Address</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Uptime & Login</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Traffic</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(row, idx) in paginatedOnlineData.data" :key="row.id" class="bg-emerald-50/75 hover:bg-emerald-100/80 border-l-4 border-l-emerald-500 transition-colors">
                            <td class="px-4 py-3.5 text-center text-xs text-gray-400 font-mono">
                                {{ (paginatedOnlineData.current_page - 1) * perPage + idx + 1 }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="can('voucher_monitoring_btn_kick')"
                                        :id="`btn-kick-${row.id}`"
                                        @click="kickSession(row)"
                                        :disabled="kicking === row.username || !row.is_active"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-600 hover:text-white transition-colors disabled:opacity-50"
                                        title="Kick Sesi Online dari MikroTik"
                                    >
                                        <svg class="w-3 h-3" :class="{'animate-pulse': kicking === row.username}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span>{{ kicking === row.username ? '...' : 'KICK' }}</span>
                                    </button>
                                    <div>
                                        <div class="font-mono font-bold text-indigo-700 text-sm tracking-wide">{{ row.code }}</div>
                                        <div v-if="row.username !== row.code" class="text-[11px] text-gray-400 font-mono">User: {{ row.username }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-xs font-semibold">
                                    {{ row.profile || '-' }}
                                </span>
                                <div v-if="row.profile_duration" class="text-[11px] text-gray-400 mt-0.5">Durasi: {{ row.profile_duration }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-0.5">
                                    <span class="text-xs font-bold text-gray-800">{{ row.reseller }}</span>
                                    <span v-if="row.area && row.area !== '-'" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                        📍 {{ row.area }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs text-gray-700">
                                <div class="font-bold text-gray-900">{{ row.ip_address || '-' }}</div>
                                <div class="text-[10px] text-gray-400 font-mono">{{ row.mac_address || '-' }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs">
                                <div class="font-bold text-emerald-600 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>{{ formatUptime(row.uptime) }}</span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ formatDate(row.login_time) }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs">
                                <div class="text-blue-600 font-medium">↓ {{ formatBytes(row.download) }}</div>
                                <div class="text-amber-600 font-medium">↑ {{ formatBytes(row.upload) }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right text-xs">
                                <button
                                    v-if="row.voucher_id && can('voucher_monitoring_btn_toggle')"
                                    :id="`btn-toggle-${row.id}`"
                                    @click="toggleStatus(row)"
                                    :disabled="toggling === row.voucher_id"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors disabled:opacity-50"
                                    :class="row.is_active ? 'text-orange-600 bg-orange-50 hover:bg-orange-500 hover:text-white border border-orange-200' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-500 hover:text-white border border-emerald-200'"
                                    :title="row.is_active ? 'Nonaktifkan Voucher' : 'Aktifkan Voucher'"
                                >
                                    <svg v-if="toggling === row.voucher_id" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>{{ row.is_active ? 'Disable' : 'Enable' }}</span>
                                </button>
                                <span v-else class="text-gray-400 text-xs">-</span>
                            </td>
                        </tr>
                        <tr v-if="paginatedOnlineData.data.length === 0">
                            <td colspan="8" class="text-center py-16">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center border border-gray-100">
                                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm font-semibold">Tidak ada sesi voucher yang sedang online saat ini.</p>
                                    <p class="text-gray-400 text-xs">Voucher yang login di MikroTik akan otomatis tampil realtime di sini.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Online -->
            <div v-if="paginatedOnlineData.last_page > 1" class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <span class="text-xs text-gray-500">
                    Menampilkan {{ (paginatedOnlineData.current_page - 1) * perPage + 1 }} - {{ Math.min(paginatedOnlineData.current_page * perPage, paginatedOnlineData.total) }} dari {{ paginatedOnlineData.total }} data
                </span>
                <div class="flex items-center gap-1">
                    <button 
                        @click="currentOnlinePage--" 
                        :disabled="currentOnlinePage <= 1"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span class="px-2 text-xs font-semibold text-gray-700">Hal {{ currentOnlinePage }} / {{ paginatedOnlineData.last_page }}</span>
                    <button 
                        @click="currentOnlinePage++" 
                        :disabled="currentOnlinePage >= paginatedOnlineData.last_page"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT: 2. VOUCHER OFFLINE TABLE -->
        <div v-else-if="activeTab === 'offline'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode / User</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil Paket</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pemilik / Area</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP / MAC Terakhir</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sesi Terakhir</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Durasi</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Traffic</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Alasan Putus</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(row, idx) in paginatedOfflineData.data" :key="row.id" class="bg-rose-50/75 hover:bg-rose-100/80 border-l-4 border-l-rose-500 transition-colors">
                            <td class="px-4 py-3.5 text-center text-xs text-gray-400 font-mono">
                                {{ (paginatedOfflineData.current_page - 1) * perPage + idx + 1 }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="font-mono font-bold text-gray-900 text-sm tracking-wide">{{ row.code }}</div>
                                <div v-if="row.username !== row.code" class="text-[11px] text-gray-400 font-mono">User: {{ row.username }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 bg-gray-100 text-gray-700 border border-gray-200 rounded text-xs font-medium">
                                    {{ row.profile || '-' }}
                                </span>
                                <div v-if="row.profile_duration" class="text-[11px] text-gray-400 mt-0.5">Durasi: {{ row.profile_duration }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-0.5">
                                    <span class="text-xs font-bold text-gray-800">{{ row.reseller }}</span>
                                    <span v-if="row.area && row.area !== '-'" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                        📍 {{ row.area }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs text-gray-700">
                                <div>{{ row.ip_address || '-' }}</div>
                                <div class="text-[10px] text-gray-400">{{ row.mac_address || '-' }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs text-gray-600">
                                <div v-if="row.logout_time">Logout: {{ formatDate(row.logout_time) }}</div>
                                <div v-else-if="row.login_time">Login: {{ formatDate(row.login_time) }}</div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs font-mono">
                                <span v-if="row.total_time > 0" class="text-indigo-600 font-semibold">{{ formatUptime(row.total_time) }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs">
                                <div class="text-blue-600 font-medium">↓ {{ formatBytes(row.download) }}</div>
                                <div class="text-amber-600 font-medium">↑ {{ formatBytes(row.upload) }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span 
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                                    :class="{
                                        'bg-gray-100 text-gray-700 border border-gray-200': row.terminate_cause.includes('User-Request') || row.terminate_cause.includes('Logout'),
                                        'bg-amber-50 text-amber-700 border border-amber-200': row.terminate_cause.includes('Lost-Carrier'),
                                        'bg-rose-50 text-rose-700 border border-rose-200': row.terminate_cause.includes('Timeout') || row.terminate_cause.includes('Expired'),
                                        'bg-blue-50 text-blue-700 border border-blue-200': !row.terminate_cause.includes('User-Request') && !row.terminate_cause.includes('Lost')
                                    }"
                                >
                                    {{ row.terminate_cause }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right text-xs">
                                <button
                                    v-if="row.voucher_id && can('voucher_monitoring_btn_toggle')"
                                    :id="`btn-toggle-${row.id}`"
                                    @click="toggleStatus(row)"
                                    :disabled="toggling === row.voucher_id"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors disabled:opacity-50"
                                    :class="row.is_active ? 'text-orange-600 bg-orange-50 hover:bg-orange-500 hover:text-white border border-orange-200' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-500 hover:text-white border border-emerald-200'"
                                    :title="row.is_active ? 'Nonaktifkan Voucher' : 'Aktifkan Voucher'"
                                >
                                    <svg v-if="toggling === row.voucher_id" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>{{ row.is_active ? 'Disable' : 'Enable' }}</span>
                                </button>
                                <span v-else class="text-gray-400 text-xs">-</span>
                            </td>
                        </tr>
                        <tr v-if="paginatedOfflineData.data.length === 0">
                            <td colspan="10" class="text-center py-16">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center border border-gray-100">
                                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm font-semibold">Tidak ada riwayat voucher offline ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Offline -->
            <div v-if="paginatedOfflineData.last_page > 1" class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <span class="text-xs text-gray-500">
                    Menampilkan {{ (paginatedOfflineData.current_page - 1) * perPage + 1 }} - {{ Math.min(paginatedOfflineData.current_page * perPage, paginatedOfflineData.total) }} dari {{ paginatedOfflineData.total }} data
                </span>
                <div class="flex items-center gap-1">
                    <button 
                        @click="currentOfflinePage--" 
                        :disabled="currentOfflinePage <= 1"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span class="px-2 text-xs font-semibold text-gray-700">Hal {{ currentOfflinePage }} / {{ paginatedOfflineData.last_page }}</span>
                    <button 
                        @click="currentOfflinePage++" 
                        :disabled="currentOfflinePage >= paginatedOfflineData.last_page"
                        class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

    </component>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';

const page = usePage();
const layoutComponent = computed(() => {
    return page.props.auth?.user?.role === 'reseller' || page.props.auth?.user?.role === 'customer' 
        ? ClientAreaLayout 
        : AppLayout;
});

const can = (permission) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    const role = (user.role || '').toLowerCase();
    if (['admin', 'super admin', 'superadmin'].includes(role)) return true;
    const perms = Array.isArray(user.permissions) ? user.permissions : Object.values(user.permissions || {});
    return perms.includes(permission);
};

const props = defineProps({
    tab: { type: String, default: 'online' },
    onlineUsers: { type: Array, default: () => [] },
    offlineUsers: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ online_count: 0, offline_count: 0, total_count: 0, total_download: 0, total_upload: 0 }) },
    profiles: { type: Array, default: () => [] },
    resellers: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const activeTab = ref(props.tab || props.filters?.tab || 'online');
const search = ref(props.filters?.search || '');
const profileFilter = ref(props.filters?.profile_id || '');
const resellerFilter = ref(props.filters?.reseller_id || '');
const areaFilter = ref(props.filters?.area_id || '');

const refreshing = ref(false);
const kicking = ref(null);
const toggling = ref(null);

const perPage = 15;
const currentOnlinePage = ref(1);
const currentOfflinePage = ref(1);

const switchTab = (tabName) => {
    activeTab.value = tabName;
    doFilter();
};

const doFilter = () => {
    router.get(route('vouchers.online'), {
        tab: activeTab.value,
        search: search.value,
        profile_id: profileFilter.value,
        reseller_id: resellerFilter.value,
        area_id: areaFilter.value,
    }, { preserveState: true, replace: true });
};

const resetFilter = () => {
    search.value = '';
    profileFilter.value = '';
    resellerFilter.value = '';
    areaFilter.value = '';
    doFilter();
};

// Client-side pagination & filter matching
const paginatedOnlineData = computed(() => {
    const list = props.onlineUsers || [];
    const start = (currentOnlinePage.value - 1) * perPage;
    return {
        data: list.slice(start, start + perPage),
        current_page: currentOnlinePage.value,
        last_page: Math.max(1, Math.ceil(list.length / perPage)),
        total: list.length,
    };
});

const paginatedOfflineData = computed(() => {
    const list = props.offlineUsers || [];
    const start = (currentOfflinePage.value - 1) * perPage;
    return {
        data: list.slice(start, start + perPage),
        current_page: currentOfflinePage.value,
        last_page: Math.max(1, Math.ceil(list.length / perPage)),
        total: list.length,
    };
});

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['onlineUsers', 'offlineUsers', 'stats'],
        onFinish: () => (refreshing.value = false),
    });
}

// Auto-refresh tiap 30 detik (dilewati jika dokumen sedang tidak aktif atau sedang kick/toggle)
const AUTO_REFRESH_MS = 30000;
let autoTimer = null;
onMounted(() => {
    autoTimer = setInterval(() => {
        if (document.hidden || refreshing.value || kicking.value || toggling.value) return;
        router.reload({ only: ['onlineUsers', 'offlineUsers', 'stats'], preserveScroll: true, preserveState: true });
    }, AUTO_REFRESH_MS);
});
onUnmounted(() => clearInterval(autoTimer));

function toggleStatus(row) {
    const voucherId = row.voucher_id || row.id;
    if (!voucherId) return;
    const action = row.is_active ? 'Nonaktifkan' : 'Aktifkan';
    
    Swal.fire({
        title: `${action} Voucher?`,
        text: `Voucher ${row.code} akan di-${action.toLowerCase()}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Ya, ${action}!`,
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            toggling.value = voucherId;
            router.post(`/vouchers/${voucherId}/toggle-status`, {}, {
                preserveScroll: true,
                onFinish: () => (toggling.value = null),
                onSuccess: () => {
                    Swal.fire('Berhasil', `Voucher ${row.code} berhasil di-${action.toLowerCase()}.`, 'success');
                }
            });
        }
    });
}

function kickSession(row) {
    if (!row.username) return;
    Swal.fire({
        title: 'Kick Sesi Online?',
        text: `Putus koneksi aktif untuk voucher ${row.code} (${row.username})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Ya, Putus Sesi!',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            kicking.value = row.username;
            router.post('/radius/online-users/disconnect', { username: row.username }, {
                preserveScroll: true,
                onFinish: () => (kicking.value = null),
                onSuccess: () => {
                    Swal.fire('Selesai', `Sesi ${row.code} berhasil diputus.`, 'success');
                    refresh();
                }
            });
        }
    });
}

function formatUptime(seconds) {
    if (!seconds || seconds <= 0) return '0s';
    const d = Math.floor(seconds / (3600 * 24));
    const h = Math.floor((seconds % (3600 * 24)) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    
    let parts = [];
    if (d > 0) parts.push(`${d}h`);
    if (h > 0) parts.push(`${h}j`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 || parts.length === 0) parts.push(`${s}d`);
    
    return parts.join(' ');
}

function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDate(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
}
</script>
