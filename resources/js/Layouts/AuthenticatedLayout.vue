<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import SidebarLink from '@/Components/SidebarLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/theme';

const showingMobileSidebar = ref(false);
const { isDark, toggle } = useTheme();

const page = usePage();

function cambiarNegocio(event) {
    const id = event.target.value;
    if (id) {
        router.patch(route('negocio-activo.update', id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="flex min-h-screen bg-bg">
        <!-- Sidebar (desktop) -->
        <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-border lg:bg-surface">
            <div class="flex h-16 shrink-0 items-center gap-2 border-b border-border px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-8 fill-current text-primary" />
                    <span class="font-semibold text-text">Multinegocio</span>
                </Link>
            </div>

            <div v-if="page.props.misNegocios?.length" class="border-b border-border px-3 py-3">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-text-3">Negocio activo</label>
                <select
                    :value="page.props.negocioActivo?.id"
                    @change="cambiarNegocio"
                    class="block w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                >
                    <option v-for="n in page.props.misNegocios" :key="n.id" :value="n.id">
                        {{ n.nombre }}
                    </option>
                </select>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <SidebarLink :href="route('dashboard')" :active="route().current('dashboard')">
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </template>
                    Dashboard
                </SidebarLink>

                <SidebarLink
                    v-if="$page.props.auth.esDueno"
                    :href="route('negocios.index')"
                    :active="route().current('negocios.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m16.5 0a3.001 3.001 0 01-3.75.614 3 3 0 01-2.25-1.015 2.993 2.993 0 01-2.25 1.015 3 3 0 01-2.25-1.015A2.993 2.993 0 019.75 9.964a3 3 0 01-2.25 1.015 3.001 3.001 0 01-3.75-.63m16.5.001a3.004 3.004 0 00-.621-4.72L18.6 3.44A1.5 1.5 0 0017.539 3H6.46a1.5 1.5 0 00-1.06.44l-1.83 1.83a3.004 3.004 0 00-.62 4.72m16.5 0-.001.031c0 .225-.012.447-.037.665A3 3 0 0117.25 12a3 3 0 01-2.83-2.24" />
                        </svg>
                    </template>
                    Negocios
                </SidebarLink>

                <template v-if="page.props.negocioActivo">
                    <SidebarLink :href="route('almacenes.index')" :active="route().current('almacenes.*')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </template>
                        Almacenes
                    </SidebarLink>

                    <SidebarLink :href="route('productos.index')" :active="route().current('productos.*')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                            </svg>
                        </template>
                        Productos
                    </SidebarLink>

                    <SidebarLink :href="route('traspasos.index')" :active="route().current('traspasos.*')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </template>
                        Traspasos
                    </SidebarLink>

                    <SidebarLink :href="route('turnos.index')" :active="route().current('turnos.*') || route().current('ventas.*')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.625c.621 0 1.125.504 1.125 1.125V6h-.75a.75.75 0 01-.75-.75v-.75m0 0H3.75m0 0v6.75m0 0a.75.75 0 01-.75.75h-.75m18 0v6.75m0 0v.75c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 013 20.25v-.75m18-6a.75.75 0 01-.75-.75V12" />
                            </svg>
                        </template>
                        Turnos de venta
                    </SidebarLink>

                    <SidebarLink
                        v-if="page.props.negocioActivo.puedeConfigurarSalario"
                        :href="route('negocios.salario.edit', page.props.negocioActivo.id)"
                        :active="route().current('negocios.salario.*')"
                    >
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                        Salario del cajero
                    </SidebarLink>
                </template>

                <template v-if="$page.props.auth.user.is_super_admin_sistema">
                    <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-text-3">
                        Administración
                    </p>

                    <SidebarLink :href="route('admin.panel')" :active="route().current('admin.panel')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5" />
                            </svg>
                        </template>
                        Panel general
                    </SidebarLink>

                    <SidebarLink :href="route('admin.negocios.index')" :active="route().current('admin.negocios.*')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m16.5 0a3.001 3.001 0 01-3.75.614 3 3 0 01-2.25-1.015 2.993 2.993 0 01-2.25 1.015 3 3 0 01-2.25-1.015A2.993 2.993 0 019.75 9.964a3 3 0 01-2.25 1.015 3.001 3.001 0 01-3.75-.63m16.5.001a3.004 3.004 0 00-.621-4.72L18.6 3.44A1.5 1.5 0 0017.539 3H6.46a1.5 1.5 0 00-1.06.44l-1.83 1.83a3.004 3.004 0 00-.62 4.72m16.5 0-.001.031c0 .225-.012.447-.037.665A3 3 0 0117.25 12a3 3 0 01-2.83-2.24" />
                            </svg>
                        </template>
                        Negocios
                    </SidebarLink>

                    <SidebarLink
                        :href="route('admin.cuentas.index')"
                        :active="route().current('admin.cuentas.*')"
                    >
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.7M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </template>
                        Cuentas
                    </SidebarLink>

                    <SidebarLink :href="route('admin.actividad.index')" :active="route().current('admin.actividad.*')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                        Actividad
                    </SidebarLink>
                </template>
            </nav>
        </aside>

        <!-- Sidebar (mobile drawer) -->
        <div v-show="showingMobileSidebar" class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-text opacity-40" @click="showingMobileSidebar = false" />
            <aside class="fixed inset-y-0 left-0 flex w-64 flex-col border-r border-border bg-surface">
                <div class="flex h-16 shrink-0 items-center gap-2 border-b border-border px-6">
                    <ApplicationLogo class="h-8 w-8 fill-current text-primary" />
                    <span class="font-semibold text-text">Multinegocio</span>
                </div>

                <div v-if="page.props.misNegocios?.length" class="border-b border-border px-3 py-3">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-text-3">Negocio activo</label>
                    <select
                        :value="page.props.negocioActivo?.id"
                        @change="cambiarNegocio"
                        class="block w-full rounded-lg border-border-strong bg-surface text-sm text-text focus:border-primary focus:ring-primary"
                    >
                        <option v-for="n in page.props.misNegocios" :key="n.id" :value="n.id">
                            {{ n.nombre }}
                        </option>
                    </select>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" @click="showingMobileSidebar = false">
                    <SidebarLink :href="route('dashboard')" :active="route().current('dashboard')">
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                        </template>
                        Dashboard
                    </SidebarLink>

                    <SidebarLink
                        v-if="$page.props.auth.esDueno"
                        :href="route('negocios.index')"
                        :active="route().current('negocios.*')"
                    >
                        <template #icon>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m16.5 0a3.001 3.001 0 01-3.75.614 3 3 0 01-2.25-1.015 2.993 2.993 0 01-2.25 1.015 3 3 0 01-2.25-1.015A2.993 2.993 0 019.75 9.964a3 3 0 01-2.25 1.015 3.001 3.001 0 01-3.75-.63m16.5.001a3.004 3.004 0 00-.621-4.72L18.6 3.44A1.5 1.5 0 0017.539 3H6.46a1.5 1.5 0 00-1.06.44l-1.83 1.83a3.004 3.004 0 00-.62 4.72m16.5 0-.001.031c0 .225-.012.447-.037.665A3 3 0 0117.25 12a3 3 0 01-2.83-2.24" />
                            </svg>
                        </template>
                        Negocios
                    </SidebarLink>

                    <template v-if="page.props.negocioActivo">
                        <SidebarLink :href="route('almacenes.index')" :active="route().current('almacenes.*')">
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                            </template>
                            Almacenes
                        </SidebarLink>

                        <SidebarLink :href="route('productos.index')" :active="route().current('productos.*')">
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </template>
                            Productos
                        </SidebarLink>

                        <SidebarLink :href="route('traspasos.index')" :active="route().current('traspasos.*')">
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                </svg>
                            </template>
                            Traspasos
                        </SidebarLink>

                        <SidebarLink :href="route('turnos.index')" :active="route().current('turnos.*') || route().current('ventas.*')">
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.625c.621 0 1.125.504 1.125 1.125V6h-.75a.75.75 0 01-.75-.75v-.75m0 0H3.75m0 0v6.75m0 0a.75.75 0 01-.75.75h-.75m18 0v6.75m0 0v.75c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 013 20.25v-.75m18-6a.75.75 0 01-.75-.75V12" />
                                </svg>
                            </template>
                            Turnos de venta
                        </SidebarLink>
                    </template>

                    <template v-if="$page.props.auth.user.is_super_admin_sistema">
                        <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-text-3">
                            Administración
                        </p>

                        <SidebarLink :href="route('admin.panel')" :active="route().current('admin.panel')">
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5" />
                                </svg>
                            </template>
                            Panel general
                        </SidebarLink>

                        <SidebarLink :href="route('admin.negocios.index')" :active="route().current('admin.negocios.*')">
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m16.5 0a3.001 3.001 0 01-3.75.614 3 3 0 01-2.25-1.015 2.993 2.993 0 01-2.25 1.015 3 3 0 01-2.25-1.015A2.993 2.993 0 019.75 9.964a3 3 0 01-2.25 1.015 3.001 3.001 0 01-3.75-.63m16.5.001a3.004 3.004 0 00-.621-4.72L18.6 3.44A1.5 1.5 0 0017.539 3H6.46a1.5 1.5 0 00-1.06.44l-1.83 1.83a3.004 3.004 0 00-.62 4.72m16.5 0-.001.031c0 .225-.012.447-.037.665A3 3 0 0117.25 12a3 3 0 01-2.83-2.24" />
                                </svg>
                            </template>
                            Negocios
                        </SidebarLink>

                        <SidebarLink
                            :href="route('admin.cuentas.index')"
                            :active="route().current('admin.cuentas.*')"
                        >
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.7M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                            </template>
                            Cuentas
                        </SidebarLink>

                        <SidebarLink :href="route('admin.actividad.index')" :active="route().current('admin.actividad.*')">
                            <template #icon>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            Actividad
                        </SidebarLink>
                    </template>
                </nav>
            </aside>
        </div>

        <!-- Main column -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <!-- Top bar -->
            <div class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-4 border-b border-border bg-surface px-4 sm:px-6 lg:px-8">
                <button
                    type="button"
                    class="text-text-3 hover:text-text lg:hidden"
                    @click="showingMobileSidebar = true"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <div class="flex-1" />

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-transparent bg-surface px-3 py-2 text-sm font-medium leading-4 text-text-2 transition duration-150 ease-in-out hover:text-text focus:outline-none"
                        >
                            {{ $page.props.auth.user.name }}

                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                        <button
                            type="button"
                            @click="toggle"
                            class="flex w-full items-center justify-between px-4 py-2 text-start text-sm leading-5 text-text-2 transition duration-150 ease-in-out hover:bg-surface-2 focus:bg-surface-2 focus:outline-none"
                        >
                            <span>Tema</span>
                            <span class="font-mono text-xs text-text-3">{{ isDark ? 'oscuro' : 'claro' }}</span>
                        </button>
                        <DropdownLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>

            <!-- Page Heading -->
            <header class="bg-surface shadow" v-if="$slots.header">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
