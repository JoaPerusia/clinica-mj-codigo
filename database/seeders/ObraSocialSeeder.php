<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ObraSocial;

class ObraSocialSeeder extends Seeder
{
    public function run(): void
    {
        $obras = [
            'Particular / Sin Obra Social',
            'IAPOS',
            'PAMI',
            'Jerárquicos Salud',
            'Sancor Salud',
            'Swiss Medical',
            'OSDE',
            'OSECAC',
            'UOM',
            'Prevención Salud',
            'Federada Salud',
            'Avalian',
            'Medifé'
        ];

        foreach ($obras as $nombre) {
            ObraSocial::create(['nombre' => $nombre]);
        }
    }
}