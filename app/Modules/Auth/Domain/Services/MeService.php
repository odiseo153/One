<?php

namespace App\Modules\Auth\Domain\Services;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;

class MeService
{
    private AuthRepositoryPort $authRepository;

    public function __construct(AuthRepositoryPort $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function execute(): User
    {
        return $this->authRepository->me();
    }
}
