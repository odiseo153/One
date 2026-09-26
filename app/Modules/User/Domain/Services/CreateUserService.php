<?php

namespace App\Modules\User\Domain\Services;

use App\Core\Services\CreateService;
use App\Modules\User\Adapters\Repositories\UserRepository;

class CreateUserService extends CreateService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
