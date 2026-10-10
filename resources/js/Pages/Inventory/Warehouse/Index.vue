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

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Barang / Material</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Stok per Area</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Stok (All Area)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="material in materials.data" :key="material.id" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-500 group-hover:scale-110 transition-transform">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">{{ material.name }}</div>
                                                <div v-if="material.supplier" class="text-[11px] text-gray-500 mt-0.5">{{ material.supplier }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-700">
                                            {{ material.category || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="material.stocks && material.stocks.length > 0" class="flex flex-wrap gap-2">
                                            <div v-for="stock in material.stocks" :key="stock.id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-indigo-50 border border-indigo-100 text-indigo-700">
                                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                <span>{{ stock.area?.name || 'Area ?' }}: <strong class="text-indigo-900">{{ formatNum(stock.stock) }}</strong> {{ material.unit }} <span class="font-bold text-[9px] text-indigo-500">{{ formatSecondaryUnit(material, stock.stock) }}</span></span>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">Belum ada distribusi area</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-black text-slate-800">{{ formatNum(material.stocks?.reduce((a, b) => a + Number(b.stock), 0) || 0) }}</span>
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">{{ material.unit }} <span class="text-slate-400 normal-case">{{ formatSecondaryUnit(material, material.stocks?.reduce((a, b) => a + Number(b.stock), 0)) }}</span></span>
                                    </td>
                                </tr>
                                <tr v-if="!materials.data || materials.data.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 text-gray-400 mb-4">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">Barang tidak ditemukan</h3>
                                        <p class="text-sm text-gray-500">Gunakan kata kunci lain atau pastikan data barang sudah ditambahkan.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination (if applicable) -->
                    <div v-if="materials.links && materials.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Menampilkan <span class="font-bold">{{ materials.from }}</span> - <span class="font-bold">{{ materials.to }}</span> dari <span class="font-bold">{{ materials.total }}</span> hasil
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <template v-for="(link, i) in materials.links" :key="i">
                                        <Component
                                            :is="link.url ? 'Link' : 'span'"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                            :class="[
                                                link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                { 'rounded-l-md': i === 0, 'rounded-r-md': i === materials.links.length - 1 }
                                            ]"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
    materials: Object,
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

const formatSecondaryUnit = (material, stockQty) => {
    stockQty = Number(stockQty) || 0;
    if (stockQty <= 0) return '';
    
    if ((material.category || '').toLowerCase().includes('isolasi') || (material.name || '').toLowerCase().includes('isolasi')) {
        const ppp = material.pcs_per_pack > 0 ? Number(material.pcs_per_pack) : 1;
        const cpp = material.cm_per_pcs > 0 ? Number(material.cm_per_pcs) : 50;
        const cmPerPack = ppp * cpp;
        if (cmPerPack > 0 && stockQty >= cmPerPack) {
            const packs = stockQty / cmPerPack;
            return ` (~${formatNum(parseFloat(packs.toFixed(2)))} Pack)`;
        }
    } else if (Number(material.meter_per_roll) > 0 && material.unit === 'meter') {
        const mpr = Number(material.meter_per_roll);
        if (stockQty >= mpr) {
            const rolls = stockQty / mpr;
            return ` (~${formatNum(parseFloat(rolls.toFixed(2)))} Roll)`;
        }
    } else if (Number(material.pcs_per_pack) > 0 && material.unit === 'pcs') {
        const ppp = Number(material.pcs_per_pack);
        if (stockQty >= ppp) {
            const packs = stockQty / ppp;
            return ` (~${formatNum(parseFloat(packs.toFixed(2)))} Pack)`;
        }
    }
    return '';
};
</script>
