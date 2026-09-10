<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte la bitácora en el LIBRO DE MOVIMIENTOS del negocio:
     *
     * - `evento`: nombre propio del movimiento (turno.abierto,
     *   precio.cambiado, stock.ajustado…) en vez del genérico
     *   created/updated/deleted, que producía entradas como "Turno #14
     *   actualizado" — ilegibles sin abrir el módulo (RF-48).
     *
     * - `hash`/`hash_previo`: cadena de integridad (RF-50). Ningún software
     *   puede impedir que alguien con acceso directo a la base modifique una
     *   fila; lo que sí puede es volverlo DETECTABLE. Alterar una entrada
     *   rompe la cadena de todas las posteriores.
     *
     * - `intervencion_id`: liga el movimiento a la intervención de soporte
     *   en la que se hizo, y es lo que permite que el acta se escriba sola.
     *   Deliberadamente SIN clave foránea: el libro no debe poder perder
     *   filas por un borrado en cascada de otra tabla.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('evento')->nullable()->after('subject_id');
            $table->unsignedBigInteger('intervencion_id')->nullable()->after('changes');
            $table->string('hash_previo', 64)->nullable()->after('intervencion_id');
            $table->string('hash', 64)->nullable()->after('hash_previo');

            $table->index('evento');
            $table->index('intervencion_id');
            $table->index(['business_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['evento']);
            $table->dropIndex(['intervencion_id']);
            $table->dropIndex(['business_id', 'id']);
            $table->dropColumn(['evento', 'intervencion_id', 'hash_previo', 'hash']);
        });
    }
};
