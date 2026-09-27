<?php

namespace App\Modules\Province\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Province as ProvinceModel;
use App\Modules\Province\Domain\Contracts\ProvinceRepositoryPort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** @extends BaseRepository<ProvinceModel> */
class ProvinceRepository extends BaseRepository implements ProvinceRepositoryPort
{
    public function __construct()
    {
        parent::__construct(ProvinceModel::class);
    }

    protected function getFilters(): array
    {
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];
    }

    protected function getSorts(): array
    {
        return [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];
    }

    protected function getWith(): array
    {
        return ['municipalities'];
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
