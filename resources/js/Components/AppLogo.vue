<template>
    <div class="flex items-center justify-center">
        <!-- Logo from Settings (mengikuti Logo Perusahaan atau Logo Aplikasi) -->
        <img v-if="logoSrc && !imageError" 
             :src="logoSrc" 
             class="object-contain w-auto transition-all duration-300" 
             :class="sizeClasses"
             :alt="displayName" 
             @error="handleImageError" />
             
        <!-- Default Fallback Logo (rendered as a single SVG jika belum ada logo yang diupload) -->
        <div v-else class="flex items-center" :class="[sidebarCollapsed ? 'justify-center w-full' : '']">
            <!-- Full Logo SVG for fallback -->
            <svg v-if="!sidebarCollapsed" :class="sizeClasses" class="text-blue-600" viewBox="0 0 200 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Icon background -->
                <rect width="40" height="40" y="4" rx="10" fill="currentColor"/>
                <!-- Lightning Icon -->
                <path d="M22 14V7L13 18H20V25L29 14H22Z" fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Text: Dynamic App/Company Name -->
                <text x="52" y="24" fill="#111827" font-family="system-ui, sans-serif" font-size="17" font-weight="800">{{ displayName }}</text>
                <text v-if="displayName !== ''" x="52" y="38" fill="currentColor" font-family="system-ui, sans-serif" font-size="10" font-weight="700" letter-spacing="0.1em">OFFICIAL PORTAL</text>
            </svg>
            
            <!-- Icon-only SVG for collapsed sidebar -->
            <svg v-else :class="sizeClasses" class="text-blue-600 max-w-[40px]" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="10" fill="currentColor"/>
                <path d="M22 10V3L13 14H20V21L29 10H22Z" fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    sidebarCollapsed: {
        type: Boolean,
        default: false
    },
    // Used to adjust size in different contexts (sidebar vs header vs mobile vs navbar vs login)
    context: {
        type: String,
        default: 'desktop' // desktop, mobile, tablet, login, navbar
    },
    preferCompanyLogo: {
        type: Boolean,
        default: false
    }
});

const page = usePage();
const imageError = ref(false);

const handleImageError = () => {
    imageError.value = true;
};

// Prioritaskan logo perusahaan atau logo aplikasi sesuai preferensi konteks
const logoSrc = computed(() => {
    if (props.preferCompanyLogo) {
        return page.props.company_logo || page.props.app_logo || null;
    }
    return page.props.app_logo || page.props.company_logo || null;
});

const displayName = computed(() => {
    if (props.preferCompanyLogo) {
        return page.props.company_name || page.props.app_name || 'ISP Manager';
    }
    return page.props.app_name || page.props.company_name || 'ISP Manager';
});

// Reset image error state if logo URL changes
watch([() => page.props.app_logo, () => page.props.company_logo], () => {
    imageError.value = false;
});

// Calculate sizes for the uploaded image based on context and state
const sizeClasses = computed(() => {
    if (props.sidebarCollapsed) {
        return 'h-[32px] sm:h-[40px] max-w-[48px] w-auto px-1'; // Shrink when sidebar collapsed
    }
    
    switch (props.context) {
        case 'mobile':
            return 'h-[30px] sm:h-[36px] max-w-[140px] w-auto';
        case 'tablet':
            return 'h-[34px] sm:h-[40px] max-w-[160px] w-auto';
        case 'navbar':
            return 'h-[34px] sm:h-[40px] max-w-[180px] w-auto';
        case 'login':
            return 'h-[48px] sm:h-[64px] max-w-[240px] w-auto mb-4';
        default:
            return 'h-[38px] sm:h-[44px] max-w-[180px] w-auto';
    }
});

// Fallback sizes
const iconSizeClasses = computed(() => {
    switch (props.context) {
        case 'login': return 'w-12 h-12 sm:w-14 sm:h-14';
        default: return 'w-9 h-9 sm:w-10 sm:h-10';
    }
});
const svgSizeClasses = computed(() => {
    switch (props.context) {
        case 'login': return 'w-7 h-7 sm:w-8 sm:h-8';
        default: return 'w-5 h-5 sm:w-6 sm:h-6';
    }
});
const titleSizeClasses = computed(() => {
    switch (props.context) {
        case 'login': return 'text-xl sm:text-2xl';
        default: return 'text-base sm:text-lg';
    }
});
const subtitleSizeClasses = computed(() => {
    switch (props.context) {
        case 'login': return 'text-xs sm:text-sm';
        default: return 'text-[10px] sm:text-[11px]';
    }
});
</script>
