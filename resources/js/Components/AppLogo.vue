<template>
    <div class="flex flex-col items-center justify-center">
        <!-- Logo from Settings -->
        <img v-if="$page.props.app_logo" 
             :src="$page.props.app_logo" 
             class="object-contain w-auto transition-all duration-300" 
             :class="sizeClasses"
             alt="Application Logo" />
             
        <!-- Default Fallback Logo (rendered as a single SVG) -->
        <div v-else class="flex items-center" :class="[sidebarCollapsed ? 'justify-center w-full' : '']">
            <!-- Full Logo SVG for fallback -->
            <svg v-if="!sidebarCollapsed" :class="sizeClasses" class="text-blue-600" viewBox="0 0 200 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Icon background -->
                <rect width="40" height="40" y="4" rx="10" fill="currentColor"/>
                <!-- Lightning Icon -->
                <path d="M22 14V7L13 18H20V25L29 14H22Z" fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Text: Dynamic App Name -->
                <text x="52" y="24" fill="#111827" font-family="system-ui, sans-serif" font-size="18" font-weight="800">{{ $page.props.app_name !== null ? $page.props.app_name : 'ISP Manager' }}</text>
                <text v-if="$page.props.app_name !== ''" x="52" y="38" fill="currentColor" font-family="system-ui, sans-serif" font-size="10" font-weight="700" letter-spacing="0.1em">ENTERPRISE</text>
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
    // Used to adjust size in different contexts (sidebar vs header vs mobile)
    context: {
        type: String,
        default: 'desktop' // desktop, mobile, tablet, login
    }
});

const page = usePage();
const imageError = ref(false);

const handleImageError = () => {
    imageError.value = true;
};

// Reset image error state if logo URL changes
watch(() => page.props.app_logo, () => {
    imageError.value = false;
});

// Calculate sizes for the uploaded image based on context and state
const sizeClasses = computed(() => {
    if (props.sidebarCollapsed) {
        return 'h-[32px] sm:h-[40px] w-auto px-1'; // Shrink when sidebar collapsed
    }
    
    switch (props.context) {
        case 'mobile':
            return 'h-[32px] sm:h-[40px] w-auto';
        case 'tablet':
            return 'h-[36px] sm:h-[44px] w-auto';
        case 'login':
            return 'h-[48px] sm:h-[64px] w-auto mb-6';
        default:
            return 'h-[40px] sm:h-[48px] w-auto';
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
