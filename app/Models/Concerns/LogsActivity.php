<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Deja una traza en `activity_logs` cada vez que el modelo se crea, actualiza
 * o elimina — para que el Super Admin del Sistema tenga un registro de los
 * movimientos realizados por cada usuario (ver [[referencia-zeta-pos]]:
 * necesita ver "todo del sistema", incluida esta bitácora).
 *
 * Cada modelo que use este trait puede sobreescribir `activityAccountId()`,
 * `activityBusinessId()` y `activityDescription()` para dar contexto; sin
 * eso, la traza igual se registra, solo que sin cuenta/negocio asociados.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created'));
        static::updated(fn ($model) => $model->recordActivity('updated'));
        static::deleted(fn ($model) => $model->recordActivity('deleted'));
    }

    protected function recordActivity(string $action): void
    {
        $accountId = $this->activityAccountId();
        $businessId = $this->activityBusinessId();

        // En un borrado físico la fila ya no existe en la BD para cuando se
        // guarda esta traza — si el propio modelo ES la cuenta/negocio que
        // se está referenciando (p. ej. Account::delete() referenciándose a
        // sí misma como account_id), hay que omitir esa columna o la FK
        // contra una fila recién eliminada falla.
        if ($action === 'deleted') {
            if (in_array('account_id', $this->activitySelfReferences(), true)) {
                $accountId = null;
            }
            if (in_array('business_id', $this->activitySelfReferences(), true)) {
                $businessId = null;
            }
        }

        ActivityLog::create([
            'causer_id' => Auth::id(),
            'account_id' => $accountId,
            'business_id' => $businessId,
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'action' => $action,
            'description' => $this->activityDescription($action),
            'changes' => $action === 'updated' ? $this->activityChanges() : null,
        ]);
    }

    /**
     * Columnas de activity_logs que, para este modelo, apuntan a sí mismo
     * (ver recordActivity(): se omiten en un 'deleted' para no violar la FK).
     */
    protected function activitySelfReferences(): array
    {
        return [];
    }

    /**
     * Cambios de la actualización recién guardada, sin campos sensibles
     * (password, tokens) ni metadatos de timestamps.
     */
    protected function activityChanges(): ?array
    {
        $oculto = array_merge(['password', 'remember_token', 'updated_at'], $this->activityHidden ?? []);

        $cambios = Arr::except($this->getChanges(), $oculto);

        return $cambios !== [] ? $cambios : null;
    }

    protected function activityAccountId(): ?int
    {
        return null;
    }

    protected function activityBusinessId(): ?int
    {
        return null;
    }

    protected function activityDescription(string $action): string
    {
        return class_basename($this)." #{$this->getKey()} ".$this->activityVerbo($action);
    }

    protected function activityVerbo(string $action): string
    {
        return match ($action) {
            'created' => 'creó',
            'updated' => 'actualizó',
            'deleted' => 'eliminó',
            default => $action,
        };
    }
}
