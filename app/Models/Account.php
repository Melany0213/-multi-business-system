<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Account extends Model
{
    use LogsActivity;

    protected $fillable = [
        'owner_user_id',
        'nombre_cliente',
        'rut',
        'telefono',
        'estado',
        'plan_id',
    ];

    protected $attributes = [
        'estado' => 'activa',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * Catálogo de productos compartido por todos los negocios de esta cuenta.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * ¿Puede esta cuenta crear un negocio más según el límite de su plan?
     * Sin plan asignado, o un plan sin límite (max_negocios null), no hay tope.
     */
    public function puedeCrearNegocio(): bool
    {
        $limite = $this->plan?->max_negocios;

        return $limite === null || $this->businesses()->count() < $limite;
    }

    public function isSuspended(): bool
    {
        return $this->estado === 'suspendida';
    }

    /**
     * Suspende la cuenta y, en cascada (lógica, no destructiva), a todos sus
     * usuarios — salvo los que ya estaban bloqueados manualmente. Doc §6.1.
     */
    public function suspend(): void
    {
        $this->update(['estado' => 'suspendida']);

        User::whereIn('id', $this->relatedUserIds())
            ->where('estado_global', '!=', 'bloqueado')
            ->update(['estado_global' => 'suspendido']);
    }

    /**
     * Reactiva la cuenta y solo a los usuarios que quedaron suspendidos por
     * la cascada anterior (los bloqueados manualmente siguen bloqueados).
     */
    public function reactivate(): void
    {
        $this->update(['estado' => 'activa']);

        User::whereIn('id', $this->relatedUserIds())
            ->where('estado_global', 'suspendido')
            ->update(['estado_global' => 'activo']);
    }

    /**
     * El dueño de la cuenta más todos los usuarios con algún Acceso a
     * alguno de sus negocios.
     */
    public function relatedUserIds(): Collection
    {
        $businessIds = $this->businesses()->pluck('id');

        return Access::whereIn('business_id', $businessIds)
            ->pluck('user_id')
            ->push($this->owner_user_id)
            ->unique()
            ->values();
    }

    protected function activityAccountId(): ?int
    {
        return $this->id;
    }

    protected function activitySelfReferences(): array
    {
        return ['account_id'];
    }

    protected function activityDescription(string $action): string
    {
        return "Cuenta \"{$this->nombre_cliente}\" ".$this->activityVerbo($action);
    }
}
