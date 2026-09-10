<?php

namespace App\Models\Concerns;

use App\Services\Bitacora;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Deja una entrada en el LIBRO DE MOVIMIENTOS cada vez que el modelo se crea,
 * actualiza o elimina.
 *
 * Este rastro automático es la RED DE SEGURIDAD del libro: atrapa hasta lo
 * que nadie se acordó de nombrar. Encima de él, cada modelo puede darle a sus
 * movimientos un nombre propio sobreescribiendo `activityEvento()` — la
 * diferencia entre "Turno #14 actualizado", que obliga a abrir el módulo, y
 * "Turno #14 cerrado", que se entiende leyéndolo (RF-48).
 *
 * Lo que NO pasa por un modelo Eloquent (los cambios sobre tablas pivote:
 * stock por almacén, precio y costo por negocio) no llega acá y hay que
 * registrarlo a mano con `Bitacora::registrar()` — ver RF-49.
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

        app(Bitacora::class)->registrar(
            $this->activityEvento($action),
            $this->activityDescription($action),
            [
                'account_id' => $accountId,
                'business_id' => $businessId,
                'sujeto' => $this,
                'action' => $action,
                'cambios' => $action === 'updated' ? $this->activityChanges() : null,
            ],
        );
    }

    /**
     * Nombre del movimiento. Por defecto, "<modelo>.<participio>"
     * (producto.creado, almacen.eliminado); los modelos cuyos cambios de
     * estado significan algo para el negocio lo sobreescriben para nombrar
     * el hecho real: turno.abierto, traspaso.autorizado, venta.anulada.
     */
    protected function activityEvento(string $action): string
    {
        return Str::snake(class_basename($this)).'.'.$this->activityParticipio($action);
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

    protected function activityParticipio(string $action): string
    {
        return match ($action) {
            'created' => 'creado',
            'updated' => 'actualizado',
            'deleted' => 'eliminado',
            default => $action,
        };
    }
}
