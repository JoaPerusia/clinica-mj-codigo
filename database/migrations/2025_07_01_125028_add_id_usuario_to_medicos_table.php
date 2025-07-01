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
        Schema::table('Medicos', function (Blueprint $table) {
            // Añadir la columna id_usuario
            $table->unsignedBigInteger('id_usuario')->after('id_medico')->nullable(); // Puedes hacerlo nullable si algunos médicos no tendrán usuario de login inicialmente

            // Establecer id_usuario como clave foránea
            $table->foreign('id_usuario')
                  ->references('id_usuario')->on('Usuarios') // Hace referencia a la columna id_usuario en la tabla Usuarios
                  ->onDelete('set null'); // Opcional: Si el usuario es eliminado, el id_usuario en Medicos se pone a NULL
                                          // Considera 'cascade' si la eliminación de un usuario médico debe eliminar al médico
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Medicos', function (Blueprint $table) {
            // Eliminar la clave foránea primero
            $table->dropForeign(['id_usuario']);
            // Eliminar la columna id_usuario
            $table->dropColumn('id_usuario');
        });
    }
};