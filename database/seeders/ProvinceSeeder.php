<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            'Azua',
            'Bahoruco',
            'Barahona',
            'Dajabón',
            'Distrito Nacional',
            'Duarte',
            'El Seibo',
            'Elías Piña',
            'Espaillat',
            'Hato Mayor',
            'Hermanas Mirabal',
            'Independencia',
            'La Altagracia',
            'La Romana',
            'La Vega',
            'María Trinidad Sánchez',
            'Monseñor Nouel',
            'Monte Cristi',
            'Monte Plata',
            'Pedernales',
            'Peravia',
            'Puerto Plata',
            'Samaná',
            'San Cristóbal',
            'San José de Ocoa',
            'San Juan',
            'San Pedro de Macorís',
            'Sánchez Ramírez',
            'Santiago',
            'Santiago Rodríguez',
            'Santo Domingo',
            'Valverde',
        ];

        foreach ($provinces as $name) {
            Province::updateOrCreate(
                ['name' => $name],
                ['geojson_polygon' => $this->polygon($this->bounds()[$name])],
            );
        }
    }

    /**
     * @return array<string, array{float, float, float, float}>
     */
    private function bounds(): array
    {
        return [
            'Azua' => [-71.05, 18.25, -70.35, 18.75],
            'Bahoruco' => [-71.75, 18.35, -71.05, 18.75],
            'Barahona' => [-71.45, 17.85, -70.85, 18.35],
            'Dajabón' => [-71.85, 19.25, -71.35, 19.75],
            'Distrito Nacional' => [-70.02, 18.43, -69.86, 18.55],
            'Duarte' => [-70.45, 19.00, -69.85, 19.45],
            'El Seibo' => [-69.25, 18.55, -68.65, 19.05],
            'Elías Piña' => [-72.05, 18.65, -71.45, 19.15],
            'Espaillat' => [-70.65, 19.25, -70.15, 19.65],
            'Hato Mayor' => [-69.65, 18.65, -69.05, 19.15],
            'Hermanas Mirabal' => [-70.45, 19.25, -70.05, 19.55],
            'Independencia' => [-72.05, 18.00, -71.35, 18.55],
            'La Altagracia' => [-68.95, 18.35, -68.20, 18.95],
            'La Romana' => [-69.15, 18.25, -68.65, 18.65],
            'La Vega' => [-70.95, 18.75, -70.25, 19.35],
            'María Trinidad Sánchez' => [-70.15, 19.25, -69.55, 19.75],
            'Monseñor Nouel' => [-70.65, 18.75, -70.15, 19.15],
            'Monte Cristi' => [-71.75, 19.55, -71.05, 19.95],
            'Monte Plata' => [-70.20, 18.55, -69.45, 19.05],
            'Pedernales' => [-71.85, 17.45, -71.15, 18.05],
            'Peravia' => [-70.65, 18.15, -70.15, 18.45],
            'Puerto Plata' => [-71.15, 19.55, -70.25, 19.95],
            'Samaná' => [-69.75, 19.05, -69.15, 19.45],
            'San Cristóbal' => [-70.45, 18.25, -69.95, 18.65],
            'San José de Ocoa' => [-70.75, 18.45, -70.25, 18.85],
            'San Juan' => [-71.75, 18.55, -70.95, 19.15],
            'San Pedro de Macorís' => [-69.65, 18.25, -69.05, 18.65],
            'Sánchez Ramírez' => [-70.35, 18.85, -69.85, 19.25],
            'Santiago' => [-71.05, 19.15, -70.45, 19.65],
            'Santiago Rodríguez' => [-71.55, 19.15, -71.05, 19.55],
            'Santo Domingo' => [-70.15, 18.30, -69.55, 18.75],
            'Valverde' => [-71.25, 19.35, -70.75, 19.75],
        ];
    }

    /**
     * @param  array{float, float, float, float}  $bounds
     * @return array{type: string, coordinates: array<int, array<int, array<int, float>>>}
     */
    private function polygon(array $bounds): array
    {
        [$west, $south, $east, $north] = $bounds;

        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$west, $south],
                [$east, $south],
                [$east, $north],
                [$west, $north],
                [$west, $south],
            ]],
        ];
    }
}
