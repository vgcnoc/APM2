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
                        <h3 class="text-lg font-bold text-gray-900">Riwayat Pengisian & Permintaan Saldo</h3>
                        <p class="text-sm text-gray-500">Isi saldo reseller secara manual. Jika "Kasbon", otomatis akan dibuatkan Invoice Penagihan besok.</p>
                    </div>
                    <button @click="showAddModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Isi Saldo Manual
                    </button>
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

        <!-- Add Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" id="modal-title">Isi Saldo Reseller</h3>
                                <form @submit.prevent="submitTopup" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Pilih Reseller <span class="text-red-500">*</span></label>
                                        <select v-model="form.reseller_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl" required>
                                            <option value="">Pilih Reseller...</option>
                                            <option v-for="res in resellers" :key="res.id" :value="res.id">
                                                {{ res.customer?.name }} - Saldo: Rp {{ formatRupiah(res.balance) }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nominal Saldo (Rp) <span class="text-red-500">*</span></label>
                                        <input type="number" v-model="form.amount" class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required min="1000">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Metode <span class="text-red-500">*</span></label>
                                        <select v-model="form.payment_method" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl" required>
                                            <option value="kasbon">Kasbon (Otomatis buat tagihan besok)</option>
                                            <option value="transfer">Transfer Bank (Sudah Bayar)</option>
                                            <option value="cash">Tunai (Sudah Bayar)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Catatan Tambahan</label>
                                        <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Permintaan via WA, dll"></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="submitTopup" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Simpan & Isi Saldo
                        </button>
                        <button type="button" @click="showAddModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Object,
    resellers: Array
});

const showAddModal = ref(false);

const form = useForm({
    reseller_id: '',
    amount: '',
    payment_method: 'kasbon',
    notes: 'Permintaan manual via WhatsApp'
});

const submitTopup = () => {
    form.post(route('reseller-requests.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            form.reset('amount', 'notes');
            form.notes = 'Permintaan manual via WhatsApp';
        }
    });
};

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
