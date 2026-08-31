<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    actividad: Object,
    usuarios: Array,
    filtros: Object,
});

function nombreCompleto(usuario) {
    return [usuario.name, usuario.primer_apellido, usuario.segundo_apellido].filter(Boolean).join(' ');
}

function filtrarPorUsuario(event) {
    router.get(
        route('admin.actividad.index'),
        { usuario_id: event.target.value || undefined },
        { preserveState: true, preserveScroll: true },
    );
}

function contexto(item) {
    return [item.account?.nombre_cliente, item.business?.nombre].filter(Boolean).join(' · ') || '—';
}
</script>

<template>
    <Head title="Actividad" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-text">
                    Bitácora de actividad
                </h2>
                <select
                    :value="filtros.usuario_id ?? ''"
                    @change="filtrarPorUsuario"
                    class="block rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                >
                    <option value="">Todos los usuarios</option>
                    <option v-for="usuario in usuarios" :key="usuario.id" :value="usuario.id">
                        {{ nombreCompleto(usuario) }} ({{ usuario.username }})
                    </option>
                </select>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                    <table class="w-full text-left text-sm text-text">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase text-text-3">
                            <tr>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Usuario</th>
                                <th class="px-6 py-3">Movimiento</th>
                                <th class="px-6 py-3">Contexto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in actividad.data" :key="item.id" class="border-b border-border align-top">
                                <td class="whitespace-nowrap px-6 py-4 text-text-3">
                                    {{ new Date(item.created_at).toLocaleString('es') }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ item.causer ? nombreCompleto(item.causer) : 'Sistema' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ item.description }}
                                    <div v-if="item.changes" class="mt-1 font-mono text-xs text-text-3">
                                        {{ Object.keys(item.changes).join(', ') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-text-3">{{ contexto(item) }}</td>
                            </tr>
                            <tr v-if="actividad.data.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-text-3">
                                    Sin movimientos registrados todavía.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="actividad.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
                    <Link
                        v-for="(link, index) in actividad.links"
                        :key="index"
                        :href="link.url ?? '#'"
                        preserve-scroll
                        preserve-state
                        class="rounded-lg border px-3 py-1.5 text-sm"
                        :class="[
                            link.active ? 'border-primary bg-primary-soft text-primary' : 'border-border text-text-2',
                            !link.url && 'pointer-events-none opacity-40',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
