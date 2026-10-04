<template>
    <AppLayout title="Profil Voucher" subtitle="Kelola daftar profil / template voucher">
        <!-- HEADER -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah Profil
            </button>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Profil</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Durasi</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Limit Rate</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Shared Users</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="profile in profiles" :key="profile.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ profile.name }}</div>
                                <div class="text-xs text-gray-500">{{ profile.description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-emerald-600">Rp {{ formatPrice(profile.price) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-bold">
                                {{ profile.duration }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                {{ profile.limit_rate || 'Default' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ profile.shared_users }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openEditModal(profile)" class="text-indigo-600 hover:text-indigo-900 mr-4 font-semibold">Edit</button>
                                <button @click="deleteProfile(profile.id)" class="text-red-600 hover:text-red-900 font-semibold">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="profiles.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-gray-500 text-base font-medium">Belum ada data profil voucher.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL FORM -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">{{ editMode ? 'Edit Profil' : 'Tambah Profil Baru' }}</h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nama Profil <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required placeholder="Contoh: 1 Jam 2000">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Harga (Rp) <span class="text-red-500">*</span></label>
                            <input v-model="form.price" type="number" min="0" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Durasi (MikroTik Format) <span class="text-red-500">*</span></label>
                            <input v-model="form.duration" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required placeholder="Contoh: 1h, 1d, 30d">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Limit Rate</label>
                            <input v-model="form.limit_rate" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm" placeholder="Contoh: 1M/1M">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Shared Users <span class="text-red-500">*</span></label>
                            <input v-model="form.shared_users" type="number" min="1" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-cyan-50/40 p-4 rounded-xl border border-cyan-100">
                            <div v-for="fee in feeFields" :key="fee.key">
                                <label class="flex items-center gap-1.5 text-[13px] font-bold text-slate-700 mb-2 whitespace-nowrap">
                                    {{ fee.label }}
                                    <svg class="w-4 h-4 text-slate-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
                                    <span class="text-red-500 font-bold">*Req</span>
                                </label>
                                <div class="flex shadow-sm">
                                    <span class="inline-flex items-center px-3 text-sm text-gray-700 bg-gray-50 border border-r-0 border-gray-300 rounded-l-lg font-medium">Rp</span>
                                    <input v-model="form[fee.key]" type="number" min="0" placeholder="min : 0" class="w-full border-gray-300 rounded-r-lg focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <p v-if="form.errors[fee.key]" class="text-xs text-red-500 mt-1">{{ form.errors[fee.key] }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Deskripsi</label>
                            <textarea v-model="form.description" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                        
                        <button type="submit" class="hidden" id="submitBtn"></button>
                    </form>
                </div>

                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                    <button type="button" @click="$el.querySelector('#submitBtn').click()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    profiles: Array,
});

const formatPrice = (price) => {
    return parseFloat(price || 0).toLocaleString('id-ID');
};

const feeFields = [
    { key: 'fee_admin', label: '1. Fee Admin/Modal' },
    { key: 'fee_reseller', label: '2. Fee Reseller/Mitra' },
    { key: 'fee_partner', label: '3. Fee Partner/Kurir' },
];

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    price: 0,
    fee_admin: 0,
    fee_reseller: 0,
    fee_partner: 0,
    duration: '',
    limit_rate: '',
    shared_users: 1,
    description: '',
});

const openCreateModal = () => {
    editMode.value = false;
    editId.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (profile) => {
    editMode.value = true;
    editId.value = profile.id;
    form.name = profile.name;
    form.price = profile.price;
    form.fee_admin = profile.fee_admin ?? 0;
    form.fee_reseller = profile.fee_reseller ?? 0;
    form.fee_partner = profile.fee_partner ?? 0;
    form.duration = profile.duration;
    form.limit_rate = profile.limit_rate;
    form.shared_users = profile.shared_users;
    form.description = profile.description;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitForm = () => {
    if (editMode.value) {
        form.post(route('vouchers.profiles.update.post', editId.value), {
            onSuccess: () => {
                closeModal();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Profil Voucher diperbarui.' });
            }
        });
    } else {
        form.post(route('vouchers.profiles.store'), {
            onSuccess: () => {
                closeModal();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Profil Voucher ditambahkan.' });
            }
        });
    }
};

const deleteProfile = (id) => {
    Swal.fire({
        title: 'Hapus Profil?',
        text: "Anda yakin ingin menghapus profil ini? (Voucher terkait bisa bermasalah jika tidak dihapus cascade).",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.profiles.destroy.post', id), {}, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Profil berhasil dihapus.', 'success');
                }
            });
        }
    });
};
</script>
