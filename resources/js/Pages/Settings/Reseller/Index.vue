<template>
    <AppLayout title="Pengaturan Reseller" subtitle="Kelola hak akses profil voucher untuk reseller">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="relative max-w-md w-full">
                <input 
                    v-model="search" 
                    @input="onSearch"
                    type="text" 
                    placeholder="Cari nama atau email..." 
                    class="w-full pl-10 pr-4 py-2 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Akses Profil Voucher</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                                <div class="text-xs text-gray-500">{{ user.email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 uppercase">{{ user.role }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="profile in user.voucher_profiles" :key="profile.id" class="px-2 py-1 text-[11px] font-semibold bg-emerald-100 text-emerald-800 rounded">
                                        {{ profile.name }}
                                    </span>
                                    <span v-if="!user.voucher_profiles || user.voucher_profiles.length === 0" class="text-xs text-gray-400 italic">Belum ada akses profil</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openSettingModal(user)" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    Atur Akses
                                </button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 font-medium">Tidak ada data reseller/user.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t bg-gray-50">
                <Pagination :links="users.links" />
            </div>
        </div>

        <!-- Modal Atur Akses -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">Atur Akses Profil Voucher</h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="px-6 py-3 bg-indigo-50 border-b border-indigo-100">
                    <p class="text-sm font-bold text-indigo-900">{{ selectedUser?.name }}</p>
                    <p class="text-xs text-indigo-700">{{ selectedUser?.email }}</p>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Pilih Profil Voucher yang diizinkan:</p>
                    <div class="space-y-2">
                        <label v-for="profile in voucherProfiles" :key="profile.id" class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="form.voucher_profile_ids.includes(profile.id) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <div class="flex items-center h-5">
                                <input type="checkbox" :value="profile.id" v-model="form.voucher_profile_ids" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">{{ profile.name }}</span>
                                <span class="block text-xs text-gray-500">Harga: Rp {{ Number(profile.price).toLocaleString('id-ID') }} | Durasi: {{ profile.duration }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                    <button @click="saveSettings" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';

const props = defineProps({
    users: Object,
    voucherProfiles: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const selectedUser = ref(null);

const form = useForm({
    voucher_profile_ids: []
});

const onSearch = debounce(() => {
    router.get(route('settings.resellers.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

const openSettingModal = (user) => {
    selectedUser.value = user;
    form.voucher_profile_ids = user.voucher_profiles ? user.voucher_profiles.map(p => p.id) : [];
    showModal.value = true;
};

const saveSettings = () => {
    if (!selectedUser.value) return;
    
    form.post(route('settings.resellers.update.post', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'Akses profil voucher berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};
</script>
