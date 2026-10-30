<template>
    <AppLayout title="Pelunasan Reseller" subtitle="Verifikasi penerimaan uang saldo/tagihan dari Reseller">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-12 font-sans">
            
            <!-- Alert Messages -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm flex items-center gap-2">
                <span>❌</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- ── 4 RINGKASAN STATISTIK CARDS ── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">
                
                <!-- Card 1: Menunggu Validasi (Klik untuk filter tab pending) -->
                <div 
                    @click="switchTab('pending')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'pending'
                            ? 'bg-amber-50/90 border-amber-300 ring-2 ring-amber-500 shadow-md'
                            : 'bg-white border-gray-200/80 hover:border-amber-200 hover:bg-amber-50/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                            <span class="truncate">Menunggu Validasi</span>
                        </span>
                        <span v-if="activeTab === 'pending'" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 border border-amber-300 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-amber-700 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.pending_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-amber-800 font-semibold">
                        <span>{{ stats?.pending_count || 0 }} Setoran Menunggu</span>
                        <span class="text-xs">&rarr;</span>
                    </div>
                </div>

                <!-- Card 2: Tervalidasi Hari Ini -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 min-w-0 border border-gray-200/80 shadow-xs hover:border-sky-200 hover:bg-sky-50/20 transition-all duration-200">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-sky-800 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
                            <span class="truncate">Tervalidasi Hari Ini</span>
                        </span>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 border border-sky-200 shrink-0">
                            Hari Ini
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-sky-700 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.today_verified_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-sky-800 font-semibold">
                        <span>{{ stats?.today_verified_count || 0 }} Setoran Masuk</span>
                        <span class="text-sky-500 text-xs">✓</span>
                    </div>
                </div>

                <!-- Card 3: Tervalidasi Bulan Ini -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 min-w-0 border border-gray-200/80 shadow-xs hover:border-indigo-200 hover:bg-indigo-50/20 transition-all duration-200">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-800 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shrink-0"></span>
                            <span class="truncate">Tervalidasi Bulan Ini</span>
                        </span>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200 shrink-0">
                            Bulan Ini
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-indigo-700 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.month_verified_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-indigo-800 font-semibold">
                        <span>{{ stats?.month_verified_count || 0 }} Setoran Masuk</span>
                        <span class="text-indigo-500 text-xs">📊</span>
                    </div>
                </div>

                <!-- Card 4: Total Tervalidasi (Klik untuk filter tab verified) -->
                <div 
                    @click="switchTab('verified')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'verified'
                            ? 'bg-emerald-50/90 border-emerald-300 ring-2 ring-emerald-500 shadow-md'
                            : 'bg-white border-gray-200/80 hover:border-emerald-200 hover:bg-emerald-50/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="truncate">Total Tervalidasi</span>
                        </span>
                        <span v-if="activeTab === 'verified'" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-200 text-emerald-900 border border-emerald-300 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-emerald-700 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.total_verified_amount || 0) }}
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 text-xs text-emerald-800 font-semibold">
                        <span>{{ stats?.total_verified_count || 0 }} Masuk Kas</span>
                        <span class="text-xs">&rarr;</span>
                    </div>
                </div>

            </div>

            <!-- ── FILTER BAR ── -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-4 sm:p-5 transition-all min-w-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 items-end">
                    
                    <!-- Search Input -->
                    <div class="xl:col-span-4 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Pencarian Data
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                v-model="filterForm.search" 
                                @input="onSearchInput"
                                @keyup.enter="applyFilters"
                                placeholder="Cari nama reseller, nomor invoice, catatan..." 
                                class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 pl-8 pr-3 py-2 transition-colors"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Petugas Penagih -->
                    <div class="xl:col-span-3 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Petugas Penagih
                        </label>
                        <select 
                            v-model="filterForm.collector_id" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Petugas Penagih</option>
                            <option v-for="c in collectors" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Metode Pembayaran -->
                    <div class="xl:col-span-3 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Metode Pembayaran
                        </label>
                        <select 
                            v-model="filterForm.payment_method" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Metode Pembayaran</option>
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                            <option value="loket">Loket Mitra</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="xl:col-span-2 flex items-center gap-2">
                        <button 
                            @click="applyFilters"
                            type="button" 
                            class="flex-1 inline-flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-3 rounded-xl transition shadow-xs cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter
                        </button>
                        <button 
                            v-if="hasActiveFilters"
                            @click="resetFilters" 
                            type="button" 
                            title="Reset Semua Filter"
                            class="inline-flex items-center justify-center bg-gray-100 hover:bg-rose-50 hover:text-rose-600 text-gray-600 text-xs font-bold py-2 px-3 rounded-xl transition border border-gray-200 cursor-pointer shrink-0"
                        >
                            Reset
                        </button>
                    </div>

                </div>

                <!-- Date Range Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 mt-3 pt-3 border-t border-gray-100 items-center">
                    <div class="xl:col-span-4 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Tanggal Terima Dari
                        </label>
                        <input 
                            type="date" 
                            v-model="filterForm.start_date" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                        />
                    </div>
                    <div class="xl:col-span-4 min-w-0">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Sampai Dengan Tanggal
                        </label>
                        <input 
                            type="date" 
                            v-model="filterForm.end_date" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                        />
                    </div>
                    <div class="sm:col-span-2 xl:col-span-4 flex items-center justify-start xl:justify-end text-xs text-gray-500 pt-1 xl:pt-4">
                        <span v-if="hasActiveFilters" class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 px-3 py-1 rounded-xl border border-amber-200 font-bold text-xs">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Filter sedang aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── TABEL DAFTAR SETORAN ── -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden min-w-0">
                <div class="p-4 sm:p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900">Validasi Setoran Reseller</h3>
                        <p class="text-xs sm:text-sm text-gray-500">Uang setoran saldo/tagihan reseller diverifikasi di sini.</p>
                    </div>
                    
                    <div class="flex bg-gray-200/70 p-1 rounded-xl w-full lg:w-auto max-w-full overflow-x-auto overscroll-x-contain gap-1">
                        <button 
                            type="button"
                            @click="switchTab('pending')" 
                            class="px-3.5 sm:px-4 py-2 text-xs font-bold rounded-lg transition-all whitespace-nowrap text-center flex-1 lg:flex-none flex items-center justify-center gap-2 cursor-pointer shrink-0"
                            :class="activeTab === 'pending' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'">
                            <span>Menunggu Validasi</span>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                :class="activeTab === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-gray-300/80 text-gray-700'"
                            >
                                {{ stats?.pending_count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('verified')" 
                            class="px-3.5 sm:px-4 py-2 text-xs font-bold rounded-lg transition-all whitespace-nowrap text-center flex-1 lg:flex-none flex items-center justify-center gap-2 cursor-pointer shrink-0"
                            :class="activeTab === 'verified' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'">
                            <span>Sudah Tervalidasi</span>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                :class="activeTab === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-300/80 text-gray-700'"
                            >
                                {{ stats?.total_verified_count || 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Terima</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reseller / Invoice</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penagih & Metode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rincian Pembayaran</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-gray-50/50 transition-colors">
                                <!-- Tanggal Terima -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="font-semibold text-gray-800">
                                        {{ new Date(pay.payment_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                    </div>
                                    <div v-if="pay.created_at" class="text-[11px] text-gray-400">
                                        {{ new Date(pay.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'}) }} WIB
                                    </div>
                                </td>

                                <!-- Reseller / Invoice -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ pay.customer?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                        <span v-if="pay.customer?.customer_code" class="text-indigo-600 font-mono font-medium">{{ pay.customer.customer_code }}</span>
                                        <span v-if="pay.customer?.phone" class="text-gray-400">({{ pay.customer.phone }})</span>
                                    </div>
                                    <div class="text-[11px] font-mono text-gray-500 mt-0.5">
                                        INV: <span class="font-semibold text-gray-700">{{ pay.invoice?.invoice_number }}</span>
                                    </div>
                                </td>

                                <!-- Penagih & Metode -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold inline-flex items-center justify-center">
                                            {{ (pay.collector?.name || 'P')[0].toUpperCase() }}
                                        </span>
                                        {{ pay.collector?.name || 'Tidak tercatat' }}
                                    </div>
                                    <div class="mt-1">
                                        <span 
                                            class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full capitalize"
                                            :class="getMethodBadgeClass(pay.payment_method)"
                                        >
                                            {{ pay.payment_method || 'Tunai' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Rincian Pembayaran -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <div class="text-[11px] text-gray-500 font-medium">
                                            Tagihan: Rp {{ formatRupiah(pay.invoice?.amount || 0) }}
                                        </div>
                                        <div class="text-sm font-black text-green-600 flex items-center gap-1">
                                            <span class="bg-green-100 text-green-700 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold">Setor</span>
                                            Rp {{ formatRupiah(pay.amount) }}
                                        </div>
                                        <div class="text-[11px] font-bold" :class="(pay.invoice?.remaining || 0) > 0 ? 'text-red-500' : 'text-gray-400'">
                                            Sisa Utang: Rp {{ formatRupiah(pay.invoice?.remaining || 0) }}
                                        </div>
                                        <div v-if="pay.notes" class="text-[11px] text-gray-500 italic mt-0.5 line-clamp-1 max-w-[220px]" :title="pay.notes">
                                            📝 {{ pay.notes }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div v-if="pay.status === 'pending'" class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="approve(pay.id)" 
                                            class="inline-flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white px-3.5 py-2 rounded-xl shadow-sm transition-all hover:shadow text-xs font-bold ring-1 ring-green-600/50 cursor-pointer"
                                        >
                                            ✓ Validasi
                                        </button>
                                        <button 
                                            @click="cancel(pay.id, pay.amount)" 
                                            class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 px-3.5 py-2 rounded-xl border border-rose-200 transition-all text-xs font-bold shadow-sm cursor-pointer"
                                        >
                                            ✕ Cancel
                                        </button>
                                    </div>
                                    <div v-else class="flex items-center justify-end gap-2">
                                        <span class="inline-flex items-center gap-1.5 text-green-700 font-bold text-xs bg-green-50 px-3 py-1.5 rounded-lg border border-green-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Tervalidasi
                                        </span>
                                        <button 
                                            @click="cancel(pay.id, pay.amount)" 
                                            title="Batalkan validasi setoran ini" 
                                            class="inline-flex items-center gap-1 bg-gray-50 hover:bg-rose-50 text-gray-500 hover:text-rose-600 px-2.5 py-1.5 rounded-lg border border-gray-200 hover:border-rose-200 text-xs font-semibold transition-all cursor-pointer"
                                        >
                                            ✕ Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!payments.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center text-2xl mb-3">
                                            🔍
                                        </div>
                                        <p class="font-bold text-gray-700 text-sm">Tidak ada data setoran yang cocok</p>
                                        <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian, rentang tanggal, atau petugas penagih pada filter.</p>
                                        <button 
                                            v-if="hasActiveFilters" 
                                            @click="resetFilters" 
                                            class="mt-3 text-xs text-indigo-600 font-bold hover:underline cursor-pointer"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="payments.links && payments.links.length > 3" class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500">
                        Menampilkan <span class="font-bold">{{ payments.from || 0 }}</span> - <span class="font-bold">{{ payments.to || 0 }}</span> dari <span class="font-bold">{{ payments.total || 0 }}</span> data setoran
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link 
                            v-for="(link, i) in payments.links" 
                            :key="i" 
                            :href="link.url || '#'" 
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs rounded-lg border transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold border-indigo-600 shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payments: Object,
    activeTab: String,
    stats: Object,
    filters: Object,
    collectors: Array
});

// Form Filter reaktif
const filterForm = reactive({
    search: props.filters?.search || '',
    collector_id: props.filters?.collector_id || '',
    payment_method: props.filters?.payment_method || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    tab: props.activeTab || 'pending',
});

// Deteksi apakah filter sedang aktif
const hasActiveFilters = computed(() => {
    return !!(
        filterForm.search ||
        filterForm.collector_id ||
        filterForm.payment_method ||
        filterForm.start_date ||
        filterForm.end_date
    );
});

// Debounce pencarian
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 450);
};

// Terapkan filter ke backend
const applyFilters = () => {
    router.get(route('reseller-settlements.index'), {
        search: filterForm.search || undefined,
        collector_id: filterForm.collector_id || undefined,
        payment_method: filterForm.payment_method || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
        tab: filterForm.tab || 'pending',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Beralih antar tab (Menunggu Validasi vs Sudah Tervalidasi)
const switchTab = (tabName) => {
    filterForm.tab = tabName;
    applyFilters();
};

// Reset filter
const resetFilters = () => {
    filterForm.search = '';
    filterForm.collector_id = '';
    filterForm.payment_method = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    applyFilters();
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

const getMethodBadgeClass = (method) => {
    const m = (method || '').toLowerCase();
    if (m === 'cash' || m === 'tunai') return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    if (m === 'transfer') return 'bg-blue-50 text-blue-700 border border-blue-200';
    if (m === 'qris') return 'bg-purple-50 text-purple-700 border border-purple-200';
    return 'bg-gray-100 text-gray-700 border border-gray-200';
};

const approve = (id) => {
    if (confirm('Uang sudah diterima? Memproses ini akan melunasi atau mengurangi tagihan invoice Reseller.')) {
        router.post(route('reseller-settlements.approve', id), {}, { preserveScroll: true });
    }
};

const cancel = (id, amount) => {
    if (confirm(`Yakin ingin membatalkan setoran ini (Rp ${formatRupiah(amount)})? Pembayaran akan dihapus dan tagihan kembali ke status semula.`)) {
        router.post(route('reseller-settlements.cancel', id), {}, { preserveScroll: true });
    }
};
</script>
