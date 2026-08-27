<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsernameGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private UsernameGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = app(UsernameGenerator::class);
    }

    public function test_genera_iniciales_de_apellidos_mas_el_nombre_completo(): void
    {
        $username = $this->generator->generate('María José', 'Fernández', 'López');

        $this->assertSame('flmariajose', $username);
    }

    public function test_ante_colision_agrega_los_ultimos_2_digitos_del_carnet(): void
    {
        User::factory()->create(['username' => 'flmariajose']);

        $username = $this->generator->generate('María José', 'Fernández', 'López', '92041547');

        $this->assertSame('flmariajose47', $username);
    }

    public function test_sin_carnet_y_con_colision_usa_un_contador_de_respaldo(): void
    {
        User::factory()->create(['username' => 'flmariajose']);

        $username = $this->generator->generate('María José', 'Fernández', 'López');

        $this->assertSame('flmariajose2', $username);
    }

    public function test_si_el_sufijo_del_carnet_tambien_choca_usa_el_contador(): void
    {
        User::factory()->create(['username' => 'flmariajose']);
        User::factory()->create(['username' => 'flmariajose47']);

        $username = $this->generator->generate('María José', 'Fernández', 'López', '92041547');

        $this->assertSame('flmariajose2', $username);
    }
}
