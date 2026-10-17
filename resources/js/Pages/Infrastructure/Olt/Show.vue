<template>
    <AppLayout :title="`Detail OLT: ${olt.name}`">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="'/olts'" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            {{ olt.name }}
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="olt.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                {{ olt.status === 'active' ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ olt.brand || 'Brand N/A' }} {{ olt.model ? `- ${olt.model}` : '' }} | {{ olt.ip_address || 'IP N/A' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="'/olts'" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Info Card -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Informasi Perangkat
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Hostname</p>
                                <p class="text-sm font-medium text-gray-900">{{ olt.hostname || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">IP Address / Domain</p>
                                <p class="text-sm font-mono font-medium text-gray-900">{{ olt.ip_address || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Lokasi</p>
                                <p class="text-sm font-medium text-gray-900">{{ olt.location || '-' }}</p>
                            </div>
                            <div v-if="olt.latitude || olt.longitude">
                                <p class="text-xs text-gray-500 mb-1">Titik Koordinat GPS</p>
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg font-mono text-xs">
                                        <span class="font-bold text-gray-500 uppercase">Lat:</span>
                                        <span class="text-gray-900 font-semibold">{{ olt.latitude || '-' }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg font-mono text-xs">
                                        <span class="font-bold text-gray-500 uppercase">Long:</span>
                                        <span class="text-gray-900 font-semibold">{{ olt.longitude || '-' }}</span>
                                    </span>
                                    <a v-if="olt.latitude && olt.longitude" :href="`https://maps.google.com/?q=${olt.latitude},${olt.longitude}`" target="_blank" class="text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1">
                                        <span>Buka di Google Maps</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Keterangan</p>
                                <p class="text-sm font-medium text-gray-900">{{ olt.description || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- VLAN Stats -->
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-sm text-white p-5 relative overflow-hidden">
                        <div class="absolute -right-6 -top-6 text-white/10">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M19.333 3.667h-14.666c-1.105 0-2 .895-2 2v12.666c0 1.105.895 2 2 2h14.666c1.105 0 2-.895 2-2v-12.666c0-1.105-.895-2-2-2zm-12.666 4.666h4.666v2h-4.666v-2zm-2 9.334v-2h14.666v2h-14.666zm14.666-4.667h-14.666v-2h14.666v2zm0-4.667h-6.666v-2h6.666v2z"/></svg>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-sm font-bold uppercase tracking-wider mb-2 opacity-90">Total Port PON</h3>
                            <div class="text-4xl font-black mb-4">{{ olt.total_pon_ports }}</div>
                            <div class="space-y-3 mt-4 max-h-96 overflow-y-auto pr-1">
                                <div v-for="pon in (olt.pon_vlans || [])" :key="pon.port" class="border-b border-white/20 pb-2">
                                    <div class="flex justify-between items-center text-sm font-semibold">
                                        <span>PON {{ pon.port }}</span>
                                        <span class="font-medium bg-white/20 px-2 py-0.5 rounded text-xs">{{ pon.vlans ? pon.vlans.length : 0 }} VLAN</span>
                                    </div>
                                    <div v-if="pon.vlans && pon.vlans.length > 0" class="flex flex-wrap gap-1 mt-1.5">
                                        <span v-for="(v, vi) in pon.vlans" :key="vi" class="px-2 py-0.5 bg-black/25 rounded text-[11px] font-mono flex items-center gap-1 border border-white/10">
                                            <span class="font-bold text-white">{{ v.vlan_id }}</span>
                                            <span class="opacity-80 font-sans text-[10px]">{{ v.name }}</span>
                                            <span class="bg-white/20 px-1 py-0.2 rounded text-[9px] uppercase font-bold">{{ getVlanAccessMode(v) }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hierarchy / ODC List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-100 p-5 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                ODC Terhubung ({{ olt.odcs ? olt.odcs.length : 0 }})
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                            <div v-if="!olt.odcs || olt.odcs.length === 0" class="p-8 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <p>Belum ada ODC yang terhubung ke OLT ini.</p>
                            </div>
                            <div v-for="odc in (olt.odcs || [])" :key="odc.id" class="p-5 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        </div>
                                        <div>
                                            <Link :href="'/odcs/' + odc.id" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition-colors">{{ odc.name }}</Link>
                                            <div class="text-xs text-gray-500 flex flex-wrap items-center gap-1.5 mt-1">
                                                <span v-if="odc.pon_port" class="bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded font-medium border border-indigo-100">PON {{ odc.pon_port }}</span>
                                                <div v-if="getPonVlans(olt, odc.pon_port).length > 0" class="flex flex-wrap gap-1">
                                                    <span v-for="(v, vi) in getPonVlans(olt, odc.pon_port)" :key="vi" class="px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                                        VLAN {{ v.vlan_id }} · {{ v.name }} ({{ v.access_mode }})
                                                    </span>
                                                </div>
                                                <span v-else-if="odc.pon_port" class="text-gray-400 italic text-[11px]">(Belum diset VLAN)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-bold text-gray-900">{{ odc.capacity }} Port</div>
                                        <div class="text-[10px] text-gray-500">{{ odc.odps ? odc.odps.length : 0 }} ODP Terhubung</div>
                                    </div>
                                </div>
                                <div v-if="odc.odps && odc.odps.length > 0" class="pl-12 mt-2 space-y-2">
                                    <div v-for="odp in odc.odps" :key="odp.id" class="flex flex-col sm:flex-row sm:items-center justify-between bg-white border border-gray-200 rounded-lg p-2.5 text-xs gap-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-purple-500"></div>
                                            <Link :href="'/odps/' + odp.id" class="font-bold text-gray-800 hover:text-purple-600">{{ odp.name }}</Link>
                                            <span class="text-gray-400">· {{ odp.total_ports }} Port</span>
                                        </div>
                                        <div v-if="getPonVlans(olt, odc.pon_port).length > 0" class="flex flex-wrap items-center gap-1 pl-3.5 sm:pl-0">
                                            <span class="text-[10px] text-gray-400 font-medium">Sinkron VLAN:</span>
                                            <span v-for="(v, vi) in getPonVlans(olt, odc.pon_port)" :key="vi" class="px-1.5 py-0.5 rounded text-[9px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                                {{ v.vlan_id }} ({{ v.access_mode }})
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
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    olt: Object,
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

function getPonVlans(olt, ponPort) {
    if (!olt || !ponPort || !olt.pon_vlans) return [];
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const ponData = vlans.find(p => p.port == ponPort);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
}
</script>
