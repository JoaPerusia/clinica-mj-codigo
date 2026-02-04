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
        Schema::create('especialidad_obra_social', function (Blueprint $table) {
            $table->id();
            
            // Claves foráneas
            $table->unsignedBigInteger('id_especialidad');
            $table->unsignedBigInteger('id_obra_social');

            // Datos específicos de esta combinación
            $table->decimal('costo', 10, 2)->default(0); // Ej: 5000.00
            $table->text('instrucciones')->nullable();   // Ej: "Traer bono y toalla"

            // Relaciones
            $table->foreign('id_especialidad')->references('id_especialidad')->on('especialidades')->onDelete('cascade');
            $table->foreign('id_obra_social')->references('id_obra_social')->on('obras_sociales')->onDelete('cascade');

            // Evitar duplicados (No puedes tener 2 veces Cardiología+IAPOS)
            $table->unique(['id_especialidad', 'id_obra_social'], 'esp_os_unique');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('especialidad_obra_social');
    }
};
