<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { usePattern } from '@/Composables/usePattern';

const { currentPattern, patterns, setPattern, initPattern } = usePattern();
const isOpen = ref(false);
const dropdownRef = ref(null);

onMounted(() => {
    initPattern();
    document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});

function handleOutsideClick(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

function select(id) {
    setPattern(id);
    isOpen.value = false;
}
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <!-- Toggle Button -->
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="pattern-picker-btn inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 transition-all hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-500 shadow-sm active:scale-95 cursor-pointer"
            :class="isOpen ? 'ring-2 ring-indigo-500/30 border-indigo-400 text-indigo-600 dark:text-indigo-400' : ''"
            title="Pilih Corak Background Aplikasi"
            aria-label="Pilih Corak Background Aplikasi"
        >
            <!-- Pattern / Palette Icon -->
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-72 sm:w-80 origin-top-right rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200/90 dark:border-slate-800 shadow-2xl p-2 z-50 focus:outline-none animate-in fade-in"
            >
                <!-- Dropdown Header -->
                <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800/80 mb-1 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">🎨</span>
                        <div>
                            <p class="text-xs font-black text-slate-900 dark:text-white leading-tight">Corak Background</p>
                            <p class="text-[10px] text-slate-400">Pilih tekstur & estetika latar</p>
                        </div>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60">
                        Modern
                    </span>
                </div>

                <!-- Pattern Options List -->
                <div class="space-y-1">
                    <button
                        v-for="p in patterns"
                        :key="p.id"
                        type="button"
                        @click="select(p.id)"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left transition-all group cursor-pointer"
                        :class="currentPattern === p.id 
                            ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-950 dark:text-white font-bold border border-indigo-200/70 dark:border-indigo-800/70 shadow-sm' 
                            : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/70 text-slate-700 dark:text-slate-300 border border-transparent'"
                    >
                        <!-- Pattern Preview Swatch -->
                        <div
                            class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border transition-transform group-hover:scale-105"
                            :class="currentPattern === p.id 
                                ? 'border-indigo-400 bg-indigo-100/70 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 shadow-sm' 
                                : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                        >
                            <span class="text-base select-none">{{ p.icon }}</span>
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <p class="text-xs font-bold truncate leading-tight">{{ p.name }}</p>
                                <span
                                    v-if="p.badge"
                                    class="text-[9px] px-1.5 py-0.2 rounded font-semibold tracking-wider shrink-0"
                                    :class="currentPattern === p.id 
                                        ? 'bg-indigo-600 text-white' 
                                        : 'bg-slate-200/70 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                                >
                                    {{ p.badge }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate mt-0.5">{{ p.subtitle }}</p>
                        </div>

                        <!-- Active Indicator -->
                        <div v-if="currentPattern === p.id" class="w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] shrink-0 shadow-sm">
                            ✓
                        </div>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
