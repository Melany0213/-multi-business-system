<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Account;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NegocioSalarioTest extends TestCase
{
    use RefreshDatabase;

    private Account $cuenta;

    private Business $negocio;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->dueno = User::factory()->create();
        $this->cuenta = Account::create(['owner_user_id' => $this->dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $this->negocio = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);
    }

    public function test_el_dueno_puede_configurar_el_salario_combinando_fijo_y_porcentaje(): void
    {
        $this->actingAs($this->dueno)
            ->patch(route('negocios.salario.update', $this->negocio), [
                'salario_monto_fijo' => 5,
                'salario_porcentaje' => 3,
                'salario_base_porcentaje' => 'venta',
            ])
            ->assertRedirect(route('negocios.salario.edit', $this->negocio));

        $this->negocio->refresh();
        $this->assertEquals(5.0, (float) $this->negocio->salario_monto_fijo);
        $this->assertEquals(3.0, (float) $this->negocio->salario_porcentaje);
        $this->assertSame('venta', $this->negocio->salario_base_porcentaje);
    }

    public function test_un_administrador_sin_delegacion_no_puede_ver_ni_editar_el_salario(): void
    {
        $admin = User::factory()->create();
        Access::create([
            'user_id' => $admin->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('administrador')->id,
            'tipo_vigencia' => 'fija',
        ]);

        $this->actingAs($admin)->get(route('negocios.salario.edit', $this->negocio))->assertForbidden();
        $this->actingAs($admin)->patch(route('negocios.salario.update', $this->negocio), [
            'salario_monto_fijo' => 1,
            'salario_porcentaje' => 0,
            'salario_base_porcentaje' => 'venta',
        ])->assertForbidden();
    }

    public function test_el_dueno_puede_delegar_el_ajuste_al_administrador(): void
    {
        $admin = User::factory()->create();
        Access::create([
            'user_id' => $admin->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('administrador')->id,
            'tipo_vigencia' => 'fija',
        ]);

        $this->negocio->update(['admin_puede_configurar_salario' => true]);

        $this->actingAs($admin)
            ->patch(route('negocios.salario.update', $this->negocio), [
                'salario_monto_fijo' => 2,
                'salario_porcentaje' => 5,
                'salario_base_porcentaje' => 'utilidad',
            ])
            ->assertRedirect();

        $this->negocio->refresh();
        $this->assertEquals(2.0, (float) $this->negocio->salario_monto_fijo);
        $this->assertEquals(5.0, (float) $this->negocio->salario_porcentaje);
    }

    public function test_un_administrador_delegado_no_puede_ampliarse_el_permiso_a_si_mismo(): void
    {
        $admin = User::factory()->create();
        Access::create([
            'user_id' => $admin->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('administrador')->id,
            'tipo_vigencia' => 'fija',
        ]);

        $this->negocio->update(['admin_puede_configurar_salario' => true]);

        // Intenta mandar admin_puede_configurar_salario=false para revocarse
        // a sí mismo (o dejarlo en true "explícitamente") — en ningún caso
        // debería poder tocar ese campo, solo el dueño.
        $this->actingAs($admin)->patch(route('negocios.salario.update', $this->negocio), [
            'salario_monto_fijo' => 0,
            'salario_porcentaje' => 0,
            'salario_base_porcentaje' => 'venta',
            'admin_puede_configurar_salario' => false,
        ]);

        $this->assertTrue($this->negocio->fresh()->admin_puede_configurar_salario);
    }

    public function test_una_dependienta_no_puede_ver_la_configuracion_de_salario(): void
    {
        $dependienta = User::factory()->create();
        Access::create([
            'user_id' => $dependienta->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('dependiente')->id,
            'tipo_vigencia' => 'fija',
        ]);

        $this->negocio->update(['admin_puede_configurar_salario' => true]);

        $this->actingAs($dependienta)->get(route('negocios.salario.edit', $this->negocio))->assertForbidden();
    }
}
