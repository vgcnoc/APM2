<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';
import axios from 'axios';

const props = defineProps({
    chronicCustomers: { type: Array, default: () => [] },
    chronicPaths: { type: Array, default: () => [] },
    socializationVisits: { type: Array, default: () => [] },
    visitSummary: { type: Object, default: () => ({}) },
    kpiSummary: { type: Object, default: () => ({}) },
    areas: { type: Array, default: () => [] },
    availableOfficers: { type: Array, default: () => [] },
    customerOptions: { type: Array, default: () => [] },
    materials: { type: Array, default: () => [] },
    materialTransactions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: true },
});

const { can } = usePermission();

const canViewStats = computed(() => can('chronic_monitoring_card_stats'));
const canFilter = computed(() => can('chronic_monitoring_filter'));
const canTabCustomers = computed(() => can('chronic_monitoring_tab_customers'));
const canTabPaths = computed(() => can('chronic_monitoring_tab_paths'));
const canTabVisits = computed(() => can('chronic_monitoring_tab_visits'));
const canManageVisit = computed(() => can('chronic_socialization_manage'));
const canReportVisit = computed(() => can('chronic_socialization_report') || can('chronic_socialization_manage'));
const canDeleteVisit = computed(() => can('chronic_socialization_delete'));
const canScheduleMaint = computed(() => can('chronic_monitoring_schedule_maintenance') || can('network_maintenance_create'));
const canWhatsapp = computed(() => can('chronic_monitoring_whatsapp'));

const defaultTab = can('chronic_monitoring_tab_customers')
    ? 'customers'
    : can('chronic_monitoring_tab_paths')
      ? 'paths'
      : 'visits';

const activeTab = ref(defaultTab); // 'customers' | 'paths' | 'visits'

// Filter state
const period = ref(props.filters.period || '90_days');
const areaId = ref(props.filters.area_id || '');
const minTickets = ref(props.filters.min_tickets || 1);
const search = ref(props.filters.search || '');
const visitStatus = ref(props.filters.visit_status || 'all');
const myTasks = ref(Boolean(props.filters.my_tasks));

// ── PAGINATION STATE (UNTUK 3 TAB) ─────────────────────────────────────────
const perPage = ref(10);
const custPage = ref(1);
const pathPage = ref(1);
const visitPage = ref(1);

const resetPages = () => {
    custPage.value = 1;
    pathPage.value = 1;
    visitPage.value = 1;
};

const custTotalPages = computed(() => Math.max(1, Math.ceil(props.chronicCustomers.length / perPage.value)));
const pathTotalPages = computed(() => Math.max(1, Math.ceil(props.chronicPaths.length / perPage.value)));
const visitTotalPages = computed(() => Math.max(1, Math.ceil(props.socializationVisits.length / perPage.value)));

const paginatedCustomers = computed(() => {
    const p = Math.min(custPage.value, custTotalPages.value);
    const start = (p - 1) * perPage.value;
    return props.chronicCustomers.slice(start, start + perPage.value);
});

const paginatedPaths = computed(() => {
    const p = Math.min(pathPage.value, pathTotalPages.value);
    const start = (p - 1) * perPage.value;
    return props.chronicPaths.slice(start, start + perPage.value);
});

const paginatedVisits = computed(() => {
    const p = Math.min(visitPage.value, visitTotalPages.value);
    const start = (p - 1) * perPage.value;
    return props.socializationVisits.slice(start, start + perPage.value);
});

const getVisiblePages = (current, total) => {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    const pages = [];
    pages.push(1);
    if (current > 3) pages.push('...');
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
};

const applyFilters = () => {
    resetPages();
    router.get(
        route('operational.chronic-monitoring.index'),
        {
            period: period.value,
            area_id: areaId.value || undefined,
            min_tickets: minTickets.value,
            search: search.value || undefined,
            visit_status: visitStatus.value !== 'all' ? visitStatus.value : undefined,
            my_tasks: myTasks.value ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const setVisitFilter = (statusVal, onlyMine = false) => {
    visitStatus.value = statusVal;
    myTasks.value = onlyMine;
    applyFilters();
};

// ── MODAL 1: JADWALKAN PETUGAS SOSIALISASI KE RUMAH PELANGGAN ─────────────
const showAssignVisitModal = ref(false);
const isSubmittingVisit = ref(false);
const officerSearch = ref('');
const customerSearchModal = ref('');

const visitForm = ref({
    mode: 'single', // 'single' | 'batch'
    customer_id: '',
    customer_name_display: '',
    customer_ids: [],
    batch_label: '',
    purpose_category: 'gangguan_berulang',
    title: '',
    issue_summary: '',
    admin_notes: '',
    scheduled_date: new Date().toISOString().slice(0, 10),
    scheduled_time: '09:00',
    priority: 'high',
    officer_ids: [],
    send_wa_officer: true,
    send_wa_customer: false,
});

const filteredOfficers = computed(() => {
    const q = officerSearch.value.trim().toLowerCase();
    if (!q) return props.availableOfficers;
    return props.availableOfficers.filter(
        (o) =>
            (o.name && o.name.toLowerCase().includes(q)) ||
            (o.role_label && o.role_label.toLowerCase().includes(q))
    );
});

const filteredCustomerOptions = computed(() => {
    const q = customerSearchModal.value.trim().toLowerCase();
    if (!q) return props.customerOptions.slice(0, 50);
    return props.customerOptions
        .filter(
            (c) =>
                (c.name && c.name.toLowerCase().includes(q)) ||
                (c.customer_code && c.customer_code.toLowerCase().includes(q)) ||
                (c.address && c.address.toLowerCase().includes(q))
        )
        .slice(0, 50);
});

const openCreateVisitManual = () => {
    visitForm.value = {
        mode: 'single',
        customer_id: '',
        customer_name_display: '',
        customer_ids: [],
        batch_label: '',
        purpose_category: 'gangguan_berulang',
        title: 'Kunjungan Sosialisasi & Pengecekan Kepuasan Layanan Pelanggan',
        issue_summary: '',
        admin_notes: 'Mohon datangi rumah pelanggan dengan ramah, sampaikan permohonan maaf atas kendala sebelumnya, jelaskan perbaikan yang telah dilakukan, dan pastikan koneksi WiFi di perangkat pelanggan sudah normal.',
        scheduled_date: new Date().toISOString().slice(0, 10),
        scheduled_time: '09:00',
        priority: 'normal',
        officer_ids: [],
        send_wa_officer: true,
        send_wa_customer: false,
    };
    showAssignVisitModal.value = true;
};

const openCreateVisitForCustomer = (cust) => {
    const catList = (cust.top_categories || []).map((c) => `${c.category} (${c.count}x)`).join(', ');
    const summaryText = `Tercatat ${cust.total_tickets}x gangguan (PGK: ${cust.pgk_count}, PGS: ${cust.pgs_count}) dengan keluhan dominan: ${catList || cust.dominant_category}. Redaman terakhir: ${cust.rx_power !== null ? cust.rx_power + ' dBm' : '-'}. Jalur: ${cust.odp_name}.`;

    visitForm.value = {
        mode: 'single',
        customer_id: cust.id,
        customer_name_display: `${cust.name} (${cust.customer_code}) - ${cust.address || ''}`,
        customer_ids: [cust.id],
        batch_label: '',
        purpose_category: 'gangguan_berulang',
        title: `Sosialisasi & Pendampingan Pelanggan Rawan Gangguan (${cust.name})`,
        issue_summary: summaryText,
        admin_notes: `Mohon mendatangi rumah Bpk/Ibu ${cust.name} untuk bersilaturahmi, menyampaikan permohonan maaf atas gangguan berulang (${cust.total_tickets}x), menjelaskan langkah perbaikan permanen yang dilakukan tim teknis, serta mengecek langsung kenyamanan sinyal WiFi di rumah pelanggan.`,
        scheduled_date: new Date().toISOString().slice(0, 10),
        scheduled_time: '09:00',
        priority: cust.severity === 'critical' ? 'urgent' : 'high',
        officer_ids: [],
        send_wa_officer: true,
        send_wa_customer: false,
    };
    showAssignVisitModal.value = true;
};

const openBatchVisitForPath = (pathNode) => {
    const custIds = (pathNode.affected_customers || []).map((c) => c.id);
    if (!custIds.length) {
        alert('Belum ada daftar pelanggan spesifik pada titik jalur ini.');
        return;
    }

    visitForm.value = {
        mode: 'batch',
        customer_id: '',
        customer_name_display: '',
        customer_ids: custIds,
        batch_label: `${custIds.length} Pelanggan Terdampak di ${pathNode.name} (${pathNode.area_name})`,
        purpose_category: 'pasca_perbaikan_jalur',
        title: `Sosialisasi Pelanggan Terdampak Jalur ${pathNode.name}`,
        issue_summary: `Jalur ${pathNode.name} mengalami ${pathNode.total_incidents} insiden (${pathNode.pgj_count} PGJ, ${pathNode.customer_ticket_count} PGK/PGS) yang berdampak pada ${custIds.length} pelanggan.`,
        admin_notes: `Kunjungi rumah pelanggan di jalur ${pathNode.name} untuk menginformasikan peremajaan/perbaikan jalur distribusi ODP serta memastikan layanan internet pelanggan sudah kembali stabil.`,
        scheduled_date: new Date().toISOString().slice(0, 10),
        scheduled_time: '09:30',
        priority: 'high',
        officer_ids: [],
        send_wa_officer: true,
        send_wa_customer: false,
    };
    showAssignVisitModal.value = true;
};

const toggleOfficerSelection = (id) => {
    const idx = visitForm.value.officer_ids.indexOf(id);
    if (idx >= 0) {
        visitForm.value.officer_ids.splice(idx, 1);
    } else {
        visitForm.value.officer_ids.push(id);
    }
};

const submitAssignVisit = () => {
    if (visitForm.value.mode === 'single' && !visitForm.value.customer_id) {
        alert('Silakan pilih pelanggan yang akan dikunjungi.');
        return;
    }
    if (!visitForm.value.officer_ids.length) {
        alert('Pilih minimal 1 petugas yang akan mendatangi rumah pelanggan.');
        return;
    }

    isSubmittingVisit.value = true;
    router.post(
        route('operational.chronic-monitoring.visits.store'),
        {
            customer_id: visitForm.value.mode === 'single' ? visitForm.value.customer_id : null,
            customer_ids: visitForm.value.mode === 'batch' ? visitForm.value.customer_ids : null,
            purpose_category: visitForm.value.purpose_category,
            title: visitForm.value.title,
            issue_summary: visitForm.value.issue_summary,
            admin_notes: visitForm.value.admin_notes,
            scheduled_date: visitForm.value.scheduled_date,
            scheduled_time: visitForm.value.scheduled_time,
            priority: visitForm.value.priority,
            officer_ids: visitForm.value.officer_ids,
            send_wa_officer: visitForm.value.send_wa_officer,
            send_wa_customer: visitForm.value.send_wa_customer,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showAssignVisitModal.value = false;
                activeTab.value = 'visits';
            },
            onFinish: () => {
                isSubmittingVisit.value = false;
            },
        }
    );
};

// ── MODAL 2: ISI LAPORAN HASIL KUNJUNGAN SOSIALISASI (OLEH PETUGAS) ──────
const showReportVisitModal = ref(false);
const isSubmittingReport = ref(false);
const selectedVisit = ref(null);
const photoPreviewUrl = ref(null);
const isGettingGps = ref(false);

const defaultChecklistOptions = [
    'Menyampaikan permohonan maaf & empati langsung kepada pelanggan di rumah',
    'Menjelaskan penyebab gangguan & tindakan perbaikan yang telah dilakukan tim',
    'Mengecek langsung sinyal WiFi, posisi ONT/Router & redaman di rumah pelanggan',
    'Memberikan edukasi penggunaan perangkat & nomor kontak pengaduan prioritas',
    'Memastikan pelanggan sudah kembali nyaman menggunakan layanan internet',
];

const reportForm = ref({
    status: 'completed',
    scheduled_date: '',
    scheduled_time: '',
    start_time: '',
    end_time: '',
    met_with_name: '',
    met_with_relation: 'Pelanggan Langsung',
    customer_response: 'puas_memahami',
    socialization_points: [],
    report_notes: '',
    follow_up_action: '',
    latitude: '',
    longitude: '',
    photo_visit: null,
});

const openReportModal = (visit) => {
    selectedVisit.value = visit;
    const nowTime = new Date().toTimeString().slice(0, 5);
    reportForm.value = {
        status: visit.status === 'scheduled' ? 'completed' : visit.status,
        scheduled_date: visit.scheduled_date || new Date().toISOString().slice(0, 10),
        scheduled_time: visit.scheduled_time || '09:00',
        start_time: visit.start_time || nowTime,
        end_time: visit.end_time || nowTime,
        met_with_name: visit.met_with_name || visit.customer_name || '',
        met_with_relation: visit.met_with_relation || 'Pelanggan Langsung',
        customer_response: visit.customer_response || 'puas_memahami',
        socialization_points:
            Array.isArray(visit.socialization_points) && visit.socialization_points.length
                ? [...visit.socialization_points]
                : [
                      'Menyampaikan permohonan maaf & empati langsung kepada pelanggan di rumah',
                      'Menjelaskan penyebab gangguan & tindakan perbaikan yang telah dilakukan tim',
                      'Mengecek langsung sinyal WiFi, posisi ONT/Router & redaman di rumah pelanggan',
                  ],
        report_notes: visit.report_notes || '',
        follow_up_action: visit.follow_up_action || '',
        latitude: visit.latitude || '',
        longitude: visit.longitude || '',
        photo_visit: null,
    };
    photoPreviewUrl.value = visit.photo_visit_urls?.[0] || null;
    showReportVisitModal.value = true;
};

const toggleSocializationPoint = (pt) => {
    const idx = reportForm.value.socialization_points.indexOf(pt);
    if (idx >= 0) {
        reportForm.value.socialization_points.splice(idx, 1);
    } else {
        reportForm.value.socialization_points.push(pt);
    }
};

const onVisitPhotoChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    reportForm.value.photo_visit = file;
    photoPreviewUrl.value = URL.createObjectURL(file);
};

const captureGpsLocation = () => {
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung GPS Geolocation.');
        return;
    }
    isGettingGps.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            reportForm.value.latitude = pos.coords.latitude.toFixed(6);
            reportForm.value.longitude = pos.coords.longitude.toFixed(6);
            isGettingGps.value = false;
        },
        (err) => {
            alert('Gagal mengambil titik GPS: ' + err.message);
            isGettingGps.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const submitVisitReport = () => {
    if (!selectedVisit.value) return;
    isSubmittingReport.value = true;

    const formData = new FormData();
    formData.append('status', reportForm.value.status);
    formData.append('scheduled_date', reportForm.value.scheduled_date || '');
    formData.append('scheduled_time', reportForm.value.scheduled_time || '');
    formData.append('start_time', reportForm.value.start_time || '');
    formData.append('end_time', reportForm.value.end_time || '');
    formData.append('met_with_name', reportForm.value.met_with_name || '');
    formData.append('met_with_relation', reportForm.value.met_with_relation || '');
    formData.append('customer_response', reportForm.value.customer_response || '');
    formData.append('socialization_points', JSON.stringify(reportForm.value.socialization_points || []));
    formData.append('report_notes', reportForm.value.report_notes || '');
    formData.append('follow_up_action', reportForm.value.follow_up_action || '');
    if (reportForm.value.latitude) formData.append('latitude', reportForm.value.latitude);
    if (reportForm.value.longitude) formData.append('longitude', reportForm.value.longitude);
    if (reportForm.value.photo_visit) {
        formData.append('photo_visit', reportForm.value.photo_visit);
    }

    router.post(
        route('operational.chronic-monitoring.visits.report', selectedVisit.value.id),
        formData,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showReportVisitModal.value = false;
            },
            onFinish: () => {
                isSubmittingReport.value = false;
            },
        }
    );
};

