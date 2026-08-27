<?php

namespace Tests\Feature\Admin;

use App\Models\Access;
use App\Models\Account;
use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function crearPlan(string $nombre = 'Básico'): Plan
    {
        return Plan::create(['nombre' => $nombre, 'max_negocios' => 1, 'max_usuarios' => 3]);
    }

    public function test_un_usuario_normal_no_puede_ver_el_panel_de_cuentas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.cuentas.index'))->assertForbidden();
    }

    public function test_el_super_admin_del_sistema_puede_ver_el_panel_de_cuentas(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);

        $this->actingAs($superAdmin)->get(route('admin.cuentas.index'))->assertOk();
    }

    public function test_el_super_admin_puede_crear_una_cuenta_con_su_dueno(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);
        $plan = $this->crearPlan();

        $this->actingAs($superAdmin)->post(route('admin.cuentas.store'), [
            'nombre_cliente' => 'Negocios Demo S.A.',
            'plan_id' => $plan->id,
            'dueno_nombre' => 'Nuevo',
            'dueno_primer_apellido' => 'Dueño',
            'dueno_segundo_apellido' => 'Demo',
            'dueno_email' => 'nuevo-dueno@example.com',
            'dueno_password' => 'password123',
        ])->assertRedirect(route('admin.cuentas.index'));

        $this->assertDatabaseHas('accounts', ['nombre_cliente' => 'Negocios Demo S.A.']);
        $this->assertDatabaseHas('users', ['email' => 'nuevo-dueno@example.com', 'username' => 'ddnuevo']);
    }

    public function test_suspender_una_cuenta_suspende_al_dueno_y_a_sus_dependientes_pero_no_a_los_bloqueados(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);

        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $negocio = Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);

        $dependiente = User::factory()->create();
        Access::create([
            'user_id' => $dependiente->id,
            'business_id' => $negocio->id,
            'role_id' => Role::findByName('dependiente')->id,
            'tipo_vigencia' => 'fija',
        ]);

        $bloqueado = User::factory()->create(['estado_global' => 'bloqueado']);
        Access::create([
            'user_id' => $bloqueado->id,
            'business_id' => $negocio->id,
            'role_id' => Role::findByName('dependiente')->id,
            'tipo_vigencia' => 'fija',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.cuentas.suspend', $cuenta))
            ->assertRedirect();

        $this->assertSame('suspendida', $cuenta->fresh()->estado);
        $this->assertSame('suspendido', $dueno->fresh()->estado_global);
        $this->assertSame('suspendido', $dependiente->fresh()->estado_global);
        $this->assertSame('bloqueado', $bloqueado->fresh()->estado_global);
    }

    public function test_reactivar_una_cuenta_solo_restaura_a_los_que_quedaron_suspendidos(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);

        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta Y']);
        $cuenta->suspend();

        $this->actingAs($superAdmin)
            ->patch(route('admin.cuentas.reactivate', $cuenta))
            ->assertRedirect();

        $this->assertSame('activa', $cuenta->fresh()->estado);
        $this->assertSame('activo', $dueno->fresh()->estado_global);
    }

    public function test_el_super_admin_puede_ver_el_detalle_de_una_cuenta(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta Z']);

        $this->actingAs($superAdmin)->get(route('admin.cuentas.show', $cuenta))->assertOk();
    }

    public function test_el_super_admin_puede_editar_una_cuenta(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Nombre Original']);
        $plan = $this->crearPlan('Premium');

        $this->actingAs($superAdmin)
            ->patch(route('admin.cuentas.update', $cuenta), [
                'nombre_cliente' => 'Nombre Editado',
                'rut' => '11.111.111-1',
                'telefono' => '+56 9 1111 1111',
                'plan_id' => $plan->id,
            ])
            ->assertRedirect(route('admin.cuentas.show', $cuenta));

        $this->assertSame('Nombre Editado', $cuenta->fresh()->nombre_cliente);
        $this->assertSame('11.111.111-1', $cuenta->fresh()->rut);
        $this->assertSame('+56 9 1111 1111', $cuenta->fresh()->telefono);
        $this->assertSame($plan->id, $cuenta->fresh()->plan_id);
    }

    public function test_el_super_admin_puede_eliminar_una_cuenta_y_sus_negocios_en_cascada(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta a Borrar']);
        $negocio = Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio a Borrar', 'tipo' => 'productos']);

        $this->actingAs($superAdmin)
            ->delete(route('admin.cuentas.destroy', $cuenta))
            ->assertRedirect(route('admin.cuentas.index'));

        $this->assertDatabaseMissing('accounts', ['id' => $cuenta->id]);
        $this->assertDatabaseMissing('businesses', ['id' => $negocio->id]);
        // El dueño, como usuario, no se borra al eliminar la cuenta.
        $this->assertDatabaseHas('users', ['id' => $dueno->id]);
    }
}
