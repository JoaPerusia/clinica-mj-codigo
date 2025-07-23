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
        $medicoRol = Rol::where('rol', 'medico')->first();

        if (!$medicoRol) {
            $this->command->info('El rol "medico" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
            return;
        }

        $especialidades = Especialidad::all();

        if ($especialidades->isEmpty()) {
            $this->command->info('No hay especialidades en la base de datos. Asegúrate de que EspecialidadSeeder se ejecute primero.');
            return;
        }

        $medicosData = [
            [
                'nombre' => 'Dr. Juan',
                'apellido' => 'Perez',
                'email' => 'juan.perez@example.com',
                'dni' => '11223344',
                'fecha_nacimiento' => '1975-01-01',
                'obra_social' => 'SaludMed',
                'telefono' => '1122334455', 
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
                'dni' => '22334455',
                'fecha_nacimiento' => '1982-03-15',
                'obra_social' => 'MedicaPlus',
                'telefono' => '9988776655',
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
                'dni' => '33445566',
                'fecha_nacimiento' => '1970-07-20',
                'obra_social' => 'SaludIntegral',
                'telefono' => '5544332211',
                'especialidades' => ['Dermatología', 'Psicología'],
                'horarios_trabajo' => [
                    ['dia_semana' => 'Miércoles', 'hora_inicio' => '08:00:00', 'hora_fin' => '16:00:00'],
                    ['dia_semana' => 'Viernes', 'hora_inicio' => '08:00:00', 'hora_fin' => '16:00:00'],
                ]
            ],
        ];

        $diasSemanaMap = [
            'Domingo' => 0, 'Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3,
            'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6,
        ];

        foreach ($medicosData as $data) {
            // 1. Crear el usuario para el médico con todos los campos (incluido telefono en 'usuarios')
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
                    'id_rol' => $medicoRol->id_rol,
                ]
            );

            // 2. Crear el perfil de médico asociado al usuario (en la tabla 'medicos')
            $medico = Medico::firstOrCreate(
                ['id_usuario' => $user->id_usuario],
                [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                ]
            );

            // 3. Adjuntar especialidades al médico
            $especialidadesIds = Especialidad::whereIn('nombre_especialidad', $data['especialidades'])->pluck('id_especialidad');
            $medico->especialidades()->syncWithoutDetaching($especialidadesIds);

            // 4. Añadir horarios de trabajo al médico
            foreach ($data['horarios_trabajo'] as $horario) {
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
