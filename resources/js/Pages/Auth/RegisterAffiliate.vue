<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLogo from '@/Components/AppLogo.vue';

const props = defineProps({
    initialRef: String,
    upline: Object,
    commissionSettings: Object,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    referral_code: props.initialRef || '',
    bank_name: 'BCA',
    bank_account_number: '',
    bank_account_holder: '',
    address: '',
});

const bankOptions = [
    'BCA',
    'Bank Mandiri',
    'BRI',
    'BNI',
    'Bank Syariah Indonesia (BSI)',
    'Bank Jago',
    'SeaBank',
    'DANA',
    'GoPay',
    'OVO',
    'ShopeePay',
];

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const showPassword = ref(false);

const submit = () => {
    form.post(route('register-affiliate.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Pendaftaran Mitra Afiliasi & Sales Marketing" />

    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans antialiased">
        <!-- Subtle Grid Pattern Background -->
        <div class="absolute inset-0 bg-dot-grid opacity-50 pointer-events-none"></div>

        <!-- Ambient Glow -->
        <div class="absolute top-10 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-gradient-to-tr from-indigo-300/20 via-purple-300/20 to-emerald-200/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header & Branding -->
        <div class="sm:mx-auto sm:w-full sm:max-w-2xl relative z-10 text-center">
            <AppLogo context="login" />
            
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200/80 text-indigo-700 text-xs font-bold mb-3 shadow-2xs">
                <span>⚡</span>
                <span>Program Kemitraan Marketing &amp; Afiliasi Resmi</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Gabung Jadi Mitra Afiliasi &amp; Raih Pasif Income
            </h1>
            <p class="mt-2 text-sm text-slate-600 max-w-lg mx-auto">
                Bukan karyawan tetap bisa bergabung! Ajak pelanggan pasang internet rumah dan bangun jaringan tim Anda untuk komisi berulang setiap bulan.
            </p>

            <!-- Multi-Tier Benefit Highlight Cards -->
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3 text-left">
                <!-- Komisi Booking -->
                <div class="bg-white/90 backdrop-blur-md p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm mb-2">
                        🎯
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Komisi Booking</div>
                    <div class="text-lg font-bold text-indigo-600 mt-0.5">
                        {{ formatRupiah(commissionSettings?.booking || 35000) }}
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Cair sekali setiap kali ada pelanggan baru yang Anda booking/pasang.</p>
                </div>

                <!-- Komisi Bulanan -->
                <div class="bg-white/90 backdrop-blur-md p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm mb-2">
                        💰
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Pasif Income / Bulan</div>
                    <div class="text-lg font-bold text-emerald-600 mt-0.5">
                        {{ formatRupiah(commissionSettings?.monthly || 5000) }}
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Mengalir otomatis setiap bulan saat pelanggan Anda membayar tagihan!</p>
                </div>

                <!-- Komisi Upline Tier -->
                <div class="bg-white/90 backdrop-blur-md p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm mb-2">
                        🚀
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Komisi Jaringan Tim</div>
                    <div class="text-xs font-bold text-purple-700 mt-0.5">
                        Tier 1: {{ formatRupiah(commissionSettings?.upline1 || 2000) }} &bull; Tier 2: {{ formatRupiah(commissionSettings?.upline2 || 1000) }}
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Dapatkan komisi dari transaksi yang dihasilkan mitra di bawah binaan Anda.</p>
                </div>
            </div>
        </div>

        <!-- Registration Card Container -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl relative z-10">
            <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-[0_20px_50px_-15px_rgba(15,23,42,0.12)] border border-slate-200/90 overflow-hidden">
                
                <!-- Upline Recommendation Notice if from referral link -->
                <div v-if="upline" class="p-3.5 bg-gradient-to-r from-indigo-50 via-purple-50 to-indigo-50 border-b border-indigo-100 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">
                            🤝
                        </span>
                        <div>
                            <span class="text-slate-500 block text-[10px]">Direkrut oleh Sponsor:</span>
                            <span class="font-bold text-slate-800">{{ upline.name }} (Kode: {{ upline.code }})</span>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 font-bold rounded-full text-[10px]">
                        Terhubung
                    </span>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="mb-5 pb-3 border-b border-slate-100">
                        <h2 class="text-base font-bold text-slate-800">
                            Lengkapi Formulir Kemitraan
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Data rekening digunakan untuk pencairan saldo komisi langsung ke bank/e-wallet Anda.
                        </p>
                    </div>

                    <form class="space-y-4" @submit.prevent="submit">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Nama Lengkap
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: Budi Santoso"
                                class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- WhatsApp & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    No. WhatsApp Aktif
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    placeholder="Contoh: 081234567890"
                                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Email Akun
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="emailanda@gmail.com"
                                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ form.errors.email }}
                                </p>
                            </div>
                        </div>

                        <!-- Password & Konfirmasi -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Kata Sandi
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        required
                                        placeholder="Minimal 6 karakter"
                                        class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 pr-10"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                                        tabindex="-1"
                                    >
                                        <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Konfirmasi Sandi
                                </label>
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    placeholder="Ulangi kata sandi"
                                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                />
                            </div>
                        </div>

                        <!-- Rekening Bank / E-Wallet Pencairan -->
                        <div class="pt-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Rekening Bank / E-Wallet Pencairan Komisi
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <select
                                    v-model="form.bank_name"
                                    class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                >
                                    <option v-for="b in bankOptions" :key="b" :value="b">{{ b }}</option>
                                </select>

                                <input
                                    v-model="form.bank_account_number"
                                    type="text"
                                    required
                                    placeholder="Nomor Rekening / No HP"
                                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                />

                                <input
                                    v-model="form.bank_account_holder"
                                    type="text"
                                    required
                                    placeholder="Atas Nama Pemilik"
                                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                                />
                            </div>
                            <p v-if="form.errors.bank_account_number" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.bank_account_number }}
                            </p>
                        </div>

                        <!-- Kode Referral Pengajak (Opsional) -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1 flex items-center justify-between">
                                <span>Kode Referral Pengajak (Opsional)</span>
                                <span class="text-[11px] text-slate-400 font-normal">Kosongkan jika mendaftar mandiri</span>
                            </label>
                            <input
                                v-model="form.referral_code"
                                type="text"
                                placeholder="Contoh: AFF-XXXXX"
                                class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-mono focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                            />
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full flex items-center justify-center py-3 px-4 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/25 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all transform active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Daftar Jadi Mitra Afiliasi Sekarang &rarr;</span>
                            </button>
                        </div>
                    </form>

                    <!-- Link to Login -->
                    <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-600">
                        <span>Sudah memiliki akun mitra atau staf? </span>
                        <Link :href="route('login')" class="font-bold text-indigo-600 hover:underline">
                            Masuk ke Portal Login &rarr;
                        </Link>
                    </div>

                </div>
            </div>

            <!-- Footer Note -->
            <p class="mt-5 text-center text-xs text-slate-400">
                &copy; {{ new Date().getFullYear() }} VIRUZS.ISP Billing &amp; Reseller Infrastructure. All rights reserved.
            </p>
        </div>
    </div>
</template>

<style scoped>
.bg-dot-grid {
    background-image: radial-gradient(rgba(148, 163, 184, 0.35) 1px, transparent 1px);
    background-size: 24px 24px;
}
</style>
