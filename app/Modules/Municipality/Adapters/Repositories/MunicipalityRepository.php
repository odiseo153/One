<?php

namespace App\Modules\Municipality\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Core\Support\EntitySearchHelper;
use App\Models\Municipality as MunicipalityModel;
use App\Models\Province;
use App\Modules\Municipality\Domain\Contracts\MunicipalityRepositoryPort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** @extends BaseRepository<MunicipalityModel> */
class MunicipalityRepository extends BaseRepository implements MunicipalityRepositoryPort
{
    public function __construct()
    {
        parent::__construct(MunicipalityModel::class);
    }

    /**
     * Setup Municipality-specific filters, sorts and includes
     * Customize this method to define what can be filtered, sorted, and included
     */
    protected function getFilters(): array
    {
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::callback('search', fn ($query, $value) => EntitySearchHelper::apply(
                $query,
                $value,
                ['columns' => ['name', 'domain', 'subdomain']],
            )),
            AllowedFilter::callback('status', function ($query, mixed $value): void {
                $value === 'inactive' ? $query->whereNotNull('deleted_at') : $query->whereNull('deleted_at');
            }),
            AllowedFilter::exact('province_id'),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];
    }

    protected function getSorts(): array
    {
        return [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('status'),
            AllowedSort::field('province_id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];
    }

    protected function getWith(): array
    {
        return ['province', 'users', 'sectors'];
    }

    /**
     * @return EloquentCollection<int, MunicipalityModel>
     */
    public function getActiveOptions(?int $provinceId = null): EloquentCollection
    {
        return $this->query(array_filter([
            'province_id' => $provinceId,
        ], fn (mixed $value): bool => $value !== null))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'province_id', 'geojson_polygon']);
    }

    public function getManagementData(?string $search, string $status): array
    {
        return [
            'municipalities' => $this->getAll(
                15,
                '-id',
                ['province' => fn ($query) => $query->withTrashed()],
                compact('search', 'status'),
            ),
            'provinces' => Province::query()->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function options(?int $municipalityId = null): array
    {
        return MunicipalityModel::query()
            ->when($municipalityId, fn ($query) => $query->where('id', $municipalityId))
            ->when(! $municipalityId, fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function findOption(?int $municipalityId): ?array
    {
        return $municipalityId
            ? MunicipalityModel::query()->find($municipalityId, ['id', 'name'])?->toArray()
            : null;
    }
}
