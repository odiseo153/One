<?php

namespace App\Modules\Sector\Domain\Services;

use App\Core\Services\UpdateService;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;

class UpdateSectorService extends UpdateService
{
    public function __construct(SectorRepository $repository)
    {
        parent::__construct($repository);
    }
}
