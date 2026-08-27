<?php

namespace App\Http\Controllers\Dueno;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Business;
use App\Models\Rubro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NegocioController extends Controller
{
    public function index(Request $request): Response
    {
        $cuenta = $this->cuentaDelDueno($request);

        return Inertia::render('Negocios/Index', [
            'negocios' => $cuenta->businesses()->with('rubro')->withCount('almacenes')->get(),
            'puedeCrearNegocio' => $cuenta->puedeCrearNegocio(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->cuentaDelDueno($request);

        return Inertia::render('Negocios/Create', [
            'rubros' => Rubro::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cuenta = $this->cuentaDelDueno($request);

        if (! $cuenta->puedeCrearNegocio()) {
            throw ValidationException::withMessages([
                'nombre' => 'Alcanzaste el límite de negocios de tu plan actual.',
            ]);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'rubro_id' => ['nullable', 'exists:rubros,id'],
            'tipo' => ['required', 'in:productos,servicios,mixto'],
            'moneda' => ['required', 'string', 'max:10'],
        ]);

        $negocio = $cuenta->businesses()->create($data);
        $negocio->almacenes()->create(['nombre' => 'Almacén principal']);

        return redirect()->route('negocios.index');
    }

    public function edit(Request $request, Business $negocio): Response
    {
        $this->autorizarNegocio($request, $negocio);

        return Inertia::render('Negocios/Edit', [
            'negocio' => $negocio,
            'rubros' => Rubro::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Business $negocio): RedirectResponse
    {
        $this->autorizarNegocio($request, $negocio);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'rubro_id' => ['nullable', 'exists:rubros,id'],
            'tipo' => ['required', 'in:productos,servicios,mixto'],
            'moneda' => ['required', 'string', 'max:10'],
        ]);

        $negocio->update($data);

        return redirect()->route('negocios.index');
    }

    public function toggleEstado(Request $request, Business $negocio): RedirectResponse
    {
        $this->autorizarNegocio($request, $negocio);

        $negocio->update(['estado' => $negocio->isActive() ? 'inactivo' : 'activo']);

        return back();
    }

    private function cuentaDelDueno(Request $request): Account
    {
        $cuenta = Account::where('owner_user_id', $request->user()->id)->first();

        abort_unless($cuenta, 403, 'No eres dueño de ninguna cuenta.');

        return $cuenta;
    }

    private function autorizarNegocio(Request $request, Business $negocio): void
    {
        $cuenta = $this->cuentaDelDueno($request);

        abort_unless($negocio->account_id === $cuenta->id, 403);
    }
}
