<?php

namespace App\Modules\Municipality\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Municipality as MunicipalityModel;
use App\Modules\Municipality\Domain\Contracts\MunicipalityRepositoryPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;

class MunicipalityRepository extends BaseRepository implements MunicipalityRepositoryPort
{
    public function __construct()
    {
        parent::__construct(new MunicipalityModel);
    }

    /**
     * Setup Municipality-specific filters, sorts and includes
     * Customize this method to define what can be filtered, sorted, and included
     */
    protected function setupDefaults(): void
    {
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('province_id'),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];

        $this->allowedSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('status'),
            AllowedSort::field('province_id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];

        $this->allowedIncludes = [
            AllowedInclude::relationship('province'),
            AllowedInclude::relationship('users'),
            AllowedInclude::relationship('sectors'),
        ];

        $this->defaultSort = '-created_at';
    }

    /**
     * @param  array<int, string>  $with
     * @return LengthAwarePaginator<int, MunicipalityModel>
     */
    public function getAll(int $perPage, ?string $defaultSort = null, array $with = []): LengthAwarePaginator
    {
        // Spatie Query Builder will automatically handle:
        // - Filtering: GET /municipalitys?filter[name]=example&filter[status]=active
        // - Sorting: GET /municipalitys?sort=-created_at,name
        // - Including: GET /municipalitys?include=user,category
        // - Combining: GET /municipalitys?filter[status]=active&sort=-created_at&include=user

        return parent::getAll($perPage, $defaultSort, $with);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MunicipalityModel
    {
        return MunicipalityModel::create($data)->load($this->includeNames());
    }

    /**
     * @param  int|string  $id
     */
    public function findById($id): MunicipalityModel
    {
        return MunicipalityModel::with($this->includeNames())->findOrFail($id);
    }

    /**
     * @return EloquentCollection<int, MunicipalityModel>
     */
    public function getActiveOptions(?int $provinceId = null): EloquentCollection
    {
        $query = MunicipalityModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name');

        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }

        return $query->get(['id', 'name', 'province_id', 'geojson_polygon']);
    }
}
