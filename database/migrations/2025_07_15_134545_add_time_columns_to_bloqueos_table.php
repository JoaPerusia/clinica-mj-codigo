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
        Schema::table('bloqueos', function (Blueprint $table) {
            // Añadir las nuevas columnas para la hora de inicio y fin, haciéndolas nulas
            $table->time('hora_inicio')->nullable()->after('fecha_fin');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bloqueos', function (Blueprint $table) {
            // Eliminar las columnas si se revierte la migración
            $table->dropColumn('hora_fin');
            $table->dropColumn('hora_inicio');
        });
    }
};