<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            // PK sobre el ID de sesión
            $table->string('id')->primary();

            // FK a usuarios.id_usuario, permite nulo y setea a null al borrar el usuario
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null');

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};