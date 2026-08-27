<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventario\Concerns\RequiereNegocioActivo;
use App\Models\Business;
use App\Models\Traspaso;
use App\Services\AccessScheduler;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TraspasoController extends Controller
{
    use RequiereNegocioActivo;

    public function index(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.ver');

        $almacenIds = $negocio->almacenes()->pluck('id');

        $traspasos = Traspaso::with(['producto', 'almacenOrigen.business', 'almacenDestino.business', 'solicitadoPor', 'autorizadoPor', 'confirmadoPor'])
            ->where(function ($query) use ($almacenIds) {
                $query->whereIn('almacen_origen_id', $almacenIds)
                    ->orWhereIn('almacen_destino_id', $almacenIds);
            })
            ->latest()
            ->get()
            ->map(fn (Traspaso $t) => [
                'id' => $t->id,
                'producto' => $t->producto->nombre,
                'cantidad' => $t->cantidad,
                'estado' => $t->estado,
                'es_origen' => $almacenIds->contains($t->almacen_origen_id),
                'es_destino' => $almacenIds->contains($t->almacen_destino_id),
                'almacen_origen' => "{$t->almacenOrigen->business->nombre} · {$t->almacenOrigen->nombre}",
                'almacen_destino' => "{$t->almacenDestino->business->nombre} · {$t->almacenDestino->nombre}",
                'solicitado_por' => $t->solicitadoPor->name,
                'autorizado_por' => $t->autorizadoPor?->name,
                'confirmado_por' => $t->confirmadoPor?->name,
                'creado_en' => $t->created_at->format('d-m-Y H:i'),
            ]);

        return Inertia::render('Inventario/Traspasos/Index', [
            'traspasos' => $traspasos,
            'puedeAutorizar' => app(AccessScheduler::class)->hasPermission($request->user(), $negocio, 'inventario.autorizar_traslado'),
            'puedeTrasladar' => app(AccessScheduler::class)->hasPermission($request->user(), $negocio, 'inventario.trasladar'),
        ]);
    }

    public function create(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.trasladar');

        $negocio->loadMissing('account.businesses.almacenes');

        return Inertia::render('Inventario/Traspasos/Create', [
            'productos' => $negocio->productos()->where('business_producto.estado', 'activo')->get(['productos.id', 'productos.nombre']),
            'almacenesOrigen' => $negocio->almacenes()->where('estado', 'activo')->get(['id', 'nombre']),
            'destinos' => $negocio->account->businesses->flatMap(
                fn ($b) => $b->almacenes->where('estado', 'activo')->map(fn ($a) => [
                    'id' => $a->id,
                    'etiqueta' => "{$b->nombre} · {$a->nombre}",
                ])
            )->values(),
        ]);
    }

    public function store(Request $request, NegocioActivoResolver $resolver): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.trasladar');

        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'almacen_origen_id' => ['required', 'exists:almacenes,id', 'different:almacen_destino_id'],
            'almacen_destino_id' => ['required', 'exists:almacenes,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless(
            $negocio->almacenes()->where('id', $data['almacen_origen_id'])->exists(),
            403,
            'El almacén de origen debe ser de tu negocio activo.'
        );

        Traspaso::create([
            ...$data,
            'solicitado_por' => $request->user()->id,
        ]);

        return redirect()->route('traspasos.index');
    }

    public function autorizar(Request $request, NegocioActivoResolver $resolver, Traspaso $traspaso): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.autorizar_traslado');
        $this->autorizarComoOrigen($negocio, $traspaso);

        try {
            $traspaso->autorizar($request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['cantidad' => $e->getMessage()]);
        }

        return back();
    }

    public function rechazar(Request $request, NegocioActivoResolver $resolver, Traspaso $traspaso): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.autorizar_traslado');
        $this->autorizarComoOrigen($negocio, $traspaso);

        $traspaso->rechazar($request->user());

        return back();
    }

    public function confirmar(Request $request, NegocioActivoResolver $resolver, Traspaso $traspaso): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.trasladar');

        abort_unless($negocio->almacenes()->where('id', $traspaso->almacen_destino_id)->exists(), 403);

        try {
            $traspaso->confirmar($request->user());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['estado' => $e->getMessage()]);
        }

        return back();
    }

    private function autorizarComoOrigen(Business $negocio, Traspaso $traspaso): void
    {
        abort_unless($negocio->almacenes()->where('id', $traspaso->almacen_origen_id)->exists(), 403);
    }
}
