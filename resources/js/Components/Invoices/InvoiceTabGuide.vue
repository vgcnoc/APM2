<template>
    <div>
        <!-- ── 1. CONTEXTUAL INLINE BANNER (Ringkasan Cerdas Tab Aktif) ───────────────── -->
        <div 
            v-if="showInlineHelper" 
            class="mb-5 rounded-2xl border transition-all duration-300 shadow-2xs overflow-hidden"
            :class="activeTabMeta.bgLight + ' ' + activeTabMeta.borderClass"
        >
            <div class="p-3.5 sm:p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-3">
                    <div 
                        class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                        :class="activeTabMeta.badgeBg + ' ' + activeTabMeta.textColor"
                    >
                        <span v-html="activeTabMeta.iconSvg" class="w-5 h-5 flex items-center justify-center"></span>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-white/80 border border-gray-200/60 shadow-2xs" :class="activeTabMeta.textColor">
                                Tab Aktif: {{ activeTabMeta.label }}
                            </span>
                            <span class="text-xs font-extrabold px-2 py-0.5 rounded-full bg-white shadow-2xs" :class="activeTabMeta.textColor">
                                {{ getLiveCount(activeTabMeta.key) }} Data
                            </span>
                            <span v-if="activeTabMeta.amountLabel" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-white/90 text-gray-700 border border-gray-200/50">
                                {{ activeTabMeta.amountLabel }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-700 mt-1 line-clamp-2 md:line-clamp-1">
                            <span class="font-semibold text-gray-900">{{ activeTabMeta.summary }}</span>
                            <span class="hidden sm:inline text-gray-600"> &mdash; {{ activeTabMeta.networkBrief }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                    <!-- Tombol Buka Panduan Lengkap -->
                    <button 
                        @click="openModal(activeTabMeta.key)"
                        class="px-3.5 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-800 rounded-xl text-xs font-bold shadow-2xs hover:shadow-xs transition-all flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span>Panduan Detail (11 Tab)</span>
                    </button>

                    <!-- Tombol Tutup Banner Kecil -->
                    <button 
                        @click="dismissInlineHelper" 
                        class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg transition-colors"
                        title="Sembunyikan banner bantuan ini"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- ── 2. MODAL BUKU PANDUAN LENGKAP 11 TAB ──────────────────────────────────── -->
        <div 
            v-if="isModalOpen" 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
            @click.self="closeModal"
        >
            <div class="bg-white rounded-3xl max-w-5xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-gray-100 overflow-hidden transform transition-all animate-fade-in">
                
                <!-- Modal Header -->
                <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-slate-50 via-white to-indigo-50/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-black text-gray-900">Buku Panduan 11 Tab Invoice Pelanggan</h3>
                                <span class="hidden sm:inline-flex text-[11px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Auto-Sync Realtime</span>
                            </div>
                            <p class="text-xs text-gray-500">Standar Operasional Prosedur (SOP), kriteria sistem, logika isolir, dan pengaruh ke MikroTik / RADIUS</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Mode View Toggle (Tab Detail vs Matriks) -->
                        <div class="hidden sm:flex bg-gray-100 p-1 rounded-xl text-xs font-semibold">
                            <button 
                                @click="modalView = 'tabs'" 
                                :class="['px-3 py-1 rounded-lg transition-all', modalView === 'tabs' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-gray-500 hover:text-gray-800']"
                            >
                                Detail Tab
                            </button>
                            <button 
                                @click="modalView = 'matrix'" 
                                :class="['px-3 py-1 rounded-lg transition-all', modalView === 'matrix' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-gray-500 hover:text-gray-800']"
                            >
                                Matriks Jaringan
                            </button>
                        </div>

                        <button 
                            @click="closeModal" 
                            class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex items-center justify-center transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Search & Mobile Sub-nav -->
                <div class="px-5 sm:px-6 py-2.5 bg-gray-50/80 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                    <div class="relative w-full sm:w-80">
                        <input 
                            v-model="modalSearch"
                            type="text" 
                            placeholder="Cari kata kunci: isolir, cron, cicil, lunas..." 
                            class="w-full pl-8 pr-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 shadow-2xs"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <div class="flex items-center justify-between w-full sm:w-auto gap-2">
                        <!-- Mobile Switcher Mode -->
                        <div class="flex sm:hidden bg-gray-200/70 p-0.5 rounded-lg text-[11px] font-semibold">
                            <button @click="modalView = 'tabs'" :class="['px-2.5 py-1 rounded-md', modalView === 'tabs' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-gray-600']">Detail Tab</button>
                            <button @click="modalView = 'matrix'" :class="['px-2.5 py-1 rounded-md', modalView === 'matrix' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-gray-600']">Matriks</button>
                        </div>

                        <div class="text-[11px] text-gray-500 font-medium">
                            Menampilkan <span class="font-bold text-gray-800">{{ filteredTabs.length }}</span> dari 11 Tab
                        </div>
                    </div>
                </div>

                <!-- Modal Body: TAB VIEW -->
                <div v-if="modalView === 'tabs'" class="flex-1 overflow-hidden flex flex-col md:flex-row">
                    <!-- Left Sidebar / Tab List (Responsive Scrollable) -->
                    <div class="w-full md:w-72 lg:w-80 border-b md:border-b-0 md:border-r border-gray-100 overflow-y-auto bg-slate-50/50 p-2 sm:p-3 shrink-0 max-h-48 md:max-h-full">
                        <div class="text-[11px] font-extrabold text-gray-400 uppercase tracking-wider px-2 py-1 mb-1">
                            Daftar 11 Tab Penagihan
                        </div>
                        <div class="space-y-1">
                            <button 
                                v-for="tab in filteredTabs" 
                                :key="tab.key"
                                @click="selectedModalTabKey = tab.key"
                                :class="[
                                    'w-full text-left px-3 py-2.5 rounded-xl transition-all flex items-center justify-between gap-2.5',
                                    selectedModalTabKey === tab.key 
                                        ? 'bg-white text-gray-900 shadow-sm border border-gray-200 font-bold' 
                                        : 'text-gray-600 hover:bg-white/60 hover:text-gray-900'
                                ]"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span 
                                        class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 text-xs"
                                        :class="tab.badgeBg + ' ' + tab.textColor"
                                        v-html="tab.iconSvg"
                                    ></span>
                                    <span class="text-xs truncate">{{ tab.label }}</span>
                                </div>
                                <span 
                                    class="text-[11px] font-extrabold px-2 py-0.5 rounded-full shrink-0"
                                    :class="selectedModalTabKey === tab.key ? tab.badgeBg + ' ' + tab.textColor : 'bg-gray-100 text-gray-600'"
                                >
                                    {{ getLiveCount(tab.key) }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Right Content / Tab Guide Detail -->
                    <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 bg-white">
                        <div v-if="currentSelectedTab" class="space-y-5">
                            <!-- Detail Header Card -->
                            <div class="p-4 sm:p-5 rounded-2xl border" :class="currentSelectedTab.bgLight + ' ' + currentSelectedTab.borderClass">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div 
                                            class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm"
                                            :class="currentSelectedTab.badgeBg + ' ' + currentSelectedTab.textColor"
                                            v-html="currentSelectedTab.iconSvg"
                                        ></div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-base sm:text-lg font-black text-gray-900">{{ currentSelectedTab.label }}</h4>
                                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-white shadow-2xs" :class="currentSelectedTab.textColor">
                                                    {{ getLiveCount(currentSelectedTab.key) }} Data
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-600 mt-0.5">{{ currentSelectedTab.subtitle }}</p>
                                        </div>
                                    </div>

                                    <!-- Quick Switch to this Tab -->
                                    <button 
                                        @click="applyTabFromGuide(currentSelectedTab.key)"
                                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5 self-start sm:self-auto"
                                    >
                                        <span>Buka Tab Ini di Tabel</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- 4 Grid Sections: Fungsi, Kriteria, Dampak Jaringan, Aksi -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Card 1: Fungsi & Tujuan Bisnis -->
                                <div class="p-4 rounded-2xl bg-gray-50/70 border border-gray-100 space-y-2">
                                    <div class="flex items-center gap-2 text-xs font-extrabold text-indigo-700 uppercase tracking-wider">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Tujuan & Fungsi Bisnis</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-700 leading-relaxed">{{ currentSelectedTab.goal }}</p>
                                </div>

                                <!-- Card 2: Kriteria & Logika Sistem -->
                                <div class="p-4 rounded-2xl bg-gray-50/70 border border-gray-100 space-y-2">
                                    <div class="flex items-center gap-2 text-xs font-extrabold text-blue-700 uppercase tracking-wider">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <span>Kriteria & Logika Sistem</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-700 leading-relaxed font-mono text-[12px] bg-white p-2.5 rounded-xl border border-gray-200/60">
                                        {{ currentSelectedTab.systemCriteria }}
                                    </p>
                                </div>

                                <!-- Card 3: Dampak ke Jaringan & RADIUS -->
                                <div class="p-4 rounded-2xl bg-gray-50/70 border border-gray-100 space-y-2">
                                    <div class="flex items-center gap-2 text-xs font-extrabold text-emerald-700 uppercase tracking-wider">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                                        <span>Status Koneksi & Mikrotik</span>
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-700 leading-relaxed">
                                        <div class="font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full" :class="currentSelectedTab.networkDotClass"></span>
                                            {{ currentSelectedTab.networkStatus }}
                                        </div>
                                        <p>{{ currentSelectedTab.networkDescription }}</p>
                                    </div>
                                </div>

                                <!-- Card 4: SOP & Aksi Operasional -->
                                <div class="p-4 rounded-2xl bg-gray-50/70 border border-gray-100 space-y-2">
                                    <div class="flex items-center gap-2 text-xs font-extrabold text-amber-700 uppercase tracking-wider">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Aksi & SOP Tim Kasir / Billing</span>
                                    </div>
                                    <ul class="text-xs sm:text-sm text-gray-700 space-y-1.5">
                                        <li v-for="(act, idx) in currentSelectedTab.actions" :key="idx" class="flex items-start gap-1.5">
                                            <span class="text-amber-500 font-bold shrink-0">✔</span>
                                            <span>{{ act }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Live Stat Summary Pill for this Tab -->
                            <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl flex flex-col sm:flex-row items-center justify-between text-xs text-slate-700 gap-2">
                                <div>
                                    <span class="font-bold text-slate-900">Perhitungan Realtime:</span>
                                    <span class="ml-1">{{ currentSelectedTab.calculationNote }}</span>
                                </div>
                                <div class="font-mono font-bold text-indigo-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                                    {{ currentSelectedTab.liveAmountText }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Body: MATRIX VIEW -->
                <div v-else class="flex-1 overflow-y-auto p-5 sm:p-6 bg-white space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Matriks Hubungan Status, Internet, dan Aksi Operasional</h4>
                            <p class="text-xs text-gray-500">Panduan lintas divisi untuk sinkronisasi Billing, Customer Service, dan Tim Jaringan / NOC</p>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-2xs">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider">
                                    <th class="px-4 py-3">Tab Menu</th>
                                    <th class="px-4 py-3">Status Invoice</th>
                                    <th class="px-4 py-3">Status Pelanggan</th>
                                    <th class="px-4 py-3">Koneksi Internet (RADIUS/Mikrotik)</th>
                                    <th class="px-4 py-3">Aksi Utama Tim Billing</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in matrixRows" :key="row.tab" class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 py-3 font-bold text-gray-900 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" :class="row.dotClass"></span>
                                        {{ row.tab }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-[11px] text-gray-700">{{ row.invoiceStatus }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="row.customerBadgeClass">
                                            {{ row.customerStatus }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-medium">{{ row.networkImpact }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ row.mainAction }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Panduan terhubung otomatis ke pengaturan siklus tagihan & modul FreeRADIUS / MikroTik.</span>
                    </div>
                    <button 
                        @click="closeModal" 
                        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition-all shadow-2xs"
                    >
                        Tutup Panduan
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    activeTab: {
        type: String,
        default: 'semua'
    },
    invoiceIssueDate: {
        type: [Number, String],
        default: 1
    }
});

const emit = defineEmits(['selectTab']);

const isModalOpen = ref(false);
const modalView = ref('tabs'); // 'tabs' | 'matrix'
const selectedModalTabKey = ref(props.activeTab || 'semua');
const modalSearch = ref('');
const showInlineHelper = ref(true);

// Sinkronkan tab yang dipilih di modal ketika activeTab dari luar berubah
watch(() => props.activeTab, (newTab) => {
    if (newTab) {
        selectedModalTabKey.value = newTab;
    }
});

onMounted(() => {
    const saved = localStorage.getItem('hide_invoice_inline_helper');
    if (saved === 'true') {
        showInlineHelper.value = false;
    }
});

function dismissInlineHelper() {
    showInlineHelper.value = false;
    localStorage.setItem('hide_invoice_inline_helper', 'true');
}

function openModal(tabKey = null) {
    if (tabKey) {
        selectedModalTabKey.value = tabKey;
    } else {
        selectedModalTabKey.value = props.activeTab || 'semua';
    }
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
}

function applyTabFromGuide(tabKey) {
    emit('selectTab', tabKey);
    closeModal();
}

function formatCurrency(val) {
    if (!val && val !== 0) return 'Rp 0';
    return 'Rp ' + Math.round(Number(val) || 0).toLocaleString('id-ID');
}

function getLiveCount(tabKey) {
    if (!props.stats) return 0;
    return props.stats[tabKey] ?? 0;
}

// ── DEFINISI DINAMIS 11 TAB BESERTA KRITERIA & SOP NYA ─────────────────────────
const tabDefinitions = computed(() => [
    {
        key: 'semua',
        label: 'Daftar Tagihan',
        subtitle: 'Seluruh tagihan pelanggan aktif pada siklus penagihan bulan ini',
        badgeBg: 'bg-gray-100',
        textColor: 'text-gray-800',
        bgLight: 'bg-slate-50/80',
        borderClass: 'border-slate-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
        summary: 'Pusat pantauan utama rekapitulasi seluruh invoice pelanggan pada periode aktif.',
        networkBrief: 'Mencakup pelanggan internet normal, dispensasi janji bayar, maupun yang terisolir.',
        goal: 'Menyajikan seluruh transaksi invoice yang terbit di bulan ini untuk pemantauan menyeluruh, pencarian massal, pencetakan kwitansi, dan export laporan Excel.',
        systemCriteria: 'issued_date <= cycleEnd AND billing_type != "prorata_future" (seluruh status: paid, unpaid, overdue, partial).',
        networkStatus: 'Bervariasi (Sesuai Status Pelanggan)',
        networkDotClass: 'bg-indigo-500',
        networkDescription: 'Pelanggan yang sudah bayar berstatus normal, sedangkan pelanggan belum bayar yang lewat tanggal jatuh tempo berpotensi masuk profil isolir.',
        actions: [
            'Pencarian invoice berdasarkan nama, alamat, paket, atau area pelanggan.',
            'Export daftar tagihan ke Excel / CSV untuk arsip administrasi.',
            'Cetak daftar tagihan atau cetak PDF massal.',
            'Filter rentang tanggal penerbitan invoice.'
        ],
        calculationNote: 'Total seluruh invoice yang aktif di siklus bulan ini.',
        liveAmountText: `Total Data: ${getLiveCount('semua')} Tagihan`,
        amountLabel: null
    },
    {
        key: 'belum_lunas',
        label: 'Belum Lunas (Menunggak)',
        subtitle: 'Invoice pelanggan aktif yang sama sekali belum dibayar (0% terbayar)',
        badgeBg: 'bg-rose-100',
        textColor: 'text-rose-700',
        bgLight: 'bg-rose-50/70',
        borderClass: 'border-rose-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        summary: 'Daftar invoice menunggak murni yang belum ada setoran pembayaran sama sekali.',
        networkBrief: 'Layanan internet masih aktif selama belum melewati tanggal isolir.',
        goal: 'Fokus penagihan bagi pelanggan yang belum membayar di awal bulan sebelum batas akhir isolir tiba.',
        systemCriteria: 'status IN ("unpaid", "overdue") AND customer.status = "active" AND issued_date <= cycleEnd.',
        networkStatus: 'Masih Aktif (Sebelum Jatuh Tempo)',
        networkDotClass: 'bg-amber-500',
        networkDescription: 'Jika tanggal hari ini belum melewati batas auto-isolir, internet pelanggan tetap berjalan normal. Begitu lewat tanggal isolir, sistem akan otomatis mengalihkan akun ke profil terisolir.',
        actions: [
            'Klik tombol "Bayar" untuk input pelunasan kasir atau transfer bank.',
            'Kirim pesan WhatsApp Reminder Tagihan berkala kepada pelanggan.',
            'Input "Janji Bayar" jika pelanggan meminta dispensasi tenggat waktu.'
        ],
        calculationNote: 'Total nominal invoice unpaid & overdue.',
        liveAmountText: `${formatCurrency(props.stats?.total_unpaid_amount)}`,
        amountLabel: `Total: ${formatCurrency(props.stats?.total_unpaid_amount)}`
    },
    {
        key: 'jatuh_tempo',
        label: 'Tagihan Jatuh Tempo',
        subtitle: 'Tagihan kritis yang telah melewati batas due date atau tanggal auto-isolir',
        badgeBg: 'bg-red-100',
        textColor: 'text-red-700',
        bgLight: 'bg-red-50/70',
        borderClass: 'border-red-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        summary: 'Invoice kritis siap isolir (melewati due date atau jadwal isolir otomatis).',
        networkBrief: 'Menjadi sasaran eksekusi cron auto-isolir RADIUS/MikroTik jika tidak ada Janji Bayar.',
        goal: 'Daftar prioritas darurat bagi tim penagihan untuk mencegah lonjakan piutang tak tertagih dan acuan isolir massal.',
        systemCriteria: 'status IN ("unpaid", "overdue", "partial") AND (isolation_at <= NOW() OR due_date < TODAY()).',
        networkStatus: 'Kritis / Siap Diisolir Otomatis',
        networkDotClass: 'bg-red-600',
        networkDescription: 'Koneksi internet pelanggan di tab ini akan diputus/dialihkan oleh cron isolir berkala ke IP Pool Isolir dengan kecepatan dibatasi atau halaman web peringatan.',
        actions: [
            'Prioritas penagihan langsung (telepon CS atau blast WA Peringatan Terakhir).',
            'Eksekusi Isolir Manual bagi pelanggan membandel.',
            'Pemberian dispensasi komitmen bayar melalui tombol Janji Bayar.'
        ],
        calculationNote: 'Nominal sisa tagihan unpaid + sisa piutang partial yang sudah jatuh tempo.',
        liveAmountText: `${formatCurrency(props.stats?.total_jatuh_tempo_amount)}`,
        amountLabel: `Kritis: ${formatCurrency(props.stats?.total_jatuh_tempo_amount)}`
    },
    {
        key: 'piutang',
        label: 'Piutang (Bayar Sebagian)',
        subtitle: 'Invoice yang sudah dicicil sebagian namun masih menyisakan sisa pembayaran',
        badgeBg: 'bg-amber-100',
        textColor: 'text-amber-700',
        bgLight: 'bg-amber-50/70',
        borderClass: 'border-amber-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        summary: 'Pelanggan yang melakukan cicilan bertahap (status partial).',
        networkBrief: 'Internet tetap aktif sesuai toleransi kasir hingga sisa tagihan dilunasi.',
        goal: 'Memantau sisa piutang riil dari invoice yang baru terbayar sebagian agar tetap teragendakan untuk pelunasan kedua.',
        systemCriteria: 'status = "partial" (jumlah terbayar > 0 DAN sisa tagihan > 0).',
        networkStatus: 'Aktif dengan Toleransi Cicilan',
        networkDotClass: 'bg-amber-500',
        networkDescription: 'Pelanggan telah menunjukkan itikad baik dengan mencicil sebagian, sehingga internet diizinkan aktif kecuali disuspend manual oleh admin.',
        actions: [
            'Input setoran pembayaran pelunasan berikutnya.',
            'Cetak nota tanda terima pembayaran bertahap.',
            'Pantau sisa piutang bersih di kartu Total Piutang.'
        ],
        calculationNote: 'Sisa kekurangan pokok tagihan dikurangi akumulasi cicilan yang sudah diverifikasi.',
        liveAmountText: `Sisa Bersih: ${formatCurrency(props.stats?.total_piutang_amount)}`,
        amountLabel: `Sisa: ${formatCurrency(props.stats?.total_piutang_amount)}`
    },
    {
        key: 'lunas',
        label: 'Lunas',
        subtitle: 'Arsip tagihan pelanggan yang sudah dibayar penuh 100% pada siklus ini',
        badgeBg: 'bg-emerald-100',
        textColor: 'text-emerald-700',
        bgLight: 'bg-emerald-50/70',
        borderClass: 'border-emerald-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        summary: 'Seluruh transaksi sukses yang sudah masuk ke kas / rekening bank.',
        networkBrief: 'Internet beroperasi normal dengan paket kecepatan penuh.',
        goal: 'Arsip transaksi bersih bulan berjalan dan dasar rekonsiliasi laporan kas masuk tim keuangan.',
        systemCriteria: 'status = "paid" AND issued_date BETWEEN cycleStart AND cycleEnd.',
        networkStatus: 'Aktif Penuh & Normal',
        networkDotClass: 'bg-emerald-500',
        networkDescription: 'Profil RADIUS normal, limit bandwidth MikroTik sesuai paket internet langganan.',
        actions: [
            'Cetak Struk Thermal (58mm/80mm) untuk pelanggan fisik.',
            'Cetak Invoice / Kwitansi PDF resmi ber-barcode.',
            'Kirim notifikasi otomatis ucapan terima kasih via WhatsApp Gateway.'
        ],
        calculationNote: 'Total akumulasi uang masuk kas/bank bulan ini.',
        liveAmountText: `Penerimaan: ${formatCurrency(props.stats?.total_paid_amount)}`,
        amountLabel: `Masuk: ${formatCurrency(props.stats?.total_paid_amount)}`
    },
    {
        key: 'prorata',
        label: 'Pelanggan Prorata',
        subtitle: 'Pelanggan baru tengah bulan yang tagihannya dijadwalkan pada siklus depan',
        badgeBg: 'bg-blue-100',
        textColor: 'text-blue-700',
        bgLight: 'bg-blue-50/70',
        borderClass: 'border-blue-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`,
        summary: 'Perhitungan tagihan proporsional hari pemakaian pelanggan baru.',
        networkBrief: 'Internet aktif sejak hari pertama aktivasi.',
        goal: 'Mencegah penagihan dobel bagi pelanggan baru yang aktivasi di luar tanggal 1 atau tengah siklus berjalan.',
        systemCriteria: 'billing_type = "prorata" AND issued_date > cycleEnd AND status != "cancelled".',
        networkStatus: 'Aktif Normal Masa Prorata',
        networkDotClass: 'bg-blue-500',
        networkDescription: 'Pelanggan baru langsung menikmati koneksi internet tanpa terganggu status penagihan siklus sebelumnya.',
        actions: [
            'Verifikasi perhitungan jumlah hari aktif pertama.',
            'Pemeriksaan jadwal terbit otomatis pada siklus billing bulan berikutnya.'
        ],
        calculationNote: 'Jumlah pelanggan baru masa prorata.',
        liveAmountText: `${getLiveCount('prorata')} Pelanggan`,
        amountLabel: null
    },
    {
        key: 'upgrade',
        label: 'Upgrade / Downgrade',
        subtitle: 'Invoice penyesuaian biaya karena perubahan paket langganan pelanggan',
        badgeBg: 'bg-purple-100',
        textColor: 'text-purple-700',
        bgLight: 'bg-purple-50/70',
        borderClass: 'border-purple-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>`,
        summary: 'Penagihan selisih biaya perubahan kecepatan internet di tengah bulan.',
        networkBrief: 'Profil kecepatan di RADIUS/MikroTik disinkronkan ke paket baru.',
        goal: 'Memastikan selisih harga saat pelanggan naik atau turun paket dihitung transparan dan tercatat rapi.',
        systemCriteria: 'billing_type IN ("upgrade", "downgrade") OR notes LIKE "%upgrade%" OR notes LIKE "%downgrade%".',
        networkStatus: 'Profil Kecepatan Baru Diperbarui',
        networkDotClass: 'bg-purple-500',
        networkDescription: 'Setelah pembayaran selisih diterima, CoA disconnect/reset secret dikirim ke router agar paket baru langsung aktif.',
        actions: [
            'Cek rincian selisih biaya paket lama dan paket baru.',
            'Terima pembayaran penyesuaian paket.',
            'Kirim perintah sinkronisasi profil bandwidth ke MikroTik.'
        ],
        calculationNote: 'Invoice dengan jenis transaksi perubahan paket.',
        liveAmountText: `${getLiveCount('upgrade')} Transaksi`,
        amountLabel: null
    },
    {
        key: 'janji_bayar',
        label: 'Janji Bayar',
        subtitle: 'Pelanggan menunggak yang diberi dispensasi tanggal komitmen pembayaran',
        badgeBg: 'bg-yellow-100',
        textColor: 'text-yellow-700',
        bgLight: 'bg-yellow-50/70',
        borderClass: 'border-yellow-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
        summary: 'Dispensasi khusus: Cron isolir tidak akan memutus koneksi selama tanggal komitmen belum lewat.',
        networkBrief: 'Internet tetap MENYALA normal selama dalam masa toleransi janji bayar.',
        goal: 'Menjaga kepuasan pelanggan loyal yang meminta penundaan bayar tanpa memutus internet mereka sebelum komitmen habis.',
        systemCriteria: 'promise_date IS NOT NULL AND status IN ("unpaid", "partial", "overdue") AND customer.status = "active".',
        networkStatus: 'DILINDUNGI DARI AUTO-ISOLIR',
        networkDotClass: 'bg-yellow-500',
        networkDescription: 'Cron auto-isolir mengecek kolom promise_date. Jika tanggal hari ini <= promise_date, akun dilewati dan koneksi internet tetap jalan normal.',
        actions: [
            'Pantau tanggal jatuh tempo komitmen janji bayar.',
            'Kirim pesan WhatsApp pengingat 1 hari sebelum janji bayar berakhir.',
            'Eksekusi pembayaran atau batalkan janji bayar jika komitmen dilanggar.'
        ],
        calculationNote: 'Total pelanggan yang memegang komitmen dispensasi bayar.',
        liveAmountText: `${getLiveCount('janji_bayar')} Komitmen`,
        amountLabel: null
    },
    {
        key: 'terisolir',
        label: 'Terisolir',
        subtitle: 'Pelanggan yang koneksi internetnya dialihkan karena tunggakan pembayaran',
        badgeBg: 'bg-rose-100',
        textColor: 'text-rose-700',
        bgLight: 'bg-rose-50/70',
        borderClass: 'border-rose-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>`,
        summary: 'Pelanggan nonaktif/suspend karena alasan keuangan (suspend_end IS NULL).',
        networkBrief: 'Internet dialihkan ke Landing Page Isolir atau IP Pool Isolir.',
        goal: 'Memisahkan pelanggan yang saat ini sedang dalam kondisi tidak bisa berinternet agar terfokus pada penagihan aktif.',
        systemCriteria: 'customer.status = "suspended" AND customer.suspend_end IS NULL AND status IN ("unpaid", "overdue", "partial").',
        networkStatus: 'TERISOLIR (IP Pool Isolir / Speed Dialihkan)',
        networkDotClass: 'bg-rose-600',
        networkDescription: 'Akun pelanggan berada di group isolir FreeRADIUS atau dinonaktifkan di PPPoE secret MikroTik. Begitu dibayar lunas, sistem otomatis meng-un-isolate dalam hitungan detik.',
        actions: [
            'Konfirmasi pelunasan tagihan pelanggan.',
            'Sistem otomatis Buka Isolir (Auto CoA RADIUS) saat invoice berstatus Lunas.',
            'Opsi Buka Isolir Darurat / Beri Janji Bayar jika ada kesepakatan baru.'
        ],
        calculationNote: 'Pelanggan aktif dalam kondisi terputus karena menunggak.',
        liveAmountText: `${getLiveCount('terisolir')} Pelanggan`,
        amountLabel: null
    },
    {
        key: 'stop_sementara',
        label: 'Stop Sementara (Cuti)',
        subtitle: 'Pelanggan yang mengajukan permohonan cuti resmi dengan rentang tanggal berakhir',
        badgeBg: 'bg-amber-100',
        textColor: 'text-amber-700',
        bgLight: 'bg-amber-50/70',
        borderClass: 'border-amber-200',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        summary: 'Cuti resmi berlangganan (misal mudik, dinas luar kota, renovasi rumah).',
        networkBrief: 'Internet dinonaktifkan sementara dan tagihan bulanan dibebaskan/ditangguhkan.',
        goal: 'Mengelola pelanggan yang cuti agar tidak terbit tagihan berjalan palsu dan tercatat tanggal reaktivasi pastinya.',
        systemCriteria: 'customer.status = "suspended" AND customer.suspend_end IS NOT NULL.',
        networkStatus: 'Nonaktif Sementara Masa Cuti',
        networkDotClass: 'bg-amber-500',
        networkDescription: 'Secret RADIUS di-disable sementara tanpa menghapus data topologi ODP dan material kabel dropcore.',
        actions: [
            'Pantau tanggal jatuh tempo berakhirnya masa cuti.',
            'Re-aktivasi layanan saat masa cuti selesai.',
            'Perpanjang masa cuti jika ada permohonan tertulis.'
        ],
        calculationNote: 'Pelanggan dengan izin cuti terdata.',
        liveAmountText: `${getLiveCount('stop_sementara')} Pelanggan Cuti`,
        amountLabel: null
    },
    {
        key: 'stop_permanen',
        label: 'Stop Permanen',
        subtitle: 'Rekap tagihan pelanggan yang sudah putus langganan resmi (Terminated / Dismantle)',
        badgeBg: 'bg-stone-200',
        textColor: 'text-stone-800',
        bgLight: 'bg-stone-100/80',
        borderClass: 'border-stone-300',
        iconSvg: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>`,
        summary: 'Pelanggan resmi berhenti berlangganan.',
        networkBrief: 'Akun login RADIUS dihapus, port ODP dilepas, dan modem ONT ditarik kembali.',
        goal: 'Arsip tagihan sisa bagi pelanggan yang sudah di-dismantle dan validasi penarikan aset perangkat ke gudang.',
        systemCriteria: 'customer.status = "terminated" AND issued_date BETWEEN cycleStart AND cycleEnd.',
        networkStatus: 'Putus Permanen / Akun Dihapus',
        networkDotClass: 'bg-stone-700',
        networkDescription: 'Akun pelanggan dihapus permanen dari server FreeRADIUS/Mikrotik. Port ODP otomatis dibebaskan kembali.',
        actions: [
            'Pemeriksaan pelunasan tagihan terakhir / denda terminasi.',
            'Sinkronisasi surat tugas pencabutan perangkat (modem ONT/Dropcore).',
            'Konfirmasi pengembalian material ke gudang logistik.'
        ],
        calculationNote: 'Total sisa tagihan dari pelanggan yang berhenti.',
        liveAmountText: `${getLiveCount('stop_permanen')} Pelanggan`,
        amountLabel: null
    }
]);

// Tab aktif yang saat ini tampil di halaman invoice
const activeTabMeta = computed(() => {
    const found = tabDefinitions.value.find(t => t.key === props.activeTab);
    return found || tabDefinitions.value[0];
});

// Tab yang sedang diklik & dibaca di dalam modal panduan
const currentSelectedTab = computed(() => {
    const found = tabDefinitions.value.find(t => t.key === selectedModalTabKey.value);
    return found || activeTabMeta.value;
});

// Filter pencarian tab di dalam modal
const filteredTabs = computed(() => {
    if (!modalSearch.value.trim()) return tabDefinitions.value;
    const q = modalSearch.value.toLowerCase().trim();
    return tabDefinitions.value.filter(t => {
        return t.label.toLowerCase().includes(q) ||
            t.subtitle.toLowerCase().includes(q) ||
            t.goal.toLowerCase().includes(q) ||
            t.systemCriteria.toLowerCase().includes(q) ||
            t.networkStatus.toLowerCase().includes(q) ||
            t.actions.some(a => a.toLowerCase().includes(q));
    });
});

// Matriks Perbandingan Status
const matrixRows = [
    {
        tab: 'Daftar Tagihan',
        dotClass: 'bg-gray-500',
        invoiceStatus: 'Semua Status (paid, unpaid, overdue, partial)',
        customerStatus: 'Aktif / Bervariasi',
        customerBadgeClass: 'bg-gray-100 text-gray-700',
        networkImpact: 'Normal / Terisolir (Sesuai status bayar)',
        mainAction: 'Monitoring komprehensif, cetak daftar, export Excel'
    },
    {
        tab: 'Belum Lunas',
        dotClass: 'bg-rose-500',
        invoiceStatus: 'unpaid, overdue (0% bayar)',
        customerStatus: 'Aktif',
        customerBadgeClass: 'bg-emerald-100 text-emerald-700',
        networkImpact: 'Normal (Jika belum lewat batas isolir)',
        mainAction: 'Kirim WA Reminder, input bayar kasir/transfer'
    },
    {
        tab: 'Jatuh Tempo',
        dotClass: 'bg-red-600',
        invoiceStatus: 'unpaid, overdue, partial (Lewat due date)',
        customerStatus: 'Aktif (Kritis)',
        customerBadgeClass: 'bg-red-100 text-red-700',
        networkImpact: 'Target Cron Auto-Isolir RADIUS',
        mainAction: 'Penagihan darurat, isolir manual, atau Janji Bayar'
    },
    {
        tab: 'Piutang',
        dotClass: 'bg-amber-500',
        invoiceStatus: 'partial (Mencicil sebagian)',
        customerStatus: 'Aktif',
        customerBadgeClass: 'bg-emerald-100 text-emerald-700',
        networkImpact: 'Aktif (Dispensasi cicilan)',
        mainAction: 'Input pelunasan sisa tagihan, cetak tanda terima'
    },
    {
        tab: 'Lunas',
        dotClass: 'bg-emerald-500',
        invoiceStatus: 'paid (100% lunas)',
        customerStatus: 'Aktif',
        customerBadgeClass: 'bg-emerald-100 text-emerald-700',
        networkImpact: 'Aktif Penuh Normal (Bandwidth penuh)',
        mainAction: 'Cetak kwitansi/struk thermal, kirim WA lunas'
    },
    {
        tab: 'Prorata',
        dotClass: 'bg-blue-500',
        invoiceStatus: 'prorata (Dijadwalkan siklus depan)',
        customerStatus: 'Baru / Aktif',
        customerBadgeClass: 'bg-blue-100 text-blue-700',
        networkImpact: 'Aktif sejak hari pemasangan pertama',
        mainAction: 'Validasi hari pemakaian pertama pelanggan baru'
    },
    {
        tab: 'Upgrade/Downgrade',
        dotClass: 'bg-purple-500',
        invoiceStatus: 'upgrade / downgrade (Selisih paket)',
        customerStatus: 'Aktif',
        customerBadgeClass: 'bg-purple-100 text-purple-700',
        networkImpact: 'Profil kecepatan dialihkan ke paket baru',
        mainAction: 'Terima selisih biaya & kirim CoA reset router'
    },
    {
        tab: 'Janji Bayar',
        dotClass: 'bg-yellow-500',
        invoiceStatus: 'unpaid, partial, overdue (promise_date terisi)',
        customerStatus: 'Aktif (Dispensasi)',
        customerBadgeClass: 'bg-yellow-100 text-yellow-800',
        networkImpact: 'KEBAL ISOLIR sebelum tanggal janji lewat',
        mainAction: 'Pantau tanggal janji & ingatkan H-1 sebelum lewat'
    },
    {
        tab: 'Terisolir',
        dotClass: 'bg-rose-600',
        invoiceStatus: 'unpaid, overdue, partial',
        customerStatus: 'Suspended (Menunggak)',
        customerBadgeClass: 'bg-rose-100 text-rose-800',
        networkImpact: 'TERISOLIR (Dialihkan ke web info/pool isolir)',
        mainAction: 'Terima pelunasan & Auto Un-Isolate via CoA'
    },
    {
        tab: 'Stop Cuti',
        dotClass: 'bg-amber-600',
        invoiceStatus: 'Tanpa tagihan berjalan / penangguhan',
        customerStatus: 'Suspended (Cuti Terjadwal)',
        customerBadgeClass: 'bg-amber-100 text-amber-800',
        networkImpact: 'Dinonaktifkan sementara tanpa cabut kabel',
        mainAction: 'Pantau tanggal selesai cuti untuk aktivasi kembali'
    },
    {
        tab: 'Stop Permanen',
        dotClass: 'bg-stone-700',
        invoiceStatus: 'Sisa tagihan akhir / denda terminasi',
        customerStatus: 'Terminated (Putus)',
        customerBadgeClass: 'bg-stone-200 text-stone-800',
        networkImpact: 'AKUN DIHAPUS & Port ODP dilepas ke gudang',
        mainAction: 'Tagihan final & Work Order bongkar modem ONT'
    }
];

defineExpose({
    openModal
});
</script>

