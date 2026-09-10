<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * El dueño pide ayuda (RF-58) y después dice si le sirvió (RF-60).
 *
 * Lo que se escribe acá es literalmente el motivo con el que el soporte va a
 * entrar: por eso el formulario insiste en el detalle en vez de conformarse
 * con una línea.
 */
defineProps({
    negocio: Object,
    tipos: Object,
    solicitudes: Array,
});

const mostrandoFormulario = ref(false);
const actaAbierta = ref(null);

const form = useForm({ tipo: 'revision', descripcion: '' });

function enviar() {
    form.post(route('negocio.solicitudes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            mostrandoFormulario.value = false;
        },
    });
}

function responder(id, conformidad) {
    router.patch(route('negocio.solicitudes.conformidad', id), { conformidad }, { preserveScroll: true });
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

const ETIQUETA_ESTADO = {
    abierta: 'esperando al soporte',
    en_revision: 'el soporte la está mirando',
    resuelta: 'resuelta',
    rechazada: 'rechazada',
};
</script>

<template>
    <Head title="Soporte" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-text">Soporte</h2>
                    <p class="mt-1 text-sm text-text-3">
                        Pedí que revisen o corrijan algo en {{ negocio.nombre }}.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                    @click="mostrandoFormulario = !mostrandoFormulario"
                >
                    {{ mostrandoFormulario ? 'Cancelar' : 'Pedir una revisión' }}
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
                <form
                    v-if="mostrandoFormulario"
                    class="space-y-4 rounded-lg border border-border bg-surface p-6"
                    @submit.prevent="enviar"
                >
                    <div>
                        <label class="text-sm font-medium text-text">¿Qué necesitás?</label>
                        <div class="mt-2 space-y-2">
                            <label
                                v-for="(etiqueta, valor) in tipos"
                                :key="valor"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition"
                                :class="form.tipo === valor ? 'border-primary bg-primary-soft' : 'border-border hover:bg-surface-2'"
                            >
                                <input type="radio" :value="valor" v-model="form.tipo" class="text-primary focus:ring-primary" />
                                <span class="text-text">{{ etiqueta }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="descripcion" class="text-sm font-medium text-text">Contá qué pasó</label>
                        <textarea
                            id="descripcion"
                            v-model="form.descripcion"
                            rows="4"
                            class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                            placeholder="Ej.: el turno 34 cerró con 2.300 de faltante y el cajero dice que registró bien todas las ventas."
                        />
                        <p class="mt-1 text-xs text-text-3">
                            Esto es exactamente lo que va a quedar como motivo si el soporte necesita entrar a
                            tu negocio: cuanto más claro, mejor queda registrado para las dos partes.
                        </p>
                        <InputError class="mt-2" :message="form.errors.descripcion" />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    >Enviar al soporte</button>
                </form>

                <article
                    v-for="s in solicitudes"
                    :key="s.id"
                    class="rounded-lg border border-border bg-surface p-4"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-text">#{{ s.id }} · {{ s.tipo_etiqueta }}</span>
                        <span class="rounded px-2 py-0.5 text-xs font-medium" :class="COLOR_ESTADO[s.estado]">
                            {{ ETIQUETA_ESTADO[s.estado] }}
                        </span>
                        <span v-if="s.conformidad === 'conforme'" class="text-xs text-emerald-600 dark:text-emerald-400">
                            diste conformidad
                        </span>
                    </div>

                    <p class="mt-1 text-xs text-text-3">{{ s.solicitante }} · {{ fecha(s.fecha) }}</p>
                    <p class="mt-2 text-sm text-text-2">{{ s.descripcion }}</p>

                    <div v-if="s.respuesta" class="mt-3 rounded bg-surface-2 p-3">
                        <p class="text-xs uppercase tracking-wide text-text-3">Respuesta del soporte</p>
                        <p class="mt-1 text-sm text-text-2">{{ s.respuesta }}</p>
                    </div>

                    <div v-if="s.intervenciones.length" class="mt-3">
                        <p class="text-xs uppercase tracking-wide text-text-3">Lo que el soporte tocó</p>
                        <div v-for="i in s.intervenciones" :key="i.id" class="mt-1 text-sm">
                            <button
                                type="button"
                                class="text-xs text-primary hover:underline"
                                @click="actaAbierta = actaAbierta === i.id ? null : i.id"
                            >
                                Acta de la intervención #{{ i.id }} ({{ fecha(i.abierta_at) }})
                            </button>
                            <ul v-if="actaAbierta === i.id" class="mt-1 space-y-1 rounded bg-surface-2 p-3 text-xs">
                                <li v-for="(m, j) in (i.acta?.movimientos ?? [])" :key="j">
                                    <span class="font-mono text-text-3">{{ fecha(m.fecha) }}</span> — {{ m.descripcion }}
                                </li>
                                <li v-if="!i.acta?.movimientos?.length" class="text-text-3">
                                    La intervención se cerró sin tocar nada.
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- El ciclo lo cierra el dueño, no el soporte (RF-60) -->
                    <div v-if="s.espera_conformidad && s.puede_responder" class="mt-3 border-t border-border pt-3">
                        <p class="text-sm text-text">¿Quedó resuelto?</p>
                        <div class="mt-2 flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                @click="responder(s.id, 'conforme')"
                            >Sí, quedó resuelto</button>
                            <button
                                type="button"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-text-2 transition hover:bg-surface-2"
                                @click="responder(s.id, 'reabierta')"
                            >No, reabrir</button>
                        </div>
                    </div>
                </article>

                <p v-if="!solicitudes.length" class="rounded-lg border border-border bg-surface px-4 py-10 text-center text-sm text-text-3">
                    No pediste nada todavía.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
