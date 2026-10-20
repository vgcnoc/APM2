<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    workOrder: Object,
});

const activeTab = ref('details'); // 'details' | 'materials' | 'optical' | 'photos'
const showReportModal = ref(false);
const showRejectModal = ref(false);
const rejectReason = ref('');

// Form Pelaporan Lapangan Teknisi
const reportForm = useForm({
    actual_latitude: props.workOrder.actual_latitude || props.workOrder.planned_latitude || '',
    actual_longitude: props.workOrder.actual_longitude || props.workOrder.planned_longitude || '',
    start_point: props.workOrder.start_point || '',
    end_point: props.workOrder.end_point || '',
    cable_meter_start: props.workOrder.cable_meter_start || 0,
    cable_meter_end: props.workOrder.cable_meter_end || 0,
    cable_length_used: props.workOrder.cable_length_used || 0,
    photo_pole: null,
    photo_splicing: null,
    photo_opm: null,
    notes: props.workOrder.notes || '',
    optical_checks: props.workOrder.optical_checks && props.workOrder.optical_checks.length > 0
        ? props.workOrder.optical_checks.map(oc => ({ port_number: oc.port_number, rx_power_dbm: oc.rx_power_dbm, notes: oc.notes }))
        : Array.from({ length: props.workOrder.capacity || 8 }, (_, i) => ({ port_number: i + 1, rx_power_dbm: -19.5, notes: '' })),
    reconciliations: props.workOrder.materials.map(m => ({
        id: m.id,
        name: m.material?.name,
        requested_qty: m.requested_qty,
        used_qty: m.used_qty ?? m.requested_qty,
        unit: m.unit,
    })),
});

