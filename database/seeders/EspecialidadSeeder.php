<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspecialidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('especialidades')->insert([
            ['nombre_especialidad' => 'Cardiología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Pediatría', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Dermatología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Neurología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Oftalmología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Ginecología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Traumatología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Psicología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Urología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_especialidad' => 'Medicina General', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}