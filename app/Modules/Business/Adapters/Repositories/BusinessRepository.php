<?php

namespace App\Modules\Business\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Core\Support\EntitySearchHelper;
use App\Models\Business as BusinessModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class BusinessRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new BusinessModel);
    }

    protected function setupDefaults(): void
    {
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::partial('category'),
            AllowedFilter::callback('search', fn ($query, $value) => EntitySearchHelper::apply(
                $query,
                $value,
                [
                    'columns' => ['name', 'category', 'rnc', 'address_text', 'registration_status'],
                    'relations' => [
                        'sector' => ['name'],
                        'municipality' => ['name'],
                        'municipality.province' => ['name'],
                        'inspector' => ['name'],
                    ],
                ],
            )),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::exact('sector_id'),
            AllowedFilter::exact('registration_status'),
            AllowedFilter::exact('inspector_id'),
            AllowedFilter::partial('rnc'),
            AllowedFilter::callback('province_id', fn ($query, $value) => $query->whereHas(
                'municipality',
                fn ($municipality) => $municipality->where('province_id', $value),
            )),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];

        $this->allowedSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('category'),
            AllowedSort::field('sector_id'),
            AllowedSort::field('registration_status'),
            AllowedSort::field('detected_at'),
            AllowedSort::field('last_verified_at'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];

        $this->allowedIncludes = [
            AllowedInclude::relationship('sector'),
            AllowedInclude::relationship('inspector'),
            AllowedInclude::relationship('municipality'),
        ];

        $this->defaultSort = 'name';
    }

    /**
     * @return LengthAwarePaginator<int, BusinessModel>
     */
    public function getForTable(int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for(BusinessModel::query())
            ->allowedFilters(...$this->allowedFilters)
            ->allowedSorts(...$this->allowedSorts)
            ->defaultSort('name')
            ->with([
                'sector:id,name',
                'municipality:id,name,province_id',
                'municipality.province:id,name',
                'inspector:id,name',
            ])
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();
    }

    /**
     * @return EloquentCollection<int, BusinessModel>
     */
    public function getForMap(?int $municipalityId = null, ?int $provinceId = null): EloquentCollection
    {
        $query = QueryBuilder::for(BusinessModel::query())
            ->allowedFilters(...$this->allowedFilters)
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes)
            ->defaultSort($this->defaultSort);

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        } elseif ($provinceId) {
            $query->whereHas('municipality', fn ($municipality) => $municipality->where('province_id', $provinceId));
        }

        return $query->with('sector:id,name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BusinessModel
    {
        return BusinessModel::create($data)->load('sector:id,name');
    }

    /**
     * @param  int|string  $id
     * @param  array<string, mixed>  $data
     */
    public function update($id, array $data): BusinessModel
    {
        $business = $this->findBusinessById($id);
        $business->update($data);
        $business->refresh();

        return $business->load('sector:id,name');
    }

    /**
     * @param  Collection<int, BusinessModel>  $businesses
     * @return array<string, int>
     */
    public function summary(Collection $businesses): array
    {
        return [
            'total' => $businesses->count(),
            'registered' => $businesses->where('registration_status', 'registered')->count(),
            'unregistered' => $businesses->where('registration_status', 'unregistered')->count(),
            'pending_verification' => $businesses->where('registration_status', 'pending_verification')->count(),
        ];
    }

    private function findBusinessById(int|string $id): BusinessModel
    {
        return BusinessModel::with('sector:id,name')->findOrFail($id);
    }
}
