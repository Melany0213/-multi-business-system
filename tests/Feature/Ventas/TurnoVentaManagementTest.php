<?php

namespace Tests\Feature\Ventas;

use App\Models\Access;
use App\Models\Account;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Producto;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TurnoVentaManagementTest extends TestCase
{
    use RefreshDatabase;

    private Account $cuenta;

    private Business $negocio;

    private Almacen $almacen;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $dueno = User::factory()->create();
        $this->cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $this->negocio = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);
        $this->almacen = $this->negocio->almacenes()->create(['nombre' => 'Almacén X']);

        $this->producto = $this->cuenta->productos()->create(['nombre' => 'Refresco']);
        $this->negocio->productos()->attach($this->producto->id, ['precio' => 10, 'costo' => 6]);
        $this->almacen->productos()->attach($this->producto->id, ['cantidad' => 20]);
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

    private function abrirTurno(User $cajero): Turno
    {
        $this->darAcceso($cajero, 'dependiente');

        $this->actingAs($cajero)->post(route('turnos.store'), [
            'almacen_id' => $this->almacen->id,
            'dispositivo' => 'Caja 1',
            'tasa_usd' => 400,
            'tasa_eur' => 430,
        ])->assertRedirect();

        return Turno::where('cajero_id', $cajero->id)->where('estado', 'abierto')->firstOrFail();
    }

    public function test_un_dependiente_puede_abrir_un_turno(): void
    {
        $cajero = User::factory()->create();

        $turno = $this->abrirTurno($cajero);

        $this->assertSame('abierto', $turno->estado);
        $this->assertSame($this->almacen->id, $turno->almacen_id);
    }

    public function test_no_se_puede_abrir_un_segundo_turno_mientras_hay_uno_abierto(): void
    {
        $cajero = User::factory()->create();
        $this->abrirTurno($cajero);

        $this->actingAs($cajero)->post(route('turnos.store'), [
            'almacen_id' => $this->almacen->id,
            'dispositivo' => 'Caja 2',
        ])->assertSessionHasErrors('dispositivo');

        $this->assertSame(1, Turno::where('cajero_id', $cajero->id)->count());
    }

    public function test_un_usuario_sin_acceso_al_negocio_no_puede_abrir_turno(): void
    {
        $cajero = User::factory()->create();

        $this->actingAs($cajero)->post(route('turnos.store'), [
            'almacen_id' => $this->almacen->id,
            'dispositivo' => 'Caja 1',
        ])->assertForbidden();
    }

    public function test_registrar_una_venta_descuenta_stock_y_calcula_el_total(): void
    {
        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        $this->actingAs($cajero)->post(route('ventas.store', $turno), [
            'metodo_pago' => 'efectivo',
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 3],
            ],
        ])->assertRedirect(route('turnos.show', $turno));

        $this->assertSame(17, $this->almacen->productos()->first()->pivot->cantidad);
        $this->assertDatabaseHas('ventas', ['turno_id' => $turno->id, 'monto_total' => 30]);
        $this->assertDatabaseHas('venta_detalles', [
            'producto_id' => $this->producto->id,
            'cantidad' => 3,
            'precio_unitario' => 10,
            'costo_unitario' => 6,
        ]);
    }

    public function test_no_se_puede_vender_mas_stock_del_disponible(): void
    {
        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        $this->actingAs($cajero)->post(route('ventas.store', $turno), [
            'metodo_pago' => 'efectivo',
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 999],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(20, $this->almacen->productos()->first()->pivot->cantidad);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_otro_cajero_no_puede_vender_en_un_turno_ajeno(): void
    {
        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        $otro = User::factory()->create();
        $this->darAcceso($otro, 'dependiente');

        $this->actingAs($otro)->post(route('ventas.store', $turno), [
            'metodo_pago' => 'efectivo',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertForbidden();
    }

    public function test_cerrar_turno_calcula_salario_como_porcentaje_de_la_utilidad(): void
    {
        $this->negocio->update(['salario_porcentaje' => 10, 'salario_base_porcentaje' => 'utilidad']);

        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        // Venta de 3 unidades: monto 30, utilidad (10-6)*3 = 12.
        $this->actingAs($cajero)->post(route('ventas.store', $turno), [
            'metodo_pago' => 'efectivo',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 3]],
        ]);

        $this->actingAs($cajero)->patch(route('turnos.cerrar', $turno), [
            'conteo' => ['locales' => [], 'usd' => 0, 'eur' => 0],
        ])->assertRedirect(route('turnos.show', $turno));

        $turno->refresh();
        $this->assertSame('cerrado', $turno->estado);
        $this->assertEquals(30.0, (float) $turno->total_venta);
        $this->assertEquals(12.0, (float) $turno->total_utilidad);
        $this->assertEquals(1.2, (float) $turno->salario); // 10% de 12
        $this->assertEquals(28.8, (float) $turno->deposito); // 30 - 0 - 1.2
    }

    public function test_cerrar_turno_calcula_salario_como_monto_fijo(): void
    {
        $this->negocio->update(['salario_monto_fijo' => 5]);

        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        $this->actingAs($cajero)->post(route('ventas.store', $turno), [
            'metodo_pago' => 'efectivo',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 2]],
        ]);

        $this->actingAs($cajero)->patch(route('turnos.cerrar', $turno), [
            'conteo' => ['locales' => [], 'usd' => 0, 'eur' => 0],
        ]);

        $this->assertEquals(5.0, (float) $turno->fresh()->salario);
    }

    public function test_cerrar_turno_combina_monto_fijo_y_porcentaje_de_la_venta(): void
    {
        $this->negocio->update([
            'salario_monto_fijo' => 5,
            'salario_porcentaje' => 10,
            'salario_base_porcentaje' => 'venta',
        ]);

        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        // Venta de 3 unidades a 10 c/u = 30. Salario = 5 + 10% de 30 = 8.
        $this->actingAs($cajero)->post(route('ventas.store', $turno), [
            'metodo_pago' => 'efectivo',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 3]],
        ]);

        $this->actingAs($cajero)->patch(route('turnos.cerrar', $turno), [
            'conteo' => ['locales' => [], 'usd' => 0, 'eur' => 0],
        ]);

        $this->assertEquals(8.0, (float) $turno->fresh()->salario);
    }

    public function test_el_conteo_de_efectivo_convierte_usd_y_eur_con_la_tasa_del_turno(): void
    {
        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero); // tasa_usd=400, tasa_eur=430

        $this->actingAs($cajero)->patch(route('turnos.cerrar', $turno), [
            'conteo' => [
                'locales' => [100 => 2, 50 => 1], // 200 + 50 = 250
                'usd' => 3, // 3 * 400 = 1200
                'eur' => 1, // 1 * 430 = 430
            ],
        ]);

        $this->assertEquals(1880.0, (float) $turno->fresh()->total_contado);
    }

    public function test_un_cajero_no_puede_cerrar_el_turno_de_otro(): void
    {
        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        $otro = User::factory()->create();
        $this->darAcceso($otro, 'dependiente');

        $this->actingAs($otro)->patch(route('turnos.cerrar', $turno), [])->assertForbidden();
    }

    public function test_un_administrador_puede_cerrar_el_turno_de_un_dependiente(): void
    {
        $cajero = User::factory()->create();
        $turno = $this->abrirTurno($cajero);

        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');

        $this->actingAs($admin)->patch(route('turnos.cerrar', $turno), [])->assertRedirect();

        $this->assertSame('cerrado', $turno->fresh()->estado);
    }
}
