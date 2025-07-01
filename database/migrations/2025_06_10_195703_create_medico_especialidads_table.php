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
        Schema::create('medico_especialidad', function (Blueprint $table) {
            // Las claves foráneas de las tablas que se relacionan
            $table->unsignedBigInteger('id_medico');
            $table->unsignedBigInteger('id_especialidad');

            // Definir las claves foráneas
            $table->foreign('id_medico')->references('id_medico')->on('medicos')->onDelete('cascade');
            $table->foreign('id_especialidad')->references('id_especialidad')->on('especialidades')->onDelete('cascade');

            // Definir la clave primaria compuesta
            $table->primary(['id_medico', 'id_especialidad']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medico_especialidad');
    }
};