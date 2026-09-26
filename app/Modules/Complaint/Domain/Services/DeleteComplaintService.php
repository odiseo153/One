<?php

namespace App\Modules\Complaint\Domain\Services;

use App\Core\Services\DeleteService;
use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;

class DeleteComplaintService extends DeleteService
{
    public function __construct(ComplaintRepository $repository)
    {
        parent::__construct($repository);
    }
}
