<template>
    <AppLayout title="Laporan Keuangan Reseller" subtitle="Ringkasan kasbon, setoran, dan saldo beredar">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-12 font-sans">
            
            <!-- Dashboard Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-3.5 sm:gap-4 print:grid-cols-5">
                <!-- Saldo Beredar -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(79,70,229,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-indigo-100 font-medium tracking-wide uppercase text-[10px] mb-1">Saldo Beredar</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.saldo_beredar) }}</h2>
                        <p class="text-[11px] text-indigo-100/80 mt-1">Saldo aktif dompet mitra</p>
                    </div>
                </div>

                <!-- Total Piutang Kasbon -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(249,115,22,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-orange-100 font-medium tracking-wide uppercase text-[10px] mb-1">Piutang Kasbon</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.piutang) }}</h2>
                        <p class="text-[11px] text-orange-100/80 mt-1">Kasbon belum dilunasi</p>
                    </div>
                </div>

                <!-- Total Pemasukan -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(34,197,94,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-green-100 font-medium tracking-wide uppercase text-[10px] mb-1">Total Pemasukan</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.pemasukan) }}</h2>
                        <p class="text-[11px] text-green-100/80 mt-1">Setoran kasbon, modal awal &amp; top up</p>
                    </div>
                </div>

                <!-- Beban Operasional Reseller -->
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(239,68,68,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-red-100 font-medium tracking-wide uppercase text-[10px] mb-1">Beban Operasional Reseller</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.pengeluaran) }}</h2>
                        <p class="text-[11px] text-red-100/80 mt-1">Beban khusus operasional kemitraan</p>
                    </div>
                </div>

                <!-- Kas Bersih Reseller -->
                <div 
                    class="sm:col-span-2 xl:col-span-2 2xl:col-span-1 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(59,130,246,0.2)] relative overflow-hidden group ring-4 min-w-0 transition-all"
                    :class="stats.kas_bersih >= 0 ? 'bg-gradient-to-br from-blue-500 to-blue-600 ring-blue-500/20' : 'bg-gradient-to-br from-rose-600 to-rose-700 ring-rose-500/20'"
                >
                    <div class="relative z-10 min-w-0">
                        <p class="text-blue-100 font-medium tracking-wide uppercase text-[10px] mb-1">Kas Bersih Reseller</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">
                            Rp {{ formatRupiah(stats.kas_bersih) }}
                        </h2>
                        <p class="text-[11px] text-blue-100/80 mt-1">Pemasukan dikurangi beban mitra</p>
                    </div>
                </div>
            </div>

            <!-- ── FILTER UTAMA CERDAS & LENGKAP ── -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-xs border border-gray-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all print:hidden">
                <div class="space-y-3.5">
                    
                    <!-- Baris 1: Filter Presets Periode & Quick Action Buttons -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <!-- Presets Periode -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mr-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Periode:
                            </span>
                            <button 
                                type="button" 
                                v-for="preset in datePresets" 
                                :key="preset.id"
                                @click="applyDatePreset(preset.id)"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                                :class="activePreset === preset.id 
                                    ? 'bg-indigo-600 text-white shadow-xs' 
                                    : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                            >
                                {{ preset.label }}
                            </button>
                        </div>

                        <!-- Action Toolbar -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button 
                                @click="exportCsv" 
                                type="button" 
                                title="Ekspor data hasil filter ke file CSV / Excel"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-bold transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Ekspor CSV</span>
                            </button>
                            <button 
                                @click="printReport" 
                                type="button" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 text-xs font-bold transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Cetak Laporan</span>
                            </button>
                            <Link :href="route('reseller-requests.index')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-xs font-bold transition">
                                <span>💳 Mutasi &amp; Saldo</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>
                        </div>
                    </div>

                    <!-- Baris 2: Controls Lengkap (Pencarian, Area, Tipe, Status, Urutan, dan Rentang Tanggal) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-12 gap-3 items-end">
                        
                        <!-- Pencarian Cepat Cerdas -->
                        <div class="xl:col-span-3 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Cari Reseller
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="filtersForm.search" 
                                    placeholder="Cari nama, kode, nomor WA, email..." 
                                    class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 pl-8 pr-7 py-2 transition"
                                />
                                <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <button 
                                    v-if="filtersForm.search" 
                                    @click="filtersForm.search = ''" 
                                    type="button" 
                                    class="absolute right-2 top-2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 text-xs"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Filter Wilayah / Area -->
                        <div class="xl:col-span-2 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Wilayah / Area
                            </label>
                            <select 
                                v-model="filtersForm.area_id" 
                                @change="submitBackendFilter"
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition cursor-pointer"
                            >
                                <option value="">Semua Wilayah</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">
                                    {{ a.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Tipe Reseller -->
                        <div class="xl:col-span-2 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Tipe Mitra
                            </label>
                            <select 
                                v-model="filtersForm.type" 
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition cursor-pointer"
                            >
                                <option value="all">Semua Tipe</option>
                                <option value="pelanggan">Pelanggan (Loket Biller)</option>
                                <option value="mitra_user">Mitra User (Direct)</option>
                            </select>
                        </div>

                        <!-- Filter Status Saldo / Piutang -->
                        <div class="xl:col-span-2 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Status Keuangan
                            </label>
                            <select 
                                v-model="filtersForm.status" 
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition cursor-pointer"
                            >
                                <option value="all">Semua Status</option>
                                <option value="has_balance">Ada Saldo (> Rp 0)</option>
                                <option value="zero_balance">Saldo Kosong (Rp 0)</option>
                                <option value="has_piutang">Punya Piutang Kasbon (> Rp 0)</option>
                                <option value="lunas">Kasbon Lunas / Nol</option>
                            </select>
                        </div>

                        <!-- Urutkan Berdasarkan (Sort) -->
                        <div class="xl:col-span-3 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Urutkan Berdasarkan
                            </label>
                            <select 
                                v-model="filtersForm.sort" 
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition cursor-pointer"
                            >
                                <option value="balance_desc">Saldo Tertinggi (Bawaan)</option>
                                <option value="balance_asc">Saldo Terendah</option>
                                <option value="paid_desc">Total Setoran / Modal Terbanyak</option>
                                <option value="piutang_desc">Sisa Piutang Terbesar</option>
                                <option value="usage_desc">Pemakaian Saldo Terbanyak</option>
                                <option value="name_asc">Nama Reseller (A - Z)</option>
                            </select>
                        </div>

                    </div>

                    <!-- Baris 3: Tanggal Custom Dari - Sampai & Tombol Reset -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 pt-3 border-t border-gray-100 dark:border-slate-800 items-center">
                        <div class="xl:col-span-3 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Tanggal Mulai
                            </label>
                            <input 
                                type="date" 
                                v-model="filtersForm.start_date" 
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition"
                            />
                        </div>
                        <div class="xl:col-span-3 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Sampai Tanggal
                            </label>
                            <input 
                                type="date" 
                                v-model="filtersForm.end_date" 
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition"
                            />
                        </div>
                        <div class="sm:col-span-2 xl:col-span-6 flex flex-wrap items-center justify-start xl:justify-end gap-2 pt-1 xl:pt-4">
                            <button 
                                @click="submitBackendFilter" 
                                type="button" 
                                class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1.5 px-3.5 rounded-xl transition shadow-xs cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                <span>Terapkan Rentang Tanggal</span>
                            </button>
                            <button 
                                v-if="hasActiveFilters" 
                                @click="resetAllFilters" 
                                type="button" 
                                class="inline-flex items-center gap-1.5 bg-gray-100 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-gray-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 text-xs font-bold py-1.5 px-3 rounded-xl transition border border-gray-200 dark:border-slate-700 cursor-pointer"
                            >
                                <span>✕ Reset Semua Filter</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── RINGKASAN DINAMIS HASIL FILTER (CERDAS) ── -->
            <div class="bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-900/50 rounded-2xl p-3 sm:p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-indigo-950 dark:text-indigo-200 font-semibold">
                    <span class="inline-flex items-center gap-1.5 bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2.5 py-1 rounded-lg text-xs font-bold">
                        <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                        {{ filteredResellers.length }} dari {{ resellers.length }} Mitra
                    </span>
                    <span class="text-gray-400 dark:text-slate-600 hidden sm:inline">&vert;</span>
                    <span>Saldo Tersaring: <b class="text-indigo-700 dark:text-indigo-300">Rp {{ formatRupiah(filteredTotals.balance) }}</b></span>
                    <span class="text-gray-400 dark:text-slate-600 hidden sm:inline">&vert;</span>
                    <span>Piutang Tersaring: <b :class="filteredTotals.piutang > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-600 dark:text-slate-400'">Rp {{ formatRupiah(filteredTotals.piutang) }}</b></span>
                    <span class="text-gray-400 dark:text-slate-600 hidden sm:inline">&vert;</span>
                    <span>Setoran/Modal: <b class="text-emerald-700 dark:text-emerald-300">Rp {{ formatRupiah(filteredTotals.total_paid) }}</b></span>
                </div>
                <div v-if="hasActiveFilters" class="text-[11px] text-indigo-700 dark:text-indigo-300 font-medium">
                    Filter aktif &bull; Menampilkan data spesifik
                </div>
            </div>

            <!-- Tabel Reseller -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 dark:border-slate-800 overflow-hidden min-w-0">
                <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Rincian Per Reseller</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">Analisis saldo dompet, kasbon, dan total uang masuk dari masing-masing mitra.</p>
                    </div>

                    <!-- Status Quick Pills -->
                    <div class="flex flex-wrap items-center gap-1.5 print:hidden">
                        <button 
                            type="button"
                            @click="filtersForm.status = 'all'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                            :class="filtersForm.status === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                        >
                            Semua ({{ resellers.length }})
                        </button>
                        <button 
                            type="button"
                            @click="filtersForm.status = 'has_balance'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1"
                            :class="filtersForm.status === 'has_balance' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                        >
                            <span>Ada Saldo</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full" :class="filtersForm.status === 'has_balance' ? 'bg-indigo-700' : 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300'">
                                {{ countHasBalance }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="filtersForm.status = 'has_piutang'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1"
                            :class="filtersForm.status === 'has_piutang' ? 'bg-rose-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700'"
                        >
                            <span>Ada Piutang</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full" :class="filtersForm.status === 'has_piutang' ? 'bg-rose-700' : 'bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300'">
                                {{ countHasPiutang }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                        <thead class="bg-gray-50 dark:bg-slate-800/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Nama Reseller</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Saldo Tersisa</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Kasbon (Hutang)</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Sisa Piutang</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider" title="Total akumulasi uang masuk dari setoran kasbon lunas, top up langsung, dan modal awal kemitraan">
                                    Total Setoran / Modal
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                            <tr v-for="reseller in filteredResellers" :key="reseller.id" class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                        <span>{{ reseller.name }}</span>
                                        <span 
                                            class="text-[10px] font-bold px-1.5 py-0.5 rounded border shrink-0"
                                            :class="reseller.has_customer ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800' : 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800'"
                                        >
                                            {{ reseller.type }}
                                        </span>
                                        <span v-if="reseller.area_name && reseller.area_name !== '-'" class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300">
                                            {{ reseller.area_name }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
                                        <span class="font-mono text-gray-400 dark:text-slate-500">{{ reseller.code }}</span>
                                        <span v-if="reseller.phone && reseller.phone !== '-'">{{ reseller.phone }}</span>
                                        <a 
                                            v-if="reseller.phone && reseller.phone !== '-'" 
                                            :href="`https://wa.me/${formatWa(reseller.phone)}`" 
                                            target="_blank" 
                                            class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline inline-flex items-center gap-0.5"
                                        >
                                            💬 WA
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-indigo-600 dark:text-indigo-400">Rp {{ formatRupiah(reseller.balance) }}</div>
                                    <div v-if="reseller.total_usage > 0" class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">
                                        Terpakai: Rp {{ formatRupiah(reseller.total_usage) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-600 dark:text-slate-300">Rp {{ formatRupiah(reseller.total_kasbon) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black" :class="reseller.piutang > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-slate-500'">
                                        Rp {{ formatRupiah(reseller.piutang) }}
                                    </div>
                                    <div v-if="reseller.piutang > 0" class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-0.5">⚠️ Perlu ditagih</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-green-600 dark:text-green-400">Rp {{ formatRupiah(reseller.total_paid) }}</div>
                                    <div class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Setoran &amp; Modal Masuk</div>
                                </td>
                            </tr>
                            <tr v-if="!filteredResellers.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                    <p class="font-medium">Tidak ada data mitra reseller yang cocok dengan filter yang dipilih.</p>
                                    <button 
                                        @click="resetAllFilters" 
                                        type="button" 
                                        class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                    >
                                        Reset Filter
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 sm:gap-6">
                <!-- Mutasi Transaksi -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 dark:border-slate-800 p-4 sm:p-6 min-w-0">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Riwayat Mutasi Saldo &amp; Voucher</h3>
                        <span class="text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg shrink-0">
                            {{ recent_transactions.length }} Terakhir
                        </span>
                    </div>
                    
                    <div v-if="recent_transactions.length" class="space-y-3">
                        <div v-for="trx in recent_transactions" :key="trx.id" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-3.5 rounded-2xl border border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors min-w-0">
                            <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                                <div :class="trx.type === 'credit' ? 'bg-green-100 dark:bg-green-950/50 text-green-600 dark:text-green-400' : 'bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400'" class="p-2.5 rounded-xl shrink-0">
                                    <svg v-if="trx.type === 'credit'" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white break-words">{{ getResellerName(trx.reseller) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 break-words" :title="trx.description">{{ trx.description }}</p>
                                    <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">{{ new Date(trx.created_at).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>
                            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center pl-11 sm:pl-0 shrink-0">
                                <p :class="trx.type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-sm font-black whitespace-nowrap">
                                    {{ trx.type === 'credit' ? '+' : '-' }} Rp {{ formatRupiah(trx.amount) }}
                                </p>
                                <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-wide font-bold">{{ trx.reference_id }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100 dark:border-slate-800 border-dashed text-gray-500 dark:text-slate-400 text-sm">
                        Belum ada riwayat mutasi transaksi. <br>
                        <span class="text-xs">Beli voucher atau tambah saldo untuk melihat transaksi di sini.</span>
                    </div>
                </div>

                <!-- Buku Kas Beban Reseller -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 dark:border-slate-800 p-4 sm:p-6 flex flex-col h-full min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Buku Kas Beban Reseller</h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Biaya jamuan, ID card, spanduk &amp; operasional kemitraan.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Link :href="route('expense-categories.index')" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 px-3 py-1.5 rounded-xl shadow-xs text-xs font-bold transition-all whitespace-nowrap">
                                <svg class="w-4 h-4 text-gray-500 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Kategori
                            </Link>
                            <button @click="showExpenseModal = true" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-1.5 rounded-xl shadow-xs transition-all hover:shadow text-xs font-bold cursor-pointer whitespace-nowrap">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Catat Beban
                            </button>
                        </div>
                    </div>
                    
                    <div v-if="recent_expenses.length" class="space-y-3 flex-1">
                        <div v-for="exp in recent_expenses" :key="exp.id" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-3.5 rounded-2xl border border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors min-w-0">
                            <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                                <div class="bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400 p-2.5 rounded-xl shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white break-words">{{ exp.description }}</p>
                                        <span v-if="exp.category" class="text-[10px] font-bold px-2 py-0.5 rounded-md border shrink-0 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800">
                                            {{ exp.category.name }}
                                        </span>
                                        <span v-if="exp.area" class="text-[10px] font-bold px-2 py-0.5 rounded-md border bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800 shrink-0">
                                            Wilayah: {{ exp.area }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">{{ new Date(exp.expense_date).toLocaleDateString('id-ID') }} &middot; Oleh: {{ exp.user?.name || 'Sistem' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 pl-11 sm:pl-0 shrink-0">
                                <p class="text-sm font-black text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                    - Rp {{ formatRupiah(exp.amount) }}
                                </p>
                                <button @click="deleteExpense(exp.id)" class="text-gray-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 transition-colors p-1 cursor-pointer" title="Hapus Pengeluaran">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100 dark:border-slate-800 border-dashed text-gray-500 dark:text-slate-400 text-sm flex-1 flex flex-col justify-center">
                        Belum ada riwayat beban operasional reseller.<br>
                        <span class="text-xs text-gray-400 dark:text-slate-500 mt-1">Biaya seperti jamuan mitra, cetak brosur/spanduk, atau komisi dapat dicatat di sini.</span>
                    </div>
                </div>
            </div>

            <!-- Modal Form Pengeluaran -->
            <div v-if="showExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/75 backdrop-blur-xs transition-opacity" @click="showExpenseModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md w-full max-h-[90vh] flex flex-col border border-gray-100 dark:border-slate-800">
                    <div class="bg-gray-50/50 dark:bg-slate-800/40 px-5 sm:px-6 py-4 sm:py-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <h3 class="text-base sm:text-lg leading-6 font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 p-1.5 rounded-lg shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </span>
                            Catat Beban Operasional Reseller
                        </h3>
                        <button @click="showExpenseModal = false" class="text-gray-400 dark:text-slate-500 hover:text-gray-500 dark:hover:text-slate-300 cursor-pointer shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="px-5 sm:px-6 py-4 sm:py-5 overflow-y-auto flex-1">
                        <form @submit.prevent="submitExpense" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="expenseForm.expense_date" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-gray-900 dark:text-slate-100 rounded-xl shadow-xs focus:ring-rose-500 focus:border-rose-500 text-sm" required>
                                <p v-if="expenseForm.errors.expense_date" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.expense_date }}</p>
                            </div>
                            <div>
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kategori Pengeluaran</label>
                                    <Link :href="route('expense-categories.index')" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                                        + Kelola Kategori
                                    </Link>
                                </div>
                                <select v-model="expenseForm.expense_category_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-gray-900 dark:text-slate-100 rounded-xl shadow-xs focus:ring-rose-500 focus:border-rose-500 text-sm font-medium cursor-pointer">
                                    <option value="">-- Tanpa Kategori (Umum) --</option>
                                    <option v-for="cat in expense_categories" :key="cat.id" :value="cat.id">
                                        {{ cat.code ? '[' + cat.code + '] ' : '' }}{{ cat.name }}
                                    </option>
                                </select>
                                <p v-if="expenseForm.errors.expense_category_id" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.expense_category_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Area / Wilayah</label>
                                <select v-model="expenseForm.area_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-gray-900 dark:text-slate-100 rounded-xl shadow-xs focus:ring-rose-500 focus:border-rose-500 text-sm font-medium cursor-pointer">
                                    <option value="">-- Kantor Pusat / Semua Wilayah (Umum) --</option>
                                    <option v-for="a in areas" :key="a.id" :value="a.id">
                                        {{ a.name }}
                                    </option>
                                </select>
                                <p v-if="expenseForm.errors.area_id" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.area_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Nominal (Rp) <span class="text-rose-500">*</span></label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-gray-400 dark:text-slate-500">Rp</span>
                                    <input type="number" v-model="expenseForm.amount" min="1" step="1" class="pl-10 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-gray-900 dark:text-slate-100 rounded-xl shadow-xs focus:ring-rose-500 focus:border-rose-500 font-bold text-lg" placeholder="Contoh: 150000" required>
                                </div>
                                <p v-if="expenseForm.errors.amount" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.amount }}</p>
                                <p v-if="expenseForm.amount && !isNaN(expenseForm.amount) && Number(expenseForm.amount) > 0" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">
                                    Terbaca: Rp {{ formatRupiah(expenseForm.amount) }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Keterangan / Tujuan Pengeluaran <span class="text-rose-500">*</span></label>
                                <textarea v-model="expenseForm.description" rows="2" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-gray-900 dark:text-slate-100 rounded-xl shadow-xs focus:ring-rose-500 focus:border-rose-500 text-sm" placeholder="Contoh: Jamuan makan mitra, cetak spanduk voucher, dll" required></textarea>
                                <p v-if="expenseForm.errors.description" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.description }}</p>
                            </div>
                            
                            <div class="pt-4 flex flex-wrap justify-end gap-2.5 sm:gap-3">
                                <button type="button" @click="showExpenseModal = false" class="flex-1 sm:flex-initial bg-white dark:bg-slate-800 py-2.5 px-5 border border-gray-300 dark:border-slate-700 rounded-xl shadow-xs text-sm font-bold text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" :disabled="expenseForm.processing" class="flex-1 sm:flex-initial inline-flex justify-center items-center gap-1.5 py-2.5 px-5 border border-transparent shadow-xs text-sm font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-all disabled:opacity-50 cursor-pointer">
                                    <svg v-if="expenseForm.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>{{ expenseForm.processing ? 'Menyimpan...' : 'Simpan Beban Reseller' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    stats: Object,
    resellers: Array,
    recent_transactions: Array,
    recent_expenses: Array,
    expense_categories: Array,
    areas: {
        type: Array,
        default: () => []
    },
    filters: Object
});

// Presets Periode Tanggal
const datePresets = [
    { id: 'all', label: 'Semua Waktu' },
    { id: 'today', label: 'Hari Ini' },
    { id: 'this_month', label: 'Bulan Ini' },
    { id: 'last_month', label: 'Bulan Lalu' },
    { id: 'this_year', label: 'Tahun Ini' },
];

const activePreset = ref(props.filters?.start_date ? 'custom' : 'all');

// Form filter reaktif
const filtersForm = reactive({
    search: props.filters?.search || '',
    area_id: props.filters?.area_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    type: props.filters?.type || 'all',
    status: props.filters?.status || 'all',
    sort: props.filters?.sort || 'balance_desc',
});

// Deteksi apakah filter sedang aktif
const hasActiveFilters = computed(() => {
    return !!(
        filtersForm.search ||
        filtersForm.area_id ||
        filtersForm.start_date ||
        filtersForm.end_date ||
        (filtersForm.type && filtersForm.type !== 'all') ||
        (filtersForm.status && filtersForm.status !== 'all') ||
        (filtersForm.sort && filtersForm.sort !== 'balance_desc')
    );
});

// Terapkan preset periode
const applyDatePreset = (presetId) => {
    activePreset.value = presetId;
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (presetId === 'all') {
        filtersForm.start_date = '';
        filtersForm.end_date = '';
    } else if (presetId === 'today') {
        const todayStr = fmt(now);
        filtersForm.start_date = todayStr;
        filtersForm.end_date = todayStr;
    } else if (presetId === 'this_month') {
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        filtersForm.start_date = fmt(firstDay);
        filtersForm.end_date = fmt(lastDay);
    } else if (presetId === 'last_month') {
        const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
        filtersForm.start_date = fmt(firstDay);
        filtersForm.end_date = fmt(lastDay);
    } else if (presetId === 'this_year') {
        const firstDay = new Date(now.getFullYear(), 0, 1);
        const lastDay = new Date(now.getFullYear(), 11, 31);
        filtersForm.start_date = fmt(firstDay);
        filtersForm.end_date = fmt(lastDay);
    }

    submitBackendFilter();
};

// Kirim filter tanggal/area ke backend untuk recalculate ringkasan KPI
const submitBackendFilter = () => {
    router.get(route('reseller-reports.index'), {
        start_date: filtersForm.start_date || undefined,
        end_date: filtersForm.end_date || undefined,
        area_id: filtersForm.area_id || undefined,
        type: filtersForm.type !== 'all' ? filtersForm.type : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Reset semua filter
const resetAllFilters = () => {
    filtersForm.search = '';
    filtersForm.area_id = '';
    filtersForm.start_date = '';
    filtersForm.end_date = '';
    filtersForm.type = 'all';
    filtersForm.status = 'all';
    filtersForm.sort = 'balance_desc';
    activePreset.value = 'all';
    submitBackendFilter();
};

// Hitung cepat jumlah badge
const countHasBalance = computed(() => {
    return (props.resellers || []).filter(r => (r.balance || 0) > 0).length;
});

const countHasPiutang = computed(() => {
    return (props.resellers || []).filter(r => (r.piutang || 0) > 0).length;
});

// Filter & Sort cerdas secara reaktif di sisi klien
const filteredResellers = computed(() => {
    let list = [...(props.resellers || [])];

    // 1. Filter Area (jika dipilih di form lokal)
    if (filtersForm.area_id) {
        list = list.filter(r => String(r.area_id) === String(filtersForm.area_id));
    }

    // 2. Filter Tipe Mitra
    if (filtersForm.type === 'pelanggan') {
        list = list.filter(r => r.has_customer);
    } else if (filtersForm.type === 'mitra_user') {
        list = list.filter(r => !r.has_customer);
    }

    // 3. Filter Status Keuangan
    if (filtersForm.status === 'has_balance') {
        list = list.filter(r => (r.balance || 0) > 0);
    } else if (filtersForm.status === 'zero_balance') {
        list = list.filter(r => (r.balance || 0) <= 0);
    } else if (filtersForm.status === 'has_piutang') {
        list = list.filter(r => (r.piutang || 0) > 0);
    } else if (filtersForm.status === 'lunas') {
        list = list.filter(r => (r.piutang || 0) <= 0);
    }

    // 4. Pencarian Cerdas Multi-kolom (Nama, Kode, Telepon, Email, Area)
    if (filtersForm.search && filtersForm.search.trim() !== '') {
        const q = filtersForm.search.toLowerCase().trim();
        list = list.filter(r => {
            return (
                (r.name || '').toLowerCase().includes(q) ||
                (r.code || '').toLowerCase().includes(q) ||
                (r.phone || '').toLowerCase().includes(q) ||
                (r.email || '').toLowerCase().includes(q) ||
                (r.area_name || '').toLowerCase().includes(q)
            );
        });
    }

    // 5. Urutan (Sorting)
    if (filtersForm.sort === 'balance_desc') {
        list.sort((a, b) => (b.balance || 0) - (a.balance || 0));
    } else if (filtersForm.sort === 'balance_asc') {
        list.sort((a, b) => (a.balance || 0) - (b.balance || 0));
    } else if (filtersForm.sort === 'paid_desc') {
        list.sort((a, b) => (b.total_paid || 0) - (a.total_paid || 0));
    } else if (filtersForm.sort === 'piutang_desc') {
        list.sort((a, b) => (b.piutang || 0) - (a.piutang || 0));
    } else if (filtersForm.sort === 'usage_desc') {
        list.sort((a, b) => (b.total_usage || 0) - (a.total_usage || 0));
    } else if (filtersForm.sort === 'name_asc') {
        list.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
    }

    return list;
});

// Ringkasan Total untuk baris informasi cerdas
const filteredTotals = computed(() => {
    const list = filteredResellers.value;
    return {
        balance: list.reduce((sum, r) => sum + (r.balance || 0), 0),
        piutang: list.reduce((sum, r) => sum + (r.piutang || 0), 0),
        total_paid: list.reduce((sum, r) => sum + (r.total_paid || 0), 0),
        total_kasbon: list.reduce((sum, r) => sum + (r.total_kasbon || 0), 0),
    };
});

// Ekspor CSV langsung di browser
const exportCsv = () => {
    const rows = filteredResellers.value;
    if (!rows.length) {
        Swal.fire({
            icon: 'info',
            title: 'Data Kosong',
            text: 'Tidak ada data reseller yang dapat diekspor sesuai filter saat ini.'
        });
        return;
    }

    const headers = ['ID', 'Nama Reseller', 'Tipe Mitra', 'Wilayah / Area', 'Kode / Akun', 'No WhatsApp', 'Saldo Tersisa (Rp)', 'Total Kasbon (Rp)', 'Sisa Piutang (Rp)', 'Total Setoran / Modal (Rp)', 'Total Pemakaian Voucher (Rp)'];
    
    const escapeCsv = (val) => {
        if (val === null || val === undefined) return '""';
        return `"${String(val).replace(/"/g, '""')}"`;
    };

    const csvContent = [
        headers.join(','),
        ...rows.map(r => [
            r.id,
            escapeCsv(r.name),
            escapeCsv(r.type),
            escapeCsv(r.area_name),
            escapeCsv(r.code),
            escapeCsv(r.phone),
            r.balance || 0,
            r.total_kasbon || 0,
            r.piutang || 0,
            r.total_paid || 0,
            r.total_usage || 0
        ].join(','))
    ].join('\r\n');

    // Menambahkan UTF-8 BOM agar terbaca sempurna di Microsoft Excel
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    const dateStr = new Date().toISOString().split('T')[0];
    link.setAttribute('href', url);
    link.setAttribute('download', `Laporan_Keuangan_Reseller_${dateStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    Swal.fire({
        icon: 'success',
        title: 'Berhasil Diekspor!',
        text: `${rows.length} data mitra reseller berhasil diunduh ke CSV.`,
        timer: 1600,
        showConfirmButton: false
    });
};

const printReport = () => {
    window.print();
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number || 0);
};

const formatWa = (phone) => {
    if (!phone) return '';
    return String(phone).replace(/^0/, '62').replace(/\D/g, '');
};

const getResellerName = (reseller) => {
    if (!reseller) return 'Unknown Reseller';
    return reseller.customer?.name || reseller.user?.name || ('Reseller #' + reseller.id);
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
    amount: '',
    expense_category_id: '',
    area_id: '',
    description: '',
    expense_date: getTodayDate(),
});

const submitExpense = () => {
    // Pastikan penanda kemitraan disertakan
    const descLower = expenseForm.description.toLowerCase();
    if (!descLower.includes('reseller') && !descLower.includes('mitra')) {
        expenseForm.description = expenseForm.description + ' [Beban Mitra]';
    }

    expenseForm.post(route('expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showExpenseModal.value = false;
            expenseForm.reset();
            expenseForm.expense_category_id = '';
            expenseForm.area_id = '';
            expenseForm.expense_date = getTodayDate();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dicatat!',
                text: 'Catatan beban operasional kemitraan berhasil disimpan.',
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

const deleteExpense = (id) => {
    Swal.fire({
        title: 'Hapus Beban Reseller?',
        text: 'Apakah Anda yakin ingin menghapus catatan beban kemitraan ini? Laba bersih reseller akan dikalkulasi ulang.',
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
                        text: 'Catatan beban kemitraan berhasil dihapus.',
                        timer: 1600,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    const errorMsg = Object.values(err)[0] || 'Gagal menghapus beban kemitraan.';
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
</script>
