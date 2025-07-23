<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRol = Rol::where('rol', 'administrador')->first();

        if ($adminRol) {
            DB::table('usuarios')->insert([
                'nombre' => 'Admin',
                'apellido' => 'Test',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'dni' => '12345678',
                'fecha_nacimiento' => '1980-01-01',
                'obra_social' => 'AdminSalud',
                'telefono' => '1234567890', 
                'id_rol' => $adminRol->id_rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $this->command->info('El rol "administrador" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
        }
    }
}
