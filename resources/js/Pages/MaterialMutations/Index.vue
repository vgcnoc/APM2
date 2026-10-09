<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    mutations: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');

const formatNumber = (number) => {
    return new Intl.NumberFormat('id-ID').format(number || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

watch([search, typeFilter], debounce(([newSearch, newType]) => {
    router.get('/material-mutations', {
        search: newSearch,
        type: newType,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300));
</script>

<template>
    <AppLayout title="Mutasi & Kondisi Barang" subtitle="Log pergerakan stok masuk, keluar, dan kondisi barang">
        <div class="space-y-6">
            
            <!-- Filters -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all" placeholder="Cari nama barang, no transaksi...">
                    </div>
                </div>
                
                <div class="w-full sm:w-48">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tipe Mutasi</label>
                    <select v-model="typeFilter" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all">
                        <option value="">Semua Tipe</option>
                        <option value="in">Masuk (IN)</option>
                        <option value="out">Keluar (OUT)</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="py-4 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Waktu</th>
                                <th class="py-4 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Barang</th>
                                <th class="py-4 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Transaksi & Tujuan</th>
                                <th class="py-4 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Tipe</th>
                                <th class="py-4 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Jumlah</th>
                                <th class="py-4 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="mutation in mutations.data" :key="mutation.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-5 align-top">
                                    <span class="text-sm text-gray-900 font-medium">{{ formatDate(mutation.created_at) }}</span>
                                </td>
                                <td class="py-4 px-5 align-top">
                                    <p class="text-sm font-bold text-gray-900">{{ mutation.material?.name || 'Barang Dihapus' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ mutation.material?.category }}</p>
                                </td>
                                <td class="py-4 px-5 align-top">
                                    <div v-if="mutation.transaction">
                                        <Link :href="`/material-transactions/${mutation.transaction.id}`" class="text-xs font-bold text-blue-600 hover:underline">
                                            {{ mutation.transaction.transaction_number }}
                                        </Link>
                                        <p class="text-xs text-gray-600 mt-1 line-clamp-2" :title="mutation.transaction.purpose">{{ mutation.transaction.purpose }}</p>
                                        <p class="text-[10px] text-gray-400 mt-1">Oleh: {{ mutation.transaction.user?.name }}</p>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">Transaksi dihapus</span>
                                </td>
                                <td class="py-4 px-5 align-top text-center">
                                    <span v-if="mutation.transaction?.type === 'in'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-black tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                                        MASUK
                                    </span>
                                    <span v-else-if="mutation.transaction?.type === 'out'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-black tracking-wider bg-orange-50 text-orange-700 border border-orange-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                                        KELUAR
                                    </span>
                                </td>
                                <td class="py-4 px-5 align-top text-right">
                                    <span class="text-sm font-black" :class="mutation.transaction?.type === 'in' ? 'text-emerald-600' : 'text-orange-600'">
                                        {{ mutation.transaction?.type === 'in' ? '+' : '-' }}{{ mutation.quantity }} {{ mutation.unit || 'pcs' }}
                                    </span>
                                    
                                    <div v-if="mutation.stock_before !== null && mutation.stock_after !== null" class="mt-1 flex flex-col items-end gap-0.5 text-[10px]">
                                        <span class="text-gray-400">Sblm: {{ formatNumber(mutation.stock_before) }}</span>
                                        <span class="text-gray-500 font-medium border-t border-gray-200 pt-0.5">Sdh: {{ formatNumber(mutation.stock_after) }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-5 align-top text-center">
                                    <span v-if="mutation.condition" class="inline-flex px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ mutation.condition }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400 italic">Layak Pakai</span>
                                </td>
                            </tr>
                            <tr v-if="mutations.data.length === 0">
                                <td colspan="6" class="py-12 px-5 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        <p class="text-base font-medium text-gray-500">Tidak ada data mutasi</p>
                                        <p class="text-sm mt-1">Belum ada riwayat pergerakan stok yang tercatat.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="mutations.links && mutations.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Menampilkan <span class="font-medium text-gray-900">{{ mutations.from }}</span> sampai <span class="font-medium text-gray-900">{{ mutations.to }}</span> dari <span class="font-medium text-gray-900">{{ mutations.total }}</span> mutasi
                    </span>
                    <div class="flex gap-1">
                        <Link v-for="(link, i) in mutations.links" :key="i" :href="link.url || '#'" 
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label">
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
