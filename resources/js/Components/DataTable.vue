<template>
    <div class="glass-card w-full min-w-0 overflow-hidden">
        <!-- Table Header: Search & Filters -->
        <div class="p-3.5 sm:p-4 border-b border-gray-200 space-y-3.5">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <!-- Search -->
                <div :class="['relative min-w-0 flex-1', $slots.actions ? 'max-w-full lg:max-w-md xl:max-w-lg' : 'w-full sm:max-w-md lg:max-w-xl']">
                    <!-- Search Icon / Loading Spinner -->
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 flex items-center pointer-events-none z-10">
                        <svg v-if="!isSearching" class="w-4 h-4 text-gray-400 transition-colors" :class="{ '!text-indigo-600': isFocused }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <svg v-else class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <input
                        ref="searchInputRef"
                        type="text"
                        v-model="searchQuery"
                        @input="onInput"
                        @focus="onFocus"
                        @blur="onBlur"
                        @keydown="onKeydown"
                        :placeholder="searchPlaceholder"
                        autocomplete="off"
                        spellcheck="false"
                        class="form-input !pl-10 !pr-10 w-full rounded-xl border-gray-200 bg-white/95 text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-2xs transition-all"
                    />

                    <!-- Clear Button -->
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100 transition-colors cursor-pointer z-10"
                        title="Hapus pencarian (Esc)"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Action Buttons Slot -->
                <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:gap-3 w-full lg:w-auto">
                    <slot name="actions" />
                </div>
            </div>

            <!-- Filters Slot -->
            <div v-if="$slots.filters" class="p-3 sm:px-4 sm:py-3 bg-slate-50/70 border border-slate-100 rounded-xl flex flex-wrap items-center gap-2.5 sm:gap-3 w-full min-w-0 shadow-sm">
                <slot name="filters" />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto w-full overscroll-x-contain">
            <table class="data-table min-w-full">
                <thead>
                    <tr>
                        <th v-if="selectable" class="w-10 text-center px-3">
                            <input type="checkbox" 
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 shadow-sm" 
                                :checked="isAllSelected" 
                                @change="toggleAll" />
                        </th>
                        <th v-for="col in columns" :key="col.key" :class="col.class">
                            {{ col.label }}
                        </th>
                        <th v-if="$slots.rowActions" class="text-right pr-6 whitespace-nowrap min-w-[140px]">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!data || data.length === 0">
                        <td :colspan="columns.length + (selectable ? 1 : 0) + ($slots.rowActions ? 1 : 0)" class="text-center py-16">
                            <slot name="empty">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-slate-700 text-sm font-bold">{{ emptyTitle }}</p>
                                        <p v-if="emptySubtitle" class="text-slate-400 text-xs">{{ emptySubtitle }}</p>
                                    </div>
                                </div>
                            </slot>
                        </td>
                    </tr>
                    <tr v-else v-for="(row, index) in data" :key="row.id || index">
                        <td v-if="selectable" class="w-10 text-center px-3">
                            <input type="checkbox" 
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 shadow-sm" 
                                :value="row.id" 
                                v-model="localSelected" />
                        </td>
                        <slot name="row" :row="row" :index="index" />
                        <td v-if="$slots.rowActions" class="text-right pr-6 whitespace-nowrap min-w-[140px]">
                            <slot name="rowActions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination" class="px-5 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-0">
            <div class="text-sm text-gray-500 text-center sm:text-left">
                Menampilkan <span class="text-gray-900 font-bold">{{ pagination.from || 0 }}</span>
                - <span class="text-gray-900 font-bold">{{ pagination.to || 0 }}</span>
                dari <span class="text-gray-900 font-bold">{{ pagination.total || 0 }}</span> data
            </div>
            <div class="flex flex-wrap items-center justify-center gap-1">
                <template v-for="link in pagination.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200',
                            link.active
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm text-gray-500 font-medium"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    pagination: { type: Object, default: null },
    searchPlaceholder: { type: String, default: 'Cari data...' },
    searchRoute: { type: String, default: '' },
    selectable: { type: Boolean, default: false },
    selected: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    emptyTitle: { type: String, default: 'Tidak ada data ditemukan' },
    emptySubtitle: { type: String, default: '' },
});

