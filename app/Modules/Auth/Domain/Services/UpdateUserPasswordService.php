<?php

namespace App\Modules\Auth\Domain\Services;

use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;

class UpdateUserPasswordService
{
    public function __construct(private AuthRepositoryPort $repository) {}

    public function execute(
        int $userId,
        string $currentPassword,
        string $newPassword,
    ): void {
        $this->repository->updateUserPassword(
            $userId,
            $currentPassword,
            $newPassword,
        );
    }
}
