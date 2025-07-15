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
        Schema::create('horarios_medicos', function (Blueprint $table) {
            $table->bigIncrements('id_horario'); // Clave primaria
            $table->unsignedBigInteger('id_medico'); // Clave foránea al médico
            $table->string('dia_semana'); 
            $table->time('hora_inicio'); 
            $table->time('hora_fin');    
            $table->timestamps();

            // Definir la clave foránea
            $table->foreign('id_medico')
                  ->references('id_medico') 
                  ->on('medicos')
                  ->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_medicos');
    }
};