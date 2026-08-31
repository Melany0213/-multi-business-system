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
        // Traza de auditoría genérica: qué usuario hizo qué acción sobre qué
        // registro, en qué cuenta/negocio — para que el Super Admin del
        // Sistema pueda ver los movimientos realizados por cada usuario sin
        // tener que entrar a cada módulo por separado.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();

            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->string('action'); // created|updated|deleted|...
            $table->string('description');
            $table->json('changes')->nullable();

            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('causer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
