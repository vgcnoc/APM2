<template>
    <AppLayout title="Stock Opname Per Area">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-rose-100/40 to-orange-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-rose-500 to-orange-600 rounded-xl mb-4 shadow-lg shadow-rose-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Stock Opname Per Area</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Catat pengeluaran dan penerimaan barang untuk stock opname di masing-masing area.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="openModal('out')" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white transition-all duration-200 bg-gradient-to-r from-rose-600 to-orange-500 border border-transparent rounded-xl shadow-md hover:shadow-lg hover:from-rose-500 hover:to-orange-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 overflow-hidden">
                                <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Stok Keluar (Distribusi)
                            </button>
                            <button @click="openModal('in')" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-gray-700 transition-all duration-200 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 overflow-hidden">
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Stok Masuk (Retur)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10 space-y-6">
                
                <!-- Summary Metrics Bar -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Mutasi & Opname</p>
                            <h3 class="text-2xl font-black text-gray-900 mt-1">{{ formatQty(summary?.total_count || transactions.total || 0) }}</h3>
                            <p class="text-xs text-gray-500 font-medium mt-0.5">Transaksi tercatat</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-rose-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider">Stok Keluar (Distribusi)</p>
                            <h3 class="text-2xl font-black text-rose-600 mt-1">{{ formatQty(summary?.out_count || 0) }}</h3>
                            <p class="text-xs text-rose-400 mt-0.5">Pemakaian teknisi / area</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Stok Masuk (Retur)</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ formatQty(summary?.in_count || 0) }}</h3>
                            <p class="text-xs text-emerald-400 mt-0.5">Pengembalian sisa / retur</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Comprehensive Filters Card -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                    <!-- Top row: Type Tabs and Quick Search -->
                    <div class="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center justify-between">
                        <!-- Type Pill Buttons -->
                        <div class="inline-flex p-1 bg-slate-100 rounded-2xl w-fit">
                            <button 
                                type="button" 
                                @click="filterType = ''; applyFilters()" 
                                :class="[!filterType ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-900 font-medium']"
                                class="px-4 py-2 rounded-xl text-xs sm:text-sm transition-all"
                            >
                                Semua Tipe
                            </button>
                            <button 
                                type="button" 
                                @click="filterType = 'out'; applyFilters()" 
                                :class="[filterType === 'out' ? 'bg-rose-500 text-white shadow-sm font-bold' : 'text-gray-500 hover:text-rose-600 font-medium']"
                                class="px-4 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                Stok Keluar
                            </button>
                            <button 
                                type="button" 
                                @click="filterType = 'in'; applyFilters()" 
                                :class="[filterType === 'in' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-gray-500 hover:text-emerald-600 font-medium']"
                                class="px-4 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                Stok Masuk
                            </button>
                        </div>

                        <!-- Search Box -->
                        <div class="relative flex-1 group max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-rose-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input 
                                type="text" 
                                v-model="filterSearch" 
                                @input="debouncedApply" 
                                placeholder="Cari No. Ref, teknisi/penerima, area, barang, catatan..." 
                                class="w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500/40 focus:border-rose-500 transition-all placeholder:text-gray-400"
                            />
                            <button v-if="filterSearch" @click="filterSearch = ''; applyFilters()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Middle row: Dropdown filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Purpose Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Keperluan / Tujuan</label>
                            <select v-model="filterPurpose" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500/40 focus:border-rose-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Keperluan</option>
                                <option v-for="p in purposes" :key="p" :value="p">{{ p }}</option>
                            </select>
                        </div>

                        <!-- Area / Teknisi Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Area / Penerima</label>
                            <select v-model="filterTechnician" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500/40 focus:border-rose-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Area / Penerima</option>
                                <option v-for="a in areas" :key="a.id" :value="a.name">{{ a.name }}</option>
                            </select>
                        </div>

                        <!-- Material Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Material / Barang</label>
                            <select v-model="filterMaterialId" @change="applyFilters" class="w-full py-2.5 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500/40 focus:border-rose-500 transition-all font-medium text-gray-700">
                                <option value="">Semua Barang</option>
                                <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bottom row: Date Filters & Quick Presets -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-1">Pilihan Cepat:</span>
                            <button 
                                type="button" 
                                @click="setQuickDate('all')" 
                                :class="[!filterDateFrom && !filterDateTo ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
                                class="px-3 py-1.5 rounded-lg text-xs transition"
                            >
                                Semua
                            </button>
                            <button 
                                type="button" 
                                @click="setQuickDate('today')" 
                                :class="[quickActive === 'today' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
                                class="px-3 py-1.5 rounded-lg text-xs transition"
                            >
                                Hari Ini
                            </button>
                            <button 
                                type="button" 
                                @click="setQuickDate('week')" 
                                :class="[quickActive === 'week' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
                                class="px-3 py-1.5 rounded-lg text-xs transition"
                            >
                                7 Hari Terakhir
                            </button>
                            <button 
                                type="button" 
                                @click="setQuickDate('month')" 
                                :class="[quickActive === 'month' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-gray-600 hover:bg-slate-200']"
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
                        <span class="text-gray-400 font-medium">Filter Diterapkan ({{ activeFilterCount }}):</span>
                        <span v-if="filterType" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold" :class="filterType === 'out' ? 'bg-rose-50 text-rose-700 border border-rose-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'">
                            Tipe: {{ filterType === 'out' ? 'Stok Keluar' : 'Stok Masuk' }}
                            <button @click="filterType = ''; applyFilters()" class="hover:opacity-75">✕</button>
                        </span>
                        <span v-if="filterSearch" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 font-semibold border border-rose-100">
                            Pencarian: "{{ filterSearch }}"
                            <button @click="filterSearch = ''; applyFilters()" class="hover:text-rose-900">✕</button>
                        </span>
                        <span v-if="filterPurpose" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 font-semibold border border-rose-100">
                            Keperluan: {{ filterPurpose }}
                            <button @click="filterPurpose = ''; applyFilters()" class="hover:text-rose-900">✕</button>
                        </span>
                        <span v-if="filterTechnician" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 font-semibold border border-rose-100">
                            Area / Teknisi: {{ filterTechnician }}
                            <button @click="filterTechnician = ''; applyFilters()" class="hover:text-rose-900">✕</button>
                        </span>
                        <span v-if="filterMaterialId" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 font-semibold border border-rose-100">
                            Barang: {{ getMaterialName(filterMaterialId) }}
                            <button @click="filterMaterialId = ''; applyFilters()" class="hover:text-rose-900">✕</button>
                        </span>
                        <span v-if="filterDateFrom || filterDateTo" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 font-semibold border border-rose-100">
                            Rentang: {{ filterDateFrom || '...' }} s/d {{ filterDateTo || '...' }}
                            <button @click="filterDateFrom = ''; filterDateTo = ''; applyFilters()" class="hover:text-rose-900">✕</button>
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & No. Ref</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis & Tujuan</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penerima / Pengirim</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Barang</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Total Stock</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ formatDate(trx.date) }}</div>
                                        <div class="text-xs text-gray-500 mt-1 font-mono">{{ trx.transaction_number }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-2">
                                            <span v-if="trx.type === 'out'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 text-xs font-bold w-fit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                                STOK KELUAR
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-bold w-fit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                                STOK MASUK
                                            </span>
                                            <span class="text-sm text-gray-700">{{ trx.purpose }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            {{ trx.technician_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-1.5">
                                            <div v-for="item in trx.items" :key="item.id" class="flex items-center gap-2 text-sm">
                                                <div :class="['w-1.5 h-1.5 rounded-full shrink-0', trx.type === 'out' ? 'bg-rose-500' : 'bg-emerald-500']"></div>
                                                <span class="font-medium text-gray-900">{{ item.material ? item.material.name : 'Unknown' }}</span>
                                                <span v-if="item.condition" class="text-xs text-gray-400">({{ item.condition }})</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col gap-1.5 items-center">
                                            <div v-for="tot in getTransactionTotals(trx)" :key="tot.id" 
                                                 :class="['text-xs font-bold px-2.5 py-1 rounded-lg w-fit whitespace-nowrap border flex items-center gap-1.5 shadow-sm', 
                                                          trx.type === 'out' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100']">
                                                <span class="text-gray-700 font-semibold">{{ tot.name }}:</span>
                                                <span>{{ trx.type === 'out' ? '-' : '+' }}{{ formatQty(tot.quantity) }} {{ tot.unit }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="confirmDelete(trx.id)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Batalkan Mutasi">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat Mutasi / Stock Opname.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="mt-6 flex justify-center">
                    <div class="inline-flex bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <template v-for="(link, k) in transactions.links" :key="k">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border-r border-gray-100 last:border-0 bg-gray-50" v-html="link.label"></div>
                            <Link v-else :href="link.url" :class="['px-4 py-2 text-sm border-r border-gray-100 last:border-0 transition-colors hover:bg-rose-50 hover:text-rose-600', link.active ? 'bg-rose-50 text-rose-700 font-bold' : 'text-gray-600']" v-html="link.label" preserve-state preserve-scroll></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog :open="isModalOpen" @close="closeModal" class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" aria-hidden="true" />
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between shrink-0">
                        <DialogTitle class="text-lg font-black text-gray-900 flex items-center gap-2">
                            <div :class="['p-1.5 rounded-lg', form.type === 'out' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600']">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            Input {{ form.type === 'out' ? 'Stok Keluar (Distribusi)' : 'Stok Masuk (Retur Sisa)' }}
                        </DialogTitle>
                        <button @click="closeModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 overflow-hidden">
                        <div class="p-6 overflow-y-auto flex-1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Tanggal <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="form.date" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Teknisi / Karyawan <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.technician_name" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all" placeholder="Cth: Budi Pratama" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Tujuan / Keterangan <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.purpose" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all" :placeholder="form.type === 'out' ? 'Cth: Bekal Pasang Baru' : 'Cth: Sisa Pasang (Retur)'" required>
                                </div>
                            </div>

                            <!-- Items List -->
                            <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                                    <h4 class="text-sm font-bold text-gray-700">Daftar Barang</h4>
                                    <button type="button" @click="addItem" class="text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        Tambah Baris
                                    </button>
                                </div>
                                
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(item, index) in form.items" :key="index" class="p-4 flex flex-col md:flex-row gap-4 items-start md:items-center hover:bg-slate-50/50 transition-colors">
                                        
                                        <div class="w-full md:flex-1">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Pilih Barang</label>
                                            <select v-model="item.material_id" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 bg-white" required>
                                                <option value="" disabled>Pilih Barang...</option>
                                                <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                                    {{ mat.name }} (Stok: {{ mat.stock }} {{ mat.unit }})
                                                </option>
                                            </select>
                                        </div>

                                        <div class="w-full md:w-48">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Jumlah (Dalam Satuan Terkecil)</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" v-model="item.quantity" class="w-full text-sm pl-3 pr-16 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500" placeholder="Jumlah" required>
                                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                    <span class="text-xs font-bold text-gray-400">{{ getUnit(item.material_id) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="w-full md:w-auto mt-2 md:mt-0 flex justify-end">
                                            <button type="button" @click="removeItem(index)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Hapus Baris" :disabled="form.items.length === 1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        
                                    </div>
                                    <div v-if="form.items.length === 0" class="p-8 text-center text-gray-400 text-sm italic">
                                        Belum ada barang yang ditambahkan.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                                <textarea v-model="form.notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all"></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex justify-end shrink-0 rounded-b-3xl">
                            <div class="flex items-center gap-3">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing || form.items.length === 0" :class="['inline-flex items-center justify-center px-6 py-2.5 text-sm font-bold text-white transition-all border border-transparent rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed', form.type === 'out' ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500' : 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500']">
                                    <svg v-if="form.processing" class="w-4 h-4 mr-2 -ml-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Simpan & {{ form.type === 'out' ? 'Kurangi' : 'Tambah' }} Stok
                                </button>
                            </div>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { debounce } from 'lodash';

const props = defineProps({
    transactions: Object,
    materials: Array,
    purposes: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
});

// Comprehensive Filter State
const filterSearch = ref(props.filters?.search || '');
const filterType = ref(props.filters?.type || '');
const filterPurpose = ref(props.filters?.purpose || '');
const filterTechnician = ref(props.filters?.technician_name || '');
const filterMaterialId = ref(props.filters?.material_id || '');
const filterDateFrom = ref(props.filters?.date_from || '');
const filterDateTo = ref(props.filters?.date_to || '');
const quickActive = ref('');

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterSearch.value) count++;
    if (filterType.value) count++;
    if (filterPurpose.value) count++;
    if (filterTechnician.value) count++;
    if (filterMaterialId.value) count++;
    if (filterDateFrom.value || filterDateTo.value) count++;
    return count;
});

const getMaterialName = (id) => {
    const m = (props.materials || []).find(mat => mat.id == id);
    return m ? m.name : `ID #${id}`;
};

const applyFilters = () => {
    const params = {};
    if (filterSearch.value) params.search = filterSearch.value;
    if (filterType.value) params.type = filterType.value;
    if (filterPurpose.value) params.purpose = filterPurpose.value;
    if (filterTechnician.value) params.technician_name = filterTechnician.value;
    if (filterMaterialId.value) params.material_id = filterMaterialId.value;
    if (filterDateFrom.value) params.date_from = filterDateFrom.value;
    if (filterDateTo.value) params.date_to = filterDateTo.value;

    router.get('/stok-in-out', params, {
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
    filterType.value = '';
    filterPurpose.value = '';
    filterTechnician.value = '';
    filterMaterialId.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    quickActive.value = '';
    applyFilters();
};

const isModalOpen = ref(false);

const form = useForm({
    type: 'out',
    date: new Date().toISOString().split('T')[0],
    technician_name: '',
    purpose: '',
    notes: '',
    items: [
        { material_id: '', quantity: null }
    ]
});

const getUnit = (materialId) => {
    if (!materialId) return '';
    const mat = props.materials.find(m => m.id === materialId);
    return mat ? mat.unit : '';
};

const addItem = () => {
    form.items.push({ material_id: '', quantity: null });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
};

const formatQty = (qty) => {
    if (qty === null || qty === undefined) return '0';
    const num = Math.abs(Number(qty));
    if (isNaN(num)) return qty;
    return num % 1 === 0 
        ? num.toLocaleString('id-ID') 
        : num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

const getTransactionTotals = (trx) => {
    if (!trx.items || !trx.items.length) return [];
    const grouped = {};
    trx.items.forEach(item => {
        const key = item.material_id || item.id;
        const name = item.material ? item.material.name : 'Unknown';
        const unit = item.unit || (item.material ? item.material.unit : '');
        if (!grouped[key]) {
            grouped[key] = {
                id: key,
                name: name,
                quantity: 0,
                unit: unit
            };
        }
        grouped[key].quantity += Math.abs(Number(item.quantity) || 0);
    });
    return Object.values(grouped);
};

const openModal = (type) => {
    form.type = type;
    form.date = new Date().toISOString().split('T')[0];
    form.technician_name = '';
    form.notes = '';
    form.items = [{ material_id: '', quantity: null }];
    form.clearErrors();
    
    // Set default purposes based on type
    if (type === 'out') {
        form.purpose = 'Bekal Teknisi';
    } else {
        form.purpose = 'Retur Sisa Pasang';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submitForm = () => {
    form.post('/stok-in-out', {
        onSuccess: () => closeModal(),
    });
};

const confirmDelete = (id) => {
    if (confirm('Yakin ingin membatalkan transaksi ini? Stok barang akan dikembalikan ke kondisi semula.')) {
        form.delete(`/stok-in-out/${id}`);
    }
};
</script>
