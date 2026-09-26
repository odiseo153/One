<?php

namespace App\Modules\Complaint\Domain\Services;

use App\Core\Services\ListService;
use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;

class ListComplaintsService extends ListService
{
    public function __construct(ComplaintRepository $repository)
    {
        parent::__construct($repository);
    }
}
