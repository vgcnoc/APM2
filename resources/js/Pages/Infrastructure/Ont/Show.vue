<template>
    <AppLayout :title="`Detail ONT: ${ont.serial_number}`">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="'/onts'" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            ONT {{ ont.serial_number }}
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="ont.status === 'active' ? 'bg-green-100 text-green-800' : (ont.status === 'inventory' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')">
                                {{ ont.status === 'active' ? 'Aktif (Terpasang)' : (ont.status === 'inventory' ? 'Inventory (Gudang)' : ont.status) }}
                            </span>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ ont.brand || 'Brand N/A' }} {{ ont.model ? `- ${ont.model}` : '' }} | {{ ont.mac_address || 'MAC N/A' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="'/onts'" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Info Kolom Kiri -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Status Pelanggan -->
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-sm text-white p-5 relative overflow-hidden">
                        <div class="absolute -right-6 -top-6 text-white/10">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-2a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm9 11a1 1 0 0 1-2 0v-2a3 3 0 0 0-3-3H8a3 3 0 0 0-3 3v2a1 1 0 0 1-2 0v-2a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v2z"/></svg>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-sm font-bold uppercase tracking-wider mb-2 opacity-90">Pemilik / Pelanggan</h3>
                            <div v-if="ont.customer">
                                <div class="text-2xl font-bold mb-1">{{ ont.customer.name }}</div>
                                <div class="text-sm opacity-90">{{ ont.customer.cid }}</div>
                                <div v-if="ont.customer.package" class="mt-3 bg-white/20 px-3 py-1.5 rounded-lg text-sm inline-block font-medium">
                                    {{ ont.customer.package.name }}
                                </div>
                            </div>
                            <div v-else class="text-xl font-bold text-white/80">
                                Belum Terpasang (Inventory)
                            </div>
                        </div>
                    </div>

                    <!-- Koneksi ODP -->
                    <div class="bg-purple-50 rounded-xl shadow-sm border border-purple-100 p-5">
                        <h3 class="text-sm font-bold text-purple-900 uppercase tracking-wider mb-4 border-b border-purple-100 pb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Jalur Koneksi
                        </h3>
                        <div class="space-y-4">
                            <div v-if="ont.odp">
                                <p class="text-xs text-purple-500 mb-1">Terhubung Ke ODP</p>
                                <Link :href="'/odps/' + ont.odp.id" class="text-sm font-bold text-purple-700 hover:underline">
                                    {{ ont.odp.name }}
                                </Link>
                                <span class="ml-2 text-xs font-semibold bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">Port {{ ont.port_number || '?' }}</span>
                            </div>
                            <div v-else>
                                <p class="text-xs text-gray-500 mb-1">ODP</p>
                                <p class="text-sm font-medium text-gray-700">-</p>
                            </div>
                            
                            <div v-if="ont.odp && ont.odp.odc">
                                <p class="text-xs text-purple-500 mb-1">Jalur ODC</p>
                                <Link :href="'/odcs/' + ont.odp.odc.id" class="text-sm font-bold text-purple-700 hover:underline">
                                    {{ ont.odp.odc.name }}
                                </Link>
                                <span v-if="ont.odp.odc.pon_port" class="ml-2 text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">PON {{ ont.odp.odc.pon_port }}</span>
                            </div>
                            
                            <div v-if="ont.odp && ont.odp.odc && ont.odp.odc.olt">
                                <p class="text-xs text-purple-500 mb-1">Induk OLT</p>
                                <Link :href="'/olts/' + ont.odp.odc.olt.id" class="text-sm font-bold text-purple-700 hover:underline">
                                    {{ ont.odp.odc.olt.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Spesifikasi -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Detail Konfigurasi ONT -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-100 p-5 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                Spesifikasi & Konfigurasi ONT
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Mode VLAN</p>
                                    <p class="text-sm font-medium text-gray-900">{{ ont.vlan_mode || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">VLAN ID</p>
                                    <p class="text-sm font-medium text-gray-900">{{ ont.vlan_id || '-' }}</p>
                                </div>
                                
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Mode Akses</p>
                                    <p class="text-sm font-medium text-gray-900">{{ ont.access_mode || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Kredensial PPPoE</p>
                                    <div class="text-sm font-medium text-gray-900" v-if="ont.pppoe_user">
                                        <span class="text-gray-500 mr-2">User:</span> {{ ont.pppoe_user }}<br>
                                        <span class="text-gray-500 mr-2">Pass:</span> 
                                        <span class="font-mono bg-gray-100 px-1 py-0.5 rounded text-xs">{{ ont.pppoe_password || '***' }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900" v-else>-</p>
                                </div>
                                
                                <div class="col-span-1 md:col-span-2 border-t border-gray-100 pt-4 mt-2">
                                    <h4 class="text-sm font-bold text-gray-800 mb-4">Akses Login ONT</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">IP Login</p>
                                            <p class="text-sm font-mono font-medium text-indigo-600">{{ ont.ip_login || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Username</p>
                                            <p class="text-sm font-medium text-gray-900">{{ ont.login_user || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Password</p>
                                            <p class="text-sm font-medium text-gray-900">{{ ont.login_password || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-span-1 md:col-span-2 border-t border-gray-100 pt-4 mt-2">
                                    <h4 class="text-sm font-bold text-gray-800 mb-4">Redaman Optik (Optical Power)</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-red-50 border border-red-100 rounded-lg p-4">
                                            <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-1">RX Power</p>
                                            <p class="text-2xl font-bold text-red-700">{{ ont.rx_power ? `${ont.rx_power} dBm` : '-' }}</p>
                                        </div>
                                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                                            <p class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-1">TX Power</p>
                                            <p class="text-2xl font-bold text-blue-700">{{ ont.tx_power ? `${ont.tx_power} dBm` : '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-span-1 md:col-span-2 border-t border-gray-100 pt-4 mt-2">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Petugas Instalasi</p>
                                    <div class="flex flex-wrap gap-2 mt-2" v-if="ont.input_officers && ont.input_officers.length > 0">
                                        <span v-for="(officer, index) in ont.input_officers" :key="index" class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full text-xs font-medium">
                                            {{ officer }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900" v-else>-</p>
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
    ont: Object,
});
</script>
