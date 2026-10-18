<template>
    <AppLayout title="Pengaturan Reseller" subtitle="Kelola hak akses profil voucher untuk reseller">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-amber-50 text-amber-600 rounded-2xl text-xl">🏷️</span>
                        <span>Pengaturan Reseller &amp; Hak Akses Voucher</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola pembatasan profil voucher hotspot yang diizinkan untuk masing-masing reseller</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Reseller</span>
                        <span class="p-2 bg-amber-50 text-amber-600 rounded-xl text-sm font-bold">🤝</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 mt-2 font-mono">{{ stats?.total_resellers || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Akun mitra reseller aktif</div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-600">Akses Penuh (Default)</span>
                        <span class="p-2 bg-sky-50 text-sky-600 rounded-xl text-sm font-bold">🌐</span>
                    </div>
                    <div class="text-2xl font-black text-sky-700 mt-2 font-mono">{{ stats?.full_access_count || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Bisa menjual semua profil</div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Akses Dibatasi</span>
                        <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl text-sm font-bold">🔒</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-2 font-mono">{{ stats?.custom_access_count || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Dibatasi profil tertentu</div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Total Profil Voucher</span>
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl text-sm font-bold">🎫</span>
                    </div>
                    <div class="text-2xl font-black text-indigo-700 mt-2 font-mono">{{ stats?.total_profiles || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Paket voucher hotspot berbayar</div>
                </div>
            </div>

            <!-- Toolbar: Role Filter Tabs & Search -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-200/80 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Role Filter Tabs -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button 
                            type="button" 
                            @click="setRole('reseller')" 
                            class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="currentRole === 'reseller' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            <span>🟠</span>
                            <span>Hanya Reseller</span>
                            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px]" :class="currentRole === 'reseller' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
                                {{ stats?.total_resellers || 0 }}
                            </span>
                        </button>

                        <button 
                            type="button" 
                            @click="setRole('all')" 
                            class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="currentRole === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            <span>👥</span>
                            <span>Semua Pengguna</span>
                        </button>

                        <button 
                            type="button" 
                            @click="setRole('customer')" 
                            class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="currentRole === 'customer' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            <span>🏠</span>
                            <span>Pelanggan</span>
                        </button>

                        <button 
                            type="button" 
                            @click="setRole('staff')" 
                            class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="currentRole === 'staff' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            <span>🛠️</span>
                            <span>Staff / Admin</span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full lg:w-80">
                        <input 
                            v-model="search" 
                            @input="onSearch"
                            type="text" 
                            placeholder="Cari nama, email, no HP..." 
                            class="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium text-slate-800"
                        >
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <button 
                            v-if="search" 
                            @click="clearSearch" 
                            class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Info banner saat filter Reseller aktif -->
                <div v-if="currentRole === 'reseller'" class="p-3 bg-amber-50/60 border border-amber-200/60 rounded-2xl flex items-center justify-between text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <span>💡</span>
                        <span>Menampilkan mitra <strong>Reseller</strong>. Gunakan tombol <strong>Atur Akses</strong> untuk membatasi profil voucher yang boleh dijual oleh masing-masing reseller.</span>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Pengguna / Reseller -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">User / Reseller</th>
                                <th class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Role &amp; Status</th>
                                <th class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Akses Profil Voucher</th>
                                <th class="px-6 py-4 text-right text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/60 transition-colors">
                                <!-- Kolom User -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-black shadow-sm" :class="getUserAvatarClass(user.role)">
                                            {{ getUserInitial(user.name) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                                <span>{{ user.name }}</span>
                                                <span v-if="user.reseller" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                                                    Mitra Reseller
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                                                <span>{{ user.email }}</span>
                                                <span v-if="user.phone" class="text-slate-400">• {{ user.phone }}</span>
                                            </div>
                                            <div v-if="user.customer" class="text-[11px] text-indigo-600 font-semibold mt-0.5 flex items-center gap-1">
                                                <span>Pelanggan: {{ user.customer.name }} ({{ user.customer.customer_code }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom Role -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-[11px] font-black rounded-full uppercase tracking-wider border shadow-xs" :class="getRoleBadgeClass(user.role)">
                                        {{ user.role || 'User' }}
                                    </span>
                                </td>

                                <!-- Kolom Hak Akses Profil -->
                                <td class="px-6 py-4">
                                    <!-- Mode Akses Penuh (Default) -->
                                    <div v-if="!user.voucher_profiles || user.voucher_profiles.length === 0">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            <span>🌐 Semua Profil (Bebas / Default)</span>
                                        </span>
                                        <p class="text-[11px] text-slate-400 mt-1 italic">Dapat menjual seluruh paket voucher aktif</p>
                                    </div>

                                    <!-- Mode Dibatasi ke Profil Tertentu -->
                                    <div v-else class="space-y-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                🔒 Dibatasi ({{ user.voucher_profiles.length }} Profil)
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 max-w-lg">
                                            <span 
                                                v-for="profile in user.voucher_profiles" 
                                                :key="profile.id" 
                                                class="px-2 py-0.5 text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg border border-slate-200 transition-colors"
                                            >
                                                {{ profile.name }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold">
                                    <button 
                                        @click="openSettingModal(user)" 
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 hover:border-transparent transition-all shadow-xs cursor-pointer active:scale-95"
                                    >
                                        <span>⚙️</span>
                                        <span>Atur Akses</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Baris Kosong -->
                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="4" class="px-6 py-16 text-center text-slate-500 font-medium">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-xl mx-auto mb-3">🔍</div>
                                    <div class="text-sm font-bold text-slate-800">Tidak ada pengguna yang cocok</div>
                                    <div class="text-xs text-slate-400 mt-0.5">Silakan ganti kata kunci pencarian atau ubah tab filter role.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Menampilkan <strong class="text-slate-800">{{ users.from || 0 }}</strong> - <strong class="text-slate-800">{{ users.to || 0 }}</strong> dari <strong class="text-slate-800">{{ users.total || 0 }}</strong> data
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="link in users.links" :key="link.label">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl text-xs font-bold transition-all" 
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-200'"
                            ></Link>
                            <span 
                                v-else 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl text-xs text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed"
                            ></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Atur Akses Profil Voucher -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[90vh] border border-slate-100">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl text-lg">🔑</span>
                            <div>
                                <h2 class="text-base font-black text-slate-900">Atur Akses Profil Voucher</h2>
                                <p class="text-[11px] text-slate-500">Tentukan profil voucher yang diizinkan untuk akun ini</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
                            ✕
                        </button>
                    </div>
                    
                    <!-- Info Pengguna yang Diedit -->
                    <div class="px-6 py-3.5 bg-indigo-50/70 border-b border-indigo-100/60 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-extrabold text-indigo-950">{{ selectedUser?.name }}</div>
                            <div class="text-xs text-indigo-700 mt-0.5">{{ selectedUser?.email }}</div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200">
                            {{ selectedUser?.role }}
                        </span>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto flex-1 space-y-4">
                        <!-- Quick Action & Mode Explainer -->
                        <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100">
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Pilihan Profil Voucher:
                            </div>
                            <div class="flex items-center gap-2">
                                <button 
                                    type="button" 
                                    @click="selectAllProfiles" 
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline cursor-pointer"
                                >
                                    Pilih Semua
                                </button>
                                <span class="text-slate-300">•</span>
                                <button 
                                    type="button" 
                                    @click="clearAllProfiles" 
                                    class="text-xs font-bold text-rose-600 hover:text-rose-800 underline cursor-pointer"
                                >
                                    Bebaskan (Semua Profil)
                                </button>
                            </div>
                        </div>

                        <!-- Status Alert Box -->
                        <div v-if="form.voucher_profile_ids.length === 0" class="p-3.5 rounded-2xl bg-sky-50 border border-sky-200 text-xs text-sky-900 leading-relaxed">
                            <strong class="font-extrabold text-sky-950 block mb-0.5">🌐 Mode Akses Penuh (Default)</strong>
                            Tidak ada profil yang dibatasi. Reseller ini diizinkan menjual <strong>seluruh profil voucher hotspot aktif</strong>. Centang profil di bawah jika ingin membatasi akses.
                        </div>
                        <div v-else class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                            <strong class="font-extrabold text-amber-950 block mb-0.5">🔒 Mode Akses Dibatasi</strong>
                            Reseller ini <strong>hanya diizinkan</strong> menjual <strong>{{ form.voucher_profile_ids.length }}</strong> profil voucher yang Anda centang di bawah.
                        </div>

                        <!-- Daftar Profil Voucher Checkbox -->
                        <div class="space-y-2.5 pt-1">
                            <label 
                                v-for="profile in sellableProfiles" 
                                :key="profile.id" 
                                class="flex items-start p-3.5 border-2 rounded-2xl cursor-pointer transition-all"
                                :class="form.voucher_profile_ids.includes(profile.id) ? 'border-indigo-600 bg-indigo-50/40 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                            >
                                <div class="flex items-center h-5 mt-0.5">
                                    <input 
                                        type="checkbox" 
                                        :value="profile.id" 
                                        v-model="form.voucher_profile_ids" 
                                        class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer"
                                    >
                                </div>
                                <div class="ml-3.5 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="block text-sm font-extrabold text-slate-900">{{ profile.name }}</span>
                                        <span class="text-xs font-black text-indigo-700 font-mono">Rp {{ formatRupiah(profile.price) }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2 flex-wrap">
                                        <span v-if="profile.duration">Durasi: <strong>{{ profile.duration }}</strong></span>
                                        <span v-if="profile.limit_rate">• Speed: <strong>{{ profile.limit_rate }}</strong></span>
                                        <span v-if="profile.fee_reseller" class="text-emerald-700 font-bold">• Komisi Reseller: Rp {{ formatRupiah(profile.fee_reseller) }}</span>
                                    </div>
                                </div>
                            </label>

                            <div v-if="sellableProfiles.length === 0" class="text-center py-6 text-xs text-slate-400 italic">
                                Belum ada profil voucher yang tersedia di sistem.
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-slate-500">
                            {{ form.voucher_profile_ids.length > 0 ? form.voucher_profile_ids.length + ' profil dipilih' : 'Semua profil diizinkan' }}
                        </span>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="showModal = false" 
                                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-extrabold text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="saveSettings" 
                                class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-extrabold hover:bg-indigo-700 transition-all shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-50" 
                                :disabled="form.processing"
                            >
                                <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const props = defineProps({
    users: Object,
    voucherProfiles: Array,
    stats: Object,
    filters: Object,
});

const sellableProfiles = computed(() => {
    return (props.voucherProfiles || []).filter(p => !p.is_customer_free);
});

const currentRole = ref(props.filters?.role || 'reseller');
const search = ref(props.filters?.search || '');
const showModal = ref(false);
const selectedUser = ref(null);

const form = useForm({
    voucher_profile_ids: []
});

const onSearch = debounce(() => {
    applyFilters();
}, 300);

const clearSearch = () => {
    search.value = '';
    applyFilters();
};

const setRole = (role) => {
    currentRole.value = role;
    applyFilters();
};

const applyFilters = () => {
    router.get(
        route('settings.resellers.index'), 
        { search: search.value, role: currentRole.value }, 
        { preserveState: true, replace: true }
    );
};

const openSettingModal = (user) => {
    selectedUser.value = user;
    form.voucher_profile_ids = user.voucher_profiles ? user.voucher_profiles.map(p => p.id) : [];
    showModal.value = true;
};

const selectAllProfiles = () => {
    form.voucher_profile_ids = sellableProfiles.value.map(p => p.id);
};

const clearAllProfiles = () => {
    form.voucher_profile_ids = [];
};

const saveSettings = () => {
    if (!selectedUser.value) return;
    
    form.post(route('settings.resellers.update.post', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: `Akses profil voucher untuk ${selectedUser.value.name} telah diperbarui.`,
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Gagal menyimpan pengaturan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstErr,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

const formatRupiah = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const getUserInitial = (name) => {
    return (name || 'U').charAt(0).toUpperCase();
};

const getUserAvatarClass = (role) => {
    const r = (role || '').toLowerCase();
    if (r === 'reseller') return 'bg-amber-100 text-amber-800 border border-amber-300';
    if (r === 'admin' || r === 'superadmin') return 'bg-purple-100 text-purple-800 border border-purple-300';
    if (r === 'teknisi') return 'bg-cyan-100 text-cyan-800 border border-cyan-300';
    if (r === 'cs') return 'bg-blue-100 text-blue-800 border border-blue-300';
    return 'bg-slate-100 text-slate-700 border border-slate-300';
};

const getRoleBadgeClass = (role) => {
    const r = (role || '').toLowerCase();
    if (r === 'reseller') return 'bg-amber-50 text-amber-800 border-amber-300';
    if (r === 'admin' || r === 'superadmin') return 'bg-purple-50 text-purple-800 border-purple-300';
    if (r === 'teknisi') return 'bg-cyan-50 text-cyan-800 border-cyan-300';
    if (r === 'cs') return 'bg-blue-50 text-blue-800 border-blue-300';
    return 'bg-slate-50 text-slate-700 border-slate-300';
};
</script>
