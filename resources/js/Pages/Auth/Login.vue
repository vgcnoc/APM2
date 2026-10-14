<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
                badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dotClass: 'bg-emerald-500',
                activeRing: 'focus:ring-emerald-500 focus:border-emerald-500',
                activeTabBg: 'text-emerald-700 bg-white shadow-sm ring-1 ring-slate-900/5',
                btnClass: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 shadow-emerald-600/20',
                btnText: 'Masuk Portal Mitra',
                desc: 'Akses khusus loket pembayaran, cetak voucher WiFi & transaksi agen.',
                emailPlaceholder: 'mitra@domain.com / email agen',
                emailLabel: 'Email Mitra / Agen',
                hint: 'Gunakan akun yang telah disetujui admin ISP.',
            };
        case 'customer':
            return {
                title: 'Portal Mandiri Pelanggan',
                badge: 'Self-Care',
                badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
                dotClass: 'bg-indigo-500',
                activeRing: 'focus:ring-indigo-500 focus:border-indigo-500',
                activeTabBg: 'text-indigo-700 bg-white shadow-sm ring-1 ring-slate-900/5',
                btnClass: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 shadow-indigo-600/20',
                btnText: 'Masuk Portal Pelanggan',
                desc: 'Pantau status paket internet rumah, riwayat invoice & pengajuan tiket.',
                emailPlaceholder: 'email.pelanggan@domain.com',
                emailLabel: 'Email Terdaftar',
                hint: 'Gunakan email yang terdaftar pada sistem langganan Anda.',
            };
        default:
            return {
                title: 'Portal Operasional Staf',
                badge: 'Internal Staf',
                badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
                dotClass: 'bg-blue-500',
                activeRing: 'focus:ring-blue-500 focus:border-blue-500',
                activeTabBg: 'text-blue-700 bg-white shadow-sm ring-1 ring-slate-900/5',
                btnClass: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 shadow-blue-600/20',
                btnText: 'Masuk Portal Staf',
                desc: 'Dashboard operasional untuk Tim NOC, CS, Billing & Administrator.',
                emailPlaceholder: 'admin@isp.net / nama@perusahaan.com',
                emailLabel: 'Email Akun Resmi',
                hint: 'Akses terbatas untuk staf dan personel yang berwenang.',
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
    <Head title="Masuk Portal Layanan" />

    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans select-none antialiased">
        <!-- Modern Subtle Dot Grid Background -->
        <div class="absolute inset-0 bg-dot-grid opacity-60 pointer-events-none"></div>

        <!-- Ambient Ambient Radial Glow Behind Card -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[320px] bg-gradient-to-tr from-blue-300/25 via-indigo-300/20 to-teal-200/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header / Logo -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 flex flex-col items-center text-center">
            <AppLogo context="login" />
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 mt-1">
                Portal Akses Terpadu
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-xs">
                Sistem Manajemen Billing &amp; Operasional ISP
            </p>
        </div>

        <!-- Main Card Container -->
        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-[460px] relative z-10">
            <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_15px_40px_-15px_rgba(15,23,42,0.08)] border border-slate-200/90 overflow-hidden transition-all duration-300">
                
                <!-- Modern Segmented Pill Control -->
                <div class="p-3 bg-slate-50/70 border-b border-slate-100">
                    <div class="bg-slate-200/60 p-1 rounded-xl grid grid-cols-3 gap-1">
                        <button
                            v-for="t in tabs"
                            :key="t.id"
                            type="button"
                            @click="currentTab = t.id"
                            class="relative flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-lg text-xs sm:text-[13px] font-medium transition-all duration-150 focus:outline-none"
                            :class="[
                                currentTab === t.id
                                    ? tabConfig.activeTabBg + ' font-semibold'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/40'
                            ]"
                        >
                            <!-- Tab Dot Indicator -->
                            <span
                                v-if="currentTab === t.id"
                                class="w-1.5 h-1.5 rounded-full flex-shrink-0 animate-pulse"
                                :class="tabConfig.dotClass"
                            ></span>

                            <!-- Tab Icons -->
                            <svg v-if="t.id === 'staff'" class="w-3.5 h-3.5 flex-shrink-0" :class="currentTab === 'staff' ? 'text-blue-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <svg v-else-if="t.id === 'reseller'" class="w-3.5 h-3.5 flex-shrink-0" :class="currentTab === 'reseller' ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 flex-shrink-0" :class="currentTab === 'customer' ? 'text-indigo-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>

                            <span class="truncate">{{ t.label }}</span>
                        </button>
                    </div>
                </div>

                <div class="p-6 sm:p-7">
                    <!-- Clean Micro Context Strip -->
                    <div class="mb-5 flex items-center justify-between gap-2 pb-4 border-b border-slate-100">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                <span>{{ tabConfig.title }}</span>
                            </h2>
                            <p class="text-[12px] text-slate-500 truncate mt-0.5">
                                {{ tabConfig.desc }}
                            </p>
                        </div>
                        <span class="flex-shrink-0 px-2 py-0.5 text-[11px] font-medium rounded-full border" :class="tabConfig.badgeClass">
                            {{ tabConfig.badge }}
                        </span>
                    </div>

                    <!-- Flash Message Status -->
                    <div v-if="status" class="mb-4 text-xs font-medium text-emerald-700 bg-emerald-50 p-3 rounded-xl border border-emerald-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ status }}</span>
                    </div>

                    <!-- Main Form -->
                    <form class="space-y-4" @submit.prevent="submit">
                        <!-- Email Input Field -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
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
                                    class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-300 hover:border-slate-400 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:bg-white focus:ring-2 transition duration-150"
                                    :class="tabConfig.activeRing"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-600 font-medium">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password Input Field with Toggle -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
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
                                    placeholder="••••••••"
                                    class="block w-full pl-10 pr-10 py-2.5 bg-slate-50/70 border border-slate-300 hover:border-slate-400 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:bg-white focus:ring-2 transition duration-150"
                                    :class="tabConfig.activeRing"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
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
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-600 font-medium">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Checkbox & Hint -->
                        <div class="flex items-center justify-between text-xs pt-0.5">
                            <label class="flex items-center cursor-pointer select-none text-slate-600 hover:text-slate-900">
                                <input
                                    id="remember_me"
                                    v-model="form.remember"
                                    name="remember"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded transition duration-150 cursor-pointer"
                                />
                                <span class="ml-2">Ingat perangkat ini</span>
                            </label>

                            <span class="text-[11px] text-slate-400">
                                {{ tabConfig.hint }}
                            </span>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-1">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 transform active:scale-[0.99] disabled:opacity-70 disabled:cursor-not-allowed"
                                :class="tabConfig.btnClass"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ tabConfig.btnText }}</span>
                            </button>
                        </div>
                    </form>

                    <!-- Smart Contextual Footer Action (One Per Mode) -->
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <!-- Mitra Reseller Mode -->
                        <div v-if="currentTab === 'reseller'" class="flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span>🏪</span>
                                <span>Belum bermitra dengan kami?</span>
                            </div>
                            <Link
                                :href="route ? route('register-reseller') : '/register-reseller'"
                                class="inline-flex items-center gap-1 font-semibold text-emerald-700 hover:text-emerald-800 hover:underline transition-colors whitespace-nowrap"
                            >
                                <span>Daftar Mitra Mandiri</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>

                        <!-- Customer Mode -->
                        <div v-else-if="currentTab === 'customer'" class="flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span>⚡</span>
                                <span>Mau cek invoice tanpa login?</span>
                            </div>
                            <button
                                type="button"
                                @click="openCheckBillModal"
                                class="inline-flex items-center gap-1 font-semibold text-indigo-700 hover:text-indigo-800 hover:underline transition-colors whitespace-nowrap"
                            >
                                <span>Cek Tagihan Cepat</span>
                                <span>&rarr;</span>
                            </button>
                        </div>

                        <!-- Staff Mode -->
                        <div v-else class="flex items-center justify-between gap-3 text-xs text-slate-500">
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Keamanan &amp; Integritas Data Terlindungi</span>
                            </div>
                            <button
                                type="button"
                                @click="openCheckBillModal"
                                class="text-[11px] text-slate-500 hover:text-blue-600 font-medium transition-colors whitespace-nowrap"
                            >
                                Cek Tagihan Publik
                            </button>
                        </div>

                        <!-- Marketing Affiliate Program Callout -->
                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-2 text-xs bg-gradient-to-r from-amber-50 to-orange-50 p-2.5 rounded-xl border border-amber-200/60">
                            <div class="flex items-center gap-2 text-amber-900 font-medium">
                                <span class="text-base">💼</span>
                                <span>Mau hasilkan komisi bulanan? <strong>Gabung Afiliasi</strong></span>
                            </div>
                            <Link
                                href="/register-affiliate"
                                class="inline-flex items-center gap-1 font-bold text-amber-800 hover:text-amber-900 hover:underline whitespace-nowrap text-xs bg-amber-100/90 px-2.5 py-1 rounded-lg border border-amber-300/60 transition-colors shadow-xs"
                            >
                                <span>Daftar Gratis</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Global Footer Note -->
            <p class="mt-5 text-center text-xs text-slate-400">
                &copy; {{ new Date().getFullYear() }} VIRUZS.ISP Billing &amp; Reseller Infrastructure. All rights reserved.
            </p>
        </div>
    </div>

    <!-- ── MODAL CEK TAGIHAN INSTAN PELANGGAN ── -->
    <div
        v-if="showCheckBillModal"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
        @click.self="closeCheckBillModal"
    >
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-4 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-indigo-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white">Cek Tagihan Instan</h2>
                        <p class="text-[11px] text-slate-300">Cek status layanan &amp; total tagihan tanpa login</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="closeCheckBillModal"
                    class="text-white/70 hover:text-white hover:bg-white/10 rounded-lg p-1.5 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4">
                <!-- Search Box -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        ID Pelanggan atau Nomor WhatsApp
                    </label>
                    <form @submit.prevent="handleCheckBill" class="flex gap-2">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Contoh: CUST-0012 atau 08123456789"
                                class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                autofocus
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="isSearching"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isSearching" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isSearching ? 'Mencari...' : 'Periksa' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Error Message -->
                <div v-if="searchError" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-start gap-2">
                    <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ searchError }}</span>
                </div>

                <!-- Result Card -->
                <div v-if="billResult" class="space-y-4 pt-1">
                    <!-- Customer Information Header -->
                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Data Pelanggan</span>
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize"
                                :class="billResult.customer.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                            >
                                {{ billResult.customer.status === 'active' ? 'Layanan Aktif' : billResult.customer.status }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Pelanggan</span>
                                <span class="font-bold text-slate-800 text-sm">{{ billResult.customer.name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">ID Pelanggan</span>
                                <span class="font-mono font-semibold text-indigo-600">{{ billResult.customer.customer_code }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Paket Layanan</span>
                                <span class="font-medium text-slate-700">{{ billResult.customer.package_name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Wilayah / Area</span>
                                <span class="font-medium text-slate-700">{{ billResult.customer.area }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices Status -->
                    <div v-if="billResult.invoices && billResult.invoices.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Tagihan Belum Lunas ({{ billResult.invoices.length }})</h4>
                            <span class="text-xs font-bold text-rose-600">
                                Total: {{ formatRupiah(billResult.total_unpaid) }}
                            </span>
                        </div>

                        <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                            <div
                                v-for="(inv, idx) in billResult.invoices"
                                :key="idx"
                                class="p-3 bg-rose-50/50 border border-rose-200/70 rounded-xl flex items-center justify-between"
                            >
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs font-bold text-slate-900">{{ inv.invoice_number }}</span>
                                        <span class="text-[10px] bg-rose-100 text-rose-700 px-1.5 py-0.2 rounded font-medium uppercase">
                                            {{ inv.status }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        Periode: <span class="font-medium text-slate-700">{{ inv.period_label || '-' }}</span> &bull; Jatuh Tempo: <span class="font-medium text-rose-700">{{ inv.due_date }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block text-xs font-bold text-slate-900">{{ formatRupiah(inv.remaining) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Guidance Box -->
                        <div class="p-3 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs space-y-1">
                            <div class="font-semibold flex items-center gap-1 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Petunjuk Pembayaran:
                            </div>
                            <p class="text-[11px] leading-relaxed text-amber-800">
                                Pembayaran dapat dilakukan via transfer Bank / QRIS resmi perusahaan, atau tunai melalui <strong>Loket Mitra / Reseller</strong> terdekat.
                            </p>
                        </div>
                    </div>

                    <!-- No Unpaid Invoices -->
                    <div v-else class="p-5 text-center bg-emerald-50/80 border border-emerald-200 rounded-xl space-y-2">
                        <div class="w-9 h-9 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-bold text-emerald-900">Tidak Ada Tagihan Tertunggak!</h4>
                        <p class="text-[11px] text-emerald-700 max-w-xs mx-auto">
                            Semua invoice internet Anda telah lunas. Terima kasih telah menggunakan layanan kami.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button
                    type="button"
                    @click="closeCheckBillModal"
                    class="px-4 py-1.5 bg-white border border-slate-300 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition-colors shadow-sm"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-dot-grid {
    background-image: radial-gradient(rgba(148, 163, 184, 0.35) 1px, transparent 1px);
    background-size: 24px 24px;
}
</style>
