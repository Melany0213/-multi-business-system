<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitud de revisión o corrección que el DUEÑO le hace al soporte
     * (RF-58). Es la pieza que cierra el ciclo: cuando el Super Admin abre
     * una intervención a partir de una solicitud, el motivo no lo escribe
     * él — es el pedido firmado por el dueño (RF-59).
     */
    public function up(): void
    {
        Schema::create('solicitudes_soporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('solicitante_id')->constrained('users')->cascadeOnDelete();

            $table->string('tipo');           // revision | correccion
            $table->text('descripcion');

            // Registro concreto señalado por el dueño ("esta venta", "este
            // producto"), opcional: la solicitud puede ser genérica.
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();

            $table->string('estado')->default('abierta'); // abierta|en_revision|resuelta|rechazada
            $table->text('respuesta')->nullable();        // qué contestó el soporte al cerrarla

            // Conformidad del dueño (RF-60): el ciclo lo cierra él, no el admin.
            $table->string('conformidad')->nullable();    // conforme|reabierta
            $table->timestamp('conformidad_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_soporte');
    }
};
