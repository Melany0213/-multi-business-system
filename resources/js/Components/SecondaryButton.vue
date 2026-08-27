<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-lg border border-border-strong bg-surface px-4 py-2.5 text-sm font-medium text-text transition duration-150 ease-in-out hover:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface disabled:opacity-25"
    >
        <slot />
    </button>
</template>
