<template>
    <AppLayout title="Master Kategori Pengeluaran">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-xs text-xl">
                            🏷️
                        </span>
                        <span>Master Kategori Pengeluaran</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Kelola pos pengelompokan biaya operasional, logistik, dan perawatan jaringan</p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="openModal()" 
                        class="inline-flex items-center px-4 py-2.5 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Kategori</span>
                    </button>
                </div>
            </div>

            <!-- KPI / Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Kategori</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.total_categories }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Pos anggaran pengeluaran</p>
                    </div>
                    <div class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        🗂️
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Kategori Aktif</p>
                        <p class="mt-1 text-2xl font-black text-emerald-600 font-mono">{{ stats.active_categories }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tersedia untuk pencatatan biaya</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        ✅
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Pengeluaran Tercatat</p>
                        <p class="mt-1 text-xl font-black text-rose-600 font-mono">Rp {{ formatCurrency(stats.total_expense_amount) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi seluruh transaksi biaya</p>
                    </div>
                    <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        💸
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        @input="onSearch"
                        placeholder="Cari nama, kode singkatan, atau keterangan..." 
                        class="block w-full pl-10 pr-9 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-200 focus:border-rose-500 transition-all text-slate-800"
                    />
                    <button 
                        v-if="searchQuery" 
                        @click="resetSearch" 
                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan <strong class="text-slate-800">{{ categories.data?.length || 0 }}</strong> kategori
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white shadow-sm border border-slate-200/80 rounded-3xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Kode</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Nama Kategori</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Jumlah Transaksi</th>
                                <th scope="col" class="px-6 py-4 text-right text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Total Nominal</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-slate-50/60 transition-colors">
                                <!-- Kode -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="badgeColorClass(cat.color)" class="px-2.5 py-1 text-[11px] font-black rounded-lg border uppercase tracking-wider shadow-xs">
                                        {{ cat.code || '-' }}
                                    </span>
                                </td>

                                <!-- Nama Kategori -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-extrabold text-slate-900">{{ cat.name }}</div>
                                </td>

                                <!-- Keterangan -->
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-500 max-w-xs truncate" :title="cat.description">{{ cat.description || '-' }}</div>
                                </td>

                                <!-- Jumlah Transaksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-700">
                                        {{ cat.expenses_count || 0 }}x
                                    </span>
                                </td>

                                <!-- Total Nominal -->
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-xs text-slate-900 font-mono">
                                    Rp {{ formatCurrency(cat.expenses_sum_amount || 0) }}
                                </td>

                                <!-- Status Toggle -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button 
                                        type="button"
                                        @click="toggleActive(cat)" 
                                        :class="cat.is_active ? 'bg-emerald-600' : 'bg-slate-300'"
                                        class="relative inline-flex flex-shrink-0 h-5 w-10 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none"
                                        :title="cat.is_active ? 'Status Aktif (Klik untuk nonaktifkan)' : 'Status Nonaktif (Klik untuk aktifkan)'"
                                    >
                                        <span aria-hidden="true" :class="cat.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
                                    </button>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-bold">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button 
                                            @click="openModal(cat)" 
                                            class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl border border-transparent hover:border-indigo-100 transition-all cursor-pointer" 
                                            title="Edit Kategori"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button 
                                            @click="deleteCategory(cat)" 
                                            class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl border border-transparent hover:border-rose-100 transition-all cursor-pointer" 
                                            title="Hapus Kategori"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Baris Kosong -->
                            <tr v-if="!categories.data || categories.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-xl mb-2">🏷️</div>
                                        <p class="font-extrabold text-slate-700 text-sm">Tidak ada kategori pengeluaran ditemukan</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Coba ganti kata kunci pencarian atau klik Tambah Kategori baru</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Menampilkan <strong class="text-slate-800">{{ categories.from || 0 }}</strong> sampai <strong class="text-slate-800">{{ categories.to || 0 }}</strong> dari <strong class="text-slate-800">{{ categories.total || 0 }}</strong> data
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in categories.links" :key="i">
                            <Link 
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="link.active ? 'bg-rose-600 text-white font-black border-rose-600 shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-200 font-semibold'"
                                class="px-3 py-1.5 border rounded-xl text-xs transition-colors"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1.5 border rounded-xl text-xs text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
                <div class="relative bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-slate-100 animate-fade-in-up">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-rose-50 via-pink-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-600/20 text-lg">
                                🏷️
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900">{{ isEditing ? 'Edit Kategori Pengeluaran' : 'Tambah Kategori Baru' }}</h3>
                                <p class="text-xs text-slate-500 font-medium">Master pos alokasi pengeluaran dana</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <form @submit.prevent="submitForm">
                        <div class="p-6 space-y-4">
                            <!-- Nama Kategori -->
                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 mb-1">
                                    Nama Kategori <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.name" 
                                    required 
                                    placeholder="Contoh: Operasional Lapangan" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-200 focus:border-rose-500 transition-all"
                                />
                                <p v-if="form.errors.name" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.name }}</p>
                            </div>

                            <!-- Kode Kategori -->
                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 mb-1">Kode Kategori (Singkatan)</label>
                                <input 
                                    type="text" 
                                    v-model="form.code" 
                                    @input="form.code = form.code.toUpperCase()"
                                    placeholder="Contoh: OPR, MNT, PLN, BW" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold uppercase text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-200 focus:border-rose-500 transition-all"
                                />
                                <p class="text-[10px] text-slate-400 mt-1">Kode singkatan untuk label di laporan transaksi buku kas</p>
                            </div>

                            <!-- Pilihan Warna Badge -->
                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Warna Label</label>
                                <div class="grid grid-cols-4 gap-2">
                                    <button 
                                        type="button" 
                                        v-for="c in colorOptions" 
                                        :key="c.value"
                                        @click="form.color = c.value"
                                        :class="[
                                            badgeColorClass(c.value),
                                            form.color === c.value ? 'ring-2 ring-slate-900 ring-offset-1 font-black shadow-sm' : 'opacity-70 hover:opacity-100'
                                        ]"
                                        class="px-2 py-1.5 rounded-xl text-xs font-bold border text-center transition-all cursor-pointer"
                                    >
                                        {{ c.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 mb-1">Keterangan / Pos Alokasi</label>
                                <textarea 
                                    v-model="form.description" 
                                    rows="2" 
                                    placeholder="Contoh: Bensin kendaraan operasional, konsumsi teknisi, dsb." 
                                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-200 focus:border-rose-500 transition-all"
                                ></textarea>
                            </div>

                            <!-- Status Aktif Switch -->
                            <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <div>
                                    <span class="text-xs font-extrabold text-slate-800">Status Kategori Aktif</span>
                                    <p class="text-[10px] text-slate-500">Tampilkan kategori ini pada pilihan pos pengeluaran</p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="form.is_active = !form.is_active"
                                    :class="form.is_active ? 'bg-emerald-600' : 'bg-slate-300'"
                                    class="relative inline-flex flex-shrink-0 h-5 w-10 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none"
                                >
                                    <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
                                </button>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex justify-end gap-2">
                            <button 
                                type="button" 
                                @click="closeModal" 
                                class="px-4 py-2 text-xs font-extrabold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing || !form.name" 
                                class="bg-rose-600 hover:bg-rose-700 active:scale-95 text-white px-5 py-2 rounded-xl text-xs font-extrabold shadow-sm transition-all disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                            >
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
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

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
            return 'bg-amber-50 text-amber-800 border-amber-300';
        case 'emerald':
            return 'bg-emerald-50 text-emerald-800 border-emerald-300';
        case 'indigo':
            return 'bg-indigo-50 text-indigo-800 border-indigo-300';
        case 'blue':
            return 'bg-blue-50 text-blue-800 border-blue-300';
        case 'purple':
            return 'bg-purple-50 text-purple-800 border-purple-300';
        case 'cyan':
            return 'bg-cyan-50 text-cyan-800 border-cyan-300';
        case 'gray':
            return 'bg-slate-100 text-slate-700 border-slate-300';
        case 'rose':
        default:
            return 'bg-rose-50 text-rose-800 border-rose-300';
    }
}

