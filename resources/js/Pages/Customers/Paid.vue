<template>
    <AppLayout title="Pelanggan Berbayar" subtitle="Manajemen pelanggan internet reguler, pemantauan tagihan & status layanan realtime">
        <div class="max-w-7xl mx-auto space-y-6 pb-12 font-sans">
            
            <!-- Alert Flash Messages -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-sm font-medium shadow-2xs flex items-center gap-2">
                <span class="text-lg">✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-2xs flex items-center gap-2">
                <span class="text-lg">❌</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Banner Mewah & Informatif -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-blue-700 via-indigo-700 to-teal-700 p-6 rounded-3xl text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-52 h-52 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 space-y-1.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold uppercase tracking-wider text-blue-100 border border-white/20">
                        <span>⭐</span> Pelanggan Berlangganan & Tagihan
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-2.5">
                        <span>Data Pelanggan Berbayar</span>
                        <span class="text-sm font-normal text-blue-200 px-2.5 py-0.5 rounded-full bg-white/10 border border-white/10">
                            Periode: {{ currentPeriod.label }}
                        </span>
                    </h2>
                    <p class="text-xs sm:text-sm text-blue-100 max-w-2xl leading-relaxed">
                        Pusat kendali pelanggan internet reguler berbayar dengan integrasi status penagihan invoice, audit tunggakan, dan pemantauan sesi FreeRADIUS.
                    </p>
                </div>
                <div class="relative z-10 flex flex-wrap items-center gap-2.5 shrink-0">
                    <Link 
                        :href="route('invoices.index')"
                        class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 text-white px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm border border-white/20 backdrop-blur-md transition-all shadow-sm"
                        title="Buka Menu Manajemen Invoice"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Invoice Tagihan</span>
                    </Link>
                    <Link 
                        :href="route('customers.create')"
                        class="inline-flex items-center gap-2 bg-white text-indigo-900 hover:bg-blue-50 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm shadow-md transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Pelanggan</span>
                    </Link>
                </div>
            </div>

            <!-- ── 6 CARD METRIK CERDAS RINGKASAN PEMBAYARAN ── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5">
                
                <!-- Card 1: Total Pelanggan Berbayar -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-blue-100 dark:border-slate-800 shadow-xs hover:border-blue-300 dark:hover:border-blue-700 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Pelanggan Berbayar
                            </span>
                            <span class="text-[10px] bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full font-bold">Total</span>
                        </div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">
                            {{ Number(stats.total || 0).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 font-medium border-t border-gray-100 dark:border-slate-800 pt-1.5 flex items-center justify-between gap-1">
                        <span class="truncate">Potensi:</span>
                        <span class="text-blue-700 dark:text-blue-400 font-bold font-mono whitespace-nowrap">{{ formatShortRupiah(stats.total_potential) }}</span>
                    </div>
                </div>

                <!-- Card 2: Lunas Periode Ini (Paid) -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-emerald-100 dark:border-slate-800 shadow-xs hover:border-emerald-300 dark:hover:border-emerald-700 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Sudah Bayar (Lunas)
                            </span>
                            <span class="text-[10px] bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 rounded-full font-bold">Paid</span>
                        </div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                            {{ Number(stats.paid_count || 0).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 font-medium border-t border-gray-100 dark:border-slate-800 pt-1.5 flex items-center justify-between gap-1">
                        <span class="truncate">Diterima:</span>
                        <span class="text-emerald-700 dark:text-emerald-400 font-bold font-mono whitespace-nowrap">{{ formatShortRupiah(stats.paid_amount) }}</span>
                    </div>
                </div>

                <!-- Card 3: Belum Bayar (Unpaid) -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-amber-100 dark:border-slate-800 shadow-xs hover:border-amber-300 dark:hover:border-amber-700 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                Menunggu Bayar
                            </span>
                            <span class="text-[10px] bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded-full font-bold">Unpaid</span>
                        </div>
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                            {{ Number(stats.unpaid_count || 0).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 font-medium border-t border-gray-100 dark:border-slate-800 pt-1.5 flex items-center justify-between gap-1">
                        <span class="truncate">Nominal:</span>
                        <span class="text-amber-700 dark:text-amber-400 font-bold font-mono whitespace-nowrap">{{ formatShortRupiah(stats.unpaid_amount) }}</span>
                    </div>
                </div>

                <!-- Card 4: Menunggak / Overdue -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-rose-100 dark:border-slate-800 shadow-xs hover:border-rose-300 dark:hover:border-rose-700 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Lewat Jatuh Tempo
                            </span>
                            <span class="text-[10px] bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 px-2 py-0.5 rounded-full font-bold">Overdue</span>
                        </div>
                        <div class="text-2xl font-black text-rose-600 dark:text-rose-400">
                            {{ Number(stats.overdue_count || 0).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 font-medium border-t border-gray-100 dark:border-slate-800 pt-1.5 flex items-center justify-between gap-1">
                        <span class="truncate">Piutang:</span>
                        <span class="text-rose-700 dark:text-rose-400 font-bold font-mono whitespace-nowrap">{{ formatShortRupiah(stats.overdue_amount) }}</span>
                    </div>
                </div>

                <!-- Card 5: Terisolir (Suspended) -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-purple-100 dark:border-slate-800 shadow-xs hover:border-purple-300 dark:hover:border-purple-700 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                Pelanggan Terisolir
                            </span>
                            <span class="text-[10px] bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full font-bold">Isolir</span>
                        </div>
                        <div class="text-2xl font-black text-purple-600 dark:text-purple-400">
                            {{ Number(stats.suspended_count || 0).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 font-medium border-t border-gray-100 dark:border-slate-800 pt-1.5 flex items-center justify-between gap-1">
                        <span class="truncate">Layanan:</span>
                        <span class="text-purple-700 dark:text-purple-400 font-bold whitespace-nowrap">Diblokir</span>
                    </div>
                </div>

                <!-- Card 6: Sesi Jaringan (RADIUS) -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-4 border border-teal-100 dark:border-slate-800 shadow-xs hover:border-teal-300 dark:hover:border-teal-700 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                Sesi Online (PPPoE)
                            </span>
                            <span class="text-[10px] bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 px-2 py-0.5 rounded-full font-bold">Radius</span>
                        </div>
                        <div class="text-2xl font-black text-teal-600 dark:text-teal-400">
                            {{ Number(stats.online_count || 0).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 font-medium border-t border-gray-100 dark:border-slate-800 pt-1.5 flex items-center justify-between gap-1">
                        <span class="truncate">Offline:</span>
                        <span class="text-rose-600 dark:text-rose-400 font-bold font-mono whitespace-nowrap">{{ Number(stats.offline_count || 0).toLocaleString('id-ID') }}</span>
                    </div>
                </div>

            </div>

            <!-- ── FILTER CERDAS & SEGMENTED TABS STATUS PEMBAYARAN ── -->
            <div class="space-y-3">
                <!-- Segmented Pills Status Pembayaran (Scroll Halus Tanpa Scrollbar Kasar) -->
                <div class="flex items-center gap-1.5 p-1.5 bg-gray-100/90 dark:bg-slate-800/80 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xs overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                    <!-- Tab: Semua -->
                    <button 
                        type="button" 
                        @click="setPaymentStatusFilter('')"
                        :class="filters.payment_status === '' 
                            ? 'bg-white dark:bg-slate-900 text-indigo-900 dark:text-blue-300 shadow-xs font-bold border border-gray-200 dark:border-slate-700' 
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-700/50 font-semibold border border-transparent'"
                        class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                    >
                        <span>Semua</span>
                        <span 
                            :class="filters.payment_status === '' ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                            class="px-2 py-0.5 text-[11px] font-extrabold rounded-full"
                        >
                            {{ Number(stats.total || 0).toLocaleString('id-ID') }}
                        </span>
                    </button>

                    <!-- Tab: 🟢 Lunas (Paid) -->
                    <button 
                        type="button" 
                        @click="setPaymentStatusFilter('paid')"
                        :class="filters.payment_status === 'paid' 
                            ? 'bg-emerald-600 text-white shadow-xs font-bold border border-emerald-700 dark:border-emerald-500' 
                            : 'text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100/70 dark:hover:bg-emerald-950/40 font-semibold border border-transparent'"
                        class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full" :class="filters.payment_status === 'paid' ? 'bg-white' : 'bg-emerald-500'"></span>
                        <span>Lunas (Paid)</span>
                        <span 
                            :class="filters.payment_status === 'paid' ? 'bg-white/20 text-white' : 'bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300'"
                            class="px-2 py-0.5 text-[11px] font-extrabold rounded-full"
                        >
                            {{ Number(stats.paid_count || 0).toLocaleString('id-ID') }}
                        </span>
                    </button>

                    <!-- Tab: 🟡 Belum Bayar (Unpaid) -->
                    <button 
                        type="button" 
                        @click="setPaymentStatusFilter('unpaid')"
                        :class="filters.payment_status === 'unpaid' 
                            ? 'bg-amber-600 text-white shadow-xs font-bold border border-amber-700 dark:border-amber-500' 
                            : 'text-amber-700 dark:text-amber-400 hover:bg-amber-100/70 dark:hover:bg-amber-950/40 font-semibold border border-transparent'"
                        class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full" :class="filters.payment_status === 'unpaid' ? 'bg-white' : 'bg-amber-500'"></span>
                        <span>Belum Bayar (Unpaid)</span>
                        <span 
                            :class="filters.payment_status === 'unpaid' ? 'bg-white/20 text-white' : 'bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300'"
                            class="px-2 py-0.5 text-[11px] font-extrabold rounded-full"
                        >
                            {{ Number(stats.unpaid_count || 0).toLocaleString('id-ID') }}
                        </span>
                    </button>

                    <!-- Tab: 🔴 Menunggak (Overdue) -->
                    <button 
                        type="button" 
                        @click="setPaymentStatusFilter('overdue')"
                        :class="filters.payment_status === 'overdue' 
                            ? 'bg-rose-600 text-white shadow-xs font-bold border border-rose-700 dark:border-rose-500' 
                            : 'text-rose-700 dark:text-rose-400 hover:bg-rose-100/70 dark:hover:bg-rose-950/40 font-semibold border border-transparent'"
                        class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full" :class="filters.payment_status === 'overdue' ? 'bg-white' : 'bg-rose-500 animate-pulse'"></span>
                        <span>Lewat Jatuh Tempo (Overdue)</span>
                        <span 
                            :class="filters.payment_status === 'overdue' ? 'bg-white/20 text-white' : 'bg-rose-100 dark:bg-rose-950/70 text-rose-800 dark:text-rose-300'"
                            class="px-2 py-0.5 text-[11px] font-extrabold rounded-full"
                        >
                            {{ Number(stats.overdue_count || 0).toLocaleString('id-ID') }}
                        </span>
                    </button>

                    <!-- Tab: 🟠 Terisolir -->
                    <button 
                        type="button" 
                        @click="setPaymentStatusFilter('isolir')"
                        :class="filters.payment_status === 'isolir' 
                            ? 'bg-purple-600 text-white shadow-xs font-bold border border-purple-700 dark:border-purple-500' 
                            : 'text-purple-700 dark:text-purple-400 hover:bg-purple-100/70 dark:hover:bg-purple-950/40 font-semibold border border-transparent'"
                        class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                    >
                        <span>Terisolir (Suspended)</span>
                        <span 
                            :class="filters.payment_status === 'isolir' ? 'bg-white/20 text-white' : 'bg-purple-100 dark:bg-purple-950/70 text-purple-800 dark:text-purple-300'"
                            class="px-2 py-0.5 text-[11px] font-extrabold rounded-full"
                        >
                            {{ Number(stats.suspended_count || 0).toLocaleString('id-ID') }}
                        </span>
                    </button>

                    <!-- Tab: 🔵 Bayar Sebagian -->
                    <button 
                        type="button" 
                        @click="setPaymentStatusFilter('partial')"
                        :class="filters.payment_status === 'partial' 
                            ? 'bg-blue-600 text-white shadow-xs font-bold border border-blue-700 dark:border-blue-500' 
                            : 'text-blue-700 dark:text-blue-400 hover:bg-blue-100/70 dark:hover:bg-blue-950/40 font-semibold border border-transparent'"
                        class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                    >
                        <span>Cicilan / Sebagian</span>
                        <span 
                            :class="filters.payment_status === 'partial' ? 'bg-white/20 text-white' : 'bg-blue-100 dark:bg-blue-950/70 text-blue-800 dark:text-blue-300'"
                            class="px-2 py-0.5 text-[11px] font-extrabold rounded-full"
                        >
                            {{ Number(stats.partial_count || 0).toLocaleString('id-ID') }}
                        </span>
                    </button>

                    <!-- Tab: ⚪ Tanpa Tagihan -->
                    <button 
                        type="button" 
                        @click="setPaymentStatusFilter('no_invoice')"
                        :class="filters.payment_status === 'no_invoice' 
                            ? 'bg-gray-700 dark:bg-slate-700 text-white shadow-xs font-bold border border-gray-800 dark:border-slate-600' 
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700/50 font-semibold border border-transparent'"
                        class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                    >
                        <span>Belum Ada Tagihan</span>
                        <span 
                            :class="filters.payment_status === 'no_invoice' ? 'bg-white/20 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                            class="px-2 py-0.5 text-[11px] font-extrabold rounded-full"
                        >
                            {{ Number(stats.no_invoice_count || 0).toLocaleString('id-ID') }}
                        </span>
                    </button>
                </div>

                <!-- Filter Bar Kontrol 2 Baris Rapih & Elegan -->
                <div class="bg-white dark:bg-slate-900/90 p-4 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-2xs space-y-3">
                    <!-- Baris 1: Search Utama + Tombol Terapkan & Reset -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <div class="relative flex-1">
                            <input 
                                v-model="formFilters.search" 
                                type="text" 
                                placeholder="Cari nama pelanggan, ID pelanggan, no WA, alamat, user PPPoE, nomor invoice..." 
                                class="w-full pl-9 pr-4 py-2.5 bg-gray-50 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm shadow-2xs placeholder-gray-400 dark:placeholder-gray-500"
                                @keyup.enter="applyFilters"
                            >
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button 
                                @click="applyFilters" 
                                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                <span>Filter</span>
                            </button>
                            <button 
                                v-if="hasActiveFilter" 
                                @click="resetFilters" 
                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1 cursor-pointer"
                                title="Reset Seluruh Filter"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <!-- Baris 2: 5 Dropdown Filter Spesifik -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 pt-2 border-t border-gray-100 dark:border-slate-800/80">
                        <!-- Dropdown Periode Bulan -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Periode Bulan</label>
                            <select v-model="formFilters.period_month" @change="applyFilters" class="w-full bg-gray-50 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl text-xs shadow-2xs focus:ring-blue-500 focus:border-blue-500 py-2">
                                <option v-for="(mName, mIdx) in months" :key="mIdx" :value="mIdx + 1">
                                    {{ mName }}
                                </option>
                            </select>
                        </div>

                        <!-- Dropdown Periode Tahun -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Tahun</label>
                            <select v-model="formFilters.period_year" @change="applyFilters" class="w-full bg-gray-50 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl text-xs shadow-2xs focus:ring-blue-500 focus:border-blue-500 py-2">
                                <option v-for="yr in yearOptions" :key="yr" :value="yr">
                                    {{ yr }}
                                </option>
                            </select>
                        </div>

                        <!-- Dropdown Paket Internet -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Paket Layanan</label>
                            <select v-model="formFilters.package_id" @change="applyFilters" class="w-full bg-gray-50 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl text-xs shadow-2xs focus:ring-blue-500 focus:border-blue-500 py-2">
                                <option value="">Semua Paket</option>
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} ({{ pkg.speed_mbps }}M)
                                </option>
                            </select>
                        </div>

                        <!-- Dropdown Area / Wilayah -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Area / Wilayah</label>
                            <select v-model="formFilters.area_id" @change="applyFilters" class="w-full bg-gray-50 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl text-xs shadow-2xs focus:ring-blue-500 focus:border-blue-500 py-2">
                                <option value="">Semua Wilayah</option>
                                <option v-for="ar in areas" :key="ar.id" :value="ar.id">
                                    {{ ar.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Dropdown Koneksi Online/Offline -->
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Sesi Jaringan</label>
                            <select v-model="formFilters.connection_status" @change="applyFilters" class="w-full bg-gray-50 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl text-xs shadow-2xs focus:ring-blue-500 focus:border-blue-500 py-2">
                                <option value="">Semua Sesi</option>
                                <option value="online">🟢 Online</option>
                                <option value="offline">🔴 Offline</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TABEL DATA PELANGGAN BERBAYAR ── -->
            <div class="bg-white dark:bg-slate-900/90 rounded-3xl shadow-xs border border-gray-200 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 text-xs sm:text-sm">
                        <thead class="bg-gray-50/90 dark:bg-slate-800/90 text-gray-600 dark:text-gray-300 uppercase text-[11px] font-bold tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5 text-left">Pelanggan & Kontak</th>
                                <th class="px-5 py-3.5 text-left">Paket & Tarif</th>
                                <th class="px-5 py-3.5 text-left">Wilayah & ODP</th>
                                <th class="px-5 py-3.5 text-left">Sesi Jaringan</th>
                                <th class="px-5 py-3.5 text-left">Status Tagihan (Periode)</th>
                                <th class="px-5 py-3.5 text-left">Total Tagihan / Tunggakan</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            <tr 
                                v-for="customer in customers.data" 
                                :key="customer.id"
                                :class="[
                                    customer.is_suspended 
                                        ? 'bg-purple-50/40 dark:bg-purple-950/20 hover:bg-purple-50/70 dark:hover:bg-purple-950/40 border-l-4 border-l-purple-500' 
                                        : (customer.payment_summary?.status === 'overdue' 
                                            ? 'bg-rose-50/30 dark:bg-rose-950/20 hover:bg-rose-50/60 dark:hover:bg-rose-950/40 border-l-4 border-l-rose-500' 
                                            : (customer.payment_summary?.status === 'paid' 
                                                ? 'hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20 border-l-4 border-l-emerald-500' 
                                                : 'hover:bg-blue-50/20 dark:hover:bg-slate-800/50 border-l-4 border-l-gray-300 dark:border-l-slate-700'))
                                ]"
                                class="transition-colors"
                            >
                                <!-- 1. Pelanggan & Kontak -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5">
                                        <Link 
                                            :href="route('customers.show', customer.id)"
                                            class="font-bold text-gray-900 dark:text-white hover:text-blue-700 dark:hover:text-blue-400 transition-colors text-sm flex items-center gap-1.5"
                                        >
                                            <span>{{ customer.name }}</span>
                                            <span v-if="customer.is_suspended" class="px-1.5 py-0.2 rounded text-[10px] font-extrabold bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                ISOLIR
                                            </span>
                                        </Link>
                                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="font-mono font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 px-1.5 py-0.2 rounded border border-blue-200 dark:border-blue-800/60">
                                                {{ customer.customer_code }}
                                            </span>
                                            <span v-if="customer.ont?.pppoe_user" class="font-mono text-gray-600 dark:text-gray-400 text-[11px]" title="Username PPPoE">
                                                User: {{ customer.ont.pppoe_user }}
                                            </span>
                                        </div>
                                        <div v-if="customer.phone" class="mt-1 flex items-center gap-1.5">
                                            <a 
                                                :href="getWhatsAppUrl(customer)" 
                                                target="_blank" 
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:text-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 px-2 py-0.5 rounded-lg border border-emerald-200 dark:border-emerald-800/60 transition-colors"
                                                title="Chat WhatsApp"
                                            >
                                                <span>📱</span>
                                                <span class="font-mono">{{ customer.phone }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                <!-- 2. Paket & Tarif -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">
                                            {{ customer.package?.name || 'Paket Standar' }}
                                        </span>
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <span class="font-mono font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800/60">
                                                {{ formatRupiah(customer.package?.price || 0) }} / bln
                                            </span>
                                            <span v-if="customer.package?.speed_mbps" class="font-semibold text-gray-500 dark:text-gray-400 text-[11px]">
                                                {{ customer.package.speed_mbps }} Mbps
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Wilayah & ODP -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5 text-xs">
                                        <span class="font-bold text-gray-800 dark:text-gray-200">
                                            {{ customer.areaModel?.name || customer.area || '-' }}
                                        </span>
                                        <span v-if="customer.ont?.odp?.name" class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <span>📍</span> ODP: {{ customer.ont.odp.name }}
                                        </span>
                                        <span v-else class="text-[11px] text-gray-400">
                                            Alamat: {{ truncateText(customer.address, 25) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- 4. Sesi Jaringan (RADIUS) -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div v-if="customer.is_online" class="flex flex-col gap-1">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/70 px-2.5 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-700 w-max shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            ONLINE
                                        </span>
                                        <div v-if="customer.online_session?.ip && customer.online_session.ip !== '-'" class="text-[10px] font-mono text-gray-600 dark:text-gray-400">
                                            IP: <span class="font-bold text-gray-800 dark:text-gray-200">{{ customer.online_session.ip }}</span>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-800 dark:text-rose-300 bg-rose-100 dark:bg-rose-950/70 px-2.5 py-0.5 rounded-full border border-rose-300 dark:border-rose-700 w-max shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            OFFLINE
                                        </span>
                                    </div>
                                </td>

                                <!-- 5. Status Tagihan Periode Terpilih -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col items-start gap-1">
                                        <!-- Badge Status -->
                                        <span 
                                            v-if="customer.payment_summary?.status === 'paid'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                        >
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            LUNAS
                                        </span>
                                        <span 
                                            v-else-if="customer.payment_summary?.status === 'overdue'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800 border border-rose-200 animate-pulse"
                                        >
                                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            MENUNGGAK
                                        </span>
                                        <span 
                                            v-else-if="customer.payment_summary?.status === 'partial'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-blue-100 text-blue-800 border border-blue-200"
                                        >
                                            SEBAGIAN (CICILAN)
                                        </span>
                                        <span 
                                            v-else-if="customer.payment_summary?.status === 'unpaid'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800 border border-amber-200"
                                        >
                                            BELUM BAYAR
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200"
                                        >
                                            BELUM TERBIT
                                        </span>

                                        <!-- Invoice Meta Details -->
                                        <div v-if="customer.current_invoice" class="text-[11px] text-gray-500 font-mono">
                                            Inv: {{ customer.current_invoice.invoice_number }}
                                        </div>
                                        <div v-if="customer.current_invoice?.due_date" class="text-[10px] text-gray-500">
                                            Jatuh Tempo: {{ formatDate(customer.current_invoice.due_date) }}
                                        </div>
                                        <div v-if="customer.payment_summary?.days_overdue > 0" class="text-[10px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">
                                            Lewat {{ customer.payment_summary.days_overdue }} Hari
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Total Tagihan / Akumulasi Tunggakan -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col items-start gap-1">
                                        <div class="font-bold text-sm font-mono" :class="customer.payment_summary?.status === 'paid' ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-white'">
                                            {{ formatRupiah(customer.payment_summary?.total_bill || 0) }}
                                        </div>

                                        <!-- Warning Tunggakan Masa Lalu -->
                                        <div v-if="customer.payment_summary?.past_debt_months > 0" class="flex flex-col gap-0.5">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-rose-800 bg-rose-100 px-2 py-0.5 rounded-md border border-rose-200">
                                                <span>⚠️</span> Menunggak {{ customer.payment_summary.past_debt_months }} Bulan
                                            </span>
                                            <span class="text-[10px] text-rose-600 font-mono font-semibold">
                                                Tunggakan: {{ formatRupiah(customer.payment_summary.past_debt_amount) }}
                                            </span>
                                        </div>
                                        <div v-else-if="customer.payment_summary?.status === 'paid'" class="text-[10px] text-emerald-700 font-semibold flex items-center gap-1">
                                            <span>✨</span> Bebas Tunggakan
                                        </div>
                                    </div>
                                </td>

                                <!-- 7. Aksi Layanan & Profil -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">

                                        <!-- Tombol WhatsApp Pengingat Tagihan (Jika belum bayar / overdue) -->
                                        <a 
                                            v-if="customer.payment_summary?.status !== 'paid' && customer.phone"
                                            :href="getWhatsAppReminderUrl(customer)" 
                                            target="_blank"
                                            class="p-1.5 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg shadow-2xs transition-all hover:scale-105 active:scale-95"
                                            title="Kirim Tagihan / Reminder via WhatsApp"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        </a>

                                        <!-- Tombol WhatsApp Kwitansi Lunas (Jika lunas) -->
                                        <a 
                                            v-else-if="customer.payment_summary?.status === 'paid' && customer.phone"
                                            :href="getWhatsAppReceiptUrl(customer)" 
                                            target="_blank"
                                            class="p-1.5 text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg shadow-2xs transition-all hover:scale-105 active:scale-95"
                                            title="Kirim Kwitansi Lunas via WhatsApp"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </a>

                                        <!-- Tombol Cetak Kwitansi / Invoice -->
                                        <a 
                                            v-if="customer.current_invoice"
                                            :href="route('invoices.print', customer.current_invoice.id)"
                                            target="_blank"
                                            class="p-1.5 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg shadow-2xs transition-all hover:scale-105 active:scale-95"
                                            title="Cetak Invoice / Kwitansi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>

                                        <!-- Tombol Isolir / Buka Isolir Cepat -->
                                        <button 
                                            @click="toggleIsolir(customer)"
                                            :disabled="isolirProcessing === customer.id"
                                            :class="customer.is_suspended ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200' : 'text-purple-700 bg-purple-50 hover:bg-purple-100 border-purple-200'"
                                            class="p-1.5 rounded-lg border shadow-2xs transition-all hover:scale-105 active:scale-95 disabled:opacity-50 cursor-pointer"
                                            :title="customer.is_suspended ? 'Buka Isolir (Aktifkan Kembali Layanan)' : 'Isolir Layanan (Blokir Penagihan)'"
                                        >
                                            <svg v-if="customer.is_suspended" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </button>

                                        <!-- Tombol Detail Pelanggan -->
                                        <Link 
                                            :href="route('customers.show', customer.id)"
                                            class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                                            title="Buka Profil Lengkap Pelanggan"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!customers.data || customers.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900 mb-1">Tidak Ada Pelanggan Ditemukan</h3>
                                        <p class="text-xs text-gray-500 mb-4">Coba sesuaikan kata kunci pencarian, filter status pembayaran, atau periode penagihan.</p>
                                        <button 
                                            @click="resetFilters"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all cursor-pointer"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="customers.links && customers.links.length > 3" class="px-5 py-4 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600 dark:text-gray-400">
                    <div>
                        Menampilkan <span class="font-bold text-gray-900 dark:text-white">{{ customers.from || 0 }}</span> - <span class="font-bold text-gray-900 dark:text-white">{{ customers.to || 0 }}</span> dari <span class="font-bold text-gray-900 dark:text-white">{{ customers.total || 0 }}</span> pelanggan
                    </div>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, idx) in customers.links" 
                            :key="idx" 
                            :href="link.url || '#'" 
                            :class="[
                                link.active ? 'bg-blue-600 text-white font-bold' : (link.url ? 'bg-white dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-slate-700' : 'text-gray-400 opacity-50 cursor-not-allowed'),
                                'px-3 py-1.5 rounded-xl text-xs transition-colors'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    customers: Object,
    packages: Array,
    areas: Array,
    filters: Object,
    stats: Object,
    currentPeriod: Object,
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const currentYear = new Date().getFullYear();
const yearOptions = [currentYear - 2, currentYear - 1, currentYear, currentYear + 1];

// Form filter reactive state
const formFilters = ref({
    search: props.filters?.search || '',
    area_id: props.filters?.area_id || '',
    package_id: props.filters?.package_id || '',
    payment_status: props.filters?.payment_status || '',
    connection_status: props.filters?.connection_status || '',
    period_month: props.filters?.period_month || (new Date().getMonth() + 1),
    period_year: props.filters?.period_year || currentYear,
    per_page: props.filters?.per_page || 15,
});

const hasActiveFilter = computed(() => {
    return !!(
        formFilters.value.search || 
        formFilters.value.area_id || 
        formFilters.value.package_id || 
        formFilters.value.payment_status || 
        formFilters.value.connection_status ||
        formFilters.value.period_month !== (new Date().getMonth() + 1) ||
        formFilters.value.period_year !== currentYear
    );
});

const applyFilters = () => {
    router.get('/customers/paid', formFilters.value, {
        preserveState: true,
        replace: true,
    });
};

const setPaymentStatusFilter = (status) => {
    formFilters.value.payment_status = status;
    applyFilters();
};

const resetFilters = () => {
    formFilters.value.search = '';
    formFilters.value.area_id = '';
    formFilters.value.package_id = '';
    formFilters.value.payment_status = '';
    formFilters.value.connection_status = '';
    formFilters.value.period_month = new Date().getMonth() + 1;
    formFilters.value.period_year = currentYear;
    applyFilters();
};

// Formatting Helpers
const formatRupiah = (val) => {
    const num = Number(val || 0);
    return 'Rp ' + num.toLocaleString('id-ID');
};

const formatShortRupiah = (val) => {
    const num = Number(val || 0);
    if (num >= 1000000000) {
        return 'Rp ' + (num / 1000000000).toFixed(1) + ' M';
    }
    if (num >= 1000000) {
        return 'Rp ' + (num / 1000000).toFixed(1) + ' jt';
    }
    if (num >= 1000) {
        return 'Rp ' + (num / 1000).toFixed(0) + ' rb';
    }
    return 'Rp ' + num.toLocaleString('id-ID');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return dateStr;
    }
};

const truncateText = (txt, maxLen = 30) => {
    if (!txt) return '-';
    return txt.length > maxLen ? txt.substring(0, maxLen) + '...' : txt;
};

// WhatsApp Helpers
const cleanPhone = (phone) => {
    if (!phone) return '';
    let p = String(phone).replace(/\D/g, '');
    if (p.startsWith('0')) p = '62' + p.substring(1);
    return p;
};

const getWhatsAppUrl = (customer) => {
    const p = cleanPhone(customer.phone);
    return `https://wa.me/${p}`;
};

const getWhatsAppReminderUrl = (customer) => {
    const p = cleanPhone(customer.phone);
    const inv = customer.current_invoice;
    const invNum = inv?.invoice_number || '-';
    const amount = formatRupiah(inv?.remaining || inv?.amount || customer.package?.price || 0);
    const dueDate = inv?.due_date ? formatDate(inv.due_date) : 'Segera';
    const period = props.currentPeriod?.label || '';

    const text = `Halo Kak *${customer.name}*,\n\nKami menginformasikan tagihan internet Anda periode *${period}*:\n` +
        `• No. Pelanggan: *${customer.customer_code}*\n` +
        `• No. Tagihan: *${invNum}*\n` +
        `• Paket: *${customer.package?.name || '-'}*\n` +
        `• Total Tagihan: *${amount}*\n` +
        `• Jatuh Tempo: *${dueDate}*\n\n` +
        `Mohon dapat melakukan pembayaran sebelum tanggal jatuh tempo agar koneksi internet tetap lancar dan tidak terisolir. Terima kasih! 🙏`;

    return `https://wa.me/${p}?text=${encodeURIComponent(text)}`;
};

const getWhatsAppReceiptUrl = (customer) => {
    const p = cleanPhone(customer.phone);
    const inv = customer.current_invoice;
    const invNum = inv?.invoice_number || '-';
    const amount = formatRupiah(inv?.total_paid || inv?.amount || 0);
    const period = props.currentPeriod?.label || '';

    const text = `Terima kasih Kak *${customer.name}*!\n\nPembayaran tagihan internet periode *${period}* telah kami terima dengan rincian:\n` +
        `• No. Pelanggan: *${customer.customer_code}*\n` +
        `• No. Kwitansi: *${invNum}*\n` +
        `• Total Dibayar: *${amount}*\n` +
        `• Status: *LUNAS (PAID)* ✅\n\n` +
        `Terima kasih telah setia berlangganan bersama kami. Selamat menikmati layanan internet berkecepatan tinggi! 🚀`;

    return `https://wa.me/${p}?text=${encodeURIComponent(text)}`;
};



// Isolir / Buka Isolir
const isolirProcessing = ref(null);
const toggleIsolir = (customer) => {
    const isSuspended = customer.is_suspended;
    const actionText = isSuspended ? 'Buka Isolir' : 'Isolir Layanan';
    const descText = isSuspended 
        ? `Buka isolir untuk ${customer.name}? Layanan internet pelanggan akan diaktifkan kembali di RADIUS.` 
        : `Isolir koneksi internet untuk ${customer.name}? Akses internet pelanggan akan dinonaktifkan sementara di RADIUS.`;

    Swal.fire({
        title: `${actionText}?`,
        text: descText,
        icon: isSuspended ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isSuspended ? '#059669' : '#9333ea',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Ya, ${actionText}!`,
        cancelButtonText: 'Batal'
    }).then((res) => {
        if (res.isConfirmed) {
            isolirProcessing.value = customer.id;
            const routeName = isSuspended ? 'customers.unsuspend' : 'customers.suspend';
            
            router.post(route(routeName, customer.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    isolirProcessing.value = null;
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: `Status layanan ${customer.name} berhasil diperbarui.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: () => {
                    isolirProcessing.value = null;
                    Swal.fire('Gagal', 'Terjadi kesalahan saat mengubah status isolir.', 'error');
                },
                onFinish: () => {
                    isolirProcessing.value = null;
                }
            });
        }
    });
};
</script>
