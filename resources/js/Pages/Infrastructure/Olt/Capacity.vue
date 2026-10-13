<template>
    <AppLayout title="Kapasitas & Utilisasi Multi OLT" subtitle="Analisis Perencanaan Kapasitas Jaringan FTTH & Utilisasi Port PON Lintas Perangkat OLT">
        <div class="space-y-6">
            <!-- Nav Tabs Multi OLT -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto">
                <Link href="/olts" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>1. Data & Perangkat OLT</span>
                </Link>
                <Link href="/olts/pon-monitor" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>2. Monitoring Port PON</span>
                </Link>
                <Link href="/olts/map" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>3. Peta Sebaran OLT</span>
                </Link>
                <Link href="/olts/capacity" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shrink-0 bg-purple-600 text-white shadow-sm shadow-purple-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    <span>4. Kapasitas & Utilisasi</span>
                </Link>
            </div>

            <!-- Stats Overview Kapasitas Global -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Slot Kapasitas OLT</div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ summary.total_capacity.toLocaleString() }} Port ONT</div>
                    <div class="text-[11px] text-gray-400 mt-1">Dari {{ summary.total_pon }} Port PON Aktif</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-purple-100 shadow-sm">
                    <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">ONT Terpasang / Terpakai</div>
                    <div class="text-2xl font-black text-purple-700 mt-1">{{ summary.total_used.toLocaleString() }} Pelanggan</div>
                    <div class="text-[11px] text-gray-400 mt-1">Perangkat ONT terhubung</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-sm">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Sisa Slot Tersedia</div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ summary.total_available.toLocaleString() }} Slot</div>
                    <div class="text-[11px] text-gray-400 mt-1">Dapat diaktivasi langsung</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-sm">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Utilisasi Jaringan Global</div>
                    <div class="text-2xl font-black text-indigo-700 mt-1">{{ summary.overall_percentage }}%</div>
                    <!-- Mini Progress Bar -->
                    <div class="w-full h-1.5 bg-gray-100 rounded-full mt-2 overflow-hidden">
                        <div class="h-full bg-indigo-600 rounded-full" :style="{ width: `${Math.min(100, summary.overall_percentage)}%` }"></div>
                    </div>
                </div>
            </div>

            <!-- Toolbar Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-3">
                <div class="flex items-center gap-3 flex-1 w-full sm:w-auto">
                    <select v-model="filterArea" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm bg-gray-50/50 w-full sm:w-64">
                        <option value="">Semua Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.name">{{ a.name }}</option>
                    </select>
                </div>
                <div class="text-xs text-gray-500">
                    Memantau utilisasi per OLT untuk menentukan kebutuhan ekspansi jaringan & penambahan modul PON baru.
                </div>
            </div>

            <!-- Table Analisis Kapasitas per OLT -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="p-4">Perangkat OLT & Area</th>
                                <th class="p-4 text-center">Port PON & ODC</th>
                                <th class="p-4 text-center">Total Kapasitas</th>
                                <th class="p-4 text-center">Terpakai</th>
                                <th class="p-4 text-center">Tersedia</th>
                                <th class="p-4">Utilisasi & Progress</th>
                                <th class="p-4 text-center">Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="item in filteredCapacities" :key="item.id" class="hover:bg-purple-50/20 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">{{ item.name }}</div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100 text-[10px] font-bold">
                                            {{ item.area_name }}
                                        </span>
                                        <span>· {{ item.brand || 'Brand N/A' }} {{ item.model ? `(${item.model})` : '' }}</span>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="font-bold text-gray-900">{{ item.total_pon_ports }} Port PON</div>
                                    <div class="text-xs text-rose-700 font-semibold">{{ item.odc_count }} ODC</div>
                                </td>

                                <td class="p-4 text-center font-bold text-gray-900">
                                    {{ item.total_capacity.toLocaleString() }}
                                </td>

                                <td class="p-4 text-center font-bold text-purple-700">
                                    {{ item.used_capacity.toLocaleString() }}
                                </td>

                                <td class="p-4 text-center font-bold text-emerald-700">
                                    {{ item.available_capacity.toLocaleString() }}
                                </td>

                                <td class="p-4 w-56">
                                    <div class="flex justify-between items-baseline text-xs mb-1">
                                        <span class="font-bold" :class="getPercentageColor(item.percentage)">{{ item.percentage }}%</span>
                                        <span class="text-[10px] text-gray-400">terisi</span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-500" 
                                            :class="getBarColor(item.percentage)" 
                                            :style="{ width: `${Math.min(100, item.percentage)}%` }"
                                        ></div>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="getRecommendationBadge(item.percentage)">
                                        {{ getRecommendationText(item.percentage) }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="filteredCapacities.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-400">
                                    Tidak ada data kapasitas OLT yang sesuai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    oltCapacities: Array,
    areas: Array,
    summary: Object,
});

const filterArea = ref('');

const filteredCapacities = computed(() => {
    if (!filterArea.value) return props.oltCapacities || [];
    return (props.oltCapacities || []).filter(c => c.area_name === filterArea.value);
});

const getPercentageColor = (pct) => {
    if (pct > 85) return 'text-rose-600';
    if (pct > 60) return 'text-amber-600';
    return 'text-emerald-700';
};

const getBarColor = (pct) => {
    if (pct > 85) return 'bg-rose-500';
    if (pct > 60) return 'bg-amber-500';
    return 'bg-emerald-500';
};

const getRecommendationBadge = (pct) => {
    if (pct > 85) return 'bg-rose-50 text-rose-700 border border-rose-200';
    if (pct > 60) return 'bg-amber-50 text-amber-700 border border-amber-200';
    return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
};

const getRecommendationText = (pct) => {
    if (pct > 85) return '🚨 Perlu Tambah OLT/PON';
    if (pct > 60) return '⚡ Kapasitas Optimal';
    return '✓ Kapasitas Aman';
};
</script>
