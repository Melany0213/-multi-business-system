<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TablaMovimientos from '@/Components/TablaMovimientos.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { Head, Link } from '@inertiajs/vue3';

/**
 * Expediente del usuario (RF-52). Acá es donde se ve "metió un dedo mal":
 * qué turnos cerró y con qué descuadre, qué anuló, y toda su línea de tiempo.
 */
defineProps({
    usuario: Object,
    accesos: Array,
    turnos: Array,
    ventasAnuladas: Array,
    movimientos: Object,
});

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '—';
}

function moneda(n) {
    return Number(n ?? 0).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function claseDescuadre(d) {
    if (d === null || d === undefined) return 'text-text-3';
    if (Math.abs(d) < 0.01) return 'text-emerald-600 dark:text-emerald-400';
    return 'text-red-600 dark:text-red-400';
}
</script>

<template>
    <Head :title="`Expediente · ${usuario.nombre}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-semibold leading-tight text-text">{{ usuario.nombre }}</h2>
                        <span
                            class="rounded px-2 py-0.5 text-xs font-medium"
                            :class="usuario.estado_global === 'activo'
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-red-500/10 text-red-600 dark:text-red-400'"
                        >{{ usuario.estado_global }}</span>
                        <span
                            v-if="usuario.es_super_admin"
                            class="rounded bg-primary-soft px-2 py-0.5 text-xs font-medium text-primary"
                        >Super Admin del Sistema</span>
                    </div>
                    <p class="mt-1 text-sm text-text-3">
                        {{ usuario.username }}
                        <span v-if="usuario.email"> · {{ usuario.email }}</span>
                        <span v-if="usuario.telefono"> · {{ usuario.telefono }}</span>
                    </p>
                </div>
                <span class="rounded-lg bg-surface-2 px-3 py-1.5 text-xs text-text-3">Solo lectura</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <!-- Accesos -->
                <div class="overflow-hidden rounded-lg border border-border bg-surface">
                    <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">
                        Dónde entra y con qué vigencia
                    </h3>
                    <table class="w-full text-left text-sm text-text">
                        <tbody>
                            <tr v-for="a in accesos" :key="a.id" class="border-b border-border">
                                <td class="px-4 py-3">
                                    <Link
                                        v-if="a.negocio_id"
                                        :href="route('admin.expedientes.negocio', a.negocio_id)"
                                        class="text-primary hover:underline"
                                    >{{ a.negocio }}</Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3">{{ a.rol }}</td>
                                <td class="px-4 py-3 text-text-3">{{ a.vigencia }}</td>
                                <td class="px-4 py-3 text-text-3">{{ a.estado }}</td>
                            </tr>
                            <tr v-if="!accesos.length">
                                <td class="px-4 py-8 text-center text-text-3">Este usuario no tiene accesos a ningún negocio.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Turnos -->
                <div class="overflow-x-auto rounded-lg border border-border bg-surface">
                    <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">
                        Sus turnos de caja
                        <span class="font-normal text-text-3">— el descuadre es por dónde empieza cualquier revisión</span>
                    </h3>
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Negocio</th>
                                <th class="px-4 py-3">Apertura</th>
                                <th class="px-4 py-3">Cierre</th>
                                <th class="px-4 py-3">Venta</th>
                                <th class="px-4 py-3">Salario</th>
                                <th class="px-4 py-3">Descuadre</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in turnos" :key="t.id" class="border-b border-border">
                                <td class="px-4 py-3 text-text-3">{{ t.id }}</td>
                                <td class="px-4 py-3">{{ t.negocio }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-text-3">{{ fecha(t.apertura) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-text-3">
                                    {{ t.estado === 'abierto' ? 'Abierto ahora' : fecha(t.cierre) }}
                                </td>
                                <td class="px-4 py-3">{{ moneda(t.venta) }}</td>
                                <td class="px-4 py-3">{{ moneda(t.salario) }}</td>
                                <td class="px-4 py-3 font-medium" :class="claseDescuadre(t.descuadre)">
                                    {{ t.descuadre === null ? '—' : moneda(t.descuadre) }}
                                </td>
                            </tr>
                            <tr v-if="!turnos.length">
                                <td colspan="7" class="px-4 py-8 text-center text-text-3">Nunca abrió un turno.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Ventas anuladas -->
                <div v-if="ventasAnuladas.length" class="overflow-hidden rounded-lg border border-border bg-surface">
                    <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">
                        Ventas anuladas en sus turnos
                    </h3>
                    <table class="w-full text-left text-sm text-text">
                        <tbody>
                            <tr v-for="v in ventasAnuladas" :key="v.id" class="border-b border-border">
                                <td class="px-4 py-3 text-text-3">#{{ v.id }}</td>
                                <td class="px-4 py-3">{{ v.negocio }}</td>
                                <td class="px-4 py-3">{{ moneda(v.monto) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-text-3">{{ fecha(v.fecha) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Línea de tiempo -->
                <div class="overflow-hidden rounded-lg border border-border bg-surface">
                    <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">
                        Todo lo que hizo en el sistema
                    </h3>
                    <TablaMovimientos :movimientos="movimientos.data" mostrar-negocio />
                </div>
                <Paginacion :links="movimientos.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
