<?php

namespace App\Modules\Municipality;

use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;
use App\Modules\Municipality\Domain\Contracts\MunicipalityRepositoryPort;
use Illuminate\Support\ServiceProvider;

class MunicipalityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MunicipalityRepositoryPort::class, MunicipalityRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
