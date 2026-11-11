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
const onlyUnpaidCustomers = ref(false);
const showMapTiles = ref(true);
const showConnections = ref(true);

// Floating on-map legend visibility
const isMapLegendOpen = ref(true);

// Filters
const filterCabang = ref('');
const filterOdc = ref('');
const filterOdp = ref('');
const filterStatusPelanggan = ref('');

// Formatters & WhatsApp Link Helper
const formatRupiah = (val) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat('id-ID').format(Math.round(num));
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return String(dateStr).substring(0, 10);
        return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
    } catch (e) {
        return String(dateStr).substring(0, 10);
    }
};

const getWhatsAppUrl = (phone, customerName, totalUnpaid, dueDate) => {
    if (!phone) return null;
    let cleanPhone = String(phone).replace(/\D/g, '');
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.substring(1);
    } else if (!cleanPhone.startsWith('62')) {
        cleanPhone = '62' + cleanPhone;
    }
    const amountStr = totalUnpaid ? `Rp ${formatRupiah(totalUnpaid)}` : '';
    const dueStr = dueDate ? formatDateIndo(dueDate) : '';
    const message = encodeURIComponent(
        `Halo Bapak/Ibu ${customerName},\n\nKami dari tim VIZBILL ingin menginformasikan perihal tagihan internet Anda sebesar ${amountStr}${dueStr ? ' dengan jatuh tempo ' + dueStr : ''}. Mohon untuk melakukan pembayaran tepat waktu agar koneksi internet tetap aktif dan lancar. Terima kasih! 🙏`
    );
    return `https://wa.me/${cleanPhone}?text=${message}`;
};

// Filter options & Area Helper
const selectedAreaInfo = computed(() => {
    if (!filterCabang.value) return null;
    return props.areas.find(a => String(a.id) === String(filterCabang.value)) || null;
});

const matchesAreaFilter = (item) => {
    if (!filterCabang.value) return true;
    if (String(item.area_id) === String(filterCabang.value)) return true;
    const selected = props.areas.find(a => String(a.id) === String(filterCabang.value));
    if (selected && item.area_name && String(item.area_name).trim().toLowerCase() === String(selected.name).trim().toLowerCase()) return true;
    return false;
};

const setQuickArea = (areaId) => {
    filterCabang.value = areaId ? String(areaId) : '';
};

const filteredOdcOptions = computed(() => {
    if (!filterCabang.value) return props.odcs;
    return props.odcs.filter(o => matchesAreaFilter(o));
});

