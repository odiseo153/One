<?php

namespace App\Modules\User\Domain\Services;

use App\Core\Services\FindByIdService;
use App\Modules\User\Adapters\Repositories\UserRepository;

class FindByIdUserService extends FindByIdService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
