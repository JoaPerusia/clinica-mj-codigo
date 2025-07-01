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
        Schema::create('usuarios', function (Blueprint $table) {
            // CAMBIO AQUÍ: Define explícitamente id_usuario como clave primaria auto-incremental
            $table->bigIncrements('id_usuario'); // Esta línea reemplaza a $table->id();

            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('telefono')->nullable(); // Si tienes el campo en tu modelo
            $table->unsignedBigInteger('id_rol')->nullable(); // Si tienes el campo en tu modelo y es FK
            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('set null'); // Si ya tienes la FK

            // Recuerda añadir aquí todas las columnas que has definido en tu tabla Usuarios
            // como 'nombre', 'email', 'password', 'telefono', 'id_rol', etc.
            // Si no las tienes, Laravel solo creará 'id' (o 'id_usuario' ahora) y timestamps.

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};