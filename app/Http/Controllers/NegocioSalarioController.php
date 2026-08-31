<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\AccessScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Configuración del salario del cajero por turno: siempre editable por el
 * dueño de la cuenta; el administrador solo si el dueño se lo delegó (ver
 * Business::puedeConfigurarSalario()). No es un CRUD del "negocio activo"
 * porque el dueño necesita poder ajustar cualquiera de sus negocios, esté o
 * no seleccionado como activo en este momento.
 */
class NegocioSalarioController extends Controller
{
    public function edit(Request $request, Business $negocio, AccessScheduler $scheduler): Response
    {
        $puedeDelegar = $this->autorizar($request, $negocio, $scheduler);

        return Inertia::render('Negocios/Salario/Edit', [
            'negocio' => $negocio,
            'basesPorcentaje' => Business::BASES_PORCENTAJE_SALARIO,
            'puedeDelegar' => $puedeDelegar,
        ]);
    }

    public function update(Request $request, Business $negocio, AccessScheduler $scheduler): RedirectResponse
    {
        $puedeDelegar = $this->autorizar($request, $negocio, $scheduler);

        $data = $request->validate([
            'salario_monto_fijo' => ['required', 'numeric', 'min:0'],
            'salario_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'salario_base_porcentaje' => ['required', 'in:'.implode(',', array_keys(Business::BASES_PORCENTAJE_SALARIO))],
            'admin_puede_configurar_salario' => ['sometimes', 'boolean'],
        ]);

        // Solo quien ya tiene permiso "de dueño" puede decidir si delega el
        // control al administrador — el administrador delegado no puede
        // ampliarse el permiso a sí mismo.
        if (! $puedeDelegar) {
            unset($data['admin_puede_configurar_salario']);
        }

        $negocio->update($data);

        return redirect()->route('negocios.salario.edit', $negocio);
    }

    /**
     * @return bool si además puede tocar la delegación misma (es dueño/super admin)
     */
    private function autorizar(Request $request, Business $negocio, AccessScheduler $scheduler): bool
    {
        $user = $request->user();
        $esDuenoOAdmin = $user->is_super_admin_sistema || $negocio->account->owner_user_id === $user->id;

        abort_unless($negocio->puedeConfigurarSalario($user, $scheduler), 403);

        return $esDuenoOAdmin;
    }
}
