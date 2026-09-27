<template>
    <AppLayout title="Data ODP">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ODP</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data Optical Distribution Point (ODP)</p>
                </div>
                <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah ODP
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama ODP</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Induk ODC</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="odp in odps.data" :key="odp.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ odp.name }}
                                <span v-if="odp.is_split" class="ml-2 px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">Split</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ odp.odc ? odp.odc.name : '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ odp.used_ports }} / {{ odp.total_ports }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="odp.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ odp.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <Link :href="`/odps/${odp.id}`" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                <button @click="openEditModal(odp)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                <button @click="deleteOdp(odp.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="odps.data.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                                Belum ada data ODP.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah/Edit ODP -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit ODP' : 'Tambah ODP Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        
                        <!-- Informasi Dasar -->
                        <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-white px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span class="text-sm font-semibold text-purple-900">Informasi Dasar</span>
                            </div>
                            <div class="p-4 grid grid-cols-2 gap-4">
                                <div v-if="form.type !== 'Split 2'">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODP</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ODP-01-ABC" />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div :class="form.type === 'Split 2' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Induk ODC</label>
                                    <select v-model="form.odc_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="">Pilih ODC Induk</option>
                                        <option v-for="odc in odcs" :key="odc.id" :value="odc.id">{{ odc.name }}</option>
                                    </select>
                                    <p v-if="form.errors.odc_id" class="text-red-500 text-xs mt-1">{{ form.errors.odc_id }}</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Cabang</label>
                                    <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="">Pilih Cabang</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                                </div>
                                
                                <div v-if="isBranchMismatch" class="col-span-2 bg-orange-50 border border-orange-200 rounded-lg p-3 flex gap-3">
                                    <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <div>
                                        <h4 class="text-sm font-bold text-orange-800">Cabang ODP tidak sesuai dengan ODC!</h4>
                                        <p class="text-xs text-orange-700 mt-1">ODC terdaftar di cabang yang berbeda dengan cabang ODP yang Anda pilih. Pastikan data cabang sudah benar.</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tipe ODP</label>
                                    <select v-model="form.type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="Normal">Normal</option>
                                        <option value="Split 2">Split 2</option>
                                    </select>
                                    <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas Port</label>
                                    <input v-model="form.total_ports" type="number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                    <p v-if="form.errors.total_ports" class="text-red-500 text-xs mt-1">{{ form.errors.total_ports }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Split 2 -->
                        <div v-if="form.type === 'Split 2'" class="space-y-4">
                            <!-- Alert Split 2 -->
                            <div class="bg-purple-50 text-purple-800 px-4 py-3 rounded-lg border border-purple-200 text-sm flex items-start gap-3">
                                <svg class="w-5 h-5 text-purple-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p><span class="font-bold">Split 2:</span> ODP dibagi menjadi 2 unit dengan konfigurasi khusus.</p>
                                    <p class="text-xs mt-1 opacity-80" :class="totalSplitPorts > form.total_ports ? 'text-red-600 font-bold' : ''">
                                        Total Port Unit: {{ totalSplitPorts }} / {{ form.total_ports }} 
                                        <span v-if="totalSplitPorts > form.total_ports">(Melebihi kapasitas utama!)</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="flex gap-2 bg-gray-100 p-1 rounded-lg">
                                <button type="button" @click="activeTab = 0" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 0 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODP Induk 1:{{ form.split_units[0].ratio }}
                                </button>
                                <button type="button" @click="activeTab = 1" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 1 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODP Lanjutan 1:{{ form.split_units[1].ratio }}
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <span class="text-sm font-semibold text-purple-900">
                                        {{ activeTab === 0 ? 'ODP Induk' : 'ODP Lanjutan' }} 1:{{ form.split_units[activeTab].ratio }}
                                    </span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODP Unit</label>
                                            <input v-model="form.split_units[activeTab].name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Rasio</label>
                                            <input v-model="form.split_units[activeTab].ratio" type="number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat</label>
                                        <textarea v-model="form.split_units[activeTab].address" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Alamat lokasi ODP"></textarea>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Titik Koordinat</label>
                                            <button type="button" @click="getLocationForSplit(activeTab)" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Auto GPS
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input v-model="form.split_units[activeTab].latitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Latitude" />
                                            <input v-model="form.split_units[activeTab].longitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Longitude" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Titik Awal</label>
                                            <input v-model="form.split_units[activeTab].start_point" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Titik Akhir Kabel</label>
                                            <input v-model="form.split_units[activeTab].end_point" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Penarikan Kabel</label>
                                        <select v-model="form.split_units[activeTab].cable_pull" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="Individu">Individu</option>
                                            <option value="Bersama">Bersama</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODP</label>
                                        <input type="file" @change="e => form.split_units[activeTab].photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Normal -->
                        <div v-if="form.type === 'Normal'" class="space-y-6">
                            <!-- Alamat -->
                            <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-green-900">Alamat</span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat lokasi ODP</label>
                                        <textarea v-model="form.address" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                        <p v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Titik Koordinat</label>
                                            <button type="button" @click="getLocation" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Auto GPS
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input v-model="form.latitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Latitude" />
                                            <input v-model="form.longitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Longitude" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detail ODP -->
                            <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-purple-900">Detail ODP</span>
                                </div>
                                <div class="p-4 grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Status</label>
                                        <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="full">Full</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Keterangan Tambahan</label>
                                        <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="sticky bottom-0 bg-white p-6 border-t border-gray-100 flex justify-end rounded-b-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-[#1e40af] rounded-lg hover:bg-blue-900 disabled:opacity-50">
                            Simpan ODP
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odps: Object,
    odcs: Array,
    areas: Array,
    filters: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const activeTab = ref(0);

