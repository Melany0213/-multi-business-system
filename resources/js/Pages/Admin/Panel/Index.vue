<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    metricas: Object,
    cuentasPorPlan: Array,
    operacion: Object,
});

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es', { style: 'currency', currency: 'USD' }).format(valor ?? 0);
}

function delta(hoy, ayer) {
    return (hoy ?? 0) - (ayer ?? 0);
}

function claseDelta(valor) {
    if (Math.abs(valor) < 0.01) return 'text-text-3';
    return valor > 0 ? 'text-success' : 'text-danger';
}
</script>

<template>
    <Head title="Panel general" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Panel general de la plataforma
            </h2>
        </template>

        <div class="space-y-6 py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg border border-border bg-surface p-5 shadow-sm">
                        <p class="text-xs uppercase text-text-3">Cuentas activas</p>
                        <p class="mt-1 text-2xl font-semibold text-success">{{ metricas.cuentas_activas }}</p>
                        <p class="mt-1 text-xs text-text-3">{{ metricas.cuentas_suspendidas }} suspendidas</p>
                    </div>

                    <div class="rounded-lg border border-border bg-surface p-5 shadow-sm">
                        <p class="text-xs uppercase text-text-3">Negocios</p>
                        <p class="mt-1 text-2xl font-semibold text-text">{{ metricas.total_negocios }}</p>
                        <p class="mt-1 text-xs text-text-3">{{ metricas.negocios_activos }} activos</p>
                    </div>

                    <div class="rounded-lg border border-border bg-surface p-5 shadow-sm">
                        <p class="text-xs uppercase text-text-3">Usuarios en el sistema</p>
                        <p class="mt-1 text-2xl font-semibold text-text">{{ metricas.total_usuarios }}</p>
                    </div>

                    <div class="rounded-lg border border-border bg-surface p-5 shadow-sm sm:col-span-2 lg:col-span-3">
                        <p class="text-xs uppercase text-text-3">Ingreso mensual estimado (suscripciones de planes)</p>
                        <p class="mt-1 text-2xl font-semibold text-primary">
                            {{ formatearMoneda(metricas.ingreso_mensual_estimado) }}
                        </p>
                        <p class="mt-1 text-xs text-text-3">
                            Suma de la mensualidad del plan de cada cuenta activa. Las métricas de ventas/utilidad
                            de cada negocio llegarán con el módulo de Turnos y Ventas.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <div class="mb-4 flex items-baseline justify-between">
                        <h3 class="font-medium text-text">Operación de la plataforma — hoy vs. ayer</h3>
                        <span class="text-xs text-text-3">Todos los negocios, todas las cuentas</span>
                    </div>

                    <div v-if="operacion.hoy.turnos_cerrados === 0 && operacion.ayer.turnos_cerrados === 0" class="text-sm text-text-3">
                        Todavía no se cerró ningún turno en la plataforma — estos números aparecen a medida
                        que los negocios operan.
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <p class="text-xs uppercase text-text-3">Turnos cerrados</p>
                            <p class="mt-1 text-xl font-semibold text-text">{{ operacion.hoy.turnos_cerrados }}</p>
                            <p class="text-xs text-text-3">Ayer: {{ operacion.ayer.turnos_cerrados }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-text-3">Venta</p>
                            <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(operacion.hoy.venta) }}</p>
                            <p class="text-xs" :class="claseDelta(delta(operacion.hoy.venta, operacion.ayer.venta))">
                                {{ delta(operacion.hoy.venta, operacion.ayer.venta) >= 0 ? '+' : '' }}{{ formatearMoneda(delta(operacion.hoy.venta, operacion.ayer.venta)) }} vs. ayer
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-text-3">Utilidad</p>
                            <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(operacion.hoy.utilidad) }}</p>
                            <p class="text-xs" :class="claseDelta(delta(operacion.hoy.utilidad, operacion.ayer.utilidad))">
                                {{ delta(operacion.hoy.utilidad, operacion.ayer.utilidad) >= 0 ? '+' : '' }}{{ formatearMoneda(delta(operacion.hoy.utilidad, operacion.ayer.utilidad)) }} vs. ayer
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-text-3">Salario</p>
                            <p class="mt-1 text-xl font-semibold text-primary">{{ formatearMoneda(operacion.hoy.salario) }}</p>
                            <p class="text-xs" :class="claseDelta(delta(operacion.hoy.salario, operacion.ayer.salario))">
                                {{ delta(operacion.hoy.salario, operacion.ayer.salario) >= 0 ? '+' : '' }}{{ formatearMoneda(delta(operacion.hoy.salario, operacion.ayer.salario)) }} vs. ayer
                            </p>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-text-3">
                        Se cuenta por fecha de cierre de turno. Las monedas de cada negocio se suman sin
                        convertir entre sí — sirve como pulso general, no como cifra contable exacta.
                    </p>
                </div>
            </div>

            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <div class="flex items-center justify-between border-b border-border px-6 py-4">
                        <h3 class="font-medium text-text">Cuentas por plan</h3>
                    </div>
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Plan</th>
                                <th class="px-6 py-3">Precio mensual</th>
                                <th class="px-6 py-3">Cuentas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="plan in cuentasPorPlan" :key="plan.id" class="border-b border-border">
                                <td class="px-6 py-4 font-medium">{{ plan.nombre }}</td>
                                <td class="px-6 py-4">{{ formatearMoneda(plan.precio_mensual) }}</td>
                                <td class="px-6 py-4 font-mono">{{ plan.accounts_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mx-auto flex max-w-7xl gap-4 sm:px-6 lg:px-8">
                <Link :href="route('admin.negocios.index')" class="text-sm font-medium text-primary hover:underline">
                    Ver todos los negocios →
                </Link>
                <Link :href="route('admin.actividad.index')" class="text-sm font-medium text-primary hover:underline">
                    Ver bitácora de actividad →
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
