<template>
    <Head title="Pembayaran Online - APM" />

    <div class="min-h-screen bg-slate-50 flex flex-col justify-between">
        <!-- Header -->
        <header class="bg-white border-b border-gray-100 shadow-2xs sticky top-0 z-40">
            <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="goBack" class="p-2 -ml-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors cursor-pointer" title="Kembali">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </button>
                    <div>
                        <h1 class="text-base font-bold text-gray-900 leading-tight">Pembayaran Online</h1>
                        <p class="text-xs text-gray-500">Sistem Pembayaran Otomatis</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold" :class="invoice.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                        <span class="w-2 h-2 rounded-full" :class="invoice.status === 'paid' ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
                        {{ invoice.status === 'paid' ? 'Lunas' : 'Menunggu Pembayaran' }}
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 py-6 w-full flex-1 space-y-6">
            
            <!-- Jika Sudah Lunas -->
            <div v-if="invoice.status === 'paid'" class="bg-white rounded-3xl p-8 border border-emerald-100 shadow-xl text-center space-y-4 animate-fade-in-up">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-gray-900">Pembayaran Berhasil!</h2>
                    <p class="text-sm text-gray-500 mt-1">Invoice {{ invoice.invoice_number }} telah lunas dan terverifikasi otomatis.</p>
                </div>
                <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-100 max-w-sm mx-auto text-xs text-emerald-800 space-y-1 text-left">
                    <div class="flex justify-between">
                        <span>Nominal:</span>
                        <strong class="font-bold">Rp {{ Number(invoice.amount).toLocaleString('id-ID') }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Metode:</span>
                        <strong class="font-bold uppercase">{{ invoice.payment_channel || 'Payment Gateway' }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu:</span>
                        <strong class="font-bold">{{ new Date().toLocaleString('id-ID') }}</strong>
                    </div>
                </div>
                <div class="pt-2">
                    <button @click="goBack" class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-sm font-bold shadow-md transition cursor-pointer">
                        Kembali ke Dashboard
                    </button>
                </div>
            </div>

            <!-- Jika Belum Lunas -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Kolom Kiri: Rincian Tagihan & Pilihan Channel -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Kartu Ringkasan Tagihan -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Tagihan</span>
                                <div class="text-3xl font-black text-indigo-700 tracking-tight mt-0.5">
                                    Rp {{ Number(invoice.remaining > 0 ? invoice.remaining : invoice.amount).toLocaleString('id-ID') }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-gray-800 block font-mono">{{ invoice.invoice_number }}</span>
                                <span class="text-[11px] text-gray-400 block">{{ invoice.issued_date }}</span>
                            </div>
                        </div>

                        <!-- Info Customer / Reseller -->
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between text-gray-600">
                                <span>Tujuan Pembayaran:</span>
                                <strong class="font-bold text-gray-900">{{ invoice.notes || (invoice.is_reseller_balance ? 'Saldo Reseller' : 'Tagihan Internet Bulanan') }}</strong>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Nama Pembayar:</span>
                                <strong class="font-bold text-gray-900">{{ invoice.customer?.name || 'Pelanggan APM' }}</strong>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>No. WhatsApp:</span>
                                <span class="font-medium text-gray-700">{{ invoice.customer?.phone || '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pilihan Metode Pembayaran (Channels) -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-gray-900">Pilih Saluran Pembayaran</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button 
                                v-for="ch in channels" 
                                :key="ch.code"
                                @click="switchChannel(ch.code)"
                                type="button"
                                class="p-3.5 rounded-2xl border text-left transition-all flex items-center justify-between gap-3 cursor-pointer"
                                :class="selectedChannel === ch.code ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-100 hover:border-gray-200 hover:bg-gray-50/50'"
                            >
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-900 truncate">{{ ch.name }}</div>
                                    <div class="text-[10px] text-gray-500 truncate mt-0.5">{{ ch.description }}</div>
                                </div>
                                <div class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0" :class="selectedChannel === ch.code ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300'">
                                    <svg v-if="selectedChannel === ch.code" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Tampilan Pembayaran (QRIS / VA / Link) -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-5 text-center relative overflow-hidden">
                        
                        <!-- Mode QRIS -->
                        <div v-if="selectedChannel === 'QRIS'" class="space-y-4">
                            <div class="flex items-center justify-center gap-2">
                                <span class="px-2.5 py-1 bg-red-50 text-red-700 rounded-lg text-xs font-black tracking-widest border border-red-200">
                                    QRIS
                                </span>
                                <span class="text-xs font-bold text-gray-700">Scan untuk Bayar</span>
                            </div>

                            <!-- QR Code Box -->
                            <div class="p-4 bg-white rounded-2xl border-2 border-gray-200/80 inline-block shadow-inner mx-auto">
                                <img 
                                    :src="'https://api.qrserver.com/v1/create-qr-code/?size=230x230&margin=10&data=' + encodeURIComponent(paymentData.qr_string || paymentData.checkout_url || invoice.invoice_number)" 
                                    alt="QRIS Pembayaran"
                                    class="w-52 h-52 sm:w-56 sm:h-56 mx-auto object-contain rounded-lg"
                                />
                            </div>

                            <p class="text-[11px] text-gray-500 max-w-xs mx-auto">
                                Buka aplikasi <strong>BCA, BRImo, Livin, GoPay, OVO, Dana, ShopeePay</strong> atau bank lain lalu scan kode QR di atas.
                            </p>
                        </div>

                        <!-- Mode Virtual Account -->
                        <div v-else class="space-y-4 text-left">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <span class="text-xs font-bold text-gray-600 uppercase">{{ selectedChannel }} Virtual Account</span>
                                <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full font-bold">Otomatis Terverifikasi</span>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 text-center space-y-1">
                                <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">Nomor Rekening Virtual Account</span>
                                <div class="text-2xl font-black text-gray-900 font-mono tracking-wider flex items-center justify-center gap-2">
                                    <span>{{ paymentData.pay_code || ('8808' + invoice.id) }}</span>
                                    <button @click="copyToClipboard(paymentData.pay_code || ('8808' + invoice.id))" class="text-xs text-indigo-600 hover:text-indigo-800 p-1.5 rounded-lg hover:bg-white transition cursor-pointer" title="Salin">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                                <span v-if="copied" class="text-[10px] text-emerald-600 font-bold block animate-fade-in">✓ Nomor berhasil disalin!</span>
                            </div>
                        </div>

                        <!-- Tombol Simulasi / Cek Status Pembayaran Otomatis -->
                        <div class="pt-2 border-t border-gray-100 space-y-2">
                            <button 
                                @click="simulatePayment" 
                                :disabled="processing"
                                class="w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="!processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                <span>{{ processing ? 'Memverifikasi Pembayaran...' : 'Konfirmasi / Simulasi Pembayaran Sukses' }}</span>
                            </button>
                            <p class="text-[10px] text-gray-400">
                                Pada mode integrasi aktif, status pembayaran akan terupdate otomatis secara real-time via Webhook.
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    invoice: Object,
    channels: Array,
    paymentData: Object,
    config: Object,
});

const selectedChannel = ref(props.invoice.payment_channel || 'QRIS');
const copied = ref(false);
const processing = ref(false);

const switchChannel = (code) => {
    selectedChannel.value = code;
    router.post(route('payment-gateway.checkout'), {
        invoice_id: props.invoice.id,
        channel: code
    }, {
        preserveScroll: true
    });
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2500);
};

const simulatePayment = () => {
    processing.value = true;
    router.post(route('payment-gateway.simulate', props.invoice.id), {
        channel: selectedChannel.value
    }, {
        onFinish: () => {
            processing.value = false;
        }
    });
};

const goBack = () => {
    if (props.invoice.is_reseller_balance) {
        router.visit(route('client-area.dashboard'));
    } else {
        router.visit(route('customer-area.billing'));
    }
};
</script>
