<template>
    <Head title="Reseller Dashboard" />

    <ClientAreaLayout>
        <div class="space-y-6 pb-10">
            <!-- Header Section: Sederhana & Elegan -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200/60">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Reseller Aktif
                        </span>
                        <span class="text-xs text-gray-300">•</span>
                        <span class="text-xs font-medium text-gray-500">
                            {{ reseller.name || 'Mitra Reseller' }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Selamat Datang, {{ reseller.customer?.name || $page.props.auth.user.name }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Kelola stok voucher, pantau saldo modal, dan cetak voucher pelanggan dengan mudah.
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="bg-white border border-gray-200/80 rounded-xl px-3.5 py-2 text-xs font-medium text-gray-600 shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    </div>
                </div>
            </div>

            <!-- Alert Banner Jika Ada Kasbon Belum Lunas -->
            <div v-if="total_unpaid_kasbon > 0" class="bg-gradient-to-r from-rose-50 via-amber-50 to-orange-50 border border-rose-200/80 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs animate-fade-in-up">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-bold text-rose-950">Tagihan Kasbon Saldo Belum Lunas</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-200 text-rose-800 uppercase tracking-wide">Perlu Disetor</span>
                        </div>
                        <p class="text-xs text-rose-800 mt-0.5">
                            Total tagihan kasbon yang belum dibayar: <strong class="font-extrabold text-rose-950">Rp {{ Number(total_unpaid_kasbon).toLocaleString('id-ID') }}</strong>. Mohon segera setorkan ke kolektor atau kasir kantor.
                        </p>
                    </div>
                </div>
                <button @click="showKasbonModal = true" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-sm whitespace-nowrap cursor-pointer flex items-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Lihat Rincian Kasbon
                </button>
            </div>

            <!-- 4 Stat KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Sisa Saldo Modal -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Sisa Saldo Modal</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between gap-1">
                        <div class="flex items-baseline gap-1">
                            <span class="text-sm font-bold text-gray-400">Rp</span>
                            <span class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</span>
                        </div>
                        <button @click="showTopUpModal = true" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-bold shadow-xs transition cursor-pointer flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Isi Saldo
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        Saldo aktif cetak voucher
                    </p>
                </div>

                <!-- Tagihan Kasbon Saldo -->
                <div :class="total_unpaid_kasbon > 0 ? 'bg-rose-50/40 border-rose-200/80 hover:border-rose-300' : 'bg-white border-gray-200/80'" class="rounded-2xl border p-5 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider" :class="total_unpaid_kasbon > 0 ? 'text-rose-700' : 'text-gray-500'">Hutang Kasbon</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center border group-hover:scale-105 transition-transform" :class="total_unpaid_kasbon > 0 ? 'bg-rose-100 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-600 border-emerald-100'">
                            <svg v-if="total_unpaid_kasbon > 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between gap-1">
                        <div class="flex items-baseline gap-1">
                            <span class="text-sm font-bold" :class="total_unpaid_kasbon > 0 ? 'text-rose-400' : 'text-gray-400'">Rp</span>
                            <span class="text-2xl font-extrabold tracking-tight" :class="total_unpaid_kasbon > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ Number(total_unpaid_kasbon || 0).toLocaleString('id-ID') }}</span>
                        </div>
                        <button v-if="total_unpaid_kasbon > 0" @click="showKasbonModal = true" class="text-[11px] font-bold text-rose-600 hover:text-rose-800 underline cursor-pointer">
                            Rincian &rarr;
                        </button>
                    </div>
                    <p class="text-xs mt-2 flex items-center gap-1.5" :class="total_unpaid_kasbon > 0 ? 'text-rose-600 font-semibold' : 'text-gray-500'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="total_unpaid_kasbon > 0 ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'"></span>
                        {{ total_unpaid_kasbon > 0 ? 'Belum lunas (wajib setor)' : 'Tidak ada tunggakan kasbon' }}
                    </p>
                </div>

                <!-- Stok Voucher -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Stok Voucher</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ available_count || 0 }}</span>
                        <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">Keping</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Voucher siap dijual
                    </p>
                </div>

                <!-- Keuntungan Bersih Voucher (Laba) -->
                <div class="bg-gradient-to-br from-emerald-50/50 via-white to-white rounded-2xl border border-emerald-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Keuntungan Bersih Voucher</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center border border-emerald-200 group-hover:scale-105 transition-transform shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-sm font-bold text-gray-400">Rp</span>
                        <span class="text-2xl font-black text-emerald-600 tracking-tight">{{ Number(total_margin || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-emerald-100 flex items-center justify-between text-xs">
                        <span class="text-gray-500">Omzet Voucher Terjual:</span>
                        <span class="font-bold text-gray-800 font-mono">Rp {{ Number(income || 0).toLocaleString('id-ID') }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Generate Voucher Card (Modern Dual-Tab) -->
                <div class="xl:col-span-1">
                    <div class="bg-[#111327] rounded-2xl p-5 sm:p-6 shadow-xl border border-indigo-950 flex flex-col h-full text-gray-200">
                        <!-- Card Header -->
                        <div class="flex items-center gap-3.5 mb-5 pb-4 border-b border-indigo-900/60">
                            <div class="w-11 h-11 rounded-xl bg-[#1e213d] flex items-center justify-center text-indigo-300 border border-indigo-500/20 shadow-sm shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-bold text-white tracking-tight">Generator Voucher Hotspot</h2>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-indigo-500/20 text-indigo-300 rounded-full border border-indigo-400/30">Mikrotik / RADIUS</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">Buat voucher kustom satuan atau cetak voucher massal otomatis</p>
                            </div>
                        </div>

                        <!-- Tab Selector in Card -->
                        <div class="bg-[#191c36] p-1 rounded-xl flex gap-1 mb-5 border border-indigo-900/60 shrink-0">
                            <button 
                                type="button" 
                                @click="activeGeneratorTab = 'single'"
                                :class="activeGeneratorTab === 'single' ? 'bg-[#4f46e5] text-white shadow-md font-bold' : 'text-gray-400 hover:text-gray-200 font-medium'"
                                class="flex-1 py-2 text-xs rounded-lg flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Single (Satuan)</span>
                                <span class="px-1.5 py-0.2 bg-indigo-900/80 text-indigo-200 text-[9px] rounded-full">Kustom</span>
                            </button>
                            <button 
                                type="button" 
                                @click="activeGeneratorTab = 'bulk'"
                                :class="activeGeneratorTab === 'bulk' ? 'bg-[#4f46e5] text-white shadow-md font-bold' : 'text-gray-400 hover:text-gray-200 font-medium'"
                                class="flex-1 py-2 text-xs rounded-lg flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>Massal (Batch)</span>
                                <span class="px-1.5 py-0.2 bg-gray-800 text-gray-300 text-[9px] rounded-full">Otomatis</span>
                            </button>
                        </div>

                        <!-- TAB 1: SINGLE VOUCHER FORM -->
                        <form v-if="activeGeneratorTab === 'single'" @submit.prevent="submitSingleForm" class="space-y-4 flex-1 flex flex-col">
                            <!-- 1. Pilih Profil -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1.5">
                                    PILIH PROFIL PAKET HOTSPOT <span class="text-rose-400">*</span>
                                </label>
                                <div class="relative">
                                    <select v-model="singleForm.voucher_profile_id" class="w-full appearance-none bg-[#191c36] border border-indigo-900/80 rounded-xl text-sm font-semibold text-white p-3 pr-10 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer" required>
                                        <option value="" disabled class="bg-[#191c36] text-gray-400">-- Pilih Profil Paket --</option>
                                        <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id" class="bg-[#191c36] text-white">
                                            {{ prof.name }} • Durasi: {{ prof.duration || '-' }} • Tarif: Rp{{ Number(prof.price).toLocaleString('id-ID') }}
                                        </option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <div v-if="singleForm.errors.voucher_profile_id" class="text-rose-400 text-xs mt-1">{{ singleForm.errors.voucher_profile_id }}</div>
                            </div>

                            <!-- 2. Reseller Price Breakdown (Pengganti Gratis) -->
                            <div v-if="selectedSingleProfile" class="p-3.5 bg-[#191c36]/90 border border-indigo-900/70 rounded-xl space-y-2 text-xs">
                                <div class="flex justify-between items-center text-gray-400">
                                    <span>Tarif Jual Pelanggan</span>
                                    <span class="font-bold text-white">Rp {{ Number(selectedSingleProfile.price).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-gray-400">
                                    <span>Keuntungan Reseller (Fee)</span>
                                    <span class="font-bold text-emerald-400">+Rp {{ Number(selectedSingleProfile.fee_reseller || 0).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="h-px bg-indigo-900/50 w-full my-1"></div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="font-bold text-gray-200">Potong Saldo Modal</span>
                                    <span class="font-extrabold text-indigo-300 bg-indigo-950 border border-indigo-800/80 px-2 py-0.5 rounded-lg">
                                        Rp {{ Number(getProfileResellerCost(selectedSingleProfile)).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div v-if="getProfileResellerCost(selectedSingleProfile) > (reseller.balance || 0)" class="bg-rose-950/60 text-rose-300 p-2 rounded-lg text-[11px] font-semibold flex items-center gap-1.5 border border-rose-800/50 mt-1">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Saldo tidak cukup (Saldo: Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }})</span>
                                </div>
                            </div>

                            <!-- 3. Format Kredensial Login -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1.5">
                                    FORMAT KREDENSIAL LOGIN <span class="text-rose-400">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_only'"
                                        :class="singleForm.login_type === 'username_only' ? 'bg-[#4f46e5] text-white shadow-md font-bold' : 'bg-[#191c36] text-gray-300 border border-indigo-900/60 hover:bg-[#202344] font-medium'"
                                        class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Hanya Username</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_password'"
                                        :class="singleForm.login_type === 'username_password' ? 'bg-[#4f46e5] text-white shadow-md font-bold' : 'bg-[#191c36] text-gray-300 border border-indigo-900/60 hover:bg-[#202344] font-medium'"
                                        class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <span>Username & Password</span>
                                    </button>
                                </div>
                                <div class="mt-1.5 text-[11px] text-indigo-300/80 flex items-start gap-1">
                                    <span class="text-indigo-400">ℹ️</span>
                                    <span v-if="singleForm.login_type === 'username_only'">Mode Satu Kode: Cukup ketik Username (Password otomatis sama).</span>
                                    <span v-else>Mode Dua Kode: Login dengan Username & Password terpisah.</span>
                                </div>
                            </div>

                            <!-- 4. Username & Password Inputs -->
                            <div class="bg-[#15172e] p-3.5 rounded-xl border border-indigo-950 space-y-3">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-300">
                                            Username / Kode Login <span class="text-rose-400">*</span>
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomUsername"
                                            class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Acak Username</span>
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.username" 
                                            type="text" 
                                            placeholder="VCDCY85" 
                                            class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white font-mono font-bold tracking-wider text-base p-3 pr-10 focus:ring-2 focus:ring-indigo-500 uppercase transition-all" 
                                            required
                                        />
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                    </div>
                                    <div v-if="singleForm.errors.username" class="text-rose-400 text-xs mt-1">{{ singleForm.errors.username }}</div>
                                </div>

                                <div v-if="singleForm.login_type === 'username_password'">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-300">
                                            Password Login <span class="text-rose-400">*</span>
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomPassword"
                                            class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Acak Password</span>
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.password" 
                                            :type="showSinglePassword ? 'text' : 'password'" 
                                            placeholder="Ketik password..." 
                                            class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white font-mono text-sm p-3 pr-10 focus:ring-2 focus:ring-indigo-500 transition-all" 
                                            required
                                        />
                                        <button 
                                            type="button" 
                                            @click="showSinglePassword = !showSinglePassword"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-200 cursor-pointer"
                                        >
                                            <svg v-if="!showSinglePassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Data Pembeli (Opsional) -->
                            <div class="bg-[#15172e] p-3 rounded-xl border border-indigo-950 space-y-2.5">
                                <span class="text-[11px] font-bold text-emerald-400 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    Data Pembeli (Opsional)
                                </span>
                                <div class="grid grid-cols-2 gap-2">
                                    <input 
                                        v-model="singleForm.buyer_name" 
                                        type="text" 
                                        placeholder="Nama Pembeli" 
                                        class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-xs p-2.5 focus:ring-2 focus:ring-emerald-500" 
                                    />
                                    <input 
                                        v-model="singleForm.buyer_phone" 
                                        type="text" 
                                        placeholder="No. WhatsApp" 
                                        class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-xs p-2.5 focus:ring-2 focus:ring-emerald-500" 
                                    />
                                </div>
                            </div>

                            <!-- Submit Single Button -->
                            <button 
                                type="submit" 
                                class="w-full py-3.5 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:brightness-110 active:brightness-95 text-white rounded-xl font-bold text-sm transition-all shadow-[0_0_20px_rgba(99,102,241,0.4)] disabled:opacity-50 disabled:cursor-not-allowed mt-auto flex justify-center items-center gap-2 cursor-pointer"
                                :disabled="singleForm.processing || !singleForm.voucher_profile_id || !singleForm.username || (getProfileResellerCost(selectedSingleProfile) > (reseller.balance || 0))"
                            >
                                <svg v-if="singleForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ singleForm.processing ? 'Memproses...' : 'Buat 1 Voucher' }}
                            </button>
                        </form>

                        <!-- TAB 2: BULK / MASSAL FORM -->
                        <form v-else @submit.prevent="submitBulkForm" class="space-y-4 flex-1 flex flex-col">
                            <!-- 1. Pilih Profil -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1.5">
                                    PILIH PROFIL PAKET HOTSPOT <span class="text-rose-400">*</span>
                                </label>
                                <div class="relative">
                                    <select v-model="bulkForm.voucher_profile_id" class="w-full appearance-none bg-[#191c36] border border-indigo-900/80 rounded-xl text-sm font-semibold text-white p-3 pr-10 focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer" required>
                                        <option value="" disabled class="bg-[#191c36] text-gray-400">-- Pilih Profil Paket --</option>
                                        <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id" class="bg-[#191c36] text-white">
                                            {{ prof.name }} • Modal: Rp{{ Number(getProfileResellerCost(prof)).toLocaleString('id-ID') }}
                                        </option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Jumlah Generate & Quick Picks -->
                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-300">
                                        JUMLAH VOUCHER (QTY) <span class="text-rose-400">*</span>
                                    </label>
                                    <div class="flex gap-1">
                                        <button 
                                            v-for="amt in [10, 25, 50, 100]" 
                                            :key="amt"
                                            type="button" 
                                            @click="bulkForm.amount = amt"
                                            :class="bulkForm.amount === amt ? 'bg-indigo-600 text-white font-black' : 'bg-[#191c36] text-gray-400 hover:text-white'"
                                            class="px-2 py-0.5 rounded text-[10px] border border-indigo-900/60 transition-colors cursor-pointer"
                                        >
                                            {{ amt }}
                                        </button>
                                    </div>
                                </div>
                                <input 
                                    v-model.number="bulkForm.amount" 
                                    type="number" 
                                    min="1" 
                                    max="500" 
                                    class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white font-bold text-base p-3 focus:ring-2 focus:ring-indigo-500" 
                                    required 
                                />
                            </div>

                            <!-- 3. Format Kredensial Login & Kombinasi -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1">Tipe Login</label>
                                    <select v-model="bulkForm.type" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-xs p-2.5">
                                        <option value="vc">Satu Kode (vc)</option>
                                        <option value="up">User & Pass (up)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1">Kombinasi</label>
                                    <select v-model="bulkForm.combination" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-xs p-2.5">
                                        <optgroup label="── Populer & Standar ──" class="bg-[#191c36] text-gray-400">
                                            <option value="alphanumeric" class="text-white">Huruf Besar & Angka (A1B2C)</option>
                                            <option value="alphanumeric_lower" class="text-white">Huruf Kecil & Angka (a1b2c)</option>
                                            <option value="alphanumeric_mixed" class="text-white">Campur Huruf Besar, Kecil & Angka (aB1c2)</option>
                                            <option value="numeric" class="text-white">Hanya Angka (12345)</option>
                                        </optgroup>
                                        <optgroup label="── Anti Salah Ketik (Hotspot) ──" class="bg-[#191c36] text-gray-400">
                                            <option value="clean_readable" class="text-white">Mudah Dibaca Kapital (Tanpa 0, O, 1, I, L)</option>
                                            <option value="clean_readable_lower" class="text-white">Mudah Dibaca Kecil (Tanpa 0, o, 1, i, l)</option>
                                        </optgroup>
                                        <optgroup label="── Hanya Huruf ──" class="bg-[#191c36] text-gray-400">
                                            <option value="alpha" class="text-white">Hanya Huruf Besar (ABCDE)</option>
                                            <option value="alpha_lower" class="text-white">Hanya Huruf Kecil (abcdef)</option>
                                        </optgroup>
                                        <optgroup label="── Pola Unik & Mudah Diingat ──" class="bg-[#191c36] text-gray-400">
                                            <option value="consonant_vowel" class="text-white">Mudah Dieja: Konsonan-Vokal (BADE84)</option>
                                            <option value="words_numeric" class="text-white">Kata Indonesia + Angka (KOPI25, CERIA78)</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <!-- 4. Prefix & Panjang -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1">Prefix (Opsional)</label>
                                    <input v-model="bulkForm.prefix" type="text" maxlength="4" placeholder="VC" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-xs p-2.5 uppercase font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1">Panjang Karakter</label>
                                    <input v-model.number="bulkForm.length" type="number" min="4" max="12" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-xs p-2.5 font-bold">
                                </div>
                            </div>

                            <!-- 5. Total Biaya Modal & Estimasi Keuntungan -->
                            <div v-if="selectedBulkProfile" class="p-3.5 bg-[#191c36]/90 border border-indigo-900/70 rounded-xl space-y-2 text-xs">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="font-bold text-gray-200">Total Potong Modal</span>
                                    <span class="font-extrabold text-indigo-300 bg-indigo-950 border border-indigo-800/80 px-2 py-0.5 rounded-lg">
                                        Rp {{ Number(getTotalBulkCost).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-emerald-400 font-semibold bg-emerald-950/30 px-2 py-1 rounded-lg border border-emerald-900/40">
                                    <span>Estimasi Keuntungan</span>
                                    <span class="font-bold">+Rp {{ Number(getTotalBulkProfit).toLocaleString('id-ID') }}</span>
                                </div>
                                <div v-if="getTotalBulkCost > (reseller.balance || 0)" class="bg-rose-950/60 text-rose-300 p-2 rounded-lg text-[11px] font-semibold flex items-center gap-1.5 border border-rose-800/50 mt-1">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Saldo tidak cukup (Saldo: Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }})</span>
                                </div>
                            </div>

                            <!-- Submit Bulk Button -->
                            <button 
                                type="submit" 
                                class="w-full py-3.5 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:brightness-110 active:brightness-95 text-white rounded-xl font-bold text-sm transition-all shadow-[0_0_20px_rgba(99,102,241,0.4)] disabled:opacity-50 disabled:cursor-not-allowed mt-auto flex justify-center items-center gap-2 cursor-pointer"
                                :disabled="bulkForm.processing || !bulkForm.voucher_profile_id || !bulkForm.amount || (getTotalBulkCost > (reseller.balance || 0))"
                            >
                                <svg v-if="bulkForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ bulkForm.processing ? 'Sedang Memproses...' : `Generate ${bulkForm.amount} Voucher` }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- History / Table -->
                <div class="xl:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden flex flex-col h-full">
                        <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 tracking-tight">Riwayat Voucher</h2>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Daftar voucher yang telah Anda generate.</p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button 
                                    type="button"
                                    @click="openGenerateModal"
                                    class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm shadow-indigo-200 cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ Generate Voucher</span>
                                </button>
                                <Link 
                                    :href="route('vouchers.index')" 
                                    class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-2xs"
                                >
                                    <span>Semua Voucher &rarr;</span>
                                </Link>
                                <button 
                                    @click="printSelected" 
                                    class="px-4 py-2 bg-slate-800 hover:bg-slate-900 active:bg-black text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                    :disabled="vouchers.data.length === 0"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    {{ selectedVouchers.length > 0 ? `Print Terpilih (${selectedVouchers.length})` : 'Print Semua Voucher' }}
                                </button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto flex-1">
                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                <thead class="bg-gray-50/75">
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-center sm:pl-6 w-12">
                                            <input 
                                                type="checkbox" 
                                                :checked="isAllSelected" 
                                                @change="toggleSelectAll"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer"
                                                :disabled="vouchers.data.length === 0"
                                            />
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Kode</th>
                                        <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Paket</th>
                                        <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Harga Jual</th>
                                        <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                                        <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Tanggal</th>
                                        <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="voucher in vouchers.data" :key="voucher.id" class="hover:bg-slate-50/70 transition-colors" :class="{ 'bg-indigo-50/30': selectedVouchers.includes(voucher.id) }">
                                        <td class="py-3.5 pl-4 pr-3 text-center sm:pl-6">
                                            <input 
                                                type="checkbox" 
                                                :value="voucher.id" 
                                                v-model="selectedVouchers"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer"
                                            />
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-sm font-bold font-mono text-gray-900 tracking-wider">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                                </div>
                                                <div>
                                                    <div>{{ voucher.code }}</div>
                                                    <div v-if="voucher.buyer_name" class="text-[11px] font-sans font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 w-fit mt-0.5 flex items-center gap-1">
                                                        👤 {{ voucher.buyer_name }} <span v-if="voucher.buyer_phone" class="text-gray-500 font-mono text-[10px]">({{ voucher.buyer_phone }})</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-sm font-medium text-gray-700">
                                            {{ voucher.profile ? voucher.profile.name : '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-sm font-bold text-gray-900">
                                            Rp {{ Number(voucher.profile ? voucher.profile.price : 0).toLocaleString('id-ID') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5">
                                            <span v-if="voucher.status === 'available'" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Tersedia
                                            </span>
                                            <span v-else-if="voucher.status === 'used'" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 border border-amber-200/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Terpakai
                                            </span>
                                            <span v-else class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 border border-gray-200">
                                                {{ voucher.status }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-xs font-medium text-gray-500">
                                            {{ new Date(voucher.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                        </td>
                                        <td class="whitespace-nowrap py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-medium">
                                            <button 
                                                type="button"
                                                @click="openBuyerModal(voucher)" 
                                                :class="voucher.buyer_name ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-300' : 'text-gray-700 bg-gray-50 hover:bg-gray-100 border-gray-300'"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border shadow-2xs transition-all cursor-pointer"
                                                title="Kirim ke WhatsApp & Beri Nama Pembeli"
                                            >
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                                <span>{{ voucher.buyer_name ? 'Kirim WA' : '+ Nama/WA' }}</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="vouchers.data.length === 0">
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                <p class="text-sm font-bold text-gray-700">Belum ada voucher</p>
                                                <p class="text-xs text-gray-400 mt-0.5">Voucher yang Anda generate akan muncul di daftar ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="vouchers.links && vouchers.links.length > 3" class="px-6 py-4 bg-white border-t border-gray-100">
                            <div class="flex flex-wrap gap-1.5 justify-center sm:justify-start">
                                <template v-for="(link, p) in vouchers.links" :key="p">
                                    <div v-if="link.url === null" class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-100 rounded-lg bg-gray-50" v-html="link.label" />
                                    <Link v-else :href="link.url" class="px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-indigo-50 focus:border-indigo-500 focus:text-indigo-600 transition-colors shadow-sm" :class="{ 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700': link.active, 'border-gray-200 text-gray-600 bg-white': !link.active }" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Rincian Tagihan Kasbon Saldo -->
        <Teleport to="body">
            <div v-if="showKasbonModal" class="fixed inset-0 z-[110] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showKasbonModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-fade-in-up my-auto z-10">
                    
                    <!-- Header Modal -->
                    <div class="p-5 sm:p-6 border-b border-gray-100 bg-gradient-to-r from-rose-50/70 via-amber-50/50 to-white flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-500/20 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-gray-900">Rincian Tagihan Kasbon Saldo</h3>
                                <p class="text-xs text-gray-500">Daftar invoice kasbon yang belum lunas atau masih memiliki sisa tagihan</p>
                            </div>
                        </div>
                        <button @click="showKasbonModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-4 flex-1">
                        <!-- Summary Ringkasan -->
                        <div class="p-4 bg-rose-50/80 rounded-2xl border border-rose-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-rose-800 uppercase tracking-wider block">Total Tagihan Kasbon Belum Lunas</span>
                                <span class="text-2xl font-black text-rose-600 font-mono">Rp {{ Number(total_unpaid_kasbon || 0).toLocaleString('id-ID') }}</span>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                {{ unpaid_kasbon_invoices?.length || 0 }} Invoice
                            </span>
                        </div>

                        <!-- Petunjuk Penyetoran -->
                        <div class="p-3.5 bg-blue-50/70 rounded-xl border border-blue-100 text-xs text-blue-800 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>
                                Penyetoran dapat dilakukan secara tunai kepada <strong>petugas kolektor lapangan</strong> atau langsung di kasir kantor APM. Pembayaran akan otomatis mengurangi tagihan ini setelah diverifikasi.
                            </span>
                        </div>

                        <!-- Tabel Invoice Kasbon -->
                        <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-2xs">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">No. Invoice & Tanggal</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Jatuh Tempo</th>
                                        <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Total Kasbon</th>
                                        <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Sisa Tagihan</th>
                                        <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100 text-xs">
                                    <tr v-for="inv in unpaid_kasbon_invoices" :key="inv.id" class="hover:bg-gray-50/50">
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-900 font-mono">{{ inv.invoice_number }}</div>
                                            <div class="text-[10px] text-gray-400 mt-0.5">{{ inv.issued_date }}</div>
                                            <div v-if="inv.notes" class="text-[10px] text-gray-500 italic mt-0.5 line-clamp-1">{{ inv.notes }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-block px-2 py-0.5 bg-amber-50 text-amber-700 font-bold rounded text-[11px] border border-amber-200/60">
                                                {{ inv.due_date }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-600 font-mono">
                                            Rp {{ Number(inv.amount).toLocaleString('id-ID') }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-black text-rose-600 font-mono">
                                            Rp {{ Number(inv.remaining).toLocaleString('id-ID') }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <Link :href="route('payment-gateway.pay', inv.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl font-bold text-xs shadow-xs transition cursor-pointer">
                                                <span>Bayar</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!unpaid_kasbon_invoices || unpaid_kasbon_invoices.length === 0">
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 italic">
                                            Tidak ada tagihan kasbon yang belum lunas.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3 shrink-0">
                        <span class="text-xs text-gray-500">Status kasbon diperbarui secara otomatis.</span>
                        <button @click="showKasbonModal = false" class="px-5 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </Teleport>

        <!-- Modal Top Up Saldo Mandiri Reseller -->
        <Teleport to="body">
            <div v-if="showTopUpModal" class="fixed inset-0 z-[110] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showTopUpModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden animate-fade-in-up my-auto z-10">
                    
                    <!-- Header Modal Top Up (Always visible at top) -->
                    <div class="p-5 sm:p-6 border-b border-gray-100 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-white flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-gray-900">Isi Saldo Dompet Modal</h3>
                                <p class="text-xs text-gray-500">Top up instan otomatis via QRIS & Virtual Account</p>
                            </div>
                        </div>
                        <button @click="showTopUpModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Form with scrollable body and pinned footer -->
                    <form @submit.prevent="submitTopUp" class="flex flex-col flex-1 overflow-hidden min-h-0">
                        <!-- Scrollable Body Content -->
                        <div class="p-5 sm:p-6 space-y-4 sm:space-y-5 overflow-y-auto flex-1">
                            <!-- Saldo Saat Ini -->
                            <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-500">Saldo Dompet Saat Ini</span>
                                <span class="text-sm font-black text-gray-900 font-mono">Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</span>
                            </div>

                            <!-- Pilihan Nominal Cepat -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">Pilih Nominal Cepat</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button 
                                        v-for="amt in presetAmounts" 
                                        :key="amt"
                                        type="button"
                                        @click="topUpForm.amount = amt"
                                        :class="topUpForm.amount == amt ? 'bg-blue-600 text-white border-blue-600 ring-2 ring-blue-500/30' : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300 hover:bg-blue-50/50'"
                                        class="py-2.5 px-3 rounded-xl border text-xs font-black transition cursor-pointer text-center"
                                    >
                                        Rp {{ (amt / 1000).toLocaleString('id-ID') }}k
                                    </button>
                                </div>
                            </div>

                            <!-- Nominal Custom -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Atau Masukkan Nominal Sendiri</label>
                                <div class="relative rounded-2xl">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold text-sm">
                                        Rp
                                    </div>
                                    <input 
                                        v-model.number="topUpForm.amount" 
                                        type="number" 
                                        min="10000" 
                                        max="10000000" 
                                        step="5000"
                                        required
                                        class="w-full bg-gray-50/70 border border-gray-300 text-gray-900 text-base font-extrabold rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white block pl-11 pr-4 py-3 shadow-xs transition" 
                                        placeholder="50000"
                                    />
                                </div>
                                <span class="text-[11px] text-gray-400 mt-1 block">Minimal pengisian saldo: Rp 10.000</span>
                            </div>

                            <!-- Pilihan Saluran Pembayaran -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">Metode Pembayaran</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label 
                                        v-for="ch in paymentChannels" 
                                        :key="ch.id"
                                        :class="topUpForm.channel === ch.id ? 'border-blue-600 bg-blue-50/60 ring-2 ring-blue-500/30' : 'border-gray-200 bg-white hover:border-gray-300'"
                                        class="p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition"
                                    >
                                        <input 
                                            type="radio" 
                                            v-model="topUpForm.channel" 
                                            :value="ch.id" 
                                            class="text-blue-600 focus:ring-blue-500 h-4 w-4 border-gray-300"
                                        />
                                        <div>
                                            <div class="text-xs font-bold text-gray-900">{{ ch.name }}</div>
                                            <div class="text-[10px] text-gray-500">{{ ch.desc }}</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Info Otomatisasi -->
                            <div class="p-3 bg-emerald-50/80 border border-emerald-200/70 rounded-xl text-xs text-emerald-800 flex items-start gap-2 leading-relaxed">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Setelah Anda menyelesaikan pembayaran, <strong>saldo dompet akan langsung otomatis bertambah</strong> secara real-time tanpa perlu konfirmasi manual admin.</span>
                            </div>
                        </div>

                        <!-- Action Buttons Footer (Pinned at bottom, always visible) -->
                        <div class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/90 flex items-center justify-end gap-3 shrink-0">
                            <button 
                                type="button" 
                                @click="showTopUpModal = false" 
                                class="px-4 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="topUpForm.processing || !topUpForm.amount || topUpForm.amount < 10000"
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <svg v-if="topUpForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ topUpForm.processing ? 'Memproses...' : 'Lanjut ke Pembayaran' }}</span>
                                <svg v-if="!topUpForm.processing" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </Teleport>

        <!-- MODAL BERI NAMA & KIRIM WHATSAPP -->
        <Teleport to="body">
            <div v-if="showBuyerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-lg max-h-[92vh] flex flex-col overflow-hidden animate-fade-in-up">
                    
                    <!-- HEADER -->
                    <div class="px-6 py-4 bg-gradient-to-r from-emerald-800 to-teal-900 text-white flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20 text-emerald-300">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-white flex items-center gap-2">
                                    Kirim Voucher via WhatsApp
                                </h2>
                                <p class="text-xs text-emerald-100">Beri nama pembeli & kirim rincian login ke WhatsApp</p>
                            </div>
                        </div>
                        <button @click="closeBuyerModal" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="p-6 overflow-y-auto space-y-4">
                        <!-- Info Voucher Card -->
                        <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Voucher Dipilih</span>
                                    <div class="text-lg font-mono font-extrabold text-gray-900 mt-0.5">{{ selectedVoucherForBuyer?.code }}</div>
                                    <div v-if="selectedVoucherForBuyer?.password && selectedVoucherForBuyer?.password !== selectedVoucherForBuyer?.code" class="text-xs text-gray-600 font-mono">
                                        Password: <strong class="text-indigo-600">{{ selectedVoucherForBuyer?.password }}</strong>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-100 text-indigo-700">
                                        {{ selectedVoucherForBuyer?.profile?.name || '-' }}
                                    </span>
                                    <div class="text-xs font-semibold text-gray-600 mt-1">
                                        {{ selectedVoucherForBuyer?.profile?.duration || '-' }}
                                    </div>
                                    <div class="text-xs font-bold text-emerald-600 mt-0.5">
                                        Rp {{ Number(selectedVoucherForBuyer?.profile?.price || 0).toLocaleString('id-ID') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Nama & WhatsApp -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Nama Pembeli <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model="buyerForm.buyer_name" 
                                    type="text" 
                                    placeholder="Contoh: Budi Santoso" 
                                    class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" 
                                    required
                                />
                                <p class="text-[11px] text-gray-400 mt-1">Jika pembeli lupa kodenya, Anda bisa mencari langsung dengan nama ini di menu Semua Voucher.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    No. WhatsApp Pembeli
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="buyerForm.buyer_phone" 
                                        type="text" 
                                        placeholder="Contoh: 081234567890 atau 6281234567890" 
                                        class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" 
                                    />
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Jika diisi, WhatsApp akan langsung terbuka ke nomor pembeli.</p>
                            </div>
                        </div>

                        <!-- Preview Pesan WhatsApp -->
                        <div class="bg-emerald-50/60 rounded-xl p-3.5 border border-emerald-200">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                    <span>💬 Preview Pesan WhatsApp</span>
                                </span>
                                <button 
                                    type="button" 
                                    @click="copyWaMessage" 
                                    class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1 bg-white px-2 py-0.5 rounded border border-emerald-300 transition-colors cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    <span>Salin Teks</span>
                                </button>
                            </div>
                            <pre class="text-[11px] text-gray-700 font-sans whitespace-pre-wrap bg-white/80 p-2.5 rounded-lg border border-emerald-100 max-h-40 overflow-y-auto leading-relaxed">{{ previewWaMessage }}</pre>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-2 shrink-0">
                        <button type="button" @click="closeBuyerModal" class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer">
                            Tutup
                        </button>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button 
                                type="button" 
                                @click="saveBuyerOnly" 
                                :disabled="buyerForm.processing"
                                class="flex-1 sm:flex-initial px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all disabled:opacity-50 cursor-pointer"
                            >
                                {{ buyerForm.processing ? 'Menyimpan...' : 'Hanya Simpan Nama' }}
                            </button>
                            <button 
                                type="button" 
                                @click="sendViaWhatsApp" 
                                :disabled="buyerForm.processing"
                                class="flex-1 sm:flex-initial px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-200 hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                <span>Kirim ke WhatsApp</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </Teleport>

        <!-- MODAL GENERATOR VOUCHER HOTSPOT (MODERN DUAL-TAB MATCHING USER SCREENSHOT) -->
        <Teleport to="body">
            <div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/80 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="relative bg-[#111327] border border-indigo-900/80 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden animate-fade-in-up text-gray-200">
                    
                    <!-- MODAL HEADER -->
                    <div class="px-6 py-4 bg-[#111327] border-b border-indigo-950 flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-[#1e213d] flex items-center justify-center border border-indigo-500/20 text-indigo-300 shadow-sm shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-bold tracking-tight text-white">Generator Voucher Hotspot</h2>
                                    <span class="px-2.5 py-0.5 text-[10px] font-semibold bg-indigo-500/20 text-indigo-300 rounded-full border border-indigo-400/30">Mikrotik / RADIUS</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">Buat voucher kustom satuan atau cetak voucher massal otomatis</p>
                            </div>
                        </div>
                        <button @click="closeGenerateModal" class="w-9 h-9 rounded-xl bg-[#1e213d] hover:bg-[#282c50] text-gray-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- TAB SELECTOR -->
                    <div class="bg-[#15172e] border-b border-indigo-950 px-6 pt-3 flex gap-2 shrink-0">
                        <button 
                            type="button" 
                            @click="activeGeneratorTab = 'single'"
                            :class="activeGeneratorTab === 'single' ? 'bg-[#222649] text-white border-b-2 border-indigo-500 shadow-sm font-bold' : 'text-gray-400 hover:text-gray-200 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl flex items-center gap-2 transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Single Voucher (Satuan)</span>
                            <span class="px-1.5 py-0.2 bg-indigo-900/60 text-indigo-300 border border-indigo-700/50 text-[10px] rounded-full">Kustom</span>
                        </button>
                        <button 
                            type="button" 
                            @click="activeGeneratorTab = 'bulk'"
                            :class="activeGeneratorTab === 'bulk' ? 'bg-[#222649] text-white border-b-2 border-indigo-500 shadow-sm font-bold' : 'text-gray-400 hover:text-gray-200 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl flex items-center gap-2 transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Generate Massal (Batch)</span>
                            <span class="px-1.5 py-0.2 bg-gray-800 text-gray-400 text-[10px] rounded-full">Otomatis</span>
                        </button>
                    </div>

                    <!-- MODAL BODY -->
                    <div class="p-6 overflow-y-auto space-y-5">
                        
                        <!-- TAB 1: SINGLE VOUCHER -->
                        <form v-if="activeGeneratorTab === 'single'" @submit.prevent="submitSingleForm" class="space-y-5">
                            <!-- 1. PILIH PROFIL -->
                            <div>
                                <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1.5">
                                    PILIH PROFIL PAKET HOTSPOT <span class="text-rose-400">*</span>
                                </label>
                                <div class="relative">
                                    <select v-model="singleForm.voucher_profile_id" class="w-full appearance-none bg-[#191c36] border border-indigo-900/80 rounded-xl text-sm font-semibold text-white p-3.5 pr-10 focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer" required>
                                        <option value="" disabled class="bg-[#191c36] text-gray-400">-- Pilih Profil Paket --</option>
                                        <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id" class="bg-[#191c36] text-white">
                                            {{ prof.name }} • Durasi: {{ prof.duration || '-' }} • Tarif: Rp{{ Number(prof.price).toLocaleString('id-ID') }}
                                        </option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <div v-if="singleForm.errors.voucher_profile_id" class="text-rose-400 text-xs mt-1">{{ singleForm.errors.voucher_profile_id }}</div>
                            </div>

                            <!-- 2. RESELLER FINANCIAL BREAKDOWN (Pengganti Gratis, disesuaikan untuk reseller) -->
                            <div v-if="selectedSingleProfile" class="p-4 bg-[#191c36]/90 border border-indigo-900/80 rounded-xl space-y-2.5 text-xs">
                                <div class="flex justify-between items-center text-gray-400">
                                    <span>Tarif Jual ke Pelanggan</span>
                                    <span class="font-bold text-white text-sm">Rp {{ Number(selectedSingleProfile.price).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-gray-400">
                                    <span>Keuntungan Reseller (Fee)</span>
                                    <span class="font-bold text-emerald-400">+Rp {{ Number(selectedSingleProfile.fee_reseller || 0).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="h-px bg-indigo-900/60 w-full my-1"></div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="font-bold text-gray-200">Potong Saldo Modal</span>
                                    <span class="font-extrabold text-indigo-300 bg-indigo-950/80 border border-indigo-800/80 px-2.5 py-1 rounded-lg">
                                        Rp {{ Number(getProfileResellerCost(selectedSingleProfile)).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div v-if="getProfileResellerCost(selectedSingleProfile) > (reseller.balance || 0)" class="bg-rose-950/60 text-rose-300 p-2.5 rounded-lg text-xs font-semibold flex items-center gap-2 border border-rose-800/50 mt-1">
                                    <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Saldo tidak mencukupi! Butuh Rp {{ Number(getProfileResellerCost(selectedSingleProfile)).toLocaleString('id-ID') }} (Saldo Anda: Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}).</span>
                                </div>
                            </div>

                            <!-- 3. FORMAT KREDENSIAL LOGIN -->
                            <div>
                                <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-2">
                                    FORMAT KREDENSIAL LOGIN <span class="text-rose-400">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_only'"
                                        :class="singleForm.login_type === 'username_only' ? 'bg-[#4f46e5] text-white shadow-lg shadow-indigo-600/30 font-bold border-transparent' : 'bg-[#191c36] text-gray-300 border border-indigo-900/60 hover:bg-[#202344] font-medium'"
                                        class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2.5 transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Hanya Username</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_password'"
                                        :class="singleForm.login_type === 'username_password' ? 'bg-[#4f46e5] text-white shadow-lg shadow-indigo-600/30 font-bold border-transparent' : 'bg-[#191c36] text-gray-300 border border-indigo-900/60 hover:bg-[#202344] font-medium'"
                                        class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2.5 transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <span>Username & Password</span>
                                    </button>
                                </div>
                                <div class="mt-2 text-xs text-indigo-300/90 flex items-start gap-1.5">
                                    <span class="text-indigo-400">ℹ️</span>
                                    <span v-if="singleForm.login_type === 'username_only'">Mode Satu Kode: Pengguna login cukup mengetik Username (Password otomatis sama dengan Username).</span>
                                    <span v-else>Mode Dua Kode: Pengguna login memasukkan Username dan Password secara terpisah.</span>
                                </div>
                            </div>

                            <!-- 4. USERNAME / KODE LOGIN (DENGAN TOMBOL ACAK) -->
                            <div class="bg-[#15172e] p-4 rounded-xl border border-indigo-950 space-y-4">
                                <div>
                                    <div class="flex justify-between items-center mb-1.5">
                                        <label class="block text-xs font-bold text-gray-300">
                                            Username / Kode Login <span class="text-rose-400">*</span>
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomUsername"
                                            class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Acak Username</span>
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.username" 
                                            type="text" 
                                            placeholder="VCDCY85" 
                                            class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white font-mono font-bold tracking-wider text-base p-3.5 pr-10 focus:ring-2 focus:ring-indigo-500 uppercase transition-all" 
                                            required
                                        />
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                    </div>
                                    <div v-if="singleForm.errors.username" class="text-rose-400 text-xs mt-1">{{ singleForm.errors.username }}</div>
                                </div>

                                <!-- Password (jika mode Username & Password) -->
                                <div v-if="singleForm.login_type === 'username_password'">
                                    <div class="flex justify-between items-center mb-1.5">
                                        <label class="block text-xs font-bold text-gray-300">
                                            Password Login <span class="text-rose-400">*</span>
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomPassword"
                                            class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Acak Password</span>
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.password" 
                                            :type="showSinglePassword ? 'text' : 'password'" 
                                            placeholder="Ketik password..." 
                                            class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white font-mono text-base p-3.5 pr-10 focus:ring-2 focus:ring-indigo-500 transition-all" 
                                            required
                                        />
                                        <button 
                                            type="button" 
                                            @click="showSinglePassword = !showSinglePassword"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-200 cursor-pointer"
                                        >
                                            <svg v-if="!showSinglePassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. DATA PEMBELI (OPSIONAL) -->
                            <div class="bg-[#15172e] p-3.5 rounded-xl border border-indigo-950 space-y-3">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    <span>Data Pembeli (Opsional — untuk cari nama & kirim WA)</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-300 mb-1">Nama Pembeli</label>
                                        <input 
                                            v-model="singleForm.buyer_name" 
                                            type="text" 
                                            placeholder="Contoh: Budi Santoso" 
                                            class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-sm p-2.5 focus:ring-2 focus:ring-emerald-500" 
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-300 mb-1">No. WhatsApp</label>
                                        <input 
                                            v-model="singleForm.buyer_phone" 
                                            type="text" 
                                            placeholder="081234567890" 
                                            class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-sm p-2.5 focus:ring-2 focus:ring-emerald-500" 
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- LIVE PREVIEW VOUCHER CARD -->
                            <div v-if="singleForm.username" class="p-3.5 bg-gradient-to-br from-[#0c0d1c] to-[#1a1d3d] rounded-xl text-white shadow-lg border border-indigo-700/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-300">Live Preview Voucher</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-[#4f46e5] text-white shadow-sm">
                                        Tarif: Rp {{ Number(selectedSingleProfile?.price || 0).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="bg-white/5 backdrop-blur-sm p-3 rounded-lg border border-white/10 flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-indigo-200">Username: <span class="font-mono font-bold text-white text-sm">{{ singleForm.username }}</span></p>
                                        <p v-if="singleForm.login_type === 'username_password'" class="text-xs text-indigo-200">
                                            Password: <span class="font-mono font-bold text-amber-300">{{ singleForm.password || '-' }}</span>
                                        </p>
                                        <p v-else class="text-[11px] text-indigo-300/80 italic">User = Password</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-semibold text-white">{{ selectedSingleProfile?.name || '-' }}</p>
                                        <p class="text-[10px] text-indigo-300">{{ selectedSingleProfile?.duration || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- TAB 2: BULK VOUCHER -->
                        <form v-else @submit.prevent="submitBulkForm" class="space-y-4">
                            <!-- 1. PILIH PROFIL -->
                            <div>
                                <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1">
                                    PILIH PROFIL PAKET HOTSPOT <span class="text-rose-400">*</span>
                                </label>
                                <select v-model="bulkForm.voucher_profile_id" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-sm text-white p-3 focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="" disabled class="bg-[#191c36] text-gray-400">-- Pilih Profil --</option>
                                    <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id" class="bg-[#191c36] text-white">
                                        {{ prof.name }} (Modal: Rp{{ Number(getProfileResellerCost(prof)).toLocaleString('id-ID') }})
                                    </option>
                                </select>
                            </div>

                            <!-- 2. JUMLAH GENERATE & QUICK PICKS -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider">
                                        JUMLAH VOUCHER (QTY) <span class="text-rose-400">*</span>
                                    </label>
                                    <div class="flex gap-1.5">
                                        <button 
                                            v-for="amt in [10, 25, 50, 100]" 
                                            :key="amt"
                                            type="button" 
                                            @click="bulkForm.amount = amt"
                                            :class="bulkForm.amount === amt ? 'bg-indigo-600 text-white font-bold' : 'bg-[#191c36] text-gray-400 hover:text-white'"
                                            class="px-2.5 py-0.5 rounded text-[11px] border border-indigo-900/60 transition-colors cursor-pointer"
                                        >
                                            {{ amt }}
                                        </button>
                                    </div>
                                </div>
                                <input v-model.number="bulkForm.amount" type="number" min="1" max="500" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white font-bold text-sm p-3 focus:ring-2 focus:ring-indigo-500" required />
                            </div>

                            <!-- 3. PANJANG KARAKTER & PREFIX -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1">Panjang Karakter <span class="text-rose-400">*</span></label>
                                    <input v-model.number="bulkForm.length" type="number" min="4" max="12" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-sm p-2.5 font-bold" required />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1">Prefix (Opsional)</label>
                                    <input v-model="bulkForm.prefix" type="text" maxlength="4" placeholder="Contoh: VC" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-sm p-2.5 uppercase font-mono" />
                                </div>
                            </div>

                            <!-- 4. KOMBINASI & FORMAT KREDENSIAL -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1">Kombinasi Karakter <span class="text-rose-400">*</span></label>
                                    <select v-model="bulkForm.combination" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-sm p-2.5" required>
                                        <optgroup label="── Populer & Standar ──" class="bg-[#191c36] text-gray-400">
                                            <option value="alphanumeric" class="text-white">Huruf Besar & Angka (A1B2C)</option>
                                            <option value="alphanumeric_lower" class="text-white">Huruf Kecil & Angka (a1b2c)</option>
                                            <option value="alphanumeric_mixed" class="text-white">Campur Huruf Besar, Kecil & Angka (aB1c2)</option>
                                            <option value="numeric" class="text-white">Hanya Angka (12345)</option>
                                        </optgroup>
                                        <optgroup label="── Anti Salah Ketik (Hotspot) ──" class="bg-[#191c36] text-gray-400">
                                            <option value="clean_readable" class="text-white">Mudah Dibaca Kapital (Tanpa 0, O, 1, I, L)</option>
                                            <option value="clean_readable_lower" class="text-white">Mudah Dibaca Kecil (Tanpa 0, o, 1, i, l)</option>
                                        </optgroup>
                                        <optgroup label="── Hanya Huruf ──" class="bg-[#191c36] text-gray-400">
                                            <option value="alpha" class="text-white">Hanya Huruf Besar (ABCDE)</option>
                                            <option value="alpha_lower" class="text-white">Hanya Huruf Kecil (abcdef)</option>
                                        </optgroup>
                                        <optgroup label="── Pola Unik & Mudah Diingat ──" class="bg-[#191c36] text-gray-400">
                                            <option value="consonant_vowel" class="text-white">Mudah Dieja: Konsonan-Vokal (BADE84)</option>
                                            <option value="words_numeric" class="text-white">Kata Indonesia + Angka (KOPI25, CERIA78)</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1">Format Kredensial <span class="text-rose-400">*</span></label>
                                    <select v-model="bulkForm.type" class="w-full bg-[#191c36] border border-indigo-900/80 rounded-xl text-white text-sm p-2.5" required>
                                        <option value="vc">Satu Kode (User = Pass)</option>
                                        <option value="up">Username & Password Beda</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 5. RINCIAN MODAL & ESTIMASI KEUNTUNGAN BULK -->
                            <div v-if="selectedBulkProfile" class="p-4 bg-[#191c36]/90 border border-indigo-900/80 rounded-xl space-y-2.5 text-xs">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="font-bold text-gray-200">Total Potong Saldo Modal</span>
                                    <span class="font-extrabold text-indigo-300 bg-indigo-950/80 border border-indigo-800/80 px-2.5 py-1 rounded-lg">
                                        Rp {{ Number(getTotalBulkCost).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-emerald-400 font-semibold bg-emerald-950/40 px-2.5 py-1.5 rounded-lg border border-emerald-900/40">
                                    <span>Estimasi Keuntungan Reseller</span>
                                    <span class="font-bold">+Rp {{ Number(getTotalBulkProfit).toLocaleString('id-ID') }}</span>
                                </div>
                                <div v-if="getTotalBulkCost > (reseller.balance || 0)" class="bg-rose-950/60 text-rose-300 p-2.5 rounded-lg text-xs font-semibold flex items-center gap-2 border border-rose-800/50 mt-1">
                                    <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Saldo Anda tidak mencukupi! Butuh Rp {{ Number(getTotalBulkCost).toLocaleString('id-ID') }} (Saldo Anda: Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}).</span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- MODAL FOOTER -->
                    <div class="px-6 py-4 border-t border-indigo-950 bg-[#111327] flex justify-between items-center shrink-0">
                        <button 
                            type="button" 
                            @click="closeGenerateModal" 
                            class="px-5 py-2.5 rounded-xl border border-indigo-900/80 text-xs font-bold text-gray-300 hover:bg-[#1f2244] hover:text-white transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <div>
                            <button 
                                v-if="activeGeneratorTab === 'single'"
                                type="button" 
                                @click="submitSingleForm"
                                :disabled="singleForm.processing || !singleForm.voucher_profile_id || !singleForm.username || (getProfileResellerCost(selectedSingleProfile) > (reseller.balance || 0))"
                                class="px-6 py-2.5 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:brightness-110 active:brightness-95 text-white rounded-xl text-xs font-bold shadow-[0_0_20px_rgba(99,102,241,0.5)] transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <svg v-if="singleForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ singleForm.processing ? 'Menyimpan...' : 'Buat 1 Voucher' }}</span>
                            </button>

                            <button 
                                v-else
                                type="button" 
                                @click="submitBulkForm"
                                :disabled="bulkForm.processing || !bulkForm.voucher_profile_id || !bulkForm.amount || (getTotalBulkCost > (reseller.balance || 0))"
                                class="px-6 py-2.5 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:brightness-110 active:brightness-95 text-white rounded-xl text-xs font-bold shadow-[0_0_20px_rgba(99,102,241,0.5)] transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <svg v-if="bulkForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ bulkForm.processing ? 'Generating...' : `Generate ${bulkForm.amount} Voucher` }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </Teleport>
    </ClientAreaLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    reseller: Object,
    vouchers: Object,
    profiles: Array,
    income: Number,
    total_margin: Number,
    available_count: Number,
    total_unpaid_kasbon: {
        type: Number,
        default: 0
    },
    unpaid_kasbon_invoices: {
        type: Array,
        default: () => []
    }
});

const showKasbonModal = ref(false);
const showTopUpModal = ref(false);

const topUpForm = useForm({
    amount: 50000,
    channel: 'QRIS',
});

const presetAmounts = [20000, 50000, 100000, 200000, 500000];

const paymentChannels = [
    { id: 'QRIS', name: 'QRIS', desc: 'GoPay, OVO, Dana, ShopeePay, BCA/BRI/Mandiri QR' },
    { id: 'BCAVA', name: 'BCA Virtual Account', desc: 'Transfer Otomatis Bank BCA' },
    { id: 'BRIVA', name: 'BRI Virtual Account', desc: 'Transfer Otomatis Bank BRI' },
    { id: 'BNIVA', name: 'BNI Virtual Account', desc: 'Transfer Otomatis Bank BNI' },
    { id: 'MANDIRIVA', name: 'Mandiri Virtual Account', desc: 'Transfer Otomatis Bank Mandiri' },
];

const submitTopUp = () => {
    topUpForm.post(route('client-area.top-up-balance'), {
        preserveScroll: true,
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: errors.amount || errors.error || 'Terjadi kesalahan saat memproses top up.',
            });
        }
    });
};

const activeGeneratorTab = ref('single');
const showGenerateModal = ref(false);
const showSinglePassword = ref(false);

const sellableProfiles = computed(() => {
    return (props.profiles || []).filter(p => !p.is_customer_free);
});

const singleForm = useForm({
    mode: 'single',
    voucher_profile_id: props.profiles?.[0]?.id || '',
    login_type: 'username_only',
    username: '',
    password: '',
    buyer_name: '',
    buyer_phone: '',
    cost_type: 'paid',
});

const bulkForm = useForm({
    mode: 'bulk',
    voucher_profile_id: props.profiles?.[0]?.id || '',
    amount: 10,
    length: 6,
    prefix: '',
    type: 'vc',
    combination: 'alphanumeric',
    cost_type: 'paid',
});

const selectedSingleProfile = computed(() => {
    if (!singleForm.voucher_profile_id) return null;
    return props.profiles?.find(p => p.id === singleForm.voucher_profile_id) || null;
});

const selectedBulkProfile = computed(() => {
    if (!bulkForm.voucher_profile_id) return null;
    return props.profiles?.find(p => p.id === bulkForm.voucher_profile_id) || null;
});

const getProfileResellerCost = (profile) => {
    if (!profile) return 0;
    const price = Number(profile.price || 0);
    const fee = Number(profile.fee_reseller || 0);
    return Math.max(0, price - fee);
};

const getTotalBulkCost = computed(() => {
    if (!selectedBulkProfile.value) return 0;
    return getProfileResellerCost(selectedBulkProfile.value) * Number(bulkForm.amount || 0);
});

const getTotalBulkProfit = computed(() => {
    if (!selectedBulkProfile.value) return 0;
    return Number(selectedBulkProfile.value.fee_reseller || 0) * Number(bulkForm.amount || 0);
});

const generateRandomUsername = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let res = 'VC';
    for (let i = 0; i < 5; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    singleForm.username = res;
    if (singleForm.login_type === 'username_only') {
        singleForm.password = res;
    }
};

const generateRandomPassword = () => {
    const chars = 'abcdefghjkmnpqrstuvwxyz23456789';
    let res = '';
    for (let i = 0; i < 6; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    singleForm.password = res;
};

// Auto generate username if empty
if (!singleForm.username) {
    generateRandomUsername();
}

const openGenerateModal = () => {
    if (!singleForm.username) {
        generateRandomUsername();
    }
    showGenerateModal.value = true;
};

const closeGenerateModal = () => {
    showGenerateModal.value = false;
};

const submitSingleForm = () => {
    singleForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showGenerateModal.value = false;
            const createdCode = singleForm.username;
            singleForm.reset('buyer_name', 'buyer_phone');
            generateRandomUsername();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: `Voucher ${createdCode} berhasil dibuat.`,
                timer: 2500,
                showConfirmButton: false
            });
        },
        onError: (errs) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: errs.voucher_profile_id || errs.reseller_id || errs.username || errs.error || 'Terjadi kesalahan saat membuat voucher.',
            });
        }
    });
};

const submitBulkForm = () => {
    bulkForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showGenerateModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: `${bulkForm.amount} Voucher berhasil digenerate.`,
                timer: 2500,
                showConfirmButton: false
            });
        },
        onError: (errs) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: errs.voucher_profile_id || errs.reseller_id || errs.amount || errs.error || 'Terjadi kesalahan saat generate massal.',
            });
        }
    });
};

const selectedVouchers = ref([]);

const isAllSelected = computed(() => {
    return props.vouchers.data.length > 0 && selectedVouchers.value.length === props.vouchers.data.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedVouchers.value = props.vouchers.data.map(v => v.id);
    } else {
        selectedVouchers.value = [];
    }
};

const printSelected = () => {
    let idsToPrint = [];
    if (selectedVouchers.value.length > 0) {
        idsToPrint = selectedVouchers.value;
    } else if (props.vouchers.data.length > 0) {
        idsToPrint = props.vouchers.data.map(v => v.id);
    } else {
        Swal.fire('Info', 'Belum ada voucher untuk dicetak.', 'info');
        return;
    }

    const queryParams = new URLSearchParams();
    idsToPrint.forEach(id => {
        queryParams.append('ids[]', id);
    });

    const url = '/vouchers/print?' + queryParams.toString();
    window.open(url, '_blank');
};

// WhatsApp & Buyer Modal Logic
const showBuyerModal = ref(false);
const selectedVoucherForBuyer = ref(null);

const buyerForm = useForm({
    buyer_name: '',
    buyer_phone: '',
});

const openBuyerModal = (voucher) => {
    selectedVoucherForBuyer.value = voucher;
    buyerForm.buyer_name = voucher.buyer_name || '';
    buyerForm.buyer_phone = voucher.buyer_phone || '';
    showBuyerModal.value = true;
};

const closeBuyerModal = () => {
    showBuyerModal.value = false;
    selectedVoucherForBuyer.value = null;
};

const formatWaPhone = (phone) => {
    if (!phone) return '';
    let cleaned = phone.toString().replace(/\D/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    } else if (cleaned.startsWith('8')) {
        cleaned = '62' + cleaned;
    }
    return cleaned;
};

const previewWaMessage = computed(() => {
    const v = selectedVoucherForBuyer.value;
    if (!v) return '';
    const buyerName = buyerForm.buyer_name ? buyerForm.buyer_name.trim() : 'Kak';
    const profileName = v.profile?.name || 'Paket Hotspot';
    const duration = v.profile?.duration ? ` (${v.profile.duration})` : '';
    const price = Number(v.profile?.price || 0).toLocaleString('id-ID');

    let credText = '';
    if (v.password && v.password !== v.username && v.password !== v.code) {
        credText = `🔑 *Username:* ${v.username}\n🔒 *Password:* ${v.password}`;
    } else {
        credText = `🔑 *Kode Voucher:* ${v.code}\n_(Username dan Password sama)_`;
    }

    return `Halo Kak *${buyerName}*,\nBerikut detail voucher internet WiFi Anda:\n\n📶 *Paket:* ${profileName}${duration}\n💰 *Harga:* Rp ${price}\n\n${credText}\n\n🌐 *Cara Login:*\n1. Hubungkan HP/Laptop ke sinyal WiFi Hotspot\n2. Buka browser (Chrome/Safari)\n3. Masukkan Username & Password di atas lalu klik Login\n\n⚠️ *Penting:* Simpan pesan WhatsApp ini agar kode voucher tidak hilang jika perangkat Anda terputus.\n\nTerima kasih! 🙏`;
});

const copyWaMessage = async () => {
    try {
        await navigator.clipboard.writeText(previewWaMessage.value);
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: 'Teks pesan voucher berhasil disalin ke clipboard.',
            timer: 1500,
            showConfirmButton: false
        });
    } catch (e) {
        Swal.fire('Info', 'Silakan salin teks pesan secara manual.', 'info');
    }
};

const saveBuyerOnly = () => {
    if (!selectedVoucherForBuyer.value) return;
    buyerForm.post(route('vouchers.assign-buyer', selectedVoucherForBuyer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeBuyerModal();
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'Data nama pembeli voucher berhasil diperbarui.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

const sendViaWhatsApp = () => {
    if (!selectedVoucherForBuyer.value) return;
    const v = selectedVoucherForBuyer.value;
    const cleanPhone = formatWaPhone(buyerForm.buyer_phone);
    const message = previewWaMessage.value;
    const waUrl = cleanPhone 
        ? `https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(message)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;

    // Save buyer name & phone first, then open WhatsApp in new tab
    buyerForm.post(route('vouchers.assign-buyer', v.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeBuyerModal();
            window.open(waUrl, '_blank');
        }
    });
};
</script>

<style scoped>
@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    nav, form, button, .no-print {
        display: none !important;
    }
    .shadow-sm, .shadow-2xl, .shadow-\[0_8px_30px_rgb\(0\,0\,0\,0\.04\)\] {
        box-shadow: none !important;
        border: none !important;
    }
    .overflow-x-auto {
        overflow: visible !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
