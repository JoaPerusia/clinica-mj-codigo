<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            DB::table('roles')->insert([
            ['rol' => Rol::ADMINISTRADOR, 'created_at' => now(), 'updated_at' => now()],
            ['rol' => Rol::MEDICO, 'created_at' => now(), 'updated_at' => now()],
            ['rol' => Rol::PACIENTE, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}