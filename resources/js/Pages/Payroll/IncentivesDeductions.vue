<template>
    <AppLayout title="Insentif & Potongan">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Insentif & Potongan</h1>
                    <p class="text-sm text-gray-500 mt-1">Daftar semua bonus, insentif, dan potongan karyawan</p>
                </div>
                <div class="flex gap-2">
                    <button @click="showManualModal = true" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-bold text-sm text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Tambah Manual
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Insentif</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">Rp {{ formatCurrency(kpi.totalIncentive) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Potongan</p>
                        <p class="mt-1 text-2xl font-bold text-rose-600">Rp {{ formatCurrency(kpi.totalDeduction) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-rose-100 rounded-full flex items-center justify-center text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Otomatis</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ kpi.countAuto }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Manual</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ kpi.countManual }}</p>
                    </div>
                    <div class="w-12 h-12 bg-teal-50 rounded-full flex items-center justify-center text-teal-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Bulan</label>
                        <select v-model="filter.month" @change="fetchData" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option v-for="(name, index) in months" :key="index" :value="index + 1">{{ name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tahun</label>
                        <input type="number" v-model="filter.year" @change="fetchData" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Pencarian</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" v-model="filter.search" @keyup.enter="fetchData" placeholder="Cari karyawan / keterangan..." class="block w-full pl-10 border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-4">
                <nav class="flex space-x-2" aria-label="Tabs">
                    <button @click="setTab('semua')" :class="[filter.tab === 'semua' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Semua ({{ records.total }})</button>
                    <button @click="setTab('insentif')" :class="[filter.tab === 'insentif' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Insentif</button>
                    <button @click="setTab('potongan')" :class="[filter.tab === 'potongan' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Potongan</button>
                    <button @click="setTab('auto')" :class="[filter.tab === 'auto' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Auto</button>
                    <button @click="setTab('manual')" :class="[filter.tab === 'manual' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100', 'px-4 py-2 text-sm font-bold rounded-lg transition-all']">Manual</button>
                </nav>
            </div>

            <!-- Table -->
            <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Karyawan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Mode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Jumlah</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="record in records.data" :key="record.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">{{ record.user_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[record.record_type === 'Insentif' ? 'bg-indigo-100 text-indigo-800' : 'bg-rose-100 text-rose-800', 'px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full']">
                                        {{ record.record_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[record.mode === 'Auto' ? 'border border-indigo-200 text-indigo-700' : 'border border-gray-200 text-gray-700', 'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full']">
                                        {{ record.mode }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold">
                                    <span :class="record.record_type === 'Insentif' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ record.record_type === 'Insentif' ? '+' : '-' }}Rp {{ formatCurrency(record.amount) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium">{{ record.description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(record.date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button v-if="record.status === 'pending'" @click="deleteRecord(record)" class="text-rose-600 hover:text-rose-900 p-2 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    <span v-else class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Paid</span>
                                </td>
                            </tr>
                            <tr v-if="records.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        <span class="text-lg font-medium text-gray-900">Tidak ada data</span>
                                        <p class="text-sm text-gray-500 mt-1">Belum ada insentif atau potongan di periode ini.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="records.links && records.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-100 sm:px-6 flex justify-between items-center">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Menampilkan <span class="font-medium">{{ records.from }}</span> sampai <span class="font-medium">{{ records.to }}</span> dari <span class="font-medium">{{ records.total }}</span> data
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, k) in records.links" :key="k">
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

            <!-- Modal Tambah Manual -->
            <div v-if="showManualModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showManualModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-100">
                        <div class="bg-gray-50/50 px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center gap-2">
                                <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                </span>
                                Tambah Insentif / Potongan
                            </h3>
                            <button @click="showManualModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="px-6 py-5">
                            <form @submit.prevent="submitManual" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Jenis</label>
                                    <select v-model="manualForm.type" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <option value="incentive">Bonus / Insentif</option>
                                        <option value="deduction">Potongan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Karyawan</label>
                                    <select v-model="manualForm.user_id" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <option value="" disabled>Pilih Karyawan</option>
                                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                                    <input type="date" v-model="manualForm.date" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                                    <input type="number" v-model="manualForm.amount" min="1" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold text-lg" placeholder="Contoh: 50000" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kategori / Keterangan</label>
                                    <input list="category-list-global" v-model="manualForm.description" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Pilih atau ketik keterangan..." required>
                                    <datalist id="category-list-global">
                                        <template v-if="manualForm.type === 'incentive'">
                                            <option value="Lembur"></option>
                                            <option value="Bonus Kerajinan"></option>
                                            <option value="THR (Tunjangan Hari Raya)"></option>
                                            <option value="Bonus Pencapaian Target"></option>
                                        </template>
                                        <template v-else>
                                            <option value="Kasbon / Pinjaman"></option>
                                            <option value="Potongan Absen / Terlambat"></option>
                                            <option value="Iuran BPJS Kesehatan"></option>
                                            <option value="Iuran BPJS Ketenagakerjaan"></option>
                                            <option value="Denda / Ganti Rugi Barang"></option>
                                        </template>
                                    </datalist>
                                </div>
                                
                                <div class="pt-4 flex justify-end gap-3">
                                    <button type="button" @click="showManualModal = false" class="bg-white py-2.5 px-5 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">
                                        Batal
                                    </button>
                                    <button type="submit" :disabled="manualForm.processing" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 inline-flex justify-center py-2.5 px-5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all disabled:opacity-50">
                                        {{ manualForm.processing ? 'Menyimpan...' : 'Simpan Data' }}
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
    records: Object,
    kpi: Object,
    filters: Object,
    users: Array,
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const filter = reactive({
    tab: props.filters.tab || 'semua',
    month: props.filters.month || new Date().getMonth() + 1,
    year: props.filters.year || new Date().getFullYear(),
    search: props.filters.search || '',
});

const showManualModal = ref(false);

const manualForm = useForm({
    type: 'incentive',
    user_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    }).format(date);
};

const fetchData = () => {
    router.get(route('insentif-potongan.index'), filter, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const setTab = (tab) => {
    filter.tab = tab;
    fetchData();
};

const goToPage = (url) => {
    if (!url) return;
    const urlObj = new URL(url);
    const page = urlObj.searchParams.get('page');
    router.get(route('insentif-potongan.index'), { ...filter, page }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteRecord = (record) => {
    if (confirm(`Hapus ${record.record_type} untuk ${record.user_name} sejumlah Rp ${formatCurrency(record.amount)}?`)) {
        router.delete(route('insentif-potongan.destroy'), {
            data: { id: record.id },
            preserveScroll: true,
        });
    }
};

const submitManual = () => {
    const routeName = manualForm.type === 'incentive' ? 'payroll.incentive.store' : 'payroll.deduction.store';
    
    manualForm.post(route(routeName, manualForm.user_id), {
        preserveScroll: true,
        onSuccess: () => {
            showManualModal.value = false;
            manualForm.reset();
            manualForm.user_id = '';
        },
    });
};
</script>
