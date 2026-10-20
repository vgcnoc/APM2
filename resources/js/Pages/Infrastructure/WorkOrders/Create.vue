<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    areas: Array,
    olts: Array,
    odcs: Array,
    technicians: Array,
    materials: Array,
    materialTransactions: {
        type: Array,
        default: () => []
    },
});

const currentStep = ref(1);
const isLoadingBOM = ref(false);

const form = useForm({
    type: 'odp_deployment', // 'odc_deployment' | 'odp_deployment'
    device_type: 'Normal', // 'Normal', 'Split 2', 'Closure', 'Pedestal' | 'Normal', 'Split', 'Pedestal', 'Pole Mount'
    target_name: '',
    area_id: props.areas[0]?.id || '',
    olt_id: '',
    pon_port: 1,
    odc_id: '',
    odc_splitter_port: 1,
    capacity: 8,
    core_capacity: 8,
    splitter_ratio: '1:8',
    location: '',
    planned_latitude: '',
    planned_longitude: '',
    technician_id: props.technicians[0]?.id || '',
    technician_ids: props.technicians[0]?.id ? [props.technicians[0]?.id] : [],
    scheduled_date: new Date().toISOString().slice(0, 10),
    start_point: '',
    end_point: '',
    cable_pull: 'Individu',
    cable_length_estimate: 150,
    notes: '',
    materials: [],
    split_units: [
        {
            name: '',
            ratio: 4,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: '',
            cable_pull: 'Individu',
        },
        {
            name: '',
            ratio: 4,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: '',
            cable_pull: 'Individu',
        }
    ],
});

const isLocating = ref(false);
const gpsAccuracy = ref(null);
const gpsStatusMsg = ref('');

const getAutoLocation = () => {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser Anda.');
        return;
    }
    isLocating.value = true;
    gpsStatusMsg.value = 'Mencari sinyal GPS perangkat...';
    navigator.geolocation.getCurrentPosition(
        (position) => {
            isLocating.value = false;
            form.planned_latitude = position.coords.latitude.toFixed(7);
            form.planned_longitude = position.coords.longitude.toFixed(7);
            gpsAccuracy.value = Math.round(position.coords.accuracy);
            gpsStatusMsg.value = `Lokasi GPS berhasil diambil (Akurasi: ±${gpsAccuracy.value}m)`;
            setTimeout(() => {
                gpsStatusMsg.value = '';
            }, 5000);
        },
        (error) => {
            isLocating.value = false;
            gpsStatusMsg.value = '';
            let msg = 'Gagal mendeteksi lokasi GPS.';
            if (error.code === 1) msg = 'Akses lokasi ditolak browser. Anda bisa ketik koordinat manual atau izinkan akses lokasi pada browser/HP.';
            else if (error.code === 2) msg = 'Sinyal lokasi tidak tersedia. Silakan ketik koordinat manual.';
            else if (error.code === 3) msg = 'Waktu pencarian sinyal GPS habis. Silakan coba lagi atau ketik koordinat manual.';
            alert(msg);
        },
        {
            enableHighAccuracy: true,
            timeout: 12000,
            maximumAge: 0,
        }
    );
};

const clearCoordinates = () => {
    form.planned_latitude = '';
    form.planned_longitude = '';
    gpsAccuracy.value = null;
    gpsStatusMsg.value = '';
};

const parseAndSetCoordinates = (text) => {
    if (!text || typeof text !== 'string') return false;
    const trimmed = text.trim();
    const match = trimmed.match(/(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)/);
    if (match) {
        form.planned_latitude = parseFloat(match[1]).toFixed(7);
        form.planned_longitude = parseFloat(match[2]).toFixed(7);
        gpsStatusMsg.value = 'Koordinat berhasil dipaste!';
        setTimeout(() => { gpsStatusMsg.value = ''; }, 3000);
        return true;
    }
    return false;
};

const handleLatPaste = (e) => {
    const text = (e.clipboardData || window.clipboardData)?.getData('text');
    if (text && (text.includes(',') || text.includes('http') || text.includes(' ') || text.includes('\t'))) {
        if (parseAndSetCoordinates(text)) {
            e.preventDefault();
        }
    }
};

const handleLngPaste = (e) => {
    const text = (e.clipboardData || window.clipboardData)?.getData('text');
    if (text && (text.includes(',') || text.includes('http') || text.includes(' ') || text.includes('\t'))) {
        if (parseAndSetCoordinates(text)) {
            e.preventDefault();
        }
    }
};

const toggleTechnician = (id) => {
    const idx = form.technician_ids.indexOf(id);
    if (idx > -1) {
        if (form.technician_ids.length > 1) {
            form.technician_ids.splice(idx, 1);
            if (form.technician_id === id) {
                form.technician_id = form.technician_ids[0];
            }
        }
    } else {
        form.technician_ids.push(id);
    }
};

const setLeadTechnician = (id) => {
    form.technician_id = id;
    if (!form.technician_ids.includes(id)) {
        form.technician_ids.push(id);
    }
};

// Filter OLT dan ODC berdasarkan Area yang dipilih
const filteredOlts = computed(() => {
    if (!form.area_id) return [];
    return props.olts.filter(o => o.area_id == form.area_id);
});

const filteredOdcs = computed(() => {
    if (!form.area_id) return [];
    return props.odcs.filter(o => o.area_id == form.area_id);
});

// Auto-Naming Cerdas
const generateTargetName = () => {
    const area = props.areas?.find(a => a.id == form.area_id);
    const areaCode = area ? area.name.substring(0, 3).toUpperCase() : 'DST';

    if (form.type === 'odc_deployment') {
        const olt = props.olts?.find(o => o.id == form.olt_id);
        const oltName = olt ? olt.name.replace(/\s+/g, '') : 'OLT1';
        return `ODC-${areaCode}-${oltName}-P${form.pon_port || 1}`;
    } else {
        const odc = props.odcs?.find(o => o.id == form.odc_id);
        const odcName = odc ? odc.name.replace(/\s+/g, '') : (areaCode ? `${areaCode}01` : 'ODC01');
        return `ODP-${odcName}-01`;
    }
};

watch([() => form.type, () => form.area_id, () => form.odc_id, () => form.olt_id, () => form.pon_port], () => {
    form.target_name = generateTargetName();
}, { immediate: true });

// Penanganan perubahan tipe perangkat (Normal, Split 2, Closure, Pedestal, Pole Mount, Wall Mount, FAT Mini, Inline Closure, Split, Mini ODC)
const onDeviceTypeChange = () => {
    if (form.type === 'odp_deployment') {
        if (form.device_type === 'Split 2') {
            form.splitter_ratio = 'Split 2 (1:2 FBT)';
        } else if (form.device_type === 'Closure') {
            form.splitter_ratio = `Closure 1:${form.capacity}`;
        } else if (form.device_type === 'Pedestal') {
            form.splitter_ratio = `1:${form.capacity} (Pedestal)`;
        } else if (form.device_type === 'Pole Mount') {
            form.splitter_ratio = `1:${form.capacity} (Pole Mount)`;
        } else if (form.device_type === 'Wall Mount') {
            form.splitter_ratio = `1:${form.capacity} (Wall Mount)`;
        } else if (form.device_type === 'FAT Mini') {
            form.splitter_ratio = `1:${form.capacity} (FAT Mini)`;
        } else if (form.device_type === 'Inline Closure') {
            form.splitter_ratio = `1:${form.capacity} (Inline)`;
        } else {
            form.splitter_ratio = `1:${form.capacity}`;
        }
    } else {
        if (form.device_type === 'Split') {
            form.splitter_ratio = 'Split 1:2 FBT';
        } else if (form.device_type === 'Pedestal') {
            form.splitter_ratio = '1:8 PLC (Pedestal)';
        } else if (form.device_type === 'Pole Mount') {
            form.splitter_ratio = '1:8 PLC (Pole Mount)';
        } else if (form.device_type === 'Wall Mount') {
            form.splitter_ratio = '1:8 PLC (Wall Mount)';
        } else if (form.device_type === 'Mini ODC') {
            form.splitter_ratio = '1:4 PLC (Mini ODC)';
        } else {
            form.splitter_ratio = '1:8 PLC';
        }
    }
};

