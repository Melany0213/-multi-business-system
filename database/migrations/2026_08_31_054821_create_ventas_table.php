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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            // UUID generado en el momento de la venta (no solo el id
            // autoincremental) para que una venta creada offline en el
            // futuro cliente móvil no dependa de un id del servidor.
            $table->uuid('uuid')->unique();

            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();

            $table->decimal('monto_total', 12, 2);
            $table->string('metodo_pago'); // efectivo|tarjeta|transferencia
            $table->string('estado')->default('pagado'); // pagado|anulado
            $table->timestamp('fecha_hora');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
