<?php

namespace App\Modules\User\Domain\Services;

use App\Core\Services\UpdateService;
use App\Modules\User\Adapters\Repositories\UserRepository;

class UpdateUserService extends UpdateService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
