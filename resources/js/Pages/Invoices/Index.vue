<template>
    <AppLayout title="Invoice Pelanggan" subtitle="Kelola tagihan pelanggan dan status pembayarannya">
        <!-- Summary Cards -->
        <div v-can="'invoices_card_summary'" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Card 1: Total Belum Lunas -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Belum Lunas</p>
                    <h3 class="text-xl font-black text-gray-900">{{ formatCurrency(stats.total_unpaid_amount) }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <!-- Card 2: Tagihan Jatuh Tempo -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tagihan Jatuh Tempo</p>
                    <h3 class="text-xl font-black text-red-600">{{ formatCurrency(stats.total_jatuh_tempo_amount) }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <!-- Card 3: Total Piutang -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Piutang</p>
                    <h3 class="text-xl font-black text-amber-600">{{ formatCurrency(stats.total_piutang_amount) }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <!-- Card 4: Pendapatan Bulan Ini -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Pendapatan Bulan Ini</p>
                    <h3 class="text-xl font-black text-emerald-600">{{ formatCurrency(stats.total_paid_amount) }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <!-- Tabs Section -->
        <div class="flex flex-wrap gap-3 mb-5 border-b border-gray-200 pb-3 overflow-x-auto custom-scrollbar">
            <button v-can="'invoices_tab_all'" @click="filterTab('semua')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'semua' || !filterTabVal ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Daftar Tagihan <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.semua }}</span>
            </button>
            <button v-can="'invoices_tab_unpaid'" @click="filterTab('jatuh_tempo')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'jatuh_tempo' ? 'text-red-600 border-b-2 border-red-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Tagihan Jatuh Tempo <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.jatuh_tempo }}</span>
            </button>
            <button v-can="'invoices_tab_unpaid'" @click="filterTab('piutang')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'piutang' ? 'text-amber-600 border-b-2 border-amber-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Piutang (Bayar Sebagian) <span class="bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.piutang }}</span>
            </button>
            <button v-can="'invoices_tab_paid'" @click="filterTab('lunas')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'lunas' ? 'text-emerald-600 border-b-2 border-emerald-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Lunas <span class="bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.lunas }}</span>
            </button>
            <button v-can="'invoices_tab_all'" @click="filterTab('prorata')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'prorata' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Pelanggan Prorata <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.prorata }}</span>
            </button>
            <button v-can="'invoices_change_package'" @click="filterTab('upgrade')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'upgrade' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                Upgrade / Downgrade <span class="bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.upgrade || 0 }}</span>
            </button>
            <button v-can="'invoices_promise'" @click="filterTab('janji_bayar')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'janji_bayar' ? 'text-yellow-600 border-b-2 border-yellow-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Janji Bayar <span class="bg-yellow-50 text-yellow-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.janji_bayar }}</span>
            </button>
            <button v-can="'invoices_tab_isolated'" @click="filterTab('stop_sementara')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'stop_sementara' ? 'text-orange-600 border-b-2 border-orange-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Stop Sementara <span class="bg-orange-50 text-orange-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.stop_sementara || 0 }}</span>
            </button>
            <button v-can="'invoices_tab_isolated'" @click="filterTab('stop_permanen')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'stop_permanen' ? 'text-red-800 border-b-2 border-red-800' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Stop Permanen <span class="bg-red-50 text-red-800 px-2 py-0.5 rounded-full text-[10px]">{{ stats.stop_permanen || 0 }}</span>
            </button>
        </div>

        <DataTable
            :columns="columns"
            :data="invoices.data"
            :pagination="invoices"
            searchPlaceholder="Cari nama atau area..."
            searchRoute="/invoices"
            :filters="filters"
        >
            <template #filters>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <!-- Tanggal (Start Date -> End Date) -->
                    <div v-can="'invoices_filter_period'" class="flex items-center gap-2 whitespace-nowrap text-sm text-gray-600 font-medium ml-2">
                        <span>Tanggal</span>
                        <input type="date" v-model="filterStartDate" class="form-input text-sm rounded-lg border-gray-300 w-[130px]" />
                        <span>s/d</span>
                        <input type="date" v-model="filterEndDate" class="form-input text-sm rounded-lg border-gray-300 w-[130px]" />
                    </div>

                    <!-- Status -->
                    <select v-can="'invoices_filter_status'" v-model="filterStatusVal" class="form-select text-sm rounded-lg border-gray-300 text-gray-700 min-w-[140px]">
                        <option value="">Semua Status</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partial</option>
                    </select>

                    <!-- Area -->
                    <select v-can="'invoices_filter_area'" v-model="filterAreaVal" class="form-select text-sm rounded-lg border-gray-300 text-gray-700 min-w-[140px]">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                    </select>

                    <button @click="applyFilters" class="btn-primary py-2 px-4 whitespace-nowrap ml-1 flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Terapkan
                    </button>
                </div>
            </template>
            
            <template #actions>
                <button v-can="'invoices_btn_export'" class="btn-secondary bg-white border border-gray-200 text-emerald-600 hover:bg-emerald-50 text-sm py-2 px-4 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </button>
                <button v-can="'invoices_print'" class="btn-secondary bg-white border border-gray-200 text-red-600 hover:bg-red-50 text-sm py-2 px-4 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak PDF
                </button>
            </template>

            <template #row="{ row, index }">
                <!-- Checkbox placeholder -->
                <td class="text-center w-10">
                    <input type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                </td>
                <td class="text-gray-500 text-xs text-center">
                    {{ (invoices.current_page - 1) * invoices.per_page + index + 1 }}
                </td>
                <td>
                    <div v-if="row.customer" class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-gray-700 bg-gray-100 shrink-0">
                            {{ row.customer.name?.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex flex-col gap-1">
                            <Link :href="`/customers/${row.customer.id}`" class="text-gray-900 font-bold text-xs uppercase hover:underline">
                                {{ row.customer.name }}
                            </Link>
                            <span v-if="row.customer.overdue_count > 0" class="inline-flex items-center gap-1 text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-md w-max border border-red-100" :title="'Total Tunggakan: ' + formatCurrency(row.customer.total_overdue)">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Nunggak {{ row.customer.overdue_count }} Bulan
                            </span>
                            <span v-else-if="row.customer.unpaid_count > 1" class="inline-flex items-center gap-1 text-[9px] font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-md w-max border border-amber-200" :title="'Total Tagihan: ' + formatCurrency(row.customer.total_unpaid)">
                                {{ row.customer.unpaid_count }} Tagihan Berjalan
                            </span>
                        </div>
                    </div>
                    <span v-else class="text-gray-400 italic text-xs">Pelanggan Dihapus</span>
                </td>
                <td>
                    <span v-if="row.customer?.area" class="text-xs text-gray-600 flex items-center gap-1">
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ row.customer.area }}
                    </span>
                    <span v-else class="text-gray-400">-</span>
                </td>
                <td><span class="text-xs text-gray-600 truncate max-w-[150px] inline-block">{{ row.customer?.address || '-' }}</span></td>
                <td>
                    <div class="flex flex-col gap-1 items-start">
                        <span class="text-[11px] font-bold text-gray-700 uppercase">{{ row.customer?.package || '-' }}</span>
                        <span v-if="row.is_prorata || row.billing_type === 'prorata'" class="text-[9px] font-bold px-1.5 py-0.5 rounded-md uppercase border bg-blue-50 text-blue-700 border-blue-200">
                            Prorata
                        </span>
                        <span v-else-if="row.billing_type" :class="[
                            'text-[9px] font-bold px-1.5 py-0.5 rounded-md uppercase border',
                            row.billing_type === 'prabayar' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                            'bg-amber-50 text-amber-700 border-amber-200'
                        ]">
                            {{ row.billing_type }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-gray-800" :title="'Tanggal Aktivasi Layanan: ' + (row.customer?.activation_date || '-')">
                            {{ row.customer?.activation_date || row.customer?.register_date || '-' }}
                        </span>
                        <span v-if="row.customer?.register_date && row.customer?.activation_date && row.customer?.register_date !== row.customer?.activation_date" class="text-[9px] text-gray-400 mt-0.5" :title="'Tanggal Booking/Registrasi: ' + row.customer?.register_date">
                            Reg: {{ row.customer?.register_date }}
                        </span>
                    </div>
                </td>
                <td>
                    <span v-if="row.last_payment_date" class="flex flex-col">
                        <span class="text-[11px] text-gray-600">{{ row.last_payment_date }}</span>
                        <span 
                            class="text-[9px] font-bold uppercase mt-0.5 tracking-tight flex items-center gap-0.5 flex-wrap"
                            :class="{
                                'text-emerald-600': row.last_payment_method === 'reseller_balance',
                                'text-indigo-600': row.last_payment_method === 'transfer' || row.last_payment_method === 'payment_gateway',
                                'text-blue-600': row.last_payment_method === 'cash',
                                'text-purple-600': row.last_payment_method === 'qris',
                            }"
                        >
                            <span>{{ formatPaymentMethod(row.last_payment_method) }}</span>
                            <template v-if="row.last_payment_method === 'transfer' && extractBankName(row.last_payment_notes)">
                                <span>- {{ extractBankName(row.last_payment_notes) }}</span>
                            </template>
                            <template v-else-if="row.last_payment_method === 'reseller_balance' && !row.last_payment_collector && extractResellerName(row.last_payment_notes)">
                                <span class="font-extrabold text-emerald-700">
                                    - {{ extractResellerName(row.last_payment_notes) }}
                                </span>
                            </template>
                        </span>

                        <!-- Akun / Petugas yang Memproses Invoice -->
                        <span v-if="row.last_payment_collector || extractResellerName(row.last_payment_notes)" class="text-[9px] text-slate-500 font-medium flex items-center gap-1 mt-0.5" :title="'Diproses oleh akun: ' + (row.last_payment_collector || extractResellerName(row.last_payment_notes))">
                            <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="truncate">Oleh: <strong class="text-slate-700 font-semibold">{{ row.last_payment_collector || extractResellerName(row.last_payment_notes) }}</strong></span>
                        </span>
                        <span v-else-if="row.last_payment_method === 'payment_gateway'" class="text-[9px] text-indigo-500 font-medium flex items-center gap-1 mt-0.5" title="Diproses otomatis oleh sistem payment gateway">
                            <svg class="w-2.5 h-2.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Oleh: <strong class="font-semibold">Sistem Otomatis</strong></span>
                        </span>
                    </span>
                    <span v-else class="text-[11px] text-gray-600">-</span>
                </td>
                <td>
                    <div class="text-xs font-bold text-gray-900">{{ formatCurrency(row.amount) }}</div>
                    <div v-if="row.status === 'partial'" class="text-[10px] font-bold text-rose-600 mt-0.5">
                        Sisa: {{ formatCurrency(row.remaining) }}
                    </div>
                </td>
                <td>
                    <span v-if="row.customer" :class="['px-2 py-1 text-[10px] font-medium rounded-md text-white whitespace-nowrap', row.customer.status === 'aktif' ? 'bg-blue-500' : 'bg-gray-500']">
                        {{ row.customer.status }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span :class="['px-2.5 py-1 text-[10px] font-bold rounded-md whitespace-nowrap flex items-center gap-1 w-max', statusClass(row.status)]">
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(row.status)"></span>
                            {{ formatStatus(row.status) }}
                        </span>
                        <a 
                            v-if="row.last_payment_proof" 
                            :href="row.last_payment_proof" 
                            target="_blank" 
                            class="inline-flex items-center justify-center p-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded border border-indigo-200 transition-colors shadow-2xs" 
                            title="Lihat Bukti Transfer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </a>
                    </div>
                </td>
                <td>
                    <div v-if="row.promise_date" class="flex items-center gap-1.5">
                        <button 
                            type="button"
                            @click="openPromiseModal(row)"
                            class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-md text-[10px] font-bold whitespace-nowrap flex items-center gap-1 w-max transition-colors cursor-pointer shadow-2xs"
                            title="Klik untuk ubah tanggal janji bayar"
                        >
                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ row.promise_date }}
                        </button>
                        <a 
                            :href="'/invoices/' + row.id + '/print-promise'" 
                            target="_blank"
                            class="p-1 text-amber-700 bg-amber-50 hover:bg-amber-100 rounded border border-amber-200 transition-colors cursor-pointer"
                            title="Cetak Surat Komitmen Janji Bayar"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </a>
                    </div>
                    <button 
                        v-else-if="row.status !== 'paid'" 
                        type="button" 
                        @click="openPromiseModal(row)" 
                        class="text-[10px] font-medium text-gray-400 hover:text-amber-600 hover:bg-amber-50 px-2 py-0.5 rounded-md border border-dashed border-gray-200 hover:border-amber-300 transition-colors flex items-center gap-1 cursor-pointer"
                        title="Atur tanggal janji bayar"
                    >
                        <span>+ Janji</span>
                    </button>
                    <span v-else class="text-gray-400 text-xs">-</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                    <!-- Tombol Bayar Lunas (Unpaid) -->
                    <button 
                        v-can="'invoices_pay'"
                        v-if="row.status === 'unpaid'" 
                        type="button"
                        @click="openPaymentModal(row, 'full')" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs hover:shadow-sm cursor-pointer"
                        title="Proses Pembayaran Lunas"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zM7 15h2m1 0h6"/></svg>
                        <span>Bayar Lunas</span>
                    </button>

                    <!-- Tombol Lunasi Sisa (Untuk Partial) -->
                    <button 
                        v-can="'invoices_pay'"
                        v-else-if="row.status === 'partial'" 
                        type="button"
                        @click="openPaymentModal(row, 'full')" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs hover:shadow-sm cursor-pointer" 
                        title="Bayar / Lunasi Sisa Tagihan"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Lunasi Sisa</span>
                    </button>

                    <!-- Tombol Bayar Sebagian (Cicil) untuk Unpaid & Partial -->
                    <button 
                        v-can="'invoices_pay'"
                        v-if="row.status === 'unpaid' || row.status === 'partial'" 
                        type="button"
                        @click="openPaymentModal(row, 'partial')" 
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 hover:border-amber-400 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer" 
                        title="Bayar Sebagian / Cicil Tagihan"
                    >
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Bayar Sebagian</span>
                    </button>

                    <!-- Tombol Rollback (Paid atau Partial) -->
                    <button 
                        v-can="'invoices_rollback'"
                        v-if="row.status === 'paid' || row.status === 'partial'" 
                        type="button"
                        @click="rollbackInvoice(row)" 
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 hover:border-rose-300 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer" 
                        title="Batalkan pembayaran / Rollback status tagihan"
                    >
                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <span>Rollback</span>
                    </button>

                    <!-- Tombol Cetak Cepat -->
                    <a 
                        v-can="'invoices_print'"
                        :href="'/invoices/' + row.id + '/print'" 
                        target="_blank" 
                        class="inline-flex items-center justify-center p-1.5 bg-white hover:bg-indigo-50 text-gray-500 hover:text-indigo-600 border border-gray-200 hover:border-indigo-200 rounded-lg transition-colors shadow-2xs" 
                        title="Cetak Invoice / Struk"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </a>

                    <!-- Tombol Menu Aksi Lainnya (...) -->
                    <button 
                        type="button" 
                        @click="toggleDropdown($event, row)" 
                        class="inline-flex items-center justify-center p-1.5 bg-white hover:bg-gray-100 text-gray-500 hover:text-gray-800 border border-gray-200 hover:border-gray-300 rounded-lg transition-colors shadow-2xs cursor-pointer"
                        :class="{ 'ring-2 ring-indigo-500 bg-gray-50 text-indigo-600': activeDropdownRow?.id === row.id }"
                        title="Menu Aksi Lainnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Action Dropdown Menu Floating -->
        <Teleport to="body">
            <div v-if="activeDropdownRow" class="fixed inset-0 z-[120]" @click="closeDropdown" @contextmenu.prevent="closeDropdown">
                <div 
                    :style="{ top: `${dropdownPos.top}px`, left: `${dropdownPos.left}px` }"
                    class="fixed w-60 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-[121] text-xs divide-y divide-gray-100 animate-fade-in-up"
                    @click.stop
                >
                    <!-- Header Info -->
                    <div class="px-4 py-2 bg-gray-50/70 -mt-2 rounded-t-2xl border-b border-gray-100 mb-1">
                        <div class="font-bold text-gray-900 truncate">{{ activeDropdownRow.customer?.name || 'Pelanggan' }}</div>
                        <div class="flex items-center gap-1.5 text-[10px] text-gray-500 font-mono mt-0.5">
                            <span class="font-bold text-indigo-600">#{{ activeDropdownRow.invoice_number }}</span>
                            <span>&bull;</span>
                            <span class="font-semibold text-gray-700">{{ formatCurrency(activeDropdownRow.amount) }}</span>
                        </div>
                        <div v-if="activeDropdownRow.last_payment_collector" class="text-[10px] text-slate-500 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Diproses: <strong class="text-slate-800 font-bold">{{ activeDropdownRow.last_payment_collector }}</strong></span>
                        </div>
                        <div v-if="activeDropdownRow.last_payment_proof" class="mt-1.5 pt-1.5 border-t border-gray-200/60">
                            <a :href="activeDropdownRow.last_payment_proof" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50/80 hover:bg-indigo-100 px-2 py-1 rounded-lg transition-colors w-full justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Lihat Bukti Transfer</span>
                            </a>
                        </div>
                    </div>

                    <!-- Grup 1: Penagihan & Keuangan -->
                    <div class="py-1">
                        <!-- Bayar Lunas / Lunasi Sisa -->
                        <button 
                            v-can="'invoices_pay'"
                            v-if="activeDropdownRow.status === 'unpaid' || activeDropdownRow.status === 'partial'"
                            type="button" 
                            @click="openPaymentModal(activeDropdownRow, 'full'); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-emerald-800 hover:bg-emerald-50 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zM7 15h2m1 0h6"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold text-emerald-900">{{ activeDropdownRow.status === 'partial' ? 'Lunasi Sisa Tagihan' : 'Bayar Lunas' }}</span>
                                <p class="text-[10px] text-emerald-600 font-normal">Bayar penuh seluruh sisa tagihan</p>
                            </div>
                        </button>

                        <!-- Bayar Sebagian (Cicil) -->
                        <button 
                            v-can="'invoices_pay'"
                            v-if="activeDropdownRow.status === 'unpaid' || activeDropdownRow.status === 'partial'"
                            type="button" 
                            @click="openPaymentModal(activeDropdownRow, 'partial'); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-amber-800 hover:bg-amber-50 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-amber-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold text-amber-900">Bayar Sebagian (Cicil)</span>
                                <p class="text-[10px] text-amber-600/80 font-normal">Bayar sebagian / cicilan tagihan</p>
                            </div>
                        </button>

                        <!-- Janji Bayar -->
                        <button 
                            v-can="'invoices_promise'"
                            v-if="activeDropdownRow.status === 'unpaid' || activeDropdownRow.status === 'partial'"
                            type="button" 
                            @click="openPromiseModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-gray-700 hover:bg-amber-50 hover:text-amber-800 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-amber-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold">{{ activeDropdownRow.promise_date ? 'Ubah Janji Bayar' : 'Atur Janji Bayar' }}</span>
                                <p class="text-[10px] text-gray-400 font-normal">Tentukan tanggal komitmen</p>
                            </div>
                        </button>

                        <!-- Cetak Surat Janji Bayar -->
                        <a 
                            v-if="activeDropdownRow.promise_date"
                            :href="'/invoices/' + activeDropdownRow.id + '/print-promise'" 
                            target="_blank"
                            @click="closeDropdown"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-gray-700 hover:bg-amber-50 hover:text-amber-800 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold text-amber-900">Surat Janji Bayar</span>
                                <p class="text-[10px] text-amber-600 font-normal">Cetak surat pernyataan komitmen</p>
                            </div>
                        </a>

                        <!-- Edit Tagihan -->
                        <button 
                            v-can="'invoices_edit'"
                            type="button" 
                            @click="openEditModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-800 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-blue-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold">Edit Tagihan</span>
                                <p class="text-[10px] text-gray-400 font-normal">Ubah nominal / jatuh tempo</p>
                            </div>
                        </button>

                        <!-- Cetak Tagihan -->
                        <a 
                            v-can="'invoices_print'"
                            :href="'/invoices/' + activeDropdownRow.id + '/print'" 
                            target="_blank"
                            @click="closeDropdown"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-800 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-indigo-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold">Cetak Invoice PDF</span>
                                <p class="text-[10px] text-gray-400 font-normal">Buka struk & cetak thermal</p>
                            </div>
                        </a>
                    </div>

                    <!-- Grup 2: Layanan & Paket Pelanggan -->
                    <div v-if="activeDropdownRow.customer" class="py-1">
                        <!-- Upgrade / Downgrade -->
                        <button 
                            v-can="'invoices_change_package'"
                            type="button" 
                            @click="openUpgradeModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-gray-700 hover:bg-purple-50 hover:text-purple-800 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-purple-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold">Upgrade / Downgrade</span>
                                <p class="text-[10px] text-gray-400 font-normal">Ganti paket internet</p>
                            </div>
                        </button>

                        <!-- Stop Sementara -->
                        <button 
                            v-can="'customers_active_suspend'"
                            v-if="activeDropdownRow.customer.status !== 'suspended'"
                            type="button" 
                            @click="stopSementara(activeDropdownRow); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-gray-700 hover:bg-orange-50 hover:text-orange-800 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-orange-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold">Stop Sementara</span>
                                <p class="text-[10px] text-gray-400 font-normal">Isolir sementara layanan</p>
                            </div>
                        </button>

                        <!-- Stop Permanen & CBP -->
                        <button 
                            v-can="'customers_active_terminate'"
                            v-if="activeDropdownRow.customer.status !== 'terminated'"
                            type="button" 
                            @click="openStopPermanenModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-rose-700 hover:bg-rose-50 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-rose-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold">Stop Permanen & CBP</span>
                                <p class="text-[10px] text-rose-400 font-normal">Putus & tarik perangkat</p>
                            </div>
                        </button>
                    </div>

                    <!-- Grup 3: Hapus -->
                    <div class="py-1">
                        <button 
                            v-can="'invoices_delete'"
                            type="button" 
                            @click="deleteInvoice(activeDropdownRow); closeDropdown()"
                            class="w-full px-4 py-2 text-left flex items-center gap-2.5 text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors font-medium cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-md bg-red-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div>
                                <span class="font-semibold">Hapus Tagihan</span>
                                <p class="text-[10px] text-red-400 font-normal">Hapus permanen invoice ini</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Payment Modal -->
        <Teleport to="body">
            <div v-if="showPaymentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closePaymentModal"></div>

                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-md relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">Pembayaran Tagihan</h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black tracking-wider uppercase"
                                    :class="paymentMode === 'full' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'">
                                    {{ paymentMode === 'full' ? 'Pelunasan' : 'Bayar Sebagian' }}
                                </span>
                            </div>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ selectedInvoice?.invoice_number }} &bull; {{ selectedInvoice?.customer?.name }}</p>
                        </div>
                        <button @click="closePaymentModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPayment">
                        <div class="p-6 space-y-4 max-h-[78vh] overflow-y-auto">
                            
                            <!-- Info Tunggakan Smart Allocation -->
                            <div v-if="selectedInvoice?.customer?.overdue_count > 0" class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex gap-3">
                                <div class="shrink-0 text-rose-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-rose-800">Pelanggan memiliki {{ selectedInvoice?.customer?.overdue_count }} tagihan menunggak (lewat jatuh tempo)!</h4>
                                    <p class="text-[11px] text-rose-600 mt-1">Total tunggakan: <strong>{{ formatCurrency(selectedInvoice?.customer?.total_overdue) }}</strong>.</p>
                                    <p class="text-[10px] text-rose-600/80 mt-1 leading-snug">Sistem Alokasi Cerdas: Jika nominal pembayaran lebih besar dari 1 tagihan, sistem otomatis melunasi tagihan yang paling lama terlebih dahulu.</p>
                                </div>
                            </div>
                            <div v-else-if="selectedInvoice?.customer?.unpaid_count > 1" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
                                <div class="shrink-0 text-amber-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-amber-800">Pelanggan memiliki {{ selectedInvoice?.customer?.unpaid_count }} tagihan aktif berjalan.</h4>
                                    <p class="text-[11px] text-amber-700 mt-1">Total seluruh tagihan: <strong>{{ formatCurrency(selectedInvoice?.customer?.total_unpaid) }}</strong>.</p>
                                </div>
                            </div>

                            <!-- Tab Pilihan: Bayar Lunas vs Bayar Sebagian -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Pembayaran</label>
                                <div class="grid grid-cols-2 p-1 bg-gray-100 rounded-xl gap-1">
                                    <button 
                                        type="button" 
                                        @click="setPaymentMode('full')"
                                        class="py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                        :class="paymentMode === 'full' ? 'bg-white text-emerald-700 shadow-sm border border-emerald-100' : 'text-gray-500 hover:text-gray-900'"
                                    >
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Bayar Lunas</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="setPaymentMode('partial')"
                                        class="py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                        :class="paymentMode === 'partial' ? 'bg-white text-amber-700 shadow-sm border border-amber-100' : 'text-gray-500 hover:text-gray-900'"
                                    >
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Bayar Sebagian (Cicil)</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Nominal Input -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                        {{ paymentMode === 'full' ? 'Nominal Pelunasan' : 'Nominal Cicilan / Sebagian' }}
                                    </label>
                                    <span class="text-[11px] font-bold text-gray-500 font-mono">
                                        Total Sisa: {{ formatCurrency(selectedInvoice?.remaining || 0) }}
                                    </span>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold sm:text-sm">Rp</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        v-model.number="paymentForm.amount" 
                                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-lg font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                                        placeholder="0"
                                        min="1"
                                    >
                                </div>

                                <!-- Preset Cepat untuk Bayar Sebagian -->
                                <div v-if="paymentMode === 'partial'" class="flex items-center gap-1.5 mt-2">
                                    <span class="text-[10px] text-gray-400 font-medium">Preset:</span>
                                    <button 
                                        type="button" 
                                        @click="paymentForm.amount = Math.round((selectedInvoice?.remaining || 0) * 0.25)" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer"
                                    >25%</button>
                                    <button 
                                        type="button" 
                                        @click="paymentForm.amount = Math.round((selectedInvoice?.remaining || 0) * 0.5)" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer"
                                    >50%</button>
                                    <button 
                                        type="button" 
                                        @click="paymentForm.amount = Math.round((selectedInvoice?.remaining || 0) * 0.75)" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer"
                                    >75%</button>
                                    <button 
                                        type="button" 
                                        @click="setPaymentMode('full')" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-md transition-colors ml-auto cursor-pointer"
                                    >Lunasi Penuh</button>
                                </div>

                                <!-- Live Kalkulasi Sisa Tagihan -->
                                <div v-if="paymentMode === 'partial'" class="mt-2.5 p-2.5 bg-amber-50/80 border border-amber-200 rounded-xl flex items-center justify-between text-xs">
                                    <div class="text-amber-800 font-medium">Sisa tagihan setelah cicilan ini:</div>
                                    <div class="font-bold font-mono" :class="remainingAfterPay > 0 ? 'text-amber-900' : 'text-emerald-700'">
                                        {{ formatCurrency(Math.max(0, remainingAfterPay)) }}
                                        <span v-if="remainingAfterPay <= 0" class="text-[10px] text-emerald-600 block text-right font-sans font-normal">(Akan Lunas)</span>
                                    </div>
                                </div>

                                <div v-if="paymentMode === 'full'" class="flex justify-between mt-2">
                                    <span class="text-[10px] text-gray-500">Tagihan saat ini: {{ formatCurrency(selectedInvoice?.remaining || 0) }}</span>
                                    <button v-if="selectedInvoice?.customer?.unpaid_count > 1" type="button" @click="paymentForm.amount = selectedInvoice?.customer?.total_unpaid" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 cursor-pointer">Bayar Semua Tagihan</button>
                                </div>
                            </div>

                            <!-- Opsi Status Koneksi Internet Pelanggan -->
                            <div class="bg-gray-50/90 rounded-xl p-3.5 border border-gray-200">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                                        <span>Status Internet Pelanggan</span>
                                    </label>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="selectedInvoice?.customer?.status === 'suspended' ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="selectedInvoice?.customer?.status === 'suspended' ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'"></span>
                                        {{ selectedInvoice?.customer?.status === 'suspended' ? 'Saat Ini Terisolir' : 'Saat Ini Aktif' }}
                                    </span>
                                </div>
                                
                                <p class="text-[11px] text-gray-500 mb-2.5 leading-snug">
                                    Pilih apakah koneksi internet pelanggan dinyalakan atau dimatikan (sync MikroTik & RADIUS):
                                </p>

                                <div class="grid grid-cols-2 gap-2.5">
                                    <!-- Opsi Nyalakan -->
                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="paymentForm.internet_action" value="on" class="peer sr-only" />
                                        <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-3 flex flex-col items-center text-center gap-1.5 transition-all hover:border-emerald-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70 peer-checked:ring-1 peer-checked:ring-emerald-500">
                                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-800 peer-checked:text-emerald-800">Nyalakan Internet</div>
                                                <div class="text-[10px] text-gray-400 peer-checked:text-emerald-600 font-medium leading-tight mt-0.5">Buka isolir (Aktif)</div>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Opsi Matikan -->
                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="paymentForm.internet_action" value="off" class="peer sr-only" />
                                        <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-3 flex flex-col items-center text-center gap-1.5 transition-all hover:border-rose-300 peer-checked:border-rose-500 peer-checked:bg-rose-50/70 peer-checked:ring-1 peer-checked:ring-rose-500">
                                            <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-800 peer-checked:text-rose-800">Matikan Internet</div>
                                                <div class="text-[10px] text-gray-400 peer-checked:text-rose-600 font-medium leading-tight mt-0.5">Tetap isolir (Suspend)</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Method Selection -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-3">Pilih Metode Pembayaran</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="paymentForm.method" value="cash" class="peer sr-only" />
                                        <div class="rounded-xl border-2 border-gray-100 bg-white p-3 flex flex-col items-center justify-center gap-2 transition-all hover:border-gray-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-600">
                                            <svg class="w-6 h-6 text-gray-400 peer-checked:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zM7 15h2m1 0h6"/></svg>
                                            <span class="text-xs font-bold text-gray-600 peer-checked:text-indigo-700">Tunai / Cash</span>
                                        </div>
                                    </label>
                                    
                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="paymentForm.method" value="transfer" class="peer sr-only" />
                                        <div class="rounded-xl border-2 border-gray-100 bg-white p-3 flex flex-col items-center justify-center gap-2 transition-all hover:border-gray-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-600">
                                            <svg class="w-6 h-6 text-gray-400 peer-checked:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                            <span class="text-xs font-bold text-gray-600 peer-checked:text-indigo-700">Transfer Bank</span>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="paymentForm.method" value="qris" class="peer sr-only" />
                                        <div class="rounded-xl border-2 border-gray-100 bg-white p-3 flex flex-col items-center justify-center gap-2 transition-all hover:border-gray-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-600">
                                            <svg class="w-6 h-6 text-gray-400 peer-checked:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                            <span class="text-xs font-bold text-gray-600 peer-checked:text-indigo-700">Scan QRIS</span>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="paymentForm.method" value="payment_gateway" class="peer sr-only" />
                                        <div class="rounded-xl border-2 border-gray-100 bg-white p-3 flex flex-col items-center justify-center gap-2 transition-all hover:border-gray-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-600">
                                            <svg class="w-6 h-6 text-gray-400 peer-checked:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                            <span class="text-xs font-bold text-gray-600 peer-checked:text-indigo-700">Payment Gateway</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- List Rekening (Jika Transfer) -->
                            <div v-if="paymentForm.method === 'transfer' && payment_banks && payment_banks.length > 0" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-3">Tujuan Transfer</label>
                                <div class="space-y-2">
                                    <label v-for="(bank, i) in payment_banks" :key="i" class="flex items-center justify-between p-3 bg-white rounded-lg border shadow-sm cursor-pointer transition-all" :class="paymentForm.selected_bank === bank ? 'border-indigo-500 ring-1 ring-indigo-500 bg-indigo-50/50' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" v-model="paymentForm.selected_bank" :value="bank" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded-full" />
                                            <div>
                                                <p class="text-sm font-bold" :class="paymentForm.selected_bank === bank ? 'text-indigo-900' : 'text-gray-900'">{{ bank.bank_name }}</p>
                                                <p class="text-xs mt-0.5" :class="paymentForm.selected_bank === bank ? 'text-indigo-700' : 'text-gray-500'">a.n. {{ bank.account_name }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold font-mono tracking-wider" :class="paymentForm.selected_bank === bank ? 'text-indigo-700' : 'text-indigo-600'">{{ bank.account_number }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div v-else-if="paymentForm.method === 'transfer'" class="bg-amber-50 text-amber-700 p-3 rounded-xl border border-amber-200 text-xs text-center">
                                Belum ada data rekening yang diatur. Silakan atur di Pengaturan Billing.
                            </div>

                            <!-- Upload Bukti Transfer (Khusus Transfer Bank) -->
                            <div v-if="paymentForm.method === 'transfer'" class="space-y-1.5">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                    Upload Bukti Transfer (Bukti TF)
                                </label>
                                
                                <div class="relative">
                                    <input 
                                        type="file" 
                                        ref="proofFileInput" 
                                        @change="handleProofUpload" 
                                        accept="image/*,.pdf" 
                                        class="hidden" 
                                        id="invoice-proof-input"
                                    />

                                    <!-- Preview jika ada file yang dipilih -->
                                    <div v-if="proofPreview || paymentForm.proof_image" class="p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <img v-if="proofPreview" :src="proofPreview" alt="Bukti Transfer" class="w-12 h-12 object-cover rounded-lg border border-indigo-200 shadow-2xs shrink-0" />
                                            <div v-else class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center shrink-0">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-gray-900 truncate">{{ proofFileName || 'Bukti Transfer Terpilih' }}</p>
                                                <p class="text-[10px] text-gray-500 font-medium">{{ proofFileSize || 'Siap diupload' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <button type="button" @click="$refs.proofFileInput?.click()" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-white border border-indigo-200 hover:bg-indigo-50 px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                                                Ganti
                                            </button>
                                            <button type="button" @click="removeProofFile" class="text-[11px] font-bold text-rose-600 hover:text-rose-800 bg-white border border-rose-200 hover:bg-rose-50 px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Area Upload Kosong -->
                                    <label 
                                        v-else 
                                        for="invoice-proof-input" 
                                        class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-200 hover:border-indigo-400 rounded-xl bg-gray-50/70 hover:bg-indigo-50/30 cursor-pointer transition-all group"
                                    >
                                        <div class="w-10 h-10 rounded-full bg-white shadow-2xs border border-gray-100 flex items-center justify-center text-gray-400 group-hover:text-indigo-600 group-hover:scale-105 transition-all mb-1.5">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <span class="text-xs font-bold text-gray-700 group-hover:text-indigo-600 transition-colors">Klik untuk upload bukti transfer (foto/PDF)</span>
                                        <span class="text-[10px] text-gray-400 mt-0.5">Format: JPG, PNG, WEBP, atau PDF (Maks. 5MB)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Catatan Tambahan (Opsional)</label>
                                <textarea v-model="paymentForm.notes" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none" placeholder="Cth: Titip di satpam / Pembayaran bulan ini dan depan..."></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
                            <button type="button" @click="closePaymentModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-xl transition-colors cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="paymentForm.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-sm shadow-indigo-200 flex items-center gap-2 cursor-pointer">
                                <svg v-if="paymentForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ paymentForm.processing ? 'Memproses...' : (paymentMode === 'partial' ? 'Konfirmasi Bayar Sebagian' : 'Konfirmasi Pembayaran Lunas') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Promise Modal -->
        <Teleport to="body">
            <div v-if="showPromiseModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closePromiseModal"></div>

                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-md relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-amber-50/60">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">Atur Janji Bayar</h3>
                                <span v-if="selectedInvoice?.promise_date" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-900">
                                    Aktif
                                </span>
                            </div>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ selectedInvoice?.invoice_number }} &bull; {{ selectedInvoice?.customer?.name }}</p>
                        </div>
                        <button @click="closePromiseModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPromise">
                        <div class="p-6 space-y-4 max-h-[78vh] overflow-y-auto">
                            <!-- Info Sisa Tagihan -->
                            <div class="p-3 bg-amber-50/70 border border-amber-200/70 rounded-xl flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-600">Total Sisa Tagihan:</span>
                                <span class="text-sm font-black font-mono text-amber-900">{{ formatCurrency(selectedInvoice?.remaining || 0) }}</span>
                            </div>

                            <!-- Input Tanggal Janji Bayar -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                                    Tanggal Janji Bayar <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    v-model="promiseForm.promise_date" 
                                    required 
                                    :min="new Date().toISOString().split('T')[0]" 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                >
                                
                                <!-- Preset Cepat Tanggal -->
                                <div class="flex items-center gap-1.5 mt-2">
                                    <span class="text-[10px] text-gray-400 font-medium">Preset:</span>
                                    <button 
                                        type="button" 
                                        @click="setPromisePreset(1)" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer"
                                    >+1 Hari</button>
                                    <button 
                                        type="button" 
                                        @click="setPromisePreset(3)" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer"
                                    >+3 Hari</button>
                                    <button 
                                        type="button" 
                                        @click="setPromisePreset(7)" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer"
                                    >+7 Hari</button>
                                    <button 
                                        type="button" 
                                        @click="setPromiseEndOfMonth" 
                                        class="text-[10px] font-bold px-2 py-0.5 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-md transition-colors ml-auto cursor-pointer"
                                    >Akhir Bulan</button>
                                </div>
                            </div>

                            <!-- Opsi Status Koneksi Internet Pelanggan -->
                            <div class="bg-gray-50/90 rounded-xl p-3.5 border border-gray-200">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                                        <span>Status Internet Pelanggan</span>
                                    </label>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="selectedInvoice?.customer?.status === 'suspended' ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="selectedInvoice?.customer?.status === 'suspended' ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'"></span>
                                        {{ selectedInvoice?.customer?.status === 'suspended' ? 'Saat Ini Terisolir' : 'Saat Ini Aktif' }}
                                    </span>
                                </div>
                                
                                <p class="text-[11px] text-gray-500 mb-2.5 leading-snug">
                                    Pilih apakah koneksi internet dinyalakan atau dimatikan (sync MikroTik & RADIUS) selama periode janji bayar:
                                </p>

                                <div class="grid grid-cols-2 gap-2.5">
                                    <!-- Opsi Nyalakan -->
                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="promiseForm.internet_action" value="on" class="peer sr-only" />
                                        <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-3 flex flex-col items-center text-center gap-1.5 transition-all hover:border-emerald-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70 peer-checked:ring-1 peer-checked:ring-emerald-500">
                                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-800 peer-checked:text-emerald-800">Nyalakan Internet</div>
                                                <div class="text-[10px] text-gray-400 peer-checked:text-emerald-600 font-medium leading-tight mt-0.5">Buka isolir (Kompensasi)</div>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Opsi Matikan -->
                                    <label class="cursor-pointer relative">
                                        <input type="radio" v-model="promiseForm.internet_action" value="off" class="peer sr-only" />
                                        <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-3 flex flex-col items-center text-center gap-1.5 transition-all hover:border-rose-300 peer-checked:border-rose-500 peer-checked:bg-rose-50/70 peer-checked:ring-1 peer-checked:ring-rose-500">
                                            <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-800 peer-checked:text-rose-800">Matikan Internet</div>
                                                <div class="text-[10px] text-gray-400 peer-checked:text-rose-600 font-medium leading-tight mt-0.5">Tetap isolir (Suspend)</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <p class="text-[11px] text-gray-500 leading-snug bg-amber-50/50 p-2.5 rounded-xl border border-amber-200/50">
                                Tagihan ini akan ditandai dengan tanggal komitmen bayar. Jika melewati tanggal tersebut dan belum lunas, sistem otomatis mengisolir kembali.
                            </p>

                            <!-- Checkbox Buat Surat Janji Bayar -->
                            <div class="flex items-center gap-2.5 p-3 bg-amber-50/80 rounded-xl border border-amber-200 transition-colors">
                                <input 
                                    type="checkbox" 
                                    id="print_promise_checkbox" 
                                    v-model="printAfterSave" 
                                    class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500 cursor-pointer"
                                />
                                <label for="print_promise_checkbox" class="text-xs font-bold text-amber-950 flex items-center gap-1.5 cursor-pointer select-none">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>Buat & Buka Surat Janji Bayar setelah disimpan</span>
                                </label>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <button 
                                    v-if="selectedInvoice?.promise_date"
                                    type="button" 
                                    @click="removePromiseDate" 
                                    class="px-3.5 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition-colors border border-rose-200 cursor-pointer"
                                >
                                    Hapus Janji Bayar
                                </button>
                                <a 
                                    v-if="selectedInvoice?.promise_date"
                                    :href="'/invoices/' + selectedInvoice?.id + '/print-promise'" 
                                    target="_blank" 
                                    class="px-3.5 py-2 text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-xl transition-colors border border-amber-300 flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                >
                                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>Cetak Surat</span>
                                </a>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="closePromiseModal" class="px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-xl transition-colors cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" :disabled="promiseForm.processing" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold transition-all shadow-sm shadow-amber-200 flex items-center gap-2 cursor-pointer">
                                    <svg v-if="promiseForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ promiseForm.processing ? 'Menyimpan...' : 'Simpan Janji Bayar' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Edit Invoice Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeEditModal"></div>

                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-md relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Edit Tagihan</h3>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ selectedInvoice?.invoice_number }} &bull; {{ selectedInvoice?.customer?.name }}</p>
                        </div>
                        <button @click="closeEditModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nominal Tagihan</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500">Rp</span>
                                    </div>
                                    <input type="number" v-model="editForm.amount" class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" required>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Terbit</label>
                                    <input type="date" v-model="editForm.issued_date" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Jatuh Tempo</label>
                                    <input type="date" v-model="editForm.due_date" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" required>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2">
                            <button type="button" @click="closeEditModal" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 hover:border-gray-300 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="editForm.processing" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <svg v-if="editForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Stop Sementara Modal -->
        <Teleport to="body">
            <div v-if="showStopSementaraModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeStopSementaraModal"></div>

                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-sm relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-orange-100 flex items-center gap-3 bg-orange-50/50">
                        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Stop Sementara</h3>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">Suspend sementara pelanggan {{ selectedInvoice?.customer?.name }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitStopSementara">
                        <div class="p-6 space-y-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Harap tentukan tanggal mulai dan tanggal selesai untuk menghentikan layanan (isolir) sementara. Tagihan baru tidak akan tercetak selama masa ini.
                            </p>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Mulai (Start Date)</label>
                                <input type="date" v-model="stopSementaraForm.start_date" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-orange-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Selesai (End Date)</label>
                                <input type="date" v-model="stopSementaraForm.end_date" :min="stopSementaraForm.start_date" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-orange-500" required>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-2">
                            <button type="button" @click="closeStopSementaraModal" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="stopSementaraForm.processing" class="bg-orange-500 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-orange-500/20 hover:bg-orange-600 transition-all disabled:opacity-50 flex items-center gap-2">
                                {{ stopSementaraForm.processing ? 'Menyimpan...' : 'Stop Sementara' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Upgrade / Downgrade Modal -->
        <Teleport to="body">
            <div v-if="showUpgradeModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0 overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeUpgradeModal"></div>

                <div class="relative bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden z-10 my-8">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-50 via-indigo-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-md shadow-purple-600/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Upgrade / Downgrade Paket</h3>
                                <p class="text-xs text-gray-500 font-medium">Pelanggan: <span class="font-bold text-gray-700">{{ selectedUpgradeInvoice?.customer?.name }}</span></p>
                            </div>
                        </div>
                        <button @click="closeUpgradeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitUpgrade">
                        <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                            <!-- Paket Saat Ini vs Paket Baru -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <!-- Paket Saat Ini -->
                                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/70">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Paket Saat Ini</div>
                                    <div class="text-sm font-bold text-gray-900 truncate">{{ selectedUpgradeInvoice?.customer?.package_name || 'Tidak ada paket' }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Kecepatan: <span class="font-semibold text-gray-700">{{ selectedUpgradeInvoice?.customer?.package_speed || 0 }} Mbps</span></div>
                                    <div class="text-xs text-indigo-600 font-bold mt-1">{{ formatCurrency(selectedUpgradeInvoice?.customer?.package_price || 0) }} / bln</div>
                                </div>

                                <!-- Paket Baru -->
                                <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/40">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">Pilih Paket Baru</span>
                                        <span v-if="selectedNewPackage" :class="isUpgrade ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-amber-100 text-amber-700 border-amber-200'" class="px-1.5 py-0.5 rounded text-[10px] font-black border">
                                            {{ isUpgrade ? '⬆ Upgrade' : '⬇ Downgrade' }}
                                        </span>
                                    </div>
                                    <select v-model="upgradeForm.new_package_id" required class="w-full text-xs font-semibold px-2.5 py-2 bg-white border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                        <option value="" disabled>-- Pilih Paket --</option>
                                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                            {{ pkg.name }} ({{ pkg.speed_mbps }} Mbps) - {{ formatCurrency(pkg.price) }}
                                        </option>
                                    </select>
                                    <div v-if="selectedNewPackage" class="text-[11px] text-gray-600 mt-1.5 flex items-center justify-between">
                                        <span>Selisih Harga:</span>
                                        <span :class="priceDiff >= 0 ? 'text-emerald-600' : 'text-amber-600'" class="font-bold">
                                            {{ priceDiff >= 0 ? '+' : '' }}{{ formatCurrency(priceDiff) }} / bln
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Tanggal Terbit & Tanggal Perubahan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-gray-800">Tanggal Terbit Invoice</label>
                                        <span class="text-[10px] text-indigo-600 font-semibold bg-indigo-100/70 px-1.5 py-0.5 rounded">Awal Siklus</span>
                                    </div>
                                    <input type="date" v-model="upgradeForm.start_date" required class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white" />
                                    <p class="text-[10px] text-gray-500 mt-1">
                                        Default sesuai terbit invoice (Pengaturan: Tgl {{ props.invoice_issue_date || 1 }})
                                    </p>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-gray-800">Tanggal Bayar / Ganti Paket</label>
                                        <span class="text-[10px] text-purple-600 font-semibold bg-purple-100/70 px-1.5 py-0.5 rounded">Tanggal Efektif</span>
                                    </div>
                                    <input type="date" v-model="upgradeForm.effective_date" required class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white" />
                                    <p class="text-[10px] text-gray-500 mt-1">
                                        Total siklus penagihan: <strong>{{ calculatedTotalCycleDays }} hari</strong>
                                    </p>
                                </div>
                            </div>

                            <!-- Rincian Prorata Dua Arah: Pemakaian Ke Belakang & Ke Depan -->
                            <div v-if="selectedNewPackage && upgradeForm.billing_action === 'prorata_diff'" class="space-y-3">
                                <div class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center justify-between">
                                    <span>Rincian Tagihan Prorata Siklus Penagihan</span>
                                    <span class="text-[10px] font-normal text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">Ke Belakang + Ke Depan</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <!-- Pemakaian Ke Belakang (Paket Lama) -->
                                    <div class="p-3.5 bg-amber-50/60 border border-amber-200 rounded-xl relative overflow-hidden">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] font-extrabold text-amber-800 uppercase tracking-wide">1. Pemakaian Ke Belakang</span>
                                            <span class="text-[10px] font-bold bg-amber-200/80 text-amber-900 px-1.5 py-0.5 rounded">
                                                {{ calculatedPastDays }} Hari
                                            </span>
                                        </div>
                                        <div class="text-[11px] font-semibold text-gray-700">
                                            {{ formatShortDate(calculatedStartDate) }} s/d {{ formatShortDate(calculatedPastEndDate) }}
                                        </div>
                                        <div class="text-xs font-bold text-gray-800 truncate mt-0.5">
                                            {{ selectedUpgradeInvoice?.customer?.package_name || 'Paket Lama' }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-1">
                                            ({{ calculatedPastDays }}/{{ calculatedTotalCycleDays }}) &times; {{ formatCurrency(currentPackagePrice) }}
                                        </div>
                                        <div class="text-sm font-black text-amber-700 mt-1.5">
                                            {{ formatCurrency(calculatedPastAmount) }}
                                        </div>
                                    </div>

                                    <!-- Pemakaian Ke Depan (Paket Baru) -->
                                    <div class="p-3.5 bg-emerald-50/60 border border-emerald-200 rounded-xl relative overflow-hidden">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] font-extrabold text-emerald-800 uppercase tracking-wide">2. Pemakaian Ke Depan</span>
                                            <span class="text-[10px] font-bold bg-emerald-200/80 text-emerald-900 px-1.5 py-0.5 rounded">
                                                {{ calculatedFutureDays }} Hari
                                            </span>
                                        </div>
                                        <div class="text-[11px] font-semibold text-gray-700">
                                            {{ formatShortDate(calculatedEffectiveDate) }} s/d {{ formatShortDate(calculatedFutureEndDate) }}
                                        </div>
                                        <div class="text-xs font-bold text-emerald-900 truncate mt-0.5">
                                            {{ selectedNewPackage?.name }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-1">
                                            ({{ calculatedFutureDays }}/{{ calculatedTotalCycleDays }}) &times; {{ formatCurrency(selectedNewPackage?.price) }}
                                        </div>
                                        <div class="text-sm font-black text-emerald-700 mt-1.5">
                                            {{ formatCurrency(calculatedFutureAmount) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Box Ringkasan Total Tagihan Prorata -->
                                <div class="p-3.5 bg-gradient-to-r from-purple-900 to-indigo-900 text-white rounded-xl shadow-md">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-[10px] uppercase font-bold text-purple-200 tracking-wider">Total Tagihan Prorata Bulan Ini</div>
                                            <div class="text-[11px] text-purple-300 mt-0.5">
                                                {{ formatCurrency(calculatedPastAmount) }} (Lama) + {{ formatCurrency(calculatedFutureAmount) }} (Baru)
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-black text-white">
                                                {{ formatCurrency(calculatedTotalProrata) }}
                                            </div>
                                            <div class="text-[10px] text-purple-200">
                                                <span v-if="selectedUpgradeInvoice?.status === 'paid'">
                                                    Selisih tagihan: <span class="font-bold underline">{{ formatCurrency(calculatedTotalProrata - Number(selectedUpgradeInvoice?.amount || 0)) }}</span>
                                                </span>
                                                <span v-else>
                                                    Tagihan awal: {{ formatCurrency(selectedUpgradeInvoice?.amount || 0) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Opsi Billing Action -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-2">Pilihan Penyesuaian Tagihan</label>
                                <div class="space-y-2.5">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="upgradeForm.billing_action === 'prorata_diff' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-500' : 'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="prorata_diff" v-model="upgradeForm.billing_action" class="mt-1 text-purple-600 focus:ring-purple-500" />
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-800">Hitung Prorata Lengkap (Ke Belakang &amp; Ke Depan)</span>
                                                <span v-if="selectedNewPackage" class="text-xs font-black px-2 py-0.5 rounded bg-white border border-purple-200 text-purple-700">
                                                    {{ formatCurrency(calculatedTotalProrata) }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                Membagi tagihan berdasarkan pemakaian paket lama (tgl 1 s/d saat bayar/ganti) + paket baru (saat ganti s/d akhir bulan).
                                            </p>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="upgradeForm.billing_action === 'replace_invoice' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-500' : 'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="replace_invoice" v-model="upgradeForm.billing_action" class="mt-1 text-purple-600 focus:ring-purple-500" />
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-800">Ubah Nominal Penuh ke Tarif Paket Baru</span>
                                                <span v-if="selectedNewPackage" class="text-xs font-black px-2 py-0.5 rounded bg-white border border-purple-200 text-purple-700">
                                                    {{ formatCurrency(selectedNewPackage?.price) }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                Mengabaikan prorata masa lalu; total tagihan invoice langsung diganti dengan tarif utuh paket baru.
                                            </p>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="upgradeForm.billing_action === 'next_month_only' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-500' : 'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="next_month_only" v-model="upgradeForm.billing_action" class="mt-1 text-purple-600 focus:ring-purple-500" />
                                        <div class="flex-1">
                                            <span class="text-xs font-bold text-gray-800">Hanya Ubah Paket (Efektif Periode Bulan Depan)</span>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                Tagihan bulan ini tetap tidak diubah. Tarif baru akan berlaku saat penerbitan tagihan bulan depan.
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Opsi Sekaligus Bayar Lunas Sekarang -->
                            <div class="p-3.5 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-3">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" v-model="upgradeForm.pay_now" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300" />
                                    <div>
                                        <span class="text-xs font-bold text-emerald-900">Sekaligus Tandai Lunas / Bayar Sekarang</span>
                                        <p class="text-[11px] text-emerald-700">Otomatis mencatat pembayaran lunas untuk tagihan hasil upgrade/downgrade ini saat disimpan.</p>
                                    </div>
                                </label>

                                <div v-if="upgradeForm.pay_now" class="pt-2 border-t border-emerald-200 flex items-center gap-2">
                                    <span class="text-[11px] font-bold text-emerald-900">Metode:</span>
                                    <select v-model="upgradeForm.payment_method" class="text-xs font-semibold px-2.5 py-1.5 bg-white border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                        <option value="cash">Tunai / Cash</option>
                                        <option value="transfer">Transfer Bank</option>
                                        <option value="qris">Scan QRIS</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Opsi Sinkronisasi RADIUS -->
                            <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" v-model="upgradeForm.sync_radius" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-gray-300" />
                                    <div>
                                        <span class="text-xs font-bold text-gray-800">Disconnect PPPoE & Sync RADIUS Sekarang</span>
                                        <p class="text-[11px] text-gray-500">Putus sesi aktif agar router pelanggan reconnect seketika dengan profile kecepatan paket baru.</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Catatan -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                                <textarea v-model="upgradeForm.notes" rows="2" placeholder="Contoh: Upgrade kecepatan atas permintaan pelanggan via WA" class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50 focus:bg-white"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-2">
                            <button type="button" @click="closeUpgradeModal" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="upgradeForm.processing || !upgradeForm.new_package_id" class="bg-purple-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-purple-600/20 hover:bg-purple-700 transition-all disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="upgradeForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ upgradeForm.processing ? 'Menyimpan...' : 'Terapkan Paket Baru' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Stop Permanen & Eskalasi Cabut Perangkat (CBP) -->
        <Teleport to="body">
            <div v-if="showStopPermanenModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeStopPermanenModal"></div>

                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-lg relative z-10 animate-fade-in-up border border-slate-100 my-8">
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-rose-100 flex items-center justify-between bg-gradient-to-r from-rose-50 via-red-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center text-white shadow-lg shadow-rose-500/25 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-slate-800 tracking-tight">Stop Permanen &amp; Cabut Perangkat</h3>
                                <p class="text-xs font-medium text-rose-600 mt-0.5">Otomatis Terjadwal ke CBP &amp; Disposisi Kepala Teknisi</p>
                            </div>
                        </div>
                        <button @click="closeStopPermanenModal" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-white/80 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitStopPermanen">
                        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                            <!-- Ringkasan Pelanggan -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-sm font-black text-slate-900">{{ selectedStopPermanenInvoice?.customer?.name }}</div>
                                        <div class="text-xs text-slate-500 font-mono mt-0.5">
                                            {{ selectedStopPermanenInvoice?.customer?.customer_code || '-' }} • {{ selectedStopPermanenInvoice?.customer?.phone || '-' }}
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200">
                                        {{ selectedStopPermanenInvoice?.customer?.status || 'Active' }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-600 leading-relaxed">
                                    <span class="text-slate-400 font-semibold">Alamat:</span> {{ selectedStopPermanenInvoice?.customer?.address || '-' }}
                                    <span v-if="selectedStopPermanenInvoice?.customer?.area"> ({{ selectedStopPermanenInvoice?.customer?.area }})</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200 text-xs">
                                    <div>
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Paket Saat Ini:</span>
                                        <span class="font-bold text-slate-700">{{ selectedStopPermanenInvoice?.customer?.package || '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Tagihan Tertunggak:</span>
                                        <span class="font-black text-rose-600">{{ formatCurrency(selectedStopPermanenInvoice?.remaining > 0 ? selectedStopPermanenInvoice?.remaining : selectedStopPermanenInvoice?.amount) }}</span>
                                    </div>
                                </div>
                                <!-- Info ONT jika ada -->
                                <div v-if="selectedStopPermanenInvoice?.customer?.ont" class="p-2.5 rounded-xl bg-amber-50/80 border border-amber-200/80 text-[11px] text-amber-900 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div>
                                        <strong class="font-bold">Perangkat Terpasang:</strong> 
                                        {{ selectedStopPermanenInvoice.customer.ont.brand }} {{ selectedStopPermanenInvoice.customer.ont.model }}
                                        (SN: {{ selectedStopPermanenInvoice.customer.ont.serial_number || '-' }})
                                        <span v-if="selectedStopPermanenInvoice.customer.ont.odp"> • ODP: {{ selectedStopPermanenInvoice.customer.ont.odp }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pilihan Alasan Stop -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alasan Stop Permanen / Pencabutan <span class="text-rose-500">*</span></label>
                                <select v-model="stopPermanenForm.reason_preset" class="w-full text-xs font-semibold px-3 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                                    <option value="Menunggak Pembayaran">Menunggak Pembayaran Berkepanjangan</option>
                                    <option value="Berhenti Berlangganan (Permintaan Pelanggan)">Berhenti Berlangganan (Permintaan Pelanggan)</option>
                                    <option value="Pindah Rumah / Domisili Luar Jangkauan">Pindah Rumah / Domisili Luar Jangkauan</option>
                                    <option value="Komplain Gangguan Berulang">Komplain Gangguan Berulang / Tidak Puas Layanan</option>
                                    <option value="Lainnya">Lainnya (Ketik Manual)</option>
                                </select>
                                <input v-if="stopPermanenForm.reason_preset === 'Lainnya'" type="text" v-model="stopPermanenForm.custom_reason" placeholder="Tulis alasan spesifik..." class="w-full mt-2 px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 bg-white" required />
                            </div>

                            <!-- Tujukan ke Kepala Teknisi -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Disposisikan ke Kepala Teknisi <span class="text-slate-400 font-normal lowercase">(untuk jadwal cabut)</span>
                                </label>
                                <select v-model="stopPermanenForm.lead_technician_id" class="w-full text-xs font-semibold px-3 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                                    <option value="">-- Semua / Default Kepala Teknisi --</option>
                                    <option v-for="tech in (lead_technicians || [])" :key="tech.id" :value="tech.id">
                                        {{ tech.name }} {{ tech.phone ? '(' + tech.phone + ')' : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Catatan untuk Teknisi -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Tambahan untuk Tim Lapangan (Opsional)</label>
                                <textarea v-model="stopPermanenForm.notes" rows="2" placeholder="Contoh: Titik lokasi dekat masjid, koordinasi dengan pemilik rumah sebelum cabut..." class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 bg-white"></textarea>
                            </div>

                            <!-- Alert Sistem Otomatis -->
                            <div class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200 text-xs text-rose-800 space-y-1">
                                <div class="font-bold flex items-center gap-1.5">
                                    <span>⚠️</span> Efek Tindakan:
                                </div>
                                <ul class="list-disc list-inside text-[11px] text-rose-700 space-y-0.5">
                                    <li>Layanan RADIUS otomatis diisolir / dihapus seketika.</li>
                                    <li>Pelanggan dipindahkan ke menu <strong>Stop Permanen</strong>.</li>
                                    <li>Otomatis membuat nomor tiket di antrean <strong>CBP (Cabut Perangkat)</strong>.</li>
                                    <li>Admin dapat langsung meneruskan disposisi ke WhatsApp Kepala Teknisi.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                            <button type="button" @click="closeStopPermanenModal" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="stopPermanenForm.processing" class="bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md shadow-rose-500/25 transition-all disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="stopPermanenForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ stopPermanenForm.processing ? 'Memproses...' : 'Stop Permanen & Buat Tiket CBP' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Sukses Disposisi CBP & Tombol Kirim WhatsApp -->
        <Teleport to="body">
            <div v-if="showDispatchSuccessModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showDispatchSuccessModal = false"></div>

                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-md relative z-10 animate-fade-in-up border border-slate-100 p-6 text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center text-3xl shadow-lg shadow-emerald-500/20">
                        ✅
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800">Tiket CBP Berhasil Dibuat!</h3>
                        <p class="text-xs font-semibold text-slate-500 mt-1">
                            Nomor Tiket: <span class="font-mono text-rose-600 font-bold text-sm bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200">{{ activeDispatchData?.cbp_number }}</span>
                        </p>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 text-left">
                        Pelanggan <strong>{{ activeDispatchData?.customer_name }}</strong> telah di-Stop Permanen dan otomatis masuk antrean Cabut Perangkat. Silakan teruskan disposisi ke Kepala Teknisi untuk mengatur jadwal pencabutan.
                    </p>

                    <div class="space-y-2 pt-2">
                        <button type="button" @click="sendWhatsAppToLead" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl text-sm shadow-md shadow-emerald-500/25 flex items-center justify-center gap-2 transition-all">
                            <span>📲</span> Kirim ke Kepala Teknisi via WhatsApp
                        </button>

                        <div class="flex gap-2">
                            <Link :href="activeDispatchData?.schedule_url || '/cbp/jadwal'" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-3 rounded-xl text-xs transition-colors text-center">
                                📅 Buka Jadwal CBP
                            </Link>
                            <button type="button" @click="showDispatchSuccessModal = false" class="px-5 bg-white hover:bg-slate-50 text-slate-500 font-semibold py-2.5 rounded-xl text-xs border border-slate-200 transition-colors">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    invoices: Object,
    stats: Object,
    packages: Array,
    areas: Array,
    lead_technicians: Array,
    filters: Object,
    payment_banks: Array,
    invoice_issue_date: Number,
});

const columns = [
    { key: 'checkbox', label: '' },
    { key: 'index', label: 'NO' },
    { key: 'customer', label: 'NAMA PELANGGAN' },
    { key: 'area', label: 'AREA' },
    { key: 'address', label: 'ALAMAT' },
    { key: 'package', label: 'NAMA PAKET' },
    { key: 'activation_date', label: 'TANGGAL AKTIVASI' },
    { key: 'last_payment_date', label: 'PEMBAYARAN TERAKHIR' },
    { key: 'amount', label: 'TAGIHAN' },
    { key: 'customer_status', label: 'STATUS PELANGGAN' },
    { key: 'status', label: 'STATUS' },
    { key: 'promise', label: 'JANJI BAYAR' },
];

const filterTabVal = ref(props.filters?.tab || 'semua');
const filterStatusVal = ref(props.filters?.status || '');
const filterAreaVal = ref(props.filters?.area_id || '');
const filterStartDate = ref(props.filters?.start_date || '');
const filterEndDate = ref(props.filters?.end_date || '');

function applyFilters() {
    router.get('/invoices', {
        search: props.filters?.search || undefined,
        tab: filterTabVal.value || undefined,
        status: filterStatusVal.value || undefined,
        area_id: filterAreaVal.value || undefined,
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function filterTab(tab) {
    filterTabVal.value = tab;
    applyFilters();
}

function formatCurrency(value) {
    if (!value && value !== 0) return 'Rp 0';
    return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatStatus(status) {
    if (status === 'paid') return 'Lunas';
    if (status === 'unpaid') return 'Belum Lunas';
    if (status === 'partial') return 'Bayar Sebagian';
    return status;
}

function formatPaymentMethod(method) {
    if (!method) return '';
    if (method === 'cash') return 'Tunai / Cash';
    if (method === 'transfer') return 'Transfer';
    if (method === 'qris') return 'QRIS';
    if (method === 'payment_gateway') return 'Payment Gateway';
    if (method === 'reseller_balance') return 'Loket Reseller';
    return method.replace(/_/g, ' ');
}

function extractResellerName(notes) {
    if (!notes) return null;
    const match = notes.match(/Loket Mitra:\s*(.*?)(?:\s*\(|\s*via|$)/i);
    if (match && match[1]) {
        return match[1].trim();
    }
    return null;
}

function extractBankName(notes) {
    if (!notes) return null;
    const match = notes.match(/\[Transfer ke:\s*(.*?)\s*-/);
    if (match && match[1]) {
        return match[1].trim();
    }
    return null;
}

function statusClass(status) {
    switch (status) {
        case 'paid': return 'bg-emerald-100 text-emerald-700';
        case 'unpaid': return 'bg-rose-100 text-rose-700';
        case 'partial': return 'bg-amber-100 text-amber-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}

function statusDotClass(status) {
    switch (status) {
        case 'paid': return 'bg-emerald-500';
        case 'unpaid': return 'bg-rose-500';
        case 'partial': return 'bg-amber-500';
        default: return 'bg-gray-500';
    }
}

// Action Dropdown Floating Logic
const activeDropdownRow = ref(null);
const dropdownPos = ref({ top: 0, left: 0 });

function toggleDropdown(event, row) {
    if (activeDropdownRow.value && activeDropdownRow.value.id === row.id) {
        activeDropdownRow.value = null;
        return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    const dropdownHeight = 310;
    const dropdownWidth = 240;

    let top = rect.bottom + 6;
    if (top + dropdownHeight > window.innerHeight && rect.top > dropdownHeight) {
        top = Math.max(10, rect.top - dropdownHeight - 6);
    }

    let left = rect.right - dropdownWidth;
    if (left < 10) {
        left = 10;
    }

    dropdownPos.value = { top, left };
    activeDropdownRow.value = row;
}

function closeDropdown() {
    activeDropdownRow.value = null;
}

function handleWindowChange() {
    if (activeDropdownRow.value) {
        activeDropdownRow.value = null;
    }
}

onMounted(() => {
    window.addEventListener('scroll', handleWindowChange, { passive: true });
    window.addEventListener('resize', handleWindowChange);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleWindowChange);
    window.removeEventListener('resize', handleWindowChange);
});

// Payment Modal Logic
const showPaymentModal = ref(false);
const selectedInvoice = ref(null);
const proofFileInput = ref(null);
const proofPreview = ref(null);
const proofFileName = ref('');
const proofFileSize = ref('');
const paymentMode = ref('full'); // 'full' | 'partial'

const paymentForm = useForm({
    method: 'cash',
    amount: 0,
    notes: '',
    selected_bank: null,
    proof_image: null,
    internet_action: 'on', // 'on' | 'off'
});

const remainingAfterPay = computed(() => {
    const totalRemaining = Number(selectedInvoice.value?.remaining || selectedInvoice.value?.amount || 0);
    const paying = Number(paymentForm.amount) || 0;
    return totalRemaining - paying;
});

function setPaymentMode(mode) {
    paymentMode.value = mode;
    if (!selectedInvoice.value) return;

    const totalRemaining = Number(selectedInvoice.value.remaining || selectedInvoice.value.amount || 0);
    if (mode === 'full') {
        paymentForm.amount = totalRemaining;
        paymentForm.internet_action = 'on';
    } else {
        // Mode Bayar Sebagian (Cicil)
        if (paymentForm.amount >= totalRemaining) {
            paymentForm.amount = Math.round(totalRemaining / 2);
        }
        // Jika pelanggan saat ini suspended, defaultkan 'off' (tetap isolir sampai lunas),
        // namun admin tetap dapat memilih 'on' secara bebas
        if (selectedInvoice.value.customer?.status === 'suspended') {
            paymentForm.internet_action = 'off';
        } else {
            paymentForm.internet_action = 'on';
        }
    }
}

function handleProofUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    paymentForm.proof_image = file;
    proofFileName.value = file.name;
    proofFileSize.value = `${(file.size / 1024).toFixed(1)} KB`;

    if (file.type && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (event) => {
            proofPreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        proofPreview.value = null;
    }
}

function removeProofFile() {
    paymentForm.proof_image = null;
    proofPreview.value = null;
    proofFileName.value = '';
    proofFileSize.value = '';
    if (proofFileInput.value) {
        proofFileInput.value.value = '';
    }
}

function openPaymentModal(invoice, mode = 'full') {
    selectedInvoice.value = invoice;
    paymentForm.method = 'cash';
    paymentForm.notes = '';
    paymentForm.selected_bank = null;
    removeProofFile();
    setPaymentMode(mode);
    showPaymentModal.value = true;
}

function closePaymentModal() {
    showPaymentModal.value = false;
    setTimeout(() => {
        selectedInvoice.value = null;
        paymentForm.reset();
        removeProofFile();
    }, 200);
}

function submitPayment() {
    paymentForm.transform((data) => {
        let notes = data.notes;
        if (data.method === 'transfer' && data.selected_bank) {
            const prefix = `[Transfer ke: ${data.selected_bank.bank_name} - ${data.selected_bank.account_number}]`;
            notes = notes ? prefix + ' \n' + notes : prefix;
        }
        return {
            ...data,
            notes: notes
        };
    }).post(`/invoices/${selectedInvoice.value.id}/pay`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closePaymentModal();
        },
    });
}

function rollbackInvoice(row) {
    if (confirm(`Apakah Anda yakin ingin membatalkan pembayaran untuk pelanggan ${row.customer.name}? Status tagihan akan kembali menjadi Belum Lunas.`)) {
        router.post(`/invoices/${row.id}/rollback`, {}, {
            preserveScroll: true,
        });
    }
}

// Promise Modal Logic
const showPromiseModal = ref(false);
const printAfterSave = ref(true);
const promiseForm = useForm({
    promise_date: '',
    internet_action: 'on', // 'on' | 'off'
});

function setPromisePreset(days) {
    const d = new Date();
    d.setDate(d.getDate() + days);
    promiseForm.promise_date = d.toISOString().split('T')[0];
}

function setPromiseEndOfMonth() {
    const now = new Date();
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    promiseForm.promise_date = lastDay.toISOString().split('T')[0];
}

// Stop Sementara Modal Logic
const showStopSementaraModal = ref(false);
const stopSementaraForm = useForm({
    start_date: '',
    end_date: '',
});

function stopSementara(row) {
    selectedInvoice.value = row;
    stopSementaraForm.start_date = '';
    stopSementaraForm.end_date = '';
    showStopSementaraModal.value = true;
}

function closeStopSementaraModal() {
    showStopSementaraModal.value = false;
    selectedInvoice.value = null;
    stopSementaraForm.reset();
}

function submitStopSementara() {
    stopSementaraForm.post(`/invoices/${selectedInvoice.value.id}/stop-sementara`, {
        preserveScroll: true,
        onSuccess: () => closeStopSementaraModal(),
    });
}

// ── Stop Permanen & Eskalasi CBP Modal Logic ───────────────────
const showStopPermanenModal = ref(false);
const selectedStopPermanenInvoice = ref(null);
const stopPermanenForm = useForm({
    reason_preset: 'Menunggak Pembayaran',
    custom_reason: '',
    notes: '',
    lead_technician_id: '',
});

const showDispatchSuccessModal = ref(false);
const activeDispatchData = ref(null);

function openStopPermanenModal(row) {
    selectedStopPermanenInvoice.value = row;
    stopPermanenForm.reason_preset = 'Menunggak Pembayaran';
    stopPermanenForm.custom_reason = '';
    stopPermanenForm.notes = '';
    stopPermanenForm.lead_technician_id = '';
    showStopPermanenModal.value = true;
}

function closeStopPermanenModal() {
    showStopPermanenModal.value = false;
    setTimeout(() => {
        selectedStopPermanenInvoice.value = null;
        stopPermanenForm.reset();
    }, 200);
}

function submitStopPermanen() {
    const finalReason = stopPermanenForm.reason_preset === 'Lainnya'
        ? (stopPermanenForm.custom_reason || 'Stop Permanen')
        : stopPermanenForm.reason_preset;

    stopPermanenForm.transform((data) => ({
        ...data,
        reason: finalReason,
    })).post(`/invoices/${selectedStopPermanenInvoice.value.id}/stop-permanen`, {
        preserveScroll: true,
        onSuccess: (page) => {
            closeStopPermanenModal();
            if (page.props.flash?.cbp_dispatch) {
                activeDispatchData.value = page.props.flash.cbp_dispatch;
                showDispatchSuccessModal.value = true;
            }
        },
    });
}

function cleanPhone(phone) {
    if (!phone) return '';
    let p = String(phone).replace(/\D/g, '');
    if (p.startsWith('0')) p = '62' + p.substring(1);
    return p;
}

function sendWhatsAppToLead() {
    if (!activeDispatchData.value) return;
    const phone = cleanPhone(activeDispatchData.value.phone);
    const text = activeDispatchData.value.whatsapp_text || '';
    const url = phone
        ? `https://wa.me/${phone}?text=${encodeURIComponent(text)}`
        : `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
}

function openPromiseModal(invoice) {
    selectedInvoice.value = invoice;
    promiseForm.promise_date = invoice.promise_date || '';
    // Defaultkan 'on' untuk memberikan akses internet / kompensasi selama masa janji bayar
    promiseForm.internet_action = 'on';
    showPromiseModal.value = true;
}

function closePromiseModal() {
    showPromiseModal.value = false;
    setTimeout(() => {
        if (!showPaymentModal.value) selectedInvoice.value = null;
        promiseForm.reset();
    }, 200);
}

function removePromiseDate() {
    if (!selectedInvoice.value) return;
    if (confirm(`Hapus status janji bayar untuk invoice #${selectedInvoice.value.invoice_number}?`)) {
        router.post(`/invoices/${selectedInvoice.value.id}/promise`, {
            promise_date: null,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                closePromiseModal();
            }
        });
    }
}

function submitPromise() {
    const targetInvoice = selectedInvoice.value;
    const shouldPrint = printAfterSave.value;
    promiseForm.post(`/invoices/${targetInvoice.id}/promise`, {
        preserveScroll: true,
        onSuccess: () => {
            closePromiseModal();
            if (shouldPrint && targetInvoice?.id) {
                window.open(`/invoices/${targetInvoice.id}/print-promise`, '_blank');
            }
        },
    });
}

// Edit Modal Logic
const showEditModal = ref(false);
const editForm = useForm({
    amount: 0,
    issued_date: '',
    due_date: '',
});

function openEditModal(invoice) {
    selectedInvoice.value = invoice;
    editForm.amount = invoice.amount;
    
    // Convert DD/MM/YYYY or YYYY-MM-DD to YYYY-MM-DD for input type="date"
    let idate = invoice.issued_date;
    if (idate && idate.includes('/')) idate = idate.split('/').reverse().join('-');
    
    let ddate = invoice.due_date;
    if (ddate && ddate.includes('/')) ddate = ddate.split('/').reverse().join('-');

    editForm.issued_date = idate || '';
    editForm.due_date = ddate || '';
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    setTimeout(() => {
        selectedInvoice.value = null;
        editForm.reset();
    }, 200);
}

function submitEdit() {
    editForm.put(`/invoices/${selectedInvoice.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        }
    });
}

// Delete Logic
function deleteInvoice(invoice) {
    if (confirm(`Apakah Anda yakin ingin menghapus tagihan ${invoice.invoice_number} secara permanen? Semua data pembayaran terkait tagihan ini juga akan ikut terhapus.`)) {
        router.delete(`/invoices/${invoice.id}`, {
            preserveScroll: true
        });
    }
}

// Upgrade / Downgrade Logic
const showUpgradeModal = ref(false);
const selectedUpgradeInvoice = ref(null);
const upgradeForm = useForm({
    new_package_id: '',
    billing_action: 'prorata_diff',
    start_date: '',
    effective_date: new Date().toISOString().split('T')[0],
    sync_radius: true,
    notes: '',
    pay_now: false,
    payment_method: 'cash',
});

function formatShortDate(dateObj) {
    if (!dateObj) return '';
    const d = new Date(dateObj);
    if (isNaN(d.getTime())) return '';
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${day} ${months[d.getMonth()]}`;
}

const selectedNewPackage = computed(() => {
    if (!props.packages || !upgradeForm.new_package_id) return null;
    return props.packages.find(p => p.id == upgradeForm.new_package_id) || null;
});

const currentPackagePrice = computed(() => {
    return Number(selectedUpgradeInvoice.value?.customer?.package_price || 0);
});

const priceDiff = computed(() => {
    if (!selectedNewPackage.value) return 0;
    return Number(selectedNewPackage.value.price) - currentPackagePrice.value;
});

const isUpgrade = computed(() => priceDiff.value >= 0);

const defaultIssueDay = computed(() => {
    return Number(props.invoice_issue_date || 1);
});

const calculatedStartDate = computed(() => {
    if (upgradeForm.start_date) {
        return new Date(upgradeForm.start_date + 'T00:00:00');
    }
    if (selectedUpgradeInvoice.value?.issued_date) {
        let idate = selectedUpgradeInvoice.value.issued_date;
        if (idate.includes('/')) idate = idate.split('/').reverse().join('-');
        return new Date(idate + 'T00:00:00');
    }
    const now = new Date();
    const yr = selectedUpgradeInvoice.value?.period_year || now.getFullYear();
    const mo = selectedUpgradeInvoice.value?.period_month ? selectedUpgradeInvoice.value.period_month - 1 : now.getMonth();
    return new Date(yr, mo, defaultIssueDay.value);
});

const calculatedEffectiveDate = computed(() => {
    if (!upgradeForm.effective_date) return new Date();
    return new Date(upgradeForm.effective_date + 'T00:00:00');
});

const calculatedCycleEndDate = computed(() => {
    const d = new Date(calculatedStartDate.value);
    d.setMonth(d.getMonth() + 1);
    return d;
});

const calculatedTotalCycleDays = computed(() => {
    const start = calculatedStartDate.value.getTime();
    const end = calculatedCycleEndDate.value.getTime();
    const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
    return Math.max(1, diff);
});

const calculatedPastDays = computed(() => {
    const start = calculatedStartDate.value.getTime();
    const eff = calculatedEffectiveDate.value.getTime();
    if (eff <= start) return 0;
    const diff = Math.round((eff - start) / (1000 * 60 * 60 * 24));
    return Math.min(calculatedTotalCycleDays.value, Math.max(0, diff));
});

const calculatedFutureDays = computed(() => {
    return Math.max(1, calculatedTotalCycleDays.value - calculatedPastDays.value);
});

const calculatedPastEndDate = computed(() => {
    if (calculatedPastDays.value <= 0) return calculatedStartDate.value;
    const d = new Date(calculatedEffectiveDate.value);
    d.setDate(d.getDate() - 1);
    return d;
});

const calculatedFutureEndDate = computed(() => {
    const d = new Date(calculatedCycleEndDate.value);
    d.setDate(d.getDate() - 1);
    return d;
});

const calculatedPastAmount = computed(() => {
    if (!currentPackagePrice.value || calculatedPastDays.value <= 0) return 0;
    return Math.round((currentPackagePrice.value / calculatedTotalCycleDays.value) * calculatedPastDays.value);
});

const calculatedFutureAmount = computed(() => {
    if (!selectedNewPackage.value) return 0;
    const newPrice = Number(selectedNewPackage.value.price || 0);
    return Math.round((newPrice / calculatedTotalCycleDays.value) * calculatedFutureDays.value);
});

const calculatedTotalProrata = computed(() => {
    return calculatedPastAmount.value + calculatedFutureAmount.value;
});

function openUpgradeModal(invoice) {
    selectedUpgradeInvoice.value = invoice;
    upgradeForm.new_package_id = '';
    upgradeForm.billing_action = 'prorata_diff';

    // Tentukan tanggal terbit awal siklus
    let sDate = '';
    if (invoice.issued_date) {
        let idate = invoice.issued_date;
        if (idate.includes('/')) idate = idate.split('/').reverse().join('-');
        sDate = idate;
    } else {
        const now = new Date();
        const yr = invoice.period_year || now.getFullYear();
        const mo = String(invoice.period_month || (now.getMonth() + 1)).padStart(2, '0');
        const day = String(props.invoice_issue_date || 1).padStart(2, '0');
        sDate = `${yr}-${mo}-${day}`;
    }
    upgradeForm.start_date = sDate;
    upgradeForm.effective_date = new Date().toISOString().split('T')[0];
    upgradeForm.sync_radius = true;
    upgradeForm.notes = '';
    upgradeForm.pay_now = false;
    upgradeForm.payment_method = 'cash';
    showUpgradeModal.value = true;
}

function closeUpgradeModal() {
    showUpgradeModal.value = false;
    setTimeout(() => {
        selectedUpgradeInvoice.value = null;
        upgradeForm.reset();
    }, 200);
}

function submitUpgrade() {
    if (!upgradeForm.new_package_id) {
        alert('Silakan pilih paket baru terlebih dahulu.');
        return;
    }
    upgradeForm.post(`/invoices/${selectedUpgradeInvoice.value.id}/change-package`, {
        preserveScroll: true,
        onSuccess: () => {
            closeUpgradeModal();
        }
    });
}
</script>
