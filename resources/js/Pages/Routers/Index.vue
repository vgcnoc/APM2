<template>
    <AppLayout title="Data Router Mikrotik">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans px-4 sm:px-6 lg:px-8 py-6">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">✅</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <button @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-700 text-xs">✕</button>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚠️</span>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <button @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-700 text-xs">✕</button>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2.5 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                        </span>
                        <span>Data Router Mikrotik</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Kelola data router Mikrotik, coverage area, port akses API/Winbox, dan integrasi FreeRADIUS NAS.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link 
                        href="/radius/nas" 
                        class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Kelola FreeRADIUS (NAS)</span>
                    </Link>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Router -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Router</span>
                        <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ stats?.total_routers || routers.total || 0 }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Router Mikrotik terdaftar</div>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                        </svg>
                    </div>
                </div>

                <!-- Router Online -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Router Online</span>
                        <div class="text-2xl font-black text-emerald-700 mt-1 font-mono">
                            {{ countOnlineRouters }}
                            <span class="text-xs font-normal text-slate-400">/ {{ routers.data?.length || 0 }}</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">API terhubung &amp; responsif</div>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Router Offline -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Router Offline</span>
                        <div class="text-2xl font-black text-rose-700 mt-1 font-mono">
                            {{ countOfflineRouters }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Periksa rute / VPN tunnel</div>
                    </div>
                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Area Coverage -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-600">Wilayah Terkover</span>
                        <div class="text-2xl font-black text-sky-700 mt-1 font-mono">
                            {{ stats?.covered_areas || 0 }}
                            <span class="text-xs font-normal text-slate-400">/ {{ stats?.total_areas || areas.length || 0 }}</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Area ber-router aktif</div>
                    </div>
                    <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Toolbar (Search, Filter Area, Refresh All, Tambah Router) -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-200/80 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-80">
                        <input 
                            v-model="searchQuery" 
                            @input="onSearchInput"
                            type="text" 
                            placeholder="Cari router, IP, area, PIC..." 
                            class="w-full pl-9 pr-8 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium text-slate-800"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button 
                            v-if="searchQuery" 
                            @click="clearSearch" 
                            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold"
                            title="Reset pencarian"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Filter Area Dropdown -->
                    <div class="w-full sm:w-56">
                        <select 
                            v-model="filterArea" 
                            @change="applyFilter" 
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all"
                        >
                            <option value="">Semua Area Coverage</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                📍 {{ area.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <!-- Tombol Cek Semua Koneksi -->
                    <button 
                        @click="checkConnections(true)" 
                        :disabled="isPingingAll"
                        class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 disabled:opacity-60 cursor-pointer shadow-2xs"
                        title="Uji ulang status API semua router"
                    >
                        <svg 
                            class="w-4 h-4 text-slate-600" 
                            :class="{ 'animate-spin': isPingingAll }"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="hidden sm:inline">{{ isPingingAll ? 'Memeriksa...' : 'Cek Koneksi' }}</span>
                    </button>

                    <!-- Tombol Tambah Router -->
                    <button 
                        @click="openCreateModal" 
                        class="px-4 sm:px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-extrabold shadow-sm hover:shadow transition-all flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Data Router</span>
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80">
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Nama Router &amp; IP</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Area Coverage</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Port API</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Port Winbox</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">PIC / Penanggung Jawab</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Status Koneksi</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="routerItem in routers.data" :key="routerItem.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Nama Router & IP -->
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs mt-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-slate-900 font-mono tracking-tight">{{ routerItem.name }}</div>
                                            <div class="text-[11px] text-slate-500 font-mono flex items-center gap-1.5 mt-0.5">
                                                <span 
                                                    v-if="routerItem.nas?.vpn_ip" 
                                                    class="inline-flex items-center gap-1 text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded text-[10px] font-bold border border-indigo-100"
                                                    title="IP VPN Tunnel"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                    VPN: {{ routerItem.nas.vpn_ip }}
                                                </span>
                                                <span 
                                                    v-else-if="routerItem.nas?.nasname" 
                                                    class="inline-flex items-center gap-1 text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded text-[10px] font-bold"
                                                    title="IP Sumber NAS"
                                                >
                                                    IP: {{ routerItem.nas.nasname }}
                                                </span>
                                                <span v-else class="text-slate-400 italic text-[10px]">
                                                    IP belum diset
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Area Coverage -->
                                <td class="px-6 py-4">
                                    <div v-if="routerItem.area" class="space-y-1">
                                        <Link 
                                            :href="`/settings/areas?search=${encodeURIComponent(routerItem.area.name)}`" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/80 shadow-2xs hover:bg-indigo-100 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>{{ routerItem.area.name }}</span>
                                        </Link>
                                        <div class="text-[11px] text-slate-400 font-medium pl-0.5">
                                            {{ routerItem.area.customers_count || 0 }} Pelanggan • {{ routerItem.area.odps_count || 0 }} ODP
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic">
                                        - Belum Ditentukan -
                                    </span>
                                </td>

                                <!-- Port API -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200/60">
                                        {{ routerItem.api_port || 8728 }}
                                    </span>
                                </td>

                                <!-- Port Winbox -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200/60">
                                        {{ routerItem.winbox_port || 8291 }}
                                    </span>
                                </td>

                                <!-- PIC -->
                                <td class="px-6 py-4">
                                    <div v-if="routerItem.pic" class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-xs font-extrabold flex items-center justify-center shrink-0">
                                            {{ routerItem.pic.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900 leading-tight">{{ routerItem.pic.name }}</div>
                                            <div v-if="routerItem.pic.role" class="text-[10px] text-slate-400 capitalize">{{ routerItem.pic.role }}</div>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 font-medium">-</span>
                                </td>

                                <!-- Status Koneksi -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <span 
                                                v-if="connectionStatuses[routerItem.id] === 'online'" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Online
                                            </span>
                                            <span 
                                                v-else-if="connectionStatuses[routerItem.id] === 'offline'" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Offline
                                            </span>
                                            <span 
                                                v-else 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200"
                                            >
                                                <svg class="animate-spin h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Memeriksa...
                                            </span>
                                        </div>

                                        <!-- Refresh single connection -->
                                        <button 
                                            @click="pingSingleRouter(routerItem.id, true)"
                                            :disabled="connectionStatuses[routerItem.id] === 'loading'"
                                            class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-indigo-600 transition-colors disabled:opacity-40"
                                            title="Uji ulang koneksi ke router ini"
                                        >
                                            <svg 
                                                class="w-3.5 h-3.5" 
                                                :class="{ 'animate-spin': connectionStatuses[routerItem.id] === 'loading' }" 
                                                fill="none" 
                                                stroke="currentColor" 
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p 
                                        v-if="connectionDetails[routerItem.id]?.message" 
                                        class="mt-1 text-[11px] text-slate-500 max-w-56 leading-tight"
                                    >
                                        {{ connectionDetails[routerItem.id].message }}
                                    </p>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Script Button -->
                                        <button 
                                            @click="openScriptModal(routerItem)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/60 shadow-2xs transition-colors flex items-center gap-1 cursor-pointer"
                                            title="Lihat script konfigurasi MikroTik"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                            </svg>
                                            <span>Script</span>
                                        </button>

                                        <!-- Edit Button -->
                                        <button 
                                            @click="openEditModal(routerItem)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 shadow-2xs transition-colors flex items-center gap-1 cursor-pointer"
                                            title="Edit data router"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>

                                        <!-- Hapus Button -->
                                        <button 
                                            @click="confirmDeleteRouter(routerItem)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 shadow-2xs transition-colors flex items-center gap-1 cursor-pointer"
                                            title="Hapus router"
                                        >
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty Row -->
                            <tr v-if="!routers.data || routers.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mx-auto">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                                            </svg>
                                        </div>
                                        <div class="text-sm font-bold text-slate-800">Tidak ada data Router</div>
                                        <p class="text-xs text-slate-400">
                                            {{ searchQuery || filterArea ? 'Tidak ada router yang sesuai dengan kriteria pencarian / filter Anda.' : 'Belum ada router Mikrotik yang ditambahkan.' }}
                                        </p>
                                        <button 
                                            v-if="searchQuery || filterArea" 
                                            @click="resetAllFilters" 
                                            class="mt-2 text-xs text-indigo-600 font-bold hover:underline"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500" v-if="routers.total > 0">
                    <div>
                        Menampilkan <span class="font-bold text-slate-700">{{ routers.from || 0 }}</span> - <span class="font-bold text-slate-700">{{ routers.to || 0 }}</span> dari <span class="font-bold text-slate-700">{{ routers.total }}</span> Router
                    </div>
                    <div class="flex items-center gap-1.5" v-if="routers.links && routers.links.length > 3">
                        <template v-for="(link, idx) in routers.links" :key="idx">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl text-xs font-bold transition-colors" 
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                preserve-state
                            />
                            <span 
                                v-else 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl bg-slate-50 text-slate-400 border-slate-200 text-xs cursor-not-allowed"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit Router -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-auto overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">{{ isEditing ? 'Edit Data Router' : 'Tambah Data Router Mikrotik' }}</h3>
                            <p class="text-xs text-slate-500">Konfigurasi router Mikrotik, coverage area, port API &amp; Winbox.</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center text-xs font-bold transition-colors">
                        ✕
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-5 max-h-[72vh] overflow-y-auto">
                        <!-- Informasi Router & Area -->
                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 space-y-4">
                            <h4 class="font-extrabold text-xs uppercase tracking-wider text-indigo-700 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Informasi Router &amp; Area</span>
                            </h4>

                            <div class="bg-amber-50 text-amber-800 p-3.5 rounded-xl text-xs border border-amber-200 flex items-start gap-2.5">
                                <span class="text-amber-500 text-base shrink-0 leading-none">⚠️</span>
                                <div>
                                    <span class="font-extrabold">Penting:</span>
                                    <span> Jangan mengubah Identity Mikrotik setelah router dibuat agar relasi RADIUS dan API tetap terhubung.</span>
                                </div>
                            </div>

                            <!-- Read-only NAS info card when editing -->
                            <div v-if="isEditing && editingRouter?.nas" class="bg-indigo-50/70 border border-indigo-100 rounded-xl p-3.5 flex items-center justify-between">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Alamat IP / VPN NAS Terkait</p>
                                    <p class="text-xs font-mono font-bold text-indigo-950 mt-0.5">
                                        {{ editingRouter.nas.vpn_ip ? `${editingRouter.nas.vpn_ip} (OpenVPN Tunnel)` : (editingRouter.nas.nasname || '-') }}
                                    </p>
                                </div>
                                <span class="text-[11px] bg-indigo-100 text-indigo-800 font-bold px-2.5 py-1 rounded-full border border-indigo-200/60 font-mono">
                                    NAS #{{ editingRouter.nas_id }}
                                </span>
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Nama Router -->
                                <div>
                                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Nama Router <span class="text-rose-500">*Wajib</span>
                                    </label>
                                    <input 
                                        v-model="form.name" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase text-slate-900" 
                                        placeholder="Contoh: MIKROTIK-PUSAT, CCR1009, RB750GR3..." 
                                        required 
                                    />
                                    <p v-if="vpnEnabled && !isEditing" class="text-[11px] text-indigo-600 font-medium mt-1.5">
                                        ✨ FreeRADIUS NAS akan otomatis dialokasikan IP VPN unik tunnel oleh server.
                                    </p>
                                    <div v-if="!vpnEnabled && !isEditing" class="mt-3">
                                        <label class="block text-xs font-bold text-slate-700 mb-1">IP Sumber NAS (Mikrotik)</label>
                                        <input 
                                            v-model="form.nasname" 
                                            required 
                                            placeholder="Contoh: 192.168.1.1 atau IP Publik Mikrotik" 
                                            class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-mono text-slate-900" 
                                        />
                                    </div>
                                    <p v-if="form.errors.nasname" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.nasname }}</p>
                                    <p v-if="form.errors.name" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.name }}</p>
                                </div>

                                <!-- Kolom Area Coverage -->
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                                            Area Coverage / Wilayah
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="toggleCustomArea" 
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-extrabold flex items-center gap-1 transition-colors"
                                        >
                                            <span v-if="!isCustomArea">+ Buat Area Baru</span>
                                            <span v-else>← Pilih dari Daftar Area</span>
                                        </button>
                                    </div>

                                    <!-- Mode 1: Pilih dari daftar area -->
                                    <div v-if="!isCustomArea">
                                        <select 
                                            v-model="form.area_id" 
                                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option :value="null">-- Pilih Area / Kelompok Wilayah --</option>
                                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                                📍 {{ area.name }}
                                            </option>
                                        </select>
                                        <p class="text-[11px] text-slate-400 mt-1">Mengelompokkan router ke area operasional untuk penanganan pelanggan di area tersebut.</p>
                                    </div>

                                    <!-- Mode 2: Ketik nama area baru -->
                                    <div v-else class="space-y-1.5">
                                        <div class="relative">
                                            <input 
                                                v-model="form.new_area_name" 
                                                type="text" 
                                                class="w-full pl-9 pr-4 py-2.5 bg-white border border-indigo-300 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900" 
                                                placeholder="Ketik nama area baru (contoh: Area Barat, Desa Sukamaju, dll)..." 
                                            />
                                            <svg class="w-4 h-4 text-indigo-500 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </div>
                                        <p class="text-[11px] text-indigo-600 font-medium">Area baru ini akan otomatis dibuat dan langsung dihubungkan ke router ini.</p>
                                    </div>
                                    <p v-if="form.errors.area_id" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.area_id }}</p>
                                    <p v-if="form.errors.new_area_name" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.new_area_name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Port Akses -->
                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 space-y-4">
                            <h4 class="font-extrabold text-xs uppercase tracking-wider text-indigo-700 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Port Akses Mikrotik</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Port API</label>
                                    <input 
                                        v-model="form.api_port" 
                                        type="number" 
                                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                        placeholder="Default: 8728" 
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">Default: 8728. Digunakan untuk monitoring &amp; isolir.</p>
                                    <p v-if="form.errors.api_port" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.api_port }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Port Winbox</label>
                                    <input 
                                        v-model="form.winbox_port" 
                                        type="number" 
                                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                        placeholder="Default: 8291" 
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">Default: 8291. Port remote Winbox.</p>
                                    <p v-if="form.errors.winbox_port" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.winbox_port }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Hak Akses / PIC -->
                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 space-y-4">
                            <h4 class="font-extrabold text-xs uppercase tracking-wider text-indigo-700 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Penanggung Jawab / PIC</span>
                            </h4>
                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Assign PIC / Teknisi / Investor</label>
                                <select 
                                    v-model="form.pic_id" 
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option :value="null">-- Tidak Ada / Belum Ditugaskan --</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} {{ user.role ? `(${user.role})` : '' }}
                                    </option>
                                </select>
                                <p v-if="form.errors.pic_id" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.pic_id }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50/80 px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-2xs"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-6 py-2.5 text-xs font-extrabold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-sm hover:shadow flex items-center gap-2 cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Data Router' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Script MikroTik -->
        <div v-if="isScriptModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl my-auto overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">Script Konfigurasi MikroTik</h3>
                            <p class="text-xs text-slate-500">Router: <span class="font-mono font-bold text-slate-800">{{ activeScriptRouter?.name }}</span></p>
                        </div>
                    </div>
                    <button @click="closeScriptModal" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center text-xs font-bold transition-colors">
                        ✕
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <!-- Info Alert -->
                    <div class="bg-indigo-50/70 border border-indigo-200 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="text-xs text-indigo-900 space-y-0.5">
                            <div class="font-extrabold flex items-center gap-1.5">
                                <span>💡</span>
                                <span>Instruksi Paste Script ke Terminal MikroTik:</span>
                            </div>
                            <p class="text-indigo-800">
                                Buka Winbox &gt; Terminal &gt; Paste seluruh isi script di bawah ini. Pastikan koneksi internet router aktif.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <label class="text-xs font-bold text-slate-700">Versi RouterOS:</label>
                            <select 
                                v-model="scriptOsVersion" 
                                @change="buildRouterScript" 
                                class="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-xl font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="v7">RouterOS v7 (Rekomendasi)</option>
                                <option value="v6">RouterOS v6</option>
                            </select>
                        </div>
                    </div>

                    <!-- Credential Quick View -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-200/70 font-mono text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block font-sans">Host / IP API</span>
                            <span class="font-bold text-indigo-700">{{ activeScriptRouter?.nas?.vpn_ip || activeScriptRouter?.nas?.nasname || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block font-sans">Port API</span>
                            <span class="font-bold text-slate-800">{{ activeScriptRouter?.api_port || 8728 }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block font-sans">Username API</span>
                            <span class="font-bold text-slate-800">{{ activeScriptRouter?.nas?.api_user || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block font-sans">Secret RADIUS</span>
                            <span class="font-bold text-slate-800">{{ activeScriptRouter?.nas?.secret ? '••••••••' : '-' }}</span>
                        </div>
                    </div>

                    <!-- Script Textarea -->
                    <div class="relative">
                        <textarea 
                            :value="generatedRouterScript" 
                            readonly 
                            rows="14" 
                            class="w-full bg-slate-900 font-mono text-emerald-400 text-xs border border-slate-800 rounded-2xl p-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 selection:bg-indigo-600 selection:text-white"
                        ></textarea>
                    </div>
                </div>

                <div class="bg-slate-50/80 px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-3">
                    <Link 
                        :href="`/radius/nas`" 
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1"
                    >
                        <span>Buka Pengaturan Lengkap FreeRADIUS (NAS) →</span>
                    </Link>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="closeScriptModal" 
                            class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-2xs"
                        >
                            Tutup
                        </button>
                        <button 
                            @click="copyGeneratedScript" 
                            class="px-5 py-2 text-xs font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-sm hover:shadow flex items-center gap-2 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>{{ isScriptCopied ? 'Tersalin ke Clipboard! ✅' : 'Salin Seluruh Script' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    routers: Object,
    users: Array,
    areas: Array,
    stats: Object,
    filters: Object,
    vpnEnabled: Boolean,
    serverIp: String,
    vpn: Object,
    clientPool: Object,
    isolirUrl: String,
});

// Search & Filter State
const searchQuery = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
let searchTimer = null;

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilter();
    }, 350);
}

