<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRol = Rol::where('rol', 'Administrador')->first();

        if ($adminRol) {
            $user = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'nombre' => 'Admin',
                    'apellido' => 'Test',
                    'password' => Hash::make('password'),
                    'dni' => '11223344',
                    'fecha_nacimiento' => '1980-01-01',
                    'obra_social' => 'AdminSalud',
                    'telefono' => '1234567890',
                ]
            );

            $user->roles()->attach($adminRol->id_rol);

            $this->command->info('Usuario Administrador creado y rol asignado correctamente.');
        } else {
            $this->command->info('El rol "Administrador" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
        }
    }
}