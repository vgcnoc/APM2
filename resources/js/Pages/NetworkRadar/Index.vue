<script setup>
import { ref, onMounted, nextTick, shallowRef, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odcs: Array,
    odps: Array,
    customers: Array,
});

const mapEl = ref(null);
let map = null;
let layerGroups = {};

const layers = ref([
    { key: 'odc', label: 'ODC', color: '#f59e0b', visible: true, icon: '🟠' },
    { key: 'odp', label: 'ODP', color: '#22c55e', visible: true, icon: '🟢' },
    { key: 'customer', label: 'Pelanggan', color: '#3b82f6', visible: true, icon: '🔵' },
    { key: 'reseller', label: 'Reseller', color: '#8b5cf6', visible: true, icon: '🟣' },
]);

// Helper for map bounding box
const getBounds = (L) => {
    let bounds = new L.LatLngBounds();
    let hasPoints = false;

    const addPoint = (lat, lng) => {
        if (lat && lng) {
            bounds.extend([lat, lng]);
            hasPoints = true;
        }
    };

    props.odcs.forEach(i => addPoint(i.latitude, i.longitude));
    props.odps.forEach(i => addPoint(i.latitude, i.longitude));
    props.customers.forEach(i => addPoint(i.latitude, i.longitude));

    return hasPoints ? bounds : null;
};

// Map init
onMounted(async () => {
    await nextTick();
    const L = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    map = L.map(mapEl.value, {
        center: [-6.2, 106.8],
        zoom: 12,
        zoomControl: false,
    });

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        attribution: '© Google',
        maxZoom: 24,
        maxNativeZoom: 19,
    }).addTo(map);

    // Init layer groups
    layers.value.forEach(l => {
        layerGroups[l.key] = L.layerGroup().addTo(map);
    });

    // Custom Icon helper
    const createIcon = (color, svgPath) => {
        return L.divIcon({
            className: 'custom-map-marker',
            html: `
                <div class="relative flex items-center justify-center w-8 h-8 rounded-full shadow-lg" style="background-color: ${color}; border: 2px solid white;">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${svgPath}"/></svg>
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rotate-45" style="background-color: ${color};"></div>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 36],
            popupAnchor: [0, -32],
        });
    };

    const icons = {
        odc: createIcon('#f59e0b', 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2'),
        odp: createIcon('#22c55e', 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684z'),
        customer: createIcon('#3b82f6', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'),
        reseller: createIcon('#8b5cf6', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'),
    };

    // Plot ODC
    props.odcs.forEach(item => {
        if (!item.latitude || !item.longitude) return;
        const marker = L.marker([item.latitude, item.longitude], { icon: icons.odc });
        marker.bindPopup(`
            <div class="font-sans">
                <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">ODC Master</div>
                <h3 class="font-bold text-gray-800 text-base mb-1">${item.name}</h3>
                <p class="text-xs text-gray-500 mb-2">${item.address || 'Tanpa Alamat'}</p>
                <div class="text-xs bg-gray-50 p-2 rounded border border-gray-100 flex items-center gap-2">
                    📍 ${item.latitude}, ${item.longitude}
                </div>
            </div>
        `);
        marker.addTo(layerGroups['odc']);
    });

    // Plot ODP
    props.odps.forEach(item => {
        if (!item.latitude || !item.longitude) return;
        const marker = L.marker([item.latitude, item.longitude], { icon: icons.odp });
        marker.bindPopup(`
            <div class="font-sans">
                <div class="text-xs font-bold text-green-500 uppercase tracking-wider mb-1">ODP / Splitter</div>
                <h3 class="font-bold text-gray-800 text-base mb-1">${item.name}</h3>
                <p class="text-[10px] text-gray-400 font-medium uppercase mb-2">Via ODC: ${item.odc?.name || '-'}</p>
                <p class="text-xs text-gray-500 mb-2">${item.address || 'Tanpa Alamat'}</p>
                <div class="text-xs bg-gray-50 p-2 rounded border border-gray-100 flex items-center gap-2">
                    📍 ${item.latitude}, ${item.longitude}
                </div>
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
            <div class="font-sans">
                <div class="flex items-center justify-between mb-1">
                    <div class="text-xs font-bold ${isReseller ? 'text-purple-500' : 'text-blue-500'} uppercase tracking-wider">${isReseller ? 'Reseller' : 'Pelanggan'}</div>
                    ${statusBadge}
                </div>
                <h3 class="font-bold text-gray-800 text-base mb-1">${item.name} <span class="text-gray-400 text-xs font-medium">(${item.customer_code})</span></h3>
                <p class="text-[10px] text-gray-400 font-medium uppercase mb-2">Tersambung ke: ${item.odp?.name || 'Langsung'}</p>
                <p class="text-xs text-gray-500 mb-2">${item.address || 'Tanpa Alamat'}</p>
                <div class="text-xs bg-gray-50 p-2 rounded border border-gray-100 flex items-center gap-2">
                    📍 ${item.latitude}, ${item.longitude}
                </div>
            </div>
        `);
        marker.addTo(layerGroups[groupKey]);
    });

    const bounds = getBounds(L);
    if (bounds) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }
    
    // Resize Observer for map
    const resizeObserver = new ResizeObserver(() => {
        if (map) {
            requestAnimationFrame(() => map.invalidateSize());
        }
    });
    if (mapEl.value) resizeObserver.observe(mapEl.value);
});

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
    <AppLayout>
        <div class="h-[calc(100vh-4rem)] flex flex-col relative overflow-hidden bg-white">
            
            <!-- Floating Header -->
            <div class="absolute top-4 left-4 right-4 z-[400] pointer-events-none flex items-start justify-between">
                
                <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-lg border border-white/20 p-4 pointer-events-auto max-w-sm">
                    <h1 class="text-lg font-black text-gray-900 tracking-tight flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Radar Jaringan
                    </h1>
                    <p class="text-xs text-gray-500 font-medium">
                        Pemetaan koordinat ODC, ODP, Pelanggan, dan Reseller secara visual.
                    </p>
                </div>
                
                <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-lg border border-white/20 p-2 pointer-events-auto flex flex-col gap-1">
                    <div class="px-3 py-1.5 text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 mb-1">Layers</div>
                    <label v-for="layer in layers" :key="layer.key" class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 rounded-xl cursor-pointer transition-colors">
                        <input 
                            type="checkbox" 
                            v-model="layer.visible" 
                            @change="toggleLayer(layer)"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 transition-all"
                        >
                        <div class="flex items-center gap-2 flex-1">
                            <span>{{ layer.icon }}</span>
                            <span class="text-sm font-semibold text-gray-700">{{ layer.label }}</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Map Container -->
            <div ref="mapEl" class="w-full h-full z-0"></div>
            
        </div>
    </AppLayout>
</template>

<style>
.custom-map-marker {
    background: transparent;
    border: none;
}
.leaflet-popup-content-wrapper {
    border-radius: 1rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0,0,0,0.05);
}
.leaflet-popup-content {
    margin: 16px;
}
</style>
