<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    negocio: Object,
    basesPorcentaje: Object,
    puedeDelegar: Boolean,
});

const form = useForm({
    salario_monto_fijo: props.negocio.salario_monto_fijo,
    salario_porcentaje: props.negocio.salario_porcentaje,
    salario_base_porcentaje: props.negocio.salario_base_porcentaje,
    admin_puede_configurar_salario: props.negocio.admin_puede_configurar_salario,
});

const resumen = computed(() => {
    const fijo = Number(form.salario_monto_fijo) || 0;
    const porcentaje = Number(form.salario_porcentaje) || 0;
    const base = props.basesPorcentaje[form.salario_base_porcentaje]?.toLowerCase() ?? 'venta';

    const partes = [];
    if (fijo > 0) partes.push(`${negocio.moneda} ${fijo.toFixed(2)} fijos`);
    if (porcentaje > 0) partes.push(`${porcentaje}% de la ${base}`);

    return partes.length ? `Salario = ${partes.join(' + ')} por turno.` : 'Salario = 0 (no configurado todavía).';
});

function submit() {
    form.patch(route('negocios.salario.update', props.negocio.id));
}
</script>

<template>
    <Head :title="`Salario · ${negocio.nombre}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">
                    Salario del cajero · {{ negocio.nombre }}
                </h2>
                <Link :href="route('negocios.edit', negocio.id)" class="text-sm font-medium text-primary hover:underline">
                    ← Volver al negocio
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <p class="text-sm text-text-3">
                        Se calcula al cerrar cada turno. Podés combinar un monto fijo con un porcentaje —
                        dejá el que no uses en 0.
                    </p>

                    <form @submit.prevent="submit" class="mt-6 space-y-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="salario_monto_fijo" :value="`Monto fijo por turno (${negocio.moneda})`" />
                                <TextInput
                                    id="salario_monto_fijo"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="form.salario_monto_fijo"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.salario_monto_fijo" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="salario_porcentaje" value="Porcentaje adicional (%)" />
                                <TextInput
                                    id="salario_porcentaje"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    v-model="form.salario_porcentaje"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.salario_porcentaje" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="salario_base_porcentaje" value="El porcentaje se calcula sobre" />
                            <select
                                id="salario_base_porcentaje"
                                v-model="form.salario_base_porcentaje"
                                class="mt-1 block w-full rounded-lg border-border-strong bg-surface text-text focus:border-primary focus:ring-primary sm:w-64"
                            >
                                <option v-for="(etiqueta, valor) in basesPorcentaje" :key="valor" :value="valor">
                                    {{ etiqueta }}
                                </option>
                            </select>
                            <InputError :message="form.errors.salario_base_porcentaje" class="mt-2" />
                        </div>

                        <div class="rounded-lg border border-border bg-surface-2 px-4 py-3 text-sm text-text-2">
                            {{ resumen }}
                        </div>

                        <hr class="border-border" />

                        <div>
                            <label class="flex items-start gap-3" :class="{ 'opacity-50': !puedeDelegar }">
                                <input
                                    type="checkbox"
                                    v-model="form.admin_puede_configurar_salario"
                                    :disabled="!puedeDelegar"
                                    class="mt-1 rounded border-border-strong text-primary focus:ring-primary"
                                />
                                <span>
                                    <span class="block text-sm font-medium text-text">
                                        Permitir que el administrador ajuste estos parámetros
                                    </span>
                                    <span class="block text-xs text-text-3">
                                        Con esto activado, cualquier usuario con rol Administrador en este negocio
                                        puede entrar a esta misma pantalla y cambiar el monto/porcentaje.
                                    </span>
                                </span>
                            </label>
                            <p v-if="!puedeDelegar" class="mt-2 text-xs text-text-3">
                                Solo el dueño de la cuenta puede activar o desactivar esta delegación.
                            </p>
                        </div>

                        <div class="flex justify-end">
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
