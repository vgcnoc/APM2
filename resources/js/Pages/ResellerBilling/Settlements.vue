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
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Validasi Setoran Penagih</h3>
                        <p class="text-sm text-gray-500">Uang yang sudah diterima penagih diverifikasi di sini.</p>
                    </div>
                    
                    <div class="flex bg-gray-200/70 p-1 rounded-xl w-full sm:w-auto overflow-x-auto">
                        <Link 
                            :href="route('reseller-settlements.index', { tab: 'pending' })" 
                            class="px-4 py-1.5 text-sm font-bold rounded-lg transition-all whitespace-nowrap text-center flex-1 sm:flex-none"
                            :class="activeTab === 'pending' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            Menunggu Validasi
                        </Link>
                        <Link 
                            :href="route('reseller-settlements.index', { tab: 'verified' })" 
                            class="px-4 py-1.5 text-sm font-bold rounded-lg transition-all whitespace-nowrap text-center flex-1 sm:flex-none"
                            :class="activeTab === 'verified' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            Sudah Tervalidasi
                        </Link>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Terima</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reseller / Invoice</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penagih</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rincian Pembayaran</th>
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
                                    <div class="flex flex-col gap-1">
                                        <div class="text-[11px] text-gray-500 font-medium">Tagihan: Rp {{ formatRupiah(pay.invoice?.amount || 0) }}</div>
                                        <div class="text-sm font-black text-green-600 flex items-center gap-1">
                                            <span class="bg-green-100 text-green-700 px-1.5 py-0.5 rounded text-[10px] uppercase">Setor</span>
                                            Rp {{ formatRupiah(pay.amount) }}
                                        </div>
                                        <div class="text-[11px] font-bold text-red-500">Sisa Utang: Rp {{ formatRupiah(pay.invoice?.remaining || 0) }}</div>
                                        <div class="text-[10px] text-gray-400 italic mt-0.5 line-clamp-1 max-w-[200px]" :title="pay.notes || '-'">{{ pay.notes || '-' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button v-if="pay.status === 'pending'" @click="approve(pay.id)" class="inline-flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white px-3.5 py-2 rounded-xl shadow-sm transition-all hover:shadow text-xs font-bold ring-1 ring-green-600/50">
                                        ✓ Validasi
                                    </button>
                                    <span v-else class="inline-flex items-center gap-1.5 text-green-700 font-bold text-xs bg-green-50 px-3 py-1.5 rounded-lg border border-green-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Tervalidasi
                                    </span>
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
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payments: Object,
    activeTab: String
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
