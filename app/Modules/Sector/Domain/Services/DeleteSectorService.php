<?php

namespace App\Modules\Sector\Domain\Services;

use App\Core\Services\DeleteService;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;

class DeleteSectorService extends DeleteService
{
    public function __construct(SectorRepository $repository)
    {
        parent::__construct($repository);
    }
}
