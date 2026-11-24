<script setup>
import { ref, reactive, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    customers: Object,
    counts: {
        type: Object,
        default: () => ({
            total: 0,
            port_occupied: 0,
            no_cbp: 0,
            in_process: 0,
            completed: 0,
            no_ont: 0,
        }),
    },
    areas: { type: Array, default: () => [] },
    odcs: { type: Array, default: () => [] },
    odps: { type: Array, default: () => [] },
    technicians: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

// State filter reaktif
const form = reactive({
    search: props.filters.search || '',
    status_cbp: props.filters.status_cbp || 'all',
    port_status: props.filters.port_status || 'all',
    area_id: props.filters.area_id ? String(props.filters.area_id) : '',
    odc_id: props.filters.odc_id ? String(props.filters.odc_id) : '',
    odp_id: props.filters.odp_id ? String(props.filters.odp_id) : '',
    technician_id: props.filters.technician_id ? String(props.filters.technician_id) : '',
    date_type: props.filters.date_type || 'updated',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    sort_by: props.filters.sort_by || 'latest',
    per_page: Number(props.filters.per_page) || 20,
});

// Toggle expand advanced filter panel
const showAdvancedFilter = ref(
    Boolean(
        form.area_id ||
        form.odc_id ||
        form.odp_id ||
        form.technician_id ||
        form.date_from ||
        form.date_to ||
        (form.sort_by && form.sort_by !== 'latest') ||
        (form.per_page && form.per_page !== 20)
    )
);

// Dynamic ODP list (filtered by selected ODC and/or Area if chosen)
const filteredOdps = computed(() => {
    let list = props.odps;
    if (form.odc_id) {
        list = list.filter(o => String(o.odc_id) === String(form.odc_id));
    }
    if (form.area_id) {
        list = list.filter(o => String(o.area_id) === String(form.area_id));
    }
    return list;
});

// Debounce helper
const debounce = (fn, delay = 350) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const applyFilter = () => {
    router.get(
        route('network-data.terminated'),
        {
            search: form.search || undefined,
            status_cbp: form.status_cbp !== 'all' ? form.status_cbp : undefined,
            port_status: form.port_status !== 'all' ? form.port_status : undefined,
            area_id: form.area_id || undefined,
            odc_id: form.odc_id || undefined,
            odp_id: form.odp_id || undefined,
            technician_id: form.technician_id || undefined,
            date_type: (form.date_from || form.date_to) ? form.date_type : undefined,
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
            sort_by: form.sort_by !== 'latest' ? form.sort_by : undefined,
            per_page: form.per_page !== 20 ? form.per_page : undefined,
        },
        { preserveState: true, replace: true }
    );
};

const debouncedSearch = debounce(() => {
    applyFilter();
}, 350);

// Watch search with debounce
watch(() => form.search, () => {
    debouncedSearch();
});

// Watch ODC change to reset ODP if needed
watch(() => form.odc_id, (newVal) => {
    if (newVal && form.odp_id) {
        const match = props.odps.some(o => String(o.id) === String(form.odp_id) && String(o.odc_id) === String(newVal));
        if (!match) {
            form.odp_id = '';
        }
    }
});

// Date Presets
const setDatePreset = (preset) => {
    const today = new Date();
    const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (preset === 'today') {
        form.date_from = formatDate(today);
        form.date_to = formatDate(today);
    } else if (preset === '7days') {
        const last7 = new Date();
        last7.setDate(today.getDate() - 6);
        form.date_from = formatDate(last7);
        form.date_to = formatDate(today);
    } else if (preset === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        form.date_from = formatDate(firstDay);
        form.date_to = formatDate(today);
    } else if (preset === 'last_month') {
        const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        form.date_from = formatDate(firstDayLastMonth);
        form.date_to = formatDate(lastDayLastMonth);
    } else if (preset === 'all') {
        form.date_from = '';
        form.date_to = '';
    }
    applyFilter();
};

// Card Filter Shortcuts
const selectKpiCard = (type) => {
    if (type === 'all') {
        form.status_cbp = 'all';
        form.port_status = 'all';
    } else if (type === 'occupied') {
        form.port_status = form.port_status === 'occupied' ? 'all' : 'occupied';
        form.status_cbp = 'all';
    } else if (type === 'no_cbp') {
        form.status_cbp = form.status_cbp === 'no_request' ? 'all' : 'no_request';
        form.port_status = 'all';
    } else if (type === 'in_process') {
        form.status_cbp = form.status_cbp === 'in_process' ? 'all' : 'in_process';
        form.port_status = 'all';
    } else if (type === 'completed') {
        form.port_status = form.port_status === 'freed' ? 'all' : 'freed';
        form.status_cbp = 'all';
    } else if (type === 'no_ont') {
        form.port_status = form.port_status === 'no_ont' ? 'all' : 'no_ont';
        form.status_cbp = 'all';
    }
    applyFilter();
};

// Reset All Filters
const resetFilters = () => {
    form.search = '';
    form.status_cbp = 'all';
    form.port_status = 'all';
    form.area_id = '';
    form.odc_id = '';
    form.odp_id = '';
    form.technician_id = '';
    form.date_type = 'updated';
    form.date_from = '';
    form.date_to = '';
    form.sort_by = 'latest';
    form.per_page = 20;
    applyFilter();
};

// Check if any filter is actively applied
const hasActiveFilters = computed(() => {
    return Boolean(
        form.search ||
        form.status_cbp !== 'all' ||
        form.port_status !== 'all' ||
        form.area_id ||
        form.odc_id ||
        form.odp_id ||
        form.technician_id ||
        form.date_from ||
        form.date_to ||
        form.sort_by !== 'latest' ||
        form.per_page !== 20
    );
});

// Helper names for active filter badges
const selectedAreaName = computed(() => {
    if (!form.area_id) return '';
    const a = props.areas.find(ar => String(ar.id) === String(form.area_id));
    return a ? a.name : `Area #${form.area_id}`;
});

const selectedOdcName = computed(() => {
    if (!form.odc_id) return '';
    const o = props.odcs.find(od => String(od.id) === String(form.odc_id));
    return o ? o.name : `ODC #${form.odc_id}`;
});

const selectedOdpName = computed(() => {
    if (!form.odp_id) return '';
    const op = props.odps.find(p => String(p.id) === String(form.odp_id));
    return op ? op.name : `ODP #${form.odp_id}`;
});

const selectedTechnicianName = computed(() => {
    if (!form.technician_id) return '';
    const t = props.technicians.find(tech => String(tech.id) === String(form.technician_id));
    return t ? t.name : `Teknisi #${form.technician_id}`;
});

// Export CSV of currently filtered view
const exportToCsv = () => {
    if (!props.customers.data || props.customers.data.length === 0) return;

    const headers = ['Kode Pelanggan', 'Nama Pelanggan', 'Telepon', 'Alamat', 'ODP', 'Port', 'SN ONT', 'Status CBP', 'No Tiket CBP', 'Teknisi', 'Jadwal', 'Keterangan Port'];
    const rows = props.customers.data.map(c => [
        `"${c.customer_code || ''}"`,
        `"${(c.name || '').replace(/"/g, '""')}"`,
        `"${c.phone || ''}"`,
        `"${(c.address || '').replace(/"/g, '""')}"`,
        `"${c.ont?.odp?.name || '-'}"`,
        `"${c.ont?.port_number || '-'}"`,
        `"${c.ont?.sn || '-'}"`,
        `"${c.latest_cbp_request ? c.latest_cbp_request.status : 'Belum Ada Tiket'}"`,
        `"${c.latest_cbp_request?.cbp_number || '-'}"`,
        `"${c.latest_cbp_request?.technicians?.map(t => t.name).join(', ') || '-'}"`,
        `"${c.latest_cbp_request?.scheduled_date || '-'}"`,
        `"${c.latest_cbp_request?.status === 'completed' ? 'Port Siap Digunakan' : 'Port Belum Kosong'}"`,
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `data_cabut_perangkat_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const printTable = () => {
    window.print();
};
</script>

<template>
    <Head title="Cabut Perangkat (Stop Permanen)" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h2 class="font-black text-xl text-gray-800 dark:text-white leading-tight flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500 animate-pulse"></span>
                        Cabut Perangkat (Stop Permanen)
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                        Daftar pelanggan stop permanen dan status real-time proses Cabut Perangkat (CBP) sebelum port steril
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <Link :href="route('network-data.index')" class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        &larr; Data Jaringan
                    </Link>
                    <Link :href="route('cbp.jadwal')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Jadwal & Penugasan CBP
                    </Link>
                    <button
                        type="button"
                        @click="exportToCsv"
                        class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                        title="Export CSV"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>CSV</span>
                    </button>
                    <button
                        type="button"
                        @click="printTable"
                        class="px-3 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                        title="Cetak Halaman"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

                <!-- ═══ 1. BANNER PERINGATAN STERILISASI PORT ═══ -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-50 via-orange-50 to-amber-50 dark:from-rose-950/40 dark:via-orange-950/30 dark:to-amber-950/20 border border-rose-200 dark:border-rose-900/60 text-rose-950 dark:text-rose-200 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-rose-900 dark:text-rose-200">Perhatian: Port ODP Belum Kosong Sebelum Selesai Cabut</h4>
                            <p class="text-xs text-rose-800/90 dark:text-rose-300/80 mt-0.5 leading-relaxed">
                                Pelanggan berstatus Stop Permanen masih tercatat menempati port fisik di ODP sampai teknisi selesai melakukan cabut perangkat fisik di lapangan. Pastikan tim teknisi menyelesaikan tugas cabut perangkat agar port dapat dialokasikan untuk pelanggan baru.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══ 2. SMART KPI & QUICK FILTER CARDS (INTERAKTIF) ═══ -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <!-- Card 1: Total Stop -->
                    <div
                        @click="selectKpiCard('all')"
                        class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none bg-white dark:bg-slate-900"
                        :class="(form.port_status === 'all' && form.status_cbp === 'all') ? 'border-slate-800 dark:border-slate-500 ring-2 ring-slate-200 dark:ring-slate-700 shadow-sm' : 'border-gray-200 dark:border-slate-800 hover:border-slate-400'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Stop</span>
                            <div class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5 tracking-tight">{{ counts.total || 0 }}</div>
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Semua stop permanen</div>
                    </div>

                    <!-- Card 2: Port Belum Kosong (Urgent) -->
                    <div
                        @click="selectKpiCard('occupied')"
                        class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none bg-white dark:bg-slate-900"
                        :class="form.port_status === 'occupied' ? 'border-rose-500 ring-2 ring-rose-200 dark:ring-rose-900/60 shadow-sm bg-gradient-to-br from-white to-rose-50/40 dark:from-slate-900 dark:to-rose-950/20' : 'border-gray-200 dark:border-slate-800 hover:border-rose-400'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-wider flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                Port Terisi
                            </span>
                            <div class="w-6 h-6 rounded-lg bg-rose-50 dark:bg-rose-950/60 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1.5 tracking-tight">{{ counts.port_occupied || 0 }}</div>
                        <div class="text-[10px] text-rose-700/80 dark:text-rose-300/70 mt-0.5 truncate font-semibold">🔴 Urgent cabut</div>
                    </div>

                    <!-- Card 3: Belum Ada Tiket CBP -->
                    <div
                        @click="selectKpiCard('no_cbp')"
                        class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none bg-white dark:bg-slate-900"
                        :class="form.status_cbp === 'no_request' ? 'border-amber-500 ring-2 ring-amber-200 dark:ring-amber-900/60 shadow-sm bg-gradient-to-br from-white to-amber-50/40 dark:from-slate-900 dark:to-amber-950/20' : 'border-gray-200 dark:border-slate-800 hover:border-amber-400'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Tanpa Tiket
                            </span>
                            <div class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/60 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1.5 tracking-tight">{{ counts.no_cbp || 0 }}</div>
                        <div class="text-[10px] text-amber-600/80 dark:text-amber-300/70 mt-0.5 truncate">Perlu dibuat tiket</div>
                    </div>

                    <!-- Card 4: Dalam Penugasan / Proses -->
                    <div
                        @click="selectKpiCard('in_process')"
                        class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none bg-white dark:bg-slate-900"
                        :class="form.status_cbp === 'in_process' ? 'border-sky-500 ring-2 ring-sky-200 dark:ring-sky-900/60 shadow-sm bg-gradient-to-br from-white to-sky-50/40 dark:from-slate-900 dark:to-sky-950/20' : 'border-gray-200 dark:border-slate-800 hover:border-sky-400'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-sky-700 dark:text-sky-400 uppercase tracking-wider flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                Proses Cabut
                            </span>
                            <div class="w-6 h-6 rounded-lg bg-sky-50 dark:bg-sky-950/60 flex items-center justify-center text-sky-600 dark:text-sky-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1.5 tracking-tight">{{ counts.in_process || 0 }}</div>
                        <div class="text-[10px] text-sky-600/80 dark:text-sky-300/70 mt-0.5 truncate">Jadwal & Teknisi</div>
                    </div>

                    <!-- Card 5: Port Steril (Selesai Dicabut) -->
                    <div
                        @click="selectKpiCard('completed')"
                        class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none bg-white dark:bg-slate-900"
                        :class="form.port_status === 'freed' ? 'border-emerald-500 ring-2 ring-emerald-200 dark:ring-emerald-900/60 shadow-sm bg-gradient-to-br from-white to-emerald-50/40 dark:from-slate-900 dark:to-emerald-950/20' : 'border-gray-200 dark:border-slate-800 hover:border-emerald-400'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Port Steril
                            </span>
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5 tracking-tight">{{ counts.completed || 0 }}</div>
                        <div class="text-[10px] text-emerald-700/80 dark:text-emerald-300/70 mt-0.5 truncate font-semibold">Siap dialokasikan</div>
                    </div>

                    <!-- Card 6: Tanpa ONT -->
                    <div
                        @click="selectKpiCard('no_ont')"
                        class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none bg-white dark:bg-slate-900"
                        :class="form.port_status === 'no_ont' ? 'border-purple-500 ring-2 ring-purple-200 dark:ring-purple-900/60 shadow-sm bg-gradient-to-br from-white to-purple-50/40 dark:from-slate-900 dark:to-purple-950/20' : 'border-gray-200 dark:border-slate-800 hover:border-purple-400'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-purple-700 dark:text-purple-400 uppercase tracking-wider">Tanpa ONT</span>
                            <div class="w-6 h-6 rounded-lg bg-purple-50 dark:bg-purple-950/60 flex items-center justify-center text-purple-600 dark:text-purple-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1.5 tracking-tight">{{ counts.no_ont || 0 }}</div>
                        <div class="text-[10px] text-purple-600/70 dark:text-purple-300/70 mt-0.5 truncate">Data kabel/ONT (-)</div>
                    </div>
                </div>

                <!-- ═══ 3. SMART FILTER CONTROL BAR (LENGKAP, CERDAS, CANGGIH) ═══ -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-200/80 dark:border-slate-800 p-4 space-y-3.5">
                    <!-- Baris Atas: Search + Quick Selectors + Toggle Advanced Filter -->
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                        <!-- Smart Search Input with Clear Button -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="Cari nama, kode pelanggan (CUS-xxx), no telp, SN ONT, nama ODP, alamat, atau no tiket CBP..."
                                class="block w-full pl-10 pr-9 py-2.5 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"
                            />
                            <button
                                v-if="form.search"
                                type="button"
                                @click="form.search = ''; applyFilter();"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 cursor-pointer"
                                title="Hapus Pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Filter Status Port ODP -->
                        <div class="w-full sm:w-auto min-w-[170px]">
                            <select
                                v-model="form.port_status"
                                @change="applyFilter"
                                class="w-full py-2.5 px-3 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer"
                            >
                                <option value="all">🔌 Semua Status Port</option>
                                <option value="occupied">🔴 Port Belum Kosong (Terisi)</option>
                                <option value="freed">✅ Port Steril (Siap Pakai)</option>
                                <option value="no_ont">⚪ Tanpa ONT Terkait</option>
                            </select>
                        </div>

                        <!-- Filter Status CBP -->
                        <div class="w-full sm:w-auto min-w-[170px]">
                            <select
                                v-model="form.status_cbp"
                                @change="applyFilter"
                                class="w-full py-2.5 px-3 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer"
                            >
                                <option value="all">📋 Semua Status CBP</option>
                                <option value="no_request">⚠️ Belum Ada Tiket CBP</option>
                                <option value="pending">⏳ Menunggu Jadwal (Pending)</option>
                                <option value="assigned">🔧 Teknisi Ditugaskan</option>
                                <option value="completed">✅ Selesai Dicabut</option>
                            </select>
                        </div>

                        <!-- Tombol Expand Filter Lanjutan -->
                        <button
                            type="button"
                            @click="showAdvancedFilter = !showAdvancedFilter"
                            :class="[
                                'px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shrink-0 border',
                                showAdvancedFilter
                                    ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-900/60 shadow-xs'
                                    : 'bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            <span>Filter Lengkap</span>
                            <span v-if="hasActiveFilters" class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showAdvancedFilter ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <!-- Tombol Reset Filter Cepat -->
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="resetFilters"
                            class="px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 bg-gray-100 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer shrink-0"
                            title="Reset Semua Filter"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset</span>
                        </button>
                    </div>

                    <!-- ═══ 4. PANEL FILTER LANJUTAN (ACCORDION CANGGIH) ═══ -->
                    <div
                        v-show="showAdvancedFilter"
                        class="pt-3.5 border-t border-gray-100 dark:border-slate-800/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 animate-in fade-in slide-in-from-top-1"
                    >
                        <!-- Filter Wilayah / Cabang -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-1">Wilayah / Cabang</label>
                            <select
                                v-model="form.area_id"
                                @change="applyFilter"
                                class="w-full py-2 px-2.5 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition cursor-pointer"
                            >
                                <option value="">Semua Wilayah</option>
                                <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                                    {{ area.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter ODC -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-1">ODC (Distribusi)</label>
                            <select
                                v-model="form.odc_id"
                                @change="applyFilter"
                                class="w-full py-2 px-2.5 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition cursor-pointer"
                            >
                                <option value="">Semua ODC</option>
                                <option v-for="odc in odcs" :key="odc.id" :value="String(odc.id)">
                                    {{ odc.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter ODP -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-1">
                                ODP
                                <span v-if="form.odc_id" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-normal">(Sesuai ODC)</span>
                            </label>
                            <select
                                v-model="form.odp_id"
                                @change="applyFilter"
                                class="w-full py-2 px-2.5 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition cursor-pointer"
                            >
                                <option value="">Semua ODP ({{ filteredOdps.length }})</option>
                                <option v-for="odp in filteredOdps" :key="odp.id" :value="String(odp.id)">
                                    {{ odp.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Teknisi CBP -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-1">Teknisi Ditugaskan</label>
                            <select
                                v-model="form.technician_id"
                                @change="applyFilter"
                                class="w-full py-2 px-2.5 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition cursor-pointer"
                            >
                                <option value="">Semua Teknisi</option>
                                <option v-for="tech in technicians" :key="tech.id" :value="String(tech.id)">
                                    {{ tech.name }} ({{ tech.role || 'Teknisi' }})
                                </option>
                            </select>
                        </div>

                        <!-- Filter Periode Tanggal -->
                        <div class="sm:col-span-2">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300">
                                    Rentang Tanggal
                                </label>
                                <div class="flex items-center gap-1.5 text-[10px]">
                                    <button type="button" @click="setDatePreset('all')" class="text-gray-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold cursor-pointer">Semua</button>
                                    <span class="text-gray-300 dark:text-slate-600">•</span>
                                    <button type="button" @click="setDatePreset('today')" class="text-gray-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold cursor-pointer">Hari Ini</button>
                                    <span class="text-gray-300 dark:text-slate-600">•</span>
                                    <button type="button" @click="setDatePreset('7days')" class="text-gray-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold cursor-pointer">7 Hari</button>
                                    <span class="text-gray-300 dark:text-slate-600">•</span>
                                    <button type="button" @click="setDatePreset('this_month')" class="text-gray-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold cursor-pointer">Bulan Ini</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <select
                                    v-model="form.date_type"
                                    @change="applyFilter"
                                    class="py-2 px-2 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition cursor-pointer"
                                >
                                    <option value="updated">Tgl Stop/Update</option>
                                    <option value="scheduled">Tgl Jadwal CBP</option>
                                    <option value="completed">Tgl Selesai CBP</option>
                                </select>
                                <input
                                    v-model="form.date_from"
                                    type="date"
                                    @change="applyFilter"
                                    class="py-2 px-2 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition"
                                />
                                <input
                                    v-model="form.date_to"
                                    type="date"
                                    @change="applyFilter"
                                    class="py-2 px-2 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition"
                                />
                            </div>
                        </div>

                        <!-- Urutkan (Sorting) -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-1">Urutkan Berdasarkan</label>
                            <select
                                v-model="form.sort_by"
                                @change="applyFilter"
                                class="w-full py-2 px-2.5 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition cursor-pointer"
                            >
                                <option value="latest">Terbaru Dicatat (Default)</option>
                                <option value="oldest">Terlama</option>
                                <option value="name_asc">Nama Pelanggan (A - Z)</option>
                                <option value="name_desc">Nama Pelanggan (Z - A)</option>
                                <option value="code_asc">Kode Pelanggan (CUS-xxx)</option>
                                <option value="odp_name">Nama ODP (A - Z)</option>
                            </select>
                        </div>

                        <!-- Per Page -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-1">Baris Per Halaman</label>
                            <select
                                v-model="form.per_page"
                                @change="applyFilter"
                                class="w-full py-2 px-2.5 bg-gray-50/70 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs text-gray-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-rose-500 transition cursor-pointer"
                            >
                                <option :value="15">15 Baris</option>
                                <option :value="20">20 Baris (Standar)</option>
                                <option :value="25">25 Baris</option>
                                <option :value="50">50 Baris</option>
                                <option :value="100">100 Baris</option>
                            </select>
                        </div>
                    </div>

                    <!-- ═══ 5. ACTIVE FILTER BADGES ROW ═══ -->
                    <div v-if="hasActiveFilters" class="pt-2 border-t border-gray-100 dark:border-slate-800/80 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-gray-500 dark:text-slate-400">Filter Aktif:</span>

                        <!-- Search Badge -->
                        <span v-if="form.search" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-200 border border-gray-200 dark:border-slate-700">
                            Pencarian: "{{ form.search }}"
                            <button type="button" @click="form.search = ''; applyFilter();" class="text-gray-400 hover:text-rose-600 cursor-pointer">&times;</button>
                        </span>

                        <!-- Port Status Badge -->
                        <span v-if="form.port_status !== 'all'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900/60">
                            Port: {{ form.port_status === 'occupied' ? 'Belum Kosong' : form.port_status === 'freed' ? 'Steril / Siap Pakai' : 'Tanpa ONT' }}
                            <button type="button" @click="form.port_status = 'all'; applyFilter();" class="text-rose-400 hover:text-rose-700 cursor-pointer">&times;</button>
                        </span>

                        <!-- CBP Status Badge -->
                        <span v-if="form.status_cbp !== 'all'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900/60">
                            Status: {{ form.status_cbp === 'no_request' ? 'Tanpa Tiket' : form.status_cbp === 'in_process' ? 'Proses Cabut' : form.status_cbp }}
                            <button type="button" @click="form.status_cbp = 'all'; applyFilter();" class="text-indigo-400 hover:text-indigo-700 cursor-pointer">&times;</button>
                        </span>

                        <!-- Area Badge -->
                        <span v-if="form.area_id" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900/60">
                            Wilayah: {{ selectedAreaName }}
                            <button type="button" @click="form.area_id = ''; applyFilter();" class="text-blue-400 hover:text-blue-700 cursor-pointer">&times;</button>
                        </span>

                        <!-- ODC Badge -->
                        <span v-if="form.odc_id" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-900/60">
                            ODC: {{ selectedOdcName }}
                            <button type="button" @click="form.odc_id = ''; applyFilter();" class="text-cyan-400 hover:text-cyan-700 cursor-pointer">&times;</button>
                        </span>

                        <!-- ODP Badge -->
                        <span v-if="form.odp_id" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-900/60">
                            ODP: {{ selectedOdpName }}
                            <button type="button" @click="form.odp_id = ''; applyFilter();" class="text-teal-400 hover:text-teal-700 cursor-pointer">&times;</button>
                        </span>

                        <!-- Technician Badge -->
                        <span v-if="form.technician_id" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60">
                            Teknisi: {{ selectedTechnicianName }}
                            <button type="button" @click="form.technician_id = ''; applyFilter();" class="text-amber-400 hover:text-amber-700 cursor-pointer">&times;</button>
                        </span>

                        <!-- Date Badge -->
                        <span v-if="form.date_from || form.date_to" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/60">
                            Periode: {{ form.date_from || '...' }} s/d {{ form.date_to || '...' }}
                            <button type="button" @click="form.date_from = ''; form.date_to = ''; applyFilter();" class="text-emerald-400 hover:text-emerald-700 cursor-pointer">&times;</button>
                        </span>

                        <!-- Total Hasil Info -->
                        <span class="text-[11px] font-medium text-gray-400 dark:text-slate-500 ml-auto">
                            Ditemukan <strong class="text-gray-700 dark:text-slate-300">{{ customers.total }}</strong> pelanggan
                        </span>
                    </div>
                </div>

                <!-- ═══ 6. TABEL DATA PELANGGAN STOP PERMANEN ═══ -->
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-xs border border-gray-200/80 dark:border-slate-800 rounded-2xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                            <thead class="bg-gray-50/80 dark:bg-slate-800/60">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Pelanggan</th>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Port & ODP</th>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status Proses CBP</th>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Keterangan Port</th>
                                    <th class="px-6 py-3.5 text-right text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-100 dark:divide-slate-800">
                                <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-rose-50/20 dark:hover:bg-slate-800/40 transition-colors">
                                    <!-- Pelanggan -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-950/70 flex items-center justify-center text-rose-700 dark:text-rose-300 font-black text-xs shrink-0 mt-0.5">
                                                {{ customer.name ? customer.name.charAt(0).toUpperCase() : '?' }}
                                            </div>
                                            <div>
                                                <a :href="`/customers/${customer.id}`" class="text-sm font-bold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline">
                                                    {{ customer.name }}
                                                </a>
                                                <div class="text-xs text-gray-500 dark:text-slate-400 font-mono mt-0.5">{{ customer.customer_code }}</div>
                                                <div v-if="customer.phone" class="text-[11px] text-gray-400 dark:text-slate-500 mt-0.5 flex items-center gap-1">
                                                    📞 {{ customer.phone }}
                                                </div>
                                                <div v-if="customer.address" class="text-[11px] text-gray-400 dark:text-slate-500 mt-0.5 line-clamp-1 max-w-xs" :title="customer.address">
                                                    🏠 {{ customer.address }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Port & ODP -->
                                    <td class="px-6 py-4">
                                        <div v-if="customer.ont" class="space-y-1">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/60">
                                                <span>ODP: {{ customer.ont.odp?.name || '-' }}</span>
                                                <span class="text-indigo-300 dark:text-indigo-600">•</span>
                                                <span class="text-rose-600 dark:text-rose-400 font-black">Port {{ customer.ont.port_number || '-' }}</span>
                                            </div>
                                            <div class="text-[11px] text-gray-500 dark:text-slate-400 font-mono">
                                                SN: {{ customer.ont.sn || '-' }}
                                            </div>
                                            <div v-if="customer.ont.odp?.address" class="text-[10px] text-gray-400 dark:text-slate-500 max-w-xs truncate" :title="customer.ont.odp.address">
                                                📍 {{ customer.ont.odp.address }}
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 dark:text-slate-500 italic">Tidak ada port/ONT terkait</span>
                                    </td>

                                    <!-- Status Proses CBP -->
                                    <td class="px-6 py-4">
                                        <div v-if="customer.latest_cbp_request" class="space-y-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <span v-if="customer.latest_cbp_request.status === 'pending'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                    ⏳ Menunggu Jadwal Cabut
                                                </span>
                                                <span v-else-if="customer.latest_cbp_request.status === 'assigned'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 dark:bg-blue-950/70 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                                    🔧 Teknisi Ditugaskan
                                                </span>
                                                <span v-else-if="customer.latest_cbp_request.status === 'completed'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                                    ✅ Selesai Dicabut
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-300 border border-gray-300 dark:border-slate-700">
                                                    {{ customer.latest_cbp_request.status }}
                                                </span>

                                                <span class="text-[10px] font-mono font-bold text-gray-500 dark:text-slate-400">
                                                    {{ customer.latest_cbp_request.cbp_number }}
                                                </span>
                                            </div>

                                            <!-- Detail Teknisi / Jadwal -->
                                            <div v-if="customer.latest_cbp_request.technicians?.length" class="text-xs text-gray-700 dark:text-slate-300">
                                                <span class="font-bold text-gray-600 dark:text-slate-400">Teknisi:</span>
                                                <span class="font-semibold text-indigo-700 dark:text-indigo-400"> {{ customer.latest_cbp_request.technicians.map(t => t.name).join(', ') }}</span>
                                            </div>
                                            <div v-if="customer.latest_cbp_request.scheduled_date" class="text-[11px] text-gray-500 dark:text-slate-400">
                                                📅 Jadwal: {{ customer.latest_cbp_request.scheduled_date }}
                                            </div>
                                        </div>
                                        <div v-else class="space-y-1">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-950/70 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                                                ⚠️ Belum Ada Tiket CBP
                                            </span>
                                            <div class="text-[11px] text-gray-400 dark:text-slate-500 italic">Perlu dibuatkan tiket cabut perangkat</div>
                                        </div>
                                    </td>

                                    <!-- Keterangan Port -->
                                    <td class="px-6 py-4">
                                        <div v-if="customer.latest_cbp_request?.status === 'completed'" class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Perangkat sudah dicabut. Port siap digunakan.
                                        </div>
                                        <div v-else class="text-xs text-rose-700 dark:text-rose-300 font-bold bg-rose-50 dark:bg-rose-950/40 p-2 rounded-xl border border-rose-200 dark:border-rose-900/60 space-y-1">
                                            <div class="flex items-center gap-1 text-[11px]">
                                                <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Port fisik belum kosong
                                            </div>
                                            <div class="text-[10px] font-normal text-rose-800 dark:text-rose-300/80">
                                                Masih dalam proses penarikan ONT oleh teknisi.
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('cbp.jadwal')" class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl border border-indigo-200 dark:border-indigo-800 transition flex items-center gap-1">
                                                Jadwal CBP &rarr;
                                            </Link>
                                            <Link v-if="!customer.latest_cbp_request" :href="route('cbp.index')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition shadow-xs">
                                                Buat CBP
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="customers.data.length === 0">
                                    <td colspan="5" class="px-6 py-14 text-center">
                                        <div class="max-w-md mx-auto space-y-3">
                                            <div class="w-12 h-12 mx-auto rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-gray-400 dark:text-slate-500">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                            </div>
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Tidak Ada Data Pelanggan</h4>
                                            <p class="text-xs text-gray-500 dark:text-slate-400">
                                                <span v-if="hasActiveFilters">Tidak ada pelanggan stop permanen yang cocok dengan kombinasi filter yang Anda pilih.</span>
                                                <span v-else>Tidak ada data pelanggan stop permanen saat ini.</span>
                                            </p>
                                            <button
                                                v-if="hasActiveFilters"
                                                type="button"
                                                @click="resetFilters"
                                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Reset Semua Filter
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ═══ 7. PAGINATION (RESPONSIF & CANGGIH) ═══ -->
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/60 dark:bg-slate-800/40 flex flex-col sm:flex-row items-center justify-between gap-3" v-if="customers.total > 0">
                        <div class="text-xs text-gray-500 dark:text-slate-400">
                            Menampilkan <strong class="text-gray-800 dark:text-white">{{ customers.from || 0 }}</strong> - <strong class="text-gray-800 dark:text-white">{{ customers.to || 0 }}</strong> dari total <strong class="text-gray-800 dark:text-white">{{ customers.total }}</strong> data
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap justify-center" v-if="customers.links && customers.links.length > 3">
                            <template v-for="(link, p) in customers.links" :key="p">
                                <div v-if="link.url === null" class="px-3 py-1.5 text-xs text-gray-400 dark:text-slate-600 bg-gray-100 dark:bg-slate-800/60 rounded-xl cursor-not-allowed select-none" v-html="link.label"></div>
                                <Link v-else :href="link.url" class="px-3 py-1.5 text-xs rounded-xl transition-all cursor-pointer" :class="link.active ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700'" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
