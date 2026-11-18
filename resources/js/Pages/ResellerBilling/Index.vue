<template>
    <AppLayout title="Penagihan Reseller" subtitle="Menu untuk Penagih menerima pembayaran kasbon deposit voucher reseller">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-12 font-sans">
            
            <!-- Alert Messages -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-2xl text-sm font-medium shadow-xs">
                ✅ {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-2xl text-sm font-medium shadow-xs">
                ❌ {{ $page.props.flash.error }}
            </div>

            <!-- ── 6 SYNCHRONIZED STATS CARDS RESELLER ── -->
            <div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-6 gap-2.5 sm:gap-4">
                
                <!-- Card 1: Belum Lunas (Klik untuk aktifkan tab Belum Lunas) -->
                <div 
                    @click="switchTab('unpaid')"
                    class="cursor-pointer rounded-2xl p-3.5 sm:p-4 2xl:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'unpaid'
                            ? 'bg-amber-50/90 dark:bg-amber-950/50 border-amber-300 dark:border-amber-600 ring-2 ring-amber-500 shadow-md'
                            : 'bg-white dark:bg-slate-800 border-gray-200/80 dark:border-slate-700/80 hover:border-amber-200 dark:hover:border-amber-700 hover:bg-amber-50/20 dark:hover:bg-amber-950/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-1 mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-400 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                            <span class="truncate">Belum Lunas</span>
                        </span>
                        <span v-if="activeTab === 'unpaid'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-amber-200/80 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-sm sm:text-lg xl:text-xl font-black text-gray-900 dark:text-white break-words leading-tight">
                        Rp {{ formatRupiah(stats?.unpaid?.total || 0) }}
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-1 text-[11px] sm:text-xs text-amber-700 dark:text-amber-400 font-medium">
                        <span>{{ stats?.unpaid?.count || 0 }} Tagihan</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 2: Bayar Sebagian (Klik untuk aktifkan tab Bayar Sebagian) -->
                <div 
                    @click="switchTab('partial')"
                    class="cursor-pointer rounded-2xl p-3.5 sm:p-4 2xl:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'partial'
                            ? 'bg-sky-50/90 dark:bg-sky-950/50 border-sky-300 dark:border-sky-600 ring-2 ring-sky-500 shadow-md'
                            : 'bg-white dark:bg-slate-800 border-gray-200/80 dark:border-slate-700/80 hover:border-sky-200 dark:hover:border-sky-700 hover:bg-sky-50/20 dark:hover:bg-sky-950/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-1 mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-sky-800 dark:text-sky-400 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
                            <span class="truncate">Bayar Sebagian</span>
                        </span>
                        <span v-if="activeTab === 'partial'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-sky-200/80 dark:bg-sky-900/60 text-sky-900 dark:text-sky-200 border border-sky-300 dark:border-sky-700 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-sm sm:text-lg xl:text-xl font-black text-sky-700 dark:text-sky-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.partial?.total || 0) }}
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-1 text-[11px] sm:text-xs text-sky-700 dark:text-sky-400 font-medium">
                        <span>{{ stats?.partial?.count || 0 }} Tagihan</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 3: Janji Bayar (Klik untuk aktifkan tab Janji Bayar) -->
                <div 
                    @click="switchTab('promise')"
                    class="cursor-pointer rounded-2xl p-3.5 sm:p-4 2xl:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'promise'
                            ? 'bg-purple-50/90 dark:bg-purple-950/50 border-purple-300 dark:border-purple-600 ring-2 ring-purple-500 shadow-md'
                            : 'bg-white dark:bg-slate-800 border-gray-200/80 dark:border-slate-700/80 hover:border-purple-200 dark:hover:border-purple-700 hover:bg-purple-50/20 dark:hover:bg-purple-950/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-1 mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-purple-800 dark:text-purple-400 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shrink-0"></span>
                            <span class="truncate">Janji Bayar</span>
                        </span>
                        <span v-if="activeTab === 'promise'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-purple-200/80 dark:bg-purple-900/60 text-purple-900 dark:text-purple-200 border border-purple-300 dark:border-purple-700 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-sm sm:text-lg xl:text-xl font-black text-purple-700 dark:text-purple-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.promise?.total || 0) }}
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-1 text-[11px] sm:text-xs text-purple-700 dark:text-purple-400 font-medium">
                        <span>{{ stats?.promise?.count || 0 }} Reseller</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 4: Jatuh Tempo (Klik untuk aktifkan tab Jatuh Tempo) -->
                <div 
                    @click="switchTab('overdue')"
                    class="cursor-pointer rounded-2xl p-3.5 sm:p-4 2xl:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'overdue'
                            ? 'bg-rose-50/90 dark:bg-rose-950/50 border-rose-300 dark:border-rose-600 ring-2 ring-rose-500 shadow-md'
                            : 'bg-white dark:bg-slate-800 border-gray-200/80 dark:border-slate-700/80 hover:border-rose-200 dark:hover:border-rose-700 hover:bg-rose-50/20 dark:hover:bg-rose-950/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-1 mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-rose-800 dark:text-rose-400 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                            <span class="truncate">Jatuh Tempo</span>
                        </span>
                        <span v-if="activeTab === 'overdue'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-rose-200/80 dark:bg-rose-900/60 text-rose-900 dark:text-rose-200 border border-rose-300 dark:border-rose-700 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-sm sm:text-lg xl:text-xl font-black text-rose-700 dark:text-rose-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.overdue?.total || 0) }}
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-1 text-[11px] sm:text-xs text-rose-700 dark:text-rose-400 font-medium">
                        <span>{{ stats?.overdue?.count || 0 }} Lewat Tempo</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 5: Sudah Lunas (Klik untuk aktifkan tab Sudah Lunas) -->
                <div 
                    @click="switchTab('paid')"
                    class="cursor-pointer rounded-2xl p-3.5 sm:p-4 2xl:p-5 min-w-0 border transition-all duration-200 transform hover:-translate-y-0.5 select-none"
                    :class="[
                        activeTab === 'paid'
                            ? 'bg-emerald-50/90 dark:bg-emerald-950/50 border-emerald-300 dark:border-emerald-600 ring-2 ring-emerald-500 shadow-md'
                            : 'bg-white dark:bg-slate-800 border-gray-200/80 dark:border-slate-700/80 hover:border-emerald-200 dark:hover:border-emerald-700 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/20 shadow-xs'
                    ]"
                >
                    <div class="flex items-center justify-between gap-1 mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400 flex items-center gap-1.5 min-w-0 truncate">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="truncate">Sudah Lunas</span>
                        </span>
                        <span v-if="activeTab === 'paid'" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-200/80 dark:bg-emerald-900/60 text-emerald-900 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700 shrink-0">
                            Aktif
                        </span>
                    </div>
                    <div class="text-sm sm:text-lg xl:text-xl font-black text-emerald-700 dark:text-emerald-400 break-words leading-tight">
                        Rp {{ formatRupiah(stats?.paid?.total || 0) }}
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-1 text-[11px] sm:text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                        <span>{{ stats?.paid?.count || 0 }} Lunas</span>
                        <span class="text-[10px] font-bold underline">&rarr;</span>
                    </div>
                </div>

                <!-- Card 6: Uang Diterima Hari Ini (Permanen / Real-time Today Metric) -->
                <div class="rounded-2xl p-3.5 sm:p-4 2xl:p-5 min-w-0 border bg-gradient-to-br from-indigo-50/90 via-blue-50/80 to-white dark:from-slate-800 dark:via-slate-800/90 dark:to-indigo-950/40 border-indigo-200/80 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between gap-1 mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5 min-w-0 truncate">
                            <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="truncate">Hari Ini</span>
                        </span>
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shrink-0">
                            {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                        </span>
                    </div>
                    <div class="text-sm sm:text-lg xl:text-xl font-black text-indigo-900 dark:text-white break-words leading-tight">
                        Rp {{ formatRupiah(stats?.today?.verified_amount || 0) }}
                    </div>
                    <div class="mt-2 flex flex-col gap-0.5 text-[11px] sm:text-xs text-indigo-700 dark:text-indigo-400 font-medium">
                        <div class="flex flex-wrap items-center justify-between gap-1">
                            <span>{{ stats?.today?.verified_count || 0 }} Setoran</span>
                            <span v-if="stats?.today?.pending_amount > 0" class="text-[9px] text-amber-800 dark:text-amber-300 font-bold bg-amber-100 dark:bg-amber-950/60 px-1 py-0.2 rounded border border-amber-200 dark:border-amber-800">
                                +{{ formatRupiah(stats.today.pending_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── FILTER BAR RESELLER (Wilayah/Area, Tanggal, & Pencarian) ── -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xs border border-gray-200/80 dark:border-slate-700 p-4 transition-all min-w-0">
                <div class="flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-3">
                    
                    <!-- Filters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 flex-1 min-w-0">
                        <!-- Filter Area / Wilayah -->
                        <div class="min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Wilayah / Area Reseller
                            </label>
                            <select 
                                v-model="filterForm.area_id" 
                                @change="executeFilter"
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-900 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors cursor-pointer"
                            >
                                <option value="">Semua Wilayah / Area</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    {{ area.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Tanggal Mulai -->
                        <div class="min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                {{ activeTab === 'paid' ? 'Tgl Bayar Dari' : (activeTab === 'promise' ? 'Tgl Janji Dari' : 'Jatuh Tempo Dari') }}
                            </label>
                            <input 
                                type="date" 
                                v-model="filterForm.start_date" 
                                @change="executeFilter"
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-900 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                            />
                        </div>

                        <!-- Filter Tanggal Selesai -->
                        <div class="min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Sampai Dengan
                            </label>
                            <input 
                                type="date" 
                                v-model="filterForm.end_date" 
                                @change="executeFilter"
                                class="block w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-900 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Search Box & Reset Button -->
                    <div class="flex items-end gap-2 w-full xl:w-72 2xl:w-80 shrink-0">
                        <div class="flex-1 min-w-0">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-1">
                                Pencarian Cepat
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400 dark:text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="filterForm.search" 
                                    @input="handleSearchInput"
                                    placeholder="Cari reseller, telepon, invoice..."
                                    class="block w-full pl-8 text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-2 transition-colors"
                                />
                            </div>
                        </div>

                        <button 
                            v-if="hasActiveFilters"
                            type="button" 
                            @click="resetFilters" 
                            class="px-3.5 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition-colors whitespace-nowrap shadow-2xs shrink-0 cursor-pointer"
                            title="Reset semua filter"
                        >
                            Reset
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── TABLE SECTION RESELLER ── -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 dark:border-slate-700/80 overflow-hidden min-w-0 transition-colors">
                <!-- Table Header with Tabs -->
                <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-slate-700/80 bg-gray-50/50 dark:bg-slate-850 flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex flex-wrap items-center gap-2">
                            <span>Daftar Tagihan Kasbon Reseller</span>
                            <span 
                                class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                :class="{
                                    'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800': activeTab === 'unpaid',
                                    'bg-sky-100 dark:bg-sky-950/60 text-sky-800 dark:text-sky-300 border border-sky-200 dark:border-sky-800': activeTab === 'partial',
                                    'bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800': activeTab === 'promise',
                                    'bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800': activeTab === 'overdue',
                                    'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800': activeTab === 'paid',
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
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Menampilkan kasbon deposit voucher reseller sesuai tab dan filter yang dipilih
                        </p>
                    </div>
                    
                    <!-- Tabs Switcher -->
                    <div class="flex bg-gray-200/70 dark:bg-slate-700/70 p-1 rounded-xl w-full xl:w-auto max-w-full overflow-x-auto overscroll-x-contain gap-1">
                        <button 
                            type="button"
                            @click="switchTab('unpaid')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap shrink-0 cursor-pointer"
                            :class="activeTab === 'unpaid' ? 'bg-white dark:bg-slate-800 text-indigo-700 dark:text-indigo-400 shadow-xs' : 'text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white'">
                            <span>Belum Lunas</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'unpaid' ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300' : 'bg-gray-300/80 dark:bg-slate-600 text-gray-700 dark:text-slate-300'">
                                {{ stats?.unpaid?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('partial')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap shrink-0 cursor-pointer"
                            :class="activeTab === 'partial' ? 'bg-white dark:bg-slate-800 text-sky-700 dark:text-sky-400 shadow-xs' : 'text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white'">
                            <span>Bayar Sebagian</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'partial' ? 'bg-sky-100 dark:bg-sky-950/60 text-sky-800 dark:text-sky-300' : 'bg-gray-300/80 dark:bg-slate-600 text-gray-700 dark:text-slate-300'">
                                {{ stats?.partial?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('promise')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap shrink-0 cursor-pointer"
                            :class="activeTab === 'promise' ? 'bg-white dark:bg-slate-800 text-purple-700 dark:text-purple-400 shadow-xs' : 'text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white'">
                            <span>Janji Bayar</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'promise' ? 'bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300' : 'bg-gray-300/80 dark:bg-slate-600 text-gray-700 dark:text-slate-300'">
                                {{ stats?.promise?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('overdue')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap shrink-0 cursor-pointer"
                            :class="activeTab === 'overdue' ? 'bg-rose-500 text-white shadow-xs' : 'text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white'">
                            <span>Jatuh Tempo</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'overdue' ? 'bg-white text-rose-600' : 'bg-gray-300/80 dark:bg-slate-600 text-gray-700 dark:text-slate-300'">
                                {{ stats?.overdue?.count || 0 }}
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="switchTab('paid')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap shrink-0 cursor-pointer"
                            :class="activeTab === 'paid' ? 'bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 shadow-xs' : 'text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white'">
                            <span>Sudah Lunas</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'paid' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'bg-gray-300/80 dark:bg-slate-600 text-gray-700 dark:text-slate-300'">
                                {{ stats?.paid?.count || 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50/80 dark:bg-slate-900/60">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">No. Tagihan</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Reseller / Wilayah</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total / Sisa Kasbon</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    <span v-if="activeTab === 'paid'">Tgl Bayar Lunas</span>
                                    <span v-else-if="activeTab === 'promise'">Tgl Janji Bayar</span>
                                    <span v-else>Jatuh Tempo</span>
                                </th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-black text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-100 dark:divide-slate-700/60">
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-indigo-50/30 dark:hover:bg-slate-700/40 transition-colors">
                                <!-- No. Tagihan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ inv.invoice_number }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        <span v-if="inv.status === 'unpaid'" class="bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 px-2 py-0.5 rounded text-[11px] font-bold border border-rose-200 dark:border-rose-800">Belum Lunas</span>
                                        <span v-else-if="inv.status === 'partial'" class="bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded text-[11px] font-bold border border-amber-200 dark:border-amber-800">Sebagian</span>
                                        <span v-else-if="inv.status === 'paid'" class="bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 rounded text-[11px] font-bold border border-emerald-200 dark:border-emerald-800">Lunas</span>
                                        <span v-if="inv.period_label" class="text-[11px] text-gray-500 dark:text-slate-400 font-medium">({{ inv.period_label }})</span>
                                    </div>
                                </td>

                                <!-- Reseller / Wilayah -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                        <span>{{ inv.customer?.name || 'Reseller' }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span>{{ getCustomerPhone(inv) }}</span>
                                        <a 
                                            v-if="getCustomerPhone(inv) !== '-'" 
                                            :href="`https://wa.me/${formatWa(getCustomerPhone(inv))}?text=${encodeURIComponent(getWaInvoiceMessage(inv))}`" 
                                            target="_blank" 
                                            class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-0.5"
                                            title="Kirim pesan penagihan kasbon via WhatsApp"
                                        >
                                            <span>💬 WA</span>
                                        </a>
                                        <span v-if="inv.customer?.area_model?.name || inv.customer?.area" class="text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded font-medium border border-slate-200/60 dark:border-slate-600">
                                            📍 {{ inv.customer?.area_model?.name || inv.customer?.area }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Total / Sisa -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-500 dark:text-slate-400">Total: Rp {{ formatRupiah(inv.amount) }}</div>
                                    <div class="text-sm font-black text-indigo-600 dark:text-indigo-400 mt-0.5">Sisa: Rp {{ formatRupiah(inv.remaining) }}</div>
                                    <div v-if="inv.pending_amount > 0" class="mt-1 flex items-center gap-1.5 bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded-md text-[10px] font-bold border border-amber-200 dark:border-amber-800 w-max">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Pending: Rp {{ formatRupiah(inv.pending_amount) }}
                                    </div>
                                </td>

                                <!-- Tanggal -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                    <template v-if="activeTab === 'paid'">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ inv.payments?.length ? new Date(Math.max(...inv.payments.map(p => new Date(p.payment_date)))).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                                        </div>
                                    </template>
                                    <template v-else-if="activeTab === 'promise'">
                                        <div class="font-bold text-purple-700 dark:text-purple-400 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ inv.promise_date ? new Date(inv.promise_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">
                                            Tempo: {{ new Date(inv.due_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ new Date(inv.due_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}</div>
                                        <div v-if="inv.status !== 'paid'" :class="getDueDuration(inv.due_date).class" class="text-[11px] font-bold mt-1">
                                            {{ getDueDuration(inv.due_date).text }}
                                        </div>
                                        <div v-if="inv.promise_date && inv.status !== 'paid'" class="mt-1 flex items-center gap-1 bg-purple-50 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 px-2 py-0.5 rounded-md text-[10px] font-bold border border-purple-200 dark:border-purple-800 w-max">
                                            <svg class="w-3 h-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
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
                                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-xl shadow-xs transition-all hover:shadow text-xs font-bold ring-1 ring-indigo-700/50 cursor-pointer active:scale-95"
                                        >
                                            💰 Terima Uang
                                        </button>
                                        <span v-else-if="inv.status !== 'paid' && inv.effective_remaining <= 0" class="text-[11px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2.5 py-1 rounded-lg border border-amber-200 dark:border-amber-800">
                                            Menunggu Validasi
                                        </span>

                                        <!-- Tombol Janji Bayar (Jika belum lunas) -->
                                        <button 
                                            v-if="inv.status !== 'paid'" 
                                            type="button"
                                            @click="openPromiseModal(inv)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all border shadow-2xs cursor-pointer"
                                            :class="inv.promise_date ? 'bg-amber-100 dark:bg-amber-950/60 hover:bg-amber-200 dark:hover:bg-amber-900/60 text-amber-900 dark:text-amber-200 border-amber-300 dark:border-amber-700' : 'bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800'"
                                            title="Atur / Ubah Janji Bayar"
                                        >
                                            <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>{{ inv.promise_date ? 'Ubah Janji' : 'Janji Bayar' }}</span>
                                        </button>

                                        <!-- Status Lunas jika Paid -->
                                        <span v-if="inv.status === 'paid'" class="text-emerald-700 dark:text-emerald-300 font-bold text-xs bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                            ✓ Lunas
                                        </span>

                                        <!-- Tombol Rollback (Jika sudah lunas, bayar sebagian, atau punya transaksi pembayaran) -->
                                        <button 
                                            v-if="inv.status === 'paid' || inv.status === 'partial' || (inv.payments && inv.payments.length > 0)" 
                                            type="button"
                                            @click="rollbackInvoice(inv)" 
                                            class="inline-flex items-center gap-1 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 px-2.5 py-1.5 rounded-xl border border-rose-200 dark:border-rose-800 text-xs font-bold transition-all shadow-2xs cursor-pointer"
                                            title="Batalkan pembayaran / Rollback status invoice"
                                        >
                                            <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            <span>Rollback</span>
                                        </button>

                                        <!-- Tombol Hapus Tagihan Kasbon -->
                                        <button 
                                            type="button"
                                            @click="deleteInvoice(inv)" 
                                            class="inline-flex items-center gap-1 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 px-2.5 py-1.5 rounded-xl border border-rose-200 dark:border-rose-800 text-xs font-bold transition-all shadow-2xs cursor-pointer"
                                            title="Hapus tagihan kasbon ini"
                                        >
                                            <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!invoices.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                    <svg class="w-10 h-10 text-gray-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="font-medium text-gray-600 dark:text-slate-300">Tidak ada kasbon reseller yang cocok dengan filter</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Coba ubah tanggal, pilihan wilayah, atau kata kunci pencarian Anda</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="invoices.links && invoices.links.length > 3" class="px-6 py-4 bg-gray-50/50 dark:bg-slate-800 border-t border-gray-100 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 dark:text-slate-400">
                        Menampilkan <span class="font-bold text-gray-800 dark:text-white">{{ invoices.from || 0 }}</span> - <span class="font-bold text-gray-800 dark:text-white">{{ invoices.to || 0 }}</span> dari <span class="font-bold text-gray-800 dark:text-white">{{ invoices.total || 0 }}</span> tagihan kasbon
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link 
                            v-for="(link, i) in invoices.links" 
                            :key="i" 
                            :href="link.url || '#'" 
                            v-html="link.label"
                            preserve-scroll
                            preserve-state
                            class="px-3 py-1.5 text-xs rounded-lg border transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-600 border-gray-200 dark:border-slate-600',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>

            </div>
        </div>

        <!-- Collect Modal -->
        <div v-if="showModal && selectedInvoice" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
            <div class="relative z-10 bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg w-full max-h-[90vh] flex flex-col border border-gray-100 dark:border-slate-700">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-5 sm:px-6 py-4 text-white flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold leading-tight">Penerimaan Uang Kasbon</h3>
                        <p class="text-xs text-indigo-100 mt-0.5">Invoice #{{ selectedInvoice.invoice_number }} &middot; {{ selectedInvoice.customer?.name }}</p>
                    </div>
                    <button type="button" @click="showModal = false" class="text-white/80 hover:text-white p-1 rounded-xl hover:bg-white/10 transition shrink-0 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-4 sm:p-6 overflow-y-auto flex-1 space-y-4">
                    <div class="bg-indigo-50/70 dark:bg-slate-900/60 border border-indigo-100 dark:border-slate-700 p-3.5 rounded-2xl">
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 uppercase font-bold tracking-wider mb-1">Sisa Tagihan Kasbon Bisa Diterima</p>
                        <p class="font-black text-xl sm:text-2xl text-indigo-950 dark:text-white break-words">Rp {{ formatRupiah(selectedInvoice.effective_remaining) }}</p>
                        <p v-if="selectedInvoice.pending_amount > 0" class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-start gap-1">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Ada pembayaran Rp {{ formatRupiah(selectedInvoice.pending_amount) }} sedang menunggu validasi admin.</span>
                        </p>
                    </div>
                    
                    <!-- History Area -->
                    <div v-if="selectedInvoice.payments && selectedInvoice.payments.length > 0" class="bg-gray-50 dark:bg-slate-900/60 p-3.5 rounded-2xl border border-gray-100 dark:border-slate-700">
                        <p class="text-xs font-bold text-gray-700 dark:text-slate-300 mb-2 uppercase tracking-wide">Riwayat Cicilan / Pembayaran Kasbon</p>
                        <ul class="space-y-2">
                            <li v-for="pay in selectedInvoice.payments" :key="pay.id" class="flex flex-wrap justify-between items-center gap-1 text-sm border-b border-gray-200 dark:border-slate-700 pb-1.5 last:border-0 last:pb-0">
                                <div>
                                    <span class="font-semibold text-gray-800 dark:text-white">Rp {{ formatRupiah(pay.amount) }}</span>
                                    <span class="text-xs text-gray-500 dark:text-slate-400 ml-2">({{ new Date(pay.payment_date).toLocaleDateString('id-ID') }})</span>
                                </div>
                                <span v-if="pay.status === 'verified'" class="text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">Lunas</span>
                                <span v-else-if="pay.status === 'pending'" class="text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">Menunggu Validasi</span>
                            </li>
                        </ul>
                    </div>
                    
                    <form @submit.prevent="submitCollection" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-slate-300 mb-1">Tanggal Penerimaan</label>
                            <input type="date" v-model="form.payment_date" class="block w-full border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white rounded-xl text-xs py-2 px-3" required>
                        </div>

                        <!-- Pilihan Jenis Pembayaran (Penuh vs Sebagian) -->
                        <div>
                            <label class="block text-[11px] uppercase tracking-wider font-bold text-gray-600 dark:text-slate-400 mb-2">Tipe Pembayaran</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <button 
                                    type="button" 
                                    @click="setPaymentType('full')"
                                    class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer"
                                    :class="paymentType === 'full' 
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-200' 
                                        : 'bg-gray-50 dark:bg-slate-900 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-700'"
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Bayar Lunas (Penuh)</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="setPaymentType('partial')"
                                    class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer"
                                    :class="paymentType === 'partial' 
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-200' 
                                        : 'bg-gray-50 dark:bg-slate-900 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-700'"
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <span>Bayar Sebagian (Cicil)</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] uppercase tracking-wider font-bold text-gray-600 dark:text-slate-400 mb-1">
                                Nominal Diterima (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                v-model.number="form.amount" 
                                min="1000"
                                :max="selectedInvoice.effective_remaining"
                                class="block w-full border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 rounded-xl font-bold text-lg px-4 py-2.5 font-mono" 
                                required 
                            />

                            <!-- Quick Chips -->
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <button 
                                    type="button" 
                                    @click="form.amount = selectedInvoice.effective_remaining"
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg border transition cursor-pointer"
                                    :class="form.amount === selectedInvoice.effective_remaining ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-600'"
                                >
                                    Bayar Penuh (Rp {{ formatRupiah(selectedInvoice.effective_remaining) }})
                                </button>
                                <button 
                                    v-if="selectedInvoice.effective_remaining >= 2000"
                                    type="button" 
                                    @click="form.amount = Math.floor(selectedInvoice.effective_remaining / 2)"
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg border transition cursor-pointer"
                                    :class="form.amount === Math.floor(selectedInvoice.effective_remaining / 2) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-600'"
                                >
                                    50% (Rp {{ formatRupiah(Math.floor(selectedInvoice.effective_remaining / 2)) }})
                                </button>
                            </div>

                            <!-- Kalkulasi Sisa untuk Pembayaran Sebagian -->
                            <div v-if="paymentType === 'partial'" class="mt-2.5 p-3 bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl space-y-1 text-xs">
                                <div class="flex flex-wrap justify-between gap-1 text-gray-600 dark:text-slate-300">
                                    <span>Total Sisa Saat Ini:</span>
                                    <span class="font-bold font-mono">Rp {{ formatRupiah(selectedInvoice.effective_remaining) }}</span>
                                </div>
                                <div class="flex flex-wrap justify-between gap-1 text-indigo-700 dark:text-indigo-300">
                                    <span>Nominal Dibayar Sekarang:</span>
                                    <span class="font-bold font-mono">- Rp {{ formatRupiah(form.amount || 0) }}</span>
                                </div>
                                <div class="border-t border-amber-200 dark:border-amber-800 pt-1 flex flex-wrap justify-between gap-1 font-bold" :class="remainingAfterPartial > 0 ? 'text-amber-800 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-400'">
                                    <span>Sisa Tagihan Berikutnya:</span>
                                    <span class="font-mono">Rp {{ formatRupiah(Math.max(0, remainingAfterPartial)) }}</span>
                                </div>
                                <p v-if="remainingAfterPartial > 0" class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">
                                    ℹ️ Tagihan ini akan berstatus <strong>Bayar Sebagian (Partial)</strong> hingga sisa Rp {{ formatRupiah(remainingAfterPartial) }} dilunasi.
                                </p>
                                <p v-else class="text-[10px] text-emerald-700 dark:text-emerald-400 mt-0.5 font-bold">
                                    ✓ Nominal ini akan melunasi seluruh tagihan.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-slate-300 mb-1">Metode Bayar</label>
                            <select v-model="form.payment_method" class="block w-full border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white rounded-xl text-xs py-2 px-3" required>
                                <option value="cash">Tunai (Cash)</option>
                                <option value="transfer">Transfer Bank</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-slate-300 mb-1">Catatan</label>
                            <textarea v-model="form.notes" rows="2" class="block w-full border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white rounded-xl text-xs p-3"></textarea>
                        </div>

                        <div class="flex items-start bg-indigo-50/80 dark:bg-slate-900/60 p-3 rounded-2xl border border-indigo-100 dark:border-slate-700">
                            <div class="flex items-center h-5">
                                <input id="direct" v-model="form.is_direct_payment" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer">
                            </div>
                            <div class="ml-3 text-xs">
                                <label for="direct" class="font-bold text-indigo-900 dark:text-indigo-300 cursor-pointer">Pembayaran Langsung di Kantor</label>
                                <p class="text-indigo-700 dark:text-slate-400 text-[11px] mt-0.5">Mencentang ini akan langsung melunaskan kasbon (bypass verifikasi admin). Gunakan jika uang sudah ada di kasir.</p>
                            </div>
                        </div>

                        <div class="pt-4 flex flex-wrap items-center justify-end gap-2.5 sm:gap-3 border-t border-gray-100 dark:border-slate-700">
                            <button type="button" @click="showModal = false" class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600 text-xs font-bold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 dark:shadow-indigo-950/50 transition cursor-pointer disabled:opacity-50">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Catat Penerimaan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── JANJI BAYAR MODAL RESELLER ── -->
        <Teleport to="body">
            <div v-if="showPromiseModal" class="fixed inset-0 z-100 flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm transition-opacity" @click="closePromiseModal"></div>

                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl overflow-hidden w-full max-w-md max-h-[90vh] flex flex-col relative z-10 border border-gray-100 dark:border-slate-700">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between bg-amber-50/60 dark:bg-slate-850 shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 flex items-center justify-center text-lg shrink-0">
                                📅
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Atur Janji Bayar Kasbon</h3>
                                <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 mt-0.5 truncate">
                                    {{ promiseInvoice?.customer?.name }} &middot; #{{ promiseInvoice?.invoice_number }}
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="closePromiseModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-700 p-2 rounded-xl transition-colors shrink-0 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPromise" class="flex flex-col flex-1 overflow-hidden">
                        <div class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1">
                            <div class="p-3.5 bg-indigo-50/60 dark:bg-slate-900/60 border border-indigo-100 dark:border-slate-700 rounded-2xl flex flex-wrap items-center justify-between gap-2">
                                <span class="text-xs font-bold text-gray-600 dark:text-slate-300">Sisa Tagihan Kasbon:</span>
                                <span class="text-sm font-black font-mono text-indigo-700 dark:text-indigo-400">Rp {{ formatRupiah(promiseInvoice?.remaining) }}</span>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                    Tanggal Janji Bayar <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    v-model="promiseForm.promise_date" 
                                    required 
                                    :min="new Date().toISOString().split('T')[0]" 
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl text-sm font-bold text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                    Catatan / Keterangan (Opsional)
                                </label>
                                <textarea 
                                    v-model="promiseForm.notes" 
                                    rows="2" 
                                    placeholder="Contoh: Reseller janji setoran setelah voucher habis tgl 20..." 
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                ></textarea>
                            </div>

                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed bg-amber-50/40 dark:bg-amber-950/30 p-3 rounded-xl border border-amber-100/60 dark:border-amber-800/40">
                                ℹ️ Tagihan ini akan ditandai dengan tanggal janji bayar dan otomatis tersinkronisasi ke daftar Invoice Pelanggan/Reseller.
                            </p>
                        </div>

                        <div class="px-5 py-3.5 border-t border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-850 flex flex-wrap items-center justify-between gap-2 shrink-0">
                            <div>
                                <button 
                                    v-if="promiseInvoice?.promise_date"
                                    type="button" 
                                    @click="removePromise" 
                                    class="px-3.5 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-xl transition-colors border border-rose-200 dark:border-rose-800 whitespace-nowrap cursor-pointer"
                                >
                                    Hapus Janji
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 ml-auto">
                                <button type="button" @click="closePromiseModal" class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700 bg-gray-100 dark:bg-slate-800 rounded-xl transition-colors cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" :disabled="promiseForm.processing" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-xs shadow-amber-200 flex items-center gap-1.5 whitespace-nowrap cursor-pointer disabled:opacity-50">
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

const formatWa = (phone) => {
    if (!phone) return '';
    const clean = String(phone).replace(/\D/g, '');
    if (clean.startsWith('0')) {
        return '62' + clean.slice(1);
    }
    return clean;
};

const getCustomerPhone = (inv) => {
    return inv.customer?.phone || inv.customer?.reseller?.user?.phone || '-';
};

const getWaInvoiceMessage = (inv) => {
    const customerName = inv.customer?.name || 'Mitra Reseller';
    const amountStr = formatRupiah(inv.remaining || inv.amount);
    return `Halo kak ${customerName}, kami dari tim keuangan mengonfirmasi penagihan kasbon voucher deposit #${inv.invoice_number} sebesar Rp ${amountStr}. Terima kasih.`;
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
    if (!selectedInvoice.value) return;
    form.post(route('reseller-billing.collect', selectedInvoice.value.id), {
        preserveScroll: true,
        onSuccess: (res) => {
            if (res?.props?.flash?.error) {
                Swal.fire('Gagal', res.props.flash.error, 'error');
                return;
            }
            showModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Penerimaan pembayaran kasbon berhasil dicatat.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {}).flat().join('<br>') || 'Gagal memproses pembayaran.';
            Swal.fire('Gagal', msg, 'error');
        }
    });
};

// ── Rollback Action ──
const rollbackInvoice = (inv) => {
    Swal.fire({
        title: 'Rollback Pembayaran Kasbon?',
        html: `Apakah Anda yakin ingin membatalkan pembayaran untuk tagihan kasbon <strong>#${inv.invoice_number}</strong>?<br><br>Status tagihan akan dikembalikan menjadi belum lunas dan riwayat pembayaran terkait akan dibatalkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Rollback',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('invoices.rollback', inv.id), {}, {
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res?.props?.flash?.error) {
                        Swal.fire('Gagal', res.props.flash.error, 'error');
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Rollback Berhasil!',
                        text: `Status tagihan #${inv.invoice_number} dikembalikan menjadi belum lunas.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal Rollback', err?.error || 'Gagal memproses rollback invoice.', 'error');
                }
            });
        }
    });
};

// ── Delete Invoice Kasbon Action ──
const deleteInvoice = (inv) => {
    const customerName = inv.customer?.name || 'Reseller';
    const amountStr = formatRupiah(inv.amount);
    
    Swal.fire({
        title: 'Hapus Tagihan Kasbon?',
        html: `
            <div class="text-left text-xs text-gray-600 dark:text-gray-300 space-y-3 mt-2">
                <p>Apakah Anda yakin ingin menghapus tagihan kasbon <strong>#${inv.invoice_number}</strong>?</p>
                <div class="bg-gray-50 dark:bg-slate-700 p-3 rounded-xl border border-gray-200 dark:border-slate-600 text-xs space-y-1">
                    <div><span class="text-gray-500 dark:text-gray-400 font-semibold">Reseller:</span> <strong class="text-gray-900 dark:text-white">${customerName}</strong></div>
                    <div><span class="text-gray-500 dark:text-gray-400 font-semibold">Total Tagihan:</span> <span class="font-bold font-mono text-gray-900 dark:text-white">Rp ${amountStr}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400 font-semibold">Sisa Tagihan:</span> <span class="font-bold font-mono text-indigo-600 dark:text-indigo-400">Rp ${formatRupiah(inv.remaining)}</span></div>
                </div>
                <div class="p-3 rounded-xl text-xs bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-800">
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
                onSuccess: (res) => {
                    if (res?.props?.flash?.error) {
                        Swal.fire('Gagal Menghapus', res.props.flash.error, 'error');
                        return;
                    }
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
    promiseForm.post(route('invoices.promise', promiseInvoice.value.id), {
        preserveScroll: true,
        onSuccess: (res) => {
            if (res?.props?.flash?.error) {
                Swal.fire('Gagal', res.props.flash.error, 'error');
                return;
            }
            closePromiseModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Tanggal janji bayar berhasil disimpan.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (err) => {
            Swal.fire('Gagal', Object.values(err || {}).flat().join('<br>') || 'Gagal menyimpan janji bayar.', 'error');
        }
    });
};

const removePromise = () => {
    if (!promiseInvoice.value) return;
    Swal.fire({
        title: 'Hapus Janji Bayar?',
        html: `Hapus tanggal janji bayar untuk kasbon <strong>#${promiseInvoice.value.invoice_number}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Janji',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('invoices.promise', promiseInvoice.value.id), {
                promise_date: null,
                notes: promiseInvoice.value.notes || null,
            }, {
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res?.props?.flash?.error) {
                        Swal.fire('Gagal', res.props.flash.error, 'error');
                        return;
                    }
                    closePromiseModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Dihapus!',
                        text: 'Tanggal janji bayar berhasil dihapus.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
};

const getDueDuration = (dueDateStr) => {
    const due = new Date(dueDateStr);
    const today = new Date();
    due.setHours(0,0,0,0);
    today.setHours(0,0,0,0);
    
    const diffTime = due - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
        return { text: `Lewat ${Math.abs(diffDays)} hari`, class: 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded inline-block border border-rose-200 dark:border-rose-800' };
    } else if (diffDays === 0) {
        return { text: 'Jatuh tempo hari ini', class: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded inline-block border border-amber-200 dark:border-amber-800' };
    } else {
        return { text: `Sisa ${diffDays} hari`, class: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded inline-block border border-emerald-200 dark:border-emerald-800' };
    }
};
</script>
