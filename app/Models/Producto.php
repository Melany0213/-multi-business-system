<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Producto extends Model
{
    use LogsActivity;

    protected $fillable = [
        'account_id',
        'nombre',
        'sku',
        'categoria',
        'imagen_path',
        'estado',
    ];

    protected $attributes = [
        'estado' => 'activo',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Negocios que ofrecen este producto (con su precio/costo propios).
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_producto')
            ->withPivot('precio', 'costo', 'estado')
            ->withTimestamps();
    }

    public function almacenes(): BelongsToMany
    {
        return $this->belongsToMany(Almacen::class, 'almacen_producto')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    /**
     * Margen de ganancia en dinero para un precio/costo dados (son por
     * negocio, no columnas propias del producto — ver business_producto).
     */
    public static function margenGanancia(float $precio, float $costo): float
    {
        return round($precio - $costo, 2);
    }

    public static function margenPorcentaje(float $precio, float $costo): ?float
    {
        if ($costo <= 0) {
            return null;
        }

        return round((($precio - $costo) / $costo) * 100, 2);
    }

    protected function activityAccountId(): ?int
    {
        return $this->account_id;
    }

    protected function activityDescription(string $action): string
    {
        return "Producto \"{$this->nombre}\" ".$this->activityVerbo($action);
    }
}
