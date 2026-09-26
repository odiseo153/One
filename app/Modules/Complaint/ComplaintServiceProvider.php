<?php

namespace App\Modules\Complaint;

use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;
use App\Modules\Complaint\Domain\Contracts\ComplaintRepositoryPort;
use Illuminate\Support\ServiceProvider;

class ComplaintServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ComplaintRepositoryPort::class, ComplaintRepository::class);
    }

    public function boot()
    {
        // Register routes, views, etc.
    }
}
