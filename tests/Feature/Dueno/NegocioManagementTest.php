<?php

namespace Tests\Feature\Dueno;

use App\Models\Account;
use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegocioManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_un_usuario_que_no_es_dueno_no_puede_ver_negocios(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('negocios.index'))->assertForbidden();
    }

    public function test_el_dueno_ve_sus_negocios(): void
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio 1', 'tipo' => 'productos']);

        $this->actingAs($dueno)->get(route('negocios.index'))->assertOk();
    }

    public function test_el_dueno_puede_crear_un_negocio_y_recibe_un_almacen_principal(): void
    {
        $dueno = User::factory()->create();
        Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);

        $this->actingAs($dueno)->post(route('negocios.store'), [
            'nombre' => 'Mi Negocio',
            'tipo' => 'productos',
            'moneda' => 'USD',
        ])->assertRedirect(route('negocios.index'));

        $this->assertDatabaseHas('businesses', ['nombre' => 'Mi Negocio']);

        $negocio = Business::where('nombre', 'Mi Negocio')->first();
        $this->assertSame(1, $negocio->almacenes()->count());
    }

    public function test_el_dueno_ve_automaticamente_el_negocio_que_acaba_de_crear(): void
    {
        $dueno = User::factory()->create();
        Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);

        $this->actingAs($dueno)->post(route('negocios.store'), [
            'nombre' => 'Mi Negocio',
            'tipo' => 'productos',
            'moneda' => 'USD',
        ]);

        $negocio = Business::where('nombre', 'Mi Negocio')->first();
        $acceso = $dueno->accesses()->where('business_id', $negocio->id)->first();

        $this->assertNotNull($acceso);
    }

    public function test_no_se_puede_crear_un_negocio_si_se_alcanzo_el_limite_del_plan(): void
    {
        $dueno = User::factory()->create();
        $plan = Plan::create(['nombre' => 'Básico', 'max_negocios' => 1]);
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X', 'plan_id' => $plan->id]);
        Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio 1', 'tipo' => 'productos']);

        $this->actingAs($dueno)->post(route('negocios.store'), [
            'nombre' => 'Negocio 2',
            'tipo' => 'productos',
            'moneda' => 'USD',
        ])->assertSessionHasErrors('nombre');

        $this->assertDatabaseMissing('businesses', ['nombre' => 'Negocio 2']);
    }

    public function test_un_dueno_no_puede_editar_el_negocio_de_otro_dueno(): void
    {
        $duenoA = User::factory()->create();
        Account::create(['owner_user_id' => $duenoA->id, 'nombre_cliente' => 'Cuenta A']);

        $duenoB = User::factory()->create();
        $cuentaB = Account::create(['owner_user_id' => $duenoB->id, 'nombre_cliente' => 'Cuenta B']);
        $negocioB = Business::create(['account_id' => $cuentaB->id, 'nombre' => 'Negocio de B', 'tipo' => 'productos']);

        $this->actingAs($duenoA)->get(route('negocios.edit', $negocioB))->assertForbidden();
    }

    public function test_el_dueno_puede_editar_su_negocio(): void
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $negocio = Business::create(['account_id' => $cuenta->id, 'nombre' => 'Nombre Original', 'tipo' => 'productos']);

        $this->actingAs($dueno)->patch(route('negocios.update', $negocio), [
            'nombre' => 'Nombre Editado',
            'tipo' => 'servicios',
            'moneda' => 'USD',
        ])->assertRedirect(route('negocios.index'));

        $this->assertSame('Nombre Editado', $negocio->fresh()->nombre);
        $this->assertSame('servicios', $negocio->fresh()->tipo);
    }

    public function test_el_dueno_puede_activar_y_desactivar_su_negocio(): void
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $negocio = Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);

        $this->actingAs($dueno)->patch(route('negocios.toggle-estado', $negocio));
        $this->assertSame('inactivo', $negocio->fresh()->estado);

        $this->actingAs($dueno)->patch(route('negocios.toggle-estado', $negocio));
        $this->assertSame('activo', $negocio->fresh()->estado);
    }
}
