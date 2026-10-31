<template>
    <AppLayout :title="`Detail ODC: ${odc.name}`">
        <div class="p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <Link href="/odcs" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center flex-wrap gap-2">
                            <span>{{ odc.name }}</span>
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="odc.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                {{ odc.status === 'active' ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                            <span
                                v-if="odcSplitRole === 'induk'"
                                class="px-2.5 py-1 text-xs font-black rounded-full bg-emerald-600 text-white shadow-2xs"
                            >
                                🟢 ODC SPLIT • UNIT INDUK
                            </span>
                            <span
                                v-else-if="odcSplitRole === 'lanjutan'"
                                class="px-2.5 py-1 text-xs font-black rounded-full bg-purple-600 text-white shadow-2xs"
                            >
                                🟣 ODC SPLIT • UNIT LANJUTAN
                            </span>
                            <span
                                v-else-if="odcSplitRole === 'split'"
                                class="px-2.5 py-1 text-xs font-black rounded-full bg-amber-500 text-white shadow-2xs"
                            >
                                ⚡ ODC TIPE SPLIT
                            </span>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Tipe: {{ odc.type }} | Kapasitas: {{ odc.capacity }} Port <span v-if="odc.splitter_ratio">| Rasio Splitter: {{ odc.splitter_ratio }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/odcs" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Info Card -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Relasi Pasangan ODC Split -->
                    <div
                        v-if="odcSplitRole"
                        class="rounded-xl shadow-sm border p-5"
                        :class="odcSplitRole === 'induk' ? 'bg-emerald-50/70 border-emerald-200' : odcSplitRole === 'lanjutan' ? 'bg-purple-50/70 border-purple-200' : 'bg-amber-50/70 border-amber-200'"
                    >
                        <h3
                            class="text-sm font-bold uppercase tracking-wider mb-3 border-b pb-2.5 flex items-center gap-2"
                            :class="odcSplitRole === 'induk' ? 'text-emerald-900 border-emerald-200/80' : odcSplitRole === 'lanjutan' ? 'text-purple-900 border-purple-200/80' : 'text-amber-900 border-amber-200/80'"
                        >
                            <span>🔗 Relasi Pasangan ODC Split</span>
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5">Peran Unit Saat Ini</p>
                                <p class="font-black" :class="odcSplitRole === 'induk' ? 'text-emerald-800' : odcSplitRole === 'lanjutan' ? 'text-purple-800' : 'text-amber-800'">
                                    {{ odcSplitRole === 'induk' ? '🟢 Unit Induk (Sumber Split)' : odcSplitRole === 'lanjutan' ? '🟣 Unit Lanjutan (Hasil Split)' : '⚡ Tipe Split (Belum Ditautkan)' }}
                                </p>
                            </div>

                            <div v-if="odc.parent">
                                <p class="text-xs text-gray-500 mb-1">Terhubung dari ODC Induk</p>
                                <Link
                                    :href="`/odcs/${odc.parent.id}`"
                                    class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-purple-200 hover:border-purple-400 transition-colors shadow-2xs"
                                >
                                    <div>
                                        <div class="font-bold text-purple-900 text-sm">🟢 {{ odc.parent.name }}</div>
                                        <div class="text-[11px] text-gray-500">Kapasitas: {{ odc.parent.capacity }} Port</div>
                                    </div>
                                    <span class="text-xs font-bold text-purple-600">Buka Induk &rarr;</span>
                                </Link>
                            </div>

                            <div v-if="odc.children && odc.children.length > 0">
                                <p class="text-xs text-gray-500 mb-1">Terhubung ke ODC Lanjutan ({{ odc.children.length }})</p>
                                <div class="space-y-2">
                                    <Link
                                        v-for="child in odc.children"
                                        :key="child.id"
                                        :href="`/odcs/${child.id}`"
                                        class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-emerald-200 hover:border-emerald-400 transition-colors shadow-2xs"
                                    >
                                        <div>
                                            <div class="font-bold text-emerald-900 text-sm">🟣 {{ child.name }}</div>
                                            <div class="text-[11px] text-gray-500">Kapasitas: {{ child.capacity }} Port • {{ child.odps ? child.odps.length : 0 }} ODP</div>
                                        </div>
                                        <span class="text-xs font-bold text-emerald-600">Buka Lanjutan &rarr;</span>
                                    </Link>
                                </div>
                            </div>

                            <div v-if="odc.start_point || odc.end_point || odc.cable_pull" class="pt-2 border-t border-black/5 text-xs text-gray-700 space-y-1">
                                <div v-if="odc.start_point || odc.end_point">
                                    <span class="text-gray-500">Jalur Tarikan:</span>
                                    <strong class="ml-1">{{ odc.start_point || '-' }} &rarr; {{ odc.end_point || odc.name }}</strong>
                                </div>
                                <div v-if="odc.cable_pull">
                                    <span class="text-gray-500">Metode Penarikan:</span>
                                    <strong class="ml-1">{{ odc.cable_pull }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Induk OLT -->
                    <div class="bg-indigo-50 rounded-xl shadow-sm border border-indigo-100 p-5">
                        <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wider mb-4 border-b border-indigo-100 pb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                            Terhubung Ke
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-indigo-500 mb-1">Induk OLT</p>
                                <Link v-if="odc.olt" :href="`/olts/${odc.olt.id}`" class="text-sm font-bold text-indigo-700 hover:underline">
                                    {{ odc.olt.name }}
                                </Link>
                                <span v-else class="text-sm font-medium text-gray-500">-</span>
                            </div>
                            <div v-if="odc.pon_port">
                                <p class="text-xs text-indigo-500 mb-1">Port PON</p>
                                <div class="text-sm font-bold text-indigo-700">
                                    PON {{ odc.pon_port }}
                                </div>
                                <div class="mt-2 pt-2 border-t border-purple-100">
                                    <p class="text-[11px] font-bold text-indigo-900 uppercase tracking-wider mb-1.5">VLAN & Mode Akses dari OLT:</p>
                                    <div v-if="getPonVlans(odc).length > 0" class="space-y-1.5">
                                        <div v-for="(v, vi) in getPonVlans(odc)" :key="vi" class="p-2 rounded-lg bg-white border border-indigo-100 flex items-center justify-between shadow-2xs">
                                            <div>
                                                <span class="font-mono font-bold text-xs text-gray-900">VLAN {{ v.vlan_id }}</span>
                                                <span class="text-xs text-gray-600 ml-1.5">· {{ v.name }}</span>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="getModeBadgeClass(v.access_mode)">
                                                {{ v.access_mode }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Belum ada VLAN diset di OLT untuk PON {{ odc.pon_port }}.
                                    </div>
                                </div>
                            </div>
                            <div v-if="odc.splitter_ratio">
                                <p class="text-xs text-indigo-500 mb-1">Rasio Splitter ODC</p>
                                <div class="text-sm font-bold text-purple-700">
                                    Splitter {{ odc.splitter_ratio }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi ODC -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Informasi ODC
                        </h3>
                        <div class="space-y-4">
                            <div v-if="odc.photo">
                                <img :src="`/storage/${odc.photo}`" alt="Foto ODC" class="w-full h-48 object-cover rounded-lg border border-gray-200" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Alamat/Lokasi</p>
                                <p class="text-sm font-medium text-gray-900">{{ odc.location || '-' }}</p>
                            </div>
                            <div v-if="odc.latitude || odc.longitude">
                                <p class="text-xs text-gray-500 mb-1">Titik Koordinat GPS</p>
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg font-mono text-xs">
                                        <span class="font-bold text-gray-500 uppercase">Lat:</span>
                                        <span class="text-gray-900 font-semibold">{{ odc.latitude || '-' }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg font-mono text-xs">
                                        <span class="font-bold text-gray-500 uppercase">Long:</span>
                                        <span class="text-gray-900 font-semibold">{{ odc.longitude || '-' }}</span>
                                    </span>
                                    <a v-if="odc.latitude && odc.longitude" :href="`https://maps.google.com/?q=${odc.latitude},${odc.longitude}`" target="_blank" class="text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1">
                                        <span>Buka di Google Maps</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div v-if="odc.type === 'Split'">
                                <p class="text-xs text-gray-500 mb-1">Penarikan Kabel</p>
                                <p class="text-sm font-medium text-gray-900">{{ odc.cable_pull || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Keterangan</p>
                                <p class="text-sm font-medium text-gray-900">{{ odc.description || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hierarchy / ODP List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-100 p-5 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                ODP Terhubung ({{ odc.odps ? odc.odps.length : 0 }})
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-[800px] overflow-y-auto">
                            <div v-if="!odc.odps || odc.odps.length === 0" class="p-8 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p>Belum ada ODP yang terhubung ke ODC ini.</p>
                            </div>
                            <div
                                v-for="odp in (odc.odps || [])"
                                :key="odp.id"
                                class="p-5 transition-colors"
                                :class="[
                                    odp.parent_odp_id
                                        ? 'bg-purple-50/35 hover:bg-purple-50/60 border-l-4 border-l-purple-500'
                                        : (odp.children && odp.children.length > 0)
                                            ? 'bg-emerald-50/35 hover:bg-emerald-50/60 border-l-4 border-l-emerald-500'
                                            : 'hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-3 gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600 shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center flex-wrap gap-2">
                                                <span v-if="odp.parent_odp_id" class="text-purple-600 font-black">↳</span>
                                                <Link :href="`/odps/${odp.id}`" class="text-sm font-bold text-gray-900 hover:text-purple-600 transition-colors">{{ odp.name }}</Link>
                                                <span v-if="odp.children && odp.children.length > 0" class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-600 text-white">
                                                    🟢 SPLIT 2 • INDUK (↱ {{ odp.children.map(c => c.name).join(', ') }})
                                                </span>
                                                <span v-else-if="odp.parent" class="px-2 py-0.5 rounded text-[10px] font-black bg-purple-600 text-white">
                                                    🟣 SPLIT 2 • LANJUTAN (↳ {{ odp.parent.name }})
                                                </span>
                                                <span v-else-if="odp.type === 'Split 2' || odp.is_split" class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-500 text-white">
                                                    ⚡ SPLIT 2
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                {{ odp.address || 'Alamat tidak tersedia' }}
                                            </div>
                                            <div v-if="getPonVlans(odc).length > 0" class="flex flex-wrap items-center gap-1 mt-1.5">
                                                <span class="text-[10px] text-gray-400 font-medium">Sinkron VLAN:</span>
                                                <span v-for="(v, vi) in getPonVlans(odc)" :key="vi" class="px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                                    {{ v.vlan_id }} · {{ v.name }} ({{ v.access_mode }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-xs font-bold text-gray-900">{{ odp.total_ports }} Port</div>
                                        <div class="text-[10px] text-gray-500">{{ odp.onts ? odp.onts.length : 0 }} Pelanggan Aktif</div>
                                    </div>
                                </div>
                                <div v-if="odp.onts && odp.onts.length > 0" class="pl-12 mt-2 space-y-2 border-l-2 border-purple-100 ml-5 py-1">
                                    <div v-for="ont in odp.onts" :key="ont.id" class="flex items-center justify-between bg-white border border-gray-100 rounded p-2 text-xs hover:bg-gray-50">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                            <span class="font-medium text-gray-700">
                                                {{ ont.customer ? ont.customer.name : 'Pelanggan N/A' }}
                                                <span class="text-gray-400">({{ ont.sn_mac }})</span>
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
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odc: Object,
});

const odcSplitRole = computed(() => {
    const o = props.odc;
    if (!o) return null;
    if (o.parent_odc_id || o.parent) return 'lanjutan';
    if (o.children && o.children.length > 0) return 'induk';
    if (o.is_split || o.type === 'Split') return 'split';
    return null;
});

function getVlanAccessMode(vlan) {
    if (vlan.access_mode) return vlan.access_mode;
    const name = (vlan.name || '').toLowerCase();
    if (name.includes('pppoe')) return 'PPPOE';
    if (name.includes('hotspot')) return 'Hotspot';
    if (name.includes('static')) return 'Static';
    if (name.includes('dhcp')) return 'DHCP';
    if (name.includes('bridge')) return 'Bridge';
    if (name.includes('trunk') || name.includes('tagged')) return 'Tagged';
    if (name.includes('access') || name.includes('untagged')) return 'Untagged';
    return 'PPPOE';
}

function getModeBadgeClass(mode) {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'HOTSPOT':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'STATIC':
        case 'STATIC IP':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'DHCP':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'BRIDGE':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'TAGGED':
        case 'TRUNK':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'UNTAGGED':
        case 'ACCESS':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        default:
            return 'bg-blue-50 text-blue-700 border-blue-200';
    }
}

function getPonVlans(odc) {
    if (!odc || !odc.olt || !odc.pon_port || !odc.olt.pon_vlans) return [];
    let vlans = odc.olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];

    const ponData = vlans.find(p => p.port == odc.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
}
</script>
