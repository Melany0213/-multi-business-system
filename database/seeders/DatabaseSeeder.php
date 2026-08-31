<?php

namespace Database\Seeders;

use App\Models\Access;
use App\Models\Account;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Rubro;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    // Nota: a propósito NO usamos WithoutModelEvents — Business dispara un
    // evento `created` que le da acceso automático al dueño (ver Business::booted()).

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RubroSeeder::class);
        $this->call(PlanSeeder::class);

        $usernames = app(UsernameGenerator::class);
        $credenciales = [];

        // Super Admin del Sistema (nivel plataforma) — dueño de la plataforma.
        $superAdmin = User::factory()->create([
            'name' => 'Melany',
            'primer_apellido' => 'Coto',
            'segundo_apellido' => 'Ramírez',
            'username' => $usernames->generate('Melany', 'Coto', 'Ramírez'),
            'email' => 'melcr132000@gmail.com',
            'is_super_admin_sistema' => true,
        ]);
        $credenciales[] = ['Super Admin Sistema', $superAdmin->username, 'password'];

        // --- Datos de ejemplo para probar el modelo multi-negocio ---

        $dueno = User::factory()->create([
            'name' => 'Carlos',
            'primer_apellido' => 'Fernández',
            'segundo_apellido' => 'Gómez',
            'username' => $usernames->generate('Carlos', 'Fernández', 'Gómez'),
            'email' => 'dueno@example.com',
        ]);
        $credenciales[] = ['Dueño Demo', $dueno->username, 'password'];

        $cuenta = Account::create([
            'owner_user_id' => $dueno->id,
            'nombre_cliente' => 'Negocios Demo S.A.',
            'rut' => '76.412.908-4',
            'telefono' => '+56 9 6142 8877',
            'estado' => 'activa',
            'plan_id' => Plan::where('nombre', 'Profesional')->first()->id,
        ]);

        $negocio = Business::create([
            'account_id' => $cuenta->id,
            'rubro_id' => Rubro::where('nombre', 'Cafetería')->first()->id,
            'nombre' => 'Cafetería Central',
            'tipo' => 'productos',
            'estado' => 'activo',
            'moneda' => 'USD',
            'zona_horaria' => 'UTC',
        ]);

        $almacen = $negocio->almacenes()->create(['nombre' => 'Almacén Central']);

        // Catálogo a nivel Cuenta — el mismo producto puede ofrecerse en
        // varios negocios del dueño, cada uno con su propio precio/costo.
        $refresco = $cuenta->productos()->create([
            'nombre' => 'Refresco',
            'sku' => 'REF-001',
            'categoria' => 'Bebidas',
        ]);
        $cafe = $cuenta->productos()->create([
            'nombre' => 'Café',
            'sku' => 'CAF-001',
            'categoria' => 'Bebidas',
        ]);

        $negocio->productos()->attach([
            $refresco->id => ['precio' => 1.50, 'costo' => 0.80],
            $cafe->id => ['precio' => 2.00, 'costo' => 0.90],
        ]);
        $almacen->productos()->attach([
            $refresco->id => ['cantidad' => 48],
            $cafe->id => ['cantidad' => 30],
        ]);

        // Segundo negocio del mismo dueño, para probar el selector de negocio
        // activo y el traspaso de mercancía entre negocios distintos.
        $segundoNegocio = Business::create([
            'account_id' => $cuenta->id,
            'rubro_id' => Rubro::where('nombre', 'Restaurante')->first()->id,
            'nombre' => 'Restaurante El Fogón',
            'tipo' => 'productos',
            'estado' => 'activo',
            'moneda' => 'USD',
            'zona_horaria' => 'UTC',
        ]);
        $almacenFogon = $segundoNegocio->almacenes()->create(['nombre' => 'Almacén Principal']);

        // El mismo Refresco, pero más caro en el restaurante (doc: "en los
        // lugares de consumo normalmente es más caro").
        $segundoNegocio->productos()->attach($refresco->id, ['precio' => 2.25, 'costo' => 0.80]);
        $almacenFogon->productos()->attach($refresco->id, ['cantidad' => 12]);

        $administrador = User::factory()->create([
            'name' => 'Laura',
            'primer_apellido' => 'Martínez',
            'segundo_apellido' => 'Ruiz',
            'username' => $usernames->generate('Laura', 'Martínez', 'Ruiz'),
            'email' => 'administrador@example.com',
        ]);
        $credenciales[] = ['Administrador Demo', $administrador->username, 'password'];

        Access::create([
            'user_id' => $administrador->id,
            'business_id' => $negocio->id,
            'role_id' => Role::findByName('administrador')->id,
            'tipo_vigencia' => 'fija',
            'estado' => 'activo',
            'created_by' => $dueno->id,
        ]);

        $dependiente = User::factory()->create([
            'name' => 'Ana',
            'primer_apellido' => 'Pérez',
            'segundo_apellido' => 'Díaz',
            'username' => $usernames->generate('Ana', 'Pérez', 'Díaz'),
            'email' => 'dependienta@example.com',
        ]);
        $credenciales[] = ['Dependienta Demo', $dependiente->username, 'password'];

        // Acceso semanal: lunes (1) y martes (2), en horario de mañana.
        Access::create([
            'user_id' => $dependiente->id,
            'business_id' => $negocio->id,
            'role_id' => Role::findByName('dependiente')->id,
            'tipo_vigencia' => 'semanal',
            'dias_semana' => [1, 2],
            'hora_inicio' => '08:00:00',
            'hora_fin' => '14:00:00',
            'estado' => 'activo',
            'created_by' => $dueno->id,
        ]);

        $this->command?->table(['Rol', 'Usuario', 'Password'], $credenciales);
    }
}
// crmelany	password
// fgcarlos	password
// mrlaura	password
// pdana	password
