<?php

namespace App\Modules\Municipality\Domain\Entities;

class Municipality
{
    public $id;

    public $name;

    public $logo_url;

    public $domain;

    public $subdomain;

    public $province_id;

    public $geojson_polygon;

    public $status;

    public $registration_date;

    public $contracted_plan;

    public $deleted_at;

    public $created_at;

    public $updated_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->logo_url = $data['logo_url'] ?? null;
        $this->domain = $data['domain'] ?? null;
        $this->subdomain = $data['subdomain'] ?? null;
        $this->province_id = $data['province_id'] ?? null;
        $this->geojson_polygon = $data['geojson_polygon'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->registration_date = $data['registration_date'] ?? null;
        $this->contracted_plan = $data['contracted_plan'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
