<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Bandeja del soporte (RF-58). El Super Admin toma, resuelve o rechaza — pero
 * nunca da el ciclo por cerrado: eso lo hace el dueño con su conformidad.
 */
defineProps({
    solicitudes: Object,
    filtros: Object,
    estados: Array,
});

const respondiendo = ref(null);
const accion = ref('resolver');

const form = useForm({ respuesta: '' });

function abrirRespuesta(id, cual) {
    respondiendo.value = id;
    accion.value = cual;
    form.reset();
    form.clearErrors();
}

function enviar(id) {
    form.patch(route(`admin.solicitudes.${accion.value}`, id), {
        preserveScroll: true,
        onSuccess: () => { respondiendo.value = null; },
    });
}

function tomar(id) {
    router.patch(route('admin.solicitudes.tomar', id), {}, { preserveScroll: true });
}

function filtrar(event) {
    router.get(route('admin.solicitudes.index'), { estado: event.target.value || undefined }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '—';
}

const COLOR_ESTADO = {
    abierta: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    en_revision: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    resuelta: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    rechazada: 'bg-surface-2 text-text-3',
};
</script>

<template>
    <Head title="Solicitudes de soporte" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-text">Solicitudes de soporte</h2>
                    <p class="mt-1 text-sm text-text-3">
                        Lo que los dueños pidieron revisar o corregir en sus negocios.
                    </p>
                </div>
                <select
                    :value="filtros.estado ?? ''"
                    @change="filtrar"
                    class="block rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                >
                    <option value="">Todos los estados</option>
                    <option v-for="e in estados" :key="e" :value="e">{{ e.replace('_', ' ') }}</option>
                </select>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-3 sm:px-6 lg:px-8">
                <article
                    v-for="s in solicitudes.data"
                    :key="s.id"
                    class="rounded-lg border border-border bg-surface p-4"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-text">#{{ s.id }} · {{ s.tipo_etiqueta }}</span>
                                <span class="rounded px-2 py-0.5 text-xs font-medium" :class="COLOR_ESTADO[s.estado]">
                                    {{ s.estado.replace('_', ' ') }}
                                </span>
                                <span
                                    v-if="s.espera_conformidad"
                                    class="rounded bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400"
                                >esperando conformidad del dueño</span>
                                <span
                                    v-else-if="s.conformidad === 'conforme'"
                                    class="rounded bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                                >el dueño dio conformidad</span>
                                <span
                                    v-else-if="s.conformidad === 'reabierta'"
                                    class="rounded bg-red-500/10 px-2 py-0.5 text-xs font-medium text-red-600 dark:text-red-400"
                                >el dueño la reabrió</span>
                            </div>

                            <p class="mt-1 text-xs text-text-3">
                                <Link :href="route('admin.expedientes.negocio', s.negocio_id)" class="text-primary hover:underline">
                                    {{ s.negocio }}
                                </Link>
                                · {{ s.cuenta }} · {{ s.solicitante }} · {{ fecha(s.fecha) }}
                                <span v-if="s.referencia"> · sobre {{ s.referencia }}</span>
                            </p>

                            <p class="mt-2 text-sm text-text-2">{{ s.descripcion }}</p>

                            <p v-if="s.respuesta" class="mt-2 rounded bg-surface-2 p-2 text-sm text-text-2">
                                <span class="text-xs uppercase tracking-wide text-text-3">Respuesta del soporte</span><br />
                                {{ s.respuesta }}
                            </p>

                            <p v-if="s.intervenciones.length" class="mt-2 text-xs text-text-3">
                                Intervenciones:
                                <Link
                                    v-for="id in s.intervenciones"
                                    :key="id"
                                    :href="route('admin.intervenciones.show', id)"
                                    class="ms-1 text-primary hover:underline"
                                >#{{ id }}</Link>
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-col gap-1.5">
                            <button
                                v-if="s.estado === 'abierta'"
                                type="button"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-text-2 transition hover:bg-surface-2"
                                @click="tomar(s.id)"
                            >Tomar</button>

                            <Link
                                v-if="s.estado === 'abierta' || s.estado === 'en_revision'"
                                :href="route('admin.intervenciones.create', { negocio: s.negocio_id, solicitud_id: s.id })"
                                class="rounded-lg bg-red-600 px-3 py-1.5 text-center text-xs font-semibold text-white transition hover:bg-red-700"
                            >Intervenir</Link>

                            <button
                                v-if="s.estado === 'abierta' || s.estado === 'en_revision'"
                                type="button"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-text-2 transition hover:bg-surface-2"
                                @click="abrirRespuesta(s.id, 'resolver')"
                            >Resolver</button>

                            <button
                                v-if="s.estado === 'abierta' || s.estado === 'en_revision'"
                                type="button"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-text-3 transition hover:bg-surface-2"
                                @click="abrirRespuesta(s.id, 'rechazar')"
                            >Rechazar</button>
                        </div>
                    </div>

                    <form v-if="respondiendo === s.id" class="mt-3 border-t border-border pt-3" @submit.prevent="enviar(s.id)">
                        <label class="text-xs uppercase tracking-wide text-text-3">
                            {{ accion === 'resolver' ? 'Qué hiciste' : 'Por qué no procede' }}
                        </label>
                        <textarea
                            v-model="form.respuesta"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                        />
                        <p v-if="form.errors.respuesta" class="mt-1 text-xs text-red-600">{{ form.errors.respuesta }}</p>
                        <p class="mt-1 text-xs text-text-3">
                            El dueño va a leer esto y decidir si le sirvió. La solicitud no se cierra hasta que él responda.
                        </p>
                        <div class="mt-2 flex gap-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50"
                            >Enviar</button>
                            <button
                                type="button"
                                class="text-xs text-text-3 hover:text-text"
                                @click="respondiendo = null"
                            >Cancelar</button>
                        </div>
                    </form>
                </article>

                <p v-if="!solicitudes.data.length" class="rounded-lg border border-border bg-surface px-4 py-10 text-center text-sm text-text-3">
                    Ningún dueño pidió nada todavía.
                </p>

                <Paginacion :links="solicitudes.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
