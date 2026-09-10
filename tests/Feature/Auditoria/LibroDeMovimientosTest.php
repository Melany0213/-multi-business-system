<?php

namespace Tests\Feature\Auditoria;

use App\Exceptions\BitacoraInmutableException;
use App\Models\Access;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El libro de movimientos: que registre TODO lo que pasa en el negocio
 * (RF-48 y RF-49) y que no se pueda tocar (RF-50).
 */
class LibroDeMovimientosTest extends TestCase
{
    use RefreshDatabase;

    private Account $cuenta;

    private Business $negocio;

    private Almacen $almacen;

    private Producto $producto;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->dueno = User::factory()->create(['username' => 'carlos']);
        $this->cuenta = Account::create(['owner_user_id' => $this->dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $this->negocio = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Cafetería', 'tipo' => 'productos']);
        $this->almacen = $this->negocio->almacenes()->create(['nombre' => 'Depósito']);

        $this->producto = $this->cuenta->productos()->create(['nombre' => 'Café']);
        $this->negocio->productos()->attach($this->producto->id, ['precio' => 10, 'costo' => 6]);
        $this->almacen->productos()->attach($this->producto->id, ['cantidad' => 20]);

        Access::create([
            'user_id' => $this->dueno->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('super_admin_negocio')->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ]);
    }

    private function ultimoEvento(string $evento): ?ActivityLog
    {
        return ActivityLog::where('evento', $evento)->latest('id')->first();
    }

    // ---------------------------------------------------------------- RF-48

    public function test_los_movimientos_se_guardan_con_nombre_propio_y_no_como_actualizado(): void
    {
        $this->actingAs($this->dueno)->post(route('turnos.store'), [
            'almacen_id' => $this->almacen->id,
            'dispositivo' => 'Caja 1',
            'tasa_usd' => 400,
            'tasa_eur' => 430,
        ])->assertRedirect();

        $movimiento = $this->ultimoEvento('turno.abierto');

        $this->assertNotNull($movimiento, 'Abrir un turno debe quedar como "turno.abierto"');
        $this->assertStringContainsString('Depósito', $movimiento->description);
        $this->assertStringContainsString('Caja 1', $movimiento->description);
        $this->assertSame($this->negocio->id, $movimiento->business_id);
    }

    public function test_el_cierre_de_turno_registra_el_descuadre_en_el_propio_texto(): void
    {
        $this->actingAs($this->dueno)->post(route('turnos.store'), [
            'almacen_id' => $this->almacen->id,
            'dispositivo' => 'Caja 1',
            'tasa_usd' => 400,
            'tasa_eur' => 430,
        ]);

        $turno = $this->negocio->turnos()->firstOrFail();

        $this->actingAs($this->dueno)->patch(route('turnos.cerrar', $turno), [
            'conteo' => ['locales' => ['100' => 1], 'usd' => 0, 'eur' => 0],
        ])->assertRedirect();

        $movimiento = $this->ultimoEvento('turno.cerrado');

        $this->assertNotNull($movimiento);
        // No hubo ventas en efectivo, así que los 100 contados sobran.
        $this->assertStringContainsString('sobran', $movimiento->description);
    }

    // ---------------------------------------------------------------- RF-49

    public function test_el_ajuste_manual_de_stock_deja_rastro_con_el_valor_anterior(): void
    {
        $this->actingAs($this->dueno)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 5])
            ->assertRedirect();

        $movimiento = $this->ultimoEvento('stock.ajustado');

