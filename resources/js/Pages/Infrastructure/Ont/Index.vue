<template>
    <AppLayout title="Data ONT">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ONT / Modem</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data inventaris perangkat ONT. (Data otomatis ditambahkan dari Surat Jalan Gudang)</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Perangkat</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Cabang</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan (Instalasi)</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Akses / VLAN</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="ont in onts.data" :key="ont.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900 font-mono">{{ ont.serial_number }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ ont.brand || 'Unknown' }} {{ ont.model || '' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ ont.area ? ont.area.name : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="ont.customer">
                                        <div class="text-sm font-medium text-gray-900">{{ ont.customer.name }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ ont.customer.customer_code }}</div>
                                    </div>
                                    <div v-else class="text-sm text-gray-400 italic">Belum terpasang</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ ont.access_mode || '-' }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ ont.vlan_mode || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="{
                                        'bg-green-100 text-green-700': ont.status === 'active',
                                        'bg-blue-100 text-blue-700': ont.status === 'Sudah Set',
                                        'bg-red-100 text-red-700': ont.status === 'los',
                                        'bg-yellow-100 text-yellow-700': ont.status === 'Belum Set/Baru Input',
                                        'bg-gray-100 text-gray-700': !['active', 'Sudah Set', 'los', 'Belum Set/Baru Input'].includes(ont.status),
                                    }">
                                        {{ ont.status || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <Link :href="route('onts.show', ont.id)" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                    <button @click="openEditModal(ont)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                    <button @click="deleteOnt(ont.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="onts.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    Belum ada data inventaris ONT.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Form ONT -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit ONT' : 'Tambah ONT Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        
                        <!-- Cabang -->
                        <div class="border border-purple-100 rounded-xl overflow-hidden bg-white p-4">
                            <label class="block text-xs font-bold text-purple-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Cabang
                            </label>
                            <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Pilih cabang</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                            </select>
                            <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                        </div>

                        <!-- Data Perangkat -->
                        <div class="border border-blue-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-blue-50/50 px-4 py-3 border-b border-blue-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                    <span class="text-sm font-semibold text-blue-900">Data Perangkat</span>
                                </div>
                            </div>
                            <div class="p-4 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Merk ONT</label>
                                    <input v-model="form.brand" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Merk ONT" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tipe ONT</label>
                                    <input v-model="form.model" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tipe ONT" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Serial Number</label>
                                    <input v-model="form.serial_number" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Serial Number" required />
                                    <p v-if="form.errors.serial_number" class="text-red-500 text-xs mt-1">{{ form.errors.serial_number }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">MAC Address</label>
                                    <input v-model="form.mac_address" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="AA:BB:CC:DD:EE:FF" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">VLAN Mode</label>
                                    <select v-model="form.vlan_mode" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="Non-VLAN">Non-VLAN</option>
                                        <option value="VLAN">VLAN</option>
                                    </select>
                                </div>
                                <div v-if="form.vlan_mode === 'VLAN'">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">VLAN ID</label>
                                    <input v-model="form.vlan_id" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: 100" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Mode Akses</label>
                                    <select v-model="form.access_mode" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="PPPOE">PPPOE</option>
                                        <option value="Static">Static</option>
                                        <option value="DHCP">DHCP</option>
                                        <option value="Bridge">Bridge</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="px-4 pb-4">
                                <h4 class="text-xs font-bold text-gray-700 mt-4 mb-2">IP Login ONT</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">No IP</label>
                                        <input v-model="form.ip_login" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="192.168.1.1" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">User</label>
                                        <input v-model="form.login_user" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Username login" />
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs text-gray-500 mb-1">Password</label>
                                        <input v-model="form.login_password" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password login" />
                                    </div>
                                </div>
                            </div>

                            <div class="px-4 pb-4 border-t border-gray-100 pt-4">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-xs font-bold text-gray-700">Akun PPPoE</h4>
                                    <button type="button" @click="generatePppoe" class="text-xs font-medium bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-md border border-gray-300 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Generate
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">User</label>
                                        <input v-model="form.pppoe_user" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="User PPPoE" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Password</label>
                                        <input v-model="form.pppoe_password" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password PPPoE" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Petugas Input -->
                        <div class="border border-red-100 rounded-xl overflow-hidden bg-white bg-red-50/10">
                            <div class="px-4 py-3 border-b border-red-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-sm font-semibold text-red-800">Petugas Input</span>
                                </div>
                                <button type="button" @click="addOfficer" class="text-xs font-medium text-red-600 bg-white border border-red-200 hover:bg-red-50 px-3 py-1 rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Petugas
                                </button>
                            </div>
                            
                            <div class="p-4 space-y-4">
                                <div v-for="(officer, index) in form.input_officers" :key="index" class="bg-white p-4 rounded-lg border border-red-100 relative shadow-sm">
                                    <button v-if="form.input_officers.length > 1" type="button" @click="removeOfficer(index)" class="absolute top-3 right-3 text-gray-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    
                                    <h5 class="text-xs font-bold text-red-500 mb-3">Petugas {{ index + 1 }}</h5>
                                    
                                    <div class="grid grid-cols-2 gap-4 mb-3">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Nama Petugas Input ONT</label>
                                            <input v-model="officer.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="Pilih atau ketik petugas input" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Tanggal</label>
                                            <input v-model="officer.date" type="date" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Keterangan</label>
                                        <textarea v-model="officer.note" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="Keterangan tambahan..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Status ONT</label>
                            <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="Belum Set/Baru Input">Belum Set/Baru Input</option>
                                <option value="Sudah Set">Sudah Set (Siap Pasang)</option>
                                <option value="active">Active (Terpasang)</option>
                                <option value="inactive">Inactive</option>
                                <option value="los">LOS</option>
                                <option value="damaged">Damaged</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sticky bottom-0 bg-white p-6 border-t border-gray-100 flex justify-end rounded-b-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan ONT
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    onts: Object,
    areas: Array,
    filters: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    area_id: '',
    brand: '',
    model: '',
    serial_number: '',
    mac_address: '',
    vlan_mode: 'Non-VLAN',
    vlan_id: '',
    access_mode: 'PPPOE',
    ip_login: '192.168.1.1',
    login_user: '',
    login_password: '',
    pppoe_user: '',
    pppoe_password: '',
    input_officers: [
        { name: '', date: new Date().toISOString().split('T')[0], note: '' }
    ],
    status: 'Belum Set/Baru Input',
    id: null
});

function openCreateModal() {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.id = null;
    isModalOpen.value = true;
}

function openEditModal(ont) {
    isEditing.value = true;
    form.id = ont.id;
    form.area_id = ont.area_id || '';
    form.brand = ont.brand || '';
    form.model = ont.model || '';
    form.serial_number = ont.serial_number || '';
    form.mac_address = ont.mac_address || '';
    form.vlan_mode = ont.vlan_mode || 'Non-VLAN';
    form.vlan_id = ont.vlan_id || '';
    form.access_mode = ont.access_mode || 'PPPOE';
    form.ip_login = ont.ip_login || '192.168.1.1';
    form.login_user = ont.login_user || '';
    form.login_password = ont.login_password || '';
    form.pppoe_user = ont.pppoe_user || '';
    form.pppoe_password = ont.pppoe_password || '';
    
    if (ont.input_officers && ont.input_officers.length > 0) {
        form.input_officers = JSON.parse(JSON.stringify(ont.input_officers));
    } else {
        form.input_officers = [{ name: '', date: new Date().toISOString().split('T')[0], note: '' }];
    }
    
    form.status = ont.status || 'Belum Set/Baru Input';
    
    form.clearErrors();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function addOfficer() {
    form.input_officers.push({
        name: '',
        date: new Date().toISOString().split('T')[0],
        note: ''
    });
}

function removeOfficer(index) {
    form.input_officers.splice(index, 1);
}

function generatePppoe() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let user = '';
    let pass = '';
    for(let i = 0; i < 6; i++) user += chars.charAt(Math.floor(Math.random() * chars.length));
    for(let i = 0; i < 8; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    
    form.pppoe_user = 'user_' + user;
    form.pppoe_password = pass;
}

function submit() {
    if (isEditing.value && form.id) {
        form.post(`/onts/${form.id}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/onts', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOnt(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data inventaris ONT ini?')) {
        router.post(`/onts/${id}/delete`);
    }
}
</script>
