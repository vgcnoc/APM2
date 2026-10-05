<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

// Custom debounce
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    resellers: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(
        route('resellers.index'),
        { search: value },
        { preserveState: true, replace: true }
    );
}, 300));

const showModal = ref(false);
const editingReseller = ref(null);

const form = useForm({
    balance: 0,
    is_active: true
});

const showCreateModal = ref(false);
const accountReseller = ref(null);
const createForm = useForm({
    email: '',
    password: ''
});

const openEditModal = (reseller) => {
    editingReseller.value = reseller;
    form.balance = reseller.balance || 0;
    form.is_active = reseller.is_active;
    showModal.value = true;
};

const openCreateAccountModal = (reseller) => {
    accountReseller.value = reseller;
    createForm.email = reseller.customer.email || '';
    createForm.password = '';
    showCreateModal.value = true;
};

const createAccount = () => {
    if (!accountReseller.value) return;
    
    createForm.post(route('resellers.create-account', accountReseller.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            Swal.fire(
                'Berhasil!',
                'Akun berhasil dibuat.',
                'success'
            );
        },
        onError: (errors) => {
            Swal.fire(
                'Gagal!',
                errors.error || 'Terjadi kesalahan saat membuat akun. Pastikan form diisi dengan benar.',
                'error'
            );
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const saveReseller = () => {
    if (editingReseller.value) {
        form.put(route('resellers.update', editingReseller.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire('Berhasil!', 'Data dompet Reseller berhasil diperbarui.', 'success');
            },
        });
    }
};

const deleteReseller = (reseller) => {
    Swal.fire({
        title: 'Cabut Akses Reseller?',
        text: `Pelanggan ini tidak akan lagi memiliki dompet reseller. Data master pelanggan tidak dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Cabut!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('resellers.destroy', reseller.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil!', 'Akses Reseller berhasil dicabut.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Data Reseller Voucher" />

    <AppLayout>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Data Dompet Reseller</h2>
                    <p class="mt-1 text-sm text-gray-600">Kelola dompet dan saldo pelanggan yang menjadi mitra penjualan voucher.</p>
                </div>
                <Link href="/customers/booking" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Reseller dari Booking
                </Link>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                <div class="relative w-full md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors" placeholder="Cari reseller..." />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data Pelanggan</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Alamat & Area</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo Dompet</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status Akses</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="reseller in resellers.data" :key="reseller.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="reseller.customer">
                                        <Link :href="`/customers/${reseller.customer.id}`" class="text-sm font-medium text-blue-600 hover:underline">
                                            {{ reseller.customer.name }}
                                        </Link>
                                        <div class="text-xs text-gray-500 mt-1">{{ reseller.customer.phone || '-' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ reseller.customer.customer_code }}</div>
                                    </div>
                                    <div v-else class="text-sm text-red-500 font-bold">Error: No Customer</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    <span v-if="reseller.customer">
                                        {{ reseller.customer.address || '-' }}
                                        <div v-if="reseller.customer.area_model" class="text-xs text-indigo-600 mt-1">{{ reseller.customer.area_model.name }}</div>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="reseller.is_active" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                    <span v-else class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button v-if="reseller.customer && !reseller.customer.user" @click="openCreateAccountModal(reseller)" class="text-emerald-600 hover:text-emerald-900 p-2 rounded-lg hover:bg-emerald-50 transition-colors" title="Buat Akun Login">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    </button>
                                    <button @click="openEditModal(reseller)" class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors ml-1" title="Edit Dompet">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    <button @click="deleteReseller(reseller)" class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors ml-1" title="Cabut Akses">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="resellers.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-gray-100 rounded-full p-3 mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900">Belum ada reseller</h3>
                                        <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan pelanggan sebagai reseller dari menu Booking.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="resellers.links && resellers.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, p) in resellers.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded-lg bg-gray-50" v-html="link.label" />
                            <a v-else :href="link.url" class="mr-1 mb-1 px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 focus:border-blue-500 focus:text-blue-500 transition-colors" :class="{ 'bg-blue-50 border-blue-200 text-blue-600 font-medium': link.active, 'border-gray-200 text-gray-600': !link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Native Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Atur Dompet Reseller</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <div v-if="editingReseller && editingReseller.customer" class="mb-4 pb-4 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Nama Pelanggan</p>
                        <p class="text-sm text-gray-900 font-medium">{{ editingReseller.customer.name }}</p>
                    </div>

                    <form @submit.prevent="saveReseller" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Dompet (Rp)</label>
                            <input v-model="form.balance" type="number" min="0" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="0" />
                            <div v-if="form.errors.balance" class="mt-1 text-sm text-red-600">{{ form.errors.balance }}</div>
                            <p class="text-xs text-gray-500 mt-1">Gunakan untuk top-up manual atau koreksi saldo.</p>
                        </div>

                        <div class="flex items-center mt-4">
                            <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
                            <label for="is_active" class="ml-2 block text-sm text-gray-700">Akses Reseller Aktif</label>
                        </div>
                        
                        <button type="submit" class="hidden" id="submitBtn"></button>
                    </form>
                </div>
                
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                    <button type="button" @click="$el.querySelector('#submitBtn').click()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 disabled:opacity-50" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Create Account Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Buat Akun Login Reseller</h3>
                    <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <div v-if="accountReseller && accountReseller.customer" class="mb-4 pb-4 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Nama Pelanggan</p>
                        <p class="text-sm text-gray-900 font-medium">{{ accountReseller.customer.name }}</p>
                    </div>

                    <form @submit.prevent="createAccount" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username (Email) <span class="text-red-500">*</span></label>
                            <input v-model="createForm.email" type="email" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="email@contoh.com" required />
                            <div v-if="createForm.errors.email" class="mt-1 text-sm text-red-600">{{ createForm.errors.email }}</div>
                            <p class="text-xs text-gray-500 mt-1">Digunakan untuk login reseller.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                            <input v-model="createForm.password" type="password" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="Minimal 8 karakter" required minlength="8" />
                            <div v-if="createForm.errors.password" class="mt-1 text-sm text-red-600">{{ createForm.errors.password }}</div>
                        </div>

                        <button type="submit" class="hidden" id="submitCreateBtn"></button>
                    </form>
                </div>
                
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button type="button" @click="closeCreateModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                    <button type="button" @click="$el.querySelector('#submitCreateBtn').click()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 disabled:opacity-50" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Menyimpan...' : 'Buat Akun' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

