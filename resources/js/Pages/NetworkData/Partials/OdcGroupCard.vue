<template>
    <div
        class="odc-group min-w-0 flex flex-col h-full rounded-xl overflow-hidden transition-all"
        :class="[
            splitInfo?.role === 'induk'
                ? 'ring-2 ring-emerald-400/80 shadow-md'
                : splitInfo?.role === 'lanjutan'
                    ? 'ring-2 ring-purple-400/80 shadow-md'
                    : 'shadow-sm'
        ]"
    >
        <header class="odc-header" :class="'odc-header--' + (splitInfo?.role || 'standard')">
            <div v-if="splitInfo?.isSplit" class="odc-header__role">
                <span class="odc-header__dot" aria-hidden="true"></span>
                {{ splitInfo.role === 'induk' ? 'Unit Induk' : splitInfo.role === 'lanjutan' ? 'Unit Lanjutan' : 'ODC Split' }}
            </div>
            <p class="odc-header__server">OLT / Server: <strong>{{ odcGroup.odc.olt?.name || 'Unknown' }}</strong></p>
            <h3 class="odc-header__name">{{ odcGroup.odc.name }}</h3>
            <p class="odc-header__location">{{ odcGroup.odc.location || odcGroup.odc.address || 'Tanpa alamat' }}</p>

            <div v-if="splitInfo?.partnerLabel && !isInsideSplitPair" class="odc-header__partner">
                <span>{{ splitInfo.role === 'induk' ? 'Terhubung ke lanjutan' : 'Terhubung dari induk' }}</span>
                <strong>{{ splitInfo.partnerLabel }}</strong>
            </div>

            <dl class="odc-header__specs">
                <div><dt>Port PON</dt><dd>{{ odcGroup.odc.pon_port || '—' }}</dd></div>
                <div><dt>Kapasitas ODC</dt><dd>{{ odcGroup.odc.capacity || 0 }} port</dd></div>
                <div><dt>Splitter</dt><dd>{{ odcGroup.odc.splitter_ratio || '—' }}</dd></div>
            </dl>

            <div v-if="odcGroup.odc.start_point || odcGroup.odc.end_point" class="odc-header__cable">
                <span class="odc-header__label">Jalur kabel</span>
                <p>
                    {{ odcGroup.odc.start_point || splitInfo?.partnerLabel || 'Sumber' }}
                    <span aria-label="menuju"> → </span>
                    {{ odcGroup.odc.end_point || odcGroup.odc.name }}
                </p>
                <span v-if="odcGroup.odc.cable_pull" class="odc-header__label">{{ odcGroup.odc.cable_pull }}</span>
            </div>

            <div v-if="ponVlans.length" class="odc-header__vlans">
                <span class="odc-header__label">VLAN OLT</span>
                <div class="flex flex-wrap gap-2">
                    <span v-for="(vlan, index) in ponVlans" :key="index" class="odc-header__vlan">
                        <strong>VLAN {{ getVlanDisplayId(vlan) }}</strong>
                        <span v-if="vlan && typeof vlan === 'object' && vlan.name && vlan.name !== getVlanDisplayId(vlan)" class="odc-header__vlan-name">{{ vlan.name }}</span>
                        <span :class="['px-1.5 py-0.5 text-[10px] font-bold rounded', getModeBadgeClass(getVlanAccessMode(vlan))]">{{ getVlanAccessMode(vlan) }}</span>
                    </span>
                </div>
            </div>

            <div class="odc-header__stats">
                <div><strong>{{ odcGroup.odps.length }}</strong><span>ODP terhubung</span></div>
                <div><strong>{{ odcAvailablePorts }}</strong><span>Port ODP tersedia</span></div>
            </div>
        </header>

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
                class="odc-odp-grid"
            >
                <template v-for="odpUnit in odpDisplayUnits" :key="odpUnit.key">
                    <!-- CONNECTED ODP SPLIT 2 PAIR FRAME (Berdampingan Induk <-> Lanjutan) -->
                    <div
                        v-if="odpUnit.isSplitPair"
                        class="col-span-full rounded-xl border border-indigo-200 bg-gray-50/50 p-3 flex flex-col gap-3"
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
                            <span class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-emerald-800">
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

