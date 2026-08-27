<?php

namespace App\Services;

use App\Models\Access;
use App\Models\Business;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Resuelve, para un usuario y un instante dado, a qué negocios tiene acceso
 * vigente y con qué rol. Desacoplado de la autenticación: la sesión solo
 * necesita preguntarle a este servicio "¿qué ve este usuario ahora mismo?".
 */
class AccessScheduler
{
    /**
     * Accesos del usuario que están vigentes en este instante (estado activo,
     * dentro de su ventana de tiempo, y con negocio/cuenta también activos).
     *
     * @return Collection<int, Access>
     */
    public function activeAccessesFor(User $user, ?CarbonInterface $at = null): Collection
    {
        $at ??= Carbon::now();

        if (! $user->isActive()) {
            return collect();
        }

        return $user->accesses()
            ->with(['business.account', 'business.rubro', 'role'])
            ->where('estado', 'activo')
            ->orderBy('id')
            ->get()
            ->filter(fn (Access $access) => $this->businessUsable($access->business)
                && $this->isVigente($access, $at));
    }

    /**
     * Negocios seleccionables por el usuario ahora mismo.
     *
     * @return Collection<int, Business>
     */
    public function businessesActiveFor(User $user, ?CarbonInterface $at = null): Collection
    {
        return $this->activeAccessesFor($user, $at)
            ->map(fn (Access $access) => $access->business)
            ->unique('id')
            ->values();
    }

    /**
     * ¿Tiene el usuario, en este negocio, el permiso indicado, ahora mismo?
     * Esta es "la regla de oro" de autorización del sistema (ver doc §5).
     */
    public function hasPermission(User $user, Business $business, string $permission, ?CarbonInterface $at = null): bool
    {
        return $this->activeAccessesFor($user, $at)
            ->where('business_id', $business->id)
            ->contains(fn (Access $access) => $access->role->hasPermissionTo($permission));
    }

    protected function businessUsable(Business $business): bool
    {
        return $business->isActive() && ! $business->account->isSuspended();
    }

    protected function isVigente(Access $access, CarbonInterface $at): bool
    {
        return match ($access->tipo_vigencia) {
            'fija' => $this->dentroDeRangoDeFechas($access, $at) && $this->dentroDeVentanaHoraria($access, $at),
            'diaria', 'mensual' => $this->dentroDeRangoDeFechas($access, $at) && $this->dentroDeVentanaHoraria($access, $at),
            'semanal' => $this->diaDeSemanaPermitido($access, $at)
                && $this->dentroDeRangoDeFechas($access, $at)
                && $this->dentroDeVentanaHoraria($access, $at),
            default => false,
        };
    }

    protected function dentroDeRangoDeFechas(Access $access, CarbonInterface $at): bool
    {
        if ($access->fecha_inicio && $at->toDateString() < $access->fecha_inicio->toDateString()) {
            return false;
        }

        if ($access->fecha_fin && $at->toDateString() > $access->fecha_fin->toDateString()) {
            return false;
        }

        return true;
    }

    protected function diaDeSemanaPermitido(Access $access, CarbonInterface $at): bool
    {
        if (empty($access->dias_semana)) {
            return true;
        }

        return in_array($at->dayOfWeek, $access->dias_semana, true);
    }

    protected function dentroDeVentanaHoraria(Access $access, CarbonInterface $at): bool
    {
        if (! $access->hora_inicio || ! $access->hora_fin) {
            return true;
        }

        $hora = $at->format('H:i:s');

        return $hora >= $access->hora_inicio && $hora <= $access->hora_fin;
    }
}
