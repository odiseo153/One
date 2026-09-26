<?php

namespace App\Modules\Auth\Domain\Services;

use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ForgotPasswordService
{
    public function __construct(private AuthRepositoryPort $repository) {}

    public function execute(string $email): string
    {
        $status = $this->repository->sendPasswordResetLink($email);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new HttpException(422, __($status));
        }

        return __($status);
    }
}
