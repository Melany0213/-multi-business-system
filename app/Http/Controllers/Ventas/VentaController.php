<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Turno;
use App\Models\Venta;
use App\Services\AccessScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VentaController extends Controller
{
    public function create(Request $request, Turno $turno): Response
    {
        $this->autorizarVenta($request, $turno);

        $negocio = $turno->business;

        $productos = $negocio->productos()
            ->where('business_producto.estado', 'activo')
            ->with(['almacenes' => fn ($query) => $query->where('almacenes.id', $turno->almacen_id)])
            ->get()
            ->map(fn ($producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio' => (float) $producto->pivot->precio,
                'costo' => (float) $producto->pivot->costo,
                'stock' => (int) ($producto->almacenes->first()->pivot->cantidad ?? 0),
            ])
            ->values();

        return Inertia::render('Ventas/Ventas/Create', [
            'turno' => $turno->only(['id', 'dispositivo']),
            'productos' => $productos,
        ]);
    }

    public function store(Request $request, Turno $turno): RedirectResponse
    {
        $this->autorizarVenta($request, $turno);

        $data = $request->validate([
            'metodo_pago' => ['required', 'in:efectivo,tarjeta,transferencia'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $venta = DB::transaction(function () use ($turno, $data) {
            $venta = Venta::create([
                'turno_id' => $turno->id,
                'metodo_pago' => $data['metodo_pago'],
                'monto_total' => 0,
            ]);

            $montoTotal = 0;

            foreach ($data['items'] as $item) {
                $producto = $turno->business->productos()
                    ->where('productos.id', $item['producto_id'])
                    ->firstOrFail();

                $pivotAlmacen = $producto->almacenes()
                    ->where('almacenes.id', $turno->almacen_id)
                    ->first()?->pivot;

                $stockActual = $pivotAlmacen->cantidad ?? 0;

                if ($stockActual < $item['cantidad']) {
                    throw ValidationException::withMessages([
                        'items' => "No hay suficiente stock de \"{$producto->nombre}\" en este almacén.",
                    ]);
                }

                $producto->almacenes()->updateExistingPivot($turno->almacen_id, [
                    'cantidad' => $stockActual - $item['cantidad'],
                ]);

                $venta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->pivot->precio,
                    'costo_unitario' => $producto->pivot->costo,
                ]);

                $montoTotal += $item['cantidad'] * $producto->pivot->precio;
            }

            $venta->update(['monto_total' => round($montoTotal, 2)]);

            return $venta;
        });

        return redirect()->route('turnos.show', $venta->turno_id);
    }

    private function autorizarVenta(Request $request, Turno $turno): void
    {
        abort_unless($turno->isAbierto(), 422, 'Este turno ya está cerrado.');
        abort_unless($turno->cajero_id === $request->user()->id, 403);

        $tienePermiso = app(AccessScheduler::class)->hasPermission($request->user(), $turno->business, 'ventas.crear');
        abort_unless($tienePermiso, 403);
    }
}
