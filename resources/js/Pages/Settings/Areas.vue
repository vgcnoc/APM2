<template>
    <AppLayout title="Master Data Area">
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-2xl text-xl">📍</span>
                        <span>Master Data Area / Wilayah</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola daftar area coverage, titik koordinat geografis, serta sebaran pelanggan &amp; infrastruktur jaringan</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Area / Wilayah</span>
                        <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ stats?.total_areas || (areas.total || areas.data?.length || 0) }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Wilayah operasional terdaftar</div>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        🗺️
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Pelanggan di Area</span>
                        <div class="text-2xl font-black text-emerald-700 mt-1 font-mono">{{ stats?.total_customers || 0 }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Pelanggan aktif terplot di area</div>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        👥
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-600">Titik ODP di Area</span>
                        <div class="text-2xl font-black text-sky-700 mt-1 font-mono">{{ stats?.total_odps || 0 }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Kotak pembagi fiber optic</div>
                    </div>
                    <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                        📦
                    </div>
                </div>
            </div>

            <!-- Toolbar: Search & Action -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-96">
                    <input 
                        v-model="search" 
                        @input="onSearch"
                        type="text" 
                        placeholder="Cari nama area, alamat, deskripsi..." 
                        class="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium text-slate-800"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <button 
                        v-if="search" 
                        @click="clearSearch" 
                        class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold"
                    >
                        ✕
                    </button>
                </div>

                <button 
                    @click="openCreateModal" 
                    class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-extrabold shadow-sm hover:shadow transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Area Baru</span>
                </button>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider w-16">No</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Nama Area / Wilayah</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Alamat &amp; Koordinat</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Sebaran Pelanggan &amp; ODP</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Deskripsi</th>
                                <th scope="col" class="px-6 py-4 text-right text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="(area, index) in areaList" :key="area.id" class="hover:bg-slate-50/60 transition-colors">
                                <!-- No -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-400">
                                    {{ getRowNumber(index) }}
                                </td>

                                <!-- Nama Area -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-600">
                                            📍
                                        </div>
                                        <div>
                                            <div class="text-sm font-extrabold text-slate-900">{{ area.name }}</div>
                                            <div v-if="area.radius_meters" class="text-[11px] text-slate-400 mt-0.5">
                                                Radius: {{ area.radius_meters }} meter
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Alamat & Koordinat -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-semibold text-slate-700">
                                        {{ area.address || '-' }}
                                    </div>
                                    <div v-if="area.latitude && area.longitude" class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <a 
                                            :href="`https://www.google.com/maps?q=${area.latitude},${area.longitude}`" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition-colors"
                                            title="Buka lokasi di Google Maps"
                                        >
                                            <span>🗺️</span>
                                            <span>{{ area.latitude }}, {{ area.longitude }}</span>
                                        </a>
                                    </div>
                                </td>

                                <!-- Sebaran Pelanggan & ODP -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <span>👥</span>
                                            <span>{{ area.customers_count || 0 }} Pelanggan</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-sky-50 text-sky-800 border border-sky-200">
                                            <span>📦</span>
                                            <span>{{ area.odps_count || 0 }} ODP</span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Deskripsi -->
                                <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                                    {{ area.description || '-' }}
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold space-x-2">
                                    <button 
                                        @click="openEditModal(area)" 
                                        class="px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 hover:border-transparent transition-all shadow-xs cursor-pointer active:scale-95"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        @click="deleteArea(area)" 
                                        class="px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-transparent transition-all shadow-xs cursor-pointer active:scale-95"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>

                            <!-- Baris Kosong -->
                            <tr v-if="areaList.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center text-slate-500">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-xl mx-auto mb-3">📍</div>
                                    <div class="text-sm font-bold text-slate-800">Belum ada data area / wilayah</div>
                                    <div class="text-xs text-slate-400 mt-0.5">Klik tombol "Tambah Area Baru" untuk mendaftarkan wilayah operasional baru.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="paginationLinks && paginationLinks.length > 3" class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Menampilkan <strong class="text-slate-800">{{ areas.from || 1 }}</strong> - <strong class="text-slate-800">{{ areas.to || areaList.length }}</strong> dari <strong class="text-slate-800">{{ areas.total || areaList.length }}</strong> area
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="link in paginationLinks" :key="link.label">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl text-xs font-bold transition-all" 
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-200'"
                            ></Link>
                            <span 
                                v-else 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl text-xs text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed"
                            ></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Area -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl text-lg">📍</span>
                        <h3 class="text-base font-black text-slate-900">{{ isEditing ? 'Edit Area / Wilayah' : 'Tambah Area Baru' }}</h3>
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submit">
                    <div class="p-6 space-y-4">
                        <!-- Nama Area -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Area / Wilayah <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 focus:bg-white transition-all" 
                                placeholder="Contoh: Area BC 1, Cikarang Utara, dsb." 
                                required 
                            />
                            <p v-if="form.errors.name" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.name }}</p>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Deskripsi</label>
                            <textarea 
                                v-model="form.description" 
                                rows="2" 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 focus:bg-white transition-all" 
                                placeholder="Keterangan tambahan cakupan wilayah (opsional)"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.description }}</p>
                        </div>

                        <!-- Alamat -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Alamat / Patokan Area</label>
                            <textarea 
                                v-model="form.address" 
                                rows="2" 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 focus:bg-white transition-all" 
                                placeholder="Contoh: Jl. Raya Lemah Abang No. 12"
                            ></textarea>
                            <p v-if="form.errors.address" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.address }}</p>
                        </div>

                        <!-- Titik Koordinat -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                <span>Titik Koordinat (Latitude &amp; Longitude)</span>
                                <span class="text-[10px] text-slate-400 font-normal">Opsional untuk pemetaan radar/map</span>
                            </label>
                            <div class="flex gap-2">
                                <input 
                                    v-model="form.latitude" 
                                    type="text" 
                                    @paste="handleCoordPaste"
                                    @input="onCoordInput('latitude')"
                                    class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 focus:bg-white transition-all" 
                                    placeholder="Latitude (-6.123456)" 
                                />
                                <input 
                                    v-model="form.longitude" 
                                    type="text" 
                                    @input="onCoordInput('longitude')"
                                    class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 focus:bg-white transition-all" 
                                    placeholder="Longitude (106.123456)" 
                                />
                                <button 
                                    type="button" 
                                    @click="getLocation" 
                                    :disabled="isGettingLocation"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer disabled:opacity-50" 
                                    title="Ambil Koordinat Lokasi Saat Ini (GPS)"
                                >
                                    <svg v-if="isGettingLocation" class="animate-spin h-3.5 w-3.5 text-slate-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>GPS</span>
                                </button>
                            </div>
                            <p v-if="form.errors.latitude || form.errors.longitude" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.latitude || form.errors.longitude }}</p>
                        </div>

                        <!-- Radius Cakupan (Meter) -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                <span>Radius Cakupan Area (Meter)</span>
                                <span class="text-[10px] text-slate-400 font-normal">Default: 500 meter</span>
                            </label>
                            <input 
                                v-model.number="form.radius_meters" 
                                type="number" 
                                min="0" 
                                step="50"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 focus:bg-white transition-all" 
                                placeholder="Contoh: 500" 
                            />
                            <p v-if="form.errors.radius_meters" class="text-rose-500 text-xs mt-1 font-semibold">{{ form.errors.radius_meters }}</p>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex justify-end gap-3">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 text-xs font-extrabold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-5 py-2 text-xs font-extrabold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 active:scale-95 transition-all shadow-sm disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Area' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
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
    areas: [Object, Array],
    stats: Object,
    filters: Object,
});