watch(() => form.type, (newType) => {
    form.device_type = 'Normal';
    if (newType === 'odc_deployment') {
        form.capacity = 24;
        form.core_capacity = 24;
        form.splitter_ratio = '1:8 PLC';
    } else {
        form.capacity = 8;
        form.core_capacity = 8;
        form.splitter_ratio = '1:8';
    }
});

watch(() => form.capacity, (newCap) => {
    if (form.type === 'odp_deployment' && form.device_type === 'Normal') {
        form.splitter_ratio = `1:${newCap}`;
    }
});

const activeTab = ref(0);

const isSplitMode = computed(() => {
    return (form.type === 'odc_deployment' && form.device_type === 'Split') ||
           (form.type === 'odp_deployment' && form.device_type === 'Split 2');
});

const totalSplitPorts = computed(() => {
    return Number(form.split_units[0].ratio || 0) + Number(form.split_units[1].ratio || 0);
});

// Watch area_id to reset unselected OLT/ODC
watch(() => form.area_id, () => {
    if (form.olt_id && !filteredOlts.value.some(o => o.id === form.olt_id)) {
        form.olt_id = '';
        form.pon_port = 1;
    }
    if (form.odc_id && !filteredOdcs.value.some(o => o.id === form.odc_id)) {
        form.odc_id = '';
    }
});

// Watch split mode to initialize ratios and names
watch([() => form.type, () => form.device_type, () => form.capacity, () => form.target_name], () => {
    if (isSplitMode.value) {
        const half = Math.floor(form.capacity / 2) || 4;
        if (!form.split_units[0].ratio) form.split_units[0].ratio = half;
        if (!form.split_units[1].ratio) form.split_units[1].ratio = half;
        const defaultName0 = form.target_name ? `${form.target_name}-A` : (form.type === 'odc_deployment' ? 'ODC Induk' : 'ODP Induk');
        const defaultName1 = form.target_name ? `${form.target_name}-B` : (form.type === 'odc_deployment' ? 'ODC Lanjutan' : 'ODP Lanjutan');
        if (!form.split_units[0].name || form.split_units[0].name.endsWith('-A') || form.split_units[0].name.includes('Induk')) {
            form.split_units[0].name = defaultName0;
        }
        if (!form.split_units[1].name || form.split_units[1].name.endsWith('-B') || form.split_units[1].name.includes('Lanjutan')) {
            form.split_units[1].name = defaultName1;
        }
    }
}, { immediate: true });

const selectedOltPonPorts = computed(() => {
    if (!form.olt_id) return [];
    const olt = props.olts.find(o => o.id === form.olt_id);
    if (!olt || !olt.total_pon_ports) return [];
    return Array.from({ length: olt.total_pon_ports }, (_, i) => i + 1);
});

function getVlanAccessMode(vlan) {
    if (vlan.access_mode) return vlan.access_mode;
    const name = (vlan.name || '').toLowerCase();
    if (name.includes('pppoe')) return 'PPPOE';
    if (name.includes('hotspot')) return 'Hotspot';
    if (name.includes('static')) return 'Static';
    if (name.includes('dhcp')) return 'DHCP';
    if (name.includes('bridge')) return 'Bridge';
    if (name.includes('trunk') || name.includes('tagged')) return 'Tagged';
    if (name.includes('access') || name.includes('untagged')) return 'Untagged';
    return 'PPPOE';
}

function getModeBadgeClass(mode) {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'HOTSPOT':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'STATIC':
        case 'STATIC IP':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'DHCP':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'BRIDGE':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'TAGGED':
        case 'TRUNK':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}

const currentModalPonVlans = computed(() => {
    if (!form.olt_id || !form.pon_port) return [];
    const olt = props.olts.find(o => o.id === form.olt_id);
    if (!olt || !olt.pon_vlans) return [];
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const ponData = vlans.find(p => p.port == form.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
});

const selectedOdcInfo = computed(() => {
    if (!form.odc_id) return null;
    return props.odcs.find(o => o.id === form.odc_id);
});

const currentModalOdpVlans = computed(() => {
    if (!selectedOdcInfo.value || !selectedOdcInfo.value.pon_port || !selectedOdcInfo.value.olt || !selectedOdcInfo.value.olt.pon_vlans) return [];
    let vlans = selectedOdcInfo.value.olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const ponData = vlans.find(p => p.port == selectedOdcInfo.value.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
});

const isBranchMismatch = computed(() => {
    if (!form.area_id || !selectedOdcInfo.value) return false;
    return selectedOdcInfo.value.area_id != form.area_id;
});

const getLocationForSplit = (tabIndex) => {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser Anda.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.split_units[tabIndex].latitude = position.coords.latitude.toFixed(7);
            form.split_units[tabIndex].longitude = position.coords.longitude.toFixed(7);
        },
        (error) => {
            alert('Gagal mendeteksi lokasi GPS: ' + error.message);
        },
        { enableHighAccuracy: true, timeout: 12000 }
    );
};

// Info Wilayah / Area Terpilih
const selectedArea = computed(() => {
    return props.areas?.find(a => a.id == form.area_id) || null;
});

const selectedAreaName = computed(() => {
    return selectedArea.value?.name || 'Area Terpilih';
});

// Helper untuk mengambil stok suatu material di Area yang sedang dipilih
const getAreaStock = (materialId) => {
    const mat = props.materials?.find(m => m.id == materialId);
    if (!mat) return 0;
    if (form.area_id && mat.stocks && Array.isArray(mat.stocks)) {
        const s = mat.stocks.find(st => st.area_id == form.area_id);
        if (s) return parseFloat(s.stock) || 0;
    }
    return parseFloat(mat.stock) || 0;
};

// Filter transaksi Order Toko berdasarkan Area yang dipilih
const filteredMaterialTransactions = computed(() => {
    const allTrx = props.materialTransactions || [];
    const areaId = form.area_id;
    const areaName = selectedArea.value?.name?.toLowerCase().trim();
    if (!areaId) return allTrx;
    
    const matched = allTrx.filter(trx => {
        if (trx.area_id && trx.area_id == areaId) return true;
        if (trx.area && areaName && trx.area.toLowerCase().trim() === areaName) return true;
        if (trx.technician_name && areaName && trx.technician_name.toLowerCase().trim() === areaName) return true;
        return false;
    });

    return matched.length > 0 ? matched : allTrx;
});

