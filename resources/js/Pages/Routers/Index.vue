<template>
    <AppLayout title="Data Router">
        <div class="p-6 max-w-6xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Data Router</h2>
                        <p class="text-sm text-gray-500 mt-1">Kelola data router Mikrotik, coverage area, dan hak akses pengguna.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Filter Area -->
                        <div class="w-full sm:w-auto">
                            <select 
                                v-model="filterArea" 
                                @change="applyFilter" 
                                class="w-full sm:w-48 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">Semua Area</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    📍 {{ area.name }}
                                </option>
                            </select>
                        </div>

                        <button @click="openCreateModal" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span>Tambah Data Router</span>
                        </button>
                    </div>
                </div>
                
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Router</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Area</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port API</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port Winbox</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">PIC / Penanggung Jawab</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Koneksi</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="router in routers.data" :key="router.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-bold text-gray-900 font-mono">{{ router.name }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span v-if="router.area" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ router.area.name }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400 italic">
                                        - Belum Ditentukan -
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ router.api_port }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ router.winbox_port }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ router.pic ? router.pic.name : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    <span v-if="connectionStatuses[router.id] === 'online'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Online
                                    </span>
                                    <span v-else-if="connectionStatuses[router.id] === 'offline'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Offline
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                                        <svg class="animate-spin h-3 w-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Memeriksa...
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <button @click="openEditModal(router)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                    <button @click="deleteRouter(router.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="routers.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    Belum ada data Router.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2" v-if="routers.links && routers.links.length > 3">
                    <template v-for="(link, idx) in routers.links" :key="idx">
                        <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded text-sm" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"></Link>
                        <span v-else v-html="link.label" class="px-3 py-1 border rounded bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed text-sm"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-auto overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Data Router' : 'Tambah Data Router' }}</h3>
                        <p class="text-xs text-gray-500">Konfigurasi data router Mikrotik, coverage area, dan port akses.</p>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                        <!-- Informasi Router & Area -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                            <h4 class="font-bold text-sm text-indigo-700 flex items-center gap-2 mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Informasi Router & Area
                            </h4>
                            <div class="bg-red-50 text-red-700 p-3 rounded-lg text-xs mb-4 border border-red-100">
                                <span class="font-bold">Baca dulu sebelum lanjut.</span><br>
                                Jangan mengganti Identity Mikrotik setelah data router dibuat agar relasi data tetap konsisten.
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Nama Router -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Router <span class="text-red-500">*Req</span></label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase" placeholder="Gunakan HURUF BESAR" required />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>

                                <!-- KOLOM PENAMBAHAN AREA -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Area / Kelompok Wilayah
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="toggleCustomArea" 
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 transition-colors"
                                        >
                                            <span v-if="!isCustomArea">+ Buat Area Baru</span>
                                            <span v-else>← Pilih dari Daftar Area</span>
                                        </button>
                                    </div>

                                    <!-- Mode 1: Pilih dari daftar area -->
                                    <div v-if="!isCustomArea">
                                        <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option :value="null">-- Pilih Area / Kelompok --</option>
                                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                                📍 {{ area.name }}
                                            </option>
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Catatan pengelompokan wilayah router (opsional, tidak membatasi/mempengaruhi koneksi pelanggan).</p>
                                    </div>

                                    <!-- Mode 2: Ketik nama area baru -->
                                    <div v-else class="space-y-1.5">
                                        <div class="relative">
                                            <input 
                                                v-model="form.new_area_name" 
                                                type="text" 
                                                class="w-full pl-9 pr-4 py-2.5 bg-white border border-indigo-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                                placeholder="Ketik nama area baru (contoh: Area Barat, Desa Sukamaju, dll)..." 
                                            />
                                            <svg class="w-4 h-4 text-indigo-500 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs text-indigo-600 font-medium">Area baru ini akan disimpan sebagai label kelompok wilayah router.</p>
                                    </div>
                                    <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                                    <p v-if="form.errors.new_area_name" class="text-red-500 text-xs mt-1">{{ form.errors.new_area_name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Port Akses -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                            <h4 class="font-bold text-sm text-indigo-700 flex items-center gap-2 mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Port Akses
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port API</label>
                                    <input v-model="form.api_port" type="number" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Default : 8728" />
                                    <p class="text-xs text-gray-500 mt-1">Abaikan jika menggunakan port default. Harus sama dengan yang di Mikrotik</p>
                                    <p v-if="form.errors.api_port" class="text-red-500 text-xs mt-1">{{ form.errors.api_port }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port Winbox</label>
                                    <input v-model="form.winbox_port" type="number" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Default : 8291" />
                                    <p class="text-xs text-gray-500 mt-1">Abaikan jika menggunakan port default. Harus sama dengan yang di Mikrotik</p>
                                    <p v-if="form.errors.winbox_port" class="text-red-500 text-xs mt-1">{{ form.errors.winbox_port }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Hak Akses -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                            <h4 class="font-bold text-sm text-indigo-700 flex items-center gap-2 mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                Hak Akses
                            </h4>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assign To / Penanggung Jawab / PIC / Investor</label>
                                <select v-model="form.pic_id" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option :value="null">-- Pilih Pengguna --</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                                <p v-if="form.errors.pic_id" class="text-red-500 text-xs mt-1">{{ form.errors.pic_id }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Tutup
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    routers: Object,
    users: Array,
    areas: Array,
    filters: Object,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const isCustomArea = ref(false);
const filterArea = ref(props.filters?.area_id || '');

const form = useForm({
    name: '',
    area_id: null,
    new_area_name: '',
    api_port: '',
    winbox_port: '',
    pic_id: null,
});

function toggleCustomArea() {
    isCustomArea.value = !isCustomArea.value;
    if (!isCustomArea.value) {
        form.new_area_name = '';
    } else {
        form.area_id = null;
    }
}

function applyFilter() {
    router.get('/routers', { area_id: filterArea.value || undefined }, { preserveState: true, replace: true });
}

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    isCustomArea.value = false;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(router) {
    isEditing.value = true;
    editId.value = router.id;
    isCustomArea.value = false;
    form.name = router.name;
    form.area_id = router.area_id || null;
    form.new_area_name = '';
    form.api_port = router.api_port == 8728 ? '' : router.api_port;
    form.winbox_port = router.winbox_port == 8291 ? '' : router.winbox_port;
    form.pic_id = router.pic_id;
    form.clearErrors();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => {
        form.reset();
        isCustomArea.value = false;
    }, 300);
}

function submit() {
    if (isEditing.value) {
        form.post(`/routers/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/routers', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteRouter(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data Router ini?')) {
        router.post(`/routers/${id}/delete`);
    }
}

const connectionStatuses = ref({});

function checkConnections() {
    if (!props.routers?.data) return;
    props.routers.data.forEach(r => {
        connectionStatuses.value[r.id] = 'loading';
        axios.get(`/routers/${r.id}/ping`)
            .then(res => {
                connectionStatuses.value[r.id] = res.data.status;
            })
            .catch(() => {
                connectionStatuses.value[r.id] = 'offline';
            });
    });
}

onMounted(() => {
    checkConnections();
});
</script>
