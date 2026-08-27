<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Business;
use App\Models\User;
use App\Services\AccessScheduler;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessOwnerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_el_dueno_ve_automaticamente_los_negocios_que_crea(): void
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);

        $negocio = Business::create([
            'account_id' => $cuenta->id,
            'nombre' => 'Negocio del dueño',
            'tipo' => 'productos',
        ]);

        $negocios = app(AccessScheduler::class)->businessesActiveFor($dueno);

        $this->assertTrue($negocios->contains('id', $negocio->id));
    }

    public function test_el_acceso_automatico_del_dueno_tiene_el_rol_de_mayor_privilegio(): void
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta Y']);

        $negocio = Business::create([
            'account_id' => $cuenta->id,
            'nombre' => 'Otro negocio del dueño',
            'tipo' => 'productos',
        ]);

        $acceso = $dueno->accesses()->where('business_id', $negocio->id)->first();

        $this->assertNotNull($acceso);
        $this->assertSame('super_admin_negocio', $acceso->role->name);
    }
}
