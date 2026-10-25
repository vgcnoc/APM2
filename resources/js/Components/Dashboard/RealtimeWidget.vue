<template>
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-5 md:p-6 transition-all hover:shadow-md relative overflow-hidden">
        <!-- Card Header -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-sm shadow-purple-500/20">
                    ⚡
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Realtime</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Monitoring aktivitas user & billing live</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/50 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>LIVE</span>
                </span>
                <button
                    @click="refreshData"
                    :disabled="isLoading"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Refresh Data Realtime"
                >
                    <svg class="w-4 h-4" :class="{ 'animate-spin text-purple-600': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="space-y-6">
            <!-- ═══ ROW 1: Real Online ═══ -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide uppercase">Real Online</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                            {{ formatNumber(currentStats.real_online) }}<span class="text-slate-400 font-semibold text-lg">/{{ formatNumber(currentStats.max_capacity) }}</span>
                        </p>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-xs font-black bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/50 shadow-2xs cursor-help"
                        :title="capacityTooltip"
                    >
                        Max {{ formatNumber(currentStats.max_capacity) }}
                    </span>
                </div>

                <!-- Striped Purple Progress Bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-5 overflow-hidden shadow-inner relative flex items-center">
                    <div
                        class="h-full bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-500 transition-all duration-700 ease-out flex items-center justify-center relative overflow-hidden striped-bar"
                        :style="{ width: `${Math.min(100, Math.max(3, realOnlinePercent))}%` }"
                    >
                        <span v-if="realOnlinePercent > 10" class="text-[11px] font-black text-white px-2 tracking-wide drop-shadow-sm select-none">
                            {{ formatNumber(currentStats.real_online) }}
                        </span>
                    </div>
                    <span v-if="realOnlinePercent <= 10" class="text-[11px] font-black text-purple-600 dark:text-purple-400 px-2.5">
                        {{ formatNumber(currentStats.real_online) }}
                    </span>
                </div>

                <!-- Info Sumber Kapasitas (Deteksi Otomatis CPU) -->
                <div v-if="capacitySummary" class="flex items-center justify-between gap-2 text-[10px] text-slate-400 dark:text-slate-500">
                    <span class="truncate" :title="capacityTooltip">
                        <span class="font-bold" :class="currentStats.capacity_source === 'router' ? 'text-indigo-500' : 'text-amber-500'">
                            {{ currentStats.capacity_source === 'router' ? 'CPU Router' : 'CPU Server' }}
                        </span>
                        · {{ capacitySummary }}
                    </span>
                    <span v-if="currentStats.capacity_cpu_load !== null && currentStats.capacity_cpu_load !== undefined" class="shrink-0 font-bold" :class="cpuLoadClass">
                        Load {{ currentStats.capacity_cpu_load }}%
                    </span>
                </div>
            </div>

            <!-- ═══ ROW 2: Online: Voucher / Billing ═══ -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide">
                            Online: <span class="text-emerald-500 font-extrabold">Voucher</span>/<span class="text-sky-500 font-extrabold">Billing</span>
                        </p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                            {{ formatNumber(currentStats.online_voucher) }}<span class="text-slate-400 font-semibold text-lg">/{{ formatNumber(currentStats.online_billing) }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-full text-xs font-black bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800/50 shadow-2xs">
                            {{ formatNumber(currentStats.pending_count) }} Pending
                        </span>

                        <!-- Tombol Action Expired Voucher -->
                        <button
                            @click="openModal('expired')"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black text-white bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 shadow-sm shadow-rose-500/20 active:scale-95 transition-all cursor-pointer"
                            title="Kelola & Hapus Voucher Expired"
                        >
                            <span>🗑️</span>
                            <span>{{ formatNumber(currentStats.expired_voucher_count) }} Expired</span>
                        </button>
                    </div>
                </div>

                <!-- Dual Striped Bar: Green (Voucher) + Cyan (Billing) -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-5 overflow-hidden shadow-inner flex relative">
                    <!-- Voucher Segment -->
                    <div
                        v-if="currentStats.online_voucher > 0"
                        class="h-full bg-emerald-500 transition-all duration-700 ease-out flex items-center justify-center relative striped-bar"
                        :style="{ width: `${voucherPercent}%` }"
                        :title="`Voucher Online: ${formatNumber(currentStats.online_voucher)}`"
                    >
                        <span v-if="voucherPercent > 6" class="text-[10px] font-black text-white px-1 truncate select-none drop-shadow-sm">
                            {{ formatNumber(currentStats.online_voucher) }}
                        </span>
                    </div>

                    <!-- Billing Segment -->
                    <div
                        v-if="currentStats.online_billing > 0"
                        class="h-full bg-sky-400 transition-all duration-700 ease-out flex items-center justify-center relative striped-bar"
                        :style="{ width: `${billingPercent}%` }"
                        :title="`Billing (PPPoE) Online: ${formatNumber(currentStats.online_billing)}`"
                    >
                        <span v-if="billingPercent > 8" class="text-[10px] font-black text-white px-1 truncate select-none drop-shadow-sm">
                            {{ formatNumber(currentStats.online_billing) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══ ROW 3: Billing: UNPAID / ISOLIR ═══ -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide">
                            Billing: <span class="text-amber-500 font-extrabold uppercase">UNPAID</span>/<span class="text-rose-500 font-extrabold uppercase">ISOLIR</span>
                        </p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                            {{ formatNumber(currentStats.unpaid_count) }}<span class="text-slate-400 font-semibold text-lg">/{{ formatNumber(currentStats.isolir_count) }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Tombol Action Unpaid -->
                        <button
                            v-can="'dashboard_realtime_btn_unpaid'"
                            @click="openModal('unpaid')"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 shadow-sm shadow-amber-500/20 active:scale-95 transition-all"
                            title="Aksi Pelanggan Unpaid"
                        >
                            <span>📢</span>
                            <span>Unpaid</span>
                        </button>

                        <!-- Tombol Action Isolir -->
                        <button
                            v-can="'dashboard_realtime_btn_isolir'"
                            @click="openModal('isolir')"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 shadow-sm shadow-rose-500/20 active:scale-95 transition-all"
                            title="Aksi Pelanggan Isolir"
                        >
                            <span>📢</span>
                            <span>Isolir</span>
                        </button>
                    </div>
                </div>

                <!-- Dual Striped Bar: Orange (Unpaid) + Red (Isolir) -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-5 overflow-hidden shadow-inner flex relative">
                    <!-- Unpaid Segment -->
                    <div
                        v-if="currentStats.unpaid_count > 0"
                        class="h-full bg-amber-500 transition-all duration-700 ease-out flex items-center justify-center relative striped-bar"
                        :style="{ width: `${unpaidPercent}%` }"
                        :title="`Unpaid Invoices: ${formatNumber(currentStats.unpaid_count)}`"
                    >
                        <span v-if="unpaidPercent > 5" class="text-[10px] font-black text-white px-1 select-none drop-shadow-sm">
                            {{ formatNumber(currentStats.unpaid_count) }}
                        </span>
                    </div>

                    <!-- Isolir Segment -->
                    <div
                        v-if="currentStats.isolir_count > 0"
                        class="h-full bg-rose-500 transition-all duration-700 ease-out flex items-center justify-center relative striped-bar"
                        :style="{ width: `${isolirPercent}%` }"
                        :title="`Pelanggan Isolir: ${formatNumber(currentStats.isolir_count)}`"
                    >
                        <span v-if="isolirPercent > 6" class="text-[10px] font-black text-white px-1 select-none drop-shadow-sm">
                            {{ formatNumber(currentStats.isolir_count) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL DETAIL & QUICK BLAST / EXPIRED ═══ -->
        <div v-if="activeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-800"
                    :class="activeModal === 'unpaid' ? 'bg-amber-500/10' : (activeModal === 'expired' ? 'bg-rose-500/10' : 'bg-red-500/10')">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ activeModal === 'unpaid' ? '📢' : (activeModal === 'expired' ? '🎟️' : '🚨') }}</span>
                        <div>
                            <h4 class="text-base font-black text-slate-900 dark:text-white">
                                {{ activeModal === 'unpaid' ? 'Pelanggan Unpaid (Belum Bayar)' : (activeModal === 'expired' ? 'Voucher Hotspot Expired' : 'Pelanggan Status Isolir') }}
                            </h4>
                            <p class="text-xs text-slate-500">
                                {{ activeModal === 'unpaid' ? `Total ${formatNumber(currentStats.unpaid_count)} invoice belum bayar` : (activeModal === 'expired' ? `Total ${formatNumber(currentStats.expired_voucher_count)} voucher kedaluwarsa` : `Total ${formatNumber(currentStats.isolir_count)} pelanggan tercatat`) }}
                            </p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                        ✕
                    </button>
                </div>

                <!-- Modal Body: Customer / Voucher List -->
                <div class="p-6 max-h-[380px] overflow-y-auto space-y-2.5">
                    <!-- 1. Modal Unpaid Invoices -->
                    <template v-if="activeModal === 'unpaid'">
                        <div v-for="item in currentStats.unpaid_customers" :key="item.id"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ item.customer_name }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ item.invoice_number }} · {{ item.area_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-black text-amber-600 dark:text-amber-400">Rp {{ formatCurrency(item.amount) }}</p>
                                <p class="text-[10px] text-slate-400">{{ item.customer_phone }}</p>
                            </div>
                        </div>
                        <div v-if="!currentStats.unpaid_customers?.length" class="text-center py-6 text-xs text-slate-400">
                            Tidak ada data invoice unpaid.
                        </div>
                    </template>

                    <!-- 2. Modal Expired Vouchers (Bisa Dihapus Satuan & Massal) -->
                    <template v-else-if="activeModal === 'expired'">
                        <!-- Toolbar: Search & Hapus Semua -->
                        <div class="flex items-center gap-2 mb-3">
                            <div class="relative flex-1">
                                <input
                                    v-model="expiredSearch"
                                    type="text"
                                    placeholder="Cari kode/profil/reseller..."
                                    class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 py-2 pl-3 pr-7 focus:ring-rose-500 focus:border-rose-500"
                                />
                                <button
                                    v-if="expiredSearch"
                                    @click="expiredSearch = ''"
                                    type="button"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs"
                                >
                                    ✕
                                </button>
                            </div>
                            <button
                                v-if="(currentStats.expired_vouchers?.length || 0) > 0"
                                @click="deleteAllExpiredVouchers"
                                :disabled="isBulkDeleting"
                                type="button"
                                class="px-3 py-2 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-sm shadow-rose-500/20 active:scale-95 disabled:opacity-50 shrink-0"
                                title="Hapus semua voucher expired dari database & FreeRADIUS"
                            >
                                <svg v-if="isBulkDeleting" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span v-else>🗑️</span>
                                <span>Hapus Semua</span>
                            </button>
                        </div>

                        <!-- Daftar Voucher Expired -->
                        <div
                            v-for="item in filteredExpiredVouchers"
                            :key="item.id"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-mono font-black text-slate-900 dark:text-white bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">
                                        {{ item.code }}
                                    </span>
                                    <span v-if="item.is_free" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60">
                                        🎁 Gratis
                                    </span>
                                    <span v-else class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200/60 font-mono">
                                        {{ item.price_formatted }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-semibold truncate">
                                        {{ item.profile_name }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-1 flex-wrap">
                                    <span>👤 {{ item.reseller_name }}</span>
                                    <span>·</span>
                                    <span class="text-rose-500 dark:text-rose-400 font-medium">⏱️ Habis: {{ item.expired_at }}</span>
                                </div>
                            </div>

                            <button
                                @click="deleteSingleExpiredVoucher(item)"
                                :disabled="deletingId === item.id"
                                type="button"
                                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs active:scale-95 disabled:opacity-50 shrink-0"
                                title="Hapus voucher ini"
                            >
                                <svg v-if="deletingId === item.id" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span v-else>🗑️</span>
                                <span>Hapus</span>
                            </button>
                        </div>

                        <div v-if="!filteredExpiredVouchers.length" class="text-center py-8 text-xs text-slate-400">
                            <div class="text-3xl mb-2">🎉</div>
                            <p class="font-bold text-slate-600 dark:text-slate-300">Tidak ada voucher expired</p>
                            <p class="text-[11px] mt-0.5">Semua voucher aktif atau telah dibersihkan.</p>
                        </div>
                    </template>

                    <!-- 3. Modal Isolir Customers -->
                    <template v-else>
                        <div v-for="item in currentStats.isolir_customers" :key="item.id"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ item.name }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ item.customer_code }} · {{ item.area_name }}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                    ISOLIR
                                </span>
                                <p class="text-[10px] text-slate-400 mt-1">{{ item.phone }}</p>
                            </div>
                        </div>
                        <div v-if="!currentStats.isolir_customers?.length" class="text-center py-6 text-xs text-slate-400">
                            Tidak ada data pelanggan isolir.
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button @click="activeModal = null" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition">
                        Tutup
                    </button>
                    <Link
                        v-if="activeModal === 'unpaid'"
                        href="/invoices?status=unpaid"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 transition flex items-center gap-1.5 shadow-sm"
                    >
                        <span>Buka Menu Invoice →</span>
                    </Link>
                    <Link
                        v-else-if="activeModal === 'expired'"
                        href="/vouchers/expired"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition flex items-center gap-1.5 shadow-sm"
                    >
                        <span>Buka Menu Voucher Expired →</span>
                    </Link>
                    <Link
                        v-else
                        href="/customers/isolir"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition flex items-center gap-1.5 shadow-sm"
                    >
                        <span>Buka Pelanggan Isolir →</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            real_online: 0,
            max_capacity: 0,
            capacity_source: null,
            capacity_cpu_load: null,
            capacity_devices: [],
            online_voucher: 0,
            online_billing: 0,
            pending_count: 0,
            expired_voucher_count: 0,
            expired_vouchers: [],
            unpaid_count: 0,
            isolir_count: 0,
            unpaid_customers: [],
            isolir_customers: [],
        }),
    },
    areaId: {
        type: [String, Number],
        default: 'all',
    },
    autoPoll: {
        type: Boolean,
        default: true,
    },
});

