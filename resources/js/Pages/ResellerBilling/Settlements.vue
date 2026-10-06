<template>
    <AppLayout title="Pelunasan Reseller" subtitle="Verifikasi penerimaan uang dari Penagih">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ✅ {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ❌ {{ $page.props.flash.error }}
            </div>

            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Pembayaran Menunggu Pelunasan (Setoran Penagih)</h3>
                    <p class="text-sm text-gray-500">Uang yang sudah diterima penagih perlu diverifikasi di sini agar tagihan lunas.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Terima</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reseller / Invoice</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penagih</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal Bayar</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(pay.payment_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ pay.customer?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">INV: {{ pay.invoice?.invoice_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ pay.collector?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500 capitalize">{{ pay.payment_method }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-green-600">Rp {{ formatRupiah(pay.amount) }}</div>
                                    <div class="text-xs text-gray-500 italic">{{ pay.notes || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="approve(pay.id)" class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg shadow-sm transition-colors text-xs font-bold">
                                        ✓ Lunasi / Setujui
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!payments.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-medium">Tidak ada setoran / pembayaran yang menunggu</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payments: Object
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

const approve = (id) => {
    if (confirm('Uang sudah diterima? Memproses ini akan melunasi atau mengurangi tagihan invoice Reseller.')) {
        router.post(route('reseller-settlements.approve', id), {}, { preserveScroll: true });
    }
};
</script>
