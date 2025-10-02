<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;
use App\Models\Medico;
use App\Models\Especialidad;
use App\Models\HorarioMedico;

class MedicoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Mapeo de días a números
        $mapaDias = [
            'domingo'    => 0,
            'lunes'      => 1,
            'martes'     => 2,
            'miercoles'  => 3,
            'miércoles'  => 3, // por si viene con tilde
            'jueves'     => 4,
            'viernes'    => 5,
            'sabado'     => 6,
            'sábado'     => 6, // por si viene con tilde
        ];

        // 2. Rol Médico
        $medicoRol = Rol::where('rol', 'Medico')->first();
        if (! $medicoRol) {
            $this->command->info('❌ El rol "Médico" no existe. Ejecutá primero RolSeeder.');
            return;
        }

        // 3. Especialidades
        $especialidadesMap = Especialidad::pluck('id_especialidad', 'nombre_especialidad');
        if ($especialidadesMap->isEmpty()) {
            $this->command->info('❌ No hay especialidades. Ejecutá primero EspecialidadSeeder.');
            return;
        }

        // 4. Datos de ejemplo
        $medicosData = [
            [
                'nombre'            => 'Dr. Juan',
                'apellido'          => 'Perez',
                'email'             => 'juan.perez@example.com',
                'dni'               => '11222333',
                'fecha_nacimiento'  => '1980-01-01',
                'obra_social'       => 'Osde',
                'telefono'          => '1144445555',
                'especialidades'    => ['Pediatría', 'Cardiología'],
                'horarios_trabajo'  => [
                    ['dia_semana' => 'lunes',     'hora_inicio' => '09:00', 'hora_fin' => '12:00'],
                    ['dia_semana' => 'miercoles', 'hora_inicio' => '14:00', 'hora_fin' => '18:00'],
                ],
            ],
            [
                'nombre'            => 'Dra. Ana',
                'apellido'          => 'Gomez',
                'email'             => 'ana.gomez@example.com',
                'dni'               => '22333444',
                'fecha_nacimiento'  => '1975-06-20',
                'obra_social'       => 'Swiss Medical',
                'telefono'          => '1166667777',
                'especialidades'    => ['Dermatología'],
                'horarios_trabajo'  => [
                    ['dia_semana' => 'martes',   'hora_inicio' => '10:00', 'hora_fin' => '13:00'],
                    ['dia_semana' => 'jueves',   'hora_inicio' => '15:00', 'hora_fin' => '19:00'],
                ],
            ],
            [
                'nombre'            => 'Dr. Luis',
                'apellido'          => 'Ramirez',
                'email'             => 'luis.ramirez@example.com',
                'dni'               => '33444555',
                'fecha_nacimiento'  => '1992-03-10',
                'obra_social'       => 'Galeno',
                'telefono'          => '1188889999',
                'especialidades'    => ['Ginecología'],
                'horarios_trabajo'  => [
                    ['dia_semana' => 'viernes', 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
                    ['dia_semana' => 'sabado',  'hora_inicio' => '09:00', 'hora_fin' => '13:00'],
                ],
            ],
        ];

        foreach ($medicosData as $data) {
            // 5. Crear o actualizar el usuario
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

            // 6. Asignar rol Médico
            $user->roles()->syncWithoutDetaching([$medicoRol->id_rol]);

            // 7. Construir string resumen de horarios
            $horarioDisponible = collect($data['horarios_trabajo'])
                ->map(fn($h) => ucfirst($h['dia_semana']) . " {$h['hora_inicio']}-{$h['hora_fin']}")
                ->join(', ');

            $medico = Medico::updateOrCreate(
                ['id_usuario' => $user->id_usuario],
                ['horario_disponible' => $horarioDisponible]
            );

            // 8. Sincronizar especialidades
            $ids = collect($data['especialidades'])
                ->map(fn($name) => $especialidadesMap[$name] ?? null)
                ->filter()
                ->all();
            $medico->especialidades()->sync($ids);

            // 9. Cargar horarios detallados
            foreach ($data['horarios_trabajo'] as $h) {
                $diaNumero = $mapaDias[strtolower($h['dia_semana'])] ?? null;

                if ($diaNumero === null) {
                    $this->command->warn("⚠️ Día inválido '{$h['dia_semana']}' para {$user->email}. Se omitió.");
                    continue;
                }

                HorarioMedico::updateOrCreate([
                    'id_medico'   => $medico->id_medico,
                    'dia_semana'  => $diaNumero,
                    'hora_inicio' => $h['hora_inicio'],
                    'hora_fin'    => $h['hora_fin'],
                ], []);
            }

            $this->command->info("✅ Médico {$user->nombre} {$user->apellido} seedado.");
        }
    }
}