<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Intervencion;
use App\Models\SolicitudSoporte;
use App\Services\Intervenciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Apertura y cierre de las intervenciones de soporte (RF-54).
 *
 * Escribir en el negocio de otro no puede ser un clic más: pide la
 * contraseña otra vez, un motivo, y se apaga sola. Mirar, en cambio, no pide
 * nada (ver ExpedienteController) — si mirar también tuviera ceremonia, la
 * ceremonia se volvería rutina y dejaría de significar algo.
 */
class IntervencionController extends Controller
{
    public function __construct(private Intervenciones $intervenciones) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Intervenciones/Index', [
            'intervenciones' => Intervencion::with(['business.account', 'superAdmin', 'solicitud'])
                ->latest('id')
                ->paginate(25)
                ->through(fn (Intervencion $i) => [
                    'id' => $i->id,
                    'negocio' => $i->business?->nombre,
                    'negocio_id' => $i->business_id,
                    'cuenta' => $i->business?->account?->nombre_cliente,
                    'super_admin' => $i->superAdmin?->nombreCompleto(),
                    'origen' => $i->origen,
                    'solicitud_id' => $i->solicitud_id,
                    'motivo' => $i->motivo,
                    'estado' => $i->estado,
                    'vigente' => $i->estaVigente(),
                    'minutos_restantes' => $i->minutosRestantes(),
                    'abierta_at' => $i->abierta_at?->toIso8601String(),
                    'cerrada_at' => $i->cerrada_at?->toIso8601String(),
                    'total_movimientos' => $i->acta['total_movimientos'] ?? null,
                ]),
        ]);
    }

    public function create(Request $request, Business $negocio): Response
    {
        $negocio->load('account.owner');

        $solicitudId = $request->integer('solicitud_id') ?: null;

        return Inertia::render('Admin/Intervenciones/Create', [
            'negocio' => [
                'id' => $negocio->id,
                'nombre' => $negocio->nombre,
                'cuenta' => $negocio->account->nombre_cliente,
                'dueno' => $negocio->account->owner?->nombreCompleto(),
            ],
            // Solicitudes del dueño todavía sin resolver: son el camino
            // normal, y por eso se ofrecen antes que el motivo libre.
            'solicitudes' => SolicitudSoporte::with('solicitante')
                ->where('business_id', $negocio->id)
                ->whereIn('estado', ['abierta', 'en_revision'])
                ->latest('id')->get()
                ->map(fn (SolicitudSoporte $s) => [
                    'id' => $s->id,
                    'tipo' => SolicitudSoporte::TIPOS[$s->tipo] ?? $s->tipo,
                    'descripcion' => $s->descripcion,
                    'solicitante' => $s->solicitante?->nombreCompleto(),
                    'fecha' => $s->created_at?->toIso8601String(),
                ])->values(),
            'solicitudPreseleccionada' => $solicitudId,
            'minutosPorDefecto' => Intervencion::MINUTOS_POR_DEFECTO,
        ]);
    }

    public function store(Request $request, Business $negocio): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'solicitud_id' => ['nullable', 'integer', 'exists:solicitudes_soporte,id'],
            'motivo' => ['required_without:solicitud_id', 'nullable', 'string', 'min:10', 'max:1000'],
            'minutos' => ['required', 'integer', 'min:5', 'max:240'],
        ], [
            'motivo.min' => 'El motivo tiene que explicar qué vas a hacer: en seis meses vos misma vas a leerlo.',
            'motivo.required_without' => 'Sin una solicitud del dueño, hace falta escribir el motivo.',
        ]);

        $this->reautenticar($request, $data['password']);

        $solicitud = null;

        if (! empty($data['solicitud_id'])) {
            $solicitud = SolicitudSoporte::findOrFail($data['solicitud_id']);

            // Una solicitud solo justifica intervenir el negocio del que
            // salió: si no, el pedido del dueño de un negocio serviría de
            // excusa para entrar en cualquier otro.
            abort_unless($solicitud->business_id === $negocio->id, 403);
        }

        $intervencion = $this->intervenciones->abrir(
            admin: $request->user(),
            negocio: $negocio,
            // Cuando nace de una solicitud, el motivo NO lo escribe el
            // soporte: es el pedido firmado por el dueño (RF-59).
            motivo: $solicitud ? $solicitud->descripcion : $data['motivo'],
            origen: $solicitud ? 'solicitud' : 'iniciativa',
            solicitud: $solicitud,
            minutos: $data['minutos'],
        );

        if ($solicitud && $solicitud->estado === 'abierta') {
            $solicitud->update(['estado' => 'en_revision']);
        }

        return redirect()->route('admin.expedientes.negocio', $negocio)
            ->with('mensaje', "Intervención #{$intervencion->id} abierta. Todo lo que hagas queda registrado a tu nombre.");
    }

    public function show(Intervencion $intervencion): Response
    {
        $intervencion->load(['business.account', 'superAdmin', 'solicitud.solicitante']);

        return Inertia::render('Admin/Intervenciones/Show', [
            'intervencion' => [
                'id' => $intervencion->id,
                'negocio' => $intervencion->business?->nombre,
                'negocio_id' => $intervencion->business_id,
                'cuenta' => $intervencion->business?->account?->nombre_cliente,
                'super_admin' => $intervencion->superAdmin?->nombreCompleto(),
                'origen' => $intervencion->origen,
                'motivo' => $intervencion->motivo,
                'estado' => $intervencion->estado,
                'vigente' => $intervencion->estaVigente(),
                'minutos_restantes' => $intervencion->minutosRestantes(),
                'abierta_at' => $intervencion->abierta_at?->toIso8601String(),
                'expira_at' => $intervencion->expira_at?->toIso8601String(),
                'cerrada_at' => $intervencion->cerrada_at?->toIso8601String(),
                'acta' => $intervencion->acta,
                'solicitud' => $intervencion->solicitud ? [
                    'id' => $intervencion->solicitud->id,
                    'descripcion' => $intervencion->solicitud->descripcion,
                    'solicitante' => $intervencion->solicitud->solicitante?->nombreCompleto(),
                ] : null,
            ],
            // Mientras sigue abierta, el acta todavía no existe: se muestra en
            // vivo lo que lleva hecho.
            'movimientosEnVivo' => $intervencion->estado === 'abierta'
                ? $intervencion->movimientos()->orderBy('id')->get()
                    ->map(fn ($m) => [
                        'fecha' => $m->created_at?->toIso8601String(),
                        'evento' => $m->evento,
                        'descripcion' => $m->description,
                        'cambios' => $m->changes,
                    ])->values()
                : [],
        ]);
    }

    public function cerrar(Intervencion $intervencion): RedirectResponse
    {
        $this->intervenciones->cerrar($intervencion);

        return redirect()->route('admin.intervenciones.show', $intervencion)
            ->with('mensaje', 'Intervención cerrada. El acta quedó levantada a partir del libro de movimientos.');
    }

    /**
     * Pedir la contraseña otra vez es lo más barato de todo el mecanismo y
     * cubre el caso más probable de todos: la sesión abierta en una máquina
     * que alguien más alcanzó.
     */
    private function reautenticar(Request $request, string $password): void
    {
        if (! Hash::check($password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'La contraseña no coincide.',
            ]);
        }
    }
}
