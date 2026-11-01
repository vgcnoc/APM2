<template>
    <AppLayout title="Data Material / Barang" subtitle="Kelola persediaan material infrastruktur jaringan">
        <div class="space-y-6">
            <!-- Summary Cards (Interaktif & Indikator Status) -->
            <div v-can="'materials_card_stats'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Item -->
                <div 
                    @click="setQuickStockStatus('')"
                    class="glass-card p-5 border-2 transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5"
                    :class="!filterStockStatus ? 'border-blue-500/50 ring-2 ring-blue-500/30 bg-blue-50/20 shadow-sm' : 'border-blue-500/10 hover:border-blue-500/30'"
                    title="Klik untuk tampilkan semua material"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Item</p>
                            <p class="text-2xl font-bold text-gray-900">{{ summary?.total_items || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Nilai Aset -->
                <div v-can="'materials_field_price'" class="glass-card p-5 border-2 border-emerald-500/10 hover:border-emerald-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Nilai Aset</p>
                            <p class="text-xl font-bold text-gray-900">Rp {{ formatNumber(summary?.total_value || 0) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Stok Menipis -->
                <div 
                    @click="setQuickStockStatus('low')"
                    class="glass-card p-5 border-2 transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5"
                    :class="filterStockStatus === 'low' ? 'border-amber-500/50 ring-2 ring-amber-500/30 bg-amber-50/20 shadow-sm' : 'border-amber-500/10 hover:border-amber-500/30'"
                    title="Klik untuk filter stok menipis (1-10)"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Stok Menipis</p>
                            <p class="text-2xl font-bold text-gray-900">{{ summary?.low_stock || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Stok Habis -->
                <div 
                    @click="setQuickStockStatus('empty')"
                    class="glass-card p-5 border-2 transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5"
                    :class="filterStockStatus === 'empty' ? 'border-red-500/50 ring-2 ring-red-500/30 bg-red-50/20 shadow-sm' : 'border-red-500/10 hover:border-red-500/30'"
                    title="Klik untuk filter stok habis (0)"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-red-100 text-red-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Stok Habis</p>
                            <p class="text-2xl font-bold text-gray-900">{{ summary?.out_of_stock || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Actions & Filters (Rapi, Terstruktur & Responsif Dua Baris) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3.5 mb-3">
                <!-- Baris 1: Pencarian & Tombol Aksi Utama -->
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari nama barang, kategori, supplier..." 
                            class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm transition-all shadow-2xs"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <button 
                            v-if="search" 
                            @click="search = ''; performSearch()" 
                            type="button" 
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-colors"
                            title="Hapus pencarian"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2.5 flex-wrap justify-end">
                        <button 
                            v-can="'materials_delete'"
                            v-if="selectedItems.length > 0"
                            @click="deleteSelected"
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 font-semibold text-xs shadow-2xs transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus ({{ selectedItems.length }})
                        </button>

                        <button 
                            type="button" 
                            @click="openQuickStatusModal()" 
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-amber-300 text-amber-900 bg-amber-50 hover:bg-amber-100 font-bold text-xs shadow-xs hover:shadow-sm transition-all whitespace-nowrap cursor-pointer"
                            title="Catat Status Material (Layak Pakai / Rusak / Retur)"
                        >
                            <span class="text-amber-600 font-black text-sm">⚡</span>
                            <span>Buat Status Material</span>
                        </button>

                        <button 
                            v-can="'materials_create'" 
                            type="button"
                            @click="openModal()" 
                            class="btn-primary inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold shadow-xs whitespace-nowrap cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Tambah Material</span>
                        </button>
                    </div>
                </div>

                <!-- Baris 2: Toolbar Filter Dropdown (Kategori, Area/Gudang, Status Stok, Reset) -->
                <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center gap-2.5">
                    <!-- Filter Kategori -->
                    <div v-can="'materials_filter_category'" class="relative flex items-center bg-gray-50 border border-gray-200/80 rounded-xl pl-3 pr-2 py-1 shadow-2xs min-w-[170px] flex-1 sm:flex-initial">
                        <div class="shrink-0 mr-2 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <div class="flex flex-col flex-1 min-w-0">
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider leading-none mt-0.5">Kategori</span>
                            <select v-model="filterCategory" @change="performSearch" class="form-select border-0 pt-0 pb-0.5 pl-0 !pr-6 h-auto text-xs bg-transparent focus:ring-0 text-gray-700 font-semibold w-full cursor-pointer truncate">
                                <option value="all">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter Gudang / Area -->
                    <div v-can="'materials_filter_area'" class="relative flex items-center bg-gray-50 border border-gray-200/80 rounded-xl pl-3 pr-2 py-1 shadow-2xs min-w-[175px] flex-1 sm:flex-initial">
                        <div class="shrink-0 mr-2 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="flex flex-col flex-1 min-w-0">
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider leading-none mt-0.5">Gudang / Area</span>
                            <select v-model="filterArea" @change="performSearch" class="form-select border-0 pt-0 pb-0.5 pl-0 !pr-6 h-auto text-xs bg-transparent focus:ring-0 text-gray-700 font-semibold w-full cursor-pointer truncate">
                                <option value="">Semua Gudang / Area</option>
                                <option v-for="area in areas" :key="area.id" :value="String(area.id)">{{ area.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter Status Stok -->
                    <div v-can="'materials_filter_category'" class="relative flex items-center bg-gray-50 border border-gray-200/80 rounded-xl pl-3 pr-2 py-1 shadow-2xs min-w-[175px] flex-1 sm:flex-initial">
                        <div class="shrink-0 mr-2 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="flex flex-col flex-1 min-w-0">
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider leading-none mt-0.5">Status Stok</span>
                            <select v-model="filterStockStatus" @change="performSearch" class="form-select border-0 pt-0 pb-0.5 pl-0 !pr-6 h-auto text-xs bg-transparent focus:ring-0 text-gray-700 font-semibold w-full cursor-pointer truncate">
                                <option value="">Semua Status Stok</option>
                                <option value="available">🟢 Stok Tersedia (>10)</option>
                                <option value="low">🟡 Stok Menipis (1-10)</option>
                                <option value="empty">🔴 Stok Habis (0)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Spacer -->
                    <div class="hidden sm:block flex-1"></div>

                    <!-- Reset Filters Button -->
                    <button 
                        v-if="search || (filterCategory && filterCategory !== 'all') || filterStockStatus || filterArea"
                        @click="resetFilters" 
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 font-medium text-xs shadow-2xs hover:text-gray-900 transition-all cursor-pointer"
                        title="Reset Semua Filter"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <div v-if="$page.props.flash?.error" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-4 w-12 text-center">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 transition-all">
                                </th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Barang</th>

                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Stok</th>
                                <th v-can="'materials_field_price'" class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">H. Modal / Jual</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in materials.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-4 text-center">
                                    <input type="checkbox" v-model="selectedItems" :value="item.id" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 transition-all">
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-semibold text-gray-900">{{ item.name }}</p>
                                        <button 
                                            type="button" 
                                            @click="openStatusModalFor(item)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 hover:border-amber-300 transition-all shadow-sm shrink-0" 
                                            title="Klik untuk Buat/Ubah Status Kondisi (Layak Pakai, Tidak Layak, Rusak)"
                                        >
                                            <span>⚡</span> Buat Status
                                        </button>
                                    </div>
                                    <p v-can="'materials_field_supplier'" v-if="item.supplier" class="text-xs text-blue-600 mt-0.5">Supplier: {{ item.supplier }}</p>
                                </td>

                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ item.category || 'Lainnya' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <div v-can="'materials_field_initial_stock'" class="flex items-center gap-1.5" title="Stok Awal">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">Awal:</span>
                                            <span class="text-xs font-semibold text-gray-600">
                                                {{ formatNumber(item.scoped_initial_stock !== undefined ? item.scoped_initial_stock : item.initial_stock) }} 
                                                {{ item.category?.toLowerCase().includes('kabel') ? 'meter' : (item.category?.toLowerCase().includes('isolasi') ? 'cm' : (item.category?.toLowerCase().includes('klem') ? 'pcs' : item.unit)) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5" title="Sisa Stok Saat Ini">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">Total Sisa:</span>
                                            <span class="text-sm font-bold text-blue-600">{{ formatNumber(item.scoped_stock !== undefined ? item.scoped_stock : item.stock) }}</span>
                                            <span class="text-xs text-gray-500">
                                                {{ item.category?.toLowerCase().includes('kabel') ? 'meter' : (item.category?.toLowerCase().includes('isolasi') ? 'cm' : (item.category?.toLowerCase().includes('klem') ? 'pcs' : item.unit)) }}
                                            </span>
                                        </div>
                                        <div v-if="item.stocks && item.stocks.length > 0" class="flex flex-col items-end w-full mt-1.5 gap-0.5 border-t border-gray-100 pt-1.5">
                                            <div v-for="s in item.stocks" :key="s.id" class="flex items-center justify-between w-full max-w-[140px] bg-gray-50 px-2 py-0.5 rounded border border-gray-100">
                                                <span class="text-[9px] font-medium text-gray-600 truncate max-w-[70px]" :title="s.area?.name">{{ s.area?.name || 'Area' }}</span>
                                                <span class="text-[10px] font-bold text-gray-700">{{ formatNumber(s.stock) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="item.category?.toLowerCase().includes('kabel') && (item.scoped_stock !== undefined ? (item.scoped_stock / (item.meter_per_roll || 100)) : item.total_rolls)" class="text-xs text-gray-400 mt-1">
                                            ({{ formatNumber(item.scoped_stock !== undefined ? (item.scoped_stock / (item.meter_per_roll || 100)) : item.total_rolls) }} roll)
                                        </div>
                                        <div v-else-if="item.category?.toLowerCase().includes('klem') && (item.scoped_stock !== undefined ? (item.scoped_stock / (item.pcs_per_pack || 25)) : item.total_packs)" class="text-xs text-gray-400 mt-0.5">
                                            ({{ formatNumber(item.scoped_stock !== undefined ? (item.scoped_stock / (item.pcs_per_pack || 25)) : item.total_packs) }} bungkus)
                                        </div>
                                        <div v-else-if="item.category?.toLowerCase().includes('isolasi') && (item.scoped_stock !== undefined ? Math.floor(item.scoped_stock / ((item.cm_per_pcs || 2400) * (item.pcs_per_pack || 1))) : item.total_pieces)" class="text-xs text-gray-400 mt-0.5">
                                            ({{ formatNumber(item.scoped_stock !== undefined ? Math.floor(item.scoped_stock / ((item.cm_per_pcs || 2400) * (item.pcs_per_pack || 1))) : item.total_pieces) }} pcs utuh)
                                        </div>
                                    </div>
                                </td>
                                <td v-can="'materials_field_price'" class="py-4 px-6 text-right text-sm">
                                    <p class="text-gray-900">M: Rp {{ formatNumber(item.price_per_unit) }}</p>
                                    <p class="text-emerald-600 font-medium">J: Rp {{ formatNumber(item.selling_price) }}</p>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            type="button" 
                                            @click="openStatusModalFor(item)" 
                                            title="Buat Status Material (Layak Pakai, Tidak Layak, Rusak)" 
                                            class="p-1.5 text-amber-600 hover:text-amber-800 rounded-lg hover:bg-amber-50 transition-colors inline-flex items-center justify-center font-bold"
                                        >
                                            <span class="text-xs">⚡</span>
                                        </button>
                                        <button v-can="'materials_add_stock'" @click="openAddStockModal(item)" title="Tambah Stok Masuk" class="p-1.5 text-emerald-500 hover:text-emerald-700 rounded-lg hover:bg-emerald-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                        <button v-can="'materials_edit'" @click="openModal(item)" title="Edit Data Barang" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                        <button v-can="'materials_delete'" @click="deleteData(item.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="materials.data.length === 0">
                                <td colspan="6" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada data material</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="materials.links && materials.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Menampilkan {{ materials.from }} - {{ materials.to }} dari {{ materials.total }} data</p>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in materials.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form (Tambah / Edit Material Area) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col animate-fade-in-up">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/70 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-gray-900 leading-tight">
                                {{ isEditing ? 'Edit Data Material (Area)' : 'Tambah Material Baru (Fisik Area)' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Pencatatan material fisik yang ada di area (bukan dari toko). Tanpa biaya/harga.</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Informasi Dasar -->
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Informasi Dasar</h4>
                            
                            <div class="space-y-4">
                                <!-- Nama Barang / Material dengan Autocomplete / Combobox -->
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-bold text-gray-700">
                                            Nama Barang / Material <span class="text-red-500">*</span>
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="isNameDropdownOpen = !isNameDropdownOpen" 
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline inline-flex items-center gap-1 cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                            <span>{{ isNameDropdownOpen ? 'Tutup Pilihan' : 'Pilih dari Katalog Standar' }}</span>
                                        </button>
                                    </div>
                                    
                                    <div class="relative">
                                        <input 
                                            v-model="form.name" 
                                            type="text" 
                                            list="catalog-material-datalist"
                                            @focus="isNameDropdownOpen = true"
                                            class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-semibold transition-all" 
                                            required 
                                            placeholder="Ketik manual atau pilih nama material standar..."
                                        >
                                        <button 
                                            type="button" 
                                            @click="isNameDropdownOpen = !isNameDropdownOpen" 
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                                            title="Tampilkan daftar nama katalog"
                                        >
                                            <svg class="w-4 h-4 transition-transform duration-200" :class="isNameDropdownOpen ? 'rotate-180 text-emerald-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>

                                    <!-- Native Datalist Fallback -->
                                    <datalist id="catalog-material-datalist">
                                        <option v-for="mat in catalogMaterials" :key="mat.id" :value="mat.name">{{ mat.category ? `[${mat.category}] ` : '' }}{{ mat.unit ? `(${mat.unit})` : '' }}</option>
                                    </datalist>

                                    <!-- Dropdown Pilihan Nama Katalog (Custom Combobox) -->
                                    <div 
                                        v-if="isNameDropdownOpen && filteredCatalogMaterials.length > 0" 
                                        class="absolute z-30 left-0 right-0 mt-1.5 bg-white border border-emerald-200 rounded-2xl shadow-xl max-h-60 overflow-y-auto divide-y divide-gray-100"
                                    >
                                        <div class="p-2 bg-emerald-50/70 border-b border-emerald-100 flex items-center justify-between text-[11px] font-bold text-emerald-800">
                                            <span>PILIH NAMA STANDAR (OTOMATIS ISI KATEGORI & SATUAN)</span>
                                            <button type="button" @click="isNameDropdownOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                                        </div>
                                        <button 
                                            v-for="mat in filteredCatalogMaterials" 
                                            :key="mat.id" 
                                            type="button"
                                            @click="selectCatalogMaterial(mat)"
                                            class="w-full px-4 py-2.5 text-left hover:bg-emerald-50/60 transition-colors flex items-center justify-between group cursor-pointer"
                                        >
                                            <div>
                                                <span class="text-sm font-bold text-gray-800 group-hover:text-emerald-700">{{ mat.name }}</span>
                                                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500">
                                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-gray-100 text-gray-600 uppercase">{{ mat.category || 'Umum' }}</span>
                                                    <span>Satuan: <strong>{{ mat.unit }}</strong></span>
                                                    <span v-if="mat.meter_per_roll">({{ mat.meter_per_roll }}m/roll)</span>
                                                    <span v-else-if="mat.cm_per_pcs">({{ mat.cm_per_pcs }}cm/pcs)</span>
                                                    <span v-else-if="mat.pcs_per_pack">({{ mat.pcs_per_pack }}pcs/pack)</span>
                                                </div>
                                            </div>
                                            <span class="text-xs font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">Pilih →</span>
                                        </button>
                                    </div>

                                    <p class="text-[11px] text-gray-400 mt-1">
                                        💡 Pilih nama dari katalog standar agar penamaan seragam antar area, atau ketik nama baru secara manual.
                                    </p>
                                </div>

                                <!-- Kategori & Supplier -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                                        <div v-if="!isNewCategory">
                                            <select v-model="form.category" @change="checkNewCategory(); handleCategoryChange()" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-medium bg-white" required>
                                                <option value="" disabled>Pilih Kategori...</option>
                                                <option v-for="cat in categoriesWithSpecial" :key="cat" :value="cat">{{ cat }}</option>
                                                <option value="new_category_option" class="font-bold text-emerald-600">+ Tambah Kategori Baru...</option>
                                            </select>
                                        </div>
                                        <div v-else class="flex gap-2">
                                            <input type="text" v-model="form.category" @input="handleCategoryChange" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-medium" placeholder="Ketik kategori baru..." required autofocus />
                                            <button type="button" @click="cancelNewCategory" class="px-3 py-2 bg-gray-100 border border-gray-300 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors">
                                                Batal
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Supplier / Merk</label>
                                        <input type="text" v-model="form.supplier" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-medium" placeholder="Contoh: ZTE / FiberStar / Unibel">
                                    </div>
                                </div>

                                <!-- Area / Wilayah Penempatan -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">
                                        Area / Wilayah Penempatan Fisik
                                    </label>
                                    <select v-model="form.area_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 bg-white text-sm font-medium">
                                        <option value="">🏢 Gudang Utama / Pusat (Tanpa Area Khusus)</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">📍 {{ area.name }}</option>
                                    </select>
                                    <p class="text-[11px] text-gray-400 mt-1">Pilih area cabang di mana material fisik ini berada.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Kalkulator Satuan Cerdas (Smart Unit - Sesuai Rumus Katalog Produk, TANPA HARGA) -->
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                                Satuan & Rumus Stok Cerdas (Tanpa Harga)
                            </h4>

                            <!-- Kalkulator Kabel & Patchcord (Roll ↔ Meter) -->
                            <div v-if="isCable" class="bg-blue-50/60 rounded-2xl border border-blue-200 overflow-hidden shadow-xs">
                                <div class="bg-blue-100/60 px-5 py-3 border-b border-blue-200 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <h5 class="text-sm font-bold text-blue-900">Kalkulator Kabel (Roll ↔ Meter)</h5>
                                    </div>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white text-blue-700 border border-blue-200">Satuan stok: Meter</span>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Meter per Roll <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" min="1" v-model="form.meter_per_roll" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white text-sm" placeholder="Contoh: 1000" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">{{ isEditing ? 'Jumlah Roll Saat Ini' : 'Jumlah Roll (Stok Awal)' }}</label>
                                            <input type="number" step="0.01" min="0" v-model="initialRolls" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white text-sm" placeholder="Contoh: 2">
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-white border border-blue-200 px-4 py-2.5">
                                        <span class="text-xs text-gray-600 font-medium">Total Stok Tersimpan (Meter):</span>
                                        <span class="text-base font-black text-blue-700">{{ formatNumber((Number(form.meter_per_roll) || 0) * (Number(initialRolls) || 0)) }} Meter</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Kalkulator Isolasi (Pcs ↔ CM) -->
                            <div v-else-if="isIsolasi" class="bg-amber-50/60 rounded-2xl border border-amber-200 overflow-hidden shadow-xs">
                                <div class="bg-amber-100/60 px-5 py-3 border-b border-amber-200 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <h5 class="text-sm font-bold text-amber-900">Kalkulator Isolasi (Pcs ↔ Cm)</h5>
                                    </div>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white text-amber-700 border border-amber-200">Satuan stok: CM</span>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Cm per Pcs (Panjang Isolasi) <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" min="1" v-model="form.cm_per_pcs" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 bg-white text-sm" placeholder="Contoh: 1800 atau 50" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">{{ isEditing ? 'Jumlah Pcs Saat Ini' : 'Jumlah Pcs (Stok Awal)' }}</label>
                                            <input type="number" step="0.01" min="0" v-model="initialPacks" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 bg-white text-sm" placeholder="Contoh: 5">
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-white border border-amber-200 px-4 py-2.5">
                                        <span class="text-xs text-gray-600 font-medium">Total Stok Tersimpan (CM):</span>
                                        <span class="text-base font-black text-amber-700">{{ formatNumber((Number(initialPacks) || 0) * (Number(form.cm_per_pcs) || 0)) }} CM</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Kalkulator Klem (Bungkus ↔ Pcs) -->
                            <div v-else-if="isKlem" class="bg-indigo-50/60 rounded-2xl border border-indigo-200 overflow-hidden shadow-xs">
                                <div class="bg-indigo-100/60 px-5 py-3 border-b border-indigo-200 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <h5 class="text-sm font-bold text-indigo-900">Kalkulator Klem (Bungkus ↔ Pcs)</h5>
                                    </div>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white text-indigo-700 border border-indigo-200">Satuan stok: PCS</span>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Isi Pcs per Bungkus <span class="text-red-500">*</span></label>
                                            <input type="number" step="1" min="1" v-model="form.pcs_per_pack" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white text-sm" placeholder="Contoh: 50" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">{{ isEditing ? 'Jumlah Bungkus Saat Ini' : 'Jumlah Bungkus (Stok Awal)' }}</label>
                                            <input type="number" step="0.01" min="0" v-model="initialBungkus" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white text-sm" placeholder="Contoh: 10">
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-white border border-indigo-200 px-4 py-2.5">
                                        <span class="text-xs text-gray-600 font-medium">Total Stok Tersimpan (PCS):</span>
                                        <span class="text-base font-black text-indigo-700">{{ formatNumber((Number(initialBungkus) || 0) * (Number(form.pcs_per_pack) || 0)) }} PCS</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Satuan Generik (Untuk ONT, Splitter, Peralatan, Umum, dsb.) -->
                            <div v-else class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Satuan Pemakaian <span class="text-red-500">*</span></label>
                                        <select v-model="form.unit" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white text-sm font-medium" required>
                                            <option value="pcs">Pcs (Satuan utuh)</option>
                                            <option value="meter">Meter</option>
                                            <option value="cm">Centimeter (CM)</option>
                                            <option value="pack">Pack</option>
                                            <option value="roll">Roll</option>
                                            <option value="box">Box</option>
                                            <option value="unit">Unit</option>
                                            <option value="set">Set</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">{{ isEditing ? 'Sisa Stok Saat Ini' : 'Stok Awal' }} ({{ form.unit }})</label>
                                        <input type="number" step="0.01" min="0" v-model="form.stock" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white text-sm font-semibold" placeholder="Contoh: 10">
                                    </div>
                                </div>

                                <!-- Konfigurasi Pack Khusus Aksesoris / Splitter / Fast Connector -->
                                <div v-if="isPackCategory" class="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-100">
                                    <div class="flex items-center gap-1.5 mb-2 text-xs font-bold text-emerald-800">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <span>Konfigurasi Satuan Pack (Opsional)</span>
                                    </div>
                                    <p class="text-[11px] text-emerald-700 mb-3">Jika material ini dibeli dalam kemasan Pack, tentukan jumlah Pcs per Pack:</p>
                                    <div class="w-full sm:w-1/2">
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Isi Pcs per Pack</label>
                                        <div class="relative">
                                            <input type="number" step="0.01" min="1" v-model="form.pcs_per_pack" class="w-full pl-3 pr-14 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white" placeholder="Contoh: 50">
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-gray-400 pointer-events-none">Pcs</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Konfigurasi Perangkat Lanjutan (ONT, Router, Switch, OLT) -->
                        <div v-if="isDeviceCategory" class="bg-blue-50/60 rounded-2xl p-4 border border-blue-100">
                            <h5 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                                <span>Konfigurasi Perangkat Lanjutan</span>
                            </h5>
                            <p class="text-[11px] text-blue-700 mb-3">Centang jika perangkat memerlukan pelacakan nomor seri (S/N) atau MAC address saat instalasi / mutasi.</p>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-xl border border-blue-200 text-xs font-bold text-gray-700">
                                    <input type="checkbox" v-model="form.requires_sn" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>Wajib Serial Number (S/N)</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-xl border border-blue-200 text-xs font-bold text-gray-700">
                                    <input type="checkbox" v-model="form.requires_mac" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>Wajib MAC Address</span>
                                </label>
                            </div>
                        </div>

                        <!-- Keterangan / Catatan Tambahan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Catatan Tambahan (Opsional)</label>
                            <textarea v-model="form.description" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm font-medium" placeholder="Catatan lokasi rak, kondisi fisik, atau keterangan lain..."></textarea>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between shrink-0">
                    <button 
                        v-if="isEditing" 
                        type="button" 
                        @click="deleteData(editingId); closeModal()" 
                        class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-3 py-2 rounded-xl transition-colors cursor-pointer"
                    >
                        Hapus Material
                    </button>
                    <div v-else></div>

                    <div class="flex items-center gap-2.5">
                        <button type="button" @click="closeModal" class="px-4 py-2.5 text-xs font-bold text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition-all disabled:opacity-50 cursor-pointer">
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Material') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Add Stock -->
        <div v-if="showAddStockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeAddStockModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
                    <h3 class="text-lg font-bold text-gray-900">Tambah Stok Masuk</h3>
                    <button @click="closeAddStockModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto max-h-[calc(100vh-12rem)]">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-5">
                        <h4 class="font-bold text-blue-900">{{ selectedMaterial?.name }}</h4>
                        <div class="flex gap-4 mt-2 text-sm text-blue-800">
                            <div>Sisa Stok: <span class="font-bold">{{ formatNumber(selectedMaterial?.stock) }} {{ selectedMaterial?.unit }}</span></div>
                            <div v-if="selectedMaterial?.category === 'Kabel' && selectedMaterial?.total_rolls">Total: <span class="font-bold">{{ selectedMaterial?.total_rolls }} roll</span></div>
                        </div>
                    </div>

                    <form @submit.prevent="submitAddStock" class="space-y-5">
                        <div v-if="selectedMaterial?.category === 'Kabel'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tambah Roll Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_rolls" @input="calculateAddedCableStock" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <span class="text-gray-500 text-sm">roll</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Meter (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-700" readonly>
                                    <span class="text-gray-500 text-sm">m</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">@ {{ selectedMaterial?.meter_per_roll }} m/roll</p>
                            </div>
                        </div>
                        <div v-else-if="selectedMaterial?.category === 'Paku Klem'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tambah Bungkus Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_packs" @input="calculateAddedPackStock" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <span class="text-gray-500 text-sm">bungkus</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Pcs (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-700" readonly>
                                    <span class="text-gray-500 text-sm">pcs</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">@ {{ selectedMaterial?.pcs_per_pack }} pcs/bungkus</p>
                            </div>
                        </div>
                        <div v-else-if="selectedMaterial?.category === 'Isolasi'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tambah Pcs Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_pieces" @input="calculateAddedIsolasiStock" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <span class="text-gray-500 text-sm">pcs</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Cm (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-700" readonly>
                                    <span class="text-gray-500 text-sm">cm</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">@ {{ selectedMaterial?.cm_per_pcs }} cm/pcs</p>
                            </div>
                        </div>
                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Stok Masuk *</label>
                            <div class="flex items-center gap-2">
                                <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                <span class="text-gray-500 text-sm">{{ selectedMaterial?.unit }}</span>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Perbarui Harga (Opsional)
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Hrg Modal Baru</label>
                                    <input v-model="addStockForm.price_per_unit" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Hrg Jual Baru</label>
                                    <input v-model="addStockForm.selling_price" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2">Kosongkan/biarkan jika harga tidak berubah.</p>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Area / Wilayah untuk Stok Ini *</label>
                            <select v-model="addStockForm.area_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                <option value="">Pilih Area...</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1">Stok akan ditambahkan ke lokasi/Area yang dipilih.</p>
                        </div>
                    </form>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="closeAddStockModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="submitAddStock" :disabled="addStockForm.processing" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200 transition-all disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="addStockForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Simpan Stok Baru
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Buat Status Material -->
        <div v-if="isStatusModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="isStatusModalOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-amber-100 text-amber-700 text-sm">⚡</span>
                        Buat Status Material
                    </h3>
                    <button @click="isStatusModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitStatusForm" class="p-6 space-y-4">
                    <!-- Pilihan Material / Info Material -->
                    <div v-if="statusTargetMaterial" class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Material Terpilih</label>
                                <p class="text-base font-bold text-gray-900 mt-0.5">{{ statusTargetMaterial.name }}</p>
                                <p class="text-xs text-blue-600 font-semibold mt-0.5">
                                    Sisa Total Stok: {{ formatNumber(statusTargetMaterial.stock) }} {{ statusTargetMaterial.unit }}
                                </p>
                            </div>
                            <button type="button" @click="statusTargetMaterial = null" class="text-xs text-blue-600 hover:underline">
                                Ganti Barang
                            </button>
                        </div>
                    </div>
                    <div v-else>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Pilih Material / Barang <span class="text-red-500">*</span></label>
                        <select v-model="selectedMaterialId" @change="onSelectMaterialChange" class="w-full text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500" required>
                            <option value="" disabled>-- Pilih Barang --</option>
                            <option v-for="mat in materials.data" :key="mat.id" :value="mat.id">
                                {{ mat.name }} (Stok: {{ formatNumber(mat.stock) }} {{ mat.unit }})
                            </option>
                        </select>
                    </div>

                    <!-- Pilihan Status Kondisi Material -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2 uppercase">Status Kondisi <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="statusForm.condition = 'Layak Pakai'; if (!statusForm.purpose || statusForm.purpose.includes('Rusak') || statusForm.purpose.includes('Bekas')) statusForm.purpose = 'Retur Sisa Pasang (Layak)'" 
                                class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all flex flex-col items-center gap-1"
                                :class="statusForm.condition === 'Layak Pakai' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm ring-2 ring-emerald-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'">
                                <span class="text-base">🟢</span>
                                Layak Pakai
                            </button>
                            <button type="button" @click="statusForm.condition = 'Tidak Layak'; if (!statusForm.purpose || statusForm.purpose.includes('Sisa') || statusForm.purpose.includes('Rusak')) statusForm.purpose = 'Barang Bekas Tidak Layak'" 
                                class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all flex flex-col items-center gap-1"
                                :class="statusForm.condition === 'Tidak Layak' ? 'bg-amber-600 text-white border-amber-600 shadow-sm ring-2 ring-amber-200' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'">
                                <span class="text-base">🟡</span>
                                Tidak Layak
                            </button>
                            <button type="button" @click="statusForm.condition = 'Rusak'; if (!statusForm.purpose || statusForm.purpose.includes('Sisa') || statusForm.purpose.includes('Bekas')) statusForm.purpose = 'Barang Rusak Lapangan'" 
                                class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all flex flex-col items-center gap-1"
                                :class="statusForm.condition === 'Rusak' ? 'bg-rose-600 text-white border-rose-600 shadow-sm ring-2 ring-rose-200' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'">
                                <span class="text-base">🔴</span>
                                Rusak
                            </button>
                        </div>
                    </div>

                    <!-- Tipe Pergerakan: Masuk (Retur) atau Keluar (Afkir / Pemusnahan) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Tipe Mutasi Stok <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all"
                                :class="statusForm.type === 'in' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-white border-gray-200 text-gray-600'">
                                <input type="radio" value="in" v-model="statusForm.type" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs">📥 Masuk (Retur ke Gudang)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all"
                                :class="statusForm.type === 'out' ? 'bg-rose-50 border-rose-300 text-rose-800 font-bold' : 'bg-white border-gray-200 text-gray-600'">
                                <input type="radio" value="out" v-model="statusForm.type" class="text-rose-600 focus:ring-rose-500">
                                <span class="text-xs">📤 Keluar (Afkir / Dikeluarkan)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Jumlah & Petugas -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Jumlah (Qty) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0.01" v-model="statusForm.quantity" required class="w-full text-sm pl-3 pr-14 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 font-medium" placeholder="Qty">
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-gray-400 pointer-events-none">
                                    {{ statusTargetMaterial?.unit || 'unit' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Petugas / Teknisi <span class="text-red-500">*</span></label>
                            <input type="text" v-model="statusForm.technician_name" required class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500" placeholder="Nama teknisi / petugas">
                        </div>
                    </div>

                    <!-- Catatan / Tujuan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Keterangan / Keperluan <span class="text-red-500">*</span></label>
                        <input type="text" v-model="statusForm.purpose" required class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500" placeholder="Cth: Sisa penarikan kabel / Rusak kena petir">
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="isStatusModalOpen = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="statusForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm transition">
                            Simpan Status Material
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    materials: Object,
    areas: Array,
    categories: Array,
    catalogMaterials: {
        type: Array,
        default: () => []
    },
    summary: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const filterCategory = ref(props.filters.category || 'all');
const filterStockStatus = ref(props.filters.stock_status || '');
const filterArea = ref(props.filters.area_id || '');
const showModal = ref(false);
const showAddStockModal = ref(false);
const selectedMaterial = ref(null);
const isEditing = ref(false);
const editingId = ref(null);
const isNewCategory = ref(false);
const initialRolls = ref(0);
const initialPacks = ref(0);
const initialBungkus = ref(0);

// Combobox autocomplete untuk nama material
const isNameDropdownOpen = ref(false);

const selectedItems = ref([]);

const isAllSelected = computed(() => {
    return props.materials.data.length > 0 && selectedItems.value.length === props.materials.data.length;
});

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedItems.value = props.materials.data.map(item => item.id);
    } else {
        selectedItems.value = [];
    }
};

const deleteSelected = () => {
    if (selectedItems.value.length === 0) return;
    
    if (confirm(`Apakah Anda yakin ingin menghapus ${selectedItems.value.length} material yang dipilih?`)) {
        router.post('/materials/bulk-destroy', {
            ids: selectedItems.value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedItems.value = [];
            }
        });
    }
};

const form = useForm({
    id: null,
    name: '',
    supplier: '',
    category: '',
    unit: 'pcs',
    area_id: '',
    meter_per_roll: null,
    total_rolls: 0,
    pcs_per_pack: null,
    total_packs: 0,
    cm_per_pcs: null,
    total_pieces: 0,
    stock: null,
    initial_stock: null,
    price_per_unit: 0,
    selling_price: 0,
    is_active: true,
    requires_sn: false,
    requires_mac: false,
    description: '',
});

const categoriesWithSpecial = computed(() => {
    let cats = props.categories ? [...props.categories] : [];
    if (!cats.includes('Isolasi')) cats.push('Isolasi');
    if (!cats.includes('Klem')) cats.push('Klem');
    if (!cats.includes('Kabel')) cats.push('Kabel');
    if (!cats.includes('Patchcord')) cats.push('Patchcord');
    if (!cats.includes('ONT')) cats.push('ONT');
    if (!cats.includes('Splitter')) cats.push('Splitter');
    if (!cats.includes('Aksesoris')) cats.push('Aksesoris');
    if (!cats.includes('Peralatan')) cats.push('Peralatan');
    return Array.from(new Set(cats)).sort();
});

const isIsolasi = computed(() => {
    const cat = (form.category || '').toLowerCase();
    const name = (form.name || '').toLowerCase();
    return cat === 'isolasi' || cat.includes('isolasi') || name.includes('isolasi');
});

const isKlem = computed(() => {
    const cat = (form.category || '').toLowerCase();
    const name = (form.name || '').toLowerCase();
    return cat === 'klem' || cat === 'paku klem' || cat.includes('klem') || name.includes('klem');
});

const isCable = computed(() => {
    const cat = (form.category || '').toLowerCase();
    return ['kabel', 'patchcord', 'kabel drop', 'kabel drop / frecon'].includes(cat) || cat.includes('kabel');
});

const isPackCategory = computed(() => {
    if (!form.category || isKlem.value || isCable.value || isIsolasi.value) return false;
    if (isNewCategory.value) return true;
    const cat = form.category.toLowerCase();
    return ['aksesoris', 'konektor', 'splitter', 'fast', 'adapter'].some(k => cat.includes(k));
});

const isDeviceCategory = computed(() => {
    const cat = (form.category || '').toUpperCase();
    return ['ONT', 'ROUTER', 'SWITCH', 'OLT'].some(k => cat.includes(k));
});

const checkNewCategory = (e) => {
    if (e.target.value === 'new_category_option') {
        isNewCategory.value = true;
        form.category = '';
    }
};

const cancelNewCategory = () => {
    isNewCategory.value = false;
    form.category = '';
};

// Filtered catalog materials untuk autocomplete
const filteredCatalogMaterials = computed(() => {
    if (!props.catalogMaterials || props.catalogMaterials.length === 0) return [];
    const q = (form.name || '').toLowerCase().trim();
    if (!q) return props.catalogMaterials.slice(0, 30);
    return props.catalogMaterials.filter(m => 
        (m.name && m.name.toLowerCase().includes(q)) ||
        (m.category && m.category.toLowerCase().includes(q))
    ).slice(0, 30);
});

const selectCatalogMaterial = (mat) => {
    form.name = mat.name;
    if (mat.category) form.category = mat.category;
    if (mat.supplier) form.supplier = mat.supplier;
    if (mat.unit) form.unit = mat.unit;
    if (mat.meter_per_roll) form.meter_per_roll = Number(mat.meter_per_roll);
    if (mat.pcs_per_pack) form.pcs_per_pack = Number(mat.pcs_per_pack);
    if (mat.cm_per_pcs) form.cm_per_pcs = Number(mat.cm_per_pcs);
    form.requires_sn = !!mat.requires_sn;
    form.requires_mac = !!mat.requires_mac;
    isNameDropdownOpen.value = false;
    handleCategoryChange();
};

const handleCategoryChange = () => {
    if (isCable.value) {
        form.unit = 'meter';
        if (!form.meter_per_roll || Number(form.meter_per_roll) <= 0) {
            form.meter_per_roll = 1000;
        }
    } else if (isIsolasi.value) {
        form.unit = 'cm';
        if (!form.cm_per_pcs || Number(form.cm_per_pcs) <= 0) {
            form.cm_per_pcs = 50;
        }
    } else if (isKlem.value) {
        form.unit = 'pcs';
        if (!form.pcs_per_pack || Number(form.pcs_per_pack) <= 0) {
            form.pcs_per_pack = 50;
        }
    } else {
        if (form.unit === 'meter' || form.unit === 'cm') {
            form.unit = 'pcs';
        }
    }
};

const addStockForm = useForm({
    area_id: '',
    added_stock: '',
    added_rolls: '',
    added_packs: '',
    added_pieces: '',
    price_per_unit: '',
    selling_price: ''
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const getAreaName = (areaId) => {
    if (!areaId) return '-';
    const area = props.areas.find(a => a.id === areaId);
    return area ? area.name : '-';
};

const performSearch = () => {
    router.get('/materials', { 
        search: search.value ? search.value.trim() : undefined,
        category: (filterCategory.value && filterCategory.value !== 'all') ? filterCategory.value : undefined,
        stock_status: filterStockStatus.value || undefined,
        area_id: filterArea.value || undefined
    }, { preserveState: true, preserveScroll: true });
};

const setQuickStockStatus = (status) => {
    if (filterStockStatus.value === status) {
        filterStockStatus.value = '';
    } else {
        filterStockStatus.value = status;
    }
    performSearch();
};

const resetFilters = () => {
    search.value = '';
    filterCategory.value = 'all';
    filterStockStatus.value = '';
    filterArea.value = '';
    router.get('/materials', {}, {
        replace: true,
        preserveState: false,
        preserveScroll: false
    });
};

watch(() => props.filters, (newFilters) => {
    search.value = newFilters?.search || '';
    filterCategory.value = newFilters?.category || 'all';
    filterStockStatus.value = newFilters?.stock_status || '';
    filterArea.value = newFilters?.area_id ? String(newFilters.area_id) : '';
}, { deep: true });

const openModal = (item = null) => {
    if (item) {
        isEditing.value = true;
        editingId.value = item.id;
        form.id = item.id;
        form.name = item.name;
        form.supplier = item.supplier || '';
        form.category = item.category || '';
        form.unit = item.unit || 'pcs';
        form.meter_per_roll = item.meter_per_roll ? Number(item.meter_per_roll) : null;
        form.pcs_per_pack = item.pcs_per_pack ? Number(item.pcs_per_pack) : null;
        form.cm_per_pcs = item.cm_per_pcs ? Number(item.cm_per_pcs) : null;
        form.stock = item.stock ? Number(item.stock) : 0;
        form.initial_stock = (item.initial_stock !== null && item.initial_stock !== undefined && item.initial_stock !== '') 
            ? Number(item.initial_stock) 
            : (item.stock ? Number(item.stock) : 0);
        form.total_rolls = item.total_rolls ? Number(item.total_rolls) : 0;
        form.total_packs = item.total_packs ? Number(item.total_packs) : 0;
        form.total_pieces = item.total_pieces ? Number(item.total_pieces) : 0;
        form.requires_sn = !!item.requires_sn;
        form.requires_mac = !!item.requires_mac;
        form.description = item.description || '';
        form.price_per_unit = 0;
        form.selling_price = 0;

        if (isCable.value) {
            initialRolls.value = (form.meter_per_roll > 0 && form.stock) 
                ? Number((form.stock / form.meter_per_roll).toFixed(2)) 
                : (item.total_rolls || 0);
        } else {
            initialRolls.value = 0;
        }

        if (isIsolasi.value) {
            initialPacks.value = (form.cm_per_pcs > 0 && form.stock) 
                ? Number((form.stock / form.cm_per_pcs).toFixed(2)) 
                : (item.total_pieces || 0);
        } else {
            initialPacks.value = 0;
        }

        if (isKlem.value) {
            initialBungkus.value = (form.pcs_per_pack > 0 && form.stock) 
                ? Number((form.stock / form.pcs_per_pack).toFixed(2)) 
                : (item.total_packs || (item.stock || 0));
        } else {
            initialBungkus.value = 0;
        }

        let matchedArea = '';
        if (filterArea.value && item.stocks?.some(s => String(s.area_id) === String(filterArea.value))) {
            matchedArea = Number(filterArea.value);
        } else if (item.stocks && item.stocks.length > 0) {
            matchedArea = item.stocks[0].area_id;
        }
        form.area_id = matchedArea;
        isNewCategory.value = !props.categories.includes(item.category);
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.id = null;
        form.unit = 'pcs';
        form.price_per_unit = 0;
        form.selling_price = 0;
        form.area_id = (filterArea.value && filterArea.value !== '') ? Number(filterArea.value) : ((props.areas && props.areas.length === 1) ? props.areas[0].id : '');
        isNewCategory.value = false;
        initialRolls.value = 0;
        initialPacks.value = 0;
        initialBungkus.value = 0;
    }
    isNameDropdownOpen.value = false;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    isNameDropdownOpen.value = false;
    form.reset();
};

const openAddStockModal = (item) => {
    selectedMaterial.value = item;
    addStockForm.reset();
    addStockForm.area_id = (props.areas && props.areas.length === 1) ? props.areas[0].id : (filterArea.value || '');
    addStockForm.added_stock = '';
    addStockForm.added_rolls = '';
    addStockForm.added_packs = '';
    addStockForm.price_per_unit = item.price_per_unit;
    addStockForm.selling_price = item.selling_price;
    showAddStockModal.value = true;
};

const closeAddStockModal = () => {
    showAddStockModal.value = false;
    selectedMaterial.value = null;
    addStockForm.reset();
};

const calculateAddedCableStock = () => {
    if (selectedMaterial.value?.category === 'Kabel') {
        const rolls = parseFloat(addStockForm.added_rolls) || 0;
        const meterPerRoll = parseFloat(selectedMaterial.value.meter_per_roll) || 0;
        addStockForm.added_stock = rolls * meterPerRoll;
    }
};

const calculateAddedPackStock = () => {
    if (selectedMaterial.value?.category === 'Paku Klem') {
        const packs = parseFloat(addStockForm.added_packs) || 0;
        const pcsPerPack = parseFloat(selectedMaterial.value.pcs_per_pack) || 0;
        addStockForm.added_stock = packs * pcsPerPack;
    }
};

const calculateAddedIsolasiStock = () => {
    if (selectedMaterial.value?.category === 'Isolasi') {
        const pieces = parseFloat(addStockForm.added_pieces) || 0;
        const cmPerPcs = parseFloat(selectedMaterial.value.cm_per_pcs) || 0;
        addStockForm.added_stock = pieces * cmPerPcs;
    }
};

const submitAddStock = () => {
    if (selectedMaterial.value) {
        addStockForm.post(`/materials/${selectedMaterial.value.id}/add-stock`, {
            onSuccess: () => closeAddStockModal(),
        });
    }
};

const submit = () => {
    // Rumus Kabel: roll * meter_per_roll -> disimpan dalam meter
    if (isCable.value) {
        form.unit = 'meter';
        if (Number(initialRolls.value) >= 0 && Number(form.meter_per_roll) > 0) {
            form.stock = parseFloat(initialRolls.value) * parseFloat(form.meter_per_roll);
            form.total_rolls = parseFloat(initialRolls.value);
        }
    }
    // Rumus Isolasi: pcs * cm_per_pcs -> disimpan dalam cm
    else if (isIsolasi.value) {
        form.unit = 'cm';
        form.pcs_per_pack = 1;
        if (Number(initialPacks.value) >= 0 && Number(form.cm_per_pcs) > 0) {
            form.stock = parseFloat(initialPacks.value) * parseFloat(form.cm_per_pcs);
            form.total_pieces = parseFloat(initialPacks.value);
        }
    }
    // Rumus Klem: bungkus * pcs_per_pack -> disimpan dalam pcs
    else if (isKlem.value) {
        form.unit = 'pcs';
        if (Number(initialBungkus.value) >= 0 && Number(form.pcs_per_pack) > 0) {
            form.stock = parseFloat(initialBungkus.value) * parseFloat(form.pcs_per_pack);
            form.total_packs = parseFloat(initialBungkus.value);
        } else if (Number(initialBungkus.value) >= 0) {
            form.stock = parseFloat(initialBungkus.value);
        }
    }

    if (!form.id && (form.initial_stock === null || form.initial_stock === undefined || form.initial_stock === '')) {
        form.initial_stock = form.stock || 0;
    }

    // Tiadakan harga untuk pencatatan fisik area (bukan toko)
    form.price_per_unit = 0;
    form.selling_price = 0;

    if (isEditing.value) {
        form.post(`/materials/${editingId.value}/update`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/materials', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus data material ini?')) {
        router.post(`/materials/${id}/delete`);
    }
};

// Modal Buat Status Material
const isStatusModalOpen = ref(false);
const statusTargetMaterial = ref(null);
const selectedMaterialId = ref('');

const statusForm = useForm({
    type: 'in',
    date: new Date().toISOString().split('T')[0],
    technician_name: '',
    purpose: 'Retur Sisa Pasang (Layak)',
    notes: '',
    condition: 'Layak Pakai',
    quantity: null,
    items: [],
});

const openQuickStatusModal = () => {
    statusTargetMaterial.value = null;
    selectedMaterialId.value = '';
    statusForm.date = new Date().toISOString().split('T')[0];
    statusForm.technician_name = '';
    statusForm.type = 'in';
    statusForm.condition = 'Layak Pakai';
    statusForm.purpose = 'Retur Sisa Pasang (Layak)';
    statusForm.quantity = null;
    statusForm.notes = '';
    isStatusModalOpen.value = true;
};

const openStatusModalFor = (material) => {
    statusTargetMaterial.value = material;
    selectedMaterialId.value = material.id;
    statusForm.date = new Date().toISOString().split('T')[0];
    statusForm.technician_name = '';
    statusForm.type = 'in';
    statusForm.condition = 'Layak Pakai';
    statusForm.purpose = 'Retur Sisa Pasang (Layak)';
    statusForm.quantity = null;
    statusForm.notes = '';
    isStatusModalOpen.value = true;
};

const onSelectMaterialChange = () => {
    const found = props.materials.data.find(m => m.id === Number(selectedMaterialId.value) || m.id === selectedMaterialId.value);
    if (found) {
        statusTargetMaterial.value = found;
    }
};

const submitStatusForm = () => {
    const targetId = statusTargetMaterial.value?.id || selectedMaterialId.value;
    if (!targetId) {
        alert('Silakan pilih material terlebih dahulu');
        return;
    }
    if (!statusForm.quantity || Number(statusForm.quantity) <= 0) {
        alert('Silakan masukkan jumlah (qty) yang valid');
        return;
    }
    statusForm.items = [
        {
            material_id: targetId,
            quantity: statusForm.quantity,
            condition: statusForm.condition,
        }
    ];
    statusForm.post('/stok-in-out', {
        preserveScroll: true,
        onSuccess: () => {
            isStatusModalOpen.value = false;
        }
    });
};
</script>
