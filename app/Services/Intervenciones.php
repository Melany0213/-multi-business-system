<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Intervencion;
use App\Models\SolicitudSoporte;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Abre, resuelve y cierra las intervenciones de soporte (RF-54).
 *
 * Es el servicio del que depende toda la diferencia entre mirar y tocar:
 * AccessScheduler le pregunta a este servicio si el Super Admin tiene
 * habilitado escribir en un negocio ahora mismo.
 */
class Intervenciones
{
    /**
     * La intervención que habilita a este usuario a escribir en este negocio
     * ahora mismo, si existe.
     *
     * Se consulta contra la base en cada llamada a propósito: memorizarla
     * dentro del proceso haría que una intervención recién cerrada siguiera
     * habilitando escrituras hasta el final del request. Es una consulta
     * indexada y la corrección importa más que ahorrarla.
     */
    public function vigentePara(?User $user, ?int $businessId, ?CarbonInterface $at = null): ?Intervencion
    {
        if (! $user || ! $businessId || ! $user->is_super_admin_sistema) {
            return null;
        }

        return Intervencion::query()
            ->vigentes($at)
            ->where('super_admin_id', $user->id)
            ->where('business_id', $businessId)
            ->latest('id')
            ->first();
    }

    public function idVigentePara(?User $user, ?int $businessId, ?CarbonInterface $at = null): ?int
    {
        return $this->vigentePara($user, $businessId, $at)?->id;
    }

    /**
     * Todos los negocios en los que este Super Admin puede escribir ahora.
     * AccessScheduler los suma a "mis negocios" para que las pantallas reales
     * del dueño funcionen bajo su sesión mientras dure la intervención.
     *
     * @return Collection<int, Business>
     */
    public function negociosIntervenidosPor(?User $user, ?CarbonInterface $at = null): Collection
    {
        if (! $user || ! $user->is_super_admin_sistema) {
            return collect();
        }

        return Intervencion::query()
            ->vigentes($at)
            ->where('super_admin_id', $user->id)
            ->with('business')
            ->get()
            ->map(fn (Intervencion $i) => $i->business)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Abre una intervención sobre UN negocio.
     *
     * Si ya hay una vigente para ese negocio se devuelve esa misma, en vez de
     * apilar dos: el alcance es el negocio, no el clic.
     */
    public function abrir(
        User $admin,
        Business $negocio,
        string $motivo,
        string $origen = 'iniciativa',
        ?SolicitudSoporte $solicitud = null,
        ?int $minutos = null,
    ): Intervencion {
        $yaVigente = $this->vigentePara($admin, $negocio->id);

        if ($yaVigente) {
            return $yaVigente;
        }

        $ahora = now();

        return Intervencion::create([
            'super_admin_id' => $admin->id,
            'business_id' => $negocio->id,
            'solicitud_id' => $solicitud?->id,
            'origen' => $origen,
            'motivo' => $motivo,
            'abierta_at' => $ahora,
            'expira_at' => $ahora->copy()->addMinutes($minutos ?? Intervencion::MINUTOS_POR_DEFECTO),
        ]);
    }

    public function cerrar(Intervencion $intervencion): Intervencion
    {
        if ($intervencion->estado === 'abierta') {
            $intervencion->cerrar('cerrada');
        }

        return $intervencion->fresh();
    }

    /**
     * Cierra con su acta las intervenciones cuyo plazo ya pasó.
     *
     * Vencer no depende de esto para dejar de habilitar escrituras — eso lo
     * decide el reloj en `Intervencion::estaVigente()`. Esto solo pone al día
     * la fila y levanta su acta, y por eso puede correr tarde sin abrir un
     * hueco de seguridad.
     */
    public function vencerExpiradas(?CarbonInterface $at = null): int
    {
        $at ??= now();

        $expiradas = Intervencion::query()
            ->where('estado', 'abierta')
            ->where('expira_at', '<=', $at)
            ->get();

        $expiradas->each(fn (Intervencion $i) => $i->cerrar('vencida'));

        return $expiradas->count();
    }
}
