<?php

namespace App\Modules\Sector;

use App\Modules\Sector\Adapters\Repositories\SectorRepository;
use App\Modules\Sector\Domain\Contracts\SectorRepositoryPort;
use Illuminate\Support\ServiceProvider;

class SectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SectorRepositoryPort::class, SectorRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
