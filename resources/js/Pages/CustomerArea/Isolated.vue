<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLogo from '@/Components/AppLogo.vue';
import CustomerIsolatedBanner from '@/Components/CustomerIsolatedBanner.vue';
import {
    ArrowRightIcon,
    ArrowUpRightIcon,
    CheckBadgeIcon,
    ClockIcon,
    CreditCardIcon,
    GlobeAltIcon,
    MagnifyingGlassIcon,
    QuestionMarkCircleIcon,
    ShieldExclamationIcon,
    SignalSlashIcon,
    SparklesIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    isolationData: {
        type: Object,
        default: null,
    },
    searchQuery: {
        type: String,
        default: '',
    },
    banks: {
        type: Array,
        default: () => [],
    },
    companyName: {
        type: String,
        default: 'VIRUZS',
    },
    companyPhone: {
        type: String,
        default: '088976397034',
    },
    companyAddress: {
        type: String,
        default: '',
    },
});

const q = ref(props.searchQuery || '');
const isSearching = ref(false);

const handleSearch = () => {
    const query = q.value.trim();
    if (!query) return;
    isSearching.value = true;
    router.get(route('customer.isolated'), { q: query }, {
        preserveState: true,
        onFinish: () => {
            isSearching.value = false;
        },
    });
};
</script>

