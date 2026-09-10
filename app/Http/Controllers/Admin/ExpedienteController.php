<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Access;
use App\Models\ActivityLog;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Intervencion;
use App\Models\Producto;
use App\Models\SolicitudSoporte;
use App\Models\Traspaso;
use App\Models\Turno;
use App\Models\User;
use App\Models\Venta;
use App\Services\Intervenciones;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Expedientes de solo lectura del Super Admin del Sistema (RF-51 y RF-52).
 *
 * Es el espejo de lo que ve el dueño, sin un solo control de edición: mirar
 * no cambia nada y por eso no pide ceremonia. Para tocar algo hace falta
 * abrir una intervención (ver IntervencionController).
 *
 * El eje del expediente es el NEGOCIO y no el dueño, aunque la necesidad se
 * planteó al revés: un dueño tiene varios negocios, y lo que más hay que
 * auditar (ventas, anulaciones, cierres de caja, ajustes de stock) no lo hace
 * él sino el cajero. Con el dueño como eje, ese cajero quedaba escondido dos
 * niveles más abajo.
 *
 * Cada pestaña se consulta por separado en vez de traer todo junto: un
 * negocio con años de ventas haría inservible una sola carga.
 */
class ExpedienteController extends Controller
{
    public const PESTANAS = ['resumen', 'almacenes', 'productos', 'traspasos', 'turnos', 'usuarios', 'libro', 'soporte'];

    public function negocio(Request $request, Business $negocio, Intervenciones $intervenciones): Response
    {
        $pestana = in_array($request->query('pestana'), self::PESTANAS, true)
            ? $request->query('pestana')
            : 'resumen';

        $negocio->load(['account.owner', 'rubro']);

        return Inertia::render('Admin/Expedientes/Negocio', [
            'negocio' => [
                'id' => $negocio->id,
                'nombre' => $negocio->nombre,
                'tipo' => $negocio->tipo,
                'estado' => $negocio->estado,
                'moneda' => $negocio->moneda,
                'rubro' => $negocio->rubro?->nombre,
                'cuenta' => $negocio->account->nombre_cliente,
                'cuenta_id' => $negocio->account_id,
                'dueno' => $negocio->account->owner?->nombreCompleto(),
                'dueno_id' => $negocio->account->owner_user_id,
                'salario' => [
                    'monto_fijo' => (float) $negocio->salario_monto_fijo,
                    'porcentaje' => (float) $negocio->salario_porcentaje,
                    'base' => Business::BASES_PORCENTAJE_SALARIO[$negocio->salario_base_porcentaje] ?? $negocio->salario_base_porcentaje,
                    'delegado_al_admin' => (bool) $negocio->admin_puede_configurar_salario,
                ],
            ],
            'pestanas' => self::PESTANAS,
            'pestana' => $pestana,
            'datos' => $this->datosDePestana($negocio, $pestana),
            // Lo que hace visible, en todo momento, si esta sesión está
            // mirando o tocando.
            'intervencionVigente' => $this->serializarIntervencion(
                $intervenciones->vigentePara($request->user(), $negocio->id)
            ),
        ]);
    }

