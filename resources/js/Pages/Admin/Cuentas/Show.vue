<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    cuenta: Object,
    usuarios: Array,
});

const usuarioSeleccionado = ref(null);

function verUsuario(usuario) {
    usuarioSeleccionado.value = usuario;
}

function cerrarModal() {
    usuarioSeleccionado.value = null;
}

function formatearRol(nombre) {
    return nombre ? nombre.replace(/_/g, ' ') : '—';
}
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
                        >
                            <button
                                type="button"
                                @click="verUsuario(usuario)"
                                class="flex w-full items-center justify-between rounded-lg border border-border p-3 text-left text-sm transition hover:bg-surface-2"
                            >
                                <span>{{ usuario.name }} ({{ usuario.email }})</span>
                                <span class="flex items-center gap-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="usuario.estado_global === 'activo'
                                            ? 'bg-success-soft text-success'
                                            : 'bg-danger-soft text-danger'"
                                    >
                                        {{ usuario.estado_global }}
                                    </span>
                                    <span class="text-xs text-text-3">Ver detalle →</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-text-3">Sin usuarios asociados.</p>
                </div>
            </div>
        </div>

        <Modal :show="usuarioSeleccionado !== null" @close="cerrarModal">
            <div v-if="usuarioSeleccionado" class="p-6">
                <h3 class="text-lg font-medium text-text">
                    {{ usuarioSeleccionado.name }} {{ usuarioSeleccionado.primer_apellido }} {{ usuarioSeleccionado.segundo_apellido }}
                </h3>

                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase text-text-3">Usuario de acceso</dt>
                        <dd class="font-mono text-text">{{ usuarioSeleccionado.username }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-text-3">Email</dt>
                        <dd class="text-text">{{ usuarioSeleccionado.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-text-3">Teléfono</dt>
                        <dd class="text-text">{{ usuarioSeleccionado.telefono ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-text-3">Carnet de identidad</dt>
                        <dd class="text-text">{{ usuarioSeleccionado.carnet_identidad ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-text-3">Estado</dt>
                        <dd class="text-text">{{ usuarioSeleccionado.estado_global }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-text-3">Rol en la plataforma</dt>
                        <dd class="text-text">
                            {{ usuarioSeleccionado.id === cuenta.owner?.id ? 'Dueño de la cuenta' : 'Colaborador' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <h4 class="mb-2 text-sm font-medium text-text">Accesos a negocios</h4>
                    <ul v-if="usuarioSeleccionado.accesses?.length" class="space-y-2">
                        <li
                            v-for="acceso in usuarioSeleccionado.accesses"
                            :key="acceso.id"
                            class="rounded-lg border border-border p-3 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ acceso.business?.nombre }}</span>
                                <span class="text-xs text-text-3">{{ acceso.estado }}</span>
                            </div>
                            <div class="text-text-2">
                                {{ formatearRol(acceso.role?.name) }} · vigencia {{ acceso.tipo_vigencia }}
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-text-3">Sin accesos a negocios registrados.</p>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="cerrarModal">Cerrar</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
