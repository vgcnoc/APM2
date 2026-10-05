<template>
    <AppLayout title="Voucher Offline" subtitle="Daftar voucher yang sedang tidak terhubung / offline">
        <!-- Summary -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="glass-card px-4 py-3 flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400"></span>
                </span>
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Voucher Offline</p>
                    <p class="text-xl font-bold text-gray-900 leading-tight">{{ offlineUsers.length }}</p>
                </div>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="paginatedData.data"
            :pagination="paginatedData"
            searchPlaceholder="Cari kode, profile, reseller..."
            @search="handleSearch"
            :clientSidePagination="true"
        >
            <template #actions>
                <button id="btn-refresh-offline" @click="refresh" class="btn-secondary bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium text-sm py-2 px-4 rounded-lg shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </template>

            <template #row="{ row, index }">
                <td class="text-gray-500 text-xs text-center">
                    {{ (paginatedData.current_page - 1) * paginatedData.per_page + index + 1 }}
                </td>
                <td>
                    <span class="inline-flex px-2 py-1 bg-gray-50 text-gray-700 border border-gray-200 rounded text-xs font-mono font-bold tracking-widest">{{ row.code }}</span>
                </td>
                <td>
                    <span class="inline-flex px-2 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-[10px] font-bold uppercase whitespace-nowrap">{{ row.profile || '-' }}</span>
                </td>
                <td>
                    <span class="text-sm font-semibold text-gray-900">{{ row.reseller }}</span>
                </td>
                <td>
                    <div class="flex flex-col">
                        <span class="font-mono text-xs text-gray-700">{{ row.ip_address || '-' }}</span>
                        <span class="font-mono text-[10px] text-gray-400">{{ row.mac_address || '-' }}</span>
                    </div>
                </td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-xs text-red-500 font-medium whitespace-nowrap">Last Logout:</span>
                        <span class="text-[10px] text-gray-600">{{ formatDate(row.last_logout) || 'Belum Login' }}</span>
                    </div>
                </td>
            </template>
        </DataTable>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    offlineUsers: { type: Array, default: () => [] },
});

const columns = [
    { key: 'index', label: '#' },
    { key: 'code', label: 'KODE VOUCHER' },
    { key: 'profile', label: 'PROFIL' },
    { key: 'reseller', label: 'RESELLER' },
    { key: 'ip_mac', label: 'IP / MAC ADDRESS' },
    { key: 'last_logout', label: 'STATUS' },
];

const refreshing = ref(false);
const search = ref('');
const currentPage = ref(1);
const perPage = 15;

const filteredData = computed(() => {
    let result = props.offlineUsers;
    if (search.value) {
        const query = search.value.toLowerCase();
        result = result.filter(v => 
            (v.code && v.code.toLowerCase().includes(query)) ||
            (v.profile && v.profile.toLowerCase().includes(query)) ||
            (v.reseller && v.reseller.toLowerCase().includes(query)) ||
            (v.ip_address && v.ip_address.toLowerCase().includes(query)) ||
            (v.mac_address && v.mac_address.toLowerCase().includes(query))
        );
    }
    return result;
});

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    const end = start + perPage;
    return {
        data: filteredData.value.slice(start, end),
        current_page: currentPage.value,
        last_page: Math.ceil(filteredData.value.length / perPage),
        per_page: perPage,
        total: filteredData.value.length,
        links: [] // Simplified for client side
    };
});

function handleSearch(val) {
    search.value = val;
    currentPage.value = 1; // Reset page
}

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['offlineUsers'],
        onFinish: () => (refreshing.value = false),
    });
}

function formatDate(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
}
</script>
