<template>
    <AppLayout title="Data ODC">
        <div class="p-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ODC</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data Optical Distribution Cabinet (ODC)</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Tombol Hapus Terpilih -->
                    <button 
                        v-if="selectedIds.length > 0 && (hasPermission('network_odc_delete') || hasPermission('network_odc_delete_all'))"
                        @click="openDeleteModal('selected')"
                        type="button"
                        class="px-3.5 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-semibold text-sm rounded-lg border border-red-200 shadow-xs flex items-center gap-2 transition-all"
                    >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih ({{ selectedIds.length }})</span>
                    </button>

                    <!-- Tombol Hapus Semua -->
                    <button 
                        v-if="hasPermission('network_odc_delete_all')"
                        @click="openDeleteModal('all')"
                        type="button"
                        class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg shadow-sm flex items-center gap-2 transition-all"
                        title="Hapus Seluruh Data ODC"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Semua</span>
                    </button>

                    <!-- Tombol Tambah ODC -->
                    <button 
                        v-if="hasPermission('network_odc_create')"
                        @click="openCreateModal" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah ODC</span>
                    </button>
                </div>
            </div>

            <!-- 1. Statistik Cards ODC -->
            <div v-if="hasPermission('network_odc_card_stats')" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
                <!-- Card 1: Total ODC -->
                <div 
                    @click="setQuickFilter('all')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[!filterState.status && !filterState.odp_status ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/20' : 'border-gray-200/80 hover:border-blue-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-blue-600 transition-colors">Total ODC</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-100/70 text-blue-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900">{{ Number(stats?.total || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Kapasitas: {{ Number(stats?.total_capacity || 0).toLocaleString('id-ID') }} Port</p>
                </div>

                <!-- Card 2: ODC Aktif -->
                <div 
                    @click="setQuickFilter('active')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-gray-200/80 hover:border-emerald-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-emerald-600 transition-colors">ODC Aktif</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-100/70 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600">{{ Number(stats?.active || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">{{ stats?.total ? Math.round((stats.active / stats.total) * 100) : 0 }}% Beroperasi Normal</p>
                </div>

                <!-- Card 3: Total Kapasitas Port Splitter -->
                <div 
                    class="bg-white rounded-2xl p-4 border border-gray-200/80 transition-all duration-200 shadow-xs hover:shadow-md"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Kapasitas</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-100/70 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zM9 9h6M9 13h6M9 17h4"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600">{{ Number(stats?.total_capacity || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Port Splitter ODC</p>
                </div>

                <!-- Card 4: Terhubung ODP -->
                <div 
                    @click="setQuickFilter('has_odp')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.odp_status === 'has_odp' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/20' : 'border-gray-200/80 hover:border-purple-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-purple-600 transition-colors">Terhubung ODP</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-100/70 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-purple-600">{{ Number(stats?.has_odp || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">Distribusi ke ODP</p>
                </div>

                <!-- Card 5: Inaktif / Maint -->
                <div 
                    @click="setQuickFilter('inactive')" 
                    class="bg-white rounded-2xl p-4 border transition-all duration-200 cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="[filterState.status === 'inactive_maintenance' || filterState.status === 'inactive' ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/20' : 'border-gray-200/80 hover:border-rose-300']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 group-hover:text-rose-600 transition-colors">Inaktif / Maint</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-100/70 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600">{{ Number(stats?.inactive || 0).toLocaleString('id-ID') }}</div>
                    <p class="text-[11px] text-gray-500 mt-1">{{ stats?.inactive > 0 ? 'Perlu perhatian' : 'Semua Beroperasi' }}</p>
                </div>
            </div>

            <!-- 2. Smart Quick Status Tabs -->
            <div v-if="hasPermission('network_odc_filter')" class="flex items-center gap-1.5 p-1.5 bg-slate-100/80 rounded-2xl mb-4 overflow-x-auto border border-slate-200/60 scrollbar-none">
                <button 
                    type="button" 
                    @click="setQuickFilter('all')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[!filterState.status && !filterState.odp_status ? 'bg-white text-gray-900 shadow-xs ring-1 ring-black/5 font-extrabold' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50']"
                >
                    <span>Semua</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="[!filterState.status && !filterState.odp_status ? 'bg-gray-100 text-gray-800' : 'bg-gray-200/70 text-gray-600']">{{ stats?.total || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('active')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'active' ? 'bg-white text-emerald-700 shadow-xs ring-1 ring-emerald-500/20 font-extrabold' : 'text-gray-600 hover:text-emerald-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Aktif (Normal)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 font-bold">{{ stats?.active || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('inactive')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.status === 'inactive_maintenance' || filterState.status === 'inactive' ? 'bg-white text-rose-700 shadow-xs ring-1 ring-rose-500/20 font-extrabold' : 'text-gray-600 hover:text-rose-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Inaktif / Maintenance</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 font-bold">{{ stats?.inactive || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('has_odp')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.odp_status === 'has_odp' ? 'bg-white text-purple-700 shadow-xs ring-1 ring-purple-500/20 font-extrabold' : 'text-gray-600 hover:text-purple-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Dengan ODP</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-50 text-purple-700 font-bold">{{ stats?.has_odp || 0 }}</span>
                </button>
                <button 
                    type="button" 
                    @click="setQuickFilter('no_odp')" 
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="[filterState.odp_status === 'no_odp' ? 'bg-white text-amber-700 shadow-xs ring-1 ring-amber-500/20 font-extrabold' : 'text-gray-600 hover:text-amber-700 hover:bg-white/50']"
                >
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Belum Ada ODP</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 font-bold">{{ stats?.no_odp || 0 }}</span>
                </button>
            </div>

            <!-- 3. Smart Search & Filter Bar -->
            <div v-if="hasPermission('network_odc_filter')" class="mb-5 bg-white p-4 rounded-2xl shadow-xs border border-gray-200/80 space-y-3.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                    <!-- Search Input -->
                    <div class="lg:col-span-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input 
                                v-model="filterState.search" 
                                type="text" 
                                class="w-full pl-10 pr-9 py-2 bg-gray-50/60 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all placeholder:text-gray-400" 
                                placeholder="Cari nama, lokasi, deskripsi..." 
                            />
                            <button 
                                v-if="filterState.search"
                                @click="filterState.search = ''"
                                type="button"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Area Dropdown -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterState.area_id" 
                            class="w-full px-3 py-2 bg-gray-50/60 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-gray-700"
                        >
                            <option value="">Semua Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>

                    <!-- OLT Dropdown -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterState.olt_id" 
                            class="w-full px-3 py-2 bg-gray-50/60 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-gray-700"
                        >
                            <option value="">Semua OLT</option>
                            <option v-for="olt in olts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                        </select>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterState.status" 
                            class="w-full px-3 py-2 bg-gray-50/60 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-gray-700"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Active (Normal)</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <!-- Filter Toggle & Reset -->
                    <div class="lg:col-span-2 flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="isAdvancedDrawerOpen = !isAdvancedDrawerOpen"
                            class="flex-1 px-3 py-2 border rounded-xl text-sm font-semibold flex items-center justify-center gap-1.5 transition-all"
                            :class="[isAdvancedDrawerOpen || isAdvancedFilterActive ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50']"
                        >
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            <span>Filter</span>
                            <span v-if="activeFilterCount > 0" class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[11px] font-bold flex items-center justify-center">{{ activeFilterCount }}</span>
                        </button>
                        
                        <button 
                            v-if="activeFilterCount > 0"
                            type="button"
                            @click="resetFilters"
                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl border border-transparent hover:border-red-200 transition-all"
                            title="Reset Semua Filter"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <!-- 4. Laci Filter Lanjutan (Collapsible Drawer) -->
                <div v-show="isAdvancedDrawerOpen" class="pt-3 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                    <!-- Tipe ODC -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1">Tipe ODC</label>
                        <select v-model="filterState.type" class="w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                            <option value="">Semua Tipe</option>
                            <option value="Normal">Normal</option>
                            <option value="Split">Split</option>
                            <option value="Pedestal">Pedestal</option>
                            <option value="Pole Mount">Pole Mount</option>
                        </select>
                    </div>

                    <!-- Kapasitas Port -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1">Kapasitas Spesifik</label>
                        <select v-model="filterState.capacity" class="w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                            <option value="">Semua Kapasitas</option>
                            <option v-for="cap in capacities" :key="cap" :value="cap">{{ cap }} Port</option>
                        </select>
                    </div>

                    <!-- Relasi ODP -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1">Downstream ODP</label>
                        <select v-model="filterState.odp_status" class="w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                            <option value="">Semua Relasi</option>
                            <option value="has_odp">Memiliki ODP (Terhubung)</option>
                            <option value="no_odp">Belum Ada ODP</option>
                        </select>
                    </div>

                    <!-- Rentang Tanggal -->
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1">Tanggal Input</label>
                        <div class="flex items-center gap-1.5">
                            <input v-model="filterState.date_from" type="date" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded-lg text-xs" />
                            <span class="text-gray-400 text-xs">-</span>
                            <input v-model="filterState.date_to" type="date" class="w-full px-2 py-1 bg-gray-50 border border-gray-200 rounded-lg text-xs" />
                        </div>
                    </div>

                    <!-- Limit Data per Halaman -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1">Data per Halaman</label>
                        <select v-model="filterState.per_page" class="w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                            <option value="10">10 data</option>
                            <option value="15">15 data</option>
                            <option value="25">25 data</option>
                            <option value="50">50 data</option>
                            <option value="100">100 data</option>
                            <option value="250">250 data</option>
                        </select>
                    </div>
                </div>

                <!-- 5. Active Filter Chips -->
                <div v-if="activeFilterCount > 0" class="pt-2 flex flex-wrap items-center gap-1.5 text-xs text-gray-500">
                    <span class="font-medium text-gray-400">Filter Aktif:</span>
                    
                    <span v-if="filterState.search" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold border border-indigo-200">
                        Cari: "{{ filterState.search }}"
                        <button type="button" @click="filterState.search = ''" class="hover:text-indigo-900">&times;</button>
                    </span>

                    <span v-if="filterState.area_id" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-200">
                        Area: {{ areas.find(a => a.id == filterState.area_id)?.name }}
                        <button type="button" @click="filterState.area_id = ''" class="hover:text-blue-900">&times;</button>
                    </span>

                    <span v-if="filterState.olt_id" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-violet-50 text-violet-700 font-semibold border border-violet-200">
                        OLT: {{ olts.find(o => o.id == filterState.olt_id)?.name }}
                        <button type="button" @click="filterState.olt_id = ''" class="hover:text-violet-900">&times;</button>
                    </span>

                    <span v-if="filterState.status" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
                        Status: {{ filterState.status }}
                        <button type="button" @click="filterState.status = ''" class="hover:text-emerald-900">&times;</button>
                    </span>

                    <span v-if="filterState.capacity" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 font-semibold border border-purple-200">
                        Kapasitas: {{ filterState.capacity }} Port
                        <button type="button" @click="filterState.capacity = ''" class="hover:text-purple-900">&times;</button>
                    </span>

                    <span v-if="filterState.type" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold border border-amber-200">
                        Tipe: {{ filterState.type }}
                        <button type="button" @click="filterState.type = ''" class="hover:text-amber-900">&times;</button>
                    </span>

                    <span v-if="filterState.odp_status" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-cyan-50 text-cyan-700 font-semibold border border-cyan-200">
                        ODP: {{ filterState.odp_status === 'has_odp' ? 'Ada ODP' : 'Tanpa ODP' }}
                        <button type="button" @click="filterState.odp_status = ''" class="hover:text-cyan-900">&times;</button>
                    </span>

                    <button type="button" @click="resetFilters" class="text-xs text-red-600 hover:underline font-semibold ml-1">
                        Hapus Semua Filter
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-200/80">
                                <th class="w-10 px-4 py-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllPageSelected" 
                                        @change="toggleSelectAllPage"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer w-4 h-4"
                                        title="Pilih Semua di Halaman Ini"
                                    />
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama ODC</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Induk OLT</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kapasitas</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr 
                                v-for="odc in odcs.data" 
                                :key="odc.id" 
                                class="hover:bg-indigo-50/20 transition-colors"
                                :class="{'bg-indigo-50/40': selectedIds.includes(odc.id)}"
                            >
                                <td class="px-4 py-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="selectedIds.includes(odc.id)" 
                                        @change="toggleSelectRow(odc.id)"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer w-4 h-4"
                                    />
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <div class="font-bold text-gray-900">{{ odc.name }}</div>
                                        <span v-if="odc.type && odc.type !== 'Normal'" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            {{ odc.type }}
                                        </span>
                                    </div>
                                    <div v-if="odc.location" class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ odc.location }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span v-if="odc.area" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-bold border border-blue-100">{{ odc.area.name }}</span>
                                    <span v-else class="text-xs text-gray-400 italic">Belum diset</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div class="font-bold text-gray-900">{{ odc.olt ? odc.olt.name : '-' }}</div>
                                    <div v-if="odc.pon_port" class="mt-1">
                                        <span class="bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded text-[11px] font-bold border border-indigo-200">
                                            PON {{ odc.pon_port }}
                                        </span>
                                        <div v-if="getPonVlans(odc).length > 0" class="flex flex-wrap gap-1 mt-1">
                                            <span v-for="(v, vi) in getPonVlans(odc)" :key="vi" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                                <span>{{ v.vlan_id }}</span>
                                                <span class="opacity-75">· {{ v.name }}</span>
                                                <span class="px-1 py-0.2 rounded text-[9px] bg-white/70 font-bold uppercase">{{ v.access_mode }}</span>
                                            </span>
                                        </div>
                                        <div v-else class="text-[11px] text-gray-400 italic mt-0.5">
                                            (Belum diset VLAN di PON {{ odc.pon_port }})
                                        </div>
                                    </div>
                                    <div v-else class="text-[11px] text-gray-400 italic mt-0.5">
                                        Port PON belum dipilih
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div class="font-bold text-gray-900">{{ odc.capacity }} Port</div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span v-if="odc.splitter_ratio" class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            Splitter {{ odc.splitter_ratio }}
                                        </span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700">
                                            {{ odc.odps_count || 0 }} ODP
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="odc.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-700 border border-gray-200'">
                                        {{ odc.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                    <Link :href="`/odcs/${odc.id}`" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                    <button v-if="hasPermission('network_odc_edit')" @click="openEditModal(odc)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                    <button v-if="hasPermission('network_odc_delete')" @click="openDeleteModal('single', odc)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="!odcs.data || odcs.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <p class="font-medium text-gray-700">Tidak ada data ODC yang ditemukan</p>
                                    <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau ubah filter Anda.</p>
                                    <button 
                                        v-if="activeFilterCount > 0" 
                                        @click="resetFilters" 
                                        type="button" 
                                        class="mt-3 px-3.5 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-xs rounded-lg transition-all"
                                    >
                                        Reset Filter
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div v-if="odcs.total > 0" class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-gray-500">
                        Menampilkan <span class="font-bold text-gray-900">{{ odcs.from || 0 }}</span> sampai <span class="font-bold text-gray-900">{{ odcs.to || 0 }}</span> dari <span class="font-bold text-gray-900">{{ odcs.total }}</span> data ODC
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in odcs.links" :key="i">
                            <Link 
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                                :class="link.active ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'"
                                v-html="link.label"
                                preserve-scroll
                            />
                            <span 
                                v-else
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-400 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Floating Selection Banner -->
            <div 
                v-if="selectedIds.length > 0" 
                class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-40 bg-gray-900 text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-4 border border-gray-700/80 animate-fade-in"
            >
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">{{ selectedIds.length }}</span>
                    <span class="text-sm font-semibold">ODC Dipilih</span>
                </div>
                <div class="h-4 w-px bg-gray-700"></div>
                <button 
                    @click="openDeleteModal('selected')" 
                    type="button"
                    class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5 transition-all"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus Terpilih</span>
                </button>
                <button 
                    @click="selectedIds = []" 
                    type="button"
                    class="px-2.5 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-xl text-xs font-medium transition-all"
                >
                    Batal
                </button>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Ganda & Aman (Safety Confirmation Modal) -->
        <div v-if="deleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 animate-scale-up">
                <div class="p-6">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900">
                        {{ deleteMode === 'all' ? 'Konfirmasi Hapus SEMUA Data ODC' : (deleteMode === 'selected' ? `Konfirmasi Hapus ${selectedIds.length} Data ODC Terpilih` : `Hapus Unit ODC: ${targetDeleteOdc?.name}`) }}
                    </h3>

                    <div class="mt-2.5 text-sm text-gray-600 space-y-2">
                        <p v-if="deleteMode === 'all'">
                            Tindakan ini akan menghapus <strong>seluruh data ODC</strong> yang sesuai dengan filter hak akses Anda.
                        </p>
                        <p v-else-if="deleteMode === 'selected'">
                            Tindakan ini akan menghapus <strong>{{ selectedIds.length }} unit ODC</strong> yang Anda pilih.
                        </p>
                        <p v-else>
                            Apakah Anda yakin ingin menghapus data ODC <strong>{{ targetDeleteOdc?.name }}</strong>?
                        </p>

                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Perlindungan Relasi Downstream ODP
                            </div>
                            <p>ODP yang terhubung ke ODC ini akan dilepaskan (unassigned) secara aman agar data ODP dan pelanggan ONT yang ada di dalamnya tidak rusak atau hilang.</p>
                        </div>

                        <!-- Safety input confirmation for ALL mode -->
                        <div v-if="deleteMode === 'all'" class="pt-3">
                            <label class="block text-xs font-bold text-red-600 uppercase tracking-wider mb-1.5">
                                Ketik kata "HAPUS" untuk konfirmasi penghapusan:
                            </label>
                            <input 
                                v-model="deleteConfirmationText" 
                                type="text" 
                                placeholder="Ketik HAPUS di sini" 
                                class="w-full px-3.5 py-2.5 border-2 border-red-300 rounded-xl text-sm font-bold text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 uppercase tracking-wider"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="closeDeleteModal" 
                            :disabled="isDeleting"
                            class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all"
                        >
                            Batal
                        </button>
                        <button 
                            type="button" 
                            @click="confirmDeleteAction" 
                            :disabled="isDeleting || (deleteMode === 'all' && deleteConfirmationText !== 'HAPUS')"
                            class="px-5 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl shadow-xs transition-all flex items-center gap-2"
                        >
                            <svg v-if="isDeleting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>{{ isDeleting ? 'Menghapus...' : (deleteMode === 'all' ? 'Hapus Semua ODC' : 'Konfirmasi Hapus') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit ODC -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit ODC' : 'Tambah ODC Baru' }}</h3>
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
                                <span class="text-sm font-semibold text-indigo-900">Informasi Dasar</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-if="form.type !== 'Split'">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODC</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ODC-01" />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Area / Wilayah</label>
                                    <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="">Pilih Area</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                                </div>
                                <div :class="form.type === 'Split' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Induk OLT</label>
                                    <select v-model="form.olt_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required :disabled="!form.area_id">
                                        <option value="">Pilih OLT Induk</option>
                                        <option v-for="olt in filteredOlts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                                    </select>
                                    <p v-if="form.errors.olt_id" class="text-red-500 text-xs mt-1">{{ form.errors.olt_id }}</p>
                                </div>
                                <div v-if="form.olt_id" :class="form.type === 'Split' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port PON (Opsional)</label>
                                    <select v-model="form.pon_port" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Pilih Port PON (Opsional)</option>
                                        <option v-for="port in selectedOltPonPorts" :key="port" :value="port">PON {{ port }}</option>
                                    </select>
                                    <p v-if="form.errors.pon_port" class="text-red-500 text-xs mt-1">{{ form.errors.pon_port }}</p>
                                </div>

                                <!-- Preview Sinkronisasi VLAN & Mode Akses dari OLT -->
                                <div v-if="form.olt_id && form.pon_port" class="col-span-1 sm:col-span-2 bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Sinkronisasi VLAN & Mode Akses dari OLT (PON {{ form.pon_port }})
                                        </span>
                                        <span class="text-[11px] text-blue-700 font-semibold">{{ currentModalPonVlans.length }} VLAN Terdeteksi</span>
                                    </div>
                                    <div v-if="currentModalPonVlans.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                                        <span v-for="(v, vi) in currentModalPonVlans" :key="vi" class="px-2 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1.5 bg-white shadow-2xs" :class="getModeBadgeClass(v.access_mode)">
                                            <span class="font-mono font-bold">{{ v.vlan_id }}</span>
                                            <span class="text-gray-700">· {{ v.name }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border bg-gray-50">{{ v.access_mode }}</span>
                                        </span>
                                    </div>
                                    <div v-else class="text-xs text-blue-600/80 italic">
                                        Belum ada VLAN diset di OLT untuk PON {{ form.pon_port }}. ODC ini akan otomatis tersinkron begitu VLAN diset di OLT.
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tipe ODC</label>
                                        <select v-model="form.type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                            <option value="Normal">Normal</option>
                                            <option value="Split">Split</option>
                                        </select>
                                        <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Rasio Splitter</label>
                                        <input v-model="form.splitter_ratio" type="text" placeholder="misal: 1:4, 1:8, 1:16" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        <p v-if="form.errors.splitter_ratio" class="text-red-500 text-xs mt-1">{{ form.errors.splitter_ratio }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas (Port)</label>
                                        <input v-model="form.capacity" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                        <p v-if="form.errors.capacity" class="text-red-500 text-xs mt-1">{{ form.errors.capacity }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Split -->
                        <div v-if="form.type === 'Split'" class="space-y-4">
                            <!-- Alert Split -->
                            <div class="bg-indigo-50 text-indigo-800 px-4 py-3 rounded-lg border border-indigo-200 text-sm flex items-start gap-3">
                                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p><span class="font-bold">Split:</span> ODC dibagi menjadi 2 unit dengan konfigurasi khusus.</p>
                                    <p class="text-xs mt-1 opacity-80" :class="totalSplitPorts > form.capacity ? 'text-red-600 font-bold' : ''">
                                        Total Kapasitas Unit: {{ totalSplitPorts }} / {{ form.capacity }} 
                                        <span v-if="totalSplitPorts > form.capacity">(Melebihi kapasitas utama!)</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="flex gap-2 bg-gray-100 p-1 rounded-lg">
                                <button type="button" @click="activeTab = 0" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 0 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODC Induk (Kapasitas: {{ form.split_units[0].ratio }})
                                </button>
                                <button type="button" @click="activeTab = 1" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 1 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODC Lanjutan (Kapasitas: {{ form.split_units[1].ratio }})
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="border border-indigo-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-indigo-100 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span class="text-sm font-semibold text-indigo-900">
                                        {{ activeTab === 0 ? 'ODC Induk' : 'ODC Lanjutan' }}
                                    </span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODC Unit</label>
                                            <input v-model="form.split_units[activeTab].name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas</label>
                                            <input v-model="form.split_units[activeTab].ratio" type="number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat ODC</label>
                                        <textarea v-model="form.split_units[activeTab].location" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Alamat lokasi ODC"></textarea>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Titik Koordinat</label>
                                            <button type="button" @click="getLocationForSplit(activeTab)" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Auto GPS
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input v-model="form.split_units[activeTab].latitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Latitude" />
                                            <input v-model="form.split_units[activeTab].longitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Longitude" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Titik Awal</label>
                                            <input v-model="form.split_units[activeTab].start_point" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Titik Akhir Kabel</label>
                                            <input v-model="form.split_units[activeTab].end_point" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Penarikan Kabel</label>
                                        <select v-model="form.split_units[activeTab].cable_pull" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="Individu">Individu</option>
                                            <option value="Bersama">Bersama</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODC</label>
                                        <input type="file" @change="e => form.split_units[activeTab].photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" accept="image/*" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Normal -->
                        <div v-if="form.type === 'Normal'" class="space-y-6">
                            <!-- Lokasi -->
                            <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-green-50/50 px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-green-900">Lokasi & Koordinat</span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat ODC</label>
                                        <textarea v-model="form.location" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Masukkan alamat lengkap"></textarea>
                                        <p v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Titik Koordinat</label>
                                            <button type="button" @click="getLocation" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Auto GPS
                                            </button>
                                        </div>
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
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODC</label>
                                        <input type="file" @change="e => form.photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" accept="image/*" />
                                        <p v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Keterangan Tambahan</label>
                                        <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
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
                            Simpan ODC
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odcs: Object,
    olts: Array,
    areas: Array,
    capacities: Array,
    types: Array,
    stats: Object,
    filters: Object
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions || []);
const roles = computed(() => page.props.auth?.user?.roles || []);
const isAdmin = computed(() => roles.value.includes('admin') || roles.value.includes('Super Admin'));
const hasPermission = (perm) => isAdmin.value || permissions.value.includes(perm);

// Filter State & Drawer
const isAdvancedDrawerOpen = ref(false);
const filterState = reactive({
    search: props.filters?.search || '',
    area_id: props.filters?.area_id || '',
    olt_id: props.filters?.olt_id || '',
    status: props.filters?.status || '',
    capacity: props.filters?.capacity || '',
    type: props.filters?.type || '',
    odp_status: props.filters?.odp_status || '',
    min_capacity: props.filters?.min_capacity || '',
    max_capacity: props.filters?.max_capacity || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    per_page: props.filters?.per_page || '15',
});

let searchDebounceTimer = null;
const applyFilters = (preservePagination = false) => {
    const query = {};
    Object.keys(filterState).forEach(key => {
        if (filterState[key] !== '' && filterState[key] !== null && filterState[key] !== undefined) {
            query[key] = filterState[key];
        }
    });

    if (!preservePagination) {
        delete query.page;
    }

    router.get('/odcs', query, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

watch(
    () => [
        filterState.area_id,
        filterState.olt_id,
        filterState.status,
        filterState.capacity,
        filterState.type,
        filterState.odp_status,
        filterState.min_capacity,
        filterState.max_capacity,
        filterState.date_from,
        filterState.date_to,
        filterState.per_page,
    ],
    () => {
        applyFilters(false);
    }
);

watch(
    () => filterState.search,
    () => {
        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            applyFilters(false);
        }, 350);
    }
);

const setQuickFilter = (type) => {
    if (type === 'all') {
        filterState.status = '';
        filterState.odp_status = '';
    } else if (type === 'active') {
        filterState.status = 'active';
        filterState.odp_status = '';
    } else if (type === 'inactive') {
        filterState.status = 'inactive_maintenance';
        filterState.odp_status = '';
    } else if (type === 'has_odp') {
        filterState.odp_status = 'has_odp';
        filterState.status = '';
    } else if (type === 'no_odp') {
        filterState.odp_status = 'no_odp';
        filterState.status = '';
    }
};

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterState.search) count++;
    if (filterState.area_id) count++;
    if (filterState.olt_id) count++;
    if (filterState.status) count++;
    if (filterState.capacity) count++;
    if (filterState.type) count++;
    if (filterState.odp_status) count++;
    if (filterState.min_capacity || filterState.max_capacity) count++;
    if (filterState.date_from || filterState.date_to) count++;
    return count;
});

const isAdvancedFilterActive = computed(() => {
    return !!(
        filterState.type ||
        filterState.odp_status ||
        filterState.min_capacity ||
        filterState.max_capacity ||
        filterState.date_from ||
        filterState.date_to ||
        (filterState.per_page && filterState.per_page !== '15')
    );
});

const resetFilters = () => {
    filterState.search = '';
    filterState.area_id = '';
    filterState.olt_id = '';
    filterState.status = '';
    filterState.capacity = '';
    filterState.type = '';
    filterState.odp_status = '';
    filterState.min_capacity = '';
    filterState.max_capacity = '';
    filterState.date_from = '';
    filterState.date_to = '';
    filterState.per_page = '15';
    applyFilters(false);
};

// Selection State for Bulk Actions
const selectedIds = ref([]);

const isAllPageSelected = computed(() => {
    if (!props.odcs?.data || props.odcs.data.length === 0) return false;
    return props.odcs.data.every(odc => selectedIds.value.includes(odc.id));
});

const toggleSelectAllPage = () => {
    if (isAllPageSelected.value) {
        const pageIds = props.odcs.data.map(o => o.id);
        selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id));
    } else {
        const pageIds = props.odcs.data.map(o => o.id);
        const combined = new Set([...selectedIds.value, ...pageIds]);
        selectedIds.value = Array.from(combined);
    }
};

const toggleSelectRow = (id) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(item => item !== id);
    } else {
        selectedIds.value.push(id);
    }
};

// Modal Delete State
const deleteModalOpen = ref(false);
const deleteMode = ref('selected'); // 'single' | 'selected' | 'all'
const targetDeleteOdc = ref(null);
const deleteConfirmationText = ref('');
const isDeleting = ref(false);

const openDeleteModal = (mode, odc = null) => {
    deleteMode.value = mode;
    targetDeleteOdc.value = odc;
    deleteConfirmationText.value = '';
    deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    deleteModalOpen.value = false;
    deleteConfirmationText.value = '';
    targetDeleteOdc.value = null;
};

const confirmDeleteAction = () => {
    if (deleteMode.value === 'all' && deleteConfirmationText.value.trim() !== 'HAPUS') {
        alert('Harap ketik "HAPUS" untuk konfirmasi.');
        return;
    }

    isDeleting.value = true;

    if (deleteMode.value === 'single' && targetDeleteOdc.value) {
        router.post(`/odcs/${targetDeleteOdc.value.id}/delete`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                isDeleting.value = false;
            },
            onError: () => {
                isDeleting.value = false;
            }
        });
    } else if (deleteMode.value === 'selected') {
        router.post('/odcs/bulk-destroy', {
            ids: selectedIds.value,
            all: false
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                closeDeleteModal();
                isDeleting.value = false;
            },
            onError: () => {
                isDeleting.value = false;
            }
        });
    } else if (deleteMode.value === 'all') {
        router.post('/odcs/bulk-destroy', {
            all: true,
            search: filterState.search,
            area_id: filterState.area_id,
            olt_id: filterState.olt_id,
            status: filterState.status,
            capacity: filterState.capacity,
            type: filterState.type,
            odp_status: filterState.odp_status,
            min_capacity: filterState.min_capacity,
            max_capacity: filterState.max_capacity,
            date_from: filterState.date_from,
            date_to: filterState.date_to,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                closeDeleteModal();
                isDeleting.value = false;
            },
            onError: () => {
                isDeleting.value = false;
            }
        });
    }
};

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const activeTab = ref(0);

