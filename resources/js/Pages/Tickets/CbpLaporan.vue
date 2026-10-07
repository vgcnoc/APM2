<template>
    <AppLayout title="Laporan CBP" subtitle="Pelaporan Pencabutan Perangkat oleh Teknisi">
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
                </div>
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
                                <th class="p-4 w-24 text-center">Aksi</th>
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
                                    <div v-if="req.notes" class="text-[10px] text-gray-400 mt-1 border-t border-gray-100 pt-1" :title="req.notes">
                                        Note: <span class="truncate">{{ req.notes }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                        :class="{
                                            'bg-blue-50 text-blue-600 border-blue-200': req.status === 'assigned',
                                            'bg-emerald-50 text-emerald-600 border-emerald-200': req.status === 'completed',
                                            'bg-gray-50 text-gray-600 border-gray-200': req.status === 'canceled'
                                        }">
                                        <span :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            req.status === 'assigned' ? 'bg-blue-500' :
                                            req.status === 'completed' ? 'bg-emerald-500' :
                                            'bg-gray-500'
                                        ]"></span>
                                        {{ req.status === 'assigned' ? 'Ditugaskan' : req.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
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
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="req.status === 'assigned'" @click="openProgressModal(req)" class="px-3 py-1.5 text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors" title="Selesaikan & Laporkan">
                                            Lapor Selesai
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p>Belum ada Laporan CBP.</p>
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

        <!-- Progress Modal -->
        <Teleport to="body">
            <div v-if="showProgressModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeProgressModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Laporan CBP Selesai</h3>
                        <button @click="closeProgressModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitProgress">
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Update Status</label>
                                <select v-model="progressForm.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                                    <option value="assigned">Masih Diproses</option>
                                    <option value="completed">Selesai Cabut</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Catatan Teknisi</label>
                                <textarea v-model="progressForm.notes" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Catatan opsional..."></textarea>
                            </div>

                            <!-- Material Return Section -->
                            <div v-if="progressForm.status === 'completed'" class="border-t border-gray-100 pt-4 mt-2">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">Pengembalian Material/Stok</label>
                                    <button type="button" @click="addMaterial" class="text-xs text-blue-600 font-medium hover:text-blue-700 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Tambah Barang
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(item, index) in progressForm.materials" :key="index" class="flex gap-2 items-start relative group">
                                        <div class="flex-1 space-y-2">
                                            <select v-model="item.material_id" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent" required>
                                                <option value="" disabled>Pilih Material...</option>
                                                <option v-for="mat in materials" :key="mat.id" :value="mat.id">{{ mat.name }} ({{ mat.unit }})</option>
                                            </select>
                                            <div class="flex gap-2">
                                                <input type="number" v-model="item.quantity" min="0.01" step="0.01" class="w-24 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Jumlah" required>
                                                <input type="text" v-model="item.unit" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm" placeholder="Unit/Keterangan">
                                            </div>
                                        </div>
                                        <button type="button" @click="removeMaterial(index)" class="mt-2 p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div v-if="!progressForm.materials.length" class="text-[11px] text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-100 text-center italic">
                                        Tidak ada material yang dikembalikan.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeProgressModal" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="progressForm.processing" class="bg-emerald-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-emerald-500/20 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ progressForm.processing ? 'Menyimpan...' : 'Simpan & Laporkan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Object,
    materials: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

function performSearch() {
    router.get('/cbp/laporan', {
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
}

// Progress Modal
const showProgressModal = ref(false);
const selectedCbp = ref(null);
const progressForm = useForm({
    status: 'completed',
    notes: '',
    materials: [],
});

function addMaterial() {
    progressForm.materials.push({
        material_id: '',
        quantity: 1,
    });
}

function removeMaterial(index) {
    progressForm.materials.splice(index, 1);
}

function openProgressModal(req) {
    selectedCbp.value = req;
    progressForm.status = 'completed';
    progressForm.notes = req.notes || '';
    progressForm.materials = [];
    showProgressModal.value = true;
}

function closeProgressModal() {
    showProgressModal.value = false;
    selectedCbp.value = null;
    progressForm.reset();
}

function submitProgress() {
    progressForm.post(`/cbp/${selectedCbp.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => closeProgressModal(),
    });
}
</script>
