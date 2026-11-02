<template>
    <AppLayout title="Order Toko (Restock)">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-indigo-100/40 to-blue-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl mb-4 shadow-lg shadow-indigo-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Order Toko (Restock)</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Distribusi restock material dari <strong>Gudang Pusat</strong> ke Area cabang. Sistem otomatis memotong stok Gudang Pusat, menambahkan stok ke Area tujuan & Data Material, serta mencatat pengeluaran ke <strong>Buku Kas Umum</strong>.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link :href="route('financial-reports.index')" class="inline-flex items-center gap-1.5 px-4 py-3 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-bold text-sm shadow-xs transition">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Buku Kas Umum
                            </Link>
                            <button id="btn-open-order" @click="openModal" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white transition-all duration-200 bg-gradient-to-r from-indigo-600 to-blue-500 border border-transparent rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 overflow-hidden">
                                <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Buat Order Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10 space-y-6">
                <!-- Summary Metrics Bar -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Transaksi Order</p>
                            <h3 class="text-2xl font-black text-gray-900 mt-1">{{ formatNumber(summary?.total_count || transactions?.total || 0) }}</h3>
                            <p class="text-xs text-indigo-600 font-medium mt-0.5">Sesuai filter aktif</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Biaya Belanja</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1">Rp {{ formatNumber(summary?.total_cost || 0) }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Tercatat di Buku Kas</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Filter Cerdas</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-2xl font-black" :class="activeFilterCount > 0 ? 'text-indigo-600' : 'text-gray-400'">{{ activeFilterCount }}</span>
                                <span class="text-xs font-semibold text-gray-500">Kriteria Aktif</span>
                            </div>
                            <button v-if="activeFilterCount > 0" @click="resetFilters" class="text-xs text-rose-500 hover:text-rose-700 font-bold mt-0.5 inline-flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                Reset Semua Filter
                            </button>
                            <p v-else class="text-xs text-gray-400 mt-0.5">Menampilkan seluruh data</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="activeFilterCount > 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-50 text-gray-400'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Comprehensive Filter Panel -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                    <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center">
                        <!-- Search Box -->
                        <div class="relative flex-1 group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input 
                                type="text" 
                                v-model="filterSearch" 
                                @input="debouncedApply" 
                                placeholder="Cari No. PO, area/wilayah, nama barang, catatan..." 
                                class="w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all placeholder:text-gray-400"
                            />
                            <button v-if="filterSearch" @click="filterSearch = ''; applyFilters()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Area Filter -->
                        <div class="w-full lg:w-48">
                            <select v-model="filterArea" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Area</option>
                                <option v-for="a in areas" :key="a.id" :value="a.name">{{ a.name }}</option>
                            </select>
                        </div>

                        <!-- Material Filter -->
                        <div class="w-full lg:w-56">
                            <select v-model="filterMaterialId" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Barang / Produk</option>
                                <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>

                        <!-- ONT Filter -->
                        <div class="w-full lg:w-40">
                            <select v-model="filterOnt" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Jenis</option>
                                <option value="with_ont">Memuat ONT</option>
                                <option value="without_ont">Non-ONT</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date Filters & Quick Presets -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-1">Pilihan Cepat:</span>
                            <button 
                                type="button" 
                                @click="setQuickDate('all')" 
                                :class="[!filterDateFrom && !filterDateTo ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
                                class="px-3 py-1.5 rounded-lg text-xs transition"
                            >
                                Semua
                            </button>
                            <button 
                                type="button" 
                                @click="setQuickDate('today')" 
                                :class="[quickActive === 'today' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
                                class="px-3 py-1.5 rounded-lg text-xs transition"
                            >
                                Hari Ini
                            </button>
                            <button 
                                type="button" 
                                @click="setQuickDate('week')" 
                                :class="[quickActive === 'week' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
                                class="px-3 py-1.5 rounded-lg text-xs transition"
                            >
                                7 Hari Terakhir
                            </button>
                            <button 
                                type="button" 
                                @click="setQuickDate('month')" 
                                :class="[quickActive === 'month' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
                                class="px-3 py-1.5 rounded-lg text-xs transition"
                            >
                                Bulan Ini
                            </button>
                        </div>

                        <!-- Date range picker -->
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1 text-xs">
                                <span class="text-gray-400 font-medium">Dari:</span>
                                <input type="date" v-model="filterDateFrom" @change="applyFilters" class="bg-transparent border-0 p-0 text-xs text-gray-700 focus:ring-0" />
                            </div>
                            <span class="text-gray-400 text-xs">s/d</span>
                            <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1 text-xs">
                                <span class="text-gray-400 font-medium">Sampai:</span>
                                <input type="date" v-model="filterDateTo" @change="applyFilters" class="bg-transparent border-0 p-0 text-xs text-gray-700 focus:ring-0" />
                            </div>
                            <button v-if="activeFilterCount > 0" @click="resetFilters" class="px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Filter">
                                Reset
                            </button>
                        </div>
                    </div>

                    <!-- Active Filter Chips -->
                    <div v-if="activeFilterCount > 0" class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-gray-400 font-medium">Filter Diterapkan:</span>
                        <span v-if="filterSearch" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                            Pencarian: "{{ filterSearch }}"
                            <button @click="filterSearch = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                        </span>
                        <span v-if="filterArea" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                            Area: {{ filterArea }}
                            <button @click="filterArea = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                        </span>
                        <span v-if="filterMaterialId" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                            Barang: {{ getMaterialName(filterMaterialId) }}
                            <button @click="filterMaterialId = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                        </span>
                        <span v-if="filterOnt" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                            {{ filterOnt === 'with_ont' ? 'Memuat ONT' : 'Non-ONT' }}
                            <button @click="filterOnt = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                        </span>
                        <span v-if="filterDateFrom || filterDateTo" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                            Rentang: {{ filterDateFrom || '...' }} s/d {{ filterDateTo || '...' }}
                            <button @click="filterDateFrom = ''; filterDateTo = ''; applyFilters()" class="hover:text-indigo-900">✕</button>
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal &amp; No. Ref</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Barang Masuk (Terkonversi)</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Biaya & Kas</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="trx in (transactions?.data || [])" :key="trx.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ formatDate(trx.date) }}</div>
                                        <div class="text-xs text-gray-500 mt-1 font-mono">{{ trx.transaction_number }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ trx.technician_name }}
                                        </div>
                                        <!-- Akun Serah Terima (Order & Pemberi) -->
                                        <div class="mt-2 space-y-1 text-xs bg-slate-50/90 border border-slate-200/80 rounded-xl p-2 max-w-[220px]">
                                            <div class="flex items-center gap-1.5 text-slate-600">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide shrink-0">Order:</span>
                                                <span class="font-bold text-slate-800 truncate" :title="getOrderAccount(trx)">
                                                    {{ getOrderAccount(trx) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-slate-600">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide shrink-0">Pemberi:</span>
                                                <span class="font-bold text-emerald-700 truncate" :title="getGiverAccount(trx)">
                                                    {{ getGiverAccount(trx) }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-2">
                                            <div v-for="item in trx.items" :key="item.id" class="flex items-center gap-2 text-sm">
                                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                                <span class="font-medium text-gray-900">{{ item.material ? item.material.name : 'Unknown' }}</span>
                                                <span class="text-gray-400">&mdash;</span>
                                                <span class="font-bold text-emerald-600">+{{ formatNumber(item.quantity) }} {{ item.material ? item.material.unit : '' }}</span>
                                                <span v-if="getItemOrderUnitLabel(item)" class="text-xs text-gray-500 font-medium">({{ getItemOrderUnitLabel(item) }})</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-black text-gray-900">Rp {{ formatNumber(trx.total_cost) }}</div>
                                        <div class="mt-1 flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-md w-fit">
                                            <svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Buku Kas Umum
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button @click="printOrder(trx.id)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Cetak Surat Serah Terima (Print)">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                            </button>
                                            <button @click="viewOrder(trx)" class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Lihat Detail">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <button v-if="hasOnt(trx)" @click="sendOnt(trx.id)" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Kirim ke Data ONT">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                                            </button>
                                            <button @click="editOrder(trx)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Edit Order">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button @click="confirmDelete(trx.id)" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Batalkan Transaksi">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!transactions?.data || transactions.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat Order Toko.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="transactions?.links && transactions.links.length > 3" class="mt-6 flex justify-center">
                    <div class="inline-flex bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <template v-for="(link, k) in transactions.links" :key="k">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border-r border-gray-100 last:border-0 bg-gray-50" v-html="link.label"></div>
                            <Link v-else :href="link.url" :class="['px-4 py-2 text-sm border-r border-gray-100 last:border-0 transition-colors hover:bg-indigo-50 hover:text-indigo-600', link.active ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600']" v-html="link.label" preserve-state preserve-scroll></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Order Modal -->
        <TransitionRoot appear :show="isModalOpen" as="template">
            <Dialog @close="closeModal" class="relative z-50">
                <TransitionChild as="template" enter="duration-200 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-150 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" aria-hidden="true" />
                </TransitionChild>

                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95 translate-y-4" enter-to="opacity-100 scale-100 translate-y-0" leave="duration-150 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                        <DialogPanel class="relative w-full max-w-5xl bg-slate-50/95 backdrop-blur-xl rounded-3xl shadow-2xl shadow-indigo-950/25 ring-1 ring-slate-900/10 flex flex-col max-h-[92vh] overflow-hidden transition-all">
                            <!-- Decorative glow -->
                            <div class="pointer-events-none absolute -top-24 -right-24 w-72 h-72 rounded-full bg-gradient-to-br from-indigo-500/15 to-cyan-400/15 blur-3xl"></div>
                            <div class="pointer-events-none absolute -bottom-24 -left-16 w-64 h-64 rounded-full bg-gradient-to-tr from-blue-500/10 to-violet-400/10 blur-3xl"></div>

                            <!-- Header -->
                            <div class="relative px-6 py-4 border-b border-slate-200/80 flex items-center justify-between shrink-0 bg-white/90 backdrop-blur">
                                <div class="flex items-center gap-3.5">
                                    <div class="p-2.5 rounded-2xl shadow-md transition-all shrink-0" :class="isViewing ? 'bg-gradient-to-br from-emerald-500 to-teal-400 text-white shadow-emerald-500/25' : (form.id ? 'bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-blue-500/25' : 'bg-gradient-to-br from-indigo-600 to-blue-500 text-white shadow-indigo-500/25')">
                                        <svg v-if="isViewing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <svg v-else-if="form.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    </div>
                                    <div>
                                        <DialogTitle class="text-base sm:text-lg font-black text-gray-900 tracking-tight leading-tight">{{ isViewing ? 'Detail Order Toko' : (form.id ? 'Edit Order Toko' : 'Buat Order Baru') }}</DialogTitle>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ isViewing ? 'Informasi detail mengenai pengiriman order ini' : 'Order material dari area/wilayah ke toko (Gudang Pusat)' }}</p>
                                    </div>
                                </div>
                                <button id="btn-close-order" type="button" @click="closeModal" class="p-2 -mr-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer" title="Tutup">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <form @submit.prevent="submitForm" class="relative flex flex-col flex-1 overflow-hidden">
                                <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5 custom-scrollbar">
                                    <!-- Informasi Header Order (Area & Pihak Serah Terima) -->
                                    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <!-- Area / Wilayah -->
                                            <div>
                                                <label for="order-area" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                    Area / Wilayah Tujuan <span class="text-rose-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <select id="order-area" v-model="form.area_name" required :disabled="isViewing" class="w-full px-3 py-2.5 text-xs sm:text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-semibold text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 cursor-pointer">
                                                        <option value="" disabled>Pilih area / wilayah tujuan</option>
                                                        <option v-for="area in areas" :key="area.id" :value="area.name">{{ area.name }}</option>
                                                    </select>
                                                </div>
                                                <p v-if="form.errors.area_name" class="mt-1 text-[11px] text-rose-500 font-medium">{{ form.errors.area_name }}</p>
                                            </div>

                                            <!-- Akun Yang Order (Pemesan) -->
                                            <div>
                                                <label for="order-user" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                                    Akun Yang Order <span class="text-rose-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <select id="order-user" v-model="form.order_user_id" required :disabled="isViewing" class="w-full px-3 py-2.5 text-xs sm:text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-semibold text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 cursor-pointer">
                                                        <option :value="null" disabled>Pilih akun pemesan</option>
                                                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role || 'User' }})</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Akun Yang Memberi (Gudang/Pemberi) -->
                                            <div>
                                                <label for="giver-user" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    Akun Yang Memberi (Gudang) <span class="text-rose-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <select id="giver-user" v-model="form.giver_user_id" required :disabled="isViewing" class="w-full px-3 py-2.5 text-xs sm:text-sm bg-slate-50/80 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all font-semibold text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 cursor-pointer">
                                                        <option :value="null" disabled>Pilih akun pemberi</option>
                                                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role || 'User' }})</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MODE BUAT / EDIT ORDER: 2 PANEL MODERN WORKSPACE -->
                                    <div v-if="!isViewing" class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                                        <!-- PANEL KIRI: KATALOG MATERIAL GUDANG (lg:col-span-6) -->
                                        <div class="lg:col-span-6 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col h-[460px]">
                                            <!-- Pencarian Material -->
                                            <div class="relative mb-3">
                                                <input id="order-product-search" ref="searchRef" v-model="search" type="text" placeholder="Cari nama atau kategori material..." class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all" @keydown.enter.prevent="pickFirst">
                                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 sm:top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <button v-if="search" type="button" @click="search = ''" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-200 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </div>

                                            <!-- Filter Kategori Pills (Horizontal scrollable) -->
                                            <div class="flex items-center gap-1.5 pb-2.5 overflow-x-auto no-scrollbar shrink-0 border-b border-slate-100">
                                                <button v-for="cat in availableCategories" :key="cat" type="button" @click="selectedCategory = cat" class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition-all shrink-0 cursor-pointer" :class="selectedCategory === cat ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'">
                                                    {{ cat }}
                                                </button>
                                            </div>

                                            <!-- Info Bar -->
                                            <div class="flex items-center justify-between py-2 text-xs text-slate-500">
                                                <span>Tersedia: <strong class="text-slate-800">{{ filteredMaterials.length }}</strong> material stok</span>
                                                <span class="text-[11px] text-indigo-600 font-medium">Klik untuk tambah</span>
                                            </div>

                                            <!-- Scrollable List Material Gudang -->
                                            <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                                <div v-for="mat in filteredMaterials" :key="mat.id" @click="addItem(mat)" class="group flex items-center justify-between gap-3 p-2.5 rounded-xl border border-slate-100 hover:border-indigo-300 hover:bg-indigo-50/40 hover:shadow-xs transition-all cursor-pointer" :class="{'bg-indigo-50/20 border-indigo-200': inCart(mat.id)}">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-xs sm:text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">{{ mat.name }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="text-[10px] font-medium text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">{{ mat.category || 'Umum' }}</span>
                                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="Number(mat.stock) > 10 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                                                                Stok: {{ formatNumber(mat.stock) }} {{ mat.unit }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="shrink-0 flex items-center gap-1.5">
                                                        <span v-if="getItemQtyInCart(mat.id) > 0" class="text-[11px] font-bold px-2 py-1 rounded-lg bg-indigo-100 text-indigo-700">
                                                            {{ getItemQtyInCart(mat.id) }} di cart
                                                        </span>
                                                        <button type="button" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-600 text-white shadow-xs group-hover:bg-indigo-700 transition-colors flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                            <span>Tambah</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Empty state -->
                                                <div v-if="filteredMaterials.length === 0" class="h-full flex flex-col items-center justify-center py-12 text-center text-slate-400">
                                                    <svg class="w-10 h-10 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                                    <p class="text-xs font-medium">Tidak ada material stok yang sesuai</p>
                                                    <p class="text-[11px] text-slate-400 mt-0.5">Coba cari kata kunci lain atau pilih tab 'Semua'</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- PANEL KANAN: KERANJANG BELANJA & CATATAN (lg:col-span-6) -->
                                        <div class="lg:col-span-6 flex flex-col gap-4">
                                            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col h-[325px]">
                                                <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900">Keranjang Belanja</h4>
                                                        <span v-if="form.items.length" class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-bold rounded-full bg-indigo-600 text-white animate-pop">{{ form.items.length }}</span>
                                                    </div>
                                                    <button v-if="form.items.length" type="button" @click="form.items = []" class="text-xs font-medium text-slate-400 hover:text-rose-500 transition-colors cursor-pointer">
                                                        Kosongkan Semua
                                                    </button>
                                                </div>

                                                <!-- List Cart items -->
                                                <div class="flex-1 overflow-y-auto space-y-2 py-2 pr-1 custom-scrollbar">
                                                    <!-- Empty state cart -->
                                                    <div v-if="form.items.length === 0" class="h-full flex flex-col items-center justify-center py-6 text-center text-slate-400">
                                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                        </div>
                                                        <p class="text-xs font-bold text-slate-600">Keranjang Masih Kosong</p>
                                                        <p class="text-[11px] text-slate-400 mt-0.5 max-w-[220px]">Klik tombol "+ Tambah" pada daftar di sebelah kiri untuk memasukkan material.</p>
                                                    </div>

                                                    <!-- Items in cart -->
                                                    <TransitionGroup tag="div" name="cart" class="space-y-2">
                                                        <div v-for="(item, index) in form.items" :key="item.material_id" class="p-3 bg-slate-50/70 border border-slate-200/90 rounded-xl hover:border-indigo-300 hover:bg-white transition-all space-y-2" :class="{'!border-rose-300 !bg-rose-50/30': isOverstock(item)}">
                                                            <!-- Item Header: Name & Remove Button -->
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="min-w-0 flex-1">
                                                                    <span class="text-xs sm:text-sm font-bold text-slate-900 truncate block">{{ materialById(item.material_id)?.name }}</span>
                                                                    <span class="text-[10px] text-slate-400 block">{{ materialById(item.material_id)?.category || 'Umum' }}</span>
                                                                </div>
                                                                <button type="button" @click="form.items.splice(index, 1)" class="p-1 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus dari keranjang">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                                </button>
                                                            </div>

                                                            <!-- Stepper, Unit & Conversion -->
                                                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-200/60">
                                                                <!-- Stepper -->
                                                                <div class="flex items-center rounded-lg border border-slate-200 bg-white overflow-hidden shadow-xs">
                                                                    <button type="button" @click="decQty(item)" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold text-xs select-none">−</button>
                                                                    <input :id="`order-qty-${index}`" v-model.number="item.quantity" type="number" min="0.01" step="any" required class="w-14 py-1 text-center text-xs font-bold border-0 focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none text-slate-900">
                                                                    <button type="button" @click="item.quantity = (Number(item.quantity) || 0) + 1" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold text-xs select-none">+</button>
                                                                </div>

                                                                <!-- Unit dropdown -->
                                                                <select :id="`order-unit-${index}`" v-model="item.purchase_unit" class="py-1 pl-2 pr-6 text-xs font-bold bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/40 text-slate-700 shadow-xs cursor-pointer">
                                                                    <option v-for="u in unitOptions(materialById(item.material_id))" :key="u.value" :value="u.value">{{ u.label }}</option>
                                                                </select>

                                                                <!-- Conversion preview badge -->
                                                                <div v-if="conversionText(item)" class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                                                                    = {{ conversionText(item) }}
                                                                </div>
                                                            </div>

                                                            <!-- Overstock Alert -->
                                                            <div v-if="isOverstock(item)" class="text-[11px] font-semibold text-rose-600 flex items-center gap-1 pt-1">
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                                <span>Stok Gudang kurang! (Tersedia: {{ formatNumber(getAvailableStock(item)) }} {{ materialById(item.material_id)?.unit }})</span>
                                                            </div>
                                                        </div>
                                                    </TransitionGroup>
                                                </div>
                                                <p v-if="form.errors.items" class="mt-1 text-xs text-rose-500 font-semibold">{{ form.errors.items }}</p>
                                            </div>

                                            <!-- Catatan Card -->
                                            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                                                <label for="order-notes" class="block text-xs font-bold text-slate-700 mb-1">Catatan Order (Opsional)</label>
                                                <textarea id="order-notes" v-model="form.notes" rows="2" placeholder="Tambahkan keterangan / instruksi order toko..." class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all resize-none"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MODE DETAIL VIEW: TABEL RINGKASAN ORDER (isViewing) -->
                                    <div v-else class="space-y-4">
                                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                                            <h4 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                                                <span>Daftar Material Yang Dipesan</span>
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ form.items.length }} item</span>
                                            </h4>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                                        <tr>
                                                            <th class="p-3">Material</th>
                                                            <th class="p-3">Kategori</th>
                                                            <th class="p-3 text-center">Jumlah Dipesan</th>
                                                            <th class="p-3 text-right">Masuk ke Stok Area</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <tr v-for="item in form.items" :key="item.material_id" class="hover:bg-slate-50/50">
                                                            <td class="p-3 font-bold text-slate-800">{{ materialById(item.material_id)?.name }}</td>
                                                            <td class="p-3 text-slate-500">{{ materialById(item.material_id)?.category || 'Umum' }}</td>
                                                            <td class="p-3 text-center font-semibold text-slate-700">
                                                                {{ formatNumber(item.quantity) }} {{ unitOptions(materialById(item.material_id)).find(o => o.value === item.purchase_unit)?.label || item.purchase_unit }}
                                                            </td>
                                                            <td class="p-3 text-right font-bold text-emerald-600">
                                                                {{ conversionText(item) || `${formatNumber(item.quantity)} ${materialById(item.material_id)?.unit || ''}` }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div v-if="form.notes" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                                            <h5 class="text-xs font-bold text-slate-700 mb-1">Catatan:</h5>
                                            <p class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 whitespace-pre-wrap">{{ form.notes }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sticky Footer Modal -->
                                <div class="px-6 py-4 border-t border-slate-200/80 bg-white/95 backdrop-blur flex flex-wrap items-center justify-between gap-3 shrink-0">
                                    <div class="text-xs text-slate-500">
                                        <span v-if="hasOverstockItems" class="text-rose-600 font-bold flex items-center gap-1.5">
                                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                            Stok Gudang Utama tidak mencukupi untuk item bertanda merah!
                                        </span>
                                        <div v-else-if="form.items.length" class="flex flex-col">
                                            <span class="font-medium text-slate-700">{{ form.items.length }} jenis material dipilih</span>
                                            <span class="font-bold text-emerald-600 text-sm">Estimasi Nilai: Rp {{ formatNumber(estimatedTotalCost) }}</span>
                                        </div>
                                        <span v-else class="text-slate-400">Pilih material dari daftar di sebelah kiri</span>
                                    </div>

                                    <div class="flex items-center gap-2.5">
                                        <button v-if="isViewing && form.id" type="button" @click="printOrder(form.id)" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs sm:text-sm font-bold text-white rounded-xl bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                            Cetak Surat Jalan
                                        </button>
                                        <button id="btn-cancel-order" type="button" @click="closeModal" class="px-5 py-2.5 text-xs sm:text-sm font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer">
                                            {{ isViewing ? 'Tutup' : 'Batal' }}
                                        </button>
                                        <button v-if="!isViewing" id="btn-submit-order" type="submit" :disabled="form.processing || form.items.length === 0 || !form.area_name || hasOverstockItems" class="inline-flex items-center px-5 py-2.5 text-xs sm:text-sm font-bold text-white rounded-xl bg-gradient-to-r from-indigo-700 to-blue-600 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 transition-all cursor-pointer">
                                            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            {{ form.id ? 'Simpan Perubahan' : 'Kirim Order' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { debounce } from 'lodash';

const props = defineProps({
    transactions: Object,
    materials: Array,
    areas: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    currentUser: { type: Object, default: () => null },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
});

// Comprehensive Filters state
const filterSearch = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area || '');
const filterMaterialId = ref(props.filters?.material_id || '');
const filterDateFrom = ref(props.filters?.date_from || '');
const filterDateTo = ref(props.filters?.date_to || '');
const filterOnt = ref(props.filters?.ont_filter || '');
const quickActive = ref('');

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterSearch.value) count++;
    if (filterArea.value) count++;
    if (filterMaterialId.value) count++;
    if (filterDateFrom.value || filterDateTo.value) count++;
    if (filterOnt.value) count++;
    return count;
});

