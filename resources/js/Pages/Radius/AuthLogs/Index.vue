<template>
    <AppLayout title="Log Autentikasi (RADIUS)">
        <div class="p-6">
            <!-- Header & Toolbar -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <span>Log Autentikasi (Radius)</span>
                        <span v-if="logs.total !== undefined" class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold border border-slate-200">
                            {{ logs.total }} Data
                        </span>
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Pantau riwayat login pelanggan, sukses maupun gagal</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Tombol Hapus Terpilih (Muncul jika ada item dicentang) -->
                    <button 
                        v-if="selectedIds.length > 0 && canDelete"
                        @click="deleteSelected" 
                        class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold shadow-sm flex items-center gap-1.5 transition-all animate-pulse"
                        title="Hapus baris log yang dicentang"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih ({{ selectedIds.length }})</span>
                    </button>

                    <!-- Filter Status -->
                    <select v-model="statusFilter" @change="search" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                        <option value="">Semua Status</option>
                        <option value="success">Sukses (Accept)</option>
                        <option value="failed">Gagal (Reject)</option>
                    </select>

                    <!-- Search Input -->
                    <div class="relative">
                        <input 
                            v-model="searchQuery" 
                            @keyup.enter="search"
                            type="text" 
                            placeholder="Cari username atau alasan..." 
                            class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56 md:w-64 shadow-2xs"
                        />
                        <button 
                            v-if="searchQuery" 
                            @click="searchQuery = ''; search()" 
                            class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                            title="Hapus pencarian"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <button @click="search" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 shadow-2xs transition-all">
                        Cari
                    </button>

                    <button @click="refresh" class="px-3.5 py-2 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-sm font-semibold hover:bg-indigo-100 shadow-2xs flex items-center gap-1.5 transition-all" title="Segarkan data">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Refresh</span>
                    </button>

                    <!-- Tombol Bersihkan / Hapus Semua Log -->
                    <button 
                        v-if="canDelete"
                        @click="openClearModal" 
                        class="px-3.5 py-2 bg-rose-50 text-rose-700 border border-rose-200 rounded-lg text-sm font-semibold hover:bg-rose-100 shadow-2xs flex items-center gap-1.5 transition-all"
                        title="Opsi bersihkan riwayat log autentikasi"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Bersihkan Log</span>
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[750px]">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <!-- Checkbox Select All -->
                                <th v-if="canDelete" class="w-12 px-4 py-3.5 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        @change="toggleSelectAll"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer"
                                        title="Pilih Semua di Halaman Ini"
                                    />
                                </th>
                                <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Password Diajukan</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Pesan (Reply)</th>
                                <th v-if="canDelete" class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr 
                                v-for="log in logs.data" 
                                :key="log.id" 
                                :class="[
                                    'transition-colors',
                                    selectedIds.includes(log.id) ? 'bg-rose-50/40' : 'hover:bg-gray-50/60'
                                ]"
                            >
                                <!-- Checkbox Row -->
                                <td v-if="canDelete" class="w-12 px-4 py-3.5 text-center">
                                    <input 
                                        type="checkbox" 
                                        :value="log.id" 
                                        v-model="selectedIds"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer"
                                    />
                                </td>
                                
                                <td class="px-5 py-3.5 text-sm text-gray-600 whitespace-nowrap">
                                    <div class="font-medium text-gray-800">{{ formatDateTime(log.authdate) }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ formatTimeDiff(log.authdate) }}</div>
                                </td>

                                <td class="px-5 py-3.5 text-sm font-semibold text-gray-900">
                                    <span class="font-mono bg-slate-50 border border-slate-200/60 px-2 py-0.5 rounded text-xs text-slate-700">
                                        {{ log.username }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-sm text-gray-500 font-mono text-xs">
                                    {{ log.pass || '-' }}
                                </td>

                                <td class="px-5 py-3.5 text-sm">
                                    <span 
                                        v-if="log.reply === 'Access-Accept'" 
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-semibold"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Sukses
                                    </span>
                                    <span 
                                        v-else 
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-xs font-semibold"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Gagal
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-sm text-gray-600 font-mono text-xs">
                                    {{ log.reply }}
                                </td>

                                <!-- Aksi Hapus Satuan -->
                                <td v-if="canDelete" class="px-4 py-3.5 text-sm text-center">
                                    <button 
                                        @click="deleteSingle(log)" 
                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 rounded-lg transition-all shadow-2xs"
                                        title="Hapus baris log ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="logs.data.length === 0">
                                <td :colspan="canDelete ? 7 : 5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-sm font-medium">Belum ada riwayat autentikasi tercatat.</p>
                                    <p class="text-xs text-gray-400 mt-1">Log autentikasi RADIUS akan otomatis muncul saat perangkat/klien mencoba login PPPoE / Hotspot.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3" v-if="logs.data.length > 0">
                <p class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium text-gray-700">{{ logs.from }}</span> sampai <span class="font-medium text-gray-700">{{ logs.to }}</span> dari <span class="font-medium text-gray-700">{{ logs.total }}</span> log
                </p>
                <div class="flex gap-1 flex-wrap">
                    <Link 
                        v-for="(link, i) in logs.links" 
                        :key="i"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 text-sm rounded-lg transition-colors',
                            link.active ? 'bg-indigo-600 text-white font-medium shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200',
                            !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>

            <!-- Modal Bersihkan Log -->
            <div v-if="showClearModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 transform transition-all animate-fade-in">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Bersihkan Riwayat Log</h3>
                                <p class="text-xs text-gray-500">Pilih kriteria data log yang ingin dihapus</p>
                            </div>
                        </div>
                        <button @click="showClearModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Pilihan Mode Pembersihan -->
                    <div class="mt-4 space-y-2.5">
                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === 'failed' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-gray-200 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="failed" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-gray-900">Hanya Log Gagal (Access-Reject)</div>
                                <div class="text-[11px] text-gray-500">Hapus semua kegagalan autentikasi (login ditolak/password salah). Log sukses tetap dipertahankan.</div>
                            </div>
                        </label>

                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === '7_days' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-gray-200 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="7_days" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-gray-900">Log Lebih Dari 7 Hari Lalu</div>
                                <div class="text-[11px] text-gray-500">Hapus log lama yang usianya sudah lebih dari 1 minggu.</div>
                            </div>
                        </label>

                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === '30_days' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-gray-200 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="30_days" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-gray-900">Log Lebih Dari 30 Hari Lalu</div>
                                <div class="text-[11px] text-gray-500">Hapus log lama yang usianya sudah lebih dari 1 bulan.</div>
                            </div>
                        </label>

                        <label 
                            v-if="searchQuery || statusFilter"
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === 'filtered' ? 'border-rose-400 bg-rose-50/50 ring-1 ring-rose-400' : 'border-gray-200 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="filtered" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-gray-900">Sesuai Filter & Pencarian Saat Ini</div>
                                <div class="text-[11px] text-gray-500">
                                    Hanya hapus data yang cocok dengan pencarian "{{ searchQuery }}" dan status "{{ statusFilter || 'semua' }}".
                                </div>
                            </div>
                        </label>

                        <label 
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                clearMode === 'all' ? 'border-rose-500 bg-rose-50/70 ring-1 ring-rose-500' : 'border-gray-200 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="all" v-model="clearMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <div class="text-xs font-bold text-rose-700">Hapus SEMUA Riwayat Log</div>
                                <div class="text-[11px] text-rose-600/80">Kosongkan seluruh data tabel riwayat login RADIUS secara permanen.</div>
                            </div>
                        </label>
                    </div>

                    <!-- Konfirmasi teks jika mode ALL -->
                    <div v-if="clearMode === 'all'" class="mt-3.5 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                        <p class="font-semibold mb-1">Konfirmasi Keamanan:</p>
                        <p class="mb-2">Ketik <span class="font-mono font-bold text-rose-600 bg-white px-1.5 py-0.5 rounded border border-amber-300">HAPUS</span> di bawah ini untuk konfirmasi pengosongan seluruh log:</p>
                        <input 
                            v-model="confirmInput" 
                            type="text" 
                            placeholder="Ketik HAPUS" 
                            class="w-full px-3 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-semibold uppercase tracking-wider focus:ring-2 focus:ring-rose-500"
                        />
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-5 flex justify-end gap-2.5">
                        <button 
                            type="button" 
                            @click="showClearModal = false"
                            class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="button" 
                            @click="executeClear"
                            :disabled="isClearing || (clearMode === 'all' && confirmInput.trim().toUpperCase() !== 'HAPUS')"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-xs flex items-center gap-1.5',
                                (clearMode === 'all' && confirmInput.trim().toUpperCase() !== 'HAPUS') || isClearing
                                    ? 'bg-rose-300 cursor-not-allowed'
                                    : 'bg-rose-600 hover:bg-rose-700'
                            ]"
                        >
                            <svg v-if="isClearing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>{{ isClearing ? 'Membersihkan...' : 'Konfirmasi Bersihkan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    logs: Object,
    filters: Object
});

