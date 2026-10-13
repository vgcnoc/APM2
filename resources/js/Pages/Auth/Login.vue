<script setup>
import { Head, useForm } from '@inertiajs/vue3';
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

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

// ── Tab Role Selector ──
const currentTab = ref('staff'); // 'staff' | 'reseller' | 'customer'

const tabs = [
    {
        id: 'staff',
        label: 'Staf & Tim',
        sub: 'Admin, NOC, CS, Teknisi',
        color: 'blue',
    },
    {
        id: 'reseller',
        label: 'Mitra / Reseller',
        sub: 'Loket Biller & Voucher',
        color: 'emerald',
    },
    {
        id: 'customer',
        label: 'Pelanggan',
        sub: 'Tagihan & Layanan',
        color: 'indigo',
    },
];

const tabConfig = computed(() => {
    switch (currentTab.value) {
        case 'reseller':
            return {
                title: 'Portal Mitra & Reseller',
                badge: 'Loket Resmi',
                badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                desc: 'Akses khusus agen biller pembayaran tagihan pelanggan, cetak voucher hotspot & manajemen saldo.',
                emailPlaceholder: 'mitra@domain.com / email reseller',
                btnClass: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 shadow-emerald-500/25',
                btnText: 'Masuk ke Portal Mitra',
                hint: 'Pastikan akun kemitraan Anda telah diaktifkan oleh admin ISP.',
            };
        case 'customer':
            return {
                title: 'Portal Mandiri Pelanggan',
                badge: 'Self-Care',
                badgeClass: 'bg-indigo-100 text-indigo-800 border-indigo-200',
                desc: 'Lihat rincian paket internet aktif, riwayat invoice, serta pengajuan bantuan layanan rumah.',
                emailPlaceholder: 'email.pelanggan@gmail.com',
                btnClass: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 shadow-indigo-500/25',
                btnText: 'Masuk ke Portal Pelanggan',
                hint: 'Gunakan email yang terdaftar pada sistem langganan internet Anda.',
            };
        default:
            return {
                title: 'Portal Manajemen & Operasional',
                badge: 'Internal Staf',
                badgeClass: 'bg-blue-100 text-blue-800 border-blue-200',
                desc: 'Dashboard operasional untuk Administrator, Billing Officer, Tim Support, dan Teknisi Lapangan.',
                emailPlaceholder: 'admin@isp.net / nama@perusahaan.com',
                btnClass: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 shadow-blue-500/25',
                btnText: 'Masuk ke Portal Staf',
                hint: 'Akses terbatas hanya untuk staf resmi yang berwenang.',
            };
    }
});

