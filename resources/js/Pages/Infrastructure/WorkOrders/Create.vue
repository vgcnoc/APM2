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
    splitter_ratio: '1:8',
    location: '',
    planned_latitude: '',
    planned_longitude: '',
    technician_id: props.technicians[0]?.id || '',
    technician_ids: props.technicians[0]?.id ? [props.technicians[0]?.id] : [],
    scheduled_date: new Date().toISOString().slice(0, 10),
    start_point: '',
    end_point: '',
    cable_length_estimate: 150,
    notes: '',
    materials: [],
});

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
watch([() => form.type, () => form.area_id, () => form.odc_id, () => form.olt_id, () => form.pon_port], () => {
    const area = props.areas.find(a => a.id == form.area_id);
    const areaCode = area ? area.name.substring(0, 3).toUpperCase() : 'DST';

    if (form.type === 'odc_deployment') {
        const olt = props.olts.find(o => o.id == form.olt_id);
        const oltName = olt ? olt.name.replace(/\s+/g, '') : 'OLT1';
        form.target_name = `ODC-${areaCode}-${oltName}-P${form.pon_port || 1}`;
    } else {
        const odc = props.odcs.find(o => o.id == form.odc_id);
        const odcName = odc ? odc.name.replace(/\s+/g, '') : 'ODC01';
        form.target_name = `ODP-${odcName}-01`;
    }
});

// Penanganan perubahan tipe perangkat (Normal, Split 2, Closure, Pedestal, Pole Mount)
const onDeviceTypeChange = () => {
    if (form.type === 'odp_deployment') {
        if (form.device_type === 'Split 2') {
            form.splitter_ratio = 'Split 2 (1:2 FBT)';
        } else if (form.device_type === 'Closure') {
            form.splitter_ratio = `Closure 1:${form.capacity}`;
        } else if (form.device_type === 'Pedestal') {
            form.splitter_ratio = `1:${form.capacity} (Pedestal)`;
        } else {
            form.splitter_ratio = `1:${form.capacity}`;
        }
    } else {
        if (form.device_type === 'Split') {
            form.splitter_ratio = 'Split 1:2 FBT';
        } else {
            form.splitter_ratio = '1:8 PLC';
        }
    }
};

watch(() => form.type, (newType) => {
    form.device_type = 'Normal';
    if (newType === 'odc_deployment') {
        form.capacity = 24;
        form.splitter_ratio = '1:8 PLC';
    } else {
        form.capacity = 8;
        form.splitter_ratio = '1:8';
    }
});

