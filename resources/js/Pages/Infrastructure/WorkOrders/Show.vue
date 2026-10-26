<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    workOrder: Object,
    materials: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('details'); // 'details' | 'materials' | 'optical' | 'photos'
const showReportModal = ref(false);
const showRejectModal = ref(false);
const rejectReason = ref('');

// Permintaan Tambahan Material Lapangan (Request Material)
const showMaterialRequestModal = ref(false);
const materialRequestForm = useForm({
    items: [
        { item_name: '', material_id: null, quantity: 1, unit: 'meter' }
    ],
    notes: '',
});

const addRequestItem = () => {
    materialRequestForm.items.push({ item_name: '', material_id: null, quantity: 1, unit: 'pcs' });
};

const removeRequestItem = (idx) => {
    if (materialRequestForm.items.length > 1) {
        materialRequestForm.items.splice(idx, 1);
    }
};

const onMaterialSelect = (item) => {
    if (item.material_id) {
        const found = props.materials.find(m => m.id === item.material_id);
        if (found) {
            item.item_name = found.name;
            item.unit = found.unit || 'pcs';
        }
    }
};

const submitMaterialRequest = () => {
    const validItems = materialRequestForm.items.filter(i => (i.item_name && i.item_name.trim()) || i.material_id);
    if (validItems.length === 0) {
        alert('Silakan tuliskan atau pilih minimal 1 material yang dibutuhkan.');
        return;
    }
    if (!materialRequestForm.notes || !materialRequestForm.notes.trim()) {
        alert('Mohon tuliskan alasan / kondisi kebutuhan di lapangan.');
        return;
    }

    materialRequestForm.items = validItems.map(i => ({
        item_name: i.item_name ? i.item_name.trim() : '',
        material_id: i.material_id,
        quantity: parseFloat(i.quantity) || 1,
        unit: i.unit || 'pcs',
    }));

    materialRequestForm.post(route('infrastructure.work-orders.request-material', props.workOrder.id), {
        preserveScroll: true,
        onSuccess: () => {
            materialRequestForm.reset();
            materialRequestForm.items = [{ item_name: '', material_id: null, quantity: 1, unit: 'meter' }];
            materialRequestForm.notes = '';
            showMaterialRequestModal.value = false;
        }
    });
};

// Review & Approval Material Tambahan (Ka. Teknisi / Gudang / Admin)
const showReviewMaterialModal = ref(false);
const selectedReviewRequest = ref(null);
const reviewForm = useForm({
    material_id: '',
    quantity: '',
    notes: '',
    rejection_reason: '',
});

const openReviewModal = (mr) => {
    selectedReviewRequest.value = mr;
    reviewForm.material_id = mr.material_id || (props.materials[0]?.id || '');
    reviewForm.quantity = mr.quantity;
    reviewForm.notes = '';
    reviewForm.rejection_reason = '';
    showReviewMaterialModal.value = true;
};

const approveReviewRequest = () => {
    if (!selectedReviewRequest.value) return;
    if (!reviewForm.material_id) {
        alert('Silakan pilih material dari stok gudang.');
        return;
    }
    reviewForm.post(route('infrastructure.work-orders.material-requests.approve', [props.workOrder.id, selectedReviewRequest.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showReviewMaterialModal.value = false;
            selectedReviewRequest.value = null;
        }
    });
};

const rejectReviewRequest = () => {
    if (!selectedReviewRequest.value) return;
    if (!reviewForm.rejection_reason || !reviewForm.rejection_reason.trim()) {
        alert('Silakan masukkan alasan penolakan.');
        return;
    }
    reviewForm.post(route('infrastructure.work-orders.material-requests.reject', [props.workOrder.id, selectedReviewRequest.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showReviewMaterialModal.value = false;
            selectedReviewRequest.value = null;
        }
    });
};

const pendingMaterialRequests = computed(() => {
    return (props.workOrder.material_requests || []).filter(r => r.status === 'pending');
});