const getMaterialName = (id) => {
    const m = (props.materials || []).find(mat => mat.id == id);
    return m ? m.name : `ID #${id}`;
};

const applyFilters = () => {
    const params = {};
    if (filterSearch.value) params.search = filterSearch.value;
    if (filterArea.value) params.area = filterArea.value;
    if (filterMaterialId.value) params.material_id = filterMaterialId.value;
    if (filterDateFrom.value) params.date_from = filterDateFrom.value;
    if (filterDateTo.value) params.date_to = filterDateTo.value;
    if (filterOnt.value) params.ont_filter = filterOnt.value;

    router.get('/order-toko', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedApply = debounce(() => {
    applyFilters();
}, 350);

const setQuickDate = (preset) => {
    quickActive.value = preset;
    const today = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (preset === 'today') {
        filterDateFrom.value = fmt(today);
        filterDateTo.value = fmt(today);
    } else if (preset === 'week') {
        const last7 = new Date();
        last7.setDate(today.getDate() - 7);
        filterDateFrom.value = fmt(last7);
        filterDateTo.value = fmt(today);
    } else if (preset === 'month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        filterDateFrom.value = fmt(firstDay);
        filterDateTo.value = fmt(today);
    } else if (preset === 'all') {
        filterDateFrom.value = '';
        filterDateTo.value = '';
    }
    applyFilters();
};

const resetFilters = () => {
    filterSearch.value = '';
    filterArea.value = '';
    filterMaterialId.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    filterOnt.value = '';
    quickActive.value = '';
    applyFilters();
};

const isModalOpen = ref(false);
const pickerOpen = ref(false);
const search = ref('');
const selectedCategory = ref('Semua');
const selectedMaterial = ref(null);
const pickerRef = ref(null);
const searchRef = ref(null);
const isViewing = ref(false);

const form = useForm({
    id: null,
    area_name: '',
    order_user_id: null,
    giver_user_id: 1,
    order_account_name: '',
    giver_account_name: '',
    date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [],
});

const materialById = (id) => (props.materials || []).find(m => m.id === id);
const inCart = (id) => form.items.some(i => i.material_id === id);

const availableCategories = computed(() => {
    const cats = new Set();
    (props.materials || []).forEach(m => {
        if (m.category && m.category.trim()) {
            cats.add(m.category.trim());
        }
    });
    return ['Semua', ...Array.from(cats).sort()];
});

const filteredMaterials = computed(() => {
    // Only show materials that have stock > 0
    const availableMaterials = (props.materials || []).filter(m => Number(m.stock) > 0);
    const q = search.value.trim().toLowerCase();
    
    return availableMaterials.filter(m => {
        const matchesSearch = !q || (m.name || '').toLowerCase().includes(q) || (m.category || '').toLowerCase().includes(q);
        const matchesCategory = selectedCategory.value === 'Semua' || (m.category || '').toLowerCase() === selectedCategory.value.toLowerCase();
        return matchesSearch && matchesCategory;
    });
});

const addItem = (mat) => {
    if (!mat || isViewing.value) return;
    const existing = form.items.find(i => i.material_id === mat.id);
    if (existing) {
        existing.quantity = (Number(existing.quantity) || 0) + 1;
    } else {
        form.items.push({
            material_id: mat.id,
            purchase_unit: unitOptions(mat)[0].value,
            quantity: 1,
            original_qty: 0,
        });
    }
};

const getItemQtyInCart = (matId) => {
    const item = form.items.find(i => i.material_id === matId);
    return item ? Number(item.quantity) || 0 : 0;
};


// Satuan order yang tersedia per material (selaras dengan konversi di PurchaseOrderController)
const unitOptions = (mat) => {
    if (!mat) return [{ value: 'base', label: 'pcs' }];
    const baseOption = { value: 'base', label: mat.unit || 'pcs' };
    if (mat.category === 'Isolasi' || (mat.name && mat.name.toLowerCase().includes('isolasi'))) {
        return [{ value: 'pack', label: 'Bungkus / Pack' }];
    }
    if (mat.category === 'Paku Klem' || mat.category === 'Klem' || (mat.name && mat.name.toLowerCase().includes('klem'))) {
        return [{ value: 'pack', label: 'Bungkus' }, baseOption];
    }
    if (Number(mat.meter_per_roll) > 0) return [{ value: 'roll', label: 'Roll' }, baseOption];
    if (Number(mat.pcs_per_pack) > 0) return [{ value: 'pack', label: 'Pack' }, baseOption];
    return [baseOption];
};

const resolvePurchaseUnit = (mat, savedUnit) => {
    if (!mat) return 'base';
    const options = unitOptions(mat);
    if (!savedUnit) return 'base';

    const matchVal = options.find(o => o.value === savedUnit);
    if (matchVal) return matchVal.value;

    const sLower = String(savedUnit).toLowerCase().trim();
    const matchValLower = options.find(o => o.value.toLowerCase() === sLower);
    if (matchValLower) return matchValLower.value;

    if (sLower === 'bungkus' || sLower === 'pack' || sLower === 'bks') {
        const hasPack = options.find(o => o.value === 'pack');
        if (hasPack) return 'pack';
    }
    if (sLower === 'roll' || sLower === 'rol') {
        const hasRoll = options.find(o => o.value === 'roll');
        if (hasRoll) return 'roll';
    }
    if (sLower === 'pcs' || sLower === 'pc' || sLower === 'biji') {
        const hasPcs = options.find(o => o.value === 'pcs');
        if (hasPcs) return 'pcs';
    }

    const matchLabel = options.find(o => o.label.toLowerCase() === sLower);
    if (matchLabel) return matchLabel.value;

    return 'base';
};

const getItemOrderUnitLabel = (item) => {
    const mat = item.material || materialById(item.material_id);
    if (!mat || !item.unit || item.unit === 'base') return '';
    if (item.unit.toLowerCase() === (mat.unit || '').toLowerCase()) return '';

    const pUnit = resolvePurchaseUnit(mat, item.unit);
    if (pUnit === 'base') return '';

    const qty = reverseQty({
        material_id: item.material_id,
        purchase_unit: pUnit,
        quantity: item.quantity,
    });

    const opt = unitOptions(mat).find(o => o.value === pUnit);
    const unitLabel = opt ? opt.label : item.unit;
    return `${formatNumber(qty)} ${unitLabel}`;
};

const reverseQty = (item) => {
    const mat = materialById(item.material_id);
    const qty = Number(item.quantity) || 0;
    if (!mat || item.purchase_unit === 'base' || !item.purchase_unit) return qty;
    
    if (item.purchase_unit === 'roll' && Number(mat.meter_per_roll) > 0) return qty / Number(mat.meter_per_roll);
    if ((mat.category === 'Isolasi' || (mat.name && mat.name.toLowerCase().includes('isolasi'))) && item.purchase_unit === 'pack') {
        const factor = (Number(mat.pcs_per_pack) > 0 ? Number(mat.pcs_per_pack) : 1) * (Number(mat.cm_per_pcs) > 0 ? Number(mat.cm_per_pcs) : 50);
        return qty / factor;
    }
    if (item.purchase_unit === 'pack' && Number(mat.pcs_per_pack) > 0) return qty / Number(mat.pcs_per_pack);
    if (item.purchase_unit === 'pcs' && Number(mat.cm_per_pcs) > 0) return qty / Number(mat.cm_per_pcs);
    return qty;
};

const getAvailableStock = (item) => {
    const mat = materialById(item.material_id);
    if (!mat) return 0;
    return Number(mat.stock) + (Number(item.original_qty) || 0);
};

const isOverstock = (item) => {
    return convertedQty(item) > getAvailableStock(item);
};

const hasOverstockItems = computed(() => {
    return form.items.some(item => isOverstock(item));
});

const convertedQty = (item) => {
    const mat = materialById(item.material_id);
    const qty = Number(item.quantity) || 0;
    if (!mat) return qty;
    const isIsolasi = (mat.category === 'Isolasi' || (mat.name && mat.name.toLowerCase().includes('isolasi')));
    if (item.purchase_unit === 'roll' && Number(mat.meter_per_roll) > 0) {
        return qty * Number(mat.meter_per_roll);
    }
    if (isIsolasi && (item.purchase_unit === 'pack' || item.purchase_unit === 'bungkus' || item.purchase_unit === 'base')) {
        const ppp = Number(mat.pcs_per_pack) > 0 ? Number(mat.pcs_per_pack) : 1;
        const cpp = Number(mat.cm_per_pcs) > 0 ? Number(mat.cm_per_pcs) : 50;
        return qty * ppp * cpp;
    }
    if (item.purchase_unit === 'pack' && Number(mat.pcs_per_pack) > 0) {
        return qty * Number(mat.pcs_per_pack);
    }
    if (item.purchase_unit === 'pcs' && Number(mat.cm_per_pcs) > 0) {
        return qty * Number(mat.cm_per_pcs);
    }
    return qty;
};

const conversionText = (item) => {
    const mat = materialById(item.material_id);
    if (!mat) return '';
    if (mat.category === 'Isolasi' || (mat.name && mat.name.toLowerCase().includes('isolasi'))) {
        return `${formatNumber(convertedQty(item))} cm`;
    }
    if (item.purchase_unit === 'base') return '';
    return `${formatNumber(convertedQty(item))} ${mat?.unit || ''}`;
};

const estimatedTotalCost = computed(() => {
    return form.items.reduce((sum, item) => {
        const mat = materialById(item.material_id);
        if (!mat) return sum;
        const qty = Number(item.quantity) || 0;
        const price = Number(mat.price_per_unit) || 0;
        const isIsolasi = (mat.category === 'Isolasi' || (mat.name && mat.name.toLowerCase().includes('isolasi')));
        
        if (item.purchase_unit === 'roll' || item.purchase_unit === 'pack' || item.purchase_unit === 'bungkus') {
            return sum + (qty * price);
        }
        
        if (item.purchase_unit === 'base' || item.purchase_unit === mat.unit) {
            let baseRate = price;
            if (Number(mat.meter_per_roll) > 0) {
                baseRate = price / Number(mat.meter_per_roll);
            } else if (isIsolasi) {
                const factor = (Number(mat.pcs_per_pack) || 1) * (Number(mat.cm_per_pcs) || 50);
                baseRate = factor > 0 ? (price / factor) : price;
            } else if (Number(mat.pcs_per_pack) > 0) {
                baseRate = price / Number(mat.pcs_per_pack);
            }
            return sum + (convertedQty(item) * baseRate);
        }
        
        return sum + (qty * price);
    }, 0);
});

const togglePicker = async () => {
    pickerOpen.value = !pickerOpen.value;
    if (pickerOpen.value) {
        search.value = '';
        await nextTick();
        searchRef.value?.focus();
    }
};

const selectMaterial = (mat) => {
    selectedMaterial.value = mat;
    pickerOpen.value = false;
};

const pickFirst = () => {
    if (filteredMaterials.value.length) addItem(filteredMaterials.value[0]);
};

const addToCart = () => {
    const mat = selectedMaterial.value;
    if (!mat) return;
    const existing = form.items.find(i => i.material_id === mat.id);
    if (existing) {
        existing.quantity = (Number(existing.quantity) || 0) + 1;
    } else {
        form.items.push({
            material_id: mat.id,
            purchase_unit: unitOptions(mat)[0].value,
            quantity: 1,
            original_qty: 0,
        });
    }
    selectedMaterial.value = null;
    search.value = '';
    selectedCategory.value = 'Semua';
};

const decQty = (item) => {
    const q = (Number(item.quantity) || 0) - 1;
    item.quantity = q > 0 ? q : 1;
};

const onClickOutside = (e) => {
    if (pickerOpen.value && pickerRef.value && !pickerRef.value.contains(e.target)) {
        pickerOpen.value = false;
    }
};
onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num || 0);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
};

