<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    cuenta: Object,
    planes: Array,
});

const form = useForm({
    nombre_cliente: props.cuenta.nombre_cliente,
    rut: props.cuenta.rut ?? '',
    telefono: props.cuenta.telefono ?? '',
    plan_id: props.cuenta.plan_id ?? '',
});

const submit = () => {
    form.patch(route('admin.cuentas.update', props.cuenta.id));
};

const eliminar = () => {
    if (confirm('¿Eliminar esta cuenta? Se eliminarán también sus negocios y accesos. Los usuarios (dueño, administradores, dependientas) no se eliminan. Esta acción no se puede deshacer.')) {
        router.delete(route('admin.cuentas.destroy', props.cuenta.id));
    }
};
</script>

<template>
    <Head :title="`Editar ${cuenta.nombre_cliente}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Editar cuenta
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
                                <InputLabel for="rut" value="RUT / identificador fiscal" />
                                <TextInput
                                    id="rut"
                                    v-model="form.rut"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.rut" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="telefono" value="Teléfono" />
                                <TextInput
                                    id="telefono"
                                    v-model="form.telefono"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.telefono" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="plan_id" value="Plan" />
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

                        <div class="flex items-center justify-between">
                            <DangerButton type="button" @click="eliminar">
                                Eliminar cuenta
                            </DangerButton>

                            <PrimaryButton :disabled="form.processing">
                                Guardar
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
