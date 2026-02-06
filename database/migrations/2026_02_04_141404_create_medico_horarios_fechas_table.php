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
        Schema::create('medico_horarios_fechas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_medico');
            
            $table->date('fecha');      // Ej: 2026-03-10
            $table->time('hora_inicio');
            $table->time('hora_fin');
            
            $table->foreign('id_medico')->references('id_medico')->on('medicos')->onDelete('cascade');
            
            // Evitar duplicar la misma fecha y hora para el mismo médico
            $table->unique(['id_medico', 'fecha', 'hora_inicio']); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medico_horarios_fechas');
    }
};
