<template>
    <AppLayout title="Pelanggan Terpasang" subtitle="Pelanggan yang sedang/sudah diinstalasi">
        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan aktif..."
            searchRoute="/customers/installed"
        >
            <template #filters>
                <select v-model="status" @change="applyFilter" class="form-select w-40">
                    <option value="">Semua</option>
                    <option value="installing">Proses Pasang</option>
                    <option value="active">Aktif</option>
                </select>
            </template>

            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center text-sm font-bold text-gray-900 shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-gray-900 font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-xs text-cyan-400 font-medium">{{ row.package?.name || '-' }}</span>
                </td>
                <td><StatusBadge :status="row.status" /></td>
                <td>
                    <span v-if="row.ont" class="text-xs font-mono text-gray-600">{{ row.ont.serial_number }}</span>
                    <span v-else class="text-xs text-gray-600">-</span>
                </td>
                <td>
                    <template v-if="row.ont?.odp?.odc?.olt">
                        <div class="text-xs space-y-0.5">
                            <p class="text-gray-500">{{ row.ont.odp.odc.olt.name }}</p>
                            <p class="text-gray-500">→ {{ row.ont.odp.odc.name }} → {{ row.ont.odp.name }}</p>
                        </div>
                    </template>
                    <span v-else class="text-xs text-gray-600">-</span>
                </td>
                <td>
                    <span v-if="row.ont?.rx_power" :class="signalClass(row.ont.rx_power)" class="text-sm font-mono font-bold">
                        {{ row.ont.rx_power }} dBm
                    </span>
                    <span v-else class="text-gray-600">-</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1.5">
                    
                    <!-- 1. Belum Dijadwalkan -->
                    <button v-if="row.status === 'installing' && (!row.technician_schedules || row.technician_schedules.length === 0)" 
                        @click="openAssignModal(row)" 
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Jadwalkan Teknisi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Jadwal Pasang
                    </button>
                    
                    <!-- 2. Menunggu Laporan Teknisi -->
                    <Link v-if="row.status === 'installing' && (row.technician_schedules && row.technician_schedules.length > 0) && !row.ont" 
                        :href="`/customers/${row.id}`" 
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Input Laporan Instalasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Input Laporan
                    </Link>

                    <!-- 3. Selesai Pasang, Menunggu Audit Admin -->
                    <div v-if="row.status === 'installing' && row.ont" class="flex gap-1.5">
                        <Link :href="`/customers/${row.id}`" 
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Review Hasil Pemasangan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Audit
                        </Link>
                        <button @click="openActivationModal(row)" 
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 text-purple-600 hover:bg-purple-100 border border-purple-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Proses Aktivasi Pelanggan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Aktivasi
                        </button>
                    </div>

                    <!-- 4. Sudah Aktif -->
                    <span v-if="row.status === 'active'" class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-lg text-xs font-bold shadow-sm cursor-default">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Telah Aktif
                    </span>

                    <Link :href="`/customers/${row.id}`" class="p-1.5 rounded-lg text-gray-400 hover:bg-white hover:text-blue-500 border border-transparent hover:border-gray-200 transition-all shadow-sm hover:shadow" title="Detail Lengkap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </Link>
                </div>
            </template>
        </DataTable>

        <!-- Modal Assign Jadwal Pasang -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Jadwal Pasang Baru</h3>
                    <button @click="showAssignModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitAssign">
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-2">Pilih Teknisi</label>
                            <div class="space-y-2 max-h-32 overflow-y-auto p-2 border border-gray-200 rounded-lg">
                                <label v-for="tech in technicians" :key="tech.id" class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :value="tech.id" v-model="assignForm.technician_ids" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">{{ tech.name }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Pasang</label>
                                <input v-model="assignForm.scheduled_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" required />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Waktu (Jam)</label>
                                <input v-model="assignForm.scheduled_time" type="time" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" required />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-medium text-gray-500">Data ONT</label>
                                    <button type="button" @click="assignForm.ont_models.push('')" class="text-blue-500 hover:text-blue-600 focus:outline-none" title="Tambah ONT">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="(ont, index) in assignForm.ont_models" :key="'ont-'+index" class="flex gap-2 items-center">
                                        <select v-model="assignForm.ont_models[index]" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500">
                                            <option value="">-- Pilih ONT --</option>
                                            <option v-for="ont in availableOnts" :key="ont.id" :value="ont.brand + ' ' + ont.model + ' (SN: ' + ont.serial_number + ')'">
                                                {{ ont.brand }} {{ ont.model }} - SN: {{ ont.serial_number }}
                                            </option>
                                        </select>
                                        <button v-if="assignForm.ont_models.length > 1" type="button" @click="assignForm.ont_models.splice(index, 1)" class="text-red-500 hover:text-red-600 focus:outline-none" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-medium text-gray-500">Material (Surat Jalan)</label>
                                    <button type="button" @click="assignForm.material_transaction_ids.push('')" class="text-blue-500 hover:text-blue-600 focus:outline-none" title="Tambah Surat Jalan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="(trxId, index) in assignForm.material_transaction_ids" :key="'trx-'+index" class="flex gap-2 items-center">
                                        <select v-model="assignForm.material_transaction_ids[index]" @change="updateMaterialItems" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500">
                                            <option value="">-- Pilih Surat Jalan / Order --</option>
                                            <option v-for="trx in materialTransactions" :key="trx.id" :value="trx.transaction_number">
                                                {{ getTransactionLabel(trx) }}
                                            </option>
                                        </select>
                                        <button v-if="assignForm.material_transaction_ids.length > 1" type="button" @click="removeMaterialTransaction(index)" class="text-red-500 hover:text-red-600 focus:outline-none" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Kolom Rincian -->
                                    <div v-if="assignForm.material_items.length > 0" class="mt-2 p-3 bg-blue-50/50 border border-blue-100 rounded-lg max-h-48 overflow-y-auto">
                                        <p class="text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-2">Rincian Penggunaan Barang:</p>
                                        <ul class="space-y-2">
                                            <li v-for="(item, idx) in assignForm.material_items" :key="idx" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-blue-100/50 pb-2 last:border-0 last:pb-0">
                                                <input v-model="item.name" type="text" class="flex-1 w-full sm:w-auto px-2 py-1 text-xs border border-blue-200 rounded focus:ring-blue-500 font-medium text-blue-900 bg-white" placeholder="Nama Barang">
                                                <div class="flex gap-1 shrink-0 items-center">
                                                    <input v-model="item.qty" type="number" step="0.01" min="0" class="w-16 px-2 py-1 text-xs border border-blue-200 rounded focus:ring-blue-500" placeholder="Qty">
                                                    <input v-model="item.unit" list="unit-options-list" type="text" class="w-20 px-2 py-1 text-xs border border-blue-200 rounded focus:ring-blue-500" placeholder="Satuan">
                                                    <datalist id="unit-options-list">
                                                        <option value="pcs"></option>
                                                        <option value="meter"></option>
                                                        <option value="roll"></option>
                                                        <option value="pack"></option>
                                                        <option value="box"></option>
                                                    </datalist>
                                                    <button type="button" @click="assignForm.material_items.splice(idx, 1)" class="text-red-400 hover:text-red-600 focus:outline-none ml-1" title="Hapus">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Catatan (Opsional)</label>
                            <textarea v-model="assignForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">Batal</button>
                        <button type="submit" :disabled="assignForm.processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            {{ assignForm.processing ? 'Menyimpan...' : 'Tugaskan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activation Modal -->
        <div v-if="showActivationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-purple-50 to-purple-100/50">
                    <h3 class="text-lg font-bold text-purple-900 flex items-center gap-2">
                        <span class="text-2xl">⚡</span> Aktivasi Pelanggan
                    </h3>
                    <button @click="showActivationModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitActivation">
                    <div class="p-6 space-y-5">
                        <div class="bg-purple-50 text-purple-800 p-4 rounded-xl text-sm mb-4 border border-purple-200 shadow-sm flex gap-3">
                            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>Pastikan Anda telah memeriksa data fisik dan foto hasil instalasi sebelum melakukan aktivasi.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Aktivasi</label>
                            <input v-model="activationForm.activation_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username</label>
                                <input v-model="activationForm.pppoe_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="user@isp" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password</label>
                                <input v-model="activationForm.pppoe_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="***" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">VLAN Mode</label>
                                <select v-model="activationForm.vlan_mode" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all">
                                    <option value="">-- Pilih --</option>
                                    <option value="Route">Route</option>
                                    <option value="Bridge">Bridge</option>
                                    <option value="VLAN">VLAN (Tagged)</option>
                                    <option value="Untagged">Untagged</option>
                                </select>
                            </div>
                            
                            <div v-if="activationForm.vlan_mode === 'VLAN'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">No VLAN ID</label>
                                <input v-model="activationForm.vlan_id" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Misal: 100" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea v-model="activationForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Catatan internal setelah aktivasi..."></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showActivationModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="activationForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <svg v-if="activationForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ activationForm.processing ? 'Memproses...' : 'Aktivasi Sekarang' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({ 
    customers: Object, 
    technicians: Array, 
    availableOnts: { type: Array, default: () => [] }, 
    materialTransactions: { type: Array, default: () => [] },
    filters: Object 
});

