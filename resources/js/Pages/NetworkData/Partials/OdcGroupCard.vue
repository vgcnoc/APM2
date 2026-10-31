<template>
    <div
        class="flex flex-col h-full rounded-xl overflow-hidden transition-all"
        :class="[
            splitInfo?.role === 'induk'
                ? 'ring-2 ring-emerald-400/80 shadow-md'
                : splitInfo?.role === 'lanjutan'
                    ? 'ring-2 ring-purple-400/80 shadow-md'
                    : 'shadow-sm'
        ]"
    >
        <!-- ODC Header -->
        <div
            class="relative overflow-hidden text-white p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b shadow-lg"
            :class="[
                splitInfo?.role === 'induk'
                    ? 'bg-gradient-to-r from-[#0f2e28] via-[#134e4a] to-[#1e1b4b] border-emerald-700/60'
                    : splitInfo?.role === 'lanjutan'
                        ? 'bg-gradient-to-r from-[#2e0952] via-[#4c1d95] to-[#1e1b4b] border-purple-700/60'
                        : 'bg-gradient-to-r from-[#20053b] via-[#3b0764] to-[#20053b] border-purple-800/60'
            ]"
        >
            <!-- Modern Tech Motif & Ambient Glow Pattern -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden select-none">
                <svg class="absolute inset-0 w-full h-full opacity-20" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern :id="'odc-dots-' + uniqueId" width="22" height="22" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="1.1" fill="#d8b4fe" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" :fill="'url(#odc-dots-' + uniqueId + ')'" />
                </svg>

                <svg class="absolute right-0 top-0 h-full w-full max-w-4xl opacity-45 md:opacity-60 overflow-visible" viewBox="0 0 800 120" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient :id="'odc-line-grad-' + uniqueId" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#9333ea" stop-opacity="0" />
                            <stop offset="25%" stop-color="#c084fc" stop-opacity="0.85" />
                            <stop offset="65%" stop-color="#f472b6" stop-opacity="0.95" />
                            <stop offset="100%" stop-color="#a855f7" stop-opacity="0.15" />
                        </linearGradient>
                        <linearGradient :id="'odc-line-grad2-' + uniqueId" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#6366f1" stop-opacity="0" />
                            <stop offset="35%" stop-color="#a855f7" stop-opacity="0.8" />
                            <stop offset="75%" stop-color="#e879f9" stop-opacity="0.85" />
                            <stop offset="100%" stop-color="#c084fc" stop-opacity="0.1" />
                        </linearGradient>
                        <filter :id="'odc-glow-' + uniqueId" x="-40%" y="-40%" width="180%" height="180%">
                            <feGaussianBlur stdDeviation="3.5" result="glow" />
                            <feComposite in="SourceGraphic" in2="glow" operator="over" />
                        </filter>
                    </defs>

                    <path d="M 80 68 L 220 68 L 280 28 L 430 28 L 480 72 L 670 72" :stroke="'url(#odc-line-grad-' + uniqueId + ')'" stroke-width="1.8" fill="none" />
                    <path d="M 180 98 L 300 98 L 350 58 L 500 58 L 550 98 L 710 98" :stroke="'url(#odc-line-grad2-' + uniqueId + ')'" stroke-width="1.4" stroke-dasharray="5 3" fill="none" />
                    <path d="M 330 18 L 440 18 L 490 48 L 620 48" stroke="#f472b6" stroke-width="1.2" stroke-dasharray="3 4" fill="none" opacity="0.5" />

                    <polygon points="280,20 288,28 280,36 272,28" fill="#c084fc" fill-opacity="0.35" stroke="#f3e8ff" stroke-width="1.2" />
                    <polygon points="480,64 488,72 480,80 472,72" fill="#f472b6" fill-opacity="0.4" stroke="#ffffff" stroke-width="1.2" />

                    <circle cx="220" cy="68" r="4" fill="#ffffff" :filter="'url(#odc-glow-' + uniqueId + ')'" />
                    <circle cx="220" cy="68" r="9" stroke="#c084fc" stroke-width="1.2" stroke-opacity="0.75" fill="none" />
                    <circle cx="430" cy="28" r="3.5" fill="#fdf4ff" :filter="'url(#odc-glow-' + uniqueId + ')'" />
                    <circle cx="670" cy="72" r="4.5" fill="#ffffff" :filter="'url(#odc-glow-' + uniqueId + ')'" />
                    <circle cx="390" cy="62" r="30" stroke="#c084fc" stroke-width="1.2" stroke-dasharray="4 4" stroke-opacity="0.4" fill="none" />
                </svg>

                <div class="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/4 w-80 h-36 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            </div>

            <div class="relative z-10 min-w-0 flex-1">
                <!-- Split Role & Partner Pill Row -->
                <div v-if="splitInfo?.isSplit" class="flex items-center flex-wrap gap-2 mb-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm border"
                        :class="[
                            splitInfo.role === 'induk'
                                ? 'bg-emerald-400/25 text-emerald-100 border-emerald-300/60'
                                : splitInfo.role === 'lanjutan'
                                    ? 'bg-fuchsia-400/25 text-fuchsia-100 border-fuchsia-300/60'
                                    : 'bg-amber-400/25 text-amber-100 border-amber-300/60'
                        ]"
                    >
                        <span v-if="splitInfo.role === 'induk'">🟢 ODC SPLIT • UNIT INDUK</span>
                        <span v-else-if="splitInfo.role === 'lanjutan'">🟣 ODC SPLIT • UNIT LANJUTAN</span>
                        <span v-else>⚡ ODC TIPE SPLIT</span>
                    </span>

                    <span
                        v-if="splitInfo.partnerLabel"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-black/30 text-white border border-white/20 backdrop-blur-xs"
                    >
                        <span v-if="splitInfo.role === 'induk'" class="text-emerald-300">↱ Terhubung ke Lanjutan:</span>
                        <span v-else class="text-fuchsia-300">↳ Lanjutan dari Induk:</span>
                        <strong class="text-white">{{ splitInfo.partnerLabel }}</strong>
                    </span>
                </div>

                <div class="flex items-center flex-wrap gap-2.5 mb-2">
                    <span class="px-2.5 py-1 bg-white/20 text-white rounded-md text-[10px] font-bold uppercase tracking-wider backdrop-blur-sm shadow-sm flex items-center gap-1.5 shrink-0">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                        SERVER: {{ odcGroup.odc.olt?.name || 'Unknown' }}
                    </span>
                    <h3 class="text-xl font-black tracking-tight text-white break-words">{{ odcGroup.odc.name }}</h3>
                </div>

                <div class="flex items-center flex-wrap gap-x-4 gap-y-1.5 text-xs text-purple-100/90 font-medium">
                    <span class="flex items-center gap-1.5 hover:text-white transition-colors cursor-default">
                        <svg class="w-3.5 h-3.5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="truncate max-w-[240px]">{{ odcGroup.odc.location || odcGroup.odc.address || 'Tanpa Alamat' }}</span>
                    </span>
                    <span class="flex items-center gap-1.5 hover:text-white transition-colors cursor-default">
                        <svg class="w-3.5 h-3.5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        PON {{ odcGroup.odc.pon_port || '?' }}
                    </span>
                    <span v-if="odcGroup.odc.capacity" class="flex items-center gap-1 bg-white/10 px-2 py-0.5 rounded text-[11px] font-bold text-white border border-white/15">
                        KAPASITAS: {{ odcGroup.odc.capacity }} PORT
                    </span>
                    <span v-if="odcGroup.odc.splitter_ratio" class="flex items-center gap-1.5 bg-white/10 px-2 py-0.5 rounded text-[11px] font-bold text-white border border-white/15">
                        SPLITTER: {{ odcGroup.odc.splitter_ratio }}
                    </span>
                </div>

                <!-- Rute Kabel ODC Split jika tersedia -->
                <div v-if="odcGroup.odc.start_point || odcGroup.odc.end_point" class="mt-2 flex items-center flex-wrap gap-1.5 text-[10px] text-purple-100/90">
                    <span class="bg-black/25 border border-white/15 px-2 py-0.5 rounded-md font-semibold inline-flex items-center gap-1">
                        <span>🔗 Jalur Kabel:</span>
                        <strong class="text-white">{{ odcGroup.odc.start_point || splitInfo?.partnerLabel || 'Sumber' }}</strong>
                        <span>&rarr;</span>
                        <strong class="text-white">{{ odcGroup.odc.end_point || odcGroup.odc.name }}</strong>
                        <span v-if="odcGroup.odc.cable_pull" class="ml-1 px-1.5 py-0.2 rounded bg-white/15 text-[9px] uppercase font-bold">{{ odcGroup.odc.cable_pull }}</span>
                    </span>
                </div>

                <!-- VLAN & Mode Akses ODC dari OLT -->
                <div v-if="ponVlans.length > 0" class="flex flex-wrap items-center gap-1.5 mt-2.5">
                    <span class="text-[10px] text-purple-200 font-bold uppercase tracking-wider">VLAN OLT:</span>
                    <div v-for="(vlan, vIdx) in ponVlans" :key="vIdx" class="inline-flex items-center gap-1 bg-black/25 backdrop-blur-xs px-2 py-0.5 rounded text-[11px] font-medium border border-white/10 shadow-xs">
                        <span class="font-bold text-white">VLAN {{ getVlanDisplayId(vlan) }}</span>
                        <span v-if="vlan && typeof vlan === 'object' && vlan.name && vlan.name !== getVlanDisplayId(vlan)" class="text-purple-200 text-[10px]">({{ vlan.name }})</span>
                        <span :class="['px-1.5 py-0.2 text-[9px] font-bold rounded-full uppercase', getModeBadgeClass(getVlanAccessMode(vlan))]">
                            {{ getVlanAccessMode(vlan) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 relative z-10 shrink-0 self-start lg:self-center">
                <div class="flex flex-col items-center justify-center bg-black/25 backdrop-blur-md px-3.5 py-2.5 rounded-xl border border-white/15 shadow-inner min-w-[70px]">
                    <span class="block text-2xl font-black text-white leading-none mb-1">{{ odcGroup.odps.length }}</span>
                    <span class="block text-[9px] font-bold text-purple-200 uppercase tracking-[0.2em]">ODP</span>
                </div>
                <div class="flex flex-col items-center justify-center bg-black/25 backdrop-blur-md px-3.5 py-2.5 rounded-xl border border-white/15 shadow-inner min-w-[70px]">
                    <span class="block text-2xl font-black text-white leading-none mb-1">{{ odcAvailablePorts }}</span>
                    <span class="block text-[9px] font-bold text-purple-200 uppercase tracking-[0.2em]">Free Port</span>
                </div>
            </div>
        </div>

        <!-- ODC Body (ODP Cards) -->
        <div class="bg-white border border-gray-200 border-t-0 p-4 rounded-b-xl flex-1 flex flex-col">
            <!-- Empty ODP Placeholder (mis. untuk unit ODC Split yang belum dipasang ODP) -->
            <div
                v-if="odcGroup.odps.length === 0"
                class="flex-1 min-h-[150px] rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/70 p-6 flex flex-col items-center justify-center text-center"
            >
                <div class="w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-500 flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </div>
                <p class="text-xs font-bold text-slate-700">
                    Belum Ada Distribusi ODP pada {{ odcGroup.odc.name }}
                </p>
                <p class="text-[11px] text-slate-500 mt-1 max-w-sm">
                    <template v-if="splitInfo?.isSplit && splitInfo.partnerLabel">
                        Unit ODC {{ splitInfo.role === 'induk' ? 'Induk' : 'Lanjutan' }} ini terhubung langsung dengan <strong>{{ splitInfo.partnerLabel }}</strong> dan siap menerima instalasi ODP baru.
                    </template>
                    <template v-else>
                        Kapasitas {{ odcGroup.odc.capacity || 0 }} Port siap dihubungkan ke distribusi ODP.
                    </template>
                </p>
            </div>

            <!-- Grid of ODP Display Units (Supports Side-by-Side Split 2 ODP Pairs!) -->
            <div
                v-else
                class="grid gap-4 items-stretch"
                :class="isInsideSplitPair ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3'"
            >
                <template v-for="odpUnit in odpDisplayUnits" :key="odpUnit.key">
                    <!-- CONNECTED ODP SPLIT 2 PAIR FRAME (Berdampingan Induk <-> Lanjutan) -->
                    <div
                        v-if="odpUnit.isSplitPair"
                        class="rounded-2xl border-2 border-indigo-300/90 bg-gradient-to-br from-emerald-50/60 via-white to-purple-50/60 p-3 shadow-sm flex flex-col gap-3"
                        :class="[
                            odpUnit.items.length >= 3 && !isInsideSplitPair
                                ? 'md:col-span-2 xl:col-span-3'
                                : 'md:col-span-2'
                        ]"
                    >
                        <!-- Top Connection Header for ODP Split 2 Pair -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-3 py-2 rounded-xl bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 text-white shadow-xs">
                            <div class="flex items-center flex-wrap gap-1.5 text-[11px]">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-400/20 border border-amber-300/40 text-amber-200 font-black uppercase tracking-wider text-[9px]">
                                    ⚡ PASANGAN ODP SPLIT 2 BERDAMPINGAN
                                </span>
                                <template v-for="(item, idx) in odpUnit.items" :key="'chip-' + item.odp.id">
                                    <span v-if="idx > 0" class="text-amber-300 font-black px-0.5 text-xs" title="Terhubung Kabel Split">━━⚡━━&gt;</span>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold text-[10px] border"
                                        :class="idx === 0 ? 'bg-emerald-500/25 border-emerald-400/50 text-emerald-100' : 'bg-purple-500/25 border-purple-400/50 text-purple-100'"
                                    >
                                        <span>{{ idx === 0 ? '🟢 INDUK:' : '🟣 LANJUTAN:' }}</span>
                                        <strong class="text-white">{{ item.odp.name }}</strong>
                                        <span class="opacity-80">({{ item.odp.total_ports }}P)</span>
                                    </span>
                                </template>
                            </div>

                            <div class="flex items-center gap-2 shrink-0 text-[10px] font-bold">
                                <span class="px-2 py-0.5 rounded-md bg-white/10 border border-white/15 text-emerald-200">
                                    {{ getPairAvailablePorts(odpUnit.items) }} Free / {{ getPairTotalPorts(odpUnit.items) }} Port Gabungan
                                </span>
                            </div>
                        </div>

                        <!-- Side-by-Side ODP Cards with Center Connector -->
                        <div
                            class="grid gap-4 relative items-stretch"
                            :class="odpUnit.items.length >= 3 && !isInsideSplitPair ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3' : 'grid-cols-1 md:grid-cols-2'"
                        >
                            <!-- Floating Center Optical Connector Badge (Desktop/Tablet) -->
                            <div
                                v-if="odpUnit.items.length === 2"
                                class="hidden md:flex absolute left-1/2 top-12 -translate-x-1/2 z-20 pointer-events-none"
                            >
                                <div class="px-2 py-1 rounded-full bg-gradient-to-r from-emerald-600 via-indigo-600 to-purple-600 text-white text-[9px] font-black shadow-md border-2 border-white flex items-center gap-1 tracking-wider">
                                    <span>INDUK</span>
                                    <span>⇄</span>
                                    <span>LANJUTAN</span>
                                </div>
                            </div>

                            <template v-for="(item, idx) in odpUnit.items" :key="item.odp.id">
                                <!-- Mobile vertical connector between Induk and Lanjutan -->
                                <div v-if="idx > 0" class="flex md:hidden items-center justify-center -my-2 z-10">
                                    <span class="px-2.5 py-0.5 rounded-full bg-indigo-600 text-white text-[9px] font-black shadow border border-white">
                                        ⬇ TERHUBUNG SPLIT DARI INDUK ⬇
                                    </span>
                                </div>

                                <OdpNetworkCard
                                    :odp="item.odp"
                                    :split-info="item.splitInfo"
                                    :pon-vlans="getOdpVlans(item.odp)"
                                    :cbp-requests="cbpRequests"
                                    @open-photo="$emit('open-photo', $event)"
                                />
                            </template>
                        </div>
                    </div>

                    <!-- NORMAL / STANDALONE ODP CARD -->
                    <OdpNetworkCard
                        v-else
                        :odp="odpUnit.items[0].odp"
                        :split-info="odpUnit.items[0].splitInfo"
                        :pon-vlans="getOdpVlans(odpUnit.items[0].odp)"
                        :cbp-requests="cbpRequests"
                        @open-photo="$emit('open-photo', $event)"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import OdpNetworkCard from './OdpNetworkCard.vue';

const props = defineProps({
    odcGroup: { type: Object, required: true },
    splitInfo: { type: Object, default: () => ({ isSplit: false }) },
    isInsideSplitPair: { type: Boolean, default: false },
    ponVlans: { type: Array, default: () => [] },
    getOdpSplitInfoFn: { type: Function, required: true },
    getOdpPonVlansFn: { type: Function, required: true },
    cbpRequests: { type: Array, default: () => [] },
});

defineEmits(['open-photo']);

const uniqueId = computed(() => String(props.odcGroup?.odc?.id ?? 'null').replace(/[^a-zA-Z0-9_-]/g, '_'));

const odcAvailablePorts = computed(() => {
    let available = 0;
    (props.odcGroup?.odps || []).forEach(o => {
        available += Math.max(0, Number(o.total_ports || 0) - Number(o.used_ports || 0));
    });
    return available;
});

const getOdpVlans = (odp) => {
    return props.getOdpPonVlansFn ? props.getOdpPonVlansFn(odp) : [];
};

const getVlanDisplayId = (vlan) => {
    if (!vlan) return '-';
    if (typeof vlan === 'object') {
        return vlan.vlan_id || vlan.id || vlan.vlan || (vlan.name ? vlan.name : '-');
    }
    return String(vlan);
};

const getVlanAccessMode = (vlan) => {
    if (vlan && vlan.access_mode) return vlan.access_mode;
    const name = (vlan?.name || '').toUpperCase();
    if (name.includes('HOTSPOT')) return 'Hotspot';
    if (name.includes('PPPOE')) return 'PPPOE';
    if (name.includes('STATIC')) return 'Static';
    if (name.includes('DHCP')) return 'DHCP';
    return 'PPPOE';
};

const getModeBadgeClass = (mode) => {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-purple-100 text-purple-800 border border-purple-200';
        case 'HOTSPOT':
            return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'STATIC':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'DHCP':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'BRIDGE':
            return 'bg-teal-100 text-teal-800 border border-teal-200';
        case 'TAGGED':
            return 'bg-indigo-100 text-indigo-800 border border-indigo-200';
        case 'UNTAGGED':
            return 'bg-gray-100 text-gray-800 border border-gray-200';
        default:
            return 'bg-slate-100 text-slate-800 border border-slate-200';
    }
};

