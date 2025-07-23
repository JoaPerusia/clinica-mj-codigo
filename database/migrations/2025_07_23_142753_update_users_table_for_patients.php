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
        Schema::table('usuarios', function (Blueprint $table) {
            // Añadir nuevas columnas para el perfil de paciente principal
            $table->string('apellido')->nullable()->after('nombre');
            $table->string('dni', 20)->unique()->nullable()->after('apellido');
            $table->date('fecha_nacimiento')->nullable()->after('dni');
            $table->string('obra_social')->nullable()->after('fecha_nacimiento');

            // Eliminar la columna 'telefono' si existe y no se usa para el usuario en general
            if (Schema::hasColumn('usuarios', 'telefono')) {
                $table->dropColumn('telefono');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Revertir las columnas añadidas
            $table->dropColumn('obra_social');
            $table->dropColumn('fecha_nacimiento');
            $table->dropColumn('dni');
            $table->dropColumn('apellido');

            // Si eliminaste 'telefono', puedes añadirlo de nuevo aquí si lo necesitas en el futuro
            // $table->string('telefono', 20)->nullable();
        });
    }
};
