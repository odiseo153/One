<?php

namespace App\Core\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class BaseRepository
{
    protected $model;

    protected $allowedFilters = [];

    protected $allowedSorts = [];

    protected $allowedIncludes = [];

    protected $allowedCounts = [];

    protected $defaultSort = '-created_at';

    /** @var class-string|null Clase de la entidad del módulo (ej: User::class) */
    protected $entityClass = null;

    public function __construct(Model $modelClass)
    {
        $this->model = $modelClass;
        $this->setupDefaults();
    }

    /**
     * Setup default filters, sorts and includes
     * Override this method in child repositories to customize
     */
    protected function setupDefaults()
    {
        $this->allowedFilters = [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('created_at'),
            AllowedFilter::exact('updated_at'),
        ];

        $this->allowedSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('status'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];

        $this->allowedIncludes = [];
    }

    /**
     * Hook que se ejecuta después de crear un modelo.
     * Los repositorios hijos pueden sobrescribirlo para lógica extra.
     */
    protected function afterCreate(Model $model, array $data): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook que se ejecuta después de actualizar un modelo.
     */
    protected function afterUpdate(Model $model, array $data): void
    {
        // Override in child classes if needed
    }

    /**
     * Convierte un modelo Eloquent a la entidad del módulo si está configurada.
     */
    protected function toEntity(?Model $model): mixed
    {
        if ($model === null) {
            return null;
        }

        if ($this->entityClass) {
            return new $this->entityClass($model->toArray());
        }

        return $model;
    }

    /**
     * Busca el modelo Eloquent por ID (interno, siempre devuelve el modelo).
     */
    protected function findModelById($id): Model
    {
        $query = $this->model->newQuery();

        if (! empty($this->allowedIncludes)) {
            $query->with($this->includeNames());
        }

        return $query->findOrFail($id);
    }

    public function getAll(
        int $perPage,
        ?string $defaultSort = null,
        array $with = [],
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 1000));

        $query = QueryBuilder::for($this->model->query())
            ->withTrashed()
            ->allowedFilters(...$this->allowedFilters)
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes)
            ->withCount($this->allowedCounts)
            ->defaultSort($defaultSort ?? $this->defaultSort);

        if (! empty($with)) {
            $query->with($with);
        } elseif (! empty($this->allowedIncludes)) {
            $query->with($this->includeNames());
        }

        $paginator = $query->paginate($perPage);

        // Si hay entidad configurada, transforma cada item a la entidad del módulo
        if ($this->entityClass) {
            $entityClass = $this->entityClass;
            $paginator
                ->getCollection()
                ->transform(function ($model) use ($entityClass) {
                    return new $entityClass($model->toArray());
                });
        }

        return $paginator;
    }

    public function query(): QueryBuilder
    {
        return QueryBuilder::for($this->model->newQuery())
            ->allowedFilters(...$this->allowedFilters)
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes)
            ->defaultSort($this->defaultSort);
    }

    public function create(array $data)
    {
        $model = $this->model->create($data);

        $this->afterCreate($model, $data);

        return $this->toEntity($model);
    }

    public function findById($id)
    {
        $model = $this->findModelById($id);

        return $this->toEntity($model);
    }

    public function update($id, array $data)
    {
        $model = $this->findModelById($id);
        $model->update($data);
        $model->refresh();

        $this->afterUpdate($model, $data);

        return $this->toEntity($model);
    }

    public function delete($id)
    {
        $model = $this->findModelById($id);

        return $model->delete();
    }

    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    public function getAllowedIncludes(): array
    {
        return $this->allowedIncludes;
    }

    protected function includeNames(): array
    {
        return array_map(
            fn ($include) => $include->getName(),
            $this->allowedIncludes,
        );
    }
}
