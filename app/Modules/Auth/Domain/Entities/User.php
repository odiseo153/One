<?php

namespace App\Modules\Auth\Domain\Entities;

class User
{
    public $id;

    public $first_name;

    public $last_name;

    public $username;

    public $cardNetCustomer;

    public $email;

    public $password;

    public $email_verified_at;

    public $remember_token;

    public $created_at;

    public $updated_at;

    public $deleted_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->first_name = $data['first_name'] ?? null;
        $this->last_name = $data['last_name'] ?? null;
        $this->cardNetCustomer = $data['cardNetCustomer'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->email_verified_at = $data['email_verified_at'] ?? null;
        $this->remember_token = $data['remember_token'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
    }
}
