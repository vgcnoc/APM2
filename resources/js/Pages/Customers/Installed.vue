<template>
    <AppLayout title="Pelanggan Terpasang" subtitle="Pelanggan yang sedang/sudah diinstalasi">
        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan aktif..."
            searchRoute="/customers/installed"
        >
            <template #filters>
                <select v-model="status" @change="applyFilter" class="form-select w-40">
                    <option value="">Semua</option>
                    <option value="installing">Proses Pasang</option>
                    <option value="active">Aktif</option>
                </select>
            </template>

            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center text-sm font-bold text-white shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-white font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-xs text-cyan-400 font-medium">{{ row.package?.name || '-' }}</span>
                </td>
                <td><StatusBadge :status="row.status" /></td>
                <td>
                    <span v-if="row.ont" class="text-xs font-mono text-gray-300">{{ row.ont.serial_number }}</span>
                    <span v-else class="text-xs text-gray-600">-</span>
                </td>
                <td>
                    <template v-if="row.ont?.odp?.odc?.olt">
                        <div class="text-xs space-y-0.5">
                            <p class="text-gray-400">{{ row.ont.odp.odc.olt.name }}</p>
                            <p class="text-gray-500">→ {{ row.ont.odp.odc.name }} → {{ row.ont.odp.name }}</p>
                        </div>
                    </template>
                    <span v-else class="text-xs text-gray-600">-</span>
                </td>
                <td>
                    <span v-if="row.ont?.rx_power" :class="signalClass(row.ont.rx_power)" class="text-sm font-mono font-bold">
                        {{ row.ont.rx_power }} dBm
                    </span>
                    <span v-else class="text-gray-600">-</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <Link :href="`/customers/${row.id}`" class="p-2 rounded-lg text-gray-400 hover:bg-white/10 hover:text-blue-400 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </Link>
            </template>
        </DataTable>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({ customers: Object, filters: Object });

const columns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'package', label: 'Paket' },
    { key: 'status', label: 'Status' },
    { key: 'ont', label: 'ONT S/N' },
    { key: 'topology', label: 'Topologi' },
    { key: 'signal', label: 'Redaman' },
];

const status = ref(props.filters?.status || '');

function applyFilter() {
    router.get('/customers/installed', { status: status.value || undefined }, { preserveState: true });
}

function signalClass(rx) {
    if (!rx) return 'text-gray-500';
    if (rx >= -20) return 'text-emerald-400';
    if (rx >= -25) return 'text-green-400';
    if (rx >= -28) return 'text-yellow-400';
    return 'text-red-400';
}
</script>
