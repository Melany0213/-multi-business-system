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
        Schema::create('accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();

            $table->string('tipo_vigencia'); // fija|diaria|semanal|mensual
            $table->json('dias_semana')->nullable(); // ej. [1,2] para lunes y martes (semanal)
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            $table->string('estado')->default('activo'); // activo|inactivo|revocado
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'business_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accesses');
    }
};
