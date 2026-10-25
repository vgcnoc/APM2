<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRightOnRectangleIcon, BanknotesIcon, BuildingLibraryIcon, ChartBarSquareIcon, ChevronRightIcon, ClipboardDocumentListIcon, PlusCircleIcon, ShieldCheckIcon, UserGroupIcon, UsersIcon } from '@heroicons/vue/24/outline';
import AppLogo from '@/Components/AppLogo.vue';
import { useTheme } from '@/Composables/useTheme';
import { affiliateSections, affiliateHref } from '@/Composables/affiliateNavigation';

defineProps({ section: String });
defineEmits(['navigate']);
const page = usePage();
const { isDarkMode } = useTheme();
const user = computed(() => page.props.auth?.user || {});
const initials = computed(() => (user.value.name || 'Sales').trim().split(/\s+/).slice(0, 2).map(name => name[0]).join('').toUpperCase());
const icons = { overview: ChartBarSquareIcon, booking: PlusCircleIcon, customers: UsersIcon, downlines: UserGroupIcon, commissions: ClipboardDocumentListIcon, withdrawals: BanknotesIcon, bank: BuildingLibraryIcon };
</script>

<template>
    <div class="sales-sidebar-content">
        <div class="sales-brand"><AppLogo context="navbar" :white="isDarkMode" /><span>SALES &amp; AFFILIATE</span></div>
        <div class="sales-workspace-label"><span class="workspace-dot"></span> Ruang kerja pribadi</div>
        <nav aria-label="Menu Sales Afiliasi" class="sales-navigation">
            <div v-for="group in [{ id: 'workspace', label: 'WORKSPACE' }, { id: 'finance', label: 'KEUANGAN' }]" :key="group.id" class="sales-nav-group">
                <p>{{ group.label }}</p>
                <Link v-for="item in affiliateSections.filter(item => item.group === group.id)" :key="item.id"
                    :href="affiliateHref(item.id)" :aria-current="section === item.id ? 'page' : undefined"
                    class="sales-nav-link" :class="{ 'is-active': section === item.id }" @click="$emit('navigate')">
                    <component :is="icons[item.icon]" /><span>{{ item.label }}</span><ChevronRightIcon v-if="section === item.id" class="nav-chevron" />
                </Link>
            </div>
        </nav>
        <div class="sales-sidebar-footer">
            <div class="sales-access-note"><ShieldCheckIcon /><p>Portal pribadi Anda<span>Data pelanggan dan jaringan sesuai akses akun.</span></p></div>
            <div class="sales-user"><span class="sales-avatar">{{ initials }}</span><div><strong>{{ user.name }}</strong><span>{{ page.props.is_employee ? 'Karyawan internal' : 'Sales & Afiliasi' }}</span></div></div>
            <Link href="/logout" method="post" as="button" class="sales-logout"><ArrowRightOnRectangleIcon /><span>Keluar dari akun</span></Link>
        </div>
    </div>
</template>
