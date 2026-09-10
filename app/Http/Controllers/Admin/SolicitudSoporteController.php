<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SolicitudSoporte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bandeja del soporte: los pedidos que hicieron los dueños (RF-58).
 *
 * El Super Admin puede tomarlas, resolverlas o rechazarlas — pero no puede
 * darlas por cerradas del todo: eso lo decide el dueño con su conformidad
 * (RF-60), y por eso acá no hay ninguna acción que salte ese paso.
 */
class SolicitudSoporteController extends Controller
{
    public function index(Request $request): Response
    {
        $estado = $request->query('estado');

        return Inertia::render('Admin/Solicitudes/Index', [
            'solicitudes' => SolicitudSoporte::with(['business.account', 'solicitante', 'intervenciones'])
                ->when(
                    in_array($estado, SolicitudSoporte::ESTADOS, true),
                    fn ($q) => $q->where('estado', $estado),
                )
                ->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SolicitudSoporte $s) => [
                    'id' => $s->id,
                    'negocio' => $s->business?->nombre,
                    'negocio_id' => $s->business_id,
                    'cuenta' => $s->business?->account?->nombre_cliente,
                    'solicitante' => $s->solicitante?->nombreCompleto(),
                    'tipo' => $s->tipo,
                    'tipo_etiqueta' => SolicitudSoporte::TIPOS[$s->tipo] ?? $s->tipo,
                    'descripcion' => $s->descripcion,
                    'referencia' => $this->describirReferencia($s),
                    'estado' => $s->estado,
                    'respuesta' => $s->respuesta,
                    'conformidad' => $s->conformidad,
                    'espera_conformidad' => $s->esperaConformidad(),
                    'intervenciones' => $s->intervenciones->pluck('id')->values(),
                    'fecha' => $s->created_at?->toIso8601String(),
                ]),
            'filtros' => ['estado' => $estado],
            'estados' => SolicitudSoporte::ESTADOS,
        ]);
    }

    /**
     * El soporte se hace cargo. Es un estado propio y no un detalle interno:
     * el dueño necesita ver que su pedido dejó de estar en la nada.
     */
    public function tomar(SolicitudSoporte $solicitud): RedirectResponse
    {
        abort_unless($solicitud->estado === 'abierta', 409, 'Esta solicitud ya fue tomada o cerrada.');

        $solicitud->update(['estado' => 'en_revision']);

        return back()->with('mensaje', 'Solicitud tomada.');
    }

    public function resolver(Request $request, SolicitudSoporte $solicitud): RedirectResponse
    {
        $data = $request->validate([
            'respuesta' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        abort_unless($solicitud->estaAbierta(), 409, 'Esta solicitud ya está cerrada.');

        // Queda "resuelta", no "cerrada": el ciclo se cierra cuando el dueño
        // responde si le sirvió (RF-60).
        $solicitud->update(['estado' => 'resuelta', 'respuesta' => $data['respuesta']]);

        return back()->with('mensaje', 'Solicitud resuelta. Queda esperando la conformidad del dueño.');
    }

    public function rechazar(Request $request, SolicitudSoporte $solicitud): RedirectResponse
    {
        $data = $request->validate([
            'respuesta' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        abort_unless($solicitud->estaAbierta(), 409, 'Esta solicitud ya está cerrada.');

        $solicitud->update(['estado' => 'rechazada', 'respuesta' => $data['respuesta']]);

        return back()->with('mensaje', 'Solicitud rechazada con su motivo.');
    }

    private function describirReferencia(SolicitudSoporte $s): ?string
    {
        if (! $s->referencia_tipo || ! $s->referencia_id) {
            return null;
        }

        return class_basename($s->referencia_tipo)." #{$s->referencia_id}";
    }
}
