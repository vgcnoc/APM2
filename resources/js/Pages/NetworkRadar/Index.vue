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

// Floating on-map legend visibility
const isMapLegendOpen = ref(true);

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
    if (typeof val === 'number') return isFinite(val) ? val : null;
    const clean = String(val).trim().replace(',', '.');
    const num = parseFloat(clean);
    return isNaN(num) || !isFinite(num) ? null : num;
};

// Smart coordinate paste handler (Google Maps format, comma/space pair)
const handleLatPasteOrInput = () => {
    if (!inputLat.value) return;
    const raw = String(inputLat.value).trim();
    // Check if contains Google Maps URL (e.g. @-6.998482,106.717579 or ?q=-6.998482,106.717579)
    const urlMatch = raw.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/) || raw.match(/q=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
    if (urlMatch) {
        inputLat.value = urlMatch[1];
        inputLng.value = urlMatch[2];
        return;
    }
    // Check if contains comma or space separating two coordinates
    if (raw.includes(',') || raw.includes(' ')) {
        const parts = raw.split(/[\s,]+/).filter(Boolean);
        if (parts.length >= 2) {
            const p0 = parseFloat(parts[0].replace(',', '.'));
            const p1 = parseFloat(parts[1].replace(',', '.'));
            if (!isNaN(p0) && !isNaN(p1) && isFinite(p0) && isFinite(p1)) {
                inputLat.value = p0.toFixed(14);
                inputLng.value = p1.toFixed(6);
            }
        }
    }
};

const escapeHtmlAttr = (str) => {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
};

// State for Nearby API
const isSearchingNearby = ref(false);
const nearbyResults = ref(null);
const closestOdp = ref(null);
const closestLineLayer = ref(null);

// Filtered Lists (using nearby API results if search was performed, otherwise props)
const visibleOdcs = computed(() => {
    if (!checkOdc.value) return [];
    const sourceList = (nearbyResults.value && nearbyResults.value.odcs !== undefined)
        ? nearbyResults.value.odcs
        : props.odcs;

    return sourceList.filter(item => {
        if (!item.latitude || !item.longitude) return false;
        if (filterCabang.value && item.area_id != filterCabang.value) return false;
        if (filterOdc.value && item.id != filterOdc.value) return false;

        // Radius filter if user location is set
        if (myLocation.value) {
            const dist = item.distance_meters !== undefined
                ? item.distance_meters
                : calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, parseFloat(item.latitude), parseFloat(item.longitude));
            if (dist > searchRadiusMeters.value) return false;
        }
        return true;
    });
});

const visibleOdps = computed(() => {
    if (!checkOdp.value) return [];
    const sourceList = (nearbyResults.value && nearbyResults.value.odps !== undefined)
        ? nearbyResults.value.odps
        : props.odps;

    return sourceList.filter(item => {
        if (!item.latitude || !item.longitude) return false;
        if (filterCabang.value && item.area_id != filterCabang.value) return false;
        if (filterOdc.value && item.odc_id != filterOdc.value) return false;
        if (filterOdp.value && item.id != filterOdp.value) return false;

        if (myLocation.value) {
            const dist = item.distance_meters !== undefined
                ? item.distance_meters
                : calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, parseFloat(item.latitude), parseFloat(item.longitude));
            if (dist > searchRadiusMeters.value) return false;
        }
        return true;
    });
});

