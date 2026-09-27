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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tujuan / Peruntukan *</label>
                    <input v-model="form.purpose" type="text" required placeholder="Contoh: Instalasi Pelanggan Baru (Pak Andi), Ticketing #1234" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                    <textarea v-model="form.notes" rows="2" placeholder="Catatan opsional..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
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
                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" class="absolute -top-2.5 -right-2.5 w-6 h-6 bg-white border border-red-200 rounded-full text-red-500 hover:bg-red-50 hover:text-red-600 flex items-center justify-center transition-colors shadow-sm z-10">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-8">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Pilih Material *</label>
                            <select v-model="item.material_id" @change="onMaterialSelected(index)" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                                <option value="">Pilih Material / Barang...</option>
                                <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                    {{ mat.name }} (Stok: {{ mat.stock }} {{ mat.unit }})
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jumlah Diambil *</label>
                            <div class="flex gap-2">
                                <input v-model="item.input_quantity" type="number" step="0.01" min="0.01" :max="item.unit_mode === 'roll' ? (item.max_stock / (item.meter_per_roll || 1)) : item.max_stock" required class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white">
                                
                                <select v-if="item.is_cable" v-model="item.unit_mode" class="w-24 px-2 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white text-sm">
                                    <option value="meter">Meter</option>
                                    <option value="roll">Roll</option>
                                </select>
                                <div v-else class="flex items-center px-4 rounded-xl border border-gray-200 bg-gray-50 text-gray-500 text-sm font-medium whitespace-nowrap">
                                    {{ item.unit || '...' }}
                                </div>
                            </div>
                            <p v-if="item.is_cable && item.unit_mode === 'roll'" class="text-[10px] text-gray-500 mt-1">
                                = {{ (item.input_quantity * item.meter_per_roll).toFixed(2) }} Meter
                            </p>
                            <p class="text-[10px] text-emerald-600 mt-1" v-if="item.max_stock !== null">
                                Sisa Stok: {{ item.unit_mode === 'roll' ? (item.max_stock / item.meter_per_roll).toFixed(2) + ' Roll' : item.max_stock + ' ' + (item.unit || '') }}
                            </p>
                        </div>
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
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    materials: Array,
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
    notes: '',
    items: [
        { material_id: '', input_quantity: 1, unit_mode: 'default', unit: '', max_stock: null, is_cable: false, meter_per_roll: 1000 }
    ]
});

const addItem = () => {
    form.items.push({ material_id: '', input_quantity: 1, unit_mode: 'default', unit: '', max_stock: null, is_cable: false, meter_per_roll: 1000 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onMaterialSelected = (index) => {
    const itemId = form.items[index].material_id;
    const material = props.materials.find(m => m.id === itemId);
    
    if (material) {
        const isCable = material.category === 'Kabel' || material.name.toLowerCase().includes('kabel');
        
        form.items[index].unit = material.unit;
        form.items[index].max_stock = material.stock;
        form.items[index].is_cable = isCable;
        form.items[index].meter_per_roll = material.meter_per_roll || 1000;
        form.items[index].unit_mode = isCable ? 'meter' : 'default';
        
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
            quantity: item.unit_mode === 'roll' ? (item.input_quantity * item.meter_per_roll) : item.input_quantity
        }))
    })).post('/material-transactions');
};
</script>
