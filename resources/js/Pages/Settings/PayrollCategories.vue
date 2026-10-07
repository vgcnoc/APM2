<template>
    <AppLayout title="Master Insentif & Potongan">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Master Insentif & Potongan</h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola jenis-jenis insentif dan potongan</p>
                </div>
                <div class="flex gap-2">
                    <button @click="openModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-bold text-sm text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Tambah
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Insentif</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total_incentive }}</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Potongan</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total_deduction }}</p>
                    </div>
                    <div class="w-12 h-12 bg-rose-50 rounded-full flex items-center justify-center text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-4">
                <div class="relative max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" v-model="search" @keyup.enter="fetchData" placeholder="Cari nama..." class="block w-full pl-10 border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Nama</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Mode</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Nominal Default</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Aktif</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">{{ cat.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[cat.type === 'incentive' ? 'bg-indigo-100 text-indigo-800' : 'bg-rose-100 text-rose-800', 'px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full']">
                                        {{ cat.type === 'incentive' ? 'Insentif' : 'Potongan' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[cat.mode === 'auto' ? 'border border-indigo-200 text-indigo-700' : 'border border-gray-200 text-gray-700', 'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full']">
                                        {{ cat.mode === 'auto' ? 'Auto' : 'Manual' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                    Rp {{ formatCurrency(cat.default_amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">{{ cat.description || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="toggleActive(cat)" :class="[cat.is_active ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500']">
                                        <span class="sr-only">Toggle Active</span>
                                        <span aria-hidden="true" :class="[cat.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button @click="openModal(cat)" class="text-indigo-600 hover:text-indigo-900 p-2 hover:bg-indigo-50 rounded-lg transition-colors mr-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button @click="deleteCategory(cat)" class="text-rose-600 hover:text-rose-900 p-2 hover:bg-rose-50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    Tidak ada data kategori.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-100 sm:px-6 flex justify-between items-center">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Menampilkan <span class="font-medium">{{ categories.from }}</span> sampai <span class="font-medium">{{ categories.to }}</span> dari <span class="font-medium">{{ categories.total }}</span> data
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <template v-for="(link, k) in categories.links" :key="k">
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
                            <h3 class="text-lg leading-6 font-bold text-gray-900">{{ editMode ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="px-6 py-5">
                            <form @submit.prevent="submit" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                                    <input type="text" v-model="form.name" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipe</label>
                                        <select v-model="form.type" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <option value="incentive">Insentif</option>
                                            <option value="deduction">Potongan</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mode</label>
                                        <select v-model="form.mode" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <option value="manual">Manual</option>
                                            <option value="auto">Auto</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nominal Default (Rp)</label>
                                    <input type="number" v-model="form.default_amount" min="0" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                    <p class="text-xs text-gray-500 mt-1">Kosongkan atau isi 0 jika nominal berbeda-beda.</p>
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
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    categories: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    type: 'incentive',
    mode: 'manual',
    default_amount: 0,
    description: '',
    is_active: true,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const fetchData = () => {
    router.get(route('payroll-categories.index'), { search: search.value }, { preserveState: true });
};

const goToPage = (url) => {
    if (!url) return;
    router.get(url, { search: search.value }, { preserveState: true, preserveScroll: true });
};

const openModal = (cat = null) => {
    if (cat) {
        editMode.value = true;
        form.id = cat.id;
        form.name = cat.name;
        form.type = cat.type;
        form.mode = cat.mode;
        form.default_amount = cat.default_amount;
        form.description = cat.description;
        form.is_active = cat.is_active;
    } else {
        editMode.value = false;
        form.reset();
        form.id = null;
    }
    showModal.value = true;
};

const submit = () => {
    if (editMode.value) {
        form.put(route('payroll-categories.update', form.id), {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post(route('payroll-categories.store'), {
            onSuccess: () => showModal.value = false,
        });
    }
};

const toggleActive = (cat) => {
    router.put(route('payroll-categories.update', cat.id), {
        ...cat,
        is_active: !cat.is_active
    }, { preserveScroll: true });
};

const deleteCategory = (cat) => {
    if (confirm(`Hapus kategori ${cat.name}?`)) {
        router.delete(route('payroll-categories.destroy', cat.id), { preserveScroll: true });
    }
};
</script>