const resendOfficerWa = (visit) => {
    router.post(
        route('operational.chronic-monitoring.visits.notify-wa', visit.id),
        {},
        { preserveScroll: true }
    );
};

const deleteVisitItem = (visit) => {
    if (!confirm(`Hapus jadwal kunjungan sosialisasi ${visit.visit_number} (${visit.customer_name})?`)) return;
    router.delete(route('operational.chronic-monitoring.visits.destroy', visit.id), {
        preserveScroll: true,
    });
};

// ── MODAL 3: JADWALKAN PEMELIHARAAN JALUR ODP/ODC LANGSUNG + PERSIAPAN MATERIAL ──
const showMaintenanceModal = ref(false);
const isSubmittingMaint = ref(false);
const showAllMaintOfficers = ref(false);
const selectedMaintNode = ref(null);

const maintForm = ref({
    category: 'pengecekan_odp',
    title: '',
    area_id: '',
    odp_id: '',
    odc_id: '',
    location_name: '',
    scheduled_date: new Date().toISOString().slice(0, 10),
    scheduled_time: '09:00',
    priority: 'high',
    description: '',
    technician_ids: [],
    materials: [],
    send_wa_technician: true,
});

const filteredMaintOfficers = computed(() => {
    if (showAllMaintOfficers.value || !maintForm.value.area_id) {
        return props.availableOfficers;
    }
    const scoped = props.availableOfficers.filter(
        (o) => !o.area_id || Number(o.area_id) === Number(maintForm.value.area_id) || maintForm.value.technician_ids.includes(o.id)
    );
    return scoped.length > 0 ? scoped : props.availableOfficers;
});

// Daftar Material Persiapan Gudang (Bekal Petugas Pemeliharaan Jalur)
const availableMaintMaterialItems = computed(() => {
    const items = [];
    const targetArea = maintForm.value.area_id;
    const handledMaterialIds = new Set();

    // 1. Dari Order Toko / Restock Area
    const trxs = props.materialTransactions || [];
    trxs.forEach((trx) => {
        if (targetArea && trx.area_id && Number(trx.area_id) !== Number(targetArea)) return;
        if (trx.items && trx.items.length > 0) {
            trx.items.forEach((item) => {
                if (!item.material) return;
                const name = (item.material.name || '').toLowerCase();
                const category = (item.material.category || '').toLowerCase();
                const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;

                if (!isRegistered && !name.includes('modem') && !category.includes('modem') && !name.includes('ont') && !category.includes('ont')) {
                    const remainingInOrder =
                        typeof item.remaining_quantity !== 'undefined'
                            ? parseFloat(item.remaining_quantity)
                            : parseFloat(item.quantity);

                    let areaStock = null;
                    if (item.material.stocks && targetArea) {
                        const stockObj = item.material.stocks.find((s) => Number(s.area_id) === Number(targetArea));
                        if (stockObj) {
                            areaStock = parseFloat(stockObj.stock) || 0;
                        }
                    }

                    let availableQty = remainingInOrder;
                    if (areaStock !== null && areaStock < availableQty) {
                        availableQty = areaStock;
                    }

                    if (availableQty > 0) {
                        handledMaterialIds.add(item.material_id);
                        items.push({
                            unique_key: 'ord-' + item.id,
                            id: item.id,
                            material_id: item.material_id,
                            trx_number: trx.transaction_number,
                            trx_date: trx.date,
                            purpose: trx.purpose,
                            area_name: trx.area_model?.name || trx.area || 'Order Toko Area',
                            name: item.material.name,
                            category: item.material.category,
                            area_stock: availableQty,
                            unit: item.unit || item.material.unit || 'pcs',
                            material: item.material,
                        });
                    }
                }
            });
        }
    });

    // 2. Dari Inventory Gudang Area
    const allMaterials = props.materials || [];
    allMaterials.forEach((m) => {
        const name = (m.name || '').toLowerCase();
        const category = (m.category || '').toLowerCase();
        if (name.includes('modem') || category.includes('modem') || name.includes('ont') || category.includes('ont')) {
            return;
        }

        let areaStock = 0;
        let hasAreaStock = false;
        if (m.stocks && targetArea) {
            const stockObj = m.stocks.find((s) => Number(s.area_id) === Number(targetArea));
            if (stockObj) {
                hasAreaStock = true;
                areaStock = parseFloat(stockObj.stock) || 0;
            }
        }

        if (targetArea) {
            if (!hasAreaStock || areaStock <= 0) return;
        } else {
            areaStock = parseFloat(m.stock) || 0;
            if (areaStock <= 0) return;
        }

        if (!handledMaterialIds.has(m.id) && areaStock > 0) {
            items.push({
                unique_key: 'mat-' + m.id,
                id: 'mat-' + m.id,
                material_id: m.id,
                trx_number: '',
                area_name: 'Stok Gudang Area',
                name: m.name,
                category: m.category,
                area_stock: areaStock,
                unit: m.unit || 'pcs',
                material: m,
            });
        }
    });

    return items.filter((item) => (parseFloat(item.area_stock) || 0) > 0);
});

const addMaintMaterialRow = () => {
    if (!maintForm.value.materials) {
        maintForm.value.materials = [];
    }
    maintForm.value.materials.push({
        selected_key: '',
        material_transaction_item_id: null,
        material_id: '',
        trx_number: '',
        name: '',
        quantity: 1,
        unit: 'pcs',
        max_stock: null,
        notes: '',
    });
};