// Daftar Item Material dari Order Toko (Restock) Area ini
const availableRestockMaterials = computed(() => {
    const items = [];
    const trxs = filteredMaterialTransactions.value || [];

    trxs.forEach(trx => {
        if (trx.items && trx.items.length > 0) {
            trx.items.forEach(item => {
                if (!item.material) return;
                const name = (item.material.name || '').toLowerCase();
                const category = (item.material.category || '').toLowerCase();
                const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;

                // Kecualikan ONT / Modem untuk kebutuhan rollout kabel/tiang ODC/ODP
                if (!isRegistered && !name.includes('modem') && !category.includes('modem')) {
                    let areaStock = getAreaStock(item.material_id);
                    if (areaStock <= 0 && item.quantity) {
                        areaStock = parseFloat(item.quantity) || 0;
                    }

                    items.push({
                        id: item.id,
                        material_id: item.material_id,
                        trx_number: trx.transaction_number,
                        trx_date: trx.date,
                        purpose: trx.purpose,
                        area_name: trx.area_model?.name || trx.area || selectedAreaName.value,
                        name: item.material.name,
                        category: item.material.category,
                        area_stock: areaStock,
                        unit: item.unit || item.material.unit || 'pcs',
                        material: item.material,
                    });
                }
            });
        }
    });

    return items;
});

// Master material yang belum tercantum di daftar restock untuk optgroup alternatif
const nonRestockMaterials = computed(() => {
    const restockMatIds = new Set(availableRestockMaterials.value.map(i => i.material_id));
    return (props.materials || []).filter(m => !restockMatIds.has(m.id));
});

// Auto-BOM Fetcher
const fetchAutoBOM = async () => {
    isLoadingBOM.value = true;
    try {
        const response = await axios.get(route('infrastructure.work-orders.bom'), {
            params: {
                type: form.type,
                device_type: form.device_type,
                capacity: form.capacity,
                core_capacity: form.core_capacity,
                cable_length: form.cable_length_estimate,
                area_id: form.area_id,
            }
        });

        if (Array.isArray(response.data) && response.data.length > 0) {
            form.materials = response.data.map(item => ({
                material_id: item.material_id,
                material_transaction_item_id: item.material_transaction_item_id || null,
                trx_number: item.trx_number || '',
                name: item.name,
                quantity: item.quantity,
                unit: item.unit,
                available_stock: item.available_stock,
                is_sufficient: item.is_sufficient,
                note: item.note,
            }));
        }
    } catch (e) {
        console.error('Error fetching Auto-BOM:', e);
    } finally {
        isLoadingBOM.value = false;
    }
};

watch([() => form.type, () => form.device_type, () => form.capacity, () => form.core_capacity, () => form.cable_length_estimate, () => form.area_id], () => {
    if (currentStep.value >= 2) {
        fetchAutoBOM();
    }
});

const syncSplitDataIfApplicable = () => {
    if (isSplitMode.value) {
        const unit0 = form.split_units[0];
        const unit1 = form.split_units[1];
        if (unit0.location && !form.location) form.location = unit0.location;
        if (unit0.latitude && !form.planned_latitude) form.planned_latitude = unit0.latitude;
        if (unit0.longitude && !form.planned_longitude) form.planned_longitude = unit0.longitude;
        if (unit0.start_point && !form.start_point) form.start_point = unit0.start_point;
        if (unit0.end_point && !form.end_point) form.end_point = unit0.end_point;
        if (unit0.cable_pull && !form.cable_pull) form.cable_pull = unit0.cable_pull;

        const splitSummary = `[Split Unit 1: ${unit0.name || 'Unit A'} (${unit0.ratio} Port) - Lokasi: ${unit0.location || '-'}, GPS: ${unit0.latitude || '-'}/${unit0.longitude || '-'}, Kabel: ${unit0.start_point || '-'} -> ${unit0.end_point || '-'} (${unit0.cable_pull || 'Individu'})] | [Split Unit 2: ${unit1.name || 'Unit B'} (${unit1.ratio} Port) - Lokasi: ${unit1.location || '-'}, GPS: ${unit1.latitude || '-'}/${unit1.longitude || '-'}, Kabel: ${unit1.start_point || '-'} -> ${unit1.end_point || '-'} (${unit1.cable_pull || 'Individu'})]`;
        if (!form.notes.includes('[Split Unit 1:')) {
            form.notes = form.notes ? `${form.notes}\n${splitSummary}` : splitSummary;
        }
    }
};

const nextStep = () => {
    if (currentStep.value === 1) {
        syncSplitDataIfApplicable();
        fetchAutoBOM();
    }
    currentStep.value++;
};

const prevStep = () => {
    currentStep.value--;
};

const onMaterialChange = (row) => {
    if (!row.material_id) return;
    
    // Prioritaskan temukan item dari Order Toko area
    const restockItem = availableRestockMaterials.value.find(i => i.material_id == row.material_id);
    const masterMat = props.materials?.find(m => m.id == row.material_id);

    if (restockItem) {
        row.name = restockItem.name;
        row.unit = restockItem.unit;
        row.available_stock = restockItem.area_stock;
        row.trx_number = restockItem.trx_number;
        row.material_transaction_item_id = restockItem.id;
        if (!row.note || row.note === 'Material tambahan' || row.note === '-') {
            row.note = `Diambil dari Order Toko (${restockItem.trx_number})`;
        }
    } else if (masterMat) {
        row.name = masterMat.name;
        row.unit = masterMat.unit || 'pcs';
        row.available_stock = getAreaStock(masterMat.id);
        row.trx_number = '';
        row.material_transaction_item_id = null;
    }

    row.is_sufficient = (parseFloat(row.available_stock) || 0) >= (parseFloat(row.quantity) || 0);
};

const onQuantityChange = (row) => {
    row.is_sufficient = (parseFloat(row.available_stock) || 0) >= (parseFloat(row.quantity) || 0);
};

const addMaterialRow = (fromRestock = false) => {
    let pickedMatId = '';
    let pickedTrxNumber = '';
    let pickedTrxItemId = null;
    let pickedName = '';
    let pickedUnit = 'pcs';
    let pickedStock = 0;
    let pickedNote = 'Material tambahan';

    if (fromRestock && availableRestockMaterials.value.length > 0) {
        const currentMatIds = new Set(form.materials.map(m => m.material_id));
        const candidate = availableRestockMaterials.value.find(i => !currentMatIds.has(i.material_id)) || availableRestockMaterials.value[0];
        
        pickedMatId = candidate.material_id;
        pickedTrxNumber = candidate.trx_number;
        pickedTrxItemId = candidate.id;
        pickedName = candidate.name;
        pickedUnit = candidate.unit;
        pickedStock = candidate.area_stock;
        pickedNote = `Diambil dari Order Toko (${candidate.trx_number})`;
    } else if (props.materials && props.materials.length > 0) {
        const mat = props.materials[0];
        pickedMatId = mat.id;
        pickedName = mat.name;
        pickedUnit = mat.unit || 'pcs';
        pickedStock = getAreaStock(mat.id);
    }

    form.materials.push({
        material_id: pickedMatId,
        material_transaction_item_id: pickedTrxItemId,
        trx_number: pickedTrxNumber,
        name: pickedName,
        quantity: 1,
        unit: pickedUnit,
        available_stock: pickedStock,
        is_sufficient: (parseFloat(pickedStock) || 0) >= 1,
        note: pickedNote,
    });
};

