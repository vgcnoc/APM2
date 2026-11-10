<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePattern } from '@/Composables/usePattern';

const { currentPattern, patterns, categories, setPattern, initPattern } = usePattern();
const isOpen = ref(false);
const dropdownRef = ref(null);
const selectedCategory = ref('all');

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

const activePatternObj = computed(() => {
    return patterns.find(p => p.id === currentPattern.value) || patterns[0];
});

const filteredPatterns = computed(() => {
    if (selectedCategory.value === 'all') return patterns;
    return patterns.filter(p => p.category === selectedCategory.value);
});

function randomize() {
    const others = patterns.filter(p => p.id !== currentPattern.value);
    if (!others.length) return;
    const picked = others[Math.floor(Math.random() * others.length)];
    setPattern(picked.id);
}
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <!-- Toggle Button -->
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="pattern-picker-btn relative inline-flex h-9 px-2.5 shrink-0 items-center gap-1.5 rounded-xl border transition-all focus-visible:outline focus-visible:outline-2 shadow-xs active:scale-95 cursor-pointer bg-white/90 dark:bg-slate-900/90 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700"
            :class="[
                isOpen ? 'ring-2 ring-indigo-500/30 dark:ring-indigo-500/40 border-indigo-400 dark:border-indigo-600' : ''
            ]"
            title="Pilih Corak & Warna Latar Aplikasi"
            aria-label="Pilih Corak & Warna Latar Aplikasi"
        >
            <!-- Current Theme Icon or Color Dot -->
            <span class="text-sm select-none">{{ activePatternObj.icon || '🎨' }}</span>
            <span
                class="w-2.5 h-2.5 rounded-full border border-white dark:border-slate-900 shadow-2xs shrink-0"
                :style="{ backgroundColor: activePatternObj.accentColor || '#6366f1' }"
            ></span>
            <svg class="w-3.5 h-3.5 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
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
                class="absolute right-0 mt-2 w-80 sm:w-96 origin-top-right rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200/90 dark:border-slate-800 shadow-2xl p-3 z-50 focus:outline-none animate-in fade-in"
            >
                <!-- Dropdown Header -->
                <div class="px-2 py-1.5 border-b border-slate-100 dark:border-slate-800/80 mb-2 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-base select-none">🎨</span>
                        <div class="min-w-0">
                            <p class="text-xs font-black text-slate-900 dark:text-white leading-tight truncate">Corak &amp; Warna Latar</p>
                            <p class="text-[10px] text-slate-400 truncate">13 Pilihan Corak 3D, NOC &amp; Estetika</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="randomize"
                        class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-all flex items-center gap-1 shrink-0 active:scale-95"
                        title="Acak Corak & Warna Secara Cerdas"
                    >
                        <span>🎲</span>
                        <span>Acak</span>
                    </button>
                </div>

                <!-- Category Tabs -->
                <div class="flex items-center gap-1 overflow-x-auto pb-1.5 mb-2 scrollbar-none">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="selectedCategory = cat.id"
                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold whitespace-nowrap transition-all cursor-pointer shrink-0"
                        :class="[
                            selectedCategory === cat.id
                                ? 'bg-indigo-600 text-white shadow-2xs'
                                : 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                        ]"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Pattern Options Scrollable List -->
                <div class="max-h-[60vh] overflow-y-auto space-y-1.5 pr-1" style="scrollbar-width: thin;">
                    <button
                        v-for="p in filteredPatterns"
                        :key="p.id"
                        type="button"
                        @click="select(p.id)"
                        class="w-full flex items-center gap-3 p-2 rounded-xl text-left transition-all group cursor-pointer border"
                        :class="[
                            currentPattern === p.id 
                                ? 'bg-slate-100/90 dark:bg-slate-800/90 font-bold border-indigo-400/80 dark:border-indigo-500/80 shadow-xs ring-1 ring-indigo-400/30'
                                : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-700 dark:text-slate-300 border-slate-100 dark:border-slate-800/60'
                        ]"
                    >
                        <!-- Pattern Preview Swatch -->
                        <div
                            class="w-11 h-11 rounded-xl shrink-0 border border-slate-200 dark:border-slate-700 overflow-hidden relative shadow-2xs transition-transform group-hover:scale-105"
                            :style="p.image ? { backgroundImage: `url(${p.image})`, backgroundSize: 'cover', backgroundPosition: 'center' } : { background: p.lightPreview || '#6366f1' }"
                        >
                            <!-- Mini overlay with icon -->
                            <div class="absolute inset-0 bg-black/20 dark:bg-black/40 flex items-center justify-center">
                                <span class="text-sm drop-shadow-md select-none">{{ p.icon }}</span>
                            </div>
                            <!-- Accent Dot in bottom-right corner -->
                            <div
                                class="absolute bottom-1 right-1 w-2.5 h-2.5 rounded-full border border-white dark:border-slate-900 shadow-xs"
                                :style="{ backgroundColor: p.accentColor || '#6366f1' }"
                            ></div>
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <p class="text-xs font-bold truncate text-slate-900 dark:text-white leading-tight">
                                    {{ p.name }}
                                </p>
                                <span
                                    v-if="p.badge"
                                    class="text-[9px] px-1.5 py-0.2 rounded font-semibold tracking-wider shrink-0"
                                    :class="[
                                        currentPattern === p.id 
                                            ? 'bg-indigo-600 text-white shadow-2xs'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    {{ p.badge }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-400 truncate mt-0.5 leading-snug">
                                {{ p.subtitle }}
                            </p>
                        </div>

                        <!-- Active Indicator -->
                        <div 
                            v-if="currentPattern === p.id" 
                            class="w-5 h-5 rounded-full text-white flex items-center justify-center text-[10px] font-black shrink-0 shadow-xs bg-indigo-600"
                            :style="p.accentColor ? { backgroundColor: p.accentColor } : {}"
                        >
                            ✓
                        </div>
                    </button>
                </div>

                <!-- Footer Note -->
                <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 px-1 flex items-center justify-between text-[10px] text-slate-400">
                    <span>💡 Otomatis tersimpan di browser</span>
                    <span class="font-mono text-[9px] text-slate-400">{{ activePatternObj.name }}</span>
                </div>
            </div>
        </transition>
    </div>
</template>
