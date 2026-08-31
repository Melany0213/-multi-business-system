<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Dueno\NegocioController;
use App\Http\Controllers\Inventario\AlmacenController;
use App\Http\Controllers\Inventario\ProductoController;
use App\Http\Controllers\Inventario\TraspasoController;
use App\Http\Controllers\NegocioActivoController;
use App\Http\Controllers\NegocioSalarioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Ventas\TurnoController;
use App\Http\Controllers\Ventas\VentaController;
use App\Services\AccessScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    return $request->user()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function (Request $request, AccessScheduler $scheduler) {
    $accesses = $scheduler->activeAccessesFor($request->user());

    return Inertia::render('Dashboard', [
        'negocios' => $accesses->map(fn ($access) => [
            'id' => $access->business->id,
            'nombre' => $access->business->nombre,
            'tipo' => $access->business->tipo,
            'rubro' => $access->business->rubro?->nombre,
            'rol' => str_replace('_', ' ', $access->role->name),
            'vigencia' => $access->tipo_vigencia,
        ])->values(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('negocios')->name('negocios.')->group(function () {
    Route::get('/', [NegocioController::class, 'index'])->name('index');
    Route::get('/crear', [NegocioController::class, 'create'])->name('create');
    Route::post('/', [NegocioController::class, 'store'])->name('store');
    Route::get('/{negocio}/editar', [NegocioController::class, 'edit'])->name('edit');
    Route::patch('/{negocio}', [NegocioController::class, 'update'])->name('update');
    Route::patch('/{negocio}/estado', [NegocioController::class, 'toggleEstado'])->name('toggle-estado');

    Route::get('/{negocio}/salario', [NegocioSalarioController::class, 'edit'])->name('salario.edit');
    Route::patch('/{negocio}/salario', [NegocioSalarioController::class, 'update'])->name('salario.update');
});

Route::patch('/negocio-activo/{negocio}', [NegocioActivoController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('negocio-activo.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/almacenes', [AlmacenController::class, 'index'])->name('almacenes.index');
    Route::get('/almacenes/crear', [AlmacenController::class, 'create'])->name('almacenes.create');
    Route::post('/almacenes', [AlmacenController::class, 'store'])->name('almacenes.store');
    Route::get('/almacenes/{almacen}/editar', [AlmacenController::class, 'edit'])->name('almacenes.edit');
    Route::patch('/almacenes/{almacen}', [AlmacenController::class, 'update'])->name('almacenes.update');
    Route::patch('/almacenes/{almacen}/estado', [AlmacenController::class, 'toggleEstado'])->name('almacenes.toggle-estado');

    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/productos/crear', [ProductoController::class, 'create'])->name('productos.create');
    Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    Route::get('/productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
    Route::patch('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
    Route::patch('/productos/{producto}/estado', [ProductoController::class, 'toggleEstado'])->name('productos.toggle-estado');
    Route::patch('/productos/{producto}/stock/{almacen}', [ProductoController::class, 'actualizarStock'])->name('productos.stock');

    Route::get('/traspasos', [TraspasoController::class, 'index'])->name('traspasos.index');
    Route::get('/traspasos/crear', [TraspasoController::class, 'create'])->name('traspasos.create');
    Route::post('/traspasos', [TraspasoController::class, 'store'])->name('traspasos.store');
    Route::patch('/traspasos/{traspaso}/autorizar', [TraspasoController::class, 'autorizar'])->name('traspasos.autorizar');
    Route::patch('/traspasos/{traspaso}/rechazar', [TraspasoController::class, 'rechazar'])->name('traspasos.rechazar');
    Route::patch('/traspasos/{traspaso}/confirmar', [TraspasoController::class, 'confirmar'])->name('traspasos.confirmar');

    Route::get('/turnos', [TurnoController::class, 'index'])->name('turnos.index');
    Route::get('/turnos/abrir', [TurnoController::class, 'create'])->name('turnos.create');
    Route::post('/turnos', [TurnoController::class, 'store'])->name('turnos.store');
    Route::get('/turnos/{turno}', [TurnoController::class, 'show'])->name('turnos.show');
    Route::patch('/turnos/{turno}/cerrar', [TurnoController::class, 'cerrar'])->name('turnos.cerrar');

    Route::get('/turnos/{turno}/ventas/crear', [VentaController::class, 'create'])->name('ventas.create');
    Route::post('/turnos/{turno}/ventas', [VentaController::class, 'store'])->name('ventas.store');
});

Route::middleware(['auth', 'verified', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/negocios', [PanelController::class, 'negocios'])->name('negocios.index');
    Route::get('/actividad', [PanelController::class, 'actividad'])->name('actividad.index');

    Route::get('/cuentas', [AccountController::class, 'index'])->name('cuentas.index');
    Route::get('/cuentas/crear', [AccountController::class, 'create'])->name('cuentas.create');
    Route::post('/cuentas', [AccountController::class, 'store'])->name('cuentas.store');
    Route::get('/cuentas/{account}/editar', [AccountController::class, 'edit'])->name('cuentas.edit');
    Route::patch('/cuentas/{account}/suspender', [AccountController::class, 'suspend'])->name('cuentas.suspend');
    Route::patch('/cuentas/{account}/reactivar', [AccountController::class, 'reactivate'])->name('cuentas.reactivate');
    Route::get('/cuentas/{account}', [AccountController::class, 'show'])->name('cuentas.show');
    Route::patch('/cuentas/{account}', [AccountController::class, 'update'])->name('cuentas.update');
    Route::delete('/cuentas/{account}', [AccountController::class, 'destroy'])->name('cuentas.destroy');
});

require __DIR__.'/auth.php';
