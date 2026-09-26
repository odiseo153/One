<?php

namespace App\Core\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class EntityDateHelper
{
    public static function apply(
        Builder $query,
        mixed $firstDate,
        mixed $secondDate = null,
        bool $searchRange = false,
        string $column = 'created_at',
        bool $treatAsDateTime = true,
    ): void {
        [$from, $to] = self::resolveBounds(
            $firstDate,
            $secondDate,
            $searchRange,
        );

        if ($from === null && $to === null) {
            return;
        }

        if ($from !== null && $to !== null) {
            $query->whereBetween($column, [
                self::normalizeStart($from, $treatAsDateTime),
                self::normalizeEnd($to, $treatAsDateTime),
            ]);

            return;
        }

        if ($from !== null) {
            if ($searchRange) {
                $query->where(
                    $column,
                    '>=',
                    self::normalizeStart($from, $treatAsDateTime),
                );

                return;
            }

            if ($treatAsDateTime) {
                $query->whereBetween($column, [
                    self::normalizeStart($from, true),
                    self::normalizeEnd($from, true),
                ]);

                return;
            }

            $query->whereDate($column, self::parseDate($from)->toDateString());
        }
    }

    public static function applyFrom(
        Builder $query,
        mixed $value,
        string $column = 'created_at',
        bool $treatAsDateTime = true,
    ): void {
        $date = self::parseNullableDate($value);

        if ($date === null) {
            return;
        }

        $query->where(
            $column,
            '>=',
            self::normalizeStart($date, $treatAsDateTime),
        );
    }

    public static function applyTo(
        Builder $query,
        mixed $value,
        string $column = 'created_at',
        bool $treatAsDateTime = true,
    ): void {
        $date = self::parseNullableDate($value);

        if ($date === null) {
            return;
        }

        $query->where(
            $column,
            '<=',
            self::normalizeEnd($date, $treatAsDateTime),
        );
    }

    private static function resolveBounds(
        mixed $firstDate,
        mixed $secondDate,
        bool $searchRange,
    ): array {
        if (is_array($firstDate)) {
            $from = self::parseNullableDate($firstDate[0] ?? null);
            $to = self::parseNullableDate($firstDate[1] ?? null);

            return [$from, $to];
        }

        $from = self::parseNullableDate($firstDate);
        $to = $searchRange ? self::parseNullableDate($secondDate) : null;

        return [$from, $to];
    }

    private static function parseNullableDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private static function parseDate(mixed $value): Carbon
    {
        return self::parseNullableDate($value) ?? Carbon::parse($value);
    }

    private static function normalizeStart(
        Carbon $date,
        bool $treatAsDateTime,
    ): string {
        return $treatAsDateTime
            ? $date->copy()->startOfDay()->toDateTimeString()
            : $date->toDateString();
    }

    private static function normalizeEnd(
        Carbon $date,
        bool $treatAsDateTime,
    ): string {
        return $treatAsDateTime
            ? $date->copy()->endOfDay()->toDateTimeString()
            : $date->toDateString();
    }
}
