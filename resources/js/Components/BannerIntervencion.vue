<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Aviso permanente de intervención de soporte (RF-54).
 *
 * No se puede cerrar ni ocultar, y va en todas las pantallas: la diferencia
 * entre "estoy mirando" y "estoy escribiendo en el negocio de otro" no puede
 * depender de que uno se acuerde. Si el aviso se pudiera descartar, a los dos
 * días nadie lo vería.
 */
const page = usePage();

const intervenciones = computed(() => page.props.intervenciones ?? []);

function cerrar(id) {
    router.patch(route('admin.intervenciones.cerrar', id), {}, { preserveScroll: true });
}
</script>

<template>
    <div
        v-for="i in intervenciones"
        :key="i.id"
        class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b-2 border-red-700 bg-red-600 px-4 py-2.5 text-sm text-white sm:px-6 lg:px-8"
    >
        <span class="flex items-center gap-2 font-semibold">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            Intervención de soporte activa
        </span>

        <span class="min-w-0">
            Estás escribiendo en <strong>{{ i.negocio }}</strong> — cada cambio queda registrado a tu nombre.
        </span>

        <span v-if="i.motivo" class="min-w-0 truncate italic opacity-90" :title="i.motivo">
            «{{ i.motivo }}»
        </span>

        <span class="ms-auto flex items-center gap-3 whitespace-nowrap">
            <span class="rounded bg-red-800/60 px-2 py-0.5 font-mono text-xs">
                {{ i.minutos_restantes }} min restantes
            </span>
            <button
                type="button"
                class="rounded bg-white px-2.5 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                @click="cerrar(i.id)"
            >
                Cerrar ahora
            </button>
        </span>
    </div>
</template>
