<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    catalogo: Array,
});

const form = useForm({
    producto_id: '',
    nombre: '',
    sku: '',
    categoria: '',
    precio: '',
    costo: '',
    imagen: null,
});

const usaExistente = computed(() => !!form.producto_id);

function onImagen(event) {
    form.imagen = event.target.files[0] ?? null;
}

const submit = () => {
    form.post(route('productos.store'), { forceFormData: true });
};
</script>

<template>
    <Head title="Nuevo producto" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">Nuevo producto</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div v-if="catalogo.length">
                            <InputLabel for="producto_id" value="Producto del catálogo de tu cuenta (opcional)" />
                            <select
                                id="producto_id"
                                v-model="form.producto_id"
                                class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                            >
                                <option value="">— Crear producto nuevo —</option>
                                <option v-for="p in catalogo" :key="p.id" :value="p.id">
                                    {{ p.nombre }} <template v-if="p.sku">({{ p.sku }})</template>
                                </option>
                            </select>
                            <p class="mt-2 text-xs text-text-3">
                                Ya lo ofrece otro de tus negocios — elígelo aquí para venderlo también en este, solo con su propio precio y costo.
                            </p>
                        </div>

                        <template v-if="!usaExistente">
                            <div>
                                <InputLabel for="nombre" value="Nombre del producto" />
                                <TextInput
                                    id="nombre"
                                    v-model="form.nombre"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.nombre" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="sku" value="SKU / código (opcional)" />
                                    <TextInput id="sku" v-model="form.sku" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.sku" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="categoria" value="Categoría (opcional)" />
                                    <TextInput id="categoria" v-model="form.categoria" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.categoria" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="imagen" value="Foto (opcional)" />
                                <input
                                    id="imagen"
                                    type="file"
                                    accept="image/*"
                                    @change="onImagen"
                                    class="mt-1 block w-full text-sm text-text-2 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary"
                                />
                                <InputError :message="form.errors.imagen" class="mt-2" />
                            </div>
                        </template>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="precio" value="Precio de venta en este negocio" />
                                <TextInput
                                    id="precio"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="form.precio"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.precio" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="costo" value="Costo de compra en este negocio" />
                                <TextInput
                                    id="costo"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="form.costo"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.costo" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
