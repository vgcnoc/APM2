<template>
    <Head title="Loket Pembayaran Internet - Reseller" />

    <ClientAreaLayout>
        <div class="space-y-6 pb-12">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200/60">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Loket Pembayaran Internet
                        </span>
                        <span v-if="is_master" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-gradient-to-r from-amber-500 to-yellow-500 text-white shadow-xs">
                            👑 MASTER BILLER
                        </span>
                        <span class="text-xs text-gray-300">•</span>
                        <span class="text-xs font-medium text-gray-500">
                            {{ reseller.name || 'Mitra Reseller' }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Loket Pembayaran Internet Pelanggan
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Terima uang tunai dari pelanggan, bayar tagihan internet instan potong saldo modal, dan cetak struk pembayaran resmi.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/client-area/dashboard" class="px-4 py-2 bg-white border border-gray-200/80 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Dashboard
                    </Link>
                </div>
            </div>

            <!-- 3 Kartu KPI -->
            <!-- 4 Kartu KPI Loket -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Sisa Saldo Dompet Modal -->
                <div class="bg-gradient-to-br from-blue-50/70 via-indigo-50/30 to-white rounded-2xl border border-blue-200/80 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-700">Sisa Saldo Modal</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between gap-1">
                        <div class="flex items-baseline gap-1">
                            <span class="text-sm font-bold text-gray-400">Rp</span>
                            <span class="text-2xl font-black text-gray-900 tracking-tight">{{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</span>
                        </div>
                        <button @click="showTopUpModal = true" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-bold shadow-xs transition cursor-pointer flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Isi Saldo
                        </button>
                    </div>
                    <p class="text-xs text-blue-800 mt-2 flex items-center gap-1.5 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        Saldo aktif loket
                    </p>
                </div>

                <!-- Total Transaksi Loket -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Transaksi</span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-gray-900 tracking-tight">{{ totalTransactionsCount || 0 }}</span>
                        <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200/60">Tagihan</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        Pelanggan sukses dibayar
                    </p>
                </div>

                <!-- Perputaran Uang Loket -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Perputaran Uang</span>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-sm font-bold text-gray-400">Rp</span>
                        <span class="text-2xl font-black text-gray-900 tracking-tight">{{ Number(totalTransactionsAmount || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        Total tagihan terbayar
                    </p>
                </div>

                <!-- Keuntungan Tunai Loket & Fee Mitra -->
                <div class="bg-gradient-to-br from-emerald-50/70 via-emerald-50/30 to-white rounded-2xl border border-emerald-200/80 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Keuntungan Loket & Fee Mitra</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-sm font-bold text-gray-400">Rp</span>
                        <span class="text-2xl font-black text-emerald-600 tracking-tight">{{ Number((total_fee_profit || 0) + ((totalTransactionsCount || 0) * 2500)).toLocaleString('id-ID') }}</span>
                    </div>
                    <p class="text-[11px] text-emerald-700 mt-2 flex items-center gap-1.5 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Fee Paket: Rp {{ Number(total_fee_profit || 0).toLocaleString('id-ID') }} + Admin Tunai
                    </p>
                </div>
            </div>

            <!-- Box Pencarian Pelanggan & Loket Kasir -->
            <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm p-6 sm:p-8 space-y-6">
                <div>
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm border border-indigo-100">
                            1
                        </div>
                        <h2 class="text-lg font-bold text-gray-900">Cari Data Pelanggan Internet</h2>
                    </div>
                    <p class="text-xs text-gray-500 ml-10">
                        Ketik ID Pelanggan (contoh: CUS-000001), Nama Pelanggan, atau Nomor WhatsApp pelanggan yang ingin membayar.
                    </p>
                </div>

                <!-- Input Search -->
                <div class="relative max-w-2xl">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input 
                        v-model="searchQuery" 
                        @input="handleSearchInput" 
                        type="text" 
                        placeholder="Ketik No. Pelanggan, Nama, atau No. WhatsApp..." 
                        class="w-full pl-11 pr-24 py-3.5 bg-gray-50/70 border border-gray-300 rounded-2xl text-sm font-semibold text-gray-900 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-2xs"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <button 
                            v-if="searchQuery" 
                            @click="clearSearch" 
                            type="button" 
                            class="px-2.5 py-1.5 text-xs text-gray-400 hover:text-gray-600 rounded-lg mr-1 cursor-pointer"
                        >
                            Bersihkan
                        </button>
                        <span v-if="isSearching" class="pr-3 flex items-center">
                            <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </span>
                    </div>
                </div>

                <!-- Hasil Pencarian Pelanggan -->
                <div v-if="searchResults.length > 0" class="space-y-4 pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">Hasil Pencarian ({{ searchResults.length }} Pelanggan Ditemukan)</h3>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div 
                            v-for="cust in searchResults" 
                            :key="cust.id" 
                            class="border rounded-2xl p-5 transition-all space-y-4"
                            :class="selectedCustomer?.id === cust.id ? 'border-indigo-600 bg-indigo-50/20 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-white hover:border-gray-300'"
                        >
                            <!-- Info Pelanggan Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                                <div class="flex items-start gap-3.5">
                                    <!-- Foto KTP Thumbnail / Avatar -->
                                    <div v-if="cust.identity_photo_url" class="relative group shrink-0">
                                        <img 
                                            :src="cust.identity_photo_url" 
                                            alt="Foto KTP" 
                                            @click="openPhotoPreview(cust.identity_photo_url, cust.name + ' (' + cust.customer_code + ')')"
                                            class="w-16 h-12 sm:w-20 sm:h-14 object-cover rounded-xl border-2 border-indigo-200 shadow-sm cursor-pointer transition transform group-hover:scale-105 group-hover:border-indigo-500 bg-gray-100"
                                            title="Klik untuk memperbesar Foto KTP"
                                        />
                                        <div 
                                            @click="openPhotoPreview(cust.identity_photo_url, cust.name + ' (' + cust.customer_code + ')')"
                                            class="absolute -bottom-1 -right-1 bg-indigo-600 hover:bg-indigo-700 text-white p-1 rounded-full shadow cursor-pointer"
                                            title="Perbesar KTP"
                                        >
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                        </div>
                                    </div>
                                    <div v-else class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-black text-base flex items-center justify-center shrink-0 shadow-sm">
                                        {{ (cust.name || 'P').charAt(0).toUpperCase() }}
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-base sm:text-lg font-black text-gray-900">{{ cust.name }}</h4>
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-gray-100 text-gray-700">{{ cust.customer_code }}</span>
                                            
                                            <!-- BADGE WILAYAH / AREA -->
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>Wilayah: {{ cust.area || 'Pusat' }}</span>
                                            </span>

                                            <!-- Status Layanan -->
                                            <span v-if="cust.status === 'active'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Layanan Aktif
                                            </span>
                                            <span v-else-if="cust.status === 'suspended'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                Terisolir (Otomatis Aktif jika Dibayar)
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">
                                                {{ cust.status }}
                                            </span>

                                            <!-- Tombol Preview KTP -->
                                            <button 
                                                v-if="cust.identity_photo_url" 
                                                @click="openPhotoPreview(cust.identity_photo_url, cust.name + ' (' + cust.customer_code + ')')"
                                                type="button"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition cursor-pointer"
                                            >
                                                <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Foto KTP</span>
                                            </button>
                                            <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 text-gray-400 border border-gray-200">
                                                Belum Ada KTP
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-3 text-xs text-gray-500 flex-wrap">
                                            <span class="font-semibold text-blue-700 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Wilayah: <strong>{{ cust.area || '-' }}</strong>
                                            </span>
                                            <span>• Paket: <strong class="text-indigo-600">{{ cust.package_name }}</strong> (Rp {{ Number(cust.package_price).toLocaleString('id-ID') }})</span>
                                            <span v-if="cust.phone">• WhatsApp: <strong class="text-gray-700">{{ cust.phone }}</strong></span>
                                            <span v-if="cust.address">• {{ cust.address }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-left sm:text-right shrink-0 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-xl">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 block">Total Tagihan Tertunggak</span>
                                    <span class="text-xl sm:text-2xl font-black font-mono" :class="cust.total_unpaid > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                        Rp {{ Number(cust.total_unpaid || 0).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Daftar Invoice Pelanggan -->
                            <div v-if="cust.unpaid_invoices.length > 0" class="space-y-3">
                                <span class="text-xs font-bold text-gray-700 block">Pilih Tagihan yang Ingin Dibayarkan Pelanggan:</span>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div 
                                        v-for="inv in cust.unpaid_invoices" 
                                        :key="inv.id" 
                                        class="p-4 bg-gray-50/80 rounded-2xl border border-gray-200 flex flex-col justify-between gap-3 hover:bg-white hover:shadow-xs transition"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <span class="font-mono font-bold text-sm text-gray-900">{{ inv.invoice_number }}</span>
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-rose-100 text-rose-800">
                                                    {{ inv.status }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-600 space-y-0.5">
                                                <div>Periode: <strong>{{ inv.period_label || (inv.period_month + '/' + inv.period_year) }}</strong></div>
                                                <div>Jatuh Tempo: <span class="text-rose-600 font-semibold">{{ inv.due_date }}</span></div>
                                                <div v-if="inv.notes" class="text-[11px] text-gray-400 italic">{{ inv.notes }}</div>
                                            </div>
                                        </div>

                                        <div class="pt-3 border-t border-gray-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Nominal Tagihan:</span>
                                                    <span class="text-sm font-black text-rose-600 font-mono">
                                                        Rp {{ Number(inv.remaining).toLocaleString('id-ID') }}
                                                    </span>
                                                </div>
                                                <div v-if="inv.fee_reseller > 0" class="flex flex-wrap items-center gap-2 mt-1">
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold border border-emerald-200">
                                                        Fee Mitra: +Rp {{ Number(inv.fee_reseller).toLocaleString('id-ID') }}
                                                    </span>
                                                    <span class="text-[11px] font-bold text-gray-600">
                                                        Potong Saldo Modal: <strong class="text-indigo-700 font-mono font-black">Rp {{ Number(inv.modal_cost).toLocaleString('id-ID') }}</strong>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Tombol Bayar Potong Saldo -->
                                            <button 
                                                @click="confirmPayInvoice(cust, inv)"
                                                :disabled="payingInvoiceId === inv.id || reseller.balance < (inv.modal_cost !== undefined ? inv.modal_cost : inv.remaining)"
                                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap self-end sm:self-auto"
                                                :class="reseller.balance >= (inv.modal_cost !== undefined ? inv.modal_cost : inv.remaining) ? 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-emerald-500/20' : 'bg-gray-200 text-gray-500'"
                                                :title="reseller.balance < (inv.modal_cost !== undefined ? inv.modal_cost : inv.remaining) ? 'Saldo Anda kurang untuk bayar modal tagihan ini' : 'Bayar via Saldo Dompet Reseller'"
                                            >
                                                <svg v-if="payingInvoiceId === inv.id" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ reseller.balance >= (inv.modal_cost !== undefined ? inv.modal_cost : inv.remaining) ? 'Bayar Potong Saldo' : 'Saldo Kurang' }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-4 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Pelanggan ini <strong>tidak memiliki tagihan internet yang tertunggak</strong>. Semua tagihan telah lunas.</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else-if="searchQuery && !isSearching" class="p-8 text-center bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <p class="text-sm font-bold text-gray-700">Pelanggan tidak ditemukan</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pastikan nomor ID pelanggan, nama, atau nomor WhatsApp yang Anda masukkan sudah benar.</p>
                </div>
            </div>

            <!-- Tabel Riwayat Pembayaran Loket yang Diproses -->
            <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 tracking-tight">Riwayat Pembayaran di Loket Anda</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Daftar tagihan internet pelanggan yang telah sukses dibayarkan melalui loket reseller Anda.</p>
                    </div>
                </div>

                <div class="overflow-x-auto flex-1">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50/75">
                            <tr>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Waktu & No. Ref</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Pelanggan</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Faktur Tagihan</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Nominal Tagihan</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Struk Sah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white text-xs">
                            <tr v-for="pay in recentPayments.data" :key="pay.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Waktu & Ref -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900 font-mono text-xs">{{ pay.reference_number || ('PAY-' + pay.id) }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        {{ pay.payment_date ? new Date(pay.payment_date).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-' }}
                                    </div>
                                </td>

                                <!-- Pelanggan -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">{{ pay.customer?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-500 font-mono flex items-center gap-1.5 mt-0.5">
                                        <span>{{ pay.customer?.customer_code || '-' }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span class="text-blue-600 font-sans font-semibold">Wilayah: {{ pay.customer?.areaModel?.name || pay.customer?.area || '-' }}</span>
                                    </div>
                                </td>

                                <!-- Faktur Tagihan -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="font-mono font-bold text-indigo-700">{{ pay.invoice?.invoice_number || '-' }}</div>
                                    <div class="text-[11px] text-gray-400">Periode: {{ pay.invoice?.period_month }}/{{ pay.invoice?.period_year }}</div>
                                </td>

                                <!-- Nominal Tagihan -->
                                <td class="px-4 py-4 whitespace-nowrap text-right font-bold text-gray-900 font-mono text-sm">
                                    Rp {{ Number(pay.amount || 0).toLocaleString('id-ID') }}
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Lunas Terpotong
                                    </span>
                                </td>

                                <!-- Aksi Cetak Struk -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <button 
                                        @click="openReceiptFromPayment(pay)" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-bold text-xs transition cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        <span>Lihat Struk</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!recentPayments.data || recentPayments.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                    Belum ada transaksi pembayaran pelanggan yang diproses lewat loket ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="recentPayments.links && recentPayments.links.length > 3" class="px-6 py-4 bg-white border-t border-gray-100">
                    <div class="flex flex-wrap gap-1.5 justify-center sm:justify-start">
                        <template v-for="(link, p) in recentPayments.links" :key="p">
                            <div v-if="link.url === null" class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-100 rounded-lg bg-gray-50" v-html="link.label" />
                            <Link v-else :href="link.url" class="px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-indigo-50 focus:border-indigo-500 focus:text-indigo-600 transition-colors shadow-2xs" :class="{ 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700': link.active, 'border-gray-200 text-gray-600 bg-white': !link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <!-- MODAL STRUK PEMBAYARAN RESMI (THERMAL RECEIPT) -->
        <Teleport to="body">
            <div v-if="activeReceipt" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="activeReceipt = null"></div>
                
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-up flex flex-col max-h-[90vh]">
                    
                    <!-- Header Modal Struk -->
                    <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between bg-slate-50 no-print">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-700">Bukti Pembayaran Sah</span>
                        </div>
                        <button @click="activeReceipt = null" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Area Kertas Struk (Thermal Format) -->
                    <div id="receipt-paper" class="p-6 bg-white overflow-y-auto space-y-4 font-mono text-xs text-gray-900 flex-1">
                        <!-- Logo & Kop Loket -->
                        <div class="text-center space-y-1 pb-3 border-b-2 border-dashed border-gray-300">
                            <div class="text-base font-black tracking-wider uppercase">APM NETWORK</div>
                            <div class="text-[11px] text-gray-600 font-sans">Layanan Internet Cepat & Stabil</div>
                            <div class="text-[10px] text-gray-500">Loket Mitra: <strong class="text-gray-800">{{ activeReceipt.reseller_name }}</strong></div>
                        </div>

                        <!-- Data Transaksi -->
                        <div class="space-y-1 text-[11px] pb-3 border-b border-dashed border-gray-300">
                            <div class="flex justify-between">
                                <span class="text-gray-500">No. Ref:</span>
                                <span class="font-bold">{{ activeReceipt.reference_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Waktu:</span>
                                <span>{{ activeReceipt.payment_date }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">No. Faktur:</span>
                                <span class="font-bold">{{ activeReceipt.invoice_number }}</span>
                            </div>
                        </div>

                        <!-- Data Pelanggan -->
                        <div class="space-y-1 text-[11px] pb-3 border-b border-dashed border-gray-300">
                            <div class="flex justify-between">
                                <span class="text-gray-500">ID Pelanggan:</span>
                                <span class="font-bold">{{ activeReceipt.customer_code }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Nama:</span>
                                <span class="font-bold uppercase">{{ activeReceipt.customer_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Wilayah/Area:</span>
                                <span class="font-bold">{{ activeReceipt.customer_area || '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Paket:</span>
                                <span>{{ activeReceipt.package_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Periode:</span>
                                <span>{{ activeReceipt.period }}</span>
                            </div>
                        </div>

                        <!-- Rincian Biaya -->
                        <div class="space-y-1 text-[11px] pb-3 border-b-2 border-dashed border-gray-300">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tagihan Internet:</span>
                                <span>Rp {{ Number(activeReceipt.amount).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Biaya Admin Loket:</span>
                                <span>Rp {{ Number(activeReceipt.admin_fee || 2500).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-black pt-1 border-t border-gray-200">
                                <span>TOTAL TUNAI:</span>
                                <span>Rp {{ Number(activeReceipt.total_collected || (activeReceipt.amount + 2500)).toLocaleString('id-ID') }}</span>
                            </div>
                        </div>

                        <!-- Status Lunas Footer -->
                        <div class="text-center pt-2 space-y-1">
                            <div class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 font-black rounded text-xs uppercase tracking-widest border border-emerald-300">
                                *** LUNAS ***
                            </div>
                            <p class="text-[10px] text-gray-500 font-sans mt-2">
                                Terima kasih atas pembayaran Anda.<br/>Simpan struk ini sebagai bukti pembayaran sah.
                            </p>
                        </div>
                    </div>

                    <!-- Footer Action Modal -->
                    <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-2 no-print">
                        <button 
                            @click="printReceipt" 
                            type="button" 
                            class="flex-1 py-2.5 px-4 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak Struk</span>
                        </button>

                        <button 
                            @click="shareViaWhatsApp" 
                            type="button" 
                            class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
                        >
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.587 1.961.954 2.795.955 3.179 0 5.767-2.587 5.768-5.766.001-3.18-2.585-5.766-5.767-5.766zm3.374 8.214c-.14.394-.813.738-1.121.785-.29.044-.658.077-1.077-.058-.261-.084-.596-.197-1.026-.385-1.815-.796-3.003-2.628-3.094-2.75-.091-.122-.74-1.002-.74-1.912 0-.91.477-1.357.646-1.543.17-.186.371-.233.495-.233.123 0 .248.001.356.006.113.006.265-.043.414.316.155.372.527 1.284.573 1.377.046.093.078.201.016.324-.063.124-.094.201-.186.309-.093.109-.196.243-.28.327-.094.093-.191.194-.082.381.109.186.484.798 1.037 1.291.713.636 1.314.833 1.5.926.186.093.295.078.403-.047.109-.124.465-.543.589-.729.124-.186.248-.155.418-.093.17.062 1.085.512 1.271.605.186.093.309.14.356.217.047.077.047.45-.093.844zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.662 1.433 5.178L2 22l4.981-1.306C8.423 21.536 10.155 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18c-1.634 0-3.167-.487-4.449-1.323l-.319-.209-2.969.779.792-2.898-.222-.353C3.91 14.73 3.4 13.411 3.4 12c0-4.743 3.857-8.6 8.6-8.6 4.743 0 8.6 3.857 8.6 8.6 0 4.743-3.857 8.6-8.6 8.6z"/></svg>
                            <span>Kirim WA</span>
                        </button>
                    </div>

                </div>
            </div>
        </Teleport>

        <!-- Modal Top Up Saldo Mandiri Reseller -->
        <Teleport to="body">
            <div v-if="showTopUpModal" class="fixed inset-0 z-[130] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showTopUpModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in-up">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-white flex items-center justify-between">
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

                    <form @submit.prevent="submitTopUp" class="p-6 space-y-5">
                        <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500">Saldo Dompet Saat Ini</span>
                            <span class="text-sm font-black text-gray-900 font-mono">Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</span>
                        </div>

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
                                    {{ formatPresetLabel(amt) }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Atau Masukkan Nominal Sendiri</label>
                            <div class="relative rounded-2xl">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold text-sm">
                                    Rp
                                </div>
                                <input 
                                    v-model.number="topUpForm.amount" 
                                    type="number" 
                                    :min="effectiveMinDeposit" 
                                    :max="effectiveMaxDeposit" 
                                    step="5000"
                                    required
                                    class="w-full bg-gray-50/70 border border-gray-300 text-gray-900 text-base font-extrabold rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white block pl-11 pr-4 py-3 shadow-xs transition" 
                                    placeholder="50000"
                                />
                            </div>
                            <span class="text-[11px] text-gray-500 mt-1 block">Minimal pengisian saldo ({{ is_master ? 'Master Biller' : 'Mitra Loket' }}): <strong>Rp {{ Number(effectiveMinDeposit).toLocaleString('id-ID') }}</strong></span>
                        </div>

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

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="showTopUpModal = false" class="px-4 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="topUpForm.processing || !topUpForm.amount || topUpForm.amount < effectiveMinDeposit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                                <svg v-if="topUpForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ topUpForm.processing ? 'Memproses...' : 'Lanjut ke Pembayaran' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- MODAL PREVIEW FOTO KTP PELANGGAN -->
        <Teleport to="body">
            <div v-if="previewPhotoModal" class="fixed inset-0 z-[150] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-950/80 backdrop-blur-sm" @click="previewPhotoModal = false"></div>
                
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden animate-fade-in-up flex flex-col z-10 border border-gray-100">
                    <!-- Header Modal KTP -->
                    <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Verifikasi Dokumen KTP</h4>
                                <p class="text-xs text-gray-500">{{ previewPhotoTitle }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <a :href="previewPhotoUrl" target="_blank" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-gray-100 rounded-xl transition" title="Buka Gambar Asli">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                            <button @click="previewPhotoModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body Image -->
                    <div class="p-4 bg-slate-900 flex items-center justify-center min-h-[250px] max-h-[70vh] overflow-hidden">
                        <img :src="previewPhotoUrl" alt="Foto KTP Pelanggan" class="max-h-[65vh] w-auto max-w-full rounded-xl object-contain shadow-2xl" />
                    </div>

                    <!-- Footer -->
                    <div class="p-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Identitas Sah Terdaftar di Sistem
                        </span>
                        <button @click="previewPhotoModal = false" class="px-4 py-1.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold text-xs cursor-pointer shadow-xs">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </ClientAreaLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    reseller: Object,
    is_master: {
        type: Boolean,
        default: false
    },
    min_deposit: {
        type: Number,
        default: 50000
    },
    recentPayments: Object,
    totalTransactionsAmount: Number,
    totalTransactionsCount: Number,
    total_fee_profit: Number,
});

const page = usePage();

// Search state
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const selectedCustomer = ref(null);
let searchDebounce = null;

// Biller Action State
const payingInvoiceId = ref(null);
const activeReceipt = ref(null);

// Modal Preview Foto KTP
const previewPhotoModal = ref(false);
const previewPhotoUrl = ref('');
const previewPhotoTitle = ref('');

const openPhotoPreview = (url, title) => {
    previewPhotoUrl.value = url;
    previewPhotoTitle.value = title || 'Foto KTP';
    previewPhotoModal.value = true;
};

// Cek apakah ada flash receipt setelah pembayaran berhasil
watch(() => page.props.flash, (flash) => {
    if (flash?.billerReceipt) {
        activeReceipt.value = flash.billerReceipt;
        // Refresh pencarian agar invoice yang barusan lunas hilang
        if (searchQuery.value) {
            performSearch(searchQuery.value);
        }
    }
}, { immediate: true });

const handleSearchInput = () => {
    clearTimeout(searchDebounce);
    if (!searchQuery.value || searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        isSearching.value = false;
        return;
    }

    isSearching.value = true;
    searchDebounce = setTimeout(() => {
        performSearch(searchQuery.value);
    }, 350);
};

const performSearch = async (q) => {
    isSearching.value = true;
    try {
        const response = await axios.get(route('client-area.biller.search-customer'), {
            params: { q: q.trim() }
        });
        if (response.data.success) {
            searchResults.value = response.data.customers || [];
        }
    } catch (e) {
        console.error('Search error', e);
    } finally {
        isSearching.value = false;
    }
};

const clearSearch = () => {
    searchQuery.value = '';
    searchResults.value = [];
    selectedCustomer.value = null;
};

// Konfirmasi & Bayar Tagihan
const confirmPayInvoice = (customer, invoice) => {
    const remaining = Number(invoice.remaining);
    const feeReseller = Number(invoice.fee_reseller || customer.package_fee_reseller || 0);
    const modalCost = Number(invoice.modal_cost !== undefined ? invoice.modal_cost : Math.max(0, remaining - feeReseller));
    const balance = Number(props.reseller.balance || 0);

    if (balance < modalCost) {
        Swal.fire({
            icon: 'warning',
            title: 'Saldo Modal Tidak Cukup',
            text: `Saldo dompet modal Anda (Rp ${balance.toLocaleString('id-ID')}) tidak mencukupi untuk membayar harga modal tagihan Rp ${modalCost.toLocaleString('id-ID')}. Silakan isi saldo terlebih dahulu.`,
            confirmButtonText: 'Isi Saldo Sekarang',
            showCancelButton: true,
            cancelButtonText: 'Tutup'
        }).then((result) => {
            if (result.isConfirmed) {
                showTopUpModal.value = true;
            }
        });
        return;
    }

    Swal.fire({
        title: 'Konfirmasi Pembayaran Loket',
        html: `
            <div class="text-left text-xs space-y-2 mt-2">
                <div class="p-3 bg-gray-50 rounded-xl space-y-1">
                    <div class="flex justify-between"><span>Pelanggan:</span> <strong>${customer.name}</strong></div>
                    <div class="flex justify-between"><span>ID Pelanggan:</span> <span class="font-mono">${customer.customer_code}</span></div>
                    <div class="flex justify-between"><span>No. Faktur:</span> <span class="font-mono">${invoice.invoice_number}</span></div>
                    <div class="flex justify-between"><span>Periode:</span> <strong>${invoice.period_label}</strong></div>
                    <div class="flex justify-between text-gray-700"><span>Tagihan Internet:</span> <strong>Rp ${remaining.toLocaleString('id-ID')}</strong></div>
                    ${feeReseller > 0 ? `
                        <div class="flex justify-between text-emerald-600 font-bold"><span>Fee Reseller/Mitra:</span> <span>-Rp ${feeReseller.toLocaleString('id-ID')}</span></div>
                    ` : ''}
                    <div class="flex justify-between text-indigo-700 font-extrabold border-t pt-1">
                        <span>Potong Saldo Modal:</span> <strong>Rp ${modalCost.toLocaleString('id-ID')}</strong>
                    </div>
                </div>
                <div class="p-2.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 space-y-1">
                    <div>💡 <strong>Uang Diterima Tunai:</strong> Tarik tunai dari pelanggan sebesar <strong>Rp ${(remaining + 2500).toLocaleString('id-ID')}</strong> (termasuk admin Rp 2.500).</div>
                    ${feeReseller > 0 ? `
                        <div class="text-[11px] text-emerald-700 font-semibold">🎉 <strong>Keuntungan Anda:</strong> Rp ${feeReseller.toLocaleString('id-ID')} (Fee Mitra Paket) + Rp 2.500 (Admin Tunai) = <strong>Rp ${(feeReseller + 2500).toLocaleString('id-ID')}</strong></div>
                    ` : ''}
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Bayar Sekarang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669',
    }).then((res) => {
        if (res.isConfirmed) {
            executePayment(invoice.id);
        }
    });
};

const executePayment = (invoiceId) => {
    payingInvoiceId.value = invoiceId;
    router.post(route('client-area.biller.pay', invoiceId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            payingInvoiceId.value = null;
        },
        onError: (err) => {
            payingInvoiceId.value = null;
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memproses Pembayaran',
                text: err.error || 'Terjadi kesalahan saat memproses pembayaran loket.',
            });
        }
    });
};

// Buka Struk dari Riwayat Tabel
const openReceiptFromPayment = (pay) => {
    activeReceipt.value = {
        payment_id: pay.id,
        reference_number: pay.reference_number || ('PAY-' + pay.id),
        payment_date: pay.payment_date ? new Date(pay.payment_date).toLocaleString('id-ID') : '-',
        reseller_name: props.reseller.name || 'Mitra Reseller',
        customer_code: pay.customer?.customer_code || '-',
        customer_name: pay.customer?.name || '-',
        customer_phone: pay.customer?.phone || '',
        customer_address: pay.customer?.address || '',
        customer_area: pay.customer?.areaModel?.name || pay.customer?.area || '-',
        package_name: pay.customer?.package?.name || 'Internet Rumah',
        invoice_number: pay.invoice?.invoice_number || '-',
        period: (pay.invoice?.period_month && pay.invoice?.period_year) ? `${pay.invoice.period_month}/${pay.invoice.period_year}` : '-',
        amount: Number(pay.amount || 0),
        admin_fee: 2500,
        total_collected: Number(pay.amount || 0) + 2500,
    };
};

// Cetak Struk
const printReceipt = () => {
    document.body.classList.add('is-printing-biller-receipt');
    setTimeout(() => {
        window.print();
        setTimeout(() => {
            document.body.classList.remove('is-printing-biller-receipt');
        }, 1000);
    }, 100);
};

// Kirim Struk via WhatsApp
const shareViaWhatsApp = () => {
    if (!activeReceipt.value) return;
    const r = activeReceipt.value;
    const phone = (r.customer_phone || '').replace(/[^0-9]/g, '');
    const cleanPhone = phone.startsWith('0') ? '62' + phone.substring(1) : phone;

    const message = 
`*BUKTI PEMBAYARAN INTERNET APM NETWORK*
---------------------------------------
Loket Mitra : ${r.reseller_name}
No. Ref     : ${r.reference_number}
Waktu       : ${r.payment_date}
No. Faktur  : ${r.invoice_number}
---------------------------------------
ID Pelanggan: ${r.customer_code}
Nama        : ${r.customer_name}
Wilayah     : ${r.customer_area || '-'}
Paket       : ${r.package_name}
Periode     : ${r.period}
---------------------------------------
Tagihan     : Rp ${Number(r.amount).toLocaleString('id-ID')}
Admin Loket : Rp ${Number(r.admin_fee || 2500).toLocaleString('id-ID')}
*TOTAL TUNAI : Rp ${Number(r.total_collected || (r.amount + 2500)).toLocaleString('id-ID')}*
---------------------------------------
Status      : *LUNAS (TERVERIFIKASI)*

_Terima kasih atas pembayaran Anda. Simpan bukti ini sebagai tanda terima sah._`;

    const url = cleanPhone 
        ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`
        : `https://wa.me/?text=${encodeURIComponent(message)}`;
    
    window.open(url, '_blank');
};

// Top Up Modal State
const showTopUpModal = ref(false);
const topUpForm = useForm({
    amount: props.is_master ? Math.max(2000000, Number(props.min_deposit || 2000000)) : Math.max(50000, Number(props.min_deposit || 50000)),
    channel: 'QRIS',
});
const effectiveMinDeposit = computed(() => {
    if (props.min_deposit && Number(props.min_deposit) > 0) return Number(props.min_deposit);
    return props.is_master ? 2000000 : 50000;
});

const effectiveMaxDeposit = computed(() => props.is_master ? 50000000 : 10000000);

const presetAmounts = computed(() => {
    return props.is_master
        ? [2000000, 3000000, 5000000, 10000000, 15000000, 25000000]
        : [50000, 100000, 200000, 300000, 500000, 1000000];
});

const formatPresetLabel = (amt) => {
    if (amt >= 1000000) {
        return 'Rp ' + (amt / 1000000).toLocaleString('id-ID') + ' Jt';
    }
    return 'Rp ' + (amt / 1000).toLocaleString('id-ID') + 'k';
};
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
</script>

<style>
@media print {
    body.is-printing-biller-receipt * {
        visibility: hidden !important;
    }
    body.is-printing-biller-receipt #receipt-paper,
    body.is-printing-biller-receipt #receipt-paper * {
        visibility: visible !important;
    }
    body.is-printing-biller-receipt #receipt-paper {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 80mm !important;
        max-width: 80mm !important;
        margin: 0 !important;
        padding: 10px !important;
        background: white !important;
        color: black !important;
        font-size: 11px !important;
    }
    body.is-printing-biller-receipt .no-print {
        display: none !important;
    }
}
</style>
