<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre: '',
});

const submit = () => {
    form.post(route('almacenes.store'));
};
</script>

<template>
    <Head title="Nuevo almacén" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">Nuevo almacén</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="nombre" value="Nombre del almacén" />
                            <TextInput
                                id="nombre"
                                v-model="form.nombre"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.nombre" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">Crear almacén</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
