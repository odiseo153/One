<?php

namespace App\Modules\Province\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Province as ProvinceModel;
use App\Modules\Province\Domain\Contracts\ProvinceRepositoryPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;

class ProvinceRepository extends BaseRepository implements ProvinceRepositoryPort
{
    public function __construct()
    {
        parent::__construct(new ProvinceModel);
    }

    /**
     * Setup Province-specific filters, sorts and includes
     * Customize this method to define what can be filtered, sorted, and included
     */
    protected function setupDefaults(): void
    {
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];

        $this->allowedSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];

        $this->allowedIncludes = [
            AllowedInclude::relationship('municipalities'),
        ];

        $this->defaultSort = '-created_at';
    }

    /**
     * @param  array<int, string>  $with
     * @return LengthAwarePaginator<int, ProvinceModel>
     */
    public function getAll(int $perPage, ?string $defaultSort = null, array $with = []): LengthAwarePaginator
    {
        // Spatie Query Builder will automatically handle:
        // - Filtering: GET /provinces?filter[name]=example&filter[status]=active
        // - Sorting: GET /provinces?sort=-created_at,name
        // - Including: GET /provinces?include=user,category
        // - Combining: GET /provinces?filter[status]=active&sort=-created_at&include=user

        return parent::getAll($perPage, $defaultSort, $with);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProvinceModel
    {
        return ProvinceModel::create($data)->load($this->includeNames());
    }

    /**
     * @param  int|string  $id
     */
    public function findById($id): ProvinceModel
    {
        return ProvinceModel::with($this->includeNames())->findOrFail($id);
    }

    /**
     * @return EloquentCollection<int, ProvinceModel>
     */
    public function getActiveOptions(): EloquentCollection
    {
        return ProvinceModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'geojson_polygon']);
    }
}
