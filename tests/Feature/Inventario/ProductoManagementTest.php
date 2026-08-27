<?php

namespace Tests\Feature\Inventario;

use App\Models\Access;
use App\Models\Account;
use App\Models\Business;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductoManagementTest extends TestCase
{
    use RefreshDatabase;

    private Account $cuenta;

    private Business $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $dueno = User::factory()->create();
        $this->cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $this->negocio = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);
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

    public function test_un_administrador_puede_crear_un_producto_nuevo_con_foto(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');

        $this->actingAs($admin)->post(route('productos.store'), [
            'nombre' => 'Refresco',
            'precio' => 1.5,
            'costo' => 0.8,
            'imagen' => UploadedFile::fake()->create('refresco.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(route('productos.index'));

        $producto = Producto::where('nombre', 'Refresco')->first();
        $this->assertNotNull($producto);
        $this->assertSame($this->cuenta->id, $producto->account_id);
        $this->assertNotNull($producto->imagen_path);
        Storage::disk('public')->assertExists($producto->imagen_path);

        $pivot = $this->negocio->productos()->first()->pivot;
        $this->assertEquals(1.5, $pivot->precio);
        $this->assertEquals(0.8, $pivot->costo);
    }

    public function test_un_administrador_puede_ofrecer_un_producto_existente_del_catalogo_con_su_propio_precio(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');

        $otroNegocio = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Otro Negocio', 'tipo' => 'productos']);
        $refresco = $this->cuenta->productos()->create(['nombre' => 'Refresco']);
        $otroNegocio->productos()->attach($refresco->id, ['precio' => 1.5, 'costo' => 0.8]);

        $this->actingAs($admin)->post(route('productos.store'), [
            'producto_id' => $refresco->id,
            'precio' => 2.25,
            'costo' => 0.8,
        ])->assertRedirect(route('productos.index'));

        $this->assertDatabaseCount('productos', 1);
        $this->assertEquals(2.25, $this->negocio->productos()->first()->pivot->precio);
    }

    public function test_una_dependienta_no_puede_crear_productos(): void
    {
        $dependienta = User::factory()->create();
        $this->darAcceso($dependienta, 'dependiente');

        $this->actingAs($dependienta)->post(route('productos.store'), [
            'nombre' => 'Refresco',
            'precio' => 1.5,
            'costo' => 0.8,
        ])->assertForbidden();
    }

    public function test_un_administrador_puede_actualizar_el_stock_de_un_producto_en_un_almacen(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');

        $producto = $this->cuenta->productos()->create(['nombre' => 'Refresco']);
        $this->negocio->productos()->attach($producto->id, ['precio' => 1.5, 'costo' => 0.8]);
        $almacen = $this->negocio->almacenes()->create(['nombre' => 'Almacén Central']);

        $this->actingAs($admin)->patch(route('productos.stock', [$producto, $almacen]), [
            'cantidad' => 25,
        ])->assertRedirect();

        $this->assertSame(25, $producto->almacenes()->first()->pivot->cantidad);
    }

    public function test_no_se_puede_actualizar_stock_de_un_producto_que_el_negocio_no_ofrece(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');
        $almacen = $this->negocio->almacenes()->create(['nombre' => 'Almacén Central']);

        $otroDueno = User::factory()->create();
        $otraCuenta = Account::create(['owner_user_id' => $otroDueno->id, 'nombre_cliente' => 'Otra']);
        $productoAjeno = $otraCuenta->productos()->create(['nombre' => 'Ajeno']);

        $this->actingAs($admin)->patch(route('productos.stock', [$productoAjeno, $almacen]), [
            'cantidad' => 25,
        ])->assertForbidden();
    }

    public function test_el_indice_muestra_el_margen_de_ganancia(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');
        $producto = $this->cuenta->productos()->create(['nombre' => 'Refresco']);
        $this->negocio->productos()->attach($producto->id, ['precio' => 1.5, 'costo' => 0.8]);

        $response = $this->actingAs($admin)->get(route('productos.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('productos.0.nombre', 'Refresco')
            ->where('productos.0.margen', 0.7)
        );
    }
}
