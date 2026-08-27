<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubro extends Model
{
    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