/**
 * Kelompokkan ODP di dalam satu ODC menjadi:
 * - Pasangan Split 2 berdampingan (Induk langsung diikuti Lanjutan)
 * - ODP tunggal lainnya tetap terurut rapi
 */
const odpDisplayUnits = computed(() => {
    const odps = props.odcGroup?.odps || [];
    if (odps.length === 0) return [];

    const familyMap = new Map();
    const orderKeys = [];

    odps.forEach(odp => {
        const splitInfo = props.getOdpSplitInfoFn(odp);
        const key = (splitInfo.isSplit && splitInfo.rootId)
            ? `split-${splitInfo.rootId}`
            : `single-${odp.id}`;

        if (!familyMap.has(key)) {
            familyMap.set(key, []);
            orderKeys.push(key);
        }
        familyMap.get(key).push({ odp, splitInfo });
    });

    const units = orderKeys.map(key => {
        const items = familyMap.get(key);
        // Pastikan Induk selalu di kiri (index 0) dan Lanjutan di kanan (index 1+)
        items.sort((a, b) => {
            const aRank = a.odp.parent_odp_id ? 1 : 0;
            const bRank = b.odp.parent_odp_id ? 1 : 0;
            if (aRank !== bRank) return aRank - bRank;
            return Number(a.odp.id) - Number(b.odp.id);
        });

        return {
            key,
            isSplitPair: items.length > 1,
            sortName: items[0].odp.name || '',
            items,
        };
    });

    units.sort((a, b) => a.sortName.localeCompare(b.sortName, undefined, { numeric: true, sensitivity: 'base' }));
    return units;
});

const getPairAvailablePorts = (items) => {
    return items.reduce((sum, item) => sum + Math.max(0, Number(item.odp.total_ports || 0) - Number(item.odp.used_ports || 0)), 0);
};

const getPairTotalPorts = (items) => {
    return items.reduce((sum, item) => sum + Number(item.odp.total_ports || 0), 0);
};
</script>
