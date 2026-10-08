<script setup>
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';


const props = defineProps({
    customers: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Custom debounce
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};


watch(search, debounce((value) => {
    router.get(
        route('network-data.terminated'),
        { search: value },
        { preserveState: true, replace: true }
    );
}, 300));
</script>

<template>
    <Head title="Cabut Perangkat (Stop Permanen)" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Cabut Perangkat (Stop Permanen)
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Daftar pelanggan yang berhenti berlangganan (Port siap dicabut)</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search & Filter -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="relative flex-1 max-w-md w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama, ID, atau alamat pelanggan..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-red-500 focus:border-red-500 sm:text-sm transition-colors"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID / Pelanggan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Berhenti</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Port (ODP)</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Alamat ODP</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold mr-3 shrink-0">
                                                {{ customer.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">{{ customer.name }}</div>
                                                <div class="text-xs text-gray-500">{{ customer.customer_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ customer.updated_at ? new Date(customer.updated_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : '-' }}</div>
                                        <div class="text-xs text-red-600 font-bold bg-red-50 px-2 py-1 rounded inline-block mt-1">
                                            Stop Permanen
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="customer.ont" class="flex flex-col space-y-1">
                                            <span class="text-sm font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded border border-indigo-100 inline-block w-fit">
                                                ODP: {{ customer.ont.odp?.name || '-' }} / Port {{ customer.ont.port_number || '-' }}
                                            </span>
                                            <span class="text-[11px] text-gray-500">
                                                SN: {{ customer.ont.sn || '-' }}
                                            </span>
                                        </div>
                                        <span v-else class="text-sm text-gray-400 italic">Tidak ada perangkat terkait</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="customer.ont && customer.ont.odp" class="text-xs text-gray-600 line-clamp-2 max-w-xs">
                                            {{ customer.ont.odp.address || '-' }}
                                        </div>
                                        <div v-else class="text-xs text-gray-400 italic">-</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600">
                                        Perangkat ONT dapat dicabut dari rumah pelanggan dan port di ODP dapat dikosongkan.
                                    </td>
                                </tr>
                                <tr v-if="customers.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        Tidak ada data perangkat yang harus dicabut saat ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-center gap-2" v-if="customers.links && customers.links.length > 3">
                        <template v-for="(link, p) in customers.links" :key="p">
                            <div v-if="link.url === null" class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-3 py-1.5 text-sm rounded-lg transition-colors" :class="link.active ? 'bg-red-600 text-white font-medium shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200 hover:border-gray-300'" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
