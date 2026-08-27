<?php

namespace Tests\Feature\Inventario;

use App\Models\Access;
use App\Models\Account;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlmacenManagementTest extends TestCase
{
    use RefreshDatabase;

    private Business $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $this->negocio = Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);
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

    public function test_un_administrador_puede_crear_un_almacen(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');

        $this->actingAs($admin)->post(route('almacenes.store'), [
            'nombre' => 'Almacén Secundario',
        ])->assertRedirect(route('almacenes.index'));

        $this->assertDatabaseHas('almacenes', ['nombre' => 'Almacén Secundario', 'business_id' => $this->negocio->id]);
    }

    public function test_una_dependienta_no_puede_crear_un_almacen(): void
    {
        $dependienta = User::factory()->create();
        $this->darAcceso($dependienta, 'dependiente');

        $this->actingAs($dependienta)->post(route('almacenes.store'), [
            'nombre' => 'Almacén Secundario',
        ])->assertForbidden();
    }

    public function test_una_dependienta_si_puede_ver_los_almacenes(): void
    {
        $dependienta = User::factory()->create();
        $this->darAcceso($dependienta, 'dependiente');

        $this->actingAs($dependienta)->get(route('almacenes.index'))->assertOk();
    }

    public function test_un_administrador_puede_activar_y_desactivar_un_almacen(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');
        $almacen = Almacen::create(['business_id' => $this->negocio->id, 'nombre' => 'Almacén X']);

        $this->actingAs($admin)->patch(route('almacenes.toggle-estado', $almacen));
        $this->assertSame('inactivo', $almacen->fresh()->estado);
    }

    public function test_no_se_puede_editar_un_almacen_de_otro_negocio(): void
    {
        $admin = User::factory()->create();
        $this->darAcceso($admin, 'administrador');

        $otroDueno = User::factory()->create();
        $otraCuenta = Account::create(['owner_user_id' => $otroDueno->id, 'nombre_cliente' => 'Otra']);
        $otroNegocio = Business::create(['account_id' => $otraCuenta->id, 'nombre' => 'Otro Negocio', 'tipo' => 'productos']);
        $almacenAjeno = Almacen::create(['business_id' => $otroNegocio->id, 'nombre' => 'Ajeno']);

        $this->actingAs($admin)->get(route('almacenes.edit', $almacenAjeno))->assertForbidden();
    }
}
