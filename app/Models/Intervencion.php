<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Intervención de soporte: la única llave que le permite al Super Admin del
 * Sistema ESCRIBIR en un negocio ajeno (RF-54). Sin una vigente, su acceso a
 * cualquier negocio es de solo lectura.
 *
 * Nace por uno de dos caminos (RF-59):
 *  - `solicitud`  — el camino normal: el dueño pidió la revisión y su pedido
 *                   ES el motivo. El consentimiento no hay que pedirlo, ya
 *                   está probado en el registro.
 *  - `iniciativa` — la excepción: el soporte detectó algo. Motivo escrito por
 *                   el admin y aviso inmediato al dueño.
 */
class Intervencion extends Model
{
    use LogsActivity;

    protected $table = 'intervenciones';

    protected $fillable = [
        'super_admin_id',
        'business_id',
        'solicitud_id',
        'origen',
        'motivo',
        'abierta_at',
        'expira_at',
        'cerrada_at',
        'estado',
        'acta',
    ];

    protected $casts = [
        'abierta_at' => 'datetime',
        'expira_at' => 'datetime',
        'cerrada_at' => 'datetime',
        'acta' => 'array',
    ];

    protected $attributes = [
        'estado' => 'abierta',
    ];

    /**
     * Cuánto dura una intervención sin tocar nada. El peor escenario real no
     * es el abuso deliberado: es la pestaña olvidada abierta un mes.
     */
    public const MINUTOS_POR_DEFECTO = 30;

    public const ORIGENES = ['solicitud', 'iniciativa'];

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudSoporte::class, 'solicitud_id');
    }

    /**
     * Movimientos del libro hechos al amparo de esta intervención.
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'intervencion_id');
    }

    /**
     * ¿Habilita escribir ahora mismo? Vencer es un hecho del reloj, no un
     * estado que alguien tenga que ir a marcar: una intervención cuyo plazo
     * pasó deja de habilitar aunque su fila siga diciendo "abierta".
     */
    public function estaVigente(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->estado === 'abierta' && $this->expira_at->greaterThan($at);
    }

    public function minutosRestantes(?CarbonInterface $at = null): int
    {
        $at ??= now();

        return $this->estaVigente($at) ? (int) ceil($at->diffInSeconds($this->expira_at) / 60) : 0;
    }

    public function scopeVigentes(Builder $query, ?CarbonInterface $at = null): Builder
    {
        return $query->where('estado', 'abierta')->where('expira_at', '>', $at ?? now());
    }

    /**
     * Cierra la intervención y levanta su acta (RF-57) a partir del libro de
     * movimientos: qué se tocó, con qué valores, cuánto duró y por qué. El
     * acta no se escribe a mano — se deduce de lo que quedó registrado, que
     * es justamente lo que la vuelve difícil de maquillar.
     */
    public function cerrar(string $estadoFinal = 'cerrada'): void
    {
        $movimientos = $this->movimientos()->orderBy('id')->get();
        $cerradaAt = now();

        $this->update([
            'estado' => $estadoFinal,
            'cerrada_at' => $cerradaAt,
            'acta' => [
                'motivo' => $this->motivo,
                'origen' => $this->origen,
                'solicitud_id' => $this->solicitud_id,
                'abierta_at' => $this->abierta_at->toIso8601String(),
                'cerrada_at' => $cerradaAt->toIso8601String(),
                'duracion_minutos' => (int) ceil($this->abierta_at->diffInSeconds($cerradaAt) / 60),
                'total_movimientos' => $movimientos->count(),
                'movimientos' => $movimientos->map(fn (ActivityLog $m) => [
                    'fecha' => $m->created_at->toIso8601String(),
                    'evento' => $m->evento,
                    'descripcion' => $m->description,
                    'cambios' => $m->changes,
                ])->all(),
            ],
        ]);
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
        return match (true) {
            $action === 'created' => 'intervencion.abierta',
            $action === 'updated' && $this->estado === 'cerrada' => 'intervencion.cerrada',
            $action === 'updated' && $this->estado === 'vencida' => 'intervencion.vencida',
            default => 'intervencion.'.$this->activityVerbo($action),
        };
    }

    protected function activityDescription(string $action): string
    {
        return match ($this->activityEvento($action)) {
            'intervencion.abierta' => "Intervención de soporte #{$this->getKey()} abierta: {$this->motivo}",
            'intervencion.cerrada' => "Intervención de soporte #{$this->getKey()} cerrada",
            'intervencion.vencida' => "Intervención de soporte #{$this->getKey()} vencida por tiempo",
            default => "Intervención de soporte #{$this->getKey()} ".$this->activityVerbo($action),
        };
    }
}
