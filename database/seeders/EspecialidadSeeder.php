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
            ['nombre' => 'Cardiología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Pediatría', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Dermatología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Neurología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Oftalmología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ginecología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Traumatología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Psicología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Urología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Medicina General', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}