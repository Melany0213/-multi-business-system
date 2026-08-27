<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoAlmacenTest extends TestCase
{
    use RefreshDatabase;

    private Account $cuenta;

    private Business $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        $dueno = User::factory()->create();
        $this->cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta de prueba']);
        $this->negocio = Business::create([
            'account_id' => $this->cuenta->id,
            'nombre' => 'Negocio de prueba',
            'tipo' => 'productos',
        ]);
    }

    public function test_un_negocio_puede_tener_varios_almacenes(): void
    {
        Almacen::create(['business_id' => $this->negocio->id, 'nombre' => 'Almacén Central']);
        Almacen::create(['business_id' => $this->negocio->id, 'nombre' => 'Punto de venta 2']);

        $this->assertCount(2, $this->negocio->almacenes);
    }

    public function test_se_calcula_el_margen_de_ganancia_a_partir_de_precio_y_costo(): void
    {
        $this->assertSame(50.0, Producto::margenGanancia(150, 100));
        $this->assertSame(50.0, Producto::margenPorcentaje(150, 100));
    }

    public function test_el_stock_de_un_producto_se_lleva_por_almacen(): void
    {
        $producto = $this->cuenta->productos()->create(['nombre' => 'Refresco']);

        $almacenA = Almacen::create(['business_id' => $this->negocio->id, 'nombre' => 'Almacén A']);
        $almacenB = Almacen::create(['business_id' => $this->negocio->id, 'nombre' => 'Almacén B']);

        $almacenA->productos()->attach($producto->id, ['cantidad' => 40]);
        $almacenB->productos()->attach($producto->id, ['cantidad' => 5]);

        $this->assertSame(40, $almacenA->productos()->first()->pivot->cantidad);
        $this->assertSame(5, $almacenB->productos()->first()->pivot->cantidad);
    }

    public function test_el_mismo_producto_del_catalogo_puede_tener_precios_distintos_en_negocios_distintos(): void
    {
        $otroNegocio = Business::create([
            'account_id' => $this->cuenta->id,
            'nombre' => 'Otro negocio',
            'tipo' => 'productos',
        ]);

        $refresco = $this->cuenta->productos()->create(['nombre' => 'Refresco']);

        $this->negocio->productos()->attach($refresco->id, ['precio' => 150, 'costo' => 100]);
        $otroNegocio->productos()->attach($refresco->id, ['precio' => 200, 'costo' => 100]);

        $this->assertEquals(150, $this->negocio->productos()->first()->pivot->precio);
        $this->assertEquals(200, $otroNegocio->productos()->first()->pivot->precio);

        // Es el mismo registro de catálogo en ambos negocios.
        $this->assertSame($refresco->id, $this->negocio->productos()->first()->id);
        $this->assertSame($refresco->id, $otroNegocio->productos()->first()->id);
    }
}
