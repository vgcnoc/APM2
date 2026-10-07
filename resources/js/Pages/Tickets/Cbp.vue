<template>
    <AppLayout title="CBP / Cabut Perangkat" subtitle="Manajemen Pencabutan dan Stop Permanen Layanan">
        <div class="space-y-6">
            <!-- Header Actions & Filters -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-2">
                <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3 flex-1">
                    <div class="relative w-full sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari ID CBP, pelanggan..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all text-sm"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    
                    <select v-model="filterStatus" @change="performSearch" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 text-sm bg-gray-50/50">
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu Teknisi (Pending)</option>
                        <option value="assigned">Dalam Proses (Assigned)</option>
                        <option value="completed">Selesai (Completed)</option>
                        <option value="canceled">Dibatalkan</option>
                    </select>
                </div>

                <button @click="openModal()" class="bg-gradient-to-r from-red-600 to-rose-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-md shadow-red-500/20 hover:shadow-lg hover:shadow-red-500/30 hover:-translate-y-0.5 transition-all w-full md:w-auto shrink-0 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Form CBP
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="p-4 w-40">Nomor CBP</th>
                                <th class="p-4">Pelanggan</th>
                                <th class="p-4">Alasan Cabut</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Teknisi</th>
                                <th class="p-4">Waktu Selesai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="req in requests.data" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 font-mono font-medium text-red-600">{{ req.cbp_number }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">{{ req.customer?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-200">{{ req.customer?.customer_code || '-' }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-gray-700 max-w-xs truncate" :title="req.reason">{{ req.reason || '-' }}</div>
                                </td>
                                <td class="p-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5 w-max',
                                        req.status === 'pending' ? 'bg-orange-50 text-orange-600 border-orange-200' :
                                        req.status === 'assigned' ? 'bg-blue-50 text-blue-600 border-blue-200' :
                                        req.status === 'completed' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
                                        'bg-gray-50 text-gray-600 border-gray-200'
                                    ]">
                                        <span :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            req.status === 'pending' ? 'bg-orange-500 animate-pulse' :
                                            req.status === 'assigned' ? 'bg-blue-500' :
                                            req.status === 'completed' ? 'bg-emerald-500' :
                                            'bg-gray-500'
                                        ]"></span>
                                        {{ req.status === 'pending' ? 'Pending' : req.status === 'assigned' ? 'Ditugaskan' : req.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div v-if="req.technicians && req.technicians.length" class="flex flex-col gap-1">
                                        <div v-for="tech in req.technicians" :key="tech.id" class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-[9px] font-bold text-blue-700">
                                                {{ tech.name.charAt(0) }}
                                            </div>
                                            <span class="text-xs font-medium text-gray-700">{{ tech.name }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">Belum ditugaskan</span>
                                </td>
                                <td class="p-4">
                                    <span v-if="req.completed_at" class="text-xs text-gray-600">{{ req.completed_at }}</span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="7" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p>Belum ada data CBP / Cabut Perangkat.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="requests.links?.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <span class="text-sm text-gray-500">Menampilkan {{ requests.from }} - {{ requests.to }} dari {{ requests.total }} data</span>
                    <div class="flex gap-1">
                        <template v-for="link in requests.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 text-sm rounded-lg transition-colors', link.active ? 'bg-red-50 text-red-600 font-bold border border-red-200' : 'text-gray-600 hover:bg-gray-100 border border-transparent']" v-html="link.label"></Link>
                            <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 animate-fade-in-up flex flex-col max-h-[90vh]">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 rounded-t-2xl shrink-0">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Buat CBP Baru</h3>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">Form pembuatan data cabut perangkat (Stop Permanen)</p>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="flex flex-col flex-1 overflow-hidden">
                        <div class="p-6 overflow-y-auto custom-scrollbar space-y-5">
                            
                            <!-- Notice -->
                            <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex gap-3">
                                <div class="shrink-0 text-rose-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-rose-800">Perhatian!</h4>
                                    <p class="text-[11px] text-rose-600 mt-1 leading-snug">Menyimpan form ini akan otomatis mengubah status pelanggan menjadi <strong>Stop Permanen</strong>, menghentikan layanan, dan memberhentikan tagihan bulan depan.</p>
                                </div>
                            </div>

                            <!-- Area Selection -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Pilih Area / Wilayah</label>
                                <select v-model="selectedArea" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                                    <option value="" disabled>Pilih Area...</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                </select>
                            </div>

                            <!-- Customer ID -->
                            <div v-if="selectedArea">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Pilih Pelanggan</label>
                                <select v-model="form.customer_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                                    <option value="" disabled>Pilih Pelanggan...</option>
                                    <option v-for="cust in filteredCustomers" :key="cust.id" :value="cust.id">{{ cust.customer_code }} - {{ cust.name }}</option>
                                </select>
                                <p class="text-[10px] text-gray-500 mt-1">Pelanggan yang dipilih akan di-stop permanen.</p>
                            </div>

                            <!-- Alasan -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Alasan Pencabutan</label>
                                <textarea v-model="form.reason" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" placeholder="Jelaskan alasan pelanggan berhenti... (misal: pindah rumah, dsb)" required></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 rounded-b-2xl flex justify-end gap-3 shrink-0">
                            <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="form.processing || !form.customer_id" class="bg-red-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm shadow-red-500/20 hover:bg-red-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ form.processing ? 'Menyimpan...' : 'Eskalasi' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Assign Modal -->
        <Teleport to="body">
            <div v-if="showAssignModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeAssignModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Tugaskan Teknisi</h3>
                        <button @click="closeAssignModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitAssign">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Pilih Teknisi</label>
                                <select v-model="assignForm.assigned_to" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                                    <option value="" disabled>Pilih teknisi yang bertugas...</option>
                                    <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeAssignModal" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="assignForm.processing" class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-blue-500/20 hover:bg-blue-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ assignForm.processing ? 'Menyimpan...' : 'Tugaskan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Progress Modal -->
        <Teleport to="body">
            <div v-if="showProgressModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeProgressModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Progress Pencabutan</h3>
                        <button @click="closeProgressModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitProgress">
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Update Status</label>
                                <select v-model="progressForm.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" required>
                                    <option value="assigned">Masih Dalam Proses</option>
                                    <option value="completed">Selesai Dicabut</option>
                                </select>
                            </div>
                            
                            <div v-if="progressForm.status === 'completed'" class="border-t border-gray-100 pt-4 mt-2">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">Perangkat / Material yang Dikembalikan</label>
                                    <button type="button" @click="addMaterial" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah Material
                                    </button>
                                </div>
                                <div v-if="progressForm.materials.length === 0" class="text-[11px] text-gray-400 italic text-center py-2 bg-gray-50 rounded-xl border border-gray-100 border-dashed">
                                    Tidak ada material yang dikembalikan (atau kabel terputus/hilang).
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(mat, idx) in progressForm.materials" :key="idx" class="flex items-start gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                        <div class="flex-1 space-y-2">
                                            <select v-model="mat.material_id" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 focus:border-transparent" required>
                                                <option value="" disabled>Pilih Material/Perangkat...</option>
                                                <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }} (Stok: {{ m.stock }} {{ m.unit }})</option>
                                            </select>
                                            <div class="flex items-center gap-2">
                                                <input type="number" v-model="mat.quantity" step="0.01" min="0.01" class="w-20 px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Qty" required>
                                                <span class="text-[10px] text-gray-500 font-medium">Qty (Jumlah yang kembali)</span>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeMaterial(idx)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-2">Otomatis masuk ke stok gudang saat proses selesai.</p>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Catatan Teknisi (Opsional)</label>
                                <textarea v-model="progressForm.notes" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Catatan... misal: ONT terbakar, dsb"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeProgressModal" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="progressForm.processing" class="bg-emerald-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-emerald-500/20 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ progressForm.processing ? 'Menyimpan...' : 'Simpan Progress' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Object,
    areas: Array,
    customers: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

function performSearch() {
    router.get('/cbp', {
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
}

// Create Modal
const showModal = ref(false);
const selectedArea = ref('');

const form = useForm({
    customer_id: '',
    reason: '',
});

const filteredCustomers = computed(() => {
    if (!selectedArea.value) return [];
    return props.customers.filter(c => c.area_id === selectedArea.value);
});

function openModal() {
    form.reset();
    selectedArea.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    selectedArea.value = '';
}

function submitCreate() {
    form.post('/cbp', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}
</script>