const currentStats = ref({ ...props.stats });
watch(() => props.stats, (newVal) => {
    if (newVal) currentStats.value = { ...newVal };
}, { deep: true });
const isLoading = ref(false);
const activeModal = ref(null);
const expiredSearch = ref('');
const deletingId = ref(null);
const isBulkDeleting = ref(false);
let pollTimer = null;

const filteredExpiredVouchers = computed(() => {
    let list = currentStats.value.expired_vouchers || [];
    if (!expiredSearch.value) return list;
    const q = expiredSearch.value.toLowerCase();
    return list.filter((v) =>
        (v.code && v.code.toLowerCase().includes(q)) ||
        (v.username && v.username.toLowerCase().includes(q)) ||
        (v.profile_name && v.profile_name.toLowerCase().includes(q)) ||
        (v.reseller_name && v.reseller_name.toLowerCase().includes(q)) ||
        (v.area_name && v.area_name.toLowerCase().includes(q))
    );
});

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return Number(num).toLocaleString('id-ID');
};

const formatCurrency = (amount) => {
    if (!amount) return '0';
    return Number(amount).toLocaleString('id-ID');
};


// Row 1: Real Online %
const realOnlinePercent = computed(() => {
    const max = currentStats.value.max_capacity || 1;
    const current = currentStats.value.real_online || 0;
    return Math.min(100, Math.max(0, (current / max) * 100));
});

