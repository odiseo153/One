<?php

namespace App\Modules\Auth\Domain\Services;

use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;
use App\Modules\Auth\Domain\Entities\User;

class LoginService
{
    private AuthRepositoryPort $authRepository;

    public function __construct(AuthRepositoryPort $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function execute(
        string $email,
        string $password,
        bool $rememberMe,
    ): array {
        $user = new User([
            'email' => $email,
            'password' => $password,
        ]);

        return $this->authRepository->login($user, $rememberMe);
    }
}
