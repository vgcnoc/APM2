<template>
    <component :is="layoutComponent" title="Data Voucher Gratis" subtitle="Daftar voucher gratis yang dibuat saat aktivasi">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Data Voucher Gratis</h1>
            <p class="text-sm text-gray-500">Daftar voucher gratis yang dibuat saat aktivasi pelanggan</p>
        </div>
        <!-- HEADER -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari pelanggan/username..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        @keyup.enter="doFilter"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Pelanggan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID Pelanggan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hotspot User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hotspot Password</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">VLAN ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Aktivasi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ customer.customer_id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ customer.ont?.hotspot_user || '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-mono text-gray-500">
                                    {{ customer.ont?.hotspot_password || '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-md">
                                    {{ customer.ont?.hotspot_vlan_id || '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ customer.activation_date ? new Date(customer.activation_date).toLocaleDateString('id-ID') : '-' }}
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-base font-medium">Tidak ada data voucher gratis</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200" v-if="customers.links && customers.links.length > 3">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Menampilkan <span class="font-medium text-gray-900">{{ customers.from }}</span> - <span class="font-medium text-gray-900">{{ customers.to }}</span> dari <span class="font-medium text-gray-900">{{ customers.total }}</span> data
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in customers.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                            :class="[
                                link.active 
                                    ? 'bg-indigo-600 text-white shadow-sm' 
                                    : 'text-gray-600 hover:bg-gray-200',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';

const props = defineProps({
    customers: Object,
    filters: Object,
    auth: Object
});

const layoutComponent = computed(() => {
    return props.auth?.user?.role === 'reseller' ? ClientAreaLayout : AppLayout;
});

const search = ref(props.filters.search || '');

const doFilter = () => {
    router.get('/vouchers/free', {
        search: search.value,
    }, {
        preserveState: true,
        replace: true
    });
};

watch(search, () => {
    doFilter();
});
</script>
