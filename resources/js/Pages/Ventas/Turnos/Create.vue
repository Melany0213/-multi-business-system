<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    almacenes: Array,
});

const form = useForm({
    almacen_id: '',
    dispositivo: '',
    tasa_usd: '',
    tasa_eur: '',
});

const submit = () => {
    form.post(route('turnos.store'));
};
</script>

<template>
    <Head title="Abrir turno" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text">
                Abrir turno de venta
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="almacen_id" value="Almacén" />
                            <select
                                id="almacen_id"
                                v-model="form.almacen_id"
                                class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary"
                                required
                            >
                                <option value="" disabled>Selecciona un almacén</option>
                                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                                    {{ almacen.nombre }}
                                </option>
                            </select>
                            <InputError :message="form.errors.almacen_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="dispositivo" value="Dispositivo (nombre del terminal)" />
                            <TextInput
                                id="dispositivo"
                                v-model="form.dispositivo"
                                class="mt-1 block w-full"
                                placeholder="Ej. Caja 1, Super 4"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.dispositivo" class="mt-2" />
                        </div>

                        <div>
                            <p class="text-sm font-medium text-text">Tasa de cambio de hoy (opcional)</p>
                            <p class="mt-1 text-xs text-text-3">
                                Cuántas unidades de la moneda del negocio equivalen a 1 USD / 1 EUR hoy.
                            </p>

                            <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="tasa_usd" value="1 USD =" />
                                    <TextInput
                                        id="tasa_usd"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="form.tasa_usd"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.tasa_usd" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="tasa_eur" value="1 EUR =" />
                                    <TextInput
                                        id="tasa_eur"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="form.tasa_eur"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.tasa_eur" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">
                                Abrir turno
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
