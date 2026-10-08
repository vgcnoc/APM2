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
        <!-- Progress Modal (Ultra Modern) -->
        <Teleport to="body">
            <div v-if="showProgressModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" @click="closeProgressModal"></div>
                <div class="bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.2)] w-full max-w-md relative z-10 animate-fade-in-up border border-white/40 overflow-hidden flex flex-col">
                    
                    <!-- Decorative background blur -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-400/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 -left-24 w-40 h-40 bg-teal-400/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="px-7 py-5 flex items-center justify-between bg-white/60 backdrop-blur-xl border-b border-gray-100 z-10 relative">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30 text-white shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Laporan Teknisi</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Laporan hasil cabut perangkat</p>
                            </div>
                        </div>
                        <button @click="closeProgressModal" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2.5 rounded-full transition-all hover:rotate-90 duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitProgress" class="flex flex-col relative z-10">
                        <div class="p-7 space-y-6 max-h-[65vh] overflow-y-auto custom-scrollbar">
                            
                            <!-- Customer Info -->
                            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 flex gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shrink-0 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">{{ selectedCbp?.customer?.name }}</h4>
                                    <p class="text-xs text-slate-500 mt-0.5 font-medium">{{ selectedCbp?.customer?.customer_code }} <span v-if="selectedCbp?.customer?.area"> • {{ selectedCbp?.customer?.area?.name || selectedCbp?.customer?.area }}</span></p>
                                </div>
                            </div>

                            <!-- Time Input -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Jam Mulai -->
                                <div class="bg-slate-50 rounded-2xl p-4 border-2 border-slate-200 flex flex-col justify-center items-center gap-2">
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider">Jam Mulai</label>
                                    <div v-if="progressForm.start_time" class="text-2xl font-black text-slate-800 tracking-tight">{{ progressForm.start_time }}</div>
                                    <button v-else type="button" @click="setNow('start_time')" class="text-xs bg-blue-600 text-white font-bold px-4 py-2 rounded-xl hover:bg-blue-700 transition-colors shadow-sm w-full">MULAI SEKARANG</button>
                                </div>
                                <!-- Jam Selesai -->
                                <div class="bg-slate-50 rounded-2xl p-4 border-2 border-slate-200 flex flex-col justify-center items-center gap-2">
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider">Jam Selesai</label>
                                    <div v-if="progressForm.end_time" class="text-2xl font-black text-slate-800 tracking-tight">{{ progressForm.end_time }}</div>
                                    <button v-else type="button" @click="setNow('end_time')" :disabled="!progressForm.start_time" :class="['text-xs font-bold px-4 py-2 rounded-xl transition-colors shadow-sm w-full', !progressForm.start_time ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-emerald-600 text-white hover:bg-emerald-700']">SELESAI SEKARANG</button>
                                </div>
                            </div>
                            
                            <!-- Photo Progress -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Foto Progress</label>
                                <input type="file" @change="e => progressForm.photo = e.target.files[0]" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 file:transition-colors bg-slate-50 border-2 border-slate-200 rounded-2xl cursor-pointer">
                                <div v-if="progressForm.errors?.photo" class="text-xs text-rose-500 mt-1.5">{{ progressForm.errors.photo }}</div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Catatan Teknisi <span class="text-slate-400 lowercase font-normal">(opsional)</span></label>
                                <textarea v-model="progressForm.notes" rows="3" class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all placeholder-slate-400 resize-none custom-scrollbar" placeholder="Tuliskan kendala atau catatan..."></textarea>
                            </div>

                            <!-- Device/Material info during installation -->
                            <div class="bg-amber-50/50 border border-amber-100 rounded-2xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                        </div>
                                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Perangkat Terpasang (Instalasi)</label>
                                    </div>
                                </div>
                                <!-- Render based on hardwareItems from Installation Data -->
                                <div v-if="hardwareItems.length > 0" class="space-y-2">
                                    <div v-for="item in hardwareItems" :key="item.id" class="flex flex-col gap-2 bg-white rounded-xl p-3 border border-slate-200 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider font-bold">{{ item.type }}</div>
                                                <div class="text-sm font-bold text-slate-800">{{ item.name }}</div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <template v-if="item.type === 'Material' && item.name.toLowerCase().includes('kabel')">
                                                    <div v-if="hasMaterial(item.id)" class="relative w-20">
                                                        <input type="number" v-model="getMaterial(item.id).quantity" min="1" class="w-full px-2 py-1 text-sm border-2 border-slate-200 rounded-lg text-center font-bold focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Meter">
                                                    </div>
                                                </template>
                                                <button v-if="!hasMaterial(item.id)" type="button" @click="cabutItem(item)" class="text-xs bg-rose-100 text-rose-700 font-bold px-3 py-1.5 rounded-lg hover:bg-rose-200 transition-colors shadow-sm whitespace-nowrap">
                                                    Cabut
                                                </button>
                                                <div v-else class="text-xs bg-emerald-100 text-emerald-700 font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 shadow-sm h-[32px] whitespace-nowrap">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    Dicabut
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-slate-500 mb-2 italic p-3 bg-white border border-slate-200 rounded-xl">Data Instalasi tidak ditemukan.</div>
                            </div>
                        </div>

                        <div class="px-7 py-5 bg-slate-50/80 backdrop-blur-md border-t border-slate-100 flex justify-end gap-3 z-10 rounded-b-3xl">
                            <button type="button" @click="closeProgressModal" class="px-6 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200/50 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="progressForm.processing" class="relative group overflow-hidden bg-emerald-600 text-white px-7 py-2.5 rounded-xl text-sm font-bold shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] hover:bg-emerald-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed">
                                <span class="relative z-10 flex items-center gap-2">
                                    <svg v-if="progressForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ progressForm.processing ? 'Menyimpan...' : 'Simpan Laporan' }}
                                </span>
                                <div class="absolute inset-0 h-full w-full bg-gradient-to-r from-emerald-500 to-teal-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
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
    start_time: '',
    end_time: '',
    photo: null,
    notes: '',
    materials: [],
});

