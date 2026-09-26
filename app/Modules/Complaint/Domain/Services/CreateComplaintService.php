<?php

namespace App\Modules\Complaint\Domain\Services;

use App\Core\Services\CreateService;
use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;

class CreateComplaintService extends CreateService
{
    public function __construct(ComplaintRepository $repository)
    {
        parent::__construct($repository);
    }
}
