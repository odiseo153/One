<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        $provinceIds = Province::pluck('id', 'name');

        $municipalities = [
            'Azua' => [
                'Azua de Compostela', 'Estebanía', 'Guayabal', 'Las Charcas',
                'Las Yayas de Viajama', 'Padre Las Casas', 'Peralta', 'Pueblo Viejo',
                'Sabana Yegua', 'Tábara Arriba',
            ],
            'Bahoruco' => [
                'Neiba', 'Galván', 'Los Ríos', 'Tamayo', 'Villa Jaragua',
            ],
            'Barahona' => [
                'Barahona', 'Cabral', 'El Peñón', 'Enriquillo', 'Fundación',
                'Jaquimeyes', 'La Ciénaga', 'Las Salinas', 'Paraíso', 'Polo',
                'Vicente Noble',
            ],
            'Dajabón' => [
                'Dajabón', 'El Pino', 'Loma de Cabrera', 'Partido', 'Restauración',
            ],
            'Distrito Nacional' => [
                'Santo Domingo de Guzmán',
            ],
            'Duarte' => [
                'San Francisco de Macorís', 'Arenoso', 'Castillo',
                'Eugenio María de Hostos', 'Las Guáranas', 'Pimentel', 'Villa Riva',
            ],
            'El Seibo' => [
                'Santa Cruz de El Seibo', 'Miches',
            ],
            'Elías Piña' => [
                'Comendador', 'Bánica', 'El Llano', 'Hondo Valle', 'Juan Santiago',
                'Pedro Santana',
            ],
            'Espaillat' => [
                'Moca', 'Cayetano Germosén', 'Gaspar Hernández', 'Jamao al Norte',
            ],
            'Hato Mayor' => [
                'Hato Mayor del Rey', 'Sabana de la Mar', 'El Valle',
            ],
            'Hermanas Mirabal' => [
                'Salcedo', 'Tenares', 'Villa Tapia',
            ],
            'Independencia' => [
                'Jimaní', 'Cristóbal', 'Duvergé', 'La Descubierta', 'Mella',
                'Postrer Río',
            ],
            'La Altagracia' => [
                'Higüey', 'San Rafael del Yuma',
            ],
            'La Romana' => [
                'La Romana', 'Guaymate', 'Villa Hermosa',
            ],
            'La Vega' => [
                'La Vega', 'Constanza', 'Jarabacoa', 'Jima Abajo',
            ],
            'María Trinidad Sánchez' => [
                'Nagua', 'Cabrera', 'El Factor', 'Río San Juan',
            ],
            'Monseñor Nouel' => [
                'Bonao', 'Maimón', 'Piedra Blanca',
            ],
            'Monte Cristi' => [
                'Monte Cristi', 'Castañuelas', 'Guayubín', 'Las Matas de Santa Cruz',
                'Pepillo Salcedo', 'Villa Vásquez',
            ],
            'Monte Plata' => [
                'Monte Plata', 'Bayaguana', 'Sabana Grande de Boyá', 'Peralvillo',
                'Yamasá',
            ],
            'Pedernales' => [
                'Pedernales', 'Oviedo',
            ],
            'Peravia' => [
                'Baní', 'Nizao', 'Matanzas',
            ],
            'Puerto Plata' => [
                'San Felipe de Puerto Plata', 'Altamira', 'Guananico', 'Imbert',
                'Los Hidalgos', 'Luperón', 'Sosúa', 'Villa Isabela', 'Villa Montellano',
            ],
            'Samaná' => [
                'Santa Bárbara de Samaná', 'Las Terrenas', 'Sánchez',
            ],
            'San Cristóbal' => [
                'San Cristóbal', 'Bajos de Haina', 'Cambita Garabitos', 'Los Cacaos',
                'Sabana Grande de Palenque', 'San Gregorio de Nigua', 'Villa Altagracia',
                'Yaguate',
            ],
            'San José de Ocoa' => [
                'San José de Ocoa', 'Rancho Arriba', 'Sabana Larga',
            ],
            'San Juan' => [
                'San Juan de la Maguana', 'Bohechío', 'El Cercado', 'Juan de Herrera',
                'Las Matas de Farfán', 'Vallejuelo',
            ],
            'San Pedro de Macorís' => [
                'San Pedro de Macorís', 'Consuelo', 'Guayacanes', 'Quisqueya',
                'Ramón Santana', 'San José de los Llanos',
            ],
            'Sánchez Ramírez' => [
                'Cotuí', 'Cevicos', 'Fantino', 'La Mata',
            ],
            'Santiago' => [
                'Santiago de los Caballeros', 'Baitoa', 'Jánico', 'Licey al Medio',
                'Puñal', 'Sabana Iglesia', 'San José de las Matas', 'Tamboril',
                'Villa Bisonó', 'Villa González',
            ],
            'Santiago Rodríguez' => [
                'San Ignacio de Sabaneta', 'Monción', 'Villa Los Almácigos',
            ],
            'Santo Domingo' => [
                'Santo Domingo Este', 'Santo Domingo Norte', 'Santo Domingo Oeste',
                'Boca Chica', 'Los Alcarrizos', 'Pedro Brand', 'San Antonio de Guerra',
            ],
            'Valverde' => [
                'Mao', 'Esperanza', 'Laguna Salada',
            ],
        ];

        foreach ($municipalities as $provinceName => $names) {
            $provinceId = $provinceIds->get($provinceName);
            $province = Province::query()->find($provinceId);

            foreach ($names as $index => $name) {
                Municipality::updateOrCreate(
                    ['name' => $name],
                    [
                        'province_id' => $provinceId,
                        'geojson_polygon' => $this->municipalityPolygon(
                            $province?->geojson_polygon,
                            $index,
                            count($names),
                        ),
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $provincePolygon
     * @return array<string, mixed>|null
     */
    private function municipalityPolygon(?array $provincePolygon, int $index, int $total): ?array
    {
        $bounds = $this->boundsFromPolygon($provincePolygon);

        if (! $bounds) {
            return null;
        }

        [$west, $south, $east, $north] = $bounds;
        $columns = (int) ceil(sqrt($total));
        $rows = (int) ceil($total / $columns);
        $column = $index % $columns;
        $row = intdiv($index, $columns);
        $width = ($east - $west) / $columns;
        $height = ($north - $south) / $rows;

        return $this->polygon([
            $west + ($column * $width),
            $south + ($row * $height),
            $west + (($column + 1) * $width),
            $south + (($row + 1) * $height),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $polygon
     * @return array{float, float, float, float}|null
     */
    private function boundsFromPolygon(?array $polygon): ?array
    {
        $coordinates = $polygon['coordinates'][0] ?? null;

        if (! is_array($coordinates)) {
            return null;
        }

        $lngs = array_column($coordinates, 0);
        $lats = array_column($coordinates, 1);

        return [
            (float) min($lngs),
            (float) min($lats),
            (float) max($lngs),
            (float) max($lats),
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
