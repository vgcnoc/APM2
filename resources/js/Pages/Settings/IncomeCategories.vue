<template>
    <AppLayout title="Master Kategori Pemasukan">
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
                        <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-xs text-xl">
                            💰
                        </span>
                        <span>Master Kategori Pemasukan</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Kelola pos pengelompokan sumber pendapatan non-tagihan, jasa setting, dan kas masuk</p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="openModal()" 
                        class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Kategori Pemasukan</span>
                    </button>
                </div>
            </div>

            <!-- Navigation Tabs (Pemasukan vs Pengeluaran) -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-100 rounded-2xl w-fit">
                <Link 
                    :href="route('expense-categories.index')" 
                    class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5"
                >
                    <span>🏷️</span>
                    <span>Kategori Pengeluaran</span>
                </Link>
                <Link 
                    :href="route('income-categories.index')" 
                    class="px-4 py-2 text-xs font-bold rounded-xl bg-white text-emerald-700 shadow-xs transition-all flex items-center gap-1.5"
                >
                    <span>💰</span>
                    <span>Kategori Pemasukan</span>
                </Link>
                <Link 
                    :href="route('financial-reports.index')" 
                    class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5"
                >
                    <span>📖</span>
                    <span>Kembali ke Buku Kas</span>
                </Link>
            </div>

            <!-- KPI / Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Kategori</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.total_categories }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Pos sumber pendapatan kas</p>
                    </div>
                    <div class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        🗂️
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Kategori Aktif</p>
                        <p class="mt-1 text-2xl font-black text-emerald-600 font-mono">{{ stats.active_categories }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tersedia untuk pencatatan kas</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        ✅
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Pemasukan Tercatat</p>
                        <p class="mt-1 text-xl font-black text-emerald-600 font-mono">Rp {{ formatCurrency(stats.total_income_amount) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi transaksi non-tagihan</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        💵
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
                        class="block w-full pl-10 pr-9 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 transition-all text-slate-800"
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
                    Menampilkan <strong class="text-slate-800">{{ categories.data?.length || 0 }}</strong> kategori pemasukan
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
                                        {{ cat.incomes_count || 0 }}x
                                    </span>
                                </td>

                                <!-- Total Nominal -->
                                <td class="px-6 py-4 whitespace-nowrap text-right font-black text-xs text-emerald-600 font-mono">
                                    Rp {{ formatCurrency(cat.incomes_sum_amount || 0) }}
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
                                            class="p-2 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl border border-transparent hover:border-emerald-100 transition-all cursor-pointer" 
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
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-3xl mb-2">💰</span>
                                        <p class="text-xs font-bold text-slate-600">Tidak ada kategori pemasukan ditemukan</p>
                                        <p class="text-[11px] text-slate-400 mt-1">Coba gunakan kata kunci lain atau tambahkan kategori baru</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="categories.links && categories.links.length > 3" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-xs text-slate-500 font-medium">
                        Menampilkan <strong>{{ categories.from || 0 }}</strong> - <strong>{{ categories.to || 0 }}</strong> dari <strong>{{ categories.total || 0 }}</strong> data
                    </p>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in categories.links" 
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                link.active ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Kategori -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold border border-emerald-100">
                                💰
                            </span>
                            <h3 class="text-base font-extrabold text-slate-900">
                                {{ isEditing ? 'Edit Kategori Pemasukan' : 'Tambah Kategori Pemasukan' }}
                            </h3>
                        </div>
                        <button 
                            @click="closeModal" 
                            class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                                    placeholder="Contoh: Jasa Setting & Instalasi Khusus" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 transition-all"
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
                                    placeholder="Contoh: JSA, BRG, SEW, VCH" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold uppercase text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 transition-all"
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
                                <label class="block text-xs font-extrabold text-slate-700 mb-1">Keterangan / Sumber Pendapatan</label>
                                <textarea 
                                    v-model="form.description" 
                                    rows="2" 
                                    placeholder="Contoh: Pendapatan penarikan kabel khusus, setting router cafe, dsb." 
                                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 transition-all"
                                ></textarea>
                            </div>

                            <!-- Status Aktif Switch -->
                            <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <div>
                                    <span class="text-xs font-extrabold text-slate-800">Status Kategori Aktif</span>
                                    <p class="text-[10px] text-slate-500">Tampilkan kategori ini pada pilihan pencatatan pemasukan</p>
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
                                class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white px-5 py-2 rounded-xl text-xs font-extrabold shadow-sm transition-all disabled:opacity-50 flex items-center gap-2 cursor-pointer"
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
    categories: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            total_categories: 0,
            active_categories: 0,
            total_income_amount: 0,
        })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const searchQuery = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    color: 'emerald',
    is_active: true,
});

const colorOptions = [
    { label: 'Emerald', value: 'emerald' },
    { label: 'Blue', value: 'blue' },
    { label: 'Indigo', value: 'indigo' },
    { label: 'Purple', value: 'purple' },
    { label: 'Amber', value: 'amber' },
    { label: 'Cyan', value: 'cyan' },
    { label: 'Teal', value: 'teal' },
    { label: 'Gray', value: 'gray' },
];

const badgeColorClass = (color) => {
    switch (color) {
        case 'emerald':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'blue':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'indigo':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'purple':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'cyan':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'teal':
            return 'bg-teal-50 text-teal-700 border-teal-200';
        case 'rose':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID').format(Number(amount) || 0);
};

const onSearch = debounce(() => {
    router.get(route('income-categories.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const resetSearch = () => {
    searchQuery.value = '';
    router.get(route('income-categories.index'), {}, {
        preserveState: true,
        replace: true,
    });
};

const openModal = (category = null) => {
    form.clearErrors();
    if (category) {
        isEditing.value = true;
        editingId.value = category.id;
        form.name = category.name;
        form.code = category.code || '';
        form.description = category.description || '';
        form.color = category.color || 'emerald';
        form.is_active = Boolean(category.is_active);
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.color = 'emerald';
        form.is_active = true;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    isEditing.value = false;
    editingId.value = null;
};

const submitForm = () => {
    if (isEditing.value) {
        form.post(route('income-categories.update.post', editingId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('income-categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const toggleActive = (category) => {
    router.post(route('income-categories.toggle', category.id), {}, {
        preserveScroll: true,
    });
};

const deleteCategory = (category) => {
    Swal.fire({
        title: 'Hapus Kategori?',
        text: `Kategori "${category.name}" akan dihapus. Riwayat transaksi terkait akan dialihkan menjadi tanpa kategori.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('income-categories.destroy.post', category.id), {}, {
                preserveScroll: true,
            });
        }
    });
};
</script>

