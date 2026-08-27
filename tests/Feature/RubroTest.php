<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Business;
use App\Models\Rubro;
use App\Models\User;
use Database\Seeders\RubroSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubroTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_crea_el_catalogo_de_rubros(): void
    {
        $this->seed(RubroSeeder::class);

        $this->assertDatabaseHas('rubros', ['nombre' => 'Cafetería']);
        $this->assertDatabaseHas('rubros', ['nombre' => 'Tienda de mascotas']);
        $this->assertDatabaseHas('rubros', ['nombre' => 'Otro']);
    }

    public function test_un_negocio_puede_tener_un_rubro_asignado(): void
    {
        $rubro = Rubro::create(['nombre' => 'Veterinaria']);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);

        $negocio = Business::create([
            'account_id' => $cuenta->id,
            'rubro_id' => $rubro->id,
            'nombre' => 'Veterinaria Feliz',
            'tipo' => 'servicios',
        ]);

        $this->assertSame('Veterinaria', $negocio->rubro->nombre);
    }

    public function test_un_negocio_puede_no_tener_rubro(): void
    {
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta Y']);

        $negocio = Business::create([
            'account_id' => $cuenta->id,
            'nombre' => 'Negocio sin rubro',
            'tipo' => 'productos',
        ]);

        $this->assertNull($negocio->rubro);
    }
}
