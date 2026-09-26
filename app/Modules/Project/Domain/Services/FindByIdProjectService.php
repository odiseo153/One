<?php

namespace App\Modules\Project\Domain\Services;

use App\Core\Services\FindByIdService;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class FindByIdProjectService extends FindByIdService
{
    public function __construct(ProjectRepository $repository)
    {
        parent::__construct($repository);
    }
}