const form = useForm({
    name: '',
    olt_id: '',
    pon_port: '',
    splitter_ratio: '1:4',
    area_id: '',
    type: 'Normal',
    capacity: 144,
    location: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: '',
    photo: null,
    split_units: [
        {
            name: 'ODC Induk',
            ratio: 72,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODC Induk',
            cable_pull: 'Individu',
            photo: null
        },
        {
            name: 'ODC Lanjutan',
            ratio: 72,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODC Lanjutan',
            cable_pull: 'Individu',
            photo: null
        }
    ]
});

// Initialize default ratio when type changes to Split
watch(() => form.type, (newType) => {
    if (newType === 'Split') {
        const half = Math.floor(form.capacity / 2);
        form.split_units[0].ratio = half;
        form.split_units[1].ratio = half;
    }
});

const selectedOltPonPorts = computed(() => {
    if (!form.olt_id) return [];
    const olt = props.olts.find(o => o.id === form.olt_id);
    if (!olt || !olt.total_pon_ports) return [];
    return Array.from({ length: olt.total_pon_ports }, (_, i) => i + 1);
});

// Watch changes to olt_id to reset pon_port if necessary
watch(() => form.olt_id, (newVal) => {
    if (newVal) {
        const olt = props.olts.find(o => o.id === newVal);
        if (olt && olt.total_pon_ports && form.pon_port > olt.total_pon_ports) {
            form.pon_port = '';
        }
    } else {
        form.pon_port = '';
    }
});

