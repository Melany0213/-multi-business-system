<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>

<template>
    <div v-if="links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="(link, index) in links"
            :key="index"
            :href="link.url ?? '#'"
            preserve-scroll
            preserve-state
            class="rounded-lg border px-3 py-1.5 text-sm"
            :class="[
                link.active ? 'border-primary bg-primary-soft text-primary' : 'border-border text-text-2',
                !link.url && 'pointer-events-none opacity-40',
            ]"
            v-html="link.label"
        />
    </div>
</template>
