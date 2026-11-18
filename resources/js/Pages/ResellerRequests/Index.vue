<template>
    <AppLayout title="Riwayat &amp; Mutasi Saldo Reseller" subtitle="Pantau seluruh mutasi saldo masuk &amp; keluar (voucher, loket, kasbon) dan kelola permohonan isi saldo reseller.">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-12">
            
            <!-- Header Actions & Flash Messages -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-gray-100 dark:border-slate-700/80 shadow-[0_4px_20px_rgb(0,0,0,0.02)] min-w-0 transition-colors">
                <div class="min-w-0">
                    <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2 flex-wrap">
                        <span>💳</span>
                        <span>Riwayat Saldo &amp; Keuangan Reseller</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 mt-1">
                        Sistem pencatatan terintegrasi untuk saldo dompet reseller, mutasi transaksi pembelian voucher, loket biller, dan kasbon.
                    </p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto shrink-0">
                    <button 
                        @click="openAddModal()" 
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 sm:px-5 py-2.5 rounded-2xl font-bold text-xs sm:text-sm shadow-lg shadow-indigo-200 dark:shadow-indigo-950/50 transition-all cursor-pointer transform active:scale-95 whitespace-nowrap"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>+ Isi Saldo Manual</span>
                    </button>
                </div>
            </div>

            <!-- KPI Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">
                <!-- Total Saldo Beredar -->
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-lg shadow-indigo-100 dark:shadow-indigo-950/40 relative overflow-hidden min-w-0">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-100">Total Saldo Beredar</span>
                        <div class="p-2 bg-white/20 backdrop-blur-md rounded-xl text-white shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black font-mono break-words leading-tight">Rp {{ formatRupiah(stats.total_saldo_beredar) }}</div>
                    <p class="text-[11px] text-purple-100 mt-1.5">Saldo aktif di dompet {{ stats.total_resellers || resellers.length }} reseller</p>
                </div>

                <!-- Total Saldo Masuk (Kredit) -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-gray-100 dark:border-slate-700/80 shadow-[0_4px_20px_rgb(0,0,0,0.02)] min-w-0 transition-colors">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Saldo Masuk</span>
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl text-emerald-600 dark:text-emerald-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black font-mono text-gray-900 dark:text-white break-words leading-tight">+ Rp {{ formatRupiah(stats.total_credit) }}</div>
                    <p class="text-[11px] text-gray-400 dark:text-slate-400 mt-1.5 break-words">
                        <span v-if="stats.total_credit > 0">Top Up Kasbon, Tunai &amp; Payment Gateway</span>
                        <span v-else-if="stats.total_saldo_beredar > 0" class="text-indigo-600 dark:text-indigo-400 font-medium">Saldo aktif dompet: Rp {{ formatRupiah(stats.total_saldo_beredar) }}</span>
                        <span v-else>Belum ada mutasi saldo masuk</span>
                    </p>
                </div>

                <!-- Total Saldo Terpakai (Debit) -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-gray-100 dark:border-slate-700/80 shadow-[0_4px_20px_rgb(0,0,0,0.02)] min-w-0 transition-colors">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Saldo Terpakai</span>
                        <div class="p-2 bg-rose-50 dark:bg-rose-950/50 rounded-xl text-rose-600 dark:text-rose-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black font-mono text-gray-900 dark:text-white break-words leading-tight">- Rp {{ formatRupiah(stats.total_debit) }}</div>
                    <p class="text-[11px] text-gray-400 dark:text-slate-400 mt-1.5">
                        <span v-if="stats.total_debit > 0">Pembelian Voucher &amp; Bayar Tagihan Loket</span>
                        <span v-else>Belum ada pemakaian saldo voucher/loket</span>
                    </p>
                </div>

                <!-- Permintaan Pending -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-gray-100 dark:border-slate-700/80 shadow-[0_4px_20px_rgb(0,0,0,0.02)] min-w-0 transition-colors">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Permintaan Pending</span>
                        <div class="p-2 bg-amber-50 dark:bg-amber-950/50 rounded-xl text-amber-600 dark:text-amber-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <div class="text-lg sm:text-xl 2xl:text-2xl font-black font-mono text-gray-900 dark:text-white">{{ stats.pending_requests_count }}</div>
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400">Pengajuan</span>
                    </div>
                    <p class="text-[11px] mt-1.5 break-words" :class="stats.pending_requests_count > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-gray-400 dark:text-slate-400'">
                        <span v-if="stats.pending_requests_count > 0">
                            Rp {{ formatRupiah(stats.pending_requests_amount) }} menunggu persetujuan
                        </span>
                        <span v-else-if="stats.total_requests_count > 0">
                            Semua ditindaklanjuti ({{ stats.total_requests_count }} riwayat)
                        </span>
                        <span v-else>
                            Belum ada pengajuan isi saldo
                        </span>
                    </p>
                </div>
            </div>

            <!-- Tab Navigation & Filters Container -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.02)] border border-gray-100 dark:border-slate-700/80 overflow-hidden min-w-0 transition-colors">
                
                <!-- Tab Headers -->
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-700/80 px-3 sm:px-6 pt-3 sm:pt-4 bg-gray-50/60 dark:bg-slate-850 flex-wrap gap-2 sm:gap-4 min-w-0">
                    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto max-w-full overscroll-x-contain">
                        <button 
                            @click="switchTab('transactions')" 
                            class="px-3.5 sm:px-5 py-2.5 sm:py-3 rounded-t-2xl font-bold text-xs sm:text-sm transition-all relative flex items-center gap-1.5 sm:gap-2 cursor-pointer whitespace-nowrap shrink-0"
                            :class="activeTab === 'transactions' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 border-t-2 border-indigo-600 dark:border-indigo-400 shadow-xs' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100/70 dark:hover:bg-slate-700/50'"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>Mutasi Saldo (Kredit &amp; Debit)</span>
                            <span class="text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-black font-mono" :class="activeTab === 'transactions' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300' : 'bg-gray-200/70 dark:bg-slate-700 text-gray-600 dark:text-slate-300'">
                                {{ transactions.total || 0 }} Mutasi
                            </span>
                        </button>
                        <button 
                            @click="switchTab('requests')" 
                            class="px-3.5 sm:px-5 py-2.5 sm:py-3 rounded-t-2xl font-bold text-xs sm:text-sm transition-all relative flex items-center gap-1.5 sm:gap-2 cursor-pointer whitespace-nowrap shrink-0"
                            :class="activeTab === 'requests' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 border-t-2 border-indigo-600 dark:border-indigo-400 shadow-xs' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100/70 dark:hover:bg-slate-700/50'"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Permintaan &amp; Top Up Saldo</span>
                            <span v-if="stats.pending_requests_count > 0" class="text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-black font-mono bg-amber-500 text-white animate-pulse">
                                {{ stats.pending_requests_count }} Pending
                            </span>
                            <span v-else class="text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-black font-mono" :class="activeTab === 'requests' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300' : 'bg-gray-200/70 dark:bg-slate-700 text-gray-600 dark:text-slate-300'">
                                {{ requests.total || 0 }} Riwayat
                            </span>
                        </button>
                    </div>

                    <!-- Reset Filter Button -->
                    <div v-if="hasActiveFilters" class="pb-2">
                        <button 
                            @click="resetFilters" 
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 px-3 py-1.5 rounded-xl transition cursor-pointer whitespace-nowrap"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Reset Filter</span>
                        </button>
                    </div>
                </div>

                <!-- Filter Controls Toolbar -->
                <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-slate-700/80 bg-white dark:bg-slate-800 transition-colors">
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3">
                        <!-- Search Box -->
                        <div class="sm:col-span-2 xl:col-span-3 relative min-w-0">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input 
                                v-model="filterForm.search" 
                                @input="debouncedSearch"
                                type="text" 
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50/70 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm text-gray-900 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" 
                                placeholder="Cari keterangan, kode ref, nama..."
                            />
                        </div>

                        <!-- Reseller Dropdown Filter -->
                        <div class="xl:col-span-3 min-w-0">
                            <select 
                                v-model="filterForm.reseller_id" 
                                @change="applyFilters"
                                class="w-full px-3 py-2.5 bg-gray-50/70 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                            >
                                <option value="">Semua Reseller</option>
                                <option v-for="res in resellers" :key="res.id" :value="res.id">
                                    {{ res.name }} (Saldo: Rp {{ formatRupiah(res.balance) }})
                                </option>
                            </select>
                        </div>

                        <!-- Type / Status Filter depending on active tab -->
                        <div v-if="activeTab === 'transactions'" class="xl:col-span-2 min-w-0">
                            <select 
                                v-model="filterForm.type" 
                                @change="applyFilters"
                                class="w-full px-3 py-2.5 bg-gray-50/70 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                            >
                                <option value="">Semua Tipe Mutasi</option>
                                <option value="credit">➕ Saldo Masuk (Kredit)</option>
                                <option value="debit">➖ Saldo Keluar (Debit)</option>
                            </select>
                        </div>
                        <div v-else class="xl:col-span-2 min-w-0">
                            <select 
                                v-model="filterForm.status" 
                                @change="applyFilters"
                                class="w-full px-3 py-2.5 bg-gray-50/70 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700 rounded-xl text-sm text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                            >
                                <option value="">Semua Status</option>
                                <option value="pending">⏳ Menunggu (Pending)</option>
                                <option value="approved">✅ Disetujui (Approved)</option>
                                <option value="rejected">❌ Ditolak (Rejected)</option>
                            </select>
                        </div>

                        <!-- Date Range Filters -->
                        <div class="sm:col-span-2 xl:col-span-4 grid grid-cols-1 xs:grid-cols-2 sm:flex sm:items-center gap-2 min-w-0">
                            <input 
                                v-model="filterForm.start_date" 
                                @change="applyFilters"
                                type="date" 
                                class="w-full sm:flex-1 min-w-0 px-2.5 py-2 bg-gray-50/70 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                title="Dari Tanggal"
                            />
                            <span class="hidden sm:inline text-gray-400 dark:text-slate-500 text-xs shrink-0">-</span>
                            <input 
                                v-model="filterForm.end_date" 
                                @change="applyFilters"
                                type="date" 
                                class="w-full sm:flex-1 min-w-0 px-2.5 py-2 bg-gray-50/70 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                title="Sampai Tanggal"
                            />
                        </div>
                    </div>
                </div>

                <!-- TAB 1: Mutasi Transaksi Saldo (Kredit & Debit) -->
                <div v-if="activeTab === 'transactions'">
                    <div class="overflow-x-auto w-full overscroll-x-contain">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50/80 dark:bg-slate-900/60">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Waktu &amp; Tanggal</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Reseller</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Tipe</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Nominal</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Keterangan &amp; Referensi</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-100 dark:divide-slate-700/60">
                                <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-indigo-50/30 dark:hover:bg-slate-700/40 transition-colors">
                                    <!-- Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-slate-300">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ formatDate(tx.created_at) }}</div>
                                        <div class="text-[11px] text-gray-400 dark:text-slate-500 font-mono mt-0.5">{{ formatTime(tx.created_at) }} WIB</div>
                                    </td>

                                    <!-- Reseller -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ getResellerName(tx.reseller) }}</span>
                                            <span v-if="tx.reseller?.customer" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60">
                                                Pelanggan
                                            </span>
                                            <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60">
                                                Mitra User
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                            <span>{{ tx.reseller?.customer?.phone || tx.reseller?.user?.phone || '-' }}</span>
                                            <a 
                                                v-if="tx.reseller?.customer?.phone || tx.reseller?.user?.phone" 
                                                :href="`https://wa.me/${formatWa(tx.reseller?.customer?.phone || tx.reseller?.user?.phone)}`" 
                                                target="_blank" 
                                                class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-0.5"
                                            >
                                                <span>💬 WA</span>
                                            </a>
                                        </div>
                                    </td>

                                    <!-- Tipe Mutasi -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            v-if="tx.type === 'credit'" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/60"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"/></svg>
                                            + KREDIT (Masuk)
                                        </span>
                                        <span 
                                            v-else 
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200/70 dark:border-rose-800/60"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                            - DEBIT (Keluar)
                                        </span>
                                    </td>

                                    <!-- Nominal -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div 
                                            class="text-sm font-black font-mono"
                                            :class="tx.type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                        >
                                            {{ tx.type === 'credit' ? '+' : '-' }} Rp {{ formatRupiah(tx.amount) }}
                                        </div>
                                    </td>

                                    <!-- Keterangan & Referensi -->
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-800 dark:text-slate-200 font-medium break-words max-w-md">
                                            {{ tx.description }}
                                        </div>
                                        <div v-if="tx.reference_id" class="mt-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-600">
                                                Ref: {{ tx.reference_id }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                @click="openAddModal(tx.reseller_id)" 
                                                class="inline-flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 px-2.5 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-800 transition font-bold cursor-pointer shadow-xs active:scale-95"
                                                title="Isi saldo manual untuk reseller ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                <span>+ Isi Saldo</span>
                                            </button>
                                            <button 
                                                @click="openCorrectionForTx(tx)" 
                                                class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 transition font-bold cursor-pointer"
                                                title="Koreksi nominal transaksi jika salah input"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Koreksi</span>
                                            </button>
                                            <button 
                                                @click="confirmDeleteTx(tx)" 
                                                class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/50 px-2.5 py-1.5 rounded-lg border border-rose-200 dark:border-rose-800 transition font-bold cursor-pointer"
                                                title="Hapus riwayat mutasi ini dan sesuaikan saldo reseller"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!transactions.data || !transactions.data.length">
                                    <td colspan="6" class="px-6 py-16 text-center text-gray-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-14 h-14 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-gray-400 dark:text-slate-500 mb-3">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            </div>
                                            <p class="font-bold text-gray-800 dark:text-slate-200 text-sm">Belum Ada Riwayat Mutasi Saldo</p>
                                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1 max-w-sm">
                                                Mutasi akan otomatis tercatat setiap kali reseller membeli voucher, membayar di loket biller, atau mendapatkan isi saldo manual.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.links && transactions.links.length > 3" class="px-6 py-4 bg-gray-50/70 dark:bg-slate-800 border-t border-gray-100 dark:border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-gray-500 dark:text-slate-400">
                            Menampilkan <span class="font-bold text-gray-800 dark:text-white">{{ transactions.from || 0 }}</span> - <span class="font-bold text-gray-800 dark:text-white">{{ transactions.to || 0 }}</span> dari <span class="font-bold text-gray-800 dark:text-white">{{ transactions.total || 0 }}</span> mutasi
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, p) in transactions.links" :key="p">
                                <div v-if="link.url === null" class="px-3 py-1 text-xs text-gray-400 dark:text-slate-500 border border-gray-200 dark:border-slate-700 rounded-lg bg-gray-50 dark:bg-slate-800 select-none" v-html="link.label" />
                                <Link 
                                    v-else 
                                    :href="link.url" 
                                    preserve-scroll 
                                    preserve-state
                                    class="px-3 py-1 text-xs border rounded-lg transition cursor-pointer"
                                    :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white font-bold shadow-xs' : 'bg-white dark:bg-slate-700 border-gray-200 dark:border-slate-600 text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-600'"
                                    v-html="link.label" 
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Permintaan & Top Up Saldo (Kasbon & Transfer) -->
                <div v-else-if="activeTab === 'requests'">
                    <div class="overflow-x-auto w-full overscroll-x-contain">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50/80 dark:bg-slate-900/60">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Tanggal Pengajuan</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Reseller</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Nominal</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Metode &amp; Catatan</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Disetujui Oleh</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-100 dark:divide-slate-700/60">
                                <tr v-for="req in requests.data" :key="req.id" class="hover:bg-indigo-50/30 dark:hover:bg-slate-700/40 transition-colors">
                                    <!-- Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-slate-300">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ formatDate(req.created_at) }}</div>
                                        <div class="text-[11px] text-gray-400 dark:text-slate-500 font-mono mt-0.5">{{ formatTime(req.created_at) }} WIB</div>
                                    </td>

                                    <!-- Reseller -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ getResellerName(req.reseller) }}</span>
                                            <span v-if="req.reseller?.customer" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60">
                                                Pelanggan
                                            </span>
                                            <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60">
                                                Mitra User
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                            <span>{{ req.reseller?.customer?.phone || req.reseller?.user?.phone || '-' }}</span>
                                            <a 
                                                v-if="req.reseller?.customer?.phone || req.reseller?.user?.phone" 
                                                :href="`https://wa.me/${formatWa(req.reseller?.customer?.phone || req.reseller?.user?.phone)}`" 
                                                target="_blank" 
                                                class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-0.5"
                                            >
                                                <span>💬 WA</span>
                                            </a>
                                        </div>
                                    </td>

                                    <!-- Nominal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-black font-mono text-indigo-600 dark:text-indigo-400">
                                        Rp {{ formatRupiah(req.amount) }}
                                    </td>

                                    <!-- Metode & Catatan -->
                                    <td class="px-6 py-4">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase mb-1"
                                            :class="{
                                                'bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800': req.payment_method === 'kasbon',
                                                'bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800': req.payment_method === 'transfer',
                                                'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800': req.payment_method === 'cash',
                                            }"
                                        >
                                            {{ req.payment_method === 'kasbon' ? 'Kasbon Saldo' : (req.payment_method === 'transfer' ? 'Transfer Bank' : 'Tunai / Cash') }}
                                        </span>
                                        <div class="text-xs text-gray-500 dark:text-slate-400 italic break-words line-clamp-2 max-w-xs">
                                            {{ req.notes || 'Tidak ada catatan' }}
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="req.status === 'pending'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 animate-pulse">
                                            ⏳ Menunggu
                                        </span>
                                        <span v-else-if="req.status === 'approved'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            ✅ Disetujui
                                        </span>
                                        <span v-else-if="req.status === 'paid'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-50 dark:bg-green-950/60 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800">
                                            🟢 Lunas
                                        </span>
                                        <span v-else-if="req.status === 'rejected'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            ❌ Ditolak
                                        </span>
                                    </td>

                                    <!-- Approver -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-slate-400">
                                        <div v-if="req.approver" class="font-bold text-gray-800 dark:text-slate-200">{{ req.approver.name }}</div>
                                        <div v-else class="text-gray-400 dark:text-slate-500 italic">-</div>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                        <!-- Status Pending -->
                                        <div v-if="req.status === 'pending'" class="flex justify-end items-center gap-1.5">
                                            <button 
                                                @click="confirmApprove(req)" 
                                                class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1.5 rounded-lg shadow-xs transition text-xs font-bold cursor-pointer"
                                                title="Setujui permohonan saldo"
                                            >
                                                ✓ Setujui
                                            </button>
                                            <button 
                                                @click="openCorrectionForReq(req)" 
                                                class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 transition text-xs font-bold cursor-pointer"
                                                title="Koreksi nominal sebelum disetujui"
                                            >
                                                ✏️ Koreksi
                                            </button>
                                            <button 
                                                @click="confirmReject(req)" 
                                                class="inline-flex items-center gap-1 bg-rose-600 hover:bg-rose-700 text-white px-2.5 py-1.5 rounded-lg shadow-xs transition text-xs font-bold cursor-pointer"
                                                title="Tolak permohonan saldo"
                                            >
                                                ✕ Tolak
                                            </button>
                                        </div>

                                        <!-- Status Approved (Termasuk Isi Saldo Manual) -->
                                        <div v-else-if="req.status === 'approved'" class="flex justify-end items-center gap-1.5">
                                            <button 
                                                @click="openAddModal(req.reseller_id)" 
                                                class="inline-flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 px-2.5 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-800 transition font-bold cursor-pointer shadow-xs active:scale-95"
                                                title="Isi saldo manual lagi untuk reseller ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                <span>+ Isi Saldo</span>
                                            </button>
                                            <button 
                                                @click="openCorrectionForReq(req)" 
                                                class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 transition text-xs font-bold cursor-pointer"
                                                title="Koreksi nominal saldo yang sudah masuk"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Koreksi</span>
                                            </button>
                                            <button 
                                                @click="confirmRollbackReq(req)" 
                                                class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/50 px-2.5 py-1.5 rounded-lg border border-rose-200 dark:border-rose-800 transition text-xs font-bold cursor-pointer"
                                                title="Batalkan pengisian saldo dan tarik kembali saldonya"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Batalkan</span>
                                            </button>
                                        </div>

                                        <!-- Status Lainnya (Rejected / Dibatalkan) -->
                                        <div v-else class="flex justify-end items-center gap-1.5">
                                            <button 
                                                @click="openAddModal(req.reseller_id)" 
                                                class="inline-flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 px-2.5 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-800 transition font-bold cursor-pointer shadow-xs active:scale-95"
                                                title="Isi saldo manual untuk reseller ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                <span>+ Isi Saldo</span>
                                            </button>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300 font-semibold">
                                                Dibatalkan
                                            </span>
                                            <button 
                                                @click="confirmDeleteReq(req)" 
                                                class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/50 px-2 py-1 rounded-lg border border-rose-200 dark:border-rose-800 transition text-xs font-bold cursor-pointer"
                                                title="Hapus riwayat pengajuan ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!requests.data || !requests.data.length">
                                    <td colspan="7" class="px-6 py-16 text-center text-gray-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-14 h-14 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-gray-400 dark:text-slate-500 mb-3">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <p class="font-bold text-gray-800 dark:text-slate-200 text-sm">Tidak Ada Permintaan Saldo</p>
                                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1 max-w-sm">
                                                Gunakan tombol "+ Isi Saldo Manual" untuk menambahkan saldo reseller secara langsung.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="requests.links && requests.links.length > 3" class="px-6 py-4 bg-gray-50/70 dark:bg-slate-800 border-t border-gray-100 dark:border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-gray-500 dark:text-slate-400">
                            Menampilkan <span class="font-bold text-gray-800 dark:text-white">{{ requests.from || 0 }}</span> - <span class="font-bold text-gray-800 dark:text-white">{{ requests.to || 0 }}</span> dari <span class="font-bold text-gray-800 dark:text-white">{{ requests.total || 0 }}</span> permohonan
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, p) in requests.links" :key="p">
                                <div v-if="link.url === null" class="px-3 py-1 text-xs text-gray-400 dark:text-slate-500 border border-gray-200 dark:border-slate-700 rounded-lg bg-gray-50 dark:bg-slate-800 select-none" v-html="link.label" />
                                <Link 
                                    v-else 
                                    :href="link.url" 
                                    preserve-scroll 
                                    preserve-state
                                    class="px-3 py-1 text-xs border rounded-lg transition cursor-pointer"
                                    :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white font-bold shadow-xs' : 'bg-white dark:bg-slate-700 border-gray-200 dark:border-slate-600 text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-600'"
                                    v-html="link.label" 
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add Manual Topup Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm transition-opacity" @click="showAddModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg w-full max-h-[90vh] flex flex-col border border-gray-100 dark:border-slate-700">
                    
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-5 sm:px-6 py-4 sm:py-5 text-white flex items-center justify-between shrink-0">
                        <div class="min-w-0 pr-2">
                            <h3 class="text-base sm:text-lg font-black tracking-tight flex items-center gap-2" id="modal-title">
                                <span>💳</span>
                                <span>Isi Saldo Dompet Reseller</span>
                            </h3>
                            <p class="text-xs text-indigo-100 mt-0.5">
                                <span v-if="selectedReseller">Menambahkan saldo untuk <strong class="text-white">{{ selectedReseller.name }}</strong></span>
                                <span v-else>Tambahkan saldo manual via Kasbon, Transfer, atau Tunai.</span>
                            </p>
                        </div>
                        <button @click="showAddModal = false" class="text-white/80 hover:text-white p-1 rounded-xl hover:bg-white/10 transition shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitTopup" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Select Reseller -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">
                                    Pilih Reseller <span class="text-rose-500">*</span>
                                </label>
                                <span v-if="selectedReseller" class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="selectedReseller.has_customer ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800' : 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800'">
                                    {{ selectedReseller.has_customer ? 'Tipe: Pelanggan' : 'Tipe: Mitra User' }}
                                </span>
                            </div>
                            <select 
                                v-model="form.reseller_id" 
                                @change="onResellerChange"
                                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium transition"
                                required
                            >
                                <option value="">-- Pilih Reseller --</option>
                                <option v-for="res in resellers" :key="res.id" :value="res.id">
                                    {{ res.name }} &middot; Saldo Saat Ini: Rp {{ formatRupiah(res.balance) }}
                                </option>
                            </select>
                        </div>

                        <!-- Current Balance Preview if selected -->
                        <div v-if="selectedReseller" class="bg-indigo-50/70 dark:bg-slate-900/60 border border-indigo-100 dark:border-slate-700 rounded-2xl p-3.5 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Saldo Saat Ini</span>
                                <div class="text-base font-black font-mono text-indigo-950 dark:text-white">Rp {{ formatRupiah(selectedReseller.balance) }}</div>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Estimasi Saldo Baru</span>
                                <div class="text-base font-black font-mono text-emerald-600 dark:text-emerald-400">
                                    Rp {{ formatRupiah(Number(selectedReseller.balance || 0) + (Number(form.amount) || 0)) }}
                                </div>
                            </div>
                        </div>

                        <!-- Nominal Saldo -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Nominal Tambah Saldo (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                ref="amountInputRef"
                                type="number" 
                                v-model.number="form.amount" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-mono font-bold text-gray-900 dark:text-white" 
                                required 
                                min="1000"
                                step="1000"
                                placeholder="Contoh: 100000"
                            />
                            
                            <!-- Quick Chips Presets -->
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <button 
                                    type="button" 
                                    v-for="preset in [50000, 100000, 200000, 500000, 1000000]" 
                                    :key="preset"
                                    @click="form.amount = preset"
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg border transition cursor-pointer"
                                    :class="form.amount === preset ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-600'"
                                >
                                    Rp {{ formatRupiah(preset) }}
                                </button>
                                <button 
                                    v-if="form.amount" 
                                    type="button" 
                                    @click="form.amount = ''" 
                                    class="text-[10px] font-bold px-2 py-1 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-600 border border-gray-200 dark:border-slate-600 cursor-pointer ml-auto"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Metode Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <label 
                                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 transition text-center"
                                    :class="[
                                        form.payment_method === 'kasbon' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 font-black' : 'border-gray-200 dark:border-slate-700 hover:border-gray-300 text-gray-600 dark:text-slate-400',
                                        selectedReseller && !selectedReseller.has_customer ? 'opacity-40 cursor-not-allowed bg-gray-50 dark:bg-slate-900' : 'cursor-pointer'
                                    ]"
                                >
                                    <input 
                                        type="radio" 
                                        v-model="form.payment_method" 
                                        value="kasbon" 
                                        :disabled="selectedReseller && !selectedReseller.has_customer" 
                                        class="sr-only" 
                                    />
                                    <span class="text-base mb-1">📝</span>
                                    <span class="text-xs">Kasbon</span>
                                    <span class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5">Tagihan Besok</span>
                                </label>
                                <label 
                                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 cursor-pointer transition text-center"
                                    :class="form.payment_method === 'transfer' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 font-black' : 'border-gray-200 dark:border-slate-700 hover:border-gray-300 text-gray-600 dark:text-slate-400'"
                                >
                                    <input type="radio" v-model="form.payment_method" value="transfer" class="sr-only" />
                                    <span class="text-base mb-1">🏦</span>
                                    <span class="text-xs">Transfer</span>
                                    <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">Sudah Bayar</span>
                                </label>
                                <label 
                                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 cursor-pointer transition text-center"
                                    :class="form.payment_method === 'cash' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 font-black' : 'border-gray-200 dark:border-slate-700 hover:border-gray-300 text-gray-600 dark:text-slate-400'"
                                >
                                    <input type="radio" v-model="form.payment_method" value="cash" class="sr-only" />
                                    <span class="text-base mb-1">💵</span>
                                    <span class="text-xs">Tunai / Cash</span>
                                    <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">Sudah Bayar</span>
                                </label>
                            </div>
                            <p v-if="form.payment_method === 'kasbon'" class="text-[11px] text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/60 p-2.5 rounded-xl border border-purple-200/70 dark:border-purple-800 mt-2">
                                ℹ️ <strong>Mode Kasbon:</strong> Saldo langsung bertambah dan otomatis diterbitkan Invoice Penagihan Kasbon di menu <em>Penagihan Kasbon</em>.
                            </p>
                            <p v-else-if="selectedReseller && !selectedReseller.has_customer" class="text-[11px] text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 p-2 rounded-xl border border-amber-200/70 dark:border-amber-800 mt-2">
                                💡 <em>Reseller tipe Mitra User hanya dapat diisi via Transfer atau Tunai (Kasbon khusus akun reseller bertipe pelanggan).</em>
                            </p>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Catatan / Keterangan
                            </label>
                            <textarea 
                                v-model="form.notes" 
                                rows="2" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" 
                                placeholder="Contoh: Permintaan via WhatsApp, setor tunai kantor, dll"
                            ></textarea>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 flex flex-wrap items-center justify-end gap-2.5 sm:gap-3 border-t border-gray-100 dark:border-slate-700">
                            <button 
                                type="button" 
                                @click="showAddModal = false" 
                                class="flex-1 sm:flex-initial px-4 sm:px-5 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600 text-xs sm:text-sm font-bold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 sm:px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-indigo-200 dark:shadow-indigo-950/50 transition cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Simpan &amp; Isi Saldo</span>
                            </button>
                        </div>
                    </form>

            </div>
        </div>

        <!-- Correction Modal (Koreksi Nominal Saldo) -->
        <div v-if="showCorrectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto" aria-labelledby="modal-correction" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm transition-opacity" @click="showCorrectionModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full max-h-[90vh] flex flex-col border border-gray-100 dark:border-slate-700">
                    
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-5 sm:px-6 py-4 sm:py-5 text-white flex items-center justify-between shrink-0">
                        <div class="min-w-0 pr-2">
                            <h3 class="text-base sm:text-lg font-black tracking-tight" id="modal-correction">Koreksi Nominal Saldo</h3>
                            <p class="text-xs text-blue-100 mt-0.5">Ubah nominal jika salah input atau salah ketik sebelumnya.</p>
                        </div>
                        <button @click="showCorrectionModal = false" class="text-white/80 hover:text-white p-1 rounded-xl hover:bg-white/10 transition shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCorrection" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Info Reseller -->
                        <div class="bg-gray-50 dark:bg-slate-900/70 border border-gray-200/80 dark:border-slate-700 rounded-2xl p-3.5 space-y-1.5">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 dark:text-slate-400 font-semibold">Reseller:</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ correctionTarget?.resellerName }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 dark:text-slate-400 font-semibold">Tipe Transaksi:</span>
                                <span v-if="correctionTarget?.txType === 'debit'" class="font-bold text-rose-600 dark:text-rose-400">
                                    ➖ DEBIT (Saldo Keluar)
                                </span>
                                <span v-else class="font-bold text-emerald-600 dark:text-emerald-400">
                                    ➕ KREDIT (Saldo Masuk)
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 dark:text-slate-400 font-semibold">Saldo Dompet Saat Ini:</span>
                                <span class="font-bold font-mono text-indigo-700 dark:text-indigo-400">Rp {{ formatRupiah(correctionTarget?.currentBalance) }}</span>
                            </div>
                            <div v-if="correctionTarget?.referenceId" class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 dark:text-slate-400 font-semibold">Kode Referensi:</span>
                                <span class="font-mono text-[11px] text-gray-600 dark:text-slate-300 font-bold bg-white dark:bg-slate-800 px-2 py-0.5 rounded border border-gray-200 dark:border-slate-700">{{ correctionTarget?.referenceId }}</span>
                            </div>
                        </div>

                        <!-- Perbandingan Nominal -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-100 dark:bg-slate-900 rounded-2xl p-3 border border-gray-200 dark:border-slate-700">
                                <span class="text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Nominal Lama</span>
                                <div class="text-sm font-black font-mono text-gray-700 dark:text-slate-300 line-through">
                                    Rp {{ formatRupiah(correctionTarget?.oldAmount) }}
                                </div>
                            </div>
                            <div class="bg-indigo-50/80 dark:bg-indigo-950/40 rounded-2xl p-3 border border-indigo-200 dark:border-indigo-800">
                                <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block mb-1">Nominal Baru</span>
                                <div class="text-sm font-black font-mono text-indigo-700 dark:text-indigo-300">
                                    Rp {{ formatRupiah(correctionForm.amount) }}
                                </div>
                            </div>
                        </div>

                        <!-- Input Nominal Baru -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Nominal Seharusnya (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                v-model.number="correctionForm.amount" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono font-bold text-gray-900 dark:text-white" 
                                required 
                                min="1000"
                                step="1000"
                                placeholder="Masukkan nominal yang benar..."
                            />

                            <!-- Quick Amount Preset Chips -->
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <button 
                                    type="button" 
                                    v-for="preset in [10000, 20000, 50000, 100000, 200000, 500000, 1000000]" 
                                    :key="preset"
                                    @click="correctionForm.amount = preset"
                                    class="text-[10px] font-bold px-2 py-1 rounded-lg border transition cursor-pointer"
                                    :class="correctionForm.amount === preset ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-600'"
                                >
                                    {{ formatRupiah(preset) }}
                                </button>
                            </div>
                        </div>

                        <!-- Live Adjustment Summary Callout -->
                        <div 
                            class="rounded-2xl p-3.5 border transition-all"
                            :class="balanceImpact >= 0 ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200' : 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200'"
                        >
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span>Dampak Pada Saldo Reseller:</span>
                                <span class="font-mono text-sm font-black">
                                    {{ balanceImpact >= 0 ? '+' : '' }}Rp {{ formatRupiah(balanceImpact) }}
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-600 dark:text-slate-300">
                                Saldo reseller akan otomatis 
                                <strong :class="balanceImpact >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'">
                                    {{ balanceImpact >= 0 ? 'bertambah' : 'berkurang' }}
                                </strong> 
                                sebesar Rp {{ formatRupiah(Math.abs(balanceImpact)) }}, sehingga estimasi saldo akhirnya menjadi 
                                <strong class="font-mono text-gray-900 dark:text-white">Rp {{ formatRupiah(newEstimatedBalance) }}</strong>.
                            </div>
                            <div v-if="newEstimatedBalance < 0" class="text-rose-600 dark:text-rose-400 font-bold text-[11px] mt-2 flex items-center gap-1">
                                ⚠️ Saldo akhir tidak boleh minus! Reseller saat ini hanya memiliki saldo Rp {{ formatRupiah(correctionTarget?.currentBalance) }}.
                            </div>
                        </div>

                        <!-- Alasan Koreksi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Alasan / Catatan Koreksi
                            </label>
                            <input 
                                type="text" 
                                v-model="correctionForm.reason" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" 
                                placeholder="Contoh: Salah ketik kelebihan angka nol"
                            />
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 flex flex-wrap items-center justify-end gap-2.5 sm:gap-3 border-t border-gray-100 dark:border-slate-700">
                            <button 
                                type="button" 
                                @click="showCorrectionModal = false" 
                                class="flex-1 sm:flex-initial px-4 sm:px-5 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600 text-xs sm:text-sm font-bold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="correctionForm.processing || newEstimatedBalance < 0 || correctionForm.amount === correctionTarget?.oldAmount" 
                                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 sm:px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-indigo-200 dark:shadow-indigo-950/50 transition cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="correctionForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Simpan Koreksi</span>
                            </button>
                        </div>
                    </form>

            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const page = usePage();

const props = defineProps({
    transactions: {
        type: Object,
        default: () => ({ data: [] })
    },
    requests: {
        type: Object,
        default: () => ({ data: [] })
    },
    resellers: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_resellers: 0,
            total_saldo_beredar: 0,
            total_credit: 0,
            total_debit: 0,
            pending_requests_count: 0,
            pending_requests_amount: 0,
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// Active Tab ('transactions' or 'requests')
const activeTab = ref(props.filters.tab || 'transactions');

const switchTab = (tabName) => {
    activeTab.value = tabName;
    applyFilters();
};

// Filter Form State
const filterForm = ref({
    search: props.filters.search || '',
    reseller_id: props.filters.reseller_id || '',
    type: props.filters.type || '',
    status: props.filters.status || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const hasActiveFilters = computed(() => {
    return !!(
        filterForm.value.search || 
        filterForm.value.reseller_id || 
        filterForm.value.type || 
        filterForm.value.status || 
        filterForm.value.start_date || 
        filterForm.value.end_date
    );
});

// Debounced search
let searchTimer = null;
const debouncedSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
};

const applyFilters = () => {
    router.get(route('reseller-requests.index'), {
        tab: activeTab.value,
        search: filterForm.value.search || undefined,
        reseller_id: filterForm.value.reseller_id || undefined,
        type: activeTab.value === 'transactions' ? (filterForm.value.type || undefined) : undefined,
        status: activeTab.value === 'requests' ? (filterForm.value.status || undefined) : undefined,
        start_date: filterForm.value.start_date || undefined,
        end_date: filterForm.value.end_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const resetFilters = () => {
    filterForm.value = {
        search: '',
        reseller_id: '',
        type: '',
        status: '',
        start_date: '',
        end_date: '',
    };
    applyFilters();
};

// Modal & Add Form State
const showAddModal = ref(false);

const form = useForm({
    reseller_id: '',
    amount: '',
    payment_method: 'kasbon',
    notes: 'Permintaan manual via Admin'
});

const selectedReseller = computed(() => {
    if (!form.reseller_id) return null;
    return props.resellers.find(r => String(r.id) === String(form.reseller_id));
});

const amountInputRef = ref(null);

const onResellerChange = () => {
    if (selectedReseller.value && !selectedReseller.value.has_customer && form.payment_method === 'kasbon') {
        form.payment_method = 'transfer';
    }
};

const openAddModal = (resellerId = null) => {
    form.reset();
    form.payment_method = 'kasbon';
    form.notes = 'Permintaan manual via Admin';
    
    if (resellerId) {
        form.reseller_id = resellerId;
    } else if (props.resellers.length > 0 && !form.reseller_id) {
        form.reseller_id = props.resellers[0].id;
    }

    if (selectedReseller.value && !selectedReseller.value.has_customer) {
        form.payment_method = 'transfer';
    }

    showAddModal.value = true;
    setTimeout(() => {
        amountInputRef.value?.focus();
    }, 150);
};

const submitTopup = () => {
    if (!form.reseller_id) {
        Swal.fire('Perhatian', 'Silakan pilih reseller terlebih dahulu.', 'warning');
        return;
    }
    if (!form.amount || form.amount < 1000) {
        Swal.fire('Perhatian', 'Nominal saldo minimal Rp 1.000.', 'warning');
        return;
    }

    form.post(route('reseller-requests.store'), {
        preserveScroll: true,
        onSuccess: (res) => {
            if (res?.props?.flash?.error) {
                Swal.fire('Gagal Menambah Saldo', res.props.flash.error, 'error');
                return;
            }
            showAddModal.value = false;
            form.reset();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Saldo reseller berhasil ditambahkan.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire('Gagal Menambah Saldo', msg || 'Terjadi kesalahan sistem.', 'error');
        }
    });
};

// Approval & Reject Actions for Requests
const confirmApprove = (req) => {
    const resellerName = getResellerName(req.reseller);
    Swal.fire({
        title: 'Setujui Permintaan Saldo?',
        html: `Apakah Anda yakin ingin menyetujui penambahan saldo untuk <strong>${resellerName}</strong> sebesar <strong class="text-indigo-600">Rp ${formatRupiah(req.amount)}</strong>?<br><br>Saldo reseller akan langsung bertambah${req.payment_method === 'kasbon' ? ' dan invoice penagihan kasbon akan diterbitkan.' : '.'}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Setujui!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('reseller-requests.approve', req.id), {}, {
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res?.props?.flash?.error) {
                        Swal.fire('Gagal', res.props.flash.error, 'error');
                        return;
                    }
                    Swal.fire('Disetujui!', 'Permintaan saldo reseller telah disetujui.', 'success');
                },
                onError: (err) => {
                    Swal.fire('Gagal', err?.error || 'Gagal menyetujui permintaan.', 'error');
                }
            });
        }
    });
};

const confirmReject = (req) => {
    const resellerName = getResellerName(req.reseller);
    Swal.fire({
        title: 'Tolak Permintaan Saldo?',
        html: `Tolak permintaan saldo dari <strong>${resellerName}</strong> sebesar Rp ${formatRupiah(req.amount)}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Tolak',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('reseller-requests.reject', req.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Ditolak', 'Permintaan saldo telah ditolak.', 'info');
                }
            });
        }
    });
};

// Rollback / Batalkan Pengisian Saldo Request
const confirmRollbackReq = (req) => {
    const resellerName = getResellerName(req.reseller);
    Swal.fire({
        title: 'Batalkan Pengisian Saldo?',
        html: `Apakah Anda yakin ingin membatalkan pengisian saldo untuk <strong>${resellerName}</strong> sebesar <strong class="text-rose-600">Rp ${formatRupiah(req.amount)}</strong>?<br><br>Saldo dompet reseller akan ditarik kembali dan tagihan/invoice kasbon terkait akan dibatalkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Batalkan Saldo',
        cancelButtonText: 'Kembali'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('reseller-requests.destroy', req.id), {
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res?.props?.flash?.error) {
                        Swal.fire('Gagal', res.props.flash.error, 'error');
                        return;
                    }
                    Swal.fire('Dibatalkan!', 'Pengisian saldo berhasil dibatalkan dan saldo telah dikembalikan.', 'success');
                },
                onError: (err) => {
                    Swal.fire('Gagal', err?.error || 'Gagal membatalkan pengisian saldo.', 'error');
                }
            });
        }
    });
};

// Hapus / Batalkan Mutasi Transaksi
const confirmDeleteTx = (tx) => {
    const resellerName = getResellerName(tx.reseller);
    const isCredit = tx.type === 'credit';
    const amountStr = formatRupiah(tx.amount);
    
    Swal.fire({
        title: 'Hapus Transaksi Mutasi?',
        html: `
            <div class="text-left text-xs text-gray-600 dark:text-gray-300 space-y-3 mt-2">
                <p>Apakah Anda yakin ingin menghapus data riwayat mutasi untuk <strong>${resellerName}</strong>?</p>
                <div class="bg-gray-50 dark:bg-slate-700 p-3 rounded-xl border border-gray-200 dark:border-slate-600 text-xs space-y-1">
                    <div><span class="text-gray-500 dark:text-gray-400 font-semibold">Tipe:</span> <span class="font-bold ${isCredit ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'}">${isCredit ? '➕ Saldo Masuk (Kredit)' : '➖ Saldo Keluar (Debit)'}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400 font-semibold">Nominal:</span> <span class="font-bold font-mono text-gray-900 dark:text-white">Rp ${amountStr}</span></div>
                    <div class="text-gray-500 dark:text-gray-400 italic pt-1 border-t border-gray-200 dark:border-slate-600">${tx.description || '-'}</div>
                </div>
                <div class="p-3 rounded-xl text-xs ${isCredit ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-800' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800'}">
                    <label class="flex items-start gap-2.5 cursor-pointer font-medium">
                        <input type="checkbox" id="swal-adjust-balance" checked class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                        <span>
                            <strong>Sesuaikan Saldo Reseller Otomatis</strong><br>
                            <span class="text-[11px] opacity-90">${isCredit 
                                ? 'Saldo dompet reseller akan ditarik/dikurangi kembali Rp ' + amountStr 
                                : 'Saldo dompet reseller akan dikembalikan (+ Rp ' + amountStr + ').'}</span>
                        </span>
                    </label>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Mutasi',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const checkbox = document.getElementById('swal-adjust-balance');
            return {
                adjust_balance: checkbox ? checkbox.checked : true
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const adjust = result.value?.adjust_balance ?? true;
            router.delete(route('reseller-transactions.destroy', tx.id), {
                data: { adjust_balance: adjust },
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res?.props?.flash?.error) {
                        Swal.fire('Gagal Menghapus', res.props.flash.error, 'error');
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihapus!',
                        text: 'Data transaksi mutasi telah dihapus' + (adjust ? ' dan saldo reseller telah disesuaikan.' : '.'),
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    const msg = err?.error || Object.values(err || {}).flat().join('<br>') || 'Gagal menghapus transaksi mutasi.';
                    Swal.fire('Gagal Menghapus', msg, 'error');
                }
            });
        }
    });
};

const confirmDeleteReq = (req) => {
    const resellerName = getResellerName(req.reseller);
    Swal.fire({
        title: 'Hapus Riwayat Pengajuan?',
        html: `Apakah Anda yakin ingin menghapus data riwayat pengajuan saldo untuk <strong>${resellerName}</strong> sebesar <strong class="text-indigo-600">Rp ${formatRupiah(req.amount)}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('reseller-requests.destroy', req.id), {
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res?.props?.flash?.error) {
                        Swal.fire('Gagal Menghapus', res.props.flash.error, 'error');
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Dihapus!',
                        text: 'Riwayat pengajuan saldo berhasil dihapus.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal Menghapus', err?.error || 'Gagal menghapus pengajuan saldo.', 'error');
                }
            });
        }
    });
};

// ==========================================
// Correction Modal State & Methods
// ==========================================
const showCorrectionModal = ref(false);
const correctionTarget = ref(null);

const correctionForm = useForm({
    amount: '',
    reason: 'Koreksi salah ketik nominal'
});

// Nominal change = newAmount - oldAmount
const nominalDiff = computed(() => {
    if (!correctionTarget.value || !correctionForm.amount) return 0;
    return Number(correctionForm.amount) - Number(correctionTarget.value.oldAmount || 0);
});

// Balance impact: for credit it adds, for debit it subtracts
const balanceImpact = computed(() => {
    if (!correctionTarget.value) return 0;
    if (correctionTarget.value.txType === 'debit') {
        return -nominalDiff.value;
    }
    return nominalDiff.value;
});

const newEstimatedBalance = computed(() => {
    if (!correctionTarget.value) return 0;
    return Number(correctionTarget.value.currentBalance || 0) + balanceImpact.value;
});

const openCorrectionForTx = (tx) => {
    const res = props.resellers.find(r => String(r.id) === String(tx.reseller_id)) || tx.reseller;
    correctionTarget.value = {
        targetType: 'transaction',
        id: tx.id,
        txType: tx.type, // 'credit' or 'debit'
        resellerName: getResellerName(tx.reseller),
        currentBalance: Number(res?.balance || tx.reseller?.balance || 0),
        oldAmount: Number(tx.amount || 0),
        referenceId: tx.reference_id,
        item: tx
    };
    correctionForm.amount = Number(tx.amount || 0);
    correctionForm.reason = 'Koreksi salah ketik nominal';
    showCorrectionModal.value = true;
};

const openCorrectionForReq = (req) => {
    const res = props.resellers.find(r => String(r.id) === String(req.reseller_id)) || req.reseller;
    correctionTarget.value = {
        targetType: 'request',
        id: req.id,
        txType: 'credit', // requests always add balance
        resellerName: getResellerName(req.reseller),
        currentBalance: Number(res?.balance || req.reseller?.balance || 0),
        oldAmount: Number(req.amount || 0),
        referenceId: `REQ-${req.id}`,
        status: req.status,
        item: req
    };
    correctionForm.amount = Number(req.amount || 0);
    correctionForm.reason = 'Koreksi salah ketik nominal';
    showCorrectionModal.value = true;
};

const submitCorrection = () => {
    if (!correctionTarget.value) return;
    if (newEstimatedBalance.value < 0) {
        Swal.fire('Perhatian', 'Saldo reseller tidak mencukupi untuk pengurangan nominal ini.', 'warning');
        return;
    }

    if (correctionTarget.value.targetType === 'transaction') {
        correctionForm.put(route('reseller-transactions.update', correctionTarget.value.id), {
            preserveScroll: true,
            onSuccess: (res) => {
                if (res?.props?.flash?.error) {
                    Swal.fire('Gagal Mengoreksi', res.props.flash.error, 'error');
                    return;
                }
                showCorrectionModal.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Dikoreksi!',
                    text: 'Nominal transaksi dan saldo reseller telah disesuaikan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            onError: (err) => {
                Swal.fire('Gagal Mengoreksi', Object.values(err).flat().join('<br>'), 'error');
            }
        });
    } else {
        correctionForm.put(route('reseller-requests.update', correctionTarget.value.id), {
            preserveScroll: true,
            onSuccess: (res) => {
                if (res?.props?.flash?.error) {
                    Swal.fire('Gagal Mengoreksi', res.props.flash.error, 'error');
                    return;
                }
                showCorrectionModal.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Dikoreksi!',
                    text: 'Nominal pengajuan saldo dan data terkait telah disesuaikan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            onError: (err) => {
                Swal.fire('Gagal Mengoreksi', Object.values(err).flat().join('<br>'), 'error');
            }
        });
    }
};

// Helper Functions
const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatWa = (phone) => {
    if (!phone) return '';
    const clean = String(phone).replace(/\D/g, '');
    if (clean.startsWith('0')) {
        return '62' + clean.slice(1);
    }
    return clean;
};

const getResellerName = (reseller) => {
    if (!reseller) return 'Unknown Reseller';
    return reseller.customer?.name || reseller.user?.name || ('Reseller #' + reseller.id);
};
</script>
