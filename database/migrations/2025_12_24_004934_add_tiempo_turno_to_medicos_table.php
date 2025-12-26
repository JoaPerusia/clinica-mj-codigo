<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            // Agregamos la columna 'tiempo_turno' (entero, minutos), por defecto 30
            $table->integer('tiempo_turno')->default(30)->after('matricula');
        });
    }

    public function down(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn('tiempo_turno');
        });
    }
};
