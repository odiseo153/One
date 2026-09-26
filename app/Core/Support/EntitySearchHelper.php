<?php

namespace App\Core\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EntitySearchHelper
{
    /**
     * @param  Builder<Model>  $query
     * @param  array{columns?: array<int, string>, include_id?: bool, relations?: array<string, array<int, string>>}  $options
     * @param  array<int, array<int, string>>  $concats
     */
    public static function apply(
        Builder $query,
        mixed $value,
        array $options,
        array $concats = [],
    ): void {
        $search = trim((string) (is_array($value) ? $value[0] ?? '' : $value));

        if ($search === '') {
            return;
        }

        $columns = array_values(array_filter(
            $options['columns'] ?? [],
            fn (string $column): bool => $column !== '',
        ));
        $includeId = $options['include_id'] ?? true;
        $relations = $options['relations'] ?? [];

        if (empty($columns) && ! $includeId && empty($relations)) {
            return;
        }

        $query->where(function (Builder $query) use (
            $search,
            $columns,
            $includeId,
            $relations,
        ) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }

            if ($includeId && is_numeric($search)) {
                $query->orWhere('id', (int) $search);
            }

            foreach ($relations as $relation => $relationColumns) {
                if (empty($relationColumns)) {
                    continue;
                }

                $query->orWhereHas(
                    $relation,
                    fn (Builder $relationQuery) => self::apply(
                        $relationQuery,
                        $search,
                        [
                            'columns' => $relationColumns,
                            'include_id' => false,
                        ],
                    ),
                );
            }
        });
    }
}
