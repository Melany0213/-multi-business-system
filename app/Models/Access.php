<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class Access extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'role_id',
        'tipo_vigencia',
        'dias_semana',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'estado',
        'created_by',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    protected $attributes = [
        'estado' => 'activo',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
