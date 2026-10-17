<script setup>
import { ref, onMounted, nextTick, computed, onUnmounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    odcs: { type: Array, default: () => [] },
    odps: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    employeeActivities: { type: Array, default: () => [] },
    initLat: [String, Number],
    initLng: [String, Number],
});

// Navigation
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/dashboard';
    }
};

// Tabs: 'radar' | 'karyawan'
const activeTab = ref('radar');

// Form state
const inputLat = ref('');
const inputLng = ref('');
const searchRadiusMeters = ref(5000);
const checkOdc = ref(true);
const checkOdp = ref(true);
const checkPelanggan = ref(true);
const showMapTiles = ref(true);
const showConnections = ref(true);

// Filters
const filterCabang = ref('');
const filterOdc = ref('');
const filterOdp = ref('');
const filterStatusPelanggan = ref('');

// Filter options
const filteredOdcOptions = computed(() => {
    if (!filterCabang.value) return props.odcs;
    return props.odcs.filter(o => o.area_id == filterCabang.value);
});

const filteredOdpOptions = computed(() => {
    let list = props.odps;
    if (filterCabang.value) {
        list = list.filter(o => o.area_id == filterCabang.value);
    }
    if (filterOdc.value) {
        list = list.filter(o => o.odc_id == filterOdc.value);
    }
    return list;
});

// GPS & Map State
const gpsLoading = ref(false);
const gpsError = ref('');
const myLocation = ref(null);
const mapEl = ref(null);
let map = null;
let L = null;
let tileLayer = null;
let clusterGroup = null;
let myLocationMarker = null;
let myLocationCircle = null;
let connectionsLayerGroup = null;
let routeLayer = null;
let routeBadge = null;

// Helper: Haversine distance in meters
const calculateDistanceMeters = (lat1, lon1, lat2, lon2) => {
    if (!lat1 || !lon1 || !lat2 || !lon2) return 999999999;
    const R = 6371e3; // meters
    const phi1 = (lat1 * Math.PI) / 180;
    const phi2 = (lat2 * Math.PI) / 180;
    const deltaPhi = ((lat2 - lat1) * Math.PI) / 180;
    const deltaLambda = ((lon2 - lon1) * Math.PI) / 180;

    const a =
        Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2) +
        Math.cos(phi1) * Math.cos(phi2) * Math.sin(deltaLambda / 2) * Math.sin(deltaLambda / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
};

const formatDistance = (meters) => {
    if (meters < 1000) return `${Math.round(meters)} m`;
    return `${(meters / 1000).toFixed(2)} km`;
};

const formatTime = (seconds) => {
    if (seconds < 60) return `${Math.round(seconds)} dtk`;
    const min = Math.round(seconds / 60);
    if (min < 60) return `${min} mnt`;
    const hr = Math.floor(min / 60);
    const m = min % 60;
    return `${hr}j ${m}m`;
};

