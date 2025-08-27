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
    public function run(): void
    {
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
                'telefono' => '1144556677',
            ],
            [
                'nombre' => 'Laura',
                'apellido' => 'Fernández',
                'dni' => '30444555',
                'fecha_nacimiento' => '1995-09-10',
                'obra_social' => 'Particular',
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

            $user->roles()->syncWithoutDetaching([$pacienteRol->id_rol]);

            Paciente::firstOrCreate(
                ['id_usuario' => $user->id_usuario],
                [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'dni' => $data['dni'],
                    'fecha_nacimiento' => Carbon::parse($data['fecha_nacimiento']),
                    'obra_social' => $data['obra_social'],
                    'telefono' => $data['telefono'], 
                ]
            );
        }

        $this->command->info('Pacientes de prueba creados exitosamente.');
    }
}