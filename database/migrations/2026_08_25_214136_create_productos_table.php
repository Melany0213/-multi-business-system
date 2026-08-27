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
        // El catálogo de productos vive a nivel Cuenta (compartido entre todos
        // los negocios de un mismo dueño) — precio/costo son por negocio
        // (tabla business_producto) y el stock es por almacén (almacen_producto).
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('sku')->nullable();
            $table->string('categoria')->nullable();
            $table->string('imagen_path')->nullable();
            $table->string('estado')->default('activo'); // activo|inactivo (archivado del catálogo)
            $table->timestamps();

            $table->unique(['account_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
