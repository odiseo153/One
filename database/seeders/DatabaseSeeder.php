<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'odiseo',
            'email' => 'odiseo@gmail.com',
        ]);
        $this->call([
            ProvinceSeeder::class,
            MunicipalitySeeder::class,
            SectorSeeder::class,
            BusinessCategorySeeder::class,
            UserSeeder::class,
            ComplaintSeeder::class,
            ProjectSeeder::class,
        ]);
    }
}
