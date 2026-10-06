<template>
    <AppLayout title="Permintaan Saldo (Kasbon)" subtitle="Kelola permintaan isi saldo dan kasbon dari reseller">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ✅ {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ❌ {{ $page.props.flash.error }}
            </div>

            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Daftar Permintaan Saldo</h3>
                        <p class="text-sm text-gray-500">Jika "Kasbon", otomatis akan dibuatkan Invoice Penagihan besok setelah disetujui.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reseller</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Metode / Catatan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="req in requests.data" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(req.created_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ req.reseller?.customer?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ req.reseller?.customer?.phone || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-indigo-600">
                                    Rp {{ formatRupiah(req.amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 capitalize mb-1">
                                        {{ req.payment_method }}
                                    </span>
                                    <div class="text-xs text-gray-500 italic break-words line-clamp-2 max-w-xs">{{ req.notes || 'Tidak ada catatan' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="req.status === 'pending'" class="bg-yellow-50 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold border border-yellow-200">Menunggu</span>
                                    <span v-else-if="req.status === 'approved'" class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-bold border border-blue-200">Disetujui</span>
                                    <span v-else-if="req.status === 'paid'" class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">Lunas</span>
                                    <span v-else-if="req.status === 'rejected'" class="bg-red-50 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">Ditolak</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div v-if="req.status === 'pending'" class="flex justify-end gap-2">
                                        <button @click="approve(req.id)" class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg shadow-sm transition-colors text-xs font-bold">
                                            ✓ Setujui
                                        </button>
                                        <button @click="reject(req.id)" class="inline-flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg shadow-sm transition-colors text-xs font-bold">
                                            ✕ Tolak
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 font-medium">Selesai</span>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-medium">Tidak ada data permintaan saldo</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Placeholder -->
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Object
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

const approve = (id) => {
    if (confirm('Setujui permintaan ini? Saldo reseller akan bertambah, dan jika kasbon akan otomatis dibuatkan invoice penagihan besok.')) {
        router.post(route('reseller-requests.approve', id), {}, { preserveScroll: true });
    }
};

const reject = (id) => {
    if (confirm('Tolak permintaan ini?')) {
        router.post(route('reseller-requests.reject', id), {}, { preserveScroll: true });
    }
};
</script>
