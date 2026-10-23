<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import AppLogo from '@/Components/AppLogo.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

// ── Live Network Clock & Latency Simulation ──
const currentTime = ref('');
let timerInterval = null;

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }) + ' WIB';
};

onMounted(() => {
    if (typeof document !== 'undefined') {
        document.body.classList.add('auth-device-scale');
        document.body.style.zoom = '100%';
    }
    updateClock();
    timerInterval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.classList.remove('auth-device-scale');
        document.body.style.zoom = '80%';
    }
    if (timerInterval) clearInterval(timerInterval);
});

// ── Role Segment Switcher ──
const currentTab = ref('staff'); // 'staff' | 'reseller' | 'affiliate' | 'customer'

const tabs = [
    {
        id: 'staff',
        label: 'Staf & Tim',
        shortLabel: 'Staf',
        color: 'blue',
    },
    {
        id: 'reseller',
        label: 'Mitra Biller',
        shortLabel: 'Biller',
        color: 'emerald',
    },
    {
        id: 'affiliate',
        label: 'Mitra Afiliasi',
        shortLabel: 'Afiliasi',
        color: 'amber',
    },
    {
        id: 'customer',
        label: 'Pelanggan',
        shortLabel: 'Pelanggan',
        color: 'indigo',
    },
];

const tabConfig = computed(() => {
    switch (currentTab.value) {
        case 'reseller':
            return {
                title: 'Portal Agen & Reseller',
                badge: 'Loket Resmi',
                badgeClass: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
                dotClass: 'bg-emerald-400 shadow-[0_0_10px_rgba(16,185,129,0.8)]',
                activeRing: 'focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15',
                activeTabBg: 'text-white bg-gradient-to-r from-emerald-600 to-teal-600 shadow-md shadow-emerald-900/40 font-semibold',
                btnClass: 'bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 shadow-lg shadow-emerald-600/20',
                topGlow: 'from-emerald-500 via-teal-400 to-cyan-400',
                btnText: 'Masuk Portal Mitra',
                desc: 'Akses loket pembayaran, cetak voucher WiFi & transaksi agen.',
                emailPlaceholder: 'mitra@domain.com / email agen',
                emailLabel: 'Email Mitra / Agen',
                hint: 'Gunakan akun yang telah diverifikasi admin ISP.',
                portalAccent: 'emerald',
            };
        case 'affiliate':
            return {
                title: 'Portal Mitra Afiliasi & Sales',
                badge: 'Mitra Afiliasi',
                badgeClass: 'bg-amber-500/10 text-amber-400 border-amber-500/25',
                dotClass: 'bg-amber-400 shadow-[0_0_10px_rgba(245,158,11,0.8)]',
                activeRing: 'focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15',
                activeTabBg: 'text-white bg-gradient-to-r from-amber-600 via-orange-600 to-amber-500 shadow-md shadow-amber-900/40 font-semibold',
                btnClass: 'bg-gradient-to-r from-amber-600 via-orange-600 to-amber-500 hover:from-amber-500 hover:to-orange-400 shadow-lg shadow-amber-600/20',
                topGlow: 'from-amber-500 via-orange-400 to-yellow-400',
                btnText: 'Masuk Portal Afiliasi',
                desc: 'Pantau komisi booking, pasif income & penarikan saldo affiliate.',
                emailPlaceholder: 'affiliate@domain.com / email terdaftar',
                emailLabel: 'Email Mitra Afiliasi',
                hint: 'Akses khusus agen sales & marketing affiliate.',
                portalAccent: 'amber',
            };
        case 'customer':
            return {
                title: 'Portal Mandiri Pelanggan',
                badge: 'Self-Care',
                badgeClass: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/25',
                dotClass: 'bg-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.8)]',
                activeRing: 'focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15',
                activeTabBg: 'text-white bg-gradient-to-r from-indigo-600 to-violet-600 shadow-md shadow-indigo-900/40 font-semibold',
                btnClass: 'bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-500 hover:from-indigo-500 hover:to-violet-400 shadow-lg shadow-indigo-600/20',
                topGlow: 'from-indigo-500 via-violet-400 to-purple-400',
                btnText: 'Masuk Portal Pelanggan',
                desc: 'Pantau status paket internet rumah, invoice & riwayat bantuan.',
                emailPlaceholder: 'email.pelanggan@domain.com',
                emailLabel: 'Email Pelanggan Terdaftar',
                hint: 'Gunakan email yang terdaftar pada sistem langganan.',
                portalAccent: 'indigo',
            };
        default:
            return {
                title: 'Portal Operasional Staf',
                badge: 'Internal Staf',
                badgeClass: 'bg-blue-500/10 text-sky-400 border-blue-500/25',
                dotClass: 'bg-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.8)]',
                activeRing: 'focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15',
                activeTabBg: 'text-white bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-600 shadow-md shadow-blue-900/40 font-semibold',
                btnClass: 'bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 hover:from-blue-500 hover:to-sky-400 shadow-lg shadow-blue-600/20',
                topGlow: 'from-blue-500 via-indigo-500 to-cyan-400',
                btnText: 'Masuk Portal Staf',
                desc: 'Dashboard operasional untuk Tim NOC, CS, Billing & Administrator.',
                emailPlaceholder: 'admin@isp.net / staf@perusahaan.com',
                emailLabel: 'Email Akun Resmi Staf',
                hint: 'Akses terbatas untuk personel berwenang.',
                portalAccent: 'blue',
            };
    }
});

