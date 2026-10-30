<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { compressImage, formatBytes } from '@/Utils/imageCompressor';

const page = usePage();

const props = defineProps({
    maintenanceList: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ total: 0, done: 0, in_progress: 0, pending: 0, rescheduled: 0, my_tasks: 0 }) },
    areas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    availableTechnicians: { type: Array, default: () => [] },
    availableOdps: { type: Array, default: () => [] },
    availableOdcs: { type: Array, default: () => [] },
    indonesianDate: { type: String, default: '' },
    canCreate: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: false },
});

// Helper Tanggal Lokal (WIB / Browser timezone)
function getLocalDateString(offsetDays = 0) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getCurrentTimeStr() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    return `${h}:${m}`;
}

// Filter States
const filterDate = ref(props.filters?.date || getLocalDateString(0));
const filterArea = ref(props.filters?.area_id || '');
const filterStatus = ref(props.filters?.status || 'all');
const filterCategory = ref(props.filters?.category || 'all');
const searchQuery = ref(props.filters?.search || '');
const filterOnlyMyTasks = ref(props.filters?.my_tasks || false);
const isLoading = ref(false);

// Filter trigger
function applyFilters() {
    isLoading.value = true;
    router.get('/operational/network-maintenance', {
        date: filterDate.value,
        area_id: filterArea.value || '',
        status: filterStatus.value,
        category: filterCategory.value,
        search: searchQuery.value,
        my_tasks: filterOnlyMyTasks.value ? 1 : 0,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { isLoading.value = false; }
    });
}

function setQuickDate(offsetDays) {
    filterDate.value = getLocalDateString(offsetDays);
    applyFilters();
}

function toggleMyTasks(val) {
    filterOnlyMyTasks.value = val;
    applyFilters();
}

function filterByStatusCard(statusKey) {
    filterStatus.value = filterStatus.value === statusKey ? 'all' : statusKey;
    applyFilters();
}

// Filtered Maintenance List (Instant client-side filter if search text changed quickly)
const currentUserId = computed(() => page.props.auth?.user?.id);
const currentUserName = computed(() => (page.props.auth?.user?.name || '').toLowerCase());

const displayedList = computed(() => {
    return props.maintenanceList || [];
});

// ══════════════════════════════════════════════════════════════════
// MODAL: UPDATE STATUS & LAPORAN KERJA CEPAT
// ══════════════════════════════════════════════════════════════════
const showUpdateModal = ref(false);
const activeMaintenanceItem = ref(null);
const updatePhotoAfterFile = ref(null);
const isSubmitting = ref(false);

const updateForm = ref({
    status: 'completed',
    start_time: '',
    end_time: '',
    optical_power_after: '',
    action_taken: '',
    scheduled_date: '',
});

const isCompressingPhotoAfter = ref(false);
const photoAfterCompressionInfo = ref(null);

function openQuickUpdateModal(item) {
    activeMaintenanceItem.value = item;
    updatePhotoAfterFile.value = null;
    photoAfterCompressionInfo.value = null;
    updateForm.value = {
        status: item.is_done ? 'completed' : (item.is_in_progress ? 'completed' : 'in_progress'),
        start_time: item.start_time || '',
        end_time: item.end_time || '',
        optical_power_after: item.optical_power_after || '',
        action_taken: item.action_taken || '',
        scheduled_date: item.scheduled_date || filterDate.value,
    };
    showUpdateModal.value = true;
}

function selectUpdateStatus(newStatus) {
    updateForm.value.status = newStatus;
    const nowStr = getCurrentTimeStr();
    if (newStatus === 'in_progress') {
        if (!updateForm.value.start_time) {
            updateForm.value.start_time = nowStr;
        }
    } else if (newStatus === 'completed') {
        if (!updateForm.value.end_time) {
            updateForm.value.end_time = nowStr;
        }
        if (!updateForm.value.start_time) {
            updateForm.value.start_time = nowStr;
        }
    }
}

async function handlePhotoAfterUpload(e) {
    const file = e.target.files?.[0];
    if (!file) {
        updatePhotoAfterFile.value = null;
        photoAfterCompressionInfo.value = null;
        return;
    }

    isCompressingPhotoAfter.value = true;
    try {
        const res = await compressImage(file, { maxWidth: 1920, maxHeight: 1920, quality: 0.82 });
        updatePhotoAfterFile.value = res.file;
        photoAfterCompressionInfo.value = res;
    } catch (err) {
        console.warn('Gagal kompres foto:', err);
        updatePhotoAfterFile.value = file;
    } finally {
        isCompressingPhotoAfter.value = false;
    }
}

function removePhotoAfter() {
    updatePhotoAfterFile.value = null;
    photoAfterCompressionInfo.value = null;
}

