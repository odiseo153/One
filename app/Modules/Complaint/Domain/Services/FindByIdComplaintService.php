<?php

namespace App\Modules\Complaint\Domain\Services;

use App\Core\Services\FindByIdService;
use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;

class FindByIdComplaintService extends FindByIdService
{
    public function __construct(ComplaintRepository $repository)
    {
        parent::__construct($repository);
    }
}
