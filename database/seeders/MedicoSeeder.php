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
                'dni' => '98765432',
                'fecha_nacimiento' => '1980-01-01',
                'obra_social' => 'Particular',
                'telefono' => '1133445566',
                'especialidades' => ['Cardiología', 'Clínica Médica'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '13:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '14:00', 'hora_fin' => '18:00'],
                ],
            ],
            [
                'nombre' => 'Dra. Ana',
                'apellido' => 'Gómez',
                'email' => 'ana.gomez@example.com',
                'dni' => '23456789',
                'fecha_nacimiento' => '1985-06-25',
                'obra_social' => 'Osde',
                'telefono' => '1199887766',
                'especialidades' => ['Dermatología'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'martes', 'hora_inicio' => '10:00', 'hora_fin' => '14:00'],
                    ['dia_semana' => 'jueves', 'hora_inicio' => '15:00', 'hora_fin' => '19:00'],
                ],
            ],
        ];

        foreach ($medicosData as $data) {
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

            $user->roles()->syncWithoutDetaching([$medicoRol->id_rol]);

            $medico = Medico::firstOrCreate(
                ['id_usuario' => $user->id_usuario],
                [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                ]
            );

            $especialidadesIds = Especialidad::whereIn('nombre_especialidad', $data['especialidades'])->pluck('id_especialidad');
            $medico->especialidades()->syncWithoutDetaching($especialidadesIds);

            foreach ($data['horarios_trabajo'] as $horario) {
                $diaNumero = $diasSemanaMap[$horario['dia_semana']];
                HorarioMedico::firstOrCreate(
                    [
                        'id_medico' => $medico->id_medico,
                        'dia_semana' => $horario['dia_semana'],
                        'hora_inicio' => $horario['hora_inicio'],
                        'hora_fin' => $horario['hora_fin'],
                    ]
                );
            }
        }
    }
}