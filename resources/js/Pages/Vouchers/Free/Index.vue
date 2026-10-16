<template>
    <component :is="layoutComponent" title="Data Voucher Gratis" subtitle="Kelola dan generate voucher hotspot gratis">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Data Voucher Gratis</h1>
            <p class="text-sm text-gray-500">Kelola dan generate voucher hotspot gratis</p>
        </div>

        <!-- EXECUTIVE KPI STATS CARDS -->
        <div v-if="can('voucher_free_card_stats')" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 mb-6">
            <!-- Card 1: Total Voucher Gratis -->
            <div 
                @click="filterByStatus('')" 
                :class="currentTab === 'vouchers' && statusFilter === '' && !search ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/30' : 'border-gray-200 bg-white hover:border-emerald-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Total Voucher Gratis</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-gray-900 tracking-tight">
                        {{ Number(stats?.total || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-emerald-700 font-semibold mt-0.5 flex items-center gap-1">
                        <span>🎁 Kuota Bebas Biaya (Rp 0)</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Tersedia -->
            <div 
                @click="filterByStatus('available')" 
                :class="currentTab === 'vouchers' && statusFilter === 'available' ? 'ring-2 ring-blue-500 border-blue-500 bg-blue-50/30' : 'border-gray-200 bg-white hover:border-blue-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">Tersedia</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-blue-900 tracking-tight">
                        {{ Number(stats?.available || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-blue-600 font-medium mt-0.5">
                        Siap dibagikan & login
                    </div>
                </div>
            </div>

            <!-- Card 3: Digunakan & Online -->
            <div 
                @click="filterByStatus('used')" 
                :class="currentTab === 'vouchers' && statusFilter === 'used' ? 'ring-2 ring-purple-500 border-purple-500 bg-purple-50/30' : 'border-gray-200 bg-white hover:border-purple-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-purple-700 uppercase tracking-wider">Digunakan</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-purple-900 tracking-tight">
                        {{ Number(stats?.used || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-purple-700 font-semibold mt-0.5 flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>{{ Number(stats?.online || 0) }} Sesi Online Live</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Expired -->
            <div 
                @click="filterByStatus('expired')" 
                :class="currentTab === 'vouchers' && statusFilter === 'expired' ? 'ring-2 ring-rose-500 border-rose-500 bg-rose-50/30' : 'border-gray-200 bg-white hover:border-rose-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Expired</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-rose-900 tracking-tight">
                        {{ Number(stats?.expired || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-rose-600 font-medium mt-0.5">
                        Durasi masa aktif habis
                    </div>
                </div>
            </div>

            <!-- Card 5: Pelanggan ONT -->
            <div 
                v-if="can('voucher_free_tab_customers')"
                @click="switchTab('customers')" 
                :class="currentTab === 'customers' ? 'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50/30' : 'border-gray-200 bg-white hover:border-indigo-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between col-span-2 sm:col-span-1 lg:col-span-1"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Pelanggan ONT</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-indigo-900 tracking-tight">
                        {{ Number(stats?.customer_count || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-indigo-700 font-semibold mt-0.5">
                        Free Hotspot ONT Aktivasi
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN SUB-TAB NAVIGATION & ACTION BUTTONS -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-2.5 rounded-2xl border border-gray-200 shadow-2xs">
            <!-- Tab Buttons -->
            <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                <button 
                    v-if="can('voucher_free_tab_generated') || can('menu_vouchers_free')"
                    @click="switchTab('vouchers')"
                    :class="currentTab === 'vouchers' 
                        ? 'bg-emerald-600 text-white shadow-xs font-bold' 
                        : 'text-gray-600 hover:text-emerald-700 hover:bg-emerald-50/60 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    <span>Voucher Gratis (Hasil Generate)</span>
                    <span 
                        :class="currentTab === 'vouchers' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-2 py-0.5 text-[11px] font-bold rounded-full"
                    >
                        {{ Number(stats?.total || 0).toLocaleString('id-ID') }}
                    </span>
                </button>

                <button 
                    v-if="can('voucher_free_tab_customers')"
                    @click="switchTab('customers')"
                    :class="currentTab === 'customers' 
                        ? 'bg-indigo-600 text-white shadow-xs font-bold' 
                        : 'text-gray-600 hover:text-indigo-700 hover:bg-indigo-50/60 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Hotspot Pelanggan ONT (Aktivasi)</span>
                    <span 
                        :class="currentTab === 'customers' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-2 py-0.5 text-[11px] font-bold rounded-full"
                    >
                        {{ Number(stats?.customer_count || 0).toLocaleString('id-ID') }}
                    </span>
                </button>
            </div>

            <!-- Action Buttons (Print & Generate) -->
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <!-- Bulk Print Button -->
                <button 
                    v-if="currentTab === 'vouchers' && selectedVouchers.length > 0 && can('vouchers_free_print')" 
                    @click="printSelected" 
                    class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs flex items-center gap-2 transition-colors animate-fade-in"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Cetak ({{ selectedVouchers.length }})</span>
                </button>

                <!-- Bulk Delete Button -->
                <button 
                    v-if="currentTab === 'vouchers' && selectedVouchers.length > 0 && can('vouchers_free_delete')" 
                    @click="bulkDelete" 
                    class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs flex items-center gap-2 transition-colors animate-fade-in"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Hapus ({{ selectedVouchers.length }})</span>
                </button>

                <!-- Main Generate Voucher Gratis Button -->
                <button 
                    v-if="can('vouchers_free_create')"
                    @click="openGenerateModal" 
                    class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-emerald-600/20 flex items-center gap-2 transition-all hover:scale-[1.02] active:scale-[0.98]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Generate Voucher Gratis</span>
                </button>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- SUB-TAB 1: DAFTAR VOUCHER GRATIS (HASIL GENERATE) -->
        <!-- ========================================================================================= -->
        <div v-if="currentTab === 'vouchers'" class="space-y-4">
            <!-- FILTER BAR -->
            <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[220px]">
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari kode / username / nama pelanggan..." 
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm shadow-2xs"
                            @keyup.enter="doFilterVouchers"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <select v-model="statusFilter" @change="doFilterVouchers" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Semua Status</option>
                        <option value="available">Tersedia</option>
                        <option value="used">Digunakan</option>
                        <option value="expired">Expired</option>
                    </select>

                    <!-- Profile Filter -->
                    <select v-model="profileFilter" @change="doFilterVouchers" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-emerald-500 focus:border-emerald-500 max-w-[220px]">
                        <option value="">Semua Profil</option>
                        <option v-for="prof in (freeProfiles?.length ? freeProfiles : allProfiles)" :key="prof.id" :value="prof.id">
                            {{ prof.name }} ({{ prof.duration || 'Unlimited' }})
                        </option>
                    </select>

                    <!-- Reset Filter Button -->
                    <button 
                        v-if="search || statusFilter || profileFilter" 
                        @click="resetVoucherFilter" 
                        class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                        title="Reset Filter"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- TABLE OF GENERATED FREE VOUCHERS -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-10">
                                    <input 
                                        type="checkbox" 
                                        @change="toggleSelectAll" 
                                        :checked="isAllSelected" 
                                        class="rounded border-gray-300 text-emerald-600 shadow-xs focus:ring-emerald-500"
                                    >
                                </th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode / User</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Password</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil Hotspot</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penerima / Pelanggan</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status & Sesi</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu Dibuat</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="voucher in vouchers.data" :key="voucher.id" class="hover:bg-emerald-50/20 transition-colors">
                                <td class="px-5 py-4">
                                    <input 
                                        type="checkbox" 
                                        v-model="selectedVouchers" 
                                        :value="voucher.id" 
                                        class="rounded border-gray-300 text-emerald-600 shadow-xs focus:ring-emerald-500"
                                    >
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-mono font-bold text-gray-900 tracking-wide">{{ voucher.code }}</span>
                                        <button 
                                            @click="copyText(voucher.code, 'Kode voucher')" 
                                            class="text-gray-400 hover:text-emerald-600 transition-colors p-1" 
                                            title="Salin Kode"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                    <div v-if="voucher.username && voucher.username !== voucher.code" class="text-xs text-gray-500 font-mono mt-0.5">
                                        User: {{ voucher.username }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            🎁 Gratis (Rp 0)
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-mono text-gray-700 bg-gray-50 px-2 py-1 rounded border border-gray-200">{{ voucher.password }}</span>
                                        <button 
                                            @click="copyText(voucher.password, 'Password')" 
                                            class="text-gray-400 hover:text-emerald-600 transition-colors p-1" 
                                            title="Salin Password"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ voucher.profile?.name || 'Profil Khusus' }}</div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                                        <span>Durasi: {{ voucher.profile?.duration || 'Mengikuti Pelanggan' }}</span>
                                        <span v-if="voucher.profile?.limit_rate" class="text-[10px] bg-purple-50 text-purple-700 font-mono px-1.5 py-0.2 rounded border border-purple-200">
                                            {{ voucher.profile.limit_rate }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div v-if="voucher.customer" class="flex flex-col items-start gap-1">
                                        <div class="text-sm font-semibold text-indigo-700 hover:underline">
                                            {{ voucher.customer.name }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-mono">
                                            ID: {{ voucher.customer.customer_id }}
                                        </div>
                                    </div>
                                    <div v-else-if="voucher.reseller" class="flex flex-col items-start gap-1">
                                        <span class="text-xs font-medium text-gray-700 bg-gray-100 px-2 py-0.5 rounded">
                                            Mitra: {{ voucher.reseller.customer?.name || ('Reseller #' + voucher.reseller.id) }}
                                        </span>
                                    </div>
                                    <div v-else>
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-600 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded-lg">
                                            <span>🏢 Voucher Bebas (Promosi/Trial)</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col items-start gap-1">
                                        <!-- Primary Status -->
                                        <span 
                                            v-if="voucher.status === 'available'" 
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800"
                                        >
                                            TERSEDIA
                                        </span>
                                        <span 
                                            v-else-if="voucher.status === 'used'" 
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800"
                                        >
                                            DIGUNAKAN
                                        </span>
                                        <span 
                                            v-else 
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800"
                                        >
                                            EXPIRED
                                        </span>

                                        <!-- RADIUS Active/Online Status -->
                                        <div class="mt-1">
                                            <span v-if="!voucher.is_active" class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 uppercase">
                                                Nonaktif
                                            </span>
                                            <span v-else-if="onlineUsernames.includes(voucher.username)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Sesi Aktif Online
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                Offline
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-gray-500">
                                    <div>{{ voucher.created_at ? new Date(voucher.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-' }}</div>
                                    <div v-if="voucher.used_at" class="text-[11px] text-gray-400 mt-0.5">
                                        Pakai: {{ new Date(voucher.used_at).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' }) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs font-semibold">
                                    <div class="flex items-center justify-end gap-2.5">
                                        <!-- Cetak Satuan -->
                                        <button 
                                            v-if="can('vouchers_free_print')"
                                            @click="printSingle(voucher.id)" 
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" 
                                            title="Cetak Voucher"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </button>

                                        <!-- Toggle Status -->
                                        <button 
                                            v-if="can('vouchers_free_toggle')"
                                            @click="toggleStatus(voucher)" 
                                            :disabled="toggling === voucher.id"
                                            :class="voucher.is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50'"
                                            class="p-1.5 rounded-lg transition-colors disabled:opacity-50"
                                            :title="voucher.is_active ? 'Nonaktifkan Voucher' : 'Aktifkan Voucher'"
                                        >
                                            <svg v-if="voucher.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>

                                        <!-- Delete -->
                                        <button 
                                            v-if="can('vouchers_free_delete')"
                                            @click="deleteVoucher(voucher.id)" 
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" 
                                            title="Hapus Voucher"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!vouchers.data || vouchers.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900 mb-1">Belum Ada Voucher Gratis yang Di-generate</h3>
                                        <p class="text-xs text-gray-500 mb-4">Mulai buat voucher hotspot gratis untuk kebutuhan promosi, trial kuota, atau pelanggan ONT dengan tombol di bawah.</p>
                                        <button 
                                            v-if="can('vouchers_free_create')"
                                            @click="openGenerateModal" 
                                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all"
                                        >
                                            + Generate Voucher Gratis Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION FOR VOUCHERS -->
                <div class="bg-gray-50/80 px-6 py-3.5 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3" v-if="vouchers.links && vouchers.links.length > 3">
                    <div class="text-xs text-gray-500">
                        Menampilkan <span class="font-bold text-gray-900">{{ vouchers.from || 0 }}</span> - <span class="font-bold text-gray-900">{{ vouchers.to || 0 }}</span> dari <span class="font-bold text-gray-900">{{ vouchers.total || 0 }}</span> voucher
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in vouchers.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                                :class="link.active ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-700 bg-white border border-gray-200 hover:bg-gray-100'"
                                v-html="link.label"
                            />
                            <span 
                                v-else 
                                class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 bg-gray-100 cursor-not-allowed" 
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- SUB-TAB 2: HOTSPOT PELANGGAN ONT (AKTIVASI) -->
        <!-- ========================================================================================= -->
        <div v-else-if="currentTab === 'customers'" class="space-y-4">
            <!-- INFO BANNER -->
            <div class="bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 border border-indigo-200 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-indigo-600 text-white rounded-xl shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-gray-900">Fasilitas Hotspot Gratis Pelanggan ONT</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-800 border border-indigo-200">Aktivasi ONT</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">Rp 0 / Bebas Biaya</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">
                            Akun hotspot ini dibuat otomatis saat aktivasi modem ONT pelanggan dan tersambung ke FreeRADIUS. Klik <b>"Sinkron / Buat Voucher"</b> untuk mencatatnya ke daftar voucher gratis resmi.
                        </p>
                    </div>
                </div>
                <Link href="/vouchers/profiles" class="text-xs font-bold text-indigo-700 hover:text-indigo-900 bg-white px-3.5 py-2 rounded-xl border border-indigo-200 shadow-2xs hover:bg-indigo-50 transition-colors whitespace-nowrap">
                    Kelola Profil Voucher &rarr;
                </Link>
            </div>

            <!-- SEARCH FILTER FOR CUSTOMERS -->
            <div class="flex justify-between items-center gap-3">
                <div class="relative flex-1 max-w-md">
                    <input 
                        v-model="searchCustomer" 
                        type="text" 
                        placeholder="Cari nama pelanggan, ID pelanggan, atau username hotspot..." 
                        class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-2xs"
                        @keyup.enter="doFilterCustomers"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button 
                    v-if="searchCustomer" 
                    @click="searchCustomer = ''; doFilterCustomers()" 
                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold"
                >
                    Reset
                </button>
            </div>

            <!-- TABLE OF CUSTOMERS WITH FREE HOTSPOT -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Pelanggan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID & Paket</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hotspot User</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hotspot Password</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">VLAN & ONT</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status & Masa Aktif</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-indigo-50/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ customer.name }}</div>
                                    <div class="text-xs text-gray-500">{{ customer.area_model?.name || customer.area || 'Area -' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-gray-700">{{ customer.customer_id }}</div>
                                    <div class="text-xs text-indigo-600 font-medium">{{ customer.package?.name || 'Paket Internet' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-mono font-bold text-gray-900">{{ customer.ont?.hotspot_user || '-' }}</span>
                                        <button 
                                            v-if="customer.ont?.hotspot_user" 
                                            @click="copyText(customer.ont.hotspot_user, 'Hotspot User')" 
                                            class="text-gray-400 hover:text-indigo-600 p-1" 
                                            title="Salin Username"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                    <span v-if="onlineUsernames.includes(customer.ont?.hotspot_user)" class="inline-flex items-center gap-1 text-[10px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200 mt-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Sedang Online
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-mono text-gray-700 bg-gray-50 px-2 py-0.5 rounded border border-gray-200">{{ customer.ont?.hotspot_password || '-' }}</span>
                                        <button 
                                            v-if="customer.ont?.hotspot_password" 
                                            @click="copyText(customer.ont.hotspot_password, 'Hotspot Password')" 
                                            class="text-gray-400 hover:text-indigo-600 p-1" 
                                            title="Salin Password"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-700">
                                        VLAN: <span class="font-mono font-bold">{{ customer.ont?.hotspot_vlan_id || '-' }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        Modem ONT
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="{
                                        'bg-emerald-100 text-emerald-800': customer.status === 'active',
                                        'bg-amber-100 text-amber-800': customer.status === 'suspended',
                                        'bg-blue-100 text-blue-800': customer.status === 'installing',
                                        'bg-purple-100 text-purple-800': customer.status === 'survey',
                                        'bg-gray-100 text-gray-800': !['active', 'suspended', 'installing', 'survey'].includes(customer.status)
                                    }">
                                        {{ 
                                            customer.status === 'active' ? 'Aktif' : 
                                            (customer.status === 'suspended' ? 'Isolir' : 
                                            (customer.status === 'installing' ? 'Instalasi' : 
                                            (customer.status === 'survey' ? 'Survey' : customer.status)))
                                        }}
                                    </span>
                                    <div class="text-[11px] text-gray-500 mt-1">
                                        Aktivasi: {{ customer.activation_date ? new Date(customer.activation_date).toLocaleDateString('id-ID') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold">
                                    <button 
                                        v-if="can('vouchers_free_sync_customer')"
                                        @click="syncVoucherForCustomer(customer)" 
                                        :disabled="syncingCustomer === customer.id"
                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold border border-indigo-200 transition-all flex items-center gap-1.5 ml-auto disabled:opacity-50"
                                    >
                                        <svg v-if="syncingCustomer === customer.id" class="animate-spin w-3.5 h-3.5 text-indigo-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <svg v-else class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>{{ syncingCustomer === customer.id ? 'Menyinkronkan...' : 'Sinkron / Buat Voucher' }}</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!customers.data || customers.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <p class="text-sm font-medium">Tidak ada data pelanggan dengan fasilitas Free Hotspot ONT.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION FOR CUSTOMERS -->
                <div class="bg-gray-50/80 px-6 py-3.5 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3" v-if="customers.links && customers.links.length > 3">
                    <div class="text-xs text-gray-500">
                        Menampilkan <span class="font-bold text-gray-900">{{ customers.from || 0 }}</span> - <span class="font-bold text-gray-900">{{ customers.to || 0 }}</span> dari <span class="font-bold text-gray-900">{{ customers.total || 0 }}</span> data pelanggan
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in customers.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                                :class="link.active ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-700 bg-white border border-gray-200 hover:bg-gray-100'"
                                v-html="link.label"
                            />
                            <span 
                                v-else 
                                class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 bg-gray-100 cursor-not-allowed" 
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- MODAL GENERATE VOUCHER GRATIS (DUAL-TAB: SINGLE / BULK) -->
        <!-- ========================================================================================= -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden animate-fade-in-up">
                    
                    <!-- MODAL HEADER -->
                    <div class="px-6 py-4 bg-gradient-to-r from-emerald-800 via-teal-800 to-slate-900 text-white flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20 text-emerald-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold tracking-tight text-white flex items-center gap-2">
                                    Generate Voucher Gratis
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold bg-emerald-500/30 text-emerald-200 rounded-full border border-emerald-400/30">Rp 0 / Bebas Biaya</span>
                                </h2>
                                <p class="text-xs text-emerald-200/80">Buat voucher gratis kustom satuan atau cetak voucher gratis massal</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- TAB SELECTOR -->
                    <div class="bg-gray-50 border-b border-gray-200 px-6 pt-3 flex gap-2 shrink-0">
                        <button 
                            type="button" 
                            @click="activeModalTab = 'single'"
                            :class="activeModalTab === 'single' ? 'bg-white text-emerald-700 border-emerald-600 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-700 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl border-b-2 flex items-center gap-2 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Single Voucher (Satuan)</span>
                            <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[10px] rounded-full font-bold">Kustom</span>
                        </button>
                        <button 
                            type="button" 
                            @click="activeModalTab = 'bulk'"
                            :class="activeModalTab === 'bulk' ? 'bg-white text-emerald-700 border-emerald-600 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-700 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl border-b-2 flex items-center gap-2 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Generate Massal (Batch)</span>
                            <span class="px-1.5 py-0.2 bg-teal-100 text-teal-800 text-[10px] rounded-full font-bold">Otomatis</span>
                        </button>
                    </div>

                    <!-- MODAL BODY -->
                    <div class="p-6 overflow-y-auto space-y-5">
                        
                        <!-- ================= TAB 1: SINGLE VOUCHER ================= -->
                        <form v-if="activeModalTab === 'single'" @submit.prevent="submitSingleForm" class="space-y-4">
                            
                            <!-- 1. Pilih Profil Paket Hotspot -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Pilih Profil Paket Hotspot <span class="text-red-500">*</span>
                                </label>
                                <select v-model="singleForm.voucher_profile_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 transition-all" required>
                                    <option value="" disabled>-- Pilih Profil Paket --</option>
                                    <option v-for="prof in availableProfiles" :key="prof.id" :value="prof.id">
                                        {{ prof.name }} &bull; Durasi: {{ prof.duration || 'Unlimited' }} &bull; Limit: {{ prof.limit_rate || 'Default' }} (Gratis)
                                    </option>
                                </select>
                            </div>

                            <!-- 2. Format Kredensial Login -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Format Kredensial Login <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_only'"
                                        :class="singleForm.login_type === 'username_only' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium'"
                                        class="px-3 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Kode Saja (Username = Password)</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_password'"
                                        :class="singleForm.login_type === 'username_password' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium'"
                                        class="px-3 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <span>Username & Password Terpisah</span>
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Input Username & Password -->
                            <div class="bg-gray-50/80 p-4 rounded-xl border border-gray-200 space-y-4">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-700">Username / Kode Voucher <span class="text-red-500">*</span></label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomUsername"
                                            class="text-xs text-emerald-600 hover:text-emerald-800 font-bold flex items-center gap-1 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Acak Kode</span>
                                        </button>
                                    </div>
                                    <input 
                                        v-model="singleForm.username" 
                                        type="text" 
                                        placeholder="Contoh: FREE-ABC123" 
                                        class="w-full border-gray-300 rounded-xl text-sm font-mono uppercase focus:ring-emerald-500 focus:border-emerald-500" 
                                        required
                                    >
                                </div>

                                <div v-if="singleForm.login_type === 'username_password'">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-700">Password <span class="text-red-500">*</span></label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomPassword"
                                            class="text-xs text-emerald-600 hover:text-emerald-800 font-bold flex items-center gap-1 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Acak Password</span>
                                        </button>
                                    </div>
                                    <input 
                                        v-model="singleForm.password" 
                                        type="text" 
                                        placeholder="Contoh: 849201" 
                                        class="w-full border-gray-300 rounded-xl text-sm font-mono focus:ring-emerald-500 focus:border-emerald-500" 
                                        required
                                    >
                                </div>
                            </div>

                            <!-- 4. Hubungkan ke Pelanggan (Opsional) -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Hubungkan ke Pelanggan (Opsional)
                                </label>
                                <select v-model="singleForm.customer_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">-- Bebas (Bukan untuk pelanggan tertentu) --</option>
                                    <option v-for="cust in customerList" :key="cust.id" :value="cust.id">
                                        {{ cust.name }} ({{ cust.customer_id }})
                                    </option>
                                </select>
                                <p class="text-[11px] text-gray-500 mt-1">
                                    Kosongkan jika voucher gratis ini ditujukan untuk tamu, trial promosi, atau publik.
                                </p>
                            </div>

                            <!-- Single Form Submit -->
                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    Batal
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="singleForm.processing"
                                    class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-200 transition-all disabled:opacity-50 flex items-center gap-2"
                                >
                                    <svg v-if="singleForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>{{ singleForm.processing ? 'Menyimpan...' : 'Buat Voucher Gratis Satuan' }}</span>
                                </button>
                            </div>
                        </form>

                        <!-- ================= TAB 2: BULK VOUCHER ================= -->
                        <form v-else-if="activeModalTab === 'bulk'" @submit.prevent="submitBulkForm" class="space-y-4">
                            
                            <!-- 1. Profil Paket -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Pilih Profil Paket Hotspot <span class="text-red-500">*</span>
                                </label>
                                <select v-model="bulkForm.voucher_profile_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                                    <option value="" disabled>-- Pilih Profil Paket --</option>
                                    <option v-for="prof in availableProfiles" :key="prof.id" :value="prof.id">
                                        {{ prof.name }} &bull; Durasi: {{ prof.duration || 'Unlimited' }} &bull; Limit: {{ prof.limit_rate || 'Default' }} (Gratis)
                                    </option>
                                </select>
                            </div>

                            <!-- 2. Jumlah Generate & Quick Pick -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Jumlah Generate <span class="text-red-500">*</span></label>
                                    <div class="flex gap-1.5">
                                        <button type="button" @click="bulkForm.amount = 5" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">5</button>
                                        <button type="button" @click="bulkForm.amount = 10" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">10</button>
                                        <button type="button" @click="bulkForm.amount = 25" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">25</button>
                                        <button type="button" @click="bulkForm.amount = 50" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">50</button>
                                        <button type="button" @click="bulkForm.amount = 100" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">100</button>
                                    </div>
                                </div>
                                <input v-model="bulkForm.amount" type="number" min="1" max="500" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                            </div>

                            <!-- 3. Prefix & Panjang Karakter -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Prefix Kode</label>
                                    <input v-model="bulkForm.prefix" type="text" maxlength="10" placeholder="FREE-" class="w-full border-gray-300 rounded-xl text-sm font-mono uppercase focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Panjang Karakter <span class="text-red-500">*</span></label>
                                    <input v-model="bulkForm.length" type="number" min="4" max="12" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                                </div>
                            </div>

                            <!-- 4. Format Tipe & Kombinasi Karakter -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tipe Voucher <span class="text-red-500">*</span></label>
                                    <select v-model="bulkForm.type" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="vc">Kode Saja (User = Pass)</option>
                                        <option value="up">Username & Password</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Kombinasi Karakter</label>
                                    <select v-model="bulkForm.combination" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="alphanumeric">Alfanumerik (Huruf & Angka)</option>
                                        <option value="numeric">Angka Saja (0-9)</option>
                                        <option value="alpha">Huruf Saja (A-Z)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit Bulk -->
                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    Batal
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="bulkForm.processing"
                                    class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-200 transition-all disabled:opacity-50 flex items-center gap-2"
                                >
                                    <svg v-if="bulkForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>{{ bulkForm.processing ? 'Generating...' : `Generate ${bulkForm.amount} Voucher Gratis` }}</span>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </Teleport>

    </component>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const page = usePage();
const { can, hasRole } = usePermission();
const layoutComponent = computed(() => {
    return page.props.auth?.user?.role === 'reseller' ? ClientAreaLayout : AppLayout;
});

const props = defineProps({
    vouchers: Object,
    customers: Object,
    freeProfile: Object,
    freeProfiles: Array,
    allProfiles: Array,
    resellers: Array,
    customerList: Array,
    onlineUsernames: {
        type: Array,
        default: () => []
    },
    stats: Object,
    filters: Object,
});

// Tab state
const currentTab = ref(props.filters?.tab || 'vouchers');
const switchTab = (tab) => {
    currentTab.value = tab;
    router.get('/vouchers/free', {
        tab: tab,
        search: search.value,
        status: statusFilter.value,
        profile_id: profileFilter.value,
        search_customer: searchCustomer.value,
    }, {
        preserveState: true,
        replace: true
    });
};

// Filter states for Vouchers
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const profileFilter = ref(props.filters?.profile_id || '');

const doFilterVouchers = () => {
    router.get('/vouchers/free', {
        tab: 'vouchers',
        search: search.value,
        status: statusFilter.value,
        profile_id: profileFilter.value,
    }, {
        preserveState: true,
        replace: true
    });
};

const filterByStatus = (status) => {
    currentTab.value = 'vouchers';
    if (statusFilter.value === status) {
        statusFilter.value = '';
    } else {
        statusFilter.value = status;
    }
    doFilterVouchers();
};

const resetVoucherFilter = () => {
    search.value = '';
    statusFilter.value = '';
    profileFilter.value = '';
    doFilterVouchers();
};

// Search filter for Customers tab
const searchCustomer = ref(props.filters?.search_customer || '');
const doFilterCustomers = () => {
    router.get('/vouchers/free', {
        tab: 'customers',
        search_customer: searchCustomer.value,
    }, {
        preserveState: true,
        replace: true
    });
};

// Selection and bulk actions
const selectedVouchers = ref([]);
const isAllSelected = computed(() => {
    const list = props.vouchers?.data || [];
    return list.length > 0 && selectedVouchers.value.length === list.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedVouchers.value = (props.vouchers?.data || []).map(v => v.id);
    } else {
        selectedVouchers.value = [];
    }
};

const printSelected = () => {
    if (selectedVouchers.value.length === 0) return;
    const url = `/vouchers/print?ids=${selectedVouchers.value.join(',')}`;
    window.open(url, '_blank');
};

const printSingle = (id) => {
    window.open(`/vouchers/print?ids=${id}`, '_blank');
};

const bulkDelete = () => {
    if (selectedVouchers.value.length === 0) return;
    Swal.fire({
        title: `Hapus ${selectedVouchers.value.length} Voucher Gratis?`,
        text: 'Voucher terpilih akan dihapus permanen dari sistem dan RADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Semua!'
    }).then((res) => {
        if (res.isConfirmed) {
            router.post('/vouchers/bulk-destroy', {
                ids: selectedVouchers.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedVouchers.value = [];
                    Swal.fire('Terhapus!', 'Voucher gratis terpilih berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const deleteVoucher = (id) => {
    Swal.fire({
        title: 'Hapus Voucher Gratis?',
        text: 'Voucher ini akan dihapus dari sistem dan FreeRADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!'
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(`/vouchers/${id}/delete`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Voucher gratis berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const toggling = ref(null);
const toggleStatus = (voucher) => {
    const action = voucher.is_active ? 'Nonaktifkan' : 'Aktifkan';
    if (!confirm(`${action} voucher ${voucher.code}?`)) return;
    toggling.value = voucher.id;
    router.post(`/vouchers/${voucher.id}/toggle-status`, {}, {
        preserveScroll: true,
        onFinish: () => (toggling.value = null)
    });
};

// 1-Click Sync/Generate for Customer ONT
const syncingCustomer = ref(null);
const syncVoucherForCustomer = (customer) => {
    syncingCustomer.value = customer.id;
    router.post(`/vouchers/free/customer/${customer.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            syncingCustomer.value = null;
            Swal.fire({
                icon: 'success',
                title: 'Tersinkronisasi!',
                text: `Voucher gratis untuk ${customer.name} berhasil dibuat / disinkronkan ke RADIUS.`,
            });
        },
        onError: () => {
            syncingCustomer.value = null;
            Swal.fire('Gagal', 'Terjadi kesalahan saat menyinkronkan voucher pelanggan.', 'error');
        }
    });
};

// Clipboard Helper
const copyText = (txt, label = 'Teks') => {
    if (!txt || txt === '-') return;
    navigator.clipboard.writeText(txt).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `${label} disalin ke clipboard!`,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        });
    });
};

// Modal and Forms
const showModal = ref(false);
const activeModalTab = ref('single');

const availableProfiles = computed(() => {
    if (props.freeProfiles && props.freeProfiles.length > 0) {
        return props.freeProfiles;
    }
    return props.allProfiles || [];
});

const defaultProfileId = computed(() => {
    return props.freeProfile?.id || availableProfiles.value[0]?.id || '';
});

// Helper Generate Random Chars
function getRandomChars(len = 6, numericOnly = false) {
    const chars = numericOnly ? '0123456789' : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let res = '';
    for (let i = 0; i < len; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return res;
}

function generateRandomUsername() {
    singleForm.username = 'FREE-' + getRandomChars(6);
}

function generateRandomPassword() {
    singleForm.password = getRandomChars(6);
}

const singleForm = useForm({
    mode: 'single',
    is_free: true,
    cost_type: 'free',
    voucher_profile_id: '',
    login_type: 'username_only',
    username: '',
    password: '',
    customer_id: '',
});

const bulkForm = useForm({
    mode: 'bulk',
    is_free: true,
    cost_type: 'free',
    voucher_profile_id: '',
    amount: 10,
    prefix: 'FREE-',
    length: 6,
    type: 'vc',
    combination: 'alphanumeric',
});

const openGenerateModal = () => {
    singleForm.reset();
    singleForm.clearErrors();
    singleForm.is_free = true;
    singleForm.cost_type = 'free';
    singleForm.voucher_profile_id = defaultProfileId.value;
    generateRandomUsername();

    bulkForm.reset();
    bulkForm.clearErrors();
    bulkForm.is_free = true;
    bulkForm.cost_type = 'free';
    bulkForm.voucher_profile_id = defaultProfileId.value;

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitSingleForm = () => {
    singleForm.is_free = true;
    singleForm.cost_type = 'free';
    singleForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dibuat',
                text: `Voucher gratis ${singleForm.username} berhasil di-generate.`,
            });
        }
    });
};

const submitBulkForm = () => {
    bulkForm.is_free = true;
    bulkForm.cost_type = 'free';
    bulkForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Generate Massal',
                text: `${bulkForm.amount} Voucher gratis berhasil di-generate dan tersimpan ke RADIUS.`,
            });
        }
    });
};
</script>
