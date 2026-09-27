<?php

namespace App\Modules\Sector\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Core\Support\EntitySearchHelper;
use App\Models\Municipality;
use App\Models\Sector as SectorModel;
use App\Modules\Sector\Domain\Contracts\SectorRepositoryPort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** @extends BaseRepository<SectorModel> */
class SectorRepository extends BaseRepository implements SectorRepositoryPort
{
    public function __construct()
    {
        parent::__construct(SectorModel::class);
    }

    /**
     * Setup Sector-specific filters, sorts and includes
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
                ['columns' => ['name']],
            )),
            AllowedFilter::callback('status', function ($query, mixed $value): void {
                $value === 'inactive' ? $query->whereNotNull('deleted_at') : $query->whereNull('deleted_at');
            }),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::callback('province_id', fn ($query, $value) => $query->whereHas(
                'municipality',
                fn ($municipality) => $municipality->where('province_id', $value),
            )),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];
    }

    protected function getSorts(): array
    {
        return [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('municipality_id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];
    }

    protected function getWith(): array
    {
        return ['municipality'];
    }

    /**
     * @return EloquentCollection<int, SectorModel>
     */
    public function getForMap(
        ?int $municipalityId = null,
        bool $fallbackToAll = false,
        ?int $provinceId = null,
    ): EloquentCollection {
        $sectors = $this->query(array_filter([
            'municipality_id' => $municipalityId,
            'province_id' => $municipalityId ? null : $provinceId,
        ], fn (mixed $value): bool => $value !== null))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'municipality_id', 'name', 'geojson_polygon']);

        if (! $fallbackToAll || $sectors->isNotEmpty() || ! $municipalityId) {
            return $sectors;
        }

        return SectorModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'municipality_id', 'name', 'geojson_polygon']);
    }

    public function getManagementData(?string $search, string $status): array
    {
        return [
            'sectors' => $this->getAll(
                15,
                '-id',
                ['municipality' => fn ($query) => $query->withTrashed()],
                compact('search', 'status'),
            ),
            'municipalities' => Municipality::query()->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function municipalityId(int $sectorId): ?int
    {
        $municipalityId = SectorModel::query()->where('id', $sectorId)->value('municipality_id');

        return $municipalityId ? (int) $municipalityId : null;
    }

    public function municipalityHasSectors(int $municipalityId): bool
    {
        return SectorModel::query()
            ->where('municipality_id', $municipalityId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function belongsToMunicipality(int $sectorId, int $municipalityId): bool
    {
        return SectorModel::query()
            ->where('id', $sectorId)
            ->where('municipality_id', $municipalityId)
            ->exists();
    }
}
