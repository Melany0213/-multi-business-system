<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    productos: Array,
    almacenesOrigen: Array,
    destinos: Array,
});

const form = useForm({
    producto_id: '',
    almacen_origen_id: props.almacenesOrigen[0]?.id ?? '',
    almacen_destino_id: '',
    cantidad: 1,
    notas: '',
});

const submit = () => {
    form.post(route('traspasos.store'));
};
</script>

<template>
    <Head title="Solicitar traspaso" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">Solicitar traspaso de mercancía</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="producto_id" value="Producto" />
                            <select
                                id="producto_id"
                                v-model="form.producto_id"
                                required
                                class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                            >
                                <option value="" disabled>Selecciona un producto</option>
                                <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                            </select>
                            <InputError :message="form.errors.producto_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="almacen_origen_id" value="Almacén de origen (tu negocio activo)" />
                            <select
                                id="almacen_origen_id"
                                v-model="form.almacen_origen_id"
                                required
                                class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                            >
                                <option v-for="a in almacenesOrigen" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                            </select>
                            <InputError :message="form.errors.almacen_origen_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="almacen_destino_id" value="Almacén de destino" />
                            <select
                                id="almacen_destino_id"
                                v-model="form.almacen_destino_id"
                                required
                                class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                            >
                                <option value="" disabled>Selecciona un destino</option>
                                <option v-for="d in destinos" :key="d.id" :value="d.id">{{ d.etiqueta }}</option>
                            </select>
                            <InputError :message="form.errors.almacen_destino_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="cantidad" value="Cantidad" />
                            <TextInput
                                id="cantidad"
                                type="number"
                                min="1"
                                v-model="form.cantidad"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.cantidad" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="notas" value="Notas (opcional)" />
                            <textarea
                                id="notas"
                                v-model="form.notas"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                            ></textarea>
                            <InputError :message="form.errors.notas" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">Solicitar traspaso</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
