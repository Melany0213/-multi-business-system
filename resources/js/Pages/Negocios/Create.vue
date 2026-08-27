<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    rubros: Array,
});

const form = useForm({
    nombre: '',
    rubro_id: '',
    tipo: 'productos',
    moneda: 'USD',
});

const submit = () => {
    form.post(route('negocios.store'));
};
</script>

<template>
    <Head title="Nuevo negocio" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Nuevo negocio
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="nombre" value="Nombre del negocio" />
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
                                <InputLabel for="rubro_id" value="Rubro (opcional)" />
                                <select
                                    id="rubro_id"
                                    v-model="form.rubro_id"
                                    class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Sin rubro</option>
                                    <option v-for="rubro in rubros" :key="rubro.id" :value="rubro.id">
                                        {{ rubro.nombre }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.rubro_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="tipo" value="Tipo" />
                                <select
                                    id="tipo"
                                    v-model="form.tipo"
                                    class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                                >
                                    <option value="productos">Productos</option>
                                    <option value="servicios">Servicios</option>
                                    <option value="mixto">Mixto</option>
                                </select>
                                <InputError :message="form.errors.tipo" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="moneda" value="Moneda" />
                            <TextInput
                                id="moneda"
                                v-model="form.moneda"
                                class="mt-1 block w-full sm:w-32"
                                maxlength="10"
                                required
                            />
                            <InputError :message="form.errors.moneda" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">
                                Crear negocio
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
