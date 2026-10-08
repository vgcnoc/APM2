<script setup>
import { ref, onMounted, nextTick, computed, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    odcs: Array,
    odps: Array,
    customers: Array,
});

const mapEl = ref(null);
let map = null;
let L = null;
let layerGroups = {};
let myLocationMarker = null;
let myLocationCircle = null;
let nearbyLines = [];
let nearbyMarkers = [];

// State
const myLocation = ref(null);
const gpsLoading = ref(false);
const gpsError = ref('');
const searchQuery = ref('');
const searchRadius = ref(2); // km
const searchType = ref('odp');
const nearbyResults = ref([]);
const nearbyLoading = ref(false);
const showPanel = ref(false);
const mapStyle = ref('street'); // street | satellite
const gpsTracking = ref(false);
let watchId = null;

const layers = ref([
    { key: 'odc', label: 'ODC', color: '#f59e0b', visible: true, icon: '🟠', count: 0 },
    { key: 'odp', label: 'ODP', color: '#22c55e', visible: true, icon: '🟢', count: 0 },
    { key: 'customer', label: 'Pelanggan', color: '#3b82f6', visible: true, icon: '🔵', count: 0 },
    { key: 'reseller', label: 'Reseller', color: '#8b5cf6', visible: true, icon: '🟣', count: 0 },
]);

// Count
onMounted(() => {
    layers.value.find(l => l.key === 'odc').count = props.odcs?.length || 0;
    layers.value.find(l => l.key === 'odp').count = props.odps?.length || 0;
    layers.value.find(l => l.key === 'customer').count = props.customers?.filter(c => !c.is_reseller)?.length || 0;
    layers.value.find(l => l.key === 'reseller').count = props.customers?.filter(c => c.is_reseller)?.length || 0;
});

const totalMarkers = computed(() => layers.value.reduce((s, l) => s + l.count, 0));

// Icon Paths
const svgPaths = {
    odc: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2',
    odp: 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684z',
    customer: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    reseller: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    mylocation: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z',
};

const typeColors = {
    odc: '#f59e0b',
    odp: '#22c55e',
    customer: '#3b82f6',
    reseller: '#8b5cf6',
};

const typeLabels = {
    odc: 'ODC',
    odp: 'ODP',
    customer: 'Pelanggan',
    reseller: 'Reseller',
};