const { can, hasRole } = usePermission();
const canDelete = computed(() => {
    return hasRole(['admin', 'super admin', 'superadmin']) || can('radius_auth_logs_delete') || can('menu_radius_auth_logs');
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const selectedIds = ref([]);

// State Modal Bersihkan Log
const showClearModal = ref(false);
const clearMode = ref('failed');
const confirmInput = ref('');
const isClearing = ref(false);

const isAllSelected = computed(() => {
    if (!props.logs?.data || props.logs.data.length === 0) return false;
    return props.logs.data.every(log => selectedIds.value.includes(log.id));
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.logs.data.map(log => log.id);
    }
}

function search() {
    selectedIds.value = [];
    router.get('/radius/auth-logs', { 
        search: searchQuery.value,
        status: statusFilter.value 
    }, { preserveState: true });
}

function refresh() {
    selectedIds.value = [];
    router.reload({ only: ['logs'] });
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).format(d);
    } catch {
        return dateStr;
    }
}

function formatTimeDiff(dateStr) {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        const now = new Date();
        const diffMs = now - d;
        const diffSec = Math.floor(diffMs / 1000);
        if (diffSec < 60) return `${diffSec} dtk lalu`;
        const diffMin = Math.floor(diffSec / 60);
        if (diffMin < 60) return `${diffMin} mnt lalu`;
        const diffHour = Math.floor(diffMin / 60);
        if (diffHour < 24) return `${diffHour} jam lalu`;
        const diffDay = Math.floor(diffHour / 24);
        return `${diffDay} hr lalu`;
    } catch {
        return '';
    }
}

