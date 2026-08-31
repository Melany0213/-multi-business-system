<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vistas de alcance global para el Super Admin del Sistema: a diferencia de
 * Cuentas (que se mira cuenta por cuenta), acá ve todo el sistema de un
 * vistazo — negocios de cualquier cuenta, métricas agregadas y la bitácora
 * de movimientos de cada usuario. Ver [[referencia-zeta-pos]].
 */
class PanelController extends Controller
{
    public function index(): Response
    {
        $cuentasActivas = Account::where('estado', 'activa')->count();
        $cuentasSuspendidas = Account::where('estado', 'suspendida')->count();

        $totalNegocios = Business::count();
        $negociosActivos = Business::where('estado', 'activo')->count();

        $totalUsuarios = User::count();

        // "Temas financieros": por ahora, lo único monetario que existe en el
        // sistema es la suscripción por plan de cada cuenta (Ventas/Turnos
        // vendrá en una etapa posterior — ver memoria del proyecto).
        $ingresoMensualEstimado = Account::query()
            ->where('estado', 'activa')
            ->join('planes', 'accounts.plan_id', '=', 'planes.id')
            ->sum('planes.precio_mensual');

        $cuentasPorPlan = Plan::withCount('accounts')->get(['id', 'nombre', 'precio_mensual']);

        return Inertia::render('Admin/Panel/Index', [
            'metricas' => [
                'cuentas_activas' => $cuentasActivas,
                'cuentas_suspendidas' => $cuentasSuspendidas,
                'total_negocios' => $totalNegocios,
                'negocios_activos' => $negociosActivos,
                'total_usuarios' => $totalUsuarios,
                'ingreso_mensual_estimado' => (float) $ingresoMensualEstimado,
            ],
            'cuentasPorPlan' => $cuentasPorPlan,
        ]);
    }

    public function negocios(): Response
    {
        $negocios = Business::with(['account', 'rubro'])
            ->withCount('almacenes')
            ->latest()
            ->get();

        return Inertia::render('Admin/Negocios/Index', [
            'negocios' => $negocios,
        ]);
    }

    public function actividad(Request $request): Response
    {
        $data = $request->validate([
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $actividad = ActivityLog::with(['causer', 'account', 'business'])
            ->when($data['usuario_id'] ?? null, fn ($query, $id) => $query->where('causer_id', $id))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $usuarios = User::orderBy('name')->get(['id', 'name', 'primer_apellido', 'segundo_apellido', 'username']);

        return Inertia::render('Admin/Actividad/Index', [
            'actividad' => $actividad,
            'usuarios' => $usuarios,
            'filtros' => ['usuario_id' => $data['usuario_id'] ?? null],
        ]);
    }
}
