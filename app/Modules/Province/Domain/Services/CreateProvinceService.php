<?php

namespace App\Modules\Province\Domain\Services;

use App\Core\Services\CreateService;
use App\Modules\Province\Adapters\Repositories\ProvinceRepository;

class CreateProvinceService extends CreateService
{
    public function __construct(ProvinceRepository $repository)
    {
        parent::__construct($repository);
    }
}
