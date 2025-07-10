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
        Schema::create('medicos', function (Blueprint $table) {
            $table->bigIncrements('id_medico'); 

            
            $table->string('nombre');
            $table->string('apellido');
            $table->string('horario_disponible')->nullable(); 
            $table->unsignedBigInteger('id_usuario')->nullable(); // Podría ser nullable si un médico no siempre tiene un usuario al principio
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};