<template>
    <AppLayout title="Inventory">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-indigo-100/40 to-purple-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mb-4 shadow-lg shadow-indigo-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Inventory</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Pantau distribusi persediaan barang di setiap area / gudang secara detail.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
                
                <!-- Filters -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-center justify-between mb-8">
                    <div class="relative w-full sm:w-96 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="search" @input="debouncedSearch" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all" placeholder="Cari area gudang..." />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8">
                    <div v-for="area in areas" :key="area.id" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-gray-900">{{ area.name }}</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Total {{ area.stocks?.length || 0 }} jenis barang</p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-white border-b border-gray-100">
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Barang / Material</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Stok Tersedia</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="stock in area.stocks" :key="stock.id" class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-gray-900">{{ stock.material?.name || 'Unknown Item' }}</div>
                                                    <div class="text-xs text-gray-500 mt-0.5 font-medium">{{ stock.material?.category || 'No Category' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-sm font-black text-slate-800">{{ formatNum(stock.stock) }}</span>
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">{{ stock.material?.unit || '' }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!area.stocks || area.stocks.length === 0">
                                        <td colspan="2" class="px-6 py-8 text-center text-sm font-medium text-gray-400 italic">
                                            Tidak ada stok tercatat di area ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div v-if="areas.length === 0" class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 text-gray-400 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Belum ada Area tercatat</h3>
                        <p class="text-sm text-gray-500">Tambahkan area terlebih dahulu di menu pengaturan untuk melihat distribusi stok.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
    areas: Array,
    filters: Object
});

const search = ref(props.filters.search || '');

const debouncedSearch = debounce(() => {
    router.get('/warehouse-stocks', { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

const formatNum = (n) => {
    return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
};
</script>
