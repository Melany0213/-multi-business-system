<?php

namespace App\Http\Controllers\Negocio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventario\Concerns\RequiereNegocioActivo;
use App\Models\SolicitudSoporte;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El dueño le pide al soporte que revise o corrija algo (RF-58), y después
 * dice si le sirvió (RF-60).
 *
 * Esta es la pieza que cierra el ciclo completo: una intervención abierta a
 * partir de una solicitud hereda su motivo del pedido del propio dueño, así
 * que el consentimiento no hay que pedirlo aparte — ya quedó probado en el
 * registro. Y el ciclo no lo termina el soporte declarándose satisfecho: lo
 * termina el dueño.
 */
class SolicitudController extends Controller
{
    use RequiereNegocioActivo;

    public function index(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'reportes.ver');

        return Inertia::render('Negocio/Solicitudes/Index', [
            'negocio' => ['id' => $negocio->id, 'nombre' => $negocio->nombre],
            'tipos' => SolicitudSoporte::TIPOS,
            'solicitudes' => SolicitudSoporte::with(['solicitante', 'intervenciones'])
                ->where('business_id', $negocio->id)
                ->latest('id')
                ->get()
                ->map(fn (SolicitudSoporte $s) => [
                    'id' => $s->id,
                    'tipo' => $s->tipo,
                    'tipo_etiqueta' => SolicitudSoporte::TIPOS[$s->tipo] ?? $s->tipo,
                    'descripcion' => $s->descripcion,
                    'estado' => $s->estado,
                    'respuesta' => $s->respuesta,
                    'conformidad' => $s->conformidad,
                    'espera_conformidad' => $s->esperaConformidad(),
                    'solicitante' => $s->solicitante?->nombreCompleto(),
                    'puede_responder' => $this->puedeDarConformidad($request, $s),
                    'intervenciones' => $s->intervenciones->map(fn ($i) => [
                        'id' => $i->id,
                        'abierta_at' => $i->abierta_at?->toIso8601String(),
                        'cerrada_at' => $i->cerrada_at?->toIso8601String(),
                        'acta' => $i->acta,
                    ])->values(),
                    'fecha' => $s->created_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    public function store(Request $request, NegocioActivoResolver $resolver): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'reportes.ver');

        $data = $request->validate([
            'tipo' => ['required', 'in:'.implode(',', array_keys(SolicitudSoporte::TIPOS))],
            'descripcion' => ['required', 'string', 'min:15', 'max:2000'],
            'referencia_tipo' => ['nullable', 'string', 'max:255'],
            'referencia_id' => ['nullable', 'integer'],
        ], [
            'descripcion.min' => 'Contá qué pasó con algo de detalle: eso es lo que después queda como motivo de la revisión.',
        ]);

        SolicitudSoporte::create([
            ...$data,
            'business_id' => $negocio->id,
            'solicitante_id' => $request->user()->id,
        ]);

        return back()->with('mensaje', 'Solicitud enviada al soporte.');
    }

    /**
     * El dueño da por buena la solución, o la reabre. Reabrir no borra nada:
     * la solicitud vuelve a estar en juego con todo su historial a la vista.
     */
    public function conformidad(Request $request, SolicitudSoporte $solicitud): RedirectResponse
    {
        abort_unless($this->puedeDarConformidad($request, $solicitud), 403);
        abort_unless($solicitud->esperaConformidad(), 409, 'Esta solicitud no está esperando tu respuesta.');

        $data = $request->validate([
            'conformidad' => ['required', 'in:conforme,reabierta'],
        ]);

        $solicitud->update([
            'conformidad' => $data['conformidad'],
            'conformidad_at' => now(),
            'estado' => $data['conformidad'] === 'conforme' ? 'resuelta' : 'abierta',
        ]);

        return back()->with('mensaje', $data['conformidad'] === 'conforme'
            ? 'Gracias: la solicitud quedó cerrada con tu conformidad.'
            : 'La solicitud volvió a abrirse.');
    }

    /**
     * Responder por el negocio le toca a quien lo pidió o al dueño de la
     * cuenta — no a cualquiera que pase por ahí con permiso de lectura.
     */
    private function puedeDarConformidad(Request $request, SolicitudSoporte $solicitud): bool
    {
        $user = $request->user();

        return $solicitud->solicitante_id === $user->id
            || $solicitud->business?->account?->owner_user_id === $user->id;
    }
}
