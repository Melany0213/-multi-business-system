<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Venta extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid',
        'turno_id',
        'monto_total',
        'metodo_pago',
        'estado',
        'fecha_hora',
    ];

    protected $attributes = [
        'estado' => 'pagado',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Venta $venta) {
            $venta->uuid ??= (string) Str::uuid();
            $venta->fecha_hora ??= now();
        });
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function isAnulable(): bool
    {
        return $this->estado === 'pagado' && $this->turno->isAbierto();
    }

    /**
     * Anula la venta y repone el stock vendido en el almacén del turno. Solo
     * tiene sentido mientras el turno sigue abierto — una vez cerrado, sus
     * totales (venta/utilidad/salario) ya quedaron fijos en Turno::cerrar()
     * y no se recalculan retroactivamente.
     */
    public function anular(): void
    {
        $almacen = $this->turno->almacen;

        $this->detalles->each(function (VentaDetalle $detalle) use ($almacen) {
            $pivot = $almacen->productos()->where('producto_id', $detalle->producto_id)->first()?->pivot;

            $almacen->productos()->syncWithoutDetaching([
                $detalle->producto_id => ['cantidad' => ($pivot->cantidad ?? 0) + $detalle->cantidad],
            ]);
        });

        $this->update(['estado' => 'anulado']);
    }

    protected function activityAccountId(): ?int
    {
        return $this->turno?->business?->account_id;
    }

    protected function activityBusinessId(): ?int
    {
        return $this->turno?->business_id;
    }

    protected function activityDescription(string $action): string
    {
        return "Venta #{$this->getKey()} (\${$this->monto_total}) ".$this->activityVerbo($action);
    }
}
