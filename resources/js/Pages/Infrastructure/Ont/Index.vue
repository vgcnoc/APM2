<template>
    <AppLayout title="Data ONT">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ONT / Modem</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data inventaris perangkat ONT. (Data otomatis ditambahkan dari Surat Jalan Gudang)</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Tombol Hapus Terpilih -->
                    <button 
                        v-if="selectedOnts.length > 0"
                        v-can="'network_ont_delete'"
                        @click="openDeleteModal('selected')"
                        type="button"
                        class="px-3.5 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-semibold text-sm rounded-lg border border-red-200 shadow-sm flex items-center gap-2 transition-all"
                    >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih ({{ selectedOnts.length }})</span>
                    </button>

                    <!-- Tombol Hapus Semua -->
                    <button 
                        v-can="'network_ont_delete_all'"
                        @click="openDeleteModal('all')"
                        type="button"
                        class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg shadow-sm flex items-center gap-2 transition-all"
                        title="Hapus Seluruh Data ONT"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Semua</span>
                    </button>

                    <!-- Tombol Print Card -->
                    <button 
                        v-if="selectedOnts.length > 0" 
                        v-can="'network_ont_print_card'"
                        @click="openPrintModal" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Print Card ({{ selectedOnts.length }})</span>
                    </button>

                    <!-- Tombol Pengaturan Format PPPoE -->
                    <button 
                        type="button"
                        @click="openPppoeSettingsModal" 
                        class="px-3.5 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer"
                        title="Pengaturan Format & Prefiks Generate Akun PPPoE"
                    >
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                        <span>Format PPPoE</span>
                    </button>

                    <!-- Tombol Tambah ONT -->
                    <button 
                        v-can="'network_ont_create'"
                        @click="openCreateModal" 
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah ONT</span>
                    </button>
                </div>
            </div>

            <!-- 1. Statistik Cards Inventaris ONT -->
            <div v-can="'network_ont_card_stats'" class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
                <!-- Card 1: Total Perangkat -->
                <div 
                    @click="setQuickFilter('all')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[!filterState.status && !filterState.installation_status && !filterState.tab ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/20' : 'border-gray-200/80 hover:border-blue-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-blue-600 transition-colors">Total ONT</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-100/70 text-blue-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900">{{ Number(stats?.total || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Semua perangkat</p>
                </div>

                <!-- Card 2: Siap Pasang -->
                <div 
                    @click="setQuickFilter('sudah_set')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'Sudah Set' && (!filterState.installation_status || filterState.installation_status === 'uninstalled') ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-gray-200/80 hover:border-emerald-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-emerald-600 transition-colors">Siap Pasang</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-100/70 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600">{{ Number(stats?.sudah_set || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Status: Sudah Set</p>
                </div>

                <!-- Card 3: Belum Diset -->
                <div 
                    @click="setQuickFilter('belum_set')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'Belum Set/Baru Input' && (!filterState.installation_status || filterState.installation_status === 'uninstalled') ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/20' : 'border-gray-200/80 hover:border-amber-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-amber-600 transition-colors">Belum Diset</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-100/70 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-600">{{ Number(stats?.belum_set || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Baru / Butuh Setting</p>
                </div>

                <!-- Card 4: Terpasang Pelanggan -->
                <div 
                    @click="setQuickFilter('installed')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.installation_status === 'installed' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/20' : 'border-gray-200/80 hover:border-purple-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-purple-600 transition-colors">Terpasang</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-100/70 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-purple-600">{{ Number(stats?.terpasang || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Aktif di Pelanggan</p>
                </div>

                <!-- Card 5: Gangguan LOS -->
                <div 
                    @click="setQuickFilter('los')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'los' ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/20' : 'border-gray-200/80 hover:border-rose-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-rose-600 transition-colors">Gangguan (LOS)</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-100/70 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600">{{ Number(stats?.los || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Kabel Putus / Redaman</p>
                </div>

                <!-- Card 6: Rusak / Afkir -->
                <div 
                    @click="setQuickFilter('rusak')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'rusak' ? 'border-red-500 ring-2 ring-red-500/20 bg-red-50/20' : 'border-gray-200/80 hover:border-red-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-red-600 transition-colors">Rusak</span>
                        <div class="w-8 h-8 rounded-xl bg-red-100/70 text-red-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-red-600">{{ Number(stats?.rusak || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Afkir / Butuh Servis</p>
                </div>

                <!-- Card 7: ONT Diganti -->
                <div 
                    @click="setQuickFilter('ont_diganti')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.tab === 'ont_diganti' ? 'border-orange-500 ring-2 ring-orange-500/20 bg-orange-50/20' : 'border-gray-200/80 hover:border-orange-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-orange-600 transition-colors">ONT di ganti</span>
                        <div class="w-8 h-8 rounded-xl bg-orange-100/70 text-orange-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-orange-600">{{ Number(stats?.ont_diganti || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Ditarik dari PGS/PGK</p>
                </div>

                <!-- Card 8: ONT Pengganti -->
                <div 
                    @click="setQuickFilter('ont_pengganti')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.tab === 'ont_pengganti' ? 'border-cyan-500 ring-2 ring-cyan-500/20 bg-cyan-50/20' : 'border-gray-200/80 hover:border-cyan-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-cyan-600 transition-colors">ONT pengganti</span>
                        <div class="w-8 h-8 rounded-xl bg-cyan-100/70 text-cyan-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 0 1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-cyan-600">{{ Number(stats?.ont_pengganti || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Disiapkan / Terpasang</p>
                </div>
            </div>

            <!-- 2. Smart Quick Status Tabs -->
            <div v-can="'network_ont_filter'" class="flex items-center gap-1.5 p-1.5 bg-slate-100/80 rounded-2xl mb-4 overflow-x-auto border border-slate-200/60 scrollbar-none">
                <button 
                    type="button" 
                    @click="setQuickFilter('all')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[!filterState.status && !filterState.installation_status && !filterState.tab ? 'bg-white text-gray-900 shadow-xs ring-1 ring-black/5 font-extrabold' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50']"
                >
                    <span>Semua</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="[!filterState.status && !filterState.installation_status && !filterState.tab ? 'bg-gray-100 text-gray-800' : 'bg-gray-200/70 text-gray-600']">{{ stats?.total || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('sudah_set')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'Sudah Set' && (!filterState.installation_status || filterState.installation_status === 'uninstalled') ? 'bg-white text-emerald-700 shadow-xs ring-1 ring-emerald-500/20 font-extrabold' : 'text-gray-600 hover:text-emerald-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Siap Pasang</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 font-bold">{{ stats?.sudah_set || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('belum_set')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'Belum Set/Baru Input' && (!filterState.installation_status || filterState.installation_status === 'uninstalled') ? 'bg-white text-amber-700 shadow-xs ring-1 ring-amber-500/20 font-extrabold' : 'text-gray-600 hover:text-amber-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Belum Diset</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 font-bold">{{ stats?.belum_set || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('installed')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.installation_status === 'installed' ? 'bg-white text-purple-700 shadow-xs ring-1 ring-purple-500/20 font-extrabold' : 'text-gray-600 hover:text-purple-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Terpasang Pelanggan</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-50 text-purple-700 font-bold">{{ stats?.terpasang || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('uninstalled')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.installation_status === 'uninstalled' ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-indigo-500/20 font-extrabold' : 'text-gray-600 hover:text-indigo-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span>Standby (Gudang)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-50 text-indigo-700 font-bold">{{ stats?.belum_terpasang || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('los')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'los' ? 'bg-white text-rose-700 shadow-xs ring-1 ring-rose-500/20 font-extrabold' : 'text-gray-600 hover:text-rose-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Gangguan (LOS)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 font-bold">{{ stats?.los || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('rusak')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'rusak' ? 'bg-white text-red-700 shadow-xs ring-1 ring-red-500/20 font-extrabold' : 'text-gray-600 hover:text-red-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span>Rusak</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-red-50 text-red-700 font-bold">{{ stats?.rusak || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('ont_diganti')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.tab === 'ont_diganti' ? 'bg-white text-orange-700 shadow-xs ring-1 ring-orange-500/20 font-extrabold' : 'text-gray-600 hover:text-orange-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <span>ONT di ganti</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-orange-50 text-orange-700 font-bold">{{ stats?.ont_diganti || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('ont_pengganti')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.tab === 'ont_pengganti' ? 'bg-white text-cyan-700 shadow-xs ring-1 ring-cyan-500/20 font-extrabold' : 'text-gray-600 hover:text-cyan-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    <span>ONT pengganti</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-cyan-50 text-cyan-700 font-bold">{{ stats?.ont_pengganti || 0 }}</span>
                </button>
            </div>

            <!-- 3. Smart Comprehensive Filters Container -->
            <div v-can="'network_ont_filter'" class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-4 mb-6 space-y-3.5">
                <!-- Baris Utama Filter -->
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2.5">
                    <!-- Live Search Input -->
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input 
                            type="text" 
                            v-model="filterState.search" 
                            @input="onSearchInput"
                            @keyup.enter="applyFilters"
                            placeholder="Cari Serial Number, ID ONT, Pelanggan, MAC, PPPoE User..." 
                            class="w-full pl-10 pr-9 py-2.5 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 placeholder-gray-400"
                        />
                        <button 
                            v-if="filterState.search" 
                            @click="clearSearch" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                            title="Hapus pencarian"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Filter Cabang / Area -->
                    <div class="w-full sm:w-auto min-w-[170px]">
                        <select 
                            v-model="filterState.area_id" 
                            @change="applyFilters"
                            class="w-full py-2.5 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Cabang</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Status ONT -->
                    <div class="w-full sm:w-auto min-w-[160px]">
                        <select 
                            v-model="filterState.status" 
                            @change="applyFilters"
                            class="w-full py-2.5 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="Sudah Set">Siap Pasang (Sudah Set)</option>
                            <option value="Belum Set/Baru Input">Belum Diset (Baru Input)</option>
                            <option value="active">Active (Terpasang)</option>
                            <option value="los">LOS (Gangguan)</option>
                            <option value="rusak">Rusak / Afkir</option>
                        </select>
                    </div>

                    <!-- Filter Status Instalasi -->
                    <div class="w-full sm:w-auto min-w-[170px]">
                        <select 
                            v-model="filterState.installation_status" 
                            @change="applyFilters"
                            class="w-full py-2.5 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Pasang</option>
                            <option value="installed">Terpasang Pelanggan</option>
                            <option value="uninstalled">Standby (Belum Terpasang)</option>
                        </select>
                    </div>

                    <!-- Tombol Filter Lanjutan Toggle & Reset -->
                    <div class="flex items-center gap-2 shrink-0">
                        <button 
                            type="button" 
                            @click="showAdvancedFilters = !showAdvancedFilters" 
                            class="px-3.5 py-2.5 rounded-xl border text-sm font-semibold transition-all flex items-center gap-2"
                            :class="[showAdvancedFilters || advancedFiltersActiveCount > 0 ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-gray-50 hover:bg-gray-100 border-gray-200 text-gray-700']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            <span>Filter Lanjutan</span>
                            <span v-if="advancedFiltersActiveCount > 0" class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[11px] font-bold flex items-center justify-center">
                                {{ advancedFiltersActiveCount }}
                            </span>
                        </button>

                        <button 
                            v-if="activeFiltersCount > 0" 
                            type="button" 
                            @click="resetAllFilters" 
                            class="px-3 py-2.5 rounded-xl bg-gray-50 hover:bg-red-50 hover:text-red-600 border border-gray-200 hover:border-red-200 text-gray-600 text-sm font-semibold transition-all flex items-center gap-1.5"
                            title="Reset semua filter"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>

                <!-- Baris Filter Lanjutan (Collapsible) -->
                <div v-show="showAdvancedFilters" class="pt-3 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                    <!-- Brand / Merk -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Merk / Brand</label>
                        <select 
                            v-model="filterState.brand" 
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-xs transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Brand</option>
                            <option v-for="b in brands" :key="b" :value="b">{{ b }}</option>
                        </select>
                    </div>

                    <!-- Mode Akses -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Mode Akses</label>
                        <select 
                            v-model="filterState.access_mode" 
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-xs transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Akses</option>
                            <option value="PPPOE">PPPoE</option>
                            <option value="HOTSPOT/MEMBER">Hotspot / Member</option>
                            <option value="STATIC">Static IP</option>
                            <option value="DHCP">DHCP / Dynamic</option>
                            <option value="Bridge">Bridge</option>
                        </select>
                    </div>

                    <!-- Mode VLAN -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Mode VLAN</label>
                        <select 
                            v-model="filterState.vlan_mode" 
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-xs transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua VLAN</option>
                            <option value="VLAN">VLAN</option>
                            <option value="Non-VLAN">Non-VLAN</option>
                        </select>
                    </div>

                    <!-- Rentang Tanggal Input -->
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tgl Input / Aktivasi</label>
                        <div class="flex items-center gap-1">
                            <input 
                                type="date" 
                                v-model="filterState.date_from" 
                                @change="applyFilters"
                                class="w-full py-1.5 px-2 bg-gray-50 border border-gray-200 rounded-lg text-xs"
                                title="Tanggal Dari"
                            />
                            <span class="text-gray-400 text-xs">-</span>
                            <input 
                                type="date" 
                                v-model="filterState.date_to" 
                                @change="applyFilters"
                                class="w-full py-1.5 px-2 bg-gray-50 border border-gray-200 rounded-lg text-xs"
                                title="Tanggal Sampai"
                            />
                        </div>
                    </div>

                    <!-- Tampilkan Per Halaman -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Baris Per Halaman</label>
                        <select 
                            v-model="filterState.per_page" 
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-xs transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option :value="15">15 Per Halaman</option>
                            <option :value="25">25 Per Halaman</option>
                            <option :value="50">50 Per Halaman</option>
                            <option :value="100">100 Per Halaman</option>
                        </select>
                    </div>
                </div>

                <!-- Active Filter Chips / Badges -->
                <div v-if="activeFiltersCount > 0" class="pt-2 border-t border-gray-100 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-gray-400">Filter Aktif:</span>
                    
                    <span v-if="filterState.search" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Cari: "{{ filterState.search }}"</span>
                        <button @click="removeFilter('search')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.tab" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Tab: {{ filterState.tab === 'ont_diganti' ? 'ONT di ganti' : 'ONT pengganti' }}</span>
                        <button @click="removeFilter('tab')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.area_id" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Cabang: {{ getAreaName(filterState.area_id) }}</span>
                        <button @click="removeFilter('area_id')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.status" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Status: {{ (filterState.status === 'rusak' || filterState.status === 'damaged') ? 'Rusak / Afkir' : filterState.status }}</span>
                        <button @click="removeFilter('status')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.installation_status" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Pasang: {{ filterState.installation_status === 'installed' ? 'Terpasang' : 'Standby' }}</span>
                        <button @click="removeFilter('installation_status')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.brand" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Brand: {{ filterState.brand }}</span>
                        <button @click="removeFilter('brand')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.access_mode" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Akses: {{ filterState.access_mode }}</span>
                        <button @click="removeFilter('access_mode')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.vlan_mode" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>VLAN: {{ filterState.vlan_mode }}</span>
                        <button @click="removeFilter('vlan_mode')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.date_from || filterState.date_to" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Tgl: {{ filterState.date_from || '...' }} s/d {{ filterState.date_to || '...' }}</span>
                        <button @click="removeFilter('date_from'); removeFilter('date_to')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <button @click="resetAllFilters" class="text-xs text-rose-600 hover:text-rose-800 font-bold ml-1">
                        Hapus Semua
                    </button>

                    <span class="text-xs text-gray-400 ml-auto font-medium">
                        Total: <strong class="text-gray-700">{{ onts.total || 0 }}</strong> perangkat cocok
                    </span>
                </div>
            </div>

            <!-- Banner Terpilih (Bulk Selection) -->
            <div v-if="selectedOnts.length > 0" class="mb-4 bg-indigo-600 text-white rounded-2xl p-4 shadow-lg shadow-indigo-600/20 flex flex-col sm:flex-row items-center justify-between gap-3 animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center font-bold text-sm">
                        {{ selectedOnts.length }}
                    </span>
                    <span class="text-sm font-semibold">Perangkat ONT terpilih</span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button 
                        v-can="'network_ont_delete'"
                        @click="openDeleteModal('selected')" 
                        type="button"
                        class="px-3.5 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih ({{ selectedOnts.length }})</span>
                    </button>
                    <button 
                        v-can="'network_ont_print_card'"
                        @click="openPrintModal" 
                        class="px-4 py-2 bg-white text-indigo-700 hover:bg-indigo-50 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Print Card ({{ selectedOnts.length }})</span>
                    </button>
                    <button 
                        @click="selectedOnts = []" 
                        class="px-3.5 py-2 bg-indigo-700/60 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition-all"
                    >
                        Batal
                    </button>
                </div>
            </div>

            <!-- Table Inventaris ONT -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <th class="px-6 py-4 text-left w-10">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">ID ONT</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Perangkat</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Cabang</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan (Instalasi)</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Akses / VLAN</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="ont in onts.data" :key="ont.id" class="hover:bg-indigo-50/20 transition-colors" :class="{'bg-indigo-50/40': selectedOnts.includes(ont.id)}">
                                <td class="px-6 py-4">
                                    <input type="checkbox" :value="ont.id" v-model="selectedOnts" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                        {{ ont.ont_id || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-1.5">
                                        <Link 
                                            v-if="ont.replacement_info?.ticket_url" 
                                            :href="ont.replacement_info.ticket_url" 
                                            class="px-2 py-1 text-xs font-semibold text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg transition-colors flex items-center gap-1"
                                            title="Buka Tiket Terkait"
                                        >
                                            <span>Tiket</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </Link>
                                        <Link :href="`/onts/${ont.id}`" class="px-2.5 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                                            Detail
                                        </Link>
                                        <button v-can="'network_ont_edit'" @click="openEditModal(ont)" class="px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition-colors">
                                            Edit
                                        </button>
                                        <button v-can="'network_ont_delete'" @click="deleteOnt(ont.id)" class="px-2.5 py-1 text-xs font-medium text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-bold text-gray-900 font-mono">{{ ont.serial_number }}</span>
                                        <button 
                                            type="button" 
                                            @click="copySn(ont.serial_number)" 
                                            class="text-gray-400 hover:text-indigo-600 transition-colors p-1 rounded-md hover:bg-gray-100"
                                            :title="copiedSn === ont.serial_number ? 'Tersalin!' : 'Salin Serial Number'"
                                        >
                                            <svg v-if="copiedSn === ont.serial_number" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1.5 flex-wrap">
                                        <!-- Badge Penggantian PGS / PGK -->
                                        <button 
                                            v-if="ont.replacement_info" 
                                            type="button"
                                            @click.stop="openReplacementDetail(ont)"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold cursor-pointer transition-all hover:scale-105"
                                            :class="ont.replacement_info.role === 'replacement' ? 'bg-cyan-100 text-cyan-800 border border-cyan-300' : 'bg-orange-100 text-orange-800 border border-orange-300'"
                                            :title="'Klik untuk melihat detail riwayat ' + (ont.replacement_info.ticket_number || ont.replacement_info.ticket_type)"
                                        >
                                            <span v-if="ont.replacement_info.role === 'replacement'">✨ Pengganti</span>
                                            <span v-else>🔄 Diganti</span>
                                            <span v-if="ont.replacement_info.ticket_number" class="underline font-mono">{{ ont.replacement_info.ticket_number }}</span>
                                        </button>
                                        <span class="font-medium text-gray-700">{{ ont.brand || 'Unknown' }}</span>
                                        <span v-if="ont.model" class="text-gray-400">• {{ ont.model }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span v-if="ont.area" class="inline-flex items-center px-2 py-0.5 rounded-md text-xs bg-slate-100 text-slate-700 font-medium">
                                        {{ ont.area.name }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="ont.customer">
                                        <div class="text-sm font-medium text-gray-900 flex items-center gap-1.5 flex-wrap">
                                            <Link :href="`/customers/${ont.customer.id}`" class="text-indigo-600 hover:text-indigo-900 hover:underline font-semibold">
                                                {{ ont.customer.name }}
                                            </Link>
                                            <span v-if="ont.customer.service_status === 'gratis'" class="px-1.5 py-0.2 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs" title="Pelanggan Bebas Tagihan (Gratis)">
                                                🎁 Gratis
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5 font-mono">{{ ont.customer.customer_code }}</div>
                                    </div>
                                    <div v-else-if="ont.replacement_info?.origin_customer_name" class="space-y-0.5">
                                        <div class="text-xs font-semibold text-gray-900 flex items-center gap-1">
                                            <span class="text-orange-600 font-bold">👤 Ex:</span>
                                            <span>{{ ont.replacement_info.origin_customer_name }}</span>
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-mono">{{ ont.replacement_info.origin_customer_code || '-' }}</div>
                                        <div class="text-[10px] text-orange-700 font-medium flex items-center gap-1">
                                            <span>Tiket {{ ont.replacement_info.ticket_type }}: {{ ont.replacement_info.ticket_number || '-' }}</span>
                                        </div>
                                    </div>
                                    <div v-else-if="ont.replacement_info?.target_customer_name" class="space-y-0.5">
                                        <div class="text-xs font-semibold text-gray-900 flex items-center gap-1">
                                            <span class="text-cyan-600 font-bold">🎯 Tujuan:</span>
                                            <span>{{ ont.replacement_info.target_customer_name }}</span>
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-mono">{{ ont.replacement_info.target_customer_code || '-' }}</div>
                                        <div class="text-[10px] text-cyan-700 font-medium">
                                            {{ ont.replacement_info.replacement_status === 'installed' ? '✓ Terpasang' : 'Dibawa Teknisi' }}
                                        </div>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1 text-xs text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 font-medium">
                                        <span>📦</span> Belum terpasang (Standby)
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-semibold text-gray-800">{{ ont.access_mode || '-' }}</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5 font-mono">{{ ont.vlan_mode || '-' }} <span v-if="ont.vlan_id">({{ ont.vlan_id }})</span></div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" :class="{
                                        'bg-emerald-100 text-emerald-800': ont.status === 'Sudah Set',
                                        'bg-blue-100 text-blue-800': ont.status === 'active',
                                        'bg-cyan-100 text-cyan-800': ont.status === 'Disiapkan Teknisi',
                                        'bg-rose-100 text-rose-800': ont.status === 'los',
                                        'bg-amber-100 text-amber-800': ont.status === 'Belum Set/Baru Input',
                                        'bg-red-100 text-red-800': ont.status === 'rusak' || ont.status === 'damaged',
                                        'bg-gray-100 text-gray-700': !['active', 'Sudah Set', 'los', 'Belum Set/Baru Input', 'rusak', 'damaged'].includes(ont.status),
                                    }">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="{
                                            'bg-emerald-500 animate-pulse': ont.status === 'Sudah Set',
                                            'bg-blue-500': ont.status === 'active',
                                            'bg-cyan-500 animate-pulse': ont.status === 'Disiapkan Teknisi',
                                            'bg-rose-500 animate-ping': ont.status === 'los',
                                            'bg-amber-500': ont.status === 'Belum Set/Baru Input',
                                            'bg-red-500': ont.status === 'rusak' || ont.status === 'damaged',
                                            'bg-gray-400': !['active', 'Sudah Set', 'los', 'Belum Set/Baru Input', 'rusak', 'damaged'].includes(ont.status),
                                        }"></span>
                                        <span>{{ (ont.status === 'rusak' || ont.status === 'damaged') ? 'Rusak / Afkir' : (ont.status || '-') }}</span>
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="onts.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-700">Tidak ada perangkat ONT yang ditemukan</p>
                                    <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau ubah filter status di atas</p>
                                    <button v-if="activeFiltersCount > 0" @click="resetAllFilters" class="mt-3 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-semibold transition-colors">
                                        Reset Semua Filter
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="onts.links?.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-gray-500 font-medium">
                        Menampilkan <strong class="text-gray-700">{{ onts.from || 0 }} - {{ onts.to || 0 }}</strong> dari <strong class="text-gray-700">{{ onts.total || 0 }}</strong> ONT
                    </span>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <Link 
                            v-for="(link, i) in onts.links" 
                            :key="i" 
                            :href="link.url || '#'" 
                            class="px-3 py-1.5 text-xs rounded-xl font-medium transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-200/80',
                                !link.url ? 'opacity-40 cursor-not-allowed hover:bg-transparent' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form ONT -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit ONT' : 'Tambah ONT Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        
                        <!-- Cabang -->
                        <div class="border border-purple-100 rounded-xl overflow-hidden bg-white p-4">
                            <label class="block text-xs font-bold text-purple-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Cabang
                            </label>
                            <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Pilih cabang</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                            </select>
                            <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                        </div>

                        <!-- Data Perangkat -->
                        <div class="border border-blue-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-blue-50/50 px-4 py-3 border-b border-blue-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                    <span class="text-sm font-semibold text-blue-900">Data Perangkat</span>
                                </div>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">ID ONT</label>
                                    <input v-model="form.ont_id" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kosongkan untuk generate otomatis" />
                                    <p v-if="form.errors.ont_id" class="text-red-500 text-xs mt-1">{{ form.errors.ont_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Merk ONT</label>
                                    <input v-model="form.brand" list="ont-brand-suggestions" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: ZTE, Huawei, Zvolin..." />
                                    <datalist id="ont-brand-suggestions">
                                        <option value="ZTE">ZTE</option>
                                        <option value="Huawei">Huawei</option>
                                        <option value="Fiberhome">Fiberhome</option>
                                        <option value="ZVOLIN">ZVOLIN</option>
                                        <option value="NETVIBES">NETVIBES</option>
                                        <option value="MYLINK">MYLINK</option>
                                        <option value="CMC">CMC</option>
                                        <option value="C-LINK">C-LINK</option>
                                        <option value="Ruijie">Ruijie</option>
                                    </datalist>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tipe / Model ONT</label>
                                    <input v-model="form.model" list="ont-model-suggestions" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: F660, F609, GM220..." />
                                    <datalist id="ont-model-suggestions">
                                        <option value="F660">F660</option>
                                        <option value="F609">F609</option>
                                        <option value="H609e">H609e</option>
                                        <option value="F460">F460</option>
                                        <option value="F452">F452</option>
                                        <option value="P613s">P613s</option>
                                        <option value="F613">F613</option>
                                        <option value="GM220">GM220</option>
                                        <option value="EG8141H5">EG8141H5</option>
                                        <option value="HG8245H">HG8245H</option>
                                        <option value="ML212X">ML212X</option>
                                    </datalist>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Serial Number</label>
                                    <input v-model="form.serial_number" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Serial Number" required />
                                    <p v-if="form.errors.serial_number" class="text-red-500 text-xs mt-1">{{ form.errors.serial_number }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">MAC Address</label>
                                    <input v-model="form.mac_address" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="AA:BB:CC:DD:EE:FF" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">VLAN Mode</label>
                                    <select v-model="form.vlan_mode" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="Non-VLAN">Non-VLAN</option>
                                        <option value="VLAN">VLAN</option>
                                    </select>
                                </div>
                                <div v-if="form.vlan_mode === 'VLAN'">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">VLAN ID</label>
                                    <input v-model="form.vlan_id" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: 100" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Mode Akses (Catatan)</label>
                                    <select v-model="form.access_mode" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="PPPOE">PPPoE</option>
                                        <option value="HOTSPOT/MEMBER">Hotspot / Member</option>
                                        <option value="STATIC">Static IP</option>
                                        <option value="DHCP">DHCP / Dynamic</option>
                                        <option value="Bridge">Bridge</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="px-4 pb-4">
                                <h4 class="text-xs font-bold text-gray-700 mt-4 mb-2">IP Login ONT</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">No IP</label>
                                        <input v-model="form.ip_login" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="192.168.1.1" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">User</label>
                                        <input v-model="form.login_user" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Username login" />
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs text-gray-500 mb-1">Password</label>
                                        <input v-model="form.login_password" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password login" />
                                    </div>
                                </div>
                            </div>

                            <!-- Akun Member / PPPoE (Hanya muncul jika bukan mode Hotspot) -->
                            <div v-if="!String(form.access_mode || '').trim().toLowerCase().includes('hotspot')" class="px-4 pb-4 border-t border-gray-100 pt-4">
                                <div class="flex items-center justify-between mb-2.5 flex-wrap gap-2">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-gray-800">Akun Member / PPPoE</h4>
                                        <span v-if="pppoeCheckStatus === 'checking'" class="text-[11px] font-medium text-gray-500 flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded-full">
                                            <svg class="animate-spin w-3 h-3 text-purple-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            Memeriksa...
                                        </span>
                                        <span v-else-if="pppoeCheckStatus === 'available'" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full flex items-center gap-1">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            Tersedia & Siap Digunakan
                                        </span>
                                        <span v-else-if="pppoeCheckStatus === 'duplicate'" class="text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full flex items-center gap-1 animate-pulse">
                                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Duplikat Ditolak!
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            type="button" 
                                            @click="generatePppoe" 
                                            :disabled="isGeneratingPppoe"
                                            class="text-xs font-bold bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white px-3 py-1.5 rounded-lg shadow-xs flex items-center gap-1.5 cursor-pointer transition disabled:opacity-50"
                                            title="Generate otomatis Akun PPPoE unik sesuai format prefiks perusahaan"
                                        >
                                            <svg v-if="!isGeneratingPppoe" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <svg v-else class="animate-spin w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            <span>{{ isGeneratingPppoe ? 'Mengenerate...' : 'Generate' }}</span>
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="openPppoeSettingsModal" 
                                            class="text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1.5 rounded-lg border border-gray-300 flex items-center gap-1 cursor-pointer transition"
                                            title="Pengaturan format dan prefiks PPPoE"
                                        >
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                                            <span>Format</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">User PPPoE / Member</label>
                                        <div class="relative">
                                            <input 
                                                v-model="form.pppoe_user" 
                                                @input="onPppoeUserInput"
                                                type="text" 
                                                :class="[
                                                    'w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-sm font-medium transition focus:outline-none',
                                                    pppoeCheckStatus === 'duplicate' 
                                                        ? 'border-rose-400 bg-rose-50/40 text-rose-900 focus:ring-2 focus:ring-rose-400' 
                                                        : pppoeCheckStatus === 'available'
                                                        ? 'border-emerald-400 bg-emerald-50/20 text-emerald-900 focus:ring-2 focus:ring-emerald-400'
                                                        : 'border-gray-200 focus:ring-2 focus:ring-blue-500'
                                                ]" 
                                                placeholder="User PPPoE/Member (Contoh: VGC0195@vnet.id)" 
                                            />
                                            <div v-if="pppoeCheckStatus === 'available'" class="absolute right-3 top-2.5 text-emerald-600 pointer-events-none">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <div v-else-if="pppoeCheckStatus === 'duplicate'" class="absolute right-3 top-2.5 text-rose-600 pointer-events-none">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            </div>
                                        </div>

                                        <!-- Duplicate Warning Alert Box -->
                                        <div v-if="pppoeCheckStatus === 'duplicate'" class="mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs space-y-1">
                                            <div class="font-bold flex items-center gap-1.5 text-rose-900">
                                                <span>🚫</span> {{ pppoeConflictMessage || 'Akun PPPoE / Member sudah terdaftar pada perangkat lain!' }}
                                            </div>
                                            <p class="text-[11px] text-rose-700 leading-relaxed">
                                                Sistem otomatis menolak duplikasi akun. Silakan ganti username atau klik tombol <button type="button" @click="generatePppoe" class="font-bold underline text-rose-900 hover:text-black">Generate</button> untuk mendapatkan username unik berikutnya.
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Password PPPoE / Member</label>
                                        <input v-model="form.pppoe_password" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password PPPoE/Member" />
                                    </div>
                                </div>
                            </div>
                            <!-- Catatan Khusus Mode Hotspot -->
                            <div v-else class="px-4 pb-4 border-t border-purple-100 pt-4 bg-purple-50/40">
                                <div class="flex items-start gap-2.5 p-3 bg-white border border-purple-200/80 rounded-xl text-purple-900 shadow-2xs">
                                    <svg class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="text-xs leading-relaxed">
                                        <span class="font-bold text-purple-950 block mb-0.5">Mode Akses: Hotspot</span>
                                        Akun Member / PPPoE tidak diset di data inventaris ONT. Username dan password hotspot diset saat proses <strong>Aktivasi Pelanggan</strong>.
                                    </div>
                                </div>
                            </div>

                            <!-- Hotspot Tambahan -->
                            <div class="px-4 pb-4 border-t border-gray-100 pt-4 bg-purple-50/30">
                                <label class="flex items-center gap-2 cursor-pointer mb-3">
                                    <input type="checkbox" v-model="form.free_hotspot" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-4 h-4">
                                    <span class="text-sm font-bold text-purple-800">Tambahkan Mode Akses Hotspot (Dual Mode)</span>
                                </label>

                                <div v-if="form.free_hotspot" class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">VLAN ID Hotspot</label>
                                        <input v-model="form.hotspot_vlan_id" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Misal: 200" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Petugas Input -->
                        <div class="border border-red-100 rounded-xl overflow-hidden bg-white bg-red-50/10">
                            <div class="px-4 py-3 border-b border-red-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-sm font-semibold text-red-800">Petugas Input</span>
                                </div>
                                <button type="button" @click="addOfficer" class="text-xs font-medium text-red-600 bg-white border border-red-200 hover:bg-red-50 px-3 py-1 rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Petugas
                                </button>
                            </div>
                            
                            <div class="p-4 space-y-4">
                                <div v-for="(officer, index) in form.input_officers" :key="index" class="bg-white p-4 rounded-lg border border-red-100 relative shadow-sm">
                                    <button v-if="form.input_officers.length > 1" type="button" @click="removeOfficer(index)" class="absolute top-3 right-3 text-gray-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    
                                    <h5 class="text-xs font-bold text-red-500 mb-3">Petugas {{ index + 1 }}</h5>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Nama Petugas Input ONT</label>
                                            <input v-model="officer.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="Pilih atau ketik petugas input" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Tanggal</label>
                                            <input v-model="officer.date" type="date" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Keterangan</label>
                                        <textarea v-model="officer.note" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="Keterangan tambahan..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Status ONT</label>
                            <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="Belum Set/Baru Input">Belum Set/Baru Input</option>
                                <option value="Sudah Set">Sudah Set (Siap Pasang)</option>
                                <option value="active">Active (Terpasang)</option>
                                <option value="inactive">Inactive</option>
                                <option value="los">LOS</option>
                                <option value="rusak">Rusak / Afkir</option>
                                <option value="damaged">Damaged</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sticky bottom-0 bg-white p-6 border-t border-gray-100 flex justify-end rounded-b-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan ONT
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Print Card Preview -->
        <Teleport to="body">
            <div v-if="isPrintModalOpen" id="ont-card-print-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col print-modal-box">
                    <div class="bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between no-print">
                        <h3 class="text-lg font-bold text-gray-900">Preview Print Card ({{ selectedOnts.length }} Item)</h3>
                        <div class="flex gap-2">
                            <button @click="printCards" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print Sekarang
                            </button>
                            <button @click="closePrintModal" class="text-gray-400 hover:text-gray-500 p-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- A4 Paper Preview -->
                    <div class="p-6 overflow-y-auto bg-gray-200 flex-1 flex justify-center print-preview-wrapper">
                        <div class="bg-white shadow-2xl print-card-paper" style="width: 210mm; min-height: 297mm; padding: 10mm;" id="print-area">
                            <div class="print-card-grid" style="display: flex; flex-wrap: wrap; gap: 6mm; justify-content: center;">
                                <!-- Template Card VIRUZS - Using Original Image -->
                                <div v-for="ont in getSelectedOntData()" :key="ont.id" class="print-card" style="width: 90mm; position: relative; break-inside: avoid; page-break-inside: avoid;">
                                    <!-- Background Template Image -->
                                    <img src="/images/card-template.jpg?v=2" alt="Card Template" style="width: 100%; display: block; border-radius: 6px; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;" />
                                    
                                    <!-- Overlay: Nama Pelanggan -->
                                    <div style="position: absolute; top: 46%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                        {{ ont.customer ? ont.customer.name : '-' }}
                                    </div>
                                    
                                    <!-- Overlay: Sales + No WA -->
                                    <div style="position: absolute; top: 61%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" :title="getCardSalesInfo(ont)">
                                        {{ getCardSalesInfo(ont) }}
                                    </div>
                                    
                                    <!-- Overlay: Tanggal Instalasi (dari Penugasan) -->
                                    <div style="position: absolute; top: 76%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                        {{ getCardInstallationDate(ont) }}
                                    </div>
                                    
                                    <!-- Overlay: ID-O -->
                                    <div style="position: absolute; top: 91%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 12px; font-weight: 800; color: #4c1d95; font-family: 'Consolas', 'Courier New', monospace; line-height: 1; display: flex; justify-content: space-between; align-items: center;">
                                        <span>{{ ont.ont_id || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus Semua / Terpilih -->
        <Teleport to="body">
            <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-red-100">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto border-4 border-red-50 shadow-inner">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ deleteConfirmMode === 'selected' ? `Hapus ${selectedOnts.length} Data ONT Terpilih?` : 'Hapus Seluruh Data ONT?' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                {{ deleteConfirmMode === 'selected'
                                    ? `Tindakan ini akan menghapus ${selectedOnts.length} data inventaris ONT yang Anda pilih secara permanen.`
                                    : `Tindakan ini akan menghapus SEMUA data ONT (${onts.total || onts.data.length} ONT) yang ada pada sistem secara permanen!` 
                                }}
                            </p>
                        </div>

                        <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 text-left text-xs text-amber-800 space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-amber-900">
                                <span>🛡️</span> Integritas Port ODP & Logistik:
                            </div>
                            <p class="text-[11px] leading-relaxed">
                                Port ODP yang terhubung dengan perangkat ONT yang dihapus akan otomatis dikembalikan menjadi <span class="font-bold">"available"</span> dan kapasitas port ODP akan dihitung ulang secara aman.
                            </p>
                        </div>

                        <!-- Konfirmasi Text HAPUS untuk Hapus Semua -->
                        <div v-if="deleteConfirmMode === 'all'" class="text-left space-y-1.5 pt-1">
                            <label class="block text-xs font-semibold text-gray-700">
                                Ketik <span class="font-bold text-red-600 font-mono">HAPUS</span> untuk konfirmasi:
                            </label>
                            <input 
                                v-model="deleteConfirmInput" 
                                type="text" 
                                placeholder="Ketik HAPUS" 
                                class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm text-center uppercase tracking-widest font-mono font-bold focus:ring-2 focus:ring-red-500 focus:outline-none"
                            />
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="closeDeleteModal" 
                                class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="executeBulkDelete" 
                                :disabled="isDeleting || (deleteConfirmMode === 'all' && deleteConfirmInput.toUpperCase() !== 'HAPUS')"
                                class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl transition-colors shadow-md shadow-red-200 flex items-center justify-center gap-2"
                            >
                                <svg v-if="isDeleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ isDeleting ? 'Menghapus...' : (deleteConfirmMode === 'selected' ? 'Hapus Terpilih' : 'Ya, Hapus Semua') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Riwayat Pergantian ONT (PGS / PGK) -->
        <Teleport to="body">
            <div v-if="selectedReplacementOnt" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between" :class="selectedReplacementOnt.replacement_info?.role === 'replacement' ? 'bg-cyan-50/70' : 'bg-orange-50/70'">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-base shadow-xs" :class="selectedReplacementOnt.replacement_info?.role === 'replacement' ? 'bg-cyan-100 text-cyan-700' : 'bg-orange-100 text-orange-700'">
                                {{ selectedReplacementOnt.replacement_info?.role === 'replacement' ? '✨' : '🔄' }}
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-gray-900">
                                    {{ selectedReplacementOnt.replacement_info?.role === 'replacement' ? 'Informasi ONT Pengganti' : 'Informasi ONT di ganti' }}
                                </h3>
                                <p class="text-xs text-gray-500 font-mono">SN: {{ selectedReplacementOnt.serial_number }}</p>
                            </div>
                        </div>
                        <button @click="closeReplacementDetail" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-white/80 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <!-- Tiket Terkait -->
                        <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-200/70 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">Tiket Layanan</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase" :class="selectedReplacementOnt.replacement_info?.ticket_type === 'PGS' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700'">
                                    {{ selectedReplacementOnt.replacement_info?.ticket_type || 'Pergantian' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Nomor Tiket:</span>
                                <span class="font-mono font-bold text-gray-900 text-xs">{{ selectedReplacementOnt.replacement_info?.ticket_number || '-' }}</span>
                            </div>
                            <div v-if="selectedReplacementOnt.replacement_info?.date" class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Tanggal Pergantian:</span>
                                <span class="font-semibold text-gray-800">{{ selectedReplacementOnt.replacement_info.date }}</span>
                            </div>
                            <div v-if="selectedReplacementOnt.replacement_info?.technician_name" class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Teknisi / PIC:</span>
                                <span class="font-semibold text-gray-800">{{ selectedReplacementOnt.replacement_info.technician_name }}</span>
                            </div>
                        </div>

                        <!-- Data Pelanggan -->
                        <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-200/70 space-y-2">
                            <div class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">Pelanggan Terkait</div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Nama Pelanggan:</span>
                                <span class="font-bold text-gray-900">{{ selectedReplacementOnt.replacement_info?.origin_customer_name || selectedReplacementOnt.replacement_info?.target_customer_name || selectedReplacementOnt.customer?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Kode Pelanggan (CID):</span>
                                <span class="font-mono text-gray-700">{{ selectedReplacementOnt.replacement_info?.origin_customer_code || selectedReplacementOnt.replacement_info?.target_customer_code || selectedReplacementOnt.customer?.customer_code || '-' }}</span>
                            </div>
                        </div>

                        <!-- Unit Berpasangan (Partner ONT) -->
                        <div v-if="selectedReplacementOnt.replacement_info?.partner_ont_sn" class="bg-gray-50 rounded-xl p-3.5 border border-gray-200/70 space-y-2">
                            <div class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">
                                {{ selectedReplacementOnt.replacement_info?.role === 'replacement' ? 'Unit ONT Lama yang Digantikan' : 'Unit ONT Baru Pengganti' }}
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Serial Number:</span>
                                <span class="font-mono font-bold text-indigo-700">{{ selectedReplacementOnt.replacement_info.partner_ont_sn }}</span>
                            </div>
                            <div v-if="selectedReplacementOnt.replacement_info?.partner_ont_model" class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Tipe / Model:</span>
                                <span class="text-gray-700 font-medium">{{ selectedReplacementOnt.replacement_info.partner_ont_model }}</span>
                            </div>
                        </div>

                        <!-- Catatan / Keterangan -->
                        <div v-if="selectedReplacementOnt.replacement_info?.notes" class="bg-amber-50 rounded-xl p-3.5 border border-amber-200 text-amber-900 space-y-1">
                            <span class="font-bold text-[10px] uppercase tracking-wider text-amber-800">Catatan Teknisi / Alasan:</span>
                            <p class="text-xs leading-relaxed">{{ selectedReplacementOnt.replacement_info.notes }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button 
                                type="button" 
                                @click="closeReplacementDetail" 
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-xs transition-colors"
                            >
                                Tutup
                            </button>
                            <Link 
                                v-if="selectedReplacementOnt.replacement_info?.ticket_url" 
                                :href="selectedReplacementOnt.replacement_info.ticket_url" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition-colors shadow-sm flex items-center gap-1.5"
                            >
                                <span>Buka Tiket {{ selectedReplacementOnt.replacement_info.ticket_type }}</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Pengaturan Format & Prefiks PPPoE -->
        <Teleport to="body">
            <div v-if="isPppoeSettingsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in overflow-y-auto">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden border border-gray-100 my-auto">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-linear-to-r from-purple-700 to-indigo-700 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white backdrop-blur-xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Pengaturan Format & Prefiks PPPoE</h3>
                                <p class="text-xs text-purple-200">Konfigurasi generator cerdas akun Member / PPPoE ONT</p>
                            </div>
                        </div>
                        <button type="button" @click="closePppoeSettingsModal" class="text-white/80 hover:text-white transition p-1 cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="savePppoeSettings" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        <!-- Live Preview Card -->
                        <div class="bg-linear-to-br from-indigo-50 to-purple-50 rounded-2xl p-4 border border-indigo-100 shadow-2xs">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 flex items-center gap-1.5">
                                    <span>👁️</span> Live Preview Hasil Generate
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    100% Bebas Bentrok
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                <div class="bg-white p-3 rounded-xl border border-indigo-100 shadow-2xs">
                                    <span class="text-[11px] font-semibold text-gray-500 block mb-0.5">Username PPPoE:</span>
                                    <span class="text-sm font-black font-mono text-purple-700 break-all">{{ livePppoePreview.user }}</span>
                                </div>
                                <div class="bg-white p-3 rounded-xl border border-indigo-100 shadow-2xs">
                                    <span class="text-[11px] font-semibold text-gray-500 block mb-0.5">Password:</span>
                                    <span class="text-sm font-black font-mono text-emerald-700">{{ livePppoePreview.pass }}</span>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-2.5 leading-relaxed">
                                Karyawan hanya perlu mengklik tombol <strong>Generate</strong>, sistem akan otomatis mengambil urutan nomor berikutnya tanpa risiko bentrok.
                            </p>
                        </div>

                        <!-- Setting Fields -->
                        <div class="space-y-4">
                            <!-- Prefiks & Suffiks -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Prefiks Khusus PPPoE <span class="text-purple-600">*</span>
                                    </label>
                                    <input 
                                        v-model="pppoeSettingsForm.pppoe_prefix" 
                                        type="text" 
                                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none" 
                                        placeholder="Misal: VGC atau vz_" 
                                    />
                                    <p class="text-[11px] text-gray-400 mt-1">Awalan username (Contoh: VGC, vz_, pppoe_)</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Suffiks / Domain PPPoE
                                    </label>
                                    <input 
                                        v-model="pppoeSettingsForm.pppoe_suffix" 
                                        type="text" 
                                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none" 
                                        placeholder="Misal: @vnet.id (Boleh kosong)" 
                                    />
                                    <p class="text-[11px] text-gray-400 mt-1">Domain akhir (kosongkan jika tanpa domain)</p>
                                </div>
                            </div>

                            <!-- Format Penomoran & Panjang Digit -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Metode Penomoran <span class="text-purple-600">*</span>
                                    </label>
                                    <select 
                                        v-model="pppoeSettingsForm.pppoe_format" 
                                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer"
                                    >
                                        <option value="sequential">Urutan Angka Otomatis (Sequential)</option>
                                        <option value="random_numeric">Angka Acak (Random Digits)</option>
                                        <option value="random_alphanumeric">Huruf & Angka Acak (Alphanumeric)</option>
                                    </select>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        {{ pppoeSettingsForm.pppoe_format === 'sequential' 
                                            ? 'Auto-increment otomatis mendeteksi nomor tertinggi di DB' 
                                            : 'Karakter acak dengan verifikasi bebas tabrakan' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Panjang Digit Nomor <span class="text-purple-600">*</span>
                                    </label>
                                    <input 
                                        v-model.number="pppoeSettingsForm.pppoe_length" 
                                        type="number" 
                                        min="2" 
                                        max="12" 
                                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none" 
                                    />
                                    <p class="text-[11px] text-gray-400 mt-1">Misal: 4 digit = 0001 s/d 9999</p>
                                </div>
                            </div>

                            <!-- Format Huruf (Casing) -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Format Huruf (Casing)
                                </label>
                                <select 
                                    v-model="pppoeSettingsForm.pppoe_case" 
                                    class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer"
                                >
                                    <option value="standard">Standar (Prefiks & Domain sesuai input teks)</option>
                                    <option value="upper">SEMUA HURUF BESAR (UPPERCASE)</option>
                                    <option value="lower">semua huruf kecil (lowercase)</option>
                                </select>
                            </div>

                            <!-- Konfigurasi Password -->
                            <div class="border-t border-gray-100 pt-4 space-y-4">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Format Password Akun</h4>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Tipe Password</label>
                                        <select 
                                            v-model="pppoeSettingsForm.pppoe_password_type" 
                                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer"
                                        >
                                            <option value="random_numeric">PIN Angka Acak (Rekomendasi)</option>
                                            <option value="random_alphanumeric">Huruf & Angka Acak</option>
                                            <option value="static">Password Default Tetap</option>
                                        </select>
                                    </div>
                                    <div v-if="pppoeSettingsForm.pppoe_password_type !== 'static'">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Panjang Password (Karakter)</label>
                                        <input 
                                            v-model.number="pppoeSettingsForm.pppoe_password_length" 
                                            type="number" 
                                            min="4" 
                                            max="32" 
                                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none" 
                                        />
                                    </div>
                                    <div v-else>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Nilai Password Default</label>
                                        <input 
                                            v-model="pppoeSettingsForm.pppoe_default_password" 
                                            type="text" 
                                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold focus:bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none" 
                                            placeholder="Misal: 123456" 
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button 
                                type="button" 
                                @click="closePppoeSettingsModal" 
                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="isSavingPppoeSettings"
                                class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl transition shadow-md shadow-purple-200 flex items-center gap-2 cursor-pointer"
                            >
                                <svg v-if="isSavingPppoeSettings" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ isSavingPppoeSettings ? 'Menyimpan...' : 'Simpan Format' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    body.is-printing-ont-card #app {
        display: none !important;
    }

    body.is-printing-ont-card {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body.is-printing-ont-card #ont-card-print-modal {
        position: static !important;
        inset: auto !important;
        display: block !important;
        width: 100% !important;
        height: auto !important;
        background: transparent !important;
        backdrop-filter: none !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        box-shadow: none !important;
        visibility: visible !important;
    }

    body.is-printing-ont-card #ont-card-print-modal * {
        visibility: visible !important;
    }

    body.is-printing-ont-card .no-print,
    body.is-printing-ont-card .no-print * {
        display: none !important;
        visibility: hidden !important;
    }

    body.is-printing-ont-card .print-modal-box {
        width: 100% !important;
        max-width: none !important;
        max-height: none !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
        background: transparent !important;
        display: block !important;
    }

    body.is-printing-ont-card .print-preview-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
        overflow: visible !important;
        display: block !important;
    }

    body.is-printing-ont-card #print-area,
    body.is-printing-ont-card .print-card-paper {
        width: 100% !important;
        max-width: 190mm !important;
        min-height: auto !important;
        height: auto !important;
        margin: 0 auto !important;
        padding: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        display: block !important;
    }

    body.is-printing-ont-card .print-card-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 6mm !important;
        justify-content: flex-start !important;
        margin: 0 auto !important;
        width: 100% !important;
    }

    body.is-printing-ont-card .print-card {
        width: 90mm !important;
        position: relative !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        margin-bottom: 2mm !important;
    }

    body.is-printing-ont-card .print-card img {
        width: 100% !important;
        display: block !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    onts: Object,
    areas: Array,
    brands: Array,
    stats: Object,
    filters: Object,
    pppoe_settings: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const isPrintModalOpen = ref(false);
const selectedOnts = ref([]);
const selectedReplacementOnt = ref(null);

function openReplacementDetail(ont) {
    selectedReplacementOnt.value = ont;
}

function closeReplacementDetail() {
    selectedReplacementOnt.value = null;
}

// Smart Filter State
const filterState = ref({
    search: props.filters?.search || '',
    tab: props.filters?.tab || '',
    status: props.filters?.status || '',
    installation_status: props.filters?.installation_status || '',
    area_id: props.filters?.area_id || '',
    brand: props.filters?.brand || '',
    access_mode: props.filters?.access_mode || '',
    vlan_mode: props.filters?.vlan_mode || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    per_page: props.filters?.per_page ? Number(props.filters.per_page) : 15,
});

const showAdvancedFilters = ref(
    !!(props.filters?.brand || props.filters?.access_mode || props.filters?.vlan_mode || props.filters?.date_from || props.filters?.date_to || (props.filters?.per_page && props.filters?.per_page != 15))
);

let searchTimeout = null;

function applyFilters() {
    const query = {};
    if (filterState.value.search) query.search = filterState.value.search;
    if (filterState.value.tab) query.tab = filterState.value.tab;
    if (filterState.value.status) query.status = filterState.value.status;
    if (filterState.value.installation_status) query.installation_status = filterState.value.installation_status;
    if (filterState.value.area_id) query.area_id = filterState.value.area_id;
    if (filterState.value.brand) query.brand = filterState.value.brand;
    if (filterState.value.access_mode) query.access_mode = filterState.value.access_mode;
    if (filterState.value.vlan_mode) query.vlan_mode = filterState.value.vlan_mode;
    if (filterState.value.date_from) query.date_from = filterState.value.date_from;
    if (filterState.value.date_to) query.date_to = filterState.value.date_to;
    if (filterState.value.per_page && filterState.value.per_page != 15) query.per_page = filterState.value.per_page;

    router.get('/onts', query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
}

function clearSearch() {
    filterState.value.search = '';
    applyFilters();
}

function setQuickFilter(type) {
    if (type === 'ont_diganti') {
        filterState.value.tab = filterState.value.tab === 'ont_diganti' ? '' : 'ont_diganti';
        filterState.value.status = '';
        filterState.value.installation_status = '';
        applyFilters();
        return;
    }
    if (type === 'ont_pengganti') {
        filterState.value.tab = filterState.value.tab === 'ont_pengganti' ? '' : 'ont_pengganti';
        filterState.value.status = '';
        filterState.value.installation_status = '';
        applyFilters();
        return;
    }
    filterState.value.tab = '';
    if (type === 'all') {
        filterState.value.status = '';
        filterState.value.installation_status = '';
    } else if (type === 'sudah_set') {
        const isActive = filterState.value.status === 'Sudah Set' && filterState.value.installation_status === 'uninstalled';
        filterState.value.status = isActive ? '' : 'Sudah Set';
        filterState.value.installation_status = isActive ? '' : 'uninstalled';
    } else if (type === 'belum_set') {
        const isActive = filterState.value.status === 'Belum Set/Baru Input' && filterState.value.installation_status === 'uninstalled';
        filterState.value.status = isActive ? '' : 'Belum Set/Baru Input';
        filterState.value.installation_status = isActive ? '' : 'uninstalled';
    } else if (type === 'installed') {
        filterState.value.installation_status = filterState.value.installation_status === 'installed' ? '' : 'installed';
        filterState.value.status = '';
    } else if (type === 'uninstalled') {
        filterState.value.installation_status = filterState.value.installation_status === 'uninstalled' ? '' : 'uninstalled';
        filterState.value.status = '';
    } else if (type === 'los') {
        filterState.value.status = filterState.value.status === 'los' ? '' : 'los';
        filterState.value.installation_status = '';
    } else if (type === 'rusak') {
        filterState.value.status = filterState.value.status === 'rusak' ? '' : 'rusak';
        filterState.value.installation_status = '';
    }
    applyFilters();
}

function resetAllFilters() {
    filterState.value = {
        search: '',
        tab: '',
        status: '',
        installation_status: '',
        area_id: '',
        brand: '',
        access_mode: '',
        vlan_mode: '',
        date_from: '',
        date_to: '',
        per_page: 15,
    };
    applyFilters();
}

function removeFilter(key) {
    filterState.value[key] = '';
    applyFilters();
}

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filterState.value.search) count++;
    if (filterState.value.tab) count++;
    if (filterState.value.area_id) count++;
    if (filterState.value.status) count++;
    if (filterState.value.installation_status) count++;
    if (filterState.value.brand) count++;
    if (filterState.value.access_mode) count++;
    if (filterState.value.vlan_mode) count++;
    if (filterState.value.date_from || filterState.value.date_to) count++;
    return count;
});

const advancedFiltersActiveCount = computed(() => {
    let count = 0;
    if (filterState.value.brand) count++;
    if (filterState.value.access_mode) count++;
    if (filterState.value.vlan_mode) count++;
    if (filterState.value.date_from || filterState.value.date_to) count++;
    if (filterState.value.per_page && filterState.value.per_page != 15) count++;
    return count;
});

function getAreaName(areaId) {
    const a = props.areas?.find(ar => ar.id == areaId);
    return a ? a.name : areaId;
}

const copiedSn = ref(null);
function copySn(sn) {
    if (!sn) return;
    navigator.clipboard.writeText(sn);
    copiedSn.value = sn;
    setTimeout(() => {
        if (copiedSn.value === sn) copiedSn.value = null;
    }, 2000);
}

const isAllSelected = computed(() => {
    return (props.onts?.data?.length || 0) > 0 && selectedOnts.value.length === props.onts.data.length;
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedOnts.value = [];
    } else {
        selectedOnts.value = (props.onts?.data || []).map(ont => ont.id);
    }
}

function getSelectedOntData() {
    return (props.onts?.data || []).filter(ont => selectedOnts.value.includes(ont.id));
}

function openPrintModal() {
    if (selectedOnts.value.length === 0) return;
    isPrintModalOpen.value = true;
    document.body.classList.add('is-printing-ont-card');
}

function closePrintModal() {
    isPrintModalOpen.value = false;
    document.body.classList.remove('is-printing-ont-card');
}

function printCards() {
    document.body.classList.add('is-printing-ont-card');
    setTimeout(() => {
        window.print();
    }, 150);
}

watch(isPrintModalOpen, (isOpen) => {
    if (isOpen) {
        document.body.classList.add('is-printing-ont-card');
    } else {
        document.body.classList.remove('is-printing-ont-card');
    }
});

onUnmounted(() => {
    document.body.classList.remove('is-printing-ont-card');
});

function getCardSalesInfo(ont) {
    if (!ont) return '-';
    const sales = ont.customer?.sales;
    if (sales) {
        const name = sales.name || '-';
        const phone = sales.phone || sales.whatsapp || sales.no_wa || sales.employee?.phone;
        if (phone) {
            return `${name} (${phone})`;
        }
        return name;
    }
    return getOfficerName(ont);
}

function getCardInstallationDate(ont) {
    if (!ont) return '-';
    const cust = ont.customer;
    if (cust) {
        const schedules = cust.technician_schedules || cust.technicianSchedules || [];
        const installSched = schedules.find(s => s.type === 'installation' || s.type === 'instalasi') || schedules[0];
        if (installSched && installSched.scheduled_date) {
            return formatDate(installSched.scheduled_date);
        }
        if (cust.installation_date) {
            return formatDate(cust.installation_date);
        }
    }
    if (ont.start_time) {
        return formatDate(ont.start_time);
    }
    if (cust && cust.activation_date) {
        return formatDate(cust.activation_date);
    }
    if (cust && cust.registration_date) {
        return formatDate(cust.registration_date);
    }
    return formatDate(ont.created_at);
}

function getOfficerName(ont) {
    if (ont.input_officers && ont.input_officers.length > 0) {
        const first = ont.input_officers[0];
        return typeof first === 'object' ? (first.name || '-') : first;
    }
    return '-';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const str = String(dateStr).trim();
    let d;
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        const [y, m, dayNum] = str.split('-').map(Number);
        d = new Date(y, m - 1, dayNum);
    } else {
        d = new Date(str);
    }
    if (isNaN(d.getTime())) return str;
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

const form = useForm({
    ont_id: '',
    area_id: '',
    brand: '',
    model: '',
    serial_number: '',
    mac_address: '',
    vlan_mode: 'Non-VLAN',
    vlan_id: '',
    access_mode: 'PPPOE',
    ip_login: '192.168.1.1',
    login_user: '',
    login_password: '',
    pppoe_user: '',
    pppoe_password: '',
    free_hotspot: false,
    hotspot_user: '',
    hotspot_password: '',
    hotspot_vlan_id: '',
    input_officers: [
        { name: '', date: new Date().toISOString().split('T')[0], note: '' }
    ],
    status: 'Belum Set/Baru Input',
    id: null
});

function openCreateModal(initialBrand = '') {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.id = null;
    if (initialBrand) {
        form.brand = initialBrand;
    }
    pppoeCheckStatus.value = 'idle';
    pppoeConflictMessage.value = '';
    isModalOpen.value = true;
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        if (params.get('create') === '1' || params.get('brand_new')) {
            const initialBrand = params.get('brand_new') || '';
            openCreateModal(initialBrand);
        }
    }
});

function openEditModal(ont) {
    isEditing.value = true;
    form.id = ont.id;
    form.ont_id = ont.ont_id || '';
    form.area_id = ont.area_id || '';
    form.brand = ont.brand || '';
    form.model = ont.model || '';
    form.serial_number = ont.serial_number || '';
    form.mac_address = ont.mac_address || '';
    form.vlan_mode = ont.vlan_mode || 'Non-VLAN';
    form.vlan_id = ont.vlan_id || '';
    let rawMode = String(ont.access_mode || 'PPPOE').trim().toUpperCase();
    if (rawMode.includes('HOTSPOT') || rawMode.includes('MEMBER')) rawMode = 'HOTSPOT/MEMBER';
    else if (rawMode.includes('STATIC')) rawMode = 'STATIC';
    else if (rawMode === 'DHCP') rawMode = 'DHCP';
    else if (rawMode === 'BRIDGE') rawMode = 'Bridge';
    else if (rawMode === 'PPPOE') rawMode = 'PPPOE';
    form.access_mode = rawMode;
    form.ip_login = ont.ip_login || '192.168.1.1';
    form.login_user = ont.login_user || '';
    form.login_password = ont.login_password || '';
    
    // Jika mode hotspot, user/pass member tidak disimpan di ONT (diset saat aktivasi)
    if (String(form.access_mode || '').trim().toLowerCase().includes('hotspot')) {
        form.pppoe_user = '';
        form.pppoe_password = '';
    } else {
        form.pppoe_user = ont.pppoe_user || '';
        form.pppoe_password = ont.pppoe_password || '';
    }
    
    form.free_hotspot = !!ont.free_hotspot;
    form.hotspot_user = ont.hotspot_user || '';
    form.hotspot_password = ont.hotspot_password || '';
    form.hotspot_vlan_id = ont.hotspot_vlan_id || '';
    
    if (ont.input_officers && ont.input_officers.length > 0) {
        form.input_officers = JSON.parse(JSON.stringify(ont.input_officers));
    } else {
        form.input_officers = [{ name: '', date: new Date().toISOString().split('T')[0], note: '' }];
    }
    
    form.status = ont.status || 'Belum Set/Baru Input';
    
    form.clearErrors();
    pppoeCheckStatus.value = 'idle';
    pppoeConflictMessage.value = '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function addOfficer() {
    form.input_officers.push({
        name: '',
        date: new Date().toISOString().split('T')[0],
        note: ''
    });
}

function removeOfficer(index) {
    form.input_officers.splice(index, 1);
}

// ── PPPoE Generator & Duplicate Auto-Reject State ───────────
const pppoeSettings = ref({
    pppoe_prefix: props.pppoe_settings?.pppoe_prefix ?? 'VGC',
    pppoe_format: props.pppoe_settings?.pppoe_format ?? 'sequential',
    pppoe_length: Number(props.pppoe_settings?.pppoe_length ?? 4),
    pppoe_suffix: props.pppoe_settings?.pppoe_suffix ?? '@vnet.id',
    pppoe_case: props.pppoe_settings?.pppoe_case ?? 'standard',
    pppoe_password_type: props.pppoe_settings?.pppoe_password_type ?? 'random_numeric',
    pppoe_password_length: Number(props.pppoe_settings?.pppoe_password_length ?? 6),
    pppoe_default_password: props.pppoe_settings?.pppoe_default_password ?? '123456',
});

const isPppoeSettingsModalOpen = ref(false);
const isSavingPppoeSettings = ref(false);
const isGeneratingPppoe = ref(false);
const pppoeCheckStatus = ref('idle'); // 'idle' | 'checking' | 'available' | 'duplicate'
const pppoeConflictMessage = ref('');
let pppoeCheckTimeout = null;

const pppoeSettingsForm = ref({ ...pppoeSettings.value });

function openPppoeSettingsModal() {
    pppoeSettingsForm.value = { ...pppoeSettings.value };
    isPppoeSettingsModalOpen.value = true;
}

function closePppoeSettingsModal() {
    isPppoeSettingsModalOpen.value = false;
}

const livePppoePreview = computed(() => {
    const s = pppoeSettingsForm.value;
    const prefix = s.pppoe_prefix || '';
    const suffix = s.pppoe_suffix || '';
    const len = Math.max(2, Math.min(12, Number(s.pppoe_length) || 4));
    
    let mid = '';
    if (s.pppoe_format === 'sequential') {
        mid = '0'.repeat(Math.max(0, len - 3)) + '195';
    } else if (s.pppoe_format === 'random_numeric') {
        mid = '7842'.slice(0, len).padEnd(len, '9');
    } else {
        mid = '7x9k'.slice(0, len).padEnd(len, 'a');
    }
    
    let user = `${prefix}${mid}${suffix}`;
    if (s.pppoe_case === 'upper') user = user.toUpperCase();
    else if (s.pppoe_case === 'lower') user = user.toLowerCase();
    
    let pass = '';
    const pwLen = Math.max(4, Math.min(32, Number(s.pppoe_password_length) || 6));
    if (s.pppoe_password_type === 'static') {
        pass = s.pppoe_default_password || '123456';
    } else if (s.pppoe_password_type === 'random_alphanumeric') {
        pass = '8k3m9p2w'.slice(0, pwLen).padEnd(pwLen, 'x');
    } else {
        pass = '681942'.slice(0, pwLen).padEnd(pwLen, '5');
    }
    
    return { user, pass };
});

async function savePppoeSettings() {
    isSavingPppoeSettings.value = true;
    try {
        const res = await axios.post('/onts/update-pppoe-settings', pppoeSettingsForm.value);
        if (res.data && res.data.success) {
            pppoeSettings.value = { ...res.data.settings };
            isPppoeSettingsModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Pengaturan Disimpan',
                text: 'Format generate PPPoE berhasil disimpan.',
                timer: 1800,
                showConfirmButton: false,
            });
        }
    } catch (err) {
        console.error('Failed to save PPPoE settings:', err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Menyimpan',
            text: err.response?.data?.message || 'Terjadi kesalahan saat menyimpan pengaturan PPPoE.',
            confirmButtonColor: '#e11d48',
        });
    } finally {
        isSavingPppoeSettings.value = false;
    }
}

async function generatePppoe() {
    isGeneratingPppoe.value = true;
    try {
        const res = await axios.get('/onts/generate-pppoe', {
            params: {
                prefix: pppoeSettings.value.pppoe_prefix,
                format: pppoeSettings.value.pppoe_format,
                length: pppoeSettings.value.pppoe_length,
                suffix: pppoeSettings.value.pppoe_suffix,
                case: pppoeSettings.value.pppoe_case,
                password_type: pppoeSettings.value.pppoe_password_type,
                password_length: pppoeSettings.value.pppoe_password_length,
                default_password: pppoeSettings.value.pppoe_default_password,
            }
        });
        if (res.data && res.data.success) {
            const userVal = res.data.pppoe_user || res.data.user || '';
            const passVal = res.data.pppoe_password || res.data.password || '';
            form.pppoe_user = String(userVal);
            form.pppoe_password = String(passVal);
            pppoeCheckStatus.value = 'available';
            pppoeConflictMessage.value = '';
        }
    } catch (err) {
        console.error('Error generating PPPoE:', err);
        const prefix = pppoeSettings.value.pppoe_prefix || 'VGC';
        const suffix = pppoeSettings.value.pppoe_suffix || '@vnet.id';
        const randomNum = Math.floor(1000 + Math.random() * 9000);
        form.pppoe_user = `${prefix}${randomNum}${suffix}`;
        form.pppoe_password = String(Math.floor(100000 + Math.random() * 900000));
        pppoeCheckStatus.value = 'available';
        pppoeConflictMessage.value = '';
    } finally {
        isGeneratingPppoe.value = false;
    }
}

function onPppoeUserInput() {
    clearTimeout(pppoeCheckTimeout);
    const val = String(form.pppoe_user || '').trim();
    if (!val) {
        pppoeCheckStatus.value = 'idle';
        pppoeConflictMessage.value = '';
        return;
    }

    pppoeCheckStatus.value = 'checking';
    pppoeCheckTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/onts/check-pppoe-user', {
                params: {
                    user: val,
                    ignore_ont_id: isEditing.value && form.id ? form.id : null,
                }
            });
            if (res.data && res.data.available) {
                pppoeCheckStatus.value = 'available';
                pppoeConflictMessage.value = '';
            } else if (res.data) {
                pppoeCheckStatus.value = 'duplicate';
                pppoeConflictMessage.value = res.data.message || 'Akun PPPoE sudah digunakan oleh perangkat lain!';
            }
        } catch (err) {
            console.error('Error checking PPPoE username:', err);
            pppoeCheckStatus.value = 'idle';
        }
    }, 350);
}

watch(() => form.access_mode, (val) => {
    if (String(val || '').trim().toLowerCase().includes('hotspot')) {
        form.pppoe_user = '';
        form.pppoe_password = '';
    }
});

async function submit() {
    if (String(form.access_mode || '').trim().toLowerCase().includes('hotspot')) {
        form.pppoe_user = '';
        form.pppoe_password = '';
    } else if (form.pppoe_user && form.pppoe_user.trim() !== '') {
        try {
            const checkRes = await axios.get('/onts/check-pppoe-user', {
                params: {
                    user: form.pppoe_user.trim(),
                    ignore_ont_id: isEditing.value && form.id ? form.id : null
                }
            });
            if (checkRes.data && !checkRes.data.available) {
                pppoeCheckStatus.value = 'duplicate';
                pppoeConflictMessage.value = checkRes.data.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Akun PPPoE Duplikat Ditolak!',
                    html: `<p class="text-sm text-gray-700">${checkRes.data.message}</p><p class="mt-2 text-xs font-semibold text-rose-600">Sistem otomatis menolak duplikasi akun. Silakan ubah atau klik tombol <strong>Generate</strong> untuk membuat akun unik.</p>`,
                    confirmButtonText: 'Saya Mengerti',
                    confirmButtonColor: '#e11d48',
                });
                return;
            }
        } catch (e) {
            console.error('Error validating PPPoE username:', e);
        }
    }

    if (isEditing.value && form.id) {
        form.post(`/onts/${form.id}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/onts', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOnt(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data inventaris ONT ini?')) {
        router.post(`/onts/${id}/delete`);
    }
}

// ── Bulk Delete / Hapus Semua ONT ────────────────────────────
const showDeleteConfirmModal = ref(false);
const deleteConfirmMode = ref('all');
const deleteConfirmInput = ref('');
const isDeleting = ref(false);

function openDeleteModal(mode = 'all') {
    deleteConfirmMode.value = mode;
    deleteConfirmInput.value = '';
    showDeleteConfirmModal.value = true;
}

function closeDeleteModal() {
    showDeleteConfirmModal.value = false;
    deleteConfirmInput.value = '';
    isDeleting.value = false;
}

function executeBulkDelete() {
    if (deleteConfirmMode.value === 'all' && deleteConfirmInput.value.toUpperCase() !== 'HAPUS') {
        alert('Ketik HAPUS untuk konfirmasi hapus semua data ONT.');
        return;
    }

    isDeleting.value = true;
    router.post('/onts/bulk-destroy', {
        all: deleteConfirmMode.value === 'all',
        ids: deleteConfirmMode.value === 'selected' ? selectedOnts.value : [],
        search: filterState.search,
        status: filterState.status,
        installation_status: filterState.installation_status,
        area_id: filterState.area_id,
        brand: filterState.brand,
        access_mode: filterState.access_mode,
        vlan_mode: filterState.vlan_mode,
        date_from: filterState.date_from,
        date_to: filterState.date_to,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            selectedOnts.value = [];
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
}
</script>
