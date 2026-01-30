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
        Schema::create('obras_sociales', function (Blueprint $table) {
            $table->id('id_obra_social'); // Clave primaria personalizada
            $table->string('nombre')->unique(); // Nombre único (ej: IAPOS)
            $table->string('siglas')->nullable(); // Opcional (ej: IOSFA)
            $table->boolean('habilitada')->default(true); // Para habilitar/deshabilitar a futuro sin borrar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obra_socials');
    }
};