// ── Cek Tagihan Instan Modal ──
const showCheckBillModal = ref(false);
const searchQuery = ref('');
const isSearching = ref(false);
const searchError = ref('');
const billResult = ref(null);

const openCheckBillModal = () => {
    showCheckBillModal.value = true;
    searchQuery.value = '';
    searchError.value = '';
    billResult.value = null;
};

const closeCheckBillModal = () => {
    showCheckBillModal.value = false;
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const handleCheckBill = async () => {
    const q = searchQuery.value.trim();
    if (!q || q.length < 3) {
        searchError.value = 'Silakan masukkan minimal 3 karakter ID Pelanggan atau No WhatsApp.';
        billResult.value = null;
        return;
    }

    isSearching.value = true;
    searchError.value = '';
    billResult.value = null;

    try {
        const response = await axios.get('/public/check-bill', {
            params: { q },
        });

        if (response.data.success) {
            billResult.value = response.data;
        } else {
            searchError.value = response.data.message || 'Data pelanggan tidak ditemukan.';
        }
    } catch (err) {
        searchError.value = 'Gagal menghubungi server. Silakan coba beberapa saat lagi.';
    } finally {
        isSearching.value = false;
    }
};
</script>

<template>
    <Head title="Masuk Portal Layanan — Next-Gen ISP Platform" />

    <div class="min-h-screen bg-[#080c14] text-slate-100 flex flex-col justify-between relative overflow-hidden font-sans select-none antialiased">
        <!-- ── SUBTLE MODERN BACKGROUND AMBIENT ── -->
        <div class="absolute inset-0 bg-subtle-grid opacity-30 pointer-events-none"></div>
        <div class="absolute -top-32 -left-32 w-[520px] h-[520px] bg-gradient-to-br from-blue-600/15 via-indigo-600/8 to-transparent rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute top-1/3 -right-32 w-[540px] h-[540px] bg-gradient-to-bl from-cyan-500/12 via-teal-600/6 to-transparent rounded-full blur-[150px] pointer-events-none"></div>
        <div class="absolute -bottom-32 left-1/4 w-[480px] h-[480px] bg-gradient-to-tr from-indigo-600/10 via-purple-600/8 to-transparent rounded-full blur-[130px] pointer-events-none"></div>

        <!-- ── TOP TELEMETRY RIBBON (SIMPLE & CERDAS) ── -->
        <header class="relative z-20 w-full px-4 sm:px-8 py-3 border-b border-white/[0.06] bg-slate-950/40 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto flex items-center justify-between text-xs">
                <!-- System Status Indicator -->
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px] font-medium tracking-wide">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>CORE NETWORK: ACTIVE</span>
                    </span>
                    <span class="hidden md:inline-flex items-center gap-1.5 text-slate-400 text-[11px]">
                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>Backbone FTTH: <strong>99.98% SLA</strong></span>
                    </span>
                </div>

                <!-- Clock & Security Tags -->
                <div class="flex items-center gap-3 text-slate-400">
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-300 bg-slate-900/60 px-2.5 py-0.5 rounded-full border border-white/[0.06]">
                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>256-BIT SSL SECURED</span>
                    </span>
                    <span class="font-mono text-slate-300 text-[11px] font-medium tracking-wide bg-slate-900/40 px-2.5 py-0.5 rounded-full border border-white/[0.04]">
                        {{ currentTime || '--:--:-- WIB' }}
                    </span>
                </div>
            </div>
        </header>

        <!-- ── MAIN TWO-PANEL INTERACTIVE CONTAINER ── -->
        <main class="relative z-10 flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-10">
            <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-14 items-center">
                
                <!-- ── LEFT PANEL: MODERN INTELLIGENT SHOWCASE ── -->
                <div class="hidden lg:flex lg:col-span-6 flex-col justify-center space-y-7 text-left">
                    <!-- Brand Identity & Badge -->
                    <div class="flex flex-col items-start space-y-4">
                        <!-- Next-Gen Badge di Paling Atas -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[11px] font-semibold tracking-wider uppercase backdrop-blur-md">
                            <span class="text-cyan-400">⚡</span>
                            <span>Next-Gen ISP Orchestration Engine</span>
                        </div>
                        
                        <!-- Logo Vizbill di BAWAH Badge Next-Gen -->
                        <div class="py-1">
                            <AppLogo context="login" />
                        </div>

                        <h1 class="text-3xl xl:text-4xl font-extrabold text-white tracking-tight leading-snug pt-1">
                            Platform Billing &amp; <br />
                            <span class="bg-gradient-to-r from-cyan-400 via-sky-300 to-indigo-300 bg-clip-text text-transparent">
                                Manajemen Jaringan Terpadu
                            </span>
                        </h1>
                        <p class="text-sm text-slate-400 leading-relaxed max-w-lg">
                            Sistem kendali operasional ISP terpadu: monitoring topologi FTTH, OLT/ODC/ODP mapping, otomasi tiket gangguan, billing pintar, voucher WiFi, hingga portal mandiri mitra.
                        </p>
                    </div>

                    <!-- 3 Refined Feature Highlights -->
                    <div class="space-y-3 max-w-lg">
                        <!-- Feature 1 -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/40 border border-white/[0.06] hover:border-cyan-500/30 hover:bg-slate-900/70 transition-all duration-300 group">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-white group-hover:text-cyan-300 transition-colors">FTTH &amp; ODP Real-Time Mapping</h3>
                                    <p class="text-xs text-slate-400 truncate">Monitoring redaman optik &amp; status port OLT sinkron.</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">
                                Live Sync
                            </span>
                        </div>

                        <!-- Feature 2 -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/40 border border-white/[0.06] hover:border-emerald-500/30 hover:bg-slate-900/70 transition-all duration-300 group">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-white group-hover:text-emerald-300 transition-colors">Smart Billing &amp; WhatsApp Blast</h3>
                                    <p class="text-xs text-slate-400 truncate">Invoice otomatis, rekonsiliasi mutasi bank &amp; auto isolir.</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                                Auto Flow
                            </span>
                        </div>

                        <!-- Feature 3 -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/40 border border-white/[0.06] hover:border-indigo-500/30 hover:bg-slate-900/70 transition-all duration-300 group">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-white group-hover:text-indigo-300 transition-colors">Granular Role Access Control</h3>
                                    <p class="text-xs text-slate-400 truncate">Hak akses aman untuk NOC, Teknisi, Biller &amp; Keuangan.</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                RBAC Pro
                            </span>
                        </div>
                    </div>

                    <!-- Mini Telemetry Meter -->
                    <div class="pt-1 flex items-center gap-3 text-xs text-slate-400">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900/60 border border-white/[0.05]">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span class="text-slate-400">Latency: <strong class="text-slate-200">&lt; 2 ms</strong></span>
                        </div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900/60 border border-white/[0.05]">
                            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                            <span class="text-slate-400">Radius: <strong class="text-slate-200">Synced</strong></span>
                        </div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900/60 border border-white/[0.05]">
                            <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                            <span class="text-slate-400">Build: <strong class="text-slate-200">v2.8 Enterprise</strong></span>
                        </div>
                    </div>
                </div>

                <!-- ── RIGHT PANEL: SIMPLE & CERDAS AUTH CARD ── -->
                <div class="w-full lg:col-span-6 flex flex-col items-center">
                    
                    <!-- Mobile Logo Header (visible only on small screens) -->
                    <div class="lg:hidden mb-6 text-center flex flex-col items-center">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[11px] font-medium tracking-wide mb-3">
                            <span class="text-cyan-400">⚡</span>
                            <span>Next-Gen ISP Orchestration Engine</span>
                        </div>
                        <div class="py-1 mb-2">
                            <AppLogo context="login" />
                        </div>
                        <h2 class="text-base font-bold text-white tracking-tight">Portal Akses Terpadu</h2>
                        <p class="text-xs text-slate-400">Billing &amp; Operasional ISP Management</p>
                    </div>

                    <!-- Modern Glass Card Container -->
                    <div class="w-full max-w-[460px] bg-slate-900/70 backdrop-blur-2xl rounded-3xl border border-white/[0.08] shadow-[0_24px_70px_-15px_rgba(0,0,0,0.8),0_0_50px_-10px_rgba(59,130,246,0.12)] relative overflow-hidden transition-all duration-300">
                        
                        <!-- Top Ambient Glowing Line -->
                        <div 
                            class="h-[2px] w-full bg-gradient-to-r transition-all duration-500" 
                            :class="tabConfig.topGlow"
                        ></div>

                        <!-- Segmented Pill Tabs (Clean & Cerdas 4 Tabs) -->
                        <div class="p-3 sm:p-3.5 bg-slate-950/60 border-b border-white/[0.06]">
                            <div class="bg-slate-950/90 p-1 rounded-2xl border border-white/[0.06] grid grid-cols-4 gap-1 shadow-inner">
                                <button
                                    v-for="t in tabs"
                                    :key="t.id"
                                    type="button"
                                    @click="currentTab = t.id"
                                    class="relative flex items-center justify-center gap-1 py-2 px-1.5 rounded-xl text-[11px] sm:text-xs font-medium transition-all duration-200 focus:outline-none"
                                    :class="[
                                        currentTab === t.id
                                            ? tabConfig.activeTabBg
                                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'
                                    ]"
                                    :title="t.label"
                                >
                                    <!-- Animated Dot Indicator for Active Tab -->
                                    <span
                                        v-if="currentTab === t.id"
                                        class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                        :class="tabConfig.dotClass"
                                    ></span>

                                    <!-- Clean Icons per Tab -->
                                    <svg v-if="t.id === 'staff'" class="w-3.5 h-3.5 shrink-0 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <svg v-else-if="t.id === 'reseller'" class="w-3.5 h-3.5 shrink-0 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <svg v-else-if="t.id === 'affiliate'" class="w-3.5 h-3.5 shrink-0 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 shrink-0 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>

                                    <span class="truncate">{{ t.shortLabel }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 sm:p-7 space-y-5">
                            
                            <!-- Context Strip Header -->
                            <div class="flex items-center justify-between gap-3 pb-3.5 border-b border-white/[0.06]">
                                <div class="min-w-0">
                                    <h2 class="text-sm font-bold text-white tracking-tight">
                                        {{ tabConfig.title }}
                                    </h2>
                                    <p class="text-xs text-slate-400 mt-0.5 truncate">
                                        {{ tabConfig.desc }}
                                    </p>
                                </div>
                                <span class="shrink-0 px-2.5 py-0.5 text-[11px] font-semibold rounded-full border" :class="tabConfig.badgeClass">
                                    {{ tabConfig.badge }}
                                </span>
                            </div>

                            <!-- Flash Message Status -->
                            <div v-if="status" class="text-xs font-medium text-emerald-300 bg-emerald-950/50 p-3 rounded-xl border border-emerald-500/25 flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ status }}</span>
                            </div>

                            <!-- Main Auth Form -->
                            <form class="space-y-4" @submit.prevent="submit">
                                <!-- Email Input -->
                                <div>
                                    <label for="email" class="block text-xs font-medium text-slate-300 mb-1.5">
                                        {{ tabConfig.emailLabel }}
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                            </svg>
                                        </div>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            name="email"
                                            type="email"
                                            autocomplete="email"
                                            required
                                            :placeholder="tabConfig.emailPlaceholder"
                                            class="block w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-700/60 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:bg-slate-950 focus:ring-2 transition duration-200"
                                            :class="tabConfig.activeRing"
                                        />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400 font-medium">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <!-- Password Input -->
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="password" class="block text-xs font-medium text-slate-300">
                                            Kata Sandi
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            name="password"
                                            :type="showPassword ? 'text' : 'password'"
                                            autocomplete="current-password"
                                            required
                                            placeholder="••••••••••••"
                                            class="block w-full pl-10 pr-11 py-2.5 bg-slate-950/70 border border-slate-700/60 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:bg-slate-950 focus:ring-2 transition duration-200 font-mono tracking-wider"
                                            :class="tabConfig.activeRing"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 focus:outline-none transition-colors"
                                            tabindex="-1"
                                            :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                                        >
                                            <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-400 font-medium">
                                        {{ form.errors.password }}
                                    </p>
                                </div>

                                <!-- Checkbox & Hint -->
                                <div class="flex items-center justify-between text-xs pt-0.5">
                                    <label class="flex items-center cursor-pointer select-none text-slate-300 hover:text-white transition-colors">
                                        <input
                                            id="remember_me"
                                            v-model="form.remember"
                                            name="remember"
                                            type="checkbox"
                                            class="h-4 w-4 rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 transition duration-150 cursor-pointer"
                                        />
                                        <span class="ml-2 font-normal">Ingat perangkat</span>
                                    </label>

                                    <span class="text-[11px] text-slate-400 italic">
                                        {{ tabConfig.hint }}
                                    </span>
                                </div>

                                <!-- Submit Button with Clean Interactive Glow -->
                                <div class="pt-1.5">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full flex items-center justify-center py-3 px-4 rounded-xl text-sm font-semibold text-white shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed group relative overflow-hidden"
                                        :class="tabConfig.btnClass"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2.5 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ tabConfig.btnText }}</span>
                                    </button>
                                </div>
                            </form>

                            <!-- ── CONTEXTUAL FOOTER ACTIONS (CLEAN & CERDAS) ── -->
                            <div class="pt-3 border-t border-white/[0.06] space-y-2.5">
                                <!-- Mode Reseller -->
                                <div v-if="currentTab === 'reseller'" class="flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <span>🏪</span>
                                        <span>Belum bermitra dengan kami?</span>
                                    </div>
                                    <Link
                                        :href="route ? route('register-reseller') : '/register-reseller'"
                                        class="inline-flex items-center gap-1 font-semibold text-emerald-400 hover:text-emerald-300 hover:underline transition-colors whitespace-nowrap"
                                    >
                                        <span>Daftar Mitra Mandiri</span>
                                        <span>&rarr;</span>
                                    </Link>
                                </div>

                                <!-- Mode Affiliate -->
                                <div v-else-if="currentTab === 'affiliate'" class="flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <span class="text-amber-400">💼</span>
                                        <span>Belum jadi Mitra Afiliasi?</span>
                                    </div>
                                    <Link
                                        href="/register-affiliate"
                                        class="inline-flex items-center gap-1 font-semibold text-amber-400 hover:text-amber-300 hover:underline transition-colors whitespace-nowrap"
                                    >
                                        <span>Daftar Mitra Afiliasi</span>
                                        <span>&rarr;</span>
                                    </Link>
                                </div>

                                <!-- Mode Pelanggan -->
                                <div v-else-if="currentTab === 'customer'" class="flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <span class="text-amber-400">⚡</span>
                                        <span>Cek tagihan tanpa login?</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openCheckBillModal"
                                        class="inline-flex items-center gap-1 font-semibold text-indigo-400 hover:text-indigo-300 hover:underline transition-colors whitespace-nowrap"
                                    >
                                        <span>Cek Tagihan Instan</span>
                                        <span>&rarr;</span>
                                    </button>
                                </div>

                                <!-- Mode Staff -->
                                <div v-else class="flex items-center justify-between gap-3 text-xs text-slate-400">
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                        <svg class="w-3.5 h-3.5 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span>Sistem Terenkripsi &amp; Diaudit</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openCheckBillModal"
                                        class="text-[11px] text-slate-400 hover:text-sky-400 font-semibold transition-colors whitespace-nowrap"
                                    >
                                        Cek Tagihan Publik &rarr;
                                    </button>
                                </div>

                                <!-- Marketing Affiliate Banner (Refined Micro-Banner for non-affiliate tabs) -->
                                <div v-if="currentTab !== 'affiliate'" class="pt-1">
                                    <div class="flex items-center justify-between gap-2 text-xs bg-slate-950/40 p-2.5 rounded-xl border border-white/[0.05] backdrop-blur-md">
                                        <div class="flex items-center gap-2 text-slate-300">
                                            <span class="text-amber-400">💼</span>
                                            <span class="text-[11px]">Hasilkan komisi pasif bersama <strong>Mitra Afiliasi</strong></span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="currentTab = 'affiliate'"
                                            class="inline-flex items-center gap-1 font-medium text-amber-300 hover:text-white whitespace-nowrap text-[11px] bg-amber-500/10 hover:bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/25 transition-all shadow-xs"
                                        >
                                            <span>Login / Gabung</span>
                                            <span>&rarr;</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Global Copyright -->
                    <p class="mt-4 text-center text-xs text-slate-500">
                        &copy; {{ new Date().getFullYear() }} VIRUZS.ISP Infrastructure &amp; Billing Management. All rights reserved.
                    </p>
                </div>

            </div>
        </main>

        <!-- ── FOOTER BAR ── -->
        <footer class="relative z-20 w-full px-4 sm:px-8 py-3 border-t border-white/[0.06] bg-slate-950/40 backdrop-blur-xl text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>VIRUZS - Smart Billing, Smarter Business.</span>
                <div class="flex items-center gap-4 text-[11px]">
                    <span class="hover:text-slate-300 transition-colors cursor-pointer">Dokumentasi</span>
                    <span class="hover:text-slate-300 transition-colors cursor-pointer">Bantuan Teknis</span>
                    <span class="hover:text-slate-300 transition-colors cursor-pointer">Status Jaringan</span>
                </div>
            </div>
        </footer>

        <!-- ── MODAL CEK TAGIHAN INSTAN PELANGGAN ── -->
        <div
            v-if="showCheckBillModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
            @click.self="closeCheckBillModal"
        >
            <div class="bg-slate-900 border border-white/[0.08] rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden transform transition-all animate-fade-in-up">
                <!-- Modal Header -->
                <div class="bg-slate-950/70 px-6 py-4 border-b border-white/[0.06] text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Cek Tagihan Instan Pelanggan</h3>
                            <p class="text-[11px] text-slate-400">Periksa status layanan &amp; total tagihan tanpa login</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeCheckBillModal"
                        class="text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg p-1.5 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <!-- Search Input Box -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            ID Pelanggan atau Nomor WhatsApp
                        </label>
                        <form @submit.prevent="handleCheckBill" class="flex gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Contoh: CUST-0012 atau 08123456789"
                                    class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-950 border border-slate-700/60 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition duration-150 font-mono"
                                    autofocus
                                />
                            </div>
                            <button
                                type="submit"
                                :disabled="isSearching"
                                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white rounded-xl text-xs font-semibold shadow-md transition-all flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isSearching" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ isSearching ? 'Mencari...' : 'Periksa' }}</span>
                            </button>
                        </form>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="searchError" class="p-3 bg-rose-950/40 border border-rose-500/25 text-rose-300 text-xs rounded-xl flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ searchError }}</span>
                    </div>

                    <!-- Result Breakdown Card -->
                    <div v-if="billResult" class="space-y-4 pt-1">
                        <!-- Customer Info Header -->
                        <div class="p-4 bg-slate-950/80 border border-white/[0.06] rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Data Pelanggan</span>
                                <span
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize"
                                    :class="billResult.customer.status === 'active' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25' : 'bg-amber-500/15 text-amber-400 border border-amber-500/25'"
                                >
                                    {{ billResult.customer.status === 'active' ? 'Layanan Aktif' : billResult.customer.status }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-500 block text-[11px]">Nama Pelanggan</span>
                                    <span class="font-bold text-white text-sm">{{ billResult.customer.name }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[11px]">ID Pelanggan</span>
                                    <span class="font-mono font-semibold text-cyan-400">{{ billResult.customer.customer_code }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[11px]">Paket Layanan</span>
                                    <span class="font-medium text-slate-300">{{ billResult.customer.package_name }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[11px]">Wilayah / Area</span>
                                    <span class="font-medium text-slate-300">{{ billResult.customer.area }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Invoices List -->
                        <div v-if="billResult.invoices && billResult.invoices.length > 0" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-300">Tagihan Belum Lunas ({{ billResult.invoices.length }})</h4>
                                <span class="text-xs font-bold text-rose-400">
                                    Total: {{ formatRupiah(billResult.total_unpaid) }}
                                </span>
                            </div>

                            <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                                <div
                                    v-for="(inv, idx) in billResult.invoices"
                                    :key="idx"
                                    class="p-3 bg-rose-950/20 border border-rose-500/25 rounded-xl flex items-center justify-between"
                                >
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-xs font-bold text-white">{{ inv.invoice_number }}</span>
                                            <span class="text-[10px] bg-rose-500/20 text-rose-300 border border-rose-500/30 px-1.5 py-0.5 rounded font-medium uppercase">
                                                {{ inv.status }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-1">
                                            Periode: <span class="font-medium text-slate-200">{{ inv.period_label || '-' }}</span> &bull; Jatuh Tempo: <span class="font-medium text-rose-300">{{ inv.due_date }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col items-end gap-1.5">
                                        <span class="block text-xs font-bold text-white font-mono">{{ formatRupiah(inv.remaining) }}</span>
                                        <a
                                            v-if="inv.id"
                                            :href="'/payment-gateway/pay/' + inv.id"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-bold shadow-xs transition-all hover:scale-105"
                                        >
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                            Bayar QRIS
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Guidance Box -->
                            <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-200 text-xs space-y-1">
                                <div class="font-semibold flex items-center gap-1 text-[11px]">
                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Petunjuk Pembayaran:</span>
                                </div>
                                <p class="text-[11px] leading-relaxed text-amber-300/90">
                                    Pembayaran dapat dilakukan via transfer Bank / QRIS resmi perusahaan, atau tunai melalui <strong>Loket Mitra / Reseller</strong> terdekat.
                                </p>
                            </div>
                        </div>

                        <!-- No Unpaid Invoices -->
                        <div v-else class="p-5 text-center bg-emerald-950/25 border border-emerald-500/25 rounded-2xl space-y-2">
                            <div class="w-10 h-10 bg-emerald-500/15 text-emerald-400 rounded-full flex items-center justify-center mx-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h4 class="text-xs font-bold text-emerald-300">Tidak Ada Tagihan Tertunggak!</h4>
                            <p class="text-[11px] text-emerald-400/90 max-w-xs mx-auto">
                                Semua tagihan langganan internet Anda telah lunas. Terima kasih atas kepercayaan Anda.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-slate-950 border-t border-white/[0.06] flex justify-end">
                    <button
                        type="button"
                        @click="closeCheckBillModal"
                        class="px-4 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-subtle-grid {
    background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 30%, transparent 85%);
    -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 30%, transparent 85%);
}
</style>
