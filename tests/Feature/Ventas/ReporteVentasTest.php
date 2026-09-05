<?php

namespace Tests\Feature\Ventas;

use App\Models\Access;
use App\Models\Account;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReporteVentasTest extends TestCase
{
    use RefreshDatabase;

    private Business $negocio;

    private Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $this->negocio = Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);
        $this->almacen = $this->negocio->almacenes()->create(['nombre' => 'Almacén X']);
    }

    private function darAcceso(User $user, string $rol): void
    {
        Access::create([
            'user_id' => $user->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName($rol)->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ]);
    }

    public function test_una_dependienta_no_puede_ver_los_reportes(): void
    {
        $dependienta = User::factory()->create();
        $this->darAcceso($dependienta, 'dependiente');

        $this->actingAs($dependienta)->get(route('reportes.ventas'))->assertForbidden();
    }

    public function test_un_administrador_ve_el_resumen_de_hoy_y_los_turnos_abiertos(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');

        $cajero = User::factory()->create();

        Turno::create([
            'business_id' => $this->negocio->id,
            'almacen_id' => $this->almacen->id,
            'cajero_id' => $cajero->id,
            'dispositivo' => 'Caja cerrada',
            'estado' => 'cerrado',
            'fecha_apertura' => now(),
            'fecha_cierre' => now(),
            'total_venta' => 50,
            'total_utilidad' => 20,
            'salario' => 5,
        ]);

        Turno::create([
            'business_id' => $this->negocio->id,
            'almacen_id' => $this->almacen->id,
            'cajero_id' => $cajero->id,
            'dispositivo' => 'Caja abierta',
            'estado' => 'abierto',
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('reportes.ventas'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ventas/Reportes/Index')
                ->where('operacion.hoy.turnos_cerrados', 1)
                ->where('operacion.hoy.venta', 50)
                ->has('turnosAbiertos', 1)
            );
    }
}
