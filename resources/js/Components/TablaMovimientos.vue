<script setup>
/**
 * El libro de movimientos, tal como lo leen tanto el soporte como el dueño.
 * Es la misma tabla a propósito: un solo almacén con dos lecturas, no dos
 * registros que puedan terminar contando cosas distintas.
 */
defineProps({
    movimientos: { type: Array, required: true },
    mostrarNegocio: { type: Boolean, default: false },
});

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '—';
}

/**
 * El prefijo del evento (turno, venta, stock…) alcanza para orientarse de un
 * vistazo; el nombre completo va en el título por si hace falta el detalle.
 */
function familia(evento) {
    return (evento ?? '').split('.')[0] || 'sistema';
}

const COLORES = {
    venta: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    turno: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    stock: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    precio: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    traspaso: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    sesion: 'bg-slate-500/10 text-slate-600 dark:text-slate-400',
    intervencion: 'bg-red-500/10 text-red-600 dark:text-red-400',
    solicitud: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
};

function colorFamilia(evento) {
    return COLORES[familia(evento)] ?? 'bg-surface-2 text-text-3';
}

function cambiosLegibles(cambios) {
    if (!cambios) return null;

    return Object.entries(cambios).map(([campo, valor]) => {
        // Los cambios sobre tablas pivote se guardan como {antes, despues};
        // los de modelos, como el valor nuevo a secas.
        if (valor && typeof valor === 'object' && 'antes' in valor) {
            return `${campo}: ${valor.antes ?? '—'} → ${valor.despues ?? '—'}`;
        }
        return `${campo}: ${valor}`;
    });
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-text">
            <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Movimiento</th>
                    <th v-if="mostrarNegocio" class="px-4 py-3">Negocio</th>
                    <th class="px-4 py-3">Quién</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="m in movimientos"
                    :key="m.id"
                    class="border-b border-border align-top"
                    :class="m.intervencion_id && 'bg-red-500/5'"
                >
                    <td class="whitespace-nowrap px-4 py-3 text-text-3">{{ fecha(m.fecha) }}</td>

                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded px-1.5 py-0.5 font-mono text-[11px]"
                                :class="colorFamilia(m.evento)"
                                :title="m.evento"
                            >
                                {{ familia(m.evento) }}
                            </span>
                            <span>{{ m.descripcion }}</span>
                        </div>

                        <ul v-if="cambiosLegibles(m.cambios)" class="mt-1 space-y-0.5 font-mono text-xs text-text-3">
                            <li v-for="(c, i) in cambiosLegibles(m.cambios)" :key="i">{{ c }}</li>
                        </ul>

                        <p v-if="m.intervencion_id" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
                            Hecho por el soporte durante la intervención #{{ m.intervencion_id }}
                        </p>
                    </td>

                    <td v-if="mostrarNegocio" class="px-4 py-3 text-text-3">{{ m.negocio ?? '—' }}</td>

                    <td class="whitespace-nowrap px-4 py-3 text-text-2">{{ m.autor }}</td>
                </tr>

                <tr v-if="movimientos.length === 0">
                    <td :colspan="mostrarNegocio ? 4 : 3" class="px-4 py-8 text-center text-text-3">
                        Sin movimientos registrados.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
