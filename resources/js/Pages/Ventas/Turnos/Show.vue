<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    turno: Object,
    esPropio: Boolean,
    puedeVender: Boolean,
});

const DENOMINACIONES = [5000, 2000, 1000, 500, 200, 100, 50, 20, 10, 5];

const mostrarConteo = ref(false);

const form = useForm({
    conteo: {
        locales: Object.fromEntries(DENOMINACIONES.map((valor) => [valor, 0])),
        usd: 0,
        eur: 0,
    },
});

const totalContadoEnVivo = computed(() => {
    const totalLocal = DENOMINACIONES.reduce(
        (suma, valor) => suma + valor * (Number(form.conteo.locales[valor]) || 0),
        0,
    );
    const totalUsd = (Number(form.conteo.usd) || 0) * (Number(props.turno.tasa_usd) || 0);
    const totalEur = (Number(form.conteo.eur) || 0) * (Number(props.turno.tasa_eur) || 0);

    return totalLocal + totalUsd + totalEur;
});

function limpiarConteo() {
    DENOMINACIONES.forEach((valor) => (form.conteo.locales[valor] = 0));
    form.conteo.usd = 0;
    form.conteo.eur = 0;
}

function cerrarTurno() {
    form.patch(route('turnos.cerrar', props.turno.id), {
        onSuccess: () => (mostrarConteo.value = false),
    });
}

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es', { minimumFractionDigits: 2 }).format(valor ?? 0);
}

function formatearFecha(fecha) {
    return fecha ? new Date(fecha).toLocaleString('es') : '—';
}

const totalVentaEnVivo = computed(() =>
    props.turno.ventas.filter((v) => v.estado === 'pagado').reduce((s, v) => s + Number(v.monto_total), 0),
);

// Reconciliación: lo contado en el conteo de efectivo (billetes físicos) solo
// debería cuadrar contra las ventas en EFECTIVO de este turno — las de
// tarjeta/transferencia no pasan por la caja física.
const esperadoEnCaja = computed(() =>
    props.turno.ventas
        .filter((v) => v.estado === 'pagado' && v.metodo_pago === 'efectivo')
        .reduce((s, v) => s + Number(v.monto_total), 0),
);

const diferenciaCaja = computed(() => Number(props.turno.total_contado ?? 0) - esperadoEnCaja.value);

const mensajeReconciliacion = computed(() => {
    if (Math.abs(diferenciaCaja.value) < 0.01) {
        return `Cuadra con lo esperado (${formatearMoneda(esperadoEnCaja.value)} en efectivo).`;
    }

    const cantidad = formatearMoneda(Math.abs(diferenciaCaja.value));

    return diferenciaCaja.value > 0
        ? `Sobran ${cantidad} respecto a lo esperado en efectivo.`
        : `Faltan ${cantidad} respecto a lo esperado en efectivo.`;
});

const claseReconciliacion = computed(() => {
    if (Math.abs(diferenciaCaja.value) < 0.01) {
        return { caja: 'bg-surface', texto: 'text-success' };
    }

    return diferenciaCaja.value > 0
        ? { caja: 'bg-warning-soft', texto: 'text-warning' }
        : { caja: 'bg-danger-soft', texto: 'text-danger' };
});
</script>

