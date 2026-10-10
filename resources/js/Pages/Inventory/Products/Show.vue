<template>
    <AppLayout title="Detail Produk">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section with Glassmorphism -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-emerald-100/40 to-teal-100/40 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-gradient-to-tr from-blue-100/40 to-cyan-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <Link href="/produk" class="inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-700 mb-4 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                Kembali ke Katalog
                            </Link>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                                {{ product.name }}
                                <span v-if="product.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Aktif</span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800 border border-gray-200">Nonaktif</span>
                            </h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Detail informasi produk, stok, dan konfigurasi satuan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Left Column: Details -->
                    <div class="md:col-span-2 space-y-8">
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <div class="p-1.5 bg-emerald-100 rounded-lg text-emerald-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                Informasi Dasar
                            </h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kategori</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ product.category || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Supplier / Merk</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ product.supplier || '-' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Keterangan Tambahan</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ product.description || '-' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <div class="p-1.5 bg-blue-100 rounded-lg text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                Harga & Keuangan
                            </h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Harga Modal (Per Unit)</dt>
                                    <dd class="mt-1 text-base font-bold text-gray-900">Rp {{ formatCurrency(product.price_per_unit) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Harga Jual (Per Unit)</dt>
                                    <dd class="mt-1 text-base font-bold text-emerald-600">Rp {{ formatCurrency(product.selling_price) }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div v-if="product.requires_sn || product.requires_mac" class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <div class="p-1.5 bg-indigo-100 rounded-lg text-indigo-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                                </div>
                                Konfigurasi Perangkat Lanjutan
                            </h3>
                            <ul class="space-y-3">
                                <li v-if="product.requires_sn" class="flex items-center gap-3 text-sm font-medium text-gray-800">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Wajib mengisi Serial Number (S/N)
                                </li>
                                <li v-if="product.requires_mac" class="flex items-center gap-3 text-sm font-medium text-gray-800">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Wajib mengisi MAC Address
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Right Column: Stock & Unit Info -->
                    <div class="md:col-span-1 space-y-8">
                        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-6 shadow-lg shadow-emerald-600/20 text-white relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                            
                            <h3 class="text-sm font-bold text-emerald-100 uppercase tracking-wider mb-2">Total Stok Tersedia</h3>
                            <div class="flex items-end gap-2 mb-1">
                                <span class="text-4xl font-black">{{ formatNum(product.stock) }}</span>
                                <span class="text-lg font-bold text-emerald-200 mb-1 uppercase">{{ product.unit }}</span>
                            </div>
                            
                            <div class="mt-6 pt-6 border-t border-emerald-500/30">
                                <h4 class="text-xs font-bold text-emerald-200 uppercase tracking-wider mb-3">Konfigurasi Smart Unit</h4>
                                <ul class="space-y-2 text-sm font-medium">
                                    <li v-if="product.meter_per_roll > 0" class="flex justify-between">
                                        <span>1 Roll</span>
                                        <span class="font-bold">{{ product.meter_per_roll }} Meter</span>
                                    </li>
                                    <li v-if="product.pcs_per_pack > 0" class="flex justify-between">
                                        <span>1 Pack</span>
                                        <span class="font-bold">{{ product.pcs_per_pack }} Pcs</span>
                                    </li>
                                    <li v-if="product.cm_per_pcs > 0" class="flex justify-between">
                                        <span>1 Pcs</span>
                                        <span class="font-bold">{{ product.cm_per_pcs }} CM</span>
                                    </li>
                                    <li v-if="!product.meter_per_roll && !product.pcs_per_pack && !product.cm_per_pcs" class="text-emerald-100">
                                        Tidak ada konversi (1:1)
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    product: Object
});

const formatNum = (n) => {
    return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
};

const formatCurrency = (n) => {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(n) || 0);
};
</script>
