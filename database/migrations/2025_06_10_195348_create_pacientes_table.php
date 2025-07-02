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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->bigIncrements('id_paciente'); 
            $table->string('nombre');
            $table->string('apellido');
            $table->string('dni')->unique(); 
            $table->date('fecha_nacimiento')->nullable();
            $table->string('obra_social')->nullable(); 

            // Clave foránea a la tabla de usuarios
            $table->unsignedBigInteger('id_usuario'); // Un paciente está asociado a UN usuario, y un usuario puede "contener" varios pacientes
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};