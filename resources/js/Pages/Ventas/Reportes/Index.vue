<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    operacion: Object,
    turnosAbiertos: Array,
    ultimasVentas: Array,
});

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es', { minimumFractionDigits: 2 }).format(valor ?? 0);
}

function formatearFecha(fecha) {
    return fecha ? new Date(fecha).toLocaleString('es') : '—';
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
    <Head title="Reportes de venta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Reportes de venta
            </h2>
        </template>

        <div class="space-y-6 py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <div class="mb-4 flex items-baseline justify-between">
                        <h3 class="font-medium text-text">Hoy vs. ayer</h3>
                    </div>

                    <div v-if="operacion.hoy.turnos_cerrados === 0 && operacion.ayer.turnos_cerrados === 0" class="text-sm text-text-3">
                        Todavía no se cerró ningún turno en este negocio.
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <p class="text-xs uppercase text-text-3">Turnos cerrados</p>
                            <p class="mt-1 text-xl font-semibold text-text">{{ operacion.hoy.turnos_cerrados }}</p>
                            <p class="text-xs text-text-3">Ayer: {{ operacion.ayer.turnos_cerrados }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-text-3">Venta</p>
                            <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(operacion.hoy.venta) }}</p>
                            <p class="text-xs" :class="claseDelta(delta(operacion.hoy.venta, operacion.ayer.venta))">
                                {{ delta(operacion.hoy.venta, operacion.ayer.venta) >= 0 ? '+' : '' }}{{ formatearMoneda(delta(operacion.hoy.venta, operacion.ayer.venta)) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-text-3">Transferencias</p>
                            <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(operacion.hoy.transferencias) }}</p>
                            <p class="text-xs" :class="claseDelta(delta(operacion.hoy.transferencias, operacion.ayer.transferencias))">
                                {{ delta(operacion.hoy.transferencias, operacion.ayer.transferencias) >= 0 ? '+' : '' }}{{ formatearMoneda(delta(operacion.hoy.transferencias, operacion.ayer.transferencias)) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-text-3">Utilidad</p>
                            <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(operacion.hoy.utilidad) }}</p>
                            <p class="text-xs" :class="claseDelta(delta(operacion.hoy.utilidad, operacion.ayer.utilidad))">
                                {{ delta(operacion.hoy.utilidad, operacion.ayer.utilidad) >= 0 ? '+' : '' }}{{ formatearMoneda(delta(operacion.hoy.utilidad, operacion.ayer.utilidad)) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-text-3">Salario</p>
                            <p class="mt-1 text-xl font-semibold text-primary">{{ formatearMoneda(operacion.hoy.salario) }}</p>
                            <p class="text-xs" :class="claseDelta(delta(operacion.hoy.salario, operacion.ayer.salario))">
                                {{ delta(operacion.hoy.salario, operacion.ayer.salario) >= 0 ? '+' : '' }}{{ formatearMoneda(delta(operacion.hoy.salario, operacion.ayer.salario)) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <div class="border-b border-border px-6 py-4">
                        <h3 class="font-medium text-text">Turnos abiertos</h3>
                    </div>
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Cajero</th>
                                <th class="px-6 py-3">Dispositivo</th>
                                <th class="px-6 py-3">Desde</th>
                                <th class="px-6 py-3 text-right">Venta en vivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="turno in turnosAbiertos" :key="turno.id" class="border-b border-border">
                                <td class="px-6 py-4 font-medium">
                                    <Link :href="route('turnos.show', turno.id)" class="hover:underline">
                                        {{ turno.cajero?.name }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">{{ turno.dispositivo }}</td>
                                <td class="px-6 py-4 text-text-3">{{ formatearFecha(turno.fecha_apertura) }}</td>
                                <td class="px-6 py-4 text-right font-mono">{{ formatearMoneda(turno.venta_en_vivo) }}</td>
                            </tr>
                            <tr v-if="turnosAbiertos.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-text-3">
                                    No hay turnos abiertos en este momento.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <div class="border-b border-border px-6 py-4">
                        <h3 class="font-medium text-text">Últimas ventas</h3>
                    </div>
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Cajero</th>
                                <th class="px-6 py-3">Método</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="venta in ultimasVentas" :key="venta.id" class="border-b border-border">
                                <td class="whitespace-nowrap px-6 py-4 text-text-3">{{ formatearFecha(venta.fecha_hora) }}</td>
                                <td class="px-6 py-4">
                                    <Link :href="route('turnos.show', venta.turno_id)" class="hover:underline">
                                        {{ venta.turno?.cajero?.name }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 capitalize">{{ venta.metodo_pago }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="venta.estado === 'pagado' ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger'"
                                    >
                                        {{ venta.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono">{{ formatearMoneda(venta.monto_total) }}</td>
                            </tr>
                            <tr v-if="ultimasVentas.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-text-3">
                                    Todavía no hay ventas registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
