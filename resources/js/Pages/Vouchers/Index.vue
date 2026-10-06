<template>
    <component :is="layoutComponent" title="Data Voucher" subtitle="Kelola dan generate voucher hotspot">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Data Voucher</h1>
            <p class="text-sm text-gray-500">Kelola dan lihat semua voucher Anda</p>
        </div>
        <!-- HEADER -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari kode/username..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        @keyup.enter="doFilter"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select v-model="statusFilter" @change="doFilter" class="border-gray-300 rounded-lg">
                    <option value="">Semua Status</option>
                    <option value="available">Tersedia</option>
                    <option value="used">Digunakan</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button v-if="selectedVouchers.length > 0" @click="printSelected" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print ({{ selectedVouchers.length }})
                </button>
                <button v-if="selectedVouchers.length > 0" @click="bulkDelete" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus ({{ selectedVouchers.length }})
                </button>
                <button @click="openGenerateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Generate Voucher
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-10">
                                <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode / User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Terpakai Sejak</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aktif Sampai</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penggunaan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="voucher in vouchers.data" :key="voucher.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <input type="checkbox" v-model="selectedVouchers" :value="voucher.id" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900 font-mono">{{ voucher.code }}</div>
                                <div class="text-xs text-gray-500" v-if="voucher.username !== voucher.code">User: {{ voucher.username }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                {{ voucher.password }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ voucher.profile?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="voucher.status === 'available'" class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">TERSEDIA</span>
                                <span v-else-if="voucher.status === 'used'" class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">DIGUNAKAN</span>
                                <span v-else class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">EXPIRED</span>

                                <div class="mt-2 flex flex-col gap-1 items-start">
                                    <span v-if="!voucher.is_active" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-rose-100 text-rose-700 uppercase tracking-widest">
                                        Nonaktif
                                    </span>
                                    <span v-else-if="onlineUsernames.includes(voucher.username)" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Offline
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.first_login">
                                    {{ new Date(usageStats[voucher.username].first_login).toLocaleString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.first_login && voucher.profile?.duration">
                                    {{ calculateExpiration(usageStats[voucher.username].first_login, voucher.profile.duration) }}
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.total_time !== undefined">
                                    <span :class="usageStats[voucher.username].total_time > 0 ? 'text-indigo-600 font-bold' : ''">
                                        {{ formatSeconds(usageStats[voucher.username].total_time) }} 
                                    </span>
                                    <span class="text-gray-400 text-xs ml-1" v-if="voucher.profile?.duration">
                                        / {{ voucher.profile.duration }}
                                    </span>
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <button 
                                        @click="toggleStatus(voucher)" 
                                        :disabled="toggling === voucher.id"
                                        :class="voucher.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-emerald-600 hover:text-emerald-900'"
                                        class="font-semibold transition-colors disabled:opacity-50"
                                    >
                                        {{ voucher.is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button @click="deleteVoucher(voucher.id)" class="text-red-600 hover:text-red-900 font-semibold">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="vouchers.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-gray-500 text-base font-medium">Belum ada data voucher.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2" v-if="vouchers.links && vouchers.links.length > 3">
                <template v-for="(link, idx) in vouchers.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded text-sm" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"></Link>
                    <span v-else v-html="link.label" class="px-3 py-1 border rounded bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed text-sm"></span>
                </template>
            </div>
        </div>

        <!-- GENERATE MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">Generate Voucher Baru</h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Profil <span class="text-red-500">*</span></label>
                            <select v-model="form.voucher_profile_id" class="w-full border-gray-300 rounded-lg" required>
                                <option value="" disabled>-- Pilih Profil --</option>
                                <option v-for="prof in profiles" :key="prof.id" :value="prof.id">{{ prof.name }} (Rp{{ prof.price }})</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Jumlah Generate <span class="text-red-500">*</span></label>
                            <input v-model="form.amount" type="number" min="1" max="500" class="w-full border-gray-300 rounded-lg" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Panjang Karakter <span class="text-red-500">*</span></label>
                            <input v-model="form.length" type="number" min="4" max="12" class="w-full border-gray-300 rounded-lg" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Prefix (Opsional)</label>
                            <input v-model="form.prefix" type="text" maxlength="4" placeholder="Contoh: VC" class="w-full border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tipe Login <span class="text-red-500">*</span></label>
                            <select v-model="form.type" class="w-full border-gray-300 rounded-lg" required>
                                <option value="up">Username & Password Berbeda</option>
                                <option value="vc">Satu Kode (User = Password)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Generate Untuk Reseller (Opsional)</label>
                            <select v-model="form.reseller_id" class="w-full border-gray-300 rounded-lg">
                                <option value="">-- System (Tanpa Potong Saldo) --</option>
                                <option v-for="reseller in resellers" :key="reseller.id" :value="reseller.id">
                                    {{ reseller.customer.name }} (Saldo: Rp{{ reseller.balance.toLocaleString('id-ID') }})
                                </option>
                            </select>
                            <div v-if="form.errors.reseller_id" class="text-red-500 text-xs mt-1">{{ form.errors.reseller_id }}</div>
                        </div>
                        
                        <button type="submit" class="hidden" id="submitBtn"></button>
                    </form>
                </div>

                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                    <button type="button" @click="$el.querySelector('#submitBtn').click()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700" :disabled="form.processing">
                        {{ form.processing ? 'Generating...' : 'Generate' }}
                    </button>
                </div>
            </div>
        </div>

    </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';

const page = usePage();
const layoutComponent = computed(() => {
    return page.props.auth?.user?.role === 'reseller' || page.props.auth?.user?.role === 'customer' 
        ? ClientAreaLayout 
        : AppLayout;
});

const props = defineProps({
    vouchers: Object,
    profiles: Array,
    resellers: Array,
    filters: Object,
    onlineUsernames: Array,
    usageStats: Object,
});

const formatSeconds = (seconds) => {
    if (!seconds) return '0 mnt';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h} jam ${m} mnt`;
    return `${m} mnt`;
};

const parseDurationToSeconds = (duration) => {
    if (!duration) return 0;
    const str = duration.toString().toLowerCase().trim();
    if (/^\d+$/.test(str)) return parseInt(str);
    
    let seconds = 0;
    const regex = /(\d+)\s*([wdhms])/g;
    let match;
    const units = { w: 604800, d: 86400, h: 3600, m: 60, s: 1 };
    
    while ((match = regex.exec(str)) !== null) {
        seconds += parseInt(match[1]) * units[match[2]];
    }
    return seconds;
};

const calculateExpiration = (firstLoginIso, durationStr) => {
    if (!firstLoginIso || !durationStr) return '-';
    const seconds = parseDurationToSeconds(durationStr);
    if (!seconds) return '-';
    
    const date = new Date(firstLoginIso);
    date.setSeconds(date.getSeconds() + seconds);
    
    return date.toLocaleString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const showModal = ref(false);
const selectedVouchers = ref([]);
const toggling = ref(null);

const toggleStatus = (voucher) => {
    const action = voucher.is_active ? 'Nonaktifkan' : 'Aktifkan';
    if (!confirm(`${action} voucher ${voucher.code}?`)) return;
    toggling.value = voucher.id;
    router.post(`/vouchers/${voucher.id}/toggle-status`, {}, {
        preserveScroll: true,
        onFinish: () => (toggling.value = null),
    });
};

const form = useForm({
    voucher_profile_id: '',
    amount: 10,
    length: 6,
    prefix: '',
    type: 'vc',
    reseller_id: '',
});

const doFilter = () => {
    router.get(route('vouchers.index'), { search: search.value, status: statusFilter.value }, { preserveState: true, replace: true });
};

const openGenerateModal = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = () => {
    form.post(route('vouchers.store'), {
        onSuccess: () => {
            closeModal();
            Swal.fire({ icon: 'success', title: 'Berhasil', text: form.amount + ' Voucher berhasil digenerate.' });
        }
    });
};

const deleteVoucher = (id) => {
    Swal.fire({
        title: 'Hapus Voucher?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.destroy.post', id), {}, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const bulkDelete = () => {
    Swal.fire({
        title: 'Hapus ' + selectedVouchers.value.length + ' Voucher?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.bulk-destroy'), { ids: selectedVouchers.value }, {
                onSuccess: () => {
                    selectedVouchers.value = [];
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const printSelected = () => {
    if (selectedVouchers.value.length === 0) return;
    
    // Convert array of IDs to query string parameters: ids[]=1&ids[]=2
    const queryParams = new URLSearchParams();
    selectedVouchers.value.forEach(id => {
        queryParams.append('ids[]', id);
    });
    
    const url = route('vouchers.print') + '?' + queryParams.toString();
    window.open(url, '_blank');
};

const isAllSelected = computed(() => {
    return props.vouchers.data.length > 0 && selectedVouchers.value.length === props.vouchers.data.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedVouchers.value = props.vouchers.data.map(v => v.id);
    } else {
        selectedVouchers.value = [];
    }
};
</script>