function formatCurrency(val) {
    if (!val) return '0';
    return Number(val).toLocaleString('id-ID');
}

const onSearch = debounce(() => {
    router.get('/settings/expense-categories', {
        search: searchQuery.value || undefined,
    }, { preserveState: true, replace: true });
}, 300);

function resetSearch() {
    searchQuery.value = '';
    router.get('/settings/expense-categories', {}, { preserveState: true, replace: true });
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
    form.clearErrors();
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
    const url = isEditing.value && editingCategory.value
        ? route('expense-categories.update.post', editingCategory.value.id)
        : route('expense-categories.store');

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil!',
                text: isEditing.value ? 'Kategori berhasil diperbarui.' : 'Kategori baru berhasil ditambahkan.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Periksa kembali formulir input.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstErr,
                confirmButtonColor: '#e11d48'
            });
        }
    });
}

function toggleActive(category) {
    router.post(route('expense-categories.toggle', category.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Status Diperbarui',
                text: `Status kategori ${category.name} berhasil diubah.`,
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
}

function deleteCategory(category) {
    const count = category.expenses_count || 0;
    const warningMsg = count > 0 
        ? `<div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 mt-2 text-left"><strong>Perhatian:</strong> Kategori ini memiliki <strong>${count} transaksi</strong> (Total Rp ${formatCurrency(category.expenses_sum_amount || 0)}). Riwayat keuangan transaksi akan tetap aman tersimpan di Buku Kas tanpa label kategori.</div>`
        : '<p class="text-xs text-slate-500 mt-2">Kategori ini belum memiliki transaksi pengeluaran.</p>';

    Swal.fire({
        title: 'Hapus Kategori?',
        html: `Apakah Anda yakin ingin menghapus kategori <strong>"${category.name}"</strong>?${warningMsg}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Kategori',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('expense-categories.destroy.post', category.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Terhapus',
                        text: `Kategori ${category.name} berhasil dihapus.`,
                        showConfirmButton: false,
                        timer: 2500
                    });
                },
                onError: (err) => {
                    const firstErr = Object.values(err || {})[0] || 'Gagal menghapus kategori.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menghapus',
                        text: firstErr,
                        confirmButtonColor: '#e11d48'
                    });
                }
            });
        }
    });
}
</script>
