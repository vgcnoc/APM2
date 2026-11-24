<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ServerIcon, 
    ArrowsRightLeftIcon, 
    ShareIcon, 
    WifiIcon, 
    UserIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    XMarkIcon,
    MapPinIcon,
    PlusIcon,
    MinusIcon,
    ArrowPathIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    GlobeAltIcon,
    PhoneIcon,
    EyeIcon,
    AdjustmentsHorizontalIcon,
    SparklesIcon,
    SignalIcon,
    InformationCircleIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    olts: {
        type: Array,
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => null
    }
});

const isMobile = ref(false);
const checkMobile = () => {
    isMobile.value = window.innerWidth < 768;
};

// Canvas Pan & Zoom
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(40);
const isDragging = ref(false);
const startX = ref(0);
const startY = ref(0);
const isFullscreen = ref(false);
const showStats = ref(true);
const showLegend = ref(false);
const hierarchyLevel = ref('all'); // 'all', '1', '2', '3', '4'
const activeFilter = ref('all'); // 'all', 'split', 'high_usage', 'offline'

const handleWheel = (e) => {
    if (isMobile.value) return;
    e.preventDefault();
    const zoomAmount = e.deltaY > 0 ? 0.9 : 1.1;
    scale.value = Math.max(0.2, Math.min(scale.value * zoomAmount, 3));
};

const handleMouseDown = (e) => {
    if (isMobile.value) return;
    if (e.target.closest('.node-card') || e.target.closest('button') || e.target.closest('input') || e.target.closest('select')) return;
    
    isDragging.value = true;
    startX.value = e.clientX - translateX.value;
    startY.value = e.clientY - translateY.value;
};

const handleMouseMove = (e) => {
    if (!isDragging.value || isMobile.value) return;
    translateX.value = e.clientX - startX.value;
    translateY.value = e.clientY - startY.value;
};

const handleMouseUp = () => {
    isDragging.value = false;
};

const centerMap = () => {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 40;
};

const zoomIn = () => {
    scale.value = Math.min(scale.value * 1.2, 3);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value * 0.8, 0.2);
};

const resetZoom = () => {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 40;
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch(err => {
            console.warn('Fullscreen error:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
        });
    }
};

const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

// Selection and Detail Drawer
const selectedNode = ref(null);
const selectedPath = ref([]);
const isDrawerOpen = ref(false);
const activeDrawerTab = ref('spec'); // 'spec', 'customer', 'path'

const selectNode = (type, data, path) => {
    selectedNode.value = { type, data };
    selectedPath.value = path;
    activeDrawerTab.value = (type === 'port' && data.ont?.customer) ? 'customer' : 'spec';
    isDrawerOpen.value = true;
};

const closeDrawer = () => {
    isDrawerOpen.value = false;
    setTimeout(() => {
        selectedNode.value = null;
        selectedPath.value = [];
    }, 250);
};

// Hierarchy expansion state
const expandedNodes = ref(new Set());
const toggleExpand = (id) => {
    if (expandedNodes.value.has(id)) {
        expandedNodes.value.delete(id);
    } else {
        expandedNodes.value.add(id);
    }
};

const isExpanded = (id) => expandedNodes.value.has(id);

const expandAll = () => {
    props.olts.forEach(olt => {
        expandedNodes.value.add(`olt-${olt.id}`);
        olt.pons?.forEach(pon => {
            expandedNodes.value.add(`pon-${pon.id}`);
            pon.odcs?.forEach(odc => {
                expandedNodes.value.add(`odc-${odc.id}`);
                odc.odps?.forEach(odp => {
                    expandedNodes.value.add(`odp-${odp.id}`);
                });
            });
        });
    });
};

const collapseAll = () => {
    expandedNodes.value.clear();
};

const applyHierarchyLevel = (level) => {
    hierarchyLevel.value = level;
    expandedNodes.value.clear();
    
    if (level === 'all' || level >= 1) {
        props.olts.forEach(olt => {
            expandedNodes.value.add(`olt-${olt.id}`);
            if (level === 'all' || level >= 2) {
                olt.pons?.forEach(pon => {
                    expandedNodes.value.add(`pon-${pon.id}`);
                    if (level === 'all' || level >= 3) {
                        pon.odcs?.forEach(odc => {
                            expandedNodes.value.add(`odc-${odc.id}`);
                            if (level === 'all' || level >= 4) {
                                odc.odps?.forEach(odp => {
                                    expandedNodes.value.add(`odp-${odp.id}`);
                                });
                            }
                        });
                    }
                });
            }
        });
    }
};

// Area Filter with Inertia Router
const selectedAreaId = ref(props.filters.area_id || '');
const handleAreaChange = (e) => {
    const areaId = e.target.value;
    selectedAreaId.value = areaId;
    router.get(
        '/network-topology',
        { area_id: areaId || undefined },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['olts', 'filters', 'stats']
        }
    );
};

// Smart Live Search Engine
const searchQuery = ref('');
const isSearchFocused = ref(false);

const searchResults = computed(() => {
    const q = (searchQuery.value || '').toLowerCase().trim();
    if (!q || q.length < 2) return [];

    const results = [];

    for (const olt of props.olts) {
        if (
            olt.name?.toLowerCase().includes(q) ||
            olt.ip_address?.toLowerCase().includes(q) ||
            olt.model?.toLowerCase().includes(q) ||
            olt.brand?.toLowerCase().includes(q)
        ) {
            results.push({
                type: 'olt',
                id: olt.id,
                title: olt.name,
                badge: 'OLT',
                badgeColor: 'bg-indigo-100 text-indigo-700 border-indigo-200',
                subtitle: `OLT ${olt.brand || ''} ${olt.model || ''} - IP: ${olt.ip_address || '-'}`,
                path: [{ type: 'olt', id: olt.id }],
                data: olt
            });
        }

        for (const pon of olt.pons || []) {
            if (
                pon.name?.toLowerCase().includes(q) ||
                `pon ${pon.port_number}`.includes(q)
            ) {
                results.push({
                    type: 'pon',
                    id: pon.id,
                    title: pon.name || `PON Port ${pon.port_number}`,
                    badge: 'PON',
                    badgeColor: 'bg-purple-100 text-purple-700 border-purple-200',
                    subtitle: `OLT: ${olt.name} • Port ${pon.port_number}`,
                    path: [{ type: 'olt', id: olt.id }, { type: 'pon', id: pon.id }],
                    data: pon
                });
            }

            for (const odc of pon.odcs || []) {
                if (
                    odc.name?.toLowerCase().includes(q) ||
                    odc.location?.toLowerCase().includes(q) ||
                    odc.splitter_ratio?.toLowerCase().includes(q)
                ) {
                    results.push({
                        type: 'odc',
                        id: odc.id,
                        title: odc.name,
                        badge: odc.parent_odc_id ? 'ODC Split Lanjutan' : (odc.children?.length ? 'ODC Split Induk' : 'ODC'),
                        badgeColor: odc.parent_odc_id ? 'bg-purple-100 text-purple-700 border-purple-200' : 'bg-amber-100 text-amber-700 border-amber-200',
                        subtitle: `ODC Ratio ${odc.splitter_ratio || '1:4'} • PON: ${pon.name || pon.port_number}`,
                        path: [{ type: 'olt', id: olt.id }, { type: 'pon', id: pon.id }, { type: 'odc', id: odc.id }],
                        data: odc
                    });
                }

                for (const odp of odc.odps || []) {
                    if (
                        odp.name?.toLowerCase().includes(q) ||
                        odp.address?.toLowerCase().includes(q) ||
                        odp.description?.toLowerCase().includes(q)
                    ) {
                        results.push({
                            type: 'odp',
                            id: odp.id,
                            title: odp.name,
                            badge: odp.parent_odp_id ? 'ODP Split 2' : 'ODP',
                            badgeColor: 'bg-sky-100 text-sky-700 border-sky-200',
                            subtitle: `ODP di ODC: ${odc.name} • ${odp.used_ports}/${odp.total_ports} Port`,
                            path: [{ type: 'olt', id: olt.id }, { type: 'pon', id: pon.id }, { type: 'odc', id: odc.id }, { type: 'odp', id: odp.id }],
                            data: odp
                        });
                    }

                    for (const port of odp.ports || []) {
                        const ont = port.ont;
                        const customer = ont?.customer;

                        if (
                            customer?.name?.toLowerCase().includes(q) ||
                            customer?.customer_code?.toLowerCase().includes(q) ||
                            customer?.phone?.includes(q) ||
                            ont?.serial_number?.toLowerCase().includes(q) ||
                            ont?.wifi_ssid?.toLowerCase().includes(q)
                        ) {
                            results.push({
                                type: 'port',
                                id: port.id,
                                title: customer?.name || `Port ${port.port_number}`,
                                badge: 'Pelanggan',
                                badgeColor: 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                subtitle: `${customer?.customer_code || '-'} • ODP: ${odp.name} P${port.port_number} (SN: ${ont?.serial_number || '-'})`,
                                path: [
                                    { type: 'olt', id: olt.id },
                                    { type: 'pon', id: pon.id },
                                    { type: 'odc', id: odc.id },
                                    { type: 'odp', id: odp.id },
                                    { type: 'port', id: port.id }
                                ],
                                data: port
                            });
                        }
                    }
                }
            }
        }
        if (results.length >= 25) break;
    }

    return results.slice(0, 25);
});