function clearSearch() {
    searchQuery.value = '';
    applyFilter();
}

function resetAllFilters() {
    searchQuery.value = '';
    filterArea.value = '';
    applyFilter();
}

function applyFilter() {
    router.get('/routers', {
        search: searchQuery.value || undefined,
        area_id: filterArea.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const editingRouter = ref(null);
const isCustomArea = ref(false);

const form = useForm({
    name: '',
    area_id: null,
    new_area_name: '',
    api_port: '',
    winbox_port: '',
    pic_id: null,
    nasname: '',
});

function toggleCustomArea() {
    isCustomArea.value = !isCustomArea.value;
    if (!isCustomArea.value) {
        form.new_area_name = '';
    } else {
        form.area_id = null;
    }
}

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    editingRouter.value = null;
    isCustomArea.value = false;
    form.reset();
    form.api_port = 8728;
    form.winbox_port = 8291;
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(routerData) {
    isEditing.value = true;
    editId.value = routerData.id;
    editingRouter.value = routerData;
    isCustomArea.value = false;
    form.name = routerData.name;
    form.area_id = routerData.area_id || null;
    form.new_area_name = '';
    form.api_port = routerData.api_port || 8728;
    form.winbox_port = routerData.winbox_port || 8291;
    form.pic_id = routerData.pic_id || null;
    form.clearErrors();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => {
        form.reset();
        isCustomArea.value = false;
        editingRouter.value = null;
    }, 300);
}

function submit() {
    if (isEditing.value) {
        form.post(`/routers/${editId.value}/update`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/routers', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

// Safe Delete with SweetAlert2
function confirmDeleteRouter(routerData) {
    const areaInfo = routerData.area 
        ? `<div class="mt-2.5 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs text-left">
            <span class="font-extrabold block mb-0.5">⚠️ Terkait Area: ${routerData.area.name}</span>
            Area ini memiliki <strong>${routerData.area.customers_count || 0} pelanggan</strong> dan <strong>${routerData.area.odps_count || 0} ODP</strong>. Menghapus router ini akan menghapus akun FreeRADIUS NAS dan memutuskan komunikasi API untuk isolir pelanggan.
           </div>` 
        : '';

    Swal.fire({
        title: `Hapus Router ${routerData.name}?`,
        html: `
            <div class="text-xs text-slate-600">
                <p>Data Router dan akun FreeRADIUS NAS terkait akan dihapus secara permanen dari sistem.</p>
                ${areaInfo}
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Router',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl',
            confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
            cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/routers/${routerData.id}/delete`, {}, {
                preserveScroll: true,
            });
        }
    });
}

// Connection Testing
const connectionStatuses = ref({});
const connectionDetails = ref({});
const isPingingAll = ref(false);

const countOnlineRouters = computed(() => {
    return Object.values(connectionStatuses.value).filter(s => s === 'online').length;
});

const countOfflineRouters = computed(() => {
    return Object.values(connectionStatuses.value).filter(s => s === 'offline').length;
});

function pingSingleRouter(routerId, forceRefresh = false) {
    connectionStatuses.value[routerId] = 'loading';
    const url = `/routers/${routerId}/ping${forceRefresh ? '?refresh=1' : ''}`;
    axios.get(url)
        .then(res => {
            connectionStatuses.value[routerId] = res.data.status || 'offline';
            connectionDetails.value[routerId] = res.data;
        })
        .catch(err => {
            connectionStatuses.value[routerId] = 'offline';
            connectionDetails.value[routerId] = {
                status: 'offline',
                message: err?.response?.data?.message || 'Gagal menghubungi server.',
            };
        });
}

function checkConnections(forceRefresh = false) {
    if (!props.routers?.data || props.routers.data.length === 0) return;
    isPingingAll.value = true;
    let completed = 0;
    const total = props.routers.data.length;

    props.routers.data.forEach(r => {
        connectionStatuses.value[r.id] = 'loading';
        const url = `/routers/${r.id}/ping${forceRefresh ? '?refresh=1' : ''}`;
        axios.get(url)
            .then(res => {
                connectionStatuses.value[r.id] = res.data.status || 'offline';
                connectionDetails.value[r.id] = res.data;
            })
            .catch(err => {
                connectionStatuses.value[r.id] = 'offline';
                connectionDetails.value[r.id] = {
                    status: 'offline',
                    message: err?.response?.data?.message || 'Gagal menghubungi server.',
                };
            })
            .finally(() => {
                completed++;
                if (completed >= total) {
                    isPingingAll.value = false;
                }
            });
    });
}

// Script MikroTik Generation
const isScriptModalOpen = ref(false);
const activeScriptRouter = ref(null);
const scriptOsVersion = ref('v7');
const generatedRouterScript = ref('');
const isScriptCopied = ref(false);

function rosEscape(src) {
    if (!src) return '';
    return String(src)
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\$/g, '\\$')
        .replace(/\r?\n/g, '\\r\\n');
}

function buildRouterScript() {
    if (!activeScriptRouter.value) {
        generatedRouterScript.value = '';
        return;
    }

    const routerItem = activeScriptRouter.value;
    const nasItem = routerItem.nas || {};
    const v6 = scriptOsVersion.value === 'v6';
    const publicIp = props.serverIp || window.location.hostname || '157.66.140.17';
    const vpnOn = props.vpn?.enabled && nasItem.vpn_user && nasItem.vpn_ip;
    const radiusIp = vpnOn ? (props.vpn?.gateway || '10.254.0.1') : publicIp;
    const radiusHostMask = radiusIp.includes(':') ? 128 : 32;
    const endpoints = (props.vpn?.endpoints?.length ? props.vpn.endpoints : [publicIp]);
    const backup = props.vpn?.backup || null;
    const backupOn = Boolean(vpnOn && backup?.enabled && backup?.l2tp_enabled && backup?.endpoint && backup?.ipsec_secret);
    const pool = props.clientPool || { name: 'VIZBILLPOOL', network: '10.200.192.0/20', local_address: '10.200.192.1', ranges: '10.200.192.2-10.200.207.254' };
    const isolirUrl = props.isolirUrl || 'isolir.apm2.com';
    const secret = rosEscape(nasItem.secret || '');
    const name = (routerItem.name || nasItem.shortname || 'VIZBILL-ROUTER').replace(/"/g, '');
    const apiUser = rosEscape(nasItem.api_user || 'VIZBILLAPI');
    const apiPass = rosEscape(nasItem.api_password || '');
    const apiPort = routerItem.api_port || 8728;
    const coaPort = nasItem.coa_port || 3799;

    const profiles = [
        { name: 'VIZBILLRADIUS', cookie: '1w', refresh: '1m', keepalive: '2m' },
        { name: 'VIZBILLRADIUS-Short', cookie: '1w', refresh: '1m', keepalive: '2m' },
        { name: 'VIZBILLRADIUS-Long', cookie: '4w2d', refresh: '6d10m', keepalive: '6d' },
    ];

    const L = [];
    L.push(`# COPY PASTE ALL SCRIPTS TO THE NEW MIKROTIK TERMINAL`);
    L.push(`# VIZBILL RADIUS - RouterOS ${v6 ? 'v6' : 'v7'} - Router: ${name}`);
    L.push(`#############################################################`);
    L.push(`/system identity set name="${name}";`);
    L.push(`/ip dns set allow-remote-requests=yes;`);
    L.push(``);

    L.push(`### 1. USER API (MONITORING & DISABLE/ENABLE KONEKSI PELANGGAN) ###`);
    L.push(`:if ([:len [/user group find name="VIZBILL-API"]] = 0) do={ /user group add name="VIZBILL-API" policy=read,write,api,test } else={ /user group set [find name="VIZBILL-API"] policy=read,write,api,test };`);
    L.push(`/user rem [find comment~"APM2"];`);
    L.push(`/user rem [find comment~"VIZBILL"];`);
    L.push(`/user add name="${apiUser}" password="${apiPass}" group="VIZBILL-API" address=${radiusIp}/${radiusHostMask} comment="USER FOR VIZBILL BILLING API (DON'T CHANGE IT)";`);
    L.push(`/ip service set api disabled=no port=${apiPort};`);
    L.push(``);

    L.push(`### 2. RADIUS ###`);
    L.push(`/ppp aaa set use-radius=yes accounting=yes interim-update=1m;`);
    L.push(`/radius incoming set accept=yes port=${coaPort};`);
    L.push(`/radius rem [find comment~"APM2RADIUS"];`);
    L.push(`/radius rem [find comment~"VIZBILLRADIUS"];`);
    L.push(`/radius add address=${radiusIp}${vpnOn ? ` src-address=${nasItem.vpn_ip}` : ''} comment="VIZBILLRADIUS" authentication-port=1812 accounting-port=1813 secret="${secret}" service=ppp,hotspot timeout=3s;`);
    L.push(``);

    L.push(`### 3. IP POOL PELANGGAN ###`);
    L.push(`:if ([:len [/ip pool find name="${pool.name}"]] = 0) do={ /ip pool add name=${pool.name} ranges=${pool.ranges} comment="Network : ${pool.network}" } else={ /ip pool set [find name="${pool.name}"] ranges=${pool.ranges} comment="Network : ${pool.network}" };`);
    L.push(``);

    L.push(`### 4. HOTSPOT PROFILE & USER PROFILE ###`);
    L.push(`/ip hotspot profile set login-by=http-chap,http-pap,cookie,mac-cookie http-cookie-lifetime=4w2d use-radius=yes radius-accounting=yes [find];`);
    ['APM2RADIUS', 'APM2RADIUS-Short', 'APM2RADIUS-Long'].forEach(p => L.push(`/ip hotspot user profile remove [find name="${p}"];`));
    profiles.forEach(p => L.push(`/ip hotspot user profile remove [find name="${p.name}"];`));
    profiles.forEach(p => L.push(`/ip hotspot user profile add name=${p.name} mac-cookie-timeout=${p.cookie} status-autorefresh=${p.refresh} keepalive-timeout=${p.keepalive} shared-users=1;`));
    L.push(`/ip hotspot user profile set [find default=yes] keepalive-timeout=2m mac-cookie-timeout=1w shared-users=1 status-autorefresh=1m;`);
    L.push(``);

    L.push(`### 5. PPP PROFILE ###`);
    ['APM2RADIUS', 'APM2RADIUS-Short', 'APM2RADIUS-Long'].forEach(p => L.push(`/ppp profile remove [find name="${p}"];`));
    profiles.forEach(p => L.push(`/ppp profile remove [find name="${p.name}"];`));
    profiles.forEach(p => L.push(`/ppp profile add name=${p.name} insert-queue-before=first local-address=${pool.local_address} remote-address=${pool.name} only-one=default;`));
    L.push(``);

    L.push(`### 6. SISTEM ISOLIR (WEB PROXY REDIRECT) ###`);
    L.push(`/ip firewall address-list rem [find list=APM2BYPASS];`);
    L.push(`/ip firewall address-list rem [find list=VIZBILLBYPASS];`);
    L.push(`/ip firewall address-list add address=${publicIp} comment="DEFAULT BY VIZBILL (DONT CHANGE IT)" list=VIZBILLBYPASS;`);
    L.push(`/ip proxy set enabled=yes port=8097;`);
    L.push(`/ip proxy access rem [find comment~"APM2"];`);
    L.push(`/ip proxy access rem [find comment~"VIZBILL"];`);
    if (v6) {
        L.push(`/ip proxy access add action=deny redirect-to="${isolirUrl}" comment="DENY OTHER THAN THE ISOLIR IP THAT GOES TO THE WEB PROXY BY VIZBILL" dst-address=!${publicIp} local-port=8097;`);
    } else {
        L.push(`/ip proxy access add action=redirect action-data="${isolirUrl}" comment="DENY OTHER THAN THE ISOLIR IP THAT GOES TO THE WEB PROXY BY VIZBILL" dst-address=!${publicIp} local-port=8097;`);
    }
    L.push(`/ip firewall nat remove [find comment="APM2ISOLIR"];`);
    L.push(`/ip firewall nat remove [find comment="VIZBILLISOLIR"];`);
    L.push(`/ip firewall nat add action=redirect chain=dstnat comment=VIZBILLISOLIR dst-address-list=!VIZBILLBYPASS dst-port=80,443 protocol=tcp src-address-list=VIZBILLISOLIR to-ports=8097;`);
    L.push(`/ip firewall filter remove [find comment="APM2ISOLIR"];`);
    L.push(`/ip firewall filter remove [find comment="VIZBILLISOLIR"];`);
    L.push(`/ip firewall filter add action=drop chain=forward comment=VIZBILLISOLIR dst-address=!${publicIp} dst-port=!53,5353 protocol=udp src-address-list=VIZBILLISOLIR;`);
    L.push(`/ip firewall filter add action=drop chain=forward comment=VIZBILLISOLIR dst-address=!${publicIp} protocol=tcp src-address-list=VIZBILLISOLIR;`);
    L.push(``);

    if (vpnOn) {
        L.push(`### 7. VPN OPENVPN UTAMA${backupOn ? ' + L2TP/IPSEC CADANGAN' : ''} KE SERVER VIZBILL ###`);
        L.push(`/system scheduler rem [find name~"apm2failovervpn"];`);
        L.push(`/system scheduler rem [find name~"vizbillfailovervpn"];`);
        L.push(`/interface l2tp-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface sstp-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface ovpn-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface l2tp-client remove [find name~"VIZBILL-VPN"];`);
        L.push(`/interface sstp-client remove [find name~"VIZBILL-VPN"];`);
        L.push(`/interface ovpn-client remove [find name~"VIZBILL-VPN"];`);
        L.push(`/ppp profile remove [find name="APM2VPN"];`);
        L.push(`/ppp profile remove [find name="VIZBILLVPN"];`);
        L.push(`/ppp profile add name=VIZBILLVPN change-tcp-mss=yes only-one=default use-encryption=no comment="DEFAULT BY VIZBILL (DON'T CHANGE IT)";`);
        endpoints.forEach((ep, i) => {
            L.push(`/interface ovpn-client add disabled=${i === 0 ? 'no' : 'yes'} connect-to=${ep} port=1195 name="VIZBILL-VPN-${i + 1}" profile=VIZBILLVPN user="${nasItem.vpn_user}" password="${nasItem.vpn_password}" add-default-route=no cipher=aes256 auth=sha1 certificate=none comment="OpenVPN NAS billing; IP tunnel: ${nasItem.vpn_ip}";`);
        });
        if (backupOn) {
            L.push(`/interface l2tp-client add disabled=yes connect-to=${backup.endpoint} name="VIZBILL-VPN-BACKUP-L2TP" profile=VIZBILLVPN user="${nasItem.vpn_user}" password="${nasItem.vpn_password}" use-ipsec=yes ipsec-secret="${rosEscape(backup.ipsec_secret)}" add-default-route=no comment="L2TP/IPsec backup NAS billing; IP tunnel: ${nasItem.vpn_ip}";`);
        }
        L.push(``);

        L.push(`### 8. STATIC ROUTE RADIUS LEWAT VPN ###`);
        L.push(`/ip route remove [find comment="STATIC ROUTE BY APM2"];`);
        L.push(`/ip route remove [find comment="STATIC ROUTE BY VIZBILL"];`);
        L.push(`/ip route add dst-address=${radiusIp}/32 gateway=VIZBILL-VPN-1 comment="STATIC ROUTE BY VIZBILL";`);
        L.push(``);

        const failover = [
            `{`,
            `:global vizbillVpnIndex`,
            `:local targetIP "${radiusIp}"`,
            `:local srcIP "${nasItem.vpn_ip}"`,
            `:local vpnIfaces [/interface find name~"^VIZBILL-VPN-"]`,
            `:local vpnCount [:len $vpnIfaces]`,
            `:if ($vpnCount > 0) do={`,
            `  :if ([:typeof $vizbillVpnIndex] != "num") do={ :set vizbillVpnIndex 0 }`,
            `  :local pingResult 0`,
            `  :do { :set pingResult [/ping $targetIP count=5 src-address=$srcIP] } on-error={ :set pingResult 0 }`,
            `  :if ($pingResult = 0) do={`,
            `    :set vizbillVpnIndex (($vizbillVpnIndex + 1) % $vpnCount)`,
            `    :local selectedIface ($vpnIfaces->$vizbillVpnIndex)`,
            `    :local selectedName [/interface get $selectedIface name]`,
            `    :foreach i in=$vpnIfaces do={ /interface set $i disabled=yes }`,
            `    /interface set $selectedIface disabled=no`,
            `    /ip route set [find comment="STATIC ROUTE BY VIZBILL"] gateway=$selectedName`,
            `    :log warning ("VIZBILL VPN FAILOVER -> " . $selectedName)`,
            `  }`,
            `} else={ :log error "NO VIZBILL-VPN INTERFACE FOUND" }`,
            `}`,
        ].join('\n');

        L.push(`### 9. SCHEDULER FAILOVER VPN (CEK TIAP 10 DETIK) ###`);
        L.push(`/system scheduler add interval=10s name=vizbillfailovervpn start-time=startup policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon on-event="${rosEscape(failover)}";`);
        L.push(``);
    }

    L.push(`#############################################################`);
    L.push(`# KREDENSI AKSES API VIZBILL:`);
    L.push(`#   Host API      : ${vpnOn ? nasItem.vpn_ip + ' (IP OpenVPN)' : (nasItem.nasname || '(IP router)')}`);
    L.push(`#   Port API      : ${apiPort}`);
    L.push(`#   Username API  : ${apiUser}`);
    L.push(`#   Password API  : ${apiPass}`);

    generatedRouterScript.value = L.join('\n');
}

function openScriptModal(routerData) {
    activeScriptRouter.value = routerData;
    isScriptCopied.value = false;
    scriptOsVersion.value = 'v7';
    buildRouterScript();
    isScriptModalOpen.value = true;
}

function closeScriptModal() {
    isScriptModalOpen.value = false;
    setTimeout(() => {
        activeScriptRouter.value = null;
        generatedRouterScript.value = '';
        isScriptCopied.value = false;
    }, 200);
}

async function copyGeneratedScript() {
    if (!generatedRouterScript.value) return;
    try {
        await navigator.clipboard.writeText(generatedRouterScript.value);
        isScriptCopied.value = true;
        setTimeout(() => {
            isScriptCopied.value = false;
        }, 3000);
    } catch (e) {
        console.error('Failed to copy', e);
    }
}

// Lifecycle
onMounted(() => {
    checkConnections();
});

watch(() => props.routers?.data, () => {
    checkConnections();
});
</script>
