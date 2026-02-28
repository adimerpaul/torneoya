<?php

namespace Database\Seeders;

use App\Models\Deporte;
use Illuminate\Database\Seeder;

class DeportesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['codigo' => 'futbol_11', 'nombre' => 'Futbol 11', 'icono' => 'sports_soccer'],
            ['codigo' => 'futbol_sala', 'nombre' => 'Futbol de Sala', 'icono' => 'sports_soccer'],
            ['codigo' => 'futbol_7', 'nombre' => 'Futbol 7', 'icono' => 'sports_soccer'],
            ['codigo' => 'baloncesto', 'nombre' => 'Baloncesto', 'icono' => 'sports_basketball'],
            ['codigo' => 'voleibol', 'nombre' => 'Voleibol', 'icono' => 'sports_volleyball'],
            ['codigo' => 'balonmano', 'nombre' => 'Balonmano', 'icono' => 'sports_handball'],
            ['codigo' => 'tenis', 'nombre' => 'Tenis', 'icono' => 'sports_tennis'],
            ['codigo' => 'ajedrez', 'nombre' => 'Ajedrez', 'icono' => 'extension'],
            ['codigo' => 'atletismo', 'nombre' => 'Atletismo', 'icono' => 'directions_run'],
        ];

        foreach ($items as $item) {
            Deporte::query()->updateOrCreate(
                ['codigo' => $item['codigo']],
                ['nombre' => $item['nombre'], 'icono' => $item['icono'], 'activo' => true]
            );
        }
    }
}
