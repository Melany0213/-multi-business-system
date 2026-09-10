<?php

namespace App\Models;

use App\Exceptions\BitacoraInmutableException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una entrada del LIBRO DE MOVIMIENTOS. Un solo almacén con dos lecturas:
 * el dueño lo lee como la historia de su negocio ("se abrió turno", "subió
 * el precio del café") y el soporte como traza técnica (qué fila cambió,
 * valor anterior y posterior). Dos tablas separadas terminarían divergiendo
 * y entonces ninguna serviría como prueba.
 *
 * Es de SOLO ESCRITURA (RF-50): no hay forma de editar ni borrar una entrada,
 * tampoco para el Super Admin del Sistema. Ver `booted()`.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'causer_id',
        'account_id',
        'business_id',
        'subject_type',
        'subject_id',
        'evento',
        'action',
        'description',
        'changes',
        'intervencion_id',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    protected static function booted(): void
    {
        // Append-only. Bloquearlo en el modelo (y no solo quitando las rutas)
        // hace que ni siquiera un `->update()` escrito por descuido más
        // adelante pueda alterar el libro: falla ruidosamente.
        static::updating(fn () => throw new BitacoraInmutableException(
            'El libro de movimientos es de solo escritura: una entrada no puede modificarse.'
        ));

        static::deleting(fn () => throw new BitacoraInmutableException(
            'El libro de movimientos es de solo escritura: una entrada no puede eliminarse.'
        ));

        static::creating(function (ActivityLog $log) {
            // `creating` corre ANTES de que Eloquent ponga los timestamps, y
            // la fecha entra en el hash: hay que fijarla acá o la cadena se
            // calcularía sobre un created_at nulo.
            $log->created_at ??= now();
            $log->updated_at ??= $log->created_at;

            $log->hash_previo = static::query()->orderByDesc('id')->value('hash');
            $log->hash = $log->calcularHash();
        });
    }

    /**
     * Huella de esta entrada, encadenada con la anterior. Cambiar cualquier
     * campo de una fila vieja hace que su hash deje de coincidir, y como cada
     * entrada posterior incorpora el hash de la previa, para falsificarla
     * habría que reescribir todo el libro desde ahí hacia adelante.
     */
    public function calcularHash(): string
    {
        $payload = json_encode([
            'causer_id' => $this->causer_id,
            'account_id' => $this->account_id,
            'business_id' => $this->business_id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'evento' => $this->evento,
            'action' => $this->action,
            'description' => $this->description,
            'changes' => $this->changes,
            'intervencion_id' => $this->intervencion_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE);

        return hash('sha256', ($this->hash_previo ?? '').'|'.$payload);
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function intervencion(): BelongsTo
    {
        return $this->belongsTo(Intervencion::class);
    }

    /**
     * Movimientos hechos durante una intervención de soporte — la materia
     * prima con la que el acta se escribe sola (RF-57).
     */
    public function scopeDeIntervencion(Builder $query, int $intervencionId): Builder
    {
        return $query->where('intervencion_id', $intervencionId);
    }
}
