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
