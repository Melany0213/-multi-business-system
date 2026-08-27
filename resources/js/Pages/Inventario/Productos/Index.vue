<script setup>
import { reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    productos: Array,
    almacenes: Array,
    puedeEditar: Boolean,
});

const stockInputs = reactive({});
for (const producto of props.productos) {
    for (const linea of producto.stock_por_almacen) {
        stockInputs[`${producto.id}-${linea.almacen_id}`] = linea.cantidad;
    }
    for (const almacen of props.almacenes) {
        const key = `${producto.id}-${almacen.id}`;
        if (!(key in stockInputs)) {
            stockInputs[key] = 0;
        }
    }
}

function guardarStock(producto, almacen) {
    const cantidad = stockInputs[`${producto.id}-${almacen.id}`];
    router.patch(route('productos.stock', [producto.id, almacen.id]), { cantidad }, { preserveScroll: true });
}

function toggleEstado(producto) {
    router.patch(route('productos.toggle-estado', producto.id), {}, { preserveScroll: true });
}

function formatMoney(value) {
    return `$${Number(value).toFixed(2)}`;
}
</script>

<template>
    <Head title="Productos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">Productos</h2>
                <Link v-if="puedeEditar" :href="route('productos.create')">
                    <PrimaryButton>Nuevo producto</PrimaryButton>
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
                                <th class="px-6 py-3">Categoría</th>
                                <th class="px-6 py-3">Precio</th>
                                <th class="px-6 py-3">Costo</th>
                                <th class="px-6 py-3">Margen</th>
                                <th class="px-6 py-3">Stock por almacén</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="producto in productos" :key="producto.id" class="border-b border-border align-top">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="producto.imagen_url"
                                            :src="producto.imagen_url"
                                            class="h-10 w-10 rounded-lg object-cover"
                                            alt=""
                                        />
                                        <div v-else class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-2 font-mono text-xs text-text-3">
                                            {{ producto.nombre.slice(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ producto.nombre }}</div>
                                            <div v-if="producto.sku" class="font-mono text-xs text-text-3">{{ producto.sku }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ producto.categoria ?? '—' }}</td>
                                <td class="px-6 py-4 font-mono">{{ formatMoney(producto.precio) }}</td>
                                <td class="px-6 py-4 font-mono">{{ formatMoney(producto.costo) }}</td>
                                <td class="px-6 py-4 font-mono">
                                    {{ formatMoney(producto.margen) }}
                                    <span v-if="producto.margen_porcentaje !== null" class="text-xs text-text-3">
                                        ({{ producto.margen_porcentaje }}%)
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-for="almacen in almacenes" :key="almacen.id" class="mb-1 flex items-center gap-2 last:mb-0">
                                        <span class="w-28 truncate text-xs text-text-3">{{ almacen.nombre }}</span>
                                        <input
                                            v-if="puedeEditar"
                                            type="number"
                                            min="0"
                                            v-model.number="stockInputs[`${producto.id}-${almacen.id}`]"
                                            @change="guardarStock(producto, almacen)"
                                            class="w-20 rounded-md border-border-strong bg-surface py-1 text-sm text-text focus:border-primary focus:ring-primary"
                                        />
                                        <span v-else class="font-mono">{{ stockInputs[`${producto.id}-${almacen.id}`] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="producto.estado === 'activo'
                                            ? 'bg-success-soft text-success'
                                            : 'bg-danger-soft text-danger'"
                                    >
                                        {{ producto.estado }}
                                    </span>
                                </td>
                                <td v-if="puedeEditar" class="px-6 py-4 text-right space-x-3">
                                    <Link
                                        :href="route('productos.edit', producto.id)"
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        @click="toggleEstado(producto)"
                                        class="text-sm font-medium hover:underline"
                                        :class="producto.estado === 'activo' ? 'text-danger' : 'text-success'"
                                    >
                                        {{ producto.estado === 'activo' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </td>
                                <td v-else></td>
                            </tr>
                            <tr v-if="productos.length === 0">
                                <td colspan="8" class="px-6 py-8 text-center text-text-3">
                                    Aún no hay productos en este negocio.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
