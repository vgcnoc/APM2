<template>
    <AppLayout :title="`Detail ODP: ${odp.name}`">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="'/odps'" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            {{ odp.name }}
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="odp.status === 'active' ? 'bg-green-100 text-green-800' : (odp.status === 'full' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')">
                                {{ odp.status === 'active' ? 'Aktif' : (odp.status === 'full' ? 'Penuh' : odp.status) }}
                            </span>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Total Kapasitas: {{ odp.total_ports }} Port | Terpakai: {{ odp.onts ? odp.onts.length : 0 }} Port
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="'/odps'" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Info Card -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Induk OLT & ODC -->
                    <div class="bg-purple-50 rounded-xl shadow-sm border border-purple-100 p-5">
                        <h3 class="text-sm font-bold text-purple-900 uppercase tracking-wider mb-4 border-b border-purple-100 pb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Terhubung Ke
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-purple-500 mb-1">Induk ODC</p>
                                <Link v-if="odp.odc" :href="'/odcs/' + odp.odc.id" class="text-sm font-bold text-purple-700 hover:underline">
                                    {{ odp.odc.name }}
                                </Link>
                                <span v-else class="text-sm font-medium text-gray-500">-</span>
                            </div>
                            <div v-if="odp.odc && odp.odc.olt">
                                <p class="text-xs text-purple-500 mb-1">Induk OLT Utama</p>
                                <Link :href="'/olts/' + odp.odc.olt.id" class="text-sm font-bold text-purple-700 hover:underline">
                                    {{ odp.odc.olt.name }}
                                </Link>
                                <div class="text-xs text-purple-600 mt-1" v-if="odp.odc.pon_port">
                                    Mewarisi PON {{ odp.odc.pon_port }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi ODP -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Informasi ODP
                        </h3>
                        <div class="space-y-4">
                            <div v-if="odp.photo">
                                <img :src="`/storage/${odp.photo}`" alt="Foto ODP" class="w-full h-48 object-cover rounded-lg border border-gray-200" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Alamat/Lokasi</p>
                                <p class="text-sm font-medium text-gray-900">{{ odp.address || '-' }}</p>
                            </div>
                            <div v-if="odp.latitude && odp.longitude">
                                <p class="text-xs text-gray-500 mb-1">Koordinat GPS</p>
                                <a :href="`https://maps.google.com/?q=${odp.latitude},${odp.longitude}`" target="_blank" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1">
                                    {{ odp.latitude }}, {{ odp.longitude }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Keterangan</p>
                                <p class="text-sm font-medium text-gray-900">{{ odp.description || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hierarchy / ONT List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-100 p-5 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                Daftar ONT / Pelanggan ({{ odp.onts ? odp.onts.length : 0 }})
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-[800px] overflow-y-auto">
                            <div v-if="!odp.onts || odp.onts.length === 0" class="p-8 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <p>Belum ada pelanggan yang terhubung ke ODP ini.</p>
                            </div>
                            <div v-for="ont in (odp.onts || [])" :key="ont.id" class="p-5 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600 shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">
                                                {{ ont.customer ? ont.customer.name : 'Pelanggan N/A' }}
                                                <span v-if="ont.customer && ont.customer.cid" class="text-xs text-gray-500 font-normal ml-1">({{ ont.customer.cid }})</span>
                                            </div>
                                            <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                                <span class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">{{ ont.sn_mac }}</span>
                                                <span class="text-gray-400">|</span>
                                                <span>{{ ont.brand }} {{ ont.model }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="ont.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                            {{ ont.status === 'active' ? 'Aktif' : 'Non-Aktif' }}
                                        </span>
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
    odp: Object,
});
</script>
