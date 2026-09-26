<?php

namespace App\Modules\Municipality\Domain\Services;

use App\Core\Services\CreateService;
use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;

class CreateMunicipalityService extends CreateService
{
    public function __construct(MunicipalityRepository $repository)
    {
        parent::__construct($repository);
    }
}
