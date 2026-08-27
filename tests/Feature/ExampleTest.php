<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visitante_sin_sesion_es_enviado_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_es_enviado_al_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }
}