const getGoogleMapsUrl = (lat, lng) => `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;

// Parse Coordinates (handle comma or dot)
const parseCoordinate = (val) => {
    if (val === null || val === undefined || val === '') return null;
    if (typeof val === 'number') return val;
    const clean = String(val).trim().replace(',', '.');
    const num = parseFloat(clean);
    return isNaN(num) ? null : num;
};

// Filtered Lists
const visibleOdcs = computed(() => {
    if (!checkOdc.value) return [];
    return props.odcs.filter(item => {
        if (!item.latitude || !item.longitude) return false;
        if (filterCabang.value && item.area_id != filterCabang.value) return false;
        if (filterOdc.value && item.id != filterOdc.value) return false;

        // Radius filter if user location is set
        if (myLocation.value) {
            const dist = calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, parseFloat(item.latitude), parseFloat(item.longitude));
            if (dist > searchRadiusMeters.value) return false;
        }
        return true;
    });
});

const visibleOdps = computed(() => {
    if (!checkOdp.value) return [];
    return props.odps.filter(item => {
        if (!item.latitude || !item.longitude) return false;
        if (filterCabang.value && item.area_id != filterCabang.value) return false;
        if (filterOdc.value && item.odc_id != filterOdc.value) return false;
        if (filterOdp.value && item.id != filterOdp.value) return false;

        if (myLocation.value) {
            const dist = calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, parseFloat(item.latitude), parseFloat(item.longitude));
            if (dist > searchRadiusMeters.value) return false;
        }
        return true;
    });
});

const visibleCustomers = computed(() => {
    if (!checkPelanggan.value) return [];
    return props.customers.filter(item => {
        if (!item.latitude || !item.longitude) return false;
        if (filterCabang.value && item.area_id != filterCabang.value) return false;
        if (filterOdc.value && item.odc_id != filterOdc.value) return false;
        if (filterOdp.value && item.odp_id != filterOdp.value) return false;

        if (filterStatusPelanggan.value) {
            if (filterStatusPelanggan.value === 'gratis') {
                if (item.service_status !== 'gratis') return false;
            } else if (filterStatusPelanggan.value === 'free_home') {
                if (item.service_status !== 'free_home') return false;
            } else if (filterStatusPelanggan.value === 'terminated') {
                if (!['terminated', 'canceled', 'inactive'].includes(item.status)) return false;
            } else {
                if (item.status !== filterStatusPelanggan.value) return false;
            }
        }

        if (myLocation.value) {
            const dist = calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, parseFloat(item.latitude), parseFloat(item.longitude));
            if (dist > searchRadiusMeters.value) return false;
        }
        return true;
    });
});

const visibleEmployees = computed(() => {
    return props.employeeActivities.filter(item => {
        if (!item.latitude || !item.longitude) return false;
        if (myLocation.value) {
            const dist = calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, parseFloat(item.latitude), parseFloat(item.longitude));
            if (dist > searchRadiusMeters.value) return false;
        }
        return true;
    });
});

// Stats Counts
const stats = computed(() => {
    return {
        odc: visibleOdcs.value.length,
        odp: visibleOdps.value.length,
        pelanggan: visibleCustomers.value.length,
    };
});

const totalVisiblePoints = computed(() => {
    if (activeTab.value === 'karyawan') {
        return visibleEmployees.value.length;
    }
    return visibleOdcs.value.length + visibleOdps.value.length + visibleCustomers.value.length;
});

// GPS Location Detection
const getMyLocation = () => {
    if (!navigator.geolocation) {
        gpsError.value = 'Browser Anda tidak mendukung deteksi lokasi GPS.';
        return;
    }
    gpsLoading.value = true;
    gpsError.value = '';

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const { latitude, longitude, accuracy } = pos.coords;
            inputLat.value = latitude.toFixed(14);
            inputLng.value = longitude.toFixed(6);
            myLocation.value = { lat: latitude, lng: longitude, accuracy: accuracy || 50 };
            gpsLoading.value = false;
            updateUserMarkerAndCircle(latitude, longitude, true);
            refreshMapMarkers();
        },
        (err) => {
            gpsLoading.value = false;
            if (err.code === 1) gpsError.value = 'Izin akses GPS ditolak. Silakan izinkan di browser Anda.';
            else if (err.code === 2) gpsError.value = 'Sinyal GPS tidak dapat ditemukan.';
            else gpsError.value = 'Waktu permintaan lokasi GPS habis (timeout).';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

// Handle "Cari Terdekat" Button Click
const handleSearchNearby = () => {
    const lat = parseCoordinate(inputLat.value);
    const lng = parseCoordinate(inputLng.value);

    if (lat !== null && lng !== null) {
        myLocation.value = { lat, lng, accuracy: 50 };
        updateUserMarkerAndCircle(lat, lng, true);
        refreshMapMarkers();
    } else {
        getMyLocation();
    }
};

// Marker Icons Factory
const createDotIcon = (color, size = 14, strokeColor = '#ffffff') => {
    return L.divIcon({
        className: 'custom-radar-dot-marker',
        html: `
            <div style="
                width: ${size}px;
                height: ${size}px;
                border-radius: 50%;
                background-color: ${color};
                border: 2px solid ${strokeColor};
                box-shadow: 0 1px 4px rgba(0,0,0,0.35);
            "></div>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -size / 2 - 2],
    });
};