// ── Aksi Hapus Satuan ──────────────────────────────────────────
function deleteSingle(log) {
    Swal.fire({
        title: 'Hapus Log Autentikasi?',
        html: `Apakah Anda yakin ingin menghapus log autentikasi untuk username <b>${log.username}</b> (${log.reply})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/radius/auth-logs/${log.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = selectedIds.value.filter(id => id !== log.id);
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Log autentikasi berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Gagal menghapus log.', 'error');
                }
            });
        }
    });
}

// ── Aksi Hapus Terpilih (Bulk Delete) ───────────────────────────
function deleteSelected() {
    if (selectedIds.value.length === 0) return;

    Swal.fire({
        title: `Hapus ${selectedIds.value.length} Log Terpilih?`,
        text: 'Data log autentikasi yang dipilih akan dihapus secara permanen dari database.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Ya, Hapus (${selectedIds.value.length})!`,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/radius/auth-logs/bulk-delete', {
                ids: selectedIds.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = [];
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Log autentikasi terpilih berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Gagal menghapus log terpilih.', 'error');
                }
            });
        }
    });
}

// ── Aksi Modal Bersihkan Log ───────────────────────────────────
function openClearModal() {
    confirmInput.value = '';
    showClearModal.value = true;
}

function executeClear() {
    if (clearMode.value === 'all' && confirmInput.value.trim().toUpperCase() !== 'HAPUS') {
        return;
    }

    isClearing.value = true;
    router.post('/radius/auth-logs/bulk-delete', {
        all: true,
        mode: clearMode.value,
        search: searchQuery.value,
        status: statusFilter.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showClearModal.value = false;
            selectedIds.value = [];
            confirmInput.value = '';
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Pembersihan riwayat log autentikasi telah selesai.',
                timer: 1800,
                showConfirmButton: false
            });
        },
        onError: (err) => {
            Swal.fire('Gagal!', err?.message || 'Gagal membersihkan log.', 'error');
        },
        onFinish: () => {
            isClearing.value = false;
        }
    });
}
</script>
