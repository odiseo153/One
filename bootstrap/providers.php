<?php

use App\Modules\Project\ProjectServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ProjectServiceProvider::class,
];
