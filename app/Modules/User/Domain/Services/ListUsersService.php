<?php

namespace App\Modules\User\Domain\Services;

use App\Core\Services\ListService;
use App\Modules\User\Adapters\Repositories\UserRepository;

class ListUsersService extends ListService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
