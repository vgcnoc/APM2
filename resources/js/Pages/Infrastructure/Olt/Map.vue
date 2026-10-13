<template>
    <AppLayout title="Peta Sebaran Multi OLT" subtitle="Pemetaan Geografis Titik Lokasi Seluruh Perangkat OLT / Shelter / POP Jaringan">
        <div class="space-y-6">
            <!-- Nav Tabs Multi OLT -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto">
                <Link href="/olts" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>1. Data & Perangkat OLT</span>
                </Link>
                <Link href="/olts/pon-monitor" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>2. Monitoring Port PON</span>
                </Link>
                <Link href="/olts/map" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shrink-0 bg-sky-600 text-white shadow-sm shadow-sky-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>3. Peta Sebaran OLT</span>
                </Link>
                <Link href="/olts/capacity" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    <span>4. Kapasitas & Utilisasi</span>
                </Link>
            </div>

            <!-- Layout Peta & Sidebar OLT -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar Daftar OLT -->
                <div class="lg:col-span-1 space-y-4">
                    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-black text-sm text-gray-900">Titik Shelter OLT</h3>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-800">
                                {{ olts.length }} Unit
                            </span>
                        </div>

                        <!-- Filter Area -->
                        <select v-model="selectedAreaId" class="w-full text-xs rounded-xl border-gray-200 focus:border-sky-500 mb-3 bg-gray-50/50">
                            <option value="">Semua Wilayah</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>

                        <!-- List OLT -->
                        <div class="space-y-2 max-h-[550px] overflow-y-auto pr-1">
                            <div 
                                v-for="olt in filteredOlts" 
                                :key="olt.id" 
                                @click="focusOlt(olt)" 
                                class="p-3 rounded-xl border transition-all cursor-pointer hover:shadow-sm"
                                :class="selectedOltId === olt.id ? 'border-sky-500 bg-sky-50/50 shadow-sm' : 'border-gray-100 hover:border-gray-200'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-gray-900">{{ olt.name }}</span>
                                    <span class="w-2 h-2 rounded-full" :class="olt.status === 'active' ? 'bg-emerald-500' : 'bg-gray-300'"></span>
                                </div>
                                <div class="text-[11px] text-gray-500 mt-1 font-mono truncate">
                                    IP: {{ olt.ip_address || 'N/A' }}
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5 flex justify-between">
                                    <span>{{ olt.brand || 'Brand N/A' }}</span>
                                    <span class="text-sky-700 font-bold">{{ olt.total_pon_ports }} Port PON</span>
                                </div>
                                <div v-if="olt.location" class="text-[10px] text-gray-500 mt-1 truncate">
                                    📍 {{ olt.location }}
                                </div>
                            </div>

                            <div v-if="filteredOlts.length === 0" class="text-center py-8 text-xs text-gray-400">
                                Tidak ada OLT berkoordinat pada filter ini.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kontainer Peta Leaflet -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden relative">
                        <!-- Toolbar Peta -->
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/70 flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 animate-pulse"></span>
                                <span class="font-bold text-gray-700">Peta Sebaran OLT (OpenStreetMap / Leaflet)</span>
                            </div>
                            <div class="text-gray-500 text-[11px]">
                                Klik marker OLT untuk melihat detail kapasitas & ODC
                            </div>
                        </div>

                        <!-- Map Element -->
                        <div id="olt-map" class="w-full h-[600px] z-10"></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    olts: Array,
    areas: Array,
});

const selectedAreaId = ref('');
const selectedOltId = ref(null);
let map = null;
let markersLayer = null;

const filteredOlts = computed(() => {
    if (!selectedAreaId.value) return props.olts || [];
    return (props.olts || []).filter(o => o.area_id == selectedAreaId.value);
});

const initMap = () => {
    // Default center Indonesia atau OLT pertama
    let centerLat = -6.2088;
    let centerLng = 106.8456;
    let zoomLevel = 12;

    const firstValid = (props.olts || []).find(o => o.latitude && o.longitude);
    if (firstValid) {
        centerLat = parseFloat(firstValid.latitude);
        centerLng = parseFloat(firstValid.longitude);
    }

    map = L.map('olt-map').setView([centerLat, centerLng], zoomLevel);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
    renderMarkers();
};

const renderMarkers = () => {
    if (!markersLayer) return;
    markersLayer.clearLayers();

    const bounds = [];

    filteredOlts.value.forEach(olt => {
        if (!olt.latitude || !olt.longitude) return;

        const lat = parseFloat(olt.latitude);
        const lng = parseFloat(olt.longitude);
        bounds.push([lat, lng]);

        // Custom OLT Marker Icon
        const iconHtml = `
            <div style="background:#0284c7; color:#fff; width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.3); border:2px solid #fff;">
                <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        `;

        const customIcon = L.divIcon({
            html: iconHtml,
            className: 'custom-olt-pin',
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });

        const popupHtml = `
            <div style="font-family:sans-serif; min-width:200px; padding:4px;">
                <div style="font-size:10px; font-weight:bold; color:#0284c7; text-transform:uppercase;">CENTRAL OLT SHELTER</div>
                <div style="font-size:14px; font-weight:bold; color:#111; margin-top:2px;">${olt.name}</div>
                <div style="font-size:11px; color:#555; font-family:monospace;">IP: ${olt.ip_address || '-'}</div>
                <div style="font-size:11px; color:#555; margin-top:4px;">Brand: <strong>${olt.brand || '-'}</strong> (${olt.model || '-'})</div>
                <div style="font-size:11px; color:#555;">Port PON: <strong>${olt.total_pon_ports} Port</strong> · ODC: <strong>${olt.odcs ? olt.odcs.length : 0} Unit</strong></div>
                <div style="font-size:11px; color:#059669; font-weight:bold; margin-top:4px;">✓ ${olt.total_onts || 0} Pelanggan ONT Terhubung</div>
                <div style="margin-top:8px; border-top:1px solid #eee; padding-top:6px;">
                    <a href="/olts/${olt.id}" style="font-size:11px; color:#2563eb; text-decoration:none; font-weight:bold;">Buka Detail OLT &rarr;</a>
                </div>
            </div>
        `;

        const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(popupHtml);
        markersLayer.addLayer(marker);
    });

    if (bounds.length > 0 && map) {
        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
    }
};

const focusOlt = (olt) => {
    selectedOltId.value = olt.id;
    if (map && olt.latitude && olt.longitude) {
        map.flyTo([parseFloat(olt.latitude), parseFloat(olt.longitude)], 15, {
            duration: 1.2
        });
    }
};

onMounted(() => {
    nextTick(() => {
        initMap();
    });
});
</script>
