<template>
    <AppLayout title="Data Survey" subtitle="Proses pengecekan redaman & ketersediaan port ODP">
        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan..."
            searchRoute="/customers/survey"
        >
            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-white shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-white font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500">{{ row.address }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span v-if="row.surveys?.length" class="text-xs text-cyan-400 font-mono">
                        {{ row.surveys[0]?.odp?.name || '-' }}
                    </span>
                    <span v-else class="text-xs text-gray-600">Belum survey</span>
                </td>
                <td>
                    <span v-if="row.surveys?.length" :class="signalClass(row.surveys[0]?.signal_loss)" class="text-sm font-mono font-bold">
                        {{ row.surveys[0]?.signal_loss ? `${row.surveys[0].signal_loss} dBm` : '-' }}
                    </span>
                    <span v-else class="text-gray-600">-</span>
                </td>
                <td>
                    <span v-if="row.surveys?.length && row.surveys[0]?.port_available" class="badge badge-active">Tersedia</span>
                    <span v-else-if="row.surveys?.length" class="badge badge-terminated">Penuh</span>
                    <span v-else class="text-gray-600 text-xs">-</span>
                </td>
                <td>
                    <StatusBadge v-if="row.surveys?.length" :status="row.surveys[0]?.feasibility || 'feasible'" />
                    <span v-else class="text-gray-600 text-xs">-</span>
                </td>
                <td class="text-xs text-gray-400">{{ row.surveys?.[0]?.surveyor?.name || '-' }}</td>
            </template>

            <template #rowActions="{ row }">
                <Link :href="`/customers/${row.id}`" class="btn-ghost text-xs">Detail</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({ customers: Object, availableOdps: Array, filters: Object });

const columns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'odp', label: 'ODP Terdekat' },
    { key: 'signal', label: 'Redaman' },
    { key: 'port', label: 'Port ODP' },
    { key: 'feasibility', label: 'Kelayakan' },
    { key: 'surveyor', label: 'Surveyor' },
];

function signalClass(val) {
    if (!val) return 'text-gray-500';
    const v = Math.abs(parseFloat(val));
    if (v <= 20) return 'text-emerald-400';
    if (v <= 25) return 'text-green-400';
    if (v <= 28) return 'text-yellow-400';
    return 'text-red-400';
}
</script>
