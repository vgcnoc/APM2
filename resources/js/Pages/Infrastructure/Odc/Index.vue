<template>
    <AppLayout title="Data ODC">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ODC</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data Optical Distribution Cabinet (ODC)</p>
                </div>
                <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah ODC
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama ODC</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Induk OLT</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kapasitas</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="odc in odcs.data" :key="odc.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ odc.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="font-medium text-gray-900">{{ odc.olt ? odc.olt.name : '-' }}</div>
                                <div v-if="odc.pon_port" class="text-xs text-indigo-600 font-semibold mt-0.5">
                                    PON {{ odc.pon_port }}
                                    <span v-if="getPonVlansText(odc)" class="text-gray-500 font-normal ml-1 bg-gray-100 px-1.5 py-0.5 rounded">
                                        {{ getPonVlansText(odc) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ odc.capacity }} Port</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="odc.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ odc.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <Link :href="route('odcs.show', odc.id)" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                <button @click="openEditModal(odc)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                <button @click="deleteOdc(odc.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="odcs.data.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                                Belum ada data ODC.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah/Edit ODC -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit ODC' : 'Tambah ODC Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        
                        <!-- Informasi Dasar -->
                        <div class="border border-indigo-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-indigo-50/50 px-4 py-3 border-b border-indigo-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span class="text-sm font-semibold text-indigo-900">Informasi Dasar</span>
                            </div>
                            <div class="p-4 grid grid-cols-2 gap-4">
                                <div v-if="form.type !== 'Split'">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODC</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ODC-01" />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div :class="form.type === 'Split' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Induk OLT</label>
                                    <select v-model="form.olt_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="">Pilih OLT Induk</option>
                                        <option v-for="olt in olts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                                    </select>
                                    <p v-if="form.errors.olt_id" class="text-red-500 text-xs mt-1">{{ form.errors.olt_id }}</p>
                                </div>
                                <div v-if="form.olt_id" :class="form.type === 'Split' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port PON (Opsional)</label>
                                    <select v-model="form.pon_port" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Pilih Port PON (Opsional)</option>
                                        <option v-for="port in selectedOltPonPorts" :key="port" :value="port">PON {{ port }}</option>
                                    </select>
                                    <p v-if="form.errors.pon_port" class="text-red-500 text-xs mt-1">{{ form.errors.pon_port }}</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Cabang (Area)</label>
                                    <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Pilih Cabang (Opsional)</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tipe ODC</label>
                                    <select v-model="form.type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="Normal">Normal</option>
                                        <option value="Split">Split</option>
                                    </select>
                                    <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas (Port)</label>
                                    <input v-model="form.capacity" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                    <p v-if="form.errors.capacity" class="text-red-500 text-xs mt-1">{{ form.errors.capacity }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Split -->
                        <div v-if="form.type === 'Split'" class="space-y-4">
                            <!-- Alert Split -->
                            <div class="bg-indigo-50 text-indigo-800 px-4 py-3 rounded-lg border border-indigo-200 text-sm flex items-start gap-3">
                                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p><span class="font-bold">Split:</span> ODC dibagi menjadi 2 unit dengan konfigurasi khusus.</p>
                                    <p class="text-xs mt-1 opacity-80" :class="totalSplitPorts > form.capacity ? 'text-red-600 font-bold' : ''">
                                        Total Kapasitas Unit: {{ totalSplitPorts }} / {{ form.capacity }} 
                                        <span v-if="totalSplitPorts > form.capacity">(Melebihi kapasitas utama!)</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="flex gap-2 bg-gray-100 p-1 rounded-lg">
                                <button type="button" @click="activeTab = 0" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 0 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODC Induk (Kapasitas: {{ form.split_units[0].ratio }})
                                </button>
                                <button type="button" @click="activeTab = 1" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 1 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODC Lanjutan (Kapasitas: {{ form.split_units[1].ratio }})
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="border border-indigo-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-indigo-100 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span class="text-sm font-semibold text-indigo-900">
                                        {{ activeTab === 0 ? 'ODC Induk' : 'ODC Lanjutan' }}
                                    </span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODC Unit</label>
                                            <input v-model="form.split_units[activeTab].name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas</label>
                                            <input v-model="form.split_units[activeTab].ratio" type="number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat ODC</label>
                                        <textarea v-model="form.split_units[activeTab].location" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Alamat lokasi ODC"></textarea>
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
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODC</label>
                                        <input type="file" @change="e => form.split_units[activeTab].photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" accept="image/*" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Normal -->
                        <div v-if="form.type === 'Normal'" class="space-y-6">
                            <!-- Lokasi -->
                            <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-green-50/50 px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-green-900">Lokasi & Koordinat</span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat ODC</label>
                                        <textarea v-model="form.location" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Masukkan alamat lengkap"></textarea>
                                        <p v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</p>
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
                            
                            <!-- Detail -->
                            <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-purple-50/50 px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-purple-900">Detail & Status</span>
                                </div>
                                <div class="p-4 grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Status</label>
                                        <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="maintenance">Maintenance</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODC</label>
                                        <input type="file" @change="e => form.photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" accept="image/*" />
                                        <p v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</p>
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
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan ODC
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odcs: Object,
    olts: Array,
    areas: Array,
    filters: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const activeTab = ref(0);

const form = useForm({
    name: '',
    olt_id: '',
    pon_port: '',
    area_id: '',
    type: 'Normal',
    capacity: 144,
    location: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: '',
    photo: null,
    split_units: [
        {
            name: 'ODC Induk',
            ratio: 72,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODC Induk',
            cable_pull: 'Individu',
            photo: null
        },
        {
            name: 'ODC Lanjutan',
            ratio: 72,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODC Lanjutan',
            cable_pull: 'Individu',
            photo: null
        }
    ]
});

// Initialize default ratio when type changes to Split
watch(() => form.type, (newType) => {
    if (newType === 'Split') {
        const half = Math.floor(form.capacity / 2);
        form.split_units[0].ratio = half;
        form.split_units[1].ratio = half;
    }
});

const selectedOltPonPorts = computed(() => {
    if (!form.olt_id) return [];
    const olt = props.olts.find(o => o.id === form.olt_id);
    if (!olt || !olt.total_pon_ports) return [];
    return Array.from({ length: olt.total_pon_ports }, (_, i) => i + 1);
});

// Watch changes to olt_id to reset pon_port if necessary
watch(() => form.olt_id, (newVal) => {
    if (newVal) {
        const olt = props.olts.find(o => o.id === newVal);
        if (olt && olt.total_pon_ports && form.pon_port > olt.total_pon_ports) {
            form.pon_port = '';
        }
    } else {
        form.pon_port = '';
    }
});

const totalSplitPorts = computed(() => {
    return Number(form.split_units[0].ratio || 0) + Number(form.split_units[1].ratio || 0);
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function openEditModal(odc) {
    isEditing.value = true;
    editId.value = odc.id;
    form.name = odc.name;
    form.olt_id = odc.olt_id;
    form.pon_port = odc.pon_port || '';
    form.area_id = odc.area_id || '';
    form.type = odc.type || 'Normal';
    form.capacity = odc.capacity;
    form.location = odc.location;
    form.latitude = odc.latitude;
    form.longitude = odc.longitude;
    form.status = odc.status;
    form.description = odc.description;
    form.photo = null;
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

function getPonVlansText(odc) {
    if (!odc.olt || !odc.pon_port || !odc.olt.pon_vlans) return '';
    const ponData = odc.olt.pon_vlans.find(p => p.port == odc.pon_port);
    if (!ponData || !ponData.vlans || ponData.vlans.length === 0) return '';
    return ponData.vlans.map(v => `${v.name} (${v.vlan_id})`).join(', ');
}

function submit() {
    if (isEditing.value) {
        form.post(`/odcs/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form._method = 'post';
        form.post('/odcs', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOdc(id) {
    if (confirm('Apakah Anda yakin ingin menghapus ODC ini?')) {
        router.post(`/odcs/${id}/delete`);
    }
}
</script>
