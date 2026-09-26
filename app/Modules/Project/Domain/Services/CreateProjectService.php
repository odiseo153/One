<?php

namespace App\Modules\Project\Domain\Services;

use App\Core\Services\CreateService;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class CreateProjectService extends CreateService
{
    public function __construct(ProjectRepository $repository)
    {
        parent::__construct($repository);
    }
}
