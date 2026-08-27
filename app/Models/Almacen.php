<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Almacen extends Model
{
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
}
