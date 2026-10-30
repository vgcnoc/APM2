<template>
    <AppLayout title="Inventory">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-indigo-100/40 to-purple-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mb-4 shadow-lg shadow-indigo-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Inventory</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Pantau distribusi persediaan barang di setiap area / gudang secara detail.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <button type="button" @click="openDistributeModal(null)" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs sm:text-sm shadow-sm hover:shadow transition-all" title="Alokasikan / Tambah Stok Material ke Area Gudang">
                                <span>➕</span> Alokasikan Stok Area
                            </button>
                            <button v-can="'warehouse_stocks_btn_status'" type="button" @click="openStatusModal(null)" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-xs sm:text-sm shadow-sm hover:shadow transition-all" title="Catat / Buat Status Material (Layak / Rusak / Retur)">
                                <span>⚡</span> Buat Status Material
                            </button>
                            <Link v-can="'warehouse_stocks_btn_retur'" href="/stok-in-out?action=retur" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs sm:text-sm shadow-sm hover:shadow transition-all">
                                <span>🟢</span> + Catat Retur Sisa (Layak)
                            </Link>
                            <Link v-can="'warehouse_stocks_btn_rusak'" href="/stok-in-out?action=rusak" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs sm:text-sm shadow-sm hover:shadow transition-all">
                                <span>🔴</span> + Catat Barang Rusak
                            </Link>
                            <Link v-can="'menu_material_mutations'" href="/material-mutations" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl font-bold text-xs sm:text-sm shadow-sm transition-all">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                Rekap Kondisi
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10 space-y-6">
                
                <!-- Summary Metrics Bar -->
                <div v-can="'warehouse_stocks_card_stats'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Varian Barang</p>
                            <h3 class="text-2xl font-black text-gray-900 mt-1">{{ formatNum(summary?.total_materials || materials.total || 0) }}</h3>
                            <p class="text-xs text-indigo-600 font-medium mt-0.5">Master material terdaftar</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Stok Tersedia</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ formatNum(summary?.available || 0) }}</h3>
                            <p class="text-xs text-emerald-400 mt-0.5">Memiliki stok di gudang</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-rose-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider">Stok Kosong / Habis</p>
                            <h3 class="text-2xl font-black text-rose-600 mt-1">{{ formatNum(summary?.out_of_stock || 0) }}</h3>
                            <p class="text-xs text-rose-400 mt-0.5">Perlu restock / order toko</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Comprehensive Filters Toolbar -->
                <div v-can="'warehouse_stocks_filter'" class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                    <!-- Top row: Search Box and Quick Reset -->
                    <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center">
                        <div class="relative flex-1 group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input 
                                type="text" 
                                v-model="filterSearch" 
                                @input="debouncedApply" 
                                class="w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all placeholder:text-gray-400" 
                                placeholder="Cari nama barang, kategori, supplier, unit, deskripsi..." 
                            />
                            <button v-if="filterSearch" @click="filterSearch = ''; applyFilters()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Sort By -->
                        <div class="w-full lg:w-48">
                            <select v-model="filterSortBy" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="name_asc">Urutkan: Nama A - Z</option>
                                <option value="name_desc">Urutkan: Nama Z - A</option>
                                <option value="category">Urutkan: Kategori</option>
                            </select>
                        </div>
                    </div>

                    <!-- Middle row: Dropdown Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Category Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Kategori Barang</label>
                            <select v-model="filterCategory" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                        </div>

                        <!-- Area Gudang Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Area Gudang</label>
                            <select v-model="filterAreaId" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Area Gudang</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>

                        <!-- Stock Status Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Status Stok</label>
                            <select v-model="filterStockStatus" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Status Stok</option>
                                <option value="available">Stok Tersedia (&gt; 0)</option>
                                <option value="low_stock">Stok Menipis (&le; 20)</option>
                                <option value="out_of_stock">Stok Kosong / Habis (0)</option>
                            </select>
                        </div>

                        <!-- Unit Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Satuan Unit</label>
                            <select v-model="filterUnit" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Satuan</option>
                                <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Active Filter Chips & Reset -->
                    <div v-if="activeFilterCount > 0" class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-gray-400 font-medium">Filter Diterapkan ({{ activeFilterCount }}):</span>
                            <span v-if="filterSearch" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                                Cari: "{{ filterSearch }}"
                                <button @click="filterSearch = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                            </span>
                            <span v-if="filterCategory" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                                Kategori: {{ filterCategory }}
                                <button @click="filterCategory = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                            </span>
                            <span v-if="filterAreaId" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                                Area: {{ getAreaName(filterAreaId) }}
                                <button @click="filterAreaId = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                            </span>
                            <span v-if="filterStockStatus" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                                Status: {{ getStatusLabel(filterStockStatus) }}
                                <button @click="filterStockStatus = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                            </span>
                            <span v-if="filterUnit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                                Satuan: {{ filterUnit }}
                                <button @click="filterUnit = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                            </span>
                        </div>
                        <button @click="resetFilters" class="px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Semua Filter">
                            Reset Semua Filter
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Barang / Material</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Stok per Area</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">
                                        <span v-if="filterAreaId" class="inline-flex items-center gap-1">
                                            <span>Stok Area:</span>
                                            <span class="text-indigo-600 font-black normal-case">{{ getAreaName(filterAreaId) }}</span>
                                        </span>
                                        <span v-else>Total Stok (Semua Area)</span>
                                    </th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="material in materials.data" :key="material.id" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-500 group-hover:scale-110 transition-transform">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900 flex items-center gap-2 flex-wrap">
                                                    <span>{{ material.name }}</span>
                                                    <button v-can="'warehouse_stocks_btn_status'" type="button" @click="openStatusModal(material)" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 hover:border-amber-300 transition-all shadow-sm shrink-0" title="Klik untuk Buat/Ubah Status Kondisi (Layak, Tidak Layak, Rusak)">
                                                        <span>⚡</span> Buat Status
                                                    </button>
                                                </div>
                                                <div v-if="material.supplier" class="text-[11px] text-gray-500 mt-0.5">{{ material.supplier }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-700">
                                            {{ material.category || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="material.stocks && material.stocks.length > 0" class="flex flex-wrap items-center gap-2">
                                            <div v-for="stock in material.stocks" :key="stock.id" 
                                                 v-show="!filterAreaId || filterAreaId == stock.area_id"
                                                 class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold border transition"
                                                 :class="[filterAreaId && stock.area_id == filterAreaId ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm ring-2 ring-indigo-200' : 'bg-indigo-50 border-indigo-100 text-indigo-700']">
                                                <svg class="w-3.5 h-3.5" :class="filterAreaId && stock.area_id == filterAreaId ? 'text-indigo-200' : 'text-indigo-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                <span>{{ stock.area?.name || 'Area ?' }}: <strong :class="filterAreaId && stock.area_id == filterAreaId ? 'text-white' : 'text-indigo-900'">{{ formatNum(stock.stock) }}</strong> {{ material.unit }} <span class="font-bold text-[9px]" :class="filterAreaId && stock.area_id == filterAreaId ? 'text-indigo-100' : 'text-indigo-500'">{{ formatSecondaryUnit(material, stock.stock) }}</span></span>
                                            </div>
                                            <span v-if="filterAreaId && (material.stocks || []).some(s => s.area_id != filterAreaId && Number(s.stock) > 0)" 
                                                  class="text-[10px] text-slate-500 font-semibold bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/80 cursor-help"
                                                  :title="'Stok di area lain: ' + (material.stocks || []).filter(s => s.area_id != filterAreaId).map(s => (s.area?.name || 'Area') + ': ' + formatNum(s.stock) + ' ' + material.unit).join(', ')">
                                                + Ada di area lain
                                            </span>
                                            <button 
                                                type="button" 
                                                @click="openDistributeModal(material)"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 transition"
                                                title="Tambah atau Alokasikan Stok ke Area Lain"
                                            >
                                                <span>➕</span> Area
                                            </button>
                                        </div>
                                        <div v-else class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-gray-400 italic">Belum ada distribusi area</span>
                                            <button 
                                                type="button" 
                                                @click="openDistributeModal(material)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 hover:border-indigo-300 transition shadow-2xs"
                                                title="Klik untuk langsung mendistribusikan stok barang ini ke area gudang"
                                            >
                                                <span>➕</span> Alokasikan ke Area
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end justify-center">
                                            <div>
                                                <span class="text-sm font-black text-slate-800">{{ formatNum(filterAreaId ? getAreaStock(material, filterAreaId) : getAllAreasTotalStock(material)) }}</span>
                                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">{{ material.unit }} <span class="text-slate-400 normal-case">{{ formatSecondaryUnit(material, filterAreaId ? getAreaStock(material, filterAreaId) : getAllAreasTotalStock(material)) }}</span></span>
                                            </div>
                                            <div class="mt-1">
                                                <span v-if="(filterAreaId ? getAreaStock(material, filterAreaId) : getAllAreasTotalStock(material)) <= 0" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                                    {{ filterAreaId ? 'Stok Habis di Area Ini' : 'Stok Habis' }}
                                                </span>
                                                <span v-else-if="(filterAreaId ? getAreaStock(material, filterAreaId) : getAllAreasTotalStock(material)) <= 20" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    Menipis
                                                </span>
                                                <span v-else class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ filterAreaId ? 'Tersedia di Area Ini' : 'Tersedia' }}
                                                </span>
                                            </div>
                                            <div class="inline-flex items-center gap-1 text-[11px] text-slate-500 mt-1 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60 shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
                                                <span class="text-slate-400 font-normal">Awal{{ filterAreaId ? ' Area' : '' }}:</span>
                                                <span class="font-bold text-slate-700">{{ formatNum(filterAreaId ? getAreaInitialStock(material, filterAreaId) : getAllAreasInitialStock(material)) }}</span>
                                                <span class="text-[9px] uppercase font-bold text-slate-400">{{ material.unit }}</span>
                                            </div>
                                            <div v-if="filterAreaId" class="inline-flex items-center gap-1 text-[10px] text-indigo-700 font-semibold mt-1 bg-indigo-50/70 px-1.5 py-0.5 rounded border border-indigo-100" title="Total stok barang ini di seluruh area/cabang">
                                                <span>🏢 All Area:</span>
                                                <span class="font-bold font-mono">{{ formatNum(getAllAreasTotalStock(material)) }}</span>
                                                <span class="text-[8px] uppercase font-bold text-indigo-400">{{ material.unit }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" @click="openDistributeModal(material)" class="p-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition-colors inline-flex items-center justify-center font-bold" title="Alokasikan / Tambah Stok ke Area">
                                                <span class="text-xs font-black">➕</span>
                                            </button>
                                            <button v-can="'warehouse_stocks_btn_status'" type="button" @click="openStatusModal(material)" class="p-2 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Set Status Kondisi Material (Layak/Tidak Layak/Rusak)">
                                                <span class="text-xs font-black">⚡</span>
                                            </button>
                                            <Link v-can="'warehouse_stocks_edit'" :href="`/produk?search=${encodeURIComponent(material.name)}`" class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Edit Produk">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </Link>
                                            <Link :href="`/stok-in-out?search=${encodeURIComponent(material.name)}`" class="p-2 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg transition-colors" title="Riwayat Mutasi & Pemakaian">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                            </Link>
                                            <button v-can="'warehouse_stocks_delete'" @click="deleteMaterial(material)" class="p-2 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Hapus Barang">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!materials.data || materials.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 text-gray-400 mb-4">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">Barang tidak ditemukan</h3>
                                        <p class="text-sm text-gray-500">Gunakan kata kunci atau filter lain untuk menemukan barang.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination (if applicable) -->
                    <div v-if="materials.links && materials.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Menampilkan <span class="font-bold">{{ materials.from }}</span> - <span class="font-bold">{{ materials.to }}</span> dari <span class="font-bold">{{ materials.total }}</span> hasil
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <template v-for="(link, i) in materials.links" :key="i">
                                        <Component
                                            :is="link.url ? 'Link' : 'span'"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                            :class="[
                                                link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                { 'rounded-l-md': i === 0, 'rounded-r-md': i === materials.links.length - 1 }
                                            ]"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Set Status Material Langsung -->
        <Dialog :open="isStatusModalOpen" @close="isStatusModalOpen = false" class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" aria-hidden="true" />
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel class="w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <DialogTitle class="text-base font-black text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-amber-100 text-amber-700">⚡</span>
                            Set Status Kondisi Material
                        </DialogTitle>
                        <button type="button" @click="isStatusModalOpen = false" class="p-2 text-gray-400 hover:text-red-500 rounded-xl">✕</button>
                    </div>

                    <form @submit.prevent="submitStatusForm" class="p-6 space-y-4">
                        <!-- Nama Barang Display -->
                        <div v-if="selectedMaterial" class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Material Terpilih</label>
                                <p class="text-base font-black text-gray-900 mt-0.5">{{ selectedMaterial.name }}</p>
                                <p class="text-xs text-indigo-600 font-semibold mt-0.5">Satuan: {{ selectedMaterial.unit }}</p>
                            </div>
                            <button type="button" @click="selectedMaterial = null" class="text-xs text-indigo-600 hover:underline">
                                Ganti Barang
                            </button>
                        </div>
                        <div v-else>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Pilih Material / Barang <span class="text-red-500">*</span></label>
                            <select v-model="selectedMaterialId" @change="onWarehouseMaterialSelect" class="w-full text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 font-semibold" required>
                                <option value="" disabled>-- Pilih Barang --</option>
                                <option v-for="mat in materials.data" :key="mat.id" :value="mat.id">
                                    {{ mat.name }} (Stok: {{ formatNum(getTotalStock(mat)) }} {{ mat.unit }})
                                </option>
                            </select>
                        </div>

                        <!-- Pilihan Kondisi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2 uppercase">Kondisi Material <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="statusForm.condition = 'Layak Pakai'; if (!statusForm.purpose || statusForm.purpose.includes('Rusak') || statusForm.purpose.includes('Bekas')) statusForm.purpose = 'Retur Sisa Pasang'" 
                                    class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all flex flex-col items-center gap-1"
                                    :class="statusForm.condition === 'Layak Pakai' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm ring-2 ring-emerald-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'">
                                    <span class="text-sm">🟢</span>
                                    Layak Pakai
                                </button>
                                <button type="button" @click="statusForm.condition = 'Tidak Layak'; if (!statusForm.purpose || statusForm.purpose.includes('Sisa') || statusForm.purpose.includes('Rusak')) statusForm.purpose = 'Barang Bekas Tidak Layak'" 
                                    class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all flex flex-col items-center gap-1"
                                    :class="statusForm.condition === 'Tidak Layak' ? 'bg-amber-600 text-white border-amber-600 shadow-sm ring-2 ring-amber-200' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'">
                                    <span class="text-sm">🟡</span>
                                    Tidak Layak
                                </button>
                                <button type="button" @click="statusForm.condition = 'Rusak'; if (!statusForm.purpose || statusForm.purpose.includes('Sisa') || statusForm.purpose.includes('Bekas')) statusForm.purpose = 'Barang Rusak Lapangan'" 
                                    class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all flex flex-col items-center gap-1"
                                    :class="statusForm.condition === 'Rusak' ? 'bg-rose-600 text-white border-rose-600 shadow-sm ring-2 ring-rose-200' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'">
                                    <span class="text-sm">🔴</span>
                                    Rusak
                                </button>
                            </div>
                        </div>

                        <!-- Tipe Mutasi: Masuk (Retur) atau Keluar (Buang/Pakai) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Aksi Stok <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all"
                                    :class="statusForm.type === 'in' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-white border-gray-200 text-gray-600'">
                                    <input type="radio" value="in" v-model="statusForm.type" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs">📥 Masuk (Retur ke Gudang)</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all"
                                    :class="statusForm.type === 'out' ? 'bg-rose-50 border-rose-300 text-rose-800 font-bold' : 'bg-white border-gray-200 text-gray-600'">
                                    <input type="radio" value="out" v-model="statusForm.type" class="text-rose-600 focus:ring-rose-500">
                                    <span class="text-xs">📤 Keluar (Afkir/Dikeluarkan)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Jumlah & Petugas -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Jumlah (Qty) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0.01" v-model="statusForm.quantity" required class="w-full text-sm pl-3 pr-12 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500" placeholder="Qty">
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-gray-400 pointer-events-none">{{ selectedMaterial?.unit }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Petugas / Teknisi <span class="text-red-500">*</span></label>
                                <input type="text" v-model="statusForm.technician_name" required class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500" placeholder="Nama petugas">
                            </div>
                        </div>

                        <!-- Keterangan / Keperluan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Tujuan / Catatan <span class="text-red-500">*</span></label>
                            <input type="text" v-model="statusForm.purpose" required class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500" placeholder="Cth: Ditarik dari lapangan / Sisa pasang">
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" @click="isStatusModalOpen = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Batal</button>
                            <button type="submit" :disabled="statusForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm transition">
                                Simpan Status Kondisi
                            </button>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>

        <!-- Modal Alokasi / Distribusi Stok ke Area -->
        <Dialog :open="isDistributeModalOpen" @close="isDistributeModalOpen = false" class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" aria-hidden="true" />
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel class="w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 bg-indigo-50/70 flex items-center justify-between">
                        <DialogTitle class="text-base font-black text-indigo-950 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-indigo-600 text-white text-xs">➕</span>
                            Alokasikan / Distribusi Stok ke Area
                        </DialogTitle>
                        <button type="button" @click="isDistributeModalOpen = false" class="p-2 text-gray-400 hover:text-red-500 rounded-xl">✕</button>
                    </div>

                    <form @submit.prevent="submitDistributeForm" class="p-6 space-y-4">
                        <!-- Pilihan / Info Barang -->
                        <div v-if="distributeMaterial" class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Material Terpilih</label>
                                <p class="text-sm font-black text-gray-900 mt-0.5">{{ distributeMaterial.name }}</p>
                                <div class="flex items-center gap-3 text-xs text-indigo-700 font-semibold mt-1">
                                    <span>Kategori: {{ distributeMaterial.category || '-' }}</span>
                                    <span>•</span>
                                    <span>Satuan: {{ distributeMaterial.unit }}</span>
                                </div>
                            </div>
                            <button type="button" @click="distributeMaterial = null" class="text-xs text-indigo-600 font-bold hover:underline">
                                Ganti
                            </button>
                        </div>
                        <div v-else>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Pilih Material / Barang <span class="text-red-500">*</span></label>
                            <select v-model="distributeMaterialId" @change="onDistributeMaterialSelect" class="w-full text-sm px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-semibold" required>
                                <option value="" disabled>-- Pilih Barang --</option>
                                <option v-for="mat in materials.data" :key="mat.id" :value="mat.id">
                                    {{ mat.name }} (Total Stok: {{ formatNum(getTotalStock(mat)) }} {{ mat.unit }})
                                </option>
                            </select>
                        </div>

                        <!-- Pilih Area Tujuan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Area / Gudang Cabang Tujuan <span class="text-red-500">*</span></label>
                            <select v-model="distributeForm.area_id" class="w-full text-sm px-3.5 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-semibold bg-white" required>
                                <option value="" disabled>-- Pilih Area Gudang --</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    {{ area.name }}
                                </option>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Stok fisik barang ini akan dialokasikan ke gudang area yang dipilih.</p>
                        </div>

                        <!-- Input Kuantitas Stok (Pintar berdasarkan kategori) -->
                        <div v-if="(distributeMaterial?.category || '').toLowerCase().includes('kabel')" class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-indigo-50/40 p-3.5 rounded-2xl border border-indigo-100">
                            <div>
                                <label class="block text-xs font-bold text-indigo-900 mb-1 uppercase">Tambah Roll Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="distributeForm.added_rolls" @input="calculateCableDistribute" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-xl border border-indigo-200 bg-white font-bold text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Jml Roll" required>
                                    <span class="text-indigo-800 text-xs font-bold">roll</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-indigo-900 mb-1 uppercase">Total Meter (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="distributeForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-3 py-2 rounded-xl border border-indigo-200 bg-white font-black text-sm text-indigo-950 focus:ring-2 focus:ring-indigo-500" required>
                                    <span class="text-indigo-800 text-xs font-bold">meter</span>
                                </div>
                                <p class="text-[10px] text-indigo-600/70 mt-1">@ {{ distributeMaterial?.meter_per_roll || 1000 }} meter/roll</p>
                            </div>
                        </div>

                        <div v-else-if="(distributeMaterial?.category || '').toLowerCase().includes('klem')" class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-indigo-50/40 p-3.5 rounded-2xl border border-indigo-100">
                            <div>
                                <label class="block text-xs font-bold text-indigo-900 mb-1 uppercase">Tambah Bungkus Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="distributeForm.added_packs" @input="calculatePackDistribute" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-xl border border-indigo-200 bg-white font-bold text-sm" placeholder="Jml Bungkus" required>
                                    <span class="text-indigo-800 text-xs font-bold">bks</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-indigo-900 mb-1 uppercase">Total Pcs (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="distributeForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-3 py-2 rounded-xl border border-indigo-200 bg-white font-black text-sm text-indigo-950" required>
                                    <span class="text-indigo-800 text-xs font-bold">pcs</span>
                                </div>
                                <p class="text-[10px] text-indigo-600/70 mt-1">@ {{ distributeMaterial?.pcs_per_pack || 100 }} pcs/bungkus</p>
                            </div>
                        </div>

                        <div v-else>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Jumlah Stok Masuk <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <input v-model="distributeForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 font-bold text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Kuantitas stok masuk..." required>
                                <span class="text-gray-500 text-xs font-bold uppercase">{{ distributeMaterial?.unit || 'Unit' }}</span>
                            </div>
                        </div>

                        <!-- Update Harga (Opsional) -->
                        <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 mb-1">Harga Beli / Modal (Opsional)</label>
                                <input v-model="distributeForm.price_per_unit" type="number" min="0" class="w-full px-3 py-1.5 text-xs rounded-xl border border-gray-200 bg-white" placeholder="Rp">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 mb-1">Harga Jual (Opsional)</label>
                                <input v-model="distributeForm.selling_price" type="number" min="0" class="w-full px-3 py-1.5 text-xs rounded-xl border border-gray-200 bg-white" placeholder="Rp">
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-2 flex items-center justify-end gap-2.5">
                            <button type="button" @click="isDistributeModalOpen = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="distributeForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition flex items-center gap-1.5">
                                <span>{{ distributeForm.processing ? 'Menyimpan...' : 'Simpan & Alokasikan Stok' }}</span>
                            </button>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { debounce } from 'lodash';

const props = defineProps({
    materials: Object,
    categories: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
});

// Comprehensive Filter State
const filterSearch = ref(props.filters?.search || '');
const filterCategory = ref(props.filters?.category || '');
const filterAreaId = ref(props.filters?.area_id || '');
const filterStockStatus = ref(props.filters?.stock_status || '');
const filterUnit = ref(props.filters?.unit || '');
const filterSortBy = ref(props.filters?.sort_by || 'name_asc');

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterSearch.value) count++;
    if (filterCategory.value) count++;
    if (filterAreaId.value) count++;
    if (filterStockStatus.value) count++;
    if (filterUnit.value) count++;
    if (filterSortBy.value && filterSortBy.value !== 'name_asc') count++;
    return count;
});

