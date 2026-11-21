<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon,
    ArrowRightOnRectangleIcon, 
    BanknotesIcon, 
    BookOpenIcon,
    BuildingLibraryIcon, 
    ChartBarSquareIcon, 
    ChevronRightIcon, 
    ClipboardDocumentListIcon, 
    ExclamationTriangleIcon,
    PlusCircleIcon, 
    ShieldCheckIcon, 
    UserGroupIcon, 
    UsersIcon 
} from '@heroicons/vue/24/outline';
import AppLogo from '@/Components/AppLogo.vue';
import { useTheme } from '@/Composables/useTheme';
import { affiliateSections, affiliateHref } from '@/Composables/affiliateNavigation';

defineProps({ section: String });
defineEmits(['navigate']);
const page = usePage();
const { isDarkMode } = useTheme();
const user = computed(() => page.props.auth?.user || {});
const initials = computed(() => (user.value.name || 'Sales').trim().split(/\s+/).slice(0, 2).map(name => name[0]).join('').toUpperCase());
const isAdminOrStaff = computed(() => {
    const role = String(page.props.auth?.user?.role || '').toLowerCase();
    const staffRoles = [
        'admin', 'superadmin', 'super admin', 'super-admin', 
        'noc', 'teknisi', 'cs', 'finance', 'manager', 'dispatcher', 'hrd', 'operator'
    ];
    const hasRole = page.props.auth?.user?.roles?.some(r => staffRoles.includes(String(r.name || '').toLowerCase()));
    return staffRoles.includes(role) || Boolean(hasRole) || Boolean(page.props.auth?.user?.is_admin);
});
const icons = { 
    overview: ChartBarSquareIcon, 
    booking: PlusCircleIcon, 
    customers: UsersIcon, 
    downlines: UserGroupIcon, 
    clawback: ExclamationTriangleIcon,
    commissions: ClipboardDocumentListIcon, 
    withdrawals: BanknotesIcon, 
    bank: BuildingLibraryIcon,
    guide: BookOpenIcon,
};
</script>

<template>
    <div class="sales-sidebar-content">
        <div class="sales-brand"><AppLogo context="navbar" :white="isDarkMode" /><span>SALES &amp; AFFILIATE</span></div>
        <div class="sales-workspace-label"><span class="workspace-dot"></span> Ruang kerja pribadi</div>
        <nav aria-label="Menu Sales Afiliasi" class="sales-navigation">
            <div v-for="group in [
                { id: 'workspace', label: 'WORKSPACE' }, 
                { id: 'finance', label: 'KEUANGAN' },
                { id: 'info', label: 'PANDUAN & SOP' }
            ]" :key="group.id" class="sales-nav-group">
                <p>{{ group.label }}</p>
                <Link v-for="item in affiliateSections.filter(item => item.group === group.id)" :key="item.id"
                    :href="affiliateHref(item.id)" :aria-current="section === item.id ? 'page' : undefined"
                    class="sales-nav-link" :class="{ 'is-active': section === item.id }" @click="$emit('navigate')">
                    <component :is="icons[item.icon]" /><span>{{ item.label }}</span><ChevronRightIcon v-if="section === item.id" class="nav-chevron" />
                </Link>
            </div>
        </nav>
        <div class="sales-sidebar-footer">
            <Link 
                v-if="isAdminOrStaff" 
                href="/" 
                class="sales-admin-return"
                title="Kembali ke Panel Utama Admin ISP"
                @click="$emit('navigate')"
            >
                <ArrowLeftIcon class="w-4 h-4 shrink-0" />
                <span>Panel Admin ISP</span>
            </Link>
            <div class="sales-access-note"><ShieldCheckIcon /><p>Portal pribadi Anda<span>Data pelanggan dan jaringan sesuai akses akun.</span></p></div>
            <div class="sales-user"><span class="sales-avatar">{{ initials }}</span><div><strong>{{ user.name }}</strong><span>{{ page.props.is_employee ? 'Karyawan internal' : 'Sales & Afiliasi' }}</span></div></div>
            <Link href="/logout" method="post" as="button" class="sales-logout"><ArrowRightOnRectangleIcon /><span>Keluar dari akun</span></Link>
        </div>
    </div>
</template>
