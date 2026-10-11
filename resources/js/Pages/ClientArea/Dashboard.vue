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

            <!-- 3 Stat KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Sisa Saldo Modal -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Sisa Saldo Modal</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-sm font-bold text-gray-400">Rp</span>
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">{{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        Saldo tersedia untuk cetak voucher
                    </p>
                </div>

                <!-- Stok Voucher -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Stok Voucher</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">{{ available_count || 0 }}</span>
                        <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">Keping</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Voucher siap dijual ke pelanggan
                    </p>
                </div>

                <!-- Total Pemasukan -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Pemasukan</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-sm font-bold text-gray-400">Rp</span>
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">{{ Number(income || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Voucher yang telah aktif / terpakai
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Generate Voucher Card -->
                <div class="xl:col-span-1">
                    <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-200/80 h-full flex flex-col">
                        <div class="flex items-center gap-3.5 mb-6 pb-5 border-b border-gray-100">
                            <div class="w-11 h-11 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-100/80">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 tracking-tight">Generate Voucher</h2>
                                <p class="text-xs text-gray-500 font-medium">Buat kode voucher pelanggan baru</p>
                            </div>
                        </div>

                        <form @submit.prevent="generateVoucher" class="space-y-5 flex-1 flex flex-col">
                            <div class="space-y-4 flex-1">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Pilih Paket Voucher</label>
                                    <div class="relative">
                                        <select v-model="form.profile_id" class="w-full appearance-none bg-gray-50/70 border border-gray-300 text-gray-900 text-sm font-semibold rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white block p-3.5 pr-10 shadow-sm transition-all cursor-pointer">
                                            <option value="" disabled>-- Pilih Paket --</option>
                                            <option v-for="profile in profiles" :key="profile.id" :value="profile.id">
                                                {{ profile.name }} — Jual: Rp {{ Number(profile.price).toLocaleString('id-ID') }}
                                            </option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.profile_id" class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ form.errors.profile_id }}
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Jumlah (Keping)</label>
                                        <input v-model="form.qty" type="number" min="1" max="100" class="w-full bg-gray-50/70 border border-gray-300 text-gray-900 text-base font-bold rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white block p-3 shadow-sm transition-all" placeholder="10" />
                                        <div v-if="form.errors.qty" class="text-rose-600 text-xs font-medium mt-1">{{ form.errors.qty }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Format Kode</label>
                                        <div class="relative">
                                            <select v-model="form.combination" class="w-full appearance-none bg-gray-50/70 border border-gray-300 text-gray-900 text-sm font-semibold rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white block p-3 pr-8 shadow-sm transition-all cursor-pointer">
                                                <option value="alphanumeric">Abjad & Angka</option>
                                                <option value="numeric">Angka (0-9)</option>
                                                <option value="alpha">Abjad (A-Z)</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Total -->
                                <div v-if="selectedProfile" class="mt-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-2.5 text-xs">
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span class="font-medium">Harga Jual Pelanggan</span>
                                        <span class="font-bold text-gray-800">Rp {{ Number(selectedProfile.price).toLocaleString('id-ID') }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span class="font-medium">Keuntungan Reseller (Fee)</span>
                                        <span class="font-bold text-emerald-600">+Rp {{ Number(selectedProfile.fee_reseller || 0).toLocaleString('id-ID') }}/voucher</span>
                                    </div>
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span class="font-medium">Harga Modal Beli</span>
                                        <span class="font-bold text-indigo-600">Rp {{ Number(getCostPerVoucher(selectedProfile)).toLocaleString('id-ID') }}/voucher</span>
                                    </div>
                                    <div class="h-px bg-slate-200 w-full my-1.5"></div>
                                    <div class="flex justify-between items-center text-sm pt-0.5">
                                        <span class="text-gray-900 font-bold">Total Potong Saldo Modal</span>
                                        <span class="font-black text-indigo-700 bg-indigo-50 border border-indigo-100 px-2.5 py-1 rounded-lg">
                                            Rp {{ Number(getTotalCost(selectedProfile, form.qty)).toLocaleString('id-ID') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-emerald-700 font-semibold bg-emerald-50 px-2.5 py-1.5 rounded-lg border border-emerald-100">
                                        <span>Estimasi Total Keuntungan</span>
                                        <span class="font-bold">Rp {{ Number(getTotalProfit(selectedProfile, form.qty)).toLocaleString('id-ID') }}</span>
                                    </div>
                                    <div v-if="getTotalCost(selectedProfile, form.qty) > reseller.balance" class="bg-rose-50 text-rose-700 p-3 rounded-lg text-xs font-semibold mt-2 flex items-start gap-2 border border-rose-200 leading-snug">
                                        <svg class="w-4 h-4 shrink-0 text-rose-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Saldo tidak mencukupi! Butuh modal Rp {{ Number(getTotalCost(selectedProfile, form.qty)).toLocaleString('id-ID') }} (Saldo Anda: Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}).</span>
                                    </div>
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl font-bold text-sm transition-all shadow-sm hover:shadow disabled:opacity-50 disabled:cursor-not-allowed mt-auto flex justify-center items-center gap-2"
                                :disabled="form.processing || !form.profile_id || !form.qty || (getTotalCost(selectedProfile, form.qty) > reseller.balance)"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ form.processing ? 'Sedang Memproses...' : 'Generate Voucher Sekarang' }}
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
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="printSelected" 
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
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
                                        <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Tanggal</th>
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
                                                {{ voucher.code }}
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
                                        <td class="whitespace-nowrap py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-medium text-gray-500">
                                            {{ new Date(voucher.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                        </td>
                                    </tr>
                                    <tr v-if="vouchers.data.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center">
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
});

const form = useForm({
    profile_id: '',
    qty: 1,
    combination: 'alphanumeric'
});

const selectedVouchers = ref([]);

const selectedProfile = computed(() => {
    if (!form.profile_id) return null;
    return props.profiles.find(p => p.id === form.profile_id);
});

const getCostPerVoucher = (profile) => {
    if (!profile) return 0;
    const price = Number(profile.price || 0);
    const fee = Number(profile.fee_reseller || 0);
    return Math.max(0, price - fee);
};

const getTotalCost = (profile, qty) => {
    return getCostPerVoucher(profile) * Number(qty || 0);
};

const getTotalProfit = (profile, qty) => {
    return Number(profile?.fee_reseller || 0) * Number(qty || 0);
};

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

const generateVoucher = () => {
    form.post(route('client-area.generate-vouchers'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('qty');
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Voucher berhasil digenerate',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: errors.error || 'Terjadi kesalahan saat generate voucher',
            });
        }
    });
};
</script>

<style>
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
