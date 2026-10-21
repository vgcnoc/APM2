<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

// Custom debounce
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    resellers: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(
        route('resellers.index'),
        { search: value },
        { preserveState: true, replace: true }
    );
}, 300));

const showModal = ref(false);
const editingReseller = ref(null);

const form = useForm({
    balance: 0,
    is_active: true
});

const showCreateModal = ref(false);
const accountReseller = ref(null);
const createForm = useForm({
    email: '',
    password: ''
});

const openEditModal = (reseller) => {
    editingReseller.value = reseller;
    form.balance = reseller.balance || 0;
    form.is_active = reseller.is_active;
    showModal.value = true;
};

const openCreateAccountModal = (reseller) => {
    accountReseller.value = reseller;
    createForm.email = reseller.customer?.email || reseller.user?.email || '';
    createForm.password = '';
    showCreateModal.value = true;
};

const createAccount = () => {
    if (!accountReseller.value) return;
    
    createForm.post(route('resellers.create-account', accountReseller.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            Swal.fire(
                'Berhasil!',
                'Akun berhasil dibuat.',
                'success'
            );
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const showResetModal = ref(false);
const resetReseller = ref(null);
const resetForm = useForm({
    email: '',
    password: '',
});

const openResetModal = (reseller) => {
    resetReseller.value = reseller;
    resetForm.email = reseller.user ? reseller.user.email : '';
    resetForm.password = '';
    showResetModal.value = true;
};

const closeResetModal = () => {
    showResetModal.value = false;
    resetForm.reset();
    resetForm.clearErrors();
};

const resetPassword = () => {
    if (!resetReseller.value) return;
    
    resetForm.post(route('resellers.reset-password', resetReseller.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeResetModal();
            Swal.fire(
                'Berhasil!',
                'Data akun login berhasil diubah.',
                'success'
            );
        }
    });
};

const saveReseller = () => {
    if (editingReseller.value) {
        form.put(route('resellers.update', editingReseller.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire('Berhasil!', 'Data dompet Reseller berhasil diperbarui.', 'success');
            },
        });
    }
};

const deleteReseller = (reseller) => {
    Swal.fire({
        title: 'Cabut Akses Reseller?',
        text: `Pelanggan ini tidak akan lagi memiliki dompet reseller. Data master pelanggan tidak dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Cabut!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('resellers.destroy', reseller.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil!', 'Akses Reseller berhasil dicabut.', 'success');
                }
            });
        }
    });
};

// ── Floating Action Dropdown Logic ─────────────────────────
const activeDropdownRow = ref(null);
const dropdownPos = ref({ top: 0, left: 0 });

function toggleDropdown(event, reseller) {
    if (activeDropdownRow.value && activeDropdownRow.value.id === reseller.id) {
        activeDropdownRow.value = null;
        return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    const dropdownHeight = 350;
    const dropdownWidth = 288; // w-72 = 288px

    let top = rect.bottom + 6;
    if (top + dropdownHeight > window.innerHeight) {
        if (rect.top > dropdownHeight) {
            top = Math.max(10, rect.top - dropdownHeight - 6);
        } else {
            top = Math.max(10, window.innerHeight - dropdownHeight - 10);
        }
    }

    let left = rect.right - dropdownWidth;
    if (left < 10) {
        left = 10;
    }

    dropdownPos.value = { top, left };
    activeDropdownRow.value = reseller;
}

function closeDropdown() {
    activeDropdownRow.value = null;
}

function handleWindowChange() {
    if (activeDropdownRow.value) {
        activeDropdownRow.value = null;
    }
}

function getAvatarColor(name) {
    if (!name) return 'bg-blue-500';
    const colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-pink-500', 'bg-amber-500'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

onMounted(() => {
    window.addEventListener('scroll', handleWindowChange, { passive: true });
    window.addEventListener('resize', handleWindowChange);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleWindowChange);
    window.removeEventListener('resize', handleWindowChange);
});
</script>

<template>
    <Head title="Data Reseller Voucher" />

    <AppLayout>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Data Dompet Reseller</h2>
                    <p class="mt-1 text-sm text-gray-600">Kelola dompet dan saldo pelanggan yang menjadi mitra penjualan voucher.</p>
                </div>
                <Link href="/customers/booking" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Reseller dari Booking
                </Link>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                <div class="relative w-full md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors" placeholder="Cari reseller..." />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data Pelanggan</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Alamat & Area</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo Dompet</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status Akses</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="reseller in resellers.data" :key="reseller.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="reseller.customer">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <Link :href="`/customers/${reseller.customer.id}`" class="text-sm font-semibold text-gray-900 hover:text-blue-600 transition-colors">
                                                {{ reseller.customer.name }}
                                            </Link>
                                            <span v-if="!reseller.customer.package_id" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                                🎟️ Biller &amp; Reseller Murni
                                            </span>
                                            <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                                🌐 Pelanggan + Reseller
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                            <span>{{ reseller.customer.phone || reseller.user?.phone || '-' }}</span>
                                            <a v-if="reseller.customer.phone || reseller.user?.phone" :href="`https://wa.me/${(reseller.customer.phone || reseller.user?.phone || '').replace(/^0/, '62')}`" target="_blank" class="text-[10px] text-emerald-600 font-bold hover:underline">
                                                💬 WA
                                            </a>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ reseller.customer.customer_code }}</div>
                                    </div>
                                    <div v-else-if="reseller.user">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-sm font-semibold text-gray-900">{{ reseller.user.name }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                                🎟️ Mitra Biller Murni
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                            <span>{{ reseller.user.phone || '-' }}</span>
                                            <a v-if="reseller.user.phone" :href="`https://wa.me/${reseller.user.phone.replace(/^0/, '62')}`" target="_blank" class="text-[10px] text-emerald-600 font-bold hover:underline">
                                                💬 WA
                                            </a>
                                        </div>
                                        <div class="text-[10px] text-gray-400">{{ reseller.user.email }}</div>
                                    </div>
                                    <div v-else class="text-sm text-red-500 font-bold">Error: No Customer</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    <span v-if="reseller.customer">
                                        {{ reseller.customer.address || '-' }}
                                        <div v-if="reseller.customer.area_model" class="text-xs text-indigo-600 mt-1 font-semibold">{{ reseller.customer.area_model.name }}</div>
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="reseller.is_active" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                    <span v-else class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="openEditModal(reseller)" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-semibold transition-colors shadow-2xs"
                                            title="Atur Saldo & Status Dompet"
                                        >
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Atur Saldo</span>
                                        </button>

                                        <!-- Tombol Dropdown Menu Aksi -->
                                        <button 
                                            type="button" 
                                            @click.stop="toggleDropdown($event, reseller)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-lg transition-all shadow-2xs hover:shadow-xs cursor-pointer text-xs font-semibold focus:outline-none"
                                            :class="{ 'ring-2 ring-blue-500 bg-blue-50 text-blue-600 border-blue-300': activeDropdownRow?.id === reseller.id }"
                                            title="Pilihan Aksi"
                                        >
                                            <span>Aksi</span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-blue-600': activeDropdownRow?.id === reseller.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="resellers.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-gray-100 rounded-full p-3 mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900">Belum ada reseller</h3>
                                        <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan pelanggan sebagai reseller dari menu Booking.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="resellers.links && resellers.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, p) in resellers.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded-lg bg-gray-50" v-html="link.label" />
                            <a v-else :href="link.url" class="mr-1 mb-1 px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 focus:border-blue-500 focus:text-blue-500 transition-colors" :class="{ 'bg-blue-50 border-blue-200 text-blue-600 font-medium': link.active, 'border-gray-200 text-gray-600': !link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Dropdown Menu Floating -->
        <Teleport to="body">
            <div v-if="activeDropdownRow" class="fixed inset-0 z-[120]" @click="closeDropdown" @contextmenu.prevent="closeDropdown">
                <div 
                    :style="{ top: `${dropdownPos.top}px`, left: `${dropdownPos.left}px` }"
                    class="fixed w-72 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-200/80 py-1.5 z-[121] text-xs divide-y divide-slate-100 animate-fade-in-up"
                    @click.stop
                >
                    <!-- Header Info Reseller -->
                    <div class="px-3.5 py-2.5 bg-slate-50/90 -mt-1.5 rounded-t-2xl border-b border-slate-100 mb-1">
                        <div class="flex items-center gap-2.5">
                            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-2xs', getAvatarColor(activeDropdownRow.customer?.name || activeDropdownRow.user?.name || 'R')]">
                                {{ (activeDropdownRow.customer?.name || activeDropdownRow.user?.name || 'R').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 truncate text-xs">{{ activeDropdownRow.customer?.name || activeDropdownRow.user?.name || 'Reseller' }}</div>
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 truncate mt-0.5">
                                    <span class="font-semibold text-emerald-600">Saldo: Rp {{ Number(activeDropdownRow.balance || 0).toLocaleString('id-ID') }}</span>
                                    <span>&bull;</span>
                                    <span :class="activeDropdownRow.is_active ? 'text-green-600 font-bold' : 'text-red-500 font-bold'">{{ activeDropdownRow.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grup 1: Kelola Saldo & Profil -->
                    <div class="py-1">
                        <!-- Atur Saldo Dompet -->
                        <button 
                            type="button"
                            @click="openEditModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-blue-50/80 hover:text-blue-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-blue-50 group-hover:bg-blue-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-blue-700">Atur Saldo & Status</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-blue-500 font-normal leading-tight">Topup saldo dompet & atur status aktif</p>
                            </div>
                        </button>

                        <!-- Lihat Profil Pelanggan -->
                        <Link 
                            v-if="activeDropdownRow.customer"
                            :href="`/customers/${activeDropdownRow.customer.id}`" 
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-indigo-50/80 hover:text-indigo-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 group-hover:bg-indigo-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-indigo-700">Lihat Profil Pelanggan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-indigo-500 font-normal leading-tight">Detail akun pelanggan & riwayat paket</p>
                            </div>
                        </Link>

                        <!-- Edit Data Pelanggan -->
                        <Link 
                            v-if="activeDropdownRow.customer"
                            :href="`/customers/${activeDropdownRow.customer.id}/edit`" 
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-purple-50/80 hover:text-purple-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-purple-50 group-hover:bg-purple-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-purple-700">Edit Profil Pelanggan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-purple-500 font-normal leading-tight">Ubah data identitas, alamat, atau kontak</p>
                            </div>
                        </Link>
                    </div>

                    <!-- Grup 2: Akun Login Portal Reseller -->
                    <div class="py-1">
                        <!-- Buat Akun Login -->
                        <button 
                            v-if="!activeDropdownRow.user && !activeDropdownRow.user_id"
                            type="button"
                            @click="openCreateAccountModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-emerald-50/80 hover:text-emerald-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 group-hover:bg-emerald-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-emerald-700">Buat Akun Portal Reseller</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-emerald-500 font-normal leading-tight">Buat kredensial login client area</p>
                            </div>
                        </button>

                        <!-- Edit Akun / Password -->
                        <button 
                            v-if="activeDropdownRow.user || activeDropdownRow.user_id"
                            type="button"
                            @click="openResetModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-amber-50/80 hover:text-amber-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 group-hover:bg-amber-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-amber-700">Kelola Akun Login</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-amber-500 font-normal leading-tight">Ubah email atau reset password login</p>
                            </div>
                        </button>
                    </div>

                    <!-- Grup 3: Cabut Akses (Danger) -->
                    <div class="py-1">
                        <button 
                            type="button"
                            @click="deleteReseller(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-red-50/80 hover:text-red-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-red-50 group-hover:bg-red-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-red-600 group-hover:text-red-700">Cabut Akses Reseller</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-red-500 font-normal leading-tight">Hapus dompet reseller dari pelanggan ini</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Native Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Atur Dompet Reseller</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <div v-if="editingReseller && editingReseller.customer" class="mb-4 pb-4 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Nama Pelanggan</p>
                        <p class="text-sm text-gray-900 font-medium">{{ editingReseller.customer.name }}</p>
                    </div>

                    <form @submit.prevent="saveReseller" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Dompet (Rp)</label>
                            <input v-model="form.balance" type="number" min="0" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="0" />
                            <div v-if="form.errors.balance" class="mt-1 text-sm text-red-600">{{ form.errors.balance }}</div>
                            <p class="text-xs text-gray-500 mt-1">Gunakan untuk top-up manual atau koreksi saldo.</p>
                        </div>

                        <div class="flex items-center mt-4">
                            <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
                            <label for="is_active" class="ml-2 block text-sm text-gray-700">Akses Reseller Aktif</label>
                        </div>
                        
                        <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 disabled:opacity-50" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Create Account Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Buat Akun Login Reseller</h3>
                    <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <div v-if="accountReseller && accountReseller.customer" class="mb-4 pb-4 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Nama Pelanggan</p>
                        <p class="text-sm text-gray-900 font-medium">{{ accountReseller.customer.name }}</p>
                    </div>

                    <form @submit.prevent="createAccount" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username (Email) <span class="text-red-500">*</span></label>
                            <input v-model="createForm.email" type="email" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="email@contoh.com" required />
                            <div v-if="createForm.errors.email" class="mt-1 text-sm text-red-600">{{ createForm.errors.email }}</div>
                            <p class="text-xs text-gray-500 mt-1">Digunakan untuk login reseller.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                            <input v-model="createForm.password" type="password" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="Minimal 8 karakter" required minlength="8" />
                            <div v-if="createForm.errors.password" class="mt-1 text-sm text-red-600">{{ createForm.errors.password }}</div>
                        </div>

                        <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeCreateModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 disabled:opacity-50" :disabled="createForm.processing">
                                {{ createForm.processing ? 'Menyimpan...' : 'Buat Akun' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reset Password Modal -->
        <div v-if="showResetModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Edit Akun Login Reseller</h3>
                    <button @click="closeResetModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <div v-if="resetReseller && resetReseller.customer" class="mb-4 pb-4 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Nama Pelanggan</p>
                        <p class="text-sm text-gray-900 font-medium">{{ resetReseller.customer.name }}</p>
                    </div>

                    <form @submit.prevent="resetPassword" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username (Email)</label>
                            <input v-model="resetForm.email" type="email" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="email@contoh.com" />
                            <div v-if="resetForm.errors.email" class="mt-1 text-sm text-red-600">{{ resetForm.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-red-500">*</span></label>
                            <input v-model="resetForm.password" type="password" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm" placeholder="Kosongkan jika tidak ingin mengubah password" minlength="8" />
                            <div v-if="resetForm.errors.password" class="mt-1 text-sm text-red-600">{{ resetForm.errors.password }}</div>
                        </div>
                        
                        <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeResetModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-bold hover:bg-amber-700 disabled:opacity-50 flex items-center gap-2" :disabled="resetForm.processing">
                                <svg v-if="resetForm.processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ resetForm.processing ? 'Menyimpan...' : 'Ubah Password' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

