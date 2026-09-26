<template>
    <div :class="['stat-card', color]">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1">{{ title }}</p>
                <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ formattedValue }}</p>
                <p v-if="subtitle" class="text-xs text-gray-500 mt-1">{{ subtitle }}</p>
            </div>
            <div :class="['p-3 rounded-xl', iconBgClass]">
                <slot name="icon">
                    <svg class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath"/>
                    </svg>
                </slot>
            </div>
        </div>

        <!-- Optional Trend -->
        <div v-if="trend" class="flex items-center gap-1 mt-3">
            <svg v-if="trend > 0" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            <svg v-else class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
            </svg>
            <span :class="trend > 0 ? 'text-emerald-400' : 'text-red-400'" class="text-xs font-medium">
                {{ Math.abs(trend) }}%
            </span>
            <span class="text-xs text-gray-500">dari bulan lalu</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
    subtitle: { type: String, default: '' },
    color: { type: String, default: 'blue' },
    icon: { type: String, default: 'chart' },
    trend: { type: Number, default: null },
    isCurrency: { type: Boolean, default: false },
});

const formattedValue = computed(() => {
    if (props.isCurrency) {
        return 'Rp ' + Number(props.value).toLocaleString('id-ID');
    }
    return Number(props.value).toLocaleString('id-ID');
});

const iconPaths = {
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    network: 'M13 10V3L4 14h7v7l9-11h-7z',
    ticket: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    money: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    server: 'M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
};

const iconPath = computed(() => iconPaths[props.icon] || iconPaths.chart);

const bgColors = {
    blue: 'bg-blue-500/10',
    green: 'bg-emerald-500/10',
    yellow: 'bg-yellow-500/10',
    red: 'bg-red-500/10',
    purple: 'bg-purple-500/10',
    indigo: 'bg-indigo-500/10',
};

const textColors = {
    blue: 'text-blue-400',
    green: 'text-emerald-400',
    yellow: 'text-yellow-400',
    red: 'text-red-400',
    purple: 'text-purple-400',
    indigo: 'text-indigo-400',
};

const iconBgClass = computed(() => bgColors[props.color] || bgColors.blue);
const iconColorClass = computed(() => textColors[props.color] || textColors.blue);
</script>
