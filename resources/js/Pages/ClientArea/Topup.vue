<template>
    <ClientAreaLayout title="Isi Saldo (Top Up)">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Success/Error Messages -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ✅ {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ❌ {{ $page.props.errors.error }}
            </div>

            <!-- Current Balance Info -->
            <div class="bg-gradient-to-br from-indigo-600 to-blue-500 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 -mr-8 -mt-8 w-48 h-48 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <p class="text-indigo-100 font-medium mb-1">Saldo Anda Saat Ini</p>
                        <h2 class="text-4xl font-black tracking-tight">Rp {{ formatRupiah(reseller.balance) }}</h2>
                    </div>
                    <div class="bg-white/20 backdrop-blur-md border border-white/20 p-4 rounded-2xl">
                        <p class="text-sm font-semibold mb-1">Butuh lebih banyak saldo?</p>
                        <p class="text-xs text-indigo-100">Ajukan Kasbon sekarang, bayar besok lewat tagihan!</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Request Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="text-lg font-bold text-gray-900">Form Pengajuan Saldo</h3>
                        </div>
                        <div class="p-6">
                            <form @submit.prevent="submitRequest" class="space-y-5">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Nominal Saldo (Rp) <span class="text-red-500">*</span></label>
                                    <input type="number" v-model="form.amount" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all" placeholder="Contoh: 100000" required min="10000" />
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Metode Pembayaran <span class="text-red-500">*</span></label>
                                    <select v-model="form.payment_method" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all" required>
                                        <option value="kasbon">Kasbon (Bayar via Penagihan Besok)</option>
                                        <option value="transfer">Transfer Bank (Bayar Sekarang)</option>
                                        <option value="cash">Tunai (Bayar Langsung ke Kantor)</option>
                                    </select>
                                    <p v-if="form.payment_method === 'kasbon'" class="text-xs text-indigo-600 mt-2 bg-indigo-50 p-2 rounded-lg font-medium">
                                        💡 Saldo akan ditambahkan setelah disetujui admin. Pembayaran akan ditagihkan besok.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Catatan Tambahan (Opsional)</label>
                                    <textarea v-model="form.notes" rows="2" class="block w-full rounded-2xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all resize-none" placeholder="Tulis pesan untuk admin..."></textarea>
                                </div>

                                <button type="submit" :disabled="form.processing" class="w-full mt-4 flex items-center justify-center gap-2 bg-indigo-600 text-white py-3 px-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-500/50 transition-all disabled:opacity-50">
                                    <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Ajukan Permintaan</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- History Table -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden h-full flex flex-col">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-900">Riwayat Permintaan Saldo</h3>
                        </div>
                        <div class="flex-1 overflow-x-auto p-0">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Metode</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="req in requests.data" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ new Date(req.created_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                            Rp {{ formatRupiah(req.amount) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="capitalize bg-gray-100 px-2 py-1 rounded-md text-xs font-semibold">{{ req.payment_method }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span v-if="req.status === 'pending'" class="bg-yellow-50 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold border border-yellow-200">Menunggu</span>
                                            <span v-else-if="req.status === 'approved'" class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-bold border border-blue-200">Disetujui</span>
                                            <span v-else-if="req.status === 'paid'" class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">Lunas</span>
                                            <span v-else-if="req.status === 'rejected'" class="bg-red-50 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">Ditolak</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                            {{ req.notes || '-' }}
                                        </td>
                                    </tr>
                                    <tr v-if="!requests.data.length">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <p class="font-medium text-gray-400">Belum ada riwayat permintaan saldo</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </ClientAreaLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';

const props = defineProps({
    reseller: Object,
    requests: Object,
});

const form = useForm({
    amount: '',
    payment_method: 'kasbon',
    notes: '',
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

const submitRequest = () => {
    form.post(route('client-area.topup-request'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('amount', 'notes');
        },
    });
};
</script>
