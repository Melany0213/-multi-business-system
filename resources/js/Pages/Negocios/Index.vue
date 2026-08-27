<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    negocios: Array,
    puedeCrearNegocio: Boolean,
});

function toggleEstado(negocio) {
    router.patch(route('negocios.toggle-estado', negocio.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Mis negocios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">
                    Negocios
                </h2>
                <Link v-if="puedeCrearNegocio" :href="route('negocios.create')">
                    <PrimaryButton>Nuevo negocio</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    v-if="!puedeCrearNegocio"
                    class="mb-4 rounded-lg border border-warning bg-warning-soft p-4 text-sm text-warning"
                >
                    Alcanzaste el límite de negocios de tu plan actual. Contacta al Super Admin para ampliarlo.
                </div>

                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Rubro</th>
                                <th class="px-6 py-3">Tipo</th>
                                <th class="px-6 py-3">Almacenes</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="negocio in negocios" :key="negocio.id" class="border-b border-border">
                                <td class="px-6 py-4 font-medium">{{ negocio.nombre }}</td>
                                <td class="px-6 py-4">{{ negocio.rubro?.nombre ?? '—' }}</td>
                                <td class="px-6 py-4 capitalize">{{ negocio.tipo }}</td>
                                <td class="px-6 py-4 font-mono">{{ negocio.almacenes_count }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="negocio.estado === 'activo'
                                            ? 'bg-success-soft text-success'
                                            : 'bg-danger-soft text-danger'"
                                    >
                                        {{ negocio.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Link
                                        :href="route('negocios.edit', negocio.id)"
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        @click="toggleEstado(negocio)"
                                        class="text-sm font-medium hover:underline"
                                        :class="negocio.estado === 'activo' ? 'text-danger' : 'text-success'"
                                    >
                                        {{ negocio.estado === 'activo' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="negocios.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-text-3">
                                    Aún no tienes negocios. Crea el primero para empezar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
