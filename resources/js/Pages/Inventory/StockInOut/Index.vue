<template>
    <AppLayout title="Stok Masuk & Keluar">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-rose-100/40 to-orange-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-rose-500 to-orange-600 rounded-xl mb-4 shadow-lg shadow-rose-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Mutasi Stok (Masuk & Keluar)</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Catat pengeluaran barang sebagai Bekal Teknisi atau penerimaan barang Retur (sisa) dari Teknisi.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="openModal('out')" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white transition-all duration-200 bg-gradient-to-r from-rose-600 to-orange-500 border border-transparent rounded-xl shadow-md hover:shadow-lg hover:from-rose-500 hover:to-orange-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 overflow-hidden">
                                <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Stok Keluar (Distribusi)
                            </button>
                            <button @click="openModal('in')" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-gray-700 transition-all duration-200 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 overflow-hidden">
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Stok Masuk (Retur)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
                
                <!-- Filters -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-center justify-between mb-8">
                    <div class="relative w-full sm:w-96 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 group-focus-within:text-rose-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="search" @input="debouncedSearch" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 sm:text-sm transition-all" placeholder="Cari teknisi, no. transaksi, atau tujuan..." />
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & No. Ref</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis & Tujuan</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penerima / Pengirim</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Barang</th>
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
                                        <div class="flex flex-col gap-2">
                                            <span v-if="trx.type === 'out'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 text-xs font-bold w-fit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                                STOK KELUAR
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-bold w-fit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                                STOK MASUK
                                            </span>
                                            <span class="text-sm text-gray-700">{{ trx.purpose }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            {{ trx.technician_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-2">
                                            <div v-for="item in trx.items" :key="item.id" class="flex items-center gap-2 text-sm">
                                                <div :class="['w-1.5 h-1.5 rounded-full', trx.type === 'out' ? 'bg-rose-500' : 'bg-emerald-500']"></div>
                                                <span class="font-medium text-gray-900">{{ item.material ? item.material.name : 'Unknown' }}</span>
                                                <span class="text-gray-400">&mdash;</span>
                                                <span :class="['font-bold', trx.type === 'out' ? 'text-rose-600' : 'text-emerald-600']">
                                                    {{ trx.type === 'out' ? '-' : '+' }}{{ item.quantity }} {{ item.material ? item.material.unit : '' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="confirmDelete(trx.id)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Batalkan Mutasi">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat Mutasi (Masuk/Keluar).</td>
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
                            <Link v-else :href="link.url" :class="['px-4 py-2 text-sm border-r border-gray-100 last:border-0 transition-colors hover:bg-rose-50 hover:text-rose-600', link.active ? 'bg-rose-50 text-rose-700 font-bold' : 'text-gray-600']" v-html="link.label" preserve-state preserve-scroll></Link>
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
                            <div :class="['p-1.5 rounded-lg', form.type === 'out' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600']">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            Input {{ form.type === 'out' ? 'Stok Keluar (Distribusi)' : 'Stok Masuk (Retur Sisa)' }}
                        </DialogTitle>
                        <button @click="closeModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitForm" class="flex flex-col flex-1 overflow-hidden">
                        <div class="p-6 overflow-y-auto flex-1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Tanggal <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="form.date" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Teknisi / Karyawan <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.technician_name" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all" placeholder="Cth: Budi Pratama" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Tujuan / Keterangan <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.purpose" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all" :placeholder="form.type === 'out' ? 'Cth: Bekal Pasang Baru' : 'Cth: Sisa Pasang (Retur)'" required>
                                </div>
                            </div>

                            <!-- Items List -->
                            <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                                    <h4 class="text-sm font-bold text-gray-700">Daftar Barang</h4>
                                    <button type="button" @click="addItem" class="text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        Tambah Baris
                                    </button>
                                </div>
                                
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(item, index) in form.items" :key="index" class="p-4 flex flex-col md:flex-row gap-4 items-start md:items-center hover:bg-slate-50/50 transition-colors">
                                        
                                        <div class="w-full md:flex-1">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Pilih Barang</label>
                                            <select v-model="item.material_id" class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 bg-white" required>
                                                <option value="" disabled>Pilih Barang...</option>
                                                <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                                    {{ mat.name }} (Stok: {{ mat.stock }} {{ mat.unit }})
                                                </option>
                                            </select>
                                        </div>

                                        <div class="w-full md:w-48">
                                            <label class="block text-xs font-bold text-gray-500 mb-1 md:hidden">Jumlah (Dalam Satuan Terkecil)</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" v-model="item.quantity" class="w-full text-sm pl-3 pr-16 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500" placeholder="Jumlah" required>
                                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                    <span class="text-xs font-bold text-gray-400">{{ getUnit(item.material_id) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="w-full md:w-auto mt-2 md:mt-0 flex justify-end">
                                            <button type="button" @click="removeItem(index)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Hapus Baris" :disabled="form.items.length === 1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        
                                    </div>
                                    <div v-if="form.items.length === 0" class="p-8 text-center text-gray-400 text-sm italic">
                                        Belum ada barang yang ditambahkan.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                                <textarea v-model="form.notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 transition-all"></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex justify-end shrink-0 rounded-b-3xl">
                            <div class="flex items-center gap-3">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing || form.items.length === 0" :class="['inline-flex items-center justify-center px-6 py-2.5 text-sm font-bold text-white transition-all border border-transparent rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed', form.type === 'out' ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500' : 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500']">
                                    <svg v-if="form.processing" class="w-4 h-4 mr-2 -ml-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Simpan & {{ form.type === 'out' ? 'Kurangi' : 'Tambah' }} Stok
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
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { debounce } from 'lodash';

const props = defineProps({
    transactions: Object,
    materials: Array,
    filters: Object
});

const search = ref(props.filters.search || '');

const debouncedSearch = debounce(() => {
    router.get('/stok-in-out', { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

const isModalOpen = ref(false);

const form = useForm({
    type: 'out',
    date: new Date().toISOString().split('T')[0],
    technician_name: '',
    purpose: '',
    notes: '',
    items: [
        { material_id: '', quantity: null }
    ]
});

const getUnit = (materialId) => {
    if (!materialId) return '';
    const mat = props.materials.find(m => m.id === materialId);
    return mat ? mat.unit : '';
};

const addItem = () => {
    form.items.push({ material_id: '', quantity: null });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
};

const openModal = (type) => {
    form.reset();
    form.type = type;
    // Set default purposes based on type
    if (type === 'out') {
        form.purpose = 'Bekal Teknisi';
    } else {
        form.purpose = 'Retur Sisa Pasang';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submitForm = () => {
    form.post('/stok-in-out', {
        onSuccess: () => closeModal(),
    });
};

const confirmDelete = (id) => {
    if (confirm('Yakin ingin membatalkan transaksi ini? Stok barang akan dikembalikan ke kondisi semula.')) {
        form.delete(`/stok-in-out/${id}`);
    }
};
</script>
