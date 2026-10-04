<template>
    <AppLayout title="Data NAS (Router)">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen NAS / Router</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data NAS (Network Access Server) untuk FreeRADIUS</p>
                </div>
                <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah NAS
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">IP / Hostname (nasname)</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Shortname</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Secret</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Deskripsi</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="n in nas.data" :key="n.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 font-mono">{{ n.nasname }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ n.shortname || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ n.type || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono text-xs">
                                <span class="bg-gray-100 px-2 py-1 rounded select-all">{{ n.secret }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ n.description || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button @click="openEditModal(n)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                <button @click="deleteNas(n.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="nas.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                Belum ada data NAS.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit NAS' : 'Tambah NAS Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">IP Address / Hostname (nasname)</label>
                            <input v-model="form.nasname" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="192.168.1.1" required />
                            <p v-if="form.errors.nasname" class="text-red-500 text-xs mt-1">{{ form.errors.nasname }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Shortname (Nama Singkat)</label>
                            <input v-model="form.shortname" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Router-Pusat" />
                            <p v-if="form.errors.shortname" class="text-red-500 text-xs mt-1">{{ form.errors.shortname }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Type</label>
                            <select v-model="form.type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="other">Other</option>
                                <option value="mikrotik">Mikrotik</option>
                                <option value="cisco">Cisco</option>
                                <option value="chillispot">ChilliSpot</option>
                            </select>
                            <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">RADIUS Secret</label>
                            <input v-model="form.secret" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="secretpassword123" required />
                            <p v-if="form.errors.secret" class="text-red-500 text-xs mt-1">{{ form.errors.secret }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Deskripsi</label>
                            <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Router distribusi"></textarea>
                        </div>
                    </div>
                    
                    <div class="sticky bottom-0 bg-white p-6 border-t border-gray-100 flex justify-end rounded-b-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    nas: Object,
    filters: Object
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    nasname: '',
    shortname: '',
    type: 'other',
    ports: null,
    secret: '',
    server: null,
    community: null,
    description: ''
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    form.type = 'mikrotik';
    isModalOpen.value = true;
}

function openEditModal(n) {
    isEditing.value = true;
    editId.value = n.id;
    form.nasname = n.nasname;
    form.shortname = n.shortname || '';
    form.type = n.type || 'other';
    form.ports = n.ports;
    form.secret = n.secret;
    form.server = n.server || null;
    form.community = n.community || null;
    form.description = n.description || '';
    form.clearErrors();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function submit() {
    if (isEditing.value) {
        form.post(`/radius/nas/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/radius/nas', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteNas(id) {
    if (confirm('Apakah Anda yakin ingin menghapus NAS ini? (Pelanggan yang terhubung mungkin tidak bisa authentikasi)')) {
        router.post(`/radius/nas/${id}/delete`);
    }
}
</script>
