<?php

namespace App\Modules\Municipality\Adapters\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Municipality\Domain\Services\CreateMunicipalityService;
use App\Modules\Municipality\Domain\Services\DeleteMunicipalityService;
use App\Modules\Municipality\Domain\Services\FindByIdMunicipalityService;
use App\Modules\Municipality\Domain\Services\ListMunicipalitiesService;
use App\Modules\Municipality\Domain\Services\UpdateMunicipalityService;
use App\Modules\Municipality\Http\Requests\StoreMunicipalityRequest;
use App\Modules\Municipality\Http\Resources\MunicipalityResource;

class MunicipalityController extends BaseController
{
    public function __construct(
        CreateMunicipalityService $createService,
        ListMunicipalitiesService $listService,
        FindByIdMunicipalityService $findByIdService,
        UpdateMunicipalityService $updateService,
        DeleteMunicipalityService $deleteService
    ) {
        $this->createService = $createService;
        $this->listService = $listService;
        $this->findByIdService = $findByIdService;
        $this->updateService = $updateService;
        $this->deleteService = $deleteService;
        $this->resourceClass = MunicipalityResource::class;
        $this->storeRequestClass = StoreMunicipalityRequest::class;
    }
}
