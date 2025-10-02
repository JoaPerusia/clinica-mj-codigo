<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->bigIncrements('id_bloqueo');

            // Rango de fechas
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // Horarios opcionales
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            // Motivo del bloqueo
            $table->string('motivo')->nullable(); // vacaciones, congreso, permiso, etc.

            // Relación con médico
            $table->unsignedBigInteger('id_medico');
            $table->foreign('id_medico')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos');
    }
};