<?php

namespace App\Http\Controllers\Negocio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventario\Concerns\RequiereNegocioActivo;
use App\Models\ActivityLog;
use App\Models\Intervencion;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El libro de movimientos visto por el DUEÑO, y la lista de todas las veces
 * que el soporte entró a su negocio (RF-61).
 *
 * Que esto exista es lo que vuelve real la transparencia del mecanismo de
 * intervenciones: si el dueño tuviera que pedir el listado para enterarse de
 * que alguien entró, la garantía sería solo una promesa.
 */
class LibroController extends Controller
{
    use RequiereNegocioActivo;

    public function index(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'reportes.ver');

        $evento = $request->query('evento');

        $movimientos = ActivityLog::with(['causer', 'intervencion'])
            ->where('business_id', $negocio->id)
            ->when($evento, fn ($q) => $q->where('evento', 'like', $evento.'%'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ActivityLog $m) => [
                'id' => $m->id,
                'fecha' => $m->created_at?->toIso8601String(),
                'evento' => $m->evento,
                'descripcion' => $m->description,
                'autor' => $m->causer?->nombreCompleto() ?? 'Sistema',
                'cambios' => $m->changes,
                'intervencion_id' => $m->intervencion_id,
            ]);

        return Inertia::render('Negocio/Libro/Index', [
            'negocio' => ['id' => $negocio->id, 'nombre' => $negocio->nombre],
            'movimientos' => $movimientos,
            'filtros' => ['evento' => $evento],
            // Familias de evento presentes en ESTE negocio: un filtro con
            // opciones que no existen en el libro solo hace perder tiempo.
            'familias' => ActivityLog::where('business_id', $negocio->id)
                ->whereNotNull('evento')
                ->distinct()
                ->pluck('evento')
                ->map(fn ($e) => explode('.', $e)[0])
                ->unique()
                ->sort()
                ->values(),
            'intervenciones' => Intervencion::with('superAdmin')
                ->where('business_id', $negocio->id)
                ->latest('id')
                ->get()
                ->map(fn (Intervencion $i) => [
                    'id' => $i->id,
                    'soporte' => $i->superAdmin?->nombreCompleto(),
                    'origen' => $i->origen,
                    'motivo' => $i->motivo,
                    'estado' => $i->estado,
                    'vigente' => $i->estaVigente(),
                    'abierta_at' => $i->abierta_at?->toIso8601String(),
                    'cerrada_at' => $i->cerrada_at?->toIso8601String(),
                    'duracion_minutos' => $i->acta['duracion_minutos'] ?? null,
                    'total_movimientos' => $i->acta['total_movimientos'] ?? null,
                    // El dueño ve el acta completa: sin eso, "queda un acta"
                    // sería una formalidad interna del soporte.
                    'acta' => $i->acta,
                ])->values(),
        ]);
    }
}