<style scoped>
.odc-group { container: network-odc / inline-size; }
.odc-odp-grid, .split-odp-cards { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; align-items: stretch; }
@container network-odc (min-width: 640px) {
    .odc-odp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@container network-odc (min-width: 700px) {
    .split-odp-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@container network-odc (min-width: 1050px) {
    .odc-odp-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
.odc-header {
    --odc-accent: #6366f1;
    --odc-muted: #d8deee;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 14px 16px;
    border-top: 4px solid var(--odc-accent);
    border-bottom: 1px solid var(--border-dark, #e2e8f0);
    background: #242044;
    color: #fff;
}
.odc-header--induk { --odc-accent: #34d399; background: linear-gradient(110deg, #113d35, #1e293b); }
.odc-header--lanjutan { --odc-accent: #c084fc; background: linear-gradient(110deg, #48206b, #292044); }
.odc-header__role { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.odc-header__dot { height: 8px; width: 8px; flex-shrink: 0; border-radius: 50%; background: var(--odc-accent); }
.odc-header__server, .odc-header__location { margin: 0; font-size: 12px; color: var(--odc-muted, #64748b); overflow-wrap: anywhere; }
.odc-header .odc-header__name { margin: 0; color: #fff !important; font-size: 19px; font-weight: 800; line-height: 1.35; overflow-wrap: anywhere; }
.odc-header__partner { display: grid; gap: 4px; padding: 8px 10px; border-left: 3px solid var(--odc-accent); border-radius: 4px; background: rgb(255 255 255 / .07); overflow-wrap: anywhere; }
.odc-header__partner span, .odc-header__label { font-size: 10px; color: var(--odc-muted, #64748b); font-weight: 650; }
.odc-header__partner strong { font-size: 12px; line-height: 1.5; }
.odc-header__specs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin: 0; }
.odc-header__specs dt { font-size: 10px; color: var(--odc-muted, #64748b); }
.odc-header__specs dd { margin: 4px 0 0; font-size: 13px; font-weight: 750; overflow-wrap: anywhere; }
.odc-header__cable { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; padding-top: 8px; border-top: 1px solid rgb(255 255 255 / .15); }
.odc-header__cable p { margin: 0; font-size: 11px; overflow-wrap: anywhere; }
.odc-header__vlans { display: grid; gap: 6px; }
.odc-header__vlan { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 4px; padding: 3px 6px; border: 1px solid rgb(255 255 255 / .15); border-radius: 6px; font-size: 11px; }
.odc-header__vlan-name { color: var(--odc-muted, #64748b); font-size: 10px; overflow-wrap: anywhere; }
.odc-header__stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: auto; padding-top: 10px; border-top: 1px solid rgb(255 255 255 / .15); }
.odc-header__stats div { display: flex; align-items: center; gap: 8px; min-width: 0; }
.odc-header__stats strong { font-size: 24px; line-height: 1; }
.odc-header__stats span { font-size: 11px; color: var(--odc-muted, #64748b); }
@container network-odc (min-width: 800px) {
    .odc-header--standard {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(210px, .65fr) minmax(190px, .55fr);
        grid-template-rows: auto auto auto;
        gap: 4px 20px;
    }
    .odc-header--standard .odc-header__server { grid-column: 1; grid-row: 1; }
    .odc-header--standard .odc-header__name { grid-column: 1; grid-row: 2; }
    .odc-header--standard .odc-header__location { grid-column: 1; grid-row: 3; }
    .odc-header--standard .odc-header__specs { grid-column: 2; grid-row: 1 / 4; align-self: center; }
    .odc-header--standard .odc-header__stats { grid-column: 3; grid-row: 1 / 4; align-self: center; margin: 0; padding: 0; border: 0; }
    .odc-header--standard .odc-header__cable, .odc-header--standard .odc-header__vlans { grid-column: 1 / -1; }
}
@media (max-width: 480px) { .odc-header { padding: 16px; } }
</style>

