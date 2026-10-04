<template>
    <AppLayout title="Data Router">
        <div class="p-6 max-w-6xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Data Router</h2>
                        <p class="text-sm text-gray-500 mt-1">Kelola data router Mikrotik dan hak akses pengguna.</p>
                    </div>
                    <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Tambah Data Router
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Router</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port API</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port Winbox</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">PIC / Penanggung Jawab</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="router in routers.data" :key="router.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ router.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ router.api_port }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ router.winbox_port }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ router.pic ? router.pic.name : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <button @click="openEditModal(router)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                    <button @click="deleteRouter(router.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="routers.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    Belum ada data Router.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-auto overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Data Router' : 'Tambah Data Router' }}</h3>
                        <p class="text-xs text-gray-500">Tambahkan router Mikrotik dan port akses yang digunakan.</p>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                        <!-- Informasi Router -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                            <h4 class="font-bold text-sm text-indigo-700 flex items-center gap-2 mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Informasi Router
                            </h4>
                            <div class="bg-red-50 text-red-700 p-3 rounded-lg text-xs mb-4 border border-red-100">
                                <span class="font-bold">Baca dulu sebelum lanjut.</span><br>
                                Jangan mengganti Identity Mikrotik setelah data router dibuat agar relasi data tetap konsisten.
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Router <span class="text-red-500">*Req</span></label>
                                <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase" placeholder="Gunakan HURUF BESAR" required />
                                <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>
                        </div>

                        <!-- Port Akses -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                            <h4 class="font-bold text-sm text-indigo-700 flex items-center gap-2 mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Port Akses
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port API</label>
                                    <input v-model="form.api_port" type="number" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Default : 8728" />
                                    <p class="text-xs text-gray-500 mt-1">Abaikan jika menggunakan port default. Harus sama dengan yang di Mikrotik</p>
                                    <p v-if="form.errors.api_port" class="text-red-500 text-xs mt-1">{{ form.errors.api_port }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port Winbox</label>
                                    <input v-model="form.winbox_port" type="number" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Default : 8291" />
                                    <p class="text-xs text-gray-500 mt-1">Abaikan jika menggunakan port default. Harus sama dengan yang di Mikrotik</p>
                                    <p v-if="form.errors.winbox_port" class="text-red-500 text-xs mt-1">{{ form.errors.winbox_port }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Hak Akses -->
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                            <h4 class="font-bold text-sm text-indigo-700 flex items-center gap-2 mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                Hak Akses
                            </h4>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assign To / Penanggung Jawab / PIC / Investor</label>
                                <select v-model="form.pic_id" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option :value="null">-- Pilih Pengguna --</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                                <p v-if="form.errors.pic_id" class="text-red-500 text-xs mt-1">{{ form.errors.pic_id }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Tutup
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    routers: Object,
    users: Array,
    filters: Object,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    api_port: '',
    winbox_port: '',
    pic_id: null,
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(router) {
    isEditing.value = true;
    editId.value = router.id;
    form.name = router.name;
    form.api_port = router.api_port == 8728 ? '' : router.api_port;
    form.winbox_port = router.winbox_port == 8291 ? '' : router.winbox_port;
    form.pic_id = router.pic_id;
    form.clearErrors();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function submit() {
    // form.name = form.name.toUpperCase(); // Optional enforce uppercase
    
    if (isEditing.value) {
        form.post(`/routers/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/routers', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteRouter(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data Router ini?')) {
        router.post(`/routers/${id}/delete`);
    }
}
</script>
