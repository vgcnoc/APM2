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
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Pelanggan Prorata <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.prorata }}</span>
            </button>
            <button @click="filterTab('upgrade')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'upgrade' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                Upgrade / Downgrade <span class="bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.upgrade || 0 }}</span>
            </button>
            <button @click="filterTab('janji_bayar')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'janji_bayar' ? 'text-yellow-600 border-b-2 border-yellow-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Janji Bayar <span class="bg-yellow-50 text-yellow-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.janji_bayar }}</span>
            </button>
            <button @click="filterTab('stop_sementara')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'stop_sementara' ? 'text-orange-600 border-b-2 border-orange-600' : 'text-gray-500 hover:text-gray-700']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Stop Sementara <span class="bg-orange-50 text-orange-600 px-2 py-0.5 rounded-full text-[10px]">{{ stats.stop_sementara || 0 }}</span>
            </button>
            <button @click="filterTab('stop_permanen')" :class="['px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap', filterTabVal === 'stop_permanen' ? 'text-red-800 border-b-2 border-red-800' : 'text-gray-500 hover:text-gray-700']">
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
                <td><span class="text-[11px] text-gray-600">{{ row.customer?.register_date || '-' }}</span></td>
                <td>
                    <span v-if="row.last_payment_date" class="flex flex-col">
                        <span class="text-[11px] text-gray-600">{{ row.last_payment_date }}</span>
                        <span class="text-[9px] font-bold text-indigo-500 uppercase mt-0.5">
                            {{ formatPaymentMethod(row.last_payment_method) }}
                            <template v-if="row.last_payment_method === 'transfer' && extractBankName(row.last_payment_notes)">
                                - {{ extractBankName(row.last_payment_notes) }}
                            </template>
                        </span>
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
                    <a :href="'/invoices/' + row.id + '/print'" target="_blank" class="p-1.5 text-gray-400 hover:text-indigo-600 transition-colors" title="Cetak Invoice">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </a>
                    <button @click="openEditModal(row)" class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors" title="Edit Tagihan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button v-if="row.customer" @click="openUpgradeModal(row)" class="p-1.5 text-gray-400 hover:text-purple-600 transition-colors" title="Upgrade / Downgrade Paket">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </button>
                    <button v-if="row.customer && row.customer.status !== 'suspended'" @click="stopSementara(row)" class="p-1.5 text-gray-400 hover:text-orange-600 transition-colors" title="Stop Sementara">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <button v-if="row.customer && row.customer.status !== 'terminated'" @click="stopPermanen(row)" class="p-1.5 text-gray-400 hover:text-red-800 transition-colors" title="Stop Permanen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </button>
                    <button @click="deleteInvoice(row)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Hapus Tagihan">
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
                                    <button v-if="selectedInvoice?.customer?.unpaid_count > 1" type="button" @click="paymentForm.amount = selectedInvoice?.customer?.total_unpaid" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800">Bayar Semua Tagihan</button>
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

                            <!-- Tanggal Efektif -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Efektif Perubahan</label>
                                <input type="date" v-model="upgradeForm.effective_date" required class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50 focus:bg-white" />
                                <p class="text-[11px] text-gray-400 mt-1">Sisa hari periode bulan berjalan: <span class="font-bold text-gray-700">{{ calculatedRemainingDays }} hari</span></p>
                            </div>

                            <!-- Opsi Billing Action -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-2">Penyesuaian Biaya & Tagihan (Billing)</label>
                                <div class="space-y-2.5">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="upgradeForm.billing_action === 'prorata_diff' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-500' : 'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="prorata_diff" v-model="upgradeForm.billing_action" class="mt-1 text-purple-600 focus:ring-purple-500" />
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-800">Hitung Prorata Selisih Sisa Hari</span>
                                                <span v-if="selectedNewPackage" class="text-xs font-black px-2 py-0.5 rounded bg-white border" :class="calculatedProrata >= 0 ? 'text-emerald-600 border-emerald-200' : 'text-amber-600 border-amber-200'">
                                                    {{ calculatedProrata >= 0 ? '+' : '' }}{{ formatCurrency(calculatedProrata) }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                Nominal tagihan disesuaikan berdasarkan sisa {{ calculatedRemainingDays }} hari pada bulan ini. (Jika invoice sudah lunas, akan dibuat invoice selisih baru).
                                            </p>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="upgradeForm.billing_action === 'replace_invoice' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-500' : 'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="replace_invoice" v-model="upgradeForm.billing_action" class="mt-1 text-purple-600 focus:ring-purple-500" />
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-800">Ubah Nominal Tagihan ke Tarif Paket Baru</span>
                                                <span v-if="selectedNewPackage" class="text-xs font-black px-2 py-0.5 rounded bg-white border border-purple-200 text-purple-700">
                                                    {{ formatCurrency(selectedNewPackage?.price) }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                Mengganti total tagihan invoice ini menjadi tarif penuh dari paket baru (hanya jika invoice belum lunas).
                                            </p>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="upgradeForm.billing_action === 'next_month_only' ? 'border-purple-500 bg-purple-50/50 ring-1 ring-purple-500' : 'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="next_month_only" v-model="upgradeForm.billing_action" class="mt-1 text-purple-600 focus:ring-purple-500" />
                                        <div class="flex-1">
                                            <span class="text-xs font-bold text-gray-800">Hanya Ubah Paket (Efektif Bulan Berikutnya)</span>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                Invoice saat ini tidak diubah nominalnya. Paket pelanggan langsung diupdate dan tarif baru berlaku saat generate invoice bulan depan.
                                            </p>
                                        </div>
                                    </label>
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
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    invoices: Object,
    stats: Object,
    packages: Array,
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
    if (method === 'transfer') return 'Transfer';
    if (method === 'qris') return 'QRIS';
    if (method === 'payment_gateway') return 'Payment Gateway';
    return method;
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

function stopPermanen(row) {
    if (confirm(`Apakah Anda yakin ingin Stop Permanen pelanggan ${row.customer.name}? Layanan akan dinonaktifkan secara permanen.`)) {
        router.post(`/invoices/${row.id}/stop-permanen`, {}, { preserveScroll: true });
    }
}

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
    effective_date: new Date().toISOString().split('T')[0],
    sync_radius: true,
    notes: '',
});

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

const calculatedRemainingDays = computed(() => {
    if (!upgradeForm.effective_date) return 0;
    const d = new Date(upgradeForm.effective_date);
    const year = d.getFullYear();
    const month = d.getMonth();
    const lastDay = new Date(year, month + 1, 0).getDate();
    return Math.max(1, lastDay - d.getDate() + 1);
});

const calculatedDaysInMonth = computed(() => {
    if (!upgradeForm.effective_date) return 30;
    const d = new Date(upgradeForm.effective_date);
    return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
});

const calculatedProrata = computed(() => {
    if (!selectedNewPackage.value) return 0;
    const diff = priceDiff.value;
    const days = calculatedRemainingDays.value;
    const totalDays = calculatedDaysInMonth.value || 30;
    return Math.round((diff / totalDays) * days);
});

function openUpgradeModal(invoice) {
    selectedUpgradeInvoice.value = invoice;
    upgradeForm.new_package_id = '';
    upgradeForm.billing_action = 'prorata_diff';
    upgradeForm.effective_date = new Date().toISOString().split('T')[0];
    upgradeForm.sync_radius = true;
    upgradeForm.notes = '';
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
