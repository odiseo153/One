<?php

namespace App\Modules\Sector\Adapters\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Sector\Domain\Services\CreateSectorService;
use App\Modules\Sector\Domain\Services\DeleteSectorService;
use App\Modules\Sector\Domain\Services\FindByIdSectorService;
use App\Modules\Sector\Domain\Services\ListSectorsService;
use App\Modules\Sector\Domain\Services\UpdateSectorService;
use App\Modules\Sector\Http\Requests\StoreSectorRequest;
use App\Modules\Sector\Http\Resources\SectorResource;

class SectorController extends BaseController
{
    public function __construct(
        CreateSectorService $createService,
        ListSectorsService $listService,
        FindByIdSectorService $findByIdService,
        UpdateSectorService $updateService,
        DeleteSectorService $deleteService
    ) {
        $this->createService = $createService;
        $this->listService = $listService;
        $this->findByIdService = $findByIdService;
        $this->updateService = $updateService;
        $this->deleteService = $deleteService;
        $this->resourceClass = SectorResource::class;
        $this->storeRequestClass = StoreSectorRequest::class;
    }
}
