<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    negocios: Array,
});

function iniciales(nombre) {
    return nombre
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Mis negocios
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <p v-if="negocios.length === 0" class="text-text-3">
                        No tienes acceso vigente a ningún negocio en este momento.
                    </p>

                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="negocio in negocios"
                            :key="negocio.id"
                            class="grid gap-3 rounded-lg border border-border p-5 transition hover:border-primary"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-soft font-mono text-sm font-semibold text-primary"
                                >
                                    {{ iniciales(negocio.nombre) }}
                                </div>
                                <div>
                                    <p class="font-medium text-text">{{ negocio.nombre }}</p>
                                    <p class="text-xs text-text-3">
                                        {{ negocio.rubro ?? 'Sin rubro' }} · <span class="capitalize">{{ negocio.tipo }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <span class="rounded-full bg-primary-soft px-2.5 py-1 text-xs font-semibold capitalize text-primary">
                                    {{ negocio.rol }}
                                </span>
                                <span class="rounded-full bg-info-soft px-2.5 py-1 text-xs font-semibold capitalize text-info">
                                    Vigencia {{ negocio.vigencia }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
