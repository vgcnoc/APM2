<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    user: Object
});
</script>

<template>
    <Head title="Profil Pelanggan Belum Terhubung" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4 text-slate-100">
        <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 rounded-3xl p-8 text-center shadow-2xl backdrop-blur-xl space-y-6">
            <!-- Icon -->
            <div class="w-16 h-16 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-2xl flex items-center justify-center mx-auto shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Content -->
            <div class="space-y-2">
                <h1 class="text-xl font-bold text-white tracking-tight">Akun Belum Ditautkan</h1>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Halo, <span class="text-indigo-400 font-semibold">{{ user?.name || 'Pelanggan' }}</span>! Akun login Anda sudah terdaftar, namun belum terhubung ke data nomor ID Pelanggan aktif.
                </p>
            </div>

            <!-- Notice Box -->
            <div class="p-4 bg-slate-800/60 rounded-2xl border border-slate-700/60 text-xs text-slate-300 text-left space-y-2">
                <div class="flex items-center gap-1.5 font-semibold text-slate-200">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Langkah Selanjutnya:</span>
                </div>
                <p class="leading-relaxed text-slate-400">
                    Silakan hubungi Customer Service kami untuk menautkan ID Pelanggan internet Anda ke akun ini, atau pastikan email yang Anda gunakan saat aktivasi sesuai.
                </p>
            </div>

            <!-- Actions -->
            <div class="pt-2 flex flex-col gap-2.5">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20"
                >
                    Keluar / Ganti Akun
                </Link>
            </div>
        </div>
    </div>
</template>
