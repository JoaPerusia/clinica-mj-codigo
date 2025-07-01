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
        // CAMBIO 1: Cambiar el nombre de la tabla de 'rols' a 'roles'
        Schema::create('roles', function (Blueprint $table) {
            // CAMBIO 2: Definir explícitamente id_rol como la clave primaria auto-incremental
            $table->bigIncrements('id_rol'); // <-- Esta línea reemplaza a $table->id();

            // CAMBIO 3: Añadir la columna 'rol' si la usas para el nombre del rol (ej. 'Administrador')
            $table->string('rol')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // CAMBIO 4: Cambiar el nombre de la tabla aquí también
        Schema::dropIfExists('roles');
    }
};