<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventario\Concerns\RequiereNegocioActivo;
use App\Models\Almacen;
use App\Models\Business;
use App\Models\Producto;
use App\Services\AccessScheduler;
use App\Services\Bitacora;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductoController extends Controller
{
    use RequiereNegocioActivo;

    public function __construct(private Bitacora $bitacora) {}

    public function index(Request $request, NegocioActivoResolver $resolver, AccessScheduler $scheduler): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.ver');

        $productos = $negocio->productos()->with('almacenes')->get()->map(fn (Producto $producto) => [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'sku' => $producto->sku,
            'categoria' => $producto->categoria,
            'precio' => $producto->pivot->precio,
            'costo' => $producto->pivot->costo,
            'margen' => Producto::margenGanancia($producto->pivot->precio, $producto->pivot->costo),
            'margen_porcentaje' => Producto::margenPorcentaje($producto->pivot->precio, $producto->pivot->costo),
            'imagen_url' => $producto->imagen_path ? Storage::disk('public')->url($producto->imagen_path) : null,
            'estado' => $producto->pivot->estado,
            'stock_por_almacen' => $producto->almacenes
                ->filter(fn ($a) => $a->business_id === $negocio->id)
                ->map(fn ($a) => [
                    'almacen_id' => $a->id,
                    'almacen_nombre' => $a->nombre,
                    'cantidad' => $a->pivot->cantidad,
                ])->values(),
        ])->values();

        return Inertia::render('Inventario/Productos/Index', [
            'productos' => $productos,
            'almacenes' => $negocio->almacenes()->where('estado', 'activo')->get(['id', 'nombre']),
            'puedeEditar' => $scheduler->hasPermission($request->user(), $negocio, 'inventario.editar'),
        ]);
    }

    public function create(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');

        $yaOfrecidos = $negocio->productos()->pluck('productos.id');

        return Inertia::render('Inventario/Productos/Create', [
            'catalogo' => $negocio->account->productos()
                ->whereNotIn('id', $yaOfrecidos)
                ->where('estado', 'activo')
                ->get(['id', 'nombre', 'sku', 'categoria']),
        ]);
    }

    public function store(Request $request, NegocioActivoResolver $resolver): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');

        $data = $request->validate([
            'producto_id' => ['nullable', 'exists:productos,id'],
            'nombre' => ['required_without:producto_id', 'nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ]);

        if (! empty($data['producto_id'])) {
            $producto = Producto::where('account_id', $negocio->account_id)->findOrFail($data['producto_id']);
        } else {
            $atributos = ['nombre' => $data['nombre'], 'sku' => $data['sku'] ?? null, 'categoria' => $data['categoria'] ?? null];

            if ($request->hasFile('imagen')) {
                $atributos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
            }

            $producto = $negocio->account->productos()->create($atributos);
        }

        $negocio->productos()->syncWithoutDetaching([
            $producto->id => ['precio' => $data['precio'], 'costo' => $data['costo']],
        ]);

        // El precio y el costo viven en la tabla pivote business_producto, que
        // Eloquent no observa: sin este registro explicito el alta quedaba
        // fuera del libro de movimientos (RF-49).
        $this->bitacora->registrar(
            'producto.ofrecido',
            "Producto \"{$producto->nombre}\" incorporado al negocio a "
                .number_format((float) $data['precio'], 2).' (costo '.number_format((float) $data['costo'], 2).')',
            ['negocio' => $negocio, 'sujeto' => $producto, 'action' => 'created'],
        );

        return redirect()->route('productos.index');
    }

    public function edit(Request $request, NegocioActivoResolver $resolver, Producto $producto): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');
        $pivot = $this->pivotOrFail($negocio, $producto);

        return Inertia::render('Inventario/Productos/Edit', [
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'sku' => $producto->sku,
                'categoria' => $producto->categoria,
                'precio' => $pivot->precio,
                'costo' => $pivot->costo,
            ],
        ]);
    }

    public function update(Request $request, NegocioActivoResolver $resolver, Producto $producto): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');
        $this->pivotOrFail($negocio, $producto);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ]);

        $atributos = ['nombre' => $data['nombre'], 'sku' => $data['sku'] ?? null, 'categoria' => $data['categoria'] ?? null];

        if ($request->hasFile('imagen')) {
            if ($producto->imagen_path) {
                Storage::disk('public')->delete($producto->imagen_path);
            }
            $atributos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($atributos);

        // Tocar el costo cambia la utilidad del turno y, con ella, el salario
        // del cajero: es de los datos mas sensibles del sistema y hasta ahora
        // cambiaba sin dejar rastro alguno (RF-49).
        $antes = $this->pivotOrFail($negocio, $producto);

        $negocio->productos()->updateExistingPivot($producto->id, ['precio' => $data['precio'], 'costo' => $data['costo']]);

        $this->bitacora->registrarCambioDeValores(
            'precio.cambiado',
            "Precio o costo de \"{$producto->nombre}\" modificado en este negocio",
            ['precio' => $antes->precio, 'costo' => $antes->costo],
            ['precio' => $data['precio'], 'costo' => $data['costo']],
            ['negocio' => $negocio, 'sujeto' => $producto],
        );

        return redirect()->route('productos.index');
    }

    public function toggleEstado(Request $request, NegocioActivoResolver $resolver, Producto $producto): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');
        $pivot = $this->pivotOrFail($negocio, $producto);

        $nuevoEstado = $pivot->estado === 'activo' ? 'inactivo' : 'activo';

        $negocio->productos()->updateExistingPivot($producto->id, ['estado' => $nuevoEstado]);

        $this->bitacora->registrarCambioDeValores(
            'producto.estado_cambiado',
            "Producto \"{$producto->nombre}\" pasa a {$nuevoEstado} en este negocio",
            ['estado' => $pivot->estado],
            ['estado' => $nuevoEstado],
            ['negocio' => $negocio, 'sujeto' => $producto],
        );

        return back();
    }

    public function actualizarStock(Request $request, NegocioActivoResolver $resolver, Producto $producto, Almacen $almacen): RedirectResponse
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'inventario.editar');
        $this->pivotOrFail($negocio, $producto);
        abort_unless($almacen->business_id === $negocio->id, 403);

        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:0'],
        ]);

        $cantidadAnterior = $producto->almacenes()
            ->where('almacenes.id', $almacen->id)
            ->first()?->pivot?->cantidad;

        $producto->almacenes()->syncWithoutDetaching([$almacen->id => ['cantidad' => $data['cantidad']]]);

        // El ajuste manual de stock es el movimiento mas sensible a fraude de
        // todo un POS, y viajaba por una tabla pivote sin dejar ninguna traza
        // (RF-49). Queda registrado con el valor anterior y el nuevo, que es
        // lo unico que permite despues revisar si el ajuste tenia sentido.
        $this->bitacora->registrarCambioDeValores(
            'stock.ajustado',
            "Stock de \"{$producto->nombre}\" ajustado a mano en \"{$almacen->nombre}\"",
            ['cantidad' => $cantidadAnterior],
            ['cantidad' => $data['cantidad']],
            ['negocio' => $negocio, 'sujeto' => $producto],
        );

        return back();
    }

    /**
     * El producto debe ser del catálogo de la MISMA cuenta que el negocio
     * activo, y ese negocio debe ofrecerlo (tener fila en business_producto).
     */
    private function pivotOrFail(Business $negocio, Producto $producto): object
    {
        abort_unless($producto->account_id === $negocio->account_id, 403);

        $pivot = $negocio->productos()->where('productos.id', $producto->id)->first()?->pivot;

        abort_unless($pivot, 404);

        return $pivot;
    }
}
