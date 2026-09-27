<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

class BusinessCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => '4711', 'name' => 'Venta al por menor en comercios no especializados con predominio de alimentos y bebidas'],
            ['code' => '4721', 'name' => 'Venta al por menor de alimentos en comercios especializados'],
            ['code' => '4771', 'name' => 'Venta al por menor de prendas de vestir, calzado y artículos de cuero'],
            ['code' => '4932', 'name' => 'Transporte de pasajeros por vía terrestre'],
            ['code' => '5610', 'name' => 'Actividades de restaurantes y servicio móvil de comidas'],
            ['code' => '5630', 'name' => 'Servicio de bebidas'],
            ['code' => '6201', 'name' => 'Programación informática'],
            ['code' => '6810', 'name' => 'Actividades inmobiliarias realizadas con bienes propios o arrendados'],
            ['code' => '6920', 'name' => 'Actividades de contabilidad, teneduría de libros y auditoría'],
            ['code' => '7020', 'name' => 'Actividades de consultoría de gestión'],
            ['code' => '8620', 'name' => 'Actividades de médicos y odontólogos'],
            ['code' => '9602', 'name' => 'Peluquería y otros tratamientos de belleza'],
        ];

        foreach ($categories as $category) {
            BusinessCategory::query()->updateOrCreate(['code' => $category['code']], $category);
        }
    }
}
