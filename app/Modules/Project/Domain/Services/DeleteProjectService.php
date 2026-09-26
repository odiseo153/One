<?php

namespace App\Modules\Project\Domain\Services;

use App\Core\Services\DeleteService;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class DeleteProjectService extends DeleteService
{
    public function __construct(ProjectRepository $repository)
    {
        parent::__construct($repository);
    }
}
