<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'max_negocios',
        'max_usuarios',
        'precio_mensual',
        'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    protected $casts = [
        'activo' => 'boolean',
        'precio_mensual' => 'decimal:2',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