const handleSelectSearchResult = (result) => {
    // 1. Expand all ancestors
    result.path.forEach(step => {
        expandedNodes.value.add(`${step.type}-${step.id}`);
    });

    // 2. Select node and open drawer
    selectNode(result.type, result.data, result.path);

    // 3. Clear search query to close dropdown
    searchQuery.value = '';
    isSearchFocused.value = false;

    // 4. Smooth focus on canvas if element exists
    nextTick(() => {
        const el = document.getElementById(`node-${result.type}-${result.id}`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            el.classList.add('ring-4', 'ring-indigo-500', 'shadow-2xl', 'animate-pulse');
            setTimeout(() => {
                el.classList.remove('ring-4', 'ring-indigo-500', 'shadow-2xl', 'animate-pulse');
            }, 3000);
        }
    });
};

// Helper functions for statuses
const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
        case 'active': case 'available': case 'online': return 'bg-emerald-500';
        case 'inactive': case 'los': case 'damaged': case 'fault': case 'stop': case 'offline': return 'bg-red-500';
        case 'maintenance': case 'reserved': case 'warning': return 'bg-amber-500';
        case 'full': case 'used': return 'bg-slate-700';
        default: return 'bg-slate-400';
    }
};

const getStatusBadgeClass = (status) => {
    switch (status?.toLowerCase()) {
        case 'active': case 'available': case 'online': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'inactive': case 'los': case 'damaged': case 'fault': case 'stop': case 'offline': return 'bg-red-50 text-red-700 border-red-200';
        case 'maintenance': case 'reserved': case 'warning': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'full': case 'used': return 'bg-slate-100 text-slate-700 border-slate-300';
        default: return 'bg-slate-50 text-slate-600 border-slate-200';
    }
};

const getStatusText = (status) => {
    return status ? status.toUpperCase() : 'UNKNOWN';
};

const isNodeInPath = (type, id) => {
    return selectedPath.value.some(p => p.type === type && p.id === id);
};

// Node Filter Matching
const matchesFilter = (type, data) => {
    if (activeFilter.value === 'all') return true;
    if (activeFilter.value === 'split') {
        if (type === 'odc') return !!(data.parent_odc_id || (data.children && data.children.length > 0) || data.is_split || data.type === 'Split');
        if (type === 'odp') return !!(data.parent_odp_id || (data.children && data.children.length > 0) || data.is_split || data.type === 'Split 2');
        return true;
    }
    if (activeFilter.value === 'high_usage') {
        if (type === 'pon') return ((data.used_capacity || 0) / (data.capacity || 128)) >= 0.7;
        if (type === 'odp') return (data.total_ports > 0 && (data.used_ports / data.total_ports) >= 0.7);
        return true;
    }
    if (activeFilter.value === 'offline') {
        return data.status?.toLowerCase() === 'inactive' || data.status?.toLowerCase() === 'los' || data.status?.toLowerCase() === 'damaged';
    }
    return true;
};

// Optical signal assessment
const getSignalBadge = (rx) => {
    if (!rx) return null;
    const num = parseFloat(rx);
    if (isNaN(num)) return null;
    if (num >= -24 && num <= -14) {
        return { label: 'Optimal', color: 'bg-emerald-100 text-emerald-800 border-emerald-300' };
    } else if (num >= -27 && num < -24) {
        return { label: 'Waspada', color: 'bg-amber-100 text-amber-800 border-amber-300' };
    } else {
        return { label: 'Kritis / Redam Tinggi', color: 'bg-red-100 text-red-800 border-red-300' };
    }
};

// WhatsApp redirect
const getWaLink = (phone, customerName) => {
    if (!phone) return '#';
    let clean = phone.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) clean = '62' + clean.slice(1);
    const text = encodeURIComponent(`Halo ${customerName || 'Pelanggan'}, kami dari tim NOC/Teknisi menginformasikan perihal jaringan fiber Anda.`);
    return `https://wa.me/${clean}?text=${text}`;
};

// Google Maps link
const getMapLink = (lat, lng) => {
    if (!lat || !lng) return null;
    return `https://www.google.com/maps?q=${lat},${lng}`;
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
    document.addEventListener('fullscreenchange', onFullscreenChange);
    
    // Expand all OLTS and PONs by default
    props.olts.forEach(olt => {
        expandedNodes.value.add(`olt-${olt.id}`);
        olt.pons?.forEach(pon => {
            expandedNodes.value.add(`pon-${pon.id}`);
        });
    });
    
    centerMap();
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
});
</script>

