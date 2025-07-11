<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;
use App\Models\Paciente;
use Carbon\Carbon; // Para manejar las fechas de nacimiento

class PacienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el ID del rol "Paciente" dinámicamente
        $pacienteRol = Rol::where('rol', 'Paciente')->first();

        if (!$pacienteRol) {
            $this->command->info('El rol "Paciente" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
            return;
        }

        $pacientesData = [
            [
                'nombre' => 'María',
                'apellido' => 'González',
                'dni' => '20111222',
                'fecha_nacimiento' => '1990-05-15',
                'obra_social' => 'Osde',
                'email' => 'maria.gonzalez@example.com',
                'telefono' => '1133445566',
            ],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Rodríguez',
                'dni' => '25333444',
                'fecha_nacimiento' => '1985-11-22',
                'obra_social' => 'Swiss Medical',
                'email' => 'carlos.rodriguez@example.com',
                'telefono' => '1166778899',
            ],
            [
                'nombre' => 'Laura',
                'apellido' => 'Fernández',
                'dni' => '30555666',
                'fecha_nacimiento' => '1992-01-01',
                'obra_social' => 'Particular',
                'email' => 'laura.fernandez@example.com',
                'telefono' => '1199887766',
            ],
        ];

        foreach ($pacientesData as $data) {
            // Crear el usuario asociado al paciente
            $user = User::create([
                'nombre' => $data['nombre'] . ' ' . $data['apellido'],
                'email' => $data['email'],
                'password' => Hash::make('password'), // Contraseña por defecto
                'telefono' => $data['telefono'],
                'id_rol' => $pacienteRol->id_rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Crear el registro del paciente, vinculándolo al usuario
            Paciente::create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'dni' => $data['dni'],
                'fecha_nacimiento' => Carbon::parse($data['fecha_nacimiento']),
                'obra_social' => $data['obra_social'],
                'id_usuario' => $user->id_usuario, // Usar id_usuario del usuario recién creado
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Pacientes de prueba creados exitosamente.');
    }
}