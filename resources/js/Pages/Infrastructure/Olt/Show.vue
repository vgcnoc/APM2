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
                                <p class="text-xs text-gray-500 mb-1">Lokasi</p>
                                <p class="text-sm font-medium text-gray-900">{{ olt.location || '-' }}</p>
                            </div>
                            <div v-if="olt.latitude && olt.longitude">
                                <p class="text-xs text-gray-500 mb-1">Koordinat GPS</p>
                                <a :href="`https://maps.google.com/?q=${olt.latitude},${olt.longitude}`" target="_blank" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1">
                                    {{ olt.latitude }}, {{ olt.longitude }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
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
                            <div class="space-y-2 mt-4">
                                <div v-for="pon in (olt.pon_vlans || [])" :key="pon.port" class="flex justify-between items-center text-sm border-b border-white/20 pb-1">
                                    <span>PON {{ pon.port }}</span>
                                    <span class="font-medium bg-white/20 px-2 py-0.5 rounded text-xs">{{ pon.vlans ? pon.vlans.length : 0 }} VLAN</span>
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
                                            <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                                <span v-if="odc.pon_port" class="bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded font-medium">PON {{ odc.pon_port }}</span>
                                                <span v-if="getPonVlansText(olt, odc.pon_port)" class="text-gray-400">[{{ getPonVlansText(olt, odc.pon_port) }}]</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-bold text-gray-900">{{ odc.capacity }} Port</div>
                                        <div class="text-[10px] text-gray-500">{{ odc.odps ? odc.odps.length : 0 }} ODP Terhubung</div>
                                    </div>
                                </div>
                                <div v-if="odc.odps && odc.odps.length > 0" class="pl-12 mt-2 space-y-2">
                                    <div v-for="odp in odc.odps" :key="odp.id" class="flex items-center justify-between bg-white border border-gray-200 rounded p-2 text-xs">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-purple-500"></div>
                                            <Link :href="'/odps/' + odp.id" class="font-medium text-gray-700 hover:text-purple-600">{{ odp.name }}</Link>
                                        </div>
                                        <div class="text-gray-500">{{ odp.total_ports }} Port</div>
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

function getPonVlansText(olt, ponPort) {
    if (!olt || !ponPort || !olt.pon_vlans) return '';
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return ''; }
    }
    if (!Array.isArray(vlans)) return '';
    
    const ponData = vlans.find(p => p.port == ponPort);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans) || ponData.vlans.length === 0) return '';
    return ponData.vlans.map(v => `${v.name} (${v.vlan_id})`).join(', ');
}
</script>
