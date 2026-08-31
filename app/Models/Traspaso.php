<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Traspaso de mercancía entre dos almacenes — pueden ser del mismo negocio o
 * de negocios distintos de la misma cuenta. Flujo de dos pasos (doc): el
 * administrador del negocio ORIGEN autoriza la salida (descuenta stock ahí
 * mismo), y luego alguien del negocio DESTINO confirma la entrada (suma el
 * stock allá). Es, en sí mismo, la traza de auditoría: quién solicitó, quién
 * autorizó, quién confirmó, y cuándo.
 */
class Traspaso extends Model
{
    use LogsActivity;

    protected $fillable = [
        'producto_id',
        'almacen_origen_id',
        'almacen_destino_id',
        'cantidad',
        'estado',
        'solicitado_por',
        'autorizado_por',
        'confirmado_por',
        'fecha_autorizacion',
        'fecha_confirmacion',
        'notas',
    ];

    protected $attributes = [
        'estado' => 'solicitado',
    ];

    protected $casts = [
        'fecha_autorizacion' => 'datetime',
        'fecha_confirmacion' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function isSolicitado(): bool
    {
        return $this->estado === 'solicitado';
    }

    public function isAutorizado(): bool
    {
        return $this->estado === 'autorizado';
    }

    /**
     * Autoriza la salida: descuenta el stock del almacén origen ahora mismo
     * (el producto ya no está disponible ahí, aunque aún no llegó a destino).
     */
    public function autorizar(User $usuario): void
    {
        if (! $this->isSolicitado()) {
            throw new RuntimeException('Solo se puede autorizar un traspaso solicitado.');
        }

        $pivotOrigen = $this->almacenOrigen->productos()->where('producto_id', $this->producto_id)->first()?->pivot;
        $stockActual = $pivotOrigen->cantidad ?? 0;

        if ($stockActual < $this->cantidad) {
            throw new RuntimeException('No hay suficiente stock en el almacén de origen para autorizar este traspaso.');
        }

        $this->almacenOrigen->productos()->updateExistingPivot($this->producto_id, [
            'cantidad' => $stockActual - $this->cantidad,
        ]);

        $this->update([
            'estado' => 'autorizado',
            'autorizado_por' => $usuario->id,
            'fecha_autorizacion' => now(),
        ]);
    }

    public function rechazar(User $usuario): void
    {
        if (! $this->isSolicitado()) {
            throw new RuntimeException('Solo se puede rechazar un traspaso solicitado.');
        }

        $this->update([
            'estado' => 'rechazado',
            'autorizado_por' => $usuario->id,
            'fecha_autorizacion' => now(),
        ]);
    }

    /**
     * Confirma la entrada: suma el stock en el almacén destino. A partir de
     * aquí el traspaso queda completo y es un registro histórico inmutable.
     */
    public function confirmar(User $usuario): void
    {
        if (! $this->isAutorizado()) {
            throw new RuntimeException('Solo se puede confirmar un traspaso ya autorizado.');
        }

        $this->almacenDestino->productos()->syncWithoutDetaching([
            $this->producto_id => [
                'cantidad' => ($this->almacenDestino->productos()->where('producto_id', $this->producto_id)->first()?->pivot->cantidad ?? 0) + $this->cantidad,
            ],
        ]);

        $this->update([
            'estado' => 'completado',
            'confirmado_por' => $usuario->id,
            'fecha_confirmacion' => now(),
        ]);
    }

    protected function activityAccountId(): ?int
    {
        return $this->almacenOrigen?->business?->account_id;
    }

    protected function activityBusinessId(): ?int
    {
        return $this->almacenOrigen?->business_id;
    }

    protected function activityDescription(string $action): string
    {
        return "Traspaso #{$this->getKey()} ({$this->estado}) ".$this->activityVerbo($action);
    }
}
