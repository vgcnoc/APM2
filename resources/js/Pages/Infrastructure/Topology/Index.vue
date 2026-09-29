<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
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
    XMarkIcon,
    MapPinIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    olts: Array,
    areas: Array,
    filters: Object
});

const isMobile = ref(false);
const checkMobile = () => {
    isMobile.value = window.innerWidth < 768;
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
    // Center the topology map on mount
    centerMap();
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});

// Canvas Pan & Zoom
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const isDragging = ref(false);
const startX = ref(0);
const startY = ref(0);

const handleWheel = (e) => {
    if (isMobile.value) return;
    e.preventDefault();
    const zoomAmount = e.deltaY > 0 ? 0.9 : 1.1;
    scale.value = Math.max(0.2, Math.min(scale.value * zoomAmount, 3));
};

const handleMouseDown = (e) => {
    if (isMobile.value) return;
    // Don't drag if clicking on a node
    if (e.target.closest('.node-card')) return;
    
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
    translateY.value = 50;
};

// Selection and Detail Drawer
const selectedNode = ref(null);
const selectedPath = ref([]);
const isDrawerOpen = ref(false);

const selectNode = (type, data, path) => {
    selectedNode.value = { type, data };
    selectedPath.value = path;
    isDrawerOpen.value = true;
};

const closeDrawer = () => {
    isDrawerOpen.value = false;
    setTimeout(() => {
        selectedNode.value = null;
        selectedPath.value = [];
    }, 300);
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

// Expand all OLTS and PONs by default
onMounted(() => {
    props.olts.forEach(olt => {
        expandedNodes.value.add(`olt-${olt.id}`);
        olt.pons?.forEach(pon => {
            expandedNodes.value.add(`pon-${pon.id}`);
        });
    });
});

// Helper functions for statuses
const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
        case 'active': case 'available': return 'bg-emerald-500';
        case 'inactive': case 'los': case 'damaged': case 'fault': case 'stop': return 'bg-red-500';
        case 'maintenance': case 'reserved': return 'bg-yellow-500';
        case 'full': case 'used': return 'bg-slate-800';
        default: return 'bg-gray-400';
    }
};

const getStatusText = (status) => {
    return status ? status.toUpperCase() : 'UNKNOWN';
};

const isNodeInPath = (type, id) => {
    return selectedPath.value.some(p => p.type === type && p.id === id);
};

</script>

