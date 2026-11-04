<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';
import {
    ArrowRightIcon,
    ArrowUpRightIcon,
    BanknotesIcon,
    CheckIcon,
    ClipboardDocumentCheckIcon,
    ClipboardDocumentIcon,
    CreditCardIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MapPinIcon,
    PhoneIcon,
    ShieldExclamationIcon,
    SignalSlashIcon,
    UserIcon,
    WifiIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
    invoices: {
        type: Array,
        default: () => [],
    },
    totalUnpaid: {
        type: Number,
        default: 0,
    },
    banks: {
        type: Array,
        default: () => [],
    },
    companyPhone: {
        type: String,
        default: '088976397034',
    },
    companyName: {
        type: String,
        default: 'VIRUZS',
    },
    mode: {
        type: String,
        default: 'full', // 'full' or 'compact'
    },
    canContinueToDashboard: {
        type: Boolean,
        default: false,
    },
    showCloseButton: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'continue-dashboard']);

// State untuk feedback copy to clipboard
const copiedBankIndex = ref(null);
const copiedCode = ref(false);

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Math.round(Number(val) || 0));
};

const copyAccountNumber = (accNumber, index) => {
    if (!accNumber) return;
    navigator.clipboard.writeText(String(accNumber)).then(() => {
        copiedBankIndex.value = index;
        setTimeout(() => {
            copiedBankIndex.value = null;
        }, 2200);
    }).catch(() => {});
};

const copyCustomerCode = (code) => {
    if (!code) return;
    navigator.clipboard.writeText(String(code)).then(() => {
        copiedCode.value = true;
        setTimeout(() => {
            copiedCode.value = false;
        }, 2200);
    }).catch(() => {});
};

// URL WhatsApp CS dengan format teks konfirmasi otomatis
const waLink = computed(() => {
    let cleanPhone = (props.companyPhone || '088976397034').replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.substring(1);
    }
    const custName = props.customer?.name || 'Pelanggan';
    const custCode = props.customer?.customer_code || '-';
    const total = formatRupiah(props.totalUnpaid);

    const message = `Halo Layanan Pelanggan ${props.companyName}, saya ingin konfirmasi pembayaran tagihan internet yang sedang terisolir:\n\n`
        + `• Nama: ${custName}\n`
        + `• ID Pelanggan: ${custCode}\n`
        + `• Total Tagihan: ${total}\n\n`
        + `Mohon bantuan untuk verifikasi dan pembukaan kembali isolir koneksi internet saya. Terima kasih.`;

    return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
});

const continueLogin = () => {
    router.post(route('customer.isolated.continue'));
};
</script>

