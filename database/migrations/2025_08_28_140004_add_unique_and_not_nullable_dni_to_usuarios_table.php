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
        Schema::table('usuarios', function (Blueprint $table) {
            // 1. Eliminar el índice único existente
            $table->dropUnique('usuarios_dni_unique');

            // 2. Cambiar la columna a NOT NULL y luego volver a hacerla única
            $table->string('dni')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Invierte los cambios si es necesario, permitiendo nulos y duplicados
            $table->string('dni')->nullable()->change();
            $table->dropUnique(['dni']);
        });
    }
};