<?php

namespace App\Modules\Province\Domain\Entities;

class Province
{
    public $id;

    public $name;

    public $geojson_polygon;

    public $deleted_at;

    public $created_at;

    public $updated_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->geojson_polygon = $data['geojson_polygon'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
