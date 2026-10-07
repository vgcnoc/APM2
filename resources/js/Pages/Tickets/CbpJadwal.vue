<template>
    <AppLayout title="Jadwal CBP" subtitle="Manajemen Jadwal Pencabutan Perangkat">
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
                                            'bg-orange-50 text-orange-600 border-orange-200': req.status === 'pending',
                                            'bg-blue-50 text-blue-600 border-blue-200': req.status === 'assigned',
                                            'bg-emerald-50 text-emerald-600 border-emerald-200': req.status === 'completed',
                                            'bg-gray-50 text-gray-600 border-gray-200': req.status === 'canceled'
                                        }">
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
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="req.status === 'pending'" @click="openAssignModal(req)" class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors" title="Tugaskan Teknisi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        </button>
                                        <button v-if="req.status === 'assigned'" @click="openAssignModal(req)" class="p-1.5 text-orange-600 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg transition-colors" title="Ubah Teknisi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p>Belum ada Jadwal CBP.</p>
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
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Pilih Teknisi <span class="text-gray-400 lowercase font-normal">(Bisa pilih lebih dari 1)</span></label>
                                <select multiple v-model="assignForm.technicians" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all h-32" required>
                                    <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                                </select>
                                <p class="text-[10px] text-gray-500 mt-1">Tahan tombol CTRL (atau Command di Mac) untuk memilih beberapa teknisi.</p>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeAssignModal" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="assignForm.processing || assignForm.technicians.length === 0" class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-blue-500/20 hover:bg-blue-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ assignForm.processing ? 'Menyimpan...' : 'Tugaskan' }}
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
    technicians: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

function performSearch() {
    router.get('/cbp/jadwal', {
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
}

// Assign Modal
const showAssignModal = ref(false);
const selectedCbp = ref(null);
const assignForm = useForm({
    technicians: [],
});

function openAssignModal(req) {
    selectedCbp.value = req;
    assignForm.technicians = req.technicians ? req.technicians.map(t => t.id) : [];
    showAssignModal.value = true;
}

function closeAssignModal() {
    showAssignModal.value = false;
    selectedCbp.value = null;
    assignForm.reset();
}

function submitAssign() {
    assignForm.post(`/cbp/${selectedCbp.value.id}/assign`, {
        preserveScroll: true,
        onSuccess: () => closeAssignModal(),
    });
}
</script>
