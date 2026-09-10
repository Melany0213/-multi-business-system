<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class Access extends Model
{
    use LogsActivity;

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
        return match ($action) {
            'created' => 'acceso.otorgado',
            'deleted' => 'acceso.revocado',
            default => 'acceso.'.$this->activityParticipio($action),
        };
    }

    protected function activityDescription(string $action): string
    {
        $quien = "\"{$this->user?->name}\" en \"{$this->business?->nombre}\"";

        return match ($this->activityEvento($action)) {
            'acceso.otorgado' => "Acceso otorgado a {$quien} con rol \"{$this->role?->name}\"",
            'acceso.revocado' => "Acceso revocado a {$quien}",
            default => "Acceso de {$quien} ".$this->activityVerbo($action),
        };
    }
}