// ── Cek Tagihan Cepat Modal (Tanpa Login) ──
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

    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-10 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
        <!-- Abstract Background Glowing Elements -->
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-200/70 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-200/70 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000 pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-teal-200/40 rounded-full mix-blend-multiply filter blur-3xl opacity-40 pointer-events-none"></div>

        <!-- Header / Logo -->
        <div class="sm:mx-auto sm:w-full sm:max-w-lg relative z-10 flex flex-col items-center">
            <AppLogo context="login" />
            <h1 class="mt-4 text-xl sm:text-2xl font-bold tracking-tight text-gray-900 text-center">
                Sistem Terpadu Layanan Internet
            </h1>
            <p class="mt-1 text-center text-xs sm:text-sm text-gray-500">
                Pilih area portal yang sesuai untuk melanjutkan
            </p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-lg relative z-10 px-4 sm:px-0">
            <!-- Main Login Card -->
            <div class="bg-white/90 backdrop-blur-xl shadow-2xl rounded-2xl border border-white/60 overflow-hidden transition-all duration-300">
                
                <!-- Role Area Tabs -->
                <div class="bg-slate-100/80 p-1.5 border-b border-gray-200/80 grid grid-cols-3 gap-1.5">
                    <button
                        v-for="t in tabs"
                        :key="t.id"
                        type="button"
                        @click="currentTab = t.id"
                        class="relative flex flex-col items-center justify-center py-2.5 px-2 rounded-xl text-center transition-all duration-200 font-medium text-xs sm:text-sm focus:outline-none"
                        :class="[
                            currentTab === t.id
                                ? 'bg-white text-gray-900 shadow-md font-semibold ring-1 ring-black/5'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
                        ]"
                    >
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <!-- Staff Icon -->
                            <svg v-if="t.id === 'staff'" class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <!-- Reseller Icon -->
                            <svg v-else-if="t.id === 'reseller'" class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <!-- Customer Icon -->
                            <svg v-else class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="truncate">{{ t.label }}</span>
                        </div>
                        <span class="text-[10px] text-gray-500 font-normal hidden sm:inline-block truncate max-w-full">
                            {{ t.sub }}
                        </span>
                        
                        <!-- Active Pill Indicator -->
                        <span 
                            v-if="currentTab === t.id"
                            class="absolute -bottom-1.5 left-1/2 transform -translate-x-1/2 w-8 h-1 rounded-full"
                            :class="{
                                'bg-blue-600': t.id === 'staff',
                                'bg-emerald-600': t.id === 'reseller',
                                'bg-indigo-600': t.id === 'customer'
                            }"
                        ></span>
                    </button>
                </div>

                <div class="p-6 sm:p-8">
                    <!-- Dynamic Area Banner -->
                    <div class="mb-6 p-3.5 rounded-xl border flex items-start gap-3 transition-colors duration-200"
                        :class="{
                            'bg-blue-50/70 border-blue-100': currentTab === 'staff',
                            'bg-emerald-50/70 border-emerald-100': currentTab === 'reseller',
                            'bg-indigo-50/70 border-indigo-100': currentTab === 'customer',
                        }"
                    >
                        <div class="p-2 rounded-lg bg-white shadow-sm flex-shrink-0">
                            <!-- Icon -->
                            <svg v-if="currentTab === 'staff'" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <svg v-else-if="currentTab === 'reseller'" class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-gray-900 truncate">
                                    {{ tabConfig.title }}
                                </h2>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border" :class="tabConfig.badgeClass">
                                    {{ tabConfig.badge }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 mt-0.5 leading-relaxed">
                                {{ tabConfig.desc }}
                            </p>
                        </div>
                    </div>

                    <!-- Instant Check Bill Banner (Highlight for Pelanggan) -->
                    <div v-if="currentTab === 'customer'" class="mb-6 p-4 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/20 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="flex items-center gap-3 text-center sm:text-left">
                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold">Mau Cek Tagihan Langsung?</h3>
                                <p class="text-xs text-indigo-100">Bisa cek tanpa login menggunakan ID Pelanggan / WhatsApp.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="openCheckBillModal"
                            class="w-full sm:w-auto px-4 py-2 bg-white text-indigo-700 hover:bg-indigo-50 font-semibold text-xs rounded-lg shadow transition-all duration-150 flex items-center justify-center gap-1.5 whitespace-nowrap"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Cek Tagihan Instan
                        </button>
                    </div>

                    <div v-if="status" class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
                        {{ status }}
                    </div>

                    <!-- Login Form -->
                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email Pengguna
                            </label>
                            <div class="mt-1 relative rounded-lg shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition duration-200"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-600 font-medium">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="mt-1 relative rounded-lg shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="••••••••"
                                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition duration-200"
                                />
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-600 font-medium">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <label class="flex items-center cursor-pointer select-none">
                                <input
                                    id="remember_me"
                                    v-model="form.remember"
                                    name="remember"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition duration-200"
                                />
                                <span class="ml-2 text-gray-600">Ingat sesi saya</span>
                            </label>

                            <span class="text-gray-400">
                                {{ tabConfig.hint }}
                            </span>
                        </div>

                        <div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-md text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5"
                                :class="[
                                    tabConfig.btnClass,
                                    { 'opacity-75 cursor-not-allowed': form.processing }
                                ]"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ tabConfig.btnText }}
                            </button>
                        </div>
                    </form>

                    <!-- Bottom Additional Helpers -->
                    <div class="mt-6 pt-5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
                        <button
                            type="button"
                            @click="openCheckBillModal"
                            class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-800 font-medium transition-colors"
                        >
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Cek Tagihan Pelanggan (Tanpa Login)
                        </button>

                        <div class="text-center sm:text-right">
                            <span v-if="currentTab === 'reseller'" class="text-gray-500">
                                Mau jadi agen loket? Hubungi CS kami
                            </span>
                            <span v-else-if="currentTab === 'customer'" class="text-gray-500">
                                Butuh bantuan login? Hubungi Helpdesk
                            </span>
                            <span v-else class="text-gray-400">
                                Keamanan & Hak Akses Terlindungi
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Global Footer Note -->
            <p class="mt-4 text-center text-xs text-gray-400">
                &copy; {{ new Date().getFullYear() }} ISP Billing & Network Infrastructure. All rights reserved.
            </p>
        </div>
    </div>

    <!-- ── MODAL CEK TAGIHAN INSTAN PELANGGAN ── -->
    <div
        v-if="showCheckBillModal"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        @click.self="closeCheckBillModal"
    >
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold">Cek Tagihan Cepat Pelanggan</h2>
                        <p class="text-xs text-blue-100">Informasi invoice dan status layanan internet Anda</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="closeCheckBillModal"
                    class="text-white/80 hover:text-white hover:bg-white/10 rounded-lg p-1.5 transition-colors"
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
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-1.5">
                        Masukkan ID Pelanggan atau No. WhatsApp
                    </label>
                    <form @submit.prevent="handleCheckBill" class="flex gap-2">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Contoh: CUST-0012 atau 08123456789"
                                class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                autofocus
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="isSearching"
                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-all flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isSearching" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isSearching ? 'Mencari...' : 'Cek' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Error Message -->
                <div v-if="searchError" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl flex items-start gap-2">
                    <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ searchError }}</span>
                </div>

                <!-- Result Card -->
                <div v-if="billResult" class="space-y-4 pt-1">
                    <!-- Customer Information Header -->
                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 uppercase">Informasi Pelanggan</span>
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize"
                                :class="billResult.customer.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
                            >
                                {{ billResult.customer.status === 'active' ? 'Aktif' : billResult.customer.status }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-500 block text-[11px]">Nama Pelanggan</span>
                                <span class="font-bold text-gray-900 text-sm">{{ billResult.customer.name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-[11px]">ID Pelanggan</span>
                                <span class="font-mono font-semibold text-blue-600">{{ billResult.customer.customer_code }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-[11px]">Paket Layanan</span>
                                <span class="font-medium text-gray-800">{{ billResult.customer.package_name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-[11px]">Wilayah / Area</span>
                                <span class="font-medium text-gray-800">{{ billResult.customer.area }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices Status -->
                    <div v-if="billResult.invoices && billResult.invoices.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700">Tagihan Belum Lunas ({{ billResult.invoices.length }})</h4>
                            <span class="text-xs font-bold text-red-600">
                                Total: {{ formatRupiah(billResult.total_unpaid) }}
                            </span>
                        </div>

                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            <div
                                v-for="(inv, idx) in billResult.invoices"
                                :key="idx"
                                class="p-3 bg-red-50/60 border border-red-200/80 rounded-xl flex items-center justify-between"
                            >
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs font-bold text-gray-900">{{ inv.invoice_number }}</span>
                                        <span class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.2 rounded font-medium uppercase">
                                            {{ inv.status }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-600 mt-0.5">
                                        Periode: <span class="font-medium">{{ inv.period_label || '-' }}</span> &bull; Jatuh Tempo: <span class="font-medium text-red-700">{{ inv.due_date }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block text-xs font-bold text-gray-900">{{ formatRupiah(inv.remaining) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Guidance Box -->
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Cara Pembayaran:
                            </div>
                            <p class="text-[11px] leading-relaxed text-amber-700">
                                Anda dapat melakukan pelunasan tagihan melalui transfer bank / QRIS resmi perusahaan, atau membayar secara tunai melalui <strong>Loket Mitra / Reseller</strong> terdekat di lingkungan Anda.
                            </p>
                        </div>
                    </div>

                    <!-- No Unpaid Invoices -->
                    <div v-else class="p-5 text-center bg-green-50/80 border border-green-200 rounded-xl space-y-2">
                        <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-green-900">Tidak Ada Tagihan Tertunggak!</h4>
                        <p class="text-xs text-green-700 max-w-sm mx-auto">
                            Seluruh tagihan internet Anda telah lunas. Layanan internet Anda berjalan dengan lancar. Terima kasih!
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button
                    type="button"
                    @click="closeCheckBillModal"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-sm"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.08); }
  66% { transform: translate(-20px, 20px) scale(0.92); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 8s infinite ease-in-out;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
</style>