const removeMaintMaterialRow = (index) => {
    maintForm.value.materials.splice(index, 1);
};

const onMaintMaterialChange = (item) => {
    const selectedKey = item.selected_key;
    const selectedOrder = availableMaintMaterialItems.value.find(
        (i) => i.unique_key === selectedKey || i.id === selectedKey || i.material_id === selectedKey
    );
    if (selectedOrder) {
        item.material_id = selectedOrder.material_id;
        item.trx_number = selectedOrder.trx_number || '';
        item.name = selectedOrder.name;
        item.unit = selectedOrder.unit;
        item.max_stock = selectedOrder.area_stock;
        item.material_transaction_item_id = selectedOrder.unique_key?.startsWith('ord-')
            ? selectedOrder.id || null
            : null;

        if (item.quantity && item.quantity > selectedOrder.area_stock) {
            item.quantity = selectedOrder.area_stock;
        } else if (!item.quantity || item.quantity <= 0) {
            item.quantity = 1;
        }
    }
};

const validateMaintMaterialQty = (item) => {
    if (item.max_stock !== undefined && item.max_stock !== null) {
        if (item.quantity > item.max_stock) {
            item.quantity = item.max_stock;
        } else if (item.quantity < 0.01) {
            item.quantity = 0.01;
        }
    }
};

const openScheduleMaintenanceModal = (pathNode) => {
    selectedMaintNode.value = pathNode;
    showAllMaintOfficers.value = false;
    maintForm.value = {
        category: pathNode.node_type === 'ODC' ? 'pengecekan_odc' : 'pengecekan_odp',
        title: `Peremajaan & Pengecekan ${pathNode.name} (Rawan Gangguan ${pathNode.total_incidents}x)`,
        area_id: pathNode.area_id || '',
        odp_id: pathNode.odp_id || '',
        odc_id: pathNode.odc_id || '',
        location_name: pathNode.address !== '-' ? pathNode.address : pathNode.name,
        scheduled_date: new Date().toISOString().slice(0, 10),
        scheduled_time: '09:00',
        priority: pathNode.severity === 'critical' ? 'urgent' : 'high',
        description: `${pathNode.diagnosis} Mohon cek konektor, adapter splitter, closure, dan redaman jalur distribusi pada ${pathNode.name}.`,
        technician_ids: [],
        materials: [],
        send_wa_technician: true,
    };
    showMaintenanceModal.value = true;
};

const toggleMaintTech = (id) => {
    const idx = maintForm.value.technician_ids.indexOf(id);
    if (idx >= 0) {
        maintForm.value.technician_ids.splice(idx, 1);
    } else {
        maintForm.value.technician_ids.push(id);
    }
};

const openNetworkMaintenancePage = (maint) => {
    if (!maint) {
        router.visit('/operational/network-maintenance');
        return;
    }
    router.visit('/operational/network-maintenance', {
        data: {
            date: maint.scheduled_date_raw || new Date().toISOString().slice(0, 10),
            search: maint.maintenance_number || '',
        },
    });
};

const submitScheduleMaintenance = async () => {
    if (!maintForm.value.title) {
        alert('Judul pemeliharaan jalur wajib diisi.');
        return;
    }
    isSubmittingMaint.value = true;
    try {
        const validMaterials = (maintForm.value.materials || [])
            .filter((m) => m.material_id && parseFloat(m.quantity) > 0)
            .map((m) => ({
                material_id: m.material_id,
                material_transaction_item_id: m.material_transaction_item_id || null,
                trx_number: m.trx_number || null,
                quantity_prepared: parseFloat(m.quantity) || 1,
                quantity: parseFloat(m.quantity) || 1,
                unit: m.unit || 'pcs',
                notes: m.notes || '',
            }));

        const payload = {
            ...maintForm.value,
            materials: validMaterials,
        };

        const res = await axios.post(route('operational.network-maintenance.store'), payload);
        if (res.data?.success) {
            showMaintenanceModal.value = false;
            router.reload({ preserveScroll: true });
        } else {
            alert(res.data?.message || 'Gagal menjadwalkan pemeliharaan jalur.');
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Terjadi kesalahan saat menyimpan jadwal pemeliharaan jalur.');
    } finally {
        isSubmittingMaint.value = false;
    }
};

// ── MODAL 4: DETAIL TIMELINE TIKET PELANGGAN / ODP ──────────────────────
const showTimelineModal = ref(false);
const timelineTarget = ref(null);
const openCustomerTimeline = (cust) => {
    timelineTarget.value = {
        type: 'customer',
        title: `${cust.name} (${cust.customer_code})`,
        subtitle: `ODP: ${cust.odp_name} | Total ${cust.total_tickets}x Gangguan`,
        recommendation: cust.recommendation,
        replaced_items: cust.replaced_items_summary || [],
        tickets: cust.tickets_timeline || [],
        customer: cust,
    };
    showTimelineModal.value = true;
};

const openPathTimeline = (pathNode) => {
    timelineTarget.value = {
        type: 'path',
        title: `${pathNode.name} (${pathNode.area_name})`,
        subtitle: `${pathNode.parent_name} | ${pathNode.affected_customers_count} Pelanggan Terdampak (${pathNode.total_incidents} Insiden)`,
        recommendation: pathNode.diagnosis,
        affected_customers: pathNode.affected_customers || [],
        tickets: pathNode.recent_tickets || [],
        pathNode,
    };
    showTimelineModal.value = true;
};

// Photo Lightbox
const lightboxUrl = ref(null);

const formatWaUrl = (phone, customText = '') => {
    if (!phone) return '#';
    let clean = String(phone).replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) clean = '62' + clean.slice(1);
    const textParam = customText ? `?text=${encodeURIComponent(customText)}` : '';
    return `https://wa.me/${clean}${textParam}`;
};
</script>