// Ringkasan perangkat sumber kapasitas (hasil deteksi otomatis CPU)
const capacityDevices = computed(() => currentStats.value.capacity_devices || []);

const describeDevice = (d) => {
    const parts = [];
    parts.push(d.board || d.name || 'Perangkat');
    if (d.cores) {
        const ghz = d.mhz ? ` @${(d.mhz / 1000).toFixed(1)}GHz` : '';
        parts.push(`${d.cores} core${ghz}`);
    }
    return parts.join(' · ');
};

const capacitySummary = computed(() => {
    const usable = capacityDevices.value.filter((d) => d.capacity > 0);
    if (!usable.length) return '';
    if (usable.length === 1) return describeDevice(usable[0]);
    return `${usable.length} perangkat`;
});

const capacityTooltip = computed(() => {
    if (!capacityDevices.value.length) return 'Kapasitas belum terdeteksi';
    const lines = capacityDevices.value.map((d) => {
        const status = d.status === 'online' ? 'online' : 'tidak terjangkau';
        const cap = d.capacity > 0 ? `±${formatNumber(d.capacity)} sesi` : 'belum terdeteksi';
        const extra = d.status !== 'online' && d.capacity > 0 ? ' (data deteksi terakhir)' : '';
        return `${d.name}: ${describeDevice(d)} — ${cap}, ${status}${extra}`;
    });
    const header = currentStats.value.capacity_source === 'router'
        ? 'Kapasitas dihitung otomatis dari CPU router MikroTik:'
        : 'Router belum terdeteksi, kapasitas sementara dari CPU server:';
    return [header, ...lines].join('\n');
});

