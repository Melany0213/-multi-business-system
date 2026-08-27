<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class Business extends Model
{
    protected $fillable = [
        'account_id',
        'rubro_id',
        'nombre',
        'tipo',
        'estado',
        'moneda',
        'zona_horaria',
        'configuracion',
        'logo_path',
        'color_primario',
        'color_secundario',
    ];

    protected $casts = [
        'configuracion' => 'array',
    ];

    protected $attributes = [
        'estado' => 'activo',
        'moneda' => 'USD',
        'zona_horaria' => 'UTC',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(Access::class);
    }

    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class);
    }

    /**
     * Productos del catálogo de la cuenta que este negocio ofrece, con su
     * propio precio/costo (ver [[modelo-datos-multinegocio]] — catálogo
     * compartido por cuenta, precio/costo y stock por negocio/almacén).
     */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'business_producto')
            ->withPivot('precio', 'costo', 'estado')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->estado === 'activo';
    }

    protected static function booted(): void
    {
        // El dueño de la cuenta siempre tiene acceso a cada negocio que crea,
        // con el rol de mayor privilegio — sin esto, no vería sus propios
        // negocios en "Mis negocios" (el dueño no es un caso especial en el
        // sistema de Accesos, es simplemente el primer Acceso que se crea).
        static::created(function (Business $business) {
            $role = Role::where('name', 'super_admin_negocio')->where('guard_name', 'web')->first();

            if (! $role) {
                return;
            }

            Access::create([
                'user_id' => $business->account->owner_user_id,
                'business_id' => $business->id,
                'role_id' => $role->id,
                'tipo_vigencia' => 'fija',
                'estado' => 'activo',
                'created_by' => $business->account->owner_user_id,
            ]);
        });
    }
}
