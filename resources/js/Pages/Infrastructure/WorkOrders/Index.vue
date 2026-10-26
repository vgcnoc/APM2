<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
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
        </div>
    </AppLayout>
</template>
