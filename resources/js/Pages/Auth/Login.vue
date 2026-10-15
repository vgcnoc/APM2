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
    updateClock();
    timerInterval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// ── Role Segment Switcher ──
const currentTab = ref('staff'); // 'staff' | 'reseller' | 'customer'

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
        shortLabel: 'Mitra',
        color: 'emerald',
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
                badgeClass: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
                dotClass: 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]',
                activeRing: 'focus:border-emerald-500 focus:ring-emerald-500/25',
                activeTabBg: 'text-white bg-gradient-to-r from-emerald-600 to-teal-600 shadow-lg shadow-emerald-600/30 font-bold',
                btnClass: 'bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-500 shadow-emerald-500/25',
                topGlow: 'from-emerald-500 via-teal-400 to-cyan-400',
                btnText: 'Masuk Portal Mitra',
                desc: 'Akses khusus loket pembayaran, cetak voucher WiFi & transaksi agen.',
                emailPlaceholder: 'mitra@domain.com / email agen',
                emailLabel: 'Email Mitra / Agen',
                hint: 'Gunakan akun yang telah disetujui admin ISP.',
                portalAccent: 'emerald',
            };
        case 'customer':
            return {
                title: 'Portal Mandiri Pelanggan',
                badge: 'Self-Care',
                badgeClass: 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30',
                dotClass: 'bg-indigo-400 shadow-[0_0_8px_rgba(129,140,248,0.8)]',
                activeRing: 'focus:border-indigo-500 focus:ring-indigo-500/25',
                activeTabBg: 'text-white bg-gradient-to-r from-indigo-600 to-purple-600 shadow-lg shadow-indigo-600/30 font-bold',
                btnClass: 'bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-purple-500 shadow-indigo-500/25',
                topGlow: 'from-indigo-500 via-purple-400 to-pink-500',
                btnText: 'Masuk Portal Pelanggan',
                desc: 'Pantau status paket internet rumah, riwayat invoice & pengajuan tiket.',
                emailPlaceholder: 'email.pelanggan@domain.com',
                emailLabel: 'Email Pelanggan Terdaftar',
                hint: 'Gunakan email yang terdaftar pada sistem langganan Anda.',
                portalAccent: 'indigo',
            };
        default:
            return {
                title: 'Portal Operasional Staf',
                badge: 'Internal Staf',
                badgeClass: 'bg-blue-500/15 text-cyan-400 border-cyan-500/30',
                dotClass: 'bg-cyan-400 shadow-[0_0_8px_rgba(34,211,238,0.8)]',
                activeRing: 'focus:border-cyan-500 focus:ring-cyan-500/25',
                activeTabBg: 'text-white bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 shadow-lg shadow-blue-600/30 font-bold',
                btnClass: 'bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 shadow-cyan-500/25',
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

    <div class="min-h-screen bg-[#070b14] text-slate-100 flex flex-col justify-between relative overflow-hidden font-sans select-none antialiased">
        <!-- ── DYNAMIC CYBER BACKGROUND EFFECTS ── -->
        <!-- 1. Geometric Grid Canvas -->
        <div class="absolute inset-0 bg-cyber-grid opacity-35 pointer-events-none"></div>

        <!-- 2. Radial Ambient Glow Spheres -->
        <div class="absolute -top-40 -left-40 w-[600px] h-[600px] bg-gradient-to-br from-blue-600/20 via-indigo-600/10 to-transparent rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute top-1/2 -right-40 w-[650px] h-[650px] bg-gradient-to-bl from-cyan-500/15 via-teal-600/10 to-transparent rounded-full blur-[150px] pointer-events-none"></div>
        <div class="absolute -bottom-40 left-1/3 w-[500px] h-[500px] bg-gradient-to-tr from-purple-600/15 via-indigo-600/10 to-transparent rounded-full blur-[130px] pointer-events-none"></div>

        <!-- ── TOP TELEMETRY RIBBON ── -->
        <header class="relative z-20 w-full px-4 sm:px-8 py-3.5 border-b border-slate-800/80 bg-slate-950/60 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto flex items-center justify-between text-xs">
                <!-- System Status Indicator -->
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 text-[11px] font-semibold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 absolute"></span>
                        <span>CORE NETWORK: ACTIVE</span>
                    </span>
                    <span class="hidden md:inline-flex items-center gap-1.5 text-slate-400 text-[11px]">
                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Backbone FTTH: 99.98% SLA</span>
                    </span>
                </div>

                <!-- Clock & Security Tags -->
                <div class="flex items-center gap-3 text-slate-400">
                    <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-mono text-cyan-300 bg-cyan-950/40 px-2 py-0.5 rounded border border-cyan-800/40">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>256-BIT SSL SECURED</span>
                    </span>
                    <span class="font-mono text-slate-300 text-[11px] font-medium tracking-wider">
                        {{ currentTime || '--:--:-- WIB' }}
                    </span>
                </div>
            </div>
        </header>

        <!-- ── MAIN TWO-PANEL INTERACTIVE CONTAINER ── -->
        <main class="relative z-10 flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- ── LEFT PANEL: FUTURISTIC SHOWCASE (DESKTOP) ── -->
                <div class="hidden lg:flex lg:col-span-6 flex-col justify-center space-y-6 text-left">
                    <!-- Brand Identity & Badge -->
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold uppercase tracking-wider backdrop-blur-md">
                            <span class="text-cyan-400">⚡</span>
                            <span>Next-Gen ISP Orchestration Engine</span>
                        </div>
                        
                        <!-- High-Tech Glass Logo Wrapper -->
                        <div class="inline-block p-3 rounded-2xl bg-slate-900/60 border border-slate-700/50 backdrop-blur-xl shadow-xl">
                            <AppLogo context="login" />
                        </div>

                        <h1 class="text-3xl xl:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            Platform Billing &amp; <br />
                            <span class="bg-gradient-to-r from-cyan-400 via-indigo-300 to-purple-400 bg-clip-text text-transparent">
                                Manajemen Jaringan Terpadu
                            </span>
                        </h1>
                        <p class="text-sm text-slate-300 leading-relaxed max-w-lg">
                            Sistem kendali operasional ISP terlengkap: monitoring topologi FTTH, OLT/ODC/ODP mapping, manajemen tiket gangguan, otomatisasi billing, cetak voucher WiFi, hingga portal mandiri mitra.
                        </p>
                    </div>

                    <!-- 3 Futuristic Feature Cards -->
                    <div class="space-y-3 max-w-lg">
                        <!-- Feature 1 -->
                        <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-xl hover:border-cyan-500/40 hover:bg-slate-900/80 transition-all duration-300 group">
                            <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-white group-hover:text-cyan-300 transition-colors">FTTH &amp; ODP Real-Time Mapping</h3>
                                <p class="text-xs text-slate-400">Sinkronisasi status port, redaman Rx optik, dan auto-isolir PPPoE.</p>
                            </div>
                        </div>

                        <!-- Feature 2 -->
                        <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-xl hover:border-emerald-500/40 hover:bg-slate-900/80 transition-all duration-300 group">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-white group-hover:text-emerald-300 transition-colors">Smart Billing &amp; WhatsApp Blast</h3>
                                <p class="text-xs text-slate-400">Pengiriman invoice otomatis, janji bayar, dan rekonsiliasi mutasi bank.</p>
                            </div>
                        </div>

                        <!-- Feature 3 -->
                        <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-xl hover:border-purple-500/40 hover:bg-slate-900/80 transition-all duration-300 group">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-white group-hover:text-purple-300 transition-colors">Granular Role-Based Access Control</h3>
                                <p class="text-xs text-slate-400">Multi-tier role akses untuk NOC, Teknisi Lapangan, CS, Finance &amp; HRD.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mini Telemetry Meter -->
                    <div class="pt-2 flex items-center gap-6 text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span>Latency: <strong>&lt; 2 ms</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
                            <span>Radius Engine: <strong>Synchronized</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                            <span>Build: <strong>v2.8 Enterprise</strong></span>
                        </div>
                    </div>
                </div>

                <!-- ── RIGHT PANEL: CYBER AUTH CARD ── -->
                <div class="w-full lg:col-span-6 flex flex-col items-center">
                    
                    <!-- Mobile Logo Header (visible only on small screens) -->
                    <div class="lg:hidden mb-5 text-center flex flex-col items-center">
                        <div class="p-2.5 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-xl inline-block mb-2">
                            <AppLogo context="login" />
                        </div>
                        <h2 class="text-lg font-bold text-white tracking-tight">Portal Akses Terpadu</h2>
                        <p class="text-xs text-slate-400">Billing &amp; Operasional ISP Management</p>
                    </div>

                    <!-- Main Glass Card Container -->
                    <div class="w-full max-w-[480px] bg-slate-900/80 backdrop-blur-2xl rounded-3xl border border-slate-700/60 shadow-[0_0_60px_-15px_rgba(99,102,241,0.25)] relative overflow-hidden transition-all duration-300">
                        
                        <!-- Top Ambient Glowing Neon Line -->
                        <div 
                            class="h-[3px] w-full bg-gradient-to-r transition-all duration-500" 
                            :class="tabConfig.topGlow"
                        ></div>

                        <!-- Segmented Pill Tabs -->
                        <div class="p-3 sm:p-4 bg-slate-950/60 border-b border-slate-800/80">
                            <div class="bg-slate-900/90 p-1.5 rounded-2xl border border-slate-800 grid grid-cols-3 gap-1.5 shadow-inner">
                                <button
                                    v-for="t in tabs"
                                    :key="t.id"
                                    type="button"
                                    @click="currentTab = t.id"
                                    class="relative flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs sm:text-[13px] font-medium transition-all duration-200 focus:outline-none"
                                    :class="[
                                        currentTab === t.id
                                            ? tabConfig.activeTabBg
                                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'
                                    ]"
                                >
                                    <!-- Animated Dot Indicator -->
                                    <span
                                        v-if="currentTab === t.id"
                                        class="w-1.5 h-1.5 rounded-full flex-shrink-0 animate-ping"
                                        :class="tabConfig.dotClass"
                                    ></span>

                                    <!-- Icons per Tab -->
                                    <svg v-if="t.id === 'staff'" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <svg v-else-if="t.id === 'reseller'" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>

                                    <span class="truncate">{{ t.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 sm:p-8 space-y-6">
                            
                            <!-- Context Strip Header -->
                            <div class="flex items-center justify-between gap-2 pb-4 border-b border-slate-800/80">
                                <div class="min-w-0">
                                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                                        <span>{{ tabConfig.title }}</span>
                                    </h2>
                                    <p class="text-xs text-slate-400 mt-0.5 truncate">
                                        {{ tabConfig.desc }}
                                    </p>
                                </div>
                                <span class="shrink-0 px-2.5 py-1 text-[11px] font-semibold rounded-full border" :class="tabConfig.badgeClass">
                                    {{ tabConfig.badge }}
                                </span>
                            </div>

                            <!-- Flash Message Status -->
                            <div v-if="status" class="text-xs font-medium text-emerald-300 bg-emerald-950/60 p-3 rounded-xl border border-emerald-500/30 flex items-center gap-2 animate-fade-in-up">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ status }}</span>
                            </div>

                            <!-- Main Auth Form -->
                            <form class="space-y-4" @submit.prevent="submit">
                                <!-- Email Input -->
                                <div>
                                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                        {{ tabConfig.emailLabel }}
                                    </label>
                                    <div class="relative rounded-xl">
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
                                            class="block w-full pl-10 pr-4 py-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:bg-slate-950 focus:ring-2 transition duration-200"
                                            :class="tabConfig.activeRing"
                                        />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400 font-medium">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <!-- Password Input -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                            Kata Sandi
                                        </label>
                                    </div>
                                    <div class="relative rounded-xl">
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
                                            class="block w-full pl-10 pr-11 py-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:bg-slate-950 focus:ring-2 transition duration-200 font-mono tracking-wider"
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
                                <div class="flex items-center justify-between text-xs pt-1">
                                    <label class="flex items-center cursor-pointer select-none text-slate-300 hover:text-white transition-colors">
                                        <input
                                            id="remember_me"
                                            v-model="form.remember"
                                            name="remember"
                                            type="checkbox"
                                            class="h-4 w-4 rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 transition duration-150 cursor-pointer"
                                        />
                                        <span class="ml-2 font-medium">Ingat perangkat ini</span>
                                    </label>

                                    <span class="text-[11px] text-slate-400 italic">
                                        {{ tabConfig.hint }}
                                    </span>
                                </div>

                                <!-- Submit Button with Shimmer & Glow -->
                                <div class="pt-2">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full flex items-center justify-center py-3.5 px-4 rounded-xl text-sm font-bold text-white shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all duration-300 transform active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed group relative overflow-hidden"
                                        :class="tabConfig.btnClass"
                                    >
                                        <!-- Shimmer effect -->
                                        <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-out pointer-events-none"></span>

                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2.5 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ tabConfig.btnText }}</span>
                                    </button>
                                </div>
                            </form>

                            <!-- ── CONTEXTUAL FOOTER ACTIONS ── -->
                            <div class="pt-4 border-t border-slate-800/80 space-y-3">
                                <!-- Mode Reseller -->
                                <div v-if="currentTab === 'reseller'" class="flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <span>🏪</span>
                                        <span>Belum bermitra dengan kami?</span>
                                    </div>
                                    <Link
                                        :href="route ? route('register-reseller') : '/register-reseller'"
                                        class="inline-flex items-center gap-1 font-bold text-emerald-400 hover:text-emerald-300 hover:underline transition-colors whitespace-nowrap"
                                    >
                                        <span>Daftar Mitra Mandiri</span>
                                        <span>&rarr;</span>
                                    </Link>
                                </div>

                                <!-- Mode Pelanggan -->
                                <div v-else-if="currentTab === 'customer'" class="flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <span class="text-amber-400">⚡</span>
                                        <span>Mau cek invoice tanpa login?</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openCheckBillModal"
                                        class="inline-flex items-center gap-1 font-bold text-indigo-400 hover:text-indigo-300 hover:underline transition-colors whitespace-nowrap"
                                    >
                                        <span>Cek Tagihan Instan</span>
                                        <span>&rarr;</span>
                                    </button>
                                </div>

                                <!-- Mode Staff -->
                                <div v-else class="flex items-center justify-between gap-3 text-xs text-slate-400">
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                        <svg class="w-3.5 h-3.5 text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span>Sistem Terenkripsi &amp; Diaudit</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openCheckBillModal"
                                        class="text-[11px] text-slate-400 hover:text-cyan-400 font-semibold transition-colors whitespace-nowrap"
                                    >
                                        Cek Tagihan Publik &rarr;
                                    </button>
                                </div>

                                <!-- Marketing Affiliate Banner -->
                                <div class="pt-2">
                                    <div class="flex items-center justify-between gap-2 text-xs bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-amber-500/10 p-3 rounded-2xl border border-amber-500/25 backdrop-blur-md">
                                        <div class="flex items-center gap-2 text-amber-300 font-medium">
                                            <span class="text-base">💼</span>
                                            <span class="text-[11px] sm:text-xs">Hasilkan komisi pasif bersama <strong>Mitra Afiliasi</strong></span>
                                        </div>
                                        <Link
                                            href="/register-affiliate"
                                            class="inline-flex items-center gap-1 font-bold text-amber-200 hover:text-white whitespace-nowrap text-xs bg-amber-500/20 hover:bg-amber-500/30 px-3 py-1.5 rounded-xl border border-amber-500/40 transition-all shadow-xs"
                                        >
                                            <span>Daftar</span>
                                            <span>&rarr;</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Global Copyright -->
                    <p class="mt-5 text-center text-xs text-slate-500">
                        &copy; {{ new Date().getFullYear() }} VIRUZS.ISP Infrastructure &amp; Billing Management. All rights reserved.
                    </p>
                </div>

            </div>
        </main>

        <!-- ── FOOTER BAR ── -->
        <footer class="relative z-20 w-full px-4 sm:px-8 py-3 border-t border-slate-800/80 bg-slate-950/60 backdrop-blur-xl text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>Enterprise ISP Cloud Infrastructure</span>
                <div class="flex items-center gap-4 text-[11px]">
                    <span class="hover:text-slate-300 transition-colors cursor-pointer">Dokumentasi</span>
                    <span class="hover:text-slate-300 transition-colors cursor-pointer">Bantuan Teknis</span>
                    <span class="hover:text-slate-300 transition-colors cursor-pointer">Status Jaringan</span>
                </div>
            </div>
        </footer>

        <!-- ── MODAL CEK TAGIHAN INSTAN PELANGGAN (CYBER DARK GLASS) ── -->
        <div
            v-if="showCheckBillModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
            @click.self="closeCheckBillModal"
        >
            <div class="bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden transform transition-all animate-fade-in-up">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-slate-950 via-indigo-950 to-slate-950 px-6 py-4 border-b border-slate-800 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
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
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
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
                                    class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition duration-150 font-mono"
                                    autofocus
                                />
                            </div>
                            <button
                                type="submit"
                                :disabled="isSearching"
                                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
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
                    <div v-if="searchError" class="p-3 bg-rose-950/50 border border-rose-500/30 text-rose-300 text-xs rounded-xl flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ searchError }}</span>
                    </div>

                    <!-- Result Breakdown Card -->
                    <div v-if="billResult" class="space-y-4 pt-1">
                        <!-- Customer Info Header -->
                        <div class="p-4 bg-slate-950/80 border border-slate-800 rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Data Pelanggan</span>
                                <span
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize"
                                    :class="billResult.customer.status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'"
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
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Tagihan Belum Lunas ({{ billResult.invoices.length }})</h4>
                                <span class="text-xs font-bold text-rose-400">
                                    Total: {{ formatRupiah(billResult.total_unpaid) }}
                                </span>
                            </div>

                            <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                                <div
                                    v-for="(inv, idx) in billResult.invoices"
                                    :key="idx"
                                    class="p-3 bg-rose-950/25 border border-rose-500/30 rounded-xl flex items-center justify-between"
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
                                    <div class="text-right">
                                        <span class="block text-xs font-bold text-white font-mono">{{ formatRupiah(inv.remaining) }}</span>
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
                        <div v-else class="p-5 text-center bg-emerald-950/30 border border-emerald-500/30 rounded-2xl space-y-2">
                            <div class="w-10 h-10 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h4 class="text-xs font-bold text-emerald-300">Tidak Ada Tagihan Tertunggak!</h4>
                            <p class="text-[11px] text-emerald-400/90 max-w-xs mx-auto">
                                Semua invoice internet Anda telah lunas. Terima kasih atas loyalitas Anda.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-slate-950 border-t border-slate-800 flex justify-end">
                    <button
                        type="button"
                        @click="closeCheckBillModal"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-bold rounded-xl transition-colors shadow-sm"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-cyber-grid {
    background-image: 
        linear-gradient(to right, rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 36px 36px;
    mask-image: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.9) 0%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.9) 0%, transparent 80%);
}
</style>
