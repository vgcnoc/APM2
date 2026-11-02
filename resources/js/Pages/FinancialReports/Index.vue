<template>
    <AppLayout title="Laporan Keuangan Keseluruhan" subtitle="Master Dashboard Keuangan">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-8">
            
            <!-- Filter Bulan & Tahun -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 bg-white p-4 rounded-3xl shadow-sm border border-gray-100 min-w-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900">Periode Laporan</h2>
                        <p class="text-xs text-gray-500">Pilih bulan dan tahun acuan pembukuan</p>
                    </div>
                </div>
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                    <button 
                        @click="setThisMonth" 
                        type="button"
                        class="px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                        title="Tampilkan bulan ini"
                    >
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Bulan Ini</span>
                    </button>
                    <select v-model="form.month" class="block flex-1 sm:flex-none sm:w-32 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-xs sm:text-sm font-medium focus:ring-blue-500 focus:border-blue-500">
                        <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                    </select>
                    <select v-model="form.year" class="block flex-1 sm:flex-none sm:w-28 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-xs sm:text-sm font-medium focus:ring-blue-500 focus:border-blue-500">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button @click="applyPeriodFilter" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all hover:shadow whitespace-nowrap shrink-0 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span>Filter</span>
                    </button>
                </div>
            </div>

            <!-- Grand Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Omzet -->
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-5 min-w-0 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-indigo-100 text-xs sm:text-sm font-semibold mb-1 uppercase tracking-wider">Total Pemasukan (Omzet)</p>
                    <h3 class="text-xl sm:text-2xl 2xl:text-3xl font-black mb-3 break-words leading-tight">Rp {{ formatRupiah(summary?.total_omzet || 0) }}</h3>
                    <div class="flex flex-col gap-1 text-xs text-indigo-50 font-medium">
                        <div class="flex flex-wrap justify-between gap-1 border-t border-indigo-400/30 pt-2">
                            <span>Pelanggan Reguler:</span>
                            <span>Rp {{ formatRupiah(summary?.omzet_customer || 0) }}</span>
                        </div>
                        <div class="flex flex-wrap justify-between gap-1">
                            <span>Reseller Voucher:</span>
                            <span>Rp {{ formatRupiah(summary?.omzet_reseller || 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pengeluaran -->
                <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-3xl p-5 min-w-0 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-rose-100 text-xs sm:text-sm font-semibold mb-1 uppercase tracking-wider">Total Pengeluaran Kas</p>
                    <h3 class="text-xl sm:text-2xl 2xl:text-3xl font-black mb-3 break-words leading-tight">Rp {{ formatRupiah(summary?.total_pengeluaran || 0) }}</h3>
                    <div class="flex justify-between gap-1 border-t border-rose-400/30 pt-2 mt-auto text-xs text-rose-50 font-medium">
                        <span>Lihat detail di Buku Kas Umum</span>
                        <span>↓</span>
                    </div>
                </div>

                <!-- Laba Bersih -->
                <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl p-5 min-w-0 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-20 rounded-full blur-xl animate-pulse"></div>
                    <p class="text-emerald-100 text-xs sm:text-sm font-semibold mb-1 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-200 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A12.014 12.014 0 0010.378 1l-.156.003a11.967 11.967 0 00-6.19 2.502L3.6 3.902a1 1 0 00-.472.934L3.6 15a1 1 0 00.563.896l5.378 2.534c.185.087.39.087.575 0l5.378-2.534A1 1 0 0016.4 15l.473-10.164a1 1 0 00-.473-.934l-.431-.397a11.96 11.96 0 00-4.669-2.459zm-1.896 4.398a1 1 0 00-1.229.588l-2 5a1 1 0 001.858.742L8.5 10.428l2.036-1.018a1 1 0 00.32-1.637l-1.452-2.324z" clip-rule="evenodd"></path></svg>
                        <span>Laba Bersih Perusahaan</span>
                    </p>
                    <h3 class="text-xl sm:text-2xl 2xl:text-3xl font-black mb-3 break-words leading-tight">Rp {{ formatRupiah(summary?.laba_bersih || 0) }}</h3>
                    <div class="flex justify-between border-t border-emerald-400/30 pt-2 text-xs text-emerald-50 font-medium">
                        <span>(Omzet - Pengeluaran)</span>
                    </div>
                </div>
                
                <!-- Piutang Keseluruhan -->
                <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-3xl p-5 min-w-0 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-amber-100 text-xs sm:text-sm font-semibold mb-1 uppercase tracking-wider">Total Piutang Berjalan</p>
                    <h3 class="text-xl sm:text-2xl 2xl:text-3xl font-black mb-3 break-words leading-tight">Rp {{ formatRupiah(summary?.total_piutang || 0) }}</h3>
                    <div class="flex flex-col gap-1 text-xs text-amber-50 font-medium">
                        <div class="flex flex-wrap justify-between gap-1 border-t border-amber-400/30 pt-2">
                            <span>Tunggakan Pelanggan Aktif:</span>
                            <span>Rp {{ formatRupiah(summary?.piutang_customer || 0) }}</span>
                        </div>
                        <div class="flex flex-wrap justify-between gap-1">
                            <span>Kasbon Reseller:</span>
                            <span>Rp {{ formatRupiah(summary?.piutang_reseller || 0) }}</span>
                        </div>
                        <div v-if="summary?.piutang_terminated > 0" class="flex flex-wrap justify-between gap-1 text-rose-200 font-semibold">
                            <span>Piutang Stop Permanen:</span>
                            <span>Rp {{ formatRupiah(summary?.piutang_terminated || 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 sm:gap-6">
                <!-- Grafik -->
                <div class="xl:col-span-2 bg-white p-4 sm:p-6 min-w-0 overflow-hidden rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100">
                    <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-4 sm:mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                        <span>Grafik Pendapatan & Pengeluaran (6 Bulan Terakhir)</span>
                    </h3>
                    <div class="h-72 sm:h-80 w-full relative z-10 min-w-0">
                        <apexchart type="area" height="100%" :options="chartOptions" :series="chartSeries"></apexchart>
                    </div>
                </div>

                <!-- KPI Penagih -->
                <div class="bg-white p-4 sm:p-6 min-w-0 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 flex flex-col">
                    <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                        <span>Papan Skor Penagih (Bulan Ini)</span>
                    </h3>
                    <div v-if="!kpi || kpi.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-400 py-8">
                        <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <p class="text-sm font-medium text-center">Belum ada setoran penagih bulan ini.</p>
                    </div>
                    <ul v-else class="space-y-3">
                        <li v-for="(person, idx) in kpi" :key="idx" class="flex flex-wrap items-center justify-between gap-2 p-3 rounded-2xl bg-gray-50 border border-gray-100 hover:border-amber-200 transition-colors min-w-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-xs sm:text-sm shadow-md shrink-0">
                                    {{ idx + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ person.name }}</p>
                                    <p class="text-xs text-gray-500">{{ person.count }} Transaksi</p>
                                </div>
                            </div>
                            <div class="text-right ml-auto">
                                <p class="text-sm font-black text-emerald-600 whitespace-nowrap">Rp {{ formatRupiah(person.total_collected) }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Buku Kas Umum (Unified Ledger) -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden min-w-0">
                <!-- Header Card -->
                <div class="p-4 sm:p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            <span>Buku Kas Umum (General Ledger)</span>
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Rekapitulasi riwayat mutasi kas keluar masuk secara real-time</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <Link :href="route('expense-categories.index')" class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-1.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3.5 py-2 rounded-xl shadow-sm text-xs font-bold transition-all whitespace-nowrap">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            Master Kategori
                        </Link>
                        <button @click="showExpenseModal = true" class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl shadow-sm transition-all hover:shadow text-xs font-bold whitespace-nowrap cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Catat Pengeluaran
                        </button>
                    </div>
                </div>

                <!-- FILTER LENGKAP TOOLBAR -->
                <div class="p-4 sm:p-5 border-b border-gray-100 bg-white space-y-4">
                    <!-- Filter Controls Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                        
                        <!-- Search Box (4 Cols) -->
                        <div class="sm:col-span-2 lg:col-span-4">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                Pencarian Transaksi
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input 
                                    type="text" 
                                    v-model="ledgerFilter.search" 
                                    @keyup.enter="applyLedgerFilter(true)"
                                    placeholder="Keterangan, nama pelanggan, admin, No PO..." 
                                    class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm border-gray-200 bg-gray-50 focus:bg-white rounded-xl focus:ring-blue-500 focus:border-blue-500 font-medium placeholder-gray-400"
                                />
                                <button 
                                    v-if="ledgerFilter.search" 
                                    @click="ledgerFilter.search = ''; applyLedgerFilter(true)" 
                                    type="button" 
                                    class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Tipe Mutasi (2 Cols) -->
                        <div class="lg:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                Tipe Mutasi
                            </label>
                            <select 
                                v-model="ledgerFilter.type" 
                                @change="applyLedgerFilter(true)"
                                class="w-full py-2 px-3 text-xs sm:text-sm border-gray-200 bg-gray-50 focus:bg-white rounded-xl focus:ring-blue-500 focus:border-blue-500 font-medium"
                            >
                                <option value="all">Semua Tipe Mutasi</option>
                                <option value="income">🟢 Pemasukan Kas (+)</option>
                                <option value="expense">🔴 Pengeluaran Kas (-)</option>
                            </select>
                        </div>

                        <!-- Kategori (2 Cols) -->
                        <div class="lg:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                Kategori
                            </label>
                            <select 
                                v-model="ledgerFilter.category" 
                                @change="applyLedgerFilter(true)"
                                class="w-full py-2 px-3 text-xs sm:text-sm border-gray-200 bg-gray-50 focus:bg-white rounded-xl focus:ring-blue-500 focus:border-blue-500 font-medium"
                            >
                                <option value="all">Semua Kategori</option>
                                <optgroup label="Sumber Pemasukan">
                                    <option value="customer">👤 Pelanggan Reguler</option>
                                    <option value="reseller">🏷️ Reseller Voucher</option>
                                </optgroup>
                                <optgroup label="Kategori Pengeluaran">
                                    <option value="material">📦 Belanja Material (Order Toko)</option>
                                    <option v-for="cat in expense_categories" :key="cat.id" :value="cat.id">
                                        {{ cat.code ? '[' + cat.code + '] ' : '' }}{{ cat.name }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Rentang Tanggal (2 Cols) -->
                        <div class="lg:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                Tanggal Spesifik
                            </label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input 
                                    type="date" 
                                    v-model="ledgerFilter.date_from" 
                                    title="Dari Tanggal"
                                    class="w-full p-1.5 text-[11px] border-gray-200 bg-gray-50 focus:bg-white rounded-xl focus:ring-blue-500 focus:border-blue-500 font-medium"
                                />
                                <input 
                                    type="date" 
                                    v-model="ledgerFilter.date_to" 
                                    title="Sampai Tanggal"
                                    class="w-full p-1.5 text-[11px] border-gray-200 bg-gray-50 focus:bg-white rounded-xl focus:ring-blue-500 focus:border-blue-500 font-medium"
                                />
                            </div>
                        </div>

                        <!-- Baris Per Halaman & Tombol Aksi (2 Cols) -->
                        <div class="lg:col-span-2 flex items-center gap-1.5">
                            <div class="w-24 shrink-0">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                    Paginasi
                                </label>
                                <select 
                                    v-model="ledgerFilter.per_page" 
                                    @change="applyLedgerFilter(true)"
                                    class="w-full py-2 px-2 text-xs border-gray-200 bg-gray-50 focus:bg-white rounded-xl focus:ring-blue-500 focus:border-blue-500 font-bold"
                                >
                                    <option :value="10">10 / hal</option>
                                    <option :value="25">25 / hal</option>
                                    <option :value="50">50 / hal</option>
                                    <option :value="100">100 / hal</option>
                                </select>
                            </div>
                            <div class="flex-1 flex items-center gap-1 pt-4">
                                <button 
                                    @click="applyLedgerFilter(true)" 
                                    type="button"
                                    class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-2 px-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-1 cursor-pointer"
                                    title="Terapkan Filter"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Terapkan</span>
                                </button>
                                <button 
                                    @click="resetLedgerFilter" 
                                    type="button" 
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-2 rounded-xl text-xs transition cursor-pointer"
                                    title="Reset Semua Filter"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Active Filter Chips & Intelligent Subtotals -->
                    <div class="pt-2 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                        
                        <!-- Left: Active Filter Chips -->
                        <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                            <span class="text-gray-400 font-semibold mr-1">Filter Aktif:</span>
                            
                            <span v-if="ledgerFilter.search" class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-blue-200">
                                <span>Cari: "{{ ledgerFilter.search }}"</span>
                                <button @click="ledgerFilter.search = ''; applyLedgerFilter(true)" class="text-blue-500 hover:text-blue-800 ml-0.5">&times;</button>
                            </span>

                            <span v-if="ledgerFilter.type && ledgerFilter.type !== 'all'" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-indigo-200">
                                <span>Tipe: {{ ledgerFilter.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</span>
                                <button @click="ledgerFilter.type = 'all'; applyLedgerFilter(true)" class="text-indigo-500 hover:text-indigo-800 ml-0.5">&times;</button>
                            </span>

                            <span v-if="ledgerFilter.category && ledgerFilter.category !== 'all'" class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-purple-200">
                                <span>Kategori: {{ getCategoryLabel(ledgerFilter.category) }}</span>
                                <button @click="ledgerFilter.category = 'all'; applyLedgerFilter(true)" class="text-purple-500 hover:text-purple-800 ml-0.5">&times;</button>
                            </span>

                            <span v-if="ledgerFilter.date_from || ledgerFilter.date_to" class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 px-2.5 py-1 rounded-lg text-xs font-bold border border-amber-200">
                                <span>Tgl: {{ ledgerFilter.date_from || '...' }} s/d {{ ledgerFilter.date_to || '...' }}</span>
                                <button @click="ledgerFilter.date_from = ''; ledgerFilter.date_to = ''; applyLedgerFilter(true)" class="text-amber-600 hover:text-amber-900 ml-0.5">&times;</button>
                            </span>

                            <span v-if="Number(ledgerFilter.per_page) !== 10" class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-2 py-1 rounded-lg text-xs font-medium border border-gray-200">
                                <span>{{ ledgerFilter.per_page }}/hal</span>
                                <button @click="ledgerFilter.per_page = 10; applyLedgerFilter(true)" class="text-gray-500 hover:text-gray-800 ml-0.5">&times;</button>
                            </span>

                            <button 
                                v-if="hasActiveFilters" 
                                @click="resetLedgerFilter" 
                                type="button" 
                                class="text-rose-600 hover:text-rose-800 font-bold underline text-xs ml-1 cursor-pointer"
                            >
                                Hapus Semua
                            </button>

                            <span v-if="!hasActiveFilters" class="text-gray-400 italic">
                                Menampilkan semua transaksi bulan {{ months[(form.month || 1) - 1] }} {{ form.year }} (Default 10 per hal)
                            </span>
                        </div>

                        <!-- Right: Subtotal Mutasi Terfilter -->
                        <div class="flex flex-wrap items-center gap-3 shrink-0 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200 font-mono text-[11px] sm:text-xs">
                            <span class="text-gray-500 font-sans font-medium">
                                Cocok: <strong class="text-gray-900 font-mono font-bold">{{ ledger_summary?.total_count ?? (ledgerPagination?.total ?? ledgerItems.length) }}</strong> data
                            </span>
                            <span class="text-emerald-700 font-bold">
                                +Rp {{ formatRupiah(ledger_summary?.total_income || 0) }}
                            </span>
                            <span class="text-rose-600 font-bold">
                                -Rp {{ formatRupiah(ledger_summary?.total_expense || 0) }}
                            </span>
                            <span class="font-bold border-l border-gray-300 pl-2 text-indigo-700">
                                Net: Rp {{ formatRupiah(ledger_summary?.net || 0) }}
                            </span>
                        </div>

                    </div>
                </div>
                
                <!-- Tabel Ledger -->
                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal &amp; Waktu</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe / Kategori</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Mutasi Nominal</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="item in ledgerItems" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500">
                                    {{ new Date(item.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="item.type === 'income'" class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-emerald-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                        Pemasukan ({{ item.category === 'reseller' ? 'Reseller' : 'Pelanggan' }})
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border" :class="item.category_code === 'MAT' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                        {{ item.category_name || 'Pengeluaran Kas' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-2 flex-wrap">
                                        <p class="text-sm text-gray-900 font-medium">{{ item.description }}</p>
                                        <button v-if="item.material_order" @click="openMaterialDetail(item.material_order)" type="button" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2 py-0.5 rounded-md transition-colors cursor-pointer">
                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            {{ item.material_order.items_count }} Item Material
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">Oleh: {{ item.user }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <p v-if="item.type === 'income'" class="text-sm font-black text-emerald-600">+ Rp {{ formatRupiah(item.amount) }}</p>
                                    <p v-else class="text-sm font-black text-rose-600">- Rp {{ formatRupiah(item.amount) }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button v-if="item.material_order" @click="openMaterialDetail(item.material_order)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-900 border border-indigo-200 rounded-xl transition-all shadow-xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                        Rincian
                                    </button>
                                    <button v-else-if="item.type === 'expense' && item.expense_id" @click="deleteExpense(item.expense_id, item.description)" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 hover:text-rose-900 border border-rose-200 rounded-xl transition-all shadow-xs cursor-pointer" title="Hapus Catatan Pengeluaran">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Hapus
                                    </button>
                                    <span v-else class="text-xs text-gray-300">-</span>
                                </td>
                            </tr>
                            <tr v-if="ledgerItems.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <svg class="w-12 h-12 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="font-bold text-gray-800 text-sm">Tidak ada transaksi yang cocok</p>
                                        <p class="text-xs text-gray-500">Coba ubah kata kunci pencarian, kategori, atau rentang tanggal pada filter di atas.</p>
                                        <button v-if="hasActiveFilters" @click="resetLedgerFilter" type="button" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-xl text-xs font-bold transition">
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINASI 10 FOOTER (Sesuai Permintaan User) -->
                <div v-if="ledgerPagination" class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                    <!-- Left Info -->
                    <div class="text-gray-500 font-medium text-center sm:text-left">
                        Menampilkan <strong class="text-gray-900 font-bold">{{ ledgerPagination.from || 0 }}</strong> - <strong class="text-gray-900 font-bold">{{ ledgerPagination.to || 0 }}</strong> dari <strong class="text-gray-900 font-bold">{{ ledgerPagination.total || 0 }}</strong> transaksi
                        <span class="text-gray-400 ml-1">({{ ledgerFilter.per_page }} per halaman)</span>
                    </div>

                    <!-- Right Navigation Buttons -->
                    <div v-if="ledgerPagination.links && ledgerPagination.links.length > 3" class="flex flex-wrap items-center justify-center gap-1">
                        <template v-for="(link, idx) in ledgerPagination.links" :key="idx">
                            <button
                                v-if="link.url"
                                @click="goToPage(link.url)"
                                v-html="link.label"
                                :class="[
                                    link.active 
                                        ? 'bg-blue-600 text-white font-black shadow-sm border border-blue-600' 
                                        : 'bg-white text-gray-700 hover:bg-gray-100 hover:text-gray-900 border border-gray-200',
                                    'px-3 py-1.5 rounded-xl text-xs font-medium transition cursor-pointer min-w-[32px] text-center'
                                ]"
                            ></button>
                            <span 
                                v-else 
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-xl text-xs font-medium text-gray-300 border border-gray-100 bg-white/50 cursor-not-allowed min-w-[32px] text-center"
                            ></span>
                        </template>
                    </div>
                </div>

            </div>

        </div>

        <!-- Modal Form Pengeluaran -->
        <div v-if="showExpenseModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showExpenseModal = false"></div>
            <div class="relative z-10 bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md w-full max-h-[90vh] flex flex-col border border-gray-100">
                <div class="bg-gray-50/50 px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <h3 class="text-base sm:text-lg leading-6 font-bold text-gray-900 flex items-center gap-2">
                        <span class="bg-rose-100 text-rose-600 p-1.5 rounded-lg shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </span>
                        <span>Catat Pengeluaran Baru</span>
                    </h3>
                    <button @click="showExpenseModal = false" class="text-gray-400 hover:text-gray-500 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto flex-1">
                    <form @submit.prevent="submitExpense" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                            <input type="date" v-model="expenseForm.expense_date" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm" required>
                            <p v-if="expenseForm.errors.expense_date" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.expense_date }}</p>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-1">
                                <label class="block text-sm font-medium text-gray-700">Kategori Pengeluaran</label>
                                <Link :href="route('expense-categories.index')" class="text-xs font-bold text-rose-600 hover:text-rose-800">
                                    + Kelola Kategori
                                </Link>
                            </div>
                            <select v-model="expenseForm.expense_category_id" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm font-medium">
                                <option value="">-- Tanpa Kategori (Umum) --</option>
                                <option v-for="cat in expense_categories" :key="cat.id" :value="cat.id">
                                    {{ cat.code ? '[' + cat.code + '] ' : '' }}{{ cat.name }}
                                </option>
                            </select>
                            <p v-if="expenseForm.errors.expense_category_id" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.expense_category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nominal (Rp) <span class="text-rose-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-gray-400">Rp</span>
                                <input type="number" v-model="expenseForm.amount" min="1" step="1" class="pl-10 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 font-bold text-lg" placeholder="Contoh: 150000" required>
                            </div>
                            <p v-if="expenseForm.errors.amount" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.amount }}</p>
                            <p v-if="expenseForm.amount && !isNaN(expenseForm.amount) && Number(expenseForm.amount) > 0" class="text-xs text-emerald-600 font-semibold mt-1">
                                Terbaca: Rp {{ formatRupiah(expenseForm.amount) }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keterangan / Tujuan Pengeluaran <span class="text-rose-500">*</span></label>
                            <textarea v-model="expenseForm.description" rows="2" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm" placeholder="Contoh: Beli kabel LAN, bensin teknisi, dll" required></textarea>
                            <p v-if="expenseForm.errors.description" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.description }}</p>
                        </div>
                        
                        <div class="pt-3 flex flex-wrap justify-end gap-2.5">
                            <button type="button" @click="showExpenseModal = false" class="flex-1 sm:flex-initial bg-white py-2.5 px-5 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="expenseForm.processing" class="flex-1 sm:flex-initial inline-flex justify-center items-center gap-1.5 py-2.5 px-5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-all disabled:opacity-50 whitespace-nowrap cursor-pointer">
                                <svg v-if="expenseForm.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ expenseForm.processing ? 'Menyimpan...' : 'Simpan Pengeluaran' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Detail Rincian Material Order -->
        <Teleport to="body">
            <div v-if="showMaterialDetailModal && selectedMaterialOrder" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showMaterialDetailModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-fade-in-up">
                    
                    <!-- Header Modal -->
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-indigo-50/70 via-blue-50/50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/20 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-gray-900">Rincian Material Order Toko</h3>
                                <p class="text-xs text-gray-500 font-mono">{{ selectedMaterialOrder.transaction_number }} &bull; Wilayah: <span class="font-bold text-indigo-700">{{ selectedMaterialOrder.area_name }}</span></p>
                            </div>
                        </div>
                        <button @click="showMaterialDetailModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-6 overflow-y-auto space-y-4 flex-1">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3 bg-gray-50 rounded-2xl border border-gray-100 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Tanggal Order</span>
                                <span class="font-bold text-gray-800">{{ selectedMaterialOrder.date }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Area / Wilayah</span>
                                <span class="font-bold text-indigo-700">{{ selectedMaterialOrder.area_name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Jenis Barang</span>
                                <span class="font-bold text-gray-800">{{ selectedMaterialOrder.items?.length || 0 }} Jenis</span>
                            </div>
                        </div>

                        <!-- Catatan jika ada -->
                        <div v-if="selectedMaterialOrder.notes" class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                            <span class="font-bold">Catatan:</span> {{ selectedMaterialOrder.notes }}
                        </div>

                        <!-- Tabel Rincian Barang -->
                        <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-2xs">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Barang / Kategori</th>
                                        <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">Jumlah</th>
                                        <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Harga Satuan</th>
                                        <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100 text-xs">
                                    <tr v-for="it in selectedMaterialOrder.items" :key="it.id" class="hover:bg-gray-50/50">
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-900">{{ it.material_name }}</div>
                                            <span class="inline-block text-[10px] font-semibold text-gray-500 bg-gray-100 px-1.5 py-0.2 rounded mt-0.5">{{ it.category }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold text-emerald-600">
                                            +{{ it.quantity }} {{ it.unit }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-600 font-mono">
                                            Rp {{ formatRupiah(it.price_per_unit) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-black text-gray-900 font-mono">
                                            Rp {{ formatRupiah(it.total_price) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!selectedMaterialOrder.items || selectedMaterialOrder.items.length === 0">
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-400 italic">
                                            Tidak ada rincian item material pada order ini.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50/70 border-t-2 border-gray-200">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-xs font-black text-gray-700 uppercase tracking-wider">Total Biaya Belanja:</td>
                                        <td class="px-4 py-3 text-right font-black text-rose-600 text-sm font-mono">
                                            Rp {{ formatRupiah(selectedMaterialOrder.total_cost) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                        <Link :href="route('order-toko.index')" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold underline flex items-center gap-1">
                            Buka di Menu Order Toko &rarr;
                        </Link>
                        <button @click="showMaterialDetailModal = false" class="px-5 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
import Swal from 'sweetalert2';

const props = defineProps({
    month: {
        type: Number,
        default: () => new Date().getMonth() + 1
    },
    year: {
        type: Number,
        default: () => new Date().getFullYear()
    },
    summary: {
        type: Object,
        default: () => ({})
    },
    chartData: {
        type: Array,
        default: () => []
    },
    ledger: {
        type: [Object, Array],
        default: () => []
    },
    ledger_summary: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    kpi: {
        type: Array,
        default: () => []
    },
    expense_categories: {
        type: Array,
        default: () => []
    }
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const currentYear = new Date().getFullYear();
const years = Array.from({length: 5}, (_, i) => currentYear - i);

// Top Period form
const form = useForm({
    month: props.month,
    year: props.year
});

// Buku Kas Ledger Filters
const ledgerFilter = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || 'all',
    category: props.filters?.category || 'all',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    per_page: Number(props.filters?.per_page) || 10,
});

// Computed ledger items
const ledgerItems = computed(() => {
    if (!props.ledger) return [];
    if (Array.isArray(props.ledger)) return props.ledger;
    return props.ledger.data || [];
});

const ledgerPagination = computed(() => {
    if (!props.ledger || Array.isArray(props.ledger)) return null;
    return props.ledger;
});

const hasActiveFilters = computed(() => {
    return Boolean(
        ledgerFilter.value.search ||
        (ledgerFilter.value.type && ledgerFilter.value.type !== 'all') ||
        (ledgerFilter.value.category && ledgerFilter.value.category !== 'all') ||
        ledgerFilter.value.date_from ||
        ledgerFilter.value.date_to ||
        Number(ledgerFilter.value.per_page) !== 10
    );
});

const getCategoryLabel = (val) => {
    if (val === 'customer') return 'Pelanggan Reguler';
    if (val === 'reseller') return 'Reseller Voucher';
    if (val === 'material') return 'Belanja Material';
    const found = props.expense_categories.find(c => String(c.id) === String(val) || String(c.code) === String(val));
    return found ? found.name : val;
};

// Actions to apply filter
const applyLedgerFilter = (resetPage = true) => {
    const params = {
        month: form.month,
        year: form.year,
        search: ledgerFilter.value.search || undefined,
        type: ledgerFilter.value.type !== 'all' ? ledgerFilter.value.type : undefined,
        category: ledgerFilter.value.category !== 'all' ? ledgerFilter.value.category : undefined,
        date_from: ledgerFilter.value.date_from || undefined,
        date_to: ledgerFilter.value.date_to || undefined,
        per_page: Number(ledgerFilter.value.per_page) !== 10 ? ledgerFilter.value.per_page : 10,
        page: resetPage ? 1 : (props.ledger?.current_page || 1)
    };

    // Remove undefined/empty keys
    Object.keys(params).forEach(key => (params[key] === undefined || params[key] === '') && delete params[key]);

    router.get(route('financial-reports.index'), params, {
        preserveState: true,
        preserveScroll: true
    });
};

const applyPeriodFilter = () => {
    // When changing month/year in top bar, clear date_from/date_to and reset to page 1
    ledgerFilter.value.date_from = '';
    ledgerFilter.value.date_to = '';
    applyLedgerFilter(true);
};

const setThisMonth = () => {
    const now = new Date();
    form.month = now.getMonth() + 1;
    form.year = now.getFullYear();
    ledgerFilter.value.date_from = '';
    ledgerFilter.value.date_to = '';
    applyLedgerFilter(true);
};

const resetLedgerFilter = () => {
    ledgerFilter.value = {
        search: '',
        type: 'all',
        category: 'all',
        date_from: '',
        date_to: '',
        per_page: 10,
    };
    router.get(route('financial-reports.index'), {
        month: form.month,
        year: form.year,
        per_page: 10,
        page: 1
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const goToPage = (url) => {
    if (!url) return;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true
    });
};

const formatRupiah = (number) => {
    if (number === null || number === undefined || isNaN(number)) return '0';
    return new Intl.NumberFormat('id-ID').format(number);
};

const getTodayDate = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const showExpenseModal = ref(false);
const expenseForm = useForm({
    expense_category_id: '',
    amount: '',
    description: '',
    expense_date: getTodayDate(),
});

// Modal Detail Material Order Toko
const selectedMaterialOrder = ref(null);
const showMaterialDetailModal = ref(false);

const openMaterialDetail = (order) => {
    selectedMaterialOrder.value = order;
    showMaterialDetailModal.value = true;
};

const submitExpense = () => {
    expenseForm.post(route('expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showExpenseModal.value = false;
            expenseForm.reset();
            expenseForm.expense_category_id = '';
            expenseForm.expense_date = getTodayDate();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dicatat!',
                text: 'Catatan pengeluaran berhasil disimpan ke buku kas.',
                timer: 1800,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] || 'Terjadi kesalahan saat menyimpan pengeluaran.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstError
            });
        }
    });
};

const deleteExpense = (id, desc = '') => {
    Swal.fire({
        title: 'Hapus Pengeluaran?',
        text: desc ? `Yakin ingin menghapus catatan "${desc}"? Buku kas dan laba bersih akan diperbarui secara otomatis.` : 'Apakah Anda yakin ingin menghapus catatan pengeluaran ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('expenses.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Catatan pengeluaran berhasil dihapus.',
                        timer: 1600,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    const errorMsg = Object.values(err)[0] || 'Gagal menghapus catatan pengeluaran.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: errorMsg
                    });
                }
            });
        }
    });
};

// Setup Chart
const apexchart = VueApexCharts;

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        fontFamily: 'inherit',
        toolbar: { show: false },
        zoom: { enabled: false },
        parentHeightOffset: 0
    },
    colors: ['#4f46e5', '#10b981', '#f43f5e'],
    dataLabels: { enabled: false },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    xaxis: {
        categories: props.chartData ? props.chartData.map(d => d.month) : [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#6b7280', fontSize: '12px', fontWeight: 500 }
        }
    },
    yaxis: {
        labels: {
            formatter: (val) => {
                if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' Jt';
                if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' Rb';
                return 'Rp ' + val;
            },
            style: { colors: '#6b7280', fontSize: '11px', fontWeight: 500 }
        }
    },
    grid: {
        borderColor: '#f3f4f6',
        strokeDashArray: 4,
        yaxis: { lines: { show: true } },
        xaxis: { lines: { show: false } },
        padding: { top: 0, right: 0, bottom: 0, left: 10 }
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
        offsetY: -20,
        markers: { radius: 12 },
        itemMargin: { horizontal: 10 }
    },
    tooltip: {
        y: { formatter: (val) => 'Rp ' + formatRupiah(val) }
    }
}));

const chartSeries = computed(() => [
    {
        name: 'Omzet Pelanggan',
        data: props.chartData ? props.chartData.map(d => d.customer) : []
    },
    {
        name: 'Omzet Reseller',
        data: props.chartData ? props.chartData.map(d => d.reseller) : []
    },
    {
        name: 'Pengeluaran',
        data: props.chartData ? props.chartData.map(d => d.expense) : []
    }
]);
</script>
