<template>
    <AppLayout title="Data Pelanggan" subtitle="Kelola semua data pelanggan ISP">
        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, kode, telepon..."
            searchRoute="/customers"
        >
            <!-- Filter Slot -->
            <template #filters>
                <select
                    v-model="filterStatus"
                    @change="applyFilters"
                    class="form-select w-40"
                >
                    <option value="">Semua Status</option>
                    <option v-for="(label, key) in statusOptions" :key="key" :value="key">
                        {{ label }}
                    </option>
                </select>

                <select
                    v-model="filterPackage"
                    @change="applyFilters"
                    class="form-select w-44"
                >
                    <option value="">Semua Paket</option>
                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                        {{ pkg.name }}
                    </option>
                </select>
            </template>

            <!-- Action Button -->
            <template #actions>
                <Link href="/customers/create" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Pelanggan
                </Link>
            </template>

            <!-- Table Rows -->
            <template #row="{ row, index }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-sm font-bold text-gray-900 shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-gray-900 font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td class="text-gray-500">{{ row.phone }}</td>
                <td class="max-w-[200px] truncate text-gray-500 text-xs">{{ row.address }}</td>
                <td>
                    <span v-if="row.package" class="text-xs text-cyan-400 font-medium">
                        {{ row.package.name }}
                    </span>
                    <span v-else class="text-xs text-gray-600">-</span>
                </td>
                <td><StatusBadge :status="row.status" /></td>
                <td>
                    <span v-if="row.ont" class="text-xs font-mono text-emerald-400">
                        {{ row.ont.odp?.name || '-' }}
                    </span>
                    <span v-else class="text-xs text-gray-600">Belum terpasang</span>
                </td>
            </template>

            <!-- Row Actions -->
            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <Link :href="`/customers/${row.id}`" class="p-2 rounded-lg text-gray-500 hover:bg-white hover:text-blue-400 transition-all" title="Lihat Detail">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </Link>
                    <Link :href="`/customers/${row.id}/edit`" class="p-2 rounded-lg text-gray-500 hover:bg-white hover:text-yellow-400 transition-all" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </Link>
                    <button @click="confirmDelete(row)" class="p-2 rounded-lg text-gray-500 hover:bg-red-500/10 hover:text-red-400 transition-all" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative glass-card p-6 max-w-md w-full animate-fade-in-up">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Hapus Pelanggan?</h3>
                            <p class="text-sm text-gray-500">{{ deletingCustomer?.name }} ({{ deletingCustomer?.customer_code }})</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">
                        Data pelanggan beserta ONT yang terhubung akan dihapus. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="showDeleteModal = false" class="btn-ghost">Batal</button>
                        <button @click="deleteCustomer" class="btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus Permanen
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    customers: Object,
    packages: Array,
    filters: Object,
    statusOptions: Object,
});

const columns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'phone', label: 'Telepon' },
    { key: 'address', label: 'Alamat' },
    { key: 'package', label: 'Paket' },
    { key: 'status', label: 'Status' },
    { key: 'odp', label: 'ODP' },
];

const filterStatus = ref(props.filters?.status || '');
const filterPackage = ref(props.filters?.package_id || '');

function applyFilters() {
    router.get('/customers', {
        status: filterStatus.value || undefined,
        package_id: filterPackage.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

// ── Delete Modal ───────────────────────────────────────────
const showDeleteModal = ref(false);
const deletingCustomer = ref(null);

function confirmDelete(customer) {
    deletingCustomer.value = customer;
    showDeleteModal.value = true;
}

function deleteCustomer() {
    router.post(`/customers/${deletingCustomer.value.id}/delete`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingCustomer.value = null;
        },
    });
}
</script>