    public function usuario(User $usuario): Response
    {
        $usuario->load(['accesses.business', 'accesses.role']);

        $turnos = Turno::with('business')
            ->where('cajero_id', $usuario->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return Inertia::render('Admin/Expedientes/Usuario', [
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombreCompleto(),
                'username' => $usuario->username,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'estado_global' => $usuario->estado_global,
                'es_super_admin' => (bool) $usuario->is_super_admin_sistema,
                'creado_en' => $usuario->created_at?->toIso8601String(),
            ],
            'accesos' => $usuario->accesses->map(fn (Access $a) => [
                'id' => $a->id,
                'negocio' => $a->business?->nombre,
                'negocio_id' => $a->business_id,
                'rol' => str_replace('_', ' ', (string) $a->role?->name),
                'estado' => $a->estado,
                'vigencia' => $this->describirVigencia($a),
            ])->values(),
            'turnos' => $turnos->map(fn (Turno $t) => [
                'id' => $t->id,
                'negocio' => $t->business?->nombre,
                'estado' => $t->estado,
                'apertura' => $t->fecha_apertura?->toIso8601String(),
                'cierre' => $t->fecha_cierre?->toIso8601String(),
                'venta' => (float) $t->total_venta,
                'salario' => (float) $t->salario,
                // El descuadre por cierre es el número por el que empieza
                // cualquier revisión de un cajero.
                'descuadre' => $t->estado === 'cerrado' ? $t->descuadre() : null,
            ])->values(),
            'ventasAnuladas' => Venta::with('turno.business')
                ->whereIn('turno_id', $turnos->pluck('id'))
                ->where('estado', 'anulado')
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (Venta $v) => [
                    'id' => $v->id,
                    'negocio' => $v->turno?->business?->nombre,
                    'monto' => (float) $v->monto_total,
                    'fecha' => $v->fecha_hora?->toIso8601String(),
                ])->values(),
            'movimientos' => ActivityLog::with('business')
                ->where('causer_id', $usuario->id)
                ->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (ActivityLog $m) => $this->serializarMovimiento($m)),
        ]);
    }

    /**
     * Solo la pestaña pedida toca la base. Cada rama devuelve ya listo lo que
     * la vista necesita, sin modelos crudos: el expediente no debe poder
     * filtrar por accidente un campo que el dueño no querría ver expuesto.
     */
    private function datosDePestana(Business $negocio, string $pestana): array
    {
        return match ($pestana) {
            'almacenes' => [
                'almacenes' => $negocio->almacenes()->withCount('productos')->get()
                    ->map(fn (Almacen $a) => [
                        'id' => $a->id,
                        'nombre' => $a->nombre,
                        'estado' => $a->estado,
                        'productos' => $a->productos_count,
                        'unidades' => (int) $a->productos()->sum('almacen_producto.cantidad'),
                    ])->values(),
            ],

            'productos' => [
                'productos' => $negocio->productos()->with('almacenes')->get()
                    ->map(fn (Producto $p) => [
                        'id' => $p->id,
                        'nombre' => $p->nombre,
                        'sku' => $p->sku,
                        'categoria' => $p->categoria,
                        'precio' => (float) $p->pivot->precio,
                        'costo' => (float) $p->pivot->costo,
                        'estado' => $p->pivot->estado,
                        'stock' => (int) $p->almacenes
                            ->where('business_id', $negocio->id)
                            ->sum(fn ($a) => $a->pivot->cantidad),
                    ])->values(),
            ],

            'traspasos' => [
                'traspasos' => Traspaso::with(['producto', 'almacenOrigen.business', 'almacenDestino.business', 'solicitadoPor', 'autorizadoPor', 'confirmadoPor'])
                    ->where(function ($q) use ($negocio) {
                        $ids = $negocio->almacenes()->pluck('id');
                        $q->whereIn('almacen_origen_id', $ids)->orWhereIn('almacen_destino_id', $ids);
                    })
                    ->latest('id')->limit(100)->get()
                    ->map(fn (Traspaso $t) => [
                        'id' => $t->id,
                        'producto' => $t->producto?->nombre,
                        'cantidad' => $t->cantidad,
                        'estado' => $t->estado,
                        'origen' => $t->almacenOrigen?->nombre,
                        'destino' => $t->almacenDestino?->nombre,
                        'solicitado_por' => $t->solicitadoPor?->name,
                        'autorizado_por' => $t->autorizadoPor?->name,
                        'confirmado_por' => $t->confirmadoPor?->name,
                        'fecha' => $t->created_at?->toIso8601String(),
                    ])->values(),
            ],

            'turnos' => [
                'turnos' => $negocio->turnos()->with(['cajero', 'almacen'])->latest('id')->limit(100)->get()
                    ->map(fn (Turno $t) => [
                        'id' => $t->id,
                        'cajero' => $t->cajero?->nombreCompleto(),
                        'cajero_id' => $t->cajero_id,
                        'almacen' => $t->almacen?->nombre,
                        'dispositivo' => $t->dispositivo,
                        'estado' => $t->estado,
                        'apertura' => $t->fecha_apertura?->toIso8601String(),
                        'cierre' => $t->fecha_cierre?->toIso8601String(),
                        'venta' => (float) $t->total_venta,
                        'utilidad' => (float) $t->total_utilidad,
                        'salario' => (float) $t->salario,
                        'deposito' => (float) $t->deposito,
                        'descuadre' => $t->estado === 'cerrado' ? $t->descuadre() : null,
                        'ventas' => $t->ventas()->count(),
                        'anuladas' => $t->ventas()->where('estado', 'anulado')->count(),
                    ])->values(),
            ],

            'usuarios' => [
                'usuarios' => Access::with(['user', 'role'])
                    ->where('business_id', $negocio->id)
                    ->get()
                    ->map(fn (Access $a) => [
                        'acceso_id' => $a->id,
                        'usuario_id' => $a->user_id,
                        'nombre' => $a->user?->nombreCompleto(),
                        'username' => $a->user?->username,
                        'estado_usuario' => $a->user?->estado_global,
                        'rol' => str_replace('_', ' ', (string) $a->role?->name),
                        'estado_acceso' => $a->estado,
                        'vigencia' => $this->describirVigencia($a),
                    ])->values(),
            ],

            'libro' => [
                'movimientos' => ActivityLog::with(['causer', 'intervencion'])
                    ->where('business_id', $negocio->id)
                    ->latest('id')
                    ->paginate(30)
                    ->withQueryString()
                    ->through(fn (ActivityLog $m) => $this->serializarMovimiento($m)),
            ],

            'soporte' => [
                'solicitudes' => SolicitudSoporte::with('solicitante')
                    ->where('business_id', $negocio->id)
                    ->latest('id')->get()
                    ->map(fn (SolicitudSoporte $s) => [
                        'id' => $s->id,
                        'tipo' => $s->tipo,
                        'descripcion' => $s->descripcion,
                        'estado' => $s->estado,
                        'conformidad' => $s->conformidad,
                        'solicitante' => $s->solicitante?->nombreCompleto(),
                        'fecha' => $s->created_at?->toIso8601String(),
                    ])->values(),
                'intervenciones' => Intervencion::with('superAdmin')
                    ->where('business_id', $negocio->id)
                    ->latest('id')->get()
                    ->map(fn (Intervencion $i) => $this->serializarIntervencion($i))
                    ->values(),
            ],

            default => [
                'resumen' => [
                    'almacenes' => $negocio->almacenes()->count(),
                    'productos' => $negocio->productos()->count(),
                    'usuarios' => Access::where('business_id', $negocio->id)->distinct('user_id')->count('user_id'),
                    'turnos_abiertos' => $negocio->turnos()->where('estado', 'abierto')->count(),
                    'turnos_totales' => $negocio->turnos()->count(),
                    'movimientos' => ActivityLog::where('business_id', $negocio->id)->count(),
                    'solicitudes_abiertas' => SolicitudSoporte::where('business_id', $negocio->id)
                        ->whereIn('estado', ['abierta', 'en_revision'])->count(),
                    'ultimo_movimiento' => ActivityLog::where('business_id', $negocio->id)
                        ->latest('id')->first()?->created_at?->toIso8601String(),
                ],
                'ultimosMovimientos' => ActivityLog::with('causer')
                    ->where('business_id', $negocio->id)
                    ->latest('id')->limit(10)->get()
                    ->map(fn (ActivityLog $m) => $this->serializarMovimiento($m))->values(),
            ],
        };
    }

    private function serializarMovimiento(ActivityLog $m): array
    {
        return [
            'id' => $m->id,
            'fecha' => $m->created_at?->toIso8601String(),
            'evento' => $m->evento,
            'descripcion' => $m->description,
            'autor' => $m->causer?->nombreCompleto() ?? 'Sistema',
            'autor_id' => $m->causer_id,
            'negocio' => $m->relationLoaded('business') ? $m->business?->nombre : null,
            'cambios' => $m->changes,
            // Marcar el movimiento hecho bajo intervención es lo que permite
            // distinguir de un vistazo lo que hizo el negocio de lo que hizo
            // el soporte (RF-56).
            'intervencion_id' => $m->intervencion_id,
        ];
    }

    private function serializarIntervencion(?Intervencion $i): ?array
    {
        if (! $i) {
            return null;
        }

        return [
            'id' => $i->id,
            'motivo' => $i->motivo,
            'origen' => $i->origen,
            'solicitud_id' => $i->solicitud_id,
            'estado' => $i->estado,
            'vigente' => $i->estaVigente(),
            'minutos_restantes' => $i->minutosRestantes(),
            'abierta_at' => $i->abierta_at?->toIso8601String(),
            'expira_at' => $i->expira_at?->toIso8601String(),
            'cerrada_at' => $i->cerrada_at?->toIso8601String(),
            'super_admin' => $i->relationLoaded('superAdmin') ? $i->superAdmin?->nombreCompleto() : null,
            'total_movimientos' => $i->acta['total_movimientos'] ?? null,
        ];
    }

    /**
     * La vigencia en una línea legible: el expediente se lee para entender
     * rápido, no para reconstruir a mano cuatro columnas de fechas y horas.
     */
    private function describirVigencia(Access $a): string
    {
        $partes = [$a->tipo_vigencia];

        if ($a->fecha_inicio || $a->fecha_fin) {
            $partes[] = trim(($a->fecha_inicio?->format('d-m-Y') ?? '...').' a '.($a->fecha_fin?->format('d-m-Y') ?? '...'));
        }

        if ($a->hora_inicio && $a->hora_fin) {
            $partes[] = "{$a->hora_inicio} - {$a->hora_fin}";
        }

        if ($a->tipo_vigencia === 'semanal' && $a->dias_semana) {
            $nombres = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'];
            $partes[] = collect($a->dias_semana)->map(fn ($d) => $nombres[$d] ?? $d)->implode(', ');
        }

        return implode(' · ', $partes);
    }
}
