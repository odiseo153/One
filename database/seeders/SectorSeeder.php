<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $municipality = Municipality::query()
            ->where('name', 'Santo Domingo de Guzmán')
            ->first()
            ?? Municipality::query()->firstOrFail();

        $sectors = [
            'Ensanche La Fe',
            'Villa Progreso',
            'Los Ríos',
            'El Millón',
            'La Esperilla',
            'Los Cacicazgos',
            'Bella Vista',
            'Ensanche Naco',
            'Los Prados',
            'Arroyo Hondo',
        ];

        foreach ($sectors as $index => $name) {
            Sector::updateOrCreate(
                ['name' => $name],
                [
                    'municipality_id' => $municipality->id,
                    'geojson_polygon' => $this->sectorPolygon(
                        $municipality->geojson_polygon,
                        $index,
                        count($sectors),
                    ),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $municipalityPolygon
     * @return array<string, mixed>|null
     */
    private function sectorPolygon(?array $municipalityPolygon, int $index, int $total): ?array
    {
        $bounds = $this->boundsFromPolygon($municipalityPolygon);

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
