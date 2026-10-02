<template>
    <AppLayout title="Manajemen Role & Akses" subtitle="Atur role dan hak akses pengguna secara detail">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar: Daftar Role -->
            <div class="w-full lg:w-80 shrink-0 space-y-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between p-5 border-b border-gray-100">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Daftar Role
                        </h3>
                        <button @click="openCreateModal" class="text-xs bg-blue-600 text-white hover:bg-blue-700 font-bold px-3.5 py-2 rounded-lg transition-all shadow-sm hover:shadow-md flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah
                        </button>
                    </div>

                    <div class="p-3 space-y-1.5 max-h-[calc(100vh-300px)] overflow-y-auto">
                        <div v-for="role in roles" :key="role.id"
                            @click="selectRole(role)"
                            :class="[
                                'p-4 rounded-xl cursor-pointer transition-all duration-200 border-2 group relative',
                                selectedRole?.id === role.id
                                    ? 'bg-blue-50 border-blue-300 shadow-sm ring-1 ring-blue-100'
                                    : 'bg-white border-transparent hover:bg-gray-50 hover:border-gray-200'
                            ]">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div :class="[
                                        'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-sm font-bold transition-colors',
                                        selectedRole?.id === role.id ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600'
                                    ]">
                                        {{ role.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-gray-900 capitalize truncate">{{ role.name }}</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ role.permissions.length }} hak akses · {{ role.users_count }} user
                                        </p>
                                    </div>
                                </div>
                                <div v-if="role.name !== 'admin'" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                    <button @click.stop="openEditModal(role)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Rename">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button @click.stop="confirmDeleteRole(role)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Admin badge -->
                            <div v-if="role.name === 'admin'" class="mt-2">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/></svg>
                                    Super Admin
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main: Detail Hak Akses -->
            <div class="flex-1 min-w-0">
                <!-- Selected Role -->
                <div v-if="selectedRole" class="space-y-5">
                    <!-- Header -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 capitalize flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold">{{ selectedRole.name.charAt(0).toUpperCase() }}</span>
                                    Hak Akses: {{ selectedRole.name }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ checkedCount }} dari {{ totalCount }} hak akses aktif
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button v-if="selectedRole.name !== 'admin'" @click="toggleAll" class="px-4 py-2 text-sm font-medium rounded-xl border transition-colors"
                                    :class="isAllChecked ? 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200' : 'bg-blue-50 text-blue-600 border-blue-200 hover:bg-blue-100'">
                                    {{ isAllChecked ? 'Nonaktifkan Semua' : 'Aktifkan Semua' }}
                                </button>
                                <button v-if="selectedRole.name !== 'admin'" @click="savePermissions" :disabled="isSaving"
                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-all shadow-sm hover:shadow-md disabled:opacity-50 flex items-center gap-2">
                                    <svg v-if="isSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                </button>
                            </div>
                        </div>

                        <!-- Admin Notice -->
                        <div v-if="selectedRole.name === 'admin'" class="mt-4 flex items-start gap-3 bg-amber-50 text-amber-800 p-4 rounded-xl border border-amber-200">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <p class="text-sm font-medium">Role <b>Admin</b> adalah Super Admin yang memiliki seluruh hak akses. Pengaturan tidak dapat diubah.</p>
                        </div>

                        <!-- Progress bar -->
                        <div v-if="selectedRole.name !== 'admin'" class="mt-4">
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2 rounded-full transition-all duration-500"
                                    :style="{ width: (checkedCount / totalCount * 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Permission Groups -->
                    <div class="space-y-4" :class="{'opacity-60 pointer-events-none': selectedRole.name === 'admin'}">
                        <div v-for="(group, gIdx) in permissionGroups" :key="gIdx"
                            class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md">
                            <!-- Group Header -->
                            <button @click="toggleGroup(gIdx)"
                                class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                                        :class="getGroupColor(group.icon)">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[group.icon] || iconPaths['cog']"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <h4 class="font-bold text-gray-900 text-sm">{{ group.group }}</h4>
                                        <p class="text-xs text-gray-500">{{ getGroupCheckedCount(group) }}/{{ group.permissions.length }} aktif</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- Group Toggle All -->
                                    <label @click.stop class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="isGroupAllChecked(group)" @change="toggleGroupAll(group)" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                    <!-- Collapse Arrow -->
                                    <svg :class="['w-5 h-5 text-gray-400 transition-transform duration-200', openGroups.includes(gIdx) ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <!-- Group Body -->
                            <div v-show="openGroups.includes(gIdx)" class="border-t border-gray-100">
                                <div class="divide-y divide-gray-50">
                                    <div v-for="perm in group.permissions" :key="perm.name"
                                        class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50/50 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <!-- Type Badge -->
                                            <span :class="[
                                                'shrink-0 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border',
                                                perm.type === 'menu' ? 'bg-indigo-50 text-indigo-600 border-indigo-200' :
                                                perm.type === 'feature' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
                                                'bg-orange-50 text-orange-600 border-orange-200'
                                            ]">
                                                {{ perm.type === 'menu' ? 'Menu' : perm.type === 'feature' ? 'Fitur' : 'Aksi' }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ perm.label }}</p>
                                                <p class="text-[11px] text-gray-400 font-mono truncate">{{ perm.name }}</p>
                                            </div>
                                        </div>

                                        <!-- Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                                            <input type="checkbox" :checked="selectedPermissions.includes(perm.name)"
                                                @change="togglePermission(perm.name)" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-6 text-blue-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-xl mb-2">Pilih Role</h3>
                    <p class="text-gray-500 max-w-sm mx-auto">Pilih salah satu role di sebelah kiri untuk melihat dan mengatur hak akses secara detail.</p>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit Role -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fade-in-up overflow-hidden">
                    <form @submit.prevent="submitForm">
                        <div class="px-6 pt-6 pb-5">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Nama Role' : 'Tambah Role Baru' }}</h3>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Role</label>
                                <input v-model="formName" type="text" required
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    placeholder="Contoh: teknisi, sales, spv">
                                <p class="mt-2 text-xs text-gray-500">Nama role akan otomatis diformat. Hak akses bisa diatur setelah role dibuat.</p>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="showModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Batal</button>
                            <button type="submit" :disabled="isSubmitting" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-sm disabled:opacity-50">
                                {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Role?</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Apakah Anda yakin ingin menghapus role <b class="capitalize">{{ roleToDelete?.name }}</b>?
                        <span v-if="roleToDelete?.users_count > 0" class="block mt-1 text-red-500 font-medium">
                            ⚠️ Masih ada {{ roleToDelete.users_count }} user dengan role ini!
                        </span>
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">Batal</button>
                        <button @click="deleteRole" :disabled="isDeleting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center">
                            {{ isDeleting ? 'Menghapus...' : 'Hapus' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: Array,
    permissionGroups: Array,
});

// ── State ──────────────────────────────────────────
const selectedRole = ref(null);
const selectedPermissions = ref([]);
const openGroups = ref([]);
const isSaving = ref(false);

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const formName = ref('');
const isSubmitting = ref(false);

// Delete modal state
const showDeleteModal = ref(false);
const roleToDelete = ref(null);
const isDeleting = ref(false);

// ── Computed ───────────────────────────────────────
const allPermissionNames = computed(() => {
    const names = [];
    props.permissionGroups.forEach(g => {
        g.permissions.forEach(p => names.push(p.name));
    });
    return names;
});

const totalCount = computed(() => allPermissionNames.value.length);

const checkedCount = computed(() => {
    if (selectedRole.value?.name === 'admin') return totalCount.value;
    return selectedPermissions.value.length;
});

const isAllChecked = computed(() => checkedCount.value === totalCount.value);

// ── Methods ────────────────────────────────────────
const selectRole = (role) => {
    selectedRole.value = role;
    selectedPermissions.value = [...role.permissions];
    // Open all groups by default
    openGroups.value = props.permissionGroups.map((_, i) => i);
};

const toggleGroup = (idx) => {
    const i = openGroups.value.indexOf(idx);
    if (i >= 0) openGroups.value.splice(i, 1);
    else openGroups.value.push(idx);
};

const togglePermission = (name) => {
    const i = selectedPermissions.value.indexOf(name);
    if (i >= 0) selectedPermissions.value.splice(i, 1);
    else selectedPermissions.value.push(name);
};

const toggleAll = () => {
    if (isAllChecked.value) {
        selectedPermissions.value = [];
    } else {
        selectedPermissions.value = [...allPermissionNames.value];
    }
};

const isGroupAllChecked = (group) => {
    return group.permissions.every(p => selectedPermissions.value.includes(p.name));
};

const toggleGroupAll = (group) => {
    if (isGroupAllChecked(group)) {
        group.permissions.forEach(p => {
            const i = selectedPermissions.value.indexOf(p.name);
            if (i >= 0) selectedPermissions.value.splice(i, 1);
        });
    } else {
        group.permissions.forEach(p => {
            if (!selectedPermissions.value.includes(p.name)) {
                selectedPermissions.value.push(p.name);
            }
        });
    }
};

const getGroupCheckedCount = (group) => {
    return group.permissions.filter(p => selectedPermissions.value.includes(p.name)).length;
};

const savePermissions = () => {
    if (!selectedRole.value || selectedRole.value.name === 'admin') return;
    isSaving.value = true;
    router.post(`/settings/roles/${selectedRole.value.id}/update`, {
        name: selectedRole.value.name,
        permissions: selectedPermissions.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Update local state
            const role = props.roles.find(r => r.id === selectedRole.value.id);
            if (role) {
                role.permissions = [...selectedPermissions.value];
                selectedRole.value = role;
            }
        },
        onFinish: () => isSaving.value = false,
    });
};

