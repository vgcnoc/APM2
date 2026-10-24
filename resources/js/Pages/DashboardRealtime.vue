<template>
    <AppLayout title="Dashboard Realtime" subtitle="Monitoring live koneksi pelanggan, voucher, billing & radius">
        <!-- ═══ Area / Wilayah Smart Filter Bar ═══ -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-4 mb-6 transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Left: Title & Active Context Info -->
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-blue-600 text-white flex items-center justify-center font-black shadow-md shadow-purple-500/20 text-lg shrink-0">
                        ⚡
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">Dashboard Live & Realtime Monitoring</h2>
                            <span
                                v-if="selectedAreaId !== 'all'"
                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                📍 {{ currentAreaName }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200"
                            >
                                🌐 Seluruh Wilayah (Nasional)
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5 truncate">
                            Data koneksi RADIUS, sesi Voucher, dan tagihan tersinkronisasi otomatis.
                        </p>
                    </div>
                </div>

                <!-- Right: Dropdown Selector -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs text-slate-400 font-medium">Area:</label>
                        <select
                            v-model="activeAreaId"
                            @change="onAreaChange"
                            class="text-xs font-bold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 py-2 pl-3 pr-8 focus:ring-purple-500 focus:border-purple-500 cursor-pointer"
                        >
                            <option value="all">Semua Wilayah</option>
                            <option v-for="area in availableAreas" :key="area.id" :value="area.id">
                                📍 {{ area.name }}
                            </option>
                        </select>
                    </div>

                    <button
                        @click="triggerManualRefresh"
                        :disabled="isRefreshing"
                        type="button"
                        class="px-3.5 py-2 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 active:scale-95 shadow-2xs"
                    >
                        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ TOP SECTION: Realtime Card Widget + NAS/Router Status ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Left: Prominent Realtime Widget (7 Cols) -->
            <div class="lg:col-span-7">
                <RealtimeWidget
                    ref="realtimeWidgetRef"
                    :stats="realtimeStats"
                    :area-id="activeAreaId"
                    :auto-poll="true"
                />
            </div>

            <!-- Right: Router NAS & System Live Heartbeat (5 Cols) -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-5 md:p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📡</span>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">RADIUS & Gateway Status</h3>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-300 border border-blue-200/60">
                            MikroTik Sync
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                    ✓
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Server Autentikasi RADIUS</p>
                                    <p class="text-[10px] text-slate-400">FreeRADIUS Daemon Online</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-500 text-white shadow-2xs">
                                RUNNING
                            </span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-950 text-sky-600 flex items-center justify-center font-bold text-sm">
                                    🖧
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Router NAS Terhubung</p>
                                    <p class="text-[10px] text-slate-400">{{ nasStats.total_nas || 1 }} NAS Aktif Melayani Sesi</p>
                                </div>
                            </div>
                            <Link href="/radius/nas" class="text-xs font-bold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                                Kelola →
                            </Link>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-950 text-purple-600 flex items-center justify-center font-bold text-sm">
                                    ⚡
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Pengguna Sesi Aktif</p>
                                    <p class="text-[10px] text-slate-400">PPPoE + Hotspot Live Traffic</p>
                                </div>
                            </div>
                            <Link href="/radius/online-users" class="text-xs font-bold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                                Lihat Sesi →
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <span>Terakhir diperbarui: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ lastUpdated }}</strong></span>
                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Auto 15s
                    </span>
                </div>
            </div>
        </div>

        <!-- ═══ BOTTOM SECTION: Quick Live Navigation Cards ═══ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- 1. Pelanggan Online -->
            <Link
                href="/customers/online"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all group block"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center text-lg">
                        🟢
                    </div>
                    <span class="text-xs font-bold text-emerald-600 group-hover:translate-x-0.5 transition-transform">Buka →</span>
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Status Koneksi Pelanggan</h4>
                <p class="text-xs text-slate-400 mt-1">Pantau pelanggan online, offline & riwayat disconnect.</p>
            </Link>

            <!-- 2. Voucher Monitoring -->
            <Link
                href="/vouchers/monitoring"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all group block"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950 text-purple-600 flex items-center justify-center text-lg">
                        🎟️
                    </div>
                    <span class="text-xs font-bold text-purple-600 group-hover:translate-x-0.5 transition-transform">Buka →</span>
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Monitoring Voucher Hotspot</h4>
                <p class="text-xs text-slate-400 mt-1">Pantau sesi voucher aktif, sisa kuota & masa aktif.</p>
            </Link>

            <!-- 3. Pelanggan Isolir -->
            <Link
                href="/customers/isolir"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all group block"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950 text-rose-600 flex items-center justify-center text-lg">
                        🔒
                    </div>
                    <span class="text-xs font-bold text-rose-600 group-hover:translate-x-0.5 transition-transform">Buka →</span>
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Pelanggan Terisolir</h4>
                <p class="text-xs text-slate-400 mt-1">Daftar pelanggan menunggak dan isolir otomatis.</p>
            </Link>

            <!-- 4. Invoice Unpaid -->
            <Link
                href="/invoices?status=unpaid"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all group block"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-600 flex items-center justify-center text-lg">
                        💳
                    </div>
                    <span class="text-xs font-bold text-amber-600 group-hover:translate-x-0.5 transition-transform">Buka →</span>
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Tagihan Unpaid</h4>
                <p class="text-xs text-slate-400 mt-1">Rincian invoice tertagih yang belum diselesaikan.</p>
            </Link>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import RealtimeWidget from '@/Components/Dashboard/RealtimeWidget.vue';

const props = defineProps({
    realtimeStats: {
        type: Object,
        default: () => ({}),
    },
    availableAreas: {
        type: Array,
        default: () => [],
    },
    selectedAreaId: {
        type: [String, Number],
        default: 'all',
    },
    nasStats: {
        type: Object,
        default: () => ({ total_nas: 1 }),
    },
});

const activeAreaId = ref(props.selectedAreaId);
const isRefreshing = ref(false);
const realtimeWidgetRef = ref(null);
const lastUpdated = ref(props.realtimeStats?.last_updated || new Date().toLocaleTimeString('id-ID'));

const currentAreaName = computed(() => {
    if (activeAreaId.value === 'all') return 'Seluruh Wilayah (Nasional)';
    const found = props.availableAreas.find((a) => a.id == activeAreaId.value);
    return found ? found.name : 'Wilayah';
});

const onAreaChange = () => {
    router.get(
        '/dashboard/realtime',
        { area_id: activeAreaId.value },
        { preserveState: true, preserveScroll: true }
    );
};

const triggerManualRefresh = async () => {
    isRefreshing.value = true;
    if (realtimeWidgetRef.value?.refreshData) {
        await realtimeWidgetRef.value.refreshData();
    }
    lastUpdated.value = new Date().toLocaleTimeString('id-ID');
    setTimeout(() => {
        isRefreshing.value = false;
    }, 500);
};
</script>