const createUserLocationIcon = () => {
    return L.divIcon({
        className: 'user-radar-location-marker',
        html: `
            <div class="relative flex items-center justify-center" style="width: 32px; height: 32px;">
                <div class="absolute w-8 h-8 rounded-full bg-red-500/25 animate-ping"></div>
                <div class="relative w-4 h-4 rounded-full bg-red-600 border-2 border-white shadow-md"></div>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -18],
    });
};

// Update User Marker & Radius Circle
const updateUserMarkerAndCircle = (lat, lng, flyTo = false) => {
    if (!map || !L) return;

    if (myLocationMarker) map.removeLayer(myLocationMarker);
    if (myLocationCircle) map.removeLayer(myLocationCircle);

    myLocationMarker = L.marker([lat, lng], {
        icon: createUserLocationIcon(),
        zIndexOffset: 9999,
    }).addTo(map);

    myLocationMarker.bindPopup(`
        <div class="font-sans text-xs">
            <div class="font-bold text-red-600 mb-0.5 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-600 inline-block"></span>
                <span>Lokasi Anda</span>
            </div>
            <div class="text-gray-500 text-[11px] mb-1">Koordinat Pusat Radar</div>
            <div class="bg-gray-50 p-1.5 rounded font-mono text-[11px] text-gray-700 border border-gray-100">
                ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>
        </div>
    `);

    myLocationCircle = L.circle([lat, lng], {
        radius: searchRadiusMeters.value || 5000,
        color: '#ef4444',
        fillColor: '#ef4444',
        fillOpacity: 0.05,
        weight: 1.5,
        dashArray: '6, 6',
    }).addTo(map);

    if (flyTo) {
        map.flyTo([lat, lng], 13, { duration: 1 });
    }
};

// Real Road Route (OSRM)
const drawRoadRoute = async (targetLat, targetLng, targetName) => {
    if (!map || !L || !myLocation.value) return;

    const start = `${myLocation.value.lng},${myLocation.value.lat}`;
    const end = `${targetLng},${targetLat}`;

    try {
        const res = await axios.get(
            `https://router.project-osrm.org/route/v1/driving/${start};${end}?overview=full&geometries=geojson`
        );
        if (res.data.code !== 'Ok' || !res.data.routes.length) return;

        const route = res.data.routes[0];
        const distKm = route.distance / 1000;
        const durSec = route.duration;

        if (routeLayer) map.removeLayer(routeLayer);
        if (routeBadge) map.removeLayer(routeBadge);

        routeLayer = L.geoJSON(route.geometry, {
            style: {
                color: '#64748b',
                weight: 4,
                opacity: 0.85,
                dashArray: '8, 8',
                lineCap: 'round',
                lineJoin: 'round',
            },
        }).addTo(map);

        routeBadge = L.marker([targetLat, targetLng], {
            icon: L.divIcon({
                className: 'route-distance-badge',
                html: `
                    <div class="px-2.5 py-1 bg-white border border-gray-300 rounded-lg shadow-md text-center transform -translate-y-12">
                        <div class="text-[11px] font-black text-gray-800">${formatDistance(route.distance)}</div>
                        <div class="text-[9px] font-semibold text-gray-500">🚗 ${formatTime(durSec)}</div>
                    </div>
                `,
                iconSize: [80, 36],
                iconAnchor: [40, 18],
            }),
            zIndexOffset: 1000,
        }).addTo(map);

        map.fitBounds(routeLayer.getBounds(), { padding: [40, 40] });
    } catch (err) {
        console.error('OSRM route failed:', err);
    }
};