<template>
    <Head title="Network Topology" />
    <AppLayout>
        <div class="h-[calc(100vh-4rem)] flex flex-col relative overflow-hidden bg-slate-50">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between z-10 shrink-0 shadow-sm">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-bold text-slate-800">Network Topology</h1>
                    <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">Interactive Map</span>
                </div>
                <div class="flex items-center gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <select name="area_id" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 py-1.5" :value="filters.area_id" @change="$event.target.form.submit()">
                            <option value="">Semua Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </form>
                    <button @click="centerMap" class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg hidden md:block" title="Center Map">
                        <ArrowsPointingOutIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Canvas Area -->
            <div 
                class="flex-1 relative cursor-grab active:cursor-grabbing overflow-auto md:overflow-hidden"
                @wheel="handleWheel"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="handleMouseUp"
                @mouseleave="handleMouseUp"
            >
                <div 
                    class="p-4 md:p-0 transition-transform duration-100 ease-out origin-top"
                    :style="!isMobile ? { transform: `translate(${translateX}px, ${translateY}px) scale(${scale})` } : {}"
                >
                    <div :class="[isMobile ? 'flex flex-col gap-4' : 'flex flex-row justify-center min-w-max']">
                        <!-- OLT Level -->
                        <div v-for="olt in olts" :key="olt.id" class="flex flex-col items-center">
                            <!-- OLT Node -->
                            <div 
                                class="node-card bg-white border-2 rounded-xl shadow-md p-4 w-64 cursor-pointer hover:shadow-lg transition-all"
                                :class="[isNodeInPath('olt', olt.id) ? 'border-blue-500 ring-4 ring-blue-100' : 'border-slate-200']"
                                @click="selectNode('olt', olt, [{type: 'olt', id: olt.id}])"
                            >
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="p-2 bg-indigo-100 text-indigo-700 rounded-lg">
                                        <ServerIcon class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-500">OLT</div>
                                        <div class="font-bold text-slate-800">{{ olt.name }}</div>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-500 mb-2">{{ olt.area?.name || 'Unknown Area' }}</div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full" :class="getStatusColor(olt.status)"></span>
                                        {{ getStatusText(olt.status) }}
                                    </span>
                                    <span class="font-medium bg-slate-100 px-2 py-0.5 rounded text-slate-600">{{ olt.pons?.length || 0 }} PONs</span>
                                </div>
                                <button @click.stop="toggleExpand(`olt-${olt.id}`)" class="mt-3 w-full py-1 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-600 rounded border border-slate-200">
                                    {{ expandedNodes.has(`olt-${olt.id}`) ? 'Collapse' : 'Expand' }}
                                </button>
                            </div>

                            <!-- PON Level -->
                            <div v-show="expandedNodes.has(`olt-${olt.id}`)" class="flex relative mt-8" :class="[isMobile ? 'flex-col pl-6 mt-2 border-l-2 border-slate-300 ml-8' : 'flex-row justify-center']">
                                <div v-if="!isMobile && olt.pons?.length" class="absolute top-[-32px] left-1/2 w-[calc(100%-16rem)] h-[2px] bg-slate-300 -translate-x-1/2"></div>
                                <div v-if="!isMobile && olt.pons?.length" class="absolute top-[-32px] left-1/2 w-[2px] h-[32px] bg-slate-300 -translate-x-1/2"></div>
                                
                                <div v-for="(pon, pIdx) in olt.pons" :key="pon.id" class="flex flex-col items-center" :class="[isMobile ? 'mb-4' : 'mx-4']">
                                    <div v-if="!isMobile" class="w-[2px] h-[32px] bg-slate-300"></div>
                                    
                                    <!-- PON Node -->
                                    <div 
                                        class="node-card bg-white border-2 rounded-xl shadow-sm p-3 w-56 cursor-pointer hover:shadow-md transition-all relative"
                                        :class="[isNodeInPath('pon', pon.id) ? 'border-purple-500 ring-4 ring-purple-100' : 'border-slate-200']"
                                        @click="selectNode('pon', pon, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}])"
                                    >
                                        <div v-if="isMobile" class="absolute left-[-26px] top-1/2 w-[24px] h-[2px] bg-slate-300"></div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="p-1.5 bg-purple-100 text-purple-700 rounded-lg">
                                                <ArrowsRightLeftIcon class="w-5 h-5" />
                                            </div>
                                            <div>
                                                <div class="text-[10px] font-bold text-slate-500">PON PORT {{ pon.port_number }}</div>
                                                <div class="font-bold text-slate-800 text-sm">{{ pon.name || `PON ${pon.port_number}` }}</div>
                                            </div>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 mb-1 overflow-hidden">
                                            <div class="bg-purple-500 h-1.5 rounded-full" :style="{width: `${Math.min(100, ((pon.used_capacity || 0) / pon.capacity) * 100)}%`}"></div>
                                        </div>
                                        <div class="flex justify-between text-[10px] text-slate-500 mb-2">
                                            <span>{{ pon.used_capacity || 0 }} Used</span>
                                            <span>{{ Math.max(0, pon.capacity - (pon.used_capacity || 0)) }} Avail</span>
                                        </div>
                                        <button @click.stop="toggleExpand(`pon-${pon.id}`)" class="mt-2 w-full py-1 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-600 rounded border border-slate-200">
                                            {{ expandedNodes.has(`pon-${pon.id}`) ? 'Collapse' : 'Expand' }}
                                        </button>
                                    </div>

                                    <!-- ODC Level -->
                                    <div v-show="expandedNodes.has(`pon-${pon.id}`)" class="flex relative mt-8" :class="[isMobile ? 'flex-col pl-6 mt-2 border-l-2 border-slate-300 ml-8' : 'flex-row justify-center']">
                                        <div v-if="!isMobile && pon.odcs?.length" class="absolute top-[-32px] left-1/2 w-[calc(100%-14rem)] h-[2px] bg-slate-300 -translate-x-1/2"></div>
                                        <div v-if="!isMobile && pon.odcs?.length" class="absolute top-[-32px] left-1/2 w-[2px] h-[32px] bg-slate-300 -translate-x-1/2"></div>
                                        
                                        <div v-for="odc in pon.odcs" :key="odc.id" class="flex flex-col items-center" :class="[isMobile ? 'mb-4' : 'mx-2']">
                                            <div v-if="!isMobile" class="w-[2px] h-[32px] bg-slate-300"></div>
                                            
                                            <!-- ODC Node -->
                                            <div 
                                                class="node-card bg-white border-2 rounded-xl shadow-sm p-3 w-52 cursor-pointer hover:shadow-md transition-all relative"
                                                :class="[isNodeInPath('odc', odc.id) ? 'border-orange-500 ring-4 ring-orange-100' : 'border-slate-200']"
                                                @click="selectNode('odc', odc, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}, {type: 'odc', id: odc.id}])"
                                            >
                                                <div v-if="isMobile" class="absolute left-[-26px] top-1/2 w-[24px] h-[2px] bg-slate-300"></div>
                                                <div class="flex items-center gap-2 mb-2">
                                                    <div class="p-1.5 bg-orange-100 text-orange-700 rounded-lg">
                                                        <ShareIcon class="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <div class="text-[10px] font-bold text-slate-500">ODC ({{ odc.splitter_ratio || 'N/A' }})</div>
                                                        <div class="font-bold text-slate-800 text-sm">{{ odc.name }}</div>
                                                    </div>
                                                </div>
                                                <button @click.stop="toggleExpand(`odc-${odc.id}`)" class="mt-2 w-full py-1 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-600 rounded border border-slate-200">
                                                    {{ expandedNodes.has(`odc-${odc.id}`) ? 'Collapse' : 'Expand' }}
                                                </button>
                                            </div>

                                            <!-- ODP Level -->
                                            <div v-show="expandedNodes.has(`odc-${odc.id}`)" class="flex relative mt-8" :class="[isMobile ? 'flex-col pl-6 mt-2 border-l-2 border-slate-300 ml-8' : 'flex-row justify-center']">
                                                <div v-if="!isMobile && odc.odps?.length" class="absolute top-[-32px] left-1/2 w-[calc(100%-12rem)] h-[2px] bg-slate-300 -translate-x-1/2"></div>
                                                <div v-if="!isMobile && odc.odps?.length" class="absolute top-[-32px] left-1/2 w-[2px] h-[32px] bg-slate-300 -translate-x-1/2"></div>
                                                
                                                <div v-for="odp in odc.odps" :key="odp.id" class="flex flex-col items-center" :class="[isMobile ? 'mb-4' : 'mx-2']">
                                                    <div v-if="!isMobile" class="w-[2px] h-[32px] bg-slate-300"></div>
                                                    
                                                    <!-- ODP Node -->
                                                    <div 
                                                        class="node-card bg-white border-2 rounded-xl shadow-sm p-3 w-48 cursor-pointer hover:shadow-md transition-all relative"
                                                        :class="[isNodeInPath('odp', odp.id) ? 'border-sky-500 ring-4 ring-sky-100' : 'border-slate-200']"
                                                        @click="selectNode('odp', odp, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}, {type: 'odc', id: odc.id}, {type: 'odp', id: odp.id}])"
                                                    >
                                                        <div v-if="isMobile" class="absolute left-[-26px] top-1/2 w-[24px] h-[2px] bg-slate-300"></div>
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <div class="p-1.5 bg-sky-100 text-sky-700 rounded-lg">
                                                                <ShareIcon class="w-4 h-4 rotate-90" />
                                                            </div>
                                                            <div>
                                                                <div class="text-[10px] font-bold text-slate-500">ODP</div>
                                                                <div class="font-bold text-slate-800 text-sm truncate w-24" :title="odp.name">{{ odp.name }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="flex justify-between items-center text-[10px]">
                                                            <span class="flex items-center gap-1">
                                                                <span class="w-1.5 h-1.5 rounded-full" :class="getStatusColor(odp.status)"></span>
                                                                {{ odp.status }}
                                                            </span>
                                                            <span class="text-slate-500">{{ odp.used_ports }}/{{ odp.total_ports }}</span>
                                                        </div>
                                                        <button @click.stop="toggleExpand(`odp-${odp.id}`)" class="mt-2 w-full py-1 bg-slate-50 hover:bg-slate-100 text-[10px] font-medium text-slate-600 rounded border border-slate-200">
                                                            {{ expandedNodes.has(`odp-${odp.id}`) ? 'Hide Ports' : 'View Ports' }}
                                                        </button>
                                                    </div>

                                                    <!-- Port Level -->
                                                    <div v-show="expandedNodes.has(`odp-${odp.id}`)" class="flex flex-col mt-4 gap-2 relative w-full" :class="[isMobile ? 'pl-6 border-l-2 border-slate-300 ml-8' : 'items-center']">
                                                        <div v-if="!isMobile && odp.ports?.length" class="w-[2px] h-[16px] bg-slate-300"></div>
                                                        
                                                        <div v-for="port in odp.ports" :key="port.id" class="w-full max-w-[200px] relative">
                                                            <div v-if="isMobile" class="absolute left-[-26px] top-1/2 w-[24px] h-[2px] bg-slate-300"></div>
                                                            <div v-if="!isMobile" class="absolute top-[-16px] left-1/2 w-[2px] h-[16px] bg-slate-300 -translate-x-1/2"></div>
                                                            
                                                            <div 
                                                                class="node-card bg-white border rounded-lg shadow-sm p-2 text-xs flex items-center justify-between cursor-pointer hover:border-blue-400"
                                                                :class="[isNodeInPath('port', port.id) ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200']"
                                                                @click.stop="selectNode('port', port, [{type: 'olt', id: olt.id}, {type: 'pon', id: pon.id}, {type: 'odc', id: odc.id}, {type: 'odp', id: odp.id}, {type: 'port', id: port.id}])"
                                                            >
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-bold text-slate-600">P{{ port.port_number }}</span>
                                                                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusColor(port.status)"></span>
                                                                </div>
                                                                
                                                                <div v-if="port.ont" class="flex items-center gap-1">
                                                                    <WifiIcon class="w-3 h-3 text-emerald-500" />
                                                                    <span class="text-[10px] text-slate-600 truncate w-16" :title="port.ont.customer?.name">{{ port.ont.customer?.name || 'ONT' }}</span>
                                                                </div>
                                                                <div v-else class="text-[10px] text-slate-400 italic">Empty</div>
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
                    <div v-if="olts.length === 0" class="flex flex-col items-center justify-center h-[50vh] text-slate-500">
                        <ShareIcon class="w-16 h-16 mb-4 text-slate-300" />
                        <h2 class="text-xl font-bold text-slate-700">Tidak ada data Topologi</h2>
                        <p class="text-sm">Silakan pilih Area lain atau tambahkan data OLT terlebih dahulu.</p>
                    </div>
                </div>
            </div>

            <!-- Detail Drawer -->
            <div 
                class="fixed inset-y-0 right-0 w-80 bg-white shadow-2xl border-l border-gray-200 transform transition-transform duration-300 ease-in-out z-50 flex flex-col"
                :class="isDrawerOpen ? 'translate-x-0' : 'translate-x-full'"
            >
                <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-slate-50">
                    <h3 class="font-bold text-slate-800 capitalize">{{ selectedNode?.type }} Detail</h3>
                    <button @click="closeDrawer" class="p-1 rounded-md hover:bg-slate-200 text-slate-500">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4" v-if="selectedNode">
                    <!-- Dynamic details based on type -->
                    <div class="mb-6">
                        <div class="text-sm text-slate-500 mb-1">Name / ID</div>
                        <div class="font-bold text-lg text-slate-900">{{ selectedNode.data.name || `Port ${selectedNode.data.port_number}` || selectedNode.data.serial_number }}</div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div v-if="selectedNode.data.status" class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-xs text-slate-500 font-medium">Status</span>
                            <span class="px-2 py-0.5 rounded text-xs font-bold text-white shadow-sm" :class="getStatusColor(selectedNode.data.status)">
                                {{ getStatusText(selectedNode.data.status) }}
                            </span>
                        </div>
                        
                        <div v-if="selectedNode.data.capacity !== undefined" class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-xs text-slate-500 font-medium">Capacity</span>
                            <span class="text-sm font-bold text-slate-800">{{ selectedNode.data.capacity }}</span>
                        </div>

                        <div v-if="selectedNode.data.total_ports !== undefined" class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-xs text-slate-500 font-medium">Total Ports</span>
                            <span class="text-sm font-bold text-slate-800">{{ selectedNode.data.total_ports }}</span>
                        </div>
                        
                        <div v-if="selectedNode.data.used_ports !== undefined" class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-xs text-slate-500 font-medium">Used Ports</span>
                            <span class="text-sm font-bold text-slate-800">{{ selectedNode.data.used_ports }}</span>
                        </div>
                        
                        <div v-if="selectedNode.data.location || selectedNode.data.address" class="py-2 border-b border-slate-100">
                            <span class="text-xs text-slate-500 font-medium block mb-1">Location</span>
                            <span class="text-sm text-slate-800 flex items-start gap-1">
                                <MapPinIcon class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                                {{ selectedNode.data.location || selectedNode.data.address }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer Info (if Port has ONT) -->
                    <div v-if="selectedNode.type === 'port' && selectedNode.data.ont" class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <UserIcon class="w-5 h-5 text-blue-600" />
                            <h4 class="font-bold text-blue-900 text-sm">Customer Connected</h4>
                        </div>
                        <div class="text-sm font-bold text-slate-800 mb-1">{{ selectedNode.data.ont.customer?.name || 'Unknown' }}</div>
                        <div class="text-xs text-slate-500 mb-2">{{ selectedNode.data.ont.customer?.customer_code }}</div>
                        
                        <div class="text-xs border-t border-blue-200 mt-2 pt-2">
                            <div class="flex justify-between mb-1">
                                <span class="text-slate-500">ONT SN</span>
                                <span class="font-medium text-slate-800">{{ selectedNode.data.ont.serial_number }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Network Path -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h4 class="font-bold text-slate-800 text-sm mb-3">Network Path</h4>
                        <div class="flex flex-col gap-2 relative">
                            <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-slate-300"></div>
                            
                            <div v-for="(pathItem, i) in selectedPath" :key="i" class="flex items-center gap-3 relative z-10">
                                <div class="w-4 h-4 rounded-full border-2 border-white shadow-sm flex items-center justify-center" 
                                    :class="i === selectedPath.length - 1 ? 'bg-blue-500 scale-125' : 'bg-slate-400'">
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">{{ pathItem.type }}</span>
                                    <span class="text-xs font-medium text-slate-800">{{ 
                                        pathItem.type === 'olt' ? olts.find(o => o.id === pathItem.id)?.name :
                                        pathItem.type === 'pon' ? `PON ${pathItem.id}` :
                                        pathItem.type === 'odc' ? `ODC ID: ${pathItem.id}` :
                                        pathItem.type === 'odp' ? `ODP ID: ${pathItem.id}` :
                                        `Port ${pathItem.id}`
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-6">
                        <Link v-if="selectedNode.type === 'olt'" :href="`/olts/${selectedNode.data.id}`" class="w-full flex justify-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition-colors">Lihat Data OLT</Link>
                        <Link v-if="selectedNode.type === 'odc'" :href="`/odcs/${selectedNode.data.id}`" class="w-full flex justify-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition-colors">Lihat Data ODC</Link>
                        <Link v-if="selectedNode.type === 'odp'" :href="`/odps/${selectedNode.data.id}`" class="w-full flex justify-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition-colors">Lihat Data ODP</Link>
                        <Link v-if="selectedNode.type === 'port' && selectedNode.data.ont && selectedNode.data.ont.customer_id" :href="`/customers/${selectedNode.data.ont.customer_id}`" class="w-full flex justify-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition-colors">Lihat Pelanggan</Link>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-200 bg-slate-50">
                    <button class="w-full bg-white border border-gray-300 text-gray-700 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition-colors" @click="closeDrawer">
                        Tutup Panel
                    </button>
                </div>
            </div>

            <!-- Overlay for Drawer on mobile -->
            <div 
                v-if="isDrawerOpen" 
                class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm z-40 md:hidden"
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
