<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; // Importa Hash
use App\Models\Rol; // Importa el modelo Rol

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Opcional: Borrar usuarios existentes para evitar duplicados en cada ejecución del seeder
        // DB::table('usuarios')->truncate(); para borrar todos los datos
        // Obtener el ID del rol "Administrador" dinámicamente
        $adminRol = Rol::where('rol', 'Administrador')->first();

        if ($adminRol) {
            DB::table('usuarios')->insert([ 
                'nombre' => 'Admin Test',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'), 
                'telefono' => '1234567890',
                'id_rol' => $adminRol->id_rol, // Asignar el ID del rol Administrador
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $this->command->info('El rol "Administrador" no fue encontrado. Asegúrate de que RolSeeder se ejecute primero.');
        }
    }
}