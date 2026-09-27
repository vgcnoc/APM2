<template>
    <AppLayout title="Manajemen User" subtitle="Kelola pengguna dan hak akses aplikasi">
        <div class="space-y-6">
            <!-- Stats / Actions -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex gap-4">
                    <div class="px-4 py-2 bg-blue-50 text-blue-700 rounded-xl font-medium text-sm flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Total: {{ users.total }} User
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                    <div class="relative flex-1 md:w-64">
                        <input v-model="search" type="text" placeholder="Cari nama atau email..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 absolute left-3.5 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    
                    <select v-model="filterRole" class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-700 font-medium">
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="cs">CS</option>
                        <option value="teknisi">Teknisi</option>
                        <option value="sales">Sales</option>
                        <option value="noc">NOC</option>
                    </select>

                    <button @click="openCreateModal" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-sm hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah User
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bergabung</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium uppercase" :class="{
                                        'bg-purple-100 text-purple-700': user.role === 'admin',
                                        'bg-blue-100 text-blue-700': user.role === 'cs',
                                        'bg-orange-100 text-orange-700': user.role === 'teknisi',
                                        'bg-emerald-100 text-emerald-700': user.role === 'sales',
                                        'bg-gray-100 text-gray-700': user.role === 'noc'
                                    }">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="flex items-center gap-1.5 text-xs font-medium" :class="user.is_active ? 'text-green-600' : 'text-red-500'">
                                        <span class="w-2 h-2 rounded-full" :class="user.is_active ? 'bg-green-500' : 'bg-red-500'"></span>
                                        {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-right space-x-3">
                                    <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900 transition-colors">Edit</button>
                                    <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900 transition-colors">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Tidak ada data user.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="users.links?.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                    <span class="text-sm text-gray-500">Menampilkan {{ users.from }}-{{ users.to }} dari {{ users.total }}</span>
                    <div class="flex gap-1">
                        <Link v-for="(link, i) in users.links" :key="i" :href="link.url || '#'" 
                            class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-200',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label">
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
            
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit User' : 'Tambah User' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                        <input v-model="form.name" type="text" required class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="John Doe">
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input v-model="form.email" type="email" required class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="user@isp.local">
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select v-model="form.role" required class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="admin">Admin</option>
                            <option value="cs">CS</option>
                            <option value="teknisi">Teknisi</option>
                            <option value="sales">Sales</option>
                            <option value="noc">NOC</option>
                        </select>
                        <p v-if="form.errors.role" class="mt-1 text-xs text-red-500">{{ form.errors.role }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password <span v-if="!isEditing">*</span></label>
                        <input v-model="form.password" type="password" :required="!isEditing" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Minimal 8 karakter">
                        <p v-if="isEditing" class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin merubah password.</p>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>
                    
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-2">
                            <input type="checkbox" v-model="form.is_active" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">User Aktif (Bisa Login)</span>
                        </label>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const filterRole = ref(props.filters.role || '');
const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    role: 'teknisi',
    is_active: true,
});

const performSearch = debounce(() => {
    router.get('/users', {
        search: search.value,
        role: filterRole.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch(search, performSearch);
watch(filterRole, performSearch);

function openCreateModal() {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.id = null;
    isModalOpen.value = true;
}

function openEditModal(user) {
    isEditing.value = true;
    form.reset();
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    form.is_active = !!user.is_active;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
}

function submit() {
    if (isEditing.value && form.id) {
        form.post(`/users/${form.id}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/users', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        router.post(`/users/${id}/delete`);
    }
}
</script>
