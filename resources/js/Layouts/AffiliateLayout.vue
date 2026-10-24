<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

defineProps({ title: String });
const page = usePage();
const navigation = computed(() => [
    { label: 'Dashboard Sales', href: '/affiliate/dashboard', active: page.url.startsWith('/affiliate/dashboard') && !page.url.includes('tab=downlines') },
    { label: 'Booking Baru', href: '/affiliate/booking/create', active: page.url.startsWith('/affiliate/booking') },
    { label: 'Afiliasi & Downline Saya', href: '/affiliate/dashboard?tab=downlines', active: page.url.includes('tab=downlines') },
]);
</script>

<template>
    <Head :title="title || 'Dashboard Sales Afiliasi'" />
    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans">
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-12 shrink-0"><AppLogo context="desktop" :sidebar-collapsed="true" /></div>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold tracking-tight">Portal Sales &amp; Afiliasi</p>
                        <p class="text-xs text-slate-500 truncate">{{ page.props.auth?.user?.name }}</p>
                    </div>
                </div>
                <Link href="/logout" method="post" as="button" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold hover:bg-slate-50">Keluar</Link>
            </div>
            <nav aria-label="Menu Sales Afiliasi" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-3 flex gap-2 overflow-x-auto">
                <Link v-for="item in navigation" :key="item.href" :href="item.href" :aria-current="item.active ? 'page' : undefined"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold whitespace-nowrap transition-colors"
                    :class="item.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'">
                    {{ item.label }}
                </Link>
            </nav>
        </header>
        <main>
            <div v-if="page.props.flash?.success || page.props.flash?.error" role="status" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
                <p class="p-4 rounded-xl border text-sm" :class="page.props.flash.error ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'">
                    {{ page.props.flash.error || page.props.flash.success }}
                </p>
            </div>
            <slot />
        </main>
    </div>
</template>
