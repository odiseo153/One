<?php

namespace App\Modules\Auth\Adapters\Repositories;

use App\Models\User as ModelUser;
use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;
use App\Modules\Auth\Domain\Entities\User;
use App\Modules\Auth\Domain\Exceptions\EmailNotVerifiedException;
use App\Modules\Auth\Domain\Exceptions\InvalidCredentialsException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthRepository implements AuthRepositoryPort
{
    public function create(array $data): ModelUser
    {
        return DB::transaction(function () use ($data) {
            $user = ModelUser::create($data);
            $user->sendEmailVerificationNotification();

            return $user;
        });
    }

    public function login(User $user, bool $rememberMe): ?array
    {
        $modelUser = ModelUser::with('cardNetCustomer')->firstWhere(
            'email',
            $user->email,
        );

        if (
            ! $modelUser ||
            ! Hash::check($user->password, $modelUser->password)
        ) {
            throw new InvalidCredentialsException;
        }

        /*
        if (!$modelUser->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException();
        }
        */

        if (! $modelUser->is_register) {
            throw new Exception('Usuario no registrado.');
        }

        $token = $modelUser->createToken('token', ['*'], now()->addDays(7))
            ->plainTextToken;

        Auth::login($modelUser, $rememberMe);

        return [
            'access_token' => $token,
            'id' => $modelUser->id,
            'username' => $modelUser->username,
            'name' => $modelUser->name,
            'last_name' => $modelUser->last_name,
            'email' => $modelUser->email,
            'role' => $modelUser->role,
            'position_id' => $modelUser->position_id,
            'provider_signature' => $modelUser->provider_signature,
            'provider_cv' => $modelUser->provider_cv,
            'provider_specialty' => $modelUser->provider_specialty,
            'allow_signature' => $modelUser->allow_signature,
            'cardnet_unique_id' => $modelUser->cardNetCustomer?->unique_id,
            'created_at' => $modelUser->created_at,
            'updated_at' => $modelUser->updated_at,
        ];
    }

    public function logout(): void
    {
        Auth::user()->tokens()->delete();
    }

    public function me(): ModelUser
    {
        return Auth::user()->loadMissing('cardNetCustomer');
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data): string
    {
        return Password::reset($data, function (
            ModelUser $user,
            string $password,
        ) {
            $user
                ->forceFill([
                    'password' => Hash::make($password),
                ])
                ->save();
        });
    }

    public function updateUserPassword(
        int $userId,
        string $currentPassword,
        string $newPassword,
    ): void {
        $user = ModelUser::findOrFail($userId);

        if (! Hash::check($currentPassword, $user->password)) {
            throw new HttpException(
                422,
                'La contraseña actual no es correcta.',
            );
        }

        $user->update([
            'password' => $newPassword,
        ]);
    }
}
