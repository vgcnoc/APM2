<template>
    <AppLayout title="Laporan Keuangan Pelanggan" subtitle="Rekapitulasi pemasukan dan piutang pelanggan internet">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-8">
            
            <!-- Filter Periode & Area -->
            <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 bg-white p-4 rounded-3xl shadow-sm border border-gray-100 min-w-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="p-2.5 bg-sky-50 text-sky-600 rounded-xl shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900">Filter Laporan</h2>
                        <p class="text-xs text-gray-500">Sesuaikan periode bulan, tahun & wilayah</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap lg:flex-nowrap gap-2 w-full lg:w-auto items-center">
                    <!-- Area Filter -->
                    <select v-model="form.area_id" class="col-span-2 sm:col-span-1 block w-full sm:w-36 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-xs sm:text-sm font-medium focus:ring-sky-500 focus:border-sky-500">
                        <option value="">Semua Area</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                    <!-- Bulan Filter -->
                    <select v-model="form.month" class="block w-full sm:w-32 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-xs sm:text-sm font-medium focus:ring-sky-500 focus:border-sky-500">
                        <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                    </select>
                    <!-- Tahun Filter -->
                    <select v-model="form.year" class="block w-full sm:w-24 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-xs sm:text-sm font-medium focus:ring-sky-500 focus:border-sky-500">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button @click="applyFilter" class="col-span-2 sm:col-span-1 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all hover:shadow whitespace-nowrap">
                        Filter
                    </button>
                </div>
            </div>

            <!-- Summary Cards (Interactive & Linked to Table Tabs) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <!-- Card 1: Total Pemasukan -->
                <div @click="setActiveTab('pembayaran', true)" 
                     :class="['cursor-pointer transition-all duration-200 rounded-3xl p-5 sm:p-6 min-w-0 text-white shadow-lg relative overflow-hidden group select-none',
                              activeTab === 'pembayaran' ? 'ring-4 ring-sky-300 shadow-xl' : 'hover:-translate-y-0.5']"
                     style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-sky-100 text-xs font-bold uppercase tracking-wider truncate">Total Pemasukan Pelanggan</p>
                        <span class="text-[11px] bg-white/20 px-2 py-0.5 rounded-full font-semibold shrink-0">{{ ledger.length }} Trx</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl 2xl:text-3xl font-black mb-2 break-words leading-tight">Rp {{ formatRupiah(summary.pemasukan) }}</h3>
                    <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-sky-100/90 font-medium pt-2 border-t border-white/20">
                        <span>Bulan {{ months[props.month - 1] }} {{ props.year }}</span>
                        <span class="underline decoration-dashed group-hover:translate-x-1 transition-transform">Buka Tabel &rarr;</span>
                    </div>
                </div>

                <!-- Card 2: Piutang Berjalan (Aktif) -->
                <div @click="setActiveTab('piutang_aktif', true)" 
                     :class="['cursor-pointer transition-all duration-200 rounded-3xl p-5 sm:p-6 min-w-0 text-white shadow-lg relative overflow-hidden group select-none',
                              activeTab === 'piutang_aktif' ? 'ring-4 ring-amber-300 shadow-xl' : 'hover:-translate-y-0.5']"
                     style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-amber-100 text-xs font-bold uppercase tracking-wider truncate">Piutang Pelanggan Aktif</p>
                        <span class="text-[11px] bg-white/20 px-2 py-0.5 rounded-full font-semibold shrink-0">{{ activePiutangList.length }} Tagihan</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl 2xl:text-3xl font-black mb-2 break-words leading-tight">Rp {{ formatRupiah(summary.piutang) }}</h3>
                    <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-amber-100/90 font-medium pt-2 border-t border-white/20">
                        <span>Tunggakan pelanggan berjalan</span>
                        <span class="underline decoration-dashed group-hover:translate-x-1 transition-transform">Buka Tabel &rarr;</span>
                    </div>
                </div>

                <!-- Card 3: Piutang Pelanggan Stop Permanen -->
                <div @click="setActiveTab('piutang_terminated', true)" 
                     :class="['sm:col-span-2 xl:col-span-1 cursor-pointer transition-all duration-200 rounded-3xl p-5 sm:p-6 min-w-0 text-white shadow-lg relative overflow-hidden group select-none',
                              activeTab === 'piutang_terminated' ? 'ring-4 ring-rose-300 shadow-xl' : 'hover:-translate-y-0.5']"
                     style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-rose-100 text-xs font-bold uppercase tracking-wider truncate">Piutang Stop Permanen</p>
                        <span class="text-[11px] bg-white/20 px-2 py-0.5 rounded-full font-semibold shrink-0">{{ terminatedPiutangList.length }} Tagihan</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl 2xl:text-3xl font-black mb-2 break-words leading-tight">Rp {{ formatRupiah(summary.piutang_terminated || 0) }}</h3>
                    <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-rose-100/90 font-medium pt-2 border-t border-white/20">
                        <span>Pelanggan nonaktif / diputus</span>
                        <span class="underline decoration-dashed group-hover:translate-x-1 transition-transform">Buka Tabel &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- Grafik Tren -->
            <div class="bg-white p-4 sm:p-6 min-w-0 overflow-hidden rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100">
                <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-4 sm:mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Tren Pemasukan Pelanggan (6 Bulan Terakhir)</span>
                </h3>
                <div class="h-72 sm:h-80 w-full relative z-10 min-w-0">
                    <apexchart type="area" height="100%" :options="chartOptions" :series="chartSeries"></apexchart>
                </div>
            </div>

            <!-- Bagian Rincian Data (Tabs Sinkron dengan Card) -->
            <div id="report-table-section" class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden min-w-0 scroll-mt-6">
                <!-- Header Tabs & Controls -->
                <div class="p-4 sm:p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col gap-4">
                    <!-- Row 1: Title and Tab Buttons -->
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Rincian Data Laporan</span>
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Data rincian di bawah ini selaras 100% dengan nominal pada kartu summary di atas.</p>
                        </div>

                        <!-- Tab Buttons -->
                        <div class="flex items-center gap-1.5 p-1 bg-gray-200/60 rounded-2xl w-full sm:w-fit max-w-full overflow-x-auto overscroll-x-contain">
                            <button @click="setActiveTab('pembayaran')" 
                                    :class="['px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shrink-0',
                                             activeTab === 'pembayaran' ? 'bg-white text-sky-700 shadow-sm' : 'text-gray-600 hover:text-gray-900']">
                                <span>💵 Riwayat Pembayaran</span>
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', activeTab === 'pembayaran' ? 'bg-sky-100 text-sky-700' : 'bg-gray-300 text-gray-700']">
                                    {{ ledger.length }}
                                </span>
                            </button>
                            <button @click="setActiveTab('piutang_aktif')" 
                                    :class="['px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shrink-0',
                                             activeTab === 'piutang_aktif' ? 'bg-white text-amber-700 shadow-sm' : 'text-gray-600 hover:text-gray-900']">
                                <span>⏳ Tunggakan Aktif</span>
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', activeTab === 'piutang_aktif' ? 'bg-amber-100 text-amber-700' : 'bg-gray-300 text-gray-700']">
                                    {{ activePiutangList.length }}
                                </span>
                            </button>
                            <button @click="setActiveTab('piutang_terminated')" 
                                    :class="['px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shrink-0',
                                             activeTab === 'piutang_terminated' ? 'bg-white text-rose-700 shadow-sm' : 'text-gray-600 hover:text-gray-900']">
                                <span>⛔ Stop Permanen</span>
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', activeTab === 'piutang_terminated' ? 'bg-rose-100 text-rose-700' : 'bg-gray-300 text-gray-700']">
                                    {{ terminatedPiutangList.length }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Row 2: Search Bar & Per-Page Selector -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 border-t border-gray-200/60">
                        <!-- Search Input -->
                        <div class="relative flex-1 max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input 
                                v-model="tableSearch" 
                                type="text" 
                                :placeholder="searchPlaceholder"
                                class="block w-full pl-9 pr-8 py-2 text-xs sm:text-sm bg-white border border-gray-200 rounded-xl focus:ring-sky-500 focus:border-sky-500 text-gray-800 placeholder-gray-400 shadow-2xs transition-colors"
                            >
                            <button 
                                v-if="tableSearch" 
                                @click="tableSearch = ''" 
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600"
                                title="Hapus Pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- Per-Page Selector -->
                        <div class="flex items-center gap-2 self-end sm:self-auto text-xs text-gray-600 font-medium">
                            <span>Tampilkan:</span>
                            <select 
                                v-model.number="perPage" 
                                class="py-1.5 pl-2.5 pr-7 text-xs font-semibold bg-white border border-gray-200 rounded-xl focus:ring-sky-500 focus:border-sky-500 text-gray-700 shadow-2xs"
                            >
                                <option :value="10">10 baris</option>
                                <option :value="15">15 baris</option>
                                <option :value="25">25 baris</option>
                                <option :value="50">50 baris</option>
                                <option :value="100">100 baris</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- TAB 1: RIWAYAT PEMBAYARAN -->
                <div v-if="activeTab === 'pembayaran'" class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan / Kode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Area</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Invoice</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Metode & Penagih</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal Diterima</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="item in paginatedList" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(item.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900 font-bold">{{ item.customer }}</p>
                                    <p class="text-xs text-gray-400 font-mono">{{ item.customer_code }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    {{ item.area }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-mono font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded-md">
                                        {{ item.invoice_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900 capitalize font-medium">{{ item.method }}</p>
                                    <p class="text-xs text-gray-500">Oleh: {{ item.collector }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <p class="text-sm font-black text-emerald-600">+ Rp {{ formatRupiah(item.amount) }}</p>
                                </td>
                            </tr>
                            <tr v-if="paginatedList.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <p v-if="tableSearch" class="font-medium text-gray-700">Tidak ada riwayat pembayaran yang cocok dengan "<span class="text-sky-600 font-bold">{{ tableSearch }}</span>".</p>
                                    <p v-else class="font-medium">Belum ada pembayaran pelanggan terverifikasi pada periode/area ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- TAB 2: DAFTAR TUNGGAKAN AKTIF -->
                <div v-else-if="activeTab === 'piutang_aktif'" class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jatuh Tempo</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan / Kode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Area / No. HP</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Invoice</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total Tagihan</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Terbayar</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Sisa Tunggakan</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="item in paginatedList" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ item.due_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900 font-bold">{{ item.customer }}</p>
                                    <p class="text-xs text-gray-400 font-mono">{{ item.customer_code }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <p class="font-medium text-gray-900">{{ item.area }}</p>
                                    <p class="text-xs text-gray-400">{{ item.phone }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-mono font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md">
                                        {{ item.invoice_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-700">
                                    Rp {{ formatRupiah(item.amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-emerald-600">
                                    Rp {{ formatRupiah(item.paid) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-black text-amber-600">
                                    Rp {{ formatRupiah(item.remaining) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span :class="['px-2.5 py-1 text-[11px] font-bold rounded-lg uppercase tracking-wider',
                                                   item.status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800']">
                                        {{ item.status === 'partial' ? 'Sebagian' : 'Belum Bayar' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a :href="`/invoices?search=${encodeURIComponent(item.invoice_number)}`" 
                                       class="text-xs font-bold text-sky-600 hover:text-sky-800 bg-sky-50 hover:bg-sky-100 px-3 py-1.5 rounded-lg transition-colors">
                                        Buka Tagihan &rarr;
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="paginatedList.length === 0">
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <p v-if="tableSearch" class="font-medium text-gray-700">Tidak ada tunggakan yang cocok dengan "<span class="text-sky-600 font-bold">{{ tableSearch }}</span>".</p>
                                    <p v-else class="font-medium">Tidak ada tunggakan dari pelanggan aktif pada periode/area ini (Lunas semua).</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- TAB 3: PIUTANG PELANGGAN STOP PERMANEN -->
                <div v-else-if="activeTab === 'piutang_terminated'" class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Stop / Jatuh Tempo</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan / Kode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Area / No. HP</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Invoice</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Alasan Stop</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Sisa Piutang</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="item in paginatedList" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <p class="font-bold text-rose-600">{{ item.cancel_date }}</p>
                                    <p class="text-xs text-gray-400">Due: {{ item.due_date }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900 font-bold">{{ item.customer }}</p>
                                    <p class="text-xs text-gray-400 font-mono">{{ item.customer_code }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <p class="font-medium text-gray-900">{{ item.area }}</p>
                                    <p class="text-xs text-gray-400">{{ item.phone }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-mono font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md">
                                        {{ item.invoice_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 max-w-xs truncate">
                                    {{ item.cancel_reason || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-black text-rose-600">
                                    Rp {{ formatRupiah(item.remaining) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a :href="`/invoices?tab=stop_permanen&search=${encodeURIComponent(item.invoice_number)}`" 
                                       class="text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition-colors">
                                        Kelola di Stop Permanen &rarr;
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="paginatedList.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <p v-if="tableSearch" class="font-medium text-gray-700">Tidak ada data stop permanen yang cocok dengan "<span class="text-sky-600 font-bold">{{ tableSearch }}</span>".</p>
                                    <p v-else class="font-medium">Tidak ada tunggakan dari pelanggan stop permanen pada area ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bar -->
                <div v-if="filteredList.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-700">
                            Menampilkan <span class="font-bold text-gray-900">{{ fromIndex }}</span> - <span class="font-bold text-gray-900">{{ toIndex }}</span> dari <span class="font-bold text-gray-900">{{ filteredList.length }}</span> hasil
                            <span v-if="tableSearch" class="text-xs text-gray-500 font-normal ml-1">(difilter dari {{ currentRawList.length }} total data)</span>
                        </p>
                    </div>
                    <div v-if="totalPages > 1">
                        <nav class="relative z-0 inline-flex rounded-xl shadow-xs -space-x-px" aria-label="Pagination">
                            <template v-for="(link, i) in paginationLinks" :key="i">
                                <button
                                    v-if="!link.disabled"
                                    type="button"
                                    @click="changePage(link.page)"
                                    v-html="link.label"
                                    class="relative inline-flex items-center px-3.5 py-2 border text-sm font-bold transition-all duration-150 select-none cursor-pointer"
                                    :class="[
                                        link.active 
                                            ? 'z-10 bg-sky-600 border-sky-600 text-white shadow-xs' 
                                            : 'bg-white border-gray-300 text-gray-600 hover:bg-sky-50 hover:text-sky-600 hover:border-sky-300',
                                        { 'rounded-l-xl': i === 0, 'rounded-r-xl': i === paginationLinks.length - 1 }
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="relative inline-flex items-center px-3.5 py-2 border text-sm font-medium bg-gray-100/70 border-gray-200 text-gray-400 cursor-not-allowed select-none"
                                    :class="{ 'rounded-l-xl': i === 0, 'rounded-r-xl': i === paginationLinks.length - 1 }"
                                />
                            </template>
                        </nav>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    month: Number,
    year: Number,
    area_id: [Number, String],
    areas: Array,
    summary: Object,
    chartData: Array,
    ledger: Array,
    activePiutangList: Array,
    terminatedPiutangList: Array
});

// Active tab for table: 'pembayaran' | 'piutang_aktif' | 'piutang_terminated'
const activeTab = ref('pembayaran');

// Pagination & search state
const tableSearch = ref('');
const perPage = ref(15);
const currentPage = ref(1);

const setActiveTab = (tab, scroll = false) => {
    activeTab.value = tab;
    currentPage.value = 1;
    tableSearch.value = '';
    if (scroll) {
        const el = document.getElementById('report-table-section');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const searchPlaceholder = computed(() => {
    if (activeTab.value === 'pembayaran') return 'Cari nama, kode pelanggan, no. invoice, penagih, metode...';
    if (activeTab.value === 'piutang_aktif') return 'Cari nama, kode pelanggan, no. invoice, area, status...';
    if (activeTab.value === 'piutang_terminated') return 'Cari nama, no. invoice, alasan stop...';
    return 'Cari...';
});

// Current raw dataset for active tab
const currentRawList = computed(() => {
    if (activeTab.value === 'pembayaran') return props.ledger || [];
    if (activeTab.value === 'piutang_aktif') return props.activePiutangList || [];
    if (activeTab.value === 'piutang_terminated') return props.terminatedPiutangList || [];
    return [];
});

// Filtered dataset based on live tableSearch
const filteredList = computed(() => {
    const list = currentRawList.value;
    const query = (tableSearch.value || '').trim().toLowerCase();
    if (!query) return list;

    return list.filter(item => {
        const customer = (item.customer || '').toLowerCase();
        const customerCode = (item.customer_code || '').toLowerCase();
        const invoiceNumber = (item.invoice_number || '').toLowerCase();
        const area = (item.area || '').toLowerCase();

        if (activeTab.value === 'pembayaran') {
            const method = (item.method || '').toLowerCase();
            const collector = (item.collector || '').toLowerCase();
            return customer.includes(query) ||
                   customerCode.includes(query) ||
                   invoiceNumber.includes(query) ||
                   area.includes(query) ||
                   method.includes(query) ||
                   collector.includes(query);
        } else if (activeTab.value === 'piutang_aktif') {
            const phone = (item.phone || '').toLowerCase();
            const status = (item.status || '').toLowerCase();
            return customer.includes(query) ||
                   customerCode.includes(query) ||
                   invoiceNumber.includes(query) ||
                   area.includes(query) ||
                   phone.includes(query) ||
                   status.includes(query);
        } else if (activeTab.value === 'piutang_terminated') {
            const phone = (item.phone || '').toLowerCase();
            const reason = (item.cancel_reason || '').toLowerCase();
            return customer.includes(query) ||
                   customerCode.includes(query) ||
                   invoiceNumber.includes(query) ||
                   area.includes(query) ||
                   phone.includes(query) ||
                   reason.includes(query);
        }
        return false;
    });
});

// Total pages calculation
const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredList.value.length / perPage.value));
});

// Sliced paginated list
const paginatedList = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredList.value.slice(start, start + perPage.value);
});

// Counters for footer display
const fromIndex = computed(() => {
    if (filteredList.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const toIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredList.value.length);
});

// Watch search & perPage changes to reset to page 1
watch([tableSearch, perPage], () => {
    currentPage.value = 1;
});

// Pagination links generation
const paginationLinks = computed(() => {
    const total = totalPages.value;
    const cur = currentPage.value;
    const links = [];

    // Previous button
    links.push({
        label: '&laquo; Previous',
        page: cur > 1 ? cur - 1 : null,
        active: false,
        disabled: cur <= 1
    });

    if (total <= 7) {
        for (let p = 1; p <= total; p++) {
            links.push({
                label: String(p),
                page: p,
                active: p === cur,
                disabled: false
            });
        }
    } else {
        links.push({ label: '1', page: 1, active: cur === 1, disabled: false });

        if (cur > 3) {
            links.push({ label: '...', page: null, active: false, disabled: true });
        }

        const start = Math.max(2, cur - 1);
        const end = Math.min(total - 1, cur + 1);

        for (let p = start; p <= end; p++) {
            links.push({
                label: String(p),
                page: p,
                active: p === cur,
                disabled: false
            });
        }

        if (cur < total - 2) {
            links.push({ label: '...', page: null, active: false, disabled: true });
        }

        links.push({ label: String(total), page: total, active: cur === total, disabled: false });
    }

    // Next button
    links.push({
        label: 'Next &raquo;',
        page: cur < total ? cur + 1 : null,
        active: false,
        disabled: cur >= total
    });

    return links;
});

const changePage = (page) => {
    if (!page || page === currentPage.value) return;
    currentPage.value = page;
    const tableSection = document.getElementById('report-table-section');
    if (tableSection) {
        tableSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const currentYear = new Date().getFullYear();
const years = Array.from({length: 5}, (_, i) => currentYear - i);

const form = useForm({
    month: props.month,
    year: props.year,
    area_id: props.area_id || ''
});

const applyFilter = () => {
    form.get(route('customer-reports.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number || 0);
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
    colors: ['#0ea5e9'],
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
        categories: (props.chartData || []).map(d => d.month),
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
    },
    tooltip: {
        y: { formatter: (val) => 'Rp ' + formatRupiah(val) }
    }
}));

const chartSeries = computed(() => [
    {
        name: 'Omzet Pelanggan',
        data: (props.chartData || []).map(d => d.amount)
    }
]);
</script>
