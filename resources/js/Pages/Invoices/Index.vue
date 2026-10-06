<template>
    <AppLayout title="Invoice Pelanggan" subtitle="Kelola tagihan pelanggan dan status pembayarannya">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
            <button @click="filterTab('semua')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'semua' || !filterTabVal ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Daftar Tagihan <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.semua }}</span>
            </button>
            <button @click="filterTab('jatuh_tempo')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'jatuh_tempo' ? 'text-red-600 border-b-2 border-red-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Tagihan Jatuh Tempo <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.jatuh_tempo }}</span>
            </button>
            <button @click="filterTab('piutang')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'piutang' ? 'text-amber-600 border-b-2 border-amber-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Piutang (Bayar Sebagian) <span class="bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.piutang }}</span>
            </button>
            <button @click="filterTab('lunas')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'lunas' ? 'text-emerald-600 border-b-2 border-emerald-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Lunas <span class="bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.lunas }}</span>
            </button>
            <button @click="filterTab('prorata')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'prorata' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Pelanggan Prorata <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.prorata }}</span>
            </button>
            <button @click="filterTab('upgrade')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'upgrade' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Riwayat Upgrade <span class="bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.upgrade }}</span>
            </button>
            <!-- Janji Bayar Tab -->
            <button @click="filterTab('janji_bayar')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'janji_bayar' ? 'text-yellow-600 border-b-2 border-yellow-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Janji Bayar <span class="bg-yellow-50 text-yellow-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.janji_bayar }}</span>
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
                    <div class="flex items-center gap-2 whitespace-nowrap text-sm text-gray-600 font-medium ml-2">
                        <span>Tanggal</span>
                        <input type="date" v-model="filterStartDate" class="form-input text-sm rounded-lg border-gray-300 w-[130px]" />
                        <span>s/d</span>
                        <input type="date" v-model="filterEndDate" class="form-input text-sm rounded-lg border-gray-300 w-[130px]" />
                    </div>

                    <!-- Status -->
                    <select v-model="filterStatusVal" class="form-select text-sm rounded-lg border-gray-300 text-gray-700 min-w-[140px]">
                        <option value="">Semua Status</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partial</option>
                    </select>

                    <!-- Area -->
                    <select v-model="filterAreaVal" class="form-select text-sm rounded-lg border-gray-300 text-gray-700 min-w-[140px]">
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
                <button class="btn-secondary bg-white border border-gray-200 text-emerald-600 hover:bg-emerald-50 text-sm py-2 px-4 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </button>
                <button class="btn-secondary bg-white border border-gray-200 text-red-600 hover:bg-red-50 text-sm py-2 px-4 flex items-center gap-2">
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
                            <span v-if="row.customer.unpaid_count > 1" class="inline-flex items-center gap-1 text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-md w-max border border-red-100" :title="'Total Tunggakan: ' + formatCurrency(row.customer.total_unpaid)">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Nunggak {{ row.customer.unpaid_count }} Bulan
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
                <td><span class="text-[11px] font-bold text-gray-700 uppercase">{{ row.customer?.package || '-' }}</span></td>
                <td><span class="text-[11px] text-gray-600">{{ row.customer?.register_date || '-' }}</span></td>
                <td>
                    <span v-if="row.last_payment_date" class="flex flex-col">
                        <span class="text-[11px] text-gray-600">{{ row.last_payment_date }}</span>
                        <span class="text-[9px] font-bold text-indigo-500 uppercase mt-0.5">{{ formatPaymentMethod(row.last_payment_method) }}</span>
                    </span>
                    <span v-else class="text-[11px] text-gray-600">-</span>
                </td>
                <td><span class="text-xs font-bold text-gray-900">{{ formatCurrency(row.amount) }}</span></td>
                <td>
                    <span v-if="row.customer" :class="['px-2 py-1 text-[10px] font-medium rounded-md text-white whitespace-nowrap', row.customer.status === 'aktif' ? 'bg-blue-500' : 'bg-gray-500']">
                        {{ row.customer.status }}
                    </span>
                </td>
                <td>
                    <span :class="['px-2.5 py-1 text-[10px] font-bold rounded-md whitespace-nowrap flex items-center gap-1 w-max', statusClass(row.status)]">
                        <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(row.status)"></span>
                        {{ formatStatus(row.status) }}
                    </span>
                </td>
                <td>
                    <span v-if="row.promise_date" class="px-2 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-md text-[10px] font-bold whitespace-nowrap flex items-center gap-1 w-max">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ row.promise_date }}
                    </span>
                    <span v-else class="text-gray-400 text-xs">-</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <button v-if="row.status === 'unpaid'" @click="openPaymentModal(row)" class="px-3 py-1.5 border border-amber-300 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-md text-[10px] font-bold whitespace-nowrap flex items-center gap-1 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Bayar Lunas
                    </button>
                    <button v-if="row.status === 'unpaid'" @click="openPromiseModal(row)" class="p-1.5 text-gray-400 hover:text-yellow-600 transition-colors" title="Set Janji Bayar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    <button v-else-if="row.status === 'paid' || row.status === 'partial'" @click="rollbackInvoice(row)" class="px-3 py-1.5 border border-red-300 text-red-600 bg-red-50 hover:bg-red-100 rounded-md text-[10px] font-bold whitespace-nowrap flex items-center gap-1 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Rollback
                    </button>
                    <button class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Payment Modal -->
        <Teleport to="body">
            <div v-if="showPaymentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closePaymentModal"></div>

                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-md relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Pembayaran Tagihan</h3>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ selectedInvoice?.invoice_number }} &bull; {{ selectedInvoice?.customer?.name }}</p>
                        </div>
                        <button @click="closePaymentModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPayment">
                        <div class="p-6 space-y-5">
                            
                            <!-- Info Tunggakan Smart Allocation -->
                            <div v-if="selectedInvoice?.customer?.unpaid_count > 1" class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex gap-3">
                                <div class="shrink-0 text-rose-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-rose-800">Pelanggan memiliki {{ selectedInvoice?.customer?.unpaid_count }} tagihan menunggak!</h4>
                                    <p class="text-[11px] text-rose-600 mt-1">Total seluruh tunggakan: <strong>{{ formatCurrency(selectedInvoice?.customer?.total_unpaid) }}</strong>.</p>
                                    <p class="text-[10px] text-rose-600/80 mt-1 leading-snug">Sistem Alokasi Cerdas: Jika nominal pembayaran lebih besar dari 1 tagihan, sistem otomatis melunasi tagihan yang paling lama terlebih dahulu.</p>
                                </div>
                            </div>
                            
                            <!-- Nominal Input -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Nominal Pembayaran</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold sm:text-sm">Rp</span>
                                    </div>
                                    <input type="number" v-model="paymentForm.amount" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-lg font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="0">
                                </div>
                                <div class="flex justify-between mt-2">
                                    <span class="text-[10px] text-gray-500">Tagihan saat ini: {{ formatCurrency(selectedInvoice?.remaining || 0) }}</span>
                                    <button v-if="selectedInvoice?.customer?.unpaid_count > 1" type="button" @click="paymentForm.amount = selectedInvoice?.customer?.total_unpaid" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800">Bayar Semua Tunggakan</button>
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

                            <!-- Notes -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Catatan Tambahan (Opsional)</label>
                                <textarea v-model="paymentForm.notes" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none" placeholder="Cth: Titip di satpam / Pembayaran bulan ini dan depan..."></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
                            <button type="button" @click="closePaymentModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="paymentForm.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-sm shadow-indigo-200 flex items-center gap-2">
                                <svg v-if="paymentForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ paymentForm.processing ? 'Memproses...' : 'Konfirmasi Pembayaran' }}
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

                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-sm relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-yellow-50/50">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Atur Janji Bayar</h3>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ selectedInvoice?.customer?.name }}</p>
                        </div>
                        <button @click="closePromiseModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPromise">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Janji Bayar</label>
                                <input type="date" v-model="promiseForm.promise_date" required :min="new Date().toISOString().split('T')[0]" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                            </div>
                            <p class="text-[10px] text-gray-500 leading-snug">Tagihan ini akan ditandai dengan tanggal janji bayar. Jika melewati tanggal tersebut, tagihan akan segera ditindaklanjuti.</p>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
                            <button type="button" @click="closePromiseModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-200 bg-gray-100 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="promiseForm.processing" class="px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl text-sm font-bold transition-all shadow-sm shadow-yellow-200 flex items-center gap-2">
                                {{ promiseForm.processing ? 'Menyimpan...' : 'Simpan Janji Bayar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    invoices: Object,
    stats: Object,
    areas: Array,
    filters: Object,
    payment_banks: Array,
});

