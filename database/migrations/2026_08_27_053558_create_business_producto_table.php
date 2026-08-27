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
        Schema::create('business_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->decimal('precio', 12, 2)->default(0);
            $table->decimal('costo', 12, 2)->default(0);
            $table->string('estado')->default('activo'); // activo|inactivo — ¿lo ofrece este negocio?
            $table->timestamps();

            $table->unique(['business_id', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_producto');
    }
};
