<?php

namespace App\Modules\User\Adapters\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\User\Domain\Services\CreateUserService;
use App\Modules\User\Domain\Services\DeleteUserService;
use App\Modules\User\Domain\Services\FindByIdUserService;
use App\Modules\User\Domain\Services\ListUsersService;
use App\Modules\User\Domain\Services\UpdateUserService;
use App\Modules\User\Http\Requests\StoreUserRequest;
use App\Modules\User\Http\Resources\UserResource;

class UserController extends BaseController
{
    public function __construct(
        CreateUserService $createService,
        ListUsersService $listService,
        FindByIdUserService $findByIdService,
        UpdateUserService $updateService,
        DeleteUserService $deleteService
    ) {
        $this->createService = $createService;
        $this->listService = $listService;
        $this->findByIdService = $findByIdService;
        $this->updateService = $updateService;
        $this->deleteService = $deleteService;
        $this->resourceClass = UserResource::class;
        $this->storeRequestClass = StoreUserRequest::class;
    }
}
