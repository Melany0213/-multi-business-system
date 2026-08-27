<?php

namespace Database\Seeders;

use App\Models\Rubro;
use Illuminate\Database\Seeder;

class RubroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rubros = [
            'Cafetería',
            'Restaurante',
            'Minimarket / Tienda de conveniencia',
            'Tienda de mascotas',
            'Veterinaria',
            'Ferretería',
            'Farmacia',
            'Peluquería / Salón de belleza',
            'Ropa / Boutique',
            'Otro',
        ];

        foreach ($rubros as $nombre) {
            Rubro::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
