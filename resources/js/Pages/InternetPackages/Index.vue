<template>
    <AppLayout title="Paket Internet" subtitle="Kelola daftar paket internet dan harga">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="w-full sm:w-96 relative">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Cari paket internet..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    @keyup.enter="doSearch"
                >
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <button @click="openCreateModal" class="btn-primary flex items-center gap-2 whitespace-nowrap">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah Paket
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Paket</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kecepatan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="pkg in packages.data" :key="pkg.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ pkg.name }}</div>
                                <div class="text-xs text-gray-500 mt-1" v-if="pkg.description">{{ pkg.description }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ pkg.speed_mbps }} Mbps
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-emerald-600">Rp {{ formatPrice(pkg.price) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="pkg.is_active" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-50 text-gray-700 border border-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                    Nonaktif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openEditModal(pkg)" class="text-blue-600 hover:text-blue-900 mr-4 font-semibold">Edit</button>
                                <button @click="deletePackage(pkg.id)" class="text-red-600 hover:text-red-900 font-semibold">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="packages.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-500 text-base font-medium">Belum ada data paket internet.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2" v-if="packages.links && packages.links.length > 3">
                <template v-for="(link, idx) in packages.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded" :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"></Link>
                    <span v-else v-html="link.label" class="px-3 py-1 border rounded bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed"></span>
                </template>
            </div>
        </div>

        <!-- Modal Form -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto my-auto p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6 border-b pb-4">{{ editMode ? 'Edit Paket Internet' : 'Tambah Paket Internet' }}</h3>
                
                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Paket</label>
                            <input v-model="form.name" type="text" class="input-text w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="Contoh: Paket Family 20M">
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Kecepatan (Mbps)</label>
                                <input v-model="form.speed_mbps" type="number" class="input-text w-full border border-gray-300 rounded-lg px-3 py-2" required min="1" placeholder="Contoh: 20">
                                <div v-if="form.errors.speed_mbps" class="text-red-500 text-xs mt-1">{{ form.errors.speed_mbps }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Harga (Rp)</label>
                                <input v-model="form.price" type="number" class="input-text w-full border border-gray-300 rounded-lg px-3 py-2" required min="0" placeholder="Contoh: 150000">
                                <div v-if="form.errors.price" class="text-red-500 text-xs mt-1">{{ form.errors.price }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi (Opsional)</label>
                            <textarea v-model="form.description" class="input-text h-24 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Keterangan atau fasilitas paket..."></textarea>
                            <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                        </div>

                        <div class="flex items-center pt-2">
                            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="is_active" class="ml-2 block text-sm font-semibold text-gray-700">Paket Aktif (Bisa dipilih pelanggan)</label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100">
                        <button type="button" @click="closeModal" class="btn-secondary px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :disabled="form.processing">
                            {{ editMode ? 'Simpan Perubahan' : 'Tambah Paket' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    packages: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const doSearch = () => {
    router.get(route('internet-packages.index'), { search: search.value }, { preserveState: true, replace: true });
};

const formatPrice = (price) => {
    return parseFloat(price).toLocaleString('id-ID');
};

// Modal Logic
const showModal = ref(false);
const editMode = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    speed_mbps: '',
    price: '',
    description: '',
    is_active: true
});

const openCreateModal = () => {
    editMode.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (pkg) => {
    editMode.value = true;
    editingId.value = pkg.id;
    form.name = pkg.name;
    form.speed_mbps = pkg.speed_mbps;
    form.price = pkg.price;
    form.description = pkg.description;
    form.is_active = pkg.is_active;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => form.reset(), 200);
};

const submitForm = () => {
    if (editMode.value) {
        form.post(route('internet-packages.update.post', editingId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('internet-packages.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const deletePackage = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus paket ini?')) {
        router.post(route('internet-packages.destroy.post', id));
    }
};
</script>

<style scoped>
.input-text {
    @apply w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition-colors;
}
</style>
