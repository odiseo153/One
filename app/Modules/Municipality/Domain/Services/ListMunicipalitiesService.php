<?php

namespace App\Modules\Municipality\Domain\Services;

use App\Core\Services\ListService;
use App\Modules\Municipality\Adapters\Repositories\MunicipalityRepository;

class ListMunicipalitiesService extends ListService
{
    public function __construct(MunicipalityRepository $repository)
    {
        parent::__construct($repository);
    }
}
