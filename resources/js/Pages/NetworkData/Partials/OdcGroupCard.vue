<template>
    <div
        class="odc-group min-w-0 flex flex-col h-full rounded-2xl overflow-hidden transition-all bg-white border border-slate-200/90 shadow-xs hover:shadow-md"
        :class="[
            splitInfo?.role === 'induk'
                ? 'ring-2 ring-emerald-400/90 border-emerald-300'
                : splitInfo?.role === 'lanjutan'
                    ? 'ring-2 ring-purple-400/90 border-purple-300'
                    : 'border-slate-200'
        ]"
    >
        <!-- ODC Hardware Header -->
        <header
            class="odc-header relative overflow-hidden"
            :class="'odc-header--' + (splitInfo?.role || 'standard')"
        >
            <!-- Background Decorative Glow -->
            <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full opacity-10 bg-white pointer-events-none"></div>

            <!-- Role Badge if Split -->
            <div v-if="splitInfo?.isSplit" class="odc-header__role mb-2 flex items-center justify-between">
                <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider text-white shadow-xs"
                    :class="splitInfo.role === 'induk' ? 'bg-emerald-600' : 'bg-purple-600'"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    {{ splitInfo.role === 'induk' ? 'Unit Induk (Master ODC)' : splitInfo.role === 'lanjutan' ? 'Unit Lanjutan (Extension ODC)' : 'ODC Split' }}
                </span>

                <span v-if="splitInfo?.partnerLabel && !isInsideSplitPair" class="text-[10px] text-slate-300 bg-white/10 px-2 py-0.5 rounded-md border border-white/15">
                    {{ splitInfo.role === 'induk' ? '➔ Ke:' : '↳ Dari:' }} <strong>{{ splitInfo.partnerLabel }}</strong>
                </span>
            </div>

            <!-- Server & Title -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-indigo-200/90 font-medium flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                        OLT / Server: <strong class="text-white">{{ odcGroup.odc.olt?.name || 'Unknown' }}</strong>
                    </p>
                    <h3 class="text-xl md:text-2xl font-black text-white tracking-tight mt-0.5 break-words">
                        {{ odcGroup.odc.name }}
                    </h3>
                    <p class="text-xs text-slate-300/80 flex items-center gap-1.5 mt-1 font-medium break-words">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ odcGroup.odc.location || odcGroup.odc.address || 'Tanpa alamat' }}
                    </p>
                </div>

                <!-- Fast Summary Pills -->
                <div class="flex items-center gap-2 shrink-0">
                    <div class="bg-white/10 backdrop-blur-xs border border-white/15 px-3 py-1.5 rounded-xl text-center">
                        <div class="text-lg font-black text-white leading-none">{{ odcGroup.odps.length }}</div>
                        <div class="text-[9px] uppercase tracking-wider text-slate-300 font-bold mt-0.5">ODP</div>
                    </div>
                    <div class="bg-emerald-500/20 backdrop-blur-xs border border-emerald-400/30 px-3 py-1.5 rounded-xl text-center">
                        <div class="text-lg font-black text-emerald-300 leading-none">{{ odcAvailablePorts }}</div>
                        <div class="text-[9px] uppercase tracking-wider text-emerald-200 font-bold mt-0.5">Port Free</div>
                    </div>
                </div>
            </div>

            <!-- Hardware Specs Row -->
            <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-white/15 text-xs">
                <div class="bg-black/20 rounded-lg p-2 border border-white/10">
                    <span class="text-[9px] font-bold text-slate-300 uppercase block">Port PON</span>
                    <strong class="text-sm font-black text-white">{{ odcGroup.odc.pon_port ? 'PON ' + odcGroup.odc.pon_port : '—' }}</strong>
                </div>
                <div class="bg-black/20 rounded-lg p-2 border border-white/10">
                    <span class="text-[9px] font-bold text-slate-300 uppercase block">Kapasitas ODC</span>
                    <strong class="text-sm font-black text-white">{{ odcGroup.odc.capacity || 0 }} Port</strong>
                </div>
                <div class="bg-black/20 rounded-lg p-2 border border-white/10">
                    <span class="text-[9px] font-bold text-slate-300 uppercase block">Splitter Ratio</span>
                    <strong class="text-sm font-black text-white">{{ odcGroup.odc.splitter_ratio || '1:8' }}</strong>
                </div>
            </div>

            <!-- Cable Pull & VLAN Row -->
            <div v-if="odcGroup.odc.start_point || odcGroup.odc.end_point || ponVlans.length" class="mt-2.5 pt-2.5 border-t border-white/10 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div v-if="odcGroup.odc.start_point || odcGroup.odc.end_point" class="flex items-center gap-1.5 text-slate-300 text-[11px]">
                    <span class="text-indigo-300 font-bold">Rute Kabel:</span>
                    <span class="font-medium">{{ odcGroup.odc.start_point || splitInfo?.partnerLabel || 'Sumber' }} ➔ {{ odcGroup.odc.end_point || odcGroup.odc.name }}</span>
                    <span v-if="odcGroup.odc.cable_pull" class="bg-white/15 px-1.5 py-0.2 rounded font-mono font-bold text-[10px]">{{ odcGroup.odc.cable_pull }}</span>
                </div>

                <div v-if="ponVlans.length" class="flex items-center gap-1.5 flex-wrap ml-auto">
                    <span class="text-[10px] font-bold text-indigo-200 uppercase">VLAN:</span>
                    <span v-for="(vlan, index) in ponVlans" :key="index" class="bg-white/15 border border-white/20 text-white text-[10px] px-2 py-0.5 rounded-md font-bold flex items-center gap-1">
                        <span>VLAN {{ getVlanDisplayId(vlan) }}</span>
                        <span class="text-[9px] font-mono opacity-80 uppercase">({{ getVlanAccessMode(vlan) }})</span>
                    </span>
                </div>
            </div>
        </header>

        <!-- ODC Body (ODP Cards Grid) -->
        <div class="bg-slate-50/60 p-4 md:p-5 flex-1 flex flex-col">
            <!-- Empty ODP Placeholder -->
            <div
                v-if="odcGroup.odps.length === 0"
                class="flex-1 min-h-[160px] rounded-2xl border-2 border-dashed border-slate-300 bg-white p-6 flex flex-col items-center justify-center text-center shadow-2xs"
            >
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center mb-2.5 shadow-2xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </div>
                <h4 class="text-sm font-black text-slate-800">
                    Belum Ada Distribusi ODP pada {{ odcGroup.odc.name }}
                </h4>
                <p class="text-xs text-slate-500 mt-1 max-w-sm">
                    <template v-if="splitInfo?.isSplit && splitInfo.partnerLabel">
                        Unit ODC {{ splitInfo.role === 'induk' ? 'Induk' : 'Lanjutan' }} ini siap dihubungkan dengan <strong>{{ splitInfo.partnerLabel }}</strong> untuk instalasi ODP baru.
                    </template>
                    <template v-else>
                        Kapasitas {{ odcGroup.odc.capacity || 0 }} Port siap dihubungkan ke titik distribusi ODP.
                    </template>
                </p>
            </div>

            <!-- Auto-Fill Dynamic Grid of ODP Cards -->
            <div
                v-else
                class="odc-odp-grid"
            >
                <template v-for="odpUnit in odpDisplayUnits" :key="odpUnit.key">
                    <!-- CONNECTED ODP SPLIT 2 PAIR FRAME (Berdampingan Induk <-> Lanjutan) -->
                    <div
                        v-if="odpUnit.isSplitPair"
                        class="col-span-full rounded-2xl border-2 border-indigo-200/80 bg-white p-3.5 md:p-4 shadow-xs space-y-3.5"
                    >
                        <SplitConnectionHeader
                            kind="ODP"
                            :nodes="odpUnit.items.map(item => ({
                                id: item.odp.id,
                                name: item.odp.name,
                                role: item.splitInfo?.role,
                                detail: item.odp.total_ports + ' port',
                            }))"
                        >
                            <span class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-1 text-emerald-800 font-bold text-xs">
                                <strong>{{ getPairAvailablePorts(odpUnit.items) }}</strong> / {{ getPairTotalPorts(odpUnit.items) }} port tersedia
                            </span>
                        </SplitConnectionHeader>

                        <div class="split-odp-cards">
                            <OdpNetworkCard
                                v-for="item in odpUnit.items"
                                :key="item.odp.id"
                                :odp="item.odp"
                                :split-info="item.splitInfo"
                                :pon-vlans="getOdpVlans(item.odp)"
                                :cbp-requests="cbpRequests"
                                @open-photo="$emit('open-photo', $event)"
                            />
                        </div>
                    </div>

                    <!-- STANDALONE ODP CARD -->
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
import SplitConnectionHeader from './SplitConnectionHeader.vue';

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

<style scoped>
.odc-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
    border-top: 4px solid #6366f1;
}
.odc-header--induk {
    border-top-color: #10b981;
    background: linear-gradient(135deg, #062b24 0%, #064e3b 60%, #0f172a 100%);
}
.odc-header--lanjutan {
    border-top-color: #a855f7;
    background: linear-gradient(135deg, #2e1065 0%, #4c1d95 60%, #0f172a 100%);
}

.odc-odp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
    gap: 16px;
    align-items: stretch;
}

.split-odp-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
    gap: 16px;
    align-items: stretch;
}
</style>
