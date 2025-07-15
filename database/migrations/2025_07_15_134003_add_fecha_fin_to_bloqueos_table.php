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
            // Renombrar la columna 'fecha' a 'fecha_inicio'
            $table->renameColumn('fecha', 'fecha_inicio');
            // Añadir la nueva columna 'fecha_fin'
            $table->date('fecha_fin')->after('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bloqueos', function (Blueprint $table) {
            // Revertir el nombre de la columna si se deshace la migración
            $table->renameColumn('fecha_inicio', 'fecha');
            // Eliminar la columna 'fecha_fin'
            $table->dropColumn('fecha_fin');
        });
    }
};