<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    traspasos: Array,
    puedeAutorizar: Boolean,
    puedeTrasladar: Boolean,
});

const estadoClase = {
    solicitado: 'bg-warning-soft text-warning',
    autorizado: 'bg-info-soft text-info',
    completado: 'bg-success-soft text-success',
    rechazado: 'bg-danger-soft text-danger',
};

function autorizar(t) {
    router.patch(route('traspasos.autorizar', t.id), {}, { preserveScroll: true });
}
function rechazar(t) {
    if (confirm('¿Rechazar esta solicitud de traspaso?')) {
        router.patch(route('traspasos.rechazar', t.id), {}, { preserveScroll: true });
    }
}
function confirmarEntrada(t) {
    router.patch(route('traspasos.confirmar', t.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Traspasos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">Traspasos de mercancía</h2>
                <Link v-if="puedeTrasladar" :href="route('traspasos.create')">
                    <PrimaryButton>Solicitar traspaso</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-x-auto rounded-lg border border-border bg-surface shadow-sm">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Producto</th>
                                <th class="px-6 py-3">Cantidad</th>
                                <th class="px-6 py-3">Origen</th>
                                <th class="px-6 py-3">Destino</th>
                                <th class="px-6 py-3">Traza</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in traspasos" :key="t.id" class="border-b border-border align-top">
                                <td class="px-6 py-4 font-medium">{{ t.producto }}</td>
                                <td class="px-6 py-4 font-mono">{{ t.cantidad }}</td>
                                <td class="px-6 py-4">{{ t.almacen_origen }}</td>
                                <td class="px-6 py-4">{{ t.almacen_destino }}</td>
                                <td class="px-6 py-4 text-xs text-text-3">
                                    <div>Solicitó: {{ t.solicitado_por }}</div>
                                    <div v-if="t.autorizado_por">Autorizó: {{ t.autorizado_por }}</div>
                                    <div v-if="t.confirmado_por">Confirmó: {{ t.confirmado_por }}</div>
                                    <div class="font-mono">{{ t.creado_en }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="estadoClase[t.estado]">
                                        {{ t.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                                    <template v-if="t.estado === 'solicitado' && t.es_origen && puedeAutorizar">
                                        <button @click="autorizar(t)" class="text-sm font-medium text-success hover:underline">
                                            Autorizar salida
                                        </button>
                                        <button @click="rechazar(t)" class="text-sm font-medium text-danger hover:underline">
                                            Rechazar
                                        </button>
                                    </template>
                                    <button
                                        v-if="t.estado === 'autorizado' && t.es_destino && puedeTrasladar"
                                        @click="confirmarEntrada(t)"
                                        class="text-sm font-medium text-success hover:underline"
                                    >
                                        Confirmar entrada
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="traspasos.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-text-3">
                                    No hay traspasos registrados para este negocio.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
