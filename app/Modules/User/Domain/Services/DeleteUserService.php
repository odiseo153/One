<?php

namespace App\Modules\User\Domain\Services;

use App\Core\Services\DeleteService;
use App\Modules\User\Adapters\Repositories\UserRepository;

class DeleteUserService extends DeleteService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
