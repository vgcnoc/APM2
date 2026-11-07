<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';
import { computed } from 'vue';

const props = defineProps({
    registeredMitra: {
        type: Object,
        default: () => null,
    },
    companyPhone: {
        type: String,
        default: '',
    },
    adminWhatsapp: {
        type: String,
        default: '',
    },
    resellerMinDeposit: {
        type: Number,
        default: 20000,
    },
    billerMinDeposit: {
        type: Number,
        default: 2000000,
    },
});

const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

const partner = computed(() => props.registeredMitra || {
    name: 'Calon Mitra',
    store_name: '',
    display_name: 'Calon Mitra',
    email: '-',
    phone: '-',
    tier: 'regular',
    tier_label: 'Mitra Reseller (Voucher & Eceran)',
    min_deposit: props.resellerMinDeposit || 20000,
});

const waMessage = computed(() => {
    const tier = partner.value.tier === 'master' ? 'Master Biller & Loket Pembayaran' : 'Mitra Reseller';
    const name = partner.value.name || partner.value.display_name;
    const store = partner.value.store_name ? ` (${partner.value.store_name})` : '';
    const email = partner.value.email;
    const phone = partner.value.phone;
    const deposit = formatRupiah(partner.value.min_deposit);

    return `Halo Admin VIRUZS, saya telah melakukan pendaftaran mandiri sebagai *${tier}*.\n\n`
        + `📋 *Data Pendaftaran:*\n`
        + `• Nama: ${name}${store}\n`
        + `• Email: ${email}\n`
        + `• WhatsApp: ${phone}\n`
        + `• Ketentuan Min. Saldo Awal: ${deposit}\n\n`
        + `Mohon bantuan verifikasi dan persetujuan (approval) aktivasi akun kemitraan saya. Terima kasih banyak! 🙏`;
});

const waUrl = computed(() => {
    const phone = props.adminWhatsapp || '6288976397034';
    return `https://wa.me/${phone}?text=${encodeURIComponent(waMessage.value)}`;
});
</script>

<template>
    <Head title="Pendaftaran Berhasil Dikirim - Menunggu Approval Admin" />

    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
        <!-- Abstract Background Glowing Elements -->
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-200/60 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-teal-200/60 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000 pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-cyan-200/40 rounded-full mix-blend-multiply filter blur-3xl opacity-40 pointer-events-none"></div>

        <div class="max-w-xl mx-auto w-full relative z-10 space-y-6">
            
            <!-- Logo & Brand Header -->
            <div class="text-center flex flex-col items-center">
                <Link :href="route('login', { portal: 'reseller' })" class="group inline-flex items-center justify-center p-3 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200/90 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all duration-200" title="Klik untuk kembali ke halaman login">
                    <AppLogo context="register" :force-light="true" align="center" />
                </Link>
            </div>

            <!-- Success / Pending Approval Card -->
            <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-3xl border border-white/60 overflow-hidden">
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 px-6 py-6 text-white text-center">
                    <div class="w-16 h-16 rounded-3xl bg-white/20 backdrop-blur-md text-3xl flex items-center justify-center mx-auto mb-3 shadow-inner border border-white/30">
                        ⏳
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 text-white text-[11px] font-black uppercase tracking-wider border border-white/20 mb-2">
                        Pendaftaran Berhasil Dikirim
                    </span>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight">
                        Menunggu Persetujuan Admin
                    </h1>
                    <p class="text-xs text-emerald-100 mt-1 max-w-md mx-auto">
                        Terima kasih! Formulir pendaftaran kemitraan Anda telah diterima oleh sistem dan saat ini sedang menunggu verifikasi serta persetujuan resmi dari Administrator.
                    </p>
                </div>

                <!-- Card Body -->
                <div class="p-6 sm:p-7 space-y-5">
                    
                    <!-- Rincian Data Kemitraan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-3">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center justify-between border-b border-slate-200/60 pb-2">
                            <span>📋 Ringkasan Pendaftaran</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="partner.tier === 'master' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800'">
                                {{ partner.tier === 'master' ? '👑 Master Biller' : '🏪 Mitra Reseller' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Pemilik:</span>
                                <strong class="text-slate-800 font-bold">{{ partner.name }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Toko / Loket:</span>
                                <strong class="text-slate-800 font-bold">{{ partner.store_name || '-' }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Email Akun:</span>
                                <span class="text-slate-800 font-medium">{{ partner.email }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nomor WhatsApp:</span>
                                <span class="text-slate-800 font-mono font-medium">{{ partner.phone }}</span>
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">Syarat Minimal Saldo Modal:</span>
                            <strong class="text-emerald-700 font-mono font-black text-sm">{{ formatRupiah(partner.min_deposit) }}</strong>
                        </div>
                    </div>

                    <!-- Box Langkah Aktivasi -->
                    <div class="p-4 bg-amber-50/80 rounded-2xl border border-amber-200 text-xs text-amber-950 space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-amber-900">
                            <span>💡</span> Langkah Selanjutnya Untuk Aktivasi Akun:
                        </div>
                        <ol class="space-y-1.5 text-[11.5px] text-amber-900/90 list-decimal list-inside leading-relaxed">
                            <li>
                                <strong>Konfirmasi Pendaftaran:</strong> Hubungi Admin via WhatsApp melalui tombol hijau di bawah untuk memberitahukan data registrasi Anda.
                            </li>
                            <li>
                                <strong>Isi Saldo Modal Awal:</strong> Lakukan pengisian saldo modal awal minimal <strong>{{ formatRupiah(partner.min_deposit) }}</strong> sesuai tipe kemitraan yang dipilih (<strong>{{ partner.tier_label }}</strong>).
                            </li>
                            <li>
                                <strong>Persetujuan &amp; Aktivasi:</strong> Admin akan melakukan verifikasi data dan menyetujui (approve) akun Anda. Setelah aktif, Anda dapat langsung login dan mulai melayani pelanggan.
                            </li>
                        </ol>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3 pt-1">
                        <a
                            :href="waUrl"
                            target="_blank"
                            class="w-full py-3.5 px-6 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span class="text-lg">💬</span>
                            <span>Konfirmasi Aktivasi ke Admin via WhatsApp</span>
                            <span>&rarr;</span>
                        </a>

                        <Link
                            :href="route('login', { portal: 'reseller' })"
                            class="w-full py-3 px-4 border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl transition text-center block"
                        >
                            Kembali ke Halaman Login Portal Mitra
                        </Link>
                    </div>

                </div>
            </div>

            <!-- Footer Note -->
            <p class="text-center text-xs text-gray-400">
                &copy; {{ new Date().getFullYear() }} VIRUZS.ISP Billing &amp; Reseller Infrastructure. All rights reserved.
            </p>

        </div>
    </div>
</template>

