<template>
    <AppLayout title="Data OLT">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen OLT</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data Optical Line Terminal (OLT)</p>
                </div>
                <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah OLT
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama OLT</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">IP Address / Domain</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Brand / Model</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port PON</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="olt in olts.data" :key="olt.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ olt.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span v-if="olt.area" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-bold border border-blue-100">{{ olt.area.name }}</span>
                                <span v-else class="text-xs text-gray-400 italic">Belum diset</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ olt.ip_address || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ olt.brand || '-' }} {{ olt.model ? '('+olt.model+')' : '' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="font-bold text-gray-900">{{ olt.total_pon_ports }} Port PON</div>
                                <div v-if="getOltVlans(olt).length > 0" class="flex flex-wrap gap-1 mt-1">
                                    <span v-for="(v, vi) in getOltVlans(olt).slice(0, 4)" :key="vi" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                        <span>{{ v.vlan_id }}</span>
                                        <span class="opacity-75 text-[9px]">({{ v.access_mode }})</span>
                                    </span>
                                    <span v-if="getOltVlans(olt).length > 4" class="px-1 py-0.5 rounded text-[10px] bg-gray-100 text-gray-600 font-medium">
                                        +{{ getOltVlans(olt).length - 4 }}
                                    </span>
                                </div>
                                <div v-else class="text-[11px] text-gray-400 italic mt-0.5">Belum diset VLAN</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="olt.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ olt.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <Link :href="`/olts/${olt.id}`" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                <button @click="openEditModal(olt)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                <button @click="deleteOlt(olt.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="olts.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                Belum ada data OLT.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah/Edit OLT -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit OLT' : 'Tambah OLT Baru' }}</h3>
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
                                <span class="text-sm font-semibold text-indigo-900">Spesifikasi OLT</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Area / Wilayah</label>
                                    <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="" disabled>Pilih Area</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama OLT</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: OLT-ZTE-01" required />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Hostname (Opsional)</label>
                                    <input v-model="form.hostname" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: olt1.isp.net" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">IP Address / Domain (Opsional)</label>
                                    <input v-model="form.ip_address" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="192.168.1.1 atau vpn.viruzs.my.id:45" />
                                    <p class="text-[11px] text-gray-400 mt-1">Dapat diisi IP Address atau Domain / Hostname (dengan atau tanpa port).</p>
                                    <p v-if="form.errors.ip_address" class="text-red-500 text-xs mt-1">{{ form.errors.ip_address }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Jumlah Port PON</label>
                                    <input v-model="form.total_pon_ports" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                    <p v-if="form.errors.total_pon_ports" class="text-red-500 text-xs mt-1">{{ form.errors.total_pon_ports }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas (ONT per PON)</label>
                                    <input v-model="form.pon_capacity" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Default: 64" />
                                    <p v-if="form.errors.pon_capacity" class="text-red-500 text-xs mt-1">{{ form.errors.pon_capacity }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Brand / Merk</label>
                                    <input v-model="form.brand" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ZTE" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Model</label>
                                    <input v-model="form.model" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: C320" />
                                </div>
                            </div>
                        </div>

                        <!-- Lokasi -->
                        <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-green-50/50 px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                <span class="text-sm font-semibold text-green-900">Lokasi Penempatan</span>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat / Lokasi</label>
                                    <textarea v-model="form.location" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Rak Server Lt.2 Data Center"></textarea>
                                </div>
                                <div>
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
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Deskripsi / Keterangan</label>
                                    <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- VLAN per PON -->
                        <div class="border border-blue-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-blue-50/50 px-4 py-3 border-b border-blue-100 flex items-center justify-between">
                                <span class="text-sm font-semibold text-blue-900">Konfigurasi VLAN per PON</span>
                                <button type="button" @click="syncPonVlans" class="text-[10px] bg-blue-600 text-white px-2 py-1 rounded font-bold shadow-sm hover:bg-blue-700 transition-colors">Generate Sesuai Port</button>
                            </div>
                            <div class="p-4 space-y-4 max-h-64 overflow-y-auto">
                                <div v-if="!form.pon_vlans || form.pon_vlans.length === 0" class="text-xs text-gray-500 text-center py-2">
                                    Klik "Generate Sesuai Port" untuk memunculkan input VLAN.
                                </div>
                                <div v-for="(pon, index) in form.pon_vlans" :key="index" class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-bold text-gray-800">PON {{ pon.port }}</h4>
                                        <button type="button" @click="addVlanToPon(index)" class="text-[10px] px-2 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded font-bold">+ Tambah VLAN</button>
                                    </div>
                                    
                                    <div v-if="pon.vlans.length === 0" class="text-[10px] text-gray-500 italic mb-1">Belum ada VLAN.</div>
                                    
                                    <div class="space-y-2">
                                        <div v-for="(vlan, vIndex) in pon.vlans" :key="vIndex" class="bg-white p-2.5 rounded-lg border border-gray-200 shadow-2xs">
                                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                                <div class="sm:col-span-5">
                                                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">Nama Layanan / VLAN</label>
                                                    <input v-model="vlan.name" type="text" placeholder="Cth: PPPoE Internet" class="w-full px-2.5 py-1.5 bg-gray-50 border border-gray-200 rounded text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div class="sm:col-span-3">
                                                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">VLAN ID</label>
                                                    <input v-model="vlan.vlan_id" type="number" placeholder="ID (Cth: 18)" class="w-full px-2.5 py-1.5 bg-gray-50 border border-gray-200 rounded text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div class="sm:col-span-3">
                                                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">Mode Akses</label>
                                                    <select v-model="vlan.access_mode" class="w-full px-2 py-1.5 bg-gray-50 border border-gray-200 rounded text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                        <option value="PPPOE">PPPOE</option>
                                                        <option value="Hotspot">Hotspot</option>
                                                        <option value="Static">Static IP</option>
                                                        <option value="DHCP">DHCP</option>
                                                        <option value="Bridge">Bridge</option>
                                                        <option value="Tagged">Tagged (Trunk)</option>
                                                        <option value="Untagged">Untagged (Access)</option>
                                                    </select>
                                                </div>
                                                <div class="sm:col-span-1 flex justify-end sm:justify-center pt-2 sm:pt-4">
                                                    <button type="button" @click="pon.vlans.splice(vIndex, 1)" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded" title="Hapus VLAN">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
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
                            Simpan OLT
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
    olts: Object,
    areas: Array,
    filters: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    area_id: '',
    name: '',
    hostname: '',
    ip_address: '',
    brand: '',
    model: '',
    total_pon_ports: 8,
    pon_capacity: 64,
    pon_vlans: [],
    location: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: ''
});

function syncPonVlans() {
    const total = parseInt(form.total_pon_ports) || 0;
    if (total <= 0) return;
    
    if (!form.pon_vlans) {
        form.pon_vlans = [];
    }
    
    // Add new PONs if needed
    for (let i = 1; i <= total; i++) {
        if (!form.pon_vlans.find(p => p.port === i)) {
            form.pon_vlans.push({ port: i, vlans: [] });
        }
    }
    
    // Remove extra PONs if reduced, but sort them first to be safe
    form.pon_vlans.sort((a, b) => a.port - b.port);
    if (form.pon_vlans.length > total) {
        form.pon_vlans = form.pon_vlans.filter(p => p.port <= total);
    }
}

function addVlanToPon(ponIndex) {
    if (!form.pon_vlans[ponIndex].vlans) {
        form.pon_vlans[ponIndex].vlans = [];
    }
    form.pon_vlans[ponIndex].vlans.push({ name: '', vlan_id: '', access_mode: 'PPPOE' });
}

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    form.area_id = '';
    isModalOpen.value = true;
}

function openEditModal(olt) {
    isEditing.value = true;
    editId.value = olt.id;
    form.area_id = olt.area_id || '';
    form.name = olt.name;
    form.hostname = olt.hostname || '';
    form.ip_address = olt.ip_address || '';
    form.brand = olt.brand || '';
    form.model = olt.model || '';
    form.total_pon_ports = olt.total_pon_ports;
    form.pon_capacity = 64; // Default
    
    let vlans = olt.pon_vlans || [];
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { vlans = []; }
    }
    form.pon_vlans = JSON.parse(JSON.stringify(vlans)).map(p => ({
        ...p,
        vlans: (p.vlans || []).map(v => ({
            ...v,
            access_mode: v.access_mode || getVlanAccessMode(v)
        }))
    }));

    form.location = olt.location || '';
    form.latitude = olt.latitude || '';
    form.longitude = olt.longitude || '';
    form.status = olt.status;
    form.description = olt.description || '';
    form.clearErrors();
    isModalOpen.value = true;
}

function getVlanAccessMode(vlan) {
    if (vlan.access_mode) return vlan.access_mode;
    const name = (vlan.name || '').toLowerCase();
    if (name.includes('pppoe')) return 'PPPOE';
    if (name.includes('hotspot')) return 'Hotspot';
    if (name.includes('static')) return 'Static';
    if (name.includes('dhcp')) return 'DHCP';
    if (name.includes('bridge')) return 'Bridge';
    if (name.includes('trunk') || name.includes('tagged')) return 'Tagged';
    if (name.includes('access') || name.includes('untagged')) return 'Untagged';
    return 'PPPOE';
}

function getModeBadgeClass(mode) {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'HOTSPOT':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'STATIC':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'DHCP':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'BRIDGE':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'TAGGED':
        case 'TRUNK':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'UNTAGGED':
        case 'ACCESS':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        default:
            return 'bg-blue-50 text-blue-700 border-blue-200';
    }
}

function getOltVlans(olt) {
    if (!olt || !olt.pon_vlans) return [];
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const list = [];
    vlans.forEach(p => {
        (p.vlans || []).forEach(v => {
            if (v && v.vlan_id) {
                list.push({
                    port: p.port,
                    name: v.name,
                    vlan_id: v.vlan_id,
                    access_mode: getVlanAccessMode(v)
                });
            }
        });
    });
    return list;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function submit() {
    if (isEditing.value) {
        form.post(`/olts/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/olts', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOlt(id) {
    if (confirm('Apakah Anda yakin ingin menghapus OLT ini?')) {
        router.post(`/olts/${id}/delete`);
    }
}
</script>
