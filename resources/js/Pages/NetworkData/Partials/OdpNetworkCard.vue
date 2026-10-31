<template>
    <div
        class="rounded-xl transition-all group bg-white flex flex-col relative overflow-hidden"
        :class="[
            splitInfo?.role === 'induk'
                ? 'border-2 border-emerald-300/90 hover:border-emerald-400 shadow-xs hover:shadow-md'
                : splitInfo?.role === 'lanjutan'
                    ? 'border-2 border-purple-300/90 hover:border-purple-400 shadow-xs hover:shadow-md'
                    : splitInfo?.isSplit
                        ? 'border-2 border-amber-300/80 hover:border-amber-400 shadow-xs hover:shadow-md'
                        : 'border border-gray-200 hover:border-indigo-300 hover:shadow-md'
        ]"
    >
        <!-- Status Strip -->
        <div class="w-full h-1" :class="getOdpStatusColor(odp)"></div>

        <!-- Split Relationship Banner on Card -->
        <div
            v-if="splitInfo?.isSplit"
            class="px-3.5 py-2 border-b text-[10px] flex flex-col gap-1"
            :class="[
                splitInfo.role === 'induk'
                    ? 'bg-gradient-to-r from-emerald-50 via-teal-50/70 to-white border-emerald-200/80 text-emerald-900'
                    : splitInfo.role === 'lanjutan'
                        ? 'bg-gradient-to-r from-purple-50 via-fuchsia-50/70 to-white border-purple-200/80 text-purple-900'
                        : 'bg-gradient-to-r from-amber-50 to-white border-amber-200/80 text-amber-900'
            ]"
        >
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-black uppercase tracking-wider text-[9px] shadow-2xs"
                    :class="[
                        splitInfo.role === 'induk'
                            ? 'bg-emerald-600 text-white'
                            : splitInfo.role === 'lanjutan'
                                ? 'bg-purple-600 text-white'
                                : 'bg-amber-500 text-white'
                    ]"
                >
                    <span v-if="splitInfo.role === 'induk'">🟢 SPLIT 2 • UNIT INDUK</span>
                    <span v-else-if="splitInfo.role === 'lanjutan'">🟣 SPLIT 2 • UNIT LANJUTAN</span>
                    <span v-else>⚡ ODP TIPE SPLIT 2</span>
                </span>

                <span
                    v-if="splitInfo.partnerLabel"
                    class="inline-flex items-center gap-1 font-bold text-[10px] px-2 py-0.5 rounded-md border bg-white/90 truncate max-w-full"
                    :class="splitInfo.role === 'induk' ? 'border-emerald-200 text-emerald-800' : 'border-purple-200 text-purple-800'"
                    :title="splitInfo.partnerLabel"
                >
                    <span v-if="splitInfo.role === 'induk'">↱ Lanjutan:</span>
                    <span v-else>↳ Dari Induk:</span>
                    <strong class="truncate max-w-[150px]">{{ splitInfo.partnerLabel }}</strong>
                </span>
            </div>

            <div
                v-if="odp.start_point || odp.end_point || odp.cable_pull"
                class="flex items-center gap-1.5 text-[9px] font-medium opacity-85 truncate"
                :title="`Rute: ${odp.start_point || 'Induk'} ➔ ${odp.end_point || odp.name}`"
            >
                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                <span class="truncate">
                    Rute: <strong>{{ odp.start_point || splitInfo.partnerLabel || 'Sumber' }}</strong> &rarr; <strong>{{ odp.end_point || odp.name }}</strong>
                </span>
                <span v-if="odp.cable_pull" class="ml-auto px-1.5 py-0.2 rounded bg-white/80 border border-current/20 font-bold shrink-0">
                    {{ odp.cable_pull }}
                </span>
            </div>
        </div>

        <div class="p-4 flex-1 flex flex-col">
            <div class="flex justify-between items-start mb-3 gap-2">
                <div class="min-w-0">
                    <h4 class="font-bold text-gray-800 text-lg leading-tight break-words">{{ odp.name }}</h4>
                    <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ odp.kode_odp || '-' }}</p>
                </div>
                <div class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider shrink-0" :class="getOdpStatusBadge(odp)">
                    {{ odp.status }}
                </div>
            </div>

            <!-- Address & Coord Pills -->
            <div class="flex flex-wrap gap-2 mb-3">
                <div v-if="odp.address && odp.address !== '-'" class="bg-gray-50 border border-gray-200 text-gray-600 text-[10px] px-2 py-1 rounded-md flex items-center gap-1" :title="odp.address">
                    <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="truncate max-w-[150px]">{{ odp.address }}</span>
                </div>

                <div class="bg-gray-50 border border-gray-200 text-gray-700 text-[10px] px-2 py-1 rounded-md flex items-center gap-1 font-mono">
                    <svg class="w-3 h-3 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="font-bold text-slate-500 uppercase">Lat:</span>
                    <span :class="odp.latitude ? 'text-gray-900 font-semibold' : 'text-gray-400 italic'">{{ odp.latitude || '-' }}</span>
                </div>

                <div class="bg-gray-50 border border-gray-200 text-gray-700 text-[10px] px-2 py-1 rounded-md flex items-center gap-1 font-mono">
                    <svg class="w-3 h-3 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="font-bold text-slate-500 uppercase">Long:</span>
                    <span :class="odp.longitude ? 'text-gray-900 font-semibold' : 'text-gray-400 italic'">{{ odp.longitude || '-' }}</span>
                </div>

                <a v-if="odp.latitude && odp.longitude" :href="`https://www.google.com/maps?q=${odp.latitude},${odp.longitude}`" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-1.5 py-1 rounded-md flex items-center gap-1 transition-colors" title="Buka Titik Koordinat di Google Maps">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Maps
                </a>

                <div class="bg-gray-50 border border-gray-200 text-gray-800 font-bold text-[10px] px-2 py-1 rounded-md flex items-center gap-1">
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ odp.total_ports }} Port
                </div>

                <div class="bg-indigo-50 border border-indigo-200 text-indigo-700 text-[10px] px-2 py-1 rounded-md flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ odp.area?.name || 'Belum ada area' }}
                </div>

                <div v-if="odpCbpCount > 0" class="bg-rose-50 border border-rose-300 text-rose-700 font-black text-[10px] px-2 py-1 rounded-md flex items-center gap-1 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    ⏳ {{ odpCbpCount }} Port Proses CBP
                </div>

                <div v-if="odpGratisCount > 0" class="bg-emerald-50 border border-emerald-300 text-emerald-800 font-black text-[10px] px-2 py-1 rounded-md flex items-center gap-1 shadow-xs" title="Terdapat pelanggan gratis pada ODP ini">
                    <span>🎁</span>
                    <span>{{ odpGratisCount }} Gratis</span>
                </div>
            </div>

            <!-- VLAN Sinkron OLT PON -->
            <div class="mb-4 p-2 bg-indigo-50/60 rounded-lg border border-indigo-100/80">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        VLAN Sinkron OLT
                    </span>
                    <span v-if="odp.odc?.pon_port" class="text-[9px] font-bold text-indigo-600 bg-indigo-100 px-1.5 py-0.2 rounded">PON {{ odp.odc.pon_port }}</span>
                </div>
                <div v-if="ponVlans.length > 0" class="flex flex-wrap gap-1.5">
                    <div v-for="(vlan, vIdx) in ponVlans" :key="vIdx" class="inline-flex items-center gap-1 bg-white border border-indigo-200 px-1.5 py-0.5 rounded text-[10px] shadow-xs">
                        <span class="font-bold text-indigo-700">VLAN {{ vlan.vlan_id || vlan }}</span>
                        <span v-if="vlan.name" class="text-gray-600 text-[9px]">({{ vlan.name }})</span>
                        <span :class="['px-1 py-0.2 text-[8px] font-bold rounded-full uppercase', getModeBadgeClass(getVlanAccessMode(vlan))]">
                            {{ getVlanAccessMode(vlan) }}
                        </span>
                    </div>
                </div>
                <span v-else class="text-[10px] text-gray-400 italic">Tidak ada VLAN diset pada OLT</span>
            </div>

            <!-- Ports List (Directly in Card) -->
            <div class="space-y-2 mb-4 max-h-56 overflow-y-auto pr-1 text-xs flex-1">
                <div v-for="portNum in Number(odp.total_ports || 0)" :key="portNum" class="flex items-start gap-2 py-1 border-b border-gray-50 last:border-b-0">
                    <span class="text-gray-400 font-mono w-4 text-right pt-0.5 shrink-0">{{ portNum }}</span>
                    <template v-if="getPortData(portNum)">
                        <div class="w-2.5 h-2.5 rounded-full shrink-0 mt-1" :class="getCustomerStatusColor(getPortData(portNum).customer?.status, getCbpForCustomer(getPortData(portNum).customer))"></div>
                        <div v-if="getPortData(portNum).customer" class="flex flex-col min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap leading-tight">
                                <span v-if="getCbpForCustomer(getPortData(portNum).customer)" class="text-rose-700 font-black text-[10px] bg-rose-100 px-1.5 py-0.2 rounded border border-rose-300 uppercase tracking-wider">
                                    ⏳ Proses CBP
                                </span>
                                <span v-else-if="getPortData(portNum).customer.status === 'active'" class="text-emerald-600 font-bold text-xs">Aktif</span>
                                <span v-else-if="getPortData(portNum).customer.status === 'installing'" class="text-amber-600 font-bold text-xs">Proses Instalasi</span>
                                <span v-else-if="getPortData(portNum).customer.status === 'suspended'" class="text-orange-600 font-bold text-xs">Isolir</span>
                                <span v-else-if="getPortData(portNum).customer.status === 'terminated'" class="text-rose-700 font-bold text-xs">Stop Permanen</span>
                                <span v-else class="text-gray-600 font-bold text-xs">{{ getPortData(portNum).customer.status }}</span>

                                <span class="text-gray-400 text-xs">•</span>

                                <span class="font-bold text-gray-800 truncate text-xs">
                                    {{ getPortData(portNum).customer.customer_code }} - {{ getPortData(portNum).customer.name }}
                                </span>

                                <span v-if="getPortData(portNum).customer.service_status === 'gratis'" class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs" title="Pelanggan Bebas Tagihan (Gratis)">
                                    <span>🎁</span>
                                    <span>Gratis</span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2 mt-0.5 flex-wrap text-[9px] text-gray-500">
                                <span v-if="getPortData(portNum).customer.status === 'active' && getPortData(portNum).customer.activation_date">
                                    Tgl: {{ getPortData(portNum).customer.activation_date }}
                                </span>
                                <span v-else-if="getPortData(portNum).start_time">
                                    Tgl: {{ getPortData(portNum).updated_at ? getPortData(portNum).updated_at.substring(0, 10) : '' }}
                                </span>
                                <span v-else>
                                    Tgl: {{ getPortData(portNum).customer.created_at?.substring(0, 10) }}
                                </span>

                                <span v-if="getPortData(portNum).vlan_id" class="bg-purple-100 text-purple-700 px-1 rounded font-bold border border-purple-200">VLAN: {{ getPortData(portNum).vlan_id }}</span>
                                <span v-if="getPortData(portNum).hotspot_vlan_id" class="bg-orange-100 text-orange-700 px-1 rounded font-bold border border-orange-200">HS: {{ getPortData(portNum).hotspot_vlan_id }}</span>
                            </div>

                            <div v-if="getCbpForCustomer(getPortData(portNum).customer)" class="mt-1 p-2 rounded-xl bg-gradient-to-r from-rose-50 to-orange-50 border border-rose-200 text-[10px] space-y-1">
                                <div class="flex items-center justify-between text-rose-800 font-black">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Proses Cabut Perangkat (CBP)
                                    </span>
                                    <span class="font-mono bg-white px-1.5 py-0.2 rounded border border-rose-200 text-rose-800 text-[9px] font-bold">
                                        {{ getCbpForCustomer(getPortData(portNum).customer).cbp_number }}
                                    </span>
                                </div>
                                <div class="text-[9px] text-slate-700 flex items-center justify-between flex-wrap gap-1">
                                    <span>
                                        Status: <strong :class="getCbpForCustomer(getPortData(portNum).customer).status === 'assigned' ? 'text-blue-700' : 'text-amber-700'">
                                            {{ getCbpForCustomer(getPortData(portNum).customer).status === 'assigned' ? '🔧 Teknisi Ditugaskan' : '⏳ Menunggu Jadwal' }}
                                        </strong>
                                    </span>
                                    <span v-if="getCbpForCustomer(getPortData(portNum).customer).technicians?.length" class="text-slate-600 font-semibold truncate max-w-[130px]" :title="getCbpForCustomer(getPortData(portNum).customer).technicians.map(t => t.name).join(', ')">
                                        {{ getCbpForCustomer(getPortData(portNum).customer).technicians.map(t => t.name).join(', ') }}
                                    </span>
                                </div>
                                <div class="text-[9px] text-rose-800/90 italic flex items-center justify-between pt-1 border-t border-rose-100">
                                    <span>⚠️ Port belum dicabut / belum kosong</span>
                                    <Link :href="route('cbp.jadwal')" class="text-rose-700 hover:text-rose-900 font-bold underline">Kelola Jadwal &rarr;</Link>
                                </div>
                            </div>
                            <div v-else-if="getPortData(portNum).customer.status === 'terminated'" class="mt-1 p-1.5 rounded-lg bg-amber-50 border border-amber-200 text-[9px] text-amber-800 flex items-center justify-between">
                                <span>⛔ Stop Permanen (Perlu Cabut Port)</span>
                                <Link :href="route('cbp.index')" class="text-amber-900 font-bold underline">Buat CBP &rarr;</Link>
                            </div>
                        </div>
                        <span v-else class="font-medium text-gray-400 italic">ONT Inventori</span>
                    </template>
                    <template v-else>
                        <div class="w-2 h-2 rounded-full shrink-0 mt-1 bg-gray-300"></div>
                        <span class="font-medium text-emerald-600/70 italic text-xs">Kosong / Available</span>
                    </template>
                </div>
            </div>

            <!-- Pending Installations -->
            <div v-if="pendingInstallations.length > 0" class="mt-2 pt-3 border-t border-gray-100 mb-3">
                <h5 class="text-[11px] font-bold text-gray-800 mb-2">Antrean Instalasi (Menunggu Kabel & Port)</h5>
                <div class="space-y-1">
                    <div v-for="cust in pendingInstallations" :key="cust.id" class="flex items-start gap-1.5 text-[10px]">
                        <div class="w-1.5 h-1.5 rounded-full bg-cyan-500 shrink-0 mt-1"></div>
                        <span class="text-gray-600">
                            <span class="font-medium text-gray-800">{{ cust.customer_code }}</span> - {{ cust.name }}
                            <span v-if="cust.service_status === 'gratis'" class="inline-flex items-center gap-0.5 px-1 py-0.2 ml-1 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                🎁 Gratis
                            </span>
                            <span class="text-gray-400">[{{ cust.status === 'installing' ? 'Ready Instalasi' : 'Proses Survey' }}]</span>
                        </span>
                    </div>
                </div>
            </div>

            <button @click="$emit('open-photo', odp)" class="mt-auto w-full text-center bg-indigo-50 text-indigo-600 hover:bg-indigo-100 text-xs font-bold py-2 rounded-lg transition-colors flex items-center justify-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Lihat Foto
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    odp: { type: Object, required: true },
    splitInfo: { type: Object, default: () => ({ isSplit: false }) },
    ponVlans: { type: Array, default: () => [] },
    cbpRequests: { type: Array, default: () => [] },
});

