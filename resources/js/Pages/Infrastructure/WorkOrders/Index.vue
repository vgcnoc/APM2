<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    workOrders: Object,
    kpi: Object,
    areas: Array,
    technicians: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const statusFilter = ref(props.filters.status || '');
const areaFilter = ref(props.filters.area_id || '');
const techFilter = ref(props.filters.technician_id || '');

// Modal & Form Edit Work Order
const showEditModal = ref(false);
const activeWo = ref(null);
const editForm = useForm({
    target_name: '',
    device_type: 'Normal',
    area_id: '',
    capacity: 8,
    core_capacity: null,
    splitter_ratio: '',
    location: '',
    planned_latitude: '',
    planned_longitude: '',
    technician_id: '',
    technician_ids: [],
    scheduled_date: '',
    start_point: '',
    end_point: '',
    cable_pull: '',
    notes: '',
});

const openEditModal = (wo) => {
    activeWo.value = wo;
    editForm.target_name = wo.target_name || '';
    editForm.device_type = wo.device_type || 'Normal';
    editForm.area_id = wo.area_id || '';
    editForm.capacity = wo.capacity || (wo.type === 'odc_deployment' ? 48 : 8);
    editForm.core_capacity = wo.core_capacity || null;
    editForm.splitter_ratio = wo.splitter_ratio || (wo.type === 'odc_deployment' ? '1:4' : '1:8');
    editForm.location = wo.location || '';
    editForm.planned_latitude = wo.planned_latitude || '';
    editForm.planned_longitude = wo.planned_longitude || '';
    editForm.technician_id = wo.technician_id || (wo.technicians && wo.technicians.length > 0 ? wo.technicians[0].id : '');
    editForm.technician_ids = wo.technicians && wo.technicians.length > 0
        ? wo.technicians.map(t => t.id)
        : (wo.technician_id ? [wo.technician_id] : []);
    editForm.scheduled_date = wo.scheduled_date ? wo.scheduled_date.substring(0, 10) : '';
    editForm.start_point = wo.start_point || '';
    editForm.end_point = wo.end_point || '';
    editForm.cable_pull = wo.cable_pull || '';
    editForm.notes = wo.notes || '';
    showEditModal.value = true;
};

const getCurrentLocation = () => {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser Anda.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            editForm.planned_latitude = position.coords.latitude.toFixed(7);
            editForm.planned_longitude = position.coords.longitude.toFixed(7);
        },
        (error) => {
            alert('Gagal mendeteksi lokasi GPS: ' + error.message);
        }
    );
};

