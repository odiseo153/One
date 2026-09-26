<?php

namespace App\Modules\Auth\Domain\Contracts;

use App\Models\User as ModelUser;
use App\Modules\Auth\Domain\Entities\User;

interface AuthRepositoryPort
{
    public function create(array $data): ModelUser;

    public function login(User $user, bool $rememberMe): ?array;

    public function logout(): void;

    public function me(): ModelUser;

    public function sendPasswordResetLink(string $email): string;

    public function resetPassword(array $data): string;

    public function updateUserPassword(
        int $userId,
        string $currentPassword,
        string $newPassword,
    ): void;
}
