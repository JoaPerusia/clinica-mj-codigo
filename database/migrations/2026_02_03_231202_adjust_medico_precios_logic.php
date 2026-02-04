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
        // 1. Agregar precio_particular a la tabla médicos
        Schema::table('medicos', function (Blueprint $table) {
            $table->integer('precio_particular')->default(0)->after('id_usuario');
        });

        // 2. Eliminar la columna costo de la tabla pivote (ya no sirve)
        Schema::table('medico_obra_social', function (Blueprint $table) {
            $table->dropColumn('costo');
        });
    }

    public function down(): void
    {
        Schema::table('medico_obra_social', function (Blueprint $table) {
            $table->decimal('costo', 10, 2)->default(0);
        });

        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn('precio_particular');
        });
    }
};