        $this->assertNotNull($movimiento, 'El ajuste manual de stock viaja por una tabla pivote y debe registrarse a mano');
        $this->assertSame(20, (int) $movimiento->changes['cantidad']['antes']);
        $this->assertSame(5, (int) $movimiento->changes['cantidad']['despues']);
        $this->assertSame($this->negocio->id, $movimiento->business_id);
    }

    public function test_el_cambio_de_precio_y_costo_deja_rastro(): void
    {
        $this->actingAs($this->dueno)
            ->patch(route('productos.update', $this->producto), [
                'nombre' => 'Café',
                'precio' => 12,
                'costo' => 9,
            ])->assertRedirect();

        $movimiento = $this->ultimoEvento('precio.cambiado');

        $this->assertNotNull($movimiento, 'Tocar el costo cambia la utilidad y el salario del cajero: tiene que quedar registrado');
        $this->assertEquals(6, $movimiento->changes['costo']['antes']);
        $this->assertEquals(9, $movimiento->changes['costo']['despues']);
    }

    public function test_guardar_sin_cambiar_nada_no_ensucia_el_libro(): void
    {
        $antes = ActivityLog::where('evento', 'precio.cambiado')->count();

        $this->actingAs($this->dueno)
            ->patch(route('productos.update', $this->producto), [
                'nombre' => 'Café',
                'precio' => 10,
                'costo' => 6,
            ])->assertRedirect();

        $this->assertSame($antes, ActivityLog::where('evento', 'precio.cambiado')->count());
    }

    public function test_los_inicios_y_cierres_de_sesion_quedan_registrados(): void
    {
        $this->post(route('login'), ['username' => 'carlos', 'password' => 'password'])->assertRedirect();

        $inicio = $this->ultimoEvento('sesion.iniciada');
        $this->assertNotNull($inicio, 'Sin esto no se puede saber quién estaba dentro cuando pasó algo');
        $this->assertSame($this->dueno->id, $inicio->causer_id);

        $this->post(route('logout'));
        $this->assertNotNull($this->ultimoEvento('sesion.cerrada'));
    }

    public function test_los_intentos_fallidos_quedan_registrados_sin_guardar_la_contrasena(): void
    {
        $this->post(route('login'), ['username' => 'carlos', 'password' => 'clave-incorrecta']);

        $fallido = $this->ultimoEvento('sesion.fallida');

        $this->assertNotNull($fallido);
        $this->assertStringContainsString('carlos', $fallido->description);
        $this->assertStringNotContainsString('clave-incorrecta', $fallido->description);
        $this->assertStringNotContainsString('clave-incorrecta', json_encode($fallido->changes ?? []));
    }

    // ---------------------------------------------------------------- RF-50

    public function test_una_entrada_del_libro_no_se_puede_modificar(): void
    {
        $movimiento = ActivityLog::latest('id')->firstOrFail();

        $this->expectException(BitacoraInmutableException::class);

        $movimiento->update(['description' => 'otra cosa']);
    }

    public function test_una_entrada_del_libro_no_se_puede_eliminar(): void
    {
        $movimiento = ActivityLog::latest('id')->firstOrFail();

        $this->expectException(BitacoraInmutableException::class);

        $movimiento->delete();
    }

    public function test_cada_entrada_encadena_el_hash_de_la_anterior(): void
    {
        $this->actingAs($this->dueno)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 7]);

        $movimientos = ActivityLog::orderBy('id')->get();

        $this->assertGreaterThan(1, $movimientos->count());

        $anterior = null;

        foreach ($movimientos as $movimiento) {
            $this->assertSame($anterior, $movimiento->hash_previo, "El movimiento #{$movimiento->id} no engancha con el anterior");
            $this->assertSame($movimiento->calcularHash(), $movimiento->hash);
            $anterior = $movimiento->hash;
        }
    }

    public function test_el_verificador_detecta_una_entrada_alterada_por_fuera_del_sistema(): void
    {
        $this->actingAs($this->dueno)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 7]);

        $this->artisan('bitacora:verificar')->assertExitCode(0);

        // Alguien con acceso directo a la base cambia una fila, esquivando por
        // completo al modelo y su bloqueo de escritura.
        $movimiento = ActivityLog::orderBy('id')->skip(1)->firstOrFail();
        DB::table('activity_logs')->where('id', $movimiento->id)->update(['description' => 'nunca pasó esto']);

        $this->artisan('bitacora:verificar')->assertExitCode(1);
    }
}