<template>
    <Head title="Pemberitahuan Penangguhan Layanan Internet — Template Isolir" />

    <div class="min-h-screen bg-[#070b14] text-slate-100 font-sans flex flex-col relative overflow-x-hidden selection:bg-rose-500 selection:text-white">
        <!-- Ambient Glowing Background Grid -->
        <div class="fixed inset-0 pointer-events-none">
            <div class="absolute -top-40 -left-40 w-[600px] h-[600px] bg-rose-600/10 rounded-full blur-[140px]"></div>
            <div class="absolute top-1/3 -right-40 w-[550px] h-[550px] bg-indigo-600/10 rounded-full blur-[140px]"></div>
            <div class="absolute -bottom-40 left-1/3 w-[600px] h-[600px] bg-cyan-600/10 rounded-full blur-[140px]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:32px_32px]"></div>
        </div>

        <!-- Top Navigation Bar -->
        <header class="relative z-20 border-b border-slate-800/80 bg-slate-950/60 backdrop-blur-md sticky top-0">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <AppLogo context="navbar" white />
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                        SISTEM NOTIFIKASI ISOLIR
                    </span>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        href="/login?portal=customer"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold border border-slate-700 transition-all flex items-center gap-1.5"
                    >
                        <UserIcon class="w-3.5 h-3.5 text-indigo-400" />
                        <span>Portal Pelanggan</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="relative z-10 flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">
            <!-- Jika Data Isolir Pelanggan Ditemukan -->
            <section v-if="isolationData" class="space-y-6">
                <CustomerIsolatedBanner
                    :customer="isolationData.customer"
                    :invoices="isolationData.invoices"
                    :total-unpaid="isolationData.total_unpaid"
                    :banks="isolationData.banks"
                    :company-phone="isolationData.company_phone || companyPhone"
                    :company-name="isolationData.company_name || companyName"
                    mode="full"
                    :can-continue-to-dashboard="Boolean(isolationData.user_id)"
                />

                <!-- Tombol Cari ID Lain -->
                <div class="flex justify-center">
                    <button
                        type="button"
                        @click="router.get(route('customer.isolated'))"
                        class="text-xs text-slate-400 hover:text-slate-200 transition-colors flex items-center gap-1.5 underline decoration-slate-700 underline-offset-4"
                    >
                        <span>Periksa ID Pelanggan lain atau masukkan nomor lain &rarr;</span>
                    </button>
                </div>
            </section>

            <!-- Jika Data Tidak Ditemukan / Form Pencarian Awal -->
            <section v-else class="space-y-8">
                <!-- Hero Header Card -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-[#180a1c] to-slate-900 border border-rose-500/30 p-8 sm:p-12 text-center shadow-2xl">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-black tracking-wider uppercase mb-5">
                        <SignalSlashIcon class="w-4 h-4 text-rose-400" />
                        PEMBERITAHUAN ISOLIR LAYANAN
                    </div>

                    <h1 class="text-2xl sm:text-4xl md:text-5xl font-black text-white tracking-tight max-w-2xl mx-auto leading-tight">
                        Layanan Internet Terputus atau Tertangguhkan?
                    </h1>
                    <p class="mt-3 sm:mt-4 text-xs sm:text-base text-slate-300 max-w-xl mx-auto leading-relaxed">
                        Masukkan ID Pelanggan Anda (cth: <span class="font-mono text-indigo-300">CUST-0012</span>) atau nomor WhatsApp yang terdaftar untuk memeriksa rincian tagihan dan membuka isolir secara otomatis.
                    </p>

                    <!-- Search Box Form -->
                    <form @submit.prevent="handleSearch" class="mt-8 max-w-md mx-auto flex flex-col sm:flex-row items-center gap-2.5">
                        <div class="relative w-full">
                            <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                v-model="q"
                                type="text"
                                placeholder="ID Pelanggan / No WhatsApp"
                                required
                                class="w-full pl-11 pr-4 py-3.5 bg-slate-950/80 border border-slate-700 rounded-2xl text-white placeholder-slate-500 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition-all font-mono"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="isSearching"
                            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-rose-600 to-indigo-600 hover:from-rose-500 hover:to-indigo-500 text-white text-xs font-bold transition-all shadow-lg shadow-rose-950/50 flex items-center justify-center gap-2 shrink-0 disabled:opacity-50"
                        >
                            <span>{{ isSearching ? 'Memeriksa…' : 'Cek Status' }}</span>
                            <ArrowRightIcon v-if="!isSearching" class="w-4 h-4" />
                        </button>
                    </form>

                    <p v-if="searchQuery && !isolationData" class="mt-4 text-xs text-rose-400 font-semibold">
                        Data pelanggan untuk pencarian "{{ searchQuery }}" tidak ditemukan. Pastikan ID Pelanggan atau nomor HP sesuai.
                    </p>
                </div>

                <!-- Template Preview Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 space-y-3">
                        <div class="w-10 h-10 rounded-2xl bg-cyan-500/20 border border-cyan-500/30 text-cyan-400 flex items-center justify-center">
                            <SparklesIcon class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm font-bold text-white">Pembukaan Otomatis</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Begitu tagihan terverifikasi lunas, server RADIUS secara otomatis memindahkan profil router Anda ke paket aktif tanpa perlu restart manual.
                        </p>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 space-y-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/20 border border-rose-500/30 text-rose-400 flex items-center justify-center">
                            <CreditCardIcon class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm font-bold text-white">Banyak Metode Pembayaran</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Mendukung pembayaran instan melalui scan QRIS, transfer bank resmi (BCA, BRI, Mandiri), hingga loket mitra biller terdekat.
                        </p>
                    </div>

                    <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 space-y-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center">
                            <CheckBadgeIcon class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm font-bold text-white">Layanan Siaga 24/7</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Butuh bantuan darurat atau konfirmasi cepat? Tim CS {{ companyName }} siap membantu Anda melalui kanal WhatsApp resmi.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Tanya Jawab Seputar Isolir (FAQ) -->
            <section class="bg-slate-900/40 border border-slate-800/80 rounded-3xl p-6 sm:p-8 space-y-4">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <QuestionMarkCircleIcon class="w-4 h-4 text-indigo-400" />
                    <span>Pertanyaan Umum Seputar Isolir Layanan</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-300">
                    <div class="bg-slate-950/60 p-4 rounded-2xl border border-slate-800/60 space-y-1">
                        <strong class="text-white text-sm block">Kapan layanan saya kena isolir?</strong>
                        <p class="text-slate-400">
                            Isolir otomatis diterapkan oleh sistem billing apabila terdapat tagihan internet bulanan yang belum diselesaikan setelah melewati tanggal jatuh tempo yang telah ditentukan.
                        </p>
                    </div>

                    <div class="bg-slate-950/60 p-4 rounded-2xl border border-slate-800/60 space-y-1">
                        <strong class="text-white text-sm block">Berapa lama internet aktif kembali setelah bayar?</strong>
                        <p class="text-slate-400">
                            Untuk pembayaran online via QRIS atau transfer yang terverifikasi, sistem RADIUS akan membuka isolir dan memulihkan akses internet dalam waktu 1–5 menit.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
            <p>&copy; {{ new Date().getFullYear() }} <strong class="text-slate-400">{{ companyName }}</strong>. Smart Billing &amp; High-Speed Internet Access.</p>
        </footer>
    </div>
</template>
