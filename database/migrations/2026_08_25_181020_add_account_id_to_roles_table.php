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
        Schema::table('roles', function (Blueprint $table) {
            // null = rol de sistema (global), valor = rol personalizado de esa cuenta
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->cascadeOnDelete();

            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['account_id', 'name', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['account_id', 'name', 'guard_name']);
            $table->dropConstrainedForeignId('account_id');

            $table->unique(['name', 'guard_name']);
        });
    }
};
