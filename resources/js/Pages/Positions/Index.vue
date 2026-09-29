<template>
    <AppLayout title="Posisi / Jabatan" subtitle="Kelola master data posisi dan jabatan karyawan">
        <div class="space-y-6">
            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="glass-card p-5 border-2 border-indigo-500/10 hover:border-indigo-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-indigo-100 text-indigo-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Posisi</p>
                            <p class="text-2xl font-bold text-gray-900">{{ positions.total || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5 border-2 border-emerald-500/10 hover:border-emerald-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Departemen</p>
                            <p class="text-2xl font-bold text-gray-900">{{ uniqueDepartments }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5 border-2 border-amber-500/10 hover:border-amber-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Rata-rata Gaji</p>
                            <p class="text-xl font-bold text-gray-900">Rp {{ formatNumber(avgSalary) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <div class="relative w-full sm:w-80">
                    <input 
                        type="text" 
                        v-model="search" 
                        placeholder="Cari posisi atau departemen..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                        @keyup.enter="performSearch"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button @click="openModal()" class="btn-primary w-full md:w-auto shrink-0 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Posisi
                </button>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Posisi / Jabatan</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Departemen</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Gaji Pokok</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="pos in positions.data" :key="pos.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                            {{ pos.name?.charAt(0)?.toUpperCase() }}
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900">{{ pos.name }}</p>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span v-if="pos.department" class="px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ pos.department }}
                                    </span>
                                    <span v-else class="text-xs text-gray-300">-</span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <p class="text-sm font-semibold text-gray-900">Rp {{ formatNumber(pos.base_salary) }}</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ pos.description || '-' }}</p>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openModal(pos)" title="Edit" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button @click="deletePosition(pos.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="positions.data.length === 0">
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <p class="text-sm font-medium">Belum ada data posisi</p>
                                        <p class="text-xs text-gray-300 mt-1">Klik "Tambah Posisi" untuk memulai</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="positions.links && positions.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Menampilkan {{ positions.from }} - {{ positions.to }} dari {{ positions.total }} data</p>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in positions.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-y-auto max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Posisi' : 'Tambah Posisi Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Posisi / Jabatan *</label>
                            <input v-model="form.name" type="text" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Contoh: Teknisi, NOC, Manager">
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                            <input v-model="form.department" type="text" list="dept-options" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Contoh: Teknikal, Operasional">
                            <datalist id="dept-options">
                                <option value="Teknikal"></option>
                                <option value="Operasional"></option>
                                <option value="Keuangan"></option>
                                <option value="Marketing"></option>
                                <option value="Manajemen"></option>
                                <option value="Customer Service"></option>
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok (Rp)</label>
                            <input v-model="form.base_salary" type="number" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                            <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 resize-none" placeholder="Deskripsi tugas dan tanggung jawab (opsional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">Batal</button>
                    <button type="button" @click="submit" :disabled="form.processing" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Posisi' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    positions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    department: '',
    base_salary: 0,
    description: '',
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const uniqueDepartments = computed(() => {
    const depts = new Set(props.positions.data?.filter(p => p.department).map(p => p.department));
    return depts.size;
});

const avgSalary = computed(() => {
    const data = props.positions.data?.filter(p => p.base_salary > 0) || [];
    if (data.length === 0) return 0;
    return Math.round(data.reduce((sum, p) => sum + Number(p.base_salary), 0) / data.length);
});

const performSearch = () => {
    router.get('/positions', { search: search.value }, { preserveState: true, preserveScroll: true });
};

function openModal(pos = null) {
    if (pos) {
        isEditing.value = true;
        editingId.value = pos.id;
        form.name = pos.name || '';
        form.department = pos.department || '';
        form.base_salary = pos.base_salary || 0;
        form.description = pos.description || '';
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
}

function submit() {
    if (isEditing.value) {
        form.post(`/positions/${editingId.value}/update`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/positions', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function deletePosition(id) {
    if (confirm('Apakah Anda yakin ingin menghapus posisi ini?')) {
        router.post(`/positions/${id}/delete`);
    }
}
</script>
