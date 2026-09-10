<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ intervenciones: Object });

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '—';
}
</script>

<template>
    <Head title="Intervenciones" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-text">Intervenciones de soporte</h2>
                <p class="mt-1 text-sm text-text-3">
                    Cada vez que el soporte escribió en el negocio de un cliente, con su motivo y su acta.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-x-auto rounded-lg border border-border bg-surface">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Negocio</th>
                                <th class="px-4 py-3">Motivo</th>
                                <th class="px-4 py-3">Origen</th>
                                <th class="px-4 py-3">Abierta</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Cambios</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="i in intervenciones.data"
                                :key="i.id"
                                class="border-b border-border align-top"
                                :class="i.vigente && 'bg-red-500/5'"
                            >
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.intervenciones.show', i.id)" class="text-primary hover:underline">
                                        {{ i.id }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.expedientes.negocio', i.negocio_id)" class="hover:underline">
                                        {{ i.negocio }}
                                    </Link>
                                    <div class="text-xs text-text-3">{{ i.cuenta }}</div>
                                </td>
                                <td class="max-w-md px-4 py-3 text-text-2">{{ i.motivo }}</td>
                                <td class="px-4 py-3 text-text-3">
                                    {{ i.origen === 'solicitud' ? `pedido del dueño (#${i.solicitud_id})` : 'iniciativa del soporte' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-text-3">
                                    {{ fecha(i.abierta_at) }}
                                    <div class="text-xs">{{ i.super_admin }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span
                                        v-if="i.vigente"
                                        class="rounded bg-red-500/10 px-2 py-0.5 text-xs font-medium text-red-600 dark:text-red-400"
                                    >activa · {{ i.minutos_restantes }} min</span>
                                    <span v-else class="text-text-3">{{ i.estado }}</span>
                                </td>
                                <td class="px-4 py-3 text-text-3">{{ i.total_movimientos ?? '—' }}</td>
                            </tr>
                            <tr v-if="!intervenciones.data.length">
                                <td colspan="7" class="px-4 py-10 text-center text-text-3">
                                    El soporte nunca escribió en el negocio de un cliente.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Paginacion :links="intervenciones.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
