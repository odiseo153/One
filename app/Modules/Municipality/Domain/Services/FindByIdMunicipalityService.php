<?php

namespace App\Modules\Municipality\Domain\Services;

use App\Core\Services\FindByIdService;
use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;

class FindByIdMunicipalityService extends FindByIdService
{
    public function __construct(MunicipalityRepository $repository)
    {
        parent::__construct($repository);
    }
}
