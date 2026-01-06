<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;
use App\Models\Paciente;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buscamos el rol Paciente
        $pacienteRol = Rol::where('rol', Rol::PACIENTE)->first();
        if (! $pacienteRol) {
            $this->command->info('❌ Falta el rol Paciente. Ejecuta RolSeeder primero.');
            return;
        }

        // 2. Definimos el array con los pacientes de ejemplo
        $pacientes = [
            [
                'nombre'           => 'María',
                'apellido'         => 'González',
                'dni'              => '20111222',
                'fecha_nacimiento' => '1990-05-15',
                'obra_social'      => 'Osde',
                'email'            => 'maria.gonzalez@example.com',
                'telefono'         => '1133445566',
            ],
            [
                'nombre'           => 'Carlos',
                'apellido'         => 'Rodríguez',
                'dni'              => '25333444',
                'fecha_nacimiento' => '1985-11-22',
                'obra_social'      => 'Swiss Medical',
                'email'            => 'carlos.rodriguez@example.com',
                'telefono'         => '1166778899',
            ],
            [
                'nombre'           => 'Laura',
                'apellido'         => 'Fernandez',
                'dni'              => '30555666',
                'fecha_nacimiento' => '1995-09-30',
                'obra_social'      => 'Galeno',
                'email'            => 'laura.fernandez@example.com',
                'telefono'         => '1199887766',
            ],
        ];

        // 3. Recorremos cada paciente y lo creamos/actualizamos
        foreach ($pacientes as $data) {
            // Crear o actualizar usuario
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'nombre'           => $data['nombre'],
                    'apellido'         => $data['apellido'],
                    'dni'              => $data['dni'],
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'obra_social'      => $data['obra_social'],
                    'telefono'         => $data['telefono'],
                    'password'         => Hash::make('password'),
                ]
            );

            // Asignar rol Paciente
            $user->roles()->syncWithoutDetaching([$pacienteRol->id_rol]);

            // Crear o actualizar perfil en la tabla pacientes
            Paciente::updateOrCreate(
                ['id_usuario' => $user->id_usuario],
                [
                    'nombre'           => $data['nombre'],
                    'apellido'         => $data['apellido'],
                    'dni'              => $data['dni'],
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'obra_social'      => $data['obra_social'],
                    'telefono'         => $data['telefono'],
                ]
            );

            $this->command->info("✅ Paciente {$data['nombre']} {$data['apellido']} seedado.");
        }
    }
}