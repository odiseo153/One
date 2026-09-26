<?php

namespace App\Core\Support;

use Illuminate\Database\Eloquent\Builder;

class PatientSearchHelper
{
    public static function apply(
        Builder $query,
        mixed $value,
        array $options = [],
    ): void {
        $search = trim((string) (is_array($value) ? $value[0] ?? '' : $value));

        if ($search === '') {
            return;
        }

        $normalized = self::normalize($search);
        $columns = $options['columns'] ?? [
            'patient_code',
            'ssn',
            'first_name',
            'last_name',
            'phone_number',
        ];
        $includeId = $options['include_id'] ?? true;

        $query->where(function (Builder $patientQuery) use (
            $search,
            $normalized,
            $columns,
            $includeId,
        ) {
            $firstColumn = array_shift($columns);

            if ($firstColumn !== null) {
                $patientQuery->where($firstColumn, 'like', '%'.$search.'%');
            }

            foreach ($columns as $column) {
                $patientQuery->orWhere($column, 'like', '%'.$search.'%');
            }

            if ($includeId && is_numeric($search)) {
                $patientQuery->orWhere('id', (int) $search);
            }

            if ($normalized !== '') {
                $patientQuery
                    ->orWhereRaw(
                        self::compactExpression(
                            "CONCAT(first_name, ' ', last_name)",
                        ).' LIKE ?',
                        ['%'.$normalized.'%'],
                    )
                    ->orWhereRaw(
                        self::compactExpression(
                            "CONCAT(last_name, ' ', first_name)",
                        ).' LIKE ?',
                        ['%'.$normalized.'%'],
                    );
            }
        });
    }

    private static function normalize(string $value): string
    {
        $normalized = mb_strtolower($value, 'UTF-8');
        $normalized = preg_replace("/[^\pL\pN]+/u", '', $normalized) ?? '';

        return trim($normalized);
    }

    private static function compactExpression(string $expression): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER({$expression}), ' ', ''), ',', ''), '.', ''), '-', ''), '_', '')";
    }
}