<template>
    <div
        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-[#190a1f] to-slate-950 border border-rose-500/30 text-white shadow-2xl transition-all duration-300"
        :class="mode === 'compact' ? 'p-5 sm:p-7' : 'p-6 sm:p-9'"
    >
        <!-- Background Ambient Neon Glows -->
        <div class="absolute -right-24 -top-24 w-96 h-96 bg-rose-600/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#f43f5e15_1px,transparent_1px)] [background-size:24px_24px] opacity-40 pointer-events-none"></div>

        <!-- Tombol Tutup Opsional (misal pada modal) -->
        <button
            v-if="showCloseButton"
            type="button"
            @click="emit('close')"
            class="absolute top-5 right-5 p-2 rounded-xl bg-slate-900/60 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/60 transition-colors z-20"
            aria-label="Tutup pemberitahuan"
        >
            <XMarkIcon class="w-5 h-5" />
        </button>

        <div class="relative z-10 space-y-6">
            <!-- Top Corporate Brand Bar with Company Logo -->
            <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <AppLogo context="navbar" :prefer-company-logo="true" white class="max-h-8 sm:max-h-9" />
                    <div class="hidden sm:block h-4 w-[1px] bg-slate-800"></div>
                    <span class="hidden sm:inline-block text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                        {{ companyName }} Network Security &amp; Billing
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-slate-900/90 text-slate-300 border border-slate-800 shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                        Portal Resmi Isolir
                    </span>
                </div>
            </div>

            <!-- 1. Header Alert Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-rose-500/20 pb-5">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-400 flex items-center justify-center shadow-lg shadow-rose-950/50 shrink-0">
                        <SignalSlashIcon class="w-6 h-6 animate-pulse" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black tracking-wider uppercase bg-rose-500/25 text-rose-300 border border-rose-500/40 shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-rose-400 animate-ping"></span>
                                LAYANAN DIISOLIR SEMENTARA
                            </span>
                            <span v-if="customer.isolated_at" class="text-[11px] text-slate-400 font-mono">
                                Sejak: {{ customer.isolated_at }}
                            </span>
                        </div>
                        <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight mt-1.5">
                            Akses Internet Anda Ditangguhkan
                        </h2>
                    </div>
                </div>

                <!-- Total Tunggakan Badge di Header -->
                <div class="flex flex-col sm:items-end justify-center bg-rose-950/40 border border-rose-500/30 rounded-2xl px-4 py-2.5 sm:px-5 sm:py-3 shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-300">Total Tagihan Tertunggak</span>
                    <span class="text-xl sm:text-2xl font-black font-mono text-rose-400 tracking-tight">
                        {{ formatRupiah(totalUnpaid) }}
                    </span>
                </div>
            </div>

            <!-- Pesan Pengantar Ramah & Jelas -->
            <div class="bg-rose-950/20 border border-rose-500/20 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5">
                <InformationCircleIcon class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Koneksi internet pada perangkat ONT / Router Anda sedang dinonaktifkan sementara karena terdapat tagihan langganan yang belum diselesaikan.
                    <strong class="text-white font-semibold">Internet Anda akan otomatis aktif kembali dalam beberapa menit</strong> segera setelah pembayaran berhasil diverifikasi oleh sistem.
                </p>
            </div>

            <!-- 2. Kartu Identitas Pelanggan & Paket -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Info Pelanggan -->
                <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mb-2">
                            <UserIcon class="w-3.5 h-3.5 text-indigo-400" />
                            Data Pelanggan
                        </span>
                        <h3 class="text-base sm:text-lg font-bold text-white truncate">
                            {{ customer.name }}
                        </h3>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-indigo-300 bg-indigo-950/80 px-2.5 py-1 rounded-lg border border-indigo-800/50">
                                {{ customer.customer_code }}
                            </span>
                            <button
                                type="button"
                                @click="copyCustomerCode(customer.customer_code)"
                                class="text-[11px] text-slate-400 hover:text-white flex items-center gap-1 transition-colors px-1.5 py-1 rounded bg-slate-800/50"
                                title="Salin ID Pelanggan"
                            >
                                <component :is="copiedCode ? ClipboardDocumentCheckIcon : ClipboardDocumentIcon" class="w-3.5 h-3.5" :class="copiedCode ? 'text-emerald-400' : ''" />
                                <span>{{ copiedCode ? 'Tersalin' : 'Salin' }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] text-slate-400 flex items-center gap-1.5">
                        <MapPinIcon class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                        <span class="truncate">{{ customer.area }}</span>
                    </div>
                </div>

                <!-- Info Paket Layanan -->
                <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mb-2">
                            <WifiIcon class="w-3.5 h-3.5 text-cyan-400" />
                            Paket Internet
                        </span>
                        <h3 class="text-base sm:text-lg font-bold text-white truncate">
                            {{ customer.package_name }}
                        </h3>
                        <p class="text-xs text-cyan-300 font-semibold mt-1">
                            Kecepatan: {{ customer.package_speed }}
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Tarif Bulanan:</span>
                        <span class="font-bold font-mono text-white">{{ formatRupiah(customer.package_price) }}</span>
                    </div>
                </div>

                <!-- Status Sambungan -->
                <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mb-2">
                            <ShieldExclamationIcon class="w-3.5 h-3.5 text-rose-400" />
                            Status Koneksi
                        </span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-sm shadow-rose-500 animate-pulse"></span>
                            <span class="text-base font-black text-rose-400 tracking-wide uppercase">TERISOLIR</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">
                            Sistem RADIUS memblokir lalu lintas internet hingga tagihan terlunasi.
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Auto-Reopen:</span>
                        <span class="text-emerald-400 font-bold flex items-center gap-1">
                            <CheckIcon class="w-3.5 h-3.5" /> Otomatis Aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Rincian Tagihan Belum Lunas -->
            <div v-if="invoices && invoices.length" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <BanknotesIcon class="w-4 h-4 text-rose-400" />
                        <h4 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider">
                            Rincian Tagihan Tertunggak ({{ invoices.length }})
                        </h4>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Pilih pembayaran untuk proses instan</span>
                </div>

                <div class="divide-y divide-slate-800/60">
                    <div
                        v-for="inv in invoices"
                        :key="inv.id || inv.invoice_number"
                        class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group"
                    >
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-xs font-bold text-white bg-slate-800 px-2 py-0.5 rounded border border-slate-700">
                                    {{ inv.invoice_number }}
                                </span>
                                <span class="text-xs font-semibold text-slate-200">
                                    {{ inv.period_label }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1.5">
                                <span>Jatuh Tempo:</span>
                                <strong class="text-rose-400">{{ inv.due_date }}</strong>
                            </p>
                        </div>

                        <div class="flex items-center gap-3 justify-between sm:justify-end">
                            <span class="text-base sm:text-lg font-black font-mono text-rose-400">
                                {{ formatRupiah(inv.remaining) }}
                            </span>

                            <!-- Tombol Bayar Online QRIS jika ada ID -->
                            <a
                                v-if="inv.id"
                                :href="`/payment-gateway/pay/${inv.id}`"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-rose-600 to-indigo-600 hover:from-rose-500 hover:to-indigo-500 text-white text-xs font-bold shadow-md shadow-rose-950/40 transition-all hover:scale-105 active:scale-95 shrink-0"
                            >
                                <CreditCardIcon class="w-3.5 h-3.5" />
                                <span>Bayar Sekarang (QRIS)</span>
                                <ArrowUpRightIcon class="w-3 h-3" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Pilihan Cara Pembayaran Resmi & Rekening Bank -->
            <div class="space-y-3">
                <h4 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <CreditCardIcon class="w-4 h-4 text-indigo-400" />
                    Kanal Pembayaran Resmi
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- Rekening Bank Resmi -->
                    <div
                        v-for="(b, idx) in banks"
                        :key="b.account_number || idx"
                        class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between hover:border-slate-700 transition-colors"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-xs uppercase text-indigo-300 px-2 py-0.5 rounded bg-indigo-950/80 border border-indigo-800/40">
                                    {{ b.bank_name }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyAccountNumber(b.account_number, idx)"
                                    class="text-[11px] font-medium text-slate-400 hover:text-white flex items-center gap-1 px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 transition-colors"
                                >
                                    <component
                                        :is="copiedBankIndex === idx ? ClipboardDocumentCheckIcon : ClipboardDocumentIcon"
                                        class="w-3.5 h-3.5"
                                        :class="copiedBankIndex === idx ? 'text-emerald-400' : ''"
                                    />
                                    <span>{{ copiedBankIndex === idx ? 'Tersalin!' : 'Salin No' }}</span>
                                </button>
                            </div>
                            <p class="font-mono text-base sm:text-lg font-black text-white tracking-wider mt-1 select-all">
                                {{ b.account_number }}
                            </p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">
                                a.n {{ b.account_name }}
                            </p>
                        </div>
                    </div>

                    <!-- Loket Mitra Biller Terdekat -->
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between hover:border-slate-700 transition-colors">
                        <div>
                            <span class="font-bold text-xs uppercase text-emerald-300 px-2 py-0.5 rounded bg-emerald-950/80 border border-emerald-800/40 inline-block mb-1.5">
                                Loket Tunai Mitra
                            </span>
                            <p class="text-xs font-bold text-white mt-1">
                                Bayar via Loket Biller Terdekat
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                Cukup sebutkan ID Pelanggan <strong class="text-slate-200 font-mono">{{ customer.customer_code }}</strong> kepada kasir loket mitra kami.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Tombol Aksi & Konfirmasi Bantuan WhatsApp -->
            <div class="pt-3 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <!-- Tombol WhatsApp CS Konfirmasi -->
                    <a
                        :href="waLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-950/40 transition-all hover:scale-102 active:scale-98"
                    >
                        <PhoneIcon class="w-4 h-4" />
                        <span>Konfirmasi via WhatsApp CS</span>
                        <ArrowUpRightIcon class="w-3.5 h-3.5 opacity-80" />
                    </a>

                    <!-- Tombol Masuk ke Dashboard Pelanggan jika sesi tersedia -->
                    <button
                        v-if="canContinueToDashboard"
                        type="button"
                        @click="continueLogin"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold border border-slate-700 transition-colors"
                    >
                        <span>Lanjut ke Dashboard Pelanggan</span>
                        <ArrowRightIcon class="w-4 h-4 text-slate-400" />
                    </button>
                </div>

                <div class="text-[11px] text-slate-400 text-center sm:text-right">
                    <span>Butuh bantuan teknis? Hubungi </span>
                    <strong class="text-slate-300">{{ companyPhone }}</strong>
                </div>
            </div>
        </div>
    </div>
</template>