// Map Tiles
const tiles = {
    street: { url: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', label: 'Street' },
    satellite: { url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', label: 'Satellite' },
};
let tileLayer = null;

// Helper
const createIcon = (color, svgPath, size = 32) => {
    return L.divIcon({
        className: 'custom-map-marker',
        html: `
            <div class="radar-marker" style="width:${size}px;height:${size}px;background:${color};">
                <svg width="${size * 0.5}" height="${size * 0.5}" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${svgPath}"/></svg>
                <div class="radar-marker-tail" style="background:${color};"></div>
            </div>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size + 4],
        popupAnchor: [0, -(size + 4)],
    });
};

const formatDistance = (km) => {
    if (km < 1) return `${Math.round(km * 1000)} m`;
    return `${km.toFixed(2)} km`;
};

const getGoogleMapsUrl = (lat, lng) => `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;

// ───── GPS ─────────────────────────────────────────────
const getMyLocation = () => {
    if (!navigator.geolocation) {
        gpsError.value = 'Browser tidak mendukung GPS.';
        return;
    }
    gpsLoading.value = true;
    gpsError.value = '';

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const { latitude, longitude, accuracy } = pos.coords;
            myLocation.value = { lat: latitude, lng: longitude, accuracy };
            gpsLoading.value = false;
            placeMyLocation(latitude, longitude, accuracy);
        },
        (err) => {
            gpsLoading.value = false;
            if (err.code === 1) gpsError.value = 'Akses GPS ditolak. Izinkan lokasi di browser.';
            else if (err.code === 2) gpsError.value = 'Lokasi tidak tersedia.';
            else gpsError.value = 'Timeout mendapatkan lokasi.';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

const startTracking = () => {
    if (!navigator.geolocation) return;
    gpsTracking.value = true;
    watchId = navigator.geolocation.watchPosition(
        (pos) => {
            const { latitude, longitude, accuracy } = pos.coords;
            myLocation.value = { lat: latitude, lng: longitude, accuracy };
            placeMyLocation(latitude, longitude, accuracy, false);
        },
        () => {},
        { enableHighAccuracy: true, timeout: 15000 }
    );
};

const stopTracking = () => {
    gpsTracking.value = false;
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
};

const placeMyLocation = (lat, lng, accuracy, flyTo = true) => {
    if (!map || !L) return;

    if (myLocationMarker) map.removeLayer(myLocationMarker);
    if (myLocationCircle) map.removeLayer(myLocationCircle);

    const pulseIcon = L.divIcon({
        className: 'custom-map-marker',
        html: `
            <div class="my-location-marker">
                <div class="my-location-pulse"></div>
                <div class="my-location-dot"></div>
            </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
    });

    myLocationMarker = L.marker([lat, lng], { icon: pulseIcon, zIndexOffset: 9999 }).addTo(map);
    myLocationMarker.bindPopup(`
        <div class="font-sans">
            <div class="text-xs font-bold text-red-500 uppercase tracking-wider mb-1">📡 Lokasi Saya</div>
            <div class="text-xs text-gray-600 mb-1">Akurasi: ± ${Math.round(accuracy || 0)} meter</div>
            <div class="text-xs bg-gray-50 p-2 rounded border border-gray-100">📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}</div>
        </div>
    `);

    myLocationCircle = L.circle([lat, lng], {
        radius: accuracy || 50,
        color: '#ef4444',
        fillColor: '#ef4444',
        fillOpacity: 0.08,
        weight: 1,
        dashArray: '5,5',
    }).addTo(map);

    if (flyTo) map.flyTo([lat, lng], 16, { duration: 1.2 });
};

// ───── Nearby Search ───────────────────────────────────
const searchNearby = async () => {
    if (!myLocation.value) {
        getMyLocation();
        return;
    }
    nearbyLoading.value = true;
    showPanel.value = true;
    clearNearbyVisuals();

    try {
        const res = await axios.post('/network-radar/nearby', {
            lat: myLocation.value.lat,
            lng: myLocation.value.lng,
            radius: searchRadius.value,
            type: searchType.value,
        });
        nearbyResults.value = res.data.results;
        drawNearbyLines();
    } catch (err) {
        console.error('Nearby search failed:', err);
    } finally {
        nearbyLoading.value = false;
    }
};

const clearNearbyVisuals = () => {
    nearbyLines.forEach(l => map?.removeLayer(l));
    nearbyMarkers.forEach(m => map?.removeLayer(m));
    nearbyLines = [];
    nearbyMarkers = [];
};

const drawNearbyLines = () => {
    if (!map || !L || !myLocation.value) return;

    nearbyResults.value.forEach((item, idx) => {
        if (!item.latitude || !item.longitude) return;
        const color = typeColors[item._type] || '#6b7280';
        const isNearest = idx === 0;

        // Line from me to item
        const line = L.polyline(
            [[myLocation.value.lat, myLocation.value.lng], [item.latitude, item.longitude]],
            {
                color: color,
                weight: isNearest ? 4 : 2,
                opacity: isNearest ? 0.9 : 0.4,
                dashArray: isNearest ? null : '8,6',
            }
        ).addTo(map);
        nearbyLines.push(line);

        // Distance label on midpoint
        if (isNearest) {
            const midLat = (myLocation.value.lat + item.latitude) / 2;
            const midLng = (myLocation.value.lng + item.longitude) / 2;
            const label = L.marker([midLat, midLng], {
                icon: L.divIcon({
                    className: 'custom-map-marker',
                    html: `<div class="distance-badge" style="border-color: ${color}; color: ${color};">${formatDistance(item.distance)}</div>`,
                    iconSize: [80, 24],
                    iconAnchor: [40, 12],
                }),
            }).addTo(map);
            nearbyMarkers.push(label);
        }
    });
};

const flyToItem = (item) => {
    if (!map) return;
    map.flyTo([item.latitude, item.longitude], 18, { duration: 1 });
};

// ───── Map Style ───────────────────────────────────────
const switchMapStyle = () => {
    mapStyle.value = mapStyle.value === 'street' ? 'satellite' : 'street';
    if (tileLayer && map) {
        map.removeLayer(tileLayer);
        tileLayer = L.tileLayer(tiles[mapStyle.value].url, {
            attribution: '© Google',
            maxZoom: 24,
            maxNativeZoom: 19,
        }).addTo(map);
    }
};

// ───── Search Filter ──────────────────────────────────
const filteredResults = computed(() => {
    if (!searchQuery.value) return nearbyResults.value;
    const q = searchQuery.value.toLowerCase();
    return nearbyResults.value.filter(r =>
        (r.name && r.name.toLowerCase().includes(q)) ||
        (r.address && r.address.toLowerCase().includes(q)) ||
        (r.customer_code && r.customer_code.toLowerCase().includes(q))
    );
});

// ───── Map Init ───────────────────────────────────────
onMounted(async () => {
    await nextTick();
    L = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    map = L.map(mapEl.value, {
        center: [-6.2, 106.8],
        zoom: 12,
        zoomControl: false,
    });

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    tileLayer = L.tileLayer(tiles.street.url, {
        attribution: '© Google',
        maxZoom: 24,
        maxNativeZoom: 19,
    }).addTo(map);

    // Init layer groups
    layers.value.forEach(l => {
        layerGroups[l.key] = L.layerGroup().addTo(map);
    });

    const icons = {
        odc: createIcon('#f59e0b', svgPaths.odc),
        odp: createIcon('#22c55e', svgPaths.odp),
        customer: createIcon('#3b82f6', svgPaths.customer),
        reseller: createIcon('#8b5cf6', svgPaths.reseller),
    };

    // Plot ODC
    props.odcs.forEach(item => {
        if (!item.latitude || !item.longitude) return;
        const marker = L.marker([item.latitude, item.longitude], { icon: icons.odc });
        const portInfo = item.odps_count !== undefined ? `<div class="text-xs text-amber-600 font-semibold mb-1">🔌 ${item.odps_count} ODP terhubung</div>` : '';
        marker.bindPopup(`
            <div class="font-sans min-w-[200px]">
                <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">ODC Master</div>
                <h3 class="font-bold text-gray-800 text-base mb-1">${item.name}</h3>
                ${portInfo}
                <p class="text-xs text-gray-500 mb-2">${item.address || 'Tanpa Alamat'}</p>
                <div class="text-xs bg-gray-50 p-2 rounded border border-gray-100 flex items-center gap-2 mb-2">
                    📍 ${item.latitude}, ${item.longitude}
                </div>
                <a href="${getGoogleMapsUrl(item.latitude, item.longitude)}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800">
                    🗺️ Navigasi ke sini →
                </a>
            </div>
        `);
        marker.addTo(layerGroups['odc']);
    });

    // Plot ODP
    props.odps.forEach(item => {
        if (!item.latitude || !item.longitude) return;
        const marker = L.marker([item.latitude, item.longitude], { icon: icons.odp });
        const available = (item.total_ports || 0) - (item.used_ports || 0);
        const portBar = item.total_ports ? `
            <div class="mb-2">
                <div class="flex justify-between text-[10px] text-gray-500 mb-0.5 font-semibold">
                    <span>Port: ${item.used_ports || 0}/${item.total_ports}</span>
                    <span class="${available > 0 ? 'text-green-600' : 'text-red-600'}">${available > 0 ? available + ' tersedia' : 'PENUH'}</span>
                </div>
                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all ${available > 0 ? 'bg-green-500' : 'bg-red-500'}" style="width: ${item.total_ports ? Math.round((item.used_ports / item.total_ports) * 100) : 0}%"></div>
                </div>
            </div>
        ` : '';
        marker.bindPopup(`
            <div class="font-sans min-w-[200px]">
                <div class="flex items-center justify-between mb-1">
                    <div class="text-xs font-bold text-green-500 uppercase tracking-wider">ODP / Splitter</div>
                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded ${item.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">${item.status || '-'}</span>
                </div>
                <h3 class="font-bold text-gray-800 text-base mb-0.5">${item.name}</h3>
                <p class="text-[10px] text-gray-400 font-medium uppercase mb-2">Via ODC: ${item.odc?.name || '-'}</p>
                ${portBar}
                <p class="text-xs text-gray-500 mb-2">${item.address || 'Tanpa Alamat'}</p>
                <div class="text-xs bg-gray-50 p-2 rounded border border-gray-100 flex items-center gap-2 mb-2">
                    📍 ${item.latitude}, ${item.longitude}
                </div>
                <a href="${getGoogleMapsUrl(item.latitude, item.longitude)}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800">
                    🗺️ Navigasi ke sini →
                </a>
            </div>
        `);
        marker.addTo(layerGroups['odp']);
    });

    // Plot Customers & Resellers
    props.customers.forEach(item => {
        if (!item.latitude || !item.longitude) return;
        const isReseller = item.is_reseller;
        const groupKey = isReseller ? 'reseller' : 'customer';
        const marker = L.marker([item.latitude, item.longitude], { icon: icons[groupKey] });

        let statusBadge = '';
        if (item.status === 'active') statusBadge = '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-[10px] font-bold uppercase">Aktif</span>';
        else if (item.status === 'suspended') statusBadge = '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[10px] font-bold uppercase">Isolir</span>';
        else statusBadge = `<span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[10px] font-bold uppercase">${item.status}</span>`;

        marker.bindPopup(`
            <div class="font-sans min-w-[200px]">
                <div class="flex items-center justify-between mb-1">
                    <div class="text-xs font-bold ${isReseller ? 'text-purple-500' : 'text-blue-500'} uppercase tracking-wider">${isReseller ? 'Reseller' : 'Pelanggan'}</div>
                    ${statusBadge}
                </div>
                <h3 class="font-bold text-gray-800 text-base mb-1">${item.name} <span class="text-gray-400 text-xs font-medium">(${item.customer_code})</span></h3>
                <p class="text-[10px] text-gray-400 font-medium uppercase mb-2">ODP: ${item.odp?.name || '-'}</p>
                <p class="text-xs text-gray-500 mb-2">${item.address || 'Tanpa Alamat'}</p>
                <div class="text-xs bg-gray-50 p-2 rounded border border-gray-100 flex items-center gap-2 mb-2">
                    📍 ${item.latitude}, ${item.longitude}
                </div>
                <a href="${getGoogleMapsUrl(item.latitude, item.longitude)}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800">
                    🗺️ Navigasi ke sini →
                </a>
            </div>
        `);
        marker.addTo(layerGroups[groupKey]);
    });

    // Fit bounds
    const bounds = getBounds();
    if (bounds) map.fitBounds(bounds, { padding: [50, 50] });

    // Resize observer
    const resizeObserver = new ResizeObserver(() => {
        if (map) requestAnimationFrame(() => map.invalidateSize());
    });
    if (mapEl.value) resizeObserver.observe(mapEl.value);
});

onUnmounted(() => {
    stopTracking();
});

const getBounds = () => {
    let bounds = new L.LatLngBounds();
    let hasPoints = false;
    const addPoint = (lat, lng) => {
        if (lat && lng) { bounds.extend([lat, lng]); hasPoints = true; }
    };
    props.odcs.forEach(i => addPoint(i.latitude, i.longitude));
    props.odps.forEach(i => addPoint(i.latitude, i.longitude));
    props.customers.forEach(i => addPoint(i.latitude, i.longitude));
    return hasPoints ? bounds : null;
};

// Toggle Layer
const toggleLayer = (layer) => {
    if (layer.visible) {
        layerGroups[layer.key]?.addTo(map);
    } else {
        map?.removeLayer(layerGroups[layer.key]);
    }
};

</script>

<template>
    <Head title="Radar Titik Koordinat" />
    <AppLayout title="Radar Tikor" subtitle="Pemetaan ODC, ODP, Pelanggan & Reseller">
        <div class="h-[calc(100vh-6rem)] flex flex-col relative overflow-hidden bg-gray-900 rounded-2xl shadow-2xl">

            <!-- ═══ Map Container ═══ -->
            <div ref="mapEl" class="w-full h-full z-0"></div>

            <!-- ═══ Floating Top Bar ═══ -->
            <div class="absolute top-3 left-3 right-3 z-[400] pointer-events-none flex items-start gap-3">

                <!-- Title Card -->
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-100 p-3 pointer-events-auto shrink-0">
                    <h1 class="text-sm font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        </span>
                        Radar Jaringan
                    </h1>
                    <p class="text-[10px] text-gray-400 font-medium mt-0.5 ml-9">{{ totalMarkers }} titik terpetakan</p>
                </div>

                <!-- Spacer -->
                <div class="flex-1"></div>

                <!-- Layer Control -->
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-100 p-2 pointer-events-auto shrink-0">
                    <div class="px-2 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Layers</div>
                    <label v-for="layer in layers" :key="layer.key" class="flex items-center gap-2 px-2 py-1.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors">
                        <input type="checkbox" v-model="layer.visible" @change="toggleLayer(layer)"
                            class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition-all">
                        <span>{{ layer.icon }}</span>
                        <span class="text-xs font-semibold text-gray-700">{{ layer.label }}</span>
                        <span class="text-[10px] text-gray-400 font-medium ml-auto">({{ layer.count }})</span>
                    </label>
                </div>
            </div>

            <!-- ═══ Floating Action Buttons (Bottom Left) ═══ -->
            <div class="absolute bottom-4 left-3 z-[400] flex flex-col gap-2">

                <!-- My Location -->
                <button
                    @click="getMyLocation"
                    :disabled="gpsLoading"
                    class="group relative w-11 h-11 rounded-xl bg-white shadow-lg border border-gray-100 flex items-center justify-center text-gray-600 hover:text-red-500 hover:shadow-xl transition-all duration-200"
                    :class="{ 'ring-2 ring-red-400 text-red-500': myLocation }"
                    title="Lokasi Saya"
                >
                    <svg v-if="!gpsLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <svg v-else class="w-5 h-5 animate-spin text-red-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-[10px] font-semibold rounded-lg opacity-0 group-hover:opacity-100 whitespace-nowrap transition-opacity">Lokasi Saya</span>
                </button>

                <!-- Live Track Toggle -->
                <button
                    @click="gpsTracking ? stopTracking() : startTracking()"
                    class="group relative w-11 h-11 rounded-xl bg-white shadow-lg border border-gray-100 flex items-center justify-center transition-all duration-200"
                    :class="gpsTracking ? 'text-green-500 ring-2 ring-green-400' : 'text-gray-600 hover:text-green-500'"
                    title="Live Tracking"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/>
                    </svg>
                    <span v-if="gpsTracking" class="absolute top-1 right-1 w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-[10px] font-semibold rounded-lg opacity-0 group-hover:opacity-100 whitespace-nowrap transition-opacity">{{ gpsTracking ? 'Stop Tracking' : 'Live Tracking' }}</span>
                </button>

                <!-- Switch Map -->
                <button
                    @click="switchMapStyle"
                    class="group relative w-11 h-11 rounded-xl bg-white shadow-lg border border-gray-100 flex items-center justify-center text-gray-600 hover:text-indigo-500 transition-all duration-200"
                    title="Ganti Peta"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-[10px] font-semibold rounded-lg opacity-0 group-hover:opacity-100 whitespace-nowrap transition-opacity">{{ mapStyle === 'street' ? 'Satellite' : 'Street' }}</span>
                </button>

                <!-- Find Nearest -->
                <button
                    @click="showPanel = !showPanel"
                    class="group relative w-11 h-11 rounded-xl shadow-lg border flex items-center justify-center transition-all duration-200"
                    :class="showPanel ? 'bg-indigo-500 text-white border-indigo-400 ring-2 ring-indigo-300' : 'bg-white text-gray-600 border-gray-100 hover:text-indigo-500'"
                    title="Cari Terdekat"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-[10px] font-semibold rounded-lg opacity-0 group-hover:opacity-100 whitespace-nowrap transition-opacity">Cari Terdekat</span>
                </button>
            </div>

            <!-- ═══ GPS Error Toast ═══ -->
            <div v-if="gpsError" class="absolute bottom-4 left-1/2 -translate-x-1/2 z-[500] animate-bounce-in">
                <div class="bg-red-500 text-white px-4 py-2 rounded-xl shadow-xl text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    {{ gpsError }}
                    <button @click="gpsError = ''" class="ml-2 hover:text-red-200">✕</button>
                </div>
            </div>

            <!-- ═══ Nearby Search Panel ═══ -->
            <transition name="slide-panel">
                <div v-if="showPanel" class="absolute top-3 right-3 bottom-3 z-[401] w-80 sm:w-96 flex flex-col pointer-events-auto">
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-100 flex flex-col h-full overflow-hidden">

                        <!-- Panel Header -->
                        <div class="p-4 border-b border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-black text-gray-900 text-sm flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </span>
                                    Cari Terdekat
                                </h3>
                                <button @click="showPanel = false; clearNearbyVisuals()" class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <!-- GPS Status -->
                            <div v-if="myLocation" class="flex items-center gap-2 p-2 bg-green-50 rounded-xl mb-3 border border-green-100">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-[10px] text-green-700 font-semibold">GPS Aktif — {{ myLocation.lat.toFixed(5) }}, {{ myLocation.lng.toFixed(5) }}</span>
                            </div>
                            <div v-else class="flex items-center gap-2 p-2 bg-amber-50 rounded-xl mb-3 border border-amber-100">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                <span class="text-[10px] text-amber-700 font-semibold">Klik "Lokasi Saya" dulu untuk GPS</span>
                            </div>

                            <!-- Search Controls -->
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <select v-model="searchType" class="flex-1 text-xs font-semibold rounded-xl border-gray-200 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 py-2">
                                        <option value="odp">🟢 ODP Terdekat</option>
                                        <option value="odc">🟠 ODC Terdekat</option>
                                        <option value="customer">🔵 Pelanggan Terdekat</option>
                                        <option value="all">🌐 Semua</option>
                                    </select>
                                    <select v-model="searchRadius" class="w-24 text-xs font-semibold rounded-xl border-gray-200 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 py-2">
                                        <option :value="0.5">500m</option>
                                        <option :value="1">1 km</option>
                                        <option :value="2">2 km</option>
                                        <option :value="5">5 km</option>
                                        <option :value="10">10 km</option>
                                        <option :value="20">20 km</option>
                                    </select>
                                </div>
                                <button
                                    @click="searchNearby"
                                    :disabled="nearbyLoading"
                                    class="w-full py-2.5 rounded-xl text-xs font-bold text-white transition-all duration-200 flex items-center justify-center gap-2"
                                    :class="nearbyLoading ? 'bg-gray-400' : 'bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 shadow-lg shadow-indigo-200'"
                                >
                                    <svg v-if="!nearbyLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    {{ nearbyLoading ? 'Mencari...' : (myLocation ? '🔍 Cari Sekarang' : '📡 Aktifkan GPS & Cari') }}
                                </button>
                            </div>
                        </div>

                        <!-- Results -->
                        <div class="flex-1 overflow-y-auto p-2">
                            <!-- Search Filter -->
                            <div v-if="nearbyResults.length" class="p-2">
                                <input v-model="searchQuery" type="text" placeholder="Filter hasil..." class="w-full text-xs rounded-xl border-gray-200 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 py-2 px-3">
                            </div>

                            <!-- Empty State -->
                            <div v-if="!nearbyResults.length && !nearbyLoading" class="flex flex-col items-center justify-center p-8 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mb-3">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                </div>
                                <p class="text-xs text-gray-400 font-medium">Klik tombol "Cari Sekarang" untuk menemukan perangkat di sekitar Anda.</p>
                            </div>

                            <!-- Result Items -->
                            <div v-for="(item, idx) in filteredResults" :key="`${item._type}-${item.id}`"
                                @click="flyToItem(item)"
                                class="mx-1 mb-1.5 p-3 rounded-xl border cursor-pointer transition-all duration-200 hover:shadow-md"
                                :class="idx === 0 ? 'bg-gradient-to-r from-indigo-50 to-purple-50 border-indigo-200 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200'"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm"
                                        :style="`background-color: ${typeColors[item._type]}`">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="svgPaths[item._type]"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold uppercase tracking-wider" :style="`color: ${typeColors[item._type]}`">{{ typeLabels[item._type] }}</span>
                                            <span v-if="idx === 0" class="text-[9px] font-black text-indigo-600 bg-indigo-100 px-1.5 py-0.5 rounded">TERDEKAT</span>
                                        </div>
                                        <h4 class="font-bold text-gray-800 text-sm truncate">{{ item.name }}</h4>
                                        <p v-if="item.customer_code" class="text-[10px] text-gray-400 font-medium">({{ item.customer_code }})</p>

                                        <!-- ODP Port Info -->
                                        <div v-if="item._type === 'odp' && item.total_ports" class="mt-1">
                                            <div class="flex items-center gap-1 text-[10px] font-semibold">
                                                <span class="text-gray-500">Port: {{ item.used_ports || 0 }}/{{ item.total_ports }}</span>
                                                <span :class="(item.total_ports - (item.used_ports||0)) > 0 ? 'text-green-600' : 'text-red-600'">
                                                    {{ (item.total_ports - (item.used_ports||0)) > 0 ? `(${item.total_ports - (item.used_ports||0)} kosong)` : '(PENUH)' }}
                                                </span>
                                            </div>
                                        </div>

                                        <p v-if="item.address" class="text-[10px] text-gray-400 truncate mt-0.5">{{ item.address }}</p>

                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs font-black" :class="idx === 0 ? 'text-indigo-600' : 'text-gray-600'">
                                                📏 {{ formatDistance(item.distance) }}
                                            </span>
                                            <a :href="getGoogleMapsUrl(item.latitude, item.longitude)" target="_blank" @click.stop
                                                class="text-[10px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                                🗺️ Navigasi
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Result Count Footer -->
                        <div v-if="nearbyResults.length" class="p-3 border-t border-gray-100 bg-gray-50/50">
                            <p class="text-[10px] text-gray-400 font-semibold text-center">
                                Ditemukan {{ nearbyResults.length }} perangkat dalam radius {{ searchRadius }} km
                            </p>
                        </div>
                    </div>
                </div>
            </transition>

        </div>
    </AppLayout>
</template>

<style>
/* Map Marker Base */
.custom-map-marker { background: transparent; border: none; }

.radar-marker {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 2.5px solid white;
    box-shadow: 0 4px 12px -2px rgba(0,0,0,0.25);
    transition: transform 0.2s;
}
.radar-marker:hover { transform: scale(1.15); }
.radar-marker-tail {
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%) rotate(45deg);
    width: 8px;
    height: 8px;
}

/* My Location Pulse */
.my-location-marker {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.my-location-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #ef4444;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5);
    z-index: 2;
}
.my-location-pulse {
    position: absolute;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.2);
    animation: locationPulse 2s ease-out infinite;
    z-index: 1;
}
@keyframes locationPulse {
    0% { transform: scale(0.5); opacity: 1; }
    100% { transform: scale(2); opacity: 0; }
}

/* Distance Badge */
.distance-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    background: white;
    border: 2px solid;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    white-space: nowrap;
}

/* Popup Styles */
.leaflet-popup-content-wrapper {
    border-radius: 1rem;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.05);
}
.leaflet-popup-content { margin: 14px; }

/* Panel Transition */
.slide-panel-enter-active,
.slide-panel-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-panel-enter-from,
.slide-panel-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

/* Bounce In Animation */
@keyframes bounceIn {
    0% { transform: translateX(-50%) scale(0.8); opacity: 0; }
    50% { transform: translateX(-50%) scale(1.05); }
    100% { transform: translateX(-50%) scale(1); opacity: 1; }
}
.animate-bounce-in { animation: bounceIn 0.4s ease-out; }

/* Scrollbar */
.overflow-y-auto::-webkit-scrollbar { width: 4px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>