defineEmits(['open-photo']);

const getOdpStatusColor = (odp) => {
    if (odp.status === 'full') return 'bg-red-500';
    if (odp.status === 'maintenance') return 'bg-amber-500';
    return 'bg-emerald-500';
};

const getOdpStatusBadge = (odp) => {
    if (odp.status === 'full') return 'bg-red-100 text-red-700';
    if (odp.status === 'maintenance') return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
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

const getCbpForCustomer = (custOrId) => {
    if (!custOrId) return null;
    const customerId = typeof custOrId === 'object' ? custOrId.id : custOrId;
    if (typeof custOrId === 'object' && custOrId.latest_cbp_request && ['pending', 'assigned'].includes(custOrId.latest_cbp_request.status)) {
        return custOrId.latest_cbp_request;
    }
    if (props.cbpRequests) {
        return props.cbpRequests.find(cbp => cbp.customer_id === customerId && ['pending', 'assigned'].includes(cbp.status)) || null;
    }
    return null;
};

const odpCbpCount = computed(() => {
    if (!props.odp?.onts) return 0;
    let count = 0;
    props.odp.onts.forEach(ont => {
        if (ont.customer && getCbpForCustomer(ont.customer)) {
            count++;
        }
    });
    return count;
});

const odpGratisCount = computed(() => {
    if (!props.odp?.onts) return 0;
    return props.odp.onts.filter(o => o.customer && o.customer.service_status === 'gratis').length;
});

const getCustomerStatusColor = (status, cbp) => {
    if (cbp) return 'bg-rose-500 animate-pulse ring-2 ring-rose-300';
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'suspended') return 'bg-orange-500';
    if (status === 'terminated') return 'bg-rose-600';
    if (status === 'installing' || status === 'booking' || status === 'survey') return 'bg-amber-500';
    return 'bg-gray-400';
};

