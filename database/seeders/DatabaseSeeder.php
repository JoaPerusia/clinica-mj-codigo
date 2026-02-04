<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class, // siembra los roles
            ObraSocialSeeder::class, // siembra las obras sociales
            UserSeeder::class, // siembra los usuarios
            EspecialidadSeeder::class, // siembra las especialidades
            MedicoSeeder::class, // siembra los médicos
            PacienteSeeder::class, // siembra los pacientes
        ]);
    }
}
