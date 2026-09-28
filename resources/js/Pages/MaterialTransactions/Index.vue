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
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Total Tagihan (Jual)</th>
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
                                    <div class="space-y-2">
                                        <div v-for="detail in item.items" :key="detail.id" class="text-xs">
                                            <div>
                                                <span class="font-medium text-gray-800">{{ detail.material ? detail.material.name : 'Unknown' }}</span>
                                                <span class="text-gray-500 ml-1">({{ detail.quantity }} {{ detail.unit || (detail.material ? detail.material.unit : 'pcs') }})</span>
                                                <span class="text-emerald-600 font-medium ml-1">@ Rp {{ formatNumber(detail.price_per_unit) }}</span>
                                            </div>
                                            <div v-if="detail.material" class="flex items-center gap-2 mt-0.5 text-[10px]">
                                                <span class="text-gray-400">Stok Awal: <span class="font-semibold text-gray-600">{{ formatNumber(detail.material.initial_stock) }}</span></span>
                                                <span class="text-gray-300">|</span>
                                                <span class="text-gray-400">Sisa Stok: <span class="font-semibold text-blue-600">{{ formatNumber(detail.material.stock) }}</span></span>
                                            </div>
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
</script>