const emit = defineEmits(['update:selected']);



const localSelected = computed({
    get: () => props.selected,
    set: (val) => emit('update:selected', val)
});

const isAllSelected = computed(() => {
    return props.data && props.data.length > 0 && props.data.every(row => localSelected.value.includes(row.id));
});

function toggleAll(e) {
    if (e.target.checked) {
        // Add all current page items that aren't already selected
        const currentIds = props.data.map(r => r.id);
        const newSelected = [...localSelected.value];
        currentIds.forEach(id => {
            if (!newSelected.includes(id)) newSelected.push(id);
        });
        localSelected.value = newSelected;
    } else {
        // Remove all current page items from selected
        const currentIds = props.data.map(r => r.id);
        localSelected.value = localSelected.value.filter(id => !currentIds.includes(id));
    }
}

const page = usePage();
const searchInputRef = ref(null);
const searchQuery = ref(typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('search') || '') : '');
const isFocused = ref(false);
const isSearching = ref(false);
let searchTimeout = null;

// Watch for external URL changes (e.g. browser back/forward or tab clicks),
// but NEVER overwrite what the user is currently typing in the input!
watch(() => page.url, () => {
    if (isFocused.value) return; // Do not interrupt or overwrite active typing!
    if (typeof window !== 'undefined') {
        const currentUrlSearch = new URLSearchParams(window.location.search).get('search') || '';
        if (searchQuery.value !== currentUrlSearch) {
            searchQuery.value = currentUrlSearch;
        }
    }
});

function onFocus() {
    isFocused.value = true;
}

function onBlur() {
    isFocused.value = false;
    // If user typed and immediately clicked away or blurred before debounce fired, execute search immediately:
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
        executeSearch(searchQuery.value);
    }
}

function onKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimeout);
        searchTimeout = null;
        executeSearch(searchQuery.value);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        if (searchQuery.value) {
            clearSearch();
        } else {
            searchInputRef.value?.blur();
        }
    }
}

function onInput() {
    clearTimeout(searchTimeout);
    // 400ms smooth debounce: responsive yet gives user time to type whole words comfortably
    searchTimeout = setTimeout(() => {
        searchTimeout = null;
        executeSearch(searchQuery.value);
    }, 400);
}

function clearSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = null;
    searchQuery.value = '';
    executeSearch('');
    searchInputRef.value?.focus();
}

function executeSearch(query) {
    if (!props.searchRoute) return;

    const [baseRoute, existingQuery] = props.searchRoute.split('?');
    const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams();

    if (existingQuery) {
        const parsedExisting = new URLSearchParams(existingQuery);
        for (const [k, v] of parsedExisting.entries()) {
            urlParams.set(k, v);
        }
    }

    if (props.filters && typeof props.filters === 'object') {
        for (const [k, v] of Object.entries(props.filters)) {
            if (v !== undefined && v !== null && v !== '') {
                urlParams.set(k, v);
            } else {
                urlParams.delete(k);
            }
        }
    }

    const queryParams = Object.fromEntries(urlParams);
    delete queryParams.page; // Reset pagination to first page on new search query

    const cleanQuery = (query ?? '').trim();
    if (cleanQuery) {
        queryParams.search = cleanQuery;
    } else {
        delete queryParams.search;
    }

    isSearching.value = true;
    router.get(baseRoute, queryParams, {
        preserveState: true,
        preserveScroll: true,
        replace: true, // Crucial: avoid polluting history stack with intermediate keystrokes
        onFinish: () => {
            isSearching.value = false;
        },
    });
}
</script>
