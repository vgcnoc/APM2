<template>
    <Head title="Tagihan & Kasbon Reseller" />

    <ClientAreaLayout>
        <div class="space-y-6 pb-10">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200/60">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Reseller Area
                        </span>
                        <span class="text-xs text-gray-300">•</span>
                        <span class="text-xs font-medium text-gray-500">
                            {{ reseller.name || 'Mitra Reseller' }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Tagihan Kasbon & Saldo Reseller
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Pantau rincian tagihan kasbon saldo modal voucher dan transaksi saldo Anda serta bayar online otomatis.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/client-area/dashboard" class="px-4 py-2 bg-white border border-gray-200/80 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Dashboard
                    </Link>
                </div>
            </div>

            <!-- 3 Ringkasan KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Total Kasbon Belum Lunas -->
                <div :class="total_unpaid_kasbon > 0 ? 'bg-gradient-to-br from-rose-50/60 via-amber-50/30 to-white border-rose-200/80' : 'bg-white border-gray-200/80'" class="rounded-2xl border p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider" :class="total_unpaid_kasbon > 0 ? 'text-rose-700' : 'text-gray-500'">Hutang Kasbon Saldo</span>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center border" :class="total_unpaid_kasbon > 0 ? 'bg-rose-100 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-600 border-emerald-100'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-sm font-bold" :class="total_unpaid_kasbon > 0 ? 'text-rose-400' : 'text-gray-400'">Rp</span>
                        <span class="text-2xl font-extrabold tracking-tight" :class="total_unpaid_kasbon > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ Number(total_unpaid_kasbon || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <p class="text-xs mt-2 flex items-center gap-1.5" :class="total_unpaid_kasbon > 0 ? 'text-rose-600 font-semibold' : 'text-gray-500'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="total_unpaid_kasbon > 0 ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'"></span>
                        {{ total_unpaid_kasbon > 0 ? 'Wajib disetor / dilunasi' : 'Tidak ada tunggakan kasbon' }}
                    </p>
                </div>

                <!-- Sisa Saldo Modal Aktif -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Sisa Saldo Dompet</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-sm font-bold text-gray-400">Rp</span>
                        <span class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        Saldo modal cetak voucher aktif
                    </p>
                </div>

                <!-- Total Invoice Terdata -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Transaksi Faktur</span>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ invoices.total || invoices.data.length }}</span>
                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md">Invoice</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        Riwayat invoice tercatat di sistem
                    </p>
                </div>
            </div>

            <!-- Filter Tabs Bar -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-3 sm:p-4 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <!-- Tipe Tagihan Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <button 
                        @click="filterType('')" 
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                        :class="!filters.type ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                    >
                        Semua Faktur Saldo
                    </button>
                    <button 
                        @click="filterType('kasbon')" 
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer flex items-center gap-1.5"
                        :class="filters.type === 'kasbon' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                    >
                        <span>Kasbon Saldo</span>
                        <span v-if="total_unpaid_kasbon > 0" class="w-2 h-2 rounded-full bg-rose-500"></span>
                    </button>
                    <button 
                        @click="filterType('topup')" 
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                        :class="filters.type === 'topup' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                    >
                        Top Up Mandiri
                    </button>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 hidden sm:inline">Status:</span>
                    <button 
                        @click="filterStatus('')" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                        :class="!filters.status ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    >
                        Semua
                    </button>
                    <button 
                        @click="filterStatus('unpaid')" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-1"
                        :class="filters.status === 'unpaid' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60'"
                    >
                        Belum Lunas
                    </button>
                    <button 
                        @click="filterStatus('paid')" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                        :class="filters.status === 'paid' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60'"
                    >
                        Lunas
                    </button>
                </div>
            </div>

            <!-- Tabel Riwayat Tagihan & Kasbon -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden flex flex-col">
                <div class="overflow-x-auto flex-1">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50/75">
                            <tr>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">No. Invoice & Tanggal</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Jenis Faktur</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Jatuh Tempo</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Total Tagihan</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Sisa Tagihan</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white text-xs">
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- No. Invoice -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900 font-mono text-sm">{{ inv.invoice_number }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        Terbit: {{ inv.issued_date ? new Date(inv.issued_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                                    </div>
                                    <div v-if="inv.notes" class="text-[11px] text-gray-500 italic mt-0.5 max-w-xs truncate">{{ inv.notes }}</div>
                                </td>

                                <!-- Jenis Tagihan -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span v-if="(inv.notes || '').toLowerCase().includes('top up') || (inv.notes || '').toLowerCase().includes('isi saldo')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Top Up Mandiri
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200/70">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Kasbon Saldo Modal
                                    </span>
                                </td>

                                <!-- Jatuh Tempo -->
                                <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-600">
                                    <span class="inline-block px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-200/70 font-mono text-[11px]">
                                        {{ inv.due_date ? new Date(inv.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                                    </span>
                                </td>

                                <!-- Total Tagihan -->
                                <td class="px-4 py-4 whitespace-nowrap text-right font-bold text-gray-900 font-mono text-sm">
                                    Rp {{ Number(inv.amount || 0).toLocaleString('id-ID') }}
                                </td>

                                <!-- Sisa Tagihan -->
                                <td class="px-4 py-4 whitespace-nowrap text-right font-mono text-sm">
                                    <span :class="getRemaining(inv) > 0 ? 'text-rose-600 font-black' : 'text-emerald-600 font-bold'">
                                        Rp {{ Number(getRemaining(inv)).toLocaleString('id-ID') }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span v-if="inv.status === 'paid'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Lunas
                                    </span>
                                    <span v-else-if="inv.status === 'partial'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200/70">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Sebagian
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/70">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        Belum Lunas
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <Link 
                                        v-if="inv.status !== 'paid'" 
                                        :href="route('payment-gateway.pay', inv.id)" 
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl font-bold text-xs shadow-xs transition hover:shadow-indigo-200 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        <span>Bayar Online</span>
                                    </Link>
                                    <span v-else class="text-xs font-bold text-emerald-600 inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Lunas
                                    </span>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="text-sm font-bold text-gray-700">Tidak ada riwayat tagihan atau kasbon</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Semua transaksi faktur Anda akan ditampilkan di halaman ini.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links && invoices.links.length > 3" class="px-6 py-4 bg-white border-t border-gray-100">
                    <div class="flex flex-wrap gap-1.5 justify-center sm:justify-start">
                        <template v-for="(link, p) in invoices.links" :key="p">
                            <div v-if="link.url === null" class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-100 rounded-lg bg-gray-50" v-html="link.label" />
                            <Link v-else :href="link.url" class="px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-indigo-50 focus:border-indigo-500 focus:text-indigo-600 transition-colors shadow-2xs" :class="{ 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700': link.active, 'border-gray-200 text-gray-600 bg-white': !link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </ClientAreaLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';

const props = defineProps({
    reseller: Object,
    invoices: Object,
    total_unpaid_kasbon: Number,
    filters: Object,
});

const getRemaining = (inv) => {
    if (inv.remaining_calc !== undefined) {
        return inv.remaining_calc;
    }
    const verifiedPaid = (inv.payments || [])
        .filter(p => p.status === 'verified')
        .reduce((sum, p) => sum + Number(p.amount || 0), 0);
    return Math.max(0, Number(inv.amount || 0) - verifiedPaid);
};

const filterType = (type) => {
    router.get(route('client-area.billing'), {
        ...props.filters,
        type: type || undefined,
        page: undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filterStatus = (status) => {
    router.get(route('client-area.billing'), {
        ...props.filters,
        status: status || undefined,
        page: undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