<template>
    <Head title="Monitoring Rawan Gangguan & Sosialisasi Pelanggan" />

    <AppLayout
        title="Monitoring Pelanggan & Jalur Rawan Gangguan"
        subtitle="Deteksi dini pelanggan & jalur ODP/ODC sering gangguan beserta penugasan petugas sosialisasi ke rumah pelanggan"
    >
        <div class="space-y-6">
            <!-- 1. KPI CARDS -->
            <div v-if="canViewStats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Pelanggan Sering Gangguan -->
                <div
                    @click="canTabCustomers && (activeTab = 'customers')"
                    class="cursor-pointer bg-white dark:bg-slate-900 rounded-2xl p-4 border border-rose-200/80 dark:border-rose-900/50 shadow-sm hover:shadow-md transition"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                                Pelanggan Rawan / Kronis
                            </p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">
                                    {{ kpiSummary.chronic_customers_critical + kpiSummary.chronic_customers_warning }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    dari {{ kpiSummary.total_customers_with_issues }} pelanggan
                                </span>
                            </div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold">
                            ⚠️
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-[11px] font-semibold">
                        <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                            🔴 {{ kpiSummary.chronic_customers_critical }} Kronis (≥3x)
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                            🟠 {{ kpiSummary.chronic_customers_warning }} Rawan (2x)
                        </span>
                    </div>
                </div>

                <!-- Card 2: Wajib Dikunjungi Sosialisasi -->
                <div
                    @click="activeTab = 'customers'"
                    class="cursor-pointer bg-white dark:bg-slate-900 rounded-2xl p-4 border border-amber-200/80 dark:border-amber-900/50 shadow-sm hover:shadow-md transition"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                Belum Dikunjungi Sosialisasi
                            </p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">
                                    {{ kpiSummary.unvisited_repeat_customers }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    pelanggan berulang (≥2x)
                                </span>
                            </div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                            🏠
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                        Pelanggan gangguan berulang yang perlu didatangi petugas untuk sosialisasi & edukasi.
                    </p>
                </div>

                <!-- Card 3: Jalur / ODP / ODC Rawan -->
                <div
                    @click="activeTab = 'paths'"
                    class="cursor-pointer bg-white dark:bg-slate-900 rounded-2xl p-4 border border-purple-200/80 dark:border-purple-900/50 shadow-sm hover:shadow-md transition"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                                Jalur / ODP / ODC Rawan
                            </p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">
                                    {{ kpiSummary.total_paths_with_issues }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    titik distribusi
                                </span>
                            </div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-purple-100 dark:bg-purple-950/80 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                            🔌
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-[11px] font-semibold">
                        <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                            🔴 {{ kpiSummary.chronic_paths_critical }} ODP/ODC Kritis
                        </span>
                        <span class="text-slate-500 dark:text-slate-400">Perlu peremajaan jalur</span>
                    </div>
                </div>

                <!-- Card 4: Kunjungan Sosialisasi Rumah -->
                <div
                    @click="activeTab = 'visits'"
                    class="cursor-pointer bg-white dark:bg-slate-900 rounded-2xl p-4 border border-emerald-200/80 dark:border-emerald-900/50 shadow-sm hover:shadow-md transition"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                Kunjungan Sosialisasi Rumah
                            </p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">
                                    {{ kpiSummary.visits_completed }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    selesai dikunjungi
                                </span>
                            </div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            🤝
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-[11px] font-semibold">
                        <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                            📅 {{ kpiSummary.visits_pending }} Menunggu Kunjungan
                        </span>
                        <span v-if="visitSummary.my_tasks > 0" class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            👤 {{ visitSummary.my_tasks }} Tugas Saya
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. FILTER BAR & QUICK ACTION -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div v-if="canFilter || canManageVisit" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <div v-if="canFilter" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 flex-1">
                        <!-- Periode -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                                Periode Pantauan
                            </label>
                            <select
                                v-model="period"
                                @change="applyFilters"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-semibold py-2 px-3"
                            >
                                <option value="7_days">7 Hari Terakhir</option>
                                <option value="30_days">30 Hari Terakhir</option>
                                <option value="90_days">90 Hari Terakhir (3 Bulan)</option>
                                <option value="this_month">Bulan Ini</option>
                                <option value="all">Semua Riwayat Waktu</option>
                            </select>
                        </div>

                        <!-- Area -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                                Wilayah / Area
                            </label>
                            <select
                                v-model="areaId"
                                @change="applyFilters"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-semibold py-2 px-3"
                            >
                                <option value="">Semua Wilayah</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">
                                    {{ a.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Minimum Frekuensi -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                                Frekuensi Gangguan
                            </label>
                            <select
                                v-model="minTickets"
                                @change="applyFilters"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-semibold py-2 px-3"
                            >
                                <option :value="1">Semua Pernah Gangguan (≥ 1x)</option>
                                <option :value="2">Sering / Berulang (≥ 2x)</option>
                                <option :value="3">Kronis / Kritis (≥ 3x)</option>
                            </select>
                        </div>

                        <!-- Pencarian -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">
                                Cari Pelanggan / ODP / Petugas
                            </label>
                            <div class="flex gap-1.5">
                                <input
                                    v-model="search"
                                    @keyup.enter="applyFilters"
                                    type="text"
                                    placeholder="Nama, ID, ODP, Keluhan..."
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                                />
                                <button
                                    type="button"
                                    @click="applyFilters"
                                    class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold shrink-0 cursor-pointer"
                                >
                                    Cari
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Utus Petugas Sosialisasi Manual -->
                    <div v-if="canManageVisit" class="flex items-center gap-2 shrink-0 pt-2 lg:pt-4">
                        <button
                            type="button"
                            @click="openCreateVisitManual"
                            class="w-full lg:w-auto px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-orange-600 hover:from-rose-500 hover:to-orange-500 text-white text-xs font-bold shadow-md shadow-rose-500/20 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>🤝</span>
                            <span>+ Jadwalkan Petugas Sosialisasi</span>
                        </button>
                    </div>
                </div>

                <!-- 3. NAVIGATION TABS & PER-PAGE SELECTOR -->
                <div class="flex flex-wrap items-center justify-between gap-3 mt-4 pt-4 border-t border-slate-200/70 dark:border-slate-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="canTabCustomers"
                            type="button"
                            @click="activeTab = 'customers'"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer',
                                activeTab === 'customers'
                                    ? 'bg-rose-600 text-white shadow-sm'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
                            ]"
                        >
                            <span>👥 Pelanggan Sering Gangguan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab === 'customers' ? 'bg-white/25 text-white' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'">
                                {{ chronicCustomers.length }}
                            </span>
                        </button>

                        <button
                            v-if="canTabPaths"
                            type="button"
                            @click="activeTab = 'paths'"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer',
                                activeTab === 'paths'
                                    ? 'bg-purple-600 text-white shadow-sm'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
                            ]"
                        >
                            <span>🔌 Jalur / ODP / ODC Sering Gangguan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab === 'paths' ? 'bg-white/25 text-white' : 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300'">
                                {{ chronicPaths.length }}
                            </span>
                        </button>

                        <button
                            v-if="canTabVisits"
                            type="button"
                            @click="activeTab = 'visits'"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer',
                                activeTab === 'visits'
                                    ? 'bg-emerald-600 text-white shadow-sm'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
                            ]"
                        >
                            <span>🏠 Kunjungan Sosialisasi Rumah Pelanggan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab === 'visits' ? 'bg-white/25 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'">
                                {{ socializationVisits.length }}
                            </span>
                        </button>
                    </div>

                    <!-- Per-Page Selector -->
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold">Tampilkan:</span>
                        <select
                            v-model="perPage"
                            @change="resetPages"
                            class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold py-1.5 px-2.5"
                        >
                            <option :value="10">10 / halaman</option>
                            <option :value="20">20 / halaman</option>
                            <option :value="50">50 / halaman</option>
                            <option :value="100">100 / halaman</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: PELANGGAN SERING GANGGUAN                          -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'customers'" class="space-y-4">
                <div v-if="chronicCustomers.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl p-10 text-center border border-slate-200 dark:border-slate-800">
                    <p class="text-base font-bold text-slate-700 dark:text-slate-200">Tidak ada data pelanggan gangguan pada filter ini</p>
                    <p class="text-xs text-slate-500 mt-1">Coba ubah filter Periode ke "90 Hari Terakhir" atau "Semua Riwayat Waktu", atau turunkan Frekuensi ke ≥ 1x.</p>
                </div>

                <div
                    v-for="cust in paginatedCustomers"
                    :key="cust.id"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border shadow-sm transition hover:shadow-md"
                    :class="{
                        'border-rose-300 dark:border-rose-800/80': cust.severity === 'critical',
                        'border-amber-300 dark:border-amber-800/80': cust.severity === 'warning',
                        'border-slate-200 dark:border-slate-800': cust.severity === 'normal',
                    }"
                >
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <!-- Left Info -->
                        <div class="space-y-2.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="cust.severity === 'critical'"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-600 text-white"
                                >
                                    🔴 KRONIS ({{ cust.total_tickets }}x Gangguan)
                                </span>
                                <span
                                    v-else-if="cust.severity === 'warning'"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500 text-white"
                                >
                                    🟠 RAWAN ({{ cust.total_tickets }}x Gangguan)
                                </span>
                                <span
                                    v-else
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                                >
                                    🔵 {{ cust.total_tickets }}x Gangguan
                                </span>

                                <span class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                                    {{ cust.name }}
                                </span>
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-mono font-bold">
                                    {{ cust.customer_code }}
                                </span>
                                <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 text-xs font-semibold">
                                    {{ cust.area_name }}
                                </span>
                                <span
                                    v-if="cust.open_tickets_count > 0"
                                    class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300 text-[11px] font-bold animate-pulse"
                                >
                                    ⚡ {{ cust.open_tickets_count }} Tiket Masih Aktif
                                </span>
                            </div>

                            <!-- Alamat & Jalur ODP -->
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-400">
                                <span>📍 {{ cust.address || 'Alamat belum lengkap' }}</span>
                                <span v-if="cust.phone">
                                    📞 <a :href="formatWaUrl(cust.phone)" target="_blank" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">{{ cust.phone }}</a>
                                </span>
                                <span>📦 Paket: <strong>{{ cust.package_name }}</strong></span>
                            </div>

                            <!-- Info Teknis ODP, ONT, Redaman, & Pergantian Perangkat -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span class="px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-xs font-semibold border border-purple-200/60 dark:border-purple-800/50">
                                    🔌 ODP: <strong>{{ cust.odp_name }}</strong> {{ cust.odp_port ? `(Port ${cust.odp_port})` : '' }}
                                </span>
                                <span
                                    v-if="cust.rx_power !== null"
                                    :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-bold border',
                                        cust.is_bad_rx
                                            ? 'bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-800'
                                            : 'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                                    ]"
                                >
                                    📶 Redaman: {{ cust.rx_power }} dBm {{ cust.is_bad_rx ? '(RAWAN!)' : '(Normal)' }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    🎫 PGK: <strong>{{ cust.pgk_count }}</strong> | PGS: <strong>{{ cust.pgs_count }}</strong>
                                </span>
                                <span
                                    v-if="cust.ont_replaced_count > 0"
                                    class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold border border-amber-200"
                                >
                                    🔄 Ganti ONT: {{ cust.ont_replaced_count }}x
                                </span>
                                <span
                                    v-if="cust.material_replaced_count > 0"
                                    class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200"
                                >
                                    🛠️ Ganti Material: {{ cust.material_replaced_count }}x
                                </span>
                            </div>

                            <!-- Kategori Keluhan Berulang -->
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span class="text-[11px] font-bold text-slate-500">Keluhan Dominan:</span>
                                <span
                                    v-for="(cat, idx) in cust.top_categories"
                                    :key="idx"
                                    class="px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 text-[11px] font-semibold"
                                >
                                    {{ cat.category }} ({{ cat.count }}x)
                                </span>
                                <span class="text-[11px] text-slate-400 ml-1">
                                    • Gangguan Terakhir: {{ cust.last_ticket_at }}
                                </span>
                            </div>

                            <!-- Rekomendasi Analisa Sistem -->
                            <div class="p-2.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-800/50 text-xs text-amber-900 dark:text-amber-200">
                                <strong>💡 Diagnosa & Saran Perbaikan:</strong> {{ cust.recommendation }}
                            </div>

                            <!-- Status Kunjungan Sosialisasi Rumah Pelanggan -->
                            <div class="pt-1">
                                <div
                                    v-if="cust.latest_visit"
                                    class="p-2.5 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                                    :class="
                                        cust.latest_visit.status === 'completed'
                                            ? 'bg-emerald-50/70 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-200'
                                            : 'bg-sky-50/70 dark:bg-sky-950/30 border-sky-200 dark:border-sky-800/60 text-sky-900 dark:text-sky-200'
                                    "
                                >
                                    <div>
                                        <span class="font-extrabold">
                                            {{ cust.latest_visit.status === 'completed' ? '✅ Sudah Dikunjungi Sosialisasi:' : '📅 Terjadwal Kunjungan Sosialisasi:' }}
                                        </span>
                                        <span class="ml-1">
                                            {{ cust.latest_visit.scheduled_date }} oleh <strong>{{ cust.latest_visit.officers }}</strong>
                                        </span>
                                        <span v-if="cust.latest_visit.met_with_name" class="ml-1">
                                            (Bertemu: <strong>{{ cust.latest_visit.met_with_name }}</strong> — {{ cust.latest_visit.response_label }})
                                        </span>
                                        <p v-if="cust.latest_visit.report_notes" class="text-[11px] opacity-85 mt-0.5">
                                            "{{ cust.latest_visit.report_notes }}"
                                        </p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase shrink-0 bg-white/70 dark:bg-slate-800">
                                        {{ cust.latest_visit.visit_number }}
                                    </span>
                                </div>
                                <div
                                    v-else-if="cust.total_tickets >= 2"
                                    class="px-3 py-2 rounded-xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-800/50 text-xs text-rose-700 dark:text-rose-300 font-semibold flex items-center gap-2"
                                >
                                    <span>🏠</span>
                                    <span>Belum ada petugas yang mendatangi rumah pelanggan ini untuk sosialisasi pasca gangguan berulang.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Action Buttons -->
                        <div class="flex flex-row lg:flex-col gap-2 shrink-0">
                            <button
                                v-if="canManageVisit"
                                type="button"
                                @click="openCreateVisitForCustomer(cust)"
                                class="flex-1 lg:flex-none px-3.5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>🤝</span>
                                <span>Utus Petugas Sosialisasi</span>
                            </button>

                            <button
                                type="button"
                                @click="openCustomerTimeline(cust)"
                                class="flex-1 lg:flex-none px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>📋</span>
                                <span>Riwayat {{ cust.total_tickets }} Tiket</span>
                            </button>

                            <a
                                v-if="cust.phone && canWhatsapp"
                                :href="formatWaUrl(cust.phone, `Halo Bapak/Ibu ${cust.name}, kami dari tim layanan pelanggan ingin menanyakan apakah koneksi internet Bapak/Ibu saat ini sudah berjalan lancar dan stabil?`)"
                                target="_blank"
                                class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-bold flex items-center justify-center gap-1.5"
                            >
                                <span>💬</span>
                                <span>Sapa via WA</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- PAGINATION TAB 1: PELANGGAN -->
                <div
                    v-if="chronicCustomers.length > 0"
                    class="bg-white dark:bg-slate-900 rounded-2xl px-4 py-3.5 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm"
                >
                    <p class="text-xs text-slate-600 dark:text-slate-400 font-semibold">
                        Menampilkan
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ (custPage - 1) * perPage + 1 }}</span>
                        -
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ Math.min(custPage * perPage, chronicCustomers.length) }}</span>
                        dari
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ chronicCustomers.length }}</span>
                        pelanggan
                    </p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            :disabled="custPage <= 1"
                            @click="custPage = Math.max(1, custPage - 1)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                        >
                            ‹ Sebelumnya
                        </button>
                        <template v-for="(p, idx) in getVisiblePages(custPage, custTotalPages)" :key="idx">
                            <span v-if="p === '...'" class="px-2 text-xs text-slate-400">...</span>
                            <button
                                v-else
                                type="button"
                                @click="custPage = p"
                                :class="[
                                    'w-8 h-8 rounded-xl text-xs font-extrabold transition cursor-pointer',
                                    custPage === p
                                        ? 'bg-rose-600 text-white shadow-sm'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'
                                ]"
                            >
                                {{ p }}
                            </button>
                        </template>
                        <button
                            type="button"
                            :disabled="custPage >= custTotalPages"
                            @click="custPage = Math.min(custTotalPages, custPage + 1)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                        >
                            Berikutnya ›
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: JALUR / ODP / ODC SERING GANGGUAN                  -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'paths'" class="space-y-4">
                <div v-if="chronicPaths.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl p-10 text-center border border-slate-200 dark:border-slate-800">
                    <p class="text-base font-bold text-slate-700 dark:text-slate-200">Tidak ada jalur ODP/ODC bermasalah pada filter ini</p>
                    <p class="text-xs text-slate-500 mt-1">Coba pilih periode waktu yang lebih panjang atau turunkan batas minimum frekuensi gangguan.</p>
                </div>

                <div
                    v-for="pathNode in paginatedPaths"
                    :key="pathNode.node_key"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border shadow-sm transition hover:shadow-md"
                    :class="{
                        'border-rose-300 dark:border-rose-800/80': pathNode.severity === 'critical',
                        'border-amber-300 dark:border-amber-800/80': pathNode.severity === 'warning',
                        'border-slate-200 dark:border-slate-800': pathNode.severity === 'normal',
                    }"
                >
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <div class="space-y-2.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="pathNode.severity === 'critical'"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-600 text-white"
                                >
                                    🔴 JALUR KRITIS ({{ pathNode.total_incidents }} Insiden)
                                </span>
                                <span
                                    v-else-if="pathNode.severity === 'warning'"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500 text-white"
                                >
                                    🟠 JALUR RAWAN ({{ pathNode.total_incidents }} Insiden)
                                </span>
                                <span
                                    v-else
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300"
                                >
                                    🔌 {{ pathNode.total_incidents }} Insiden
                                </span>

                                <span class="px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 text-xs font-extrabold">
                                    {{ pathNode.node_type }}
                                </span>
                                <span class="text-base font-extrabold text-slate-900 dark:text-white">
                                    {{ pathNode.name }}
                                </span>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    ({{ pathNode.parent_name }})
                                </span>
                                <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 text-xs font-semibold">
                                    {{ pathNode.area_name }}
                                </span>
                            </div>

                            <!-- Statistik Insiden & Redaman ODP -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 text-xs font-bold border border-rose-200/60">
                                    👥 {{ pathNode.affected_customers_count }} Pelanggan Terdampak
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    ⚡ Tiket Jalur (PGJ): <strong>{{ pathNode.pgj_count }}</strong> | Tiket Pelanggan (PGK+PGS): <strong>{{ pathNode.customer_ticket_count }}</strong>
                                </span>
                                <span v-if="pathNode.total_ports > 0" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    🔌 Port Terpakai: {{ pathNode.used_ports }} / {{ pathNode.total_ports }}
                                </span>
                                <span
                                    v-if="pathNode.avg_rx_power !== null"
                                    :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-bold border',
                                        pathNode.bad_rx_ont_count > 0
                                            ? 'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/70 dark:text-rose-300'
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/70 dark:text-emerald-300'
                                    ]"
                                >
                                    📶 Rata-rata Redaman: {{ pathNode.avg_rx_power }} dBm (Terburuk: {{ pathNode.worst_rx_power }} dBm)
                                </span>
                            </div>

                            <!-- Diagnosa Jalur -->
                            <div class="p-2.5 rounded-xl bg-purple-50/80 dark:bg-purple-950/30 border border-purple-200/70 dark:border-purple-800/50 text-xs text-purple-900 dark:text-purple-200">
                                <strong>🔍 Analisa Kesehatan Jalur:</strong> {{ pathNode.diagnosis }}
                            </div>

                            <!-- Daftar Singkat Pelanggan Terdampak di ODP Ini -->
                            <div v-if="pathNode.affected_customers && pathNode.affected_customers.length" class="pt-1">
                                <p class="text-[11px] font-bold text-slate-500 mb-1.5">
                                    Pelanggan yang Mengalami Gangguan di {{ pathNode.name }}:
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="ac in pathNode.affected_customers"
                                        :key="ac.id"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1.5"
                                        :class="
                                            ac.has_visited
                                                ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 text-emerald-800 dark:text-emerald-300'
                                                : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'
                                        "
                                    >
                                        <span>{{ ac.name }}</span>
                                        <span class="px-1.5 py-0.2 rounded bg-rose-100 dark:bg-rose-900 text-rose-700 dark:text-rose-200 text-[10px] font-bold">
                                            {{ ac.total_tickets }}x
                                        </span>
                                        <span v-if="ac.has_visited" title="Sudah Dikunjungi Sosialisasi">✅</span>
                                        <span v-else-if="ac.has_scheduled_visit" title="Terjadwal Sosialisasi">📅</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Info Pemeliharaan Jalur Terakhir -->
                            <div
                                v-if="pathNode.latest_maintenance"
                                class="p-2.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-xs text-emerald-800 dark:text-emerald-200 space-y-1.5"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        🔧 <strong>Pemeliharaan Jalur:</strong>
                                        <span class="font-mono font-bold ml-1">{{ pathNode.latest_maintenance.maintenance_number }}</span>
                                        <span class="mx-1">•</span>
                                        <span>{{ pathNode.latest_maintenance.scheduled_date }}</span>
                                        <span class="mx-1">•</span>
                                        <span>Status: <strong class="uppercase">{{ pathNode.latest_maintenance.status }}</strong></span>
                                        <span class="mx-1">•</span>
                                        <span>Petugas: <strong>{{ pathNode.latest_maintenance.technicians }}</strong></span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openNetworkMaintenancePage(pathNode.latest_maintenance)"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold cursor-pointer transition"
                                    >
                                        📋 Buka & Isi Laporan di Pemeliharaan Jalur →
                                    </button>
                                </div>
                                <div
                                    v-if="pathNode.latest_maintenance.materials_summary && pathNode.latest_maintenance.materials_summary.length"
                                    class="flex flex-wrap items-center gap-1.5 pt-0.5"
                                >
                                    <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300">📦 Bekal Material:</span>
                                    <span
                                        v-for="(ms, mIdx) in pathNode.latest_maintenance.materials_summary"
                                        :key="mIdx"
                                        class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 border border-emerald-200 dark:border-emerald-700 text-[11px] font-semibold text-emerald-800 dark:text-emerald-200"
                                    >
                                        {{ ms }}
                                    </span>
                                </div>
                                <p v-if="pathNode.latest_maintenance.action_taken" class="text-[11px] text-emerald-900 dark:text-emerald-100">
                                    <strong>Hasil Tindakan:</strong> {{ pathNode.latest_maintenance.action_taken }}
                                </p>
                            </div>
                        </div>

                        <!-- Right Action Buttons -->
                        <div class="flex flex-row lg:flex-col gap-2 shrink-0">
                            <button
                                v-if="canScheduleMaint"
                                type="button"
                                @click="openScheduleMaintenanceModal(pathNode)"
                                class="flex-1 lg:flex-none px-3.5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>🔧</span>
                                <span>Jadwalkan Perbaikan Jalur</span>
                            </button>

                            <button
                                v-if="pathNode.latest_maintenance && canScheduleMaint"
                                type="button"
                                @click="openNetworkMaintenancePage(pathNode.latest_maintenance)"
                                class="flex-1 lg:flex-none px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>📝</span>
                                <span>Update Laporan Jalur</span>
                            </button>

                            <button
                                v-if="pathNode.affected_customers && pathNode.affected_customers.length > 0 && canManageVisit"
                                type="button"
                                @click="openBatchVisitForPath(pathNode)"
                                class="flex-1 lg:flex-none px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>🤝</span>
                                <span>Sosialisasi {{ pathNode.affected_customers.length }} Pelanggan</span>
                            </button>

                            <button
                                type="button"
                                @click="openPathTimeline(pathNode)"
                                class="flex-1 lg:flex-none px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>📋</span>
                                <span>Rincian Tiket Jalur</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PAGINATION TAB 2: JALUR / ODP / ODC -->
                <div
                    v-if="chronicPaths.length > 0"
                    class="bg-white dark:bg-slate-900 rounded-2xl px-4 py-3.5 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm"
                >
                    <p class="text-xs text-slate-600 dark:text-slate-400 font-semibold">
                        Menampilkan
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ (pathPage - 1) * perPage + 1 }}</span>
                        -
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ Math.min(pathPage * perPage, chronicPaths.length) }}</span>
                        dari
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ chronicPaths.length }}</span>
                        titik distribusi (ODP/ODC)
                    </p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            :disabled="pathPage <= 1"
                            @click="pathPage = Math.max(1, pathPage - 1)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                        >
                            ‹ Sebelumnya
                        </button>
                        <template v-for="(p, idx) in getVisiblePages(pathPage, pathTotalPages)" :key="idx">
                            <span v-if="p === '...'" class="px-2 text-xs text-slate-400">...</span>
                            <button
                                v-else
                                type="button"
                                @click="pathPage = p"
                                :class="[
                                    'w-8 h-8 rounded-xl text-xs font-extrabold transition cursor-pointer',
                                    pathPage === p
                                        ? 'bg-purple-600 text-white shadow-sm'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'
                                ]"
                            >
                                {{ p }}
                            </button>
                        </template>
                        <button
                            type="button"
                            :disabled="pathPage >= pathTotalPages"
                            @click="pathPage = Math.min(pathTotalPages, pathPage + 1)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                        >
                            Berikutnya ›
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: JADWAL & LAPORAN PETUGAS SOSIALISASI RUMAH          -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'visits'" class="space-y-4">
                <!-- Sub-filter Status Kunjungan -->
                <div class="flex flex-wrap items-center gap-2 bg-white dark:bg-slate-900 p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="setVisitFilter('all', false)"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer transition',
                            visitStatus === 'all' && !myTasks ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                        ]"
                    >
                        Semua Kunjungan ({{ visitSummary.total || 0 }})
                    </button>
                    <button
                        type="button"
                        @click="setVisitFilter('all', true)"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer transition',
                            myTasks ? 'bg-indigo-600 text-white' : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300'
                        ]"
                    >
                        👤 Tugas Kunjungan Saya ({{ visitSummary.my_tasks || 0 }})
                    </button>
                    <button
                        type="button"
                        @click="setVisitFilter('scheduled', false)"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer transition',
                            visitStatus === 'scheduled' && !myTasks ? 'bg-sky-600 text-white' : 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300'
                        ]"
                    >
                        📅 Terjadwal ({{ visitSummary.scheduled || 0 }})
                    </button>
                    <button
                        type="button"
                        @click="setVisitFilter('completed', false)"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer transition',
                            visitStatus === 'completed' && !myTasks ? 'bg-emerald-600 text-white' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'
                        ]"
                    >
                        ✅ Selesai Sosialisasi ({{ visitSummary.completed || 0 }})
                    </button>
                </div>

                <div v-if="socializationVisits.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl p-10 text-center border border-slate-200 dark:border-slate-800">
                    <p class="text-base font-bold text-slate-700 dark:text-slate-200">Belum ada jadwal kunjungan sosialisasi pelanggan</p>
                    <p class="text-xs text-slate-500 mt-1">Klik tombol "Utus Petugas Sosialisasi" pada daftar pelanggan sering gangguan untuk menugaskan petugas mendatangi rumah pelanggan.</p>
                </div>

                <div
                    v-for="visit in paginatedVisits"
                    :key="visit.id"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border shadow-sm transition hover:shadow-md"
                    :class="visit.is_done ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-sky-200 dark:border-sky-900/50'"
                >
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <div class="space-y-2.5 flex-1">
                            <!-- Header Badge & Nomor Tugas -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="visit.status === 'completed'"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-600 text-white"
                                >
                                    ✅ SELESAI DIKUNJUNGI
                                </span>
                                <span
                                    v-else-if="visit.status === 'in_progress'"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500 text-white"
                                >
                                    🚗 SEDANG DIKUNJUNGI
                                </span>
                                <span
                                    v-else-if="visit.status === 'rescheduled'"
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-purple-600 text-white"
                                >
                                    🔄 JADWAL ULANG
                                </span>
                                <span
                                    v-else
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-sky-600 text-white"
                                >
                                    📅 TERJADWAL KUNJUNGAN
                                </span>

                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-mono font-bold">
                                    {{ visit.visit_number }}
                                </span>
                                <span class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                                    {{ visit.customer_name }} ({{ visit.customer_code }})
                                </span>
                                <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 text-xs font-semibold">
                                    {{ visit.area_name }}
                                </span>
                            </div>

                            <!-- Judul & Tujuan Sosialisasi -->
                            <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                                🎯 {{ visit.title }} — <span class="text-rose-600 dark:text-rose-400">{{ visit.purpose_label }}</span>
                            </p>

                            <!-- Detail Rumah Pelanggan & Petugas -->
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-400">
                                <span>📍 <strong>Alamat Rumah:</strong> {{ visit.customer_address }}</span>
                                <a
                                    v-if="visit.customer_lat && visit.customer_lng"
                                    :href="`https://www.google.com/maps?q=${visit.customer_lat},${visit.customer_lng}`"
                                    target="_blank"
                                    class="text-blue-600 dark:text-blue-400 font-bold hover:underline"
                                >
                                    🗺️ Buka Titik Rumah (Maps)
                                </a>
                                <span v-if="visit.customer_phone && visit.customer_phone !== '-'">
                                    📞 <a :href="formatWaUrl(visit.customer_phone)" target="_blank" class="text-emerald-600 font-bold hover:underline">{{ visit.customer_phone }}</a>
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200/70">
                                    👮 Petugas Sosialisasi: <strong>{{ visit.officers_str }}</strong>
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    📅 Jadwal: <strong>{{ visit.scheduled_date_formatted }}</strong> {{ visit.scheduled_time ? `(${visit.scheduled_time})` : '' }}
                                </span>
                                <span v-if="visit.odp_name && visit.odp_name !== '-'" class="px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-xs font-semibold">
                                    🔌 ODP: {{ visit.odp_name }}
                                </span>
                            </div>

                            <!-- Ringkasan Masalah & Instruksi -->
                            <div v-if="visit.issue_summary || visit.admin_notes" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700 text-xs space-y-1">
                                <p v-if="visit.issue_summary" class="text-slate-700 dark:text-slate-300">
                                    <strong>📊 Dasar Riwayat Gangguan:</strong> {{ visit.issue_summary }}
                                </p>
                                <p v-if="visit.admin_notes" class="text-slate-600 dark:text-slate-400">
                                    <strong>📝 Instruksi Petugas:</strong> {{ visit.admin_notes }}
                                </p>
                            </div>

                            <!-- HASIL LAPORAN KUNJUNGAN SOSIALISASI -->
                            <div
                                v-if="visit.status === 'completed' || visit.met_with_name || visit.report_notes"
                                class="p-3 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 space-y-2 text-xs"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-extrabold text-emerald-900 dark:text-emerald-200">
                                        📋 Hasil Laporan Kunjungan di Rumah Pelanggan:
                                    </span>
                                    <span v-if="visit.completed_at" class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                        Selesai: {{ visit.completed_at }} {{ visit.completed_by_name ? `oleh ${visit.completed_by_name}` : '' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-700 dark:text-slate-200">
                                    <div>
                                        <strong>🤝 Bertemu Dengan:</strong> {{ visit.met_with_name || '-' }}
                                        <span v-if="visit.met_with_relation" class="text-slate-500">({{ visit.met_with_relation }})</span>
                                    </div>
                                    <div>
                                        <strong>💬 Respon Pelanggan:</strong>
                                        <span class="ml-1 px-2 py-0.5 rounded bg-white dark:bg-slate-800 font-bold text-emerald-700 dark:text-emerald-300">
                                            {{ visit.response_label }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Checklist Poin Sosialisasi -->
                                <div v-if="visit.socialization_points && visit.socialization_points.length" class="space-y-1">
                                    <p class="font-bold text-slate-600 dark:text-slate-300">Poin Sosialisasi yang Disampaikan:</p>
                                    <ul class="space-y-0.5 pl-1">
                                        <li v-for="(pt, i) in visit.socialization_points" :key="i" class="flex items-start gap-1.5 text-emerald-800 dark:text-emerald-200">
                                            <span>☑️</span>
                                            <span>{{ pt }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <p v-if="visit.report_notes" class="text-slate-800 dark:text-slate-200">
                                    <strong>📝 Catatan Hasil Sosialisasi & Masukan Pelanggan:</strong> {{ visit.report_notes }}
                                </p>
                                <p v-if="visit.follow_up_action" class="text-rose-700 dark:text-rose-300 font-semibold">
                                    <strong>🔧 Tindak Lanjut:</strong> {{ visit.follow_up_action }}
                                </p>

                                <!-- Foto Dokumentasi Kunjungan -->
                                <div v-if="visit.photo_visit_urls && visit.photo_visit_urls.length" class="pt-1 flex items-center gap-3">
                                    <img
                                        v-for="(imgUrl, idx) in visit.photo_visit_urls"
                                        :key="idx"
                                        :src="imgUrl"
                                        @click="lightboxUrl = imgUrl"
                                        class="w-20 h-20 object-cover rounded-xl border-2 border-emerald-400 cursor-pointer hover:scale-105 transition shadow-sm"
                                        title="Klik untuk memperbesar foto kunjungan rumah pelanggan"
                                    />
                                    <span class="text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold">
                                        📸 Foto Bukti Kunjungan Petugas di Rumah Pelanggan
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Action Buttons -->
                        <div class="flex flex-row lg:flex-col gap-2 shrink-0">
                            <button
                                v-if="canReportVisit"
                                type="button"
                                @click="openReportModal(visit)"
                                class="flex-1 lg:flex-none px-3.5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>📝</span>
                                <span>{{ visit.is_done ? 'Edit Laporan Sosialisasi' : 'Isi Laporan Kunjungan' }}</span>
                            </button>

                            <button
                                v-if="canWhatsapp"
                                type="button"
                                @click="resendOfficerWa(visit)"
                                class="flex-1 lg:flex-none px-3.5 py-2 rounded-xl bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>📲</span>
                                <span>WA Tugas ke Petugas</span>
                            </button>

                            <button
                                v-if="canDeleteVisit"
                                type="button"
                                @click="deleteVisitItem(visit)"
                                class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold cursor-pointer"
                            >
                                🗑️ Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PAGINATION TAB 3: KUNJUNGAN SOSIALISASI -->
                <div
                    v-if="socializationVisits.length > 0"
                    class="bg-white dark:bg-slate-900 rounded-2xl px-4 py-3.5 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm"
                >
                    <p class="text-xs text-slate-600 dark:text-slate-400 font-semibold">
                        Menampilkan
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ (visitPage - 1) * perPage + 1 }}</span>
                        -
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ Math.min(visitPage * perPage, socializationVisits.length) }}</span>
                        dari
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ socializationVisits.length }}</span>
                        kunjungan sosialisasi
                    </p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            :disabled="visitPage <= 1"
                            @click="visitPage = Math.max(1, visitPage - 1)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                        >
                            ‹ Sebelumnya
                        </button>
                        <template v-for="(p, idx) in getVisiblePages(visitPage, visitTotalPages)" :key="idx">
                            <span v-if="p === '...'" class="px-2 text-xs text-slate-400">...</span>
                            <button
                                v-else
                                type="button"
                                @click="visitPage = p"
                                :class="[
                                    'w-8 h-8 rounded-xl text-xs font-extrabold transition cursor-pointer',
                                    visitPage === p
                                        ? 'bg-emerald-600 text-white shadow-sm'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'
                                ]"
                            >
                                {{ p }}
                            </button>
                        </template>
                        <button
                            type="button"
                            :disabled="visitPage >= visitTotalPages"
                            @click="visitPage = Math.min(visitTotalPages, visitPage + 1)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                        >
                            Berikutnya ›
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 1: UTUS / JADWALKAN PETUGAS SOSIALISASI KE RUMAH PELANGGAN   -->
        <!-- ================================================================= -->
        <div
            v-if="showAssignVisitModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8 max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🤝</span>
                            <span>Penugasan Petugas Sosialisasi ke Rumah Pelanggan</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Utus petugas untuk mendatangi langsung rumah pelanggan guna sosialisasi, menjaga kepercayaan, & memastikan layanan prima.
                        </p>
                    </div>
                    <button @click="showAssignVisitModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <div class="space-y-4">
                    <!-- Target Pelanggan -->
                    <div v-if="visitForm.mode === 'batch'" class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 text-xs font-bold text-purple-800 dark:text-purple-200">
                        👥 Mode Sosialisasi Massal Jalur: {{ visitForm.batch_label }}
                    </div>
                    <div v-else-if="visitForm.customer_name_display" class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 text-xs font-bold text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
                        <span>🏠 Rumah Pelanggan: {{ visitForm.customer_name_display }}</span>
                        <button type="button" @click="visitForm.customer_name_display = ''; visitForm.customer_id = ''" class="text-rose-600 underline text-[11px] cursor-pointer">Ganti Pelanggan</button>
                    </div>
                    <div v-else>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pilih Pelanggan yang Akan Dikunjungi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="customerSearchModal"
                            type="text"
                            placeholder="Ketik nama / kode pelanggan..."
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3 mb-2"
                        />
                        <select
                            v-model="visitForm.customer_id"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                        >
                            <option value="">-- Pilih Pelanggan ({{ filteredCustomerOptions.length }} tampil) --</option>
                            <option v-for="c in filteredCustomerOptions" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.customer_code }}) — {{ c.address }}
                            </option>
                        </select>
                    </div>

                    <!-- Kategori Tujuan & Prioritas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Kategori Tujuan Sosialisasi
                            </label>
                            <select
                                v-model="visitForm.purpose_category"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            >
                                <option value="gangguan_berulang">Sosialisasi & Penanganan Gangguan Berulang</option>
                                <option value="pasca_perbaikan_jalur">Edukasi Pasca Peremajaan / Perbaikan Jalur ODP</option>
                                <option value="edukasi_perangkat">Edukasi Penggunaan Perangkat ONT & WiFi Rumah</option>
                                <option value="komplain_khusus">Kunjungan Khusus Care & Mediasi Keluhan</option>
                                <option value="service_recovery">Service Recovery & Pengecekan Kepuasan Pelanggan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Prioritas Kunjungan
                            </label>
                            <select
                                v-model="visitForm.priority"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            >
                                <option value="normal">Normal</option>
                                <option value="high">Tinggi (Prioritas)</option>
                                <option value="urgent">Urgent (Segera Kunjungi Hari Ini)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Judul Kunjungan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Judul Kegiatan Sosialisasi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="visitForm.title"
                            type="text"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                        />
                    </div>

                    <!-- Tanggal & Jam Kunjungan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Tanggal Kunjungan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="visitForm.scheduled_date"
                                type="date"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Estimasi Jam Kunjungan
                            </label>
                            <input
                                v-model="visitForm.scheduled_time"
                                type="time"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            />
                        </div>
                    </div>

                    <!-- Pilih Petugas Sosialisasi -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Pilih Petugas yang Mendatangi Rumah Pelanggan <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] font-bold text-emerald-600">
                                {{ visitForm.officer_ids.length }} Petugas Dipilih
                            </span>
                        </div>
                        <input
                            v-model="officerSearch"
                            type="text"
                            placeholder="Cari nama petugas / jabatan..."
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-1.5 px-3 mb-2"
                        />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-44 overflow-y-auto p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/50">
                            <label
                                v-for="off in filteredOfficers"
                                :key="off.id"
                                class="flex items-center gap-2 p-2 rounded-lg cursor-pointer text-xs transition"
                                :class="
                                    visitForm.officer_ids.includes(off.id)
                                        ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-900 dark:text-emerald-200 font-bold border border-emerald-300'
                                        : 'hover:bg-slate-100 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-300'
                                "
                            >
                                <input
                                    type="checkbox"
                                    :checked="visitForm.officer_ids.includes(off.id)"
                                    @change="toggleOfficerSelection(off.id)"
                                    class="rounded text-emerald-600"
                                />
                                <div class="truncate">
                                    <p class="truncate">{{ off.name }}</p>
                                    <p class="text-[10px] opacity-75">{{ off.role_label }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Ringkasan Riwayat Gangguan & Instruksi Sosialisasi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Ringkasan Riwayat Gangguan Pelanggan (Bekal Info untuk Petugas)
                        </label>
                        <textarea
                            v-model="visitForm.issue_summary"
                            rows="2"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5"
                            placeholder="Ringkasan keluhan berulang yang dialami pelanggan..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pesan / Materi Sosialisasi yang Harus Disampaikan di Rumah Pelanggan
                        </label>
                        <textarea
                            v-model="visitForm.admin_notes"
                            rows="3"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5"
                            placeholder="Instruksi untuk petugas saat bertemu pelanggan..."
                        ></textarea>
                    </div>

                    <!-- Opsi Kirim WhatsApp Otomatis -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" v-model="visitForm.send_wa_officer" class="rounded text-emerald-600" />
                            <span>📲 Kirim WA Surat Tugas ke Petugas</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" v-model="visitForm.send_wa_customer" class="rounded text-emerald-600" />
                            <span>💬 Kirim WA Pemberitahuan Kunjungan ke Pelanggan</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showAssignVisitModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmittingVisit"
                        @click="submitAssignVisit"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-md cursor-pointer disabled:opacity-50"
                    >
                        {{ isSubmittingVisit ? 'Menyimpan...' : '🤝 Simpan & Tugaskan Petugas' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 2: ISI LAPORAN KUNJUNGAN SOSIALISASI DI RUMAH PELANGGAN      -->
        <!-- ================================================================= -->
        <div
            v-if="showReportVisitModal && selectedVisit"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8 max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
                            📝 Laporan Hasil Kunjungan Sosialisasi Rumah Pelanggan
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ selectedVisit.visit_number }} — {{ selectedVisit.customer_name }} ({{ selectedVisit.customer_code }})
                        </p>
                    </div>
                    <button @click="showReportVisitModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Status Kunjungan <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="reportForm.status"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold py-2 px-3"
                            >
                                <option value="completed">✅ Selesai Dikunjungi</option>
                                <option value="in_progress">🚗 Sedang Dikunjungi</option>
                                <option value="rescheduled">🔄 Jadwal Ulang (Rumah Kosong)</option>
                                <option value="scheduled">📅 Terjadwal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Jam Mulai Kunjungan
                            </label>
                            <input
                                v-model="reportForm.start_time"
                                type="time"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Jam Selesai
                            </label>
                            <input
                                v-model="reportForm.end_time"
                                type="time"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            />
                        </div>
                    </div>

                    <!-- Penerima di Rumah Pelanggan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nama yang Ditemui di Rumah Pelanggan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="reportForm.met_with_name"
                                type="text"
                                placeholder="Misal: Bpk. Kurniawan / Ibu Siti"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Hubungan Penerima
                            </label>
                            <select
                                v-model="reportForm.met_with_relation"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            >
                                <option value="Pelanggan Langsung">Pelanggan Langsung</option>
                                <option value="Suami / Istri">Suami / Istri</option>
                                <option value="Keluarga Serumah">Keluarga Serumah</option>
                                <option value="Penjaga / Karyawan">Penjaga / Karyawan</option>
                            </select>
                        </div>
                    </div>

                    <!-- Respon Pelanggan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Respon / Tanggapan Pelanggan Saat Dikunjungi
                        </label>
                        <select
                            v-model="reportForm.customer_response"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-semibold py-2 px-3"
                        >
                            <option value="puas_memahami">😊 Puas & Memahami Penjelasan Petugas (Layanan Sudah Normal)</option>
                            <option value="minta_pantauan">👀 Memahami & Minta Koneksi Tetap Dipantau Beberapa Hari Ke Depan</option>
                            <option value="masih_keluhan">⚠️ Masih Ada Keluhan Teknis (Perlu Tindak Lanjut Teknisi)</option>
                            <option value="butuh_ganti_perangkat">🔧 Perlu Pergantian Perangkat ONT / Peremajaan Kabel Dropcore</option>
                        </select>
                    </div>

                    <!-- Checklist Poin Sosialisasi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Checklist Poin Sosialisasi yang Telah Dilakukan di Rumah Pelanggan:
                        </label>
                        <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                            <label
                                v-for="(pt, idx) in defaultChecklistOptions"
                                :key="idx"
                                class="flex items-start gap-2 text-xs text-slate-700 dark:text-slate-200 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :checked="reportForm.socialization_points.includes(pt)"
                                    @change="toggleSocializationPoint(pt)"
                                    class="mt-0.5 rounded text-emerald-600"
                                />
                                <span>{{ pt }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Catatan Hasil Sosialisasi & Tindak Lanjut -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Catatan Hasil Sosialisasi & Masukan dari Pelanggan <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="reportForm.report_notes"
                            rows="3"
                            placeholder="Tuliskan hasil obrolan/sosialisasi dengan pelanggan di rumah serta kondisi koneksi saat dicek..."
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Rencana Tindak Lanjut (Opsional bila masih ada kendala)
                        </label>
                        <input
                            v-model="reportForm.follow_up_action"
                            type="text"
                            placeholder="Misal: Pantau traffic 3 hari ke depan / Jadwalkan tarik ulang dropcore..."
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                        />
                    </div>

                    <!-- Titik GPS & Upload Foto Bukti Kunjungan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                📸 Foto Bukti Kunjungan di Rumah Pelanggan
                            </label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="onVisitPhotoChange"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                            />
                            <img
                                v-if="photoPreviewUrl"
                                :src="photoPreviewUrl"
                                class="mt-2 w-28 h-28 object-cover rounded-xl border border-slate-300"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                📍 Titik GPS Lokasi Kunjungan
                            </label>
                            <div class="flex gap-1.5 mb-2">
                                <input
                                    v-model="reportForm.latitude"
                                    type="text"
                                    placeholder="Latitude"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-1.5 px-2.5"
                                />
                                <input
                                    v-model="reportForm.longitude"
                                    type="text"
                                    placeholder="Longitude"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-1.5 px-2.5"
                                />
                            </div>
                            <button
                                type="button"
                                @click="captureGpsLocation"
                                :disabled="isGettingGps"
                                class="px-3 py-1.5 rounded-xl bg-sky-100 hover:bg-sky-200 dark:bg-sky-950 text-sky-700 dark:text-sky-300 text-xs font-bold cursor-pointer"
                            >
                                {{ isGettingGps ? 'Mengambil GPS...' : '📍 Ambil Titik GPS Saat Ini' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showReportVisitModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmittingReport"
                        @click="submitVisitReport"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-md cursor-pointer disabled:opacity-50"
                    >
                        {{ isSubmittingReport ? 'Menyimpan...' : '💾 Simpan Laporan Sosialisasi' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 3: JADWALKAN PEMELIHARAAN JALUR ODP / ODC + BEKAL MATERIAL   -->
        <!-- ================================================================= -->
        <div
            v-if="showMaintenanceModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8 max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
                            🔧 Jadwalkan Pemeliharaan / Peremajaan Jalur
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Otomatis terhubung ke menu <strong>Pemeliharaan Jalur (ODP/ODC)</strong> & Inventory Gudang Area
                        </p>
                    </div>
                    <button @click="showMaintenanceModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <!-- Info Ringkas Titik Distribusi -->
                <div
                    v-if="selectedMaintNode"
                    class="p-3 rounded-xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/60 flex flex-wrap items-center justify-between gap-2 text-xs"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-purple-600 text-white font-extrabold text-[11px]">
                            {{ selectedMaintNode.node_type }}
                        </span>
                        <span class="font-extrabold text-purple-950 dark:text-purple-100 text-sm">
                            {{ selectedMaintNode.name }}
                        </span>
                        <span class="px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-bold">
                            📍 {{ selectedMaintNode.area_name }}
                        </span>
                    </div>
                    <div class="text-[11px] font-bold text-rose-600 dark:text-rose-400">
                        ⚡ {{ selectedMaintNode.total_incidents }} Insiden ({{ selectedMaintNode.affected_customers_count }} Pelanggan)
                    </div>
                </div>

                <div class="space-y-3.5">
                    <!-- Kategori & Prioritas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jenis Kegiatan Jalur</label>
                            <select
                                v-model="maintForm.category"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-semibold py-2 px-3"
                            >
                                <option value="pengecekan_odp">🔌 Pengecekan & Peremajaan ODP</option>
                                <option value="pengecekan_odc">🗄️ Pengecekan & Peremajaan ODC</option>
                                <option value="perapihan_jalur">🧵 Perapihan Jalur Distribusi / Dropcore</option>
                                <option value="pengecekan_jalur_utama">⚡ Pengecekan Jalur Utama / Backbone</option>
                                <option value="pemeliharaan_rutin">🛡️ Pemeliharaan Rutin Preventif</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Prioritas Penanganan</label>
                            <select
                                v-model="maintForm.priority"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-semibold py-2 px-3"
                            >
                                <option value="urgent">🔴 Urgent (Kritis / Segera)</option>
                                <option value="high">🟠 High (Prioritas Tinggi)</option>
                                <option value="normal">🔵 Normal (Terjadwal)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Judul Kegiatan <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="maintForm.title"
                            type="text"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Pelaksanaan</label>
                            <input
                                v-model="maintForm.scheduled_date"
                                type="date"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jam Mulai</label>
                            <input
                                v-model="maintForm.scheduled_time"
                                type="time"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs py-2 px-3"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Instruksi Pengerjaan & Diagnosa</label>
                        <textarea
                            v-model="maintForm.description"
                            rows="3"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5"
                        ></textarea>
                    </div>

                    <!-- Pilih Petugas Teknis / Maintenance -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                👷 Pilih Petugas Teknis / Maintenance ({{ maintForm.technician_ids.length }} dipilih)
                            </label>
                            <label class="flex items-center gap-1.5 text-[11px] text-slate-500 cursor-pointer">
                                <input type="checkbox" v-model="showAllMaintOfficers" class="rounded text-purple-600" />
                                <span>Tampilkan petugas semua wilayah</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-40 overflow-y-auto p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40">
                            <label
                                v-for="off in filteredMaintOfficers"
                                :key="off.id"
                                :class="[
                                    'flex items-center justify-between p-2 rounded-lg text-xs cursor-pointer border transition',
                                    maintForm.technician_ids.includes(off.id)
                                        ? 'bg-purple-50 dark:bg-purple-950/60 border-purple-400 text-purple-900 dark:text-purple-200 font-bold'
                                        : 'bg-white dark:bg-slate-800 border-slate-200/70 dark:border-slate-700 text-slate-700 dark:text-slate-300'
                                ]"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <input
                                        type="checkbox"
                                        :checked="maintForm.technician_ids.includes(off.id)"
                                        @change="toggleMaintTech(off.id)"
                                        class="rounded text-purple-600"
                                    />
                                    <span class="truncate">{{ off.name }}</span>
                                </div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 shrink-0">
                                    {{ off.role_label }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- PERSIAPAN MATERIAL GUDANG / BEKAL PETUGAS (OPSIONAL)      -->
                    <!-- ========================================================= -->
                    <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/80 dark:border-amber-800/50 space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h4 class="text-xs font-extrabold text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                                    <span>📦</span>
                                    <span>Persiapan Material Gudang / Bekal Petugas (Opsional)</span>
                                </h4>
                                <p class="text-[11px] text-amber-700 dark:text-amber-400">
                                    Otomatis memotong stok gudang area (OUT-MNT) & dihitung pemakaian/sisa kembalinya saat selesai
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="addMaintMaterialRow"
                                class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1"
                            >
                                <span>+</span>
                                <span>Tambah Material</span>
                            </button>
                        </div>

                        <div
                            v-if="!maintForm.materials || maintForm.materials.length === 0"
                            class="text-center py-2.5 px-3 rounded-lg bg-white/70 dark:bg-slate-900/50 border border-dashed border-amber-300 dark:border-amber-800 text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            Belum ada material yang disiapkan. Klik <strong>+ Tambah Material</strong> jika petugas membawa splitter, konektor, closure, atau kabel dari gudang.
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="(matItem, mIdx) in maintForm.materials"
                                :key="mIdx"
                                class="p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-amber-200 dark:border-slate-700 space-y-2"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                    <div class="sm:col-span-7">
                                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Pilih Material (Stok Area / Order Toko)</label>
                                        <select
                                            v-model="matItem.selected_key"
                                            @change="onMaintMaterialChange(matItem)"
                                            class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs py-1.5 px-2"
                                        >
                                            <option value="">-- Pilih Material dari Gudang --</option>
                                            <option
                                                v-for="opt in availableMaintMaterialItems"
                                                :key="opt.unique_key"
                                                :value="opt.unique_key"
                                            >
                                                {{ opt.name }} — Sisa: {{ opt.area_stock }} {{ opt.unit }} ({{ opt.trx_number ? `Order ${opt.trx_number}` : opt.area_name }})
                                            </option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">
                                            Jumlah Dibawa {{ matItem.max_stock !== null ? `(Maks: ${matItem.max_stock})` : '' }}
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <input
                                                v-model.number="matItem.quantity"
                                                type="number"
                                                step="any"
                                                min="0.01"
                                                :max="matItem.max_stock || undefined"
                                                @input="validateMaintMaterialQty(matItem)"
                                                class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs py-1.5 px-2 font-bold"
                                            />
                                            <span class="text-[11px] font-bold text-slate-500 shrink-0">{{ matItem.unit || 'pcs' }}</span>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2 flex justify-end sm:pt-4">
                                        <button
                                            type="button"
                                            @click="removeMaintMaterialRow(mIdx)"
                                            class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 text-xs font-bold cursor-pointer"
                                        >
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </div>
                                <input
                                    v-model="matItem.notes"
                                    type="text"
                                    placeholder="Keterangan material (opsional, misal: Cadangan ganti splitter ODP)..."
                                    class="w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-[11px] py-1 px-2.5"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Kirim Notifikasi WA ke Teknisi -->
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" v-model="maintForm.send_wa_technician" class="rounded text-purple-600" />
                            <span>📲 Kirim WA Tugas & Rincian Bekal Material ke Teknisi yang Ditugaskan</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showMaintenanceModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmittingMaint"
                        @click="submitScheduleMaintenance"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold shadow-md cursor-pointer disabled:opacity-50"
                    >
                        {{ isSubmittingMaint ? 'Menyimpan...' : '🔧 Simpan Jadwal & Bekal Material' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 4: TIMELINE RIWAYAT TIKET PELANGGAN / JALUR                  -->
        <!-- ================================================================= -->
        <div
            v-if="showTimelineModal && timelineTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-3xl w-full p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
                            📋 {{ timelineTarget.title }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ timelineTarget.subtitle }}</p>
                    </div>
                    <button @click="showTimelineModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 text-xs text-amber-900 dark:text-amber-200">
                    <strong>💡 Analisa:</strong> {{ timelineTarget.recommendation }}
                </div>

                <div v-if="timelineTarget.replaced_items && timelineTarget.replaced_items.length" class="text-xs">
                    <span class="font-bold text-slate-600 dark:text-slate-300">🛠️ Riwayat Material Pernah Diganti: </span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold">
                        {{ timelineTarget.replaced_items.join(', ') }}
                    </span>
                </div>

                <div class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
                    <div
                        v-for="(t, idx) in timelineTarget.tickets"
                        :key="idx"
                        class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-xs space-y-1"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2 py-0.5 rounded font-extrabold text-[10px]"
                                    :class="{
                                        'bg-rose-100 text-rose-700': t.type === 'PGK',
                                        'bg-amber-100 text-amber-700': t.type === 'PGS',
                                        'bg-purple-100 text-purple-700': t.type === 'PGJ',
                                    }"
                                >
                                    {{ t.type }}
                                </span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ t.ticket_number }}</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400">{{ t.category }}</span>
                            </div>
                            <span class="text-slate-500">{{ t.created_at }}</span>
                        </div>
                        <p v-if="t.customer_name" class="font-semibold text-slate-700 dark:text-slate-300">
                            👤 Pelanggan: {{ t.customer_name }} ({{ t.customer_code }})
                        </p>
                        <p class="text-slate-600 dark:text-slate-300">
                            <strong>Keluhan:</strong> {{ t.reported_issue || '-' }}
                        </p>
                        <p v-if="t.action_taken" class="text-emerald-700 dark:text-emerald-300">
                            <strong>Tindakan Perbaikan:</strong> {{ t.action_taken }}
                        </p>
                        <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500 pt-1">
                            <span v-if="t.technicians">👷 Teknisi: <strong>{{ t.technicians }}</strong></span>
                            <span v-if="t.optical_power_before || t.optical_power_after">
                                📶 Redaman: {{ t.optical_power_before || '-' }} → <strong>{{ t.optical_power_after || '-' }}</strong>
                            </span>
                            <span v-if="t.ont_replaced" class="text-amber-600 font-bold">🔄 Ganti ONT</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button
                        v-if="timelineTarget.type === 'customer' && timelineTarget.customer"
                        type="button"
                        @click="showTimelineModal = false; openCreateVisitForCustomer(timelineTarget.customer)"
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold cursor-pointer"
                    >
                        🤝 Utus Petugas Sosialisasi ke Rumah Pelanggan Ini
                    </button>
                    <button
                        type="button"
                        @click="showTimelineModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- LIGHTBOX FOTO -->
        <div
            v-if="lightboxUrl"
            @click="lightboxUrl = null"
            class="fixed inset-0 z-[60] bg-black/80 flex items-center justify-center p-4 cursor-pointer"
        >
            <img :src="lightboxUrl" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl" />
        </div>
    </AppLayout>
</template>