// Form Pelaporan Lapangan Teknisi
const reportForm = useForm({
    actual_latitude: props.workOrder.actual_latitude || props.workOrder.planned_latitude || '',
    actual_longitude: props.workOrder.actual_longitude || props.workOrder.planned_longitude || '',
    start_point: props.workOrder.start_point || '',
    end_point: props.workOrder.end_point || '',
    cable_meter_start: props.workOrder.cable_meter_start || 0,
    cable_meter_end: props.workOrder.cable_meter_end || 0,
    cable_length_used: props.workOrder.cable_length_used || 0,
    photo_odp: null,
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

// Sinkronisasi daftar rekonsiliasi jika ada material tambahan yang baru disetujui
watch(() => props.workOrder.materials, (newMats) => {
    if (newMats) {
        reportForm.reconciliations = newMats.map(m => {
            const existing = reportForm.reconciliations.find(r => r.id === m.id);
            return {
                id: m.id,
                name: m.material?.name || 'Material',
                requested_qty: m.requested_qty,
                used_qty: existing ? existing.used_qty : (m.used_qty ?? m.requested_qty),
                unit: m.unit,
            };
        });
    }
}, { deep: true });

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

const isUploadingPhoto = ref(false);
const onQuickPhotoUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    isUploadingPhoto.value = true;
    const formData = new FormData();
    formData.append('photo_odp', file);
    router.post(route('infrastructure.work-orders.upload-photo', props.workOrder.id), formData, {
        preserveScroll: true,
        onFinish: () => {
            isUploadingPhoto.value = false;
        }
    });
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

const cancelWorkOrder = () => {
    if (confirm(`Apakah Anda yakin ingin membatalkan & menghapus Work Order Rollout ${props.workOrder.wo_number} (${props.workOrder.target_name})? Seluruh material yang telah dialokasikan/disiapkan akan otomatis dikembalikan ke stok gudang.`)) {
        router.delete(route('infrastructure.work-orders.destroy', props.workOrder.id));
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

            <!-- Alert Permintaan Material Tambahan Menunggu Review -->
            <div v-if="pendingMaterialRequests.length > 0" class="mb-4 p-4 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 text-white flex items-center justify-between shadow-lg animate-fade-in-up">
                <div class="flex items-center gap-3">
                    <span class="text-2xl animate-bounce">📦</span>
                    <div>
                        <p class="font-black text-sm">Ada {{ pendingMaterialRequests.length }} Permintaan Tambahan Material Lapangan!</p>
                        <p class="text-xs text-amber-100">Teknisi mengajukan kebutuhan material tambahan untuk melanjutkan pekerjaan.</p>
                    </div>
                </div>
                <button type="button" @click="activeTab = 'materials'" class="px-4 py-2 bg-white text-amber-900 rounded-xl font-bold text-xs shadow-sm hover:bg-amber-50 transition-all cursor-pointer shrink-0">
                    Buka Tab Material &rarr;
                </button>
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
                            v-can="'infra_wo_btn_print_sj'"
                            :href="route('infrastructure.work-orders.print-surat-jalan', workOrder.id)"
                            target="_blank"
                            class="px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5"
                        >
                            <span>📄 Cetak Surat Jalan</span>
                        </a>

                        <!-- Action: Request Tambahan Material Lapangan (Teknisi / Admin) -->
                        <button
                            v-if="workOrder.status !== 'completed'"
                            v-can="'infrastructure_work_orders_request_material'"
                            type="button"
                            @click="showMaterialRequestModal = true"
                            class="px-4 py-2 bg-amber-500/90 hover:bg-amber-400 text-slate-900 font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>📢 + Request Material</span>
                        </button>

                        <!-- Action: Gudang / Ka. Teknisi / Maintenance Approve Material -->
                        <button
                            v-if="workOrder.status === 'pending_material'"
                            v-can="'infrastructure_work_orders_dispatch'"
                            type="button"
                            @click="approveMaterialDispatch"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                        >
                            <span>📦 Konfirmasi Pengeluaran Material</span>
                        </button>

                        <!-- Action: Teknisi Start Work -->
                        <button
                            v-if="workOrder.status === 'material_ready'"
                            v-can="'infrastructure_work_orders_start'"
                            type="button"
                            @click="startWork"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                        >
                            <span>🚀 Mulai Pengerjaan Lapangan</span>
                        </button>

                        <!-- Action: Teknisi Submit Report -->
                        <button
                            v-if="workOrder.status === 'in_progress' || workOrder.status === 'rejected'"
                            v-can="'infrastructure_work_orders_report'"
                            type="button"
                            @click="showReportModal = true"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                        >
                            <span>📋 Input Laporan &amp; QC Redaman</span>
                        </button>

                        <!-- Action: NOC / Ka. Teknisi Review Approve / Reject -->
                        <div v-if="workOrder.status === 'under_review'" class="flex items-center gap-2">
                            <button
                                v-can="'infrastructure_work_orders_approve'"
                                type="button"
                                @click="approveCommissioning"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5"
                            >
                                <span>✅ Setujui &amp; Komisioning ke GIS</span>
                            </button>

                            <button
                                v-can="'infrastructure_work_orders_reject'"
                                type="button"
                                @click="showRejectModal = true"
                                class="px-3 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition-all"
                            >
                                <span>Minta Revisi</span>
                            </button>
                        </div>

                        <!-- Action: Batalkan & Hapus Work Order (Stok Kembali ke Gudang) -->
                        <button
                            v-can="'infrastructure_work_orders_delete'"
                            type="button"
                            @click="cancelWorkOrder"
                            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
                            title="Batalkan dan hapus penugasan rollout, seluruh material akan dikembalikan ke stok gudang"
                        >
                            <span>🗑️</span>
                            <span>Batalkan Work Order</span>
                        </button>
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

                    <!-- Daftar Permintaan Tambahan Material Lapangan (Request Material) -->
                    <div class="mt-8 pt-6 border-t border-slate-200/80">
                        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                            <div>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                    <span>📢</span> Permintaan Tambahan Material Lapangan ({{ workOrder.material_requests?.length || 0 }})
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Pengajuan kebutuhan darurat dari teknisi lapangan saat pelaksanaan penarikan / splicing ODC &amp; ODP.
                                </p>
                            </div>
                            <button
                                v-if="workOrder.status !== 'completed'"
                                type="button"
                                @click="showMaterialRequestModal = true"
                                class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>+ Request Tambahan Baru</span>
                            </button>
                        </div>

                        <div v-if="!workOrder.material_requests || workOrder.material_requests.length === 0" class="p-6 text-center bg-slate-50 rounded-2xl border border-slate-200/60 text-slate-400 text-xs">
                            Belum ada permintaan tambahan material lapangan untuk penugasan ini.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="mr in workOrder.material_requests"
                                :key="'mr-' + mr.id"
                                class="p-4 rounded-2xl border transition-all text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
                                :class="{
                                    'bg-amber-50/70 border-amber-300': mr.status === 'pending',
                                    'bg-emerald-50/70 border-emerald-300': mr.status === 'approved',
                                    'bg-rose-50/70 border-rose-300': mr.status === 'rejected',
                                }"
                            >
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-slate-900 text-sm">
                                            📦 {{ mr.item_name || mr.material?.name || 'Material' }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 font-mono font-black text-amber-700">
                                            {{ mr.quantity }} {{ mr.unit }}
                                        </span>
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase"
                                            :class="{
                                                'bg-amber-100 text-amber-800 border-amber-300 animate-pulse': mr.status === 'pending',
                                                'bg-emerald-100 text-emerald-800 border-emerald-300': mr.status === 'approved',
                                                'bg-rose-100 text-rose-800 border-rose-300': mr.status === 'rejected',
                                            }"
                                        >
                                            {{ mr.status === 'pending' ? '⏳ Menunggu Persetujuan' : (mr.status === 'approved' ? '✓ Disetujui & Masuk Bekal' : '✕ Ditolak') }}
                                        </span>
                                    </div>
                                    <p class="text-slate-600">
                                        <b>Alasan / Kondisi Lapangan:</b> "{{ mr.notes || '-' }}"
                                    </p>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-2 flex-wrap">
                                        <span>Diajukan oleh: <b>{{ mr.requester?.name || 'Teknisi' }}</b></span>
                                        <span>&bull;</span>
                                        <span>{{ formatDate(mr.created_at) }}</span>
                                        <template v-if="mr.reviewer">
                                            <span>&bull;</span>
                                            <span>Direview oleh: <b>{{ mr.reviewer?.name }}</b></span>
                                        </template>
                                    </div>
                                    <div v-if="mr.rejection_reason && mr.status === 'rejected'" class="text-rose-700 font-semibold text-[11px]">
                                        Alasan Penolakan: {{ mr.rejection_reason }}
                                    </div>
                                </div>

                                <!-- Action Approval untuk Admin / Gudang / Ka. Teknisi jika status Pending -->
                                <div v-if="mr.status === 'pending'" v-can="'infrastructure_work_orders_review_material'" class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        @click="openReviewModal(mr)"
                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1"
                                    >
                                        <span>✓ Review &amp; Setujui</span>
                                    </button>
                                </div>
                            </div>
                        </div>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Foto Fisik ODP / ODC (Auto Sync ke Master Data) -->
                        <div class="bg-indigo-50/60 p-4 rounded-2xl border-2 border-indigo-200/90 flex flex-col justify-between shadow-xs">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-indigo-950 uppercase flex items-center gap-1">
                                        <span>📷</span> Foto Fisik {{ workOrder.type === 'odc_deployment' ? 'ODC' : 'ODP' }}
                                    </span>
                                    <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        Auto GIS
                                    </span>
                                </div>
                                <p class="text-[10px] text-indigo-700/80 mb-2">
                                    Otomatis mengisi foto data {{ workOrder.type === 'odc_deployment' ? 'ODC' : 'ODP' }} di master &amp; peta GIS.
                                </p>
                            </div>

                            <div v-if="workOrder.photo_odp" class="aspect-video rounded-xl overflow-hidden border bg-black relative group my-2">
                                <img :src="'/storage/' + workOrder.photo_odp" class="w-full h-full object-cover" />
                                <div v-can="'infrastructure_work_orders_upload_photo'" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <label class="px-3 py-1.5 bg-white text-slate-900 rounded-xl text-xs font-bold cursor-pointer hover:bg-slate-100 shadow transition-all">
                                        🔄 Ganti Foto
                                        <input type="file" @change="onQuickPhotoUpload" accept="image/*" class="hidden" />
                                    </label>
                                </div>
                            </div>
                            <div v-else class="aspect-video rounded-xl border-2 border-dashed border-indigo-200 bg-white/70 flex flex-col items-center justify-center p-3 text-center my-2">
                                <span class="text-2xl mb-1">📷</span>
                                <span class="text-[11px] font-bold text-slate-600 mb-2">Belum ada foto fisik {{ workOrder.type === 'odc_deployment' ? 'ODC' : 'ODP' }}</span>
                                <label v-can="'infrastructure_work_orders_upload_photo'" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold cursor-pointer transition-all shadow-xs">
                                    <span>+ Upload Foto Sekarang</span>
                                    <input type="file" @change="onQuickPhotoUpload" accept="image/*" class="hidden" />
                                </label>
                            </div>

                            <div class="text-[10px] text-slate-400 text-right mt-1">
                                <span v-if="workOrder.photo_odp" class="text-emerald-600 font-bold">✓ Tersinkronisasi ke Master</span>
                                <span v-else class="text-amber-600 font-medium">Bisa diunggah saat pengerjaan</span>
                            </div>
                        </div>

                        <!-- Foto Tiang -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-700 uppercase block mb-2">📸 Foto Box Terpasang di Tiang</span>
                                <p class="text-[10px] text-slate-400 mb-2">Bukti box terpasang rapi pada tiang.</p>
                            </div>
                            <div v-if="workOrder.photo_pole" class="aspect-video rounded-xl overflow-hidden border bg-black my-2">
                                <img :src="'/storage/' + workOrder.photo_pole" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="aspect-video rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400 my-2">
                                Belum ada foto tiang
                            </div>
                            <span class="text-[10px] text-slate-400 text-right mt-1">Dokumentasi Lapangan</span>
                        </div>

                        <!-- Foto Splicing -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-700 uppercase block mb-2">📸 Foto Tray Splicing Fiber</span>
                                <p class="text-[10px] text-slate-400 mb-2">Bukti kerapian susunan core &amp; cassette.</p>
                            </div>
                            <div v-if="workOrder.photo_splicing" class="aspect-video rounded-xl overflow-hidden border bg-black my-2">
                                <img :src="'/storage/' + workOrder.photo_splicing" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="aspect-video rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400 my-2">
                                Belum ada foto splicing
                            </div>
                            <span class="text-[10px] text-slate-400 text-right mt-1">QC Splicer</span>
                        </div>

                        <!-- Foto OPM -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-700 uppercase block mb-2">📸 Foto Layar Display OPM</span>
                                <p class="text-[10px] text-slate-400 mb-2">Bukti nilai redaman optik terkalibrasi.</p>
                            </div>
                            <div v-if="workOrder.photo_opm" class="aspect-video rounded-xl overflow-hidden border bg-black my-2">
                                <img :src="'/storage/' + workOrder.photo_opm" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="aspect-video rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400 my-2">
                                Belum ada foto OPM
                            </div>
                            <span class="text-[10px] text-slate-400 text-right mt-1">QC Redaman</span>
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
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                                <div class="p-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200">
                                    <label class="block text-[11px] font-bold text-indigo-950 mb-0.5">📸 Foto Fisik ODP / ODC *</label>
                                    <span class="text-[10px] text-indigo-600 block mb-1.5 font-medium">Auto isi data master &amp; GIS</span>
                                    <input type="file" @change="e => reportForm.photo_odp = e.target.files[0]" accept="image/*" class="w-full text-xs" />
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-0.5">📸 Foto Tiang</label>
                                    <span class="text-[10px] text-slate-500 block mb-1.5">Box terpasang di tiang</span>
                                    <input type="file" @change="e => reportForm.photo_pole = e.target.files[0]" accept="image/*" class="w-full text-xs" />
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-0.5">📸 Foto Splicing Tray</label>
                                    <span class="text-[10px] text-slate-500 block mb-1.5">Core &amp; tray rapi</span>
                                    <input type="file" @change="e => reportForm.photo_splicing = e.target.files[0]" accept="image/*" class="w-full text-xs" />
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-0.5">📸 Foto Layar OPM</label>
                                    <span class="text-[10px] text-slate-500 block mb-1.5">Display nilai dBm</span>
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
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <span class="text-xs font-bold text-slate-700 uppercase block">
                                    Rekonsiliasi Material Terpakai vs Retur
                                </span>
                                <button
                                    type="button"
                                    @click="showMaterialRequestModal = true"
                                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold shadow-xs transition-all flex items-center gap-1 cursor-pointer"
                                >
                                    <span>📢 + Request Tambahan Material</span>
                                </button>
                            </div>

                            <div v-if="pendingMaterialRequests.length > 0" class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                                <span>⏳ Terdapat <b>{{ pendingMaterialRequests.length }} permintaan material</b> sedang menunggu persetujuan Ka. Teknisi / Gudang.</span>
                                <span class="font-bold text-[10px] bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full">Menunggu</span>
                            </div>

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

            <!-- MODAL: REQUEST TAMBAHAN MATERIAL LAPANGAN -->
            <div v-if="showMaterialRequestModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 max-h-[90vh] overflow-y-auto space-y-5 animate-fade-in-up">
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-1.5">
                                <span>📢</span> Request Tambahan Material Lapangan
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Penugasan {{ workOrder.wo_number }} &bull; {{ workOrder.target_name }}</p>
                        </div>
                        <button type="button" @click="showMaterialRequestModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                    </div>

                    <form @submit.prevent="submitMaterialRequest" class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700 uppercase">
                                    Daftar Material yang Dibutuhkan *
                                </label>
                                <button
                                    type="button"
                                    @click="addRequestItem"
                                    class="text-indigo-600 hover:text-indigo-800 font-bold text-xs flex items-center gap-1 cursor-pointer"
                                >
                                    <span>+ Tambah Baris</span>
                                </button>
                            </div>

                            <div v-for="(item, idx) in materialRequestForm.items" :key="'req-row-' + idx" class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Baris #{{ idx + 1 }}</span>
                                    <button
                                        v-if="materialRequestForm.items.length > 1"
                                        type="button"
                                        @click="removeRequestItem(idx)"
                                        class="text-rose-500 hover:text-rose-700 text-xs font-bold cursor-pointer"
                                    >
                                        ✕ Hapus
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                    <!-- Pilih Material dari Gudang atau Ketik Manual -->
                                    <div class="sm:col-span-6">
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Pilih dari Master / Ketik</label>
                                        <select
                                            v-model="item.material_id"
                                            @change="onMaterialSelect(item)"
                                            class="w-full text-xs rounded-xl border-slate-300 py-1.5 px-2 bg-white"
                                        >
                                            <option :value="null">-- Ketik Nama Manual --</option>
                                            <option v-for="m in materials" :key="m.id" :value="m.id">
                                                {{ m.name }} (Stok: {{ m.stock }} {{ m.unit }})
                                            </option>
                                        </select>
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Nama Barang *</label>
                                        <input
                                            type="text"
                                            v-model="item.item_name"
                                            placeholder="Cth: Dropcore 50m / Klem Tiang"
                                            class="w-full text-xs rounded-xl border-slate-300 py-1.5 px-2"
                                            required
                                        />
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Jumlah (Qty) *</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            v-model.number="item.quantity"
                                            class="w-full text-xs font-bold text-center rounded-xl border-slate-300 py-1.5 px-2"
                                            required
                                        />
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Satuan *</label>
                                        <select v-model="item.unit" class="w-full text-xs rounded-xl border-slate-300 py-1.5 px-2 bg-white">
                                            <option value="meter">meter</option>
                                            <option value="pcs">pcs</option>
                                            <option value="rol">rol</option>
                                            <option value="set">set</option>
                                            <option value="unit">unit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alasan Kebutuhan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Alasan / Kondisi Lapangan *
                            </label>
                            <textarea
                                v-model="materialRequestForm.notes"
                                rows="2"
                                placeholder="Contoh: Kabel kurang 40 meter karena tiang harus memutar melewati persimpangan jalan..."
                                class="w-full text-xs rounded-xl border-slate-300 p-2.5"
                                required
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t">
                            <button type="button" @click="showMaterialRequestModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="materialRequestForm.processing" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-md transition-all cursor-pointer disabled:opacity-50">
                                Kirim Permintaan Tambahan &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: REVIEW & PERSETUJUAN MATERIAL TAMBAHAN (Ka. Teknisi / Gudang / Admin) -->
            <div v-if="showReviewMaterialModal && selectedReviewRequest" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 space-y-4 animate-fade-in-up">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-1.5">
                            <span>📦</span> Review Permintaan Tambahan Material
                        </h3>
                        <button type="button" @click="showReviewMaterialModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                    </div>

                    <div class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs space-y-1">
                        <p class="font-bold text-amber-900">
                            Kebutuhan Teknisi: {{ selectedReviewRequest.item_name }} ({{ selectedReviewRequest.quantity }} {{ selectedReviewRequest.unit }})
                        </p>
                        <p class="text-slate-600">
                            <b>Alasan:</b> "{{ selectedReviewRequest.notes }}"
                        </p>
                        <p class="text-[10px] text-slate-400">
                            Diajukan oleh: <b>{{ selectedReviewRequest.requester?.name || 'Teknisi' }}</b> &bull; {{ formatDate(selectedReviewRequest.created_at) }}
                        </p>
                    </div>

                    <!-- Pilih Material Gudang & Qty Disetujui -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Pilih Material dari Stok Gudang *
                            </label>
                            <select
                                v-model="reviewForm.material_id"
                                class="w-full text-xs rounded-xl border-slate-300 py-2 px-3 bg-white font-medium"
                                required
                            >
                                <option value="" disabled>-- Pilih Material Gudang --</option>
                                <option v-for="m in materials" :key="m.id" :value="m.id">
                                    {{ m.name }} — Stok: {{ m.stock }} {{ m.unit }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Jumlah yang Disetujui *
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    v-model.number="reviewForm.quantity"
                                    class="w-32 text-xs font-bold rounded-xl border-slate-300 py-1.5 px-3 text-center"
                                    required
                                />
                                <span class="text-xs text-slate-500 font-bold">{{ selectedReviewRequest.unit }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Alasan Penolakan (Hanya jika ingin menolak)
                            </label>
                            <input
                                type="text"
                                v-model="reviewForm.rejection_reason"
                                placeholder="Tulis alasan jika menolak permintaan..."
                                class="w-full text-xs rounded-xl border-slate-300 py-1.5 px-3"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t gap-2">
                        <button
                            type="button"
                            @click="rejectReviewRequest"
                            :disabled="reviewForm.processing"
                            class="px-4 py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-xl text-xs font-bold transition-all border border-rose-200 cursor-pointer disabled:opacity-50"
                        >
                            ✕ Tolak Permintaan
                        </button>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="showReviewMaterialModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="approveReviewRequest"
                                :disabled="reviewForm.processing"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition-all cursor-pointer disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>✓ Setujui &amp; Potong Stok</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
