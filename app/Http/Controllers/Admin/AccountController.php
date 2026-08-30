<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Plan;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        $cuentas = Account::with(['owner', 'plan'])
            ->withCount('businesses')
            ->latest()
            ->get();

        return Inertia::render('Admin/Cuentas/Index', [
            'cuentas' => $cuentas,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Cuentas/Create', [
            'planes' => Plan::where('activo', true)->get(),
        ]);
    }

    public function show(Account $account): Response
    {
        $account->load(['owner', 'plan', 'businesses.rubro']);

        $businessIds = $account->businesses->pluck('id');

        $usuarios = User::whereIn('id', $account->relatedUserIds())
            ->with(['accesses' => function ($query) use ($businessIds) {
                $query->whereIn('business_id', $businessIds)->with(['business', 'role']);
            }])
            ->get();

        return Inertia::render('Admin/Cuentas/Show', [
            'cuenta' => $account,
            'usuarios' => $usuarios,
        ]);
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Admin/Cuentas/Edit', [
            'cuenta' => $account->load('plan'),
            'planes' => Plan::where('activo', true)->get(),
        ]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $data = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:255'],
            'rut' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:planes,id'],
        ]);

        $account->update($data);

        return redirect()->route('admin.cuentas.show', $account);
    }

    public function destroy(Account $account): RedirectResponse
    {
        $account->delete();

        return redirect()->route('admin.cuentas.index');
    }

    public function store(Request $request, UsernameGenerator $usernames): RedirectResponse
    {
        $data = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:255'],
            'rut' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:planes,id'],
            'dueno_nombre' => ['required', 'string', 'max:255'],
            'dueno_primer_apellido' => ['required', 'string', 'max:255'],
            'dueno_segundo_apellido' => ['required', 'string', 'max:255'],
            'dueno_email' => ['required', 'email', 'unique:users,email'],
            'dueno_telefono' => ['nullable', 'string', 'max:255'],
            'dueno_carnet_identidad' => ['nullable', 'string', 'max:255', 'unique:users,carnet_identidad'],
            'dueno_password' => ['required', 'string', 'min:8'],
        ]);

        $dueno = User::create([
            'name' => $data['dueno_nombre'],
            'primer_apellido' => $data['dueno_primer_apellido'],
            'segundo_apellido' => $data['dueno_segundo_apellido'],
            'username' => $usernames->generate(
                $data['dueno_nombre'],
                $data['dueno_primer_apellido'],
                $data['dueno_segundo_apellido'],
                $data['dueno_carnet_identidad'] ?? null,
            ),
            'email' => $data['dueno_email'],
            'telefono' => $data['dueno_telefono'] ?? null,
            'carnet_identidad' => $data['dueno_carnet_identidad'] ?? null,
            'password' => Hash::make($data['dueno_password']),
        ]);

        Account::create([
            'owner_user_id' => $dueno->id,
            'nombre_cliente' => $data['nombre_cliente'],
            'rut' => $data['rut'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
        ]);

        return redirect()->route('admin.cuentas.index');
    }

    public function suspend(Account $account): RedirectResponse
    {
        $account->suspend();

        return back();
    }

    public function reactivate(Account $account): RedirectResponse
    {
        $account->reactivate();

        return back();
    }
}
