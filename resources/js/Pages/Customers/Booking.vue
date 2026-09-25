<template>
    <AppLayout title="Data Booking" subtitle="Calon pelanggan yang baru mendaftar">
        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, telepon..."
            searchRoute="/customers/booking"
        >
            <template #actions>
                <Link href="/customers/create" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Booking Baru
                </Link>
            </template>

            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center text-sm font-bold text-white shrink-0">
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
                <td class="text-gray-400">{{ row.phone }}</td>
                <td class="max-w-[250px] truncate text-gray-400 text-xs">{{ row.address }}</td>
                <td class="text-xs text-gray-500">{{ row.registration_date }}</td>
                <td>
                    <div v-if="row.latitude && row.longitude" class="text-xs font-mono text-cyan-400">
                        {{ Number(row.latitude).toFixed(4) }}, {{ Number(row.longitude).toFixed(4) }}
                    </div>
                    <span v-else class="text-xs text-gray-600">Belum ada</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <Link :href="`/customers/${row.id}`" class="p-2 rounded-lg text-gray-400 hover:bg-white/10 hover:text-blue-400 transition-all" title="Detail">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </Link>
                    <Link :href="`/customers/${row.id}/edit`" class="p-2 rounded-lg text-gray-400 hover:bg-white/10 hover:text-yellow-400 transition-all" title="Proses Survey">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </Link>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

defineProps({ customers: Object, filters: Object });

const columns = [
    { key: 'name', label: 'Nama Pelanggan' },
    { key: 'phone', label: 'Telepon' },
    { key: 'address', label: 'Alamat' },
    { key: 'date', label: 'Tgl Daftar' },
    { key: 'coords', label: 'Koordinat' },
];
</script>