<template>
    <Head :title="`Turno · ${turno.dispositivo}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-text">
                        Turno · {{ turno.dispositivo }}
                    </h2>
                    <p class="text-sm text-text-3">
                        {{ turno.cajero?.name }} · {{ turno.almacen?.nombre }} · desde {{ formatearFecha(turno.fecha_apertura) }}
                    </p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="turno.estado === 'abierto' ? 'bg-success-soft text-success' : 'bg-surface-2 text-text-3'"
                >
                    {{ turno.estado }}
                </span>
            </div>
        </template>

        <div class="space-y-6 py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-border bg-surface p-5">
                        <p class="text-xs uppercase text-text-3">Venta</p>
                        <p class="mt-1 text-xl font-semibold text-text">
                            {{ formatearMoneda(turno.estado === 'abierto' ? totalVentaEnVivo : turno.total_venta) }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border bg-surface p-5">
                        <p class="text-xs uppercase text-text-3">Transferencias</p>
                        <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(turno.total_transferencias) }}</p>
                    </div>
                    <div class="rounded-lg border border-border bg-surface p-5">
                        <p class="text-xs uppercase text-text-3">Salario</p>
                        <p class="mt-1 text-xl font-semibold text-primary">{{ formatearMoneda(turno.salario) }}</p>
                    </div>
                </div>

                <div v-if="turno.estado === 'cerrado'" class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-border bg-surface p-5">
                        <p class="text-xs uppercase text-text-3">Utilidad</p>
                        <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(turno.total_utilidad) }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-5" :class="claseReconciliacion.caja">
                        <p class="text-xs uppercase text-text-3">Contado al cierre</p>
                        <p class="mt-1 text-xl font-semibold text-text">{{ formatearMoneda(turno.total_contado) }}</p>
                        <p class="mt-1 text-xs font-medium" :class="claseReconciliacion.texto">
                            {{ mensajeReconciliacion }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border bg-surface p-5">
                        <p class="text-xs uppercase text-text-3">Depósito</p>
                        <p class="mt-1 text-xl font-semibold text-success">{{ formatearMoneda(turno.deposito) }}</p>
                    </div>
                </div>

                <div v-if="turno.estado === 'abierto'" class="mt-4 flex gap-3">
                    <Link v-if="puedeVender" :href="route('ventas.create', turno.id)">
                        <PrimaryButton>Registrar venta</PrimaryButton>
                    </Link>
                    <SecondaryButton v-if="esPropio" @click="mostrarConteo = true">
                        Cerrar turno
                    </SecondaryButton>
                </div>
            </div>

            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <div class="border-b border-border px-6 py-4">
                        <h3 class="font-medium text-text">Resumen de ventas</h3>
                    </div>
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Productos</th>
                                <th class="px-6 py-3">Método</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="venta in turno.ventas" :key="venta.id" class="border-b border-border align-top">
                                <td class="whitespace-nowrap px-6 py-4 text-text-3">{{ formatearFecha(venta.fecha_hora) }}</td>
                                <td class="px-6 py-4">
                                    <div v-for="detalle in venta.detalles" :key="detalle.id" class="text-xs text-text-2">
                                        {{ detalle.producto?.nombre }} × {{ detalle.cantidad }}
                                    </div>
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
                            <tr v-if="turno.ventas.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-text-3">
                                    Todavía no hay ventas registradas en este turno.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="mostrarConteo" @close="mostrarConteo = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-text">Conteo de efectivo</h3>
                <p class="mt-1 text-sm text-text-3">Contá los billetes recibidos en este turno antes de cerrarlo.</p>

                <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3">
                    <div v-for="valor in DENOMINACIONES" :key="valor" class="flex items-center gap-2">
                        <span class="w-16 shrink-0 text-sm text-text-3">$ {{ valor }}</span>
                        <input
                            type="number"
                            min="0"
                            v-model="form.conteo.locales[valor]"
                            class="w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="w-16 shrink-0 text-sm text-text-3">
                            USD
                            <span v-if="turno.tasa_usd" class="block text-xs">{{ turno.tasa_usd }}</span>
                        </span>
                        <input
                            type="number"
                            min="0"
                            v-model="form.conteo.usd"
                            class="w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="w-16 shrink-0 text-sm text-text-3">
                            EUR
                            <span v-if="turno.tasa_eur" class="block text-xs">{{ turno.tasa_eur }}</span>
                        </span>
                        <input
                            type="number"
                            min="0"
                            v-model="form.conteo.eur"
                            class="w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <SecondaryButton type="button" @click="limpiarConteo">Limpiar</SecondaryButton>
                    <span class="text-2xl font-semibold text-success">{{ formatearMoneda(totalContadoEnVivo) }}</span>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="mostrarConteo = false">Cancelar</SecondaryButton>
                    <DangerButton :disabled="form.processing" @click="cerrarTurno">
                        Cerrar turno
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
