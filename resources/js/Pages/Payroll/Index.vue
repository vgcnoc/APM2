<template>
    <AppLayout title="Gaji, Insentif & Potongan" subtitle="Manajemen Gaji, Bonus, Potongan, dan Pencairan">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ $page.props.flash.error }}
            </div>

            <!-- Filter Bulan & Pengaturan -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-4 rounded-3xl shadow-sm border border-gray-100">
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Periode Gaji</h2>
                            <p class="text-xs text-gray-500">Pilih bulan penggajian</p>
                        </div>
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <select v-model="form.month" class="block w-full sm:w-32 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-sm font-medium focus:ring-indigo-500 focus:border-indigo-500">
                            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                        </select>
                        <select v-model="form.year" class="block w-full sm:w-28 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-sm font-medium focus:ring-indigo-500 focus:border-indigo-500">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                        <button @click="applyFilter" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition-all hover:shadow">
                            Tampilkan
                        </button>
                    </div>
                </div>

                <!-- Pengaturan Tanggal Cair -->
                <div class="flex items-center gap-3 w-full lg:w-auto border-t lg:border-t-0 lg:border-l border-gray-100 pt-4 lg:pt-0 lg:pl-4">
                    <form @submit.prevent="updateSettings" class="flex items-center gap-2 w-full">
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Cair Otomatis</label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-600">Tgl:</span>
                                <input type="number" v-model="settingForm.disbursement_date" min="1" max="31" class="w-16 border-gray-200 rounded-lg text-sm text-center focus:ring-indigo-500 focus:border-indigo-500 p-1.5" required>
                                <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm font-bold px-2 py-1 bg-indigo-50 rounded-lg">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daftar Karyawan & Gaji -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="user in users" :key="user.id" class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden flex flex-col relative">
                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4 z-10">
                        <span v-if="user.is_paid" class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-xs font-black shadow-sm ring-1 ring-emerald-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            SUDAH CAIR
                        </span>
                        <span v-else class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-black shadow-sm ring-1 ring-amber-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            MENUNGGU
                        </span>
                    </div>

                    <div class="p-6 bg-gradient-to-b from-gray-50/50 to-white flex-1">
                        <div class="flex items-center gap-4 mb-5">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-md">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 leading-tight">{{ user.name }}</h3>
                                <p class="text-xs font-medium text-indigo-600 capitalize bg-indigo-50 px-2 py-0.5 rounded-md inline-block mt-1">{{ user.role }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                <span class="text-gray-500">Gaji Pokok:</span>
                                <span class="font-bold text-gray-900">Rp {{ formatRupiah(user.base_salary) }}</span>
                            </div>
                            
                            <!-- Rincian Insentif -->
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Insentif Auto (Sistem):</span>
                                <span class="font-bold text-emerald-600">+ Rp {{ formatRupiah(user.total_auto_incentive) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm pb-2 border-b border-gray-100">
                                <span class="text-gray-500">Insentif Manual (Bonus):</span>
                                <span class="font-bold text-emerald-600">+ Rp {{ formatRupiah(user.total_manual_incentive) }}</span>
                            </div>

                            <div v-for="inc in user.incentives_list.filter(i => i.type === 'manual')" :key="'inc-'+inc.id" class="flex justify-between items-center text-xs text-gray-500 pl-4 mb-1">
                                <span>↳ {{ inc.description }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600">+ {{ formatRupiah(inc.amount) }}</span>
                                    <button v-if="inc.status === 'pending'" @click="destroyIncentive(inc.id)" class="text-rose-500 hover:text-rose-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <div v-if="!user.is_paid" class="pt-1 pb-2 flex gap-2">
                                <button @click="openManualModal(user, 'incentive')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center justify-center gap-1 bg-indigo-50 hover:bg-indigo-100 px-2 py-1.5 rounded-lg transition-colors w-full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    Bonus
                                </button>
                                <button @click="openManualModal(user, 'deduction')" class="text-xs font-bold text-rose-600 hover:text-rose-800 flex items-center justify-center gap-1 bg-rose-50 hover:bg-rose-100 px-2 py-1.5 rounded-lg transition-colors w-full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                    Potongan
                                </button>
                            </div>

                            <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                <span class="text-gray-500">Total Potongan:</span>
                                <span class="font-bold text-rose-600">- Rp {{ formatRupiah(user.total_deduction) }}</span>
                            </div>
                            <div v-for="ded in user.deductions_list" :key="'ded-'+ded.id" class="flex justify-between items-center text-xs text-gray-500 pl-4 mb-1">
                                <span>↳ {{ ded.description }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-rose-600">- {{ formatRupiah(ded.amount) }}</span>
                                    <button v-if="ded.status === 'pending'" @click="destroyDeduction(ded.id)" class="text-rose-500 hover:text-rose-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-3 flex justify-between items-center mt-2 border border-gray-100">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Diterima</span>
                                <span class="text-lg font-black text-indigo-700">Rp {{ formatRupiah(user.net_salary) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border-t border-gray-100 bg-gray-50 flex gap-2">
                        <button v-if="!user.is_paid" @click="disburse(user)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all flex justify-center items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Cairkan Gaji Sekarang
                        </button>
                        <div v-else class="w-full text-center py-2.5 rounded-xl text-xs font-bold text-gray-500 bg-gray-200/50">
                            Telah dibayarkan pada {{ new Date(user.payment_date).toLocaleDateString('id-ID') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Tambah Insentif Manual -->
            <div v-if="showManualModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeManualModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-100">
                        <div class="bg-gray-50/50 px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center gap-2">
                                <span :class="modalType === 'incentive' ? 'bg-indigo-100 text-indigo-600' : 'bg-rose-100 text-rose-600'" class="p-1.5 rounded-lg">
                                    <svg v-if="modalType === 'incentive'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                </span>
                                {{ modalType === 'incentive' ? 'Tambah Bonus / Insentif' : 'Tambah Potongan' }}
                            </h3>
                            <button @click="closeManualModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="px-6 py-5">
                            <form @submit.prevent="submitManual" class="space-y-4">
                                <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100 mb-4">
                                    <p class="text-sm font-medium text-indigo-900">Karyawan: <strong>{{ selectedUser?.name }}</strong></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tanggal {{ modalType === 'incentive' ? 'Insentif' : 'Potongan' }}</label>
                                    <input type="date" v-model="manualForm.date" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                                    <input type="number" v-model="manualForm.amount" min="1" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold text-lg" placeholder="Contoh: 50000" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kategori / Keterangan</label>
                                    <input list="category-list" v-model="manualForm.description" @input="handleDescriptionChange" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Pilih atau ketik keterangan..." required>
                                    <datalist id="category-list">
                                        <template v-if="modalType === 'incentive'">
                                            <option v-for="cat in (payrollCategories || []).filter(c => c.type === 'incentive' && c.mode === 'manual')" :key="cat.id" :value="cat.name"></option>
                                        </template>
                                        <template v-else>
                                            <option v-for="cat in (payrollCategories || []).filter(c => c.type === 'deduction' && c.mode === 'manual')" :key="cat.id" :value="cat.name"></option>
                                        </template>
                                    </datalist>
                                </div>
                                
                                <div class="pt-4 flex justify-end gap-3">
                                    <button type="button" @click="closeManualModal" class="bg-white py-2.5 px-5 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">
                                        Batal
                                    </button>
                                    <button type="submit" :disabled="manualForm.processing" :class="modalType === 'incentive' ? 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500' : 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500'" class="inline-flex justify-center py-2.5 px-5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all disabled:opacity-50">
                                        {{ manualForm.processing ? 'Menyimpan...' : 'Simpan ' + (modalType === 'incentive' ? 'Bonus' : 'Potongan') }}
                                    </button>
                                </div>
                            </form>
                        </div>
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
    month: Number,
    year: Number,
    users: Array,
    settings: Object,
    payrollCategories: Array
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const currentYear = new Date().getFullYear();
const years = Array.from({length: 5}, (_, i) => currentYear - i);

const form = useForm({
    month: props.month,
    year: props.year
});

const settingForm = useForm({
    disbursement_date: props.settings.disbursement_date
});

const applyFilter = () => {
    form.get(route('payroll.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const updateSettings = () => {
    settingForm.post(route('payroll.settings.update'), {
        preserveScroll: true
    });
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

// Manual Modal
const showManualModal = ref(false);
const modalType = ref('incentive'); // 'incentive' or 'deduction'
const selectedUser = ref(null);
const manualForm = useForm({
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const openManualModal = (user, type = 'incentive') => {
    selectedUser.value = user;
    modalType.value = type;
    manualForm.reset();
    showManualModal.value = true;
};

const closeManualModal = () => {
    showManualModal.value = false;
    selectedUser.value = null;
};

const handleDescriptionChange = () => {
    const category = (props.payrollCategories || []).find(c => c.name === manualForm.description && c.type === modalType.value);
    if (category && category.default_amount > 0) {
        manualForm.amount = category.default_amount;
    }
};

const submitManual = () => {
    const r = modalType.value === 'incentive' 
        ? route('payroll.incentive.store', selectedUser.value.id)
        : route('payroll.deduction.store', selectedUser.value.id);

    // Map `date` to either `incentive_date` or `deduction_date`
    const payload = {
        amount: manualForm.amount,
        description: manualForm.description,
        [modalType.value === 'incentive' ? 'incentive_date' : 'deduction_date']: manualForm.date
    };

    router.post(r, payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeManualModal();
        }
    });
};

const destroyIncentive = (id) => {
    if (confirm('Hapus insentif manual ini?')) {
        router.delete(route('payroll.incentive.destroy', id), { preserveScroll: true });
    }
};

const destroyDeduction = (id) => {
    if (confirm('Hapus potongan manual ini?')) {
        router.delete(route('payroll.deduction.destroy', id), { preserveScroll: true });
    }
};

const disburse = (user) => {
    if (confirm(`Cairkan gaji dan insentif untuk ${user.name} bulan ini?\n\nPERINGATAN: Aksi ini akan mencatat pengeluaran otomatis di Buku Kas dan tidak dapat dibatalkan.`)) {
        router.post(route('payroll.disburse', user.id), {
            month: props.month,
            year: props.year
        }, {
            preserveScroll: true
        });
    }
};
</script>
