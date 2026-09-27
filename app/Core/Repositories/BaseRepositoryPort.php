<?php

namespace App\Core\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BaseRepositoryPort
{
    /** @param array<string, mixed> $data */
    public function create(array $data): mixed;

    /**
     * @param  array<int|string, mixed>  $with
     * @param  array<string, mixed>  $parameters
     * @return LengthAwarePaginator<int, mixed>
     */
    public function getAll(
        int $perPage = 15,
        ?string $defaultSort = null,
        array $with = [],
        array $parameters = [],
    ): LengthAwarePaginator;

    public function findById(int|string $id): mixed;

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): mixed;

    public function delete(int|string $id): bool;

    public function restore(int|string $id): bool;

    public function forceDelete(int|string $id): bool;
}
