<?php

namespace App\Modules\Complaint\Domain\Services;

use App\Core\Services\UpdateService;
use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;

class UpdateComplaintService extends UpdateService
{
    public function __construct(ComplaintRepository $repository)
    {
        parent::__construct($repository);
    }
}
