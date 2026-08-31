<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'primer_apellido', 'segundo_apellido', 'username', 'email', 'telefono', 'carnet_identidad', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, LogsActivity, Notifiable;

    /**
     * Defaults también a nivel de modelo (no solo en la migración): Eloquent
     * no rehidrata los defaults de columna de la BD en la instancia recién
     * creada, así que sin esto `isActive()` vería null justo tras crear.
     */
    protected $attributes = [
        'estado_global' => 'activo',
        'is_super_admin_sistema' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin_sistema' => 'boolean',
        ];
    }

    /**
     * Cuentas (SaaS) que este usuario posee como Super Admin del Negocio.
     */
    public function ownedAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'owner_user_id');
    }

    /**
     * Accesos (Usuario-Negocio-Rol con vigencia) de este usuario.
     */
    public function accesses(): HasMany
    {
        return $this->hasMany(Access::class);
    }

    public function isActive(): bool
    {
        return $this->estado_global === 'activo';
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->name} {$this->primer_apellido} {$this->segundo_apellido}");
    }

    protected function activityDescription(string $action): string
    {
        return "Usuario \"{$this->username}\" ".$this->activityVerbo($action);
    }
}