const columns = [
    { key: 'checkbox', label: '' },
    { key: 'index', label: 'NO' },
    { key: 'customer', label: 'NAMA PELANGGAN' },
    { key: 'area', label: 'AREA' },
    { key: 'address', label: 'ALAMAT' },
    { key: 'package', label: 'NAMA PAKET' },
    { key: 'register_date', label: 'TANGGAL REGISTER' },
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
    if (!value) return 'Rp 0';
    return 'Rp ' + Number(value).toLocaleString('id-ID');
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
    if (method === 'transfer') return 'Transfer Bank';
    if (method === 'qris') return 'QRIS';
    if (method === 'payment_gateway') return 'Payment Gateway';
    return method;
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

// Payment Modal Logic
const showPaymentModal = ref(false);
const selectedInvoice = ref(null);

const paymentForm = useForm({
    method: 'cash',
    amount: 0,
    notes: '',
    selected_bank: null,
});

function openPaymentModal(invoice) {
    selectedInvoice.value = invoice;
    paymentForm.amount = invoice.remaining;
    paymentForm.method = 'cash';
    paymentForm.notes = '';
    paymentForm.selected_bank = null;
    showPaymentModal.value = true;
}

function closePaymentModal() {
    showPaymentModal.value = false;
    setTimeout(() => {
        selectedInvoice.value = null;
        paymentForm.reset();
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
const promiseForm = useForm({
    promise_date: '',
});

function openPromiseModal(invoice) {
    selectedInvoice.value = invoice;
    promiseForm.promise_date = invoice.promise_date || '';
    showPromiseModal.value = true;
}

function closePromiseModal() {
    showPromiseModal.value = false;
    setTimeout(() => {
        if (!showPaymentModal.value) selectedInvoice.value = null;
        promiseForm.reset();
    }, 200);
}

function submitPromise() {
    promiseForm.post(`/invoices/${selectedInvoice.value.id}/promise`, {
        preserveScroll: true,
        onSuccess: () => {
            closePromiseModal();
        },
    });
}
</script>
