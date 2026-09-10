<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TablaMovimientos from '@/Components/TablaMovimientos.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { Head, Link, router } from '@inertiajs/vue3';

/**
 * Expediente del negocio: espejo de SOLO LECTURA de todo lo que ve el dueño
 * (RF-51). No hay un solo botón que modifique nada acá — para eso hay que
 * abrir una intervención, que es un acto deliberado y con acta.
 */
const props = defineProps({
    negocio: Object,
    pestanas: Array,
    pestana: String,
    datos: Object,
    intervencionVigente: Object,
});

const ETIQUETAS = {
    resumen: 'Resumen',
    almacenes: 'Almacenes',
    productos: 'Productos y precios',
    traspasos: 'Traspasos',
    turnos: 'Turnos',
    usuarios: 'Usuarios',
    libro: 'Libro de movimientos',
    soporte: 'Soporte',
};

function irA(pestana) {
    router.get(route('admin.expedientes.negocio', props.negocio.id), { pestana }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function fecha(iso) {
    return iso ? new Date(iso).toLocaleString('es') : '—';
}

function moneda(n) {
    return Number(n ?? 0).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function claseDescuadre(d) {
    if (d === null || d === undefined) return 'text-text-3';
    if (Math.abs(d) < 0.01) return 'text-emerald-600 dark:text-emerald-400';
    return 'text-red-600 dark:text-red-400';
}
</script>

<template>
    <Head :title="`Expediente · ${negocio.nombre}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-semibold leading-tight text-text">{{ negocio.nombre }}</h2>
                        <span
                            class="rounded px-2 py-0.5 text-xs font-medium"
                            :class="negocio.estado === 'activo'
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-surface-2 text-text-3'"
                        >{{ negocio.estado }}</span>
                    </div>
                    <p class="mt-1 text-sm text-text-3">
                        {{ negocio.cuenta }}
                        <span v-if="negocio.rubro"> · {{ negocio.rubro }}</span>
                        · Dueño:
                        <Link
                            v-if="negocio.dueno_id"
                            :href="route('admin.expedientes.usuario', negocio.dueno_id)"
                            class="text-primary hover:underline"
                        >{{ negocio.dueno }}</Link>
                        <span v-else>—</span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="rounded-lg bg-surface-2 px-3 py-1.5 text-xs text-text-3">Solo lectura</span>
                    <Link
                        v-if="!intervencionVigente"
                        :href="route('admin.intervenciones.create', negocio.id)"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-red-700"
                    >
                        Intervenir
                    </Link>
                    <Link
                        v-else
                        :href="route('admin.intervenciones.show', intervencionVigente.id)"
                        class="rounded-lg border border-red-600 px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-500/10"
                    >
                        Intervención #{{ intervencionVigente.id }} activa
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <!-- Pestañas -->
                <div class="flex flex-wrap gap-1 border-b border-border">
                    <button
                        v-for="p in pestanas"
                        :key="p"
                        type="button"
                        class="-mb-px border-b-2 px-3 py-2 text-sm font-medium transition"
                        :class="p === pestana
                            ? 'border-primary text-primary'
                            : 'border-transparent text-text-3 hover:text-text'"
                        @click="irA(p)"
                    >
                        {{ ETIQUETAS[p] }}
                    </button>
                </div>

                <!-- Resumen -->
                <div v-if="pestana === 'resumen'" class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="(valor, clave) in {
                            'Almacenes': datos.resumen.almacenes,
                            'Productos': datos.resumen.productos,
                            'Usuarios': datos.resumen.usuarios,
                            'Turnos abiertos': datos.resumen.turnos_abiertos,
                            'Turnos totales': datos.resumen.turnos_totales,
                            'Movimientos': datos.resumen.movimientos,
                            'Solicitudes abiertas': datos.resumen.solicitudes_abiertas,
                        }" :key="clave" class="rounded-lg border border-border bg-surface p-4">
                            <p class="text-xs uppercase tracking-wide text-text-3">{{ clave }}</p>
                            <p class="mt-1 text-2xl font-semibold text-text">{{ valor }}</p>
                        </div>

                        <div class="rounded-lg border border-border bg-surface p-4">
                            <p class="text-xs uppercase tracking-wide text-text-3">Última actividad</p>
                            <p class="mt-1 text-sm font-medium text-text">{{ fecha(datos.resumen.ultimo_movimiento) }}</p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-border bg-surface p-4">
                        <h3 class="text-sm font-semibold text-text">Salario del cajero</h3>
                        <p class="mt-1 text-sm text-text-2">
                            {{ moneda(negocio.salario.monto_fijo) }} fijo
                            + {{ negocio.salario.porcentaje }}% sobre {{ negocio.salario.base }}
                        </p>
                        <p class="mt-1 text-xs text-text-3">
                            {{ negocio.salario.delegado_al_admin
                                ? 'El dueño delegó esta configuración en el administrador.'
                                : 'Solo el dueño puede configurarlo.' }}
                        </p>
                    </div>

                    <div class="rounded-lg border border-border bg-surface">
                        <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">Últimos movimientos</h3>
                        <TablaMovimientos :movimientos="datos.ultimosMovimientos" />
                    </div>
                </div>

                <!-- Almacenes -->
                <div v-else-if="pestana === 'almacenes'" class="overflow-hidden rounded-lg border border-border bg-surface">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-4 py-3">Almacén</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Productos</th>
                                <th class="px-4 py-3">Unidades en stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="a in datos.almacenes" :key="a.id" class="border-b border-border">
                                <td class="px-4 py-3">{{ a.nombre }}</td>
                                <td class="px-4 py-3 text-text-3">{{ a.estado }}</td>
                                <td class="px-4 py-3">{{ a.productos }}</td>
                                <td class="px-4 py-3">{{ a.unidades }}</td>
                            </tr>
                            <tr v-if="!datos.almacenes.length">
                                <td colspan="4" class="px-4 py-8 text-center text-text-3">Este negocio no tiene almacenes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Productos -->
                <div v-else-if="pestana === 'productos'" class="overflow-x-auto rounded-lg border border-border bg-surface">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3">Precio</th>
                                <th class="px-4 py-3">Costo</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in datos.productos" :key="p.id" class="border-b border-border">
                                <td class="px-4 py-3">{{ p.nombre }}</td>
                                <td class="px-4 py-3 text-text-3">{{ p.sku ?? '—' }}</td>
                                <td class="px-4 py-3">{{ moneda(p.precio) }}</td>
                                <td class="px-4 py-3 text-text-3">{{ moneda(p.costo) }}</td>
                                <td class="px-4 py-3">{{ p.stock }}</td>
                                <td class="px-4 py-3 text-text-3">{{ p.estado }}</td>
                            </tr>
                            <tr v-if="!datos.productos.length">
                                <td colspan="6" class="px-4 py-8 text-center text-text-3">Este negocio no ofrece productos.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Traspasos -->
                <div v-else-if="pestana === 'traspasos'" class="overflow-x-auto rounded-lg border border-border bg-surface">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">Ruta</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Quiénes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in datos.traspasos" :key="t.id" class="border-b border-border align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-text-3">{{ fecha(t.fecha) }}</td>
                                <td class="px-4 py-3">{{ t.cantidad }} × {{ t.producto }}</td>
                                <td class="px-4 py-3 text-text-2">{{ t.origen }} → {{ t.destino }}</td>
                                <td class="px-4 py-3">{{ t.estado }}</td>
                                <td class="px-4 py-3 text-xs text-text-3">
                                    <div>Pidió: {{ t.solicitado_por ?? '—' }}</div>
                                    <div>Autorizó: {{ t.autorizado_por ?? '—' }}</div>
                                    <div>Confirmó: {{ t.confirmado_por ?? '—' }}</div>
                                </td>
                            </tr>
                            <tr v-if="!datos.traspasos.length">
                                <td colspan="5" class="px-4 py-8 text-center text-text-3">Sin traspasos.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Turnos -->
                <div v-else-if="pestana === 'turnos'" class="overflow-x-auto rounded-lg border border-border bg-surface">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Cajero</th>
                                <th class="px-4 py-3">Apertura</th>
                                <th class="px-4 py-3">Cierre</th>
                                <th class="px-4 py-3">Venta</th>
                                <th class="px-4 py-3">Salario</th>
                                <th class="px-4 py-3">Ventas</th>
                                <th class="px-4 py-3">Descuadre</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in datos.turnos" :key="t.id" class="border-b border-border">
                                <td class="px-4 py-3 text-text-3">{{ t.id }}</td>
                                <td class="px-4 py-3">
                                    <Link
                                        v-if="t.cajero_id"
                                        :href="route('admin.expedientes.usuario', t.cajero_id)"
                                        class="text-primary hover:underline"
                                    >{{ t.cajero }}</Link>
                                    <span v-else>—</span>
                                    <div class="text-xs text-text-3">{{ t.almacen }} · {{ t.dispositivo }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-text-3">{{ fecha(t.apertura) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-text-3">
                                    {{ t.estado === 'abierto' ? 'Abierto ahora' : fecha(t.cierre) }}
                                </td>
                                <td class="px-4 py-3">{{ moneda(t.venta) }}</td>
                                <td class="px-4 py-3">{{ moneda(t.salario) }}</td>
                                <td class="px-4 py-3">
                                    {{ t.ventas }}
                                    <span v-if="t.anuladas" class="text-red-600 dark:text-red-400">({{ t.anuladas }} anul.)</span>
                                </td>
                                <td class="px-4 py-3 font-medium" :class="claseDescuadre(t.descuadre)">
                                    {{ t.descuadre === null ? '—' : moneda(t.descuadre) }}
                                </td>
                            </tr>
                            <tr v-if="!datos.turnos.length">
                                <td colspan="8" class="px-4 py-8 text-center text-text-3">Sin turnos.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Usuarios -->
                <div v-else-if="pestana === 'usuarios'" class="overflow-x-auto rounded-lg border border-border bg-surface">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-4 py-3">Usuario</th>
                                <th class="px-4 py-3">Rol</th>
                                <th class="px-4 py-3">Vigencia</th>
                                <th class="px-4 py-3">Acceso</th>
                                <th class="px-4 py-3">Estado global</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in datos.usuarios" :key="u.acceso_id" class="border-b border-border">
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.expedientes.usuario', u.usuario_id)"
                                        class="text-primary hover:underline"
                                    >{{ u.nombre }}</Link>
                                    <div class="text-xs text-text-3">{{ u.username }}</div>
                                </td>
                                <td class="px-4 py-3">{{ u.rol }}</td>
                                <td class="px-4 py-3 text-text-3">{{ u.vigencia }}</td>
                                <td class="px-4 py-3 text-text-3">{{ u.estado_acceso }}</td>
                                <td class="px-4 py-3 text-text-3">{{ u.estado_usuario }}</td>
                            </tr>
                            <tr v-if="!datos.usuarios.length">
                                <td colspan="5" class="px-4 py-8 text-center text-text-3">Nadie tiene acceso a este negocio.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Libro -->
                <div v-else-if="pestana === 'libro'">
                    <div class="overflow-hidden rounded-lg border border-border bg-surface">
                        <TablaMovimientos :movimientos="datos.movimientos.data" />
                    </div>
                    <Paginacion :links="datos.movimientos.links" />
                </div>

                <!-- Soporte -->
                <div v-else-if="pestana === 'soporte'" class="space-y-4">
                    <div class="overflow-hidden rounded-lg border border-border bg-surface">
                        <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">
                            Solicitudes del dueño
                        </h3>
                        <table class="w-full text-left text-sm text-text">
                            <tbody>
                                <tr v-for="s in datos.solicitudes" :key="s.id" class="border-b border-border align-top">
                                    <td class="whitespace-nowrap px-4 py-3 text-text-3">{{ fecha(s.fecha) }}</td>
                                    <td class="px-4 py-3">
                                        {{ s.descripcion }}
                                        <div class="text-xs text-text-3">{{ s.solicitante }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-text-3">
                                        {{ s.estado }}
                                        <span v-if="s.conformidad"> · {{ s.conformidad }}</span>
                                    </td>
                                </tr>
                                <tr v-if="!datos.solicitudes.length">
                                    <td class="px-4 py-8 text-center text-text-3">El dueño no pidió nada todavía.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-border bg-surface">
                        <h3 class="border-b border-border px-4 py-3 text-sm font-semibold text-text">
                            Intervenciones del soporte en este negocio
                        </h3>
                        <table class="w-full text-left text-sm text-text">
                            <tbody>
                                <tr v-for="i in datos.intervenciones" :key="i.id" class="border-b border-border align-top">
                                    <td class="whitespace-nowrap px-4 py-3 text-text-3">{{ fecha(i.abierta_at) }}</td>
                                    <td class="px-4 py-3">
                                        <Link :href="route('admin.intervenciones.show', i.id)" class="text-primary hover:underline">
                                            #{{ i.id }}
                                        </Link>
                                        — {{ i.motivo }}
                                        <div class="text-xs text-text-3">origen: {{ i.origen }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-text-3">
                                        {{ i.vigente ? `activa (${i.minutos_restantes} min)` : i.estado }}
                                    </td>
                                </tr>
                                <tr v-if="!datos.intervenciones.length">
                                    <td class="px-4 py-8 text-center text-text-3">El soporte nunca entró a este negocio.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
