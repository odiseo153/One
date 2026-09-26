<?php

namespace App\Modules\Municipality\Domain\Services;

use App\Core\Services\DeleteService;
use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;

class DeleteMunicipalityService extends DeleteService
{
    public function __construct(MunicipalityRepository $repository)
    {
        parent::__construct($repository);
    }
}
