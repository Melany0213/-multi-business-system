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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo'); // servicios|productos|mixto (libre)
            $table->string('estado')->default('activo'); // activo|inactivo
            $table->string('moneda')->default('USD');
            $table->string('zona_horaria')->default('UTC');
            $table->json('configuracion')->nullable(); // impuestos, formato de recibo, etc.

            // Personalización visual (theming) por negocio
            $table->string('logo_path')->nullable();
            $table->string('color_primario')->nullable();
            $table->string('color_secundario')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
