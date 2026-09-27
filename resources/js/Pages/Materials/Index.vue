<template>
    <AppLayout title="Data Material / Barang" subtitle="Kelola persediaan material infrastruktur jaringan">
        <div class="space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="w-full sm:w-96 relative">
                    <input 
                        type="text" 
                        v-model="search" 
                        placeholder="Cari nama atau kategori barang..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                        @keyup.enter="performSearch"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button @click="openModal()" class="btn-primary shrink-0 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Material
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <div v-if="$page.props.flash?.error" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Barang</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Stok</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">H. Modal / Jual</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in materials.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6">
                                    <p class="text-sm font-semibold text-gray-900">{{ item.name }}</p>
                                    <p v-if="item.supplier" class="text-xs text-blue-600 mt-0.5">Supplier: {{ item.supplier }}</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ item.category || 'Lainnya' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex flex-col items-end">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-bold text-gray-900">{{ formatNumber(item.stock) }}</span>
                                            <span class="text-xs text-gray-500">{{ item.unit }}</span>
                                        </div>
                                        <div v-if="item.category === 'Kabel' && item.total_rolls" class="text-xs text-gray-400 mt-0.5">
                                            Total {{ item.total_rolls }} roll
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right text-sm">
                                    <p class="text-gray-900">M: Rp {{ formatNumber(item.price_per_unit) }}</p>
                                    <p class="text-emerald-600 font-medium">J: Rp {{ formatNumber(item.selling_price) }}</p>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openModal(item)" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteData(item.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="materials.data.length === 0">
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada data material</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="materials.links && materials.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Menampilkan {{ materials.from }} - {{ materials.to }} dari {{ materials.total }} data</p>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in materials.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Produk' : 'Tambah Produk Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk *</label>
                            <input v-model="form.name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required placeholder="Contoh: Kabel FO 12 Core / Isolasi Hitam">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                            <input v-model="form.supplier" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nama supplier / distributor">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                <select v-model="form.category" @change="handleCategoryChange" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih Kategori...</option>
                                    <option value="Kabel">Kabel</option>
                                    <option value="Konektor / Frecon">Konektor / Frecon</option>
                                    <option value="Isolasi">Isolasi</option>
                                    <option value="Paku Klem">Paku Klem</option>
                                    <option value="Perangkat Aktif">Perangkat Aktif</option>
                                    <option value="Aksesoris">Aksesoris Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                                <input v-if="form.category === 'Kabel'" type="text" v-model="form.unit" disabled class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed">
                                <template v-else>
                                    <input type="text" v-model="form.unit" list="unit-options" placeholder="Ketik atau pilih satuan..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <datalist id="unit-options">
                                        <option value="pcs"></option>
                                        <option value="meter"></option>
                                        <option value="cm"></option>
                                        <option value="rol"></option>
                                        <option value="pack"></option>
                                        <option value="unit"></option>
                                        <option value="set"></option>
                                        <option value="box"></option>
                                    </datalist>
                                </template>
                            </div>
                        </div>

                        <!-- Kabel Calculator -->
                        <div v-if="form.category === 'Kabel'" class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3 text-blue-600 font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Kalkulator Kabel (Roll ↔ Meter)
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Meter per Roll</label>
                                    <input v-model="form.meter_per_roll" @input="calculateCableStock" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Roll</label>
                                    <input v-model="form.total_rolls" @input="calculateCableStock" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per roll)</label>
                                    <input v-model="form.price_per_unit" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per roll)</label>
                                    <input v-model="form.selling_price" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per meter)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-gray-100 bg-gray-50 text-sm font-semibold text-gray-700">
                                        Rp {{ formatNumber(hargaModalPerMeter) }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per meter)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-emerald-100 bg-emerald-50 text-sm font-semibold text-emerald-700">
                                        Rp {{ formatNumber(hargaJualPerMeter) }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-blue-100 flex justify-between items-center">
                                <span class="text-xs text-blue-700">Total Stok Tersimpan (Otomatis):</span>
                                <span class="text-sm font-bold text-blue-700">{{ form.stock }} Meter</span>
                            </div>
                        </div>

                        <!-- Normal Stock & Price (Non Kabel) -->
                        <div v-else class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                                <input v-model="form.stock" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hrg Modal</label>
                                <input v-model="form.price_per_unit" type="number" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hrg Jual</label>
                                <input v-model="form.selling_price" type="number" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
                        </div>
                    </form>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="closeModal" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">Batal</button>
                    <button type="button" @click="submit" :disabled="form.processing" class="btn-primary text-sm">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    materials: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    supplier: '',
    category: '',
    unit: 'pcs',
    meter_per_roll: 0,
    total_rolls: 0,
    stock: 0,
    price_per_unit: 0,
    selling_price: 0,
    description: '',
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const hargaModalPerMeter = computed(() => {
    const meterPerRoll = parseFloat(form.meter_per_roll) || 0;
    const hargaPerRoll = parseFloat(form.price_per_unit) || 0;
    if (meterPerRoll <= 0) return 0;
    return Math.round(hargaPerRoll / meterPerRoll);
});

const hargaJualPerMeter = computed(() => {
    const meterPerRoll = parseFloat(form.meter_per_roll) || 0;
    const hargaPerRoll = parseFloat(form.selling_price) || 0;
    if (meterPerRoll <= 0) return 0;
    return Math.round(hargaPerRoll / meterPerRoll);
});

const performSearch = () => {
    router.get('/materials', { search: search.value }, { preserveState: true });
};

const handleCategoryChange = () => {
    if (form.category === 'Kabel') {
        form.unit = 'meter'; // Base unit for cable is ALWAYS meter
        calculateCableStock();
    } else {
        if (form.unit === 'meter' || form.unit === 'roll') {
            form.unit = 'pcs';
        }
    }
};

const calculateCableStock = () => {
    const meter = parseFloat(form.meter_per_roll) || 0;
    const rolls = parseFloat(form.total_rolls) || 0;
    form.stock = meter * rolls;
};

const openModal = (item = null) => {
    if (item) {
        isEditing.value = true;
        editingId.value = item.id;
        form.name = item.name;
        form.supplier = item.supplier || '';
        form.category = item.category || '';
        form.unit = item.unit || 'pcs';
        form.meter_per_roll = item.meter_per_roll || 0;
        form.total_rolls = item.total_rolls || 0;
        form.stock = item.stock || 0;
        form.price_per_unit = item.price_per_unit || 0;
        form.selling_price = item.selling_price || 0;
        form.description = item.description || '';
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    // Pastikan stok terhitung jika kategori Kabel
    if (form.category === 'Kabel') {
        calculateCableStock();
    }
    
    if (isEditing.value) {
        form.put(`/materials/${editingId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/materials', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus data material ini?')) {
        router.delete(`/materials/${id}`);
    }
};
</script>