// Render Connections (ODC -> ODP and ODP -> Customer)
const updateConnections = () => {
    if (!map || !L || !connectionsLayerGroup) return;
    connectionsLayerGroup.clearLayers();

    if (!showConnections.value || activeTab.value !== 'radar') return;

    // 1. Line ODC to ODP
    const odcMap = new Map();
    props.odcs.forEach(o => {
        if (o.latitude && o.longitude) {
            odcMap.set(o.id, [parseFloat(o.latitude), parseFloat(o.longitude)]);
        }
    });

    const odpMap = new Map();
    visibleOdps.value.forEach(odp => {
        if (odp.latitude && odp.longitude) {
            const odpCoords = [parseFloat(odp.latitude), parseFloat(odp.longitude)];
            odpMap.set(odp.id, odpCoords);

            if (odp.odc_id && odcMap.has(odp.odc_id)) {
                const odcCoords = odcMap.get(odp.odc_id);
                L.polyline([odcCoords, odpCoords], {
                    color: '#2563eb',
                    weight: 2,
                    opacity: 0.55,
                    dashArray: '4, 4',
                }).addTo(connectionsLayerGroup);
            }
        }
    });

    // 2. Line ODP to Customer
    visibleCustomers.value.forEach(c => {
        if (c.latitude && c.longitude && c.odp_id && odpMap.has(c.odp_id)) {
            const custCoords = [parseFloat(c.latitude), parseFloat(c.longitude)];
            const odpCoords = odpMap.get(c.odp_id);
            L.polyline([odpCoords, custCoords], {
                color: '#10b981',
                weight: 1.5,
                opacity: 0.45,
                dashArray: '3, 3',
            }).addTo(connectionsLayerGroup);
        }
    });
};

