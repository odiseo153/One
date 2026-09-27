<?php

namespace App\Modules\Business\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Core\Support\EntitySearchHelper;
use App\Models\Business as BusinessModel;
use App\Models\BusinessCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** @extends BaseRepository<BusinessModel> */
class BusinessRepository extends BaseRepository
{
    protected string $defaultSort = 'name';

    public function __construct()
    {
        parent::__construct(BusinessModel::class);
    }

    protected function getFilters(): array
    {
        return [
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
    }

    protected function getSorts(): array
    {
        return [
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
    }

    protected function getWith(): array
    {
        return [
            'sector',
            'inspector',
            'municipality',
            'primaryCiiu',
            'secondaryCiiu',
            'employees',
        ];
    }

    /**
     * @return LengthAwarePaginator<int, BusinessModel>
     */
    public function getForTable(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAll(
            $perPage,
            'name',
            [
                'sector:id,name',
                'municipality:id,name,province_id',
                'municipality.province:id,name',
                'inspector:id,name',
            ],
        );
    }

    /**
     * @return EloquentCollection<int, BusinessModel>
     */
    public function getForMap(?int $municipalityId = null, ?int $provinceId = null): EloquentCollection
    {
        $query = $this->query(array_filter([
            'municipality_id' => $municipalityId,
            'province_id' => $municipalityId ? null : $provinceId,
        ], fn (mixed $value): bool => $value !== null))->defaultSort('name');

        return $query->with([
            'sector:id,name',
            'primaryCiiu:id,code,name',
            'secondaryCiiu:id,code,name',
            'employees:id,business_id,first_name,last_name,document_type,document_number,salary',
        ])->get();
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

    /**
     * @return array<int, array{id: int, code: string, name: string}>
     */
    public function getCategories(): array
    {
        return BusinessCategory::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (BusinessCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
            ])
            ->all();
    }

    /**
     * @return EloquentCollection<int, BusinessModel>
     */
    public function getForMatching(?int $municipalityId, ?int $sectorId): EloquentCollection
    {
        return BusinessModel::query()
            ->with('sector:id,name')
            ->when($sectorId, fn ($query) => $query->where('sector_id', $sectorId))
            ->when(! $sectorId && $municipalityId, fn ($query) => $query->where('municipality_id', $municipalityId))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $employees
     */
    public function createWithEmployees(array $data, array $employees): BusinessModel
    {
        return DB::transaction(function () use ($data, $employees): BusinessModel {
            $business = $this->create($data);
            $business->employees()->createMany($employees);

            return $business->load('employees');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $employees
     */
    public function updateWithEmployees(int $businessId, array $data, array $employees): BusinessModel
    {
        return DB::transaction(function () use ($businessId, $data, $employees): BusinessModel {
            $business = $this->update($businessId, $data);
            $business->employees()->delete();
            $business->employees()->createMany($employees);

            return $business->load('employees');
        });
    }
}
