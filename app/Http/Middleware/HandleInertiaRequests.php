<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Services\AccessScheduler;
use App\Services\Intervenciones;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private AccessScheduler $scheduler,
        private NegocioActivoResolver $negocioActivoResolver,
        private Intervenciones $intervenciones,
    ) {}

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [
                ...parent::share($request),
                'auth' => ['user' => null, 'esDueno' => false],
                'misNegocios' => [],
                'negocioActivo' => null,
                'intervenciones' => [],
            ];
        }

        $negocios = $this->scheduler->businessesActiveFor($user);
        $negocioActivo = $this->negocioActivoResolver->resolver($request);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'esDueno' => Account::where('owner_user_id', $user->id)->exists(),
            ],
            'misNegocios' => $negocios->map(fn ($n) => ['id' => $n->id, 'nombre' => $n->nombre])->values(),
            // Aviso permanente de intervencion (RF-54): tiene que estar en
            // TODAS las pantallas, no solo en las del modulo de soporte. La
            // diferencia entre "estoy mirando" y "estoy tocando produccion
            // ajena" no puede depender de acordarse.
            'intervenciones' => $this->intervenciones
                ->negociosIntervenidosPor($user)
                ->map(function ($negocio) use ($user) {
                    $intervencion = $this->intervenciones->vigentePara($user, $negocio->id);

                    return [
                        'id' => $intervencion?->id,
                        'negocio' => $negocio->nombre,
                        'negocio_id' => $negocio->id,
                        'motivo' => $intervencion?->motivo,
                        'minutos_restantes' => $intervencion?->minutosRestantes(),
                    ];
                })->values(),
            'negocioActivo' => $negocioActivo ? [
                'id' => $negocioActivo->id,
                'nombre' => $negocioActivo->nombre,
                'puedeConfigurarSalario' => $negocioActivo->puedeConfigurarSalario($user, $this->scheduler),
                'puedeVerReportes' => $this->scheduler->hasPermission($user, $negocioActivo, 'reportes.ver'),
            ] : null,
        ];
    }
}
