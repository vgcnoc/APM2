<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { Bars3Icon, ChevronRightIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import AffiliateSidebar from '@/Components/AffiliateSidebar.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import PatternPicker from '@/Components/PatternPicker.vue';
import { affiliateSections, affiliateSection } from '@/Composables/affiliateNavigation';

const props = defineProps({ title: String, subtitle: String, section: String });
const page = usePage();
const mobileMenu = ref(false);
const currentSection = computed(() => props.section || affiliateSection(page.url, page.props.tab));
const currentLabel = computed(() => affiliateSections.find(item => item.id === currentSection.value)?.label || 'Dashboard Sales');
watch(() => page.url, () => { mobileMenu.value = false; });
let desktopMedia;
const closeMenuOnDesktop = event => { if (event.matches) mobileMenu.value = false; };
onMounted(() => {
    desktopMedia = window.matchMedia('(min-width: 1024px)');
    desktopMedia.addEventListener('change', closeMenuOnDesktop);
});
onUnmounted(() => desktopMedia?.removeEventListener('change', closeMenuOnDesktop));
</script>

<template>
    <Head :title="title || currentLabel" />
    <div class="affiliate-workspace apm-shell">
        <aside class="sales-sidebar apm-sidebar"><AffiliateSidebar :section="currentSection" /></aside>
        <div class="sales-workspace-main">
            <header class="sales-topbar apm-topbar">
                <div class="sales-breadcrumb">
                    <button type="button" class="sales-menu-toggle sales-icon-button" aria-label="Buka menu Sales" aria-controls="sales-mobile-menu" :aria-expanded="mobileMenu" @click="mobileMenu = true"><Bars3Icon /></button>
                    <span class="breadcrumb-root">Portal Sales</span><ChevronRightIcon class="breadcrumb-chevron" /><strong>{{ currentLabel }}</strong>
                </div>
                <div class="sales-topbar-actions">
                    <PatternPicker />
                    <ThemeToggle />
                    <Link v-if="currentSection !== 'booking'" href="/affiliate/booking/create" class="sales-booking-button"><PlusIcon /><span>Booking Baru</span></Link>
                </div>
            </header>
            <main id="sales-main-content" class="sales-main-content">
                <div v-if="page.props.flash?.success || page.props.flash?.error" role="status" class="sales-flash" :class="page.props.flash.error ? 'is-error' : 'is-success'">{{ page.props.flash.error || page.props.flash.success }}</div>
                <slot />
            </main>
            <footer class="sales-page-footer">
                <span>Copyright {{ new Date().getFullYear() }} &copy; <strong class="font-extrabold text-slate-800 dark:text-slate-100">VIRUZS</strong> - Smart Billing, Smarter Business.</span>
                <span>{{ page.props.company_name || page.props.app_name || 'ISP Manager' }} <span aria-hidden="true">/</span> Sales &amp; Affiliate</span>
            </footer>
        </div>

        <Dialog :open="mobileMenu" class="sales-mobile-dialog affiliate-workspace" @close="mobileMenu = false">
            <div class="sales-menu-backdrop" aria-hidden="true"></div>
            <DialogPanel id="sales-mobile-menu" class="sales-mobile-panel apm-sidebar">
                <DialogTitle class="sr-only">Menu Sales &amp; Afiliasi</DialogTitle>
                <button type="button" class="sales-close-menu sales-icon-button" aria-label="Tutup menu Sales" @click="mobileMenu = false"><XMarkIcon /></button>
                <AffiliateSidebar :section="currentSection" @navigate="mobileMenu = false" />
            </DialogPanel>
        </Dialog>
    </div>
</template>

<style>
.affiliate-workspace { --sales-canvas: #f5f7fb; --sales-surface: #fff; --sales-subtle: #f0f4f9; --sales-text: #1b2940; --sales-muted: #687b95; --sales-line: #e4eaf2; --sales-accent: #326baf; --sales-accent-soft: #e9f1fc; font-family: 'Inter', system-ui, sans-serif; color: var(--sales-text); background: var(--sales-canvas); min-height: 100svh; }
html.dark .affiliate-workspace { --sales-canvas: #0b111c; --sales-surface: #121d2d; --sales-subtle: #182538; --sales-text: #e7eef9; --sales-muted: #96a8c0; --sales-line: #94aec426; --sales-accent: #9bc8f1; --sales-accent-soft: #7caeee14; }
.sales-sidebar { position: fixed; inset: 0 auto 0 0; width: 252px; z-index: 35; background: var(--sales-surface); border-right: 1px solid var(--sales-line); }
.sales-sidebar-content { height: 100%; display: flex; flex-direction: column; overflow: hidden; }
.sales-sidebar-content > :not(.sales-navigation) { flex-shrink: 0; }
.sales-brand { padding: 30px 26px 24px; }
.sales-brand > span { display: block; font-size: 8px; font-weight: 650; letter-spacing: .18em; color: var(--sales-muted); margin-top: 12px; }
html.dark .sales-brand svg > path { fill: #112437; stroke: #112437; }
.sales-workspace-label { margin: 0 20px 24px; padding: 11px 13px; display: flex; gap: 9px; align-items: center; border: 1px solid var(--sales-line); border-radius: 9px; color: var(--sales-muted); font-size: 10px; }
.workspace-dot { width: 5px; height: 5px; border-radius: 50%; background: #53ab95; }
.sales-navigation { padding: 0 15px 22px; flex: 1; min-height: 0; overflow-y: auto; }
.sales-nav-group + .sales-nav-group { margin-top: 28px; }
.sales-nav-group > p { color: var(--sales-muted); font-weight: 600; font-size: 9px; letter-spacing: .13em; margin: 0 13px 12px; }
.sales-nav-link { display: flex; gap: 12px; align-items: center; color: var(--sales-muted); padding: 13px 14px; margin-top: 4px; font-size: 11px; font-weight: 550; border: 1px solid transparent; border-radius: 9px; transition: background .18s, color .18s; }
.sales-nav-link > svg { width: 18px; height: 18px; flex-shrink: 0; stroke-width: 1.6; }
.sales-nav-link > span { flex: 1; }
.sales-nav-link:hover { color: var(--sales-text); background: var(--sales-subtle); }
.sales-nav-link.is-active { color: var(--sales-accent); background: var(--sales-accent-soft); border-color: #7caeee29; }
.sales-nav-link .nav-chevron { width: 12px; height: 12px; }
.sales-sidebar-footer { padding: 18px 20px 20px; border-top: 1px solid var(--sales-line); }
.sales-access-note { display: flex; gap: 9px; color: var(--sales-muted); font-size: 10px; line-height: 1.6; margin-bottom: 22px; }
.sales-access-note > svg { width: 17px; height: 17px; flex-shrink: 0; color: #6c9caa; }
.sales-access-note p > span { display: block; font-size: 9px; margin-top: 3px; opacity: .85; }
.sales-user { display: flex; gap: 10px; align-items: center; }
.sales-avatar { width: 34px; height: 34px; flex-shrink: 0; border: 1px solid var(--sales-line); display: grid; place-items: center; border-radius: 10px; background: var(--sales-subtle); color: var(--sales-accent); font-size: 10px; font-weight: 650; }
.sales-user > div { min-width: 0; }
.sales-user strong { display: block; font-size: 10px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sales-user div > span { display: block; color: var(--sales-muted); font-size: 9px; margin-top: 4px; }
.sales-logout { display: flex; gap: 9px; align-items: center; margin-top: 18px; color: var(--sales-muted); font-size: 10px; border-radius: 7px; padding: 7px 0; width: 100%; }
.sales-logout:hover { color: #d66c7c; }
.sales-logout svg { width: 15px; height: 15px; }
.sales-workspace-main { margin-left: 252px; min-height: 100svh; display: flex; flex-direction: column; min-width: 0; }
.sales-topbar { position: sticky; top: 0; z-index: 25; min-height: 76px; display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 18px 34px; border-bottom: 1px solid var(--sales-line); background: var(--sales-surface); }
.sales-breadcrumb { display: flex; align-items: center; gap: 13px; font-size: 11px; min-width: 0; }
.breadcrumb-root { color: var(--sales-muted); }
.breadcrumb-chevron { width: 12px; height: 12px; color: var(--sales-muted); }
.sales-breadcrumb strong { font-weight: 600; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.sales-topbar-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.sales-booking-button, .sales-primary-action { display: inline-flex; justify-content: center; align-items: center; gap: 7px; padding: 11px 15px; border-radius: 9px; background: #316da8; border: 1px solid #6195c040; color: #fff; font-size: 11px; font-weight: 600; transition: background .18s; }
.sales-booking-button:hover, .sales-primary-action:hover { background: #3e7eb8; }
.sales-booking-button svg, .sales-primary-action svg { width: 15px; height: 15px; }
.sales-main-content { width: 100%; max-width: 1500px; margin: 0 auto; padding: 32px 34px; flex: 1; min-width: 0; }
.sales-page-footer { display: flex; justify-content: space-between; gap: 16px; padding: 0 34px 24px; color: var(--sales-muted); font-size: 9px; }
.sales-page-footer > span > span { margin: 0 8px; opacity: .6; }
.sales-flash { border: 1px solid; border-radius: 11px; padding: 14px 16px; font-size: 12px; margin-bottom: 20px; }
.sales-flash.is-success { color: #32816e; border-color: #65b99c40; background: #65b99c0d; }
.sales-flash.is-error { color: #b85265; border-color: #dc8c9b40; background: #dc8c9b0d; }
html.dark .sales-flash.is-success { color: #8bd0ba; }
html.dark .sales-flash.is-error { color: #eda5b2; }
.sales-icon-button { display: grid; place-items: center; height: 36px; width: 36px; border: 1px solid var(--sales-line); background: var(--sales-surface); border-radius: 10px; color: var(--sales-muted); }
.sales-icon-button svg { width: 20px; height: 20px; }
.sales-menu-toggle { display: none; flex-shrink: 0; }
.sales-mobile-dialog { position: fixed; inset: 0; z-index: 80; background: transparent; min-height: 0; }
html.dark .sales-mobile-dialog[role="dialog"], html.dark .sales-withdraw-dialog[role="dialog"] { background: transparent !important; }
.sales-menu-backdrop { position: fixed; inset: 0; background: #030a16aa; backdrop-filter: blur(4px); }
.sales-mobile-panel { position: fixed; inset: 0 auto 0 0; width: min(290px, calc(100vw - 36px)); height: 100dvh; background: var(--sales-surface); border-right: 1px solid var(--sales-line); box-shadow: 20px 0 80px #00000033; animation: sales-menu-in .2s ease-out; }
.sales-close-menu { position: absolute; right: 14px; top: 24px; width: 30px; height: 30px; }
.sales-mobile-panel .sales-brand { padding-right: 55px; }
.affiliate-workspace :is(a, button, input, select, textarea):focus-visible { outline: 2px solid var(--sales-accent); outline-offset: 3px; }
@keyframes sales-menu-in { from { transform: translateX(-100%); } to { transform: translateX(0); } }
@media (max-width: 1023px) {
    .sales-sidebar { display: none; }
    .sales-workspace-main { margin-left: 0; }
    .sales-menu-toggle { display: grid; }
    .sales-topbar { padding: 15px 24px; min-height: 68px; }
    .sales-main-content { padding: 26px 24px; }
    .sales-page-footer { padding: 0 24px 24px; }
}
@media (max-width: 600px) {
    .sales-topbar { padding: 12px 16px; gap: 12px; }
    .sales-breadcrumb { gap: 10px; font-size: 11px; }
    .breadcrumb-root, .breadcrumb-chevron { display: none; }
    .sales-topbar-actions { gap: 8px; }
    .sales-booking-button { padding: 10px; }
    .sales-booking-button > span { display: none; }
    .sales-booking-button::after { content: 'Booking'; font-size: 10px; }
    .sales-main-content { padding: 24px 16px; }
    .sales-page-footer { padding: 0 16px 24px; flex-wrap: wrap; gap: 7px; }
}
@media (prefers-reduced-motion: reduce) { .sales-mobile-panel { animation: none; } .sales-nav-link { transition: none; } }
</style>
