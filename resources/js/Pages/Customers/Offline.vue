<template>
    <OnlineView v-bind="$props" />
</template>

<script setup>
import OnlineView from './Online.vue';

defineProps({
    customers: { type: Object, required: true },
    areas: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            online: 0,
            offline: 0,
            health_rate: 0,
            pppoe_count: 0,
            hotspot_count: 0,
        })
    },
    totalOnline: { type: Number, default: 0 },
    totalOffline: { type: Number, default: 0 },
});
</script>
