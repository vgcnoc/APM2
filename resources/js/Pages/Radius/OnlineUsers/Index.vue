<template>
    <AppLayout title="Pengguna Online (FreeRADIUS)">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans px-4 sm:px-6 lg:px-8 py-6">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">✅</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <button @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-700 text-xs">✕</button>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚠️</span>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <button @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-700 text-xs">✕</button>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2.5 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                        </span>
                        <span>Pengguna Online (FreeRADIUS)</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Monitoring sesi aktif pelanggan PPPoE dan voucher Hotspot, alokasi IP, MAC address, durasi uptime, serta kontrol pemutusan sesi (CoA / Disconnect).
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Auto-refresh interval dropdown -->
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-2xs gap-2">
                        <span class="text-[11px] font-bold text-slate-500 hidden sm:inline">Auto Refresh:</span>
                        <select 
                            v-model="autoRefreshInterval" 
                            @change="handleAutoRefreshChange"
                            class="text-xs font-extrabold text-slate-800 bg-transparent border-0 p-0 focus:ring-0 cursor-pointer"
                        >
                            <option :value="0">Mati (Manual)</option>
                            <option :value="15">Tiap 15 Detik</option>
                            <option :value="30">Tiap 30 Detik</option>
                            <option :value="60">Tiap 1 Menit</option>
                        </select>
                    </div>

                    <!-- Tombol Manual Refresh -->
                    <button 
                        @click="refresh" 
                        :disabled="isRefreshing"
                        class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 rounded-xl text-xs font-extrabold transition-all flex items-center gap-2 disabled:opacity-60 cursor-pointer shadow-2xs"
                        title="Perbarui daftar pengguna online sekarang"
                    >
                        <svg 
                            class="w-4 h-4 text-indigo-600" 
                            :class="{ 'animate-spin': isRefreshing }"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ isRefreshing ? 'Memperbarui...' : 'Refresh' }}</span>
                    </button>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Sesi Online -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Total Pengguna Online</span>
                        <div class="text-2xl font-black text-emerald-700 mt-1 font-mono">
                            {{ stats?.total_online || users.total || 0 }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Sesi aktif terhubung internet</div>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Total Download -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Total Trafik Download</span>
                        <div class="text-2xl font-black text-indigo-700 mt-1 font-mono">
                            {{ formatBytes(stats?.total_download || 0) }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Akumulasi sesi berjalan (&darr;)</div>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>
                </div>

                <!-- Total Upload -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-600">Total Trafik Upload</span>
                        <div class="text-2xl font-black text-sky-700 mt-1 font-mono">
                            {{ formatBytes(stats?.total_upload || 0) }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Akumulasi sesi berjalan (&uarr;)</div>
                    </div>
                    <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    </div>
                </div>

                <!-- Router Aktif -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-600">Router / NAS Melayani</span>
                        <div class="text-2xl font-black text-purple-700 mt-1 font-mono">
                            {{ stats?.active_routers || 0 }}
                            <span class="text-xs font-normal text-slate-400">/ {{ nases?.length || 0 }}</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Router dengan sesi aktif</div>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Toolbar (Search & Router Filter) -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-200/80 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <!-- Search Input with Debounce -->
                    <div class="relative w-full sm:w-80">
                        <input 
                            v-model="searchQuery" 
                            @input="onSearchInput"
                            type="text" 
                            placeholder="Cari username, IP, MAC address..." 
                            class="w-full pl-9 pr-8 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium text-slate-800"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button 
                            v-if="searchQuery" 
                            @click="clearSearch" 
                            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold"
                            title="Reset pencarian"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Filter Router NAS Dropdown -->
                    <div class="w-full sm:w-64">
                        <select 
                            v-model="filterNas" 
                            @change="applyFilter" 
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 font-medium focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all"
                        >
                            <option value="">Semua Router (NAS)</option>
                            <option v-for="n in nases" :key="n.id" :value="n.vpn_ip || n.nasname">
                                🖥️ {{ n.shortname || n.nasname }} ({{ n.vpn_ip || n.nasname }})
                            </option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-slate-500 font-medium flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>Monitoring Real-time via FreeRADIUS RadAcct</span>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80">
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Pengguna / Akun</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Alamat IP &amp; Router NAS</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">MAC (Calling Station)</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Durasi Uptime</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Trafik Sesi (DL / UL)</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users.data" :key="user.radacctid" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Pengguna / Akun -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <!-- User Type Badge & Username -->
                                        <div class="flex items-center gap-2">
                                            <span 
                                                v-if="user.user_type === 'pppoe'" 
                                                class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200/60"
                                            >
                                                PPPoE
                                            </span>
                                            <span 
                                                v-else-if="user.user_type === 'voucher'" 
                                                class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-amber-50 text-amber-700 border border-amber-200/60"
                                            >
                                                Hotspot
                                            </span>
                                            <span 
                                                v-else 
                                                class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-slate-100 text-slate-600"
                                            >
                                                RADIUS
                                            </span>
                                            
                                            <span class="text-xs font-black font-mono text-slate-900 tracking-tight">{{ user.username }}</span>
                                        </div>

                                        <!-- Customer Detail Link if matched -->
                                        <div v-if="user.customer_name" class="flex items-center gap-1.5 text-xs">
                                            <Link 
                                                :href="`/customers/${user.customer_id}`" 
                                                class="font-bold text-slate-800 hover:text-indigo-600 hover:underline"
                                            >
                                                {{ user.customer_name }}
                                            </Link>
                                            <span v-if="user.package_name" class="text-[10px] text-slate-400 font-medium">
                                                ({{ user.package_name }})
                                            </span>
                                            <span v-if="user.area_name" class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">
                                                📍 {{ user.area_name }}
                                            </span>
                                        </div>

                                        <!-- Voucher Profile if matched -->
                                        <div v-else-if="user.profile_name" class="text-[11px] text-amber-700 font-medium">
                                            Profil: <strong>{{ user.profile_name }}</strong>
                                        </div>
                                    </div>
                                </td>

                                <!-- Alamat IP & Router NAS -->
                                <td class="px-6 py-4">
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-mono font-black text-indigo-700">
                                            {{ user.framedipaddress || '-' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1 font-mono">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            <span>{{ user.nas_name ? `${user.nas_name} (${user.nasipaddress})` : (user.nasipaddress || '-') }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- MAC (Calling Station) -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200/60">
                                        {{ user.callingstationid || '-' }}
                                    </span>
                                </td>

                                <!-- Durasi Uptime -->
                                <td class="px-6 py-4">
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-bold text-slate-900 font-mono">
                                            {{ formatUptime(user.live_session_time || user.acctsessiontime) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium" v-if="user.acctstarttime">
                                            Sejak: {{ formatDateTime(user.acctstarttime) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Trafik Sesi (DL / UL) -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-xs font-mono">
                                            <span class="inline-flex items-center gap-0.5 text-emerald-700 font-extrabold" title="Download">
                                                <span>&darr;</span>
                                                <span>{{ formatBytes(user.acctoutputoctets) }}</span>
                                            </span>
                                            <span class="text-slate-300">/</span>
                                            <span class="inline-flex items-center gap-0.5 text-sky-700 font-extrabold" title="Upload">
                                                <span>&uarr;</span>
                                                <span>{{ formatBytes(user.acctinputoctets) }}</span>
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Total: {{ formatBytes((user.acctoutputoctets || 0) + (user.acctinputoctets || 0)) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi Disconnect -->
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="disconnectUser(user)" 
                                        class="px-3 py-1.5 rounded-xl text-xs font-extrabold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 shadow-2xs transition-all flex items-center gap-1.5 ml-auto cursor-pointer active:scale-95"
                                        title="Kirim perintah CoA / Disconnect"
                                    >
                                        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <span>Disconnect</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty Row -->
                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mx-auto">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <div class="text-sm font-bold text-slate-800">Tidak ada pengguna online saat ini</div>
                                        <p class="text-xs text-slate-400">
                                            {{ searchQuery || filterNas ? 'Tidak ada sesi online yang sesuai dengan kriteria pencarian atau filter router.' : 'Belum ada sesi pelanggan PPPoE atau voucher Hotspot yang aktif.' }}
                                        </p>
                                        <button 
                                            v-if="searchQuery || filterNas" 
                                            @click="resetAllFilters" 
                                            class="mt-2 text-xs text-indigo-600 font-bold hover:underline"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500" v-if="users.total > 0">
                    <div>
                        Menampilkan <span class="font-bold text-slate-700">{{ users.from || 0 }}</span> - <span class="font-bold text-slate-700">{{ users.to || 0 }}</span> dari <span class="font-bold text-slate-700">{{ users.total }}</span> Sesi Online
                    </div>
                    <div class="flex items-center gap-1.5" v-if="users.links && users.links.length > 3">
                        <template v-for="(link, idx) in users.links" :key="idx">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl text-xs font-bold transition-colors" 
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                preserve-state
                            />
                            <span 
                                v-else 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl bg-slate-50 text-slate-400 border-slate-200 text-xs cursor-not-allowed"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    users: Object,
    stats: Object,
    nases: Array,
    filters: Object,
});

// Search & Filter State
const searchQuery = ref(props.filters?.search || '');
const filterNas = ref(props.filters?.nas_ip || '');
let searchTimer = null;

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilter();
    }, 350);
}

function clearSearch() {
    searchQuery.value = '';
    applyFilter();
}

function resetAllFilters() {
    searchQuery.value = '';
    filterNas.value = '';
    applyFilter();
}

function applyFilter() {
    router.get('/radius/online-users', {
        search: searchQuery.value || undefined,
        nas_ip: filterNas.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

// Refresh & Auto-refresh State
const isRefreshing = ref(false);
const autoRefreshInterval = ref(0);
let autoRefreshTimer = null;

function refresh() {
    isRefreshing.value = true;
    router.reload({
        only: ['users', 'stats'],
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
}

function handleAutoRefreshChange() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }

    if (autoRefreshInterval.value > 0) {
        autoRefreshTimer = setInterval(() => {
            refresh();
        }, autoRefreshInterval.value * 1000);
    }
}

// Disconnect User with SweetAlert2
function disconnectUser(user) {
    const routerInfo = user.nas_name 
        ? `<div><span class="text-slate-400">Router:</span> <strong>${user.nas_name} (${user.nasipaddress || '-'})</strong></div>`
        : '';

    Swal.fire({
        title: `Putuskan Sesi ${user.username}?`,
        html: `
            <div class="text-xs text-slate-600 space-y-2">
                <p>Perintah <strong>CoA / Disconnect-Request</strong> akan dikirimkan ke Router MikroTik untuk memutuskan sesi internet pengguna ini.</p>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-left space-y-1 font-mono text-[11px]">
                    <div><span class="text-slate-400">Username:</span> <strong>${user.username}</strong></div>
                    ${user.customer_name ? `<div><span class="text-slate-400">Pelanggan:</span> <strong>${user.customer_name}</strong></div>` : ''}
                    <div><span class="text-slate-400">IP User:</span> <strong>${user.framedipaddress || '-'}</strong></div>
                    <div><span class="text-slate-400">MAC:</span> <strong>${user.callingstationid || '-'}</strong></div>
                    ${routerInfo}
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Putuskan Sesi',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl',
            confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
            cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/radius/online-users/disconnect', {
                username: user.username,
                nasipaddress: user.nasipaddress,
            }, {
                preserveScroll: true,
            });
        }
    });
}

// Helpers
function formatBytes(bytes) {
    if (bytes === 0 || !bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatUptime(seconds) {
    if (!seconds) return '0s';
    
    const d = Math.floor(seconds / (3600 * 24));
    const h = Math.floor((seconds % (3600 * 24)) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);

    const parts = [];
    if (d > 0) parts.push(`${d}h`);
    if (h > 0) parts.push(`${h}j`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 && d === 0) parts.push(`${s}d`);
    
    return parts.join(' ') || '0s';
}

function formatDateTime(val) {
    if (!val) return '-';
    try {
        const d = new Date(val);
        return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ', ' + d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    } catch {
        return val;
    }
}

onMounted(() => {
    handleAutoRefreshChange();
});

onUnmounted(() => {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
});
</script>