const cpuLoadClass = computed(() => {
    const load = currentStats.value.capacity_cpu_load || 0;
    if (load >= 80) return 'text-rose-500';
    if (load >= 60) return 'text-amber-500';
    return 'text-emerald-500';
});

// Row 2: Voucher % vs Billing %
const voucherPercent = computed(() => {
    const max = currentStats.value.max_capacity || 1;
    const voucher = currentStats.value.online_voucher || 0;
    return Math.min(100, Math.max(0, (voucher / max) * 100));
});

const billingPercent = computed(() => {
    const max = currentStats.value.max_capacity || 1;
    const billing = currentStats.value.online_billing || 0;
    return Math.min(100, Math.max(0, (billing / max) * 100));
});

// Row 3: Unpaid % vs Isolir %
const unpaidPercent = computed(() => {
    const total = Math.max(1, (currentStats.value.unpaid_count || 0) + (currentStats.value.isolir_count || 0));
    const unpaid = currentStats.value.unpaid_count || 0;
    return Math.min(100, Math.max(0, (unpaid / total) * 100));
});

const isolirPercent = computed(() => {
    const total = Math.max(1, (currentStats.value.unpaid_count || 0) + (currentStats.value.isolir_count || 0));
    const isolir = currentStats.value.isolir_count || 0;
    return Math.min(100, Math.max(0, (isolir / total) * 100));
});

