<template>
    <AppLayout title="Order Toko (Restock)">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-indigo-100/40 to-blue-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl mb-4 shadow-lg shadow-indigo-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Order Toko (Restock)</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Catat pembelian barang dari Supplier. Sistem akan otomatis mengkonversi satuan Beli menjadi Stok Gudang.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="openModal" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white transition-all duration-200 bg-gradient-to-r from-indigo-600 to-blue-500 border border-transparent rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 overflow-hidden">
                                <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Buat Order Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & No. Ref</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Supplier</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Barang Masuk (Terkonversi)</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Biaya</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ formatDate(trx.date) }}</div>
                                        <div class="text-xs text-gray-500 mt-1 font-mono">{{ trx.transaction_number }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                            {{ trx.technician_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-2">
                                            <div v-for="item in trx.items" :key="item.id" class="flex items-center gap-2 text-sm">
                                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                                <span class="font-medium text-gray-900">{{ item.material ? item.material.name : 'Unknown' }}</span>
                                                <span class="text-gray-400">&mdash;</span>
                                                <span class="font-bold text-emerald-600">+{{ item.quantity }} {{ item.material ? item.material.unit : '' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">Rp {{ formatNumber(trx.total_cost) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="confirmDelete(trx.id)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Batalkan Transaksi">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat Order Toko.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="mt-6 flex justify-center">
                    <div class="inline-flex bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <template v-for="(link, k) in transactions.links" :key="k">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border-r border-gray-100 last:border-0 bg-gray-50" v-html="link.label"></div>
                            <Link v-else :href="link.url" :class="['px-4 py-2 text-sm border-r border-gray-100 last:border-0 transition-colors hover:bg-indigo-50 hover:text-indigo-600', link.active ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600']" v-html="link.label" preserve-state preserve-scroll></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog :open="isModalOpen" @close="closeModal" class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" aria-hidden="true" />
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between shrink-0">
                        <DialogTitle class="text-lg font-black text-gray-900 flex items-center gap-2">
                            <div class="p-1.5 bg-indigo-100 rounded-lg text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            Input Order Toko
                        </DialogTitle>
                        <button @click="closeModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 overflow-hidden">
                        <div class="p-6 overflow-y-auto flex-1">
                            
                            <!-- Header Info -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Tanggal Pembelian <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="form.date" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Toko / Supplier <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.supplier_name" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Cth: Global Fiberoptic Jakarta" required>
                                </div>
                            </div>

                            <!-- Items List -->
                            <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                                    <h4 class="text-sm font-bold text-gray-700">Daftar Barang yang Dibeli</h4>
                                    <button type="button" @click="addItem" class="text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        Tambah Baris
                                    </button>
                                </div>
                                
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(item, index) in form.items" :key="index" class="p-4 flex flex-col md:flex-row gap-4 items-start md:items-center hover:bg-slate-50/50 transition-colors">
                                        
                                        <div class="w-full md:w-1/3">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Pilih Barang</label>
                                            <select v-model="item.material_id" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white" required>
                                                <option value="" disabled>Pilih Barang...</option>
                                                <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                                    {{ mat.name }} (Stok: {{ mat.stock }} {{ mat.unit }})
                                                </option>
                                            </select>
                                        </div>

                                        <div class="w-full md:w-24">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Satuan Beli</label>
                                            <select v-model="item.purchase_unit" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white" required>
                                                <option value="pcs">Pcs / Unit</option>
                                                <option value="roll">Roll</option>
                                                <option value="pack">Pack / Dus</option>
                                            </select>
                                        </div>

                                        <div class="w-full md:w-24">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Jumlah Beli</label>
                                            <input type="number" step="0.01" v-model="item.quantity" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500" placeholder="Jml" required>
                                        </div>

                                        <div class="w-full md:flex-1">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Harga Per Satuan Beli (Rp)</label>
                                            <input type="number" v-model="item.price" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500" placeholder="Harga Satuan" required>
                                        </div>
                                        
                                        <div class="w-full md:w-auto mt-2 md:mt-0 flex justify-end">
                                            <button type="button" @click="removeItem(index)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Hapus Baris" :disabled="form.items.length === 1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        
                                    </div>
                                    <div v-if="form.items.length === 0" class="p-8 text-center text-gray-400 text-sm italic">
                                        Belum ada barang yang ditambahkan. Klik "Tambah Baris".
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 text-right">
                                    <span class="text-sm font-bold text-gray-500 mr-4">Estimasi Total:</span>
                                    <span class="text-lg font-black text-indigo-700">Rp {{ formatNumber(calculateTotal()) }}</span>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                                <textarea v-model="form.notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all"></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex justify-end shrink-0 rounded-b-3xl">
                            <div class="flex items-center gap-3">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing || form.items.length === 0" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-bold text-white transition-all bg-indigo-600 border border-transparent rounded-xl shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg v-if="form.processing" class="w-4 h-4 mr-2 -ml-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Simpan & Masukkan Stok
                                </button>
                            </div>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

const props = defineProps({
    transactions: Object,
    materials: Array,
});

const isModalOpen = ref(false);

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    supplier_name: '',
    notes: '',
    items: [
        { material_id: '', purchase_unit: 'pcs', quantity: null, price: null }
    ]
});

const addItem = () => {
    form.items.push({ material_id: '', purchase_unit: 'pcs', quantity: null, price: null });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const calculateTotal = () => {
    return form.items.reduce((total, item) => {
        return total + ((item.quantity || 0) * (item.price || 0));
    }, 0);
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
};

const openModal = () => {
    form.reset();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submitForm = () => {
    form.post('/order-toko', {
        onSuccess: () => closeModal(),
    });
};

const confirmDelete = (id) => {
    if (confirm('Yakin ingin membatalkan transaksi ini? Stok barang akan dikurangi kembali!')) {
        form.delete(`/order-toko/${id}`);
    }
};
</script>
