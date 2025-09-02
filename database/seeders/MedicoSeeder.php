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
use Carbon\Carbon;

class MedicoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medicoRol = Rol::where('rol', 'Medico')->first();

        if (!$medicoRol) {
            $this->command->info('El rol "Medico" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
            return;
        }

        $especialidades = Especialidad::all();

        if ($especialidades->isEmpty()) {
            $this->command->info('No hay especialidades en la base de datos. Asegúrate de que EspecialidadSeeder se ejecute primero.');
            return;
        }

        // Mapeo de días de la semana a números para mayor consistencia
        $diasSemanaMap = [
            'domingo' => 0,
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6
        ];

        $medicosData = [
            [
                'nombre' => 'Dr. Juan',
                'apellido' => 'Perez',
                'email' => 'juan.perez@example.com',
                'dni' => '11222333',
                'fecha_nacimiento' => '1980-01-01',
                'obra_social' => 'Osde',
                'telefono' => '1144445555',
                'especialidades' => ['Pediatría', 'Cardiología'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '12:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '14:00', 'hora_fin' => '18:00'],
                ]
            ],
            [
                'nombre' => 'Dra. Ana',
                'apellido' => 'Gomez',
                'email' => 'ana.gomez@example.com',
                'dni' => '22333444',
                'fecha_nacimiento' => '1975-06-20',
                'obra_social' => 'Swiss Medical',
                'telefono' => '1166667777',
                'especialidades' => ['Dermatología'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'martes', 'hora_inicio' => '10:00', 'hora_fin' => '13:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '15:00', 'hora_fin' => '19:00'],
                ]
            ],
            [
                'nombre' => 'Dr. Luis',
                'apellido' => 'Ramirez',
                'email' => 'luis.ramirez@example.com',
                'dni' => '33444555',
                'fecha_nacimiento' => '1992-03-10',
                'obra_social' => 'Galeno',
                'telefono' => '1188889999',
                'especialidades' => ['Ginecología'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'viernes', 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
                    ['dia_semana' => 'sabado', 'hora_inicio' => '09:00', 'hora_fin' => '13:00'],
                ]
            ],
        ];

        foreach ($medicosData as $data) {
            // 1. Crear o encontrar el usuario (en la tabla 'usuarios'). Las líneas de nombre y apellido aquí son CORRECTAS.
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

            // 2. Adjuntar el rol de Médico
            $user->roles()->syncWithoutDetaching([$medicoRol->id_rol]);

            // 3. Crear el perfil de médico asociado al usuario (en la tabla 'medicos').
            // Aquí es donde se elimina la duplicación. Solo se inserta la clave foránea `id_usuario`.
            $medico = Medico::firstOrCreate(
                ['id_usuario' => $user->id_usuario]
            );

            // 4. Adjuntar especialidades al médico
            $especialidadesIds = Especialidad::whereIn('nombre_especialidad', $data['especialidades'])->pluck('id_especialidad');
            $medico->especialidades()->syncWithoutDetaching($especialidadesIds);

            // 5. Añadir horarios de trabajo al médico
            foreach ($data['horarios_trabajo'] as $horario) {
                // Usamos el mapa para obtener el número del día de la semana
                $diaNumero = $diasSemanaMap[$horario['dia_semana']];
                HorarioMedico::firstOrCreate(
                    [
                        'id_medico' => $medico->id_medico,
                        'dia_semana' => $diaNumero,
                        'hora_inicio' => $horario['hora_inicio'],
                        'hora_fin' => $horario['hora_fin'],
                    ]
                );
            }
        }
    }
}