const openModal = () => {
    form.id = null;
    form.area_name = '';
    form.order_user_id = props.currentUser?.id || (props.users && props.users[0]?.id) || null;
    form.giver_user_id = 1;
    form.order_account_name = '';
    form.giver_account_name = '';
    form.date = new Date().toISOString().slice(0, 10);
    form.notes = '';
    form.items = [];
    form.clearErrors();
    selectedMaterial.value = null;
    isViewing.value = false;
    isModalOpen.value = true;
};

const viewOrder = (trx) => {
    form.reset();
    form.clearErrors();
    form.id = trx.id;
    form.area_name = trx.technician_name;
    form.order_user_id = trx.order_user_id || trx.user_id || null;
    form.giver_user_id = trx.giver_user_id || 1;
    form.order_account_name = getOrderAccount(trx);
    form.giver_account_name = getGiverAccount(trx);
    form.date = trx.date;
    form.notes = trx.notes;
    form.items = trx.items.map(item => {
        const mat = item.material || materialById(item.material_id);
        const pUnit = resolvePurchaseUnit(mat, item.unit);
        const qty = reverseQty({
            material_id: item.material_id,
            purchase_unit: pUnit,
            quantity: item.quantity,
        });
        return {
            id: item.id,
            material_id: item.material_id,
            purchase_unit: pUnit,
            quantity: qty,
            price: item.price_per_unit,
            original_qty: Number(item.quantity) || 0,
        };
    });
    isViewing.value = true;
    isModalOpen.value = true;
};

