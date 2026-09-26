<?php

namespace App\Modules\Project\Domain\Services;

use App\Core\Services\ListService;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;

class ListProjectsService extends ListService
{
    public function __construct(ProjectRepository $repository)
    {
        parent::__construct($repository);
    }
}
