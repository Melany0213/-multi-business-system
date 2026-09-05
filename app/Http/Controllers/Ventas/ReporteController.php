<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventario\Concerns\RequiereNegocioActivo;
use App\Models\Business;
use App\Models\Turno;
use App\Models\Venta;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard operativo de UN negocio (a diferencia del Panel del Super Admin,
 * que consolida toda la plataforma) — venta/utilidad/salario de hoy vs.
 * ayer, turnos abiertos y últimas ventas. Réplica de la pantalla de inicio
 * de Zeta POS (ver [[referencia-zeta-pos]]).
 */
class ReporteController extends Controller
{
    use RequiereNegocioActivo;

    public function index(Request $request, NegocioActivoResolver $resolver): Response
    {
        $negocio = $this->negocioActivoOrFail($request, $resolver);
        $this->autorizarPermiso($request, $negocio, 'reportes.ver');

        $turnosAbiertos = Turno::where('business_id', $negocio->id)
            ->where('estado', 'abierto')
            ->with('cajero')
            ->withSum(['ventas as venta_en_vivo' => fn ($q) => $q->where('estado', 'pagado')], 'monto_total')
            ->get();

        $ultimasVentas = Venta::whereHas('turno', fn ($q) => $q->where('business_id', $negocio->id))
            ->with(['turno.cajero'])
            ->latest('fecha_hora')
            ->limit(10)
            ->get();

        return Inertia::render('Ventas/Reportes/Index', [
            'operacion' => [
                'hoy' => $this->resumenDelDia($negocio, Carbon::today()),
                'ayer' => $this->resumenDelDia($negocio, Carbon::yesterday()),
            ],
            'turnosAbiertos' => $turnosAbiertos,
            'ultimasVentas' => $ultimasVentas,
        ]);
    }

    private function resumenDelDia(Business $negocio, Carbon $dia): array
    {
        $turnos = Turno::where('business_id', $negocio->id)
            ->where('estado', 'cerrado')
            ->whereDate('fecha_cierre', $dia);

        return [
            'turnos_cerrados' => (clone $turnos)->count(),
            'venta' => (float) (clone $turnos)->sum('total_venta'),
            'transferencias' => (float) (clone $turnos)->sum('total_transferencias'),
            'utilidad' => (float) (clone $turnos)->sum('total_utilidad'),
            'salario' => (float) (clone $turnos)->sum('salario'),
        ];
    }
}
