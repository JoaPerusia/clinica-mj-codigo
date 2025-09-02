<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;
use App\Models\Paciente;
use Carbon\Carbon;

class PacienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pacienteRol = Rol::where('rol', 'Paciente')->first();

        if (!$pacienteRol) {
            $this->command->info('El rol \"Paciente\" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
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
                'apellido' => 'Fernandez',
                'dni' => '30555666',
                'fecha_nacimiento' => '1995-09-30',
                'obra_social' => 'Galeno',
                'email' => 'laura.fernandez@example.com',
                'telefono' => '1199887766',
            ],
        ];

        foreach ($pacientesData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'dni' => $data['dni'],
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'obra_social' => $data['obra_social'],
                    'telefono' => $data['telefono'],
                    'password' => Hash::make('password'),
                ]
            );

            // Usa la relación muchos a muchos para adjuntar el rol.
            $user->roles()->syncWithoutDetaching([$pacienteRol->id_rol]);

            Paciente::firstOrCreate(
                ['id_usuario' => $user->id_usuario],
                [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'dni' => $data['dni'],
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'obra_social' => $data['obra_social'],
                    'telefono' => $data['telefono'],
                ]
            );
        }

        $this->command->info('Pacientes de prueba creados exitosamente.');
    }
}