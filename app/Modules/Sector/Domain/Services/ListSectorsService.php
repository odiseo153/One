<?php

namespace App\Modules\Sector\Domain\Services;

use App\Core\Services\ListService;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;

class ListSectorsService extends ListService
{
    public function __construct(SectorRepository $repository)
    {
        parent::__construct($repository);
    }
}
