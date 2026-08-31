<script setup>
import { computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    turno: Object,
    productos: Array,
});

// cantidades[producto_id] = cantidad a vender (0 = no incluido en la venta)
const cantidades = reactive(Object.fromEntries(props.productos.map((p) => [p.id, 0])));

const form = useForm({
    metodo_pago: 'efectivo',
    items: [],
});

const carrito = computed(() =>
    props.productos
        .filter((p) => Number(cantidades[p.id]) > 0)
        .map((p) => ({ ...p, cantidad: Number(cantidades[p.id]) })),
);

const total = computed(() => carrito.value.reduce((suma, item) => suma + item.cantidad * item.precio, 0));

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es', { minimumFractionDigits: 2 }).format(valor ?? 0);
}

function submit() {
    form.items = carrito.value.map((item) => ({ producto_id: item.id, cantidad: item.cantidad }));
    form.post(route('ventas.store', turno.id));
}
</script>

<template>
    <Head title="Registrar venta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Registrar venta · {{ turno.dispositivo }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto grid max-w-5xl gap-6 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                        <table class="w-full text-left text-sm text-text">
                            <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                                <tr>
                                    <th class="px-4 py-3">Producto</th>
                                    <th class="px-4 py-3">Precio</th>
                                    <th class="px-4 py-3">Stock</th>
                                    <th class="px-4 py-3">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="producto in productos" :key="producto.id" class="border-b border-border">
                                    <td class="px-4 py-3 font-medium">{{ producto.nombre }}</td>
                                    <td class="px-4 py-3 font-mono">{{ formatearMoneda(producto.precio) }}</td>
                                    <td class="px-4 py-3 font-mono">{{ producto.stock }}</td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            min="0"
                                            :max="producto.stock"
                                            v-model="cantidades[producto.id]"
                                            class="w-20 rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                                        />
                                    </td>
                                </tr>
                                <tr v-if="productos.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-text-3">
                                        Este negocio todavía no tiene productos con stock en este almacén.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="rounded-lg border border-border bg-surface p-5 shadow-sm">
                        <h3 class="font-medium text-text">Resumen</h3>

                        <ul class="mt-3 space-y-1 text-sm">
                            <li v-for="item in carrito" :key="item.id" class="flex justify-between text-text-2">
                                <span>{{ item.nombre }} × {{ item.cantidad }}</span>
                                <span class="font-mono">{{ formatearMoneda(item.cantidad * item.precio) }}</span>
                            </li>
                            <li v-if="carrito.length === 0" class="text-text-3">Todavía no agregaste productos.</li>
                        </ul>

                        <div class="mt-3 flex justify-between border-t border-border pt-3 text-base font-semibold text-text">
                            <span>Total</span>
                            <span class="font-mono">{{ formatearMoneda(total) }}</span>
                        </div>

                        <div class="mt-4">
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-text-3">
                                Método de pago
                            </label>
                            <select
                                v-model="form.metodo_pago"
                                class="block w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                            >
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>

                        <InputError :message="form.errors.items" class="mt-2" />

                        <PrimaryButton
                            class="mt-4 w-full justify-center"
                            :disabled="form.processing || carrito.length === 0"
                            @click="submit"
                        >
                            Procesar pago
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
