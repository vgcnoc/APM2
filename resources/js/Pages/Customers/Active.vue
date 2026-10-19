<template>
    <AppLayout title="Pelanggan Aktif" subtitle="Daftar seluruh pelanggan dengan layanan aktif">
        
        <!-- Statistik Cards -->
        <div v-can="'customers_card_summary'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard 
                title="Total Pelanggan Aktif" 
                :value="stats?.total || stats?.aktif || 0" 
                icon="M5 13l4 4L19 7" 
                color="emerald" 
            />
            <StatCard 
                title="Status Online" 
                :value="stats?.online || 0" 
                icon="M13 10V3L4 14h7v7l9-11h-7z" 
                color="green" 
            />
            <StatCard 
                title="Status Offline" 
                :value="stats?.offline || 0" 
                icon="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" 
                color="red" 
            />
            <StatCard 
                title="Estimasi Omset Bulanan" 
                :value="formatRupiah(stats?.total_omset || 0)" 
                icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" 
                color="blue" 
            />
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan aktif..."
            searchRoute="/customers/active"
            selectable
            v-model:selected="selectedIds"
        >
            <template #filters>
                <div class="flex flex-wrap items-center gap-2.5 w-full">
                    <!-- Filter Area -->
                    <select v-model="filterArea" @change="applyFilters" class="form-select w-full sm:w-44 md:w-48 text-sm bg-white">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="area.id || area" :value="area.id || area">
                            {{ area.name || area }}
                        </option>
                    </select>

                    <!-- Filter Paket -->
                    <select v-model="filterPackage" @change="applyFilters" class="form-select w-full sm:w-44 md:w-48 text-sm bg-white">
                        <option value="">Semua Paket</option>
                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                            {{ pkg.name }}
                        </option>
                    </select>

                    <!-- Filter Status Koneksi -->
                    <select v-model="filterConnection" @change="applyFilters" class="form-select w-full sm:w-44 md:w-48 text-sm bg-white">
                        <option value="">Semua Koneksi</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                    </select>

                    <button v-if="filterArea || filterPackage || filterConnection" @click="resetFilters" class="px-3.5 py-2 bg-gray-50 text-gray-600 hover:bg-gray-100 font-medium text-sm rounded-lg transition-colors border border-gray-200 whitespace-nowrap">
                        Reset
                    </button>
                </div>
            </template>

            <template #actions>
                <div class="flex items-center gap-2">
                    <button v-can="'customers_delete'" v-if="canDelete && selectedIds.length > 0" 
                        @click="bulkDelete" 
                        class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-lg transition-colors border border-red-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Terpilih ({{ selectedIds.length }})
                    </button>
                </div>
            </template>
            
            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <div :class="[
                                'w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-sm',
                                isCustomerOnline(row)
                                    ? 'bg-gradient-to-br from-emerald-500 to-green-600 ring-2 ring-emerald-400/60'
                                    : 'bg-gradient-to-br from-rose-500 to-red-600 ring-2 ring-rose-400/60'
                            ]">
                                {{ row.name.charAt(0) }}
                            </div>
                            <span v-if="isCustomerOnline(row)" class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white ring-1 ring-emerald-300"></span>
                            <span v-else class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-rose-500 border-2 border-white ring-1 ring-rose-300"></span>
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" 
                                :class="[
                                    'font-semibold transition-colors inline-flex items-center gap-1.5',
                                    isCustomerOnline(row) 
                                        ? 'text-emerald-600 hover:text-emerald-700 font-bold' 
                                        : 'text-rose-600 hover:text-rose-700 font-bold'
                                ]">
                                <span v-if="isCustomerOnline(row)" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                                <span v-else class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                                {{ row.name }}
                            </Link>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span :class="isCustomerOnline(row) ? 'text-emerald-700/80 font-medium' : 'text-rose-700/80 font-medium'" class="text-xs font-mono">{{ row.customer_code }}</span>
                                <span v-if="row.registration_date" class="text-[10px] text-indigo-700 bg-indigo-50 border border-indigo-100/70 px-1.5 py-0.5 rounded font-medium" title="Tanggal Daftar">
                                    📅 {{ formatDate(row.registration_date) }}
                                </span>
                                <span v-if="row.areaModel?.name || row.area" class="text-[11px] px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded">
                                    {{ row.areaModel?.name || row.area }}
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-xs text-indigo-600 font-semibold">{{ row.package?.name || '-' }}</span>
                        <span class="text-[11px] text-gray-400 font-mono">
                            {{ formatRupiah(row.base_amount > 0 ? row.base_amount : (row.package?.price || 0)) }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="flex flex-col gap-1 items-start">
                        <div v-if="row.ont?.pppoe_user || row.ont?.hotspot_user">
                            <span v-if="isCustomerOnline(row)" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Offline
                            </span>
                            <span class="block text-[11px] text-gray-400 font-mono mt-0.5">
                                <span v-if="row.ont.hotspot_user && !row.ont.pppoe_user" class="text-[9px] font-bold text-amber-600 bg-amber-50 px-1 py-0.2 rounded border border-amber-200 mr-1">HOTSPOT</span>
                                {{ row.ont.hotspot_user && !row.ont.pppoe_user ? row.ont.hotspot_user : row.ont.pppoe_user }}
                            </span>
                        </div>
                        <span v-else class="text-xs text-gray-400 italic">No Account</span>
                    </div>
                </td>
                <td>
                    <div v-if="row.ont">
                        <span class="text-xs font-mono font-medium text-gray-700 block">{{ row.ont.serial_number }}</span>
                        <span class="text-[11px] text-gray-400">{{ row.ont.brand }} {{ row.ont.model }}</span>
                    </div>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td>
                    <template v-if="row.ont?.odp">
                        <div class="text-xs space-y-0.5">
                            <p v-if="row.ont.odp.odc?.olt" class="text-gray-400 text-[11px]">{{ row.ont.odp.odc.olt.name }}</p>
                            <p class="text-gray-700 font-medium">
                                <span v-if="row.ont.odp.odc" class="text-gray-400">{{ row.ont.odp.odc.name }} &rarr; </span>
                                {{ row.ont.odp.name }}
                            </p>
                        </div>
                    </template>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td>
                    <span v-if="row.ont?.rx_power" :class="signalClass(row.ont.rx_power)" class="text-sm font-mono font-bold">
                        {{ row.ont.rx_power }} dBm
                    </span>
                    <span v-else class="text-gray-400">-</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1.5">
                    <!-- Kick Button jika online -->
                    <button v-if="row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user)" 
                        @click="kickSession(row.ont.pppoe_user)" 
                        class="p-1.5 rounded-lg text-gray-400 hover:bg-orange-50 hover:text-orange-600 border border-transparent hover:border-orange-200 transition-all shadow-sm hover:shadow" 
                        title="Kick / Disconnect Sesi Online">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </button>

                    <Link :href="`/customers/${row.id}`" class="p-1.5 rounded-lg text-gray-400 hover:bg-white hover:text-blue-600 border border-transparent hover:border-gray-200 transition-all shadow-sm hover:shadow" title="Detail Lengkap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </Link>
                    
                    <button v-can="'customers_delete'" @click="confirmDelete(row)" class="p-1.5 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 border border-transparent hover:border-red-200 transition-all shadow-sm hover:shadow" title="Hapus Pelanggan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Konfirmasi Hapus -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
                <div class="p-6">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Konfirmasi Hapus</h3>
                    <p class="text-sm text-gray-500 text-center mb-6">
                        Yakin ingin menghapus pelanggan <strong class="text-gray-900">{{ deleteCustomer?.name }}</strong>? Data yang dihapus tidak dapat dipulihkan.
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" class="flex-1 py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button @click="executeDelete" :disabled="isDeleting" class="flex-1 py-2.5 px-4 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <span v-if="isDeleting">Menghapus...</span>
                            <span v-else>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';

const props = defineProps({
    customers: Object,
    stats: Object,
    packages: Array,
    areas: Array,
    filters: Object,
    onlineUsernames: Array,
});

const page = usePage();
const selectedIds = ref([]);

const filterArea = ref(props.filters?.area_id || props.filters?.area || '');
const filterPackage = ref(props.filters?.package_id || '');
const filterConnection = ref(props.filters?.connection_status || '');

function applyFilters() {
    router.get('/customers/active', {
        search: props.filters?.search || undefined,
        area_id: filterArea.value || undefined,
        package_id: filterPackage.value || undefined,
        connection_status: filterConnection.value || undefined,
    }, { preserveState: true, replace: true });
}

function resetFilters() {
    filterArea.value = '';
    filterPackage.value = '';
    filterConnection.value = '';
    router.get('/customers/active', {}, { preserveState: true, replace: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const clean = String(dateStr).split(' ')[0];
        const parts = clean.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return clean;
    } catch (e) {
        return dateStr;
    }
}

function formatRupiah(val) {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
}

function isCustomerOnline(row) {
    const pppoe = row.ont?.pppoe_user;
    const hotspot = row.ont?.hotspot_user;
    return (pppoe && props.onlineUsernames?.includes(pppoe)) || (hotspot && props.onlineUsernames?.includes(hotspot));
}

function signalClass(rx) {
    if (!rx) return 'text-gray-400';
    if (rx >= -20) return 'text-emerald-500';
    if (rx >= -25) return 'text-green-500';
    if (rx >= -28) return 'text-amber-500';
    return 'text-rose-500';
}

const canDelete = computed(() => {
    try {
        const user = page.props?.auth?.user;
        if (!user) return false;
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) return true;
        }
        let perms = user.permissions || [];
        if (!Array.isArray(perms) && typeof perms === 'object') perms = Object.values(perms);
        return perms.includes('customers_delete') || perms.includes('menu_customers_all');
    } catch (e) {
        return false;
    }
});

const columns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'package', label: 'Paket Layanan' },
    { key: 'connection', label: 'Koneksi' },
    { key: 'ont', label: 'Perangkat ONT' },
    { key: 'topology', label: 'Topologi Jaringan' },
    { key: 'signal', label: 'Redaman' },
];

function kickSession(username) {
    if (confirm(`Kick sesi RADIUS untuk pengguna ${username}?`)) {
        router.post('/radius/online-users/disconnect', { username }, {
            preserveScroll: true
        });
    }
}

// Delete Logic
const showDeleteModal = ref(false);
const deleteCustomer = ref(null);
const isDeleting = ref(false);

function confirmDelete(customer) {
    deleteCustomer.value = customer;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!deleteCustomer.value) return;
    isDeleting.value = true;
    router.post(`/customers/${deleteCustomer.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteCustomer.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
}

function bulkDelete() {
    if (confirm(`Hapus ${selectedIds.value.length} data terpilih secara permanen?`)) {
        router.post('/customers/bulk-destroy', { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => selectedIds.value = []
        });
    }
}
</script>
