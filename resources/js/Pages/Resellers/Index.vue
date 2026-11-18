<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
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
    stats: {
        type: Object,
        default: () => ({ total: 0, master: 0, regular: 0, active: 0, inactive: 0, total_balance: 0 })
    },
    masterOptions: {
        type: Array,
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const search = ref(props.filters.search || '');
const tierFilter = ref(props.filters.tier || 'all');
const statusFilter = ref(props.filters.status || 'all');
const balanceFilter = ref(props.filters.balance_filter || 'all');
const areaFilter = ref(props.filters.area_id || 'all');

const isFilterActive = computed(() => {
    return !!search.value || tierFilter.value !== 'all' || statusFilter.value !== 'all' || balanceFilter.value !== 'all' || areaFilter.value !== 'all';
});

const applyFilter = () => {
    router.get(
        route('resellers.index'),
        { 
            search: search.value || undefined, 
            tier: tierFilter.value !== 'all' ? tierFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            balance_filter: balanceFilter.value !== 'all' ? balanceFilter.value : undefined,
            area_id: areaFilter.value !== 'all' ? areaFilter.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

watch(search, debounce(() => {
    applyFilter();
}, 300));

const setTierFilter = (tier) => {
    tierFilter.value = tier;
    applyFilter();
};

const resetAllFilters = () => {
    search.value = '';
    tierFilter.value = 'all';
    statusFilter.value = 'all';
    balanceFilter.value = 'all';
    areaFilter.value = 'all';
    applyFilter();
};

// ==========================================
// Modal Atur Saldo & Kemitraan Reseller
// ==========================================
const showModal = ref(false);
const editingReseller = ref(null);

const form = useForm({
    balance: 0,
    is_active: true,
    tier: 'regular',
    parent_id: '',
    override_fee: '',
    can_sell_vouchers: true,
    can_pay_bills: true,
    notes: '',
});

const openEditModal = (reseller) => {
    editingReseller.value = reseller;
    form.balance = Number(reseller.balance || 0);
    form.is_active = Boolean(reseller.is_active);
    form.tier = reseller.tier || 'regular';
    form.parent_id = reseller.parent_id || '';
    form.override_fee = reseller.override_fee !== null ? reseller.override_fee : '';
    form.can_sell_vouchers = reseller.can_sell_vouchers !== null ? Boolean(reseller.can_sell_vouchers) : true;
    form.can_pay_bills = reseller.can_pay_bills !== null ? Boolean(reseller.can_pay_bills) : true;
    form.notes = '';
    form.clearErrors();
    showModal.value = true;
};

const addQuickBalance = (nominal) => {
    form.balance = (Number(form.balance) || 0) + nominal;
};

const resetQuickBalance = () => {
    if (editingReseller.value) {
        form.balance = Number(editingReseller.value.balance || 0);
    }
};

const saveReseller = () => {
    if (editingReseller.value) {
        form.put(route('resellers.update', editingReseller.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data dompet saldo & kemitraan Reseller berhasil diperbarui.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            },
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

// ==========================================
// Modal Share Link Pendaftaran Mandiri
// ==========================================
const showShareLinkModal = ref(false);
const selectedMasterReferral = ref('');

const currentAppOrigin = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }
    return '';
});

const regularRegisterUrl = computed(() => `${currentAppOrigin.value}/register-reseller?tier=regular`);
const masterRegisterUrl = computed(() => `${currentAppOrigin.value}/register-reseller?tier=master`);
const referralRegisterUrl = computed(() => {
    if (!selectedMasterReferral.value) return '';
    return `${currentAppOrigin.value}/register-reseller?ref=${selectedMasterReferral.value}`;
});

const openShareLinkModal = () => {
    if (props.masterOptions && props.masterOptions.length > 0) {
        selectedMasterReferral.value = props.masterOptions[0].referral_code || '';
    }
    showShareLinkModal.value = true;
};

const closeShareLinkModal = () => {
    showShareLinkModal.value = false;
};

const copyUrl = async (url, label = 'Tautan') => {
    if (!url) return;
    try {
        await navigator.clipboard.writeText(url);
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `${label} berhasil disalin!`,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    } catch (e) {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = url;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `${label} berhasil disalin!`,
            showConfirmButton: false,
            timer: 2000
        });
    }
};

const shareWaMessage = (url, type = 'loket') => {
    let msg = '';
    if (type === 'master') {
        msg = `Halo Kak,\nYuk bergabung menjadi *Master Biller / Distributor Saldo Resmi* di jaringan kami!\n\n👑 *Keuntungan Master Biller:*\n- Distribusi saldo ke loket-loket binaan\n- Dapatkan komisi pasif override setiap kali loket binaan transaksi\n- Kode referral eksklusif\n\n📝 *Daftar mandiri di sini:*\n${url}\n\nTerima kasih! 🙏`;
    } else if (type === 'referral') {
        msg = `Halo Kak,\nAyo daftar menjadi *Mitra Loket Biller & Reseller Voucher* binaan resmi kami!\n\n🏪 *Keuntungan Loket:*\n- Bebas biaya internet bulanan\n- Layani penjualan voucher hotspot & pembayaran tagihan pelanggan\n- Margin keuntungan langsung\n\n📝 *Daftar langsung di sini:*\n${url}\n\nTerima kasih! 🙏`;
    } else {
        msg = `Halo Kak,\nBuka peluang usaha tambahan dengan menjadi *Mitra Loket Resmi & Reseller Hotspot* di wilayah Anda!\n\n🏪 *Fitur & Fasilitas:*\n- Cetak & jual voucher hotspot instan\n- Terima loket pembayaran tagihan internet warga\n- Gratis biaya langganan bulanan\n\n📝 *Daftar mandiri di sini:*\n${url}\n\nTerima kasih! 🙏`;
    }
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`, '_blank');
};

// ==========================================
// Modal Buat & Reset Akun Login Portal
// ==========================================
const showCreateModal = ref(false);
const accountReseller = ref(null);
const createForm = useForm({
    email: '',
    password: ''
});

const openCreateAccountModal = (reseller) => {
    accountReseller.value = reseller;
    createForm.email = reseller.customer?.email || reseller.user?.email || (reseller.customer?.customer_code ? reseller.customer.customer_code.toLowerCase().replace(/[^a-z0-9]/g, '') + '@reseller.apm' : '');
    createForm.password = '';
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const createAccount = () => {
    if (!accountReseller.value) return;
    
    createForm.post(route('resellers.create-account', accountReseller.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            Swal.fire('Berhasil!', 'Akun login reseller berhasil dibuat dan ditautkan.', 'success');
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire('Gagal!', msg || 'Gagal membuat akun reseller.', 'error');
        }
    });
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
    resetForm.clearErrors();
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
            Swal.fire('Berhasil!', 'Data akun login berhasil diubah.', 'success');
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire('Gagal!', msg || 'Gagal mengubah password reseller.', 'error');
        }
    });
};

// ==========================================
// Modal Detail Downlines Master Reseller
// ==========================================
const showDownlinesModal = ref(false);
const selectedMasterForDownlines = ref(null);

const openDownlinesModal = (master) => {
    selectedMasterForDownlines.value = master;
    showDownlinesModal.value = true;
};

const closeDownlinesModal = () => {
    showDownlinesModal.value = false;
    selectedMasterForDownlines.value = null;
};

// ==========================================
// Toggle Tier (Master <-> Regular)
// ==========================================
const toggleTier = (reseller) => {
    const isPromote = reseller.tier !== 'master';
    const actionText = isPromote ? 'Promosikan menjadi Master Biller / Distributor?' : 'Kembalikan status menjadi Reseller Regular?';
    const confirmBtn = isPromote ? 'Ya, Promosikan' : 'Ya, Kembalikan Regular';

    Swal.fire({
        title: actionText,
        text: isPromote 
            ? `Mitra ${reseller.customer?.name || reseller.user?.name} akan mendapatkan Kode Referral unik, dapat merekrut loket binaan, mentransfer saldo P2P, serta menerima royalti pasif saat loket binaannya bertransaksi.`
            : `Status Master Biller ${reseller.customer?.name || reseller.user?.name} akan dicabut kembali menjadi Mitra Biasa.`,
        icon: isPromote ? 'info' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isPromote ? '#d97706' : '#64748b',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: confirmBtn,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('resellers.toggle-tier', reseller.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil!', 'Status Tier kemitraan berhasil diperbarui.', 'success');
                },
                onError: () => {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat memproses perubahan tier.', 'error');
                }
            });
        }
    });
};

// ==========================================
// Cabut Akses Reseller (Delete)
// ==========================================
const deleteReseller = (reseller) => {
    const name = reseller.customer?.name || reseller.user?.name || ('Reseller #' + reseller.id);
    Swal.fire({
        title: `Cabut Akses Reseller ${name}?`,
        text: 'Akses portal, dompet saldo, dan data billing reseller akan dihapus bersih. Data pelanggan master tidak akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Cabut Akses!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('resellers.destroy', reseller.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil!', 'Akses kemitraan Reseller berhasil dicabut bersih.', 'success');
                }
            });
        }
    });
};

// ==========================================
// Floating Action Dropdown Positioning
// ==========================================
const activeDropdownRow = ref(null);
const dropdownPos = ref({ top: 0, left: 0 });

function toggleDropdown(event, reseller) {
    if (activeDropdownRow.value && activeDropdownRow.value.id === reseller.id) {
        activeDropdownRow.value = null;
        return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    const dropdownHeight = 440;
    const dropdownWidth = 288;

    let top = rect.bottom + 6;
    if (top + dropdownHeight > window.innerHeight) {
        if (rect.top > dropdownHeight) {
            top = Math.max(10, rect.top - dropdownHeight - 6);
        } else {
            top = Math.max(10, window.innerHeight - dropdownHeight - 10);
        }
    }

    let left = rect.right - dropdownWidth;
    if (left + dropdownWidth > window.innerWidth - 10) {
        left = window.innerWidth - dropdownWidth - 10;
    }
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
    const colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-pink-500', 'bg-amber-500', 'bg-teal-500'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

const cleanPhoneForWa = (phone) => {
    if (!phone) return '';
    let digits = phone.toString().replace(/\D/g, '');
    if (digits.startsWith('0')) {
        digits = '62' + digits.substring(1);
    } else if (digits.startsWith('8')) {
        digits = '62' + digits;
    }
    return digits;
};

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
    <Head title="Kemitraan Reseller & Master Biller" />

    <AppLayout 
        title="Kemitraan Reseller & Master Biller" 
        subtitle="Kelola dompet saldo, distributor Master Biller, jaringan loket binaan, dan kredensial login"
    >
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-12">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/80 shadow-2xs">
                            👑 Ekosistem Multi-Tier Reseller
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ stats.active || 0 }} Aktif &bull; {{ stats.inactive || 0 }} Nonaktif
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">Kemitraan Reseller & Master Biller</h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-gray-600 dark:text-gray-400">Kelola dompet saldo, distributor Master Biller, jaringan loket binaan, dan kredensial login.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
                    <!-- Tombol Modal Salin Link Pendaftaran Mandiri -->
                    <button 
                        type="button" 
                        @click="openShareLinkModal" 
                        class="flex-1 sm:flex-initial justify-center px-3.5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-emerald-600/20 flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
                        title="Bagikan atau salin link pendaftaran mandiri calon mitra"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        <span>Link Pendaftaran Mandiri</span>
                    </button>

                    <!-- Tambah dari Booking Pelanggan -->
                    <Link 
                        href="/customers/booking" 
                        class="flex-1 sm:flex-initial justify-center px-3.5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-blue-600/20 flex items-center gap-1.5 transition-all whitespace-nowrap cursor-pointer"
                        title="Daftarkan pelanggan aktif menjadi reseller dari menu Booking"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah dari Booking</span>
                    </Link>
                </div>
            </div>

            <!-- 4 KPI Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">
                <!-- Total Mitra -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-4.5 min-w-0 shadow-xs">
                    <div class="flex items-center justify-between gap-2 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        <span class="truncate">Total Mitra Terdaftar</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold shrink-0">🏪</div>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ stats.total || 0 }}</div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1.5">
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">&bull; {{ stats.active || 0 }} Aktif</span>
                        <span class="text-rose-500 dark:text-rose-400 font-semibold">&bull; {{ stats.inactive || 0 }} Nonaktif</span>
                    </p>
                </div>

                <!-- Master Biller -->
                <div class="bg-gradient-to-br from-amber-500/10 via-white to-white dark:from-amber-950/20 dark:via-slate-800 dark:to-slate-800 rounded-2xl border border-amber-200/80 dark:border-amber-800/60 p-4.5 min-w-0 shadow-xs">
                    <div class="flex items-center justify-between gap-2 text-xs font-bold text-amber-800 dark:text-amber-400 uppercase tracking-wider mb-2">
                        <span class="truncate">Master Biller / Distributor</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center font-bold shrink-0">👑</div>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-amber-700 dark:text-amber-300 tracking-tight">{{ stats.master || 0 }}</div>
                    <p class="text-xs text-amber-600 dark:text-amber-400/80 mt-1">Distributor saldo modal binaan</p>
                </div>

                <!-- Reseller Biasa -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-4.5 min-w-0 shadow-xs">
                    <div class="flex items-center justify-between gap-2 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        <span class="truncate">Mitra Loket Biasa</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold shrink-0">🎫</div>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ stats.regular || 0 }}</div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Loket penjualan voucher & tagihan</p>
                </div>

                <!-- Total Saldo Beredar -->
                <div class="bg-gradient-to-br from-emerald-500/10 via-white to-white dark:from-emerald-950/20 dark:via-slate-800 dark:to-slate-800 rounded-2xl border border-emerald-200/80 dark:border-emerald-800/60 p-4.5 min-w-0 shadow-xs">
                    <div class="flex items-center justify-between gap-2 text-xs font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-wider mb-2">
                        <span class="truncate">Total Saldo Beredar</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold shrink-0">💰</div>
                    </div>
                    <div class="text-lg sm:text-xl 2xl:text-2xl font-black text-emerald-700 dark:text-emerald-300 tracking-tight break-words leading-tight">
                        Rp {{ Number(stats.total_balance || 0).toLocaleString('id-ID') }}
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400/80 mt-1">Saldo aktif di dompet mitra</p>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xs border border-gray-100 dark:border-slate-700 p-3.5 sm:p-4 space-y-3">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 min-w-0">
                    <!-- Tier Filter Tabs -->
                    <div class="flex items-center p-1 bg-slate-100 dark:bg-slate-900/60 rounded-xl gap-1 overflow-x-auto max-w-full overscroll-x-contain">
                        <button 
                            type="button" 
                            @click="setTierFilter('all')" 
                            :class="tierFilter === 'all' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                            class="px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer whitespace-nowrap shrink-0 flex items-center gap-1.5"
                        >
                            <span>Semua Mitra</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="tierFilter === 'all' ? 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200' : 'bg-slate-200/60 dark:bg-slate-800 text-slate-600'">{{ stats.total || 0 }}</span>
                        </button>
                        <button 
                            type="button" 
                            @click="setTierFilter('master')" 
                            :class="tierFilter === 'master' ? 'bg-amber-500 text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                            class="px-3.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap shrink-0"
                        >
                            <span>👑 Master Biller</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="tierFilter === 'master' ? 'bg-amber-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'">{{ stats.master || 0 }}</span>
                        </button>
                        <button 
                            type="button" 
                            @click="setTierFilter('regular')" 
                            :class="tierFilter === 'regular' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                            class="px-3.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap shrink-0"
                        >
                            <span>🏪 Loket Biasa</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="tierFilter === 'regular' ? 'bg-blue-700 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'">{{ stats.regular || 0 }}</span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full lg:w-72 xl:w-80 shrink-0">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input 
                            v-model="search" 
                            type="text" 
                            class="block w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-xl focus:ring-amber-500 focus:border-amber-500 text-xs text-gray-900 dark:text-white placeholder-gray-400 transition-colors" 
                            placeholder="Cari nama, nomor WA, kode referral..." 
                        />
                    </div>
                </div>

                <!-- Secondary Filter Bar (Status, Saldo, Area) -->
                <div class="flex items-center gap-2.5 flex-wrap pt-2 border-t border-gray-100 dark:border-slate-700/60 text-xs">
                    <!-- Status Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold text-[11px]">Status:</span>
                        <select 
                            v-model="statusFilter" 
                            @change="applyFilter" 
                            class="py-1 px-2.5 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200 text-xs focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="all">Semua Status</option>
                            <option value="active">🟢 Aktif Saja</option>
                            <option value="inactive">🔴 Nonaktif</option>
                        </select>
                    </div>

                    <!-- Saldo Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold text-[11px]">Saldo:</span>
                        <select 
                            v-model="balanceFilter" 
                            @change="applyFilter" 
                            class="py-1 px-2.5 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200 text-xs focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="all">Semua Saldo</option>
                            <option value="positive">💰 Memiliki Saldo (> Rp 0)</option>
                            <option value="zero">⚪ Saldo Nol (Rp 0)</option>
                        </select>
                    </div>

                    <!-- Area Filter -->
                    <div v-if="areas && areas.length > 0" class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold text-[11px]">Area:</span>
                        <select 
                            v-model="areaFilter" 
                            @change="applyFilter" 
                            class="py-1 px-2.5 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-200 text-xs focus:ring-amber-500 focus:border-amber-500 max-w-[170px] truncate"
                        >
                            <option value="all">Semua Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- Reset Filter Button -->
                    <button 
                        v-if="isFilterActive" 
                        type="button" 
                        @click="resetAllFilters" 
                        class="ml-auto inline-flex items-center gap-1 text-[11px] text-rose-600 dark:text-rose-400 hover:underline font-bold cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Table of Resellers -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xs border border-gray-100 dark:border-slate-700 overflow-hidden min-w-0">
                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mitra &amp; Tingkatan</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sponsor / Master</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Loket Binaan</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Saldo Dompet</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-for="reseller in resellers.data" :key="reseller.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-750 transition-colors">
                                <!-- Mitra & Tingkatan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-start gap-3">
                                        <div :class="['w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-2xs', reseller.tier === 'master' ? 'bg-gradient-to-tr from-amber-600 to-yellow-500' : getAvatarColor(reseller.customer?.name || reseller.user?.name || 'R')]">
                                            {{ reseller.tier === 'master' ? '👑' : (reseller.customer?.name || reseller.user?.name || 'R').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <Link v-if="reseller.customer" :href="`/customers/${reseller.customer.id}`" class="text-sm font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                                    {{ reseller.customer.name }}
                                                </Link>
                                                <span v-else-if="reseller.user" class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ reseller.user.name }}
                                                </span>
                                                <span v-else class="text-sm text-red-500 font-bold">Tanpa Akun</span>

                                                <!-- Tier Badge -->
                                                <span v-if="reseller.tier === 'master'" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs">
                                                    👑 MASTER BILLER
                                                </span>
                                                <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                                    🏪 REGULAR
                                                </span>
                                            </div>

                                            <!-- Referral Code for Master -->
                                            <div v-if="reseller.tier === 'master' && reseller.referral_code" class="mt-1 flex items-center gap-1.5">
                                                <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">Ref:</span>
                                                <code class="px-1.5 py-0.2 rounded bg-amber-50 dark:bg-amber-950/80 text-amber-900 dark:text-amber-300 font-mono text-[11px] font-bold border border-amber-200 dark:border-amber-800">{{ reseller.referral_code }}</code>
                                            </div>

                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-2 flex-wrap">
                                                <span v-if="reseller.customer?.areaModel?.name || reseller.customer?.area" class="text-[11px] font-medium text-slate-600 dark:text-slate-300 flex items-center gap-0.5">
                                                    📍 {{ reseller.customer?.areaModel?.name || reseller.customer?.area }}
                                                </span>
                                                <span v-if="reseller.customer?.phone || reseller.user?.phone" class="font-mono">{{ reseller.customer?.phone || reseller.user?.phone }}</span>
                                                <a 
                                                    v-if="reseller.customer?.phone || reseller.user?.phone" 
                                                    :href="`https://api.whatsapp.com/send?phone=${cleanPhoneForWa(reseller.customer?.phone || reseller.user?.phone)}`" 
                                                    target="_blank" 
                                                    class="inline-flex items-center gap-0.5 text-[10px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline"
                                                >
                                                    <span>💬 WA</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Master / Sponsor -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300">
                                    <div v-if="reseller.master" class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white flex items-center gap-1">
                                            <span>👑</span>
                                            {{ reseller.master.customer?.name || reseller.master.user?.name || ('Master #' + reseller.master.id) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-mono">Ref: {{ reseller.master.referral_code || '-' }}</span>
                                    </div>
                                    <span v-else-if="reseller.tier === 'master'" class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">
                                        Distributor Utama
                                    </span>
                                    <span v-else class="text-gray-400 italic">Mandiri (Tanpa Sponsor)</span>
                                </td>

                                <!-- Loket Binaan (Downlines) -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="reseller.tier === 'master'">
                                        <button 
                                            type="button" 
                                            @click="openDownlinesModal(reseller)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 rounded-lg text-xs font-bold border border-amber-200 dark:border-amber-800 transition-colors cursor-pointer"
                                            title="Klik untuk melihat daftar loket binaan"
                                        >
                                            <span>👥</span>
                                            <span>{{ reseller.downlines_count || 0 }} Loket</span>
                                            <span class="text-[10px] text-amber-600 dark:text-amber-400 underline">Lihat</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>

                                <!-- Saldo Dompet -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-extrabold text-gray-900 dark:text-white">
                                        Rp {{ Number(reseller.balance || 0).toLocaleString('id-ID') }}
                                    </div>
                                    <div v-if="Number(reseller.balance || 0) > 0" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
                                        &bull; Saldo Aktif
                                    </div>
                                </td>

                                <!-- Status Akses -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <span v-if="reseller.is_active" class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 w-fit">
                                            Aktif
                                        </span>
                                        <span v-else class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800 w-fit">
                                            Nonaktif
                                        </span>
                                        <span v-if="reseller.customer && reseller.customer.status !== 'active'" class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                                            Pelanggan: {{ reseller.customer.status }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Tombol Cepat Atur Saldo -->
                                        <button 
                                            @click="openEditModal(reseller)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                            title="Atur Saldo & Status Dompet"
                                        >
                                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Saldo</span>
                                        </button>

                                        <!-- Tombol Dropdown Menu Aksi -->
                                        <button 
                                            type="button" 
                                            @click.stop="toggleDropdown($event, reseller)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-650 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-lg transition-all shadow-2xs text-xs font-bold cursor-pointer"
                                            :class="{ 'ring-2 ring-amber-500 bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-300': activeDropdownRow?.id === reseller.id }"
                                            title="Pilihan Aksi Lengkap"
                                        >
                                            <span>Aksi</span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-amber-600': activeDropdownRow?.id === reseller.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="resellers.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-gray-100 dark:bg-slate-700 rounded-full p-3 mb-3">
                                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Tidak ada data mitra</h3>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sesuaikan filter atau tambah mitra baru dari Booking Pelanggan / Form Pendaftaran.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Modern Inertia Pagination -->
                <div class="px-6 py-4 bg-gray-50/80 dark:bg-slate-900/60 border-t border-gray-100 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <div class="text-gray-600 dark:text-gray-400 font-medium">
                        Menampilkan <span class="font-bold text-gray-900 dark:text-white">{{ resellers.from || 0 }}</span> - <span class="font-bold text-gray-900 dark:text-white">{{ resellers.to || 0 }}</span> dari <span class="font-bold text-gray-900 dark:text-white">{{ resellers.total || 0 }}</span> mitra
                    </div>
                    <div v-if="resellers.links && resellers.links.length > 3" class="flex flex-wrap gap-1">
                        <template v-for="(link, p) in resellers.links" :key="p">
                            <div 
                                v-if="link.url === null" 
                                class="px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-slate-700 rounded-lg bg-gray-50 dark:bg-slate-800" 
                                v-html="link.label" 
                            />
                            <Link 
                                v-else 
                                :href="link.url" 
                                preserve-scroll 
                                preserve-state 
                                class="px-3 py-1.5 text-xs border rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors font-medium" 
                                :class="{ 'bg-amber-500 border-amber-500 text-white font-bold': link.active, 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300': !link.active }" 
                                v-html="link.label" 
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- FLOATING ACTION DROPDOWN MENU                                             -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div v-if="activeDropdownRow" class="fixed inset-0 z-[120]" @click="closeDropdown" @contextmenu.prevent="closeDropdown">
                <div 
                    :style="{ top: `${dropdownPos.top}px`, left: `${dropdownPos.left}px` }"
                    class="fixed w-72 max-w-[calc(100vw-20px)] max-h-[calc(100vh-20px)] overflow-y-auto bg-white/95 dark:bg-slate-800/95 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-200/80 dark:border-slate-700 py-1.5 z-[121] text-xs divide-y divide-slate-100 dark:divide-slate-700 animate-fade-in-up"
                    @click.stop
                >
                    <!-- Header Info Reseller -->
                    <div class="px-3.5 py-2.5 bg-slate-50/90 dark:bg-slate-900/60 -mt-1.5 rounded-t-2xl border-b border-slate-100 dark:border-slate-700 mb-1">
                        <div class="flex items-center gap-2.5">
                            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-2xs', activeDropdownRow.tier === 'master' ? 'bg-amber-500' : getAvatarColor(activeDropdownRow.customer?.name || activeDropdownRow.user?.name || 'R')]">
                                {{ activeDropdownRow.tier === 'master' ? '👑' : (activeDropdownRow.customer?.name || activeDropdownRow.user?.name || 'R').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 dark:text-white truncate text-xs">{{ activeDropdownRow.customer?.name || activeDropdownRow.user?.name || 'Reseller' }}</div>
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">Rp {{ Number(activeDropdownRow.balance || 0).toLocaleString('id-ID') }}</span>
                                    <span>&bull;</span>
                                    <span :class="activeDropdownRow.is_active ? 'text-green-600 dark:text-green-400 font-bold' : 'text-red-500 font-bold'">{{ activeDropdownRow.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grup 1: Promosi Tier & Loket Binaan -->
                    <div class="py-1">
                        <!-- Toggle Master / Regular -->
                        <button 
                            type="button" 
                            @click="toggleTier(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-amber-50/80 dark:hover:bg-amber-950/40 hover:text-amber-800 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950 group-hover:bg-amber-100 flex items-center justify-center shrink-0 text-amber-600">
                                👑
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-amber-800 dark:group-hover:text-amber-300">
                                    {{ activeDropdownRow.tier === 'master' ? 'Kembalikan ke Regular' : 'Jadikan Master Biller' }}
                                </span>
                                <p class="text-[10px] text-slate-400 group-hover:text-amber-600 font-normal leading-tight">
                                    {{ activeDropdownRow.tier === 'master' ? 'Ubah status jadi loket reguler biasa' : 'Beri hak distribusi saldo & kode referral' }}
                                </p>
                            </div>
                        </button>

                        <!-- Lihat Loket Binaan jika Master -->
                        <button 
                            v-if="activeDropdownRow.tier === 'master'"
                            type="button" 
                            @click="openDownlinesModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-blue-50/80 dark:hover:bg-blue-950/40 hover:text-blue-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950 group-hover:bg-blue-100 flex items-center justify-center shrink-0 text-blue-600">
                                👥
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-blue-700 dark:group-hover:text-blue-300">Lihat Loket Binaan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-blue-500 font-normal leading-tight">{{ activeDropdownRow.downlines_count || 0 }} sub-reseller terdaftar</p>
                            </div>
                        </button>
                    </div>

                    <!-- Grup 2: Kelola Saldo & Profil -->
                    <div class="py-1">
                        <!-- Atur Saldo Dompet -->
                        <button 
                            type="button" 
                            @click="openEditModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-blue-50/80 dark:hover:bg-blue-950/40 hover:text-blue-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950 group-hover:bg-blue-100 flex items-center justify-center shrink-0 text-blue-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-blue-700 dark:group-hover:text-blue-300">Atur Saldo & Kemitraan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-blue-500 font-normal leading-tight">Topup manual / atur sponsor & tier</p>
                            </div>
                        </button>

                        <!-- Riwayat Mutasi Saldo -->
                        <Link 
                            :href="`/reseller-requests?search=${encodeURIComponent(activeDropdownRow.customer?.name || activeDropdownRow.user?.name || '')}`"
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-teal-50/80 dark:hover:bg-teal-950/40 hover:text-teal-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-950 group-hover:bg-teal-100 flex items-center justify-center shrink-0 text-teal-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-teal-700 dark:group-hover:text-teal-300">Riwayat Mutasi Saldo</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-teal-500 font-normal leading-tight">Lihat audit transaksi kredit/debit</p>
                            </div>
                        </Link>

                        <!-- Hubungi via WhatsApp -->
                        <a 
                            v-if="activeDropdownRow.customer?.phone || activeDropdownRow.user?.phone"
                            :href="`https://api.whatsapp.com/send?phone=${cleanPhoneForWa(activeDropdownRow.customer?.phone || activeDropdownRow.user?.phone)}`"
                            target="_blank"
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-emerald-50/80 dark:hover:bg-emerald-950/40 hover:text-emerald-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950 group-hover:bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-600">
                                💬
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">Hubungi via WhatsApp</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-emerald-500 font-normal leading-tight">{{ activeDropdownRow.customer?.phone || activeDropdownRow.user?.phone }}</p>
                            </div>
                        </a>

                        <!-- Lihat Profil Pelanggan -->
                        <Link 
                            v-if="activeDropdownRow.customer"
                            :href="`/customers/${activeDropdownRow.customer.id}`" 
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-indigo-50/80 dark:hover:bg-indigo-950/40 hover:text-indigo-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950 group-hover:bg-indigo-100 flex items-center justify-center shrink-0 text-indigo-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">Lihat Profil Pelanggan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-indigo-500 font-normal leading-tight">Detail akun pelanggan & riwayat paket</p>
                            </div>
                        </Link>
                    </div>

                    <!-- Grup 3: Akun Login Portal Reseller -->
                    <div class="py-1">
                        <!-- Buat Akun Login -->
                        <button 
                            v-if="!activeDropdownRow.user && !activeDropdownRow.user_id"
                            type="button" 
                            @click="openCreateAccountModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-emerald-50/80 dark:hover:bg-emerald-950/40 hover:text-emerald-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950 group-hover:bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">Buat Akun Login Portal</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-emerald-500 font-normal leading-tight">Buat kredensial login client area</p>
                            </div>
                        </button>

                        <!-- Edit Akun / Password -->
                        <button 
                            v-if="activeDropdownRow.user || activeDropdownRow.user_id"
                            type="button" 
                            @click="openResetModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-amber-50/80 dark:hover:bg-amber-950/40 hover:text-amber-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950 group-hover:bg-amber-100 flex items-center justify-center shrink-0 text-amber-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-slate-800 dark:text-slate-200 group-hover:text-amber-700 dark:group-hover:text-amber-300">Kelola Akun Login</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-amber-500 font-normal leading-tight">Ubah email atau reset password</p>
                            </div>
                        </button>
                    </div>

                    <!-- Grup 4: Cabut Akses (Danger) -->
                    <div class="py-1">
                        <button 
                            type="button" 
                            @click="deleteReseller(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 dark:text-slate-200 hover:bg-rose-50/80 dark:hover:bg-rose-950/40 hover:text-rose-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-rose-50 dark:bg-rose-950 group-hover:bg-rose-100 flex items-center justify-center shrink-0 text-rose-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-xs block text-rose-600 group-hover:text-rose-700">Cabut Akses Reseller</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-rose-500 font-normal leading-tight">Hapus kemitraan & seluruh data kasbon</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- MODAL BAGIKAN / SALIN LINK PENDAFTARAN MANDIRI                             -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div 
                v-if="showShareLinkModal" 
                class="fixed inset-0 z-[115] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 animate-fade-in"
                @click.self="closeShareLinkModal"
            >
                <div 
                    class="bg-white dark:bg-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all animate-scale-up flex flex-col max-h-[90vh]"
                    @click.stop
                >
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-2xl border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Link Pendaftaran Mandiri Calon Mitra</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Salin URL atau kirimkan via WhatsApp ke calon mitra loket/distributor.</p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="closeShareLinkModal" 
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-xl transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-4 overflow-y-auto pr-1">
                        <!-- Opsi 1: Pendaftaran Loket Reguler -->
                        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs flex items-center gap-1.5">
                                    <span>🏪</span> Link Pendaftaran Loket Biasa (Reguler)
                                </span>
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                                    Reseller Standar
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Cocok untuk warung, konter pulsa, atau warga yang ingin jualan voucher hotspot & loket pembayaran.</p>
                            <div class="flex items-center gap-2 pt-1">
                                <input 
                                    type="text" 
                                    readonly 
                                    :value="regularRegisterUrl" 
                                    class="w-full text-xs font-mono bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-slate-200" 
                                />
                                <button 
                                    type="button" 
                                    @click="copyUrl(regularRegisterUrl, 'Link Loket Biasa')" 
                                    class="px-3 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0"
                                >
                                    Salin
                                </button>
                                <button 
                                    type="button" 
                                    @click="shareWaMessage(regularRegisterUrl, 'loket')" 
                                    class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0"
                                >
                                    💬 WA
                                </button>
                            </div>
                        </div>

                        <!-- Opsi 2: Pendaftaran Master Biller -->
                        <div class="bg-amber-50/60 dark:bg-amber-950/30 p-4 rounded-2xl border border-amber-200 dark:border-amber-800/60 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-amber-900 dark:text-amber-300 text-xs flex items-center gap-1.5">
                                    <span>👑</span> Link Pendaftaran Master Biller (Distributor)
                                </span>
                                <span class="text-[10px] text-amber-700 dark:text-amber-300 font-bold bg-amber-100 dark:bg-amber-900/60 px-2 py-0.5 rounded-full border border-amber-300 dark:border-amber-700">
                                    Distributor Multi-Tier
                                </span>
                            </div>
                            <p class="text-[11px] text-amber-800 dark:text-amber-400/80">Calon mitra langsung diarahkan ke pendaftaran tingkatan Master Biller.</p>
                            <div class="flex items-center gap-2 pt-1">
                                <input 
                                    type="text" 
                                    readonly 
                                    :value="masterRegisterUrl" 
                                    class="w-full text-xs font-mono bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800 rounded-xl px-3 py-2 text-slate-800 dark:text-slate-200" 
                                />
                                <button 
                                    type="button" 
                                    @click="copyUrl(masterRegisterUrl, 'Link Master Biller')" 
                                    class="px-3 py-2 bg-amber-200 hover:bg-amber-300 dark:bg-amber-900 dark:hover:bg-amber-800 text-amber-900 dark:text-amber-200 rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0"
                                >
                                    Salin
                                </button>
                                <button 
                                    type="button" 
                                    @click="shareWaMessage(masterRegisterUrl, 'master')" 
                                    class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0"
                                >
                                    💬 WA
                                </button>
                            </div>
                        </div>

                        <!-- Opsi 3: Link Referral Master Biller Binaan -->
                        <div v-if="masterOptions && masterOptions.length > 0" class="bg-blue-50/60 dark:bg-blue-950/30 p-4 rounded-2xl border border-blue-200 dark:border-blue-800/60 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-blue-900 dark:text-blue-300 text-xs flex items-center gap-1.5">
                                    <span>🔗</span> Link Referral Loket Binaan Master
                                </span>
                            </div>
                            <p class="text-[11px] text-blue-800 dark:text-blue-400/80">Pilih Master Biller di bawah untuk membuat tautan pendaftaran dengan kode referral otomatis.</p>
                            <div class="space-y-2 pt-1">
                                <select 
                                    v-model="selectedMasterReferral" 
                                    class="w-full rounded-xl border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs"
                                >
                                    <option v-for="m in masterOptions" :key="m.id" :value="m.referral_code">
                                        👑 {{ m.name }} (Kode: {{ m.referral_code }})
                                    </option>
                                </select>
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="text" 
                                        readonly 
                                        :value="referralRegisterUrl" 
                                        class="w-full text-xs font-mono bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-xl px-3 py-2 text-slate-800 dark:text-slate-200" 
                                    />
                                    <button 
                                        type="button" 
                                        @click="copyUrl(referralRegisterUrl, 'Link Referral Master')" 
                                        class="px-3 py-2 bg-blue-200 hover:bg-blue-300 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-900 dark:text-blue-200 rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0"
                                    >
                                        Salin
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="shareWaMessage(referralRegisterUrl, 'referral')" 
                                        class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0"
                                    >
                                        💬 WA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between gap-3">
                        <a 
                            :href="regularRegisterUrl" 
                            target="_blank" 
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            <span>Buka di Tab Baru (Preview)</span>
                        </a>
                        <button 
                            type="button" 
                            @click="closeShareLinkModal" 
                            class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- MODAL ATUR SALDO & KEMITRAAN RESELLER                                     -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div 
                v-if="showModal && editingReseller" 
                class="fixed inset-0 z-[115] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 animate-fade-in"
                @click.self="closeModal"
            >
                <div 
                    class="bg-white dark:bg-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all animate-scale-up flex flex-col max-h-[90vh]"
                    @click.stop
                >
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 rounded-2xl border border-blue-200 dark:border-blue-800 shadow-2xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Atur Saldo &amp; Kemitraan Reseller</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Kelola saldo modal, tingkatan tier, izin fitur, dan sponsor.</p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-xl transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-1 overflow-y-auto max-h-[75vh] mt-4 space-y-4">
                        <!-- Mitra Info Header Card -->
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-black tracking-wider">Nama Mitra Reseller</p>
                                <p class="text-sm text-gray-900 dark:text-white font-bold mt-0.5">{{ editingReseller.customer?.name || editingReseller.user?.name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-mono">Saldo Saat Ini: <b class="text-emerald-600 dark:text-emerald-400">Rp {{ Number(editingReseller.balance || 0).toLocaleString('id-ID') }}</b></p>
                            </div>
                            <span v-if="editingReseller.tier === 'master'" class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                👑 Master Biller
                            </span>
                            <span v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                🏪 Loket Biasa
                            </span>
                        </div>

                        <form @submit.prevent="saveReseller" class="space-y-4">
                            <!-- Saldo Dompet & Quick Add Buttons -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    Saldo Dompet (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="form.balance" 
                                    type="number" 
                                    min="0" 
                                    class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-sm font-bold shadow-2xs" 
                                    placeholder="0" 
                                    required 
                                />
                                <div v-if="form.errors.balance" class="text-xs text-red-600 font-semibold">{{ form.errors.balance }}</div>

                                <!-- Quick Add Buttons -->
                                <div class="flex items-center gap-1.5 flex-wrap pt-1">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-semibold uppercase">Tambah Cepat:</span>
                                    <button 
                                        type="button" 
                                        @click="addQuickBalance(50000)" 
                                        class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold cursor-pointer transition-colors"
                                    >
                                        +50 rb
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="addQuickBalance(100000)" 
                                        class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold cursor-pointer transition-colors"
                                    >
                                        +100 rb
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="addQuickBalance(500000)" 
                                        class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold cursor-pointer transition-colors"
                                    >
                                        +500 rb
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="addQuickBalance(1000000)" 
                                        class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold cursor-pointer transition-colors"
                                    >
                                        +1 jt
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="resetQuickBalance" 
                                        class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-[11px] font-bold cursor-pointer transition-colors ml-auto"
                                        title="Kembalikan ke nominal saldo semula"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Catatan / Keterangan Penyesuaian Saldo -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Catatan / Keterangan Perubahan Saldo <span class="text-gray-400 font-normal">(Opsional)</span>
                                </label>
                                <input 
                                    v-model="form.notes" 
                                    type="text" 
                                    class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-xs" 
                                    placeholder="Contoh: Setoran tunai loket, modal awal binaan, dll." 
                                />
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Akan tersimpan secara transparan pada mutasi transaksi saldo reseller.</p>
                            </div>

                            <!-- Tingkatan Tier -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Tingkatan Kemitraan (Tier)</label>
                                <select v-model="form.tier" class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-sm">
                                    <option value="regular">🏪 Loket Biasa (Mitra Reguler)</option>
                                    <option value="master">👑 Master Biller / Distributor Saldo</option>
                                </select>
                            </div>

                            <!-- Sponsor Master (Jika Regular) -->
                            <div v-if="form.tier === 'regular'">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Sponsor / Master Biller Binaan</label>
                                <select v-model="form.parent_id" class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-sm">
                                    <option value="">-- Mandiri (Tanpa Sponsor) --</option>
                                    <option v-for="m in masterOptions" :key="m.id" :value="m.id" :disabled="editingReseller && editingReseller.id === m.id">
                                        👑 {{ m.name }} (Ref: {{ m.referral_code }})
                                    </option>
                                </select>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Master Biller akan menerima royalti pasif saat loket ini melakukan pembayaran tagihan.</p>
                            </div>

                            <!-- Royalti Override Kustom (Opsional jika Master) -->
                            <div v-if="form.tier === 'master'">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Royalti Override Pasif per Tagihan (Rp)</label>
                                <input v-model="form.override_fee" type="number" min="0" placeholder="Biarkan kosong untuk default sistem (Rp 500)" class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-sm" />
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Royalti yang diterima Master setiap kali sub-reseller binaannya melunasi tagihan pelanggan.</p>
                            </div>

                            <!-- Izin Fitur & Status Akses -->
                            <div class="space-y-2.5 pt-2 border-t border-gray-100 dark:border-slate-700">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Izin Fitur & Akses Portal</span>
                                
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="modal_can_sell_vouchers" v-model="form.can_sell_vouchers" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer" />
                                    <label for="modal_can_sell_vouchers" class="text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">Boleh Generate & Jual Voucher Hotspot</label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="modal_can_pay_bills" v-model="form.can_pay_bills" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer" />
                                    <label for="modal_can_pay_bills" class="text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">Boleh Layani Pembayaran Tagihan Internet (Loket Biller)</label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="modal_is_active" v-model="form.is_active" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer" />
                                    <label for="modal_is_active" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer">Akses Kemitraan Aktif (Dapat Login ke Client Area)</label>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-100 dark:border-slate-700 flex justify-end gap-2">
                                <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 cursor-pointer transition-colors">Batal</button>
                                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold disabled:opacity-50 cursor-pointer transition-colors shadow-sm" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Kemitraan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- MODAL DETAIL DOWNLINES (LOKET BINAAN MASTER)                              -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div 
                v-if="showDownlinesModal && selectedMasterForDownlines" 
                class="fixed inset-0 z-[115] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 animate-fade-in"
                @click.self="closeDownlinesModal"
            >
                <div 
                    class="bg-white dark:bg-slate-800 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all animate-scale-up flex flex-col max-h-[85vh]"
                    @click.stop
                >
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shadow-sm">👑</div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Jaringan Loket Binaan Master</h3>
                                <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5">
                                    Master: <strong class="font-bold">{{ selectedMasterForDownlines.customer?.name || selectedMasterForDownlines.user?.name }}</strong> 
                                    (Ref: <code class="font-mono font-bold">{{ selectedMasterForDownlines.referral_code }}</code>)
                                </p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="closeDownlinesModal" 
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-xl transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 p-1 overflow-y-auto flex-1">
                        <div v-if="selectedMasterForDownlines.downlines && selectedMasterForDownlines.downlines.length > 0" class="divide-y divide-gray-100 dark:divide-slate-700">
                            <div v-for="downline in selectedMasterForDownlines.downlines" :key="downline.id" class="py-3 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="font-bold text-gray-900 dark:text-white text-xs truncate">
                                        {{ downline.customer?.name || downline.user?.name || ('Reseller #' + downline.id) }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5 flex-wrap">
                                        <span>{{ downline.customer?.phone || downline.user?.phone || '-' }}</span>
                                        <a 
                                            v-if="downline.customer?.phone || downline.user?.phone" 
                                            :href="`https://api.whatsapp.com/send?phone=${cleanPhoneForWa(downline.customer?.phone || downline.user?.phone)}`" 
                                            target="_blank" 
                                            class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline"
                                        >
                                            💬 WA
                                        </a>
                                        <span>&bull;</span>
                                        <span class="text-gray-400">{{ downline.customer?.address || '-' }}</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs font-black text-emerald-600 dark:text-emerald-400">
                                        Rp {{ Number(downline.balance || 0).toLocaleString('id-ID') }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        {{ downline.is_active ? '✅ Aktif' : '❌ Nonaktif' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-10 text-center">
                            <div class="w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">🏪</div>
                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">Belum Ada Loket Binaan</h4>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                                Master Biller ini belum memiliki sub-reseller yang mendaftar menggunakan kode referral 
                                <strong class="font-mono text-amber-700 dark:text-amber-400">{{ selectedMasterForDownlines.referral_code }}</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-slate-700 flex justify-end">
                        <button type="button" @click="closeDownlinesModal" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-xl text-xs font-bold cursor-pointer transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- CREATE ACCOUNT MODAL                                                      -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div 
                v-if="showCreateModal && accountReseller" 
                class="fixed inset-0 z-[115] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 animate-fade-in"
                @click.self="closeCreateModal"
            >
                <div 
                    class="bg-white dark:bg-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all animate-scale-up flex flex-col"
                    @click.stop
                >
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Buat Akun Login Portal Reseller</h3>
                        <button @click="closeCreateModal" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 overflow-y-auto max-h-[70vh]">
                        <div v-if="accountReseller.customer" class="mb-4 pb-3 border-b border-gray-100 dark:border-slate-700">
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">Nama Mitra</p>
                            <p class="text-sm text-gray-900 dark:text-white font-bold">{{ accountReseller.customer.name }}</p>
                        </div>

                        <form @submit.prevent="createAccount" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Username (Email) <span class="text-rose-500">*</span></label>
                                <input v-model="createForm.email" type="email" class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-xs" placeholder="email@contoh.com" required />
                                <div v-if="createForm.errors.email" class="mt-1 text-xs text-red-600">{{ createForm.errors.email }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Password <span class="text-rose-500">*</span></label>
                                <input v-model="createForm.password" type="password" class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-xs" placeholder="Minimal 6 karakter" required minlength="6" />
                                <div v-if="createForm.errors.password" class="mt-1 text-xs text-red-600">{{ createForm.errors.password }}</div>
                            </div>

                            <div class="pt-4 border-t border-gray-100 dark:border-slate-700 flex justify-end gap-2">
                                <button type="button" @click="closeCreateModal" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 cursor-pointer">Batal</button>
                                <button type="submit" class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 disabled:opacity-50 cursor-pointer transition-colors" :disabled="createForm.processing">
                                    {{ createForm.processing ? 'Menyimpan...' : 'Buat Akun Portal' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- RESET PASSWORD MODAL                                                      -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div 
                v-if="showResetModal && resetReseller" 
                class="fixed inset-0 z-[115] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 animate-fade-in"
                @click.self="closeResetModal"
            >
                <div 
                    class="bg-white dark:bg-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all animate-scale-up flex flex-col"
                    @click.stop
                >
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit Akun Login Reseller</h3>
                        <button @click="closeResetModal" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 overflow-y-auto max-h-[70vh]">
                        <div v-if="resetReseller.customer" class="mb-4 pb-3 border-b border-gray-100 dark:border-slate-700">
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">Nama Mitra</p>
                            <p class="text-sm text-gray-900 dark:text-white font-bold">{{ resetReseller.customer.name }}</p>
                        </div>

                        <form @submit.prevent="resetPassword" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Username (Email)</label>
                                <input v-model="resetForm.email" type="email" class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-xs" placeholder="email@contoh.com" />
                                <div v-if="resetForm.errors.email" class="mt-1 text-xs text-red-600">{{ resetForm.errors.email }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Password Baru</label>
                                <input v-model="resetForm.password" type="password" class="w-full rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500 text-xs" placeholder="Kosongkan jika tidak ingin mengubah password (min. 6 karakter)" minlength="6" />
                                <div v-if="resetForm.errors.password" class="mt-1 text-xs text-red-600">{{ resetForm.errors.password }}</div>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-100 dark:border-slate-700 flex justify-end gap-2">
                                <button type="button" @click="closeResetModal" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 cursor-pointer">Batal</button>
                                <button type="submit" class="px-5 py-2 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 disabled:opacity-50 flex items-center gap-2 cursor-pointer transition-colors" :disabled="resetForm.processing">
                                    {{ resetForm.processing ? 'Menyimpan...' : 'Ubah Akun' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
