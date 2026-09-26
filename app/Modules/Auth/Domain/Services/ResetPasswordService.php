<?php

namespace App\Modules\Auth\Domain\Services;

use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResetPasswordService
{
    public function __construct(private AuthRepositoryPort $repository) {}

    public function execute(array $data): string
    {
        $status = $this->repository->resetPassword($data);

        if ($status !== Password::PASSWORD_RESET) {
            throw new HttpException(422, __($status));
        }

        return __('Your password has been reset.');
    }
}
