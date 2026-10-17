<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
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
</script>

<template>
    <Head title="Tagihan Saya" />

    <CustomerLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Tagihan Saya</h1>
            <p class="mt-1 text-gray-500">Riwayat tagihan dan pembayaran layanan internet Anda.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Invoice</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jatuh Tempo</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ invoice.invoice_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ invoice.period_month }}/{{ invoice.period_year }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(invoice.due_date).toLocaleDateString('id-ID') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ formatRupiah(invoice.amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="invoice.status === 'paid'" class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                <span v-else-if="invoice.status === 'unpaid'" class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">Belum Dibayar</span>
                                <span v-else class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">Jatuh Tempo</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link v-if="invoice.status !== 'paid'" :href="route('payment-gateway.pay', invoice.id)" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all hover:shadow-indigo-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    Bayar Otomatis
                                </Link>
                                <span v-else class="text-xs text-emerald-600 font-bold flex items-center justify-end gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Lunas
                                </span>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                Tidak ada riwayat tagihan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="invoices.links && invoices.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <div class="flex flex-wrap gap-1">
                    <template v-for="(link, p) in invoices.links" :key="p">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded-lg bg-gray-50" v-html="link.label" />
                        <Link v-else :href="link.url" class="mr-1 mb-1 px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 focus:border-indigo-500 focus:text-indigo-500 transition-colors" :class="{ 'bg-indigo-50 border-indigo-200 text-indigo-600 font-medium': link.active, 'border-gray-200 text-gray-600': !link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
