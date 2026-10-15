<template>
    <template v-if="isAllowed">
        <slot />
    </template>
    <template v-else>
        <slot name="fallback" />
    </template>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { checkUserPermission } from '@/Directives/can';

const props = defineProps({
    permission: {
        type: [String, Array],
        required: true,
    },
    all: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

const isAllowed = computed(() => {
    const user = page.props.auth?.user;
    return checkUserPermission(props.permission, props.all ? 'all' : undefined, user);
});
</script>
