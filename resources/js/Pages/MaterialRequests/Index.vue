<template>
    <AppLayout title="Permintaan Material" subtitle="Daftar permintaan tambahan material instalasi dari teknisi">
        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pending -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-6 text-white shadow-lg shadow-amber-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-amber-100 font-medium text-sm mb-1">Menunggu Persetujuan</p>
                                <h3 class="text-3xl font-bold">{{ summary.pending }} <span class="text-lg font-normal text-amber-200">Request</span></h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approved -->
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg shadow-emerald-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-emerald-100 font-medium text-sm mb-1">Disetujui</p>
                                <h3 class="text-3xl font-bold">{{ summary.approved }} <span class="text-lg font-normal text-emerald-200">Request</span></h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-6 text-white shadow-lg shadow-red-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-red-100 font-medium text-sm mb-1">Ditolak</p>
                                <h3 class="text-3xl font-bold">{{ summary.rejected }} <span class="text-lg font-normal text-red-200">Request</span></h3>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="glass-card p-5 animate-fade-in-up">
                <div class="flex flex-col lg:flex-row justify-between gap-4">
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="relative">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                            <input v-model="filterForm.search" type="text" placeholder="Cari No. Request, Teknisi..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                            <select v-model="filterForm.status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white">
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu Persetujuan</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-end gap-3">
                        <button @click="resetFilters" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors text-sm flex items-center gap-2">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Request List -->
            <div class="glass-card overflow-hidden animate-fade-in-up" style="animation-delay: 0.1s">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 bg-gray-50/50 uppercase">
                            <tr>
                                <th class="px-6 py-4 font-bold">Waktu & Info</th>
                                <th class="px-6 py-4 font-bold">Teknisi / Area</th>
                                <th class="px-6 py-4 font-bold">Item Material</th>
                                <th class="px-6 py-4 font-bold">Catatan</th>
                                <th class="px-6 py-4 font-bold">Status</th>
                                <th class="px-6 py-4 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="requests.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <p>Tidak ada data permintaan material yang ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="request in requests.data" :key="request.id" class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ request.request_number }}</div>
                                    <div class="text-xs text-gray-500">{{ formatDate(request.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-800">{{ request.user?.name || '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ request.area?.name || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <ul class="text-xs space-y-1">
                                        <li v-for="item in request.items" :key="item.id" class="flex gap-2">
                                            <span class="font-medium text-gray-700">{{ item.material?.name }}</span>
                                            <span class="text-gray-500">({{ item.quantity }} {{ item.unit || item.material?.unit }})</span>
                                        </li>
                                    </ul>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-gray-600 line-clamp-2">{{ request.notes || '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="request.status === 'pending'" class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold border border-amber-200">Menunggu</span>
                                    <span v-else-if="request.status === 'approved'" class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200">Disetujui</span>
                                    <span v-else class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold border border-red-200">Ditolak</span>
                                    
                                    <div v-if="request.status !== 'pending' && request.approver" class="text-[10px] text-gray-500 mt-1">
                                        Oleh: {{ request.approver.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div v-if="request.status === 'pending'" class="flex justify-end gap-2">
                                        <button @click="processRequest(request, 'approve')" class="px-3 py-1.5 bg-emerald-500 text-white text-xs font-bold rounded-lg hover:bg-emerald-600 transition-colors">Setujui</button>
                                        <button @click="processRequest(request, 'reject')" class="px-3 py-1.5 bg-red-500 text-white text-xs font-bold rounded-lg hover:bg-red-600 transition-colors">Tolak</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div v-if="requests.data.length > 0" class="flex justify-center mt-6">
                <div class="flex gap-1 glass-card p-1 rounded-xl">
                    <Component 
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in requests.links" 
                        :key="index"
                        :href="link.url"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="[
                            link.active ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Object,
    filters: Object,
    summary: Object,
});

const filterForm = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

let searchTimeout = null;
watch(filterForm, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('material-requests.index'), value, { preserveState: true, preserveScroll: true });
    }, 300);
}, { deep: true });

function resetFilters() {
    filterForm.value = { search: '', status: '' };
}

function processRequest(request, action) {
    if (!confirm(`Apakah Anda yakin ingin ${action === 'approve' ? 'MENYETUJUI' : 'MENOLAK'} request material ini?`)) return;
    
    router.post(route(`material-requests.${action}`, request.id), {}, {
        preserveScroll: true
    });
}
</script>
