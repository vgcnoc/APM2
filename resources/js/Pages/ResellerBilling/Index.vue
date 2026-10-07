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
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Tagihan Reseller</h3>
                    
                    <div class="flex bg-gray-200/70 p-1 rounded-xl">
                        <Link 
                            :href="route('reseller-billing.index', { tab: 'unpaid' })" 
                            class="px-4 py-1.5 text-sm font-bold rounded-lg transition-all"
                            :class="activeTab === 'unpaid' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            Belum Lunas
                        </Link>
                        <Link 
                            :href="route('reseller-billing.index', { tab: 'paid' })" 
                            class="px-4 py-1.5 text-sm font-bold rounded-lg transition-all"
                            :class="activeTab === 'paid' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            Sudah Lunas
                        </Link>
                    </div>
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
                                    <span v-else-if="inv.status === 'paid'" class="bg-green-50 text-green-700 px-2 py-0.5 rounded text-xs font-bold border border-green-200">Lunas</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ inv.customer?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ inv.customer?.phone || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-500">Total: Rp {{ formatRupiah(inv.amount) }}</div>
                                    <div class="text-sm font-black text-indigo-600 mt-0.5">Sisa: Rp {{ formatRupiah(inv.remaining) }}</div>
                                    <div v-if="inv.pending_amount > 0" class="mt-1 flex items-center gap-1.5 bg-yellow-50 text-yellow-700 px-2 py-1 rounded-md text-[10px] font-bold border border-yellow-200 w-max">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Pending Validasi: Rp {{ formatRupiah(inv.pending_amount) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(inv.due_date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <template v-if="inv.status !== 'paid'">
                                        <button v-if="inv.effective_remaining > 0" @click="openModal(inv)" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2 rounded-xl shadow-sm transition-all hover:shadow text-xs font-bold ring-1 ring-indigo-700/50">
                                            💰 Terima Uang
                                        </button>
                                        <span v-else class="text-xs font-bold text-gray-400 italic">Menunggu Validasi Admin</span>
                                    </template>
                                    <span v-else class="text-green-600 font-bold text-xs bg-green-50 px-3 py-1.5 rounded-lg border border-green-200">
                                        Selesai
                                    </span>
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
                        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-2">Penerimaan Uang - {{ selectedInvoice.invoice_number }}</h3>
                        <div class="bg-indigo-50 border border-indigo-100 p-3 rounded-xl mb-4">
                            <p class="text-[11px] text-indigo-600 uppercase font-bold tracking-wider mb-1">Sisa Tagihan Bisa Diterima</p>
                            <p class="font-black text-2xl text-indigo-700">Rp {{ formatRupiah(selectedInvoice.effective_remaining) }}</p>
                            <p v-if="selectedInvoice.pending_amount > 0" class="text-xs text-yellow-600 mt-2 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Ada pembayaran Rp {{ formatRupiah(selectedInvoice.pending_amount) }} sedang menunggu validasi admin.
                            </p>
                        </div>
                        
                        <!-- History Area -->
                        <div v-if="selectedInvoice.payments && selectedInvoice.payments.length > 0" class="mb-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-xs font-bold text-gray-700 mb-2 uppercase tracking-wide">Riwayat Cicilan/Pembayaran</p>
                            <ul class="space-y-2">
                                <li v-for="pay in selectedInvoice.payments" :key="pay.id" class="flex justify-between items-center text-sm border-b border-gray-200 pb-1 last:border-0 last:pb-0">
                                    <div>
                                        <span class="font-semibold text-gray-800">Rp {{ formatRupiah(pay.amount) }}</span>
                                        <span class="text-xs text-gray-500 ml-2">({{ new Date(pay.payment_date).toLocaleDateString('id-ID') }})</span>
                                    </div>
                                    <span v-if="pay.status === 'verified'" class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded">Lunas</span>
                                    <span v-else-if="pay.status === 'pending'" class="text-xs font-bold text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded">Menunggu Val.</span>
                                </li>
                            </ul>
                        </div>
                        
                        <form @submit.prevent="submitCollection" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Penerimaan</label>
                                <input type="date" v-model="form.payment_date" class="mt-1 block w-full border-gray-300 rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-bold text-gray-600 mb-1">Nominal Uang (Rp)</label>
                                <input type="number" v-model="form.amount" class="block w-full border-gray-200 bg-gray-50 focus:bg-white rounded-xl font-bold text-lg" required :max="selectedInvoice.effective_remaining">
                                <div class="flex justify-between items-center mt-2">
                                    <p class="text-xs text-gray-500">Bisa diisi lunas atau sebagian.</p>
                                    <button type="button" @click="form.amount = selectedInvoice.effective_remaining" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2 py-1 rounded-md">Isi Max Lunas</button>
                                </div>
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
                            <div class="flex items-start bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                                <div class="flex items-center h-5">
                                    <input id="direct" v-model="form.is_direct_payment" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="direct" class="font-medium text-indigo-900">Pembayaran Langsung di Kantor</label>
                                    <p class="text-indigo-700 text-xs mt-0.5">Mencentang ini akan langsung melunaskan tagihan (bypass verifikasi admin). Gunakan jika uang sudah ada di kasir.</p>
                                </div>
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
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    invoices: Object,
    activeTab: String
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
    notes: '',
    is_direct_payment: false
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
