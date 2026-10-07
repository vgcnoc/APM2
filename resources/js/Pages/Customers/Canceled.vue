<template>
    <AppLayout title="Data Pembatalan" subtitle="Pelanggan yang dibatalkan">
        
        <!-- Statistik -->
        <div class="grid grid-cols-1 gap-6 mb-6">
            <div class="glass-card p-6 animate-fade-in-up" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Dibatalkan</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.total }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <DataTable
            :data="customers.data"
            :columns="columns"
            :pagination="customers"
            :selectable="canDelete"
            @selection-change="selectedIds = $event"
        >
            <template #toolbar>
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="filterSearch"
                            @keyup.enter="applyFilters"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-red-500 focus:border-red-500 sm:text-sm transition-all"
                            placeholder="Cari nama, kode..."
                        >
                    </div>
                    <button @click="applyFilters" class="p-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl transition-colors" title="Terapkan Filter">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    </button>
                    
                    <button v-if="hasFilters" @click="resetFilters" class="p-2 bg-gray-50 text-gray-500 hover:bg-gray-100 rounded-xl transition-colors" title="Reset Filter">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>

            <template #bulkActions v-if="canDelete">
                <button 
                    @click="showDeleteAllModal = true" 
                    class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-xl transition-colors flex items-center shadow-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus {{ selectedIds.length }} Data
                </button>
            </template>

            <template #cell(name)="{ row }">
                <div>
                    <div class="font-bold text-gray-900">{{ row.name }}</div>
                    <div class="text-xs font-mono text-blue-500 mt-0.5">{{ row.customer_code }}</div>
                </div>
            </template>

            <template #cell(phone)="{ row }">
                <div class="text-sm text-gray-600">{{ row.phone }}</div>
            </template>

            <template #cell(address)="{ row }">
                <div class="text-sm text-gray-600 max-w-[200px] truncate" :title="row.address">
                    {{ row.address }}
                </div>
            </template>

            <template #cell(cancel_reason)="{ row }">
                <div class="text-sm text-red-600 max-w-[250px] whitespace-pre-wrap break-words" :title="row.cancel_reason">
                    {{ row.cancel_reason || '-' }}
                </div>
            </template>

            <template #cell(date)="{ row }">
                <td class="text-xs text-gray-500">{{ row.updated_at ? new Date(row.updated_at).toLocaleDateString('id-ID') : '-' }}</td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button v-if="canDelete" 
                        @click="confirmDelete(row)" 
                        class="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-500 transition-all" title="Hapus Data">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Konfirmasi Hapus Semua Data -->
        <Teleport to="body">
            <div v-if="showDeleteAllModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-red-900/60 backdrop-blur-sm cursor-pointer" @click="showDeleteAllModal = false"></div>
                <div class="relative bg-white border border-red-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-red-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Hapus {{ selectedIds.length }} Data Terpilih?
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Data terpilih akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteAllModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">Batal</button>
                        <button @click="deleteAllBooking" :disabled="isDeletingAll" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-red-200">
                            <svg v-if="isDeletingAll" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Hapus Semua</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ customers: Object, filters: Object, stats: Object });
const page = usePage();

const selectedIds = ref([]);

const canDelete = computed(() => {
    try {
        if (!page || !page.props || !page.props.auth || !page.props.auth.user) return false;
        
        const user = page.props.auth.user;
        
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) {
                return true;
            }
        }
        
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        for (let r of roles) {
            if (typeof r === 'string') {
                const rStr = r.toLowerCase().trim();
                if (rStr === 'admin' || rStr === 'super admin' || rStr.includes('admin')) return true;
            }
        }
        
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        return perms.includes('menu_customers_booking') || perms.includes('customers_booking_delete') || perms.includes('customers_delete');
    } catch (e) {
        console.error("Auth check error:", e);
        return false;
    }
});

const filterSearch = ref(props.filters?.search || '');

const hasFilters = computed(() => {
    return filterSearch.value !== '';
});

function applyFilters() {
    router.get('/customers/canceled', {
        search: filterSearch.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterSearch.value = '';
    router.get('/customers/canceled', {}, { preserveState: true, preserveScroll: true });
}

const columns = [
    { key: 'name', label: 'Nama Pelanggan' },
    { key: 'phone', label: 'Telepon' },
    { key: 'address', label: 'Alamat' },
    { key: 'cancel_reason', label: 'Alasan Batal' },
    { key: 'date', label: 'Tgl Batal' },
];

function confirmDelete(row) {
    if (confirm('Yakin ingin menghapus data pembatalan secara permanen?')) {
        router.delete(`/customers/${row.id}`, {
            preserveScroll: true
        });
    }
}

const showDeleteAllModal = ref(false);
const isDeletingAll = ref(false);

function deleteAllBooking() {
    if (selectedIds.value.length === 0) return;
    
    isDeletingAll.value = true;
    router.post('/customers/bulk-destroy', {
        ids: selectedIds.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteAllModal.value = false;
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeletingAll.value = false;
        }
    });
}
</script>