const openModal = (type) => {
    activeModal.value = type;
};

const deleteSingleExpiredVoucher = async (item) => {
    const result = await Swal.fire({
        title: `Hapus Voucher ${item.code}?`,
        text: 'Voucher akan dihapus permanen dari sistem & FreeRADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
    });

    if (!result.isConfirmed) return;

    deletingId.value = item.id;
    try {
        const res = await axios.post(`/dashboard/vouchers/${item.id}/delete`);
        if (res.data?.success) {
            // Hapus dari list lokal langsung
            if (currentStats.value.expired_vouchers) {
                currentStats.value.expired_vouchers = currentStats.value.expired_vouchers.filter((v) => v.id !== item.id);
            }
            if (currentStats.value.expired_voucher_count > 0) {
                currentStats.value.expired_voucher_count--;
            }
            Swal.fire({
                title: 'Berhasil!',
                text: res.data.message || `Voucher ${item.code} berhasil dihapus.`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
            });
        }
    } catch (e) {
        console.error('Delete error:', e);
        Swal.fire({
            title: 'Gagal',
            text: e.response?.data?.message || 'Gagal menghapus voucher.',
            icon: 'error',
        });
    } finally {
        deletingId.value = null;
    }
};

const deleteAllExpiredVouchers = async () => {
    const count = currentStats.value.expired_voucher_count || currentStats.value.expired_vouchers?.length || 0;
    const result = await Swal.fire({
        title: `Hapus SEMUA (${count}) Voucher Expired?`,
        text: 'Seluruh voucher yang telah habis masa aktifnya akan dibersihkan permanen dari database & FreeRADIUS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Ya, Hapus Semua (${count})!`,
        cancelButtonText: 'Batal',
    });

    if (!result.isConfirmed) return;

    isBulkDeleting.value = true;
    try {
        const res = await axios.post('/dashboard/vouchers/delete-expired');
        if (res.data?.success) {
            currentStats.value.expired_vouchers = [];
            currentStats.value.expired_voucher_count = 0;
            Swal.fire({
                title: 'Dibersihkan!',
                text: res.data.message || 'Semua voucher expired berhasil dihapus.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
            });
        }
    } catch (e) {
        console.error('Bulk delete error:', e);
        Swal.fire({
            title: 'Gagal',
            text: e.response?.data?.message || 'Gagal menghapus voucher expired massal.',
            icon: 'error',
        });
    } finally {
        isBulkDeleting.value = false;
    }
};


const refreshData = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get('/dashboard/realtime-api', {
            params: { area_id: props.areaId },
        });
        if (res.data) {
            currentStats.value = { ...res.data };
        }
    } catch (e) {
        console.warn('Realtime polling skipped:', e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    if (props.autoPoll) {
        pollTimer = setInterval(() => {
            refreshData();
        }, 15000); // Poll every 15s
    }
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<style scoped>
.striped-bar {
    background-image: repeating-linear-gradient(
        -45deg,
        rgba(255, 255, 255, 0.2),
        rgba(255, 255, 255, 0.2) 8px,
        rgba(255, 255, 255, 0) 8px,
        rgba(255, 255, 255, 0) 16px
    );
    background-size: 200% 200%;
    animation: moveStripes 25s linear infinite;
}

@keyframes moveStripes {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 100px 0;
    }
}
</style>
