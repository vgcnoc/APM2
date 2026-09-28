<template>
    <AppLayout title="Buat Order Pengambilan" subtitle="Catat pengeluaran barang dari gudang">
        <form @submit.prevent="submit" class="max-w-4xl space-y-6">
            <!-- Informasi Umum -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">Informasi Order</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengambilan *</label>
                        <input v-model="form.date" type="date" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Teknisi / Peminjam *</label>
                        <input v-model="form.technician_name" type="text" required placeholder="Contoh: Budi, Tim 1" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tujuan / Peruntukan *</label>
                        <input v-model="form.purpose" type="text" required placeholder="Contoh: Instalasi (Pak Andi), Ticketing #1234" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Area / Wilayah *</label>
                        <select v-model="form.area_id" @change="onAreaChange" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                            <option value="">Pilih Area / Wilayah</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                    <textarea v-model="form.notes" rows="1" placeholder="Catatan opsional..." class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
                </div>
            </div>

            <!-- Keranjang Barang -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-bold text-gray-900">Daftar Barang yang Diambil</h3>
                    <button type="button" @click="addItem" class="text-sm font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Barang
                    </button>
                </div>

                <div v-for="(item, index) in form.items" :key="index" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 relative group">
                    <div class="grid grid-cols-1 md:flex items-start md:items-end gap-3 mb-2">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Material *</label>
                            <select v-model="item.material_id" @change="onMaterialSelected(index)" :disabled="!form.area_id" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white disabled:bg-gray-100">
                                <option value="">Pilih material</option>
                                <option v-for="mat in filteredMaterials" :key="mat.id" :value="mat.id">
                                    {{ mat.name }} (Stok: {{ mat.stock }} {{ mat.unit }})
                                </option>
                            </select>
                        </div>
                        
                        <div class="w-full md:w-32">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jumlah *</label>
                            <input v-model="item.input_quantity" type="number" step="0.01" min="0.01" :max="item.is_cable && item.unit_mode === 'roll' ? (item.max_stock / (item.meter_per_roll || 1)) : (item.is_pack && item.unit_mode === 'bungkus' ? (item.max_stock / (item.pcs_per_pack || 1)) : (item.is_isolasi && item.unit_mode === 'pcs' ? (item.max_stock / (item.cm_per_pcs || 1)) : item.max_stock))" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                        </div>
                        
                        <div class="w-full md:w-32">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Satuan *</label>
                            <select v-if="item.is_cable" v-model="item.unit_mode" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                                <option value="meter">Meter</option>
                                <option value="roll">Roll</option>
                            </select>
                            <select v-else-if="item.is_pack" v-model="item.unit_mode" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                                <option value="pcs">Pcs</option>
                                <option value="bungkus">Bungkus</option>
                            </select>
                            <select v-else-if="item.is_isolasi" v-model="item.unit_mode" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                                <option value="cm">Cm</option>
                                <option value="pcs">Pcs (Utuh)</option>
                            </select>
                            <!-- Input Satuan Manual (Datalist) -->
                            <template v-else>
                                <input type="text" v-model="item.unit_manual" list="item-unit-options" required placeholder="..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                                <datalist id="item-unit-options">
                                    <option value="pcs"></option>
                                    <option value="meter"></option>
                                    <option value="rol"></option>
                                    <option value="pack"></option>
                                    <option value="box"></option>
                                </datalist>
                            </template>
                        </div>

                        <div class="md:pb-1">
                            <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" class="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-colors shrink-0" title="Hapus baris">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <div v-else class="w-10"></div> <!-- Placeholder for alignment -->
                        </div>
                    </div>
                    
                    <!-- Info text -->
                    <div class="flex gap-4">
                        <p v-if="item.is_cable && item.unit_mode === 'roll'" class="text-[10px] text-gray-500 mt-0.5">
                            = {{ (item.input_quantity * item.meter_per_roll).toFixed(2) }} Meter
                        </p>
                        <p v-else-if="item.is_pack && item.unit_mode === 'bungkus'" class="text-[10px] text-gray-500 mt-0.5">
                            = {{ (item.input_quantity * item.pcs_per_pack) }} Pcs
                        </p>
                        <p v-else-if="item.is_isolasi && item.unit_mode === 'pcs'" class="text-[10px] text-gray-500 mt-0.5">
                            = {{ (item.input_quantity * item.cm_per_pcs) }} Cm
                        </p>
                        <p class="text-[10px] text-emerald-600 mt-0.5" v-if="item.max_stock !== null">
                            Sisa Stok: {{ item.is_cable && item.unit_mode === 'roll' ? (item.max_stock / item.meter_per_roll).toFixed(2) + ' Roll' : (item.is_pack && item.unit_mode === 'bungkus' ? (item.max_stock / item.pcs_per_pack).toFixed(2) + ' Bungkus' : (item.is_isolasi && item.unit_mode === 'pcs' ? (item.max_stock / item.cm_per_pcs).toFixed(2) + ' Pcs' : item.max_stock + ' ' + (item.unit || ''))) }}
                        </p>
                    </div>
                </div>

                <div v-if="form.errors" class="text-sm text-red-500">
                    <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <Link href="/material-transactions" class="px-6 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-colors">
                    Batal
                </Link>
                <button type="submit" :disabled="form.processing" class="btn-primary text-sm px-6">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan & Potong Stok' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    materials: Array,
    areas: Array,
});

