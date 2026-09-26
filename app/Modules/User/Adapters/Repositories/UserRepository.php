<?php

namespace App\Modules\User\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User as UserModel;
use App\Modules\User\Domain\Contracts\UserRepositoryPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;

class UserRepository extends BaseRepository implements UserRepositoryPort
{
    public function __construct()
    {
        parent::__construct(new UserModel);
    }

    /**
     * Setup User-specific filters, sorts and includes
     * Customize this method to define what can be filtered, sorted, and included
     */
    protected function setupDefaults(): void
    {
        // Define allowed filters for User
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'), // Example: partial search on name
            AllowedFilter::exact('status'),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::exact('sector_id'),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
            // Add more filters as needed:
            // AllowedFilter::exact('user_id'),
            // AllowedFilter::scope('created_after'), // Requires scope in model
            // AllowedFilter::scope('active'), // Requires scope in model
        ];

        // Define allowed sorts for User
        $this->allowedSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('status'),
            AllowedSort::field('municipality_id'),
            AllowedSort::field('sector_id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
            // Add more sorts as needed:
            // AllowedSort::field('user_id'),
        ];

        // Define allowed includes (relationships) for User
        $this->allowedIncludes = [
            AllowedInclude::relationship('municipality'),
            AllowedInclude::relationship('sector'),
        ];

        // Set default sort
        $this->defaultSort = '-created_at';
    }

    /**
     * @param  array<int, string>  $with
     * @return LengthAwarePaginator<int, UserModel>
     */
    public function getAll(int $perPage, ?string $defaultSort = null, array $with = []): LengthAwarePaginator
    {
        // Spatie Query Builder will automatically handle:
        // - Filtering: GET /users?filter[name]=example&filter[status]=active
        // - Sorting: GET /users?sort=-created_at,name
        // - Including: GET /users?include=user,category
        // - Combining: GET /users?filter[status]=active&sort=-created_at&include=user

        return parent::getAll($perPage, $defaultSort, $with);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UserModel
    {
        return UserModel::create($data)->load($this->includeNames());
    }

    /**
     * @param  int|string  $id
     */
    public function findById($id): UserModel
    {
        return UserModel::with($this->includeNames())->findOrFail($id);
    }

    /**
     * @return EloquentCollection<int, UserModel>
     */
    public function getForMap(?int $municipalityId = null, bool $fallbackToAll = false): EloquentCollection
    {
        $query = UserModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name');

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        $users = $query->get(['id', 'name', 'municipality_id', 'sector_id']);

        if (! $fallbackToAll || $users->isNotEmpty() || ! $municipalityId) {
            return $users;
        }

        return UserModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'municipality_id', 'sector_id']);
    }
}