function setNow(field) {
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    progressForm[field] = timeString;
    
    // Save to localStorage if it's start_time
    if (selectedCbp.value && field === 'start_time') {
        localStorage.setItem(`cbp_start_${selectedCbp.value.id}`, timeString);
    }
}

const hardwareItems = computed(() => {
    if (!selectedCbp.value || !selectedCbp.value.customer) return [];
    
    // Parse from installation report
    const schedule = selectedCbp.value.customer.technician_schedules?.find(s => s.type === 'installation');
    let items = [];
    let idCounter = 0;
    
    if (schedule && schedule.notes) {
        const lines = schedule.notes.split('\n');
        lines.forEach(line => {
            if (line.startsWith('ONT: ')) {
                const onts = line.replace('ONT: ', '').split(', ');
                onts.forEach(ont => {
                    if(ont.trim()) items.push({ id: idCounter++, type: 'ONT', name: ont.trim() });
                });
            } else if (line.startsWith('Material: ')) {
                const mats = line.replace('Material: ', '').split(', ');
                mats.forEach(mat => {
                    if(mat.trim()) items.push({ id: idCounter++, type: 'Material', name: mat.trim() });
                });
            }
        });
    }

    // Fallback if installation report notes doesn't exist, use the active ONT
    if (items.length === 0 && selectedCbp.value.customer.ont) {
        items.push({
            id: idCounter++,
            type: 'ONT',
            name: `ONT ${selectedCbp.value.customer.ont.brand} ${selectedCbp.value.customer.ont.model} (SN: ${selectedCbp.value.customer.ont.serial_number || '-'})`
        });
        items.push({
            id: idCounter++,
            type: 'Material',
            name: 'Kabel Fiber Optik (Drop Core)'
        });
    }
    
    return items;
});

function hasMaterial(id) {
    return progressForm.materials.some(m => m._id === id);
}

function getMaterial(id) {
    return progressForm.materials.find(m => m._id === id);
}

function cabutItem(item) {
    let unit = 'pcs';
    if (item.type === 'Material' && item.name.toLowerCase().includes('kabel')) unit = 'm';
    
    // Find material_id by matching name with props.materials
    let matId = '';
    const nameLower = item.name.toLowerCase();
    
    if (item.type === 'ONT') {
        const ontMat = props.materials.find(m => m.category === 'ont' || m.name.toLowerCase().includes('ont'));
        if (ontMat) matId = ontMat.id;
    } else {
        const matched = props.materials.find(m => nameLower.includes(m.name.toLowerCase()) || m.name.toLowerCase().includes(nameLower));
        if (matched) {
            matId = matched.id;
        } else if (nameLower.includes('kabel')) {
            const kabelMat = props.materials.find(m => m.category === 'kabel' || m.name.toLowerCase().includes('kabel'));
            if (kabelMat) matId = kabelMat.id;
        }
    }
    
    progressForm.materials.push({
        material_id: matId,
        quantity: 1,
        unit: unit,
        _id: item.id,
        _name: item.name
    });
}

function openProgressModal(req) {
    selectedCbp.value = req;
    
    // Restore start_time from localStorage if available
    const savedStart = localStorage.getItem(`cbp_start_${req.id}`);
    progressForm.start_time = savedStart || '';
    
    progressForm.end_time = '';
    progressForm.photo = null;
    progressForm.notes = req.notes || '';
    progressForm.materials = [];
    showProgressModal.value = true;
}

function closeProgressModal() {
    showProgressModal.value = false;
    selectedCbp.value = null;
    progressForm.reset();
    progressForm.clearErrors();
}

function submitProgress() {
    progressForm.post(`/cbp/${selectedCbp.value.id}/status`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            if (selectedCbp.value) {
                localStorage.removeItem(`cbp_start_${selectedCbp.value.id}`);
            }
            closeProgressModal();
        },
    });
}
</script>
