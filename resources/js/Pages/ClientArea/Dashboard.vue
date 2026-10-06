<template>
    <Head title="Reseller Dashboard" />

    <ClientAreaLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Selamat datang, {{ reseller.customer?.name || $page.props.auth.user.name }}</h1>
                    <p class="text-gray-500 mt-1">Kelola penjualan voucher dan pantau sisa saldo Anda.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 text-white px-6 py-5 rounded-2xl flex items-center gap-4 shadow-lg shadow-emerald-500/30 flex-1 sm:flex-none transform transition-transform hover:scale-105">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-emerald-100">Total Pemasukan</p>
                            <p class="text-2xl font-black tracking-tight">Rp {{ Number(income || 0).toLocaleString('id-ID') }}</p>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white px-6 py-5 rounded-2xl flex items-center gap-4 shadow-lg shadow-blue-500/30 flex-1 sm:flex-none transform transition-transform hover:scale-105">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-100">Sisa Saldo</p>
                            <p class="text-2xl font-black tracking-tight">Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Generate Voucher Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 p-6 sm:p-8 transition-shadow hover:shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900">Generate Voucher</h2>
                        </div>

                        <form @submit.prevent="generateVoucher" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Paket Voucher</label>
                                <select v-model="form.profile_id" class="w-full border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 rounded-xl shadow-sm transition-all py-3">
                                    <option value="" disabled>-- Pilih Paket --</option>
                                    <option v-for="profile in profiles" :key="profile.id" :value="profile.id">
                                        {{ profile.name }} (Rp {{ Number(profile.price).toLocaleString('id-ID') }})
                                    </option>
                                </select>
                                <div v-if="form.errors.profile_id" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.profile_id }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Jumlah Voucher</label>
                                <input v-model="form.qty" type="number" min="1" max="100" class="w-full border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 rounded-xl shadow-sm transition-all py-3" placeholder="Contoh: 10" />
                                <div v-if="form.errors.qty" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.qty }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Kombinasi Username</label>
                                <select v-model="form.combination" class="w-full border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 rounded-xl shadow-sm transition-all py-3">
                                    <option value="alphanumeric">Huruf & Angka (Contoh: A1B2C)</option>
                                    <option value="numeric">Hanya Angka (Contoh: 12345)</option>
                                    <option value="alpha">Hanya Huruf (Contoh: ABCDE)</option>
                                </select>
                                <div v-if="form.errors.combination" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.combination }}</div>
                            </div>

                            <!-- Preview Total -->
                            <div v-if="selectedProfile" class="p-5 bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl border border-gray-200/60 space-y-3 shadow-inner">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 font-medium">Harga Satuan:</span>
                                    <span class="font-bold text-gray-800">Rp {{ Number(selectedProfile.price).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 font-medium">Jumlah:</span>
                                    <span class="font-bold text-gray-800">{{ form.qty }} x</span>
                                </div>
                                <div class="pt-3 border-t border-gray-200/80 flex justify-between font-black text-gray-900 text-lg">
                                    <span>Total Biaya:</span>
                                    <span>Rp {{ Number(selectedProfile.price * (form.qty || 0)).toLocaleString('id-ID') }}</span>
                                </div>
                                <div v-if="(selectedProfile.price * (form.qty || 0)) > reseller.balance" class="text-xs text-red-600 mt-2 flex gap-1 items-start">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Saldo tidak mencukupi
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-xl font-bold text-lg hover:from-indigo-700 hover:to-blue-700 transition-all shadow-lg shadow-indigo-500/30 disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-xl transform hover:-translate-y-0.5"
                                :disabled="form.processing || !form.profile_id || !form.qty || ((selectedProfile?.price * form.qty) > reseller.balance)"
                            >
                                {{ form.processing ? 'Memproses...' : 'Generate Sekarang' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- History / Table -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden flex flex-col h-full transition-shadow hover:shadow-2xl">
                        <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/30">
                            <h2 class="text-lg font-bold text-gray-900">Riwayat Voucher Anda</h2>
                            <button @click="printAll" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm text-indigo-600 hover:text-indigo-800 hover:border-indigo-300 hover:bg-indigo-50 font-bold flex items-center gap-2 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print Voucher
                            </button>
                        </div>
                        <div class="overflow-x-auto flex-1">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paket</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Harga</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="voucher in vouchers.data" :key="voucher.id" class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 tracking-widest">{{ voucher.code }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ voucher.profile ? voucher.profile.name : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">Rp {{ Number(voucher.profile ? voucher.profile.price : 0).toLocaleString('id-ID') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="voucher.status === 'available'" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800">Tersedia</span>
                                            <span v-else-if="voucher.status === 'used'" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Terpakai</span>
                                            <span v-else class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ voucher.status }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                            {{ new Date(voucher.created_at).toLocaleDateString('id-ID') }}
                                        </td>
                                    </tr>
                                    <tr v-if="vouchers.data.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            Belum ada voucher yang digenerate.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="vouchers.links && vouchers.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <div class="flex flex-wrap gap-1">
                                <template v-for="(link, p) in vouchers.links" :key="p">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded-lg bg-gray-50" v-html="link.label" />
                                    <Link v-else :href="link.url" class="mr-1 mb-1 px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 focus:border-blue-500 focus:text-blue-500 transition-colors" :class="{ 'bg-blue-50 border-blue-200 text-blue-600 font-medium': link.active, 'border-gray-200 text-gray-600': !link.active }" v-html="link.label" />
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
    // A simple print logic for now (can be expanded later if they need PDF layout)
    window.print();
};
</script>

<style>
@media print {
    nav, form, button, .bg-blue-50 {
        display: none !important;
    }
    .shadow-sm {
        box-shadow: none !important;
        border: none !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