const removeMaterialRow = (index) => {
    form.materials.splice(index, 1);
};

const submitErrorMessage = ref('');
const isSubmitting = ref(false);

const submit = () => {
    syncSplitDataIfApplicable();
    submitErrorMessage.value = '';

    // 1. Validasi / Auto-generate Nama Target
    if (!form.target_name || !form.target_name.trim()) {
        form.target_name = generateTargetName();
    }

    // 2. Validasi Area
    if (!form.area_id) {
        currentStep.value = 1;
        submitErrorMessage.value = 'Silakan pilih Wilayah / Area terlebih dahulu pada Langkah 1.';
        alert(submitErrorMessage.value);
        return;
    }

    // 3. Validasi Material
    if (!form.materials || form.materials.length === 0) {
        currentStep.value = 2;
        submitErrorMessage.value = 'Minimal 1 item material wajib dialokasikan pada Langkah 2.';
        alert(submitErrorMessage.value);
        return;
    }

    // Validasi setiap baris material
    for (let i = 0; i < form.materials.length; i++) {
        const m = form.materials[i];
        if (!m.material_id) {
            currentStep.value = 2;
            submitErrorMessage.value = `Baris material ke-${i + 1} belum memilih jenis material.`;
            alert(submitErrorMessage.value);
            return;
        }
        if (!m.quantity || parseFloat(m.quantity) <= 0) {
            currentStep.value = 2;
            submitErrorMessage.value = `Jumlah untuk material "${m.name || 'item ' + (i + 1)}" harus lebih dari 0.`;
            alert(submitErrorMessage.value);
            return;
        }
    }

    // 4. Validasi Tim Teknisi (Minimal 1 teknisi)
    if (!form.technician_ids || form.technician_ids.length === 0) {
        if (form.technician_id) {
            form.technician_ids = [form.technician_id];
        } else if (props.technicians && props.technicians.length > 0) {
            form.technician_ids = [props.technicians[0].id];
            form.technician_id = props.technicians[0].id;
        } else {
            submitErrorMessage.value = 'Silakan pilih minimal 1 teknisi pelaksana penugasan pada Langkah 3.';
            alert(submitErrorMessage.value);
            return;
        }
    }

    if (!form.technician_id && form.technician_ids.length > 0) {
        form.technician_id = form.technician_ids[0];
    }

    // 5. Validasi Tanggal
    if (!form.scheduled_date) {
        form.scheduled_date = new Date().toISOString().slice(0, 10);
    }

    isSubmitting.value = true;

    form.post(route('infrastructure.work-orders.store'), {
        preserveScroll: true,
        onError: (errors) => {
            console.error('Validation errors received:', errors);
            const errorList = Object.values(errors);
            submitErrorMessage.value = errorList.join(' • ');
            alert('Gagal menerbitkan penugasan karena data belum lengkap:\n\n• ' + errorList.join('\n• '));

            if (errors.target_name || errors.area_id || errors.odc_id || errors.olt_id || errors.capacity) {
                currentStep.value = 1;
            } else if (errors.materials || Object.keys(errors).some(k => k.startsWith('materials'))) {
                currentStep.value = 2;
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <AppLayout title="Buat Penugasan Pembangunan ODC / ODP">
        <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-sans">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('infrastructure.work-orders.index')"
                    class="text-xs font-bold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1 mb-2"
                >
                    <span>&larr;</span>
                    <span>Kembali ke Daftar Penugasan</span>
                </Link>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                    <span>⚡ Buat Penugasan Rollout Baru</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Sistem perancangan cerdas: kalkulator material otomatis (Auto-BOM), validasi relasi jaringan OLT/ODC, dan penunjukan teknisi.
                </p>
            </div>

            <!-- Wizard Step Navigation -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-xs mb-8">
                <div class="grid grid-cols-3 gap-2 text-center text-xs font-bold">
                    <div
                        class="p-3 rounded-2xl transition-all flex items-center justify-center gap-2"
                        :class="currentStep === 1 ? 'bg-indigo-600 text-white shadow-sm' : (currentStep > 1 ? 'bg-emerald-50 text-emerald-700' : 'text-slate-400 bg-slate-50')"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 1 ? 'bg-white/20' : (currentStep > 1 ? 'bg-emerald-200' : 'bg-slate-200')">1</span>
                        <span>Spesifikasi &amp; Jaringan</span>
                    </div>

                    <div
                        class="p-3 rounded-2xl transition-all flex items-center justify-center gap-2"
                        :class="currentStep === 2 ? 'bg-indigo-600 text-white shadow-sm' : (currentStep > 2 ? 'bg-emerald-50 text-emerald-700' : 'text-slate-400 bg-slate-50')"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 2 ? 'bg-white/20' : (currentStep > 2 ? 'bg-emerald-200' : 'bg-slate-200')">2</span>
                        <span>Auto-BOM Material</span>
                    </div>

                    <div
                        class="p-3 rounded-2xl transition-all flex items-center justify-center gap-2"
                        :class="currentStep === 3 ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 bg-slate-50'"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="currentStep === 3 ? 'bg-white/20' : 'bg-slate-200'">3</span>
                        <span>Penugasan &amp; Terbitkan</span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- ════ STEP 1: SPESIFIKASI & RELASI JARINGAN ════ -->
                <div v-show="currentStep === 1" class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span>📡</span>
                        <span>Langkah 1: Tipe Perangkat &amp; Titik Jaringan</span>
                    </h3>

                    <!-- Tipe Penugasan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label
                            class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex items-center gap-3"
                            :class="form.type === 'odp_deployment' ? 'border-indigo-600 bg-indigo-50/30' : 'border-slate-200 hover:border-slate-300'"
                        >
                            <input type="radio" v-model="form.type" value="odp_deployment" class="text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-bold text-slate-900 block text-sm">Pembangunan ODP Baru</span>
                                <span class="text-xs text-slate-500">Optical Distribution Point untuk distribusi port pelanggan.</span>
                            </div>
                        </label>

                        <label
                            class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex items-center gap-3"
                            :class="form.type === 'odc_deployment' ? 'border-indigo-600 bg-indigo-50/30' : 'border-slate-200 hover:border-slate-300'"
                        >
                            <input type="radio" v-model="form.type" value="odc_deployment" class="text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-bold text-slate-900 block text-sm">Pembangunan ODC Baru</span>
                                <span class="text-xs text-slate-500">Optical Distribution Cabinet untuk cabang feeder OLT.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Area & Target Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Area / Wilayah</label>
                            <select
                                v-model="form.area_id"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Target Perangkat (Auto-Generated)</label>
                            <input
                                v-model="form.target_name"
                                type="text"
                                placeholder="Contoh: ODP-ODC01-01"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-indigo-700 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Induk Jaringan ODC (OLT Induk & Port PON) -->
                    <div v-if="form.type === 'odc_deployment'" class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">OLT Induk</label>
                                <select
                                    v-model="form.olt_id"
                                    :disabled="!form.area_id"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50"
                                >
                                    <option value="">-- Pilih OLT di Area Ini --</option>
                                    <option v-for="o in filteredOlts" :key="o.id" :value="o.id">{{ o.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Port PON OLT</label>
                                <select
                                    v-if="selectedOltPonPorts.length > 0"
                                    v-model.number="form.pon_port"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option :value="null">-- Pilih Port PON (Opsional) --</option>
                                    <option v-for="port in selectedOltPonPorts" :key="port" :value="port">PON {{ port }}</option>
                                </select>
                                <input
                                    v-else
                                    v-model.number="form.pon_port"
                                    type="number"
                                    min="1"
                                    max="16"
                                    placeholder="Nomor PON (1 - 16)"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Preview Sinkronisasi VLAN & Mode Akses dari OLT -->
                        <div v-if="form.olt_id && form.pon_port" class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>Sinkronisasi VLAN & Mode Akses dari OLT (PON {{ form.pon_port }})</span>
                                </span>
                                <span class="text-[11px] text-blue-700 font-bold bg-blue-100 px-2 py-0.5 rounded-full">{{ currentModalPonVlans.length }} VLAN Terdeteksi</span>
                            </div>
                            <div v-if="currentModalPonVlans.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                                <span v-for="(v, vi) in currentModalPonVlans" :key="vi" class="px-2 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1.5 bg-white shadow-2xs" :class="getModeBadgeClass(v.access_mode)">
                                    <span class="font-mono font-bold">{{ v.vlan_id }}</span>
                                    <span class="text-slate-700">· {{ v.name }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border bg-slate-50">{{ v.access_mode }}</span>
                                </span>
                            </div>
                            <div v-else class="text-xs text-blue-600/80 italic">
                                Belum ada VLAN diset di OLT untuk PON {{ form.pon_port }}. ODC ini akan otomatis tersinkron begitu VLAN diset di OLT.
                            </div>
                        </div>
                    </div>

                    <!-- Induk Jaringan ODP (ODC Induk & Port Feeder) -->
                    <div v-else class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">ODC Induk</label>
                                <select
                                    v-model="form.odc_id"
                                    :disabled="!form.area_id"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50"
                                >
                                    <option value="">-- Pilih ODC di Area Ini --</option>
                                    <option v-for="odc in filteredOdcs" :key="odc.id" :value="odc.id">{{ odc.name }} (Kapasitas {{ odc.capacity }})</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Port Feeder ODC</label>
                                <input
                                    v-model.number="form.odc_splitter_port"
                                    type="number"
                                    min="1"
                                    max="48"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Preview Sinkronisasi VLAN & Mode Akses dari OLT melalui ODC -->
                        <div v-if="selectedOdcInfo" class="bg-purple-50/70 border border-purple-200 rounded-xl p-3.5 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-purple-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>Sinkronisasi VLAN & Mode Akses dari OLT (via {{ selectedOdcInfo.name }})</span>
                                </span>
                                <span class="text-[11px] text-purple-700 font-bold bg-purple-100 px-2 py-0.5 rounded-full">{{ currentModalOdpVlans.length }} VLAN Terdeteksi</span>
                            </div>
                            <div class="text-[11px] text-purple-800">
                                Induk OLT: <strong>{{ selectedOdcInfo.olt ? selectedOdcInfo.olt.name : '-' }}</strong>
                                <span v-if="selectedOdcInfo.pon_port"> · Port <strong>PON {{ selectedOdcInfo.pon_port }}</strong></span>
                            </div>
                            <div v-if="currentModalOdpVlans.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                                <span v-for="(v, vi) in currentModalOdpVlans" :key="vi" class="px-2 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1.5 bg-white shadow-2xs" :class="getModeBadgeClass(v.access_mode)">
                                    <span class="font-mono font-bold">{{ v.vlan_id }}</span>
                                    <span class="text-slate-700">· {{ v.name }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border bg-slate-50">{{ v.access_mode }}</span>
                                </span>
                            </div>
                            <div v-else class="text-xs text-purple-600/80 italic">
                                Belum ada VLAN diset di OLT untuk ODC ini. ODP akan otomatis tersinkron begitu VLAN diset di OLT.
                            </div>
                        </div>

                        <!-- Branch Mismatch Warning -->
                        <div v-if="isBranchMismatch" class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-3 text-amber-900">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <h4 class="text-xs font-bold text-amber-900">Cabang ODP tidak sesuai dengan ODC!</h4>
                                <p class="text-[11px] text-amber-800 mt-0.5">ODC terdaftar di cabang/wilayah yang berbeda dengan wilayah ODP yang Anda pilih. Pastikan data wilayah sudah benar.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tipe Perangkat, Kapasitas & Rasio Splitter -->
                    <!-- Layout ODP: 4 Kolom (Tipe, Kapasitas Port, Kapasitas Core, Rasio Splitter) -->
                    <div v-if="form.type === 'odp_deployment'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                <span>Tipe ODP</span>
                                <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">Tipe Boks</span>
                            </label>
                            <select
                                v-model="form.device_type"
                                @change="onDeviceTypeChange"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs"
                            >
                                <option value="Normal">Normal (Standar Tiang Solid)</option>
                                <option value="Split 2">Split 2 (Bercabang 2 Box / Cascading)</option>
                                <option value="Closure">Closure (Dome / Sambungan Inline)</option>
                                <option value="Pedestal">Pedestal (Box Bawah Tanah / Ground)</option>
                                <option value="Pole Mount">Pole Mount (Bracket Tiang Outdoor)</option>
                                <option value="Wall Mount">Wall Mount (Dinding Indoor / Koridor)</option>
                                <option value="FAT Mini">FAT Mini (Compact Micro 4-8 Port)</option>
                                <option value="Inline Closure">Inline Closure (Gantung Kabel Udara)</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                {{ form.device_type === 'Split 2' ? '⚡ Menggunakan splitter cascading' : 'Konstruksi fisik & model housing' }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                <span>Kapasitas Port</span>
                                <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">Port Drop</span>
                            </label>
                            <select
                                v-model.number="form.capacity"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800"
                            >
                                <option :value="2">2 Port</option>
                                <option :value="4">4 Port</option>
                                <option :value="8">8 Port (Standar)</option>
                                <option :value="12">12 Port</option>
                                <option :value="16">16 Port</option>
                                <option :value="24">24 Port</option>
                                <option :value="32">32 Port</option>
                                <option :value="48">48 Port</option>
                                <option :value="64">64 Port</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Jumlah port distribusi ke pelanggan
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                <span>Kapasitas Core</span>
                                <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">Core FO</span>
                            </label>
                            <select
                                v-model.number="form.core_capacity"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800"
                            >
                                <option :value="1">1 Core</option>
                                <option :value="2">2 Core</option>
                                <option :value="4">4 Core</option>
                                <option :value="6">6 Core</option>
                                <option :value="8">8 Core (Standar)</option>
                                <option :value="12">12 Core</option>
                                <option :value="24">24 Core</option>
                                <option :value="48">48 Core</option>
                                <option :value="96">96 Core</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Kapasitas core kabel / tray ODP
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Rasio Splitter</label>
                            <input
                                v-model="form.splitter_ratio"
                                type="text"
                                placeholder="Contoh: 1:8 PLC / Split 2"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Rasio modul pembagi sinyal</p>
                        </div>
                    </div>

                    <!-- Layout ODC: 4 Kolom (Tipe, Kapasitas Port, Kapasitas Core, Rasio Splitter) -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                <span>Tipe ODC</span>
                                <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">Tipe Boks</span>
                            </label>
                            <select
                                v-model="form.device_type"
                                @change="onDeviceTypeChange"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs"
                            >
                                <option value="Normal">Normal (Kabinet Standar Outdoor)</option>
                                <option value="Split">Split (ODC Split Bercabang Feeder)</option>
                                <option value="Pedestal">Pedestal (Kabinet Lantai Beton)</option>
                                <option value="Pole Mount">Pole Mount (ODC Tiang / Pole Mounted)</option>
                                <option value="Wall Mount">Wall Mount (ODC Dinding / Indoor Cabinet)</option>
                                <option value="Mini ODC">Mini ODC (Sub-ODC Feeder Compact)</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                {{ form.device_type === 'Split' ? '⚡ Menggunakan splitter cabang cascading' : 'Konstruksi fisik & model housing boks' }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                <span>Kapasitas Port</span>
                                <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">Port Feeder</span>
                            </label>
                            <select
                                v-model.number="form.capacity"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800"
                            >
                                <option :value="8">8 Port</option>
                                <option :value="12">12 Port</option>
                                <option :value="16">16 Port</option>
                                <option :value="24">24 Port (Standar)</option>
                                <option :value="32">32 Port</option>
                                <option :value="48">48 Port</option>
                                <option :value="72">72 Port</option>
                                <option :value="96">96 Port</option>
                                <option :value="144">144 Port</option>
                                <option :value="288">288 Port</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Jumlah port output / feeder distribusi ODC
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                <span>Kapasitas Core</span>
                                <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">Tray Core</span>
                            </label>
                            <select
                                v-model.number="form.core_capacity"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800"
                            >
                                <option :value="12">12 Core</option>
                                <option :value="24">24 Core (Standar)</option>
                                <option :value="48">48 Core</option>
                                <option :value="72">72 Core</option>
                                <option :value="96">96 Core</option>
                                <option :value="144">144 Core</option>
                                <option :value="288">288 Core</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Total kapasitas tray core ODC
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Rasio Splitter</label>
                            <input
                                v-model="form.splitter_ratio"
                                type="text"
                                placeholder="Contoh: 1:8 PLC / Split 1:2 FBT"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Rasio modul pembagi sinyal optik</p>
                        </div>
                    </div>

                    <!-- ════ UI KHUSUS SPLIT MODE (ODC Split / ODP Split 2) ════ -->
                    <div v-if="isSplitMode" class="space-y-4 pt-2">
                        <!-- Alert Split Banner -->
                        <div :class="form.type === 'odc_deployment' ? 'bg-indigo-50 border-indigo-200 text-indigo-900' : 'bg-purple-50 border-purple-200 text-purple-900'" class="px-4 py-3.5 rounded-2xl border text-sm flex items-start gap-3 shadow-2xs">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" :class="form.type === 'odc_deployment' ? 'text-indigo-600' : 'text-purple-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="flex-1">
                                <p class="font-bold text-xs uppercase tracking-wider">
                                    Mode Split: {{ form.type === 'odc_deployment' ? 'ODC' : 'ODP' }} dibagi menjadi 2 unit dengan konfigurasi khusus
                                </p>
                                <p class="text-xs mt-1" :class="totalSplitPorts > form.capacity ? 'text-rose-600 font-bold' : 'opacity-85'">
                                    Total Kapasitas Sub-Unit: <span class="font-bold font-mono">{{ totalSplitPorts }}</span> / <span class="font-bold font-mono">{{ form.capacity }}</span> Port
                                    <span v-if="totalSplitPorts > form.capacity" class="ml-1 bg-rose-100 text-rose-700 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase">(Melebihi kapasitas utama!)</span>
                                </p>
                            </div>
                        </div>

                        <!-- Tabs Split Units -->
                        <div class="flex gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200/70">
                            <button
                                type="button"
                                @click="activeTab = 0"
                                class="flex-1 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5"
                                :class="activeTab === 0 ? 'bg-white shadow-xs text-indigo-700' : 'text-slate-600 hover:text-slate-900'"
                            >
                                <span>Unit Induk</span>
                                <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold">{{ form.type === 'odc_deployment' ? form.split_units[0].ratio + ' Port' : '1:' + form.split_units[0].ratio }}</span>
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 1"
                                class="flex-1 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5"
                                :class="activeTab === 1 ? 'bg-white shadow-xs text-indigo-700' : 'text-slate-600 hover:text-slate-900'"
                            >
                                <span>Unit Lanjutan</span>
                                <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold">{{ form.type === 'odc_deployment' ? form.split_units[1].ratio + ' Port' : '1:' + form.split_units[1].ratio }}</span>
                            </button>
                        </div>

                        <!-- Tab Content: Detail Sub-Unit -->
                        <div class="border border-slate-200/80 rounded-2xl overflow-hidden bg-slate-50/50 p-4 sm:p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200/70">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">{{ activeTab === 0 ? 'A' : 'B' }}</span>
                                    <span>{{ activeTab === 0 ? (form.type === 'odc_deployment' ? 'ODC Induk' : 'ODP Induk') : (form.type === 'odc_deployment' ? 'ODC Lanjutan' : 'ODP Lanjutan') }}</span>
                                </h4>
                                <span class="text-xs font-semibold text-slate-500">Konfigurasi Jalur & Titik Sub-Unit</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Sub-Unit</label>
                                    <input
                                        v-model="form.split_units[activeTab].name"
                                        type="text"
                                        placeholder="Contoh: ODC-01-A atau ODP-01-A"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kapasitas Port / Rasio</label>
                                    <input
                                        v-model.number="form.split_units[activeTab].ratio"
                                        type="number"
                                        min="1"
                                        placeholder="Contoh: 8 atau 16"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alamat / Lokasi Sub-Unit</label>
                                <textarea
                                    v-model="form.split_units[activeTab].location"
                                    rows="2"
                                    placeholder="Masukkan alamat lengkap atau keterangan tiang penempatan unit ini"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                ></textarea>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700 uppercase">Titik Koordinat Sub-Unit</label>
                                    <button
                                        type="button"
                                        @click="getLocationForSplit(activeTab)"
                                        :disabled="isLocating"
                                        class="px-3 py-1 bg-white border border-slate-200 hover:border-indigo-300 text-slate-700 hover:text-indigo-600 rounded-xl text-xs font-bold flex items-center gap-1 shadow-2xs transition-all cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Auto GPS</span>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <input
                                        v-model="form.split_units[activeTab].latitude"
                                        type="text"
                                        placeholder="Latitude (-6.123456)"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                    <input
                                        v-model="form.split_units[activeTab].longitude"
                                        type="text"
                                        placeholder="Longitude (106.123456)"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Titik Awal Tarikan</label>
                                    <input
                                        v-model="form.split_units[activeTab].start_point"
                                        type="text"
                                        placeholder="Contoh: OLT Server / Tiang ODC-01"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Titik Akhir Tarikan</label>
                                    <input
                                        v-model="form.split_units[activeTab].end_point"
                                        type="text"
                                        placeholder="Contoh: Tiang No 12 RW 03"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Penarikan Kabel</label>
                                    <select
                                        v-model="form.split_units[activeTab].cable_pull"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="Individu">Individu (Kabel Dedicated)</option>
                                        <option value="Bersama">Bersama (Bundled / Sharing)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════ UI STANDAR / NORMAL MODE ════ -->
                    <div v-else class="space-y-4 pt-2">
                        <!-- Lokasi & Titik Koordinat (Auto GPS / Ketik Manual) -->
                        <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/60 pb-2.5">
                                <div>
                                    <label class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>📍</span>
                                        <span>Lokasi &amp; Titik Koordinat Target</span>
                                    </label>
                                    <p class="text-[11px] text-slate-500">
                                        Bisa <b>Auto GPS</b> (ambil dari posisi perangkat saat ini) atau <b>ketik manual</b> koordinat.
                                    </p>
                                </div>

                                <!-- Auto GPS Action Button -->
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="getAutoLocation"
                                        :disabled="isLocating"
                                        class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs transition-all disabled:opacity-60 cursor-pointer"
                                    >
                                        <svg v-if="isLocating" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>{{ isLocating ? 'Mendeteksi GPS...' : '📍 Auto GPS (Ambil Lokasi)' }}</span>
                                    </button>

                                    <a
                                        v-if="form.planned_latitude && form.planned_longitude"
                                        :href="`https://www.google.com/maps?q=${form.planned_latitude},${form.planned_longitude}`"
                                        target="_blank"
                                        class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 rounded-xl text-xs font-semibold flex items-center gap-1 shadow-2xs hover:border-indigo-300 transition-all"
                                        title="Lihat titik di Google Maps"
                                    >
                                        <span>🗺️ Cek Maps</span>
                                    </a>

                                    <button
                                        v-if="form.planned_latitude || form.planned_longitude"
                                        type="button"
                                        @click="clearCoordinates"
                                        class="px-2 py-1.5 text-slate-400 hover:text-rose-600 text-xs font-medium rounded-xl border border-slate-200 bg-white hover:bg-rose-50 hover:border-rose-200 transition-all cursor-pointer"
                                        title="Kosongkan koordinat"
                                    >
                                        ✕ Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Status Toast/Banner for GPS -->
                            <div v-if="gpsStatusMsg" class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-semibold flex items-center gap-1.5 animate-fadeIn">
                                <span>✅</span>
                                <span>{{ gpsStatusMsg }}</span>
                            </div>

                            <!-- 3 Input Fields Grid: Lokasi, Lat, Long -->
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-5">
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Deskripsi Tiang / Lokasi Target</label>
                                    <input
                                        v-model="form.location"
                                        type="text"
                                        placeholder="Contoh: Tiang PLN Depan Gang Mawar No 4"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                        <span>Latitude Rencana</span>
                                        <span class="text-[10px] text-slate-400 font-normal">Ketik manual</span>
                                    </label>
                                    <input
                                        v-model="form.planned_latitude"
                                        @paste="handleLatPaste"
                                        type="text"
                                        placeholder="-6.123456"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-medium focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                <div class="sm:col-span-4">
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                        <span>Longitude Rencana</span>
                                        <span class="text-[10px] text-slate-400 font-normal">Ketik manual</span>
                                    </label>
                                    <input
                                        v-model="form.planned_longitude"
                                        @paste="handleLngPaste"
                                        type="text"
                                        placeholder="106.123456"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-medium focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <!-- Jalur Tarikan Kabel & Metode Penarikan (Normal Mode) -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-slate-200/60">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Titik Awal (Start Point)</label>
                                    <input
                                        v-model="form.start_point"
                                        type="text"
                                        placeholder="Contoh: Server OLT / ODC Induk"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Titik Akhir Kabel (End Point)</label>
                                    <input
                                        v-model="form.end_point"
                                        type="text"
                                        placeholder="Contoh: Tiang Distribusi ODP"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Penarikan Kabel</label>
                                    <select
                                        v-model="form.cable_pull"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="Individu">Individu (Kabel Dedicated)</option>
                                        <option value="Bersama">Bersama (Bundled / Sharing)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-400 flex items-center justify-between pt-1">
                                <span>💡 <i>Klik tombol <b>Auto GPS</b> untuk mengambil koordinat perangkat saat ini secara otomatis, atau ketik manual / paste format <code>lat, long</code>.</i></span>
                                <span v-if="gpsAccuracy" class="text-emerald-600 font-medium whitespace-nowrap">Akurasi GPS: &plusmn;{{ gpsAccuracy }}m</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="nextStep"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-sm transition-all"
                        >
                            Lanjut ke Auto-BOM Material &rarr;
                        </button>
                    </div>
                </div>

                <!-- ════ STEP 2: AUTO-BOM KALKULATOR MATERIAL ════ -->
                <div v-show="currentStep === 2" class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span>📦</span>
                                <span>Langkah 2: Auto-BOM (Bill of Materials) &amp; Kesiapan Gudang</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kebutuhan material dihitung otomatis berdasarkan kapasitas {{ form.capacity }} port dan estimasi tarikan kabel.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 font-semibold">Estimasi Kabel:</span>
                            <input
                                v-model.number="form.cable_length_estimate"
                                type="number"
                                step="10"
                                class="w-20 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-indigo-700"
                            />
                            <span class="text-xs text-slate-500">Meter</span>
                        </div>
                    </div>

                    <!-- Info Bar: Sumber Inventory Gudang Area -->
                    <div class="p-3.5 bg-gradient-to-r from-amber-50/70 via-indigo-50/40 to-emerald-50/40 rounded-2xl border border-amber-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-200 text-amber-700 flex items-center justify-center text-sm font-bold shadow-2xs">
                                📦
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>Material diambil dari:</span>
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-900 rounded-md font-bold text-[11px] border border-amber-200">Inventory Gudang</span>
                                    <span class="text-indigo-700 font-extrabold">&bull; Area {{ selectedAreaName }}</span>
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Material diambil langsung dari persediaan Inventory Gudang & area terkait. Sisa material yang berlebih otomatis dikembalikan ke Inventory Gudang saat verifikasi penyelesaian pekerjaan.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-white border border-slate-200 rounded-xl text-slate-700 text-[11px] font-semibold shadow-2xs">
                                Persediaan Gudang: <b class="text-emerald-800">{{ (props.materials || []).length }} Item</b>
                            </span>
                        </div>
                    </div>

                    <!-- Material Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-2.5">Nama Material</th>
                                    <th class="px-3 py-2.5 w-24 text-center">Jumlah</th>
                                    <th class="px-3 py-2.5 w-20">Satuan</th>
                                    <th class="px-3 py-2.5 text-center">Stok Gudang</th>
                                    <th class="px-3 py-2.5">Keterangan</th>
                                    <th class="px-2 py-2.5 text-center w-12">Hapus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(m, idx) in form.materials" :key="idx" class="hover:bg-slate-50/50">
                                    <td class="px-4 py-2.5">
                                        <select
                                            v-model="m.material_id"
                                            @change="onMaterialChange(m)"
                                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <!-- Grup 1: Material dari Order Toko Area -->
                                            <optgroup v-if="availableRestockMaterials.length > 0" :label="`🛒 Order Toko (Restock Area ${selectedAreaName})`">
                                                <option v-for="ord in availableRestockMaterials" :key="'ord-' + ord.id" :value="ord.material_id">
                                                    {{ ord.name }} (PO: {{ ord.trx_number }}) — Stok: {{ ord.area_stock }} {{ ord.unit }}
                                                </option>
                                            </optgroup>
                                            <!-- Grup 2: Master Data Produk Lainnya -->
                                            <optgroup label="📦 Master Data Produk Lainnya">
                                                <option v-for="opt in nonRestockMaterials" :key="'mat-' + opt.id" :value="opt.id">
                                                    {{ opt.name }} — Stok Area: {{ getAreaStock(opt.id) }} {{ opt.unit }}
                                                </option>
                                            </optgroup>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input
                                            v-model.number="m.quantity"
                                            @input="onQuantityChange(m)"
                                            type="number"
                                            step="any"
                                            min="0.01"
                                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs text-center font-bold"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 font-semibold text-slate-600">{{ m.unit }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold border inline-flex items-center gap-1 shadow-2xs"
                                            :class="m.is_sufficient ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                            :title="m.is_sufficient ? 'Stok area mencukupi' : 'Stok di area tidak mencukupi! Kurang dari kebutuhan penugasan'"
                                        >
                                            <span>{{ m.is_sufficient ? '✓' : '⚠️' }}</span>
                                            <span>{{ m.available_stock }} {{ m.unit }}</span>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-500 text-[11px]">
                                        <div class="space-y-1">
                                            <div>{{ m.note }}</div>
                                            <div v-if="m.trx_number" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                <span>🛒 Restock:</span>
                                                <span>{{ m.trx_number }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2.5 text-center">
                                        <button
                                            type="button"
                                            @click="removeMaterialRow(idx)"
                                            class="text-rose-500 hover:text-rose-700 font-bold text-sm"
                                        >
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                        <div class="flex items-center gap-2">
                            <button
                                v-if="availableRestockMaterials.length > 0"
                                type="button"
                                @click="addMaterialRow(true)"
                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 active:bg-amber-200 text-amber-900 border border-amber-300 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer"
                                title="Tambah item material langsung dari Order Toko / Restock area ini"
                            >
                                <span>🛒 + Ambil dari Order Toko</span>
                            </button>

                            <button
                                type="button"
                                @click="addMaterialRow(false)"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-colors flex items-center gap-1 cursor-pointer"
                            >
                                <span>➕ Tambah Item Manual</span>
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="fetchAutoBOM"
                            class="text-xs text-indigo-600 hover:underline font-bold"
                        >
                            🔄 Reset ke Rekomendasi Auto-BOM
                        </button>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="prevStep"
                            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors"
                        >
                            &larr; Kembali
                        </button>
                        <button
                            type="button"
                            @click="nextStep"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-sm transition-all"
                        >
                            Lanjut ke Penugasan &rarr;
                        </button>
                    </div>
                </div>

                <!-- ════ STEP 3: PENUGASAN & JADWAL ════ -->
                <div v-show="currentStep === 3" class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span>👷</span>
                        <span>Langkah 3: Penunjukan Teknisi &amp; Jadwal Pelaksanaan</span>
                    </h3>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center gap-1.5">
                                    <span>🌟</span>
                                    <span>Lead Splicer / Koordinator Utama</span>
                                </label>
                                <select
                                    :value="form.technician_id"
                                    @change="setLeadTechnician(Number($event.target.value))"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900"
                                >
                                    <option v-for="t in technicians" :key="t.id" :value="t.id">
                                        {{ t.name }}
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Penanggung jawab utama atas kualitas redaman OPM & serah terima barang.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Target Tanggal Pengerjaan</label>
                                <input
                                    v-model="form.scheduled_date"
                                    type="date"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Estimasi jadwal penarikan kabel & splicing di tiang OSP.</p>
                            </div>
                        </div>

                        <!-- Multi-Select Tim Teknisi Tambahan -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase">
                                    👷 Anggota Tim Teknisi / Helper (Bisa Pilih Lebih Dari 1)
                                </label>
                                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                    {{ form.technician_ids.length }} Teknisi Terpilih
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5 p-3 bg-slate-50 rounded-2xl border border-slate-200/80">
                                <label
                                    v-for="t in technicians"
                                    :key="t.id"
                                    class="flex items-center gap-2 p-2.5 rounded-xl border text-xs cursor-pointer transition-all select-none"
                                    :class="form.technician_ids.includes(t.id) 
                                        ? (form.technician_id === t.id ? 'bg-amber-50/80 border-amber-300 text-amber-900 shadow-xs' : 'bg-white border-indigo-300 text-indigo-900 shadow-xs') 
                                        : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white'"
                                >
                                    <input
                                        type="checkbox"
                                        :value="t.id"
                                        :checked="form.technician_ids.includes(t.id)"
                                        @change="toggleTechnician(t.id)"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                                    />
                                    <div class="truncate">
                                        <div class="font-bold truncate flex items-center gap-1">
                                            <span>{{ t.name }}</span>
                                            <span v-if="form.technician_id === t.id" class="text-[10px] text-amber-600" title="Lead Splicer">🌟</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ form.technician_id === t.id ? 'Lead Splicer' : 'Anggota Tim' }}
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Rute Tarikan Kabel Awal & Akhir -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Titik Awal Tarikan (Start Point)</label>
                            <input
                                v-model="form.start_point"
                                type="text"
                                placeholder="Contoh: OLT Sentral / Tiang No 12"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Titik Akhir Tarikan (End Point)</label>
                            <input
                                v-model="form.end_point"
                                type="text"
                                placeholder="Contoh: Tiang ODP Depan Rumah No 5"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan &amp; Instruksi Khusus ke Tim</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            placeholder="Tuliskan catatan medan, instruksi izin RT/RW, atau penanda tiang khusus..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                        ></textarea>
                    </div>

                    <!-- Summary Ringkasan Box -->
                    <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 text-xs text-slate-700 space-y-1">
                        <span class="font-bold text-indigo-900 block mb-1">📋 Konfirmasi Terbit Penugasan:</span>
                        <div>
                            &bull; <b>Perangkat:</b> {{ form.target_name }} ({{ form.type === 'odc_deployment' ? 'ODC' : 'ODP' }} - Tipe {{ form.device_type }})
                            &bull; {{ form.capacity }} Port
                            <template v-if="form.core_capacity">
                                &bull; {{ form.core_capacity }} Core FO
                            </template>
                            <template v-if="form.planned_latitude && form.planned_longitude">
                                &bull; Koordinat: {{ form.planned_latitude }}, {{ form.planned_longitude }}
                            </template>
                        </div>
                        <div>&bull; <b>Material Dialokasikan:</b> {{ form.materials.length }} Item Material</div>
                        <div>&bull; <b>Alur Berikutnya:</b> Gudang akan menerima notifikasi penyiapan material &rarr; Surat Jalan Outbound &rarr; Teknisi pengerjaan lapangan &rarr; QC Redaman Optik.</div>
                    </div>

                    <!-- Error Notification Banner -->
                    <div v-if="submitErrorMessage || Object.keys(form.errors).length > 0" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1">
                        <div class="flex items-center gap-2 font-bold text-rose-900">
                            <span>⚠️</span>
                            <span>Penerbitan Penugasan Belum Berhasil:</span>
                        </div>
                        <p v-if="submitErrorMessage">{{ submitErrorMessage }}</p>
                        <ul v-if="Object.keys(form.errors).length > 0" class="list-disc pl-5 text-[11px] space-y-0.5">
                            <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                        </ul>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="prevStep"
                            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer"
                        >
                            &larr; Kembali
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing || isSubmitting"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <svg v-if="form.processing || isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ (form.processing || isSubmitting) ? 'Menerbitkan Penugasan...' : '🚀 Terbitkan Work Order Resmi' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
