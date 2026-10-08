<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        </span>
                        Retur Material ke Gudang
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Kembalikan sisa material dari Stok Area ke Gudang Utama</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Stok Area Tersedia</p>
                        <p class="text-xl font-extrabold text-gray-900">{{ areaStocks.length }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total Retur</p>
                        <p class="text-xl font-extrabold text-gray-900">{{ summary.total_returns }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total Item Retur</p>
                        <p class="text-xl font-extrabold text-gray-900">{{ Number(summary.total_returned_items).toLocaleString('id-ID') }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="border-b border-gray-100">
                    <nav class="flex -mb-px">
                        <button @click="activeTab = 'stock'" :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all', activeTab === 'stock' ? 'border-teal-500 text-teal-700 bg-teal-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                Stok Area
                            </span>
                        </button>
                        <button @click="activeTab = 'history'" :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all', activeTab === 'history' ? 'border-blue-500 text-blue-700 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Riwayat Retur
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab: Stok Area -->
                <div v-show="activeTab === 'stock'" class="p-4 sm:p-6">
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row gap-3 mb-5">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" v-model="searchStock" @input="filterStocks" placeholder="Cari material..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <select v-model="filterAreaId" @change="filterStocks" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
                            <option value="">Semua Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>

                    <!-- Table Stok Area -->
                    <div v-if="filteredStocks.length" class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80">
                                    <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                                        <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">Material</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">Kategori</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">Area</th>
                                    <th class="px-4 py-3 text-right font-bold text-gray-600 uppercase text-xs tracking-wider">Stok Tersedia</th>
                                    <th class="px-4 py-3 text-right font-bold text-gray-600 uppercase text-xs tracking-wider">Jumlah Retur</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="stock in filteredStocks" :key="stock.id" 
                                    :class="['transition-all hover:bg-gray-50/50', selectedItems.find(s => s.id === stock.id) ? 'bg-teal-50/30' : '']">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" :checked="!!selectedItems.find(s => s.id === stock.id)" @change="toggleItem(stock)" class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-gray-900">{{ stock.material_name }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold" :class="getCategoryClass(stock.material_category)">
                                            {{ stock.material_category }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ stock.area_name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-extrabold text-gray-900">{{ Number(stock.stock).toLocaleString('id-ID') }}</span>
                                        <span class="text-gray-500 text-xs ml-1">{{ stock.display_unit }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div v-if="selectedItems.find(s => s.id === stock.id)" class="flex items-center justify-end gap-2">
                                            <input 
                                                type="number" 
                                                :value="selectedItems.find(s => s.id === stock.id)?.qty"
                                                @input="updateQty(stock.id, $event.target.value)"
                                                :max="stock.raw_stock"
                                                min="0.01"
                                                step="0.01"
                                                class="w-28 px-3 py-1.5 border border-teal-300 rounded-lg text-sm text-right font-bold focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            >
                                            <button @click="setMax(stock)" class="px-2 py-1.5 text-xs font-bold text-teal-700 bg-teal-100 rounded-lg hover:bg-teal-200 transition-colors" title="Retur Semua">
                                                MAX
                                            </button>
                                        </div>
                                        <span v-else class="text-gray-400 text-xs">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center py-12">
                        <svg class="mx-auto w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p class="text-gray-500 font-medium">Tidak ada stok area yang tersedia</p>
                        <p class="text-gray-400 text-sm mt-1">Semua material sudah berada di Gudang Utama</p>
                    </div>

                    <!-- Retur Form (appears when items selected) -->
                    <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
                        <div v-if="selectedItems.length > 0" class="mt-6 bg-gradient-to-br from-teal-50 to-emerald-50 border border-teal-200 rounded-2xl p-6">
                            <h3 class="font-bold text-teal-900 text-lg mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                Proses Retur ({{ selectedItems.length }} item)
                            </h3>
                            
                            <!-- Summary of selected items -->
                            <div class="mb-4 space-y-2">
                                <div v-for="item in selectedItems" :key="item.id" class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-2.5 border border-teal-100">
                                    <div>
                                        <span class="font-bold text-gray-900 text-sm">{{ item.material_name }}</span>
                                        <span class="text-gray-500 text-xs ml-2">dari {{ item.area_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-teal-700">{{ Number(item.qty).toLocaleString('id-ID') }}</span>
                                        <span class="text-xs text-gray-500">{{ item.display_unit }}</span>
                                        <button @click="removeItem(item.id)" class="ml-2 text-red-400 hover:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-bold text-teal-800 mb-1">Nama Pengembalian / Teknisi <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="returnForm.technician_name" placeholder="Nama yang mengembalikan..." class="w-full px-4 py-2.5 border border-teal-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-teal-800 mb-1">Catatan (Opsional)</label>
                                    <input type="text" v-model="returnForm.notes" placeholder="Catatan tambahan..." class="w-full px-4 py-2.5 border border-teal-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <button @click="clearSelection" class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                                    Batal
                                </button>
                                <button @click="submitReturn" :disabled="processing || !returnForm.technician_name" :class="['px-6 py-2.5 text-sm font-bold text-white rounded-xl shadow-lg transition-all flex items-center gap-2', processing || !returnForm.technician_name ? 'bg-gray-300 cursor-not-allowed' : 'bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 hover:shadow-xl']">
                                    <svg v-if="processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ processing ? 'Memproses...' : 'Proses Retur ke Gudang' }}
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Tab: Riwayat Retur -->
                <div v-show="activeTab === 'history'" class="p-4 sm:p-6">
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row gap-3 mb-5">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" v-model="historySearch" @input="searchHistory" placeholder="Cari no. retur, teknisi..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <input type="date" v-model="historyStartDate" @change="searchHistory" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <input type="date" v-model="historyEndDate" @change="searchHistory" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                    </div>

                    <div v-if="returns.data && returns.data.length" class="space-y-3">
                        <div v-for="ret in returns.data" :key="ret.id" class="bg-white border border-gray-100 rounded-xl p-4 hover:shadow-md transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="inline-flex px-3 py-1 bg-teal-100 text-teal-800 text-xs font-bold rounded-lg">{{ ret.transaction_number }}</span>
                                        <span class="text-xs text-gray-500">{{ formatDate(ret.date) }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700">
                                        <span class="font-bold">{{ ret.technician_name }}</span>
                                        <span class="text-gray-400 mx-1">•</span>
                                        <span>{{ ret.area_model?.name || '-' }}</span>
                                    </p>
                                    <p v-if="ret.notes" class="text-xs text-gray-500 mt-1 italic">{{ ret.notes }}</p>
                                    
                                    <!-- Items -->
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="item in ret.items" :key="item.id" class="inline-flex items-center px-2.5 py-1 bg-gray-50 border border-gray-100 rounded-lg text-xs">
                                            <span class="font-bold text-gray-700">{{ item.material?.name }}</span>
                                            <span class="text-gray-400 mx-1">×</span>
                                            <span class="font-extrabold text-teal-700">{{ Number(item.quantity).toLocaleString('id-ID') }}</span>
                                            <span class="text-gray-400 ml-0.5">{{ item.unit }}</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="deleteReturn(ret)" class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Batalkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <svg class="mx-auto w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-gray-500 font-medium">Belum ada riwayat retur</p>
                        <p class="text-gray-400 text-sm mt-1">Retur material dari tab "Stok Area" untuk memulai</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="returns.links && returns.links.length > 3" class="flex justify-center mt-6">
                        <nav class="flex items-center gap-1">
                            <template v-for="link in returns.links" :key="link.label">
                                <Link v-if="link.url" :href="link.url" v-html="link.label" :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-colors', link.active ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-100']" />
                                <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-gray-300" />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="p-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">Batalkan Retur?</h3>
                    <p class="text-sm text-gray-500 mt-2">Stok akan dikembalikan dari Gudang Utama ke Area. Tindakan ini akan membalikkan transaksi retur.</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Tutup</button>
                    <button @click="confirmDelete" :disabled="deleteProcessing" class="px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
                        <svg v-if="deleteProcessing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Ya, Batalkan
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    areaStocks: { type: Array, default: () => [] },
    returns: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    areas: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ total_returns: 0, total_returned_items: 0 }) },
});

const activeTab = ref('stock');
const searchStock = ref(props.filters.search || '');
const filterAreaId = ref(props.filters.area_id || '');
const historySearch = ref(props.filters.return_search || '');
const historyStartDate = ref(props.filters.start_date || '');
const historyEndDate = ref(props.filters.end_date || '');

const selectedItems = ref([]);
const selectAll = ref(false);
const processing = ref(false);

const returnForm = ref({
    technician_name: '',
    notes: '',
});

// Delete modal
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleteProcessing = ref(false);

// Filtered stocks (client-side for instant search)
const filteredStocks = computed(() => {
    let items = props.areaStocks;
    if (searchStock.value) {
        const term = searchStock.value.toLowerCase();
        items = items.filter(s => 
            s.material_name.toLowerCase().includes(term) || 
            s.material_category.toLowerCase().includes(term) ||
            s.area_name.toLowerCase().includes(term)
        );
    }
    if (filterAreaId.value) {
        items = items.filter(s => s.area_id == filterAreaId.value);
    }
    return items;
});

function filterStocks() {
    // Client-side filtering already handled by computed
}

function toggleSelectAll() {
    if (selectAll.value) {
        selectedItems.value = filteredStocks.value.map(s => ({
            id: s.id,
            material_id: s.material_id,
            area_id: s.area_id,
            material_name: s.material_name,
            area_name: s.area_name,
            display_unit: s.display_unit,
            qty: s.raw_stock,
            max: s.raw_stock,
        }));
    } else {
        selectedItems.value = [];
    }
}

function toggleItem(stock) {
    const idx = selectedItems.value.findIndex(s => s.id === stock.id);
    if (idx >= 0) {
        selectedItems.value.splice(idx, 1);
    } else {
        selectedItems.value.push({
            id: stock.id,
            material_id: stock.material_id,
            area_id: stock.area_id,
            material_name: stock.material_name,
            area_name: stock.area_name,
            display_unit: stock.display_unit,
            qty: stock.raw_stock,
            max: stock.raw_stock,
        });
    }
}

function updateQty(stockId, val) {
    const item = selectedItems.value.find(s => s.id === stockId);
    if (item) {
        item.qty = Math.min(parseFloat(val) || 0, item.max);
    }
}

function setMax(stock) {
    const item = selectedItems.value.find(s => s.id === stock.id);
    if (item) {
        item.qty = stock.raw_stock;
    }
}

function removeItem(id) {
    selectedItems.value = selectedItems.value.filter(s => s.id !== id);
}

function clearSelection() {
    selectedItems.value = [];
    selectAll.value = false;
}

function getCategoryClass(category) {
    const cat = (category || '').toLowerCase();
    if (cat.includes('kabel')) return 'bg-blue-50 text-blue-700 border border-blue-100';
    if (cat.includes('ont') || cat.includes('onu')) return 'bg-purple-50 text-purple-700 border border-purple-100';
    if (cat.includes('paku') || cat.includes('klem')) return 'bg-amber-50 text-amber-700 border border-amber-100';
    if (cat.includes('isolasi')) return 'bg-green-50 text-green-700 border border-green-100';
    return 'bg-gray-50 text-gray-700 border border-gray-100';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

// Group selected items by area_id
function submitReturn() {
    if (!returnForm.value.technician_name || selectedItems.value.length === 0) return;
    
    processing.value = true;

    // Group by area_id — each area needs a separate transaction
    const grouped = {};
    for (const item of selectedItems.value) {
        if (!grouped[item.area_id]) grouped[item.area_id] = [];
        grouped[item.area_id].push(item);
    }

    const areaIds = Object.keys(grouped);
    let completedCount = 0;

    for (const areaId of areaIds) {
        const items = grouped[areaId];
        router.post('/material-returns', {
            area_id: areaId,
            technician_name: returnForm.value.technician_name,
            notes: returnForm.value.notes,
            items: items.map(i => ({
                material_id: i.material_id,
                quantity: i.qty,
                unit: i.display_unit,
            })),
        }, {
            preserveScroll: true,
            onSuccess: () => {
                completedCount++;
                if (completedCount >= areaIds.length) {
                    processing.value = false;
                    selectedItems.value = [];
                    selectAll.value = false;
                    returnForm.value.technician_name = '';
                    returnForm.value.notes = '';
                }
            },
            onError: () => {
                processing.value = false;
            },
        });
    }
}

let searchTimeout = null;
function searchHistory() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/material-returns', {
            return_search: historySearch.value,
            start_date: historyStartDate.value,
            end_date: historyEndDate.value,
            area_id: filterAreaId.value,
        }, { preserveState: true, preserveScroll: true });
    }, 400);
}

function deleteReturn(ret) {
    deleteTarget.value = ret;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (!deleteTarget.value) return;
    deleteProcessing.value = true;
    router.post(`/material-returns/${deleteTarget.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteProcessing.value = false;
            deleteTarget.value = null;
        },
        onError: () => {
            deleteProcessing.value = false;
        },
    });
}

// Flash messages
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
</script>
