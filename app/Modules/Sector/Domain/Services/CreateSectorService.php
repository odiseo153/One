<?php

namespace App\Modules\Sector\Domain\Services;

use App\Core\Services\CreateService;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;

class CreateSectorService extends CreateService
{
    public function __construct(SectorRepository $repository)
    {
        parent::__construct($repository);
    }
}
