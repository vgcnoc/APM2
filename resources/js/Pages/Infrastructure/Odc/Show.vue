<template>
    <AppLayout :title="`Detail ODC: ${odc.name}`">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <Link href="/odcs" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            {{ odc.name }}
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="odc.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                {{ odc.status === 'active' ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Tipe: {{ odc.type }} | Kapasitas: {{ odc.capacity }} Port
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
                                <p class="text-sm font-bold text-indigo-700 flex items-center gap-2">
                                    PON {{ odc.pon_port }}
                                    <span v-if="getPonVlansText(odc)" class="text-xs font-normal text-indigo-500 bg-indigo-100 px-1.5 py-0.5 rounded">
                                        {{ getPonVlansText(odc) }}
                                    </span>
                                </p>
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
                            <div v-if="odc.latitude && odc.longitude">
                                <p class="text-xs text-gray-500 mb-1">Koordinat GPS</p>
                                <a :href="`https://maps.google.com/?q=${odc.latitude},${odc.longitude}`" target="_blank" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1">
                                    {{ odc.latitude }}, {{ odc.longitude }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
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
                            <div v-for="odp in (odc.odps || [])" :key="odp.id" class="p-5 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600 shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </div>
                                        <div>
                                            <Link :href="`/odps/${odp.id}`" class="text-sm font-bold text-gray-900 hover:text-purple-600 transition-colors">{{ odp.name }}</Link>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                {{ odp.address || 'Alamat tidak tersedia' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
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
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odc: Object,
});

function getPonVlansText(odc) {
    if (!odc.olt || !odc.pon_port || !odc.olt.pon_vlans) return '';
    let vlans = odc.olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return ''; }
    }
    if (!Array.isArray(vlans)) return '';
    
    const ponData = vlans.find(p => p.port == odc.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans) || ponData.vlans.length === 0) return '';
    return ponData.vlans.map(v => `${v.name} (${v.vlan_id})`).join(', ');
}
</script>
