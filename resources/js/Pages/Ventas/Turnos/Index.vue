<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    turnoAbierto: Object,
    historial: Array,
});

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es', { minimumFractionDigits: 2 }).format(valor ?? 0);
}

function formatearFecha(fecha) {
    return fecha ? new Date(fecha).toLocaleString('es') : '—';
}
</script>

<template>
    <Head title="Turnos de venta" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">
                    Turnos de venta
                </h2>
                <Link v-if="!turnoAbierto" :href="route('turnos.create')">
                    <PrimaryButton>Abrir turno</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="space-y-6 py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="turnoAbierto" class="rounded-lg border border-primary bg-primary-soft p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase text-primary">Turno abierto</p>
                            <p class="mt-1 font-medium text-text">{{ turnoAbierto.dispositivo }} · {{ turnoAbierto.almacen?.nombre }}</p>
                            <p class="text-sm text-text-3">Desde {{ formatearFecha(turnoAbierto.fecha_apertura) }}</p>
                        </div>
                        <Link :href="route('turnos.show', turnoAbierto.id)" class="text-sm font-medium text-primary hover:underline">
                            Ver turno →
                        </Link>
                    </div>
                </div>

                <div v-else class="rounded-lg border border-border bg-surface p-6 text-sm text-text-3">
                    No tenés un turno abierto en este negocio en este momento.
                </div>
            </div>

            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <div class="border-b border-border px-6 py-4">
                        <h3 class="font-medium text-text">Turnos cerrados recientes</h3>
                    </div>
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Cajero</th>
                                <th class="px-6 py-3">Cierre</th>
                                <th class="px-6 py-3">Venta</th>
                                <th class="px-6 py-3">Transferencias</th>
                                <th class="px-6 py-3">Salario</th>
                                <th class="px-6 py-3">Depósito</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="turno in historial" :key="turno.id" class="border-b border-border">
                                <td class="px-6 py-4">{{ turno.cajero?.name }}</td>
                                <td class="px-6 py-4 text-text-3">{{ formatearFecha(turno.fecha_cierre) }}</td>
                                <td class="px-6 py-4 font-mono">{{ formatearMoneda(turno.total_venta) }}</td>
                                <td class="px-6 py-4 font-mono">{{ formatearMoneda(turno.total_transferencias) }}</td>
                                <td class="px-6 py-4 font-mono">{{ formatearMoneda(turno.salario) }}</td>
                                <td class="px-6 py-4 font-mono font-medium">{{ formatearMoneda(turno.deposito) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('turnos.show', turno.id)" class="text-sm font-medium text-primary hover:underline">
                                        Ver
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="historial.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-text-3">
                                    Todavía no hay turnos cerrados en este negocio.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
