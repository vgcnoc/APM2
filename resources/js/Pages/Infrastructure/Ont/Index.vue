<template>
    <AppLayout title="Data ONT">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ONT / Modem</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data inventaris perangkat ONT. (Data otomatis ditambahkan dari Surat Jalan Gudang)</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Tombol Print Card -->
                    <button v-if="selectedOnts.length > 0" @click="openPrintModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Card ({{ selectedOnts.length }})
                    </button>
                    <!-- Tombol Tambah ONT -->
                    <button @click="openCreateModal" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah ONT
                    </button>
                </div>
            </div>

            <!-- 1. Statistik Cards Inventaris ONT -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
                <!-- Card 1: Total Perangkat -->
                <div 
                    @click="setQuickFilter('all')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[!filterState.status && !filterState.installation_status ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/20' : 'border-gray-200/80 hover:border-blue-300']"
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
                    :class="[filterState.status === 'Sudah Set' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-gray-200/80 hover:border-emerald-300']"
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
                    :class="[filterState.status === 'Belum Set/Baru Input' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/20' : 'border-gray-200/80 hover:border-amber-300']"
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
            </div>

            <!-- 2. Smart Quick Status Tabs -->
            <div class="flex items-center gap-1.5 p-1.5 bg-slate-100/80 rounded-2xl mb-4 overflow-x-auto border border-slate-200/60 scrollbar-none">
                <button 
                    type="button" 
                    @click="setQuickFilter('all')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[!filterState.status && !filterState.installation_status ? 'bg-white text-gray-900 shadow-xs ring-1 ring-black/5 font-extrabold' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50']"
                >
                    <span>Semua</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="[!filterState.status && !filterState.installation_status ? 'bg-gray-100 text-gray-800' : 'bg-gray-200/70 text-gray-600']">{{ stats?.total || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('sudah_set')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'Sudah Set' ? 'bg-white text-emerald-700 shadow-xs ring-1 ring-emerald-500/20 font-extrabold' : 'text-gray-600 hover:text-emerald-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Siap Pasang</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 font-bold">{{ stats?.sudah_set || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('belum_set')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'Belum Set/Baru Input' ? 'bg-white text-amber-700 shadow-xs ring-1 ring-amber-500/20 font-extrabold' : 'text-gray-600 hover:text-amber-700 hover:bg-white/50']"
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
            </div>

            <!-- 3. Smart Comprehensive Filters Container -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-4 mb-6 space-y-3.5">
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
                            <option value="PPPOE">PPPOE</option>
                            <option value="STATIC">STATIC</option>
                            <option value="DHCP">DHCP</option>
                            <option value="BRIDGE">BRIDGE</option>
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

                    <span v-if="filterState.area_id" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Cabang: {{ getAreaName(filterState.area_id) }}</span>
                        <button @click="removeFilter('area_id')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.status" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Status: {{ filterState.status }}</span>
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
                    <span class="text-sm font-semibold">Perangkat ONT terpilih untuk dicetak atau dikelola</span>
                </div>
                <div class="flex items-center gap-2">
                    <button 
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
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Perangkat</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Cabang</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan (Instalasi)</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Akses / VLAN</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
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
                                    <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1.5">
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
                                        'bg-rose-100 text-rose-800': ont.status === 'los',
                                        'bg-amber-100 text-amber-800': ont.status === 'Belum Set/Baru Input',
                                        'bg-gray-100 text-gray-700': !['active', 'Sudah Set', 'los', 'Belum Set/Baru Input'].includes(ont.status),
                                    }">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="{
                                            'bg-emerald-500 animate-pulse': ont.status === 'Sudah Set',
                                            'bg-blue-500': ont.status === 'active',
                                            'bg-rose-500 animate-ping': ont.status === 'los',
                                            'bg-amber-500': ont.status === 'Belum Set/Baru Input',
                                            'bg-gray-400': !['active', 'Sudah Set', 'los', 'Belum Set/Baru Input'].includes(ont.status),
                                        }"></span>
                                        <span>{{ ont.status || '-' }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="`/onts/${ont.id}`" class="px-2.5 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                                            Detail
                                        </Link>
                                        <button @click="openEditModal(ont)" class="px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition-colors">
                                            Edit
                                        </button>
                                        <button @click="deleteOnt(ont.id)" class="px-2.5 py-1 text-xs font-medium text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors">
                                            Hapus
                                        </button>
                                    </div>
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
                                    <input v-model="form.brand" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Merk ONT" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tipe ONT</label>
                                    <input v-model="form.model" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tipe ONT" />
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
                                        <option value="PPPOE">PPPOE</option>
                                        <option value="Static">Static</option>
                                        <option value="DHCP">DHCP</option>
                                        <option value="Bridge">Bridge</option>
                                        <option value="Hotspot">Hotspot</option>
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

                            <div class="px-4 pb-4 border-t border-gray-100 pt-4">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-xs font-bold text-gray-700">Akun Member / PPPoE</h4>
                                    <button type="button" @click="generatePppoe" class="text-xs font-medium bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-md border border-gray-300 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Generate
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">User</label>
                                        <input v-model="form.pppoe_user" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="User PPPoE/Member" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Password</label>
                                        <input v-model="form.pppoe_password" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password PPPoE/Member" />
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
                                    <img src="/images/card-template.jpg" alt="Card Template" style="width: 100%; display: block; border-radius: 6px; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;" />
                                    
                                    <!-- Overlay: Nama Pelanggan -->
                                    <div style="position: absolute; top: 46%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                        {{ ont.customer ? ont.customer.name : '-' }}
                                    </div>
                                    
                                    <!-- Overlay: Sales -->
                                    <div style="position: absolute; top: 61%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                        {{ ont.customer?.sales?.name || getOfficerName(ont) }}
                                    </div>
                                    
                                    <!-- Overlay: Tanggal Aktivasi -->
                                    <div style="position: absolute; top: 76%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                        {{ formatDate(ont.created_at) }}
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
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    onts: Object,
    areas: Array,
    brands: Array,
    stats: Object,
    filters: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const isPrintModalOpen = ref(false);
const selectedOnts = ref([]);

// Smart Filter State
const filterState = ref({
    search: props.filters?.search || '',
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
    if (type === 'all') {
        filterState.value.status = '';
        filterState.value.installation_status = '';
    } else if (type === 'sudah_set') {
        filterState.value.status = filterState.value.status === 'Sudah Set' ? '' : 'Sudah Set';
        filterState.value.installation_status = '';
    } else if (type === 'belum_set') {
        filterState.value.status = filterState.value.status === 'Belum Set/Baru Input' ? '' : 'Belum Set/Baru Input';
        filterState.value.installation_status = '';
    } else if (type === 'installed') {
        filterState.value.installation_status = filterState.value.installation_status === 'installed' ? '' : 'installed';
        filterState.value.status = '';
    } else if (type === 'uninstalled') {
        filterState.value.installation_status = filterState.value.installation_status === 'uninstalled' ? '' : 'uninstalled';
        filterState.value.status = '';
    } else if (type === 'los') {
        filterState.value.status = filterState.value.status === 'los' ? '' : 'los';
        filterState.value.installation_status = '';
    }
    applyFilters();
}

function resetAllFilters() {
    filterState.value = {
        search: '',
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
    return props.onts.data.length > 0 && selectedOnts.value.length === props.onts.data.length;
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedOnts.value = [];
    } else {
        selectedOnts.value = props.onts.data.map(ont => ont.id);
    }
}

function getSelectedOntData() {
    return props.onts.data.filter(ont => selectedOnts.value.includes(ont.id));
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



function getOfficerName(ont) {
    if (ont.input_officers && ont.input_officers.length > 0) {
        const first = ont.input_officers[0];
        return typeof first === 'object' ? (first.name || '-') : first;
    }
    return '-';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
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

function openCreateModal() {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.id = null;
    isModalOpen.value = true;
}

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
    form.access_mode = ont.access_mode || 'PPPOE';
    form.ip_login = ont.ip_login || '192.168.1.1';
    form.login_user = ont.login_user || '';
    form.login_password = ont.login_password || '';
    form.pppoe_user = ont.pppoe_user || '';
    form.pppoe_password = ont.pppoe_password || '';
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

function generatePppoe() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let user = '';
    let pass = '';
    for(let i = 0; i < 6; i++) user += chars.charAt(Math.floor(Math.random() * chars.length));
    for(let i = 0; i < 8; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    
    form.pppoe_user = 'user_' + user;
    form.pppoe_password = pass;
}

function submit() {
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
</script>
