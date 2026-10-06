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

        <div class="mb-4 bg-white border border-gray-200 rounded-lg p-1 inline-flex shadow-sm">
            <button @click="filterTab('')" :class="['px-4 py-1.5 text-sm font-semibold rounded-md transition-colors', !filterMode ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">Semua</button>
            <button @click="filterTab('pppoe')" :class="['px-4 py-1.5 text-sm font-semibold rounded-md transition-colors', filterMode === 'pppoe' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">PPPoE</button>
            <button @click="filterTab('hotspot')" :class="['px-4 py-1.5 text-sm font-semibold rounded-md transition-colors', filterMode === 'hotspot' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">Hotspot</button>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, username, IP, MAC..."
            searchRoute="/customers/online"
            :filters="filters"
        >
            <template #filters>
                <!-- Area -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[150px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Area</span>
                        <select v-model="filterArea" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                            <option value="">Semua Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Paket -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[150px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Paket</span>
                        <select v-model="filterPackage" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                            <option value="">Semua Paket</option>
                            <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>
            </template>

            <template #actions>
                <button id="btn-refresh-online" @click="refresh" class="btn-secondary bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium text-sm py-2 px-4 rounded-lg shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </template>

            <template #row="{ row, index }">
                <td class="text-gray-500 text-xs text-center">
                    {{ (customers.current_page - 1) * customers.per_page + index + 1 }}
                </td>
                <td>
                    <div class="flex items-center gap-3">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0', getAvatarColor(row.name)]">
                            {{ row.name?.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <Link :href="`/customers/${row.id}`" class="text-gray-900 font-semibold text-sm hover:text-blue-500 transition-colors">
                                    {{ row.name }}
                                </Link>
                                <button
                                    :id="`btn-kick-${row.id}`"
                                    @click="kickSession(row)"
                                    :disabled="kicking === row.username"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition-colors disabled:opacity-50 border border-red-100"
                                    title="Kick Sesi Online"
                                >
                                    <svg class="w-3 h-3" :class="{'animate-pulse': kicking === row.username}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    {{ kicking === row.username ? 'PROSES' : 'KICK' }}
                                </button>
                            </div>
                            <p class="text-[10px] text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span v-if="row.area" class="px-2 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded text-[10px] font-bold whitespace-nowrap uppercase">{{ row.area }}</span>
                    <span v-else class="px-2 py-1 bg-gray-50 text-gray-500 border border-gray-100 rounded text-[10px] font-bold whitespace-nowrap uppercase">-</span>
                </td>
                <td>
                    <div v-if="row.package" class="flex items-center gap-1.5 text-blue-500 text-xs font-bold whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                        {{ row.package }}
                    </div>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td>
                    <span class="inline-flex px-2 py-1 bg-gray-50 text-gray-700 border border-gray-200 rounded text-xs font-mono font-medium">{{ row.username || '-' }}</span>
                </td>
                <td>
                    <span v-if="row.access_mode" :class="['inline-flex px-2 py-1 border rounded text-[10px] font-bold uppercase whitespace-nowrap', modeClass(row.access_mode)]">
                        {{ modeLabel(row.access_mode) }}
                    </span>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td class="font-mono text-xs text-gray-700">{{ row.ip_address || '-' }}</td>
                <td class="font-mono text-xs text-gray-700">{{ row.mac_address || '-' }}</td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-xs text-emerald-600 font-medium whitespace-nowrap">Up: {{ formatUptime(row.session_time) }}</span>
                        <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ formatDate(row.start_time) }}</span>
                    </div>
                </td>
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
    { key: 'index', label: '#' },
    { key: 'name', label: 'PELANGGAN' },
    { key: 'area', label: 'AREA' },
    { key: 'package', label: 'PAKET' },
    { key: 'username', label: 'USERNAME PPP' },
    { key: 'access_mode', label: 'MODE' },
    { key: 'ip_address', label: 'IP ADDRESS' },
    { key: 'mac_address', label: 'MAC ADDRESS' },
    { key: 'uptime', label: 'UPTIME / LOGIN' },
];

function getAvatarColor(name) {
    if (!name) return 'bg-gray-500';
    const colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-pink-500', 'bg-orange-500'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

const filterArea = ref(props.filters?.area_id || '');
const filterPackage = ref(props.filters?.package_id || '');
const filterMode = ref(props.filters?.tab || '');
const refreshing = ref(false);
const kicking = ref(null);

function applyFilters() {
    router.get('/customers/online', {
        search: props.filters?.search || undefined,
        area_id: filterArea.value || undefined,
        package_id: filterPackage.value || undefined,
        tab: filterMode.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function filterTab(tab) {
    filterMode.value = tab;
    applyFilters();
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
        case 'pppoe': return 'bg-indigo-50 text-indigo-600 border-indigo-200';
        case 'hotspot': return 'bg-amber-50 text-amber-600 border-amber-200';
        case 'static_ip': return 'bg-sky-50 text-sky-600 border-sky-200';
        default: return 'bg-gray-50 text-gray-600 border-gray-200';
    }
}

function formatUptime(seconds) {
    if (!seconds) return '0s';
    const d = Math.floor(seconds / (3600*24));
    const h = Math.floor(seconds % (3600*24) / 3600);
    const m = Math.floor(seconds % 3600 / 60);
    const s = Math.floor(seconds % 60);
    
    let parts = [];
    if (d > 0) parts.push(`${d}d`);
    if (h > 0) parts.push(`${h}h`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 || parts.length === 0) parts.push(`${s}s`);
    
    return parts.join(' ');
}

function formatDate(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
}
</script>
