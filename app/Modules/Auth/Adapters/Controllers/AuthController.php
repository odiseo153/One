<?php

namespace App\Modules\Auth\Adapters\Controllers;

use App\Modules\Auth\Domain\Services\ForgotPasswordService;
use App\Modules\Auth\Domain\Services\LoginService;
use App\Modules\Auth\Domain\Services\LogoutService;
use App\Modules\Auth\Domain\Services\MeService;
use App\Modules\Auth\Domain\Services\RegisterService;
use App\Modules\Auth\Domain\Services\ResetPasswordService;
use App\Modules\Auth\Domain\Services\UpdateUserPasswordService;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterUserRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Modules\Auth\Http\Requests\UpdateUserPasswordRequest;
use App\Modules\Auth\Http\Resources\UserResource;

class AuthController
{
    public function __construct(
        private RegisterService $registerService,
        private LoginService $loginService,
        private LogoutService $logoutService,
        private MeService $meService,
        private ForgotPasswordService $forgotPasswordService,
        private ResetPasswordService $resetPasswordService,
        private UpdateUserPasswordService $updateUserPasswordService,
    ) {}

    public function create(RegisterUserRequest $request)
    {
        $user = $this->registerService->execute($request->toArray());

        return new UserResource($user);
    }

    public function login(LoginRequest $request)
    {
        $user = $this->loginService->execute(
            $request->email,
            $request->password,
            $request->remember_me ?? false,
        );

        return response()->json(
            [
                'user' => $user,
            ],
            200,
        );
    }

    public function logout()
    {
        $this->logoutService->execute();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    public function me()
    {
        $user = $this->meService->execute();

        return response()->json(
            [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'location_id' => $user->location_id,
                    'ibo' => $user->ibo,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'role' => $user->role,
                    'type_of_entreprenur' => $user->type_of_entreprenur?->value,
                    'bsm_eligible' => (bool) $user->bsm_eligible,
                    'cardnet_unique_id' => $user->cardNetCustomer?->unique_id,
                    'has_subscription' => $user->has_subscription,
                    'card_net_customer' => $user->cardNetCustomer
                        ? [
                            'customer_id' => $user->cardNetCustomer->customer_id,
                            'unique_id' => $user->cardNetCustomer->unique_id,
                        ]
                        : null,
                ],
            ],
            200,
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $message = $this->forgotPasswordService->execute($request->email);

        return response()->json(['message' => $message], 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $message = $this->resetPasswordService->execute($request->validated());

        return response()->json(['message' => $message], 200);
    }

    public function updateUserPassword(
        int $userId,
        UpdateUserPasswordRequest $request,
    ) {
        $validated = $request->validated();

        $this->updateUserPasswordService->execute(
            $userId,
            $validated['current_password'],
            $validated['password'],
        );

        return response()->json(
            ['message' => 'Contraseña actualizada correctamente.'],
            200,
        );
    }
}
