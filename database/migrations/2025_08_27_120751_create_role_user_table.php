<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('id_rol');
            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('cascade');
            $table->unsignedBigInteger('id_usuario');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->primary(['id_rol', 'id_usuario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};