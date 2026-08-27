<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Catálogo base de permisos y roles del sistema (account_id = null → roles
 * de sistema, compartidos por todas las cuentas). Ver diseno-sistema-multinegocio.md §5.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'ventas.crear',
            'ventas.anular',
            'inventario.ver',
            'inventario.editar',
            'inventario.trasladar',          // dependienta: solicita traspaso de producto
            'inventario.autorizar_traslado',  // administrador: aprueba/rechaza el traspaso
            'caja.abrir',
            'caja.cerrar',
            'reportes.ver',
            'usuarios.gestionar',
            'negocio.configurar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminNegocio = Role::firstOrCreate(['name' => 'super_admin_negocio', 'guard_name' => 'web']);
        $superAdminNegocio->syncPermissions($permissions);

        $administrador = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $administrador->syncPermissions([
            'ventas.crear',
            'ventas.anular',
            'inventario.ver',
            'inventario.editar',
            'inventario.trasladar',
            'inventario.autorizar_traslado',
            'caja.abrir',
            'caja.cerrar',
            'reportes.ver',
            'usuarios.gestionar',
        ]);

        $dependiente = Role::firstOrCreate(['name' => 'dependiente', 'guard_name' => 'web']);
        $dependiente->syncPermissions([
            'ventas.crear',
            'inventario.ver',
            'inventario.trasladar',
            'caja.abrir',
            'caja.cerrar',
        ]);
    }
}
