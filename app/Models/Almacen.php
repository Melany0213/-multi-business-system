<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Almacen extends Model
{
    use LogsActivity;

    protected $table = 'almacenes';

    protected $fillable = [
        'business_id',
        'nombre',
        'estado',
    ];

    protected $attributes = [
        'estado' => 'activo',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'almacen_producto')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }

    protected function activityAccountId(): ?int
    {
        return $this->business?->account_id;
    }

    protected function activityBusinessId(): ?int
    {
        return $this->business_id;
    }

    protected function activityDescription(string $action): string
    {
        return "Almacén \"{$this->nombre}\" ".$this->activityVerbo($action);
    }
}
