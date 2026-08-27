<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition duration-150 ease-in-out"
        :class="active
            ? 'bg-primary-soft text-primary'
            : 'text-text-2 hover:bg-surface-2 hover:text-text'"
    >
        <slot name="icon" />
        <span><slot /></span>
    </Link>
</template>
