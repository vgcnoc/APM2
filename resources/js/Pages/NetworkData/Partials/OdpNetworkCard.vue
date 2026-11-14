<template>
    <div
        class="min-w-0 rounded-2xl transition-all group bg-white flex flex-col relative overflow-hidden shadow-xs hover:shadow-md"
        :class="[
            splitInfo?.role === 'induk'
                ? 'border-2 border-emerald-300 hover:border-emerald-400'
                : splitInfo?.role === 'lanjutan'
                    ? 'border-2 border-purple-300 hover:border-purple-400'
                    : splitInfo?.isSplit
                        ? 'border-2 border-amber-300 hover:border-amber-400'
                        : 'border border-slate-200/90 hover:border-indigo-300'
        ]"
    >
        <!-- Top Status Stripe -->
        <div class="w-full h-1.5" :class="getOdpStatusColor(odp)"></div>

        <!-- Split Relationship Banner (if Split) -->
        <div
            v-if="splitInfo?.isSplit"
            class="px-4 py-2 border-b text-[10px] flex flex-col gap-1"
            :class="[
                splitInfo.role === 'induk'
                    ? 'bg-gradient-to-r from-emerald-50 via-teal-50/70 to-white border-emerald-200/80 text-emerald-950'
                    : splitInfo.role === 'lanjutan'
                        ? 'bg-gradient-to-r from-purple-50 via-fuchsia-50/70 to-white border-purple-200/80 text-purple-950'
                        : 'bg-gradient-to-r from-amber-50 to-white border-amber-200/80 text-amber-950'
            ]"
        >
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider text-[9px] shadow-2xs"
                    :class="[
                        splitInfo.role === 'induk'
                            ? 'bg-emerald-600 text-white'
                            : splitInfo.role === 'lanjutan'
                                ? 'bg-purple-600 text-white'
                                : 'bg-amber-500 text-white'
                    ]"
                >
                    <span v-if="splitInfo.role === 'induk'">🟢 SPLIT 2 • INDUK</span>
                    <span v-else-if="splitInfo.role === 'lanjutan'">🟣 SPLIT 2 • LANJUTAN</span>
                    <span v-else>⚡ ODP SPLIT 2</span>
                </span>

                <span
                    v-if="splitInfo.partnerLabel"
                    class="flex items-center gap-1 font-bold text-[10px] px-2 py-0.5 rounded-md border bg-white/90"
                    :class="splitInfo.role === 'induk' ? 'border-emerald-200 text-emerald-800' : 'border-purple-200 text-purple-800'"
                    :title="splitInfo.partnerLabel"
                >
                    <span>{{ splitInfo.role === 'induk' ? '➔ Ke:' : '↳ Dari:' }}</span>
                    <strong class="truncate max-w-[140px]">{{ splitInfo.partnerLabel }}</strong>
                </span>
            </div>

            <div
                v-if="odp.start_point || odp.end_point || odp.cable_pull"
                class="flex items-center gap-1.5 text-[9px] font-medium opacity-85 truncate mt-0.5"
            >
                <svg class="w-3 h-3 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                <span class="truncate">
                    Rute: <strong>{{ odp.start_point || splitInfo.partnerLabel || 'Sumber' }}</strong> &rarr; <strong>{{ odp.end_point || odp.name }}</strong>
                </span>
                <span v-if="odp.cable_pull" class="ml-auto px-1.5 py-0.2 rounded bg-white/80 border border-current/20 font-bold shrink-0 font-mono">
                    {{ odp.cable_pull }}
                </span>
            </div>
        </div>

        <!-- Main Card Body -->
        <div class="p-4 md:p-5 flex-1 flex flex-col">
            <!-- Header: Name, Code, and Status Badge -->
            <div class="flex justify-between items-start gap-2 mb-3">
                <div class="min-w-0">
                    <h4 class="font-extrabold text-slate-900 text-lg leading-tight break-words group-hover:text-indigo-600 transition-colors">
                        {{ odp.name }}
                    </h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[11px] font-mono font-bold text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded">
                            {{ odp.kode_odp || '-' }}
                        </span>
                        <span class="text-[11px] text-slate-500 font-medium">
                            Area: <strong class="text-slate-700">{{ odp.area?.name || 'Semua Area' }}</strong>
                        </span>
                    </div>
                </div>

                <div class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shrink-0 shadow-2xs border" :class="getOdpStatusBadge(odp)">
                    {{ odp.status }}
                </div>
            </div>

            <!-- Capacity & Port Utilization Meter -->
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-200/70 mb-3.5">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-slate-600 font-bold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Okupansi Port:
                        <strong class="text-slate-900">{{ odp.used_ports }} / {{ odp.total_ports }}</strong>
                    </span>
                    <span class="font-mono font-bold text-xs" :class="utilizationColorText">
                        {{ utilizationPercent }}%
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden flex">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="utilizationColorBar"
                        :style="{ width: `${utilizationPercent}%` }"
                    ></div>
                </div>

                <div class="flex items-center justify-between text-[11px] mt-2 pt-1.5 border-t border-slate-200/60 font-medium">
                    <span class="text-emerald-700 font-bold flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        {{ availablePortsCount }} Port Siap Pakai
                    </span>
                    <span class="text-slate-500 font-mono text-[10px]">
                        Total {{ odp.total_ports }} Port Fisik
                    </span>
                </div>
            </div>

            <!-- Hardware Socket Matrix Bar (Visual LED Matrix at a Glance) -->
            <div class="mb-3.5 pb-3 border-b border-slate-100">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 flex items-center gap-1">
                        <span>⚡ Status Socket Port</span>
                    </span>
                    <span class="text-[9px] text-slate-400 font-medium">Klik socket untuk melihat</span>
                </div>
                <div class="flex items-center gap-1 flex-wrap">
                    <div
                        v-for="portNum in Number(odp.total_ports || 0)"
                        :key="'matrix-' + portNum"
                        class="w-7 h-7 rounded-lg flex flex-col items-center justify-center border transition-all text-center select-none"
                        :class="getSocketBoxClass(portNum)"
                        :title="getSocketTooltip(portNum)"
                    >
                        <span class="text-[9px] font-mono font-bold leading-none">{{ portNum }}</span>
                        <span class="w-1.5 h-1.5 rounded-full mt-0.5" :class="getSocketLedClass(portNum)"></span>
                    </div>
                </div>
            </div>

            <!-- Address & Location Row -->
            <div class="flex flex-wrap items-center gap-1.5 mb-3 text-xs">
                <div v-if="odp.address && odp.address !== '-'" class="bg-slate-50 border border-slate-200 text-slate-700 text-[10px] px-2 py-1 rounded-md flex items-center gap-1 max-w-full" :title="odp.address">
                    <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="truncate max-w-[180px]">{{ odp.address }}</span>
                </div>

                <a
                    v-if="odp.latitude && odp.longitude"
                    :href="`https://www.google.com/maps?q=${odp.latitude},${odp.longitude}`"
                    target="_blank"
                    class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-2 py-1 rounded-md flex items-center gap-1 transition-colors"
                    title="Buka Koordinat di Google Maps"
                >
                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Maps
                </a>

                <div v-if="odpCbpCount > 0" class="bg-rose-50 border border-rose-300 text-rose-700 font-black text-[10px] px-2 py-1 rounded-md flex items-center gap-1 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    ⏳ {{ odpCbpCount }} Port CBP
                </div>

                <div v-if="odpGratisCount > 0" class="bg-emerald-50 border border-emerald-300 text-emerald-800 font-black text-[10px] px-2 py-1 rounded-md flex items-center gap-1 shadow-2xs" title="Terdapat pelanggan gratis pada ODP ini">
                    <span>🎁 {{ odpGratisCount }} Gratis</span>
                </div>
            </div>

            <!-- VLAN Sinkron OLT PON Header Banner -->
            <div class="mb-3.5 p-2.5 bg-gradient-to-r from-indigo-50/80 via-purple-50/40 to-indigo-50/80 rounded-xl border border-indigo-100">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-extrabold text-indigo-950 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        VLAN Sinkron OLT
                    </span>
                    <span v-if="odp.odc?.pon_port" class="text-[9px] font-bold text-indigo-700 bg-white border border-indigo-200 px-2 py-0.5 rounded-full shadow-2xs">
                        PON {{ odp.odc.pon_port }}
                    </span>
                </div>

                <div v-if="ponVlans.length > 0" class="flex flex-wrap gap-1.5">
                    <div
                        v-for="(vlan, vIdx) in ponVlans"
                        :key="vIdx"
                        class="inline-flex items-center gap-1 bg-white border border-indigo-200/80 px-2 py-0.5 rounded-md text-[10px] shadow-2xs"
                    >
                        <span class="font-bold text-indigo-800">VLAN {{ vlan.vlan_id || vlan }}</span>
                        <span v-if="vlan.name && vlan.name !== (vlan.vlan_id || vlan)" class="text-slate-500 text-[9px]">({{ vlan.name }})</span>
                        <span :class="['px-1.5 py-0.2 text-[8px] font-bold rounded-full uppercase', getModeBadgeClass(getVlanAccessMode(vlan))]">
                            {{ getVlanAccessMode(vlan) }}
                        </span>
                    </div>
                </div>
                <span v-else class="text-[10px] text-slate-400 italic">Tidak ada VLAN diset pada OLT</span>
            </div>

            <!-- Ports List: Clean, Unrestricted for <=8 Ports, Custom Scroll for >8 Ports -->
            <div
                class="space-y-1.5 mb-4 flex-1 text-xs"
                :class="Number(odp.total_ports || 0) > 8 ? 'max-h-[380px] overflow-y-auto pr-1 custom-scrollbar' : 'max-h-none'"
            >
                <div
                    v-for="portNum in Number(odp.total_ports || 0)"
                    :key="portNum"
                    :id="`odp-${odp.id}-port-${portNum}`"
                    class="transition-colors"
                >
                    <!-- Port Row Item -->
                    <template v-if="getPortData(portNum)">
                        <div class="flex flex-col py-2 px-2.5 rounded-xl bg-slate-50 border border-slate-200/90 hover:bg-white hover:border-indigo-300 hover:shadow-xs transition-all">
                            <!-- Upper line: Port number, status pill, customer info -->
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="w-6 h-6 rounded-md bg-slate-200 text-slate-800 text-[10px] font-mono font-black flex items-center justify-center shrink-0 border border-slate-300">
                                        {{ String(portNum).padStart(2, '0') }}
                                    </span>

                                    <!-- Customer status badge -->
                                    <span
                                        v-if="getCbpForCustomer(getPortData(portNum).customer)"
                                        class="text-rose-700 font-black text-[9px] bg-rose-100 px-2 py-0.5 rounded-full border border-rose-300 uppercase tracking-wider shrink-0 flex items-center gap-1 shadow-2xs"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        CBP
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 border"
                                        :class="getCustomerStatusPillClass(getPortData(portNum).customer?.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getCustomerStatusDot(getPortData(portNum).customer?.status)"></span>
                                        {{ getCustomerStatusLabel(getPortData(portNum).customer?.status) }}
                                    </span>

                                    <!-- Customer Name & Code Link -->
                                    <div v-if="getPortData(portNum).customer" class="min-w-0 flex-1 truncate">
                                        <a
                                            :href="`/customers/${getPortData(portNum).customer.id}`"
                                            class="font-black text-slate-900 hover:text-indigo-600 hover:underline text-xs"
                                        >
                                            {{ getPortData(portNum).customer.customer_code }} - {{ getPortData(portNum).customer.name }}
                                        </a>
                                    </div>
                                    <span v-else class="font-medium text-slate-400 italic text-xs">ONT Inventori</span>
                                </div>

                                <!-- Gratis Pill if Applicable -->
                                <span
                                    v-if="getPortData(portNum).customer?.service_status === 'gratis'"
                                    class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs"
                                >
                                    🎁 Gratis
                                </span>
                            </div>

                            <!-- Lower line: Date & VLAN & Device info -->
                            <div class="flex items-center gap-2 mt-1.5 pl-8 flex-wrap text-[10px] text-slate-500">
                                <span v-if="getPortData(portNum).customer?.activation_date">
                                    Aktif: <strong>{{ getPortData(portNum).customer.activation_date }}</strong>
                                </span>
                                <span v-else-if="getPortData(portNum).customer?.created_at">
                                    Tgl: <strong>{{ getPortData(portNum).customer.created_at.substring(0, 10) }}</strong>
                                </span>

                                <span v-if="getPortData(portNum).vlan_id" class="bg-purple-50 text-purple-700 px-1.5 py-0.2 rounded font-mono font-bold border border-purple-200">
                                    VLAN {{ getPortData(portNum).vlan_id }}
                                </span>
                                <span v-if="getPortData(portNum).hotspot_vlan_id" class="bg-amber-50 text-amber-800 px-1.5 py-0.2 rounded font-mono font-bold border border-amber-200">
                                    HS {{ getPortData(portNum).hotspot_vlan_id }}
                                </span>

                                <span v-if="getPortData(portNum).serial_number" class="bg-slate-100 text-slate-700 px-1.5 py-0.2 rounded font-mono font-semibold text-[9px] border border-slate-200" :title="'SN ONT: ' + getPortData(portNum).serial_number">
                                    SN: {{ getPortData(portNum).serial_number }}
                                </span>
                                <span v-if="getPortData(portNum).model" class="text-slate-500 font-semibold text-[9px]">
                                    ({{ getPortData(portNum).model }})
                                </span>
                            </div>

                            <!-- CBP Alert Banner (if CBP Active) -->
                            <div v-if="getCbpForCustomer(getPortData(portNum).customer)" class="mt-2 ml-8 p-2 rounded-xl bg-rose-50 border border-rose-200 text-[10px] space-y-1">
                                <div class="flex items-center justify-between text-rose-900 font-black">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Cabut Perangkat (CBP)
                                    </span>
                                    <span class="font-mono bg-white px-1.5 py-0.2 rounded border border-rose-200 text-rose-800 font-bold">
                                        {{ getCbpForCustomer(getPortData(portNum).customer).cbp_number }}
                                    </span>
                                </div>
                                <div class="text-[9px] text-slate-700 flex items-center justify-between flex-wrap gap-1">
                                    <span>Status: <strong>{{ getCbpForCustomer(getPortData(portNum).customer).status === 'assigned' ? '🔧 Teknisi Ditugaskan' : '⏳ Menunggu Jadwal' }}</strong></span>
                                    <Link :href="route('cbp.jadwal')" class="text-rose-700 hover:text-rose-900 font-bold underline">Kelola Jadwal &rarr;</Link>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Available / Free Port -->
                    <template v-else>
                        <div class="flex items-center justify-between py-1.5 px-2.5 rounded-xl bg-emerald-50/50 border border-emerald-100 hover:bg-emerald-50 hover:border-emerald-300 transition-colors">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-mono font-black flex items-center justify-center border border-emerald-200">
                                    {{ String(portNum).padStart(2, '0') }}
                                </span>
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span class="text-xs font-semibold text-emerald-900">Port Kosong / Siap Pakai</span>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-emerald-600 bg-emerald-100/70 px-2 py-0.5 rounded-full">
                                AVAILABLE
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Pending Installations (Queue for this ODP) -->
            <div v-if="pendingInstallations.length > 0" class="mt-auto pt-2.5 border-t border-slate-100 mb-3">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1">
                        <span>⏳ Antrean Instalasi:</span>
                    </span>
                    <span class="text-[9px] font-mono font-bold text-cyan-700 bg-cyan-50 px-1.5 py-0.2 rounded border border-cyan-200">
                        {{ pendingInstallations.length }} Pelanggan
                    </span>
                </div>
                <div class="space-y-1">
                    <div
                        v-for="cust in pendingInstallations"
                        :key="cust.id"
                        class="flex items-center justify-between gap-1 text-[10px] bg-slate-50 p-1.5 rounded-lg border border-slate-200/60"
                    >
                        <span class="text-slate-700 truncate font-semibold">
                            <span class="text-slate-900">{{ cust.customer_code }}</span> - {{ cust.name }}
                        </span>
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 shrink-0">
                            {{ cust.status === 'installing' ? 'Ready Pasang' : 'Survey' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bottom Button: Lihat Foto ODP -->
            <button
                @click="$emit('open-photo', odp)"
                class="mt-auto w-full text-center bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 text-xs font-bold py-2.5 rounded-xl transition-all flex items-center justify-center gap-2 shadow-2xs hover:shadow-xs active:scale-[0.99]"
            >
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ odp.photo ? 'Lihat Foto ODP (Tersedia)' : 'Lihat Foto ODP' }}</span>
                <span v-if="odp.photo" class="w-2 h-2 rounded-full bg-emerald-500"></span>
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