const filteredOdpOptions = computed(() => {
    let list = props.odps;
    if (filterCabang.value) {
        list = list.filter(o => matchesAreaFilter(o));
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
let routeLayerGroup = null;
const isCalculatingRoute = ref(false);
const activeRoute = ref(null);

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

const getGoogleMapsUrl = (lat, lng) => {
    const cLat = parseCoordinate(lat);
    const cLng = parseCoordinate(lng);
    if (cLat === null || cLng === null) return '#';
    let sLat = myLocation.value ? parseCoordinate(myLocation.value.lat) : null;
    let sLng = myLocation.value ? parseCoordinate(myLocation.value.lng) : null;
    if (sLat === null || sLng === null) {
        sLat = parseCoordinate(inputLat.value);
        sLng = parseCoordinate(inputLng.value);
    }
    if (sLat !== null && sLng !== null) {
        return `https://www.google.com/maps/dir/?api=1&origin=${sLat},${sLng}&destination=${cLat},${cLng}&travelmode=driving`;
    }
    return `https://www.google.com/maps/dir/?api=1&destination=${cLat},${cLng}&travelmode=driving`;
};

const scrollToMap = () => {
    if (mapEl.value) {
        mapEl.value.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

// Parse Coordinates (handle comma or dot)
const parseCoordinate = (val) => {
    if (val === null || val === undefined || val === '') return null;
    if (typeof val === 'number') return isFinite(val) ? val : null;
    const clean = String(val).trim().replace(',', '.');
    const num = parseFloat(clean);
    return isNaN(num) || !isFinite(num) ? null : num;
};

// Robust coordinate pair parser (supports Google Maps URL, Indonesian 3-comma format, space, and auto-swap)
const parseCoordPair = (raw) => {
    if (!raw) return null;
    const str = String(raw).trim();

    // 1. Google Maps URL or query parameter
    const urlMatch = str.match(/@(-?\d+(?:[.,]\d+)?),(-?\d+(?:[.,]\d+)?)/) ||
                     str.match(/[?&]q=(-?\d+(?:[.,]\d+)?),(-?\d+(?:[.,]\d+)?)/);
    if (urlMatch) {
        let lat = parseFloat(urlMatch[1].replace(',', '.'));
        let lng = parseFloat(urlMatch[2].replace(',', '.'));
        if (Math.abs(lat) > 90 || (lat > 20 && lng <= 20)) { const t = lat; lat = lng; lng = t; }
        if (lat > 5.0 && lat < 12.0 && lng >= 95.0 && lng <= 145.0) lat = -Math.abs(lat);
        if (lng < 0 && Math.abs(lng) >= 95.0 && Math.abs(lng) <= 145.0) lng = Math.abs(lng);
        return { lat, lng };
    }

    // 2. Pair of coordinates separated by comma, space, slash, semicolon, or pipe
    const pairMatch = str.match(/^(-?\d+(?:[.,]\d+)?)\s*[,/ ;|]\s*(-?\d+(?:[.,]\d+)?)$/);
    if (pairMatch) {
        let lat = parseFloat(pairMatch[1].replace(',', '.'));
        let lng = parseFloat(pairMatch[2].replace(',', '.'));
        if (!isNaN(lat) && !isNaN(lng)) {
            if (Math.abs(lat) > 90 || (lat > 20 && lng <= 20)) { const t = lat; lat = lng; lng = t; }
            if (lat > 5.0 && lat < 12.0 && lng >= 95.0 && lng <= 145.0) lat = -Math.abs(lat);
            if (lng < 0 && Math.abs(lng) >= 95.0 && Math.abs(lng) <= 145.0) lng = Math.abs(lng);
            return { lat, lng };
        }
    }

    return null;
};

// Set location coordinates directly (from click, drag, or input paste)
const setLocationCoordinates = (lat, lng, triggerSearch = true) => {
    let cleanLat = typeof lat === 'number' ? lat : parseFloat(String(lat).replace(',', '.'));
    let cleanLng = typeof lng === 'number' ? lng : parseFloat(String(lng).replace(',', '.'));

    if (isNaN(cleanLat) || isNaN(cleanLng)) return;

    // Auto-swap if swapped
    if (Math.abs(cleanLat) > 90 || (cleanLat > 20 && cleanLng <= 20)) {
        const tmp = cleanLat;
        cleanLat = cleanLng;
        cleanLng = tmp;
    }

    // Auto-fix Indonesia missing negative latitude
    if (cleanLat > 5.0 && cleanLat < 12.0 && cleanLng >= 95.0 && cleanLng <= 145.0) {
        cleanLat = -Math.abs(cleanLat);
    }

    // Auto-fix Indonesia negative longitude
    if (cleanLng < 0 && Math.abs(cleanLng) >= 95.0 && Math.abs(cleanLng) <= 145.0) {
        cleanLng = Math.abs(cleanLng);
    }

    inputLat.value = cleanLat.toFixed(14);
    inputLng.value = cleanLng.toFixed(6);
    myLocation.value = { lat: cleanLat, lng: cleanLng, accuracy: 10 };

    updateUserMarkerAndCircle(cleanLat, cleanLng, false);

    if (triggerSearch) {
        handleSearchNearby();
    }
};

// Smart coordinate paste & input handler for Latitude
const handleLatPasteOrInput = () => {
    if (!inputLat.value) return;
    const pair = parseCoordPair(inputLat.value);
    if (pair) {
        setLocationCoordinates(pair.lat, pair.lng, true);
        return;
    }
    // Single number comma normalization
    const raw = String(inputLat.value).trim();
    if (raw.includes(',')) {
        inputLat.value = raw.replace(',', '.');
    }
};

// Smart coordinate paste & input handler for Longitude
const handleLngPasteOrInput = () => {
    if (!inputLng.value) return;
    const pair = parseCoordPair(inputLng.value);
    if (pair) {
        setLocationCoordinates(pair.lat, pair.lng, true);
        return;
    }
    const raw = String(inputLng.value).trim();
    if (raw.includes(',')) {
        inputLng.value = raw.replace(',', '.');
    }
};

const escapeHtmlAttr = (str) => {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
};

// State for Nearby API & Results Display
const isSearchingNearby = ref(false);
const nearbyResults = ref(null);
const closestOdp = ref(null);
const closestOdc = ref(null);
const closestCustomer = ref(null);
const closestLineLayer = ref(null);

// Category Selection in Results: 'odp' | 'odc' | 'customer' | 'all'
const resultActiveTab = ref('odp');
const resultsSearchQuery = ref('');
const displayLimit = ref(12);

// Map of markers for instant popup opening on click
const markersMap = new Map();

// Filtered Lists (using nearby API results if search was performed, otherwise props)
const visibleOdcs = computed(() => {
    if (!checkOdc.value) return [];
    const sourceList = (nearbyResults.value && nearbyResults.value.odcs !== undefined)
        ? nearbyResults.value.odcs
        : props.odcs;

    return sourceList.filter(item => {
        if (!item.latitude || !item.longitude) return false;
        if (!matchesAreaFilter(item)) return false;
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
        if (!matchesAreaFilter(item)) return false;
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
        if (!matchesAreaFilter(item)) return false;
        if (filterOdc.value && item.odc_id != filterOdc.value) return false;
        if (filterOdp.value && item.odp_id != filterOdp.value) return false;

        // Filter checkbox: Hanya Belum Lunas
        if (onlyUnpaidCustomers.value && !item.is_unpaid) return false;

        if (filterStatusPelanggan.value) {
            if (filterStatusPelanggan.value === 'belum_lunas') {
                if (!item.is_unpaid) return false;
            } else if (filterStatusPelanggan.value === 'lunas') {
                if (item.is_unpaid) return false;
            } else if (filterStatusPelanggan.value === 'jatuh_tempo') {
                if (!item.is_unpaid || !item.is_overdue) return false;
            } else if (filterStatusPelanggan.value === 'janji_bayar') {
                if (!item.is_unpaid || !item.promise_date) return false;
            } else if (filterStatusPelanggan.value === 'gratis') {
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

// ─── DAFTAR PERANGKAT & PELANGGAN TERDEKAT (SORTED BY DISTANCE) ───
const nearbyOdpsList = computed(() => {
    if (!myLocation.value || !checkOdp.value) return [];
    const source = (nearbyResults.value && nearbyResults.value.odps && nearbyResults.value.odps.length > 0)
        ? nearbyResults.value.odps
        : props.odps;

    return source.map(item => {
        const lat = parseFloat(item.latitude);
        const lng = parseFloat(item.longitude);
        if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return null;

        const dist = item.distance_meters !== undefined
            ? item.distance_meters
            : Math.round(calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, lat, lng));

        const totalPorts = parseInt(item.total_ports ?? 8, 10);
        const usedPorts = parseInt(item.used_ports ?? 0, 10);
        const availablePorts = item.available_ports !== undefined
            ? item.available_ports
            : Math.max(0, totalPorts - usedPorts);

        return {
            ...item,
            _type: 'odp',
            latitude: lat,
            longitude: lng,
            distance_meters: dist,
            total_ports: totalPorts,
            used_ports: usedPorts,
            available_ports: availablePorts,
            odc_name: item.odc_name || item.odc?.name || '-',
            area_name: item.area_name || item.area?.name || '-',
            address: item.address || 'Tanpa Alamat',
            status: item.status || 'active',
        };
    })
    .filter(item => {
        if (!item) return false;
        if (!matchesAreaFilter(item)) return false;
        if (filterOdc.value && item.odc_id != filterOdc.value) return false;
        if (filterOdp.value && item.id != filterOdp.value) return false;
        if (searchRadiusMeters.value && item.distance_meters > searchRadiusMeters.value) return false;
        return true;
    })
    .sort((a, b) => a.distance_meters - b.distance_meters);
});

const nearbyOdcsList = computed(() => {
    if (!myLocation.value || !checkOdc.value) return [];
    const source = (nearbyResults.value && nearbyResults.value.odcs && nearbyResults.value.odcs.length > 0)
        ? nearbyResults.value.odcs
        : props.odcs;

    return source.map(item => {
        const lat = parseFloat(item.latitude);
        const lng = parseFloat(item.longitude);
        if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return null;

        const dist = item.distance_meters !== undefined
            ? item.distance_meters
            : Math.round(calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, lat, lng));

        return {
            ...item,
            _type: 'odc',
            latitude: lat,
            longitude: lng,
            distance_meters: dist,
            odps_count: item.odps_count ?? (props.odps.filter(o => o.odc_id == item.id).length),
            area_name: item.area_name || item.area?.name || '-',
            address: item.address || item.location || 'Tanpa Alamat',
            status: item.status || 'active',
        };
    })
    .filter(item => {
        if (!item) return false;
        if (!matchesAreaFilter(item)) return false;
        if (filterOdc.value && item.id != filterOdc.value) return false;
        if (searchRadiusMeters.value && item.distance_meters > searchRadiusMeters.value) return false;
        return true;
    })
    .sort((a, b) => a.distance_meters - b.distance_meters);
});

const nearbyCustomersList = computed(() => {
    if (!myLocation.value || !checkPelanggan.value) return [];
    const source = (nearbyResults.value && nearbyResults.value.customers && nearbyResults.value.customers.length > 0)
        ? nearbyResults.value.customers
        : props.customers;

    return source.map(item => {
        const lat = parseFloat(item.latitude);
        const lng = parseFloat(item.longitude);
        if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return null;

        const dist = item.distance_meters !== undefined
            ? item.distance_meters
            : Math.round(calculateDistanceMeters(myLocation.value.lat, myLocation.value.lng, lat, lng));

        return {
            ...item,
            _type: item._type || (item.is_reseller ? 'reseller' : (item.status === 'booking' ? 'booking' : 'customer')),
            latitude: lat,
            longitude: lng,
            distance_meters: dist,
            odp_name: item.odp_name || item.odp?.name || '-',
            area_name: item.area_name || item.areaModel?.name || item.area || '-',
            address: item.address || 'Tanpa Alamat',
            status: item.status || 'active',
            is_unpaid: !!item.is_unpaid,
            total_unpaid: Number(item.total_unpaid) || 0,
            unpaid_invoices_count: Number(item.unpaid_invoices_count) || 0,
            due_date: item.due_date || null,
            promise_date: item.promise_date || null,
            is_overdue: !!item.is_overdue,
            package_name: item.package_name || '-',
            package_price: Number(item.package_price) || 0,
            phone: item.phone || '',
        };
    })
    .filter(item => {
        if (!item) return false;
        if (!matchesAreaFilter(item)) return false;
        if (filterOdc.value && item.odc_id != filterOdc.value) return false;
        if (filterOdp.value && item.odp_id != filterOdp.value) return false;

        // Filter checkbox: Hanya Belum Lunas
        if (onlyUnpaidCustomers.value && !item.is_unpaid) return false;

        if (filterStatusPelanggan.value) {
            if (filterStatusPelanggan.value === 'belum_lunas') {
                if (!item.is_unpaid) return false;
            } else if (filterStatusPelanggan.value === 'lunas') {
                if (item.is_unpaid) return false;
            } else if (filterStatusPelanggan.value === 'jatuh_tempo') {
                if (!item.is_unpaid || !item.is_overdue) return false;
            } else if (filterStatusPelanggan.value === 'janji_bayar') {
                if (!item.is_unpaid || !item.promise_date) return false;
            } else if (filterStatusPelanggan.value === 'gratis') {
                if (item.service_status !== 'gratis') return false;
            } else if (filterStatusPelanggan.value === 'free_home') {
                if (item.service_status !== 'free_home') return false;
            } else if (filterStatusPelanggan.value === 'terminated') {
                if (!['terminated', 'canceled', 'inactive'].includes(item.status)) return false;
            } else {
                if (item.status !== filterStatusPelanggan.value) return false;
            }
        }
        if (searchRadiusMeters.value && item.distance_meters > searchRadiusMeters.value) return false;
        return true;
    })
    .sort((a, b) => a.distance_meters - b.distance_meters);
});

// Unpaid count among nearby customers
const nearbyCustomersUnpaidCount = computed(() => {
    return nearbyCustomersList.value.filter(c => c.is_unpaid).length;
});

// Total count across all active checked categories
const totalNearbyCount = computed(() => {
    return (checkOdp.value ? nearbyOdpsList.value.length : 0) +
           (checkOdc.value ? nearbyOdcsList.value.length : 0) +
           (checkPelanggan.value ? nearbyCustomersList.value.length : 0);
});

// Item yang aktif ditampilkan di card Hasil Radar
const activeTabItems = computed(() => {
    let list = [];
    if (resultActiveTab.value === 'odp') {
        list = checkOdp.value ? nearbyOdpsList.value : [];
    } else if (resultActiveTab.value === 'odc') {
        list = checkOdc.value ? nearbyOdcsList.value : [];
    } else if (resultActiveTab.value === 'customer') {
        list = checkPelanggan.value ? nearbyCustomersList.value : [];
    } else {
        const odps = checkOdp.value ? nearbyOdpsList.value : [];
        const odcs = checkOdc.value ? nearbyOdcsList.value : [];
        const custs = checkPelanggan.value ? nearbyCustomersList.value : [];
        list = [...odps, ...odcs, ...custs]
            .sort((a, b) => a.distance_meters - b.distance_meters);
    }
    return list;
});

const filteredActiveTabItems = computed(() => {
    const list = activeTabItems.value;
    if (!resultsSearchQuery.value) return list;
    const q = resultsSearchQuery.value.toLowerCase().trim();
    return list.filter(item =>
        (item.name && item.name.toLowerCase().includes(q)) ||
        (item.customer_code && item.customer_code.toLowerCase().includes(q)) ||
        (item.address && item.address.toLowerCase().includes(q)) ||
        (item.odc_name && item.odc_name.toLowerCase().includes(q)) ||
        (item.odp_name && item.odp_name.toLowerCase().includes(q)) ||
        (item.area_name && item.area_name.toLowerCase().includes(q))
    );
});

const displayedNearbyItems = computed(() => {
    return filteredActiveTabItems.value.slice(0, displayLimit.value);
});

// Stats Counts
const stats = computed(() => {
    const unpaidList = visibleCustomers.value.filter(c => c.is_unpaid);
    const totalUnpaidNominal = unpaidList.reduce((sum, c) => sum + (Number(c.total_unpaid) || 0), 0);

    return {
        odc: visibleOdcs.value.length,
        odp: visibleOdps.value.length,
        pelanggan: visibleCustomers.value.length,
        pelanggan_belum_lunas: unpaidList.length,
        total_nominal_belum_lunas: totalUnpaidNominal,
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

// Focus map to specific Item (ODP / ODC / Pelanggan)
const focusToItem = (item) => {
    if (!map || !item) return;
    const lat = parseCoordinate(item.latitude);
    const lng = parseCoordinate(item.longitude);
    if (lat === null || lng === null) return;

    // Auto-enable map tiles and category layer if hidden
    if (!showMapTiles.value) showMapTiles.value = true;
    const rawType = item._type || 'odp';
    if (['customer', 'reseller', 'booking'].includes(rawType) && !checkPelanggan.value) {
        checkPelanggan.value = true;
    } else if (rawType === 'odp' && !checkOdp.value) {
        checkOdp.value = true;
    } else if (rawType === 'odc' && !checkOdc.value) {
        checkOdc.value = true;
    }

    scrollToMap();
    map.flyTo([lat, lng], 18, { duration: 1.2 });

    const normalizedType = ['customer', 'reseller', 'booking'].includes(rawType) ? 'customer' : rawType;
    const key = `${normalizedType}-${item.id}`;

    setTimeout(() => {
        const marker = markersMap.get(key)
            || markersMap.get(`customer-${item.id}`)
            || markersMap.get(`cust-${item.id}`)
            || markersMap.get(`odp-${item.id}`)
            || markersMap.get(`odc-${item.id}`);

        if (marker) {
            if (clusterGroup && clusterGroup.hasLayer(marker)) {
                clusterGroup.zoomToShowLayer(marker, () => {
                    marker.openPopup();
                });
            } else {
                marker.openPopup();
            }
        }
    }, 600);
};

const focusToOdp = (odp) => focusToItem({ ...odp, _type: 'odp' });
const focusToOdc = (odc) => focusToItem({ ...odc, _type: 'odc' });
const focusToCustomer = (c) => focusToItem({ ...c, _type: 'customer' });

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
        const types = [];
        if (checkOdp.value) types.push('odp');
        if (checkOdc.value) types.push('odc');
        if (checkPelanggan.value) types.push('customer');

        const response = await axios.post('/network-radar/nearby', {
            lat,
            lng,
            radius: searchRadiusMeters.value || 5000,
            types: types.length ? types : ['odp', 'odc', 'customer'],
            type: types.length === 1 ? types[0] : (types.length === 3 ? 'all' : types.join(',')),
            area_id: filterCabang.value || null,
        });

        nearbyResults.value = response.data;
        closestOdp.value = response.data.closest_odp || null;
        closestOdc.value = response.data.closest_odc || null;
        closestCustomer.value = response.data.closest_customer || null;

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
        draggable: true,
    }).addTo(map);

    myLocationMarker.on('dragend', (e) => {
        const pos = e.target.getLatLng();
        setLocationCoordinates(pos.lat, pos.lng, true);
    });

    myLocationMarker.bindPopup(`
        <div class="font-sans text-xs min-w-[190px]">
            <div class="font-bold text-red-600 mb-0.5 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-600 inline-block animate-pulse"></span>
                <span>Pusat Radar (Bisa Digeser)</span>
            </div>
            <div class="text-gray-500 text-[11px] mb-1">Titik Acuan Pencarian Terdekat</div>
            <div class="bg-gray-50 dark:bg-slate-800 p-1.5 rounded font-mono text-[11px] text-gray-700 dark:text-slate-300 border border-gray-100 dark:border-slate-700">
                ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>
            <div class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold mt-1.5">
                💡 Geser pin ini atau klik peta untuk pindah posisi radar
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
        map.flyTo([lat, lng], 14, { duration: 1 });
    }
};

// Clear active route
const clearRoute = () => {
    activeRoute.value = null;
    if (routeLayerGroup && map) {
        routeLayerGroup.clearLayers();
    }
};

// Real Road Route (OSRM with Resilient Fallback)
const drawRoadRoute = async (targetLat, targetLng, targetName) => {
    if (!map || !L) return;

    const dLat = parseCoordinate(targetLat);
    const dLng = parseCoordinate(targetLng);
    if (dLat === null || dLng === null) {
        alert('Koordinat titik tujuan tidak valid.');
        return;
    }

    // Determine starting location (user location or manual coordinates)
    let sLat = myLocation.value ? parseCoordinate(myLocation.value.lat) : null;
    let sLng = myLocation.value ? parseCoordinate(myLocation.value.lng) : null;

    if (sLat === null || sLng === null) {
        sLat = parseCoordinate(inputLat.value);
        sLng = parseCoordinate(inputLng.value);
        if (sLat !== null && sLng !== null) {
            myLocation.value = { lat: sLat, lng: sLng, accuracy: 50 };
            updateUserMarkerAndCircle(sLat, sLng, false);
        } else {
            getMyLocation();
            return;
        }
    }

    // Smoothly scroll to map container immediately
    scrollToMap();
    isCalculatingRoute.value = true;

    if (!routeLayerGroup) {
        routeLayerGroup = L.layerGroup().addTo(map);
    }
    routeLayerGroup.clearLayers();

    try {
        let routeData = null;

        // 1. Try server-side route calculation (OSRM + backend cache)
        try {
            const res = await axios.post('/network-radar/route', {
                origin_lat: sLat,
                origin_lng: sLng,
                dest_lat: dLat,
                dest_lng: dLng,
            });
            if (res.data && res.data.success) {
                routeData = res.data;
            }
        } catch (apiErr) {
            console.warn('Backend route API warning, will use fallback:', apiErr);
        }

        // 2. Client-side fallback if backend unavailable or network interrupted
        if (!routeData) {
            const dist = calculateDistanceMeters(sLat, sLng, dLat, dLng);
            const dur = Math.max(60, (dist / 30000) * 3600);
            routeData = {
                success: true,
                status: 'fallback',
                source: 'straight_line',
                distance: dist,
                duration: dur,
                formatted_distance: formatDistance(dist),
                formatted_duration: formatTime(dur),
                geometry: {
                    type: 'LineString',
                    coordinates: [
                        [sLng, sLat],
                        [dLng, dLat]
                    ]
                }
            };
        }

        // Update active route state
        activeRoute.value = {
            ...routeData,
            targetName: targetName || 'Titik Tujuan',
            targetLat: dLat,
            targetLng: dLng,
            originLat: sLat,
            originLng: sLng,
        };

        // Multi-layer Route Polyline for Razor-Sharp Visibility on Satellite & Road
        // Layer 1: Contrast Outline (White)
        L.geoJSON(routeData.geometry, {
            style: {
                color: '#ffffff',
                weight: 7,
                opacity: 0.95,
                lineCap: 'round',
                lineJoin: 'round',
            }
        }).addTo(routeLayerGroup);

        // Layer 2: Main vivid line (Blue for OSRM road, Purple for direct fallback)
        const mainPoly = L.geoJSON(routeData.geometry, {
            style: {
                color: routeData.source === 'osrm' ? '#2563eb' : '#9333ea',
                weight: 4.5,
                opacity: 0.95,
                dashArray: routeData.source === 'osrm' ? null : '6, 8',
                lineCap: 'round',
                lineJoin: 'round',
            }
        }).addTo(routeLayerGroup);

        // Destination Badge on Map
        L.marker([dLat, dLng], {
            icon: L.divIcon({
                className: 'route-target-badge',
                html: `
                    <div class="px-2.5 py-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-blue-500/80 rounded-xl shadow-xl text-center transform -translate-y-12 select-none min-w-[90px]">
                        <div class="text-[11px] font-black text-gray-900 dark:text-white truncate">${escapeHtmlAttr(targetName || 'Tujuan')}</div>
                        <div class="text-[10px] font-bold text-blue-600 dark:text-blue-400">${routeData.formatted_distance} • 🚗 ${routeData.formatted_duration}</div>
                    </div>
                `,
                iconSize: [110, 40],
                iconAnchor: [55, 20],
            }),
            zIndexOffset: 2000,
        }).addTo(routeLayerGroup);

        // Fit map bounds smoothly
        if (mainPoly.getBounds && mainPoly.getBounds().isValid()) {
            map.fitBounds(mainPoly.getBounds(), { padding: [70, 70], maxZoom: 18 });
        }
    } catch (err) {
        console.error('Fatal route calculation error:', err);
    } finally {
        isCalculatingRoute.value = false;
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
    markersMap.clear();

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
            markersMap.set(`emp-${emp.id}`, marker);
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
        markersMap.set(`odc-${odc.id}`, marker);
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
        markersMap.set(`odp-${odp.id}`, marker);
    });

    // 3. Plot Customers
    visibleCustomers.value.forEach(c => {
        const lat = parseFloat(c.latitude);
        const lng = parseFloat(c.longitude);
        if (isNaN(lat) || isNaN(lng) || !isFinite(lat) || !isFinite(lng)) return;

        let dotColor = '#16a34a'; // default Aktif & Lunas (green)
        let labelStatus = 'Aktif';

        if (c.is_unpaid) {
            if (c.is_overdue) {
                dotColor = '#dc2626'; // Overdue / Jatuh tempo (red)
                labelStatus = 'Lewat Jatuh Tempo';
            } else if (c.promise_date) {
                dotColor = '#d97706'; // Promise date (amber)
                labelStatus = 'Janji Bayar';
            } else {
                dotColor = '#e11d48'; // Belum lunas regular (rose)
                labelStatus = 'Belum Lunas';
            }
        } else if (c.service_status === 'gratis') {
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
            icon: createDotIcon(dotColor, c.is_unpaid ? 12 : 11),
        });

        // WhatsApp direct link
        const waUrl = c.phone ? getWhatsAppUrl(c.phone, c.name, c.total_unpaid, c.due_date) : null;

        // Billing status card HTML
        const billingBoxHtml = c.is_unpaid ? `
            <div class="p-2 mb-2 rounded-lg bg-rose-50 dark:bg-rose-950/70 border border-rose-200 dark:border-rose-900/60">
                <div class="flex items-center justify-between text-[11px] font-bold text-rose-700 dark:text-rose-300">
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block animate-pulse"></span>
                        BELUM LUNAS (${c.unpaid_invoices_count || 1} Inv)
                    </span>
                    <span class="font-extrabold text-xs">Rp ${formatRupiah(c.total_unpaid)}</span>
                </div>
                ${c.due_date ? `
                    <div class="text-[10px] text-rose-600 dark:text-rose-400 mt-1 flex items-center justify-between">
                        <span>Jatuh Tempo: <strong>${formatDateIndo(c.due_date)}</strong></span>
                        ${c.is_overdue ? '<span class="px-1 py-0.2 rounded bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200 font-extrabold text-[9px]">OVERDUE</span>' : ''}
                    </div>
                ` : ''}
                ${c.promise_date ? `
                    <div class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">
                        🤝 Janji Bayar: <strong>${formatDateIndo(c.promise_date)}</strong>
                    </div>
                ` : ''}
            </div>
        ` : `
            <div class="px-2 py-1 mb-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-900/60 flex items-center justify-between text-[10px] text-emerald-700 dark:text-emerald-300 font-bold">
                <span>Status Tagihan:</span>
                <span>✅ LUNAS</span>
            </div>
        `;

        // Package info HTML
        const packageInfoHtml = (c.package_name && c.package_name !== '-') ? `
            <div class="text-[10px] text-gray-600 dark:text-slate-300 mb-2 flex items-center justify-between bg-gray-50 dark:bg-slate-800/60 px-2 py-1 rounded">
                <span>📦 Paket: <strong>${escapeHtmlAttr(c.package_name)}</strong></span>
                <span class="font-bold text-gray-700 dark:text-slate-200">${c.package_price ? 'Rp ' + formatRupiah(c.package_price) : ''}</span>
            </div>
        ` : '';

        // WA Button HTML
        const waButtonHtml = waUrl ? `
            <a href="${waUrl}" target="_blank" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold inline-flex items-center gap-1 shadow-2xs" title="Kirim WA Penagihan">
                💬 WA
            </a>
        ` : '';

        marker.bindPopup(`
            <div class="font-sans text-xs min-w-[220px]">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: ${dotColor}">Pelanggan</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold" style="background-color: ${dotColor}20; color: ${dotColor}">
                        ${labelStatus}
                    </span>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-0.5">${c.name}</h4>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono mb-1.5">${c.customer_code || '-'}</p>

                ${billingBoxHtml}
                ${packageInfoHtml}

                <div class="text-[10px] text-gray-500 dark:text-slate-400 font-medium mb-1">🔌 ODP: ${c.odp_name || c.odp?.name || '-'}</div>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">${c.address || 'Tanpa Alamat'}</p>
                <div class="text-[11px] bg-gray-50 dark:bg-slate-800/80 p-1.5 rounded border border-gray-100 dark:border-slate-700 font-mono text-gray-600 dark:text-slate-300 mb-2.5">
                    📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-slate-800 gap-1.5">
                    <div class="flex items-center gap-2">
                        <a href="${getGoogleMapsUrl(lat, lng)}" target="_blank" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                            🗺️ Navigasi
                        </a>
                        <button type="button" class="text-[11px] font-bold text-gray-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 route-btn cursor-pointer" onclick="window.drawRadarRoute(${lat}, ${lng}, '${escapeHtmlAttr(c.name)}')">
                            🚗 Rute
                        </button>
                    </div>
                    ${waButtonHtml}
                </div>
            </div>
        `);
        clusterGroup.addLayer(marker);
        markersMap.set(`customer-${c.id}`, marker);
        markersMap.set(`cust-${c.id}`, marker);
    });

    updateConnections();
};

// Watchers
watch([checkOdc, checkOdp, checkPelanggan, onlyUnpaidCustomers, filterOdp, filterStatusPelanggan, activeTab], () => {
    refreshMapMarkers();
});

watch(onlyUnpaidCustomers, (val) => {
    if (val && !checkPelanggan.value) {
        checkPelanggan.value = true;
    }
});

// Auto-switch resultActiveTab if the selected category is disabled
watch([checkOdp, checkOdc, checkPelanggan], () => {
    if (resultActiveTab.value === 'odp' && !checkOdp.value) {
        if (checkOdc.value) resultActiveTab.value = 'odc';
        else if (checkPelanggan.value) resultActiveTab.value = 'customer';
        else resultActiveTab.value = 'all';
    } else if (resultActiveTab.value === 'odc' && !checkOdc.value) {
        if (checkOdp.value) resultActiveTab.value = 'odp';
        else if (checkPelanggan.value) resultActiveTab.value = 'customer';
        else resultActiveTab.value = 'all';
    } else if (resultActiveTab.value === 'customer' && !checkPelanggan.value) {
        if (checkOdp.value) resultActiveTab.value = 'odp';
        else if (checkOdc.value) resultActiveTab.value = 'odc';
        else resultActiveTab.value = 'all';
    }
});

watch(filterCabang, (newVal) => {
    filterOdc.value = '';
    filterOdp.value = '';

    if (newVal) {
        const selected = props.areas.find(a => String(a.id) === String(newVal));
        if (selected && selected.center_lat && selected.center_lng) {
            setLocationCoordinates(selected.center_lat, selected.center_lng, false);
            if (map) {
                map.flyTo([selected.center_lat, selected.center_lng], 15, { duration: 0.8 });
            }
        }
    }

    if (myLocation.value) {
        handleSearchNearby();
    } else {
        refreshMapMarkers();
    }
});

watch(filterOdc, (newVal) => {
    filterOdp.value = '';
    refreshMapMarkers();
    if (newVal) {
        const targetOdc = props.odcs.find(o => o.id == newVal);
        if (targetOdc) {
            focusToOdc(targetOdc);
        }
    }
});

watch(filterOdp, (newVal) => {
    refreshMapMarkers();
    if (newVal) {
        const targetOdp = props.odps.find(o => o.id == newVal);
        if (targetOdp) {
            focusToOdp(targetOdp);
        }
    }
});

const resetAllFilters = () => {
    filterCabang.value = '';
    filterOdc.value = '';
    filterOdp.value = '';
    filterStatusPelanggan.value = '';
    onlyUnpaidCustomers.value = false;
    resultsSearchQuery.value = '';
    refreshMapMarkers();
};

watch(showConnections, (val) => {
    updateConnections();
    if (!val && closestLineLayer.value && map) {
        map.removeLayer(closestLineLayer.value);
    } else if (val && closestOdp.value) {
        drawDirectLineToClosestOdp();
    }
});

// ═══ BASEMAP CONFIGURATIONS (GOOGLE HYBRID, SATELIT, OSM, GOOGLE STREET, DARK, TOPO) ═══
const basemaps = [
    {
        id: 'google_hybrid',
        label: 'Google Satelit (Hybrid)',
        shortLabel: 'Satelit',
        description: 'Citra satelit foto Google + nama jalan & tempat lengkap',
        icon: '🛰️',
        badge: 'Rekomendasi',
        previewClass: 'from-emerald-700 via-sky-800 to-slate-900 text-white',
        url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
        attribution: '&copy; Google Maps Satelit',
        maxZoom: 22,
        maxNativeZoom: 20,
    },
    {
        id: 'google_sat',
        label: 'Google Satelit (Murni)',
        shortLabel: 'Satelit Polos',
        description: 'Citra foto satelit jernih tanpa label nama jalan',
        icon: '🌍',
        previewClass: 'from-emerald-950 via-teal-950 to-slate-950 text-white',
        url: 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
        attribution: '&copy; Google Maps',
        maxZoom: 22,
        maxNativeZoom: 20,
    },
    {
        id: 'google_streets',
        label: 'Google Maps (Jalan)',
        shortLabel: 'Google Maps',
        description: 'Peta jalan standar resmi Google Maps',
        icon: '🛣️',
        previewClass: 'from-sky-100 via-emerald-100 to-blue-200 text-slate-800',
        url: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
        attribution: '&copy; Google Maps',
        maxZoom: 22,
        maxNativeZoom: 20,
    },
    {
        id: 'osm',
        label: 'OpenStreetMap (Standar)',
        shortLabel: 'Peta Jalan',
        description: 'Peta jalan umum OpenStreetMap standar',
        icon: '🗺️',
        previewClass: 'from-amber-100 via-emerald-100 to-sky-200 text-slate-800',
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
        maxNativeZoom: 19,
        subdomains: ['a', 'b', 'c'],
    },
    {
        id: 'carto_dark',
        label: 'Mode Gelap (Dark)',
        shortLabel: 'Mode Gelap',
        description: 'Peta kontras tinggi, elegan & fokus di malam hari',
        icon: '🌑',
        previewClass: 'from-slate-900 via-black to-slate-950 text-white',
        url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 20,
        maxNativeZoom: 19,
        subdomains: ['a', 'b', 'c', 'd'],
    },
    {
        id: 'topo',
        label: 'Topografi (Medan)',
        shortLabel: 'Topografi',
        description: 'Peta kontur elevasi & bentang alam pegunungan',
        icon: '⛰️',
        previewClass: 'from-amber-200 via-lime-200 to-emerald-300 text-slate-800',
        url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap &copy; OpenTopoMap',
        maxZoom: 17,
        maxNativeZoom: 17,
        subdomains: ['a', 'b', 'c'],
    },
];

const activeBasemap = ref('google_hybrid');
const isMapTypeMenuOpen = ref(false);

const currentBasemap = computed(() => {
    return basemaps.find(b => b.id === activeBasemap.value) || basemaps[0];
});

const quickBasemaps = computed(() => [
    basemaps.find(b => b.id === 'google_hybrid'),
    basemaps.find(b => b.id === 'osm'),
    basemaps.find(b => b.id === 'google_streets'),
    basemaps.find(b => b.id === 'carto_dark'),
].filter(Boolean));

const changeBasemap = (basemapId) => {
    const selected = basemaps.find(b => b.id === basemapId) || basemaps[0];
    activeBasemap.value = selected.id;
    try {
        localStorage.setItem('apm_radar_basemap', selected.id);
    } catch (e) {}

    if (!map || !L) return;

    if (tileLayer) {
        map.removeLayer(tileLayer);
        tileLayer = null;
    }

    if (showMapTiles.value) {
        tileLayer = L.tileLayer(selected.url, {
            attribution: selected.attribution,
            maxZoom: selected.maxZoom || 22,
            maxNativeZoom: selected.maxNativeZoom || 19,
            subdomains: selected.subdomains || ['a', 'b', 'c'],
        });
        tileLayer.addTo(map);
        if (typeof tileLayer.bringToBack === 'function') {
            tileLayer.bringToBack();
        }
    }
};

watch(showMapTiles, (val) => {
    if (!map) return;
    if (val) {
        if (!tileLayer) {
            changeBasemap(activeBasemap.value);
        } else {
            tileLayer.addTo(map);
            if (typeof tileLayer.bringToBack === 'function') {
                tileLayer.bringToBack();
            }
        }
    } else {
        if (tileLayer) {
            map.removeLayer(tileLayer);
        }
    }
});

let radiusDebounceTimer = null;
watch(searchRadiusMeters, (newVal) => {
    if (myLocationCircle && myLocation.value) {
        myLocationCircle.setRadius(newVal || 5000);
    }
    clearTimeout(radiusDebounceTimer);
    radiusDebounceTimer = setTimeout(() => {
        if (myLocation.value) {
            handleSearchNearby();
        } else {
            refreshMapMarkers();
        }
    }, 400);
});

const handleDocumentClick = (e) => {
    if (!e.target.closest('.basemap-menu-container')) {
        isMapTypeMenuOpen.value = false;
    }
};

// Map Initialization
onMounted(async () => {
    await nextTick();

    // Close basemap dropdown on click outside
    window.addEventListener('click', handleDocumentClick);

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
        maxZoom: 22,
    });

    // Determine initial basemap: from localStorage or default to 'google_hybrid'
    let savedBasemap = 'google_hybrid';
    try {
        const stored = localStorage.getItem('apm_radar_basemap');
        if (stored && basemaps.some(b => b.id === stored)) {
            savedBasemap = stored;
        }
    } catch (e) {}
    activeBasemap.value = savedBasemap;

    // Load active tile layer
    if (showMapTiles.value) {
        changeBasemap(savedBasemap);
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

    // Map Click: Set radar coordinates dynamically anywhere the user clicks
    map.on('click', (e) => {
        setLocationCoordinates(e.latlng.lat, e.latlng.lng, true);
    });

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
    window.removeEventListener('click', handleDocumentClick);
    if (window.drawRadarRoute) delete window.drawRadarRoute;
    if (closestLineLayer.value && map) {
        map.removeLayer(closestLineLayer.value);
        closestLineLayer.value = null;
    }
    if (routeLayerGroup && map) {
        routeLayerGroup.clearLayers();
        map.removeLayer(routeLayerGroup);
        routeLayerGroup = null;
    }
    if (tileLayer && map) {
        map.removeLayer(tileLayer);
        tileLayer = null;
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
                            @input="handleLngPasteOrInput"
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
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-blue-600 dark:text-blue-400 font-semibold bg-blue-50/80 dark:bg-blue-950/40 px-3 py-1.5 rounded-xl border border-blue-200/60 dark:border-blue-800/40">
                        <span>💡</span>
                        <span>Tips: Klik langsung di area peta mana saja atau geser pin merah untuk memindahkan titik koordinat radar secara instan.</span>
                    </div>
                    <p v-if="gpsError" class="text-[11px] text-red-500 font-medium mt-1">{{ gpsError }}</p>
                </div>

                <!-- ═══ AREA / WILAYAH & RADIUS PENCARIAN ═══ -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Kolom 1: Filter Area / Wilayah -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Area / Wilayah</span>
                            </label>
                            <span v-if="selectedAreaInfo" class="text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-md border border-blue-200/60 dark:border-blue-800/40">
                                {{ selectedAreaInfo.customer_count ?? 0 }} Cust &bull; {{ selectedAreaInfo.odp_count ?? 0 }} ODP
                            </span>
                        </div>
                        <div class="relative">
                            <select
                                v-model="filterCabang"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white font-medium focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors cursor-pointer"
                            >
                                <option value="">Semua Area / Wilayah</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    {{ area.name }} {{ area.customer_count !== undefined ? `(${area.customer_count} Pelanggan • ${area.odp_count} ODP)` : '' }}
                                </option>
                            </select>
                        </div>
                        <!-- Quick Area Pills -->
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <button
                                type="button"
                                @click="setQuickArea('')"
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer',
                                    !filterCabang
                                        ? 'bg-blue-900 text-white shadow-2xs'
                                        : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700'
                                ]"
                            >
                                Semua
                            </button>
                            <button
                                type="button"
                                v-for="area in areas"
                                :key="area.id"
                                @click="setQuickArea(area.id)"
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer flex items-center gap-1',
                                    filterCabang == area.id
                                        ? 'bg-blue-900 text-white shadow-2xs'
                                        : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700'
                                ]"
                            >
                                <span>{{ area.name }}</span>
                                <span v-if="area.customer_count !== undefined" class="text-[9px] opacity-80 font-normal">
                                    ({{ area.customer_count }})
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Kolom 2: Radius Pencarian (meter) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="4" stroke-width="2"/>
                                </svg>
                                <span>Radius Pencarian</span>
                            </label>
                            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-extrabold">
                                {{ formatDistance(searchRadiusMeters) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                v-model.number="searchRadiusMeters"
                                @keydown.enter="handleSearchNearby"
                                placeholder="5000"
                                class="flex-1 px-3.5 py-2.5 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                            />
                            <!-- Quick Radius Presets -->
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    v-for="r in [300, 1000, 5000]"
                                    :key="r"
                                    @click="searchRadiusMeters = r; handleSearchNearby()"
                                    :class="[
                                        'px-2.5 py-2 text-[11px] font-bold rounded-xl border transition-all cursor-pointer',
                                        searchRadiusMeters === r
                                            ? 'bg-blue-600 text-white border-blue-600 shadow-2xs'
                                            : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700'
                                    ]"
                                >
                                    {{ r < 1000 ? r + 'm' : (r / 1000) + 'km' }}
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-2">
                            Pencarian radar akan memindai seluruh perangkat & pelanggan di dalam radius ini dari titik koordinat.
                        </p>
                    </div>
                </div>

                <!-- Apa yang ingin dicek? -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-2">Apa yang ingin dicek?</label>
                    <!-- Row 1: ODC, ODP, Pelanggan, Hanya Belum Lunas -->
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-2.5">
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
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none px-2.5 py-1 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 hover:bg-rose-100/70 dark:hover:bg-rose-900/40 transition-colors">
                            <input type="checkbox" v-model="onlyUnpaidCustomers" class="rounded text-rose-600 focus:ring-rose-500 border-rose-300 dark:border-rose-700 dark:bg-slate-800 w-4 h-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600 inline-block animate-pulse"></span>
                            <span class="text-xs font-bold text-rose-700 dark:text-rose-300">Hanya Belum Lunas</span>
                        </label>
                    </div>
                    <!-- Row 2: Tampilkan Peta, Tampilkan Koneksi, Model Tampilan Peta -->
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4">
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

                        <!-- Pilihan Tampilan Maps -->
                        <div v-if="showMapTiles" class="inline-flex items-center gap-1.5 pl-2 sm:pl-3 border-l border-gray-200 dark:border-slate-700">
                            <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">Model Maps:</span>
                            <select
                                :value="activeBasemap"
                                @change="changeBasemap($event.target.value)"
                                class="px-2.5 py-1 text-xs font-bold rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer"
                            >
                                <option v-for="b in basemaps" :key="b.id" :value="b.id">
                                    {{ b.icon }} {{ b.label }}
                                </option>
                            </select>
                        </div>
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
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <!-- Header Card & Category Filter Tabs -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex flex-wrap items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
                            <span>Hasil Radar Terdekat</span>
                            <span class="text-[11px] font-medium text-gray-500 dark:text-slate-400">
                                (Radius: {{ formatDistance(searchRadiusMeters) }})
                            </span>
                            <span v-if="selectedAreaInfo" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                <span>Area: {{ selectedAreaInfo.name }}</span>
                                <button type="button" @click.stop="setQuickArea('')" class="hover:text-red-500 font-black ml-1 text-xs cursor-pointer" title="Hapus filter area">&times;</button>
                            </span>
                            <span v-if="onlyUnpaidCustomers" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900">
                                Hanya Belum Lunas
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Pilih kategori untuk melihat daftar perangkat atau pelanggan terdekat beserta status dan port detail.
                        </p>
                    </div>

                    <!-- Category Tabs Pill -->
                    <div class="inline-flex p-1 bg-gray-100 dark:bg-slate-800 rounded-xl text-xs font-semibold">
                        <button
                            type="button"
                            @click="resultActiveTab = 'odp'"
                            :class="[
                                'px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer',
                                resultActiveTab === 'odp'
                                    ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold'
                                    : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>ODP</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 font-bold">
                                {{ nearbyOdpsList.length }}
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="resultActiveTab = 'odc'"
                            :class="[
                                'px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer',
                                resultActiveTab === 'odc'
                                    ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs font-bold'
                                    : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <span>ODC</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 font-bold">
                                {{ nearbyOdcsList.length }}
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="resultActiveTab = 'customer'"
                            :class="[
                                'px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer',
                                resultActiveTab === 'customer'
                                    ? 'bg-white dark:bg-slate-700 text-purple-600 dark:text-purple-400 shadow-2xs font-bold'
                                    : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                            <span>Pelanggan</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 font-bold">
                                {{ nearbyCustomersList.length }}
                            </span>
                            <span v-if="nearbyCustomersUnpaidCount > 0" class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-extrabold animate-pulse" title="Pelanggan Belum Lunas">
                                {{ nearbyCustomersUnpaidCount }} Blm Lunas
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="resultActiveTab = 'all'"
                            :class="[
                                'px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer',
                                resultActiveTab === 'all'
                                    ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-2xs font-bold'
                                    : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            <span>Semua</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-slate-200 font-bold">
                                {{ totalNearbyCount }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- SPOTLIGHT BANNER: TERDEKAT #1 UNTUK KATEGORI AKTIF -->
                <div v-if="filteredActiveTabItems.length > 0" class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-5 text-white shadow-lg border border-blue-700/60 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/25 text-blue-200 border border-blue-400/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>
                                    {{ filteredActiveTabItems[0]._type === 'odp' ? 'ODP Terdekat Ditemukan (#1)' :
                                       filteredActiveTabItems[0]._type === 'odc' ? 'ODC Terdekat Ditemukan (#1)' :
                                       ['customer', 'reseller', 'booking'].includes(filteredActiveTabItems[0]._type) ? 'Pelanggan Terdekat Ditemukan (#1)' : 'Posisi Terdekat (#1)' }}
                                </span>
                            </div>
                            <h3 class="text-xl font-extrabold tracking-tight flex items-center gap-2 flex-wrap">
                                <span>{{ filteredActiveTabItems[0].name }}</span>
                                <span v-if="filteredActiveTabItems[0]._type === 'odp'" class="text-xs px-2.5 py-0.5 rounded-md font-bold bg-emerald-500/25 text-emerald-300 border border-emerald-500/40">
                                    {{ filteredActiveTabItems[0].available_ports }} Port Kosong
                                </span>
                                <span v-else-if="filteredActiveTabItems[0]._type === 'odc'" class="text-xs px-2.5 py-0.5 rounded-md font-bold bg-blue-500/25 text-blue-300 border border-blue-500/40">
                                    {{ filteredActiveTabItems[0].odps_count || 0 }} ODP Terhubung
                                </span>
                                <template v-else-if="['customer', 'reseller', 'booking'].includes(filteredActiveTabItems[0]._type)">
                                    <span
                                        :class="[
                                            'text-xs px-2.5 py-0.5 rounded-md font-bold border',
                                            filteredActiveTabItems[0].is_unpaid
                                                ? 'bg-rose-500/30 text-rose-200 border-rose-400/50'
                                                : 'bg-emerald-500/25 text-emerald-300 border-emerald-500/40'
                                        ]"
                                    >
                                        {{ filteredActiveTabItems[0].is_unpaid ? `⚠️ Belum Lunas: Rp ${formatRupiah(filteredActiveTabItems[0].total_unpaid)} (${filteredActiveTabItems[0].unpaid_invoices_count || 1} Inv)` : '✅ Tagihan Lunas' }}
                                    </span>
                                    <span class="text-xs px-2.5 py-0.5 rounded-md font-bold bg-purple-500/25 text-purple-300 border border-purple-500/40">
                                        {{ filteredActiveTabItems[0].service_status === 'gratis' ? 'Gratis' : filteredActiveTabItems[0].service_status === 'free_home' ? 'Freehome' : (filteredActiveTabItems[0].status || 'Aktif') }}
                                    </span>
                                </template>
                            </h3>
                            <div class="text-xs text-blue-200 flex flex-wrap items-center gap-x-4 gap-y-1 font-medium">
                                <span>📍 Jarak: <strong class="text-white font-black">{{ formatDistance(filteredActiveTabItems[0].distance_meters) }}</strong></span>
                                <span v-if="filteredActiveTabItems[0]._type === 'odp'">🏢 ODC: <strong class="text-white">{{ filteredActiveTabItems[0].odc_name || '-' }}</strong></span>
                                <span v-if="filteredActiveTabItems[0]._type === 'odp'">🔌 Terpakai: <strong class="text-white">{{ filteredActiveTabItems[0].used_ports || 0 }} / {{ filteredActiveTabItems[0].total_ports || 0 }}</strong></span>
                                <span v-if="['customer', 'reseller', 'booking'].includes(filteredActiveTabItems[0]._type)">🔌 ODP: <strong class="text-white">{{ filteredActiveTabItems[0].odp_name || '-' }}</strong></span>
                                <span v-if="['customer', 'reseller', 'booking'].includes(filteredActiveTabItems[0]._type) && filteredActiveTabItems[0].package_name && filteredActiveTabItems[0].package_name !== '-'">📦 Paket: <strong class="text-white">{{ filteredActiveTabItems[0].package_name }}</strong> {{ filteredActiveTabItems[0].package_price ? '(Rp ' + formatRupiah(filteredActiveTabItems[0].package_price) + ')' : '' }}</span>
                                <span v-if="['customer', 'reseller', 'booking'].includes(filteredActiveTabItems[0]._type) && filteredActiveTabItems[0].due_date">📅 JT: <strong class="text-white">{{ formatDateIndo(filteredActiveTabItems[0].due_date) }}</strong></span>
                                <span>🌐 Cabang: <strong class="text-white">{{ filteredActiveTabItems[0].area_name || '-' }}</strong></span>
                                <span>🏠 Alamat: <span class="text-slate-300">{{ filteredActiveTabItems[0].address || '-' }}</span></span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a
                                v-if="['customer', 'reseller', 'booking'].includes(filteredActiveTabItems[0]._type) && filteredActiveTabItems[0].phone"
                                :href="getWhatsAppUrl(filteredActiveTabItems[0].phone, filteredActiveTabItems[0].name, filteredActiveTabItems[0].total_unpaid, filteredActiveTabItems[0].due_date)"
                                target="_blank"
                                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm"
                                title="Chat penagihan WhatsApp"
                            >
                                <span>💬 Chat WA</span>
                            </a>
                            <button
                                type="button"
                                @click="focusToItem(filteredActiveTabItems[0])"
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
                                @click="drawRoadRoute(filteredActiveTabItems[0].latitude, filteredActiveTabItems[0].longitude, filteredActiveTabItems[0].name)"
                                :disabled="isCalculatingRoute"
                                class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm cursor-pointer"
                            >
                                <svg v-if="isCalculatingRoute" class="w-3.5 h-3.5 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span>{{ isCalculatingRoute ? 'Menghitung...' : '🚗 Rute Jalan' }}</span>
                            </button>
                            <a
                                :href="getGoogleMapsUrl(filteredActiveTabItems[0].latitude, filteredActiveTabItems[0].longitude)"
                                target="_blank"
                                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm"
                            >
                                <span>🗺️ Google Maps</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- DAFTAR ITEM TERDEKAT (GRID LIST) -->
                <div v-if="filteredActiveTabItems.length > 0" class="pt-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-800 dark:text-slate-200">
                                Daftar {{ resultActiveTab === 'odp' ? 'ODP' : resultActiveTab === 'odc' ? 'ODC' : resultActiveTab === 'customer' ? 'Pelanggan' : 'Semua Titik' }} Terdekat
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                                {{ filteredActiveTabItems.length }} Ditemukan
                            </span>
                        </div>

                        <!-- In-List Search Filter -->
                        <div class="relative w-full sm:w-64">
                            <input
                                type="text"
                                v-model="resultsSearchQuery"
                                placeholder="Cari nama, kode, alamat..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                            />
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" stroke-width="2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" />
                            </svg>
                            <button
                                v-if="resultsSearchQuery"
                                @click="resultsSearchQuery = ''"
                                class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 text-xs font-bold cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Items Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        <div
                            v-for="(item, idx) in displayedNearbyItems"
                            :key="`${item._type}-${item.id}`"
                            class="p-3.5 rounded-xl border border-gray-200/90 dark:border-slate-800 bg-gray-50/40 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 hover:shadow-sm hover:border-blue-300 dark:hover:border-blue-700 transition-all flex flex-col justify-between"
                        >
                            <!-- Card Header: Rank, Type Badge & Name, Distance -->
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-1.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">
                                            #{{ idx + 1 }}
                                        </span>
                                        <span
                                            :class="[
                                                'px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider',
                                                item._type === 'odp' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300' :
                                                item._type === 'odc' ? 'bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300' :
                                                'bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300'
                                            ]"
                                        >
                                            {{ item._type === 'customer' ? 'Pelanggan' : item._type === 'reseller' ? 'Reseller' : item._type === 'booking' ? 'Booking' : item._type.toUpperCase() }}
                                        </span>
                                        <h4 class="font-extrabold text-gray-900 dark:text-white text-xs truncate" :title="item.name">
                                            {{ item.name }}
                                        </h4>
                                    </div>
                                    <span class="shrink-0 text-xs font-black text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-lg border border-blue-200/60 dark:border-blue-800/40">
                                        📍 {{ formatDistance(item.distance_meters) }}
                                    </span>
                                </div>

                                <!-- Detail Khusus ODP: Port Status & Availability -->
                                <div v-if="item._type === 'odp'" class="space-y-1.5 mb-2.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-gray-500 dark:text-slate-400 font-medium">
                                            Port: <strong class="text-gray-700 dark:text-slate-200">{{ item.used_ports }}/{{ item.total_ports }}</strong>
                                        </span>
                                        <span
                                            :class="[
                                                'font-bold px-1.5 py-0.2 rounded text-[10px]',
                                                item.available_ports > 0
                                                    ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-red-100 dark:bg-red-950/80 text-red-700 dark:text-red-300'
                                            ]"
                                        >
                                            {{ item.available_ports > 0 ? `${item.available_ports} Port Kosong` : 'Port Penuh' }}
                                        </span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="item.available_ports > 0 ? 'bg-emerald-500' : 'bg-red-500'"
                                            :style="{ width: `${item.total_ports ? Math.round((item.used_ports / item.total_ports) * 100) : 0}%` }"
                                        ></div>
                                    </div>
                                    <div class="text-[10px] text-gray-500 dark:text-slate-400 flex flex-wrap gap-x-2">
                                        <span>🏢 ODC: <strong class="text-gray-700 dark:text-slate-300">{{ item.odc_name || '-' }}</strong></span>
                                        <span>🌐 Cabang: <strong class="text-gray-700 dark:text-slate-300">{{ item.area_name || '-' }}</strong></span>
                                    </div>
                                </div>

                                <!-- Detail Khusus ODC -->
                                <div v-else-if="item._type === 'odc'" class="space-y-1 mb-2.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold">
                                            🔌 {{ item.odps_count || 0 }} ODP Terhubung
                                        </span>
                                        <span class="text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase">
                                            {{ item.status || 'Active' }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 dark:text-slate-400">
                                        <span>🌐 Cabang: <strong class="text-gray-700 dark:text-slate-300">{{ item.area_name || '-' }}</strong></span>
                                    </div>
                                </div>

                                <!-- Detail Khusus Pelanggan -->
                                <div v-else-if="['customer', 'reseller', 'booking'].includes(item._type)" class="space-y-1 mb-2.5">
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="font-mono text-gray-500 dark:text-slate-400 text-[10px]">
                                            {{ item.customer_code || '-' }}
                                        </span>
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 uppercase">
                                            {{ item.service_status === 'gratis' ? 'Gratis' : item.service_status === 'free_home' ? 'Freehome' : item.status || 'Aktif' }}
                                        </span>
                                    </div>

                                    <!-- Status Tagihan: Belum Lunas vs Lunas -->
                                    <div v-if="item.is_unpaid" class="p-2 mb-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/70 border border-rose-200 dark:border-rose-900/60 text-xs">
                                        <div class="flex items-center justify-between font-bold text-rose-700 dark:text-rose-300">
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block animate-pulse"></span>
                                                Belum Lunas ({{ item.unpaid_invoices_count || 1 }} Inv)
                                            </span>
                                            <span class="font-extrabold text-sm">Rp {{ formatRupiah(item.total_unpaid) }}</span>
                                        </div>
                                        <div v-if="item.due_date" class="flex items-center justify-between text-[10px] text-rose-600 dark:text-rose-400 mt-1">
                                            <span>Jatuh Tempo: <strong>{{ formatDateIndo(item.due_date) }}</strong></span>
                                            <span v-if="item.is_overdue" class="px-1 py-0.2 rounded bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200 font-extrabold text-[9px]">OVERDUE</span>
                                        </div>
                                        <div v-if="item.promise_date" class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5 font-medium">
                                            🤝 Janji Bayar: <strong>{{ formatDateIndo(item.promise_date) }}</strong>
                                        </div>
                                    </div>
                                    <div v-else class="px-2 py-1 mb-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-900/40 flex items-center justify-between text-[10px] text-emerald-700 dark:text-emerald-300 font-semibold">
                                        <span>Status Tagihan:</span>
                                        <span class="font-bold">✅ Lunas</span>
                                    </div>

                                    <!-- Info Paket Internet -->
                                    <div v-if="item.package_name && item.package_name !== '-'" class="text-[10px] text-gray-500 dark:text-slate-400 mb-1 flex items-center justify-between">
                                        <span>📦 Paket: <strong class="text-gray-700 dark:text-slate-200">{{ item.package_name }}</strong></span>
                                        <span v-if="item.package_price" class="font-semibold text-gray-600 dark:text-slate-300">Rp {{ formatRupiah(item.package_price) }}</span>
                                    </div>

                                    <div class="text-[10px] text-gray-500 dark:text-slate-400 flex flex-wrap gap-x-2">
                                        <span>🔌 ODP: <strong class="text-gray-700 dark:text-slate-300">{{ item.odp_name || '-' }}</strong></span>
                                        <span>🌐 Cabang: <strong class="text-gray-700 dark:text-slate-300">{{ item.area_name || '-' }}</strong></span>
                                    </div>
                                </div>

                                <!-- Alamat -->
                                <p class="text-[11px] text-gray-500 dark:text-slate-400 line-clamp-1 mb-3" :title="item.address">
                                    📍 {{ item.address || 'Tanpa Alamat' }}
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="pt-2 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                                <button
                                    type="button"
                                    @click="focusToItem(item)"
                                    class="px-2.5 py-1.5 bg-gray-100 dark:bg-slate-700/80 hover:bg-blue-50 dark:hover:bg-blue-950/60 text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg text-[11px] font-bold flex items-center gap-1 transition-colors cursor-pointer"
                                    title="Fokuskan peta pada titik ini"
                                >
                                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="3" stroke-width="2"/>
                                    </svg>
                                    <span>Fokus</span>
                                </button>
                                <button
                                    type="button"
                                    @click="drawRoadRoute(item.latitude, item.longitude, item.name)"
                                    class="px-2.5 py-1.5 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 rounded-lg text-[11px] font-bold flex items-center gap-1 transition-colors cursor-pointer"
                                    title="Tampilkan rute jalan dari lokasi Anda"
                                >
                                    <span>🚗 Rute</span>
                                </button>
                                <a
                                    :href="getGoogleMapsUrl(item.latitude, item.longitude)"
                                    target="_blank"
                                    class="px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-lg text-[11px] font-bold flex items-center gap-1 transition-colors"
                                    title="Buka rute navigasi di Google Maps"
                                >
                                    <span>🗺️ Maps</span>
                                </a>
                                <a
                                    v-if="['customer', 'reseller', 'booking'].includes(item._type) && item.phone"
                                    :href="getWhatsAppUrl(item.phone, item.name, item.total_unpaid, item.due_date)"
                                    target="_blank"
                                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-bold flex items-center gap-1 transition-colors shadow-2xs"
                                    title="Kirim pesan penagihan via WhatsApp"
                                >
                                    <span>💬 WA</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tampilkan Lebih Banyak Pagination Button -->
                    <div v-if="filteredActiveTabItems.length > displayLimit" class="text-center pt-3">
                        <button
                            type="button"
                            @click="displayLimit += 12"
                            class="px-4 py-2 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-800 dark:text-slate-200 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                        >
                            Tampilkan Lebih Banyak (Sisa {{ filteredActiveTabItems.length - displayLimit }} titik)
                        </button>
                    </div>
                </div>

                <!-- Empty State jika kategori aktif kosong -->
                <div v-else class="bg-amber-50 dark:bg-amber-950/40 rounded-2xl p-4 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-200">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-xs">
                            <strong class="font-bold">
                                Tidak ada {{ resultActiveTab === 'odp' ? 'ODP' : resultActiveTab === 'odc' ? 'ODC' : resultActiveTab === 'customer' ? 'Pelanggan' : 'data' }} dalam radius {{ formatDistance(searchRadiusMeters) }}.
                            </strong>
                            <span class="text-amber-700 dark:text-amber-300 ml-1">Silakan perbesar nilai radius pencarian atau sesuaikan filter Cabang.</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-3 pl-8">
                        <button
                            type="button"
                            @click="searchRadiusMeters = 1000; handleSearchNearby()"
                            class="px-3 py-1 bg-amber-200 dark:bg-amber-900/60 hover:bg-amber-300 text-amber-900 dark:text-amber-200 text-xs font-bold rounded-lg transition-colors cursor-pointer"
                        >
                            Perbesar ke 1.000 Meter (1 KM)
                        </button>
                        <button
                            type="button"
                            @click="searchRadiusMeters = 5000; handleSearchNearby()"
                            class="px-3 py-1 bg-amber-200 dark:bg-amber-900/60 hover:bg-amber-300 text-amber-900 dark:text-amber-200 text-xs font-bold rounded-lg transition-colors cursor-pointer"
                        >
                            Perbesar ke 5.000 Meter (5 KM)
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══ CARD 2: FILTER DATA ═══ -->
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-bold text-gray-700 dark:text-slate-300 mb-3.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Filter Data</span>
                    </div>

                    <button
                        v-if="filterCabang || filterOdc || filterOdp || filterStatusPelanggan"
                        type="button"
                        @click="resetAllFilters"
                        class="text-xs text-red-600 dark:text-red-400 hover:text-red-700 font-bold flex items-center gap-1 cursor-pointer transition-colors"
                    >
                        <span>✕</span>
                        <span>Reset Filter</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- Area / Wilayah -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 dark:text-slate-400 mb-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            <span>Area / Wilayah</span>
                        </label>
                        <select
                            v-model="filterCabang"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Area / Wilayah</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }} {{ area.customer_count !== undefined ? `(${area.customer_count} Pelanggan • ${area.odp_count} ODP)` : '' }}
                            </option>
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
                            <option value="belum_lunas">⚠️ Belum Lunas / Menunggak</option>
                            <option value="lunas">✅ Lunas (Tanpa Tunggakan)</option>
                            <option value="jatuh_tempo">⏰ Sudah Lewat Jatuh Tempo</option>
                            <option value="janji_bayar">🤝 Ada Janji Bayar</option>
                            <option value="active">Aktif</option>
                            <option value="suspended">Isolir</option>
                            <option value="booking">Booking</option>
                            <option value="gratis">Gratis</option>
                            <option value="free_home">Freehome</option>
                            <option value="terminated">Stop Permanen</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ═══ STAT CARDS (4 COLS) ═══ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
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
                    <div class="text-[11px] font-medium text-gray-500 dark:text-slate-400 mt-0.5">Pelanggan Terdeteksi</div>
                </div>

                <!-- Tunggakan Belum Lunas Card -->
                <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-rose-200 dark:border-rose-900/60 p-4 shadow-xs bg-gradient-to-br from-rose-50/40 via-white to-white dark:from-rose-950/20 dark:via-slate-900 dark:to-slate-900">
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600 inline-block animate-pulse"></span>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-600 dark:text-rose-400">Tunggakan</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
                            {{ stats.pelanggan_belum_lunas }} Pelanggan
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                        Rp {{ formatRupiah(stats.total_nominal_belum_lunas) }}
                    </div>
                    <div class="text-[11px] font-medium text-gray-500 dark:text-slate-400 mt-0.5">Belum Lunas di Radius</div>
                </div>
            </div>

            <!-- ═══ CARD 3: PETA RADAR ═══ -->
            <div class="bg-white dark:bg-slate-900/90 rounded-2xl border border-gray-200/80 dark:border-slate-800 p-5 shadow-xs">
                <!-- Header with Point Badge & Quick Map Selector -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-700 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-slate-200">
                            {{ activeTab === 'karyawan' ? 'Peta Aktivitas Karyawan' : 'Peta Radar' }}
                        </h3>
                        <span class="px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 rounded-full">
                            {{ totalVisiblePoints }} titik
                        </span>
                    </div>

                    <!-- Quick Map Switcher Pills in Header -->
                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800/90 p-1 rounded-xl">
                        <span class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 px-1.5 hidden md:inline">Peta:</span>
                        <button
                            v-for="b in quickBasemaps"
                            :key="b.id"
                            type="button"
                            @click="changeBasemap(b.id)"
                            :class="[
                                'px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1 cursor-pointer',
                                activeBasemap === b.id
                                    ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold ring-1 ring-black/5 dark:ring-white/10'
                                    : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                            :title="b.description"
                        >
                            <span>{{ b.icon }}</span>
                            <span>{{ b.shortLabel }}</span>
                        </button>
                    </div>
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
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-pulse"></span>
                            <span class="font-bold text-rose-600 dark:text-rose-400">Belum Lunas</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                            <span>Lewat JT</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
                            <span>Janji Bayar</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-600"></span>
                            <span>Lunas</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <span>Isolir</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
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

                <!-- Leaflet Map Container with Floating HUD Legend & Map Type Switcher -->
                <div class="w-full h-[640px] rounded-xl overflow-hidden border border-gray-200 dark:border-slate-800 relative bg-gray-50 dark:bg-slate-950">
                    <div ref="mapEl" class="w-full h-full z-0" id="radar-map"></div>

                    <!-- ═══ FLOATING ON-MAP BASEMAP SWITCHER (GOOGLE MAPS STYLE) ═══ -->
                    <div class="basemap-menu-container absolute top-3 left-14 sm:top-4 sm:left-14 z-[400] transition-all duration-200">
                        <div class="relative">
                            <button
                                type="button"
                                @click="isMapTypeMenuOpen = !isMapTypeMenuOpen"
                                class="px-3 py-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-xl border border-gray-200 dark:border-slate-700/80 rounded-xl text-xs font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2 hover:bg-white dark:hover:bg-slate-800 transition-all hover:scale-105 cursor-pointer"
                                title="Pilih Model Tampilan Maps"
                            >
                                <span class="text-sm">{{ currentBasemap.icon }}</span>
                                <span class="hidden sm:inline">{{ currentBasemap.label }}</span>
                                <span class="sm:hidden">{{ currentBasemap.shortLabel }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': isMapTypeMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="isMapTypeMenuOpen"
                                class="absolute left-0 mt-2 w-64 sm:w-72 bg-white/98 dark:bg-slate-900/98 backdrop-blur-md shadow-2xl border border-gray-200 dark:border-slate-700/80 rounded-2xl p-2.5 z-50 select-none animate-in fade-in zoom-in-95 duration-150"
                            >
                                <div class="text-[10px] uppercase font-extrabold tracking-wider text-gray-400 dark:text-slate-400 px-2 py-1 mb-1 flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-1.5">
                                    <span>PILIH TAMPILAN MAPS</span>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">6 Pilihan</span>
                                </div>
                                <div class="space-y-1 mt-1.5 max-h-[380px] overflow-y-auto">
                                    <button
                                        v-for="b in basemaps"
                                        :key="b.id"
                                        type="button"
                                        @click="changeBasemap(b.id); isMapTypeMenuOpen = false"
                                        :class="[
                                            'w-full flex items-center gap-2.5 p-2 rounded-xl text-left transition-all cursor-pointer group',
                                            activeBasemap === b.id
                                                ? 'bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'
                                                : 'hover:bg-gray-100 dark:hover:bg-slate-800/80 text-gray-700 dark:text-slate-200 border border-transparent'
                                        ]"
                                    >
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-base shadow-xs flex-shrink-0 bg-gradient-to-br" :class="b.previewClass || 'from-gray-100 to-gray-200'">
                                            {{ b.icon }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold truncate">{{ b.label }}</span>
                                                <span v-if="b.badge" class="px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                                    {{ b.badge }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-gray-500 dark:text-slate-400 truncate mt-0.5 leading-tight">
                                                {{ b.description }}
                                            </p>
                                        </div>
                                        <div v-if="activeBasemap === b.id" class="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center flex-shrink-0">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

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

                                <!-- 2. Status Pelanggan & Tagihan -->
                                <div class="pt-2 border-t border-gray-100 dark:border-slate-800">
                                    <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-slate-400 tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Status Tagihan & Pelanggan</span>
                                        <span class="text-[10px] text-purple-600 dark:text-purple-400 font-bold">{{ stats.pelanggan }} total</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 pl-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600 shrink-0 animate-pulse"></span>
                                            <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400">Belum Lunas</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-red-600 shrink-0"></span>
                                            <span class="text-[11px] text-red-600 dark:text-red-400">Lewat JT</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600 shrink-0"></span>
                                            <span class="text-[11px] text-amber-600 dark:text-amber-400">Janji Bayar</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-green-600 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Lunas / Aktif</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500 shrink-0"></span>
                                            <span class="text-[11px] text-gray-700 dark:text-gray-300">Isolir</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 shrink-0"></span>
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
                                            <span class="w-3 hand-3 rounded-full bg-pink-500 border border-white shadow-xs inline-block shrink-0"></span>
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

                    <!-- ═══ FLOATING ON-MAP ROUTE INFO HUD ═══ -->
                    <div
                        v-if="activeRoute"
                        class="absolute bottom-4 left-4 right-4 sm:right-auto sm:w-84 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-2xl border border-blue-200 dark:border-blue-800/80 rounded-2xl p-3.5 z-[450] animate-in fade-in slide-in-from-bottom-2 select-none"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 text-xs font-extrabold text-gray-900 dark:text-white">
                                    <span class="text-sm">🚗</span>
                                    <span class="truncate">Rute ke: {{ activeRoute.targetName }}</span>
                                </div>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <span class="text-lg font-black text-blue-600 dark:text-blue-400 tracking-tight">{{ activeRoute.formatted_distance }}</span>
                                    <span class="text-xs font-bold text-gray-600 dark:text-slate-300">• {{ activeRoute.formatted_duration }}</span>
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="activeRoute.source === 'osrm' ? 'bg-blue-500' : 'bg-purple-500'"></span>
                                    <span class="truncate">{{ activeRoute.source === 'osrm' ? 'Mengikuti kontur jalan raya' : 'Jalur langsung desa / gang' }}</span>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="clearRoute"
                                class="p-1 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                title="Tutup & Hapus Rute"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-slate-800 flex items-center gap-2">
                            <a
                                :href="getGoogleMapsUrl(activeRoute.targetLat, activeRoute.targetLng)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex-1 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-xs"
                            >
                                <span>🗺️ Buka di Google Maps</span>
                            </a>
                            <button
                                type="button"
                                @click="clearRoute"
                                class="px-2.5 py-1.5 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                            >
                                ✕ Tutup
                            </button>
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
