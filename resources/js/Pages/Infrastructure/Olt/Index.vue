<template>
    <AppLayout title="Data OLT">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen OLT</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data Optical Line Terminal (OLT)</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Tombol Hapus Terpilih -->
                    <button 
                        v-if="selectedIds.length > 0"
                        v-can="'network_olt_delete'"
                        @click="openDeleteModal('selected')"
                        type="button"
                        class="px-3.5 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-semibold text-sm rounded-lg border border-red-200 shadow-sm flex items-center gap-2 transition-all"
                    >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih ({{ selectedIds.length }})</span>
                    </button>

                    <!-- Tombol Hapus Semua -->
                    <button 
                        v-can="'network_olt_delete_all'"
                        @click="openDeleteModal('all')"
                        type="button"
                        class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg shadow-sm flex items-center gap-2 transition-all"
                        title="Hapus Seluruh Data OLT"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Semua</span>
                    </button>

                    <!-- Tombol Tambah OLT -->
                    <button 
                        v-can="'network_olt_create'"
                        @click="openCreateModal" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah OLT</span>
                    </button>
                </div>
            </div>

            <!-- 1. Statistik Cards OLT -->
            <div v-can="'network_olt_card_stats'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
                <!-- Card 1: Total OLT -->
                <div 
                    @click="setQuickFilter('all')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[!filterState.status && !filterState.odc_status ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/20' : 'border-gray-200/80 hover:border-blue-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-blue-600 transition-colors">Total OLT</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-100/70 text-blue-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900">{{ Number(stats?.total || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Semua unit OLT</p>
                </div>

                <!-- Card 2: OLT Aktif -->
                <div 
                    @click="setQuickFilter('active')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-gray-200/80 hover:border-emerald-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-emerald-600 transition-colors">OLT Aktif</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-100/70 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600">{{ Number(stats?.active || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Online & Operasional</p>
                </div>

                <!-- Card 3: Total Port PON -->
                <div 
                    class="bg-white rounded-2xl p-4 border border-gray-200/80 transition-all duration-200 shadow-xs hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total PON</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-100/70 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600">{{ Number(stats?.total_pon_ports || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Total Port PON Fisik</p>
                </div>

                <!-- Card 4: Terhubung ODC -->
                <div 
                    @click="setQuickFilter('has_odc')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.odc_status === 'has_odc' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/20' : 'border-gray-200/80 hover:border-purple-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-purple-600 transition-colors">Terhubung ODC</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-100/70 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-purple-600">{{ Number(stats?.has_odc || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">OLT Memiliki ODC</p>
                </div>

                <!-- Card 5: Inaktif / Maint -->
                <div 
                    @click="setQuickFilter('inactive')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'inactive' ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/20' : 'border-gray-200/80 hover:border-rose-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-rose-600 transition-colors">Inaktif / Maint</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-100/70 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600">{{ Number(stats?.inactive || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Butuh Perhatian</p>
                </div>
            </div>

            <!-- 2. Smart Quick Status Tabs -->
            <div v-can="'network_olt_filter'" class="flex items-center gap-1.5 p-1.5 bg-slate-100/80 rounded-2xl mb-4 overflow-x-auto border border-slate-200/60 scrollbar-none">
                <button 
                    type="button" 
                    @click="setQuickFilter('all')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[!filterState.status && !filterState.odc_status ? 'bg-white text-gray-900 shadow-xs ring-1 ring-black/5 font-extrabold' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50']"
                >
                    <span>Semua</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="[!filterState.status && !filterState.odc_status ? 'bg-gray-100 text-gray-800' : 'bg-gray-200/70 text-gray-600']">{{ stats?.total || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('active')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'active' ? 'bg-white text-emerald-700 shadow-xs ring-1 ring-emerald-500/20 font-extrabold' : 'text-gray-600 hover:text-emerald-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Aktif (Online)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 font-bold">{{ stats?.active || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('inactive')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'inactive' ? 'bg-white text-rose-700 shadow-xs ring-1 ring-rose-500/20 font-extrabold' : 'text-gray-600 hover:text-rose-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Inaktif / Maintenance</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 font-bold">{{ stats?.inactive || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('has_odc')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.odc_status === 'has_odc' ? 'bg-white text-purple-700 shadow-xs ring-1 ring-purple-500/20 font-extrabold' : 'text-gray-600 hover:text-purple-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Dengan ODC</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-50 text-purple-700 font-bold">{{ stats?.has_odc || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('no_odc')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.odc_status === 'no_odc' ? 'bg-white text-amber-700 shadow-xs ring-1 ring-amber-500/20 font-extrabold' : 'text-gray-600 hover:text-amber-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Belum Ada ODC</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 font-bold">{{ Math.max(0, (stats?.total || 0) - (stats?.has_odc || 0)) }}</span>
                </button>
            </div>

            <!-- 3. Smart Comprehensive Filters Container -->
            <div v-can="'network_olt_filter'" class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-4 mb-6 space-y-3.5">
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
                            placeholder="Cari Nama OLT, IP Address, Domain, Brand, Model, Lokasi..." 
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
                            <option value="">Semua Cabang / Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Status -->
                    <div class="w-full sm:w-auto min-w-[150px]">
                        <select 
                            v-model="filterState.status" 
                            @change="applyFilters"
                            class="w-full py-2.5 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Active (Online)</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <!-- Filter Brand -->
                    <div class="w-full sm:w-auto min-w-[150px]">
                        <select 
                            v-model="filterState.brand" 
                            @change="applyFilters"
                            class="w-full py-2.5 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-sm transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Brand</option>
                            <option v-for="b in brands" :key="b" :value="b">{{ b }}</option>
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
                        </button>
                    </div>
                </div>

                <!-- Collapsible Drawer Filter Lanjutan -->
                <div v-show="showAdvancedFilters" class="pt-3 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <!-- Jumlah Port PON -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Jumlah Port PON</label>
                        <select 
                            v-model="filterState.pon_ports" 
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-xs transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua Kapasitas PON</option>
                            <option :value="2">2 Port PON</option>
                            <option :value="4">4 Port PON</option>
                            <option :value="8">8 Port PON</option>
                            <option :value="16">16 Port PON</option>
                        </select>
                    </div>

                    <!-- Relasi ODC -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Koneksi ODC</label>
                        <select 
                            v-model="filterState.odc_status" 
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-xs transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Semua ODC</option>
                            <option value="has_odc">Sudah Ada ODC Terhubung</option>
                            <option value="no_odc">Belum Ada ODC (Standby)</option>
                        </select>
                    </div>

                    <!-- Tanggal Input Dari - Sampai -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Input</label>
                        <div class="flex items-center gap-1.5">
                            <input 
                                type="date" 
                                v-model="filterState.date_from" 
                                @change="applyFilters" 
                                class="w-1/2 py-1.5 px-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                            <span class="text-xs text-gray-400">-</span>
                            <input 
                                type="date" 
                                v-model="filterState.date_to" 
                                @change="applyFilters" 
                                class="w-1/2 py-1.5 px-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Baris Per Halaman -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Baris Per Halaman</label>
                        <select 
                            v-model="filterState.per_page" 
                            @change="applyFilters"
                            class="w-full py-2 px-3 bg-gray-50 hover:bg-gray-100/50 focus:bg-white border border-gray-200 rounded-xl text-xs transition-all focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option :value="10">10 Per Halaman</option>
                            <option :value="15">15 Per Halaman</option>
                            <option :value="25">25 Per Halaman</option>
                            <option :value="50">50 Per Halaman</option>
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
                        <span>Area: {{ getAreaName(filterState.area_id) }}</span>
                        <button @click="removeFilter('area_id')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.status" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Status: {{ filterState.status }}</span>
                        <button @click="removeFilter('status')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.brand" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Brand: {{ filterState.brand }}</span>
                        <button @click="removeFilter('brand')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.pon_ports" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Port: {{ filterState.pon_ports }} PON</span>
                        <button @click="removeFilter('pon_ports')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.odc_status" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>ODC: {{ filterState.odc_status === 'has_odc' ? 'Ada ODC' : 'Tanpa ODC' }}</span>
                        <button @click="removeFilter('odc_status')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <span v-if="filterState.date_from || filterState.date_to" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-medium">
                        <span>Tgl: {{ filterState.date_from || '...' }} s/d {{ filterState.date_to || '...' }}</span>
                        <button @click="removeFilter('date_from'); removeFilter('date_to')" class="hover:text-indigo-900 ml-0.5">✕</button>
                    </span>

                    <button @click="resetAllFilters" class="text-xs text-rose-600 hover:text-rose-800 font-bold ml-1">
                        Hapus Semua Filter
                    </button>

                    <span class="text-xs text-gray-400 ml-auto font-medium">
                        Total: <strong class="text-gray-700">{{ olts.total || 0 }}</strong> unit cocok
                    </span>
                </div>
            </div>

            <!-- Banner Terpilih (Bulk Selection) -->
            <div v-if="selectedIds.length > 0" class="mb-4 bg-indigo-600 text-white rounded-2xl p-4 shadow-lg shadow-indigo-600/20 flex flex-col sm:flex-row items-center justify-between gap-3 animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center font-bold text-sm">
                        {{ selectedIds.length }}
                    </span>
                    <span class="text-sm font-semibold">Unit OLT terpilih</span>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        v-can="'network_olt_delete'"
                        @click="openDeleteModal('selected')" 
                        type="button"
                        class="px-3.5 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih ({{ selectedIds.length }})</span>
                    </button>
                    <button 
                        @click="selectedIds = []" 
                        class="px-3.5 py-2 bg-indigo-700/60 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition-all"
                    >
                        Batal
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="w-12 px-4 py-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        @change="toggleSelectAll" 
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" 
                                        title="Pilih Semua OLT di Halaman Ini"
                                    />
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama OLT</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">IP Address / Domain</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Brand / Model</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port PON</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="olt in olts.data" :key="olt.id" :class="['hover:bg-gray-50/50 transition-colors', selectedIds.includes(olt.id) ? 'bg-indigo-50/30' : '']">
                                <td class="w-12 px-4 py-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :value="olt.id" 
                                        v-model="selectedIds" 
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" 
                                    />
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ olt.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div v-if="getOltAreas(olt).length" class="flex flex-wrap gap-1.5">
                                        <span v-for="area in getOltAreas(olt)" :key="area.id" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-bold border border-blue-100">{{ area.name }}</span>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">Belum diset</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ olt.ip_address || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ olt.brand || '-' }} {{ olt.model ? '('+olt.model+')' : '' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div class="font-bold text-gray-900">{{ olt.total_pon_ports }} Port PON</div>
                                    <div v-if="getOltVlans(olt).length > 0" class="flex flex-wrap gap-1 mt-1">
                                        <span v-for="(v, vi) in getOltVlans(olt).slice(0, 4)" :key="vi" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                            <span>{{ v.vlan_id }}</span>
                                            <span class="opacity-75 text-[9px]">({{ v.access_mode }})</span>
                                        </span>
                                        <span v-if="getOltVlans(olt).length > 4" class="px-1 py-0.5 rounded text-[10px] bg-gray-100 text-gray-600 font-medium">
                                            +{{ getOltVlans(olt).length - 4 }}
                                        </span>
                                    </div>
                                    <div v-else class="text-[11px] text-gray-400 italic mt-0.5">Belum diset VLAN</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="olt.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                        {{ olt.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                    <Link :href="`/olts/${olt.id}`" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                    <button v-can="'network_olt_edit'" @click="openEditModal(olt)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                    <button v-can="'network_olt_delete'" @click="deleteOlt(olt.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="olts.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-700">Tidak ada data OLT yang ditemukan</p>
                                    <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau ubah filter di atas</p>
                                    <button v-if="activeFiltersCount > 0" @click="resetAllFilters" class="mt-3 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-semibold transition-colors">
                                        Reset Semua Filter
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="olts.links && olts.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50/40">
                    <p class="text-xs text-gray-500 font-medium">Menampilkan {{ olts.from || 0 }} - {{ olts.to || 0 }} dari {{ olts.total || 0 }} data OLT</p>
                    <div class="flex gap-1 flex-wrap">
                        <Link 
                            v-for="(link, i) in olts.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold' : 'text-gray-600 hover:bg-gray-200/70',
                                !link.url && 'opacity-40 cursor-not-allowed pointer-events-none'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit OLT -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit OLT' : 'Tambah OLT Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        
                        <!-- Informasi Dasar -->
                        <div class="border border-indigo-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-indigo-50/50 px-4 py-3 border-b border-indigo-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span class="text-sm font-semibold text-indigo-900">Spesifikasi OLT</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Area / Wilayah</label>
                                    <div class="max-h-40 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-2 space-y-1.5">
                                        <label v-for="area in areas" :key="area.id" class="flex items-center gap-2.5 cursor-pointer rounded-md px-2.5 py-2 hover:bg-white transition-colors">
                                            <input v-model="form.area_ids" :value="area.id" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            <span class="text-sm font-medium text-gray-700">{{ area.name }}</span>
                                        </label>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Centang satu atau beberapa area. Area pertama menjadi area utama untuk kompatibilitas data lama.</p>
                                    <p v-if="form.errors.area_ids" class="text-red-500 text-xs mt-1">{{ form.errors.area_ids }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama OLT</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: OLT-ZTE-01" required />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Hostname (Opsional)</label>
                                    <input v-model="form.hostname" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: olt1.isp.net" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">IP Address / Domain (Opsional)</label>
                                    <input v-model="form.ip_address" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="192.168.1.1 atau vpn.viruzs.my.id:45" />
                                    <p class="text-[11px] text-gray-400 mt-1">Dapat diisi IP Address atau Domain / Hostname (dengan atau tanpa port).</p>
                                    <p v-if="form.errors.ip_address" class="text-red-500 text-xs mt-1">{{ form.errors.ip_address }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Jumlah Port PON</label>
                                    <input v-model="form.total_pon_ports" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                    <p v-if="form.errors.total_pon_ports" class="text-red-500 text-xs mt-1">{{ form.errors.total_pon_ports }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas (ONT per PON)</label>
                                    <input v-model="form.pon_capacity" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Default: 64" />
                                    <p v-if="form.errors.pon_capacity" class="text-red-500 text-xs mt-1">{{ form.errors.pon_capacity }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Brand / Merk</label>
                                    <input v-model="form.brand" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ZTE" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Model</label>
                                    <input v-model="form.model" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: C320" />
                                </div>
                            </div>
                        </div>

                        <!-- Lokasi -->
                        <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-green-50/50 px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                <span class="text-sm font-semibold text-green-900">Lokasi Penempatan</span>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat / Lokasi</label>
                                    <textarea v-model="form.location" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Rak Server Lt.2 Data Center"></textarea>
                                </div>
                                <div>
                                    <div class="flex gap-2">
                                        <input v-model="form.latitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Latitude" />
                                        <input v-model="form.longitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Longitude" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detail -->
                        <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-purple-50/50 px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                <span class="text-sm font-semibold text-purple-900">Detail & Status</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Status</label>
                                    <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Deskripsi / Keterangan</label>
                                    <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- VLAN per PON -->
                        <div class="border border-blue-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-blue-50/50 px-4 py-3 border-b border-blue-100 flex items-center justify-between">
                                <span class="text-sm font-semibold text-blue-900">Konfigurasi VLAN per PON</span>
                                <button type="button" @click="syncPonVlans" class="text-[10px] bg-blue-600 text-white px-2 py-1 rounded font-bold shadow-sm hover:bg-blue-700 transition-colors">Generate Sesuai Port</button>
                            </div>
                            <div class="p-4 space-y-4 max-h-64 overflow-y-auto">
                                <div v-if="!form.pon_vlans || form.pon_vlans.length === 0" class="text-xs text-gray-500 text-center py-2">
                                    Klik "Generate Sesuai Port" untuk memunculkan input VLAN.
                                </div>
                                <div v-for="(pon, index) in form.pon_vlans" :key="index" class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-bold text-gray-800">PON {{ pon.port }}</h4>
                                        <button type="button" @click="addVlanToPon(index)" class="text-[10px] px-2 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded font-bold">+ Tambah VLAN</button>
                                    </div>
                                    
                                    <div v-if="pon.vlans.length === 0" class="text-[10px] text-gray-500 italic mb-1">Belum ada VLAN.</div>
                                    
                                    <div class="space-y-2">
                                        <div v-for="(vlan, vIndex) in pon.vlans" :key="vIndex" class="bg-white p-2.5 rounded-lg border border-gray-200 shadow-2xs">
                                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                                <div class="sm:col-span-5">
                                                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">Nama Layanan / VLAN</label>
                                                    <input v-model="vlan.name" type="text" placeholder="Cth: PPPoE Internet" class="w-full px-2.5 py-1.5 bg-gray-50 border border-gray-200 rounded text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div class="sm:col-span-3">
                                                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">VLAN ID</label>
                                                    <input v-model="vlan.vlan_id" type="number" placeholder="ID (Cth: 18)" class="w-full px-2.5 py-1.5 bg-gray-50 border border-gray-200 rounded text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div class="sm:col-span-3">
                                                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">Mode Akses</label>
                                                    <select v-model="vlan.access_mode" class="w-full px-2 py-1.5 bg-gray-50 border border-gray-200 rounded text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                        <option value="PPPOE">PPPOE</option>
                                                        <option value="Hotspot">Hotspot</option>
                                                        <option value="Static">Static IP</option>
                                                        <option value="DHCP">DHCP</option>
                                                        <option value="Bridge">Bridge</option>
                                                        <option value="Tagged">Tagged (Trunk)</option>
                                                        <option value="Untagged">Untagged (Access)</option>
                                                    </select>
                                                </div>
                                                <div class="sm:col-span-1 flex justify-end sm:justify-center pt-2 sm:pt-4">
                                                    <button type="button" @click="pon.vlans.splice(vIndex, 1)" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded" title="Hapus VLAN">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="sticky bottom-0 bg-white p-6 border-t border-gray-100 flex justify-end rounded-b-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan OLT
                        </button>
                    </div>
                </form>
            </div>
        </div>

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
                                {{ deleteConfirmMode === 'selected' ? `Hapus ${selectedIds.length} Data OLT Terpilih?` : 'Hapus Seluruh Data OLT?' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                {{ deleteConfirmMode === 'selected'
                                    ? `Tindakan ini akan menghapus ${selectedIds.length} data OLT yang Anda pilih secara permanen.`
                                    : `Tindakan ini akan menghapus SEMUA data OLT (${olts.total || olts.data.length} OLT) yang ada pada sistem secara permanen!` 
                                }}
                            </p>
                        </div>

                        <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 text-left text-xs text-amber-800 space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-amber-900">
                                <span>🛡️</span> Integritas Port PON & Topologi:
                            </div>
                            <p class="text-[11px] leading-relaxed">
                                Seluruh port PON yang terdaftar pada OLT ini akan ikut dihapus. Jika OLT masih terhubung dengan ODC, pastikan rute jaringan telah dialihkan.
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

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    olts: Object,
    areas: Array,
    brands: Array,
    stats: Object,
    filters: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    area_ids: [],
    name: '',
    hostname: '',
    ip_address: '',
    brand: '',
    model: '',
    total_pon_ports: 8,
    pon_capacity: 64,
    pon_vlans: [],
    location: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: ''
});

function syncPonVlans() {
    const total = parseInt(form.total_pon_ports) || 0;
    if (total <= 0) return;
    
    if (!form.pon_vlans) {
        form.pon_vlans = [];
    }
    
    // Add new PONs if needed
    for (let i = 1; i <= total; i++) {
        if (!form.pon_vlans.find(p => p.port === i)) {
            form.pon_vlans.push({ port: i, vlans: [] });
        }
    }
    
    // Remove extra PONs if reduced, but sort them first to be safe
    form.pon_vlans.sort((a, b) => a.port - b.port);
    if (form.pon_vlans.length > total) {
        form.pon_vlans = form.pon_vlans.filter(p => p.port <= total);
    }
}

function addVlanToPon(ponIndex) {
    if (!form.pon_vlans[ponIndex].vlans) {
        form.pon_vlans[ponIndex].vlans = [];
    }
    form.pon_vlans[ponIndex].vlans.push({ name: '', vlan_id: '', access_mode: 'PPPOE' });
}

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    form.area_ids = [];
    isModalOpen.value = true;
}

function openEditModal(olt) {
    isEditing.value = true;
    editId.value = olt.id;
    form.area_ids = getOltAreas(olt).map(area => area.id);
    form.name = olt.name;
    form.hostname = olt.hostname || '';
    form.ip_address = olt.ip_address || '';
    form.brand = olt.brand || '';
    form.model = olt.model || '';
    form.total_pon_ports = olt.total_pon_ports;
    form.pon_capacity = 64; // Default
    
    let vlans = olt.pon_vlans || [];
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { vlans = []; }
    }
    form.pon_vlans = JSON.parse(JSON.stringify(vlans)).map(p => ({
        ...p,
        vlans: (p.vlans || []).map(v => ({
            ...v,
            access_mode: v.access_mode || getVlanAccessMode(v)
        }))
    }));

    form.location = olt.location || '';
    form.latitude = olt.latitude || '';
    form.longitude = olt.longitude || '';
    form.status = olt.status;
    form.description = olt.description || '';
    form.clearErrors();
    isModalOpen.value = true;
}

function getVlanAccessMode(vlan) {
    if (vlan.access_mode) return vlan.access_mode;
    const name = (vlan.name || '').toLowerCase();
    if (name.includes('pppoe')) return 'PPPOE';
    if (name.includes('hotspot')) return 'Hotspot';
    if (name.includes('static')) return 'Static';
    if (name.includes('dhcp')) return 'DHCP';
    if (name.includes('bridge')) return 'Bridge';
    if (name.includes('trunk') || name.includes('tagged')) return 'Tagged';
    if (name.includes('access') || name.includes('untagged')) return 'Untagged';
    return 'PPPOE';
}

function getOltAreas(olt) {
    if (Array.isArray(olt?.areas) && olt.areas.length) {
        return olt.areas;
    }

    return olt?.area ? [olt.area] : [];
}

function getModeBadgeClass(mode) {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'HOTSPOT':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'STATIC':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'DHCP':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'BRIDGE':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'TAGGED':
        case 'TRUNK':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'UNTAGGED':
        case 'ACCESS':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        default:
            return 'bg-blue-50 text-blue-700 border-blue-200';
    }
}

function getOltVlans(olt) {
    if (!olt || !olt.pon_vlans) return [];
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const list = [];
    vlans.forEach(p => {
        (p.vlans || []).forEach(v => {
            if (v && v.vlan_id) {
                list.push({
                    port: p.port,
                    name: v.name,
                    vlan_id: v.vlan_id,
                    access_mode: getVlanAccessMode(v)
                });
            }
        });
    });
    return list;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function submit() {
    if (isEditing.value) {
        form.post(`/olts/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/olts', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOlt(id) {
    if (confirm('Apakah Anda yakin ingin menghapus OLT ini?')) {
        router.post(`/olts/${id}/delete`);
    }
}

// ── Filter State & Debounce ──────────────────────────────────
const filterState = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
    area_id: props.filters?.area_id || '',
    brand: props.filters?.brand || '',
    pon_ports: props.filters?.pon_ports || '',
    odc_status: props.filters?.odc_status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    per_page: props.filters?.per_page || 15,
});

const showAdvancedFilters = ref(false);
let searchTimeout = null;

function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
}

function clearSearch() {
    filterState.value.search = '';
    applyFilters();
}

function applyFilters() {
    router.get('/olts', {
        search: filterState.value.search || undefined,
        status: filterState.value.status || undefined,
        area_id: filterState.value.area_id || undefined,
        brand: filterState.value.brand || undefined,
        pon_ports: filterState.value.pon_ports || undefined,
        odc_status: filterState.value.odc_status || undefined,
        date_from: filterState.value.date_from || undefined,
        date_to: filterState.value.date_to || undefined,
        per_page: filterState.value.per_page !== 15 ? filterState.value.per_page : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}

function setQuickFilter(type) {
    if (type === 'all') {
        filterState.value.status = '';
        filterState.value.odc_status = '';
    } else if (type === 'active') {
        filterState.value.status = 'active';
        filterState.value.odc_status = '';
    } else if (type === 'inactive') {
        filterState.value.status = 'inactive';
        filterState.value.odc_status = '';
    } else if (type === 'has_odc') {
        filterState.value.odc_status = 'has_odc';
        filterState.value.status = '';
    } else if (type === 'no_odc') {
        filterState.value.odc_status = 'no_odc';
        filterState.value.status = '';
    }
    applyFilters();
}

function removeFilter(key) {
    filterState.value[key] = '';
    applyFilters();
}

function resetAllFilters() {
    filterState.value = {
        search: '',
        status: '',
        area_id: '',
        brand: '',
        pon_ports: '',
        odc_status: '',
        date_from: '',
        date_to: '',
        per_page: 15,
    };
    applyFilters();
}

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filterState.value.search) count++;
    if (filterState.value.status) count++;
    if (filterState.value.area_id) count++;
    if (filterState.value.brand) count++;
    if (filterState.value.pon_ports) count++;
    if (filterState.value.odc_status) count++;
    if (filterState.value.date_from || filterState.value.date_to) count++;
    return count;
});

const advancedFiltersActiveCount = computed(() => {
    let count = 0;
    if (filterState.value.brand) count++;
    if (filterState.value.pon_ports) count++;
    if (filterState.value.odc_status) count++;
    if (filterState.value.date_from || filterState.value.date_to) count++;
    return count;
});

function getAreaName(areaId) {
    const area = props.areas?.find(a => a.id == areaId);
    return area ? area.name : `Area #${areaId}`;
}

// ── Bulk Selection & Delete ──────────────────────────────────
const selectedIds = ref([]);

const isAllSelected = computed(() => {
    if (!props.olts?.data || props.olts.data.length === 0) return false;
    return props.olts.data.every(olt => selectedIds.value.includes(olt.id));
});

function toggleSelectAll() {
    if (!props.olts?.data) return;
    if (isAllSelected.value) {
        const pageIds = props.olts.data.map(o => o.id);
        selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id));
    } else {
        const newIds = props.olts.data.map(o => o.id);
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...newIds]));
    }
}

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
        alert('Ketik HAPUS untuk konfirmasi hapus semua data OLT.');
        return;
    }

    isDeleting.value = true;
    router.post('/olts/bulk-destroy', {
        all: deleteConfirmMode.value === 'all',
        ids: deleteConfirmMode.value === 'selected' ? selectedIds.value : [],
        search: filterState.value.search,
        status: filterState.value.status,
        area_id: filterState.value.area_id,
        brand: filterState.value.brand,
        pon_ports: filterState.value.pon_ports,
        odc_status: filterState.value.odc_status,
        date_from: filterState.value.date_from,
        date_to: filterState.value.date_to,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
}
</script>
