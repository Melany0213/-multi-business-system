<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Abrir una intervención (RF-54). La ceremonia es el punto: contraseña otra
 * vez, un motivo que se pueda leer en seis meses, y un plazo que vence solo.
 */
const props = defineProps({
    negocio: Object,
    solicitudes: Array,
    solicitudPreseleccionada: Number,
    minutosPorDefecto: Number,
});

const form = useForm({
    password: '',
    solicitud_id: props.solicitudPreseleccionada ?? null,
    motivo: '',
    minutos: props.minutosPorDefecto,
});

// Si la intervención nace de una solicitud, el motivo NO lo escribe el
// soporte: es el pedido firmado por el dueño (RF-59).
const solicitudElegida = computed(
    () => props.solicitudes.find((s) => s.id === form.solicitud_id) ?? null,
);

function enviar() {
    form.post(route('admin.intervenciones.store', props.negocio.id));
}

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '';
}
</script>

<template>
    <Head title="Abrir intervención" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Intervenir «{{ negocio.nombre }}»
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border-l-4 border-red-600 bg-red-500/5 p-4 text-sm text-text-2">
                    <p class="font-semibold text-text">Vas a poder escribir en un negocio que no es tuyo.</p>
                    <p class="mt-1">
                        Mientras dure, cada cambio queda registrado con tu nombre en el libro de movimientos
                        de <strong>{{ negocio.nombre }}</strong>, y {{ negocio.dueno ?? 'el dueño' }} lo va a ver.
                        Al cerrarla se levanta un acta automática con todo lo que hiciste.
                    </p>
                    <p class="mt-1">
                        La intervención te da los permisos del dueño, nunca más que eso: no podés editar el
                        libro, ni cambiar contraseñas ajenas, ni borrar ventas ya cerradas.
                    </p>
                </div>

                <form @submit.prevent="enviar" class="space-y-5 rounded-lg border border-border bg-surface p-6">
                    <!-- Camino normal: responder a un pedido del dueño -->
                    <div v-if="solicitudes.length">
                        <InputLabel value="¿Responde a una solicitud del dueño?" />
                        <p class="mb-2 mt-1 text-xs text-text-3">
                            Es el camino normal. Si elegís una, el motivo es el pedido del propio dueño y no
                            hace falta que escribas ninguno.
                        </p>

                        <div class="space-y-2">
                            <label
                                v-for="s in solicitudes"
                                :key="s.id"
                                class="flex cursor-pointer gap-3 rounded-lg border p-3 transition"
                                :class="form.solicitud_id === s.id
                                    ? 'border-primary bg-primary-soft'
                                    : 'border-border hover:bg-surface-2'"
                            >
                                <input type="radio" :value="s.id" v-model="form.solicitud_id" class="mt-1 text-primary focus:ring-primary" />
                                <span class="text-sm">
                                    <span class="font-medium text-text">{{ s.tipo }}</span>
                                    <span class="text-text-3"> · {{ s.solicitante }} · {{ fecha(s.fecha) }}</span>
                                    <span class="mt-0.5 block text-text-2">{{ s.descripcion }}</span>
                                </span>
                            </label>

                            <label
                                class="flex cursor-pointer gap-3 rounded-lg border p-3 transition"
                                :class="form.solicitud_id === null
                                    ? 'border-primary bg-primary-soft'
                                    : 'border-border hover:bg-surface-2'"
                            >
                                <input type="radio" :value="null" v-model="form.solicitud_id" class="mt-1 text-primary focus:ring-primary" />
                                <span class="text-sm text-text">
                                    No — entro por mi cuenta
                                    <span class="block text-xs text-text-3">
                                        Es la excepción: hace falta motivo escrito y el dueño queda avisado.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div v-if="!solicitudElegida">
                        <InputLabel for="motivo" value="Motivo" />
                        <textarea
                            id="motivo"
                            v-model="form.motivo"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                            placeholder="Qué vas a revisar o corregir, y por qué no puede hacerlo el dueño."
                        />
                        <p class="mt-1 text-xs text-text-3">
                            Escribilo para vos misma dentro de seis meses, no para cumplir.
                        </p>
                        <InputError class="mt-2" :message="form.errors.motivo" />
                    </div>

                    <div v-else class="rounded-lg bg-surface-2 p-3 text-sm">
                        <p class="text-xs uppercase tracking-wide text-text-3">Motivo (tomado de la solicitud)</p>
                        <p class="mt-1 text-text">{{ solicitudElegida.descripcion }}</p>
                    </div>

                    <div>
                        <InputLabel for="minutos" value="Duración (minutos)" />
                        <TextInput id="minutos" type="number" min="5" max="240" v-model="form.minutos" class="mt-1 block w-32" />
                        <p class="mt-1 text-xs text-text-3">
                            Vence sola. El riesgo real no es el abuso: es la pestaña olvidada abierta.
                        </p>
                        <InputError class="mt-2" :message="form.errors.minutos" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Tu contraseña" />
                        <TextInput id="password" type="password" v-model="form.password" class="mt-1 block w-full max-w-sm" autocomplete="current-password" />
                        <p class="mt-1 text-xs text-text-3">
                            Se pide de nuevo por si esta sesión quedó abierta en una máquina que alcanzó otra persona.
                        </p>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
                        >
                            Abrir intervención
                        </button>
                        <Link
                            :href="route('admin.expedientes.negocio', negocio.id)"
                            class="text-sm text-text-3 hover:text-text"
                        >Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
