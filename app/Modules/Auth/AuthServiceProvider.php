<?php

namespace App\Modules\Auth;

use App\Modules\Auth\Adapters\Repositories\AuthRepository;
use App\Modules\Auth\Domain\Contracts\AuthRepositoryPort;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(AuthRepositoryPort::class, AuthRepository::class);
    }

    public function boot() {}
}
