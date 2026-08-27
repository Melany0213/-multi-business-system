<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Account;
use App\Models\Business;
use App\Models\User;
use App\Services\AccessScheduler;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private AccessScheduler $scheduler;

    private Business $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->scheduler = app(AccessScheduler::class);

        $dueno = User::factory()->create();
        $cuenta = Account::create([
            'owner_user_id' => $dueno->id,
            'nombre_cliente' => 'Cuenta de prueba',
            'estado' => 'activa',
        ]);
        $this->negocio = Business::create([
            'account_id' => $cuenta->id,
            'nombre' => 'Negocio de prueba',
            'tipo' => 'productos',
            'estado' => 'activo',
        ]);
    }

    private function darAcceso(User $user, string $rol, array $overrides = []): Access
    {
        return Access::create(array_merge([
            'user_id' => $user->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName($rol)->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ], $overrides));
    }

    public function test_acceso_fijo_es_vigente_en_cualquier_momento(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'administrador');

        $negocios = $this->scheduler->businessesActiveFor($user, Carbon::parse('2026-08-26 03:00:00'));

        $this->assertTrue($negocios->contains('id', $this->negocio->id));
    }

    public function test_acceso_semanal_solo_es_vigente_los_dias_configurados(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'dependiente', [
            'tipo_vigencia' => 'semanal',
            'dias_semana' => [1, 2], // lunes y martes
        ]);

        $lunes = Carbon::parse('2026-08-24 10:00:00');
        $miercoles = Carbon::parse('2026-08-26 10:00:00');

        $this->assertTrue($this->scheduler->businessesActiveFor($user, $lunes)->contains('id', $this->negocio->id));
        $this->assertFalse($this->scheduler->businessesActiveFor($user, $miercoles)->contains('id', $this->negocio->id));
    }

    public function test_acceso_semanal_respeta_la_ventana_horaria(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'dependiente', [
            'tipo_vigencia' => 'semanal',
            'dias_semana' => [1],
            'hora_inicio' => '08:00:00',
            'hora_fin' => '14:00:00',
        ]);

        $dentroDeHorario = Carbon::parse('2026-08-24 09:00:00');
        $fueraDeHorario = Carbon::parse('2026-08-24 20:00:00');

        $this->assertTrue($this->scheduler->businessesActiveFor($user, $dentroDeHorario)->contains('id', $this->negocio->id));
        $this->assertFalse($this->scheduler->businessesActiveFor($user, $fueraDeHorario)->contains('id', $this->negocio->id));
    }

    public function test_acceso_revocado_no_es_vigente(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'dependiente', ['estado' => 'revocado']);

        $negocios = $this->scheduler->businessesActiveFor($user, Carbon::now());

        $this->assertFalse($negocios->contains('id', $this->negocio->id));
    }

    public function test_negocio_inactivo_no_es_visible_aunque_el_acceso_este_activo(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'dependiente');
        $this->negocio->update(['estado' => 'inactivo']);

        $negocios = $this->scheduler->businessesActiveFor($user, Carbon::now());

        $this->assertFalse($negocios->contains('id', $this->negocio->id));
    }

    public function test_cuenta_suspendida_bloquea_el_acceso_a_todos_sus_negocios(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'administrador');
        $this->negocio->account->update(['estado' => 'suspendida']);

        $negocios = $this->scheduler->businessesActiveFor($user, Carbon::now());

        $this->assertFalse($negocios->contains('id', $this->negocio->id));
    }

    public function test_usuario_globalmente_suspendido_no_ve_ningun_negocio(): void
    {
        $user = User::factory()->create(['estado_global' => 'suspendido']);
        $this->darAcceso($user, 'administrador');

        $negocios = $this->scheduler->businessesActiveFor($user, Carbon::now());

        $this->assertTrue($negocios->isEmpty());
    }

    public function test_dependiente_no_tiene_permiso_para_autorizar_traslados(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'dependiente');

        $this->assertFalse($this->scheduler->hasPermission($user, $this->negocio, 'inventario.autorizar_traslado'));
        $this->assertTrue($this->scheduler->hasPermission($user, $this->negocio, 'inventario.trasladar'));
    }

    public function test_administrador_si_tiene_permiso_para_autorizar_traslados(): void
    {
        $user = User::factory()->create();
        $this->darAcceso($user, 'administrador');

        $this->assertTrue($this->scheduler->hasPermission($user, $this->negocio, 'inventario.autorizar_traslado'));
    }
}
