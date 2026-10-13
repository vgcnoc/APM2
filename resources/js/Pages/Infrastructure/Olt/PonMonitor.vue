<template>
    <AppLayout title="Monitoring Port PON (Multi OLT)" subtitle="Visualisasi & Utilisasi Seluruh Port PON dari Seluruh Perangkat OLT Jaringan">
        <div class="space-y-6">
            <!-- Nav Tabs Multi OLT -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto">
                <Link href="/olts" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>1. Data & Perangkat OLT</span>
                </Link>
                <Link href="/olts/pon-monitor" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shrink-0 bg-emerald-600 text-white shadow-sm shadow-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>2. Monitoring Port PON</span>
                </Link>
                <Link href="/olts/map" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>3. Peta Sebaran OLT</span>
                </Link>
                <Link href="/olts/capacity" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    <span>4. Kapasitas & Utilisasi</span>
                </Link>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
                    <select v-model="filterArea" @change="performFilter" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>

                    <select v-model="filterBrand" @change="performFilter" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm bg-gray-50/50">
                        <option value="">Semua Brand OLT</option>
                        <option v-for="b in brands" :key="b" :value="b">{{ b }}</option>
                    </select>

                    <select v-model="filterStatus" @change="performFilter" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm bg-gray-50/50">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif Saja</option>
                        <option value="inactive">Non-Aktif</option>
                    </select>
                </div>

                <!-- Legenda Utilisasi -->
                <div class="flex items-center gap-4 text-xs font-semibold text-gray-600 bg-gray-50 px-3.5 py-2 rounded-xl border border-gray-200 shrink-0">
                    <span class="text-gray-400 font-bold uppercase text-[10px]">Utilisasi:</span>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>&lt; 60% Rendah</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span>60-85% Optimal</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <span>&gt; 85% Padat</span>
                    </div>
                </div>
            </div>

            <!-- List Perangkat Multi OLT & Grid Port PON -->
            <div class="space-y-6">
                <div v-for="olt in olts" :key="olt.id" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md">
                    <!-- Header OLT Card -->
                    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100/50 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-md shadow-indigo-500/20">
                                {{ olt.brand ? olt.brand.charAt(0).toUpperCase() : 'O' }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-black text-base text-gray-900">{{ olt.name }}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="olt.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700'">
                                        {{ olt.status === 'active' ? '● Aktif' : '○ Offline' }}
                                    </span>
                                    <span v-if="olt.area" class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100 text-xs font-bold">
                                        {{ olt.area.name }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5 font-mono">
                                    <span>IP: {{ olt.ip_address || 'N/A' }}</span>
                                    <span>· {{ olt.brand || 'Brand N/A' }} ({{ olt.model || '-' }})</span>
                                    <span v-if="olt.location">· 📍 {{ olt.location }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Ringkas OLT -->
                        <div class="flex items-center gap-3 self-end md:self-auto text-xs">
                            <div class="text-right">
                                <div class="text-[10px] text-gray-400 uppercase font-bold">Port PON:</div>
                                <div class="font-extrabold text-gray-800">{{ olt.total_pon_ports }} Port</div>
                            </div>
                            <div class="w-px h-8 bg-gray-200"></div>
                            <div class="text-right">
                                <div class="text-[10px] text-gray-400 uppercase font-bold">ODC Terhubung:</div>
                                <div class="font-extrabold text-rose-700">{{ olt.odcs ? olt.odcs.length : 0 }} ODC</div>
                            </div>
                            <div class="w-px h-8 bg-gray-200"></div>
                            <Link :href="`/olts/${olt.id}`" class="px-3.5 py-1.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm">
                                Detail OLT &rarr;
                            </Link>
                        </div>
                    </div>

                    <!-- Grid Visual Port PON -->
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                            <div 
                                v-for="portNum in olt.total_pon_ports" 
                                :key="portNum" 
                                class="p-3.5 rounded-2xl border transition-all relative overflow-hidden group hover:shadow-md cursor-pointer"
                                :class="getPonCardClass(olt, portNum)"
                            >
                                <!-- Header Nomor Port -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full" :class="getPortStatusDot(olt, portNum)"></div>
                                        <span class="font-black text-xs font-mono text-gray-900">PON {{ portNum }}</span>
                                    </div>
                                    <span class="text-[10px] font-mono font-bold text-gray-400">
                                        #{{ String(portNum).padStart(2, '0') }}
                                    </span>
                                </div>

                                <!-- Utilisasi Pelanggan -->
                                <div class="mt-3">
                                    <div class="flex justify-between items-baseline text-xs mb-1">
                                        <span class="text-[10px] font-bold text-gray-500 uppercase">Pelanggan:</span>
                                        <span class="font-black text-xs" :class="getUsageTextColor(getPonInfo(olt, portNum).percentage)">
                                            {{ getPonInfo(olt, portNum).used }} / {{ getPonInfo(olt, portNum).capacity }}
                                        </span>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-500" 
                                            :class="getProgressBarClass(getPonInfo(olt, portNum).percentage)" 
                                            :style="{ width: `${Math.min(100, getPonInfo(olt, portNum).percentage)}%` }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Info ODC & VLAN -->
                                <div class="mt-2.5 pt-2 border-t border-gray-100 text-[10px] space-y-0.5">
                                    <div class="flex justify-between text-gray-600">
                                        <span>ODC:</span>
                                        <strong class="text-rose-700 truncate max-w-[70px]">{{ getPonInfo(olt, portNum).odcName || 'Standby' }}</strong>
                                    </div>
                                    <div class="flex justify-between text-gray-600">
                                        <span>VLAN:</span>
                                        <strong class="text-indigo-700 font-mono">{{ getPonInfo(olt, portNum).vlan || '-' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="olts.length === 0" class="bg-white rounded-3xl p-12 text-center text-gray-400 border border-gray-100">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <p class="text-base font-bold text-gray-600">Tidak ada perangkat OLT yang ditemukan.</p>
                    <p class="text-xs text-gray-400 mt-1">Sesuaikan filter pencarian area atau brand di atas.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    olts: Array,
    areas: Array,
    brands: Array,
    filters: Object,
});

const filterArea = ref(props.filters?.area_id || '');
const filterBrand = ref(props.filters?.brand || '');
const filterStatus = ref(props.filters?.status || '');

const performFilter = () => {
    router.get('/olts/pon-monitor', {
        area_id: filterArea.value,
        brand: filterBrand.value,
        status: filterStatus.value,
    }, { preserveState: true, replace: true });
};

// Hitung data port PON OLT
const getPonInfo = (olt, portNumber) => {
    const capacity = olt.pon_capacity || 64;
    
    // Cari ODC yang mengarah ke PON ini
    const odc = (olt.odcs || []).find(o => o.pon_port == portNumber);
    let used = 0;
    let odcName = null;

    if (odc) {
        odcName = odc.name;
        // Hitung total port terpakai di ODP di bawah ODC ini
        if (odc.odps && odc.odps.length > 0) {
            used = odc.odps.reduce((acc, odp) => acc + (odp.used_ports || 0), 0);
        }
    }

    // Cari info VLAN port ini jika ada
    let vlan = null;
    if (olt.pon_vlans && Array.isArray(olt.pon_vlans)) {
        const ponVlan = olt.pon_vlans.find(p => p.port == portNumber);
        if (ponVlan && ponVlan.vlans && ponVlan.vlans.length > 0) {
            vlan = ponVlan.vlans[0].vlan_id;
        }
    }

    const percentage = capacity > 0 ? Math.round((used / capacity) * 100) : 0;

    return {
        used,
        capacity,
        percentage,
        odcName,
        vlan,
    };
};

const getPonCardClass = (olt, portNumber) => {
    const info = getPonInfo(olt, portNumber);
    if (!info.odcName) {
        return 'bg-gray-50/50 border-gray-200 opacity-70';
    }
    if (info.percentage > 85) {
        return 'bg-red-50/30 border-red-200 hover:border-red-400';
    }
    if (info.percentage > 60) {
        return 'bg-amber-50/30 border-amber-200 hover:border-amber-400';
    }
    return 'bg-emerald-50/20 border-emerald-200 hover:border-emerald-400';
};

const getPortStatusDot = (olt, portNumber) => {
    if (olt.status !== 'active') return 'bg-gray-300';
    const info = getPonInfo(olt, portNumber);
    if (!info.odcName) return 'bg-gray-300';
    if (info.percentage > 85) return 'bg-rose-500 animate-pulse';
    if (info.percentage > 60) return 'bg-amber-500';
    return 'bg-emerald-500';
};

const getUsageTextColor = (percentage) => {
    if (percentage > 85) return 'text-rose-600 font-black';
    if (percentage > 60) return 'text-amber-600 font-bold';
    return 'text-emerald-700 font-bold';
};

const getProgressBarClass = (percentage) => {
    if (percentage > 85) return 'bg-rose-500';
    if (percentage > 60) return 'bg-amber-500';
    return 'bg-emerald-500';
};
</script>
