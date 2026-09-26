<template>
    <span :class="badgeClass">
        <span v-if="dot" class="w-1.5 h-1.5 rounded-full mr-1.5" :class="dotClass"></span>
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    dot: { type: Boolean, default: true },
});

const statusMap = {
    // Customer
    booking: { label: 'Booking', color: 'yellow' },
    survey: { label: 'Survey', color: 'blue' },
    installing: { label: 'Pemasangan', color: 'indigo' },
    active: { label: 'Aktif', color: 'green' },
    suspended: { label: 'Suspended', color: 'orange' },
    terminated: { label: 'Terminated', color: 'red' },
    // Infrastructure
    inactive: { label: 'Nonaktif', color: 'gray' },
    maintenance: { label: 'Maintenance', color: 'yellow' },
    full: { label: 'Penuh', color: 'red' },
    // ONT
    los: { label: 'LOS', color: 'red' },
    damaged: { label: 'Rusak', color: 'red' },
    // Ticket
    open: { label: 'Open', color: 'red' },
    in_progress: { label: 'In Progress', color: 'amber' },
    resolved: { label: 'Resolved', color: 'green' },
    closed: { label: 'Closed', color: 'gray' },
    // Invoice
    unpaid: { label: 'Belum Bayar', color: 'red' },
    paid: { label: 'Lunas', color: 'green' },
    overdue: { label: 'Terlambat', color: 'orange' },
    cancelled: { label: 'Dibatalkan', color: 'gray' },
    // Survey
    feasible: { label: 'Layak', color: 'green' },
    not_feasible: { label: 'Tidak Layak', color: 'red' },
    conditional: { label: 'Bersyarat', color: 'yellow' },
};

const config = computed(() => statusMap[props.status] || { label: props.status, color: 'gray' });
const label = computed(() => config.value.label);

const colorClasses = {
    green: 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/30',
    blue: 'bg-blue-500/20 text-blue-400 ring-1 ring-blue-500/30',
    yellow: 'bg-yellow-500/20 text-yellow-400 ring-1 ring-yellow-500/30',
    red: 'bg-red-500/20 text-red-400 ring-1 ring-red-500/30',
    orange: 'bg-orange-500/20 text-orange-400 ring-1 ring-orange-500/30',
    indigo: 'bg-indigo-500/20 text-indigo-400 ring-1 ring-indigo-500/30',
    amber: 'bg-amber-500/20 text-amber-400 ring-1 ring-amber-500/30',
    gray: 'bg-gray-500/20 text-gray-500 ring-1 ring-gray-500/30',
};

const dotColors = {
    green: 'bg-emerald-400',
    blue: 'bg-blue-400',
    yellow: 'bg-yellow-400',
    red: 'bg-red-400',
    orange: 'bg-orange-400',
    indigo: 'bg-indigo-400',
    amber: 'bg-amber-400',
    gray: 'bg-gray-400',
};

const badgeClass = computed(() => [
    'badge',
    colorClasses[config.value.color] || colorClasses.gray,
]);

const dotClass = computed(() => dotColors[config.value.color] || dotColors.gray);
</script>
