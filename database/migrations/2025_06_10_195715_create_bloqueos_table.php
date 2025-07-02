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
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->bigIncrements('id_bloqueo'); 
            $table->date('fecha');
            $table->string('motivo')->nullable(); // 'vacaciones', 'congreso', 'permiso'

            // Clave foránea
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
        Schema::dropIfExists('bloqueos');
    }
};