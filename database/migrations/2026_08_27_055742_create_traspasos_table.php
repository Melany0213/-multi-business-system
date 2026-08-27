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
        Schema::create('traspasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('almacen_origen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('almacen_destino_id')->constrained('almacenes')->cascadeOnDelete();
            $table->unsignedInteger('cantidad');
            $table->string('estado')->default('solicitado'); // solicitado|autorizado|rechazado|completado

            $table->foreignId('solicitado_por')->constrained('users')->cascadeOnDelete();
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_autorizacion')->nullable();
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traspasos');
    }
};