const columns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'package', label: 'Paket' },
    { key: 'status', label: 'Status' },
    { key: 'ont', label: 'ONT S/N' },
    { key: 'topology', label: 'Topologi' },
    { key: 'signal', label: 'Redaman' },
];

// Removed selectedTransactionItems computed property

function updateMaterialItems() {
    assignForm.material_items = [];
    
    assignForm.material_transaction_ids.forEach(trxId => {
        if (!trxId) return;
        const trx = props.materialTransactions.find(t => t.transaction_number === trxId);
        if (trx && trx.items) {
            const mappedItems = trx.items.map(item => ({
                id: item.id,
                name: item.material ? item.material.name : 'Unknown',
                qty: item.quantity,
                unit: item.unit || (item.material ? item.material.unit : 'pcs')
            }));
            assignForm.material_items.push(...mappedItems);
        }
    });
}

function removeMaterialTransaction(index) {
    assignForm.material_transaction_ids.splice(index, 1);
    updateMaterialItems();
}

function addManualMaterial() {
    assignForm.material_items.push({
        id: 'manual-' + Date.now(),
        name: '',
        qty: 1,
        unit: 'pcs'
    });
}

function getTransactionLabel(trx) {
    let itemsStr = 'Tidak ada barang';
    if (trx.items && trx.items.length > 0) {
        itemsStr = trx.items.map(item => {
            const name = item.material ? item.material.name : 'Unknown';
            const qty = item.quantity;
            const unit = item.unit || (item.material ? item.material.unit : 'pcs');
            return `${name} (${qty} ${unit})`;
        }).join(', ');
        
        if (itemsStr.length > 60) {
            itemsStr = itemsStr.substring(0, 57) + '...';
        }
    }
    return `${itemsStr} - ${trx.technician_name}`;
}

