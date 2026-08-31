<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El dueño decide cómo se calcula el salario del cajero por turno —
        // no hay una fórmula fija del sistema. Monto fijo y porcentaje se
        // pueden combinar (ej. "$5 fijos + 3% de la venta"), y el dueño
        // puede delegarle al administrador el ajuste de estos valores.
        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('salario_monto_fijo', 10, 2)->default(0)->after('configuracion');
            $table->decimal('salario_porcentaje', 5, 2)->default(0)->after('salario_monto_fijo');
            // Sobre qué se aplica salario_porcentaje si es mayor a 0.
            $table->string('salario_base_porcentaje')->default('venta')->after('salario_porcentaje'); // venta|utilidad
            $table->boolean('admin_puede_configurar_salario')->default(false)->after('salario_base_porcentaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'salario_monto_fijo',
                'salario_porcentaje',
                'salario_base_porcentaje',
                'admin_puede_configurar_salario',
            ]);
        });
    }
};