const submitEditForm = () => {
    if (!activeWo.value) return;
    editForm.put(route('infrastructure.work-orders.update', activeWo.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const applyFilters = debounce(() => {
    router.get(
        route('infrastructure.work-orders.index'),
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            status: statusFilter.value || undefined,
            area_id: areaFilter.value || undefined,
            technician_id: techFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}, 300);

watch([search, typeFilter, statusFilter, areaFilter, techFilter], () => {
    applyFilters();
});

const getStatusBadge = (status) => {
    switch (status) {
        case 'pending_material':
            return { label: 'Menunggu Material', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'material_ready':
            return { label: 'Material Siap', class: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'in_progress':
            return { label: 'Sedang Dikerjakan', class: 'bg-indigo-50 text-indigo-700 border-indigo-200 animate-pulse' };
        case 'under_review':
            return { label: 'Review QC NOC', class: 'bg-purple-50 text-purple-700 border-purple-200 font-bold' };
        case 'completed':
            return { label: 'Selesai & Aktif di GIS', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'rejected':
            return { label: 'Revisi / Ditolak', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        default:
            return { label: status, class: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const deleteWo = (wo) => {
    if (confirm(`Apakah Anda yakin ingin membatalkan & menghapus Work Order Rollout ${wo.wo_number} (${wo.target_name})? Seluruh material yang telah dialokasikan/disiapkan akan otomatis dikembalikan ke stok gudang.`)) {
        router.delete(route('infrastructure.work-orders.destroy', wo.id));
    }
};
</script>

<template>
    <AppLayout title="Rollout & Penugasan Pembangunan ODC / ODP">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-sans">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl text-xl">⚡</span>
                        <span>Rollout &amp; Pembangunan ODC &amp; ODP</span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Siklus pengerjaan infrastruktur FTTH: Penugasan cerdas, alokasi material gudang (BOM), QC redaman optik lapangan, hingga komisioning otomatis ke GIS.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-can="'infrastructure_work_orders_create'"
                        :href="route('infrastructure.work-orders.create')"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2"
                    >
                        <span>➕ Buat Penugasan Baru</span>
                    </Link>
                </div>
            </div>

            <!-- Alur Rollout 3 Tahap Cerdas -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-5 text-white shadow-lg mb-8 border border-slate-800">
                <div class="flex items-center justify-between mb-3 border-b border-white/10 pb-2">
                    <span class="text-xs font-black uppercase tracking-wider text-indigo-300 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span>Alur Rollout FTTH Cerdas: 3 Tahap Menuju Auto-Commissioning</span>
                    </span>
                    <span class="text-[11px] text-slate-400 hidden sm:inline">
                        Otomatis terbit di master data ODC/ODP &amp; peta GIS
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-indigo-500/30 text-indigo-300 border border-indigo-400/30 font-black text-xs flex items-center justify-center shrink-0">1</div>
                        <div>
                            <h4 class="font-bold text-xs text-white">1. Persiapan</h4>
                            <p class="text-[11px] text-slate-300 mt-0.5">Spesifikasi perangkat, titik awal-akhir rute &amp; Auto-BOM kalkulator stok gudang.</p>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-amber-500/30 text-amber-300 border border-amber-400/30 font-black text-xs flex items-center justify-center shrink-0">2</div>
                        <div>
                            <h4 class="font-bold text-xs text-white">2. Penugasan</h4>
                            <p class="text-[11px] text-slate-300 mt-0.5">Penetapan teknisi &amp; jadwal, pengeluaran material gudang &amp; cetak surat jalan.</p>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-emerald-500/30 text-emerald-300 border border-emerald-400/30 font-black text-xs flex items-center justify-center shrink-0">3</div>
                        <div>
                            <h4 class="font-bold text-xs text-emerald-200">3. Pengerjaan &amp; Auto-Aktif</h4>
                            <p class="text-[11px] text-emerald-100/80 mt-0.5">Tarik kabel, QC redaman optik &rarr; <b>Langsung otomatis masuk data ODC/ODP &amp; GIS!</b></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div v-can="'infra_wo_card_stats'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
                <!-- Total -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Total WO</span>
                    <span class="text-xl font-extrabold text-slate-900 mt-0.5 block">{{ kpi.total }}</span>
                </div>

                <!-- Menunggu Material -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
                    <span class="text-[10px] font-semibold text-amber-600 uppercase tracking-wider block">Tunggu Material</span>
                    <span class="text-xl font-extrabold text-amber-600 mt-0.5 block">{{ kpi.pending_material }}</span>
                </div>

                <!-- Material Siap -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
                    <span class="text-[10px] font-semibold text-blue-600 uppercase tracking-wider block">Material Siap</span>
                    <span class="text-xl font-extrabold text-blue-600 mt-0.5 block">{{ kpi.material_ready }}</span>
                </div>

                <!-- Sedang Dikerjakan -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
                    <span class="text-[10px] font-semibold text-indigo-600 uppercase tracking-wider block">Dikerjakan</span>
                    <span class="text-xl font-extrabold text-indigo-600 mt-0.5 block">{{ kpi.in_progress }}</span>
                </div>

                <!-- Review NOC -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
                    <span class="text-[10px] font-semibold text-purple-600 uppercase tracking-wider block">Review QC NOC</span>
                    <span class="text-xl font-extrabold text-purple-600 mt-0.5 block">{{ kpi.under_review }}</span>
                </div>

                <!-- Selesai -->
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
                    <span class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wider block">Aktif di GIS</span>
                    <span class="text-xl font-extrabold text-emerald-600 mt-0.5 block">{{ kpi.completed }}</span>
                </div>
            </div>

            <!-- Filter & Table Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <!-- Filter Bar -->
                <div v-can="'infra_wo_filter'" class="p-4 sm:p-5 border-b border-slate-100 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-slate-50/40">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">🔍</span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nomor WO, target perangkat, atau lokasi tiang..."
                            class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-2xs"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Tipe -->
                        <select
                            v-model="typeFilter"
                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 shadow-2xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="odc_deployment">Pembangunan ODC</option>
                            <option value="odp_deployment">Pembangunan ODP</option>
                        </select>

                        <!-- Status -->
                        <select
                            v-model="statusFilter"
                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 shadow-2xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending_material">Tunggu Material</option>
                            <option value="material_ready">Material Siap</option>
                            <option value="in_progress">Sedang Dikerjakan</option>
                            <option value="under_review">Review QC NOC</option>
                            <option value="completed">Selesai (Aktif)</option>
                            <option value="rejected">Revisi</option>
                        </select>

                        <!-- Area -->
                        <select
                            v-model="areaFilter"
                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 shadow-2xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Semua Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>

                        <!-- Teknisi -->
                        <select
                            v-model="techFilter"
                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 shadow-2xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Semua Teknisi</option>
                            <option v-for="t in technicians" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-left">
                            <tr>
                                <th class="px-5 py-3.5">Nomor WO &amp; Tanggal</th>
                                <th class="px-4 py-3.5">Tipe &amp; Target</th>
                                <th class="px-4 py-3.5">Induk Jaringan</th>
                                <th class="px-4 py-3.5">Area &amp; Lokasi</th>
                                <th class="px-4 py-3.5">Teknisi Pelaksana</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="wo in workOrders.data"
                                :key="wo.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <!-- No WO -->
                                <td class="px-5 py-3.5">
                                    <span class="font-mono font-bold text-indigo-700 block text-xs">
                                        {{ wo.wo_number }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 mt-0.5 block">
                                        {{ formatDate(wo.scheduled_date) }}
                                    </span>
                                </td>

                                <!-- Tipe & Target -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase border"
                                            :class="wo.type === 'odc_deployment' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                        >
                                            {{ wo.type === 'odc_deployment' ? 'ODC' : 'ODP' }}
                                        </span>
                                        <span v-if="wo.device_type" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ wo.device_type }}
                                        </span>
                                        <span class="font-bold text-slate-900">{{ wo.target_name }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500 mt-0.5 block">
                                        Kapasitas: {{ wo.capacity }} Port <template v-if="wo.core_capacity">&bull; {{ wo.core_capacity }} Core</template> ({{ wo.splitter_ratio || 'Normal' }})
                                    </span>
                                </td>

                                <!-- Induk Jaringan -->
                                <td class="px-4 py-3.5">
                                    <div v-if="wo.type === 'odc_deployment'">
                                        <span class="text-slate-700 font-semibold">OLT: {{ wo.olt?.name || '-' }}</span>
                                        <span class="text-[11px] text-slate-400 block">Port PON: {{ wo.pon_port || '-' }}</span>
                                    </div>
                                    <div v-else>
                                        <span class="text-slate-700 font-semibold">ODC: {{ wo.parent_odc?.name || '-' }}</span>
                                        <span class="text-[11px] text-slate-400 block">Port Feeder: {{ wo.odc_splitter_port || '-' }}</span>
                                    </div>
                                </td>

                                <!-- Area & Lokasi -->
                                <td class="px-4 py-3.5">
                                    <span class="font-semibold text-slate-800">{{ wo.area?.name || '-' }}</span>
                                    <p class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">
                                        {{ wo.location || 'Lokasi tiang ditentukan di lapangan' }}
                                    </p>
                                </td>

                                <!-- Teknisi -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-[10px]" title="Lead Splicer">
                                            {{ wo.technician?.name ? wo.technician.name.charAt(0).toUpperCase() : 'T' }}
                                        </span>
                                        <div>
                                            <span class="font-semibold text-slate-800 block">{{ wo.technician?.name || 'Belum Ditugaskan' }}</span>
                                            <span v-if="wo.technicians && wo.technicians.length > 1" class="text-[10px] text-indigo-600 font-bold block">
                                                +{{ wo.technicians.length - 1 }} Anggota Tim
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border shadow-2xs"
                                        :class="getStatusBadge(wo.status).class"
                                    >
                                        {{ getStatusBadge(wo.status).label }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Shortcut Cerdas: Langsung ke Master Data jika sudah selesai -->
                                        <Link
                                            v-if="wo.status === 'completed' && wo.type === 'odc_deployment'"
                                            :href="route('odcs.index', { search: wo.target_name })"
                                            class="px-2.5 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-lg transition-colors border border-purple-200 text-[11px] flex items-center gap-1"
                                            title="Buka langsung di Master Data ODC"
                                        >
                                            <span>🏢 ODC</span>
                                            <span>&rarr;</span>
                                        </Link>

                                        <Link
                                            v-else-if="wo.status === 'completed' && wo.type === 'odp_deployment'"
                                            :href="route('odps.index', { search: wo.target_name })"
                                            class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg transition-colors border border-emerald-200 text-[11px] flex items-center gap-1"
                                            title="Buka langsung di Master Data ODP"
                                        >
                                            <span>📦 ODP</span>
                                            <span>&rarr;</span>
                                        </Link>

                                        <button
                                            type="button"
                                            @click="openEditModal(wo)"
                                            class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold rounded-lg transition-colors border border-amber-200 text-xs flex items-center gap-1 cursor-pointer"
                                            title="Edit Penugasan Pembangunan"
                                        >
                                            <span>✏️</span>
                                            <span>Edit</span>
                                        </button>

                                        <Link
                                            :href="route('infrastructure.work-orders.show', wo.id)"
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 font-bold rounded-lg transition-colors border border-slate-200/60"
                                        >
                                            Detail &rarr;
                                        </Link>
                                        <button
                                            v-can="'infrastructure_work_orders_delete'"
                                            type="button"
                                            @click="deleteWo(wo)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors border border-transparent hover:border-rose-200 cursor-pointer"
                                            title="Hapus Work Order"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!workOrders.data || workOrders.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <span class="text-4xl block mb-2">📡</span>
                                    <p class="font-medium text-slate-700">Belum Ada Penugasan Pembangunan</p>
                                    <p class="text-xs text-slate-400 mt-1">Klik tombol "+ Buat Penugasan Baru" di atas untuk menerbitkan Work Order ODC atau ODP.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="workOrders.links && workOrders.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ workOrders.from || 0 }} - {{ workOrders.to || 0 }} dari {{ workOrders.total }} penugasan</span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in workOrders.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg border text-xs"
                            :class="link.active ? 'bg-indigo-600 text-white font-bold border-indigo-600' : 'bg-white hover:bg-slate-50 text-slate-600 border-slate-200'"
                        />
                    </div>
                </div>
            </div>

            <!-- MODAL: EDIT WORK ORDER ROLLOUT -->
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs" @click="showEditModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl overflow-y-auto max-h-[92vh] border border-slate-200 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Header Modal -->
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-lg border border-amber-200 dark:border-amber-800">
                                ✏️
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                    Edit Penugasan Rollout
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black tracking-wide border uppercase" :class="activeWo?.type === 'odc_deployment' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
                                        {{ activeWo?.type === 'odc_deployment' ? 'ODC Deployment' : 'ODP Deployment' }}
                                    </span>
                                </h3>
                                <p class="text-xs text-slate-400 font-mono">{{ activeWo?.wo_number }}</p>
                            </div>
                        </div>
                        <button @click="showEditModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <!-- Form Edit -->
                    <form @submit.prevent="submitEditForm" class="p-6 space-y-4 text-xs">
                        
                        <!-- Target Name & Device Type -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                    Nama Target Perangkat <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="editForm.target_name"
                                    required
                                    placeholder="Cth: ODP V-UBG 02 / ODC UBRUG"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                    Tipe / Varian Perangkat
                                </label>
                                <select
                                    v-model="editForm.device_type"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                >
                                    <option value="Normal">Normal</option>
                                    <option value="Solid / Mini">Solid / Mini</option>
                                    <option value="Splitter Box">Splitter Box</option>
                                    <option value="ODP Pole">ODP Pole / Tiang</option>
                                    <option value="Closure">Closure</option>
                                </select>
                            </div>
                        </div>

                        <!-- Area & Tanggal Jadwal -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                    Wilayah / Area
                                </label>
                                <select
                                    v-model="editForm.area_id"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                >
                                    <option value="">Pilih Area...</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">
                                        {{ area.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                    Tanggal Pelaksanaan / Jadwal
                                </label>
                                <input
                                    type="date"
                                    v-model="editForm.scheduled_date"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Kapasitas Port & Splitter Ratio -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                    Kapasitas Port / Core
                                </label>
                                <input
                                    type="number"
                                    v-model="editForm.capacity"
                                    min="1"
                                    placeholder="8, 16, 24, 48, dll"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                    Rasio Splitter
                                </label>
                                <input
                                    type="text"
                                    v-model="editForm.splitter_ratio"
                                    placeholder="Cth: 1:8, 1:16, 1:4"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Lokasi Fisik / Tiang / Alamat -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Lokasi Fisik / Tiang / Titik Pemasangan
                            </label>
                            <input
                                type="text"
                                v-model="editForm.location"
                                placeholder="Cth: TIANG PLN DEPAN GAPURA RW 02"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <!-- Koordinat GPS -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Latitude</label>
                                    <button type="button" @click="getCurrentLocation" class="text-[10px] text-amber-600 hover:text-amber-700 font-bold cursor-pointer">
                                        📍 Deteksi GPS
                                    </button>
                                </div>
                                <input
                                    type="text"
                                    v-model="editForm.planned_latitude"
                                    placeholder="-6.912345"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Longitude</label>
                                <input
                                    type="text"
                                    v-model="editForm.planned_longitude"
                                    placeholder="107.612345"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Personil Teknisi -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Lead Splicer / Penanggung Jawab Teknisi
                            </label>
                            <select
                                v-model="editForm.technician_id"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="">-- Pilih Lead Teknisi --</option>
                                <option v-for="t in technicians" :key="t.id" :value="t.id">
                                    {{ t.name }} ({{ t.role || 'Teknisi' }})
                                </option>
                            </select>
                        </div>

                        <!-- Anggota Tim Pelaksana -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Anggota Tim Teknisi / Serbu
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
                                <label v-for="tech in technicians" :key="'tech-' + tech.id" class="flex items-center gap-1.5 p-1 rounded-lg hover:bg-white dark:hover:bg-slate-800 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :value="tech.id"
                                        v-model="editForm.technician_ids"
                                        class="rounded text-amber-600 focus:ring-amber-500 h-3.5 w-3.5"
                                    />
                                    <span class="text-[11px] text-slate-800 dark:text-slate-200 font-medium truncate">{{ tech.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Rute Tarikan Kabel -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Titik Awal</label>
                                <input
                                    type="text"
                                    v-model="editForm.start_point"
                                    placeholder="ODC / Tiang Asal"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Titik Akhir</label>
                                <input
                                    type="text"
                                    v-model="editForm.end_point"
                                    placeholder="Tiang Target ODP"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Est. Panjang Kabel (m)</label>
                                <input
                                    type="text"
                                    v-model="editForm.cable_pull"
                                    placeholder="150 m"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Catatan / Instruksi Lapangan
                            </label>
                            <textarea
                                v-model="editForm.notes"
                                rows="2"
                                placeholder="Instruksi rute penarikan, jalur kabel, atau catatan khusus tim lapangan..."
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            ></textarea>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 rounded-xl transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <span v-if="editForm.processing">⏳ Menyimpan...</span>
                                <span v-else>💾 Simpan Perubahan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
