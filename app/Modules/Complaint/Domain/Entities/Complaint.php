<?php

namespace App\Modules\Complaint\Domain\Entities;

class Complaint
{
    public $id;

    public $municipality_id;

    public $sector_id;

    public $category;

    public $description;

    public $latitude;

    public $longitude;

    public $address_text;

    public $photo_url;

    public $citizen_name;

    public $citizen_phone;

    public $tracking_code;

    public $status;

    public $assigned_user_id;

    public $resolved_at;

    public $deleted_at;

    public $created_at;

    public $updated_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->municipality_id = $data['municipality_id'] ?? null;
        $this->sector_id = $data['sector_id'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->latitude = $data['latitude'] ?? null;
        $this->longitude = $data['longitude'] ?? null;
        $this->address_text = $data['address_text'] ?? null;
        $this->photo_url = $data['photo_url'] ?? null;
        $this->citizen_name = $data['citizen_name'] ?? null;
        $this->citizen_phone = $data['citizen_phone'] ?? null;
        $this->tracking_code = $data['tracking_code'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->assigned_user_id = $data['assigned_user_id'] ?? null;
        $this->resolved_at = $data['resolved_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
