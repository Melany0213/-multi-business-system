<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    negocios: Array,
});
</script>

<template>
    <Head title="Todos los negocios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Negocios (todas las cuentas)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Negocio</th>
                                <th class="px-6 py-3">Cuenta</th>
                                <th class="px-6 py-3">Rubro</th>
                                <th class="px-6 py-3">Tipo</th>
                                <th class="px-6 py-3">Moneda</th>
                                <th class="px-6 py-3">Almacenes</th>
                                <th class="px-6 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="negocio in negocios" :key="negocio.id" class="border-b border-border">
                                <td class="px-6 py-4 font-medium">
                                    <Link
                                        :href="route('admin.expedientes.negocio', negocio.id)"
                                        class="text-primary hover:underline"
                                    >
                                        {{ negocio.nombre }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">
                                    <Link
                                        :href="route('admin.cuentas.show', negocio.account_id)"
                                        class="hover:underline"
                                    >
                                        {{ negocio.account?.nombre_cliente }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">{{ negocio.rubro?.nombre ?? '—' }}</td>
                                <td class="px-6 py-4 capitalize">{{ negocio.tipo }}</td>
                                <td class="px-6 py-4">{{ negocio.moneda }}</td>
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
                            </tr>
                            <tr v-if="negocios.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-text-3">
                                    Todavía no hay negocios registrados en ninguna cuenta.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