// Refresh Map Markers (Cluster)
const refreshMapMarkers = () => {
    if (!map || !L || !clusterGroup) return;

    clusterGroup.clearLayers();

    if (activeTab.value === 'karyawan') {
        // Plot Employees
        visibleEmployees.value.forEach(emp => {
            const marker = L.marker([parseFloat(emp.latitude), parseFloat(emp.longitude)], {
                icon: createDotIcon('#ec4899', 14),
            });

            marker.bindPopup(`
                <div class="font-sans text-xs min-w-[190px]">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-pink-100 text-pink-700">
                            ${emp.type === 'field_duty' ? 'Tugas Lapangan' : 'Absensi GPS'}
                        </span>
                        <span class="text-[10px] text-gray-500 font-medium">${emp.time || '-'}</span>
                    </div>
                    <div class="font-bold text-gray-900 text-sm mb-0.5">${emp.name}</div>
                    <div class="text-[11px] text-gray-500 mb-1">${emp.position} • ${emp.branch}</div>
                    <div class="text-xs bg-gray-50 p-1.5 rounded border border-gray-100 mb-2">
                        <div class="text-gray-600">${emp.notes || '-'}</div>
                        <div class="text-[10px] text-gray-400 mt-0.5 font-mono">📍 ${emp.latitude.toFixed(6)}, ${emp.longitude.toFixed(6)}</div>
                    </div>
                    <a href="${getGoogleMapsUrl(emp.latitude, emp.longitude)}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 hover:text-blue-800">
                        🗺️ Google Maps →
                    </a>
                </div>
            `);
            clusterGroup.addLayer(marker);
        });
        return;
    }

    // 1. Plot ODCs (Blue dot #2563eb)
    visibleOdcs.value.forEach(odc => {
        const marker = L.marker([parseFloat(odc.latitude), parseFloat(odc.longitude)], {
            icon: createDotIcon('#2563eb', 13),
        });

        marker.bindPopup(`
            <div class="font-sans text-xs min-w-[210px]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">ODC</span>
                    <span class="text-[10px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-semibold">${odc.status || 'Active'}</span>
                </div>
                <h4 class="font-bold text-gray-900 text-sm mb-1">${odc.name}</h4>
                <div class="text-xs text-blue-700 font-semibold mb-1">🔌 ${odc.odps_count || 0} ODP Terhubung</div>
                <p class="text-xs text-gray-500 mb-2">${odc.address || 'Tanpa Alamat'}</p>
                <div class="text-[11px] bg-gray-50 p-1.5 rounded border border-gray-100 font-mono text-gray-600 mb-2.5">
                    📍 ${parseFloat(odc.latitude).toFixed(6)}, ${parseFloat(odc.longitude).toFixed(6)}
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                    <a href="${getGoogleMapsUrl(odc.latitude, odc.longitude)}" target="_blank" class="text-[11px] font-bold text-blue-600 hover:underline">
                        🗺️ Navigasi
                    </a>
                    <button type="button" class="text-[11px] font-bold text-gray-700 hover:text-blue-600 route-btn" onclick="window.drawRadarRoute(${odc.latitude}, ${odc.longitude}, '${odc.name}')">
                        🚗 Rute Jalan
                    </button>
                </div>
            </div>
        `);
        clusterGroup.addLayer(marker);
    });

    // 2. Plot ODPs (Teal/Green dot #10b981)
    visibleOdps.value.forEach(odp => {
        const marker = L.marker([parseFloat(odp.latitude), parseFloat(odp.longitude)], {
            icon: createDotIcon('#10b981', 12),
        });

        const available = (odp.total_ports || 0) - (odp.used_ports || 0);

        marker.bindPopup(`
            <div class="font-sans text-xs min-w-[210px]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">ODP</span>
                    <span class="text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-semibold">${odp.status || 'Active'}</span>
                </div>
                <h4 class="font-bold text-gray-900 text-sm mb-0.5">${odp.name}</h4>
                <p class="text-[10px] text-gray-400 font-medium mb-1.5">ODC: ${odp.odc?.name || '-'}</p>
                <div class="mb-2">
                    <div class="flex justify-between text-[10px] text-gray-500 font-semibold mb-0.5">
                        <span>Port: ${odp.used_ports || 0}/${odp.total_ports || 0}</span>
                        <span class="${available > 0 ? 'text-green-600' : 'text-red-600'} font-bold">
                            ${available > 0 ? available + ' tersedia' : 'PENUH'}
                        </span>
                    </div>
                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full ${available > 0 ? 'bg-emerald-500' : 'bg-red-500'}" style="width: ${odp.total_ports ? Math.round(((odp.used_ports || 0) / odp.total_ports) * 100) : 0}%"></div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mb-2">${odp.address || 'Tanpa Alamat'}</p>
                <div class="text-[11px] bg-gray-50 p-1.5 rounded border border-gray-100 font-mono text-gray-600 mb-2.5">
                    📍 ${parseFloat(odp.latitude).toFixed(6)}, ${parseFloat(odp.longitude).toFixed(6)}
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                    <a href="${getGoogleMapsUrl(odp.latitude, odp.longitude)}" target="_blank" class="text-[11px] font-bold text-blue-600 hover:underline">
                        🗺️ Navigasi
                    </a>
                    <button type="button" class="text-[11px] font-bold text-gray-700 hover:text-blue-600 route-btn" onclick="window.drawRadarRoute(${odp.latitude}, ${odp.longitude}, '${odp.name}')">
                        🚗 Rute Jalan
                    </button>
                </div>
            </div>
        `);
        clusterGroup.addLayer(marker);
    });

    // 3. Plot Customers
    visibleCustomers.value.forEach(c => {
        let dotColor = '#16a34a'; // default Aktif (green)
        let labelStatus = 'Aktif';

        if (c.service_status === 'gratis') {
            dotColor = '#06b6d4'; // Cyan
            labelStatus = 'Gratis';
        } else if (c.service_status === 'free_home') {
            dotColor = '#a855f7'; // Purple
            labelStatus = 'Freehome';
        } else if (['terminated', 'canceled', 'inactive'].includes(c.status)) {
            dotColor = '#1f2937'; // Black/Dark
            labelStatus = 'Stop Permanen';
        } else if (c.status === 'suspended') {
            dotColor = '#f97316'; // Orange
            labelStatus = 'Isolir';
        } else if (c.status === 'booking') {
            dotColor = '#eab308'; // Yellow
            labelStatus = 'Booking';
        }

        const marker = L.marker([parseFloat(c.latitude), parseFloat(c.longitude)], {
            icon: createDotIcon(dotColor, 11),
        });

        marker.bindPopup(`
            <div class="font-sans text-xs min-w-[210px]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: ${dotColor}">Pelanggan</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold" style="background-color: ${dotColor}20; color: ${dotColor}">
                        ${labelStatus}
                    </span>
                </div>
                <h4 class="font-bold text-gray-900 text-sm mb-0.5">${c.name}</h4>
                <p class="text-[10px] text-gray-400 font-mono mb-1">${c.customer_code || '-'}</p>
                <p class="text-[10px] text-gray-500 font-medium mb-1.5">ODP: ${c.odp?.name || '-'}</p>
                <p class="text-xs text-gray-500 mb-2">${c.address || 'Tanpa Alamat'}</p>
                <div class="text-[11px] bg-gray-50 p-1.5 rounded border border-gray-100 font-mono text-gray-600 mb-2.5">
                    📍 ${parseFloat(c.latitude).toFixed(6)}, ${parseFloat(c.longitude).toFixed(6)}
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                    <a href="${getGoogleMapsUrl(c.latitude, c.longitude)}" target="_blank" class="text-[11px] font-bold text-blue-600 hover:underline">
                        🗺️ Navigasi
                    </a>
                    <button type="button" class="text-[11px] font-bold text-gray-700 hover:text-blue-600 route-btn" onclick="window.drawRadarRoute(${c.latitude}, ${c.longitude}, '${c.name}')">
                        🚗 Rute Jalan
                    </button>
                </div>
            </div>
        `);
        clusterGroup.addLayer(marker);
    });

    updateConnections();
};

