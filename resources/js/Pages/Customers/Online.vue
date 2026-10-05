<template>
    <AppLayout title="Pelanggan Online" subtitle="Daftar pelanggan yang sedang terhubung (tanpa voucher)">
        <!-- Summary -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="glass-card px-4 py-3 flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Total Online</p>
                    <p class="text-xl font-bold text-gray-900 leading-tight">{{ totalOnline }}</p>
                </div>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, username, IP, MAC..."
            searchRoute="/customers/online"
        >
            <template #filters>
                <select v-model="filterArea" @change="applyFilters" class="form-select w-44">
                    <option value="">Semua Area</option>
                    <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>
                <select v-model="filterPackage" @change="applyFilters" class="form-select w-44">
                    <option value="">Semua Paket</option>
                    <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </template>

            <template #actions>
                <button id="btn-refresh-online" @click="refresh" class="btn-ghost flex items-center gap-2">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </template>

            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="relative w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-sm font-bold text-white shrink-0">
                            {{ row.name?.charAt(0) }}
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-green-500 border-2 border-white"></span>
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-gray-900 font-medium hover:text-blue-500 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td class="text-gray-700 text-xs font-medium">{{ row.area || '-' }}</td>
                <td>
                    <span v-if="row.package" class="text-xs text-cyan-600 font-medium">{{ row.package }}</span>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td class="font-mono text-xs text-gray-800">{{ row.username || '-' }}</td>
                <td>
                    <span v-if="row.access_mode" :class="['inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase', modeClass(row.access_mode)]">
                        {{ modeLabel(row.access_mode) }}
                    </span>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td class="font-mono text-xs text-gray-700">{{ row.ip_address || '-' }}</td>
                <td class="font-mono text-xs text-gray-700">{{ row.mac_address || '-' }}</td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end">
                    <button
                        :id="`btn-kick-${row.id}`"
                        @click="kickSession(row)"
                        :disabled="kicking === row.username"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-orange-600 bg-orange-50 border border-orange-200 hover:bg-orange-100 hover:shadow transition-all disabled:opacity-50"
                        title="Kick Sesi Online"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ kicking === row.username ? 'Proses...' : 'Kick' }}
                    </button>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    customers: Object,
    areas: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    totalOnline: { type: Number, default: 0 },
});

const columns = [
    { key: 'name', label: 'Nama Pelanggan' },
    { key: 'area', label: 'Area' },
    { key: 'package', label: 'Paket' },
    { key: 'username', label: 'Username PPP/Member' },
    { key: 'access_mode', label: 'Mode Koneksi' },
    { key: 'ip_address', label: 'IP Address' },
    { key: 'mac_address', label: 'MAC Address' },
];

const filterArea = ref(props.filters?.area_id || '');
const filterPackage = ref(props.filters?.package_id || '');
const refreshing = ref(false);
const kicking = ref(null);

function applyFilters() {
    router.get('/customers/online', {
        search: props.filters?.search || undefined,
        area_id: filterArea.value || undefined,
        package_id: filterPackage.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['customers', 'totalOnline'],
        onFinish: () => (refreshing.value = false),
    });
}

function kickSession(row) {
    if (!row.username) return;
    if (!confirm(`Kick sesi online untuk ${row.name} (${row.username})?`)) return;
    kicking.value = row.username;
    router.post('/radius/online-users/disconnect', { username: row.username }, {
        preserveScroll: true,
        onFinish: () => (kicking.value = null),
    });
}

const modeLabels = {
    pppoe: 'PPPoE',
    hotspot: 'Hotspot',
    static_ip: 'Static IP',
    voucher: 'Voucher',
    lainnya: 'Lainnya',
};

function modeLabel(mode) {
    return modeLabels[mode] || mode;
}

function modeClass(mode) {
    switch (mode) {
        case 'pppoe': return 'bg-indigo-100 text-indigo-700';
        case 'hotspot': return 'bg-amber-100 text-amber-700';
        case 'static_ip': return 'bg-sky-100 text-sky-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}
</script>
