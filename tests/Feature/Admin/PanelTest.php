<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_un_usuario_normal_no_puede_ver_el_panel_general(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.panel'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.negocios.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.actividad.index'))->assertForbidden();
    }

    public function test_el_super_admin_ve_el_panel_general_con_metricas_agregadas(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);
        $plan = Plan::create(['nombre' => 'Básico', 'precio_mensual' => 10]);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta X', 'plan_id' => $plan->id]);
        Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio X', 'tipo' => 'productos']);

        $response = $this->actingAs($superAdmin)->get(route('admin.panel'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Panel/Index')
            ->where('metricas.cuentas_activas', 1)
            ->where('metricas.total_negocios', 1)
            ->where('metricas.ingreso_mensual_estimado', 10)
        );
    }

    public function test_el_super_admin_ve_todos_los_negocios_de_todas_las_cuentas(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);
        $dueno = User::factory()->create();
        $cuenta = Account::create(['owner_user_id' => $dueno->id, 'nombre_cliente' => 'Cuenta Y']);
        Business::create(['account_id' => $cuenta->id, 'nombre' => 'Negocio Y', 'tipo' => 'productos']);

        $this->actingAs($superAdmin)
            ->get(route('admin.negocios.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Negocios/Index')
                ->has('negocios', 1)
            );
    }

    public function test_las_acciones_de_cuentas_quedan_registradas_en_la_bitacora_de_actividad(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);

        $this->actingAs($superAdmin)->post(route('admin.cuentas.store'), [
            'nombre_cliente' => 'Cuenta con traza',
            'dueno_nombre' => 'Nuevo',
            'dueno_primer_apellido' => 'Dueño',
            'dueno_segundo_apellido' => 'Demo',
            'dueno_email' => 'traza@example.com',
            'dueno_password' => 'password123',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'causer_id' => $superAdmin->id,
            'action' => 'created',
            'subject_type' => Account::class,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.actividad.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Actividad/Index'));
    }
}