const status = ref(props.filters?.status || '');

function applyFilter() {
    router.get('/customers/installed', { status: status.value || undefined }, { preserveState: true });
}

function signalClass(rx) {
    if (!rx) return 'text-gray-500';
    if (rx >= -20) return 'text-emerald-400';
    if (rx >= -25) return 'text-green-400';
    if (rx >= -28) return 'text-yellow-400';
    return 'text-red-400';
}

const showAssignModal = ref(false);
const activeCustomer = ref(null);
const assignForm = useForm({
    technician_ids: [],
    scheduled_date: '',
    scheduled_time: '',
    ont_models: [''],
    material_transaction_ids: [''],
    material_items: [],
    notes: '',
});

function openAssignModal(customer) {
    activeCustomer.value = customer;
    assignForm.reset();
    assignForm.scheduled_date = new Date().toISOString().split('T')[0];
    assignForm.scheduled_time = '10:00';
    showAssignModal.value = true;
}

function submitAssign() {
    assignForm.post(`/customers/${activeCustomer.value.id}/assign-install`, {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
        }
    });
}

const showActivationModal = ref(false);
const activationForm = useForm({
    activation_date: '',
    pppoe_user: '',
    pppoe_password: '',
    vlan_mode: '',
    vlan_id: '',
    notes: ''
});

function openActivationModal(customer) {
    activeCustomer.value = customer;
    activationForm.reset();
    activationForm.activation_date = new Date().toISOString().split('T')[0];
    activationForm.pppoe_user = customer.ont?.pppoe_user || '';
    activationForm.pppoe_password = customer.ont?.pppoe_password || '';
    activationForm.vlan_mode = customer.ont?.vlan_mode || '';
    activationForm.vlan_id = customer.ont?.vlan_id || '';
    activationForm.notes = '';
    showActivationModal.value = true;
}

function submitActivation() {
    activationForm.post(`/customers/${activeCustomer.value.id}/activate`, {
        preserveScroll: true,
        onSuccess: () => {
            showActivationModal.value = false;
        }
    });
}
</script>
