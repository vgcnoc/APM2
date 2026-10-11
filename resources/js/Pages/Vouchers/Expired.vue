<template>
    <component :is="layoutComponent" title="Voucher Expired" subtitle="Daftar voucher yang sudah habis masa aktifnya / kedaluwarsa">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Voucher Expired</h1>
            <p class="text-sm text-gray-500">Daftar voucher yang sudah habis masa aktifnya / kedaluwarsa</p>
        </div>

        <!-- Summary & Actions Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <div class="flex flex-wrap items-center gap-3">
                <div class="glass-card px-4 py-3 flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    <div>
                        <p class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Total Voucher Expired</p>
                        <p class="text-xl font-bold text-gray-900 leading-tight">{{ expiredUsers.length }}</p>
                    </div>
                </div>
            </div>

            <!-- Header Quick Action: Bulk Delete if any selected -->
            <div class="flex items-center gap-2">
                <button 
                    v-if="selectedVouchers.length > 0" 
                    @click="bulkDelete" 
                    class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 shadow-sm flex items-center gap-2 transition-all animate-fade-in-up"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus Terpilih ({{ selectedVouchers.length }})
                </button>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="paginatedData.data"
            :pagination="paginatedData"
            searchPlaceholder="Cari kode, user, profil, reseller..."
            @search="handleSearch"
            :clientSidePagination="true"
            :selectable="true"
            v-model:selected="selectedVouchers"
        >
            <template #filters>
                <div class="flex flex-wrap items-center gap-2 w-full">
                    <select v-model="profileFilter" class="border-gray-200 rounded-lg text-xs font-medium py-1.5 px-3 bg-white shadow-2xs">
                        <option value="">Semua Profil</option>
                        <option v-for="prof in profiles" :key="prof.id" :value="prof.name">{{ prof.name }}</option>
                    </select>

                    <select v-if="layoutComponent === AppLayout" v-model="resellerFilter" class="border-gray-200 rounded-lg text-xs font-medium py-1.5 px-3 bg-white shadow-2xs">
                        <option value="">Semua Pemilik / Reseller</option>
                        <option value="Kantor / System">🏢 Kantor / System</option>
                        <option v-for="res in resellers" :key="res.id" :value="res.customer?.name || ('Reseller #' + res.id)">
                            👤 {{ res.customer?.name || ('Reseller #' + res.id) }}
                        </option>
                    </select>
                </div>
            </template>

            <template #actions>
                <button id="btn-refresh-expired" @click="refresh" class="btn-secondary bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium text-sm py-2 px-4 rounded-lg shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </template>

            <template #row="{ row, index }">
                <td class="text-gray-500 text-xs text-center font-mono">
                    {{ (paginatedData.current_page - 1) * paginatedData.per_page + index + 1 }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-col items-start gap-1">
                        <span class="inline-flex px-2 py-0.5 bg-gray-100 text-gray-900 border border-gray-200 rounded text-xs font-mono font-bold tracking-wider">
                            {{ row.code }}
                        </span>
                        <div v-if="row.username && row.username !== row.code" class="text-[11px] text-gray-500 font-mono">
                            User: {{ row.username }}
                        </div>
                        <span v-if="row.is_free" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            🎁 Gratis
                        </span>
                        <span v-else class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                            💰 Berbayar
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700 font-mono">
                    {{ row.password || '-' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-bold text-xs text-gray-900">{{ row.profile || '-' }}</div>
                    <div v-if="row.duration" class="text-[11px] text-gray-500">{{ row.duration }}</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex flex-col">
                        <span class="text-xs font-semibold text-gray-900">{{ row.reseller }}</span>
                        <span v-if="row.area" class="inline-flex items-center gap-1 text-[11px] text-gray-500 mt-0.5">
                            <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ row.area }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">
                    <div v-if="row.first_login">{{ formatDate(row.first_login) }}</div>
                    <div v-else class="text-gray-400">-</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs">
                    <div class="flex flex-col">
                        <span class="text-xs text-red-600 font-semibold">{{ formatDate(row.expired_at || row.used_at) || '-' }}</span>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 mt-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 w-fit border border-rose-200">
                            Masa Aktif Habis
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">
                    <div v-if="row.total_time !== undefined">
                        <span class="font-bold text-slate-800">{{ formatSeconds(row.total_time) }}</span>
                        <span v-if="row.duration" class="text-gray-400 text-[11px] ml-1">/ {{ row.duration }}</span>
                    </div>
                    <div v-else class="text-gray-400">-</div>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end">
                    <button
                        :id="`btn-delete-${row.id}`"
                        @click="deleteVoucher(row)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold transition-colors disabled:opacity-50 text-red-600 bg-red-50 hover:bg-red-500 hover:text-white"
                        title="Hapus Voucher"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        HAPUS
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
import DataTable from '@/Components/DataTable.vue';
import Swal from 'sweetalert2';

const page = usePage();
const layoutComponent = computed(() => {
    return page.props.auth?.user?.role === 'reseller' || page.props.auth?.user?.role === 'customer' 
        ? ClientAreaLayout 
        : AppLayout;
});

const props = defineProps({
    expiredUsers: { type: Array, default: () => [] },
    profiles: { type: Array, default: () => [] },
    resellers: { type: Array, default: () => [] },
});

const columns = [
    { key: 'index', label: '#' },
    { key: 'code', label: 'KODE / USER' },
    { key: 'password', label: 'PASSWORD' },
    { key: 'profile', label: 'PROFIL' },
    { key: 'reseller', label: 'PEMILIK / AREA' },
    { key: 'first_login', label: 'TERPAKAI SEJAK' },
    { key: 'expired_at', label: 'WAKTU HABIS' },
    { key: 'total_time', label: 'PENGGUNAAN' },
];

const refreshing = ref(false);
const search = ref('');
const profileFilter = ref('');
const resellerFilter = ref('');
const selectedVouchers = ref([]);
const currentPage = ref(1);
const perPage = 15;

const filteredData = computed(() => {
    let result = props.expiredUsers;

    if (search.value) {
        const query = search.value.toLowerCase();
        result = result.filter(v => 
            (v.code && v.code.toLowerCase().includes(query)) ||
            (v.username && v.username.toLowerCase().includes(query)) ||
            (v.profile && v.profile.toLowerCase().includes(query)) ||
            (v.reseller && v.reseller.toLowerCase().includes(query)) ||
            (v.area && v.area.toLowerCase().includes(query))
        );
    }

    if (profileFilter.value) {
        result = result.filter(v => v.profile === profileFilter.value);
    }

    if (resellerFilter.value) {
        result = result.filter(v => v.reseller === resellerFilter.value);
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
        links: []
    };
});

function handleSearch(val) {
    search.value = val;
    currentPage.value = 1;
}

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['expiredUsers'],
        onFinish: () => (refreshing.value = false),
    });
}

function deleteVoucher(row) {
    Swal.fire({
        title: `Hapus Voucher ${row.code}?`,
        text: 'Voucher yang dihapus tidak dapat dipulihkan kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/vouchers/${row.id}/delete`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
}

function bulkDelete() {
    if (selectedVouchers.value.length === 0) return;
    Swal.fire({
        title: `Hapus ${selectedVouchers.value.length} Voucher Expired?`,
        text: 'Semua voucher yang dipilih akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.bulk-destroy'), { ids: selectedVouchers.value }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedVouchers.value = [];
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
}

function formatDate(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
}

function formatSeconds(seconds) {
    if (!seconds) return '0 mnt';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h} jam ${m} mnt`;
    return `${m} mnt`;
}
</script>
