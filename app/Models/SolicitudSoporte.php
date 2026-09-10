<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido de revisión o corrección que el DUEÑO le hace al soporte (RF-58).
 *
 * Es la pieza que cierra el ciclo completo: la intervención que nace de una
 * solicitud hereda su motivo del pedido firmado por el dueño (RF-59), y el
 * ciclo no lo cierra el Super Admin sino el dueño, dando conformidad o
 * reabriendo (RF-60). Eso convierte el acta en un documento bilateral y no
 * en un informe del soporte sobre sí mismo.
 */
class SolicitudSoporte extends Model
{
    use LogsActivity;

    protected $table = 'solicitudes_soporte';

    protected $fillable = [
        'business_id',
        'solicitante_id',
        'tipo',
        'descripcion',
        'referencia_tipo',
        'referencia_id',
        'estado',
        'respuesta',
        'conformidad',
        'conformidad_at',
    ];

    protected $casts = [
        'conformidad_at' => 'datetime',
    ];

    protected $attributes = [
        'estado' => 'abierta',
    ];

    public const TIPOS = [
        'revision' => 'Revisar algo que no entiendo',
        'correccion' => 'Corregir algo que está mal',
    ];

    public const ESTADOS = ['abierta', 'en_revision', 'resuelta', 'rechazada'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function intervenciones(): HasMany
    {
        return $this->hasMany(Intervencion::class, 'solicitud_id');
    }

    /**
     * Una solicitud sigue en juego mientras el soporte no la haya cerrado.
     */
    public function estaAbierta(): bool
    {
        return in_array($this->estado, ['abierta', 'en_revision'], true);
    }

    /**
     * Resuelta por el soporte, pero el dueño todavía no dijo si le sirvió.
     * El ciclo no está cerrado hasta que él responda (RF-60).
     */
    public function esperaConformidad(): bool
    {
        return $this->estado === 'resuelta' && $this->conformidad === null;
    }

    protected function activityAccountId(): ?int
    {
        return $this->business?->account_id;
    }

    protected function activityBusinessId(): ?int
    {
        return $this->business_id;
    }

    protected function activityEvento(string $action): string
    {
        if ($action === 'created') {
            return 'solicitud.creada';
        }

        if ($action === 'updated' && $this->wasChanged('conformidad')) {
            return $this->conformidad === 'conforme' ? 'solicitud.conforme' : 'solicitud.reabierta';
        }

        if ($action === 'updated' && $this->wasChanged('estado')) {
            return 'solicitud.'.$this->estado;
        }

        return 'solicitud.'.$this->activityVerbo($action);
    }

    protected function activityDescription(string $action): string
    {
        return match ($this->activityEvento($action)) {
            'solicitud.creada' => "Solicitud de soporte #{$this->getKey()} abierta por el dueño: {$this->descripcion}",
            'solicitud.conforme' => "El dueño dio conformidad a la solicitud #{$this->getKey()}",
            'solicitud.reabierta' => "El dueño reabrió la solicitud #{$this->getKey()}",
            'solicitud.resuelta' => "Solicitud #{$this->getKey()} marcada como resuelta por el soporte",
            'solicitud.rechazada' => "Solicitud #{$this->getKey()} rechazada por el soporte",
            'solicitud.en_revision' => "Solicitud #{$this->getKey()} tomada por el soporte",
            default => "Solicitud de soporte #{$this->getKey()} ".$this->activityVerbo($action),
        };
    }
}