const form = useForm({
    name: '',
    odc_id: '',
    area_id: '',
    type: 'Normal',
    total_ports: 8,
    address: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: '',
    split_units: [
        {
            name: 'ODP Induk 1:4',
            ratio: 4,
            address: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODP Induk 1:4',
            cable_pull: 'Individu',
            photo: null
        },
        {
            name: 'ODP Lanjutan 1:4',
            ratio: 4,
            address: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODP Lanjutan 1:4',
            cable_pull: 'Individu',
            photo: null
        }
    ]
});

// Initialize default ratio when type changes to Split 2
watch(() => form.type, (newType) => {
    if (newType === 'Split 2') {
        const half = Math.floor(form.total_ports / 2);
        form.split_units[0].ratio = half;
        form.split_units[1].ratio = half;
        form.split_units[0].name = `ODP Induk 1:${half}`;
        form.split_units[1].name = `ODP Lanjutan 1:${half}`;
        form.split_units[0].end_point = `ODP Induk 1:${half}`;
        form.split_units[1].end_point = `ODP Lanjutan 1:${half}`;
    }
});

// Watch ratio changes to update names dynamically (only if they still match the default pattern)
watch(() => form.split_units[0].ratio, (newRatio, oldRatio) => {
    if (form.split_units[0].name === `ODP Induk 1:${oldRatio}` || !form.split_units[0].name) {
        form.split_units[0].name = `ODP Induk 1:${newRatio}`;
    }
    if (form.split_units[0].end_point === `ODP Induk 1:${oldRatio}` || !form.split_units[0].end_point) {
        form.split_units[0].end_point = `ODP Induk 1:${newRatio}`;
    }
});

watch(() => form.split_units[1].ratio, (newRatio, oldRatio) => {
    if (form.split_units[1].name === `ODP Lanjutan 1:${oldRatio}` || !form.split_units[1].name) {
        form.split_units[1].name = `ODP Lanjutan 1:${newRatio}`;
    }
    if (form.split_units[1].end_point === `ODP Lanjutan 1:${oldRatio}` || !form.split_units[1].end_point) {
        form.split_units[1].end_point = `ODP Lanjutan 1:${newRatio}`;
    }
});

const totalSplitPorts = computed(() => {
    return Number(form.split_units[0].ratio || 0) + Number(form.split_units[1].ratio || 0);
});

const isBranchMismatch = computed(() => {
    if (!form.odc_id || !form.area_id) return false;
    const odc = props.odcs.find(o => o.id === form.odc_id);
    if (!odc || !odc.area_id) return false;
    return odc.area_id !== form.area_id;
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function openEditModal(odp) {
    isEditing.value = true;
    editId.value = odp.id;
    form.name = odp.name;
    form.odc_id = odp.odc_id;
    form.area_id = odp.area_id;
    form.type = odp.type || 'Normal';
    form.total_ports = odp.total_ports;
    form.address = odp.address;
    form.latitude = odp.latitude;
    form.longitude = odp.longitude;
    form.status = odp.status;
    form.description = odp.description;
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.latitude = position.coords.latitude.toFixed(8);
                form.longitude = position.coords.longitude.toFixed(8);
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
}

function getLocationForSplit(tabIndex) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.split_units[tabIndex].latitude = position.coords.latitude.toFixed(8);
                form.split_units[tabIndex].longitude = position.coords.longitude.toFixed(8);
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
}

function submit() {
    if (isEditing.value) {
        form.post(`/odps/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/odps', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOdp(id) {
    if (confirm('Apakah Anda yakin ingin menghapus ODP ini?')) {
        router.post(`/odps/${id}/delete`);
    }
}
</script>
