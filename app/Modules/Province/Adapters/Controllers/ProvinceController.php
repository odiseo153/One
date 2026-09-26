<?php

namespace App\Modules\Province\Adapters\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Province\Domain\Services\CreateProvinceService;
use App\Modules\Province\Domain\Services\DeleteProvinceService;
use App\Modules\Province\Domain\Services\FindByIdProvinceService;
use App\Modules\Province\Domain\Services\ListProvincesService;
use App\Modules\Province\Domain\Services\UpdateProvinceService;
use App\Modules\Province\Http\Requests\StoreProvinceRequest;
use App\Modules\Province\Http\Resources\ProvinceResource;

class ProvinceController extends BaseController
{
    public function __construct(
        CreateProvinceService $createService,
        ListProvincesService $listService,
        FindByIdProvinceService $findByIdService,
        UpdateProvinceService $updateService,
        DeleteProvinceService $deleteService
    ) {
        $this->createService = $createService;
        $this->listService = $listService;
        $this->findByIdService = $findByIdService;
        $this->updateService = $updateService;
        $this->deleteService = $deleteService;
        $this->resourceClass = ProvinceResource::class;
        $this->storeRequestClass = StoreProvinceRequest::class;
    }
}
