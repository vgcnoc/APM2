<template>
    <AppLayout title="Penagihan Reseller" subtitle="Menu untuk Penagih menerima uang kasbon">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ✅ {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
                ❌ {{ $page.props.flash.error }}
            </div>

            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Tagihan Reseller (Belum Lunas)</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Tagihan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reseller</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total / Sisa</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jatuh Tempo</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ inv.invoice_number }}</div>
                                    <span v-if="inv.status === 'unpaid'" class="bg-red-50 text-red-700 px-2 py-0.5 rounded text-xs font-bold border border-red-200">Belum Lunas</span>
                                    <span v-else-if="inv.status === 'partial'" class="bg-yellow-50 text-yellow-700 px-2 py-0.5 rounded text-xs font-bold border border-yellow-200">Sebagian</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ inv.customer?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ inv.customer?.phone || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-500">Total: Rp {{ formatRupiah(inv.amount) }}</div>
                                    <div class="text-sm font-black text-indigo-600">Sisa: Rp {{ formatRupiah(inv.remaining) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(inv.due_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openModal(inv)" class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg shadow-sm transition-colors text-xs font-bold">
                                        💰 Terima Uang
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-medium">Tidak ada tagihan aktif untuk reseller</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Collect Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">Penerimaan Uang - {{ selectedInvoice.invoice_number }}</h3>
                        <p class="text-sm text-gray-500 mb-4">Sisa Tagihan: <span class="font-bold text-indigo-600">Rp {{ formatRupiah(selectedInvoice.remaining) }}</span></p>
                        
                        <form @submit.prevent="submitCollection" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Penerimaan</label>
                                <input type="date" v-model="form.payment_date" class="mt-1 block w-full border-gray-300 rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nominal Uang (Rp)</label>
                                <input type="number" v-model="form.amount" class="mt-1 block w-full border-gray-300 rounded-xl" required :max="selectedInvoice.remaining">
                                <p class="text-xs text-gray-500 mt-1">Bisa diisi lunas ({{ formatRupiah(selectedInvoice.remaining) }}) atau sebagian.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Metode Bayar</label>
                                <select v-model="form.payment_method" class="mt-1 block w-full border-gray-300 rounded-xl" required>
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="transfer">Transfer Bank</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Catatan</label>
                                <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-xl"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="submitCollection" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Catat Penerimaan
                        </button>
                        <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
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
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    invoices: Object
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

const showModal = ref(false);
const selectedInvoice = ref(null);

const form = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    notes: ''
});

const openModal = (inv) => {
    selectedInvoice.value = inv;
    form.amount = inv.remaining;
    form.notes = 'Terima dana tagihan reseller';
    showModal.value = true;
};

const submitCollection = () => {
    form.post(route('reseller-billing.collect', selectedInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        }
    });
};
</script>
