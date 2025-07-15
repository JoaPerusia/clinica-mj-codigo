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
use App\Models\HorarioMedico; 

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

        // Datos de médicos de ejemplo con sus especialidades y AHORA SUS HORARIOS
        $medicosData = [
            [
                'nombre' => 'Dr. Juan',
                'apellido' => 'Perez',
                'email' => 'juan.perez@example.com',
                'telefono' => '1122334455',
                'horario_disponible' => 'Lunes a Viernes 9-17h', // Esta columna ya no se usará para la lógica
                'especialidades' => ['Cardiología', 'Medicina General'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'Lunes', 'hora_inicio' => '09:00:00', 'hora_fin' => '13:00:00'],
                    ['dia_semana' => 'Lunes', 'hora_inicio' => '14:00:00', 'hora_fin' => '17:00:00'],
                    ['dia_semana' => 'Martes', 'hora_inicio' => '09:00:00', 'hora_fin' => '17:00:00'],
                    ['dia_semana' => 'Miércoles', 'hora_inicio' => '09:00:00', 'hora_fin' => '13:00:00'],
                    ['dia_semana' => 'Jueves', 'hora_inicio' => '09:00:00', 'hora_fin' => '17:00:00'],
                    ['dia_semana' => 'Viernes', 'hora_inicio' => '09:00:00', 'hora_fin' => '17:00:00'],
                ]
            ],
            [
                'nombre' => 'Dra. Ana',
                'apellido' => 'Gomez',
                'email' => 'ana.gomez@example.com',
                'telefono' => '9988776655',
                'horario_disponible' => 'Martes y Jueves 10-18h',
                'especialidades' => ['Pediatría', 'Neurología'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'Martes', 'hora_inicio' => '10:00:00', 'hora_fin' => '18:00:00'],
                    ['dia_semana' => 'Jueves', 'hora_inicio' => '10:00:00', 'hora_fin' => '18:00:00'],
                ]
            ],
            [
                'nombre' => 'Dr. Luis',
                'apellido' => 'Fernandez',
                'email' => 'luis.fernandez@example.com',
                'telefono' => '5544332211',
                'horario_disponible' => 'Miércoles y Viernes 8-16h',
                'especialidades' => ['Dermatología', 'Psicología'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'Miércoles', 'hora_inicio' => '08:00:00', 'hora_fin' => '16:00:00'],
                    ['dia_semana' => 'Viernes', 'hora_inicio' => '08:00:00', 'hora_fin' => '16:00:00'],
                ]
            ],
        ];

        // Mapeo de nombres de día a números de día de semana (0=Domingo, 1=Lunes, ..., 6=Sábado)
        $diasSemanaMap = [
            'Domingo' => 0,
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
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
                ['id_usuario' => $user->id_usuario],
                [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'horario_disponible' => $data['horario_disponible'], // Mantener por si acaso, pero ya no se usa
                ]
            );

            // 3. Adjuntar especialidades al médico
            $especialidadesIds = Especialidad::whereIn('nombre_especialidad', $data['especialidades'])->pluck('id_especialidad');
            $medico->especialidades()->syncWithoutDetaching($especialidadesIds);

            // 4. **NUEVO: Añadir horarios de trabajo al médico**
            foreach ($data['horarios_trabajo'] as $horario) {
                // Convertir el nombre del día a número de día de la semana
                $diaNumero = $diasSemanaMap[$horario['dia_semana']];

                HorarioMedico::firstOrCreate(
                    [
                        'id_medico' => $medico->id_medico,
                        'dia_semana' => $diaNumero, // Guardar como número
                        'hora_inicio' => $horario['hora_inicio'],
                        'hora_fin' => $horario['hora_fin'],
                    ]
                );
            }
        }
    }
}