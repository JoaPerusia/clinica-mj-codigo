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
        // 1. Borramos la tabla anterior que estaba mal conceptualmente
        Schema::dropIfExists('especialidad_obra_social');

        // 2. Creamos la nueva tabla vinculada al MÉDICO
        Schema::create('medico_obra_social', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('id_medico');
            $table->unsignedBigInteger('id_obra_social');

            $table->decimal('costo', 10, 2)->default(0);
            $table->text('instrucciones')->nullable(); // Ej: "Traer estudios previos"

            $table->foreign('id_medico')->references('id_medico')->on('medicos')->onDelete('cascade');
            $table->foreign('id_obra_social')->references('id_obra_social')->on('obras_sociales')->onDelete('cascade');

            // Evitar duplicados: Un médico solo puede tener un precio por obra social
            $table->unique(['id_medico', 'id_obra_social']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medico_obra_social');
        // (Opcional) Aquí podrías recrear la tabla especialidad_obra_social si quisieras revertir
    }
};
