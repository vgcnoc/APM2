<template>
    <div class="space-y-2">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-2xl bg-gray-50 border border-gray-200 max-h-40 overflow-y-auto">
            <label
                v-for="technician in technicians"
                :key="technician.id"
                class="flex min-w-0 items-center gap-2 rounded-xl border p-2 text-xs font-semibold transition-colors cursor-pointer"
                :class="isUnavailable(technician.id)
                    ? 'border-rose-300 bg-rose-50 text-rose-900 hover:border-rose-400'
                    : 'border-gray-200 bg-white text-gray-800 hover:border-amber-400'"
                :title="availabilityTitle(technician)"
            >
                <input
                    type="checkbox"
                    :checked="isSelected(technician.id)"
                    :value="technician.id"
                    class="rounded text-amber-500 focus:ring-amber-400"
                    @change="toggleTechnician(technician.id, $event.target.checked)"
                />
                <span class="min-w-0 flex-1 truncate">{{ technician.name }}</span>
                <span
                    v-if="isUnavailable(technician.id)"
                    class="shrink-0 rounded-md border border-rose-200 bg-rose-100 px-1.5 py-0.5 text-[9px] font-extrabold uppercase text-rose-700"
                >
                    {{ availabilityFor(technician.id).status_label }}
                </span>
            </label>
        </div>

        <p v-if="loading" class="text-[11px] text-gray-500">Memeriksa roster teknisi…</p>
        <p v-else-if="unavailableTechnicians.length" class="text-[11px] font-medium text-rose-700">
            ⚠ {{ unavailableTechnicians.map((technician) => technician.name).join(', ') }}
            memiliki jadwal {{ unavailableTechnicians.length === 1 ? 'tidak tersedia' : 'tidak tersedia' }} pada tanggal ini.
        </p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    technicians: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
    scheduledDate: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);
const availability = ref({});
const loading = ref(false);
let requestSequence = 0;

const technicianIds = computed(() => props.technicians.map((technician) => technician.id).filter(Boolean));
const selectedIds = computed(() => new Set((props.modelValue || []).map((id) => String(id))));

const availabilityFor = (technicianId) => availability.value[String(technicianId)] || {};
const isUnavailable = (technicianId) => Boolean(availabilityFor(technicianId).is_unavailable);
const isSelected = (technicianId) => selectedIds.value.has(String(technicianId));

const availabilityTitle = (technician) => {
    const status = availabilityFor(technician.id);
    return status.is_unavailable
        ? `${technician.name}: ${status.status_label} pada ${props.scheduledDate}`
        : technician.name;
};

const unavailableTechnicians = computed(() => props.technicians.filter((technician) => isUnavailable(technician.id)));

const toggleTechnician = (technicianId, checked) => {
    const next = (props.modelValue || []).filter((id) => String(id) !== String(technicianId));
    if (checked) next.push(technicianId);
    emit('update:modelValue', next);
};

const fetchAvailability = async () => {
    const sequence = ++requestSequence;
    if (!props.scheduledDate || technicianIds.value.length === 0) {
        availability.value = {};
        return;
    }

    loading.value = true;
    const params = new URLSearchParams({ date: props.scheduledDate });
    technicianIds.value.forEach((id) => params.append('technician_ids[]', id));

    try {
        const response = await fetch(`/work-schedules/technician-availability?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Tidak dapat membaca roster teknisi.');

        const result = await response.json();
        if (sequence === requestSequence) {
            availability.value = result.technicians || {};
        }
    } catch {
        if (sequence === requestSequence) availability.value = {};
    } finally {
        if (sequence === requestSequence) loading.value = false;
    }
};

watch(
    () => [props.scheduledDate, technicianIds.value.join(',')],
    fetchAvailability,
    { immediate: true },
);
</script>
