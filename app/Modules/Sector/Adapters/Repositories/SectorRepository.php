<?php

namespace App\Modules\Sector\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Sector as SectorModel;
use App\Modules\Sector\Domain\Contracts\SectorRepositoryPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;

class SectorRepository extends BaseRepository implements SectorRepositoryPort
{
    public function __construct()
    {
        parent::__construct(new SectorModel);
    }

    /**
     * Setup Sector-specific filters, sorts and includes
     * Customize this method to define what can be filtered, sorted, and included
     */
    protected function setupDefaults(): void
    {
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];

        $this->allowedSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('municipality_id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];

        $this->allowedIncludes = [
            AllowedInclude::relationship('municipality'),
        ];

        $this->defaultSort = '-created_at';
    }

    /**
     * @param  array<int, string>  $with
     * @return LengthAwarePaginator<int, SectorModel>
     */
    public function getAll(int $perPage, ?string $defaultSort = null, array $with = []): LengthAwarePaginator
    {
        // Spatie Query Builder will automatically handle:
        // - Filtering: GET /sectors?filter[name]=example&filter[status]=active
        // - Sorting: GET /sectors?sort=-created_at,name
        // - Including: GET /sectors?include=user,category
        // - Combining: GET /sectors?filter[status]=active&sort=-created_at&include=user

        return parent::getAll($perPage, $defaultSort, $with);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SectorModel
    {
        return SectorModel::create($data)->load($this->includeNames());
    }

    /**
     * @param  int|string  $id
     */
    public function findById($id): SectorModel
    {
        return SectorModel::with($this->includeNames())->findOrFail($id);
    }

    /**
     * @return EloquentCollection<int, SectorModel>
     */
    public function getForMap(
        ?int $municipalityId = null,
        bool $fallbackToAll = false,
        ?int $provinceId = null,
    ): EloquentCollection {
        $query = SectorModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name');

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        } elseif ($provinceId) {
            $query->whereHas('municipality', fn ($municipality) => $municipality->where('province_id', $provinceId));
        }

        $sectors = $query->get(['id', 'municipality_id', 'name', 'geojson_polygon']);

        if (! $fallbackToAll || $sectors->isNotEmpty() || ! $municipalityId) {
            return $sectors;
        }

        return SectorModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'municipality_id', 'name', 'geojson_polygon']);
    }
}
