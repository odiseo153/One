<?php

namespace App\Modules\User\Adapters\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Core\Support\EntitySearchHelper;
use App\Models\Municipality;
use App\Models\Sector;
use App\Models\User as UserModel;
use App\Modules\User\Domain\Contracts\UserRepositoryPort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** @extends BaseRepository<UserModel> */
class UserRepository extends BaseRepository implements UserRepositoryPort
{
    public function __construct()
    {
        parent::__construct(UserModel::class);
    }

    /**
     * Setup User-specific filters, sorts and includes
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
                ['columns' => ['name', 'email', 'phone']],
            )),
            AllowedFilter::callback('status', function ($query, mixed $value): void {
                $value === 'inactive' ? $query->whereNotNull('deleted_at') : $query->whereNull('deleted_at');
            }),
            AllowedFilter::exact('municipality_id'),
            AllowedFilter::exact('sector_id'),
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
            AllowedSort::field('municipality_id'),
            AllowedSort::field('sector_id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];
    }

    protected function getWith(): array
    {
        return [
            'municipality',
            'sector',
        ];
    }

    /**
     * @return EloquentCollection<int, UserModel>
     */
    public function getForMap(?int $municipalityId = null, bool $fallbackToAll = false): EloquentCollection
    {
        $users = $this->query(array_filter([
            'municipality_id' => $municipalityId,
        ], fn (mixed $value): bool => $value !== null))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'municipality_id', 'sector_id']);

        if (! $fallbackToAll || $users->isNotEmpty() || ! $municipalityId) {
            return $users;
        }

        return UserModel::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'municipality_id', 'sector_id']);
    }

    public function getManagementData(?string $search, string $status): array
    {
        return [
            'users' => $this->getAll(
                15,
                '-id',
                [
                    'municipality' => fn ($query) => $query->withTrashed(),
                    'sector' => fn ($query) => $query->withTrashed(),
                ],
                compact('search', 'status'),
            ),
            'municipalities' => Municipality::query()->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
            'sectors' => Sector::query()->whereNull('deleted_at')->orderBy('name')->get(['id', 'municipality_id', 'name']),
        ];
    }

    public function inspectorBelongsToMunicipality(int $inspectorId, int $municipalityId): bool
    {
        return UserModel::query()
            ->where('id', $inspectorId)
            ->where('municipality_id', $municipalityId)
            ->exists();
    }
}
