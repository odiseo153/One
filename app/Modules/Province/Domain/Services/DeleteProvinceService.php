<?php

namespace App\Modules\Province\Domain\Services;

use App\Core\Services\DeleteService;
use App\Modules\Province\Adapters\Repositories\ProvinceRepository;

class DeleteProvinceService extends DeleteService
{
    public function __construct(ProvinceRepository $repository)
    {
        parent::__construct($repository);
    }
}