const hasOnt = (trx) => {
    return !!(trx.items && trx.items.some(item => {
        if (!item.material) return false;
        const isOnt = (item.material.category || '').toUpperCase() === 'ONT' || (item.material.name || '').toLowerCase().includes('ont');
        if (isOnt) {
            const alreadySent = item.onts ? item.onts.length : 0;
            return alreadySent < item.quantity;
        }
        return false;
    }));
};

const sendOnt = (id) => {
    if (confirm('Kirim semua barang ONT dari order ini ke Data ONT?')) {
        router.post(route('order-toko.send-ont', id), {}, {
            preserveScroll: true,
        });
    }
};

const editOrder = (trx) => {
    form.reset();
    form.clearErrors();
    form.id = trx.id;
    form.area_name = trx.technician_name;
    form.order_user_id = trx.order_user_id || trx.user_id || null;
    form.giver_user_id = trx.giver_user_id || 1;
    form.order_account_name = getOrderAccount(trx);
    form.giver_account_name = getGiverAccount(trx);
    form.date = trx.date;
    form.notes = trx.notes;
    form.items = trx.items.map(item => {
        const mat = item.material || materialById(item.material_id);
        const pUnit = resolvePurchaseUnit(mat, item.unit);
        const qty = reverseQty({
            material_id: item.material_id,
            purchase_unit: pUnit,
            quantity: item.quantity,
        });
        return {
            id: item.id,
            material_id: item.material_id,
            purchase_unit: pUnit,
            quantity: qty,
            price: item.price_per_unit,
            original_qty: Number(item.quantity) || 0,
        };
    });
    isViewing.value = false;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    isViewing.value = false;
    setTimeout(() => {
        form.reset();
        form.id = null;
        form.clearErrors();
        search.value = '';
        selectedCategory.value = 'Semua';
        pickerOpen.value = false;
    }, 200);
};

const submitForm = () => {
    if (form.items.length === 0) return;
    
    if (form.id) {
        form.put(`/order-toko/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/order-toko', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (id) => {
    if (confirm('Yakin ingin membatalkan transaksi ini? Stok barang akan dikembalikan ke Gudang Utama dan ditarik dari Area!')) {
        useForm({}).delete(`/order-toko/${id}`, { preserveScroll: true });
    }
};

const getOrderAccount = (trx) => {
    return trx.order_user?.name || trx.order_account_name || trx.user?.name || '-';
};

const getGiverAccount = (trx) => {
    return trx.giver_user?.name || trx.giver_account_name || 'Super Admin (Gudang Pusat)';
};

const printOrder = (id) => {
    if (!id) return;
    window.open(`/order-toko/${id}/print?auto=true`, '_blank');
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.cart-enter-active, .cart-leave-active { transition: all 0.25s ease; }
.cart-enter-from { opacity: 0; transform: translateY(-6px) scale(0.98); }
.cart-leave-to { opacity: 0; transform: translateX(16px); }
.animate-pop { animation: pop 0.3s ease; }
@keyframes pop { 0% { transform: scale(0.6); } 60% { transform: scale(1.15); } 100% { transform: scale(1); } }
</style>
