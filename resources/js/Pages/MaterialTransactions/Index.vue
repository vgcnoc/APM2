<template>
    <AppLayout title="Riwayat Order & Pengambilan" subtitle="Daftar surat jalan dan pengeluaran material">
        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Transaksi -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg shadow-blue-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-blue-100 font-medium text-sm mb-1">Total Transaksi</p>
                                <h3 class="text-3xl font-bold">{{ formatNumber(summary.total_transactions) }} <span class="text-lg font-normal text-blue-200">Order</span></h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Item Keluar -->
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg shadow-emerald-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-emerald-100 font-medium text-sm mb-1">Total Item Dikeluarkan</p>
                                <h3 class="text-3xl font-bold">{{ formatNumber(summary.total_items) }} <span class="text-lg font-normal text-emerald-200">Pcs</span></h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Nilai Keluar -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-lg shadow-amber-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-amber-100 font-medium text-sm mb-1">Nilai Barang Keluar</p>
                                <h3 class="text-3xl font-bold"><span class="text-lg font-normal text-amber-200 mr-1">Rp</span>{{ formatNumber(summary.total_cost) }}</h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Actions -->
            <div class="glass-card p-5 animate-fade-in-up">
                <div class="flex flex-col lg:flex-row justify-between gap-4">
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                            <div class="relative">
                                <input 
                                    v-model="filterForm.search"
                                    type="text" 
                                    placeholder="Cari No. Transaksi, Petugas..." 
                                    class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                                >
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Date Range Start -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                            <input 
                                v-model="filterForm.start_date"
                                type="date" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                        </div>

                        <!-- Date Range End -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                            <input 
                                v-model="filterForm.end_date"
                                type="date" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                        </div>

                        <!-- Technician Filter -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Petugas</label>
                            <select 
                                v-model="filterForm.technician"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                                <option value="">Semua Petugas</option>
                                <option v-for="tech in technicians" :key="tech" :value="tech">{{ tech }}</option>
                            </select>
                        </div>

                        <!-- Area / Wilayah Filter -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Area / Wilayah</label>
                            <select 
                                v-model="filterForm.area_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                                <option value="">Semua Area</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-end gap-3 shrink-0">
                        <button 
                            v-if="selectedItems.length > 0"
                            @click="deleteSelected"
                            class="px-4 py-2.5 rounded-xl border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 font-medium text-sm transition-all flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus ({{ selectedItems.length }})
                        </button>
                        <button 
                            @click="resetFilters" 
                            class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 bg-white hover:bg-gray-50 font-medium text-sm transition-all flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reset
                        </button>
                        <button @click="openModal" class="btn-primary py-2.5 flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Buat Order Baru
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up mb-4">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>
            
            <div v-if="$page.props.flash?.error" class="flex items-center gap-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 animate-fade-in-up mb-4">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 w-12">
                                    <input type="checkbox" @change="toggleAll" :checked="isAllSelected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 transition-all">
                                </th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Transaksi / Tgl</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan / Petugas</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Barang & Stok Awal</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Masuk (+)</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Keluar (-)</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Sisa Stok</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Total Tagihan (Jual)</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in transactions.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6 align-top">
                                    <input type="checkbox" v-model="selectedItems" :value="item.id" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-1 transition-all">
                                </td>
                                <td class="py-4 px-6 align-top">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold text-gray-900">{{ item.transaction_number }}</p>
                                        <span v-if="item.status === 'pending'" class="px-2 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-700 rounded-full">Pending</span>
                                        <span v-else-if="item.status === 'approved' && item.type === 'in'" class="px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-700 rounded-full">Disetujui</span>
                                        <span v-else-if="item.status === 'rejected'" class="px-2 py-0.5 text-[10px] font-semibold bg-red-100 text-red-700 rounded-full">Ditolak</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ item.date }}</p>
                                </td>
                                <td class="py-4 px-6 align-top">
                                    <p class="text-sm font-semibold text-gray-900">{{ item.technician_name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ item.purpose }} 
                                        <span v-if="item.area" class="text-blue-500 font-medium">({{ item.area }})</span>
                                    </p>
                                    <div v-if="item.notes" class="mt-2 text-[11px] text-gray-500 bg-gray-50 p-2 rounded border border-gray-100 whitespace-pre-line">
                                        {{ item.notes }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-top">
                                    <div class="space-y-3">
                                        <div v-for="detail in item.items" :key="detail.id" class="flex flex-col h-10 justify-center">
                                            <span class="font-bold text-gray-800 text-xs truncate max-w-[200px]" :title="detail.material ? detail.material.name : 'Unknown'">
                                                {{ detail.material ? detail.material.name : 'Unknown' }}
                                            </span>
                                            <span class="text-[10px] text-gray-500 mt-0.5 leading-tight">
                                                Stok Awal: <span class="font-semibold text-gray-700">
                                                    {{ detail.stock_before !== null ? formatNumber(detail.stock_before) + ' ' + (detail.material ? (detail.material.category === 'Kabel' ? 'meter' : (detail.material.category === 'Isolasi' ? 'cm' : (detail.material.category === 'Paku Klem' ? 'pcs' : (detail.material.unit || '')))) : '') : '-' }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-top text-center bg-emerald-50/30 border-l border-gray-100">
                                    <div class="space-y-3">
                                        <div v-for="detail in item.items" :key="'in-'+detail.id" class="flex flex-col h-10 justify-center items-center">
                                            <template v-if="item.type === 'in'">
                                                <div class="flex items-baseline gap-1" v-if="detail.stock_after !== null && detail.stock_before !== null">
                                                    <span class="text-xs font-black text-emerald-600">+{{ formatNumber(Math.abs(detail.stock_after - detail.stock_before)) }}</span>
                                                    <span class="text-[10px] text-gray-500 font-medium">{{ detail.material ? (detail.material.category === 'Kabel' ? 'meter' : (detail.material.category === 'Isolasi' ? 'cm' : (detail.material.category === 'Paku Klem' ? 'pcs' : (detail.material.unit || 'pcs')))) : 'pcs' }}</span>
                                                </div>
                                                <span v-if="detail.unit && detail.unit.toLowerCase() !== (detail.material ? (detail.material.category === 'Kabel' ? 'meter' : (detail.material.category === 'Isolasi' ? 'cm' : (detail.material.category === 'Paku Klem' ? 'pcs' : detail.material.unit))) : 'pcs')" class="text-[9px] mt-0.5 text-gray-400">
                                                    (Asli: {{ formatNumber(detail.quantity) }} {{ detail.unit }})
                                                </span>
                                            </template>
                                            <span v-else class="text-gray-300">-</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-top text-center bg-orange-50/30 border-r border-gray-100">
                                    <div class="space-y-3">
                                        <div v-for="detail in item.items" :key="'out-'+detail.id" class="flex flex-col h-10 justify-center items-center">
                                            <template v-if="item.type === 'out'">
                                                <div class="flex items-baseline gap-1" v-if="detail.stock_after !== null && detail.stock_before !== null">
                                                    <span class="text-xs font-black text-orange-600">-{{ formatNumber(Math.abs(detail.stock_after - detail.stock_before)) }}</span>
                                                    <span class="text-[10px] text-gray-500 font-medium">{{ detail.material ? (detail.material.category === 'Kabel' ? 'meter' : (detail.material.category === 'Isolasi' ? 'cm' : (detail.material.category === 'Paku Klem' ? 'pcs' : (detail.material.unit || 'pcs')))) : 'pcs' }}</span>
                                                </div>
                                                <span v-if="detail.unit && detail.unit.toLowerCase() !== (detail.material ? (detail.material.category === 'Kabel' ? 'meter' : (detail.material.category === 'Isolasi' ? 'cm' : (detail.material.category === 'Paku Klem' ? 'pcs' : detail.material.unit))) : 'pcs')" class="text-[9px] mt-0.5 text-gray-400">
                                                    (Asli: {{ formatNumber(detail.quantity) }} {{ detail.unit }})
                                                </span>
                                            </template>
                                            <span v-else class="text-gray-300">-</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-top text-center">
                                    <div class="space-y-3">
                                        <div v-for="detail in item.items" :key="'sisa-'+detail.id" class="flex flex-col h-10 justify-center">
                                            <template v-if="detail.stock_after !== null">
                                                <span class="text-sm font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 inline-block w-fit mx-auto">
                                                    {{ formatNumber(detail.stock_after) + ' ' + (detail.material ? (detail.material.category === 'Kabel' ? 'meter' : (detail.material.category === 'Isolasi' ? 'cm' : (detail.material.category === 'Paku Klem' ? 'pcs' : (detail.material.unit || '')))) : '') }}
                                                </span>
                                            </template>
                                            <span v-else class="text-gray-300">-</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right align-top">
                                    <span class="text-sm font-bold text-gray-900">Rp {{ formatNumber(item.total_cost) }}</span>
                                </td>
                                <td class="py-4 px-6 text-right align-top">
                                    <div class="flex flex-col items-end gap-2">
                                        <div class="flex items-center justify-end gap-2">
                                            <template v-if="item.status === 'pending' && ($page.props.auth?.user?.roles?.includes('admin') || $page.props.auth?.user?.roles?.includes('super_admin'))">
                                                <button @click="approveItem(item)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg hover:bg-emerald-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Terima
                                                </button>
                                                <button @click="rejectItem(item)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Tolak
                                                </button>
                                            </template>
                                            
                                            <Link :href="`/material-transactions/${item.id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Detail
                                        </Link>
                                        <Link :href="route('material-transactions.edit', item.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-100 rounded-lg hover:bg-amber-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                            Edit
                                        </Link>
                                        <button @click="deleteItem(item)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Hapus
                                        </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="6" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada riwayat pengambilan barang.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div v-if="transactions.links && transactions.data.length > 0" class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium text-gray-900">{{ transactions.from }}</span> sampai <span class="font-medium text-gray-900">{{ transactions.to }}</span> dari <span class="font-medium text-gray-900">{{ transactions.total }}</span> transaksi
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, pIndex) in transactions.links" :key="pIndex">
                        <Link 
                            v-if="link.url"
                            :href="link.url" 
                            v-html="link.label"
                            class="px-3 py-1 text-sm border rounded-lg transition-colors"
                            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                        />
                        <span 
                            v-else 
                            v-html="link.label" 
                            class="px-3 py-1 text-sm border border-gray-200 rounded-lg text-gray-400 bg-gray-50"
                        ></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog :open="isModalOpen" @close="closeModal" class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" aria-hidden="true" />
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel class="w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <div>
                                <DialogTitle class="text-base font-bold text-gray-900 leading-tight">Buat Order Baru</DialogTitle>
                                <p class="text-[13px] text-gray-500">Order material dari cabang ke toko</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitOrder">
                        <!-- Body -->
                        <div class="px-6 py-5 space-y-5 overflow-y-auto flex-1 text-sm">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Cabang *</label>
                                <select v-model="orderForm.area_id" required @change="onAreaChange" class="w-full border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm py-2">
                                    <option value="">Pilih cabang</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                </select>
                            </div>
                            
                            <div class="border border-gray-200 p-4 rounded-xl space-y-4">
                                <label class="block font-semibold text-gray-700">Keranjang Belanja</label>
                                <div class="flex gap-2">
                                    <select v-model="tempMaterialId" :disabled="!orderForm.area_id" class="flex-1 border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm py-2 disabled:bg-gray-100 disabled:text-gray-400">
                                        <option value="">Pilih produk...</option>
                                        <option v-for="mat in materials" :key="mat.id" :value="mat.id">{{ mat.name }} (Stok Gudang: {{ mat.stock }} {{ mat.unit }})</option>
                                    </select>
                                    <button type="button" @click="addCartItem" :disabled="!tempMaterialId || !orderForm.area_id" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 font-semibold hover:bg-gray-50 disabled:opacity-50">Tambah</button>
                                </div>
                                
                                <div v-if="orderForm.items.length > 0" class="space-y-3 mt-4 border-t border-gray-100 pt-4">
                                    <div v-for="(item, idx) in orderForm.items" :key="idx" class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 bg-gray-50 rounded-lg relative group">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 truncate">{{ item.material_name }}</p>
                                            <p class="text-[11px] text-emerald-600">Sisa stok: {{ (item.is_cable && item.unit_mode === 'roll') ? (item.max_stock / item.meter_per_roll).toFixed(2) + ' Roll' : (item.is_pack && item.unit_mode === 'bungkus' ? (item.max_stock / item.pcs_per_pack).toFixed(2) + ' Bungkus' : (item.is_isolasi && item.unit_mode === 'pack' ? (item.max_stock / ((item.cm_per_pcs || 1) * (item.pcs_per_pack || 1))).toFixed(2) + ' Pack' : item.max_stock + ' ' + item.unit_manual)) }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <input type="number" step="0.01" min="0.01" :max="item.is_cable && item.unit_mode === 'roll' ? (item.max_stock / (item.meter_per_roll || 1)) : (item.is_pack && item.unit_mode === 'bungkus' ? (item.max_stock / (item.pcs_per_pack || 1)) : (item.is_isolasi && item.unit_mode === 'pack' ? (item.max_stock / ((item.cm_per_pcs || 1) * (item.pcs_per_pack || 1))) : item.max_stock))" v-model="item.input_quantity" class="w-20 border-gray-200 rounded-lg text-sm py-1.5 focus:ring-blue-500" placeholder="Jml" required>
                                            
                                            <select v-if="item.is_cable" v-model="item.unit_mode" class="w-24 border-gray-200 rounded-lg text-sm py-1.5 focus:ring-blue-500">
                                                <option value="meter">Meter</option><option value="roll">Roll</option>
                                            </select>
                                            <select v-else-if="item.is_pack" v-model="item.unit_mode" class="w-24 border-gray-200 rounded-lg text-sm py-1.5 focus:ring-blue-500">
                                                <option value="pcs">Pcs</option><option value="bungkus">Bungkus</option>
                                            </select>
                                            <select v-else-if="item.is_isolasi" v-model="item.unit_mode" class="w-24 border-gray-200 rounded-lg text-sm py-1.5 focus:ring-blue-500">
                                                <option value="cm">Cm</option><option value="pack">Pack</option>
                                            </select>
                                            <input v-else type="text" v-model="item.unit_manual" readonly class="w-24 border-gray-200 rounded-lg text-sm py-1.5 bg-gray-100 text-gray-500">
                                            
                                            <button type="button" @click="orderForm.items.splice(idx, 1)" class="p-1.5 text-gray-400 hover:text-red-500 bg-white rounded-lg border border-gray-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Catatan</label>
                                <textarea v-model="orderForm.notes" rows="3" class="w-full border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl border-t border-gray-100">
                            <button type="button" @click="closeModal" class="px-5 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50">Batal</button>
                            <button type="submit" :disabled="orderForm.processing || orderForm.items.length === 0" class="px-5 py-2 text-sm font-bold text-white bg-[#1e40af] hover:bg-blue-800 rounded-xl disabled:opacity-50 transition-colors">
                                {{ orderForm.processing ? 'Menyimpan...' : 'Kirim Order' }}
                            </button>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

const authUser = usePage().props.auth.user;

const props = defineProps({
    transactions: Object,
    summary: {
        type: Object,
        default: () => ({ total_transactions: 0, total_cost: 0, total_items: 0 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', start_date: '', end_date: '', technician: '', area_id: '' })
    },
    technicians: {
        type: Array,
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
    materials: {
        type: Array,
        default: () => []
    },
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const filterForm = ref({
    search: props.filters?.search || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    technician: props.filters?.technician || '',
    area_id: props.filters?.area_id || '',
});

let timeout = null;
const applyFilters = () => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/material-transactions', {
            search: filterForm.value.search,
            start_date: filterForm.value.start_date,
            end_date: filterForm.value.end_date,
            technician: filterForm.value.technician,
            area_id: filterForm.value.area_id,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch(filterForm, () => {
    applyFilters();
}, { deep: true });

const resetFilters = () => {
    filterForm.value = {
        search: '',
        start_date: '',
        end_date: '',
        technician: '',
        area_id: '',
    };
};

const selectedItems = ref([]);

const isAllSelected = computed(() => {
    return props.transactions.data.length > 0 && selectedItems.value.length === props.transactions.data.length;
});

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedItems.value = props.transactions.data.map(item => item.id);
    } else {
        selectedItems.value = [];
    }
};

// Helpers for transaction types
const isReturn = (item) => {
    return item.purpose && item.purpose.toLowerCase().includes('pengembalian');
};
const isOrder = (item) => {
    return item.type === 'in' && !isReturn(item);
};
const isInstall = (item) => {
    return item.type === 'out' && !isReturn(item);
};

const getOrderDetailedUnit = (detail) => {
    let text = formatNumber(detail.quantity) + ' ' + (detail.unit || (detail.material ? detail.material.unit : ''));
    if (!detail.material) return text;
    
    let cat = (detail.material.category || '').toLowerCase();
    let unit = (detail.unit || '').toLowerCase();
    
    if (cat.includes('kabel') && (unit === 'roll' || unit === 'rol')) {
        let mpr = detail.material.meter_per_roll || 1000;
        let meters = detail.quantity * mpr;
        text += `<br><span class="text-blue-600 font-bold">(&asymp; ${formatNumber(meters)} meter)</span>`;
    }
    else if (cat.includes('klem') && (unit === 'pack' || unit === 'bungkus')) {
        let ppp = detail.material.pcs_per_pack || 1;
        let pcs = detail.quantity * ppp;
        text += `<br><span class="text-blue-600 font-bold">(&asymp; ${formatNumber(pcs)} pcs)</span>`;
    }
    else if (cat.includes('isolasi') && (unit === 'pcs')) {
        let cpp = detail.material.cm_per_pcs || 50;
        let cm = detail.quantity * cpp;
        text += `<br><span class="text-blue-600 font-bold">(&asymp; ${formatNumber(cm)} cm)</span>`;
    }
    return text;
};

const deleteItem = (item) => {
    if (confirm(`Apakah Anda yakin ingin menghapus transaksi ${item.transaction_number}? Stok barang akan dikembalikan ke gudang.`)) {
        router.post(`/material-transactions/${item.id}/delete`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                selectedItems.value = selectedItems.value.filter(id => id !== item.id);
            }
        });
    }
};

const deleteSelected = () => {
    if (confirm(`Apakah Anda yakin ingin menghapus ${selectedItems.value.length} transaksi yang dipilih? Stok barang akan dikembalikan ke gudang.`)) {
        router.post('/material-transactions/bulk-destroy', {
            ids: selectedItems.value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedItems.value = [];
            }
        });
    }
};

const approveItem = (item) => {
    if (confirm(`Setujui serah terima pengembalian material ${item.transaction_number}? Stok Area akan bertambah sesuai rincian.`)) {
        router.post(`/material-transactions/${item.id}/approve`, {}, {
            preserveScroll: true
        });
    }
};

const rejectItem = (item) => {
    if (confirm(`Tolak pengembalian material ${item.transaction_number}? Stok tidak akan bertambah.`)) {
        router.post(`/material-transactions/${item.id}/reject`, {}, {
            preserveScroll: true
        });
    }
};

// --- Modal Create Order ---
const isModalOpen = ref(false);
const tempMaterialId = ref('');

const getTodayDate = () => {
    const d = new Date();
    let month = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
};

const orderForm = useForm({
    date: getTodayDate(),
    technician_name: authUser?.name || 'Administrator',
    purpose: 'Order material dari cabang ke toko',
    area_id: '',
    notes: '',
    items: []
});

const openModal = () => {
    orderForm.reset();
    orderForm.date = getTodayDate();
    orderForm.technician_name = authUser?.name || 'Administrator';
    orderForm.purpose = 'Order material dari cabang ke toko';
    tempMaterialId.value = '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const onAreaChange = () => {
    // optional: clear cart if they change area to avoid confusion with max_stocks?
    // for now just keep items, global stock doesn't change per area on CREATE.
};

const addCartItem = () => {
    if (!tempMaterialId.value) return;
    
    // Check if already in cart
    if (orderForm.items.find(i => i.material_id === tempMaterialId.value)) {
        alert('Produk ini sudah ada di keranjang.');
        return;
    }
    
    const material = props.materials.find(m => m.id === tempMaterialId.value);
    if (material) {
        const isCable = material.category === 'Kabel' || material.category === 'Kabel Drop / Frecon' || material.name.toLowerCase().includes('kabel');
        const isPack = material.category === 'Paku Klem';
        const isIsolasi = material.category === 'Isolasi';
        
        orderForm.items.push({
            material_id: material.id,
            material_name: material.name,
            input_quantity: 1,
            unit_mode: isCable ? 'meter' : (isPack ? 'pcs' : (isIsolasi ? 'cm' : 'default')),
            unit_manual: material.unit || 'pcs',
            max_stock: material.stock,
            is_cable: isCable,
            is_pack: isPack,
            is_isolasi: isIsolasi,
            meter_per_roll: material.meter_per_roll || 1000,
            pcs_per_pack: material.pcs_per_pack || 1,
            cm_per_pcs: material.cm_per_pcs || 50
        });
        
        tempMaterialId.value = '';
    }
};

const submitOrder = () => {
    orderForm.transform((data) => ({
        ...data,
        items: data.items.map(item => ({
            material_id: item.material_id,
            quantity: item.input_quantity,
            unit: (item.is_cable || item.is_pack || item.is_isolasi) ? item.unit_mode : item.unit_manual
        }))
    })).post('/material-transactions', {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        }
    });
};

</script>
