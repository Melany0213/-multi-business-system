<?php

namespace Tests\Feature\Admin;

use App\Models\Access;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Intervencion;
use App\Models\Producto;
use App\Models\SolicitudSoporte;
use App\Models\User;
use App\Services\AccessScheduler;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La diferencia entre MIRAR y TOCAR: expedientes de solo lectura (RF-51,
 * RF-52), intervenciones con ceremonia (RF-54 a RF-57) y el ciclo completo
 * de la solicitud del dueño (RF-58 a RF-61).
 */
class SoporteYExpedientesTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $dueno;

    private Account $cuenta;

    private Business $negocio;

    private Almacen $almacen;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->superAdmin = User::factory()->create(['is_super_admin_sistema' => true]);
        $this->dueno = User::factory()->create();

        $this->cuenta = Account::create(['owner_user_id' => $this->dueno->id, 'nombre_cliente' => 'Cuenta X']);
        $this->negocio = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'Cafetería', 'tipo' => 'productos']);
        $this->almacen = $this->negocio->almacenes()->create(['nombre' => 'Depósito']);

        $this->producto = $this->cuenta->productos()->create(['nombre' => 'Café']);
        $this->negocio->productos()->attach($this->producto->id, ['precio' => 10, 'costo' => 6]);
        $this->almacen->productos()->attach($this->producto->id, ['cantidad' => 20]);

        Access::create([
            'user_id' => $this->dueno->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('super_admin_negocio')->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ]);
    }

    private function abrirIntervencion(?SolicitudSoporte $solicitud = null, int $minutos = 30): Intervencion
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.intervenciones.store', $this->negocio), [
                'password' => 'password',
                'solicitud_id' => $solicitud?->id,
                'motivo' => $solicitud ? null : 'El dueño reporta un faltante de caja que no cierra con las ventas.',
                'minutos' => $minutos,
            ])->assertRedirect();

        return Intervencion::latest('id')->firstOrFail();
    }

    // -------------------------------------------------- Expedientes (RF-51/52)

    public function test_un_usuario_normal_no_entra_a_los_expedientes(): void
    {
        $this->actingAs($this->dueno)
            ->get(route('admin.expedientes.negocio', $this->negocio))
            ->assertForbidden();

        $this->actingAs($this->dueno)
            ->get(route('admin.expedientes.usuario', $this->dueno))
            ->assertForbidden();
    }

    public function test_el_super_admin_ve_el_expediente_de_cualquier_negocio_sin_tener_acceso(): void
    {
        $this->assertDatabaseMissing('accesses', ['user_id' => $this->superAdmin->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.expedientes.negocio', $this->negocio))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Expedientes/Negocio')
                ->where('negocio.nombre', 'Cafetería')
                ->where('datos.resumen.almacenes', 1)
                ->where('intervencionVigente', null)
            );
    }

    public function test_cada_pestana_del_expediente_trae_sus_propios_datos(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.expedientes.negocio', ['negocio' => $this->negocio, 'pestana' => 'productos']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pestana', 'productos')
                ->where('datos.productos.0.nombre', 'Café')
                ->where('datos.productos.0.stock', 20)
            );

        $this->actingAs($this->superAdmin)
            ->get(route('admin.expedientes.negocio', ['negocio' => $this->negocio, 'pestana' => 'usuarios']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('datos.usuarios.0.usuario_id', $this->dueno->id));
    }

    public function test_el_expediente_del_usuario_muestra_sus_accesos(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.expedientes.usuario', $this->dueno))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Expedientes/Usuario')
                ->where('accesos.0.negocio', 'Cafetería')
            );
    }

    // ------------------------------------------------- Mirar vs tocar (RF-54)

    public function test_sin_intervencion_el_super_admin_no_puede_escribir_en_un_negocio_ajeno(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 99])
            ->assertForbidden();

        $this->assertSame(20, (int) $this->almacen->productos()->first()->pivot->cantidad);
    }

    public function test_el_permiso_de_lectura_esta_siempre_concedido_y_el_de_escritura_no(): void
    {
        $scheduler = app(AccessScheduler::class);

        // Mirar es libre: es el 90% del soporte, y una ceremonia rutinaria
        // dejaria de funcionar como control.
        $this->assertTrue($scheduler->hasPermission($this->superAdmin, $this->negocio, 'inventario.ver'));
        $this->assertTrue($scheduler->hasPermission($this->superAdmin, $this->negocio, 'reportes.ver'));

        // Tocar, no.
        $this->assertFalse($scheduler->hasPermission($this->superAdmin, $this->negocio, 'inventario.editar'));
        $this->assertFalse($scheduler->hasPermission($this->superAdmin, $this->negocio, 'ventas.anular'));
    }

    /**
     * Fuera de una intervencion el Super Admin no tiene negocio activo, asi
     * que las pantallas operativas del dueno no le abren: mira por el
     * expediente, que es de solo lectura por construccion.
     */
    public function test_fuera_de_una_intervencion_el_super_admin_mira_por_el_expediente(): void
    {
        $this->actingAs($this->superAdmin)->get(route('productos.index'))->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.expedientes.negocio', ['negocio' => $this->negocio, 'pestana' => 'productos']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('datos.productos.0.stock', 20));
    }

    public function test_abrir_una_intervencion_exige_la_contrasena_correcta(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.intervenciones.store', $this->negocio), [
                'password' => 'no-es-mi-clave',
                'motivo' => 'Necesito revisar el faltante de caja del turno 34.',
                'minutos' => 30,
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, Intervencion::count());
    }

    public function test_abrir_una_intervencion_exige_un_motivo_con_contenido(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.intervenciones.store', $this->negocio), [
                'password' => 'password',
                'motivo' => 'nada',
                'minutos' => 30,
            ])
            ->assertSessionHasErrors('motivo');
    }

    public function test_con_una_intervencion_vigente_el_super_admin_puede_escribir(): void
    {
        $this->abrirIntervencion();

        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 99])
            ->assertRedirect();

        $this->assertSame(99, (int) $this->almacen->productos()->first()->pivot->cantidad);
    }

    public function test_lo_que_se_toca_bajo_intervencion_queda_ligado_a_ella_y_a_su_autor_real(): void
    {
        $intervencion = $this->abrirIntervencion();

        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 99]);

        $movimiento = ActivityLog::where('evento', 'stock.ajustado')->latest('id')->firstOrFail();

        $this->assertSame($intervencion->id, $movimiento->intervencion_id);
        // Sin suplantación: el autor es el Super Admin, no el dueño (RF-56).
        $this->assertSame($this->superAdmin->id, $movimiento->causer_id);
    }

    public function test_una_intervencion_vencida_deja_de_habilitar_la_escritura(): void
    {
        $this->abrirIntervencion(minutos: 30);

        $this->travel(31)->minutes();

        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 99])
            ->assertForbidden();

        $this->assertSame(20, (int) $this->almacen->productos()->first()->pivot->cantidad);
    }

    public function test_la_intervencion_solo_alcanza_al_negocio_para_el_que_se_abrio(): void
    {
        $otro = Business::create(['account_id' => $this->cuenta->id, 'nombre' => 'El Fogón', 'tipo' => 'productos']);
        $otroAlmacen = $otro->almacenes()->create(['nombre' => 'Cocina']);
        $otro->productos()->attach($this->producto->id, ['precio' => 15, 'costo' => 8]);
        $otroAlmacen->productos()->attach($this->producto->id, ['cantidad' => 5]);

        $this->abrirIntervencion();

        // El negocio activo del Super Admin es el intervenido; el otro negocio
        // no debería quedar expuesto de rebote.
        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $otroAlmacen]), ['cantidad' => 99])
            ->assertForbidden();

        $this->assertSame(5, (int) $otroAlmacen->productos()->first()->pivot->cantidad);
    }

    public function test_al_cerrar_la_intervencion_el_acta_se_escribe_sola(): void
    {
        $intervencion = $this->abrirIntervencion();

        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 99]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.intervenciones.cerrar', $intervencion))
            ->assertRedirect();

        $intervencion->refresh();

        $this->assertSame('cerrada', $intervencion->estado);
        $this->assertNotNull($intervencion->acta);
        $this->assertGreaterThanOrEqual(1, $intervencion->acta['total_movimientos']);

        $eventos = collect($intervencion->acta['movimientos'])->pluck('evento');
        $this->assertContains('stock.ajustado', $eventos);
    }

    public function test_cerrada_la_intervencion_ya_no_se_puede_escribir(): void
    {
        $intervencion = $this->abrirIntervencion();

        $this->actingAs($this->superAdmin)->patch(route('admin.intervenciones.cerrar', $intervencion));

        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 99])
            ->assertForbidden();
    }

    public function test_el_salario_del_cajero_tambien_exige_intervencion(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('negocios.salario.update', $this->negocio), [
                'salario_monto_fijo' => 999,
                'salario_porcentaje' => 0,
                'salario_base_porcentaje' => 'venta',
            ])->assertForbidden();

        $this->abrirIntervencion();

        $this->actingAs($this->superAdmin)
            ->patch(route('negocios.salario.update', $this->negocio), [
                'salario_monto_fijo' => 999,
                'salario_porcentaje' => 0,
                'salario_base_porcentaje' => 'venta',
            ])->assertRedirect();

        $this->assertEquals(999, $this->negocio->fresh()->salario_monto_fijo);
    }

    // ------------------------------------------ Ciclo de la solicitud (RF-58+)

    public function test_el_dueno_abre_una_solicitud_de_revision(): void
    {
        $this->actingAs($this->dueno)
            ->post(route('negocio.solicitudes.store'), [
                'tipo' => 'correccion',
                'descripcion' => 'El turno 34 cerró con un faltante que no cuadra con las ventas registradas.',
            ])->assertRedirect();

        $solicitud = SolicitudSoporte::firstOrFail();

        $this->assertSame($this->negocio->id, $solicitud->business_id);
        $this->assertSame('abierta', $solicitud->estado);
        $this->assertNotNull(ActivityLog::where('evento', 'solicitud.creada')->first());
    }

    public function test_la_intervencion_nacida_de_una_solicitud_hereda_el_motivo_del_dueno(): void
    {
        $solicitud = SolicitudSoporte::create([
            'business_id' => $this->negocio->id,
            'solicitante_id' => $this->dueno->id,
            'tipo' => 'correccion',
            'descripcion' => 'El stock de café quedó en negativo después del traspaso.',
        ]);

        $intervencion = $this->abrirIntervencion($solicitud);

        $this->assertSame('solicitud', $intervencion->origen);
        $this->assertSame($solicitud->id, $intervencion->solicitud_id);
        // El motivo NO lo escribió el soporte: es el pedido del dueño (RF-59).
        $this->assertSame($solicitud->descripcion, $intervencion->motivo);
        $this->assertSame('en_revision', $solicitud->fresh()->estado);
    }

    public function test_una_solicitud_no_justifica_intervenir_otro_negocio(): void
    {
        $otraCuenta = Account::create(['owner_user_id' => User::factory()->create()->id, 'nombre_cliente' => 'Otra']);
        $otroNegocio = Business::create(['account_id' => $otraCuenta->id, 'nombre' => 'Ajeno', 'tipo' => 'productos']);

        $solicitud = SolicitudSoporte::create([
            'business_id' => $this->negocio->id,
            'solicitante_id' => $this->dueno->id,
            'tipo' => 'revision',
            'descripcion' => 'Revisar el faltante de caja del turno 34, por favor.',
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.intervenciones.store', $otroNegocio), [
                'password' => 'password',
                'solicitud_id' => $solicitud->id,
                'minutos' => 30,
            ])->assertForbidden();
    }

    public function test_el_ciclo_lo_cierra_el_dueno_con_su_conformidad(): void
    {
        $solicitud = SolicitudSoporte::create([
            'business_id' => $this->negocio->id,
            'solicitante_id' => $this->dueno->id,
            'tipo' => 'correccion',
            'descripcion' => 'El stock de café quedó mal después del traspaso.',
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.solicitudes.resolver', $solicitud), ['respuesta' => 'Corregido el stock a 20 unidades.'])
            ->assertRedirect();

        $solicitud->refresh();
        $this->assertSame('resuelta', $solicitud->estado);
        $this->assertTrue($solicitud->esperaConformidad(), 'El soporte no puede dar el ciclo por cerrado solo');

        $this->actingAs($this->dueno)
            ->patch(route('negocio.solicitudes.conformidad', $solicitud), ['conformidad' => 'conforme'])
            ->assertRedirect();

        $this->assertSame('conforme', $solicitud->fresh()->conformidad);
    }

    public function test_el_dueno_puede_reabrir_una_solicitud_que_no_quedo_resuelta(): void
    {
        $solicitud = SolicitudSoporte::create([
            'business_id' => $this->negocio->id,
            'solicitante_id' => $this->dueno->id,
            'tipo' => 'correccion',
            'descripcion' => 'El stock de café quedó mal después del traspaso.',
            'estado' => 'resuelta',
            'respuesta' => 'Listo.',
        ]);

        $this->actingAs($this->dueno)
            ->patch(route('negocio.solicitudes.conformidad', $solicitud), ['conformidad' => 'reabierta'])
            ->assertRedirect();

        $solicitud->refresh();
        $this->assertSame('abierta', $solicitud->estado);
        $this->assertSame('reabierta', $solicitud->conformidad);
    }

    public function test_un_tercero_no_puede_dar_conformidad_por_el_dueno(): void
    {
        $cajero = User::factory()->create();
        Access::create([
            'user_id' => $cajero->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('administrador')->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ]);

        $solicitud = SolicitudSoporte::create([
            'business_id' => $this->negocio->id,
            'solicitante_id' => $this->dueno->id,
            'tipo' => 'correccion',
            'descripcion' => 'El stock de café quedó mal después del traspaso.',
            'estado' => 'resuelta',
            'respuesta' => 'Listo.',
        ]);

        $this->actingAs($cajero)
            ->patch(route('negocio.solicitudes.conformidad', $solicitud), ['conformidad' => 'conforme'])
            ->assertForbidden();
    }

    // ------------------------------------------------ El dueño mira (RF-61)

    public function test_el_dueno_ve_su_libro_y_las_intervenciones_sobre_su_negocio(): void
    {
        $intervencion = $this->abrirIntervencion();

        $this->actingAs($this->superAdmin)
            ->patch(route('productos.stock', [$this->producto, $this->almacen]), ['cantidad' => 99]);

        $this->actingAs($this->superAdmin)->patch(route('admin.intervenciones.cerrar', $intervencion));

        $this->actingAs($this->dueno)
            ->get(route('negocio.libro'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Negocio/Libro/Index')
                ->where('intervenciones.0.id', $intervencion->id)
                ->where('intervenciones.0.motivo', $intervencion->motivo)
                // El acta completa, sin tener que pedirla.
                ->has('intervenciones.0.acta.movimientos')
            );
    }

    public function test_un_dependiente_no_llega_al_libro_del_negocio(): void
    {
        $cajero = User::factory()->create();
        Access::create([
            'user_id' => $cajero->id,
            'business_id' => $this->negocio->id,
            'role_id' => Role::findByName('dependiente')->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
        ]);

        $this->actingAs($cajero)->get(route('negocio.libro'))->assertForbidden();
    }
}