const getAreaName = (id) => {
    const a = (props.areas || []).find(item => item.id == id);
    return a ? a.name : `Area #${id}`;
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'available': return 'Stok Tersedia (> 0)';
        case 'low_stock': return 'Stok Menipis (<= 20)';
        case 'out_of_stock': return 'Stok Kosong / Habis (0)';
        default: return status;
    }
};

const getAllAreasTotalStock = (material) => {
    return (material.stocks || []).reduce((a, b) => a + Number(b.stock || 0), 0);
};

const getAllAreasInitialStock = (material) => {
    const areaInitialSum = (material.stocks || []).reduce((a, b) => a + Number(b.initial_stock || 0), 0);
    if (areaInitialSum > 0) return areaInitialSum;
    return Number(material.initial_stock) > 0 ? Number(material.initial_stock) : getAllAreasTotalStock(material);
};

const getAreaStock = (material, areaId) => {
    if (!areaId) return getAllAreasTotalStock(material);
    const found = (material.stocks || []).find(s => s.area_id == areaId);
    return found ? (Number(found.stock) || 0) : 0;
};

const getAreaInitialStock = (material, areaId) => {
    if (!areaId) return getAllAreasInitialStock(material);
    const found = (material.stocks || []).find(s => s.area_id == areaId);
    if (found && Number(found.initial_stock) > 0) {
        return Number(found.initial_stock);
    }
    return found ? (Number(found.stock) || 0) : 0;
};

