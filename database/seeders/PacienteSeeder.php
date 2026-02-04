<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;
use App\Models\Paciente;
use App\Models\ObraSocial; 

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buscamos el rol Paciente
        $pacienteRol = Rol::where('rol', Rol::PACIENTE)->first();

        $obrasSocialesIds = ObraSocial::pluck('id_obra_social')->toArray();

        // Datos de ejemplo
        $pacientes = [
            [
                'nombre' => 'María',
                'apellido' => 'González',
                'dni' => '20111222',
                'email' => 'maria@example.com',
                'telefono' => '1133445566',
                'fecha_nacimiento' => '1990-05-15',
            ],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Rodríguez',
                'dni' => '30111222',
                'email' => 'carlos@example.com',
                'telefono' => '1144556677',
                'fecha_nacimiento' => '1985-08-20',
            ],
            [
                'nombre' => 'Ana',
                'apellido' => 'Fernández',
                'dni' => '40111222',
                'email' => 'ana@example.com',
                'telefono' => '1155667788',
                'fecha_nacimiento' => '1995-12-10',
            ],
        ];

        foreach ($pacientes as $data) {
            // Crear Usuario
            $user = User::create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'dni' => $data['dni'],
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'password' => Hash::make('password123'),
            ]);

            // Asignar rol
            if ($pacienteRol) {
                $user->roles()->attach($pacienteRol->id_rol);
            }

            // Seleccionar obra social aleatoria si existen, sino null
            $idObraSocial = !empty($obrasSocialesIds) ? $obrasSocialesIds[array_rand($obrasSocialesIds)] : null;

            // Crear Paciente 
            Paciente::create([
                'id_usuario' => $user->id_usuario,
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'dni' => $data['dni'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'telefono' => $data['telefono'],
                'id_obra_social' => $idObraSocial, 
            ]);
        }
    }
}