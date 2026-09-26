<?php

namespace App\Modules\Province;

use App\Modules\Province\Adapters\Repositories\ProvinceRepository;
use App\Modules\Province\Domain\Contracts\ProvinceRepositoryPort;
use Illuminate\Support\ServiceProvider;

class ProvinceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProvinceRepositoryPort::class, ProvinceRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