// Watchers
watch([checkOdc, checkOdp, checkPelanggan, filterCabang, filterOdc, filterOdp, filterStatusPelanggan, activeTab], () => {
    refreshMapMarkers();
});

watch(showConnections, () => {
    updateConnections();
});

watch(showMapTiles, (val) => {
    if (!map || !tileLayer) return;
    if (val) {
        tileLayer.addTo(map);
    } else {
        map.removeLayer(tileLayer);
    }
});

watch(searchRadiusMeters, (newVal) => {
    if (myLocationCircle && myLocation.value) {
        myLocationCircle.setRadius(newVal || 5000);
    }
    refreshMapMarkers();
});

// Map Initialization
onMounted(async () => {
    await nextTick();

    // Expose route helper to popup buttons
    window.drawRadarRoute = (lat, lng, name) => {
        drawRoadRoute(lat, lng, name);
    };

    // Dynamically load leaflet and markercluster
    const LeafletModule = await import('leaflet');
    L = LeafletModule.default || LeafletModule;
    window.L = L;

    await import('leaflet/dist/leaflet.css');
    await import('leaflet.markercluster/dist/MarkerCluster.css');
    await import('leaflet.markercluster/dist/MarkerCluster.Default.css');
    await import('leaflet.markercluster');

    const defaultLat = props.initLat ? parseCoordinate(props.initLat) : -6.998482777777778;
    const defaultLng = props.initLng ? parseCoordinate(props.initLng) : 106.717579;

    inputLat.value = defaultLat.toFixed(14);
    inputLng.value = defaultLng.toFixed(6);

    map = L.map(mapEl.value, {
        center: [defaultLat, defaultLng],
        zoom: 13,
        zoomControl: true,
    });

    // Tile layer: OpenStreetMap
    tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
        maxZoom: 19,
    });

    if (showMapTiles.value) {
        tileLayer.addTo(map);
    }

    // Connections layer
    connectionsLayerGroup = L.layerGroup().addTo(map);

    // MarkerCluster group
    clusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
    });
    map.addLayer(clusterGroup);

    // Initial user location & circle
    myLocation.value = { lat: defaultLat, lng: defaultLng, accuracy: 50 };
    updateUserMarkerAndCircle(defaultLat, defaultLng, false);

    // Plot markers
    refreshMapMarkers();

    // If initial query params provided, center there
    if (props.initLat && props.initLng) {
        map.setView([defaultLat, defaultLng], 14);
    }

    // Resize observer
    const resizeObserver = new ResizeObserver(() => {
        if (map) requestAnimationFrame(() => map.invalidateSize());
    });
    if (mapEl.value) resizeObserver.observe(mapEl.value);
});

