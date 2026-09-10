<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Intervención de soporte (RF-54): la única forma en que el Super Admin
     * del Sistema puede ESCRIBIR en un negocio ajeno. Fuera de una
     * intervención vigente, su acceso es de solo lectura (RF-51).
     *
     * Tiene alcance a UN negocio y vence sola: el riesgo real no es el abuso,
     * es la sesión olvidada abierta.
     */
    public function up(): void
    {
        Schema::create('intervenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes_soporte')->nullOnDelete();

            $table->string('origen');   // solicitud | iniciativa
            $table->text('motivo');     // heredado de la solicitud, o escrito por el admin

            $table->timestamp('abierta_at');
            $table->timestamp('expira_at');
            $table->timestamp('cerrada_at')->nullable();

            $table->string('estado')->default('abierta'); // abierta|cerrada|vencida

            // Acta (RF-57): se genera sola al cerrar, a partir del libro de
            // movimientos. No se escribe a mano y no se puede editar.
            $table->json('acta')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'estado']);
            $table->index(['super_admin_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenciones');
    }
};
