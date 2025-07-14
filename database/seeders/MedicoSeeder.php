<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol; 
use App\Models\User; 
use App\Models\Medico; 
use App\Models\Especialidad;

class MedicoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el ID del rol "Medico"
        $medicoRol = Rol::where('rol', 'Medico')->first();

        if (!$medicoRol) {
            $this->command->info('El rol "Medico" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
            return;
        }

        // Obtener algunas especialidades para asignar
        $especialidades = Especialidad::all();

        if ($especialidades->isEmpty()) {
            $this->command->info('No hay especialidades en la base de datos. Asegúrate de que EspecialidadSeeder se ejecute primero.');
            return;
        }

        // Crear 3 médicos de ejemplo
        $medicosData = [
            [
                'nombre' => 'Dr. Juan',
                'apellido' => 'Perez',
                'email' => 'juan.perez@example.com',
                'telefono' => '1122334455',
                'horario_disponible' => 'Lunes a Viernes 9-17h',
                'especialidades' => ['Cardiología', 'Medicina General']
            ],
            [
                'nombre' => 'Dra. Ana',
                'apellido' => 'Gomez',
                'email' => 'ana.gomez@example.com',
                'telefono' => '9988776655',
                'horario_disponible' => 'Martes y Jueves 10-18h',
                'especialidades' => ['Pediatría']
            ],
            [
                'nombre' => 'Dr. Luis',
                'apellido' => 'Fernandez',
                'email' => 'luis.fernandez@example.com',
                'telefono' => '5544332211',
                'horario_disponible' => 'Miércoles y Viernes 8-16h',
                'especialidades' => ['Dermatología', 'Psicología']
            ],
        ];

        foreach ($medicosData as $data) {
            // 1. Crear el usuario para el médico
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nombre' => $data['nombre'] . ' ' . $data['apellido'],
                    'password' => Hash::make('password'),
                    'telefono' => $data['telefono'],
                    'id_rol' => $medicoRol->id_rol,
                ]
            );

            // 2. Crear el perfil de médico asociado al usuario
            $medico = Medico::firstOrCreate(
                ['id_usuario' => $user->id_usuario], // Usar la clave primaria del modelo User que es id_usuario
                [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'horario_disponible' => $data['horario_disponible'],
                ]
            );

            // 3. Adjuntar especialidades al médico
            $especialidadesIds = Especialidad::whereIn('nombre_especialidad', $data['especialidades'])->pluck('id_especialidad');
            $medico->especialidades()->syncWithoutDetaching($especialidadesIds); // Usa syncWithoutDetaching para añadir si no existen
        }
    }
}