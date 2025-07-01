<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            DB::table('roles')->insert([
            ['rol' => 'Administrador', 'created_at' => now(), 'updated_at' => now()],
            ['rol' => 'Medico', 'created_at' => now(), 'updated_at' => now()],
            ['rol' => 'Paciente', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}