const filteredMaterials = computed(() => {
    if (!form.area_id) return [];
    return props.materials.filter(m => m.area_id === form.area_id);
});

const getTodayDate = () => {
    const d = new Date();
    let month = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
};

const form = useForm({
    date: getTodayDate(),
    technician_name: '',
    purpose: '',
    area_id: '',
    notes: '',
    items: [
        { material_id: '', input_quantity: 1, unit_mode: 'default', unit: '', unit_manual: '', max_stock: null, is_cable: false, is_pack: false, is_isolasi: false, meter_per_roll: 1000, pcs_per_pack: 1, cm_per_pcs: 50 }
    ]
});

const addItem = () => {
    form.items.push({ material_id: '', input_quantity: 1, unit_mode: 'default', unit: '', unit_manual: '', max_stock: null, is_cable: false, is_pack: false, is_isolasi: false, meter_per_roll: 1000, pcs_per_pack: 1, cm_per_pcs: 50 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onAreaChange = () => {
    // Reset material selection when area changes
    form.items.forEach(item => {
        item.material_id = '';
        item.max_stock = null;
    });
};

const onMaterialSelected = (index) => {
    const itemId = form.items[index].material_id;
    const material = props.materials.find(m => m.id === itemId);
    
    if (material) {
        const isCable = material.category === 'Kabel' || material.name.toLowerCase().includes('kabel');
        const isPack = material.category === 'Paku Klem';
        const isIsolasi = material.category === 'Isolasi';
        
        form.items[index].unit = material.unit;
        form.items[index].unit_manual = material.unit || 'pcs'; // default it to material's original unit
        form.items[index].max_stock = material.stock;
        form.items[index].is_cable = isCable;
        form.items[index].is_pack = isPack;
        form.items[index].is_isolasi = isIsolasi;
        form.items[index].meter_per_roll = material.meter_per_roll || 1000;
        form.items[index].pcs_per_pack = material.pcs_per_pack || 1;
        form.items[index].cm_per_pcs = material.cm_per_pcs || 50;
        
        if (isCable) {
            form.items[index].unit_mode = 'meter';
        } else if (isPack) {
            form.items[index].unit_mode = 'pcs';
        } else if (isIsolasi) {
            form.items[index].unit_mode = 'cm';
        } else {
            form.items[index].unit_mode = 'default';
        }
        
        // Reset quantity if it exceeds max stock
        if (form.items[index].input_quantity > material.stock) {
            form.items[index].input_quantity = material.stock;
        }
    }
};

const submit = () => {
    // Transform data before sending
    form.transform((data) => ({
        ...data,
        items: data.items.map(item => ({
            material_id: item.material_id,
            quantity: item.input_quantity,
            unit: (item.is_cable || item.is_pack || item.is_isolasi) ? item.unit_mode : item.unit_manual
        }))
    })).post('/material-transactions');
};
</script>
