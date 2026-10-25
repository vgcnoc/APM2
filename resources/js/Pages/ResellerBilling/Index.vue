<template>
    <AppLayout title="Penagihan Reseller" subtitle="Menu untuk Penagih menerima pembayaran kasbon deposit voucher reseller">
        <div class="max-w-7xl mx-auto space-y-6 pb-12 font-sans">
            
            <!-- Alert Messages -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ✅ {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ❌ {{ $page.props.flash.error }}
            </div>

            <!-- ── 6 SYNCHRONIZED STATS CARDS RESELLER ── -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
                
                <!-- Card 1: Belum Lunas (Klik untuk aktifkan tab Belum Lunas) -->
                <div 
                    @click="switchTab('unpaid')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'unpaid'
                            ? 'bg-amber-50/90 border-amber-300 ring-2 ring-amber-500 shadow-md'
                            : 'bg-white border-gray-200/80 hover:border-amber-200 hover:bg-amber-50/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                            Belum Lunas
                        </span>
                        <span v-if="activeTab === 'unpaid'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-amber-200/80 text-amber-900 border border-amber-300 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-gray-900 truncate">
                        Rp {{ formatRupiah(stats?.unpaid?.total || 0) }}
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-amber-700 font-medium">
                        <span>{{ stats?.unpaid?.count || 0 }} Tagihan</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 2: Bayar Sebagian (Klik untuk aktifkan tab Bayar Sebagian) -->
                <div 
                    @click="switchTab('partial')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'partial'
                            ? 'bg-sky-50/90 border-sky-300 ring-2 ring-sky-500 shadow-md'
                            : 'bg-white border-gray-200/80 hover:border-sky-200 hover:bg-sky-50/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-sky-800 flex items-center gap-1.5 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
                            Bayar Sebagian
                        </span>
                        <span v-if="activeTab === 'partial'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-sky-200/80 text-sky-900 border border-sky-300 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-sky-700 truncate">
                        Rp {{ formatRupiah(stats?.partial?.total || 0) }}
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-sky-700 font-medium">
                        <span>{{ stats?.partial?.count || 0 }} Tagihan</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 3: Janji Bayar (Klik untuk aktifkan tab Janji Bayar) -->
                <div 
                    @click="switchTab('promise')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'promise'
                            ? 'bg-purple-50/90 border-purple-300 ring-2 ring-purple-500 shadow-md'
                            : 'bg-white border-gray-200/80 hover:border-purple-200 hover:bg-purple-50/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-purple-800 flex items-center gap-1.5 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shrink-0"></span>
                            Janji Bayar
                        </span>
                        <span v-if="activeTab === 'promise'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-purple-200/80 text-purple-900 border border-purple-300 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-purple-700 truncate">
                        Rp {{ formatRupiah(stats?.promise?.total || 0) }}
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-purple-700 font-medium">
                        <span>{{ stats?.promise?.count || 0 }} Reseller</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 4: Jatuh Tempo (Klik untuk aktifkan tab Jatuh Tempo) -->
                <div 
                    @click="switchTab('overdue')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'overdue'
                            ? 'bg-rose-50/90 border-rose-300 ring-2 ring-rose-500 shadow-md'
                            : 'bg-white border-gray-200/80 hover:border-rose-200 hover:bg-rose-50/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-800 flex items-center gap-1.5 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                            Jatuh Tempo
                        </span>
                        <span v-if="activeTab === 'overdue'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-rose-200/80 text-rose-900 border border-rose-300 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-rose-700 truncate">
                        Rp {{ formatRupiah(stats?.overdue?.total || 0) }}
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-rose-700 font-medium">
                        <span>{{ stats?.overdue?.count || 0 }} Lewat Tempo</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 5: Sudah Lunas (Klik untuk aktifkan tab Sudah Lunas) -->
                <div 
                    @click="switchTab('paid')"
                    class="cursor-pointer rounded-2xl p-4 sm:p-5 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'paid'
                            ? 'bg-emerald-50/90 border-emerald-300 ring-2 ring-emerald-500 shadow-md'
                            : 'bg-white border-gray-200/80 hover:border-emerald-200 hover:bg-emerald-50/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                            Sudah Lunas
                        </span>
                        <span v-if="activeTab === 'paid'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-200/80 text-emerald-900 border border-emerald-300 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-700 truncate">
                        Rp {{ formatRupiah(stats?.paid?.total || 0) }}
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-emerald-700 font-medium">
                        <span>{{ stats?.paid?.count || 0 }} Lunas</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 6: Uang Diterima Hari Ini (Permanen / Real-time Today Metric) -->
                <div class="rounded-2xl p-4 sm:p-5 border bg-gradient-to-br from-indigo-50/90 via-blue-50/80 to-white border-indigo-200/80 shadow-xs">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-900 flex items-center gap-1.5 truncate">
                            <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Hari Ini
                        </span>
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200 shrink-0">
                            {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-indigo-900 truncate">
                        Rp {{ formatRupiah(stats?.today?.verified_amount || 0) }}
                    </div>
                    <div class="mt-2 flex flex-col gap-0.5 text-xs text-indigo-700 font-medium">
                        <div class="flex items-center justify-between">
                            <span>{{ stats?.today?.verified_count || 0 }} Setoran</span>
                            <span v-if="stats?.today?.pending_amount > 0" class="text-[9px] text-amber-800 font-bold bg-amber-100 px-1 py-0.2 rounded border border-amber-200">
                                +{{ formatRupiah(stats.today.pending_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── FILTER BAR RESELLER (Wilayah/Area, Tanggal, & Pencarian) ── -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-4 transition-all">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    
                    <!-- Filters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 flex-1">
                        <!-- Filter Area / Wilayah -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                Wilayah / Area Reseller
                            </label>
                            <select 
                                v-model="filterForm.area_id" 
                                @change="executeFilter"
                                class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors"
                            >
                                <option value="">Semua Wilayah / Area</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    {{ area.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Tanggal Mulai -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                {{ activeTab === 'paid' ? 'Tgl Bayar Dari' : (activeTab === 'promise' ? 'Tgl Janji Dari' : 'Jatuh Tempo Dari') }}
                            </label>
                            <input 
                                type="date" 
                                v-model="filterForm.start_date" 
                                @change="executeFilter"
                                class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                            />
                        </div>

                        <!-- Filter Tanggal Selesai -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                Sampai Dengan
                            </label>
                            <input 
                                type="date" 
                                v-model="filterForm.end_date" 
                                @change="executeFilter"
                                class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Search Box & Reset Button -->
                    <div class="flex items-end gap-2 lg:w-80">
                        <div class="flex-1">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                Pencarian Cepat
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="filterForm.search" 
                                    @input="handleSearchInput"
                                    placeholder="Cari reseller, telepon, invoice..."
                                    class="block w-full pl-8 text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors"
                                />
                            </div>
                        </div>

                        <button 
                            v-if="hasActiveFilters"
                            type="button" 
                            @click="resetFilters" 
                            class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-colors whitespace-nowrap shadow-2xs"
                            title="Reset semua filter"
                        >
                            Reset
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── TABLE SECTION RESELLER ── -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <!-- Table Header with Tabs -->
                <div class="p-5 sm:p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                            Daftar Tagihan Kasbon Reseller
                            <span 
                                class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                :class="{
                                    'bg-amber-100 text-amber-800': activeTab === 'unpaid',
                                    'bg-sky-100 text-sky-800': activeTab === 'partial',
                                    'bg-purple-100 text-purple-800': activeTab === 'promise',
                                    'bg-red-100 text-red-800': activeTab === 'overdue',
                                    'bg-emerald-100 text-emerald-800': activeTab === 'paid',
                                }"
                            >
                                {{ 
                                    activeTab === 'unpaid' ? 'Belum Lunas' : 
                                    activeTab === 'partial' ? 'Bayar Sebagian' :
                                    activeTab === 'promise' ? 'Janji Bayar' :
                                    activeTab === 'overdue' ? 'Jatuh Tempo' : 'Sudah Lunas' 
                                }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Menampilkan kasbon deposit voucher reseller sesuai tab dan filter yang dipilih
                        </p>
                    </div>
                    
                    <!-- Tabs Switcher -->
                    <div class="flex bg-gray-200/70 p-1 rounded-xl self-stretch lg:self-auto overflow-x-auto gap-1">
                        <button 
                            type="button"
                            @click="switchTab('unpaid')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                            :class="activeTab === 'unpaid' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            <span>Belum Lunas</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'unpaid' ? 'bg-amber-100 text-amber-800' : 'bg-gray-300/80 text-gray-700'">
                                {{ stats?.unpaid?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('partial')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                            :class="activeTab === 'partial' ? 'bg-white text-sky-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            <span>Bayar Sebagian</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'partial' ? 'bg-sky-100 text-sky-800' : 'bg-gray-300/80 text-gray-700'">
                                {{ stats?.partial?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('promise')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                            :class="activeTab === 'promise' ? 'bg-white text-purple-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            <span>Janji Bayar</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'promise' ? 'bg-purple-100 text-purple-800' : 'bg-gray-300/80 text-gray-700'">
                                {{ stats?.promise?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('overdue')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                            :class="activeTab === 'overdue' ? 'bg-red-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            <span>Jatuh Tempo</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'overdue' ? 'bg-white text-red-600' : 'bg-gray-300/80 text-gray-700'">
                                {{ stats?.overdue?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('paid')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                            :class="activeTab === 'paid' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            <span>Sudah Lunas</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-300/80 text-gray-700'">
                                {{ stats?.paid?.count || 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Tagihan</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reseller / Wilayah</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total / Sisa Kasbon</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <span v-if="activeTab === 'paid'">Tgl Bayar Lunas</span>
                                    <span v-else-if="activeTab === 'promise'">Tgl Janji Bayar</span>
                                    <span v-else>Jatuh Tempo</span>
                                </th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50/50 transition-colors">
                                <!-- No. Tagihan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ inv.invoice_number }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span v-if="inv.status === 'unpaid'" class="bg-red-50 text-red-700 px-2 py-0.5 rounded text-[11px] font-bold border border-red-200">Belum Lunas</span>
                                        <span v-else-if="inv.status === 'partial'" class="bg-yellow-50 text-yellow-700 px-2 py-0.5 rounded text-[11px] font-bold border border-yellow-200">Sebagian</span>
                                        <span v-else-if="inv.status === 'paid'" class="bg-green-50 text-green-700 px-2 py-0.5 rounded text-[11px] font-bold border border-green-200">Lunas</span>
                                        <span v-if="inv.period_label" class="text-[11px] text-gray-500 font-medium">({{ inv.period_label }})</span>
                                    </div>
                                </td>

                                <!-- Reseller / Wilayah -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ inv.customer?.name || 'Reseller' }}</div>
                                    <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                        <span>{{ inv.customer?.phone || '-' }}</span>
                                        <span v-if="inv.customer?.area_model?.name || inv.customer?.area" class="text-[10px] bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-medium border border-slate-200/60">
                                            📍 {{ inv.customer?.area_model?.name || inv.customer?.area }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Total / Sisa -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-500">Total: Rp {{ formatRupiah(inv.amount) }}</div>
                                    <div class="text-sm font-black text-indigo-600 mt-0.5">Sisa: Rp {{ formatRupiah(inv.remaining) }}</div>
                                    <div v-if="inv.pending_amount > 0" class="mt-1 flex items-center gap-1.5 bg-yellow-50 text-yellow-700 px-2 py-0.5 rounded-md text-[10px] font-bold border border-yellow-200 w-max">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Pending: Rp {{ formatRupiah(inv.pending_amount) }}
                                    </div>
                                </td>

                                <!-- Tanggal -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <template v-if="activeTab === 'paid'">
                                        <div class="font-medium text-gray-900">
                                            {{ inv.payments?.length ? new Date(Math.max(...inv.payments.map(p => new Date(p.payment_date)))).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                                        </div>
                                    </template>
                                    <template v-else-if="activeTab === 'promise'">
                                        <div class="font-bold text-purple-700 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ inv.promise_date ? new Date(inv.promise_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">
                                            Tempo: {{ new Date(inv.due_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="font-medium text-gray-900">{{ new Date(inv.due_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}</div>
                                        <div v-if="inv.status !== 'paid'" :class="getDueDuration(inv.due_date).class" class="text-[11px] font-bold mt-1">
                                            {{ getDueDuration(inv.due_date).text }}
                                        </div>
                                        <div v-if="inv.promise_date && inv.status !== 'paid'" class="mt-1 flex items-center gap-1 bg-purple-50 text-purple-800 px-2 py-0.5 rounded-md text-[10px] font-bold border border-purple-200 w-max">
                                            <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            Janji: {{ new Date(inv.promise_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                        </div>
                                    </template>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                        <!-- Tombol Terima Uang (Jika belum lunas & sisa > 0) -->
                                        <button 
                                            v-if="inv.status !== 'paid' && inv.effective_remaining > 0" 
                                            type="button"
                                            @click="openModal(inv)" 
                                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-xl shadow-xs transition-all hover:shadow text-xs font-bold ring-1 ring-indigo-700/50"
                                        >
                                            💰 Terima Uang
                                        </button>
                                        <span v-else-if="inv.status !== 'paid' && inv.effective_remaining <= 0" class="text-[11px] font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                            Menunggu Validasi
                                        </span>

                                        <!-- Tombol Janji Bayar (Jika belum lunas) -->
                                        <button 
                                            v-if="inv.status !== 'paid'" 
                                            type="button"
                                            @click="openPromiseModal(inv)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all border shadow-2xs"
                                            :class="inv.promise_date ? 'bg-amber-100 hover:bg-amber-200 text-amber-900 border-amber-300' : 'bg-amber-50 hover:bg-amber-100 text-amber-700 border-amber-200'"
                                            title="Atur / Ubah Janji Bayar"
                                        >
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>{{ inv.promise_date ? 'Ubah Janji' : 'Janji Bayar' }}</span>
                                        </button>

                                        <!-- Status Lunas jika Paid -->
                                        <span v-if="inv.status === 'paid'" class="text-green-700 font-bold text-xs bg-green-50 px-2.5 py-1 rounded-lg border border-green-200">
                                            ✓ Lunas
                                        </span>

                                        <!-- Tombol Rollback (Jika sudah lunas, bayar sebagian, atau punya transaksi pembayaran) -->
                                        <button 
                                            v-if="inv.status === 'paid' || inv.status === 'partial' || (inv.payments && inv.payments.length > 0)" 
                                            type="button"
                                            @click="rollbackInvoice(inv)" 
                                            class="inline-flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 px-2.5 py-1.5 rounded-xl border border-rose-200 text-xs font-bold transition-all shadow-2xs hover:border-rose-300"
                                            title="Batalkan pembayaran / Rollback status invoice"
                                        >
                                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            <span>Rollback</span>
                                        </button>

                                        <!-- Tombol Hapus Tagihan Kasbon -->
                                        <button 
                                            type="button"
                                            @click="deleteInvoice(inv)" 
                                            class="inline-flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-800 px-2.5 py-1.5 rounded-xl border border-rose-200 hover:border-rose-300 text-xs font-bold transition-all shadow-2xs cursor-pointer"
                                            title="Hapus tagihan kasbon ini"
                                        >
                                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!invoices.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="font-medium text-gray-600">Tidak ada kasbon reseller yang cocok dengan filter</p>
                                    <p class="text-xs text-gray-400 mt-1">Coba ubah tanggal, pilihan wilayah, atau kata kunci pencarian Anda</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="invoices.links && invoices.links.length > 3" class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500">
                        Menampilkan <span class="font-bold">{{ invoices.from || 0 }}</span> - <span class="font-bold">{{ invoices.to || 0 }}</span> dari <span class="font-bold">{{ invoices.total || 0 }}</span> tagihan kasbon
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link 
                            v-for="(link, i) in invoices.links" 
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

        <!-- Collect Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-2">Penerimaan Uang Kasbon - {{ selectedInvoice.invoice_number }}</h3>
                        <div class="bg-indigo-50 border border-indigo-100 p-3 rounded-xl mb-4">
                            <p class="text-[11px] text-indigo-600 uppercase font-bold tracking-wider mb-1">Sisa Tagihan Bisa Diterima</p>
                            <p class="font-black text-2xl text-indigo-700">Rp {{ formatRupiah(selectedInvoice.effective_remaining) }}</p>
                            <p v-if="selectedInvoice.pending_amount > 0" class="text-xs text-yellow-600 mt-2 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Ada pembayaran Rp {{ formatRupiah(selectedInvoice.pending_amount) }} sedang menunggu validasi admin.
                            </p>
                        </div>
                        
                        <!-- History Area -->
                        <div v-if="selectedInvoice.payments && selectedInvoice.payments.length > 0" class="mb-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-xs font-bold text-gray-700 mb-2 uppercase tracking-wide">Riwayat Cicilan/Pembayaran</p>
                            <ul class="space-y-2">
                                <li v-for="pay in selectedInvoice.payments" :key="pay.id" class="flex justify-between items-center text-sm border-b border-gray-200 pb-1 last:border-0 last:pb-0">
                                    <div>
                                        <span class="font-semibold text-gray-800">Rp {{ formatRupiah(pay.amount) }}</span>
                                        <span class="text-xs text-gray-500 ml-2">({{ new Date(pay.payment_date).toLocaleDateString('id-ID') }})</span>
                                    </div>
                                    <span v-if="pay.status === 'verified'" class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded">Lunas</span>
                                    <span v-else-if="pay.status === 'pending'" class="text-xs font-bold text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded">Menunggu Val.</span>
                                </li>
                            </ul>
                        </div>
                        
                        <form @submit.prevent="submitCollection" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Penerimaan</label>
                                <input type="date" v-model="form.payment_date" class="mt-1 block w-full border-gray-300 rounded-xl" required>
                            </div>
                            <!-- Pilihan Jenis Pembayaran (Penuh vs Sebagian) -->
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-bold text-gray-600 mb-2">Tipe Pembayaran</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button 
                                        type="button" 
                                        @click="setPaymentType('full')"
                                        class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer"
                                        :class="paymentType === 'full' 
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-200' 
                                            : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Bayar Lunas (Penuh)</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="setPaymentType('partial')"
                                        class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer"
                                        :class="paymentType === 'partial' 
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-200' 
                                            : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span>Bayar Sebagian (Cicil)</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-bold text-gray-600 mb-1">
                                    Nominal Diterima (Rp) <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    v-model="form.amount" 
                                    min="1000"
                                    :max="selectedInvoice.effective_remaining"
                                    class="block w-full border-gray-200 bg-gray-50 focus:bg-white rounded-xl font-bold text-lg px-4 py-2.5" 
                                    required 
                                />

                                <!-- Kalkulasi Sisa untuk Pembayaran Sebagian -->
                                <div v-if="paymentType === 'partial'" class="mt-2.5 p-3 bg-amber-50/80 border border-amber-200 rounded-xl space-y-1 text-xs">
                                    <div class="flex justify-between text-gray-600">
                                        <span>Total Sisa Saat Ini:</span>
                                        <span class="font-bold font-mono">Rp {{ formatRupiah(selectedInvoice.effective_remaining) }}</span>
                                    </div>
                                    <div class="flex justify-between text-indigo-700">
                                        <span>Nominal Dibayar Sekarang:</span>
                                        <span class="font-bold font-mono">- Rp {{ formatRupiah(form.amount || 0) }}</span>
                                    </div>
                                    <div class="border-t border-amber-200 pt-1 flex justify-between font-bold" :class="remainingAfterPartial > 0 ? 'text-amber-800' : 'text-green-700'">
                                        <span>Sisa Tagihan Berikutnya:</span>
                                        <span class="font-mono">Rp {{ formatRupiah(Math.max(0, remainingAfterPartial)) }}</span>
                                    </div>
                                    <p v-if="remainingAfterPartial > 0" class="text-[10px] text-amber-700 mt-0.5">
                                        ℹ️ Tagihan ini akan berstatus <strong>Bayar Sebagian (Partial)</strong> hingga sisa Rp {{ formatRupiah(remainingAfterPartial) }} dilunasi.
                                    </p>
                                    <p v-else class="text-[10px] text-green-700 mt-0.5 font-bold">
                                        ✓ Nominal ini akan melunasi seluruh tagihan.
                                    </p>
                                </div>
                                <div v-else class="flex justify-between items-center mt-1.5 text-xs text-gray-500">
                                    <span>Pembayaran penuh akan melunasi seluruh sisa tagihan.</span>
                                    <button type="button" @click="form.amount = selectedInvoice.effective_remaining" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2 py-0.5 rounded cursor-pointer">Isi Penuh</button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Metode Bayar</label>
                                <select v-model="form.payment_method" class="mt-1 block w-full border-gray-300 rounded-xl" required>
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="transfer">Transfer Bank</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Catatan</label>
                                <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-xl"></textarea>
                            </div>
                            <div class="flex items-start bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                                <div class="flex items-center h-5">
                                    <input id="direct" v-model="form.is_direct_payment" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="direct" class="font-medium text-indigo-900">Pembayaran Langsung di Kantor</label>
                                    <p class="text-indigo-700 text-xs mt-0.5">Mencentang ini akan langsung melunaskan kasbon (bypass verifikasi admin). Gunakan jika uang sudah ada di kasir.</p>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="submitCollection" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Catat Penerimaan
                        </button>
                        <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── JANJI BAYAR MODAL RESELLER ── -->
        <Teleport to="body">
            <div v-if="showPromiseModal" class="fixed inset-0 z-100 flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="closePromiseModal"></div>

                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-md relative z-10 animate-fade-in-up border border-gray-100">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-amber-50/60">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                                📅
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Atur Janji Bayar Kasbon</h3>
                                <p class="text-xs font-semibold text-gray-500 mt-0.5">
                                    {{ promiseInvoice?.customer?.name }} • {{ promiseInvoice?.invoice_number }}
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="closePromiseModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPromise">
                        <div class="p-6 space-y-4">
                            <div class="p-3 bg-indigo-50/60 border border-indigo-100 rounded-2xl flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-600">Sisa Tagihan Kasbon:</span>
                                <span class="text-sm font-black text-indigo-700">Rp {{ formatRupiah(promiseInvoice?.remaining) }}</span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                                    Tanggal Janji Bayar <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    v-model="promiseForm.promise_date" 
                                    required 
                                    :min="new Date().toISOString().split('T')[0]" 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                                    Catatan / Keterangan (Opsional)
                                </label>
                                <textarea 
                                    v-model="promiseForm.notes" 
                                    rows="2" 
                                    placeholder="Contoh: Reseller janji setoran setelah voucher habis tgl 20..." 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                ></textarea>
                            </div>

                            <p class="text-[11px] text-gray-500 leading-relaxed bg-amber-50/40 p-3 rounded-xl border border-amber-100/60">
                                ℹ️ Tagihan ini akan ditandai dengan tanggal janji bayar dan otomatis tersinkronisasi ke daftar Invoice Pelanggan/Reseller.
                            </p>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between gap-2">
                            <div>
                                <button 
                                    v-if="promiseInvoice?.promise_date"
                                    type="button" 
                                    @click="removePromise" 
                                    class="px-3.5 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition-colors border border-rose-200"
                                >
                                    Hapus Janji
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="closePromiseModal" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-xl transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="promiseForm.processing" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-amber-200 flex items-center gap-1.5">
                                    {{ promiseForm.processing ? 'Menyimpan...' : 'Simpan Janji Bayar' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    invoices: {
        type: Object,
        default: () => ({ data: [] }),
    },
    activeTab: {
        type: String,
        default: 'unpaid',
    },
    areas: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            unpaid: { total: 0, count: 0 },
            partial: { total: 0, count: 0 },
            promise: { total: 0, count: 0 },
            overdue: { total: 0, count: 0 },
            paid: { total: 0, count: 0 },
            today: { verified_amount: 0, verified_count: 0, pending_amount: 0, total_amount: 0 },
        }),
    },
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number || 0);
};

// ── Filter State ──
const filterForm = reactive({
    tab: props.filters?.tab || props.activeTab || 'unpaid',
    area_id: props.filters?.area_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
});

const hasActiveFilters = computed(() => {
    return !!(filterForm.area_id || filterForm.start_date || filterForm.end_date || filterForm.search);
});

const executeFilter = () => {
    router.get(route('reseller-billing.index'), {
        tab: filterForm.tab,
        area_id: filterForm.area_id || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
        search: filterForm.search || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const switchTab = (tab) => {
    filterForm.tab = tab;
    executeFilter();
};

const handleSearchInput = debounce(() => {
    executeFilter();
}, 400);

const resetFilters = () => {
    filterForm.area_id = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.search = '';
    executeFilter();
};

// ── Modal State ──
const showModal = ref(false);
const selectedInvoice = ref(null);

const paymentType = ref('full');

const form = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    notes: '',
    is_direct_payment: false
});

const setPaymentType = (type) => {
    paymentType.value = type;
    if (type === 'full') {
        form.amount = selectedInvoice.value ? selectedInvoice.value.effective_remaining : '';
    } else {
        if (selectedInvoice.value && (form.amount >= selectedInvoice.value.effective_remaining || !form.amount)) {
            form.amount = Math.max(1000, Math.floor(selectedInvoice.value.effective_remaining / 2));
        }
    }
};

const remainingAfterPartial = computed(() => {
    const rem = Number(selectedInvoice.value?.effective_remaining || 0);
    const amt = Number(form.amount || 0);
    return Math.max(0, rem - amt);
});

const openModal = (inv) => {
    selectedInvoice.value = inv;
    paymentType.value = 'full';
    form.amount = inv.effective_remaining;
    form.notes = 'Terima dana kasbon reseller';
    showModal.value = true;
};

const submitCollection = () => {
    form.post(route('reseller-billing.collect', selectedInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        }
    });
};

// ── Rollback Action ──
const rollbackInvoice = (inv) => {
    if (confirm(`Yakin ingin melakukan rollback (membatalkan pembayaran) untuk kasbon reseller #${inv.invoice_number}? Status tagihan akan dikembalikan menjadi belum lunas dan riwayat pembayaran terkait akan dibatalkan.`)) {
        router.post(`/invoices/${inv.id}/rollback`, {}, {
            preserveScroll: true,
        });
    }
};

// ── Delete Invoice Kasbon Action ──
const deleteInvoice = (inv) => {
    const customerName = inv.customer?.name || 'Reseller';
    const amountStr = formatRupiah(inv.amount);
    
    Swal.fire({
        title: 'Hapus Tagihan Kasbon?',
        html: `
            <div class="text-left text-xs text-gray-600 space-y-3 mt-2">
                <p>Apakah Anda yakin ingin menghapus tagihan kasbon <strong>#${inv.invoice_number}</strong>?</p>
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs space-y-1">
                    <div><span class="text-gray-500 font-semibold">Reseller:</span> <strong class="text-gray-900">${customerName}</strong></div>
                    <div><span class="text-gray-500 font-semibold">Total Tagihan:</span> <span class="font-bold font-mono text-gray-900">Rp ${amountStr}</span></div>
                    <div><span class="text-gray-500 font-semibold">Sisa Tagihan:</span> <span class="font-bold font-mono text-indigo-600">Rp ${formatRupiah(inv.remaining)}</span></div>
                </div>
                <div class="p-3 rounded-xl text-xs bg-amber-50 text-amber-900 border border-amber-200">
                    <label class="flex items-start gap-2.5 cursor-pointer font-medium">
                        <input type="checkbox" id="swal-adjust-balance-kasbon" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                        <span>
                            <strong>Tarik Kembali Saldo Reseller</strong><br>
                            <span class="text-[11px] opacity-90">Kurangi saldo dompet reseller sebesar Rp ${amountStr} dan batalkan pengisian saldonya (centang jika kasbon dibatalkan total).</span>
                        </span>
                    </label>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Tagihan',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const checkbox = document.getElementById('swal-adjust-balance-kasbon');
            return {
                adjust_balance: checkbox ? checkbox.checked : false
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const adjust = result.value?.adjust_balance ?? false;
            router.delete(route('reseller-billing.destroy', inv.id), {
                data: { adjust_balance: adjust },
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihapus!',
                        text: `Tagihan kasbon #${inv.invoice_number} berhasil dihapus.` + (adjust ? ' Saldo dompet reseller juga telah disesuaikan.' : ''),
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    const msg = err?.error || Object.values(err || {}).flat().join('<br>') || 'Gagal menghapus tagihan kasbon.';
                    Swal.fire('Gagal Menghapus', msg, 'error');
                }
            });
        }
    });
};

// ── Janji Bayar State & Handlers ──
const showPromiseModal = ref(false);
const promiseInvoice = ref(null);
const promiseForm = useForm({
    promise_date: '',
    notes: '',
});

const openPromiseModal = (inv) => {
    promiseInvoice.value = inv;
    promiseForm.promise_date = inv.promise_date ? String(inv.promise_date).split('T')[0] : '';
    promiseForm.notes = inv.notes || '';
    showPromiseModal.value = true;
};

const closePromiseModal = () => {
    showPromiseModal.value = false;
    promiseInvoice.value = null;
    promiseForm.reset();
};

const submitPromise = () => {
    if (!promiseInvoice.value) return;
    promiseForm.post(`/invoices/${promiseInvoice.value.id}/promise`, {
        preserveScroll: true,
        onSuccess: () => {
            closePromiseModal();
        }
    });
};

const removePromise = () => {
    if (!promiseInvoice.value) return;
    if (confirm(`Hapus tanggal janji bayar untuk kasbon #${promiseInvoice.value.invoice_number}?`)) {
        router.post(`/invoices/${promiseInvoice.value.id}/promise`, {
            promise_date: null,
            notes: promiseInvoice.value.notes || null,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                closePromiseModal();
            }
        });
    }
};

const getDueDuration = (dueDateStr) => {
    const due = new Date(dueDateStr);
    const today = new Date();
    due.setHours(0,0,0,0);
    today.setHours(0,0,0,0);
    
    const diffTime = due - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
        return { text: `Lewat ${Math.abs(diffDays)} hari`, class: 'text-red-600 bg-red-50 px-2 py-0.5 rounded inline-block border border-red-200' };
    } else if (diffDays === 0) {
        return { text: 'Jatuh tempo hari ini', class: 'text-orange-600 bg-orange-50 px-2 py-0.5 rounded inline-block border border-orange-200' };
    } else {
        return { text: `Sisa ${diffDays} hari`, class: 'text-green-600 bg-green-50 px-2 py-0.5 rounded inline-block border border-green-200' };
    }
};
</script>
