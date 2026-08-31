<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de caja: apertura y cierre de un cajero en un negocio/almacén. Es, en
 * sí mismo, la traza de auditoría de la jornada (quién, cuándo, con qué tasa
 * de cambio, qué contó al cerrar) — ver [[referencia-zeta-pos]].
 */
class Turno extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'almacen_id',
        'cajero_id',
        'dispositivo',
        'estado',
        'tasa_usd',
        'tasa_eur',
        'fecha_apertura',
        'fecha_cierre',
        'conteo_efectivo',
        'total_venta',
        'total_transferencias',
        'total_utilidad',
        'total_contado',
        'salario',
        'deposito',
    ];

    protected $attributes = [
        'estado' => 'abierto',
    ];

    protected $casts = [
        'conteo_efectivo' => 'array',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function isAbierto(): bool
    {
        return $this->estado === 'abierto';
    }

    /**
     * Total de efectivo que arroja el conteo de denominaciones al cierre,
     * convertido a la moneda del negocio con la tasa capturada al abrir.
     * Estructura esperada de `$conteo`: ['locales' => [valor => cantidad],
     * 'usd' => cantidad, 'eur' => cantidad].
     */
    public function calcularTotalContado(array $conteo): float
    {
        $totalLocal = collect($conteo['locales'] ?? [])
            ->sum(fn ($cantidad, $valor) => (float) $valor * (int) $cantidad);

        $totalUsd = (int) ($conteo['usd'] ?? 0) * (float) ($this->tasa_usd ?? 0);
        $totalEur = (int) ($conteo['eur'] ?? 0) * (float) ($this->tasa_eur ?? 0);

        return round($totalLocal + $totalUsd + $totalEur, 2);
    }

    /**
     * Cierra el turno: calcula totales a partir de sus ventas, el salario
     * según la configuración del negocio, y guarda el conteo de efectivo.
     */
    public function cerrar(array $conteo): void
    {
        $totalVenta = (float) $this->ventas()->where('estado', 'pagado')->sum('monto_total');
        $totalTransferencias = (float) $this->ventas()
            ->where('estado', 'pagado')
            ->where('metodo_pago', 'transferencia')
            ->sum('monto_total');

        $totalUtilidad = (float) VentaDetalle::whereIn('venta_id', $this->ventas()->where('estado', 'pagado')->pluck('id'))
            ->get()
            ->sum(fn (VentaDetalle $detalle) => $detalle->cantidad * ($detalle->precio_unitario - $detalle->costo_unitario));

        $salario = $this->business->calcularSalario($totalVenta, $totalUtilidad);

        $this->update([
            'estado' => 'cerrado',
            'fecha_cierre' => now(),
            'conteo_efectivo' => $conteo,
            'total_venta' => $totalVenta,
            'total_transferencias' => $totalTransferencias,
            'total_utilidad' => $totalUtilidad,
            'total_contado' => $this->calcularTotalContado($conteo),
            'salario' => $salario,
            'deposito' => round($totalVenta - $totalTransferencias - $salario, 2),
        ]);
    }

    protected function activityAccountId(): ?int
    {
        return $this->business?->account_id;
    }

    protected function activityBusinessId(): ?int
    {
        return $this->business_id;
    }

    protected function activityDescription(string $action): string
    {
        return "Turno #{$this->getKey()} ({$this->dispositivo}) ".$this->activityVerbo($action);
    }
}
