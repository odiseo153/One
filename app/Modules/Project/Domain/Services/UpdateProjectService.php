<?php

namespace App\Modules\Project\Domain\Services;

use App\Core\Services\UpdateService;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class UpdateProjectService extends UpdateService
{
    public function __construct(ProjectRepository $repository)
    {
        parent::__construct($repository);
    }
}