const totalPortsCount = computed(() => Number(props.odp?.total_ports || 0));
const usedPortsCount = computed(() => Number(props.odp?.used_ports || 0));
const availablePortsCount = computed(() => Math.max(0, totalPortsCount.value - usedPortsCount.value));

const utilizationPercent = computed(() => {
    if (totalPortsCount.value === 0) return 0;
    return Math.min(100, Math.round((usedPortsCount.value / totalPortsCount.value) * 100));
});

const utilizationColorBar = computed(() => {
    const p = utilizationPercent.value;
    if (p >= 90) return 'bg-rose-500';
    if (p >= 75) return 'bg-amber-500';
    return 'bg-emerald-500';
});

const utilizationColorText = computed(() => {
    const p = utilizationPercent.value;
    if (p >= 90) return 'text-rose-600';
    if (p >= 75) return 'text-amber-600';
    return 'text-emerald-600';
});

const getOdpStatusColor = (odp) => {
    if (odp?.status === 'full') return 'bg-rose-500';
    if (odp?.status === 'maintenance') return 'bg-amber-500';
    return 'bg-emerald-500';
};

const getOdpStatusBadge = (odp) => {
    if (odp?.status === 'full') return 'bg-rose-100 text-rose-800 border-rose-200';
    if (odp?.status === 'maintenance') return 'bg-amber-100 text-amber-800 border-amber-200';
    return 'bg-emerald-100 text-emerald-800 border-emerald-200';
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

const getCustomerStatusLabel = (status) => {
    if (status === 'active') return 'Aktif';
    if (status === 'suspended') return 'Isolir';
    if (status === 'terminated') return 'Stop';
    if (status === 'installing') return 'Pasang';
    if (status === 'survey') return 'Survey';
    return status || 'Terpakai';
};

const getCustomerStatusDot = (status) => {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'suspended') return 'bg-orange-500';
    if (status === 'terminated') return 'bg-rose-500';
    return 'bg-amber-500';
};

