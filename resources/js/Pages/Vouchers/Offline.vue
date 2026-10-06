<template>
    <component :is="layoutComponent" title="Voucher Offline" subtitle="Daftar voucher yang sedang tidak terhubung / offline">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Voucher Offline</h1>
            <p class="text-sm text-gray-500">Daftar voucher yang sedang tidak terhubung / offline</p>
        </div>
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
            <template #rowActions="{ row }">
                <div class="flex items-center justify-end">
                    <button
                        :id="`btn-toggle-${row.id}`"
                        @click="toggleStatus(row)"
                        :disabled="toggling === row.id"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold transition-colors disabled:opacity-50"
                        :class="row.is_active ? 'text-orange-600 bg-orange-50 hover:bg-orange-500 hover:text-white' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-500 hover:text-white'"
                        :title="row.is_active ? 'Nonaktifkan Voucher' : 'Aktifkan Voucher'"
                    >
                        <svg v-if="toggling === row.id" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <svg v-else-if="row.is_active" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243-4.242a9 9 0 00-12.728 0m0 0l2.829 2.829M5.636 5.636l12.728 12.728"/></svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ row.is_active ? 'DISABLE' : 'ENABLE' }}
                    </button>
                </div>
            </template>
        </DataTable>
    </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const layoutComponent = computed(() => {
    return page.props.auth?.user?.role === 'reseller' || page.props.auth?.user?.role === 'customer' 
        ? ClientAreaLayout 
        : AppLayout;
});
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
const toggling = ref(null);
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

function toggleStatus(row) {
    const action = row.is_active ? 'Nonaktifkan' : 'Aktifkan';
    if (!confirm(`${action} voucher ${row.code}?`)) return;
    toggling.value = row.id;
    router.post(`/vouchers/${row.id}/toggle-status`, {}, {
        preserveScroll: true,
        onFinish: () => (toggling.value = null),
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
