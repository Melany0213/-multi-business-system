<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventario\Concerns\RequiereNegocioActivo;
use App\Models\Business;
use App\Models\Turno;
use App\Services\AccessScheduler;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TurnoController extends Controller
{
    use RequiereNegocioActivo;

    public function index(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'caja.abrir');

        $turnoAbierto = Turno::where('business_id', $negocio->id)
            ->where('cajero_id', $request->user()->id)
            ->where('estado', 'abierto')
            ->with('almacen')
            ->first();

        $historial = Turno::where('business_id', $negocio->id)
            ->where('estado', 'cerrado')
            ->with(['cajero', 'almacen'])
            ->latest('fecha_cierre')
            ->limit(20)
            ->get();

        return Inertia::render('Ventas/Turnos/Index', [
            'turnoAbierto' => $turnoAbierto,
            'historial' => $historial,
            'almacenes' => $negocio->almacenes()->where('estado', 'activo')->get(['id', 'nombre']),
        ]);
    }

    public function create(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'caja.abrir');

        return Inertia::render('Ventas/Turnos/Create', [
            'almacenes' => $negocio->almacenes()->where('estado', 'activo')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request, NegocioActivoResolver $resolver): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'caja.abrir');

        $yaTieneAbierto = Turno::where('business_id', $negocio->id)
            ->where('cajero_id', $request->user()->id)
            ->where('estado', 'abierto')
            ->exists();

        if ($yaTieneAbierto) {
            throw ValidationException::withMessages([
                'dispositivo' => 'Ya tenés un turno abierto en este negocio. Cerralo antes de abrir otro.',
            ]);
        }

        $data = $request->validate([
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'dispositivo' => ['required', 'string', 'max:255'],
            'tasa_usd' => ['nullable', 'numeric', 'min:0'],
            'tasa_eur' => ['nullable', 'numeric', 'min:0'],
        ]);

        abort_unless(
            $negocio->almacenes()->where('id', $data['almacen_id'])->exists(),
            403,
        );

        $turno = Turno::create([
            ...$data,
            'business_id' => $negocio->id,
            'cajero_id' => $request->user()->id,
            'fecha_apertura' => now(),
        ]);

        return redirect()->route('turnos.show', $turno);
    }

    public function show(Request $request, Turno $turno): Response
    {
        $negocio = $this->autorizarTurno($request, $turno, 'caja.abrir');

        $turno->load(['almacen', 'cajero', 'ventas.detalles.producto']);

        return Inertia::render('Ventas/Turnos/Show', [
            'turno' => $turno,
            'esPropio' => $turno->cajero_id === $request->user()->id,
            'puedeVender' => app(AccessScheduler::class)->hasPermission($request->user(), $negocio, 'ventas.crear'),
            'puedeAnular' => app(AccessScheduler::class)->hasPermission($request->user(), $negocio, 'ventas.anular'),
        ]);
    }

    public function cerrar(Request $request, Turno $turno): RedirectResponse
    {
        $this->autorizarTurno($request, $turno, 'caja.cerrar');

        abort_unless($turno->isAbierto(), 422, 'Este turno ya está cerrado.');

        $data = $request->validate([
            'conteo.locales' => ['nullable', 'array'],
            'conteo.locales.*' => ['nullable', 'integer', 'min:0'],
            'conteo.usd' => ['nullable', 'integer', 'min:0'],
            'conteo.eur' => ['nullable', 'integer', 'min:0'],
        ]);

        $turno->cerrar($data['conteo'] ?? []);

        return redirect()->route('turnos.show', $turno);
    }

    /**
     * El turno debe ser de un negocio al que el usuario tiene acceso vigente
     * con el permiso indicado, y solo el propio cajero puede cerrarlo (salvo
     * que además pueda gestionar usuarios, típico del administrador/dueño).
     */
    private function autorizarTurno(Request $request, Turno $turno, string $permiso): Business
    {
        $negocio = $turno->business;
        $this->autorizarPermiso($request, $negocio, $permiso);

        if ($permiso === 'caja.cerrar' && $turno->cajero_id !== $request->user()->id) {
            abort_unless(app(AccessScheduler::class)->hasPermission($request->user(), $negocio, 'usuarios.gestionar'), 403);
        }

        return $negocio;
    }
}
