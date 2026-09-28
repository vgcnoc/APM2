<template>
    <div class="glass-card overflow-hidden animate-fade-in-up">
        <!-- Table Header: Search & Filters -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <!-- Search -->
                <div class="relative w-full md:w-80">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        v-model="searchQuery"
                        @input="onSearch"
                        :placeholder="searchPlaceholder"
                        class="form-input pl-10"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto mt-2 md:mt-0">
                    <!-- Filters Slot -->
                    <slot name="filters" />

                    <!-- Action Buttons Slot -->
                    <slot name="actions" />
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th v-for="col in columns" :key="col.key" :class="col.class">
                            {{ col.label }}
                        </th>
                        <th v-if="$slots.rowActions" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!data || data.length === 0">
                        <td :colspan="columns.length + ($slots.rowActions ? 1 : 0)" class="text-center py-16">
                            <div class="flex flex-col items-center gap-4">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 text-sm font-medium">Tidak ada data ditemukan</p>
                            </div>
                        </td>
                    </tr>
                    <tr v-else v-for="(row, index) in data" :key="row.id || index" class="animate-fade-in-up" :style="{ animationDelay: `${index * 0.03}s` }">
                        <slot name="row" :row="row" :index="index" />
                        <td v-if="$slots.rowActions" class="text-right">
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
                                ? 'bg-blue-600 text-gray-900 shadow-sm'
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
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    pagination: { type: Object, default: null },
    searchPlaceholder: { type: String, default: 'Cari data...' },
    searchRoute: { type: String, default: '' },
});

const searchQuery = ref('');
let searchTimeout = null;

function onSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (props.searchRoute) {
            router.get(props.searchRoute, { search: searchQuery.value }, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    }, 300);
}
</script>
