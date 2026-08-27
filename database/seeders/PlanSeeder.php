<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planes = [
            ['nombre' => 'Básico', 'max_negocios' => 1, 'max_usuarios' => 3, 'precio_mensual' => 9.99],
            ['nombre' => 'Profesional', 'max_negocios' => 5, 'max_usuarios' => 15, 'precio_mensual' => 24.99],
            ['nombre' => 'Empresa', 'max_negocios' => null, 'max_usuarios' => null, 'precio_mensual' => 59.99],
        ];

        foreach ($planes as $plan) {
            Plan::firstOrCreate(['nombre' => $plan['nombre']], $plan);
        }
    }
}
