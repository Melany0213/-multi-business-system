<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    planes: Array,
});

const form = useForm({
    nombre_cliente: '',
    rut: '',
    telefono: '',
    plan_id: '',
    dueno_nombre: '',
    dueno_primer_apellido: '',
    dueno_segundo_apellido: '',
    dueno_email: '',
    dueno_telefono: '',
    dueno_carnet_identidad: '',
    dueno_password: '',
});

const submit = () => {
    form.post(route('admin.cuentas.store'));
};
</script>

<template>
    <Head title="Nueva cuenta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Nueva cuenta
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="nombre_cliente" value="Nombre del cliente" />
                            <TextInput
                                id="nombre_cliente"
                                v-model="form.nombre_cliente"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.nombre_cliente" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel for="rut" value="RUT / identificador fiscal (opcional)" />
                                <TextInput
                                    id="rut"
                                    v-model="form.rut"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.rut" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="telefono" value="Teléfono de la cuenta (opcional)" />
                                <TextInput
                                    id="telefono"
                                    v-model="form.telefono"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.telefono" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="plan_id" value="Plan (opcional)" />
                                <select
                                    id="plan_id"
                                    v-model="form.plan_id"
                                    class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Sin plan</option>
                                    <option v-for="plan in planes" :key="plan.id" :value="plan.id">
                                        {{ plan.nombre }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.plan_id" class="mt-2" />
                            </div>
                        </div>

                        <hr class="border-border" />

                        <p class="text-sm text-text-3">
                            Datos del Super Admin del Negocio (dueño) para esta cuenta. El nombre de usuario
                            para iniciar sesión se genera automáticamente a partir del nombre y los apellidos.
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel for="dueno_nombre" value="Nombre" />
                                <TextInput
                                    id="dueno_nombre"
                                    v-model="form.dueno_nombre"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.dueno_nombre" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="dueno_primer_apellido" value="Primer apellido" />
                                <TextInput
                                    id="dueno_primer_apellido"
                                    v-model="form.dueno_primer_apellido"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.dueno_primer_apellido" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="dueno_segundo_apellido" value="Segundo apellido" />
                                <TextInput
                                    id="dueno_segundo_apellido"
                                    v-model="form.dueno_segundo_apellido"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.dueno_segundo_apellido" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="dueno_email" value="Email" />
                                <TextInput
                                    id="dueno_email"
                                    type="email"
                                    v-model="form.dueno_email"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.dueno_email" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="dueno_telefono" value="Teléfono (opcional)" />
                                <TextInput
                                    id="dueno_telefono"
                                    v-model="form.dueno_telefono"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.dueno_telefono" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="dueno_carnet_identidad" value="Carnet de identidad (opcional)" />
                            <TextInput
                                id="dueno_carnet_identidad"
                                v-model="form.dueno_carnet_identidad"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.dueno_carnet_identidad" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="dueno_password" value="Contraseña inicial" />
                            <TextInput
                                id="dueno_password"
                                type="password"
                                v-model="form.dueno_password"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.dueno_password" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">
                                Crear cuenta
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
