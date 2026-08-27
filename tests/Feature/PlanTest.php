<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_crea_los_3_planes(): void
    {
        $this->seed(PlanSeeder::class);

        $this->assertDatabaseHas('planes', ['nombre' => 'Básico', 'max_negocios' => 1]);
        $this->assertDatabaseHas('planes', ['nombre' => 'Profesional', 'max_negocios' => 5]);
        $this->assertDatabaseHas('planes', ['nombre' => 'Empresa', 'max_negocios' => null]);
    }

    public function test_una_cuenta_sin_plan_no_tiene_limite_de_negocios(): void
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta sin plan']);

        $this->assertTrue($cuenta->puedeCrearNegocio());
    }

    public function test_una_cuenta_no_puede_superar_el_limite_de_negocios_de_su_plan(): void
    {
        $plan = Plan::create(['nombre' => 'Básico', 'max_negocios' => 1]);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta Básica', 'plan_id' => $plan->id]);

        $this->assertTrue($cuenta->puedeCrearNegocio());

        Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio 1', 'tipo' => 'productos']);

        $this->assertFalse($cuenta->fresh()->puedeCrearNegocio());
    }

    public function test_un_plan_con_limite_null_es_ilimitado(): void
    {
        $plan = Plan::create(['nombre' => 'Empresa', 'max_negocios' => null]);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta Empresa', 'plan_id' => $plan->id]);

        Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio 1', 'tipo' => 'productos']);
        Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio 2', 'tipo' => 'productos']);

        $this->assertTrue($cuenta->fresh()->puedeCrearNegocio());
    }
}
