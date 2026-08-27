<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Http\Request;

/**
 * Resuelve el "negocio activo" del usuario en la sesión actual (doc §8):
 * es contexto de sesión, no un filtro opcional. Si el guardado en sesión ya
 * no es accesible (vigencia vencida, negocio desactivado, etc.), cae al
 * primer negocio accesible y lo recuerda.
 */
class NegocioActivoResolver
{
    public function __construct(private AccessScheduler $scheduler) {}

    public function resolver(Request $request): ?Business
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $negocios = $this->scheduler->businessesActiveFor($user);

        if ($negocios->isEmpty()) {
            $request->session()->forget('negocio_activo_id');

            return null;
        }

        $activoId = $request->session()->get('negocio_activo_id');
        $activo = $negocios->firstWhere('id', $activoId);

        if (! $activo) {
            $activo = $negocios->first();
            $request->session()->put('negocio_activo_id', $activo->id);
        }

        return $activo;
    }

    public function cambiar(Request $request, Business $negocio): bool
    {
        $user = $request->user();

        if (! $user || ! $this->scheduler->businessesActiveFor($user)->contains('id', $negocio->id)) {
            return false;
        }

        $request->session()->put('negocio_activo_id', $negocio->id);

        return true;
    }
}
