<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { useTheme } from '@/theme';

const { isDark, toggle } = useTheme();
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-bg pt-6 sm:justify-center sm:pt-0"
    >
        <button
            type="button"
            @click="toggle"
            class="fixed right-4 top-4 rounded-full border border-border bg-surface px-3 py-1.5 text-xs font-medium text-text-2 shadow-sm hover:text-text"
        >
            {{ isDark ? 'Oscuro' : 'Claro' }}
        </button>

        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-primary" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-surface px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
