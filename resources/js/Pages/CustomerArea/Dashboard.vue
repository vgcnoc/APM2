<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    latestInvoice: Object,
    recentTickets: Array
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Math.round(Number(amount) || 0));
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head title="Area Pelanggan — Beranda" />

    <CustomerLayout>
        <!-- Top Profile Welcome Banner -->
        <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl mb-8 border border-slate-800">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white text-xl sm:text-2xl font-black shadow-lg shadow-indigo-600/30">
                        {{ (customer?.name || 'P').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">{{ customer?.name || 'Pelanggan' }}</h1>
                            <span
                                v-if="customer?.status === 'active'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                AKTIF
                            </span>
                            <span
                                v-else-if="customer?.status === 'suspended'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30"
                            >
                                ISOLIR
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/20 text-slate-300 border border-slate-500/30 capitalize"
                            >
                                {{ customer?.status || 'Customer' }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1 flex items-center gap-2">
                            <span>ID Pelanggan:</span>
                            <span class="font-mono font-bold text-indigo-300 bg-indigo-950/60 px-2 py-0.5 rounded border border-indigo-800/50">
                                {{ customer?.customer_code || '-' }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex items-center gap-3 flex-wrap">
                    <Link
                        href="/my/billing"
                        class="px-4 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-white text-xs font-bold border border-slate-700 flex items-center gap-2 transition-colors shadow-xs"
                    >
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Lihat Tagihan
                    </Link>
                    <Link
                        href="/my/tickets"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold flex items-center gap-2 transition-all shadow-md shadow-indigo-600/30"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Lapor Gangguan
                    </Link>
                </div>
            </div>
        </div>

        <!-- 3 Feature Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- 1. Paket Info Card -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Layanan Internet</span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium">Paket Langganan</p>
                    <h3 class="text-xl font-black text-slate-900 mt-0.5">
                        {{ customer?.package?.name || 'Paket Standar' }}
                    </h3>
                    <p class="text-xs text-indigo-600 font-semibold mt-1">
                        Kecepatan: {{ customer?.package?.speed || '-' }} Mbps Unlimited
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Tarif Bulanan</span>
                    <span class="font-bold text-slate-900 font-mono">
                        {{ formatRupiah(customer?.package?.price) }} / bln
                    </span>
                </div>
            </div>

            <!-- 2. Tagihan Card -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6 flex flex-col justify-between md:col-span-2 relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-36 h-36 bg-rose-50 rounded-full pointer-events-none"></div>

                <div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center" :class="latestInvoice ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'">
                                <svg v-if="latestInvoice" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Status Tagihan Pelanggan</h3>
                                <p class="text-xs text-slate-500">
                                    {{ latestInvoice ? 'Tagihan menunggu pembayaran' : 'Semua tagihan Anda lunas' }}
                                </p>
                            </div>
                        </div>

                        <span
                            v-if="latestInvoice"
                            class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200 uppercase"
                        >
                            {{ latestInvoice.status }}
                        </span>
                        <span
                            v-else
                            class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase"
                        >
                            LUNAS
                        </span>
                    </div>

                    <!-- Jika ada tagihan belum lunas -->
                    <div v-if="latestInvoice" class="mt-4">
                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-2">
                            <p class="text-2xl sm:text-3xl font-black text-rose-600 font-mono tracking-tight">
                                {{ formatRupiah(latestInvoice.amount) }}
                            </p>
                            <span class="text-xs text-slate-500 font-medium">
                                Invoice: <strong class="text-slate-700 font-mono">{{ latestInvoice.invoice_number }}</strong>
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Jatuh Tempo: <strong class="text-rose-700">{{ formatDate(latestInvoice.due_date) }}</strong>
                        </p>
                    </div>

                    <!-- Jika tidak ada tagihan / lunas -->
                    <div v-else class="mt-4 py-3 flex items-center gap-3 text-emerald-700">
                        <p class="text-sm font-semibold">Tidak ada tagihan tertunggak saat ini. Terima kasih atas ketepatan pembayaran Anda!</p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                    <Link href="/my/billing" class="text-xs font-bold text-slate-600 hover:text-indigo-600 transition-colors">
                        Lihat Riwayat Semua Invoice &rarr;
                    </Link>

                    <Link
                        v-if="latestInvoice"
                        :href="route('payment-gateway.pay', latestInvoice.id)"
                        class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-600/30 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Bayar Online Sekarang (QRIS)
                    </Link>
                </div>
            </div>
        </div>

        <!-- Technical & Location Detail Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Lokasi Pemasangan -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span>Lokasi & Area</span>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Wilayah / Area</p>
                    <p class="text-sm font-bold text-slate-900">{{ customer?.areaModel?.name || customer?.area || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Alamat Terdaftar</p>
                    <p class="text-xs text-slate-700 leading-relaxed">{{ customer?.address || '-' }}</p>
                </div>
            </div>

            <!-- Sambungan / ONT -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                    <span>Perangkat Jaringan</span>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Perangkat ONT / Router</p>
                    <p class="text-sm font-bold text-slate-900">{{ customer?.ont?.model || 'Optical Network Terminal' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Serial Number (SN)</p>
                    <p class="text-xs font-mono font-semibold text-slate-700">{{ customer?.ont?.sn || 'Terkonfigurasi Otomatis' }}</p>
                </div>
            </div>

            <!-- Kontak Layanan -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span>Layanan Bantuan</span>
                </div>
                <div>
                    <p class="text-xs text-slate-500">No. WhatsApp Pelanggan</p>
                    <p class="text-sm font-bold text-slate-900 font-mono">{{ customer?.phone || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Tanggal Mulai Berlangganan</p>
                    <p class="text-xs font-semibold text-slate-700">{{ formatDate(customer?.activation_date) }}</p>
                </div>
            </div>
        </div>

        <!-- Tiket Bantuan & Gangguan Terakhir -->
        <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 flex-wrap gap-3">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Riwayat Tiket Gangguan Terakhir</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pantau status penanganan keluhan teknis internet Anda</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/my/tickets" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-colors">
                        + Ajukan Tiket Baru
                    </Link>
                </div>
            </div>
            
            <div v-if="recentTickets && recentTickets.length > 0" class="divide-y divide-slate-100">
                <div v-for="ticket in recentTickets" :key="ticket.id" class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 rounded-xl px-2 transition-colors">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-slate-900">{{ ticket.ticket_number }}</span>
                            <span class="text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded font-medium">{{ ticket.category }}</span>
                        </div>
                        <p class="text-xs font-semibold text-slate-800">{{ ticket.subject || 'Keluhan Layanan' }}</p>
                        <p class="text-xs text-slate-500 leading-relaxed">{{ (ticket.description || '').substring(0, 90) }}...</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-3">
                        <span
                            v-if="ticket.status === 'open'"
                            class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-bold"
                        >
                            Menunggu Penanganan
                        </span>
                        <span
                            v-else-if="ticket.status === 'in_progress'"
                            class="px-2.5 py-1 bg-cyan-50 text-cyan-700 border border-cyan-200 rounded-full text-xs font-bold"
                        >
                            Sedang Dikerjakan
                        </span>
                        <span
                            v-else
                            class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold"
                        >
                            Selesai Ditangani
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-10">
                <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-xs font-semibold text-slate-600">Tidak ada keluhan atau gangguan.</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Layanan internet Anda berjalan lancar.</p>
            </div>
        </div>
    </CustomerLayout>
</template>
