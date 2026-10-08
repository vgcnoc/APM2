<template>
    <AppLayout title="Riwayat Order & Pengambilan" subtitle="Daftar surat jalan dan pengeluaran material">
        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Transaksi -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg shadow-blue-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-blue-100 font-medium text-sm mb-1">Total Transaksi</p>
                                <h3 class="text-3xl font-bold">{{ formatNumber(summary.total_transactions) }} <span class="text-lg font-normal text-blue-200">Order</span></h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Item Keluar -->
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg shadow-emerald-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-emerald-100 font-medium text-sm mb-1">Total Item Dikeluarkan</p>
                                <h3 class="text-3xl font-bold">{{ formatNumber(summary.total_items) }} <span class="text-lg font-normal text-emerald-200">Pcs</span></h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Nilai Keluar -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-lg shadow-amber-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-amber-100 font-medium text-sm mb-1">Nilai Barang Keluar</p>
                                <h3 class="text-3xl font-bold"><span class="text-lg font-normal text-amber-200 mr-1">Rp</span>{{ formatNumber(summary.total_cost) }}</h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Actions -->
            <div class="glass-card p-5 animate-fade-in-up">
                <div class="flex flex-col lg:flex-row justify-between gap-4">
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                            <div class="relative">
                                <input 
                                    v-model="filterForm.search"
                                    type="text" 
                                    placeholder="Cari No. Transaksi, Petugas..." 
                                    class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                                >
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Date Range Start -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                            <input 
                                v-model="filterForm.start_date"
                                type="date" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                        </div>

                        <!-- Date Range End -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                            <input 
                                v-model="filterForm.end_date"
                                type="date" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                        </div>

                        <!-- Technician Filter -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Petugas</label>
                            <select 
                                v-model="filterForm.technician"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                                <option value="">Semua Petugas</option>
                                <option v-for="tech in technicians" :key="tech" :value="tech">{{ tech }}</option>
                            </select>
                        </div>

                        <!-- Area / Wilayah Filter -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Area / Wilayah</label>
                            <select 
                                v-model="filterForm.area_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white"
                            >
                                <option value="">Semua Area</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-end gap-3 shrink-0">
                        <button 
                            v-if="selectedItems.length > 0"
                            @click="deleteSelected"
                            class="px-4 py-2.5 rounded-xl border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 font-medium text-sm transition-all flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus ({{ selectedItems.length }})
                        </button>
                        <button 
                            @click="resetFilters" 
                            class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 bg-white hover:bg-gray-50 font-medium text-sm transition-all flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reset
                        </button>
                        <Link href="/material-transactions/create" class="btn-primary py-2.5 flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Buat Order
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up mb-4">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>
            
            <div v-if="$page.props.flash?.error" class="flex items-center gap-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 animate-fade-in-up mb-4">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 w-12">
                                    <input type="checkbox" @change="toggleAll" :checked="isAllSelected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 transition-all">
                                </th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Transaksi / Tgl</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan / Petugas</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Rincian Barang</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Total Tagihan (Jual)</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in transactions.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6 align-top">
                                    <input type="checkbox" v-model="selectedItems" :value="item.id" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-1 transition-all">
                                </td>
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
                                        <button @click="deleteItem(item)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="6" class="py-12 text-center">
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
            
            <!-- Pagination -->
            <div v-if="transactions.links && transactions.data.length > 0" class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium text-gray-900">{{ transactions.from }}</span> sampai <span class="font-medium text-gray-900">{{ transactions.to }}</span> dari <span class="font-medium text-gray-900">{{ transactions.total }}</span> transaksi
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, pIndex) in transactions.links" :key="pIndex">
                        <Link 
                            v-if="link.url"
                            :href="link.url" 
                            v-html="link.label"
                            class="px-3 py-1 text-sm border rounded-lg transition-colors"
                            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                        />
                        <span 
                            v-else 
                            v-html="link.label" 
                            class="px-3 py-1 text-sm border border-gray-200 rounded-lg text-gray-400 bg-gray-50"
                        ></span>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    transactions: Object,
    summary: {
        type: Object,
        default: () => ({ total_transactions: 0, total_cost: 0, total_items: 0 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', start_date: '', end_date: '', technician: '', area_id: '' })
    },
    technicians: {
        type: Array,
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const filterForm = ref({
    search: props.filters?.search || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    technician: props.filters?.technician || '',
    area_id: props.filters?.area_id || '',
});

let timeout = null;
const applyFilters = () => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/material-transactions', {
            search: filterForm.value.search,
            start_date: filterForm.value.start_date,
            end_date: filterForm.value.end_date,
            technician: filterForm.value.technician,
            area_id: filterForm.value.area_id,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

watch(filterForm, () => {
    applyFilters();
}, { deep: true });

const resetFilters = () => {
    filterForm.value = {
        search: '',
        start_date: '',
        end_date: '',
        technician: '',
        area_id: '',
    };
};

const selectedItems = ref([]);

const isAllSelected = computed(() => {
    return props.transactions.data.length > 0 && selectedItems.value.length === props.transactions.data.length;
});

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedItems.value = props.transactions.data.map(item => item.id);
    } else {
        selectedItems.value = [];
    }
};

const deleteItem = (item) => {
    if (confirm(`Apakah Anda yakin ingin menghapus transaksi ${item.transaction_number}? Stok barang akan dikembalikan ke gudang.`)) {
        router.post(`/material-transactions/${item.id}/delete`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                selectedItems.value = selectedItems.value.filter(id => id !== item.id);
            }
        });
    }
};

const deleteSelected = () => {
    if (confirm(`Apakah Anda yakin ingin menghapus ${selectedItems.value.length} transaksi yang dipilih? Stok barang akan dikembalikan ke gudang.`)) {
        router.post('/material-transactions/bulk-destroy', {
            ids: selectedItems.value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedItems.value = [];
            }
        });
    }
};
</script>
