<template>
    <Head title="Reseller Dashboard" />

    <ClientAreaLayout>
        <div class="space-y-8 pb-10">
            <!-- Hero Header Section -->
            <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-900 to-slate-900 rounded-3xl p-8 lg:p-12 shadow-2xl text-white">
                <!-- Decorative Background -->
                <div class="absolute top-0 right-0 -mr-16 -mt-16 opacity-30 pointer-events-none">
                    <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="200" cy="200" r="200" fill="url(#paint0_radial)" />
                        <defs>
                            <radialGradient id="paint0_radial" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(200 200) rotate(90) scale(200)">
                                <stop stop-color="white" />
                                <stop offset="1" stop-color="white" stop-opacity="0" />
                            </radialGradient>
                        </defs>
                    </svg>
                </div>
                
                <div class="relative z-10 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-10">
                    <div class="max-w-2xl">
                        <div class="inline-block px-4 py-1.5 mb-6 rounded-full bg-white/10 backdrop-blur-md text-xs font-bold tracking-widest uppercase text-indigo-200 border border-white/10 shadow-sm">
                            Reseller Portal
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-4 text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-100 to-blue-200">
                            Welcome back, {{ reseller.customer?.name || $page.props.auth.user.name }}!
                        </h1>
                        <p class="text-indigo-200/80 text-sm lg:text-lg leading-relaxed max-w-xl font-medium">
                            Kelola penjualan voucher, pantau performa bisnis Anda, dan berikan layanan internet terbaik untuk pelanggan di satu tempat dengan desain canggih.
                        </p>
                    </div>

                    <!-- Balance Cards -->
                    <div class="flex flex-col sm:flex-row gap-5 w-full xl:w-auto">
                        <!-- Total Pemasukan -->
                        <div class="bg-white/10 backdrop-blur-xl border border-white/20 p-6 rounded-3xl flex items-center gap-5 shadow-2xl flex-1 transform transition-all duration-300 hover:scale-[1.03] hover:bg-white/15 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <p class="text-xs font-bold text-emerald-200/80 uppercase tracking-widest mb-1">Total Pemasukan</p>
                                <p class="text-3xl font-black tracking-tight text-white drop-shadow-sm">Rp {{ Number(income || 0).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>

                        <!-- Sisa Saldo -->
                        <div class="bg-white/10 backdrop-blur-xl border border-white/20 p-6 rounded-3xl flex items-center gap-5 shadow-2xl flex-1 transform transition-all duration-300 hover:scale-[1.03] hover:bg-white/15 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <p class="text-xs font-bold text-blue-200/80 uppercase tracking-widest mb-1">Sisa Saldo Modal</p>
                                <p class="text-3xl font-black tracking-tight text-white drop-shadow-sm">Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Generate Voucher Card -->
                <div class="xl:col-span-1 relative group">
                    <!-- Subtle Glow Behind Card -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-3xl blur-xl opacity-20 group-hover:opacity-30 transition duration-500"></div>
                    
                    <div class="relative bg-white rounded-3xl p-7 sm:p-9 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/50 h-full flex flex-col">
                        <div class="flex flex-col gap-2 mb-8 border-b border-gray-50 pb-6">
                            <div class="w-14 h-14 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100/50 mb-2">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Generate Voucher</h2>
                            <p class="text-sm text-gray-500 font-medium">Buat akses internet untuk pelanggan Anda dengan cepat dan mudah.</p>
                        </div>

                        <form @submit.prevent="generateVoucher" class="space-y-6 flex-1 flex flex-col">
                            <div class="space-y-5 flex-1">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Pilih Paket Voucher</label>
                                    <div class="relative">
                                        <select v-model="form.profile_id" class="w-full appearance-none bg-gray-50/50 border border-gray-200 text-gray-900 text-sm font-bold rounded-2xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white block p-4 shadow-sm transition-all cursor-pointer">
                                            <option value="" disabled>-- Klik untuk memilih paket --</option>
                                            <option v-for="profile in profiles" :key="profile.id" :value="profile.id">
                                                {{ profile.name }} (Harga User: Rp {{ Number(profile.price).toLocaleString('id-ID') }})
                                            </option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.profile_id" class="text-red-500 text-xs font-medium mt-1.5 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ form.errors.profile_id }}
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Jumlah</label>
                                        <input v-model="form.qty" type="number" min="1" max="100" class="w-full bg-gray-50/50 border border-gray-200 text-gray-900 text-lg font-bold rounded-2xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white block p-4 shadow-sm transition-all" placeholder="10" />
                                        <div v-if="form.errors.qty" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.qty }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Kombinasi</label>
                                        <div class="relative">
                                            <select v-model="form.combination" class="w-full appearance-none bg-gray-50/50 border border-gray-200 text-gray-900 text-sm font-bold rounded-2xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white block p-4 shadow-sm transition-all cursor-pointer">
                                                <option value="alphanumeric">Abjad & Angka</option>
                                                <option value="numeric">Angka (0-9)</option>
                                                <option value="alpha">Abjad (A-Z)</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Total -->
                                <div v-if="selectedProfile" class="mt-4 p-5 bg-gradient-to-br from-indigo-50/50 to-blue-50/50 rounded-2xl border border-indigo-100/50 space-y-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-indigo-900/60 font-bold uppercase tracking-wider text-xs">Harga Jual (User)</span>
                                        <span class="font-bold text-gray-800">Rp {{ Number(selectedProfile.price).toLocaleString('id-ID') }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-indigo-900/60 font-bold uppercase tracking-wider text-xs">Harga Modal (Reseller)</span>
                                        <span class="font-bold text-indigo-600">Rp {{ Number(selectedProfile.fee_reseller).toLocaleString('id-ID') }}</span>
                                    </div>
                                    <div class="h-px bg-indigo-100/60 w-full my-2"></div>
                                    <div class="flex justify-between items-center text-lg">
                                        <span class="text-indigo-900 font-black">Total Potongan</span>
                                        <span class="font-black text-indigo-600 bg-indigo-100/50 px-3 py-1 rounded-lg">Rp {{ Number(selectedProfile.fee_reseller * (form.qty || 0)).toLocaleString('id-ID') }}</span>
                                    </div>
                                    <div v-if="(selectedProfile.fee_reseller * (form.qty || 0)) > reseller.balance" class="bg-red-50 text-red-600 p-3 rounded-xl text-sm font-bold mt-3 flex items-center gap-2 border border-red-100">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Saldo Anda tidak mencukupi!
                                    </div>
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                class="w-full py-4 bg-gradient-to-r from-indigo-600 via-blue-600 to-indigo-600 bg-[length:200%_auto] hover:bg-[position:right_center] text-white rounded-2xl font-black text-lg transition-all duration-300 shadow-[0_10px_20px_rgba(79,_70,_229,_0.3)] disabled:opacity-50 disabled:cursor-not-allowed hover:-translate-y-1 mt-auto flex justify-center items-center gap-2"
                                :disabled="form.processing || !form.profile_id || !form.qty || ((selectedProfile?.fee_reseller * form.qty) > reseller.balance)"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ form.processing ? 'Memproses...' : 'Generate Sekarang' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- History / Table -->
                <div class="xl:col-span-2">
                    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/50 overflow-hidden flex flex-col h-full">
                        <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white">
                            <div>
                                <h2 class="text-2xl font-black text-gray-900 tracking-tight">Riwayat Voucher</h2>
                                <p class="text-sm text-gray-500 font-medium mt-1">Daftar voucher yang telah Anda generate sebelumnya.</p>
                            </div>
                            <button @click="printAll" class="px-5 py-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-2xl text-sm text-gray-700 font-bold flex items-center gap-2 transition-all shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print Voucher
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto flex-1 p-2">
                            <table class="min-w-full border-separate" style="border-spacing: 0 0.5rem;">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-widest bg-white">Kode</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-widest bg-white">Paket</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-widest bg-white">Harga Jual</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-widest bg-white">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-400 uppercase tracking-widest bg-white">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="voucher in vouchers.data" :key="voucher.id" class="group transition-all hover:-translate-y-0.5 hover:shadow-md bg-gray-50/50 hover:bg-white rounded-2xl">
                                        <td class="px-6 py-5 whitespace-nowrap text-sm font-black text-gray-900 tracking-widest rounded-l-2xl border-y border-l border-transparent group-hover:border-gray-100">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                                </div>
                                                {{ voucher.code }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap text-sm font-bold text-gray-600 border-y border-transparent group-hover:border-gray-100">
                                            {{ voucher.profile ? voucher.profile.name : '-' }}
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-900 font-black border-y border-transparent group-hover:border-gray-100">
                                            Rp {{ Number(voucher.profile ? voucher.profile.price : 0).toLocaleString('id-ID') }}
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap border-y border-transparent group-hover:border-gray-100">
                                            <span v-if="voucher.status === 'available'" class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-xl bg-emerald-100/50 text-emerald-600 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 mt-1.5"></span>
                                                Tersedia
                                            </span>
                                            <span v-else-if="voucher.status === 'used'" class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-xl bg-amber-100/50 text-amber-600 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-2 mt-1.5"></span>
                                                Terpakai
                                            </span>
                                            <span v-else class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-xl bg-gray-100/50 text-gray-600 border border-gray-200">
                                                {{ voucher.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-bold text-gray-400 rounded-r-2xl border-y border-r border-transparent group-hover:border-gray-100">
                                            {{ new Date(voucher.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                        </td>
                                    </tr>
                                    <tr v-if="vouchers.data.length === 0">
                                        <td colspan="5" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                <p class="text-lg font-bold text-gray-500">Belum ada voucher</p>
                                                <p class="text-sm">Voucher yang Anda generate akan muncul di sini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="vouchers.links && vouchers.links.length > 3" class="px-6 py-5 bg-white border-t border-gray-100/50">
                            <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                <template v-for="(link, p) in vouchers.links" :key="p">
                                    <div v-if="link.url === null" class="px-4 py-2 text-sm font-bold text-gray-300 border border-gray-100 rounded-xl bg-gray-50/50" v-html="link.label" />
                                    <Link v-else :href="link.url" class="px-4 py-2 text-sm font-bold border rounded-xl hover:bg-indigo-50 focus:border-indigo-500 focus:text-indigo-600 transition-all duration-300 shadow-sm" :class="{ 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700': link.active, 'border-gray-200 text-gray-600 bg-white': !link.active }" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientAreaLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    reseller: Object,
    vouchers: Object,
    profiles: Array,
    income: Number,
});

const form = useForm({
    profile_id: '',
    qty: 1,
    combination: 'alphanumeric'
});

const selectedProfile = computed(() => {
    if (!form.profile_id) return null;
    return props.profiles.find(p => p.id === form.profile_id);
});

const generateVoucher = () => {
    form.post(route('client-area.generate-vouchers'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('qty');
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Voucher berhasil digenerate',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: errors.error || 'Terjadi kesalahan saat generate voucher',
            });
        }
    });
};

const printAll = () => {
    window.print();
};
</script>

<style>
@media print {
    nav, form, button, .bg-blue-50 {
        display: none !important;
    }
    .shadow-sm, .shadow-2xl, .shadow-\[0_8px_30px_rgb\(0\,0\,0\,0\.04\)\] {
        box-shadow: none !important;
        border: none !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
