<?php

namespace App\Modules\Province\Domain\Services;

use App\Core\Services\UpdateService;
use App\Modules\Province\Adapters\Repositories\ProvinceRepository;

class UpdateProvinceService extends UpdateService
{
    public function __construct(ProvinceRepository $repository)
    {
        parent::__construct($repository);
    }
}