async function submitUpdateMaintenance() {
    if (!activeMaintenanceItem.value) return;
    isSubmitting.value = true;
    try {
        const payload = new FormData();
        payload.append('status', updateForm.value.status);
        if (updateForm.value.action_taken) payload.append('action_taken', updateForm.value.action_taken);
        if (updateForm.value.optical_power_after) payload.append('optical_power_after', updateForm.value.optical_power_after);
        if (updateForm.value.start_time) payload.append('start_time', updateForm.value.start_time);
        if (updateForm.value.end_time) payload.append('end_time', updateForm.value.end_time);
        if (updateForm.value.scheduled_date) payload.append('scheduled_date', updateForm.value.scheduled_date);
        if (updatePhotoAfterFile.value) payload.append('photo_after', updatePhotoAfterFile.value);

        const res = await axios.post(`/operational/network-maintenance/${activeMaintenanceItem.value.id}/quick-update`, payload);
        if (res.data && res.data.success) {
            alert('✓ ' + (res.data.message || 'Status berhasil diperbarui!'));
            showUpdateModal.value = false;
            applyFilters();
        } else {
            alert('⚠️ ' + (res.data?.message || 'Gagal memperbarui status.'));
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'Terjadi kesalahan saat update status.';
        alert('❌ Error: ' + msg);
    } finally {
        isSubmitting.value = false;
    }
}

// ══════════════════════════════════════════════════════════════════
// MODAL: BUAT JADWAL PEMELIHARAAN BARU
// ══════════════════════════════════════════════════════════════════
const showCreateModal = ref(false);
const createPhotoBeforeFile = ref(null);
const showAllAreaTechs = ref(false);

const createForm = ref({
    category: 'perapihan_jalur',
    title: '',
    area_id: '',
    asset_type: 'manual',
    odp_id: '',
    odc_id: '',
    location_name: '',
    scheduled_date: getLocalDateString(0),
    scheduled_time: '09:00',
    priority: 'normal',
    description: '',
    technician_ids: [],
});

const filteredOdps = computed(() => {
    if (!createForm.value.area_id) return props.availableOdps;
    return props.availableOdps.filter(o => String(o.area_id) === String(createForm.value.area_id));
});

const filteredOdcs = computed(() => {
    if (!createForm.value.area_id) return props.availableOdcs;
    return props.availableOdcs.filter(c => String(c.area_id) === String(createForm.value.area_id));
});

const filteredTechnicians = computed(() => {
    if (!createForm.value.area_id || showAllAreaTechs.value) {
        return props.availableTechnicians;
    }
    return props.availableTechnicians.filter(
        t => String(t.area_id) === String(createForm.value.area_id) || createForm.value.technician_ids.includes(t.id)
    );
});

const isCompressingPhotoBefore = ref(false);
const photoBeforeCompressionInfo = ref(null);

function openCreateModal() {
    createPhotoBeforeFile.value = null;
    photoBeforeCompressionInfo.value = null;
    showAllAreaTechs.value = false;
    createForm.value = {
        category: 'perapihan_jalur',
        title: '',
        area_id: filterArea.value || props.areas?.[0]?.id || '',
        asset_type: 'manual',
        odp_id: '',
        odc_id: '',
        location_name: '',
        scheduled_date: filterDate.value || getLocalDateString(0),
        scheduled_time: getCurrentTimeStr(),
        priority: 'normal',
        description: '',
        technician_ids: [],
    };
    showCreateModal.value = true;
}

async function handlePhotoBeforeUpload(e) {
    const file = e.target.files?.[0];
    if (!file) {
        createPhotoBeforeFile.value = null;
        photoBeforeCompressionInfo.value = null;
        return;
    }

    isCompressingPhotoBefore.value = true;
    try {
        const res = await compressImage(file, { maxWidth: 1920, maxHeight: 1920, quality: 0.82 });
        createPhotoBeforeFile.value = res.file;
        photoBeforeCompressionInfo.value = res;
    } catch (err) {
        console.warn('Gagal kompres foto sebelum:', err);
        createPhotoBeforeFile.value = file;
    } finally {
        isCompressingPhotoBefore.value = false;
    }
}

function removePhotoBefore() {
    createPhotoBeforeFile.value = null;
    photoBeforeCompressionInfo.value = null;
}

async function submitCreateMaintenance() {
    if (!createForm.value.title) {
        alert('Harap isi judul kegiatan pemeliharaan!');
        return;
    }
    isSubmitting.value = true;
    try {
        const payload = new FormData();
        payload.append('category', createForm.value.category);
        payload.append('title', createForm.value.title);
        payload.append('scheduled_date', createForm.value.scheduled_date);
        if (createForm.value.scheduled_time) payload.append('scheduled_time', createForm.value.scheduled_time);
        if (createForm.value.priority) payload.append('priority', createForm.value.priority);
        if (createForm.value.area_id) payload.append('area_id', createForm.value.area_id);
        if (createForm.value.description) payload.append('description', createForm.value.description);
        
        if (createForm.value.asset_type === 'odp' && createForm.value.odp_id) {
            payload.append('odp_id', createForm.value.odp_id);
        } else if (createForm.value.asset_type === 'odc' && createForm.value.odc_id) {
            payload.append('odc_id', createForm.value.odc_id);
        } else if (createForm.value.location_name) {
            payload.append('location_name', createForm.value.location_name);
        }

        if (createForm.value.technician_ids && createForm.value.technician_ids.length > 0) {
            createForm.value.technician_ids.forEach((id) => {
                payload.append('technician_ids[]', id);
            });
        }

        if (createPhotoBeforeFile.value) {
            payload.append('photo_before', createPhotoBeforeFile.value);
        }

        const res = await axios.post('/operational/network-maintenance', payload);
        if (res.data && res.data.success) {
            alert('✓ ' + (res.data.message || 'Kegiatan pemeliharaan berhasil ditambahkan!'));
            showCreateModal.value = false;
            applyFilters();
        } else {
            alert('⚠️ ' + (res.data?.message || 'Gagal menambahkan kegiatan.'));
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'Terjadi kesalahan saat simpan data.';
        alert('❌ Error: ' + msg);
    } finally {
        isSubmitting.value = false;
    }
}

async function deleteMaintenance(item) {
    if (!confirm(`Hapus kegiatan "${item.title}" (${item.maintenance_number})?`)) return;
    try {
        const res = await axios.delete(`/operational/network-maintenance/${item.id}`);
        if (res.data && res.data.success) {
            alert('✓ ' + (res.data.message || 'Kegiatan berhasil dihapus!'));
            applyFilters();
        } else {
            alert('⚠️ ' + (res.data?.message || 'Gagal menghapus kegiatan.'));
        }
    } catch (err) {
        alert('❌ Error: ' + (err.response?.data?.message || err.message));
    }
}

// Category Badge Helper
function getCategoryBadgeClass(category) {
    switch (category) {
        case 'perapihan_jalur':
            return 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800';
        case 'pengecekan_odp':
            return 'bg-teal-50 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 border-teal-300 dark:border-teal-800';
        case 'pengecekan_odc':
            return 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border-indigo-300 dark:border-indigo-800';
        case 'pengecekan_jalur_utama':
            return 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border-rose-300 dark:border-rose-800';
        default:
            return 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700';
    }
}
</script>

<template>
    <AppLayout 
        title="Pemeliharaan Jalur (ODP/ODC)" 
        subtitle="Pusat Pengerjaan & Laporan Perapihan Jalur, ODP, ODC & Kabel FO Lapangan"
    >
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            
            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- 1. TOP HEADER & PENGATUR TANGGAL / WILAYAH                 -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    
                    <!-- Header Info -->
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-teal-500 to-emerald-600 text-white flex items-center justify-center shadow-md shadow-teal-500/20 shrink-0 text-2xl font-black">
                            🔧
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                                    Pemeliharaan & Perapihan Jalur
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-teal-50 dark:bg-teal-950/70 text-teal-700 dark:text-teal-400 border border-teal-200 dark:border-teal-900/50 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-ping"></span>
                                    Teknisi Lapangan
                                </span>
                            </div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ indonesianDate }} · <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ areas.find(a => a.id === filterArea)?.name || 'Semua Wilayah' }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Date & Area Controls -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Quick Dates -->
                        <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 text-xs font-bold text-slate-600 dark:text-slate-300">
                            <button 
                                @click="setQuickDate(-1)"
                                class="px-2.5 py-1.5 rounded-lg hover:bg-white dark:hover:bg-slate-700 transition-all cursor-pointer"
                            >
                                Kemarin
                            </button>
                            <button 
                                @click="setQuickDate(0)"
                                class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
                                :class="filterDate === getLocalDateString(0) ? 'bg-white dark:bg-slate-700 text-teal-700 dark:text-teal-400 shadow-xs' : 'hover:bg-white dark:hover:bg-slate-700'"
                            >
                                Hari Ini
                            </button>
                            <button 
                                @click="setQuickDate(1)"
                                class="px-2.5 py-1.5 rounded-lg hover:bg-white dark:hover:bg-slate-700 transition-all cursor-pointer"
                            >
                                Besok
                            </button>
                        </div>

                        <!-- Date Picker -->
                        <input 
                            type="date" 
                            v-model="filterDate"
                            @change="applyFilters"
                            class="px-3 py-1.5 text-xs font-medium rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                        />

                        <!-- Area Selector -->
                        <select 
                            v-model="filterArea"
                            @change="applyFilters"
                            class="px-3 py-1.5 text-xs font-medium rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                        >
                            <option value="">🌐 Semua Wilayah / Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>

                        <!-- Tombol Tambah Jadwal -->
                        <button
                            v-if="canCreate"
                            type="button"
                            @click="openCreateModal"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 transition-all cursor-pointer inline-flex items-center gap-1.5 shrink-0"
                        >
                            <span>+</span>
                            <span>Jadwal Jalur Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- 2. STATISTIK CARDS (RINGKASAN TUGAS HARI INI)              -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
                
                <!-- Card 1: Total Tugas -->
                <div 
                    @click="filterByStatusCard('all')"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="filterStatus === 'all' 
                        ? 'bg-slate-900 text-white border-slate-900 dark:bg-white dark:text-slate-900 ring-2 ring-slate-400' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-slate-400'"
                >
                    <div class="flex items-center justify-between text-xs font-bold opacity-80 mb-1">
                        <span>📦 Total Tugas</span>
                        <span class="text-sm">➔</span>
                    </div>
                    <div class="text-2xl font-black">{{ summary.total || 0 }}</div>
                    <div class="text-[11px] opacity-70 mt-1">Jadwal target hari ini</div>
                </div>

                <!-- Card 2: Sedang Dikerjakan -->
                <div 
                    @click="filterByStatusCard('in_progress')"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="filterStatus === 'in_progress' 
                        ? 'bg-blue-50/90 dark:bg-blue-950/60 border-blue-500 ring-2 ring-blue-300 text-blue-900 dark:text-blue-100' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-blue-400 text-slate-800 dark:text-white'"
                >
                    <div class="flex items-center justify-between text-xs font-bold text-blue-600 dark:text-blue-400 mb-1">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            Sedang Dikerjakan
                        </span>
                        <span class="text-sm">➔</span>
                    </div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ summary.in_progress || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">Petugas di lapangan</div>
                </div>

                <!-- Card 3: Selesai -->
                <div 
                    @click="filterByStatusCard('completed')"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="filterStatus === 'completed' 
                        ? 'bg-emerald-50/90 dark:bg-emerald-950/60 border-emerald-500 ring-2 ring-emerald-300 text-emerald-900 dark:text-emerald-100' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-emerald-400 text-slate-800 dark:text-white'"
                >
                    <div class="flex items-center justify-between text-xs font-bold text-emerald-600 dark:text-emerald-400 mb-1">
                        <span>✅ Selesai Dikerjakan</span>
                        <span class="text-sm">➔</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ summary.done || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">Laporan & redaman OK</div>
                </div>

                <!-- Card 4: Pending / Jadwal -->
                <div 
                    @click="filterByStatusCard('scheduled')"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md"
                    :class="filterStatus === 'scheduled' 
                        ? 'bg-amber-50/90 dark:bg-amber-950/60 border-amber-500 ring-2 ring-amber-300 text-amber-900 dark:text-amber-100' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-amber-400 text-slate-800 dark:text-white'"
                >
                    <div class="flex items-center justify-between text-xs font-bold text-amber-600 dark:text-amber-400 mb-1">
                        <span>⏳ Pending / Jadwal</span>
                        <span class="text-sm">➔</span>
                    </div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ summary.pending || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">Menunggu penanganan</div>
                </div>

                <!-- Card 5: Tugas Saya -->
                <div 
                    @click="toggleMyTasks(!filterOnlyMyTasks)"
                    class="p-4 rounded-2xl border transition-all cursor-pointer group hover:shadow-md col-span-2 md:col-span-1"
                    :class="filterOnlyMyTasks 
                        ? 'bg-teal-600 text-white border-teal-600 ring-2 ring-teal-300' 
                        : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-teal-400 text-slate-800 dark:text-white'"
                >
                    <div class="flex items-center justify-between text-xs font-bold mb-1" :class="filterOnlyMyTasks ? 'text-teal-100' : 'text-teal-600 dark:text-teal-400'">
                        <span>👤 Tugas Saya</span>
                        <span class="text-sm">➔</span>
                    </div>
                    <div class="text-2xl font-black" :class="filterOnlyMyTasks ? 'text-white' : 'text-teal-600 dark:text-teal-400'">{{ summary.my_tasks || 0 }}</div>
                    <div class="text-[11px] mt-1" :class="filterOnlyMyTasks ? 'text-teal-100' : 'text-slate-400'">
                        {{ filterOnlyMyTasks ? 'Sedang difilter' : 'Klik untuk filter saya' }}
                    </div>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- 3. BAR FILTER & PENCARIAN TUGAS                           -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 border border-slate-200/80 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-3">
                
                <!-- Tab Mode: Semua vs Tugas Saya -->
                <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl text-xs font-bold w-full md:w-auto">
                    <button
                        type="button"
                        @click="toggleMyTasks(false)"
                        class="flex-1 md:flex-none px-4 py-2 rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5"
                        :class="!filterOnlyMyTasks ? 'bg-white dark:bg-slate-900 text-teal-800 dark:text-teal-400 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                    >
                        <span>👥 Semua Tugas</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-200 dark:bg-slate-700">
                            {{ summary.total || 0 }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="toggleMyTasks(true)"
                        class="flex-1 md:flex-none px-4 py-2 rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5"
                        :class="filterOnlyMyTasks ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                    >
                        <span>👤 Tugas Saya</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="filterOnlyMyTasks ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700'">
                            {{ summary.my_tasks || 0 }}
                        </span>
                    </button>
                </div>

                <!-- Dropdown Filters & Search Input -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
                    <!-- Filter Status -->
                    <select
                        v-model="filterStatus"
                        @change="applyFilters"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300"
                    >
                        <option value="all">Status: Semua</option>
                        <option value="scheduled">⏳ Pending / Jadwal</option>
                        <option value="in_progress">🛠️ Sedang Dikerjakan</option>
                        <option value="completed">✅ Selesai Dikerjakan</option>
                        <option value="rescheduled">🗓️ Reschedule</option>
                    </select>

                    <!-- Filter Kategori -->
                    <select
                        v-model="filterCategory"
                        @change="applyFilters"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300"
                    >
                        <option value="all">Kategori: Semua</option>
                        <option value="perapihan_jalur">🧹 Perapihan Jalur & Kabel</option>
                        <option value="pengecekan_odp">📦 Pengecekan ODP</option>
                        <option value="pengecekan_odc">🗄️ Pengecekan ODC</option>
                        <option value="pengecekan_jalur_utama">⚡ Jalur Utama (Backbone)</option>
                        <option value="pemeliharaan_rutin">🔧 Pemeliharaan Rutin</option>
                    </select>

                    <!-- Search Input -->
                    <div class="relative flex-1 md:w-64">
                        <input
                            type="text"
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            placeholder="Cari MNT, rute, teknisi..."
                            class="w-full pl-8 pr-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500 focus:outline-none"
                        />
                        <span class="absolute left-2.5 top-2.5 text-xs text-slate-400">🔍</span>
                    </div>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- 4. LIST CARD PENGERJAAN JALUR (SESUAI DESAIN USER)        -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div class="space-y-4">
                
                <!-- State Kosong / Nihil -->
                <div 
                    v-if="displayedList.length === 0" 
                    class="bg-white dark:bg-slate-900 rounded-3xl p-12 text-center border border-slate-200/80 dark:border-slate-800"
                >
                    <div class="w-16 h-16 mx-auto rounded-3xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-3xl mb-3 shadow-inner">
                        🛠️
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
                        {{ filterOnlyMyTasks ? 'Tidak Ada Tugas Pemeliharaan yang Ditugaskan ke Anda' : 'Nihil Pemeliharaan & Perapihan Jalur' }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                        {{ filterOnlyMyTasks 
                            ? 'Tidak ada pekerjaan ODP, ODC, atau jalur utama yang tercatat untuk Anda pada tanggal dan filter yang dipilih.' 
                            : 'Belum ada jadwal perapihan kabel kendur atau pemeliharaan jalur yang dibuat untuk tanggal ini.' }}
                    </p>
                    <div class="mt-5" v-if="canCreate && !filterOnlyMyTasks">
                        <button
                            type="button"
                            @click="openCreateModal"
                            class="px-5 py-2.5 text-xs font-bold rounded-2xl bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 transition-all cursor-pointer inline-flex items-center gap-1.5"
                        >
                            <span>+ Buat Jadwal Jalur Sekarang</span>
                        </button>
                    </div>
                </div>

                <!-- Loop Kartu Tugas Lapangan -->
                <div 
                    v-for="(item, idx) in displayedList" 
                    :key="'mnt-' + item.id"
                    class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 hover:shadow-lg hover:border-teal-300 dark:hover:border-teal-700 transition-all space-y-3.5"
                >
                    <!-- Header Baris Atas: Nomor, Judul, Kategori & Status Badge -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <!-- Nomor Urut Bulat -->
                            <div class="w-7 h-7 rounded-xl bg-teal-100 dark:bg-teal-950/70 text-teal-800 dark:text-teal-300 text-xs font-black flex items-center justify-center shrink-0 mt-0.5">
                                {{ idx + 1 }}
                            </div>

                            <!-- Judul & Kategori -->
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm md:text-base font-bold text-slate-900 dark:text-white">
                                        {{ item.title }}
                                    </h3>
                                    <!-- Badge Kategori -->
                                    <span 
                                        class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider border shadow-2xs"
                                        :class="getCategoryBadgeClass(item.category)"
                                    >
                                        {{ item.category_icon || '🛠️' }} {{ item.category_label || item.category }}
                                    </span>
                                    <!-- Badge Prioritas Mendesak -->
                                    <span 
                                        v-if="item.priority === 'urgent' || item.priority === 'high'"
                                        class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1"
                                    >
                                        🔥 Mendesak
                                    </span>
                                </div>

                                <!-- Nomor MNT & Petugas Bertugas -->
                                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="font-mono font-bold text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2 py-0.5 rounded-md">
                                        {{ item.maintenance_number }}
                                    </span>
                                    <span>·</span>
                                    <span class="flex items-center gap-1 font-medium text-slate-700 dark:text-slate-300">
                                        <span>👷</span>
                                        <span>{{ item.technicians }}</span>
                                    </span>
                                    <span v-if="item.is_my_task" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-600 text-white">
                                        Tugas Anda
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge (Kanan Atas) -->
                        <div class="shrink-0">
                            <span 
                                v-if="item.is_done"
                                class="px-3 py-1 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 flex items-center gap-1.5 shadow-2xs"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Selesai
                            </span>
                            <span 
                                v-else-if="item.is_in_progress"
                                class="px-3 py-1 rounded-xl text-xs font-bold bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-400 border border-blue-300 dark:border-blue-800 flex items-center gap-1.5 shadow-2xs animate-pulse"
                            >
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Sedang Dikerjakan
                            </span>
                            <span 
                                v-else-if="item.is_rescheduled"
                                class="px-3 py-1 rounded-xl text-xs font-bold bg-purple-50 dark:bg-purple-950/70 text-purple-700 dark:text-purple-400 border border-purple-300 dark:border-purple-800 flex items-center gap-1.5 shadow-2xs"
                            >
                                <span>🗓️</span>
                                Reschedule
                            </span>
                            <span 
                                v-else
                                class="px-3 py-1 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800 flex items-center gap-1.5 shadow-2xs"
                            >
                                <span>⏳</span>
                                Pending
                            </span>
                        </div>
                    </div>

                    <!-- Kotak Tengah: Wilayah / Lokasi Rute & Waktu Pengerjaan (Identik dengan Screenshot) -->
                    <div class="bg-slate-50/90 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-100 dark:border-slate-800/80 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        
                        <!-- Kolom Kiri: Wilayah & Lokasi Rute -->
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                                WILAYAH / LOKASI RUTE
                            </div>
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                {{ item.location }}
                            </div>
                            <div class="text-slate-500 dark:text-slate-400 text-[11px] mt-0.5">
                                ({{ item.area_name }})
                                <span v-if="item.odp && item.odp !== '-'" class="ml-1 text-teal-600 font-mono">ODP: {{ item.odp }}</span>
                                <span v-if="item.odc && item.odc !== '-'" class="ml-1 text-indigo-600 font-mono">ODC: {{ item.odc }}</span>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Waktu / Jadwal Pengerjaan -->
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                                WAKTU / JADWAL PENGERJAAN
                            </div>
                            <div class="text-sm font-mono font-bold text-slate-800 dark:text-slate-100">
                                {{ item.duration }}
                            </div>
                            <!-- Hasil Redaman jika ada -->
                            <div v-if="item.optical_power_after" class="mt-1 flex items-center gap-1 text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                <span>📊 OPM:</span>
                                <span>{{ item.optical_power_after }}</span>
                            </div>
                        </div>

                        <!-- Jika ada Tindakan Lapangan yang dilaporkan -->
                        <div v-if="item.action_taken" class="md:col-span-2 pt-2 border-t border-slate-200/50 dark:border-slate-700/50">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-0.5">
                                HASIL TINDAKAN LAPANGAN:
                            </span>
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-normal">
                                {{ item.action_taken }}
                            </p>
                        </div>

                        <!-- Foto Dokumentasi jika ada -->
                        <div v-if="item.photo_before || item.photo_after" class="md:col-span-2 flex items-center gap-3 pt-2">
                            <div v-if="item.photo_before" class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 block">Foto Sebelum:</span>
                                <a :href="item.photo_before" target="_blank" class="block">
                                    <img :src="item.photo_before" class="w-16 h-16 object-cover rounded-xl border border-slate-200 shadow-2xs hover:opacity-90" />
                                </a>
                            </div>
                            <div v-if="item.photo_after" class="space-y-1">
                                <span class="text-[10px] font-bold text-teal-600 block">Foto Sesudah:</span>
                                <a :href="item.photo_after" target="_blank" class="block">
                                    <img :src="item.photo_after" class="w-16 h-16 object-cover rounded-xl border border-teal-300 shadow-2xs hover:opacity-90" />
                                </a>
                            </div>
                        </div>

                    </div>

                    <!-- Baris Bawah: Tombol Aksi Update & Hapus -->
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button
                            type="button"
                            @click="openQuickUpdateModal(item)"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 hover:bg-teal-600 hover:text-white border border-teal-200 dark:border-teal-800 transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <span>📝</span>
                            <span>Update Status / Laporan</span>
                        </button>
                        
                        <button
                            v-if="canDelete"
                            type="button"
                            @click="deleteMaintenance(item)"
                            title="Hapus Kegiatan"
                            class="p-2 text-xs font-bold rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-all cursor-pointer"
                        >
                            🗑️
                        </button>
                    </div>

                </div>

            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- MODAL: UPDATE STATUS & LAPORAN KERJA CEPAT                -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div 
            v-if="showUpdateModal && activeMaintenanceItem" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
            @click.self="showUpdateModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 my-8 animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-teal-500 text-white flex items-center justify-center font-bold text-lg">
                            📝
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Update Status & Laporan Hasil Kerja</h3>
                            <p class="text-[11px] text-slate-400 font-mono">{{ activeMaintenanceItem.maintenance_number }} · {{ activeMaintenanceItem.title }}</p>
                        </div>
                    </div>
                    <button 
                        @click="showUpdateModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Info Target & Petugas -->
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 text-xs space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Target & Wilayah:</span>
                        <span class="font-bold text-teal-600 dark:text-teal-400 font-mono">
                            {{ activeMaintenanceItem.category_icon || '🛠️' }} {{ activeMaintenanceItem.location }} ({{ activeMaintenanceItem.area_name }})
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Petugas Ditugaskan:</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">👷 {{ activeMaintenanceItem.technicians }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitUpdateMaintenance" class="space-y-3.5">
                    
                    <!-- 1. Pilihan Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pilih Status Saat Ini:
                        </label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label 
                                @click="selectUpdateStatus('in_progress')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'in_progress' ? 'bg-blue-50 border-blue-500 text-blue-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="in_progress" class="sr-only" />
                                <span>🛠️ Sedang Dikerjakan</span>
                            </label>
                            <label 
                                @click="selectUpdateStatus('completed')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'completed' ? 'bg-emerald-50 border-emerald-500 text-emerald-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="completed" class="sr-only" />
                                <span>✅ Selesai Dikerjakan</span>
                            </label>
                            <label 
                                @click="selectUpdateStatus('rescheduled')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'rescheduled' ? 'bg-purple-50 border-purple-500 text-purple-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="rescheduled" class="sr-only" />
                                <span>🗓️ Reschedule</span>
                            </label>
                            <label 
                                @click="selectUpdateStatus('scheduled')"
                                class="p-2.5 rounded-xl border flex items-center gap-1.5 cursor-pointer transition-all"
                                :class="updateForm.status === 'scheduled' ? 'bg-amber-50 border-amber-500 text-amber-900 font-bold shadow-xs' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            >
                                <input type="radio" v-model="updateForm.status" value="scheduled" class="sr-only" />
                                <span>⏳ Pending / Jadwal</span>
                            </label>
                        </div>
                    </div>

                    <!-- Jika Reschedule: Tanggal Baru -->
                    <div v-if="updateForm.status === 'rescheduled'">
                        <label class="block text-xs font-bold text-purple-700 dark:text-purple-300 mb-1">
                            Pindahkan Tanggal Pelaksanaan ke:
                        </label>
                        <input
                            type="date"
                            v-model="updateForm.scheduled_date"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-purple-50/50 dark:bg-slate-800 border border-purple-300 text-slate-900 dark:text-white"
                        />
                    </div>

                    <!-- 2. Jam Mulai & Jam Selesai -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Jam Mulai:
                                </label>
                                <button 
                                    type="button" 
                                    @click="updateForm.start_time = getCurrentTimeStr()" 
                                    class="text-[10px] text-teal-600 dark:text-teal-400 hover:underline font-semibold flex items-center gap-0.5 cursor-pointer"
                                >
                                    ⏱️ Sekarang
                                </button>
                            </div>
                            <input
                                type="time"
                                v-model="updateForm.start_time"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Jam Selesai:
                                </label>
                                <button 
                                    type="button" 
                                    @click="updateForm.end_time = getCurrentTimeStr()" 
                                    class="text-[10px] text-teal-600 dark:text-teal-400 hover:underline font-semibold flex items-center gap-0.5 cursor-pointer"
                                >
                                    ⏱️ Sekarang
                                </button>
                            </div>
                            <input
                                type="time"
                                v-model="updateForm.end_time"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- 3. Redaman Optik (OPM dBm) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pengukuran Redaman Optik (OPM dBm) (Opsional):
                        </label>
                        <input
                            type="text"
                            v-model="updateForm.optical_power_after"
                            placeholder="Contoh: -18.4 dBm (Normal)"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono"
                        />
                    </div>

                    <!-- 4. Tindakan & Hasil Pengerjaan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Tindakan Lapangan / Hasil Pekerjaan:
                        </label>
                        <textarea
                            v-model="updateForm.action_taken"
                            rows="3"
                            placeholder="Kabel kendur telah dirapikan dan diikat slack di tiang 14. ODP telah dibersihkan dan konektor dipastikan kencang."
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- 5. Upload Foto Sesudah (Auto Kompres Cerdas) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Upload Foto Dokumentasi Lapangan (Opsional):
                            </label>
                            <span v-if="photoAfterCompressionInfo?.isCompressed" class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-700">
                                ⚡ Auto-Kompres Aktif (Hemat {{ photoAfterCompressionInfo.ratio }})
                            </span>
                        </div>

                        <!-- Indikator Sedang Mengompres -->
                        <div v-if="isCompressingPhotoAfter" class="p-3 rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 text-xs text-teal-800 dark:text-teal-300 flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="font-medium">Mengompres ukuran dan resolusi foto secara otomatis...</span>
                        </div>

                        <!-- Input File (Jika Belum Ada File Terpilih) -->
                        <div v-else-if="!updatePhotoAfterFile">
                            <input
                                type="file"
                                accept="image/*"
                                @change="handlePhotoAfterUpload"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-slate-800 dark:file:text-teal-400 cursor-pointer"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Ukuran foto kamera HP (mis. 5-15MB) akan otomatis dikompres ke ~200-350KB agar upload instan tanpa menguras kuota.</p>
                        </div>

                        <!-- Preview Thumbnail & Info Kompresi -->
                        <div v-else class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img 
                                    v-if="photoAfterCompressionInfo?.previewUrl" 
                                    :src="photoAfterCompressionInfo.previewUrl" 
                                    class="w-12 h-12 object-cover rounded-xl border border-slate-300 dark:border-slate-600 shrink-0 shadow-2xs" 
                                />
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ updatePhotoAfterFile.name }}</p>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
                                        <span class="line-through text-slate-400" v-if="photoAfterCompressionInfo?.isCompressed">{{ photoAfterCompressionInfo.originalFormatted }}</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ photoAfterCompressionInfo?.compressedFormatted || formatBytes(updatePhotoAfterFile.size) }}</span>
                                        <span v-if="photoAfterCompressionInfo?.isCompressed" class="px-1.5 py-0.2 rounded font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                                            -{{ photoAfterCompressionInfo.ratio }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="removePhotoAfter"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                                title="Hapus / Ganti Foto"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showUpdateModal = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2 text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 disabled:opacity-50 rounded-xl transition-all flex items-center gap-2 shadow-md shadow-teal-600/20 cursor-pointer"
                        >
                            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Simpan Update 🚀</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- MODAL: BUAT JADWAL PEMELIHARAAN BARU                      -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div 
            v-if="showCreateModal" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
            @click.self="showCreateModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 my-8 animate-in fade-in zoom-in-95 duration-200">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold text-lg">
                            🔧
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Jadwal Pemeliharaan & Jalur Baru</h3>
                            <p class="text-[11px] text-slate-400">Mandiri tanpa harus lewat tiket PGS/PGK/PGJ</p>
                        </div>
                    </div>
                    <button 
                        @click="showCreateModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitCreateMaintenance" class="space-y-3.5">
                    
                    <!-- Kategori -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Kategori Pemeliharaan:
                        </label>
                        <select
                            v-model="createForm.category"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="perapihan_jalur">🧹 Perapihan Jalur & Kabel Kendur</option>
                            <option value="pengecekan_odp">📦 Pengecekan & Kebersihan Box ODP</option>
                            <option value="pengecekan_odc">🗄️ Pengecekan Kabinet & Splitter ODC</option>
                            <option value="pengecekan_jalur_utama">⚡ Pemeliharaan Jalur Utama (Backbone FO)</option>
                            <option value="pemeliharaan_rutin">🔧 Pemeliharaan Berkala & Rutin</option>
                        </select>
                    </div>

                    <!-- Judul Kegiatan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Judul / Nama Pekerjaan: *
                        </label>
                        <input
                            type="text"
                            v-model="createForm.title"
                            required
                            placeholder="Contoh: Perapihan Kabel Kendur Jl. Raya Sukabumi"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                        />
                    </div>

                    <!-- Wilayah -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Wilayah / Area:
                        </label>
                        <select
                            v-model="createForm.area_id"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Pilih Wilayah</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Target Aset / Lokasi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Target Aset / Lokasi:
                        </label>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <button
                                type="button"
                                @click="createForm.asset_type = 'manual'"
                                class="p-2 text-xs font-bold rounded-xl border text-center transition-all cursor-pointer"
                                :class="createForm.asset_type === 'manual' ? 'bg-teal-50 border-teal-500 text-teal-800' : 'border-slate-200 text-slate-600'"
                            >
                                📍 Rute Jalan
                            </button>
                            <button
                                type="button"
                                @click="createForm.asset_type = 'odp'"
                                class="p-2 text-xs font-bold rounded-xl border text-center transition-all cursor-pointer"
                                :class="createForm.asset_type === 'odp' ? 'bg-teal-50 border-teal-500 text-teal-800' : 'border-slate-200 text-slate-600'"
                            >
                                📦 Box ODP
                            </button>
                            <button
                                type="button"
                                @click="createForm.asset_type = 'odc'"
                                class="p-2 text-xs font-bold rounded-xl border text-center transition-all cursor-pointer"
                                :class="createForm.asset_type === 'odc' ? 'bg-teal-50 border-teal-500 text-teal-800' : 'border-slate-200 text-slate-600'"
                            >
                                🗄️ Kabinet ODC
                            </button>
                        </div>

                        <!-- Manual Location Name -->
                        <div v-if="createForm.asset_type === 'manual'">
                            <input
                                type="text"
                                v-model="createForm.location_name"
                                placeholder="Ketik nama jalan / patokan rute (contoh: Depan Ruko Blok C)"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                            />
                        </div>

                        <!-- Select ODP -->
                        <div v-if="createForm.asset_type === 'odp'">
                            <select
                                v-model="createForm.odp_id"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="">Pilih Box ODP Target</option>
                                <option v-for="odp in filteredOdps" :key="odp.id" :value="odp.id">
                                    {{ odp.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Select ODC -->
                        <div v-if="createForm.asset_type === 'odc'">
                            <select
                                v-model="createForm.odc_id"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="">Pilih Kabinet ODC Target</option>
                                <option v-for="odc in filteredOdcs" :key="odc.id" :value="odc.id">
                                    {{ odc.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Tanggal, Jam & Prioritas -->
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal: *</label>
                            <input
                                type="date"
                                v-model="createForm.scheduled_date"
                                required
                                class="w-full px-2.5 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 text-slate-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Jam Mulai:</label>
                            <input
                                type="time"
                                v-model="createForm.scheduled_time"
                                class="w-full px-2.5 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 text-slate-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Prioritas:</label>
                            <select
                                v-model="createForm.priority"
                                class="w-full px-2.5 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 text-slate-900 dark:text-white"
                            >
                                <option value="normal">Normal</option>
                                <option value="urgent">🔥 Mendesak</option>
                            </select>
                        </div>
                    </div>

                    <!-- Teknisi Bertugas (Multi Checkbox) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Pilih Teknisi / Tim Maintenance Bertugas:
                            </label>
                            <button
                                v-if="createForm.area_id"
                                type="button"
                                @click="showAllAreaTechs = !showAllAreaTechs"
                                class="text-[11px] font-semibold text-teal-600 dark:text-teal-400 hover:underline cursor-pointer"
                            >
                                {{ showAllAreaTechs ? 'Filter Sesuai Area' : 'Tampilkan Semua Area' }}
                            </button>
                        </div>
                        <div class="max-h-32 overflow-y-auto p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 space-y-1.5 text-xs">
                            <template v-if="filteredTechnicians.length">
                                <label
                                    v-for="t in filteredTechnicians"
                                    :key="t.id"
                                    class="flex items-center gap-2 cursor-pointer hover:bg-slate-200/50 dark:hover:bg-slate-700/50 p-1 rounded-lg"
                                >
                                    <input
                                        type="checkbox"
                                        :value="t.id"
                                        v-model="createForm.technician_ids"
                                        class="rounded text-teal-600 focus:ring-teal-500"
                                    />
                                    <span class="text-slate-800 dark:text-slate-200 font-medium">{{ t.name }}</span>
                                </label>
                            </template>
                            <p v-else class="text-xs text-slate-400 italic py-1 text-center">Belum ada teknisi terdaftar pada area ini.</p>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Instruksi / Catatan Tambahan (Opsional):
                        </label>
                        <textarea
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Contoh: Kabel kendur melintang di atas jalan raya, perlu dinaikkan dan pasang span clamp baru."
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500"
                        ></textarea>
                    </div>

                    <!-- Upload Foto Sebelum (Auto Kompres Cerdas) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Upload Foto Kondisi Sebelum / Temuan (Opsional):
                            </label>
                            <span v-if="photoBeforeCompressionInfo?.isCompressed" class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-700">
                                ⚡ Auto-Kompres Aktif (Hemat {{ photoBeforeCompressionInfo.ratio }})
                            </span>
                        </div>

                        <!-- Indikator Sedang Mengompres -->
                        <div v-if="isCompressingPhotoBefore" class="p-3 rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 text-xs text-teal-800 dark:text-teal-300 flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="font-medium">Mengompres ukuran dan resolusi foto secara otomatis...</span>
                        </div>

                        <!-- Input File (Jika Belum Ada File Terpilih) -->
                        <div v-else-if="!createPhotoBeforeFile">
                            <input
                                type="file"
                                accept="image/*"
                                @change="handlePhotoBeforeUpload"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-slate-800 dark:file:text-teal-400 cursor-pointer"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Ukuran foto kamera HP (mis. 5-15MB) akan otomatis dikompres ke ~200-350KB agar upload instan tanpa menguras kuota.</p>
                        </div>

                        <!-- Preview Thumbnail & Info Kompresi -->
                        <div v-else class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img 
                                    v-if="photoBeforeCompressionInfo?.previewUrl" 
                                    :src="photoBeforeCompressionInfo.previewUrl" 
                                    class="w-12 h-12 object-cover rounded-xl border border-slate-300 dark:border-slate-600 shrink-0 shadow-2xs" 
                                />
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ createPhotoBeforeFile.name }}</p>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
                                        <span class="line-through text-slate-400" v-if="photoBeforeCompressionInfo?.isCompressed">{{ photoBeforeCompressionInfo.originalFormatted }}</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ photoBeforeCompressionInfo?.compressedFormatted || formatBytes(createPhotoBeforeFile.size) }}</span>
                                        <span v-if="photoBeforeCompressionInfo?.isCompressed" class="px-1.5 py-0.2 rounded font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                                            -{{ photoBeforeCompressionInfo.ratio }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="removePhotoBefore"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                                title="Hapus / Ganti Foto"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2 text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 disabled:opacity-50 rounded-xl transition-all flex items-center gap-2 shadow-md shadow-teal-600/20 cursor-pointer"
                        >
                            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Simpan Jadwal 🚀</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </AppLayout>
</template>

