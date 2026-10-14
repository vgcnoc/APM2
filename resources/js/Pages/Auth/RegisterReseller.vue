<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

const props = defineProps({
    areas: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    store_name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    address: '',
    area_id: '',
});

const submit = () => {
    form.post(route('register-reseller.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Pendaftaran Mitra Reseller Voucher & Loket Biller" />

    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
        <!-- Abstract Background Glowing Elements -->
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-200/60 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-teal-200/60 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000 pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-cyan-200/40 rounded-full mix-blend-multiply filter blur-3xl opacity-40 pointer-events-none"></div>

        <div class="max-w-3xl mx-auto w-full relative z-10 space-y-6">
            
            <!-- Logo & Brand Header -->
            <div class="text-center flex flex-col items-center">
                <AppLogo context="login" />
                <div class="mt-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100/80 border border-emerald-300 text-emerald-800 text-xs font-black uppercase tracking-wider shadow-xs">
                    <span>🏪</span> Program Kemitraan Resmi
                </div>
                <h1 class="mt-2 text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                    Pendaftaran Agen Reseller &amp; Biller
                </h1>
                <p class="mt-1.5 text-xs sm:text-sm text-gray-600 max-w-xl">
                    Buka loket pembayaran tagihan internet dan jual voucher WiFi Hotspot di konter / toko Anda dengan komisi menarik.
                </p>
            </div>

            <!-- 4 Keuntungan Kemitraan (Cards) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white/80 backdrop-blur-md p-3.5 rounded-2xl border border-emerald-100 shadow-sm text-center">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center mx-auto text-sm shadow-sm mb-2">
                        🎟️
                    </div>
                    <h2 class="text-xs font-bold text-gray-900">Jual Voucher</h2>
                    <p class="text-[10px] text-gray-500 mt-0.5">Cetak &amp; jual voucher hotspot langsung dari HP</p>
                </div>
                <div class="bg-white/80 backdrop-blur-md p-3.5 rounded-2xl border border-teal-100 shadow-sm text-center">
                    <div class="w-8 h-8 rounded-xl bg-teal-500 text-white flex items-center justify-center mx-auto text-sm shadow-sm mb-2">
                        💳
                    </div>
                    <h2 class="text-xs font-bold text-gray-900">Loket Biller</h2>
                    <p class="text-[10px] text-gray-500 mt-0.5">Terima bayar tagihan warga &amp; dapatkan fee</p>
                </div>
                <div class="bg-white/80 backdrop-blur-md p-3.5 rounded-2xl border border-cyan-100 shadow-sm text-center">
                    <div class="w-8 h-8 rounded-xl bg-cyan-500 text-white flex items-center justify-center mx-auto text-sm shadow-sm mb-2">
                        ⚡
                    </div>
                    <h2 class="text-xs font-bold text-gray-900">Top Up Instan</h2>
                    <p class="text-[10px] text-gray-500 mt-0.5">Isi saldo otomatis via QRIS &amp; Virtual Account</p>
                </div>
                <div class="bg-white/80 backdrop-blur-md p-3.5 rounded-2xl border border-indigo-100 shadow-sm text-center">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500 text-white flex items-center justify-center mx-auto text-sm shadow-sm mb-2">
                        🆓
                    </div>
                    <h2 class="text-xs font-bold text-gray-900">Bebas Iuran</h2>
                    <p class="text-[10px] text-gray-500 mt-0.5">Tanpa biaya bulanan &amp; tanpa pasang kabel</p>
                </div>
            </div>

            <!-- Registration Card -->
            <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-3xl border border-white/60 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 px-6 py-5 text-white flex items-center justify-between">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold flex items-center gap-2">
                            <span>📝</span> Formulir Registrasi Agen Mandiri
                        </h2>
                        <p class="text-xs text-emerald-100 mt-0.5">Isi formulir di bawah ini dengan data yang valid untuk aktivasi otomatis</p>
                    </div>
                    <span class="text-[11px] font-extrabold bg-white/20 px-3 py-1 rounded-full uppercase tracking-wider border border-white/20 hidden sm:inline-block">
                        100% Gratis
                    </span>
                </div>

                <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                    
                    <!-- Section 1: Profil Agen & Toko -->
                    <div>
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 mb-3 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Informasi Pemilik &amp; Usaha
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Nama Lengkap Pemilik <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Ahmad Fauzi"
                                    class="w-full text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 transition"
                                />
                                <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="store_name" class="block text-xs font-semibold text-gray-700 mb-1 flex items-center justify-between">
                                    <span>Nama Toko / Konter</span>
                                    <span class="text-[10px] text-gray-400 font-normal">Opsional</span>
                                </label>
                                <input
                                    id="store_name"
                                    v-model="form.store_name"
                                    type="text"
                                    placeholder="Contoh: Berkah Cell / Toko Ibu Ani"
                                    class="w-full text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 transition"
                                />
                                <p v-if="form.errors.store_name" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.store_name }}</p>
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Nomor WhatsApp Aktif <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 text-xs font-bold">
                                        +62 /
                                    </div>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        placeholder="081234567890"
                                        class="w-full pl-12 text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 pr-3.5 py-2.5 transition"
                                    />
                                </div>
                                <p v-if="form.errors.phone" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.phone }}</p>
                            </div>

                            <div>
                                <label for="area_id" class="block text-xs font-semibold text-gray-700 mb-1 flex items-center justify-between">
                                    <span>Wilayah / Area Operasional</span>
                                    <span class="text-[10px] text-gray-400 font-normal">Opsional</span>
                                </label>
                                <select
                                    id="area_id"
                                    v-model="form.area_id"
                                    class="w-full text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 transition cursor-pointer"
                                >
                                    <option value="">-- Pilih Wilayah Operasional --</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">
                                        {{ area.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.area_id" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.area_id }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Alamat Toko / Lokasi Penjualan Voucher <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="2"
                                    required
                                    placeholder="Contoh: Jl. Melati No. 15 RT 02/04, Depan Masjid Al-Ikhlas"
                                    class="w-full text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 transition resize-none"
                                ></textarea>
                                <p v-if="form.errors.address" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.address }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Kredensial Login Client Area -->
                    <div class="pt-4 border-t border-gray-100">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 mb-3 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Akun Login Portal Mitra
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="email" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Alamat Email (Username Login) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="nama.konter@gmail.com"
                                    class="w-full text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 transition"
                                />
                                <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label for="password" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Password <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="Minimal 6 karakter"
                                    class="w-full text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 transition"
                                />
                                <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Ulangi Password <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    placeholder="Ketik ulang password"
                                    class="w-full text-xs sm:text-sm rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 transition"
                                />
                                <p v-if="form.errors.password_confirmation" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.password_confirmation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Agreement Note -->
                    <div class="p-4 bg-emerald-50/80 rounded-2xl border border-emerald-200 text-xs text-emerald-900 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <span>🛡️</span> Ketentuan Kemitraan:
                        </div>
                        <p class="text-[11px] text-emerald-800 leading-relaxed">
                            Dengan mendaftar, Anda otomatis terdaftar sebagai <strong>Mitra Loket Biller &amp; Reseller Voucher</strong> resmi. Anda bebas dari tagihan internet bulanan, dan langsung dapat melakukan transaksi setelah mengisi saldo modal.
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-500/25 transition-all transform hover:-translate-y-0.5 focus:ring-4 focus:ring-emerald-300 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Memproses Pendaftaran...' : 'Daftar Sebagai Mitra Sekarang' }}</span>
                            <span v-if="!form.processing">&rarr;</span>
                        </button>
                    </div>

                    <!-- Back to Login Link -->
                    <div class="text-center pt-2">
                        <p class="text-xs text-gray-500">
                            Sudah memiliki akun kemitraan?
                            <Link :href="route('login')" class="font-bold text-emerald-600 hover:text-emerald-800 hover:underline">
                                Masuk ke Portal Mitra Di Sini
                            </Link>
                        </p>
                    </div>

                </form>
            </div>

            <!-- Footer Note -->
            <p class="text-center text-xs text-gray-400">
                &copy; {{ new Date().getFullYear() }} VIRUZS.ISP Billing &amp; Reseller Infrastructure. All rights reserved.
            </p>

        </div>
    </div>
</template>
