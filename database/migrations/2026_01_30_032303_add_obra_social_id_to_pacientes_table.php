<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            // 1. Creamos la columna nueva (nullable por si hay datos viejos que no coinciden)
            $table->unsignedBigInteger('id_obra_social')->nullable()->after('dni');

            // 2. Creamos la relación (Foreign Key)
            $table->foreign('id_obra_social')
                ->references('id_obra_social')->on('obras_sociales');

            // 3. (Opcional) borrar la columna vieja de texto ya mismo:
            $table->dropColumn('obra_social'); 
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('obra_social')->nullable(); // Restaurar columna vieja
            $table->dropForeign(['id_obra_social']);
            $table->dropColumn('id_obra_social');
        });
    }
};
