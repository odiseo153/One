<?php

namespace App\Core\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/** @template TModel of Model */
class BaseRepository implements BaseRepositoryPort
{
    protected string $defaultSort = '-created_at';

    /** @var class-string<TModel> */
    protected string $modelClass;

    /** @param class-string<TModel> $modelClass */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): mixed
    {
        $model = $this->modelQuery()->create($data);
        $model->load($this->getWith());
        $this->afterCreate($model, $data);

        return $model;
    }

    /** @return TModel */
    public function findById(int|string $id): mixed
    {
        return $this->findModelById($id);
    }

    /** @param array<string, mixed> $data */
    /** @return TModel */
    public function update(int|string $id, array $data): mixed
    {
        $model = $this->findModelById($id);
        $model->update($data);
        $model->refresh()->load($this->getWith());
        $this->afterUpdate($model, $data);

        return $model;
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->findModelById($id)->delete();
    }

    public function restore(int|string $id): bool
    {
        if (! $this->usesSoftDeletes()) {
            return false;
        }

        $model = $this->modelQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->findOrFail($id);

        $restore = [$model, 'restore'];

        return is_callable($restore) && (bool) $restore();
    }

    public function forceDelete(int|string $id): bool
    {
        $query = $this->modelQuery();

        if ($this->usesSoftDeletes()) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        $model = $query->findOrFail($id);

        if (! $this->usesSoftDeletes()) {
            return (bool) $model->delete();
        }

        $forceDelete = [$model, 'forceDelete'];

        return (bool) $forceDelete();
    }

    /**
     * @param  array<int|string, mixed>  $with
     * @param  array<string, mixed>  $parameters
     * @return LengthAwarePaginator<int, TModel>
     */
    public function getAll(
        int $perPage = 15,
        ?string $defaultSort = null,
        array $with = [],
        array $parameters = [],
    ): LengthAwarePaginator {
        $query = $this->query($parameters)
            ->defaultSort($defaultSort ?? $this->defaultSort);

        if ($with !== []) {
            $query->with($with);
        }

        $paginator = $query
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return QueryBuilder<TModel>
     */
    public function query(array $parameters = []): QueryBuilder
    {
        $query = $this->modelQuery();

        if ($this->usesSoftDeletes()) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        $builder = QueryBuilder::for($query, $this->queryRequest($parameters));
        $builder->allowedFilters(...$this->getFilters());
        $builder->allowedSorts(...$this->getSorts());
        $builder->allowedIncludes(...$this->getWith());
        $builder->with($this->getWith());
        $builder->withCount($this->getCounts());

        return $builder;
    }

    /** @return array<int, AllowedFilter|string> */
    protected function getFilters(): array
    {
        return [];
    }

    /** @return array<int, AllowedSort|string> */
    protected function getSorts(): array
    {
        return [
            AllowedSort::field('id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
            AllowedSort::field('deleted_at'),
        ];
    }

    /** @return array<int, string> */
    protected function getWith(): array
    {
        return [];
    }

    /** @return array<int, string> */
    protected function getCounts(): array
    {
        return [];
    }

    /** @param array<string, mixed> $data */
    protected function afterCreate(Model $model, array $data): void {}

    /** @param array<string, mixed> $data */
    protected function afterUpdate(Model $model, array $data): void {}

    /** @return TModel */
    protected function findModelById(int|string $id): Model
    {
        return $this->modelQuery()
            ->with($this->getWith())
            ->findOrFail($id);
    }

    /** @return Builder<TModel> */
    private function modelQuery(): Builder
    {
        return $this->modelClass::query();
    }

    /** @param array<string, mixed> $parameters */
    private function queryRequest(array $parameters): Request
    {
        $parameters = $parameters !== [] ? $parameters : request()->query();
        $filters = is_array($parameters['filter'] ?? null)
            ? $parameters['filter']
            : [];

        foreach ($this->filterNames() as $filter) {
            if (array_key_exists($filter, $parameters) && ! array_key_exists($filter, $filters)) {
                $filters[$filter] = $parameters[$filter];
            }
        }

        $parameters['filter'] = $filters;

        return Request::create('/', 'GET', $parameters);
    }

    /** @return array<int, string> */
    private function filterNames(): array
    {
        return array_map(
            fn (AllowedFilter|string $filter): string => $filter instanceof AllowedFilter
                ? $filter->getName()
                : $filter,
            $this->getFilters(),
        );
    }

    private function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->modelClass), true);
    }
}
