<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->bigIncrements('id_usuario');

            $table->string('nombre');
            $table->string('apellido')->nullable(); // si lo usás en tu modelo
            $table->string('dni')->unique();        // ya definido como NOT NULL + único
            $table->date('fecha_nacimiento')->nullable();
            $table->string('obra_social')->nullable();

            $table->string('email')->unique();
            $table->string('password');
            $table->string('telefono', 20)->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // agregado para permitir borrado suave
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};