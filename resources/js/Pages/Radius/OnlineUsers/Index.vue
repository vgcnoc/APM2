<template>
    <AppLayout title="Pengguna Online (RADIUS)">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Pengguna Online</h2>
                    <p class="text-gray-500 text-sm mt-1">Daftar pelanggan dan voucher yang sedang terhubung ke internet saat ini</p>
                </div>
                
                <div class="flex gap-2">
                    <input 
                        v-model="searchQuery" 
                        @keyup.enter="search"
                        type="text" 
                        placeholder="Cari username, IP, MAC..." 
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">MAC (Calling Station)</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Uptime</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Trafik (DL / UL)</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in users.data" :key="user.radacctid" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ user.username }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ user.framedipaddress || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono text-xs">{{ user.callingstationid || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ formatUptime(user.acctsessiontime) }}
                                <div class="text-[10px] text-gray-400 mt-0.5">Sejak: {{ user.acctstarttime }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="text-green-600 font-medium">&darr; {{ formatBytes(user.acctoutputoctets) }}</span> / 
                                <span class="text-blue-600 font-medium">&uarr; {{ formatBytes(user.acctinputoctets) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button @click="disconnectUser(user.username)" class="text-red-600 hover:text-red-900 font-medium px-3 py-1 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                    Disconnect
                                </button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <p class="text-sm">Tidak ada pengguna online saat ini.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="mt-4 flex justify-between items-center" v-if="users.data.length > 0">
                <p class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium">{{ users.from }}</span> sampai <span class="font-medium">{{ users.to }}</span> dari <span class="font-medium">{{ users.total }}</span> pengguna
                </p>
                <div class="flex gap-1">
                    <Link 
                        v-for="(link, i) in users.links" 
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
    users: Object,
    filters: Object
});

const searchQuery = ref(props.filters.search || '');

function search() {
    router.get('/radius/online-users', { search: searchQuery.value }, { preserveState: true });
}

function refresh() {
    router.reload({ only: ['users'] });
}

function disconnectUser(username) {
    if (confirm(`Apakah Anda yakin ingin memutuskan koneksi pengguna "${username}"? (Perintah CoA akan dikirim ke Router)`)) {
        router.post('/radius/online-users/disconnect', { username }, {
            preserveScroll: true
        });
    }
}

function formatBytes(bytes) {
    if (bytes === 0 || !bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatUptime(seconds) {
    if (!seconds) return '0s';
    
    const d = Math.floor(seconds / (3600*24));
    const h = Math.floor(seconds % (3600*24) / 3600);
    const m = Math.floor(seconds % 3600 / 60);
    const s = Math.floor(seconds % 60);

    let res = [];
    if (d > 0) res.push(`${d}d`);
    if (h > 0) res.push(`${h}h`);
    if (m > 0) res.push(`${m}m`);
    if (s > 0 && d === 0) res.push(`${s}s`);
    
    return res.join(' ') || '0s';
}
</script>