const visibleCustomers = computed(() => {
    if (!checkPelanggan.value) return [];
    const sourceList = (nearbyResults.value && nearbyResults.value.customers !== undefined)
        ? nearbyResults.value.customers
        : props.customers;

    return sourceList.filter(item => {
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
            const dist = item.distance_meters !== undefined
                ? item.distance_meters
                : calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, parseFloat(item.latitude), parseFloat(item.longitude));
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

// Draw Direct Line to Closest ODP on Map
const drawDirectLineToClosestOdp = () => {
    if (!map || !L) return;
    if (closestLineLayer.value) {
        map.removeLayer(closestLineLayer.value);
        closestLineLayer.value = null;
    }

    if (!showConnections.value || !closestOdp.value || !myLocation.value) return;

    const odpLat = parseFloat(closestOdp.value.latitude);
    const odpLng = parseFloat(closestOdp.value.longitude);
    if (!odpLat || !odpLng || !isFinite(odpLat) || !isFinite(odpLng)) return;

    const userCoords = [myLocation.value.lat, myLocation.value.lng];
    const odpCoords = [odpLat, odpLng];

    const distText = formatDistance(closestOdp.value.distance_meters || 0);

    const polyline = L.polyline([userCoords, odpCoords], {
        color: '#2563eb',
        weight: 3,
        dashArray: '6, 6',
        opacity: 0.9,
    });

    const midLat = (userCoords[0] + odpCoords[0]) / 2;
    const midLng = (userCoords[1] + odpCoords[1]) / 2;

    const labelMarker = L.marker([midLat, midLng], {
        icon: L.divIcon({
            className: 'closest-distance-badge',
            html: `
                <div class="px-2.5 py-1 bg-blue-900 text-white rounded-full shadow-lg text-[10px] font-black tracking-wider whitespace-nowrap transform -translate-x-1/2 -translate-y-1/2 border border-white flex items-center gap-1">
                    <span>🎯 ODP Terdekat:</span>
                    <span class="text-emerald-300 font-bold">${distText}</span>
                </div>
            `,
            iconSize: [0, 0],
        }),
        zIndexOffset: 1500,
    });

    closestLineLayer.value = L.layerGroup([polyline, labelMarker]).addTo(map);
};

// Focus map to specific ODP
const focusToOdp = (odp) => {
    if (!map || !odp || !odp.latitude || !odp.longitude) return;
    const lat = parseFloat(odp.latitude);
    const lng = parseFloat(odp.longitude);
    if (!isFinite(lat) || !isFinite(lng)) return;
    map.flyTo([lat, lng], 17, { duration: 1.2 });
};

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
            handleSearchNearby();
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
const handleSearchNearby = async () => {
    const lat = parseCoordinate(inputLat.value);
    const lng = parseCoordinate(inputLng.value);

    if (lat === null || lng === null) {
        getMyLocation();
        return;
    }

    myLocation.value = { lat, lng, accuracy: 50 };
    updateUserMarkerAndCircle(lat, lng, true);

    isSearchingNearby.value = true;
    try {
        const response = await axios.post('/network-radar/nearby', {
            lat,
            lng,
            radius: searchRadiusMeters.value || 5000,
            type: 'all',
            area_id: filterCabang.value || null,
        });

        nearbyResults.value = response.data;
        closestOdp.value = response.data.closest_odp || null;

        drawDirectLineToClosestOdp();
        refreshMapMarkers();
    } catch (err) {
        console.error('Error fetching nearby radar data:', err);
        refreshMapMarkers();
    } finally {
        isSearchingNearby.value = false;
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
    if (!map || !L) return;

    if (!myLocation.value) {
        const lat = parseCoordinate(inputLat.value);
        const lng = parseCoordinate(inputLng.value);
        if (lat !== null && lng !== null) {
            myLocation.value = { lat, lng, accuracy: 50 };
            updateUserMarkerAndCircle(lat, lng, false);
        } else {
            getMyLocation();
            return;
        }
    }

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
                    <div class="px-2.5 py-1 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-lg shadow-md text-center transform -translate-y-12">
                        <div class="text-[11px] font-black text-gray-800 dark:text-white">${formatDistance(route.distance)}</div>
                        <div class="text-[9px] font-semibold text-gray-500 dark:text-slate-400">🚗 ${formatTime(durSec)}</div>
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
            const lat = parseFloat(o.latitude);
            const lng = parseFloat(o.longitude);
            if (!isNaN(lat) && !isNaN(lng) && isFinite(lat) && isFinite(lng)) {
                odcMap.set(o.id, [lat, lng]);
            }
        }
    });

    const odpMap = new Map();
    visibleOdps.value.forEach(odp => {
        if (odp.latitude && odp.longitude) {
            const odpLat = parseFloat(odp.latitude);
            const odpLng = parseFloat(odp.longitude);
            if (isNaN(odpLat) || isNaN(odpLng) || !isFinite(odpLat) || !isFinite(odpLng)) return;
            const odpCoords = [odpLat, odpLng];
            odpMap.set(odp.id, odpCoords);

            if (odp.odc_id && odcMap.has(odp.odc_id)) {
                const odcCoords = odcMap.get(odp.odc_id);
                L.polyline([odcCoords, odpCoords], {
                    color: '#2563eb',
                    weight: 2,
                    opacity: 0.6,
                    dashArray: '5, 5',
                }).addTo(connectionsLayerGroup);
            }
        }
    });

    // 2. Line ODP to Customer
    visibleCustomers.value.forEach(c => {
        if (c.latitude && c.longitude && c.odp_id && odpMap.has(c.odp_id)) {
            const cLat = parseFloat(c.latitude);
            const cLng = parseFloat(c.longitude);
            if (isNaN(cLat) || isNaN(cLng) || !isFinite(cLat) || !isFinite(cLng)) return;
            const custCoords = [cLat, cLng];
            const odpCoords = odpMap.get(c.odp_id);
            L.polyline([odpCoords, custCoords], {
                color: '#10b981',
                weight: 1.5,
                opacity: 0.55,
                dashArray: '4, 4',
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
            const lat = parseFloat(emp.latitude);
            const lng = parseFloat(emp.longitude);
            if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return;

            const marker = L.marker([lat, lng], {
                icon: createDotIcon('#ec4899', 14),
            });

            marker.bindPopup(`
                <div class="font-sans text-xs min-w-[190px]">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-pink-100 dark:bg-pink-950/70 text-pink-700 dark:text-pink-300">
                            ${emp.type === 'field_duty' ? 'Tugas Lapangan' : 'Absensi GPS'}
                        </span>
                        <span class="text-[10px] text-gray-500 dark:text-slate-400 font-medium">${emp.time || '-'}</span>
                    </div>
                    <div class="font-bold text-gray-900 dark:text-white text-sm mb-0.5">${emp.name}</div>
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 mb-1">${emp.position} • ${emp.branch}</div>
                    <div class="text-xs bg-gray-50 dark:bg-slate-800/80 p-1.5 rounded border border-gray-100 dark:border-slate-700 mb-2">
                        <div class="text-gray-600 dark:text-slate-300">${emp.notes || '-'}</div>
                        <div class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5 font-mono">📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}</div>
                    </div>
                    <a href="${getGoogleMapsUrl(lat, lng)}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
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
        const lat = parseFloat(odc.latitude);
        const lng = parseFloat(odc.longitude);
        if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return;

        const marker = L.marker([lat, lng], {
            icon: createDotIcon('#2563eb', 13),
        });

        marker.bindPopup(`
            <div class="font-sans text-xs min-w-[210px]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">ODC</span>
                    <span class="text-[10px] bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 px-1.5 py-0.5 rounded font-semibold">${odc.status || 'Active'}</span>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-1">${odc.name}</h4>
                <div class="text-xs text-blue-700 dark:text-blue-400 font-semibold mb-1">🔌 ${odc.odps_count || 0} ODP Terhubung</div>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">${odc.address || 'Tanpa Alamat'}</p>
                <div class="text-[11px] bg-gray-50 dark:bg-slate-800/80 p-1.5 rounded border border-gray-100 dark:border-slate-700 font-mono text-gray-600 dark:text-slate-300 mb-2.5">
                    📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-slate-800">
                    <a href="${getGoogleMapsUrl(lat, lng)}" target="_blank" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        🗺️ Navigasi
                    </a>
                    <button type="button" class="text-[11px] font-bold text-gray-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 route-btn cursor-pointer" onclick="window.drawRadarRoute(${lat}, ${lng}, '${escapeHtmlAttr(odc.name)}')">
                        🚗 Rute Jalan
                    </button>
                </div>
            </div>
        `);
        clusterGroup.addLayer(marker);
    });

    // 2. Plot ODPs (Teal/Green dot #10b981)
    visibleOdps.value.forEach(odp => {
        const lat = parseFloat(odp.latitude);
        const lng = parseFloat(odp.longitude);
        if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return;

        const marker = L.marker([lat, lng], {
            icon: createDotIcon('#10b981', 12),
        });

        const available = odp.available_ports !== undefined
            ? odp.available_ports
            : Math.max(0, (odp.total_ports || 0) - (odp.used_ports || 0));

        marker.bindPopup(`
            <div class="font-sans text-xs min-w-[210px]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">ODP</span>
                    <span class="text-[10px] bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 px-1.5 py-0.5 rounded font-semibold">${odp.status || 'Active'}</span>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-0.5">${odp.name}</h4>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-medium mb-1.5">ODC: ${odp.odc_name || odp.odc?.name || '-'}</p>
                <div class="mb-2">
                    <div class="flex justify-between text-[10px] text-gray-500 dark:text-slate-400 font-semibold mb-0.5">
                        <span>Port: ${odp.used_ports || 0}/${odp.total_ports || 0}</span>
                        <span class="${available > 0 ? 'text-green-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'} font-bold">
                            ${available > 0 ? available + ' tersedia' : 'PENUH'}
                        </span>
                    </div>
                    <div class="w-full h-1.5 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full ${available > 0 ? 'bg-emerald-500' : 'bg-red-500'}" style="width: ${odp.total_ports ? Math.round(((odp.used_ports || 0) / odp.total_ports) * 100) : 0}%"></div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">${odp.address || 'Tanpa Alamat'}</p>
                <div class="text-[11px] bg-gray-50 dark:bg-slate-800/80 p-1.5 rounded border border-gray-100 dark:border-slate-700 font-mono text-gray-600 dark:text-slate-300 mb-2.5">
                    📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-slate-800">
                    <a href="${getGoogleMapsUrl(lat, lng)}" target="_blank" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        🗺️ Navigasi
                    </a>
                    <button type="button" class="text-[11px] font-bold text-gray-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 route-btn cursor-pointer" onclick="window.drawRadarRoute(${lat}, ${lng}, '${escapeHtmlAttr(odp.name)}')">
                        🚗 Rute Jalan
                    </button>
                </div>
            </div>
        `);
        clusterGroup.addLayer(marker);
    });

    // 3. Plot Customers
    visibleCustomers.value.forEach(c => {
        const lat = parseFloat(c.latitude);
        const lng = parseFloat(c.longitude);
        if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return;

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

        const marker = L.marker([lat, lng], {
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
                <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-0.5">${c.name}</h4>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono mb-1">${c.customer_code || '-'}</p>
                <p class="text-[10px] text-gray-500 dark:text-slate-400 font-medium mb-1.5">ODP: ${c.odp_name || c.odp?.name || '-'}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">${c.address || 'Tanpa Alamat'}</p>
                <div class="text-[11px] bg-gray-50 dark:bg-slate-800/80 p-1.5 rounded border border-gray-100 dark:border-slate-700 font-mono text-gray-600 dark:text-slate-300 mb-2.5">
                    📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-slate-800">
                    <a href="${getGoogleMapsUrl(lat, lng)}" target="_blank" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        🗺️ Navigasi
                    </a>
                    <button type="button" class="text-[11px] font-bold text-gray-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 route-btn cursor-pointer" onclick="window.drawRadarRoute(${lat}, ${lng}, '${escapeHtmlAttr(c.name)}')">
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
watch([checkOdc, checkOdp, checkPelanggan, filterStatusPelanggan, activeTab], () => {
    refreshMapMarkers();
});

watch(filterCabang, () => {
    filterOdc.value = '';
    filterOdp.value = '';
    if (myLocation.value) {
        handleSearchNearby();
    } else {
        refreshMapMarkers();
    }
});

watch(filterOdc, () => {
    filterOdp.value = '';
    refreshMapMarkers();
});

watch(showConnections, (val) => {
    updateConnections();
    if (!val && closestLineLayer.value && map) {
        map.removeLayer(closestLineLayer.value);
    } else if (val && closestOdp.value) {
        drawDirectLineToClosestOdp();
    }
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
    if (myLocation.value) {
        handleSearchNearby();
    } else {
        refreshMapMarkers();
    }
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

    // If initial query params provided, trigger search nearby immediately
    if (props.initLat && props.initLng) {
        map.setView([defaultLat, defaultLng], 14);
        handleSearchNearby();
    } else {
        refreshMapMarkers();
    }

    // Resize observer
    const resizeObserver = new ResizeObserver(() => {
        if (map) requestAnimationFrame(() => map.invalidateSize());
    });
    if (mapEl.value) resizeObserver.observe(mapEl.value);
});

onUnmounted(() => {
    if (window.drawRadarRoute) delete window.drawRadarRoute;
    if (closestLineLayer.value && map) {
        map.removeLayer(closestLineLayer.value);
        closestLineLayer.value = null;
    }
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
                    class="p-2 rounded-xl text-gray-500 hover:text-gray-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    title="Kembali"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-950/80 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight leading-tight">Radar Tikor</h1>
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-normal">Monitoring coverage area & prospek jaringan</p>
                </div>
            </div>

            <!-- ═══ TAB SWITCHER ═══ -->
            <div class="inline-flex p-1 bg-gray-100 dark:bg-slate-800/80 rounded-xl">
                <button
                    type="button"
                    @click="activeTab = 'radar'"
                    :class="activeTab === 'radar' ? 'bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-xs font-bold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 font-medium'"
                    class="px-5 py-1.5 rounded-lg text-xs flex items-center gap-2 transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    :class="activeTab === 'karyawan' ? 'bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-xs font-bold' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 font-medium'"
                    class="px-5 py-1.5 rounded-lg text-xs flex items-center gap-2 transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Aktivitas Karyawan</span>
                </button>
            </div>

            <!-- ═══ CARD 1: PENGATURAN PENCARIAN ═══ -->
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-5 shadow-xs">
                <h2 class="text-sm font-bold text-gray-800 dark:text-slate-200 mb-3.5">Pengaturan Pencarian</h2>

                <!-- Lokasi Anda -->
                <div class="mb-3.5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">Lokasi Anda</label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input
                            type="text"
                            v-model="inputLat"
                            @input="handleLatPasteOrInput"
                            @keydown.enter="handleSearchNearby"
                            placeholder="-6.998482777777778"
                            class="flex-1 px-3.5 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        />
                        <input
                            type="text"
                            v-model="inputLng"
                            @keydown.enter="handleSearchNearby"
                            placeholder="106.717579"
                            class="flex-1 px-3.5 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        />
                        <button
                            type="button"
                            @click="getMyLocation"
                            :disabled="gpsLoading"
                            class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors shadow-2xs whitespace-nowrap disabled:opacity-50 cursor-pointer"
                        >
                            <svg v-if="!gpsLoading" class="w-3.5 h-3.5 text-gray-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none">
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
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">Radius Pencarian (meter)</label>
                    <input
                        type="number"
                        v-model.number="searchRadiusMeters"
                        @keydown.enter="handleSearchNearby"
                        placeholder="5000"
                        class="w-full sm:w-72 px-3.5 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                    />
                </div>

                <!-- Apa yang ingin dicek? -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-2">Apa yang ingin dicek?</label>
                    <!-- Row 1: ODC, ODP, Pelanggan -->
                    <div class="flex flex-wrap items-center gap-4 mb-2.5">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="checkOdc" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-slate-700 dark:bg-slate-800 w-4 h-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block"></span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">ODC</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="checkOdp" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-slate-700 dark:bg-slate-800 w-4 h-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">ODP</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="checkPelanggan" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-slate-700 dark:bg-slate-800 w-4 h-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-600 inline-block"></span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">Pelanggan</span>
                        </label>
                    </div>
                    <!-- Row 2: Tampilkan Peta, Tampilkan Koneksi -->
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="showMapTiles" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-slate-700 dark:bg-slate-800 w-4 h-4">
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">Tampilkan Peta</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="showConnections" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-slate-700 dark:bg-slate-800 w-4 h-4">
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">Tampilkan Koneksi</span>
                        </label>
                    </div>
                </div>

                <!-- Cari Terdekat Button -->
                <button
                    type="button"
                    @click="handleSearchNearby"
                    :disabled="isSearchingNearby"
                    class="px-5 py-2.5 bg-blue-900 hover:bg-blue-800 disabled:opacity-60 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-colors shadow-sm cursor-pointer"
                >
                    <svg v-if="!isSearchingNearby" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke-width="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" />
                    </svg>
                    <svg v-else class="w-4 h-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>{{ isSearchingNearby ? 'Mencari Perangkat Terdekat...' : 'Cari Terdekat' }}</span>
                </button>
            </div>

            <!-- ═══ CARD: HASIL RADAR TIKOR TERDEKAT ═══ -->
            <div v-if="closestOdp" class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-5 text-white shadow-lg border border-blue-700/60 transition-all">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/25 text-blue-200 border border-blue-400/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            ODP Terdekat Ditemukan
                        </div>
                        <h3 class="text-xl font-extrabold tracking-tight flex items-center gap-2">
                            <span>{{ closestOdp.name }}</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-md font-bold bg-emerald-500/25 text-emerald-300 border border-emerald-500/40">
                                {{ closestOdp.available_ports }} Port Kosong
                            </span>
                        </h3>
                        <div class="text-xs text-blue-200 flex flex-wrap items-center gap-x-4 gap-y-1 font-medium">
                            <span>📍 Jarak: <strong class="text-white font-black">{{ formatDistance(closestOdp.distance_meters) }}</strong></span>
                            <span>🏢 ODC: <strong class="text-white">{{ closestOdp.odc_name || '-' }}</strong></span>
                            <span>🌐 Cabang: <strong class="text-white">{{ closestOdp.area_name || '-' }}</strong></span>
                            <span>🔌 Terpakai: <strong class="text-white">{{ closestOdp.used_ports || 0 }} / {{ closestOdp.total_ports || 0 }}</strong></span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="focusToOdp(closestOdp)"
                            class="px-3.5 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors border border-white/20 cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                <circle cx="12" cy="12" r="3" stroke-width="2"/>
                            </svg>
                            <span>Fokus di Peta</span>
                        </button>
                        <button
                            type="button"
                            @click="drawRoadRoute(closestOdp.latitude, closestOdp.longitude, closestOdp.name)"
                            class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm cursor-pointer"
                        >
                            <span>🚗 Rute Jalan</span>
                        </button>
                        <a
                            :href="getGoogleMapsUrl(closestOdp.latitude, closestOdp.longitude)"
                            target="_blank"
                            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm"
                        >
                            <span>🗺️ Google Maps</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notice jika pencarian tidak menemukan ODP -->
            <div v-else-if="nearbyResults && nearbyResults.odps && nearbyResults.odps.length === 0" class="bg-amber-50 dark:bg-amber-950/40 rounded-2xl p-4 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-200 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs">
                    <strong class="font-bold">Tidak ada ODP dalam radius {{ formatDistance(searchRadiusMeters) }}.</strong>
                    <span class="text-amber-700 dark:text-amber-300 ml-1">Silakan perbesar nilai radius pencarian atau sesuaikan filter Cabang.</span>
                </div>
            </div>

            <!-- ═══ CARD 2: FILTER DATA ═══ -->
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-slate-300 mb-3.5">
                    <svg class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter Data</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- Cabang -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 dark:text-slate-400 mb-1">Cabang</label>
                        <select
                            v-model="filterCabang"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Semua Cabang</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>

                    <!-- ODC -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 dark:text-slate-400 mb-1">ODC</label>
                        <select
                            v-model="filterOdc"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Semua ODC</option>
                            <option v-for="odc in filteredOdcOptions" :key="odc.id" :value="odc.id">{{ odc.name }}</option>
                        </select>
                    </div>

                    <!-- ODP -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 dark:text-slate-400 mb-1">ODP</label>
                        <select
                            v-model="filterOdp"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Semua ODP</option>
                            <option v-for="odp in filteredOdpOptions" :key="odp.id" :value="odp.id">{{ odp.name }}</option>
                        </select>
                    </div>

                    <!-- Status Pelanggan -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 dark:text-slate-400 mb-1">Status Pelanggan</label>
                        <select
                            v-model="filterStatusPelanggan"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
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
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-4 shadow-xs">
                    <div class="w-2.5 h-2.5 rounded-full bg-blue-600 mb-2"></div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ stats.odc }}</div>
                    <div class="text-[11px] font-medium text-gray-500 dark:text-slate-400 mt-0.5">ODC</div>
                </div>

                <!-- ODP Card -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-4 shadow-xs">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 mb-2"></div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ stats.odp }}</div>
                    <div class="text-[11px] font-medium text-gray-500 dark:text-slate-400 mt-0.5">ODP</div>
                </div>

                <!-- Pelanggan Card -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-4 shadow-xs">
                    <div class="w-2.5 h-2.5 rounded-full bg-purple-600 mb-2"></div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ stats.pelanggan }}</div>
                    <div class="text-[11px] font-medium text-gray-500 dark:text-slate-400 mt-0.5">Pelanggan</div>
                </div>
            </div>

            <!-- ═══ CARD 3: PETA RADAR ═══ -->
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-5 shadow-xs">
                <!-- Header with Point Badge -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-700 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-slate-200">
                            {{ activeTab === 'karyawan' ? 'Peta Aktivitas Karyawan' : 'Peta Radar' }}
                        </h3>
                    </div>
                    <span class="px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 rounded-full">
                        {{ totalVisiblePoints }} titik
                    </span>
                </div>

                <!-- Static Legend Row Above Map -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] text-gray-600 dark:text-slate-300 font-medium py-2.5 mb-3 border-b border-gray-100 dark:border-slate-800">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                        <span class="font-semibold text-gray-800 dark:text-white">Anda</span>
                    </div>
                    <template v-if="activeTab === 'radar'">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            <span>ODC</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>ODP</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-600"></span>
                            <span>Aktif</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <span>Isolir</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span>Booking</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                            <span>Gratis</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                            <span>Freehome</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-800 dark:bg-gray-400"></span>
                            <span>Stop Permanen</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-0.5 border-t-2 border-blue-600 border-dashed inline-block"></span>
                            <span>Feeder ODC-ODP</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-0.5 border-t-2 border-emerald-500 border-dashed inline-block"></span>
                            <span>Drop Pelanggan</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3.5 h-0.5 bg-gray-500 dark:bg-slate-400 inline-block"></span>
                            <span>Rute Jalan</span>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                            <span>Posisi Karyawan (GPS / Lapangan)</span>
                        </div>
                    </template>
                </div>

                <!-- Leaflet Map Container with Floating HUD Legend -->
                <div class="w-full h-[640px] rounded-xl overflow-hidden border border-gray-200 dark:border-slate-800 relative bg-gray-50 dark:bg-slate-950">
                    <div ref="mapEl" class="w-full h-full z-0" id="radar-map"></div>

                    <!-- ═══ FLOATING ON-MAP LEGEND HUD ═══ -->
                    <div class="absolute top-3 right-3 sm:top-4 sm:right-4 z-[400] transition-all duration-200">
                        <!-- Minimized Floating Button -->
                        <button
                            v-if="!isMapLegendOpen"
                            type="button"
                            @click="isMapLegendOpen = true"
                            class="px-3 py-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-xl border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2 hover:bg-white dark:hover:bg-slate-800 transition-all hover:scale-105 cursor-pointer"
                            title="Tampilkan Legenda Peta"
                        >
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <span>Legenda Peta</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </button>

                        <!-- Expanded Floating Legend Card -->
                        <div
                            v-else
                            class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-2xl border border-gray-200/90 dark:border-slate-700/80 rounded-2xl p-3.5 text-xs text-gray-700 dark:text-gray-200 w-64 sm:w-72 max-h-[560px] overflow-y-auto select-none"
                        >
                            <!-- Header with title & close button -->
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-100 dark:border-slate-800">
                                <div class="flex items-center gap-1.5 font-bold text-gray-900 dark:text-white">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                    </svg>
                                    <span>Legenda Peta Radar</span>
                                </div>
                                <button
                                    type="button"
                                    @click="isMapLegendOpen = false"
                                    class="p-1 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                    title="Sembunyikan Legenda"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Legend Content by Mode -->
                            <div v-if="activeTab === 'radar'" class="space-y-3">
                                <!-- 1. Perangkat Jaringan -->
                                <div>
                                    <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-slate-400 tracking-wider mb-1.5">Titik & Perangkat</div>
                                    <div class="space-y-1.5 pl-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="relative flex h-3 w-3">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600 border border-white"></span>
                                                </span>
                                                <span class="text-[11px] font-semibold text-gray-800 dark:text-gray-200">Lokasi Anda (GPS)</span>
                                            </div>
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 font-mono">Pusat</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 rounded-full bg-blue-600 border border-white dark:border-slate-800 shadow-xs inline-block"></span>
                                                <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300">ODC (Distribusi)</span>
                                            </div>
                                            <span class="text-[10px] text-gray-400 dark:text-slate-400 font-bold">{{ stats.odc }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 rounded-full bg-emerald-500 border border-white dark:border-slate-800 shadow-xs inline-block"></span>
                                                <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300">ODP (Kotak Port)</span>
                                            </div>
                                            <span class="text-[10px] text-gray-400 dark:text-slate-400 font-bold">{{ stats.odp }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Status Pelanggan -->
                                <div class="pt-2 border-t border-gray-100 dark:border-slate-800">
                                    <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-slate-400 tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Status Pelanggan</span>
                                        <span class="text-[10px] text-purple-600 dark:text-purple-400 font-bold">{{ stats.pelanggan }} total</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 pl-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-green-600 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Aktif</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Isolir</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Booking</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-purple-600 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Freehome</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Gratis</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-gray-800 dark:bg-gray-400 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Berhenti</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Jalur & Garis Koneksi -->
                                <div class="pt-2 border-t border-gray-100 dark:border-slate-800">
                                    <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-slate-400 tracking-wider mb-1.5">Jalur & Koneksi</div>
                                    <div class="space-y-1.5 pl-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-0.5 border-t-2 border-blue-600 border-dashed inline-block shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">ODC ➜ ODP (Feeder)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-0.5 border-t-2 border-emerald-500 border-dashed inline-block shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">ODP ➜ Pelanggan (Drop)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-0.5 border-t-2 border-blue-600 border-dashed inline-block shrink-0"></span>
                                            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold">🎯 ODP Terdekat</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-0.5 border-t-2 border-slate-500 border-dashed inline-block shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">🚗 Rute Jalan (OSRM)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-0.5 border-t-2 border-red-500 border-dashed inline-block shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Lingkaran Radius</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Karyawan Mode -->
                            <div v-else class="space-y-3">
                                <div>
                                    <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-slate-400 tracking-wider mb-1.5">Aktivitas Karyawan</div>
                                    <div class="space-y-2 pl-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full bg-pink-500 border border-white shadow-xs inline-block shrink-0"></span>
                                            <div>
                                                <div class="text-[11px] font-bold text-gray-800 dark:text-gray-200">Posisi Karyawan</div>
                                                <div class="text-[10px] text-gray-400 dark:text-slate-400">Absensi GPS & Tugas Lapangan Hari Ini</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

/* Dark Mode Leaflet Popup */
.dark .leaflet-popup-content-wrapper {
    background-color: #0f172a !important;
    color: #f1f5f9 !important;
    border-color: #334155 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6) !important;
}
.dark .leaflet-popup-tip {
    background-color: #0f172a !important;
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