const areaList = computed(() => {
    if (Array.isArray(props.areas)) return props.areas;
    return props.areas?.data || [];
});

const paginationLinks = computed(() => {
    return props.areas?.links || null;
});

const getRowNumber = (index) => {
    const from = props.areas?.from || 1;
    return from + index;
};

const search = ref(props.filters?.search || '');
const isGettingLocation = ref(false);

const onSearch = debounce(() => {
    router.get(route('areas.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

const clearSearch = () => {
    search.value = '';
    router.get(route('areas.index'), { search: '' }, { preserveState: true, replace: true });
};

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    description: '',
    address: '',
    latitude: '',
    longitude: '',
    radius_meters: 500,
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.radius_meters = 500;
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(area) {
    isEditing.value = true;
    editId.value = area.id;
    form.name = area.name;
    form.description = area.description || '';
    form.address = area.address || '';
    form.latitude = area.latitude || '';
    form.longitude = area.longitude || '';
    form.radius_meters = area.radius_meters || 500;
    form.clearErrors();
    isModalOpen.value = true;
}

function getLocation() {
    if (!navigator.geolocation) {
        Swal.fire({
            icon: 'warning',
            title: 'Tidak Didukung',
            text: 'Geolocation tidak didukung oleh browser Anda.',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    isGettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude.toFixed(8);
            form.longitude = position.coords.longitude.toFixed(8);
            isGettingLocation.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Koordinat Diperoleh',
                text: `${form.latitude}, ${form.longitude}`,
                showConfirmButton: false,
                timer: 2500
            });
        },
        (error) => {
            isGettingLocation.value = false;
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mengambil Lokasi',
                text: error.message || 'Pastikan izin akses lokasi di browser diizinkan.',
                confirmButtonColor: '#4f46e5'
            });
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function handleCoordPaste(e) {
    const text = (e.clipboardData || window.clipboardData)?.getData('text');
    if (!text) return;
    const trimmed = text.trim();
    // Match pair coordinate: "-6.9587311, 106.7747203" or "-6,9587311, 106,7747203" or space-separated
    const match = trimmed.match(/^(-?\d+(?:[.,]\d+)?)\s*[,/ ]\s*(-?\d+(?:[.,]\d+)?)$/);
    if (match) {
        e.preventDefault();
        form.latitude = match[1].replace(',', '.');
        form.longitude = match[2].replace(',', '.');
        form.clearErrors('latitude', 'longitude');
    }
}

function onCoordInput(field) {
    if (form.errors[field]) {
        form.clearErrors(field);
    }
}

function submit() {
    // Auto-sanitize coordinates (convert commas to dots and trim)
    if (form.latitude !== null && form.latitude !== undefined) {
        let latStr = String(form.latitude).trim();
        const pairMatch = latStr.match(/^(-?\d+(?:[.,]\d+)?)\s*[,/ ]\s*(-?\d+(?:[.,]\d+)?)$/);
        if (pairMatch && !form.longitude) {
            form.latitude = pairMatch[1].replace(',', '.');
            form.longitude = pairMatch[2].replace(',', '.');
        } else {
            form.latitude = latStr === '' ? null : latStr.replace(',', '.');
        }
    }
    if (form.longitude !== null && form.longitude !== undefined) {
        let lngStr = String(form.longitude).trim();
        form.longitude = lngStr === '' ? null : lngStr.replace(',', '.');
    }

    const url = isEditing.value 
        ? route('areas.update.post', editId.value)
        : route('areas.store');

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil!',
                text: isEditing.value ? 'Area berhasil diperbarui.' : 'Area baru berhasil ditambahkan.',
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
                confirmButtonColor: '#4f46e5'
            });
        }
    });
}

function deleteArea(area) {
    Swal.fire({
        title: 'Hapus Area?',
        html: `Apakah Anda yakin ingin menghapus area <strong>${area.name}</strong>?<br><span class="text-xs text-slate-500">Area yang masih memiliki pelanggan atau ODP aktif tidak dapat dihapus.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Area',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('areas.destroy.post', area.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Terhapus',
                        text: `Area ${area.name} berhasil dihapus.`,
                        showConfirmButton: false,
                        timer: 2500
                    });
                },
                onError: (err) => {
                    const firstErr = Object.values(err || {})[0] || 'Gagal menghapus area.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Tidak Dapat Dihapus',
                        text: firstErr,
                        confirmButtonColor: '#e11d48'
                    });
                }
            });
        }
    });
}
</script>
