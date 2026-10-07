<template>
    <AppLayout title="Master Fee">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Master Fee</h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola fee booking, survey & pemasangan</p>
                </div>
                <div class="flex gap-2">
                    <button @click="openModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-bold text-sm text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Tambah
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Booking</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.booking }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Survey</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.survey }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Pasang</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.pasang }}</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-4 flex flex-col sm:flex-row justify-between gap-4">
                <div class="relative w-full sm:max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" v-model="filter.search" @keyup.enter="fetchData" placeholder="Cari nama..." class="block w-full pl-10 border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-4 overflow-x-auto pb-2">
                <nav class="flex space-x-2 whitespace-nowrap bg-gray-100 p-1 rounded-xl w-fit" aria-label="Tabs">
                    <button @click="setTab('semua')" :class="[filter.tab === 'semua' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200/50', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Semua ({{ stats.total }})</button>
                    <button @click="setTab('Fee Booking')" :class="[filter.tab === 'Fee Booking' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200/50', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Fee Booking ({{ stats.booking }})</button>
                    <button @click="setTab('Fee Survey')" :class="[filter.tab === 'Fee Survey' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200/50', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Fee Survey ({{ stats.survey }})</button>
                    <button @click="setTab('Fee Pasang')" :class="[filter.tab === 'Fee Pasang' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200/50', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Fee Pasang ({{ stats.pasang }})</button>
                    <button @click="setTab('Fee Freelance per Paket')" :class="[filter.tab === 'Fee Freelance per Paket' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200/50', 'px-4 py-2 text-sm font-bold rounded-lg transition-all flex items-center']"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>Fee Freelance per Paket</button>
                    <button @click="setTab('Bonus Target Booking')" :class="[filter.tab === 'Bonus Target Booking' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200/50', 'px-4 py-2 text-sm font-bold rounded-lg transition-all flex items-center']"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>Bonus Target Booking</button>
                </nav>
            </div>

            <!-- Table -->
            <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Nama</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Nominal</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Mode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Aktif</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="fee in fees.data" :key="fee.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900 uppercase">{{ fee.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getTypeBadgeClass(fee.type)" class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full">
                                        {{ fee.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                    Rp {{ formatCurrency(fee.nominal) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[fee.mode === 'Auto' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-800', 'px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full items-center']">
                                        <svg v-if="fee.mode === 'Auto'" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        {{ fee.mode }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">{{ fee.description || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="toggleActive(fee)" :class="[fee.is_active ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500']">
                                        <span class="sr-only">Toggle Active</span>
                                        <span aria-hidden="true" :class="[fee.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button @click="openModal(fee)" class="text-indigo-600 hover:text-indigo-900 p-2 hover:bg-indigo-50 rounded-lg transition-colors mr-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button @click="deleteFee(fee)" class="text-rose-600 hover:text-rose-900 p-2 hover:bg-rose-50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="fees.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    Tidak ada data master fee.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="fees.links && fees.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-100 sm:px-6 flex justify-between items-center">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Menampilkan <span class="font-medium">{{ fees.from }}</span> sampai <span class="font-medium">{{ fees.to }}</span> dari <span class="font-medium">{{ fees.total }}</span> data
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <template v-for="(link, k) in fees.links" :key="k">
                                    <button v-if="link.url" @click.prevent="goToPage(link.url)" v-html="link.label"
                                        :class="[link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50', 'relative inline-flex items-center px-4 py-2 border text-sm font-medium']">
                                    </button>
                                    <span v-else v-html="link.label" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300"></span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Form -->
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-100">
                        <div class="bg-gray-50/50 px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">{{ editMode ? 'Edit Fee' : 'Tambah Fee' }}</h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="px-6 py-5">
                            <form @submit.prevent="submit" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nama Fee</label>
                                    <input type="text" v-model="form.name" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 uppercase" required>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipe</label>
                                        <select v-model="form.type" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <option value="Fee Booking">Fee Booking</option>
                                            <option value="Fee Survey">Fee Survey</option>
                                            <option value="Fee Pasang">Fee Pasang</option>
                                            <option value="Fee Freelance per Paket">Fee Freelance per Paket</option>
                                            <option value="Bonus Target Booking">Bonus Target Booking</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mode</label>
                                        <select v-model="form.mode" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <option value="Auto">Auto</option>
                                            <option value="Manual">Manual</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                                    <input type="number" v-model="form.nominal" min="0" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                                    <textarea v-model="form.description" rows="2" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                </div>
                                <div class="flex items-center">
                                    <input id="is_active" type="checkbox" v-model="form.is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">Aktif</label>
                                </div>
                                
                                <div class="pt-4 flex justify-end gap-3">
                                    <button type="button" @click="showModal = false" class="bg-white py-2.5 px-5 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">
                                        Batal
                                    </button>
                                    <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 inline-flex justify-center py-2.5 px-5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all disabled:opacity-50">
                                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    fees: Object,
    stats: Object,
    filters: Object,
});

const filter = reactive({
    tab: props.filters.tab || 'semua',
    search: props.filters.search || '',
});

const showModal = ref(false);
const editMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    type: 'Fee Booking',
    mode: 'Auto',
    nominal: 0,
    description: '',
    is_active: true,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const getTypeBadgeClass = (type) => {
    switch(type) {
        case 'Fee Booking': return 'bg-blue-50 text-blue-700 border border-blue-100';
        case 'Fee Survey': return 'bg-orange-50 text-orange-700 border border-orange-100';
        case 'Fee Pasang': return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
        case 'Fee Freelance per Paket': return 'bg-purple-50 text-purple-700 border border-purple-100';
        case 'Bonus Target Booking': return 'bg-rose-50 text-rose-700 border border-rose-100';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const fetchData = () => {
    router.get(route('master-fees.index'), filter, { preserveState: true });
};

const setTab = (tab) => {
    filter.tab = tab;
    fetchData();
};

const goToPage = (url) => {
    if (!url) return;
    router.get(url, filter, { preserveState: true, preserveScroll: true });
};

const openModal = (fee = null) => {
    if (fee) {
        editMode.value = true;
        form.id = fee.id;
        form.name = fee.name;
        form.type = fee.type;
        form.mode = fee.mode;
        form.nominal = fee.nominal;
        form.description = fee.description;
        form.is_active = fee.is_active;
    } else {
        editMode.value = false;
        form.reset();
        form.id = null;
    }
    showModal.value = true;
};

const submit = () => {
    if (editMode.value) {
        form.put(route('master-fees.update', form.id), {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post(route('master-fees.store'), {
            onSuccess: () => showModal.value = false,
        });
    }
};

const toggleActive = (fee) => {
    router.put(route('master-fees.update', fee.id), {
        ...fee,
        is_active: !fee.is_active
    }, { preserveScroll: true });
};

const deleteFee = (fee) => {
    if (confirm(`Hapus master fee ${fee.name}?`)) {
        router.delete(route('master-fees.destroy', fee.id), { preserveScroll: true });
    }
};
</script>
