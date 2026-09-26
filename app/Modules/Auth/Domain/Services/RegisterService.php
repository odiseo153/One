<?php

namespace App\Modules\Auth\Domain\Services;

use App\Models\User as ModelUser;
use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;
use Illuminate\Support\Facades\Hash;

class RegisterService
{
    private AuthRepositoryPort $authRepository;

    public function __construct(AuthRepositoryPort $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function execute(array $data): ModelUser
    {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = $data['role'] ?? 2;

        return $this->authRepository->create($data);
    }
}
