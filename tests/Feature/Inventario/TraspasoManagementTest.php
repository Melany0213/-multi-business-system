<?php

namespace Tests\Feature\Inventario;

use App\Models\Access;
use App\Models\Account;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Producto;
use App\Models\Traspaso;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraspasoManagementTest extends TestCase
{
    use RefreshDatabase;

    private Account $cuenta;

    private Business $negocioA;

    private Business $negocioB;

    private Almacen $almacenA;

    private Almacen $almacenB;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $dueno = User::factory()->create();
        $this->cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);

        $this->negocioA = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Negocio A', 'tipo' => 'productos']);
        $this->negocioB = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Negocio B', 'tipo' => 'productos']);

        $this->almacenA = $this->negocioA->almacenes()->create(['nombre' => 'Almacén A']);
        $this->almacenB = $this->negocioB->almacenes()->create(['nombre' => 'Almacén B']);

        $this->producto = $this->cuenta->productos()->create(['nombre' => 'Refresco']);
        $this->negocioA->productos()->attach($this->producto->id, ['precio' => 1.5, 'costo' => 0.8]);
        $this->negocioB->productos()->attach($this->producto->id, ['precio' => 2, 'costo' => 0.8]);
    }

    private function darAcceso(User $user, Business $negocio, string $rol): void
    {
        Access::create([
            'user_id' => $user->id,
            'business_id' => $negocio->id,
            'role_id' => Role::findByName($rol)->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ]);
    }

    private function conStock(int $cantidad): void
    {
        $this->almacenA->productos()->attach($this->producto->id, ['cantidad' => $cantidad]);
    }

    public function test_una_dependienta_puede_solicitar_un_traspaso_hacia_otro_negocio(): void
    {
        $this->conStock(20);
        $dependienta = User::factory()->create();
        $this->darAcceso($dependienta, $this->negocioA, 'dependiente');

        $this->actingAs($dependienta)->post(route('traspasos.store'), [
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
        ])->assertRedirect(route('traspasos.index'));

        $this->assertDatabaseHas('traspasos', [
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'estado' => 'solicitado',
            'solicitado_por' => $dependienta->id,
        ]);
    }

    public function test_el_administrador_del_negocio_origen_autoriza_y_se_descuenta_el_stock(): void
    {
        $this->conStock(20);
        $admin = User::factory()->create();
        $this->darAcceso($admin, $this->negocioA, 'administrador');

        $traspaso = Traspaso::create([
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'solicitado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(route('traspasos.autorizar', $traspaso))->assertRedirect();

        $this->assertSame('autorizado', $traspaso->fresh()->estado);
        $this->assertNotNull($traspaso->fresh()->autorizado_por);
        $this->assertSame(10, $this->almacenA->productos()->first()->pivot->cantidad);
    }

    public function test_no_se_puede_autorizar_sin_stock_suficiente(): void
    {
        $this->conStock(5);
        $admin = User::factory()->create();
        $this->darAcceso($admin, $this->negocioA, 'administrador');

        $traspaso = Traspaso::create([
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'solicitado_por' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('traspasos.autorizar', $traspaso))
            ->assertSessionHasErrors();

        $this->assertSame('solicitado', $traspaso->fresh()->estado);
        $this->assertSame(5, $this->almacenA->productos()->first()->pivot->cantidad);
    }

    public function test_una_dependienta_no_puede_autorizar_solo_el_administrador(): void
    {
        $this->conStock(20);
        $dependienta = User::factory()->create();
        $this->darAcceso($dependienta, $this->negocioA, 'dependiente');

        $traspaso = Traspaso::create([
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'solicitado_por' => $dependienta->id,
        ]);

        $this->actingAs($dependienta)->patch(route('traspasos.autorizar', $traspaso))->assertForbidden();
    }

    public function test_el_administrador_del_negocio_destino_confirma_y_se_suma_el_stock(): void
    {
        $this->conStock(20);
        $adminOrigen = User::factory()->create();
        $this->darAcceso($adminOrigen, $this->negocioA, 'administrador');
        $adminDestino = User::factory()->create();
        $this->darAcceso($adminDestino, $this->negocioB, 'administrador');

        $traspaso = Traspaso::create([
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'solicitado_por' => $adminOrigen->id,
        ]);
        $traspaso->autorizar($adminOrigen);

        $this->actingAs($adminDestino)->patch(route('traspasos.confirmar', $traspaso))->assertRedirect();

        $this->assertSame('completado', $traspaso->fresh()->estado);
        $this->assertSame(10, $this->almacenB->productos()->first()->pivot->cantidad);
    }

    public function test_no_se_puede_confirmar_un_traspaso_que_aun_no_fue_autorizado(): void
    {
        $adminDestino = User::factory()->create();
        $this->darAcceso($adminDestino, $this->negocioB, 'administrador');

        $traspaso = Traspaso::create([
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'solicitado_por' => $adminDestino->id,
        ]);

        $this->actingAs($adminDestino)
            ->patch(route('traspasos.confirmar', $traspaso))
            ->assertSessionHasErrors();

        $this->assertSame('solicitado', $traspaso->fresh()->estado);
    }

    public function test_el_administrador_del_negocio_origen_puede_rechazar_la_solicitud(): void
    {
        $this->conStock(20);
        $admin = User::factory()->create();
        $this->darAcceso($admin, $this->negocioA, 'administrador');

        $traspaso = Traspaso::create([
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'solicitado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(route('traspasos.rechazar', $traspaso))->assertRedirect();

        $this->assertSame('rechazado', $traspaso->fresh()->estado);
        // No se toca el stock al rechazar (nunca se autorizó la salida).
        $this->assertSame(20, $this->almacenA->productos()->first()->pivot->cantidad);
    }

    public function test_un_administrador_ajeno_al_negocio_origen_no_puede_autorizar(): void
    {
        $this->conStock(20);
        $adminAjeno = User::factory()->create();
        $this->darAcceso($adminAjeno, $this->negocioB, 'administrador');

        $traspaso = Traspaso::create([
            'producto_id' => $this->producto->id,
            'almacen_origen_id' => $this->almacenA->id,
            'almacen_destino_id' => $this->almacenB->id,
            'cantidad' => 10,
            'solicitado_por' => $adminAjeno->id,
        ]);

        $this->actingAs($adminAjeno)->patch(route('traspasos.autorizar', $traspaso))->assertForbidden();
    }
}
