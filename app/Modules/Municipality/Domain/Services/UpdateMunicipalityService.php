<?php

namespace App\Modules\Municipality\Domain\Services;

use App\Core\Services\UpdateService;
use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;

class UpdateMunicipalityService extends UpdateService
{
    public function __construct(MunicipalityRepository $repository)
    {
        parent::__construct($repository);
    }
}