const filteredOlts = computed(() => {
    if (!form.area_id) return [];
    return props.olts.filter(o => o.area_id == form.area_id);
});

watch(() => form.area_id, (newVal) => {
    // Reset OLT if the selected OLT doesn't belong to the new Area
    if (form.olt_id) {
        const olt = props.olts.find(o => o.id === form.olt_id);
        if (olt && olt.area_id != newVal) {
            form.olt_id = '';
        }
    }
});

const totalSplitPorts = computed(() => {
    return Number(form.split_units[0].ratio || 0) + Number(form.split_units[1].ratio || 0);
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function openEditModal(odc) {
    isEditing.value = true;
    editId.value = odc.id;
    form.name = odc.name;
    form.olt_id = odc.olt_id;
    form.pon_port = odc.pon_port || '';
    form.splitter_ratio = odc.splitter_ratio || '1:4';
    form.area_id = odc.area_id || '';
    form.type = odc.type || 'Normal';
    form.capacity = odc.capacity;
    form.location = odc.location;
    form.latitude = odc.latitude;
    form.longitude = odc.longitude;
    form.status = odc.status;
    form.description = odc.description;
    form.photo = null;
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.latitude = position.coords.latitude.toFixed(8);
                form.longitude = position.coords.longitude.toFixed(8);
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
}

function getLocationForSplit(tabIndex) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.split_units[tabIndex].latitude = position.coords.latitude.toFixed(8);
                form.split_units[tabIndex].longitude = position.coords.longitude.toFixed(8);
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
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

function getModeBadgeClass(mode) {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'HOTSPOT':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'STATIC':
        case 'STATIC IP':
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

function getPonVlans(odc) {
    if (!odc || !odc.olt || !odc.pon_port || !odc.olt.pon_vlans) return [];
    let vlans = odc.olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    
    const ponData = vlans.find(p => p.port == odc.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
}

const currentModalPonVlans = computed(() => {
    if (!form.olt_id || !form.pon_port) return [];
    const olt = props.olts.find(o => o.id === form.olt_id);
    if (!olt || !olt.pon_vlans) return [];
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const ponData = vlans.find(p => p.port == form.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
});

function submit() {
    if (isEditing.value) {
        form.post(`/odcs/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form._method = 'post';
        form.post('/odcs', {
            onSuccess: () => closeModal(),
        });
    }
}
</script>
