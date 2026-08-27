<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\NegocioActivoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NegocioActivoController extends Controller
{
    public function update(Request $request, NegocioActivoResolver $resolver, Business $negocio): RedirectResponse
    {
        abort_unless($resolver->cambiar($request, $negocio), 403);

        return back();
    }
}
