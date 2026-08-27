<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    cuenta: Object,
    usuarios: Array,
});
</script>

<template>
    <Head :title="cuenta.nombre_cliente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">
                    {{ cuenta.nombre_cliente }}
                </h2>
                <Link
                    :href="route('admin.cuentas.edit', cuenta.id)"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    Editar
                </Link>
            </div>
        </template>

        <div class="space-y-6 py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase text-text-3">Plan</dt>
                            <dd class="text-text">{{ cuenta.plan?.nombre ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-text-3">Estado</dt>
                            <dd class="text-text">{{ cuenta.estado }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-text-3">RUT / identificador fiscal</dt>
                            <dd class="text-text">{{ cuenta.rut ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-text-3">Teléfono</dt>
                            <dd class="text-text">{{ cuenta.telefono ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-text-3">Dueño</dt>
                            <dd class="text-text">
                                {{ cuenta.owner?.name }} ({{ cuenta.owner?.email }})
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-text-3">Usuario de acceso del dueño</dt>
                            <dd class="font-mono text-text">{{ cuenta.owner?.username }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <h3 class="mb-4 font-medium text-text">Negocios</h3>
                    <ul v-if="cuenta.businesses.length" class="space-y-2">
                        <li
                            v-for="negocio in cuenta.businesses"
                            :key="negocio.id"
                            class="rounded-lg border border-border p-3 text-sm"
                        >
                            {{ negocio.nombre }} · {{ negocio.rubro?.nombre ?? 'Sin rubro' }} · {{ negocio.tipo }} · {{ negocio.estado }}
                        </li>
                    </ul>
                    <p v-else class="text-sm text-text-3">Sin negocios todavía.</p>
                </div>
            </div>

            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface p-6 shadow-sm">
                    <h3 class="mb-4 font-medium text-text">Usuarios de esta cuenta</h3>
                    <ul v-if="usuarios.length" class="space-y-2">
                        <li
                            v-for="usuario in usuarios"
                            :key="usuario.id"
                            class="flex items-center justify-between rounded-lg border border-border p-3 text-sm"
                        >
                            <span>{{ usuario.name }} ({{ usuario.email }})</span>
                            <span class="text-xs text-text-3">{{ usuario.estado_global }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-text-3">Sin usuarios asociados.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
