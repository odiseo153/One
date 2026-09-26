<?php

namespace App\Modules\User\Domain\Entities;

class User
{
    public $id;

    public $name;

    public $email;

    public $password;

    public $municipality_id;

    public $sector_id;

    public $role_id;

    public $phone;

    public $status;

    public $deleted_at;

    public $email_verified_at;

    public $two_factor_confirmed_at;

    public $two_factor_secret;

    public $two_factor_recovery_codes;

    public $remember_token;

    public $created_at;

    public $updated_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->municipality_id = $data['municipality_id'] ?? null;
        $this->sector_id = $data['sector_id'] ?? null;
        $this->role_id = $data['role_id'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->email_verified_at = $data['email_verified_at'] ?? null;
        $this->two_factor_confirmed_at = $data['two_factor_confirmed_at'] ?? null;
        $this->two_factor_secret = $data['two_factor_secret'] ?? null;
        $this->two_factor_recovery_codes = $data['two_factor_recovery_codes'] ?? null;
        $this->remember_token = $data['remember_token'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
