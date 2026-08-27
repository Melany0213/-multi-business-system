<?php

namespace App\Http\Controllers\Inventario\Concerns;

use App\Models\Business;
use App\Services\AccessScheduler;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\Request;

trait RequiereNegocioActivo
{
    private function negocioActivoOrFail(Request $request, NegocioActivoResolver $resolver): Business
    {
        $negocio = $resolver->resolver($request);

        abort_unless($negocio, 403, 'No tienes acceso vigente a ningún negocio en este momento.');

        return $negocio;
    }

    private function autorizarPermiso(Request $request, Business $negocio, string $permiso): void
    {
        $tiene = app(AccessScheduler::class)->hasPermission($request->user(), $negocio, $permiso);

        abort_unless($tiene, 403);
    }
}
