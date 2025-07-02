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
        Schema::create('turnos', function (Blueprint $table) {
            $table->bigIncrements('id_turno'); 
            $table->date('fecha');
            $table->time('hora');
            $table->string('estado')->default('pendiente'); // 'pendiente', 'confirmado', 'cancelado', 'atendido'

            // Claves foráneas
            $table->unsignedBigInteger('id_paciente');
            $table->foreign('id_paciente')->references('id_paciente')->on('pacientes')->onDelete('cascade'); // Asegúrate que 'pacientes' es el nombre de la tabla de pacientes

            $table->unsignedBigInteger('id_medico');
            $table->foreign('id_medico')->references('id_medico')->on('medicos')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};