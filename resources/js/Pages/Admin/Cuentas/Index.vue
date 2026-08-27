<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    cuentas: Array,
});

function suspender(cuenta) {
    router.patch(route('admin.cuentas.suspend', cuenta.id), {}, { preserveScroll: true });
}

function reactivar(cuenta) {
    router.patch(route('admin.cuentas.reactivate', cuenta.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Cuentas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">
                    Cuentas (clientes de la plataforma)
                </h2>
                <Link :href="route('admin.cuentas.create')">
                    <PrimaryButton>Nueva cuenta</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Cliente</th>
                                <th class="px-6 py-3">Dueño</th>
                                <th class="px-6 py-3">Plan</th>
                                <th class="px-6 py-3">Negocios</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="cuenta in cuentas"
                                :key="cuenta.id"
                                class="border-b border-border"
                            >
                                <td class="px-6 py-4 font-medium">
                                    <Link :href="route('admin.cuentas.show', cuenta.id)" class="hover:underline">
                                        {{ cuenta.nombre_cliente }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">
                                    {{ cuenta.owner?.name }}
                                    <div class="text-xs text-text-3">{{ cuenta.owner?.email }}</div>
                                </td>
                                <td class="px-6 py-4">{{ cuenta.plan?.nombre ?? '—' }}</td>
                                <td class="px-6 py-4 font-mono">{{ cuenta.businesses_count }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="cuenta.estado === 'activa'
                                            ? 'bg-success-soft text-success'
                                            : 'bg-danger-soft text-danger'"
                                    >
                                        {{ cuenta.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Link
                                        :href="route('admin.cuentas.edit', cuenta.id)"
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        v-if="cuenta.estado === 'activa'"
                                        @click="suspender(cuenta)"
                                        class="text-sm font-medium text-danger hover:underline"
                                    >
                                        Suspender
                                    </button>
                                    <button
                                        v-else
                                        @click="reactivar(cuenta)"
                                        class="text-sm font-medium text-success hover:underline"
                                    >
                                        Reactivar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="cuentas.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-text-3">
                                    Aún no hay cuentas registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