onUnmounted(() => {
    if (window.drawRadarRoute) delete window.drawRadarRoute;
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head title="Radar Tikor" />
    <AppLayout title="Radar Tikor" subtitle="Monitoring coverage area & prospek jaringan">
        <div class="p-2 sm:p-4 max-w-7xl mx-auto space-y-4">

            <!-- ═══ HEADER ═══ -->
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="goBack"
                    class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors"
                    title="Kembali"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight leading-tight">Radar Tikor</h1>
                    <p class="text-xs text-gray-500 font-normal">Monitoring coverage area & prospek jaringan</p>
                </div>
            </div>

            <!-- ═══ TAB SWITCHER ═══ -->
            <div class="inline-flex p-1 bg-gray-100 rounded-xl">
                <button
                    type="button"
                    @click="activeTab = 'radar'"
                    :class="activeTab === 'radar' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-700 font-medium'"
                    class="px-5 py-1.5 rounded-lg text-xs flex items-center gap-2 transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                        <circle cx="12" cy="12" r="5" stroke-width="2"/>
                        <circle cx="12" cy="12" r="1" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v3m0 12v3m-9-9h3m12 0h3"/>
                    </svg>
                    <span>Radar</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'karyawan'"
                    :class="activeTab === 'karyawan' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-700 font-medium'"
                    class="px-5 py-1.5 rounded-lg text-xs flex items-center gap-2 transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Aktivitas Karyawan</span>
                </button>
            </div>

            <!-- ═══ CARD 1: PENGATURAN PENCARIAN ═══ -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs">
                <h2 class="text-sm font-bold text-gray-800 mb-3.5">Pengaturan Pencarian</h2>

                <!-- Lokasi Anda -->
                <div class="mb-3.5">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Lokasi Anda</label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input
                            type="text"
                            v-model="inputLat"
                            placeholder="-6,998482777777778"
                            class="flex-1 px-3.5 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        />
                        <input
                            type="text"
                            v-model="inputLng"
                            placeholder="106,717579"
                            class="flex-1 px-3.5 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        />
                        <button
                            type="button"
                            @click="getMyLocation"
                            :disabled="gpsLoading"
                            class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors shadow-2xs whitespace-nowrap disabled:opacity-50"
                        >
                            <svg v-if="!gpsLoading" class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>Auto GPS</span>
                        </button>
                    </div>
                    <p v-if="gpsError" class="text-[11px] text-red-500 font-medium mt-1">{{ gpsError }}</p>
                </div>

                <!-- Radius Pencarian (meter) -->
                <div class="mb-3.5">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Radius Pencarian (meter)</label>
                    <input
                        type="number"
                        v-model.number="searchRadiusMeters"
                        placeholder="5000"
                        class="w-full sm:w-72 px-3.5 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                    />
                </div>

                <!-- Apa yang ingin dicek? -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Apa yang ingin dicek?</label>
                    <!-- Row 1: ODC, ODP, Pelanggan -->
                    <div class="flex flex-wrap items-center gap-4 mb-2.5">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="checkOdc" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 w-4 h-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block"></span>
                            <span class="text-xs font-semibold text-gray-700">ODC</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="checkOdp" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 w-4 h-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-800 inline-block"></span>
                            <span class="text-xs font-semibold text-gray-700">ODP</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="checkPelanggan" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 w-4 h-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-600 inline-block"></span>
                            <span class="text-xs font-semibold text-gray-700">Pelanggan</span>
                        </label>
                    </div>
                    <!-- Row 2: Tampilkan Peta, Tampilkan Koneksi -->
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="showMapTiles" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 w-4 h-4">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">Tampilkan Peta</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="showConnections" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 w-4 h-4">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">Tampilkan Koneksi</span>
                        </label>
                    </div>
                </div>

                <!-- Cari Terdekat Button -->
                <button
                    type="button"
                    @click="handleSearchNearby"
                    class="px-5 py-2.5 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-colors shadow-sm cursor-pointer"
                >
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke-width="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" />
                    </svg>
                    <span>Cari Terdekat</span>
                </button>
            </div>

            <!-- ═══ CARD 2: FILTER DATA ═══ -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-700 mb-3.5">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter Data</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- Cabang -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Cabang</label>
                        <select
                            v-model="filterCabang"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Semua Cabang</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>

                    <!-- ODC -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">ODC</label>
                        <select
                            v-model="filterOdc"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Semua ODC</option>
                            <option v-for="odc in filteredOdcOptions" :key="odc.id" :value="odc.id">{{ odc.name }}</option>
                        </select>
                    </div>

                    <!-- ODP -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">ODP</label>
                        <select
                            v-model="filterOdp"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Semua ODP</option>
                            <option v-for="odp in filteredOdpOptions" :key="odp.id" :value="odp.id">{{ odp.name }}</option>
                        </select>
                    </div>

                    <!-- Status Pelanggan -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Status Pelanggan</label>
                        <select
                            v-model="filterStatusPelanggan"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="gratis">Gratis</option>
                            <option value="free_home">Freehome</option>
                            <option value="terminated">Stop Permanen</option>
                            <option value="suspended">Isolir</option>
                            <option value="booking">Booking</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ═══ STAT CARDS (3 COLS) ═══ -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <!-- ODC Card -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs">
                    <div class="w-2.5 h-2.5 rounded-full bg-blue-600 mb-2"></div>
                    <div class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ stats.odc }}</div>
                    <div class="text-[11px] font-medium text-gray-500 mt-0.5">ODC</div>
                </div>

                <!-- ODP Card -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 mb-2"></div>
                    <div class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ stats.odp }}</div>
                    <div class="text-[11px] font-medium text-gray-500 mt-0.5">ODP</div>
                </div>

                <!-- Pelanggan Card -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs">
                    <div class="w-2.5 h-2.5 rounded-full bg-purple-600 mb-2"></div>
                    <div class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ stats.pelanggan }}</div>
                    <div class="text-[11px] font-medium text-gray-500 mt-0.5">Pelanggan</div>
                </div>
            </div>

            <!-- ═══ CARD 3: PETA RADAR ═══ -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs">
                <!-- Header with Point Badge -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <h3 class="text-sm font-bold text-gray-800">
                            {{ activeTab === 'karyawan' ? 'Peta Aktivitas Karyawan' : 'Peta Radar' }}
                        </h3>
                    </div>
                    <span class="px-2.5 py-0.5 text-xs font-semibold text-gray-600 bg-gray-100 rounded-full">
                        {{ totalVisiblePoints }} titik
                    </span>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] text-gray-600 font-medium py-2 mb-3 border-b border-gray-100">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span>Anda</span>
                    </div>
                    <template v-if="activeTab === 'radar'">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <span>ODC</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>ODP</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-green-600"></span>
                            <span>Aktif</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                            <span>Gratis</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                            <span>Freehome</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-gray-800"></span>
                            <span>Stop Permanen</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-0.5 bg-gray-500 inline-block"></span>
                            <span>Rute Jalan</span>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                            <span>Posisi Karyawan / Lapangan</span>
                        </div>
                    </template>
                </div>

                <!-- Leaflet Map Container -->
                <div class="w-full h-[640px] rounded-xl overflow-hidden border border-gray-200 relative bg-gray-50">
                    <div ref="mapEl" class="w-full h-full z-0" id="radar-map"></div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style>
/* Leaflet Popup Styles */
.leaflet-popup-content-wrapper {
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 0;
}
.leaflet-popup-content {
    margin: 12px 14px;
    line-height: 1.4;
}

/* Custom Marker Cluster Enhancements */
.marker-cluster {
    background-clip: padding-box;
    border-radius: 50%;
}
.marker-cluster div {
    width: 32px;
    height: 32px;
    margin-left: 4px;
    margin-top: 4px;
    text-align: center;
    border-radius: 50%;
    font: 11px/32px 'Inter', sans-serif;
    font-weight: 700;
    color: #1f2937;
}

/* User Radar Pulse */
@keyframes userLocationPulse {
    0% { transform: scale(0.6); opacity: 0.8; }
    100% { transform: scale(2.4); opacity: 0; }
}
.user-radar-pulse {
    animation: userLocationPulse 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}
</style>