<template>
    <Head title="Network Topology & Optical Architecture" />
    <AppLayout>
        <div class="h-[calc(100vh-4rem)] flex flex-col relative overflow-hidden bg-slate-100 font-sans">
            
            <!-- TOP BAR: Control Center & Live Search -->
            <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 py-2.5 z-30 shrink-0 shadow-sm flex flex-wrap items-center justify-between gap-3">
                <!-- Left: Title, Badge, & Stats Toggle -->
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white shadow-md shadow-indigo-100">
                        <ShareIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-extrabold text-slate-800 tracking-tight">Network Topology</h1>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60 rounded-full shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                                FTTH Realtime
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 hidden sm:block">Pemetaan visual hierarki OLT &rarr; PON &rarr; ODC &rarr; ODP &rarr; Pelanggan</p>
                    </div>

                    <!-- Quick Stats Toggle Button -->
                    <button 
                        @click="showStats = !showStats" 
                        class="ml-2 px-2.5 py-1 text-xs font-semibold rounded-lg border transition-all flex items-center gap-1.5 shadow-xs"
                        :class="showStats ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                        title="Buka / Tutup Ringkasan Telemetri"
                    >
                        <ChartBarIcon class="w-3.5 h-3.5" />
                        <span class="hidden md:inline">{{ showStats ? 'Tutup KPI' : 'Ringkasan KPI' }}</span>
                        <ChevronUpIcon v-if="showStats" class="w-3 h-3" />
                        <ChevronDownIcon v-else class="w-3 h-3" />
                    </button>
                </div>

                <!-- Middle: Instant Search Bar -->
                <div class="flex-1 max-w-md relative min-w-[240px]">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari OLT, PON, ODC, ODP, atau Pelanggan (Nama/SN/ID)..." 
                            @focus="isSearchFocused = true"
                            class="w-full pl-9 pr-8 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                        />
                        <button 
                            v-if="searchQuery" 
                            @click="searchQuery = ''; isSearchFocused = false" 
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600 rounded-full"
                        >
                            <XMarkIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div 
                        v-if="searchQuery && searchQuery.length >= 2" 
                        class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-80 overflow-y-auto z-50 divide-y divide-slate-100 p-1"
                    >
                        <div v-if="searchResults.length === 0" class="p-4 text-center text-xs text-slate-500">
                            Tidak ditemukan elemen atau pelanggan dengan kata kunci "<span class="font-bold">{{ searchQuery }}</span>"
                        </div>
                        <div 
                            v-for="(item, idx) in searchResults" 
                            :key="idx" 
                            @click="handleSelectSearchResult(item)"
                            class="p-2.5 hover:bg-indigo-50/70 rounded-xl cursor-pointer transition-colors flex items-center justify-between gap-2"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="p-1.5 rounded-lg shrink-0" :class="item.type === 'olt' ? 'bg-indigo-100 text-indigo-700' : item.type === 'pon' ? 'bg-purple-100 text-purple-700' : item.type === 'odc' ? 'bg-amber-100 text-amber-700' : item.type === 'odp' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700'">
                                    <ServerIcon v-if="item.type === 'olt'" class="w-4 h-4" />
                                    <ArrowsRightLeftIcon v-else-if="item.type === 'pon'" class="w-4 h-4" />
                                    <ShareIcon v-else-if="item.type === 'odc'" class="w-4 h-4" />
                                    <ShareIcon v-else-if="item.type === 'odp'" class="w-4 h-4 rotate-90" />
                                    <UserIcon v-else class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-800 truncate">{{ item.title }}</div>
                                    <div class="text-[10px] text-slate-500 truncate">{{ item.subtitle }}</div>
                                </div>
                            </div>
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-md border shrink-0" :class="item.badgeColor">
                                {{ item.badge }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Area Select, Level Filter & Fullscreen -->
                <div class="flex items-center gap-2">
                    <!-- Area Selector -->
                    <div class="relative">
                        <select 
                            :value="selectedAreaId" 
                            @change="handleAreaChange"
                            class="text-xs font-semibold bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 pl-3 pr-8 shadow-2xs text-slate-700 cursor-pointer"
                        >
                            <option value="">🌐 Semua Area Jaringan</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">📍 {{ area.name }}</option>
                        </select>
                    </div>

                    <!-- Panduan / Legend Button -->
                    <button 
                        @click="showLegend = !showLegend" 
                        class="p-2 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-xl border border-slate-200 transition-colors shadow-2xs" 
                        title="Panduan Simbol & Kode Warna"
                    >
                        <InformationCircleIcon class="w-4 h-4" />
                    </button>

                    <!-- Fullscreen Toggle -->
                    <button 
                        @click="toggleFullscreen" 
                        class="p-2 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-xl border border-slate-200 transition-colors shadow-2xs hidden md:block" 
                        :title="isFullscreen ? 'Keluar Layar Penuh' : 'Mode Layar Penuh (NOC View)'"
                    >
                        <ArrowsPointingInIcon v-if="isFullscreen" class="w-4 h-4" />
                        <ArrowsPointingOutIcon v-else class="w-4 h-4" />
                    </button>
                </div>
            </header>

            <!-- KPI & TELEMETRY STRIP (COLLAPSIBLE) -->
            <transition 
                enter-active-class="transition duration-200 ease-out" 
                enter-from-class="transform -translate-y-4 opacity-0" 
                enter-to-class="transform translate-y-0 opacity-100" 
                leave-active-class="transition duration-150 ease-in" 
                leave-from-class="transform translate-y-0 opacity-100" 
                leave-to-class="transform -translate-y-4 opacity-0"
            >
                <div v-if="showStats && stats" class="bg-white/90 backdrop-blur-sm border-b border-slate-200/80 px-4 py-2.5 z-20 shrink-0 shadow-xs">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <!-- OLT Card -->
                        <div class="bg-indigo-50/50 border border-indigo-100/80 rounded-xl p-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">OLT Perangkat</div>
                                <div class="text-base font-extrabold text-slate-800">{{ stats.total_olts }} <span class="text-xs font-semibold text-emerald-600">({{ stats.active_olts }} Aktif)</span></div>
                            </div>
                            <div class="p-2 rounded-lg bg-indigo-100 text-indigo-700">
                                <ServerIcon class="w-4 h-4" />
                            </div>
                        </div>

                        <!-- PON Card -->
                        <div class="bg-purple-50/50 border border-purple-100/80 rounded-xl p-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-purple-600 uppercase tracking-wider">Port PON</div>
                                <div class="text-base font-extrabold text-slate-800">{{ stats.total_pons }} <span class="text-xs font-normal text-slate-500">Port</span></div>
                            </div>
                            <div class="p-2 rounded-lg bg-purple-100 text-purple-700">
                                <ArrowsRightLeftIcon class="w-4 h-4" />
                            </div>
                        </div>

                        <!-- ODC Card -->
                        <div class="bg-amber-50/50 border border-amber-100/80 rounded-xl p-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">ODC Distribusi</div>
                                <div class="text-base font-extrabold text-slate-800">
                                    {{ stats.total_odcs }}
                                    <span v-if="stats.split_induk_odcs || stats.split_lanjutan_odcs" class="text-[10px] font-bold text-purple-700 block">
                                        {{ stats.split_induk_odcs }} Induk &bull; {{ stats.split_lanjutan_odcs }} Lanjutan
                                    </span>
                                </div>
                            </div>
                            <div class="p-2 rounded-lg bg-amber-100 text-amber-700">
                                <ShareIcon class="w-4 h-4" />
                            </div>
                        </div>

                        <!-- ODP Card -->
                        <div class="bg-sky-50/50 border border-sky-100/80 rounded-xl p-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-sky-600 uppercase tracking-wider">ODP Akses / Split 2</div>
                                <div class="text-base font-extrabold text-slate-800">
                                    {{ stats.total_odps }}
                                    <span v-if="stats.split_induk_odps || stats.split_lanjutan_odps" class="text-[10px] font-bold text-sky-700 block">
                                        {{ stats.split_induk_odps }} Induk &bull; {{ stats.split_lanjutan_odps }} Lanjutan
                                    </span>
                                </div>
                            </div>
                            <div class="p-2 rounded-lg bg-sky-100 text-sky-700">
                                <ShareIcon class="w-4 h-4 rotate-90" />
                            </div>
                        </div>

                        <!-- Port Utilization -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Utilisasi Port ODP</span>
                                <span class="text-xs font-bold" :class="stats.utilization_rate >= 85 ? 'text-red-600' : stats.utilization_rate >= 70 ? 'text-amber-600' : 'text-emerald-600'">
                                    {{ stats.utilization_rate }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden mb-1">
                                <div 
                                    class="h-1.5 rounded-full transition-all duration-500" 
                                    :class="stats.utilization_rate >= 85 ? 'bg-red-500' : stats.utilization_rate >= 70 ? 'bg-amber-500' : 'bg-emerald-500'" 
                                    :style="{ width: `${Math.min(100, stats.utilization_rate)}%` }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-[9px] text-slate-500 font-medium">
                                <span>{{ stats.used_ports }} Terpakai</span>
                                <span>{{ stats.available_ports }} Sisa</span>
                            </div>
                        </div>

                        <!-- Pelanggan Terhubung -->
                        <div class="bg-emerald-50/50 border border-emerald-100/80 rounded-xl p-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Pelanggan Aktif</div>
                                <div class="text-base font-extrabold text-slate-800">{{ stats.connected_customers }} <span class="text-xs font-normal text-slate-500">Klien</span></div>
                            </div>
                            <div class="p-2 rounded-lg bg-emerald-100 text-emerald-700">
                                <UserIcon class="w-4 h-4" />
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- FLOATING HUD TOOLBAR -->
            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 bg-white/95 backdrop-blur-md border border-slate-300/80 px-3 py-1.5 rounded-2xl shadow-xl">
                <!-- Zoom Out -->
                <button @click="zoomOut" class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-xl transition-colors" title="Zoom Out (-)">
                    <MinusIcon class="w-4 h-4" />
                </button>
                <!-- Scale Percentage Badge / Reset -->
                <button @click="resetZoom" class="px-2 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-lg transition-colors" title="Klik untuk Reset Zoom (100%)">
                    {{ Math.round(scale * 100) }}%
                </button>
                <!-- Zoom In -->
                <button @click="zoomIn" class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-xl transition-colors" title="Zoom In (+)">
                    <PlusIcon class="w-4 h-4" />
                </button>

                <div class="w-[1px] h-5 bg-slate-200 mx-1"></div>

                <!-- Center / Fit Map -->
                <button @click="centerMap" class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-xl transition-colors" title="Tengahkan Kanvas">
                    <ArrowPathIcon class="w-4 h-4" />
                </button>

                <div class="w-[1px] h-5 bg-slate-200 mx-1"></div>

                <!-- Level Selector -->
                <div class="flex items-center gap-1 text-[11px] font-bold text-slate-600">
                    <span class="text-slate-400 mr-1 hidden sm:inline">Hirarki:</span>
                    <button 
                        @click="expandAll" 
                        class="px-2 py-1 rounded-lg border text-xs transition-all"
                        :class="expandedNodes.size > 0 ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'"
                        title="Buka seluruh tingkat cabang"
                    >
                        Buka Semua
                    </button>
                    <button 
                        @click="collapseAll" 
                        class="px-2 py-1 rounded-lg border text-xs bg-slate-50 border-slate-200 hover:bg-slate-100 transition-all"
                        title="Tutup seluruh cabang"
                    >
                        Tutup Semua
                    </button>
                </div>

                <div class="w-[1px] h-5 bg-slate-200 mx-1"></div>

                <!-- Filter Mode Dropdown -->
                <select 
                    v-model="activeFilter" 
                    class="text-xs bg-transparent border-0 font-bold text-slate-700 focus:ring-0 cursor-pointer pr-6 py-1"
                    title="Filter Tampilan Cabang"
                >
                    <option value="all">🔍 Semua Node</option>
                    <option value="split">⚡ Tipe Split Saja</option>
                    <option value="high_usage">⚠️ Utilisasi Tinggi (&ge;70%)</option>
                    <option value="offline">🔴 Node Bermasalah</option>
                </select>
            </div>

            <!-- LEGEND MODAL / POPUP -->
            <transition 
                enter-active-class="transition duration-150 ease-out" 
                enter-from-class="transform scale-95 opacity-0" 
                enter-to-class="transform scale-100 opacity-100" 
                leave-active-class="transition duration-100 ease-in" 
                leave-from-class="transform scale-100 opacity-100" 
                leave-to-class="transform scale-95 opacity-0"
            >
                <div v-if="showLegend" class="absolute top-16 right-4 z-40 bg-white/95 backdrop-blur-md border border-slate-200 rounded-2xl shadow-2xl p-4 w-72 text-xs">
                    <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-100">
                        <span class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-indigo-600" />
                            Panduan Topologi
                        </span>
                        <button @click="showLegend = false" class="p-1 rounded-md text-slate-400 hover:bg-slate-100">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-3.5 h-3.5 rounded-md bg-indigo-600"></div>
                            <span class="font-medium text-slate-700">OLT (Optical Line Terminal)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3.5 h-3.5 rounded-md bg-purple-600"></div>
                            <span class="font-medium text-slate-700">PON Port (Interface Transceiver)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3.5 h-3.5 rounded-md bg-amber-500"></div>
                            <span class="font-medium text-slate-700">ODC (Optical Distribution Cabinet)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3.5 h-3.5 rounded-md bg-sky-500"></div>
                            <span class="font-medium text-slate-700">ODP (Optical Distribution Point)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3.5 h-3.5 rounded-md bg-emerald-500"></div>
                            <span class="font-medium text-slate-700">Port Pelanggan / ONT Aktif</span>
                        </div>

                        <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-500 space-y-1">
                            <div><strong class="text-emerald-700">🟢 SPLIT INDUK:</strong> ODC/ODP yang mencabangkan optical splitter primer.</div>
                            <div><strong class="text-purple-700">🟣 SPLIT LANJUTAN:</strong> ODC/ODP cascade lanjutan dari unit induk.</div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- MAIN TOPOLOGY CANVAS -->
            <div 
                class="flex-1 relative overflow-auto md:overflow-hidden select-none cursor-grab active:cursor-grabbing"
                :style="{
                    backgroundColor: '#f8fafc',
                    backgroundImage: 'radial-gradient(#cbd5e1 1.2px, transparent 1.2px)',
                    backgroundSize: '24px 24px'
                }"
                @wheel="handleWheel"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="handleMouseUp"
                @mouseleave="handleMouseUp"
            >
                <div 
                    class="p-6 md:p-12 transition-transform duration-75 ease-out origin-top min-w-max flex justify-center"
                    :style="!isMobile ? { transform: `translate(${translateX}px, ${translateY}px) scale(${scale})` } : {}"
                >
                    <div :class="[isMobile ? 'flex flex-col gap-8' : 'flex flex-row justify-center gap-12 min-w-max']">
                        <!-- OLT Level -->
                        <div 
                            v-for="olt in olts" 
                            :key="olt.id" 
                            :id="`node-olt-${olt.id}`"
                            class="flex flex-col items-center"
                            :class="{ 'opacity-30': !matchesFilter('olt', olt) }"
                        >
                            <!-- OLT Card -->
                            <div 
                                class="node-card bg-white border-2 rounded-2xl shadow-md p-4 w-72 cursor-pointer hover:shadow-xl transition-all relative group"
                                :class="[
                                    isNodeInPath('olt', olt.id) ? 'border-indigo-600 ring-4 ring-indigo-100' : 'border-slate-200/90 hover:border-indigo-400'
                                ]"
                                @click="selectNode('olt', olt, [{type: 'olt', id: olt.id}])"
                            >
                                <!-- Top Accent Bar -->
                                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-600 to-blue-500 rounded-t-xl"></div>
                                
                                <div class="flex items-start justify-between gap-3 mb-2.5 pt-1">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl border border-indigo-100 shrink-0">
                                            <ServerIcon class="w-6 h-6" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">OLT</span>
                                                <span v-if="olt.brand" class="text-[9px] font-bold text-slate-500">{{ olt.brand }}</span>
                                            </div>
                                            <h3 class="font-extrabold text-slate-900 text-sm truncate" :title="olt.name">{{ olt.name }}</h3>
                                        </div>
                                    </div>
                                    <span class="flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border shrink-0" :class="getStatusBadgeClass(olt.status)">
                                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="getStatusColor(olt.status)"></span>
                                        {{ getStatusText(olt.status) }}
                                    </span>
                                </div>

                                <!-- OLT Metadata -->
                                <div class="bg-slate-50 rounded-xl p-2.5 border border-slate-100 mb-3 space-y-1 text-xs">
                                    <div class="flex justify-between text-slate-600">
                                        <span class="text-slate-400 font-medium">IP Address:</span>
                                        <span class="font-mono font-bold text-slate-800">{{ olt.ip_address || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between text-slate-600">
                                        <span class="text-slate-400 font-medium">Wilayah Area:</span>
                                        <span class="font-medium text-slate-700 truncate max-w-[140px]">{{ olt.area?.name || 'Semua Area' }}</span>
                                    </div>
                                    <div class="flex justify-between text-slate-600">
                                        <span class="text-slate-400 font-medium">Kapasitas PON:</span>
                                        <span class="font-bold text-indigo-600">{{ olt.pons?.length || 0 }} Port Terpasang</span>
                                    </div>
                                </div>

                                <!-- Card Actions -->
                                <div class="flex items-center gap-2">
                                    <button 
                                        v-if="olt.pons?.length"
                                        @click.stop="toggleExpand(`olt-${olt.id}`)" 
                                        class="flex-1 py-1.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 border"
                                        :class="isExpanded(`olt-${olt.id}`) ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                                    >
                                        <span>{{ isExpanded(`olt-${olt.id}`) ? 'Sembunyikan PON' : `Lihat ${olt.pons.length} PON` }}</span>
                                        <ChevronUpIcon v-if="isExpanded(`olt-${olt.id}`)" class="w-3.5 h-3.5" />
                                        <ChevronDownIcon v-else class="w-3.5 h-3.5" />
                                    </button>
                                    <div v-else class="flex-1 text-center py-1.5 text-[11px] text-slate-400 italic bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                        0 PON Port
                                    </div>

                                    <Link 
                                        :href="`/olts/${olt.id}`" 
                                        class="p-1.5 rounded-xl border border-slate-200 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                        title="Buka Data Detail OLT"
                                    >
                                        <EyeIcon class="w-4 h-4" />
                                    </Link>
                                </div>
                            </div>

                            <!-- PON Level (Branch Lines & Nodes) -->
                            <div 
                                v-show="isExpanded(`olt-${olt.id}`)" 
                                class="flex relative mt-8" 
                                :class="[isMobile ? 'flex-col pl-6 mt-3 border-l-2 border-slate-300 ml-6' : 'flex-row justify-center']"
                            >
                                <!-- Horizontal & Vertical Bus Connectors -->
                                <div v-if="!isMobile && olt.pons?.length > 1" class="absolute top-[-32px] left-1/2 w-[calc(100%-14rem)] h-[2px] bg-indigo-300 -translate-x-1/2"></div>
                                <div v-if="!isMobile && olt.pons?.length" class="absolute top-[-32px] left-1/2 w-[2px] h-[32px] bg-indigo-300 -translate-x-1/2"></div>

                                <div 
                                    v-for="pon in olt.pons" 
                                    :key="pon.id" 
                                    :id="`node-pon-${pon.id}`"
                                    class="flex flex-col items-center" 
                                    :class="[isMobile ? 'mb-4' : 'mx-4', { 'opacity-30': !matchesFilter('pon', pon) }]"
                                >
                                    <div v-if="!isMobile" class="w-[2px] h-[32px] bg-indigo-300"></div>

                                    <!-- PON Node Card -->
                                    <div 
                                        class="node-card bg-white border-2 rounded-2xl shadow-sm p-3.5 w-60 cursor-pointer hover:shadow-md transition-all relative group"
                                        :class="[isNodeInPath('pon', pon.id) ? 'border-purple-600 ring-4 ring-purple-100' : 'border-slate-200 hover:border-purple-400']"
                                        @click="selectNode('pon', pon, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}])"
                                    >
                                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-600 to-indigo-500 rounded-t-xl"></div>

                                        <div class="flex items-center gap-2 mb-2 pt-0.5">
                                            <div class="p-1.5 bg-purple-50 text-purple-700 rounded-lg border border-purple-100 shrink-0">
                                                <ArrowsRightLeftIcon class="w-4 h-4" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-[9px] font-black uppercase text-purple-600 tracking-wider">PORT {{ pon.port_number }}</div>
                                                <h4 class="font-bold text-slate-900 text-xs truncate">{{ pon.name || `PON ${pon.port_number}` }}</h4>
                                            </div>
                                        </div>

                                        <!-- Capacity Bar -->
                                        <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 mb-2">
                                            <div class="flex justify-between text-[10px] text-slate-600 mb-1 font-semibold">
                                                <span>{{ pon.used_capacity || 0 }} Terpakai</span>
                                                <span class="text-purple-600 font-bold">{{ pon.capacity || 128 }} Max</span>
                                            </div>
                                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div 
                                                    class="bg-purple-600 h-1.5 rounded-full transition-all" 
                                                    :style="{ width: `${Math.min(100, (((pon.used_capacity || 0) / (pon.capacity || 128)) * 100))}%` }"
                                                ></div>
                                            </div>
                                        </div>

                                        <!-- PON Action -->
                                        <button 
                                            v-if="pon.odcs?.length"
                                            @click.stop="toggleExpand(`pon-${pon.id}`)" 
                                            class="w-full py-1 px-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1 border"
                                            :class="isExpanded(`pon-${pon.id}`) ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                                        >
                                            <span>{{ isExpanded(`pon-${pon.id}`) ? 'Sembunyikan ODC' : `Lihat ${pon.odcs.length} ODC` }}</span>
                                            <ChevronUpIcon v-if="isExpanded(`pon-${pon.id}`)" class="w-3 h-3" />
                                            <ChevronDownIcon v-else class="w-3 h-3" />
                                        </button>
                                        <div v-else class="text-center py-1 text-[10px] text-slate-400 italic bg-slate-50 rounded-lg border border-dashed border-slate-200">
                                            0 ODC Terhubung
                                        </div>
                                    </div>

                                    <!-- ODC Level -->
                                    <div 
                                        v-show="isExpanded(`pon-${pon.id}`)" 
                                        class="flex relative mt-8" 
                                        :class="[isMobile ? 'flex-col pl-6 mt-3 border-l-2 border-slate-300 ml-6' : 'flex-row justify-center']"
                                    >
                                        <div v-if="!isMobile && pon.odcs?.length > 1" class="absolute top-[-32px] left-1/2 w-[calc(100%-12rem)] h-[2px] bg-purple-300 -translate-x-1/2"></div>
                                        <div v-if="!isMobile && pon.odcs?.length" class="absolute top-[-32px] left-1/2 w-[2px] h-[32px] bg-purple-300 -translate-x-1/2"></div>

                                        <div 
                                            v-for="(odc, odcIdx) in pon.odcs" 
                                            :key="odc.id" 
                                            :id="`node-odc-${odc.id}`"
                                            class="flex flex-col items-center relative" 
                                            :class="[isMobile ? 'mb-4' : 'mx-3', { 'opacity-30': !matchesFilter('odc', odc) }]"
                                        >
                                            <div v-if="!isMobile" class="w-[2px] h-[32px] bg-purple-300"></div>

                                            <!-- ODC Split Connector Badge -->
                                            <div 
                                                v-if="!isMobile && odc.parent_odc_id && odcIdx > 0 && pon.odcs[odcIdx - 1]?.id === odc.parent_odc_id"
                                                class="absolute -left-5 top-12 z-20 px-2 py-0.5 rounded-full bg-gradient-to-r from-emerald-600 to-purple-600 text-white text-[9px] font-black shadow-md border border-white"
                                                title="Relasi Split ODC (Induk ⇄ Lanjutan)"
                                            >
                                                ⇄ Split
                                            </div>

                                            <!-- ODC Node Card -->
                                            <div 
                                                class="node-card bg-white border-2 rounded-2xl shadow-sm p-3.5 w-56 cursor-pointer hover:shadow-md transition-all relative group"
                                                :class="[
                                                    isNodeInPath('odc', odc.id) 
                                                        ? 'border-amber-500 ring-4 ring-amber-100' 
                                                        : odc.parent_odc_id 
                                                            ? 'border-purple-300 ring-1 ring-purple-100 hover:border-purple-400' 
                                                            : (odc.children && odc.children.length > 0) 
                                                                ? 'border-emerald-400 ring-1 ring-emerald-100 hover:border-emerald-500' 
                                                                : 'border-slate-200 hover:border-amber-400'
                                                ]"
                                                @click="selectNode('odc', odc, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}, {type: 'odc', id: odc.id}])"
                                            >
                                                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500 rounded-t-xl"></div>

                                                <div class="flex items-center gap-2 mb-2 pt-0.5">
                                                    <div class="p-1.5 bg-amber-50 text-amber-700 rounded-lg border border-amber-100 shrink-0">
                                                        <ShareIcon class="w-4 h-4" />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1">
                                                            <span class="text-[9px] font-black uppercase text-amber-700">ODC</span>
                                                            <span class="text-[9px] font-bold text-slate-500">Ratio {{ odc.splitter_ratio || '1:4' }}</span>
                                                        </div>
                                                        <h4 class="font-extrabold text-slate-900 text-xs truncate" :title="odc.name">{{ odc.name }}</h4>
                                                    </div>
                                                </div>

                                                <!-- Split Status Pill -->
                                                <div v-if="odc.children && odc.children.length > 0" class="mb-2 px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-[9px] font-extrabold text-emerald-800 truncate" :title="'Lanjutan: ' + odc.children.map(c => c.name).join(', ')">
                                                    🟢 SPLIT INDUK &rarr; {{ odc.children.map(c => c.name).join(', ') }}
                                                </div>
                                                <div v-else-if="odc.parent || odc.parent_odc_id" class="mb-2 px-2 py-0.5 rounded-md bg-purple-50 border border-purple-200 text-[9px] font-extrabold text-purple-800 truncate" :title="'Induk: ' + (odc.parent?.name || odc.parent_odc_id)">
                                                    🟣 SPLIT LANJUTAN &larr; {{ odc.parent?.name || ('#' + odc.parent_odc_id) }}
                                                </div>

                                                <!-- ODC Actions -->
                                                <div class="flex items-center gap-1.5 mt-2">
                                                    <button 
                                                        v-if="odc.odps?.length"
                                                        @click.stop="toggleExpand(`odc-${odc.id}`)" 
                                                        class="flex-1 py-1 px-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1 border"
                                                        :class="isExpanded(`odc-${odc.id}`) ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                                                    >
                                                        <span>{{ isExpanded(`odc-${odc.id}`) ? 'Sembunyikan ODP' : `Lihat ${odc.odps.length} ODP` }}</span>
                                                        <ChevronUpIcon v-if="isExpanded(`odc-${odc.id}`)" class="w-3 h-3" />
                                                        <ChevronDownIcon v-else class="w-3 h-3" />
                                                    </button>
                                                    <div v-else class="flex-1 text-center py-1 text-[10px] text-slate-400 italic bg-slate-50 rounded-lg border border-dashed border-slate-200">
                                                        0 ODP
                                                    </div>

                                                    <Link 
                                                        :href="`/odcs/${odc.id}`" 
                                                        class="p-1 rounded-lg border border-slate-200 text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                                                        title="Detail ODC"
                                                    >
                                                        <EyeIcon class="w-3.5 h-3.5" />
                                                    </Link>
                                                </div>
                                            </div>

                                            <!-- ODP Level -->
                                            <div 
                                                v-show="isExpanded(`odc-${odc.id}`)" 
                                                class="flex relative mt-8" 
                                                :class="[isMobile ? 'flex-col pl-6 mt-3 border-l-2 border-slate-300 ml-6' : 'flex-row justify-center']"
                                            >
                                                <div v-if="!isMobile && odc.odps?.length > 1" class="absolute top-[-32px] left-1/2 w-[calc(100%-10rem)] h-[2px] bg-amber-300 -translate-x-1/2"></div>
                                                <div v-if="!isMobile && odc.odps?.length" class="absolute top-[-32px] left-1/2 w-[2px] h-[32px] bg-amber-300 -translate-x-1/2"></div>

                                                <div 
                                                    v-for="(odp, odpIdx) in odc.odps" 
                                                    :key="odp.id" 
                                                    :id="`node-odp-${odp.id}`"
                                                    class="flex flex-col items-center relative" 
                                                    :class="[isMobile ? 'mb-4' : 'mx-2.5', { 'opacity-30': !matchesFilter('odp', odp) }]"
                                                >
                                                    <div v-if="!isMobile" class="w-[2px] h-[32px] bg-amber-300"></div>

                                                    <!-- ODP Split 2 Badge -->
                                                    <div 
                                                        v-if="!isMobile && odp.parent_odp_id && odpIdx > 0 && odc.odps[odpIdx - 1]?.id === odp.parent_odp_id"
                                                        class="absolute -left-4 top-12 z-20 px-1.5 py-0.5 rounded-full bg-gradient-to-r from-emerald-600 to-purple-600 text-white text-[8px] font-black shadow border border-white"
                                                        title="Relasi Split 2 (Induk ⇄ Lanjutan)"
                                                    >
                                                        ⇄ Split 2
                                                    </div>

                                                    <!-- ODP Node Card -->
                                                    <div 
                                                        class="node-card bg-white border-2 rounded-2xl shadow-sm p-3 w-52 cursor-pointer hover:shadow-md transition-all relative group"
                                                        :class="[
                                                            isNodeInPath('odp', odp.id) 
                                                                ? 'border-sky-500 ring-4 ring-sky-100' 
                                                                : odp.parent_odp_id 
                                                                    ? 'border-purple-300 ring-1 ring-purple-100' 
                                                                    : (odp.children && odp.children.length > 0) 
                                                                        ? 'border-emerald-300 ring-1 ring-emerald-100' 
                                                                        : 'border-slate-200 hover:border-sky-400'
                                                        ]"
                                                        @click="selectNode('odp', odp, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}, {type: 'odc', id: odc.id}, {type: 'odp', id: odp.id}])"
                                                    >
                                                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-sky-500 to-blue-500 rounded-t-xl"></div>

                                                        <div class="flex items-center gap-2 mb-2 pt-0.5">
                                                            <div class="p-1.5 bg-sky-50 text-sky-700 rounded-lg border border-sky-100 shrink-0">
                                                                <ShareIcon class="w-3.5 h-3.5 rotate-90" />
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="text-[9px] font-black uppercase text-sky-600">ODP</div>
                                                                <h5 class="font-extrabold text-slate-900 text-xs truncate" :title="odp.name">{{ odp.name }}</h5>
                                                            </div>
                                                        </div>

                                                        <!-- Split 2 Indicator -->
                                                        <div v-if="odp.children && odp.children.length > 0" class="mb-1.5 px-1.5 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-[8px] font-black text-emerald-800 truncate" :title="'Lanjutan: ' + odp.children.map(c => c.name).join(', ')">
                                                            🟢 INDUK &rarr; {{ odp.children.map(c => c.name).join(', ') }}
                                                        </div>
                                                        <div v-else-if="odp.parent || odp.parent_odp_id" class="mb-1.5 px-1.5 py-0.5 rounded bg-purple-50 border border-purple-200 text-[8px] font-black text-purple-800 truncate" :title="'Induk: ' + (odp.parent?.name || odp.parent_odp_id)">
                                                            🟣 LANJUTAN &larr; {{ odp.parent?.name || ('#' + odp.parent_odp_id) }}
                                                        </div>

                                                        <!-- ODP Port Capacity Bar -->
                                                        <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 mb-2">
                                                            <div class="flex justify-between items-center text-[10px] font-bold text-slate-700 mb-1">
                                                                <span>{{ odp.used_ports }}/{{ odp.total_ports }} Port</span>
                                                                <span :class="(odp.total_ports > 0 && odp.used_ports >= odp.total_ports) ? 'text-red-600' : 'text-slate-500'">
                                                                    {{ odp.total_ports > 0 ? Math.round((odp.used_ports / odp.total_ports) * 100) : 0 }}%
                                                                </span>
                                                            </div>
                                                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                                <div 
                                                                    class="h-1.5 rounded-full transition-all"
                                                                    :class="(odp.total_ports > 0 && odp.used_ports >= odp.total_ports) ? 'bg-red-500' : (odp.total_ports > 0 && (odp.used_ports / odp.total_ports) >= 0.8) ? 'bg-amber-500' : 'bg-sky-500'"
                                                                    :style="{ width: `${odp.total_ports > 0 ? Math.min(100, (odp.used_ports / odp.total_ports) * 100) : 0}%` }"
                                                                ></div>
                                                            </div>
                                                        </div>

                                                        <!-- ODP Actions -->
                                                        <div class="flex items-center gap-1.5">
                                                            <button 
                                                                v-if="odp.ports?.length"
                                                                @click.stop="toggleExpand(`odp-${odp.id}`)" 
                                                                class="flex-1 py-1 px-2 rounded-lg text-[10px] font-bold transition-all flex items-center justify-center gap-1 border"
                                                                :class="isExpanded(`odp-${odp.id}`) ? 'bg-sky-50 border-sky-200 text-sky-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                                                            >
                                                                <span>{{ isExpanded(`odp-${odp.id}`) ? 'Tutup Port' : `Buka ${odp.ports.length} Port` }}</span>
                                                                <ChevronUpIcon v-if="isExpanded(`odp-${odp.id}`)" class="w-3 h-3" />
                                                                <ChevronDownIcon v-else class="w-3 h-3" />
                                                            </button>
                                                            <div v-else class="flex-1 text-center py-1 text-[9px] text-slate-400 italic bg-slate-50 rounded-lg">
                                                                0 Port
                                                            </div>

                                                            <Link 
                                                                :href="`/odps/${odp.id}`" 
                                                                class="p-1 rounded-lg border border-slate-200 text-slate-500 hover:text-sky-600 hover:bg-sky-50 transition-colors"
                                                                title="Detail ODP"
                                                            >
                                                                <EyeIcon class="w-3 h-3" />
                                                            </Link>
                                                        </div>
                                                    </div>

                                                    <!-- Port Level -->
                                                    <div 
                                                        v-show="isExpanded(`odp-${odp.id}`)" 
                                                        class="flex flex-col mt-4 gap-2 relative w-full" 
                                                        :class="[isMobile ? 'pl-6 border-l-2 border-slate-300 ml-6' : 'items-center']"
                                                    >
                                                        <div v-if="!isMobile && odp.ports?.length" class="w-[2px] h-[16px] bg-sky-300"></div>

                                                        <div 
                                                            v-for="port in odp.ports" 
                                                            :key="port.id" 
                                                            :id="`node-port-${port.id}`"
                                                            class="w-full max-w-[210px] relative"
                                                        >
                                                            <div 
                                                                class="node-card bg-white border rounded-xl shadow-xs p-2 text-xs flex items-center justify-between cursor-pointer hover:border-indigo-400 transition-all"
                                                                :class="[
                                                                    isNodeInPath('port', port.id) ? 'border-indigo-600 ring-2 ring-indigo-200 bg-indigo-50/40' : 'border-slate-200 hover:bg-slate-50'
                                                                ]"
                                                                @click.stop="selectNode('port', port, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}, {type: 'odc', id: odc.id}, {type: 'odp', id: odp.id}, {type: 'port', id: port.id}])"
                                                            >
                                                                <div class="flex items-center gap-1.5 min-w-0">
                                                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-700 flex items-center justify-center text-[10px] font-black shrink-0">
                                                                        P{{ port.port_number }}
                                                                    </span>
                                                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getStatusColor(port.status)"></span>
                                                                    
                                                                    <div v-if="port.ont?.customer" class="min-w-0">
                                                                        <div class="font-bold text-slate-800 text-[11px] truncate max-w-[100px]" :title="port.ont.customer.name">
                                                                            {{ port.ont.customer.name }}
                                                                        </div>
                                                                        <div class="text-[9px] text-slate-400 truncate">{{ port.ont.customer.customer_code }}</div>
                                                                    </div>
                                                                    <div v-else-if="port.ont" class="min-w-0">
                                                                        <div class="font-semibold text-slate-700 text-[10px] truncate max-w-[100px]">ONT: {{ port.ont.serial_number }}</div>
                                                                    </div>
                                                                    <div v-else class="text-[10px] text-slate-400 italic">
                                                                        Tersedia
                                                                    </div>
                                                                </div>

                                                                <div class="shrink-0 flex items-center gap-1">
                                                                    <WifiIcon v-if="port.ont" class="w-3.5 h-3.5 text-emerald-500" />
                                                                    <span v-if="port.ont?.rx_power" class="text-[8px] font-mono px-1 rounded bg-slate-100 text-slate-600">
                                                                        {{ port.ont.rx_power }}
                                                                    </span>
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

                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="olts.length === 0" class="flex flex-col items-center justify-center h-[55vh] text-slate-500 p-8 max-w-md mx-auto text-center">
                        <div class="p-4 bg-white rounded-3xl border border-slate-200 shadow-sm mb-4">
                            <ShareIcon class="w-12 h-12 text-slate-300" />
                        </div>
                        <h2 class="text-base font-extrabold text-slate-800 mb-1">Tidak Ada Data Topologi</h2>
                        <p class="text-xs text-slate-500 mb-4">Belum ada perangkat OLT terdaftar pada area ini atau Anda belum memiliki hak akses scope jaringan.</p>
                        <Link href="/olts/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition-colors">
                            + Daftarkan OLT Baru
                        </Link>
                    </div>
                </div>
            </div>

            <!-- DETAIL DRAWER (RIGHT PANEL) -->
            <div 
                class="fixed inset-y-0 right-0 w-96 max-w-full bg-white shadow-2xl border-l border-slate-200 transform transition-transform duration-300 ease-in-out z-50 flex flex-col"
                :class="isDrawerOpen ? 'translate-x-0' : 'translate-x-full'"
            >
                <!-- Drawer Header -->
                <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-indigo-100 text-indigo-700">
                            <ServerIcon v-if="selectedNode?.type === 'olt'" class="w-5 h-5" />
                            <ArrowsRightLeftIcon v-else-if="selectedNode?.type === 'pon'" class="w-5 h-5" />
                            <ShareIcon v-else-if="selectedNode?.type === 'odc'" class="w-5 h-5" />
                            <ShareIcon v-else-if="selectedNode?.type === 'odp'" class="w-5 h-5 rotate-90" />
                            <UserIcon v-else class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-[10px] font-black uppercase text-indigo-600 tracking-wider">Detail Perangkat</div>
                            <h3 class="font-extrabold text-slate-900 text-sm capitalize">{{ selectedNode?.type }} {{ selectedNode?.data?.name ? `• ${selectedNode.data.name}` : '' }}</h3>
                        </div>
                    </div>
                    <button @click="closeDrawer" class="p-1.5 rounded-xl hover:bg-slate-200 text-slate-500 transition-colors">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Drawer Tabs -->
                <div class="flex border-b border-slate-200 bg-white px-2 pt-2 gap-1 text-xs font-bold text-slate-600">
                    <button 
                        @click="activeDrawerTab = 'spec'"
                        class="px-3 py-2 border-b-2 transition-all"
                        :class="activeDrawerTab === 'spec' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        Spesifikasi
                    </button>
                    <button 
                        v-if="selectedNode?.type === 'port' && selectedNode?.data?.ont?.customer"
                        @click="activeDrawerTab = 'customer'"
                        class="px-3 py-2 border-b-2 transition-all flex items-center gap-1.5"
                        :class="activeDrawerTab === 'customer' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        <span>Pelanggan</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </button>
                    <button 
                        @click="activeDrawerTab = 'path'"
                        class="px-3 py-2 border-b-2 transition-all"
                        :class="activeDrawerTab === 'path' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        Jalur Optik
                    </button>
                </div>

                <!-- Drawer Body -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4" v-if="selectedNode">
                    
                    <!-- TAB 1: SPESIFIKASI TEKNIS -->
                    <div v-show="activeDrawerTab === 'spec'" class="space-y-4">
                        <!-- Identity Card -->
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 space-y-2.5 text-xs">
                            <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                                <span class="text-slate-500">Nama / Label:</span>
                                <span class="font-extrabold text-slate-900 text-sm">{{ selectedNode.data.name || `Port ${selectedNode.data.port_number}` }}</span>
                            </div>

                            <div v-if="selectedNode.data.status" class="flex justify-between items-center py-1">
                                <span class="text-slate-500">Status Operasional:</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="getStatusBadgeClass(selectedNode.data.status)">
                                    {{ getStatusText(selectedNode.data.status) }}
                                </span>
                            </div>

                            <div v-if="selectedNode.data.ip_address" class="flex justify-between items-center py-1">
                                <span class="text-slate-500">IP Address:</span>
                                <span class="font-mono font-bold text-slate-800">{{ selectedNode.data.ip_address }}</span>
                            </div>

                            <div v-if="selectedNode.data.brand || selectedNode.data.model" class="flex justify-between items-center py-1">
                                <span class="text-slate-500">Merek & Model:</span>
                                <span class="font-bold text-slate-800">{{ selectedNode.data.brand || '' }} {{ selectedNode.data.model || '' }}</span>
                            </div>

                            <div v-if="selectedNode.data.splitter_ratio" class="flex justify-between items-center py-1">
                                <span class="text-slate-500">Splitter Ratio:</span>
                                <span class="font-extrabold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">{{ selectedNode.data.splitter_ratio }}</span>
                            </div>

                            <div v-if="selectedNode.data.capacity !== undefined" class="flex justify-between items-center py-1">
                                <span class="text-slate-500">Kapasitas Max:</span>
                                <span class="font-bold text-slate-800">{{ selectedNode.data.capacity }}</span>
                            </div>

                            <div v-if="selectedNode.data.total_ports !== undefined" class="flex justify-between items-center py-1">
                                <span class="text-slate-500">Total Port:</span>
                                <span class="font-bold text-slate-800">{{ selectedNode.data.total_ports }}</span>
                            </div>

                            <div v-if="selectedNode.data.used_ports !== undefined" class="flex justify-between items-center py-1">
                                <span class="text-slate-500">Port Terpakai:</span>
                                <span class="font-bold text-indigo-600">{{ selectedNode.data.used_ports }} ({{ selectedNode.data.total_ports - selectedNode.data.used_ports }} Sisa)</span>
                            </div>

                            <div v-if="selectedNode.data.location || selectedNode.data.address" class="py-1">
                                <span class="text-slate-500 block mb-1">Lokasi / Alamat:</span>
                                <span class="font-medium text-slate-800 flex items-start gap-1">
                                    <MapPinIcon class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                                    {{ selectedNode.data.location || selectedNode.data.address }}
                                </span>
                            </div>
                        </div>

                        <!-- Split Cascade Details -->
                        <div v-if="selectedNode.data.children?.length" class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-3.5 text-xs">
                            <span class="font-bold text-emerald-900 block mb-1">🟢 Unit Induk Split</span>
                            <p class="text-emerald-700 text-[11px] mb-2">Unit ini mendistribusikan jalur ke unit lanjutan:</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="child in selectedNode.data.children" :key="child.id" class="px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                    {{ child.name }}
                                </span>
                            </div>
                        </div>

                        <div v-else-if="selectedNode.data.parent" class="bg-purple-50/70 border border-purple-200 rounded-2xl p-3.5 text-xs">
                            <span class="font-bold text-purple-900 block mb-1">🟣 Unit Lanjutan Split</span>
                            <p class="text-purple-700 text-[11px]">Bercabang dari Unit Induk: <strong class="text-purple-900">{{ selectedNode.data.parent.name }}</strong></p>
                        </div>

                        <!-- Direct Page Navigation Button -->
                        <div class="pt-2">
                            <Link v-if="selectedNode.type === 'olt'" :href="`/olts/${selectedNode.data.id}`" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold text-center block shadow-md shadow-indigo-100 transition-colors">
                                Kelola Perangkat OLT
                            </Link>
                            <Link v-if="selectedNode.type === 'odc'" :href="`/odcs/${selectedNode.data.id}`" class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold text-center block shadow-md shadow-amber-100 transition-colors">
                                Kelola ODC Distribusi
                            </Link>
                            <Link v-if="selectedNode.type === 'odp'" :href="`/odps/${selectedNode.data.id}`" class="w-full py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold text-center block shadow-md shadow-sky-100 transition-colors">
                                Kelola ODP Akses
                            </Link>
                        </div>
                    </div>

                    <!-- TAB 2: PELANGGAN & ONT INFO -->
                    <div v-show="activeDrawerTab === 'customer'" class="space-y-4">
                        <div v-if="selectedNode.data.ont?.customer" class="bg-blue-50/80 border border-blue-200 rounded-2xl p-4 space-y-3">
                            <div class="flex items-center gap-3 pb-3 border-b border-blue-200/80">
                                <div class="p-2 rounded-xl bg-blue-600 text-white">
                                    <UserIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-blue-950 text-sm">{{ selectedNode.data.ont.customer.name }}</h4>
                                    <span class="text-xs font-mono font-bold text-blue-700">{{ selectedNode.data.ont.customer.customer_code }}</span>
                                </div>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Status Langganan:</span>
                                    <span class="font-bold uppercase text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded text-[10px]">
                                        {{ selectedNode.data.ont.customer.status || 'Active' }}
                                    </span>
                                </div>
                                <div v-if="selectedNode.data.ont.customer.package" class="flex justify-between">
                                    <span class="text-slate-500">Paket Internet:</span>
                                    <span class="font-bold text-slate-800">{{ selectedNode.data.ont.customer.package.name }}</span>
                                </div>
                                <div v-if="selectedNode.data.ont.customer.phone" class="flex justify-between items-center">
                                    <span class="text-slate-500">No. WhatsApp / Telp:</span>
                                    <span class="font-bold text-slate-800">{{ selectedNode.data.ont.customer.phone }}</span>
                                </div>
                                <div v-if="selectedNode.data.ont.customer.address">
                                    <span class="text-slate-500 block mb-1">Alamat Pemasangan:</span>
                                    <span class="font-medium text-slate-800 text-[11px] block">{{ selectedNode.data.ont.customer.address }}</span>
                                </div>
                            </div>

                            <!-- ONT Telemetry -->
                            <div class="pt-3 border-t border-blue-200/80 space-y-2 text-xs">
                                <span class="font-bold text-blue-900 block text-[11px]">Perangkat ONT / Modem:</span>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Nomor Seri (SN):</span>
                                    <span class="font-mono font-bold text-slate-800">{{ selectedNode.data.ont.serial_number || '-' }}</span>
                                </div>
                                <div v-if="selectedNode.data.ont.rx_power" class="flex justify-between items-center">
                                    <span class="text-slate-500">Optical RX Power:</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-slate-800">{{ selectedNode.data.ont.rx_power }} dBm</span>
                                        <span v-if="getSignalBadge(selectedNode.data.ont.rx_power)" class="text-[9px] font-bold px-1.5 py-0.5 rounded border" :class="getSignalBadge(selectedNode.data.ont.rx_power).color">
                                            {{ getSignalBadge(selectedNode.data.ont.rx_power).label }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="selectedNode.data.ont.wifi_ssid" class="flex justify-between">
                                    <span class="text-slate-500">SSID WiFi:</span>
                                    <span class="font-medium text-slate-800">{{ selectedNode.data.ont.wifi_ssid }}</span>
                                </div>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="pt-2 flex flex-col gap-2">
                                <Link 
                                    :href="`/customers/${selectedNode.data.ont.customer.id}`" 
                                    class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold text-center shadow-md transition-colors"
                                >
                                    Buka Profil Pelanggan
                                </Link>
                                <a 
                                    v-if="selectedNode.data.ont.customer.phone" 
                                    :href="getWaLink(selectedNode.data.ont.customer.phone, selectedNode.data.ont.customer.name)" 
                                    target="_blank" 
                                    class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold text-center flex items-center justify-center gap-1.5 shadow-sm transition-colors"
                                >
                                    <PhoneIcon class="w-3.5 h-3.5" />
                                    Hubungi Pelanggan via WhatsApp
                                </a>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-xs text-slate-400">
                            Port ini tidak terhubung dengan pelanggan aktif.
                        </div>
                    </div>

                    <!-- TAB 3: NETWORK PATH TRACING -->
                    <div v-show="activeDrawerTab === 'path'" class="space-y-4">
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                            <span class="text-xs font-extrabold text-slate-800 block mb-3">Rute Distribusi Optik</span>
                            
                            <div class="flex flex-col gap-3 relative">
                                <div class="absolute left-[11px] top-3 bottom-3 w-[2px] bg-slate-300"></div>
                                
                                <div 
                                    v-for="(pathItem, i) in selectedPath" 
                                    :key="i" 
                                    class="flex items-center gap-3 relative z-10 cursor-pointer group"
                                >
                                    <div 
                                        class="w-6 h-6 rounded-full border-2 border-white shadow-md flex items-center justify-center text-[10px] font-bold text-white transition-transform group-hover:scale-110" 
                                        :class="i === selectedPath.length - 1 ? 'bg-indigo-600 ring-4 ring-indigo-100 scale-110' : 'bg-slate-400'"
                                    >
                                        {{ i + 1 }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[9px] font-black text-slate-400 uppercase">{{ pathItem.type }}</span>
                                        <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                            {{ 
                                                pathItem.type === 'olt' ? (olts.find(o => o.id === pathItem.id)?.name || 'OLT Core') :
                                                pathItem.type === 'pon' ? `PON Port ${pathItem.id}` :
                                                pathItem.type === 'odc' ? `ODC ID #${pathItem.id}` :
                                                pathItem.type === 'odp' ? `ODP ID #${pathItem.id}` :
                                                `Port #${pathItem.id}`
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Drawer Footer -->
                <div class="p-3.5 border-t border-slate-200 bg-slate-50">
                    <button 
                        @click="closeDrawer" 
                        class="w-full py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl shadow-xs transition-colors"
                    >
                        Tutup Panel Info
                    </button>
                </div>
            </div>

            <!-- Overlay for Mobile -->
            <div 
                v-if="isDrawerOpen" 
                class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs z-40 md:hidden"
                @click="closeDrawer"
            ></div>

        </div>
    </AppLayout>
</template>

<style scoped>
.node-card {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.node-card:active {
    transform: scale(0.98);
}
</style>
