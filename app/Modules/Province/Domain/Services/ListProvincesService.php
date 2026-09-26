<?php

namespace App\Modules\Province\Domain\Services;

use App\Core\Services\ListService;
use App\Modules\Province\Adapters\Repositories\ProvinceRepository;

class ListProvincesService extends ListService
{
    public function __construct(ProvinceRepository $repository)
    {
        parent::__construct($repository);
    }
}