// ── Create/Edit Modal ──────────────────────────────
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    formName.value = '';
    showModal.value = true;
};

const openEditModal = (role) => {
    isEditing.value = true;
    editingId.value = role.id;
    formName.value = role.name;
    showModal.value = true;
};

const submitForm = () => {
    isSubmitting.value = true;
    if (isEditing.value) {
        router.post(`/settings/roles/${editingId.value}/update`, {
            name: formName.value,
            permissions: selectedRole.value?.id === editingId.value ? selectedPermissions.value : undefined,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                if (selectedRole.value?.id === editingId.value) {
                    selectedRole.value.name = formName.value;
                }
            },
            onFinish: () => isSubmitting.value = false,
        });
    } else {
        router.post('/settings/roles', { name: formName.value, permissions: [] }, {
            preserveScroll: true,
            onSuccess: () => showModal.value = false,
            onFinish: () => isSubmitting.value = false,
        });
    }
};

// ── Delete Modal ────────────────────────────────────
const confirmDeleteRole = (role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const deleteRole = () => {
    if (!roleToDelete.value) return;
    isDeleting.value = true;
    router.post(`/settings/roles/${roleToDelete.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            if (selectedRole.value?.id === roleToDelete.value.id) {
                selectedRole.value = null;
                selectedPermissions.value = [];
            }
        },
        onFinish: () => {
            isDeleting.value = false;
            roleToDelete.value = null;
        },
    });
};

// ── Helpers ─────────────────────────────────────────
const getGroupColor = (icon) => {
    const colors = {
        'dashboard': 'bg-blue-100 text-blue-600',
        'document-add': 'bg-yellow-100 text-yellow-600',
        'clipboard-check': 'bg-cyan-100 text-cyan-600',
        'cog': 'bg-indigo-100 text-indigo-600',
        'key': 'bg-purple-100 text-purple-600',
        'badge-check': 'bg-emerald-100 text-emerald-600',
        'users': 'bg-sky-100 text-sky-600',
        'alert-circle': 'bg-red-100 text-red-600',
        'globe': 'bg-teal-100 text-teal-600',
        'server': 'bg-gray-200 text-gray-700',
        'box': 'bg-amber-100 text-amber-600',
        'git-branch': 'bg-lime-100 text-lime-700',
        'wifi': 'bg-violet-100 text-violet-600',
        'archive': 'bg-orange-100 text-orange-600',
        'shopping-cart': 'bg-pink-100 text-pink-600',
        'briefcase': 'bg-rose-100 text-rose-600',
        'map': 'bg-emerald-100 text-emerald-600',
        'lock-closed': 'bg-slate-200 text-slate-700',
        'color-swatch': 'bg-fuchsia-100 text-fuchsia-600',
        'code': 'bg-zinc-200 text-zinc-700',
        'package': 'bg-cyan-100 text-cyan-700',
    };
    return colors[icon] || 'bg-gray-100 text-gray-600';
};

const iconPaths = {
    dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'document-add': 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'clipboard-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    cog: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    key: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
    'badge-check': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'alert-circle': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    globe: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    server: 'M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
    box: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'git-branch': 'M6 3v12M18 9a3 3 0 01-3 3H9m-3 0a3 3 0 003 3h0a3 3 0 003-3m0 0V3',
    wifi: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0',
    archive: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    'shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    briefcase: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    map: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
    'lock-closed': 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    'color-swatch': 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    code: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
    package: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
};
</script>