watch(() => form.capacity, (newCap) => {
    if (form.type === 'odp_deployment' && form.device_type === 'Normal') {
        form.splitter_ratio = `1:${newCap}`;
    }
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
                cable_length: form.cable_length_estimate,
                area_id: form.area_id,
            }
        });

        if (Array.isArray(response.data) && response.data.length > 0) {
            form.materials = response.data.map(item => ({
                material_id: item.material_id,
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

watch([() => form.type, () => form.device_type, () => form.capacity, () => form.cable_length_estimate, () => form.area_id], () => {
    if (currentStep.value >= 2) {
        fetchAutoBOM();
    }
});

const nextStep = () => {
    if (currentStep.value === 1) {
        fetchAutoBOM();
    }
    currentStep.value++;
};

const prevStep = () => {
    currentStep.value--;
};

const addMaterialRow = () => {
    form.materials.push({
        material_id: props.materials[0]?.id || '',
        name: props.materials[0]?.name || '',
        quantity: 1,
        unit: 'pcs',
        available_stock: 0,
        is_sufficient: true,
        note: 'Material tambahan',
    });
};

const removeMaterialRow = (index) => {
    form.materials.splice(index, 1);
};

const submit = () => {
    form.post(route('infrastructure.work-orders.store'));
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

                    <!-- Induk Jaringan (OLT / ODC) -->
                    <div v-if="form.type === 'odc_deployment'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">OLT Induk</label>
                            <select
                                v-model="form.olt_id"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            >
                                <option value="">-- Pilih OLT di Area Ini --</option>
                                <option v-for="o in filteredOlts" :key="o.id" :value="o.id">{{ o.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Port PON OLT</label>
                            <input
                                v-model.number="form.pon_port"
                                type="number"
                                min="1"
                                max="16"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            />
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">ODC Induk</label>
                            <select
                                v-model="form.odc_id"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs"
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
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            />
                        </div>
                    </div>

                    <!-- Tipe Perangkat, Kapasitas & Rasio Splitter -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1 flex items-center justify-between">
                                <span>{{ form.type === 'odc_deployment' ? 'Tipe ODC' : 'Tipe ODP' }}</span>
                                <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">Tipe Boks</span>
                            </label>
                            <select
                                v-model="form.device_type"
                                @change="onDeviceTypeChange"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs"
                            >
                                <template v-if="form.type === 'odp_deployment'">
                                    <option value="Normal">Normal (Standar Tiang Solid)</option>
                                    <option value="Split 2">Split 2 (Bercabang 2 Box / Cascading)</option>
                                    <option value="Closure">Closure (Dome / Sambungan Inline)</option>
                                    <option value="Pedestal">Pedestal (Box Bawah Tanah / Ground)</option>
                                </template>
                                <template v-else>
                                    <option value="Normal">Normal (Kabinet Standar Outdoor)</option>
                                    <option value="Split">Split (ODC Split Bercabang Feeder)</option>
                                    <option value="Pedestal">Pedestal (Kabinet Lantai Beton)</option>
                                    <option value="Pole Mount">Pole Mount (ODC Tiang / Pole Mounted)</option>
                                </template>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                {{ form.device_type === 'Split 2' || form.device_type === 'Split' ? '⚡ Menggunakan splitter cabang cascading' : 'Konstruksi fisik & model housing boks' }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                {{ form.type === 'odc_deployment' ? 'Kapasitas Core' : 'Kapasitas Port' }}
                            </label>
                            <select
                                v-model.number="form.capacity"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800"
                            >
                                <template v-if="form.type === 'odp_deployment'">
                                    <option :value="4">4 Port</option>
                                    <option :value="8">8 Port (Standar)</option>
                                    <option :value="16">16 Port</option>
                                    <option :value="24">24 Port</option>
                                    <option :value="32">32 Port</option>
                                </template>
                                <template v-else>
                                    <option :value="12">12 Core</option>
                                    <option :value="24">24 Core (Standar)</option>
                                    <option :value="48">48 Core</option>
                                    <option :value="96">96 Core</option>
                                    <option :value="144">144 Core</option>
                                </template>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                {{ form.type === 'odc_deployment' ? 'Total kapasitas tray core ODC' : 'Jumlah port distribusi ke pelanggan' }}
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
                            <p class="text-[11px] text-slate-400 mt-1">Rasio modul pembagi sinyal optik</p>
                        </div>
                    </div>

                    <!-- Lokasi & Koordinat Target -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Deskripsi Tiang / Lokasi</label>
                            <input
                                v-model="form.location"
                                type="text"
                                placeholder="Contoh: Tiang PLN Depan Gang Mawar No 4"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Latitude Rencana</label>
                            <input
                                v-model="form.planned_latitude"
                                type="text"
                                placeholder="-6.123456"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Longitude Rencana</label>
                            <input
                                v-model="form.planned_longitude"
                                type="text"
                                placeholder="106.123456"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono"
                            />
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
                                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs"
                                        >
                                            <option v-for="opt in materials" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input
                                            v-model.number="m.quantity"
                                            type="number"
                                            step="any"
                                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs text-center font-bold"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 font-semibold text-slate-600">{{ m.unit }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                            :class="m.is_sufficient ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        >
                                            {{ m.available_stock }} {{ m.unit }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-500 text-[11px]">{{ m.note }}</td>
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

                    <div class="flex items-center justify-between pt-2">
                        <button
                            type="button"
                            @click="addMaterialRow"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors flex items-center gap-1"
                        >
                            <span>➕ Tambah Item Material</span>
                        </button>

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
                        <div>&bull; <b>Perangkat:</b> {{ form.target_name }} ({{ form.type === 'odc_deployment' ? 'ODC' : 'ODP' }} - Tipe {{ form.device_type }}) &bull; {{ form.capacity }} {{ form.type === 'odc_deployment' ? 'Core' : 'Port' }}</div>
                        <div>&bull; <b>Material Dialokasikan:</b> {{ form.materials.length }} Item Material</div>
                        <div>&bull; <b>Alur Berikutnya:</b> Gudang akan menerima notifikasi penyiapan material &rarr; Surat Jalan Outbound &rarr; Teknisi pengerjaan lapangan &rarr; QC Redaman Optik.</div>
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
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5"
                        >
                            <span>🚀 Terbitkan Work Order Resmi</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
