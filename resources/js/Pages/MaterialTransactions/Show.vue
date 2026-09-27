<template>
    <AppLayout title="Detail Order & Pengambilan" subtitle="Rincian surat jalan pengeluaran material">
        <div class="space-y-6">
            <!-- Action Bar (Not visible in print) -->
            <div class="flex justify-between items-center print:hidden">
                <Link href="/material-transactions" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Riwayat
                </Link>
                
                <button @click="printDocument" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold shadow-sm transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak Surat Jalan
                </button>
            </div>

            <!-- Surat Jalan Document -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden print:shadow-none print:border-none print:rounded-none printable-area">
                <div class="p-8 sm:p-12 print:p-0">
                    <!-- Header -->
                    <div class="flex justify-between items-start border-b border-gray-200 pb-8 mb-8">
                        <div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">SURAT JALAN</h1>
                            <p class="text-sm text-gray-500 mt-1 font-medium">BUKTI PENGELUARAN MATERIAL</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-bold text-gray-900 text-xl">{{ transaction.transaction_number }}</p>
                            <p class="text-gray-500 mt-1">{{ transaction.date }}</p>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="grid grid-cols-2 gap-12 mb-8">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Diberikan Kepada (Teknisi)</h3>
                            <p class="text-base font-bold text-gray-900">{{ transaction.technician_name }}</p>
                            <p class="text-sm text-gray-600 mt-1"><span class="font-medium text-gray-500">Tujuan:</span> {{ transaction.purpose }}</p>
                            <p v-if="transaction.area" class="text-sm text-gray-600 mt-1"><span class="font-medium text-gray-500">Area/Wilayah:</span> {{ transaction.area }}</p>
                            <p v-if="transaction.notes" class="text-sm text-gray-600 mt-1"><span class="font-medium text-gray-500">Catatan:</span> {{ transaction.notes }}</p>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Dikeluarkan Oleh (Admin/Gudang)</h3>
                            <p class="text-base font-bold text-gray-900">{{ transaction.user?.name || 'Sistem' }}</p>
                            <p class="text-sm text-gray-600 mt-1"><span class="font-medium text-gray-500">Waktu Cetak:</span> {{ new Date().toLocaleString('id-ID') }}</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="mb-12 border border-gray-200 rounded-xl overflow-hidden print:border-gray-900 print:rounded-none">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 print:bg-gray-100 print:border-gray-900">
                                    <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase">No</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase">Nama Barang / Material</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase text-center">Satuan</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase text-center">Kategori</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 print:divide-gray-900">
                                <tr v-for="(item, index) in transaction.items" :key="item.id">
                                    <td class="py-3 px-4 text-sm text-gray-900 font-medium">{{ index + 1 }}</td>
                                    <td class="py-3 px-4">
                                        <p class="text-sm font-bold text-gray-900">{{ item.material?.name || 'Barang Dihapus' }}</p>
                                        <p v-if="item.material?.brand" class="text-xs text-gray-500">{{ item.material.brand }}</p>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="text-sm font-bold text-gray-900">{{ item.unit || item.material?.unit || 'pcs' }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 print:bg-transparent print:border print:border-gray-500">
                                            {{ item.material?.category || '-' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="text-sm font-bold text-gray-900">{{ item.quantity }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Signatures -->
                    <div class="grid grid-cols-2 gap-12 mt-16 pt-8">
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-600 mb-20">Yang Menerima,</p>
                            <p class="text-sm font-bold text-gray-900 border-b border-gray-900 inline-block px-8 pb-1 uppercase">{{ transaction.technician_name }}</p>
                            <p class="text-xs text-gray-500 mt-1">Teknisi / Lapangan</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-600 mb-20">Yang Menyerahkan,</p>
                            <p class="text-sm font-bold text-gray-900 border-b border-gray-900 inline-block px-8 pb-1 uppercase">{{ transaction.user?.name || 'Admin' }}</p>
                            <p class="text-xs text-gray-500 mt-1">Admin / Gudang</p>
                        </div>
                    </div>
                    
                    <div class="mt-12 pt-6 border-t border-gray-200 text-center print:block hidden">
                        <p class="text-[10px] text-gray-400">Dicetak melalui Sistem Manajemen ISP APM2 pada {{ new Date().toLocaleString('id-ID') }}. Dokumen ini sah sebagai bukti pengeluaran barang.</p>
                    </div>
                </div>
            </div>
            
            <!-- Internal HPP Info (Visible to Admin only, Hidden in print) -->
            <div class="bg-orange-50 border border-orange-100 rounded-xl p-5 print:hidden">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-sm font-bold text-orange-900">Informasi Internal (HPP)</h3>
                </div>
                <p class="text-sm text-orange-800 mb-4">Informasi nilai barang di bawah ini hanya untuk admin dan disembunyikan saat surat jalan dicetak.</p>
                <div class="flex justify-between items-center bg-white/60 p-4 rounded-lg border border-orange-200/50">
                    <span class="text-sm font-bold text-orange-900">Total Modal / HPP Pengeluaran Ini:</span>
                    <span class="text-lg font-black text-orange-600">Rp {{ formatNumber(transaction.total_cost) }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    transaction: Object
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const printDocument = () => {
    window.print();
};
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print\:block {
        display: block !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .printable-area, .printable-area * {
        visibility: visible;
    }
    .printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
}
</style>
