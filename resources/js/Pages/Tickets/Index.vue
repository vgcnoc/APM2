<template>
    <AppLayout title="Ticketing & Gangguan" subtitle="Kelola laporan gangguan pelanggan">
        <div class="space-y-6">
            <!-- Header Actions & Filters -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-2">
                <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3 flex-1">
                    <div class="relative w-full sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari ID tiket, subjek, pelanggan..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    
                    <select v-model="filterStatus" @change="performSearch" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm bg-gray-50/50">
                        <option value="">Semua Status</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>

                    <select v-model="filterPriority" @change="performSearch" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm bg-gray-50/50">
                        <option value="">Semua Prioritas</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <button @click="openModal()" class="btn-primary w-full md:w-auto shrink-0 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Tiket
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="p-4 w-40">Ticket ID</th>
                                <th class="p-4">Pelanggan</th>
                                <th class="p-4">Subjek & Kategori</th>
                                <th class="p-4">Prioritas</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Teknisi</th>
                                <th class="p-4 w-24 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 font-mono font-medium text-blue-600">{{ ticket.ticket_number }}</td>
                                <td class="p-4">
                                    <div class="font-medium text-gray-900">{{ ticket.customer?.name || '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ ticket.customer?.customer_id || '-' }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-medium text-gray-900 line-clamp-1" :title="ticket.subject">{{ ticket.subject }}</div>
                                    <div class="text-xs text-gray-500 uppercase">{{ ticket.category }}</div>
                                </td>
                                <td class="p-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-medium border',
                                        ticket.priority === 'critical' ? 'bg-red-50 text-red-600 border-red-200' :
                                        ticket.priority === 'high' ? 'bg-orange-50 text-orange-600 border-orange-200' :
                                        ticket.priority === 'medium' ? 'bg-amber-50 text-amber-600 border-amber-200' :
                                        'bg-gray-50 text-gray-600 border-gray-200'
                                    ]">
                                        {{ ticket.priority }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-medium border flex items-center gap-1.5 w-max',
                                        ticket.status === 'open' ? 'bg-blue-50 text-blue-600 border-blue-200' :
                                        ticket.status === 'in_progress' ? 'bg-amber-50 text-amber-600 border-amber-200' :
                                        ticket.status === 'resolved' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
                                        'bg-gray-50 text-gray-600 border-gray-200'
                                    ]">
                                        <div :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            ticket.status === 'open' ? 'bg-blue-500' :
                                            ticket.status === 'in_progress' ? 'bg-amber-500' :
                                            ticket.status === 'resolved' ? 'bg-emerald-500' :
                                            'bg-gray-500'
                                        ]"></div>
                                        {{ ticket.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600">
                                    {{ ticket.assignee?.name || 'Belum di-assign' }}
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openModal(ticket)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit Tiket">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteTicket(ticket.id)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Tiket">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-500">
                                    Tidak ada data tiket gangguan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="tickets.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Menampilkan <span class="font-medium text-gray-900">{{ tickets.from }}</span> - <span class="font-medium text-gray-900">{{ tickets.to }}</span> dari <span class="font-medium text-gray-900">{{ tickets.total }}</span> tiket
                    </span>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in tickets.links" 
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                !link.url ? 'text-gray-300 cursor-not-allowed' :
                                link.active ? 'bg-blue-50 text-blue-600' : 
                                'text-gray-600 hover:bg-gray-100'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Create/Edit -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                    <div class="p-5 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
                        <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Tiket Gangguan' : 'Buat Tiket Baru' }}</h3>
                        <button @click="closeModal" class="text-gray-500 hover:text-gray-900">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Pelanggan</label>
                                    <select v-model="form.customer_id" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm" :disabled="isEditing">
                                        <option value="">Pilih Pelanggan...</option>
                                        <option v-for="cust in customers" :key="cust.id" :value="cust.id">{{ cust.customer_id }} - {{ cust.name }}</option>
                                    </select>
                                    <p v-if="form.errors.customer_id" class="text-red-500 text-xs mt-1">{{ form.errors.customer_id }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Kategori Gangguan</label>
                                    <select v-model="form.category" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm">
                                        <option value="">Pilih Kategori...</option>
                                        <option value="network">Jaringan (Network)</option>
                                        <option value="billing">Billing / Tagihan</option>
                                        <option value="hardware">Hardware / Perangkat</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                    <p v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ form.errors.category }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Prioritas</label>
                                    <select v-model="form.priority" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm">
                                        <option value="low">Low (Rendah)</option>
                                        <option value="medium">Medium (Sedang)</option>
                                        <option value="high">High (Tinggi)</option>
                                        <option value="critical">Critical (Sangat Mendesak)</option>
                                    </select>
                                </div>

                                <div v-if="isEditing">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                                    <select v-model="form.status" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm">
                                        <option value="open">Open</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Subjek</label>
                                    <input type="text" v-model="form.subject" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm" placeholder="Contoh: Internet Mati Sejak Pagi">
                                    <p v-if="form.errors.subject" class="text-red-500 text-xs mt-1">{{ form.errors.subject }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Deskripsi Lengkap</label>
                                    <textarea v-model="form.description" rows="4" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm" placeholder="Deskripsikan masalah secara detail..."></textarea>
                                    <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Assign Teknisi (Opsional)</label>
                                    <select v-model="form.assigned_to" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm">
                                        <option value="">-- Belum Di-assign --</option>
                                        <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" class="btn-primary" :disabled="form.processing">
                                {{ isEditing ? 'Update Tiket' : 'Simpan Tiket' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tickets: Object,
    customers: Array,
    technicians: Array,
    filters: Object
});

const search = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || '');
const filterPriority = ref(props.filters.priority || '');

const performSearch = () => {
    router.get('/tickets', { 
        search: search.value,
        status: filterStatus.value,
        priority: filterPriority.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    customer_id: '',
    category: '',
    priority: 'medium',
    subject: '',
    description: '',
    assigned_to: '',
    status: 'open'
});

const openModal = (ticket = null) => {
    if (ticket) {
        isEditing.value = true;
        editingId.value = ticket.id;
        form.customer_id = ticket.customer_id;
        form.category = ticket.category;
        form.priority = ticket.priority;
        form.subject = ticket.subject;
        form.description = ticket.description;
        form.assigned_to = ticket.assigned_to || '';
        form.status = ticket.status;
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.priority = 'medium';
        form.status = 'open';
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(`/tickets/${editingId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/tickets', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteTicket = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus tiket ini?')) {
        router.delete(`/tickets/${id}`);
    }
};
</script>
