<?php

namespace App\Modules\Sector\Domain\Services;

use App\Core\Services\FindByIdService;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;

class FindByIdSectorService extends FindByIdService
{
    public function __construct(SectorRepository $repository)
    {
        parent::__construct($repository);
    }
}
