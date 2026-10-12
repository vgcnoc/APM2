<template>
    <AppLayout title="Master Kategori Pengeluaran">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </span>
                        Master Kategori Pengeluaran
                    </h1>
                    <p class="text-xs text-gray-500 mt-1 font-medium">Kelola pos pengelompokan biaya operasional, logistik, dan perawatan jaringan</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="openModal()" class="inline-flex items-center px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-rose-600/20 hover:shadow transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Kategori
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Kategori</p>
                        <p class="mt-1 text-2xl font-black text-gray-900">{{ stats.total_categories }}</p>
                    </div>
                    <div class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Kategori Aktif</p>
                        <p class="mt-1 text-2xl font-black text-emerald-600">{{ stats.active_categories }}</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Pengeluaran Tercatat</p>
                        <p class="mt-1 text-xl font-black text-rose-600">Rp {{ formatCurrency(stats.total_expense_amount) }}</p>
                    </div>
                    <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        @keyup.enter="handleSearch"
                        placeholder="Cari nama atau kode kategori..." 
                        class="block w-full pl-10 pr-4 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-xs font-semibold placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all"
                    />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button 
                        v-if="searchQuery"
                        @click="resetSearch" 
                        class="px-3 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        Reset
                    </button>
                    <button 
                        @click="handleSearch" 
                        class="px-4 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition-all">
                        Cari
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/70">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Kode</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-3.5 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Jumlah Transaksi</th>
                                <th scope="col" class="px-6 py-3.5 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Nominal</th>
                                <th scope="col" class="px-6 py-3.5 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3.5 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="badgeColorClass(cat.color)" class="px-2.5 py-1 text-[11px] font-black rounded-lg border uppercase tracking-wider">
                                        {{ cat.code || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ cat.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-500 max-w-xs truncate" :title="cat.description">{{ cat.description || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                                        {{ cat.expenses_count || 0 }}x
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-xs text-gray-900">
                                    Rp {{ formatCurrency(cat.expenses_sum_amount || 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button 
                                        @click="toggleActive(cat)" 
                                        :class="cat.is_active ? 'bg-emerald-600' : 'bg-gray-200'"
                                        class="relative inline-flex flex-shrink-0 h-5 w-10 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none"
                                        title="Klik untuk mengubah status aktif"
                                    >
                                        <span aria-hidden="true" :class="cat.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-medium">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="openModal(cat)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Kategori">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteCategory(cat)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Kategori">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        <p class="font-bold text-gray-600 text-sm">Belum ada kategori pengeluaran</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Klik tombol Tambah Kategori untuk menambahkan master kategori baru</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan <span class="font-bold text-gray-700">{{ categories.from || 0 }}</span> sampai <span class="font-bold text-gray-700">{{ categories.to || 0 }}</span> dari <span class="font-bold text-gray-700">{{ categories.total || 0 }}</span> data
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in categories.links" :key="i">
                            <button 
                                v-if="link.url"
                                @click="router.visit(link.url, { preserveState: true, preserveScroll: true })"
                                v-html="link.label"
                                :class="link.active ? 'bg-rose-600 text-white font-bold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                                class="px-3 py-1 text-xs rounded-lg transition-colors"
                            />
                            <span v-else v-html="link.label" class="px-2 py-1 text-xs text-gray-300"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                <div class="relative bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden z-10 animate-fade-in-up">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-rose-50 via-pink-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-600/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">{{ isEditing ? 'Edit Kategori Pengeluaran' : 'Tambah Kategori Baru' }}</h3>
                                <p class="text-xs text-gray-500 font-medium">Master pos alokasi pengeluaran dana</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm">
                        <div class="p-6 space-y-4">
                            <!-- Nama Kategori -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                                <input 
                                    type="text" 
                                    v-model="form.name" 
                                    required 
                                    placeholder="Contoh: Operasional Lapangan" 
                                    class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all"
                                />
                            </div>

                            <!-- Kode Kategori -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Kode Kategori (Singkatan)</label>
                                <input 
                                    type="text" 
                                    v-model="form.code" 
                                    placeholder="Contoh: OPR, MNT, PLN, BW" 
                                    class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold uppercase focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all"
                                />
                                <p class="text-[10px] text-gray-400 mt-1">Kode unik singkatan untuk label singkat di laporan transaksi</p>
                            </div>

                            <!-- Pilihan Warna Badge -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Warna Label</label>
                                <div class="grid grid-cols-4 gap-2">
                                    <button 
                                        type="button" 
                                        v-for="c in colorOptions" 
                                        :key="c.value"
                                        @click="form.color = c.value"
                                        :class="[
                                            badgeColorClass(c.value),
                                            form.color === c.value ? 'ring-2 ring-gray-900 ring-offset-1 font-black shadow-sm' : 'opacity-70 hover:opacity-100'
                                        ]"
                                        class="px-2 py-1.5 rounded-lg text-xs font-bold border text-center transition-all"
                                    >
                                        {{ c.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Pos Alokasi</label>
                                <textarea 
                                    v-model="form.description" 
                                    rows="2" 
                                    placeholder="Contoh: Bensin motor/mobil, konsumsi makan teknisi, biaya parkir..." 
                                    class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all"
                                ></textarea>
                            </div>

                            <!-- Status Aktif Switch -->
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200">
                                <div>
                                    <span class="text-xs font-bold text-gray-800">Status Kategori</span>
                                    <p class="text-[10px] text-gray-500">Tampilkan kategori ini pada dropdown pencatatan pengeluaran</p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="form.is_active = !form.is_active"
                                    :class="form.is_active ? 'bg-emerald-600' : 'bg-gray-200'"
                                    class="relative inline-flex flex-shrink-0 h-5 w-10 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none"
                                >
                                    <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
                                </button>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-2">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing || !form.name" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-sm shadow-rose-600/20 transition-all disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Kategori') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
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

const searchQuery = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingCategory = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    color: 'rose',
    is_active: true,
});

const colorOptions = [
    { value: 'rose', label: 'Rose' },
    { value: 'amber', label: 'Amber' },
    { value: 'emerald', label: 'Emerald' },
    { value: 'indigo', label: 'Indigo' },
    { value: 'blue', label: 'Blue' },
    { value: 'purple', label: 'Purple' },
    { value: 'cyan', label: 'Cyan' },
    { value: 'gray', label: 'Gray' },
];

function badgeColorClass(color) {
    switch (color) {
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'emerald':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'indigo':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'blue':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'purple':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'cyan':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'gray':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        case 'rose':
        default:
            return 'bg-rose-50 text-rose-700 border-rose-200';
    }
}

function formatCurrency(val) {
    if (!val) return '0';
    return Number(val).toLocaleString('id-ID');
}

function handleSearch() {
    router.get('/settings/expense-categories', {
        search: searchQuery.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetSearch() {
    searchQuery.value = '';
    handleSearch();
}

function openModal(category = null) {
    if (category) {
        isEditing.value = true;
        editingCategory.value = category;
        form.name = category.name;
        form.code = category.code || '';
        form.description = category.description || '';
        form.color = category.color || 'rose';
        form.is_active = Boolean(category.is_active);
    } else {
        isEditing.value = false;
        editingCategory.value = null;
        form.reset();
        form.color = 'rose';
        form.is_active = true;
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    setTimeout(() => {
        isEditing.value = false;
        editingCategory.value = null;
        form.reset();
    }, 200);
}

function submitForm() {
    if (isEditing.value && editingCategory.value) {
        form.put(`/settings/expense-categories/${editingCategory.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/settings/expense-categories', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function toggleActive(category) {
    router.post(`/settings/expense-categories/${category.id}/toggle`, {}, {
        preserveScroll: true,
    });
}

function deleteCategory(category) {
    if (confirm(`Yakin ingin menghapus kategori "${category.name}"? Data pengeluaran yang terhubung akan tetap tersimpan.`)) {
        router.delete(`/settings/expense-categories/${category.id}`, {
            preserveScroll: true,
        });
    }
}
</script>
