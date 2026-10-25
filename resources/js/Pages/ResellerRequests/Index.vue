<template>
    <AppLayout title="Riwayat & Mutasi Saldo Reseller" subtitle="Pantau seluruh mutasi saldo masuk & keluar (voucher, loket, kasbon) dan kelola permohonan isi saldo reseller.">
        <div class="max-w-7xl mx-auto space-y-6 pb-12">
            
            <!-- Header Actions & Flash Messages -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.02)]">
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span>💳</span>
                        <span>Riwayat Saldo &amp; Keuangan Reseller</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">
                        Sistem pencatatan terintegrasi untuk saldo dompet reseller, mutasi transaksi pembelian voucher, loket biller, dan kasbon.
                    </p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button 
                        @click="openAddModal" 
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-5 py-2.5 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 transition-all cursor-pointer transform active:scale-95"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Isi Saldo Manual</span>
                    </button>
                </div>
            </div>

            <!-- KPI Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Saldo Beredar -->
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-3xl p-5 text-white shadow-lg shadow-indigo-100 relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-100">Total Saldo Beredar</span>
                        <div class="p-2 bg-white/20 backdrop-blur-md rounded-xl text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-mono">Rp {{ formatRupiah(stats.total_saldo_beredar) }}</div>
                    <p class="text-[11px] text-purple-100 mt-1">Saldo aktif di dompet {{ resellers.length }} reseller</p>
                </div>

                <!-- Total Saldo Masuk (Kredit) -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.02)]">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Total Saldo Masuk</span>
                        <div class="p-2 bg-emerald-50 rounded-xl text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-mono text-gray-900">+ Rp {{ formatRupiah(stats.total_credit) }}</div>
                    <p class="text-[11px] text-gray-400 mt-1">Top Up Kasbon, Tunai &amp; Payment Gateway</p>
                </div>

                <!-- Total Saldo Terpakai (Debit) -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.02)]">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Saldo Terpakai</span>
                        <div class="p-2 bg-rose-50 rounded-xl text-rose-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-mono text-gray-900">- Rp {{ formatRupiah(stats.total_debit) }}</div>
                    <p class="text-[11px] text-gray-400 mt-1">Pembelian Voucher &amp; Bayar Tagihan Loket</p>
                </div>

                <!-- Permintaan Pending -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.02)]">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Permintaan Pending</span>
                        <div class="p-2 bg-amber-50 rounded-xl text-amber-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-2xl font-black font-mono text-gray-900">{{ stats.pending_requests_count }}</div>
                        <span class="text-xs font-bold text-amber-600">Pengajuan</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Rp {{ formatRupiah(stats.pending_requests_amount) }} menunggu persetujuan</p>
                </div>
            </div>

            <!-- Tab Navigation & Filters Container -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.02)] border border-gray-100 overflow-hidden">
                
                <!-- Tab Headers -->
                <div class="flex items-center justify-between border-b border-gray-100 px-6 pt-4 bg-gray-50/60 flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <button 
                            @click="switchTab('transactions')" 
                            class="px-5 py-3 rounded-t-2xl font-bold text-sm transition-all relative flex items-center gap-2 cursor-pointer"
                            :class="activeTab === 'transactions' ? 'bg-white text-indigo-600 border-t-2 border-indigo-600 shadow-xs' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100/70'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>Mutasi Saldo (Kredit &amp; Debit)</span>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-black font-mono" :class="activeTab === 'transactions' ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-200/70 text-gray-600'">
                                {{ transactions.total || 0 }}
                            </span>
                        </button>
                        <button 
                            @click="switchTab('requests')" 
                            class="px-5 py-3 rounded-t-2xl font-bold text-sm transition-all relative flex items-center gap-2 cursor-pointer"
                            :class="activeTab === 'requests' ? 'bg-white text-indigo-600 border-t-2 border-indigo-600 shadow-xs' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100/70'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Permintaan &amp; Top Up Saldo</span>
                            <span v-if="stats.pending_requests_count > 0" class="text-[11px] px-2 py-0.5 rounded-full font-black font-mono bg-amber-500 text-white animate-pulse">
                                {{ stats.pending_requests_count }} Pending
                            </span>
                            <span v-else class="text-[11px] px-2 py-0.5 rounded-full font-black font-mono" :class="activeTab === 'requests' ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-200/70 text-gray-600'">
                                {{ requests.total || 0 }}
                            </span>
                        </button>
                    </div>

                    <!-- Reset Filter Button -->
                    <div v-if="hasActiveFilters" class="pb-2">
                        <button 
                            @click="resetFilters" 
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-xl transition cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Reset Filter</span>
                        </button>
                    </div>
                </div>

                <!-- Filter Controls Toolbar -->
                <div class="p-6 border-b border-gray-100 bg-white">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                        <!-- Search Box -->
                        <div class="lg:col-span-4 relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input 
                                v-model="filterForm.search" 
                                @input="debouncedSearch"
                                type="text" 
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" 
                                placeholder="Cari keterangan, kode ref, atau nama reseller..."
                            />
                        </div>

                        <!-- Reseller Dropdown Filter -->
                        <div class="lg:col-span-3">
                            <select 
                                v-model="filterForm.reseller_id" 
                                @change="applyFilters"
                                class="w-full px-3 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                            >
                                <option value="">Semua Reseller</option>
                                <option v-for="res in resellers" :key="res.id" :value="res.id">
                                    {{ res.name }} (Saldo: Rp {{ formatRupiah(res.balance) }})
                                </option>
                            </select>
                        </div>

                        <!-- Type / Status Filter depending on active tab -->
                        <div v-if="activeTab === 'transactions'" class="lg:col-span-2">
                            <select 
                                v-model="filterForm.type" 
                                @change="applyFilters"
                                class="w-full px-3 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                            >
                                <option value="">Semua Tipe Mutasi</option>
                                <option value="credit">➕ Saldo Masuk (Kredit)</option>
                                <option value="debit">➖ Saldo Keluar (Debit)</option>
                            </select>
                        </div>
                        <div v-else class="lg:col-span-2">
                            <select 
                                v-model="filterForm.status" 
                                @change="applyFilters"
                                class="w-full px-3 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                            >
                                <option value="">Semua Status</option>
                                <option value="pending">⏳ Menunggu (Pending)</option>
                                <option value="approved">✅ Disetujui (Approved)</option>
                                <option value="rejected">❌ Ditolak (Rejected)</option>
                            </select>
                        </div>

                        <!-- Date Range Filters -->
                        <div class="lg:col-span-3 flex items-center gap-2">
                            <input 
                                v-model="filterForm.start_date" 
                                @change="applyFilters"
                                type="date" 
                                class="w-1/2 px-2.5 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                title="Dari Tanggal"
                            />
                            <span class="text-gray-400 text-xs">-</span>
                            <input 
                                v-model="filterForm.end_date" 
                                @change="applyFilters"
                                type="date" 
                                class="w-1/2 px-2.5 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                title="Sampai Tanggal"
                            />
                        </div>
                    </div>
                </div>

                <!-- TAB 1: Mutasi Transaksi Saldo (Kredit & Debit) -->
                <div v-if="activeTab === 'transactions'">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Waktu &amp; Tanggal</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Reseller</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tipe</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Nominal</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Keterangan &amp; Referensi</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-black text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-indigo-50/30 transition-colors">
                                    <!-- Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                        <div class="font-bold text-gray-900">{{ formatDate(tx.created_at) }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono mt-0.5">{{ formatTime(tx.created_at) }} WIB</div>
                                    </td>

                                    <!-- Reseller -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                            <span>{{ getResellerName(tx.reseller) }}</span>
                                            <span v-if="tx.reseller?.customer" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">
                                                Pelanggan
                                            </span>
                                            <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/60">
                                                Mitra User
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                            <span>{{ tx.reseller?.customer?.phone || tx.reseller?.user?.phone || '-' }}</span>
                                            <a 
                                                v-if="tx.reseller?.customer?.phone || tx.reseller?.user?.phone" 
                                                :href="`https://wa.me/${formatWa(tx.reseller?.customer?.phone || tx.reseller?.user?.phone)}`" 
                                                target="_blank" 
                                                class="text-[10px] text-emerald-600 font-bold hover:underline"
                                            >
                                                💬 WA
                                            </a>
                                        </div>
                                    </td>

                                    <!-- Tipe Mutasi -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            v-if="tx.type === 'credit'" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200/70"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"/></svg>
                                            KREDIT (Masuk)
                                        </span>
                                        <span 
                                            v-else 
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200/70"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                            DEBIT (Keluar)
                                        </span>
                                    </td>

                                    <!-- Nominal -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div 
                                            class="text-sm font-black font-mono"
                                            :class="tx.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'"
                                        >
                                            {{ tx.type === 'credit' ? '+' : '-' }} Rp {{ formatRupiah(tx.amount) }}
                                        </div>
                                    </td>

                                    <!-- Keterangan & Referensi -->
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-800 font-medium break-words max-w-md">
                                            {{ tx.description }}
                                        </div>
                                        <div v-if="tx.reference_id" class="mt-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                                Ref: {{ tx.reference_id }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                @click="openCorrectionForTx(tx)" 
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-2.5 py-1.5 rounded-lg border border-indigo-200 transition font-bold cursor-pointer"
                                                title="Koreksi nominal transaksi jika salah input"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Koreksi</span>
                                            </button>
                                            <button 
                                                @click="confirmDeleteTx(tx)" 
                                                class="inline-flex items-center gap-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2.5 py-1.5 rounded-lg border border-rose-200 transition font-bold cursor-pointer"
                                                title="Hapus riwayat mutasi ini dan sesuaikan saldo reseller"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!transactions.data || !transactions.data.length">
                                    <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 mb-3">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            </div>
                                            <p class="font-bold text-gray-800 text-sm">Belum Ada Riwayat Mutasi Saldo</p>
                                            <p class="text-xs text-gray-400 mt-1 max-w-sm">
                                                Mutasi akan otomatis tercatat setiap kali reseller membeli voucher, membayar di loket biller, atau mendapatkan isi saldo manual.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.links && transactions.links.length > 3" class="px-6 py-4 bg-gray-50/70 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-gray-500">
                            Menampilkan <span class="font-bold text-gray-800">{{ transactions.from || 0 }}</span> - <span class="font-bold text-gray-800">{{ transactions.to || 0 }}</span> dari <span class="font-bold text-gray-800">{{ transactions.total || 0 }}</span> mutasi
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, p) in transactions.links" :key="p">
                                <div v-if="link.url === null" class="px-3 py-1 text-xs text-gray-400 border border-gray-200 rounded-lg bg-gray-50 select-none" v-html="link.label" />
                                <Link 
                                    v-else 
                                    :href="link.url" 
                                    preserve-scroll 
                                    class="px-3 py-1 text-xs border rounded-lg hover:bg-gray-100 transition cursor-pointer"
                                    :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white font-bold shadow-xs' : 'bg-white border-gray-200 text-gray-700'"
                                    v-html="link.label" 
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Permintaan & Top Up Saldo (Kasbon & Transfer) -->
                <div v-else-if="activeTab === 'requests'">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tanggal Pengajuan</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Reseller</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Nominal</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Metode &amp; Catatan</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Disetujui Oleh</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-black text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="req in requests.data" :key="req.id" class="hover:bg-indigo-50/30 transition-colors">
                                    <!-- Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                        <div class="font-bold text-gray-900">{{ formatDate(req.created_at) }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono mt-0.5">{{ formatTime(req.created_at) }} WIB</div>
                                    </td>

                                    <!-- Reseller -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                            <span>{{ getResellerName(req.reseller) }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ req.reseller?.customer?.phone || req.reseller?.user?.phone || '-' }}
                                        </div>
                                    </td>

                                    <!-- Nominal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-black font-mono text-indigo-600">
                                        Rp {{ formatRupiah(req.amount) }}
                                    </td>

                                    <!-- Metode & Catatan -->
                                    <td class="px-6 py-4">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase mb-1"
                                            :class="{
                                                'bg-purple-100 text-purple-800 border border-purple-200': req.payment_method === 'kasbon',
                                                'bg-blue-100 text-blue-800 border border-blue-200': req.payment_method === 'transfer',
                                                'bg-emerald-100 text-emerald-800 border border-emerald-200': req.payment_method === 'cash',
                                            }"
                                        >
                                            {{ req.payment_method === 'kasbon' ? 'Kasbon Saldo' : (req.payment_method === 'transfer' ? 'Transfer Bank' : 'Tunai / Cash') }}
                                        </span>
                                        <div class="text-xs text-gray-500 italic break-words line-clamp-2 max-w-xs">
                                            {{ req.notes || 'Tidak ada catatan' }}
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="req.status === 'pending'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                            ⏳ Menunggu
                                        </span>
                                        <span v-else-if="req.status === 'approved'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✅ Disetujui
                                        </span>
                                        <span v-else-if="req.status === 'paid'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">
                                            🟢 Lunas
                                        </span>
                                        <span v-else-if="req.status === 'rejected'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            ❌ Ditolak
                                        </span>
                                    </td>

                                    <!-- Approver -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                        <div v-if="req.approver" class="font-bold text-gray-800">{{ req.approver.name }}</div>
                                        <div v-else class="text-gray-400 italic">-</div>
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
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-2.5 py-1.5 rounded-lg border border-indigo-200 transition text-xs font-bold cursor-pointer"
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
                                                @click="openCorrectionForReq(req)" 
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-2.5 py-1.5 rounded-lg border border-indigo-200 transition text-xs font-bold cursor-pointer"
                                                title="Koreksi nominal saldo yang sudah masuk"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Koreksi</span>
                                            </button>
                                            <button 
                                                @click="confirmRollbackReq(req)" 
                                                class="inline-flex items-center gap-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2.5 py-1.5 rounded-lg border border-rose-200 transition text-xs font-bold cursor-pointer"
                                                title="Batalkan pengisian saldo dan tarik kembali saldonya"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Batalkan</span>
                                            </button>
                                        </div>

                                        <!-- Status Lainnya (Rejected / Dibatalkan) -->
                                        <div v-else class="flex justify-end items-center gap-1.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-gray-100 text-gray-500 font-semibold mr-1">
                                                Dibatalkan
                                            </span>
                                            <button 
                                                @click="confirmDeleteReq(req)" 
                                                class="inline-flex items-center gap-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2 py-1 rounded-lg border border-rose-200 transition text-xs font-bold cursor-pointer"
                                                title="Hapus riwayat pengajuan ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!requests.data || !requests.data.length">
                                    <td colspan="7" class="px-6 py-16 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 mb-3">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <p class="font-bold text-gray-800 text-sm">Tidak Ada Permintaan Saldo</p>
                                            <p class="text-xs text-gray-400 mt-1 max-w-sm">
                                                Gunakan tombol "Isi Saldo Manual" untuk menambahkan saldo reseller secara langsung.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="requests.links && requests.links.length > 3" class="px-6 py-4 bg-gray-50/70 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-gray-500">
                            Menampilkan <span class="font-bold text-gray-800">{{ requests.from || 0 }}</span> - <span class="font-bold text-gray-800">{{ requests.to || 0 }}</span> dari <span class="font-bold text-gray-800">{{ requests.total || 0 }}</span> permohonan
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, p) in requests.links" :key="p">
                                <div v-if="link.url === null" class="px-3 py-1 text-xs text-gray-400 border border-gray-200 rounded-lg bg-gray-50 select-none" v-html="link.label" />
                                <Link 
                                    v-else 
                                    :href="link.url" 
                                    preserve-scroll 
                                    class="px-3 py-1 text-xs border rounded-lg hover:bg-gray-100 transition cursor-pointer"
                                    :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white font-bold shadow-xs' : 'bg-white border-gray-200 text-gray-700'"
                                    v-html="link.label" 
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add Manual Topup Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showAddModal = false"></div>
                <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full border border-gray-100">
                    
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5 text-white flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black tracking-tight" id="modal-title">Isi Saldo Dompet Reseller</h3>
                            <p class="text-xs text-indigo-100 mt-0.5">Tambahkan saldo manual via Kasbon, Transfer, atau Tunai.</p>
                        </div>
                        <button @click="showAddModal = false" class="text-white/80 hover:text-white p-1 rounded-xl hover:bg-white/10 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitTopup" class="p-6 space-y-4">
                        <!-- Select Reseller -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Pilih Reseller <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                v-model="form.reseller_id" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium transition"
                                required
                            >
                                <option value="">-- Pilih Reseller --</option>
                                <option v-for="res in resellers" :key="res.id" :value="res.id">
                                    {{ res.name }} &middot; Saldo Saat Ini: Rp {{ formatRupiah(res.balance) }}
                                </option>
                            </select>
                        </div>

                        <!-- Current Balance Preview if selected -->
                        <div v-if="selectedReseller" class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-3.5 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Saldo Saat Ini</span>
                                <div class="text-base font-black font-mono text-indigo-950">Rp {{ formatRupiah(selectedReseller.balance) }}</div>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Estimasi Saldo Baru</span>
                                <div class="text-base font-black font-mono text-emerald-600">
                                    Rp {{ formatRupiah(Number(selectedReseller.balance) + (Number(form.amount) || 0)) }}
                                </div>
                            </div>
                        </div>

                        <!-- Nominal Saldo -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Nominal Tambah Saldo (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                v-model.number="form.amount" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-mono font-bold" 
                                required 
                                min="1000"
                                step="1000"
                                placeholder="Contoh: 100000"
                            />
                            
                            <!-- Quick Chips -->
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <button 
                                    type="button" 
                                    v-for="preset in [50000, 100000, 200000, 500000, 1000000]" 
                                    :key="preset"
                                    @click="form.amount = preset"
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg border transition cursor-pointer"
                                    :class="form.amount === preset ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 border-gray-200'"
                                >
                                    +{{ formatRupiah(preset) }}
                                </button>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Metode Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <label 
                                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 cursor-pointer transition text-center"
                                    :class="form.payment_method === 'kasbon' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-black' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                >
                                    <input type="radio" v-model="form.payment_method" value="kasbon" class="sr-only" />
                                    <span class="text-base mb-1">📝</span>
                                    <span class="text-xs">Kasbon</span>
                                    <span class="text-[9px] text-gray-400 mt-0.5">Tagihan Besok</span>
                                </label>
                                <label 
                                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 cursor-pointer transition text-center"
                                    :class="form.payment_method === 'transfer' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-black' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                >
                                    <input type="radio" v-model="form.payment_method" value="transfer" class="sr-only" />
                                    <span class="text-base mb-1">🏦</span>
                                    <span class="text-xs">Transfer</span>
                                    <span class="text-[9px] text-emerald-600 font-bold mt-0.5">Sudah Bayar</span>
                                </label>
                                <label 
                                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 cursor-pointer transition text-center"
                                    :class="form.payment_method === 'cash' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900 font-black' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                >
                                    <input type="radio" v-model="form.payment_method" value="cash" class="sr-only" />
                                    <span class="text-base mb-1">💵</span>
                                    <span class="text-xs">Tunai / Cash</span>
                                    <span class="text-[9px] text-emerald-600 font-bold mt-0.5">Sudah Bayar</span>
                                </label>
                            </div>
                            <p v-if="form.payment_method === 'kasbon'" class="text-[11px] text-purple-700 bg-purple-50 p-2.5 rounded-xl border border-purple-200/70 mt-2">
                                ℹ️ <strong>Mode Kasbon:</strong> Saldo langsung bertambah dan otomatis diterbitkan Invoice Penagihan Kasbon di menu <em>Penagihan Kasbon</em>.
                            </p>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Catatan / Keterangan
                            </label>
                            <textarea 
                                v-model="form.notes" 
                                rows="2" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" 
                                placeholder="Contoh: Permintaan via WhatsApp, setor tunai kantor, dll"
                            ></textarea>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button 
                                type="button" 
                                @click="showAddModal = false" 
                                class="px-5 py-2.5 rounded-xl border border-gray-300 text-sm font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-md shadow-indigo-200 transition cursor-pointer disabled:opacity-50"
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
        </div>

        <!-- Correction Modal (Koreksi Nominal Saldo) -->
        <div v-if="showCorrectionModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-correction" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showCorrectionModal = false"></div>
                <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-md w-full border border-gray-100">
                    
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5 text-white flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black tracking-tight" id="modal-correction">Koreksi Nominal Saldo</h3>
                            <p class="text-xs text-blue-100 mt-0.5">Ubah nominal jika salah input atau salah ketik sebelumnya.</p>
                        </div>
                        <button @click="showCorrectionModal = false" class="text-white/80 hover:text-white p-1 rounded-xl hover:bg-white/10 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCorrection" class="p-6 space-y-4">
                        <!-- Info Reseller -->
                        <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-3.5 space-y-1.5">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 font-semibold">Reseller:</span>
                                <span class="font-bold text-gray-900">{{ correctionTarget?.resellerName }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 font-semibold">Saldo Dompet Saat Ini:</span>
                                <span class="font-bold font-mono text-indigo-700">Rp {{ formatRupiah(correctionTarget?.currentBalance) }}</span>
                            </div>
                            <div v-if="correctionTarget?.referenceId" class="flex justify-between items-center text-xs">
                                <span class="text-gray-500 font-semibold">Kode Referensi:</span>
                                <span class="font-mono text-[11px] text-gray-600 font-bold bg-white px-2 py-0.5 rounded border">{{ correctionTarget?.referenceId }}</span>
                            </div>
                        </div>

                        <!-- Perbandingan Nominal -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-100 rounded-2xl p-3 border border-gray-200">
                                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Nominal Lama</span>
                                <div class="text-sm font-black font-mono text-gray-700 line-through">
                                    Rp {{ formatRupiah(correctionTarget?.oldAmount) }}
                                </div>
                            </div>
                            <div class="bg-indigo-50/80 rounded-2xl p-3 border border-indigo-200">
                                <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider block mb-1">Nominal Baru</span>
                                <div class="text-sm font-black font-mono text-indigo-700">
                                    Rp {{ formatRupiah(correctionForm.amount) }}
                                </div>
                            </div>
                        </div>

                        <!-- Input Nominal Baru -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Nominal Seharusnya (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                v-model.number="correctionForm.amount" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono font-bold text-gray-900" 
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
                                    :class="correctionForm.amount === preset ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 border-gray-200'"
                                >
                                    {{ formatRupiah(preset) }}
                                </button>
                            </div>
                        </div>

                        <!-- Live Adjustment Summary Callout -->
                        <div 
                            class="rounded-2xl p-3.5 border transition-all"
                            :class="correctionDiff >= 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900'"
                        >
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span>Selisih Penyesuaian Saldo:</span>
                                <span class="font-mono text-sm font-black">
                                    {{ correctionDiff >= 0 ? '+' : '' }}Rp {{ formatRupiah(correctionDiff) }}
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-600">
                                Saldo reseller akan otomatis 
                                <strong :class="correctionDiff >= 0 ? 'text-emerald-700' : 'text-amber-700'">
                                    {{ correctionDiff >= 0 ? 'bertambah' : 'berkurang' }}
                                </strong> 
                                sebesar Rp {{ formatRupiah(Math.abs(correctionDiff)) }}, sehingga saldo akhirnya menjadi 
                                <strong class="font-mono text-gray-900">Rp {{ formatRupiah(newEstimatedBalance) }}</strong>.
                            </div>
                            <div v-if="newEstimatedBalance < 0" class="text-rose-600 font-bold text-[11px] mt-2 flex items-center gap-1">
                                ⚠️ Saldo akhir tidak boleh minus! Reseller saat ini hanya memiliki saldo Rp {{ formatRupiah(correctionTarget?.currentBalance) }}.
                            </div>
                        </div>

                        <!-- Alasan Koreksi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Alasan / Catatan Koreksi
                            </label>
                            <input 
                                type="text" 
                                v-model="correctionForm.reason" 
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" 
                                placeholder="Contoh: Salah ketik kelebihan angka nol"
                            />
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button 
                                type="button" 
                                @click="showCorrectionModal = false" 
                                class="px-5 py-2.5 rounded-xl border border-gray-300 text-sm font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="correctionForm.processing || newEstimatedBalance < 0 || correctionForm.amount === correctionTarget?.oldAmount" 
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-md shadow-indigo-200 transition cursor-pointer disabled:opacity-50"
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
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

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
    return props.resellers.find(r => r.id === form.reseller_id);
});

const openAddModal = () => {
    form.reset();
    form.payment_method = 'kasbon';
    form.notes = 'Permintaan manual via Admin';
    if (props.resellers.length > 0 && !form.reseller_id) {
        form.reseller_id = props.resellers[0].id;
    }
    showAddModal.value = true;
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
        onSuccess: () => {
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
                onSuccess: () => {
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
                onSuccess: () => {
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
            <div class="text-left text-xs text-gray-600 space-y-3 mt-2">
                <p>Apakah Anda yakin ingin menghapus data riwayat mutasi untuk <strong>${resellerName}</strong>?</p>
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs space-y-1">
                    <div><span class="text-gray-500 font-semibold">Tipe:</span> <span class="font-bold ${isCredit ? 'text-emerald-700' : 'text-rose-700'}">${isCredit ? '➕ Saldo Masuk (Kredit)' : '➖ Saldo Keluar (Debit)'}</span></div>
                    <div><span class="text-gray-500 font-semibold">Nominal:</span> <span class="font-bold font-mono text-gray-900">Rp ${amountStr}</span></div>
                    <div class="text-gray-500 italic pt-1 border-t border-gray-200">${tx.description || '-'}</div>
                </div>
                <div class="p-3 rounded-xl text-xs ${isCredit ? 'bg-amber-50 text-amber-900 border border-amber-200' : 'bg-emerald-50 text-emerald-900 border border-emerald-200'}">
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
                onSuccess: () => {
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
                onSuccess: () => {
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

const correctionDiff = computed(() => {
    if (!correctionTarget.value || !correctionForm.amount) return 0;
    return Number(correctionForm.amount) - Number(correctionTarget.value.oldAmount || 0);
});

const newEstimatedBalance = computed(() => {
    if (!correctionTarget.value) return 0;
    return Number(correctionTarget.value.currentBalance || 0) + correctionDiff.value;
});

const openCorrectionForTx = (tx) => {
    const res = props.resellers.find(r => r.id === tx.reseller_id) || tx.reseller;
    correctionTarget.value = {
        targetType: 'transaction',
        id: tx.id,
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
    const res = props.resellers.find(r => r.id === req.reseller_id) || req.reseller;
    correctionTarget.value = {
        targetType: 'request',
        id: req.id,
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
            onSuccess: () => {
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
            onSuccess: () => {
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
    return String(phone).replace(/^0/, '62').replace(/\D/g, '');
};

const getResellerName = (reseller) => {
    if (!reseller) return 'Unknown Reseller';
    return reseller.customer?.name || reseller.user?.name || ('Reseller #' + reseller.id);
};
</script>
