<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Account;
use App\Models\Business;
use App\Models\User;
use App\Services\AccessScheduler;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NegocioActivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function crearNegocioConAcceso(User $user, string $nombre): Business
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => "Cuenta de {$nombre}"]);
        $negocio = Business::create(['account_id' => $cuenta->id, 'nombre' => $nombre, 'tipo' => 'productos']);

        Access::create([
            'user_id' => $user->id,
            'business_id' => $negocio->id,
            'role_id' => Role::findByName('administrador')->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ]);

        return $negocio;
    }

    public function test_por_defecto_el_negocio_activo_es_el_primero_accesible(): void
    {
        $user = User::factory()->create();
        $negocio = $this->crearNegocioConAcceso($user, 'Negocio Uno');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page->where('negocioActivo.id', $negocio->id));
    }

    public function test_el_usuario_puede_cambiar_su_negocio_activo(): void
    {
        $user = User::factory()->create();
        $this->crearNegocioConAcceso($user, 'Negocio Uno');
        $negocioDos = $this->crearNegocioConAcceso($user, 'Negocio Dos');

        $this->actingAs($user)
            ->patch(route('negocio-activo.update', $negocioDos))
            ->assertRedirect();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertInertia(fn ($page) => $page->where('negocioActivo.id', $negocioDos->id));
    }

    public function test_el_primer_negocio_accesible_es_siempre_el_mismo_y_no_cambia_al_azar(): void
    {
        $user = User::factory()->create();
        $primero = $this->crearNegocioConAcceso($user, 'Negocio Uno');
        $this->crearNegocioConAcceso($user, 'Negocio Dos');
        $this->crearNegocioConAcceso($user, 'Negocio Tres');

        // Repetido varias veces: sin un ORDER BY estable en el query, esto
        // podía variar entre llamadas aunque los datos no cambiaran (bug
        // real detectado probando el flujo de traspasos a mano).
        $scheduler = app(AccessScheduler::class);
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($primero->id, $scheduler->businessesActiveFor($user)->first()->id);
        }
    }

    public function test_no_puede_cambiar_a_un_negocio_al_que_no_tiene_acceso(): void
    {
        $user = User::factory()->create();
        $this->crearNegocioConAcceso($user, 'Negocio Uno');

        $otroDueno = User::factory()->create();
        $otraCuenta = Account::create(['owner_user_id' => $otroDueno->id, 'nombre_cliente' => 'Otra Cuenta']);
        $negocioAjeno = Business::create(['account_id' => $otraCuenta->id, 'nombre' => 'Ajeno', 'tipo' => 'productos']);

        $this->actingAs($user)
            ->patch(route('negocio-activo.update', $negocioAjeno))
            ->assertForbidden();
    }
}
