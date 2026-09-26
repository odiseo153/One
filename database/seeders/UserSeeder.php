<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $municipalityIds = Municipality::pluck('id');
        $sectorIds = Sector::pluck('id');

        for ($i = 0; $i < 10; $i++) {
            $sectorId = $sectorIds->isNotEmpty() ? $sectorIds->random() : null;
            $municipalityId = $sectorId
                ? Sector::query()->where('id', $sectorId)->value('municipality_id')
                : $municipalityIds->random();

            User::factory()->create([
                'municipality_id' => $municipalityId,
                'sector_id' => $sectorId,
            ]);
        }
    }
}