const getCustomerStatusPillClass = (status) => {
    if (status === 'active') return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    if (status === 'suspended') return 'bg-orange-50 text-orange-800 border-orange-200';
    if (status === 'terminated') return 'bg-rose-50 text-rose-800 border-rose-200';
    return 'bg-amber-50 text-amber-800 border-amber-200';
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

const getSocketBoxClass = (portNum) => {
    const data = getPortData(portNum);
    if (!data) return 'bg-emerald-50/70 border-emerald-200 text-emerald-800 hover:bg-emerald-100';
    if (getCbpForCustomer(data.customer)) return 'bg-rose-100 border-rose-300 text-rose-800 animate-pulse';
    if (data.customer?.status === 'active') return 'bg-slate-100 border-indigo-200 text-indigo-900';
    if (data.customer?.status === 'suspended') return 'bg-orange-100 border-orange-300 text-orange-900';
    return 'bg-amber-100 border-amber-300 text-amber-900';
};

const getSocketLedClass = (portNum) => {
    const data = getPortData(portNum);
    if (!data) return 'bg-emerald-400';
    if (getCbpForCustomer(data.customer)) return 'bg-rose-500 animate-pulse';
    if (data.customer?.status === 'active') return 'bg-emerald-500';
    if (data.customer?.status === 'suspended') return 'bg-orange-500';
    return 'bg-amber-500';
};

const getSocketTooltip = (portNum) => {
    const data = getPortData(portNum);
    if (!data) return `Port ${portNum}: Tersedia / Siap Pakai`;
    const snInfo = data.serial_number ? ` | SN: ${data.serial_number}` : '';
    const phoneInfo = data.customer?.phone ? ` | HP: ${data.customer.phone}` : '';
    if (getCbpForCustomer(data.customer)) return `Port ${portNum}: ⏳ Proses CBP (${data.customer?.name}${snInfo})`;
    return `Port ${portNum}: ${data.customer?.status || 'Terpakai'} - ${data.customer?.name || 'Customer'}${snInfo}${phoneInfo}`;
};

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

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
