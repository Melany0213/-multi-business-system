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
        Schema::table('users', function (Blueprint $table) {
            $table->string('primer_apellido')->after('name');
            $table->string('segundo_apellido')->after('primer_apellido');
            $table->string('username')->unique()->after('segundo_apellido');
            $table->string('telefono')->nullable()->after('email');
            $table->string('carnet_identidad')->nullable()->unique()->after('telefono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['carnet_identidad']);
            $table->dropColumn(['primer_apellido', 'segundo_apellido', 'username', 'telefono', 'carnet_identidad']);
        });
    }
};
