<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * El acta de una intervención (RF-57). No se escribe a mano: se deduce del
 * libro de movimientos, y eso es justo lo que la vuelve difícil de maquillar.
 */
const props = defineProps({
    intervencion: Object,
    movimientosEnVivo: Array,
});

const movimientos = computed(
    () => props.intervencion.acta?.movimientos ?? props.movimientosEnVivo ?? [],
);

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '—';
}

function cerrar() {
    router.patch(route('admin.intervenciones.cerrar', props.intervencion.id));
}

function cambiosLegibles(cambios) {
    if (!cambios) return null;

    return Object.entries(cambios).map(([campo, valor]) =>
        valor && typeof valor === 'object' && 'antes' in valor
            ? `${campo}: ${valor.antes ?? '—'} → ${valor.despues ?? '—'}`
            : `${campo}: ${valor}`,
    );
}
</script>

<template>
    <Head :title="`Intervención #${intervencion.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-text">
                        Intervención #{{ intervencion.id }}
                    </h2>
                    <p class="mt-1 text-sm text-text-3">
                        <Link :href="route('admin.expedientes.negocio', intervencion.negocio_id)" class="text-primary hover:underline">
                            {{ intervencion.negocio }}
                        </Link>
                        · {{ intervencion.cuenta }} · {{ intervencion.super_admin }}
                    </p>
                </div>

                <button
                    v-if="intervencion.vigente"
                    type="button"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                    @click="cerrar"
                >
                    Cerrar y levantar acta
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-border bg-surface p-4">
                        <p class="text-xs uppercase tracking-wide text-text-3">Motivo</p>
                        <p class="mt-1 text-sm text-text">{{ intervencion.motivo }}</p>
                        <p class="mt-2 text-xs text-text-3">
                            <template v-if="intervencion.solicitud">
                                Pedido por {{ intervencion.solicitud.solicitante }} en la solicitud
                                #{{ intervencion.solicitud.id }} — el motivo lo puso el dueño, no el soporte.
                            </template>
                            <template v-else>
                                Abierta por iniciativa del soporte.
                            </template>
                        </p>
                    </div>

                    <div class="rounded-lg border border-border bg-surface p-4 text-sm">
                        <p class="text-xs uppercase tracking-wide text-text-3">Estado</p>
                        <p class="mt-1 font-medium" :class="intervencion.vigente ? 'text-red-600 dark:text-red-400' : 'text-text'">
                            {{ intervencion.vigente ? `Activa — ${intervencion.minutos_restantes} min restantes` : intervencion.estado }}
                        </p>
                        <dl class="mt-2 space-y-1 text-xs text-text-3">
                            <div><dt class="inline">Abierta:</dt> <dd class="inline">{{ fecha(intervencion.abierta_at) }}</dd></div>
                            <div><dt class="inline">Vence:</dt> <dd class="inline">{{ fecha(intervencion.expira_at) }}</dd></div>
                            <div v-if="intervencion.cerrada_at">
                                <dt class="inline">Cerrada:</dt> <dd class="inline">{{ fecha(intervencion.cerrada_at) }}</dd>
                            </div>
                            <div v-if="intervencion.acta?.duracion_minutos">
                                <dt class="inline">Duró:</dt> <dd class="inline">{{ intervencion.acta.duracion_minutos }} min</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-border bg-surface">
                    <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">
                        {{ intervencion.acta ? 'Acta — qué se tocó' : 'Lo que lleva hecho hasta ahora' }}
                        <span class="ms-1 font-normal text-text-3">({{ movimientos.length }})</span>
                    </h3>

                    <ul class="divide-y divide-border">
                        <li v-for="(m, i) in movimientos" :key="i" class="px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-baseline gap-2">
                                <span class="font-mono text-xs text-text-3">{{ fecha(m.fecha) }}</span>
                                <span class="rounded bg-surface-2 px-1.5 py-0.5 font-mono text-[11px] text-text-3">{{ m.evento }}</span>
                                <span class="text-text">{{ m.descripcion }}</span>
                            </div>
                            <ul v-if="cambiosLegibles(m.cambios)" class="mt-1 space-y-0.5 font-mono text-xs text-text-3">
                                <li v-for="(c, j) in cambiosLegibles(m.cambios)" :key="j">{{ c }}</li>
                            </ul>
                        </li>

                        <li v-if="!movimientos.length" class="px-4 py-10 text-center text-sm text-text-3">
                            {{ intervencion.acta
                                ? 'La intervención se cerró sin tocar nada.'
                                : 'Todavía no se hizo ningún cambio.' }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
