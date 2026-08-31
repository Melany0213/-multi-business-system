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
        // Turno de caja/ventas: apertura y cierre de un cajero en un negocio,
        // con un almacén (de dónde sale el stock vendido) y un "dispositivo"
        // (nombre del terminal POS, ej. "Super 4" — ver referencia Zeta POS).
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('cajero_id')->constrained('users')->cascadeOnDelete();
            $table->string('dispositivo');

            $table->string('estado')->default('abierto'); // abierto|cerrado

            // Tasa de cambio del día, capturada por el cajero al abrir turno
            // (cuántas unidades de la moneda del negocio equivalen a 1 USD/EUR).
            $table->decimal('tasa_usd', 10, 2)->nullable();
            $table->decimal('tasa_eur', 10, 2)->nullable();

            $table->timestamp('fecha_apertura');
            $table->timestamp('fecha_cierre')->nullable();

            // Conteo de efectivo al cierre: denominaciones locales + cantidad
            // de billetes USD/EUR contados (ver modal "Conteo de efectivo").
            $table->json('conteo_efectivo')->nullable();

            $table->decimal('total_venta', 12, 2)->default(0);
            $table->decimal('total_transferencias', 12, 2)->default(0);
            $table->decimal('total_utilidad', 12, 2)->default(0);
            $table->decimal('total_contado', 12, 2)->nullable();
            $table->decimal('salario', 12, 2)->nullable();
            $table->decimal('deposito', 12, 2)->nullable();

            $table->timestamps();

            $table->index(['business_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
