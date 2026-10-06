<template>
    <AppLayout title="Laporan Pajak Pelanggan" subtitle="Kalkulasi PPN, BHP, dan USO Pelanggan">
        <div id="print-section" class="max-w-7xl mx-auto space-y-6">
            <!-- Header section & Aggregates -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Base Price -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex justify-between items-start">
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Dasar (DPP)</p>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ formatCurrency(totals.base_price) }}</h3>
                        </div>
                        <div class="p-2.5 bg-blue-100/50 rounded-xl text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs font-medium text-gray-400">Total Harga Paket Internet</div>
                </div>

                <!-- Total PPN -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex justify-between items-start">
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total PPN</p>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ formatCurrency(totals.ppn) }}</h3>
                        </div>
                        <div class="p-2.5 bg-indigo-100/50 rounded-xl text-indigo-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs font-medium text-gray-400">Pajak Pertambahan Nilai</div>
                </div>

                <!-- Total BHP & USO -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex justify-between items-start">
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total BHP + USO</p>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ formatCurrency(totals.bhp + totals.uso) }}</h3>
                        </div>
                        <div class="p-2.5 bg-amber-100/50 rounded-xl text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-[10px] font-bold text-gray-500">
                        <span>BHP: {{ formatCurrency(totals.bhp) }}</span>
                        <span>USO: {{ formatCurrency(totals.uso) }}</span>
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-2xl p-6 border border-indigo-700 shadow-md relative overflow-hidden text-white">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-white opacity-5 rounded-full blur-2xl"></div>
                    <div class="relative flex justify-between items-start">
                        <div>
                            <p class="text-[11px] font-bold text-indigo-200 uppercase tracking-wider mb-1">Grand Total</p>
                            <h3 class="text-2xl font-black tracking-tight drop-shadow-sm">{{ formatCurrency(totals.grand_total) }}</h3>
                        </div>
                        <div class="p-2.5 bg-white/10 rounded-xl backdrop-blur-sm border border-white/20">
                            <svg class="w-6 h-6 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs font-medium text-indigo-200">Estimasi Tagihan Aktif</div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <!-- Toolbar -->
                <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/50">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Rincian Pajak Pelanggan</h3>
                        <p class="text-sm text-gray-500 mt-1">Data kalkulasi pajak berdasarkan paket dan profil pelanggan aktif.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input 
                                type="text" 
                                v-model="search"
                                placeholder="Cari pelanggan..." 
                                class="pl-10 pr-4 py-2 w-full sm:w-64 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-shadow"
                                @keyup.enter="doSearch"
                            >
                        </div>
                        <button @click="printReport" class="flex items-center justify-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-xl font-semibold text-sm transition-colors border border-indigo-100 shadow-sm whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4">Paket & Harga (DPP)</th>
                                <th class="px-6 py-4">PPN</th>
                                <th class="px-6 py-4">BHP & USO</th>
                                <th class="px-6 py-4 text-right">Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-xs border border-indigo-100 shadow-sm">
                                            {{ customer.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ customer.name }}</p>
                                            <p class="text-[11px] font-medium text-gray-500 flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                                {{ customer.customer_code }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900">{{ formatCurrency(customer.base_price) }}</p>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold mt-1 border border-blue-100">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        {{ customer.package_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(customer.ppn_amount) }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ customer.ppn_percent }}%</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-gray-400 w-8">BHP</span>
                                            <span class="font-semibold text-gray-900">{{ formatCurrency(customer.bhp_amount) }}</span>
                                            <span class="text-xs text-gray-400">({{ customer.bhp_percent }}%)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-gray-400 w-8">USO</span>
                                            <span class="font-semibold text-gray-900">{{ formatCurrency(customer.uso_amount) }}</span>
                                            <span class="text-xs text-gray-400">({{ customer.uso_percent }}%)</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-base font-black text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 shadow-sm inline-block">
                                        {{ formatCurrency(customer.total_price) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="customers.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900">Tidak ada data ditemukan</h3>
                                    <p class="text-sm text-gray-500 mt-1">Belum ada pelanggan aktif atau pencarian tidak cocok.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50" v-if="customers.links && customers.data.length > 0">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">
                            Menampilkan <span class="font-semibold text-gray-900">{{ customers.from }}</span> - <span class="font-semibold text-gray-900">{{ customers.to }}</span> dari <span class="font-semibold text-gray-900">{{ customers.total }}</span> pelanggan
                        </p>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, k) in customers.links" :key="k">
                                <div v-if="link.url === null" class="px-3 py-1.5 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-200 rounded-lg cursor-not-allowed" v-html="link.label"></div>
                                <Link v-else :href="link.url" class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1" :class="link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600 shadow-sm' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 hover:text-gray-900'" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    customers: Object,
    filters: Object,
    totals: Object
});

const search = ref(props.filters.search || '');

let searchTimeout = null;
const doSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/tax-reports', { search: search.value }, { preserveState: true, replace: true });
    }, 300);
};

watch(search, (val) => {
    doSearch();
});

function formatCurrency(value) {
    if (value === null || value === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

function printReport() {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    const qs = params.toString();
    window.open('/tax-reports/print' + (qs ? '?' + qs : ''), '_blank');
}
</script>

