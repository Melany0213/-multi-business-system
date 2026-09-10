<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TablaMovimientos from '@/Components/TablaMovimientos.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * El libro del negocio, visto por su dueño, y el registro de cada vez que el
 * soporte entró (RF-61). Que esto esté acá sin pedirlo es lo que hace que la
 * transparencia sea real y no una promesa.
 */
const props = defineProps({
    negocio: Object,
    movimientos: Object,
    filtros: Object,
    familias: Array,
    intervenciones: Array,
});

const actaAbierta = ref(null);

function filtrar(event) {
    router.get(route('negocio.libro'), { evento: event.target.value || undefined }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '—';
}
</script>

<template>
    <Head title="Libro de movimientos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-text">Libro de movimientos</h2>
                    <p class="mt-1 text-sm text-text-3">
                        Todo lo que pasó en {{ negocio.nombre }}, en orden. No se puede editar ni borrar.
                    </p>
                </div>
                <select
                    :value="filtros.evento ?? ''"
                    @change="filtrar"
                    class="block rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                >
                    <option value="">Todo</option>
                    <option v-for="f in familias" :key="f" :value="f">{{ f }}</option>
                </select>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <!-- Intervenciones del soporte -->
                <section v-if="intervenciones.length" class="rounded-lg border-l-4 border-amber-500 bg-surface p-4">
                    <h3 class="text-sm font-semibold text-text">Cuando el soporte entró a tu negocio</h3>
                    <p class="mt-1 text-xs text-text-3">
                        Cada vez que el equipo del sistema necesitó escribir acá quedó registrado, con su motivo
                        y un acta de lo que tocó.
                    </p>

                    <ul class="mt-3 divide-y divide-border">
                        <li v-for="i in intervenciones" :key="i.id" class="py-2.5 text-sm">
                            <div class="flex flex-wrap items-baseline gap-x-2">
                                <span class="font-medium text-text">{{ fecha(i.abierta_at) }}</span>
                                <span class="text-text-3">— {{ i.soporte }}</span>
                                <span
                                    v-if="i.vigente"
                                    class="rounded bg-red-500/10 px-1.5 py-0.5 text-xs font-medium text-red-600 dark:text-red-400"
                                >entrando ahora mismo</span>
                                <span v-else class="text-xs text-text-3">
                                    {{ i.duracion_minutos }} min · {{ i.total_movimientos ?? 0 }} cambios
                                </span>
                            </div>

                            <p class="mt-0.5 text-text-2">
                                «{{ i.motivo }}»
                                <span class="text-xs text-text-3">
                                    ({{ i.origen === 'solicitud' ? 'a pedido tuyo' : 'por iniciativa del soporte' }})
                                </span>
                            </p>

                            <button
                                v-if="i.acta?.movimientos?.length"
                                type="button"
                                class="mt-1 text-xs text-primary hover:underline"
                                @click="actaAbierta = actaAbierta === i.id ? null : i.id"
                            >
                                {{ actaAbierta === i.id ? 'Ocultar' : 'Ver' }} el acta completa
                            </button>

                            <ul v-if="actaAbierta === i.id" class="mt-2 space-y-1 rounded bg-surface-2 p-3 text-xs">
                                <li v-for="(m, j) in i.acta.movimientos" :key="j">
                                    <span class="font-mono text-text-3">{{ fecha(m.fecha) }}</span>
                                    — {{ m.descripcion }}
                                </li>
                            </ul>
                        </li>
                    </ul>
                </section>

                <div class="overflow-hidden rounded-lg border border-border bg-surface">
                    <TablaMovimientos :movimientos="movimientos.data" />
                </div>

                <Paginacion :links="movimientos.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
