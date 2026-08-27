<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    almacenes: Array,
});

function toggleEstado(almacen) {
    router.patch(route('almacenes.toggle-estado', almacen.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Almacenes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">Almacenes</h2>
                <Link :href="route('almacenes.create')">
                    <PrimaryButton>Nuevo almacén</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Productos</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="almacen in almacenes" :key="almacen.id" class="border-b border-border">
                                <td class="px-6 py-4 font-medium">{{ almacen.nombre }}</td>
                                <td class="px-6 py-4 font-mono">{{ almacen.productos_count }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="almacen.estado === 'activo'
                                            ? 'bg-success-soft text-success'
                                            : 'bg-danger-soft text-danger'"
                                    >
                                        {{ almacen.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Link
                                        :href="route('almacenes.edit', almacen.id)"
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        @click="toggleEstado(almacen)"
                                        class="text-sm font-medium hover:underline"
                                        :class="almacen.estado === 'activo' ? 'text-danger' : 'text-success'"
                                    >
                                        {{ almacen.estado === 'activo' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="almacenes.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-text-3">
                                    Aún no hay almacenes en este negocio.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