const getTotalStock = (material) => {
    return filterAreaId.value ? getAreaStock(material, filterAreaId.value) : getAllAreasTotalStock(material);
};

const getInitialStock = (material) => {
    return filterAreaId.value ? getAreaInitialStock(material, filterAreaId.value) : getAllAreasInitialStock(material);
};

const applyFilters = () => {
    const params = {};
    if (filterSearch.value) params.search = filterSearch.value;
    if (filterCategory.value) params.category = filterCategory.value;
    if (filterAreaId.value) params.area_id = filterAreaId.value;
    if (filterStockStatus.value) params.stock_status = filterStockStatus.value;
    if (filterUnit.value) params.unit = filterUnit.value;
    if (filterSortBy.value && filterSortBy.value !== 'name_asc') params.sort_by = filterSortBy.value;

    router.get('/warehouse-stocks', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedApply = debounce(() => {
    applyFilters();
}, 350);

const resetFilters = () => {
    filterSearch.value = '';
    filterCategory.value = '';
    filterAreaId.value = '';
    filterStockStatus.value = '';
    filterUnit.value = '';
    filterSortBy.value = 'name_asc';
    applyFilters();
};

const formatNum = (n) => {
    return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
};

const formatSecondaryUnit = (material, stockQty) => {
    stockQty = Number(stockQty) || 0;
    if (stockQty <= 0) return '';
    
    if ((material.category || '').toLowerCase().includes('isolasi') || (material.name || '').toLowerCase().includes('isolasi')) {
        const cpp = material.cm_per_pcs > 0 ? Number(material.cm_per_pcs) : 0;
        if (cpp > 0 && stockQty >= cpp) {
            const pcs = stockQty / cpp;
            return ` (~${formatNum(parseFloat(pcs.toFixed(2)))} Pcs)`;
        }
    } else if (Number(material.meter_per_roll) > 0 && material.unit === 'meter') {
        const mpr = Number(material.meter_per_roll);
        if (stockQty >= mpr) {
            const rolls = stockQty / mpr;
            return ` (~${formatNum(parseFloat(rolls.toFixed(2)))} Roll)`;
        }
    } else if (Number(material.pcs_per_pack) > 0 && material.unit === 'pcs') {
        const ppp = Number(material.pcs_per_pack);
        if (stockQty >= ppp) {
            const packs = stockQty / ppp;
            const unitLabel = ((material.category || '').toLowerCase().includes('klem') || (material.name || '').toLowerCase().includes('klem')) ? 'Bungkus' : 'Pack';
            return ` (~${formatNum(parseFloat(packs.toFixed(2)))} ${unitLabel})`;
        }
    }
    return '';
};

const isStatusModalOpen = ref(false);
const selectedMaterial = ref(null);
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

const openStatusModal = (material) => {
    selectedMaterial.value = material;
    selectedMaterialId.value = material ? material.id : '';
    statusForm.date = new Date().toISOString().split('T')[0];
    statusForm.technician_name = '';
    statusForm.type = 'in';
    statusForm.condition = 'Layak Pakai';
    statusForm.purpose = 'Retur Sisa Pasang (Layak)';
    statusForm.quantity = null;
    statusForm.notes = '';
    isStatusModalOpen.value = true;
};

const onWarehouseMaterialSelect = () => {
    const found = props.materials.data.find(m => m.id === Number(selectedMaterialId.value) || m.id === selectedMaterialId.value);
    if (found) {
        selectedMaterial.value = found;
    }
};

const submitStatusForm = () => {
    const target = selectedMaterial.value || props.materials.data.find(m => m.id === Number(selectedMaterialId.value) || m.id === selectedMaterialId.value);
    if (!target) {
        alert('Silakan pilih material terlebih dahulu');
        return;
    }
    if (!statusForm.quantity || Number(statusForm.quantity) <= 0) {
        alert('Silakan masukkan jumlah (qty) yang valid');
        return;
    }
    statusForm.items = [
        {
            material_id: target.id,
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

const deleteMaterial = (material) => {
    if (confirm(`Yakin ingin menghapus "${material.name}" dari inventory? Semua riwayat stok dan transaksi terkait barang ini akan dihapus.`)) {
        router.delete(`/warehouse-stocks/${material.id}`, {
            preserveScroll: true,
        });
    }
};

// ── Modal Alokasi / Distribusi Stok ke Area ────────────────────
const isDistributeModalOpen = ref(false);
const distributeMaterial = ref(null);
const distributeMaterialId = ref('');
const distributeForm = useForm({
    area_id: '',
    added_stock: null,
    added_rolls: null,
    added_packs: null,
    added_pieces: null,
    price_per_unit: null,
    selling_price: null,
    notes: '',
});

const openDistributeModal = (material) => {
    distributeMaterial.value = material;
    distributeMaterialId.value = material ? material.id : (props.materials.data[0]?.id || '');
    if (!material && props.materials.data.length > 0) {
        distributeMaterial.value = props.materials.data[0];
    }
    distributeForm.area_id = props.areas[0]?.id || '';
    distributeForm.added_stock = null;
    distributeForm.added_rolls = null;
    distributeForm.added_packs = null;
    distributeForm.added_pieces = null;
    distributeForm.price_per_unit = distributeMaterial.value?.price_per_unit || null;
    distributeForm.selling_price = distributeMaterial.value?.selling_price || null;
    distributeForm.notes = 'Alokasi stok / distribusi area';
    isDistributeModalOpen.value = true;
};

const onDistributeMaterialSelect = () => {
    const found = props.materials.data.find(m => m.id === Number(distributeMaterialId.value) || m.id === distributeMaterialId.value);
    if (found) {
        distributeMaterial.value = found;
        distributeForm.price_per_unit = found.price_per_unit || null;
        distributeForm.selling_price = found.selling_price || null;
    }
};

const calculateCableDistribute = () => {
    const mpr = Number(distributeMaterial.value?.meter_per_roll) || 0;
    if (mpr > 0 && distributeForm.added_rolls) {
        distributeForm.added_stock = parseFloat((Number(distributeForm.added_rolls) * mpr).toFixed(2));
    }
};

const calculatePackDistribute = () => {
    const ppp = Number(distributeMaterial.value?.pcs_per_pack) || 0;
    if (ppp > 0 && distributeForm.added_packs) {
        distributeForm.added_stock = parseFloat((Number(distributeForm.added_packs) * ppp).toFixed(2));
    }
};

const submitDistributeForm = () => {
    const target = distributeMaterial.value || props.materials.data.find(m => m.id === Number(distributeMaterialId.value) || m.id === distributeMaterialId.value);
    if (!target) {
        alert('Silakan pilih material terlebih dahulu');
        return;
    }
    if (!distributeForm.area_id) {
        alert('Silakan pilih area gudang tujuan');
        return;
    }
    if (!distributeForm.added_stock || Number(distributeForm.added_stock) <= 0) {
        alert('Silakan masukkan jumlah stok masuk yang valid (> 0)');
        return;
    }

    distributeForm.post(`/materials/${target.id}/add-stock`, {
        preserveScroll: true,
        onSuccess: () => {
            isDistributeModalOpen.value = false;
            distributeForm.reset();
        }
    });
};
</script>
