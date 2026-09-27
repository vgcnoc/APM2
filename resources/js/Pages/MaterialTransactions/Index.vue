<template>
    <AppLayout title="Riwayat Order & Pengambilan" subtitle="Daftar surat jalan dan pengeluaran material">
        <div class="space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="w-full sm:w-96 relative">
                    <input 
                        type="text" 
                        placeholder="Cari nomor transaksi atau teknisi..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <Link href="/material-transactions/create" class="btn-primary shrink-0 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Order Pengambilan
                </Link>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Transaksi / Tgl</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan / Teknisi</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Rincian Barang</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Total HPP (Modal)</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in transactions.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6 align-top">
                                    <p class="text-sm font-bold text-gray-900">{{ item.transaction_number }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ item.date }}</p>
                                </td>
                                <td class="py-4 px-6 align-top">
                                    <p class="text-sm font-semibold text-gray-900">{{ item.technician_name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ item.purpose }} 
                                        <span v-if="item.area" class="text-blue-500 font-medium">({{ item.area }})</span>
                                    </p>
                                </td>
                                <td class="py-4 px-6 align-top">
                                    <div class="space-y-1">
                                        <div v-for="detail in item.items" :key="detail.id" class="text-xs">
                                            <span class="font-medium text-gray-800">{{ detail.material ? detail.material.name : 'Unknown' }}</span>
                                            <span class="text-gray-500"> ({{ detail.quantity }} {{ detail.unit || (detail.material ? detail.material.unit : 'pcs') }})</span>
                                        </div>
                                        <div v-if="!item.items || item.items.length === 0" class="text-xs text-gray-400 italic">
                                            Tidak ada barang
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right align-top">
                                    <span class="text-sm font-bold text-gray-900">Rp {{ formatNumber(item.total_cost) }}</span>
                                </td>
                                <td class="py-4 px-6 text-right align-top">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/material-transactions/${item.id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Detail
                                        </Link>
                                        <button @click="deleteTransaction(item.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="4" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada riwayat pengambilan barang.</p>
                                    </div>
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
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    transactions: Object,
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const deleteTransaction = (id) => {
    if (!id) {
        alert('Gagal menghapus: ID Transaksi tidak valid. Silakan refresh halaman.');
        return;
    }
    if (confirm('Yakin ingin menghapus riwayat transaksi ini? Stok barang akan dikembalikan ke gudang secara otomatis.')) {
        router.post(`/material-transactions/${id}/delete`);
    }
};
</script>
