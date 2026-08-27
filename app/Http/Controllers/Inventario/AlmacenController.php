<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventario\Concerns\RequiereNegocioActivo;
use App\Models\Almacen;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlmacenController extends Controller
{
    use RequiereNegocioActivo;

    public function index(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.ver');

        return Inertia::render('Inventario/Almacenes/Index', [
            'almacenes' => $negocio->almacenes()->withCount('productos')->get(),
        ]);
    }

    public function create(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');

        return Inertia::render('Inventario/Almacenes/Create');
    }

    public function store(Request $request, NegocioActivoResolver $resolver): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $negocio->almacenes()->create($data);

        return redirect()->route('almacenes.index');
    }

    public function edit(Request $request, NegocioActivoResolver $resolver, Almacen $almacen): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');
        $this->autorizarAlmacen($almacen, $negocio->id);

        return Inertia::render('Inventario/Almacenes/Edit', [
            'almacen' => $almacen,
        ]);
    }

    public function update(Request $request, NegocioActivoResolver $resolver, Almacen $almacen): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');
        $this->autorizarAlmacen($almacen, $negocio->id);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $almacen->update($data);

        return redirect()->route('almacenes.index');
    }

    public function toggleEstado(Request $request, NegocioActivoResolver $resolver, Almacen $almacen): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');
        $this->autorizarAlmacen($almacen, $negocio->id);

        $almacen->update(['estado' => $almacen->isActivo() ? 'inactivo' : 'activo']);

        return back();
    }

    private function autorizarAlmacen(Almacen $almacen, int $negocioId): void
    {
        abort_unless($almacen->business_id === $negocioId, 403);
    }
}
