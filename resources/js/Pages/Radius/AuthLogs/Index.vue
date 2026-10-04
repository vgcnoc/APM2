<template>
    <AppLayout title="Log Autentikasi (RADIUS)">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Log Autentikasi (Radius)</h2>
                    <p class="text-gray-500 text-sm mt-1">Pantau riwayat login pelanggan, sukses maupun gagal</p>
                </div>
                
                <div class="flex gap-2">
                    <select v-model="statusFilter" @change="search" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="success">Sukses (Accept)</option>
                        <option value="failed">Gagal (Reject)</option>
                    </select>

                    <input 
                        v-model="searchQuery" 
                        @keyup.enter="search"
                        type="text" 
                        placeholder="Cari username atau alasan..." 
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64"
                    />
                    <button @click="search" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 shadow-sm transition-all">
                        Cari
                    </button>
                    <button @click="refresh" class="px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-sm font-semibold hover:bg-indigo-100 shadow-sm flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Password Diajukan</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Pesan (Reply)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-600">{{ log.authdate }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ log.username }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ log.pass || '-' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span v-if="log.reply === 'Access-Accept'" class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Sukses</span>
                                <span v-else class="px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Gagal</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ log.reply }}
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-sm">Belum ada riwayat autentikasi tercatat.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="mt-4 flex justify-between items-center" v-if="logs.data.length > 0">
                <p class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium">{{ logs.from }}</span> sampai <span class="font-medium">{{ logs.to }}</span> dari <span class="font-medium">{{ logs.total }}</span> log
                </p>
                <div class="flex gap-1">
                    <Link 
                        v-for="(link, i) in logs.links" 
                        :key="i"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 text-sm rounded-lg',
                            link.active ? 'bg-indigo-600 text-white font-medium' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    logs: Object,
    filters: Object
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

function search() {
    router.get('/radius/auth-logs', { 
        search: searchQuery.value,
        status: statusFilter.value 
    }, { preserveState: true });
}

function refresh() {
    router.reload({ only: ['logs'] });
}
</script>