const isReportLocating = ref(false);
const getReportLocation = () => {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser Anda.');
        return;
    }
    isReportLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            isReportLocating.value = false;
            reportForm.actual_latitude = position.coords.latitude.toFixed(7);
            reportForm.actual_longitude = position.coords.longitude.toFixed(7);
        },
        (error) => {
            isReportLocating.value = false;
            alert('Gagal mendeteksi lokasi: ' + error.message);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'pending_material':
            return { label: 'Menunggu Material', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'material_ready':
            return { label: 'Material Siap / Diambil', class: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'in_progress':
            return { label: 'Sedang Dikerjakan di Lapangan', class: 'bg-indigo-50 text-indigo-700 border-indigo-200 animate-pulse' };
        case 'under_review':
            return { label: 'Menunggu Verifikasi NOC', class: 'bg-purple-50 text-purple-700 border-purple-200 font-bold' };
        case 'completed':
            return { label: 'Selesai & Aktif di GIS', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'rejected':
            return { label: 'Revisi / Ditolak', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        default:
            return { label: status, class: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
};

const getRxBadge = (status) => {
    if (status === 'pass') return { label: 'PASS (Prima)', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    if (status === 'warning') return { label: 'WARNING (Waspada)', class: 'bg-amber-50 text-amber-700 border-amber-200' };
    return { label: 'FAIL (Redaman Tinggi)', class: 'bg-rose-50 text-rose-700 border-rose-200' };
};

const approveMaterialDispatch = () => {
    if (confirm('Konfirmasi pengeluaran material dari gudang? Stok material akan otomatis dialokasikan.')) {
        router.post(route('infrastructure.work-orders.approve-material', props.workOrder.id));
    }
};

const startWork = () => {
    router.post(route('infrastructure.work-orders.start-work', props.workOrder.id));
};

const submitFieldReport = () => {
    reportForm.post(route('infrastructure.work-orders.submit-report', props.workOrder.id), {
        onSuccess: () => {
            showReportModal.value = false;
        }
    });
};

const approveCommissioning = () => {
    if (confirm('Konfirmasi persetujuan & komisioning? Perangkat ODC/ODP akan otomatis aktif di master database dan peta GIS!')) {
        router.post(route('infrastructure.work-orders.approve', props.workOrder.id));
    }
};

const submitRejection = () => {
    if (!rejectReason.value) return;
    router.post(route('infrastructure.work-orders.reject', props.workOrder.id), {
        reason: rejectReason.value,
    }, {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectReason.value = '';
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout title="Detail Work Order Pembangunan ODC / ODP">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-sans">
            <!-- Header Back Navigation -->
            <div class="mb-4">
                <Link
                    :href="route('infrastructure.work-orders.index')"
                    class="text-xs font-bold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1"
                >
                    <span>&larr;</span>
                    <span>Kembali ke Daftar Penugasan</span>
                </Link>
            </div>

            <!-- Flash Message Alerts -->
            <div v-if="$page.props.flash?.success" class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <span class="text-xl">✅</span>
                    <span class="text-sm font-bold">{{ $page.props.flash.success }}</span>
                </div>
                <button type="button" @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-800 text-sm font-black">&times;</button>
            </div>

            <div v-if="$page.props.flash?.error" class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚠️</span>
                    <span class="text-sm font-bold">{{ $page.props.flash.error }}</span>
                </div>
                <button type="button" @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-800 text-sm font-black">&times;</button>
            </div>

            <!-- Banner Header -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl mb-8 relative overflow-hidden">
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-full bg-white/10 text-indigo-200 border border-white/15">
                                {{ workOrder.wo_number }}
                            </span>
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-bold border"
                                :class="workOrder.type === 'odc_deployment' ? 'bg-purple-500/20 text-purple-200 border-purple-400/30' : 'bg-emerald-500/20 text-emerald-200 border-emerald-400/30'"
                            >
                                {{ workOrder.type === 'odc_deployment' ? 'Pembangunan ODC' : 'Pembangunan ODP' }}
                            </span>
                            <span
                                v-if="workOrder.device_type"
                                class="px-2.5 py-1 rounded-full text-xs font-bold border bg-blue-500/20 text-blue-200 border-blue-400/30"
                            >
                                Tipe {{ workOrder.device_type }}
                            </span>
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-bold border"
                                :class="getStatusBadge(workOrder.status).class"
                            >
                                {{ getStatusBadge(workOrder.status).label }}
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white drop-shadow-sm">
                            {{ workOrder.target_name }}
                        </h1>
                        <div class="text-xs sm:text-sm text-slate-300 mt-1 max-w-3xl flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span>Wilayah: <b>{{ workOrder.area?.name }}</b></span>
                            <span>&bull;</span>
                            <span>Target: <b>{{ formatDate(workOrder.scheduled_date) }}</b></span>
                            <span>&bull;</span>
                            <span>Lead Splicer: <b class="text-amber-300">🌟 {{ workOrder.technician?.name || '-' }}</b></span>
                            <template v-if="workOrder.technicians && workOrder.technicians.length > 1">
                                <span>&bull;</span>
                                <span class="text-slate-400">Tim:</span>
                                <span v-for="t in workOrder.technicians.filter(u => u.id !== workOrder.technician_id)" :key="t.id" class="px-2 py-0.5 rounded-md bg-white/10 text-xs text-white font-medium border border-white/10">
                                    👷 {{ t.name }}
                                </span>
                            </template>
                        </div>

                        <div v-if="workOrder.rejection_reason && workOrder.status === 'rejected'" class="mt-3 p-3 rounded-xl bg-rose-500/20 border border-rose-400/40 text-xs text-rose-200">
                            <b>Catatan Revisi NOC:</b> {{ workOrder.rejection_reason }}
                        </div>
                    </div>

                    <!-- Workflow Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Cetak Surat Jalan -->
                        <a
                            :href="route('infrastructure.work-orders.print-surat-jalan', workOrder.id)"
                            target="_blank"
                            class="px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5"
                        >
                            <span>📄 Cetak Surat Jalan</span>
                        </a>

                        <!-- Action: Gudang Approve Material -->
                        <button
                            v-if="workOrder.status === 'pending_material'"
                            type="button"
                            @click="approveMaterialDispatch"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                        >
                            <span>📦 Konfirmasi Pengeluaran Material</span>
                        </button>

                        <!-- Action: Teknisi Start Work -->
                        <button
                            v-if="workOrder.status === 'material_ready'"
                            type="button"
                            @click="startWork"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                        >
                            <span>🚀 Mulai Pengerjaan Lapangan</span>
                        </button>

                        <!-- Action: Teknisi Submit Report -->
                        <button
                            v-if="workOrder.status === 'in_progress' || workOrder.status === 'rejected'"
                            type="button"
                            @click="showReportModal = true"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                        >
                            <span>📋 Input Laporan &amp; QC Redaman</span>
                        </button>

                        <!-- Action: NOC Review Approve / Reject -->
                        <div v-if="workOrder.status === 'under_review'" class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="approveCommissioning"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                            >
                                <span>✅ Setujui &amp; Komisioning ke GIS</span>
                            </button>

                            <button
                                type="button"
                                @click="showRejectModal = true"
                                class="px-3 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition-all"
                            >
                                <span>Minta Revisi</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation Header -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
                <div class="p-4 border-b border-slate-100 flex items-center gap-2 overflow-x-auto bg-slate-50/50">
                    <button
                        v-for="t in [
                            { id: 'details', label: 'Spesifikasi & Rute', icon: '📡' },
                            { id: 'materials', label: 'Material & Logistik (' + workOrder.materials.length + ')', icon: '📦' },
                            { id: 'optical', label: 'Uji Redaman OPM (' + (workOrder.optical_checks?.length || 0) + ')', icon: '⚡' },
                            { id: 'photos', label: 'Foto Dokumentasi Lapangan', icon: '📸' },
                        ]"
                        :key="t.id"
                        @click="activeTab = t.id"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === t.id ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    >
                        <span>{{ t.icon }}</span>
                        <span>{{ t.label }}</span>
                    </button>
                </div>

                <!-- ════ TAB 1: SPESIFIKASI & RUTE ════ -->
                <div v-if="activeTab === 'details'" class="p-6 sm:p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Relasi Jaringan -->
                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                                <span>🌐 Relasi Jaringan Induk</span>
                            </h4>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between py-1 border-b border-slate-200/60">
                                    <span class="text-slate-500">Tipe Pembangunan:</span>
                                    <span class="font-bold text-slate-900">
                                        {{ workOrder.type === 'odc_deployment' ? 'ODC' : 'ODP' }}
                                        <span class="text-indigo-600 font-semibold">({{ workOrder.device_type || 'Normal' }})</span>
                                    </span>
                                </div>
                                <div v-if="workOrder.type === 'odc_deployment'" class="flex justify-between py-1 border-b border-slate-200/60">
                                    <span class="text-slate-500">OLT Induk:</span>
                                    <span class="font-bold text-slate-900">{{ workOrder.olt?.name || '-' }} (Port PON: {{ workOrder.pon_port || '-' }})</span>
                                </div>
                                <div v-else class="flex justify-between py-1 border-b border-slate-200/60">
                                    <span class="text-slate-500">ODC Induk:</span>
                                    <span class="font-bold text-slate-900">{{ workOrder.parent_odc?.name || '-' }} (Port Feeder: {{ workOrder.odc_splitter_port || '-' }})</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-200/60">
                                    <span class="text-slate-500">Kapasitas Perangkat:</span>
                                    <span class="font-bold text-indigo-600">
                                        {{ workOrder.capacity }} Port
                                        <template v-if="workOrder.core_capacity">
                                            &bull; {{ workOrder.core_capacity }} Core FO
                                        </template>
                                        (Rasio: {{ workOrder.splitter_ratio || 'Normal' }})
                                    </span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-slate-500">Wilayah / Area:</span>
                                    <span class="font-bold text-slate-900">{{ workOrder.area?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Data Tarikan Kabel & Tiang -->
                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                                <span>📍 Lokasi Tiang &amp; Tarikan Kabel</span>
                            </h4>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between py-1 border-b border-slate-200/60">
                                    <span class="text-slate-500">Deskripsi Lokasi:</span>
                                    <span class="font-bold text-slate-900">{{ workOrder.location || '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-1.5 border-b border-slate-200/60">
                                    <span class="text-slate-500">Titik Koordinat Rencana:</span>
                                    <div class="flex items-center gap-1.5 font-mono text-[11px]">
                                        <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200">Lat: {{ workOrder.planned_latitude || '-' }}</span>
                                        <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200">Long: {{ workOrder.planned_longitude || '-' }}</span>
                                        <a
                                            v-if="workOrder.planned_latitude && workOrder.planned_longitude"
                                            :href="`https://www.google.com/maps?q=${workOrder.planned_latitude},${workOrder.planned_longitude}`"
                                            target="_blank"
                                            class="text-indigo-600 hover:text-indigo-800 text-[10px] font-bold underline ml-1"
                                        >
                                            Maps ↗
                                        </a>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-1.5 border-b border-slate-200/60">
                                    <span class="text-slate-500">Titik Koordinat Aktual:</span>
                                    <div class="flex items-center gap-1.5 font-mono text-[11px]">
                                        <span class="bg-indigo-50 text-indigo-700 font-bold px-1.5 py-0.5 rounded border border-indigo-200">Lat: {{ workOrder.actual_latitude || '-' }}</span>
                                        <span class="bg-indigo-50 text-indigo-700 font-bold px-1.5 py-0.5 rounded border border-indigo-200">Long: {{ workOrder.actual_longitude || '-' }}</span>
                                        <a
                                            v-if="workOrder.actual_latitude && workOrder.actual_longitude"
                                            :href="`https://www.google.com/maps?q=${workOrder.actual_latitude},${workOrder.actual_longitude}`"
                                            target="_blank"
                                            class="text-indigo-600 hover:text-indigo-800 text-[10px] font-bold underline ml-1"
                                        >
                                            Maps ↗
                                        </a>
                                    </div>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-200/60">
                                    <span class="text-slate-500">Rute Tarikan:</span>
                                    <span class="font-semibold text-slate-800">{{ workOrder.start_point || '-' }} &rarr; {{ workOrder.end_point || '-' }}</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-slate-500">Panjang Kabel Terpakai:</span>
                                    <span class="font-bold text-emerald-600 text-sm">{{ workOrder.cable_length_used ? workOrder.cable_length_used + ' Meter' : 'Belum diinput' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tim Teknisi & Splicer -->
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span>👷 Tim Teknisi &amp; Splicer Rollout</span>
                            </span>
                            <span class="text-indigo-600 font-semibold">{{ workOrder.technicians?.length || 1 }} Orang Ditugaskan</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <!-- Lead Splicer Card -->
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-amber-500 text-white font-black flex items-center justify-center text-sm shadow-xs">
                                    🌟
                                </div>
                                <div class="truncate">
                                    <p class="text-xs font-bold text-amber-900 truncate">{{ workOrder.technician?.name || '-' }}</p>
                                    <p class="text-[10px] font-semibold text-amber-700">Lead Splicer (Koordinator)</p>
                                </div>
                            </div>

                            <!-- Helper / Team Members -->
                            <template v-if="workOrder.technicians">
                                <div
                                    v-for="tech in workOrder.technicians.filter(u => u.id !== workOrder.technician_id)"
                                    :key="tech.id"
                                    class="p-3 rounded-xl bg-white border border-slate-200 flex items-center gap-3"
                                >
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-black flex items-center justify-center text-xs">
                                        {{ tech.name.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div class="truncate">
                                        <p class="text-xs font-bold text-slate-800 truncate">{{ tech.name }}</p>
                                        <p class="text-[10px] text-slate-400 capitalize">{{ tech.pivot?.role === 'lead_splicer' ? 'Lead Splicer' : 'Anggota Tim / Helper' }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ════ TAB 2: MATERIAL & LOGISTIK ════ -->
                <div v-if="activeTab === 'materials'" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">Nama Material</th>
                                    <th class="px-4 py-3 text-center">Permintaan (BOM)</th>
                                    <th class="px-4 py-3 text-center">Terpakai Riil</th>
                                    <th class="px-4 py-3 text-center">Sisa / Retur</th>
                                    <th class="px-4 py-3">Satuan</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="mat in workOrder.materials" :key="mat.id" class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 font-bold text-slate-900">
                                        <div>{{ mat.material?.name || '-' }}</div>
                                        <div v-if="mat.trx_number" class="text-[10px] font-mono text-amber-700 font-semibold mt-0.5 flex items-center gap-1">
                                            <span>🛒 Order:</span>
                                            <span>{{ mat.trx_number }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-indigo-700">{{ mat.requested_qty }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-slate-800">{{ mat.used_qty ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-emerald-600">{{ mat.returned_qty ?? 0 }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ mat.unit }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase" :class="mat.status === 'taken' ? 'bg-blue-50 text-blue-700 border-blue-200' : (mat.status === 'reconciled' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-700 border-slate-200')">
                                            {{ mat.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ════ TAB 3: UJI REDAMAN OPTIK (OPM) ════ -->
                <div v-if="activeTab === 'optical'" class="p-6">
                    <div v-if="workOrder.optical_checks && workOrder.optical_checks.length > 0" class="space-y-4">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700">💡 Standar Redaman:</span>
                            <span class="text-slate-500">ODP Optimal: -18.0 s/d -23.5 dBm &bull; Batas Toleransi: &le; -25.0 dBm</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="px-4 py-3 text-center w-20">Port #</th>
                                        <th class="px-4 py-3">Panjang Gelombang</th>
                                        <th class="px-4 py-3 text-right">Redaman (Rx Power)</th>
                                        <th class="px-4 py-3 text-center">Status QC</th>
                                        <th class="px-4 py-3">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="chk in workOrder.optical_checks" :key="chk.id" class="hover:bg-slate-50/60">
                                        <td class="px-4 py-3 text-center font-bold text-slate-800">Port {{ chk.port_number }}</td>
                                        <td class="px-4 py-3 font-mono text-slate-600">{{ chk.wavelength }}</td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-sm" :class="chk.rx_power_dbm < -25 ? 'text-rose-600' : 'text-emerald-600'">
                                            {{ chk.rx_power_dbm }} dBm
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border" :class="getRxBadge(chk.status).class">
                                                {{ getRxBadge(chk.status).label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">{{ chk.notes || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else class="text-center py-12 text-slate-400">
                        <span class="text-4xl block mb-2">⚡</span>
                        <p class="font-medium text-slate-700">Hasil Ukur Redaman Belum Diinput</p>
                        <p class="text-xs text-slate-400 mt-1">Teknisi lapangan akan menginput nilai ukur OPM saat pengerjaan selesai.</p>
                    </div>
                </div>

                <!-- ════ TAB 4: FOTO BUKTI LAPANGAN ════ -->
                <div v-if="activeTab === 'photos'" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Foto Tiang -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                            <span class="text-xs font-bold text-slate-700 uppercase block mb-2">📸 Foto Box Terpasang di Tiang</span>
                            <div v-if="workOrder.photo_pole" class="aspect-video rounded-xl overflow-hidden border bg-black">
                                <img :src="'/storage/' + workOrder.photo_pole" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="aspect-video rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400">
                                Belum ada foto tiang
                            </div>
                        </div>

                        <!-- Foto Splicing -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                            <span class="text-xs font-bold text-slate-700 uppercase block mb-2">📸 Foto Tray Splicing Fiber</span>
                            <div v-if="workOrder.photo_splicing" class="aspect-video rounded-xl overflow-hidden border bg-black">
                                <img :src="'/storage/' + workOrder.photo_splicing" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="aspect-video rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400">
                                Belum ada foto splicing
                            </div>
                        </div>

                        <!-- Foto OPM -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                            <span class="text-xs font-bold text-slate-700 uppercase block mb-2">📸 Foto Layar Display OPM</span>
                            <div v-if="workOrder.photo_opm" class="aspect-video rounded-xl overflow-hidden border bg-black">
                                <img :src="'/storage/' + workOrder.photo_opm" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="aspect-video rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400">
                                Belum ada foto OPM
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: INPUT LAPORAN PELAKSANAAN TEKNISI -->
            <div v-if="showReportModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 max-h-[90vh] overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-base font-bold text-slate-900">📋 Form Laporan Lapangan &amp; QC Redaman</h3>
                        <button @click="showReportModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                    </div>

                    <form @submit.prevent="submitFieldReport" class="space-y-5">
                        <!-- Titik Koordinat Aktual Tiang / Lapangan -->
                        <div class="p-4 bg-slate-50 rounded-2xl space-y-2 border border-slate-100">
                            <div class="flex items-center justify-between">
                                <label class="block text-[11px] font-bold text-slate-700 uppercase">
                                    📍 Titik Koordinat Aktual Tiang (GPS / Ketik Manual)
                                </label>
                                <button
                                    type="button"
                                    @click="getReportLocation"
                                    :disabled="isReportLocating"
                                    class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-lg text-xs font-bold flex items-center gap-1 shadow-2xs transition-all disabled:opacity-60 cursor-pointer"
                                >
                                    <span>{{ isReportLocating ? 'Mendeteksi GPS...' : '📍 Auto GPS Saat Ini' }}</span>
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-1">Latitude Aktual (Ketik Manual / Auto)</label>
                                    <input
                                        v-model="reportForm.actual_latitude"
                                        type="text"
                                        placeholder="-6.123456"
                                        class="w-full text-xs font-mono font-medium rounded-xl border-slate-200"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-1">Longitude Aktual (Ketik Manual / Auto)</label>
                                    <input
                                        v-model="reportForm.actual_longitude"
                                        type="text"
                                        placeholder="106.123456"
                                        class="w-full text-xs font-mono font-medium rounded-xl border-slate-200"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Meteran Kabel -->
                        <div class="grid grid-cols-3 gap-3 p-4 bg-slate-50 rounded-2xl">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Meter Awal Drum</label>
                                <input v-model.number="reportForm.cable_meter_start" type="number" class="w-full text-xs rounded-xl border-slate-200" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Meter Akhir Drum</label>
                                <input v-model.number="reportForm.cable_meter_end" type="number" class="w-full text-xs rounded-xl border-slate-200" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Total Meter Riil</label>
                                <input v-model.number="reportForm.cable_length_used" type="number" class="w-full text-xs font-bold text-indigo-700 rounded-xl border-slate-200" />
                            </div>
                        </div>

                        <!-- Upload Foto -->
                        <div class="space-y-3">
                            <span class="text-xs font-bold text-slate-700 uppercase block">Unggah Foto Bukti Lapangan</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-1">Foto Tiang</label>
                                    <input type="file" @change="e => reportForm.photo_pole = e.target.files[0]" accept="image/*" class="w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-1">Foto Splicing Tray</label>
                                    <input type="file" @change="e => reportForm.photo_splicing = e.target.files[0]" accept="image/*" class="w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-1">Foto Layar OPM</label>
                                    <input type="file" @change="e => reportForm.photo_opm = e.target.files[0]" accept="image/*" class="w-full text-xs" />
                                </div>
                            </div>
                        </div>

                        <!-- Uji Redaman Port -->
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-slate-700 uppercase block">Pengukuran Redaman Optik (OPM dBm)</span>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div v-for="(chk, i) in reportForm.optical_checks" :key="i" class="p-2.5 bg-slate-50 rounded-xl border text-xs">
                                    <span class="font-bold text-slate-700 block mb-1">Port {{ chk.port_number }}</span>
                                    <input v-model.number="chk.rx_power_dbm" type="number" step="0.1" placeholder="-19.5" class="w-full py-1 px-2 text-xs font-mono font-bold rounded-lg border-slate-200" />
                                </div>
                            </div>
                        </div>

                        <!-- Rekonsiliasi Material (Berapa yang terpakai) -->
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-slate-700 uppercase block">Rekonsiliasi Material Terpakai vs Retur</span>
                            <div class="space-y-2 text-xs">
                                <div v-for="mat in reportForm.reconciliations" :key="mat.id" class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
                                    <span class="font-semibold text-slate-800">{{ mat.name }} (Diambil: {{ mat.requested_qty }} {{ mat.unit }})</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-500">Terpakai:</span>
                                        <input v-model.number="mat.used_qty" type="number" step="any" class="w-20 text-xs py-1 px-2 text-center rounded-lg border-slate-200 font-bold" />
                                        <span class="text-slate-500">{{ mat.unit }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t">
                            <button type="button" @click="showReportModal = false" class="px-4 py-2 bg-slate-100 rounded-xl text-xs font-bold">Batal</button>
                            <button type="submit" :disabled="reportForm.processing" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-md">
                                Kirim Laporan Lapangan &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: REJECT REVISI NOC -->
            <div v-if="showRejectModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4">
                    <h3 class="text-base font-bold text-slate-900">❌ Minta Revisi Teknisi</h3>
                    <p class="text-xs text-slate-500">Tuliskan alasan penolakan atau instruksi perbaikan (contoh: redaman port 3 tinggi, kabel kurang kencang):</p>
                    <textarea v-model="rejectReason" rows="3" class="w-full text-xs rounded-xl border-slate-200" placeholder="Masukkan instruksi revisi..."></textarea>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 bg-slate-100 rounded-xl text-xs font-bold">Batal</button>
                        <button type="button" @click="submitRejection" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold">Kirim Revisi</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
