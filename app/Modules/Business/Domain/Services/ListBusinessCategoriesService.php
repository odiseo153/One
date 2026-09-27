<?php

namespace App\Modules\Business\Domain\Services;

use App\Modules\Business\Adapters\Repositories\BusinessRepository;

class ListBusinessCategoriesService
{
    public function __construct(private readonly BusinessRepository $repository) {}

    /**
     * @return array<int, array{id: int, code: string, name: string}>
     */
    public function execute(): array
    {
        return $this->repository->getCategories();
    }
}
