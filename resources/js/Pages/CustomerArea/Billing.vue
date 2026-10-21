<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    invoices: Object
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Math.round(Number(amount) || 0));
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch {
        return dateStr;
    }
};

const unpaidInvoices = computed(() => {
    if (!props.invoices?.data) return [];
    return props.invoices.data.filter(i => ['unpaid', 'overdue', 'partial'].includes(i.status));
});

const totalUnpaidAmount = computed(() => {
    return unpaidInvoices.value.reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
});
</script>

<template>
    <Head title="Tagihan Saya — Area Pelanggan" />

    <CustomerLayout>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tagihan & Pembayaran</h1>
            <p class="mt-1 text-xs sm:text-sm text-slate-500">Pantau seluruh riwayat tagihan bulanan dan status pembayaran internet Anda.</p>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <!-- 1. Total Belum Dibayar -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tagihan Belum Dibayar</p>
                    <p class="text-2xl font-black mt-1 font-mono" :class="totalUnpaidAmount > 0 ? 'text-rose-600' : 'text-slate-900'">
                        {{ formatRupiah(totalUnpaidAmount) }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">
                        {{ unpaidInvoices.length > 0 ? unpaidInvoices.length + ' tagihan aktif' : 'Semua tagihan lunas' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0" :class="totalUnpaidAmount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <!-- 2. Paket Aktif -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Paket Langganan</p>
                    <p class="text-xl font-black text-slate-900 mt-1 truncate max-w-[180px]">
                        {{ customer?.package?.name || 'Paket Standar' }}
                    </p>
                    <p class="text-[11px] text-indigo-600 font-semibold mt-1">
                        {{ customer?.package?.speed || '-' }} Mbps Unlimited
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>

            <!-- 3. Metode Pembayaran -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Metode Pembayaran</p>
                    <p class="text-base font-bold text-slate-900 mt-1">QRIS / Online & Reseller</p>
                    <p class="text-[11px] text-slate-400 mt-1">Otomatis terverifikasi 24/7</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <h3 class="font-bold text-slate-900 text-sm">Daftar Riwayat Invoice</h3>
                <span class="text-xs text-slate-500">Menampilkan {{ invoices?.data?.length || 0 }} data invoice</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/70 text-left">
                    <thead class="bg-slate-50/70 text-slate-600">
                        <tr>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">No. Invoice</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Periode</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Tgl Terbit</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Jatuh Tempo</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Nominal</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <tr v-for="invoice in (invoices?.data || [])" :key="invoice.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                {{ invoice.invoice_number }}
                                <span v-if="invoice.is_prorata" class="block text-[10px] text-indigo-600 font-sans font-medium">Prorata</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ invoice.period_month }}/{{ invoice.period_year }}
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                {{ formatDate(invoice.issued_date) }}
                            </td>
                            <td class="px-6 py-4 font-medium" :class="invoice.status === 'paid' ? 'text-slate-500' : 'text-rose-600'">
                                {{ formatDate(invoice.due_date) }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                {{ formatRupiah(invoice.amount) }}
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="invoice.status === 'paid'" class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    LUNAS
                                </span>
                                <span v-else-if="invoice.status === 'unpaid'" class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                    BELUM BAYAR
                                </span>
                                <span v-else-if="invoice.status === 'partial'" class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200">
                                    SEBAGIAN
                                </span>
                                <span v-else class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                                    {{ invoice.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link
                                    v-if="invoice.status !== 'paid'"
                                    :href="route('payment-gateway.pay', invoice.id)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs hover:shadow-rose-600/30"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    Bayar QRIS
                                </Link>
                                <span v-else class="text-xs text-emerald-600 font-bold inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Terbayar
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!invoices?.data || invoices.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                Belum ada riwayat tagihan invoice untuk akun Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="invoices?.links && invoices.links.length > 3" class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div class="flex flex-wrap gap-1">
                    <template v-for="(link, p) in invoices.links" :key="p">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1 text-xs text-slate-400 border border-slate-200 rounded-lg bg-slate-50" v-html="link.label" />
                        <Link v-else :href="link.url" class="mr-1 mb-1 px-3 py-1 text-xs border rounded-lg hover:bg-slate-100 transition-colors" :class="{ 'bg-indigo-600 border-indigo-600 text-white font-bold': link.active, 'border-slate-200 text-slate-600 bg-white': !link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
