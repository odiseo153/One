<?php

namespace App\Modules\Province\Domain\Services;

use App\Core\Services\FindByIdService;
use App\Modules\Province\Adapters\Repositories\ProvinceRepository;

class FindByIdProvinceService extends FindByIdService
{
    public function __construct(ProvinceRepository $repository)
    {
        parent::__construct($repository);
    }
}
