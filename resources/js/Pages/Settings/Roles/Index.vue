<template>
    <AppLayout title="Manajemen Role & Akses" subtitle="Atur role dan hak akses pengguna">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar list roles -->
            <div class="w-full lg:w-1/3 space-y-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900">Daftar Role</h3>
                        <button @click="openModal()" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold px-3 py-1.5 rounded-lg transition-colors">
                            + Tambah
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div v-for="role in roles" :key="role.id" 
                            @click="selectRole(role)"
                            :class="selectedRole?.id === role.id ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-100 hover:bg-gray-100'"
                            class="p-4 rounded-xl border cursor-pointer transition-colors flex items-center justify-between group">
                            <div>
                                <h4 class="font-bold text-gray-900 capitalize">{{ role.name }}</h4>
                                <p class="text-xs text-gray-500 mt-1">{{ role.permissions.length }} Hak Akses</p>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity" v-if="role.name !== 'admin'">
                                <button @click.stop="openModal(role)" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click.stop="deleteRole(role)" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Role Permissions -->
            <div class="w-full lg:w-2/3">
                <div v-if="selectedRole" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg capitalize">Hak Akses: {{ selectedRole.name }}</h3>
                            <p class="text-sm text-gray-500">Centang kotak untuk memberikan hak akses pada modul tertentu.</p>
                        </div>
                        <button v-if="selectedRole.name !== 'admin'" @click="savePermissions" :disabled="isSaving" class="btn-primary text-sm">
                            <span v-if="isSaving">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </button>
                    </div>

                    <div v-if="selectedRole.name === 'admin'" class="bg-blue-50 text-blue-700 p-4 rounded-xl border border-blue-100 mb-6">
                        <p class="text-sm font-medium">Role <b>Admin</b> memiliki seluruh hak akses (Super Admin). Anda tidak dapat mengubah hak akses untuk role ini.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" :class="{'opacity-50 pointer-events-none': selectedRole.name === 'admin'}">
                        <div v-for="(group, key) in permissions" :key="key" class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
                                <h4 class="font-bold text-gray-700 capitalize">{{ key }}</h4>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" @change="toggleGroup(key, $event.target.checked)" :checked="isGroupFullyChecked(key)" class="w-4 h-4 rounded text-blue-600 border-gray-300">
                                    <span class="text-xs font-medium text-gray-500">Pilih Semua</span>
                                </label>
                            </div>
                            <div class="space-y-2">
                                <label v-for="perm in group" :key="perm.id" class="flex items-center gap-3 p-2 hover:bg-gray-100 rounded-lg cursor-pointer transition-colors">
                                    <input type="checkbox" v-model="selectedPermissions" :value="perm.name" class="w-4 h-4 rounded text-blue-600 border-gray-300">
                                    <span class="text-sm font-medium text-gray-700">{{ formatPermissionName(perm.name) }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">Pilih Role</h3>
                    <p class="text-gray-500 mt-1">Pilih role di sebelah kiri untuk melihat dan mengatur hak aksesnya.</p>
                </div>
            </div>
        </div>

        <!-- Modal CRUD Role -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity backdrop-blur-sm" @click="showModal = false"></div>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <form @submit.prevent="submitForm">
                        <div class="px-6 pt-6 pb-4">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">{{ isEditing ? 'Edit Role' : 'Tambah Role Baru' }}</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Role</label>
                                <input v-model="form.name" type="text" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Gunakan huruf kecil tanpa spasi (cth: teknisi, sales, spv)</p>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="showModal = false" class="btn-secondary">Batal</button>
                            <button type="submit" :disabled="form.processing" class="btn-primary">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: Array,
    permissions: Object
});

const selectedRole = ref(null);
const selectedPermissions = ref([]);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isSaving = ref(false);

const form = useForm({
    name: '',
});

const selectRole = (role) => {
    selectedRole.value = role;
    selectedPermissions.value = role.permissions.map(p => p.name);
};

const formatPermissionName = (name) => {
    const parts = name.split('_');
    if (parts.length > 1) {
        return parts[1].charAt(0).toUpperCase() + parts[1].slice(1);
    }
    return name;
};

const isGroupFullyChecked = (groupKey) => {
    const groupPerms = props.permissions[groupKey].map(p => p.name);
    return groupPerms.every(p => selectedPermissions.value.includes(p));
};

const toggleGroup = (groupKey, isChecked) => {
    const groupPerms = props.permissions[groupKey].map(p => p.name);
    if (isChecked) {
        groupPerms.forEach(p => {
            if (!selectedPermissions.value.includes(p)) selectedPermissions.value.push(p);
        });
    } else {
        selectedPermissions.value = selectedPermissions.value.filter(p => !groupPerms.includes(p));
    }
};

const savePermissions = () => {
    if (!selectedRole.value || selectedRole.value.name === 'admin') return;
    
    isSaving.value = true;
    router.post(`/settings/roles/${selectedRole.value.id}/update`, {
        name: selectedRole.value.name,
        permissions: selectedPermissions.value
    }, {
        preserveScroll: true,
        onFinish: () => isSaving.value = false
    });
};

const openModal = (role = null) => {
    if (role) {
        isEditing.value = true;
        editingId.value = role.id;
        form.name = role.name;
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
    }
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.post(`/settings/roles/${editingId.value}/update`, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                if (selectedRole.value?.id === editingId.value) {
                    selectedRole.value.name = form.name;
                }
            }
        });
    } else {
        form.post('/settings/roles', {
            preserveScroll: true,
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteRole = (role) => {
    if (confirm(`Hapus role ${role.name}? Semua user dengan role ini mungkin kehilangan akses.`)) {
        router.post(`/settings/roles/${role.id}/delete`, {}, { preserveScroll: true });
    }
};
</script>