const portDataMap = computed(() => {
    const map = new Map();
    const odp = props.odp;
    if (!odp) return map;

    if (Array.isArray(odp.onts)) {
        odp.onts.forEach(o => {
            if (o.port_number != null && !map.has(Number(o.port_number))) {
                map.set(Number(o.port_number), o);
            }
        });
    }

    if (Array.isArray(odp.surveys)) {
        const sorted = [...odp.surveys].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        sorted.forEach(s => {
            const pNum = Number(s.port_number);
            if (s.port_number != null && !map.has(pNum) && s.customer && ['survey', 'installing'].includes(s.customer.status)) {
                map.set(pNum, {
                    customer: s.customer,
                    is_reservation: true,
                    start_time: s.created_at,
                    updated_at: s.updated_at,
                });
            }
        });
    }

    return map;
});

const getPortData = (portNum) => portDataMap.value.get(Number(portNum)) || null;

const pendingInstallations = computed(() => {
    const odp = props.odp;
    if (!odp?.surveys) return [];

    const customersInPorts = new Set();
    if (odp.onts) {
        odp.onts.forEach(ont => {
            if (ont.port_number && ont.customer_id) customersInPorts.add(ont.customer_id);
        });
    }
    odp.surveys.forEach(survey => {
        if (survey.port_number && survey.customer_id) customersInPorts.add(survey.customer_id);
    });

    const customers = [];
    const seen = new Set();
    const sortedSurveys = [...odp.surveys].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    for (const survey of sortedSurveys) {
        if (!survey.customer) continue;
        if (customersInPorts.has(survey.customer.id)) continue;
        if (['survey', 'installing'].includes(survey.customer.status) && !seen.has(survey.customer.id)) {
            seen.add(survey.customer.id);
            customers.push(survey.customer);
        }
    }

    return customers;
});
</script>
