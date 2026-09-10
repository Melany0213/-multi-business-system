<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Única puerta de entrada al LIBRO DE MOVIMIENTOS. Todo lo que pasa en un
 * negocio entra por acá: lo que se registra solo (los modelos, vía
 * LogsActivity) y lo que hay que registrar a mano porque no pasa por un
 * modelo — los cambios sobre tablas pivote, que Eloquent no observa.
 *
 * Registrar un movimiento nunca debe poder tumbar la operación: si el libro
 * fallara, el cajero no puede quedarse sin poder vender. Por eso los eventos
 * se nombran, pero no se validan contra un catálogo cerrado.
 */
class Bitacora
{
    public function __construct(private Intervenciones $intervenciones) {}

    /**
     * Registra un movimiento con nombre propio (RF-48).
     *
     * `$evento` es lo que hace legible el libro: "turno.abierto" o
     * "precio.cambiado" se entienden solos, mientras que el genérico
     * "actualizado" obliga a abrir el módulo para saber qué pasó.
     */
    public function registrar(string $evento, string $descripcion, array $contexto = []): ActivityLog
    {
        $negocio = $contexto['negocio'] ?? null;
        $sujeto = $contexto['sujeto'] ?? null;

        $businessId = $negocio instanceof Business ? $negocio->id : ($contexto['business_id'] ?? null);
        $accountId = $negocio instanceof Business ? $negocio->account_id : ($contexto['account_id'] ?? null);

        return ActivityLog::create([
            'causer_id' => $contexto['causer_id'] ?? Auth::id(),
            'account_id' => $accountId,
            'business_id' => $businessId,
            'subject_type' => $sujeto instanceof Model ? $sujeto::class : ($contexto['subject_type'] ?? null),
            'subject_id' => $sujeto instanceof Model ? $sujeto->getKey() : ($contexto['subject_id'] ?? null),
            'evento' => $evento,
            'action' => $contexto['action'] ?? $this->accionDesde($evento),
            'description' => $descripcion,
            'changes' => $contexto['cambios'] ?? null,
            // Si el movimiento ocurre al amparo de una intervención de
            // soporte, queda ligado a ella: eso es lo que después permite que
            // el acta se escriba sola (RF-57).
            'intervencion_id' => $contexto['intervencion_id']
                ?? $this->intervenciones->idVigentePara(Auth::user(), $businessId),
        ]);
    }

    /**
     * Registra un cambio de valor sobre algo que NO es un modelo Eloquent
     * — típicamente una tabla pivote, que es justamente donde el sistema
     * guardaba sin traza sus datos más sensibles: el stock por almacén y el
     * precio/costo por negocio (RF-49).
     *
     * Devuelve null si no cambió nada: un "guardar" que dejó todo igual no
     * es un movimiento, y ensuciar el libro con esas entradas lo vuelve
     * ilegible justo cuando hay que leerlo con urgencia.
     */
    public function registrarCambioDeValores(
        string $evento,
        string $descripcion,
        array $antes,
        array $despues,
        array $contexto = []
    ): ?ActivityLog {
        $cambios = [];

        foreach ($despues as $campo => $valorNuevo) {
            $valorViejo = $antes[$campo] ?? null;

            if ($this->sonEquivalentes($valorViejo, $valorNuevo)) {
                continue;
            }

            $cambios[$campo] = ['antes' => $valorViejo, 'despues' => $valorNuevo];
        }

        if ($cambios === []) {
            return null;
        }

        return $this->registrar($evento, $descripcion, [...$contexto, 'cambios' => $cambios, 'action' => 'updated']);
    }

    /**
     * Los valores llegan desde formularios y desde columnas decimales, así
     * que "10" y 10.00 son el mismo precio aunque no sean idénticos en PHP.
     */
    private function sonEquivalentes(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.00001;
        }

        return $a === $b;
    }

    private function accionDesde(string $evento): string
    {
        return match (true) {
            str_contains($evento, 'cread') || str_contains($evento, 'abiert') => 'created',
            str_contains($evento, 'elimin') || str_contains($evento, 'borrad') => 'deleted',
            default => 'updated',
        };
    }
}
